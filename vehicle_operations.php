<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (align with app session structure)
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue.']);
    exit();
}

// Database connection function
function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    $action = $_POST['action'] ?? '';
    
    try {
        $conn = getDBConnection();
        $applicant_id = getCurrentUserId();
        
        switch ($action) {
            case 'add':
                $make = trim($_POST['make'] ?? '');
                $regNumber = trim($_POST['regNumber'] ?? '');
                
                if (empty($make) || empty($regNumber)) {
                    throw new Exception('Please fill in all required fields.');
                }
                
                // Check if registration number already exists
                $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE regNumber = ?");
                $stmt->bind_param("s", $regNumber);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('This registration number is already registered.');
                }
                $stmt->close();
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Deactivate any existing active vehicles for this applicant
                    $stmt = $conn->prepare("UPDATE vehicles SET status = 'inactive' WHERE applicant_id = ? AND status = 'active'");
                    $stmt->bind_param("i", $applicant_id);
                    $stmt->execute();
                    $stmt->close();

                    // Derive required NOT NULL fields if schema requires them
                    $owner = '';
                    $address = '';
                    $plateNumber = $regNumber; // default plate number to reg number if not provided

                    // Fetch applicant full name to use as owner if available
                    $ownerStmt = $conn->prepare("SELECT fullName FROM applicants WHERE applicant_id = ?");
                    $ownerStmt->bind_param("i", $applicant_id);
                    $ownerStmt->execute();
                    $ownerResult = $ownerStmt->get_result();
                    if ($row = $ownerResult->fetch_assoc()) {
                        $owner = $row['fullName'] ?: '';
                    }
                    $ownerStmt->close();

                    // Insert new vehicle with active status
                    $stmt = $conn->prepare("INSERT INTO vehicles (applicant_id, regNumber, make, owner, address, PlateNumber, status, last_updated) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
                    $stmt->bind_param("isssss", $applicant_id, $regNumber, $make, $owner, $address, $plateNumber);
                    
                    if ($stmt->execute()) {
                        $conn->commit();
                        $response['success'] = true;
                        $response['message'] = 'Vehicle added successfully!';
                    } else {
                        throw new Exception("Failed to add vehicle: " . $stmt->error);
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                break;
                
            case 'edit':
                $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
                $make = trim($_POST['make'] ?? '');
                $regNumber = trim($_POST['regNumber'] ?? '');
                
                if ($vehicle_id <= 0 || empty($make) || empty($regNumber)) {
                    throw new Exception('Please fill in all required fields.');
                }
                
                // Check if registration number already exists for other vehicles
                $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE regNumber = ? AND vehicle_id != ?");
                $stmt->bind_param("si", $regNumber, $vehicle_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('This registration number is already registered to another vehicle.');
                }
                $stmt->close();
                
                // Verify vehicle ownership
                $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE vehicle_id = ? AND applicant_id = ?");
                $stmt->bind_param("ii", $vehicle_id, $applicant_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Vehicle not found or not authorized.');
                }
                $stmt->close();
                
                // Update vehicle
                $stmt = $conn->prepare("UPDATE vehicles SET make = ?, regNumber = ?, last_updated = NOW() WHERE vehicle_id = ? AND applicant_id = ?");
                $stmt->bind_param("ssii", $make, $regNumber, $vehicle_id, $applicant_id);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Vehicle updated successfully!';
                } else {
                    throw new Exception("Failed to update vehicle: " . $stmt->error);
                }
                break;
                
            case 'delete':
                $vehicle_id = intval($_POST['vehicle_id'] ?? 0);
                
                if ($vehicle_id <= 0) {
                    throw new Exception('Invalid vehicle ID.');
                }
                
                // Verify vehicle ownership and get current status
                $stmt = $conn->prepare("SELECT status FROM vehicles WHERE vehicle_id = ? AND applicant_id = ?");
                $stmt->bind_param("ii", $vehicle_id, $applicant_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 0) {
                    throw new Exception('Vehicle not found or not authorized.');
                }
                $vehicleRow = $result->fetch_assoc();
                $wasActive = strtolower($vehicleRow['status'] ?? '') === 'active';
                $stmt->close();
                
                // Delete authorized drivers linked to the vehicle
                $stmt = $conn->prepare("DELETE FROM authorized_driver WHERE vehicle_id = ?");
                $stmt->bind_param("i", $vehicle_id);
                $stmt->execute();
                $stmt->close();

                // Delete the vehicle
                $stmt = $conn->prepare("DELETE FROM vehicles WHERE vehicle_id = ? AND applicant_id = ?");
                $stmt->bind_param("ii", $vehicle_id, $applicant_id);
                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete vehicle: " . $stmt->error);
                }
                $stmt->close();

                // If the deleted vehicle was active, reactivate the most recently updated remaining vehicle
                if ($wasActive) {
                    // Find the most recent remaining vehicle for this applicant
                    $stmt = $conn->prepare("SELECT vehicle_id FROM vehicles WHERE applicant_id = ? ORDER BY last_updated DESC LIMIT 1");
                    $stmt->bind_param("i", $applicant_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $prevVehicleId = intval($row['vehicle_id']);
                        $stmt->close();
                        // Set it active
                        $stmt = $conn->prepare("UPDATE vehicles SET status = 'active', last_updated = NOW() WHERE vehicle_id = ?");
                        $stmt->bind_param("i", $prevVehicleId);
                        $stmt->execute();
                    } else {
                        $stmt->close();
                    }
                }

                $response['success'] = true;
                $response['message'] = 'Vehicle deleted successfully!';
                break;
                
            default:
                throw new Exception('Invalid action specified.');
        }
        
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit();
}

// If not POST request
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
exit();
?> 