from datetime import datetime, timezone
from typing import Any, Dict, List, Optional

from fastapi import APIRouter

from ..agent_registry import list_agents

router = APIRouter()


def _parse_dt(value: Optional[str]) -> Optional[datetime]:
    if not value:
        return None
    try:
        return datetime.fromisoformat(value.replace("Z", "+00:00"))
    except Exception:
        return None


def _compute_status(last_seen_iso: Optional[str], active_seconds: int = 120) -> str:
    dt = _parse_dt(last_seen_iso)
    if not dt:
        return "never_connected"
    now = datetime.now(timezone.utc)
    delta = (now - dt).total_seconds()
    return "active" if delta <= active_seconds else "disconnected"


@router.get("/", response_model=List[Dict[str, Any]])
async def get_agents():
    agents = list_agents()
    for a in agents:
        a["status"] = _compute_status(a.get("last_seen"))
    return agents
