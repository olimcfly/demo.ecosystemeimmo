<?php
/**
 * MIDDLEWARE MAINTENANCE - Version avec bandeau admin
 * ====================================================
 * - Bloque les visiteurs non autorisés (page 503)
 * - Pose un flag global pour afficher un bandeau sur le site public
 *   quand un admin avec IP autorisée navigue le site en maintenance
 * 
 * Placement : /includes/maintenance-check.php
 */

// Flag global — par défaut pas de bandeau
$GLOBALS['maintenance_banner'] = false;
$GLOBALS['maintenance_banner_message'] = '';
$GLOBALS['maintenance_banner_end'] = '';

// =============================================
// 1. EXCLUSIONS — ne jamais bloquer ces cas
// =============================================
$_maint_uri = $_SERVER['REQUEST_URI'] ?? '';

// Exclure /admin/ (toujours accessible)
if (strpos($_maint_uri, '/admin') !== false) {
    return;
}

// Exclure les fichiers statiques (assets)
$_maint_ext = strtolower(pathinfo(strtok($_maint_uri, '?'), PATHINFO_EXTENSION));
if (in_array($_maint_ext, ['css','js','png','jpg','jpeg','gif','svg','ico','woff','woff2','ttf','eot','webp','map','pdf','xml','json'])) {
    return;
}

