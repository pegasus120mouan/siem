#!/bin/bash

# Script de configuration Nginx pour SUSDR 360
# Usage: sudo bash setup_nginx.sh

set -e

# Variables
SERVER_IP="172.20.200.54"  # Remplacer par votre IP
SUSDR360_PATH="/opt/susdr360/app"
NGINX_SITE="susdr360"

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() { echo -e "${YELLOW}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Vérifier les privilèges root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Ce script doit être exécuté avec sudo"
        exit 1
    fi
}

# Installer Nginx et PHP-FPM
install_nginx_php() {
    log_info "Installation de Nginx et PHP-FPM..."
    
    apt update
    apt install -y nginx php-fpm php-cli php-curl php-json php-mbstring php-xml
    
    # Démarrer les services
    systemctl enable nginx
    systemctl enable php7.4-fpm  # ou php8.0-fpm selon la version
    systemctl start nginx
    systemctl start php7.4-fpm
    
    log_success "Nginx et PHP-FPM installés"
}

# Créer la configuration du site
create_site_config() {
    log_info "Création de la configuration du site..."
    
    cat > /etc/nginx/sites-available/$NGINX_SITE << EOF
server {
    listen 80;
    server_name $SERVER_IP;
    
    access_log /var/log/nginx/susdr360_access.log;
    error_log /var/log/nginx/susdr360_error.log;
    
    root $SUSDR360_PATH;
    index dashboard.php index.php index.html;
    
    # Sécurité
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    
    # API SUSDR 360
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    # Health check
    location /health {
        proxy_pass http://127.0.0.1:8000/health;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
    }
    
    # Documentation API
    location /docs {
        proxy_pass http://127.0.0.1:8000/docs;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
    }
    
    # Interface web SUSDR 360
    location /susdr360/ {
        proxy_pass http://127.0.0.1:8000/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
    }
    
    # Dashboard PHP
    location / {
        try_files \$uri \$uri/ /dashboard.php?\$query_string;
    }
    
    # PHP
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Fichiers statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Sécurité
    location ~ /\. {
        deny all;
    }
}
EOF

    log_success "Configuration créée"
}

# Activer le site
enable_site() {
    log_info "Activation du site..."
    
    # Désactiver le site par défaut
    if [[ -L /etc/nginx/sites-enabled/default ]]; then
        rm /etc/nginx/sites-enabled/default
    fi
    
    # Activer notre site
    ln -sf /etc/nginx/sites-available/$NGINX_SITE /etc/nginx/sites-enabled/
    
    # Tester la configuration
    nginx -t
    
    if [[ $? -eq 0 ]]; then
        systemctl reload nginx
        log_success "Site activé avec succès"
    else
        log_error "Erreur dans la configuration Nginx"
        exit 1
    fi
}

# Créer les dossiers nécessaires
create_directories() {
    log_info "Création des dossiers..."
    
    # Créer le dossier de l'application s'il n'existe pas
    mkdir -p $SUSDR360_PATH
    
    # Créer une page d'index temporaire si nécessaire
    if [[ ! -f $SUSDR360_PATH/index.html ]]; then
        cat > $SUSDR360_PATH/index.html << EOF
<!DOCTYPE html>
<html>
<head>
    <title>SUSDR 360 - Chargement...</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .loading { color: #007bff; }
    </style>
</head>
<body>
    <h1>🛡️ SUSDR 360</h1>
    <p class="loading">Système en cours de démarrage...</p>
    <p><a href="/api/health">Vérifier l'API</a> | <a href="/docs">Documentation</a></p>
</body>
</html>
EOF
    fi
    
    # Permissions
    chown -R www-data:www-data $SUSDR360_PATH
    chmod -R 755 $SUSDR360_PATH
    
    log_success "Dossiers créés"
}

# Configurer le firewall
configure_firewall() {
    log_info "Configuration du firewall..."
    
    # Installer UFW si pas présent
    apt install -y ufw
    
    # Configurer UFW
    ufw --force enable
    ufw allow ssh
    ufw allow 'Nginx Full'
    ufw allow 8000  # Port API pour tests directs
    
    log_success "Firewall configuré"
}

# Tester la configuration
test_configuration() {
    log_info "Test de la configuration..."
    
    # Attendre que Nginx démarre
    sleep 2
    
    # Test de base
    if curl -s http://localhost | grep -q "SUSDR 360"; then
        log_success "Site web accessible"
    else
        log_error "Problème d'accès au site web"
    fi
    
    # Test de l'API (si SUSDR 360 est démarré)
    if curl -s http://localhost:8000/health 2>/dev/null | grep -q "healthy"; then
        log_success "API SUSDR 360 accessible"
        
        # Test du proxy
        if curl -s http://localhost/health | grep -q "healthy"; then
            log_success "Proxy Nginx vers API fonctionnel"
        else
            log_error "Problème avec le proxy Nginx"
        fi
    else
        log_info "API SUSDR 360 non démarrée (normal si pas encore installée)"
    fi
}

# Afficher le résumé
display_summary() {
    echo
    echo "=================================================================="
    echo -e "${GREEN}CONFIGURATION NGINX TERMINÉE${NC}"
    echo "=================================================================="
    echo
    echo "🌐 URLs d'accès:"
    echo "   - Site principal: http://$SERVER_IP/"
    echo "   - API Health: http://$SERVER_IP/health"
    echo "   - Documentation: http://$SERVER_IP/docs"
    echo "   - API directe: http://$SERVER_IP/api/v1/"
    echo
    echo "📁 Dossiers:"
    echo "   - Site web: $SUSDR360_PATH"
    echo "   - Configuration Nginx: /etc/nginx/sites-available/$NGINX_SITE"
    echo "   - Logs: /var/log/nginx/"
    echo
    echo "🔧 Commandes utiles:"
    echo "   - Redémarrer Nginx: sudo systemctl restart nginx"
    echo "   - Tester config: sudo nginx -t"
    echo "   - Voir logs: sudo tail -f /var/log/nginx/susdr360_error.log"
    echo
    echo "📋 Prochaines étapes:"
    echo "   1. Déployer votre application SUSDR 360"
    echo "   2. Démarrer l'API sur le port 8000"
    echo "   3. Copier vos fichiers PHP dans $SUSDR360_PATH"
    echo
}

# Fonction principale
main() {
    echo "=================================================================="
    echo "Configuration Nginx pour SUSDR 360"
    echo "=================================================================="
    
    check_root
    
    log_info "Début de la configuration..."
    
    install_nginx_php
    create_directories
    create_site_config
    enable_site
    configure_firewall
    test_configuration
    
    display_summary
    
    log_success "Configuration terminée!"
}

# Exécution
main "$@"
