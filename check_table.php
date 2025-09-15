<?php
// Temporary script to check applicants table structure
$conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>All Columns in Applicants Table:</h2>";
$result = $conn->query("SHOW COLUMNS FROM applicants");

if ($result) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>" . $row['Field'] . "</strong> - " . $row['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h2>Sample Data (first row):</h2>";
$result = $conn->query("SELECT * FROM applicants LIMIT 1");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    print_r($row);
    echo "</pre>";
} else {
    echo "No data found in applicants table.";
}

$conn->close();
?> 