<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/parts/');

$stmt = $conn->prepare('SELECT p.*, u.username, u.profile_img, u.reputation_points, u.boat_type FROM parts_listings p JOIN users u ON u.id = p.seller_id WHERE p.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
if (!$listing) { http_response_code(404); redirect(SITE_URL . '/parts/'); }

$pageTitle = sanitize($listing['title']);

// Similar listings
$cat = $listing['category'];
$similar = [];
if ($cat) {
    $sStmt = $conn->prepare("SELECT id, title, price, images_json FROM parts_listings WHERE category = ? AND id != ? AND status = 'active' LIMIT 4");
    $sStmt->bind_param('si', $cat, $id);
    $sStmt->execute();
    $similar = $sStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$images = json_decode($listing['images_json'] ?? '[]', true) ?: [];

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-6xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/parts/" class="hover:text-[#c9a227]">Parts</a> /
        <span class="text-white"><?= sanitize($listing['title']) ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Images -->
        <div>
            <div class="glass-card overflow-hidden rounded-xl aspect-square bg-[#0a1628] mb-3">
                <?php if ($images): ?>
                    <img id="main-img" src="<?= UPLOAD_URL . sanitize($images[0]) ?>" alt="<?= sanitize($listing['title']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-8xl">🔧</div>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="grid grid-cols-5 gap-2">
                    <?php foreach ($images as $img): ?>
                        <button onclick="document.getElementById('main-img').src='<?= UPLOAD_URL . sanitize($img) ?>'"
                                class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-[#c9a227] transition-colors">
                            <img src="<?= UPLOAD_URL . sanitize($img) ?>" class="w-full h-full object-cover" loading="lazy">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div>
            <div class="flex items-start justify-between gap-3 mb-4">
                <?php
                $condColor = match ($listing['condition']) {
                    'new','like_new' => 'bg-green-500/20 text-green-300 border-green-500/30',
                    'good' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                    'fair' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                    'poor' => 'bg-red-500/20 text-red-300 border-red-500/30',
                    default => 'bg-gray-500/20 text-gray-300',
                };
                ?>
                <span class="text-xs px-3 py-1 rounded-full border <?= $condColor ?>"><?= ucfirst(str_replace('_',' ',$listing['condition'])) ?></span>
                <?php if ($listing['category']): ?>
                    <span class="text-xs text-gray-500"><?= sanitize($listing['category']) ?></span>
                <?php endif; ?>
            </div>

            <h1 class="text-3xl font-bold text-white mb-3"><?= sanitize($listing['title']) ?></h1>
            <div class="text-4xl font-extrabold text-[#c9a227] mb-6"><?= formatPrice((float)$listing['price']) ?></div>

            <?php if ($listing['description']): ?>
                <div class="text-gray-300 leading-relaxed mb-6 whitespace-pre-wrap"><?= sanitize($listing['description']) ?></div>
            <?php endif; ?>

            <!-- Seller card -->
            <div class="glass-card p-4 mb-6">
                <div class="flex items-center gap-3">
                    <?php if ($listing['profile_img']): ?>
                        <img src="<?= UPLOAD_URL . sanitize($listing['profile_img']) ?>" class="w-12 h-12 rounded-full object-cover border border-[#c9a227]/50">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-[#1e3a5f] border border-[#c9a227]/50 flex items-center justify-center font-bold text-[#c9a227] text-xl">
                            <?= strtoupper(substr($listing['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="font-semibold text-white"><?= sanitize($listing['username']) ?></p>
                        <p class="text-xs text-gray-400"><?= sanitize($listing['boat_type'] ?? 'Boater') ?> · ⭐ <?= number_format((int)$listing['reputation_points']) ?> rep</p>
                    </div>
                </div>
            </div>

            <?php if (isLoggedIn() && $_SESSION['user_id'] != $listing['seller_id']): ?>
                <a href="<?= SITE_URL ?>/messages/conversation.php?user=<?= $listing['seller_id'] ?>&listing_type=part&listing_id=<?= $id ?>"
                   class="btn-gold w-full py-3 text-center block text-lg">💬 Contact Seller</a>
            <?php elseif (!isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/auth/login.php" class="btn-gold w-full py-3 text-center block text-lg">Login to Contact Seller</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/parts/sell.php" class="btn-outline w-full py-3 text-center block">Edit Listing</a>
            <?php endif; ?>

            <p class="text-gray-500 text-xs mt-3 text-center">Listed <?= timeAgo($listing['created_at']) ?></p>
        </div>
    </div>

    <!-- Similar listings -->
    <?php if ($similar): ?>
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-white mb-6">Similar Listings</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <?php foreach ($similar as $s):
                    $imgs = json_decode($s['images_json'] ?? '[]', true);
                    $src  = !empty($imgs) ? UPLOAD_URL . sanitize($imgs[0]) : null;
                ?>
                    <a href="<?= SITE_URL ?>/parts/view.php?id=<?= $s['id'] ?>" class="glass-card hover:border-[#c9a227]/50 transition-all">
                        <div class="aspect-square bg-[#0a1628] rounded-t-xl overflow-hidden">
                            <?php if ($src): ?>
                                <img src="<?= $src ?>" class="w-full h-full object-cover" loading="lazy">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-4xl">🔧</div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <p class="text-sm text-white font-medium line-clamp-2"><?= sanitize($s['title']) ?></p>
                            <p class="text-[#c9a227] font-bold mt-1"><?= formatPrice((float)$s['price']) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
