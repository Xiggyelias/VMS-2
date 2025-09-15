<?php
require_once __DIR__ . '/includes/init.php';

header('Content-Type: text/plain');

try {
    $conn = getLegacyDatabaseConnection();
    
    if ($conn && !$conn->connect_error) {
        echo "Database connection: SUCCESS\n";
        
        // Check if applicants table exists
        $result = $conn->query("SHOW TABLES LIKE 'applicants'");
        if ($result && $result->num_rows > 0) {
            echo "Applicants table: EXISTS\n\n";
            
            // Show table structure
            echo "=== APPLICANTS TABLE STRUCTURE ===\n";
            $structure = $conn->query("DESCRIBE applicants");
            if ($structure) {
                while ($row = $structure->fetch_assoc()) {
                    echo sprintf("%-20s %-15s %-10s %-10s %-10s %-10s\n", 
                        $row['Field'], 
                        $row['Type'], 
                        $row['Null'], 
                        $row['Key'], 
                        $row['Default'], 
                        $row['Extra']
                    );
                }
            }
            
            echo "\n=== SAMPLE DATA ===\n";
            $sample = $conn->query("SELECT * FROM applicants LIMIT 3");
            if ($sample && $sample->num_rows > 0) {
                while ($row = $sample->fetch_assoc()) {
                    echo "Row: " . json_encode($row) . "\n";
                }
            } else {
                echo "No data in applicants table\n";
            }
            
        } else {
            echo "Applicants table: NOT FOUND\n";
            
            // Show all tables
            echo "\n=== ALL TABLES ===\n";
            $tables = $conn->query("SHOW TABLES");
            if ($tables) {
                while ($row = $tables->fetch_array()) {
                    echo $row[0] . "\n";
                }
            }
        }
        
        $conn->close();
    } else {
        echo "Database connection: FAILED\n";
        if ($conn) {
            echo "Error: " . $conn->connect_error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Database check failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>


