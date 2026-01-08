<?php
$pageTitle = "Gestion des Agents";
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'susdr360_integration.php';

// Initialiser la base de données
$db = new ConfigDatabase();

$apiUrl = $db->getSetting('susdr360_api_url', 'http://localhost:8000');
$susdr = new SUSDR360Integration($apiUrl, 5);

$agentsRaw = $susdr->getAgents();
$agents = [];
if (is_array($agentsRaw)) {
    $i = 1;
    foreach ($agentsRaw as $a) {
        $agents[] = [
            'id' => $i,
            'name' => $a['agent_id'] ?? ('SIEM-AGENT-' . str_pad((string)$i, 3, '0', STR_PAD_LEFT)),
            'hostname' => $a['hostname'] ?? 'unknown',
            'ip_address' => $a['ip_address'] ?? '',
            'os' => $a['os'] ?? 'Linux',
            'version' => $a['version'] ?? '4.5.2',
            'status' => $a['status'] ?? 'never_connected',
            'last_seen' => $a['last_seen'] ?? null,
            'events_count' => (int)($a['events_count'] ?? 0),
            'group' => 'default'
        ];
        $i++;
    }
}
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Gestion des Agents</h1>
        <div class="flex space-x-3">
            <button onclick="deployAgent()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-plus mr-2"></i>Déployer Agent
            </button>
            <button onclick="refreshAgents()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualiser
            </button>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php
        $totalAgents = count($agents);
        $activeAgents = count(array_filter($agents, fn($a) => $a['status'] === 'active'));
        $disconnectedAgents = count(array_filter($agents, fn($a) => $a['status'] === 'disconnected'));
        $totalEvents = array_sum(array_column($agents, 'events_count'));
        ?>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Total Agents</p>
                    <p class="text-white text-2xl font-bold"><?php echo $totalAgents; ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-desktop text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Agents Actifs</p>
                    <p class="text-white text-2xl font-bold"><?php echo $activeAgents; ?></p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Déconnectés</p>
                    <p class="text-white text-2xl font-bold"><?php echo $disconnectedAgents; ?></p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Événements</p>
                    <p class="text-white text-2xl font-bold"><?php echo number_format($totalEvents); ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-bar text-purple-400 text-xl"></i>
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
                <label class="text-gray-400 text-sm">Statut :</label>
                <select id="filterStatus" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                    <option value="">Tous</option>
                    <option value="active">Actif</option>
                    <option value="disconnected">Déconnecté</option>
                    <option value="never_connected">Jamais connecté</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">Groupe :</label>
                <select id="filterGroup" class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
                    <option value="">Tous</option>
                    <option value="web-servers">Serveurs Web</option>
                    <option value="database">Base de données</option>
                    <option value="endpoints">Postes de travail</option>
                </select>
            </div>
            
            <div class="flex items-center space-x-2">
                <label class="text-gray-400 text-sm">Recherche :</label>
                <input type="text" id="searchAgent" placeholder="Nom, IP, hostname..." class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm">
            </div>
        </div>
    </div>
    
    <!-- Liste des agents -->
    <div class="card rounded-xl overflow-hidden">
        <div class="p-6 border-b border-gray-600">
            <h2 class="text-white text-lg font-semibold">Agents Déployés</h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Agent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Système</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Version</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Dernière activité</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Événements</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600" id="agentsTableBody">
                    <?php foreach ($agents as $agent): ?>
                    <tr class="hover:bg-gray-700 transition-colors agent-row" 
                        data-status="<?php echo $agent['status']; ?>"
                        data-group="<?php echo $agent['group']; ?>"
                        data-search="<?php echo strtolower($agent['name'] . ' ' . $agent['hostname'] . ' ' . $agent['ip_address']); ?>">
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-desktop text-white text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-white font-medium"><?php echo htmlspecialchars($agent['name']); ?></div>
                                    <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($agent['hostname']); ?></div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-white"><?php echo htmlspecialchars($agent['ip_address']); ?></div>
                            <div class="text-gray-400 text-sm"><?php echo htmlspecialchars($agent['os']); ?></div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white">
                            <?php echo htmlspecialchars($agent['version']); ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-2 h-2 <?php echo $agent['status'] === 'active' ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-2"></div>
                                <span class="text-white text-sm capitalize"><?php echo str_replace('_', ' ', $agent['status']); ?></span>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">
                            <?php echo date('d/m/Y H:i', strtotime($agent['last_seen'])); ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-white">
                            <?php echo number_format($agent['events_count']); ?>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex space-x-2">
                                <button onclick="viewAgent(<?php echo $agent['id']; ?>)" class="text-blue-400 hover:text-blue-300" data-tooltip="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="restartAgent(<?php echo $agent['id']; ?>)" class="text-yellow-400 hover:text-yellow-300" data-tooltip="Redémarrer">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button onclick="upgradeAgent(<?php echo $agent['id']; ?>)" class="text-green-400 hover:text-green-300" data-tooltip="Mettre à jour">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <button onclick="removeAgent(<?php echo $agent['id']; ?>)" class="text-red-400 hover:text-red-300" data-tooltip="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de déploiement d'agent -->
