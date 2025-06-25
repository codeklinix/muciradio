// JavaScript for MuciRadio

document.addEventListener('DOMContentLoaded', function() {
    const stationsGrid = document.getElementById('stationsGrid');
    const favoritesGrid = document.getElementById('favoritesGrid');
    const currentPlayer = document.getElementById('currentPlayer');
    const currentLogo = document.getElementById('currentLogo');
    const currentStation = document.getElementById('currentStation');
    const currentGenre = document.getElementById('currentGenre');
    const playPauseBtn = document.getElementById('playPauseBtn');
    const volumeSlider = document.getElementById('volumeSlider');
    const favoriteBtn = document.getElementById('favoriteBtn');
    const audioPlayer = document.getElementById('audioPlayer');
    const genreButtons = document.querySelectorAll('.genre-btn');
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');
    const installPrompt = document.getElementById('installPrompt');
    const installBtn = document.getElementById('installBtn');
    const dismissBtn = document.getElementById('dismissBtn');

    // Radio stations data (will be loaded from database)
    let stations = [];
    let favorites = JSON.parse(localStorage.getItem('radioFavorites')) || [];
    let currentStationData = null;
    let deferredPrompt = null;
    
    // API Base URL
    const API_BASE = 'api/stations.php';

    // Load stations from database
    async function fetchStations(genre = 'all', search = '') {
        try {
            let url = API_BASE;
            const params = new URLSearchParams();
            
            if (genre !== 'all') {
                params.append('genre', genre);
            }
            
            if (search) {
                params.append('search', search);
            }
            
            if (params.toString()) {
                url += '?' + params.toString();
            }
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                stations = result.data;
                return stations;
            } else {
                console.error('Error loading stations:', result.error);
                return [];
            }
        } catch (error) {
            console.error('Network error:', error);
            return [];
        }
    }

    // Initialize stations
    async function loadStations(genre = 'all') {
        // Show loading spinner
        const loadingSpinner = document.getElementById('loadingSpinner');
        loadingSpinner.style.display = 'flex';
        
        try {
            await fetchStations(genre);
            
            stationsGrid.innerHTML = '';
            stations.forEach(station => {
                const stationCard = document.createElement('div');
                stationCard.className = 'station-card';
                
                // Create fallback logo URL
                const fallbackLogoUrl = `generate_icon.php?text=${encodeURIComponent(station.name.substring(0, 3))}&bg=FF6B35&size=60`;
                const logoUrl = station.logo_url || fallbackLogoUrl;
                
                stationCard.innerHTML = `
                    <div class="station-header">
                        <img src="${logoUrl}" alt="${station.name} Logo" class="station-logo" onerror="this.src='${fallbackLogoUrl}'">
                        <div class="station-info">
                            <h3>${station.name}</h3>
                            <p>${station.country}</p>
                        </div>
                    </div>
                    <div class="station-meta">
                        <span class="station-genre">${station.genre}</span>
                        <div class="station-actions">
                            <button class="action-btn play-btn" data-id="${station.id}">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="action-btn fav-btn" data-id="${station.id}">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>
                `;
                stationsGrid.appendChild(stationCard);
            });
        } catch (error) {
            console.error('Error loading stations:', error);
            stationsGrid.innerHTML = '<p>Error loading stations. Please try again later.</p>';
        } finally {
            // Hide loading spinner
            loadingSpinner.style.display = 'none';
        }
    }

    // Load initial stations
    loadStations();

    // Event delegation for play/favorite buttons
    stationsGrid.addEventListener('click', function(e) {
        const playBtn = e.target.closest('.play-btn');
        const favBtn = e.target.closest('.fav-btn');

        if (playBtn) {
            const stationId = playBtn.getAttribute('data-id');
            const station = stations.find(s => s.id === parseInt(stationId));
            selectStation(station);
        }

        if (favBtn) {
            const stationId = favBtn.getAttribute('data-id');
            toggleFavorite(stationId);
        }
    });

    function selectStation(station) {
        // Set up logo with fallback
        const fallbackLogoUrl = `generate_icon.php?text=${encodeURIComponent(station.name.substring(0, 3))}&bg=FF6B35&size=60`;
        const logoUrl = station.logo_url || fallbackLogoUrl;
        
        currentLogo.src = logoUrl;
        currentLogo.onerror = function() {
            this.src = fallbackLogoUrl;
        };
        
        currentStation.textContent = station.name;
        currentGenre.textContent = station.genre;
        currentStationData = station;
        currentPlayer.style.display = 'flex';
        
        // Clear previous audio and add error handling
        audioPlayer.src = '';
        audioPlayer.load();
        
        // Add event listeners for debugging
        audioPlayer.addEventListener('loadstart', () => {
            console.log('Audio: Load started for', station.name);
            currentGenre.textContent = 'Connecting...';
        });
        
        audioPlayer.addEventListener('canplay', () => {
            console.log('Audio: Can play', station.name);
            currentGenre.textContent = station.genre + ' - Ready';
        });
        
        audioPlayer.addEventListener('playing', () => {
            console.log('Audio: Playing', station.name);
            currentGenre.textContent = station.genre + ' - Playing ♪';
            playPauseBtn.querySelector('i').className = 'fas fa-pause';
        });
        
        audioPlayer.addEventListener('error', (e) => {
            console.error('Audio error for', station.name, e.target.error);
            const errorMsg = e.target.error?.message || 'Stream unavailable';
            currentGenre.textContent = station.genre + ' - Error: ' + errorMsg;
            playPauseBtn.querySelector('i').className = 'fas fa-play';
            
            // Show user-friendly error message
            showNotification('Failed to play ' + station.name + ': ' + errorMsg, 'error');
        });
        
        audioPlayer.addEventListener('stalled', () => {
            console.warn('Audio stalled for', station.name);
            currentGenre.textContent = station.genre + ' - Buffering...';
        });
        
        // Set source and attempt to play
        audioPlayer.src = station.stream_url;
        audioPlayer.load();
        
        audioPlayer.play().catch(error => {
            console.error('Play failed for', station.name, error);
            currentGenre.textContent = station.genre + ' - Play failed';
            playPauseBtn.querySelector('i').className = 'fas fa-play';
            showNotification('Could not play ' + station.name + ': ' + error.message, 'error');
        });
    }
    
    // Show notification function
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()">&times;</button>
            </div>
        `;
        
        // Style the notification
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${type === 'error' ? '#f8d7da' : '#d4edda'};
            color: ${type === 'error' ? '#721c24' : '#155724'};
            border: 1px solid ${type === 'error' ? '#f5c6cb' : '#c3e6cb'};
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    function toggleFavorite(stationId) {
        console.log('Toggle favorite for station: ', stationId);
        // Implement favorite functionality
    }

    playPauseBtn.addEventListener('click', function() {
        if (audioPlayer.paused) {
            audioPlayer.play();
            playPauseBtn.querySelector('i').className = 'fas fa-pause';
        } else {
            audioPlayer.pause();
            playPauseBtn.querySelector('i').className = 'fas fa-play';
        }
    });

    volumeSlider.addEventListener('input', function() {
        audioPlayer.volume = volumeSlider.value / 100;
    });

    genreButtons.forEach(button => {
        button.addEventListener('click', function() {
            genreButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const genre = button.getAttribute('data-genre');
            loadStations(genre);
        });
    });

    // Preload the loading spinner functionality
    const loadingSpinner = document.getElementById('loadingSpinner');
    loadingSpinner.style.display = 'none';
    
    // Initialize image fallback system
    initializeImageFallbacks();
    
    // Image fallback system
    function initializeImageFallbacks() {
        // Set up observer for dynamically added images
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        const images = node.querySelectorAll ? node.querySelectorAll('img.station-logo, img[src*="generate_icon.php"]') : [];
                        images.forEach(addImageFallback);
                        
                        if (node.classList && node.classList.contains('station-logo')) {
                            addImageFallback(node);
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Handle existing images
        document.querySelectorAll('img.station-logo, img[src*="generate_icon.php"]').forEach(addImageFallback);
    }
    
    function addImageFallback(img) {
        if (img.dataset.fallbackAdded) return; // Prevent duplicate handlers
        
        img.dataset.fallbackAdded = 'true';
        img.onerror = function() {
            const text = this.alt ? this.alt.substring(0, 3).toUpperCase() : 'R';
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = 60;
            canvas.height = 60;
            
            // Create gradient background
            const gradient = ctx.createLinearGradient(0, 0, 60, 60);
            gradient.addColorStop(0, '#FF6B35');
            gradient.addColorStop(1, '#F7931E');
            
            // Draw background circle
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(30, 30, 30, 0, 2 * Math.PI);
            ctx.fill();
            
            // Draw text
            ctx.fillStyle = 'white';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, 30, 30);
            
            // Set the canvas as the image source
            this.src = canvas.toDataURL();
            this.onerror = null; // Prevent infinite loop
        };
    }
});
