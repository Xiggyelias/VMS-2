<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Check if admin is already logged in
if (isAdmin()) {
    redirect(BASE_URL . '/admin-dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if (empty($username) || empty($password)) {
        redirect(BASE_URL . '/login.php?error=empty_fields');
    }

    // Attempt admin login using the new modular function
    $result = adminLogin($username, $password);
    
    if ($result['success']) {
        redirect(BASE_URL . '/admin-dashboard.php');
    } else {
        redirect(BASE_URL . '/login.php?error=invalid_password');
    }
} else {
    redirect(BASE_URL . '/login.php');
}
?>
