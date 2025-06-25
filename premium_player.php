<?php
// Premium Embed Player for MuciRadio
require_once 'config/database.php';

// Get parameters
$stationId = isset($_GET['station']) ? $_GET['station'] : 'all';
$playerType = isset($_GET['type']) ? $_GET['type'] : 'elegant';
$theme = isset($_GET['theme']) ? $_GET['theme'] : 'default';
$width = isset($_GET['width']) ? (int)$_GET['width'] : 400;
$height = isset($_GET['height']) ? (int)$_GET['height'] : 150;
$autoplay = isset($_GET['autoplay']) ? $_GET['autoplay'] === '1' : false;
$showLogo = isset($_GET['logo']) ? $_GET['logo'] === '1' : true;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Load station data
$station = null;
$embedPlayerId = null;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($stationId !== 'all') {
        $stmt = $conn->prepare("SELECT * FROM stations WHERE id = ? AND is_active = 1");
        $stmt->execute([$stationId]);
        $station = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Track embed player usage if user_id provided
    if ($userId && $stationId !== 'all') {
        $stmt = $conn->prepare("
            SELECT id FROM embed_players 
            WHERE user_id = ? AND station_id = ? AND is_active = 1 
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$userId, $stationId]);
        $embedPlayer = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($embedPlayer) {
            $embedPlayerId = $embedPlayer['id'];
        }
    }
} catch (Exception $e) {
    error_log("Database error in premium_player.php: " . $e->getMessage());
}

// Define theme colors
$themes = [
    'default' => ['primary' => '#FF6B35', 'secondary' => '#F7931E', 'text' => '#ffffff', 'bg' => 'linear-gradient(135deg, #FF6B35, #F7931E)'],
    'dark' => ['primary' => '#2d3748', 'secondary' => '#4a5568', 'text' => '#ffffff', 'bg' => 'linear-gradient(135deg, #2d3748, #4a5568)'],
    'light' => ['primary' => '#f7fafc', 'secondary' => '#edf2f7', 'text' => '#2d3748', 'bg' => 'linear-gradient(135deg, #f7fafc, #edf2f7)'],
    'minimal' => ['primary' => '#ffffff', 'secondary' => '#f8f9fa', 'text' => '#4a5568', 'bg' => '#ffffff'],
    'elegant' => ['primary' => '#667eea', 'secondary' => '#764ba2', 'text' => '#ffffff', 'bg' => 'linear-gradient(135deg, #667eea, #764ba2)']
];

