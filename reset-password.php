<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

$message = '';
$messageType = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: login.php");
    exit();
}

$conn = getDBConnection();

// Verify token
$stmt = $conn->prepare("
    SELECT prt.*, a.email 
    FROM password_reset_tokens prt 
    JOIN applicants a ON prt.user_id = a.applicant_id 
    WHERE prt.token = ? 
    AND prt.used = FALSE 
    AND prt.expires_at > NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $validToken = true;
    $tokenData = $result->fetch_assoc();
    // Generate CSRF token
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
} else {
    $message = "Invalid or expired reset link. Please request a new password reset.";
    $messageType = "error";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf_token)) {
        $message = "Invalid request. Please try again.";
        $messageType = "error";
    } elseif (empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "error";
    } else {
        // Update password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE applicants SET password = ? WHERE applicant_id = ?");
        $stmt->bind_param("si", $hashedPassword, $tokenData['user_id']);
        
        if ($stmt->execute()) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE id = ?");
            $stmt->bind_param("i", $tokenData['id']);
            $stmt->execute();
            
            $message = "Password has been reset successfully. You can now login with your new password.";
            $messageType = "success";
            
            // Clear CSRF token
            unset($_SESSION['csrf_token']);
            
            // Redirect to login page after 3 seconds
            header("refresh:3;url=login.php");
        } else {
            $message = "Failed to reset password. Please try again.";
            $messageType = "error";
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Vehicle Registration System</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        body { background: #121212; color: #eee; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100vh; display: flex; justify-content: center; align-items: center; }
        .container { background: #1e1e1e; padding: 2rem 2.5rem; border-radius: 12px; box-shadow: 0 0 20px #d00000aa; max-width: 400px; width: 100%; }
        .logo { display: block; margin: 0 auto 1rem; height: 45px; filter: brightness(0) invert(1); }
        h2 { color: #d00000; text-align: center; margin-bottom: 1rem; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        input[type="password"] { padding: 0.75rem; border: 2px solid #444; border-radius: 6px; background: #1e1e1e; color: #fff; font-size: 1.1rem; }
        button { background: #d00000; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; color: white; font-weight: 700; font-size: 1rem; cursor: pointer; transition: all 0.3s ease; }
        button:hover { background: #ff0000; }
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; text-align: center; }
        .alert-success { background: #005c00; color: #77ff77; }
        .alert-error { background: #5c0000; color: #ff7777; }
    </style>
</head>
<body>
    <div class="container">
        <img src="AULogo.png" alt="AU Logo" class="logo" />
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>" />
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>" />
                <input type="password" name="password" placeholder="New password" required minlength="8" autocomplete="new-password" />
                <input type="password" name="confirm_password" placeholder="Confirm new password" required minlength="8" autocomplete="new-password" />
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 