<?php
// Script pour créer un utilisateur administrateur
require_once 'config/database.php';

echo "<h2>Création d'un utilisateur administrateur</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        echo "<p style='color: red;'>Tous les champs sont requis !</p>";
    } else {
        try {
            $db = new ConfigDatabase();
            
            if ($db->createUser($username, $email, $password, 'admin')) {
                echo "<p style='color: green;'>✓ Utilisateur administrateur créé avec succès !</p>";
                echo "<p><strong>Nom d'utilisateur :</strong> " . htmlspecialchars($username) . "</p>";
                echo "<p><strong>Email :</strong> " . htmlspecialchars($email) . "</p>";
                echo "<p>Vous pouvez maintenant vous connecter avec ces identifiants.</p>";
            } else {
                echo "<p style='color: red;'>✗ Erreur lors de la création de l'utilisateur (peut-être existe-t-il déjà ?)</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<form method="POST" style="max-width: 400px; margin: 20px 0;">
    <div style="margin-bottom: 15px;">
        <label for="username" style="display: block; margin-bottom: 5px;">Nom d'utilisateur :</label>
        <input type="text" id="username" name="username" required 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="email" style="display: block; margin-bottom: 5px;">Email :</label>
        <input type="email" id="email" name="email" required 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label for="password" style="display: block; margin-bottom: 5px;">Mot de passe :</label>
        <input type="password" id="password" name="password" required 
               style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    
    <button type="submit" 
            style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
        Créer l'utilisateur
    </button>
</form>

<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;">
    <h3>Utilisateur par défaut :</h3>
    <p>Si vous voulez utiliser l'utilisateur par défaut :</p>
    <ul>
        <li><strong>Nom d'utilisateur :</strong> admin</li>
        <li><strong>Email :</strong> admin@siem.local</li>
        <li><strong>Mot de passe :</strong> admin123</li>
    </ul>
</div>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
</style>
