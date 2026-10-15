@auth
@include('layouts.header')
@include('layouts.topbar')
<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-300">
    <div class="pt-16 md:pt-20 px-4 sm:px-6 lg:px-8">
        <div class="py-6 max-w-3xl mx-auto">

            <div class="mb-6 flex items-center gap-4">
                <a href="javascript:void(0)" onclick="returnToController()" title="Back to Homepage"
                    class="inline-flex size-10 items-center justify-center rounded-full bg-slate-grey/10 hover:bg-slate-grey/20 dark:bg-blue-slate/30 dark:hover:bg-blue-slate/50 transition focus:outline-none focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800 shrink-0 text-prussian-blue dark:text-lavender-grey">
                    <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-prussian-blue dark:text-white">SysLink</h1>
                    <p class="mt-1 text-sm text-slate-grey dark:text-lavender-grey">Send beacon and siren status to Uplink Sentinel every minute</p>
                </div>
            </div>

            @if(session('success'))
                <div role="status" class="mb-4 rounded-lg px-4 py-3 text-sm bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div role="alert" class="mb-4 rounded-lg px-4 py-3 text-sm bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ session('error') }}</div>
            @endif

            <div class="mb-4 bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm ring-1 ring-slate-grey/20 dark:ring-blue-slate/30">
                <form method="POST" action="{{ route('settings.syslink.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="endpoint_url" class="block font-medium uppercase tracking-wider text-slate-grey dark:text-lavender-grey mb-1">Endpoint URL</label>
                        <input type="url" name="endpoint_url" id="endpoint_url" required
                            value="{{ old('endpoint_url', $connection?->endpoint_url) }}"
                            placeholder="http://sentinel-server:8090/api/syslink/surveillance-network/status"
                            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-slate-grey/30 dark:border-blue-slate/40 rounded-lg text-prussian-blue dark:text-lavender-grey">
                        @error('endpoint_url')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="token" class="block font-medium uppercase tracking-wider text-slate-grey dark:text-lavender-grey mb-1">Link token</label>
                        <input type="password" name="token" id="token" autocomplete="off" {{ $connection ? '' : 'required' }}
                            placeholder="{{ $connection ? 'Saved. Leave blank to keep it' : 'Token from the Sentinel link page' }}"
                            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-slate-grey/30 dark:border-blue-slate/40 rounded-lg text-prussian-blue dark:text-lavender-grey">
                        @error('token')<p class="mt-1 text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-munti-blue-0 hover:bg-regal-navy text-white font-medium">Save</button>
                </form>
            </div>

            @if($connection)
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow-sm ring-1 ring-slate-grey/20 dark:ring-blue-slate/30 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-prussian-blue dark:text-lavender-grey">
                    @if($lastResult)
                        Last send {{ $lastResult['at']->diffForHumans() }}:
                        <span class="{{ $lastResult['error'] ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">{{ $lastResult['error'] ?? 'OK' }}</span>
                    @else
                        Nothing sent yet.
                    @endif
                </p>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('settings.syslink.test') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-munti-blue-2 hover:bg-munti-blue-0 text-white font-medium">Send now</button>
                    </form>
                    <form method="POST" action="{{ route('settings.syslink.destroy') }}" onsubmit="return confirm('Stop sending status to Sentinel?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-lg bg-munti-red-0 hover:opacity-90 text-white font-medium">Disconnect</button>
                    </form>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@include('layouts.footer')
@else
<script>
    window.location = "{{ route('login') }}";
</script>
@endauth
