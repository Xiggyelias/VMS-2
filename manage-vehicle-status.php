<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();
// Generate CSRF token for POST requests
$csrfToken = SecurityMiddleware::generateCSRFToken();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require admin access
requireAdmin();

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

$conn = getDBConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vehicle_id']) && isset($_POST['new_status'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    if (!in_array($new_status, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update vehicle status
    $stmt = $conn->prepare("UPDATE vehicles SET status = ?, last_updated = NOW() WHERE vehicle_id = ?");
    $stmt->bind_param("si", $new_status, $vehicle_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Vehicle status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update vehicle status']);
    }
    exit;
}

// Get filter status from query parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare the query based on filter
$query = "SELECT v.*, a.fullName as owner_name, a.Email, a.phone 
          FROM vehicles v 
          JOIN applicants a ON v.applicant_id = a.applicant_id";

if ($status_filter !== 'all') {
    $query .= " WHERE v.status = ?";
}

$query .= " ORDER BY v.last_updated DESC";

$stmt = $conn->prepare($query);

if ($status_filter !== 'all') {
    $stmt->bind_param("s", $status_filter);
}

$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicle Status - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <style>
        :root {
            --primary-red: #d00000;
            --primary-red-dark: #b00000;
            --white: #ffffff;
            --black: #000000;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            margin: 0;
            background-color: var(--gray-100);
            color: var(--gray-800);
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            background-color: var(--primary-red);
            padding: 1rem 2rem;
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-logo { width: 120px; }
        .header-logo img { width: 100%; height: auto; }

        /* Use shared btn btn-logout from styles.css */

        .admin-nav {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .admin-nav a {
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .admin-nav a:hover,
        .admin-nav a.active {
            background-color: var(--primary-red);
            color: var(--white);
        }

        .filters {
            background: var(--white);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            background-color: var(--white);
        }

        .table-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            border: 1px solid var(--gray-300);
            padding: 0.75rem;
            text-align: left;
        }

        .table th {
            background-color: var(--gray-100);
            font-weight: 600;
        }

        .table tbody tr:nth-child(even) {
            background-color: var(--gray-100);
        }

        .table tbody tr:hover {
            background-color: var(--gray-200);
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-toggle {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .status-toggle.active {
            background-color: #dc3545;
            color: white;
        }

        .status-toggle.inactive {
            background-color: #28a745;
            color: white;
        }

        .status-toggle:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            display: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .header-left {
                flex-direction: column;
                gap: 10px;
            }

            .admin-nav ul {
                flex-direction: column;
            }

            .admin-nav a {
                width: 100%;
                text-align: center;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .table th, .table td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-logo" style="width: 80px;">
                        <a href="admin-dashboard.php">
                            <img src="assets/images/AULogo.png" alt="AULogo">
                        </a>
                    </div>
                    <h1>Manage Vehicle Status</h1>
                </div>
                <div class="header-right">
                    <button onclick="logout()" class="btn btn-logout">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="admin-nav">
            <ul>
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li><a href="owner-list.php">Manage Owners</a></li>
                <li><a href="vehicle-list.php">Manage Vehicles</a></li>
                <li><a href="manage-disk-numbers.php">Manage Disk Numbers</a></li>
                <li><a href="admin_reports.php">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <div class="filters">
            <div class="filter-group">
                <label for="status-filter">Filter by Status:</label>
                <select id="status-filter" class="filter-select" onchange="filterVehicles(this.value)">
                    <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Vehicles</option>
                    <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
            </div>
        </div>

        <div id="alert" class="alert"></div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vehicle ID</th>
                        <th>Registration Number</th>
                        <th>Make</th>
                        <th>Owner</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><?= htmlspecialchars($vehicle['vehicle_id']) ?></td>
                            <td><?= htmlspecialchars($vehicle['regNumber']) ?></td>
                            <td><?= htmlspecialchars($vehicle['make']) ?></td>
                            <td><?= htmlspecialchars($vehicle['owner_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($vehicle['phone']) ?><br>
                                <small><?= htmlspecialchars($vehicle['Email']) ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $vehicle['status'] ?>">
                                    <?= ucfirst($vehicle['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($vehicle['last_updated'])) ?></td>
                            <td>
                                <button 
                                    class="status-toggle <?= $vehicle['status'] === 'active' ? 'active' : 'inactive' ?>"
                                    onclick="toggleStatus(<?= $vehicle['vehicle_id'] ?>, '<?= $vehicle['status'] ?>')"
                                >
                                    <?= $vehicle['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function logout() {
            window.location.href = 'logout.php';
        }

        function filterVehicles(status) {
            window.location.href = `manage-vehicle-status.php?status=${status}`;
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 3000);
        }

        function toggleStatus(vehicleId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            if (!confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this vehicle?`)) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch('manage-vehicle-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken,
                },
                body: `vehicle_id=${encodeURIComponent(vehicleId)}&new_status=${encodeURIComponent(newStatus)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Reload the page to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('An error occurred while updating the status', 'error');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html> 