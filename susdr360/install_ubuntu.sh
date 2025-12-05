#!/bin/bash

# SUSDR 360 - Script d'Installation Automatique pour Ubuntu 20.04
# Auteur: SAHANALYTICS - Fisher Ouattara
# Usage: sudo bash install_ubuntu.sh

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

install_system_dependencies() {
    log_info "Mise à jour du système..."
    apt update && apt upgrade -y

    log_info "Installation des dépendances système..."
    apt install -y \
        software-properties-common \
        curl \
        wget \
        unzip \
        git \
        htop \
        tree \
        jq \
        build-essential \
        libssl-dev \
        libffi-dev \
        libxml2-dev \
        libxslt1-dev \
        zlib1g-dev \
        libjpeg-dev \
        libpng-dev \
        nginx \
        supervisor \
        redis-server \
        postgresql \
        postgresql-contrib

    log_success "Dépendances système installées"
}

install_python() {
    log_info "Installation de Python 3.9..."
    
    # Vérifier la version Python
    python_version=$(python3 --version 2>&1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    
    if [[ $(echo "$python_version >= 3.9" | bc -l) -eq 0 ]]; then
        log_info "Installation de Python 3.9 depuis PPA..."
        add-apt-repository ppa:deadsnakes/ppa -y
        apt update
        apt install -y python3.9 python3.9-venv python3.9-dev python3-pip
        
        # Créer un lien symbolique
        update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.9 1
    fi

    # Mettre à jour pip
    pip3 install --upgrade pip
    
    log_success "Python installé"
}

install_nodejs() {
    log_info "Installation de Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
    apt install -y nodejs
    
    log_success "Node.js installé: $(node --version)"
}

create_user() {
    log_info "Création de l'utilisateur système $SUSDR360_USER..."
    
    if ! id "$SUSDR360_USER" &>/dev/null; then
        adduser --system --group --home $SUSDR360_HOME $SUSDR360_USER
    fi
    
    # Créer la structure de dossiers
    mkdir -p $SUSDR360_HOME/{app,logs,data,config,backups,scripts}
    chown -R $SUSDR360_USER:$SUSDR360_USER $SUSDR360_HOME
    
    log_success "Utilisateur $SUSDR360_USER créé"
}

clone_repository() {
    log_info "Clonage du repository SUSDR 360..."
    
    if [[ -d "$SUSDR360_HOME/app" ]]; then
        log_warning "Le dossier app existe déjà, sauvegarde..."
        mv $SUSDR360_HOME/app $SUSDR360_HOME/app.backup.$(date +%Y%m%d_%H%M%S)
    fi
    
    sudo -u $SUSDR360_USER git clone $REPO_URL $SUSDR360_HOME/app
    
    log_success "Repository cloné"
}

setup_python_environment() {
    log_info "Configuration de l'environnement Python..."
    
    # Créer l'environnement virtuel
    sudo -u $SUSDR360_USER python3 -m venv $SUSDR360_HOME/venv
    
    # Installer les dépendances
    sudo -u $SUSDR360_USER bash -c "
        source $SUSDR360_HOME/venv/bin/activate
        cd $SUSDR360_HOME/app/susdr360
        pip install -r requirements.txt
        pip install gunicorn uvloop httptools psycopg2-binary
    "
    
    log_success "Environnement Python configuré"
}

configure_database() {
    log_info "Configuration de PostgreSQL..."
    
    # Démarrer PostgreSQL
    systemctl enable postgresql
    systemctl start postgresql
    
    # Créer la base de données et l'utilisateur
    sudo -u postgres psql -c "CREATE USER $SUSDR360_USER WITH PASSWORD 'susdr360_password';"
    sudo -u postgres psql -c "CREATE DATABASE susdr360_db OWNER $SUSDR360_USER;"
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE susdr360_db TO $SUSDR360_USER;"
    
    log_success "Base de données configurée"
}

configure_redis() {
    log_info "Configuration de Redis..."
    
    systemctl enable redis-server
    systemctl start redis-server
    
    # Test Redis
    if redis-cli ping | grep -q "PONG"; then
        log_success "Redis configuré et fonctionnel"
    else
        log_error "Problème avec Redis"
    fi
}

configure_application() {
    log_info "Configuration de l'application SUSDR 360..."
    
    # Copier et adapter la configuration
    sudo -u $SUSDR360_USER cp $SUSDR360_HOME/app/susdr360/config.yaml $SUSDR360_HOME/config/susdr360.yaml
    
    # Modifier la configuration pour la production
    cat > $SUSDR360_HOME/config/susdr360.yaml << EOF
system:
  name: "SUSDR 360"
  version: "1.0.0"
  debug: false
  data_dir: "$SUSDR360_HOME/data"
  log_level: "INFO"

api:
  host: "127.0.0.1"
  port: 8000
  workers: 4

storage:
  database:
    type: "postgresql"
    host: "localhost"
    port: 5432
    database: "susdr360_db"
    username: "$SUSDR360_USER"
    password: "susdr360_password"

agents:
  windows:
    enabled: false
  syslog:
    enabled: true
    host: "0.0.0.0"
    port: 514

notifications:
  email:
    enabled: false
EOF

    chown $SUSDR360_USER:$SUSDR360_USER $SUSDR360_HOME/config/susdr360.yaml
    chmod 640 $SUSDR360_HOME/config/susdr360.yaml
    
    log_success "Configuration de l'application terminée"
}

configure_supervisor() {
    log_info "Configuration de Supervisor..."
    
    cat > /etc/supervisor/conf.d/susdr360.conf << EOF
[program:susdr360]
command=$SUSDR360_HOME/venv/bin/python $SUSDR360_HOME/app/susdr360/start_simple.py
directory=$SUSDR360_HOME/app/susdr360
user=$SUSDR360_USER
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=$SUSDR360_HOME/logs/susdr360.log
environment=PATH="$SUSDR360_HOME/venv/bin",PYTHONPATH="$SUSDR360_HOME/app"

[program:susdr360-worker]
command=$SUSDR360_HOME/venv/bin/celery worker -A susdr360.tasks --loglevel=info
directory=$SUSDR360_HOME/app/susdr360
user=$SUSDR360_USER
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=$SUSDR360_HOME/logs/worker.log
environment=PATH="$SUSDR360_HOME/venv/bin",PYTHONPATH="$SUSDR360_HOME/app"
EOF

    systemctl enable supervisor
    systemctl start supervisor
    supervisorctl reread
    supervisorctl update
    
    log_success "Supervisor configuré"
}

configure_nginx() {
    log_info "Configuration de Nginx..."
    
    cat > /etc/nginx/sites-available/susdr360 << EOF
server {
    listen 80;
    server_name localhost;

    access_log /var/log/nginx/susdr360_access.log;
    error_log /var/log/nginx/susdr360_error.log;

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

    # Interface web SUSDR 360
    location /susdr360/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    # Dashboard PHP
    location / {
        root $SUSDR360_HOME/app;
        index dashboard.php index.php index.html;
        try_files \$uri \$uri/ =404;
    }

    # Fichiers statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        root $SUSDR360_HOME/app;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # Activer le site
    ln -sf /etc/nginx/sites-available/susdr360 /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Tester et redémarrer Nginx
    nginx -t
    systemctl enable nginx
    systemctl restart nginx
    
    log_success "Nginx configuré"
}

configure_firewall() {
    log_info "Configuration du firewall..."
    
    ufw --force enable
    ufw allow ssh
    ufw allow 'Nginx Full'
    ufw allow 8000  # Port API pour tests
    
    log_success "Firewall configuré"
}

create_scripts() {
    log_info "Création des scripts de maintenance..."
    
    # Script de health check
    cat > $SUSDR360_HOME/scripts/health_check.sh << 'EOF'
#!/bin/bash
API_URL="http://localhost:8000/health"
LOG_FILE="/opt/susdr360/logs/health_check.log"

response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL)

if [ $response -eq 200 ]; then
    echo "$(date): SUSDR 360 OK" >> $LOG_FILE
else
    echo "$(date): SUSDR 360 ERROR - HTTP $response" >> $LOG_FILE
    supervisorctl restart susdr360
fi
EOF

    # Script de backup
    cat > $SUSDR360_HOME/scripts/backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/susdr360/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup base de données
pg_dump -h localhost -U susdr360 susdr360_db > $BACKUP_DIR/db_$DATE.sql

# Backup configuration
tar -czf $BACKUP_DIR/config_$DATE.tar.gz /opt/susdr360/config/

# Nettoyage des anciens backups (garder 7 jours)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
EOF

    chmod +x $SUSDR360_HOME/scripts/*.sh
    chown -R $SUSDR360_USER:$SUSDR360_USER $SUSDR360_HOME/scripts
    
    log_success "Scripts de maintenance créés"
}

configure_cron() {
    log_info "Configuration des tâches cron..."
    
    # Ajouter les tâches cron pour l'utilisateur susdr360
    sudo -u $SUSDR360_USER crontab -l 2>/dev/null | {
        cat
        echo "# SUSDR 360 - Health check toutes les 5 minutes"
        echo "*/5 * * * * $SUSDR360_HOME/scripts/health_check.sh"
        echo "# SUSDR 360 - Backup quotidien à 3h"
        echo "0 3 * * * $SUSDR360_HOME/scripts/backup.sh"
    } | sudo -u $SUSDR360_USER crontab -
    
    log_success "Tâches cron configurées"
}

