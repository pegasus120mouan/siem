<?php
// Script pour modifier la configuration de base de données
echo "<h2>Configuration de base de données SIEM</h2>";

$configFile = __DIR__ . '/config/database.php';
$yamlFile = __DIR__ . '/susdr360/config.yaml';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbType = $_POST['db_type'] ?? 'sqlite';
    
    if ($dbType === 'sqlite') {
        echo "<p style='color: green;'>Configuration SQLite conservée (par défaut)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Changement vers $dbType nécessite une modification manuelle des fichiers</p>";
        echo "<p>Fichiers à modifier :</p>";
        echo "<ul>";
        echo "<li><strong>PHP :</strong> $configFile</li>";
        echo "<li><strong>Python :</strong> $yamlFile</li>";
        echo "</ul>";
    }
}

// Vérifier l'état actuel
echo "<h3>État actuel</h3>";

// Configuration PHP
if (file_exists($configFile)) {
    $phpConfig = file_get_contents($configFile);
    if (strpos($phpConfig, 'sqlite:') !== false) {
        echo "<p>✓ PHP : SQLite configuré</p>";
    } else {
        echo "<p>? PHP : Configuration personnalisée détectée</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Fichier de configuration PHP manquant</p>";
}

// Configuration YAML
if (file_exists($yamlFile)) {
    $yamlConfig = file_get_contents($yamlFile);
    if (strpos($yamlConfig, 'type: "sqlite"') !== false) {
        echo "<p>✓ Python : SQLite configuré</p>";
    } elseif (strpos($yamlConfig, 'type: "postgresql"') !== false) {
        echo "<p>✓ Python : PostgreSQL configuré</p>";
    } elseif (strpos($yamlConfig, 'type: "mysql"') !== false) {
        echo "<p>✓ Python : MySQL configuré</p>";
    } else {
        echo "<p>? Python : Configuration inconnue</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Fichier de configuration YAML manquant</p>";
}

// Vérifier les bases de données existantes
echo "<h3>Bases de données existantes</h3>";

$databases = [
    'Configuration (PHP)' => __DIR__ . '/config/siem_config.db',
    'SUSDR 360 (Python)' => __DIR__ . '/susdr360/data/susdr360.db'
];

foreach ($databases as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        echo "<p style='color: green;'>✓ $name : " . number_format($size / 1024, 2) . " KB</p>";
    } else {
        echo "<p style='color: orange;'>⚠ $name : Non créée (sera créée automatiquement)</p>";
    }
}
?>

<form method="POST" style="margin: 20px 0;">
    <h3>Changer le type de base de données</h3>
    
    <div style="margin: 10px 0;">
        <input type="radio" id="sqlite" name="db_type" value="sqlite" checked>
        <label for="sqlite">SQLite (recommandé pour débuter)</label>
    </div>
    
    <div style="margin: 10px 0;">
        <input type="radio" id="mysql" name="db_type" value="mysql">
        <label for="mysql">MySQL/MariaDB</label>
    </div>
    
    <div style="margin: 10px 0;">
        <input type="radio" id="postgresql" name="db_type" value="postgresql">
        <label for="postgresql">PostgreSQL</label>
    </div>
    
    <button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px;">
        Vérifier la configuration
    </button>
</form>

<div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h4>📁 Fichiers de configuration :</h4>
    <ul>
        <li><strong>PHP (Interface web) :</strong> <code><?= $configFile ?></code></li>
        <li><strong>Python (API) :</strong> <code><?= $yamlFile ?></code></li>
    </ul>
    
    <h4>🗄️ Bases de données :</h4>
    <ul>
        <li><strong>Configuration :</strong> <code><?= __DIR__ ?>/config/siem_config.db</code></li>
        <li><strong>SUSDR 360 :</strong> <code><?= __DIR__ ?>/susdr360/data/susdr360.db</code></li>
    </ul>
</div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3, h4 { color: #333; }
code { background: #f5f5f5; padding: 2px 4px; border-radius: 3px; }
</style>
