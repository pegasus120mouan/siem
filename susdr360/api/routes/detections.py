from typing import Any, Dict, List

from fastapi import APIRouter, Query

from ..detections_registry import get_db_stats, list_alerts

router = APIRouter()


@router.get("/alerts", response_model=List[Dict[str, Any]])
async def get_alerts(limit: int = Query(100, ge=1, le=1000)):
    return list_alerts(limit=limit)


@router.get("/debug", response_model=Dict[str, Any])
async def debug_detections_db():
    return get_db_stats()
