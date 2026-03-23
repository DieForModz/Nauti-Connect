<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Maintenance Guides';

$category = trim($_GET['category'] ?? '');
$search   = trim($_GET['search'] ?? '');
$validCats = ['engine','electrical','hull','sails','other'];

$where  = ['1=1'];
$params = [];
$types  = '';

if ($category !== '' && in_array($category, $validCats, true)) {
    $where[]  = 'category = ?';
    $params[]  = $category;
    $types    .= 's';
}
if ($search !== '') {
    $where[]  = 'MATCH(title, tools_needed) AGAINST (? IN BOOLEAN MODE)';
    $params[]  = $search . '*';
    $types    .= 's';
}

$whereClause = implode(' AND ', $where);
$sql         = "SELECT * FROM maintenance_guides WHERE $whereClause ORDER BY views DESC, created_at DESC LIMIT 50";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
} else {
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$guides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Featured: top 3 by views
$featuredStmt = $conn->query('SELECT * FROM maintenance_guides ORDER BY views DESC LIMIT 3');
$featured     = $featuredStmt->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-white mb-2">Maintenance Guide Library</h1>
        <p class="text-gray-400">Step-by-step guides for all your boating maintenance needs</p>
    </div>

    <!-- Featured guides -->
    <?php if (!$category && !$search && !empty($featured)): ?>
        <div class="mb-10">
            <h2 class="text-xl font-bold text-[#c9a227] mb-4">⭐ Most Viewed Guides</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <?php foreach ($featured as $guide):
                    $diffColor = match ($guide['difficulty_level']) {
                        'beginner' => 'bg-green-500/20 text-green-300 border-green-500/30',
                        'intermediate' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                        'advanced' => 'bg-red-500/20 text-red-300 border-red-500/30',
                        default => 'bg-gray-500/20 text-gray-300',
                    };
                    $catEmoji = match ($guide['category']) { 'engine'=>'⚙️','electrical'=>'⚡','hull'=>'🛥️','sails'=>'⛵','other'=>'🔧',default=>'📋' };
                ?>
                    <a href="<?= SITE_URL ?>/maintenance/view.php?id=<?= $guide['id'] ?>" class="glass-card p-5 hover:border-[#c9a227]/50 transition-all">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl"><?= $catEmoji ?></span>
                            <span class="text-xs px-2 py-0.5 rounded-full border <?= $diffColor ?>"><?= ucfirst($guide['difficulty_level']) ?></span>
                        </div>
                        <h3 class="font-bold text-white leading-tight"><?= sanitize($guide['title']) ?></h3>
                        <p class="text-gray-500 text-xs mt-2">👁 <?= number_format((int)$guide['views']) ?> views</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Search + category filter -->
    <form method="GET" id="guide-search-form" class="glass-card p-4 mb-6 flex flex-wrap gap-3">
        <input type="text" name="search" id="guide-search" class="form-input flex-1 min-w-48" placeholder="Search guides…" value="<?= sanitize($search) ?>">
        <input type="hidden" name="category" value="<?= sanitize($category) ?>">
        <button type="submit" class="btn-gold px-6">Search</button>
        <?php if ($search): ?>
            <a href="?category=<?= urlencode($category) ?>" class="btn-outline px-4">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Category tabs -->
    <div class="flex gap-2 flex-wrap mb-6">
        <a href="?" class="filter-btn <?= !$category ? 'active' : '' ?>">All</a>
        <?php
        $catLabels = ['engine'=>'⚙️ Engine','electrical'=>'⚡ Electrical','hull'=>'🛥️ Hull','sails'=>'⛵ Sails','other'=>'🔧 Other'];
        foreach ($catLabels as $val => $label): ?>
            <a href="?category=<?= $val ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="filter-btn <?= $category === $val ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Guides grid -->
    <?php if (empty($guides)): ?>
        <div class="text-center py-16">
            <div class="text-5xl mb-4">🔧</div>
            <p class="text-gray-400">No guides found. Try a different search.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5" id="guides-grid">
            <?php foreach ($guides as $guide):
                $diffColor = match ($guide['difficulty_level']) {
                    'beginner' => 'bg-green-500/20 text-green-300 border-green-500/30',
                    'intermediate' => 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
                    'advanced' => 'bg-red-500/20 text-red-300 border-red-500/30',
                    default => 'bg-gray-500/20 text-gray-300',
                };
                $catEmoji = match ($guide['category']) { 'engine'=>'⚙️','electrical'=>'⚡','hull'=>'🛥️','sails'=>'⛵','other'=>'🔧',default=>'📋' };
                $steps = json_decode($guide['steps_json'] ?? '[]', true) ?: [];
            ?>
                <a href="<?= SITE_URL ?>/maintenance/view.php?id=<?= $guide['id'] ?>" class="glass-card p-5 hover:border-[#c9a227]/50 transition-all flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-3xl"><?= $catEmoji ?></span>
                        <span class="text-xs px-2 py-0.5 rounded-full border <?= $diffColor ?>"><?= ucfirst($guide['difficulty_level']) ?></span>
                    </div>
                    <h3 class="font-bold text-white leading-tight mb-2"><?= sanitize($guide['title']) ?></h3>
                    <?php if ($guide['tools_needed']): ?>
                        <p class="text-gray-400 text-xs mb-2 line-clamp-1">🔧 <?= sanitize($guide['tools_needed']) ?></p>
                    <?php endif; ?>
                    <div class="mt-auto pt-2 border-t border-[#c9a227]/10 flex items-center justify-between text-xs text-gray-500">
                        <span><?= count($steps) ?> steps</span>
                        <span>👁 <?= number_format((int)$guide['views']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- AI Chat Widget -->
    <div class="mt-16 glass-card p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-[#c9a227]/20 border border-[#c9a227]/40 flex items-center justify-center text-xl">🤖</div>
            <div>
                <h2 class="text-xl font-bold text-white">AI Maintenance Assistant</h2>
                <p class="text-gray-400 text-sm">Ask any maintenance question</p>
            </div>
        </div>

        <div id="ai-chat-log" class="bg-[#0a1628]/60 rounded-xl p-4 min-h-32 max-h-64 overflow-y-auto mb-4 space-y-3">
            <div class="text-gray-500 text-sm text-center">Ask a question to get maintenance guidance from our guide library.</div>
        </div>

        <div class="flex gap-3">
            <input type="text" id="ai-input" class="form-input flex-1" placeholder="e.g. How do I winterize my engine?" maxlength="500">
            <button id="ai-send" onclick="sendAIMessage()" class="btn-gold px-6">Ask</button>
        </div>
    </div>
</main>

<script>
async function sendAIMessage() {
    const input = document.getElementById('ai-input');
    const log   = document.getElementById('ai-chat-log');
    const query = input.value.trim();
    if (!query) return;

    // User bubble
    log.innerHTML += `<div class="flex justify-end"><div class="bg-[#c9a227]/20 text-white text-sm px-3 py-2 rounded-xl max-w-xs border border-[#c9a227]/30">${escHtml(query)}</div></div>`;
    input.value = '';
    log.scrollTop = log.scrollHeight;

    // Loading
    const loadId = 'ai-load-' + Date.now();
    log.innerHTML += `<div id="${loadId}" class="flex justify-start"><div class="bg-[#1e3a5f]/60 text-gray-300 text-sm px-3 py-2 rounded-xl">Searching guides…</div></div>`;
    log.scrollTop = log.scrollHeight;

    try {
        const res = await fetch('<?= SITE_URL ?>/api/ai-chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= generateCSRF() ?>' },
            body: JSON.stringify({ query })
        });
        const data = await res.json();
        document.getElementById(loadId)?.remove();
        const text = data.response || 'I could not find a matching guide. Try browsing the guide library above.';
        log.innerHTML += `<div class="flex justify-start"><div class="bg-[#1e3a5f]/60 text-gray-200 text-sm px-3 py-2 rounded-xl max-w-sm">${escHtml(text)}</div></div>`;
    } catch (e) {
        document.getElementById(loadId)?.remove();
        log.innerHTML += `<div class="flex justify-start"><div class="bg-red-500/20 text-red-300 text-sm px-3 py-2 rounded-xl">Error connecting. Please try again.</div></div>`;
    }
    log.scrollTop = log.scrollHeight;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('ai-input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendAIMessage();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
