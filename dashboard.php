<?php
$pageTitle = "Dashboard - Vue d'ensemble";
require_once 'includes/header.php';
require_once 'config/database.php';

// Initialiser la base de données
$db = new ConfigDatabase();

// Récupérer les statistiques
$stats = $db->getStats();
$authLogs = $db->getAuthLogs(5); // Derniers 5 logs
?>

<div class="flex gap-6">
    <!-- Menu latéral du dashboard -->
    <div class="w-64 flex-shrink-0">
        <div class="card rounded-xl p-4">
            <nav class="space-y-2">
                <a href="attack-map.php" class="flex items-center px-4 py-3 text-white rounded-lg bg-blue-600 hover:bg-blue-700 transition-colors">
                    <i class="fas fa-globe-americas mr-3"></i>
                    Attack Map
                </a>
                
                <a href="threat-intel.php" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-shield-alt mr-3"></i>
                    Threat Intel
                </a>
                
                <a href="alerts.php" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-exclamation-triangle mr-3"></i>
                    Alerts
                </a>
                
                <a href="osint-analysis.php" class="flex items-center px-4 py-3 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-search mr-3"></i>
                    OSINT Analysis
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="flex-1">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Carte des menaces actives -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Menaces Actives</p>
                <p class="text-white text-2xl font-bold" id="activeThreats">0</p>
            </div>
            <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center text-sm">
                <span class="text-red-400">+12%</span>
                <span class="text-gray-400 ml-2">vs hier</span>
            </div>
        </div>
    </div>
    
    <!-- Carte des événements -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Événements</p>
                <p class="text-white text-2xl font-bold" id="totalEvents">0</p>
            </div>
            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-blue-400 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center text-sm">
                <span class="text-green-400">+5%</span>
                <span class="text-gray-400 ml-2">vs hier</span>
            </div>
        </div>
    </div>
    
    <!-- Carte des APIs configurées -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">APIs Actives</p>
                <p class="text-white text-2xl font-bold"><?php echo $stats['active_apis'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-plug text-green-400 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center text-sm">
                <span class="text-green-400">Opérationnel</span>
            </div>
        </div>
    </div>
    
    <!-- Carte du statut système -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Statut Système</p>
                <p class="text-white text-2xl font-bold">100%</p>
            </div>
            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-shield-alt text-green-400 text-xl"></i>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center text-sm">
                <span class="text-green-400">Tous services OK</span>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Graphique des menaces -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-white text-lg font-semibold">Évolution des Menaces</h3>
            <div class="flex space-x-2">
                <button class="px-3 py-1 bg-blue-600 text-white text-sm rounded-md">24h</button>
                <button class="px-3 py-1 bg-gray-600 text-white text-sm rounded-md">7j</button>
                <button class="px-3 py-1 bg-gray-600 text-white text-sm rounded-md">30j</button>
            </div>
        </div>
        <div id="threatsChart" class="h-64"></div>
    </div>
    
    <!-- Carte du monde des attaques -->
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-white text-lg font-semibold">Attaques Géographiques</h3>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                <span class="text-red-400 text-sm">Temps réel</span>
            </div>
        </div>
        <div id="worldMap" class="h-64 bg-slate-700 rounded-lg flex items-center justify-center">
            <p class="text-gray-400">Carte des attaques en cours de chargement...</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Dernières menaces -->
    <div class="card rounded-xl p-6">
        <h3 class="text-white text-lg font-semibold mb-4">Dernières Menaces</h3>
        <div class="space-y-4" id="latestThreats">
            <!-- Les menaces seront chargées via JavaScript -->
            <div class="flex items-center justify-center py-8">
                <i class="fas fa-spinner fa-spin text-gray-400 mr-2"></i>
                <span class="text-gray-400">Chargement...</span>
            </div>
        </div>
    </div>
    
    <!-- Top pays sources -->
    <div class="card rounded-xl p-6">
        <h3 class="text-white text-lg font-semibold mb-4">Top Pays Sources</h3>
        <div class="space-y-3" id="topCountries">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="w-6 h-4 bg-red-500 rounded-sm mr-3"></span>
                    <span class="text-white">Chine</span>
                </div>
                <span class="text-gray-400">1,234</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="w-6 h-4 bg-orange-500 rounded-sm mr-3"></span>
                    <span class="text-white">Russie</span>
                </div>
                <span class="text-gray-400">987</span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <span class="w-6 h-4 bg-yellow-500 rounded-sm mr-3"></span>
                    <span class="text-white">USA</span>
                </div>
                <span class="text-gray-400">654</span>
            </div>
        </div>
    </div>
    
    <!-- Logs d'authentification récents -->
    <div class="card rounded-xl p-6">
        <h3 class="text-white text-lg font-semibold mb-4">Logs Récents</h3>
        <div class="space-y-3">
            <?php foreach ($authLogs as $log): ?>
            <div class="flex items-center justify-between py-2 border-b border-gray-600 last:border-b-0">
                <div class="flex items-center">
                    <div class="w-2 h-2 <?php echo $log['success'] ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-3"></div>
                    <div>
                        <p class="text-white text-sm"><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></p>
                        <p class="text-gray-400 text-xs"><?php echo htmlspecialchars($log['action']); ?></p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 text-xs"><?php echo date('H:i', strtotime($log['created_at'])); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($authLogs)): ?>
            <div class="text-center py-4">
                <p class="text-gray-400 text-sm">Aucun log récent</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    </div>
</div>

<!-- Scripts spécifiques au dashboard -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le dashboard
    initDashboard();
    
    // Mettre à jour les données toutes les 30 secondes
    setInterval(updateDashboardData, 30000);
});

