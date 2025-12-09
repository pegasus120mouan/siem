# Installation SUSDR 360 avec Apache sur Ubuntu 20.04

## Vue d'ensemble

Ce guide décrit l'installation complète de SUSDR 360 en utilisant Apache comme serveur web au lieu de Nginx. Cette configuration offre une alternative robuste avec les mêmes fonctionnalités.

## Prérequis

- Ubuntu 20.04 LTS ou version ultérieure
- Accès root (sudo)
- Connexion Internet
- Au moins 2 GB de RAM
- 10 GB d'espace disque libre

## Installation automatique

### Option 1: Script d'installation complet

```bash
# Télécharger et exécuter le script d'installation
wget https://raw.githubusercontent.com/pegasus120mouan/siem/main/susdr360/install_ubuntu_apache.sh
chmod +x install_ubuntu_apache.sh
sudo ./install_ubuntu_apache.sh
```

### Option 2: Installation manuelle étape par étape

#### 1. Mise à jour du système

```bash
sudo apt update && sudo apt upgrade -y
```

#### 2. Installation des dépendances

```bash
sudo apt install -y curl wget git unzip software-properties-common \
    apt-transport-https ca-certificates gnupg lsb-release htop vim ufw fail2ban
```

#### 3. Installation de Python 3.9

```bash
sudo apt install -y python3.9 python3.9-venv python3.9-dev python3-pip
python3.9 -m pip install --upgrade pip
```

#### 4. Installation d'Apache et PHP-FPM

```bash
sudo apt install -y apache2 php-fpm php-cli php-mysql php-xml php-mbstring php-curl php-json

# Activation des modules Apache nécessaires
sudo a2enmod rewrite proxy proxy_http proxy_fcgi headers expires ssl

# Configuration PHP-FPM
sudo systemctl enable php*-fpm
sudo systemctl start php*-fpm
```

#### 5. Création de l'utilisateur système

```bash
sudo useradd -r -m -d /opt/susdr360 -s /bin/bash susdr360
```

#### 6. Clonage du repository

```bash
sudo -u susdr360 git clone https://github.com/pegasus120mouan/siem.git /opt/susdr360/siem
sudo cp -r /opt/susdr360/siem/susdr360 /opt/susdr360/
sudo mkdir -p /opt/susdr360/app
sudo chown -R www-data:www-data /opt/susdr360/app
```

#### 7. Installation des dépendances Python

```bash
cd /opt/susdr360/susdr360
sudo -u susdr360 python3.9 -m venv venv
sudo -u susdr360 ./venv/bin/pip install -r requirements.txt
```

#### 8. Configuration d'Apache

```bash
# Copier la configuration Apache
sudo cp /opt/susdr360/susdr360/apache_susdr360.conf /etc/apache2/sites-available/susdr360.conf

# Activer le site et désactiver le site par défaut
sudo a2ensite susdr360
sudo a2dissite 000-default

# Tester et redémarrer Apache
sudo apache2ctl configtest
sudo systemctl restart apache2
```

## Configuration

### Fichier de configuration Apache

Le fichier `/etc/apache2/sites-available/susdr360.conf` contient:

