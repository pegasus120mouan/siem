#!/bin/bash

# Script de migration de Nginx vers Apache pour SUSDR 360
# Usage: sudo ./migrate_nginx_to_apache.sh

set -e

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables
BACKUP_DIR="/opt/susdr360/backup_$(date +%Y%m%d_%H%M%S)"
SUSDR360_HOME="/opt/susdr360"

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Ce script doit être exécuté en tant que root (sudo)"
        exit 1
    fi
}

backup_nginx_config() {
    log_info "Sauvegarde de la configuration Nginx..."
    
    mkdir -p "$BACKUP_DIR"
    
    # Sauvegarder la configuration Nginx
    if [ -f "/etc/nginx/sites-available/susdr360" ]; then
        cp "/etc/nginx/sites-available/susdr360" "$BACKUP_DIR/nginx_susdr360.conf"
        log_success "Configuration Nginx sauvegardée dans $BACKUP_DIR"
    fi
    
    # Sauvegarder les logs Nginx
    if [ -d "/var/log/nginx" ]; then
        cp -r "/var/log/nginx" "$BACKUP_DIR/nginx_logs" 2>/dev/null || true
    fi
}

stop_nginx() {
    log_info "Arrêt de Nginx..."
    
    if systemctl is-active --quiet nginx; then
        systemctl stop nginx
        log_success "Nginx arrêté"
    else
        log_info "Nginx n'était pas en cours d'exécution"
    fi
    
    # Désactiver Nginx au démarrage
    if systemctl is-enabled --quiet nginx; then
        systemctl disable nginx
        log_success "Nginx désactivé au démarrage"
    fi
}

install_apache() {
    log_info "Installation d'Apache et des modules nécessaires..."
    
    # Mise à jour des paquets
    apt-get update
    
    # Installation d'Apache et PHP-FPM
    apt-get install -y apache2 php-fpm php-cli php-mysql php-xml php-mbstring php-curl php-json
    
    # Modules Apache nécessaires
    a2enmod rewrite
    a2enmod proxy
    a2enmod proxy_http
    a2enmod proxy_fcgi
    a2enmod headers
    a2enmod expires
    a2enmod ssl
    
    log_success "Apache installé avec succès"
}

configure_php_fpm() {
    log_info "Configuration de PHP-FPM pour Apache..."
    
    # Détecter la version PHP
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    log_info "Version PHP détectée: $PHP_VERSION"
    
    # Activer et démarrer PHP-FPM
    systemctl enable php${PHP_VERSION}-fpm
    systemctl start php${PHP_VERSION}-fpm
    
    log_success "PHP-FPM configuré pour la version $PHP_VERSION"
}

configure_apache() {
    log_info "Configuration d'Apache pour SUSDR 360..."
    
    # Copier la configuration Apache
    if [ -f "$SUSDR360_HOME/susdr360/apache_susdr360.conf" ]; then
        cp "$SUSDR360_HOME/susdr360/apache_susdr360.conf" /etc/apache2/sites-available/susdr360.conf
    else
        log_error "Fichier de configuration Apache non trouvé!"
        exit 1
    fi
    
    # Mise à jour de la version PHP dans la configuration
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    sed -i "s/php7.4-fpm.sock/php${PHP_VERSION}-fpm.sock/g" /etc/apache2/sites-available/susdr360.conf
    
    # Désactiver le site par défaut Apache
    a2dissite 000-default
    
    # Activer le site SUSDR 360
    a2ensite susdr360
    
    log_success "Configuration Apache appliquée"
}

test_apache_config() {
    log_info "Test de la configuration Apache..."
    
    if apache2ctl configtest; then
        log_success "Configuration Apache valide"
    else
        log_error "Erreur dans la configuration Apache"
        exit 1
    fi
}

start_apache() {
    log_info "Démarrage d'Apache..."
    
    systemctl restart apache2
    systemctl enable apache2
    
    if systemctl is-active --quiet apache2; then
        log_success "Apache démarré avec succès"
    else
        log_error "Échec du démarrage d'Apache"
        exit 1
    fi
}

update_firewall() {
    log_info "Mise à jour des règles de firewall..."
    
    # Supprimer les règles Nginx si elles existent
    ufw delete allow 'Nginx Full' 2>/dev/null || true
    ufw delete allow 'Nginx HTTP' 2>/dev/null || true
    ufw delete allow 'Nginx HTTPS' 2>/dev/null || true
    
    # Ajouter les règles Apache
    ufw allow 'Apache Full'
    
    log_success "Règles de firewall mises à jour"
}

