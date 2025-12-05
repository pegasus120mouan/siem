"""
Event Processor - Processeur d'événements central
Gère la normalisation, l'enrichissement et le routage des événements
"""

import json
import asyncio
import logging
from datetime import datetime, timezone
from typing import Dict, List, Any, Optional
from dataclasses import dataclass, asdict
from enum import Enum
import hashlib
import uuid

logger = logging.getLogger(__name__)

class EventType(Enum):
    """Types d'événements supportés"""
    NETWORK = "network"
    ENDPOINT = "endpoint"
    APPLICATION = "application"
    AUTHENTICATION = "authentication"
    SYSTEM = "system"
    SECURITY = "security"
    THREAT_INTEL = "threat_intel"

class Severity(Enum):
    """Niveaux de sévérité"""
    LOW = 1
    MEDIUM = 2
    HIGH = 3
    CRITICAL = 4

@dataclass
class Event:
    """Structure d'événement normalisée"""
    id: str
    timestamp: datetime
    event_type: EventType
    source: str
    severity: Severity
    title: str
    description: str
    raw_data: Dict[str, Any]
    normalized_data: Dict[str, Any]
    enrichment_data: Dict[str, Any]
    tags: List[str]
    correlation_id: Optional[str] = None
    
    def to_dict(self) -> Dict[str, Any]:
        """Convertit l'événement en dictionnaire"""
        data = asdict(self)
        data['timestamp'] = self.timestamp.isoformat()
        data['event_type'] = self.event_type.value
        data['severity'] = self.severity.value
        return data
    
    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> 'Event':
        """Crée un événement à partir d'un dictionnaire"""
        data['timestamp'] = datetime.fromisoformat(data['timestamp'])
        data['event_type'] = EventType(data['event_type'])
        data['severity'] = Severity(data['severity'])
        return cls(**data)

