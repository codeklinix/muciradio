<?php
// Check station logos in database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=muci_radio', 'root', '');
    $stmt = $pdo->query('SELECT name, logo_url FROM stations LIMIT 5');
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Station Logos:</h3>\n";
    foreach($stations as $station) {
        echo "<p><strong>" . $station['name'] . ":</strong><br>";
        echo "URL: " . $station['logo_url'] . "<br>";
        echo "<img src='" . $station['logo_url'] . "' alt='" . $station['name'] . "' style='width:60px;height:60px;border:1px solid #ccc;'></p>\n";
    }
} catch(Exception $e) {
    echo 'Database Error: ' . $e->getMessage();
}
?>
