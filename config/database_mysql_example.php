<?php
// Exemple de configuration MySQL pour remplacer SQLite
class ConfigDatabase {
    private $db;
    private $host = 'localhost';
    private $dbname = 'siem_config';
    private $username = 'siem_user';
    private $password = 'your_password';
    
    public function __construct() {
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            // Connexion MySQL au lieu de SQLite
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $this->db = new PDO($dsn, $this->username, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer les tables (syntaxe MySQL)
            $this->createTables();
            $this->insertDefaultSettings();
            $this->createDefaultAdmin();
            
        } catch (PDOException $e) {
            throw new Exception("Erreur base de données: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        // Table des utilisateurs (syntaxe MySQL)
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'analyst', 'viewer') DEFAULT 'analyst',
                is_active TINYINT(1) DEFAULT 1,
                last_login TIMESTAMP NULL,
                failed_attempts INT DEFAULT 0,
                locked_until TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Table des sessions
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                session_token VARCHAR(64) UNIQUE NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            ) ENGINE=InnoDB
        ");
        
        // Autres tables...
    }
    
    // Reste des méthodes identiques...
}
?>
