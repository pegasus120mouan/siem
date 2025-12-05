<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIEM Dashboard avec SUSDR 360</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
        .widget { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .widget h3 { margin-top: 0; color: #2c3e50; }
        .status-bar { background: #34495e; color: white; padding: 10px; border-radius: 4px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #2980b9; }
        .btn.success { background: #27ae60; }
        .btn.danger { background: #e74c3c; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ SIEM Dashboard - Intégration SUSDR 360</h1>
            <p>Tableau de bord unifié avec le système SUSDR 360</p>
        </div>

        <?php
        // Inclusion de l'intégration SUSDR 360
        include 'susdr360_integration.php';
        
        // Vérification du statut
        $susdr360_online = susdr360_is_online();
        ?>

        <div class="status-bar">
            <div>
                <strong>Statut SUSDR 360:</strong> 
                <?php if ($susdr360_online): ?>
                    <span style="color: #2ecc71;">✅ OPÉRATIONNEL</span>
                <?php else: ?>
                    <span style="color: #e74c3c;">❌ HORS LIGNE</span>
                <?php endif; ?>
            </div>
            <div>
                <a href="http://localhost:8000/docs" target="_blank" class="btn">API Documentation</a>
                <a href="http://localhost:8000/web" target="_blank" class="btn success">Interface SUSDR 360</a>
                <?php if (!$susdr360_online): ?>
                    <a href="#" onclick="location.reload()" class="btn danger">Actualiser</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Widget Statistiques SUSDR 360 -->
            <div class="widget">
                <?php echo susdr360_stats(); ?>
            </div>

            <!-- Widget Incidents Récents -->
            <div class="widget">
                <?php echo susdr360_incidents(); ?>
            </div>

            <!-- Widget Top Menaces -->
            <div class="widget">
                <?php echo susdr360_threats(); ?>
            </div>

            <!-- Widget Informations Système -->
            <div class="widget">
                <h3>📊 Informations Système</h3>
                <?php if ($susdr360_online): ?>
                    <?php
                    $system_info = $susdr360->getSystemHealth();
                    if ($system_info):
                    ?>
                    <div class="system-info">
                        <p><strong>Statut:</strong> <?php echo ucfirst($system_info['status']); ?></p>
                        <p><strong>Uptime:</strong> <?php echo $system_info['uptime']; ?></p>
                        <p><strong>CPU:</strong> <?php echo $system_info['cpu_usage']; ?>%</p>
                        <p><strong>Mémoire:</strong> <?php echo $system_info['memory_usage']; ?>%</p>
                        <p><strong>Événements/sec:</strong> <?php echo $system_info['performance']['events_per_second']; ?></p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Système SUSDR 360 non accessible. Vérifiez que le service est démarré.</p>
                    <p><code>cd c:\laragon\www\siem\susdr360</code></p>
                    <p><code>python start_simple.py</code></p>
                <?php endif; ?>
            </div>

            <!-- Widget Actions Rapides -->
            <div class="widget">
                <h3>⚡ Actions Rapides</h3>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="http://localhost:8000/docs" target="_blank" class="btn">📚 Documentation API</a>
                    <a href="http://localhost:8000/web" target="_blank" class="btn">🖥️ Interface Web SUSDR 360</a>
                    <a href="http://localhost:8000/health" target="_blank" class="btn">❤️ Health Check</a>
                    <?php if ($susdr360_online): ?>
                        <button onclick="exportData('events')" class="btn success">📥 Exporter Événements</button>
                        <button onclick="exportData('incidents')" class="btn success">📥 Exporter Incidents</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget Votre SIEM Existant -->
            <div class="widget">
                <h3>🔍 SIEM Existant</h3>
                <p>Intégration avec votre système SIEM actuel</p>
                <div style="background: #ecf0f1; padding: 15px; border-radius: 4px;">
                    <p><strong>URL:</strong> <a href="http://siem.test/dashboard.php">siem.test/dashboard.php</a></p>
                    <p><strong>Statut:</strong> <span style="color: #27ae60;">Actif</span></p>
                    <p><strong>Intégration SUSDR 360:</strong> 
                        <?php echo $susdr360_online ? '<span style="color: #27ae60;">Connectée</span>' : '<span style="color: #e74c3c;">Déconnectée</span>'; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Script de mise à jour automatique -->
        <?php echo susdr360_auto_update_script(); ?>

        <script>
        // Fonction d'export
        function exportData(type) {
            const url = `http://localhost:8000/api/v1/dashboard/export/csv?data_type=${type}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    alert(`Export ${type} généré. Taille: ${data.file_size} bytes`);
                })
                .catch(error => {
                    alert('Erreur lors de l\'export: ' + error.message);
                });
        }

        // Notification toast
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 1000;
                padding: 15px 20px; border-radius: 4px; color: white;
                background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Vérification périodique de la connexion
        function checkConnection() {
            fetch('http://localhost:8000/health')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'healthy') {
                        console.log('SUSDR 360 connecté');
                    }
                })
                .catch(error => {
                    console.warn('SUSDR 360 déconnecté');
                });
        }

        // Vérifier la connexion toutes les minutes
        setInterval(checkConnection, 60000);
        checkConnection();

        // Message de bienvenue
        <?php if ($susdr360_online): ?>
        showToast('SUSDR 360 connecté avec succès!', 'success');
        <?php else: ?>
        showToast('SUSDR 360 hors ligne. Démarrez le service pour voir les données.', 'error');
        <?php endif; ?>
        </script>
    </div>
</body>
</html>
