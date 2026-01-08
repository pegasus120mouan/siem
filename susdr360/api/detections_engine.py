import re
from datetime import datetime, timedelta, timezone
from typing import Any, Dict, List, Optional, Tuple

from .detections_registry import (
    create_alert,
    get_state,
    insert_auth_event,
    recent_evidence,
    upsert_state,
)


_RE_SSH_FAILED = re.compile(r"Failed password for (invalid user )?(?P<user>\S+) from (?P<src_ip>\d+\.\d+\.\d+\.\d+)")
_RE_SSH_SUCCESS = re.compile(r"Accepted (?P<method>password|publickey) for (?P<user>\S+) from (?P<src_ip>\d+\.\d+\.\d+\.\d+)")
_RE_SSH_INVALID_USER = re.compile(r"Invalid user (?P<user>\S+) from (?P<src_ip>\d+\.\d+\.\d+\.\d+)")
_RE_SUDO_CMD = re.compile(r"sudo: (?P<user>\S+) : .*COMMAND=(?P<cmd>.+)$")


_RISKY_CMDS = [
    "/bin/bash",
    " su ",
    "useradd",
    "usermod",
    "passwd",
    "visudo",
    "chmod 777",
    "curl",
    "wget",
]


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def _parse_dt(value: Optional[str]) -> datetime:
    if not value:
        return datetime.now(timezone.utc)
    try:
        return datetime.fromisoformat(value.replace("Z", "+00:00"))
    except Exception:
        return datetime.now(timezone.utc)


def parse_linux_auth_message(message: str) -> Optional[Dict[str, Any]]:
    msg = message or ""

    m = _RE_SSH_FAILED.search(msg)
    if m:
        return {
            "event_kind": "ssh_auth_failed",
            "src_ip": m.group("src_ip"),
            "username": m.group("user"),
            "auth_method": "password",
            "command": "",
        }

    m = _RE_SSH_SUCCESS.search(msg)
    if m:
        return {
            "event_kind": "ssh_auth_success",
            "src_ip": m.group("src_ip"),
            "username": m.group("user"),
            "auth_method": m.group("method"),
            "command": "",
        }

    m = _RE_SSH_INVALID_USER.search(msg)
    if m:
        return {
            "event_kind": "ssh_invalid_user",
            "src_ip": m.group("src_ip"),
            "username": m.group("user"),
            "auth_method": "",
            "command": "",
        }

    m = _RE_SUDO_CMD.search(msg)
    if m:
        return {
            "event_kind": "sudo_command",
            "src_ip": "",
            "username": m.group("user"),
            "auth_method": "",
            "command": m.group("cmd").strip(),
        }

    return None


def _state_key(rule_id: str, hostname: str, src_ip: str) -> str:
    return f"{rule_id}|{hostname}|{src_ip}"


def _update_counter(
    *,
    key: str,
    rule_id: str,
    window_seconds: int,
    now_iso: str,
    username: Optional[str],
) -> Tuple[int, str, str, List[str], Optional[str]]:
    st = get_state(key)
    now_dt = _parse_dt(now_iso)

    if not st:
        users = [username] if username else []
        upsert_state(
            key=key,
            rule_id=rule_id,
            count=1,
            window_seconds=window_seconds,
            first_seen=now_iso,
            last_seen=now_iso,
            users=users,
            last_alert_at=None,
        )
        return 1, now_iso, now_iso, users, None

    first_seen = st.get("first_seen") or now_iso
    first_dt = _parse_dt(first_seen)
    last_alert_at = st.get("last_alert_at")

    if (now_dt - first_dt).total_seconds() > float(window_seconds):
        users = [username] if username else []
        upsert_state(
            key=key,
            rule_id=rule_id,
            count=1,
            window_seconds=window_seconds,
            first_seen=now_iso,
            last_seen=now_iso,
            users=users,
            last_alert_at=last_alert_at,
        )
        return 1, now_iso, now_iso, users, last_alert_at

    users: List[str] = []
    try:
        import json

        users = json.loads(st.get("users_json") or "[]")
    except Exception:
        users = []

    if username and username not in users:
        users.append(username)

    count = int(st.get("count") or 0) + 1
    upsert_state(
        key=key,
        rule_id=rule_id,
        count=count,
        window_seconds=window_seconds,
        first_seen=first_seen,
        last_seen=now_iso,
        users=users,
        last_alert_at=last_alert_at,
    )
    return count, first_seen, now_iso, users, last_alert_at


