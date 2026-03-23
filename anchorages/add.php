<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle = 'Add Anchorage';
$errors    = [];
$prefillLat = (float)($_GET['lat'] ?? 0);
$prefillLng = (float)($_GET['lng'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name       = trim($_POST['name'] ?? '');
        $lat        = (float)($_POST['lat'] ?? 0);
        $lng        = (float)($_POST['lng'] ?? 0);
        $depth      = $_POST['depth'] !== '' ? (float)$_POST['depth'] : null;
        $holding    = $_POST['holding_quality'] ?? '';
        $protection = (int)($_POST['protection_rating'] ?? 0);
        $review     = trim($_POST['review_text'] ?? '');

        $validHolding = ['excellent','good','fair','poor'];

        if (empty($name))                                $errors[] = 'Name is required.';
        if ($lat < -90 || $lat > 90)                    $errors[] = 'Invalid latitude.';
        if ($lng < -180 || $lng > 180)                  $errors[] = 'Invalid longitude.';
        if ($holding && !in_array($holding, $validHolding, true)) $errors[] = 'Invalid holding quality.';
        if ($protection < 1 || $protection > 5)         $protection = null;

        if (empty($errors)) {
            $userId    = $_SESSION['user_id'];
            $depthVal  = is_null($depth) ? null : (float)$depth;
            $protVal   = is_null($protection) ? null : (int)$protection;
            $stmt = $conn->prepare('INSERT INTO anchorages (user_id, name, lat, lng, depth, holding_quality, protection_rating, review_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isdddsis', $userId, $name, $lat, $lng, $depthVal, $holding, $protVal, $review);
            if ($stmt->execute()) {
                addReputation($userId, 10, $conn);
                $_SESSION['flash_success'] = 'Anchorage added!';
                redirect(SITE_URL . '/anchorages/view.php?id=' . $conn->insert_id);
            } else {
                $errors[] = 'Failed to save.';
            }
        }
    }
}

$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Add Anchorage</h1>

    <?php if ($errors): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Map pin picker -->
        <div>
            <p class="text-gray-400 text-sm mb-3">📍 Click the map to set location</p>
            <div id="pin-map" style="height:400px;" class="rounded-xl border border-[#c9a227]/20 overflow-hidden"></div>
        </div>

        <!-- Form -->
        <form method="POST" class="glass-card p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

            <div class="form-group">
                <label class="form-label">Anchorage Name *</label>
                <input type="text" name="name" class="form-input" required maxlength="255"
                       placeholder="e.g. Smuggler's Cove" value="<?= sanitize($_POST['name'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-group">
                    <label class="form-label">Latitude *</label>
                    <input type="number" id="lat-input" name="lat" class="form-input" step="0.000001" required
                           min="-90" max="90" placeholder="e.g. 48.4284" value="<?= $prefillLat ?: ($_POST['lat'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude *</label>
                    <input type="number" id="lng-input" name="lng" class="form-input" step="0.000001" required
                           min="-180" max="180" placeholder="e.g. -123.3656" value="<?= $prefillLng ?: ($_POST['lng'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Depth (meters)</label>
                <input type="number" name="depth" class="form-input" step="0.1" min="0" placeholder="e.g. 5.5"
                       value="<?= sanitize($_POST['depth'] ?? '') ?>">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-group">
                    <label class="form-label">Holding Quality</label>
                    <select name="holding_quality" class="form-input">
                        <option value="">Select…</option>
                        <?php foreach (['excellent','good','fair','poor'] as $h): ?>
                            <option value="<?= $h ?>" <?= ($_POST['holding_quality'] ?? '') === $h ? 'selected' : '' ?>><?= ucfirst($h) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Protection (1–5 ⭐)</label>
                    <select name="protection_rating" class="form-input">
                        <option value="">Select…</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= (int)($_POST['protection_rating'] ?? 0) === $i ? 'selected' : '' ?>><?= str_repeat('⭐', $i) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Review / Notes</label>
                <textarea name="review_text" class="form-input resize-none" rows="4" maxlength="2000"
                          placeholder="Describe the anchorage, entry tips, hazards, facilities…"><?= sanitize($_POST['review_text'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-gold w-full py-3">Add Anchorage ⚓</button>
        </form>
    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const initLat = <?= json_encode($prefillLat ?: 20) ?>;
const initLng = <?= json_encode($prefillLng ?: 0) ?>;
const pinMap = L.map('pin-map').setView([initLat || 20, initLng || 0], initLat ? 10 : 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(pinMap);

let pinMarker;
<?php if ($prefillLat && $prefillLng): ?>
pinMarker = L.marker([initLat, initLng]).addTo(pinMap);
<?php endif; ?>

pinMap.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);
    document.getElementById('lat-input').value = lat;
    document.getElementById('lng-input').value = lng;
    if (pinMarker) pinMap.removeLayer(pinMarker);
    pinMarker = L.marker([lat, lng]).addTo(pinMap);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
