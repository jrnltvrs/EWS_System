document.addEventListener('DOMContentLoaded', () => {
    console.log('[Debug] DOM Content Loaded');
    console.log('[Debug] CSRF Token available:', !!window.serialConfig?.csrfToken);
    console.log('[Debug] Log URL:', window.serialConfig?.logUrl);
    console.log('[Debug] Socket URL:', window.serialConfig?.socketUrl);


    const els = {
        terminal: document.getElementById('terminal'),
        command: document.getElementById('command'),
        sendBtn: document.getElementById('btnSend'),
        liveIndicator: document.getElementById('liveIndicator')
    };
    if (!els.terminal || !els.command || !els.sendBtn || !els.liveIndicator) {
        console.error('Serial Log: required DOM elements missing');
        return;
    }
    const csrfToken = window.serialConfig?.csrfToken;
    const logUrl = window.serialConfig?.logUrl;
    if (typeof window.io !== 'function') {
        els.liveIndicator.textContent = 'Serial backend unavailable';
        els.sendBtn.disabled = true;
        console.warn('Start the serial backend and reload this page to enable serial communication.');
        return;
    }
    const serialUrl = window.serialConfig?.socketUrl || window.serialBackendUrl;
    const socket = io(serialUrl);

    const escapeHtml = unsafe => unsafe.replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
    
    
    const STYLES = {
        success: { prefix: '[SUCCESS]', class: 'text-munti-green-1' },
        error: { prefix: '[ERROR]', class: 'text-munti-red-0' },
        data: { prefix: '[DATA]', class: 'text-munti-blue-0' },
        info: { prefix: '[INFO]', class: 'text-yellow-400' }
    };

    // Helper function to check if data is garbled/binary
    function isGarbledData(text) {
        if (!text || typeof text !== 'string') return true;
        const hasNonPrintable = /[^\x20-\x7E\r\n\t]/.test(text);
        if (!hasNonPrintable) return false;
        const nonPrintableCount = (text.match(/[^\x20-\x7E\r\n\t]/g) || []).length;
        const nonPrintableRatio = text.length > 0 ? nonPrintableCount / text.length : 0;
        if (nonPrintableRatio > 0.3) return true;
        const hasReadablePattern = /(LED-Check|Online|ERROR|SlaveReply|ID=|Service mode|ACK|id=\d+)/i.test(text);
        return !hasReadablePattern;
    }

    // Helper function to clean garbled data
    function cleanGarbledData(text) {
        if (!text || typeof text !== 'string') return '';
        let cleaned = text.replace(/[^\x20-\x7E\r\n\t]/g, '');
        return cleaned.trim();
    }


    // Generate a unique ID for this tab/window
    if (!window.tabId) {
        window.tabId = 'tab_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Track recently logged messages to prevent duplicates
    const loggedHashes = new Set();
    const DEDUP_WINDOW_MS = 2000; // 2 seconds deduplication window

    // Simple hash function for message deduplication
    function getMessageHash(message, type) {
        // Only hash the first 200 chars and type to avoid memory issues
        const shortMsg = message.length > 200 ? message.substring(0, 200) : message;
        return `${type}:${shortMsg}`;
    }

    // Check if this message was recently logged by any tab
    function isDuplicateLog(message, type) {
        const hash = getMessageHash(message, type);
        
        // Check localStorage for recent logs from other tabs
        const now = Date.now();
        const recentLogs = JSON.parse(localStorage.getItem('serial_recent_logs') || '{}');
        
        // Clean up old entries (older than DEDUP_WINDOW_MS)
        Object.keys(recentLogs).forEach(key => {
            if (now - recentLogs[key] > DEDUP_WINDOW_MS) {
                delete recentLogs[key];
            }
        });
        
        // Check if this hash exists (either in memory or localStorage)
        if (loggedHashes.has(hash) || recentLogs[hash]) {
            return true;
        }
        
        // Store this hash locally and in localStorage
        loggedHashes.add(hash);
        recentLogs[hash] = now;
        localStorage.setItem('serial_recent_logs', JSON.stringify(recentLogs));
        
        // Clean memory after dedup window
        setTimeout(() => {
            loggedHashes.delete(hash);
        }, DEDUP_WINDOW_MS);
        
        return false;
    }

    // Update the log function with deduplication
    const log = (type, text) => {
        let displayText = text;
        let shouldDisplay = true;
        
        if (type === 'data') {
            if (isGarbledData(text)) {
                shouldDisplay = false;
            } else {
                displayText = cleanGarbledData(text);
                if (displayText === '') shouldDisplay = false;
            }
        }
        
        if (shouldDisplay) {
            const time = formatLogDate(new Date());
            const { prefix = '[INFO]', class: color = 'text-yellow-400' } = STYLES[type] || {};
            els.terminal.innerHTML += `<div class="mb-1"><span class="${color}">${prefix}</span> <span class="text-gray-500">(${time})</span> ${escapeHtml(displayText)}</div>`;
            els.terminal.scrollTop = els.terminal.scrollHeight;
        }
        
        // ============================================
        // DEDUPLICATED DATABASE SAVE
        // Only save to database if this isn't a duplicate from another tab
        // ============================================
        if (csrfToken && logUrl && text && text.trim() !== '') {
            // Check if this exact message was recently logged by any tab
            if (!isDuplicateLog(text, type)) {
                fetch(logUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ message: text, type, tab_id: window.tabId })
                }).catch(err => console.error('Log storage failed:', err));
            } else {
                console.log('[Dedup] Skipped duplicate database save:', text.substring(0, 50));
            }
        }
    };

    // Optional: Listen for tab closing to clean up localStorage
    window.addEventListener('beforeunload', () => {
        // Don't clean up immediately - other tabs might still need the dedup data
        // The data will expire naturally after DEDUP_WINDOW_MS
    });

    // Optional: Add a manual cleanup button for debugging (can be removed in production)
    window.cleanupDedupCache = function() {
        localStorage.removeItem('serial_recent_logs');
        loggedHashes.clear();
        console.log('[Dedup] Cache cleared');
    };


    function formatLogDate(datetime) {
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

const loadHistoricalLogs = async () => {
    try {
        const logs = await (await fetch('/logs')).json();
        logs.forEach(({ datetime, message, type }) => {
            let displayMessage = message;
            let shouldDisplay = true;
            
            if (type === 'data') {
                if (isGarbledData(message)) {
                    shouldDisplay = false;
                } else {
                    displayMessage = cleanGarbledData(message);
                    if (displayMessage === '') shouldDisplay = false;
                }
            }
            
            if (shouldDisplay) {
                const time = formatLogDate(datetime);
                const { prefix = '[INFO]', class: color = 'text-yellow-400' } = STYLES[type] || {};
                els.terminal.innerHTML += `<div class="mb-1"><span class="${color}">${prefix}</span> <span class="text-gray-500">(${time})</span> ${escapeHtml(displayMessage)}</div>`;
            }
        });
        els.terminal.scrollTop = els.terminal.scrollHeight;
    } catch (err) {
        console.error('Failed to load historical logs:', err);
    }
};
    loadHistoricalLogs();

    socket.on('status', ({ connected }) => {
        if (connected) {
            els.liveIndicator.innerHTML = '<span class="relative flex size-2.5"><span class="absolute size-full rounded-full bg-munti-green-1 opacity-75 animate-ping"></span><span class="relative size-2.5 rounded-full bg-munti-green-1"></span></span> Online';
            els.liveIndicator.classList.remove('text-gray-600', 'dark:text-gray-400');
            els.liveIndicator.classList.add('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
        } else {
            els.liveIndicator.innerHTML = '<span class="relative flex size-2.5"><span class="absolute size-full rounded-full bg-gray-400 opacity-75"></span><span class="relative size-2.5 rounded-full bg-gray-500"></span></span> Offline';
            els.liveIndicator.classList.remove('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
            els.liveIndicator.classList.add('text-gray-600', 'dark:text-gray-400');
        }
        els.sendBtn.disabled = !connected;
    });


    // Add this function to set all beacons offline
    function setAllBeaconsOffline() {
        console.log('[Arduino Status] Setting all beacons to OFFLINE');
        
        // Get all beacon items
        const allBeacons = document.querySelectorAll('.beacon-item');
        
        allBeacons.forEach(beacon => {
            const beaconId = beacon.getAttribute('data-beacon-id');
            if (beaconId) {
                // Update UI to offline
                setBeaconOffline(beaconId);
                
                // Update server status
                fetch('/settings/beacons/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ beacon_id: beaconId, status: false })
                }).catch(err => console.error(`Failed to update beacon ${beaconId} status:`, err));
            }
        });
        
        // Update beacon counts
        if (window.updateBeaconCounts) window.updateBeaconCounts();
        
        // Update map markers if available
        if (window.beaconMap && window.beaconMap.updateMarker) {
            allBeacons.forEach(beacon => {
                const beaconId = beacon.getAttribute('data-beacon-id');
                const isDoorOpen = beacon.getAttribute('data-door-open') === 'true';
                if (beaconId && window.beaconMap.updateMarker) {
                    window.beaconMap.updateMarker(beaconId, false, isDoorOpen);
                }
            });
        }
    }

    // Modify your existing socket.on('status') handler
    socket.on('status', ({ connected }) => {
        if (connected) {
            els.liveIndicator.innerHTML = '<span class="relative flex size-2.5"><span class="absolute size-full rounded-full bg-munti-green-1 opacity-75 animate-ping"></span><span class="relative size-2.5 rounded-full bg-munti-green-1"></span></span> Online';
            els.liveIndicator.classList.remove('text-gray-600', 'dark:text-gray-400');
            els.liveIndicator.classList.add('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
            
            // Reset beacon timeouts when reconnected
            if (window.resetBeaconTimeouts) {
                window.resetBeaconTimeouts();
            }
        } else {
            els.liveIndicator.innerHTML = '<span class="relative flex size-2.5"><span class="absolute size-full rounded-full bg-gray-400 opacity-75"></span><span class="relative size-2.5 rounded-full bg-gray-500"></span></span> Offline';
            els.liveIndicator.classList.remove('text-munti-green-1', 'dark:text-munti-green-1', 'font-medium');
            els.liveIndicator.classList.add('text-gray-600', 'dark:text-gray-400');
            
            // Set all beacons to offline when Arduino disconnects
            setAllBeaconsOffline();
        }
        els.sendBtn.disabled = !connected;
    });

    // Optional: Add a function to reset timeouts when reconnecting
    window.resetBeaconTimeouts = function() {
        // Clear all existing timeouts
        for (const beaconId in beaconTimeouts) {
            if (beaconTimeouts[beaconId]) {
                clearTimeout(beaconTimeouts[beaconId]);
                delete beaconTimeouts[beaconId];
            }
        }
    };



    if (!document.querySelector('#blink-style')) {
        const style = document.createElement('style');
        style.id = 'blink-style';
        style.textContent = `
        @keyframes blink-signal {
            0%, 100% { background-color: var(--blink-color, #e7000b); box-shadow: 0 3px 0 0 var(--blink-shadow, #c10007); }
            50% { background-color: var(--blink-color-light, #fb2c36); box-shadow: 0 3px 0 0 var(--blink-shadow, #c10007); }
        }
        .blink-signal {
            animation: blink-signal 1s infinite;
        }
    `;
        document.head.appendChild(style);
    }

    const ackTimeouts = {};

socket.on('data', line => {
    console.log('[Socket] Received data:', line);

    const hasNonPrintable = /[^\x20-\x7E\r\n\t]/.test(line);
    const nonPrintableRatio = (line.match(/[^\x20-\x7E\r\n\t]/g) || []).length / line.length;
    
    // If more than 30% of characters are non-printable, treat as binary data and skip
    if (hasNonPrintable && nonPrintableRatio > 0.3) {
        console.log('[Socket] Skipping garbled/binary data:', line);
        return; // Skip logging this data to terminal
    }



    const idMatch = line.match(/id=(\d+)/i);
    if (idMatch || line.match(/LED-Check/i) || line.match(/Online/i) || line.match(/ERROR/i)) {
        log('data', idMatch ? `ID:${idMatch[1]} Return: ${line}` : line);
    } else if (line.trim().length > 0 && !hasNonPrintable) {
        // Only log if it's not empty and not binary
        log('data', line);
    }

    // Handle LED-Check ACK for activation/deactivation blinking
    const ackMatch = line.match(/SlaveReply-> ID[:\s]*(\d+)\s+ACK/i);
    if (ackMatch) {
        const beaconId = ackMatch[1];
        console.log('[Socket] ACK received for beacon:', beaconId);
        
        const beaconBtn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
        
        // Check if this is for ACTIVATION
        if (window.beaconActivationPending && window.beaconActivationPending[beaconId]) {
            // Clear the pending flag
            delete window.beaconActivationPending[beaconId];
            
            // Clear the timeout for this beacon
            if (window.activationTimeoutIds && window.activationTimeoutIds[beaconId]) {
                clearTimeout(window.activationTimeoutIds[beaconId]);
                delete window.activationTimeoutIds[beaconId];
            }
            
            // Add to ACK tracking set
            if (window.activatedBeaconsAcked) {
                window.activatedBeaconsAcked.add(beaconId);
            }
            
            // NOW add blink class to the beacon that received ACK
            if (beaconBtn && !beaconBtn.classList.contains('blink-signal')) {
                beaconBtn.classList.add('blink-signal');
            }
            
            log('info', `Beacon ${beaconId} activated`);
        }
        
        // Check if this is for DEACTIVATION
        else if (window.beaconDeactivationPending && window.beaconDeactivationPending[beaconId]) {
            // Clear the pending flag
            delete window.beaconDeactivationPending[beaconId];
            
            // Clear the timeout for this beacon
            if (window.deactivationTimeoutIds && window.deactivationTimeoutIds[beaconId]) {
                clearTimeout(window.deactivationTimeoutIds[beaconId]);
                delete window.deactivationTimeoutIds[beaconId];
            }
            
            // Add to deactivation ACK tracking set
            if (window.deactivatedBeaconsAcked) {
                window.deactivatedBeaconsAcked.add(beaconId);
            }
            
            // REMOVE blink class from the beacon that received deactivation ACK
            if (beaconBtn && beaconBtn.classList.contains('blink-signal')) {
                beaconBtn.classList.remove('blink-signal');
            }
            
            log('info', `Beacon ${beaconId} deactivated`);
        }
        
        // Reset opacity if it was reduced due to timeout
        if (beaconBtn && beaconBtn.style.opacity) {
            beaconBtn.style.opacity = '';
        }
    }

        const onlineMatch = line.match(/^SlaveReply-> ID[:\s]*(\d+)\s+Online$/i);
        if (onlineMatch) {
            const beaconId = onlineMatch[1];
            console.log('[Socket] ONLINE message received for beacon:', beaconId);
            updateBeaconOnline(beaconId);
        }


        const doorErrorMatch = line.match(/SlaveReply-> ID[:\s]*(\d+)\s+ERROR:\s*(0x[0-9A-F]+)/i);
        if (doorErrorMatch) {
            const beaconId = doorErrorMatch[1];
            const errorCode = doorErrorMatch[2];
            console.log('[Socket] DOOR ERROR detected for beacon:', beaconId, 'Error code:', errorCode);

            const isDoorOpen = errorCode === '0x01';

            if (window.updateDoorStatus) {
                window.updateDoorStatus(beaconId, isDoorOpen);
            }

            fetch('/settings/beacons/door-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': window.serialConfig?.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    beacon_id: beaconId,
                    is_door_open: isDoorOpen
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        console.log(`[Door Status] Successfully updated beacon ${beaconId} door status to:`, isDoorOpen);
                    }
                })
                .catch(err => {
                    console.error(`[Door Status] Failed to update beacon ${beaconId} door status:`, err);
                });
        }
            const serviceModeMatch = line.match(/SlaveReply\s*->\s*ID\s*[:=]?\s*(\d+)\s+Service\s+mode\s+activated\.?/i);
            if (serviceModeMatch) {
                console.log('[Socket] Service mode match FOUND for beacon', serviceModeMatch[1]);
                updateDoorStatus(serviceModeMatch[1], false);
            } else {
                console.log('[Socket] Service mode NOT matched for line:', line);
            }
    });

    socket.on('message', ({ type, text }) => log(type, text));

    
    const sendCommand = async () => {
        const text = els.command.value.trim();
        if (!text) return;
        log('info', `Sending: ${text}`);
        try {
            const res = await fetch(`${serialUrl}/api/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Serial-Token': window.serialToken },
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
    };

    els.sendBtn.addEventListener('click', sendCommand);
    els.command.addEventListener('keypress', e => e.key === 'Enter' && sendCommand());


    const beaconTimeouts = {};

    // Make sure these functions are defined and accessible
    const OFFLINE_CLASSES = [
        'offline-item', 'cursor-default', 'border-gray-500/40', 'bg-gray-400',
        'text-gray-700', 'shadow-[0_6px_0_0_#4b5563]'
    ];

    const ONLINE_CLASSES = [
        'online-item', 'cursor-pointer', 'border-munti-blue-2/40', 'bg-munti-blue-2',
        'hover:bg-munti-blue-2', 'hover:shadow-[0_3px_0_0_#003595]', 'hover:translate-y-[3px]',
        'text-gray-900', 'shadow-[0_6px_0_0_#003595]'
    ];

    function setBeaconOffline(beaconId) {
        window.clearOfflineBeaconDoor?.(beaconId);
        const btn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
        if (!btn) return;

        // Remove online classes
        ONLINE_CLASSES.forEach(cls => btn.classList.remove(cls));
        
        // Add offline classes
        OFFLINE_CLASSES.forEach(cls => btn.classList.add(cls));

        // Update connection icon
        const connectionIcon = btn.querySelector('svg:first-child');
        if (connectionIcon) connectionIcon.classList.add('text-munti-red-0');

        // Add offline indicator if not exists
        if (!btn.querySelector('.absolute.top-1.right-1')) {
            const span = document.createElement('span');
            span.className = 'absolute top-1 right-1 text-[10px] font-bold text-gray-600';
            span.textContent = '';
            btn.appendChild(span);
        }

        // Remove selection if was selected
        if (btn.classList.contains('bg-yellow-400')) {
            btn.classList.remove(
                'bg-yellow-400', 'hover:bg-yellow-300',
                'border-yellow-600/40', 'shadow-[0_3px_0_0_#ca8a04]',
                'translate-y-[3px]'
            );
        }
    }

    function setBeaconClasses(button, targetSet, oppositeSet) {
        if (!button) return;
        oppositeSet.forEach(cls => button.classList.remove(cls));
        targetSet.forEach(cls => button.classList.add(cls));
        if (button.classList.contains('bg-yellow-400')) {
        }
    }

    function setBeaconOnline(beaconId) {
        const btn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
        if (!btn) return;

        setBeaconClasses(btn, ONLINE_CLASSES, OFFLINE_CLASSES);

        const connectionIcon = btn.querySelector('svg:first-child');
        if (connectionIcon) connectionIcon.classList.remove('text-munti-red-0');

        const offlineSpan = btn.querySelector('.absolute.top-1.right-1');
        if (offlineSpan) offlineSpan.remove();
    }

    socket.on('beacon:status', (data) => {
        console.log('[Socket] Beacon status update:', data);

        if (data.status) {
            if (beaconTimeouts[data.beacon_id]) {
                clearTimeout(beaconTimeouts[data.beacon_id]);
                delete beaconTimeouts[data.beacon_id];
            }

            setBeaconOnline(data.beacon_id);

            beaconTimeouts[data.beacon_id] = setTimeout(() => {
                setBeaconOffline(data.beacon_id);
                delete beaconTimeouts[data.beacon_id];
            }, 600000); // 10 minutes

            if (window.updateBeaconCounts) window.updateBeaconCounts();
        } else {
            setBeaconOffline(data.beacon_id);
            if (window.updateBeaconCounts) window.updateBeaconCounts();
        }

        if (window.beaconMap?.updateMarker) {
            const btn = document.querySelector(`.beacon-item[data-beacon-id="${data.beacon_id}"]`);
            const isDoorOpen = btn ? btn.getAttribute('data-door-open') === 'true' : false;
            window.beaconMap.updateMarker(data.beacon_id, data.status, isDoorOpen);
        }
    });

    function updateBeaconOnline(beaconId) {
        console.log(`[Beacon Status] Updating beacon ${beaconId} to ONLINE`);

        if (beaconTimeouts[beaconId]) {
            console.log(`[Beacon Status] Clearing existing timeout for beacon ${beaconId}`);
            clearTimeout(beaconTimeouts[beaconId]);
            delete beaconTimeouts[beaconId];
        }

        setBeaconOnline(beaconId);
        if (window.updateBeaconCounts) window.updateBeaconCounts();

        if (window.beaconMap?.updateMarker) {
            const btn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
            const isDoorOpen = btn ? btn.getAttribute('data-door-open') === 'true' : false;
            window.beaconMap.updateMarker(beaconId, true, isDoorOpen);
        }

        console.log(`[Beacon Status] UI updated to ONLINE for beacon ${beaconId}`);

        console.log(`[Beacon Status] Sending ONLINE status to server for beacon ${beaconId}`);
        fetch('/settings/beacons/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ beacon_id: beaconId, status: true })
        })
            .then(response => {
                console.log(`[Beacon Status] Server response status:`, response.status);
                if (response.redirected) {
                    console.error(`[Beacon Status] Request was redirected to:`, response.url);
                    throw new Error('Session expired or not authenticated');
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error(`[Beacon Status] Response text:`, text.substring(0, 200));
                        throw new Error('Server returned non-JSON response');
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log(`[Beacon Status] Server response data:`, data);
                if (data.success) {
                    console.log(`[Beacon Status] Successfully updated beacon ${beaconId} to ONLINE`);
                } else {
                    console.error(`[Beacon Status] Server returned error:`, data.error);
                }
            })
            .catch(err => {
                console.error(`[Beacon Status] Failed to update beacon ${beaconId} online status:`, err);
            });

        beaconTimeouts[beaconId] = setTimeout(() => {
            console.log(`[Beacon Status] No ONLINE message received for ${beaconId} in 10 seconds, marking as OFFLINE`);

            setBeaconOffline(beaconId);
            if (window.updateBeaconCounts) window.updateBeaconCounts();

            if (window.beaconMap?.updateMarker) {
                const btn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
                const isDoorOpen = btn ? btn.getAttribute('data-door-open') === 'true' : false;
                window.beaconMap.updateMarker(beaconId, false, isDoorOpen);
            }

            console.log(`[Beacon Status] Sending OFFLINE status to server for beacon ${beaconId}`);
            fetch('/settings/beacons/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ beacon_id: beaconId, status: false })
            })
                .then(response => {
                    console.log(`[Beacon Status] Offline - Server response status:`, response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log(`[Beacon Status] Offline - Server response:`, data);
                    if (data.success) {
                        console.log(`[Beacon Status] Successfully updated beacon ${beaconId} to OFFLINE`);
                    } else {
                        console.error(`[Beacon Status] Offline - Server error:`, data.error);
                    }
                })
                .catch(err => {
                    console.error(`[Beacon Status] Failed to update beacon ${beaconId} offline status:`, err);
                });

            delete beaconTimeouts[beaconId];
        }, 600000); // 10 minutes timeout
    }



    function updateDoorStatus(beaconId, isDoorOpen) {
        console.log(`[Door Status] Updating beacon ${beaconId} door status to:`, isDoorOpen ? 'OPEN' : 'CLOSED');

        // Acknowledge locally without waiting for Pusher or the database request.
        window.updateDoorStatus?.(beaconId, isDoorOpen);
        const beaconBtn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
        if (beaconBtn) {
            beaconBtn.setAttribute('data-door-open', String(isDoorOpen));
            const doorIcon = beaconBtn.querySelector('.door-icon');
            if (doorIcon && !isDoorOpen) {
                doorIcon.classList.remove('text-munti-red-0', 'text-red-500');
                doorIcon.classList.add('text-gray-900');
            }
            window.beaconMap?.updateMarker?.(beaconId, beaconBtn.classList.contains('online-item'), isDoorOpen);
        }
        if (!isDoorOpen) window.removeOpenDoor?.(beaconId);

        fetch('/settings/beacons/door-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.serialConfig?.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                beacon_id: beaconId,
                is_door_open: isDoorOpen
            })
        })
            .then(response => {
                console.log(`[Door Status] Server response status:`, response.status);
                if (response.redirected) {
                    console.error(`[Door Status] Request was redirected to:`, response.url);
                    throw new Error('Session expired or not authenticated');
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`[Door Status] Server response data:`, data);
                if (data.success) {
                    console.log(`[Door Status] Successfully updated beacon ${beaconId} door status to:`, isDoorOpen);

                    if (window.logToTerminal) {
                        window.logToTerminal(
                            `Beacon ${beaconId} door ${isDoorOpen ? 'OPENED' : 'CLOSED'}`,
                            isDoorOpen ? 'warning' : 'info'
                        );
                    }
                } else {
                    console.error(`[Door Status] Server returned error:`, data.error);
                }
            })
            .catch(err => {
                console.error(`[Door Status] Failed to update beacon ${beaconId} door status:`, err);
            });
    }
});




    




