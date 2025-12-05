<?php
$pageTitle = "OSINT Analysis";
require_once 'includes/header.php';
?>

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">OSINT Analysis</h1>
            <p class="text-gray-400">Analyse de renseignement open source</p>
        </div>
        <div class="flex space-x-3">
            <button onclick="openModal('bulkAnalysisModal')" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-layer-group mr-2"></i>Analyse en lot
            </button>
            <button onclick="clearResults()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors">
                <i class="fas fa-trash mr-2"></i>Effacer
            </button>
        </div>
    </div>
    
    <!-- Formulaire d'analyse -->
    <div class="card rounded-xl p-6">
        <h2 class="text-white text-lg font-semibold mb-4">Nouvelle Analyse</h2>
        
        <form id="osintForm" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Type d'analyse</label>
                    <select id="analysisType" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="ip">Adresse IP</option>
                        <option value="domain">Domaine</option>
                        <option value="hash">Hash de fichier</option>
                        <option value="url">URL</option>
                        <option value="email">Adresse email</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Sources OSINT</label>
                    <div class="flex flex-wrap gap-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="sources" value="virustotal" checked class="mr-2">
                            <span class="text-white text-sm">VirusTotal</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="sources" value="abuseipdb" checked class="mr-2">
                            <span class="text-white text-sm">AbuseIPDB</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="sources" value="shodan" class="mr-2">
                            <span class="text-white text-sm">Shodan</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="sources" value="otx" class="mr-2">
                            <span class="text-white text-sm">AlienVault OTX</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-white text-sm font-medium mb-2">Cible à analyser</label>
                <div class="flex space-x-2">
                    <input type="text" id="analysisTarget" placeholder="Entrez l'IP, domaine, hash, URL ou email..." class="flex-1 bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors">
                        <i class="fas fa-search mr-2"></i>Analyser
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Historique des analyses -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <!-- Résultats d'analyse -->
            <div class="card rounded-xl p-6">
                <h2 class="text-white text-lg font-semibold mb-4">Résultats d'Analyse</h2>
                
                <div id="analysisResults" class="space-y-4">
                    <div class="text-center py-8">
                        <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-400">Aucune analyse en cours</p>
                        <p class="text-gray-500 text-sm">Entrez une cible ci-dessus pour commencer</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <!-- Analyses récentes -->
            <div class="card rounded-xl p-6 mb-6">
                <h3 class="text-white text-lg font-semibold mb-4">Analyses Récentes</h3>
                
                <div class="space-y-3" id="recentAnalyses">
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-white text-sm font-medium">185.220.101.42</p>
                            <p class="text-gray-400 text-xs">IP • il y a 5 min</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Malveillant</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-white text-sm font-medium">example.com</p>
                            <p class="text-gray-400 text-xs">Domaine • il y a 12 min</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Propre</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <div>
                            <p class="text-white text-sm font-medium">abc123...def</p>
                            <p class="text-gray-400 text-xs">Hash • il y a 25 min</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Suspect</span>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques OSINT -->
            <div class="card rounded-xl p-6">
                <h3 class="text-white text-lg font-semibold mb-4">Statistiques</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Analyses aujourd'hui</span>
                        <span class="text-white font-semibold">47</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Menaces détectées</span>
                        <span class="text-red-400 font-semibold">12</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Taux de détection</span>
                        <span class="text-yellow-400 font-semibold">25.5%</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-gray-400">Sources actives</span>
                        <span class="text-green-400 font-semibold">4/5</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal d'analyse en lot -->
