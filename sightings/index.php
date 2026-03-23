<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Sightings';

$species  = $_GET['species'] ?? '';
$validSp  = ['orca','seal','dolphin','whale','other','debris','derelict_craft'];
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($species !== '' && in_array($species, $validSp, true)) {
    $where[]  = 's.sighting_type = ?';
    $params[]  = $species;
    $types    .= 's';
}

$whereClause = implode(' AND ', $where);
$countSql    = "SELECT COUNT(*) as cnt FROM sightings s WHERE $whereClause";
$sql         = "SELECT s.*, u.username FROM sightings s JOIN users u ON u.id = s.user_id WHERE $whereClause ORDER BY s.sighting_time DESC LIMIT ? OFFSET ?";

if ($params) {
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($types, ...$params);
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $total = (int)$conn->query($countSql)->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$sightings  = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = (int)ceil($total / $perPage);

// Recent sightings for map (last 200)
$mapStmt = $conn->query("SELECT id, sighting_type, lat, lng, sighting_time, notes FROM sightings ORDER BY sighting_time DESC LIMIT 200");
$mapData = $mapStmt->fetch_all(MYSQLI_ASSOC);

// Activity log: last 15 reports for the log panel
$logStmt = $conn->query("SELECT s.id, s.sighting_type, s.lat, s.lng, s.sighting_time, s.notes, u.username FROM sightings s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC LIMIT 15");
$activityLog = $logStmt->fetch_all(MYSQLI_ASSOC);

$typeEmoji = ['orca'=>'🐋','seal'=>'🦭','dolphin'=>'🐬','whale'=>'🐳','other'=>'👁️','debris'=>'��️','derelict_craft'=>'🚢'];
$typeLabel = ['orca'=>'Orca','seal'=>'Seal','dolphin'=>'Dolphin','whale'=>'Whale','other'=>'Other','debris'=>'Debris','derelict_craft'=>'Derelict Craft'];

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Sightings</h1>
            <p class="text-gray-400 mt-1"><?= number_format($total) ?> sightings recorded</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/sightings/report.php" class="btn-gold px-6 py-2">+ Report Sighting</a>
        <?php endif; ?>
    </div>

    <!-- Sighting type filter tabs -->
    <div class="flex gap-2 flex-wrap mb-6">
        <a href="?" class="filter-btn <?= !$species ? 'active' : '' ?>">All Types</a>
        <?php foreach ($validSp as $sp): ?>
            <a href="?species=<?= $sp ?>" class="filter-btn <?= $species === $sp ? 'active' : '' ?>">
                <?= $typeEmoji[$sp] ?> <?= $typeLabel[$sp] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Nautical Map -->
    <div class="glass-card p-0 overflow-hidden rounded-xl mb-0 relative">
        <!-- Map header bar -->
        <div class="flex items-center justify-between px-4 py-2 border-b border-[#c9a227]/20 bg-[#0a1628]/60">
            <div class="flex items-center gap-2 text-sm text-gray-300">
                <svg class="w-4 h-4 text-[#c9a227]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span class="font-semibold text-[#c9a227]">Nautical Chart</span>
                <span class="text-gray-500 hidden sm:inline">— Click a marker to view report details</span>
            </div>
            <div class="flex items-center gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-[#c9a227]"></span> Last 24h</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-[#f97316]"></span> Older</span>
            </div>
        </div>
        <div id="sightings-map" class="map-container w-full"></div>
    </div>

    <!-- Activity Log -->
    <section class="mt-6 mb-10">
        <div class="flex items-center gap-3 mb-4">
            <svg class="w-5 h-5 text-[#c9a227] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h2 class="text-lg font-bold text-white">Recent Sighting Log</h2>
            <div class="h-px flex-1 bg-[#c9a227]/15"></div>
            <span class="text-xs text-gray-500">Latest 15 reports</span>
        </div>

        <?php if (empty($activityLog)): ?>
            <div class="glass-card p-8 text-center text-gray-500">No sightings logged yet.</div>
        <?php else: ?>
            <div class="glass-card overflow-hidden">
                <!-- Desktop table -->
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#c9a227]/20">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#c9a227] uppercase tracking-wider">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#c9a227] uppercase tracking-wider">Reporter</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#c9a227] uppercase tracking-wider">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#c9a227] uppercase tracking-wider">Position</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-[#c9a227] uppercase tracking-wider">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#c9a227]/10">
                            <?php foreach ($activityLog as $i => $entry):
                                $lat    = (float)$entry['lat'];
                                $lng    = (float)$entry['lng'];
                                $latDir = $lat >= 0 ? 'N' : 'S';
                                $lngDir = $lng >= 0 ? 'E' : 'W';
                                $coords = number_format(abs($lat), 3) . "°$latDir  " . number_format(abs($lng), 3) . "°$lngDir";
                                $eEmoji = $typeEmoji[$entry['sighting_type']] ?? '👁️';
                                $eLabel = $typeLabel[$entry['sighting_type']] ?? ucfirst($entry['sighting_type']);
                            ?>
                                <tr class="hover:bg-[#c9a227]/5 transition-colors group <?= $i === 0 ? 'bg-[#c9a227]/5' : '' ?>">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-lg"><?= $eEmoji ?></span>
                                        <span class="ml-1.5 text-white text-xs font-medium"><?= $eLabel ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-[#c9a227] font-medium text-sm"><?= sanitize($entry['username']) ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                        <?= timeAgo($entry['sighting_time']) ?>
                                        <br><span class="text-gray-600"><?= date('d M H:i', strtotime($entry['sighting_time'])) ?></span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-400 whitespace-nowrap"><?= $coords ?></td>
                                    <td class="px-4 py-3 text-gray-400 max-w-xs">
                                        <a href="<?= SITE_URL ?>/sightings/view.php?id=<?= $entry['id'] ?>"
                                           class="hover:text-[#c9a227] transition-colors line-clamp-2 group-hover:underline">
                                            <?= sanitize(mb_substr($entry['notes'] ?? '', 0, 100)) ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile stacked list -->
                <div class="sm:hidden divide-y divide-[#c9a227]/10">
                    <?php foreach ($activityLog as $i => $entry):
                        $lat    = (float)$entry['lat'];
                        $lng    = (float)$entry['lng'];
                        $latDir = $lat >= 0 ? 'N' : 'S';
                        $lngDir = $lng >= 0 ? 'E' : 'W';
                        $coords = number_format(abs($lat), 3) . "°$latDir, " . number_format(abs($lng), 3) . "°$lngDir";
                        $eEmoji = $typeEmoji[$entry['sighting_type']] ?? '👁️';
                        $eLabel = $typeLabel[$entry['sighting_type']] ?? ucfirst($entry['sighting_type']);
                    ?>
                        <a href="<?= SITE_URL ?>/sightings/view.php?id=<?= $entry['id'] ?>"
                           class="flex items-start gap-3 px-4 py-4 hover:bg-[#c9a227]/5 transition-colors">
                            <span class="text-2xl flex-shrink-0"><?= $eEmoji ?></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-white text-sm font-medium"><?= $eLabel ?></span>
                                    <span class="text-[#c9a227] text-xs"><?= sanitize($entry['username']) ?></span>
                                </div>
                                <p class="text-gray-500 text-xs mt-0.5 font-mono"><?= $coords ?> · <?= timeAgo($entry['sighting_time']) ?></p>
                                <?php if ($entry['notes']): ?>
                                    <p class="text-gray-400 text-xs mt-1 line-clamp-1"><?= sanitize(mb_substr($entry['notes'], 0, 80)) ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Sightings grid -->
    <?php if (empty($sightings)): ?>
        <div class="text-center py-16">
            <div class="text-6xl mb-4">🐬</div>
            <p class="text-gray-400 text-lg">No sightings yet. <a href="<?= SITE_URL ?>/sightings/report.php" class="text-[#c9a227]">Be the first!</a></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($sightings as $s):
                $isRecent = strtotime($s['sighting_time']) > time() - 86400; // within 24h
            ?>
                <a href="<?= SITE_URL ?>/sightings/view.php?id=<?= $s['id'] ?>"
                   class="glass-card p-5 hover:border-[#c9a227]/50 transition-all <?= $isRecent ? 'border-[#c9a227]/40 sighting-recent' : '' ?>">
                    <?php if ($isRecent): ?>
                        <span class="inline-block bg-[#c9a227]/20 text-[#c9a227] text-xs px-2 py-0.5 rounded-full border border-[#c9a227]/40 mb-2">🔴 Last 24h</span>
                    <?php endif; ?>

                    <div class="flex items-start gap-3">
                        <span class="text-4xl"><?= $typeEmoji[$s['sighting_type']] ?? '👁️' ?></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-white"><?= sanitize($typeLabel[$s['sighting_type']] ?? ucfirst($s['sighting_type'])) ?></h3>
                            <p class="text-gray-400 text-xs">by <?= sanitize($s['username']) ?></p>
                            <p class="text-gray-500 text-xs mt-1"><?= date('M j, Y g:ia', strtotime($s['sighting_time'])) ?></p>
                        </div>
                        <?php if ($s['verified']): ?>
                            <span class="text-green-400 text-xs">✓ Verified</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($s['notes']): ?>
                        <p class="text-gray-400 text-sm mt-3 line-clamp-2"><?= sanitize($s['notes']) ?></p>
                    <?php endif; ?>

                    <?php if ($s['image']): ?>
                        <img src="<?= UPLOAD_URL . sanitize($s['image']) ?>" class="w-full rounded-lg mt-3 h-32 object-cover" loading="lazy">
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= $species ? 'species=' . urlencode($species) . '&' : '' ?>page=<?= $i ?>"
                       class="px-4 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-[#c9a227] text-[#0a1628] font-bold' : 'glass-card text-gray-300 hover:text-white' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const sightingMapData = <?= json_encode(array_map(fn($s) => [
    'id'      => $s['id'],
    'species' => $s['sighting_type'],
    'lat'     => (float)$s['lat'],
    'lng'     => (float)$s['lng'],
    'time'    => $s['sighting_time'],
    'notes'   => substr($s['notes'] ?? '', 0, 100),
    'recent'  => strtotime($s['sighting_time']) > time() - 86400,
], $mapData)) ?>;

const emojis     = { orca:'��', seal:'🦭', dolphin:'🐬', whale:'🐳', other:'👁️', debris:'🗑️', derelict_craft:'🚢' };
const typeLabels = { orca:'Orca', seal:'Seal', dolphin:'Dolphin', whale:'Whale', other:'Other', debris:'Debris', derelict_craft:'Derelict Craft' };
const siteUrl    = <?= json_encode(SITE_URL) ?>;

// Base OSM layer
const osmBase = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19,
});