- **Proxy vers l'API FastAPI** sur le port 8000
- **Configuration PHP-FPM** pour les fichiers PHP
- **Règles de sécurité** (headers, restrictions d'accès)
- **Cache des fichiers statiques**
- **Gestion des erreurs personnalisées**

### Configuration SUSDR 360

Éditez le fichier `/opt/susdr360/susdr360/config.yaml`:

```yaml
# Configuration de base
server:
  host: "0.0.0.0"
  port: 8000
  debug: false

# Base de données
database:
  type: "sqlite"  # ou "postgresql", "mysql"
  path: "/opt/susdr360/data/susdr360.db"

# Sécurité
security:
  secret_key: "votre-clé-secrète-très-longue"
  jwt_expiration: 3600
```

### Service systemd

Créez le service `/etc/systemd/system/susdr360.service`:

```ini
[Unit]
Description=SUSDR 360 Security Information and Event Management
After=network.target apache2.service

[Service]
Type=simple
User=susdr360
Group=susdr360
WorkingDirectory=/opt/susdr360/susdr360
Environment=PATH=/opt/susdr360/susdr360/venv/bin
ExecStart=/opt/susdr360/susdr360/venv/bin/python main.py
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Activez et démarrez le service:

```bash
sudo systemctl daemon-reload
sudo systemctl enable susdr360
sudo systemctl start susdr360
```

## Sécurité

### Firewall (UFW)

```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 'Apache Full'
sudo ufw allow 8000  # API SUSDR360
```

### Fail2ban

Configuration dans `/etc/fail2ban/jail.local`:

```ini
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
```

## Vérification de l'installation

### 1. Vérifier les services

```bash
# Apache
sudo systemctl status apache2

# PHP-FPM
sudo systemctl status php*-fpm

# SUSDR360
sudo systemctl status susdr360
```

### 2. Tester l'accès web

```bash
# Page principale
curl -I http://localhost/

# API Health check
curl http://localhost/health

# Documentation API
curl http://localhost/docs
```

### 3. Vérifier les logs

```bash
# Logs Apache
sudo tail -f /var/log/apache2/susdr360_access.log
sudo tail -f /var/log/apache2/susdr360_error.log

# Logs SUSDR360
sudo journalctl -u susdr360 -f
```

## Dépannage

### Problèmes courants

#### Apache ne démarre pas

```bash
# Vérifier la configuration
sudo apache2ctl configtest

# Vérifier les logs
sudo journalctl -u apache2 -f
```

#### Erreur 502 Bad Gateway

```bash
# Vérifier que l'API SUSDR360 fonctionne
sudo systemctl status susdr360
curl http://localhost:8000/health

# Vérifier la configuration du proxy
sudo apache2ctl -S
```

#### Problèmes de permissions

```bash
# Corriger les permissions
sudo chown -R www-data:www-data /opt/susdr360/app
sudo chown -R susdr360:susdr360 /opt/susdr360/susdr360
sudo chmod -R 755 /opt/susdr360/app
```

### Commandes utiles

```bash
# Redémarrer tous les services
sudo systemctl restart apache2 php*-fpm susdr360

# Voir la configuration Apache active
sudo apache2ctl -S

# Tester la configuration Apache
sudo apache2ctl configtest

# Voir les modules Apache chargés
sudo apache2ctl -M

# Recharger la configuration Apache sans redémarrage
sudo systemctl reload apache2
```

## Migration depuis Nginx

Si vous migrez depuis une installation Nginx existante:

### 1. Arrêter Nginx

```bash
sudo systemctl stop nginx
sudo systemctl disable nginx
```

### 2. Installer Apache

```bash
sudo ./setup_apache.sh
```

### 3. Migrer les données

```bash
# Les données de l'application restent dans /opt/susdr360
# Seule la configuration du serveur web change
```

### 4. Mettre à jour les URLs

Vérifiez que toutes les URLs dans votre configuration pointent vers Apache au lieu de Nginx.

## Performance et optimisation

### Configuration Apache pour la production

Éditez `/etc/apache2/apache2.conf`:

```apache
# Optimisations pour la production
ServerTokens Prod
ServerSignature Off

# Limites de ressources
StartServers 2
MinSpareServers 6
MaxSpareServers 12
MaxRequestWorkers 150
ThreadsPerChild 25

# Compression
LoadModule deflate_module modules/mod_deflate.so
<Location />
    SetOutputFilter DEFLATE
    SetEnvIfNoCase Request_URI \
        \.(?:gif|jpe?g|png)$ no-gzip dont-vary
    SetEnvIfNoCase Request_URI \
        \.(?:exe|t?gz|zip|bz2|sit|rar)$ no-gzip dont-vary
</Location>
```

### Monitoring

```bash
# Surveiller les performances Apache
sudo apt install apache2-utils
ab -n 1000 -c 10 http://localhost/

# Surveiller les ressources système
htop
iotop
```

## Support

Pour obtenir de l'aide:

1. Consultez les logs d'erreur
2. Vérifiez la documentation officielle Apache
3. Contactez l'équipe SAHANALYTICS

## Changelog

- **v1.0**: Configuration initiale Apache pour SUSDR 360
- **v1.1**: Ajout de la sécurité Fail2ban
- **v1.2**: Optimisations de performance
