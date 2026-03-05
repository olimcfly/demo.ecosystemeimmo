<?php
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/classes/Database.php';

$db = Database::getInstance();

// Vérifier si maintenance est active
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['maintenance_mode']);
$modeData = $stmt->fetch(PDO::FETCH_ASSOC);
$isActive = $modeData ? json_decode($modeData['setting_value'], true)['enabled'] : 0;

// Vérifier whitelist
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['maintenance_whitelist']);
$whitelistData = $stmt->fetch(PDO::FETCH_ASSOC);
$whitelist = $whitelistData ? json_decode($whitelistData['setting_value'], true) : [];

$userIP = $_SERVER['REMOTE_ADDR'];
$isWhitelisted = in_array($userIP, $whitelist);

// Si maintenance inactive et pas whitelisted, show normal page
if (!$isActive && !$isWhitelisted) {
    header('Location: /');
    exit;
}

// Charger le message
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
$stmt->execute(['maintenance_message']);
$msgData = $stmt->fetch(PDO::FETCH_ASSOC);
$msgArray = $msgData ? json_decode($msgData['setting_value'], true) : [];
$title = $msgArray['title'] ?? 'Site en maintenance';
$message = $msgArray['text'] ?? 'Nous procédons actuellement à des maintenances. Nous serons de retour très bientôt.';

// Header 503 Service Unavailable
http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .maintenance-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
            animation: fadeIn 0.6s ease-in-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        
        p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.95;
            line-height: 1.6;
            text-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
        }
        
        .loader {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 30px;
        }
        
        .loader span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            animation: pulse 1.4s ease-in-out infinite;
        }
        
        .loader span:nth-child(1) { animation-delay: 0s; }
        .loader span:nth-child(2) { animation-delay: 0.2s; }
        .loader span:nth-child(3) { animation-delay: 0.4s; }
        
        @keyframes pulse {
            0%, 100% { 
                opacity: 0.3;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.2);
            }
        }
        
        .info {
            margin-top: 40px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .info small {
            opacity: 0.8;
            font-size: 0.9rem;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #fbbf24;
            margin-right: 8px;
            animation: blink 2s ease-in-out infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @media (max-width: 600px) {
            h1 { font-size: 1.8rem; }
            p { font-size: 1rem; }
            .icon { font-size: 60px; }
            .maintenance-container { padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="icon">🔧</div>
        
        <h1><?= htmlspecialchars($title) ?></h1>
        
        <p><?= htmlspecialchars($message) ?></p>
        
        <div class="loader">
            <span></span>
            <span></span>
            <span></span>
        </div>
        
        <div class="info">
            <div>
                <span class="status-indicator"></span>
                <small>Maintenance en cours</small>
            </div>
            <small style="display: block; margin-top: 10px;">
                Nous nous excusons pour le désagrément. Le site sera opérationnel dans quelques instants.
            </small>
        </div>
    </div>
</body>
</html>