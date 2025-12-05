<?php
$pageTitle = "Threat Intelligence";
require_once 'includes/header.php';
require_once 'config/database.php';

$db = new ConfigDatabase();
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">Threat Intelligence</h1>
            <p class="text-gray-400">Analyse et corrélation des indicateurs de compromission</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="refreshIntel()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualiser
            </button>
            <button onclick="openModal('addIOCModal')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Ajouter IOC
            </button>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">IOCs Actifs</p>
                    <p class="text-white text-2xl font-bold">2,847</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-crosshairs text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Feeds Actifs</p>
                    <p class="text-white text-2xl font-bold">12</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-rss text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Correspondances</p>
                    <p class="text-white text-2xl font-bold">156</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-search text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Score Risque</p>
                    <p class="text-white text-2xl font-bold text-red-400">Élevé</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recherche et filtres -->
    <div class="card rounded-xl p-6">
        <div class="flex flex-wrap gap-4 items-center">
            <div class="flex-1 min-w-64">
                <input type="text" id="searchIOC" placeholder="Rechercher un IOC (IP, hash, domaine...)" class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg border border-gray-600 focus:border-blue-500 focus:outline-none">
            </div>
            
            <select id="filterType" class="bg-gray-700 text-white px-3 py-2 rounded-md">
                <option value="">Tous les types</option>
                <option value="ip">Adresse IP</option>
                <option value="domain">Domaine</option>
                <option value="hash">Hash</option>
                <option value="url">URL</option>
            </select>
            
            <select id="filterSeverity" class="bg-gray-700 text-white px-3 py-2 rounded-md">
                <option value="">Toutes sévérités</option>
                <option value="critical">Critique</option>
                <option value="high">Élevée</option>
                <option value="medium">Moyenne</option>
                <option value="low">Faible</option>
            </select>
            
            <button onclick="searchIOCs()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-search mr-2"></i>Rechercher
            </button>
        </div>
    </div>
    
    <!-- Tableau des IOCs -->
    <div class="card rounded-xl overflow-hidden">
        <div class="p-6 border-b border-gray-600">
            <h2 class="text-white text-lg font-semibold">Indicateurs de Compromission</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">IOC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Sévérité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Source</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Première vue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Correspondances</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600" id="iocTableBody">
                    <!-- IOCs simulés -->
                    <tr class="hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-blue-400 bg-gray-800 px-2 py-1 rounded text-sm">185.220.101.42</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">IP</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Critique</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-white">VirusTotal</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">02/12/2024 18:30</td>
                        <td class="px-6 py-4 whitespace-nowrap text-white">23</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button class="text-blue-400 hover:text-blue-300 mr-3" onclick="viewIOC('185.220.101.42')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="text-red-400 hover:text-red-300" onclick="blockIOC('185.220.101.42')">
                                <i class="fas fa-ban"></i>
                            </button>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-blue-400 bg-gray-800 px-2 py-1 rounded text-sm">malicious-domain.com</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Domaine</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Élevée</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-white">AbuseIPDB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">02/12/2024 17:45</td>
                        <td class="px-6 py-4 whitespace-nowrap text-white">8</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button class="text-blue-400 hover:text-blue-300 mr-3" onclick="viewIOC('malicious-domain.com')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="text-red-400 hover:text-red-300" onclick="blockIOC('malicious-domain.com')">
                                <i class="fas fa-ban"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Feeds de Threat Intelligence -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card rounded-xl p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Feeds Actifs</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-white font-medium">VirusTotal</p>
                            <p class="text-gray-400 text-sm">Dernière sync: il y a 5 min</p>
                        </div>
                    </div>
                    <span class="text-green-400 text-sm">Actif</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-white font-medium">AbuseIPDB</p>
                            <p class="text-gray-400 text-sm">Dernière sync: il y a 12 min</p>
                        </div>
                    </div>
                    <span class="text-green-400 text-sm">Actif</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                        <div>
                            <p class="text-white font-medium">MISP</p>
                            <p class="text-gray-400 text-sm">Erreur de connexion</p>
                        </div>
                    </div>
                    <span class="text-red-400 text-sm">Erreur</span>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Activité Récente</h3>
            <div class="space-y-3" id="recentActivity">
                <!-- L'activité sera ajoutée dynamiquement -->
            </div>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'IOC -->
<div id="addIOCModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Ajouter un IOC</h3>
            <button onclick="closeModal('addIOCModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addIOCForm">
            <div class="space-y-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Type d'IOC</label>
                    <select name="type" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="ip">Adresse IP</option>
                        <option value="domain">Domaine</option>
                        <option value="hash">Hash</option>
                        <option value="url">URL</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Valeur</label>
                    <input type="text" name="value" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Sévérité</label>
                    <select name="severity" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="low">Faible</option>
                        <option value="medium">Moyenne</option>
                        <option value="high">Élevée</option>
                        <option value="critical">Critique</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('addIOCModal')" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Annuler
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-plus mr-2"></i>Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadRecentActivity();
    
    document.getElementById('addIOCForm').addEventListener('submit', function(e) {
        e.preventDefault();
        addIOC();
    });
});

function loadRecentActivity() {
    const activities = [
        { action: 'Nouvel IOC détecté', ioc: '192.168.1.100', time: 'il y a 2 min', type: 'detection' },
        { action: 'Feed synchronisé', source: 'VirusTotal', time: 'il y a 5 min', type: 'sync' },
        { action: 'IOC bloqué', ioc: 'malware.exe', time: 'il y a 8 min', type: 'block' },
        { action: 'Correspondance trouvée', ioc: 'evil-domain.com', time: 'il y a 12 min', type: 'match' }
    ];
    
    const container = document.getElementById('recentActivity');
    container.innerHTML = activities.map(activity => `
        <div class="flex items-center justify-between py-2 border-b border-gray-600 last:border-b-0">
            <div class="flex items-center">
                <div class="w-2 h-2 ${getActivityColor(activity.type)} rounded-full mr-3"></div>
                <div>
                    <p class="text-white text-sm">${activity.action}</p>
                    <p class="text-gray-400 text-xs">${activity.ioc || activity.source}</p>
                </div>
            </div>
            <span class="text-gray-400 text-xs">${activity.time}</span>
        </div>
    `).join('');
}

function getActivityColor(type) {
    const colors = {
        detection: 'bg-red-500',
        sync: 'bg-blue-500',
        block: 'bg-yellow-500',
        match: 'bg-green-500'
    };
    return colors[type] || 'bg-gray-500';
}

function refreshIntel() {
    showNotification('Actualisation des données de threat intelligence...', 'info');
    setTimeout(() => {
        loadRecentActivity();
        showNotification('Données actualisées avec succès', 'success');
    }, 2000);
}

function searchIOCs() {
    const searchTerm = document.getElementById('searchIOC').value;
    showNotification(`Recherche d'IOCs pour: ${searchTerm}`, 'info');
}

function viewIOC(ioc) {
    showNotification(`Affichage des détails pour: ${ioc}`, 'info');
}

function blockIOC(ioc) {
    if (confirm(`Êtes-vous sûr de vouloir bloquer ${ioc} ?`)) {
        showNotification(`IOC ${ioc} bloqué avec succès`, 'success');
    }
}

function addIOC() {
    const formData = new FormData(document.getElementById('addIOCForm'));
    const ioc = formData.get('value');
    
    showNotification(`IOC ${ioc} ajouté avec succès`, 'success');
    closeModal('addIOCModal');
    document.getElementById('addIOCForm').reset();
}
</script>

<?php require_once 'includes/footer.php'; ?>
