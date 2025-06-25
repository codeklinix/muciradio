<?php
// Test script to verify broadcaster functionality
require_once 'config/database.php';

echo "<h2>Broadcaster Functionality Test</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<h3>1. Database Connection: ✅ Success</h3>";
    
    // Check if users table exists and has radio_owner role
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'role'");
    $stmt->execute();
    $roleColumn = $stmt->fetch();
    
    if ($roleColumn) {
        echo "<h3>2. Users table with role column: ✅ Success</h3>";
        echo "<p>Role column type: " . $roleColumn['Type'] . "</p>";
    } else {
        echo "<h3>2. Users table role column: ❌ Not found</h3>";
    }
    
    // Check if stations table has user_id column
    $stmt = $conn->prepare("SHOW COLUMNS FROM stations LIKE 'user_id'");
    $stmt->execute();
    $userIdColumn = $stmt->fetch();
    
    if ($userIdColumn) {
        echo "<h3>3. Stations table with user_id column: ✅ Success</h3>";
        echo "<p>User_id column type: " . $userIdColumn['Type'] . "</p>";
    } else {
        echo "<h3>3. Stations table user_id column: ❌ Not found</h3>";
    }
    
    // Test creating a sample broadcaster account
    echo "<h3>4. Testing Broadcaster Account Creation</h3>";
    
    $testUsername = 'testbroadcaster';
    $testEmail = 'test@broadcaster.com';
    $testPassword = password_hash('testpass123', PASSWORD_DEFAULT);
    
    // Check if test user already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->execute([$testUsername, $testEmail]);
    
    if ($checkStmt->fetch()) {
        echo "<p>Test broadcaster account already exists</p>";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'radio_owner')");
        $result = $insertStmt->execute([$testUsername, $testEmail, $testPassword]);
        
        if ($result) {
            echo "<p>✅ Test broadcaster account created successfully</p>";
            echo "<p>Username: $testUsername</p>";
            echo "<p>Password: testpass123</p>";
        } else {
            echo "<p>❌ Failed to create test broadcaster account</p>";
        }
    }
    
    // List all files created for broadcaster functionality
    echo "<h3>5. Broadcaster Files Created</h3>";
    $broadcasterFiles = [
        'broadcaster.html' => 'Broadcaster login/registration page',
        'broadcaster_dashboard.html' => 'Broadcaster dashboard',
        'api/broadcaster_auth.php' => 'Broadcaster authentication API',
        'api/broadcaster_stations.php' => 'Broadcaster stations management API'
    ];
    
    foreach ($broadcasterFiles as $file => $description) {
        if (file_exists($file)) {
            echo "<p>✅ $file - $description</p>";
        } else {
            echo "<p>❌ $file - $description (Missing)</p>";
        }
    }
    
    echo "<h3>6. Testing URLs</h3>";
    echo "<p>🔗 Broadcaster Portal: <a href='broadcaster.html' target='_blank'>broadcaster.html</a></p>";
    echo "<p>🔗 Dashboard: <a href='broadcaster_dashboard.html' target='_blank'>broadcaster_dashboard.html</a> (Login required)</p>";
    
    echo "<h3>7. Next Steps</h3>";
    echo "<ul>";
    echo "<li>Visit <a href='broadcaster.html'>broadcaster.html</a> to test registration</li>";
    echo "<li>Register a new broadcaster account or login with test account</li>";
    echo "<li>Add radio stations through the dashboard</li>";
    echo "<li>Test station management features</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?>
