"""
SUSDR 360 - Middleware de logging
"""

import logging

logger = logging.getLogger(__name__)

def setup_logging():
    """Configuration du logging"""
    logger.info("Logging configuré")
