<?php
$pageTitle = "Configuration Système";
require_once 'includes/header.php';
require_once 'config/database.php';

// Vérifier que l'utilisateur est admin
AuthController::requireAdmin();

// Initialiser la base de données
$db = new ConfigDatabase();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'save_api_key':
                $serviceName = trim($_POST['service_name'] ?? '');
                $apiKey = trim($_POST['api_key'] ?? '');
                
                if (empty($serviceName) || empty($apiKey)) {
                    throw new Exception('Le nom du service et la clé API sont requis');
                }
                
                $result = $db->saveApiKey($serviceName, $apiKey);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Clé API sauvegardée avec succès']);
                } else {
                    throw new Exception('Erreur lors de la sauvegarde de la clé API');
                }
                break;
                
            case 'save_setting':
                $key = trim($_POST['key'] ?? '');
                $value = trim($_POST['value'] ?? '');
                $description = trim($_POST['description'] ?? '');
                
                if (empty($key)) {
                    throw new Exception('La clé du paramètre est requise');
                }
                
                $result = $db->setSetting($key, $value, $description);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Paramètre sauvegardé avec succès']);
                } else {
                    throw new Exception('Erreur lors de la sauvegarde du paramètre');
                }
                break;
                
            case 'delete_api_key':
                $serviceName = trim($_POST['service_name'] ?? '');
                
                if (empty($serviceName)) {
                    throw new Exception('Le nom du service est requis');
                }
                
                $result = $db->deleteApiKey($serviceName);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Clé API supprimée avec succès']);
                } else {
                    throw new Exception('Erreur lors de la suppression de la clé API');
                }
                break;
                
            case 'backup_database':
                $backupPath = $db->backup();
                
                if ($backupPath) {
                    echo json_encode(['success' => true, 'message' => 'Sauvegarde créée: ' . basename($backupPath)]);
                } else {
                    throw new Exception('Erreur lors de la création de la sauvegarde');
                }
                break;
                
            default:
                throw new Exception('Action non reconnue');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Récupérer les configurations
$apiConfigs = $db->getAllApiConfigs();
$settings = $db->getAllSettings();
$stats = $db->getStats();
?>

