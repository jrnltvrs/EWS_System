<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Models\Siren;
use App\Models\Signal;
use App\Services\SysLinkNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;


class ApiController extends Controller
{
    private function getGlobalConnectionQuery(): Builder
    {
        // The SysLink row is an outbound push target with its own settings page; keep it out of
        // this list and out of the "most recently used" fallback below.
        return ApiConnection::where('revoked', false)
            ->where(fn (Builder $q) => $q->whereNull('name')->orWhere('name', '!=', SysLinkNotifier::CONNECTION_NAME));
    }

    public function index(): View
    {
        return view('settings.api');
    }

    public function getConnections(): JsonResponse
    {
        $connections = $this->getGlobalConnectionQuery()
            ->orderBy('name')
            ->orderByDesc('last_used_at')
            ->get(['id', 'name', 'endpoint_url', 'header_name', 'header_value', 'last_used_at']);

        return response()->json($connections);
    }

    public function autoRegister(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint_url' => 'required|url|max:255',
            'header_name'  => 'required|string|max:100',
            'header_value' => 'required|string|max:255',
            'name'         => 'nullable|string|max:150',
        ]);

        $existing = $this->getGlobalConnectionQuery()
            ->where('endpoint_url', $data['endpoint_url'])
            ->where('header_name', $data['header_name'])
            ->where('header_value', $data['header_value'])
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'already_exists',
                'message' => 'This connection is already saved.',
                'id'      => $existing->id,
            ]);
        }

        $name = $data['name'] ?? $this->generateDefaultName($data['endpoint_url']);

        $connection = ApiConnection::create([
            'user_id'      => Auth::id(),
            'name'         => $name,
            'endpoint_url' => $data['endpoint_url'],
            'header_name'  => $data['header_name'],
            'header_value' => $data['header_value'],
            'last_used_at' => now(),
        ]);

        return response()->json([
            'status'  => 'saved',
            'message' => 'Connection saved successfully.',
            'id'      => $connection->id,
            'name'    => $connection->name,
        ]);
    }

    private function generateDefaultName(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'unknown';
        return "Auto – {$host} (" . now()->format('M d, Y') . ")";
    }

    public function getLastConnection(): JsonResponse
    {
        $connection = $this->getGlobalConnectionQuery()
            ->orderByDesc('last_used_at')
            ->orderByDesc('id')
            ->first(['endpoint_url', 'header_name', 'header_value', 'name']);

        if (!$connection) {
            return response()->json([
                'success' => false,
                'message' => 'No connections found',
            ]);
        }

        return response()->json([
            'success'      => true,
            ...$connection->only(['endpoint_url', 'header_name', 'header_value', 'name']),
        ]);
    }

    public function markAsUsed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connection_id' => 'required|exists:api_connections,id',
        ]);

        $connection = ApiConnection::where('id', $validated['connection_id'])
            ->where('revoked', false)
            ->firstOrFail();

        $connection->update(['last_used_at' => now()]);

        return response()->json([
            'status'       => 'updated',
            'message'      => 'Last used timestamp updated.',
            'last_used_at' => $connection->last_used_at->toDateTimeString(),
        ]);
    }

    public function deleteConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:api_connections,id',
        ]);

        $connection = ApiConnection::where('id', $validated['id'])
            ->where('revoked', false)
            ->firstOrFail();

        $connection->update(['revoked' => true]);

        return response()->json([
            'status'  => 'deleted',
            'message' => 'Connection deleted successfully.',
        ]);
    }

    /**
     * Resolve the named (or most-recently-used) API connection and GET a path on it.
     * Same connection-lookup + request logic as fetchApiData(), pulled out so it can
     * also be called from console commands (no HTTP round trip, no auth session needed).
     * Returns the decoded JSON body, or null on any failure (logged where relevant).
     */
    public function callExternalApi(string $path, ?string $connectionName = null): ?array
    {
        $query = $this->getGlobalConnectionQuery();

        $connection = $connectionName
            ? $query->where('name', $connectionName)->first()
            : $query->orderByDesc('last_used_at')->first();

        if (!$connection) {
            Log::warning('callExternalApi: no active API connection found', [
                'connection_name' => $connectionName,
            ]);
            return null;
        }

        $fullUrl = rtrim($connection->endpoint_url, '/') . '/' . ltrim($path, '/');

        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Accept' => 'application/json',
                    $connection->header_name => $connection->header_value,
                ])
                ->timeout(12)
                ->get($fullUrl);

            if ($response->successful()) {
                $connection->touch('last_used_at');
                return $response->json();
            }

            Log::error('callExternalApi: request failed', [
                'url'    => $fullUrl,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;
        } catch (\Throwable $e) {
            Log::error('callExternalApi: connection error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Siren total/offline counts, from the same "Siren" connection + /api/Data/Status
     * endpoint the dashboard polls (see updateDeviceStatusUI() in controller.blade.php).
     * Returns null if the siren API couldn't be reached.
     */
    public function getSirenStatusCounts(): ?array
    {
        $data = $this->callExternalApi('/api/Data/Status', 'Siren');

        if ($data === null) {
            return null;
        }

        $totalValue = $data['totalConnectionsCount'] ?? null;
        $offlineValue = $data['badConnectionsCount'] ?? null;
        if (! is_numeric($totalValue) || ! is_numeric($offlineValue)) {
            Log::warning('getSirenStatusCounts: response is missing valid connection counts');
            return null;
        }

        $total = (int) $totalValue;
        $offline = (int) $offlineValue;
        if ($total < 0 || $offline < 0 || $offline > $total) {
            Log::warning('getSirenStatusCounts: response contains out-of-range connection counts', [
                'total' => $total,
                'offline' => $offline,
            ]);
            return null;
        }

        return [
            'total'   => $total,
            'offline' => $offline,
            'online'  => $total - $offline,
        ];
    }

    public function fetchApiData(Request $request): JsonResponse
    {
        $path = $request->query('path');
        if (!$path) {
            return response()->json(['error' => 'Missing path parameter'], 400);
        }

        $connectionName = $request->query('connection_name');

        $query = $this->getGlobalConnectionQuery();

        if ($connectionName) {
            $connection = $query->where('name', $connectionName)->first();
            if (!$connection) {
                return response()->json(['error' => "No active API connection found with name '{$connectionName}'"], 404);
            }
        } else {
            $connection = $query->orderByDesc('last_used_at')->first();
            if (!$connection) {
                return response()->json(['error' => 'No active API connection found'], 404);
            }
        }

        $fullUrl = rtrim($connection->endpoint_url, '/') . '/' . ltrim($path, '/');

        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Accept' => 'application/json',
                    $connection->header_name => $connection->header_value,
                ])
                ->timeout(12)
                ->get($fullUrl);

            if ($response->successful()) {
                $connection->touch('last_used_at');
                $this->autoPopulateFromPath($path, $response->json());
                return response()->json($response->json());
            }

            return response()->json([
                'error'  => 'API request failed',
                'status' => $response->status(),
                'body'   => $response->body(),
            ], 502);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Connection error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function autoPopulateFromPath(string $path, mixed $data): void
    {
        if ($path === '/api/Data/Sirens' && is_array($data)) {
            foreach ($data as $item) {
                Siren::updateOrCreate(
                    ['oid' => $item['oid'] ?? null],
                    [
                        'name'      => $item['name'] ?? null,
                        'location'  => $item['location'] ?? null,
                        'siren_id'  => $item['sirenId'] ?? $item['id'] ?? null,
                        'group'     => $item['group'] ?? null,
                        /* 'latitude'  => $item['latitude'] ?? null,
                        'longitude' => $item['longitude'] ?? null, */
                        'enabled'   => true,
                    ]
                );
            }
        }

        if ($path === '/api/Data/Signals' && is_array($data)) {
            foreach ($data as $item) {
                if (empty($item['oid'])) {
                    continue;
                }
                Signal::updateOrCreate(
                    ['oid' => $item['oid']],
                    [
                        'name'      => $item['name'] ?? null,
                        'signal_id' => $item['sigA'] ?? null,
                        'volume'    => $item['volA'] ?? null,
                    ]
                );
            }
        }
    }

    public function postApiData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path'            => 'required|string',
            'payload'         => 'required|array',
            'connection_name' => 'nullable|string|max:150', 
        ]);

        $user = Auth::user();
        $userId = $user ? $user->id : 'unknown';

        $query = $this->getGlobalConnectionQuery();

        if (!empty($validated['connection_name'])) {
            $connection = $query->where('name', $validated['connection_name'])->first();
            if (!$connection) {
                Log::error('API post failed – specified connection not found', [
                    'user_id'         => $userId,
                    'connection_name' => $validated['connection_name'],
                    'path'            => $validated['path'],
                ]);
                return response()->json(['error' => "No active API connection found with name '{$validated['connection_name']}'"], 404);
            }
        } else {
            $connection = $query->orderByDesc('last_used_at')->first();
            if (!$connection) {
                Log::error('API post failed – no active connection', [
                    'user_id' => $userId,
                    'path'    => $validated['path'],
                    'payload' => $validated['payload'],
                ]);
                return response()->json(['error' => 'No active API connection found'], 404);
            }
        }

        $fullUrl = rtrim($connection->endpoint_url, '/') . '/' . ltrim($validated['path'], '/');

        try {
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                    $connection->header_name => $connection->header_value,
                ])
                ->timeout(12)
                ->post($fullUrl, $validated['payload']);

            if ($response->successful()) {
                $connection->touch('last_used_at');
                return response()->json($response->json());
            }

            Log::error('API post – external request failed', [
                'user_id'       => $userId,
                'connection_id' => $connection->id,
                'url'           => $fullUrl,
                'status'        => $response->status(),
                'response_body' => $response->body(),
                'payload'       => $validated['payload'],
            ]);

            return response()->json([
                'error'  => 'API request failed',
                'status' => $response->status(),
                'body'   => $response->body(),
            ], 502);
        } catch (\Throwable $e) {
            Log::error('API post – connection error', [
                'user_id'       => $userId,
                'connection_id' => $connection->id,
                'url'           => $fullUrl,
                'error'         => $e->getMessage(),
                'payload'       => $validated['payload'],
            ]);

            return response()->json([
                'error' => 'Connection error: ' . $e->getMessage(),
            ], 500);
        }
    }



    /**
     * Fetch sirens from database for merging with API data
     */
    public function fetchSirensFromDB(Request $request): JsonResponse
    {
        try {
            $sirens = \App\Models\Siren::with('location')
                ->orderBy('name')
                ->get()
                ->map(function ($siren) {
                    return [
                        'id' => $siren->id,
                        'oid' => $siren->oid,
                        'name' => $siren->name,
                        'siren_id' => $siren->siren_id,
                        'group' => $siren->group,
                        'location_id' => $siren->location_id,
                        'location' => $siren->location ? [
                            'id' => $siren->location->id,
                            'location_name' => $siren->location->location_name
                        ] : null,
                        'latitude' => $siren->latitude,
                        'longitude' => $siren->longitude,
                        'enabled' => $siren->enabled,
                        'created_at' => $siren->created_at,
                        'updated_at' => $siren->updated_at,
                    ];
                });
            
            return response()->json($sirens);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sirens from database: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sirens from database'], 500);
        }
    }

}