function initDashboard() {
    // Charger les données initiales
    updateDashboardData();
    
    // Initialiser les graphiques
    initThreatsChart();
    initWorldMap();
}

async function updateDashboardData() {
    try {
        // Simuler des données pour la démo
        updateCounters();
        updateLatestThreats();
    } catch (error) {
        console.error('Erreur lors de la mise à jour du dashboard:', error);
    }
}

function updateCounters() {
    // Simuler des données en temps réel
    const activeThreats = Math.floor(Math.random() * 50) + 10;
    const totalEvents = Math.floor(Math.random() * 10000) + 5000;
    
    document.getElementById('activeThreats').textContent = activeThreats;
    document.getElementById('totalEvents').textContent = totalEvents.toLocaleString();
}

function updateLatestThreats() {
    const threats = [
        { type: 'Malware', ip: '192.168.1.100', country: 'CN', severity: 'high', time: '2 min' },
        { type: 'Brute Force', ip: '10.0.0.50', country: 'RU', severity: 'medium', time: '5 min' },
        { type: 'DDoS', ip: '172.16.0.25', country: 'US', severity: 'high', time: '8 min' },
        { type: 'Phishing', ip: '203.0.113.10', country: 'KR', severity: 'low', time: '12 min' }
    ];
    
    const container = document.getElementById('latestThreats');
    container.innerHTML = threats.map(threat => `
        <div class="flex items-center justify-between py-2 border-b border-gray-600 last:border-b-0">
            <div class="flex items-center">
                <div class="w-2 h-2 ${threat.severity === 'high' ? 'bg-red-500' : threat.severity === 'medium' ? 'bg-yellow-500' : 'bg-green-500'} rounded-full mr-3"></div>
                <div>
                    <p class="text-white text-sm">${threat.type}</p>
                    <p class="text-gray-400 text-xs">${threat.ip} (${threat.country})</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-gray-400 text-xs">il y a ${threat.time}</p>
            </div>
        </div>
    `).join('');
}

function initThreatsChart() {
    // Simuler un graphique simple avec D3.js
    const container = document.getElementById('threatsChart');
    
    // Données simulées
    const data = Array.from({length: 24}, (_, i) => ({
        hour: i,
        threats: Math.floor(Math.random() * 100) + 20
    }));
    
    // Configuration du graphique
    const margin = {top: 20, right: 30, bottom: 40, left: 40};
    const width = container.offsetWidth - margin.left - margin.right;
    const height = 256 - margin.top - margin.bottom;
    
    // Créer le SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width + margin.left + margin.right)
        .attr('height', height + margin.top + margin.bottom);
    
    const g = svg.append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);
    
    // Échelles
    const xScale = d3.scaleLinear()
        .domain([0, 23])
        .range([0, width]);
    
    const yScale = d3.scaleLinear()
        .domain([0, d3.max(data, d => d.threats)])
        .range([height, 0]);
    
    // Ligne
    const line = d3.line()
        .x(d => xScale(d.hour))
        .y(d => yScale(d.threats))
        .curve(d3.curveMonotoneX);
    
    // Ajouter la ligne
    g.append('path')
        .datum(data)
        .attr('fill', 'none')
        .attr('stroke', '#3b82f6')
        .attr('stroke-width', 2)
        .attr('d', line);
    
    // Ajouter les axes
    g.append('g')
        .attr('transform', `translate(0,${height})`)
        .call(d3.axisBottom(xScale).tickFormat(d => d + 'h'))
        .selectAll('text')
        .style('fill', '#9ca3af');
    
    g.append('g')
        .call(d3.axisLeft(yScale))
        .selectAll('text')
        .style('fill', '#9ca3af');
}

function initWorldMap() {
    const container = document.getElementById('worldMap');
    container.innerHTML = `
        <div class="text-center">
            <i class="fas fa-globe text-4xl text-blue-400 mb-4"></i>
            <p class="text-white">Carte interactive des attaques</p>
            <p class="text-gray-400 text-sm">Intégration en cours...</p>
        </div>
    `;
}
</script>

<?php require_once 'includes/footer.php'; ?>
