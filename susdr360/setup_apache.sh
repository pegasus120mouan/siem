#!/bin/bash

# Script d'installation et configuration Apache pour SUSDR 360
# Usage: sudo ./setup_apache.sh

set -e

echo "=== Configuration Apache pour SUSDR 360 ==="

# Vérification des privilèges root
if [[ $EUID -ne 0 ]]; then
   echo "Ce script doit être exécuté en tant que root (sudo)" 
   exit 1
fi

# Variables de configuration
APACHE_SITE_NAME="susdr360"
APACHE_CONF_FILE="/etc/apache2/sites-available/${APACHE_SITE_NAME}.conf"
APP_DIR="/opt/susdr360/app"
SERVER_IP="172.20.200.54"

echo "1. Installation d'Apache et des modules nécessaires..."
apt-get update
apt-get install -y apache2 php-fpm

# Modules Apache nécessaires
echo "2. Activation des modules Apache..."
a2enmod rewrite
a2enmod proxy
a2enmod proxy_http
a2enmod proxy_fcgi
a2enmod headers
a2enmod expires
a2enmod ssl

echo "3. Configuration PHP-FPM..."
# Vérification de la version PHP installée
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "Version PHP détectée: $PHP_VERSION"

# Configuration PHP-FPM
systemctl enable php${PHP_VERSION}-fpm
systemctl start php${PHP_VERSION}-fpm

echo "4. Création du répertoire de l'application..."
mkdir -p $APP_DIR
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR

echo "5. Copie de la configuration Apache..."
# Copier le fichier de configuration Apache
cp apache_susdr360.conf $APACHE_CONF_FILE

# Mise à jour de la version PHP dans la configuration
sed -i "s/php7.4-fpm.sock/php${PHP_VERSION}-fpm.sock/g" $APACHE_CONF_FILE

echo "6. Activation du site SUSDR 360..."
a2ensite $APACHE_SITE_NAME

echo "7. Désactivation du site par défaut..."
a2dissite 000-default

echo "8. Test de la configuration Apache..."
apache2ctl configtest

echo "9. Redémarrage d'Apache..."
systemctl restart apache2
systemctl enable apache2

echo "10. Vérification du statut des services..."
systemctl status apache2 --no-pager -l
systemctl status php${PHP_VERSION}-fpm --no-pager -l

echo "11. Configuration du firewall (si ufw est installé)..."
if command -v ufw &> /dev/null; then
    ufw allow 'Apache Full'
    echo "Règles firewall ajoutées pour Apache"
fi

echo "12. Création des fichiers d'erreur personnalisés..."
cat > $APP_DIR/404.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page non trouvée</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <h1 class="error">404 - Page non trouvée</h1>
    <p>La page que vous recherchez n'existe pas.</p>
    <a href="/">Retour à l'accueil</a>
</body>
</html>
EOF

cat > $APP_DIR/50x.html << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Erreur serveur</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .error { color: #d32f2f; }
    </style>
</head>
<body>
    <h1 class="error">Erreur serveur</h1>
    <p>Une erreur temporaire s'est produite. Veuillez réessayer plus tard.</p>
    <a href="/">Retour à l'accueil</a>
</body>
</html>
EOF

chown www-data:www-data $APP_DIR/*.html

echo "=== Configuration Apache terminée avec succès! ==="
echo ""
echo "Informations importantes:"
echo "- Site web: http://$SERVER_IP"
echo "- Logs d'accès: /var/log/apache2/susdr360_access.log"
echo "- Logs d'erreur: /var/log/apache2/susdr360_error.log"
echo "- Configuration: $APACHE_CONF_FILE"
echo "- Répertoire web: $APP_DIR"
echo ""
echo "Pour démarrer l'API SUSDR 360, exécutez:"
echo "cd /opt/susdr360 && python3 main.py"
echo ""
echo "Commandes utiles:"
echo "- Redémarrer Apache: sudo systemctl restart apache2"
echo "- Voir les logs: sudo tail -f /var/log/apache2/susdr360_error.log"
echo "- Tester la config: sudo apache2ctl configtest"
