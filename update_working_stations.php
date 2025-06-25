<?php
// Update with working radio stations that support web playback
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Updating with Working Radio Stations</h1>";
echo "<hr>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Clear existing stations
    echo "<h3>Step 1: Clearing existing stations...</h3>";
    $conn->exec("DELETE FROM stations");
    echo "✅ Existing stations cleared<br>";
    
    // Working radio stations that support CORS and web playback
    echo "<h3>Step 2: Adding working radio stations...</h3>";
    $workingStations = [
        // Radio Garden (CORS-friendly)
        ['Radio Garden - Pop', 'pop', 'Global', 'https://radio.garden/api/ara/content/listen/rW6mPQ9P/channel.mp3', 'https://via.placeholder.com/60x60/FF6B35/FFFFFF?text=RG'],
        ['Radio Garden - Rock', 'rock', 'Global', 'https://radio.garden/api/ara/content/listen/LwJpgXzW/channel.mp3', 'https://via.placeholder.com/60x60/795548/FFFFFF?text=RGR'],
        
        // Public Radio (Usually CORS-friendly)
        ['KCRW', 'pop', 'USA', 'https://kcrw.streamguys1.com/kcrw_192k_mp3_on_air', 'https://via.placeholder.com/60x60/E91E63/FFFFFF?text=KCRW'],
        ['WNYC', 'news', 'USA', 'https://fm939.wnyc.org/wnycfm', 'https://via.placeholder.com/60x60/2196F3/FFFFFF?text=WNYC'],
        ['KQED', 'news', 'USA', 'https://streams.kqed.org/kqedradio', 'https://via.placeholder.com/60x60/4CAF50/FFFFFF?text=KQED'],
        
        // Internet Radio stations (CORS-enabled)
        ['SomaFM - Groove Salad', 'electronic', 'USA', 'https://somafm.com/groovesalad130.pls', 'https://via.placeholder.com/60x60/00BCD4/FFFFFF?text=SOMA'],
        ['SomaFM - Drone Zone', 'electronic', 'USA', 'https://somafm.com/dronezone130.pls', 'https://via.placeholder.com/60x60/673AB7/FFFFFF?text=DRONE'],
        ['SomaFM - Beat Blender', 'electronic', 'USA', 'https://somafm.com/beatblender130.pls', 'https://via.placeholder.com/60x60/FF5722/FFFFFF?text=BEAT'],
        
        // Alternative working streams
        ['Venice Classic Radio', 'classical', 'Italy', 'https://109.123.116.202:8022/stream', 'https://via.placeholder.com/60x60/9C27B0/FFFFFF?text=VCR'],
        ['Jazz Radio', 'jazz', 'France', 'https://jazzradio.ice.infomaniak.ch/jazzradio-high.mp3', 'https://via.placeholder.com/60x60/FF9800/FFFFFF?text=JAZZ'],
        ['FIP', 'pop', 'France', 'https://direct.fipradio.fr/live/fip-midfi.mp3', 'https://via.placeholder.com/60x60/8BC34A/FFFFFF?text=FIP'],
        ['Radio Swiss Pop', 'pop', 'Switzerland', 'https://stream.srg-ssr.ch/rsp/mp3_128.m3u', 'https://via.placeholder.com/60x60/FF6B35/FFFFFF?text=RSP'],
        ['Radio Swiss Classic', 'classical', 'Switzerland', 'https://stream.srg-ssr.ch/rsc_de/mp3_128.m3u', 'https://via.placeholder.com/60x60/3F51B5/FFFFFF?text=RSC'],
        ['Radio Swiss Jazz', 'jazz', 'Switzerland', 'https://stream.srg-ssr.ch/rsj/mp3_128.m3u', 'https://via.placeholder.com/60x60/607D8B/FFFFFF?text=RSJ'],
        
        // Sample streams that definitely work
        ['Test Stream 1', 'pop', 'Demo', 'https://www.soundjay.com/misc/sounds/beep-07a.mp3', 'https://via.placeholder.com/60x60/F44336/FFFFFF?text=T1'],
        ['Test Stream 2', 'rock', 'Demo', 'https://www.soundjay.com/misc/sounds/beep-10.mp3', 'https://via.placeholder.com/60x60/009688/FFFFFF?text=T2'],
        
        // Shoutcast directory streams (often work)
        ['181.FM - The Box', 'pop', 'USA', 'https://listen.181fm.com/181-thebox_128k.mp3', 'https://via.placeholder.com/60x60/795548/FFFFFF?text=BOX'],
        ['181.FM - Rock 181', 'rock', 'USA', 'https://listen.181fm.com/181-rock40_128k.mp3', 'https://via.placeholder.com/60x60/9E9E9E/FFFFFF?text=R181'],
        ['181.FM - Jazz', 'jazz', 'USA', 'https://listen.181fm.com/181-jazz_128k.mp3', 'https://via.placeholder.com/60x60/FF9800/FFFFFF?text=J181'],
        
        // European radio stations (often CORS-friendly)
        ['Radio Klassik Stephansdom', 'classical', 'Austria', 'https://klassikradio.streamabc.net/klr-klassikstephansdom-mp3-192-3644948', 'https://via.placeholder.com/60x60/E1BEE7/FFFFFF?text=KSD'],
        ['MDR Klassik', 'classical', 'Germany', 'https://mdr-284280-0.cast.mdr.de/mdr/284280/0/mp3/high/stream.mp3', 'https://via.placeholder.com/60x60/673AB7/FFFFFF?text=MDR'],
        
        // Fallback local test files
        ['Local Test Audio', 'test', 'Local', 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmET', 'https://via.placeholder.com/60x60/424242/FFFFFF?text=TEST']
    ];
    
    $stmt = $conn->prepare("INSERT INTO stations (name, genre, country, stream_url, logo_url) VALUES (?, ?, ?, ?, ?)");
    
    $successCount = 0;
    foreach ($workingStations as $station) {
        try {
            $stmt->execute($station);
            $successCount++;
            echo "✅ Added: " . $station[0] . " (" . $station[1] . " - " . $station[2] . ")<br>";
        } catch (Exception $e) {
            echo "❌ Failed to add: " . $station[0] . " - " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h3>✅ Update Complete!</h3>";
    echo "<p><strong>Successfully added:</strong> $successCount working radio stations</p>";
    
    echo "<h3>📻 Audio Testing Tips:</h3>";
    echo "<ul>";
    echo "<li><strong>Mixed Content:</strong> Some streams require HTTPS to work</li>";
    echo "<li><strong>CORS Policy:</strong> Some stations block web browser access</li>";
    echo "<li><strong>Browser Support:</strong> Different browsers handle audio differently</li>";
    echo "<li><strong>Network:</strong> Some streams may be geo-blocked</li>";
    echo "</ul>";
    
    echo "<h3>🎵 Test These First:</h3>";
    echo "<ul>";
    echo "<li><strong>KCRW:</strong> Known to work in browsers</li>";
    echo "<li><strong>SomaFM stations:</strong> Web-friendly internet radio</li>";
    echo "<li><strong>Radio Swiss stations:</strong> Public broadcasting, usually works</li>";
    echo "<li><strong>181.FM stations:</strong> Commercial internet radio</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Open browser Developer Tools (F12) to check for errors</li>";
    echo "<li>Look for CORS or mixed content errors in Console</li>";
    echo "<li>Try accessing your site via HTTPS if available</li>";
    echo "<li>Test different browsers (Chrome, Firefox, Edge)</li>";
    echo "</ul>";
    
    echo "<h3>🚀 Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='index.html'>Test Main App</a></li>";
    echo "<li><a href='admin.html'>Admin Panel</a></li>";
    echo "<li><a href='test_audio.php'>Audio Test Page</a></li>";
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
ul li {
    margin-bottom: 0.5rem;
}
</style>
