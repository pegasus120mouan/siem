#!/bin/bash

# SUSDR 360 - Configuration Production
# Script de configuration avancée pour environnement de production

set -e

# Variables
SUSDR360_HOME="/opt/susdr360"
DOMAIN_NAME=""
EMAIL=""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log_info() { echo -e "${YELLOW}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Demander les informations de configuration
get_config_info() {
    echo "Configuration de SUSDR 360 pour la production"
    echo "=============================================="
    
    read -p "Nom de domaine (ex: siem.votre-entreprise.com): " DOMAIN_NAME
    read -p "Email pour SSL (Let's Encrypt): " EMAIL
    
    if [[ -z "$DOMAIN_NAME" ]]; then
        log_error "Le nom de domaine est requis"
        exit 1
    fi
}

# Configuration SSL avec Let's Encrypt
setup_ssl() {
    log_info "Installation de Certbot pour SSL..."
    
    apt install -y certbot python3-certbot-nginx
    
    log_info "Obtention du certificat SSL pour $DOMAIN_NAME..."
    
    # Modifier la configuration Nginx pour le domaine
    sed -i "s/server_name localhost;/server_name $DOMAIN_NAME;/" /etc/nginx/sites-available/susdr360
    nginx -t && systemctl reload nginx
    
    # Obtenir le certificat
    certbot --nginx -d $DOMAIN_NAME --email $EMAIL --agree-tos --non-interactive
    
    # Configuration du renouvellement automatique
    echo "0 12 * * * /usr/bin/certbot renew --quiet" | crontab -
    
    log_success "SSL configuré pour $DOMAIN_NAME"
}

# Configuration de sécurité avancée
setup_security() {
    log_info "Configuration de sécurité avancée..."
    
    # Fail2ban pour la protection contre les attaques
    apt install -y fail2ban
    
    cat > /etc/fail2ban/jail.local << EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3
EOF

    systemctl enable fail2ban
    systemctl start fail2ban
    
    # Configuration UFW plus stricte
    ufw --force reset
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow 'Nginx Full'
    ufw --force enable
    
    log_success "Sécurité renforcée configurée"
}

# Optimisation des performances
optimize_performance() {
    log_info "Optimisation des performances..."
    
    # Configuration Nginx optimisée
    cat > /etc/nginx/conf.d/performance.conf << EOF
# Optimisations de performance
worker_processes auto;
worker_connections 1024;

# Gzip compression
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

# Cache des fichiers statiques
location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# Limites de taux
limit_req_zone \$binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone \$binary_remote_addr zone=login:10m rate=1r/s;
EOF

    # Configuration PostgreSQL optimisée
    cat >> /etc/postgresql/12/main/postgresql.conf << EOF

# Optimisations SUSDR 360
shared_buffers = 256MB
effective_cache_size = 1GB
maintenance_work_mem = 64MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
EOF

    systemctl restart postgresql
    systemctl restart nginx
    
    log_success "Optimisations appliquées"
}

# Configuration du monitoring
setup_monitoring() {
    log_info "Configuration du monitoring..."
    
    # Installation de htop et iotop
    apt install -y htop iotop nethogs
    
    # Script de monitoring système
    cat > $SUSDR360_HOME/scripts/system_monitor.sh << 'EOF'
#!/bin/bash
LOG_FILE="/opt/susdr360/logs/system_monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# CPU et mémoire
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
MEM_USAGE=$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}')
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | cut -d'%' -f1)

# Services
SUSDR360_STATUS=$(supervisorctl status susdr360 | awk '{print $2}')
NGINX_STATUS=$(systemctl is-active nginx)
POSTGRES_STATUS=$(systemctl is-active postgresql)

echo "$DATE - CPU: ${CPU_USAGE}% | MEM: ${MEM_USAGE}% | DISK: ${DISK_USAGE}% | SUSDR360: $SUSDR360_STATUS | NGINX: $NGINX_STATUS | POSTGRES: $POSTGRES_STATUS" >> $LOG_FILE
EOF

    chmod +x $SUSDR360_HOME/scripts/system_monitor.sh
    
    # Ajouter au cron (toutes les 15 minutes)
    (crontab -l 2>/dev/null; echo "*/15 * * * * $SUSDR360_HOME/scripts/system_monitor.sh") | crontab -
    
    log_success "Monitoring configuré"
}

# Configuration des alertes email
setup_email_alerts() {
    log_info "Configuration des alertes email..."
    
    apt install -y postfix mailutils
    
    # Script d'alerte
    cat > $SUSDR360_HOME/scripts/alert.sh << EOF
#!/bin/bash
ALERT_EMAIL="$EMAIL"
HOSTNAME=\$(hostname)

send_alert() {
    local subject="\$1"
    local message="\$2"
    echo "\$message" | mail -s "SUSDR 360 Alert - \$subject" \$ALERT_EMAIL
}

# Vérifier si SUSDR 360 est en cours d'exécution
if ! supervisorctl status susdr360 | grep -q RUNNING; then
    send_alert "Service Down" "SUSDR 360 service is not running on \$HOSTNAME"
fi

# Vérifier l'utilisation du disque
DISK_USAGE=\$(df -h / | awk 'NR==2{print \$5}' | cut -d'%' -f1)
if [ \$DISK_USAGE -gt 90 ]; then
    send_alert "Disk Space" "Disk usage is \${DISK_USAGE}% on \$HOSTNAME"
fi
EOF

    chmod +x $SUSDR360_HOME/scripts/alert.sh
    
    # Ajouter au cron (toutes les heures)
    (crontab -l 2>/dev/null; echo "0 * * * * $SUSDR360_HOME/scripts/alert.sh") | crontab -
    
    log_success "Alertes email configurées"
}

