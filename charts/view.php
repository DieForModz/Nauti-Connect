<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/charts/');

$stmt = $conn->prepare('SELECT c.*, u.username FROM chart_shares c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$chart = $stmt->get_result()->fetch_assoc();
if (!$chart) redirect(SITE_URL . '/charts/');

$pageTitle = 'Chart: ' . $chart['region_name'];
$coords    = json_decode($chart['coordinates_json'] ?? '[]', true) ?: [];
$ext       = strtolower(pathinfo($chart['chart_file'] ?? '', PATHINFO_EXTENSION));
$isImage   = in_array($ext, ['png','jpg','jpeg','webp']);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/charts/" class="hover:text-[#c9a227]">Charts</a> /
        <span class="text-white"><?= sanitize($chart['region_name']) ?></span>
    </nav>

    <div class="glass-card p-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-white"><?= sanitize($chart['region_name']) ?></h1>
                <p class="text-gray-400 mt-1">Shared by <span class="text-[#c9a227]"><?= sanitize($chart['username']) ?></span> · <?= timeAgo($chart['created_at']) ?></p>
            </div>
            <div class="text-right">
                <p class="text-gray-400 text-sm">⬇ <?= number_format((int)$chart['download_count']) ?> downloads</p>
                <span class="text-xs bg-[#0a1628] text-gray-400 px-2 py-0.5 rounded"><?= strtoupper($ext) ?></span>
            </div>
        </div>

        <?php if ($chart['description']): ?>
            <p class="text-gray-300 leading-relaxed mb-6"><?= sanitize($chart['description']) ?></p>
        <?php endif; ?>

        <!-- GPX Map Preview -->
        <?php if (!empty($coords) && !$isImage): ?>
            <div id="chart-map" class="map-container rounded-xl mb-6 border border-[#c9a227]/20"></div>
        <?php elseif ($isImage && $chart['chart_file']): ?>
            <img src="<?= UPLOAD_URL . sanitize($chart['chart_file']) ?>" alt="Chart of <?= sanitize($chart['region_name']) ?>" class="rounded-xl w-full mb-6 border border-[#c9a227]/20">
        <?php endif; ?>

        <!-- Metadata -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                <p class="text-[#c9a227] font-bold text-lg"><?= strtoupper($ext) ?: 'N/A' ?></p>
                <p class="text-gray-400 text-xs mt-1">Format</p>
            </div>
            <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                <p class="text-[#c9a227] font-bold text-lg"><?= count($coords) ?></p>
                <p class="text-gray-400 text-xs mt-1">Waypoints</p>
            </div>
            <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                <p class="text-[#c9a227] font-bold text-lg"><?= number_format((int)$chart['download_count']) ?></p>
                <p class="text-gray-400 text-xs mt-1">Downloads</p>
            </div>
        </div>

        <?php if ($chart['chart_file'] && isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/api/chart-download.php?id=<?= $id ?>" class="btn-gold w-full py-3 text-center block text-lg">⬇ Download Chart</a>
        <?php elseif (!isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn-gold w-full py-3 text-center block text-lg">Login to Download</a>
        <?php endif; ?>
    </div>
</main>

<?php if (!empty($coords) && !$isImage): ?>
<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const coords = <?= json_encode(array_slice($coords, 0, 200)) ?>;
const map = L.map('chart-map').setView([coords[0]?.lat || 0, coords[0]?.lng || 0], 8);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
if (coords.length > 1) {
    const latlngs = coords.map(c => [c.lat, c.lng]);
    L.polyline(latlngs, { color: '#c9a227', weight: 2 }).addTo(map);
}
coords.forEach(c => {
    L.circleMarker([c.lat, c.lng], { radius: 5, color: '#c9a227', fillColor: '#c9a227', fillOpacity: 0.8 })
        .addTo(map).bindPopup(c.name || 'Waypoint');
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
