@include('layouts.header')
@include('layouts.topbar')
@include('modals.addUser')

<div class="min-h-screen bg-gray-50 dark:bg-gray-950 text-gray-300">
    <div class="pt-16 md:pt-20 px-4 sm:px-6 lg:px-8">
        <div class="py-6 max-w-screen-2xl mx-auto">

            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div class="flex items-center gap-4">
                    <a href="javascript:void(0)" onclick="returnToController()" title="Back to Homepage"
                        class="inline-flex size-10 items-center justify-center rounded-full bg-slate-grey/10 hover:bg-slate-grey/20 dark:bg-blue-slate/30 dark:hover:bg-blue-slate/50 transition focus:outline-none focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:ring-offset-2 dark:focus:ring-offset-gray-800 shrink-0 text-prussian-blue dark:text-lavender-grey">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-prussian-blue dark:text-white">Serial Port Configuration</h1>
                        <p class="mt-1 text-sm opacity-80 text-slate-grey dark:text-lavender-grey">Configure and monitor your serial connection settings</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">

                <!-- Serial Monitor Form -->
                <div class="bg-white dark:bg-gray-800 shadow-xl ring-1 ring-slate-grey/20 dark:ring-blue-slate/30 rounded-2xl overflow-hidden flex flex-col h-[calc(100vh-280px)] min-h-[530px]">
                    <div class="flex-1 overflow-y-auto p-5 md:p-6 space-y-6 custom-scroll" id="settingsContainer">
                        <form id="serialForm" class="space-y-6">
                            <!-- Status indicator -->
                            <div id="status" class="text-center font-medium py-3 rounded-md bg-slate-grey/10 dark:bg-blue-slate/30 text-prussian-blue dark:text-lavender-grey">
                                Status: Disconnected
                            </div>

                            <!-- Serial Port -->
                            <div class="space-y-2">
                                <label for="port" class="block text-xs text-prussian-blue dark:text-lavender-grey">Serial Port</label>
                                <select id="port" name="port" required
                                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                    <option value="" disabled selected>Loading ports...</option>
                                </select>
                            </div>

                            <!-- Baud Rate -->
                            <div class="space-y-2">
                                <label for="baud" class="block text-xs text-prussian-blue dark:text-lavender-grey">Baud Rate</label>
                                <select id="baud" name="baud" required
                                    class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                    <option value="4800">4800</option>
                                    <option value="9600" selected>9600</option>
                                    <option value="19200">19200</option>
                                    <option value="38400">38400</option>
                                    <option value="57600">57600</option>
                                    <option value="115200">115200</option>
                                    <option value="230400">230400</option>
                                    <option value="460800">460800</option>
                                    <option value="921600">921600</option>
                                </select>
                            </div>

                            <!-- Data Bits / Stop Bits -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="data_bits" class="block text-xs text-prussian-blue dark:text-lavender-grey">Data Bits</label>
                                    <select id="data_bits" name="data_bits" required
                                        class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                        <option value="7">7</option>
                                        <option value="8" selected>8</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="stop_bits" class="block text-xs text-prussian-blue dark:text-lavender-grey">Stop Bits</label>
                                    <select id="stop_bits" name="stop_bits" required
                                        class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                        <option value="1" selected>1</option>
                                        <option value="1.5">1.5</option>
                                        <option value="2">2</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Parity / Flow Control -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="parity" class="block text-xs text-prussian-blue dark:text-lavender-grey">Parity</label>
                                    <select id="parity" name="parity" required
                                        class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                        <option value="none" selected>None</option>
                                        <option value="odd">Odd</option>
                                        <option value="even">Even</option>
                                        <option value="mark">Mark</option>
                                        <option value="space">Space</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="flow_control" class="block text-xs text-prussian-blue dark:text-lavender-grey">Flow Control</label>
                                    <select id="flow_control" name="flow_control" required
                                        class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 text-prussian-blue dark:text-lavender-grey border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 appearance-none cursor-pointer hover:border-regal-navy dark:hover:border-prussian-blue pr-8">
                                        <option value="none" selected>None</option>
                                        <option value="rtscts">Hardware (RTS/CTS)</option>
                                        <option value="xonxoff">Software (XON/XOFF)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Transmit Delays -->
                            <div class="pt-6 border-t border-slate-grey/20 dark:border-blue-slate/30 space-y-4">
                                <h3 class="text-sm font-medium text-prussian-blue dark:text-white">Transmit Delays (ms)</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="delay_char" class="block text-xs text-slate-grey dark:text-lavender-grey">After each character</label>
                                        <input type="number" id="delay_char" name="delay_char" min="0" value="0"
                                            class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 text-prussian-blue dark:text-lavender-grey" />
                                    </div>
                                    <div class="space-y-2">
                                        <label for="delay_line" class="block text-xs text-slate-grey dark:text-lavender-grey">After each line</label>
                                        <input type="number" id="delay_line" name="delay_line" min="0" value="0"
                                            class="w-full px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none transition-all duration-200 text-prussian-blue dark:text-lavender-grey" />
                                    </div>
                                </div>
                            </div>

                            <!-- Connect / Disconnect buttons -->
                            <div class="mt-8 flex flex-col sm:flex-row gap-4">
                                <button type="button" id="btnConnect"
                                    class="flex-1 px-6 py-2.5 bg-munti-green-1 hover:bg-munti-green-1/90 text-white text-sm font-medium rounded-md shadow transition focus:ring-2 focus:ring-munti-green-1 focus:outline-none disabled:opacity-50">
                                    Connect
                                </button>
                                <button type="button" id="btnDisconnect" disabled
                                    class="flex-1 px-6 py-2.5 bg-munti-red-0 hover:bg-munti-red-0 text-white text-sm font-medium rounded-md shadow transition focus:ring-2 focus:ring-munti-red-0 focus:outline-none disabled:opacity-50">
                                    Disconnect
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Serial Log Panel -->
                <div class="bg-white dark:bg-gray-800 shadow-xl ring-1 ring-slate-grey/20 dark:ring-blue-slate/30 rounded-2xl overflow-hidden flex flex-col lg:sticky lg:top-20 h-[calc(100vh-280px)] min-h-[530px]">
                    <!-- Header: simplified to border-bottom only -->
                    <div class="flex items-center justify-between border-b border-slate-grey/20 dark:border-blue-slate/30 px-5 py-2">
                        <span class="text-sm font-semibold text-prussian-blue dark:text-white">Serial Log</span>
                        <div class="flex items-center gap-4">
                            <span id="liveIndicator" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full text-slate-grey dark:text-lavender-grey">
                                <span class="relative flex size-2.5">
                                    <span class="absolute size-full rounded-full bg-slate-grey/50 dark:bg-lavender-grey/50 opacity-75"></span>
                                    <span class="relative size-2.5 rounded-full bg-slate-grey dark:bg-lavender-grey"></span>
                                </span>
                                Offline
                            </span>
                        </div>
                    </div>

                    <!-- Terminal log area -->
                    <div id="logContainer" class="flex-1 bg-white dark:bg-black font-mono text-gray-900 dark:text-gray-300 overflow-hidden">
                        <div id="terminal" class="h-full p-4 overflow-y-auto whitespace-pre-wrap break-words leading-tight tracking-tight custom-scroll"></div>
                    </div>

                    <!-- Send form -->
                    <div class="p-5 border-t border-slate-grey/20 dark:border-blue-slate/30">
                        <form id="sendForm" class="flex items-center gap-2">
                            <input id="command" type="text" placeholder="Type command and press Send or Enter"
                                class="flex-1 px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-slate-grey/30 dark:border-blue-slate/40 rounded-md focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:border-regal-navy dark:focus:border-prussian-blue outline-none text-prussian-blue dark:text-lavender-grey placeholder-slate-grey dark:placeholder-lavender-grey/50" />
                            <button type="button" id="btnSend" disabled
                                class="px-6 py-2.5 bg-regal-navy dark:bg-prussian-blue hover:bg-blue-slate dark:hover:bg-blue-slate text-white text-sm font-medium rounded-md shadow transition focus:ring-2 focus:ring-regal-navy dark:focus:ring-prussian-blue focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.footer')


