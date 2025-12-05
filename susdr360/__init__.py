"""
SUSDR 360 - Système Unifié de Surveillance, Détection et Réponse
Version: 1.0.0
Auteur: SAHANALYTICS - Fisher Ouattara
Description: Plateforme unifiée SIEM + NDR + EDR + SOAR + IA
"""

__version__ = "1.0.0"
__author__ = "SAHANALYTICS - Fisher Ouattara"
__description__ = "Système Unifié de Surveillance, Détection et Réponse"

# Configuration des modules
MODULES = {
    'siem': 'Module SIEM Core - Collecte et corrélation des logs',
    'ndr': 'Module NDR - Network Detection & Response',
    'edr': 'Module EDR - Endpoint Detection & Response', 
    'soar': 'Module SOAR - Security Orchestration & Automated Response',
    'ai_engine': 'Module IA/ML - Intelligence Artificielle et Machine Learning',
    'threat_intel': 'Module Threat Intelligence - Renseignement sur les menaces',
    'dashboard': 'Module Dashboard - Interface utilisateur et visualisation',
    'api': 'Module API - Interface de programmation'
}

# Configuration de base
DEFAULT_CONFIG = {
    'debug': False,
    'log_level': 'INFO',
    'database_url': 'sqlite:///susdr360.db',
    'redis_url': 'redis://localhost:6379/0',
    'elasticsearch_url': 'http://localhost:9200',
    'api_host': '0.0.0.0',
    'api_port': 8000,
    'web_host': '0.0.0.0',
    'web_port': 8080
}
