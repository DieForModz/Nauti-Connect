<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
requireLogin();

$pageTitle   = 'Report Sighting';
$errors      = [];
$speciesList = ['orca','seal','dolphin','whale','other'];
$prefillLat  = (float)($_GET['lat'] ?? 0);
$prefillLng  = (float)($_GET['lng'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $species = $_POST['species_type'] ?? '';
        $lat     = (float)($_POST['lat'] ?? 0);
        $lng     = (float)($_POST['lng'] ?? 0);
        $time    = trim($_POST['sighting_time'] ?? '');
        $notes   = trim($_POST['notes'] ?? '');

        if (!in_array($species, $speciesList, true)) $errors[] = 'Invalid species.';
        if ($lat < -90 || $lat > 90)                 $errors[] = 'Invalid latitude.';
        if ($lng < -180 || $lng > 180)               $errors[] = 'Invalid longitude.';
        if (empty($time))                            $errors[] = 'Sighting date/time is required.';

        $imgPath = '';
        if (!empty($_FILES['image']['name'])) {
            // Try EXIF lat/lng extraction from photo
            $saved = saveUploadedImage($_FILES['image'], 'sightings');
            if ($saved) {
                $imgPath = $saved;
                // If no lat/lng provided, try EXIF
                if ($lat == 0 && $lng == 0) {
                    $exif = @exif_read_data(UPLOAD_PATH . $saved, 'GPS');
                    if (!empty($exif['GPS']['GPSLatitude'])) {
                        $latDeg  = $exif['GPS']['GPSLatitude'];
                        $lngDeg  = $exif['GPS']['GPSLongitude'];
                        $latRef  = $exif['GPS']['GPSLatitudeRef'] ?? 'N';
                        $lngRef  = $exif['GPS']['GPSLongitudeRef'] ?? 'E';
                        $lat     = (dms2dec($latDeg)) * ($latRef === 'S' ? -1 : 1);
                        $lng     = (dms2dec($lngDeg)) * ($lngRef === 'W' ? -1 : 1);
                    }
                }
            } else {
                $errors[] = 'Invalid image file.';
            }
        }

        if (empty($errors)) {
            $userId = $_SESSION['user_id'];
            $stmt = $conn->prepare('INSERT INTO sightings (user_id, species_type, lat, lng, sighting_time, notes, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isddsss', $userId, $species, $lat, $lng, $time, $notes, $imgPath);
            if ($stmt->execute()) {
                addReputation($userId, 5, $conn);
                $_SESSION['flash_success'] = 'Sighting reported! Thank you for contributing.';
                redirect(SITE_URL . '/sightings/view.php?id=' . $conn->insert_id);
            } else {
                $errors[] = 'Failed to save sighting.';
            }
        }
    }
}

function dms2dec(array $dms): float {
    [$d, $m, $s] = $dms;
    [$dn, $dd] = explode('/', $d);
    [$mn, $md] = explode('/', $m);
    [$sn, $sd] = explode('/', $s);
    return ((int)$dn / (int)$dd) + ((int)$mn / (int)$md / 60) + ((int)$sn / (int)$sd / 3600);
}

$speciesEmoji = ['orca'=>'🐋','seal'=>'🦭','dolphin'=>'🐬','whale'=>'🐳','other'=>'🐟'];
$csrf = generateCSRF();
include __DIR__ . '/../includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold text-white mb-8">Report Wildlife Sighting</h1>

    <?php if ($errors): ?>
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 px-4 py-3 rounded-lg mb-6">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div>
            <p class="text-gray-400 text-sm mb-3">📍 Click map to pin location</p>
            <div id="sight-map" style="height:400px;" class="rounded-xl border border-[#c9a227]/20 overflow-hidden"></div>
            <p class="text-gray-500 text-xs mt-2">Or upload a geo-tagged photo to auto-fill coordinates</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="glass-card p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= sanitize($csrf) ?>">

            <div class="form-group">
                <label class="form-label">Species *</label>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($speciesList as $sp): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="species_type" value="<?= $sp ?>" class="sr-only peer"
                                   <?= ($_POST['species_type'] ?? '') === $sp ? 'checked' : '' ?> required>
                            <div class="peer-checked:border-[#c9a227] peer-checked:bg-[#c9a227]/10 border border-[#c9a227]/20 rounded-lg p-3 text-center hover:border-[#c9a227]/50 transition-all">
                                <div class="text-2xl"><?= $speciesEmoji[$sp] ?></div>
                                <div class="text-xs mt-1 capitalize"><?= $sp ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="form-group">
                    <label class="form-label">Latitude *</label>
                    <input type="number" id="lat-input" name="lat" class="form-input" step="0.000001" required
                           min="-90" max="90" placeholder="48.1234" value="<?= $prefillLat ?: ($_POST['lat'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude *</label>
                    <input type="number" id="lng-input" name="lng" class="form-input" step="0.000001" required
                           min="-180" max="180" placeholder="-123.4567" value="<?= $prefillLng ?: ($_POST['lng'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Date &amp; Time *</label>
                <input type="datetime-local" name="sighting_time" class="form-input" required
                       value="<?= sanitize($_POST['sighting_time'] ?? date('Y-m-d\TH:i')) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input resize-none" rows="3" maxlength="2000"
                          placeholder="Describe what you saw: count, behavior, direction of travel…"><?= sanitize($_POST['notes'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Photo (optional)</label>
                <input type="file" name="image" accept="image/*" class="form-input" id="sighting-photo"
                       onchange="previewImages(this, 'sighting-preview', 1)">
                <div id="sighting-preview" class="mt-2"></div>
                <p class="text-gray-500 text-xs mt-1">Geo-tagged photos will auto-fill coordinates</p>
            </div>

            <button type="submit" class="btn-gold w-full py-3">Report Sighting 🐬</button>
        </form>
    </div>
</main>

<script src="<?= SITE_URL ?>/assets/js/map.js"></script>
<script>
const initLat = <?= json_encode($prefillLat ?: 20) ?>;
const initLng = <?= json_encode($prefillLng ?: 0) ?>;
const sightMap = L.map('sight-map').setView([initLat || 20, initLng || 0], initLat ? 10 : 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(sightMap);

let pinMarker;
<?php if ($prefillLat && $prefillLng): ?>
pinMarker = L.marker([initLat, initLng]).addTo(sightMap);
<?php endif; ?>

sightMap.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(6);
    const lng = e.latlng.lng.toFixed(6);
    document.getElementById('lat-input').value = lat;
    document.getElementById('lng-input').value = lng;
    if (pinMarker) sightMap.removeLayer(pinMarker);
    pinMarker = L.marker([lat, lng]).addTo(sightMap);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
