/**
 * Gestionnaire d'authentification pour SIEM
 * Vérifie l'authentification et gère les sessions utilisateur
 */
class AuthManager {
    constructor() {
        this.user = null;
        this.sessionToken = null;
        this.checkInterval = null;
        
        this.init();
    }
    
    async init() {
        // Vérifier l'authentification au chargement
        await this.checkAuthentication();
        
        // Vérifier périodiquement la session (toutes les 5 minutes)
        this.checkInterval = setInterval(() => {
            this.checkAuthentication();
        }, 5 * 60 * 1000);
        
        // Écouter les événements de fermeture de page
        window.addEventListener('beforeunload', () => {
            if (this.checkInterval) {
                clearInterval(this.checkInterval);
            }
        });
    }
    
    async checkAuthentication() {
        try {
            const response = await fetch('auth.php?action=check', {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success && data.user) {
                this.user = data.user;
                this.sessionToken = this.getCookie('siem_session');
                this.updateUserInterface();
                return true;
            } else {
                this.redirectToLogin();
                return false;
            }
        } catch (error) {
            console.error('Erreur de vérification d\'authentification:', error);
            this.redirectToLogin();
            return false;
        }
    }
    
    redirectToLogin() {
        // Nettoyer les données locales
        localStorage.removeItem('siem_user');
        
        // Rediriger vers la page de connexion
        if (window.location.pathname !== '/login.html') {
            window.location.href = 'login.html';
        }
    }
    
    async logout() {
        try {
            const response = await fetch('auth.php?action=logout', {
                method: 'POST',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.user = null;
                this.sessionToken = null;
                localStorage.removeItem('siem_user');
                
                // Afficher un message de confirmation
                this.showNotification('Déconnexion réussie', 'success');
                
                // Rediriger après un court délai
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 1000);
            }
        } catch (error) {
            console.error('Erreur de déconnexion:', error);
            // Forcer la redirection même en cas d'erreur
            window.location.href = 'login.html';
        }
    }
    
    updateUserInterface() {
        if (!this.user) return;
        
        // Mettre à jour les éléments de l'interface utilisateur
        const userElements = document.querySelectorAll('[data-user-info]');
        userElements.forEach(element => {
            const info = element.dataset.userInfo;
            if (this.user[info]) {
                element.textContent = this.user[info];
            }
        });
        
        // Afficher/masquer les éléments selon le rôle
        const roleElements = document.querySelectorAll('[data-role]');
        roleElements.forEach(element => {
            const requiredRole = element.dataset.role;
            if (requiredRole === this.user.role || requiredRole === 'any') {
                element.style.display = '';
            } else {
                element.style.display = 'none';
            }
        });
        
        // Ajouter le bouton de déconnexion s'il n'existe pas
        this.addLogoutButton();
    }
    
    addLogoutButton() {
        // Chercher un conteneur pour le bouton de déconnexion
        let logoutContainer = document.getElementById('logout-container');
        
        if (!logoutContainer) {
            // Créer un conteneur dans la barre de navigation
            const nav = document.querySelector('nav') || document.querySelector('.navbar') || document.body;
            logoutContainer = document.createElement('div');
            logoutContainer.id = 'logout-container';
            logoutContainer.className = 'fixed top-4 right-4 z-50';
            nav.appendChild(logoutContainer);
        }
        
        if (!document.getElementById('logout-btn')) {
            logoutContainer.innerHTML = `
                <div class="flex items-center space-x-4 bg-slate-800 bg-opacity-90 backdrop-blur-sm rounded-lg px-4 py-2 border border-blue-500/20">
                    <div class="flex items-center space-x-2 text-white">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div class="text-sm">
                            <div class="font-medium" data-user-info="username">${this.user.username}</div>
                            <div class="text-blue-300 text-xs capitalize" data-user-info="role">${this.user.role}</div>
                        </div>
                    </div>
                    <button 
                        id="logout-btn" 
                        class="text-red-400 hover:text-red-300 transition-colors p-2 rounded-md hover:bg-red-500/10"
                        title="Déconnexion"
                    >
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </div>
            `;
            
            // Ajouter l'événement de déconnexion
            document.getElementById('logout-btn').addEventListener('click', () => {
                if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                    this.logout();
                }
            });
        }
    }
    
    showNotification(message, type = 'info') {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300`;
        
        const typeClasses = {
            success: 'bg-green-600 text-white',
            error: 'bg-red-600 text-white',
            warning: 'bg-yellow-600 text-white',
            info: 'bg-blue-600 text-white'
        };
        
        notification.className += ` ${typeClasses[type] || typeClasses.info}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translate(-50%, 0)';
        }, 100);
        
        // Suppression automatique
        setTimeout(() => {
            notification.style.transform = 'translate(-50%, -100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    // Méthodes utilitaires pour les autres scripts
    getUser() {
        return this.user;
    }
    
    isAuthenticated() {
        return this.user !== null;
    }
    
    hasRole(role) {
        return this.user && this.user.role === role;
    }
    
    isAdmin() {
        return this.hasRole('admin');
    }
    
    // Méthode pour faire des requêtes authentifiées
    async authenticatedFetch(url, options = {}) {
        const defaultOptions = {
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            }
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        // Vérifier si la session a expiré
        if (response.status === 401) {
            this.redirectToLogin();
            throw new Error('Session expirée');
        }
        
        return response;
    }
}

// Initialiser le gestionnaire d'authentification
let authManager;

document.addEventListener('DOMContentLoaded', () => {
    authManager = new AuthManager();
});

// Exporter pour utilisation dans d'autres scripts
window.AuthManager = AuthManager;
window.getAuthManager = () => authManager;
