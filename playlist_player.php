<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MuciRadio Playlist Player</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        /* Theme styles with better contrast */
        body.light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #212529;
        }

        body.dark {
            background: linear-gradient(135deg, #212529 0%, #343a40 100%);
            color: #f8f9fa;
        }

        body.orange {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: #ffffff;
        }

        .player-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            padding: 15px;
        }

        /* Header */
        .player-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .player-header h2 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            opacity: 0.9;
        }

        /* Current playing section */
        .current-playing {
            background: rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .current-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .current-logo {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: inherit;
        }

        .current-details h3 {
            font-size: 1rem;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .current-details p {
            opacity: 0.8;
            font-size: 0.85rem;
            line-height: 1.2;
        }

        .controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .control-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: inherit;
            font-size: 16px;
        }

        .control-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        .control-btn.play {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.3);
            font-size: 20px;
        }

        .volume-control {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            max-width: 120px;
        }

        .volume-slider {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: rgba(255,255,255,0.3);
            outline: none;
            cursor: pointer;
        }

        /* Stations list with improved contrast */
        .stations-list {
            flex: 1;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            overflow-y: auto;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .station-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            margin: 6px 0;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .station-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(3px);
            border-color: rgba(255,255,255,0.3);
        }

        .station-item.active {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .station-logo {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: inherit;
            flex-shrink: 0;
        }

        .station-info {
            flex: 1;
            min-width: 0;
        }

        .station-info h4 {
            font-size: 0.9rem;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .station-info p {
            opacity: 0.8;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }

        .loading, .error {
            text-align: center;
            padding: 30px 20px;
            opacity: 0.8;
        }

        .spinner {
            width: 24px;
            height: 24px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Scrollbar styling */
        .stations-list::-webkit-scrollbar {
            width: 6px;
        }

        .stations-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .stations-list::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .stations-list::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Responsive adjustments */
        @media (max-width: 400px) {
            .player-container {
                padding: 10px;
            }
            
            .current-info {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            
            .controls {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .volume-control {
                max-width: 100px;
            }
        }
    </style>
</head>
<body class="<?php echo htmlspecialchars($_GET['theme'] ?? 'orange'); ?>">
    <div class="player-container">
        <!-- Header -->
        <div class="player-header">
            <h2><i class="fas fa-radio"></i> MuciRadio</h2>
        </div>

        <!-- Current Playing Station -->
        <div class="current-playing" id="currentPlaying" style="display: none;">
            <div class="current-info">
                <div class="current-logo" id="currentLogo">
                    <i class="fas fa-music"></i>
                </div>
                <div class="current-details">
                    <h3 id="currentStation">Select a station</h3>
                    <p id="currentGenre">Choose from the list below</p>
                </div>
            </div>
            <div class="controls">
                <button class="control-btn" id="prevBtn" title="Previous">
                    <i class="fas fa-step-backward"></i>
                </button>
                <button class="control-btn play" id="playPauseBtn" title="Play/Pause">
                    <i class="fas fa-play"></i>
                </button>
                <button class="control-btn" id="nextBtn" title="Next">
                    <i class="fas fa-step-forward"></i>
                </button>
                <div class="volume-control">
                    <i class="fas fa-volume-down"></i>
                    <input type="range" class="volume-slider" id="volumeSlider" min="0" max="100" value="50" title="Volume">
                    <i class="fas fa-volume-up"></i>
                </div>
            </div>
        </div>

        <!-- Stations List -->
        <div class="stations-list" id="stationsList">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading stations...</p>
            </div>
        </div>

        <!-- Hidden Audio Player -->
        <audio id="audioPlayer" preload="none"></audio>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const stationIds = urlParams.get('stations')?.split(',').map(id => parseInt(id)) || [];
        const theme = urlParams.get('theme') || 'orange';
        const autoplay = urlParams.get('autoplay') === 'true';
        const shuffle = urlParams.get('shuffle') === 'true';

        let stations = [];
        let currentStationIndex = 0;
        let currentStation = null;
        let isPlaying = false;

        // DOM elements
        const stationsList = document.getElementById('stationsList');
        const currentPlaying = document.getElementById('currentPlaying');
        const currentLogo = document.getElementById('currentLogo');
        const currentStationEl = document.getElementById('currentStation');
        const currentGenre = document.getElementById('currentGenre');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const volumeSlider = document.getElementById('volumeSlider');
        const audioPlayer = document.getElementById('audioPlayer');

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadStations();
            setupEventListeners();
        });

        // Load stations from API
        async function loadStations() {
            try {
                if (stationIds.length === 0) {
                    throw new Error('No stations specified in playlist');
                }

                const response = await fetch('api/playlists.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_stations',
                        station_ids: stationIds
                    })
                });

                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    stations = result.data;
                    
                    if (shuffle) {
                        shuffleArray(stations);
                    }
                    
                    renderStations();
                    
                    if (autoplay && stations.length > 0) {
                        setTimeout(() => selectStation(0), 1000);
                    }
                } else {
                    throw new Error(result.error || 'No stations found');
                }
            } catch (error) {
                console.error('Error loading stations:', error);
                stationsList.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Error loading playlist: ${error.message}</p>
                    </div>
                `;
            }
        }

        // Render stations list
        function renderStations() {
            stationsList.innerHTML = '';
            
            stations.forEach((station, index) => {
                const stationItem = document.createElement('div');
                stationItem.className = 'station-item';
                stationItem.onclick = () => selectStation(index);
                
                // Create fallback for logo
                const stationText = station.name.substring(0, 2).toUpperCase();
                const logoHtml = station.logo_url 
                    ? `<img src="${station.logo_url}" alt="${station.name}" class="station-logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                       <div class="station-logo" style="display:none;">${stationText}</div>`
                    : `<div class="station-logo">${stationText}</div>`;
                
                stationItem.innerHTML = `
                    ${logoHtml}
                    <div class="station-info">
                        <h4>${station.name}</h4>
                        <p>${station.genre} • ${station.country}</p>
                    </div>
                `;
                
                stationsList.appendChild(stationItem);
            });
        }

        // Select station by index
        function selectStation(index) {
            if (index < 0 || index >= stations.length) return;
            
            currentStationIndex = index;
            currentStation = stations[index];
            
            // Update UI
            updateActiveStation();
            updateCurrentPlaying();
            
            // Show current playing section
            currentPlaying.style.display = 'block';
            
            // Load and potentially play audio
            loadAudio();
        }

        // Update active station in list
        function updateActiveStation() {
            document.querySelectorAll('.station-item').forEach((item, i) => {
                item.classList.toggle('active', i === currentStationIndex);
            });
        }

        // Update current playing display
        function updateCurrentPlaying() {
            if (!currentStation) return;
            
            currentStationEl.textContent = currentStation.name;
            currentGenre.textContent = `${currentStation.genre} • ${currentStation.country}`;
            
            // Update logo
            const stationText = currentStation.name.substring(0, 2).toUpperCase();
            if (currentStation.logo_url) {
                currentLogo.innerHTML = `<img src="${currentStation.logo_url}" alt="${currentStation.name}" 
                    style="width:100%;height:100%;object-fit:cover;border-radius:6px;" 
                    onerror="this.style.display='none'; this.parentElement.innerHTML='${stationText}';">`;
            } else {
                currentLogo.textContent = stationText;
            }
        }

        // Load audio
        function loadAudio() {
            if (!currentStation) return;
            
            audioPlayer.src = currentStation.stream_url;
            audioPlayer.load();
            
            if (isPlaying) {
                playAudio();
            }
        }

        // Play audio
        function playAudio() {
            audioPlayer.play().then(() => {
                isPlaying = true;
                playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                currentGenre.textContent = `${currentStation.genre} • Playing ♪`;
            }).catch(error => {
                console.error('Play failed:', error);
                currentGenre.textContent = `${currentStation.genre} • Error playing`;
                isPlaying = false;
                playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            });
        }

        // Pause audio
        function pauseAudio() {
            audioPlayer.pause();
            isPlaying = false;
            playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
            currentGenre.textContent = `${currentStation.genre} • Paused`;
        }

        // Setup event listeners
        function setupEventListeners() {
            // Play/Pause button
            playPauseBtn.addEventListener('click', () => {
                if (!currentStation) {
                    if (stations.length > 0) {
                        selectStation(0);
                        return;
                    }
                }
                
                if (isPlaying) {
                    pauseAudio();
                } else {
                    playAudio();
                }
            });

            // Previous button
            prevBtn.addEventListener('click', () => {
                const newIndex = currentStationIndex > 0 ? currentStationIndex - 1 : stations.length - 1;
                selectStation(newIndex);
            });

            // Next button
            nextBtn.addEventListener('click', () => {
                const newIndex = currentStationIndex < stations.length - 1 ? currentStationIndex + 1 : 0;
                selectStation(newIndex);
            });

            // Volume slider
            volumeSlider.addEventListener('input', () => {
                audioPlayer.volume = volumeSlider.value / 100;
            });

            // Audio events
            audioPlayer.addEventListener('loadstart', () => {
                if (currentStation) {
                    currentGenre.textContent = `${currentStation.genre} • Connecting...`;
                }
            });

            audioPlayer.addEventListener('playing', () => {
                if (currentStation) {
                    currentGenre.textContent = `${currentStation.genre} • Playing ♪`;
                    isPlaying = true;
                    playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
                }
            });

            audioPlayer.addEventListener('pause', () => {
                if (currentStation) {
                    currentGenre.textContent = `${currentStation.genre} • Paused`;
                    isPlaying = false;
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
            });

            audioPlayer.addEventListener('error', () => {
                if (currentStation) {
                    currentGenre.textContent = `${currentStation.genre} • Stream error`;
                    isPlaying = false;
                    playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
                }
            });
        }

        // Shuffle array
        function shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }
    </script>
</body>
</html>
