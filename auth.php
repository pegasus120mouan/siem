<?php
session_start();
require_once 'config/database.php';

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = new ConfigDatabase();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        header('Content-Type: application/json');
        
        try {
            switch ($method) {
                case 'POST':
                    switch ($action) {
                        case 'login':
                            return $this->login();
                        case 'logout':
                            return $this->logout();
                        default:
                            throw new Exception('Action non supportée');
                    }
                case 'GET':
                    switch ($action) {
                        case 'check':
                            return $this->checkSession();
                        case 'logs':
                            return $this->getAuthLogs();
                        default:
                            throw new Exception('Action non supportée');
                    }
                default:
                    throw new Exception('Méthode non supportée');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['username']) || !isset($input['password'])) {
            throw new Exception('Nom d\'utilisateur et mot de passe requis');
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        if (empty($username) || empty($password)) {
            throw new Exception('Nom d\'utilisateur et mot de passe ne peuvent pas être vides');
        }
        
        // Nettoyer les sessions expirées
        $this->db->cleanExpiredSessions();
        
        // Authentifier l'utilisateur
        $user = $this->db->authenticateUser($username, $password);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Identifiants invalides ou compte verrouillé'
            ]);
            return;
        }
        
        // Créer une session
        $sessionToken = $this->db->createSession(
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        
        if (!$sessionToken) {
            throw new Exception('Erreur lors de la création de la session');
        }
        
        // Stocker le token dans la session PHP
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Définir le cookie sécurisé
        $cookieOptions = [
            'expires' => time() + (24 * 60 * 60), // 24 heures
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ];
        
        setcookie('siem_session', $sessionToken, $cookieOptions);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'session_token' => $sessionToken
        ]);
    }
    
    private function logout() {
        $sessionToken = $_SESSION['session_token'] ?? $_COOKIE['siem_session'] ?? null;
        
        if ($sessionToken) {
            $this->db->destroySession($sessionToken);
        }
        
        // Nettoyer la session PHP
        session_destroy();
        
        // Supprimer le cookie
        setcookie('siem_session', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Déconnexion réussie'
        ]);
    }
    
    private function checkSession() {
        $sessionToken = $_SESSION['session_token'] ?? $_COOKIE['siem_session'] ?? null;
        
        if (!$sessionToken) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Aucune session active'
            ]);
            return;
        }
        
        $user = $this->db->validateSession($sessionToken);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Session invalide ou expirée'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
    
    private function getAuthLogs() {
        // Vérifier que l'utilisateur est admin
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accès refusé'
            ]);
            return;
        }
        
        $limit = intval($_GET['limit'] ?? 100);
        $logs = $this->db->getAuthLogs($limit);
        
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ]);
    }
    
    private function isAdmin() {
        $sessionToken = $_SESSION['session_token'] ?? $_COOKIE['siem_session'] ?? null;
        
        if (!$sessionToken) {
            return false;
        }
        
        $user = $this->db->validateSession($sessionToken);
        return $user && $user['role'] === 'admin';
    }
    
    public static function requireAuth() {
        $auth = new self();
        $sessionToken = $_SESSION['session_token'] ?? $_COOKIE['siem_session'] ?? null;
        
        if (!$sessionToken) {
            header('Location: login.html');
            exit;
        }
        
        $user = $auth->db->validateSession($sessionToken);
        
        if (!$user) {
            header('Location: login.html');
            exit;
        }
        
        return $user;
    }
    
    public static function requireAdmin() {
        $user = self::requireAuth();
        
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Accès administrateur requis'
            ]);
            exit;
        }
        
        return $user;
    }
}

// Traiter la requête si ce fichier est appelé directement
if (basename($_SERVER['PHP_SELF']) === 'auth.php') {
    $auth = new AuthController();
    $auth->handleRequest();
}
?>
