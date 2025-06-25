<?php
// Update stations with real online radio streams
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Updating Radio Stations with Real Online Streams</h1>";
echo "<hr>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Clear existing stations
    echo "<h3>Step 1: Clearing existing stations...</h3>";
    $conn->exec("DELETE FROM stations");
    echo "✅ Existing stations cleared<br>";
    
    // Real working online radio stations
    echo "<h3>Step 2: Adding real online radio stations...</h3>";
    $onlineStations = [
        // Pop/Mainstream
        ['BBC Radio 1', 'pop', 'UK', 'http://stream.live.vc.bbcmedia.co.uk/bbc_radio_one', 'https://via.placeholder.com/60x60/FF6B35/FFFFFF?text=BBC1'],
        ['Kiss FM', 'pop', 'UK', 'http://icecast.thisisdax.com/KissFMMP3', 'https://via.placeholder.com/60x60/E91E63/FFFFFF?text=KISS'],
        ['Capital FM', 'pop', 'UK', 'http://media-ice.musicradio.com/CapitalMP3', 'https://via.placeholder.com/60x60/2196F3/FFFFFF?text=CAP'],
        
        // Rock
        ['Absolute Radio', 'rock', 'UK', 'http://icecast.absoluteradio.co.uk/absoluteradiomp3', 'https://via.placeholder.com/60x60/795548/FFFFFF?text=ABS'],
        ['Planet Rock', 'rock', 'UK', 'http://tx.planetradio.co.uk/icecast.php?i=planetrock.mp3', 'https://via.placeholder.com/60x60/424242/FFFFFF?text=PR'],
        ['Classic Rock Radio', 'rock', 'USA', 'http://37.59.32.115:6166/stream', 'https://via.placeholder.com/60x60/9C27B0/FFFFFF?text=CRR'],
        
        // Jazz
        ['Smooth Jazz', 'jazz', 'USA', 'http://smoothjazz.cdnstream1.com:8114/', 'https://via.placeholder.com/60x60/8BC34A/FFFFFF?text=SJZ'],
        ['Jazz FM', 'jazz', 'UK', 'http://edge-bauerall-01-gos2.sharp-stream.com/jazz.mp3', 'https://via.placeholder.com/60x60/607D8B/FFFFFF?text=JFM'],
        ['Jazz Radio', 'jazz', 'France', 'http://jazzradio.ice.infomaniak.ch/jazzradio-high.mp3', 'https://via.placeholder.com/60x60/FF9800/FFFFFF?text=JR'],
        
        // Classical
        ['Classic FM', 'classical', 'UK', 'http://media-ice.musicradio.com/ClassicFMMP3', 'https://via.placeholder.com/60x60/673AB7/FFFFFF?text=CFM'],
        ['Classical KUSC', 'classical', 'USA', 'http://airstream.kusc.org:8080/', 'https://via.placeholder.com/60x60/3F51B5/FFFFFF?text=KUSC'],
        ['Radio Mozart', 'classical', 'France', 'http://radiomozart.ice.infomaniak.ch/radiomozart-128.mp3', 'https://via.placeholder.com/60x60/E1BEE7/FFFFFF?text=MOZ'],
        
        // Electronic/Dance
        ['BBC Radio 1Xtra', 'electronic', 'UK', 'http://stream.live.vc.bbcmedia.co.uk/bbc_1xtra', 'https://via.placeholder.com/60x60/FF5722/FFFFFF?text=1X'],
        ['Radio FG', 'electronic', 'France', 'http://radiofg.impek.com/fg', 'https://via.placeholder.com/60x60/00BCD4/FFFFFF?text=FG'],
        ['Dance UK Radio', 'electronic', 'UK', 'http://uk7.internet-radio.com:8226/stream', 'https://via.placeholder.com/60x60/4CAF50/FFFFFF?text=DUK'],
        
        // News
        ['BBC World Service', 'news', 'UK', 'http://stream.live.vc.bbcmedia.co.uk/bbc_world_service', 'https://via.placeholder.com/60x60/F44336/FFFFFF?text=BBC'],
        ['Sky News Radio', 'news', 'UK', 'http://radio.canstream.co.uk:8007/live.mp3', 'https://via.placeholder.com/60x60/009688/FFFFFF?text=SKY'],
        ['NPR News', 'news', 'USA', 'http://npr-ice.streamguys1.com/live.mp3', 'https://via.placeholder.com/60x60/795548/FFFFFF?text=NPR'],
        
        // Talk Radio
        ['Talk Radio', 'talk', 'UK', 'http://radio.talksport.com/stream', 'https://via.placeholder.com/60x60/607D8B/FFFFFF?text=TR'],
        ['LBC', 'talk', 'UK', 'http://media-ice.musicradio.com/LBCUKMP3', 'https://via.placeholder.com/60x60/9E9E9E/FFFFFF?text=LBC'],
        ['Talk Sport', 'talk', 'UK', 'http://radio.talksport.com/stream', 'https://via.placeholder.com/60x60/FF9800/FFFFFF?text=TS'],
        
        // Country
        ['Country Hits Radio', 'country', 'USA', 'http://uk7.internet-radio.com:8266/stream', 'https://via.placeholder.com/60x60/8BC34A/FFFFFF?text=CHR'],
        ['Nash FM', 'country', 'USA', 'http://playerservices.streamtheworld.com/api/livestream-redirect/NASHFM_WS.mp3', 'https://via.placeholder.com/60x60/795548/FFFFFF?text=NASH'],
        ['Big Country Radio', 'country', 'USA', 'http://uk2.internet-radio.com:8024/stream', 'https://via.placeholder.com/60x60/4CAF50/FFFFFF?text=BCR'],
        
        // International
        ['Radio France International', 'news', 'France', 'http://rfimonde64k.ice.infomaniak.ch/rfimonde-64.mp3', 'https://via.placeholder.com/60x60/2196F3/FFFFFF?text=RFI'],
        ['Deutsche Welle', 'news', 'Germany', 'http://dw-mp3-live.ais.livecdn.com:80/dw/dw-world-eng', 'https://via.placeholder.com/60x60/FF5722/FFFFFF?text=DW'],
        ['NHK World Radio', 'news', 'Japan', 'http://nhkworld.webcdn.stream.ne.jp/www11/radiojapan/all/263943/live.m3u8', 'https://via.placeholder.com/60x60/E91E63/FFFFFF?text=NHK']
    ];
    
    $stmt = $conn->prepare("INSERT INTO stations (name, genre, country, stream_url, logo_url) VALUES (?, ?, ?, ?, ?)");
    
    $successCount = 0;
    foreach ($onlineStations as $station) {
        try {
            $stmt->execute($station);
            $successCount++;
            echo "✅ Added: " . $station[0] . " (" . $station[1] . " - " . $station[2] . ")<br>";
        } catch (Exception $e) {
            echo "❌ Failed to add: " . $station[0] . " - " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>✅ Update Complete!</h3>";
    echo "<p><strong>Successfully added:</strong> $successCount online radio stations</p>";
    
    // Show stations by genre
    echo "<h3>📻 Stations by Genre:</h3>";
    $genreQuery = $conn->query("SELECT genre, COUNT(*) as count FROM stations WHERE is_active = 1 GROUP BY genre ORDER BY genre");
    while ($row = $genreQuery->fetch(PDO::FETCH_ASSOC)) {
        echo "<strong>" . ucfirst($row['genre']) . ":</strong> " . $row['count'] . " stations<br>";
    }
    
    echo "<h3>🎵 Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='index.html'>Test Main App</a></li>";
    echo "<li><a href='admin.html'>Admin Panel</a></li>";
    echo "<li><a href='api/stations.php'>View API Data</a></li>";
    echo "<li><a href='test_api.php'>Run API Tests</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
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
.success {
    color: green;
}
.error {
    color: red;
}
</style>
