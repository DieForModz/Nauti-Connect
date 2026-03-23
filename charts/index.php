<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Chart Library';

$regions = ['Caribbean','Mediterranean','Pacific Northwest','Atlantic','Pacific','Indian Ocean','Other'];
$search  = trim($_GET['search'] ?? '');
$region  = trim($_GET['region'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(c.region_name LIKE ? OR c.description LIKE ?)';
    $like      = '%' . $search . '%';
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'ss';
}
if ($region !== '') {
    $where[]  = 'c.region_name = ?';
    $params[]  = $region;
    $types    .= 's';
}

$whereClause = implode(' AND ', $where);
$countSql    = "SELECT COUNT(*) as cnt FROM chart_shares c WHERE $whereClause";
$sql         = "SELECT c.*, u.username FROM chart_shares c JOIN users u ON u.id = c.user_id WHERE $whereClause ORDER BY c.created_at DESC LIMIT ? OFFSET ?";

if ($params) {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
} else {
    $total = (int)$conn->query($countSql)->fetch_assoc()['cnt'];
    $stmt  = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$charts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = (int)ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Chart Library</h1>
            <p class="text-gray-400 mt-1">Community-shared GPX, KMZ and chart files</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/charts/upload.php" class="btn-gold px-6 py-2">+ Share a Chart</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="glass-card p-4 mb-8 flex flex-wrap gap-3">
        <input type="text" name="search" class="form-input flex-1 min-w-48" placeholder="Search charts…" value="<?= sanitize($search) ?>">
        <select name="region" class="form-input">
            <option value="">All Regions</option>
            <?php foreach ($regions as $r): ?>
                <option value="<?= sanitize($r) ?>" <?= $region === $r ? 'selected' : '' ?>><?= sanitize($r) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-gold px-6">Filter</button>
    </form>

    <!-- Region tabs -->
    <div class="flex gap-2 flex-wrap mb-6">
        <a href="?" class="filter-btn <?= !$region ? 'active' : '' ?>">All</a>
        <?php foreach ($regions as $r): ?>
            <a href="?region=<?= urlencode($r) ?>" class="filter-btn <?= $region === $r ? 'active' : '' ?>"><?= sanitize($r) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($charts)): ?>
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🗺️</div>
            <p class="text-gray-400 text-lg">No charts yet. <a href="<?= SITE_URL ?>/charts/upload.php" class="text-[#c9a227]">Share the first one!</a></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($charts as $chart):
                $ext = strtolower(pathinfo($chart['chart_file'] ?? '', PATHINFO_EXTENSION));
                $icon = match ($ext) { 'gpx' => '📍', 'kmz' => '🗺️', 'png','jpg','jpeg' => '🖼️', default => '📄' };
            ?>
                <a href="<?= SITE_URL ?>/charts/view.php?id=<?= $chart['id'] ?>" class="glass-card p-6 hover:border-[#c9a227]/50 transition-all flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-4xl"><?= $icon ?></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-white truncate"><?= sanitize($chart['region_name']) ?></h3>
                            <p class="text-xs text-gray-400"><?= strtoupper($ext) ?> · by <?= sanitize($chart['username']) ?></p>
                        </div>
                    </div>
                    <?php if ($chart['description']): ?>
                        <p class="text-gray-400 text-sm line-clamp-2"><?= sanitize($chart['description']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between text-xs text-gray-500 mt-auto">
                        <span>⬇ <?= number_format((int)$chart['download_count']) ?> downloads</span>
                        <span><?= timeAgo($chart['created_at']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_filter(['search'=>$search,'region'=>$region])) ?>&page=<?= $i ?>"
                       class="px-4 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-[#c9a227] text-[#0a1628] font-bold' : 'glass-card text-gray-300 hover:text-white' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