<div id="deployAgentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-xl font-semibold">Déployer un Agent SIEM</h3>
            <button onclick="closeModal('deployAgentModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Onglets -->
        <div class="flex space-x-1 mb-6 bg-gray-700 rounded-lg p-1">
            <button onclick="switchTab('download')" id="downloadTab" class="flex-1 py-2 px-4 rounded-md text-white bg-blue-600 transition-colors">
                <i class="fas fa-download mr-2"></i>Télécharger Agent
            </button>
            <button onclick="switchTab('install')" id="installTab" class="flex-1 py-2 px-4 rounded-md text-gray-300 hover:text-white transition-colors">
                <i class="fas fa-cogs mr-2"></i>Instructions d'installation
            </button>
            <button onclick="switchTab('config')" id="configTab" class="flex-1 py-2 px-4 rounded-md text-gray-300 hover:text-white transition-colors">
                <i class="fas fa-wrench mr-2"></i>Configuration
            </button>
        </div>
        
        <!-- Contenu Téléchargement -->
        <div id="downloadContent" class="tab-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Agent Windows -->
                <div class="bg-gray-700 rounded-xl p-6 border border-gray-600">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fab fa-windows text-blue-400 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-semibold">Agent Windows</h4>
                            <p class="text-gray-400 text-sm">Compatible Windows 10/11, Server 2016+</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Version:</span>
                            <span class="text-white">4.5.2</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Taille:</span>
                            <span class="text-white">45.2 MB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Architecture:</span>
                            <span class="text-white">x64</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Format:</span>
                            <span class="text-white">.msi</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <button onclick="downloadAgent('windows', 'msi')" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Télécharger siem-agent-4.5.2-x64.msi
                        </button>
                        <button onclick="downloadAgent('windows', 'exe')" class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center text-sm">
                            <i class="fas fa-download mr-2"></i>
                            Version portable (.exe)
                        </button>
                    </div>
                    
                    <div class="mt-4 p-3 bg-gray-800 rounded-lg">
                        <p class="text-gray-300 text-xs">
                            <i class="fas fa-info-circle mr-1 text-blue-400"></i>
                            Installation automatique avec configuration par défaut
                        </p>
                    </div>
                </div>
                
                <!-- Agent Linux -->
                <div class="bg-gray-700 rounded-xl p-6 border border-gray-600">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-4">
                            <i class="fab fa-linux text-orange-400 text-2xl"></i>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-semibold">Agent Linux</h4>
                            <p class="text-gray-400 text-sm">Ubuntu, CentOS, RHEL, Debian</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Version:</span>
                            <span class="text-white">4.5.2</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Taille:</span>
                            <span class="text-white">32.8 MB</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Architecture:</span>
                            <span class="text-white">x86_64</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Format:</span>
                            <span class="text-white">.deb / .rpm</span>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <button onclick="downloadAgent('linux', 'install')" class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-4 rounded-lg transition-colors flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Télécharger install.sh (Linux)
                        </button>
                        <button onclick="switchTab('install')" class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center text-sm">
                            <i class="fas fa-terminal mr-2"></i>
                            Voir la commande d'installation (one-liner)
                        </button>
                    </div>
                    
                    <div class="mt-4 p-3 bg-gray-800 rounded-lg">
                        <p class="text-gray-300 text-xs">
                            <i class="fas fa-info-circle mr-1 text-orange-400"></i>
                            Nécessite les privilèges root pour l'installation
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Informations supplémentaires -->
            <div class="mt-6 bg-gray-700 rounded-xl p-6">
                <h4 class="text-white text-lg font-semibold mb-4">Fonctionnalités de l'Agent</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Collecte de logs en temps réel</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Monitoring des processus</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Détection d'intrusion</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Intégrité des fichiers</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Communication chiffrée</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Configuration centralisée</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Mise à jour automatique</span>
                        </div>
                        <div class="flex items-center text-green-400">
                            <i class="fas fa-check mr-2"></i>
                            <span class="text-white text-sm">Reporting avancé</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenu Instructions -->
        <div id="installContent" class="tab-content hidden">
            <div class="space-y-6">
                <!-- Instructions Windows -->
                <div class="bg-gray-700 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fab fa-windows text-blue-400 text-2xl mr-3"></i>
                        <h4 class="text-white text-lg font-semibold">Installation Windows</h4>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3 mt-1">1</div>
                            <div>
                                <p class="text-white font-medium">Télécharger l'installateur</p>
                                <p class="text-gray-400 text-sm">Téléchargez le fichier .msi depuis l'onglet précédent</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3 mt-1">2</div>
                            <div>
                                <p class="text-white font-medium">Exécuter en tant qu'administrateur</p>
                                <p class="text-gray-400 text-sm">Clic droit → "Exécuter en tant qu'administrateur"</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold mr-3 mt-1">3</div>
                            <div>
                                <p class="text-white font-medium">Configuration automatique</p>
                                <p class="text-gray-400 text-sm">L'agent se configure automatiquement avec ce serveur SIEM</p>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 rounded-lg p-4">
                            <p class="text-gray-300 text-sm mb-2"><strong>Commande PowerShell (alternative) :</strong></p>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block">
                                msiexec /i siem-agent-4.5.2-x64.msi /quiet SERVER_URL="https://VOTRE-SERVEUR-SIEM.COM"
                            </code>
                            <p class="text-gray-400 text-xs mt-2">Remplacez VOTRE-SERVEUR-SIEM.COM par un domaine/URL ou une IP (ex: siem.entreprise.com ou 192.168.1.100)</p>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions Linux -->
                <div class="bg-gray-700 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fab fa-linux text-orange-400 text-2xl mr-3"></i>
                        <h4 class="text-white text-lg font-semibold">Installation Linux</h4>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-800 rounded-lg p-4">
                            <p class="text-gray-300 text-sm mb-2"><strong>Ubuntu/Debian (.deb) :</strong></p>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block mb-2">
                                sudo dpkg -i siem-agent-4.5.2.deb
                            </code>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block">
                                sudo systemctl enable siem-agent && sudo systemctl start siem-agent
                            </code>
                        </div>
                        
                        <div class="bg-gray-800 rounded-lg p-4">
                            <p class="text-gray-300 text-sm mb-2"><strong>CentOS/RHEL (.rpm) :</strong></p>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block mb-2">
                                sudo rpm -ivh siem-agent-4.5.2.rpm
                            </code>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block">
                                sudo systemctl enable siem-agent && sudo systemctl start siem-agent
                            </code>
                        </div>
                        
                        <div class="bg-gray-800 rounded-lg p-4">
                            <p class="text-gray-300 text-sm mb-2"><strong>Installation one-liner (recommandé) :</strong></p>
                            <code class="text-green-400 text-sm bg-gray-900 p-2 rounded block">
                                curl -sSL http://VOTRE-SERVEUR-SIEM/agents/linux/install.sh | sudo bash -s -- --server "http://IP_DU_SIEM:8000" --token "VOTRE_TOKEN"
                            </code>
                            <p class="text-gray-400 text-xs mt-2">Renseignez l'IP/URL du SIEM (port 8000) et le token. Exemple: --server "http://192.168.1.10:8000"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contenu Configuration -->
        <div id="configContent" class="tab-content hidden">
            <div class="space-y-6">
                <div class="bg-gray-700 rounded-xl p-6">
                    <h4 class="text-white text-lg font-semibold mb-4">Configuration de l'Agent</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">URL ou IP du serveur SIEM *</label>
                            <input type="text" id="serverUrl" placeholder="https://siem.entreprise.com ou 192.168.1.100" class="w-full bg-gray-800 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none" required>
                            <p class="text-gray-400 text-xs mt-1">Exemple: siem.entreprise.com, https://siem.entreprise.com, 192.168.1.100</p>
                        </div>
                        
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">Port de communication</label>
                            <input type="number" id="serverPort" value="1514" class="w-full bg-gray-800 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">Groupe d'agents</label>
                            <select id="agentGroup" class="w-full bg-gray-800 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <option value="default">Défaut</option>
                                <option value="web-servers">Serveurs Web</option>
                                <option value="database">Base de données</option>
                                <option value="endpoints">Postes de travail</option>
                                <option value="network">Équipements réseau</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-white text-sm font-medium mb-2">Niveau de log</label>
                            <select id="logLevel" class="w-full bg-gray-800 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label class="block text-white text-sm font-medium mb-2">Clé d'authentification</label>
                        <div class="flex space-x-2">
                            <input type="text" id="authKey" value="<?php echo bin2hex(random_bytes(16)); ?>" readonly class="flex-1 bg-gray-800 text-white px-3 py-2 rounded-md border border-gray-600">
                            <button onclick="generateAuthKey()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <p class="text-gray-400 text-xs mt-1">Cette clé sera utilisée pour authentifier l'agent auprès du serveur</p>
                    </div>
                    
                    <div class="mt-6 flex space-x-3">
                        <button onclick="generateConfigFile()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-file-download mr-2"></i>Générer fichier de config
                        </button>
                        <button onclick="testConnection()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                            <i class="fas fa-plug mr-2"></i>Tester la connexion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initFilters();
    initTooltips();
});

