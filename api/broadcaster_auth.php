<?php
// Authentication API for Radio Broadcasters
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Action not specified']);
        exit();
    }
    
    switch ($input['action']) {
        case 'register':
            handleRegister($input);
            break;
        case 'login':
            handleLogin($input);
            break;
        case 'logout':
            handleLogout();
            break;
        case 'verify':
            handleVerify();
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

function handleRegister($input) {
    // Validate required fields
    if (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username, email and password are required']);
        return;
    }
    
    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = $input['password'];
    $stationName = isset($input['stationName']) ? trim($input['stationName']) : null;
    
    // Basic validation
    if (strlen($username) < 3) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters long']);
        return;
    }
    
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        return;
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
            return;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new broadcaster
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'radio_owner')");
        $result = $stmt->execute([$username, $email, $hashedPassword]);
        
        if ($result) {
            $userId = $conn->lastInsertId();
            
            // If station name is provided, create a default station
            if ($stationName) {
                $stationStmt = $conn->prepare("INSERT INTO stations (name, genre, country, stream_url, logo_url, user_id) VALUES (?, 'music', 'Unknown', '', '', ?)");
                $stationStmt->execute([$stationName, $userId]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Broadcaster account created successfully',
                'user_id' => $userId
            ]);
        } else {
            throw new Exception('Failed to create broadcaster account');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleLogin($input) {
    if (!isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Username and password required']);
        return;
    }
    
    $username = trim($input['username']);
    $password = $input['password'];
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Check if user exists (allow login with username or email)
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE (username = ? OR email = ?) AND role = 'radio_owner'");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Start session
            session_start();
            $_SESSION['broadcaster_logged_in'] = true;
            $_SESSION['broadcaster_user_id'] = $user['id'];
            $_SESSION['broadcaster_username'] = $user['username'];
            $_SESSION['broadcaster_email'] = $user['email'];
            $_SESSION['broadcaster_role'] = $user['role'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid username/email or password']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    session_start();
    
    // Clear broadcaster session variables
    unset($_SESSION['broadcaster_logged_in']);
    unset($_SESSION['broadcaster_user_id']);
    unset($_SESSION['broadcaster_username']);
    unset($_SESSION['broadcaster_email']);
    unset($_SESSION['broadcaster_role']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}

function handleVerify() {
    session_start();
    
    if (isset($_SESSION['broadcaster_logged_in']) && $_SESSION['broadcaster_logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['broadcaster_user_id'],
                'username' => $_SESSION['broadcaster_username'],
                'email' => $_SESSION['broadcaster_email'],
                'role' => $_SESSION['broadcaster_role']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false
        ]);
    }
}
?>
