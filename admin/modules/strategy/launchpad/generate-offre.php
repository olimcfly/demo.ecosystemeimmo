<?php
/**
 * API - Générer Offre (Étape 3)
 * /admin/api/launchpad/generate-offre.php
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
require_once __DIR__ . '/../../modules/launchpad/LaunchpadAI.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $manager = new LaunchpadManager($pdo, $_SESSION['admin_id']);
    $ai = new LaunchpadAI($pdo, $manager->getLaunchpadId(), CLAUDE_API_KEY);
    
    // Récupérer les données des étapes précédentes
    $profile = $manager->getProfile();
    $persona = $manager->getPrimaryPersona();
    
    if (!$profile || !$persona) {
        echo json_encode(['success' => false, 'error' => 'Data incomplete']);
        exit;
    }
    
    // Générer promesse
    $promiseResult = $ai->generatePromise($profile, $persona);
    if (!$promiseResult['success']) {
        echo json_encode($promiseResult);
        exit;
    }
    
    // Générer offre
    $offerResult = $ai->generateOffer($profile, $persona, $promiseResult['promise']);
    if (!$offerResult['success']) {
        echo json_encode($offerResult);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'promise' => $promiseResult['promise'],
            'offer' => $offerResult['offer']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>

<?php
/**
 * API - Générer Stratégie (Étape 4)
 * /admin/api/launchpad/generate-strategie.php
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
require_once __DIR__ . '/../../modules/launchpad/LaunchpadAI.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $manager = new LaunchpadManager($pdo, $_SESSION['admin_id']);
    $ai = new LaunchpadAI($pdo, $manager->getLaunchpadId(), CLAUDE_API_KEY);
    
    // Récupérer les données
    $profile = $manager->getProfile();
    $persona = $manager->getPrimaryPersona();
    $offer = $manager->getOffer();
    
    if (!$profile || !$persona || !$offer) {
        echo json_encode(['success' => false, 'error' => 'Data incomplete']);
        exit;
    }
    
    // Générer stratégie
    $strategyResult = $ai->recommendStrategy($profile, $persona, $offer);
    
    echo json_encode([
        'success' => $strategyResult['success'],
        'data' => $strategyResult['strategy'] ?? $strategyResult['error']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>

<?php
/**
 * API - Générer Plan Final (Étape 5)
 * /admin/api/launchpad/generate-plan-final.php
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
require_once __DIR__ . '/../../modules/launchpad/LaunchpadAI.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $manager = new LaunchpadManager($pdo, $_SESSION['admin_id']);
    $ai = new LaunchpadAI($pdo, $manager->getLaunchpadId(), CLAUDE_API_KEY);
    
    // Récupérer le résumé complet
    $summary = $manager->getCompleteSummary();
    
    if (!$summary['profile'] || !$summary['persona']) {
        echo json_encode(['success' => false, 'error' => 'Data incomplete']);
        exit;
    }
    
    // Générer plan final
    $planResult = $ai->generateFinalPlan($summary);
    
    echo json_encode([
        'success' => $planResult['success'],
        'data' => $planResult['plan'] ?? $planResult['error']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}