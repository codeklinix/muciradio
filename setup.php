<?php
// Database setup script for MuciRadio
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'muci_radio';

echo "<h1>MuciRadio Database Setup</h1>";
echo "<hr>";

try {
    // Step 1: Connect to MySQL without selecting database
    echo "<h3>Step 1: Connecting to MySQL...</h3>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL successfully<br>";

    // Step 2: Create database
    echo "<h3>Step 2: Creating database...</h3>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "✅ Database '$dbname' created successfully<br>";

    // Step 3: Select database
    $pdo->exec("USE $dbname");
    echo "✅ Database selected<br>";

    // Step 4: Create stations table
    echo "<h3>Step 3: Creating tables...</h3>";
    $createStationsTable = "
        CREATE TABLE IF NOT EXISTS stations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            genre VARCHAR(100) NOT NULL,
            country VARCHAR(100) NOT NULL,
            stream_url TEXT NOT NULL,
            logo_url TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createStationsTable);
    echo "✅ Stations table created<br>";

    // Create users table
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createUsersTable);
    echo "✅ Users table created<br>";

    // Step 5: Insert admin user
    echo "<h3>Step 4: Creating admin user...</h3>";
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
    if ($checkAdmin == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$hashedPassword', 'admin')");
        echo "✅ Admin user created (username: admin, password: admin123)<br>";
    } else {
        echo "ℹ️ Admin user already exists<br>";
    }

    // Step 6: Insert sample stations
    echo "<h3>Step 5: Adding sample stations...</h3>";
    $checkStations = $pdo->query("SELECT COUNT(*) FROM stations")->fetchColumn();
    if ($checkStations == 0) {
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
        $sampleStations = [
            // Popular Stations
            ['BBC Radio 1', 'pop', 'UK', 'https://stream.live.vc.bbcmedia.co.uk/bbc_radio_one', $baseUrl . '/generate_icon.php?text=BBC1&bg=FF6B35&size=60'],
            ['Kiss FM', 'pop', 'UK', 'https://icecast.thisisdax.com/KissFMMP3', $baseUrl . '/generate_icon.php?text=KISS&bg=E91E63&size=60'],
            ['Capital FM', 'pop', 'UK', 'https://media-ice.musicradio.com/CapitalMP3', $baseUrl . '/generate_icon.php?text=CAP&bg=2196F3&size=60'],
            
            // Rock Stations
            ['Absolute Radio', 'rock', 'UK', 'https://icecast.absoluteradio.co.uk/absoluteradiomp3', $baseUrl . '/generate_icon.php?text=ABS&bg=795548&size=60'],
            ['Planet Rock', 'rock', 'UK', 'https://tx.planetradio.co.uk/icecast.php?i=planetrock.mp3', $baseUrl . '/generate_icon.php?text=PR&bg=424242&size=60'],
            
            // Jazz Stations
            ['Smooth Jazz', 'jazz', 'USA', 'https://ice1.somafm.com/groovesalad-128-mp3', $baseUrl . '/generate_icon.php?text=SJZ&bg=8BC34A&size=60'],
            ['Jazz FM', 'jazz', 'UK', 'https://edge-bauerall-01-gos2.sharp-stream.com/jazz.mp3', $baseUrl . '/generate_icon.php?text=JFM&bg=607D8B&size=60'],
            
            // Classical
            ['Classic FM', 'classical', 'UK', 'https://media-ice.musicradio.com/ClassicFMMP3', $baseUrl . '/generate_icon.php?text=CFM&bg=673AB7&size=60'],
            ['Classical KUSC', 'classical', 'USA', 'https://airstream.kusc.org:8080/', $baseUrl . '/generate_icon.php?text=KUSC&bg=3F51B5&size=60'],
            
            // Electronic
            ['BBC Radio 1Xtra', 'electronic', 'UK', 'https://stream.live.vc.bbcmedia.co.uk/bbc_1xtra', $baseUrl . '/generate_icon.php?text=1X&bg=FF5722&size=60'],
            ['Radio FG', 'electronic', 'France', 'https://radiofg.impek.com/fg', $baseUrl . '/generate_icon.php?text=FG&bg=00BCD4&size=60'],
            
            // News
            ['BBC World Service', 'news', 'UK', 'https://stream.live.vc.bbcmedia.co.uk/bbc_world_service', $baseUrl . '/generate_icon.php?text=BBC&bg=F44336&size=60'],
            ['NPR News', 'news', 'USA', 'https://npr-ice.streamguys1.com/live.mp3', $baseUrl . '/generate_icon.php?text=NPR&bg=795548&size=60'],
            
            // Talk Radio
            ['Talk Radio', 'talk', 'UK', 'https://radio.talksport.com/stream', $baseUrl . '/generate_icon.php?text=TR&bg=607D8B&size=60'],
            ['LBC', 'talk', 'UK', 'https://media-ice.musicradio.com/LBCUKMP3', $baseUrl . '/generate_icon.php?text=LBC&bg=9E9E9E&size=60'],
            
            // Country
            ['Country Hits Radio', 'country', 'USA', 'https://uk7.internet-radio.com:8266/stream', $baseUrl . '/generate_icon.php?text=CHR&bg=8BC34A&size=60']
        ];

        $stmt = $pdo->prepare("INSERT INTO stations (name, genre, country, stream_url, logo_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($sampleStations as $station) {
            $stmt->execute($station);
        }
        echo "✅ " . count($sampleStations) . " sample stations added<br>";
    } else {
        echo "ℹ️ Stations already exist (" . $checkStations . " stations found)<br>";
    }

    // Step 7: Test API endpoint
    echo "<h3>Step 6: Testing API endpoints...</h3>";
    $apiUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/stations.php';
    echo "API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a><br>";

    // Test if API is accessible
    $headers = @get_headers($apiUrl);
    if ($headers) {
        echo "✅ API endpoint is accessible<br>";
    } else {
        echo "❌ API endpoint may not be accessible<br>";
    }

    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Admin Login:</strong> admin / admin123</p>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li><a href='index.html'>Visit Main App</a></li>";
    echo "<li><a href='admin.html'>Visit Admin Panel</a></li>";
    echo "<li><a href='api/stations.php'>Test API</a></li>";
    echo "</ul>";

} catch(PDOException $e) {
    echo "<h3>❌ Error during setup:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<h4>Troubleshooting:</h4>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL service is running</li>";
    echo "<li>Check if MySQL is accessible on localhost:3306</li>";
    echo "<li>Verify MySQL root user has no password (default XAMPP setup)</li>";
    echo "</ul>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    line-height: 1.6;
}
h1, h3 {
    color: #FF6B35;
}
</style>
