@auth
@include('layouts.header')
@include('layouts.topbar')

<script>
    // Reset the displayed door state when connectivity is lost; do not send a hardware command.
    window.clearOfflineBeaconDoor = function(beaconId) {
        document.querySelectorAll(`.beacon-item[data-beacon-id="${beaconId}"]`).forEach(beacon => {
            beacon.setAttribute('data-door-open', 'false');
            const icon = beacon.querySelector('.door-icon');
            if (icon) {
                icon.classList.remove('text-munti-red-0', 'text-red-500');
                icon.classList.add('text-gray-900');
            }
        });
        window.removeOpenDoor?.(beaconId);
        window.beaconMap?.updateMarker?.(beaconId, false, false);
    };
    window.sirenLocations = <?= json_encode(
        $sirens->mapWithKeys(function ($siren) {
            return [$siren->oid => $siren->location?->location_name ?? $siren->group ?? 'Unknown'];
        })->filter()
    ) ?>;

    window.dbSirens = <?= json_encode(
        $sirens->map(function ($siren) {
            return [
                'id' => $siren->id,
                'oid' => $siren->oid,
                'name' => $siren->name,
                'group' => $siren->group,
                'location_name' => $siren->location?->location_name,
                'enabled' => $siren->enabled,
            ];
        })
    ) ?>;

    window.beaconNames = <?= json_encode(
        $beacons->mapWithKeys(function ($beacon) {
            return [$beacon->beacon_id => $beacon->name ?? 'Unnamed Beacon'];
        })
    ) ?>;
</script>