verify_migration() {
    log_info "Vérification de la migration..."
    
    # Vérifier qu'Apache fonctionne
    if systemctl is-active --quiet apache2; then
        log_success "✓ Apache est actif"
    else
        log_error "✗ Apache n'est pas actif"
        return 1
    fi
    
    # Vérifier que PHP-FPM fonctionne
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
        log_success "✓ PHP-FPM est actif"
    else
        log_error "✗ PHP-FPM n'est pas actif"
        return 1
    fi
    
    # Vérifier que SUSDR 360 API fonctionne
    if systemctl is-active --quiet susdr360; then
        log_success "✓ SUSDR 360 API est active"
    else
        log_warning "⚠ SUSDR 360 API n'est pas active (redémarrage recommandé)"
    fi
    
    # Test HTTP
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|301\|302"; then
        log_success "✓ Serveur web répond correctement"
    else
        log_warning "⚠ Le serveur web ne répond pas comme attendu"
    fi
    
    # Test API
    if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/health | grep -q "200"; then
        log_success "✓ API SUSDR 360 répond correctement"
    else
        log_warning "⚠ L'API SUSDR 360 ne répond pas (vérifiez le service)"
    fi
}

cleanup_nginx() {
    log_info "Nettoyage des fichiers Nginx (optionnel)..."
    
    read -p "Voulez-vous supprimer complètement Nginx ? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        apt-get remove --purge -y nginx nginx-common nginx-core
        apt-get autoremove -y
        
        # Supprimer les fichiers de configuration restants
        rm -rf /etc/nginx
        rm -rf /var/log/nginx
        
        log_success "Nginx complètement supprimé"
    else
        log_info "Nginx conservé (désactivé)"
    fi
}

show_summary() {
    log_success "=== Migration de Nginx vers Apache terminée! ==="
    echo
    echo "Résumé des changements:"
    echo "- Nginx: arrêté et désactivé"
    echo "- Apache: installé et configuré"
    echo "- PHP-FPM: configuré pour Apache"
    echo "- Firewall: règles mises à jour"
    echo "- Sauvegarde: $BACKUP_DIR"
    echo
    echo "Services actifs:"
    echo "- Apache: systemctl status apache2"
    echo "- PHP-FPM: systemctl status php*-fpm"
    echo "- SUSDR 360: systemctl status susdr360"
    echo
    echo "Nouveaux logs:"
    echo "- Apache accès: /var/log/apache2/susdr360_access.log"
    echo "- Apache erreur: /var/log/apache2/susdr360_error.log"
    echo
    echo "Configuration Apache: /etc/apache2/sites-available/susdr360.conf"
    echo
    log_info "Testez votre application pour vous assurer que tout fonctionne correctement!"
}

rollback_instructions() {
    echo
    log_warning "En cas de problème, pour revenir à Nginx:"
    echo "1. sudo systemctl stop apache2"
    echo "2. sudo systemctl disable apache2"
    echo "3. sudo systemctl start nginx"
    echo "4. sudo systemctl enable nginx"
    echo "5. sudo cp $BACKUP_DIR/nginx_susdr360.conf /etc/nginx/sites-available/susdr360"
    echo "6. sudo nginx -t && sudo systemctl reload nginx"
}

# Fonction principale
main() {
    log_info "Début de la migration de Nginx vers Apache pour SUSDR 360..."
    echo
    
    check_root
    
    log_warning "Cette opération va:"
    log_warning "- Arrêter Nginx"
    log_warning "- Installer Apache"
    log_warning "- Migrer la configuration"
    log_warning "- Redémarrer les services"
    echo
    
    read -p "Continuer la migration ? (y/N): " -n 1 -r
    echo
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        log_info "Migration annulée"
        exit 0
    fi
    
    backup_nginx_config
    stop_nginx
    install_apache
    configure_php_fpm
    configure_apache
    test_apache_config
    start_apache
    update_firewall
    
    echo
    verify_migration
    
    echo
    cleanup_nginx
    
    echo
    show_summary
    rollback_instructions
}

# Exécution du script principal
main "$@"