class EventProcessor:
    """Processeur d'événements principal"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.normalizers = {}
        self.enrichers = {}
        self.filters = []
        self.output_handlers = []
        
        # Statistiques
        self.stats = {
            'events_processed': 0,
            'events_filtered': 0,
            'events_enriched': 0,
            'processing_errors': 0
        }
        
        logger.info("EventProcessor initialisé")
    
    def register_normalizer(self, source_type: str, normalizer_func):
        """Enregistre un normalisateur pour un type de source"""
        self.normalizers[source_type] = normalizer_func
        logger.info(f"Normalisateur enregistré pour {source_type}")
    
    def register_enricher(self, enricher_name: str, enricher_func):
        """Enregistre un enrichisseur"""
        self.enrichers[enricher_name] = enricher_func
        logger.info(f"Enrichisseur {enricher_name} enregistré")
    
    def add_filter(self, filter_func):
        """Ajoute un filtre d'événements"""
        self.filters.append(filter_func)
        logger.info("Filtre d'événements ajouté")
    
    def add_output_handler(self, handler_func):
        """Ajoute un gestionnaire de sortie"""
        self.output_handlers.append(handler_func)
        logger.info("Gestionnaire de sortie ajouté")
    
    async def process_raw_event(self, raw_data: Dict[str, Any], source: str) -> Optional[Event]:
        """Traite un événement brut"""
        try:
            self.stats['events_processed'] += 1
            
            # Génération de l'ID unique
            event_id = self._generate_event_id(raw_data, source)
            
            # Normalisation
            normalized_data = await self._normalize_event(raw_data, source)
            if not normalized_data:
                logger.warning(f"Échec de normalisation pour {source}")
                return None
            
            # Création de l'événement
            event = Event(
                id=event_id,
                timestamp=self._extract_timestamp(raw_data),
                event_type=self._determine_event_type(normalized_data),
                source=source,
                severity=self._determine_severity(normalized_data),
                title=normalized_data.get('title', 'Événement sans titre'),
                description=normalized_data.get('description', ''),
                raw_data=raw_data,
                normalized_data=normalized_data,
                enrichment_data={},
                tags=normalized_data.get('tags', [])
            )
            
            # Filtrage
            if not await self._apply_filters(event):
                self.stats['events_filtered'] += 1
                return None
            
            # Enrichissement
            await self._enrich_event(event)
            
            # Sortie vers les handlers
            await self._output_event(event)
            
            logger.debug(f"Événement traité: {event.id}")
            return event
            
        except Exception as e:
            self.stats['processing_errors'] += 1
            logger.error(f"Erreur lors du traitement de l'événement: {e}")
            return None
    
    def _generate_event_id(self, raw_data: Dict[str, Any], source: str) -> str:
        """Génère un ID unique pour l'événement"""
        # Utilise un hash du contenu + timestamp pour éviter les doublons
        content = json.dumps(raw_data, sort_keys=True)
        hash_content = hashlib.sha256(f"{source}:{content}".encode()).hexdigest()[:16]
        return f"{source}_{hash_content}_{uuid.uuid4().hex[:8]}"
    
    def _extract_timestamp(self, raw_data: Dict[str, Any]) -> datetime:
        """Extrait le timestamp de l'événement"""
        # Cherche différents formats de timestamp
        timestamp_fields = ['timestamp', '@timestamp', 'time', 'datetime', 'created_at']
        
        for field in timestamp_fields:
            if field in raw_data:
                try:
                    if isinstance(raw_data[field], str):
                        return datetime.fromisoformat(raw_data[field].replace('Z', '+00:00'))
                    elif isinstance(raw_data[field], (int, float)):
                        return datetime.fromtimestamp(raw_data[field], tz=timezone.utc)
                except:
                    continue
        
        # Par défaut, utilise le timestamp actuel
        return datetime.now(timezone.utc)
    
    def _determine_event_type(self, normalized_data: Dict[str, Any]) -> EventType:
        """Détermine le type d'événement"""
        event_type_mapping = {
            'network': EventType.NETWORK,
            'endpoint': EventType.ENDPOINT,
            'application': EventType.APPLICATION,
            'auth': EventType.AUTHENTICATION,
            'system': EventType.SYSTEM,
            'security': EventType.SECURITY,
            'threat': EventType.THREAT_INTEL
        }
        
        event_type_str = normalized_data.get('event_type', 'system').lower()
        return event_type_mapping.get(event_type_str, EventType.SYSTEM)
    
    def _determine_severity(self, normalized_data: Dict[str, Any]) -> Severity:
        """Détermine la sévérité de l'événement"""
        severity_mapping = {
            'low': Severity.LOW,
            'medium': Severity.MEDIUM,
            'high': Severity.HIGH,
            'critical': Severity.CRITICAL,
            1: Severity.LOW,
            2: Severity.MEDIUM,
            3: Severity.HIGH,
            4: Severity.CRITICAL
        }
        
        severity = normalized_data.get('severity', 'medium')
        return severity_mapping.get(severity, Severity.MEDIUM)
    
    async def _normalize_event(self, raw_data: Dict[str, Any], source: str) -> Optional[Dict[str, Any]]:
        """Normalise l'événement selon le type de source"""
        if source in self.normalizers:
            try:
                return await self.normalizers[source](raw_data)
            except Exception as e:
                logger.error(f"Erreur de normalisation pour {source}: {e}")
                return None
        
        # Normalisation par défaut
        return {
            'title': raw_data.get('message', raw_data.get('title', 'Événement')),
            'description': str(raw_data),
            'event_type': 'system',
            'severity': 'medium',
            'tags': []
        }
    
    async def _apply_filters(self, event: Event) -> bool:
        """Applique les filtres à l'événement"""
        for filter_func in self.filters:
            try:
                if not await filter_func(event):
                    return False
            except Exception as e:
                logger.error(f"Erreur dans le filtre: {e}")
        return True
    
    async def _enrich_event(self, event: Event):
        """Enrichit l'événement avec des données supplémentaires"""
        for enricher_name, enricher_func in self.enrichers.items():
            try:
                enrichment = await enricher_func(event)
                if enrichment:
                    event.enrichment_data[enricher_name] = enrichment
                    self.stats['events_enriched'] += 1
            except Exception as e:
                logger.error(f"Erreur d'enrichissement {enricher_name}: {e}")
    
    async def _output_event(self, event: Event):
        """Envoie l'événement vers les handlers de sortie"""
        for handler in self.output_handlers:
            try:
                await handler(event)
            except Exception as e:
                logger.error(f"Erreur dans le handler de sortie: {e}")
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques de traitement"""
        return self.stats.copy()
    
    async def process_batch(self, events: List[Dict[str, Any]], source: str) -> List[Event]:
        """Traite un lot d'événements"""
        tasks = [self.process_raw_event(event_data, source) for event_data in events]
        results = await asyncio.gather(*tasks, return_exceptions=True)
        
        processed_events = []
        for result in results:
            if isinstance(result, Event):
                processed_events.append(result)
            elif isinstance(result, Exception):
                logger.error(f"Erreur de traitement en lot: {result}")
        
        return processed_events

