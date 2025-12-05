<?php
$pageTitle = "Alertes de Sécurité";
require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">Alertes de Sécurité</h1>
            <p class="text-gray-400">Gestion et analyse des alertes en temps réel</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="markAllRead()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-check-double mr-2"></i>Marquer tout lu
            </button>
            <button onclick="refreshAlerts()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualiser
            </button>
        </div>
    </div>
    
    <!-- Statistiques des alertes -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Alertes Critiques</p>
                    <p class="text-white text-2xl font-bold text-red-400">23</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Alertes Élevées</p>
                    <p class="text-white text-2xl font-bold text-orange-400">67</p>
                </div>
                <div class="w-12 h-12 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Non Résolues</p>
                    <p class="text-white text-2xl font-bold">142</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Résolues Aujourd'hui</p>
                    <p class="text-white text-2xl font-bold text-green-400">89</p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="card rounded-xl p-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex items-center space-x-2">
                <label class="text-white text-sm">Filtrer par :</label>
            </div>
            
            <select id="filterSeverity" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                <option value="">Toutes sévérités</option>
                <option value="critical">Critique</option>
                <option value="high">Élevée</option>
                <option value="medium">Moyenne</option>
                <option value="low">Faible</option>
            </select>
            
            <select id="filterStatus" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                <option value="">Tous statuts</option>
                <option value="open">Ouvert</option>
                <option value="investigating">En cours</option>
                <option value="resolved">Résolu</option>
                <option value="false_positive">Faux positif</option>
            </select>
            
            <select id="filterCategory" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                <option value="">Toutes catégories</option>
                <option value="malware">Malware</option>
                <option value="intrusion">Intrusion</option>
                <option value="data_breach">Fuite de données</option>
                <option value="ddos">DDoS</option>
                <option value="phishing">Phishing</option>
            </select>
            
            <input type="text" id="searchAlert" placeholder="Rechercher..." class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
            
            <button onclick="applyFilters()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-filter mr-2"></i>Filtrer
            </button>
        </div>
    </div>
    
    <!-- Liste des alertes -->
    <div class="card rounded-xl overflow-hidden">
        <div class="p-6 border-b border-gray-600">
            <h2 class="text-white text-lg font-semibold">Alertes Récentes</h2>
        </div>
        
        <div class="divide-y divide-gray-600" id="alertsList">
            <!-- Alerte critique -->
            <div class="p-6 hover:bg-gray-700 transition-colors alert-item" data-severity="critical" data-status="open" data-category="malware">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        <div class="w-4 h-4 bg-red-500 rounded-full mt-1 animate-pulse"></div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <h3 class="text-white font-semibold">Malware détecté sur srv-web-01</h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Critique</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Ouvert</span>
                            </div>
                            <p class="text-gray-400 text-sm mb-2">
                                Trojan.Win32.Generic détecté dans C:\Windows\Temp\malicious.exe
                            </p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span><i class="fas fa-clock mr-1"></i>Il y a 5 minutes</span>
                                <span><i class="fas fa-server mr-1"></i>192.168.1.10</span>
                                <span><i class="fas fa-tag mr-1"></i>Malware</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="viewAlert(1)" class="text-blue-400 hover:text-blue-300 p-2" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="investigateAlert(1)" class="text-yellow-400 hover:text-yellow-300 p-2" title="Enquêter">
                            <i class="fas fa-search"></i>
                        </button>
                        <button onclick="resolveAlert(1)" class="text-green-400 hover:text-green-300 p-2" title="Résoudre">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="dismissAlert(1)" class="text-gray-400 hover:text-gray-300 p-2" title="Ignorer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Alerte élevée -->
            <div class="p-6 hover:bg-gray-700 transition-colors alert-item" data-severity="high" data-status="investigating" data-category="intrusion">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        <div class="w-4 h-4 bg-orange-500 rounded-full mt-1"></div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <h3 class="text-white font-semibold">Tentative d'intrusion détectée</h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Élevée</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">En cours</span>
                            </div>
                            <p class="text-gray-400 text-sm mb-2">
                                Multiples tentatives de connexion SSH échouées depuis 185.220.101.42
                            </p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span><i class="fas fa-clock mr-1"></i>Il y a 15 minutes</span>
                                <span><i class="fas fa-server mr-1"></i>185.220.101.42</span>
                                <span><i class="fas fa-tag mr-1"></i>Intrusion</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="viewAlert(2)" class="text-blue-400 hover:text-blue-300 p-2" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="investigateAlert(2)" class="text-yellow-400 hover:text-yellow-300 p-2" title="Enquêter">
                            <i class="fas fa-search"></i>
                        </button>
                        <button onclick="resolveAlert(2)" class="text-green-400 hover:text-green-300 p-2" title="Résoudre">
                            <i class="fas fa-check"></i>
                        </button>
                        <button onclick="dismissAlert(2)" class="text-gray-400 hover:text-gray-300 p-2" title="Ignorer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Alerte moyenne -->
            <div class="p-6 hover:bg-gray-700 transition-colors alert-item" data-severity="medium" data-status="resolved" data-category="phishing">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-4">
                        <div class="w-4 h-4 bg-green-500 rounded-full mt-1"></div>
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <h3 class="text-white font-semibold">Email de phishing bloqué</h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Moyenne</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Résolu</span>
                            </div>
                            <p class="text-gray-400 text-sm mb-2">
                                Email suspect provenant de noreply@fake-bank.com bloqué automatiquement
                            </p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span><i class="fas fa-clock mr-1"></i>Il y a 1 heure</span>
                                <span><i class="fas fa-envelope mr-1"></i>user@company.com</span>
                                <span><i class="fas fa-tag mr-1"></i>Phishing</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="viewAlert(3)" class="text-blue-400 hover:text-blue-300 p-2" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </button>
                        <span class="text-green-400 p-2" title="Résolu">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Graphique des alertes -->
    <div class="card rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-white text-lg font-semibold">Tendance des Alertes (24h)</h2>
            <div class="flex space-x-2">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-gray-400 text-sm">Critique</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-orange-500 rounded-full mr-2"></div>
                    <span class="text-gray-400 text-sm">Élevée</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                    <span class="text-gray-400 text-sm">Moyenne</span>
                </div>
            </div>
        </div>
        <div id="alertsChart" class="h-64"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initAlertsChart();
});

