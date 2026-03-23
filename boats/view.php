<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/boats/');

$stmt = $conn->prepare('SELECT b.*, u.username, u.profile_img, u.reputation_points FROM boat_listings b JOIN users u ON u.id = b.seller_id WHERE b.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$boat = $stmt->get_result()->fetch_assoc();
if (!$boat) redirect(SITE_URL . '/boats/');

$pageTitle = sanitize($boat['title']);
$images    = json_decode($boat['images_json'] ?? '[]', true) ?: [];
$specs     = json_decode($boat['specs_json'] ?? '[]', true) ?: [];

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-6xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/boats/" class="hover:text-[#c9a227]">Boats</a> /
        <span class="text-white"><?= sanitize($boat['title']) ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Images & main info (left 2/3) -->
        <div class="lg:col-span-2">
            <!-- Image gallery slider -->
            <div class="relative">
                <div class="aspect-video bg-[#0a1628] rounded-xl overflow-hidden border border-[#c9a227]/20 mb-3">
                    <?php if ($images): ?>
                        <img id="gallery-main" src="<?= UPLOAD_URL . sanitize($images[0]) ?>" alt="<?= sanitize($boat['title']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-9xl">⛵</div>
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <div class="grid grid-cols-6 gap-2">
                        <?php foreach ($images as $i => $img): ?>
                            <button onclick="document.getElementById('gallery-main').src='<?= UPLOAD_URL . sanitize($img) ?>'"
                                    class="aspect-square rounded-lg overflow-hidden border-2 border-transparent hover:border-[#c9a227] transition-colors">
                                <img src="<?= UPLOAD_URL . sanitize($img) ?>" class="w-full h-full object-cover" loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="glass-card p-6 mt-6">
                <h1 class="text-3xl font-bold text-white mb-2"><?= sanitize($boat['title']) ?></h1>
                <p class="text-4xl font-extrabold text-[#c9a227] mb-4"><?= formatPrice((float)$boat['price']) ?></p>

                <!-- Quick specs row -->
                <div class="flex flex-wrap gap-4 text-sm text-gray-300 mb-6 pb-6 border-b border-[#c9a227]/10">
                    <?php if ($boat['type']): ?><span class="flex items-center gap-1">⛵ <?= sanitize($boat['type']) ?></span><?php endif; ?>
                    <?php if ($boat['length']): ?><span><?= $boat['length'] ?>ft</span><?php endif; ?>
                    <?php if ($boat['year']): ?><span><?= $boat['year'] ?></span><?php endif; ?>
                </div>

                <!-- Specs table -->
                <?php if (!empty(array_filter($specs))): ?>
                    <h3 class="text-lg font-bold text-white mb-3">Specifications</h3>
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <?php
                        $specLabels = ['engine'=>'Engine','fuel_type'=>'Fuel','draft'=>'Draft','beam'=>'Beam','cabins'=>'Cabins'];
                        foreach ($specLabels as $key => $label):
                            if (empty($specs[$key])) continue;
                        ?>
                            <div class="bg-[#0a1628]/50 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-0.5"><?= $label ?></p>
                                <p class="text-white font-medium text-sm"><?= sanitize($specs[$key]) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if ($boat['description']): ?>
                    <h3 class="text-lg font-bold text-white mb-3">Description</h3>
                    <div class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap"><?= sanitize($boat['description']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5">
            <!-- Seller -->
            <div class="glass-card p-5">
                <h3 class="text-sm font-semibold text-gray-400 mb-3">Listed by</h3>
                <div class="flex items-center gap-3 mb-4">
                    <?php if ($boat['profile_img']): ?>
                        <img src="<?= UPLOAD_URL . sanitize($boat['profile_img']) ?>" class="w-12 h-12 rounded-full object-cover border border-[#c9a227]/50">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-[#1e3a5f] border border-[#c9a227]/50 flex items-center justify-center font-bold text-[#c9a227] text-xl">
                            <?= strtoupper(substr($boat['username'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="font-bold text-white"><?= sanitize($boat['username']) ?></p>
                        <p class="text-xs text-gray-400">⭐ <?= number_format((int)$boat['reputation_points']) ?> reputation</p>
                    </div>
                </div>

                <?php if (isLoggedIn() && $_SESSION['user_id'] != $boat['seller_id']): ?>
                    <a href="<?= SITE_URL ?>/messages/conversation.php?user=<?= $boat['seller_id'] ?>&listing_type=boat&listing_id=<?= $id ?>"
                       class="btn-gold w-full py-3 text-center block">💬 Contact Seller</a>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="btn-gold w-full py-3 text-center block">Login to Contact</a>
                <?php endif; ?>
            </div>

            <!-- Listing info -->
            <div class="glass-card p-5 text-sm">
                <div class="space-y-2 text-gray-400">
                    <div class="flex justify-between"><span>Listed</span><span><?= timeAgo($boat['created_at']) ?></span></div>
                    <div class="flex justify-between"><span>Status</span><span class="capitalize text-green-400"><?= sanitize($boat['status']) ?></span></div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
