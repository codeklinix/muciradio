<?php
// Simple API test
header('Content-Type: text/plain');

echo "=== MuciRadio API Simple Test ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

echo "1. Testing database connection...\n";
try {
    $pdo = new PDO('mysql:host=localhost;dbname=muci_radio', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
    // Check stations table
    $stmt = $pdo->query('SELECT COUNT(*) FROM stations');
    $count = $stmt->fetchColumn();
    echo "✅ Found $count stations in database\n";
    
    if ($count > 0) {
        $stmt = $pdo->query('SELECT id, name, genre FROM stations LIMIT 3');
        $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Sample stations:\n";
        foreach ($stations as $station) {
            echo "   - ID: {$station['id']}, Name: {$station['name']}, Genre: {$station['genre']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "💡 Try running setup.php first\n";
}

echo "\n2. Testing API file existence...\n";
$apiFile = __DIR__ . '/api/stations.php';
if (file_exists($apiFile)) {
    echo "✅ API file exists: $apiFile\n";
    if (is_readable($apiFile)) {
        echo "✅ API file is readable\n";
    } else {
        echo "❌ API file is not readable\n";
    }
} else {
    echo "❌ API file does not exist: $apiFile\n";
}

echo "\n3. Testing API execution...\n";
try {
    // Capture output from stations.php
    ob_start();
    include 'api/stations.php';
    $output = ob_get_clean();
    
    echo "✅ API executed without fatal errors\n";
    echo "📄 API Output (first 500 chars):\n";
    echo substr($output, 0, 500) . "\n";
    
    // Try to decode as JSON
    $data = json_decode($output, true);
    if ($data !== null) {
        echo "✅ API output is valid JSON\n";
        if (isset($data['success'])) {
            echo "✅ API response has success field: " . ($data['success'] ? 'true' : 'false') . "\n";
        }
    } else {
        echo "❌ API output is not valid JSON\n";
    }
} catch (Exception $e) {
    echo "❌ Error executing API: " . $e->getMessage() . "\n";
}

echo "\n4. Server environment check...\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Extensions: ";
if (extension_loaded('pdo')) echo "PDO ✅ ";
if (extension_loaded('pdo_mysql')) echo "PDO_MySQL ✅ ";
if (extension_loaded('json')) echo "JSON ✅ ";
echo "\n";

echo "\n=== Test Complete ===\n";
?>

<br><br>
<a href="setup.php" style="background: #FF6B35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Run Setup</a>
<a href="debug_api.php" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">Full Debug</a>
<a href="admin.html" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">Try Admin</a>