# Normalisateurs prédéfinis
async def normalize_syslog(raw_data: Dict[str, Any]) -> Dict[str, Any]:
    """Normalise les événements syslog"""
    return {
        'title': raw_data.get('message', 'Message syslog'),
        'description': raw_data.get('message', ''),
        'event_type': 'system',
        'severity': _map_syslog_severity(raw_data.get('severity', 6)),
        'tags': ['syslog', raw_data.get('facility', 'unknown')],
        'host': raw_data.get('host', 'unknown'),
        'process': raw_data.get('process', 'unknown')
    }

async def normalize_windows_event(raw_data: Dict[str, Any]) -> Dict[str, Any]:
    """Normalise les événements Windows"""
    event_id = raw_data.get('EventID', 0)
    
    return {
        'title': f"Windows Event {event_id}",
        'description': raw_data.get('Message', ''),
        'event_type': _map_windows_event_type(event_id),
        'severity': _map_windows_severity(raw_data.get('Level', 4)),
        'tags': ['windows', raw_data.get('Channel', 'unknown')],
        'computer': raw_data.get('Computer', 'unknown'),
        'event_id': event_id
    }

async def normalize_network_flow(raw_data: Dict[str, Any]) -> Dict[str, Any]:
    """Normalise les flux réseau"""
    return {
        'title': f"Network Flow {raw_data.get('src_ip', 'unknown')} -> {raw_data.get('dst_ip', 'unknown')}",
        'description': f"Protocol: {raw_data.get('protocol', 'unknown')}, Bytes: {raw_data.get('bytes', 0)}",
        'event_type': 'network',
        'severity': 'low',
        'tags': ['network', 'flow', raw_data.get('protocol', 'unknown')],
        'src_ip': raw_data.get('src_ip'),
        'dst_ip': raw_data.get('dst_ip'),
        'src_port': raw_data.get('src_port'),
        'dst_port': raw_data.get('dst_port'),
        'protocol': raw_data.get('protocol'),
        'bytes': raw_data.get('bytes', 0)
    }

def _map_syslog_severity(severity: int) -> str:
    """Mappe la sévérité syslog vers notre format"""
    mapping = {
        0: 'critical',  # Emergency
        1: 'critical',  # Alert
        2: 'critical',  # Critical
        3: 'high',      # Error
        4: 'medium',    # Warning
        5: 'medium',    # Notice
        6: 'low',       # Informational
        7: 'low'        # Debug
    }
    return mapping.get(severity, 'medium')

def _map_windows_severity(level: int) -> str:
    """Mappe la sévérité Windows vers notre format"""
    mapping = {
        1: 'critical',  # Critical
        2: 'high',      # Error
        3: 'medium',    # Warning
        4: 'low',       # Information
        5: 'low'        # Verbose
    }
    return mapping.get(level, 'medium')

def _map_windows_event_type(event_id: int) -> str:
    """Mappe l'Event ID Windows vers le type d'événement"""
    if event_id in [4624, 4625, 4634, 4647, 4648]:
        return 'authentication'
    elif event_id in [4688, 4689]:
        return 'endpoint'
    elif event_id in [5156, 5157, 5158]:
        return 'network'
    elif event_id in [4698, 4699, 4700, 4701, 4702]:
        return 'security'
    else:
        return 'system'
