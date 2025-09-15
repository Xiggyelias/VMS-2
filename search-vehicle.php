<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Generate CSRF token
$csrfToken = SecurityMiddleware::generateCSRFToken();

function getDBConnection() {
    $conn = new mysqli("localhost", "root", "", "vehicleregistrationsystem");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

function escapeHTML($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------- Handle Manual Search Form Submission ----------
$searchResults = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_value'])) {
    $searchValue = trim($_POST['search_value']);

    if (empty($searchValue)) {
        $error = "Please enter a plate number";
    } else {
        $conn = getDBConnection();

        $stmt = $conn->prepare("
            SELECT 
                v.vehicle_id,
                v.applicant_id,
                v.regNumber,
                v.make,
                v.owner,
                v.address,
                v.PlateNumber,
                v.registration_date,
                v.disk_number,
                a.idNumber,
                a.phone,
                a.email,
                d.fullname,
                d.licenseNumber
            FROM vehicles v
            LEFT JOIN applicants a ON v.applicant_id = a.applicant_id
            LEFT JOIN authorized_driver d ON v.vehicle_id = d.vehicle_id
            WHERE v.PlateNumber = ?
        ");
        $stmt->bind_param("s", $searchValue);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $searchResults = $result->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = "No vehicle found with the provided plate number";
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Search - Manual Entry</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    background: #121212;
    color: #eee;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    padding: 2rem 1rem;
    display: flex;
    justify-content: center;
  }
  .container {
            max-width: 1200px;
    width: 100%;
    background: #1e1e1e;
    border-radius: 12px;
    box-shadow: 0 0 20px #d00000aa;
    padding: 2rem;
  }
  header {
    text-align: center;
    margin-bottom: 2rem;
  }
  header img {
    height: 60px;
    filter: brightness(0) invert(1);
    margin-bottom: 1rem;
  }
  header h1 {
    font-size: 2.4rem;
    color: #d00000;
    letter-spacing: 2px;
    margin-bottom: 0.2rem;
    font-weight: 700;
  }
  header p {
    color: #bbb;
    font-size: 1.1rem;
        }
        .search-method {
            background: #292929;
            padding: 1.5rem;
            border-radius: 8px;
            border: 2px solid #333;
            margin-bottom: 2rem;
        }
        .search-method h3 {
            color: #d00000;
            margin-bottom: 1rem;
            font-size: 1.2rem;
  }
  input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #444;
            border-radius: 6px;
            background: #1e1e1e;
    color: #eee;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        .buttons {
            display: flex;
            gap: 1rem;
  }
  button {
    background: #d00000;
    border: none;
            padding: 0.75rem 1.5rem;
    border-radius: 8px;
    color: white;
    font-weight: 700;
            font-size: 1rem;
    cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover {
            background: #ff0000;
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: 600;
            text-align: center;
  }
  .alert-error {
    background: #5c0000;
    color: #ff7777;
  }
  .tabs {
    margin-top: 1rem;
  }
  .tab-buttons {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }
  .tab-buttons button {
    flex: 1;
    background: #292929;
    color: #eee;
    border: 1px solid #444;
    padding: 0.75rem;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
  }
  .tab-buttons button.active {
    background: #d00000;
    border-color: #d00000;
  }
  .tab-content {
    display: none;
            padding: 1rem;
            background: #292929;
            border-radius: 8px;
  }
  .tab-content.active {
    display: block;
  }
  .info-group {
    margin-bottom: 1rem;
  }
  .info-label {
    font-size: 0.85rem;
    color: #bbb;
    text-transform: uppercase;
  }
  .info-value {
    font-size: 1.1rem;
    color: #fff;
  }
        .nav-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .nav-btn {
            background: #292929;
            color: #eee;
            border: 2px solid #444;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .nav-btn:hover {
            background: #d00000;
            border-color: #d00000;
            transform: translateY(-2px);
        }
        .nav-btn.active {
            background: #d00000;
            border-color: #d00000;
        }
        @media (max-width: 768px) {
            .buttons {
                flex-direction: column;
            }
            button {
            width: 100%;
            }
            .nav-buttons {
                flex-direction: column;
        }
            .nav-btn {
            width: 100%;
            text-align: center;
        }
        }
    </style>
</head>
<body>
<main class="container">
  <header>
    <img src="AULogo.png" alt="AU Logo" />
            <h1>Vehicle Search</h1>
            <p>Search vehicle information by plate number</p>
  </header>

    <div class="nav-buttons">
        <a href="search-vehicle.php" class="nav-btn active">Manual Search</a>
        <a href="scan-vehicle.php" class="nav-btn">Camera Scanner</a>
        </div>

        <!-- Manual Entry Section -->
            <div class="search-method">
                <h3>Manual Entry</h3>
                <form method="POST" action="">
            <!-- CSRF Token -->
            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
                    <input type="text" name="search_value" placeholder="Enter plate number" 
      value="<?= escapeHTML($_POST['search_value'] ?? '') ?>" />
                    <div class="buttons">
    <button type="submit">Search</button>
                        <button type="button" onclick="clearSearch()">Clear</button>
                    </div>
  </form>
        </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= escapeHTML($error) ?></div>
  <?php endif; ?>

        <div id="results" style="display: <?= $searchResults ? 'block' : 'none' ?>;">
    <div class="tabs">
      <div class="tab-buttons">
                    <button class="active" data-tab="vehicleTab">🚗 Vehicle Info</button>
                    <button data-tab="ownerTab">👤 Owner Info</button>
                    <button data-tab="driverTab">🧑‍✈️ Authorized Driver</button>
      </div>

                <?php if ($searchResults): ?>
      <?php foreach ($searchResults as $vehicle): ?>
        <div id="vehicleTab" class="tab-content active">
          <div class="info-group"><div class="info-label">Make</div><div class="info-value"><?= escapeHTML($vehicle['make']) ?></div></div>
          <div class="info-group"><div class="info-label">Reg Number</div><div class="info-value"><?= escapeHTML($vehicle['regNumber']) ?></div></div>
          <div class="info-group"><div class="info-label">Disk Number</div><div class="info-value"><?= escapeHTML($vehicle['disk_number'] ?: 'Not Assigned') ?></div></div>
          <div class="info-group"><div class="info-label">Registration Date</div><div class="info-value"><?= escapeHTML($vehicle['registration_date']) ?></div></div>
        </div>

        <div id="ownerTab" class="tab-content">
          <div class="info-group"><div class="info-label">Owner</div><div class="info-value"><?= escapeHTML($vehicle['owner']) ?></div></div>
          <div class="info-group"><div class="info-label">ID Number</div><div class="info-value"><?= escapeHTML($vehicle['idNumber']) ?></div></div>
          <div class="info-group"><div class="info-label">Phone</div><div class="info-value"><?= escapeHTML($vehicle['phone']) ?></div></div>
          <div class="info-group"><div class="info-label">Email</div><div class="info-value"><?= escapeHTML($vehicle['email']) ?></div></div>
        </div>

        <div id="driverTab" class="tab-content">
          <div class="info-group"><div class="info-label">Full Name</div><div class="info-value"><?= escapeHTML($vehicle['fullname'] ?: 'N/A') ?></div></div>
          <div class="info-group"><div class="info-label">License Number</div><div class="info-value"><?= escapeHTML($vehicle['licenseNumber'] ?: 'N/A') ?></div></div>
        </div>
      <?php endforeach; ?>
                <?php endif; ?>
            </div>
    </div>
</main>

    <script>
        // Clear button functionality
    function clearSearch() {
                document.querySelector('input[name="search_value"]').value = '';
        document.getElementById('results').style.display = 'none';
                const errorAlert = document.querySelector('.alert-error');
                if (errorAlert) errorAlert.remove();
        }

    // Tab functionality
        document.querySelectorAll('.tab-buttons button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
                document.querySelectorAll('.tab-buttons button').forEach(btn => btn.classList.remove('active'));
                document.getElementById(button.dataset.tab).classList.add('active');
                button.classList.add('active');
            });
        });
    </script>
</body>
</html>
