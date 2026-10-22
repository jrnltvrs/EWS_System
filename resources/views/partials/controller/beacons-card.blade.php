<div class="card bg-white dark:bg-gray-900 backdrop-blur-md border border-slate-grey/20 dark:border-blue-slate/30 rounded-md shadow-sm dark:shadow-none flex flex-col overflow-hidden col-span-1 sm:col-span-2 lg:col-span-4">
    <div class="bg-munti-blue-0 dark:bg-munti-blue-0 text-white text-base font-medium px-3 py-2 md:px-4 md:py-2 text-center border-b border-munti-blue-1/20 dark:border-blue-slate/30 shrink-0 tracking-wide">BEACONS</div>
    <div class="flex-1 p-1 md:p-3 overflow-y-auto bg-gradient-to-b from-slate-grey/5 to-transparent dark:from-blue-slate/10 dark:to-transparent custom-scroll">
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 md:gap-2 md">
            @forelse ($beacons as $beacon)
            <?php
            $isOnline = $beacon->status;
            $displayGroup = $beacon->location?->location_name ?? $beacon->group ?? 'Unknown';
            ?>
            <button type="button" class="min-w-[40px] h-[90px] select-all-items beacon-item relative w-full p-1 text-center uppercase font-semibold rounded-md transition-all duration-150 ease-in-out flex flex-col {{ $isOnline ? 'online-item cursor-pointer border border-munti-white-0/30 bg-munti-blue-2 hover:bg-munti-blue-2/80 hover:shadow-[0_3px_0_0_#0e6bb7] hover:translate-y-[3px] text-munti-black-0 shadow-[0_6px_0_0_#0e6bb7]' : 'offline-item cursor-default border-gray-400/40 bg-gray-300 text-munti-black-0 shadow-[0_6px_0_0_#4b5563]' }}" data-group="{{ $displayGroup }}" data-beacon-id="{{ $beacon->beacon_id }}">
                <div class="flex-grow flex items-center justify-center">
                    {{ $beacon->name ?? 'Unnamed Beacon' }}
                </div>  
                <div class="flex justify-center gap-1 pb-2 pt-1">
                    <!-- Connection-signal icon: red when offline -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" class="w-4 h-4 {{ !$isOnline ? 'text-munti-red-0' : 'text-green-900' }}" fill="currentColor">
                        <title>Connection</title>
                        <path d="M15 12h2v18h-2zm-3.67 6.22a7 7 0 0 1 0-10.44l1.34 1.49a5 5 0 0 0 0 7.46zm9.34 0l-1.34-1.49a5 5 0 0 0 0-7.46l1.34-1.49a7 7 0 0 1 0 10.44" />    
                        <path d="M8.4 21.8a11 11 0 0 1 0-17.6l1.2 1.6a9 9 0 0 0 0 14.4zm15.2 0l-1.2-1.6a9 9 0 0 0 0-14.4l1.2-1.6a11 11 0 0 1 0 17.6" />
                    </svg>
                    <!-- Warning-alt-filled icon -->
                    {{-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" class="w-4 h-4" fill="currentColor">
                        <title>Battery Error</title>
                        <path fill="none" d="M16 26a1.5 1.5 0 1 1 1.5-1.5A1.5 1.5 0 0 1 16 26m-1.125-5h2.25v-9h-2.25Z" />
                        <path d="M16.002 6.171h-.004L4.648 27.997l.003.003h22.698l.002-.003ZM14.875 12h2.25v9h-2.25ZM16 26a1.5 1.5 0 1 1 1.5-1.5A1.5 1.5 0 0 1 16 26" />
                        <path d="M29 30H3a1 1 0 0 1-.887-1.461l13-25a1 1 0 0 1 1.774 0l13 25A1 1 0 0 1 29 30M4.65 28h22.7l.001-.003L16.002 6.17h-.004L4.648 27.997Z" />
                    </svg> --}}
                    <!-- Door-open icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                        class="w-4 h-4 door-icon {{ $isOnline && $beacon->is_door_open ? 'text-red-500' : 'text-munti-black-0' }}"
                        fill="currentColor">
                        <title>Door-open</title>
                        <path d="M5 5v14a1 1 0 0 0 1 1h3v-2H7V6h2V4H6a1 1 0 0 0-1 1m14.242-.97l-8-2A1 1 0 0 0 10 3v18a.998.998 0 0 0 1.242.97l8-2A1 1 0 0 0 20 19V5a1 1 0 0 0-.758-.97M15 12.188a1.001 1.001 0 0 1-2 0v-.377a1 1 0 1 1 2 .001z" />
                    </svg>
                </div>
                @if (!$isOnline)
                <span class="absolute top-1 right-1 text-[10px] font-bold text-gray-600"></span>
                @endif
            </button>
            @empty
            <div class="col-span-full text-center py-10 text-munti-black-0 text-sm italic">No beacons added yet</div>
            @endforelse
        </div>
    </div>
</div>
