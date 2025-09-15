<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';

// Initialize security middleware
SecurityMiddleware::initialize();

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'CSRF bypass test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'session_id' => session_id(),
    'csrf_token' => SecurityMiddleware::generateCSRFToken(),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI']
]);
?>


