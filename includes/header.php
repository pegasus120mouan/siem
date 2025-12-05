<?php
// Vérifier l'authentification
require_once __DIR__ . '/../auth.php';
$user = AuthController::requireAuth();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'SIEM Platform'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://d3js.org/topojson.v3.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .cyber-grid {
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        .sidebar {
            backdrop-filter: blur(10px);
            background: rgba(15, 23, 42, 0.8);
            border-right: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .main-content {
            backdrop-filter: blur(5px);
            background: rgba(15, 23, 42, 0.3);
        }
        
        .card {
            backdrop-filter: blur(10px);
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .nav-link {
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
        }
        
        .nav-link.active {
            background: rgba(59, 130, 246, 0.2);
            border-left: 3px solid #3b82f6;
        }
    </style>
</head>
<body class="min-h-screen cyber-grid">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 flex-shrink-0">
            <div class="p-6">
                <!-- Logo -->
                <div class="flex items-center mb-8">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                        <i class="fas fa-shield-alt text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-white font-bold text-lg">SIEM</h1>
                        <p class="text-blue-300 text-xs">Security Platform</p>
                    </div>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2">
                    <a href="dashboard.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt mr-3"></i>
                        Dashboard
                    </a>
                    
                    <a href="threats.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'threats.php' ? 'active' : ''; ?>">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        Menaces
                    </a>
                    
                    <a href="agents.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'agents.php' ? 'active' : ''; ?>">
                        <i class="fas fa-desktop mr-3"></i>
                        Agents
                    </a>
                    
                    <a href="analytics.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line mr-3"></i>
                        Analytics
                    </a>
                    
                    <a href="logs.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list-alt mr-3"></i>
                        Logs
                    </a>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="pt-4 mt-4 border-t border-gray-600">
                        <p class="text-gray-400 text-xs uppercase tracking-wider mb-2 px-4">Administration</p>
                        
                        <a href="users.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users mr-3"></i>
                            Utilisateurs
                        </a>
                        
                        <a href="config.php" class="nav-link flex items-center px-4 py-3 text-white rounded-lg <?php echo basename($_SERVER['PHP_SELF']) == 'config.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog mr-3"></i>
                            Configuration
                        </a>
                    </div>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- User info -->
            <div class="absolute bottom-0 left-0 right-0 p-6 border-t border-gray-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-white text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-blue-300 text-xs capitalize"><?php echo htmlspecialchars($user['role']); ?></p>
                        </div>
                    </div>
                    <a href="auth.php?action=logout" class="text-red-400 hover:text-red-300 transition-colors" title="Déconnexion">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <div class="bg-slate-800 bg-opacity-50 backdrop-blur-sm border-b border-gray-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-white text-xl font-semibold"><?php echo $pageTitle ?? 'Dashboard'; ?></h2>
                    <div class="flex items-center space-x-4">
                        <div class="text-white text-sm">
                            <i class="fas fa-clock mr-2"></i>
                            <span id="currentTime"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-green-400 text-sm">En ligne</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page content -->
            <div class="flex-1 overflow-auto p-6">
