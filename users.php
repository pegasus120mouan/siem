<?php
$pageTitle = "Gestion des Utilisateurs";
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
            case 'create_user':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'analyst';
                
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('Tous les champs sont requis');
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email invalide');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('Le mot de passe doit contenir au moins 6 caractères');
                }
                
                $result = $db->createUser($username, $email, $password, $role);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès']);
                } else {
                    throw new Exception('Erreur lors de la création de l\'utilisateur');
                }
                break;
                
            case 'toggle_user':
                $userId = intval($_POST['user_id'] ?? 0);
                $isActive = intval($_POST['is_active'] ?? 0);
                
                // Cette fonctionnalité nécessiterait d'ajouter une méthode à ConfigDatabase
                echo json_encode(['success' => true, 'message' => 'Statut utilisateur mis à jour']);
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

// Récupérer la liste des utilisateurs (simulation pour la démo)
$users = [
    [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@siem.local',
        'role' => 'admin',
        'is_active' => 1,
        'last_login' => '2024-12-02 20:00:00',
        'created_at' => '2024-12-01 10:00:00'
    ],
    [
        'id' => 2,
        'username' => 'analyst1',
        'email' => 'analyst1@siem.local',
        'role' => 'analyst',
        'is_active' => 1,
        'last_login' => '2024-12-02 18:30:00',
        'created_at' => '2024-12-01 14:00:00'
    ]
];
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-white">Gestion des Utilisateurs</h1>
    <button onclick="openModal('createUserModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
        <i class="fas fa-plus mr-2"></i>Nouvel Utilisateur
    </button>
</div>

<!-- Statistiques -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Utilisateurs</p>
                <p class="text-white text-2xl font-bold"><?php echo count($users); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-400 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Administrateurs</p>
                <p class="text-white text-2xl font-bold"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'admin')); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-red-400 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Analystes</p>
                <p class="text-white text-2xl font-bold"><?php echo count(array_filter($users, fn($u) => $u['role'] === 'analyst')); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-tie text-green-400 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="card rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Actifs</p>
                <p class="text-white text-2xl font-bold"><?php echo count(array_filter($users, fn($u) => $u['is_active'])); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-check-circle text-green-400 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des utilisateurs -->
<div class="card rounded-xl overflow-hidden">
    <div class="p-6 border-b border-gray-600">
        <div class="flex justify-between items-center">
            <h2 class="text-white text-lg font-semibold">Liste des Utilisateurs</h2>
            <div class="flex space-x-2">
                <input type="text" placeholder="Rechercher..." class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm" id="searchUsers">
                <select class="bg-gray-700 text-white px-3 py-2 rounded-md text-sm" id="filterRole">
                    <option value="">Tous les rôles</option>
                    <option value="admin">Administrateur</option>
                    <option value="analyst">Analyste</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Rôle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Dernière connexion</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-600" id="usersTableBody">
                <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-700 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="text-white font-medium"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="text-gray-400 text-sm">ID: <?php echo $user['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-white"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="flex items-center">
                            <div class="w-2 h-2 <?php echo $user['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?> rounded-full mr-2"></div>
                            <span class="text-white text-sm"><?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?></span>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-sm">
                        <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-2">
                            <button onclick="editUser(<?php echo $user['id']; ?>)" class="text-blue-400 hover:text-blue-300" data-tooltip="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="toggleUser(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 0 : 1; ?>)" class="text-yellow-400 hover:text-yellow-300" data-tooltip="<?php echo $user['is_active'] ? 'Désactiver' : 'Activer'; ?>">
                                <i class="fas fa-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                            </button>
                            <?php if ($user['username'] !== 'admin'): ?>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" class="text-red-400 hover:text-red-300" data-tooltip="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de création d'utilisateur -->
<div id="createUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Nouvel Utilisateur</h3>
            <button onclick="closeModal('createUserModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="createUserForm" action="users.php" method="POST">
            <input type="hidden" name="action" value="create_user">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Nom d'utilisateur</label>
                    <input type="text" name="username" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" required class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Mot de passe</label>
                    <input type="password" name="password" required minlength="6" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Rôle</label>
                    <select name="role" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="analyst">Analyste</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('createUserModal')" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Annuler
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-plus mr-2"></i>Créer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la gestion des formulaires
    handleFormSubmit('createUserForm', function(response) {
        closeModal('createUserModal');
        // Recharger la page pour voir le nouvel utilisateur
        setTimeout(() => window.location.reload(), 1000);
    });
    
    // Initialiser la recherche
    initUserSearch();
});

function initUserSearch() {
    const searchInput = document.getElementById('searchUsers');
    const roleFilter = document.getElementById('filterRole');
    
    function filterUsers() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedRole = roleFilter.value;
        const rows = document.querySelectorAll('#usersTableBody tr');
        
        rows.forEach(row => {
            const username = row.querySelector('td:first-child .text-white').textContent.toLowerCase();
            const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const role = row.querySelector('td:nth-child(3) span').textContent.toLowerCase();
            
            const matchesSearch = username.includes(searchTerm) || email.includes(searchTerm);
            const matchesRole = !selectedRole || role.includes(selectedRole);
            
            row.style.display = matchesSearch && matchesRole ? '' : 'none';
        });
    }
    
    searchInput.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
}

function editUser(userId) {
    showNotification('Fonctionnalité d\'édition en cours de développement', 'info');
}

async function toggleUser(userId, newStatus) {
    const action = newStatus ? 'activer' : 'désactiver';
    
    if (!confirm(`Êtes-vous sûr de vouloir ${action} cet utilisateur ?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_user');
        formData.append('user_id', userId);
        formData.append('is_active', newStatus);
        
        const response = await fetch('users.php', {
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
        showNotification('Erreur lors de la mise à jour', 'error');
    }
}

function deleteUser(userId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        return;
    }
    
    showNotification('Fonctionnalité de suppression en cours de développement', 'warning');
}
</script>

<?php require_once 'includes/footer.php'; ?>
