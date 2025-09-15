<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$admin_username = "admin";
$admin_password = "12345"; // In real apps, use hashed passwords!

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['username'] == $admin_username && $_POST['password'] == $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
// // Check if user is logged in and is admin
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
//     header("Location: user-dashboard.php");
//     exit();
// }

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build WHERE clause for search and filters
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(v.regNumber LIKE ? OR v.vin LIKE ? OR a.fullName LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

if (!empty($status)) {
    $where_clauses[] = "v.status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$conn = getDBConnection();

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as count 
    FROM vehicles v 
    JOIN applicants a ON v.applicant_id = a.applicant_id 
    $where_clause
";
if (!empty($search)) {
    $where_clauses[] = "(v.regNumber LIKE ? OR a.fullName LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';

} else {
    $result = $conn->query($count_sql);
    $total_records = $result->fetch_assoc()['count'];
}

$total_pages = ceil($total_records / $per_page);

// Get vehicles with owner information
$sql = "
    SELECT v.*, a.fullName as owner_name 
    FROM vehicles v 
    JOIN applicants a ON v.applicant_id = a.applicant_id 
    $where_clause 
    ORDER BY v.registration_date DESC 
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $per_page, $offset);
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
    <title>Vehicle List - Vehicle Registration System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header-right {
            display: flex;
            align-items: center;
        }

        .header-logo { width: 80px; }
        .header-logo img { width: 100%; height: auto; }

        .admin-nav {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .admin-nav li {
            margin: 0;
        }

        .admin-nav a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .admin-nav a:hover {
            background-color: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-nav a.active {
            background-color: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }

        /* Using shared btn-logout from global stylesheet */

        .search-container {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }

        .search-input {
            padding: 0.875rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .status-select {
            padding: 0.875rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            background-color: white;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: var(--primary-red);
        }

        .pagination a.active {
            background-color: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #e6ffe6;
            color: #008000;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-expired {
            background-color: #ffe6e6;
            color: var(--primary-red);
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
                    <h1>Vehicle List</h1>
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
                <li><a href="vehicle-list.php" class="active">Manage Vehicles</a></li>
                <li><a href="manage-disk-numbers.php">Manage Disk Numbers</a></li>
                <li><a href="admin_reports.php">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <div class="search-container">
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by plate number, VIN, or owner name..." 
                       value="<?= htmlspecialchars($search ?? '') ?>">
                <select name="status" class="status-select">
                    <option value="">All Status</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Expired</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search) || !empty($status)): ?>
                    <a href="vehicle-list.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Plate Number</th>
                        <th>diskNumber</th>
                        <th>Make</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <tr>
                            <td><?= htmlspecialchars($vehicle['regNumber'] ?? '') ?></td>
                            <td><?= htmlspecialchars($vehicle['disk_number'] ?? '') ?></td>
                            <td><?= htmlspecialchars($vehicle['make'] ?? '') ?></td>
                            <td><?= htmlspecialchars($vehicle['owner_name'] ?? '') ?></td>
                            <td>
                                <span class="status-badge status-<?= strtolower($vehicle['status'] ?? 'active') ?>">
                                    <?= ucfirst($vehicle['status'] ?? 'Active') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($vehicle['expiry_date'] ?? 'N/A') ?></td>
                            <td>
                                <button class="btn btn-primary btn-icon" 
                                        onclick="viewVehicle(<?= $vehicle['vehicle_id'] ?? 0 ?>)">
                                    View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>">
                        Next
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function logout() {
            window.location.href = 'logout.php';
        }

        function viewVehicle(vehicleId) {
            window.location.href = `vehicle-details.php?id=${vehicleId}`;
        }
    </script>
</body>
</html> 