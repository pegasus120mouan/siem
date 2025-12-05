#!/usr/bin/env python3
"""
SUSDR 360 - Démarrage Simplifié
Version de démarrage basique pour tester l'installation
"""

import sys
import logging
from pathlib import Path
from fastapi import FastAPI
from fastapi.responses import HTMLResponse
import uvicorn

# Configuration du logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

def create_simple_app():
    """Crée une application FastAPI simplifiée"""
    app = FastAPI(
        title="SUSDR 360 - Version Simplifiée",
        description="Système Unifié de Surveillance, Détection et Réponse",
        version="1.0.0"
    )
    
    @app.get("/")
    async def root():
        return {"message": "SUSDR 360 - Système opérationnel", "version": "1.0.0"}
    
    @app.get("/health")
    async def health():
        return {
            "status": "healthy",
            "components": {
                "api": "active",
                "system": "operational"
            }
        }
    
    # Routes Dashboard pour intégration PHP
    @app.get("/api/v1/dashboard/stats")
    async def dashboard_stats():
        import random
        from datetime import datetime
        return {
            "total_events_today": random.randint(1500, 3000),
            "total_events_week": random.randint(10000, 25000),
            "critical_alerts": random.randint(5, 15),
            "high_alerts": random.randint(20, 50),
            "medium_alerts": random.randint(100, 200),
            "low_alerts": random.randint(500, 1000),
            "blocked_attacks": random.randint(10, 30),
            "suspicious_ips": random.randint(5, 20),
            "malware_detected": random.randint(2, 8),
            "last_update": datetime.now().isoformat(),
            "total_alerts": random.randint(625, 1265),
            "attack_success_rate": round(random.uniform(85, 99), 2),
            "system_health": "operational"
        }
    
    @app.get("/api/v1/dashboard/incidents")
    async def dashboard_incidents():
        import random
        from datetime import datetime, timedelta
        incidents = []
        for i in range(10):
            incident_time = datetime.now() - timedelta(hours=random.randint(1, 24))
            incidents.append({
                "id": f"INC-{2024}{random.randint(1000, 9999)}",
                "title": random.choice([
                    "Tentative d'intrusion détectée",
                    "Malware bloqué sur poste utilisateur", 
                    "Activité réseau suspecte",
                    "Échecs de connexion multiples",
                    "Processus suspect détecté"
                ]),
                "severity": random.choice(["critical", "high", "medium", "low"]),
                "status": random.choice(["new", "investigating", "resolved"]),
                "timestamp": incident_time.isoformat(),
                "source": random.choice(["Windows Security", "Firewall", "Antivirus", "Network Monitor"])
            })
        return {"incidents": sorted(incidents, key=lambda x: x["timestamp"], reverse=True), "total": len(incidents)}
    
    @app.get("/api/v1/dashboard/threats")
    async def dashboard_threats():
        import random
        threats = [
            {"name": "Brute Force Attack", "count": random.randint(50, 150), "severity": "high"},
            {"name": "Malware Detection", "count": random.randint(20, 80), "severity": "critical"},
            {"name": "Suspicious Network Activity", "count": random.randint(30, 100), "severity": "medium"},
            {"name": "Failed Login Attempts", "count": random.randint(100, 300), "severity": "medium"},
            {"name": "Port Scanning", "count": random.randint(10, 50), "severity": "low"}
        ]
        return {"threats": sorted(threats, key=lambda x: x["count"], reverse=True), "total": len(threats)}
    
    @app.get("/api/v1/dashboard/system")
    async def dashboard_system():
        import random
        return {
            "status": "operational",
            "uptime": "2 days, 5 hours",
            "cpu_usage": random.randint(20, 80),
            "memory_usage": random.randint(30, 70),
            "disk_usage": random.randint(40, 85),
            "components": {
                "event_processor": "active",
                "correlation_engine": "active", 
                "ai_detector": "active",
                "api_server": "active",
                "database": "active"
            },
            "performance": {
                "events_per_second": random.randint(50, 200),
                "avg_response_time": round(random.uniform(0.1, 2.0), 3),
                "error_rate": round(random.uniform(0.1, 2.0), 2)
            }
        }
    
    @app.get("/web", response_class=HTMLResponse)
    async def web_interface():
        """Interface web basique"""
        html_content = """
        <!DOCTYPE html>
        <html>
        <head>
            <title>SUSDR 360</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #1a1a1a; color: white; }
                .container { max-width: 800px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 40px; }
                .status { background: #2a2a2a; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .success { color: #4ade80; }
                .info { color: #60a5fa; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🛡️ SUSDR 360</h1>
                    <p>Système Unifié de Surveillance, Détection et Réponse</p>
                    <p><strong>SAHANALYTICS - Fisher Ouattara</strong></p>
                </div>
                
                <div class="status">
                    <h2 class="success">✅ Système Opérationnel</h2>
                    <p>Le système SUSDR 360 a été installé et démarré avec succès.</p>
                </div>
                
                <div class="status">
                    <h3 class="info">📊 Composants Disponibles</h3>
                    <ul>
                        <li>✅ API REST - <a href="/docs" style="color: #60a5fa;">Documentation</a></li>
                        <li>✅ Interface Web - Cette page</li>
                        <li>✅ Système de santé - <a href="/health" style="color: #60a5fa;">Health Check</a></li>
                    </ul>
                </div>
                
                <div class="status">
                    <h3 class="info">🚀 Prochaines Étapes</h3>
                    <ol>
                        <li>Installer les dépendances complètes : <code>pip install -r requirements.txt</code></li>
                        <li>Configurer le système : Éditer <code>config.yaml</code></li>
                        <li>Démarrer le système complet : <code>python main.py</code></li>
                    </ol>
                </div>
                
                <div class="status">
                    <h3 class="info">📚 Documentation</h3>
                    <p>Consultez le fichier <code>README.md</code> pour plus d'informations.</p>
                </div>
            </div>
        </body>
        </html>
        """
        return html_content
    
    return app

def main():
    """Fonction principale"""
    print("""
    ===============================================================
                        SUSDR 360 - DEMARRAGE                     
            Systeme Unifie de Surveillance, Detection            
                        et Reponse v1.0.0                         
                                                               
                  SAHANALYTICS - Fisher Ouattara                  
                        Cote d'Ivoire 2026                        
    ===============================================================
    """)
    
    logger.info("Démarrage de SUSDR 360 en mode simplifié")
    
    try:
        app = create_simple_app()
        
        logger.info("API SUSDR 360 démarrée sur http://localhost:8000")
        logger.info("Interface web disponible sur http://localhost:8000/web")
        logger.info("Documentation API sur http://localhost:8000/docs")
        
        uvicorn.run(
            app,
            host="0.0.0.0",
            port=8000,
            log_level="info"
        )
        
    except KeyboardInterrupt:
        logger.info("Arrêt du système SUSDR 360")
    except Exception as e:
        logger.error(f"Erreur: {e}")
        sys.exit(1)

if __name__ == "__main__":
    main()
