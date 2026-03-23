<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$query = trim($input['query'] ?? '');
$convId = (int)($input['conversation_id'] ?? 0);

if (empty($query)) {
    echo json_encode(['success' => false, 'error' => 'Query is required.']);
    exit;
}

$query = substr($query, 0, 500);

$responseText = '';
$foundGuides  = [];

// FULLTEXT search
$searchTerm = preg_replace('/[+\-><()*"@~]/', ' ', $query);
$stmt = $conn->prepare(
    'SELECT id, title, steps_json, category FROM maintenance_guides
     WHERE MATCH(title, tools_needed) AGAINST (? IN BOOLEAN MODE)
     LIMIT 3'
);
$stmt->bind_param('s', $searchTerm);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fallback: LIKE search
if (empty($results)) {
    $like  = '%' . $searchTerm . '%';
    $stmt2 = $conn->prepare('SELECT id, title, steps_json, category FROM maintenance_guides WHERE title LIKE ? OR tools_needed LIKE ? LIMIT 3');
    $stmt2->bind_param('ss', $like, $like);
    $stmt2->execute();
    $results = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}

if (!empty($results)) {
    $primary = $results[0];
    $steps   = json_decode($primary['steps_json'] ?? '[]', true) ?: [];
    $excerpt = isset($steps[0]) ? substr($steps[0], 0, 200) : 'See the full guide for detailed steps.';

    $responseText = 'Based on "' . $primary['title'] . '": ' . $excerpt;
    if (count($steps) > 1) {
        $responseText .= '... [' . count($steps) . ' total steps]';
    }
    $responseText .= ' 👉 View the full guide for all steps and tools needed.';

    foreach ($results as $g) {
        $foundGuides[] = [
            'id'    => $g['id'],
            'title' => $g['title'],
            'url'   => SITE_URL . '/maintenance/view.php?id=' . $g['id'],
        ];
    }
} else {
    $responseText = 'I couldn\'t find a specific guide for that. Try browsing by category in the Guide Library — we have guides on engine winterization, fiberglass repair, electrical troubleshooting, rigging, and more!';
}

// Store conversation
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $messages = [
        ['role' => 'user', 'content' => $query, 'time' => date('c')],
        ['role' => 'assistant', 'content' => $responseText, 'time' => date('c')],
    ];
    $messagesJson = json_encode($messages);
    $title        = substr($query, 0, 100);

    if ($convId > 0) {
        // Append to existing conversation
        $cStmt = $conn->prepare('SELECT messages_json FROM ai_conversations WHERE id = ? AND user_id = ?');
        $cStmt->bind_param('ii', $convId, $userId);
        $cStmt->execute();
        $existing = $cStmt->get_result()->fetch_assoc();
        if ($existing) {
            $existing_msgs = json_decode($existing['messages_json'] ?? '[]', true) ?: [];
            $existing_msgs = array_merge($existing_msgs, $messages);
            $updatedJson   = json_encode($existing_msgs);
            $upd = $conn->prepare('UPDATE ai_conversations SET messages_json = ? WHERE id = ? AND user_id = ?');
            $upd->bind_param('sii', $updatedJson, $convId, $userId);
            $upd->execute();
        }
    } else {
        $ins = $conn->prepare('INSERT INTO ai_conversations (user_id, thread_title, messages_json) VALUES (?, ?, ?)');
        $ins->bind_param('iss', $userId, $title, $messagesJson);
        $ins->execute();
        $convId = $conn->insert_id;
    }
}

echo json_encode([
    'success'         => true,
    'response'        => $responseText,
    'guides'          => $foundGuides,
    'conversation_id' => $convId,
], JSON_UNESCAPED_UNICODE);
