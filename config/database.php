<?php
class ConfigDatabase {
    private $db;
    private $dbPath;
    
    public function __construct() {
        $this->dbPath = __DIR__ . '/siem_config.db';
        $this->initDatabase();
    }
    
    private function initDatabase() {
        try {
            $this->db = new PDO('sqlite:' . $this->dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la table des configurations si elle n'existe pas
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS api_configs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    service_name TEXT UNIQUE NOT NULL,
                    api_key TEXT NOT NULL,
                    is_active INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Créer la table des paramètres généraux
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    setting_key TEXT UNIQUE NOT NULL,
                    setting_value TEXT,
                    description TEXT,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Créer la table des utilisateurs
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT UNIQUE NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT DEFAULT 'analyst',
                    is_active INTEGER DEFAULT 1,
                    last_login DATETIME,
                    failed_attempts INTEGER DEFAULT 0,
                    locked_until DATETIME,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Créer la table des sessions
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NOT NULL,
                    session_token TEXT UNIQUE NOT NULL,
                    ip_address TEXT,
                    user_agent TEXT,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
                )
            ");
            
            // Créer la table des logs d'authentification
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS auth_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT,
                    action TEXT NOT NULL,
                    ip_address TEXT,
                    user_agent TEXT,
                    success INTEGER NOT NULL,
                    message TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            // Insérer les paramètres par défaut
            $this->insertDefaultSettings();
            
            // Créer l'utilisateur admin par défaut
            $this->createDefaultAdmin();
            
        } catch (PDOException $e) {
            throw new Exception("Erreur base de données: " . $e->getMessage());
        }
    }
    
    private function insertDefaultSettings() {
        $defaults = [
            ['osint_rate_limit', '1000', 'Délai entre les requêtes OSINT (ms)'],
            ['max_bulk_size', '100', 'Nombre maximum d\'éléments en analyse en lot'],
            ['watchlist_max_items', '50', 'Nombre maximum d\'éléments en watchlist'],
            ['auto_save_results', '1', 'Sauvegarde automatique des résultats'],
            ['notification_level', 'high', 'Niveau de notification (low/medium/high/critical)']
        ];
        
        foreach ($defaults as $setting) {
            $stmt = $this->db->prepare("
                INSERT OR IGNORE INTO settings (setting_key, setting_value, description) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute($setting);
        }
    }
    
    private function createDefaultAdmin() {
        // Vérifier si l'admin existe déjà
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            // Créer l'utilisateur admin par défaut
            $defaultPassword = 'admin123'; // À changer lors de la première connexion
            $passwordHash = password_hash($defaultPassword, PASSWORD_ARGON2ID);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password_hash, role, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute(['admin', 'admin@siem.local', $passwordHash, 'admin', 1]);
        }
    }
    
    // Gestion des utilisateurs
    public function createUser($username, $email, $password, $role = 'analyst') {
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (username, email, password_hash, role, is_active) 
            VALUES (?, ?, ?, ?, 1)
        ");
        return $stmt->execute([$username, $email, $passwordHash, $role]);
    }
    
    public function authenticateUser($username, $password) {
        // Vérifier si l'utilisateur est verrouillé
        $stmt = $this->db->prepare("
            SELECT id, username, email, password_hash, role, is_active, 
                   failed_attempts, locked_until 
            FROM users 
            WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $this->logAuthAttempt($username, 'login', false, 'Utilisateur inexistant');
            return false;
        }
        
        if (!$user['is_active']) {
            $this->logAuthAttempt($username, 'login', false, 'Compte désactivé');
            return false;
        }
        
        // Vérifier le verrouillage
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $this->logAuthAttempt($username, 'login', false, 'Compte verrouillé');
            return false;
        }
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password_hash'])) {
            // Réinitialiser les tentatives échouées
            $this->resetFailedAttempts($user['id']);
            $this->updateLastLogin($user['id']);
            $this->logAuthAttempt($username, 'login', true, 'Connexion réussie');
            
