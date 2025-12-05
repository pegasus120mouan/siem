"""
SUSDR 360 - Routes Dashboard
Endpoints spéciaux pour intégration avec le dashboard existant
"""

from fastapi import APIRouter, HTTPException, Query
from fastapi.responses import JSONResponse
from typing import List, Optional, Dict, Any
import logging
import json
from datetime import datetime, timedelta
import random

logger = logging.getLogger(__name__)
router = APIRouter()

# Simulation de données pour la démonstration
def get_mock_events_data():
    """Génère des données d'événements simulées"""
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
        "last_update": datetime.now().isoformat()
    }

def get_mock_top_threats():
    """Génère les top menaces"""
    threats = [
        {"name": "Brute Force Attack", "count": random.randint(50, 150), "severity": "high"},
        {"name": "Malware Detection", "count": random.randint(20, 80), "severity": "critical"},
        {"name": "Suspicious Network Activity", "count": random.randint(30, 100), "severity": "medium"},
        {"name": "Failed Login Attempts", "count": random.randint(100, 300), "severity": "medium"},
        {"name": "Port Scanning", "count": random.randint(10, 50), "severity": "low"}
    ]
    return sorted(threats, key=lambda x: x["count"], reverse=True)

def get_mock_recent_incidents():
    """Génère les incidents récents"""
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
    return sorted(incidents, key=lambda x: x["timestamp"], reverse=True)

@router.get("/stats")
async def get_dashboard_stats():
    """Statistiques principales pour le dashboard"""
    try:
        stats = get_mock_events_data()
        
        # Ajout de métriques calculées
        stats["total_alerts"] = stats["critical_alerts"] + stats["high_alerts"] + stats["medium_alerts"] + stats["low_alerts"]
        stats["attack_success_rate"] = round((stats["blocked_attacks"] / (stats["blocked_attacks"] + random.randint(1, 5))) * 100, 2)
        stats["system_health"] = "operational"
        
        return JSONResponse(content=stats)
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des stats: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/threats")
async def get_top_threats(limit: int = Query(10, ge=1, le=50)):
    """Top des menaces détectées"""
    try:
        threats = get_mock_top_threats()[:limit]
        return JSONResponse(content={"threats": threats, "total": len(threats)})
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des menaces: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/incidents")
async def get_recent_incidents(limit: int = Query(10, ge=1, le=100)):
    """Incidents récents"""
    try:
        incidents = get_mock_recent_incidents()[:limit]
        return JSONResponse(content={"incidents": incidents, "total": len(incidents)})
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des incidents: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/timeline")
async def get_events_timeline(hours: int = Query(24, ge=1, le=168)):
    """Timeline des événements sur X heures"""
    try:
        timeline = []
        now = datetime.now()
        
        for i in range(hours):
            hour_time = now - timedelta(hours=i)
            timeline.append({
                "timestamp": hour_time.isoformat(),
                "hour": hour_time.strftime("%H:00"),
                "events": random.randint(50, 200),
                "alerts": random.randint(5, 25),
                "threats": random.randint(0, 5)
            })
        
        timeline.reverse()  # Ordre chronologique
        return JSONResponse(content={"timeline": timeline, "period_hours": hours})
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération de la timeline: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/network")
async def get_network_stats():
    """Statistiques réseau"""
    try:
        network_stats = {
            "total_connections": random.randint(1000, 5000),
            "blocked_connections": random.randint(50, 200),
            "suspicious_ips": random.randint(10, 50),
            "top_countries": [
                {"country": "Côte d'Ivoire", "connections": random.randint(500, 1000), "blocked": random.randint(5, 20)},
                {"country": "France", "connections": random.randint(200, 500), "blocked": random.randint(10, 30)},
                {"country": "USA", "connections": random.randint(100, 300), "blocked": random.randint(15, 40)},
                {"country": "China", "connections": random.randint(50, 150), "blocked": random.randint(20, 60)},
                {"country": "Russia", "connections": random.randint(30, 100), "blocked": random.randint(25, 70)}
            ],
            "protocols": {
                "HTTP": random.randint(1000, 2000),
                "HTTPS": random.randint(2000, 3000),
                "SSH": random.randint(50, 150),
                "FTP": random.randint(20, 80),
                "SMB": random.randint(100, 300)
            }
        }
        
        return JSONResponse(content=network_stats)
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération des stats réseau: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/users")
async def get_user_activity():
    """Activité des utilisateurs"""
    try:
        users = [
            {"username": "john.doe", "events": random.randint(50, 200), "last_login": "2024-12-05T10:30:00", "risk_score": random.randint(1, 10)},
            {"username": "marie.kouame", "events": random.randint(30, 150), "last_login": "2024-12-05T09:15:00", "risk_score": random.randint(1, 10)},
            {"username": "admin", "events": random.randint(100, 300), "last_login": "2024-12-05T11:00:00", "risk_score": random.randint(1, 10)},
            {"username": "service_account", "events": random.randint(200, 500), "last_login": "2024-12-05T08:00:00", "risk_score": random.randint(1, 10)},
            {"username": "guest", "events": random.randint(5, 50), "last_login": "2024-12-04T16:45:00", "risk_score": random.randint(1, 10)}
        ]
        
        return JSONResponse(content={"users": users, "total": len(users)})
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération de l'activité utilisateurs: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/system")
async def get_system_health():
    """Santé du système SUSDR 360"""
    try:
        system_health = {
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
        
        return JSONResponse(content=system_health)
        
    except Exception as e:
        logger.error(f"Erreur lors de la récupération de la santé système: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

@router.get("/export/csv")
async def export_data_csv(data_type: str = Query("events", regex="^(events|incidents|threats)$")):
    """Export des données en CSV"""
    try:
        if data_type == "events":
            # Simulation d'export d'événements
            csv_content = "timestamp,source,event_type,severity,description\n"
            for i in range(100):
                csv_content += f"2024-12-05T{random.randint(8,17):02d}:{random.randint(0,59):02d}:00,Windows Security,4624,Low,Successful logon\n"
        
        elif data_type == "incidents":
            incidents = get_mock_recent_incidents()
            csv_content = "id,title,severity,status,timestamp,source\n"
            for incident in incidents:
                csv_content += f"{incident['id']},{incident['title']},{incident['severity']},{incident['status']},{incident['timestamp']},{incident['source']}\n"
        
        elif data_type == "threats":
            threats = get_mock_top_threats()
            csv_content = "name,count,severity\n"
            for threat in threats:
                csv_content += f"{threat['name']},{threat['count']},{threat['severity']}\n"
        
        return JSONResponse(content={
            "download_url": f"/api/v1/dashboard/download/{data_type}.csv",
            "file_size": len(csv_content),
            "generated_at": datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"Erreur lors de l'export: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")

# Endpoint spécial pour intégration PHP
@router.get("/php-integration")
async def get_php_integration_data():
    """Données formatées spécialement pour l'intégration PHP"""
    try:
        # Format compatible avec votre dashboard PHP existant
        data = {
            "susdr360_status": "active",
            "version": "1.0.0",
            "stats": get_mock_events_data(),
            "recent_alerts": get_mock_recent_incidents()[:5],
            "top_threats": get_mock_top_threats()[:5],
            "integration": {
                "api_endpoint": "http://localhost:8000/api/v1/dashboard",
                "last_sync": datetime.now().isoformat(),
                "sync_status": "success"
            }
        }
        
        return JSONResponse(content=data)
        
    except Exception as e:
        logger.error(f"Erreur lors de l'intégration PHP: {e}")
        raise HTTPException(status_code=500, detail="Erreur interne")
