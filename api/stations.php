<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Add preflight OPTIONS request handling
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    require_once '../config/database.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database configuration error: ' . $e->getMessage()
    ]);
    exit();
}

class StationAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch($method) {
            case 'GET':
                $this->getStations();
                break;
            case 'POST':
                $this->createStation();
                break;
            case 'PUT':
                $this->updateStation();
                break;
            case 'DELETE':
                $this->deleteStation();
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    }
    
    private function getStations() {
        try {
            $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
            $search = isset($_GET['search']) ? $_GET['search'] : null;
            
            $query = "SELECT * FROM stations WHERE is_active = 1";
            $params = [];
            
            if ($genre && $genre !== 'all') {
                $query .= " AND genre = ?";
                $params[] = $genre;
            }
            
            if ($search) {
                $query .= " AND (name LIKE ? OR genre LIKE ? OR country LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $query .= " ORDER BY name ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $stations
            ]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function createStation() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['name'], $input['genre'], $input['country'], $input['stream_url'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required fields'
                ]);
                return;
            }
            
            $query = "INSERT INTO stations (name, genre, country, stream_url, logo_url) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            
            $result = $stmt->execute([
                $input['name'],
                $input['genre'],
                $input['country'],
                $input['stream_url'],
                $input['logo_url'] ?? null
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Station created successfully',
                    'id' => $this->conn->lastInsertId()
                ]);
            } else {
                throw new Exception('Failed to create station');
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function updateStation() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id || !$input) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing station ID or data'
                ]);
                return;
            }
            
            $query = "UPDATE stations SET name = ?, genre = ?, country = ?, stream_url = ?, logo_url = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            $result = $stmt->execute([
                $input['name'],
                $input['genre'],
                $input['country'],
                $input['stream_url'],
                $input['logo_url'] ?? null,
                $id
            ]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Station updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update station');
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    private function deleteStation() {
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : null;
            
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing station ID'
                ]);
                return;
            }
            
            // Soft delete by setting is_active to false
            $query = "UPDATE stations SET is_active = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Station deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete station');
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Handle the request
$api = new StationAPI();
$api->handleRequest();
?>
