"""
Windows Agent - Agent de collecte pour les événements Windows
Collecte les événements depuis les journaux Windows Event Log
"""

import asyncio
import logging
import json
from datetime import datetime, timezone
from typing import Dict, List, Any, Optional, Callable
import xml.etree.ElementTree as ET
import subprocess
import re

logger = logging.getLogger(__name__)

class WindowsAgent:
    """Agent de collecte pour les événements Windows"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.is_running = False
        self.event_handlers = []
        
        # Configuration des logs à surveiller
        self.monitored_logs = config.get('monitored_logs', [
            'Security',
            'System', 
            'Application',
            'Microsoft-Windows-Sysmon/Operational'
        ])
        
        # Filtres d'événements
        self.event_filters = config.get('event_filters', {
            'Security': [4624, 4625, 4634, 4647, 4648, 4672, 4688, 4689],  # Auth & Process
            'System': [7034, 7035, 7036, 7040],  # Services
            'Application': [],  # Tous les événements
            'Microsoft-Windows-Sysmon/Operational': [1, 3, 7, 8, 11, 13]  # Process, Network, etc.
        })
        
        # Derniers événements traités (pour éviter les doublons)
        self.last_event_ids = {}
        
        # Statistiques
        self.stats = {
            'events_collected': 0,
            'events_sent': 0,
            'errors': 0,
            'last_collection': None
        }
        
        logger.info("WindowsAgent initialisé")
    
    def add_event_handler(self, handler: Callable):
        """Ajoute un gestionnaire d'événements"""
        self.event_handlers.append(handler)
        logger.info("Gestionnaire d'événements ajouté")
    
    async def start(self):
        """Démarre la collecte d'événements"""
        if self.is_running:
            logger.warning("L'agent Windows est déjà en cours d'exécution")
            return
        
        self.is_running = True
        logger.info("Démarrage de l'agent Windows")
        
        try:
            # Démarre la collecte pour chaque log surveillé
            tasks = []
            for log_name in self.monitored_logs:
                task = asyncio.create_task(self._collect_from_log(log_name))
                tasks.append(task)
            
            # Attend que toutes les tâches se terminent
            await asyncio.gather(*tasks)
            
        except Exception as e:
            logger.error(f"Erreur lors du démarrage de l'agent Windows: {e}")
            self.stats['errors'] += 1
        finally:
            self.is_running = False
    
    async def stop(self):
        """Arrête la collecte d'événements"""
        logger.info("Arrêt de l'agent Windows")
        self.is_running = False
    
    async def _collect_from_log(self, log_name: str):
        """Collecte les événements depuis un log spécifique"""
        logger.info(f"Début de la collecte depuis {log_name}")
        
        while self.is_running:
            try:
                # Récupère les nouveaux événements
                events = await self._get_new_events(log_name)
                
                for event_data in events:
                    # Traite chaque événement
                    processed_event = await self._process_event(event_data, log_name)
                    if processed_event:
                        # Envoie l'événement aux gestionnaires
                        await self._send_event(processed_event)
                
                self.stats['last_collection'] = datetime.now()
                
                # Pause avant la prochaine collecte
                await asyncio.sleep(self.config.get('collection_interval', 30))
                
            except Exception as e:
                logger.error(f"Erreur lors de la collecte depuis {log_name}: {e}")
                self.stats['errors'] += 1
                await asyncio.sleep(60)  # Pause plus longue en cas d'erreur
    
    async def _get_new_events(self, log_name: str) -> List[Dict[str, Any]]:
        """Récupère les nouveaux événements depuis un log Windows"""
        try:
            # Construction de la requête PowerShell
            last_event_id = self.last_event_ids.get(log_name, 0)
            event_filters = self.event_filters.get(log_name, [])
            
            # Filtre par Event ID si spécifié
            event_filter = ""
            if event_filters:
                event_ids = " or ".join([f"EventID={eid}" for eid in event_filters])
                event_filter = f" and ({event_ids})"
            
            # Requête PowerShell pour récupérer les événements
            powershell_query = f"""
            Get-WinEvent -FilterHashtable @{{
                LogName='{log_name}';
                StartTime=(Get-Date).AddMinutes(-{self.config.get('lookback_minutes', 60)})
            }} -MaxEvents {self.config.get('max_events_per_batch', 100)} -ErrorAction SilentlyContinue |
            Where-Object {{ $_.RecordId -gt {last_event_id}{event_filter} }} |
            Sort-Object RecordId |
            ConvertTo-Json -Depth 10
            """
            
            # Exécute la requête PowerShell
            result = await self._run_powershell(powershell_query)
            
            if not result:
                return []
            
            # Parse le JSON retourné
            try:
                events_data = json.loads(result)
                if not isinstance(events_data, list):
                    events_data = [events_data] if events_data else []
            except json.JSONDecodeError:
                logger.error(f"Erreur de parsing JSON pour {log_name}")
                return []
            
            # Met à jour le dernier ID traité
            if events_data:
                max_record_id = max(event.get('RecordId', 0) for event in events_data)
                self.last_event_ids[log_name] = max_record_id
            
            self.stats['events_collected'] += len(events_data)
            return events_data
            
        except Exception as e:
            logger.error(f"Erreur lors de la récupération des événements de {log_name}: {e}")
            return []
    
    async def _run_powershell(self, command: str) -> Optional[str]:
        """Exécute une commande PowerShell et retourne le résultat"""
        try:
            # Prépare la commande PowerShell
            full_command = [
                'powershell.exe',
                '-NoProfile',
                '-ExecutionPolicy', 'Bypass',
                '-Command', command
            ]
            
            # Exécute la commande de manière asynchrone
            process = await asyncio.create_subprocess_exec(
                *full_command,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE,
                creationflags=subprocess.CREATE_NO_WINDOW if hasattr(subprocess, 'CREATE_NO_WINDOW') else 0
            )
            
            stdout, stderr = await process.communicate()
            
            if process.returncode != 0:
                logger.error(f"Erreur PowerShell: {stderr.decode('utf-8', errors='ignore')}")
                return None
            
            return stdout.decode('utf-8', errors='ignore').strip()
            
        except Exception as e:
            logger.error(f"Erreur lors de l'exécution PowerShell: {e}")
            return None
    
    async def _process_event(self, event_data: Dict[str, Any], log_name: str) -> Optional[Dict[str, Any]]:
        """Traite et normalise un événement Windows"""
        try:
            # Extraction des données de base
            event_id = event_data.get('Id', 0)
            record_id = event_data.get('RecordId', 0)
            time_created = event_data.get('TimeCreated')
            level_display_name = event_data.get('LevelDisplayName', 'Information')
            
            # Parse le timestamp
            if isinstance(time_created, str):
                try:
                    timestamp = datetime.fromisoformat(time_created.replace('Z', '+00:00'))
                except:
                    timestamp = datetime.now(timezone.utc)
            else:
                timestamp = datetime.now(timezone.utc)
            
            # Extraction du message et des propriétés
            message = event_data.get('Message', '')
            properties = self._extract_event_properties(event_data)
            
            # Construction de l'événement normalisé
            normalized_event = {
                'timestamp': timestamp.isoformat(),
                'source': f'windows_{log_name.lower().replace("-", "_").replace("/", "_")}',
                'raw_data': event_data,
                'normalized_data': {
                    'event_id': event_id,
                    'record_id': record_id,
                    'log_name': log_name,
                    'level': level_display_name,
                    'computer': event_data.get('MachineName', 'Unknown'),
                    'message': message,
                    'properties': properties,
                    'event_type': self._determine_event_type(event_id, log_name),
                    'severity': self._map_severity(level_display_name),
                    'title': self._generate_title(event_id, log_name, message),
                    'description': self._generate_description(event_id, message, properties),
                    'tags': self._generate_tags(event_id, log_name, properties)
                }
            }
            
            # Enrichissement spécifique selon l'Event ID
            self._enrich_specific_event(normalized_event, event_id, properties)
            
            return normalized_event
            
        except Exception as e:
            logger.error(f"Erreur lors du traitement de l'événement {event_data.get('Id', 'unknown')}: {e}")
            return None
    
    def _extract_event_properties(self, event_data: Dict[str, Any]) -> Dict[str, Any]:
        """Extrait les propriétés spécifiques de l'événement"""
        properties = {}
        
        # Propriétés depuis EventData
        event_data_section = event_data.get('Properties', [])
        if isinstance(event_data_section, list):
            for i, prop in enumerate(event_data_section):
                if isinstance(prop, dict) and 'Value' in prop:
                    properties[f'Property_{i}'] = prop['Value']
                elif prop is not None:
                    properties[f'Property_{i}'] = str(prop)
        
        # Propriétés depuis les champs standards
        standard_fields = [
            'ProcessId', 'ThreadId', 'UserId', 'ActivityId',
            'RelatedActivityId', 'Keywords', 'Task', 'Opcode'
        ]
        
        for field in standard_fields:
            if field in event_data:
                properties[field] = event_data[field]
        
        return properties
    
    def _determine_event_type(self, event_id: int, log_name: str) -> str:
        """Détermine le type d'événement selon l'Event ID et le log"""
        # Événements d'authentification
        auth_events = [4624, 4625, 4634, 4647, 4648, 4672, 4768, 4769, 4771, 4776]
        if event_id in auth_events:
            return 'authentication'
        
        # Événements de processus
        process_events = [4688, 4689] + list(range(1, 10))  # Sysmon process events
        if event_id in process_events:
            return 'endpoint'
        
        # Événements réseau
        network_events = [3, 5156, 5157, 5158]  # Sysmon network + Windows Firewall
        if event_id in network_events:
            return 'network'
        
        # Événements de sécurité
        security_events = [4698, 4699, 4700, 4701, 4702, 4719, 4720, 4722, 4724]
        if event_id in security_events or log_name == 'Security':
            return 'security'
        
        # Par défaut
        return 'system'
    
    def _map_severity(self, level_display_name: str) -> str:
        """Mappe le niveau Windows vers notre échelle de sévérité"""
        level_mapping = {
            'Critical': 'critical',
            'Error': 'high',
            'Warning': 'medium',
            'Information': 'low',
            'Verbose': 'low'
        }
        return level_mapping.get(level_display_name, 'medium')
    
    def _generate_title(self, event_id: int, log_name: str, message: str) -> str:
        """Génère un titre descriptif pour l'événement"""
        # Titres spécifiques pour les Event IDs courants
        titles = {
            4624: "Connexion réussie",
            4625: "Échec de connexion",
            4634: "Déconnexion",
            4647: "Déconnexion initiée par l'utilisateur",
            4648: "Connexion avec des identifiants explicites",
            4672: "Privilèges spéciaux assignés",
            4688: "Création de processus",
            4689: "Fin de processus",
            4698: "Tâche planifiée créée",
            4699: "Tâche planifiée supprimée",
            4720: "Compte utilisateur créé",
            4722: "Compte utilisateur activé",
            4724: "Tentative de réinitialisation de mot de passe",
            7034: "Service arrêté de manière inattendue",
            7035: "Service envoyé un contrôle",
            7036: "Service entré dans un état",
            1: "Création de processus (Sysmon)",
            3: "Connexion réseau (Sysmon)",
            7: "Image chargée (Sysmon)",
            8: "CreateRemoteThread (Sysmon)",
            11: "Création de fichier (Sysmon)",
            13: "Valeur de registre modifiée (Sysmon)"
        }
        
        if event_id in titles:
            return titles[event_id]
        
        # Titre générique basé sur le message
        if message and len(message) > 10:
            return message.split('.')[0][:100]
        
        return f"Événement Windows {event_id}"
    
    def _generate_description(self, event_id: int, message: str, properties: Dict[str, Any]) -> str:
        """Génère une description détaillée de l'événement"""
        # Descriptions spécifiques pour certains événements
        if event_id == 4624:
            logon_type = properties.get('Property_8', 'Unknown')
            username = properties.get('Property_5', 'Unknown')
            source_ip = properties.get('Property_18', 'Unknown')
            return f"L'utilisateur {username} s'est connecté (Type: {logon_type}, IP: {source_ip})"
        
        elif event_id == 4625:
            username = properties.get('Property_5', 'Unknown')
            source_ip = properties.get('Property_19', 'Unknown')
            failure_reason = properties.get('Property_8', 'Unknown')
            return f"Échec de connexion pour {username} depuis {source_ip} (Raison: {failure_reason})"
        
        elif event_id == 4688:
            process_name = properties.get('Property_5', 'Unknown')
            command_line = properties.get('Property_8', '')
            return f"Processus créé: {process_name} {command_line}"
        
        # Description générique
        if message:
            return message[:500]  # Limite à 500 caractères
        
        return f"Événement Windows ID {event_id}"
    
    def _generate_tags(self, event_id: int, log_name: str, properties: Dict[str, Any]) -> List[str]:
        """Génère des tags pour l'événement"""
        tags = ['windows', log_name.lower().replace('-', '_').replace('/', '_')]
        
        # Tags spécifiques selon l'Event ID
        if event_id in [4624, 4625, 4634, 4647, 4648]:
            tags.extend(['authentication', 'logon'])
        elif event_id in [4688, 4689]:
            tags.extend(['process', 'execution'])
        elif event_id in [4698, 4699, 4700, 4701, 4702]:
            tags.extend(['scheduled_task', 'persistence'])
        elif event_id in [4720, 4722, 4724]:
            tags.extend(['account_management', 'user'])
        elif event_id in [7034, 7035, 7036]:
            tags.extend(['service', 'system'])
        elif event_id in [1, 3, 7, 8, 11, 13]:
            tags.extend(['sysmon', 'advanced_monitoring'])
        
        # Tags selon le contenu
        if any(keyword in str(properties).lower() for keyword in ['powershell', 'cmd', 'wscript']):
            tags.append('script_execution')
        
        if any(keyword in str(properties).lower() for keyword in ['admin', 'administrator', 'system']):
            tags.append('privileged')
        
        return tags
    
    def _enrich_specific_event(self, event: Dict[str, Any], event_id: int, properties: Dict[str, Any]):
        """Enrichit l'événement avec des données spécifiques selon l'Event ID"""
        normalized = event['normalized_data']
        
        # Enrichissement pour les événements d'authentification
        if event_id in [4624, 4625]:
            normalized.update({
                'username': properties.get('Property_5', 'Unknown'),
                'domain': properties.get('Property_6', 'Unknown'),
                'logon_type': properties.get('Property_8', 'Unknown'),
                'auth_package': properties.get('Property_10', 'Unknown'),
                'workstation': properties.get('Property_11', 'Unknown'),
                'source_ip': properties.get('Property_18' if event_id == 4624 else 'Property_19', 'Unknown'),
                'auth_result': 'success' if event_id == 4624 else 'failed'
            })
        
        # Enrichissement pour les événements de processus
        elif event_id == 4688:
            normalized.update({
                'process_name': properties.get('Property_5', 'Unknown'),
                'process_id': properties.get('Property_4', 'Unknown'),
                'parent_process_name': properties.get('Property_13', 'Unknown'),
                'command_line': properties.get('Property_8', ''),
                'username': properties.get('Property_1', 'Unknown'),
                'action': 'create'
            })
        
        elif event_id == 4689:
            normalized.update({
                'process_name': properties.get('Property_5', 'Unknown'),
                'process_id': properties.get('Property_4', 'Unknown'),
                'username': properties.get('Property_1', 'Unknown'),
                'action': 'terminate'
            })
        
        # Enrichissement pour Sysmon
        elif event_id == 1:  # Sysmon Process Creation
            normalized.update({
                'process_name': properties.get('Property_4', 'Unknown'),
                'process_id': properties.get('Property_3', 'Unknown'),
                'parent_process': properties.get('Property_13', 'Unknown'),
                'command_line': properties.get('Property_10', ''),
                'username': properties.get('Property_11', 'Unknown'),
                'action': 'create'
            })
        
        elif event_id == 3:  # Sysmon Network Connection
            normalized.update({
                'process_name': properties.get('Property_4', 'Unknown'),
                'protocol': properties.get('Property_7', 'Unknown'),
                'src_ip': properties.get('Property_8', 'Unknown'),
                'src_port': properties.get('Property_10', 'Unknown'),
                'dst_ip': properties.get('Property_14', 'Unknown'),
                'dst_port': properties.get('Property_16', 'Unknown')
            })
    
    async def _send_event(self, event: Dict[str, Any]):
        """Envoie l'événement aux gestionnaires"""
        try:
            for handler in self.event_handlers:
                await handler(event)
            
            self.stats['events_sent'] += 1
            
        except Exception as e:
            logger.error(f"Erreur lors de l'envoi de l'événement: {e}")
            self.stats['errors'] += 1
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques de l'agent"""
        return {
            **self.stats,
            'is_running': self.is_running,
            'monitored_logs': self.monitored_logs,
            'last_event_ids': self.last_event_ids
        }
    
    async def test_connection(self) -> bool:
        """Teste la connexion aux logs Windows"""
        try:
            # Test simple avec Get-WinEvent
            result = await self._run_powershell(
                "Get-WinEvent -ListLog Security -ErrorAction SilentlyContinue | Select-Object -First 1"
            )
            return result is not None
            
        except Exception as e:
            logger.error(f"Erreur lors du test de connexion: {e}")
            return False
