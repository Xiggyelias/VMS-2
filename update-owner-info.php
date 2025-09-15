<?php
// Start output buffering to catch any accidental output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set error reporting for development
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Set JSON header first
header('Content-Type: application/json; charset=UTF-8');

// Prevent caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Load required files
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';

// Initialize security
SecurityMiddleware::initialize();

/**
 * Send a JSON response
 * 
 * @param string $status Response status (success/error)
 * @param string $message Response message
 * @param array $data Additional data to include in response
 * @return void
 */
function sendResponse($status, $message, $data = []) {
    // Clear all output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set the status code
    http_response_code($status === 'error' ? 400 : 200);
    
    // Create the response array
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    // Add data if provided
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    // Output the JSON response
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    // Ensure no further output
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    
    exit;
}

// Error reporting for debugging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/update_owner_info_errors.log');

// Require authentication
if (!isLoggedIn()) {
    error_log('Authentication failed: User not logged in');
    sendResponse('error', 'Authentication required');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    sendResponse('error', 'Method not allowed');
}

// Validate CSRF token
$token = $_POST['_token'] ?? '';
if (!SecurityMiddleware::validateCSRFToken($token)) {
    error_log('Invalid CSRF token');
    sendResponse('error', 'Invalid CSRF token');
}

$user_id = getCurrentUserId();

// Get and validate input
$fullName = trim($_POST['fullName'] ?? '');
$idNumber = trim($_POST['idNumber'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$college = trim($_POST['college'] ?? '');

if (empty($fullName)) {
    error_log('Validation failed: Full name is required');
    sendResponse('error', 'Full name is required');
}

try {
    $conn = getLegacyDatabaseConnection();
    if (!$conn) {
        throw new Exception('Failed to connect to database');
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset('utf8mb4');
    
    $stmt = $conn->prepare("UPDATE applicants SET fullName = ?, idNumber = ?, phone = ?, college = ?, updated_at = NOW() WHERE applicant_id = ?");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param("ssssi", $fullName, $idNumber, $phone, $college, $user_id);
    
    if ($stmt->execute()) {
        $affectedRows = $stmt->affected_rows;
        if ($affectedRows > 0) {
            // Update session if fullName was changed
            if (isset($_SESSION['user_name']) && $fullName !== $_SESSION['user_name']) {
                $_SESSION['user_name'] = $fullName;
            }
            sendResponse('success', 'Owner information updated successfully', [
                'affected_rows' => $affectedRows
            ]);
        } else {
            // No rows were updated, but no error occurred
            sendResponse('success', 'No changes were made to your information');
        }
    } else {
        throw new Exception('Failed to execute statement: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    $errorMessage = 'Update owner info error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    error_log($errorMessage);
    
    // Log the backtrace for debugging
    error_log('Backtrace: ' . json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
    
    sendResponse('error', 'Failed to update information. Please try again.');
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
