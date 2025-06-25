<?php
// Test API functionality
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>MuciRadio API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .test-result { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>MuciRadio API Test</h1>

    <?php
    echo "<div class='test-result info'>Testing API functionality...</div>";

    // Test 1: Check if API file exists
    $apiFile = __DIR__ . '/api/stations.php';
    if (file_exists($apiFile)) {
        echo "<div class='test-result success'>✅ API file exists: " . $apiFile . "</div>";
    } else {
        echo "<div class='test-result error'>❌ API file not found: " . $apiFile . "</div>";
    }

    // Test 2: Check database connection
    try {
        require_once 'config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        echo "<div class='test-result success'>✅ Database connection successful</div>";

        // Test 3: Check if tables exist
        $tables = ['stations', 'users'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                echo "<div class='test-result success'>✅ Table '$table' exists</div>";
                
                // Count records
                $countStmt = $conn->query("SELECT COUNT(*) FROM $table");
                $count = $countStmt->fetchColumn();
                echo "<div class='test-result info'>ℹ️ Table '$table' has $count records</div>";
            } else {
                echo "<div class='test-result error'>❌ Table '$table' not found</div>";
            }
        }

        // Test 4: Fetch sample data
        $stmt = $conn->query("SELECT * FROM stations LIMIT 3");
        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($stations) {
            echo "<div class='test-result success'>✅ Sample station data:</div>";
            echo "<pre>" . print_r($stations, true) . "</pre>";
        }

    } catch (Exception $e) {
        echo "<div class='test-result error'>❌ Database error: " . $e->getMessage() . "</div>";
    }

    // Test 5: Test API endpoint via cURL
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/stations.php';
    echo "<div class='test-result info'>Testing API endpoint: <a href='$apiUrl' target='_blank'>$apiUrl</a></div>";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "<div class='test-result error'>❌ cURL Error: $error</div>";
    } else {
        echo "<div class='test-result success'>✅ HTTP Status: $httpCode</div>";
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if ($data) {
                echo "<div class='test-result success'>✅ API Response (JSON):</div>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<div class='test-result error'>❌ Invalid JSON response</div>";
                echo "<pre>Raw response: " . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<div class='test-result error'>❌ HTTP Error: $httpCode</div>";
            echo "<pre>Response: " . htmlspecialchars($response) . "</pre>";
        }
    }
    ?>

    <h3>Next Steps:</h3>
    <ul>
        <li><a href="setup.php">Run Database Setup</a></li>
        <li><a href="api/stations.php">Direct API Test</a></li>
        <li><a href="index.html">Main Application</a></li>
        <li><a href="admin.html">Admin Panel</a></li>
    </ul>

    <h3>Troubleshooting:</h3>
    <ul>
        <li>Make sure XAMPP Apache and MySQL services are running</li>
        <li>Check if URL rewriting is enabled in Apache</li>
        <li>Verify PHP extensions: PDO, PDO_MySQL are enabled</li>
        <li>Check error logs in XAMPP control panel</li>
    </ul>
</body>
</html>
