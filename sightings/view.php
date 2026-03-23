<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/sightings/');

$stmt = $conn->prepare('SELECT s.*, u.username FROM sightings s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$s = $stmt->get_result()->fetch_assoc();
if (!$s) redirect(SITE_URL . '/sightings/');

$typeEmoji = ['orca'=>'🐋','seal'=>'🦭','dolphin'=>'🐬','whale'=>'🐳','other'=>'👁️','debris'=>'🗑️','derelict_craft'=>'🚢'];
$typeLabel = ['orca'=>'Orca','seal'=>'Seal','dolphin'=>'Dolphin','whale'=>'Whale','other'=>'Other','debris'=>'Debris','derelict_craft'=>'Derelict Craft'];
$pageTitle = ($typeLabel[$s['sighting_type']] ?? ucfirst($s['sighting_type'])) . ' Sighting';
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/sightings/" class="hover:text-[#c9a227]">Sightings</a> /
        <span class="text-white"><?= sanitize($typeLabel[$s['sighting_type']] ?? ucfirst($s['sighting_type'])) ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Photo + Map -->
        <div class="space-y-4">
            <?php if ($s['image']): ?>
                <img src="<?= UPLOAD_URL . sanitize($s['image']) ?>" class="w-full rounded-xl border border-[#c9a227]/20 max-h-80 object-cover" alt="Sighting photo">
            <?php endif; ?>
            <div id="sighting-map" style="height:300px;" class="rounded-xl border border-[#c9a227]/20 overflow-hidden"></div>
        </div>

        <!-- Info -->
        <div class="glass-card p-6 space-y-5">
            <?php $isRecent = strtotime($s['sighting_time']) > time() - 86400; ?>
            <?php if ($isRecent): ?>
                <span class="inline-block bg-[#c9a227]/20 text-[#c9a227] text-xs px-3 py-1 rounded-full border border-[#c9a227]/40 mb-1">🔴 Reported within 24 hours</span>
            <?php endif; ?>

            <div class="flex items-center gap-4">
                <span class="text-6xl"><?= $typeEmoji[$s['sighting_type']] ?? '👁️' ?></span>
                <div>
                    <h1 class="text-3xl font-bold text-white"><?= sanitize($typeLabel[$s['sighting_type']] ?? ucfirst($s['sighting_type'])) ?></h1>
                    <?php if ($s['verified']): ?>
                        <span class="text-green-400 text-sm">✓ Verified Sighting</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="bg-[#0a1628]/50 rounded-lg p-3">
                    <p class="text-gray-400 text-xs mb-1">Date &amp; Time</p>
                    <p class="text-white font-medium text-sm"><?= date('M j, Y g:ia', strtotime($s['sighting_time'])) ?></p>
                </div>
                <div class="bg-[#0a1628]/50 rounded-lg p-3">
                    <p class="text-gray-400 text-xs mb-1">Reported By</p>
                    <p class="text-[#c9a227] font-medium text-sm"><?= sanitize($s['username']) ?></p>
                </div>
                <div class="bg-[#0a1628]/50 rounded-lg p-3">
                    <p class="text-gray-400 text-xs mb-1">Coordinates</p>
                    <p class="font-mono text-xs text-white"><?= number_format((float)$s['lat'], 4) ?>, <?= number_format((float)$s['lng'], 4) ?></p>
                </div>
                <div class="bg-[#0a1628]/50 rounded-lg p-3">
                    <p class="text-gray-400 text-xs mb-1">Logged</p>
                    <p class="text-white text-sm"><?= timeAgo($s['created_at']) ?></p>
                </div>
            </div>

            <?php if ($s['notes']): ?>
                <div>
                    <h3 class="text-sm font-semibold text-gray-300 mb-2">Description</h3>
                    <p class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap"><?= sanitize($s['notes']) ?></p>
                </div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/sightings/report.php?lat=<?= $s['lat'] ?>&lng=<?= $s['lng'] ?>"
               class="btn-outline text-center block py-2 text-sm">Report Another Nearby</a>
        </div>
    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const m = L.map('sighting-map').setView([<?= (float)$s['lat'] ?>, <?= (float)$s['lng'] ?>], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(m);
L.circleMarker([<?= (float)$s['lat'] ?>, <?= (float)$s['lng'] ?>], { radius: 12, color: '#f97316', fillColor: '#fb923c', fillOpacity: 0.9, weight: 2 })
    .addTo(m).bindPopup('<?= addslashes($typeLabel[$s['sighting_type']] ?? ucfirst($s['sighting_type'])) ?> sighting').openPopup();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
