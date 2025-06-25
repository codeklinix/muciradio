<?php
// Premium Embed Players API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$path_info = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check for authentication (simplified for demo)
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);
    
    switch ($method) {
        case 'GET':
            handleGetRequest($conn, $path_info, $user_id);
            break;
        case 'POST':
            handlePostRequest($conn, $path_info, $user_id);
            break;
        case 'PUT':
            handlePutRequest($conn, $path_info, $user_id);
            break;
        case 'DELETE':
            handleDeleteRequest($conn, $path_info, $user_id);
            break;
        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleGetRequest($conn, $path_info, $user_id) {
    if ($path_info === 'check-premium') {
        checkPremiumStatus($conn, $user_id);
    } elseif ($path_info === 'list') {
        listEmbedPlayers($conn, $user_id);
    } elseif ($path_info === 'analytics') {
        getAnalytics($conn, $user_id);
    } else {
        throw new Exception('Invalid endpoint');
    }
}

function handlePostRequest($conn, $path_info, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($path_info === 'create') {
        createEmbedPlayer($conn, $user_id, $input);
    } elseif ($path_info === 'subscribe') {
        subscribeToPremium($conn, $user_id, $input);
    } elseif ($path_info === 'track-play') {
        trackPlayerUsage($conn, $input);
    } else {
        throw new Exception('Invalid endpoint');
    }
}

function handlePutRequest($conn, $path_info, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (preg_match('/^update\/(\d+)$/', $path_info, $matches)) {
        $player_id = (int)$matches[1];
        updateEmbedPlayer($conn, $user_id, $player_id, $input);
    } else {
        throw new Exception('Invalid endpoint');
    }
}

function handleDeleteRequest($conn, $path_info, $user_id) {
    if (preg_match('/^delete\/(\d+)$/', $path_info, $matches)) {
        $player_id = (int)$matches[1];
        deleteEmbedPlayer($conn, $user_id, $player_id);
    } else {
        throw new Exception('Invalid endpoint');
    }
}

function checkPremiumStatus($conn, $user_id) {
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    $stmt = $conn->prepare("
        SELECT u.is_premium, u.premium_expires_at, 
               ps.plan_type, ps.status, ps.expires_at
        FROM users u 
        LEFT JOIN premium_subscriptions ps ON u.id = ps.user_id AND ps.status = 'active'
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    $is_premium = $user['is_premium'] && ($user['premium_expires_at'] === null || $user['premium_expires_at'] > date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'data' => [
            'is_premium' => $is_premium,
            'plan_type' => $user['plan_type'] ?: 'free',
            'expires_at' => $user['expires_at'],
            'features' => [
                'embed_players' => $is_premium,
                'custom_branding' => $is_premium,
                'analytics' => $is_premium,
                'popup_players' => $is_premium,
                'sticky_players' => $is_premium
            ]
        ]
    ]);
}

function subscribeToPremium($conn, $user_id, $input) {
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    // Validate payment (simplified for demo)
    $amount = 5.00; // $5 for premium
    $plan_type = 'premium';
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Create subscription record
        $stmt = $conn->prepare("
            INSERT INTO premium_subscriptions 
            (user_id, plan_type, amount, status, started_at, expires_at, payment_method, transaction_id) 
            VALUES (?, ?, ?, 'active', NOW(), ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            $plan_type, 
            $amount, 
            $expires_at, 
            $input['payment_method'] ?? 'stripe',
            $input['transaction_id'] ?? uniqid('txn_')
        ]);
        
        // Update user premium status
        $stmt = $conn->prepare("
            UPDATE users 
            SET is_premium = TRUE, premium_expires_at = ? 
            WHERE id = ?
        ");
        $stmt->execute([$expires_at, $user_id]);
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Premium subscription activated successfully',
            'data' => [
                'plan_type' => $plan_type,
                'expires_at' => $expires_at
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

function createEmbedPlayer($conn, $user_id, $input) {
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    // Check premium status
    $stmt = $conn->prepare("SELECT is_premium, premium_expires_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_premium'] || ($user['premium_expires_at'] && $user['premium_expires_at'] < date('Y-m-d H:i:s'))) {
        throw new Exception('Premium subscription required');
    }
    
    // Validate input
    $required_fields = ['station_id', 'player_name', 'player_type'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("$field is required");
        }
    }
    
    // Generate embed code
    $embed_params = [
        'station' => $input['station_id'],
        'type' => $input['player_type'],
        'width' => $input['width'] ?? 400,
        'height' => $input['height'] ?? 150,
        'theme' => $input['theme'] ?? 'default',
        'autoplay' => $input['autoplay'] ? '1' : '0',
        'logo' => $input['show_logo'] ? '1' : '0',
        'user_id' => $user_id
    ];
    
    $embed_url = 'https://' . $_SERVER['HTTP_HOST'] . '/MitamboApp/premium_player.php?' . http_build_query($embed_params);
    
    $embed_code = '';
    if ($input['player_type'] === 'popup') {
        $embed_code = generatePopupEmbedCode($embed_url, $input);
    } elseif ($input['player_type'] === 'sticky') {
        $embed_code = generateStickyEmbedCode($embed_url, $input);
    } else {
        $embed_code = generateStandardEmbedCode($embed_url, $input);
    }
    
    // Insert embed player record
    $stmt = $conn->prepare("
        INSERT INTO embed_players 
        (user_id, station_id, player_name, player_type, theme, custom_colors, custom_logo, 
         width, height, autoplay, show_logo, show_playlist, branding_enabled, embed_code) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $input['station_id'],
        $input['player_name'],
        $input['player_type'],
        $input['theme'] ?? 'default',
        json_encode($input['custom_colors'] ?? []),
        $input['custom_logo'] ?? null,
        $input['width'] ?? 400,
        $input['height'] ?? 150,
        $input['autoplay'] ?? false,
        $input['show_logo'] ?? true,
        $input['show_playlist'] ?? true,
        $input['branding_enabled'] ?? true,
        $embed_code
    ]);
    
    $player_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Embed player created successfully',
        'data' => [
            'id' => $player_id,
            'embed_code' => $embed_code,
            'embed_url' => $embed_url
        ]
    ]);
}

