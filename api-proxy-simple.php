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

// Créer le contexte pour file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Key: ' . $apiKey,
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
            'php_version' => PHP_VERSION,
            'openssl_version' => OPENSSL_VERSION_TEXT ?? 'Not available'
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
