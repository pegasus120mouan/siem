"""
Correlation Engine - Moteur de corrélation d'événements
Détecte les patterns et corrélations entre événements pour identifier les incidents
"""

import asyncio
import logging
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Set, Tuple
from dataclasses import dataclass, field
from collections import defaultdict, deque
from enum import Enum
import json
import hashlib

from .event_processor import Event, EventType, Severity

logger = logging.getLogger(__name__)

class CorrelationRuleType(Enum):
    """Types de règles de corrélation"""
    SEQUENCE = "sequence"           # Séquence d'événements
    FREQUENCY = "frequency"         # Fréquence d'événements
    STATISTICAL = "statistical"    # Analyse statistique
    BEHAVIORAL = "behavioral"      # Analyse comportementale
    GEOLOCATION = "geolocation"    # Analyse géographique
    TIME_BASED = "time_based"      # Basé sur le temps

@dataclass
class CorrelationRule:
    """Règle de corrélation"""
    id: str
    name: str
    description: str
    rule_type: CorrelationRuleType
    conditions: Dict[str, Any]
    time_window: timedelta
    threshold: int
    severity: Severity
    enabled: bool = True
    tags: List[str] = field(default_factory=list)
    
    def matches_event(self, event: Event) -> bool:
        """Vérifie si l'événement correspond aux conditions de la règle"""
        try:
            # Vérification du type d'événement
            if 'event_types' in self.conditions:
                if event.event_type.value not in self.conditions['event_types']:
                    return False
            
            # Vérification de la source
            if 'sources' in self.conditions:
                if event.source not in self.conditions['sources']:
                    return False
            
            # Vérification des tags
            if 'required_tags' in self.conditions:
                if not all(tag in event.tags for tag in self.conditions['required_tags']):
                    return False
            
            # Vérification des champs spécifiques
            if 'field_conditions' in self.conditions:
                for field, condition in self.conditions['field_conditions'].items():
                    event_value = self._get_field_value(event, field)
                    if not self._evaluate_condition(event_value, condition):
                        return False
            
            return True
            
        except Exception as e:
            logger.error(f"Erreur lors de l'évaluation de la règle {self.id}: {e}")
            return False
    
    def _get_field_value(self, event: Event, field_path: str) -> Any:
        """Récupère la valeur d'un champ dans l'événement"""
        # Support des chemins imbriqués comme "normalized_data.src_ip"
        parts = field_path.split('.')
        value = event
        
        for part in parts:
            if hasattr(value, part):
                value = getattr(value, part)
            elif isinstance(value, dict) and part in value:
                value = value[part]
            else:
                return None
        
        return value
    
    def _evaluate_condition(self, value: Any, condition: Dict[str, Any]) -> bool:
        """Évalue une condition sur une valeur"""
        operator = condition.get('operator', 'equals')
        expected = condition.get('value')
        
        if value is None:
            return operator == 'is_null'
        
        if operator == 'equals':
            return value == expected
        elif operator == 'not_equals':
            return value != expected
        elif operator == 'contains':
            return expected in str(value)
        elif operator == 'regex':
            import re
            return bool(re.search(expected, str(value)))
        elif operator == 'greater_than':
            return float(value) > float(expected)
        elif operator == 'less_than':
            return float(value) < float(expected)
        elif operator == 'in_list':
            return value in expected
        elif operator == 'not_in_list':
            return value not in expected
        
        return False

@dataclass
class CorrelationContext:
    """Contexte de corrélation pour un groupe d'événements"""
    rule_id: str
    events: List[Event] = field(default_factory=list)
    first_seen: Optional[datetime] = None
    last_seen: Optional[datetime] = None
    count: int = 0
    metadata: Dict[str, Any] = field(default_factory=dict)
    
    def add_event(self, event: Event):
        """Ajoute un événement au contexte"""
        self.events.append(event)
        self.count += 1
        
        if self.first_seen is None or event.timestamp < self.first_seen:
            self.first_seen = event.timestamp
        
        if self.last_seen is None or event.timestamp > self.last_seen:
            self.last_seen = event.timestamp

