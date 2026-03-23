<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$logId   = (int)($_GET['log_id'] ?? 0);
$entryId = (int)($_GET['entry_id'] ?? 0);

if (!$logId) redirect(SITE_URL . '/builds/');

// Verify ownership
$stmt = $conn->prepare('SELECT * FROM build_logs WHERE id = ? AND user_id = ?');
$stmt->bind_param('ii', $logId, $_SESSION['user_id']);
$stmt->execute();
$log = $stmt->get_result()->fetch_assoc();
if (!$log) redirect(SITE_URL . '/builds/');

// Load existing entry if editing
$entry  = null;
if ($entryId > 0) {
    $eStmt = $conn->prepare('SELECT * FROM build_log_entries WHERE id = ? AND log_id = ?');
    $eStmt->bind_param('ii', $entryId, $logId);
    $eStmt->execute();
    $entry = $eStmt->get_result()->fetch_assoc();
}

$pageTitle = $entry ? 'Edit Build Entry' : 'Add Build Entry';
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $entryDate = trim($_POST['entry_date'] ?? '');
        $content   = trim($_POST['content'] ?? '');
        $progress  = max(0, min(100, (int)($_POST['progress_percent'] ?? (int)$log['progress_percent'])));

        if (empty($content))   $errors[] = 'Entry content is required.';
        if (empty($entryDate)) $errors[] = 'Date is required.';

        $imgPaths = $entry ? (json_decode($entry['images_json'] ?? '[]', true) ?: []) : [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['images']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                $file = [
                    'tmp_name' => $tmpName,
                    'name'     => $_FILES['images']['name'][$idx],
                    'size'     => $_FILES['images']['size'][$idx],
                    'error'    => $_FILES['images']['error'][$idx],
                    'type'     => $_FILES['images']['type'][$idx],
                ];
                $saved = saveUploadedImage($file, 'builds');
                if ($saved) $imgPaths[] = $saved;
                if (count($imgPaths) >= 8) break;
            }
        }

        if (empty($errors)) {
            $imagesJson = json_encode($imgPaths);
            if ($entry) {
                $upd = $conn->prepare('UPDATE build_log_entries SET entry_date = ?, content = ?, images_json = ? WHERE id = ? AND log_id = ?');
                $upd->bind_param('sssii', $entryDate, $content, $imagesJson, $entryId, $logId);
                $upd->execute();
            } else {
                $ins = $conn->prepare('INSERT INTO build_log_entries (log_id, entry_date, content, images_json) VALUES (?, ?, ?, ?)');
                $ins->bind_param('isss', $logId, $entryDate, $content, $imagesJson);
                $ins->execute();
            }
            // Update progress
            $updLog = $conn->prepare('UPDATE build_logs SET progress_percent = ? WHERE id = ?');
            $updLog->bind_param('ii', $progress, $logId);
            $updLog->execute();

            $_SESSION['flash_success'] = 'Entry saved!';
            redirect(SITE_URL . '/builds/view.php?id=' . $logId);
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-2xl mx-auto px-4 py-10">
    <nav class="text-sm text-gray-500 mb-6">
        <a href="<?= SITE_URL ?>/builds/" class="hover:text-[#c9a227]">Builds</a> /
        <a href="<?= SITE_URL ?>/builds/view.php?id=<?= $logId ?>" class="hover:text-[#c9a227]"><?= sanitize($log['title']) ?></a> /
        <span class="text-white"><?= $entry ? 'Edit Entry' : 'New Entry' ?></span>
    </nav>

    <h1 class="text-3xl font-bold text-white mb-8"><?= $entry ? 'Edit Entry' : 'Add Build Entry' ?></h1>

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
            <label class="form-label">Entry Date *</label>
            <input type="date" name="entry_date" class="form-input" required
                   value="<?= sanitize($_POST['entry_date'] ?? ($entry['entry_date'] ?? date('Y-m-d'))) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Entry Content *</label>
            <textarea name="content" class="form-input resize-none" rows="8" required maxlength="5000"
                      placeholder="Describe what you worked on today, progress made, challenges encountered…"><?= sanitize($_POST['content'] ?? ($entry['content'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Progress: <span id="ep-val"><?= (int)($_POST['progress_percent'] ?? $log['progress_percent']) ?>%</span></label>
            <input type="range" name="progress_percent" id="ep-slider" class="progress-slider w-full"
                   min="0" max="100" value="<?= (int)($_POST['progress_percent'] ?? $log['progress_percent']) ?>"
                   oninput="document.getElementById('ep-val').textContent = this.value + '%'">
        </div>

        <div class="form-group">
            <label class="form-label">Photos</label>
            <input type="file" name="images[]" accept="image/*" multiple class="form-input" onchange="previewImages(this, 'entry-preview')">
            <div id="entry-preview" class="grid grid-cols-4 gap-2 mt-2"></div>
            <?php if ($entry && !empty(json_decode($entry['images_json'] ?? '[]', true))): ?>
                <p class="text-gray-500 text-xs mt-1">Existing photos will be kept. New uploads are added.</p>
            <?php endif; ?>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-gold flex-1 py-3">Save Entry</button>
            <a href="<?= SITE_URL ?>/builds/view.php?id=<?= $logId ?>" class="btn-outline flex-1 py-3 text-center">Cancel</a>
        </div>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