function generatePopupEmbedCode($embed_url, $input) {
    $button_text = $input['button_text'] ?? 'Listen Live';
    $button_color = $input['button_color'] ?? '#FF6B35';
    
    return "<!-- MuciRadio Premium Popup Player -->
<script>
function openRadioPlayer() {
    const popup = window.open(
        '$embed_url',
        'RadioPlayer',
        'width={$input['width']},height={$input['height']},scrollbars=no,resizable=yes,status=no,location=no,toolbar=no,menubar=no'
    );
    popup.focus();
}
</script>
<button onclick=\"openRadioPlayer()\" style=\"background: $button_color; color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-size: 16px; font-weight: bold;\">
    🎵 $button_text
</button>";
}

function generateStickyEmbedCode($embed_url, $input) {
    $position = $input['sticky_position'] ?? 'bottom-right';
    
    return "<!-- MuciRadio Premium Sticky Player -->
<div id=\"muci-sticky-player\" style=\"position: fixed; $position: 20px; z-index: 10000; box-shadow: 0 4px 20px rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden;\">
    <iframe src=\"$embed_url\" width=\"{$input['width']}\" height=\"{$input['height']}\" frameborder=\"0\" allowtransparency=\"true\" allow=\"autoplay; encrypted-media\"></iframe>
    <button onclick=\"document.getElementById('muci-sticky-player').style.display='none'\" style=\"position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-size: 12px;\">&times;</button>
</div>";
}

function generateStandardEmbedCode($embed_url, $input) {
    return "<iframe src=\"$embed_url\" width=\"{$input['width']}\" height=\"{$input['height']}\" frameborder=\"0\" allowtransparency=\"true\" allow=\"autoplay; encrypted-media\"></iframe>";
}

function listEmbedPlayers($conn, $user_id) {
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    $stmt = $conn->prepare("
        SELECT ep.*, s.name as station_name, s.genre as station_genre
        FROM embed_players ep
        JOIN stations s ON ep.station_id = s.id
        WHERE ep.user_id = ? AND ep.is_active = 1
        ORDER BY ep.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $players
    ]);
}

function getAnalytics($conn, $user_id) {
    if (!$user_id) {
        throw new Exception('User ID required');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            ep.player_name,
            ep.player_type,
            COUNT(ea.id) as total_plays,
            COUNT(DISTINCT ea.visitor_ip) as unique_visitors,
            AVG(ea.session_duration) as avg_session_duration,
            DATE(ea.played_at) as play_date
        FROM embed_players ep
        LEFT JOIN embed_analytics ea ON ep.id = ea.embed_player_id
        WHERE ep.user_id = ? AND ea.played_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY ep.id, DATE(ea.played_at)
        ORDER BY play_date DESC
    ");
    $stmt->execute([$user_id]);
    $analytics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $analytics
    ]);
}

function trackPlayerUsage($conn, $input) {
    $embed_player_id = $input['embed_player_id'] ?? null;
    if (!$embed_player_id) {
        throw new Exception('Embed player ID required');
    }
    
    $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // Update usage count
    $stmt = $conn->prepare("UPDATE embed_players SET usage_count = usage_count + 1 WHERE id = ?");
    $stmt->execute([$embed_player_id]);
    
    // Insert analytics record
    $stmt = $conn->prepare("
        INSERT INTO embed_analytics 
        (embed_player_id, visitor_ip, user_agent, referrer, played_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$embed_player_id, $visitor_ip, $user_agent, $referrer]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Usage tracked successfully'
    ]);
}

function updateEmbedPlayer($conn, $user_id, $player_id, $input) {
    // Check ownership
    $stmt = $conn->prepare("SELECT id FROM embed_players WHERE id = ? AND user_id = ?");
    $stmt->execute([$player_id, $user_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Player not found or access denied');
    }
    
    // Update player
    $fields = [];
    $values = [];
    
    $allowed_fields = ['player_name', 'theme', 'width', 'height', 'autoplay', 'show_logo', 'custom_colors', 'custom_logo'];
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $fields[] = "$field = ?";
            $values[] = $field === 'custom_colors' ? json_encode($input[$field]) : $input[$field];
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No fields to update');
    }
    
    $values[] = $player_id;
    
    $stmt = $conn->prepare("UPDATE embed_players SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($values);
    
    echo json_encode([
        'success' => true,
        'message' => 'Player updated successfully'
    ]);
}

function deleteEmbedPlayer($conn, $user_id, $player_id) {
    $stmt = $conn->prepare("UPDATE embed_players SET is_active = 0 WHERE id = ? AND user_id = ?");
    $stmt->execute([$player_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Player deleted successfully'
        ]);
    } else {
        throw new Exception('Player not found or access denied');
    }
}
?>
