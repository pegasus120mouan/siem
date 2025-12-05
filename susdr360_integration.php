<?php
/**
 * SUSDR 360 - Intégration PHP
 * Script d'intégration pour afficher les données SUSDR 360 dans le dashboard existant
 * 
 * Usage: include 'susdr360_integration.php'; dans votre dashboard.php
 */

class SUSDR360Integration {
    private $api_base_url;
    private $timeout;
    private $cache_duration;
    private $cache_dir;
    
    public function __construct($api_url = 'http://localhost:8000', $timeout = 10) {
        $this->api_base_url = rtrim($api_url, '/');
        $this->timeout = $timeout;
        $this->cache_duration = 300; // 5 minutes
        $this->cache_dir = __DIR__ . '/cache/susdr360/';
        
        // Créer le dossier de cache s'il n'existe pas
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Effectue un appel API avec gestion du cache
     */
    private function apiCall($endpoint, $use_cache = true) {
        $cache_file = $this->cache_dir . md5($endpoint) . '.json';
        
        // Vérifier le cache
        if ($use_cache && file_exists($cache_file)) {
            $cache_time = filemtime($cache_file);
            if (time() - $cache_time < $this->cache_duration) {
                $cached_data = file_get_contents($cache_file);
                return json_decode($cached_data, true);
            }
        }
        
        // Appel API
        $url = $this->api_base_url . $endpoint;
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'method' => 'GET',
                'header' => [
                    'Content-Type: application/json',
                    'User-Agent: SIEM-Dashboard/1.0'
                ]
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            error_log("SUSDR360: Erreur API call vers $url");
            return null;
        }
        
        $data = json_decode($response, true);
        
        // Sauvegarder en cache
        if ($use_cache && $data !== null) {
            file_put_contents($cache_file, $response);
        }
        
        return $data;
    }
    
    /**
     * Vérifie si SUSDR 360 est opérationnel
     */
    public function isOnline() {
        $health = $this->apiCall('/health', false);
        return $health !== null && isset($health['status']) && $health['status'] === 'healthy';
    }
    
    /**
     * Récupère les statistiques principales
     */
    public function getStats() {
        return $this->apiCall('/api/v1/dashboard/stats');
    }
    
    /**
     * Récupère les incidents récents
     */
    public function getRecentIncidents($limit = 10) {
        return $this->apiCall("/api/v1/dashboard/incidents?limit=$limit");
    }
    
    /**
     * Récupère les top menaces
     */
    public function getTopThreats($limit = 5) {
        return $this->apiCall("/api/v1/dashboard/threats?limit=$limit");
    }
    
    /**
     * Récupère la timeline des événements
     */
    public function getEventsTimeline($hours = 24) {
        return $this->apiCall("/api/v1/dashboard/timeline?hours=$hours");
    }
    
    /**
     * Récupère les statistiques réseau
     */
    public function getNetworkStats() {
        return $this->apiCall('/api/v1/dashboard/network');
    }
    
    /**
     * Récupère l'activité des utilisateurs
     */
    public function getUserActivity() {
        return $this->apiCall('/api/v1/dashboard/users');
    }
    
    /**
     * Récupère la santé du système
     */
    public function getSystemHealth() {
        return $this->apiCall('/api/v1/dashboard/system');
    }
    
    /**
     * Génère le HTML pour l'intégration dans le dashboard
     */
    public function renderDashboardWidget($widget_type = 'stats') {
        if (!$this->isOnline()) {
            return $this->renderOfflineWidget();
        }
        
        switch ($widget_type) {
            case 'stats':
                return $this->renderStatsWidget();
            case 'incidents':
                return $this->renderIncidentsWidget();
            case 'threats':
                return $this->renderThreatsWidget();
            case 'timeline':
                return $this->renderTimelineWidget();
            case 'network':
                return $this->renderNetworkWidget();
            case 'system':
                return $this->renderSystemWidget();
            default:
                return $this->renderStatsWidget();
        }
    }
    