function initAlertsChart() {
    // Simuler des données d'alertes par heure
    const data = Array.from({length: 24}, (_, i) => ({
        hour: i,
        critical: Math.floor(Math.random() * 5),
        high: Math.floor(Math.random() * 15),
        medium: Math.floor(Math.random() * 25)
    }));
    
    const container = document.getElementById('alertsChart');
    const margin = {top: 20, right: 30, bottom: 40, left: 40};
    const width = container.offsetWidth - margin.left - margin.right;
    const height = 256 - margin.top - margin.bottom;
    
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
        .domain([0, d3.max(data, d => d.critical + d.high + d.medium)])
        .range([height, 0]);
    
    // Lignes
    const line = d3.line()
        .x(d => xScale(d.hour))
        .curve(d3.curveMonotoneX);
    
    // Ligne critique
    g.append('path')
        .datum(data)
        .attr('fill', 'none')
        .attr('stroke', '#ef4444')
        .attr('stroke-width', 2)
        .attr('d', line.y(d => yScale(d.critical)));
    
    // Ligne élevée
    g.append('path')
        .datum(data)
        .attr('fill', 'none')
        .attr('stroke', '#f97316')
        .attr('stroke-width', 2)
        .attr('d', line.y(d => yScale(d.high)));
    
    // Ligne moyenne
    g.append('path')
        .datum(data)
        .attr('fill', 'none')
        .attr('stroke', '#eab308')
        .attr('stroke-width', 2)
        .attr('d', line.y(d => yScale(d.medium)));
    
    // Axes
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

function applyFilters() {
    const severity = document.getElementById('filterSeverity').value;
    const status = document.getElementById('filterStatus').value;
    const category = document.getElementById('filterCategory').value;
    const search = document.getElementById('searchAlert').value.toLowerCase();
    
    const alerts = document.querySelectorAll('.alert-item');
    
    alerts.forEach(alert => {
        const alertSeverity = alert.dataset.severity;
        const alertStatus = alert.dataset.status;
        const alertCategory = alert.dataset.category;
        const alertText = alert.textContent.toLowerCase();
        
        const severityMatch = !severity || alertSeverity === severity;
        const statusMatch = !status || alertStatus === status;
        const categoryMatch = !category || alertCategory === category;
        const searchMatch = !search || alertText.includes(search);
        
        alert.style.display = severityMatch && statusMatch && categoryMatch && searchMatch ? 'block' : 'none';
    });
}

function refreshAlerts() {
    showNotification('Actualisation des alertes...', 'info');
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function markAllRead() {
    showNotification('Toutes les alertes ont été marquées comme lues', 'success');
}

function viewAlert(id) {
    showNotification(`Affichage des détails de l'alerte ${id}`, 'info');
}

function investigateAlert(id) {
    showNotification(`Enquête lancée pour l'alerte ${id}`, 'warning');
}

function resolveAlert(id) {
    if (confirm('Êtes-vous sûr de vouloir résoudre cette alerte ?')) {
        showNotification(`Alerte ${id} résolue avec succès`, 'success');
    }
}

function dismissAlert(id) {
    if (confirm('Êtes-vous sûr de vouloir ignorer cette alerte ?')) {
        showNotification(`Alerte ${id} ignorée`, 'info');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