<!-- Loaded synchronously: the script below opens the socket while the page is parsing. -->
<script src="{{ config('services.serial.url') }}/socket.io/socket.io.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.io !== 'function') {
            console.warn('Serial backend unavailable. Start it and reload this page.');
            return;
        }
        const form = document.getElementById('serialForm');
        if (!form) return;

        const summaryEls = {
            port: document.getElementById('summary-port'),
            baud: document.getElementById('summary-baud'),
            data: document.getElementById('summary-data'),
            stop: document.getElementById('summary-stop'),
            parity: document.getElementById('summary-parity'),
            flow: document.getElementById('summary-flow'),
            char: document.getElementById('summary-char'),
            line: document.getElementById('summary-line'),
            updated: document.getElementById('summary-updated')
        };

        const getSelectText = el => el?.options?.[el.selectedIndex]?.text?.trim() || el?.value || '-- not selected --';

        const refreshSummary = () => {
            summaryEls.updated.textContent = new Date().toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit'
            });

            summaryEls.port.textContent = getSelectText(form.port);
            summaryEls.baud.textContent = form.baud?.value || '--';
            summaryEls.data.textContent = getSelectText(form.data_bits);
            summaryEls.stop.textContent = getSelectText(form.stop_bits);
            summaryEls.parity.textContent = getSelectText(form.parity);
            summaryEls.flow.textContent = getSelectText(form.flow_control);
            summaryEls.char.textContent = form.delay_char?.value || '0';
            summaryEls.line.textContent = form.delay_line?.value || '0';
        };

        form.addEventListener('change', refreshSummary);
        form.addEventListener('input', refreshSummary);
        form.addEventListener('paste', refreshSummary);
        refreshSummary();

        loadPorts();
    });

    const escapeHtml = unsafe => unsafe.replace(/[&<>"']/g, m => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    })[m]);




// ============================================
// DEDUPLICATION SYSTEM - Shared across all tabs
// ============================================

// Generate a unique ID for this tab/window
if (!window.tabId) {
    window.tabId = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Track recently logged messages to prevent duplicates
const loggedHashes = new Set();
const DEDUP_WINDOW_MS = 2000;

function getMessageHash(message, type) {
    const shortMsg = message.length > 200 ? message.substring(0, 200) : message;
    return `${type}:${shortMsg}`;
}

function isDuplicateLog(message, type) {
    const hash = getMessageHash(message, type);
    const now = Date.now();
    const recentLogs = JSON.parse(localStorage.getItem('serial_recent_logs') || '{}');
    
    Object.keys(recentLogs).forEach(key => {
        if (now - recentLogs[key] > DEDUP_WINDOW_MS) {
            delete recentLogs[key];
        }
    });
    
    if (loggedHashes.has(hash) || recentLogs[hash]) {
        return true;
    }
    
    loggedHashes.add(hash);
    recentLogs[hash] = now;
    localStorage.setItem('serial_recent_logs', JSON.stringify(recentLogs));
    
    setTimeout(() => {
        loggedHashes.delete(hash);
    }, DEDUP_WINDOW_MS);
    
    return false;
}

    // ============================================
    // SHARED SOCKET CONNECTION - SINGLETON PATTERN
    // ============================================
    
    // Check if a shared socket already exists (from controller page or another tab)
    if (!window.sharedSerialSocket) {
        window.sharedSerialSocket = {
            socket: null,
            connected: false,
            listeners: [],
            init: function() {
                if (this.socket && this.socket.connected) {
                    return this.socket;
                }
                
                this.socket = io(window.serialBackendUrl, {
                    reconnection: true,
                    reconnectionAttempts: 5,
                    reconnectionDelay: 1000
                });
                
                // Store callbacks to be called when socket is ready
                this.socket.on('connect', () => {
                    console.log('[Shared Socket] Connected to serial server');
                    this.connected = true;
                    this.listeners.forEach(cb => cb('connect', null));
                });
                
                this.socket.on('disconnect', () => {
                    console.log('[Shared Socket] Disconnected from serial server');
                    this.connected = false;
                    this.listeners.forEach(cb => cb('disconnect', null));
                });
                
                return this.socket;
            },
            on: function(event, callback) {
                if (this.socket) {
                    this.socket.on(event, callback);
                } else {
                    // Store callback for when socket is initialized
                    this.listeners.push((type, data) => {
                        if (type === 'connect' && event === 'connect') callback();
                        if (type === 'disconnect' && event === 'disconnect') callback();
                    });
                }
            },
            emit: function(event, data) {
                if (this.socket && this.socket.connected) {
                    this.socket.emit(event, data);
                }
            },
            off: function(event, callback) {
                if (this.socket) {
                    this.socket.off(event, callback);
                }
            }
        };
    }
    
    // Get or create the shared socket
    const sharedSocket = window.sharedSerialSocket;
    const socket = sharedSocket.init();

    // ---------- Helper: format date in Asia/Manila (YYYY-MM-DD HH:MM:SS AM/PM) ----------
    function formatManilaDate(datetime) {
        const date = new Date(datetime);
        const formatter = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        const parts = formatter.formatToParts(date);
        const year = parts.find(p => p.type === 'year').value;
        const month = parts.find(p => p.type === 'month').value;
        const day = parts.find(p => p.type === 'day').value;
        const hour = parts.find(p => p.type === 'hour').value;
        const minute = parts.find(p => p.type === 'minute').value;
        const second = parts.find(p => p.type === 'second').value;
        const ampm = parts.find(p => p.type === 'dayPeriod').value;
        return `${year}-${month}-${day} ${hour}:${minute}:${second} ${ampm}`;
    }

    // ---------- Load historical logs (with full Manila date) ----------
    async function loadHistoricalLogs() {
        try {
            const response = await fetch('/logs');
            const logs = await response.json();

            logs.forEach(log => {
                const time = formatManilaDate(log.datetime);
                const styles = {
                    success: {
                        prefix: '[SUCCESS]',
                        class: 'text-munti-green-1'
                    },
                    error: {
                        prefix: '[ERROR]',
                        class: 'text-munti-red-0 font-bold'
                    },
                    data: {
                        prefix: '[DATA]',
                        class: 'text-munti-green-1'
                    },
                    info: {
                        prefix: '[INFO]',
                        class: 'text-munti-yellow-0'
                    }
                };
                const {
                    prefix = '[INFO]', class: color = 'text-munti-yellow-0'
                } = styles[log.type] || {};

                const escapedMessage = escapeHtml(log.message);

                els.terminal.innerHTML += `<div class="mb-1"><span class="${color}">${prefix}</span> <span class="text-gray-500 text-xs">(${time})</span> ${escapedMessage}</div>`;
            });

            els.terminal.scrollTop = els.terminal.scrollHeight;
        } catch (err) {
            console.error('Failed to load historical logs:', err);
        }
    }

    function toggleFields(connected) {
        const fieldIds = [
            'port', 'baud', 'data_bits', 'stop_bits',
            'parity', 'flow_control', 'delay_char', 'delay_line'
        ];
        fieldIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = connected;
        });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const logUrl = "{{ route('logs.store') }}";

    const els = {
        port: document.getElementById('port'),
        baud: document.getElementById('baud'),
        connect: document.getElementById('btnConnect'),
        disconnect: document.getElementById('btnDisconnect'),
        send: document.getElementById('btnSend'),
        command: document.getElementById('command'),
        status: document.getElementById('status'),
        terminal: document.getElementById('terminal')
    };
    
    loadHistoricalLogs();

    function log(type, text) {
        const time = formatManilaDate(new Date());
        const styles = {
            success: { prefix: '[SUCCESS]', class: 'text-munti-green-1' },
            error: { prefix: '[ERROR]', class: 'text-munti-red-0 font-bold' },
            data: { prefix: '[DATA]', class: 'text-munti-green-1' },
            info: { prefix: '[INFO]', class: 'text-munti-yellow-0' }
        };
        const { prefix = '[INFO]', class: color = 'text-munti-yellow-0' } = styles[type] || {};

        const escapedText = escapeHtml(text);

        els.terminal.innerHTML += `<div class="mb-1"><span class="${color}">${prefix}</span> <span class="text-gray-500 text-xs">(${time})</span> ${escapedText}</div>`;
        els.terminal.scrollTop = els.terminal.scrollHeight;

        // Deduplicated database save
        if (csrfToken && logUrl && text && text.trim() !== '') {
            if (!isDuplicateLog(text, type)) {
                fetch(logUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ message: text, type, tab_id: window.tabId })
                }).catch(err => console.error('Log storage failed:', err));
            }
        }
    }

    // ---------- Load available serial ports ----------
    async function loadPorts() {
        try {
            const res = await fetch(window.serialBackendUrl + '/api/ports', {
                headers: { 'X-Serial-Token': window.serialToken }
            });
            const {
                success,
                ports,
                error
            } = await res.json();

            if (!success || !ports?.length) {
                els.port.innerHTML = '<option>No ports found</option>';
                log('error', 'No serial ports detected');
                return;
            }

            els.port.innerHTML = '<option value="" disabled selected>Choose a port...</option>';
            ports.forEach(p => {
                const opt = new Option(p.friendlyName, p.path);
                els.port.appendChild(opt);
            });

            log('info', `Loaded ${ports.length} ports`);
        } catch (err) {
            els.port.innerHTML = '<option>Error loading ports</option>';
            log('error', `Failed to load ports: ${err.message}`);
        }
    }

    loadPorts();

    // ============================================
    // USE SHARED SOCKET EVENT HANDLERS
    // ============================================
    
    // Track if we've already registered listeners for this page
    if (!window.portSetupListenersRegistered) {
        window.portSetupListenersRegistered = true;
        
        sharedSocket.on('connect', () => {
            log('success', 'Connected to local serial server');
        });

        sharedSocket.on('status', info => {
            const isConnected = info.connected;

            const statusEl = document.getElementById('status');
            if (statusEl) {
                statusEl.textContent = `Status: ${isConnected ? 'Connected' : 'Disconnected'}${info.path ? ` (${info.path})` : ''}`;

                if (isConnected) {
                    statusEl.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
                    statusEl.classList.add('bg-munti-green-0', 'dark:bg-munti-green-1/30', 'text-munti-green-1', 'dark:text-munti-green-1');
                } else {
                    statusEl.classList.remove('bg-munti-green-0', 'dark:bg-munti-green-1/30', 'text-munti-green-1', 'dark:text-munti-green-1');
                    statusEl.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-700', 'dark:text-gray-300');
                }
            }

            const indicator = document.getElementById('liveIndicator');
            if (indicator) {
                if (isConnected) {
                    indicator.innerHTML = `
                        <span class="relative flex size-2.5">
                            <span class="absolute size-full rounded-full bg-munti-green-1 opacity-75 animate-ping"></span>
                            <span class="relative size-2.5 rounded-full bg-green-500"></span>
                        </span>
                        Online
                    `;
                    indicator.classList.remove('text-gray-600', 'dark:text-gray-400');
                    indicator.classList.add('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
                } else {
                    indicator.innerHTML = `
                        <span class="relative flex size-2.5">
                            <span class="absolute size-full rounded-full bg-gray-400 opacity-75"></span>
                            <span class="relative size-2.5 rounded-full bg-gray-500"></span>
                        </span>
                        Offline
                    `;
                    indicator.classList.remove('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
                    indicator.classList.add('text-gray-600', 'dark:text-gray-400');
                }
            }

            if (els.connect) els.connect.disabled = isConnected;
            if (els.disconnect) els.disconnect.disabled = !isConnected;
            if (els.send) els.send.disabled = !isConnected;

            toggleFields(isConnected);
        });

        sharedSocket.on('data', line => log('data', line));
        sharedSocket.on('message', ({ type, text }) => log(type, text));
    }

    // ---------- Connect button ----------
    if (els.connect) {
        els.connect.addEventListener('click', async () => {
            const path = els.port.value;
            if (!path) {
                log('error', 'Please select a port first');
                return alert('Select a port');
            }

            const settings = {
                path: path,
                baudRate: Number(els.baud.value),
                dataBits: Number(document.getElementById('data_bits').value),
                stopBits: Number(document.getElementById('stop_bits').value),
                parity: document.getElementById('parity').value,
                flowControl: document.getElementById('flow_control').value,
                delayChar: Number(document.getElementById('delay_char').value) || 0,
                delayLine: Number(document.getElementById('delay_line').value) || 0
            };

            log('info', `Connecting to ${path} with settings:\n${JSON.stringify(settings, null, 2)}`);

            try {
                const res = await fetch(window.serialBackendUrl + '/api/connect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Serial-Token': window.serialToken
                    },
                    body: JSON.stringify(settings)
                });

                const { success, error } = await res.json();
                if (!success) {
                    log('error', `Connect failed: ${error || 'Unknown reason'}`);
                    alert(`Connect failed: ${error || 'Unknown'}`);
                }
            } catch (err) {
                log('error', `Connection error: ${err.message}`);
            }
        });
    }

    // ---------- Disconnect button ----------
    if (els.disconnect) {
        els.disconnect.addEventListener('click', async () => {
            log('info', 'Disconnecting...');
            try {
                const res = await fetch(window.serialBackendUrl + '/api/disconnect', {
                    method: 'POST',
                    headers: { 'X-Serial-Token': window.serialToken }
                });
                const { success, error } = await res.json();
                if (!success) log('error', `Disconnect failed: ${error || 'Unknown'}`);
            } catch (err) {
                log('error', `Disconnect error: ${err.message}`);
            }
        });
    }

    // ---------- Send button – now logs the full command ----------
    if (els.send) {
        els.send.addEventListener('click', async () => {
            const text = els.command.value.trim();
            if (!text) return;

            log('info', `Sending: ${text}`);

            try {
                const res = await fetch(window.serialBackendUrl + '/api/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Serial-Token': window.serialToken
                    },
                    body: JSON.stringify({ data: text })
                });

                const { success, error } = await res.json();
                if (success) {
                    els.command.value = '';
                } else {
                    log('error', `Send failed: ${error || 'Unknown'}`);
                    alert('Send failed');
                }
            } catch (err) {
                log('error', `Send error: ${err.message}`);
            }
        });
    }

    if (els.command) {
        els.command.addEventListener('keypress', e => {
            if (e.key === 'Enter' && els.send) els.send.click();
        });
    }
</script>
