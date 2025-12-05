"""
Rule Engine - Moteur de règles pour SUSDR 360
Gère les règles de détection et de corrélation
"""

import logging
from typing import Dict, List, Any, Optional
from dataclasses import dataclass
from datetime import datetime
from enum import Enum

logger = logging.getLogger(__name__)

class RuleType(Enum):
    """Types de règles"""
    DETECTION = "detection"
    CORRELATION = "correlation"
    ENRICHMENT = "enrichment"
    FILTERING = "filtering"

@dataclass
class Rule:
    """Règle de base"""
    id: str
    name: str
    description: str
    rule_type: RuleType
    enabled: bool = True
    created_at: datetime = None
    updated_at: datetime = None

class RuleEngine:
    """Moteur de règles principal"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.rules: Dict[str, Rule] = {}
        
        logger.info("RuleEngine initialisé")
    
    def add_rule(self, rule: Rule):
        """Ajoute une règle"""
        self.rules[rule.id] = rule
        logger.info(f"Règle ajoutée: {rule.name}")
    
    def get_rule(self, rule_id: str) -> Optional[Rule]:
        """Récupère une règle par ID"""
        return self.rules.get(rule_id)
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques"""
        return {
            'total_rules': len(self.rules),
            'enabled_rules': len([r for r in self.rules.values() if r.enabled])
        }
