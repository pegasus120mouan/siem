import sqlite3
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional


def _db_path() -> Path:
    base = Path(__file__).resolve().parents[1]
    data_dir = base / "data"
    data_dir.mkdir(parents=True, exist_ok=True)
    return data_dir / "agents.db"


def _connect() -> sqlite3.Connection:
    conn = sqlite3.connect(str(_db_path()))
    conn.row_factory = sqlite3.Row
    return conn


def init_db() -> None:
    conn = _connect()
    try:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS agents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id TEXT UNIQUE,
                hostname TEXT,
                ip_address TEXT,
                os TEXT,
                version TEXT,
                last_seen TEXT,
                status TEXT,
                events_count INTEGER DEFAULT 0,
                created_at TEXT,
                updated_at TEXT
            )
            """
        )
        conn.execute("CREATE INDEX IF NOT EXISTS idx_agents_last_seen ON agents(last_seen)")
        conn.commit()
    finally:
        conn.close()


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def upsert_agent(
    *,
    agent_id: str,
    hostname: str,
    ip_address: str,
    os_name: str,
    version: str,
    status: str,
    seen_at_iso: Optional[str] = None,
) -> None:
    init_db()
    now = _now_iso()
    seen = seen_at_iso or now

    conn = _connect()
    try:
        conn.execute(
            """
            INSERT INTO agents (agent_id, hostname, ip_address, os, version, last_seen, status, events_count, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ON CONFLICT(agent_id) DO UPDATE SET
                hostname=excluded.hostname,
                ip_address=excluded.ip_address,
                os=excluded.os,
                version=excluded.version,
                last_seen=excluded.last_seen,
                status=excluded.status,
                updated_at=excluded.updated_at
            """,
            (agent_id, hostname, ip_address, os_name, version, seen, status, now, now),
        )
        conn.commit()
    finally:
        conn.close()


def increment_events(agent_id: str, increment: int = 1) -> None:
    init_db()
    conn = _connect()
    try:
        conn.execute(
            "UPDATE agents SET events_count = COALESCE(events_count, 0) + ?, updated_at = ? WHERE agent_id = ?",
            (increment, _now_iso(), agent_id),
        )
        conn.commit()
    finally:
        conn.close()


def list_agents() -> List[Dict[str, Any]]:
    init_db()
    conn = _connect()
    try:
        rows = conn.execute(
            "SELECT agent_id, hostname, ip_address, os, version, status, last_seen, events_count FROM agents ORDER BY last_seen DESC"
        ).fetchall()
        return [dict(r) for r in rows]
    finally:
        conn.close()
