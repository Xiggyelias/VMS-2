<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Check if user is already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/user-dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regNo = $_POST['regNo'] ?? '';
    $password = $_POST['password'] ?? '';
    $userType = $_POST['userType'] ?? '';
    
    // Validate inputs
    if (empty($regNo) || empty($password) || empty($userType)) {
        $error = 'Please fill in all fields.';
    } else {
        // Attempt login using the new modular function
        $result = userLogin($regNo, $password, $userType);
        
        if ($result['success']) {
            // Redirect to dashboard or specified URL
            $redirectUrl = $_GET['redirect'] ?? BASE_URL . '/user-dashboard.php';
            redirect($redirectUrl);
        } else {
            $error = $result['message'];
        }
    }
}

// If we get here, there was an error or no POST data
if ($error) {
    redirect(BASE_URL . '/login.php?error=' . urlencode($error));
} else {
    redirect(BASE_URL . '/login.php');
} 