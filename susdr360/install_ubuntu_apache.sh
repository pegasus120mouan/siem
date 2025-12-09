#!/bin/bash

# SUSDR 360 - Script d'Installation Automatique pour Ubuntu 20.04 avec Apache
# Auteur: SAHANALYTICS - Fisher Ouattara
# Usage: sudo bash install_ubuntu_apache.sh

set -e  # Arrêter en cas d'erreur

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables de configuration
SUSDR360_USER="susdr360"
SUSDR360_HOME="/opt/susdr360"
REPO_URL="https://github.com/pegasus120mouan/siem.git"
DOMAIN_NAME=""  # À configurer
WEB_SERVER="apache"  # Changé de nginx à apache

# Fonctions utilitaires
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

check_ubuntu() {
    if ! grep -q "Ubuntu 20.04" /etc/os-release; then
        log_warning "Ce script est optimisé pour Ubuntu 20.04"
        read -p "Continuer quand même ? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

install_dependencies() {
    log_info "Mise à jour du système et installation des dépendances..."
    
    apt-get update
    apt-get upgrade -y
    
    # Paquets système essentiels
    apt-get install -y \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release \
        htop \
        vim \
        ufw \
        fail2ban
    
    log_success "Dépendances système installées"
}

install_python() {
    log_info "Installation de Python 3.9 et pip..."
    
    apt-get install -y python3.9 python3.9-venv python3.9-dev python3-pip
    
    # Mise à jour de pip
    python3.9 -m pip install --upgrade pip
    
    log_success "Python 3.9 installé"
}

install_apache() {
    log_info "Installation et configuration d'Apache..."
    
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
    
    # Configuration PHP-FPM
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    systemctl enable php${PHP_VERSION}-fpm
    systemctl start php${PHP_VERSION}-fpm
    
    # Désactiver le site par défaut
    a2dissite 000-default
    
    log_success "Apache installé et configuré"
}

create_user() {
    log_info "Création de l'utilisateur système SUSDR360..."
    
    if ! id "$SUSDR360_USER" &>/dev/null; then
        useradd -r -m -d "$SUSDR360_HOME" -s /bin/bash "$SUSDR360_USER"
        log_success "Utilisateur $SUSDR360_USER créé"
    else
        log_info "Utilisateur $SUSDR360_USER existe déjà"
    fi
}

clone_repository() {
    log_info "Clonage du repository SUSDR360..."
    
    if [ -d "$SUSDR360_HOME/siem" ]; then
        log_info "Repository existe déjà, mise à jour..."
        cd "$SUSDR360_HOME/siem"
        sudo -u "$SUSDR360_USER" git pull
    else
        sudo -u "$SUSDR360_USER" git clone "$REPO_URL" "$SUSDR360_HOME/siem"
    fi
    
    # Copier les fichiers de l'application
    cp -r "$SUSDR360_HOME/siem/susdr360" "$SUSDR360_HOME/"
    cp -r "$SUSDR360_HOME/siem/"*.php "$SUSDR360_HOME/app/" 2>/dev/null || true
    cp -r "$SUSDR360_HOME/siem/"*.html "$SUSDR360_HOME/app/" 2>/dev/null || true
    cp -r "$SUSDR360_HOME/siem/"*.js "$SUSDR360_HOME/app/" 2>/dev/null || true
    
    # Créer le répertoire app s'il n'existe pas
    mkdir -p "$SUSDR360_HOME/app"
    
    chown -R "$SUSDR360_USER:$SUSDR360_USER" "$SUSDR360_HOME"
    chown -R www-data:www-data "$SUSDR360_HOME/app"
    
    log_success "Repository cloné et configuré"
}

install_python_dependencies() {
    log_info "Installation des dépendances Python..."
    
    cd "$SUSDR360_HOME/susdr360"
    
    # Créer un environnement virtuel
    sudo -u "$SUSDR360_USER" python3.9 -m venv venv
    
    # Installer les dépendances
    sudo -u "$SUSDR360_USER" ./venv/bin/pip install -r requirements.txt
    
    log_success "Dépendances Python installées"
}

configure_apache() {
    log_info "Configuration d'Apache pour SUSDR360..."
    
    # Copier la configuration Apache
    cp "$SUSDR360_HOME/susdr360/apache_susdr360.conf" /etc/apache2/sites-available/susdr360.conf
    
    # Mise à jour de la version PHP dans la configuration
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    sed -i "s/php7.4-fpm.sock/php${PHP_VERSION}-fpm.sock/g" /etc/apache2/sites-available/susdr360.conf
    
    # Activer le site
    a2ensite susdr360
    
    # Test de la configuration
    apache2ctl configtest
    
    # Redémarrer Apache
    systemctl restart apache2
    systemctl enable apache2
    
    log_success "Apache configuré pour SUSDR360"
}

create_systemd_service() {
    log_info "Création du service systemd pour SUSDR360..."
    
    cat > /etc/systemd/system/susdr360.service << EOF
[Unit]
Description=SUSDR 360 Security Information and Event Management
After=network.target apache2.service

[Service]
Type=simple
User=$SUSDR360_USER
Group=$SUSDR360_USER
WorkingDirectory=$SUSDR360_HOME/susdr360
Environment=PATH=$SUSDR360_HOME/susdr360/venv/bin
ExecStart=$SUSDR360_HOME/susdr360/venv/bin/python main.py
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable susdr360
    
    log_success "Service systemd créé"
}

configure_firewall() {
    log_info "Configuration du firewall..."
    
    # Configuration UFW
    ufw --force enable
    ufw default deny incoming
    ufw default allow outgoing
    
    # Autoriser SSH
    ufw allow ssh
    
    # Autoriser Apache
    ufw allow 'Apache Full'
    
    # Autoriser l'API SUSDR360
    ufw allow 8000
    
    log_success "Firewall configuré"
}

configure_fail2ban() {
    log_info "Configuration de Fail2ban..."
    
    # Configuration pour Apache
    cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/susdr360_error.log

[apache-badbots]
enabled = true
port = http,https
filter = apache-badbots
logpath = /var/log/apache2/susdr360_access.log

[apache-noscript]
enabled = true
port = http,https
filter = apache-noscript
logpath = /var/log/apache2/susdr360_access.log

[apache-overflows]
enabled = true
port = http,https
filter = apache-overflows
logpath = /var/log/apache2/susdr360_error.log
EOF
    
    systemctl restart fail2ban
    systemctl enable fail2ban
    
    log_success "Fail2ban configuré"
}

start_services() {
    log_info "Démarrage des services..."
    
    systemctl start susdr360
    systemctl restart apache2
    
    log_success "Services démarrés"
}

show_summary() {
    log_success "=== Installation SUSDR360 terminée avec succès! ==="
    echo
    echo "Informations de connexion:"
    echo "- URL: http://$(hostname -I | awk '{print $1}')"
    echo "- Répertoire: $SUSDR360_HOME"
    echo "- Utilisateur système: $SUSDR360_USER"
    echo "- Serveur web: Apache"
    echo
    echo "Services installés:"
    echo "- SUSDR360 API: systemctl status susdr360"
    echo "- Apache: systemctl status apache2"
    echo "- PHP-FPM: systemctl status php*-fpm"
    echo
    echo "Logs importants:"
    echo "- SUSDR360: journalctl -u susdr360 -f"
    echo "- Apache accès: tail -f /var/log/apache2/susdr360_access.log"
    echo "- Apache erreur: tail -f /var/log/apache2/susdr360_error.log"
    echo
    echo "Configuration:"
    echo "- Apache: /etc/apache2/sites-available/susdr360.conf"
    echo "- SUSDR360: $SUSDR360_HOME/susdr360/config.yaml"
    echo
    log_warning "N'oubliez pas de configurer les paramètres dans config.yaml avant la première utilisation!"
}

# Fonction principale
main() {
    log_info "Début de l'installation SUSDR360 avec Apache..."
    
    check_root
    check_ubuntu
    
    install_dependencies
    install_python
    install_apache
    create_user
    clone_repository
    install_python_dependencies
    configure_apache
    create_systemd_service
    configure_firewall
    configure_fail2ban
    start_services
    
    show_summary
}

# Exécution du script principal
main "$@"
