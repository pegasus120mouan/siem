"""
Alert Manager - Gestionnaire d'alertes pour SUSDR 360
Gère la création, l'envoi et le suivi des alertes
"""

import logging
from typing import Dict, List, Any, Optional, Callable
from dataclasses import dataclass
from datetime import datetime
from enum import Enum

logger = logging.getLogger(__name__)

class AlertStatus(Enum):
    """Statuts d'alerte"""
    NEW = "new"
    ACKNOWLEDGED = "acknowledged"
    RESOLVED = "resolved"
    CLOSED = "closed"

@dataclass
class Alert:
    """Alerte de sécurité"""
    id: str
    title: str
    description: str
    severity: str
    status: AlertStatus
    created_at: datetime
    source: str
    metadata: Dict[str, Any]

class AlertManager:
    """Gestionnaire d'alertes principal"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.alerts: Dict[str, Alert] = {}
        self.handlers: List[Callable] = []
        
        logger.info("AlertManager initialisé")
    
    def create_alert(self, title: str, description: str, severity: str, source: str, metadata: Dict[str, Any] = None) -> Alert:
        """Crée une nouvelle alerte"""
        alert_id = f"alert_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{len(self.alerts)}"
        
        alert = Alert(
            id=alert_id,
            title=title,
            description=description,
            severity=severity,
            status=AlertStatus.NEW,
            created_at=datetime.now(),
            source=source,
            metadata=metadata or {}
        )
        
        self.alerts[alert_id] = alert
        logger.info(f"Alerte créée: {title}")
        
        return alert
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques"""
        return {
            'total_alerts': len(self.alerts),
            'new_alerts': len([a for a in self.alerts.values() if a.status == AlertStatus.NEW])
        }
