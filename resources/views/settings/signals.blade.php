@auth
@include('layouts.header')
@include('layouts.topbar')
@include('modals.addUser')

<!-- Pickr color picker (only this page uses it) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
<script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>

<script>
    window.existingSignals = <?= json_encode($signals) ?>;
</script>

<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-300 dark:text-gray-300">
    <div class="pt-16 md:pt-20 px-4 sm:px-6 lg:px-8">
        <div class="py-6 max-w-screen-2xl mx-auto">

            {{-- Remove the old session flash messages --}}
            {{-- @if(session('success')) ... @endif --}}
            {{-- @if(session('error')) ... @endif --}}

            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div class="flex items-center gap-4">
                    <a href="javascript:void(0)" onclick="returnToController()"
                        title="Back to Homepage"
                        class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-slate-grey/10 hover:bg-slate-grey/20 dark:bg-blue-slate/30 dark:hover:bg-blue-slate/50 text-prussian-blue dark:text-lavender-grey transition-colors focus:outline-none focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800 shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>

                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-prussian-blue dark:text-white">Signal Management</h1>
                        <p class="mt-1 text-sm text-slate-grey dark:text-lavender-grey">
                            View, add, edit and manage signals & beacon colors
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                <div class="lg:col-span-1">
                    <form method="POST" action="{{ route('settings.signals.store') }}" class="h-full" id="signalForm">
                        @csrf
                        <div class="bg-white dark:bg-gray-800 shadow-xl ring-1 ring-slate-grey/20 dark:ring-blue-slate/30 rounded-2xl overflow-hidden flex flex-col h-[calc(100vh-280px)] min-h-[530px]">

                            <div class="px-5 py-2 border-b border-slate-grey/20 dark:border-blue-slate/30 shrink-0 flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-semibold text-prussian-blue dark:text-white">
                                        Signal Settings
                                    </h2>
                                    <p class="text-slate-grey dark:text-lavender-grey mt-0.5">
                                        Add signal details, beacon colors, delay timing, and button color
                                    </p>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto p-5 md:p-6 space-y-3 custom-scroll">
                                <div class="grid grid-cols-4 gap-4 max-w-2xl mx-auto">
                                    <div>
                                        <label class="block text-xs text-prussian-blue dark:text-lavender-grey mb-1">Alarm Name</label>
                                        <input type="text" name="name" class="w-full px-3 py-1.5 text-sm border border-slate-grey/30 dark:border-blue-slate/40 rounded-md bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue placeholder-slate-grey dark:placeholder-lavender-grey/50">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-prussian-blue dark:text-lavender-grey mb-1">Signal ID</label>
                                        <input type="number" name="signal_id" class="w-full px-3 py-1.5 text-sm border border-slate-grey/30 dark:border-blue-slate/40 rounded-md bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-prussian-blue dark:text-lavender-grey mb-1">OID</label>
                                        <input type="text" name="oid" class="w-full px-3 py-1.5 text-sm border border-slate-grey/30 dark:border-blue-slate/40 rounded-md bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-prussian-blue dark:text-lavender-grey mb-1">Duration (seconds)</label>
                                        <input type="number" name="duration" value="0" min="0" step="1" class="w-full px-3 py-1.5 text-sm border border-slate-grey/30 dark:border-blue-slate/40 rounded-md bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue">
                                    </div>
                                </div>


                                <div class="border border-slate-grey/20 dark:border-blue-slate/30 rounded-lg overflow-hidden">
                                    <div class="bg-regal-navy dark:bg-prussian-blue text-white px-5 py-2 font-semibold text-base">
                                        Beacon Colors
                                    </div>
                                    <div class="p-5 md:p-6 grid md:grid-cols-2 gap-6">
                                        <div class="space-y-2 md:border-r md:border-slate-grey/20 dark:md:border-blue-slate/30 md:pr-6">
                                            <div class="flex items-center justify-between">
                                                <h3 class="font-semibold text-base text-prussian-blue dark:text-white">Color 1</h3>
                                                <div id="preview1" class="w-8 h-8 rounded-lg border-2 border-slate-grey/30 dark:border-blue-slate/40 bg-black shadow-inner cursor-pointer" title="Click to pick color"></div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                                <span class="text-xs text-slate-grey dark:text-lavender-grey mr-1">Presets:</span>
                                                <div id="presetColorsContainer1" class="flex flex-wrap items-center gap-2"></div>
                                            </div>

                                            <div class="space-y-2 text-sm">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-munti-red-0 dark:text-munti-red-0">R</span>
                                                    <input type="range" min="0" max="255" value="0" id="r1" class="flex-1 h-2 accent-munti-red-0">
                                                    <input type="number" id="rVal1" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-green-600 dark:text-green-400">G</span>
                                                    <input type="range" min="0" max="255" value="0" id="g1" class="flex-1 h-2 accent-green-500">
                                                    <input type="number" id="gVal1" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-blue-600 dark:text-blue-400">B</span>
                                                    <input type="range" min="0" max="255" value="0" id="b1" class="flex-1 h-2 accent-blue-500">
                                                    <input type="number" id="bVal1" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-slate-grey dark:text-lavender-grey">W</span>
                                                    <input type="range" min="0" max="255" value="0" id="w1" class="flex-1 h-2 accent-slate-grey dark:accent-lavender-grey">
                                                    <input type="number" id="wVal1" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-xs text-slate-grey dark:text-lavender-grey mb-0.5">Hex</label>
                                                    <input type="text" id="hex1" value="#000000" readonly class="w-24 px-2 py-0.5 bg-slate-grey/10 dark:bg-blue-slate/30 border border-slate-grey/20 dark:border-blue-slate/40 rounded font-mono text-xs text-center text-prussian-blue dark:text-lavender-grey" />
                                                    <input type="hidden" name="beacon_color_1" value="#000000" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-grey dark:text-lavender-grey mb-0.5">Delay (ms)</label>
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" name="delay_1" value="0" min="0" class="w-24 px-2 py-0.5 bg-slate-grey/10 dark:bg-blue-slate/30 border border-slate-grey/20 dark:border-blue-slate/40 rounded font-mono text-xs text-right [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey" />
                                                        <span class="text-xs text-slate-grey dark:text-lavender-grey">ms</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between">
                                                <h3 class="font-semibold text-base text-prussian-blue dark:text-white">Color 2</h3>
                                                <div id="preview2" class="w-8 h-8 rounded-lg border-2 border-slate-grey/30 dark:border-blue-slate/40 bg-black shadow-inner cursor-pointer" title="Click to pick color"></div>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-2 mt-2">
                                                <span class="text-xs text-slate-grey dark:text-lavender-grey mr-1">Presets:</span>
                                                <div id="presetColorsContainer2" class="flex flex-wrap items-center gap-2"></div>
                                            </div>

                                            <div class="space-y-2 text-sm">
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-munti-red-0 dark:text-munti-red-0">R</span>
                                                    <input type="range" min="0" max="255" value="0" id="r2" class="flex-1 h-2 accent-munti-red-0">
                                                    <input type="number" id="rVal2" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-green-600 dark:text-green-400">G</span>
                                                    <input type="range" min="0" max="255" value="0" id="g2" class="flex-1 h-2 accent-green-500">
                                                    <input type="number" id="gVal2" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-blue-600 dark:text-blue-400">B</span>
                                                    <input type="range" min="0" max="255" value="0" id="b2" class="flex-1 h-2 accent-blue-500">
                                                    <input type="number" id="bVal2" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <span class="font-medium w-10 text-slate-grey dark:text-lavender-grey">W</span>
                                                    <input type="range" min="0" max="255" value="0" id="w2" class="flex-1 h-2 accent-slate-grey dark:accent-lavender-grey">
                                                    <input type="number" id="wVal2" value="0" min="0" max="255" class="w-12 text-right font-mono text-xs bg-transparent border border-transparent hover:border-slate-grey/30 dark:hover:border-blue-slate/40 rounded px-1 py-0.5 outline-none [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey">
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-xs text-slate-grey dark:text-lavender-grey mb-0.5">Hex</label>
                                                    <input type="text" id="hex2" value="#000000" readonly class="w-24 px-2 py-0.5 bg-slate-grey/10 dark:bg-blue-slate/30 border border-slate-grey/20 dark:border-blue-slate/40 rounded font-mono text-xs text-center text-prussian-blue dark:text-lavender-grey" />
                                                    <input type="hidden" name="beacon_color_2" value="#000000" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs text-slate-grey dark:text-lavender-grey mb-0.5">Delay (ms)</label>
                                                    <div class="flex items-center gap-1">
                                                        <input type="number" name="delay_2" value="0" min="0" class="w-24 px-2 py-0.5 bg-slate-grey/10 dark:bg-blue-slate/30 border border-slate-grey/20 dark:border-blue-slate/40 rounded font-mono text-xs text-right [appearance:textfield] focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue text-prussian-blue dark:text-lavender-grey" />
                                                        <span class="text-xs text-slate-grey dark:text-lavender-grey">ms</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6">
                                    <div>
                                        <label class="block text-xs text-prussian-blue dark:text-lavender-grey mb-1">
                                            Button Color
                                        </label>

                                        <div class="flex items-center gap-3 mb-3">
                                            <div id="buttonColorPreview"
                                                class="w-10 h-10 rounded-lg border-2 border-slate-grey/30 dark:border-blue-slate/40 shadow-inner cursor-pointer transition-all hover:scale-105"
                                                style="background-color: #FF0000;">
                                            </div>
                                            <input type="hidden" name="button_color" id="button_color" value="#FF0000">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm text-slate-grey dark:text-lavender-grey">Hex:</span>
                                                <span id="buttonColorHex" class="font-mono text-sm font-medium text-prussian-blue dark:text-white">#FF0000</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-2">
                                            <span class="text-xs text-slate-grey dark:text-lavender-grey mr-1">Presets:</span>
                                            <div id="presetColorsContainer" class="flex flex-wrap items-center gap-2">
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <button type="button" id="openCustomColorPicker"
                                                class="text-xs text-slate-grey dark:text-lavender-grey hover:text-blue-slate dark:hover:text-blue-slate flex items-center gap-1 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linecap="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                                </svg>
                                                Choose custom color...
                                            </button>
                                        </div>
                                    </div>
                                    <div class="flex gap-4">
                                        <button type="submit" class="px-6 py-2.5 bg-munti-green-1 hover:bg-munti-green-1 text-white text-sm font-medium rounded-md shadow transition focus:ring-2 focus:ring-munti-green-1 focus:outline-none">
                                            SAVE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-xl ring-1 ring-slate-grey/20 dark:ring-blue-slate/30 rounded-2xl overflow-hidden sticky top-20 flex flex-col h-[calc(100vh-280px)] min-h-[530px]">

                    <div class="px-5 py-4 border-b border-slate-grey/20 dark:border-blue-slate/30 shrink-0 flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-prussian-blue dark:text-white">Signals</h2>
                        </div>
                        <button id="refreshBtn"
                            class="px-4 py-2 text-sm bg-slate-grey/10 hover:bg-slate-grey/20 dark:bg-blue-slate/30 dark:hover:bg-blue-slate/50 rounded-lg transition whitespace-nowrap text-prussian-blue dark:text-lavender-grey">
                            Refresh
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto overflow-x-auto custom-scroll" id="tableContainer">
                        <div id="statusMessage" class="text-center py-10 text-slate-grey dark:text-lavender-grey text-sm italic">
                            Loading latest signals...
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-slate-grey/20 dark:border-blue-slate/30 text-slate-grey dark:text-lavender-grey flex justify-between items-center shrink-0 text-xs">
                        <span id="lastFetched">Not loaded yet</span>
                        <a href="{{ route('settings.api') }}" class="text-slate-grey dark:text-lavender-grey hover:text-blue-slate dark:hover:text-blue-slate hover:underline whitespace-nowrap">
                            Change connection →
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // SweetAlert configuration
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });

                // Handle form submission with SweetAlert
                const signalForm = document.getElementById('signalForm');
                if (signalForm) {
                    signalForm.addEventListener('submit', async function(e) {
                        e.preventDefault();
                        
                        const submitBtn = this.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
                        
                        try {
                            const formData = new FormData(this);
                            
                            const response = await fetch(this.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                                }
                            });
                            
                            const data = await response.json();
                            
                            if (data.success) {
                                Toast.fire({
                                    icon: 'success',
                                    title: data.message || 'Signal saved successfully!'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                let errorMsg = data.message || 'Failed to save signal.';
                                if (data.errors) {
                                    errorMsg = Object.values(data.errors).flat().join('\n');
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: errorMsg
                                });
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Network error. Please try again.'
                            });
                        } finally {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });
                }
                

                const componentToHex = c => {
                    const hex = Math.round(c).toString(16);
                    return hex.length === 1 ? "0" + hex : hex;
                };
                const rgbToHex = (r, g, b) => "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
                const hexToRgb = hex => {
                    const shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
                    hex = hex.replace(shorthandRegex, (m, r, g, b) => r + r + g + g + b + b);
                    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                    return result ? {
                        r: parseInt(result[1], 16),
                        g: parseInt(result[2], 16),
                        b: parseInt(result[3], 16)
                    } : null;
                };

                const pickrInstances = {};
                const beaconPickrInstances = {};

                const initColorPicker = index => {
                    const r = document.getElementById(`r${index}`);
                    const g = document.getElementById(`g${index}`);
                    const b = document.getElementById(`b${index}`);
                    const w = document.getElementById(`w${index}`);
                    const rVal = document.getElementById(`rVal${index}`);
                    const gVal = document.getElementById(`gVal${index}`);
                    const bVal = document.getElementById(`bVal${index}`);
                    const wVal = document.getElementById(`wVal${index}`);
                    const preview = document.getElementById(`preview${index}`);
                    const hexEl = document.getElementById(`hex${index}`);
                    const hiddenInput = document.querySelector(`input[name="beacon_color_${index}"]`);

                    const update = () => {
                        let rv = +r.value,
                            gv = +g.value,
                            bv = +b.value,
                            wv = +w.value;
                        rVal.value = rv;
                        gVal.value = gv;
                        bVal.value = bv;
                        wVal.value = wv;
                        const factor = wv / 255;
                        const rm = Math.min(255, Math.round(rv + (255 - rv) * factor));
                        const gm = Math.min(255, Math.round(gv + (255 - gv) * factor));
                        const bm = Math.min(255, Math.round(bv + (255 - bv) * factor));
                        const hex = rgbToHex(rm, gm, bm);
                        preview.style.backgroundColor = hex;
                        hexEl.value = hex.toUpperCase();
                        if (hiddenInput) hiddenInput.value = hex.toUpperCase();
                    };

                    [r, g, b, w].forEach(el => el.addEventListener('input', update));
                    [rVal, gVal, bVal, wVal].forEach(el => {
                        el.addEventListener('input', () => {
                            const val = Math.max(0, Math.min(255, +el.value || 0));
                            el.value = val;
                            document.getElementById(el.id.replace('Val', '')).value = val;
                            update();
                        });
                    });
                    update();
                };

                initColorPicker(1);
                initColorPicker(2);

                const setBeaconColorFromHex = (index, hex) => {
                    const rgb = hexToRgb(hex);
                    if (!rgb) return;
                    const rSlider = document.getElementById(`r${index}`);
                    const gSlider = document.getElementById(`g${index}`);
                    const bSlider = document.getElementById(`b${index}`);
                    const wSlider = document.getElementById(`w${index}`);
                    const rNum = document.getElementById(`rVal${index}`);
                    const gNum = document.getElementById(`gVal${index}`);
                    const bNum = document.getElementById(`bVal${index}`);
                    const wNum = document.getElementById(`wVal${index}`);

                    rSlider.value = rgb.r;
                    gSlider.value = rgb.g;
                    bSlider.value = rgb.b;
                    wSlider.value = 0;
                    rNum.value = rgb.r;
                    gNum.value = rgb.g;
                    bNum.value = rgb.b;
                    wNum.value = 0;

                    rSlider.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    gSlider.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    bSlider.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    wSlider.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                };

                const resetBeaconColor = (index) => setBeaconColorFromHex(index, '#000000');

                window.setColorFromHex = setBeaconColorFromHex;
                window.resetColorPicker = resetBeaconColor;

                const initBeaconColorPicker = (index) => {
                    const preview = document.getElementById(`preview${index}`);
                    if (!preview) return;

                    const triggerId = `beacon-picker-trigger-${index}`;
                    let trigger = document.getElementById(triggerId);
                    if (!trigger) {
                        trigger = document.createElement('div');
                        trigger.id = triggerId;
                        trigger.style.display = 'none';
                        document.body.appendChild(trigger);
                    }

                    const swatches = ['#FF0000', '#FFA500', '#FFFf00', '#0000FF', '#00FF00', '#FFFFFF'];

                    const pickr = Pickr.create({
                        el: `#${triggerId}`,
                        theme: 'classic',
                        default: '#000000',
                        position: 'top-start',
                        adjustNumber: true,
                        lockOpacity: true,
                        swatches: swatches,
                        components: {
                            preview: true,
                            opacity: false,
                            hue: true,
                            interaction: {
                                hex: true,
                                rgba: false,
                                hsla: false,
                                hsva: false,
                                cmyk: false,
                                input: true,
                                clear: false,
                                save: true
                            }
                        },
                        strings: {
                            save: 'Apply'
                        }
                    });

                    beaconPickrInstances[index] = pickr;

                    pickr.on('show', () => {
                        const rect = preview.getBoundingClientRect();
                        const pickrEl = pickr.getRoot().app;
                        if (pickrEl) {
                            pickrEl.style.position = 'fixed';
                            pickrEl.style.zIndex = '9999';
                            const top = rect.top + window.scrollY - pickrEl.offsetHeight - 5;
                            const left = rect.left + window.scrollX;
                            if (top < 10) {
                                pickrEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                            } else {
                                pickrEl.style.top = `${top}px`;
                                pickrEl.style.left = `${left}px`;
                            }
                        }
                    });

                    preview.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        const r = parseInt(document.getElementById(`r${index}`).value) || 0;
                        const g = parseInt(document.getElementById(`g${index}`).value) || 0;
                        const b = parseInt(document.getElementById(`b${index}`).value) || 0;
                        const currentHex = rgbToHex(r, g, b);
                        pickr.setColor(currentHex);
                        pickr.show();
                    });

                    pickr.on('save', (color) => {
                        const hex = color.toHEXA().toString();
                        setBeaconColorFromHex(index, hex);
                        pickr.hide();
                    });

                    const presetContainer = document.getElementById(`presetColorsContainer${index}`);
                    if (presetContainer) {
                        presetContainer.innerHTML = '';
                        swatches.forEach(color => {
                            const circle = document.createElement('button');
                            circle.type = 'button';
                            circle.className = 'preset-color w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 shadow-sm hover:scale-110 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500';
                            circle.style.backgroundColor = color;
                            circle.dataset.color = color;
                            circle.title = color;
                            presetContainer.appendChild(circle);

                            circle.addEventListener('click', (e) => {
                                e.preventDefault();
                                setBeaconColorFromHex(index, color);
                            });
                        });
                    }
                };

                initBeaconColorPicker(1);
                initBeaconColorPicker(2);

                const initButtonColorPicker = () => {
                    const buttonPreview = document.getElementById('buttonColorPreview');
                    const buttonHiddenInput = document.getElementById('button_color');
                    const buttonHexSpan = document.getElementById('buttonColorHex');
                    const presetColorsContainer = document.getElementById('presetColorsContainer');
                    const customColorBtn = document.getElementById('openCustomColorPicker');

                    if (!buttonPreview) return;

                    const swatches = [
                        '#FF0000', '#FFA500', '#FFFf00', '#0000FF', '#00FF00', '#FFFFFF'
                    ];

                    if (pickrInstances.button) {
                        pickrInstances.button.destroyAndRemove();
                        delete pickrInstances.button;
                    }

                    if (presetColorsContainer) {
                        presetColorsContainer.innerHTML = '';

                        swatches.forEach(color => {
                            const circle = document.createElement('button');
                            circle.type = 'button';
                            circle.className = 'preset-color w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 shadow-sm hover:scale-110 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500';
                            circle.style.backgroundColor = color;
                            circle.dataset.color = color;
                            circle.title = color;
                            presetColorsContainer.appendChild(circle);
                        });
                    }

                    const presetColors = document.querySelectorAll('#presetColorsContainer .preset-color');

                    const buttonTrigger = document.createElement('div');
                    buttonTrigger.id = 'button-picker-trigger';
                    buttonTrigger.style.position = 'fixed';
                    buttonTrigger.style.width = '0';
                    buttonTrigger.style.height = '0';
                    buttonTrigger.style.opacity = '0';
                    buttonTrigger.style.pointerEvents = 'none';
                    buttonTrigger.style.zIndex = '-1';
                    document.body.appendChild(buttonTrigger);

                    const initialColor = buttonHiddenInput.value || '#FF0000';

                    let updatingFromPicker = false;

                    const buttonPickr = Pickr.create({
                        el: '#button-picker-trigger',
                        theme: 'classic',
                        default: initialColor,
                        position: 'top-start',
                        adjustNumber: true,
                        lockOpacity: true,
                        swatches: swatches,
                        components: {
                            preview: true,
                            opacity: false,
                            hue: true,
                            interaction: {
                                hex: true,
                                rgba: false,
                                hsla: false,
                                hsva: false,
                                cmyk: false,
                                input: true,
                                clear: false,
                                save: true
                            }
                        },
                        strings: {
                            save: 'Apply'
                        }
                    });

                    const updateColor = (hex) => {
                        buttonPreview.style.backgroundColor = hex;
                        buttonHiddenInput.value = hex.toUpperCase();
                        if (buttonHexSpan) buttonHexSpan.textContent = hex.toUpperCase();

                        presetColors.forEach(circle => {
                            const circleColor = circle.dataset.color;
                            if (circleColor.toUpperCase() === hex.toUpperCase()) {
                                circle.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
                            } else {
                                circle.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2', 'dark:ring-offset-gray-900');
                            }
                        });
                    };

                    buttonPickr.on('show', () => {
                        const rect = customColorBtn ? customColorBtn.getBoundingClientRect() : buttonPreview.getBoundingClientRect();
                        const pickrEl = buttonPickr.getRoot().app;
                        if (pickrEl) {
                            pickrEl.style.position = 'fixed';
                            pickrEl.style.zIndex = '9999';

                            const top = rect.top + window.scrollY - pickrEl.offsetHeight - 5;
                            const left = rect.left + window.scrollX;

                            if (top < 10) {
                                pickrEl.style.top = (rect.bottom + window.scrollY + 5) + 'px';
                            } else {
                                pickrEl.style.top = `${top}px`;
                                pickrEl.style.left = `${left}px`;
                            }
                        }
                    });

                    buttonPickr.on('change', (color) => {
                        if (updatingFromPicker) return;
                        const hex = color.toHEXA().toString();
                        updateColor(hex);
                    });

                    buttonPickr.on('save', (color) => {
                        updatingFromPicker = true;
                        const hex = color.toHEXA().toString();
                        updateColor(hex);
                        buttonPickr.hide();
                        setTimeout(() => {
                            updatingFromPicker = false;
                        }, 50);
                    });

                    presetColors.forEach(circle => {
                        circle.addEventListener('click', (e) => {
                            e.preventDefault();
                            const color = circle.dataset.color;
                            updatingFromPicker = true;
                            updateColor(color);
                            buttonPickr.setColor(color);
                            buttonPickr.hide();
                            updatingFromPicker = false;
                        });
                    });

                    if (customColorBtn) {
                        customColorBtn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            e.preventDefault();
                            const currentColor = buttonHiddenInput.value;
                            updatingFromPicker = true;
                            buttonPickr.setColor(currentColor);
                            updatingFromPicker = false;
                            buttonPickr.show();
                        });
                    }

                    buttonPreview.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        const currentColor = buttonHiddenInput.value;
                        updatingFromPicker = true;
                        buttonPickr.setColor(currentColor);
                        updatingFromPicker = false;
                        buttonPickr.show();
                    });

                    updateColor(initialColor);
                    pickrInstances.button = buttonPickr;
                };

                initButtonColorPicker();

                const signalMap = {};
                if (window.existingSignals && Array.isArray(window.existingSignals)) {
                    window.existingSignals.forEach(signal => {
                        signalMap[signal.oid] = signal;
                    });
                }

                const container = document.getElementById('tableContainer');
                const status = document.getElementById('statusMessage');
                const lastFetched = document.getElementById('lastFetched');
                const refreshBtn = document.getElementById('refreshBtn');

                const loadSignals = async (showLoading = true) => {
                    if (showLoading) {
                        status.innerHTML = 'Loading latest signals...';
                        container.innerHTML = '';
                    }

                    refreshBtn.disabled = true;
                    refreshBtn.textContent = 'Refreshing...';

                    try {
                        const res = await fetch('{{ route("settings.api.fetch") }}?path=/api/Data/Signals&connection_name=Siren', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();

                        if (data.error) {
                            status.innerHTML = `<span class="text-munti-red-0 dark:text-munti-red-0 font-medium">${data.error}</span>`;
                            return;
                        }
                        if (!Array.isArray(data) || data.length === 0) {
                            status.innerHTML = 'No signals received from API';
                            return;
                        }

                        // Create main container with flex column layout
                        const tableContainer = document.createElement('div');
                        tableContainer.className = 'flex flex-col h-full overflow-hidden';

                        // Create header table (fixed, non-scrollable)
                        const headerTable = document.createElement('table');
                        headerTable.className = 'w-full table-fixed bg-white dark:bg-gray-800';

                        const thead = document.createElement('thead');
                        thead.className = 'bg-slate-grey/5 dark:bg-blue-slate/30';
                        thead.innerHTML = `
                            <tr>
                                <th class="px-2 py-2 text-left font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">OID</th>
                                <th class="px-2 py-2 text-left font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Alarm Name</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Sig A</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Duration</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Color 1</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Delay 1 (s)</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Color 2</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Delay 2 (s)</th>
                                <th class="px-2 py-2 text-center font-medium text-slate-grey dark:text-lavender-grey uppercase tracking-wider">Button</th>
                            </tr>
                        `;
                        headerTable.appendChild(thead);

                        // Create scrollable body container
                        const bodyContainer = document.createElement('div');
                        bodyContainer.className = 'overflow-y-auto flex-1 custom-scroll';

                        // Create body table
                        const bodyTable = document.createElement('table');
                        bodyTable.className = 'w-full table-fixed';

                        const tbody = document.createElement('tbody');
                        tbody.className = 'divide-y divide-slate-grey/20 dark:divide-blue-slate/30 bg-white dark:bg-gray-800';

                        data.forEach(item => {
                            const existing = signalMap[item.oid] || null;
                            const row = document.createElement('tr');
                            row.className = 'hover:bg-slate-grey/5 dark:hover:bg-blue-slate/30 transition-colors cursor-pointer';
                            row.dataset.oid = item.oid || '';
                            row.dataset.name = item.name || '';

                            const colorSquare = hex => hex ? `<div style="width:20px; height:20px; background-color:${hex}; border-radius:4px; margin:0 auto; border:1px solid rgba(151,157,172,0.2);"></div>` : '<span class="text-slate-grey dark:text-lavender-grey/50">—</span>';
                            const delaySec = ms => ms ? (ms / 1000).toFixed(1) : '—';

                            row.innerHTML = `
                                <td class="px-2 py-2 font-mono text-prussian-blue dark:text-lavender-grey truncate" title="${item.oid || ''}">
                                    ${item.oid ? item.oid.substring(0,8)+'…' : '—'}
                                </td>
                                <td class="px-2 py-2 font-medium truncate ${ (item.name || '').toUpperCase() === 'ALL CLEAR' ? 'text-munti-green-1 dark:text-munti-green-1' : 'text-prussian-blue dark:text-white' }" title="${item.name || ''}">
                                    ${item.name || '—'}
                                </td>
                                <td class="px-2 py-2 text-center text-prussian-blue dark:text-lavender-grey">${item.sigA ?? '—'}</td>
                                <td class="px-2 py-2 text-center text-prussian-blue dark:text-lavender-grey">${existing?.duration ?? '—'}</td>
                                <td class="px-2 py-2 text-center">${existing?.beacon_color_1 ? colorSquare(existing.beacon_color_1) : '—'}</td>
                                <td class="px-2 py-2 text-center text-prussian-blue dark:text-lavender-grey">${existing?.delay_1 ? delaySec(existing.delay_1) : '—'}</td>
                                <td class="px-2 py-2 text-center">${existing?.beacon_color_2 ? colorSquare(existing.beacon_color_2) : '—'}</td>
                                <td class="px-2 py-2 text-center text-prussian-blue dark:text-lavender-grey">${existing?.delay_2 ? delaySec(existing.delay_2) : '—'}</td>
                                <td class="px-2 py-2 text-center">${existing?.button_color ? colorSquare(existing.button_color) : '—'}</td>
                            `;

                            row.addEventListener('click', () => {
                                const previouslySelected = document.querySelector('tr.selected-row');
                                if (previouslySelected) previouslySelected.classList.remove('selected-row');
                                row.classList.add('selected-row', 'bg-slate-grey/10', 'dark:bg-blue-slate/40');

                                const nameInput = document.querySelector('input[name="name"]');
                                const oidInput = document.querySelector('input[name="oid"]');
                                const signalIdInput = document.querySelector('input[name="signal_id"]');
                                const durationInput = document.querySelector('input[name="duration"]');
                                if (nameInput) nameInput.value = item.name || '';
                                if (oidInput) oidInput.value = item.oid || '';
                                if (signalIdInput) signalIdInput.value = item.sigA || '';
                                if (durationInput) durationInput.value = existing?.duration ?? 0;

                                if (existing) {
                                    if (existing.beacon_color_1) setColorFromHex(1, existing.beacon_color_1);
                                    if (existing.beacon_color_2) setColorFromHex(2, existing.beacon_color_2);
                                    document.querySelector('input[name="delay_1"]').value = existing.delay_1 || 0;
                                    document.querySelector('input[name="delay_2"]').value = existing.delay_2 || 0;
                                    const buttonColorInput = document.getElementById('button_color');
                                    if (existing.button_color) {
                                        updateButtonColor(existing.button_color);
                                    }
                                } else {
                                    resetColorPicker(1);
                                    resetColorPicker(2);
                                    document.querySelector('input[name="delay_1"]').value = '0';
                                    document.querySelector('input[name="delay_2"]').value = '0';
                                    document.getElementById('button_color').value = '';
                                }
                            });
                            tbody.appendChild(row);
                        });

                        bodyTable.appendChild(tbody);
                        bodyContainer.appendChild(bodyTable);

                        tableContainer.appendChild(headerTable);
                        tableContainer.appendChild(bodyContainer);

                        container.innerHTML = '';
                        container.appendChild(tableContainer);



                        const count = data.length;
                        const now = new Date().toLocaleString();
                        status.innerHTML = `<span class="text-green-600 dark:text-green-400 font-semibold">Loaded ${count} signal${count === 1 ? '' : 's'} (click a row to edit)</span>`;
                        lastFetched.textContent = `Last fetched: ${now}`;
                    } catch (err) {
                        status.innerHTML = `<span class="text-munti-red-0 dark:text-munti-red-0 font-medium">Failed to load: ${err.message}</span>`;
                    } finally {
                        refreshBtn.disabled = false;
                        refreshBtn.textContent = 'Refresh';
                    }
                };

                loadSignals();
                refreshBtn.addEventListener('click', () => loadSignals(false));
            });
        </script>

        @include('layouts.footer')
    </div>
</div>
@else
<script>
    window.location = "{{ route('login') }}";
</script>
@endauth