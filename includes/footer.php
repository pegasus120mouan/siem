            </div>
        </div>
    </div>
    
    <!-- Scripts globaux -->
    <script>
        // Mise à jour de l'heure
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR');
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Mettre à jour l'heure toutes les secondes
        setInterval(updateTime, 1000);
        updateTime();
        
        // Fonction utilitaire pour les notifications
        function showNotification(message, type = 'info', duration = 3000) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
            
            const typeClasses = {
                success: 'bg-green-600 text-white',
                error: 'bg-red-600 text-white',
                warning: 'bg-yellow-600 text-white',
                info: 'bg-blue-600 text-white'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            notification.className += ` ${typeClasses[type] || typeClasses.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icons[type] || icons.info} mr-3"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Suppression automatique
            if (duration > 0) {
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, duration);
            }
        }
        
        // Fonction pour les requêtes AJAX
        async function apiRequest(url, options = {}) {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            };
            
            try {
                const response = await fetch(url, { ...defaultOptions, ...options });
                
                if (response.status === 401) {
                    showNotification('Session expirée, redirection...', 'warning');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                    return null;
                }
                
                const data = await response.json();
                return data;
            } catch (error) {
                console.error('Erreur API:', error);
                showNotification('Erreur de communication avec le serveur', 'error');
                return null;
            }
        }
        
        // Fonction pour formater les dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR');
        }
        
        // Fonction pour formater les tailles de fichier
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Gestion des modales
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        }
        
        // Fermer les modales en cliquant à l'extérieur
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                const modal = e.target;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Gestion des formulaires avec AJAX
        function handleFormSubmit(formId, successCallback) {
            const form = document.getElementById(formId);
            if (!form) return;
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Désactiver le bouton et afficher le loading
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement...';
                
                try {
                    const response = await apiRequest(form.action, {
                        method: form.method || 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (response && response.success) {
                        showNotification(response.message || 'Opération réussie', 'success');
                        if (successCallback) {
                            successCallback(response);
                        }
                        form.reset();
                    } else {
                        showNotification(response?.message || 'Erreur lors de l\'opération', 'error');
                    }
                } catch (error) {
                    showNotification('Erreur lors de l\'opération', 'error');
                } finally {
                    // Réactiver le bouton
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
        
        // Initialisation des tooltips
        function initTooltips() {
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    const tooltip = document.createElement('div');
                    tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-800 rounded shadow-lg';
                    tooltip.textContent = this.dataset.tooltip;
                    tooltip.id = 'tooltip-' + Math.random().toString(36).substr(2, 9);
                    
                    document.body.appendChild(tooltip);
                    
                    const rect = this.getBoundingClientRect();
                    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
                    
                    this.tooltipId = tooltip.id;
                });
                
                element.addEventListener('mouseleave', function() {
                    if (this.tooltipId) {
                        const tooltip = document.getElementById(this.tooltipId);
                        if (tooltip) {
                            tooltip.remove();
                        }
                    }
                });
            });
        }
        
        // Initialiser les tooltips au chargement
        document.addEventListener('DOMContentLoaded', initTooltips);
    </script>
</body>
</html>
