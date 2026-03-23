<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/anchorages/');

$stmt = $conn->prepare('SELECT a.*, u.username FROM anchorages a JOIN users u ON u.id = a.user_id WHERE a.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$a = $stmt->get_result()->fetch_assoc();
if (!$a) redirect(SITE_URL . '/anchorages/');

$pageTitle = sanitize($a['name']);
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/anchorages/" class="hover:text-[#c9a227]">Anchorages</a> /
        <span class="text-white"><?= sanitize($a['name']) ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Map snippet -->
        <div id="view-map" class="map-container rounded-xl border border-[#c9a227]/20"></div>

        <!-- Details -->
        <div class="glass-card p-6 space-y-5">
            <h1 class="text-3xl font-bold text-white"><?= sanitize($a['name']) ?></h1>
            <p class="text-gray-400 text-sm">Added by <span class="text-[#c9a227]"><?= sanitize($a['username']) ?></span> · <?= timeAgo($a['created_at']) ?></p>

            <div class="grid grid-cols-2 gap-4">
                <?php if ($a['depth']): ?>
                    <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-[#c9a227]"><?= $a['depth'] ?>m</p>
                        <p class="text-gray-400 text-xs mt-1">Depth</p>
                    </div>
                <?php endif; ?>
                <?php if ($a['holding_quality']): ?>
                    <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-[#c9a227]"><?= ucfirst($a['holding_quality']) ?></p>
                        <p class="text-gray-400 text-xs mt-1">Holding Quality</p>
                    </div>
                <?php endif; ?>
                <?php if ($a['protection_rating']): ?>
                    <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                        <p class="text-2xl"><?= str_repeat('⭐', (int)$a['protection_rating']) ?></p>
                        <p class="text-gray-400 text-xs mt-1">Protection</p>
                    </div>
                <?php endif; ?>
                <div class="bg-[#0a1628]/50 rounded-lg p-3 text-center">
                    <p class="text-xs font-mono text-[#c9a227]"><?= number_format((float)$a['lat'], 4) ?>° N</p>
                    <p class="text-xs font-mono text-[#c9a227]"><?= number_format((float)$a['lng'], 4) ?>° E</p>
                    <p class="text-gray-400 text-xs mt-1">Coordinates</p>
                </div>
            </div>

            <?php if ($a['review_text']): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-300 mb-2">Notes</h3>
                    <p class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap"><?= sanitize($a['review_text']) ?></p>
                </div>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/anchorages/add.php?lat=<?= $a['lat'] ?>&lng=<?= $a['lng'] ?>"
                   class="btn-outline text-center py-2 block text-sm">Add Nearby Anchorage</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const map = L.map('view-map').setView([<?= (float)$a['lat'] ?>, <?= (float)$a['lng'] ?>], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
L.circleMarker([<?= (float)$a['lat'] ?>, <?= (float)$a['lng'] ?>], { radius: 12, color: '#3b82f6', fillColor: '#60a5fa', fillOpacity: 0.9, weight: 2 })
    .addTo(map).bindPopup('<?= addslashes(sanitize($a['name'])) ?>').openPopup();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
