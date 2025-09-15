<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Sign-In Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
        .info { background: #d1ecf1; color: #0c5460; }
        .test-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔍 Google Sign-In Debug Diagnostic</h1>
    
    <div class="debug-section info">
        <h3>📋 System Information</h3>
        <p><strong>Current URL:</strong> <?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?></p>
        <p><strong>User Agent:</strong> <span id="userAgent"></span></p>
        <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>
    </div>

    <div class="debug-section">
        <h3>🔧 Configuration Check</h3>
        <p><strong>Google Client ID:</strong> 561037470081-3fs3roso7v8gnq9idijoap15tn7sqr3l.apps.googleusercontent.com</p>
        <p><strong>Allowed Domain:</strong> africau.edu</p>
        <p><strong>Environment:</strong> Development</p>
    </div>

    <div class="debug-section">
        <h3>🌐 Network Tests</h3>
        <button class="test-btn" onclick="testGoogleAPI()">Test Google API Access</button>
        <button class="test-btn" onclick="testInternetConnection()">Test Internet Connection</button>
        <div id="networkResults"></div>
    </div>

    <div class="debug-section">
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
        <div id="googleTestResults"></div>
    </div>

    <div class="debug-section">
        <h3>📝 Console Logs</h3>
        <div id="consoleLogs" style="background: #000; color: #0f0; padding: 10px; font-family: monospace; height: 200px; overflow-y: scroll;"></div>
    </div>

    <div class="debug-section">
        <h3>💡 Recommendations</h3>
        <div id="recommendations"></div>
    </div>

    <script>
        // Capture user agent
        document.getElementById('userAgent').textContent = navigator.userAgent;

        // Console logging
        const originalLog = console.log;
        const originalError = console.error;
        const consoleDiv = document.getElementById('consoleLogs');

        function addToConsole(message, type = 'log') {
            const timestamp = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff6b6b' : '#0f0';
            consoleDiv.innerHTML += `<div style="color: ${color}">[${timestamp}] ${message}</div>`;
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        console.log = function(...args) {
            originalLog.apply(console, args);
            addToConsole(args.join(' '));
        };

        console.error = function(...args) {
            originalError.apply(console, args);
            addToConsole(args.join(' '), 'error');
        };

        // Network tests
        async function testGoogleAPI() {
            const results = document.getElementById('networkResults');
            results.innerHTML = '<p>Testing Google API access...</p>';
            
            try {
                const response = await fetch('https://accounts.google.com/gsi/client', { method: 'HEAD' });
                if (response.ok) {
                    results.innerHTML = '<p class="success">✅ Google API accessible</p>';
                } else {
                    results.innerHTML = '<p class="error">❌ Google API not accessible (Status: ' + response.status + ')</p>';
                }
            } catch (error) {
                results.innerHTML = '<p class="error">❌ Google API not accessible: ' + error.message + '</p>';
            }
        }

        async function testInternetConnection() {
            const results = document.getElementById('networkResults');
            results.innerHTML = '<p>Testing internet connection...</p>';
            
            try {
                const response = await fetch('https://www.google.com', { method: 'HEAD' });
                if (response.ok) {
                    results.innerHTML = '<p class="success">✅ Internet connection working</p>';
                } else {
                    results.innerHTML = '<p class="warning">⚠️ Internet connection issues (Status: ' + response.status + ')</p>';
                }
            } catch (error) {
                results.innerHTML = '<p class="error">❌ No internet connection: ' + error.message + '</p>';
            }
        }

        // Google Sign-In callback
        function handleCredential(response) {
            const results = document.getElementById('googleTestResults');
            results.innerHTML = `
                <div class="success">
                    <h4>✅ Google Sign-In Working!</h4>
                    <p><strong>Credential:</strong> ${response.credential.substring(0, 50)}...</p>
                    <p><strong>Client ID:</strong> ${response.clientId}</p>
                    <p><strong>Select By:</strong> ${response.select_by}</p>
                </div>
            `;
            updateRecommendations('success');
        }

        // Check Google Sign-In loading
        window.addEventListener('load', function() {
            setTimeout(function() {
                const googleSignIn = document.querySelector('.g_id_signin');
                const results = document.getElementById('googleTestResults');
                
                if (googleSignIn && googleSignIn.children.length > 0) {
                    results.innerHTML = '<p class="success">✅ Google Sign-In loaded successfully!</p>';
                    updateRecommendations('loaded');
                } else {
                    results.innerHTML = '<p class="error">❌ Google Sign-In failed to load</p>';
                    updateRecommendations('failed');
                }
            }, 3000);
        });

        function updateRecommendations(status) {
            const recs = document.getElementById('recommendations');
            
            if (status === 'success') {
                recs.innerHTML = `
                    <div class="success">
                        <h4>🎉 Everything is working!</h4>
                        <p>Google Sign-In is functioning correctly. The issue might be on the main login page.</p>
                    </div>
                `;
            } else if (status === 'loaded') {
                recs.innerHTML = `
                    <div class="warning">
                        <h4>⚠️ Button loaded but not tested</h4>
                        <p>Google Sign-In button loaded but hasn't been tested yet. Try clicking it.</p>
                    </div>
                `;
            } else {
                recs.innerHTML = `
                    <div class="error">
                        <h4>🔧 Troubleshooting Steps:</h4>
                        <ol>
                            <li>Check if you're using HTTPS in production (Google requires it)</li>
                            <li>Verify Google Client ID in Google Cloud Console</li>
                            <li>Add <code>http://localhost</code> to authorized origins</li>
                            <li>Check if firewall/antivirus is blocking Google API</li>
                            <li>Try disabling browser extensions</li>
                            <li>Test in incognito/private mode</li>
                        </ol>
                    </div>
                `;
            }
        }

        // Auto-run tests
        window.addEventListener('load', function() {
            setTimeout(testGoogleAPI, 1000);
            setTimeout(testInternetConnection, 2000);
        });

        console.log('Debug page loaded');
    </script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</body>
</html>















