// JavaScript for Embed Code Generator

document.addEventListener('DOMContentLoaded', function() {
    const stationSelect = document.getElementById('stationSelect');
    const playerTypeSelect = document.getElementById('playerType');
    const playerWidthInput = document.getElementById('playerWidth');
    const playerHeightInput = document.getElementById('playerHeight');
    const showLogoCheckbox = document.getElementById('showLogo');
    const autoplayCheckbox = document.getElementById('autoplay');
    const themeOptions = document.querySelectorAll('.theme-option');
    const sizePresets = document.querySelectorAll('.size-preset');
    const previewContainer = document.getElementById('previewContainer');
    const codeContent = document.getElementById('codeContent');

    let stations = [];
    let selectedTheme = 'default';

    // Load stations from API
    async function loadStations() {
        try {
            const response = await fetch('api/stations.php');
            const result = await response.json();
            
            if (result.success) {
                stations = result.data;
                populateStationSelect();
            }
        } catch (error) {
            console.error('Error loading stations:', error);
        }
    }

    // Populate station select dropdown
    function populateStationSelect() {
        // Clear existing options except the first one
        while (stationSelect.children.length > 1) {
            stationSelect.removeChild(stationSelect.lastChild);
        }

        stations.forEach(station => {
            const option = document.createElement('option');
            option.value = station.id;
            option.textContent = `${station.name} (${station.genre})`;
            stationSelect.appendChild(option);
        });
    }

    // Initialize
    loadStations();

    // Event listeners for theme selection
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            themeOptions.forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            selectedTheme = option.getAttribute('data-theme');
        });
    });

    // Event listeners for size presets
    sizePresets.forEach(preset => {
        preset.addEventListener('click', function() {
            sizePresets.forEach(p => p.classList.remove('active'));
            preset.classList.add('active');
            const width = preset.getAttribute('data-width');
            playerWidthInput.value = width;
            
            // Adjust height based on player type
            const playerType = playerTypeSelect.value;
            if (playerType === 'mini') {
                playerHeightInput.value = Math.max(80, width * 0.2);
            } else if (playerType === 'button') {
                playerHeightInput.value = Math.max(50, width * 0.15);
            } else {
                playerHeightInput.value = Math.max(150, width * 0.4);
            }
        });
    });

    // Update height when player type changes
    playerTypeSelect.addEventListener('change', function() {
        const width = parseInt(playerWidthInput.value);
        const playerType = playerTypeSelect.value;
        
        if (playerType === 'mini') {
            playerHeightInput.value = Math.max(80, width * 0.2);
        } else if (playerType === 'button') {
            playerHeightInput.value = Math.max(50, width * 0.15);
        } else {
            playerHeightInput.value = Math.max(150, width * 0.4);
        }
    });

    // Generate embed code
    window.generateEmbed = function() {
        const stationId = stationSelect.value;
        const playerType = playerTypeSelect.value;
        const width = playerWidthInput.value;
        const height = playerHeightInput.value;
        const showLogo = showLogoCheckbox.checked;
        const autoplay = autoplayCheckbox.checked;

        // Build URL parameters
        const params = new URLSearchParams({
            type: playerType,
            width: width,
            height: height,
            theme: selectedTheme,
            logo: showLogo ? '1' : '0',
            autoplay: autoplay ? '1' : '0'
        });

        if (stationId !== 'all') {
            params.append('station', stationId);
        }

        const embedUrl = `${window.location.origin}${window.location.pathname.replace('embed.html', '')}player.php?${params.toString()}`;
        
        // Generate embed code
        const embedCode = `<iframe src="${embedUrl}" 
    width="${width}" 
    height="${height}" 
    frameborder="0" 
    allowtransparency="true" 
    allow="autoplay; encrypted-media">
</iframe>`;

        // Update code content
        codeContent.innerHTML = embedCode;

        // Update preview
        previewContainer.innerHTML = `<iframe src="${embedUrl}" width="${width}" height="${height}" frameborder="0" allowtransparency="true" allow="autoplay; encrypted-media"></iframe>`;
        
        // Show success message
        showNotification('Embed code generated successfully!', 'success');
    };

    // Copy embed code to clipboard
    window.copyEmbedCode = function() {
        const codeText = codeContent.textContent;
        
        if (codeText && codeText !== 'Click "Generate Embed Code" to create your custom player code.') {
            navigator.clipboard.writeText(codeText).then(function() {
                showNotification('Embed code copied to clipboard!', 'success');
                
                // Temporarily change button text
                const copyBtn = document.querySelector('.copy-btn');
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                copyBtn.style.background = '#28a745';
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.style.background = '#FF6B35';
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                showNotification('Failed to copy embed code. Please copy manually.', 'error');
            });
        } else {
            showNotification('Please generate embed code first!', 'warning');
        }
    };

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
        let bgColor, textColor, borderColor;
        switch(type) {
            case 'success':
                bgColor = '#d4edda';
                textColor = '#155724';
                borderColor = '#c3e6cb';
                break;
            case 'error':
                bgColor = '#f8d7da';
                textColor = '#721c24';
                borderColor = '#f5c6cb';
                break;
            case 'warning':
                bgColor = '#fff3cd';
                textColor = '#856404';
                borderColor = '#ffeaa7';
                break;
            default:
                bgColor = '#d1ecf1';
                textColor = '#0c5460';
                borderColor = '#bee5eb';
        }
        
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: ${bgColor};
            color: ${textColor};
            border: 1px solid ${borderColor};
            padding: 1rem;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }

    // Mobile menu toggle (if needed)
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });
    }
});

// Add slideIn animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
