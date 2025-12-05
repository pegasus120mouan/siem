// SIEM Application JavaScript
class SIEMApp {
    constructor() {
        this.attacks = [];
        this.alerts = [];
        this.currentSection = 'dashboard';
        this.init();
    }

    init() {
        this.updateTime();
        this.generateMockData();
        this.startRealTimeUpdates();
        this.initializeMap();
        
        // Update time every second
        setInterval(() => this.updateTime(), 1000);
        
        // Generate new attacks every 5-15 seconds
        setInterval(() => this.simulateNewAttack(), Math.random() * 10000 + 5000);
    }

    updateTime() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString();
    }

    generateMockData() {
        // Generate initial attacks
        const attackTypes = ['DDoS', 'Malware', 'Phishing', 'SQL Injection', 'XSS', 'Brute Force'];
        const threatLevels = ['Low', 'Medium', 'High', 'Critical'];
        const countries = ['United States', 'China', 'Russia', 'Germany', 'France', 'Japan', 'South Korea', 'Brazil'];
        const sourceIPs = [
            '185.220.101.1', '103.85.24.1', '91.121.1.1', '198.50.200.1', 
            '46.4.1.1', '1.2.3.4', '192.168.1.1', '10.0.0.1'
        ];

        for (let i = 0; i < 20; i++) {
            this.attacks.push({
                id: i + 1,
                sourceIP: sourceIPs[Math.floor(Math.random() * sourceIPs.length)],
                targetIP: '192.168.1.' + (Math.floor(Math.random() * 254) + 1),
                attackType: attackTypes[Math.floor(Math.random() * attackTypes.length)],
                threatLevel: threatLevels[Math.floor(Math.random() * threatLevels.length)],
                sourceCountry: countries[Math.floor(Math.random() * countries.length)],
                targetCountry: 'Local Network',
                timestamp: new Date(Date.now() - Math.random() * 86400000).toISOString()
            });
        }

        // Generate initial alerts
        const alertTypes = ['Intrusion Detected', 'Malware Found', 'Suspicious Activity', 'Policy Violation'];
        for (let i = 0; i < 10; i++) {
            this.alerts.push({
                id: i + 1,
                title: alertTypes[Math.floor(Math.random() * alertTypes.length)],
                description: 'Suspicious activity detected from external source',
                severity: threatLevels[Math.floor(Math.random() * threatLevels.length)],
                timestamp: new Date(Date.now() - Math.random() * 3600000).toISOString(),
                resolved: Math.random() > 0.7
            });
        }

        this.updateDashboard();
    }

    simulateNewAttack() {
        const attackTypes = ['DDoS', 'Malware', 'Phishing', 'SQL Injection', 'XSS', 'Brute Force'];
        const threatLevels = ['Low', 'Medium', 'High', 'Critical'];
        const countries = ['United States', 'China', 'Russia', 'Germany', 'France', 'Japan'];
        const sourceIPs = ['185.220.101.1', '103.85.24.1', '91.121.1.1', '198.50.200.1'];

        const newAttack = {
            id: this.attacks.length + 1,
            sourceIP: sourceIPs[Math.floor(Math.random() * sourceIPs.length)],
            targetIP: '192.168.1.' + (Math.floor(Math.random() * 254) + 1),
            attackType: attackTypes[Math.floor(Math.random() * attackTypes.length)],
            threatLevel: threatLevels[Math.floor(Math.random() * threatLevels.length)],
            sourceCountry: countries[Math.floor(Math.random() * countries.length)],
            targetCountry: 'Local Network',
            timestamp: new Date().toISOString()
        };

        this.attacks.unshift(newAttack);
        if (this.attacks.length > 100) {
            this.attacks = this.attacks.slice(0, 100);
        }

        this.updateDashboard();
        this.animateNewAttack(newAttack);
        
        // Ajouter une nouvelle attaque sur la carte en continu
        if (this.currentSection === 'attack-map') {
            this.addContinuousAttack();
        }
    }

    updateDashboard() {
        const todayAttacks = this.attacks.filter(a => {
            const today = new Date();
            const attackDate = new Date(a.timestamp);
            return attackDate.toDateString() === today.toDateString();
        }).length;

        const activeThreats = this.attacks.filter(a => 
            ['High', 'Critical'].includes(a.threatLevel)
        ).length;

        // Update header stats
        document.getElementById('today-attacks').textContent = todayAttacks;
        document.getElementById('total-attacks').textContent = this.attacks.length.toLocaleString();
        document.getElementById('active-threats').textContent = activeThreats;

        // Update dashboard stats
        document.getElementById('dash-today').textContent = todayAttacks;
        document.getElementById('dash-total').textContent = this.attacks.length.toLocaleString();
        document.getElementById('dash-threats').textContent = activeThreats;

        // Update recent attacks
        this.updateRecentAttacks();
        this.updateRecentAlerts();
        this.updateThreatStats();
    }

    updateRecentAttacks() {
        const container = document.getElementById('recent-attacks');
        const recentAttacks = this.attacks.slice(0, 5);
        
        container.innerHTML = recentAttacks.map(attack => `
            <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full ${this.getThreatColor(attack.threatLevel)}"></span>
                        <span class="text-sm font-medium text-white">${attack.attackType}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        ${attack.sourceIP} → ${attack.targetIP}
                    </div>
                    <div class="text-xs text-gray-500">
                        ${new Date(attack.timestamp).toLocaleString()}
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-2 py-1 text-xs rounded-full text-white ${this.getThreatBgColor(attack.threatLevel)}">
                        ${attack.threatLevel}
                    </span>
                </div>
            </div>
        `).join('');
    }

    updateRecentAlerts() {
        const container = document.getElementById('recent-alerts');
        const recentAlerts = this.alerts.slice(0, 5);
        
        container.innerHTML = recentAlerts.map(alert => `
            <div class="flex items-center justify-between p-3 bg-slate-700/30 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full ${this.getThreatColor(alert.severity)}"></span>
                        <span class="text-sm font-medium text-white">${alert.title}</span>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">${alert.description}</div>
                    <div class="text-xs text-gray-500">
                        ${new Date(alert.timestamp).toLocaleString()}
                    </div>
                </div>
                <div class="text-right">
                    <span class="px-2 py-1 text-xs rounded-full text-white ${this.getThreatBgColor(alert.severity)}">
                        ${alert.severity}
                    </span>
                </div>
            </div>
        `).join('');
    }

    updateThreatStats() {
        const low = this.attacks.filter(a => a.threatLevel === 'Low').length;
        const medium = this.attacks.filter(a => a.threatLevel === 'Medium').length;
        const high = this.attacks.filter(a => a.threatLevel === 'High').length;
        const critical = this.attacks.filter(a => a.threatLevel === 'Critical').length;

        document.getElementById('low-threats').textContent = low;
        document.getElementById('medium-threats').textContent = medium;
        document.getElementById('high-threats').textContent = high;
        document.getElementById('critical-threats').textContent = critical;
    }

    getThreatColor(level) {
        switch(level) {
            case 'Critical': return 'bg-red-600';
            case 'High': return 'bg-red-500';
            case 'Medium': return 'bg-yellow-500';
            case 'Low': return 'bg-green-500';
            default: return 'bg-gray-500';
        }
    }

    getThreatBgColor(level) {
        switch(level) {
            case 'Critical': return 'bg-red-600';
            case 'High': return 'bg-red-500';
            case 'Medium': return 'bg-yellow-500';
            case 'Low': return 'bg-green-500';
            default: return 'bg-gray-500';
        }
    }

    initializeMap() {
        const svg = d3.select("#world-map");
        const width = 1200;
        const height = 600;

        // Clear any existing content
        svg.selectAll("*").remove();

        // Create projection - using a more suitable projection for attack visualization
        const projection = d3.geoEquirectangular()
            .scale(180)
            .translate([width / 2, height / 2]);

        const path = d3.geoPath().projection(projection);

        // Add dark background like in the image
        svg.append("rect")
            .attr("width", width)
            .attr("height", height)
            .attr("fill", "#0a1929");

        // Add subtle grid lines
        const graticule = d3.geoGraticule()
            .step([20, 20]);
        
        svg.append("path")
            .datum(graticule)
            .attr("d", path)
            .attr("fill", "none")
            .attr("stroke", "rgba(100, 150, 200, 0.1)")
            .attr("stroke-width", 0.5);

        // Draw world map using geographic data
        this.drawWorldMap(svg, projection);
        
        // Draw attack visualization
        this.drawAttackVisualization(svg, projection);
    }

    drawWorldMap(svg, projection) {
        console.log("Dessin direct de la carte du monde...");
        
        // Dessiner directement la carte simple qui fonctionne à coup sûr
        this.drawSimpleWorld(svg, d3.geoPath().projection(projection), projection);
    }
    
    scaleSVGPath(pathData, originalWidth, originalHeight, newWidth, newHeight) {
        // Simple scaling transformation for SVG path data
        const scaleX = newWidth / originalWidth;
        const scaleY = newHeight / originalHeight;
        
        // Parse and scale the path data
        return pathData.replace(/([ML])\s*([\d.-]+)\s*([\d.-]+)/g, (match, command, x, y) => {
            const scaledX = parseFloat(x) * scaleX;
            const scaledY = parseFloat(y) * scaleY;
            return `${command} ${scaledX} ${scaledY}`;
        });
    }

    drawSimpleWorld(svg, path, projection) {
        console.log("Dessin de la carte simple (fallback)");
        
        // Fallback world map with basic continent shapes
        const continents = [
            // North America
            { name: "North America", coords: [[-140, 60], [-60, 60], [-60, 20], [-140, 20], [-140, 60]] },
            // Europe
            { name: "Europe", coords: [[-10, 70], [40, 70], [40, 35], [-10, 35], [-10, 70]] },
            // Asia
            { name: "Asia", coords: [[40, 70], [180, 70], [180, 10], [40, 10], [40, 70]] },
            // Africa
            { name: "Africa", coords: [[-20, 35], [50, 35], [50, -35], [-20, -35], [-20, 35]] },
            // South America
            { name: "South America", coords: [[-80, 10], [-35, 10], [-35, -55], [-80, -55], [-80, 10]] },
            // Australia
            { name: "Australia", coords: [[110, -10], [155, -10], [155, -45], [110, -45], [110, -10]] }
        ];
        
        continents.forEach(continent => {
            const geoPath = d3.geoPath().projection(projection);
            svg.append("path")
                .datum({type: "Polygon", coordinates: [continent.coords]})
                .attr("d", geoPath)
                .attr("fill", "#2d5a87")  // Même couleur que la vraie carte
                .attr("stroke", "#1a3d5c")
                .attr("stroke-width", 0.8)
                .style("cursor", "pointer")
                .on("mouseover", function(event) {
                    d3.select(this).attr("fill", "#3d6a97");
                })
                .on("mouseout", function(event) {
                    d3.select(this).attr("fill", "#2d5a87");
                });
        });
        
        console.log("Carte simple dessinée avec", continents.length, "continents");
    }

    drawAttackVisualization(svg, projection) {
        // Attack data with more realistic global distribution
        const attackData = [
            // Major attack routes like in the image
            { source: [116.4074, 39.9042], target: [-74.006, 40.7128], level: 'Critical', type: 'DDoS' }, // Beijing to NYC
            { source: [37.6173, 55.7558], target: [2.3522, 48.8566], level: 'High', type: 'Malware' }, // Moscow to Paris
            { source: [139.6917, 35.6895], target: [-122.4194, 37.7749], level: 'High', type: 'Phishing' }, // Tokyo to SF
            { source: [126.9780, 37.5665], target: [-87.6298, 41.8781], level: 'Medium', type: 'SQL Injection' }, // Seoul to Chicago
            { source: [13.4050, 52.5200], target: [-118.2437, 34.0522], level: 'Critical', type: 'Brute Force' }, // Berlin to LA
            { source: [55.2708, 25.2048], target: [151.2093, -33.8688], level: 'Medium', type: 'XSS' }, // Dubai to Sydney
            { source: [-46.6333, -23.5505], target: [0.1278, 51.5074], level: 'Low', type: 'Reconnaissance' }, // São Paulo to London
            { source: [77.1025, 28.7041], target: [72.8777, 19.0760], level: 'High', type: 'Malware' }, // Delhi to Mumbai
            { source: [31.2357, 30.0444], target: [3.3792, 6.5244], level: 'Medium', type: 'Phishing' }, // Cairo to Lagos
            { source: [18.4241, -33.9249], target: [28.0473, -26.2041], level: 'Low', type: 'DDoS' }, // Cape Town to Johannesburg
        ];

        // Create curved paths for attack lines (like in the image)
        attackData.forEach((attack, i) => {
            const sourceCoords = projection(attack.source);
            const targetCoords = projection(attack.target);

            if (sourceCoords && targetCoords) {
                // Calculate control point for curved line
                const midX = (sourceCoords[0] + targetCoords[0]) / 2;
                const midY = (sourceCoords[1] + targetCoords[1]) / 2 - 50; // Curve upward
                
                // Create curved path
                const pathData = `M ${sourceCoords[0]},${sourceCoords[1]} Q ${midX},${midY} ${targetCoords[0]},${targetCoords[1]}`;
                
                // Draw dotted attack line (like in the image)
                const attackLine = svg.append("path")
                    .attr("d", pathData)
                    .attr("stroke", this.getAttackColor(attack.level))
                    .attr("stroke-width", 2)
                    .attr("stroke-dasharray", "5,3")
                    .attr("fill", "none")
                    .attr("opacity", 0.8)
                    .style("filter", `drop-shadow(0 0 3px ${this.getAttackColor(attack.level)})`);

                // Animate the line drawing
                const totalLength = attackLine.node().getTotalLength();
                attackLine
                    .attr("stroke-dasharray", totalLength + " " + totalLength)
                    .attr("stroke-dashoffset", totalLength)
                    .transition()
                    .duration(2000)
                    .delay(i * 300)
                    .attr("stroke-dashoffset", 0)
                    .attr("stroke-dasharray", "5,3");

                // Source marker (attacker - red)
                svg.append("circle")
                    .attr("cx", sourceCoords[0])
                    .attr("cy", sourceCoords[1])
                    .attr("r", 0)
                    .attr("fill", "#ff4444")
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 2)
                    .style("filter", "drop-shadow(0 0 8px #ff4444)")
                    .transition()
                    .duration(500)
                    .delay(i * 300 + 1000)
                    .attr("r", 5);

                // Target marker (target - cyan/green)
                svg.append("circle")
                    .attr("cx", targetCoords[0])
                    .attr("cy", targetCoords[1])
                    .attr("r", 0)
                    .attr("fill", "#44ffaa")
                    .attr("stroke", "#ffffff")
                    .attr("stroke-width", 2)
                    .style("filter", "drop-shadow(0 0 8px #44ffaa)")
                    .transition()
                    .duration(500)
                    .delay(i * 300 + 1500)
                    .attr("r", 4);

                // Add pulsing effect for recent attacks
                if (i < 3) {
                    svg.append("circle")
                        .attr("cx", sourceCoords[0])
                        .attr("cy", sourceCoords[1])
                        .attr("r", 5)
                        .attr("fill", "none")
                        .attr("stroke", "#ff4444")
                        .attr("stroke-width", 2)
                        .attr("opacity", 1)
                        .transition()
                        .duration(2000)
                        .delay(i * 300 + 2000)
                        .attr("r", 20)
                        .attr("opacity", 0)
                        .on("end", function() {
                            d3.select(this).remove();
                        });
                }
            }
        });

        // Add some random attack points for more activity
        this.addRandomAttackPoints(svg, projection);
    }

    addRandomAttackPoints(svg, projection) {
        // Add some additional attack points for visual richness
        const additionalPoints = [
            [100.5018, 13.7563], // Bangkok
            [121.4737, 31.2304], // Shanghai  
            [12.4964, 41.9028], // Rome
            [-3.7038, 40.4168], // Madrid
            [144.9631, -37.8136], // Melbourne
            [103.8198, 1.3521], // Singapore
            [-99.1332, 19.4326], // Mexico City
            [-58.3816, -34.6037], // Buenos Aires
        ];

        additionalPoints.forEach((point, i) => {
            const coords = projection(point);
            if (coords) {
                // Small pulsing dots
                svg.append("circle")
                    .attr("cx", coords[0])
                    .attr("cy", coords[1])
                    .attr("r", 2)
                    .attr("fill", "#ffaa44")
                    .attr("opacity", 0.7)
                    .style("filter", "drop-shadow(0 0 4px #ffaa44)")
                    .transition()
                    .duration(1500)
                    .delay(i * 200 + 3000)
                    .attr("r", 8)
                    .attr("opacity", 0)
                    .on("end", function() {
                        d3.select(this).remove();
                    });
            }
        });
    }

    getAttackColor(level) {
        switch(level) {
            case 'Critical': return '#ff3333';
            case 'High': return '#ff6633';
            case 'Medium': return '#ffaa33';
            case 'Low': return '#33ff99';
            default: return '#6699ff';
        }
    }

    getThreatLineColor(level) {
        switch(level) {
            case 'Critical': return '#dc2626';
            case 'High': return '#ef4444';
            case 'Medium': return '#f59e0b';
            case 'Low': return '#10b981';
            default: return '#6b7280';
        }
    }

    getThreatLineWidth(level) {
        switch(level) {
            case 'Critical': return 4;
            case 'High': return 3;
            case 'Medium': return 2;
            case 'Low': return 1;
            default: return 1;
        }
    }

    animateNewAttack(attack) {
        // Add visual notification for new attack
        if (this.currentSection === 'dashboard') {
            this.showNotification(`New ${attack.threatLevel} threat: ${attack.attackType}`, attack.threatLevel);
        }
    }

    showNotification(message, level) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${this.getThreatBgColor(level)}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            notification.style.transition = 'all 0.3s ease-out';
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }

    startRealTimeUpdates() {
        // Simulate real-time data updates
        setInterval(() => {
            this.updateDashboard();
        }, 5000);
        
        // Attaques continues sur la carte avec intervalle variable
        const scheduleNextAttack = () => {
            setTimeout(() => {
                if (this.currentSection === 'attack-map') {
                    this.addContinuousAttack();
                }
                scheduleNextAttack(); // Programmer la prochaine attaque
            }, Math.random() * 4000 + 2000); // Entre 2 et 6 secondes
        };
        scheduleNextAttack();
        
        // Nettoyage périodique des anciennes attaques
        setInterval(() => {
            if (this.currentSection === 'attack-map') {
                this.cleanupOldAttacks();
            }
        }, 15000);
    }
    
    addContinuousAttack() {
        console.log("Ajout d'une nouvelle attaque...");
        const svg = d3.select("#world-map");
        if (svg.empty()) {
            console.log("Erreur: SVG #world-map non trouvé!");
            return;
        }
        
        const projection = d3.geoEquirectangular()
            .scale(180)
            .translate([600, 300]);

        // Utiliser les vraies IPs de la liste avec géolocalisation
        const realAttackerIPs = [
            "47.128.30.12", "197.58.178.121", "156.204.176.43", "156.194.105.254",
            "20.221.56.85", "168.166.246.68", "172.245.61.84", "109.123.230.130",
            "167.71.211.209", "172.105.69.26", "167.71.188.107", "161.97.129.82",
            "20.234.104.179", "217.154.144.108", "172.93.222.191", "91.232.238.112",
            "164.92.208.244", "209.97.177.200", "4.205.225.47", "159.203.98.247",
            "152.53.106.19", "45.84.107.172", "175.200.104.40", "38.135.25.97",
            "95.216.144.195", "159.65.64.72", "165.232.94.68", "41.46.234.254",
            "78.153.140.195", "36.255.18.127", "120.85.118.191", "107.189.12.38"
        ];

        // Cibles communes (serveurs, entreprises, etc.)
        const commonTargets = [
            { coords: [-74.006, 40.7128], name: "New York Financial District" },
            { coords: [-122.4194, 37.7749], name: "San Francisco Tech Hub" },
            { coords: [0.1278, 51.5074], name: "London Banking Center" },
            { coords: [2.3522, 48.8566], name: "Paris Corporate" },
            { coords: [13.4050, 52.5200], name: "Berlin Data Center" },
            { coords: [139.6917, 35.6895], name: "Tokyo Financial" },
            { coords: [-87.6298, 41.8781], name: "Chicago Exchange" },
            { coords: [121.4737, 31.2304], name: "Shanghai Business" }
        ];

        // Sélectionner une IP attaquante aléatoire
        const attackerIP = realAttackerIPs[Math.floor(Math.random() * realAttackerIPs.length)];
        const target = commonTargets[Math.floor(Math.random() * commonTargets.length)];
        
        // Base de données IP intégrée
        const ipLocations = {
            "47.128.30.12": { country: "United States", city: "Ashburn", coords: [-77.4875, 39.0438] },
            "197.58.178.121": { country: "Egypt", city: "Cairo", coords: [31.2357, 30.0444] },
            "156.204.176.43": { country: "Russia", city: "Moscow", coords: [37.6173, 55.7558] },
            "156.194.105.254": { country: "Russia", city: "Saint Petersburg", coords: [30.3351, 59.9311] },
            "20.221.56.85": { country: "United States", city: "Seattle", coords: [-122.3301, 47.6038] },
            "168.166.246.68": { country: "Singapore", city: "Singapore", coords: [103.8198, 1.3521] },
            "172.245.61.84": { country: "United States", city: "New York", coords: [-74.0060, 40.7128] },
            "109.123.230.130": { country: "Netherlands", city: "Amsterdam", coords: [4.9041, 52.3676] },
            "167.71.211.209": { country: "Germany", city: "Frankfurt", coords: [8.6821, 50.1109] },
            "172.105.69.26": { country: "United Kingdom", city: "London", coords: [-0.1276, 51.5074] },
            "167.71.188.107": { country: "Canada", city: "Toronto", coords: [-79.3832, 43.6532] },
            "161.97.129.82": { country: "Finland", city: "Helsinki", coords: [24.9384, 60.1699] },
            "20.234.104.179": { country: "United States", city: "Chicago", coords: [-87.6298, 41.8781] },
            "217.154.144.108": { country: "France", city: "Paris", coords: [2.3522, 48.8566] },
            "172.93.222.191": { country: "United States", city: "Dallas", coords: [-96.7970, 32.7767] },
            "91.232.238.112": { country: "Ukraine", city: "Kyiv", coords: [30.5234, 50.4501] },
            "164.92.208.244": { country: "India", city: "Bangalore", coords: [77.5946, 12.9716] },
            "209.97.177.200": { country: "Canada", city: "Montreal", coords: [-73.5673, 45.5017] },
            "4.205.225.47": { country: "United States", city: "Los Angeles", coords: [-118.2437, 34.0522] },
            "159.203.98.247": { country: "Canada", city: "Vancouver", coords: [-123.1207, 49.2827] },
            "152.53.106.19": { country: "Brazil", city: "São Paulo", coords: [-46.6333, -23.5505] },
            "45.84.107.172": { country: "Romania", city: "Bucharest", coords: [26.1025, 44.4268] },
            "175.200.104.40": { country: "South Korea", city: "Seoul", coords: [126.9780, 37.5665] },
            "38.135.25.97": { country: "United States", city: "Miami", coords: [-80.1918, 25.7617] },
            "95.216.144.195": { country: "Turkey", city: "Istanbul", coords: [28.9784, 41.0082] },
            "159.65.64.72": { country: "Germany", city: "Berlin", coords: [13.4050, 52.5200] },
            "165.232.94.68": { country: "India", city: "Mumbai", coords: [72.8777, 19.0760] },
            "41.46.234.254": { country: "South Africa", city: "Cape Town", coords: [18.4241, -33.9249] },
            "78.153.140.195": { country: "Poland", city: "Warsaw", coords: [21.0122, 52.2297] },
            "36.255.18.127": { country: "China", city: "Beijing", coords: [116.4074, 39.9042] },
            "120.85.118.191": { country: "China", city: "Shanghai", coords: [121.4737, 31.2304] },
            "107.189.12.38": { country: "United States", city: "Phoenix", coords: [-112.0740, 33.4484] }
        };
        
        // Obtenir la géolocalisation de l'IP attaquante
        const attackerLocation = ipLocations[attackerIP] || { 
            country: "Unknown", 
            city: "Unknown", 
            coords: [0, 0] 
        };
        
        const attackData = {
            sourceIP: attackerIP,
            sourceLocation: attackerLocation,
            source: attackerLocation.coords,
            target: target.coords,
            targetName: target.name,
            level: this.getRandomThreatLevel()
        };

        const sourceCoords = projection(attackData.source);
        const targetCoords = projection(attackData.target);

        console.log(`Attaque: ${attackerIP} (${attackerLocation.city}, ${attackerLocation.country}) → ${attackData.targetName}`);
        console.log("Coordonnées source:", sourceCoords, "Coordonnées cible:", targetCoords);

        if (sourceCoords && targetCoords) {
            // ID unique pour cette attaque
            const attackId = 'attack-' + Date.now() + '-' + Math.random();
            
            // Calculer le point de contrôle pour la courbe
            const midX = (sourceCoords[0] + targetCoords[0]) / 2;
            const midY = (sourceCoords[1] + targetCoords[1]) / 2 - Math.random() * 100 - 30;
            
            // Créer le chemin courbe
            const pathData = `M ${sourceCoords[0]},${sourceCoords[1]} Q ${midX},${midY} ${targetCoords[0]},${targetCoords[1]}`;
            
            // Dessiner la ligne d'attaque
            const attackLine = svg.append("path")
                .attr("id", attackId)
                .attr("d", pathData)
                .attr("stroke", this.getAttackColor(attackData.level))
                .attr("stroke-width", 2)
                .attr("stroke-dasharray", "5,3")
                .attr("fill", "none")
                .attr("opacity", 0.9)
                .style("filter", `drop-shadow(0 0 3px ${this.getAttackColor(attackData.level)})`);

            // Animation de la ligne
            const totalLength = attackLine.node().getTotalLength();
            attackLine
                .attr("stroke-dasharray", totalLength + " " + totalLength)
                .attr("stroke-dashoffset", totalLength)
                .transition()
                .duration(2500)
                .attr("stroke-dashoffset", 0)
                .attr("stroke-dasharray", "5,3")
                .on("end", () => {
                    // Faire disparaître la ligne après l'animation
                    attackLine.transition()
                        .duration(1000)
                        .attr("opacity", 0)
                        .remove();
                });

            // Point source (attaquant) avec tooltip
            const sourcePoint = svg.append("circle")
                .attr("id", attackId + "-source")
                .attr("cx", sourceCoords[0])
                .attr("cy", sourceCoords[1])
                .attr("r", 0)
                .attr("fill", "#ff4444")
                .attr("stroke", "#ffffff")
                .attr("stroke-width", 2)
                .style("filter", "drop-shadow(0 0 8px #ff4444)")
                .style("cursor", "pointer");

            // Ajouter les événements de tooltip pour la source
            sourcePoint
                .on("mouseover", (event) => {
                    this.showTooltip(event, {
                        title: "Attaquant",
                        ip: attackData.sourceIP,
                        location: `${attackData.sourceLocation.city}, ${attackData.sourceLocation.country}`,
                        threat: attackData.level,
                        type: "Source"
                    });
                })
                .on("mouseout", () => {
                    this.hideTooltip();
                });

            sourcePoint
                .transition()
                .duration(300)
                .attr("r", 6)
                .transition()
                .delay(3000)
                .duration(500)
                .attr("r", 0)
                .remove();

            // Point cible
            svg.append("circle")
                .attr("id", attackId + "-target")
                .attr("cx", targetCoords[0])
                .attr("cy", targetCoords[1])
                .attr("r", 0)
                .attr("fill", "#44ffaa")
                .attr("stroke", "#ffffff")
                .attr("stroke-width", 2)
                .style("filter", "drop-shadow(0 0 8px #44ffaa)")
                .transition()
                .delay(2500)
                .duration(300)
                .attr("r", 5)
                .transition()
                .delay(1000)
                .duration(500)
                .attr("r", 0)
                .remove();

            // Effet de pulsation sur la source
            svg.append("circle")
                .attr("cx", sourceCoords[0])
                .attr("cy", sourceCoords[1])
                .attr("r", 6)
                .attr("fill", "none")
                .attr("stroke", "#ff4444")
                .attr("stroke-width", 2)
                .attr("opacity", 1)
                .transition()
                .duration(1500)
                .attr("r", 25)
                .attr("opacity", 0)
                .remove();
        }
    }
    
    getRandomThreatLevel() {
        const levels = ['Low', 'Medium', 'High', 'Critical'];
        const weights = [0.3, 0.4, 0.2, 0.1]; // Probabilités : Low 30%, Medium 40%, High 20%, Critical 10%
        
        const random = Math.random();
        let cumulative = 0;
        
        for (let i = 0; i < levels.length; i++) {
            cumulative += weights[i];
            if (random <= cumulative) {
                return levels[i];
            }
        }
        
        return 'Medium'; // Fallback
    }

    showTooltip(event, data) {
        // Supprimer le tooltip existant s'il y en a un
        this.hideTooltip();
        
        const tooltip = d3.select("body").append("div")
            .attr("class", "tooltip")
            .style("position", "absolute")
            .style("background", "rgba(15, 23, 42, 0.95)")
            .style("border", "1px solid rgba(59, 130, 246, 0.3)")
            .style("border-radius", "6px")
            .style("padding", "12px")
            .style("font-size", "12px")
            .style("color", "white")
            .style("pointer-events", "none")
            .style("z-index", "1000")
            .style("box-shadow", "0 4px 12px rgba(0, 0, 0, 0.3)")
            .style("opacity", 0);

        const threatColor = this.getAttackColor(data.threat);
        
        tooltip.html(`
            <div style="font-weight: bold; color: ${threatColor}; margin-bottom: 8px;">
                ${data.title}
            </div>
            <div style="margin-bottom: 4px;">
                <strong>IP:</strong> ${data.ip}
            </div>
            <div style="margin-bottom: 4px;">
                <strong>Location:</strong> ${data.location}
            </div>
            <div style="margin-bottom: 4px;">
                <strong>Threat Level:</strong> 
                <span style="color: ${threatColor};">${data.threat}</span>
            </div>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 6px;">
                ${data.type === 'Source' ? '🔴 Attacker' : '🎯 Target'}
            </div>
        `);

        tooltip
            .style("left", (event.pageX + 10) + "px")
            .style("top", (event.pageY - 10) + "px")
            .transition()
            .duration(200)
            .style("opacity", 1);
    }

    hideTooltip() {
        d3.selectAll(".tooltip").remove();
    }

    cleanupOldAttacks() {
        // Nettoyer les éléments d'attaque qui pourraient rester
        const svg = d3.select("#world-map");
        svg.selectAll("[id^='attack-']").each(function() {
            const element = d3.select(this);
            const id = element.attr("id");
            if (id && id.includes("attack-")) {
                const timestamp = parseInt(id.split("-")[1]);
                if (Date.now() - timestamp > 10000) { // Supprimer après 10 secondes
                    element.remove();
                }
            }
        });
    }
}

// Initialize the application
const app = new SIEMApp();

// Navigation functions
function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.remove('hidden');
    
    // Update navigation buttons
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.className = btn.className.replace('bg-blue-600 text-white', 'text-gray-300 hover:bg-slate-800 hover:text-white');
    });
    
    // Highlight active button - find the button that was clicked
    document.querySelectorAll('.nav-btn').forEach(btn => {
        if (btn.onclick && btn.onclick.toString().includes(sectionId)) {
            btn.className = btn.className.replace('text-gray-300 hover:bg-slate-800 hover:text-white', 'bg-blue-600 text-white');
        }
    });
    
    // Special handling for attack map
    if (sectionId === 'attack-map') {
        setTimeout(() => {
            app.initializeMap();
        }, 100);
    }
    
    app.currentSection = sectionId;
}

function refreshData() {
    app.generateMockData();
    app.showNotification('Data refreshed successfully', 'Low');
}