# Configuration des backups automatiques
setup_backups() {
    log_info "Configuration des backups automatiques..."
    
    # Script de backup complet
    cat > $SUSDR360_HOME/scripts/full_backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/susdr360/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Créer le dossier de backup
mkdir -p $BACKUP_DIR

# Backup de la base de données
pg_dump -h localhost -U susdr360 susdr360_db | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup des configurations
tar -czf $BACKUP_DIR/config_$DATE.tar.gz /opt/susdr360/config/

# Backup des données importantes
tar -czf $BACKUP_DIR/data_$DATE.tar.gz /opt/susdr360/data/ --exclude="*.log"

# Nettoyage des anciens backups
find $BACKUP_DIR -name "*.gz" -mtime +$RETENTION_DAYS -delete

# Log du backup
echo "$(date): Backup completed - db_$DATE.sql.gz, config_$DATE.tar.gz, data_$DATE.tar.gz" >> /opt/susdr360/logs/backup.log
EOF

    chmod +x $SUSDR360_HOME/scripts/full_backup.sh
    
    # Backup quotidien à 2h du matin
    (crontab -l 2>/dev/null; echo "0 2 * * * $SUSDR360_HOME/scripts/full_backup.sh") | crontab -
    
    log_success "Backups automatiques configurés"
}

# Configuration de la rotation des logs
setup_log_rotation() {
    log_info "Configuration de la rotation des logs..."
    
    cat > /etc/logrotate.d/susdr360 << EOF
$SUSDR360_HOME/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 susdr360 susdr360
    postrotate
        supervisorctl restart susdr360 > /dev/null 2>&1 || true
    endscript
}
EOF

    log_success "Rotation des logs configurée"
}

# Test de la configuration
test_configuration() {
    log_info "Test de la configuration..."
    
    # Test SSL
    if [[ -n "$DOMAIN_NAME" ]]; then
        if curl -s https://$DOMAIN_NAME/api/v1/dashboard/stats | grep -q "total_events"; then
            log_success "HTTPS fonctionnel"
        else
            log_error "Problème avec HTTPS"
        fi
    fi
    
    # Test des services
    services=("nginx" "postgresql" "redis-server" "supervisor")
    for service in "${services[@]}"; do
        if systemctl is-active --quiet $service; then
            log_success "$service est actif"
        else
            log_error "$service n'est pas actif"
        fi
    done
    
    # Test de l'application
    if supervisorctl status susdr360 | grep -q RUNNING; then
        log_success "SUSDR 360 est en cours d'exécution"
    else
        log_error "SUSDR 360 n'est pas en cours d'exécution"
    fi
}

# Affichage du résumé final
display_final_summary() {
    echo
    echo "=================================================================="
    echo -e "${GREEN}CONFIGURATION PRODUCTION TERMINÉE${NC}"
    echo "=================================================================="
    echo
    echo "🌐 URLs d'accès:"
    if [[ -n "$DOMAIN_NAME" ]]; then
        echo "   - Dashboard: https://$DOMAIN_NAME/"
        echo "   - API: https://$DOMAIN_NAME/api/v1/"
        echo "   - Documentation: https://$DOMAIN_NAME/api/docs"
    fi
    echo
    echo "🔒 Sécurité:"
    echo "   - SSL/TLS activé avec Let's Encrypt"
    echo "   - Fail2ban configuré"
    echo "   - Firewall UFW actif"
    echo
    echo "📊 Monitoring:"
    echo "   - Monitoring système actif"
    echo "   - Alertes email configurées"
    echo "   - Logs rotatifs configurés"
    echo
    echo "💾 Backups:"
    echo "   - Backup quotidien automatique"
    echo "   - Rétention de 30 jours"
    echo "   - Dossier: $SUSDR360_HOME/backups"
    echo
    echo "🔧 Maintenance:"
    echo "   - Scripts dans: $SUSDR360_HOME/scripts/"
    echo "   - Logs dans: $SUSDR360_HOME/logs/"
    echo "   - Crontab configuré"
    echo
}

# Fonction principale
main() {
    echo "=================================================================="
    echo "SUSDR 360 - Configuration Production"
    echo "=================================================================="
    
    if [[ $EUID -ne 0 ]]; then
        log_error "Ce script doit être exécuté en tant que root"
        exit 1
    fi
    
    get_config_info
    
    log_info "Début de la configuration production..."
    
    setup_ssl
    setup_security
    optimize_performance
    setup_monitoring
    setup_email_alerts
    setup_backups
    setup_log_rotation
    test_configuration
    
    display_final_summary
    
    log_success "Configuration production terminée!"
}

# Exécution
main "$@"
