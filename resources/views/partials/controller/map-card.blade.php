<div class="card bg-white dark:bg-gray-900 backdrop-blur-md border border-munti-blue-1/20 dark:border-munti-blue-1/30 rounded-md shadow-sm dark:shadow-none p-2 flex flex-col gap-2">
    <div class="flex items-center justify-between bg-munti-blue-0 dark:bg-munti-blue-1/10 border-b border-munti-blue-1/20 dark:border-munti-blue-1/30 px-4 py-2.5 uppercase tracking-wide font-medium text-munti-white-0 dark:text-munti-white-0">
        <span>Devices Map (Beacons & Sirens)</span>
        <button id="expandMapBtn" 
                class="text-xs flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h6v6M9 21H3v-6M21 3l-6 6M3 21l6-6"/>
            </svg>
        </button>
    </div>
    <div class="relative">
        <div id="map" class="bg-stone-900 rounded w-full"></div>
    </div>
    <div id="tooltip" class="fixed hidden bg-black/80 text-white rounded px-2 py-1 pointer-events-none z-[10001] text-xs shadow-lg backdrop-blur-sm" style="max-width: 200px;"></div>
</div>

<div id="expanded-map-overlay" class="hidden fixed inset-0 bg-black/90 z-[9999] flex flex-col">
    <div class="flex items-center justify-between px-6 py-4 border-b border-white/10 bg-black/50">
        <span class="uppercase tracking-wide font-medium text-white">Devices Map - Fullscreen</span>
        <button id="minimizeBtn" 
                class="flex items-center gap-2 text-white hover:text-blue-400 transition-colors text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"/>
            </svg>
            <span>Minimize</span>
        </button>
    </div>

    <button id="floatingCloseBtn" 
            class="absolute top-20 right-4 z-[10000] bg-black/70 hover:bg-black/90 text-white rounded-full w-10 h-10 flex items-center justify-center text-xl font-bold shadow-lg backdrop-blur-sm transition-all duration-200 border border-white/20"
            aria-label="Close fullscreen map">
        ✕
    </button>

    <div class="flex-1 relative">
        <div id="expanded-map" class="absolute inset-0"></div>
    </div>
</div>

