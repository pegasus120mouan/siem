<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Service');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';

try {
    $db = new ConfigDatabase();
    $service = $_SERVER['HTTP_X_SERVICE'] ?? $_GET['service'] ?? '';
    $target = $_GET['target'] ?? '';
    $type = $_GET['type'] ?? '';
    
    if (!$service || !$target) {
        http_response_code(400);
        echo json_encode(['error' => 'Service and target required']);
        exit();
    }
    
    // Récupérer la clé API de manière sécurisée
    $apiKey = $db->getApiKey($service);
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode(['error' => 'API key not configured for ' . $service]);
        exit();
    }
    
    // Router vers le bon service
    switch ($service) {
        case 'abuseipdb':
            $result = queryAbuseIPDB($target, $apiKey);
            break;
            
        case 'virustotal':
            $result = queryVirusTotal($target, $type, $apiKey);
            break;
            
        case 'shodan':
            $result = queryShodan($target, $apiKey);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported service: ' . $service]);
            exit();
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function queryAbuseIPDB($ip, $apiKey) {
    $url = "https://api.abuseipdb.com/api/v2/check?" . http_build_query([
        'ipAddress' => $ip,
        'maxAgeInDays' => 90,
        'verbose' => ''
    ]);
    
    return makeSecureRequest($url, [
        'Key: ' . $apiKey,
        'Accept: application/json'
    ]);
}

function queryVirusTotal($target, $type, $apiKey) {
    $endpoint = '';
    $identifier = '';
    
    switch ($type) {
        case 'ip':
            $endpoint = 'ip_addresses';
            $identifier = $target;
            break;
        case 'domain':
            $endpoint = 'domains';
            $identifier = $target;
            break;
        case 'url':
            $endpoint = 'urls';
            $identifier = base64_encode($target);
            break;
        default:
            throw new Exception('Invalid type for VirusTotal');
    }
    
    $url = "https://www.virustotal.com/api/v3/{$endpoint}/{$identifier}";
    
    return makeSecureRequest($url, [
        'X-Apikey: ' . $apiKey,
        'Accept: application/json'
    ]);
}

function queryShodan($ip, $apiKey) {
    $url = "https://api.shodan.io/shodan/host/{$ip}?key={$apiKey}";
    
    return makeSecureRequest($url, [
        'Accept: application/json'
    ]);
}

function makeSecureRequest($url, $headers) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => array_merge($headers, ['User-Agent: SIEM-Tool/1.0']),
            'timeout' => 30,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        throw new Exception('Request failed: ' . ($error['message'] ?? 'Unknown error'));
    }
    
    // Vérifier le code de réponse HTTP
    $http_response_header = $http_response_header ?? [];
    $status_line = $http_response_header[0] ?? '';
    preg_match('/HTTP\/\d\.\d\s+(\d+)/', $status_line, $matches);
    $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
    
    if ($httpCode !== 200) {
        throw new Exception("HTTP {$httpCode}: " . $response);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response');
    }
    
    return [
        'success' => true,
        'service' => basename(parse_url($url, PHP_URL_HOST)),
        'data' => $data
    ];
}
?>
