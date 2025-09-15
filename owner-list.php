<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

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

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE fullName LIKE ? OR idNumber LIKE ? OR phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
    $types = 'sss';
}

$conn = getDBConnection();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM applicants $where_clause";
if (!empty($params)) {
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_records = $stmt->get_result()->fetch_assoc()['count'];
} else {
    $result = $conn->query($count_sql);
    $total_records = $result->fetch_assoc()['count'];
}

$total_pages = ceil($total_records / $per_page);

// Get owners with vehicle count
$sql = "
    SELECT a.*, COUNT(v.vehicle_id) as vehicle_count 
    FROM applicants a 
    LEFT JOIN vehicles v ON a.applicant_id = v.applicant_id 
    $where_clause 
    GROUP BY a.applicant_id 
    ORDER BY a.fullName 
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
$owners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner List - Vehicle Registration System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .header-logo {
            display: flex;
            align-items: center;
        }

        .header-logo { width: 80px; }
        .header-logo img { width: 100%; height: auto; }

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
            flex: 1;
            padding: 0.875rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: var(--gray-100);
            color: var(--black);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .table tr:hover {
            background-color: #f9f9f9;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        /* Compact button variant for icon/inline actions */
        .btn-icon {
            padding: 0.5rem 1rem;
            border-radius: 6px;
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
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #f5f5f5;
        }

        .pagination a.active {
            background-color: var(--primary-red);
            color: white;
            border-color: var(--primary-red);
        }

        .vehicle-count {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .vehicle-count:hover {
            color: #d32f2f;
            text-decoration: underline;
        }

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
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="header-logo" style="width: 80px;">
                    <a href="admin-dashboard.php">
                        <img src="assets/images/AULogo.png" alt="AULogo">
                    </a>
                </div>
                <h1>Owner List</h1>
                <button onclick="logout()" class="btn btn-logout">Logout</button>
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="admin-nav">
            <ul>
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li><a href="owner-list.php" class="active">Manage Owners</a></li>
                <li><a href="vehicle-list.php">Manage Vehicles</a></li>
                <li><a href="manage-disk-numbers.php">Manage Disk Numbers</a></li>
                <li><a href="admin_reports.php">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <div class="search-container">
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by name, ID, or phone..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="owner-list.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>ID Number</th>
                        <th>Phone</th>
                        <th>Vehicles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($owners as $owner): ?>
                        <tr>
                            <td><?= htmlspecialchars($owner['fullName']) ?></td>
                            <td><?= htmlspecialchars($owner['idNumber']) ?></td>
                            <td><?= htmlspecialchars($owner['phone']) ?></td>
                            <td>
                                <a href="owner-details.php?id=<?= $owner['applicant_id'] ?>" 
                                   class="vehicle-count">
                                    <?= $owner['vehicle_count'] ?> Vehicle(s)
                                </a>
                            </td>
                            <td class="action-buttons">
                                <button class="btn btn-primary btn-icon" 
                                        onclick="viewOwner(<?= $owner['applicant_id'] ?>)">
                                    View
                                </button>
                                <button class="btn btn-secondary btn-icon" 
                                        onclick="editOwner(<?= $owner['applicant_id'] ?>)">
                                    Edit
                                </button>
                                <button class="btn btn-danger btn-icon" 
                                        onclick="deleteOwner(<?= $owner['applicant_id'] ?>)">
                                    Delete
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
                    <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                        Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
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

        function viewOwner(ownerId) {
            window.location.href = `owner-details.php?id=${ownerId}`;
        }

        function editOwner(ownerId) {
            window.location.href = `edit-owner.php?id=${ownerId}`;
        }

        function deleteOwner(ownerId) {
            if (!confirm('Are you sure you want to delete this owner? This will remove the owner record.')) {
                return;
            }

            const formData = new FormData();
            formData.append('user_id', ownerId);

            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': '<?= htmlspecialchars(SecurityMiddleware::generateCSRFToken()) ?>' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Refresh to reflect deletion
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete owner');
                }
            })
            .catch(() => alert('An error occurred. Please try again.'));
        }
    </script>
</body>
</html> 