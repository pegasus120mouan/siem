#!/usr/bin/env python3
"""
SUSDR 360 - Générateur d'Événements de Démonstration
Génère des événements de sécurité réalistes pour tester le système
"""

import random
import time
from datetime import datetime, timedelta
import json

class EventGenerator:
    """Générateur d'événements de sécurité pour démonstration"""
    
    def __init__(self):
        self.event_templates = {
            'windows_security': [
                {
                    'EventID': 4624,
                    'description': 'Connexion réussie',
                    'LogonType': [2, 3, 10],
                    'users': ['john.doe', 'marie.kouame', 'admin', 'service_account']
                },
                {
                    'EventID': 4625,
                    'description': 'Échec de connexion',
                    'LogonType': [2, 3],
                    'users': ['admin', 'administrator', 'root', 'test']
                },
                {
                    'EventID': 4688,
                    'description': 'Création de processus',
                    'processes': ['powershell.exe', 'cmd.exe', 'notepad.exe', 'chrome.exe']
                }
            ],
            'firewall': [
                {
                    'action': 'ALLOW',
                    'description': 'Trafic autorisé',
                    'ports': [80, 443, 22, 3389]
                },
                {
                    'action': 'BLOCK',
                    'description': 'Trafic bloqué',
                    'ports': [445, 135, 139, 1433]
                }
            ],
            'antivirus': [
                {
                    'event_type': 'SCAN_COMPLETE',
                    'description': 'Scan terminé',
                    'files_scanned': [1000, 5000, 10000]
                },
                {
                    'event_type': 'THREAT_DETECTED',
                    'description': 'Menace détectée',
                    'threats': ['Trojan.Win32.Test', 'Adware.Generic', 'PUP.Optional.Test']
                }
            ]
        }
        
        self.ip_ranges = [
            '192.168.1.{}',
            '10.0.0.{}',
            '172.16.1.{}'
        ]
        
        self.computers = [
            'WS-ADMIN-01', 'WS-DEV-02', 'WS-FINANCE-03', 
            'SRV-DC-01', 'SRV-WEB-01', 'SRV-DB-01'
        ]
    
    def generate_ip(self):
        """Génère une adresse IP aléatoire"""
        range_template = random.choice(self.ip_ranges)
        return range_template.format(random.randint(1, 254))
    
    def generate_windows_event(self):
        """Génère un événement Windows Security"""
        template = random.choice(self.event_templates['windows_security'])
        
        event = {
            'EventID': template['EventID'],
            'Computer': random.choice(self.computers),
            'TimeGenerated': datetime.now().isoformat(),
            'Level': random.choice(['Information', 'Warning', 'Error']),
            'Source': 'Microsoft-Windows-Security-Auditing'
        }
        
        if 'LogonType' in template:
            event['LogonType'] = random.choice(template['LogonType'])
            event['TargetUserName'] = random.choice(template['users'])
            event['IpAddress'] = self.generate_ip()
        
        if 'processes' in template:
            event['ProcessName'] = random.choice(template['processes'])
            event['CommandLine'] = f"C:\\Windows\\System32\\{event['ProcessName']}"
        
        return {
            'source': 'windows_security',
            'timestamp': event['TimeGenerated'],
            'raw_data': event
        }
    
    def generate_firewall_event(self):
        """Génère un événement firewall"""
        template = random.choice(self.event_templates['firewall'])
        
        event = {
            'timestamp': datetime.now().isoformat(),
            'action': template['action'],
            'src_ip': self.generate_ip(),
            'dst_ip': self.generate_ip(),
            'src_port': random.randint(1024, 65535),
            'dst_port': random.choice(template['ports']),
            'protocol': random.choice(['TCP', 'UDP']),
            'bytes': random.randint(64, 1500)
        }
        
        return {
            'source': 'firewall',
            'timestamp': event['timestamp'],
            'raw_data': event
        }
    
    def generate_antivirus_event(self):
        """Génère un événement antivirus"""
        template = random.choice(self.event_templates['antivirus'])
        
        event = {
            'timestamp': datetime.now().isoformat(),
            'event_type': template['event_type'],
            'computer': random.choice(self.computers),
            'product': 'SUSDR360-AV'
        }
        
        if 'files_scanned' in template:
            event['files_scanned'] = random.choice(template['files_scanned'])
            event['threats_found'] = random.randint(0, 5)
        
        if 'threats' in template:
            event['threat_name'] = random.choice(template['threats'])
            event['file_path'] = f"C:\\Users\\{random.choice(['john', 'marie', 'admin'])}\\Downloads\\suspicious_file.exe"
            event['action'] = random.choice(['QUARANTINE', 'DELETE', 'BLOCK'])
        
        return {
            'source': 'antivirus',
            'timestamp': event['timestamp'],
            'raw_data': event
        }
    
    def generate_attack_scenario(self, scenario_type='brute_force'):
        """Génère un scénario d'attaque pour tester la corrélation"""
        events = []
        
        if scenario_type == 'brute_force':
            # Simulation d'attaque brute force
            attacker_ip = self.generate_ip()
            target_user = 'admin'
            
            # 5 tentatives échouées
            for i in range(5):
                event = {
                    'source': 'windows_security',
                    'timestamp': (datetime.now() + timedelta(seconds=i*10)).isoformat(),
                    'raw_data': {
                        'EventID': 4625,
                        'Computer': 'SRV-DC-01',
                        'LogonType': 3,
                        'TargetUserName': target_user,
                        'IpAddress': attacker_ip,
                        'FailureReason': 'Bad password',
                        'TimeGenerated': (datetime.now() + timedelta(seconds=i*10)).isoformat()
                    }
                }
                events.append(event)
            
            print(f"🎯 Scénario Brute Force généré: {len(events)} tentatives depuis {attacker_ip}")
        
        elif scenario_type == 'lateral_movement':
            # Simulation de mouvement latéral
            user = 'john.doe'
            
            # Connexion initiale
            events.append({
                'source': 'windows_security',
                'timestamp': datetime.now().isoformat(),
                'raw_data': {
                    'EventID': 4624,
                    'Computer': 'WS-DEV-02',
                    'LogonType': 3,
                    'TargetUserName': user,
                    'IpAddress': '192.168.1.100'
                }
            })
            
            # Connexion SMB suspecte
            events.append({
                'source': 'firewall',
                'timestamp': (datetime.now() + timedelta(minutes=2)).isoformat(),
                'raw_data': {
                    'action': 'ALLOW',
                    'src_ip': '192.168.1.100',
                    'dst_ip': '192.168.1.50',
                    'dst_port': 445,
                    'protocol': 'TCP'
                }
            })
            
            # Exécution de processus suspect
            events.append({
                'source': 'windows_security',
                'timestamp': (datetime.now() + timedelta(minutes=3)).isoformat(),
                'raw_data': {
                    'EventID': 4688,
                    'Computer': 'SRV-WEB-01',
                    'ProcessName': 'psexec.exe',
                    'CommandLine': 'psexec.exe \\\\192.168.1.50 cmd.exe'
                }
            })
            
            print(f"🎯 Scénario Mouvement Latéral généré: {len(events)} événements")
        
        return events
    
    def generate_random_event(self):
        """Génère un événement aléatoire"""
        generators = [
            self.generate_windows_event,
            self.generate_firewall_event,
            self.generate_antivirus_event
        ]
        
        generator = random.choice(generators)
        return generator()

