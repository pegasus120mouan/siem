<?php
$pageTitle = "Logs et Audit";
require_once 'includes/header.php';
require_once 'config/database.php';

// Initialiser la base de données
$db = new ConfigDatabase();

// Récupérer les logs d'authentification
$limit = intval($_GET['limit'] ?? 50);
$authLogs = $db->getAuthLogs($limit);
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Logs et Audit</h1>
        <div class="flex space-x-3">
            <select id="logLimit" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 entrées</option>
                <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 entrées</option>
                <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 entrées</option>
                <option value="200" <?php echo $limit == 200 ? 'selected' : ''; ?>>200 entrées</option>
            </select>
            <button onclick="exportLogs()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>Exporter
            </button>
            <button onclick="window.location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualiser
            </button>
        </div>
    </div>
    
    <!-- Statistiques des logs -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php
        $totalLogs = count($authLogs);
        $successfulLogins = count(array_filter($authLogs, fn($log) => $log['success'] && $log['action'] === 'login'));
        $failedLogins = count(array_filter($authLogs, fn($log) => !$log['success'] && $log['action'] === 'login'));
        $uniqueIPs = count(array_unique(array_column($authLogs, 'ip_address')));
        ?>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Logs</p>
                    <p class="text-white text-2xl font-bold"><?php echo $totalLogs; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-list-alt text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Connexions Réussies</p>
                    <p class="text-white text-2xl font-bold"><?php echo $successfulLogins; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Tentatives Échouées</p>
                    <p class="text-white text-2xl font-bold"><?php echo $failedLogins; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">IPs Uniques</p>
                    <p class="text-white text-2xl font-bold"><?php echo $uniqueIPs; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-network-wired text-purple-400 text-xl"></i>
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
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">Action :</label>
                <select id="filterAction" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                    <option value="">Toutes</option>
                    <option value="login">Connexion</option>
                    <option value="logout">Déconnexion</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">Statut :</label>
                <select id="filterStatus" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                    <option value="">Tous</option>
                    <option value="1">Succès</option>
                    <option value="0">Échec</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">Utilisateur :</label>
                <input type="text" id="filterUser" placeholder="Nom d'utilisateur..." class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">IP :</label>
                <input type="text" id="filterIP" placeholder="Adresse IP..." class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
            </div>
            
            <button onclick="clearFilters()" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-md text-sm transition-colors">
                <i class="fas fa-times mr-1"></i>Effacer
            </button>
        </div>
    </div>
    
    <!-- Tableau des logs -->
    <div class="card rounded-xl overflow-hidden">
        <div class="p-6 border-b border-gray-600">
            <h2 class="text-white text-lg font-semibold">Logs d'Authentification</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Horodatage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Utilisateur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Adresse IP</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">User Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600" id="logsTableBody">
                    <?php foreach ($authLogs as $log): ?>
                    <tr class="hover:bg-gray-700 transition-colors log-row" 
                        data-action="<?php echo htmlspecialchars($log['action']); ?>"
                        data-success="<?php echo $log['success']; ?>"
                        data-username="<?php echo htmlspecialchars($log['username'] ?? ''); ?>"
                        data-ip="<?php echo htmlspecialchars($log['ip_address'] ?? ''); ?>">
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white text-sm">
                            <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-user text-white text-xs"></i>
                                </div>
                                <span class="text-white"><?php echo htmlspecialchars($log['username'] ?? 'N/A'); ?></span>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $log['action'] === 'login' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($log['action']); ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-2 h-2 <?php echo $log['success'] ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-2"></div>
                                <span class="text-white text-sm"><?php echo $log['success'] ? 'Succès' : 'Échec'; ?></span>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white text-sm">
                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                        </td>
                        
                        <td class="px-6 py-4 text-gray-400 text-sm max-w-xs truncate" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                            <?php echo htmlspecialchars(substr($log['user_agent'] ?? 'N/A', 0, 50)); ?>
                            <?php if (strlen($log['user_agent'] ?? '') > 50): ?>...<?php endif; ?>
                        </td>
                        
                        <td class="px-6 py-4 text-gray-400 text-sm">
                            <?php echo htmlspecialchars($log['message'] ?? ''); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (empty($authLogs)): ?>
            <div class="text-center py-8">
                <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-400">Aucun log disponible</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Graphique des activités -->
    <div class="card rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-white text-lg font-semibold">Activité des Connexions (24h)</h2>
            <div class="flex space-x-2">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                    <span class="text-gray-400 text-sm">Succès</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                    <span class="text-gray-400 text-sm">Échecs</span>
                </div>
            </div>
        </div>
        <div id="activityChart" class="h-64"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initActivityChart();
    
    // Gestion du changement de limite
    document.getElementById('logLimit').addEventListener('change', function() {
        const limit = this.value;
        window.location.href = `logs.php?limit=${limit}`;
    });
});