            unset($user['password_hash']); // Ne pas retourner le hash
            return $user;
        } else {
            // Incrémenter les tentatives échouées
            $this->incrementFailedAttempts($user['id']);
            $this->logAuthAttempt($username, 'login', false, 'Mot de passe incorrect');
            return false;
        }
    }
    
    public function createSession($userId, $ipAddress = null, $userAgent = null) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$userId, $sessionToken, $ipAddress, $userAgent, $expiresAt])) {
            return $sessionToken;
        }
        return false;
    }
    
    public function validateSession($sessionToken) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.email, u.role, u.is_active, s.expires_at
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.session_token = ? AND s.expires_at > CURRENT_TIMESTAMP AND u.is_active = 1
        ");
        $stmt->execute([$sessionToken]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function destroySession($sessionToken) {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
        return $stmt->execute([$sessionToken]);
    }
    
    public function cleanExpiredSessions() {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE expires_at < CURRENT_TIMESTAMP");
        return $stmt->execute();
    }
    
    private function incrementFailedAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_attempts = failed_attempts + 1,
                locked_until = CASE 
                    WHEN failed_attempts >= 4 THEN datetime('now', '+30 minutes')
                    ELSE locked_until 
                END,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    private function resetFailedAttempts($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_attempts = 0, locked_until = NULL, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    private function logAuthAttempt($username, $action, $success, $message = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $this->db->prepare("
            INSERT INTO auth_logs (username, action, ip_address, user_agent, success, message) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$username, $action, $ipAddress, $userAgent, $success ? 1 : 0, $message]);
    }
    
    public function getAuthLogs($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT * FROM auth_logs 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Gestion des clés API
    public function saveApiKey($serviceName, $apiKey) {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO api_configs (service_name, api_key, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$serviceName, $this->encrypt($apiKey)]);
    }
    
    public function getApiKey($serviceName) {
        $stmt = $this->db->prepare("
            SELECT api_key FROM api_configs 
            WHERE service_name = ? AND is_active = 1
        ");
        $stmt->execute([$serviceName]);
        $result = $stmt->fetchColumn();
        
        return $result ? $this->decrypt($result) : null;
    }
    
    public function getAllApiConfigs() {
        $stmt = $this->db->query("
            SELECT service_name, 
                   CASE WHEN api_key IS NOT NULL THEN 1 ELSE 0 END as has_key,
                   is_active,
                   updated_at
            FROM api_configs 
            ORDER BY service_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteApiKey($serviceName) {
        $stmt = $this->db->prepare("DELETE FROM api_configs WHERE service_name = ?");
        return $stmt->execute([$serviceName]);
    }
    
    public function toggleApiStatus($serviceName, $isActive) {
        $stmt = $this->db->prepare("
            UPDATE api_configs 
            SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE service_name = ?
        ");
        return $stmt->execute([$isActive ? 1 : 0, $serviceName]);
    }
    
    // Gestion des paramètres
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        
        return $result !== false ? $result : $default;
    }
    
    public function setSetting($key, $value, $description = null) {
        $stmt = $this->db->prepare("
            INSERT OR REPLACE INTO settings (setting_key, setting_value, description, updated_at) 
            VALUES (?, ?, COALESCE(?, (SELECT description FROM settings WHERE setting_key = ?)), CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([$key, $value, $description, $key]);
    }
    
    public function getAllSettings() {
        $stmt = $this->db->query("
            SELECT setting_key, setting_value, description, updated_at 
            FROM settings 
            ORDER BY setting_key
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Chiffrement simple (pour une sécurité basique)
    private function encrypt($data) {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt($data) {
        $key = $this->getEncryptionKey();
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function getEncryptionKey() {
        $keyFile = __DIR__ . '/.encryption_key';
        
        if (!file_exists($keyFile)) {
            $key = bin2hex(random_bytes(32));
            file_put_contents($keyFile, $key);
            chmod($keyFile, 0600); // Lecture seule pour le propriétaire
        } else {
            $key = file_get_contents($keyFile);
        }
        
        return hash('sha256', $key, true);
    }
    
    // Statistiques et maintenance
    public function getStats() {
        $stats = [];
        
        // Nombre de clés API configurées
        $stmt = $this->db->query("SELECT COUNT(*) FROM api_configs WHERE is_active = 1");
        $stats['active_apis'] = $stmt->fetchColumn();
        
        // Dernière mise à jour
        $stmt = $this->db->query("SELECT MAX(updated_at) FROM api_configs");
        $stats['last_update'] = $stmt->fetchColumn();
        
        // Taille de la base
        $stats['db_size'] = file_exists($this->dbPath) ? filesize($this->dbPath) : 0;
        
        return $stats;
    }
    
    public function backup() {
        $backupPath = __DIR__ . '/backups/siem_config_' . date('Y-m-d_H-i-s') . '.db';
        
        if (!is_dir(dirname($backupPath))) {
            mkdir(dirname($backupPath), 0755, true);
        }
        
        return copy($this->dbPath, $backupPath) ? $backupPath : false;
    }
    
    public function cleanup() {
        // Supprimer les anciennes sauvegardes (garder les 10 dernières)
        $backupDir = __DIR__ . '/backups/';
        if (is_dir($backupDir)) {
            $files = glob($backupDir . 'siem_config_*.db');
            if (count($files) > 10) {
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                for ($i = 10; $i < count($files); $i++) {
                    unlink($files[$i]);
                }
            }
        }
    }
}
?>
