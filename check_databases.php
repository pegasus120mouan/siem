<?php
// Script de vérification des bases de données SIEM
echo "<h2>Vérification des bases de données SIEM</h2>";

// Vérification de la base de configuration
echo "<h3>1. Base de données de configuration (SQLite)</h3>";
$configDbPath = __DIR__ . '/config/siem_config.db';

if (file_exists($configDbPath)) {
    echo "<p style='color: green;'>✓ Base de configuration trouvée : " . $configDbPath . "</p>";
    echo "<p>Taille : " . number_format(filesize($configDbPath) / 1024, 2) . " KB</p>";
    echo "<p>Dernière modification : " . date('Y-m-d H:i:s', filemtime($configDbPath)) . "</p>";
    
    try {
        $db = new PDO('sqlite:' . $configDbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Lister les tables
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p><strong>Tables disponibles :</strong></p>";
        echo "<ul>";
        foreach ($tables as $table) {
            $countStmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $countStmt->fetchColumn();
            echo "<li>$table ($count enregistrements)</li>";
        }
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Erreur d'accès : " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ Base de configuration non trouvée</p>";
    echo "<p>Elle sera créée automatiquement lors du premier accès.</p>";
}

// Vérification de la base SUSDR 360
echo "<h3>2. Base de données SUSDR 360 (SQLite)</h3>";
$susdrDbPath = __DIR__ . '/susdr360/data/susdr360.db';

if (file_exists($susdrDbPath)) {
    echo "<p style='color: green;'>✓ Base SUSDR 360 trouvée : " . $susdrDbPath . "</p>";
    echo "<p>Taille : " . number_format(filesize($susdrDbPath) / 1024, 2) . " KB</p>";
    echo "<p>Dernière modification : " . date('Y-m-d H:i:s', filemtime($susdrDbPath)) . "</p>";
} else {
    echo "<p style='color: orange;'>⚠ Base SUSDR 360 non trouvée</p>";
    echo "<p>Chemin attendu : " . $susdrDbPath . "</p>";
    
    // Vérifier si le dossier data existe
    $dataDir = dirname($susdrDbPath);
    if (!is_dir($dataDir)) {
        echo "<p style='color: red;'>✗ Dossier data manquant : " . $dataDir . "</p>";
        echo "<p>Création du dossier...</p>";
        if (mkdir($dataDir, 0755, true)) {
            echo "<p style='color: green;'>✓ Dossier créé avec succès</p>";
        } else {
            echo "<p style='color: red;'>✗ Impossible de créer le dossier</p>";
        }
    }
}

// Vérification de la configuration YAML
echo "<h3>3. Configuration SUSDR 360</h3>";
$configPath = __DIR__ . '/susdr360/config.yaml';

if (file_exists($configPath)) {
    echo "<p style='color: green;'>✓ Fichier de configuration trouvé</p>";
    
    $config = file_get_contents($configPath);
    if (strpos($config, 'database:') !== false) {
        echo "<p style='color: green;'>✓ Configuration de base de données présente</p>";
        
        // Extraire la configuration de la base
        preg_match('/database:\s*\n\s*type:\s*"([^"]+)"\s*\n\s*path:\s*"([^"]+)"/', $config, $matches);
        if ($matches) {
            echo "<p><strong>Type :</strong> " . $matches[1] . "</p>";
            echo "<p><strong>Chemin :</strong> " . $matches[2] . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Configuration de base de données manquante</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Fichier de configuration manquant</p>";
}

// Vérification des extensions PHP
echo "<h3>4. Extensions PHP requises</h3>";
$extensions = ['pdo', 'pdo_sqlite', 'sqlite3'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✓ $ext</p>";
    } else {
        echo "<p style='color: red;'>✗ $ext (manquante)</p>";
    }
}

// Permissions
echo "<h3>5. Permissions</h3>";
$paths = [
    __DIR__ . '/config/',
    __DIR__ . '/susdr360/data/'
];

foreach ($paths as $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "<p style='color: green;'>✓ $path (écriture autorisée)</p>";
        } else {
            echo "<p style='color: red;'>✗ $path (pas d'écriture)</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ $path (dossier manquant)</p>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
ul { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
