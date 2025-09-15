<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token for forms and AJAX
$csrfToken = SecurityMiddleware::generateCSRFToken();

// Require admin access
requireAdmin();

// Function to connect to the database
function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    // Sanitize and check input
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['content']) ? trim($_POST['content']) : '';
    $category = isset($_POST['type']) ? trim($_POST['type']) : '';
    $createdAt = date('Y-m-d H:i:s');
    $reportDate = date('Y-m-d');
    $filePath = null;

    // Get admin_id from session
    $adminId = $_SESSION['admin_id'] ?? null;

    if (!$adminId) {
        $error_message = "Please log in again to continue.";
    } else {
    // Prepare and execute insert query
    $stmt = $conn->prepare("INSERT INTO admin_reports (title, description, category, report_date, file_path, admin_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
            $error_message = "Prepare failed: " . $conn->error;
        } else {
    $stmt->bind_param("sssssis", $title, $description, $category, $reportDate, $filePath, $adminId, $createdAt);

    if ($stmt->execute()) {
                // Redirect to prevent form resubmission
                header("Location: admin_reports.php?success=1");
                exit();
    } else {
        $error_message = "Error creating report: " . $stmt->error;
    }

    $stmt->close();
        }
    }
    $conn->close();
}

// Show success message only once, then clear it from the URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Report created successfully!";
    echo '<script>if (window.history.replaceState) { window.history.replaceState(null, null, window.location.pathname); }</script>';
}

// Fetch all reports
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM admin_reports ORDER BY created_at DESC");
$stmt->execute();
$reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Vehicle Registration System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <?php includeCommonAssets(); ?>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        .header {
            background: linear-gradient(90deg, rgba(208,0,0,1) 0%, rgba(176,0,0,1) 100%);
            color: var(--white);
        }

        .header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .header-logo { width: 80px; }
        .header-logo img { width: 100%; height: auto; }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .admin-nav a:hover {
            background-color: var(--primary-red);
            color: var(--white);
        }

        .admin-nav a.active {
            background-color: var(--primary-red);
            color: var(--white);
            border-color: var(--primary-red);
        }

        .report-form, .reports-list {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-800);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(208, 0, 0, 0.1);
        }

        textarea.form-input {
            min-height: 150px;
            resize: vertical;
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

        .btn-danger {
            background-color: #dc3545;
            color: var(--white);
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            font-size: 0.9375rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .report-card {
            border: 1px solid var(--gray-200);
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            background-color: var(--white);
            transition: all 0.2s ease;
        }

        .report-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .report-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-meta {
            font-size: 0.875rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }

        .report-content {
            color: var(--gray-800);
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .report-type-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-incident {
            background-color: #ffe6e6;
            color: var(--primary-red);
        }

        .type-maintenance {
            background-color: #e6ffe6;
            color: #007200;
        }

        .type-general {
            background-color: #e6f3ff;
            color: #004080;
        }

        .report-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .admin-nav ul {
                flex-direction: column;
            }

            .admin-nav a {
                display: block;
                text-align: center;
                padding: 0.75rem;
            }

            .report-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
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
                    <h1>Admin - Reports</h1>
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
                <li><a href="admin_reports.php" class="active">Reports</a></li>
                <li><a href="user-dashboard.php">User View</a></li>
            </ul>
        </nav>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="report-form">
            <h2>Create New Report</h2>
            <form method="POST" action="" id="reportForm">
                <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="type">Type</label>
                    <select name="type" id="type" class="form-input" required>
                        <option value="">Select Type</option>
                        <option value="incident">Incident</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea name="content" id="content" class="form-input" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">Submit Report</button>
            </form>
        </div>

        <div class="reports-list">
            <h2>Recent Reports</h2>
            <?php if (empty($reports)): ?>
                <p>No reports found.</p>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <div class="report-title">
                            <?= htmlspecialchars($report['title']) ?>
                            <span class="report-type-badge type-<?= htmlspecialchars($report['category']) ?>">
                                <?= ucfirst(htmlspecialchars($report['category'])) ?>
                            </span>
                        </div>
                        <div class="report-meta">
                            Admin ID: <?= htmlspecialchars($report['admin_id']) ?> | 
                            Created on: <?= date("M j, Y g:i A", strtotime($report['created_at'])) ?>
                        </div>
                        <div class="report-content">
                            <?= nl2br(htmlspecialchars($report['description'])) ?>
                        </div>
                        <div class="report-actions">
                            <button class="btn btn-primary" onclick="window.location.href='edit_report.php?id=<?= $report['id'] ?>'">Edit</button>
                            <button class="btn btn-danger" onclick="deleteReport(<?= $report['id'] ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function logout() { window.location.href = 'logout.php'; }
        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Disable submit button after form submission
        document.getElementById('reportForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Submitting...';
        });

        function deleteReport(id) {
            if (confirm("Are you sure you want to delete this report?")) {
                // Disable the delete button to prevent multiple clicks
                event.target.disabled = true;
                event.target.textContent = 'Deleting...';
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const body = `report_id=${encodeURIComponent(id)}&_token=${encodeURIComponent(csrfToken)}`;

                fetch('delete_report.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': csrfToken
                    },
                    body
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Failed to delete: " + (data.message || "Unknown error"));
                        // Re-enable the button if deletion failed
                        event.target.disabled = false;
                        event.target.textContent = 'Delete';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while deleting the report.");
                    // Re-enable the button if there was an error
                    event.target.disabled = false;
                    event.target.textContent = 'Delete';
                });
            }
        }
    </script>
</body>
</html>
