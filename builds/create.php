<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'Start a Build Log';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title    = trim($_POST['title'] ?? '');
        $boatName = trim($_POST['boat_name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $progress = max(0, min(100, (int)($_POST['progress_percent'] ?? 0)));

        if (empty($title)) $errors[] = 'Title is required.';

        $coverImg = '';
        if (!empty($_FILES['cover_image']['name'])) {
            $saved = saveUploadedImage($_FILES['cover_image'], 'builds');
            if ($saved) $coverImg = $saved;
            else $errors[] = 'Invalid cover image.';
        }

        if (empty($errors)) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare('INSERT INTO build_logs (user_id, title, boat_name, description, progress_percent, cover_image) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssss', $userId, $title, $boatName, $desc, $progress, $coverImg);
            if ($stmt->execute()) {
                addReputation($userId, 10, $conn);
                $_SESSION['flash_success'] = 'Build log created!';
                redirect(SITE_URL . '/builds/view.php?id=' . $conn->insert_id);
            } else {
                $errors[] = 'Failed to create build log.';
            }
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Start a New Build Log</h1>

    <?php if ($errors): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="glass-card p-8 space-y-5">
        <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

        <div class="form-group">
            <label class="form-label">Build Title *</label>
            <input type="text" name="title" class="form-input" required maxlength="255"
                   placeholder="e.g. Restoring a 1972 Columbia 30" value="<?= sanitize($_POST['title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Boat Name</label>
            <input type="text" name="boat_name" class="form-input" maxlength="255"
                   placeholder="e.g. Sea Witch" value="<?= sanitize($_POST['boat_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input resize-none" rows="4" maxlength="2000"
                      placeholder="Overview of the project: what you're building or restoring, goals, timeline…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Initial Progress: <span id="progress-val"><?= (int)($_POST['progress_percent'] ?? 0) ?>%</span></label>
            <input type="range" name="progress_percent" id="progress-slider" class="progress-slider w-full"
                   min="0" max="100" value="<?= (int)($_POST['progress_percent'] ?? 0) ?>"
                   oninput="document.getElementById('progress-val').textContent = this.value + '%'">
        </div>

        <div class="form-group">
            <label class="form-label">Cover Image</label>
            <div class="border-2 border-dashed border-[#c9a227]/30 rounded-xl p-5 text-center hover:border-[#c9a227]/60 transition-colors cursor-pointer" onclick="document.getElementById('cover-img').click()">
                <div class="text-4xl mb-2">🖼️</div>
                <p class="text-gray-400 text-sm">Click to upload cover photo</p>
            </div>
            <input type="file" id="cover-img" name="cover_image" accept="image/*" class="hidden" onchange="previewImages(this, 'cover-preview', 1)">
            <div id="cover-preview" class="mt-2"></div>
        </div>

        <button type="submit" class="btn-gold w-full py-3 text-lg">Create Build Log 🔩</button>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
