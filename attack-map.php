<?php
$pageTitle = "Attack Map - Carte des Attaques";
require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">Carte des Attaques en Temps Réel</h1>
            <p class="text-gray-400">Visualisation géographique des menaces détectées</p>
        </div>
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <span class="text-red-400 text-sm">Temps réel</span>
        </div>
    </div>
    
    <!-- Carte principale agrandie -->
    <div class="bg-slate-900 rounded-xl overflow-hidden mb-6">
        <!-- En-tête de la carte -->
        <div class="bg-slate-800 px-6 py-4 border-b border-slate-700">
            <div class="flex justify-between items-center">
                <h2 class="text-white text-lg font-semibold">Attack Map</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-white text-sm">Attacker</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <span class="text-white text-sm">Target</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Carte du monde agrandie -->
        <div id="worldAttackMap" class="h-[700px] bg-slate-900 relative overflow-hidden">
            <!-- La carte sera injectée ici -->
        </div>
        
        <!-- Statistiques en bas -->
        <div class="bg-slate-800 px-6 py-4 border-t border-slate-700">
            <div class="grid grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-400" id="lowThreat">8</div>
                    <div class="text-sm text-gray-400">Low Threat</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-400" id="mediumThreat">15</div>
                    <div class="text-sm text-gray-400">Medium Threat</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-400" id="highThreat">12</div>
                    <div class="text-sm text-gray-400">High Threat</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-400" id="criticalThreat">5</div>
                    <div class="text-sm text-gray-400">Critical Threat</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Attaques Détectées</p>
                    <p class="text-white text-2xl font-bold" id="totalAttacks">1,247</p>
                </div>
                <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-crosshairs text-red-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Pays Sources</p>
                    <p class="text-white text-2xl font-bold" id="sourceCountries">47</p>
                </div>
                <div class="w-12 h-12 bg-orange-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-globe text-orange-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">IPs Bloquées</p>
                    <p class="text-white text-2xl font-bold" id="blockedIPs">892</p>
                </div>
                <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-ban text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Sévérité Moyenne</p>
                    <p class="text-white text-2xl font-bold text-yellow-400">Élevée</p>
                </div>
                <div class="w-12 h-12 bg-yellow-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Flux d'attaques en temps réel -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card rounded-xl p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Flux d'Attaques en Temps Réel</h3>
            <div class="space-y-3 max-h-64 overflow-y-auto" id="attackFeed">
                <!-- Les attaques seront ajoutées dynamiquement -->
            </div>
        </div>
        
        <div class="card rounded-xl p-6">
            <h3 class="text-white text-lg font-semibold mb-4">Top Pays Attaquants</h3>
            <div class="space-y-3" id="topAttackers">
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/cn.png" alt="Chine" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">Chine</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                        <span class="text-red-400 font-semibold">324 attaques</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/ru.png" alt="Russie" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">Russie</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-orange-500 rounded-full animate-pulse"></div>
                        <span class="text-orange-400 font-semibold">287 attaques</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/us.png" alt="États-Unis" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">États-Unis</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                        <span class="text-yellow-400 font-semibold">156 attaques</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/br.png" alt="Brésil" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">Brésil</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-green-400 font-semibold">98 attaques</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/in.png" alt="Inde" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">Inde</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                        <span class="text-blue-400 font-semibold">67 attaques</span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                    <div class="flex items-center">
                        <img src="https://flagcdn.com/w40/kr.png" alt="Corée du Sud" class="w-6 h-4 rounded-sm mr-3 object-cover">
                        <span class="text-white font-medium">Corée du Sud</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-purple-500 rounded-full animate-pulse"></div>
                        <span class="text-purple-400 font-semibold">45 attaques</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initAttackMap();
    startAttackFeed();
    
    // Mettre à jour les statistiques toutes les 10 secondes
    setInterval(updateThreatStats, 10000);
});

