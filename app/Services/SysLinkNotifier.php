<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Controllers\Settings\ApiController;
use App\Models\ApiConnection;
use App\Models\Beacon;
use App\Support\StatusThreshold;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes beacon/siren status to Uplink Sentinel's SysLink EWS endpoint
 * (POST http://<server>:8090/api/syslink/ews/status).
 * The endpoint URL and Bearer token live in the api_connections row named "SysLink".
 */
class SysLinkNotifier
{
    public const CONNECTION_NAME = 'SysLink';

    public function __construct(private ApiController $apiController)
    {
    }

    private static bool $pushQueued = false;

    /**
     * Push once after the current response is sent, so a beacon change reaches Sentinel right away
     * without slowing the request or needing a queue worker. Many changes in one request = one push.
     */
    public static function pushAfterResponse(): void
    {
        if (self::$pushQueued || ! self::connection()) {
            return;
        }
        self::$pushQueued = true;

        dispatch(function () {
            self::$pushQueued = false;
            app(self::class)->push();
        })->afterResponse();
    }

    public static function connection(): ?ApiConnection
    {
        return ApiConnection::where('revoked', false)->where('name', self::CONNECTION_NAME)->first();
    }

    public function buildPayload(): array
    {
        $beacons = Beacon::with('location')->orderBy('name')->get();
        $beaconOffline = $beacons->where('status', false)->count();
        $beaconTotal = $beacons->count();

        // Sirens are aggregate-only (the siren API gives counts, not stations), so they go in the overall message.
        $sirens = $this->apiController->getSirenStatusCounts();
        $sirenText = $sirens === null
            ? 'siren API unreachable'
            : "{$sirens['offline']}/{$sirens['total']} sirens offline";

        $healthy = StatusThreshold::label($beaconOffline, $beaconTotal) !== 'Critical'
            && ($sirens === null || StatusThreshold::label($sirens['offline'], $sirens['total']) !== 'Critical');

        return [
            'system'      => 'EWS',
            'reported_at' => now()->toIso8601String(),
            'overall'     => [
                'status'  => $healthy ? 'ok' : 'warning',
                'message' => "{$beaconOffline}/{$beaconTotal} beacons offline; {$sirenText}",
            ],
            // Optional fields are omitted when empty rather than sent as null.
            'units' => $beacons->map(fn (Beacon $b) => array_filter([
                'id'           => self::unitId($b->beacon_id ?? $b->id),
                'station'      => implode(' · ', array_filter([$b->name, $b->location?->location_name])),
                'type'         => 'Beacon',
                'status'       => $b->status ? 'online' : 'offline',
                'message'      => $b->is_door_open ? 'Door open' : null,
                'last_data_at' => $b->last_seen_at?->toIso8601String(),
            ], fn ($v) => $v !== null && $v !== ''))->values()->all(),
        ];
    }

    /** "7" -> "BCN-07", matching Sentinel's EWS unit IDs; non-numeric IDs are kept as-is. */
    private static function unitId(string|int $beaconId): string
    {
        return 'BCN-' . (ctype_digit((string) $beaconId) ? sprintf('%02d', $beaconId) : $beaconId);
    }

    /**
     * Returns null on success, or a short error message. Never throws, so a Sentinel
     * outage can't break the scheduler or the settings page.
     */
    public function push(): ?string
    {
        $connection = self::connection();
        if (! $connection) {
            return 'SysLink is not configured.';
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withToken($connection->header_value)
                ->post($connection->endpoint_url, $this->buildPayload());

            if ($response->successful()) {
                return $this->remember(null);
            }

            Log::error('SysLink push failed', ['status' => $response->status(), 'body' => $response->body()]);
            return $this->remember("Sentinel returned HTTP {$response->status()}.");
        } catch (\Throwable $e) {
            Log::error('SysLink push error: ' . $e->getMessage());
            return $this->remember('Could not reach Sentinel: ' . $e->getMessage());
        }
    }

    /** Last push result for the settings page: ['at' => Carbon, 'error' => ?string]. */
    public static function lastResult(): ?array
    {
        return Cache::get('syslink.last_push');
    }

    private function remember(?string $error): ?string
    {
        Cache::forever('syslink.last_push', ['at' => now(), 'error' => $error]);
        return $error;
    }
}
