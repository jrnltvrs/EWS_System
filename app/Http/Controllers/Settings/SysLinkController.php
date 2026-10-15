<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Services\SysLinkNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SysLinkController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(in_array(Auth::user()->user_level ?? '', ['superadmin', 'admin'], true), 403);
    }

    public function index(): View
    {
        $this->authorizeAdmin();

        return view('settings.syslink', [
            'connection' => SysLinkNotifier::connection(),
            'lastResult' => SysLinkNotifier::lastResult(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $connection = SysLinkNotifier::connection();

        $data = $request->validate([
            'endpoint_url' => 'required|url|max:255',
            // Sentinel shows the token only once; leave blank to keep the saved one.
            'token'        => [$connection ? 'nullable' : 'required', 'string', 'max:255'],
        ]);

        $values = ['endpoint_url' => $data['endpoint_url'], 'header_name' => 'Authorization'];
        if (! empty($data['token'])) {
            $values['header_value'] = $data['token'];
        }

        if ($connection) {
            $connection->update($values);
        } else {
            ApiConnection::create($values + ['name' => SysLinkNotifier::CONNECTION_NAME, 'user_id' => Auth::id()]);
        }

        return redirect()->route('settings.syslink')->with('success', 'SysLink settings saved.');
    }

    public function test(SysLinkNotifier $sysLink): RedirectResponse
    {
        $this->authorizeAdmin();

        $error = $sysLink->push();

        return redirect()->route('settings.syslink')
            ->with($error ? 'error' : 'success', $error ?? 'Status sent to Sentinel.');
    }

    public function destroy(): RedirectResponse
    {
        $this->authorizeAdmin();

        SysLinkNotifier::connection()?->update(['revoked' => true]);

        return redirect()->route('settings.syslink')->with('success', 'SysLink disconnected.');
    }
}