function initFilters() {
    const filterAction = document.getElementById('filterAction');
    const filterStatus = document.getElementById('filterStatus');
    const filterUser = document.getElementById('filterUser');
    const filterIP = document.getElementById('filterIP');
    
    function applyFilters() {
        const rows = document.querySelectorAll('.log-row');
        
        rows.forEach(row => {
            const action = row.dataset.action;
            const success = row.dataset.success;
            const username = row.dataset.username.toLowerCase();
            const ip = row.dataset.ip;
            
            const actionMatch = !filterAction.value || action === filterAction.value;
            const statusMatch = !filterStatus.value || success === filterStatus.value;
            const userMatch = !filterUser.value || username.includes(filterUser.value.toLowerCase());
            const ipMatch = !filterIP.value || ip.includes(filterIP.value);
            
            row.style.display = actionMatch && statusMatch && userMatch && ipMatch ? '' : 'none';
        });
    }
    
    filterAction.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterUser.addEventListener('input', applyFilters);
    filterIP.addEventListener('input', applyFilters);
}

function clearFilters() {
    document.getElementById('filterAction').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterUser').value = '';
    document.getElementById('filterIP').value = '';
    
    // Afficher toutes les lignes
    document.querySelectorAll('.log-row').forEach(row => {
        row.style.display = '';
    });
}

function initActivityChart() {
    // Simuler des données d'activité par heure
    const data = Array.from({length: 24}, (_, i) => ({
        hour: i,
        success: Math.floor(Math.random() * 20),
        failed: Math.floor(Math.random() * 10)
    }));
    
    const container = document.getElementById('activityChart');
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
    const xScale = d3.scaleBand()
        .domain(data.map(d => d.hour))
        .range([0, width])
        .padding(0.1);
    
    const yScale = d3.scaleLinear()
        .domain([0, d3.max(data, d => Math.max(d.success, d.failed))])
        .range([height, 0]);
    
    // Barres de succès
    g.selectAll('.bar-success')
        .data(data)
        .enter().append('rect')
        .attr('class', 'bar-success')
        .attr('x', d => xScale(d.hour))
        .attr('y', d => yScale(d.success))
        .attr('width', xScale.bandwidth() / 2)
        .attr('height', d => height - yScale(d.success))
        .attr('fill', '#10b981');
    
    // Barres d'échecs
    g.selectAll('.bar-failed')
        .data(data)
        .enter().append('rect')
        .attr('class', 'bar-failed')
        .attr('x', d => xScale(d.hour) + xScale.bandwidth() / 2)
        .attr('y', d => yScale(d.failed))
        .attr('width', xScale.bandwidth() / 2)
        .attr('height', d => height - yScale(d.failed))
        .attr('fill', '#ef4444');
    
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

function exportLogs() {
    // Récupérer les logs visibles
    const visibleRows = Array.from(document.querySelectorAll('.log-row')).filter(row => row.style.display !== 'none');
    
    if (visibleRows.length === 0) {
        showNotification('Aucun log à exporter', 'warning');
        return;
    }
    
    // Créer le contenu CSV
    const headers = ['Horodatage', 'Utilisateur', 'Action', 'Statut', 'Adresse IP', 'User Agent', 'Message'];
    let csvContent = headers.join(',') + '\n';
    
    visibleRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.textContent.trim();
            // Échapper les guillemets et virgules
            if (text.includes(',') || text.includes('"')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            return text;
        });
        csvContent += rowData.join(',') + '\n';
    });
    
    // Télécharger le fichier
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `siem_logs_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    showNotification(`${visibleRows.length} logs exportés avec succès`, 'success');
}
</script>

<?php require_once 'includes/footer.php'; ?>
