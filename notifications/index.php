<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'Sighting Alerts';
$userId    = $_SESSION['user_id'];

// Mark all unread notifications as read on page load
$markStmt = $conn->prepare('UPDATE sighting_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$markStmt->bind_param('i', $userId);
$markStmt->execute();

// Fetch all notifications for this user (newest first, last 100)
$stmt = $conn->prepare(
    'SELECT n.id, n.is_read, n.created_at,
            s.id AS sighting_id, s.sighting_type, s.lat, s.lng, s.sighting_time, s.notes,
            u.username AS reporter
     FROM sighting_notifications n
     JOIN sightings s ON s.id = n.sighting_id
     JOIN users u ON u.id = s.user_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT 100'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$typeEmoji = ['orca'=>'🐋','seal'=>'🦭','dolphin'=>'🐬','whale'=>'🐳','other'=>'👁️','debris'=>'🗑️','derelict_craft'=>'🚢'];
$typeLabel = ['orca'=>'Orca','seal'=>'Seal','dolphin'=>'Dolphin','whale'=>'Whale','other'=>'Other','debris'=>'Debris','derelict_craft'=>'Derelict Craft'];

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center gap-3 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-2">
                🔔 Sighting Alerts
            </h1>
            <p class="text-gray-400 mt-1">Nearby sightings reported while you were at sea</p>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="text-center py-20 glass-card">
            <div class="text-6xl mb-4">🔔</div>
            <p class="text-gray-400 text-lg">No alerts yet.</p>
            <p class="text-gray-500 text-sm mt-2">When someone reports a sighting near your location you'll see it here.</p>
            <a href="<?= SITE_URL ?>/profile/" class="btn-gold inline-block mt-6 px-6 py-2 text-sm">Set My Location →</a>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($notifications as $n):
                $latDir = (float)$n['lat'] >= 0 ? 'N' : 'S';
                $lngDir = (float)$n['lng'] >= 0 ? 'E' : 'W';
                $coords = number_format(abs((float)$n['lat']), 3) . "°$latDir, " . number_format(abs((float)$n['lng']), 3) . "°$lngDir";
                $emoji  = $typeEmoji[$n['sighting_type']] ?? '👁️';
                $label  = $typeLabel[$n['sighting_type']] ?? ucfirst($n['sighting_type']);
            ?>
                <a href="<?= SITE_URL ?>/sightings/view.php?id=<?= $n['sighting_id'] ?>"
                   class="glass-card p-4 flex items-start gap-4 hover:border-[#c9a227]/50 transition-all block">
                    <div class="text-3xl flex-shrink-0 mt-0.5"><?= $emoji ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                            <span class="text-white font-semibold"><?= $label ?> spotted nearby</span>
                            <span class="text-gray-500 text-xs">by <span class="text-[#c9a227]"><?= sanitize($n['reporter']) ?></span></span>
                        </div>
                        <?php if ($n['notes']): ?>
                            <p class="text-gray-400 text-sm mt-1 line-clamp-2"><?= sanitize(mb_substr($n['notes'], 0, 120)) ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-gray-500">
                            <span>📍 <?= $coords ?></span>
                            <span>🕐 <?= timeAgo($n['sighting_time']) ?></span>
                            <span class="text-[#c9a227]/60">Alerted <?= timeAgo($n['created_at']) ?></span>
                        </div>
                    </div>
                    <span class="text-[#c9a227] text-sm flex-shrink-0 mt-1">View →</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
