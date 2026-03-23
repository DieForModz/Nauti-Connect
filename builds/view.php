<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/builds/');

$stmt = $conn->prepare('SELECT b.*, u.username, u.id as owner_id FROM build_logs b JOIN users u ON u.id = b.user_id WHERE b.id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$build = $stmt->get_result()->fetch_assoc();
if (!$build) redirect(SITE_URL . '/builds/');

$pageTitle = sanitize($build['title']);
$isOwner   = isLoggedIn() && $_SESSION['user_id'] == $build['owner_id'];

// Get entries
$eStmt = $conn->prepare('SELECT * FROM build_log_entries WHERE log_id = ? ORDER BY entry_date DESC, id DESC');
$eStmt->bind_param('i', $id);
$eStmt->execute();
$entries = $eStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/builds/" class="hover:text-[#c9a227]">Builds</a> /
        <span class="text-white"><?= sanitize($build['title']) ?></span>
    </nav>

    <!-- Header card -->
    <div class="glass-card p-6 mb-8">
        <div class="flex flex-col sm:flex-row gap-5">
            <?php if ($build['cover_image']): ?>
                <img src="<?= UPLOAD_URL . sanitize($build['cover_image']) ?>" class="w-full sm:w-48 h-36 object-cover rounded-xl flex-shrink-0" alt="Cover">
            <?php endif; ?>
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-white"><?= sanitize($build['title']) ?></h1>
                <p class="text-[#c9a227] font-semibold mt-1">⛵ <?= sanitize($build['boat_name'] ?? 'Unknown Vessel') ?></p>
                <p class="text-gray-400 text-sm mt-1">by <?= sanitize($build['username']) ?> · Started <?= timeAgo($build['created_at']) ?></p>

                <?php if ($build['description']): ?>
                    <p class="text-gray-300 text-sm mt-3 leading-relaxed"><?= sanitize($build['description']) ?></p>
                <?php endif; ?>

                <!-- Progress bar -->
                <div class="mt-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-400">Overall Progress</span>
                        <span class="text-[#c9a227] font-bold"><?= (int)$build['progress_percent'] ?>%</span>
                    </div>
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill" style="width:<?= (int)$build['progress_percent'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php if ($isOwner): ?>
            <div class="mt-5 pt-5 border-t border-[#c9a227]/10 flex gap-3">
                <a href="<?= SITE_URL ?>/builds/entry.php?log_id=<?= $id ?>" class="btn-gold px-5 py-2">+ Add Entry</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Timeline entries -->
    <?php if (empty($entries)): ?>
        <div class="text-center py-16">
            <div class="text-5xl mb-4">📋</div>
            <p class="text-gray-400">No entries yet.</p>
            <?php if ($isOwner): ?>
                <a href="<?= SITE_URL ?>/builds/entry.php?log_id=<?= $id ?>" class="btn-gold mt-4 inline-block px-6 py-2">Add First Entry</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="relative">
            <!-- Timeline line -->
            <div class="absolute left-5 top-0 bottom-0 w-0.5 bg-[#c9a227]/20"></div>

            <div class="space-y-8 ml-12">
                <?php foreach ($entries as $entry):
                    $entryImages = json_decode($entry['images_json'] ?? '[]', true) ?: [];
                ?>
                    <div class="relative">
                        <!-- Timeline dot -->
                        <div class="absolute -left-12 top-4 w-5 h-5 rounded-full bg-[#c9a227] border-2 border-[#0a1628] flex items-center justify-center">
                            <div class="w-2 h-2 rounded-full bg-[#0a1628]"></div>
                        </div>

                        <div class="glass-card p-6">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[#c9a227] font-semibold"><?= date('M j, Y', strtotime($entry['entry_date'])) ?></span>
                                <?php if ($isOwner): ?>
                                    <a href="<?= SITE_URL ?>/builds/entry.php?log_id=<?= $id ?>&entry_id=<?= $entry['id'] ?>" class="text-xs text-gray-500 hover:text-[#c9a227]">Edit</a>
                                <?php endif; ?>
                            </div>
                            <div class="text-gray-300 text-sm leading-relaxed whitespace-pre-wrap"><?= sanitize($entry['content']) ?></div>

                            <?php if ($entryImages): ?>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4">
                                    <?php foreach ($entryImages as $img): ?>
                                        <a href="<?= UPLOAD_URL . sanitize($img) ?>" target="_blank" class="aspect-square rounded-lg overflow-hidden">
                                            <img src="<?= UPLOAD_URL . sanitize($img) ?>" class="w-full h-full object-cover hover:opacity-90 transition-opacity" loading="lazy">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
