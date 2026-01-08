<?php
// Script pour exécuter des requêtes SQLite rapidement
require_once 'config/database.php';

// Fonction pour exécuter une requête sur la base de config
function queryConfig($sql) {
    try {
        $dbPath = __DIR__ . '/config/siem_config.db';
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Fonction pour exécuter une requête sur la base SUSDR 360
function querySusdr360($sql) {
    try {
        $dbPath = __DIR__ . '/susdr360/data/susdr360.db';
        if (!file_exists($dbPath)) {
            return ['error' => 'Base de données SUSDR 360 non trouvée'];
        }
        
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Fonction pour afficher les résultats
function displayResults($results, $title = '') {
    if ($title) {
        echo "<h3>$title</h3>";
    }
    
    if (isset($results['error'])) {
        echo "<p style='color: red;'>Erreur: " . htmlspecialchars($results['error']) . "</p>";
        return;
    }
    
    if (empty($results)) {
        echo "<p>Aucun résultat</p>";
        return;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    
    // En-têtes
    echo "<tr style='background: #f0f0f0;'>";
    foreach (array_keys($results[0]) as $column) {
        echo "<th style='padding: 8px;'>" . htmlspecialchars($column) . "</th>";
    }
    echo "</tr>";
    
    // Données
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td style='padding: 8px;'>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><em>" . count($results) . " résultat(s)</em></p>";
}

echo "<h1>Requêtes SQLite rapides - SIEM</h1>";

// Exemples de requêtes utiles
echo "<h2>📊 Informations système</h2>";

// Utilisateurs
$users = queryConfig("SELECT username, email, role, is_active, last_login FROM users");
displayResults($users, "👥 Utilisateurs");

// Sessions actives
$sessions = queryConfig("
    SELECT u.username, s.ip_address, s.expires_at, s.created_at 
    FROM user_sessions s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.expires_at > datetime('now')
    ORDER BY s.created_at DESC
");
displayResults($sessions, "🔐 Sessions actives");

// Derniers logs d'authentification
$authLogs = queryConfig("
    SELECT username, action, ip_address, success, message, created_at 
    FROM auth_logs 
    ORDER BY created_at DESC 
    LIMIT 5
");
displayResults($authLogs, "📝 Derniers logs d'authentification");

// Configuration API
$apiConfigs = queryConfig("
    SELECT service_name, 
           CASE WHEN api_key IS NOT NULL THEN 'Oui' ELSE 'Non' END as configured,
           is_active,
           updated_at
    FROM api_configs
");
displayResults($apiConfigs, "🔧 Configuration API");

// Paramètres système
$settings = queryConfig("SELECT setting_key, setting_value, description FROM settings");
displayResults($settings, "⚙️ Paramètres système");

// Informations sur les tables
echo "<h2>🗄️ Structure des bases de données</h2>";

$configTables = queryConfig("
    SELECT name, 
           (SELECT COUNT(*) FROM sqlite_master sm2 WHERE sm2.tbl_name = sm1.name AND sm2.type = 'table') as table_count
    FROM sqlite_master sm1 
    WHERE type = 'table' 
    ORDER BY name
");
displayResults($configTables, "📋 Tables de configuration");

// Vérifier la base SUSDR 360
$susdr360Tables = querySusdr360("SELECT name FROM sqlite_master WHERE type = 'table'");
displayResults($susdr360Tables, "📋 Tables SUSDR 360");

echo "<hr>";
echo "<p><strong>💡 Conseil :</strong> Utilisez <a href='sqlite_query.php'>l'interface web complète</a> pour des requêtes personnalisées.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th { background: #f0f0f0; }
h1, h2, h3 { color: #333; }
</style>
