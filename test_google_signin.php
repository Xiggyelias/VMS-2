<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Sign-In Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .google-btn {
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Google Sign-In Test</h1>
        
        <div id="status" class="status info">
            Testing Google Sign-In configuration...
        </div>

        <div class="google-btn">
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
        </div>

        <div id="result" style="margin-top: 20px;"></div>

        <div style="margin-top: 30px;">
            <h3>Configuration:</h3>
            <ul>
                <li><strong>Client ID:</strong> 561037470081-3fs3roso7v8gnq9idijoap15tn7sqr3l.apps.googleusercontent.com</li>
                <li><strong>Allowed Domain:</strong> africau.edu</li>
                <li><strong>Test URL:</strong> <?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?></li>
            </ul>
        </div>

        <div style="margin-top: 20px;">
            <a href="login.php" style="color: #007bff; text-decoration: none;">← Back to Login Page</a>
        </div>
    </div>

    <script>
        function updateStatus(message, type) {
            const status = document.getElementById('status');
            status.textContent = message;
            status.className = `status ${type}`;
        }

        function handleCredential(response) {
            console.log('Credential received:', response);
            updateStatus('Credential received! Processing...', 'info');
            
            document.getElementById('result').innerHTML = `
                <div class="status success">
                    <h4>✅ Google Sign-In Working!</h4>
                    <p><strong>Credential:</strong> ${response.credential.substring(0, 50)}...</p>
                    <p><strong>Client ID:</strong> ${response.clientId}</p>
                    <p><strong>Select By:</strong> ${response.select_by}</p>
                </div>
            `;
        }

        // Check if Google Sign-In loads
        window.addEventListener('load', function() {
            setTimeout(function() {
                const googleSignIn = document.querySelector('.g_id_signin');
                if (googleSignIn && googleSignIn.children.length > 0) {
                    updateStatus('✅ Google Sign-In loaded successfully!', 'success');
                } else {
                    updateStatus('❌ Google Sign-In failed to load. Check console for errors.', 'error');
                }
            }, 2000);
        });

        // Debug logging
        console.log('Google Sign-In Test Page Loaded');
        console.log('Client ID:', '561037470081-3fs3roso7v8gnq9idijoap15tn7sqr3l.apps.googleusercontent.com');
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>





















