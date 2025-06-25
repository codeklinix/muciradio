<?php
// Update station logos to use local icon generator
try {
    $pdo = new PDO('mysql:host=localhost;dbname=muci_radio', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current URL base
    $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    
    // Updated station data with better logos
    $stationUpdates = [
        ['name' => 'BBC Radio 1', 'logo' => $baseUrl . '/generate_icon.php?text=BBC1&bg=FF6B35&size=60'],
        ['name' => 'Kiss FM', 'logo' => $baseUrl . '/generate_icon.php?text=KISS&bg=E91E63&size=60'], 
        ['name' => 'Capital FM', 'logo' => $baseUrl . '/generate_icon.php?text=CAP&bg=2196F3&size=60'],
        ['name' => 'Absolute Radio', 'logo' => $baseUrl . '/generate_icon.php?text=ABS&bg=795548&size=60'],
        ['name' => 'Planet Rock', 'logo' => $baseUrl . '/generate_icon.php?text=PR&bg=424242&size=60'],
        ['name' => 'Smooth Jazz', 'logo' => $baseUrl . '/generate_icon.php?text=SJZ&bg=8BC34A&size=60'],
        ['name' => 'Jazz FM', 'logo' => $baseUrl . '/generate_icon.php?text=JFM&bg=607D8B&size=60'],
        ['name' => 'Classic FM', 'logo' => $baseUrl . '/generate_icon.php?text=CFM&bg=673AB7&size=60'],
        ['name' => 'Classical KUSC', 'logo' => $baseUrl . '/generate_icon.php?text=KUSC&bg=3F51B5&size=60'],
        ['name' => 'BBC Radio 1Xtra', 'logo' => $baseUrl . '/generate_icon.php?text=1X&bg=FF5722&size=60'],
        ['name' => 'Radio FG', 'logo' => $baseUrl . '/generate_icon.php?text=FG&bg=00BCD4&size=60'],
        ['name' => 'BBC World Service', 'logo' => $baseUrl . '/generate_icon.php?text=BBC&bg=F44336&size=60'],
        ['name' => 'NPR News', 'logo' => $baseUrl . '/generate_icon.php?text=NPR&bg=795548&size=60'],
        ['name' => 'Talk Radio', 'logo' => $baseUrl . '/generate_icon.php?text=TR&bg=607D8B&size=60'],
        ['name' => 'LBC', 'logo' => $baseUrl . '/generate_icon.php?text=LBC&bg=9E9E9E&size=60'],
        ['name' => 'Country Hits Radio', 'logo' => $baseUrl . '/generate_icon.php?text=CHR&bg=8BC34A&size=60']
    ];
    
    echo "<h2>Updating Station Logos</h2>\n";
    echo "<p>Base URL: $baseUrl</p>\n";
    
    $updateStmt = $pdo->prepare("UPDATE stations SET logo_url = ? WHERE name = ?");
    
    foreach ($stationUpdates as $station) {
        $result = $updateStmt->execute([$station['logo'], $station['name']]);
        if ($result) {
            echo "<p>✅ Updated logo for: " . $station['name'] . "</p>\n";
            echo "<p>New URL: " . $station['logo'] . "</p>\n";
            echo "<img src='" . $station['logo'] . "' alt='" . $station['name'] . "' style='width:60px;height:60px;margin:10px;border:1px solid #ccc;'>\n";
        } else {
            echo "<p>❌ Failed to update: " . $station['name'] . "</p>\n";
        }
    }
    
    echo "<h3>Testing Icon Generator</h3>\n";
    echo "<p>Test icon: <img src='generate_icon.php?text=TEST&bg=FF6B35&size=60' style='width:60px;height:60px;border:1px solid #ccc;'></p>\n";
    
    echo "<h3>Sample Station Cards</h3>\n";
    $stmt = $pdo->query('SELECT name, logo_url, genre, country FROM stations LIMIT 3');
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($stations as $station) {
        echo "<div style='border:1px solid #ddd; padding:15px; margin:10px; border-radius:8px; display:inline-block; width:200px;'>";
        echo "<img src='" . $station['logo_url'] . "' alt='" . $station['name'] . "' style='width:60px;height:60px;border-radius:8px;'><br>";
        echo "<strong>" . $station['name'] . "</strong><br>";
        echo $station['genre'] . " • " . $station['country'];
        echo "</div>\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
}
</style>
