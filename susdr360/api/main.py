"""
SUSDR 360 - API Principal
Application FastAPI pour l'interface REST de SUSDR 360
"""

from fastapi import FastAPI, HTTPException, Depends, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from fastapi.middleware.gzip import GZipMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.responses import JSONResponse
import uvicorn
import logging
from datetime import datetime
from typing import Dict, List, Any, Optional
import asyncio
import json

from ..core.event_processor import EventProcessor, Event
from ..core.correlation_engine import CorrelationEngine
from .routes import events, incidents, analytics, config as config_routes, agents as agents_routes, detections as detections_routes
from .middleware.auth import AuthManager
from .models import *

try:
    from ..ai_engine.anomaly_detector import AnomalyDetector
except Exception:
    AnomalyDetector = None

logger = logging.getLogger(__name__)

class SUSDR360API:
    """Application API principale SUSDR 360"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.app = FastAPI(
            title="SUSDR 360 API",
            description="Système Unifié de Surveillance, Détection et Réponse",
            version="1.0.0",
            docs_url="/docs",
            redoc_url="/redoc"
        )
        
        # Composants principaux
        self.event_processor = EventProcessor(config.get('event_processor', {}))
        self.correlation_engine = CorrelationEngine(config.get('correlation', {}))
        self.anomaly_detector = (
            AnomalyDetector(config.get('anomaly_detection', {}))
            if AnomalyDetector is not None
            else None
        )
        self.auth_manager = AuthManager(config.get('auth', {}))
        
        # Configuration des middlewares
        self._setup_middlewares()
        
        # Configuration des routes
        self._setup_routes()
        
        # Gestionnaires d'événements
        self._setup_event_handlers()
        
        logger.info("SUSDR360API initialisée")
    
    def _setup_middlewares(self):
        """Configure les middlewares"""
        # CORS
        self.app.add_middleware(
            CORSMiddleware,
            allow_origins=self.config.get('cors_origins', ["*"]),
            allow_credentials=True,
            allow_methods=["*"],
            allow_headers=["*"],
        )
        
        # Compression
        self.app.add_middleware(GZipMiddleware, minimum_size=1000)
        
        # Middleware de logging personnalisé
        @self.app.middleware("http")
        async def log_requests(request, call_next):
            start_time = datetime.now()
            response = await call_next(request)
            process_time = (datetime.now() - start_time).total_seconds()
            
            logger.info(
                f"{request.method} {request.url.path} - "
                f"Status: {response.status_code} - "
                f"Time: {process_time:.3f}s"
            )
            return response
    
    def _setup_routes(self):
        """Configure les routes de l'API"""
        
        # Route de santé
        @self.app.get("/health")
        async def health_check():
            """Vérification de l'état de santé de l'API"""
            return {
                "status": "healthy",
                "timestamp": datetime.now().isoformat(),
                "version": "1.0.0",
                "components": {
                    "event_processor": "active",
                    "correlation_engine": "active",
                    "anomaly_detector": "active" if self.anomaly_detector is not None else "disabled"
                }
            }
        
        # Route d'information système
        @self.app.get("/info")
        async def system_info():
            """Informations système"""
            return {
                "name": "SUSDR 360",
                "description": "Système Unifié de Surveillance, Détection et Réponse",
                "version": "1.0.0",
                "author": "SAHANALYTICS - Fisher Ouattara",
                "modules": [
                    "SIEM Core",
                    "Network Detection & Response (NDR)",
                    "Endpoint Detection & Response (EDR)",
                    "Security Orchestration & Automated Response (SOAR)",
                    "AI/ML Engine",
                    "Threat Intelligence"
                ]
            }
        
        # Statistiques globales
        @self.app.get("/stats")
        async def get_stats(credentials: HTTPAuthorizationCredentials = Depends(HTTPBearer())):
            """Statistiques globales du système"""
            await self.auth_manager.verify_token(credentials.credentials)
            
            return {
                "event_processor": self.event_processor.get_stats(),
                "correlation_engine": self.correlation_engine.get_stats(),
                "anomaly_detector": self.anomaly_detector.get_stats() if self.anomaly_detector is not None else {},
                "timestamp": datetime.now().isoformat()
            }
        
        # Inclusion des routes modulaires
        self.app.include_router(
            events.router,
            prefix="/api/v1/events",
            tags=["Events"],
            dependencies=[Depends(self._get_event_processor)]
        )
        
        try:
            from .routes import dashboard
            self.app.include_router(dashboard.router, prefix="/api/v1/dashboard", tags=["Dashboard"])
        except ImportError:
            logger.warning("Module dashboard non disponible")
        
        self.app.include_router(
            incidents.router,
            prefix="/api/v1/incidents",
            tags=["Incidents"],
            dependencies=[Depends(self._get_correlation_engine)]
        )
        
        self.app.include_router(
            analytics.router,
            prefix="/api/v1/analytics",
            tags=["Analytics"],
            dependencies=[Depends(self._get_anomaly_detector)]
        )
        
        self.app.include_router(
            config_routes.router,
            prefix="/api/v1/config",
            tags=["Configuration"]
        )

        self.app.include_router(
            agents_routes.router,
            prefix="/api/v1/agents",
            tags=["Agents"]
        )

        self.app.include_router(
            detections_routes.router,
            prefix="/api/v1/detections",
            tags=["Detections"]
        )
    
    def _setup_event_handlers(self):
        """Configure les gestionnaires d'événements"""
        
        # Gestionnaire pour les événements traités
        async def handle_processed_event(event: Event):
            """Traite un événement normalisé"""
            # Analyse de corrélation
            await self.correlation_engine.analyze_event(event)
            
            # Détection d'anomalies
            if self.anomaly_detector is not None:
                anomaly_result = await self.anomaly_detector.analyze_event(event)
                if anomaly_result and anomaly_result.is_anomaly:
                    logger.warning(f"Anomalie détectée: {anomaly_result.explanation}")
        
        # Gestionnaire pour les incidents
        async def handle_incident(incident):
            """Traite un incident détecté"""
            logger.critical(f"Incident créé: {incident.title}")
            # Ici on pourrait envoyer des notifications, déclencher des actions SOAR, etc.
        
        # Enregistrement des gestionnaires
        self.event_processor.add_output_handler(handle_processed_event)
        self.correlation_engine.add_incident_handler(handle_incident)
    
    # Dépendances pour l'injection
    async def _get_event_processor(self):
        return self.event_processor
    
    async def _get_correlation_engine(self):
        return self.correlation_engine
    
    async def _get_anomaly_detector(self):
        return self.anomaly_detector
    
    async def _get_auth_manager(self):
        return self.auth_manager

def create_app(config: Dict[str, Any] = None) -> FastAPI:
    """Crée et configure l'application FastAPI"""
    if config is None:
        config = {
            'event_processor': {},
            'correlation': {},
            'anomaly_detection': {},
            'auth': {
                'secret_key': 'your-secret-key-here',
                'algorithm': 'HS256',
                'access_token_expire_minutes': 30
            },
            'cors_origins': ["http://localhost:3000", "http://localhost:8080"]
        }
    
    susdr_api = SUSDR360API(config)
    app = susdr_api.app
    app.state.event_processor = susdr_api.event_processor
    app.state.correlation_engine = susdr_api.correlation_engine
    app.state.anomaly_detector = susdr_api.anomaly_detector
    app.state.auth_manager = susdr_api.auth_manager
    return app


# Export ASGI app for uvicorn: `uvicorn susdr360.api.main:app`
app = create_app()

# Point d'entrée pour le développement
if __name__ == "__main__":
    uvicorn.run(
        app,
        host="0.0.0.0",
        port=8000,
        log_level="info",
        reload=True
    )
