"""
SUSDR 360 - API Module
Interface REST pour l'accès aux fonctionnalités SUSDR 360
"""

from .main import create_app
from .routes import events, incidents, analytics, config
from .middleware import auth, cors, logging

__all__ = [
    'create_app'
]
