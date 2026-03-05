<?php
/**
 * TEST POST-INSTALLATION MAINTENANCE
 * ===================================
 * Upload à la racine : /public_html/test-maintenance.php
 * Accéder : https://eduardo-desul-immobilier.fr/test-maintenance.php
 * SUPPRIMER APRÈS USAGE
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Test Maintenance</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#e0e0e0;line-height:1.8}";
echo ".ok{color:#00ff88;font-weight:bold} .err{color:#ff4444;font-weight:bold} .warn{color:#ffaa00}</style></head><body>";
echo "<h1>🧪 Test Module Maintenance</h1>";

$root = __DIR__;
$allGood = true;

// 1. Config
echo "<h2>1. Config</h2>";
if (file_exists($root . '/config/config.php')) {
    require_once $root . '/config/config.php';
    if (defined('DB_HOST') && defined('DB_NAME')) {
        echo "<p class='ok'>✅ config.php chargé — DB: " . DB_NAME . "</p>";
    } else {
        echo "<p class='err'>❌ config.php existe mais constantes manquantes</p>";
        $allGood = false;
    }
} else {
    echo "<p class='err'>❌ config/config.php introuvable</p>";
    $allGood = false;
}

// 2. Connexion PDO
echo "<h2>2. Connexion PDO</h2>";
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    echo "<p class='ok'>✅ Connexion PDO OK</p>";
} catch (Exception $e) {
    echo "<p class='err'>❌ PDO: " . htmlspecialchars($e->getMessage()) . "</p>";
    $allGood = false;
}

// 3. Table maintenance
echo "<h2>3. Table maintenance</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM maintenance WHERE id = 1 LIMIT 1");
    $data = $stmt->fetch();
    if ($data) {
        echo "<p class='ok'>✅ Table maintenance — ligne id=1 trouvée</p>";
        echo "<p>   is_active = <strong>" . $data['is_active'] . "</strong> " . ($data['is_active'] ? '🔴 ACTIVE' : '🟢 INACTIVE') . "</p>";
        echo "<p>   message = " . htmlspecialchars(substr($data['message'] ?? '', 0, 80)) . "...</p>";
        echo "<p>   allowed_ips = " . htmlspecialchars($data['allowed_ips'] ?? 'aucune') . "</p>";
    } else {
        echo "<p class='err'>❌ Aucune ligne id=1 dans la table maintenance</p>";
        $allGood = false;
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ Table maintenance: " . htmlspecialchars($e->getMessage()) . "</p>";
    $allGood = false;
}

// 4. Fichier middleware
echo "<h2>4. Middleware maintenance-check.php</h2>";
$mw = $root . '/includes/maintenance-check.php';
if (file_exists($mw)) {
    $content = file_get_contents($mw);
    echo "<p class='ok'>✅ Fichier existe (" . filesize($mw) . " octets)</p>";
    
    if (strpos($content, "FROM maintenance WHERE id = 1") !== false) {
        echo "<p class='ok'>✅ Lit bien la table `maintenance` (bonne version)</p>";
    } elseif (strpos($content, "setting_value") !== false) {
        echo "<p class='err'>❌ ANCIENNE VERSION — lit la table settings au lieu de maintenance !</p>";
        echo "<p class='warn'>→ Remplace par le nouveau maintenance-check.php</p>";
        $allGood = false;
    } else {
        echo "<p class='warn'>⚠️ Version inconnue du middleware</p>";
    }
} else {
    echo "<p class='err'>❌ maintenance-check.php introuvable dans /includes/</p>";
    $allGood = false;
}

// 5. Index.php
echo "<h2>5. index.php</h2>";
$idx = file_get_contents($root . '/index.php');
if (strpos($idx, 'maintenance-check.php') !== false) {
    echo "<p class='ok'>✅ index.php inclut maintenance-check.php</p>";
} else {
    echo "<p class='err'>❌ index.php N'INCLUT PAS maintenance-check.php</p>";
    $allGood = false;
}

// 6. API admin
echo "<h2>6. API admin</h2>";
$api = $root . '/admin/modules/maintenance/api/save.php';
if (file_exists($api)) {
    echo "<p class='ok'>✅ API save.php existe</p>";
} else {
    echo "<p class='err'>❌ API save.php manquant dans /admin/modules/maintenance/api/</p>";
    $allGood = false;
}

// 7. IP
echo "<h2>7. Votre IP</h2>";
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
echo "<p>IP: <strong>$ip</strong></p>";
if (isset($data) && strpos($data['allowed_ips'] ?? '', $ip) !== false) {
    echo "<p class='ok'>✅ Votre IP est dans la whitelist</p>";
} else {
    echo "<p class='warn'>⚠️ Votre IP n'est PAS dans la whitelist — vous serez bloqué si maintenance active</p>";
}

// Résumé
echo "<hr><h2>📊 Résultat</h2>";
if ($allGood) {
    echo "<p class='ok' style='font-size:18px'>✅ TOUT EST BON — Le système de maintenance est opérationnel !</p>";
    if (isset($data) && $data['is_active']) {
        echo "<p class='warn'>⚠️ La maintenance est actuellement ACTIVE — testez en navigation privée pour vérifier le blocage.</p>";
    }
} else {
    echo "<p class='err' style='font-size:18px'>❌ DES PROBLÈMES DÉTECTÉS — Corrigez les erreurs ci-dessus.</p>";
}

echo "<hr><p class='err'>⚠️ SUPPRIMER CE FICHIER APRÈS USAGE</p>";
echo "</body></html>";