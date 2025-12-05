<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Key');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier que c'est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Récupérer les paramètres
$ip = $_GET['ip'] ?? '';
$apiKey = $_SERVER['HTTP_KEY'] ?? '';

if (empty($ip) || empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'IP address and API key required']);
    exit();
}

// Valider l'IP
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid IP address']);
    exit();
}

// Construire l'URL de l'API AbuseIPDB
$url = "https://api.abuseipdb.com/api/v2/check?" . http_build_query([
    'ipAddress' => $ip,
    'maxAgeInDays' => 90,
    'verbose' => ''
]);

// Initialiser cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Key: ' . $apiKey,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,  // Désactiver la vérification SSL pour Laragon
    CURLOPT_SSL_VERIFYHOST => false,  // Désactiver la vérification de l'hôte SSL
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'SIEM-Tool/1.0',
    CURLOPT_VERBOSE => false
]);

// Exécuter la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Vérifier les erreurs cURL
if ($error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'cURL error: ' . $error,
        'debug' => [
            'url' => $url,
            'curl_version' => curl_version(),
            'php_version' => PHP_VERSION
        ]
    ]);
    exit();
}

// Vérifier le code de réponse HTTP
if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response ?: json_encode(['error' => 'API request failed']);
    exit();
}

// Retourner la réponse
echo $response;
?>
