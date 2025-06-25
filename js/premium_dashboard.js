// Premium Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const subscriptionInfo = document.getElementById('subscriptionInfo');
    const pricingSection = document.getElementById('pricingSection');
    const embedManagement = document.getElementById('embedManagement');
    const analyticsSection = document.getElementById('analyticsSection');
    const createEmbedForm = document.getElementById('createEmbedForm');
    const embedList = document.getElementById('embedList');
    const analyticsGrid = document.getElementById('analyticsGrid');
    const stationSelect = document.getElementById('stationSelect');
    const codeModal = document.getElementById('codeModal');
    const modalCodeContent = document.getElementById('modalCodeContent');

    let currentUser = null;
    let stations = [];
    let embedPlayers = [];
    let selectedPlayerType = 'elegant';

    // Initialize
    init();

    async function init() {
        // For demo purposes, we'll simulate a user ID
        currentUser = { id: 1, username: 'demo_user' };
        
        await loadStations();
        await checkPremiumStatus();
        setupEventListeners();
    }

    function setupEventListeners() {
        // Player type selection
        const playerTypeOptions = document.querySelectorAll('.player-type-option');
        playerTypeOptions.forEach(option => {
            option.addEventListener('click', function() {
                playerTypeOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                selectedPlayerType = option.getAttribute('data-type');
            });
        });

        // Create embed form
        createEmbedForm.addEventListener('submit', handleCreateEmbed);

        // Modal close
        const closeModal = document.querySelector('.close');
        closeModal.addEventListener('click', () => {
            codeModal.style.display = 'none';
        });

        // Click outside modal to close
        window.addEventListener('click', (event) => {
            if (event.target === codeModal) {
                codeModal.style.display = 'none';
            }
        });

        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navMenu = document.querySelector('.nav-menu');
        
        if (hamburger && navMenu) {
            hamburger.addEventListener('click', function() {
                hamburger.classList.toggle('active');
                navMenu.classList.toggle('active');
            });
        }
    }

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

    async function checkPremiumStatus() {
        try {
            const response = await fetch(`api/premium_embeds.php/check-premium?user_id=${currentUser.id}`);
            const result = await response.json();
            
            if (result.success) {
                displaySubscriptionStatus(result.data);
                
                if (result.data.is_premium) {
                    showPremiumFeatures();
                } else {
                    showPricingSection();
                }
            }
        } catch (error) {
            console.error('Error checking premium status:', error);
            showPricingSection();
        }
    }

    function displaySubscriptionStatus(data) {
        const isPremium = data.is_premium;
        const planType = data.plan_type;
        const expiresAt = data.expires_at;

        subscriptionInfo.innerHTML = `
            <div class="status-grid">
                <div class="status-card ${isPremium ? 'active' : 'inactive'}">
                    <h3><i class="fas fa-crown"></i></h3>
                    <h4>Premium Status</h4>
                    <p>${isPremium ? 'Active' : 'Inactive'}</p>
                </div>
                <div class="status-card ${isPremium ? 'active' : 'inactive'}">
                    <h3><i class="fas fa-calendar"></i></h3>
                    <h4>Plan Type</h4>
                    <p>${planType.charAt(0).toUpperCase() + planType.slice(1)}</p>
                </div>
                <div class="status-card ${isPremium ? 'active' : 'inactive'}">
                    <h3><i class="fas fa-clock"></i></h3>
                    <h4>Expires</h4>
                    <p>${expiresAt ? new Date(expiresAt).toLocaleDateString() : 'Never'}</p>
                </div>
                <div class="status-card ${data.features.embed_players ? 'active' : 'inactive'}">
                    <h3><i class="fas fa-code"></i></h3>
                    <h4>Embed Players</h4>
                    <p>${data.features.embed_players ? 'Available' : 'Locked'}</p>
                </div>
            </div>
        `;
    }

    function showPricingSection() {
        pricingSection.style.display = 'block';
        embedManagement.style.display = 'none';
        analyticsSection.style.display = 'none';
    }

    function showPremiumFeatures() {
        pricingSection.style.display = 'none';
        embedManagement.style.display = 'block';
        analyticsSection.style.display = 'block';
        
        loadEmbedPlayers();
        loadAnalytics();
    }

    window.subscribeToPremium = async function() {
        // In a real implementation, this would integrate with a payment processor
        // For demo purposes, we'll simulate the subscription
        
        try {
            const response = await fetch('api/premium_embeds.php/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: currentUser.id,
                    payment_method: 'demo',
                    transaction_id: 'demo_' + Date.now()
                })
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Premium subscription activated successfully!', 'success');
                setTimeout(() => {
                    checkPremiumStatus();
                }, 1000);
            } else {
                showNotification('Failed to activate subscription: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Subscription error:', error);
            showNotification('An error occurred during subscription', 'error');
        }
    };

    async function handleCreateEmbed(event) {
        event.preventDefault();
        
        const formData = {
            user_id: currentUser.id,
            station_id: stationSelect.value,
            player_name: document.getElementById('playerName').value,
            player_type: selectedPlayerType,
            width: parseInt(document.getElementById('playerWidth').value),
            height: parseInt(document.getElementById('playerHeight').value),
            theme: document.getElementById('playerTheme').value,
            autoplay: document.getElementById('autoplayCheck').checked,
            show_logo: document.getElementById('showLogoCheck').checked
        };

        try {
            const response = await fetch('api/premium_embeds.php/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Embed player created successfully!', 'success');
                createEmbedForm.reset();
                document.querySelector('.player-type-option.active').classList.remove('active');
                document.querySelector('.player-type-option[data-type="elegant"]').classList.add('active');
                selectedPlayerType = 'elegant';
                loadEmbedPlayers();
            } else {
                showNotification('Failed to create embed player: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Create embed error:', error);
            showNotification('An error occurred while creating the embed player', 'error');
        }
    }

    async function loadEmbedPlayers() {
        try {
            const response = await fetch(`api/premium_embeds.php/list?user_id=${currentUser.id}`);
            const result = await response.json();
            
            if (result.success) {
                embedPlayers = result.data;
                displayEmbedPlayers();
            }
        } catch (error) {
            console.error('Error loading embed players:', error);
            embedList.innerHTML = '<div class="error">Failed to load embed players</div>';
        }
    }

    function displayEmbedPlayers() {
        if (embedPlayers.length === 0) {
            embedList.innerHTML = '<div class="no-players">No embed players created yet. Create your first one above!</div>';
            return;
        }

        embedList.innerHTML = '';
        embedPlayers.forEach(player => {
            const playerElement = document.createElement('div');
            playerElement.className = 'embed-item';
            playerElement.innerHTML = `
                <div class="embed-header">
                    <div>
                        <h4>${player.player_name}</h4>
                        <p>${player.station_name} - ${player.station_genre}</p>
                    </div>
                    <div class="embed-type-badge ${player.player_type}">${player.player_type}</div>
                </div>
                <div class="embed-details">
                    <p><strong>Size:</strong> ${player.width}x${player.height}px</p>
                    <p><strong>Theme:</strong> ${player.theme}</p>
                    <p><strong>Usage:</strong> ${player.usage_count} plays</p>
                    <p><strong>Created:</strong> ${new Date(player.created_at).toLocaleDateString()}</p>
                </div>
                <div class="embed-actions">
                    <button class="btn-small btn-copy" onclick="showEmbedCode('${player.embed_code}')">
                        <i class="fas fa-code"></i> View Code
                    </button>
                    <button class="btn-small btn-edit" onclick="editEmbed(${player.id})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-small btn-delete" onclick="deleteEmbed(${player.id})">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            `;
            embedList.appendChild(playerElement);
        });
    }

    window.showEmbedCode = function(embedCode) {
        modalCodeContent.textContent = embedCode;
        codeModal.style.display = 'block';
    };

    window.copyModalCode = function() {
        const code = modalCodeContent.textContent;
        navigator.clipboard.writeText(code).then(function() {
            showNotification('Embed code copied to clipboard!', 'success');
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            showNotification('Failed to copy embed code', 'error');
        });
    };

    window.editEmbed = function(playerId) {
        // This would open an edit modal in a real implementation
        showNotification('Edit functionality coming soon!', 'info');
    };

    window.deleteEmbed = async function(playerId) {
        if (!confirm('Are you sure you want to delete this embed player?')) {
            return;
        }

        try {
            const response = await fetch(`api/premium_embeds.php/delete/${playerId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ user_id: currentUser.id })
            });

            const result = await response.json();
            
            if (result.success) {
                showNotification('Embed player deleted successfully!', 'success');
                loadEmbedPlayers();
            } else {
                showNotification('Failed to delete embed player: ' + result.message, 'error');
            }
        } catch (error) {
            console.error('Delete embed error:', error);
            showNotification('An error occurred while deleting the embed player', 'error');
        }
    };

    async function loadAnalytics() {
        try {
            const response = await fetch(`api/premium_embeds.php/analytics?user_id=${currentUser.id}`);
            const result = await response.json();
            
            if (result.success) {
                displayAnalytics(result.data);
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
            analyticsGrid.innerHTML = '<div class="error">Failed to load analytics</div>';
        }
    }

    function displayAnalytics(analyticsData) {
        if (!analyticsData || analyticsData.length === 0) {
            analyticsGrid.innerHTML = '<div class="no-data">No analytics data available yet. Create some embed players and get them embedded on websites to see analytics!</div>';
            return;
        }

        // Calculate totals
        const totalPlays = analyticsData.reduce((sum, item) => sum + (item.total_plays || 0), 0);
        const totalVisitors = analyticsData.reduce((sum, item) => sum + (item.unique_visitors || 0), 0);
        const avgDuration = analyticsData.reduce((sum, item) => sum + (item.avg_session_duration || 0), 0) / analyticsData.length;

        analyticsGrid.innerHTML = `
            <div class="analytics-card">
                <div class="analytics-number">${totalPlays}</div>
                <div>Total Plays</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-number">${totalVisitors}</div>
                <div>Unique Visitors</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-number">${Math.round(avgDuration)}s</div>
                <div>Avg. Duration</div>
            </div>
            <div class="analytics-card">
                <div class="analytics-number">${embedPlayers.length}</div>
                <div>Active Players</div>
            </div>
        `;
    }

    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 3000);
    }
});
