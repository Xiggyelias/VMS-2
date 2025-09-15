<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSP Fix Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .test-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .icon-test { font-size: 2rem; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 CSP Fix Test</h1>
    
    <div class="test-section">
        <h3>📋 Font Awesome Test</h3>
        <div class="icon-test">
            <i class="fa fa-google"></i> Google Icon
            <i class="fa fa-user"></i> User Icon
            <i class="fa fa-car"></i> Car Icon
        </div>
        <p>If you see icons above, Font Awesome is loading correctly.</p>
    </div>

    <div class="test-section">
        <h3>🔑 Google Sign-In Test</h3>
        <div id="g_id_onload"
             data-client_id="561037470081-3fs3roso7v8gnq9idijoap15tn7sqr3l.apps.googleusercontent.com"
             data-context="signin"
             data-ux_mode="popup"
             data-callback="handleCredential"
             data-auto_select="true"
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
        <div id="googleTestResult"></div>
    </div>

    <div class="test-section">
        <h3>📝 Console Check</h3>
        <p>Open browser console (F12) and check for any CSP errors.</p>
        <p>You should see no CSP violations for:</p>
        <ul>
            <li>Font Awesome CSS</li>
            <li>Google Sign-In script</li>
        </ul>
    </div>

    <div class="test-section">
        <h3>🔗 Test Links</h3>
        <p><a href="login.php">Main Login Page</a></p>
        <p><a href="alternative_login.php">Alternative Login</a></p>
        <p><a href="debug_google.php">Debug Page</a></p>
    </div>

    <script>
        function handleCredential(response) {
            document.getElementById('googleTestResult').innerHTML = 
                '<div class="success">✅ Google Sign-In working! Credential received.</div>';
        }

        // Check if Google Sign-In loads
        window.addEventListener('load', function() {
            setTimeout(function() {
                const googleSignIn = document.querySelector('.g_id_signin');
                if (googleSignIn && googleSignIn.children.length > 0) {
                    document.getElementById('googleTestResult').innerHTML = 
                        '<div class="success">✅ Google Sign-In button loaded successfully!</div>';
                } else {
                    document.getElementById('googleTestResult').innerHTML = 
                        '<div class="error">❌ Google Sign-In button failed to load</div>';
                }
            }, 3000);
        });

        console.log('CSP Fix Test Page Loaded');
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>





















