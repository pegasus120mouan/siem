"""
SUSDR 360 - Agents de Collecte
Agents pour la collecte d'événements depuis différentes sources
"""

from .windows_agent import WindowsAgent
from .linux_agent import LinuxAgent
from .network_agent import NetworkAgent
from .syslog_agent import SyslogAgent

__all__ = [
    'WindowsAgent',
    'LinuxAgent', 
    'NetworkAgent',
    'SyslogAgent'
]
