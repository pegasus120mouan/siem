# SUSDR 360 - Système Unifié de Surveillance, Détection et Réponse

![SUSDR 360 Logo](https://via.placeholder.com/800x200/1e293b/60a5fa?text=SUSDR+360)

## 🛡️ Vue d'ensemble

**SUSDR 360** est une plateforme de cybersécurité unifiée développée par **SAHANALYTICS** qui combine les fonctionnalités de **SIEM**, **NDR**, **EDR**, **SOAR** et **Intelligence Artificielle** dans une solution souveraine ivoirienne.

### 🎯 Objectifs

- **Souveraineté numérique** : Solution 100% locale pour la Côte d'Ivoire
- **Détection avancée** : IA/ML pour la détection d'anomalies et de menaces
- **Réponse automatisée** : Orchestration et automatisation des réponses
- **Visibilité unifiée** : Dashboard centralisé pour les équipes SOC

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    SUSDR 360 Platform                      │
├─────────────────────────────────────────────────────────────┤
│  Dashboard Exécutif  │  SOC Analyst Console  │  API Gateway │
├─────────────────────────────────────────────────────────────┤
│           Intelligence Artificielle & ML Engine            │
├─────────────────────────────────────────────────────────────┤
│  SIEM Core  │  NDR Engine  │  EDR Agent  │  SOAR Platform  │
├─────────────────────────────────────────────────────────────┤
│              Threat Intelligence Platform                   │
├─────────────────────────────────────────────────────────────┤
│    Data Lake    │   Event Store   │   Configuration DB    │
└─────────────────────────────────────────────────────────────┘
```

## 🚀 Fonctionnalités Principales

### 🔍 SIEM Core Engine
- **Collecte multi-sources** : Syslog, Windows Events, API REST
- **Normalisation intelligente** : Parsing automatique des formats
- **Corrélation avancée** : Détection de patterns complexes
- **Stockage optimisé** : Base de données haute performance

### 🌐 Network Detection & Response (NDR)
- **Analyse de trafic** : Deep Packet Inspection
- **Détection d'anomalies réseau** : Baseline comportemental
- **Threat Hunting** : Requêtes avancées sur le trafic
- **Visualisation réseau** : Topologie dynamique

### 💻 Endpoint Detection & Response (EDR)
- **Agents légers** : Windows, Linux, macOS
- **Monitoring comportemental** : Processus, fichiers, réseau
- **Réponse automatique** : Isolation, quarantaine
- **Forensics** : Timeline des événements

### 🤖 Intelligence Artificielle
- **Machine Learning** : Détection d'anomalies comportementales
- **Deep Learning** : Analyse de malwares et patterns
- **NLP** : Traitement des rapports et alertes
- **Apprentissage continu** : Amélioration automatique

### 🔗 Security Orchestration (SOAR)
- **Playbooks** : Réponse automatisée aux incidents
- **Intégrations** : API avec outils existants
- **Case Management** : Gestion des incidents
- **Workflow** : Automatisation des processus

### 🕵️ Threat Intelligence
- **Sources locales** : CTI ivoirienne et régionale
- **OSINT** : Feeds publics et commerciaux
- **IOC Management** : Indicateurs de compromission
- **Attribution** : Profiling des attaquants

## 📋 Prérequis

### Système d'exploitation
- **Windows** : Windows 10/11, Windows Server 2016+
- **Linux** : Ubuntu 20.04+, CentOS 8+, RHEL 8+
- **macOS** : macOS 10.15+ (pour les agents uniquement)

### Ressources matérielles
- **CPU** : 4 cores minimum, 8 cores recommandé
- **RAM** : 8 GB minimum, 16 GB recommandé
- **Stockage** : 100 GB minimum, SSD recommandé
- **Réseau** : 1 Gbps recommandé

### Logiciels
- **Python** : 3.9+
- **Node.js** : 16+ (pour l'interface web)
- **Base de données** : SQLite (par défaut) ou PostgreSQL
- **Redis** : Pour le cache et les queues (optionnel)

## 🛠️ Installation

### 1. Clonage du repository
```bash
git clone https://github.com/sahanalytics/susdr360.git
cd susdr360
```

### 2. Installation des dépendances Python
```bash
# Création de l'environnement virtuel
python -m venv venv

# Activation (Windows)
venv\Scripts\activate

# Activation (Linux/macOS)
source venv/bin/activate

# Installation des dépendances
pip install -r requirements.txt
```

### 3. Configuration
```bash
# Copie du fichier de configuration
cp config.yaml.example config.yaml

# Édition de la configuration
nano config.yaml
```

### 4. Initialisation de la base de données
```bash
python -m susdr360.database.init
```

### 5. Démarrage du système
```bash
# Démarrage complet
python main.py

# Démarrage avec configuration personnalisée
python main.py --config /path/to/config.yaml

# Mode debug
python main.py --debug

# Mode test
python main.py --test
```

## 🔧 Configuration

### Configuration de base
```yaml
system:
  name: "SUSDR 360"
  version: "1.0.0"
  debug: false
  data_dir: "./data"
  log_level: "INFO"

api:
  host: "0.0.0.0"
  port: 8000
  cors_origins:
    - "http://localhost:3000"
    - "http://localhost:8080"
```

### Configuration des agents
```yaml
agents:
  windows:
    enabled: true
    collection_interval: 30
    monitored_logs:
      - "Security"
      - "System"
      - "Application"
```

### Configuration IA/ML
```yaml
anomaly_detection:
  enabled: true
  contamination: 0.1
  n_estimators: 100
  max_training_samples: 10000
```

## 📊 Utilisation

### Interface Web
Accédez à l'interface web sur `http://localhost:8080`

**Fonctionnalités disponibles :**
- Dashboard temps réel
- Gestion des événements
- Analyse des incidents
- Configuration système
- Rapports et analytics

### API REST
Documentation API disponible sur `http://localhost:8000/docs`

**Endpoints principaux :**
- `POST /api/v1/events/ingest` - Ingestion d'événements
- `GET /api/v1/events/search` - Recherche d'événements
- `GET /api/v1/incidents` - Liste des incidents
- `GET /api/v1/analytics/stats` - Statistiques système

### Agents de collecte

#### Agent Windows
```bash
# Installation de l'agent Windows
python -m susdr360.agents.windows install

# Démarrage du service
python -m susdr360.agents.windows start
```

#### Agent Syslog
```bash
# Configuration du serveur Syslog
python -m susdr360.agents.syslog --port 514
```

## 🔍 Exemples d'utilisation

### Ingestion d'événements via API
```python
import requests

# Événement Windows Security
event_data = {
    "source": "windows_security",
    "raw_data": {
        "EventID": 4624,
        "Computer": "WS001",
        "LogonType": 3,
        "TargetUserName": "john.doe",
        "IpAddress": "192.168.1.100"
    }
}

response = requests.post(
    "http://localhost:8000/api/v1/events/ingest",
    json=event_data,
    headers={"Authorization": "Bearer YOUR_TOKEN"}
)
```

### Recherche d'événements
```python
# Recherche d'événements d'authentification
params = {
    "event_types": ["authentication"],
    "start_time": "2024-01-01T00:00:00Z",
    "end_time": "2024-01-02T00:00:00Z",
    "limit": 100
}

response = requests.get(
    "http://localhost:8000/api/v1/events/search",
    params=params,
    headers={"Authorization": "Bearer YOUR_TOKEN"}
)

events = response.json()["items"]
```

### Configuration de règles de corrélation
```python
# Règle de détection de brute force
rule_data = {
    "name": "Brute Force Detection",
    "description": "Détecte les tentatives de brute force",
    "rule_type": "frequency",
    "conditions": {
        "event_types": ["authentication"],
        "field_conditions": {
            "normalized_data.auth_result": {
                "operator": "equals",
                "value": "failed"
            }
        },
        "group_by": "normalized_data.src_ip"
    },
    "time_window_minutes": 5,
    "threshold": 5,
    "severity": 3
}

response = requests.post(
    "http://localhost:8000/api/v1/config/rules",
    json=rule_data,
    headers={"Authorization": "Bearer YOUR_TOKEN"}
)
```

## 📈 Monitoring et Métriques

### Métriques système
- **Événements traités** : Nombre d'événements par seconde
- **Incidents créés** : Nombre d'incidents détectés
- **Taux de détection** : Pourcentage de menaces détectées
- **Faux positifs** : Taux de faux positifs

### Health Checks
```bash
# Vérification de l'état du système
curl http://localhost:8000/health

# Statistiques détaillées
curl http://localhost:8000/stats
```

### Logs
```bash
# Logs système
tail -f susdr360.log

# Logs d'audit
tail -f data/audit.log
```

## 🔒 Sécurité

### Authentification
- **JWT Tokens** : Authentification basée sur des tokens
- **RBAC** : Contrôle d'accès basé sur les rôles
- **Session Management** : Gestion sécurisée des sessions

### Chiffrement
- **TLS/SSL** : Chiffrement des communications
- **AES-256** : Chiffrement des données sensibles
- **Hashing** : Hachage sécurisé des mots de passe

### Audit
- **Logs d'audit** : Traçabilité complète des actions
- **Intégrité** : Vérification de l'intégrité des données
- **Conformité** : Respect des standards de sécurité

## 🧪 Tests

### Tests unitaires
```bash
# Exécution des tests
pytest tests/

# Tests avec couverture
pytest --cov=susdr360 tests/

# Tests spécifiques
pytest tests/test_event_processor.py
```

### Tests d'intégration
```bash
# Tests d'intégration complets
pytest tests/integration/

# Tests de performance
pytest tests/performance/
```

### Tests de sécurité
```bash
# Scan de sécurité
bandit -r susdr360/

# Tests de pénétration
python tests/security/pentest.py
```

## 📚 Documentation

### Documentation technique
- **Architecture** : `docs/architecture.md`
- **API Reference** : `docs/api.md`
- **Configuration** : `docs/configuration.md`
- **Deployment** : `docs/deployment.md`

### Guides utilisateur
- **Guide d'installation** : `docs/installation.md`
- **Guide administrateur** : `docs/admin-guide.md`
- **Guide analyste SOC** : `docs/analyst-guide.md`

### Documentation développeur
- **Contributing** : `CONTRIBUTING.md`
- **Code Style** : `docs/code-style.md`
- **Plugin Development** : `docs/plugins.md`

## 🤝 Contribution

Nous accueillons les contributions de la communauté ! Voici comment contribuer :

### 1. Fork du projet
```bash
git fork https://github.com/sahanalytics/susdr360.git
```

### 2. Création d'une branche
```bash
git checkout -b feature/nouvelle-fonctionnalite
```

### 3. Développement
```bash
# Développement de la fonctionnalité
# Tests unitaires
# Documentation
```

### 4. Pull Request
```bash
git push origin feature/nouvelle-fonctionnalite
# Créer une Pull Request sur GitHub
```

### Guidelines
- **Code Style** : Suivre PEP 8 pour Python
- **Tests** : Ajouter des tests pour toute nouvelle fonctionnalité
- **Documentation** : Documenter les nouvelles fonctionnalités
- **Commits** : Messages de commit descriptifs

## 📄 Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Équipe

### SAHANALYTICS
- **Fisher Ouattara** - Chef de Projet & Architecte Principal
- **Équipe SOC** - Analystes L2/L3
- **Équipe DevSecOps** - Développeurs et Ingénieurs Sécurité
- **Équipe IA/ML** - Ingénieurs Intelligence Artificielle

### Contact
- **Email** : contact@sahanalytics.com
- **Site Web** : https://www.sahanalytics.com
- **LinkedIn** : https://linkedin.com/company/sahanalytics

## 🗺️ Roadmap

### Version 1.0 (Q1 2026) - Foundation
- ✅ SIEM Core Engine
- ✅ Event Processing & Correlation
- ✅ Basic AI/ML Detection
- ✅ Web Dashboard
- ✅ REST API

### Version 1.1 (Q2 2026) - Enhancement
- 🔄 Advanced NDR Capabilities
- 🔄 EDR Agent Deployment
- 🔄 SOAR Playbooks
- 🔄 Threat Intelligence Integration
- 🔄 Mobile Application

### Version 2.0 (Q3 2026) - Intelligence
- 📋 Advanced AI/ML Models
- 📋 Behavioral Analytics (UEBA)
- 📋 Predictive Threat Detection
- 📋 Advanced Visualization
- 📋 Multi-tenant Support

### Version 3.0 (Q4 2026) - Ecosystem
- 📋 Plugin Marketplace
- 📋 Cloud-Native Deployment
- 📋 Advanced Integrations
- 📋 Compliance Frameworks
- 📋 Regional Expansion

## 🏆 Reconnaissance

SUSDR 360 est développé dans le cadre du **SIADE 2026** (Salon de l'Innovation et des Applications Digitales d'Entreprise) pour promouvoir l'innovation technologique en Côte d'Ivoire et renforcer la souveraineté numérique nationale.

### Partenaires
- **Ministère de la Transformation Digitale** - Côte d'Ivoire
- **ARTCI** - Autorité de Régulation des Télécommunications
- **Universités partenaires** - Formation et recherche
- **Secteur privé** - Adoption et feedback

## 📞 Support

### Support Technique
- **Email** : support@sahanalytics.com
- **Téléphone** : +225 XX XX XX XX XX
- **Heures** : Lundi-Vendredi 8h-18h (GMT)

### Support Communautaire
- **GitHub Issues** : https://github.com/sahanalytics/susdr360/issues
- **Forum** : https://forum.sahanalytics.com
- **Discord** : https://discord.gg/sahanalytics

### Support Enterprise
- **Support 24/7** : Disponible pour les clients Enterprise
- **Formation** : Sessions de formation personnalisées
- **Consulting** : Services de conseil en cybersécurité

---

**SUSDR 360** - *Protégeons ensemble le cyberespace ivoirien* 🇨🇮

*Développé avec ❤️ par SAHANALYTICS en Côte d'Ivoire*
