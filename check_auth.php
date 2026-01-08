<?php
// Script de vérification de l'authentification
require_once 'config/database.php';

echo "<h2>Vérification de l'authentification SIEM</h2>";

try {
    $db = new ConfigDatabase();
    echo "<p style='color: green;'>✓ Base de données initialisée avec succès</p>";
    
    // Vérifier si l'utilisateur admin existe
    $stmt = $db->db->prepare("SELECT username, email, role, is_active, created_at FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Utilisateur admin trouvé :</p>";
        echo "<ul>";
        echo "<li><strong>Nom d'utilisateur :</strong> " . htmlspecialchars($admin['username']) . "</li>";
        echo "<li><strong>Email :</strong> " . htmlspecialchars($admin['email']) . "</li>";
        echo "<li><strong>Rôle :</strong> " . htmlspecialchars($admin['role']) . "</li>";
        echo "<li><strong>Actif :</strong> " . ($admin['is_active'] ? 'Oui' : 'Non') . "</li>";
        echo "<li><strong>Créé le :</strong> " . htmlspecialchars($admin['created_at']) . "</li>";
        echo "</ul>";
        
        echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>Informations de connexion :</h3>";
        echo "<p><strong>Nom d'utilisateur :</strong> admin</p>";
        echo "<p><strong>Mot de passe :</strong> admin123</p>";
        echo "<p style='color: red;'><strong>⚠️ Changez ce mot de passe après la première connexion !</strong></p>";
        echo "</div>";
        
    } else {
        echo "<p style='color: red;'>✗ Utilisateur admin non trouvé</p>";
        echo "<p>Création de l'utilisateur admin...</p>";
        
        // Créer l'utilisateur admin manuellement
        $passwordHash = password_hash('admin123', PASSWORD_ARGON2ID);
        $stmt = $db->db->prepare("
            INSERT INTO users (username, email, password_hash, role, is_active) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute(['admin', 'admin@siem.local', $passwordHash, 'admin', 1])) {
            echo "<p style='color: green;'>✓ Utilisateur admin créé avec succès !</p>";
            echo "<p><strong>Nom d'utilisateur :</strong> admin</p>";
            echo "<p><strong>Mot de passe :</strong> admin123</p>";
        } else {
            echo "<p style='color: red;'>✗ Erreur lors de la création de l'utilisateur admin</p>";
        }
    }
    
    // Tester l'authentification
    echo "<h3>Test d'authentification :</h3>";
    $testUser = $db->authenticateUser('admin', 'admin123');
    
    if ($testUser) {
        echo "<p style='color: green;'>✓ Test d'authentification réussi !</p>";
        echo "<p>L'utilisateur peut se connecter avec les identifiants admin/admin123</p>";
    } else {
        echo "<p style='color: red;'>✗ Test d'authentification échoué</p>";
        echo "<p>Vérifiez les identifiants ou la configuration de la base de données</p>";
    }
    
    // Afficher les statistiques
    echo "<h3>Statistiques :</h3>";
    $stmt = $db->db->query("SELECT COUNT(*) as total FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Nombre total d'utilisateurs : " . $userCount['total'] . "</p>";
    
    $stmt = $db->db->query("SELECT COUNT(*) as active FROM users WHERE is_active = 1");
    $activeCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Utilisateurs actifs : " . $activeCount['active'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vérifiez que PHP SQLite est installé et que les permissions sont correctes.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
ul { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
