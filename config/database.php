<?php
// Database configuration
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    
    public function __construct() {
        // Use environment variables for production, fallback to localhost for development
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'muci_radio';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
        $this->password = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
    }
    private $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // First, try to connect to MySQL server
            $testConn = new PDO(
                "mysql:host=" . $this->host,
                $this->username,
                $this->password
            );
            $testConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $testConn->exec("CREATE DATABASE IF NOT EXISTS " . $this->db_name);
            
            // Now connect to the specific database
            // Support both MySQL and PostgreSQL
            $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
            if ($database_url) {
                // Railway PostgreSQL connection
                $this->conn = new PDO($database_url);
            } else {
                // Local MySQL connection
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
            }
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables if they don't exist
            $this->createTables();
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }
    
    private function createTables() {
        try {
            // Create users table first (referenced by other tables)
            $createUsersTable = "
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('admin', 'user', 'radio_owner') DEFAULT 'user',
                    is_premium BOOLEAN DEFAULT FALSE,
                    premium_expires_at DATETIME NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            $this->conn->exec($createUsersTable);
            
            // Create stations table
            $createStationsTable = "
                CREATE TABLE IF NOT EXISTS stations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    genre VARCHAR(100) NOT NULL,
                    country VARCHAR(100) NOT NULL,
                    stream_url TEXT NOT NULL,
                    logo_url TEXT,
                    user_id INT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )
            ";
            $this->conn->exec($createStationsTable);
            
            // Create premium_subscriptions table
            $createSubscriptionsTable = "
                CREATE TABLE IF NOT EXISTS premium_subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    plan_type ENUM('basic', 'premium', 'enterprise') DEFAULT 'basic',
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'USD',
                    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
                    payment_method VARCHAR(50),
                    transaction_id VARCHAR(255),
                    started_at DATETIME NOT NULL,
                    expires_at DATETIME NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ";
            $this->conn->exec($createSubscriptionsTable);
            
            // Create embed_players table
            $createEmbedPlayersTable = "
                CREATE TABLE IF NOT EXISTS embed_players (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    station_id INT NOT NULL,
                    player_name VARCHAR(255) NOT NULL,
                    player_type ENUM('popup', 'sticky', 'elegant', 'mini', 'full') NOT NULL,
                    theme ENUM('default', 'dark', 'light', 'minimal', 'custom') DEFAULT 'default',
                    custom_colors JSON NULL,
                    custom_logo TEXT NULL,
                    width INT DEFAULT 400,
                    height INT DEFAULT 150,
                    autoplay BOOLEAN DEFAULT FALSE,
                    show_logo BOOLEAN DEFAULT TRUE,
                    show_playlist BOOLEAN DEFAULT TRUE,
                    branding_enabled BOOLEAN DEFAULT TRUE,
                    embed_code TEXT,
                    usage_count INT DEFAULT 0,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
                )
            ";
            $this->conn->exec($createEmbedPlayersTable);
            
            // Create embed_analytics table
            $createAnalyticsTable = "
                CREATE TABLE IF NOT EXISTS embed_analytics (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    embed_player_id INT NOT NULL,
                    visitor_ip VARCHAR(45),
                    user_agent TEXT,
                    referrer TEXT,
                    country VARCHAR(100),
                    played_at DATETIME NOT NULL,
                    session_duration INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (embed_player_id) REFERENCES embed_players(id) ON DELETE CASCADE
                )
            ";
            $this->conn->exec($createAnalyticsTable);
            
        } catch(PDOException $e) {
            error_log("Error creating tables: " . $e->getMessage());
        }
    }
}

// Create database and tables if they don't exist
function createDatabase() {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    try {
        // Connect to MySQL
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS muci_radio");
        $pdo->exec("USE muci_radio");
        
        // Create stations table
        $createStationsTable = "
            CREATE TABLE IF NOT EXISTS stations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                genre VARCHAR(100) NOT NULL,
                country VARCHAR(100) NOT NULL,
                stream_url TEXT NOT NULL,
                logo_url TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($createStationsTable);
        
        // Create users table for admin authentication
        $createUsersTable = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $pdo->exec($createUsersTable);
        
        // Insert default admin user (password: admin123)
        $checkAdmin = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'")->fetchColumn();
        if ($checkAdmin == 0) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (username, password, role) VALUES ('admin', '$hashedPassword', 'admin')");
        }
        
        // Insert sample stations
        $checkStations = $pdo->query("SELECT COUNT(*) FROM stations")->fetchColumn();
        if ($checkStations == 0) {
            $sampleStations = [
                ['Jazz FM', 'jazz', 'USA', 'https://stream.radio.co/sf1a2b3c4d/listen', 'https://via.placeholder.com/60x60/FF6B35/FFFFFF?text=JFM'],
                ['Rock Radio', 'rock', 'UK', 'https://stream.radio.co/se1b2c3d4e/listen', 'https://via.placeholder.com/60x60/764ba2/FFFFFF?text=RR'],
                ['Classic Hits', 'classical', 'Germany', 'https://stream.radio.co/sd1c2d3e4f/listen', 'https://via.placeholder.com/60x60/667eea/FFFFFF?text=CH'],
                ['Talk Radio', 'talk', 'Canada', 'https://stream.radio.co/sc1d2e3f4g/listen', 'https://via.placeholder.com/60x60/F7931E/FFFFFF?text=TR'],
                ['Pop Central', 'pop', 'Australia', 'https://stream.radio.co/sb1e2f3g4h/listen', 'https://via.placeholder.com/60x60/FF6B35/FFFFFF?text=PC']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO stations (name, genre, country, stream_url, logo_url) VALUES (?, ?, ?, ?, ?)");
            foreach ($sampleStations as $station) {
                $stmt->execute($station);
            }
        }
        
        return true;
    } catch(PDOException $e) {
        echo "Database creation error: " . $e->getMessage();
        return false;
    }
}

// Initialize database
createDatabase();
?>
