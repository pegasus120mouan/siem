<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'database.php';

try {
    $db = new ConfigDatabase();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_GET['path'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGet($db, $path);
            break;
            
        case 'POST':
            handlePost($db, $path);
            break;
            
        case 'PUT':
            handlePut($db, $path);
            break;
            
        case 'DELETE':
            handleDelete($db, $path);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($db, $path) {
    switch ($path) {
        case 'apis':
            echo json_encode([
                'success' => true,
                'data' => $db->getAllApiConfigs()
            ]);
            break;
            
        case 'api':
            $service = $_GET['service'] ?? '';
            if (!$service) {
                http_response_code(400);
                echo json_encode(['error' => 'Service name required']);
                return;
            }
            
            $key = $db->getApiKey($service);
            echo json_encode([
                'success' => true,
                'data' => [
                    'service' => $service,
                    'has_key' => !empty($key),
                    'key_preview' => $key ? substr($key, 0, 8) . '...' : null
                ]
            ]);
            break;
            
        case 'settings':
            echo json_encode([
                'success' => true,
                'data' => $db->getAllSettings()
            ]);
            break;
            
        case 'stats':
            echo json_encode([
                'success' => true,
                'data' => $db->getStats()
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePost($db, $path) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($path) {
        case 'api':
            $service = $input['service'] ?? '';
            $apiKey = $input['api_key'] ?? '';
            
            if (!$service || !$apiKey) {
                http_response_code(400);
                echo json_encode(['error' => 'Service name and API key required']);
                return;
            }
            
            // Validation basique des clés API
            $validations = [
                'abuseipdb' => ['min_length' => 50, 'max_length' => 100],
                'virustotal' => ['min_length' => 60, 'max_length' => 80],
                'shodan' => ['min_length' => 30, 'max_length' => 40]
            ];
            
            if (isset($validations[$service])) {
                $val = $validations[$service];
                if (strlen($apiKey) < $val['min_length'] || strlen($apiKey) > $val['max_length']) {
                    http_response_code(400);
                    echo json_encode(['error' => "Invalid API key format for $service"]);
                    return;
                }
            }
            
            $success = $db->saveApiKey($service, $apiKey);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'API key saved successfully' : 'Failed to save API key'
            ]);
            break;
            
        case 'setting':
            $key = $input['key'] ?? '';
            $value = $input['value'] ?? '';
            $description = $input['description'] ?? null;
            
            if (!$key) {
                http_response_code(400);
                echo json_encode(['error' => 'Setting key required']);
                return;
            }
            
            $success = $db->setSetting($key, $value, $description);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Setting saved successfully' : 'Failed to save setting'
            ]);
            break;
            
        case 'backup':
            $backupPath = $db->backup();
            if ($backupPath) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'backup_path' => basename($backupPath)
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create backup']);
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handlePut($db, $path) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($path) {
        case 'api/toggle':
            $service = $input['service'] ?? '';
            $isActive = $input['is_active'] ?? false;
            
            if (!$service) {
                http_response_code(400);
                echo json_encode(['error' => 'Service name required']);
                return;
            }
            
            $success = $db->toggleApiStatus($service, $isActive);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'API status updated' : 'Failed to update API status'
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}

function handleDelete($db, $path) {
    switch ($path) {
        case 'api':
            $service = $_GET['service'] ?? '';
            
            if (!$service) {
                http_response_code(400);
                echo json_encode(['error' => 'Service name required']);
                return;
            }
            
            $success = $db->deleteApiKey($service);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'API key deleted successfully' : 'Failed to delete API key'
            ]);
            break;
            
        case 'cleanup':
            $db->cleanup();
            echo json_encode([
                'success' => true,
                'message' => 'Cleanup completed'
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
}
?>