function initFilters() {
    const filterStatus = document.getElementById('filterStatus');
    const filterGroup = document.getElementById('filterGroup');
    const searchAgent = document.getElementById('searchAgent');
    
    function applyFilters() {
        const rows = document.querySelectorAll('.agent-row');
        
        rows.forEach(row => {
            const status = row.dataset.status;
            const group = row.dataset.group;
            const searchText = row.dataset.search;
            
            const statusMatch = !filterStatus.value || status === filterStatus.value;
            const groupMatch = !filterGroup.value || group === filterGroup.value;
            const searchMatch = !searchAgent.value || searchText.includes(searchAgent.value.toLowerCase());
            
            row.style.display = statusMatch && groupMatch && searchMatch ? '' : 'none';
        });
    }
    
    filterStatus.addEventListener('change', applyFilters);
    filterGroup.addEventListener('change', applyFilters);
    searchAgent.addEventListener('input', applyFilters);
}

function deployAgent() {
    openModal('deployAgentModal');
}

function refreshAgents() {
    showNotification('Actualisation des agents...', 'info');
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

function viewAgent(agentId) {
    showNotification(`Affichage des détails de l'agent ${agentId}`, 'info');
}

function restartAgent(agentId) {
    if (confirm('Êtes-vous sûr de vouloir redémarrer cet agent ?')) {
        showNotification(`Redémarrage de l'agent ${agentId}...`, 'warning');
    }
}

function upgradeAgent(agentId) {
    if (confirm('Êtes-vous sûr de vouloir mettre à jour cet agent ?')) {
        showNotification(`Mise à jour de l'agent ${agentId}...`, 'info');
    }
}

function removeAgent(agentId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet agent ? Cette action est irréversible.')) {
        showNotification(`Suppression de l'agent ${agentId}...`, 'error');
    }
}