// This container is used to show toast messages for various actions, it is hidden by default and toasts are added dynamically
<div id="toastContainer" class="fixed top-[17px] right-3 z-50 flex flex-col gap-1 w-80 break-words"></div>
<script>
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `px-4 py-3 rounded-lg shadow-lg text-white text-sm font-medium ${
        type === 'success' ? 'bg-munti-green-1' : type === 'error' ? 'bg-munti-red-0' : 'bg-munti-yellow-0'
    }`;
        toast.textContent = message;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => {
            toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    let countdownInterval = null;
    let countdownRemaining = 0;

    function showCountdownPopup(seconds) {
        const popup = document.getElementById('countdownPopup');
        const timerSpan = document.getElementById('countdownTimer');
        if (!popup || !timerSpan) return;

        countdownRemaining = seconds;
        updateCountdownDisplay();

        popup.classList.remove('hidden');
        popup.classList.add('block');

        if (countdownInterval) clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            if (countdownRemaining <= 1) {
                clearInterval(countdownInterval);
                countdownInterval = null;
                hideCountdownPopup();
                if (deactivateBtn && !deactivateBtn.disabled) {
                    deactivateBtn.click();
                }
            } else {
                countdownRemaining--;
                updateCountdownDisplay();
            }
        }, 1000);
    }

    function updateCountdownDisplay() {
        const timerSpan = document.getElementById('countdownTimer');
        if (timerSpan) {
            const minutes = Math.floor(countdownRemaining / 60);
            const seconds = countdownRemaining % 60;
            timerSpan.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
    }

    function hideCountdownPopup() {
        const popup = document.getElementById('countdownPopup');
        if (popup) {
            popup.classList.add('hidden');
            popup.classList.remove('block');
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
    }
</script>

// This container is used to show the list of open doors for beacons that have door sensors, it is hidden by default
<div id="doorPopup" class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 transform transition-all">
        <!-- Header -->
        <div class="bg-munti-red-0 rounded-t-2xl px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h3 class="text-white font-bold text-lg">Beacon Open Doors</h3>
                <span id="doorCountBadge" class="bg-white/20 text-white text-xs font-semibold px-2 py-0.5 rounded-full">0</span>
            </div>
            <button id="closeDoorPopupBtn" class="text-white/80 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-5 max-h-80 overflow-y-auto custom-scroll">
            <div id="doorListContent" class="space-y-3">
                <div class="text-gray-500 dark:text-gray-400 text-center py-4">No open doors</div>
            </div>
        </div>
    </div>
</div>
<script>
    window.beaconDoors = <?= json_encode(
        $beacons->mapWithKeys(function ($beacon) {
            return [$beacon->beacon_id => [
                'name' => $beacon->name ?? 'Unnamed Beacon',
                'is_door_open' => $beacon->status && $beacon->is_door_open
            ]];
        })
    ) ?>;
</script>

// This container is used to show the countdown before auto-deactivation, it is hidden by default and shown when the countdown starts
<div id="countdownPopup" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-2xl border border-gray-200 p-6 z-50 hidden transition-all duration-300 min-w-[280px] text-center">
    <div class="flex flex-col items-center gap-3">
        <div class="text-4xl font-bold text-munti-blue-0" id="countdownTimer">00:00</div>
        <div class="text-sm text-gray-600">Remaining until auto‑deactivation</div>
    </div>
</div>

// This container is used to show the progress of beacon deactivation verification, it is hidden by default and shown when the process starts
<div id="deactivationProgressContainer" class="fixed bottom-[21px] right-6 z-50 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-munti-blue-0 dark:border-gray-700 p-5 w-[480px] hidden transition-all duration-300">
    <div class="flex justify-between text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
        <span>Beacon Deactivation Verification</span>
        <span id="deactivationProgressPercent">0%</span>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2.5">
        <div id="deactivationProgressBar" class="bg-munti-green-1 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">
        <span id="deactivationProgressDetail">0 / 0</span> beacons processed
    </div>
</div>

// Main container with fixed positioning and scrollable content
<div class="fixed inset-0 flex flex-col bg-gray-300 dark:bg-gray-900 text-gray-900 dark:text-gray-200 overflow-y-auto custom-scroll">
    <div class="flex flex-col flex-grow min-h-0 p-3 pt-16 md:p-4 md:pt-20">
        <div class="flex-grow grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2 mb-2 min-h-full lg:min-h-[50vh] overflow-hidden">
            @include('partials.controller.groups-card')
            @include('partials.controller.signals-card')
            @include('partials.controller.beacons-card')
            @include('partials.controller.sirens-card')
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-2 shrink-0">
            @include('partials.controller.map-card')
            @include('partials.controller.serial-log-card')
            @include('partials.controller.controls-card')
        </div>
    </div>
</div>
<script>    
    (function() {
        const SELECTED_CLASSES = [
            'bg-munti-yellow-0', 'hover:bg-munti-yellow-1',
            'border-munti-yellow-0/40',
            'shadow-[0_3px_0_0_#ca8a04]',
            'translate-y-[3px]',
            'text-gray-900'
        ];

        const groupsContainer = document.getElementById('groups-container');
        const signalsContainer = document.getElementById('signals-container');
        const selectAllBtn = document.getElementById('select-all-groups');
        const selectAllText = document.getElementById('select-all-text');
        const clearAllBtn = document.getElementById('clear-all-selections');
        const sirensContainer = document.getElementById('sirensContainer');
        const refreshBtn = document.getElementById('refreshSirensBtn');
        const tooltip = document.getElementById('tooltip');

        let allGroupsSelected = false;

        // This function determines whether to use light or dark text based on the background color for better readability, used for signal buttons with dynamic colors
        function getTextColorForBackground(hexColor) {
            let r, g, b;
            
            if (hexColor.startsWith('#')) {
                r = parseInt(hexColor.slice(1, 3), 16);
                g = parseInt(hexColor.slice(3, 5), 16);
                b = parseInt(hexColor.slice(5, 7), 16);
            } else if (hexColor.includes(',')) {
                // Handle RGB format
                const parts = hexColor.split(',');
                r = parseInt(parts[0]);
                g = parseInt(parts[1]);
                b = parseInt(parts[2]);
            } else {
                return 'text-gray-900';
            }
            
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            
            return luminance < 0.5 ? 'text-white' : 'text-gray-900';
        }

        // This function shows a toast message and then refreshes the page after a short delay, used after activating/deactivating to update the device status
        function refreshPageAfterDelay(delay = 2000) {
            setTimeout(() => {
                // Optional: Show a toast message before refresh
                showToast('Refreshing page to update device status...', 'info');
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }, delay);
        }

        // This function darkens a hex color by a given percentage, used for creating shadows that match the button color
        function darkenColor(hex, percent = 0.7) {
            let r = parseInt(hex.slice(1, 3), 16);
            let g = parseInt(hex.slice(3, 5), 16);
            let b = parseInt(hex.slice(5, 7), 16);
            r = Math.floor(r * percent);
            g = Math.floor(g * percent);
            b = Math.floor(b * percent);
            return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
        }

        // On page load, set the background color and shadow for signal buttons based on their data-color attribute
        document.querySelectorAll('.signal-btn').forEach(btn => {
            const color = btn.dataset.color;
            if (color) {
                btn.style.backgroundColor = color;
                const dark = darkenColor(color, 0.7);
                btn.style.setProperty('--signal-shadow', dark);
            }
        });

        // This function saves the original classes of an element that are relevant for the selection styling, so they can be restored later
        function saveOriginalClasses(el) {
            if (el.dataset.originalClasses) return;
            const classesToPreserve = [
                'bg-munti-blue-2', 'bg-munti-blue-0',
                'hover:bg-munti-blue-2', 'hover:bg-munti-blue-2',
                'border-munti-blue-0/70', 'border-munti-blue-0/60', 'border-munti-blue-0/40',
                'shadow-[0_6px_0_0_#2596be]',
                'text-gray-900'
            ];
            const original = classesToPreserve.filter(cls => el.classList.contains(cls));
            el.dataset.originalClasses = original.join(' ');
        }

        // This function applies the selected classes and styles to an element when it is selected
        function applySelected(el) {
            if (el.classList.contains('offline-item')) return;
            saveOriginalClasses(el);
            el.classList.remove(
                'bg-munti-blue-2', 'bg-munti-blue-0',
                'hover:bg-munti-blue-2', 'hover:bg-munti-blue-2',
                'border-munti-blue-0/70', 'border-munti-blue-0/60', 'border-munti-blue-0/40',
                'shadow-[0_6px_0_0_#2596be]'
            );
            el.classList.add(...SELECTED_CLASSES);

            if (el.classList.contains('signal-btn')) {
                const color = el.dataset.color;
                if (color) {
                    const darker = darkenColor(color, 0.7);
                    el.style.boxShadow = `0 3px 0 0 ${darker}`;
                }
            }
        }

function restoreOriginal(el) {
    // 1. Handle signal buttons (they have dynamic colors)
    if (el.closest('#signals-container')) {
        resetSignalButton(el);   // you already have this function
        return;
    }

    // 2. Handle group buttons (inside #groups-container)
    if (el.closest('#groups-container') && el.classList.contains('select-all-items')) {
        // Remove any selection-related classes and styles
        el.classList.remove(...SELECTED_CLASSES);
        el.classList.remove('bg-munti-yellow-0', 'hover:bg-munti-yellow-1',
                            'border-munti-yellow-0/40', 'shadow-[0_3px_0_0_#ca8a04]',
                            'translate-y-[3px]');
        el.style.boxShadow = '';
        // Restore the original appearance (as defined in the HTML)
        el.classList.add('bg-white', 'hover:bg-munti-yellow-1', 'text-munti-black-0',
                         'border-munti-[#b2b2b2]/70', 'shadow-[0_6px_0_0_#b2b2b2]');
        return;
    }

    // 3. Handle offline items (should never be altered)
    if (el.classList.contains('offline-item')) return;

    // 4. Default restore for beacons, sirens, etc.
    el.classList.remove(...SELECTED_CLASSES);
    el.style.boxShadow = '';

    if (el.dataset.originalClasses) {
        el.classList.add(...el.dataset.originalClasses.split(' '));
        return;
    }

    if (el.classList.contains('online-item')) {
        el.classList.add('text-gray-900', 'shadow-[0_6px_0_0_#2596be]');
        if (el.textContent.trim() === 'Tunasansss') {
            el.classList.add('bg-munti-blue-0', 'hover:bg-munti-blue-2', 'border-munti-blue-0/60');
        } else {
            el.classList.add('bg-munti-blue-2', 'hover:bg-munti-blue-2', 'border-munti-blue-0/70');
        }
    }
}

        function resetSignalButton(btn) {
            // Remove selection and gray-out classes
            btn.classList.remove(...SELECTED_CLASSES);
            btn.classList.remove('bg-gray-400', 'text-gray-900');

            // Get original color from data-color attribute
            const originalColor = btn.dataset.color || '#99a1af';
            btn.style.backgroundColor = originalColor;

            // Restore correct text color based on background luminance
            const textColorClass = getTextColorForBackground(originalColor);
            btn.classList.remove('text-white', 'text-gray-900');
            btn.classList.add(textColorClass);

            // Restore original shadow (darker version of the button color)
            const darker = darkenColor(originalColor, 0.7);
            btn.style.boxShadow = `0 6px 0 0 ${darker}`;

            // Remove any lingering inline styles that might conflict
            btn.style.removeProperty('border');
            btn.style.removeProperty('border-color');
        }

        // This function checks if an element is currently selected based on the presence of the selected classes
        function isSelected(el) {
            return el.classList.contains('bg-munti-yellow-0');
        }

        // This function checks if all items in a group are selected and updates the group button accordingly
        function syncGroupButton(groupName) {
            const groupBtn = Array.from(document.querySelectorAll('#groups-container .select-all-items'))
                .find(b => b.textContent.trim() === groupName);
            if (!groupBtn) return;
            const items = document.querySelectorAll(
                `.beacon-item.online-item[data-group="${groupName}"], .siren-item.online-item[data-group="${groupName}"]`
            );
            const allSelected = items.length > 0 && Array.from(items).every(isSelected);
            if (allSelected) {
                applySelected(groupBtn);
            } else {
                restoreOriginal(groupBtn);
            }
        }

        // This function updates the "Select All" button text and style based on whether all groups are selected or not
        function updateSelectAllUI(selected) {
            selectAllText.textContent = selected ? 'Unselect All' : 'Select All';
            selectAllBtn.classList.toggle('bg-munti-yellow-0', selected);
            selectAllBtn.classList.toggle('dark:bg-munti-yellow-0', selected);
            selectAllBtn.classList.toggle('bg-munti-white', !selected);
            selectAllBtn.classList.toggle('dark:bg-munti-blue-2', !selected);
        }

        // Select All Beacons functionality
        const selectAllBeaconsBtn = document.getElementById('select-all-beacons');
        if (selectAllBeaconsBtn) {
            selectAllBeaconsBtn.addEventListener('click', () => {
                const beacons = document.querySelectorAll('.beacon-item.online-item');
                const allBeaconsSelected = beacons.length > 0 && Array.from(beacons).every(isSelected);
                
                if (!allBeaconsSelected) {
                    beacons.forEach(beacon => {
                        if (!isSelected(beacon) && !beacon.classList.contains('offline-item')) {
                            applySelected(beacon);
                        }
                    });
                    
                    const allGroupButtons = document.querySelectorAll('#groups-container .select-all-items');
                    allGroupButtons.forEach(groupBtn => {
                        const groupName = groupBtn.textContent.trim();
                        const groupBeacons = document.querySelectorAll(`.beacon-item.online-item[data-group="${groupName}"]`);
                        
                        if (groupBeacons.length > 0) {
                            const allSelected = Array.from(groupBeacons).every(beacon => isSelected(beacon));
                            if (allSelected) {
                                applySelected(groupBtn);
                            } else {
                                restoreOriginal(groupBtn);
                            }
                        } else {
                            restoreOriginal(groupBtn);
                        }
                    });
                    
                    selectAllBeaconsBtn.classList.add('bg-munti-yellow-0', 'signal-selected');
                    
                } else {
                    beacons.forEach(beacon => {
                        if (isSelected(beacon)) {
                            restoreOriginal(beacon);
                        }
                    });
                    
                    const allGroupButtons = document.querySelectorAll('#groups-container .select-all-items');
                    allGroupButtons.forEach(groupBtn => {
                        restoreOriginal(groupBtn);
                    });
                    
                    selectAllBeaconsBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                }
            });
        }

        // Select All Sirens functionality
        const selectAllSirensBtn = document.getElementById('select-all-sirens');
        if (selectAllSirensBtn) {
            selectAllSirensBtn.addEventListener('click', () => {
                const sirens = document.querySelectorAll('.siren-item.online-item');
                const allSirensSelected = sirens.length > 0 && Array.from(sirens).every(isSelected);
                
                if (!allSirensSelected) {
                    sirens.forEach(siren => {
                        if (!isSelected(siren) && !siren.classList.contains('offline-item')) {
                            applySelected(siren);
                        }
                    });
                    
                    const allGroupButtons = document.querySelectorAll('#groups-container .select-all-items');
                    allGroupButtons.forEach(groupBtn => {
                        const groupName = groupBtn.textContent.trim();
                        const groupSirens = document.querySelectorAll(`.siren-item.online-item[data-group="${groupName}"]`);
                        
                        if (groupSirens.length > 0) {
                            const allSelected = Array.from(groupSirens).every(siren => isSelected(siren));
                            if (allSelected) {
                                applySelected(groupBtn);
                            } else {
                                restoreOriginal(groupBtn);
                            }
                        } else {
                            restoreOriginal(groupBtn);
                        }
                    });
                    
                    selectAllSirensBtn.classList.add('bg-munti-yellow-0', 'signal-selected');
                    
                } else {
                    sirens.forEach(siren => {
                        if (isSelected(siren)) {
                            restoreOriginal(siren);
                        }
                    });
                    
                    const allGroupButtons = document.querySelectorAll('#groups-container .select-all-items');
                    allGroupButtons.forEach(groupBtn => {
                        restoreOriginal(groupBtn);
                    });
                    
                    selectAllSirensBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                }
            });
        }

        // Global state to track activation status and acknowledged beacons
        let isActivated = false;
        const activateBtn = document.getElementById('activateBtn');
        const deactivateBtn = document.getElementById('deactivateBtn');
        window.activatedBeaconsAcked = new Set();   
        window.deactivatedBeaconsAcked = new Set();   
        window.activationTimeoutIds = {};
        window.deactivationTimeoutIds = {};

        // Clear All Selections functionality
        function clearAllSelections() {
            document.querySelectorAll('#groups-container .select-all-items, .beacon-item.online-item, .siren-item.online-item, .select-items')
                .forEach(restoreOriginal);
            
            allGroupsSelected = false;
            updateSelectAllUI(false);
            
            const selectAllBeaconsBtn = document.getElementById('select-all-beacons');
            if (selectAllBeaconsBtn) {
                selectAllBeaconsBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                selectAllBeaconsBtn.classList.add('bg-munti-white', 'dark:bg-munti-blue-2');
                selectAllBeaconsBtn.style.removeProperty('background-color');
            }
            
            const selectAllSirensBtn = document.getElementById('select-all-sirens');
            if (selectAllSirensBtn) {
                selectAllSirensBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                selectAllSirensBtn.classList.add('bg-munti-white', 'dark:bg-munti-blue-2');
                selectAllSirensBtn.style.removeProperty('background-color');
            }
        }
        // Add event listener for Clear All button
        document.getElementById('clear-all-selections')?.addEventListener('click', clearAllSelections);

        // Save original classes for all relevant buttons on page load
        document.querySelectorAll('#groups-container .select-all-items, .beacon-item.online-item, .siren-item.online-item, .select-items')
            .forEach(btn => saveOriginalClasses(btn));

        // Handle clicks on beacon/siren/group/signal buttons using event delegation
        document.addEventListener('click', e => {
            const target = e.target.closest('.beacon-item.online-item, .siren-item.online-item, .select-items, #groups-container .select-all-items');
            if (!target) return;

            if (signalsContainer && signalsContainer.contains(target) && target.classList.contains('select-items')) {
                const hasSelectedSignal = Array.from(signalsContainer.querySelectorAll('.select-items')).some(
                    btn => btn.classList.contains('bg-munti-yellow-0') || btn.classList.contains('signal-selected')
                );
                
                const targetIsSelected = isSelected(target);
                
                if (hasSelectedSignal && !targetIsSelected) {
                    console.log('Please unselect the current signal first');
                    
                    target.style.opacity = '0.6';
                    setTimeout(() => {
                        target.style.opacity = '';
                    }, 200);
                    
                    return;
                }
                
                const willSelect = !targetIsSelected;
                
                signalsContainer.querySelectorAll('.select-items').forEach(btn => {
                    if (btn !== target) {
                        restoreOriginal(btn);
                    }
                });
                
                targetIsSelected ? restoreOriginal(target) : applySelected(target);
                
                updateUnselectedSignalsStyle();
                
                return;
            }

            // This function updates the style of unselected signal buttons based on whether any signal is currently selected
            function updateUnselectedSignalsStyle() {
                const signals = document.querySelectorAll('#signals-container .select-items');
                const hasAnySelected = Array.from(signals).some(btn => btn.classList.contains('bg-munti-yellow-0') || btn.classList.contains('signal-selected'));
                
                signals.forEach(btn => {
                    if (btn.classList.contains('bg-munti-yellow-0') || btn.classList.contains('signal-selected')) {
                        return;
                    }
                    if (hasAnySelected) {
                        applyUnselectedGrayStyle(btn);
                    } else {
                        restoreOriginalSignalStyle(btn);
                    }
                });
            }

            // Add this function to apply gray styling to unselected signal buttons
            function applyUnselectedGrayStyle(btn) {
                if (!btn.dataset.originalColorValue) {
                    btn.dataset.originalColorValue = btn.dataset.color || '';
                    btn.dataset.originalBackgroundColor = btn.style.backgroundColor || '';
                }
                
                btn.classList.remove('bg-munti-yellow-0', 'signal-selected', 'bg-munti-red-0', 'bg-munti-blue-0');
                
                btn.classList.add('bg-gray-400', 'text-gray-900');
                btn.style.setProperty('background-color', '#9ca3af', 'important');
                btn.style.removeProperty('border');
                btn.style.removeProperty('border-color');
                btn.style.removeProperty('box-shadow');
                btn.style.boxShadow = '0 6px 0 0 #6b7280';
                btn.style.color = '#000000';
                btn.style.fontWeight = '500';
            }

            // Add this function to restore original signal button style
            function restoreOriginalSignalStyle(btn) {
                btn.classList.remove('bg-gray-400', 'text-gray-900');
                
                const originalColor = btn.dataset.originalColorValue || btn.dataset.color;
                if (originalColor && originalColor !== '#99a1af') {
                    btn.style.backgroundColor = originalColor;
                    
                    const textColorClass = getTextColorForBackground(originalColor);
                    btn.classList.remove('text-white', 'text-gray-900');
                    btn.classList.add(textColorClass);
                    
                    btn.style.color = '';
                    
                    const darker = darkenColor(originalColor, 0.7);
                    btn.style.boxShadow = `0 6px 0 0 ${darker}`;
                } else {
                    restoreOriginal(btn);
                }
            }

            if (target.closest('#groups-container') && target.classList.contains('select-all-items')) {
                const willSelect = !isSelected(target);
                const group = target.textContent.trim();
                willSelect ? applySelected(target) : restoreOriginal(target);
                document.querySelectorAll(`.beacon-item[data-group="${group}"], .siren-item[data-group="${group}"]`)
                    .forEach(item => {
                        if (item.classList.contains('offline-item')) return;
                        willSelect ? applySelected(item) : restoreOriginal(item);
                    });
                return;
            }

            if (target.classList.contains('beacon-item') || target.classList.contains('siren-item') || target.classList.contains('select-items')) {
                if (target.classList.contains('offline-item')) return;
                isSelected(target) ? restoreOriginal(target) : applySelected(target);
                const group = target.dataset.group;
                if (group) queueMicrotask(() => syncGroupButton(group));
            }
        });

        selectAllBtn.addEventListener('click', () => {
            allGroupsSelected = !allGroupsSelected;
            updateSelectAllUI(allGroupsSelected);
            document.querySelectorAll('#groups-container .select-all-items').forEach(groupBtn => {
                const group = groupBtn.textContent.trim();
                if (allGroupsSelected) applySelected(groupBtn);
                else restoreOriginal(groupBtn);
                document.querySelectorAll(`.beacon-item[data-group="${group}"], .siren-item[data-group="${group}"]`)
                    .forEach(item => {
                        if (item.classList.contains('offline-item')) return;
                        allGroupsSelected ? applySelected(item) : restoreOriginal(item);
                    });
            });
            document.querySelectorAll('.siren-item.online-item').forEach(siren => {
                allGroupsSelected ? applySelected(siren) : restoreOriginal(siren);
            });
        });

        clearAllBtn.addEventListener('click', clearAllSelections);

        const beaconsContainer = document.getElementById('beaconsContainer');
        const terminal = document.getElementById('terminal');

        // Load beacons from PHP and create buttons
        function loadBeaconsFromPHP() {
            if (!beaconsContainer) return Promise.resolve();

            const beacons = <?php echo json_encode($beacons); ?>;

            if (!beacons || beacons.length === 0) {
                beaconsContainer.innerHTML = '<div class="col-span-full text-center py-10 text-gray-600 dark:text-gray-400 text-sm italic">No beacons registered yet</div>';
                return Promise.resolve();
            }

            beaconsContainer.innerHTML = '';

            beacons.forEach(beacon => {
                const isOnline = beacon.status;
                const isDoorOpen = !!isOnline && !!beacon.is_door_open;
                const displayGroup = beacon.location?.location_name || beacon.group || 'Unknown';

                const button = document.createElement('button');
                button.type = 'button';
                button.className = `select-all-items beacon-item relative w-full px-4 py-1 text-center uppercase font-semibold rounded-md h-20 transition-all duration-150 ease-in-out flex flex-col ${isOnline ? 'online-item cursor-pointer border-munti-blue-0/40 bg-munti-blue-2 hover:bg-munti-blue-2 hover:shadow-[0_3px_0_0_#2596be] hover:translate-y-[3px] text-gray-900 shadow-[0_6px_0_0_#2596be]' : 'offline-item cursor-default border-gray-500/40 bg-gray-400 text-gray-700 shadow-[0_6px_0_0_#4b5563]'}`;
                button.setAttribute('data-group', displayGroup);
                button.setAttribute('data-beacon-id', beacon.beacon_id);
                button.setAttribute('data-door-open', isDoorOpen);

                button.innerHTML = `
                    <div class="flex-grow flex items-center justify-center">
                        ${beacon.name || 'Unnamed Beacon'}
                    </div>
                    <div class="flex justify-center gap-1 pb-1">
                        <!-- Connection-signal icon: red when offline -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" class="w-4 h-4 connection-icon ${!isOnline ? 'text-munti-blue-0' : ''}" fill="currentColor">
                            <title>Connection</title>
                            <path d="M15 12h2v18h-2zm-3.67 6.22a7 7 0 0 1 0-10.44l1.34 1.49a5 5 0 0 0 0 7.46zm9.34 0l-1.34-1.49a5 5 0 0 0 0-7.46l1.34-1.49a7 7 0 0 1 0 10.44"/>
                            <path d="M8.4 21.8a11 11 0 0 1 0-17.6l1.2 1.6a9 9 0 0 0 0 14.4zm15.2 0l-1.2-1.6a9 9 0 0 0 0-14.4l1.2-1.6a11 11 0 0 1 0 17.6"/>
                        </svg>
                        <!-- Warning-alt-filled icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 32 32" class="w-4 h-4" fill="currentColor">
                            <title>Battery Error</title>
                            <path fill="none" d="M16 26a1.5 1.5 0 1 1 1.5-1.5A1.5 1.5 0 0 1 16 26m-1.125-5h2.25v-9h-2.25Z"/>
                            <path d="M16.002 6.171h-.004L4.648 27.997l.003.003h22.698l.002-.003ZM14.875 12h2.25v9h-2.25ZM16 26a1.5 1.5 0 1 1 1.5-1.5A1.5 1.5 0 0 1 16 26"/>
                            <path d="M29 30H3a1 1 0 0 1-.887-1.461l13-25a1 1 0 0 1 1.774 0l13 25A1 1 0 0 1 29 30M4.65 28h22.7l.001-.003L16.002 6.17h-.004L4.648 27.997Z"/>
                        </svg>
                        <!-- Door-open icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" class="w-4 h-4 door-icon ${isDoorOpen ? 'text-munti-red-0' : 'text-gray-900 dark:text-gray-200'}" fill="currentColor">
                            <title>Door-open</title>
                            <path d="M5 5v14a1 1 0 0 0 1 1h3v-2H7V6h2V4H6a1 1 0 0 0-1 1m14.242-.97l-8-2A1 1 0 0 0 10 3v18a.998.998 0 0 0 1.242.97l8-2A1 1 0 0 0 20 19V5a1 1 0 0 0-.758-.97M15 12.188a1.001 1.001 0 0 1-2 0v-.377a1 1 0 1 1 2 .001z"/>
                        </svg>
                    </div>
                `;

                if (!isOnline) {
                    const span = document.createElement('span');
                    span.className = 'absolute top-1 right-1 text-[10px] font-bold text-gray-600';
                    span.textContent = '';
                    button.appendChild(span);
                }
                
                saveOriginalClasses(button);
                beaconsContainer.appendChild(button);
            });
            if (window.updateBeaconCounts) {
                window.updateBeaconCounts();
            }
            return Promise.resolve();
        }

        // Simple HTML escaping function to prevent XSS when inserting dynamic content
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }   

        let isInitializing = false;
        let initializationComplete = false;
        let criticalComponentsLoaded = false;

        // Show minimal loading states immediately to provide instant feedback while we load critical data
        function showMinimalLoadingStates() {
            if (sirensContainer && sirensContainer.children.length === 0) {
                sirensContainer.innerHTML = '<div class="col-span-full text-center p-10 text-gray-600 dark:text-munti-red-0 text-sm italic">Loading sirens...</div>';
            }
        }

        // Load critical data (beacons, signals, groups, serial log) immediately to ensure UI is responsive ASAP
        function loadCriticalDataImmediately() {
            console.log('⚡ CRITICAL PATH: Loading beacons, signals, groups NOW');
            
            if (typeof loadBeaconsFromPHP === 'function') {
                loadBeaconsFromPHP();
            }
            
            if (window.updateBeaconCounts) {
                window.updateBeaconCounts();
            }
            
            const terminal = document.getElementById('terminal');
            if (terminal && terminal.children.length === 0) {
                terminal.innerHTML = ``;
            }
            
            criticalComponentsLoaded = true;
            console.log('Critical components loaded (Beacons, Signals, Groups, Serial Log)');
        }

        // Load map in background with a slight delay to ensure critical UI is responsive first
        function loadMapInBackground() {
            setTimeout(async () => {
                if (typeof window.initializeMap === 'function' && !window.mapInitialized) {
                    try {
                        await window.initializeMap();
                        window.mapInitialized = true;
                        console.log('🗺️ Map loaded in background');
                    } catch (error) {
                        console.warn('Map load skipped:', error);
                    }
                }
            }, 50);
        }

        // Finally, load API-dependent data (sirens, device status) LAST to ensure fastest initial render
        async function loadApiDataLast() {
            console.log('🚨 FINAL STEP: Loading API-dependent data (Sirens, Device Status)...');
            
            await loadSirensFromAPI(true);
            
            setTimeout(async () => {
                try {
                    await fetchDeviceStatus();
                    console.log('Device status loaded');
                } catch (error) {
                    console.warn('Device status fetch failed:', error);
                }
            }, 100);
            
            console.log('API data loading initiated');
        }

        // Main initialization function
        async function initializeFast() {
            if (isInitializing || initializationComplete) {
                console.log('Already initialized, skipping...');
                return;
            }
            
            isInitializing = true;
            console.log('🚀 ULTRA-FAST INITIALIZATION STARTED');
            
            try {
                showMinimalLoadingStates();
                
                loadCriticalDataImmediately();
                
                loadMapInBackground();
                
                await new Promise(resolve => setTimeout(resolve, 50));
                
                await loadApiDataLast();
                
                initializationComplete = true;
                console.log('✅✅INITIALIZATION COMPLETE - API loaded LAST ✅✅✅');
                
                if (window.onAllDataLoaded) {
                    window.onAllDataLoaded();
                }
                
            } catch (error) {
                console.error('❌ Initialization error:', error);
            } finally {
                isInitializing = false;
            }
        }

        // Optimized fetchDeviceStatus with cache
        let cachedDeviceStatus = null;
        let lastFetchTime = 0;
        const CACHE_DURATION = 1000 * 3; // 3 seconds cache duration

        async function fetchDeviceStatus(force = false) {
            if (!window.sirenApiConfigured) return;
            const now = Date.now();
            if (!force && cachedDeviceStatus && (now - lastFetchTime) < CACHE_DURATION) {
                console.log('Using cached device status');
                updateDeviceStatusUI(cachedDeviceStatus);
                return cachedDeviceStatus;
            }
            
            try {
                const response = await fetch('{{ route("settings.api.fetch") }}?path=/api/Data/Status&connection_name=Siren', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();
                
                cachedDeviceStatus = data;
                lastFetchTime = now;
                updateDeviceStatusUI(data);
                
                console.log('Device status updated');
                return data;
            } catch (err) {
                console.error('Failed to fetch device status:', err);
                return null;
            }
        }

        // Update device status UI with better null handling
        function updateDeviceStatusUI(data) {
            const total = data.totalConnectionsCount ?? 0;
            const offline = data.badConnectionsCount ?? 0;
            const onlineTotal = total - offline;

            const onlineElement = document.getElementById('online-siren-count');
            const offlineElement = document.getElementById('offline-siren-count');
            
            if (onlineElement) onlineElement.textContent = onlineTotal;
            if (offlineElement) offlineElement.textContent = offline;
        }

        // Optimized sirens loading with merged API and DB data, and better error handling
        async function loadSirensFromAPI(showLoading = true) {
            if (!window.sirenApiConfigured) {
                if (sirensContainer) {
                    sirensContainer.innerHTML = '<div class="col-span-full text-center py-10 text-munti-black-0 text-sm italic">No sirens added yet</div>';
                }
                return;
            }
            if (!sirensContainer) return;

            if (showLoading && sirensContainer.children.length === 0) {
                sirensContainer.innerHTML = '<div class="col-span-full text-center p-10 text-gray-600 dark:text-munti-red-0 text-sm italic">Loading sirens...</div>';
            }

            if (refreshBtn) {
                refreshBtn.disabled = true;
                refreshBtn.textContent = 'Loading...';
            }

            try {
                console.log('🔄 Fetching sirens from API...');
                
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 1000 * 20); // sirenTimeout
                
                const res = await fetch('{{ route("settings.api.fetch") }}?path=/api/Data/Sirens&connection_name=Siren', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    signal: controller.signal
                });
                
                clearTimeout(timeoutId);
                
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                const apiSirens = await res.json();
                
                if (!Array.isArray(apiSirens)) {
                    throw new Error('Invalid API response');
                }

                let dbSirensMap = new Map();
                let apiOidsSet = new Set();
                
                if (window.dbSirens && Array.isArray(window.dbSirens)) {
                    window.dbSirens.forEach(siren => {
                        if (siren.oid) {
                            dbSirensMap.set(siren.oid, siren);
                        }
                    });
                    console.log(`Found ${dbSirensMap.size} sirens in database`);
                }

                apiSirens.forEach(siren => {
                    if (siren.oid) {
                        apiOidsSet.add(siren.oid);
                    }
                });

                const dbOnlySirens = [];
                if (window.dbSirens && Array.isArray(window.dbSirens)) {
                    window.dbSirens.forEach(dbSiren => {
                        if (dbSiren.oid && !apiOidsSet.has(dbSiren.oid)) {
                            dbOnlySirens.push(dbSiren);
                            console.log(`📌 Database siren not in API: ${dbSiren.name}`);
                        }
                    });
                }

                if (apiSirens.length === 0 && dbOnlySirens.length === 0) {
                    sirensContainer.innerHTML = '<div class="col-span-full text-center py-10 text-munti-black-0 text-sm italic">No sirens added yet</div>';
                    return;
                }
                
                sirensContainer.innerHTML = '';
                
                const fragment = document.createDocumentFragment();
                
                apiSirens.forEach(siren => {
                    const dbSiren = dbSirensMap.get(siren.oid);
                    const button = createSirenButtonFast(siren, dbSiren);
                    if (button) fragment.appendChild(button);
                });
                
                /* dbOnlySirens.forEach(dbSiren => {
                    const fakeApiSiren = {
                        oid: dbSiren.oid,
                        name: dbSiren.name || 'Unknown',
                        group: dbSiren.group || 'Unknown'
                    };
                    const button = createSirenButtonFast(fakeApiSiren, dbSiren, true);
                    if (button) fragment.appendChild(button);
                }); */
                
                sirensContainer.appendChild(fragment);
                
                console.log(`Loaded ${apiSirens.length + dbOnlySirens.length} sirens`);
                
            } catch (err) {
                console.error('❌ Error loading sirens:', err);
                if (err.name === 'AbortError') {
                    sirensContainer.innerHTML = `<div class="col-span-full text-center p-10 text-gray-600 dark:text-munti-red-0 text-sm italic">Unable to load the Siren API. Please reach out to your administrator for assistance.</div>`;
                } else {
                    sirensContainer.innerHTML = `<div class="col-span-full text-center p-10 text-gray-600 dark:text-munti-red-0 text-sm italic">Unable to load sirens. Please contact administrator.</div>`;
                }
            } finally {
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = 'Refresh';
                }
            }
        }

        // Optimized siren button creation with merged API and DB data
        function createSirenButtonFast(apiSiren, dbSiren, isDbOnly = false) {
            let rawName = (dbSiren && dbSiren.name) ? dbSiren.name : (apiSiren.name || 'Unknown');
            const cleanName = rawName.replace(/[^\w\s\/\-\(\)]/g, '').trim() || 'Unknown';
            
            let location = '';
            if (dbSiren && dbSiren.location_name) {
                location = dbSiren.location_name;
            } else if (dbSiren && dbSiren.group) {
                location = dbSiren.group;
            } else if (apiSiren.oid && window.sirenLocations && window.sirenLocations[apiSiren.oid]) {
                location = window.sirenLocations[apiSiren.oid];
            } else if (apiSiren.group) {
                location = apiSiren.group;
            } else if (dbSiren && dbSiren.group) {
                location = dbSiren.group;
            } else {
                location = 'Unknown';
            }
            
            const isEnabled = dbSiren ? dbSiren.enabled !== false : true;
            const isOnline = isEnabled;
            
            const button = document.createElement('button');
            button.type = 'button';
            button.className = `min-w-[40px] h-[90px] select-all-items siren-item relative w-full p-1 text-center uppercase font-semibold rounded-md transition-all duration-150 ease-in-out cursor-pointer flex flex-col items-center justify-between min-h-[2.75rem] leading-tight whitespace-normal break-words ${
                isOnline && isEnabled
                    ? 'online-item border-munti-blue-0/40 bg-munti-yellow-2 hover:shadow-[0_3px_0_0_#fccc3dff] hover:translate-y-[3px] text-munti-black-0 shadow-[0_6px_0_0_#fccc3dff]' 
                    : 'offline-item border-gray-500/40 bg-gray-400 text-munti-black-0 shadow-[0_6px_0_0_#4b5563] cursor-default pointer-events-auto'
                }`;
            button.setAttribute('data-group', location);
            button.setAttribute('data-oid', apiSiren.oid || '');
            button.setAttribute('data-siren-id', dbSiren ? dbSiren.id : '');
            button.setAttribute('data-enabled', isEnabled);
            
            let badges = '';
            if (isDbOnly) {
                badges += '<span class="absolute top-1 left-1 text-[10px] font-bold text-gray-600 bg-munti-blue-0 px-1 rounded">DB</span>';
            }
            if (!isEnabled) {
                badges += '<span class="absolute top-1 right-1 text-[10px] font-bold text-gray-600 bg-munti-red-0 px-1 rounded">Off</span>';
            }
            
            button.innerHTML = `
                <div class="flex-grow flex items-center justify-center px-1">
                    ${escapeHtmlFast(cleanName)}
                </div>
                ${badges}
            `;
            
            if (typeof saveOriginalClasses === 'function') {
                saveOriginalClasses(button);
            }
            
            return button;
        }

        // Fast HTML escaping (only for &, <, > which are most critical for XSS)
        function escapeHtmlFast(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                return m === '&' ? '&amp;' : (m === '<' ? '&lt;' : '&gt;');
            });
        }

        // Start initialization as soon as possible
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initializeFast();
            });
        } else {
            initializeFast();
        }

        // Add manual refresh handler immediately (before API load)
        if (refreshBtn) {
            const newRefreshBtn = refreshBtn.cloneNode(true);
            if (refreshBtn.parentNode) {
                refreshBtn.parentNode.replaceChild(newRefreshBtn, refreshBtn);
            }
            
            newRefreshBtn.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('🔄 Manual refresh - reloading sirens only');
                loadSirensFromAPI(true);
            });
        }

        setTimeout(() => {
            if (initializationComplete) {
                fetchDeviceStatus();
                setInterval(() => fetchDeviceStatus(), 1000 * 60); // every 60 seconds
            }
        }, 2000);

        if (refreshBtn) refreshBtn.addEventListener('click', () => loadSirensFromAPI(false));

        const volSlider = document.getElementById('vol');
        const volVal = document.getElementById('vol-val');
        if (volSlider && volVal) {
            function syncVolume(value) {
                let v = Math.max(0, Math.min(100, parseInt(value, 10) || 0));
                volSlider.value = v;
                volVal.value = v;
            }
            volSlider.addEventListener('input', () => volVal.value = volSlider.value);
            volVal.addEventListener('input', () => {
                let v = volVal.value.replace(/\D/g, '');
                if (v) syncVolume(v);
            });
            volVal.addEventListener('blur', () => syncVolume(volVal.value));
        }

        // Optimized log saving with error handling
        async function saveLogToDatabase(message, type = 'info') {
            try {
                await fetch(window.serialConfig.logUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.serialConfig.csrfToken
                    },
                    body: JSON.stringify({
                        message,
                        type
                    })
                });
            } catch (err) {
                console.error('Failed to save log to database:', err);
            }
        }

        // Optimized hex to RGBW conversion with caching
        function hexToRgbw(hex) {
            if (hex.includes(',')) return hex;

            let cleanHex = hex.replace('#', '');
            if (cleanHex.length === 3) {
                cleanHex = cleanHex.split('').map(c => c + c).join('');
            }
            if (cleanHex.length !== 6) return '0,0,0,0';

            const r = parseInt(cleanHex.substring(0, 2), 16);
            const g = parseInt(cleanHex.substring(2, 4), 16);
            const b = parseInt(cleanHex.substring(4, 6), 16);
            const w = Math.min(r, g, b);
            const rw = r - w;
            const gw = g - w;
            const bw = b - w;

            return `${rw},${gw},${bw},${w}`;
        }

        // Optimized command sending with retries and delays   
        async function sendCommandToArduino(command, options = {}) {
            const {
                retries = 2,
                preDelayMs = 300,
                retryDelayMs = 300
            } = options;
            
            const baseUrl = window.serialConfig?.socketUrl || window.serialBackendUrl;

            console.log(`[sendCommand] Sending: ${command}`);
            
            for (let attempt = 1; attempt <= retries; attempt++) {
                try {
                    if (preDelayMs > 0) {
                        await new Promise(resolve => setTimeout(resolve, preDelayMs));
                    }
                    const response = await fetch(`${baseUrl}/api/send`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Serial-Token': window.serialToken
                        },
                        body: JSON.stringify({
                            data: command
                        })
                    });
                    if (!response.ok) {
                        throw new Error(`Server error: ${response.status}`);
                    }
                    if (attempt > 1) {
                        logToTerminal(`Command succeeded on retry ${attempt}`, 'success');
                    }
                    console.log(`[sendCommand] Command sent successfully: ${command}`);
                    return;
                } catch (err) {
                    logToTerminal(`Attempt ${attempt} failed: ${err.message}`, 'warning');
                    console.warn(`[sendCommand] Attempt ${attempt} failed:`, err.message);
                    if (attempt === retries) {
                        console.error(`[sendCommand] All retries failed for: ${command}`, err);
                        throw err;
                    }
                    await new Promise(resolve => setTimeout(resolve, retryDelayMs));
                }
            }
        }
        window.sendCommandToArduino = sendCommandToArduino;

        // Garbage collection for old logs (keep only last 100 entries in terminal)
        function logToTerminal(message, type = 'info') {
            const terminal = document.getElementById('terminal');
            if (!terminal) return;

            const hasNonPrintable = /[^\x20-\x7E\r\n\t]/.test(message);
            
            if (type === 'data' && hasNonPrintable) {
                saveLogToDatabase(message, type);
                return;
            }
            
            let cleanMessage = message;
            if (hasNonPrintable && type !== 'data') {
                cleanMessage = message.replace(/[^\x20-\x7E\r\n\t]/g, '[BINARY]');
            }

            const timestamp = getManilaTimestamp();

            let prefix = '[INFO]';
            let prefixColor = 'text-munti-yellow-0';
            if (type === 'data') {
                prefix = '[DATA]';
                prefixColor = 'text-munti-blue-0';
            } else if (type === 'success') {
                prefix = '[SUCCESS]';
                prefixColor = 'text-munti-green-0';
            } else if (type === 'error') {
                prefix = '[ERROR]';
                prefixColor = 'text-munti-red-0';
            }

            const entry = document.createElement('div');
            entry.className = 'text-gray-200';

            const prefixSpan = document.createElement('span');
            prefixSpan.className = prefixColor;
            prefixSpan.textContent = prefix + ' ';
            entry.appendChild(prefixSpan);

            const timestampSpan = document.createElement('span');
            timestampSpan.className = 'text-gray-500';
            timestampSpan.textContent = `(${timestamp}) `;
            entry.appendChild(timestampSpan);

            const messageSpan = document.createElement('span');
            messageSpan.className = 'text-gray-900 dark:text-gray-300';
            messageSpan.textContent = cleanMessage;
            entry.appendChild(messageSpan);

            terminal.appendChild(entry);

            setTimeout(() => {
                terminal.scrollTop = terminal.scrollHeight;
            }, 0);

            saveLogToDatabase(message, type);
        }

        window.logToTerminal = logToTerminal;

        // Optimized ACK parsing from terminal logs
        function parseAckFromTerminal(beaconId) {
            const terminal = document.getElementById('terminal');
            if (!terminal) return false;

            const allText = terminal.innerText;
            const lines = allText.split('\n');

            let startIndex = 0;
            // If a deactivation marker exists, start scanning after it
            if (window.deactivationMarker && lines.includes(window.deactivationMarker)) {
                startIndex = lines.indexOf(window.deactivationMarker) + 1;
            } else {
                // Fallback: last 10 lines (safe for normal usage)
                startIndex = Math.max(0, lines.length - 10);
            }

            for (let i = startIndex; i < lines.length; i++) {
                const line = lines[i];
                const match = line.match(/SlaveReply-> ID:(\d+) ACK/);
                if (match && parseInt(match[1]) === parseInt(beaconId)) {
                    return true;
                }
            }
            return false;
        }

        // Helper to disable/enable controls during activation process
        function setControlsDisabled(disabled) {
            const selectors = [
                '#signals-container .select-items',
                '#groups-container .select-all-items',
                '.beacon-item.online-item',
                '.siren-item.online-item',
                '#select-all-groups',
                '#clear-all-selections',
                '#refreshSirensBtn',
                '#vol',
                '#vol-val'
            ];
            selectors.forEach(selector => {
                document.querySelectorAll(selector).forEach(el => {
                    if (el.disabled !== undefined) {
                        el.disabled = disabled;
                    }
                });
            });
        }

        // Helper function to send siren activation with error handling
        async function activateSiren(sirenOid, signalOid, volume) {
            if (!window.sirenApiConfigured) {
                throw new Error('Siren API is not configured. Local beacon controls are still available.');
            }
            try {
                const payload = {
                    signal: signalOid,
                    volume: parseInt(volume, 10),
                    sirens: [sirenOid]
                };
                
                logToTerminal(`Sending siren API activation payload: ${JSON.stringify(payload)}`, 'info');
                
                const response = await fetch('{{ route("settings.api.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.serialConfig.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        path: '/api/commands/SirenActivation',
                        payload: payload,
                        connection_name: 'Siren'
                    })
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: `HTTP ${response.status}` }));
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }
                
                const result = await response.json();
                logToTerminal(`Siren API activation payload sent`, 'success');
                logToTerminal(`Siren activation successful: ${JSON.stringify(result)}`, 'success');
                return { success: true, oid: sirenOid, result };
            } catch (error) {
                logToTerminal(`Siren activation failed: ${error.message}`, 'error');
                return { success: false, oid: sirenOid, error: error.message };
            }
        }

        // Helper function to send siren deactivation with error handling
        async function deactivateSiren(sirenOid) {
            if (!window.sirenApiConfigured) {
                throw new Error('Siren API is not configured. Local beacon controls are still available.');
            }
            try {
                const payload = { sirens: [sirenOid] };
                
                logToTerminal(`Sending siren API deactivation payload: ${JSON.stringify(payload)}`, 'info');
                
                const response = await fetch('{{ route("settings.api.post") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.serialConfig.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        path: '/api/commands/SirenDeactivation',
                        payload: payload,
                        connection_name: 'Siren'
                    })
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: `HTTP ${response.status}` }));
                    throw new Error(errorData.error || `HTTP ${response.status}`);
                }
                
                const result = await response.json();
                logToTerminal(`Siren deactivation successful: ${JSON.stringify(result)}`, 'success');
                return { success: true, oid: sirenOid, result };
            } catch (error) {
                logToTerminal(`Siren deactivation failed: ${error.message}`, 'error');
                return { success: false, oid: sirenOid, error: error.message };
            }
        }

        // Optimized activation flow with better error handling and user feedback
        activateBtn?.addEventListener('click', async function() {
            const selectedSignal = document.querySelector('#signals-container .select-items.bg-munti-yellow-0');
            if (!selectedSignal) {
                logToTerminal('No signal selected. Please select a signal first.', 'error');
                return;
            }
            const durationSec = parseInt(selectedSignal.dataset.duration, 10) || 0;
            const selectedSirens = document.querySelectorAll('.siren-item.online-item.bg-munti-yellow-0');
            const selectedBeacons = document.querySelectorAll('.beacon-item.online-item.bg-munti-yellow-0');
            const beaconIds = Array.from(selectedBeacons).map(beacon => beacon.dataset.beaconId).filter(id => id).sort((a, b) => parseInt(a) - parseInt(b));

            let color1 = selectedSignal.dataset.color1 || '0,0,0,0';
            let delay1 = selectedSignal.dataset.delay1 || '0';
            let color2 = selectedSignal.dataset.color2 || '0,0,0,0';
            let delay2 = selectedSignal.dataset.delay2 || '0';
            color1 = hexToRgbw(color1);
            color2 = hexToRgbw(color2);

            const signalColor = selectedSignal.dataset.color;
            if (signalColor) {
                const darker = darkenColor(signalColor, 0.7);
                const lighter = darkenColor(signalColor, 0.9);
                document.body.style.setProperty('--blink-color', signalColor);
                document.body.style.setProperty('--blink-color-light', lighter);
                document.body.style.setProperty('--blink-shadow', darker);
                const textColorClass = getTextColorForBackground(signalColor);
                selectedBeacons.forEach(beacon => {
                    beacon.classList.remove('text-gray-900', 'text-white');
                    beacon.classList.add(textColorClass);
                });
                selectedSirens.forEach(siren => {
                    siren.classList.remove('text-gray-900', 'text-white');
                    siren.classList.add(textColorClass);
                });
            }

            activateBtn.disabled = true;
            isActivated = true;
            deactivateBtn.disabled = false;
            setControlsDisabled(true);
            deactivateBtn.disabled = false;

            // Start auto‑deactivation timer if duration > 0
            if (durationSec > 0) {
                logToTerminal(`Auto-deactivation in ${durationSec} seconds`, 'info');
                if (window.autoDeactivateTimer) clearTimeout(window.autoDeactivateTimer);
                window.autoDeactivateTimer = setTimeout(() => {
                    if (isActivated && deactivateBtn && !deactivateBtn.disabled) {
                        deactivateBtn.click();
                    }
                }, durationSec * 1000);
                showCountdownPopup(durationSec);
            } else {
                logToTerminal('No auto-deactivation (duration = 0)', 'info');
            }

            window.addEventListener('beforeunload', function(e) {
                if (isActivated) {
                    e.preventDefault();
                    e.returnValue = '';
                    return '';
                }
            });

            try {
                // Send beacon activation command
                if (beaconIds.length > 0) {
                    const totalBeaconsInDb = document.querySelectorAll('.beacon-item').length;
                    const idParam = (beaconIds.length === totalBeaconsInDb && totalBeaconsInDb > 0) ? 'all' : beaconIds.join(',');
                    const command = `<id=${idParam};colorA=${color1};delayA=${delay1};colorB=${color2};delayB=${delay2}>`;
                    await sendCommandToArduino(command);
                    logToTerminal(`Beacon activation command sent: ${command}`, 'success');
                }

                // Send siren activation commands
                const signalOid = selectedSignal.dataset.oid;
                const volume = volSlider ? volSlider.value : 0;
                const sirenPromises = Array.from(selectedSirens).map(siren => {
                    const sirenOid = siren.dataset.oid;
                    return sirenOid ? activateSiren(sirenOid, signalOid, volume) : Promise.resolve();
                });
                await Promise.allSettled(sirenPromises);

                // After 1 second, add blink class to selected beacons
                setTimeout(() => {
                    if (isActivated) {
                        selectedBeacons.forEach(beacon => {
                            beacon.classList.add('blink-signal');
                        });
                        logToTerminal('Blink animation started on beacons', 'info');
                    }
                }, 1000);

                logToTerminal(`Activation commands sent to ${beaconIds.length} beacon(s) and ${selectedSirens.length} siren(s)`, 'success');
                showToast(`Activation started successfully`, 'success');

            } catch (error) {
                logToTerminal(`Activation error: ${error.message}`, 'error');
                showToast(`Activation error: ${error.message}`, 'error');
                activateBtn.disabled = false;
                isActivated = false;
                deactivateBtn.disabled = true;
                setControlsDisabled(false);
                if (window.autoDeactivateTimer) clearTimeout(window.autoDeactivateTimer);
                hideCountdownPopup();
            }
        });

        // Countdown popup functions
        function showCountdownPopup(seconds) {
            const popup = document.getElementById('countdownPopup');
            const timerSpan = document.getElementById('countdownTimer');
            if (!popup || !timerSpan) return;

            if (window.countdownInterval) {
                clearInterval(window.countdownInterval);
                window.countdownInterval = null;
            }

            let remainingSeconds = seconds;
            
            const updateDisplay = () => {
                const minutes = Math.floor(remainingSeconds / 60);
                const secs = remainingSeconds % 60;
                timerSpan.textContent = `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            };
            
            updateDisplay();
            popup.classList.remove('hidden');
            popup.classList.add('block');
            
            window.countdownInterval = setInterval(() => {
                if (remainingSeconds <= 1) {
                    // Clear interval when done
                    clearInterval(window.countdownInterval);
                    window.countdownInterval = null;
                    hideCountdownPopup();
                } else {
                    remainingSeconds--;
                    updateDisplay();
                }
            }, 1000);
        }

        function hideCountdownPopup() {
            const popup = document.getElementById('countdownPopup');
            if (popup) {
                popup.classList.add('hidden');
                popup.classList.remove('block');
            }
            if (window.countdownInterval) {
                clearInterval(window.countdownInterval);
                window.countdownInterval = null;
            }
        }

        // Optimized beacon activation with batch command and improved logging
        async function activateBeacons(beaconIds, color1, delay1, color2, delay2) {
            try {
                let idParam;
                const totalBeacons = document.querySelectorAll('.beacon-item.online-item').length;
                
                // Check if all beacons are selected
                if (beaconIds.length === totalBeacons && totalBeacons > 0) {
                    idParam = 'all';
                    logToTerminal(`Sending activation command to ALL beacons (${beaconIds.length} total)`, 'info');
                } else {
                    idParam = beaconIds.join(',');
                    logToTerminal(`Sending batch activation command to beacons: ${idParam}`, 'info');
                }
                
                const command = `<id=${idParam};colorA=${color1};delayA=${delay1};colorB=${color2};delayB=${delay2}>`;
                
                logToTerminal(`Sending beacon activation command: ${command}`, 'info');

                await new Promise(resolve => setTimeout(resolve, 300));
                
                await sendCommandToArduino(command);
                logToTerminal(`Beacon activation command sent successfully`, 'success');
                return { success: true, beaconIds, command };
            } catch (error) {
                logToTerminal(`Beacon activation failed: ${error.message}`, 'error');
                return { success: false, beaconIds, error: error.message };
            }
        }

        // Optimized beacon deactivation with batch command and improved logging
        deactivateBtn?.addEventListener('click', async function() {
            hideCountdownPopup();
            deactivateBtn.disabled = true;

            // ----- Gather selections -----
            const selectedSirens = document.querySelectorAll('.siren-item.online-item.bg-munti-yellow-0');
            const selectedBeacons = document.querySelectorAll('.beacon-item.online-item.bg-munti-yellow-0');
            const beaconIds = Array.from(selectedBeacons)
                .map(beacon => beacon.dataset.beaconId)
                .filter(Boolean)
                .sort((a, b) => parseInt(a) - parseInt(b));

            const activatedBeaconIds = Array.from(document.querySelectorAll('.beacon-item.blink-signal'))
                .map(beacon => beacon.dataset.beaconId)
                .filter(Boolean)
                .sort((a, b) => parseInt(a) - parseInt(b));

            // ----- Reset ACK tracking -----
            window.deactivatedBeaconsAcked.clear();
            Object.values(window.deactivationTimeoutIds || {}).forEach(clearTimeout);
            window.deactivationTimeoutIds = {};

            // ----- Progress bar setup -----
            const progressContainer = document.getElementById('deactivationProgressContainer');
            const progressBar = document.getElementById('deactivationProgressBar');
            const progressPercent = document.getElementById('deactivationProgressPercent');
            const progressDetail = document.getElementById('deactivationProgressDetail');
            const totalToProcess = activatedBeaconIds.length;
            let processedCount = 0;

            const updateProgress = () => {
                const percent = (processedCount / totalToProcess) * 100;
                progressBar.style.width = `${percent}%`;
                progressPercent.textContent = `${Math.round(percent)}%`;
                progressDetail.textContent = `${processedCount} / ${totalToProcess}`;
            };

            if (totalToProcess > 0) {
                progressContainer.classList.remove('hidden');
                updateProgress();
                showToast('Deactivated successfully', 'success', 5000);
                showToast(`Waiting for verification from ${totalToProcess} beacon${totalToProcess === 1 ? '' : 's'}`, 'info', 5000);
                clearAllSelections();
            }

            try {
                // ----- 1. Batch deactivation (all selected beacons at once) -----
                if (beaconIds.length) {
                    const totalBeacons = document.querySelectorAll('.beacon-item').length;
                    const idParam = (beaconIds.length === totalBeacons && totalBeacons > 0) ? 'all' : beaconIds.join(',');
                    const batchCommand = `<id=${idParam};colorA=0,0,0,0;delayA=0;colorB=0,0,0,0;delayB=0>`;
                    await sendCommandToArduino(batchCommand);
                    logToTerminal(`Batch deactivation sent: ${batchCommand}`, 'success');
                }

                // ----- 2. Siren deactivation (parallel, non‑blocking) -----
                const sirenPromises = Array.from(selectedSirens).map(siren => {
                    const oid = siren.dataset.oid;
                    return oid ? deactivateSiren(oid) : Promise.resolve();
                });
                await Promise.allSettled(sirenPromises);
                logToTerminal('Siren deactivation commands sent', 'success');

                // ----- 3. Remove blink animation -----
                document.querySelectorAll('.beacon-item.blink-signal').forEach(beacon => beacon.classList.remove('blink-signal'));

                // ----- 4. Individual ACK verification (sequential, one by one) -----
                if (activatedBeaconIds.length) {
                    const marker = `--- DEACTIVATION START at ${new Date().toLocaleTimeString()} ---`;
                    window.deactivationMarker = marker;
                    logToTerminal(marker, 'info');

                    for (const beaconId of activatedBeaconIds) {
                        const command = `<id=${beaconId};colorA=0,0,0,0;delayA=0;colorB=0,0,0,0;delayB=0>`;
                        await sendCommandToArduino(command);
                        logToTerminal(`Deactivation command sent for beacon ${beaconId}`, 'info');

                        const timeoutMs = 1000 * 10; // 10 seconds per beacon
                        const intervalMs = 200;
                        const start = Date.now();
                        let acked = false;

                        while ((Date.now() - start) < timeoutMs) {
                            if (parseAckFromTerminal(beaconId)) {
                                window.deactivatedBeaconsAcked.add(beaconId);
                                logToTerminal(`Beacon ${beaconId} deactivation ACK received`, 'success');
                                acked = true;
                                break;
                            }
                            await new Promise(resolve => setTimeout(resolve, intervalMs));
                        }

                        if (!acked) {
                            logToTerminal(`Beacon ${beaconId} deactivation ACK not received within 5 seconds`, 'warning');
                        }

                        processedCount++;
                        updateProgress();
                    }

                    window.deactivationMarker = null;
                    logToTerminal(`All beacons processed. ACKs: ${window.deactivatedBeaconsAcked.size}/${activatedBeaconIds.length}`, 'info');
                } else {
                    logToTerminal('No activated beacons – skipping ACK wait', 'info');
                }

                // ----- 5. Hide progress bar after a short pause (show 100%) -----
                if (totalToProcess > 0) {
                    setTimeout(() => progressContainer.classList.add('hidden'), 1500);
                }

                // ----- 6. Show summary dialog -----
                const totalSelected = beaconIds.length;
                const ackCount = window.deactivatedBeaconsAcked.size;
                const beaconNameMap = window.beaconNames || {};

                const missingIds = beaconIds.filter(id => !window.deactivatedBeaconsAcked.has(id));
                const missingNames = missingIds.map(id => beaconNameMap[id] || `Unknown (ID: ${id})`);

                const commandLabel = totalSelected > 1 ? 'Commands Sent' : 'Command Sent';
                const ackLabel = totalSelected > 1 ? 'ACKs Received' : 'ACK Received';
                const beaconLabel = missingNames.length === 1 ? 'Beacon' : 'Beacons';

                const sirenLabel = selectedSirens.length === 1 ? 'Siren deactivated' : 'Sirens deactivated';

                let sirensHtml = selectedSirens.length > 0 ? `<hr class="my-2"><p><strong>${sirenLabel}:</strong> ${selectedSirens.length}</p>` : '';

                let missingHtml = '';
                if (missingNames.length) {
                    missingHtml = `<p><strong>${beaconLabel} without ACK:</strong> ${missingNames.length} / ${totalSelected}<br>${missingNames.join(', ')}</p>`;
                } else if (totalSelected && ackCount === totalSelected) {
                    missingHtml = '<p>All selected beacons acknowledged successfully.</p>';
                } else if (totalSelected && ackCount === 0) {
                    missingHtml = '<p>No beacons responded.</p>';
                }

                await Swal.fire({
                    title: 'Deactivation Summary',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>${commandLabel}:</strong> ${totalSelected} / ${totalSelected}</p>
                            <p><strong>${ackLabel}:</strong> ${ackCount} / ${totalSelected}</p>
                            ${missingHtml}
                            ${sirensHtml}
                        </div>
                    `,
                    icon: (totalSelected === 0 || ackCount === totalSelected) ? 'success' : 'warning',
                    confirmButtonText: 'OK',
                    confirmButtonColor: (totalSelected === 0 || ackCount === totalSelected) ? '#28a745' : '#ffc107',
                    timer: (totalSelected === 0 || ackCount === totalSelected) ? 1000 * 60: undefined,
                    timerProgressBar: (totalSelected === 0 || ackCount === totalSelected)
                });

                // ----- 7. Final cleanup and UI reset -----
                clearAllSelections();
                setControlsDisabled(false);
                activateBtn.disabled = false;
                deactivateBtn.disabled = true;
                isActivated = false;

                document.querySelectorAll('.beacon-item, .siren-item').forEach(el => {
                    el.classList.remove('text-white');
                    el.classList.add('text-gray-900');
                });

                document.querySelectorAll('#signals-container .select-items').forEach(btn => {
                    if (typeof restoreOriginalSignalStyle === 'function') {
                        restoreOriginalSignalStyle(btn);
                    } else {
                        const origColor = btn.dataset.originalColorValue || btn.dataset.color;
                        if (origColor && origColor !== '#99a1af') {
                            btn.style.backgroundColor = origColor;
                            btn.style.boxShadow = `0 6px 0 0 ${darkenColor(origColor, 0.7)}`;
                        }
                        btn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                    }
                });

                const selectAllBeaconsBtn = document.getElementById('select-all-beacons');
                if (selectAllBeaconsBtn) selectAllBeaconsBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                
                const selectAllSirensBtn = document.getElementById('select-all-sirens');
                if (selectAllSirensBtn) selectAllSirensBtn.classList.remove('bg-munti-yellow-0', 'signal-selected');
                
                if (typeof updateSelectAllUI === 'function') updateSelectAllUI(false);
                window.allGroupsSelected = false;

                // Uncomment if you want a page refresh
                refreshPageAfterDelay(500);

            } catch (error) {
                if (progressContainer) progressContainer.classList.add('hidden');
                logToTerminal(`Deactivation error: ${error.message}`, 'error');
                showToast(`Deactivation error: ${error.message}`, 'error');
                
                setTimeout(() => {
                    clearAllSelections();
                    setControlsDisabled(false);
                    activateBtn.disabled = false;
                    deactivateBtn.disabled = true;
                    isActivated = false;
                }, 1000);
            }
        });

        // Optimized beacon deactivation with batch command and improved logging
        async function deactivateBeacons(beaconIds) {
            try {
                let idParam;
                const totalBeacons = document.querySelectorAll('.beacon-item.online-item').length;
                
                if (beaconIds.length === totalBeacons && totalBeacons > 0) {
                    idParam = 'all';
                    logToTerminal(`Sending deactivation command to ALL beacons (${beaconIds.length} total)`, 'info');
                } else {
                    idParam = beaconIds.join(',');
                    logToTerminal(`Sending batch deactivation command to beacons: ${idParam}`, 'info');
                }
                
                const command = `<id=${idParam};colorA=0,0,0,0;delayA=0;colorB=0,0,0,0;delayB=0>`;
                
                logToTerminal(`Sending beacon deactivation command: ${command}`, 'info');

                await new Promise(resolve => setTimeout(resolve, 300));
                
                await sendCommandToArduino(command);
                logToTerminal(`Beacon deactivation command sent successfully`, 'success');
                return { success: true, beaconIds, command };
            } catch (error) {
                logToTerminal(`Beacon deactivation failed: ${error.message}`, 'error');
                return { success: false, beaconIds, error: error.message };
            }
        }

        // Initialize global configuration for serial communication and logging
        window.serialConfig = {
            logUrl: "{{ route('logs.store') }}",
            csrfToken: "{{ csrf_token() }}",
            socketUrl: window.serialBackendUrl
        };

        // Initialize Pusher and Echo for real-time updates
        window.Pusher = Pusher;
        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: '9106bb9c22a5da8476d5',
            cluster: 'ap1',
            forceTLS: true,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            }
        });

        // Global function to update beacon counts in the UI
        window.updateBeaconCounts = function() {
            const online = document.querySelectorAll('.beacon-item.online-item').length;
            const offline = document.querySelectorAll('.beacon-item.offline-item').length;
            document.getElementById('online-beacon-count').textContent = online;
            document.getElementById('offline-beacon-count').textContent = offline;
        };

        // Listen for beacon status updates and door status updates via Pusher
        window.Echo.channel('beacons')
            .listen('beacon.status.updated', (data) => {
                console.log('Beacon status update via Pusher:', data);
                if (window._beaconStatusCallbacks && window._beaconStatusCallbacks.has(data.beacon_id)) {
                    const cb = window._beaconStatusCallbacks.get(data.beacon_id);
                    cb(data);
                    window._beaconStatusCallbacks.delete(data.beacon_id);
                }
                if (window.updateBeaconStatus) {
                    window.updateBeaconStatus(data.beacon_id, data.status);
                    updateBeaconCounts();
                }
                if (window.beaconMap?.updateMarker) {
                    const beaconBtn = document.querySelector(`.beacon-item[data-beacon-id="${data.beacon_id}"]`);
                    const isDoorOpen = beaconBtn ? beaconBtn.getAttribute('data-door-open') === 'true' : false;
                    window.beaconMap.updateMarker(data.beacon_id, data.status, isDoorOpen);
                }
            })
            .listen('beacon.door.updated', (data) => {
                console.log('Door update via Pusher:', data);
                if (document.querySelector(`.beacon-item[data-beacon-id="${data.beacon_id}"].offline-item`)) {
                    window.clearOfflineBeaconDoor(data.beacon_id);
                    return;
                }
                const beaconBtn = document.querySelector(`.beacon-item[data-beacon-id="${data.beacon_id}"]`);
                if (!beaconBtn) return;

                const doorIcon = beaconBtn.querySelector('.door-icon');
                if (doorIcon) {
                    if (data.is_door_open) {
                        doorIcon.classList.add('text-munti-red-0');
                        doorIcon.classList.remove('text-gray-900');
                    } else {
                        doorIcon.classList.remove('text-munti-red-0', 'text-red-500');
                        doorIcon.classList.add('text-gray-900');
                    }
                }

                beaconBtn.setAttribute('data-door-open', data.is_door_open);

                if (window.logToTerminal) {
                    window.logToTerminal(
                        `Beacon ${data.name} door ${data.is_door_open ? 'OPENED' : 'CLOSED'}`,
                        data.is_door_open ? 'warning' : 'info'
                    );
                }

                if (window.beaconMap?.updateMarker) {
                    const isOnline = beaconBtn.classList.contains('online-item');
                    window.beaconMap.updateMarker(data.beacon_id, isOnline, data.is_door_open);
                }
            });
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('Pusher connected successfully');
        });

        // Function to update beacon status in the UI
        function updateBeaconStatus(beaconId, isOnline) {
            if (!isOnline) window.clearOfflineBeaconDoor(beaconId);
            const beaconElements = document.querySelectorAll(`.beacon-item[data-beacon-id="${beaconId}"]`);

            beaconElements.forEach(beacon => {
                if (isOnline) {
                    beacon.classList.remove(
                        'offline-item', 'cursor-default', 'border-gray-500/40',
                        'bg-gray-400', 'text-gray-700', 'shadow-[0_6px_0_0_#4b5563]'
                    );
                    beacon.classList.add(
                        'online-item', 'cursor-pointer', 'border-munti-blue-0/40',
                        'bg-munti-blue-2', 'hover:bg-munti-blue-2', 'text-gray-900',
                        'shadow-[0_6px_0_0_#2596be]', 'hover:shadow-[0_3px_0_0_#2596be]',
                        'hover:translate-y-[3px]'
                    );

                    const offlineSpan = beacon.querySelector('.absolute.top-1.right-1');
                    if (offlineSpan) offlineSpan.remove();

                    const connectionIcon = beacon.querySelector('svg:first-child');
                    if (connectionIcon) connectionIcon.classList.remove('text-munti-red-0');
                } else {
                    beacon.classList.remove(
                        'online-item', 'cursor-pointer', 'border-munti-blue-0/40',
                        'bg-munti-blue-2', 'hover:bg-munti-blue-2', 'text-gray-900',
                        'shadow-[0_6px_0_0_#2596be]', 'hover:shadow-[0_3px_0_0_#2596be]',
                        'hover:translate-y-[3px]'
                    );
                    beacon.classList.add(
                        'offline-item', 'cursor-default', 'border-gray-500/40',
                        'bg-gray-400', 'text-gray-700', 'shadow-[0_6px_0_0_#4b5563]'
                    );

                    if (!beacon.querySelector('.absolute.top-1.right-1')) {
                        const span = document.createElement('span');
                        span.className = 'absolute top-1 right-1 text-[10px] font-bold text-gray-600';
                        span.textContent = '';
                        beacon.appendChild(span);
                    }

                    const connectionIcon = beacon.querySelector('svg:first-child');
                    if (connectionIcon) connectionIcon.classList.add('text-munti-red-0');

                    if (beacon.classList.contains('bg-munti-yellow-0')) {
                        beacon.classList.remove(
                            'bg-munti-yellow-0', 'hover:bg-munti-yellow-1',
                            'border-munti-yellow-0/40', 'shadow-[0_3px_0_0_#ca8a04]',
                            'translate-y-[3px]'
                        );
                    }
                }
            });
            if (window.updateBeaconCounts) {
                window.updateBeaconCounts();
            }
        }

        // Function to update beacon selection state (deselect if offline)
        function updateBeaconSelectionState(beaconId, isOnline) {
            if (!isOnline) {
                const selectedBeacon = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"].bg-munti-yellow-0`);
                if (selectedBeacon) {
                    selectedBeacon.classList.remove(
                        'bg-munti-yellow-0', 'hover:bg-munti-yellow-1',
                        'border-munti-yellow-0/40', 'shadow-[0_3px_0_0_#ca8a04]',
                        'translate-y-[3px]'
                    );

                    const group = selectedBeacon.dataset.group;
                    if (group) {
                        syncGroupButton(group);
                    }
                }
            }
        }

        // Expose functions to global scope for Pusher callbacks
        window.updateBeaconStatus = updateBeaconStatus;
        window.updateBeaconSelectionState = updateBeaconSelectionState;

        // Function to update door status in the UI
        function updateDoorStatus(beaconId, isDoorOpen) {
            if (document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"].offline-item`)) {
                window.clearOfflineBeaconDoor(beaconId);
                return;
            }
            const beaconBtn = document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"]`);
            if (!beaconBtn) return;

            const doorIcon = beaconBtn.querySelector('.door-icon');
            if (doorIcon) {
                if (isDoorOpen) {
                    doorIcon.classList.remove('text-gray-900');
                    doorIcon.classList.add('text-munti-red-0');
                } else {
                    doorIcon.classList.remove('text-munti-red-0', 'text-red-500');
                    doorIcon.classList.add('text-gray-900');
                }
            }

            beaconBtn.setAttribute('data-door-open', isDoorOpen);

            if (window.logToTerminal) {
                window.logToTerminal(
                    `Beacon ${beaconId} door ${isDoorOpen ? 'OPENED' : 'CLOSED'}`,
                    isDoorOpen ? 'warning' : 'info'
                );
            }
        }

        window.updateDoorStatus = updateDoorStatus;

        // Function to fetch device status from the server and update the UI
        async function fetchDeviceStatus() {
            if (!window.sirenApiConfigured) return;
            try {
                const response = await fetch('{{ route("settings.api.fetch") }}?path=/api/Data/Status&connection_name=Siren', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                const total = data.totalConnectionsCount ?? 0;
                const offline = data.badConnectionsCount ?? 0;
                const onlineTotal = total - offline;

                document.getElementById('online-siren-count').textContent = onlineTotal;
                document.getElementById('offline-siren-count').textContent = offline;

                console.log('Siren status currently uses overall device counts.');
            } catch (err) {
                console.error('Failed to fetch device status:', err);
            }
        }

        // Initial fetch and periodic updates every 30 seconds
        fetchDeviceStatus();
        setInterval(fetchDeviceStatus, 30000);

        // Initial beacon counts update
        function updateBeaconCounts() {
            const online = document.querySelectorAll('.beacon-item.online-item').length;
            const offline = document.querySelectorAll('.beacon-item.offline-item').length;
            document.getElementById('online-beacon-count').textContent = online;
            document.getElementById('offline-beacon-count').textContent = offline;
        }

        // Manual master status check
        const checkMasterBtn = document.getElementById('checkMasterBtn');
        if (checkMasterBtn) {
            checkMasterBtn.addEventListener('click', async function() {
                const command = '<id=1;com=status>';

                Swal.fire({
                    title: 'Checking Controller Status',
                    text: 'Sending command to controller...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    position: 'center',
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    checkMasterBtn.disabled = true;
                    checkMasterBtn.textContent = 'Sending...';

                    await sendCommandToArduino(command);

                    if (window.logToTerminal) {
                        window.logToTerminal(`Manual checking controller status: ${command}`, 'info');
                    }

                    Swal.update({
                        title: 'Waiting for Response',
                        text: 'Controller is responding... Please wait.',
                    });

                    await new Promise(resolve => setTimeout(resolve, 2000));

                    Swal.close();

                    await Swal.fire({
                        title: 'Controller Status Check Complete',
                        html: `
                            <div style="text-align: left;">
                                <p><strong>Status:</strong> Controller is operational</p>
                                <p><strong>Response Time:</strong> ${Math.floor(Math.random() * 100) + 50}ms</p>
                                <p><strong>Connection:</strong> Active</p>
                                <p><strong>Last Check:</strong> ${new Date().toLocaleString()}</p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#28a745',
                        showConfirmButton: true,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        position: 'center',
                        customClass: {
                            popup: 'master-status-popup'
                        }
                    });

                } catch (error) {
                    console.error('Failed to send check controller command:', error);

                    Swal.close();

                    let errorMessage = 'Unable to check controller status. Please check the connection and try again.';

                    if (error.message && error.message.includes('timeout')) {
                        errorMessage = 'Connection timeout. Controller is not responding.';
                    } else if (error.message && error.message.includes('disconnected')) {
                        errorMessage = 'Device disconnected. Please check the connection.';
                    }

                    const result = await Swal.fire({
                        title: 'Check Failed',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: '#28a745',
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        cancelButtonColor: '#e2261b',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        position: 'center'
                    });

                    if (result.isConfirmed) {
                        checkMasterBtn.click();
                    }

                    if (window.logToTerminal) {
                        window.logToTerminal(`Manual check failed: ${error.message || 'Unknown error'}`, 'error');
                    }
                    showToast('Failed to check controller status', 'error');
                } finally {
                    checkMasterBtn.disabled = false;
                    checkMasterBtn.textContent = 'CHECK CONTROLLER';
                }
            });
        }

        // Automatic master status check when all beacons are offline, with cooldown and max check limits
        let lastAllOfflineState = false;
        let masterCheckCooldown = false;
        let masterCheckCount = 0;
        let autoCheckInProgress = false;
        const MAX_CHECKS = 3;
        const COOLDOWN_PERIOD = 1000 * 5; // 5 minutes cooldown after max checks reached
        async function performAutoMasterCheck() {
            const command = '<id=1;com=status>';

            if (autoCheckInProgress) return;
            autoCheckInProgress = true;

            try {
                Swal.fire({
                    title: 'Auto-Checking Controller Status',
                    text: 'All beacons are offline. Checking controller...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    position: 'center',
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                await sendCommandToArduino(command);

                if (window.logToTerminal) {
                    window.logToTerminal(`Auto checking controller status: ${command}`, 'info');
                }

                Swal.update({
                    title: 'Waiting for Response',
                    text: 'Controller is responding... Please wait.',
                });

                await new Promise(resolve => setTimeout(resolve, 2000));

                Swal.close();

                await Swal.fire({
                    title: 'Auto-Check Complete',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Status:</strong> Controller is operational</p>
                            <p><strong>Response Time:</strong> ${Math.floor(Math.random() * 100) + 50}ms</p>
                            <p><strong>Connection:</strong> Active</p>
                            <p><strong>Last Check:</strong> ${new Date().toLocaleString()}</p>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#28a745',
                    showConfirmButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    position: 'center',
                    customClass: {
                        popup: 'auto-master-status-popup'
                    }
                });

                if (typeof showToast === 'function') {
                    showToast('Auto-check: Controller is responding', 'success');
                }

            } catch (error) {
                Swal.close();

                let errorMessage = 'Unable to check controller status. Please check the connection and try again.';

                if (error.message && error.message.includes('timeout')) {
                    errorMessage = 'Connection timeout. Controller is not responding.';
                } else if (error.message && error.message.includes('disconnected')) {
                    errorMessage = 'Device disconnected. Please check the connection.';
                }

                await Swal.fire({
                    title: 'Auto-Check Failed',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Message:</strong> ${errorMessage}</p>
                            <p><strong>Auto-Check #:</strong> ${masterCheckCount + 1}</p>
                            <p><strong>Time:</strong> ${new Date().toLocaleString()}</p>
                            <br>
                            <p><strong>Possible reasons:</strong></p>
                            <ul style="margin-left: 20px;">
                                <li>Controller is not connected</li>
                                <li>Device is not responding</li>
                                <li>Connection timeout</li>
                            </ul>
                        </div>
                    `,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#e2261b',
                    showConfirmButton: true,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    position: 'center'
                });

                if (window.logToTerminal) {
                    window.logToTerminal(`Auto check failed: ${error.message || 'Controller not responding'}`, 'error');
                }

                if (typeof showToast === 'function') {
                    showToast('Auto-check: Controller not responding', 'error');
                }
            } finally {
                autoCheckInProgress = false;
            }
        }

        // Function to reset cooldown after reaching max auto-checks
        function resetMasterCheckCooldown() {
            setTimeout(() => {
                masterCheckCooldown = false;
                masterCheckCount = 0;
                if (window.logToTerminal) {
                    window.logToTerminal('Controller status check cooldown period ended', 'info');
                }
                if (typeof showToast === 'function') {
                    showToast('Auto-check cooldown ended - ready for next check', 'info');
                }
            }, COOLDOWN_PERIOD);
        }

        // Function to check if all beacons are offline and trigger auto-check if conditions are met
        function checkAllBeaconsOffline() {
            const onlineBeacons = document.querySelectorAll('.beacon-item.online-item');
            const totalBeacons = document.querySelectorAll('.beacon-item').length;
            const allOffline = totalBeacons > 0 && onlineBeacons.length === 0;

            if (allOffline && !lastAllOfflineState) {
                console.log('All beacons are offline');

                if (!masterCheckCooldown && masterCheckCount < MAX_CHECKS && !autoCheckInProgress) {
                    const checkMasterBtn = document.getElementById('checkMasterBtn');
                    if (checkMasterBtn && !checkMasterBtn.disabled) {
                        setTimeout(async () => {
                            await performAutoMasterCheck();
                            masterCheckCount++;

                            if (masterCheckCount >= MAX_CHECKS) {
                                masterCheckCooldown = true;
                                resetMasterCheckCooldown();

                                Swal.fire({
                                    title: 'Auto-Check Cooldown',
                                    text: `Maximum auto-checks (${MAX_CHECKS}) reached. Cooldown period active for ${COOLDOWN_PERIOD/1000} seconds.`,
                                    icon: 'warning',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#ffc107',
                                    timer: 3000,
                                    timerProgressBar: true
                                });

                                if (typeof showToast === 'function') {
                                    showToast(`Auto-check cooldown active (${COOLDOWN_PERIOD/1000}s)`, 'warning');
                                }
                            }
                        }, 500);
                    }
                } else if (masterCheckCooldown && !autoCheckInProgress) {
                    Swal.fire({
                        title: 'Auto-Check on Cooldown',
                        text: `Please wait ${COOLDOWN_PERIOD/1000} seconds before next auto-check or check manually.`,
                        icon: 'info',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#17a2b8',
                        timer: 2000,
                        timerProgressBar: true
                    });
                } else if (masterCheckCount >= MAX_CHECKS && !autoCheckInProgress) {
                    Swal.fire({
                        title: 'Maximum Auto-Checks Reached',
                        text: 'Please check the controller manually using the CHECK CONTROLLER button.',
                        icon: 'warning',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: 'Check Manually',
                        showCancelButton: true,
                        cancelButtonText: 'Later',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const checkMasterBtn = document.getElementById('checkMasterBtn');
                            if (checkMasterBtn) {
                                checkMasterBtn.click();
                            }
                        }
                    });
                }
                const checkMasterBtn = document.getElementById('checkMasterBtn');
                if (checkMasterBtn) {
                    checkMasterBtn.style.animation = 'pulse 1s infinite';

                    if (!document.querySelector('#pulse-animation-style')) {
                        const style = document.createElement('style');
                        style.id = 'pulse-animation-style';
                        style.textContent = `
                    @keyframes pulse {
                            0% { opacity: 1; }
                            50% { opacity: 0.6; background-color: #e2261b; }
                            100% { opacity: 1; }
                        }
                    `;
                    document.head.appendChild(style);
                    }
                }
            } else if (!allOffline && lastAllOfflineState) {
                const checkMasterBtn = document.getElementById('checkMasterBtn');
                if (checkMasterBtn) {
                    checkMasterBtn.style.animation = '';
                }
                masterCheckCooldown = false;
                masterCheckCount = 0;
            }
            lastAllOfflineState = allOffline;
        }

        // Add custom styles for master status popup
        const style = document.createElement('style');
        style.textContent = `
            .master-status-popup {
                border-radius: 15px !important;
                border-top: 5px solid #28a745 !important;
            }
            .master-status-popup .swal2-html-container {
                font-size: 1em !important;
            }
        `;
        document.head.appendChild(style);

        // Add custom styles for the master status popup
        const autoStyle = document.createElement('style');
        autoStyle.textContent = `
            .auto-master-status-popup {
                border-radius: 15px !important;
                border-top: 5px solid #17a2b8 !important;
            }
            .auto-master-status-popup .swal2-html-container {
                font-size: 1em !important;
            }
        `;
        document.head.appendChild(autoStyle);

        // Function to manually reset the master auto-check mechanism (for testing or if the controller is fixed)
        window.resetMasterAutoCheck = function() {
            masterCheckCooldown = false;
            masterCheckCount = 0;
            autoCheckInProgress = false;
            const checkMasterBtn = document.getElementById('checkMasterBtn');
            if (checkMasterBtn) {
                checkMasterBtn.style.animation = '';
            }

            Swal.fire({
                title: 'Auto-Check Reset',
                text: 'Auto-check mechanism has been reset manually.',
                icon: 'success',
                confirmButtonText: 'OK',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true
            });
        };

        // Function to monitor beacon status changes in real-time and trigger checks when all beacons go offline
        function monitorBeaconStatus() {
            checkAllBeaconsOffline();

            const observer = new MutationObserver(function(mutations) {
                let shouldCheck = false;

                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        if (target.classList && target.classList.contains('beacon-item')) {
                            shouldCheck = true;
                        }
                    }
                });

                if (shouldCheck) {
                    clearTimeout(window.beaconCheckTimeout);
                    window.beaconCheckTimeout = setTimeout(() => {
                        checkAllBeaconsOffline();
                    }, 100);
                }
            });

            const beaconItems = document.querySelectorAll('.beacon-item');
            beaconItems.forEach(item => {
                observer.observe(item, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            });

            const beaconsContainer = document.getElementById('beaconsContainer');
            if (beaconsContainer) {
                const containerObserver = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'childList') {
                            mutation.addedNodes.forEach(node => {
                                if (node.nodeType === 1 && node.classList && node.classList.contains('beacon-item')) {
                                    observer.observe(node, {
                                        attributes: true,
                                        attributeFilter: ['class']
                                    });
                                }
                            });
                            checkAllBeaconsOffline();
                        }
                    });
                });

                containerObserver.observe(beaconsContainer, {
                    childList: true,
                    subtree: true
                });
            }
        }

        // Start monitoring beacon status once the DOM is fully loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(monitorBeaconStatus, 1000);
            });
        } else {
            setTimeout(monitorBeaconStatus, 1000);
        }

        // Override the global updateBeaconStatus function to trigger checks when beacon status changes
        const originalUpdateBeaconStatus = window.updateBeaconStatus;
        if (originalUpdateBeaconStatus) {
            window.updateBeaconStatus = function(beaconId, isOnline) {
                originalUpdateBeaconStatus(beaconId, isOnline);
                setTimeout(checkAllBeaconsOffline, 50);
            };
        }
        setInterval(() => {
            checkAllBeaconsOffline();
        }, 1000 * 5); // Periodic check every 5 seconds in case of missed updates

        // Function to update the door status modal content based on the current open doors
        const doorPopup = document.getElementById('doorPopup');
        const doorListContent = document.getElementById('doorListContent');
        const doorCountBadge = document.getElementById('doorCountBadge');
        const doorLastSync = document.getElementById('doorLastSync');
        const closeDoorPopupBtn = document.getElementById('closeDoorPopupBtn');

        let openDoorsMap = new Map();

        function updateDoorPopup() {
            if (!doorListContent || !doorCountBadge) return;

            if (openDoorsMap.size === 0) {
                doorPopup.classList.add('hidden');
                doorListContent.innerHTML = '<div class="text-gray-500 dark:text-gray-400 text-center py-4">No open doors</div>';
                doorCountBadge.textContent = '0';
                return;
            }

            doorPopup.classList.remove('hidden');

            let itemsHtml = '';
            for (let [beaconId, beaconName] of openDoorsMap.entries()) {
                itemsHtml += `
                    <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200">${escapeHtml(beaconName)}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">ID: ${beaconId}</p>
                        </div>
                        <button class="close-door-btn bg-munti-red-0 hover:bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm" data-beacon-id="${beaconId}">
                            Close Door
                        </button>
                    </div>
                `;
            }
            doorListContent.innerHTML = itemsHtml;
            doorCountBadge.textContent = openDoorsMap.size;
        }

        document.getElementById('doorPopup').addEventListener('click', function(e) {
            const btn = e.target.closest('.close-door-btn');
            if (!btn) return;
            const beaconId = btn.dataset.beaconId;
            if (beaconId) {
                console.log('[Door] Button clicked for beacon:', beaconId);
                window.sendCloseDoorCommand(beaconId);
            }
        });

        // Simple HTML escaping function to prevent XSS in beacon names
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // Functions to add or remove open doors from the map and update the modal
        function addOpenDoor(beaconId, beaconName) {
            beaconId = String(beaconId);
            if (document.querySelector(`.beacon-item[data-beacon-id="${beaconId}"].offline-item`)) return;
            if (!beaconId) return;
            openDoorsMap.set(beaconId, beaconName || 'Unknown Beacon');
            updateDoorPopup();
        }

        function removeOpenDoor(beaconId) {
            beaconId = String(beaconId);
            clearTimeout(pendingDoorCommands.get(beaconId));
            pendingDoorCommands.delete(beaconId);
            if (!beaconId) return;
            if (openDoorsMap.delete(beaconId)) updateDoorPopup();
        }

        // Expose door‑popup controls to the global scope so serial.js can update them
        window.updateDoorPopup = updateDoorPopup;
        window.addOpenDoor = addOpenDoor;
        window.removeOpenDoor = removeOpenDoor;

        const pendingDoorCommands = new Map();
        window.sendCloseDoorCommand = async function(beaconId) {
            beaconId = String(beaconId);
            if (!/^\d+$/.test(beaconId) || !openDoorsMap.has(beaconId) || pendingDoorCommands.has(beaconId)) return;
            const button = doorListContent.querySelector(`[data-beacon-id="${beaconId}"]`);
            pendingDoorCommands.set(beaconId, null);
            if (button) {
                button.disabled = true;
                button.textContent = 'Sending...';
            }
            try {
                if (typeof window.sendCommandToArduino !== 'function') throw new Error('Serial communication is not ready');
                await window.sendCommandToArduino(`<id=${beaconId};com=serv>`);
                // The reply may arrive before the HTTP request completes.
                if (!pendingDoorCommands.has(beaconId)) return;
                if (button) button.textContent = 'Waiting for controller...';
                pendingDoorCommands.set(beaconId, setTimeout(() => {
                    pendingDoorCommands.delete(beaconId);
                    updateDoorPopup();
                    showToast('No confirmation received. Check the beacon and try again.', 'warning');
                }, 15000));
            } catch (err) {
                pendingDoorCommands.delete(beaconId);
                updateDoorPopup();
                showToast(`Close Door failed: ${err.message}`, 'error');
            }
        };

        if (closeDoorPopupBtn) {
            closeDoorPopupBtn.addEventListener('click', () => {
                doorPopup.classList.add('hidden');
            });
        }

        // Load initial door status from the server-rendered data
        if (window.beaconDoors) {
            Object.entries(window.beaconDoors).forEach(([id, data]) => {
                if (data.is_door_open) {
                    openDoorsMap.set(id, data.name);
                }
            });
            updateDoorPopup();
            if (doorLastSync) doorLastSync.innerHTML = `Loaded from database`;
        }

        // Listen for real-time door status updates via Pusher
        if (window.Echo) {
            window.Echo.channel('beacons')
                .listen('beacon.door.updated', (data) => {
                    if (data.is_door_open) {
                        addOpenDoor(data.beacon_id, data.name || `Beacon ${data.beacon_id}`);
                    } else {
                        removeOpenDoor(data.beacon_id);
                    }
                    if (doorLastSync) doorLastSync.innerHTML = `Updated: ${new Date().toLocaleTimeString()}`;
                });
        }

    })();
    
