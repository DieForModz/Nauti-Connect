/**
 * Nauti-Connect Map Module
 * Leaflet.js initialization, clustering, filtering
 */

let _mainMap = null;
let _allLayers = { anchorage: [], sighting: [], user: [] };
let _clusterGroups = {};
let _activeFilter = 'all';

/**
 * Initialize a full-featured Nauti-Connect map
 * @param {string} containerId - DOM element ID
 * @param {object} options - { lat, lng, zoom, dataUrl, addPinCallback }
 */
function initMap(containerId, options = {}) {
    const el = document.getElementById(containerId);
    if (!el) return;

    const lat    = options.lat || 20;
    const lng    = options.lng || 0;
    const zoom   = options.zoom || 2;

    _mainMap = L.map(containerId, { zoomControl: true }).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(_mainMap);

    // Cluster groups
    const anchorCluster = L.markerClusterGroup({ iconCreateFunction: createClusterIcon('blue') });
    const sightCluster  = L.markerClusterGroup({ iconCreateFunction: createClusterIcon('orange') });
    const userCluster   = L.markerClusterGroup({ iconCreateFunction: createClusterIcon('green') });

    _clusterGroups = { anchorage: anchorCluster, sighting: sightCluster, user: userCluster };

    // Fetch data
    if (options.dataUrl) {
        fetch(options.dataUrl)
            .then(r => r.json())
            .then(data => {
                if (data.anchorages) addAnchorageMarkers(data.anchorages, anchorCluster);
                if (data.sightings)  addSightingMarkers(data.sightings, sightCluster);
                if (data.users)      addUserMarkers(data.users, userCluster);

                _mainMap.addLayer(anchorCluster);
                _mainMap.addLayer(sightCluster);
                _mainMap.addLayer(userCluster);
            })
            .catch(err => console.warn('Map data fetch error:', err));
    }

    // Click to add pin
    if (options.addPinCallback) {
        _mainMap.on('click', function(e) {
            options.addPinCallback(
                parseFloat(e.latlng.lat.toFixed(6)),
                parseFloat(e.latlng.lng.toFixed(6))
            );
        });
    }

    return _mainMap;
}

function addAnchorageMarkers(anchorages, clusterGroup) {
    anchorages.forEach(a => {
        const marker = L.circleMarker([a.lat, a.lng], {
            radius: 9,
            color: '#3b82f6',
            fillColor: '#60a5fa',
            fillOpacity: 0.85,
            weight: 2,
        });
        const holdingBadge = a.holding ? `<span style="color:#60a5fa">${capitalize(a.holding)}</span>` : '';
        const depthBadge   = a.depth ? `<br>⚓ Depth: ${a.depth}m` : '';
        marker.bindPopup(
            `<b>⚓ ${escHtml(a.name)}</b><br>
             Holding: ${holdingBadge}${depthBadge}<br>
             <a href="${a.url}">View Anchorage →</a>`
        );
        _allLayers.anchorage.push(marker);
        clusterGroup.addLayer(marker);
    });
}

function addSightingMarkers(sightings, clusterGroup) {
    const emojis = { orca: '🐋', seal: '🦭', dolphin: '🐬', whale: '🐳', other: '👁️', debris: '🗑️', derelict_craft: '🚢' };
    const labels = { orca: 'Orca', seal: 'Seal', dolphin: 'Dolphin', whale: 'Whale', other: 'Other', debris: 'Debris', derelict_craft: 'Derelict Craft' };
    sightings.forEach(s => {
        const color  = s.recent ? '#c9a227' : '#f97316';
        const radius = s.recent ? 10 : 7;
        const marker = L.circleMarker([s.lat, s.lng], {
            radius,
            color,
            fillColor: color,
            fillOpacity: 0.85,
            weight: 2,
        });
        const emoji = emojis[s.species] || '👁️';
        const label = labels[s.species] || capitalize(s.species);
        marker.bindPopup(
            `${emoji} <b>${label}</b><br>
             ${s.recent ? '<span style="color:#c9a227">🔴 Last 24h</span><br>' : ''}
             ${s.time}<br>
             <a href="${s.url}">View Sighting →</a>`
        );
        _allLayers.sighting.push(marker);
        clusterGroup.addLayer(marker);
    });
}

function addUserMarkers(users, clusterGroup) {
    users.forEach(u => {
        const marker = L.circleMarker([u.lat, u.lng], {
            radius: 7,
            color: '#22c55e',
            fillColor: '#4ade80',
            fillOpacity: 0.8,
            weight: 2,
        });
        marker.bindPopup(
            `<b>👤 ${escHtml(u.username)}</b><br>
             ${u.boat_type ? escHtml(u.boat_type) + '<br>' : ''}
             <a href="${u.url}">View Profile →</a>`
        );
        _allLayers.user.push(marker);
        clusterGroup.addLayer(marker);
    });
}

/**
 * Filter markers by type ('all', 'anchorage', 'sighting', 'user')
 */
function filterMarkers(type) {
    if (!_mainMap) return;
    _activeFilter = type;

    // Update filter button states
    document.querySelectorAll('.filter-btn').forEach(btn => {
        const btnFilter = btn.getAttribute('data-filter');
        if (btnFilter) {
            btn.classList.toggle('active', type === 'all' ? btnFilter === 'all' : btnFilter === type);
        }
    });

    Object.entries(_clusterGroups).forEach(([key, group]) => {
        if (type === 'all' || type === key) {
            if (!_mainMap.hasLayer(group)) _mainMap.addLayer(group);
        } else {
            if (_mainMap.hasLayer(group)) _mainMap.removeLayer(group);
        }
    });
}

function createClusterIcon(color) {
    const colorMap = {
        blue:   { bg: '#3b82f6', text: '#fff' },
        orange: { bg: '#f97316', text: '#fff' },
        green:  { bg: '#22c55e', text: '#fff' },
        gold:   { bg: '#c9a227', text: '#0a1628' },
    };
    const c = colorMap[color] || colorMap.gold;
    return function(cluster) {
        return L.divIcon({
            html: `<div style="background:${c.bg};color:${c.text};width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid rgba(255,255,255,0.3);">${cluster.getChildCount()}</div>`,
            className: '',
            iconSize: [36, 36],
        });
    };
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
