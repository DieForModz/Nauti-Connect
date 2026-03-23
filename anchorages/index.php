<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Anchorages';

// Build filter query
$holding   = $_GET['holding'] ?? '';
$protection = (int)($_GET['protection'] ?? 0);
$validHolding = ['excellent','good','fair','poor'];

$where  = ['1=1'];
$params = [];
$types  = '';

if ($holding !== '' && in_array($holding, $validHolding, true)) {
    $where[]  = 'a.holding_quality = ?';
    $params[]  = $holding;
    $types    .= 's';
}
if ($protection > 0 && $protection <= 5) {
    $where[]  = 'a.protection_rating >= ?';
    $params[]  = $protection;
    $types    .= 'i';
}

$whereClause = implode(' AND ', $where);
$sql = "SELECT a.*, u.username FROM anchorages a JOIN users u ON u.id = a.user_id WHERE $whereClause ORDER BY a.created_at DESC LIMIT 100";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$anchorages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Anchorage Guide</h1>
            <p class="text-gray-400 mt-1"><?= count($anchorages) ?> anchorages in the database</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/anchorages/add.php" class="btn-gold px-6 py-2">+ Add Anchorage</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="glass-card p-4 mb-6 flex flex-wrap gap-3 items-end">
        <div>
            <label class="form-label text-xs">Holding Quality</label>
            <select name="holding" class="form-input">
                <option value="">Any Holding</option>
                <?php foreach ($validHolding as $h): ?>
                    <option value="<?= $h ?>" <?= $holding === $h ? 'selected' : '' ?>><?= ucfirst($h) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label text-xs">Min Protection</label>
            <select name="protection" class="form-input">
                <option value="0">Any</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>" <?= $protection === $i ? 'selected' : '' ?>><?= str_repeat('⭐',$i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn-gold px-6 py-2">Apply</button>
        <a href="?" class="btn-outline px-4 py-2 text-sm">Reset</a>
    </form>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Map -->
        <div class="lg:col-span-2">
            <div id="anchorage-map" class="map-container rounded-xl border border-[#c9a227]/20"></div>
        </div>

        <!-- Sidebar list -->
        <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
            <?php if (empty($anchorages)): ?>
                <div class="text-center py-10">
                    <div class="text-4xl mb-3">⚓</div>
                    <p class="text-gray-400">No anchorages found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($anchorages as $a): ?>
                    <a href="<?= SITE_URL ?>/anchorages/view.php?id=<?= $a['id'] ?>" class="glass-card p-4 block hover:border-[#c9a227]/50 transition-all">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="font-bold text-white"><?= sanitize($a['name']) ?></h3>
                                <p class="text-xs text-gray-400 mt-0.5">by <?= sanitize($a['username']) ?></p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full border <?= match($a['holding_quality']) {
                                'excellent' => 'bg-green-500/20 text-green-300 border-green-500/30',
                                'good'      => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                'fair'      => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                                'poor'      => 'bg-red-500/20 text-red-300 border-red-500/30',
                                default     => 'bg-gray-500/20 text-gray-300',
                            } ?>"><?= ucfirst($a['holding_quality'] ?? 'N/A') ?></span>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs text-gray-500">
                            <?php if ($a['depth']): ?><span>Depth: <?= $a['depth'] ?>m</span><?php endif; ?>
                            <?php if ($a['protection_rating']): ?><span><?= str_repeat('⭐', (int)$a['protection_rating']) ?></span><?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const anchorageData = <?= json_encode(array_map(fn($a) => [
    'id'   => $a['id'],
    'name' => $a['name'],
    'lat'  => (float)$a['lat'],
    'lng'  => (float)$a['lng'],
    'holding' => $a['holding_quality'],
    'depth' => $a['depth'],
], $anchorages)) ?>;

const map = L.map('anchorage-map').setView([20, 0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

const markers = L.markerClusterGroup();
anchorageData.forEach(a => {
    const marker = L.circleMarker([a.lat, a.lng], { radius: 8, color: '#3b82f6', fillColor: '#60a5fa', fillOpacity: 0.8, weight: 2 });
    marker.bindPopup(`<b>${a.name}</b><br>Holding: ${a.holding || 'N/A'}<br>Depth: ${a.depth || 'N/A'}m<br><a href="<?= SITE_URL ?>/anchorages/view.php?id=${a.id}" style="color:#c9a227">View →</a>`);
    markers.addLayer(marker);
});
map.addLayer(markers);
if (anchorageData.length > 0) {
    map.fitBounds(markers.getBounds().pad(0.1));
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
