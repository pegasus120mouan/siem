#!/usr/bin/env python3
"""
SUSDR 360 - Point d'entrée principal
Système Unifié de Surveillance, Détection et Réponse
Auteur: SAHANALYTICS - Fisher Ouattara
"""

import asyncio
import logging
import signal
import sys
import os
from pathlib import Path
from typing import Dict, Any
import yaml
import argparse
from datetime import datetime

# Ajout du chemin du module
sys.path.insert(0, str(Path(__file__).parent))

# Imports simplifiés pour éviter les erreurs de dépendances circulaires
try:
    from core.event_processor import EventProcessor
    from core.correlation_engine import CorrelationEngine
    # from ai_engine.anomaly_detector import AnomalyDetector
    # from agents.windows_agent import WindowsAgent
    from api.main import create_app
except ImportError as e:
    logger.error(f"Erreur d'import: {e}")
    print(f"Erreur d'import: {e}")
    sys.exit(1)
import uvicorn

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('susdr360.log'),
        logging.StreamHandler(sys.stdout)
    ]
)

logger = logging.getLogger(__name__)

class SUSDR360System:
    """Système principal SUSDR 360"""
    
    def __init__(self, config_path: str = "config.yaml"):
        self.config = self._load_config(config_path)
        self.components = {}
        self.running = False
        
        # Initialisation des composants
        self._init_components()
        
        logger.info("SUSDR 360 System initialisé")
    
    def _load_config(self, config_path: str) -> Dict[str, Any]:
        """Charge la configuration depuis un fichier YAML"""
        try:
            if os.path.exists(config_path):
                with open(config_path, 'r', encoding='utf-8') as f:
                    config = yaml.safe_load(f)
                logger.info(f"Configuration chargée depuis {config_path}")
                return config
            else:
                logger.warning(f"Fichier de configuration {config_path} non trouvé, utilisation de la configuration par défaut")
                return self._get_default_config()
        except Exception as e:
            logger.error(f"Erreur lors du chargement de la configuration: {e}")
            return self._get_default_config()
    
    def _get_default_config(self) -> Dict[str, Any]:
        """Configuration par défaut du système"""
        return {
            'system': {
                'name': 'SUSDR 360',
                'version': '1.0.0',
                'debug': False,
                'data_dir': './data',
                'log_level': 'INFO'
            },
            'api': {
                'host': '0.0.0.0',
                'port': 8000,
                'workers': 1,
                'cors_origins': ['http://localhost:3000', 'http://localhost:8080']
            },
            'web': {
                'host': '0.0.0.0',
                'port': 8080,
                'static_dir': './web'
            },
            'event_processor': {
                'buffer_size': 10000,
                'batch_size': 100,
                'processing_timeout': 30
            },
            'correlation': {
                'buffer_size': 5000,
                'cleanup_interval': 300,
                'max_contexts': 1000
            },
            'anomaly_detection': {
                'contamination': 0.1,
                'n_estimators': 100,
                'max_training_samples': 10000,
                'model_save_interval': 3600
            },
            'agents': {
                'windows': {
                    'enabled': True,
                    'collection_interval': 30,
                    'lookback_minutes': 60,
                    'max_events_per_batch': 100,
                    'monitored_logs': [
                        'Security',
                        'System',
                        'Application',
                        'Microsoft-Windows-Sysmon/Operational'
                    ]
                },
                'syslog': {
                    'enabled': False,
                    'host': '0.0.0.0',
                    'port': 514,
                    'protocol': 'udp'
                }
            },
            'storage': {
                'type': 'sqlite',
                'path': './data/susdr360.db',
                'retention_days': 90
            },
            'notifications': {
                'email': {
                    'enabled': False,
                    'smtp_server': 'localhost',
                    'smtp_port': 587,
                    'username': '',
                    'password': '',
                    'from_address': 'susdr360@sahanalytics.com'
                },
                'webhook': {
                    'enabled': False,
                    'url': '',
                    'timeout': 30
                }
            }
        }
    
    def _init_components(self):
        """Initialise tous les composants du système"""
        try:
            # Event Processor
            self.components['event_processor'] = EventProcessor(
                self.config.get('event_processor', {})
            )
            
            # Correlation Engine
            self.components['correlation_engine'] = CorrelationEngine(
                self.config.get('correlation', {})
            )
            
            # Composants optionnels (désactivés pour le démarrage initial)
            # self.components['anomaly_detector'] = AnomalyDetector(
            #     self.config.get('anomaly_detection', {})
            # )
            
            # Agents de collecte (désactivés pour le démarrage initial)
            # self._init_agents()
            
            # Configuration des handlers d'événements
            # self._setup_event_handlers()
            
            logger.info("Composants de base initialisés avec succès")
            
        except Exception as e:
            logger.error(f"Erreur lors de l'initialisation des composants: {e}")
            raise
    
    def _init_agents(self):
        """Initialise les agents de collecte"""
        self.components['agents'] = {}
        
        # Agent Windows
        if self.config.get('agents', {}).get('windows', {}).get('enabled', False):
            try:
                windows_agent = WindowsAgent(self.config['agents']['windows'])
                self.components['agents']['windows'] = windows_agent
                logger.info("Agent Windows initialisé")
            except Exception as e:
                logger.error(f"Erreur lors de l'initialisation de l'agent Windows: {e}")
        
        # Autres agents peuvent être ajoutés ici
        # Agent Syslog, Agent Network, etc.
    
    def _setup_event_handlers(self):
        """Configure les gestionnaires d'événements entre composants"""
        event_processor = self.components['event_processor']
        correlation_engine = self.components['correlation_engine']
        anomaly_detector = self.components['anomaly_detector']
        
        # Handler pour les événements traités
        async def handle_processed_event(event):
            """Traite un événement normalisé"""
            try:
                # Analyse de corrélation
                await correlation_engine.analyze_event(event)
                
                # Détection d'anomalies
                anomaly_result = await anomaly_detector.analyze_event(event)
                if anomaly_result and anomaly_result.is_anomaly:
                    logger.warning(f"Anomalie détectée: {anomaly_result.explanation}")
                
            except Exception as e:
                logger.error(f"Erreur lors du traitement de l'événement {event.id}: {e}")
        
        # Handler pour les incidents
        async def handle_incident(incident):
            """Traite un incident détecté"""
            logger.critical(f"INCIDENT DÉTECTÉ: {incident.title}")
            logger.critical(f"Sévérité: {incident.severity.name}")
            logger.critical(f"Événements impliqués: {len(incident.events)}")
            
            # Ici on pourrait ajouter:
            # - Envoi de notifications
            # - Déclenchement d'actions SOAR
            # - Sauvegarde en base de données
        
        # Handler pour les agents
        async def handle_agent_event(event_data):
            """Traite un événement depuis un agent"""
            try:
                await event_processor.process_raw_event(
                    event_data['raw_data'],
                    event_data['source']
                )
            except Exception as e:
                logger.error(f"Erreur lors du traitement d'événement d'agent: {e}")
        
        # Enregistrement des handlers
        event_processor.add_output_handler(handle_processed_event)
        correlation_engine.add_incident_handler(handle_incident)
        
        # Enregistrement des handlers pour les agents
        for agent_name, agent in self.components.get('agents', {}).items():
            if hasattr(agent, 'add_event_handler'):
                agent.add_event_handler(handle_agent_event)
    
    async def start(self):
        """Démarre le système SUSDR 360"""
        if self.running:
            logger.warning("Le système est déjà en cours d'exécution")
            return
        
        logger.info("Démarrage du système SUSDR 360...")
        self.running = True
        
        try:
            # Création du répertoire de données
            data_dir = Path(self.config['system']['data_dir'])
            data_dir.mkdir(exist_ok=True)
            
            # Démarrage des agents
            agent_tasks = []
            for agent_name, agent in self.components.get('agents', {}).items():
                if hasattr(agent, 'start'):
                    task = asyncio.create_task(agent.start())
                    agent_tasks.append(task)
                    logger.info(f"Agent {agent_name} démarré")
            
            # Démarrage de l'API
            api_config = self.config.get('api', {})
            app = create_app({
                'event_processor': self.config.get('event_processor', {}),
                'correlation': self.config.get('correlation', {}),
                'anomaly_detection': self.config.get('anomaly_detection', {}),
                'auth': {
                    'secret_key': 'susdr360-secret-key-change-in-production',
                    'algorithm': 'HS256',
                    'access_token_expire_minutes': 30
                },
                'cors_origins': api_config.get('cors_origins', ['*'])
            })
            
            # Configuration d'Uvicorn
            uvicorn_config = uvicorn.Config(
                app,
                host=api_config.get('host', '0.0.0.0'),
                port=api_config.get('port', 8000),
                log_level=self.config['system']['log_level'].lower(),
                access_log=True
            )
            
            server = uvicorn.Server(uvicorn_config)
            
            logger.info(f"API SUSDR 360 démarrée sur http://{api_config.get('host', '0.0.0.0')}:{api_config.get('port', 8000)}")
            logger.info(f"Documentation API disponible sur http://{api_config.get('host', '0.0.0.0')}:{api_config.get('port', 8000)}/docs")
            
            # Attendre que le serveur et les agents s'exécutent
            await asyncio.gather(
                server.serve(),
                *agent_tasks,
                return_exceptions=True
            )
            
        except Exception as e:
            logger.error(f"Erreur lors du démarrage du système: {e}")
            raise
        finally:
            await self.stop()
    
    async def stop(self):
        """Arrête le système SUSDR 360"""
        if not self.running:
            return
        
        logger.info("Arrêt du système SUSDR 360...")
        self.running = False
        
        try:
            # Arrêt des agents
            for agent_name, agent in self.components.get('agents', {}).items():
                if hasattr(agent, 'stop'):
                    await agent.stop()
                    logger.info(f"Agent {agent_name} arrêté")
            
            # Sauvegarde des modèles IA
            if 'anomaly_detector' in self.components:
                model_path = Path(self.config['system']['data_dir']) / 'models' / 'anomaly_models.joblib'
                model_path.parent.mkdir(exist_ok=True)
                self.components['anomaly_detector'].save_models(str(model_path))
            
            logger.info("Système SUSDR 360 arrêté proprement")
            
        except Exception as e:
            logger.error(f"Erreur lors de l'arrêt du système: {e}")
    
    def get_system_status(self) -> Dict[str, Any]:
        """Retourne l'état du système"""
        status = {
            'system': {
                'name': self.config['system']['name'],
                'version': self.config['system']['version'],
                'running': self.running,
                'uptime': datetime.now().isoformat()
            },
            'components': {}
        }
        
        # Statut des composants
        for component_name, component in self.components.items():
            if hasattr(component, 'get_stats'):
                status['components'][component_name] = component.get_stats()
            else:
                status['components'][component_name] = {'status': 'active'}
        
        return status

