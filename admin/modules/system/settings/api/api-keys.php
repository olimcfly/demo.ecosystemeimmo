<?php
/**
 * /admin/modules/settings/api/api-keys.php
 * Endpoint AJAX pour la gestion des clés API
 * 
 * Depuis ce fichier :
 *   __DIR__ = /admin/modules/settings/api/
 *   
 *   Pour atteindre /config/config.php :
 *     __DIR__ . '/../../../../config/config.php'
 *     (api → settings → modules → admin → racine → config)
 *   
 *   Pour atteindre ApiKeyManager.php (même dossier) :
 *     __DIR__ . '/ApiKeyManager.php'
 * 
 * Actions : save, delete, verify, check, check_multiple, check_module
 */

header('Content-Type: application/json');

session_start();

// Authentification obligatoire
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// ═══ CHEMINS CORRIGÉS ═══
// Depuis /admin/modules/settings/api/ → remonter 4 niveaux vers la racine
require_once __DIR__ . '/../../../../config/config.php';

// ApiKeyManager est dans le même dossier
require_once __DIR__ . '/ApiKeyManager.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

$apiManager = ApiKeyManager::getInstance($pdo);

// Lire le body JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    // Fallback sur GET pour action "check"
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $input = $_GET;
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action requise']);
        exit;
    }
}

$action = $input['action'] ?? '';

// ═══ CSRF check pour les actions d'écriture ═══
if (in_array($action, ['save', 'delete']) && isset($_SESSION['csrf_token'])) {
    $csrfToken = $input['csrf_token'] ?? '';
    if ($csrfToken !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
}

// ═══ ROUTER ═══
switch ($action) {

    // ─── Sauvegarder une clé ───
    case 'save':
        $serviceKey = trim($input['service_key'] ?? '');
        $apiKey = trim($input['api_key'] ?? '');
        
        if (empty($serviceKey) || empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'Service et clé API requis']);
            exit;
        }
        
        if (!ApiKeyManager::getServiceInfo($serviceKey)) {
            echo json_encode(['success' => false, 'message' => 'Service inconnu : ' . $serviceKey]);
            exit;
        }
        
        if (strlen($apiKey) < 8) {
            echo json_encode(['success' => false, 'message' => 'La clé semble trop courte (min 8 caractères)']);
            exit;
        }
        
        $result = $apiManager->saveKey($serviceKey, $apiKey);
        
        if ($result) {
            try {
                $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
                $log->execute([$_SESSION['admin_id'], 'api_key_saved', "Clé API configurée : $serviceKey"]);
            } catch (Exception $e) { /* table optionnelle */ }
            
            echo json_encode(['success' => true, 'message' => 'Clé enregistrée avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
        }
        break;
    
    // ─── Supprimer une clé ───
    case 'delete':
        $serviceKey = trim($input['service_key'] ?? '');
        
        if (empty($serviceKey)) {
            echo json_encode(['success' => false, 'message' => 'Service requis']);
            exit;
        }
        
        $result = $apiManager->deleteKey($serviceKey);
        
        if ($result) {
            try {
                $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
                $log->execute([$_SESSION['admin_id'], 'api_key_deleted', "Clé API supprimée : $serviceKey"]);
            } catch (Exception $e) { /* table optionnelle */ }
            
            echo json_encode(['success' => true, 'message' => 'Clé supprimée']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        break;
    
    // ─── Vérifier si une clé est configurée (GET) ───
    case 'check':
        $serviceKey = trim($input['service'] ?? $input['service_key'] ?? '');
        
        if (empty($serviceKey)) {
            echo json_encode(['success' => false, 'message' => 'Service requis']);
            exit;
        }
        
        $hasKey = $apiManager->hasKey($serviceKey);
        $serviceInfo = ApiKeyManager::getServiceInfo($serviceKey);
        
        echo json_encode([
            'success'       => true,
            'service_key'   => $serviceKey,
            'is_configured' => $hasKey,
            'service_name'  => $serviceInfo['name'] ?? $serviceKey,
            'settings_url'  => '/admin/index.php?module=settings&tab=api-keys'
        ]);
        break;
    
    // ─── Vérifier plusieurs clés d'un coup (POST) ───
    case 'check_multiple':
        $services = $input['services'] ?? [];
        
        if (!is_array($services) || empty($services)) {
            echo json_encode(['success' => false, 'message' => 'Liste de services requise']);
            exit;
        }
        
        $results = [];
        foreach ($services as $sk) {
            $sk = trim($sk);
            $results[$sk] = [
                'is_configured' => $apiManager->hasKey($sk),
                'service_name'  => ApiKeyManager::getServiceInfo($sk)['name'] ?? $sk
            ];
        }
        
        echo json_encode(['success' => true, 'services' => $results]);
        break;
    
    // ─── Vérifier les clés requises par un module (GET) ───
    case 'check_module':
        $moduleSlug = trim($input['module'] ?? '');
        
        if (empty($moduleSlug)) {
            echo json_encode(['success' => false, 'message' => 'Module requis']);
            exit;
        }
        
        $check = $apiManager->checkRequiredKeys($moduleSlug);
        
        $missingDetails = [];
        foreach ($check['missing'] as $sk) {
            $info = ApiKeyManager::getServiceInfo($sk);
            $missingDetails[] = [
                'service_key' => $sk,
                'name'        => $info['name'] ?? $sk,
                'icon'        => $info['icon'] ?? 'fas fa-key',
                'color'       => $info['color'] ?? '#64748b',
                'url'         => $info['url'] ?? '#'
            ];
        }
        
        echo json_encode([
            'success'      => true,
            'module'       => $moduleSlug,
            'has_all_keys' => empty($check['missing']),
            'missing'      => $missingDetails,
            'available'    => $check['available'],
            'settings_url' => '/admin/index.php?module=settings&tab=api-keys'
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue : ' . $action]);
}