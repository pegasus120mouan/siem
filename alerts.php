<?php
$pageTitle = "Alertes de Sécurité";
require_once 'includes/header.php';

$apiBaseUrl = getenv('SUSDR360_API_URL') ?: 'http://127.0.0.1:8000';
$apiBaseUrl = rtrim($apiBaseUrl, '/');

$alerts = [];
$alertsError = null;

try {
    $url = $apiBaseUrl . '/api/v1/detections/alerts?limit=200';
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET',
            'header' => [
                'Content-Type: application/json'
            ]
        ]
    ]);
    $json = @file_get_contents($url, false, $ctx);
    if ($json === false) {
        $alertsError = "Impossible de joindre l'API detections";
    } else {
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            $alerts = $decoded;
        }
    }
} catch (Throwable $e) {
    $alertsError = $e->getMessage();
}

$criticalCount = 0;
$highCount = 0;
foreach ($alerts as $a) {
    $sev = strtolower($a['severity'] ?? '');
    if ($sev === 'critical') $criticalCount++;
    if ($sev === 'high') $highCount++;
}
$openCount = count($alerts);
$resolvedToday = 0;

function severityLabel($severity) {
    $s = strtolower($severity ?? '');
    if ($s === 'critical') return ['Critique', 'bg-red-100 text-red-800', 'bg-red-500'];
    if ($s === 'high') return ['Élevée', 'bg-orange-100 text-orange-800', 'bg-orange-500'];
    if ($s === 'medium') return ['Moyenne', 'bg-yellow-100 text-yellow-800', 'bg-yellow-500'];
    return ['Faible', 'bg-green-100 text-green-800', 'bg-green-500'];
}

function ruleTitle($ruleId, $hostname, $srcIp) {
    $rid = strtolower($ruleId ?? '');
    if ($rid === 'ssh_bruteforce_ip') return "Brute force SSH détecté";
    if ($rid === 'ssh_success_after_fail') return "Succès SSH après échecs";
    if ($rid === 'sudo_risky_command') return "Commande sudo à risque";
    if ($rid) return "Alerte: $ruleId";
    return "Alerte";
}

function ruleCategory($ruleId) {
    $rid = strtolower($ruleId ?? '');
    if (str_starts_with($rid, 'ssh_')) return 'intrusion';
    if (str_starts_with($rid, 'sudo_')) return 'intrusion';
    return 'intrusion';
}
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
                    <p class="text-white text-2xl font-bold text-red-400"><?php echo $criticalCount; ?></p>
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
                    <p class="text-white text-2xl font-bold text-orange-400"><?php echo $highCount; ?></p>
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
                    <p class="text-white text-2xl font-bold"><?php echo $openCount; ?></p>
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
                    <p class="text-white text-2xl font-bold text-green-400"><?php echo $resolvedToday; ?></p>
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
            <?php if ($alertsError): ?>
                <div class="p-6">
                    <p class="text-red-400 text-sm"><?php echo htmlspecialchars($alertsError); ?></p>
                </div>
            <?php endif; ?>

            <?php if (count($alerts) === 0): ?>
                <div class="p-6">
                    <p class="text-gray-400 text-sm">Aucune alerte disponible.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($alerts as $a): ?>
                <?php
                    $id = (int)($a['id'] ?? 0);
                    $ruleId = $a['rule_id'] ?? '';
                    $severity = $a['severity'] ?? 'low';
                    $hostname = $a['hostname'] ?? '';
                    $srcIp = $a['src_ip'] ?? '';
                    $username = $a['username'] ?? '';
                    $createdAt = $a['created_at'] ?? '';
                    $evidence = $a['evidence'] ?? [];
                    $desc = '';
                    if (is_array($evidence) && count($evidence) > 0) {
                        $first = $evidence[0];
                        $desc = is_array($first) ? (string)($first['message'] ?? '') : '';
                    }
                    $title = ruleTitle($ruleId, $hostname, $srcIp);
                    $category = ruleCategory($ruleId);
                    [$sevLabel, $sevBadge, $sevDot] = severityLabel($severity);
                ?>

                <div class="p-6 hover:bg-gray-700 transition-colors alert-item" data-severity="<?php echo htmlspecialchars(strtolower($severity)); ?>" data-status="open" data-category="<?php echo htmlspecialchars($category); ?>">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4">
                            <div class="w-4 h-4 <?php echo htmlspecialchars($sevDot); ?> rounded-full mt-1 <?php echo strtolower($severity) === 'critical' ? 'animate-pulse' : ''; ?>"></div>
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="text-white font-semibold"><?php echo htmlspecialchars($title); ?></h3>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo htmlspecialchars($sevBadge); ?>"><?php echo htmlspecialchars($sevLabel); ?></span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Ouvert</span>
                                </div>
                                <?php if ($desc): ?>
                                    <p class="text-gray-400 text-sm mb-2"><?php echo htmlspecialchars($desc); ?></p>
                                <?php endif; ?>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($createdAt); ?></span>
                                    <?php if ($hostname): ?><span><i class="fas fa-server mr-1"></i><?php echo htmlspecialchars($hostname); ?></span><?php endif; ?>
                                    <?php if ($srcIp): ?><span><i class="fas fa-network-wired mr-1"></i><?php echo htmlspecialchars($srcIp); ?></span><?php endif; ?>
                                    <?php if ($username): ?><span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($username); ?></span><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="viewAlert(<?php echo $id; ?>)" class="text-blue-400 hover:text-blue-300 p-2" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="investigateAlert(<?php echo $id; ?>)" class="text-yellow-400 hover:text-yellow-300 p-2" title="Enquêter">
                                <i class="fas fa-search"></i>
                            </button>
                            <button onclick="resolveAlert(<?php echo $id; ?>)" class="text-green-400 hover:text-green-300 p-2" title="Résoudre">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="dismissAlert(<?php echo $id; ?>)" class="text-gray-400 hover:text-gray-300 p-2" title="Ignorer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
