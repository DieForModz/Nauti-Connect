<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'Upload Chart';
$regions   = ['Caribbean','Mediterranean','Pacific Northwest','Atlantic','Pacific','Indian Ocean','Other'];
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $regionName  = trim($_POST['region_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($regionName)) $errors[] = 'Region is required.';

        // Handle file upload
        $chartFile = '';
        $coordsJson = '[]';
        if (!empty($_FILES['chart_file']['name'])) {
            $file = $_FILES['chart_file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Upload error.';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $errors[] = 'File too large (max 10MB).';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($file['tmp_name']);
                $allowedMimes = [
                    'application/gpx+xml', 'application/xml', 'text/xml', 'text/plain',
                    'application/vnd.google-earth.kmz', 'application/zip',
                    'image/png', 'image/jpeg', 'image/webp',
                ];
                if (!in_array($mime, $allowedMimes, true)) {
                    $errors[] = 'Allowed file types: GPX, KMZ, PNG, JPEG, WEBP.';
                } else {
                    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $ext  = in_array($ext, ['gpx','kmz','png','jpg','jpeg','webp']) ? $ext : 'dat';
                    $dir  = UPLOAD_PATH . 'charts/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    $filename  = bin2hex(random_bytes(16)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                        $chartFile = 'charts/' . $filename;
                        // Parse GPX for coordinates
                        if ($ext === 'gpx') {
                            try {
                                $xml = simplexml_load_file($dir . $filename);
                                $coords = [];
                                foreach ($xml->wpt ?? [] as $wpt) {
                                    $coords[] = ['lat' => (float)$wpt['lat'], 'lng' => (float)$wpt['lon'], 'name' => (string)($wpt->name ?? '')];
                                }
                                foreach ($xml->trk ?? [] as $trk) {
                                    foreach ($trk->trkseg ?? [] as $seg) {
                                        foreach ($seg->trkpt ?? [] as $pt) {
                                            $coords[] = ['lat' => (float)$pt['lat'], 'lng' => (float)$pt['lon']];
                                        }
                                    }
                                }
                                $coordsJson = json_encode(array_slice($coords, 0, 500));
                            } catch (Exception $e) {
                                // Ignore parse errors
                            }
                        }
                    } else {
                        $errors[] = 'Failed to save file.';
                    }
                }
            }
        }

        if (empty($errors)) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare('INSERT INTO chart_shares (user_id, region_name, chart_file, coordinates_json, description) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $userId, $regionName, $chartFile, $coordsJson, $description);
            if ($stmt->execute()) {
                addReputation($userId, 10, $conn);
                $_SESSION['flash_success'] = 'Chart shared successfully!';
                redirect(SITE_URL . '/charts/view.php?id=' . $conn->insert_id);
            } else {
                $errors[] = 'Failed to save chart.';
            }
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-2xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Share a Chart</h1>

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
            <label class="form-label">Region *</label>
            <select name="region_name" class="form-input" required>
                <option value="">Select region…</option>
                <?php foreach ($regions as $r): ?>
                    <option value="<?= sanitize($r) ?>" <?= ($_POST['region_name'] ?? '') === $r ? 'selected' : '' ?>><?= sanitize($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Chart File (GPX, KMZ, or image) *</label>
            <div class="border-2 border-dashed border-[#c9a227]/30 rounded-xl p-6 text-center hover:border-[#c9a227]/60 transition-colors cursor-pointer" onclick="document.getElementById('chart_file').click()">
                <div class="text-4xl mb-2">📁</div>
                <p class="text-gray-400 text-sm" id="file-name-display">Click to select a file</p>
                <p class="text-gray-600 text-xs mt-1">GPX, KMZ, PNG, JPEG, WEBP (max 10MB)</p>
            </div>
            <input type="file" id="chart_file" name="chart_file" accept=".gpx,.kmz,.png,.jpg,.jpeg,.webp" class="hidden" onchange="document.getElementById('file-name-display').textContent = this.files[0]?.name || 'No file chosen'" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input resize-none" rows="4" maxlength="1000"
                      placeholder="Describe this chart: coverage area, year, source, any special notes…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-gold w-full py-3 text-lg">Share Chart</button>
    </form>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
