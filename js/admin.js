// JavaScript for Admin Panel

document.addEventListener('DOMContentLoaded', function() {
    const stationsTable = document.getElementById('stationsTable');
    const stationModal = document.getElementById('stationModal');
    const addStationBtn = document.getElementById('addStationBtn');
    const stationForm = document.getElementById('stationForm');
    const modalTitle = document.getElementById('modalTitle');
    const stationIdInput = document.getElementById('stationId');
    const stationNameInput = document.getElementById('stationName');
    const stationGenreInput = document.getElementById('stationGenre');
    const stationCountryInput = document.getElementById('stationCountry');
    const stationUrlInput = document.getElementById('stationUrl');
    const stationLogoInput = document.getElementById('stationLogo');

    let editMode = false;
    let stations = [];

    // API Base URL
    const API_BASE = 'api/stations.php';

    // Load stations from database
    async function loadStations() {
        console.log('🚀 Admin: Loading stations from:', API_BASE);
        
        try {
            const response = await fetch(API_BASE);
            console.log('📡 Admin: Response received:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('📄 Admin: Response data:', result);
            
            if (result.success) {
                stations = result.data;
                console.log('✅ Admin: Loaded', stations.length, 'stations');
                renderStationsTable();
            } else {
                console.error('❌ Admin: API error:', result.error);
                stationsTable.innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">Error: ${result.error}</td></tr>`;
            }
        } catch (error) {
            console.error('❌ Admin: Network error:', error);
            stationsTable.innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">Network Error: ${error.message}<br><br><a href="setup.php">Run Setup</a> | <a href="debug_api.php">Debug API</a></td></tr>`;
        }
    }

    // Render stations table
    function renderStationsTable() {
        stationsTable.innerHTML = '';

        stations.forEach((station) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${station.id}</td>
                <td>${station.name}</td>
                <td>${station.genre}</td>
                <td>${station.country}</td>
                <td>
                    <button class='edit-btn' data-id='${station.id}'>Edit</button>
                    <button class='delete-btn' data-id='${station.id}'>Delete</button>
                </td>
            `;
            stationsTable.appendChild(row);
        });
    }

    loadStations();

    // Add or update station
    stationForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const stationData = {
            name: stationNameInput.value,
            genre: stationGenreInput.value,
            country: stationCountryInput.value,
            stream_url: stationUrlInput.value,
            logo_url: stationLogoInput.value
        };

        try {
            let response;
            if (editMode) {
                // Update existing station
                const stationId = stationIdInput.value;
                response = await fetch(`${API_BASE}?id=${stationId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(stationData)
                });
            } else {
                // Create new station
                response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(stationData)
                });
            }

            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                closeModal();
                resetForm();
                editMode = false;
                modalTitle.textContent = 'Add New Station';
                loadStations();
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            console.error('Error saving station:', error);
            alert('Network error: ' + error.message);
        }
    });

    // Open modal with empty form
    addStationBtn.addEventListener('click', function() {
        openModal();
        resetForm();
        modalTitle.textContent = 'Add New Station';
    });

    // Edit and delete station handlers
    stationsTable.addEventListener('click', async function(e) {
        if (e.target.classList.contains('edit-btn')) {
            const stationId = e.target.getAttribute('data-id');
            const station = stations.find(s => s.id == stationId);

            if (station) {
                openModal();
                modalTitle.textContent = 'Edit Station';
                stationIdInput.value = station.id;
                stationNameInput.value = station.name;
                stationGenreInput.value = station.genre;
                stationCountryInput.value = station.country;
                stationUrlInput.value = station.stream_url;
                stationLogoInput.value = station.logo_url || '';

                editMode = true;
            }
        }

        // Delete station
        if (e.target.classList.contains('delete-btn')) {
            const stationId = e.target.getAttribute('data-id');
            
            if (confirm('Are you sure you want to delete this station?')) {
                try {
                    const response = await fetch(`${API_BASE}?id=${stationId}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(result.message);
                        loadStations();
                    } else {
                        alert('Error: ' + result.error);
                    }
                } catch (error) {
                    console.error('Error deleting station:', error);
                    alert('Network error: ' + error.message);
                }
            }
        }
    });

    function openModal() {
        stationModal.style.display = 'block';
    }

    window.closeModal = function() {
        stationModal.style.display = 'none';
    }

    function resetForm() {
        stationIdInput.value = '';
        stationNameInput.value = '';
        stationGenreInput.value = '';
        stationCountryInput.value = '';
        stationUrlInput.value = '';
        stationLogoInput.value = '';
    }

    window.addEventListener('click', function(event) {
        if (event.target == stationModal) {
            closeModal();
        }
    });
});

