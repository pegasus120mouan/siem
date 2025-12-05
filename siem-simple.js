// SIEM Application - Version Simple et Fonctionnelle
class SIEMApp {
    constructor() {
        this.attacks = [];
        this.currentSection = 'dashboard';
        this.attackInterval = null;
        this.osintHistory = [];
        this.watchlist = [];
        this.bulkResults = [];
        // Configuration APIs
        this.abuseIPDBKey = null;
        this.virusTotalKey = null;
        this.currentOSINTTab = 'single';
        this.init();
    }

    init() {
        console.log("SIEM App initialisée");
        this.generateMockData();
        this.updateDashboard();
        this.generateInitialAttacks();
        this.startAttacks();
    }

    generateInitialAttacks() {
        // Générer quelques attaques initiales pour le tableau
        const initialAttacks = [
            {
                time: "29/11 20:55:32",
                attackerGeo: { country: "United States", flag: "🇺🇸" },
                attackerIP: "152.42.187.52",
                targetIP: "192.168.0.12",
                attackType: "Information Disclosure",
                threatLevel: "High"
            },
            {
                time: "29/11 19:23:38",
                attackerGeo: { country: "United States", flag: "🇺🇸" },
                attackerIP: "167.71.211.209",
                targetIP: "192.168.0.12",
                attackType: "Information Disclosure",
                threatLevel: "High"
            },
            {
                time: "29/11 19:17:32",
                attackerGeo: { country: "United States", flag: "🇺🇸" },
                attackerIP: "172.245.61.84",
                targetIP: "192.168.0.12",
                attackType: "Web Vulnerability",
                threatLevel: "Medium"
            },
            {
                time: "29/11 15:09:36",
                attackerGeo: { country: "United States", flag: "🇺🇸" },
                attackerIP: "167.71.188.107",
                targetIP: "192.168.0.15",
                attackType: "Information Disclosure",
                threatLevel: "High"
            },
            {
                time: "29/11 14:51:19",
                attackerGeo: { country: "United States", flag: "🇺🇸" },
                attackerIP: "167.71.188.107",
                targetIP: "192.168.0.15",
                attackType: "Information Disclosure",
                threatLevel: "High"
            },
            {
                time: "29/11 14:43:01",
                attackerGeo: { country: "United Kingdom", flag: "🇬🇧" },
                attackerIP: "217.154.144.108",
                targetIP: "192.168.0.47",
                attackType: "WebShell Upload",
                threatLevel: "High"
            }
        ];

        initialAttacks.forEach(attack => {
            this.addAttackToTable(attack);
        });
    }

    generateMockData() {
        // Générer des données d'attaque simulées
        const attackTypes = ['DDoS', 'Malware', 'Phishing', 'SQL Injection'];
        const threatLevels = ['Low', 'Medium', 'High', 'Critical'];
        
        for (let i = 0; i < 50; i++) {
            this.attacks.push({
                id: i + 1,
                sourceIP: this.getRandomIP(),
                targetIP: '192.168.1.' + (Math.floor(Math.random() * 254) + 1),
                attackType: attackTypes[Math.floor(Math.random() * attackTypes.length)],
                threatLevel: threatLevels[Math.floor(Math.random() * threatLevels.length)],
                timestamp: new Date(Date.now() - Math.random() * 86400000).toISOString()
            });
        }
    }

    getRandomIP() {
        const ips = [
            "47.128.30.12", "197.58.178.121", "156.204.176.43", "156.194.105.254",
            "20.221.56.85", "168.166.246.68", "172.245.61.84", "109.123.230.130",
            "167.71.211.209", "172.105.69.26", "167.71.188.107", "161.97.129.82"
        ];
        return ips[Math.floor(Math.random() * ips.length)];
    }

