"""
SUSDR 360 - Modèles Pydantic
Modèles de données pour l'API REST
"""

from pydantic import BaseModel, Field, validator
from typing import Dict, List, Any, Optional, Union
from datetime import datetime
from enum import Enum

# Enums
class EventTypeEnum(str, Enum):
    NETWORK = "network"
    ENDPOINT = "endpoint"
    APPLICATION = "application"
    AUTHENTICATION = "authentication"
    SYSTEM = "system"
    SECURITY = "security"
    THREAT_INTEL = "threat_intel"

class SeverityEnum(int, Enum):
    LOW = 1
    MEDIUM = 2
    HIGH = 3
    CRITICAL = 4

class IncidentStatusEnum(str, Enum):
    OPEN = "open"
    INVESTIGATING = "investigating"
    RESOLVED = "resolved"
    CLOSED = "closed"
    FALSE_POSITIVE = "false_positive"

# Modèles d'événements
class EventCreate(BaseModel):
    """Modèle pour créer un événement"""
    source: str = Field(..., description="Source de l'événement")
    raw_data: Dict[str, Any] = Field(..., description="Données brutes de l'événement")
    timestamp: Optional[datetime] = Field(None, description="Timestamp de l'événement")
    
    class Config:
        schema_extra = {
            "example": {
                "source": "windows_security",
                "raw_data": {
                    "EventID": 4624,
                    "Computer": "WS001",
                    "LogonType": 3,
                    "TargetUserName": "john.doe",
                    "IpAddress": "192.168.1.100"
                },
                "timestamp": "2024-01-15T10:30:00Z"
            }
        }

class EventResponse(BaseModel):
    """Modèle de réponse pour un événement"""
    id: str
    timestamp: datetime
    event_type: EventTypeEnum
    source: str
    severity: SeverityEnum
    title: str
    description: str
    tags: List[str]
    correlation_id: Optional[str] = None
    
    class Config:
        schema_extra = {
            "example": {
                "id": "evt_20240115_103000_001",
                "timestamp": "2024-01-15T10:30:00Z",
                "event_type": "authentication",
                "source": "windows_security",
                "severity": 2,
                "title": "Successful Logon",
                "description": "User john.doe successfully logged on",
                "tags": ["authentication", "success", "windows"],
                "correlation_id": "corr_001"
            }
        }

class EventQuery(BaseModel):
    """Modèle pour les requêtes d'événements"""
    start_time: Optional[datetime] = Field(None, description="Début de la période")
    end_time: Optional[datetime] = Field(None, description="Fin de la période")
    event_types: Optional[List[EventTypeEnum]] = Field(None, description="Types d'événements")
    sources: Optional[List[str]] = Field(None, description="Sources à filtrer")
    severities: Optional[List[SeverityEnum]] = Field(None, description="Niveaux de sévérité")
    tags: Optional[List[str]] = Field(None, description="Tags à rechercher")
    search_text: Optional[str] = Field(None, description="Texte à rechercher")
    limit: int = Field(100, ge=1, le=1000, description="Nombre maximum de résultats")
    offset: int = Field(0, ge=0, description="Décalage pour la pagination")

# Modèles d'incidents
class IncidentResponse(BaseModel):
    """Modèle de réponse pour un incident"""
    id: str
    rule_id: str
    rule_name: str
    title: str
    description: str
    severity: SeverityEnum
    status: IncidentStatusEnum = IncidentStatusEnum.OPEN
    event_count: int
    event_ids: List[str]
    created_at: datetime
    updated_at: Optional[datetime] = None
    assigned_to: Optional[str] = None
    tags: List[str]
    metadata: Dict[str, Any]
    
    class Config:
        schema_extra = {
            "example": {
                "id": "INC_20240115_103000_001",
                "rule_id": "brute_force_detection",
                "rule_name": "Détection de Brute Force",
                "title": "Tentative de brute force détectée",
                "description": "5 tentatives de connexion échouées en 2 minutes",
                "severity": 3,
                "status": "open",
                "event_count": 5,
                "event_ids": ["evt_001", "evt_002", "evt_003"],
                "created_at": "2024-01-15T10:30:00Z",
                "tags": ["brute_force", "authentication"],
                "metadata": {"src_ip": "192.168.1.100"}
            }
        }

class IncidentUpdate(BaseModel):
    """Modèle pour mettre à jour un incident"""
    status: Optional[IncidentStatusEnum] = None
    assigned_to: Optional[str] = None
    notes: Optional[str] = None
    
    class Config:
        schema_extra = {
            "example": {
                "status": "investigating",
                "assigned_to": "analyst1",
                "notes": "Investigation en cours"
            }
        }

class IncidentQuery(BaseModel):
    """Modèle pour les requêtes d'incidents"""
    start_time: Optional[datetime] = None
    end_time: Optional[datetime] = None
    severities: Optional[List[SeverityEnum]] = None
    statuses: Optional[List[IncidentStatusEnum]] = None
    assigned_to: Optional[str] = None
    rule_ids: Optional[List[str]] = None
    tags: Optional[List[str]] = None
    limit: int = Field(50, ge=1, le=500)
    offset: int = Field(0, ge=0)