// OpenSeaMap nautical overlay (depth contours, buoys, beacons, shipping lanes)
const seamarksLayer = L.tileLayer('https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openseamap.org">OpenSeaMap</a>',
    opacity: 0.85,
    maxZoom: 19,
});

const sMap = L.map('sightings-map', {
    layers: [osmBase, seamarksLayer],
    zoomControl: true,
}).setView([20, 0], 2);

// Layer control: toggle nautical marks on/off
L.control.layers(
    { 'Street Map': osmBase },
    { '⚓ Nautical Marks': seamarksLayer },
    { position: 'topright', collapsed: false }
).addTo(sMap);

// Locate-me button
const LocateControl = L.Control.extend({
    options: { position: 'topleft' },
    onAdd() {
        const btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control');
        btn.innerHTML = '📍';
        btn.title = 'Centre on my location';
        btn.style.cssText = 'width:34px;height:34px;font-size:18px;cursor:pointer;background:#0f2340;border:2px solid rgba(201,162,39,0.4);border-radius:6px;display:flex;align-items:center;justify-content:center;';
        btn.onclick = () => { sMap.locate({ setView: true, maxZoom: 10 }); };
        L.DomEvent.disableClickPropagation(btn);
        return btn;
    }
});
sMap.addControl(new LocateControl());