def demo_event_stream():
    """Démonstration d'un flux d'événements en temps réel"""
    generator = EventGenerator()
    
    print("""
    ===============================================================
                    SUSDR 360 - DEMO EVENEMENTS                     
                  Simulation d'evenements de securite            
    ===============================================================
    """)
    
    print("🚀 Démarrage de la simulation d'événements...")
    print("   (Appuyez sur Ctrl+C pour arrêter)\n")
    
    event_count = 0
    
    try:
        while True:
            # Génère un événement aléatoire
            event = generator.generate_random_event()
            event_count += 1
            
            # Affiche l'événement
            timestamp = datetime.now().strftime("%H:%M:%S")
            source = event['source']
            event_type = event['raw_data'].get('EventID', event['raw_data'].get('event_type', 'N/A'))
            
            print(f"[{timestamp}] #{event_count:04d} | {source:15} | {event_type}")
            
            # Sauvegarde dans un fichier pour analyse
            with open('demo_events.json', 'a', encoding='utf-8') as f:
                f.write(json.dumps(event, ensure_ascii=False) + '\n')
            
            # Pause aléatoire entre les événements
            time.sleep(random.uniform(0.5, 3.0))
            
            # Génère parfois des scénarios d'attaque
            if event_count % 20 == 0:
                print(f"\n🎯 Génération d'un scénario d'attaque...")
                scenario = random.choice(['brute_force', 'lateral_movement'])
                attack_events = generator.generate_attack_scenario(scenario)
                
                for attack_event in attack_events:
                    with open('demo_events.json', 'a', encoding='utf-8') as f:
                        f.write(json.dumps(attack_event, ensure_ascii=False) + '\n')
                
                print()
    
    except KeyboardInterrupt:
        print(f"\n⏹️  Simulation arrêtée. {event_count} événements générés.")
        print(f"📁 Événements sauvegardés dans: demo_events.json")

def generate_test_batch():
    """Génère un lot d'événements de test"""
    generator = EventGenerator()
    
    print("📊 Génération d'un lot d'événements de test...")
    
    events = []
    
    # Événements normaux
    for _ in range(10):
        events.append(generator.generate_random_event())
    
    # Scénario d'attaque
    attack_events = generator.generate_attack_scenario('brute_force')
    events.extend(attack_events)
    
    # Sauvegarde
    with open('test_batch.json', 'w', encoding='utf-8') as f:
        for event in events:
            f.write(json.dumps(event, ensure_ascii=False) + '\n')
    
    print(f"✅ {len(events)} événements générés dans test_batch.json")
    return events

if __name__ == "__main__":
    import sys
    
    if len(sys.argv) > 1 and sys.argv[1] == 'batch':
        generate_test_batch()
    else:
        demo_event_stream()
