<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Marine Parts Marketplace';

$search    = trim($_GET['search'] ?? '');
$category  = trim($_GET['category'] ?? '');
$condition = trim($_GET['condition'] ?? '');
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$conditions = ['new', 'like_new', 'good', 'fair', 'poor'];
$categories = ['Engine & Drivetrain','Sails & Rigging','Electronics','Deck Hardware','Safety Equipment','Navigation','Anchoring','Interior','Other'];

// Build query
$where  = ["p.status = 'active'"];
$params = [];
$types  = '';

if ($search !== '') {
    $where[]  = '(p.title LIKE ? OR p.description LIKE ?)';
    $like      = '%' . $search . '%';
    $params[]  = $like;
    $params[]  = $like;
    $types    .= 'ss';
}
if ($category !== '') {
    $where[]  = 'p.category = ?';
    $params[]  = $category;
    $types    .= 's';
}
if ($condition !== '' && in_array($condition, $conditions, true)) {
    $where[]  = 'p.condition = ?';
    $params[]  = $condition;
    $types    .= 's';
}

$whereClause = implode(' AND ', $where);
$countSql    = "SELECT COUNT(*) as cnt FROM parts_listings p WHERE $whereClause";
$sql         = "SELECT p.*, u.username, u.profile_img FROM parts_listings p
                JOIN users u ON u.id = p.seller_id
                WHERE $whereClause ORDER BY p.created_at DESC LIMIT ? OFFSET ?";

if ($params) {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['cnt'];

    $stmt = $conn->prepare($sql);
    $allTypes = $types . 'ii';
    $allParams = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($allTypes, ...$allParams);
} else {
    $countStmt = $conn->query($countSql);
    $total     = (int)$countStmt->fetch_assoc()['cnt'];
    $stmt      = $conn->prepare($sql);
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = (int)ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Parts &amp; Gear Marketplace</h1>
            <p class="text-gray-400 mt-1"><?= number_format($total) ?> listing<?= $total !== 1 ? 's' : '' ?> available</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/parts/sell.php" class="btn-gold px-6 py-2">+ Sell a Part</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <form method="GET" class="glass-card p-4 mb-8 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <input type="text" name="search" class="form-input col-span-1 sm:col-span-2" placeholder="Search parts…" value="<?= sanitize($search) ?>">
        <select name="category" class="form-input">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= sanitize($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= sanitize($cat) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="flex gap-2">
            <select name="condition" class="form-input flex-1">
                <option value="">Any Condition</option>
                <?php foreach ($conditions as $cond): ?>
                    <option value="<?= $cond ?>" <?= $condition === $cond ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$cond)) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-gold px-4">Go</button>
        </div>
    </form>

    <!-- Grid -->
    <?php if (empty($listings)): ?>
        <div class="text-center py-20">
            <div class="text-6xl mb-4">🔧</div>
            <p class="text-gray-400 text-lg">No listings found. <a href="<?= SITE_URL ?>/parts/sell.php" class="text-[#c9a227]">Be the first to sell!</a></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($listings as $listing):
                $images = json_decode($listing['images_json'] ?? '[]', true);
                $imgSrc = !empty($images) ? UPLOAD_URL . sanitize($images[0]) : null;
                $condColor = match ($listing['condition']) {
                    'new','like_new' => 'bg-green-500/20 text-green-300 border-green-500/30',
                    'good' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                    'fair' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                    'poor' => 'bg-red-500/20 text-red-300 border-red-500/30',
                    default => 'bg-gray-500/20 text-gray-300 border-gray-500/30',
                };
            ?>
                <a href="<?= SITE_URL ?>/parts/view.php?id=<?= $listing['id'] ?>" class="glass-card hover:border-[#c9a227]/50 transition-all flex flex-col group">
                    <div class="aspect-square bg-[#0a1628] rounded-t-xl overflow-hidden">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= sanitize($listing['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-6xl">🔧</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <span class="text-[#c9a227] font-bold text-xl mb-1"><?= formatPrice((float)$listing['price']) ?></span>
                        <h3 class="font-semibold text-white text-sm mb-2 line-clamp-2"><?= sanitize($listing['title']) ?></h3>
                        <div class="flex items-center gap-2 flex-wrap mb-3">
                            <span class="text-xs px-2 py-0.5 rounded-full border <?= $condColor ?>"><?= ucfirst(str_replace('_',' ',$listing['condition'])) ?></span>
                            <?php if ($listing['category']): ?>
                                <span class="text-xs text-gray-500"><?= sanitize($listing['category']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-auto flex items-center gap-2 text-xs text-gray-400">
                            <span>by <?= sanitize($listing['username']) ?></span>
                            <span class="ml-auto"><?= timeAgo($listing['created_at']) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php
                $qBase = http_build_query(array_filter(['search'=>$search,'category'=>$category,'condition'=>$condition]));
                for ($i = 1; $i <= $totalPages; $i++):
                    $active = $i === $page;
                ?>
                    <a href="?<?= $qBase ?>&page=<?= $i ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition-all <?= $active ? 'bg-[#c9a227] text-[#0a1628]' : 'glass-card text-gray-300 hover:text-white' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