async def main():
    """Fonction principale"""
    parser = argparse.ArgumentParser(description='SUSDR 360 - Système Unifié de Surveillance, Détection et Réponse')
    parser.add_argument('--config', '-c', default='config.yaml', help='Chemin vers le fichier de configuration')
    parser.add_argument('--debug', '-d', action='store_true', help='Mode debug')
    parser.add_argument('--test', '-t', action='store_true', help='Mode test (arrêt après initialisation)')
    
    args = parser.parse_args()
    
    # Configuration du niveau de log
    if args.debug:
        logging.getLogger().setLevel(logging.DEBUG)
        logger.debug("Mode debug activé")
    
    # Initialisation du système
    try:
        system = SUSDR360System(args.config)
        
        if args.test:
            logger.info("Mode test - Vérification de l'initialisation")
            status = system.get_system_status()
            print(f"Statut du système: {status}")
            return
        
        # Gestion des signaux pour arrêt propre
        def signal_handler(signum, frame):
            logger.info(f"Signal {signum} reçu, arrêt du système...")
            asyncio.create_task(system.stop())
        
        signal.signal(signal.SIGINT, signal_handler)
        signal.signal(signal.SIGTERM, signal_handler)
        
        # Démarrage du système
        await system.start()
        
    except KeyboardInterrupt:
        logger.info("Interruption clavier détectée")
    except Exception as e:
        logger.error(f"Erreur fatale: {e}")
        sys.exit(1)

if __name__ == "__main__":
    # Banner de démarrage
    print("""
    ╔═══════════════════════════════════════════════════════════════╗
    ║                         SUSDR 360                             ║
    ║        Système Unifié de Surveillance, Détection             ║
    ║                    et Réponse v1.0.0                         ║
    ║                                                               ║
    ║              SAHANALYTICS - Fisher Ouattara                  ║
    ║                    Côte d'Ivoire 2026                        ║
    ╚═══════════════════════════════════════════════════════════════╝
    """)
    
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nArrêt du système SUSDR 360")
    except Exception as e:
        print(f"Erreur: {e}")
        sys.exit(1)