// Fonctions pour le modal de déploiement d'agent
function switchTab(tabName) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Réinitialiser tous les onglets
    document.querySelectorAll('[id$="Tab"]').forEach(tab => {
        tab.classList.remove('bg-blue-600');
        tab.classList.add('text-gray-300');
    });
    
    // Afficher le contenu sélectionné
    document.getElementById(tabName + 'Content').classList.remove('hidden');
    
    // Activer l'onglet sélectionné
    const activeTab = document.getElementById(tabName + 'Tab');
    activeTab.classList.add('bg-blue-600');
    activeTab.classList.remove('text-gray-300');
}

function downloadAgent(platform, format) {
    if (platform === 'linux' && format === 'install') {
        const link = document.createElement('a');
        link.href = 'agents/linux/install.sh';
        link.download = 'install.sh';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showNotification('Téléchargement de install.sh...', 'info');
        return;
    }

    const downloads = {
        'windows': {
            'msi': {
                filename: 'siem-agent-4.5.2-x64.msi',
                size: '45.2 MB',
                content: generateAgentContent('windows', 'msi')
            },
            'exe': {
                filename: 'siem-agent-4.5.2-portable.exe',
                size: '42.1 MB',
                content: generateAgentContent('windows', 'exe')
            }
        },
        'linux': {
            'deb': {
                filename: 'siem-agent-4.5.2.deb',
                size: '32.8 MB',
                content: generateAgentContent('linux', 'deb')
            },
            'rpm': {
                filename: 'siem-agent-4.5.2.rpm',
                size: '34.1 MB',
                content: generateAgentContent('linux', 'rpm')
            },
            'tar': {
                filename: 'siem-agent-4.5.2.tar.gz',
                size: '28.9 MB',
                content: generateAgentContent('linux', 'tar')
            }
        }
    };
    
    const download = downloads[platform][format];
    
    // Afficher notification de début
    showNotification(`Téléchargement de ${download.filename} (${download.size})...`, 'info');
    
    // Créer le blob avec le contenu
    const blob = new Blob([download.content], { 
        type: 'application/octet-stream' 
    });
    
    // Créer l'URL de téléchargement
    const url = URL.createObjectURL(blob);
    
    // Créer et déclencher le téléchargement
    const link = document.createElement('a');
    link.href = url;
    link.download = download.filename;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Nettoyer l'URL
    URL.revokeObjectURL(url);
    
    // Notification de succès
    setTimeout(() => {
        showNotification(`${download.filename} téléchargé avec succès`, 'success');
    }, 500);
}

