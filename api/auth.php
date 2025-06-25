<?php
// Authentication API for MuciRadio Admin
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
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Start session
            session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role']
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        }
        
    } catch (Exception $e) {
        // Fallback for when database is not available
        if ($username === 'admin' && $password === 'admin123') {
            session_start();
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = 1;
            $_SESSION['admin_username'] = 'admin';
            $_SESSION['admin_role'] = 'admin';
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful (fallback mode)',
                'user' => [
                    'id' => 1,
                    'username' => 'admin',
                    'role' => 'admin'
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
        }
    }
}

function handleLogout() {
    session_start();
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}

function handleVerify() {
    session_start();
    
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => [
                'id' => $_SESSION['admin_user_id'],
                'username' => $_SESSION['admin_username'],
                'role' => $_SESSION['admin_role']
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
