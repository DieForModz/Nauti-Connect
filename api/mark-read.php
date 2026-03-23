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

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$senderId  = (int)($input['sender_id'] ?? 0);
$userId    = $_SESSION['user_id'];

if ($senderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid sender_id']);
    exit;
}

$stmt = $conn->prepare(
    'UPDATE messages SET read_status = 1 WHERE sender_id = ? AND receiver_id = ? AND read_status = 0'
);
$stmt->bind_param('ii', $senderId, $userId);
$stmt->execute();

echo json_encode(['success' => true, 'updated' => $stmt->affected_rows]);
