<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/middleware/security.php';
SecurityMiddleware::initialize();

// Generate CSRF token for forms
$csrfToken = SecurityMiddleware::generateCSRFToken();

// Prepare alert messages (rendered later inside the page)
$alert_type = null;
$alert_message = null;

if (isset($_GET['error'])) {
    $error = $_GET['error'];
    if ($error === 'empty_fields') {
        $alert_type = 'danger';
        $alert_message = 'Please fill in all fields.';
    } elseif ($error === 'invalid_password') {
        $alert_type = 'danger';
        $alert_message = 'Invalid password.';
    } elseif ($error === 'not_found') {
        $alert_type = 'warning';
        $alert_message = 'Account not found.';
    }
}

if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $alert_type = 'success';
    $alert_message = 'Your password has been reset successfully. Please login with your new password.';
}

// Handle login result
if (isset($login_successful) && $login_successful) {
    // Save logged-in user ID to session
    $_SESSION['user_id'] = $user['applicant_id'];
    header("Location: user-dashboard.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Login - Vehicle Registration System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fff 0%, #f3f6ff 35%, #fdeeee 100%);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .login-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }

        .login-tab {
            padding: 1rem 2rem;
            cursor: pointer;
            color: #666;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all 0.3s ease;
        }

        .login-tab.active {
            color: var(--primary-red);
            border-bottom-color: var(--primary-red);
        }

        .login-form-container {
            display: none;
        }

        .login-form-container.active {
            display: block;
        }

        .login-left {
            background-color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-right {
            background-color: var(--primary-red);
            color: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .login-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0) 100%);
        }

        .welcome-text {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .welcome-text h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .welcome-text p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 400px;
            line-height: 1.6;
        }

        .login-form {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 2rem 2rem 1.5rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header .logo {
            width: 84px;
            height: 84px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 8px 22px rgba(0,0,0,0.08);
            border: 1px solid #eef0f4;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group input, .input-group select {
            width: 100%;
            padding: 1rem 3rem 1rem 3rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #fff;
        }

        .input-group input:focus, .input-group select:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(208, 0, 0, 0.12);
            outline: none;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            font-size: 1rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9aa0a6;
            cursor: pointer;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .forgot-password {
            color: var(--primary-red);
            text-decoration: none;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background-color: var(--primary-red);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform .1s ease, background-color 0.2s ease;
            box-shadow: 0 8px 16px rgba(208,0,0,0.15);
        }

        .login-button:hover {
            background-color: #b00000;
            transform: translateY(-1px);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .register-link a {
            color: var(--primary-red);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        /* Google Sign-In specific styles */
        .g_id_signin {
            margin: 1rem 0;
            min-height: 40px;
        }

        #googleLoginForm {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }



        @media (max-width: 768px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .login-right {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-left">
            <div class="login-form">
                <div class="login-header">
                    <div class="logo">
                        <img src="assets/images/AULogo.png" alt="AU Logo" style="height: 56px; width: auto;">
                    </div>
                    <h1 style="color: var(--primary-red); margin: 0;">Welcome Back</h1>
                    <p style="color: #666; margin-top: 0.5rem;">Please log in to your account</p>
                </div>

                <?php if ($alert_type && $alert_message): ?>
                    <div class="alert alert-<?= htmlspecialchars($alert_type) ?>">
                        <?= htmlspecialchars($alert_message) ?>
                    </div>
                <?php endif; ?>


                <div id="googleLoginForm" class="login-form-container active" role="tabpanel" aria-labelledby="tab-google">
                    <div style="display:flex; flex-direction:column; gap:1rem; align-items:center;">
                        <!-- Google Sign-In Button -->
                        <div id="g_id_onload"
                             data-client_id="<?= htmlspecialchars(GOOGLE_CLIENT_ID) ?>"
                             data-context="signin"
                             data-ux_mode="popup"
                             data-callback="handleGoogleCredential"
                             data-auto_select="false"
                             data-itp_support="true">
                        </div>
                        <div class="g_id_signin"
                             data-type="standard"
                             data-shape="rectangular"
                             data-theme="outline"
                             data-text="continue_with"
                             data-size="large"
                             data-logo_alignment="left">
                        </div>
                        
                    </div>
                </div>

            </div>
        </div>

        <div class="login-right">
            <div class="welcome-text">
                <h2>Vehicle Registration System</h2>
                <p>Manage your vehicle registrations efficiently and securely. Keep track of all your vehicles in one place.</p>
            </div>
        </div>
    </div>

    <script>
        // Check if Google Sign-In loads properly
        window.addEventListener('load', function() {
            setTimeout(function() {
                const googleSignIn = document.querySelector('.g_id_signin');
                
                if (googleSignIn && googleSignIn.children.length) {
                    console.log('Google Sign-In loaded successfully');
                } else {
                    console.log('Google Sign-In not loaded');
                }
            }, 3000); // Wait 3 seconds for Google Sign-In to load
        });


        function handleGoogleCredential(response) {
            console.log('Google credential received:', response);
            
            fetch('google_auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credential: response.credential })
            })
            .then(r => r.json())
            .then(data => {
                console.log('Auth response:', data);
                if (data && data.success && data.requires_type_selection) {
                    // First-time login and role ambiguous: prompt for registrant type
                    const tempUserId = data.temp_user_id;
                    const suggested = (data.user_info && data.user_info.derived_type) ? data.user_info.derived_type : 'student';
                    showRoleSelection(tempUserId, suggested);
                    return;
                }
                if (data && data.success) {
                    window.location.href = data.redirect || 'user-dashboard.php';
                } else {
                    console.error('Google Sign-In failed:', data && data.message);
                    alert('Sign-in failed: ' + ((data && data.message) || 'Unknown error'));
                }
            })
            .catch((error) => {
                console.error('Auth error:', error);
                alert('Sign-in failed. Please try again.');
            });
        }



        // Debug function to check Google Sign-In status
        function checkGoogleSignInStatus() {
            const googleSignIn = document.querySelector('.g_id_signin');
            console.log('Google Sign-In element:', googleSignIn);
            if (googleSignIn) {
                console.log('Google Sign-In children:', googleSignIn.children.length);
            }
        }

        // Call debug function after page loads
        window.addEventListener('load', function() {
            setTimeout(checkGoogleSignInStatus, 2000);
        });

        // Simple one-time role selection UI
        function showRoleSelection(userId, suggested) {
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100%';
            overlay.style.height = '100%';
            overlay.style.background = 'rgba(0,0,0,0.5)';
            overlay.style.display = 'flex';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            overlay.style.zIndex = '2000';

            const panel = document.createElement('div');
            panel.style.background = '#fff';
            panel.style.padding = '20px';
            panel.style.borderRadius = '10px';
            panel.style.width = '95%';
            panel.style.maxWidth = '420px';
            panel.style.boxShadow = '0 10px 30px rgba(0,0,0,0.15)';

            panel.innerHTML = `
                <h3 style="margin-top:0; color: var(--primary-red);">Select Your Role</h3>
                <p style="margin: 0 0 10px; color:#444;">Please select your registrant type to complete your first-time login.</p>
                <div style="display:flex; gap:.5rem; margin: 10px 0 15px;">
                    <button data-role="student" class="btn btn-primary" style="flex:1;">Student</button>
                    <button data-role="staff" class="btn btn-secondary" style="flex:1;">Staff</button>
                    <button data-role="guest" class="btn btn-secondary" style="flex:1;">Guest</button>
                </div>
                <div style="font-size:.9rem; color:#666;">Suggested: <strong id="suggestedRole"></strong></div>
            `;

            overlay.appendChild(panel);
            document.body.appendChild(overlay);

            panel.querySelector('#suggestedRole').textContent = (suggested || 'student').toUpperCase();
            panel.querySelectorAll('button[data-role]').forEach(btn => {
                btn.addEventListener('click', () => finalizeRole(userId, btn.getAttribute('data-role'), overlay));
            });
        }

        function finalizeRole(userId, role, overlay) {
            const payload = { user_id: userId, registrant_type: role };
            fetch('finalize_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    window.location.href = data.redirect || 'user-dashboard.php';
                } else {
                    alert('Failed to save selection. Please try again.');
                }
            })
            .catch(err => {
                console.error('Finalize role error:', err);
                alert('Failed to save selection. Please try again.');
            });
        }
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html> 