    updateDashboard() {
        const todayAttacks = this.attacks.length;
        const activeThreats = Math.floor(todayAttacks * 0.3);
        
        // Mettre à jour les compteurs
        const elements = {
            'today-attacks': todayAttacks,
            'total-attacks': todayAttacks + 1200,
            'active-threats': activeThreats,
            'dash-today': todayAttacks,
            'dash-total': todayAttacks + 1200
        };

        for (const [id, value] of Object.entries(elements)) {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        }

        // Mettre à jour l'heure
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = new Date().toLocaleTimeString();
        }
    }

    initializeMap() {
        console.log("Initialisation de la carte...");
        
        // Nettoyer la carte existante
        d3.select("#world-map").selectAll("*").remove();
        
        // Créer le SVG
        const svg = d3.select("#world-map")
            .append("svg")
            .attr("width", 1200)
            .attr("height", 600)
            .style("background", "#0f172a");

        // Projection géographique
        const projection = d3.geoEquirectangular()
            .scale(180)
            .translate([600, 300]);

        // Dessiner la carte du monde simple
        this.drawSimpleWorld(svg, projection);
        
        console.log("Carte initialisée avec succès");
    }

    drawSimpleWorld(svg, projection) {
        console.log("Chargement de la carte SVG détaillée...");
        
        // Charger directement le contenu SVG
        d3.xml("world-map.svg").then(data => {
            console.log("SVG chargé avec succès");
            
            const svgElement = data.documentElement;
            const paths = svgElement.querySelectorAll('path');
            
            // Dimensions originales du SVG
            const originalWidth = 1009.6727;
            const originalHeight = 665.96301;
            
            // Dimensions cibles
            const targetWidth = 1200;
            const targetHeight = 600;
            
            // Calcul de l'échelle
            const scaleX = targetWidth / originalWidth;
            const scaleY = targetHeight / originalHeight;
            const scale = Math.min(scaleX, scaleY);
            
            const offsetX = (targetWidth - originalWidth * scale) / 2;
            const offsetY = (targetHeight - originalHeight * scale) / 2;
            
            // Groupe pour la carte avec transformation
            const worldGroup = svg.append("g")
                .attr("transform", `translate(${offsetX}, ${offsetY}) scale(${scale})`);
            
            // Ajouter chaque pays
            paths.forEach(pathElement => {
                const pathData = pathElement.getAttribute('d');
                const countryName = pathElement.getAttribute('title') || pathElement.getAttribute('id');
                
                if (pathData) {
                    worldGroup.append("path")
                        .attr("d", pathData)
                        .attr("fill", "#2d5a87")
                        .attr("stroke", "#1a3d5c")
                        .attr("stroke-width", 0.3 / scale)
                        .attr("data-country", countryName || "Unknown")
                        .style("cursor", "pointer")
                        .on("mouseover", function() {
                            d3.select(this).attr("fill", "#3d6a97");
                        })
                        .on("mouseout", function() {
                            d3.select(this).attr("fill", "#2d5a87");
                        });
                }
            });
            
            console.log("Carte détaillée dessinée avec", paths.length, "pays");
            
        }).catch(error => {
            console.log("Erreur chargement SVG, utilisation carte simple:", error);
            this.drawFallbackWorld(svg, projection);
        });
    }

    drawFallbackWorld(svg, projection) {
        console.log("Dessin de la carte de secours...");
        
        const geoPath = d3.geoPath().projection(projection);
        
        // Continents simplifiés
        const continents = [
            { name: "North America", coords: [[-140, 60], [-60, 60], [-60, 20], [-140, 20], [-140, 60]] },
            { name: "Europe", coords: [[-10, 70], [40, 70], [40, 35], [-10, 35], [-10, 70]] },
            { name: "Asia", coords: [[40, 70], [180, 70], [180, 10], [40, 10], [40, 70]] },
            { name: "Africa", coords: [[-20, 35], [50, 35], [50, -35], [-20, -35], [-20, 35]] },
            { name: "South America", coords: [[-80, 10], [-35, 10], [-35, -55], [-80, -55], [-80, 10]] },
            { name: "Australia", coords: [[110, -10], [155, -10], [155, -45], [110, -45], [110, -10]] }
        ];
        
        continents.forEach(continent => {
            svg.append("path")
                .datum({type: "Polygon", coordinates: [continent.coords]})
                .attr("d", geoPath)
                .attr("fill", "#2d5a87")
                .attr("stroke", "#1a3d5c")
                .attr("stroke-width", 1)
                .style("cursor", "pointer")
                .on("mouseover", function() {
                    d3.select(this).attr("fill", "#3d6a97");
                })
                .on("mouseout", function() {
                    d3.select(this).attr("fill", "#2d5a87");
                });
        });
        
        console.log("Carte de secours dessinée avec", continents.length, "continents");
    }

    startAttacks() {
        console.log("Démarrage des attaques...");
        
        // Arrêter les attaques existantes
        if (this.attackInterval) {
            clearInterval(this.attackInterval);
        }
        
        // Démarrer les nouvelles attaques
        this.attackInterval = setInterval(() => {
            if (this.currentSection === 'attack-map') {
                this.addAttack();
            }
        }, 3000);
    }

    addAttack() {
        console.log("Ajout d'une attaque...");
        
        const svg = d3.select("#world-map svg");
        if (svg.empty()) {
            console.log("SVG non trouvé");
            return;
        }

        const projection = d3.geoEquirectangular()
            .scale(180)
            .translate([600, 300]);

        // IPs réelles avec géolocalisation
        const realAttackerIPs = [
            "47.128.30.12", "197.58.178.121", "156.204.176.43", "156.194.105.254",
            "20.221.56.85", "168.166.246.68", "172.245.61.84", "109.123.230.130",
            "167.71.211.209", "172.105.69.26", "167.71.188.107", "161.97.129.82",
            "20.234.104.179", "217.154.144.108", "172.93.222.191", "91.232.238.112"
        ];

        const ipLocations = {
            "47.128.30.12": { country: "United States", city: "Ashburn", coords: [-77.4875, 39.0438], flag: "🇺🇸" },
            "197.58.178.121": { country: "Egypt", city: "Cairo", coords: [31.2357, 30.0444], flag: "🇪🇬" },
            "156.204.176.43": { country: "Russia", city: "Moscow", coords: [37.6173, 55.7558], flag: "🇷🇺" },
            "156.194.105.254": { country: "Russia", city: "Saint Petersburg", coords: [30.3351, 59.9311], flag: "🇷🇺" },
            "20.221.56.85": { country: "United States", city: "Seattle", coords: [-122.3301, 47.6038], flag: "🇺🇸" },
            "168.166.246.68": { country: "Singapore", city: "Singapore", coords: [103.8198, 1.3521], flag: "🇸🇬" },
            "172.245.61.84": { country: "United States", city: "New York", coords: [-74.0060, 40.7128], flag: "🇺🇸" },
            "109.123.230.130": { country: "Netherlands", city: "Amsterdam", coords: [4.9041, 52.3676], flag: "🇳🇱" },
            "167.71.211.209": { country: "Germany", city: "Frankfurt", coords: [8.6821, 50.1109], flag: "🇩🇪" },
            "172.105.69.26": { country: "United Kingdom", city: "London", coords: [-0.1276, 51.5074], flag: "🇬🇧" },
            "167.71.188.107": { country: "Canada", city: "Toronto", coords: [-79.3832, 43.6532], flag: "🇨🇦" },
            "161.97.129.82": { country: "Finland", city: "Helsinki", coords: [24.9384, 60.1699], flag: "🇫🇮" },
            "20.234.104.179": { country: "United States", city: "Chicago", coords: [-87.6298, 41.8781], flag: "🇺🇸" },
            "217.154.144.108": { country: "France", city: "Paris", coords: [2.3522, 48.8566], flag: "🇫🇷" },
            "172.93.222.191": { country: "United States", city: "Dallas", coords: [-96.7970, 32.7767], flag: "🇺🇸" },
            "91.232.238.112": { country: "Ukraine", city: "Kyiv", coords: [30.5234, 50.4501], flag: "🇺🇦" }
        };

        const attackTypes = ['DDoS', 'Malware', 'Phishing', 'SQL Injection', 'XSS', 'Brute Force', 'WebShell Upload', 'Information Disclosure', 'Web Vulnerability'];
        const threatLevels = ['Low', 'Medium', 'High', 'Critical'];
        const targetIPs = ['192.168.0.12', '192.168.0.15', '192.168.0.47', '10.0.0.25', '172.16.1.10'];

        // Sélectionner une attaque aléatoire
        const attackerIP = realAttackerIPs[Math.floor(Math.random() * realAttackerIPs.length)];
        const attackerLocation = ipLocations[attackerIP];
        const attackType = attackTypes[Math.floor(Math.random() * attackTypes.length)];
        const threatLevel = threatLevels[Math.floor(Math.random() * threatLevels.length)];
        const targetIP = targetIPs[Math.floor(Math.random() * targetIPs.length)];

        // Coordonnées pour la carte
        const sourceCoords = projection(attackerLocation.coords);
        const targetCoords = projection([-95.7129, 37.0902]); // Centre des États-Unis comme cible

        if (!sourceCoords || !targetCoords) return;

        // Ajouter l'attaque au tableau
        this.addAttackToTable({
            time: new Date().toLocaleString('fr-FR', { 
                day: '2-digit', 
                month: '2-digit', 
                hour: '2-digit', 
                minute: '2-digit',
                second: '2-digit'
            }),
            attackerGeo: attackerLocation,
            attackerIP: attackerIP,
            targetIP: targetIP,
            attackType: attackType,
            threatLevel: threatLevel
        });

        const attackId = 'attack-' + Date.now();

        // Ligne d'attaque
        const midX = (sourceCoords[0] + targetCoords[0]) / 2;
        const midY = (sourceCoords[1] + targetCoords[1]) / 2 - 50;
        const pathData = `M ${sourceCoords[0]},${sourceCoords[1]} Q ${midX},${midY} ${targetCoords[0]},${targetCoords[1]}`;

        const attackLine = svg.append("path")
            .attr("id", attackId)
            .attr("d", pathData)
            .attr("stroke", "#ff4444")
            .attr("stroke-width", 2)
            .attr("stroke-dasharray", "5,3")
            .attr("fill", "none")
            .attr("opacity", 0.8);

        // Animation
        const totalLength = attackLine.node().getTotalLength();
        attackLine
            .attr("stroke-dasharray", totalLength + " " + totalLength)
            .attr("stroke-dashoffset", totalLength)
            .transition()
            .duration(2000)
            .attr("stroke-dashoffset", 0)
            .on("end", () => {
                attackLine.transition().duration(1000).attr("opacity", 0).remove();
            });

        // Points source et cible
        svg.append("circle")
            .attr("cx", sourceCoords[0])
            .attr("cy", sourceCoords[1])
            .attr("r", 4)
            .attr("fill", "#ff4444")
            .attr("stroke", "#ffffff")
            .attr("stroke-width", 1)
            .transition()
            .delay(2000)
            .duration(1000)
            .attr("r", 0)
            .remove();

        svg.append("circle")
            .attr("cx", targetCoords[0])
            .attr("cy", targetCoords[1])
            .attr("r", 4)
            .attr("fill", "#44ffaa")
            .attr("stroke", "#ffffff")
            .attr("stroke-width", 1)
            .transition()
            .delay(2000)
            .duration(1000)
            .attr("r", 0)
            .remove();

        console.log(`Attaque ajoutée: ${attackerIP} → ${targetIP}`);
    }

    addAttackToTable(attackData) {
        const tableBody = document.getElementById('attacks-table-body');
        if (!tableBody) return;

        // Couleurs selon le niveau de menace
        const threatColors = {
            'Low': 'text-green-400',
            'Medium': 'text-yellow-400', 
            'High': 'text-orange-400',
            'Critical': 'text-red-400'
        };

        // Couleurs selon le type d'attaque
        const attackTypeColors = {
            'DDoS': 'text-red-400',
            'Malware': 'text-purple-400',
            'Phishing': 'text-yellow-400',
            'SQL Injection': 'text-orange-400',
            'XSS': 'text-pink-400',
            'Brute Force': 'text-red-500',
            'WebShell Upload': 'text-red-600',
            'Information Disclosure': 'text-blue-400',
            'Web Vulnerability': 'text-orange-500'
        };

        // Créer une nouvelle ligne
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-700 transition-colors';
        
        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                ${attackData.time}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                <div class="flex items-center space-x-2">
                    <span class="text-lg">${attackData.attackerGeo.flag}</span>
                    <span>${attackData.attackerGeo.country}</span>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-blue-400">
                ${attackData.attackerIP}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-cyan-400">
                ${attackData.targetIP}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm ${attackTypeColors[attackData.attackType] || 'text-gray-300'}">
                ${attackData.attackType}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm ${threatColors[attackData.threatLevel]}">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-opacity-20 ${
                    attackData.threatLevel === 'Critical' ? 'bg-red-500' :
                    attackData.threatLevel === 'High' ? 'bg-orange-500' :
                    attackData.threatLevel === 'Medium' ? 'bg-yellow-500' : 'bg-green-500'
                }">
                    ${attackData.threatLevel}
                </span>
            </td>
        `;

        // Ajouter en haut du tableau
        tableBody.insertBefore(row, tableBody.firstChild);

        // Limiter à 20 lignes maximum
        while (tableBody.children.length > 20) {
            tableBody.removeChild(tableBody.lastChild);
        }
    }

    // Fonctions OSINT
    async analyzeTarget(target, type = 'auto') {
        console.log(`Analyse OSINT de: ${target} (type: ${type})`);
        
        // Afficher le loader
        document.getElementById('osint-loading').classList.remove('hidden');
        document.getElementById('osint-results').classList.add('hidden');
        
        try {
            const detectedType = type === 'auto' ? this.detectTargetType(target) : type;
            const analysisResult = await this.performOSINTAnalysis(target, detectedType);
            
            this.displayOSINTResults(analysisResult);
            this.addToOSINTHistory(target, detectedType, analysisResult);
            
            // Cacher le loader et afficher les résultats
            document.getElementById('osint-loading').classList.add('hidden');
            document.getElementById('osint-results').classList.remove('hidden');
        } catch (error) {
            console.error('Erreur lors de l\'analyse OSINT:', error);
            document.getElementById('osint-loading').classList.add('hidden');
            alert('Erreur lors de l\'analyse. Vérifiez la console pour plus de détails.');
        }
    }

    detectTargetType(target) {
        // Détection automatique du type
        const ipRegex = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$/;
        const urlRegex = /^https?:\/\//;
        const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/;
        
        if (ipRegex.test(target)) return 'ip';
        if (urlRegex.test(target)) return 'url';
        if (domainRegex.test(target)) return 'domain';
        return 'unknown';
    }

    async performOSINTAnalysis(target, type) {
        // Simulation d'analyse OSINT avec données réalistes
        const baseResult = {
            target: target,
            type: type,
            timestamp: new Date().toISOString(),
            status: Math.random() > 0.2 ? 'success' : 'partial'
        };

        switch (type) {
            case 'ip':
                return await this.analyzeIP(target, baseResult);
            case 'domain':
                return this.analyzeDomain(target, baseResult);
            case 'url':
                return this.analyzeURL(target, baseResult);
            default:
                return { ...baseResult, error: 'Type non supporté' };
        }
    }

    async analyzeIP(ip, baseResult) {
        let result = { ...baseResult };
        
        // Essayer VirusTotal en premier si configuré
        if (this.hasVirusTotal) {
            try {
                const vtData = await this.queryVirusTotal(ip, 'ip');
                result = this.processVirusTotalData(ip, vtData, result);
                result.vtAnalyzed = true;
            } catch (error) {
                console.log("Erreur API VirusTotal:", error);
            }
        }
        
        // Essayer AbuseIPDB si configuré
        if (this.hasAbuseIPDB) {
            try {
                const abuseData = await this.queryAbuseIPDB(ip);
                result = this.processAbuseIPDBData(ip, abuseData, result);
                result.abuseAnalyzed = true;
            } catch (error) {
                console.log("Erreur API AbuseIPDB:", error);
            }
        }

        // Si aucune API n'a fonctionné, utiliser les données locales
        if (!result.vtAnalyzed && !result.abuseAnalyzed) {
            result = this.analyzeIPLocal(ip, baseResult);
        }

        return result;
    }

    async queryAbuseIPDB(ip) {
        console.log('🔍 Requête AbuseIPDB pour:', ip);
        
        try {
            const response = await fetch(`secure-api-proxy.php?service=abuseipdb&target=${encodeURIComponent(ip)}`, {
                method: 'GET',
                headers: {
                    'X-Service': 'abuseipdb'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }

            const result = await response.json();
            console.log('✅ Données AbuseIPDB reçues:', result);
            return result.data;
        } catch (error) {
            console.error('❌ Erreur AbuseIPDB:', error);
            throw error;
        }
    }

    processAbuseIPDBData(ip, abuseData, baseResult) {
        const data = abuseData.data;
        
        // Calculer le niveau de risque basé sur le score d'abus
        let riskLevel = 'Faible';
        let riskColor = 'Faible';
        if (data.abuseConfidencePercentage >= 75) {
            riskLevel = 'Critique';
            riskColor = 'Critique';
        } else if (data.abuseConfidencePercentage >= 50) {
            riskLevel = 'Élevé';
            riskColor = 'Élevé';
        } else if (data.abuseConfidencePercentage >= 25) {
            riskLevel = 'Moyen';
            riskColor = 'Moyen';
        }

        // Formater la date de dernière activité
        let lastActivity = "Jamais signalée";
        if (data.lastReportedAt) {
            const lastDate = new Date(data.lastReportedAt);
            lastActivity = lastDate.toLocaleDateString('fr-FR') + ' à ' + lastDate.toLocaleTimeString('fr-FR');
        }

        return {
            ...baseResult,
            generalInfo: {
                "Adresse IP": ip,
                "Type IP": data.version === 4 ? "IPv4" : "IPv6",
                "Statut": data.isPublic ? "Public" : "Privé", 
                "Dernière activité": lastActivity,
                "Trouvée dans la base": data.abuseConfidencePercentage > 0 ? "✅ Oui" : "❌ Non"
            },
            geolocation: {
                "Pays": `${this.getCountryFlag(data.countryName)} ${data.countryName || 'Inconnu'}`,
                "Ville": data.city || 'Inconnue',
                "Code pays": data.countryCode || 'N/A',
                "ISP": data.isp || 'ISP Inconnu',
                "Organisation": data.domain || 'Organisation Inconnue',
                "Type d'usage": data.usageType || 'Usage Inconnu',
                "ASN": `AS${data.asn || '0000'}`,
                "Timezone": data.timezone || 'UTC+0'
            },
            security: {
                "Niveau de risque": riskLevel,
                "Score d'abus": `${data.abuseConfidencePercentage}% ${data.abuseConfidencePercentage === 100 ? '🔴' : data.abuseConfidencePercentage > 50 ? '🟠' : data.abuseConfidencePercentage > 0 ? '🟡' : '🟢'}`,
                "Signalements totaux": `${data.totalReports || 0} fois`,
                "Utilisateurs distincts": `${data.numDistinctUsers || 0} utilisateurs`,
                "Whitelisted": data.isWhitelisted ? "✅ Oui" : "❌ Non",
                "Proxy/VPN détecté": data.usageType && (data.usageType.toLowerCase().includes('hosting') || data.usageType.toLowerCase().includes('datacenter')) ? "🔍 Possible" : "❌ Non"
            },
            rawData: data
        };
    }

    analyzeIPLocal(ip, baseResult) {
        // Base de données IP pour simulation (fallback)
        const ipDatabase = {
            "47.128.30.12": { country: "United States", city: "Ashburn", isp: "Amazon Web Services", org: "AWS EC2", asn: "AS14618" },
            "197.58.178.121": { country: "Egypt", city: "Cairo", isp: "Telecom Egypt", org: "TE Data", asn: "AS8452" },
            "156.204.176.43": { country: "Russia", city: "Moscow", isp: "Yandex LLC", org: "Yandex Cloud", asn: "AS13238" },
            "217.154.144.108": { country: "France", city: "Paris", isp: "OVH SAS", org: "OVH Hosting", asn: "AS16276" }
        };

        const ipInfo = ipDatabase[ip] || {
            country: "Unknown",
            city: "Unknown", 
            isp: "Unknown ISP",
            org: "Unknown Organization",
            asn: "AS0000"
        };

        const riskLevel = this.calculateIPRisk(ip);
        
        return {
            ...baseResult,
            generalInfo: {
                "Adresse IP": ip,
                "Type": "IPv4",
                "Statut": Math.random() > 0.3 ? "Actif" : "Inactif",
                "Dernière activité": "Il y a 2 minutes"
            },
            geolocation: {
                "Pays": `${this.getCountryFlag(ipInfo.country)} ${ipInfo.country}`,
                "Ville": ipInfo.city,
                "ISP": ipInfo.isp,
                "Organisation": ipInfo.org,
                "ASN": ipInfo.asn,
                "Timezone": "UTC+1"
            },
            security: {
                "Niveau de risque": riskLevel,
                "Blacklisté": Math.random() > 0.8 ? "Oui" : "Non",
                "Malware détecté": Math.random() > 0.9 ? "Oui" : "Non",
                "Proxy/VPN": Math.random() > 0.7 ? "Détecté" : "Non détecté",
                "Réputation": Math.random() > 0.6 ? "Bonne" : "Suspecte"
            }
        };
    }

    analyzeDomain(domain, baseResult) {
        const riskLevel = this.calculateDomainRisk(domain);
        
        return {
            ...baseResult,
            generalInfo: {
                "Domaine": domain,
                "Statut": "Actif",
                "Âge du domaine": "2 ans 3 mois",
                "Dernière mise à jour": "Il y a 1 jour"
            },
            security: {
                "Niveau de risque": riskLevel,
                "Phishing détecté": Math.random() > 0.9 ? "Oui" : "Non",
                "Malware hébergé": Math.random() > 0.85 ? "Oui" : "Non",
                "Certificat SSL": Math.random() > 0.2 ? "Valide" : "Invalide",
                "Réputation": Math.random() > 0.7 ? "Bonne" : "Suspecte"
            }
        };
    }

    analyzeURL(url, baseResult) {
        const riskLevel = this.calculateURLRisk(url);
        
        return {
            ...baseResult,
            generalInfo: {
                "URL": url,
                "Statut HTTP": Math.random() > 0.1 ? "200 OK" : "404 Not Found",
                "Taille": "2.3 MB",
                "Dernière analyse": "Il y a 5 minutes"
            },
            security: {
                "Niveau de risque": riskLevel,
                "Contenu malveillant": Math.random() > 0.9 ? "Détecté" : "Non détecté",
                "Phishing": Math.random() > 0.95 ? "Détecté" : "Non détecté",
                "Redirections suspectes": Math.random() > 0.8 ? "Oui" : "Non",
                "Réputation": Math.random() > 0.6 ? "Bonne" : "Suspecte"
            }
        };
    }

    calculateIPRisk(ip) {
        const risks = ['Faible', 'Moyen', 'Élevé', 'Critique'];
        const weights = [0.4, 0.3, 0.2, 0.1];
        return this.getWeightedRandom(risks, weights);
    }

    calculateDomainRisk(domain) {
        const risks = ['Faible', 'Moyen', 'Élevé', 'Critique'];
        const weights = [0.5, 0.3, 0.15, 0.05];
        return this.getWeightedRandom(risks, weights);
    }

    calculateURLRisk(url) {
        const risks = ['Faible', 'Moyen', 'Élevé', 'Critique'];
        const weights = [0.3, 0.4, 0.2, 0.1];
        return this.getWeightedRandom(risks, weights);
    }

    getWeightedRandom(items, weights) {
        const random = Math.random();
        let cumulative = 0;
        for (let i = 0; i < items.length; i++) {
            cumulative += weights[i];
            if (random <= cumulative) return items[i];
        }
        return items[0];
    }

    getCountryFlag(country) {
        const flags = {
            "United States": "🇺🇸",
            "Egypt": "🇪🇬", 
            "Russia": "🇷🇺",
            "France": "🇫🇷",
            "Germany": "🇩🇪",
            "United Kingdom": "🇬🇧",
            "Canada": "🇨🇦",
            "Netherlands": "🇳🇱"
        };
        return flags[country] || "🌍";
    }

    displayOSINTResults(result) {
        // Afficher un badge pour indiquer la source des données
        let sourceIndicators = [];
        
        if (result.hasVirusTotalData) {
            sourceIndicators.push('<div class="mb-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-900 text-purple-300 border border-purple-500 mr-2"><span class="w-2 h-2 bg-purple-400 rounded-full mr-2"></span>🦠 VirusTotal en temps réel</div>');
        }
        
        if (result.rawData) {
            sourceIndicators.push('<div class="mb-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-900 text-green-300 border border-green-500 mr-2"><span class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>🛡️ AbuseIPDB en temps réel</div>');
        }
        
        if (sourceIndicators.length === 0) {
            sourceIndicators.push('<div class="mb-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-900 text-yellow-300 border border-yellow-500"><span class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>📊 Données locales (simulation)</div>');
        }
        
        const sourceIndicator = '<div class="mb-4">' + sourceIndicators.join('') + '</div>';
        
        // Afficher les informations générales
        const generalInfo = document.getElementById('osint-general-info');
        generalInfo.innerHTML = sourceIndicator;
        
        Object.entries(result.generalInfo).forEach(([key, value]) => {
            generalInfo.innerHTML += `
                <div class="bg-slate-700 p-3 rounded">
                    <div class="text-sm text-gray-400">${key}</div>
                    <div class="text-white font-medium">${value}</div>
                </div>
            `;
        });

        // Afficher la géolocalisation (si disponible)
        if (result.geolocation) {
            const geoSection = document.getElementById('osint-geolocation');
            const geoInfo = document.getElementById('osint-geo-info');
            geoSection.classList.remove('hidden');
            geoInfo.innerHTML = '';
            
            Object.entries(result.geolocation).forEach(([key, value]) => {
                geoInfo.innerHTML += `
                    <div class="bg-slate-700 p-3 rounded">
                        <div class="text-sm text-gray-400">${key}</div>
                        <div class="text-white font-medium">${value}</div>
                    </div>
                `;
            });
        }

        // Afficher les informations de sécurité
        const securityInfo = document.getElementById('osint-security-info');
        securityInfo.innerHTML = '';
        
        Object.entries(result.security).forEach(([key, value]) => {
            const colorClass = this.getSecurityColor(key, value);
            securityInfo.innerHTML += `
                <div class="flex justify-between items-center py-2 border-b border-slate-600 last:border-b-0">
                    <span class="text-gray-300">${key}</span>
                    <span class="${colorClass} font-medium">${value}</span>
                </div>
            `;
        });
    }

    getSecurityColor(key, value) {
        if (key === 'Niveau de risque') {
            switch (value) {
                case 'Faible': return 'text-green-400';
                case 'Moyen': return 'text-yellow-400';
                case 'Élevé': return 'text-orange-400';
                case 'Critique': return 'text-red-400';
            }
        }
        
        if (value === 'Oui' || value === 'Détecté' || value === 'Invalide') {
            return 'text-red-400';
        }
        if (value === 'Non' || value === 'Non détecté' || value === 'Valide' || value === 'Bonne') {
            return 'text-green-400';
        }
        if (value === 'Suspecte') {
            return 'text-yellow-400';
        }
        
        return 'text-gray-300';
    }

    addToOSINTHistory(target, type, result) {
        const historyEntry = {
            timestamp: new Date().toLocaleString('fr-FR'),
            target: target,
            type: type,
            status: result.status,
            risk: result.security['Niveau de risque'] || 'Inconnu'
        };
        
        this.osintHistory.unshift(historyEntry);
        if (this.osintHistory.length > 50) {
            this.osintHistory = this.osintHistory.slice(0, 50);
        }
        
        this.updateOSINTHistoryTable();
    }

    updateOSINTHistoryTable() {
        const historyTable = document.getElementById('osint-history');
        historyTable.innerHTML = '';
        
        this.osintHistory.forEach(entry => {
            const riskColor = this.getSecurityColor('Niveau de risque', entry.risk);
            const statusColor = entry.status === 'success' ? 'text-green-400' : 'text-yellow-400';
            
            historyTable.innerHTML += `
                <tr class="hover:bg-slate-700">
                    <td class="px-4 py-2 text-sm text-gray-300">${entry.timestamp}</td>
                    <td class="px-4 py-2 text-sm font-mono text-blue-400">${entry.target}</td>
                    <td class="px-4 py-2 text-sm text-gray-300">${entry.type.toUpperCase()}</td>
                    <td class="px-4 py-2 text-sm ${statusColor}">${entry.status}</td>
                    <td class="px-4 py-2 text-sm ${riskColor}">${entry.risk}</td>
                </tr>
            `;
        });
    }
}

// Fonctions globales
function showSection(sectionId) {
    console.log("Affichage section:", sectionId);
    
    // Cacher toutes les sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Afficher la section demandée
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
    
    // Mettre à jour les boutons de navigation
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('text-gray-300');
    });
    
    // Activer le bon bouton
    const activeBtn = document.querySelector(`[onclick="showSection('${sectionId}')"]`);
    if (activeBtn) {
        activeBtn.classList.remove('text-gray-300');
        activeBtn.classList.add('bg-blue-600', 'text-white');
    }
    
    // Initialiser la carte si nécessaire
    if (sectionId === 'attack-map') {
        setTimeout(() => {
            if (window.siemApp) {
                window.siemApp.currentSection = sectionId;
                window.siemApp.initializeMap();
            }
        }, 100);
    }
    
    if (window.siemApp) {
        window.siemApp.currentSection = sectionId;
    }
}

function refreshData() {
    console.log("Actualisation des données");
    if (window.siemApp) {
        window.siemApp.generateMockData();
        window.siemApp.updateDashboard();
    }
}

function analyzeOSINT() {
    const input = document.getElementById('osint-input');
    const typeSelect = document.getElementById('osint-type');
    
    const target = input.value.trim();
    const type = typeSelect.value;
    
    if (!target) {
        alert('Veuillez entrer une cible à analyser');
        return;
    }
    
    if (window.siemApp) {
        window.siemApp.analyzeTarget(target, type);
    }
}

// Fonction obsolète supprimée - utiliser config-manager.html

// Permettre l'analyse en appuyant sur Entrée
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('osint-input');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                analyzeOSINT();
            }
        });
    }
});

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM chargé, initialisation SIEM...");
    window.siemApp = new SIEMApp();
    
    // Charger les clés API sauvegardées et la watchlist
    setTimeout(() => {
        loadSavedAPIKeys();
        if (window.siemApp) {
            window.siemApp.loadWatchlist();
        }
    }, 100);
    
    // Mettre à jour l'heure toutes les secondes
    setInterval(() => {
        if (window.siemApp) {
            window.siemApp.updateDashboard();
        }
    }, 1000);
});

// Nouvelles fonctions globales pour les fonctionnalités avancées

function switchOSINTTab(tabName) {
    // Cacher tous les contenus d'onglets
    document.querySelectorAll('.osint-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Réinitialiser tous les onglets
    document.querySelectorAll('[id$="-tab"]').forEach(tab => {
        tab.classList.remove('text-blue-400', 'border-b-2', 'border-blue-400');
        tab.classList.add('text-gray-400');
    });
    
    // Activer l'onglet sélectionné
    const activeTab = document.getElementById(`${tabName}-tab`);
    const activeContent = document.getElementById(`${tabName}-analysis`) || 
                         document.getElementById(`${tabName}-management`);
    
    if (activeTab && activeContent) {
        activeTab.classList.remove('text-gray-400');
        activeTab.classList.add('text-blue-400', 'border-b-2', 'border-blue-400');
        activeContent.classList.remove('hidden');
    }
    
    if (window.siemApp) {
        window.siemApp.currentOSINTTab = tabName;
    }
}

function analyzeBulk() {
    if (window.siemApp) {
        window.siemApp.analyzeBulk();
    }
}

function addToWatchlist() {
    const input = document.getElementById('watchlist-input');
    const intervalSelect = document.getElementById('watchlist-interval');
    
    const target = input.value.trim();
    const interval = intervalSelect.value;
    
    if (!target) {
        alert('Veuillez entrer une cible à surveiller');
        return;
    }
    
    if (window.siemApp) {
        window.siemApp.addToWatchlist(target, interval);
        input.value = '';
    }
}

function removeFromWatchlist(itemId) {
    if (window.siemApp) {
        window.siemApp.removeFromWatchlist(itemId);
    }
}

function analyzeWatchlistItem(itemId) {
    if (window.siemApp) {
        const item = window.siemApp.watchlist.find(w => w.id === itemId);
        if (item) {
            window.siemApp.analyzeTarget(item.target, item.type);
        }
    }
}

function viewBulkDetails(target) {
    if (window.siemApp) {
        const result = window.siemApp.bulkResults.find(r => r.target === target);
        if (result) {
            // Afficher les détails dans une modal ou rediriger vers l'analyse unique
            document.getElementById('osint-input').value = target;
            switchOSINTTab('single');
            window.siemApp.displayOSINTResults(result);
            document.getElementById('osint-results').classList.remove('hidden');
        }
    }
}

// Charger les clés API depuis la base de données locale
async function loadSavedAPIKeys() {
    try {
        const response = await fetch('config/api.php?path=apis');
        const data = await response.json();
        
        if (data.success && window.siemApp) {
            const statusDiv = document.getElementById('api-status');
            let messages = [];
            
            // Charger chaque API active
            for (const apiConfig of data.data) {
                if (apiConfig.is_active && apiConfig.has_key) {
                    try {
                        const keyResponse = await fetch(`config/api.php?path=api&service=${apiConfig.service_name}`);
                        const keyData = await keyResponse.json();
                        
                        if (keyData.success && keyData.data.has_key) {
                            // Récupérer la vraie clé API
                            const apiKeyResponse = await fetch('config/api.php?path=apis');
                            // Note: Pour la sécurité, on ne récupère pas la vraie clé ici
                            // On indique juste que l'API est configurée
                            
                            switch (apiConfig.service_name) {
                                case 'abuseipdb':
                                    // La clé sera récupérée au moment de l'utilisation
                                    window.siemApp.hasAbuseIPDB = true;
                                    messages.push('🛡️ AbuseIPDB');
                                    break;
                                case 'virustotal':
                                    window.siemApp.hasVirusTotal = true;
                                    messages.push('🦠 VirusTotal');
                                    break;
                                case 'shodan':
                                    window.siemApp.hasShodan = true;
                                    messages.push('🔍 Shodan');
                                    break;
                            }
                        }
                    } catch (error) {
                        console.error(`Erreur chargement ${apiConfig.service_name}:`, error);
                    }
                }
            }
            
            if (messages.length > 0) {
                statusDiv.innerHTML = `<span class="text-green-400">🔑 APIs configurées: ${messages.join(' • ')}</span>`;
            } else {
                statusDiv.innerHTML = `<span class="text-yellow-400">⚠️ Aucune API configurée - <a href="config-manager.html" class="text-blue-400 hover:text-blue-300 underline">Configurer →</a></span>`;
            }
        }
    } catch (error) {
        console.error('Erreur chargement APIs:', error);
        document.getElementById('api-status').innerHTML = `<span class="text-red-400">❌ Erreur chargement APIs</span>`;
    }
}

console.log("Script SIEM chargé");