async function initAttackMap() {
    const container = document.getElementById('worldAttackMap');
    const width = container.offsetWidth;
    const height = container.offsetHeight;
    
    try {
        // Charger le SVG de la carte du monde
        const response = await fetch('world-map.svg');
        const svgText = await response.text();
        
        // Injecter le SVG dans le conteneur
        container.innerHTML = svgText;
        
        // Récupérer l'élément SVG injecté
        const svg = d3.select(container).select('svg');
        
        // Ajuster la taille du SVG
        svg.attr('width', width)
           .attr('height', height)
           .attr('viewBox', '0 0 1009.6727 665.96301')
           .attr('preserveAspectRatio', 'xMidYMid meet');
        
        // Styliser les pays
        svg.selectAll('path')
           .attr('fill', '#1e293b')
           .attr('stroke', '#334155')
           .attr('stroke-width', 0.5)
           .attr('opacity', 0.8);
        
        // Créer une projection basée sur les dimensions du SVG
        const projection = d3.geoIdentity()
            .fitSize([width, height], {
                type: "FeatureCollection",
                features: [{
                    type: "Feature",
                    geometry: {
                        type: "Polygon",
                        coordinates: [[[0, 0], [1009.6727, 0], [1009.6727, 665.96301], [0, 665.96301], [0, 0]]]
                    }
                }]
            });
        
        // Ajouter des points d'attaque avec coordonnées SVG
        addAttackPointsSVG(svg, width, height);
        
        // Ajouter des lignes d'attaque
        addAttackLinesSVG(svg, width, height);
        
    } catch (error) {
        console.error('Erreur lors du chargement de la carte SVG:', error);
        // Fallback vers une carte simplifiée
        const svg = d3.select(container)
            .append('svg')
            .attr('width', width)
            .attr('height', height);
        createFallbackMap(svg, width, height);
    }
    
    // Mettre à jour les statistiques
    updateThreatStats();
}

function createFallbackMap(svg, width, height) {
    // Carte simplifiée en cas d'erreur
    const worldPath = "M158.4 0C70.9 0 0 70.9 0 158.4s70.9 158.4 158.4 158.4 158.4-70.9 158.4-158.4S245.9 0 158.4 0z";
    
    svg.append('rect')
        .attr('width', width)
        .attr('height', height)
        .attr('fill', '#0f172a');
    
    // Dessiner une forme de monde simplifiée
    const continents = [
        // Amérique du Nord
        {x: width * 0.2, y: height * 0.3, width: width * 0.15, height: height * 0.25},
        // Amérique du Sud  
        {x: width * 0.25, y: height * 0.55, width: width * 0.08, height: height * 0.2},
        // Europe
        {x: width * 0.48, y: height * 0.25, width: width * 0.08, height: height * 0.15},
        // Afrique
        {x: width * 0.48, y: height * 0.4, width: width * 0.08, height: height * 0.25},
        // Asie
        {x: width * 0.6, y: height * 0.2, width: width * 0.2, height: height * 0.3},
        // Océanie
        {x: width * 0.75, y: height * 0.6, width: width * 0.08, height: height * 0.1}
    ];
    
    svg.selectAll('.continent')
        .data(continents)
        .enter().append('rect')
        .attr('class', 'continent')
        .attr('x', d => d.x)
        .attr('y', d => d.y)
        .attr('width', d => d.width)
        .attr('height', d => d.height)
        .attr('fill', '#1e293b')
        .attr('stroke', '#334155')
        .attr('stroke-width', 1)
        .attr('rx', 5);
    
    // Ajouter des points d'attaque sur la carte simplifiée
    addSimpleAttackPoints(svg, width, height);
}