# Modèles d'analyse
class AnomalyResult(BaseModel):
    """Résultat de détection d'anomalie"""
    event_id: str
    anomaly_score: float
    is_anomaly: bool
    confidence: float
    features_used: List[str]
    explanation: str
    model_used: str
    timestamp: datetime

class AnalyticsQuery(BaseModel):
    """Requête pour les analyses"""
    start_time: datetime
    end_time: datetime
    analysis_type: str = Field(..., description="Type d'analyse: trends, anomalies, statistics")
    granularity: str = Field("hour", description="Granularité: minute, hour, day")
    filters: Optional[Dict[str, Any]] = None

class TrendData(BaseModel):
    """Données de tendance"""
    timestamp: datetime
    count: int
    category: str
    value: float

class StatisticsResponse(BaseModel):
    """Réponse statistique"""
    period: Dict[str, datetime]
    total_events: int
    events_by_type: Dict[str, int]
    events_by_severity: Dict[str, int]
    top_sources: List[Dict[str, Union[str, int]]]
    incidents_created: int
    anomalies_detected: int
    trends: List[TrendData]

# Modèles de configuration
class RuleCreate(BaseModel):
    """Modèle pour créer une règle de corrélation"""
    name: str = Field(..., description="Nom de la règle")
    description: str = Field(..., description="Description de la règle")
    rule_type: str = Field(..., description="Type de règle")
    conditions: Dict[str, Any] = Field(..., description="Conditions de la règle")
    time_window_minutes: int = Field(..., ge=1, description="Fenêtre de temps en minutes")
    threshold: int = Field(..., ge=1, description="Seuil de déclenchement")
    severity: SeverityEnum = Field(..., description="Sévérité de l'incident généré")
    enabled: bool = Field(True, description="Règle activée")
    tags: List[str] = Field(default_factory=list, description="Tags de la règle")

class RuleResponse(BaseModel):
    """Réponse pour une règle de corrélation"""
    id: str
    name: str
    description: str
    rule_type: str
    conditions: Dict[str, Any]
    time_window_minutes: int
    threshold: int
    severity: SeverityEnum
    enabled: bool
    tags: List[str]
    created_at: datetime
    updated_at: Optional[datetime] = None

class ConfigUpdate(BaseModel):
    """Mise à jour de configuration"""
    key: str = Field(..., description="Clé de configuration")
    value: Any = Field(..., description="Valeur de configuration")
    description: Optional[str] = Field(None, description="Description de la configuration")

# Modèles d'authentification
class UserLogin(BaseModel):
    """Modèle de connexion utilisateur"""
    username: str = Field(..., description="Nom d'utilisateur")
    password: str = Field(..., description="Mot de passe")
    
    class Config:
        schema_extra = {
            "example": {
                "username": "analyst1",
                "password": "secure_password"
            }
        }

class Token(BaseModel):
    """Token d'authentification"""
    access_token: str
    token_type: str = "bearer"
    expires_in: int

class UserInfo(BaseModel):
    """Informations utilisateur"""
    username: str
    email: Optional[str] = None
    full_name: Optional[str] = None
    roles: List[str] = Field(default_factory=list)
    permissions: List[str] = Field(default_factory=list)
    last_login: Optional[datetime] = None

# Modèles de réponse génériques
class SuccessResponse(BaseModel):
    """Réponse de succès générique"""
    success: bool = True
    message: str
    data: Optional[Any] = None

class ErrorResponse(BaseModel):
    """Réponse d'erreur"""
    success: bool = False
    error: str
    details: Optional[str] = None
    code: Optional[str] = None

class PaginatedResponse(BaseModel):
    """Réponse paginée"""
    items: List[Any]
    total: int
    page: int
    per_page: int
    pages: int
    has_next: bool
    has_prev: bool

# Modèles de monitoring
class SystemHealth(BaseModel):
    """État de santé du système"""
    status: str = Field(..., description="État global: healthy, degraded, unhealthy")
    timestamp: datetime
    components: Dict[str, str] = Field(..., description="État des composants")
    metrics: Dict[str, float] = Field(default_factory=dict, description="Métriques système")

class ComponentStats(BaseModel):
    """Statistiques d'un composant"""
    name: str
    status: str
    uptime: float
    processed_items: int
    error_rate: float
    last_activity: datetime
    metrics: Dict[str, Any] = Field(default_factory=dict)

# Validateurs personnalisés
@validator('timestamp', pre=True, always=True)
def parse_timestamp(cls, v):
    if isinstance(v, str):
        return datetime.fromisoformat(v.replace('Z', '+00:00'))
    return v or datetime.now()

# Configuration des modèles
class Config:
    """Configuration globale des modèles"""
    use_enum_values = True
    validate_assignment = True
    arbitrary_types_allowed = True
    json_encoders = {
        datetime: lambda v: v.isoformat()
    }