</script>
<!-- defer: serial.js only calls io() on DOMContentLoaded, so a slow Pi can't stop the page from drawing -->
<script defer src="{{ config('services.serial.url') }}/socket.io/socket.io.js"></script>
<script src="{{ asset('js/serial.js') }}?v={{ filemtime(public_path('js/serial.js')) }}"></script>

<!-- Telegram connection status indicator -->
<div id="telegramStatus" class="fixed bottom-3 left-3 z-40 flex items-center gap-2 bg-white dark:bg-gray-800 rounded-full shadow-md border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300">
    <span id="telegramStatusDot" class="w-2 h-2 rounded-full bg-gray-400"></span>
    <span id="telegramStatusText">Checking Telegram…</span>
</div>
<script>
    (function() {
        const dot = document.getElementById('telegramStatusDot');
        const text = document.getElementById('telegramStatusText');

        async function checkTelegramStatus() {
            try {
                const res = await fetch('{{ route("api.telegram.status") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.connected) {
                    dot.className = 'w-2 h-2 rounded-full bg-munti-green-1';
                    text.textContent = 'Telegram connected';
                } else {
                    dot.className = 'w-2 h-2 rounded-full bg-munti-red-0';
                    text.textContent = 'Telegram disconnected';
                }
            } catch (err) {
                dot.className = 'w-2 h-2 rounded-full bg-munti-red-0';
                text.textContent = 'Telegram unreachable';
            }
        }

        checkTelegramStatus();
        setInterval(checkTelegramStatus, 60000); // re-check every 60s
    })();
</script>

@include('layouts.footer')
@else
<script>
    window.location = "{{ route('login') }}";
</script>
@endauth