function addAttackPointsSVG(svg, width, height) {
    // Coordonnées approximatives basées sur les dimensions du SVG world-map
    const attackLocations = [
        {name: 'Chine', x: width * 0.75, y: height * 0.35, type: 'attacker'},
        {name: 'Russie', x: width * 0.65, y: height * 0.25, type: 'attacker'},
        {name: 'États-Unis', x: width * 0.2, y: height * 0.4, type: 'target'},
        {name: 'Brésil', x: width * 0.3, y: height * 0.7, type: 'attacker'},
        {name: 'Allemagne', x: width * 0.52, y: height * 0.32, type: 'target'},
        {name: 'Japon', x: width * 0.85, y: height * 0.4, type: 'target'},
        {name: 'Inde', x: width * 0.7, y: height * 0.45, type: 'attacker'},
        {name: 'France', x: width * 0.48, y: height * 0.35, type: 'target'},
        {name: 'Australie', x: width * 0.82, y: height * 0.75, type: 'target'},
        {name: 'Canada', x: width * 0.18, y: height * 0.25, type: 'target'}
    ];
    
    attackLocations.forEach((location, i) => {
        const color = location.type === 'attacker' ? '#ef4444' : '#10b981';
        
        // Créer un groupe pour chaque point d'attaque
        const attackGroup = svg.append('g')
            .attr('class', 'attack-point')
            .attr('transform', `translate(${location.x}, ${location.y})`);
        
        // Cercle principal
        attackGroup.append('circle')
            .attr('r', 0)
            .attr('fill', color)
            .attr('opacity', 0.8)
            .transition()
            .delay(i * 200)
            .duration(1000)
            .attr('r', 4)
            .on('end', function() {
                // Animation de pulsation continue
                d3.select(this)
                    .transition()
                    .duration(2000)
                    .attr('r', 8)
                    .attr('opacity', 0.3)
                    .transition()
                    .duration(2000)
                    .attr('r', 4)
                    .attr('opacity', 0.8)
                    .on('end', function repeat() {
                        d3.select(this)
                            .transition()
                            .duration(2000)
                            .attr('r', 8)
                            .attr('opacity', 0.3)
                            .transition()
                            .duration(2000)
                            .attr('r', 4)
                            .attr('opacity', 0.8)
                            .on('end', repeat);
                    });
            });
        
        // Cercle d'onde de choc
        attackGroup.append('circle')
            .attr('r', 4)
            .attr('fill', 'none')
            .attr('stroke', color)
            .attr('stroke-width', 2)
            .attr('opacity', 0)
            .transition()
            .delay(i * 200 + 1000)
            .duration(0)
            .attr('opacity', 0.6)
            .transition()
            .duration(1500)
            .attr('r', 20)
            .attr('opacity', 0)
            .on('end', function shockwave() {
                d3.select(this)
                    .attr('r', 4)
                    .attr('opacity', 0.6)
                    .transition()
                    .duration(1500)
                    .attr('r', 20)
                    .attr('opacity', 0)
                    .on('end', function() {
                        setTimeout(shockwave, Math.random() * 3000 + 2000);
                    });
            });
    });
}

function addSimpleAttackPoints(svg, width, height) {
    const points = [
        {x: width * 0.25, y: height * 0.35, type: 'attacker'},
        {x: width * 0.65, y: height * 0.3, type: 'attacker'},
        {x: width * 0.22, y: height * 0.4, type: 'target'},
        {x: width * 0.5, y: height * 0.3, type: 'target'},
        {x: width * 0.7, y: height * 0.25, type: 'target'},
        {x: width * 0.27, y: height * 0.6, type: 'attacker'}
    ];
    
    points.forEach((point, i) => {
        const color = point.type === 'attacker' ? '#ef4444' : '#10b981';
        
        svg.append('circle')
            .attr('cx', point.x)
            .attr('cy', point.y)
            .attr('r', 0)
            .attr('fill', color)
            .attr('opacity', 0.8)
            .transition()
            .delay(i * 200)
            .duration(1000)
            .attr('r', 4)
            .transition()
            .duration(2000)
            .attr('r', 8)
            .attr('opacity', 0.3)
            .on('end', function() {
                d3.select(this)
                    .transition()
                    .duration(1000)
                    .attr('r', 4)
                    .attr('opacity', 0.8);
            });
    });
}

