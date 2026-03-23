<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Wildlife Sightings';

$species  = $_GET['species'] ?? '';
$validSp  = ['orca','seal','dolphin','whale','other'];
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($species !== '' && in_array($species, $validSp, true)) {
    $where[]  = 's.species_type = ?';
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
$mapStmt = $conn->query("SELECT id, species_type, lat, lng, sighting_time, notes FROM sightings ORDER BY sighting_time DESC LIMIT 200");
$mapData = $mapStmt->fetch_all(MYSQLI_ASSOC);

$speciesEmoji = ['orca'=>'🐋','seal'=>'🦭','dolphin'=>'🐬','whale'=>'🐳','other'=>'🐟'];

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Wildlife Sightings</h1>
            <p class="text-gray-400 mt-1"><?= number_format($total) ?> sightings recorded</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/sightings/report.php" class="btn-gold px-6 py-2">+ Report Sighting</a>
        <?php endif; ?>
    </div>

    <!-- Species filter tabs -->
    <div class="flex gap-2 flex-wrap mb-6">
        <a href="?" class="filter-btn <?= !$species ? 'active' : '' ?>">All Species</a>
        <?php foreach ($validSp as $sp): ?>
            <a href="?species=<?= $sp ?>" class="filter-btn <?= $species === $sp ? 'active' : '' ?>">
                <?= $speciesEmoji[$sp] ?> <?= ucfirst($sp) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Map -->
    <div id="sightings-map" class="map-container rounded-xl border border-[#c9a227]/20 mb-8"></div>

    <!-- Sightings list -->
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
                        <span class="text-4xl"><?= $speciesEmoji[$s['species_type']] ?? '🐟' ?></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-white capitalize"><?= sanitize($s['species_type']) ?></h3>
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
    'species' => $s['species_type'],
    'lat'     => (float)$s['lat'],
    'lng'     => (float)$s['lng'],
    'time'    => $s['sighting_time'],
    'notes'   => substr($s['notes'] ?? '', 0, 100),
    'recent'  => strtotime($s['sighting_time']) > time() - 86400,
], $mapData)) ?>;

const emojis = { orca:'🐋', seal:'🦭', dolphin:'🐬', whale:'🐳', other:'🐟' };
const sMap = L.map('sightings-map').setView([20, 0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(sMap);

const sMarkers = L.markerClusterGroup();
sightingMapData.forEach(s => {
    const color = s.recent ? '#c9a227' : '#f97316';
    const marker = L.circleMarker([s.lat, s.lng], { radius: s.recent ? 10 : 7, color, fillColor: color, fillOpacity: 0.8, weight: 2 });
    marker.bindPopup(`${emojis[s.species] || '🐟'} <b>${s.species}</b><br>${s.time}<br>${s.notes ? s.notes + '<br>' : ''}<a href="<?= SITE_URL ?>/sightings/view.php?id=${s.id}" style="color:#c9a227">View →</a>`);
    sMarkers.addLayer(marker);
});
sMap.addLayer(sMarkers);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
