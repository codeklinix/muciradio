<?php
// Broadcaster Stations API - Manage stations for radio broadcasters
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

// Check if user is authenticated as broadcaster
session_start();
if (!isset($_SESSION['broadcaster_logged_in']) || $_SESSION['broadcaster_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$broadcasterUserId = $_SESSION['broadcaster_user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch($method) {
        case 'GET':
            handleGetStations($conn, $broadcasterUserId);
            break;
        case 'POST':
            handleCreateStation($conn, $broadcasterUserId);
            break;
        case 'PUT':
            handleUpdateStation($conn, $broadcasterUserId);
            break;
        case 'DELETE':
            handleDeleteStation($conn, $broadcasterUserId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleGetStations($conn, $broadcasterUserId) {
    try {
        $stationId = isset($_GET['id']) ? $_GET['id'] : null;
        $requestUserId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
        
        // Verify user can only access their own stations
        if ($requestUserId && $requestUserId != $broadcasterUserId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }
        
        if ($stationId) {
            // Get specific station
            $stmt = $conn->prepare("SELECT * FROM stations WHERE id = ? AND user_id = ?");
            $stmt->execute([$stationId, $broadcasterUserId]);
            $station = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($station) {
                echo json_encode([
                    'success' => true,
                    'data' => $station
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Station not found']);
            }
        } else {
            // Get all stations for this broadcaster
            $stmt = $conn->prepare("SELECT * FROM stations WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$broadcasterUserId]);
            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $stations
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleCreateStation($conn, $broadcasterUserId) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['name'], $input['genre'], $input['country'], $input['stream_url'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields: name, genre, country, stream_url'
            ]);
            return;
        }
        
        // Validate stream URL
        if (!filter_var($input['stream_url'], FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid stream URL format'
            ]);
            return;
        }
        
        // Validate logo URL if provided
        if (!empty($input['logo_url']) && !filter_var($input['logo_url'], FILTER_VALIDATE_URL)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid logo URL format'
            ]);
            return;
        }
        
        // Check if station name already exists for this user
        $checkStmt = $conn->prepare("SELECT id FROM stations WHERE name = ? AND user_id = ?");
        $checkStmt->execute([$input['name'], $broadcasterUserId]);
        
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'A station with this name already exists in your account'
            ]);
            return;
        }
        
        $stmt = $conn->prepare("
            INSERT INTO stations (name, genre, country, stream_url, logo_url, user_id, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $result = $stmt->execute([
            $input['name'],
            $input['genre'],
            $input['country'],
            $input['stream_url'],
            $input['logo_url'] ?? null,
            $broadcasterUserId
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Station created successfully',
                'id' => $conn->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to create station');
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleUpdateStation($conn, $broadcasterUserId) {
    try {
        $stationId = isset($_GET['id']) ? $_GET['id'] : null;
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$stationId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Station ID is required']);
            return;
        }
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No data provided']);
            return;
        }
        
        // Verify station belongs to this broadcaster
        $checkStmt = $conn->prepare("SELECT id FROM stations WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$stationId, $broadcasterUserId]);
        
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Station not found or access denied']);
            return;
        }
        
        // Build dynamic update query based on provided fields
        $updateFields = [];
        $updateValues = [];
        
        $allowedFields = ['name', 'genre', 'country', 'stream_url', 'logo_url', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                // Validate URLs
                if (($field === 'stream_url' || $field === 'logo_url') && !empty($input[$field])) {
                    if (!filter_var($input[$field], FILTER_VALIDATE_URL)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => "Invalid {$field} format"
                        ]);
                        return;
                    }
                }
                
                $updateFields[] = "{$field} = ?";
                $updateValues[] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No valid fields to update']);
            return;
        }
        
        // Check for duplicate name if name is being updated
        if (isset($input['name'])) {
            $checkNameStmt = $conn->prepare("SELECT id FROM stations WHERE name = ? AND user_id = ? AND id != ?");
            $checkNameStmt->execute([$input['name'], $broadcasterUserId, $stationId]);
            
            if ($checkNameStmt->fetch()) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'error' => 'A station with this name already exists in your account'
                ]);
                return;
            }
        }
        
        $updateFields[] = "updated_at = NOW()";
        $updateValues[] = $stationId;
        
        $query = "UPDATE stations SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Station updated successfully'
            ]);
        } else {
            throw new Exception('Failed to update station');
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDeleteStation($conn, $broadcasterUserId) {
    try {
        $stationId = isset($_GET['id']) ? $_GET['id'] : null;
        
        if (!$stationId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Station ID is required']);
            return;
        }
        
        // Verify station belongs to this broadcaster
        $checkStmt = $conn->prepare("SELECT id FROM stations WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$stationId, $broadcasterUserId]);
        
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Station not found or access denied']);
            return;
        }
        
        // Soft delete by setting is_active to false
        $stmt = $conn->prepare("UPDATE stations SET is_active = 0, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$stationId, $broadcasterUserId]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Station deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete station');
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