<div id="bulkAnalysisModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop">
    <div class="bg-gray-800 rounded-xl p-6 w-full max-w-2xl mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-white text-lg font-semibold">Analyse en Lot</h3>
            <button onclick="closeModal('bulkAnalysisModal')" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="bulkAnalysisForm">
            <div class="space-y-4">
                <div>
                    <label class="block text-white text-sm font-medium mb-2">Liste des cibles</label>
                    <textarea id="bulkTargets" rows="8" placeholder="Entrez une cible par ligne (IP, domaine, hash, URL, email)..." class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none"></textarea>
                    <p class="text-gray-400 text-xs mt-1">Maximum 100 cibles par analyse</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Type d'analyse</label>
                        <select id="bulkAnalysisType" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <option value="auto">Détection automatique</option>
                            <option value="ip">Adresses IP</option>
                            <option value="domain">Domaines</option>
                            <option value="hash">Hashes</option>
                            <option value="url">URLs</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-white text-sm font-medium mb-2">Priorité</label>
                        <select id="bulkPriority" class="w-full bg-gray-700 text-white px-3 py-2 rounded-md border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <option value="normal">Normale</option>
                            <option value="high">Élevée</option>
                            <option value="urgent">Urgente</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeModal('bulkAnalysisModal')" class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
                    Annuler
                </button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition-colors">
                    <i class="fas fa-play mr-2"></i>Lancer l'analyse
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('osintForm').addEventListener('submit', function(e) {
        e.preventDefault();
        performAnalysis();
    });
    
    document.getElementById('bulkAnalysisForm').addEventListener('submit', function(e) {
        e.preventDefault();
        performBulkAnalysis();
    });
});

function performAnalysis() {
    const target = document.getElementById('analysisTarget').value.trim();
    const type = document.getElementById('analysisType').value;
    const sources = Array.from(document.querySelectorAll('input[name="sources"]:checked')).map(cb => cb.value);
    
    if (!target) {
        showNotification('Veuillez entrer une cible à analyser', 'warning');
        return;
    }
    
    if (sources.length === 0) {
        showNotification('Veuillez sélectionner au moins une source OSINT', 'warning');
        return;
    }
    
    showAnalysisProgress(target, type, sources);
}

function showAnalysisProgress(target, type, sources) {
    const resultsContainer = document.getElementById('analysisResults');
    
    resultsContainer.innerHTML = `
        <div class="border border-blue-500 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-semibold">Analyse de ${target}</h3>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">En cours</span>
            </div>
            
            <div class="space-y-3">
                ${sources.map(source => `
                    <div class="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-spinner fa-spin text-blue-400 mr-3"></i>
                            <span class="text-white">${getSourceName(source)}</span>
                        </div>
                        <span class="text-gray-400 text-sm">Analyse...</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    // Simuler l'analyse
    setTimeout(() => {
        showAnalysisResults(target, type, sources);
    }, 3000);
}

