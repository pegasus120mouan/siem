#!/usr/bin/env python3

import argparse
import json
import os
import socket
import sys
import time
import urllib.error
import urllib.request
from datetime import datetime, timezone

DEFAULT_CONFIG_PATH = "/etc/siem-agent/config.json"
DEFAULT_STATE_PATH = "/var/lib/siem-agent/state.json"


def utc_now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def read_text_file_safe(path: str, max_bytes: int = 1024 * 128) -> str:
    try:
        with open(path, "rb") as f:
            data = f.read(max_bytes)
        return data.decode("utf-8", errors="replace")
    except Exception:
        return ""


def load_json(path: str) -> dict:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def save_json(path: str, data: dict) -> None:
    os.makedirs(os.path.dirname(path), exist_ok=True)
    tmp = f"{path}.tmp"
    with open(tmp, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2)
    os.replace(tmp, path)


def get_hostname() -> str:
    try:
        return socket.gethostname()
    except Exception:
        return "unknown"


def get_primary_ip() -> str:
    # Best-effort: determine outbound IP without sending packets
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return ""


def read_meminfo() -> dict:
    info = {}
    try:
        with open("/proc/meminfo", "r", encoding="utf-8") as f:
            for line in f:
                parts = line.split(":", 1)
                if len(parts) != 2:
                    continue
                key = parts[0].strip()
                rest = parts[1].strip().split()
                if not rest:
                    continue
                val = rest[0]
                try:
                    info[key] = int(val)
                except ValueError:
                    continue
    except Exception:
        return {}
    return info


def read_cpu_stat() -> list[int]:
    try:
        with open("/proc/stat", "r", encoding="utf-8") as f:
            for line in f:
                if line.startswith("cpu "):
                    parts = line.split()
                    # cpu user nice system idle iowait irq softirq steal guest guest_nice
                    nums = [int(p) for p in parts[1:8]]
                    return nums
    except Exception:
        return []
    return []


def cpu_usage_percent(prev: list[int], cur: list[int]) -> float:
    if len(prev) < 4 or len(cur) < 4:
        return 0.0
    prev_idle = prev[3] + (prev[4] if len(prev) > 4 else 0)
    cur_idle = cur[3] + (cur[4] if len(cur) > 4 else 0)

    prev_total = sum(prev)
    cur_total = sum(cur)

    total_delta = cur_total - prev_total
    idle_delta = cur_idle - prev_idle
    if total_delta <= 0:
        return 0.0
    return max(0.0, min(100.0, (1.0 - (idle_delta / total_delta)) * 100.0))


def build_event(source: str, raw_data: dict) -> dict:
    return {
        "source": source,
        "raw_data": raw_data,
        "timestamp": utc_now_iso(),
    }


def post_json(url: str, token: str, payload: dict, timeout_seconds: int = 10) -> None:
    data = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(url, data=data, method="POST")
    req.add_header("Content-Type", "application/json")
    req.add_header("Authorization", f"Bearer {token}")
    with urllib.request.urlopen(req, timeout=timeout_seconds) as resp:
        _ = resp.read(1024)


def normalize_base_url(base_url: str) -> str:
    base = (base_url or "").strip()
    if not base:
        return ""
    # Accept raw IP/domain by defaulting to http://
    if "://" not in base:
        base = f"http://{base}"
    return base.rstrip("/")


def ingest_url(base_url: str) -> str:
    base = normalize_base_url(base_url)
    return f"{base}/api/v1/events/ingest"


def tail_file_lines(path: str, start_offset: int) -> tuple[int, list[str]]:
    try:
        with open(path, "rb") as f:
            f.seek(0, os.SEEK_END)
            end = f.tell()
            if start_offset > end:
                start_offset = end
            f.seek(start_offset)
            data = f.read()
            new_offset = f.tell()
        if not data:
            return new_offset, []
        text = data.decode("utf-8", errors="replace")
        lines = [ln for ln in text.splitlines() if ln.strip()]
        return new_offset, lines
    except FileNotFoundError:
        return start_offset, []
    except PermissionError:
        return start_offset, []
    except Exception:
        return start_offset, []


def run(config_path: str, state_path: str) -> int:
    config = load_json(config_path)
    base_url = config.get("server", {}).get("url", "")
    token = config.get("server", {}).get("auth_key", "")

    if not base_url or not token:
        print("Missing server.url or server.auth_key in config", file=sys.stderr)
        return 2

    poll_seconds = int(config.get("agent", {}).get("poll_seconds", 2))
    heartbeat_seconds = int(config.get("agent", {}).get("heartbeat_seconds", 30))
    max_lines_per_cycle = int(config.get("agent", {}).get("max_lines_per_cycle", 50))

    syslog_path = config.get("inputs", {}).get("syslog", "/var/log/syslog")
    authlog_path = config.get("inputs", {}).get("authlog", "/var/log/auth.log")

    ingest = ingest_url(base_url)

    state = {"syslog_offset": 0, "authlog_offset": 0}
    try:
        if os.path.exists(state_path):
            state = load_json(state_path)
    except Exception:
        state = {"syslog_offset": 0, "authlog_offset": 0}

    last_hb = 0.0
    prev_cpu = read_cpu_stat()

    hostname = get_hostname()
    ip = get_primary_ip()

    while True:
        now = time.time()

        # Heartbeat
        if now - last_hb >= heartbeat_seconds:
            cur_cpu = read_cpu_stat()
            cpu_pct = cpu_usage_percent(prev_cpu, cur_cpu)
            prev_cpu = cur_cpu

            mem = read_meminfo()
            mem_total_kb = mem.get("MemTotal", 0)
            mem_available_kb = mem.get("MemAvailable", 0)

            hb = build_event(
                source="linux_agent_heartbeat",
                raw_data={
                    "host": hostname,
                    "ip": ip,
                    "cpu_usage_percent": round(cpu_pct, 2),
                    "mem_total_kb": mem_total_kb,
                    "mem_available_kb": mem_available_kb,
                    "timestamp": utc_now_iso(),
                },
            )

            try:
                post_json(ingest, token, hb)
            except Exception:
                pass

            last_hb = now

        # Syslog
        syslog_offset = int(state.get("syslog_offset", 0) or 0)
        syslog_offset, sys_lines = tail_file_lines(syslog_path, syslog_offset)
        if sys_lines:
            for ln in sys_lines[:max_lines_per_cycle]:
                evt = build_event(
                    source="linux_syslog",
                    raw_data={
                        "host": hostname,
                        "ip": ip,
                        "file": syslog_path,
                        "message": ln,
                    },
                )
                try:
                    post_json(ingest, token, evt)
                except Exception:
                    pass

        # Auth log
        auth_offset = int(state.get("authlog_offset", 0) or 0)
        auth_offset, auth_lines = tail_file_lines(authlog_path, auth_offset)
        if auth_lines:
            for ln in auth_lines[:max_lines_per_cycle]:
                evt = build_event(
                    source="linux_auth",
                    raw_data={
                        "host": hostname,
                        "ip": ip,
                        "file": authlog_path,
                        "message": ln,
                    },
                )
                try:
                    post_json(ingest, token, evt)
                except Exception:
                    pass

        state["syslog_offset"] = syslog_offset
        state["authlog_offset"] = auth_offset
        try:
            save_json(state_path, state)
        except Exception:
            pass

        time.sleep(poll_seconds)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--config", default=DEFAULT_CONFIG_PATH)
    parser.add_argument("--state", default=DEFAULT_STATE_PATH)
    args = parser.parse_args()
    return run(args.config, args.state)


if __name__ == "__main__":
    raise SystemExit(main())
