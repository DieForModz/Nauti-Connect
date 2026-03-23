<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); exit; }

$stmt = $conn->prepare('SELECT chart_file FROM chart_shares WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$chart = $stmt->get_result()->fetch_assoc();

if (!$chart || !$chart['chart_file']) { http_response_code(404); exit; }

// Increment download count
$upd = $conn->prepare('UPDATE chart_shares SET download_count = download_count + 1 WHERE id = ?');
$upd->bind_param('i', $id);
$upd->execute();

$filePath = UPLOAD_PATH . $chart['chart_file'];
if (!file_exists($filePath)) { http_response_code(404); exit; }

$filename = basename($chart['chart_file']);
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mime     = $finfo->file($filePath) ?: 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
