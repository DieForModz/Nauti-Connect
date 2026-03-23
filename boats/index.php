<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Boats for Sale';

$type      = trim($_GET['type'] ?? '');
$lenMin    = (float)($_GET['len_min'] ?? 0);
$lenMax    = (float)($_GET['len_max'] ?? 0);
$yearMin   = (int)($_GET['year_min'] ?? 0);
$yearMax   = (int)($_GET['year_max'] ?? 0);
$priceMax  = (float)($_GET['price_max'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$offset    = ($page - 1) * $perPage;

$boatTypes = ['Sailboat','Motorboat','Catamaran','Trimaran','Trawler','Center Console','Inflatable/RIB','Houseboat'];

$where  = ["b.status = 'active'"];
$params = [];
$types  = '';

if ($type !== '') {
    $where[]  = 'b.type = ?';
    $params[]  = $type;
    $types    .= 's';
}
if ($lenMin > 0) { $where[] = 'b.length >= ?'; $params[] = $lenMin; $types .= 'd'; }
if ($lenMax > 0) { $where[] = 'b.length <= ?'; $params[] = $lenMax; $types .= 'd'; }
if ($yearMin > 0) { $where[] = 'b.year >= ?'; $params[] = $yearMin; $types .= 'i'; }
if ($yearMax > 0) { $where[] = 'b.year <= ?'; $params[] = $yearMax; $types .= 'i'; }
if ($priceMax > 0) { $where[] = 'b.price <= ?'; $params[] = $priceMax; $types .= 'd'; }

$whereClause = implode(' AND ', $where);
$countSql    = "SELECT COUNT(*) as cnt FROM boat_listings b WHERE $whereClause";
$sql         = "SELECT b.*, u.username FROM boat_listings b JOIN users u ON u.id = b.seller_id WHERE $whereClause ORDER BY b.created_at DESC LIMIT ? OFFSET ?";

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
$boats      = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = (int)ceil($total / $perPage);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-white">Boats for Sale</h1>
            <p class="text-gray-400 mt-1"><?= number_format($total) ?> vessels listed</p>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="<?= SITE_URL ?>/boats/sell.php" class="btn-gold px-6 py-2">+ List Your Boat</a>
        <?php endif; ?>
    </div>

    <!-- Type filters -->
    <div class="flex gap-2 flex-wrap mb-4">
        <a href="?" class="filter-btn <?= !$type ? 'active' : '' ?>">All Types</a>
        <?php foreach ($boatTypes as $bt): ?>
            <a href="?type=<?= urlencode($bt) ?>" class="filter-btn <?= $type === $bt ? 'active' : '' ?>"><?= sanitize($bt) ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Advanced filters -->
    <form method="GET" class="glass-card p-4 mb-8 grid grid-cols-2 sm:grid-cols-5 gap-3">
        <input type="hidden" name="type" value="<?= sanitize($type) ?>">
        <input type="number" name="len_min" class="form-input" placeholder="Min length (ft)" value="<?= $lenMin ?: '' ?>" min="0" step="0.1">
        <input type="number" name="len_max" class="form-input" placeholder="Max length (ft)" value="<?= $lenMax ?: '' ?>" min="0" step="0.1">
        <input type="number" name="year_min" class="form-input" placeholder="Min year" value="<?= $yearMin ?: '' ?>" min="1900" max="<?= date('Y') ?>">
        <input type="number" name="year_max" class="form-input" placeholder="Max year" value="<?= $yearMax ?: '' ?>" min="1900" max="<?= date('Y') ?>">
        <div class="flex gap-2">
            <input type="number" name="price_max" class="form-input flex-1" placeholder="Max price $" value="<?= $priceMax ?: '' ?>" min="0">
            <button type="submit" class="btn-gold px-4">Go</button>
        </div>
    </form>

    <!-- Boat cards -->
    <?php if (empty($boats)): ?>
        <div class="text-center py-20">
            <div class="text-6xl mb-4">⛵</div>
            <p class="text-gray-400 text-lg">No boats listed yet. <a href="<?= SITE_URL ?>/boats/sell.php" class="text-[#c9a227]">List yours!</a></p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($boats as $boat):
                $images = json_decode($boat['images_json'] ?? '[]', true) ?: [];
                $imgSrc = !empty($images) ? UPLOAD_URL . sanitize($images[0]) : null;
            ?>
                <a href="<?= SITE_URL ?>/boats/view.php?id=<?= $boat['id'] ?>" class="glass-card hover:border-[#c9a227]/50 transition-all group overflow-hidden">
                    <div class="aspect-video bg-[#0a1628] overflow-hidden">
                        <?php if ($imgSrc): ?>
                            <img src="<?= $imgSrc ?>" alt="<?= sanitize($boat['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-7xl">⛵</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-bold text-white text-lg leading-tight"><?= sanitize($boat['title']) ?></h3>
                            <span class="text-[#c9a227] font-extrabold text-lg whitespace-nowrap"><?= formatPrice((float)$boat['price']) ?></span>
                        </div>
                        <div class="flex gap-3 text-sm text-gray-400 flex-wrap">
                            <?php if ($boat['type']): ?><span><?= sanitize($boat['type']) ?></span><?php endif; ?>
                            <?php if ($boat['length']): ?><span><?= $boat['length'] ?>ft</span><?php endif; ?>
                            <?php if ($boat['year']): ?><span><?= $boat['year'] ?></span><?php endif; ?>
                        </div>
                        <p class="text-gray-500 text-xs mt-2">by <?= sanitize($boat['username']) ?> · <?= timeAgo($boat['created_at']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center gap-2 mt-10">
                <?php
                $q = http_build_query(array_filter(compact('type','lenMin','lenMax','yearMin','yearMax','priceMax')));
                for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= $q ?>&page=<?= $i ?>"
                       class="px-4 py-2 rounded-lg text-sm <?= $i === $page ? 'bg-[#c9a227] text-[#0a1628] font-bold' : 'glass-card text-gray-300 hover:text-white' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