// =============================================
// 2. CONNEXION BDD DIRECTE
// =============================================
try {
    $_maint_root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
    
    if (!defined('DB_HOST')) {
        $configFile = $_maint_root . '/config/config.php';
        if (!file_exists($configFile)) {
            error_log('[MAINTENANCE] config/config.php introuvable');
            return;
        }
        require_once $configFile;
    }
    
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
        error_log('[MAINTENANCE] Constantes DB non définies');
        return;
    }
    
    $_maint_pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    // =============================================
    // 3. LIRE STATUT MAINTENANCE
    // =============================================
    $_maint_stmt = $_maint_pdo->prepare("SELECT is_active, message, allowed_ips, end_date FROM maintenance WHERE id = 1 LIMIT 1");
    $_maint_stmt->execute();
    $_maint_data = $_maint_stmt->fetch();

    // Pas de ligne ou maintenance inactive → laisser passer
    if (!$_maint_data || (int)$_maint_data['is_active'] !== 1) {
        $_maint_pdo = null;
        return;
    }

    // =============================================
    // 4. DÉTECTER L'IP DU VISITEUR
    // =============================================
    $_maint_ip = '';
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $_maint_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $_maint_ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } else {
        $_maint_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    $_maint_allowed = ['127.0.0.1', '::1'];
    if (!empty($_maint_data['allowed_ips'])) {
        $_maint_extra = array_map('trim', explode(',', $_maint_data['allowed_ips']));
        $_maint_allowed = array_merge($_maint_allowed, array_filter($_maint_extra));
    }

    // =============================================
    // 5. IP AUTORISÉE → POSER LE FLAG BANDEAU
    // =============================================
    if (in_array($_maint_ip, $_maint_allowed, true)) {
        // ★ NOUVEAU : On pose le flag pour le bandeau public
        $GLOBALS['maintenance_banner'] = true;
        $GLOBALS['maintenance_banner_message'] = $_maint_data['message'] ?: '';
        $GLOBALS['maintenance_banner_ip'] = $_maint_ip;
        
        if (!empty($_maint_data['end_date'])) {
            $_maint_end = new DateTime($_maint_data['end_date']);
            $GLOBALS['maintenance_banner_end'] = $_maint_end->format('d/m/Y à H\hi');
        }
        
        $_maint_pdo = null;
        return; // IP autorisée → site normal + bandeau
    }

    // =============================================
    // 6. VÉRIFIER SESSION ADMIN
    // =============================================
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['admin_id']) || !empty($_SESSION['admin_logged_in']) || !empty($_SESSION['user_id'])) {
        // Admin connecté mais IP pas dans la whitelist → bandeau aussi
        $GLOBALS['maintenance_banner'] = true;
        $GLOBALS['maintenance_banner_message'] = $_maint_data['message'] ?: '';
        $GLOBALS['maintenance_banner_ip'] = $_maint_ip;
        
        if (!empty($_maint_data['end_date'])) {
            $_maint_end = new DateTime($_maint_data['end_date']);
            $GLOBALS['maintenance_banner_end'] = $_maint_end->format('d/m/Y à H\hi');
        }
        
        $_maint_pdo = null;
        return; // Admin connecté → site normal + bandeau
    }

    // =============================================
    // 7. MAINTENANCE ACTIVE → BLOQUER LE VISITEUR
    // =============================================
    $_maint_message = $_maint_data['message'] ?: 'Nous effectuons des améliorations sur notre site. Nous serons de retour très bientôt.';
    
    $_maint_retour = '';
    if (!empty($_maint_data['end_date'])) {
        $_maint_end = new DateTime($_maint_data['end_date']);
        $_maint_retour = 'Retour prévu le ' . $_maint_end->format('d/m/Y') . ' à ' . $_maint_end->format('H\hi');
    }

    $_maint_pdo = null;

    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

    echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Site en maintenance | Eduardo De Sul Immobilier</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%;width:100%;overflow:hidden}
        body{font-family:"Inter",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center}
        .bg{position:fixed;inset:0;background:radial-gradient(ellipse at 20% 50%,rgba(59,130,246,.15) 0%,transparent 50%),radial-gradient(ellipse at 80% 20%,rgba(139,92,246,.1) 0%,transparent 50%),radial-gradient(ellipse at 50% 80%,rgba(16,185,129,.08) 0%,transparent 50%);z-index:0}
        .c{position:relative;z-index:1;text-align:center;padding:40px 30px;max-width:560px;width:90%}
        .icon-box{width:80px;height:80px;margin:0 auto 16px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:36px;box-shadow:0 8px 32px rgba(59,130,246,.3);animation:pulse 3s ease-in-out infinite}
        .brand{font-size:13px;font-weight:500;color:#94a3b8;letter-spacing:2px;text-transform:uppercase;margin-bottom:36px}
        h1{font-size:2rem;font-weight:700;color:#f1f5f9;margin-bottom:16px;line-height:1.3}
        .msg{font-size:1.05rem;line-height:1.7;color:#94a3b8;margin-bottom:12px}
        .retour{font-size:.9rem;color:#60a5fa;margin-bottom:28px;font-weight:500}
        .bar{width:180px;height:3px;background:#1e293b;border-radius:3px;margin:0 auto 36px;overflow:hidden}
        .bar span{display:block;width:40%;height:100%;background:linear-gradient(90deg,#3b82f6,#8b5cf6);border-radius:3px;animation:slide 1.8s ease-in-out infinite}
        .contact{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:rgba(30,41,59,.8);border:1px solid #334155;border-radius:12px;font-size:.85rem;color:#94a3b8}
        .contact a{color:#60a5fa;text-decoration:none;font-weight:500}
        .contact a:hover{color:#93bbfc}
        @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
        @keyframes slide{0%{transform:translateX(-100%)}50%{transform:translateX(200%)}100%{transform:translateX(500%)}}
        @media(max-width:480px){h1{font-size:1.5rem}.msg{font-size:.95rem}.c{padding:24px 16px}}
    </style>
</head>
<body>
    <div class="bg"></div>
    <div class="c">
        <div class="icon-box">🔧</div>
        <div class="brand">Eduardo De Sul Immobilier</div>
        <h1>Site en maintenance</h1>
        <p class="msg">' . nl2br(htmlspecialchars($_maint_message)) . '</p>';
    
    if ($_maint_retour) {
        echo '<p class="retour">🕐 ' . htmlspecialchars($_maint_retour) . '</p>';
    }
    
    echo '  <div class="bar"><span></span></div>
        <div class="contact">📧 <a href="mailto:contact@eduardo-desul-immobilier.fr">contact@eduardo-desul-immobilier.fr</a></div>
    </div>
</body>
</html>';

    exit;

} catch (Exception $e) {
    error_log('[MAINTENANCE] ' . $e->getMessage());
    return;
}