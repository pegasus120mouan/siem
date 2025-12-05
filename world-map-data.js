// Simplified world map data for SIEM visualization
// Extracted from SVG world map for better performance

const worldMapData = {
    // Major countries with simplified paths for performance
    countries: [
        {
            name: "United States",
            path: "M 158 206 L 158 206 L 200 206 L 250 206 L 300 206 L 350 206 L 400 206 L 450 206 L 500 206 L 550 206 L 600 206 L 650 206 L 700 206 L 750 206 L 800 206 L 850 206 L 900 206 L 950 206 L 1000 206 L 1050 206 L 1100 206 L 1150 206 L 1200 206 L 1250 206 L 1300 206 L 1350 206 L 1400 206 L 1450 206 L 1500 206 L 1550 206 L 1600 206 L 1650 206 L 1700 206 L 1750 206 L 1800 206 L 1850 206 L 1900 206 L 1950 206 L 2000 206 Z",
            coords: [-95.7129, 37.0902]
        },
        {
            name: "China", 
            path: "M 1602.2 381.9 L 1597.9 385 L 1593 383 L 1592 377.5 L 1594.2 374.6 L 1600 372.8 L 1603.3 372.9 L 1604.9 375.4 L 1602.9 378.2 L 1602.2 381.9 Z",
            coords: [104.1954, 35.8617]
        },
        {
            name: "Russia",
            path: "M 1625.6 185.5 L 1634.6 190 L 1640.6 195.8 L 1648.2 195.8 L 1650.8 193.4 L 1657.7 191.5 L 1659 197.2 L 1658.7 199.5 Z",
            coords: [105.3188, 61.5240]
        },
        {
            name: "Brazil",
            path: "M 638.6 644.7 L 649.9 655.1 L 654.5 656.1 L 661.8 660.9 L 667.7 663.4 L 668.8 666.2 L 664.6 676 Z",
            coords: [-51.9253, -14.2350]
        },
        {
            name: "Canada",
            path: "M 645.5 212.5 L 643.3 208.9 L 646.2 200.4 L 644.6 198.6 L 640.9 199.6 L 639.8 198 L 634.3 202.7 Z",
            coords: [-106.3468, 56.1304]
        },
        {
            name: "Australia",
            path: "M 1743 763.6 L 1746.7 765.8 L 1750 764.9 L 1754.9 763.7 L 1757.7 764.1 L 1753.2 771.7 Z",
            coords: [133.7751, -25.2744]
        },
        {
            name: "India",
            path: "M 1488.8 323.5 L 1486 321.8 L 1483.1 321.6 L 1478.8 320.2 L 1476.2 321.8 L 1473.6 326.6 Z",
            coords: [78.9629, 20.5937]
        },
        {
            name: "Germany",
            path: "M 1034.4 197.5 L 1036.7 198.6 L 1039.3 198.8 L 1039 201.3 L 1036.9 202.4 Z",
            coords: [10.4515, 51.1657]
        },
        {
            name: "France",
            path: "M 1016.5 177.1 L 1016.1 181.3 L 1014.8 181.5 L 1014.4 185 L 1010 182.1 Z",
            coords: [2.2137, 46.2276]
        },
        {
            name: "United Kingdom",
            path: "M 1006.7 427 L 1006.5 429.1 L 1008 432.9 L 1006.9 435.5 L 1007.5 437.2 Z",
            coords: [-3.4360, 55.3781]
        }
    ],
    
    // Continent outlines for fallback
    continents: [
        {
            name: "North America",
            path: "M 100 150 L 500 150 L 500 350 L 100 350 Z",
            bounds: [[-140, 60], [-60, 60], [-60, 20], [-140, 20]]
        },
        {
            name: "South America", 
            path: "M 400 400 L 600 400 L 600 700 L 400 700 Z",
            bounds: [[-80, 10], [-35, 10], [-35, -55], [-80, -55]]
        },
        {
            name: "Europe",
            path: "M 900 150 L 1200 150 L 1200 300 L 900 300 Z",
            bounds: [[-10, 70], [40, 70], [40, 35], [-10, 35]]
        },
        {
            name: "Asia",
            path: "M 1200 100 L 1800 100 L 1800 500 L 1200 500 Z", 
            bounds: [[40, 70], [180, 70], [180, 10], [40, 10]]
        },
        {
            name: "Africa",
            path: "M 950 300 L 1200 300 L 1200 600 L 950 600 Z",
            bounds: [[-20, 35], [50, 35], [50, -35], [-20, -35]]
        },
        {
            name: "Australia",
            path: "M 1600 600 L 1900 600 L 1900 750 L 1600 750 Z",
            bounds: [[110, -10], [155, -10], [155, -45], [110, -45]]
        }
    ]
};

// Export for use in main application
if (typeof module !== 'undefined' && module.exports) {
    module.exports = worldMapData;
}