function addAttackLinesSVG(svg, width, height) {
    const attacks = [
        {source: {x: width * 0.75, y: height * 0.35}, target: {x: width * 0.2, y: height * 0.4}}, // Chine -> USA
        {source: {x: width * 0.65, y: height * 0.25}, target: {x: width * 0.52, y: height * 0.32}}, // Russie -> Allemagne
        {source: {x: width * 0.7, y: height * 0.45}, target: {x: width * 0.85, y: height * 0.4}}, // Inde -> Japon
        {source: {x: width * 0.3, y: height * 0.7}, target: {x: width * 0.2, y: height * 0.4}}, // Brésil -> USA
        {source: {x: width * 0.75, y: height * 0.35}, target: {x: width * 0.82, y: height * 0.75}} // Chine -> Australie
    ];
    
    attacks.forEach((attack, i) => {
        // Créer une ligne d'attaque avec animation
        const line = svg.append('line')
            .attr('class', 'attack-line')
            .attr('x1', attack.source.x)
            .attr('y1', attack.source.y)
            .attr('x2', attack.source.x)
            .attr('y2', attack.source.y)
            .attr('stroke', '#ef4444')
            .attr('stroke-width', 2)
            .attr('opacity', 0)
            .attr('stroke-dasharray', '8,4');
        
        // Animation d'apparition de la ligne
        line.transition()
            .delay(i * 1500 + 3000)
            .duration(0)
            .attr('opacity', 0.7)
            .transition()
            .duration(2000)
            .attr('x2', attack.target.x)
            .attr('y2', attack.target.y)
            .on('end', function() {
                // Animation continue du dash
                d3.select(this)
                    .transition()
                    .duration(3000)
                    .attr('opacity', 0.3)
                    .transition()
                    .duration(1000)
                    .attr('opacity', 0)
                    .on('end', function() {
                        // Répéter l'animation après un délai aléatoire
                        setTimeout(() => {
                            d3.select(this)
                                .attr('x2', attack.source.x)
                                .attr('y2', attack.source.y)
                                .attr('opacity', 0.7)
                                .transition()
                                .duration(2000)
                                .attr('x2', attack.target.x)
                                .attr('y2', attack.target.y)
                                .on('end', arguments.callee);
                        }, Math.random() * 5000 + 3000);
                    });
            });
        
        // Ajouter un point mobile sur la ligne
        setTimeout(() => {
            const attackDot = svg.append('circle')
                .attr('class', 'attack-dot')
                .attr('cx', attack.source.x)
                .attr('cy', attack.source.y)
                .attr('r', 3)
                .attr('fill', '#fbbf24')
                .attr('opacity', 0);
            
            function animateAttackDot() {
                attackDot
                    .attr('cx', attack.source.x)
                    .attr('cy', attack.source.y)
                    .attr('opacity', 0.9)
                    .transition()
                    .duration(2000)
                    .attr('cx', attack.target.x)
                    .attr('cy', attack.target.y)
                    .transition()
                    .duration(500)
                    .attr('opacity', 0)
                    .on('end', function() {
                        setTimeout(animateAttackDot, Math.random() * 8000 + 5000);
                    });
            }
            
            animateAttackDot();
        }, i * 1500 + 5000);
    });
}

function updateThreatStats() {
    // Mettre à jour les statistiques avec animation
    const stats = [
        {id: 'lowThreat', value: Math.floor(Math.random() * 15) + 5},
        {id: 'mediumThreat', value: Math.floor(Math.random() * 20) + 10},
        {id: 'highThreat', value: Math.floor(Math.random() * 15) + 8},
        {id: 'criticalThreat', value: Math.floor(Math.random() * 10) + 3}
    ];
    
    stats.forEach(stat => {
        const element = document.getElementById(stat.id);
        if (element) {
            // Animation du compteur
            const currentValue = parseInt(element.textContent);
            const targetValue = stat.value;
            const duration = 1000;
            const steps = 20;
            const stepValue = (targetValue - currentValue) / steps;
            
            let step = 0;
            const interval = setInterval(() => {
                step++;
                const newValue = Math.round(currentValue + (stepValue * step));
                element.textContent = newValue;
                
                if (step >= steps) {
                    clearInterval(interval);
                    element.textContent = targetValue;
                }
            }, duration / steps);
        }
    });
}

