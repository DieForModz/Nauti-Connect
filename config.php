<?php
define('DB_HOST', '127.0.0.1:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'maritime_db');

$__protocol = 'http';
$__host = 'localhost:8888';
define('SITE_URL', $__protocol . '://' . $__host);
define('UPLOAD_PATH', '/home/runner/work/Nauti-Connect/Nauti-Connect/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024);
define('AI_ENABLED', true);
define('SESSION_NAME', 'nauti_session');
define('CSRF_TOKEN_NAME', '_csrf_token');
define('LOG_PATH', '/tmp/nauti-logs/');

ini_set('log_errors', '1');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
if (!is_dir(LOG_PATH)) mkdir(LOG_PATH, 0750, true);
ini_set('error_log', LOG_PATH . 'error.log');