    private function renderOfflineWidget() {
        return '
        <div class="susdr360-widget offline">
            <div class="widget-header">
                <h3>🛡️ SUSDR 360</h3>
                <span class="status offline">HORS LIGNE</span>
            </div>
            <div class="widget-content">
                <p>Le système SUSDR 360 n\'est pas accessible.</p>
                <p>Vérifiez que le service est démarré sur <code>http://localhost:8000</code></p>
            </div>
        </div>
        <style>
        .susdr360-widget { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; background: #fff; }
        .susdr360-widget.offline { border-color: #dc3545; background: #f8f9fa; }
        .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .status.offline { color: #dc3545; font-weight: bold; }
        </style>';
    }
    
    private function renderStatsWidget() {
        $stats = $this->getStats();
        if (!$stats) return $this->renderOfflineWidget();
        
        return '
        <div class="susdr360-widget stats">
            <div class="widget-header">
                <h3>🛡️ SUSDR 360 - Statistiques</h3>
                <span class="status online">EN LIGNE</span>
            </div>
            <div class="widget-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">' . number_format($stats['total_events_today']) . '</div>
                        <div class="stat-label">Événements aujourd\'hui</div>
                    </div>
                    <div class="stat-item critical">
                        <div class="stat-number">' . $stats['critical_alerts'] . '</div>
                        <div class="stat-label">Alertes critiques</div>
                    </div>
                    <div class="stat-item warning">
                        <div class="stat-number">' . $stats['high_alerts'] . '</div>
                        <div class="stat-label">Alertes importantes</div>
                    </div>
                    <div class="stat-item success">
                        <div class="stat-number">' . $stats['blocked_attacks'] . '</div>
                        <div class="stat-label">Attaques bloquées</div>
                    </div>
                </div>
                <div class="last-update">
                    Dernière mise à jour: ' . date('H:i:s', strtotime($stats['last_update'])) . '
                </div>
            </div>
        </div>
        <style>
        .susdr360-widget { border: 1px solid #28a745; border-radius: 8px; padding: 15px; margin: 10px 0; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .widget-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .widget-header h3 { margin: 0; color: #333; }
        .status.online { color: #28a745; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .stat-item { text-align: center; padding: 15px; border-radius: 6px; background: #f8f9fa; }
        .stat-item.critical { background: #f8d7da; border-left: 4px solid #dc3545; }
        .stat-item.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .stat-item.success { background: #d4edda; border-left: 4px solid #28a745; }
        .stat-number { font-size: 24px; font-weight: bold; color: #333; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .last-update { text-align: center; font-size: 11px; color: #999; }
        </style>';
    }
    
    private function renderIncidentsWidget() {
        $incidents_data = $this->getRecentIncidents(5);
        if (!$incidents_data) return $this->renderOfflineWidget();
        
        $incidents = $incidents_data['incidents'];
        $html = '
        <div class="susdr360-widget incidents">
            <div class="widget-header">
                <h3>🚨 Incidents Récents</h3>
                <span class="count">' . count($incidents) . ' incidents</span>
            </div>
            <div class="widget-content">
                <div class="incidents-list">';
        
        foreach ($incidents as $incident) {
            $severity_class = $incident['severity'];
            $time = date('H:i', strtotime($incident['timestamp']));
            $html .= '
                <div class="incident-item ' . $severity_class . '">
                    <div class="incident-time">' . $time . '</div>
                    <div class="incident-details">
                        <div class="incident-title">' . htmlspecialchars($incident['title']) . '</div>
                        <div class="incident-source">' . $incident['source'] . '</div>
                    </div>
                    <div class="incident-status ' . $incident['status'] . '">' . strtoupper($incident['status']) . '</div>
                </div>';
        }
        
        $html .= '
                </div>
            </div>
        </div>
        <style>
        .incidents-list { max-height: 300px; overflow-y: auto; }
        .incident-item { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; }
        .incident-item:last-child { border-bottom: none; }
        .incident-item.critical { border-left: 4px solid #dc3545; }
        .incident-item.high { border-left: 4px solid #fd7e14; }
        .incident-item.medium { border-left: 4px solid #ffc107; }
        .incident-item.low { border-left: 4px solid #6c757d; }
        .incident-time { font-size: 12px; color: #666; min-width: 50px; }
        .incident-details { flex: 1; margin: 0 10px; }
        .incident-title { font-weight: bold; font-size: 14px; }
        .incident-source { font-size: 12px; color: #666; }
        .incident-status { font-size: 10px; padding: 2px 6px; border-radius: 3px; }
        .incident-status.new { background: #dc3545; color: white; }
        .incident-status.investigating { background: #ffc107; color: black; }
        .incident-status.resolved { background: #28a745; color: white; }
        </style>';
        
        return $html;
    }
    
    private function renderThreatsWidget() {
        $threats_data = $this->getTopThreats();
        if (!$threats_data) return $this->renderOfflineWidget();
        
        $threats = $threats_data['threats'];
        $html = '
        <div class="susdr360-widget threats">
            <div class="widget-header">
                <h3>⚠️ Top Menaces</h3>
            </div>
            <div class="widget-content">
                <div class="threats-list">';
        
        foreach ($threats as $threat) {
            $html .= '
                <div class="threat-item ' . $threat['severity'] . '">
                    <div class="threat-name">' . htmlspecialchars($threat['name']) . '</div>
                    <div class="threat-count">' . $threat['count'] . '</div>
                </div>';
        }
        
        $html .= '
                </div>
            </div>
        </div>
        <style>
        .threats-list { }
        .threat-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; }
        .threat-item:last-child { border-bottom: none; }
        .threat-name { flex: 1; }
        .threat-count { font-weight: bold; padding: 4px 8px; border-radius: 4px; background: #f8f9fa; }
        .threat-item.critical .threat-count { background: #dc3545; color: white; }
        .threat-item.high .threat-count { background: #fd7e14; color: white; }
        .threat-item.medium .threat-count { background: #ffc107; color: black; }
        </style>';
        
        return $html;
    }
    
    /**
     * Génère le JavaScript pour les mises à jour automatiques
     */
    public function renderAutoUpdateScript() {
        return '
        <script>
        // Auto-refresh SUSDR 360 widgets
        function refreshSUSDR360Widgets() {
            // Recharger la page ou faire des appels AJAX
            location.reload();
        }
        
        // Refresh toutes les 5 minutes
        setInterval(refreshSUSDR360Widgets, 300000);
        
        // Indicateur de statut en temps réel
        function checkSUSDR360Status() {
            fetch("http://localhost:8000/health")
                .then(response => response.json())
                .then(data => {
                    const statusElements = document.querySelectorAll(".susdr360-widget .status");
                    statusElements.forEach(el => {
                        if (data.status === "healthy") {
                            el.textContent = "EN LIGNE";
                            el.className = "status online";
                        } else {
                            el.textContent = "PROBLÈME";
                            el.className = "status warning";
                        }
                    });
                })
                .catch(error => {
                    const statusElements = document.querySelectorAll(".susdr360-widget .status");
                    statusElements.forEach(el => {
                        el.textContent = "HORS LIGNE";
                        el.className = "status offline";
                    });
                });
        }
        
        // Vérifier le statut toutes les 30 secondes
        setInterval(checkSUSDR360Status, 30000);
        checkSUSDR360Status(); // Vérification initiale
        </script>';
    }
}

// Utilisation simple
$susdr360 = new SUSDR360Integration();

// Fonctions helper pour utilisation dans le dashboard
function susdr360_stats() {
    global $susdr360;
    return $susdr360->renderDashboardWidget('stats');
}

function susdr360_incidents() {
    global $susdr360;
    return $susdr360->renderDashboardWidget('incidents');
}

function susdr360_threats() {
    global $susdr360;
    return $susdr360->renderDashboardWidget('threats');
}

function susdr360_is_online() {
    global $susdr360;
    return $susdr360->isOnline();
}

function susdr360_auto_update_script() {
    global $susdr360;
    return $susdr360->renderAutoUpdateScript();
}
?>
