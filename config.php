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
