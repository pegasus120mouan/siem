"""
SUSDR 360 - Core Engine
Module central pour la gestion des événements et la corrélation
"""

from .event_processor import EventProcessor
from .correlation_engine import CorrelationEngine
from .rule_engine import RuleEngine
from .alert_manager import AlertManager

__all__ = [
    'EventProcessor',
    'CorrelationEngine', 
    'RuleEngine',
    'AlertManager'
]
