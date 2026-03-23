<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!validateCSRF($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Validate file upload
if (empty($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$subdir = trim($_POST['subdir'] ?? 'misc');
// Whitelist subdirs
$allowedDirs = ['parts', 'boats', 'builds', 'sightings', 'profiles', 'misc', 'charts'];
if (!in_array($subdir, $allowedDirs, true)) $subdir = 'misc';

$saved = saveUploadedImage($_FILES['image'], $subdir);
if ($saved) {
    echo json_encode([
        'success' => true,
        'path'    => $saved,
        'url'     => UPLOAD_URL . $saved,
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid image file. Allowed: JPEG, PNG, GIF, WEBP (max 10MB)']);
}
