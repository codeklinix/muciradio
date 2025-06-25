<?php
// Playlist Player for MuciRadio Embeds
$stations = isset($_GET['stations']) ? explode(',', $_GET['stations']) : [];
$theme = isset($_GET['theme']) ? $_GET['theme'] : 'orange';
$layout = isset($_GET['layout']) ? $_GET['layout'] : 'vertical';
$autoplay = isset($_GET['autoplay']) ? $_GET['autoplay'] === 'true' : false;
$shuffle = isset($_GET['shuffle']) ? $_GET['shuffle'] === 'true' : false;

// Get URL parameters
$stationId = isset($_GET['station']) ? $_GET['station'] : 'all';
$playerType = isset($_GET['type']) ? $_GET['type'] : 'full';
$theme = isset($_GET['theme']) ? $_GET['theme'] : 'default';
$width = isset($_GET['width']) ? (int)$_GET['width'] : 400;
$height = isset($_GET['height']) ? (int)$_GET['height'] : 150;
$autoplay = isset($_GET['autoplay']) ? $_GET['autoplay'] === '1' : false;
$showLogo = isset($_GET['logo']) ? $_GET['logo'] === '1' : true;

// Load station data if specific station is requested
$station = null;
if ($stationId !== 'all') {
    try {
        require_once 'config/database.php';
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM stations WHERE id = ? AND is_active = 1");
        $stmt->execute([$stationId]);
        $station = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Database error in player.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MuciRadio Player</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            <?php if ($theme === 'dark'): ?>
                background: linear-gradient(135deg, #2d3748, #4a5568);
                color: #ffffff;
            <?php elseif ($theme === 'light'): ?>
                background: linear-gradient(135deg, #f7fafc, #edf2f7);
                color: #2d3748;
            <?php elseif ($theme === 'minimal'): ?>
                background: #ffffff;
                color: #4a5568;
            <?php else: ?>
                background: linear-gradient(135deg, #FF6B35, #F7931E);
                color: #ffffff;
            <?php endif; ?>
            width: <?php echo $width; ?>px;
            height: <?php echo $height; ?>px;
            overflow: hidden;
        }

        .player-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: <?php echo $playerType === 'button' ? '5px' : '10px'; ?>;
        }

        .player-header {
            display: <?php echo !$showLogo || $playerType === 'button' ? 'none' : 'flex'; ?>;
            align-items: center;
            margin-bottom: 10px;
        }

        .logo {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .station-info {
            flex: 1;
            min-width: 0;
        }

        .station-name {
            font-size: <?php echo $playerType === 'mini' ? '0.9rem' : '1rem'; ?>;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .station-genre {
            font-size: 0.8rem;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-controls {
            display: flex;
            align-items: center;
            gap: <?php echo $playerType === 'button' ? '5px' : '10px'; ?>;
            flex: 1;
        }

        .play-btn {
            width: <?php echo $playerType === 'button' ? '40px' : '50px'; ?>;
            height: <?php echo $playerType === 'button' ? '40px' : '50px'; ?>;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: <?php echo $playerType === 'button' ? '16px' : '20px'; ?>;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .play-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .volume-control {
            display: <?php echo $playerType === 'button' ? 'none' : 'flex'; ?>;
            align-items: center;
            gap: 5px;
            flex: 1;
            max-width: 150px;
        }

        .volume-slider {
            flex: 1;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2px;
            outline: none;
            cursor: pointer;
        }

        .station-list {
            display: <?php echo $station || $playerType !== 'full' ? 'none' : 'block'; ?>;
            max-height: <?php echo $height - 80; ?>px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .station-item {
            display: flex;
            align-items: center;
            padding: 8px;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 5px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .station-item:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .station-item.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .station-logo {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            margin-right: 10px;
            object-fit: cover;
        }

        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            font-size: 0.9rem;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scrollbar styling */
        .station-list::-webkit-scrollbar {
            width: 5px;
        }

        .station-list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        .station-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 5px;
        }

        .error-message {
            text-align: center;
            padding: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="player-container">
        <?php if ($showLogo && $playerType !== 'button'): ?>
        <div class="player-header">
            <div class="logo">
                <i class="fas fa-radio"></i>
            </div>
            <div class="station-info">
                <div class="station-name" id="currentStationName">
                    <?php echo $station ? htmlspecialchars($station['name']) : 'MuciRadio'; ?>
                </div>
                <div class="station-genre" id="currentStationGenre">
                    <?php echo $station ? htmlspecialchars($station['genre']) : 'Select a station'; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="player-controls">
            <button class="play-btn" id="playBtn">
                <i class="fas fa-play"></i>
            </button>
            
            <?php if ($playerType !== 'button'): ?>
            <div class="volume-control">
                <i class="fas fa-volume-down"></i>
                <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="50">
                <i class="fas fa-volume-up"></i>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$station && $playerType === 'full'): ?>
        <div class="station-list" id="stationList">
            <div class="loading">
                <div class="spinner"></div>
                Loading stations...
            </div>
        </div>
        <?php endif; ?>

        <audio id="audioPlayer" preload="none"></audio>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const playBtn = document.getElementById('playBtn');
            const volumeSlider = document.getElementById('volumeSlider');
            const audioPlayer = document.getElementById('audioPlayer');
            const stationList = document.getElementById('stationList');
            const currentStationName = document.getElementById('currentStationName');
            const currentStationGenre = document.getElementById('currentStationGenre');

            let currentStation = <?php echo $station ? json_encode($station) : 'null'; ?>;
            let stations = [];
            let isPlaying = false;

            // Load stations if needed
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
            <?php if ($playerType !== 'button'): ?>
            if (volumeSlider) {
                volumeSlider.addEventListener('input', function() {
                    audioPlayer.volume = volumeSlider.value / 100;
                });
            }
            <?php endif; ?>

            // Audio events
            audioPlayer.addEventListener('loadstart', function() {
                playBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            });

            audioPlayer.addEventListener('canplay', function() {
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                isPlaying = true;
            });

            audioPlayer.addEventListener('playing', function() {
                playBtn.innerHTML = '<i class="fas fa-pause"></i>';
                isPlaying = true;
            });

            audioPlayer.addEventListener('pause', function() {
                playBtn.innerHTML = '<i class="fas fa-play"></i>';
                isPlaying = false;
            });

            audioPlayer.addEventListener('error', function(e) {
                playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                isPlaying = false;
                console.error('Audio error:', e);
            });

            // Load stations function
            async function loadStations() {
                try {
                    const response = await fetch('api/stations.php');
                    const result = await response.json();
                    
                    if (result.success && result.data.length > 0) {
                        stations = result.data;
                        displayStations();
                        
                        <?php if ($autoplay): ?>
                        // Auto-select first station if autoplay is enabled
                        selectStation(stations[0]);
                        <?php endif; ?>
                    } else {
                        if (stationList) {
                            stationList.innerHTML = '<div class="error-message">No stations available</div>';
                        }
                    }
                } catch (error) {
                    console.error('Error loading stations:', error);
                    if (stationList) {
                        stationList.innerHTML = '<div class="error-message">Error loading stations</div>';
                    }
                }
            }

            // Display stations in list
            function displayStations() {
                if (!stationList) return;

                stationList.innerHTML = '';
                stations.forEach(station => {
                    const stationItem = document.createElement('div');
                    stationItem.className = 'station-item';
                    stationItem.innerHTML = `
                        <img src="${station.logo_url || 'https://via.placeholder.com/30x30/FFFFFF/FF6B35?text=' + station.name.charAt(0)}" 
                             alt="${station.name}" class="station-logo">
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;">${station.name}</div>
                            <div style="font-size: 0.8rem; opacity: 0.8;">${station.genre}</div>
                        </div>
                    `;
                    
                    stationItem.addEventListener('click', () => selectStation(station));
                    stationList.appendChild(stationItem);
                });
            }

            // Select and play station
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
                document.querySelectorAll('.station-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Highlight current station
                const stationItems = document.querySelectorAll('.station-item');
                const stationIndex = stations.findIndex(s => s.id === station.id);
                if (stationItems[stationIndex]) {
                    stationItems[stationIndex].classList.add('active');
                }
                
                playStation();
            }

            // Play current station
            function playStation() {
                if (!currentStation) return;

                audioPlayer.src = currentStation.stream_url;
                audioPlayer.load();
                audioPlayer.play().catch(error => {
                    console.error('Play failed:', error);
                    playBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                });
            }

            // Auto-play if single station and autoplay enabled
            <?php if ($station && $autoplay): ?>
            setTimeout(() => {
                playStation();
            }, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>
