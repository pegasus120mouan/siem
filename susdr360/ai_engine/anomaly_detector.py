"""
Anomaly Detector - Détecteur d'anomalies basé sur l'IA
Utilise des algorithmes de machine learning pour détecter les comportements anormaux
"""

import numpy as np
import pandas as pd
import logging
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional, Tuple
from dataclasses import dataclass
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.cluster import DBSCAN
from sklearn.decomposition import PCA
import joblib
import json

from ..core.event_processor import Event, EventType, Severity

logger = logging.getLogger(__name__)

@dataclass
class AnomalyResult:
    """Résultat de détection d'anomalie"""
    event_id: str
    anomaly_score: float
    is_anomaly: bool
    confidence: float
    features_used: List[str]
    explanation: str
    model_used: str
    timestamp: datetime

class AnomalyDetector:
    """Détecteur d'anomalies principal"""
    
    def __init__(self, config: Dict[str, Any]):
        self.config = config
        self.models = {}
        self.scalers = {}
        self.encoders = {}
        self.feature_extractors = {}
        
        # Configuration des modèles
        self.model_config = {
            'isolation_forest': {
                'contamination': config.get('contamination', 0.1),
                'n_estimators': config.get('n_estimators', 100),
                'random_state': 42
            },
            'dbscan': {
                'eps': config.get('eps', 0.5),
                'min_samples': config.get('min_samples', 5)
            }
        }
        
        # Historique pour l'apprentissage
        self.training_data = []
        self.max_training_samples = config.get('max_training_samples', 10000)
        
        # Statistiques
        self.stats = {
            'events_analyzed': 0,
            'anomalies_detected': 0,
            'models_trained': 0,
            'false_positives': 0,
            'true_positives': 0
        }
        
        logger.info("AnomalyDetector initialisé")
    
    async def analyze_event(self, event: Event) -> Optional[AnomalyResult]:
        """Analyse un événement pour détecter des anomalies"""
        try:
            self.stats['events_analyzed'] += 1
            
            # Extraction des features
            features = self._extract_features(event)
            if not features:
                return None
            
            # Sélection du modèle approprié
            model_name = self._select_model(event)
            
            # Détection d'anomalie
            anomaly_result = await self._detect_anomaly(event, features, model_name)
            
            # Mise à jour des données d'entraînement
            self._update_training_data(event, features, anomaly_result)
            
            if anomaly_result and anomaly_result.is_anomaly:
                self.stats['anomalies_detected'] += 1
                logger.warning(f"Anomalie détectée: {anomaly_result.explanation}")
            
            return anomaly_result
            
        except Exception as e:
            logger.error(f"Erreur lors de l'analyse d'anomalie: {e}")
            return None
    
    def _extract_features(self, event: Event) -> Optional[Dict[str, Any]]:
        """Extrait les features d'un événement pour l'analyse ML"""
        try:
            features = {}
            
            # Features temporelles
            features.update(self._extract_temporal_features(event))
            
            # Features par type d'événement
            if event.event_type == EventType.NETWORK:
                features.update(self._extract_network_features(event))
            elif event.event_type == EventType.AUTHENTICATION:
                features.update(self._extract_auth_features(event))
            elif event.event_type == EventType.ENDPOINT:
                features.update(self._extract_endpoint_features(event))
            elif event.event_type == EventType.SYSTEM:
                features.update(self._extract_system_features(event))
            
            # Features générales
            features.update(self._extract_general_features(event))
            
            return features
            
        except Exception as e:
            logger.error(f"Erreur lors de l'extraction de features: {e}")
            return None
    
    def _extract_temporal_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features temporelles"""
        features = {}
        
        # Heure de la journée (0-23)
        features['hour_of_day'] = event.timestamp.hour
        
        # Jour de la semaine (0-6)
        features['day_of_week'] = event.timestamp.weekday()
        
        # Est-ce un weekend ?
        features['is_weekend'] = 1 if event.timestamp.weekday() >= 5 else 0
        
        # Est-ce en dehors des heures de bureau ?
        features['is_after_hours'] = 1 if event.timestamp.hour < 8 or event.timestamp.hour > 18 else 0
        
        return features
    
    def _extract_network_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features réseau"""
        features = {}
        normalized = event.normalized_data
        
        # Ports
        src_port = normalized.get('src_port', 0)
        dst_port = normalized.get('dst_port', 0)
        
        features['src_port'] = src_port
        features['dst_port'] = dst_port
        features['is_high_port'] = 1 if src_port > 1024 or dst_port > 1024 else 0
        features['is_well_known_port'] = 1 if dst_port < 1024 else 0
        
        # Protocole
        protocol = normalized.get('protocol', 'unknown').lower()
        features['protocol_tcp'] = 1 if protocol == 'tcp' else 0
        features['protocol_udp'] = 1 if protocol == 'udp' else 0
        features['protocol_icmp'] = 1 if protocol == 'icmp' else 0
        
        # Taille des données
        bytes_transferred = normalized.get('bytes', 0)
        features['bytes'] = bytes_transferred
        features['is_large_transfer'] = 1 if bytes_transferred > 1000000 else 0  # > 1MB
        
        # Géolocalisation (si disponible)
        if 'country' in event.enrichment_data:
            country = event.enrichment_data['country']
            features['is_foreign_country'] = 1 if country not in ['CI', 'Côte d\'Ivoire'] else 0
        
        return features
    
    def _extract_auth_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features d'authentification"""
        features = {}
        normalized = event.normalized_data
        
        # Résultat de l'authentification
        auth_result = normalized.get('auth_result', 'unknown').lower()
        features['auth_success'] = 1 if auth_result == 'success' else 0
        features['auth_failed'] = 1 if auth_result == 'failed' else 0
        
        # Type d'authentification
        auth_type = normalized.get('auth_type', 'unknown').lower()
        features['auth_type_local'] = 1 if auth_type == 'local' else 0
        features['auth_type_domain'] = 1 if auth_type == 'domain' else 0
        features['auth_type_remote'] = 1 if auth_type == 'remote' else 0
        
        # Utilisateur
        username = normalized.get('username', '').lower()
        features['is_admin_user'] = 1 if 'admin' in username or 'root' in username else 0
        features['is_service_account'] = 1 if username.startswith('svc_') or username.endswith('$') else 0
        
        return features
    
    def _extract_endpoint_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features d'endpoint"""
        features = {}
        normalized = event.normalized_data
        
        # Processus
        process_name = normalized.get('process_name', '').lower()
        features['is_system_process'] = 1 if process_name in ['svchost.exe', 'explorer.exe', 'winlogon.exe'] else 0
        features['is_powershell'] = 1 if 'powershell' in process_name else 0
        features['is_cmd'] = 1 if process_name in ['cmd.exe', 'command.com'] else 0
        
        # Fichiers
        file_path = normalized.get('file_path', '').lower()
        features['is_temp_file'] = 1 if 'temp' in file_path or 'tmp' in file_path else 0
        features['is_system_file'] = 1 if file_path.startswith('c:\\windows\\system32') else 0
        
        # Actions
        action = normalized.get('action', '').lower()
        features['action_create'] = 1 if action == 'create' else 0
        features['action_delete'] = 1 if action == 'delete' else 0
        features['action_modify'] = 1 if action == 'modify' else 0
        
        return features
    
    def _extract_system_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features système"""
        features = {}
        normalized = event.normalized_data
        
        # Niveau de log
        log_level = normalized.get('log_level', '').lower()
        features['log_error'] = 1 if log_level == 'error' else 0
        features['log_warning'] = 1 if log_level == 'warning' else 0
        features['log_info'] = 1 if log_level == 'info' else 0
        
        # Source système
        source = event.source.lower()
        features['source_windows'] = 1 if 'windows' in source else 0
        features['source_linux'] = 1 if 'linux' in source or 'syslog' in source else 0
        features['source_network'] = 1 if 'firewall' in source or 'router' in source else 0
        
        return features
    
    def _extract_general_features(self, event: Event) -> Dict[str, Any]:
        """Extrait les features générales"""
        features = {}
        
        # Sévérité
        features['severity'] = event.severity.value
        
        # Nombre de tags
        features['tag_count'] = len(event.tags)
        
        # Taille de la description
        features['description_length'] = len(event.description)
        
        # Présence d'enrichissement
        features['has_enrichment'] = 1 if event.enrichment_data else 0
        
        return features
    
    def _select_model(self, event: Event) -> str:
        """Sélectionne le modèle approprié selon le type d'événement"""
        if event.event_type == EventType.NETWORK:
            return 'network_isolation_forest'
        elif event.event_type == EventType.AUTHENTICATION:
            return 'auth_isolation_forest'
        elif event.event_type == EventType.ENDPOINT:
            return 'endpoint_dbscan'
        else:
            return 'general_isolation_forest'
    
    async def _detect_anomaly(self, event: Event, features: Dict[str, Any], model_name: str) -> Optional[AnomalyResult]:
        """Détecte les anomalies avec le modèle spécifié"""
        try:
            # Vérifie si le modèle existe
            if model_name not in self.models:
                await self._train_model(model_name, event.event_type)
            
            model = self.models.get(model_name)
            if not model:
                return None
            
            # Prépare les données
            feature_vector = self._prepare_feature_vector(features, model_name)
            if feature_vector is None:
                return None
            
            # Prédiction
            if 'isolation_forest' in model_name:
                anomaly_score = model.decision_function([feature_vector])[0]
                is_anomaly = model.predict([feature_vector])[0] == -1
                confidence = abs(anomaly_score)
            elif 'dbscan' in model_name:
                # Pour DBSCAN, on utilise la distance au cluster le plus proche
                cluster_label = model.fit_predict([feature_vector])[0]
                is_anomaly = cluster_label == -1  # -1 = outlier dans DBSCAN
                anomaly_score = -1.0 if is_anomaly else 1.0
                confidence = 0.8 if is_anomaly else 0.2
            else:
                return None
            
            # Génère l'explication
            explanation = self._generate_explanation(event, features, is_anomaly, model_name)
            
            return AnomalyResult(
                event_id=event.id,
                anomaly_score=anomaly_score,
                is_anomaly=is_anomaly,
                confidence=confidence,
                features_used=list(features.keys()),
                explanation=explanation,
                model_used=model_name,
                timestamp=datetime.now()
            )
            
        except Exception as e:
            logger.error(f"Erreur lors de la détection d'anomalie avec {model_name}: {e}")
            return None
    
    def _prepare_feature_vector(self, features: Dict[str, Any], model_name: str) -> Optional[np.ndarray]:
        """Prépare le vecteur de features pour le modèle"""
        try:
            # Récupère les features attendues par le modèle
            expected_features = self._get_expected_features(model_name)
            
            # Crée le vecteur avec les valeurs par défaut pour les features manquantes
            feature_vector = []
            for feature_name in expected_features:
                value = features.get(feature_name, 0)
                
                # Encode les valeurs catégorielles si nécessaire
                if isinstance(value, str):
                    encoder_key = f"{model_name}_{feature_name}"
                    if encoder_key in self.encoders:
                        try:
                            value = self.encoders[encoder_key].transform([value])[0]
                        except:
                            value = 0  # Valeur inconnue
                    else:
                        value = hash(value) % 1000  # Hash simple pour les nouvelles valeurs
                
                feature_vector.append(float(value))
            
            # Normalisation si un scaler existe
            if model_name in self.scalers:
                feature_vector = self.scalers[model_name].transform([feature_vector])[0]
            
            return np.array(feature_vector)
            
        except Exception as e:
            logger.error(f"Erreur lors de la préparation du vecteur de features: {e}")
            return None
    
    def _get_expected_features(self, model_name: str) -> List[str]:
        """Retourne la liste des features attendues par le modèle"""
        # Features de base communes
        base_features = [
            'hour_of_day', 'day_of_week', 'is_weekend', 'is_after_hours',
            'severity', 'tag_count', 'description_length', 'has_enrichment'
        ]
        
        if 'network' in model_name:
            return base_features + [
                'src_port', 'dst_port', 'is_high_port', 'is_well_known_port',
                'protocol_tcp', 'protocol_udp', 'protocol_icmp',
                'bytes', 'is_large_transfer', 'is_foreign_country'
            ]
        elif 'auth' in model_name:
            return base_features + [
                'auth_success', 'auth_failed', 'auth_type_local',
                'auth_type_domain', 'auth_type_remote', 'is_admin_user', 'is_service_account'
            ]
        elif 'endpoint' in model_name:
            return base_features + [
                'is_system_process', 'is_powershell', 'is_cmd',
                'is_temp_file', 'is_system_file', 'action_create', 'action_delete', 'action_modify'
            ]
        else:
            return base_features
    
    async def _train_model(self, model_name: str, event_type: EventType):
        """Entraîne un modèle de détection d'anomalies"""
        try:
            logger.info(f"Entraînement du modèle {model_name}")
            
            # Récupère les données d'entraînement
            training_features = self._get_training_data(event_type)
            if len(training_features) < 10:  # Minimum de données requis
                logger.warning(f"Pas assez de données pour entraîner {model_name}")
                return
            
            # Prépare les données
            feature_names = self._get_expected_features(model_name)
            X = []
            
            for features in training_features:
                feature_vector = []
                for feature_name in feature_names:
                    value = features.get(feature_name, 0)
                    if isinstance(value, str):
                        value = hash(value) % 1000
                    feature_vector.append(float(value))
                X.append(feature_vector)
            
            X = np.array(X)
            
            # Normalisation
            scaler = StandardScaler()
            X_scaled = scaler.fit_transform(X)
            self.scalers[model_name] = scaler
            
            # Entraînement du modèle
            if 'isolation_forest' in model_name:
                model = IsolationForest(**self.model_config['isolation_forest'])
                model.fit(X_scaled)
            elif 'dbscan' in model_name:
                model = DBSCAN(**self.model_config['dbscan'])
                model.fit(X_scaled)
            else:
                logger.error(f"Type de modèle inconnu: {model_name}")
                return
            
            self.models[model_name] = model
            self.stats['models_trained'] += 1
            
            logger.info(f"Modèle {model_name} entraîné avec {len(X)} échantillons")
            
        except Exception as e:
            logger.error(f"Erreur lors de l'entraînement du modèle {model_name}: {e}")
    
    def _get_training_data(self, event_type: EventType) -> List[Dict[str, Any]]:
        """Récupère les données d'entraînement pour un type d'événement"""
        return [
            data['features'] for data in self.training_data
            if data['event_type'] == event_type
        ]
    
    def _update_training_data(self, event: Event, features: Dict[str, Any], anomaly_result: Optional[AnomalyResult]):
        """Met à jour les données d'entraînement"""
        # Ajoute seulement les événements normaux pour l'entraînement
        if not anomaly_result or not anomaly_result.is_anomaly:
            training_sample = {
                'event_type': event.event_type,
                'features': features,
                'timestamp': event.timestamp,
                'is_anomaly': anomaly_result.is_anomaly if anomaly_result else False
            }
            
            self.training_data.append(training_sample)
            
            # Limite la taille des données d'entraînement
            if len(self.training_data) > self.max_training_samples:
                self.training_data = self.training_data[-self.max_training_samples:]
    
    def _generate_explanation(self, event: Event, features: Dict[str, Any], is_anomaly: bool, model_name: str) -> str:
        """Génère une explication pour le résultat de détection"""
        if not is_anomaly:
            return "Comportement normal détecté"
        
        explanations = []
        
        # Analyse des features temporelles
        if features.get('is_after_hours', 0) == 1:
            explanations.append("activité en dehors des heures de bureau")
        
        if features.get('is_weekend', 0) == 1:
            explanations.append("activité pendant le weekend")
        
        # Analyse spécifique par type
        if 'network' in model_name:
            if features.get('is_large_transfer', 0) == 1:
                explanations.append("transfert de données volumineux")
            if features.get('is_foreign_country', 0) == 1:
                explanations.append("connexion depuis un pays étranger")
        
        elif 'auth' in model_name:
            if features.get('auth_failed', 0) == 1:
                explanations.append("échec d'authentification")
            if features.get('is_admin_user', 0) == 1:
                explanations.append("utilisation d'un compte administrateur")
        
        elif 'endpoint' in model_name:
            if features.get('is_powershell', 0) == 1:
                explanations.append("utilisation de PowerShell")
            if features.get('is_temp_file', 0) == 1:
                explanations.append("accès à des fichiers temporaires")
        
        # Sévérité élevée
        if features.get('severity', 1) >= 3:
            explanations.append("sévérité élevée")
        
        if explanations:
            return f"Anomalie détectée: {', '.join(explanations)}"
        else:
            return "Comportement anormal détecté par analyse statistique"
    
    def get_stats(self) -> Dict[str, Any]:
        """Retourne les statistiques du détecteur d'anomalies"""
        stats = self.stats.copy()
        stats['models_loaded'] = len(self.models)
        stats['training_samples'] = len(self.training_data)
        
        if self.stats['anomalies_detected'] > 0:
            stats['accuracy'] = (self.stats['true_positives'] / 
                               (self.stats['true_positives'] + self.stats['false_positives']))
        else:
            stats['accuracy'] = 0.0
        
        return stats
    
    def save_models(self, filepath: str):
        """Sauvegarde les modèles entraînés"""
        try:
            model_data = {
                'models': self.models,
                'scalers': self.scalers,
                'encoders': self.encoders,
                'config': self.model_config,
                'stats': self.stats
            }
            joblib.dump(model_data, filepath)
            logger.info(f"Modèles sauvegardés dans {filepath}")
        except Exception as e:
            logger.error(f"Erreur lors de la sauvegarde des modèles: {e}")
    
    def load_models(self, filepath: str):
        """Charge les modèles sauvegardés"""
        try:
            model_data = joblib.load(filepath)
            self.models = model_data.get('models', {})
            self.scalers = model_data.get('scalers', {})
            self.encoders = model_data.get('encoders', {})
            self.model_config = model_data.get('config', self.model_config)
            self.stats = model_data.get('stats', self.stats)
            logger.info(f"Modèles chargés depuis {filepath}")
        except Exception as e:
            logger.error(f"Erreur lors du chargement des modèles: {e}")
    
    async def feedback(self, event_id: str, is_true_positive: bool):
        """Reçoit un feedback sur la qualité de la détection"""
        if is_true_positive:
            self.stats['true_positives'] += 1
        else:
            self.stats['false_positives'] += 1
        
        logger.info(f"Feedback reçu pour {event_id}: {'TP' if is_true_positive else 'FP'}")
