<?php
require_once __DIR__ . '/includes/init.php';

echo "<h2>Google OAuth Configuration Test</h2>";
echo "<p><strong>Google Client ID:</strong> " . (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : 'NOT DEFINED') . "</p>";
echo "<p><strong>Allowed Domain:</strong> " . (defined('ALLOWED_GOOGLE_DOMAIN') ? ALLOWED_GOOGLE_DOMAIN : 'NOT DEFINED') . "</p>";
echo "<p><strong>Base URL:</strong> " . BASE_URL . "</p>";

// Test database connection
try {
    $conn = getLegacyDatabaseConnection();
    echo "<p><strong>Database Connection:</strong> ✅ SUCCESS</p>";
    
    // Check if registration_drafts table exists
    $result = $conn->query("SHOW TABLES LIKE 'registration_drafts'");
    if ($result->num_rows > 0) {
        echo "<p><strong>Registration Drafts Table:</strong> ✅ EXISTS</p>";
    } else {
        echo "<p><strong>Registration Drafts Table:</strong> ❌ MISSING</p>";
    }
    
    // Check if applicants table exists
    $result = $conn->query("SHOW TABLES LIKE 'applicants'");
    if ($result->num_rows > 0) {
        echo "<p><strong>Applicants Table:</strong> ✅ EXISTS</p>";
    } else {
        echo "<p><strong>Applicants Table:</strong> ❌ MISSING</p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>Database Connection:</strong> ❌ FAILED - " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Visit <a href='login.php' target='_blank'>login.php</a> to test Google Sign-In</li>";
echo "<li>Try signing in with an @africau.edu email</li>";
echo "<li>Test with a non-AU email to verify domain restriction</li>";
echo "<li>Complete the registration form to test autosave</li>";
echo "</ol>";

echo "<h3>Important Notes:</h3>";
echo "<ul>";
echo "<li>Google Sign-In requires HTTPS in production</li>";
echo "<li>Make sure your Google OAuth credentials are configured correctly</li>";
echo "<li>Test the complete flow: Sign-In → Registration → Dashboard</li>";
echo "</ul>";
?>




