<div class="space-y-8">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Configuration Système</h1>
        <div class="flex space-x-3">
            <button onclick="backupDatabase()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-download mr-2"></i>Sauvegarder
            </button>
            <button onclick="window.location.reload()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-sync-alt mr-2"></i>Actualiser
            </button>
        </div>
    </div>
    
    <!-- Statistiques système -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">APIs Configurées</p>
                    <p class="text-white text-2xl font-bold"><?php echo count($apiConfigs); ?></p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plug text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Paramètres</p>
                    <p class="text-white text-2xl font-bold"><?php echo count($settings); ?></p>
                </div>
                <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cog text-green-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Taille BD</p>
                    <p class="text-white text-2xl font-bold"><?php echo formatFileSize($stats['db_size'] ?? 0); ?></p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-database text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Dernière MAJ</p>
                    <p class="text-white text-sm font-bold"><?php echo $stats['last_update'] ? date('d/m H:i', strtotime($stats['last_update'])) : 'N/A'; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Configuration des APIs -->
    <div class="card rounded-xl">
        <div class="p-6 border-b border-gray-600">
            <div class="flex justify-between items-center">
                <h2 class="text-white text-lg font-semibold">Configuration des APIs</h2>
                <button onclick="openModal('addApiModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Ajouter API
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- APIs prédéfinies -->
                <?php
                $predefinedApis = [
                    'virustotal' => ['name' => 'VirusTotal', 'icon' => 'fas fa-virus', 'color' => 'blue'],
                    'abuseipdb' => ['name' => 'AbuseIPDB', 'icon' => 'fas fa-shield-alt', 'color' => 'red'],
                    'shodan' => ['name' => 'Shodan', 'icon' => 'fas fa-search', 'color' => 'green'],
                    'otx' => ['name' => 'AlienVault OTX', 'icon' => 'fas fa-eye', 'color' => 'purple'],
                    'misp' => ['name' => 'MISP', 'icon' => 'fas fa-share-alt', 'color' => 'yellow']
                ];
                
                foreach ($predefinedApis as $key => $api):
                    $configured = false;
                    foreach ($apiConfigs as $config) {
                        if ($config['service_name'] === $key) {
                            $configured = $config['has_key'];
                            break;
                        }
                    }
                ?>
                <div class="bg-gray-700 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-<?php echo $api['color']; ?>-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                                <i class="<?php echo $api['icon']; ?> text-<?php echo $api['color']; ?>-400"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-medium"><?php echo $api['name']; ?></h3>
                                <p class="text-gray-400 text-xs"><?php echo $key; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <div class="w-3 h-3 <?php echo $configured ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full"></div>
                        </div>
                    </div>
                    
                    <div class="flex space-x-2">
                        <button onclick="configureApi('<?php echo $key; ?>', '<?php echo $api['name']; ?>')" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors">
                            <i class="fas fa-cog mr-1"></i>Configurer
                        </button>
                        <?php if ($configured): ?>
                        <button onclick="deleteApiKey('<?php echo $key; ?>')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition-colors">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Paramètres système -->
    <div class="card rounded-xl">
        <div class="p-6 border-b border-gray-600">
            <div class="flex justify-between items-center">
                <h2 class="text-white text-lg font-semibold">Paramètres Système</h2>
                <button onclick="openModal('addSettingModal')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
                    <i class="fas fa-plus mr-2"></i>Nouveau Paramètre
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Paramètre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Valeur</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Dernière MAJ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600">
                    <?php foreach ($settings as $setting): ?>
                    <tr class="hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-blue-400 bg-gray-800 px-2 py-1 rounded text-sm"><?php echo htmlspecialchars($setting['setting_key']); ?></code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-white"><?php echo htmlspecialchars($setting['setting_value']); ?></td>
                        <td class="px-6 py-4 text-gray-400 text-sm"><?php echo htmlspecialchars($setting['description'] ?? ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">
                            <?php echo date('d/m/Y H:i', strtotime($setting['updated_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="editSetting('<?php echo htmlspecialchars($setting['setting_key']); ?>', '<?php echo htmlspecialchars($setting['setting_value']); ?>', '<?php echo htmlspecialchars($setting['description'] ?? ''); ?>')" class="text-blue-400 hover:text-blue-300 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal d'ajout d'API -->
<div id="addApiModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Configuration API</h3>
            <button onclick="closeModal('addApiModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addApiForm" action="config.php" method="POST">
            <input type="hidden" name="action" value="save_api_key">
            <input type="hidden" name="service_name" id="apiServiceName">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Service</label>
                    <input type="text" id="apiServiceDisplay" readonly class="w-full bg-gray-600 text-white px-3 py-2 rounded-md">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Clé API</label>
                    <input type="password" name="api_key" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                    <p class="text-gray-400 text-xs mt-1">La clé sera chiffrée avant stockage</p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('addApiModal')" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Annuler
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i>Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal d'ajout de paramètre -->
<div id="addSettingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Nouveau Paramètre</h3>
            <button onclick="closeModal('addSettingModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="addSettingForm" action="config.php" method="POST">
            <input type="hidden" name="action" value="save_setting">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Clé</label>
                    <input type="text" name="key" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Valeur</label>
                    <input type="text" name="value" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none"></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('addSettingModal')" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Annuler
                </button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i>Sauvegarder
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la gestion des formulaires
    handleFormSubmit('addApiForm', function(response) {
        closeModal('addApiModal');
        setTimeout(() => window.location.reload(), 1000);
    });
    
    handleFormSubmit('addSettingForm', function(response) {
        closeModal('addSettingModal');
        setTimeout(() => window.location.reload(), 1000);
    });
});

function configureApi(serviceName, displayName) {
    document.getElementById('apiServiceName').value = serviceName;
    document.getElementById('apiServiceDisplay').value = displayName;
    openModal('addApiModal');
}

function editSetting(key, value, description) {
    // Pré-remplir le formulaire avec les valeurs existantes
    const form = document.getElementById('addSettingForm');
    form.querySelector('input[name="key"]').value = key;
    form.querySelector('input[name="value"]').value = value;
    form.querySelector('textarea[name="description"]').value = description;
    
    openModal('addSettingModal');
}

async function deleteApiKey(serviceName) {
    if (!confirm(`Êtes-vous sûr de vouloir supprimer la clé API pour ${serviceName} ?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_api_key');
        formData.append('service_name', serviceName);
        
        const response = await fetch('config.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de la suppression', 'error');
    }
}

async function backupDatabase() {
    try {
        const formData = new FormData();
        formData.append('action', 'backup_database');
        
        const response = await fetch('config.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    } catch (error) {
        showNotification('Erreur lors de la sauvegarde', 'error');
    }
}
</script>

<?php
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

require_once 'includes/footer.php';
?>