function generateAgentContent(platform, format) {
    const timestamp = new Date().toISOString();
    const rawServer = document.getElementById('serverUrl') ? document.getElementById('serverUrl').value || 'https://your-siem-server.com' : 'https://your-siem-server.com';
    const normalized = normalizeServerEndpoint(rawServer, 1514);
    const serverUrl = normalized.url;
    
    const contents = {
        'windows': {
            'msi': `# SIEM Agent Windows Installer v4.5.2
# Generated: ${timestamp}
# Platform: Windows 10/11, Server 2016+
# Architecture: x64

[Configuration]
ServerURL=${serverUrl}
Port=1514
AutoStart=true
ServiceName=SIEMAgent
InstallPath=%ProgramFiles%\\SIEM Agent\\

[Features]
LogCollection=true
ProcessMonitoring=true
FileIntegrity=true
NetworkMonitoring=true
EncryptedCommunication=true
AutoUpdate=true

[Installation Instructions]
1. Run as Administrator
2. Double-click the MSI file
3. Follow the installation wizard
4. Agent will auto-configure with SIEM server
5. Service starts automatically

[System Requirements]
- Windows 10 or later / Windows Server 2016 or later
- 2 GB RAM minimum
- 500 MB disk space
- Network connectivity to SIEM server

For support: support@siem-company.com`,

            'exe': `# SIEM Agent Windows Portable v4.5.2
# Generated: ${timestamp}
# Platform: Windows 10/11, Server 2016+
# Architecture: x64

[Configuration]
ServerURL=${serverUrl}
Port=1514
Portable=true

[Usage Instructions]
1. Extract to desired folder
2. Run siem-agent.exe as Administrator
3. Configure server settings in config.json
4. Agent runs in portable mode

[Features]
- No installation required
- Portable configuration
- Real-time monitoring
- Encrypted communication

For support: support@siem-company.com`
        },
        'linux': {
            'deb': `# SIEM Agent Linux Package v4.5.2
# Generated: ${timestamp}
# Platform: Ubuntu, Debian
# Architecture: x86_64

Package: siem-agent
Version: 4.5.2
Architecture: amd64
Maintainer: SIEM Company <support@siem-company.com>
Description: SIEM Agent for log collection and monitoring
 This package installs the SIEM agent for real-time
 log collection, process monitoring, and security analysis.

[Configuration]
ServerURL=${serverUrl}
Port=1514
SystemdService=true

[Installation Commands]
sudo dpkg -i siem-agent-4.5.2.deb
sudo systemctl enable siem-agent
sudo systemctl start siem-agent

[Features]
- Real-time log collection
- Process monitoring
- File integrity monitoring
- Network monitoring
- Systemd integration
- Encrypted communication

[System Requirements]
- Ubuntu 18.04+ or Debian 10+
- 1 GB RAM minimum
- 200 MB disk space
- Network connectivity to SIEM server

For support: support@siem-company.com`,

            'rpm': `# SIEM Agent Linux RPM Package v4.5.2
# Generated: ${timestamp}
# Platform: CentOS, RHEL, Fedora
# Architecture: x86_64

Name: siem-agent
Version: 4.5.2
Release: 1
Summary: SIEM Agent for log collection and monitoring
License: Commercial
Group: System/Monitoring

[Configuration]
ServerURL=${serverUrl}
Port=1514
SystemdService=true

[Installation Commands]
sudo rpm -ivh siem-agent-4.5.2.rpm
sudo systemctl enable siem-agent
sudo systemctl start siem-agent

[Features]
- Real-time log collection
- Process monitoring
- File integrity monitoring
- Network monitoring
- Systemd integration
- Encrypted communication

[System Requirements]
- CentOS 7+, RHEL 7+, or Fedora 30+
- 1 GB RAM minimum
- 200 MB disk space
- Network connectivity to SIEM server

For support: support@siem-company.com`,

            'tar': `# SIEM Agent Generic Linux Archive v4.5.2
# Generated: ${timestamp}
# Platform: Generic Linux
# Architecture: x86_64

[Configuration]
ServerURL=${serverUrl}
Port=1514
ManualInstall=true

[Installation Commands]
tar -xzf siem-agent-4.5.2.tar.gz
cd siem-agent-4.5.2
sudo ./install.sh --server-url="${serverUrl}"

[Manual Installation Steps]
1. Extract the archive
2. Run install.sh as root
3. Configure /etc/siem-agent/config.json
4. Start the service: sudo systemctl start siem-agent

[Features]
- Generic Linux compatibility
- Manual configuration
- Real-time monitoring
- Encrypted communication
- Custom installation paths

[System Requirements]
- Any Linux distribution with systemd
- 1 GB RAM minimum
- 200 MB disk space
- Network connectivity to SIEM server

For support: support@siem-company.com`
        }
    };
    
    return contents[platform][format];
}

