<?php
// Test rapide de l'intégration SUSDR 360
include 'susdr360_integration.php';

echo "<h1>Test d'Intégration SUSDR 360</h1>";

// Test de connexion
echo "<h2>1. Test de Connexion</h2>";
if (susdr360_is_online()) {
    echo "<p style='color: green;'>✅ SUSDR 360 est en ligne</p>";
} else {
    echo "<p style='color: red;'>❌ SUSDR 360 est hors ligne</p>";
}

// Test des statistiques
echo "<h2>2. Test des Statistiques</h2>";
echo susdr360_stats();

// Test des incidents
echo "<h2>3. Test des Incidents</h2>";
echo susdr360_incidents();

// Test des menaces
echo "<h2>4. Test des Menaces</h2>";
echo susdr360_threats();

echo "<h2>5. Script Auto-Update</h2>";
echo susdr360_auto_update_script();

echo "<p><a href='dashboard_susdr360_example.php'>Voir le Dashboard Complet</a></p>";
?>
