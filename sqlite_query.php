<?php
// Interface web pour exécuter des requêtes SQLite
session_start();

// Sécurité basique - vous pouvez améliorer cela
$isAdmin = true; // À remplacer par une vraie vérification d'authentification

if (!$isAdmin) {
    die('Accès refusé');
}

$databases = [
    'config' => __DIR__ . '/config/siem_config.db',
    'susdr360' => __DIR__ . '/susdr360/data/susdr360.db'
];

$selectedDb = $_GET['db'] ?? 'config';
$query = $_POST['query'] ?? '';
$results = [];
$error = '';

if ($query && isset($databases[$selectedDb])) {
    try {
        $dbPath = $databases[$selectedDb];
        
        if (!file_exists($dbPath)) {
            throw new Exception("Base de données non trouvée : $dbPath");
        }
        
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Sécurité : limiter aux requêtes SELECT pour éviter les modifications accidentelles
        $queryType = strtoupper(trim(explode(' ', $query)[0]));
        
        if (!in_array($queryType, ['SELECT', 'PRAGMA', 'EXPLAIN'])) {
            throw new Exception("Seules les requêtes SELECT, PRAGMA et EXPLAIN sont autorisées via cette interface");
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        if ($queryType === 'SELECT') {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Requêtes prédéfinies utiles
$predefinedQueries = [
    'config' => [
        'Tous les utilisateurs' => 'SELECT id, username, email, role, is_active, last_login, created_at FROM users',
        'Sessions actives' => 'SELECT s.session_token, u.username, s.ip_address, s.expires_at, s.created_at FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.expires_at > datetime("now")',
        'Logs d\'authentification (10 derniers)' => 'SELECT username, action, ip_address, success, message, created_at FROM auth_logs ORDER BY created_at DESC LIMIT 10',
        'Configuration API' => 'SELECT service_name, has_key, is_active, updated_at FROM (SELECT service_name, CASE WHEN api_key IS NOT NULL THEN 1 ELSE 0 END as has_key, is_active, updated_at FROM api_configs)',
        'Paramètres système' => 'SELECT setting_key, setting_value, description, updated_at FROM settings',
        'Structure de la base' => 'SELECT name, type, sql FROM sqlite_master WHERE type IN ("table", "index") ORDER BY type, name'
    ],
    'susdr360' => [
        'Tables disponibles' => 'SELECT name FROM sqlite_master WHERE type="table"',
        'Structure des tables' => 'SELECT name, sql FROM sqlite_master WHERE type="table"',
        'Informations de la base' => 'PRAGMA database_list',
        'Statistiques' => 'SELECT name, COUNT(*) as count FROM sqlite_master WHERE type="table" GROUP BY name'
    ]
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface SQLite - SIEM</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .db-selector {
            margin-bottom: 20px;
        }
        .db-selector select {
            padding: 8px 12px;
            border: 1px solid #4a5568;
            border-radius: 4px;
            background: #2d3748;
            color: #fff;
            font-size: 14px;
        }
        .query-section {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .query-textarea {
            width: 100%;
            height: 150px;
            padding: 12px;
            border: 1px solid #4a5568;
            border-radius: 4px;
            background: #1a202c;
            color: #fff;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            resize: vertical;
        }
        .btn {
            background: #3182ce;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }
        .btn:hover {
            background: #2c5282;
        }
        .btn-secondary {
            background: #718096;
        }
        .btn-secondary:hover {
            background: #4a5568;
        }
        .predefined-queries {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .query-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .results-section {
            background: #2d3748;
            padding: 20px;
            border-radius: 8px;
        }
        .error {
            background: #fed7d7;
            color: #c53030;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background: #c6f6d5;
            color: #22543d;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #4a5568;
        }
        th {
            background: #4a5568;
            font-weight: bold;
        }
        tr:hover {
            background: #4a5568;
        }
        .no-results {
            text-align: center;
            color: #a0aec0;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ Interface SQLite - SIEM</h1>
            <p>Exécutez des requêtes sur les bases de données du système SIEM</p>
        </div>

        <form method="GET">
            <div class="db-selector">
                <label for="db">Base de données :</label>
                <select name="db" id="db" onchange="this.form.submit()">
                    <option value="config" <?= $selectedDb === 'config' ? 'selected' : '' ?>>Configuration (siem_config.db)</option>
                    <option value="susdr360" <?= $selectedDb === 'susdr360' ? 'selected' : '' ?>>SUSDR 360 (susdr360.db)</option>
                </select>
            </div>
        </form>

        <div class="predefined-queries">
            <h3>Requêtes prédéfinies</h3>
            <div class="query-buttons">
                <?php foreach ($predefinedQueries[$selectedDb] as $name => $sql): ?>
                    <button class="btn btn-secondary" onclick="setQuery('<?= htmlspecialchars($sql, ENT_QUOTES) ?>')"><?= htmlspecialchars($name) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="db" value="<?= htmlspecialchars($selectedDb) ?>">
            
            <div class="query-section">
                <h3>Requête SQL</h3>
                <textarea name="query" class="query-textarea" placeholder="Entrez votre requête SQL ici..."><?= htmlspecialchars($query) ?></textarea>
                <br>
                <button type="submit" class="btn">Exécuter la requête</button>
                <button type="button" class="btn btn-secondary" onclick="clearQuery()">Effacer</button>
            </div>
        </form>

        <?php if ($error): ?>
            <div class="error">
                <strong>Erreur :</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($results !== [] && !$error): ?>
            <div class="results-section">
                <h3>Résultats (<?= count($results) ?> ligne<?= count($results) > 1 ? 's' : '' ?>)</h3>
                
                <?php if (empty($results)): ?>
                    <div class="no-results">Aucun résultat trouvé</div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($results[0]) as $column): ?>
                                        <th><?= htmlspecialchars($column) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function setQuery(sql) {
            document.querySelector('textarea[name="query"]').value = sql;
        }
        
        function clearQuery() {
            document.querySelector('textarea[name="query"]').value = '';
        }
    </script>
</body>
</html>
