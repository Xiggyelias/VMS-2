<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) { $payload = $_POST; }

$userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
$type = strtolower(trim($payload['registrant_type'] ?? ''));

$validTypes = ['student','staff','guest'];
if (!$userId || !in_array($type, $validTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $conn = getLegacyDatabaseConnection();

    // Ensure user exists by email on applicants
    $stmt = $conn->prepare("SELECT applicant_id, Email, fullName FROM applicants WHERE applicant_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Update role
    $u = $conn->prepare("UPDATE applicants SET registrantType = ? WHERE applicant_id = ?");
    $u->bind_param('si', $type, $userId);
    $ok = $u->execute();
    $u->close();

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update role']);
        exit;
    }

    // Optional: update last_login if column exists
    $cols = [];
    $colsRes = $conn->query("SHOW COLUMNS FROM applicants");
    if ($colsRes) { while ($r = $colsRes->fetch_assoc()) { $cols[strtolower($r['Field'])] = true; } }
    if (isset($cols['last_login'])) {
        $ll = $conn->prepare("UPDATE applicants SET last_login = NOW() WHERE applicant_id = ?");
        if ($ll) { $ll->bind_param('i', $userId); $ll->execute(); $ll->close(); }
    }

    // Set session and respond with redirect
    $_SESSION['user_id'] = (int)$user['applicant_id'];
    $_SESSION['user_email'] = $user['Email'] ?? '';
    $_SESSION['user_name'] = $user['fullName'] ?? '';
    $_SESSION['user_type'] = $type;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    echo json_encode([
        'success' => true,
        'redirect' => 'user-dashboard.php',
        'user_info' => [
            'id' => (int)$user['applicant_id'],
            'email' => $user['Email'] ?? '',
            'name' => $user['fullName'] ?? '',
            'registrant_type' => $type
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
}