<style>
    #map {
        height: 206px !important;
        width: 100% !important;
        border-radius: 0.375rem;
    }

    .custom-marker { 
        background: transparent; 
        border: none; 
    }
    
    /* Beacon styles (circle/dot) */
    .marker-dot { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        position: relative; 
    }
    .blinking-dot { 
        display: inline-flex; 
        width: 10px; 
        height: 10px; 
        border-radius: 50%; 
        position: relative; 
    }
    .blinking-dot.online-dot { 
        background-color: #003595; 
        box-shadow: 0 0 8px #003595; 
    }
    .blinking-dot.door-open-dot { 
        background-color: #e2261b; 
        box-shadow: 0 0 8px #e2261b; 
    }
    .blinking-dot::after { 
        content: ''; 
        position: absolute; 
        width: 100%; 
        height: 100%; 
        border-radius: 50%; 
        background-color: inherit; 
        opacity: 0.5; 
        left: 0; 
        top: 0; 
        animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    
    /* Siren styles (triangle) */
    .siren-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .siren-triangle {
        width: 0;
        height: 0;
        border-left: 8px solid transparent;
        border-right: 8px solid transparent;
        border-bottom: 14px solid;
        position: relative;
    }
    .siren-triangle.online-siren {
        border-bottom-color: #ffb702ff;
        filter: drop-shadow(0 0 4px #ffb702ff);
    }
    .siren-triangle.alert-siren {
        border-bottom-color: #5c677dff;
        filter: drop-shadow(0 0 4px #5c677dff);
    }
    .siren-triangle.offline-siren {
        border-bottom-color: #6b7280;
        filter: drop-shadow(0 0 2px #5c677dff);
    }
    .siren-triangle::after {
        content: '';
        position: absolute;
        top: 2px;
        left: -4px;
        width: 0;
        height: 0;
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 7px solid rgba(255,255,255,0.3);
    }
    
    /* Door Open Marker with icon */
    .door-open-marker { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        position: relative; 
    }
    .door-open-marker .blinking-dot { 
        position: absolute; 
        width: 14px; 
        height: 14px; 
    }
    .door-open-marker svg { 
        position: relative; 
        z-index: 2; 
        width: 8px; 
        height: 8px; 
        color: white; 
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.3)); 
    }
    
    /* Offline Door Marker */
    .offline-door-marker { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        position: relative; 
        opacity: 0.7; 
    }
    .offline-door-marker .status-offline-dot { 
        position: absolute; 
        width: 14px; 
        height: 14px; 
    }
    .offline-door-marker svg { 
        position: relative; 
        z-index: 2; 
        width: 8px; 
        height: 8px; 
        color: white; 
        filter: drop-shadow(0 1px 1px rgba(0,0,0,0.3)); 
    }
    
    .status-offline-dot { 
        display: inline-flex; 
        width: 10px; 
        height: 10px; 
        border-radius: 50%; 
        background-color: #5c677dff; 
        box-shadow: 0 0 6px rgba(239, 68, 68, 0.5); 
    }
    
    @keyframes ping { 75%, 100% { transform: scale(2); opacity: 0; } }

    .beacon-popup, .siren-popup { font-family: inherit; }
    .beacon-popup .online, .siren-popup .online { color: #ffb702ff; font-weight: bold; }
    .beacon-popup .door-open, .siren-popup .alert { color: #e2261b; font-weight: bold; }
    .beacon-popup .offline, .siren-popup .offline { color: #5c677dff; font-weight: bold; }
</style>

<script>
    (function() {
        const map = L.map('map').setView([14.3954452, 121.0391743], 12);

        const lightLayer = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png?key={{ config('services.carto.key') }}', { attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 19
        });
        const darkLayer = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}.png?key={{ config('services.carto.key') }}', { attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            maxZoom: 19
        });

        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        (isDark ? darkLayer : lightLayer).addTo(map);

        L.control.layers({ 'Light': lightLayer, 'Dark': darkLayer }, null, {
            collapsed: true, position: 'bottomleft'
        }).addTo(map);

        let barangayLayer = null;
        let expandedBarangayLayer = null;

        const barangayStyle = {
            color: "#2564eb33",
            weight: 2,
            fillColor: "#93c5fd",
            fillOpacity: 0.25
        };

        // Store barangay GeoJSON data for reuse in expanded map
        let barangayGeoJSONData = null;

        // Fetch and add barangay boundaries
        fetch('/barangays/geojson')
            .then(response => response.json())
            .then(data => {
                barangayGeoJSONData = data;
                barangayLayer = L.geoJSON(data, {
                    style: barangayStyle,
                    onEachFeature: function(feature, layer) {
                        if (feature.properties && feature.properties.name) {
                            layer.bindPopup(`<strong>${feature.properties.name}</strong>`);
                        }
                    }
                }).addTo(map);
            })
            .catch(error => console.error('Error loading barangay boundaries:', error));

        // Helper function to clean name (remove special characters like ░░░░░░░░░░░)
        function cleanName(name) {
            if (!name) return 'Unnamed';
            let cleaned = name.replace(/[░]+/g, '').trim();
            cleaned = cleaned.replace(/[^\w\s\-\(\)]/gi, '');
            return cleaned || 'Unnamed';
        }

        function getDeviceName(item, type) {
            let name = item.name || `Unnamed ${type}`;
            return cleanName(name);
        }

        // Beacon Icons (circles) - UPDATED with door icon for yellow door-open
        const beaconIcons = {
            online: L.divIcon({ className: 'custom-marker', html: '<div class="marker-dot"><span class="blinking-dot online-dot"></span></div>', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] }),
            doorOpen: L.divIcon({ className: 'custom-marker', html: `<div class="door-open-marker"><span class="blinking-dot door-open-dot"></span><svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M5 5v14a1 1 0 0 0 1 1h3v-2H7V6h2V4H6a1 1 0 0 0-1 1m14.242-.97l-8-2A1 1 0 0 0 10 3v18a.998.998 0 0 0 1.242.97l8-2A1 1 0 0 0 20 19V5a1 1 0 0 0-.758-.97M15 12.188a1.001 1.001 0 0 1-2 0v-.377a1 1 0 1 1 2 .001z" /></svg></div>`, iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] }),
            offline: L.divIcon({ className: 'custom-marker', html: '<div class="marker-dot"><span class="status-offline-dot"></span></div>', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] }),
            offlineDoorOpen: L.divIcon({ className: 'custom-marker', html: `<div class="offline-door-marker"><span class="status-offline-dot"></span><svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M5 5v14a1 1 0 0 0 1 1h3v-2H7V6h2V4H6a1 1 0 0 0-1 1m14.242-.97l-8-2A1 1 0 0 0 10 3v18a.998.998 0 0 0 1.242.97l8-2A1 1 0 0 0 20 19V5a1 1 0 0 0-.758-.97M15 12.188a1.001 1.001 0 0 1-2 0v-.377a1 1 0 1 1 2 .001z" /></svg></div>`, iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] })
        };

        // Siren Icons (triangles)
        const sirenIcons = {
            online: L.divIcon({ className: 'custom-marker', html: '<div class="siren-marker"><div class="siren-triangle online-siren"></div></div>', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] }),
            alert: L.divIcon({ className: 'custom-marker', html: '<div class="siren-marker"><div class="siren-triangle alert-siren"></div></div>', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] }),
            offline: L.divIcon({ className: 'custom-marker', html: '<div class="siren-marker"><div class="siren-triangle offline-siren"></div></div>', iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-10] })
        };

        const beaconMarkers = {};
        const sirenMarkers = {};
        let activeTooltip = null;
        let currentHoveredMarker = null;

        // Store API siren data for reference
        let apiSirensData = new Map();

        function positionTooltip(e, tooltipElement) {
            if (!tooltipElement) return;
            const p = map.latLngToContainerPoint(e.latlng);
            tooltipElement.style.left = (p.x + 15) + 'px';
            tooltipElement.style.top = (p.y - 40) + 'px';
        }

        function showTooltip(content, latlng, mapInstance) {
            let tooltipElement;
            if (mapInstance === map) {
                tooltipElement = document.getElementById('tooltip');
            } else {
                tooltipElement = document.getElementById('expanded-tooltip');
                if (!tooltipElement) {
                    tooltipElement = document.createElement('div');
                    tooltipElement.id = 'expanded-tooltip';
                    tooltipElement.className = 'fixed hidden bg-black/80 text-white rounded px-2 py-1 pointer-events-none z-[10001] text-xs shadow-lg backdrop-blur-sm whitespace-nowrap';
                    tooltipElement.style.maxWidth = '300px';
                    document.body.appendChild(tooltipElement);
                }
            }
            
            if (!tooltipElement) return;
            
            tooltipElement.innerHTML = content;
            tooltipElement.style.display = 'block';
            
            const containerPoint = mapInstance.latLngToContainerPoint(latlng);
            tooltipElement.style.left = (containerPoint.x + 15) + 'px';
            tooltipElement.style.top = (containerPoint.y - 40) + 'px';
            
            return tooltipElement;
        }

        function hideTooltip(mapInstance) {
            let tooltipElement;
            if (mapInstance === map) {
                tooltipElement = document.getElementById('tooltip');
            } else {
                tooltipElement = document.getElementById('expanded-tooltip');
            }
            if (tooltipElement) {
                tooltipElement.style.display = 'none';
            }
        }

        function createMarkerWithTooltip(lat, lng, icon, popupContent, tooltipContent, mapInstance) {
            const marker = L.marker([lat, lng], { icon }).addTo(mapInstance);
            marker.bindPopup(popupContent);
            
            marker.on('mouseover', (e) => {
                if (currentHoveredMarker === marker) return;
                currentHoveredMarker = marker;
                showTooltip(tooltipContent, e.latlng, mapInstance);
            });
            
            marker.on('mousemove', (e) => {
                if (currentHoveredMarker === marker) {
                    const tooltipElement = mapInstance === map ? 
                        document.getElementById('tooltip') : 
                        document.getElementById('expanded-tooltip');
                    const containerPoint = mapInstance.latLngToContainerPoint(e.latlng);
                    if (tooltipElement) {
                        tooltipElement.style.left = (containerPoint.x + 15) + 'px';
                        tooltipElement.style.top = (containerPoint.y - 40) + 'px';
                    }
                }
            });
            
            marker.on('mouseout', () => {
                if (currentHoveredMarker === marker) {
                    currentHoveredMarker = null;
                    hideTooltip(mapInstance);
                }
            });
            
            return marker;
        }

        async function fetchApiSirens() {
            if (!window.sirenApiConfigured) return;
            try {
                const response = await fetch('{{ route("settings.api.fetch") }}?path=/api/Data/Sirens&connection_name=Siren', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`API responded with status ${response.status}`);
                }

                const apiData = await response.json();
                apiSirensData.clear();
                
                let sirensArray = [];
                if (Array.isArray(apiData)) {
                    sirensArray = apiData;
                } else if (apiData.data && Array.isArray(apiData.data)) {
                    sirensArray = apiData.data;
                } else if (apiData.sirens && Array.isArray(apiData.sirens)) {
                    sirensArray = apiData.sirens;
                } else {
                    console.warn('Unexpected API response format:', apiData);
                    return;
                }

                sirensArray.forEach(siren => {
                    const oid = siren.OID || siren.oid || siren.id;
                    if (oid) {
                        apiSirensData.set(oid.toString(), siren);
                    }
                });

                console.log(`Loaded ${apiSirensData.size} sirens from API`);
                loadSirens(map);
                if (expandedMap) loadSirens(expandedMap);
                
            } catch (error) {
                console.error('Error fetching sirens from API:', error);
                loadSirens(map);
                if (expandedMap) loadSirens(expandedMap);
            }
        }

        function loadBeacons(targetMap = map) {
            const beacons = <?= json_encode($beacons ?? []) ?>;
            if (!beacons?.length) return;

            if (targetMap === map) {
                Object.values(beaconMarkers).forEach(m => targetMap.removeLayer(m));
            } else {
                if (!window.expandedBeaconMarkers) window.expandedBeaconMarkers = {};
                Object.values(window.expandedBeaconMarkers).forEach(m => targetMap.removeLayer(m));
            }

            beacons.forEach(beacon => {
                const { latitude, longitude, name, location, status, is_door_open, beacon_id } = beacon;
                if (!latitude || !longitude) return;

                const isOnline = !!status;
                const isDoorOpen = isOnline && !!is_door_open;
                const cleanBeaconName = getDeviceName(beacon, 'Beacon');

                let icon;
                if (isOnline && isDoorOpen) {
                    icon = beaconIcons.doorOpen; // Yellow with door icon
                } else if (isOnline) {
                    icon = beaconIcons.online; // Green
                } else if (isDoorOpen) {
                    icon = beaconIcons.offlineDoorOpen; // Red with door icon
                } else {
                    icon = beaconIcons.offline; // Red
                }

                const locName = location?.location_name || 'Unknown';
                const beaconName = cleanBeaconName;

                let statusText = isOnline 
                    ? (isDoorOpen ? 'Door Open' : 'Online') 
                    : (isDoorOpen ? 'Door Open' : 'Offline');
                let statusClass = isOnline 
                    ? (isDoorOpen ? 'door-open' : 'online') 
                    : 'offline';

                const popupContent = `
                    <div class="beacon-popup min-w-[150px]">
                        <strong class="text-sm">${beaconName}</strong><br>
                        <span class="text-xs">Location: ${locName}</span><br>
                        <span class="text-xs">ID: ${beacon_id}</span><br>
                        <span class="text-xs mt-1 inline-block">Status: <span class="${statusClass}">${statusText}</span></span>
                    </div>
                `;

                const statusLine = isOnline 
                    ? (isDoorOpen ? 'Door Open' : 'Online') 
                    : (isDoorOpen ? 'Door Open' : 'Offline');
                const statusColor = isOnline 
                    ? (isDoorOpen ? 'text-munti-red-0' : 'text-munti-blue-2') 
                    : '#5c677dff';

                const tooltipContent = `
                    <div class="font-semibold ${statusColor}">${beaconName}</div>
                    <div class="text-gray-300">Location: ${locName}</div>
                    <div class="text-gray-300 text-[10px]">Lat: ${parseFloat(latitude).toFixed(6)}</div>
                    <div class="text-gray-300 text-[10px]">Lng: ${parseFloat(longitude).toFixed(6)}</div>
                    <div class="mt-1 ${statusColor}">${statusLine}</div>
                `;

                const marker = createMarkerWithTooltip(
                    parseFloat(latitude), 
                    parseFloat(longitude), 
                    icon, 
                    popupContent, 
                    tooltipContent, 
                    targetMap
                );
                
                if (targetMap === map) {
                    beaconMarkers[beacon_id] = marker;
                } else {
                    if (!window.expandedBeaconMarkers) window.expandedBeaconMarkers = {};
                    window.expandedBeaconMarkers[beacon_id] = marker;
                }
            });
        }

        function loadSirens(targetMap = map) {
            const sirens = <?= json_encode($sirens ?? []) ?>;
            if (!sirens?.length) return;

            if (targetMap === map) {
                Object.values(sirenMarkers).forEach(m => targetMap.removeLayer(m));
            } else {
                if (!window.expandedSirenMarkers) window.expandedSirenMarkers = {};
                Object.values(window.expandedSirenMarkers).forEach(m => targetMap.removeLayer(m));
            }

            sirens.forEach(siren => {
                const { latitude, longitude, name, location, siren_id, group, enabled, status, is_alerting } = siren;
                if (!latitude || !longitude) return;

                const cleanSirenName = getDeviceName(siren, 'Siren');
                const oid = siren.oid || siren.OID || siren.siren_id || siren.id;
                const apiSiren = oid ? apiSirensData.get(oid.toString()) : null;
                
                const isOnline = apiSiren !== null && apiSiren !== undefined;
                
                let isAlerting = false;
                if (apiSiren) {
                    isAlerting = apiSiren.IsAlerting === true || 
                                apiSiren.is_alerting === true || 
                                apiSiren.alerting === true ||
                                apiSiren.status === 'alerting' ||
                                apiSiren.Status === 'Alerting';
                }
                if (!isAlerting) {
                    isAlerting = is_alerting === true || is_alerting === 1;
                }

                let icon;
                if (isOnline && isAlerting) {
                    icon = sirenIcons.alert;
                } else if (isOnline) {
                    icon = sirenIcons.online;
                } else {
                    icon = sirenIcons.offline;
                }

                const locName = location?.location_name || 'No Location Assigned';
                const markerId = siren_id || siren.id || oid;

                let statusText = isOnline 
                    ? (isAlerting ? 'ACTIVE / ALARMING' : 'Online (Standby)') 
                    : '✗ Offline';
                let statusClass = isOnline 
                    ? (isAlerting ? 'alert' : 'online') 
                    : 'offline';

                let apiInfo = '';
                if (apiSiren) {
                    apiInfo = `<span class="text-xs">Last Updated: ${apiSiren.LastUpdate || apiSiren.last_update || 'N/A'}</span><br>`;
                }

                const popupContent = `
                    <div class="siren-popup min-w-[160px]">
                        <strong class="text-sm">${cleanSirenName}</strong><br>
                        <span class="text-xs">Location: ${locName}</span><br>
                        <span class="text-xs">Siren ID: ${markerId}</span><br>
                        <span class="text-xs">Group: ${group || '—'}</span><br>
                        ${apiInfo}
                        <span class="text-xs mt-1 inline-block">Status: <span class="${statusClass}">${statusText}</span></span>
                    </div>
                `;

                const statusLine = isOnline 
                    ? (isAlerting ? 'ACTIVE / ALARMING' : 'Online (Standby)') 
                    : '✗ Offline';
                const statusColor = isOnline 
                    ? (isAlerting ? 'munti-white-0' : 'text-munti-blue-2') 
                    : 'munti-white-0';

                const tooltipContent = `
                    <div class="font-semibold ${statusColor}">${cleanSirenName}</div>
                    <div class="text-gray-300">Location: ${locName}</div>
                    <div class="text-gray-300 text-[10px]">Lat: ${parseFloat(latitude).toFixed(6)}</div>
                    <div class="text-gray-300 text-[10px]">Lng: ${parseFloat(longitude).toFixed(6)}</div>
                    <div class="mt-1 ${statusColor}">${statusLine}</div>
                    ${apiSiren ? `<div class="text-gray-400 text-[9px] mt-1">API Connected</div>` : ''}
                `;

                const marker = createMarkerWithTooltip(
                    parseFloat(latitude), 
                    parseFloat(longitude), 
                    icon, 
                    popupContent, 
                    tooltipContent, 
                    targetMap
                );
                
                if (targetMap === map) {
                    sirenMarkers[markerId] = marker;
                } else {
                    if (!window.expandedSirenMarkers) window.expandedSirenMarkers = {};
                    window.expandedSirenMarkers[markerId] = marker;
                }
            });
        }

        window.updateBeaconStatus = function(beaconId, isOnline, isDoorOpen) {
            isDoorOpen = !!isOnline && !!isDoorOpen;
            const marker = beaconMarkers[beaconId];
            if (!marker) return;
            
            let icon;
            if (isOnline && isDoorOpen) {
                icon = beaconIcons.doorOpen;
            } else if (isOnline) {
                icon = beaconIcons.online;
            } else if (isDoorOpen) {
                icon = beaconIcons.offlineDoorOpen;
            } else {
                icon = beaconIcons.offline;
            }
            
            marker.setIcon(icon);
            
            const locName = marker.getPopup()?.getContent()?.match(/Location: (.*?)<br>/)?.[1] || 'Unknown';
            const beaconName = marker.getPopup()?.getContent()?.match(/<strong class="text-sm">(.*?)<\/strong>/)?.[1] || 'Unnamed Beacon';
            
            let statusText = isOnline 
                ? (isDoorOpen ? 'Door Open' : 'Online') 
                : (isDoorOpen ? 'Door Open' : 'Offline');
            let statusClass = isOnline 
                ? (isDoorOpen ? 'door-open' : 'online') 
                : 'offline';
            
            marker.bindPopup(`
                <div class="beacon-popup min-w-[150px]">
                    <strong class="text-sm">${beaconName}</strong><br>
                    <span class="text-xs">Location: ${locName}</span><br>
                    <span class="text-xs">ID: ${beaconId}</span><br>
                    <span class="text-xs mt-1 inline-block">Status: <span class="${statusClass}">${statusText}</span></span>
                </div>
            `);
        };

        window.updateSirenStatus = function(sirenId, isOnline, isAlerting) {
            const marker = sirenMarkers[sirenId];
            if (!marker) return;
            
            let icon;
            if (isOnline && isAlerting) {
                icon = sirenIcons.alert;
            } else if (isOnline) {
                icon = sirenIcons.online;
            } else {
                icon = sirenIcons.offline;
            }
            
            marker.setIcon(icon);
        };

        window.refreshSirenAPI = async function() {
            await fetchApiSirens();
        };

        async function refreshAllDataBeforeFullscreen() {
            console.log('Refreshing map data before opening fullscreen...');
            const expandBtn = document.getElementById('expandMapBtn');
            const originalText = expandBtn.innerHTML;
            expandBtn.innerHTML = '<div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>';
            expandBtn.disabled = true;
            
            try {
                await fetchApiSirens();
                await new Promise(resolve => setTimeout(resolve, 500));
                console.log('Data refresh complete, opening fullscreen map');
            } catch (error) {
                console.error('Error refreshing data:', error);
            } finally {
                expandBtn.innerHTML = originalText;
                expandBtn.disabled = false;
            }
        }

        let expandedMap = null;
        const overlay = document.getElementById('expanded-map-overlay');
        const expandBtn = document.getElementById('expandMapBtn');
        const minimizeBtn = document.getElementById('minimizeBtn');

        async function createExpandedMap() {
            if (expandedMap) {
                refreshExpandedMapLayers();
                return;
            }

            expandedMap = L.map('expanded-map', { zoomControl: true }).setView([14.3954452, 121.0391743], 14);

            const lightLayer = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png?key={{ config('services.carto.key') }}', { attribution: '&copy; OpenStreetMap contributors &copy; CARTO', maxZoom: 19 });
            const darkLayer = L.tileLayer('https://basemaps.cartocdn.com/rastertiles/dark_all/{z}/{x}/{y}.png?key={{ config('services.carto.key') }}', { attribution: '&copy; OpenStreetMap contributors &copy; CARTO', maxZoom: 19 });

            const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            (isDark ? darkLayer : lightLayer).addTo(expandedMap);

            L.control.layers({ 'Light': lightLayer, 'Dark': darkLayer }, null, { collapsed: true, position: 'bottomleft' }).addTo(expandedMap);
            
            if (barangayGeoJSONData) {
                expandedBarangayLayer = L.geoJSON(barangayGeoJSONData, {
                    style: barangayStyle,
                    onEachFeature: function(feature, layer) {
                        if (feature.properties && feature.properties.name) {
                            layer.bindPopup(`<strong>${feature.properties.name}</strong>`);
                        }
                    }
                }).addTo(expandedMap);
            }
            
            refreshExpandedMapLayers();
        }
        
        function refreshExpandedMapLayers() {
            if (!expandedMap) return;
            
            if (window.expandedBeaconMarkers) {
                Object.values(window.expandedBeaconMarkers).forEach(m => expandedMap.removeLayer(m));
            }
            if (window.expandedSirenMarkers) {
                Object.values(window.expandedSirenMarkers).forEach(m => expandedMap.removeLayer(m));
            }
            
            loadBeacons(expandedMap);
            loadSirens(expandedMap);
        }

        async function openExpanded() {
            overlay.classList.remove('hidden');
            await refreshAllDataBeforeFullscreen();
            await createExpandedMap();
            
            setTimeout(() => { if(expandedMap) expandedMap.invalidateSize(); }, 100);
            setTimeout(() => { if(expandedMap) expandedMap.invalidateSize(); }, 400);
        }

        function minimizeMap() {
            overlay.classList.add('hidden');
            const expandedTooltip = document.getElementById('expanded-tooltip');
            if (expandedTooltip) {
                expandedTooltip.remove();
            }
        }

        expandBtn.addEventListener('click', () => {
            if (overlay.classList.contains('hidden')) {
                openExpanded();
            } else {
                minimizeMap();
            }
        });

        minimizeBtn.addEventListener('click', minimizeMap);

        const floatingCloseBtn = document.getElementById('floatingCloseBtn');
        if (floatingCloseBtn) {
            floatingCloseBtn.addEventListener('click', minimizeMap);
        }

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) minimizeMap();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) {
                minimizeMap();
            }
        });

        window.addEventListener('resize', () => map.invalidateSize());

        fetchApiSirens().then(() => {
            loadBeacons(map);
            loadSirens(map);
        });

        window.beaconMap = {
            markers: beaconMarkers,
            updateMarker: window.updateBeaconStatus
        };
        
        window.sirenMap = {
            markers: sirenMarkers,
            updateMarker: window.updateSirenStatus,
            refreshAPI: window.refreshSirenAPI
        };

        setInterval(() => {
            fetchApiSirens();
        }, 30000);
    })();
</script>

<style>    
    /* Tooltip animation and styling */
    #tooltip, #expanded-tooltip {
        transition: opacity 0.2s ease-in-out;
        font-size: 11px;
        line-height: 1.4;
        z-index: 10001;
        pointer-events: none;
        border: 1px solid rgba(255, 255, 255, 0.1);
        white-space: nowrap;
    }
    
    /* Popup styling */
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.9);
        backdrop-filter: blur(10px);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .leaflet-popup-tip {
        background: rgba(0, 0, 0, 0.9);
    }
</style>