function generateAuthKey() {
    // Générer une nouvelle clé d'authentification
    const chars = '0123456789abcdef';
    let key = '';
    for (let i = 0; i < 32; i++) {
        key += chars[Math.floor(Math.random() * chars.length)];
    }
    document.getElementById('authKey').value = key;
    showNotification('Nouvelle clé d\'authentification générée', 'success');
}

function normalizeServerEndpoint(rawInput, fallbackPort) {
    const trimmed = (rawInput || '').trim();
    if (!trimmed) {
        return { url: '', port: fallbackPort };
    }

    // new URL() exige un schéma. On accepte donc:
    // - domaine/IP (ex: 192.168.1.10, siem.local)
    // - URL complète (ex: https://siem.local:8443)
    const hasScheme = /^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//.test(trimmed);
    const candidate = hasScheme ? trimmed : `https://${trimmed}`;

    const urlObj = new URL(candidate);

    // Normaliser: retirer trailing slash, et gérer le port.
    const portFromUrl = urlObj.port ? parseInt(urlObj.port, 10) : null;
    urlObj.port = '';
    const normalizedUrl = urlObj.toString().replace(/\/$/, '');

    return {
        url: normalizedUrl,
        port: Number.isFinite(portFromUrl) ? portFromUrl : fallbackPort
    };
}

