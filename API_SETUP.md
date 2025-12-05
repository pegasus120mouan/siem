# Configuration API AbuseIPDB

## 🔑 Obtenir une clé API

1. **Créer un compte** sur [AbuseIPDB](https://www.abuseipdb.com/register)
2. **Vérifier votre email** et vous connecter
3. **Aller dans votre compte** : [API Settings](https://www.abuseipdb.com/account/api#create-api-key)
4. **Créer une nouvelle clé API** en cliquant sur "Create Key"
5. **Copier la clé** générée (elle ressemble à : `a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6`)

## 🚀 Configuration dans le SIEM

1. **Ouvrir votre SIEM** : `http://localhost/siem/`
2. **Aller dans "OSINT Analysis"**
3. **Coller votre clé API** dans le champ "Configuration API"
4. **Cliquer sur "Configurer"**
5. **Confirmer** si vous voulez sauvegarder la clé localement

## 📊 Utilisation

### Analyse d'IP avec API
```
Entrez une IP : 8.8.8.8
Type : IP (auto-détecté)
Cliquez sur "Analyser"
```

### Données obtenues
- **Score d'abus** : Pourcentage de confiance (0-100%)
- **Nombre de rapports** : Combien de fois l'IP a été signalée
- **Géolocalisation** : Pays, ISP, organisation
- **Type d'usage** : Résidentiel, commercial, datacenter, etc.
- **Whitelist** : Si l'IP est dans une liste blanche

## 🔒 Sécurité

### ⚠️ Important
- **Ne partagez jamais** votre clé API
- **Révoquezla** si elle est compromise
- **Utilisez HTTPS** uniquement
- **Limitez l'accès** aux machines de confiance

### 🛡️ Limitations
- **1000 requêtes/jour** pour le plan gratuit
- **Rate limiting** : 1 requête/seconde
- **CORS** : Peut nécessiter un proxy pour certains navigateurs

## 🔧 Dépannage

### Erreurs courantes

#### "HTTP 401: Unauthorized"
- Vérifiez que votre clé API est correcte
- Assurez-vous qu'elle n'a pas expiré

#### "HTTP 429: Too Many Requests"
- Vous avez dépassé la limite de requêtes
- Attendez avant de refaire des requêtes

#### "CORS Error"
- Utilisez un serveur local ou un proxy CORS
- Ou installez une extension navigateur pour désactiver CORS

### Mode fallback
Si l'API ne fonctionne pas, le système utilise automatiquement :
- Base de données locale d'IPs connues
- Données simulées pour les tests
- Géolocalisation approximative

## 📈 Fonctionnalités avancées

### Intégration avec Attack Map
- Les IPs analysées sont **automatiquement géolocalisées**
- **Corrélation** avec les attaques détectées
- **Alertes** basées sur le score de réputation

### Historique
- **Toutes les analyses** sont sauvegardées
- **Filtrage** par niveau de risque
- **Export** possible des données

## 🌐 API Alternative

Si AbuseIPDB ne fonctionne pas, vous pouvez utiliser :
- **VirusTotal API**
- **IPinfo.io**
- **MaxMind GeoIP**
- **Shodan API**

Le code est facilement adaptable pour d'autres APIs de réputation IP.
