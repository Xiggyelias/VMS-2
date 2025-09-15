<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Generate CSRF token
$csrfToken = SecurityMiddleware::generateCSRFToken();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

$conn = getDBConnection();

// Handle disk number assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_disk') {
    $vehicle_id = $_POST['vehicle_id'];
    $disk_number = $_POST['disk_number'];
    
    $stmt = $conn->prepare("UPDATE vehicles SET disk_Number = ? WHERE vehicle_id = ?");
    $stmt->bind_param("si", $disk_number, $vehicle_id);
    
    if ($stmt->execute()) {
        $success_message = "Disk number assigned successfully!";
    } else {
        $error_message = "Failed to assign disk number: " . $stmt->error;
    }
    $stmt->close();
}

// Get vehicles without disk numbers
$stmt = $conn->prepare("
    SELECT v.*, a.fullName as owner_name 
    FROM vehicles v 
    JOIN applicants a ON v.applicant_id = a.applicant_id 
    WHERE v.disk_Number IS NULL OR v.disk_Number = ''
    ORDER BY v.registration_date DESC
");
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Disk Numbers - Vehicle Registration System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
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

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: var(--primary-red);
            color: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-logo { width: 120px; }
        .header-logo img { width: 100%; height: auto; }

        .header-right .btn-secondary {
            background-color: var(--white);
            color: var(--primary-red);
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
             transition: all 0.2s ease;
        }

         .header-right .btn-secondary:hover {
             background-color: var(--gray-200);
             transform: translateY(-1px);
         }

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

        .admin-nav a:hover, .admin-nav a.active {
            background-color: var(--primary-red);
            color: white;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            font-size: 0.9375rem;
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

        .search-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            flex-grow: 1;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--primary-red);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-red-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #6c757d;
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
        }

        .table-container {
            background: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
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
            color: var(--gray-800);
            font-weight: 600;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table tbody tr:hover {
            background-color: var(--gray-200);
        }

         .disk-number-form {
            display: flex;
            gap: 0.5rem; /* Reduced gap */
            align-items: center;
        }
        
        .disk-number-input {
            width: 120px; /* Adjusted width */
            padding: 0.5rem;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
             font-size: 0.9rem;
        }
        
        .assign-button {
            background-color: #5cb85c; /* Green assign button */
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
             font-size: 0.9rem;
        }
        
        .assign-button:hover {
            background-color: #4cae4c; /* Darker green on hover */
        }

        .vehicle-details-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .vehicle-details-card h3 {
            margin-top: 0;
            color: var(--primary-red);
            margin-bottom: 1rem;
        }

        .vehicle-details-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item .info-label {
             font-size: 0.9rem;
             color: var(--gray-600);
             margin-bottom: 0.25rem;
        }

         .info-item .info-value {
             font-weight: 500;
             color: var(--gray-800);
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

            .search-form {
                flex-direction: column;
                gap: 1rem;
            }

            .search-input, .btn {
                 width: 100%;
            }

            .disk-number-form {
                flex-direction: column;
                gap: 0.5rem;
            }

             .disk-number-input, .assign-button {
                 width: 100%;
                 box-sizing: border-box;
             }

             .table th, .table td {
                 padding: 0.5rem;
                 font-size: 0.9rem;
             }
        }

    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <div class="header-logo" style="width: 80px;">
                        <a href="admin-dashboard.php">
                             <img src="assets/images/AULogo.png" alt="AULogo">
                        </a>
                    </div>
                    <h1>Manage Disk Numbers</h1>
                </div>
                <div class="header-right">
                     <a href="admin-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
                <li><a href="manage-disk-numbers.php" class="active">Manage Disk Numbers</a></li>
                <li><a href="admin_reports.php">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

  <?php
// Define the variable safely to avoid undefined warnings
$regNumber = $_GET['search_reg'] ?? '';
?>

<div class="search-container">
    <h2>Search Vehicle by Registration Number</h2>
    <form class="search-form" method="GET">
        <input type="text" name="search_reg" class="search-input" 
               placeholder="Enter Registration Number..." 
               value="<?= htmlspecialchars($regNumber) ?>" required>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if (!empty($regNumber)): ?>
            <a href="manage-disk-numbers.php" class="btn btn-secondary">Clear Search</a>
        <?php endif; ?>
    </form>
</div>

        <?php if (!empty($searched_vehicle)): ?>
            <div class="vehicle-details-card">
                <h3>Vehicle Details</h3>
                <div class="vehicle-details-info">
                    <div class="info-item">
                        <div class="info-label">Registration Number</div>
                        <div class="info-value"><?= htmlspecialchars($searched_vehicle['regNumber']) ?></div>
                    </div>
                     <div class="info-item">
                        <div class="info-label">Make</div>
                        <div class="info-value"><?= htmlspecialchars($searched_vehicle['make']) ?></div>
                    </div>
                     <div class="info-item">
                        <div class="info-label">Owner</div>
                        <div class="info-value"><?= htmlspecialchars($searched_vehicle['owner_name']) ?></div>
                    </div>
                     <div class="info-item">
                        <div class="info-label">Status</div>
                         <div class="info-value">
                            <span class="status-badge status-pending">Pending Assignment</span>
                        </div>
                    </div>
                </div>
                <h4>Assign Disk Number</h4>
                <form class="disk-number-form" method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="assign_disk">
                    <input type="hidden" name="vehicle_id" value="<?= $searched_vehicle['vehicle_id'] ?>">
                    <input type="text" name="disk_number" class="disk-number-input" 
                           placeholder="Enter disk number" required
                           pattern="[A-Za-z0-9-]+" title="Only letters, numbers, and hyphens allowed">
                    <button type="submit" class="assign-button">Assign Disk Number</button>
                </form>
            </div>

        <?php else: ?>

        <div class="content-section">
            <h2>Vehicles Pending Disk Number Assignment</h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Registration Number</th>
                            <th>Make</th>
                            <th>Owner</th>
                            <th>Registration Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($vehicles)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No vehicles found or pending disk number assignment</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td><?= htmlspecialchars($vehicle['regNumber']) ?></td>
                                    <td><?= htmlspecialchars($vehicle['make']) ?></td>
                                    <td><?= htmlspecialchars($vehicle['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($vehicle['registration_date']) ?></td>
                                    <td>
                                        <form class="disk-number-form" method="POST">
                                            <!-- CSRF Token -->
                                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                            <input type="hidden" name="action" value="assign_disk">
                                            <input type="hidden" name="vehicle_id" value="<?= $vehicle['vehicle_id'] ?>">
                                            <input type="text" name="disk_number" class="disk-number-input" 
                                                   placeholder="Enter disk number" required
                                                   pattern="[A-Za-z0-9-]+" title="Only letters, numbers, and hyphens allowed">
                                            <button type="submit" class="assign-button">Assign</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script>
        // Add form validation
        document.querySelectorAll('.disk-number-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const diskNumber = this.querySelector('input[name="disk_number"]').value;
                if (!/^[A-Za-z0-9-]+$/.test(diskNumber)) {
                    e.preventDefault();
                    alert('Disk number can only contain letters, numbers, and hyphens');
                }
            });
        });
    </script>
</body>
</html> 