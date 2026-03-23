<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Build Logs';

$userId    = (int)($_GET['user'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
$types  = '';
if ($userId > 0) {
    $where[]  = 'b.user_id = ?';
    $params[]  = $userId;
    $types    .= 'i';
}

$whereClause = implode(' AND ', $where);
$countSql    = "SELECT COUNT(*) as cnt FROM build_logs b WHERE $whereClause";
$sql         = "SELECT b.*, u.username FROM build_logs b JOIN users u ON u.id = b.user_id WHERE $whereClause ORDER BY b.created_at DESC LIMIT ? OFFSET ?";

if ($params) {
    $cStmt = $conn->prepare($countSql);
    $cStmt->bind_param($types, ...$params);
    $cStmt->execute();
    $total = (int)$cStmt->get_result()->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $total = (int)$conn->query($countSql)->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$builds     = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = (int)ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Build Logs</h1>
            <p class="text-gray-400 mt-1">Community boat builds &amp; refits — <?= number_format($total) ?> projects</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/builds/create.php" class="btn-gold px-6 py-2">+ Start a Build Log</a>
        <?php endif; ?>
    </div>

    <?php if (empty($builds)): ?>
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🔩</div>
            <p class="text-gray-400 text-lg">No build logs yet. <a href="<?= SITE_URL ?>/builds/create.php" class="text-[#c9a227]">Start yours!</a></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($builds as $build):
                $imgSrc = $build['cover_image'] ? UPLOAD_URL . sanitize($build['cover_image']) : null;
                $progress = (int)$build['progress_percent'];
            ?>
                <a href="<?= SITE_URL ?>/builds/view.php?id=<?= $build['id'] ?>" class="glass-card hover:border-[#c9a227]/50 transition-all group overflow-hidden">
                    <div class="aspect-video bg-[#0a1628] overflow-hidden">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= sanitize($build['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-6xl">🔩</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-white text-lg mb-1"><?= sanitize($build['title']) ?></h3>
                        <p class="text-[#c9a227] text-sm font-medium mb-3">⛵ <?= sanitize($build['boat_name'] ?? 'Unnamed Vessel') ?></p>

                        <!-- Progress bar -->
                        <div class="mb-3">
                            <div class="flex justify-between text-xs text-gray-400 mb-1">
                                <span>Progress</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="progress-bar-track">
                                <div class="progress-bar-fill" style="width:<?= $progress ?>%"></div>
                            </div>
                        </div>

                        <?php if ($build['description']): ?>
                            <p class="text-gray-400 text-sm line-clamp-2"><?= sanitize($build['description']) ?></p>
                        <?php endif; ?>

                        <p class="text-gray-500 text-xs mt-3">by <?= sanitize($build['username']) ?> · <?= timeAgo($build['created_at']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= $userId ? 'user=' . $userId . '&' : '' ?>page=<?= $i ?>"
                       class="px-4 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-[#c9a227] text-[#0a1628] font-bold' : 'glass-card text-gray-300 hover:text-white' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
