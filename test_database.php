<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/plain');

try {
    // Test database connection
    $conn = getLegacyDatabaseConnection();
    
    if ($conn && !$conn->connect_error) {
        echo "Database connection: SUCCESS\n";
        echo "Server info: " . $conn->server_info . "\n";
        echo "Host info: " . $conn->host_info . "\n";
        
        // Test if the applicants table exists
        $result = $conn->query("SHOW TABLES LIKE 'applicants'");
        if ($result && $result->num_rows > 0) {
            echo "Applicants table: EXISTS\n";
            
            // Test a simple query
            $testQuery = $conn->query("SELECT COUNT(*) as count FROM applicants LIMIT 1");
            if ($testQuery) {
                $row = $testQuery->fetch_assoc();
                echo "Applicants count: " . ($row['count'] ?? 'Unknown') . "\n";
            } else {
                echo "Applicants table query: FAILED - " . $conn->error . "\n";
            }
        } else {
            echo "Applicants table: NOT FOUND\n";
        }
        
        $conn->close();
    } else {
        echo "Database connection: FAILED\n";
        if ($conn) {
            echo "Error: " . $conn->connect_error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Database connection: EXCEPTION\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>


