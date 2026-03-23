<?php
require_once __DIR__ . '/../includes/functions.php';
// functions.php already calls session_start() via isLoggedIn check
// Properly destroy the session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();
session_start();
session_regenerate_id(true);
$_SESSION['flash_success'] = 'You have been logged out.';
redirect(SITE_URL . '/');
