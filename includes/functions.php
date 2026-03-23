<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function generateCSRF(): string {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRF(string $token): bool {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) return false;
    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    // Regenerate token after each use
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    return $valid;
}

function formatPrice(float $price): string {
    return '$' . number_format($price, 2);
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

function resizeImage(string $sourcePath, string $destPath, int $maxWidth = 1200, int $maxHeight = 900, int $quality = 85): bool {
    $info = getimagesize($sourcePath);
    if (!$info) return false;

    [$origW, $origH, $type] = $info;

    $ratio  = min($maxWidth / $origW, $maxHeight / $origH, 1.0);
    $newW   = (int)($origW * $ratio);
    $newH   = (int)($origH * $ratio);

    $src = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
        IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
        IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
        IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
        default        => false,
    };
    if (!$src) return false;

    $dst = imagecreatetruecolor($newW, $newH);

    // Preserve transparency for PNG/GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $result = match ($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $destPath, $quality),
        IMAGETYPE_PNG  => imagepng($dst, $destPath, (int)((100 - $quality) / 10)),
        IMAGETYPE_WEBP => imagewebp($dst, $destPath, $quality),
        IMAGETYPE_GIF  => imagegif($dst, $destPath),
        default        => false,
    };

    imagedestroy($src);
    imagedestroy($dst);
    return (bool)$result;
}

function getReputation(int $userId, mysqli $conn): int {
    $stmt = $conn->prepare('SELECT reputation_points FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)($result['reputation_points'] ?? 0);
}

function addReputation(int $userId, int $points, mysqli $conn): void {
    $stmt = $conn->prepare('UPDATE users SET reputation_points = reputation_points + ? WHERE id = ?');
    $stmt->bind_param('ii', $points, $userId);
    $stmt->execute();
}

function getAllowedMimeTypes(): array {
    return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

function validateUploadedImage(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['ok' => false, 'msg' => 'File too large (max 10MB).'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, getAllowedMimeTypes(), true)) {
        return ['ok' => false, 'msg' => 'Invalid file type. Only JPEG, PNG, GIF, WEBP allowed.'];
    }
    return ['ok' => true, 'mime' => $mime];
}

function saveUploadedImage(array $file, string $subdir = ''): string|false {
    $validation = validateUploadedImage($file);
    if (!$validation['ok']) return false;

    $ext     = match ($validation['mime']) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $dir = UPLOAD_PATH . ($subdir ? rtrim($subdir, '/') . '/' : '');
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath = $dir . $filename;

    if (!resizeImage($file['tmp_name'], $destPath)) {
        // Fallback: just move without resize
        if (!move_uploaded_file($file['tmp_name'], $destPath)) return false;
    }
    return ($subdir ? $subdir . '/' : '') . $filename;
}

function currentUser(mysqli $conn): ?array {
    if (!isLoggedIn()) return null;
    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function checkRateLimit(int $userId, string $actionType, int $limitSeconds, mysqli $conn): bool {
    $stmt = $conn->prepare(
        'SELECT last_action FROM rate_limits WHERE user_id = ? AND action_type = ?'
    );
    $stmt->bind_param('is', $userId, $actionType);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $elapsed = time() - strtotime($row['last_action']);
        if ($elapsed < $limitSeconds) return false;
        $upd = $conn->prepare('UPDATE rate_limits SET last_action = NOW() WHERE user_id = ? AND action_type = ?');
        $upd->bind_param('is', $userId, $actionType);
        $upd->execute();
    } else {
        $ins = $conn->prepare('INSERT INTO rate_limits (user_id, action_type, last_action) VALUES (?, ?, NOW())');
        $ins->bind_param('is', $userId, $actionType);
        $ins->execute();
    }
    return true;
}