start_services() {
    log_info "Démarrage des services..."
    
    systemctl start postgresql
    systemctl start redis-server
    systemctl start nginx
    systemctl start supervisor
    
    # Démarrer l'application SUSDR 360
    supervisorctl start susdr360
    
    log_success "Services démarrés"
}

run_tests() {
    log_info "Tests de fonctionnement..."
    
    sleep 5  # Attendre que les services démarrent
    
    # Test API
    if curl -s http://localhost:8000/health | grep -q "healthy"; then
        log_success "API SUSDR 360 fonctionnelle"
    else
        log_error "Problème avec l'API SUSDR 360"
    fi
    
    # Test Nginx
    if curl -s http://localhost/api/v1/dashboard/stats | grep -q "total_events"; then
        log_success "Proxy Nginx fonctionnel"
    else
        log_warning "Problème avec le proxy Nginx"
    fi
    
    log_success "Tests terminés"
}

display_summary() {
    echo
    echo "=================================================================="
    echo -e "${GREEN}INSTALLATION SUSDR 360 TERMINÉE${NC}"
    echo "=================================================================="
    echo
    echo "🌐 URLs d'accès:"
    echo "   - Dashboard: http://$(hostname -I | awk '{print $1}')/"
    echo "   - API: http://$(hostname -I | awk '{print $1}')/api/v1/"
    echo "   - Documentation: http://$(hostname -I | awk '{print $1}')/api/docs"
    echo
    echo "📁 Dossiers importants:"
    echo "   - Application: $SUSDR360_HOME/app"
    echo "   - Configuration: $SUSDR360_HOME/config"
    echo "   - Logs: $SUSDR360_HOME/logs"
    echo "   - Données: $SUSDR360_HOME/data"
    echo
    echo "🔧 Commandes utiles:"
    echo "   - Statut services: sudo supervisorctl status"
    echo "   - Logs application: sudo tail -f $SUSDR360_HOME/logs/susdr360.log"
    echo "   - Redémarrer: sudo supervisorctl restart susdr360"
    echo
    echo "📋 Prochaines étapes:"
    echo "   1. Configurer votre domaine dans Nginx"
    echo "   2. Installer un certificat SSL avec certbot"
    echo "   3. Adapter la configuration selon vos besoins"
    echo "   4. Configurer les agents de collecte"
    echo
}

# Fonction principale
main() {
    echo "=================================================================="
    echo "SUSDR 360 - Installation Automatique pour Ubuntu 20.04"
    echo "SAHANALYTICS - Fisher Ouattara"
    echo "=================================================================="
    echo
    
    check_root
    check_ubuntu
    
    log_info "Début de l'installation..."
    
    install_system_dependencies
    install_python
    install_nodejs
    create_user
    clone_repository
    setup_python_environment
    configure_database
    configure_redis
    configure_application
    configure_supervisor
    configure_nginx
    configure_firewall
    create_scripts
    configure_cron
    start_services
    run_tests
    
    display_summary
    
    log_success "Installation terminée avec succès!"
}

# Exécution du script
main "$@"