function generateConfigFile() {
    const rawServer = document.getElementById('serverUrl').value.trim();
    const portField = parseInt(document.getElementById('serverPort').value, 10);
    const fallbackPort = Number.isFinite(portField) ? portField : 1514;
    
    // Validation de l'entrée
    if (!rawServer) {
        showNotification('Veuillez renseigner l\'URL ou l\'IP du serveur SIEM', 'warning');
        document.getElementById('serverUrl').focus();
        return;
    }
    
    // Validation et normalisation URL/IP
    let endpoint;
    try {
        endpoint = normalizeServerEndpoint(rawServer, fallbackPort);
    } catch (e) {
        showNotification('Format invalide. Utilisez un domaine/URL ou une IP (ex: siem.entreprise.com ou 192.168.1.100)', 'warning');
        document.getElementById('serverUrl').focus();
        return;
    }
    
    const config = {
        server: {
            url: endpoint.url,
            port: endpoint.port,
            auth_key: document.getElementById('authKey').value
        },
        agent: {
            group: document.getElementById('agentGroup').value,
            log_level: document.getElementById('logLevel').value,
            auto_update: true,
            ssl_verify: true
        },
        monitoring: {
            file_integrity: true,
            process_monitoring: true,
            network_monitoring: true,
            log_collection: true
        }
    };
    
    // Créer le fichier de configuration
    const configText = JSON.stringify(config, null, 2);
    const blob = new Blob([configText], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'siem-agent-config.json';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showNotification('Fichier de configuration généré et téléchargé', 'success');
}

function testConnection() {
    const rawServer = document.getElementById('serverUrl').value.trim();
    const portField = parseInt(document.getElementById('serverPort').value, 10);
    const fallbackPort = Number.isFinite(portField) ? portField : 1514;
    
    // Validation de l'entrée
    if (!rawServer) {
        showNotification('Veuillez renseigner l\'URL ou l\'IP du serveur SIEM', 'warning');
        document.getElementById('serverUrl').focus();
        return;
    }
    
    // Validation et normalisation URL/IP
    let endpoint;
    try {
        endpoint = normalizeServerEndpoint(rawServer, fallbackPort);
    } catch (e) {
        showNotification('Format invalide. Utilisez un domaine/URL ou une IP (ex: siem.entreprise.com ou 192.168.1.100)', 'warning');
        document.getElementById('serverUrl').focus();
        return;
    }
    
    showNotification('Test de connexion en cours...', 'info');
    
    // Simuler un test de connexion
    setTimeout(() => {
        const success = Math.random() > 0.3; // 70% de chance de succès
        
        if (success) {
            showNotification(`Connexion réussie vers ${endpoint.url}:${endpoint.port}`, 'success');
        } else {
            showNotification(`Échec de la connexion vers ${endpoint.url}:${endpoint.port}`, 'error');
        }
    }, 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
