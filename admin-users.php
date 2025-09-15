<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Require admin access
requireAdmin();

// Generate CSRF token for POST requests
$csrfToken = SecurityMiddleware::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin - Users | Vehicle Registration System</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/v/bs5/dt-2.1.8/r-3.0.3/datatables.min.css" rel="stylesheet" />
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    <style>
        :root {
            --primary: #0d47a1; /* deep blue */
            --primary-alt: #083372;
            --accent: #c8a600; /* gold */
        }

        body { background-color: #f6f8fb; }

        .sidebar {
            width: 260px;
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--primary);
            color: #fff;
        }
        .sidebar .brand { font-weight: 700; letter-spacing: .5px; }
        .sidebar .au-logo { height: 36px; width: auto; display: block; }
        .sidebar .nav-link { color: #cfe0ff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); }
        .content { margin-left: 260px; }

        .topbar { background: #fff; border-bottom: 1px solid #eaeaea; }
        .topbar .btn-icon { color: #555; }
        .topbar .btn-icon:hover { color: var(--primary); }

        /* Table tweaks */
        table.dataTable tbody tr:hover { background-color: #f2f6ff; }
        .badge-active { background-color: #28a745; }
        .badge-suspended { background-color: #dc3545; }

        /* Mobile sidebar */
        @media (max-width: 992px) {
            .sidebar { position: fixed; transform: translateX(-100%); transition: transform .25s ease; z-index: 1040; }
            .sidebar.show { transform: translateX(0); }
            .content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar d-flex flex-column p-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-2">
                <img src="assets/images/AULogo.png" alt="AU Logo" class="au-logo" />
                <span class="brand h5 mb-0">VRS Admin</span>
            </div>
            <button class="btn btn-sm btn-light d-lg-none" id="btnCloseSidebar"><i class="fa fa-times"></i></button>
        </div>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="fa fa-gauge me-2"></i>Dashboard</a></li>
            <li><a href="admin-users.php" class="nav-link active"><i class="fa fa-users me-2"></i>Users</a></li>
            <li><a href="vehicle-list.php" class="nav-link"><i class="fa fa-car-side me-2"></i>Vehicles</a></li>
            <li><a href="admin_reports.php" class="nav-link"><i class="fa fa-file-lines me-2"></i>Reports</a></li>
            <li><a href="#" class="nav-link"><i class="fa fa-gear me-2"></i>Settings</a></li>
            <li><a href="javascript:void(0)" class="nav-link" onclick="logout()"><i class="fa fa-arrow-right-from-bracket me-2"></i>Logout</a></li>
        </ul>
        <hr class="text-white-50" />
        <div class="small">© <?= date('Y') ?> Vehicle Registration System</div>
    </nav>

    <!-- Top bar -->
    <header class="topbar py-2">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-primary d-lg-none" id="btnOpenSidebar"><i class="fa fa-bars"></i></button>
                <div class="ms-auto d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-link position-relative btn-icon" id="btnRefresh" data-bs-toggle="tooltip" title="Refresh table">
                        <i class="fa fa-rotate"></i>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-bell"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-item small text-muted">No new notifications</li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link btn-icon dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=Admin&background=0D47A1&color=fff" class="rounded-circle me-2" width="28" height="28" alt="admin" />
                            <span>Admin</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider" /></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="content">
        <div class="container-fluid py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                <h4 class="mb-0">User View</h4>
                <div class="d-flex gap-2">
                    <select id="filterType" class="form-select form-select-sm" style="min-width: 180px">
                        <option value="">All Registrant Types</option>
                        <option value="student">Student</option>
                        <option value="staff">Staff</option>
                        <option value="guest">Guest</option>
                    </select>
                    <select id="filterStatus" class="form-select form-select-sm" style="min-width: 160px">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="usersTable" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>User ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registrant Type</th>
                                    <th>Vehicles</th>
                                    <th>Registration Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Spinner -->
    <div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,.6); z-index: 1050;">
        <div class="d-flex h-100 w-100 align-items-center justify-content-center">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="viewContent" class="small text-muted">Loading...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="user_id" />
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="editFullName" name="fullName" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" id="editEmail" name="Email" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" id="editPhone" name="phone" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Registrant Type</label>
                            <select id="editRegistrantType" name="registrantType" class="form-select" required>
                                <option value="student">Student</option>
                                <option value="staff">Staff</option>
                                <option value="guest">Guest</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS + DataTables -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/v/bs5/dt-2.1.8/r-3.0.3/datatables.min.js"></script>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let usersTable;

        const showLoading = (show) => {
            document.getElementById('loadingOverlay').classList.toggle('d-none', !show);
        }

        function logout() { window.location.href = 'logout.php'; }

        function initTooltips() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
        }

        async function fetchUsers() {
            showLoading(true);
            const type = document.getElementById('filterType').value;
            const status = document.getElementById('filterStatus').value;
            const url = new URL('get_users.php', window.location.href);
            if (type) url.searchParams.set('type', type);
            if (status) url.searchParams.set('status', status);
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            showLoading(false);
            return data.users || [];
        }

        function statusBadge(status) {
            const s = (status || 'active').toLowerCase();
            return s === 'suspended'
                ? '<span class="badge badge-suspended">Suspended</span>'
                : '<span class="badge badge-active">Active</span>'
        }

        function actionButtons(row) {
            const suspendAction = row.status?.toLowerCase() === 'suspended' ? 'Activate' : 'Suspend';
            return `
                <div class="btn-group btn-group-sm" role="group">
                    <button class="btn btn-outline-primary" data-bs-toggle="tooltip" title="View Details" onclick="onView(${row.applicant_id})"><i class="fa fa-eye"></i></button>
                    <button class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit User" onclick="onEdit(${row.applicant_id})"><i class="fa fa-pen"></i></button>
                    <button class="btn btn-outline-warning" data-bs-toggle="tooltip" title="${suspendAction}" onclick="onToggleSuspend(${row.applicant_id})"><i class="fa fa-user-slash"></i></button>
                    <button class="btn btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="onDelete(${row.applicant_id})"><i class="fa fa-trash"></i></button>
                </div>
            `;
        }

        async function loadTable() {
            const users = await fetchUsers();
            if (usersTable) {
                usersTable.clear();
                usersTable.rows.add(users);
                usersTable.draw();
                initTooltips();
                return;
            }
            usersTable = new DataTable('#usersTable', {
                data: users,
                responsive: true,
                columns: [
                    { data: 'applicant_id' },
                    { data: 'fullName' },
                    { data: 'Email' },
                    { data: 'phone' },
                    { data: 'registrantType', render: (d) => (d||'').toString().charAt(0).toUpperCase() + (d||'').toString().slice(1) },
                    { data: 'vehicles_count' },
                    { data: 'registration_date' },
                    { data: null, render: (row) => statusBadge(row.status) },
                    { data: null, orderable: false, searchable: false, render: (row) => actionButtons(row) },
                ],
                order: [[6, 'desc']],
                pageLength: 10,
            });
            initTooltips();
        }

        async function onView(userId) {
            const modal = new bootstrap.Modal(document.getElementById('viewModal'));
            document.getElementById('viewContent').innerHTML = 'Loading...';
            modal.show();
            const res = await fetch('view_user.php?user_id=' + encodeURIComponent(userId), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) {
                document.getElementById('viewContent').innerHTML = '<div class="text-danger">Failed to load user details.</div>';
                return;
            }
            const u = data.user;
            const vehiclesHtml = (u.vehicles || []).map(v => `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><strong>${v.plate}</strong> — ${v.make || ''}</span>
                    <span class="badge bg-secondary">${(v.status||'').toString().toUpperCase()}</span>
                </li>`).join('');
            document.getElementById('viewContent').innerHTML = `
                <div class="row g-3">
                    <div class="col-md-6">
                        <div><strong>Full Name:</strong> ${u.fullName || ''}</div>
                        <div><strong>Email:</strong> ${u.Email || ''}</div>
                        <div><strong>Phone:</strong> ${u.phone || ''}</div>
                    </div>
                    <div class="col-md-6">
                        <div><strong>Registrant Type:</strong> ${(u.registrantType||'').toString().toUpperCase()}</div>
                        <div><strong>Status:</strong> ${statusBadge(u.status)}</div>
                        <div><strong>Registered Vehicles:</strong> ${u.vehicles_count}</div>
                    </div>
                </div>
                <hr/>
                <h6>Vehicles</h6>
                <ul class="list-group list-group-flush">${vehiclesHtml || '<li class="list-group-item small text-muted">No vehicles</li>'}</ul>
                <hr/>
                <h6>Registration History</h6>
                <div class="small text-muted">First registration: ${u.registration_date || 'N/A'}</div>
            `;
        }

        async function onEdit(userId) {
            const res = await fetch('view_user.php?user_id=' + encodeURIComponent(userId), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return alert('Failed to load user');
            const u = data.user;
            document.getElementById('editUserId').value = u.applicant_id;
            document.getElementById('editFullName').value = u.fullName || '';
            document.getElementById('editEmail').value = u.Email || '';
            document.getElementById('editPhone').value = u.phone || '';
            document.getElementById('editRegistrantType').value = (u.registrantType || '').toLowerCase();
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        document.getElementById('editForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const body = new URLSearchParams(new FormData(form));
            showLoading(true);
            const res = await fetch('update_user.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken, 'Accept': 'application/json' },
                body
            });
            showLoading(false);
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Update failed');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            await loadTable();
        });

        async function onToggleSuspend(userId) {
            const confirmText = 'Toggle user status (Suspend/Activate)?';
            if (!confirm(confirmText)) return;
            showLoading(true);
            const res = await fetch('update_user.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle_status&user_id=${encodeURIComponent(userId)}`
            });
            showLoading(false);
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Operation failed');
            await loadTable();
        }

        async function onDelete(userId) {
            if (!confirm('Delete this user? This cannot be undone.')) return;
            showLoading(true);
            const res = await fetch('delete_user.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${encodeURIComponent(userId)}`
            });
            showLoading(false);
            const data = await res.json();
            if (!data.success) return alert(data.message || 'Delete failed');
            await loadTable();
        }

        document.getElementById('btnRefresh').addEventListener('click', loadTable);
        document.getElementById('filterType').addEventListener('change', loadTable);
        document.getElementById('filterStatus').addEventListener('change', loadTable);
        document.getElementById('btnOpenSidebar').addEventListener('click', () => document.getElementById('sidebar').classList.add('show'));
        document.getElementById('btnCloseSidebar').addEventListener('click', () => document.getElementById('sidebar').classList.remove('show'));

        document.addEventListener('DOMContentLoaded', loadTable);
    </script>
</body>
</html>

