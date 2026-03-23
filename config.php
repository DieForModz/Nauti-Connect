<?php
// ── PHP version requirement ───────────────────────────────────────────────────
// This application requires PHP 8.0 or higher (uses match expressions, union
// return types, and other PHP 8.0+ language features).
if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    die('Nauti-Connect requires PHP 8.0 or later. Currently running PHP ' . PHP_VERSION . '. '
        . 'In MAMP, go to MAMP > Preferences > PHP and select PHP 8.0 or higher.');
}

// ── Database configuration ────────────────────────────────────────────────────
// MAMP users: use '127.0.0.1' instead of 'localhost' to force a TCP connection
// and avoid Unix-socket path mismatches (MAMP's socket is at a non-standard path).
// MAMP default MySQL port is 8889; if you changed it, set DB_HOST to '127.0.0.1:8889'.
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'maritime_db');

// ── Site URL ──────────────────────────────────────────────────────────────────
// Auto-detected from the current request (handles both root and subdirectory
// installs, HTTP and HTTPS, and custom ports).
// To override, uncomment the line below and set your domain (no trailing slash):
// define('SITE_URL', 'https://yourdomain.com');
if (!defined('SITE_URL')) {
    $__protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $__host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $__docRoot  = rtrim(str_replace('\\', '/', (string)(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '')), '/');
    $__appRoot  = rtrim(str_replace('\\', '/', (string)(realpath(__DIR__) ?: __DIR__)), '/');
    if ($__docRoot !== '' && str_starts_with($__appRoot, $__docRoot)) {
        $__subPath = substr($__appRoot, strlen($__docRoot));
    } else {
        $__subPath = '';
    }
    define('SITE_URL', $__protocol . '://' . $__host . $__subPath);
    unset($__protocol, $__host, $__docRoot, $__appRoot, $__subPath);
}

define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('AI_ENABLED', true);
define('SESSION_NAME', 'nauti_session');
define('CSRF_TOKEN_NAME', '_csrf_token');
define('LOG_PATH', __DIR__ . '/logs/');

// PHP error logging – write all errors to the log file, never display them
ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
if (!is_dir(LOG_PATH) && !mkdir(LOG_PATH, 0750, true) && !is_dir(LOG_PATH)) {
    // If the logs directory cannot be created, fall back to the system default
    // error_log destination so errors are still captured somewhere.
    error_log('Nauti-Connect: could not create logs directory at ' . LOG_PATH);
} else {
    ini_set('error_log', LOG_PATH . 'error.log');
}