// Cluster group for sighting markers
const sMarkers = L.markerClusterGroup({
    iconCreateFunction(cluster) {
        const n = cluster.getChildCount();
        return L.divIcon({
            html: `<div style="background:#f97316;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid rgba(255,255,255,0.3);">${n}</div>`,
            className: '', iconSize: [36, 36],
        });
    }
});

sightingMapData.forEach(s => {
    const color  = s.recent ? '#c9a227' : '#f97316';
    const radius = s.recent ? 11 : 7;
    const marker = L.circleMarker([s.lat, s.lng], {
        radius, color, fillColor: color, fillOpacity: 0.85, weight: s.recent ? 3 : 2,
    });
    const label    = typeLabels[s.species] || s.species;
    const notesHtml = s.notes ? `<p style="color:#94a3b8;font-size:12px;margin:4px 0 6px;">${s.notes}</p>` : '';
    marker.bindPopup(
        `<div style="min-width:160px">
           <div style="font-size:20px;margin-bottom:2px">${emojis[s.species] || '👁️'}</div>
           <b style="color:#e2e8f0">${label}</b>
           ${s.recent ? '<span style="color:#c9a227;font-size:11px;display:block">🔴 Last 24h</span>' : ''}
           <span style="color:#64748b;font-size:11px;display:block">${s.time}</span>
           ${notesHtml}
           <a href="${siteUrl}/sightings/view.php?id=${s.id}" style="color:#c9a227;font-size:12px">View full report →</a>
         </div>`
    );
    sMarkers.addLayer(marker);
});
sMap.addLayer(sMarkers);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
