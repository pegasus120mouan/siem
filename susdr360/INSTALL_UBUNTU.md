# Installation SUSDR 360 sur Ubuntu 20.04

## 🖥️ Prérequis Matériels

### Configuration Minimale
- **CPU**: 2 cores (2.0 GHz)
- **RAM**: 4 GB
- **Stockage**: 50 GB d'espace libre
- **Réseau**: Connexion Internet stable

### Configuration Recommandée
- **CPU**: 4+ cores (2.5+ GHz)
- **RAM**: 8+ GB
- **Stockage**: 100+ GB SSD
- **Réseau**: 1 Gbps

## 🔧 Installation des Dépendances Système

### 1. Mise à jour du système
```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Installation de Python 3.9+
```bash
# Vérifier la version Python
python3 --version

# Si Python < 3.9, installer une version plus récente
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:deadsnakes/ppa -y
sudo apt update
sudo apt install python3.9 python3.9-venv python3.9-dev -y

# Créer un lien symbolique (optionnel)
sudo update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.9 1
```

### 3. Installation de pip et outils Python
```bash
sudo apt install python3-pip python3-venv python3-dev -y
pip3 install --upgrade pip
```

### 4. Installation de Git
```bash
sudo apt install git -y
```

### 5. Installation de Node.js (pour l'interface web)
```bash
# Installation via NodeSource
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install nodejs -y

# Vérifier l'installation
node --version
npm --version
```

### 6. Installation de Redis (optionnel mais recommandé)
```bash
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Test Redis
redis-cli ping
```

### 7. Installation de PostgreSQL (optionnel, alternative à SQLite)
```bash
sudo apt install postgresql postgresql-contrib -y
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Créer une base de données pour SUSDR 360
sudo -u postgres createuser --interactive susdr360
sudo -u postgres createdb susdr360_db -O susdr360
```

### 8. Installation d'Nginx (serveur web)
```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx

# Vérifier le statut
sudo systemctl status nginx
```

### 9. Installation de Supervisor (gestion des processus)
```bash
sudo apt install supervisor -y
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 10. Installation d'outils système
```bash
sudo apt install -y \
    curl \
    wget \
    unzip \
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
    libpng-dev
```

## 📦 Déploiement de l'Application

### 1. Création de l'utilisateur système
```bash
# Créer un utilisateur dédié
sudo adduser --system --group --home /opt/susdr360 susdr360

# Créer les dossiers nécessaires
sudo mkdir -p /opt/susdr360/{app,logs,data,config}
sudo chown -R susdr360:susdr360 /opt/susdr360
```

### 2. Clonage du repository
```bash
# Se connecter en tant qu'utilisateur susdr360
sudo -u susdr360 bash

# Cloner le projet
cd /opt/susdr360
git clone https://github.com/pegasus120mouan/siem.git app
cd app
```

### 3. Configuration de l'environnement Python
```bash
# Créer l'environnement virtuel
python3 -m venv /opt/susdr360/venv

# Activer l'environnement
source /opt/susdr360/venv/bin/activate

# Installer les dépendances
cd /opt/susdr360/app/susdr360
pip install -r requirements.txt

# Installer des dépendances supplémentaires pour Ubuntu
pip install gunicorn uvloop httptools
```

### 4. Configuration de l'application
```bash
# Copier le fichier de configuration
cp config.yaml /opt/susdr360/config/susdr360.yaml

# Éditer la configuration pour la production
nano /opt/susdr360/config/susdr360.yaml
```

### Configuration Production (susdr360.yaml)
```yaml
system:
  name: "SUSDR 360"
  version: "1.0.0"
  debug: false
  data_dir: "/opt/susdr360/data"
  log_level: "INFO"

api:
  host: "127.0.0.1"  # Nginx fera le proxy
  port: 8000
  workers: 4

storage:
  database:
    type: "postgresql"  # ou "sqlite"
    host: "localhost"
    port: 5432
    database: "susdr360_db"
    username: "susdr360"
    password: "votre_mot_de_passe"

# ... reste de la configuration
```

## 🔧 Configuration des Services

### 1. Configuration Supervisor
```bash
sudo nano /etc/supervisor/conf.d/susdr360.conf
```

Contenu du fichier supervisor :
```ini
[program:susdr360]
command=/opt/susdr360/venv/bin/python /opt/susdr360/app/susdr360/start_simple.py
directory=/opt/susdr360/app/susdr360
user=susdr360
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/opt/susdr360/logs/susdr360.log
environment=PATH="/opt/susdr360/venv/bin"

[program:susdr360-worker]
command=/opt/susdr360/venv/bin/python -m celery worker -A susdr360.tasks
directory=/opt/susdr360/app/susdr360
user=susdr360
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/opt/susdr360/logs/worker.log
environment=PATH="/opt/susdr360/venv/bin"
```

### 2. Configuration Nginx
```bash
sudo nano /etc/nginx/sites-available/susdr360
```