$currentTheme = $themes[$theme] ?? $themes['default'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $station ? htmlspecialchars($station['name']) : 'MuciRadio'; ?> - Premium Player</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: <?php echo $currentTheme['bg']; ?>;
            color: <?php echo $currentTheme['text']; ?>;
            width: <?php echo $width; ?>px;
            height: <?php echo $height; ?>px;
            overflow: hidden;
            position: relative;
        }

        .player-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: <?php echo $playerType === 'popup' ? '15px' : '10px'; ?>;
            position: relative;
        }

        /* Premium animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .premium-header {
            display: <?php echo !$showLogo || $playerType === 'sticky' ? 'none' : 'flex'; ?>;
            align-items: center;
            margin-bottom: 12px;
            animation: slideIn 0.5s ease-out;
        }

        .premium-logo {
            width: <?php echo $playerType === 'popup' ? '40px' : '35px'; ?>;
            height: <?php echo $playerType === 'popup' ? '40px' : '35px'; ?>;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .premium-logo:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.25);
        }

        .station-info {
            flex: 1;
            min-width: 0;
        }

        .station-name {
            font-size: <?php echo $playerType === 'popup' ? '1.1rem' : '1rem'; ?>;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .station-genre {
            font-size: 0.85rem;
            opacity: 0.9;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .premium-controls {
            display: flex;
            align-items: center;
            gap: <?php echo $playerType === 'sticky' ? '8px' : '12px'; ?>;
            flex: 1;
        }

        .premium-play-btn {
            width: <?php echo $playerType === 'popup' ? '60px' : ($playerType === 'sticky' ? '45px' : '55px'); ?>;
            height: <?php echo $playerType === 'popup' ? '60px' : ($playerType === 'sticky' ? '45px' : '55px'); ?>;
            border: none;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
            color: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: <?php echo $playerType === 'popup' ? '24px' : ($playerType === 'sticky' ? '18px' : '22px'); ?>;
            transition: all 0.3s ease;
            flex-shrink: 0;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .premium-play-btn:hover {
            transform: scale(1.1);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.2));
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .premium-play-btn.playing {
            animation: pulse 2s infinite;
        }

        .premium-play-btn.loading i {
            animation: rotate 1s linear infinite;
        }

        .volume-section {
            display: <?php echo $playerType === 'sticky' ? 'none' : 'flex'; ?>;
            align-items: center;
            gap: 8px;
            flex: 1;
            max-width: <?php echo $playerType === 'popup' ? '200px' : '150px'; ?>;
        }

        .volume-icon {
            font-size: 16px;
            opacity: 0.8;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }

        .volume-icon:hover {
            opacity: 1;
        }

        .premium-volume-slider {
            flex: 1;
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            outline: none;
            cursor: pointer;
            -webkit-appearance: none;
            backdrop-filter: blur(5px);
        }

        .premium-volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 16px;
            height: 16px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .premium-volume-slider::-webkit-slider-thumb:hover {
            background: #ffffff;
            transform: scale(1.2);
        }

        .visualizer {
            display: <?php echo $playerType === 'sticky' ? 'none' : 'flex'; ?>;
            align-items: center;
            gap: 2px;
            margin-left: 10px;
        }

        .bar {
            width: 3px;
            height: 20px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            animation: visualizer 1.5s ease-in-out infinite;
        }

        .bar:nth-child(2) { animation-delay: 0.1s; }
        .bar:nth-child(3) { animation-delay: 0.2s; }
        .bar:nth-child(4) { animation-delay: 0.3s; }
        .bar:nth-child(5) { animation-delay: 0.4s; }

        @keyframes visualizer {
            0%, 100% { height: 10px; opacity: 0.3; }
            50% { height: 25px; opacity: 1; }
        }

        .premium-station-list {
            display: <?php echo $station || $playerType !== 'popup' ? 'none' : 'block'; ?>;
            max-height: <?php echo $height - 100; ?>px;
            overflow-y: auto;
            margin-top: 12px;
            animation: slideIn 0.7s ease-out;
        }

        .premium-station-item {
            display: flex;
            align-items: center;
            padding: 10px;
            cursor: pointer;
            border-radius: 8px;
            margin-bottom: 6px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .premium-station-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .premium-station-item.active {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .premium-station-logo {
            width: 35px;
            height: 35px;
            border-radius: 6px;
            margin-right: 12px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .loading-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 0.9rem;
            animation: slideIn 0.5s ease-out;
        }

        .premium-spinner {
            width: 30px;
            height: 30px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid currentColor;
            border-radius: 50%;
            animation: rotate 1s linear infinite;
            margin-right: 12px;
        }

        .error-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Popup specific styles */
        .popup-close {
            display: <?php echo $playerType === 'popup' ? 'block' : 'none'; ?>;
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .popup-close:hover {
            background: rgba(0,0,0,0.7);
            transform: scale(1.1);
        }

        /* Sticky specific styles */
        .sticky-drag-handle {
            display: <?php echo $playerType === 'sticky' ? 'block' : 'none'; ?>;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            cursor: move;
            background: rgba(255, 255, 255, 0.1);
        }

        /* Scrollbar styling */
        .premium-station-list::-webkit-scrollbar {
            width: 6px;
        }

        .premium-station-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .premium-station-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .premium-station-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Responsive adjustments */
        @media (max-width: 350px) {
            .station-name { font-size: 0.9rem; }
            .station-genre { font-size: 0.75rem; }
            .premium-play-btn { width: 45px; height: 45px; font-size: 18px; }
        }
    </style>
</head>
<body>
    <?php if ($playerType === 'popup'): ?>
    <button class="popup-close" onclick="window.close()">&times;</button>
    <?php endif; ?>
    
    <?php if ($playerType === 'sticky'): ?>
    <div class="sticky-drag-handle"></div>
    <?php endif; ?>

    <div class="player-container">
        <?php if ($showLogo && $playerType !== 'sticky'): ?>
        <div class="premium-header">
            <div class="premium-logo">
                <i class="fas fa-radio"></i>
            </div>
            <div class="station-info">
                <div class="station-name" id="currentStationName">
                    <?php echo $station ? htmlspecialchars($station['name']) : 'MuciRadio Premium'; ?>
                </div>
                <div class="station-genre" id="currentStationGenre">
                    <?php echo $station ? htmlspecialchars($station['genre']) : 'Select a station'; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="premium-controls">
            <button class="premium-play-btn" id="premiumPlayBtn">
                <i class="fas fa-play"></i>
            </button>
            
            <?php if ($playerType !== 'sticky'): ?>
            <div class="volume-section">
                <i class="fas fa-volume-down volume-icon" id="volumeIcon"></i>
                <input type="range" class="premium-volume-slider" id="premiumVolumeSlider" min="0" max="100" value="50">
                <i class="fas fa-volume-up volume-icon"></i>
            </div>
            
            <div class="visualizer" id="visualizer" style="display: none;">
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
                <div class="bar"></div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$station && $playerType === 'popup'): ?>
        <div class="premium-station-list" id="premiumStationList">
            <div class="loading-container">
                <div class="premium-spinner"></div>
                Loading premium stations...
            </div>
        </div>
        <?php endif; ?>

        <audio id="premiumAudioPlayer" preload="none"></audio>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const playBtn = document.getElementById('premiumPlayBtn');
            const volumeSlider = document.getElementById('premiumVolumeSlider');
            const volumeIcon = document.getElementById('volumeIcon');
            const audioPlayer = document.getElementById('premiumAudioPlayer');
            const stationList = document.getElementById('premiumStationList');
            const currentStationName = document.getElementById('currentStationName');
            const currentStationGenre = document.getElementById('currentStationGenre');
            const visualizer = document.getElementById('visualizer');

            let currentStation = <?php echo $station ? json_encode($station) : 'null'; ?>;
            let stations = [];
            let isPlaying = false;
            let embedPlayerId = <?php echo $embedPlayerId ?: 'null'; ?>;

            // Initialize
            <?php if (!$station): ?>
            loadStations();
            <?php endif; ?>

            // Set initial volume
            audioPlayer.volume = 0.5;

            // Play button event
            playBtn.addEventListener('click', function() {
                if (!currentStation) {
                    if (stations.length > 0) {
                        selectStation(stations[0]);
                    }
                    return;
                }

                if (isPlaying) {
                    audioPlayer.pause();
                } else {
                    playStation();
                }
            });

            // Volume control
            <?php if ($playerType !== 'sticky'): ?>
            if (volumeSlider) {
                volumeSlider.addEventListener('input', function() {
                    const volume = volumeSlider.value / 100;
                    audioPlayer.volume = volume;
                    updateVolumeIcon(volume);
                });

                volumeIcon.addEventListener('click', function() {
                    if (audioPlayer.volume > 0) {
                        audioPlayer.volume = 0;
                        volumeSlider.value = 0;
                    } else {
                        audioPlayer.volume = 0.5;
                        volumeSlider.value = 50;
                    }
                    updateVolumeIcon(audioPlayer.volume);
                });
            }
            <?php endif; ?>

            // Audio events
            audioPlayer.addEventListener('loadstart', function() {
                playBtn.innerHTML = '<i class="fas fa-spinner"></i>';
                playBtn.classList.add('loading');
            });

            audioPlayer.addEventListener('canplay', function() {
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                playBtn.classList.remove('loading');
                playBtn.classList.add('playing');
                isPlaying = true;
                showVisualizer();
                trackUsage();
            });

            audioPlayer.addEventListener('playing', function() {
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                playBtn.classList.remove('loading');
                playBtn.classList.add('playing');
                isPlaying = true;
                showVisualizer();
            });

            audioPlayer.addEventListener('pause', function() {
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                playBtn.classList.remove('playing');
                isPlaying = false;
                hideVisualizer();
            });

            audioPlayer.addEventListener('error', function(e) {
                playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                playBtn.classList.remove('loading', 'playing');
                isPlaying = false;
                hideVisualizer();
                console.error('Audio error:', e);
            });

            // Functions
            async function loadStations() {
                try {
                    const response = await fetch('api/stations.php');
                    const result = await response.json();
                    
                    if (result.success && result.data.length > 0) {
                        stations = result.data;
                        displayStations();
                        
                        <?php if ($autoplay): ?>
                        selectStation(stations[0]);
                        <?php endif; ?>
                    } else {
                        if (stationList) {
                            stationList.innerHTML = '<div class="error-container">No premium stations available</div>';
                        }
                    }
                } catch (error) {
                    console.error('Error loading stations:', error);
                    if (stationList) {
                        stationList.innerHTML = '<div class="error-container">Error loading stations</div>';
                    }
                }
            }

            function displayStations() {
                if (!stationList) return;

                stationList.innerHTML = '';
                stations.forEach(station => {
                    const stationItem = document.createElement('div');
                    stationItem.className = 'premium-station-item';
                    stationItem.innerHTML = `
                        <img src="${station.logo_url || 'https://via.placeholder.com/35x35/' + encodeURIComponent('<?php echo str_replace('#', '', $currentTheme['primary']); ?>') + '/FFFFFF?text=' + station.name.charAt(0)}" 
                             alt="${station.name}" class="premium-station-logo">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;">${station.name}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">${station.genre}</div>
                        </div>
                    `;
                    
                    stationItem.addEventListener('click', () => selectStation(station));
                    stationList.appendChild(stationItem);
                });
            }

            function selectStation(station) {
                currentStation = station;
                
                // Update UI
                if (currentStationName) {
                    currentStationName.textContent = station.name;
                }
                if (currentStationGenre) {
                    currentStationGenre.textContent = station.genre;
                }
                
                // Update active station in list
                document.querySelectorAll('.premium-station-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                const stationItems = document.querySelectorAll('.premium-station-item');
                const stationIndex = stations.findIndex(s => s.id === station.id);
                if (stationItems[stationIndex]) {
                    stationItems[stationIndex].classList.add('active');
                }
                
                playStation();
            }

            function playStation() {
                if (!currentStation) return;

                audioPlayer.src = currentStation.stream_url;
                audioPlayer.load();
                audioPlayer.play().catch(error => {
                    console.error('Play failed:', error);
                    playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                    playBtn.classList.remove('loading', 'playing');
                });
            }

            function updateVolumeIcon(volume) {
                if (volumeIcon) {
                    if (volume === 0) {
                        volumeIcon.className = 'fas fa-volume-mute volume-icon';
                    } else if (volume < 0.5) {
                        volumeIcon.className = 'fas fa-volume-down volume-icon';
                    } else {
                        volumeIcon.className = 'fas fa-volume-up volume-icon';
                    }
                }
            }

            function showVisualizer() {
                if (visualizer) {
                    visualizer.style.display = 'flex';
                }
            }

            function hideVisualizer() {
                if (visualizer) {
                    visualizer.style.display = 'none';
                }
            }

            function trackUsage() {
                if (embedPlayerId) {
                    fetch('api/premium_embeds.php/track-play', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            embed_player_id: embedPlayerId
                        })
                    }).catch(error => {
                        console.log('Analytics tracking failed:', error);
                    });
                }
            }

            // Auto-play if single station and autoplay enabled
            <?php if ($station && $autoplay): ?>
            setTimeout(() => {
                playStation();
            }, 1000);
            <?php endif; ?>

            // Sticky player drag functionality
            <?php if ($playerType === 'sticky'): ?>
            let isDragging = false;
            let dragOffset = { x: 0, y: 0 };

            const dragHandle = document.querySelector('.sticky-drag-handle');
            if (dragHandle) {
                dragHandle.addEventListener('mousedown', function(e) {
                    isDragging = true;
                    const rect = document.body.getBoundingClientRect();
                    dragOffset.x = e.clientX - rect.left;
                    dragOffset.y = e.clientY - rect.top;
                });

                document.addEventListener('mousemove', function(e) {
                    if (isDragging && window.parent) {
                        const container = window.parent.document.getElementById('muci-sticky-player');
                        if (container) {
                            container.style.left = (e.clientX - dragOffset.x) + 'px';
                            container.style.top = (e.clientY - dragOffset.y) + 'px';
                        }
                    }
                });

                document.addEventListener('mouseup', function() {
                    isDragging = false;
                });
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>
