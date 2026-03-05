<?php
/**
 * API - Sauvegarder une étape
 * /admin/api/launchpad/save-step.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../modules/launchpad/LaunchpadManager.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $input = json_decode(file_get_contents('php://input'), true);
    $step = $input['step'] ?? null;
    $data = $input['data'] ?? [];
    
    if (!$step || !$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }
    
    $manager = new LaunchpadManager($pdo, $_SESSION['admin_id']);
    $result = [];
    
    switch ($step) {
        case 1:
            $result = $manager->saveProfile($data);
            break;
        case 2:
            $result = $manager->savePrimaryPersona($data);
            break;
        case 3:
            $result = $manager->saveOffer($data);
            break;
        case 4:
            $result = $manager->saveStrategy($data);
            break;
        case 5:
            $result = $manager->saveFinalPlan($data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid step']);
            exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}