function showAnalysisResults(target, type, sources) {
    const resultsContainer = document.getElementById('analysisResults');
    
    // Simuler des résultats
    const results = sources.map(source => ({
        source: source,
        status: Math.random() > 0.3 ? 'success' : 'error',
        threat: Math.random() > 0.7 ? 'malicious' : Math.random() > 0.4 ? 'suspicious' : 'clean',
        details: generateMockDetails(source, type)
    }));
    
    const overallThreat = results.some(r => r.threat === 'malicious') ? 'malicious' : 
                         results.some(r => r.threat === 'suspicious') ? 'suspicious' : 'clean';
    
    const threatColors = {
        malicious: 'bg-red-100 text-red-800',
        suspicious: 'bg-yellow-100 text-yellow-800',
        clean: 'bg-green-100 text-green-800'
    };
    
    resultsContainer.innerHTML = `
        <div class="border border-gray-600 rounded-lg p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-semibold">Résultats pour ${target}</h3>
                <span class="px-2 py-1 text-xs font-semibold rounded-full ${threatColors[overallThreat]}">${getThreatLabel(overallThreat)}</span>
            </div>
            
            <div class="space-y-3">
                ${results.map(result => `
                    <div class="p-3 bg-gray-700 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <i class="fas fa-${result.status === 'success' ? 'check-circle text-green-400' : 'exclamation-circle text-red-400'} mr-3"></i>
                                <span class="text-white font-medium">${getSourceName(result.source)}</span>
                            </div>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${threatColors[result.threat]}">${getThreatLabel(result.threat)}</span>
                        </div>
                        <div class="text-gray-400 text-sm">
                            ${result.details}
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="mt-4 flex space-x-2">
                <button onclick="exportResults('${target}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors">
                    <i class="fas fa-download mr-1"></i>Exporter
                </button>
                <button onclick="addToWatchlist('${target}')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm transition-colors">
                    <i class="fas fa-eye mr-1"></i>Surveiller
                </button>
            </div>
        </div>
    `;
    
    // Ajouter à l'historique
    addToRecentAnalyses(target, type, overallThreat);
}

function getSourceName(source) {
    const names = {
        virustotal: 'VirusTotal',
        abuseipdb: 'AbuseIPDB',
        shodan: 'Shodan',
        otx: 'AlienVault OTX'
    };
    return names[source] || source;
}

function getThreatLabel(threat) {
    const labels = {
        malicious: 'Malveillant',
        suspicious: 'Suspect',
        clean: 'Propre'
    };
    return labels[threat] || threat;
}

function generateMockDetails(source, type) {
    const details = {
        virustotal: {
            ip: 'Détecté par 12/89 moteurs antivirus',
            domain: 'Aucune détection malveillante',
            hash: 'Fichier détecté comme Trojan.Generic',
            url: 'URL signalée comme phishing'
        },
        abuseipdb: {
            ip: 'Signalé 23 fois dans les derniers 30 jours',
            domain: 'Aucun signalement d\'abus',
            hash: 'N/A pour ce type',
            url: 'Domaine associé à du spam'
        },
        shodan: {
            ip: 'Ports ouverts: 22, 80, 443',
            domain: 'Résolution DNS normale',
            hash: 'N/A pour ce type',
            url: 'Serveur web Apache/2.4.41'
        },
        otx: {
            ip: 'Présent dans 3 pulses de menaces',
            domain: 'Associé à une campagne de malware',
            hash: 'Hash connu dans la base de données',
            url: 'URL liée à du phishing'
        }
    };
    
    return details[source]?.[type] || 'Analyse terminée';
}

function addToRecentAnalyses(target, type, threat) {
    const container = document.getElementById('recentAnalyses');
    const threatColors = {
        malicious: 'bg-red-100 text-red-800',
        suspicious: 'bg-yellow-100 text-yellow-800',
        clean: 'bg-green-100 text-green-800'
    };
    
    const newAnalysis = document.createElement('div');
    newAnalysis.className = 'flex items-center justify-between p-3 bg-gray-700 rounded-lg';
    newAnalysis.innerHTML = `
        <div>
            <p class="text-white text-sm font-medium">${target.length > 20 ? target.substring(0, 20) + '...' : target}</p>
            <p class="text-gray-400 text-xs">${type.toUpperCase()} • à l'instant</p>
        </div>
        <span class="px-2 py-1 text-xs font-semibold rounded-full ${threatColors[threat]}">${getThreatLabel(threat)}</span>
    `;
    
    container.insertBefore(newAnalysis, container.firstChild);
    
    // Garder seulement les 5 dernières analyses
    while (container.children.length > 5) {
        container.removeChild(container.lastChild);
    }
}

function performBulkAnalysis() {
    const targets = document.getElementById('bulkTargets').value.trim().split('\n').filter(t => t.trim());
    const type = document.getElementById('bulkAnalysisType').value;
    const priority = document.getElementById('bulkPriority').value;
    
    if (targets.length === 0) {
        showNotification('Veuillez entrer au moins une cible', 'warning');
        return;
    }
    
    if (targets.length > 100) {
        showNotification('Maximum 100 cibles par analyse', 'warning');
        return;
    }
    
    showNotification(`Analyse en lot lancée pour ${targets.length} cibles`, 'info');
    closeModal('bulkAnalysisModal');
    
    // Simuler le traitement
    setTimeout(() => {
        showNotification(`Analyse en lot terminée: ${Math.floor(targets.length * 0.8)} cibles analysées`, 'success');
    }, 5000);
}

function clearResults() {
    document.getElementById('analysisResults').innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-search text-gray-400 text-4xl mb-4"></i>
            <p class="text-gray-400">Aucune analyse en cours</p>
            <p class="text-gray-500 text-sm">Entrez une cible ci-dessus pour commencer</p>
        </div>
    `;
}

function exportResults(target) {
    showNotification(`Export des résultats pour ${target}`, 'info');
}

function addToWatchlist(target) {
    showNotification(`${target} ajouté à la liste de surveillance`, 'success');
}
</script>

<?php require_once 'includes/footer.php'; ?>
