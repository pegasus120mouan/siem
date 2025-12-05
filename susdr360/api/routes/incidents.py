"""
SUSDR 360 - Routes des Incidents
Gestion des incidents de sécurité via API REST
"""

from fastapi import APIRouter, HTTPException, Depends
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from typing import List, Optional
import logging

logger = logging.getLogger(__name__)
router = APIRouter()
security = HTTPBearer()

@router.get("/")
async def get_incidents(
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """Liste des incidents"""
    return {"incidents": [], "total": 0}

@router.get("/{incident_id}")
async def get_incident(
    incident_id: str,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """Détails d'un incident"""
    return {"id": incident_id, "status": "open"}
