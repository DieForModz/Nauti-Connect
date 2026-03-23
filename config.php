<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'maritime_db');
define('SITE_URL', 'https://yourdomain.com');
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