@dataclass
class CorrelationIncident:
    """Incident détecté par corrélation"""
    id: str
    rule_id: str
    rule_name: str
    title: str
    description: str
    severity: Severity
    events: List[Event]
    created_at: datetime
    tags: List[str]
    metadata: Dict[str, Any]
    
    def to_dict(self) -> Dict[str, Any]:
        """Convertit l'incident en dictionnaire"""
        return {
            'id': self.id,
            'rule_id': self.rule_id,
            'rule_name': self.rule_name,
            'title': self.title,
            'description': self.description,
            'severity': self.severity.value,
            'event_count': len(self.events),
            'event_ids': [event.id for event in self.events],
            'created_at': self.created_at.isoformat(),
            'tags': self.tags,
            'metadata': self.metadata
        }

class CorrelationEngine:
    """Moteur de corrélation principal"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.rules: Dict[str, CorrelationRule] = {}
        self.contexts: Dict[str, CorrelationContext] = {}
        self.incident_handlers = []
        
        # Buffer d'événements pour analyse
        self.event_buffer = deque(maxlen=config.get('buffer_size', 10000))
        
        # Statistiques
        self.stats = {
            'events_analyzed': 0,
            'incidents_created': 0,
            'rules_triggered': 0,
            'contexts_active': 0
        }
        
        # Tâche de nettoyage périodique
        self._cleanup_task = None
        
        logger.info("CorrelationEngine initialisé")
    
    def add_rule(self, rule: CorrelationRule):
        """Ajoute une règle de corrélation"""
        self.rules[rule.id] = rule
        logger.info(f"Règle de corrélation ajoutée: {rule.name}")
    
    def remove_rule(self, rule_id: str):
        """Supprime une règle de corrélation"""
        if rule_id in self.rules:
            del self.rules[rule_id]
            # Nettoie les contextes associés
            contexts_to_remove = [ctx_id for ctx_id in self.contexts 
                                if self.contexts[ctx_id].rule_id == rule_id]
            for ctx_id in contexts_to_remove:
                del self.contexts[ctx_id]
            logger.info(f"Règle de corrélation supprimée: {rule_id}")
    
    def add_incident_handler(self, handler_func):
        """Ajoute un gestionnaire d'incidents"""
        self.incident_handlers.append(handler_func)
        logger.info("Gestionnaire d'incidents ajouté")
    
    async def analyze_event(self, event: Event):
        """Analyse un événement pour détecter des corrélations"""
        try:
            self.stats['events_analyzed'] += 1
            self.event_buffer.append(event)
            
            # Analyse avec chaque règle active
            for rule in self.rules.values():
                if not rule.enabled:
                    continue
                
                if rule.matches_event(event):
                    await self._process_rule_match(rule, event)
            
            # Nettoyage périodique des contextes expirés
            await self._cleanup_expired_contexts()
            
        except Exception as e:
            logger.error(f"Erreur lors de l'analyse de corrélation: {e}")
    
    async def _process_rule_match(self, rule: CorrelationRule, event: Event):
        """Traite une correspondance de règle"""
        try:
            context_key = self._generate_context_key(rule, event)
            
            # Récupère ou crée le contexte
            if context_key not in self.contexts:
                self.contexts[context_key] = CorrelationContext(rule_id=rule.id)
                self.stats['contexts_active'] += 1
            
            context = self.contexts[context_key]
            context.add_event(event)
            
            # Vérifie si le seuil est atteint
            if await self._check_threshold(rule, context):
                incident = await self._create_incident(rule, context)
                await self._handle_incident(incident)
                
                # Supprime le contexte après création de l'incident
                del self.contexts[context_key]
                self.stats['contexts_active'] -= 1
                self.stats['incidents_created'] += 1
            
            self.stats['rules_triggered'] += 1
            
        except Exception as e:
            logger.error(f"Erreur lors du traitement de la règle {rule.id}: {e}")
    
    def _generate_context_key(self, rule: CorrelationRule, event: Event) -> str:
        """Génère une clé de contexte pour grouper les événements"""
        # La clé dépend du type de règle
        if rule.rule_type == CorrelationRuleType.SEQUENCE:
            # Groupe par source ou utilisateur
            grouping_field = rule.conditions.get('group_by', 'source')
            group_value = self._get_grouping_value(event, grouping_field)
            return f"{rule.id}_{group_value}"
        
        elif rule.rule_type == CorrelationRuleType.FREQUENCY:
            # Groupe par champ spécifique
            grouping_field = rule.conditions.get('group_by', 'source')
            group_value = self._get_grouping_value(event, grouping_field)
            return f"{rule.id}_{group_value}"
        
        elif rule.rule_type == CorrelationRuleType.GEOLOCATION:
            # Groupe par utilisateur ou IP
            grouping_field = rule.conditions.get('group_by', 'normalized_data.src_ip')
            group_value = self._get_grouping_value(event, grouping_field)
            return f"{rule.id}_{group_value}"
        
        else:
            # Contexte global pour la règle
            return f"{rule.id}_global"
    
    def _get_grouping_value(self, event: Event, field_path: str) -> str:
        """Récupère la valeur de groupement depuis l'événement"""
        if field_path == 'source':
            return event.source
        
        # Support des chemins imbriqués
        parts = field_path.split('.')
        value = event
        
        for part in parts:
            if hasattr(value, part):
                value = getattr(value, part)
            elif isinstance(value, dict) and part in value:
                value = value[part]
            else:
                return 'unknown'
        
        return str(value) if value is not None else 'unknown'
    
    async def _check_threshold(self, rule: CorrelationRule, context: CorrelationContext) -> bool:
        """Vérifie si le seuil de la règle est atteint"""
        if rule.rule_type == CorrelationRuleType.FREQUENCY:
            # Vérifie la fréquence dans la fenêtre de temps
            now = datetime.now()
            cutoff_time = now - rule.time_window
            
            recent_events = [e for e in context.events if e.timestamp >= cutoff_time]
            return len(recent_events) >= rule.threshold
        
        elif rule.rule_type == CorrelationRuleType.SEQUENCE:
            # Vérifie la séquence d'événements
            required_sequence = rule.conditions.get('sequence', [])
            if len(context.events) < len(required_sequence):
                return False
            
            # Vérifie que les derniers événements correspondent à la séquence
            recent_events = context.events[-len(required_sequence):]
            for i, required_event in enumerate(required_sequence):
                if not self._event_matches_pattern(recent_events[i], required_event):
                    return False
            
            return True
        
        elif rule.rule_type == CorrelationRuleType.STATISTICAL:
            # Analyse statistique (à implémenter selon les besoins)
            return context.count >= rule.threshold
        
        else:
            # Seuil simple par défaut
            return context.count >= rule.threshold
    
    def _event_matches_pattern(self, event: Event, pattern: Dict[str, Any]) -> bool:
        """Vérifie si un événement correspond à un pattern"""
        for field, expected_value in pattern.items():
            event_value = getattr(event, field, None)
            if isinstance(event_value, Enum):
                event_value = event_value.value
            
            if event_value != expected_value:
                return False
        
        return True
    
    async def _create_incident(self, rule: CorrelationRule, context: CorrelationContext) -> CorrelationIncident:
        """Crée un incident à partir du contexte de corrélation"""
        incident_id = f"INC_{rule.id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{hash(str(context.events)) % 10000:04d}"
        
        # Génère la description de l'incident
        description = self._generate_incident_description(rule, context)
        
        incident = CorrelationIncident(
            id=incident_id,
            rule_id=rule.id,
            rule_name=rule.name,
            title=f"{rule.name} - {context.count} événements détectés",
            description=description,
            severity=rule.severity,
            events=context.events.copy(),
            created_at=datetime.now(),
            tags=rule.tags + ['correlation', rule.rule_type.value],
            metadata={
                'rule_type': rule.rule_type.value,
                'event_count': context.count,
                'time_span': str(context.last_seen - context.first_seen) if context.first_seen and context.last_seen else None,
                'sources': list(set(event.source for event in context.events)),
                'event_types': list(set(event.event_type.value for event in context.events))
            }
        )
        
        return incident
    
    def _generate_incident_description(self, rule: CorrelationRule, context: CorrelationContext) -> str:
        """Génère une description détaillée de l'incident"""
        description_parts = [
            f"Règle de corrélation '{rule.name}' déclenchée.",
            f"Type: {rule.rule_type.value}",
            f"Nombre d'événements: {context.count}",
        ]
        
        if context.first_seen and context.last_seen:
            time_span = context.last_seen - context.first_seen
            description_parts.append(f"Période: {time_span}")
        
        # Ajoute des détails spécifiques selon le type de règle
        if rule.rule_type == CorrelationRuleType.FREQUENCY:
            description_parts.append(f"Seuil de fréquence dépassé: {context.count} événements en {rule.time_window}")
        
        elif rule.rule_type == CorrelationRuleType.SEQUENCE:
            description_parts.append("Séquence d'événements suspecte détectée")
        
        # Ajoute les sources impliquées
        sources = list(set(event.source for event in context.events))
        if sources:
            description_parts.append(f"Sources: {', '.join(sources)}")
        
        return " | ".join(description_parts)
    
    async def _handle_incident(self, incident: CorrelationIncident):
        """Gère un incident détecté"""
        logger.warning(f"Incident détecté: {incident.title}")
        
        # Envoie l'incident à tous les gestionnaires
        for handler in self.incident_handlers:
            try:
                await handler(incident)
            except Exception as e:
                logger.error(f"Erreur dans le gestionnaire d'incidents: {e}")
    
    async def _cleanup_expired_contexts(self):
        """Nettoie les contextes expirés"""
        now = datetime.now()
        expired_contexts = []
        
        for context_key, context in self.contexts.items():
            rule = self.rules.get(context.rule_id)
            if not rule:
                expired_contexts.append(context_key)
                continue
            
            # Vérifie si le contexte a expiré
            if context.last_seen and (now - context.last_seen) > rule.time_window * 2:
                expired_contexts.append(context_key)
        
        # Supprime les contextes expirés
        for context_key in expired_contexts:
            del self.contexts[context_key]
            self.stats['contexts_active'] -= 1
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques du moteur de corrélation"""
        stats = self.stats.copy()
        stats['active_rules'] = len([r for r in self.rules.values() if r.enabled])
        stats['total_rules'] = len(self.rules)
        stats['active_contexts'] = len(self.contexts)
        return stats
    
    def get_active_contexts(self) -> List[Dict[str, Any]]:
        """Retourne les contextes actifs"""
        contexts_info = []
        for context_key, context in self.contexts.items():
            rule = self.rules.get(context.rule_id)
            contexts_info.append({
                'key': context_key,
                'rule_id': context.rule_id,
                'rule_name': rule.name if rule else 'Unknown',
                'event_count': context.count,
                'first_seen': context.first_seen.isoformat() if context.first_seen else None,
                'last_seen': context.last_seen.isoformat() if context.last_seen else None
            })
        return contexts_info

# Règles de corrélation prédéfinies
def create_brute_force_rule() -> CorrelationRule:
    """Règle de détection de brute force"""
    return CorrelationRule(
        id="brute_force_detection",
        name="Détection de Brute Force",
        description="Détecte les tentatives de brute force sur les authentifications",
        rule_type=CorrelationRuleType.FREQUENCY,
        conditions={
            'event_types': ['authentication'],
            'field_conditions': {
                'normalized_data.auth_result': {'operator': 'equals', 'value': 'failed'}
            },
            'group_by': 'normalized_data.src_ip'
        },
        time_window=timedelta(minutes=5),
        threshold=5,
        severity=Severity.HIGH,
        tags=['brute_force', 'authentication']
    )

def create_lateral_movement_rule() -> CorrelationRule:
    """Règle de détection de mouvement latéral"""
    return CorrelationRule(
        id="lateral_movement_detection",
        name="Détection de Mouvement Latéral",
        description="Détecte les tentatives de mouvement latéral dans le réseau",
        rule_type=CorrelationRuleType.SEQUENCE,
        conditions={
            'sequence': [
                {'event_type': 'authentication', 'normalized_data.auth_result': 'success'},
                {'event_type': 'network', 'normalized_data.protocol': 'SMB'},
                {'event_type': 'endpoint', 'normalized_data.process_name': 'psexec.exe'}
            ],
            'group_by': 'normalized_data.user'
        },
        time_window=timedelta(minutes=30),
        threshold=1,
        severity=Severity.CRITICAL,
        tags=['lateral_movement', 'apt']
    )

def create_data_exfiltration_rule() -> CorrelationRule:
    """Règle de détection d'exfiltration de données"""
    return CorrelationRule(
        id="data_exfiltration_detection",
        name="Détection d'Exfiltration de Données",
        description="Détecte les tentatives d'exfiltration de données",
        rule_type=CorrelationRuleType.STATISTICAL,
        conditions={
            'event_types': ['network'],
            'field_conditions': {
                'normalized_data.bytes': {'operator': 'greater_than', 'value': 1000000}  # > 1MB
            },
            'group_by': 'normalized_data.src_ip'
        },
        time_window=timedelta(hours=1),
        threshold=10,
        severity=Severity.HIGH,
        tags=['data_exfiltration', 'network']
    )
