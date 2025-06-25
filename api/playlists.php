<?php
// Playlist API for MuciRadio
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Create playlists table if it doesn't exist
    $createPlaylistsTable = "
        CREATE TABLE IF NOT EXISTS playlists (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            stations TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $conn->exec($createPlaylistsTable);
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetRequest($conn);
            break;
        case 'POST':
            handlePostRequest($conn);
            break;
        case 'PUT':
            handlePutRequest($conn);
            break;
        case 'DELETE':
            handleDeleteRequest($conn);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleGetRequest($conn) {
    try {
        if (isset($_GET['id'])) {
            // Get specific playlist
            $playlistId = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM playlists WHERE id = ?");
            $stmt->execute([$playlistId]);
            $playlist = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($playlist) {
                // Get station details
                $stationIds = explode(',', $playlist['stations']);
                $placeholders = str_repeat('?,', count($stationIds) - 1) . '?';
                $stationStmt = $conn->prepare("SELECT * FROM stations WHERE id IN ($placeholders)");
                $stationStmt->execute($stationIds);
                $stations = $stationStmt->fetchAll(PDO::FETCH_ASSOC);
                
                $playlist['station_details'] = $stations;
                echo json_encode(['success' => true, 'data' => $playlist]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Playlist not found']);
            }
        } else {
            // Get all playlists
            $stmt = $conn->query("SELECT * FROM playlists ORDER BY created_at DESC");
            $playlists = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add station count to each playlist
            foreach ($playlists as &$playlist) {
                $stationIds = explode(',', $playlist['stations']);
                $playlist['station_count'] = count($stationIds);
            }
            
            echo json_encode(['success' => true, 'data' => $playlists]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handlePostRequest($conn) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action not specified']);
            return;
        }
        
        switch ($input['action']) {
            case 'save':
                savePlaylist($conn, $input);
                break;
            case 'get_stations':
                getPlaylistStations($conn, $input);
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function savePlaylist($conn, $input) {
    if (!isset($input['name']) || !isset($input['stations'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Name and stations are required']);
        return;
    }
    
    $name = trim($input['name']);
    $stations = implode(',', array_map('intval', $input['stations']));
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Playlist name cannot be empty']);
        return;
    }
    
    if (empty($stations)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'At least one station is required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO playlists (name, stations) VALUES (?, ?)");
        $stmt->execute([$name, $stations]);
        
        $playlistId = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'playlist_id' => $playlistId,
            'message' => 'Playlist saved successfully'
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Playlist name already exists']);
        } else {
            throw $e;
        }
    }
}

function getPlaylistStations($conn, $input) {
    if (!isset($input['station_ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Station IDs are required']);
        return;
    }
    
    $stationIds = array_map('intval', $input['station_ids']);
    $placeholders = str_repeat('?,', count($stationIds) - 1) . '?';
    
    $stmt = $conn->prepare("SELECT * FROM stations WHERE id IN ($placeholders) AND is_active = 1");
    $stmt->execute($stationIds);
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $stations]);
}

function handlePutRequest($conn) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Playlist ID is required']);
            return;
        }
        
        $playlistId = intval($input['id']);
        $name = isset($input['name']) ? trim($input['name']) : null;
        $stations = isset($input['stations']) ? implode(',', array_map('intval', $input['stations'])) : null;
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        if ($name !== null) {
            $updates[] = "name = ?";
            $params[] = $name;
        }
        
        if ($stations !== null) {
            $updates[] = "stations = ?";
            $params[] = $stations;
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $params[] = $playlistId;
        $sql = "UPDATE playlists SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Playlist updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Playlist not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDeleteRequest($conn) {
    try {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Playlist ID is required']);
            return;
        }
        
        $playlistId = intval($_GET['id']);
        
        $stmt = $conn->prepare("DELETE FROM playlists WHERE id = ?");
        $stmt->execute([$playlistId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Playlist deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Playlist not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
