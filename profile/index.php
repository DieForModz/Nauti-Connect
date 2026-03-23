<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$viewUserId = (int)($_GET['id'] ?? ($_SESSION['user_id'] ?? 0));
if (!$viewUserId) redirect(SITE_URL . '/auth/login.php');

$stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $viewUserId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
if (!$profile) redirect(SITE_URL . '/');

$pageTitle = sanitize($profile['username']) . "'s Profile";
$tab       = $_GET['tab'] ?? 'listings';

// Count items
$counts = [];
$tables = ['parts_listings' => ['seller_id', 'Parts'], 'sightings' => ['user_id', 'Sightings'], 'anchorages' => ['user_id', 'Anchorages'], 'build_logs' => ['user_id', 'Builds']];
foreach ($tables as $tbl => [$col, $label]) {
    $r = $conn->prepare("SELECT COUNT(*) as cnt FROM $tbl WHERE $col = ?");
    $r->bind_param('i', $viewUserId);
    $r->execute();
    $counts[$tbl] = (int)$r->get_result()->fetch_assoc()['cnt'];
}

// Tab data
$tabData = [];
switch ($tab) {
    case 'sightings':
        $s = $conn->prepare("SELECT * FROM sightings WHERE user_id = ? ORDER BY sighting_time DESC LIMIT 20");
        $s->bind_param('i', $viewUserId); $s->execute();
        $tabData = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
    case 'anchorages':
        $s = $conn->prepare("SELECT * FROM anchorages WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $s->bind_param('i', $viewUserId); $s->execute();
        $tabData = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
    case 'builds':
        $s = $conn->prepare("SELECT * FROM build_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $s->bind_param('i', $viewUserId); $s->execute();
        $tabData = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        break;
    default:
        $s = $conn->prepare("SELECT * FROM parts_listings WHERE seller_id = ? ORDER BY created_at DESC LIMIT 20");
        $s->bind_param('i', $viewUserId); $s->execute();
        $tabData = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-5xl mx-auto px-4 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Sidebar -->
        <div>
            <div class="glass-card p-6 text-center">
                <?php if ($profile['profile_img']): ?>
                    <img src="<?= UPLOAD_URL . sanitize($profile['profile_img']) ?>" class="w-24 h-24 rounded-full object-cover border-2 border-[#c9a227] mx-auto mb-4">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-[#1e3a5f] border-2 border-[#c9a227] mx-auto mb-4 flex items-center justify-center text-4xl font-bold text-[#c9a227]">
                        <?= strtoupper(substr($profile['username'], 0, 1)) ?>
                    </div>
                <?php endif; ?>

                <h1 class="text-2xl font-bold text-white"><?= sanitize($profile['username']) ?></h1>
                <?php if ($profile['boat_type']): ?>
                    <p class="text-[#c9a227] text-sm mt-1">⛵ <?= sanitize($profile['boat_type']) ?></p>
                <?php endif; ?>

                <!-- Reputation -->
                <div class="mt-4 bg-[#c9a227]/10 border border-[#c9a227]/30 rounded-xl p-3">
                    <div class="text-3xl font-bold text-[#c9a227]"><?= number_format((int)$profile['reputation_points']) ?></div>
                    <div class="text-gray-400 text-xs mt-0.5">Reputation Points</div>
                </div>

                <?php if ($profile['bio']): ?>
                    <p class="text-gray-400 text-sm mt-4 leading-relaxed"><?= sanitize($profile['bio']) ?></p>
                <?php endif; ?>

                <p class="text-gray-600 text-xs mt-3">Member since <?= date('M Y', strtotime($profile['created_at'])) ?></p>

                <?php if (isLoggedIn() && $_SESSION['user_id'] !== $viewUserId): ?>
                    <a href="<?= SITE_URL ?>/messages/conversation.php?user=<?= $viewUserId ?>" class="btn-gold w-full py-2 mt-4 block text-sm">💬 Send Message</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:col-span-2">
            <!-- Tabs -->
            <div class="flex gap-1 mb-6 bg-[#0f2340] rounded-xl p-1 flex-wrap">
                <?php
                $tabMeta = [
                    'listings'   => ['label'=>'Parts (' . $counts['parts_listings'] . ')', 'tbl'=>'parts_listings'],
                    'sightings'  => ['label'=>'Sightings (' . $counts['sightings'] . ')', 'tbl'=>'sightings'],
                    'anchorages' => ['label'=>'Anchorages (' . $counts['anchorages'] . ')', 'tbl'=>'anchorages'],
                    'builds'     => ['label'=>'Builds (' . $counts['build_logs'] . ')', 'tbl'=>'build_logs'],
                ];
                foreach ($tabMeta as $key => $meta): ?>
                    <a href="?id=<?= $viewUserId ?>&tab=<?= $key ?>"
                       class="flex-1 text-center py-2 px-3 rounded-lg text-sm font-medium transition-all <?= $tab === $key ? 'bg-[#c9a227] text-[#0a1628]' : 'text-gray-400 hover:text-white' ?>"><?= $meta['label'] ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Tab content -->
            <?php if (empty($tabData)): ?>
                <div class="text-center py-16 text-gray-500">
                    <div class="text-4xl mb-3">📋</div>
                    <p>Nothing here yet.</p>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($tabData as $item): ?>
                        <?php
                        $link = match ($tab) {
                            'sightings'  => SITE_URL . '/sightings/view.php?id=' . $item['id'],
                            'anchorages' => SITE_URL . '/anchorages/view.php?id=' . $item['id'],
                            'builds'     => SITE_URL . '/builds/view.php?id=' . $item['id'],
                            default      => SITE_URL . '/parts/view.php?id=' . $item['id'],
                        };
                        $title = match ($tab) {
                            'sightings'  => ucfirst($item['sighting_type']) . ' sighting',
                            'anchorages' => $item['name'],
                            'builds'     => $item['title'],
                            default      => $item['title'],
                        };
                        ?>
                        <a href="<?= $link ?>" class="flex items-center justify-between glass-card p-4 hover:border-[#c9a227]/50 transition-all">
                            <span class="text-white font-medium"><?= sanitize($title) ?></span>
                            <span class="text-gray-500 text-sm"><?= timeAgo($item['created_at']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
