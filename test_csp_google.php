<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Sign-In CSP Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px; 
            background: #f5f5f5;
        }
        .test-section { 
            background: white; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .google-btn {
            margin: 20px 0;
            text-align: center;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .loading { background: #fff3cd; color: #856404; }
        .working { background: #d4edda; color: #155724; }
        .failed { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h1>🔧 Google Sign-In CSP Test</h1>
    
    <div class="test-section">
        <h3>📋 Current CSP Status</h3>
        <div id="cspStatus" class="status loading">Checking CSP configuration...</div>
        <p>This page tests if the Content Security Policy allows Google Sign-In to work properly.</p>
    </div>

    <div class="test-section">
        <h3>🔑 Google Sign-In Test</h3>
        <div class="google-btn">
            <div id="g_id_onload"
                 data-client_id="561037470081-3fs3roso7v8gnq9idijoap15tn7sqr3l.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-callback="handleGoogleCredential"
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
        </div>
        <div id="googleTestResult"></div>
    </div>

    <div class="test-section">
        <h3>📝 Console Check</h3>
        <p>Open browser console (F12) and check for any CSP errors.</p>
        <p>You should see no CSP violations for:</p>
        <ul>
            <li>Google Sign-In script loading</li>
            <li>Google Sign-In stylesheet loading</li>
            <li>Google Sign-In iframe/popup</li>
        </ul>
    </div>

    <div class="test-section">
        <h3>🔗 Test Links</h3>
        <p><a href="login.php">Main Login Page</a></p>
        <p><a href="test_csp_fix.php">CSP Fix Test</a></p>
        <p><a href="debug_google.php">Debug Google Page</a></p>
    </div>

    <script>
        let cspWorking = false;
        let googleLoaded = false;

        function handleGoogleCredential(response) {
            document.getElementById('googleTestResult').innerHTML = 
                '<div class="success">✅ Google Sign-In working! Credential received: ' + 
                response.credential.substring(0, 50) + '...</div>';
        }

        function updateCSPStatus() {
            const statusDiv = document.getElementById('cspStatus');
            if (cspWorking && googleLoaded) {
                statusDiv.className = 'status working';
                statusDiv.textContent = '✅ CSP Working - Google Sign-In Loaded Successfully!';
            } else if (cspWorking) {
                statusDiv.className = 'status loading';
                statusDiv.textContent = '⏳ CSP Working - Waiting for Google Sign-In to load...';
            } else {
                statusDiv.className = 'status failed';
                statusDiv.textContent = '❌ CSP Issues Detected - Check console for errors';
            }
        }

        // Check if Google Sign-In loads
        window.addEventListener('load', function() {
            // Check CSP by trying to load Google resources
            cspWorking = true; // Assume working initially
            
            setTimeout(function() {
                const googleSignIn = document.querySelector('.g_id_signin');
                if (googleSignIn && googleSignIn.children.length > 0) {
                    googleLoaded = true;
                    document.getElementById('googleTestResult').innerHTML = 
                        '<div class="success">✅ Google Sign-In button loaded successfully!</div>';
                } else {
                    document.getElementById('googleTestResult').innerHTML = 
                        '<div class="error">❌ Google Sign-In button failed to load</div>';
                    cspWorking = false;
                }
                updateCSPStatus();
            }, 3000);
        });

        // Monitor for CSP violations
        window.addEventListener('securitypolicyviolation', function(e) {
            console.error('CSP Violation:', e);
            cspWorking = false;
            updateCSPStatus();
        });

        console.log('Google Sign-In CSP Test Page Loaded');
    </script>
    
    <!-- Google Sign-In Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>


















