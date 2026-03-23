<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'List Your Boat';
$errors    = [];
$boatTypes = ['Sailboat','Motorboat','Catamaran','Trimaran','Trawler','Center Console','Inflatable/RIB','Houseboat'];
$fuelTypes = ['Diesel','Gasoline','Electric','Hybrid','Sail Only'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $title   = trim($_POST['title'] ?? '');
        $type    = trim($_POST['type'] ?? '');
        $length  = (float)($_POST['length'] ?? 0);
        $year    = (int)($_POST['year'] ?? 0);
        $price   = (float)($_POST['price'] ?? 0);
        $desc    = trim($_POST['description'] ?? '');

        if (empty($title))                     $errors[] = 'Title is required.';
        if ($price < 0)                        $errors[] = 'Price cannot be negative.';

        $specs = [
            'engine'    => trim($_POST['engine'] ?? ''),
            'fuel_type' => trim($_POST['fuel_type'] ?? ''),
            'draft'     => trim($_POST['draft'] ?? ''),
            'beam'      => trim($_POST['beam'] ?? ''),
            'cabins'    => trim($_POST['cabins'] ?? ''),
        ];

        $images = [];
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
                $saved = saveUploadedImage($file, 'boats');
                if ($saved) $images[] = $saved;
                if (count($images) >= 10) break;
            }
        }

        if (empty($errors)) {
            $userId     = $_SESSION['user_id'];
            $specsJson  = json_encode($specs);
            $imagesJson = json_encode($images);
            $yearVal    = $year > 0 ? $year : null;

            $stmt = $conn->prepare('INSERT INTO boat_listings (seller_id, title, type, length, year, price, description, specs_json, images_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issdidsss', $userId, $title, $type, $length, $yearVal, $price, $desc, $specsJson, $imagesJson);
            try {
                $stmt->execute();
                addReputation($userId, 10, $conn);
                $_SESSION['flash_success'] = 'Boat listed successfully!';
                redirect(SITE_URL . '/boats/view.php?id=' . $conn->insert_id);
            } catch (\mysqli_sql_exception $e) {
                error_log('Failed to create boat listing: ' . $e->getMessage());
                $errors[] = 'Failed to create listing. Please try again.';
            }
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">List Your Boat</h1>

    <?php if ($errors): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="glass-card p-8 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

        <div class="form-group">
            <label class="form-label">Listing Title *</label>
            <input type="text" name="title" class="form-input" required maxlength="255"
                   placeholder="e.g. 1987 Hans Christian 43 – Offshore Cruiser" value="<?= sanitize($_POST['title'] ?? '') ?>">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-input">
                    <option value="">Select…</option>
                    <?php foreach ($boatTypes as $bt): ?>
                        <option value="<?= sanitize($bt) ?>" <?= ($_POST['type'] ?? '') === $bt ? 'selected' : '' ?>><?= sanitize($bt) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Length (ft)</label>
                <input type="number" name="length" class="form-input" step="0.1" min="0" placeholder="43.0"
                       value="<?= sanitize($_POST['length'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Year</label>
                <input type="number" name="year" class="form-input" min="1900" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>"
                       value="<?= sanitize($_POST['year'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Price ($) *</label>
            <input type="number" name="price" class="form-input" step="0.01" min="0" required placeholder="85000"
                   value="<?= sanitize($_POST['price'] ?? '') ?>">
        </div>

        <fieldset class="border border-[#c9a227]/20 rounded-xl p-4">
            <legend class="text-sm text-[#c9a227] font-semibold px-2">Specifications</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
                <div class="form-group">
                    <label class="form-label">Engine</label>
                    <input type="text" name="engine" class="form-input" placeholder="Yanmar 4JH4-TE 54hp"
                           value="<?= sanitize($_POST['engine'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Fuel Type</label>
                    <select name="fuel_type" class="form-input">
                        <option value="">Select…</option>
                        <?php foreach ($fuelTypes as $ft): ?>
                            <option value="<?= sanitize($ft) ?>" <?= ($_POST['fuel_type'] ?? '') === $ft ? 'selected' : '' ?>><?= sanitize($ft) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Draft (ft)</label>
                    <input type="text" name="draft" class="form-input" placeholder="5.5"
                           value="<?= sanitize($_POST['draft'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Beam (ft)</label>
                    <input type="text" name="beam" class="form-input" placeholder="13.5"
                           value="<?= sanitize($_POST['beam'] ?? '') ?>">
                </div>
            </div>
        </fieldset>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input resize-none" rows="6" maxlength="5000"
                      placeholder="Full description of the boat, its history, recent work done, included equipment…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Photos (up to 10, max 10MB each)</label>
            <div class="border-2 border-dashed border-[#c9a227]/30 rounded-xl p-6 text-center hover:border-[#c9a227]/60 transition-colors cursor-pointer" onclick="document.getElementById('boat-images').click()">
                <div class="text-4xl mb-2">⛵</div>
                <p class="text-gray-400 text-sm">Click to upload boat photos</p>
            </div>
            <input type="file" id="boat-images" name="images[]" accept="image/*" multiple class="hidden" onchange="previewImages(this, 'boat-preview')">
            <div id="boat-preview" class="grid grid-cols-5 gap-2 mt-3"></div>
        </div>

        <button type="submit" class="btn-gold w-full py-3 text-lg">List Boat for Sale</button>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
