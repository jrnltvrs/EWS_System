<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SetupController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\LocationController;
use App\Http\Controllers\Settings\BeaconController;
use App\Http\Controllers\Settings\SignalController;
use App\Http\Controllers\Settings\PortController;
use App\Http\Controllers\Settings\ApiController;
use App\Http\Controllers\Settings\SirenController;
use App\Http\Controllers\Settings\SystemLogController;
use App\Http\Controllers\Settings\StatisticController;
use App\Http\Controllers\Settings\SysLinkController;
use App\Http\Controllers\BarangayController;



Route::get('/', function () {
    // No credentials in the database yet -> send to initial setup
    // instead of showing a login form nobody can log into.
    if (! \App\Models\User::query()->exists()) {
        return redirect()->route('setup');
    }

    return view('index');
})->name('login');
Route::get('/login', fn() => redirect()->route('login'));

Route::post('/login',  [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// First-run setup: only reachable while the users table is empty
// (SetupController redirects to /login itself once an account exists).
Route::get('/setup',  [SetupController::class, 'showSetupForm'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');







// Protected routes
Route::middleware('auth')->group(function () {




    // Password change routes (must be accessible even if first_login is true)
    Route::get('/password/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'showChangeForm'])
        ->name('password.change');
    Route::post('/password/change', [App\Http\Controllers\Auth\PasswordChangeController::class, 'update'])
        ->name('password.update');

    Route::middleware('password.changed')->group(function () {



        // routes/web.php (or api.php)
        Route::get('/barangays/geojson', [App\Http\Controllers\BarangayController::class, 'getGeoJson']);

        Route::get('/api/telegram/status', function () {
            $connected = app(\App\Services\TelegramNotifier::class)->checkConnection();
            return response()->json(['connected' => $connected]);
        })->name('api.telegram.status');

        Route::get('/controller', function () {
            $locations = \App\Models\Location::orderBy('location_name')->get();

            $beacons = \App\Models\Beacon::with('location')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('name')
                ->get();

            $beaconCounts = \App\Models\Beacon::getCounts();

            /* $signals = \App\Models\Signal::orderBy('description')->get(); */
            $signals = \App\Models\Signal::orderBy('description')->orderBy('name')->get();

            // Get sirens from database (this is working)
            $sirens = \App\Models\Siren::with('location')
                ->whereNotNull('oid')
                ->orderBy('name')
                ->get();

            return view('controller', compact('locations', 'beacons', 'beaconCounts', 'signals', 'sirens'));
        })->name('controller');




        Route::prefix('settings')->name('settings.')->group(function () {

        Route::get('/map', function () {
            $beacons = \App\Models\Beacon::with('location')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->orderBy('name')
                ->get();

            // ADD THIS - Fetch sirens from database with their coordinates
            $sirens = \App\Models\Siren::with('location')
                ->whereNotNull('latitude')      // Make sure they have coordinates
                ->whereNotNull('longitude')
                ->whereNotNull('oid')           // Need OID to match with API
                ->orderBy('name')
                ->get();

            // Debug: Log to Laravel log
            Log::info('Map page loading', [
                'beacons_count' => $beacons->count(),
                'sirens_count' => $sirens->count(),
                'sirens_with_coords' => $sirens->filter(function($s) {
                    return !is_null($s->latitude) && !is_null($s->longitude);
                })->count()
            ]);

            return view('settings.map', compact('beacons', 'sirens'));
        })->name('map');

            Route::get('/api',    [ApiController::class, 'index'])->name('api');
            Route::get('/api/connections',      [ApiController::class, 'getConnections'])->name('api.connections');
            Route::post('/api/auto-register',   [ApiController::class, 'autoRegister'])->name('api.auto-register');
            Route::get('/api/last-connection',  [ApiController::class, 'getLastConnection'])->name('api.last-connection');
            Route::post('/api/mark-used',       [ApiController::class, 'markAsUsed'])->name('api.mark-used');
            Route::post('/api/delete',          [ApiController::class, 'deleteConnection'])->name('api.delete');
            Route::get('/api/latest-signals', [ApiController::class, 'fetchLatestSignals'])
                ->middleware('auth')
                ->name('api.latest-signals');
            Route::get('/api/fetch', [ApiController::class, 'fetchApiData'])
                ->name('api.fetch')
                ->middleware('auth');
            Route::post('/api/post', [ApiController::class, 'postApiData'])->name('api.post');


            Route::get('/api/fetch-sirens', [ApiController::class, 'fetchSirensFromDB'])
                ->name('api.fetch-sirens')
                ->middleware('auth');


            Route::get('/statistics', [StatisticController::class, 'index'])->name('statistics');
            Route::get('/statistics/chart-data', [StatisticController::class, 'chartData'])->name('statistics.chart-data');







            Route::get('/signals',    [SignalController::class, 'index'])->name('signals');
            Route::post('/signals', [SignalController::class, 'store'])->name('signals.store');
            Route::get('/signals/fetch-by-oid', [SignalController::class, 'fetchByOid'])
                ->name('signals.fetch-by-oid');

            Route::put('/sirens/{oid}', [SirenController::class, 'update'])->name('sirens.update');

            Route::get('/portSetup',  [PortController::class,  'index'])->name('port');
            Route::get('/statistics',  [StatisticController::class,  'index'])->name('statistics');

            Route::get('/locations',  [LocationController::class, 'index'])->name('locations');
            Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
            Route::put('/locations/{id}', [LocationController::class, 'update'])->name('locations.update');

            Route::get('/beacons',  [LocationController::class, 'index'])->name('beacons');
            Route::post('/beacons',   [BeaconController::class, 'store'])->name('beacons.store');
            Route::put('/beacons/{id}', [BeaconController::class, 'update'])->name('beacons.update');
            Route::post('/beacons/online', [BeaconController::class, 'updateOnlineStatus'])->name('beacons.online');
            Route::post('/beacons/status', [BeaconController::class, 'updateStatus'])->name('beacons.status');
            Route::post('/beacons/door-status', [BeaconController::class, 'updateDoorStatus'])->name('beacons.door-status');
            Route::get('/beacons/list', [BeaconController::class, 'list'])->name('beacons.list');

            Route::get('/systemLogs', [SystemLogController::class, 'index'])->name('systemLogs');

            Route::get('/syslink',         [SysLinkController::class, 'index'])->name('syslink');
            Route::post('/syslink',        [SysLinkController::class, 'update'])->name('syslink.update');
            Route::post('/syslink/test',   [SysLinkController::class, 'test'])->name('syslink.test');
            Route::delete('/syslink',      [SysLinkController::class, 'destroy'])->name('syslink.destroy');


            Route::get('/users',      [UserController::class, 'index'])->name('users');
            Route::post('/users',     [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });


        Route::get('/logs', [App\Http\Controllers\LogController::class, 'index'])->name('logs.index');
        Route::post('/logs', [App\Http\Controllers\LogController::class, 'store'])->name('logs.store');
        Route::get('/settings/logs', [App\Http\Controllers\LogController::class, 'showLogsPage'])
            ->name('settings.logs');
        Route::post('/reports/system-status/download', [App\Http\Controllers\SystemStatusReportController::class, 'download'])
            ->name('reports.system-status.download');
        Route::post('/reports/system-status/telegram', [App\Http\Controllers\SystemStatusReportController::class, 'sendToTelegram'])
            ->name('reports.system-status.telegram');
    });
});
