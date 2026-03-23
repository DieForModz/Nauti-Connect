<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle  = 'Sell a Part';
$errors     = [];
$categories = ['Engine & Drivetrain','Sails & Rigging','Electronics','Deck Hardware','Safety Equipment','Navigation','Anchoring','Interior','Other'];
$conditions = ['new'=>'New','like_new'=>'Like New','good'=>'Good','fair'=>'Fair','poor'=>'Poor'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $condition   = $_POST['condition'] ?? '';
        $category    = trim($_POST['category'] ?? '');
        $status      = $_POST['status'] ?? 'active';

        if (empty($title))                          $errors[] = 'Title is required.';
        if (!array_key_exists($condition, $conditions)) $errors[] = 'Invalid condition.';
        if ($price < 0)                             $errors[] = 'Price cannot be negative.';

        $uploadedImages = [];
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
                $saved = saveUploadedImage($file, 'parts');
                if ($saved) $uploadedImages[] = $saved;
                if (count($uploadedImages) >= 5) break;
            }
        }

        if (empty($errors)) {
            $imagesJson = json_encode($uploadedImages);
            $userId     = $_SESSION['user_id'];
            $stmt = $conn->prepare('INSERT INTO parts_listings (seller_id, title, description, price, `condition`, category, images_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issdssss', $userId, $title, $description, $price, $condition, $category, $imagesJson, $status);
            if ($stmt->execute()) {
                addReputation($userId, 5, $conn);
                $_SESSION['flash_success'] = 'Listing created successfully!';
                redirect(SITE_URL . '/parts/view.php?id=' . $conn->insert_id);
            } else {
                $errors[] = 'Failed to create listing.';
            }
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Sell a Part or Gear</h1>

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
            <label class="form-label">Title *</label>
            <input type="text" name="title" class="form-input" required maxlength="255"
                   placeholder="e.g. Lewmar 66 Self-Tailing Winch" value="<?= sanitize($_POST['title'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input resize-none" rows="5" maxlength="3000"
                      placeholder="Describe condition, history, dimensions, reason for selling…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="form-group">
                <label class="form-label">Price ($)</label>
                <input type="number" name="price" class="form-input" step="0.01" min="0" placeholder="0.00"
                       value="<?= sanitize($_POST['price'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Condition *</label>
                <select name="condition" class="form-input" required>
                    <option value="">Select…</option>
                    <?php foreach ($conditions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= ($_POST['condition'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Listing Type</label>
                <select name="status" class="form-input">
                    <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>For Sale</option>
                    <option value="wanted" <?= ($_POST['status'] ?? '') === 'wanted' ? 'selected' : '' ?>>Wanted</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category" class="form-input">
                <option value="">Select category…</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= sanitize($cat) ?>" <?= ($_POST['category'] ?? '') === $cat ? 'selected' : '' ?>><?= sanitize($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Photos (up to 5, max 10MB each)</label>
            <div class="border-2 border-dashed border-[#c9a227]/30 rounded-xl p-6 text-center hover:border-[#c9a227]/60 transition-colors cursor-pointer" onclick="document.getElementById('images').click()">
                <div class="text-4xl mb-2">📷</div>
                <p class="text-gray-400 text-sm">Click to upload images</p>
                <p class="text-gray-600 text-xs mt-1">JPEG, PNG, WEBP, GIF</p>
            </div>
            <input type="file" id="images" name="images[]" accept="image/*" multiple class="hidden" onchange="previewImages(this, 'image-preview')">
            <div id="image-preview" class="grid grid-cols-5 gap-2 mt-3"></div>
        </div>

        <button type="submit" class="btn-gold w-full py-3 text-lg">Create Listing</button>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