function startAttackFeed() {
    const feedContainer = document.getElementById('attackFeed');
    const attackTypes = ['Malware', 'DDoS', 'Brute Force', 'Phishing', 'SQL Injection', 'XSS'];
    const countries = [
        {code: 'CN', name: 'Chine'},
        {code: 'RU', name: 'Russie'},
        {code: 'US', name: 'États-Unis'},
        {code: 'BR', name: 'Brésil'},
        {code: 'IN', name: 'Inde'},
        {code: 'KR', name: 'Corée du Sud'},
        {code: 'DE', name: 'Allemagne'},
        {code: 'FR', name: 'France'}
    ];
    
    function addAttack() {
        const sourceCountry = countries[Math.floor(Math.random() * countries.length)];
        const attack = {
            type: attackTypes[Math.floor(Math.random() * attackTypes.length)],
            source: sourceCountry,
            target: '192.168.1.' + Math.floor(Math.random() * 255),
            severity: ['low', 'medium', 'high'][Math.floor(Math.random() * 3)],
            time: new Date().toLocaleTimeString()
        };
        
        const severityColors = {
            low: 'text-green-400',
            medium: 'text-yellow-400',
            high: 'text-red-400'
        };
        
        const attackElement = document.createElement('div');
        attackElement.className = 'flex items-center justify-between py-3 px-3 bg-gray-700 bg-opacity-30 rounded-lg animate-pulse hover:bg-opacity-50 transition-colors';
        attackElement.innerHTML = `
            <div class="flex items-center">
                <div class="w-2 h-2 ${severityColors[attack.severity].replace('text-', 'bg-')} rounded-full mr-3 animate-pulse"></div>
                <div class="flex items-center mr-3">
                    <img src="https://flagcdn.com/w20/${attack.source.code.toLowerCase()}.png" 
                         alt="${attack.source.name}" 
                         class="w-4 h-3 rounded-sm mr-2 object-cover"
                         onerror="this.style.display='none'">
                    <span class="text-gray-300 text-xs">${attack.source.name}</span>
                </div>
                <div>
                    <p class="text-white text-sm font-medium">${attack.type}</p>
                    <p class="text-gray-400 text-xs">→ ${attack.target}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="${severityColors[attack.severity]} text-xs capitalize font-semibold">${attack.severity}</p>
                <p class="text-gray-500 text-xs">${attack.time}</p>
            </div>
        `;
        
        feedContainer.insertBefore(attackElement, feedContainer.firstChild);
        
        // Garder seulement les 8 dernières attaques
        while (feedContainer.children.length > 8) {
            feedContainer.removeChild(feedContainer.lastChild);
        }
        
        // Supprimer l'animation après 2 secondes
        setTimeout(() => {
            attackElement.classList.remove('animate-pulse');
        }, 2000);
    }
    
    // Fonction pour mettre à jour dynamiquement le top des pays
    function updateTopCountries() {
        const topCountriesData = [
            {code: 'cn', name: 'Chine', attacks: Math.floor(Math.random() * 50) + 300, color: 'red'},
            {code: 'ru', name: 'Russie', attacks: Math.floor(Math.random() * 40) + 250, color: 'orange'},
            {code: 'us', name: 'États-Unis', attacks: Math.floor(Math.random() * 30) + 130, color: 'yellow'},
            {code: 'br', name: 'Brésil', attacks: Math.floor(Math.random() * 20) + 80, color: 'green'},
            {code: 'in', name: 'Inde', attacks: Math.floor(Math.random() * 15) + 50, color: 'blue'},
            {code: 'kr', name: 'Corée du Sud', attacks: Math.floor(Math.random() * 10) + 35, color: 'purple'}
        ];
        
        const container = document.getElementById('topAttackers');
        container.innerHTML = topCountriesData.map(country => `
            <div class="flex items-center justify-between p-3 bg-gray-700 bg-opacity-50 rounded-lg hover:bg-opacity-70 transition-colors">
                <div class="flex items-center">
                    <img src="https://flagcdn.com/w40/${country.code}.png" 
                         alt="${country.name}" 
                         class="w-6 h-4 rounded-sm mr-3 object-cover"
                         onerror="this.style.display='none'">
                    <span class="text-white font-medium">${country.name}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-${country.color}-500 rounded-full animate-pulse"></div>
                    <span class="text-${country.color}-400 font-semibold">${country.attacks} attaques</span>
                </div>
            </div>
        `).join('');
    }
    
    // Ajouter une attaque toutes les 3-8 secondes
    function scheduleNextAttack() {
        const delay = Math.random() * 5000 + 3000; // 3-8 secondes
        setTimeout(() => {
            addAttack();
            scheduleNextAttack();
        }, delay);
    }
    
    // Ajouter quelques attaques initiales
    for (let i = 0; i < 5; i++) {
        setTimeout(() => addAttack(), i * 500);
    }
    
    scheduleNextAttack();
    
    // Mettre à jour le top des pays toutes les 15 secondes
    setInterval(updateTopCountries, 15000);
    
    // Mise à jour initiale du top des pays
    setTimeout(updateTopCountries, 2000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