Configuration Nginx :
```nginx
server {
    listen 80;
    server_name votre-domaine.com;  # Remplacer par votre domaine

    # Logs
    access_log /var/log/nginx/susdr360_access.log;
    error_log /var/log/nginx/susdr360_error.log;

    # Sécurité
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";

    # API SUSDR 360
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Interface web SUSDR 360
    location /susdr360/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Dashboard PHP existant
    location / {
        root /opt/susdr360/app;
        index dashboard.php index.php index.html;
        try_files $uri $uri/ =404;
    }

    # PHP
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Fichiers statiques
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 3. Activation des services
```bash
# Activer le site Nginx
sudo ln -s /etc/nginx/sites-available/susdr360 /etc/nginx/sites-enabled/
sudo nginx -t  # Tester la configuration
sudo systemctl reload nginx

# Recharger Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start susdr360
```

## 🔒 Sécurisation

### 1. Firewall (UFW)
```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw allow 8000  # Port API (temporaire pour tests)
```

### 2. SSL/TLS avec Let's Encrypt
```bash
# Installation Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtenir un certificat SSL
sudo certbot --nginx -d votre-domaine.com

# Renouvellement automatique
sudo crontab -e
# Ajouter : 0 12 * * * /usr/bin/certbot renew --quiet
```

### 3. Permissions et sécurité
```bash
# Permissions strictes
sudo chmod 750 /opt/susdr360
sudo chmod 640 /opt/susdr360/config/susdr360.yaml

# Rotation des logs
sudo nano /etc/logrotate.d/susdr360
```

Contenu logrotate :
```
/opt/susdr360/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 susdr360 susdr360
    postrotate
        supervisorctl restart susdr360
    endscript
}
```

## 🚀 Démarrage et Tests

### 1. Démarrage des services
```bash
# Démarrer tous les services
sudo systemctl start nginx
sudo systemctl start postgresql  # si utilisé
sudo systemctl start redis-server
sudo supervisorctl start all

# Vérifier les statuts
sudo supervisorctl status
sudo systemctl status nginx
```

### 2. Tests de fonctionnement
```bash
# Test API
curl http://localhost:8000/health

# Test via Nginx
curl http://localhost/api/v1/dashboard/stats

# Logs en temps réel
sudo tail -f /opt/susdr360/logs/susdr360.log
```

## 📊 Monitoring et Maintenance

### 1. Scripts de monitoring
```bash
# Créer un script de monitoring
sudo nano /opt/susdr360/scripts/health_check.sh
```

Script de monitoring :
```bash
#!/bin/bash
# Health check SUSDR 360

API_URL="http://localhost:8000/health"
LOG_FILE="/opt/susdr360/logs/health_check.log"

response=$(curl -s -o /dev/null -w "%{http_code}" $API_URL)

if [ $response -eq 200 ]; then
    echo "$(date): SUSDR 360 OK" >> $LOG_FILE
else
    echo "$(date): SUSDR 360 ERROR - HTTP $response" >> $LOG_FILE
    # Redémarrer le service
    supervisorctl restart susdr360
fi
```

### 2. Crontab pour maintenance
```bash
sudo crontab -e
```

Tâches cron :
```
# Health check toutes les 5 minutes
*/5 * * * * /opt/susdr360/scripts/health_check.sh

# Nettoyage des logs anciens
0 2 * * * find /opt/susdr360/logs -name "*.log" -mtime +30 -delete

# Backup de la base de données (si PostgreSQL)
0 3 * * * pg_dump susdr360_db > /opt/susdr360/backups/db_$(date +\%Y\%m\%d).sql
```

## 🔧 Dépannage

### Commandes utiles
```bash
# Logs de l'application
sudo tail -f /opt/susdr360/logs/susdr360.log

# Statut des services
sudo supervisorctl status
sudo systemctl status nginx

# Redémarrage des services
sudo supervisorctl restart susdr360
sudo systemctl restart nginx

# Test de connectivité
curl -I http://localhost:8000/health
netstat -tlnp | grep :8000
```

### Problèmes courants
1. **Port 8000 occupé** : `sudo lsof -i :8000`
2. **Permissions** : `sudo chown -R susdr360:susdr360 /opt/susdr360`
3. **Dépendances Python** : Réinstaller dans le venv
4. **Base de données** : Vérifier les credentials et la connectivité

## 📋 Checklist de Déploiement

- [ ] Système Ubuntu 20.04 à jour
- [ ] Python 3.9+ installé
- [ ] Dépendances système installées
- [ ] Utilisateur susdr360 créé
- [ ] Repository cloné
- [ ] Environnement virtuel configuré
- [ ] Configuration adaptée à la production
- [ ] Services Supervisor configurés
- [ ] Nginx configuré et testé
- [ ] Firewall configuré
- [ ] SSL/TLS activé (production)
- [ ] Monitoring en place
- [ ] Backups configurés
- [ ] Tests de fonctionnement réussis

## 🎯 URLs d'accès

Après installation :
- **Dashboard principal** : `https://votre-domaine.com/`
- **API SUSDR 360** : `https://votre-domaine.com/api/v1/`
- **Documentation API** : `https://votre-domaine.com/api/docs`
- **Interface SUSDR 360** : `https://votre-domaine.com/susdr360/web`
