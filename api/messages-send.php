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

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$csrfToken  = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!validateCSRF($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$senderId   = $_SESSION['user_id'];
$receiverId = (int)($input['receiver_id'] ?? 0);
$content    = trim($input['content'] ?? '');
$listingType = trim($input['listing_type'] ?? '');
$listingId  = (int)($input['listing_id'] ?? 0);

if (!$receiverId || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

if ($receiverId === $senderId) {
    echo json_encode(['success' => false, 'error' => 'Cannot message yourself']);
    exit;
}

// Rate limit: 1 message per minute per user
if (!checkRateLimit($senderId, 'message', 60, $conn)) {
    echo json_encode(['success' => false, 'error' => 'Rate limit: wait before sending another message']);
    exit;
}

// Verify receiver exists
$rStmt = $conn->prepare('SELECT id FROM users WHERE id = ?');
$rStmt->bind_param('i', $receiverId);
$rStmt->execute();
if (!$rStmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'error' => 'Recipient not found']);
    exit;
}

$lType = $listingType ?: null;
$lId   = $listingId ?: null;
$content = substr($content, 0, 2000);

$stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, listing_type, listing_id, content) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('iisis', $senderId, $receiverId, $lType, $lId, $content);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message_id' => $conn->insert_id]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}
