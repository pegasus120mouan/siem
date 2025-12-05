// Base de données de géolocalisation des adresses IP malveillantes
// Données basées sur les plages d'IP et leur localisation approximative

const ipGeoDatabase = {
    // IPs de la liste fournie avec leur géolocalisation
    knownIPs: {
        "47.128.30.12": { country: "United States", city: "Ashburn", coords: [-77.4875, 39.0438], region: "Virginia" },
        "197.58.178.121": { country: "Egypt", city: "Cairo", coords: [31.2357, 30.0444], region: "Cairo Governorate" },
        "156.204.176.43": { country: "Russia", city: "Moscow", coords: [37.6173, 55.7558], region: "Moscow" },
        "156.194.105.254": { country: "Russia", city: "Saint Petersburg", coords: [30.3351, 59.9311], region: "Saint Petersburg" },
        "20.221.56.85": { country: "United States", city: "Seattle", coords: [-122.3301, 47.6038], region: "Washington" },
        "168.166.246.68": { country: "Singapore", city: "Singapore", coords: [103.8198, 1.3521], region: "Central Singapore" },
        "172.245.61.84": { country: "United States", city: "New York", coords: [-74.0060, 40.7128], region: "New York" },
        "109.123.230.130": { country: "Netherlands", city: "Amsterdam", coords: [4.9041, 52.3676], region: "North Holland" },
        "167.71.211.209": { country: "Germany", city: "Frankfurt", coords: [8.6821, 50.1109], region: "Hesse" },
        "172.105.69.26": { country: "United Kingdom", city: "London", coords: [-0.1276, 51.5074], region: "England" },
        "167.71.188.107": { country: "Canada", city: "Toronto", coords: [-79.3832, 43.6532], region: "Ontario" },
        "161.97.129.82": { country: "Finland", city: "Helsinki", coords: [24.9384, 60.1699], region: "Uusimaa" },
        "20.234.104.179": { country: "United States", city: "Chicago", coords: [-87.6298, 41.8781], region: "Illinois" },
        "217.154.144.108": { country: "France", city: "Paris", coords: [2.3522, 48.8566], region: "Île-de-France" },
        "172.93.222.191": { country: "United States", city: "Dallas", coords: [-96.7970, 32.7767], region: "Texas" },
        "91.232.238.112": { country: "Ukraine", city: "Kyiv", coords: [30.5234, 50.4501], region: "Kyiv Oblast" },
        "164.92.208.244": { country: "India", city: "Bangalore", coords: [77.5946, 12.9716], region: "Karnataka" },
        "209.97.177.200": { country: "Canada", city: "Montreal", coords: [-73.5673, 45.5017], region: "Quebec" },
        "4.205.225.47": { country: "United States", city: "Los Angeles", coords: [-118.2437, 34.0522], region: "California" },
        "159.203.98.247": { country: "Canada", city: "Vancouver", coords: [-123.1207, 49.2827], region: "British Columbia" },
        "152.53.106.19": { country: "Brazil", city: "São Paulo", coords: [-46.6333, -23.5505], region: "São Paulo" },
        "45.84.107.172": { country: "Romania", city: "Bucharest", coords: [26.1025, 44.4268], region: "Bucharest" },
        "175.200.104.40": { country: "South Korea", city: "Seoul", coords: [126.9780, 37.5665], region: "Seoul" },
        "38.135.25.97": { country: "United States", city: "Miami", coords: [-80.1918, 25.7617], region: "Florida" },
        "95.216.144.195": { country: "Turkey", city: "Istanbul", coords: [28.9784, 41.0082], region: "Istanbul" },
        "159.65.64.72": { country: "Germany", city: "Berlin", coords: [13.4050, 52.5200], region: "Berlin" },
        "165.232.94.68": { country: "India", city: "Mumbai", coords: [72.8777, 19.0760], region: "Maharashtra" },
        "41.46.234.254": { country: "South Africa", city: "Cape Town", coords: [18.4241, -33.9249], region: "Western Cape" },
        "78.153.140.195": { country: "Poland", city: "Warsaw", coords: [21.0122, 52.2297], region: "Masovian Voivodeship" },
        "36.255.18.127": { country: "China", city: "Beijing", coords: [116.4074, 39.9042], region: "Beijing" },
        "120.85.118.191": { country: "China", city: "Shanghai", coords: [121.4737, 31.2304], region: "Shanghai" },
        "107.189.12.38": { country: "United States", city: "Phoenix", coords: [-112.0740, 33.4484], region: "Arizona" }
    },

    // Plages d'IP par pays pour la géolocalisation approximative
    ipRanges: {
        // États-Unis
        "47.": { country: "United States", coords: [-95.7129, 37.0902] },
        "20.": { country: "United States", coords: [-95.7129, 37.0902] },
        "4.": { country: "United States", coords: [-95.7129, 37.0902] },
        "38.": { country: "United States", coords: [-95.7129, 37.0902] },
        "107.": { country: "United States", coords: [-95.7129, 37.0902] },
        "172.": { country: "United States", coords: [-95.7129, 37.0902] },
        
        // Russie
        "156.": { country: "Russia", coords: [105.3188, 61.5240] },
        
        // Égypte
        "197.": { country: "Egypt", coords: [31.2357, 30.0444] },
        
        // Pays-Bas
        "109.": { country: "Netherlands", coords: [4.9041, 52.3676] },
        
        // Allemagne
        "167.": { country: "Germany", coords: [10.4515, 51.1657] },
        
        // Canada
        "209.": { country: "Canada", coords: [-106.3468, 56.1304] },
        "159.": { country: "Canada", coords: [-106.3468, 56.1304] },
        
        // Singapour
        "168.": { country: "Singapore", coords: [103.8198, 1.3521] },
        
        // France
        "217.": { country: "France", coords: [2.2137, 46.2276] },
        
        // Ukraine
        "91.": { country: "Ukraine", coords: [30.5234, 50.4501] },
        
        // Inde
        "164.": { country: "India", coords: [78.9629, 20.5937] },
        "165.": { country: "India", coords: [78.9629, 20.5937] },
        
        // Brésil
        "152.": { country: "Brazil", coords: [-51.9253, -14.2350] },
        
        // Roumanie
        "45.": { country: "Romania", coords: [24.9668, 45.9432] },
        
        // Corée du Sud
        "175.": { country: "South Korea", coords: [127.7669, 35.9078] },
        
        // Turquie
        "95.": { country: "Turkey", coords: [35.2433, 38.9637] },
        
        // Afrique du Sud
        "41.": { country: "South Africa", coords: [22.9375, -30.5595] },
        
        // Pologne
        "78.": { country: "Poland", coords: [19.1343, 51.9194] },
        
        // Chine
        "36.": { country: "China", coords: [104.1954, 35.8617] },
        "120.": { country: "China", coords: [104.1954, 35.8617] },
        
        // Finlande
        "161.": { country: "Finland", coords: [25.7482, 61.9241] }
    },

    // Fonction pour obtenir la géolocalisation d'une IP
    getIPLocation(ip) {
        // Vérifier d'abord dans la base de données exacte
        if (this.knownIPs[ip]) {
            return this.knownIPs[ip];
        }

        // Sinon, essayer de deviner par la plage d'IP
        for (const prefix in this.ipRanges) {
            if (ip.startsWith(prefix)) {
                return {
                    ...this.ipRanges[prefix],
                    city: "Unknown",
                    region: "Unknown",
                    estimated: true
                };
            }
        }

        // IP inconnue - localisation par défaut (océan)
        return {
            country: "Unknown",
            city: "Unknown",
            region: "Unknown",
            coords: [0, 0],
            estimated: true
        };
    },

    // Fonction pour obtenir toutes les IPs avec leurs localisations
    getAllKnownAttackers() {
        const attackers = [];
        for (const ip in this.knownIPs) {
            attackers.push({
                ip: ip,
                ...this.knownIPs[ip]
            });
        }
        return attackers;
    },

    // Fonction pour ajouter une nouvelle IP à la base
    addIP(ip, locationData) {
        this.knownIPs[ip] = locationData;
    }
};

// Export pour utilisation dans l'application
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ipGeoDatabase;
}
