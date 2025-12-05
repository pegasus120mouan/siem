<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Apikey');

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
$endpoint = $_GET['endpoint'] ?? '';
$id = $_GET['id'] ?? '';
$apiKey = $_SERVER['HTTP_X_APIKEY'] ?? '';

if (empty($endpoint) || empty($id) || empty($apiKey)) {
    http_response_code(400);
    echo json_encode(['error' => 'Endpoint, ID and API key required']);
    exit();
}

// Valider l'endpoint
$allowedEndpoints = ['ip_addresses', 'domains', 'urls'];
if (!in_array($endpoint, $allowedEndpoints)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit();
}

// Construire l'URL de l'API VirusTotal
$url = "https://www.virustotal.com/api/v3/{$endpoint}/{$id}";

// Créer le contexte pour file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'X-Apikey: ' . $apiKey,
            'Accept: application/json',
            'User-Agent: SIEM-Tool/1.0'
        ],
        'timeout' => 30,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

// Exécuter la requête
$response = @file_get_contents($url, false, $context);

// Vérifier si la requête a échoué
if ($response === false) {
    $error = error_get_last();
    http_response_code(500);
    echo json_encode([
        'error' => 'Request failed',
        'details' => $error ? $error['message'] : 'Unknown error',
        'debug' => [
            'url' => $url,
            'php_version' => PHP_VERSION
        ]
    ]);
    exit();
}

// Vérifier le code de réponse HTTP
$http_response_header = $http_response_header ?? [];
$status_line = $http_response_header[0] ?? '';
preg_match('/HTTP\/\d\.\d\s+(\d+)/', $status_line, $matches);
$httpCode = isset($matches[1]) ? (int)$matches[1] : 200;

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo $response ?: json_encode(['error' => 'API request failed', 'status' => $httpCode]);
    exit();
}

// Retourner la réponse
echo $response;
?>
