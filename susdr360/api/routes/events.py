"""
SUSDR 360 - Routes des Événements
Gestion des événements de sécurité via API REST
"""

from fastapi import APIRouter, HTTPException, Depends, BackgroundTasks, Query, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from typing import List, Optional, Dict, Any
from datetime import datetime, timedelta
import logging

from ..models import (
    EventCreate, EventResponse, EventQuery, SuccessResponse, 
    ErrorResponse, PaginatedResponse, EventTypeEnum, SeverityEnum
)
from ...core.event_processor import EventProcessor, Event
from ..agent_registry import upsert_agent, increment_events

logger = logging.getLogger(__name__)
router = APIRouter()
security = HTTPBearer()


def get_event_processor(request: Request) -> EventProcessor:
    event_processor = getattr(request.app.state, 'event_processor', None)
    if event_processor is None:
        raise HTTPException(status_code=500, detail="EventProcessor not initialized")
    return event_processor

@router.post("/ingest", response_model=SuccessResponse)
async def ingest_event(
    event_data: EventCreate,
    background_tasks: BackgroundTasks,
    event_processor: EventProcessor = Depends(get_event_processor),
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Ingère un nouvel événement dans le système SUSDR 360
    
    - **source**: Source de l'événement (ex: "windows_security", "firewall", "antivirus")
    - **raw_data**: Données brutes de l'événement au format JSON
    - **timestamp**: Timestamp optionnel (utilise l'heure actuelle si non fourni)
    """
    try:
        raw = event_data.raw_data or {}
        hostname = str(raw.get('host') or raw.get('hostname') or raw.get('computer') or raw.get('Computer') or 'unknown')
        ip_address = str(raw.get('ip') or raw.get('ip_address') or raw.get('IpAddress') or '')
        os_name = str(raw.get('os') or raw.get('platform') or 'Linux')
        version = str(raw.get('agent_version') or raw.get('version') or '4.5.2')
        agent_id = str(raw.get('agent_id') or raw.get('agent') or hostname)

        # Met à jour / crée l'agent dans le registre
        upsert_agent(
            agent_id=agent_id,
            hostname=hostname,
            ip_address=ip_address,
            os_name=os_name,
            version=version,
            status='active',
            seen_at_iso=(event_data.timestamp.isoformat() if event_data.timestamp else None),
        )
        increment_events(agent_id, 1)

        # Traitement asynchrone de l'événement
        background_tasks.add_task(
            event_processor.process_raw_event,
            event_data.raw_data,
            event_data.source
        )
        
        return SuccessResponse(
            message=f"Événement de {event_data.source} ajouté à la file de traitement",
            data={"source": event_data.source, "timestamp": datetime.now().isoformat()}
        )
        
    except Exception as e:
        logger.error(f"Erreur lors de l'ingestion d'événement: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/ingest/batch", response_model=SuccessResponse)
async def ingest_events_batch(
    events: List[EventCreate],
    background_tasks: BackgroundTasks,
    event_processor: EventProcessor = Depends(),
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Ingère un lot d'événements en une seule requête
    
    Optimisé pour l'ingestion en masse depuis des collecteurs
    """
    try:
        if len(events) > 1000:
            raise HTTPException(
                status_code=400, 
                detail="Trop d'événements dans le lot (maximum 1000)"
            )
        
        # Groupe les événements par source pour un traitement optimisé
        events_by_source = {}
        for event_data in events:
            source = event_data.source
            if source not in events_by_source:
                events_by_source[source] = []
            events_by_source[source].append(event_data.raw_data)
        
        # Traitement asynchrone par source
        for source, raw_events in events_by_source.items():
            background_tasks.add_task(
                event_processor.process_batch,
                raw_events,
                source
            )
        
        return SuccessResponse(
            message=f"{len(events)} événements ajoutés à la file de traitement",
            data={
                "total_events": len(events),
                "sources": list(events_by_source.keys()),
                "timestamp": datetime.now().isoformat()
            }
        )
        
    except Exception as e:
        logger.error(f"Erreur lors de l'ingestion en lot: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/search", response_model=PaginatedResponse)
async def search_events(
    start_time: Optional[datetime] = Query(None, description="Début de la période de recherche"),
    end_time: Optional[datetime] = Query(None, description="Fin de la période de recherche"),
    event_types: Optional[List[EventTypeEnum]] = Query(None, description="Types d'événements à filtrer"),
    sources: Optional[List[str]] = Query(None, description="Sources à filtrer"),
    severities: Optional[List[SeverityEnum]] = Query(None, description="Niveaux de sévérité"),
    tags: Optional[List[str]] = Query(None, description="Tags à rechercher"),
    search_text: Optional[str] = Query(None, description="Texte libre à rechercher"),
    limit: int = Query(100, ge=1, le=1000, description="Nombre de résultats par page"),
    offset: int = Query(0, ge=0, description="Décalage pour la pagination"),
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Recherche d'événements avec filtres avancés
    
    Permet de rechercher et filtrer les événements selon différents critères
    """
    try:
        # Construction de la requête
        query = EventQuery(
            start_time=start_time or datetime.now() - timedelta(hours=24),
            end_time=end_time or datetime.now(),
            event_types=event_types,
            sources=sources,
            severities=severities,
            tags=tags,
            search_text=search_text,
            limit=limit,
            offset=offset
        )
        
        # TODO: Implémenter la recherche dans la base de données
        # Pour l'instant, retourne des données simulées
        mock_events = _generate_mock_events(query)
        
        total = len(mock_events)
        items = mock_events[offset:offset + limit]
        
        return PaginatedResponse(
            items=items,
            total=total,
            page=offset // limit + 1,
            per_page=limit,
            pages=(total + limit - 1) // limit,
            has_next=offset + limit < total,
            has_prev=offset > 0
        )
        
    except Exception as e:
        logger.error(f"Erreur lors de la recherche d'événements: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{event_id}", response_model=EventResponse)
async def get_event(
    event_id: str,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Récupère un événement spécifique par son ID
    """
    try:
        # TODO: Implémenter la récupération depuis la base de données
        # Pour l'instant, retourne des données simulées
        mock_event = _generate_mock_event(event_id)
        
        if not mock_event:
            raise HTTPException(status_code=404, detail="Événement non trouvé")
        
        return mock_event
        
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Erreur lors de la récupération de l'événement {event_id}: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/{event_id}/raw", response_model=Dict[str, Any])
async def get_event_raw_data(
    event_id: str,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Récupère les données brutes d'un événement
    """
    try:
        # TODO: Implémenter la récupération des données brutes
        return {
            "event_id": event_id,
            "raw_data": {
                "EventID": 4624,
                "Computer": "WS001",
                "LogonType": 3,
                "TargetUserName": "john.doe",
                "IpAddress": "192.168.1.100",
                "TimeGenerated": "2024-01-15T10:30:00Z"
            },
            "metadata": {
                "source": "windows_security",
                "collector": "winlogbeat",
                "ingested_at": "2024-01-15T10:30:05Z"
            }
        }
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des données brutes: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.get("/stats/summary")
async def get_events_summary(
    start_time: Optional[datetime] = Query(None),
    end_time: Optional[datetime] = Query(None),
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Résumé statistique des événements sur une période
    """
    try:
        start_time = start_time or datetime.now() - timedelta(hours=24)
        end_time = end_time or datetime.now()
        
        # TODO: Implémenter les vraies statistiques
        return {
            "period": {
                "start": start_time.isoformat(),
                "end": end_time.isoformat()
            },
            "total_events": 15420,
            "events_by_type": {
                "network": 6800,
                "authentication": 3200,
                "endpoint": 2800,
                "system": 2100,
                "security": 520
            },
            "events_by_severity": {
                "low": 12000,
                "medium": 2800,
                "high": 520,
                "critical": 100
            },
            "top_sources": [
                {"source": "windows_security", "count": 4200},
                {"source": "firewall", "count": 3800},
                {"source": "antivirus", "count": 2100},
                {"source": "web_proxy", "count": 1900},
                {"source": "dns", "count": 1500}
            ],
            "events_per_hour": [
                {"hour": "00:00", "count": 450},
                {"hour": "01:00", "count": 380},
                {"hour": "02:00", "count": 320},
                {"hour": "03:00", "count": 290}
            ]
        }
        
    except Exception as e:
        logger.error(f"Erreur lors du calcul des statistiques: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.delete("/{event_id}")
async def delete_event(
    event_id: str,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Supprime un événement (pour les administrateurs uniquement)
    """
    try:
        # TODO: Vérifier les permissions administrateur
        # TODO: Implémenter la suppression
        
        return SuccessResponse(
            message=f"Événement {event_id} supprimé avec succès"
        )
        
    except Exception as e:
        logger.error(f"Erreur lors de la suppression de l'événement {event_id}: {e}")
        raise HTTPException(status_code=500, detail=str(e))

# Fonctions utilitaires pour les données simulées
def _generate_mock_events(query: EventQuery) -> List[EventResponse]:
    """Génère des événements simulés pour les tests"""
    mock_events = []
    
    for i in range(50):  # Génère 50 événements simulés
        event = EventResponse(
            id=f"evt_{datetime.now().strftime('%Y%m%d')}_{i:04d}",
            timestamp=datetime.now() - timedelta(minutes=i * 5),
            event_type=EventTypeEnum.AUTHENTICATION if i % 3 == 0 else EventTypeEnum.NETWORK,
            source="windows_security" if i % 2 == 0 else "firewall",
            severity=SeverityEnum.MEDIUM if i % 4 == 0 else SeverityEnum.LOW,
            title=f"Événement de test #{i}",
            description=f"Description de l'événement de test numéro {i}",
            tags=["test", "simulation", "demo"],
            correlation_id=f"corr_{i // 5}" if i % 5 == 0 else None
        )
        mock_events.append(event)
    
    return mock_events

def _generate_mock_event(event_id: str) -> Optional[EventResponse]:
    """Génère un événement simulé spécifique"""
    if not event_id.startswith("evt_"):
        return None
    
    return EventResponse(
        id=event_id,
        timestamp=datetime.now() - timedelta(minutes=30),
        event_type=EventTypeEnum.AUTHENTICATION,
        source="windows_security",
        severity=SeverityEnum.MEDIUM,
        title="Connexion utilisateur réussie",
        description="L'utilisateur john.doe s'est connecté avec succès depuis 192.168.1.100",
        tags=["authentication", "success", "windows", "logon"],
        correlation_id="corr_auth_001"
    )
