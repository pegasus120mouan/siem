import json
import sqlite3
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List, Optional


def _db_path() -> Path:
    base = Path(__file__).resolve().parents[1]
    data_dir = base / "data"
    data_dir.mkdir(parents=True, exist_ok=True)
    return data_dir / "detections.db"


def _connect() -> sqlite3.Connection:
    conn = sqlite3.connect(str(_db_path()))
    conn.row_factory = sqlite3.Row
    return conn


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def init_db() -> None:
    conn = _connect()
    try:
        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS auth_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                agent_id TEXT,
                hostname TEXT,
                event_kind TEXT,
                src_ip TEXT,
                username TEXT,
                auth_method TEXT,
                command TEXT,
                message TEXT,
                observed_at TEXT,
                created_at TEXT
            )
            """
        )
        conn.execute("CREATE INDEX IF NOT EXISTS idx_auth_events_observed_at ON auth_events(observed_at)")
        conn.execute("CREATE INDEX IF NOT EXISTS idx_auth_events_src_ip ON auth_events(src_ip)")
        conn.execute("CREATE INDEX IF NOT EXISTS idx_auth_events_kind ON auth_events(event_kind)")

        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rule_id TEXT,
                severity TEXT,
                agent_id TEXT,
                hostname TEXT,
                src_ip TEXT,
                username TEXT,
                count INTEGER,
                window_seconds INTEGER,
                first_seen TEXT,
                last_seen TEXT,
                evidence_json TEXT,
                created_at TEXT
            )
            """
        )
        conn.execute("CREATE INDEX IF NOT EXISTS idx_alerts_created_at ON alerts(created_at)")
        conn.execute("CREATE INDEX IF NOT EXISTS idx_alerts_rule_id ON alerts(rule_id)")

        conn.execute(
            """
            CREATE TABLE IF NOT EXISTS state (
                key TEXT PRIMARY KEY,
                rule_id TEXT,
                count INTEGER,
                window_seconds INTEGER,
                first_seen TEXT,
                last_seen TEXT,
                users_json TEXT,
                last_alert_at TEXT
            )
            """
        )
        conn.commit()
    finally:
        conn.close()


def insert_auth_event(
    *,
    agent_id: str,
    hostname: str,
    event_kind: str,
    src_ip: Optional[str],
    username: Optional[str],
    auth_method: Optional[str],
    command: Optional[str],
    message: str,
    observed_at_iso: Optional[str],
) -> None:
    init_db()
    conn = _connect()
    try:
        conn.execute(
            """
            INSERT INTO auth_events (agent_id, hostname, event_kind, src_ip, username, auth_method, command, message, observed_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """,
            (
                agent_id,
                hostname,
                event_kind,
                src_ip or "",
                username or "",
                auth_method or "",
                command or "",
                message,
                observed_at_iso or _now_iso(),
                _now_iso(),
            ),
        )
        conn.commit()
    finally:
        conn.close()


def get_state(key: str) -> Optional[Dict[str, Any]]:
    init_db()
    conn = _connect()
    try:
        row = conn.execute(
            "SELECT key, rule_id, count, window_seconds, first_seen, last_seen, users_json, last_alert_at FROM state WHERE key = ?",
            (key,),
        ).fetchone()
        return dict(row) if row else None
    finally:
        conn.close()


def upsert_state(
    *,
    key: str,
    rule_id: str,
    count: int,
    window_seconds: int,
    first_seen: str,
    last_seen: str,
    users: List[str],
    last_alert_at: Optional[str],
) -> None:
    init_db()
    conn = _connect()
    try:
        conn.execute(
            """
            INSERT INTO state (key, rule_id, count, window_seconds, first_seen, last_seen, users_json, last_alert_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(key) DO UPDATE SET
                rule_id=excluded.rule_id,
                count=excluded.count,
                window_seconds=excluded.window_seconds,
                first_seen=excluded.first_seen,
                last_seen=excluded.last_seen,
                users_json=excluded.users_json,
                last_alert_at=excluded.last_alert_at
            """,
            (key, rule_id, count, window_seconds, first_seen, last_seen, json.dumps(users), last_alert_at),
        )
        conn.commit()
    finally:
        conn.close()


def create_alert(
    *,
    rule_id: str,
    severity: str,
    agent_id: str,
    hostname: str,
    src_ip: Optional[str],
    username: Optional[str],
    count: int,
    window_seconds: int,
    first_seen: str,
    last_seen: str,
    evidence: List[Dict[str, Any]],
) -> None:
    init_db()
    conn = _connect()
    try:
        conn.execute(
            """
            INSERT INTO alerts (rule_id, severity, agent_id, hostname, src_ip, username, count, window_seconds, first_seen, last_seen, evidence_json, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """,
            (
                rule_id,
                severity,
                agent_id,
                hostname,
                src_ip or "",
                username or "",
                int(count),
                int(window_seconds),
                first_seen,
                last_seen,
                json.dumps(evidence),
                _now_iso(),
            ),
        )
        conn.commit()
    finally:
        conn.close()


def list_alerts(limit: int = 100) -> List[Dict[str, Any]]:
    init_db()
    conn = _connect()
    try:
        rows = conn.execute(
            """
            SELECT id, rule_id, severity, agent_id, hostname, src_ip, username, count, window_seconds, first_seen, last_seen, evidence_json, created_at
            FROM alerts
            ORDER BY created_at DESC
            LIMIT ?
            """,
            (int(limit),),
        ).fetchall()
        out: List[Dict[str, Any]] = []
        for r in rows:
            d = dict(r)
            try:
                d["evidence"] = json.loads(d.get("evidence_json") or "[]")
            except Exception:
                d["evidence"] = []
            d.pop("evidence_json", None)
            out.append(d)
        return out
    finally:
        conn.close()


def update_last_alert_at(key: str, last_alert_at: str) -> None:
    init_db()
    conn = _connect()
    try:
        conn.execute("UPDATE state SET last_alert_at = ? WHERE key = ?", (last_alert_at, key))
        conn.commit()
    finally:
        conn.close()


def recent_evidence(
    *,
    hostname: str,
    src_ip: Optional[str],
    event_kind: str,
    since_iso: str,
    limit: int = 20,
) -> List[Dict[str, Any]]:
    init_db()
    conn = _connect()
    try:
        rows = conn.execute(
            """
            SELECT agent_id, hostname, event_kind, src_ip, username, auth_method, command, message, observed_at
            FROM auth_events
            WHERE hostname = ?
              AND event_kind = ?
              AND observed_at >= ?
              AND (? = '' OR src_ip = ?)
            ORDER BY observed_at DESC
            LIMIT ?
            """,
            (
                hostname,
                event_kind,
                since_iso,
                (src_ip or ""),
                (src_ip or ""),
                int(limit),
            ),
        ).fetchall()
        return [dict(r) for r in rows]
    finally:
        conn.close()