def process_linux_auth_event(
    *,
    agent_id: str,
    hostname: str,
    message: str,
    observed_at_iso: Optional[str],
) -> None:
    parsed = parse_linux_auth_message(message)
    if not parsed:
        return

    event_kind = parsed.get("event_kind") or ""
    src_ip = parsed.get("src_ip") or ""
    username = parsed.get("username") or ""
    auth_method = parsed.get("auth_method") or ""
    command = parsed.get("command") or ""
    observed = observed_at_iso or _now_iso()

    insert_auth_event(
        agent_id=agent_id,
        hostname=hostname,
        event_kind=event_kind,
        src_ip=src_ip,
        username=username,
        auth_method=auth_method,
        command=command,
        message=message,
        observed_at_iso=observed,
    )

    if event_kind == "ssh_auth_failed" and src_ip:
        rule_id = "ssh_bruteforce_ip"
        window_seconds = 300
        threshold = 10
        key = _state_key(rule_id, hostname, src_ip)
        count, first_seen, last_seen, users, _ = _update_counter(
            key=key,
            rule_id=rule_id,
            window_seconds=window_seconds,
            now_iso=observed,
            username=username,
        )
        if count >= threshold:
            evidence = recent_evidence(
                hostname=hostname,
                src_ip=src_ip,
                event_kind="ssh_auth_failed",
                since_iso=first_seen,
                limit=20,
            )
            create_alert(
                rule_id=rule_id,
                severity="high",
                agent_id=agent_id,
                hostname=hostname,
                src_ip=src_ip,
                username="",
                count=count,
                window_seconds=window_seconds,
                first_seen=first_seen,
                last_seen=last_seen,
                evidence=evidence,
            )
            upsert_state(
                key=key,
                rule_id=rule_id,
                count=0,
                window_seconds=window_seconds,
                first_seen=last_seen,
                last_seen=last_seen,
                users=users,
                last_alert_at=_now_iso(),
            )

    if event_kind == "ssh_auth_success" and src_ip:
        rule_id = "ssh_success_after_fail"
        window_seconds = 600
        key = _state_key("ssh_bruteforce_ip", hostname, src_ip)
        st = get_state(key)
        if st and int(st.get("count") or 0) >= 5:
            first_seen = st.get("first_seen") or (datetime.now(timezone.utc) - timedelta(minutes=10)).isoformat()
            evidence = recent_evidence(
                hostname=hostname,
                src_ip=src_ip,
                event_kind="ssh_auth_failed",
                since_iso=first_seen,
                limit=20,
            )
            evidence = ([{"message": message, "event_kind": "ssh_auth_success", "observed_at": observed}] + evidence)[:20]
            create_alert(
                rule_id=rule_id,
                severity="critical",
                agent_id=agent_id,
                hostname=hostname,
                src_ip=src_ip,
                username=username,
                count=int(st.get("count") or 0),
                window_seconds=window_seconds,
                first_seen=first_seen,
                last_seen=observed,
                evidence=evidence,
            )

    if event_kind == "sudo_command" and command:
        risky = any(tok in command for tok in _RISKY_CMDS)
        if risky:
            rule_id = "sudo_risky_command"
            evidence = [{"message": message, "command": command, "observed_at": observed, "user": username}]
            create_alert(
                rule_id=rule_id,
                severity="high",
                agent_id=agent_id,
                hostname=hostname,
                src_ip="",
                username=username,
                count=1,
                window_seconds=0,
                first_seen=observed,
                last_seen=observed,
                evidence=evidence,
            )
