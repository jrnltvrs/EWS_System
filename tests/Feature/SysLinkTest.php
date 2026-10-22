<?php

namespace Tests\Feature;

use App\Models\ApiConnection;
use App\Models\Beacon;
use App\Models\Location;
use App\Models\User;
use App\Services\SysLinkNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SysLinkTest extends TestCase
{
    use RefreshDatabase;

    private const URL = 'http://sentinel.test:8090/api/syslink/ews/status';

    private function admin(string $level = 'admin'): User
    {
        return User::forceCreate([
            'username' => $level, 'first_name' => 'A', 'last_name' => 'B',
            'password' => bcrypt('x'), 'user_level' => $level, 'first_login' => false,
        ]);
    }

    public function test_push_sends_beacons_with_bearer_token(): void
    {
        $user = $this->admin();
        ApiConnection::create(['name' => 'SysLink', 'endpoint_url' => self::URL, 'header_name' => 'Authorization', 'header_value' => 'tok123', 'user_id' => $user->id]);
        $loc = Location::create(['location_name' => 'City Hall']);
        Beacon::create(['name' => 'B1', 'beacon_id' => '7', 'status' => true, 'location_id' => $loc->id]);
        Beacon::create(['name' => 'B2', 'beacon_id' => '8', 'status' => false, 'is_door_open' => true, 'location_id' => $loc->id]);

        Http::fake([
            'sentinel.test*' => Http::response([], 202),
            '*' => Http::response([], 500), // siren API unreachable
        ]);

        $this->assertNull(app(SysLinkNotifier::class)->push());

        Http::assertSent(function (Request $r) {
            if ($r->url() !== self::URL) {
                return false;
            }
            $units = collect($r['units'])->keyBy('id');
            return $r->hasHeader('Authorization', 'Bearer tok123')
                && isset($r['reported_at'])
                && $r['overall']['message'] === '1/2 beacons offline; siren API unreachable'
                && $r['overall']['status'] === 'warning' // 50% offline is Critical
                && $units['BCN-07']['status'] === 'online'
                && ! array_key_exists('message', $units['BCN-07'])
                && $units['BCN-08'] == [
                    'id' => 'BCN-08', 'station' => 'B2 · City Hall', 'type' => 'Beacon',
                    'status' => 'offline', 'message' => 'Door open',
                ];
        });
        $this->assertNull(SysLinkNotifier::lastResult()['error']);
    }

    public function test_beacon_changes_push_once_after_response(): void
    {
        $user = $this->admin();
        ApiConnection::create(['name' => 'SysLink', 'endpoint_url' => self::URL, 'header_name' => 'Authorization', 'header_value' => 'tok123', 'user_id' => $user->id]);
        $loc = Location::create(['location_name' => 'City Hall']);
        $a = Beacon::create(['name' => 'B1', 'beacon_id' => '7', 'status' => true, 'location_id' => $loc->id]);
        $b = Beacon::create(['name' => 'B2', 'beacon_id' => '8', 'status' => true, 'location_id' => $loc->id]);
        Http::fake(['sentinel.test*' => Http::response([], 202), '*' => Http::response([], 500)]);

        $a->update(['status' => false]);
        $b->update(['is_door_open' => true]);
        $a->update(['name' => 'Renamed']); // not a status change
        $sentinelCalls = fn () => collect(Http::recorded())->filter(fn ($pair) => $pair[0]->url() === self::URL);
        $this->assertCount(0, $sentinelCalls()); // nothing until the response is done

        $this->app->terminate();

        $sentinelCalls = $sentinelCalls();
        $this->assertCount(1, $sentinelCalls);
        $units = collect($sentinelCalls->first()[0]['units'])->keyBy('id');
        $this->assertSame('offline', $units['BCN-07']['status']);
        $this->assertSame('Door open', $units['BCN-08']['message']);
    }

    public function test_push_reports_sentinel_error(): void
    {
        $user = $this->admin();
        ApiConnection::create(['name' => 'SysLink', 'endpoint_url' => self::URL, 'header_name' => 'Authorization', 'header_value' => 'bad', 'user_id' => $user->id]);
        Http::fake(['sentinel.test*' => Http::response([], 401), '*' => Http::response([], 500)]);

        $this->assertSame('Sentinel returned HTTP 401.', app(SysLinkNotifier::class)->push());
    }

    public function test_settings_page_saves_connection_and_hides_it_from_api_list(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('settings.syslink.update'), ['endpoint_url' => self::URL, 'token' => 'tok123'])
            ->assertRedirect(route('settings.syslink'));

        $conn = SysLinkNotifier::connection();
        $this->assertSame('tok123', $conn->header_value);

        // Blank token keeps the saved one.
        $this->actingAs($user)->post(route('settings.syslink.update'), ['endpoint_url' => self::URL, 'token' => '']);
        $this->assertSame('tok123', $conn->fresh()->header_value);

        $this->actingAs($user)->getJson(route('settings.api.connections'))->assertJsonCount(0);
    }

    public function test_regular_user_is_forbidden(): void
    {
        $this->actingAs($this->admin('user'))->get(route('settings.syslink'))->assertForbidden();
    }
}
