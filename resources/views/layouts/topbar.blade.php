@include('layouts.header')

<div class="fixed top-0 left-0 right-0 z-50 bg-munti-blue-0 border-b border-slate-grey/20 dark:border-blue-slate/20 shadow-sm text-munti-blue-1 dark:text-munti-white-0">
    <div class="max-w-full mx-auto px-4 md:px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 pr-4 border-r border-slate-grey/30 dark:border-blue-slate/30">
                <img src="{{ asset('images/Munti-logo.png') }}" alt="Muntinlupa Logo" class="h-8 md:h-10 w-auto object-contain opacity-90 dark:opacity-100">
                <img src="{{ asset('images/DRRM-logo.png') }}" alt="DRRMO Logo" class="h-7 md:h-9 w-auto object-contain opacity-90 dark:opacity-100">
            </div>
            <div class="flex flex-col leading-tight">
                <h1 class="text-base font-bold tracking-tight text-munti-white-0 uppercase">
                    DDRM Emergency Warning System
                </h1>
                <p class="text-xs md:text-sm text-munti-white-0">
                    Muntinlupa City
                </p>
            </div>
        </div>

        <div class="flex items-center">
            @auth
            <div class="text-right mr-4 border-r border-slate-grey/30 dark:border-blue-slate/30 pr-4">
                <div class="text-sm font-medium text-munti-white-0">
                    {{ ucfirst(auth()->user()->user_level) }}, {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                </div>
                <div class="text-xs text-munti-white-0">
                    {{ auth()->user()->position ?? 'User' }}
                </div>
            </div>
            @endauth

            <div class="relative">
                <button id="settings-btn" type="button" class="p-2 px-0 rounded-full text-munti-white-0 hover:text-munti-blue-0 hover:text-munti-white-0 transition-colors duration-200" aria-label="Settings" aria-expanded="false" aria-haspopup="true" title="Settings">
                    <svg class="h-6 w-6 md:h-7 md:w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                <div id="settings-menu"
                    class="hidden absolute right-0 mt-2 w-56 max-h-[calc(100vh-5rem)] overflow-y-auto bg-munti-blue-0 rounded-lg shadow-lg py-1 z-50 border border-munti-blue-1/50">
                    <a href="{{ route('settings.port') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Serial Port Setup</a>
                    @if(in_array(Auth::user()->user_level ?? '', ['superadmin', 'admin']))
                    <a href="{{ route('settings.api') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">API</a>
                    <a href="{{ route('settings.signals') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Signals</a>
                    <a href="{{ route('settings.locations') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Locations & Devices</a>
                    @endif
                    <a href="{{ route('settings.map') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Beacon Map</a>
                    <a href="{{ route('settings.logs') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">IOT Logs</a>
                    <form method="POST" action="{{ route('reports.system-status.download') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Download System Status Report</button>
                    </form>
                    <hr class="my-1 border-munti-blue-1/30">
                    @if(in_array(Auth::user()->user_level ?? '', ['superadmin', 'admin']))
                    <a href="{{ route('settings.users') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">
                        Users
                    </a>
                    <a href="{{ route('settings.systemLogs') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">System Logs</a>
                    <a href="{{ route('settings.syslink') }}" target="settingsWindow" class="block px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">SysLink</a>
                    <form method="POST" action="{{ route('reports.system-status.telegram') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">Send Report to Telegram</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-xs text-white hover:bg-munti-blue-1/50 transition-colors">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('report_feedback'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: @json(str_contains(session('report_feedback'), 'sent to Telegram') ? 'success' : 'error'),
                title: @json(session('report_feedback')),
                showConfirmButton: false,
                timer: 4500
            });
        }
    });
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('settings-btn');
        const menu = document.getElementById('settings-menu');

        const toggle = () => {
            const willBeOpen = menu.classList.toggle('hidden');
            btn.setAttribute('aria-expanded', !willBeOpen);
        };

        btn.addEventListener('click', e => {
            e.stopPropagation();
            toggle();
        });

        document.addEventListener('click', e => {
            if (!btn.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                menu.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    });

    function returnToController() {
        if (window.opener && !window.opener.closed) {
            window.opener.focus();
            window.close();
        } else {
            window.location.href = '{{ route('controller') }}';
        }
    }
    
</script>
