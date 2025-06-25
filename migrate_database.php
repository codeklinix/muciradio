<?php
// Database migration script to add missing columns and update structure
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'muci_radio';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Migration Script</h2>";
    
    // 1. Check and add email column to users table
    echo "<h3>1. Checking users table structure...</h3>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('email', $columns)) {
        echo "<p>Adding email column...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE AFTER username");
        echo "<p>✅ Email column added</p>";
    } else {
        echo "<p>✅ Email column already exists</p>";
    }
    
    // 2. Update role enum to include radio_owner
    echo "<h3>2. Updating role enum...</h3>";
    
    $roleInfo = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    
    if ($roleInfo && !strpos($roleInfo['Type'], 'radio_owner')) {
        echo "<p>Updating role enum to include radio_owner...</p>";
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user', 'radio_owner') DEFAULT 'user'");
        echo "<p>✅ Role enum updated</p>";
    } else {
        echo "<p>✅ Role enum already includes radio_owner</p>";
    }
    
    // 3. Check and add user_id column to stations table
    echo "<h3>3. Checking stations table structure...</h3>";
    
    $stationColumns = $pdo->query("SHOW COLUMNS FROM stations")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('user_id', $stationColumns)) {
        echo "<p>Adding user_id column to stations table...</p>";
        $pdo->exec("ALTER TABLE stations ADD COLUMN user_id INT NULL AFTER logo_url");
        echo "<p>Adding foreign key constraint...</p>";
        $pdo->exec("ALTER TABLE stations ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "<p>✅ User_id column and foreign key added</p>";
    } else {
        echo "<p>✅ User_id column already exists</p>";
    }
    
    // 4. Update existing admin user to have an email
    echo "<h3>4. Updating admin user...</h3>";
    
    $adminUser = $pdo->query("SELECT id, email FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser && empty($adminUser['email'])) {
        echo "<p>Adding email to admin user...</p>";
        $pdo->exec("UPDATE users SET email = 'admin@muciradio.com' WHERE username = 'admin'");
        echo "<p>✅ Admin email updated</p>";
    } else {
        echo "<p>✅ Admin user already has email</p>";
    }
    
    // 5. Create test broadcaster account if it doesn't exist
    echo "<h3>5. Creating test broadcaster account...</h3>";
    
    $testUser = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $testUser->execute(['testbroadcaster', 'test@broadcaster.com']);
    
    if (!$testUser->fetch()) {
        $testPassword = password_hash('testpass123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'radio_owner')");
        $stmt->execute(['testbroadcaster', 'test@broadcaster.com', $testPassword]);
        echo "<p>✅ Test broadcaster account created</p>";
        echo "<p>Username: testbroadcaster</p>";
        echo "<p>Email: test@broadcaster.com</p>";
        echo "<p>Password: testpass123</p>";
    } else {
        echo "<p>✅ Test broadcaster account already exists</p>";
    }
    
    // 6. Show current table structure
    echo "<h3>6. Current Table Structures</h3>";
    
    echo "<h4>Users Table:</h4>";
    $usersStructure = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($usersStructure as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h4>Stations Table:</h4>";
    $stationsStructure = $pdo->query("DESCRIBE stations")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($stationsStructure as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Migration completed successfully!</h3>";
    echo "<p>You can now test the broadcaster functionality at: <a href='broadcaster.html'>broadcaster.html</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Migration failed: " . $e->getMessage() . "</h3>";
}
?>
