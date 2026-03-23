<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(SITE_URL . '/maintenance/');

$stmt = $conn->prepare('SELECT * FROM maintenance_guides WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$guide = $stmt->get_result()->fetch_assoc();
if (!$guide) redirect(SITE_URL . '/maintenance/');

// Increment views
$updV = $conn->prepare('UPDATE maintenance_guides SET views = views + 1 WHERE id = ?');
$updV->bind_param('i', $id);
$updV->execute();

$pageTitle = sanitize($guide['title']);
$steps     = json_decode($guide['steps_json'] ?? '[]', true) ?: [];
$tools     = array_filter(array_map('trim', explode(',', $guide['tools_needed'] ?? '')));

$diffColor = match ($guide['difficulty_level']) {
    'beginner'     => 'bg-green-500/20 text-green-300 border-green-500/30',
    'intermediate' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
    'advanced'     => 'bg-red-500/20 text-red-300 border-red-500/30',
    default        => 'bg-gray-500/20 text-gray-300',
};
$catEmoji = match ($guide['category']) { 'engine'=>'⚙️','electrical'=>'⚡','hull'=>'🛥️','sails'=>'⛵',default=>'🔧' };

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/maintenance/" class="hover:text-[#c9a227]">Guides</a> /
        <a href="<?= SITE_URL ?>/maintenance/?category=<?= urlencode($guide['category']) ?>" class="hover:text-[#c9a227] capitalize"><?= sanitize($guide['category']) ?></a> /
        <span class="text-white"><?= sanitize($guide['title']) ?></span>
    </nav>

    <!-- Header -->
    <div class="glass-card p-6 mb-8">
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <span class="text-4xl"><?= $catEmoji ?></span>
            <span class="text-xs px-3 py-1 rounded-full border <?= $diffColor ?>"><?= ucfirst($guide['difficulty_level']) ?></span>
            <span class="text-xs text-gray-500 capitalize"><?= sanitize($guide['category']) ?></span>
            <span class="text-xs text-gray-500 ml-auto">👁 <?= number_format((int)$guide['views']) ?> views</span>
        </div>
        <h1 class="text-3xl font-bold text-white"><?= sanitize($guide['title']) ?></h1>
    </div>

    <!-- Tools needed -->
    <?php if (!empty($tools)): ?>
        <div class="glass-card p-5 mb-6">
            <h2 class="text-lg font-bold text-[#c9a227] mb-3">🛠 Tools &amp; Materials Needed</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($tools as $tool): ?>
                    <span class="bg-[#0a1628] border border-[#c9a227]/20 text-gray-300 text-sm px-3 py-1 rounded-lg"><?= sanitize($tool) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Video -->
    <?php if (!empty($guide['video_url'])): ?>
        <div class="glass-card p-5 mb-6">
            <h2 class="text-lg font-bold text-white mb-3">📹 Video Guide</h2>
            <a href="<?= sanitize($guide['video_url']) ?>" target="_blank" rel="noopener noreferrer"
               class="text-[#c9a227] hover:text-[#d4af37] text-sm break-all">▶ Watch Video →</a>
        </div>
    <?php endif; ?>

    <!-- Steps -->
    <?php if (!empty($steps)): ?>
        <div class="glass-card p-6 mb-8">
            <h2 class="text-xl font-bold text-white mb-5">Step-by-Step Instructions</h2>
            <ol class="space-y-6">
                <?php foreach ($steps as $i => $step): ?>
                    <li class="flex gap-4">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-[#c9a227] text-[#0a1628] font-bold text-sm flex items-center justify-center">
                            <?= $i + 1 ?>
                        </div>
                        <div class="flex-1 pt-1">
                            <p class="text-gray-200 leading-relaxed"><?= sanitize($step) ?></p>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="flex justify-between gap-4">
        <a href="<?= SITE_URL ?>/maintenance/?category=<?= urlencode($guide['category']) ?>" class="btn-outline px-5 py-2">← More <?= ucfirst($guide['category']) ?> Guides</a>
        <a href="<?= SITE_URL ?>/maintenance/" class="btn-gold px-5 py-2">All Guides</a>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
