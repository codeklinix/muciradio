<!DOCTYPE html>
<html>
<head>
    <title>API Debug - MuciRadio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        .btn { background: #FF6B35; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #e55a2e; }
    </style>
</head>
<body>
    <h1>🔧 MuciRadio API Debug</h1>

    <div class="debug-section">
        <h2>1. Database Connection Test</h2>
        <?php
        try {
            // Test basic PHP
            echo "<div class='success'>✅ PHP is working</div>";
            
            // Test PDO extension
            if (extension_loaded('pdo') && extension_loaded('pdo_mysql')) {
                echo "<div class='success'>✅ PDO MySQL extension is loaded</div>";
            } else {
                echo "<div class='error'>❌ PDO MySQL extension is NOT loaded</div>";
            }
            
            // Test database connection
            $pdo = new PDO('mysql:host=localhost', 'root', '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "<div class='success'>✅ Connected to MySQL server</div>";
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE 'muci_radio'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='success'>✅ Database 'muci_radio' exists</div>";
                
                // Connect to specific database
                $pdo = new PDO('mysql:host=localhost;dbname=muci_radio', 'root', '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check tables
                $tables = ['stations', 'users'];
                foreach ($tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
                        $count = $countStmt->fetchColumn();
                        echo "<div class='success'>✅ Table '$table' exists with $count records</div>";
                    } else {
                        echo "<div class='error'>❌ Table '$table' does not exist</div>";
                    }
                }
            } else {
                echo "<div class='error'>❌ Database 'muci_radio' does not exist</div>";
                echo "<div class='info'>💡 You need to run the setup script first</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>2. API Endpoints Test</h2>
        <?php
        $apiTests = [
            'Stations API' => 'api/stations.php',
            'Playlists API' => 'api/playlists.php'
        ];
        
        foreach ($apiTests as $name => $url) {
            echo "<h3>$name</h3>";
            
            if (file_exists($url)) {
                echo "<div class='success'>✅ File exists: $url</div>";
                
                // Test if file is readable
                if (is_readable($url)) {
                    echo "<div class='success'>✅ File is readable</div>";
                    
                    // Show first few lines
                    $content = file_get_contents($url, false, null, 0, 200);
                    echo "<div class='info'>📄 First 200 characters:</div>";
                    echo "<pre>" . htmlspecialchars($content) . "...</pre>";
                } else {
                    echo "<div class='error'>❌ File is not readable</div>";
                }
            } else {
                echo "<div class='error'>❌ File does not exist: $url</div>";
            }
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>3. Server Environment</h2>
        <?php
        $serverInfo = [
            'PHP Version' => phpversion(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
            'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Unknown'
        ];
        
        foreach ($serverInfo as $key => $value) {
            echo "<div class='info'><strong>$key:</strong> $value</div>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>4. Quick Actions</h2>
        <button class="btn" onclick="window.location.href='setup.php'">Run Database Setup</button>
        <button class="btn" onclick="window.location.href='fix_images.php'">Fix Station Images</button>
        <button class="btn" onclick="window.location.href='test_api.php'">Test API</button>
        <button class="btn" onclick="testApiCall()">Test API via JavaScript</button>
        
        <div id="jsTestResult"></div>
    </div>

    <div class="debug-section">
        <h2>5. File Permissions Check</h2>
        <?php
        $filesToCheck = [
            'api/stations.php',
            'api/playlists.php',
            'config/database.php',
            'admin.html',
            'index.php'
        ];
        
        foreach ($filesToCheck as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                $readable = is_readable($file) ? '✅' : '❌';
                $writable = is_writable($file) ? '✅' : '❌';
                echo "<div class='info'><strong>$file:</strong> Readable $readable | Writable $writable | Perms: " . substr(sprintf('%o', $perms), -4) . "</div>";
            } else {
                echo "<div class='error'><strong>$file:</strong> File does not exist</div>";
            }
        }
        ?>
    </div>

    <script>
        async function testApiCall() {
            const resultDiv = document.getElementById('jsTestResult');
            resultDiv.innerHTML = '<div class="info">Testing API call...</div>';
            
            try {
                const response = await fetch('api/stations.php');
                
                if (response.ok) {
                    const data = await response.json();
                    resultDiv.innerHTML = `
                        <div class="success">✅ API call successful</div>
                        <div class="info">Response status: ${response.status}</div>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="error">❌ API call failed</div>
                        <div class="info">Status: ${response.status} ${response.statusText}</div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="error">❌ Network error: ${error.message}</div>
                `;
            }
        }
    </script>
</body>
</html>
