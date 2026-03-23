<?php
require_once __DIR__ . '/../config.php';

// Throw exceptions for all mysqli errors so they are caught by the global
// exception handler in functions.php and written to the error log.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (\mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    $isApi = function_exists('isApiRequest') && isApiRequest();
    if ($isApi) {
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Service temporarily unavailable.']));
    }
    include __DIR__ . '/../500.php';
    exit;
}

$conn->set_charset('utf8mb4');
