<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$_GET['context'] = 'landing';
$_GET['entity_id'] = 69;
$_SESSION['admin_id'] = 1;
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "<pre style='font-family:monospace;background:#1e293b;color:#e2e8f0;padding:20px;border-radius:8px'>";
echo "═══ DIAGNOSTIC BUILDER PRO ═══\n\n";

// 1. Init
echo "1. START\n";
try {
    require_once __DIR__ . '/includes/init.php';
    echo "2. ✅ init.php OK\n";
} catch (Throwable $e) {
    echo "2. ❌ init.php ERREUR: " . $e->getMessage() . "\n"; die("</pre>");
}

// 2. DB
echo "3. PDO: " . (isset($pdo) ? '✅ oui' : (isset($db) ? '✅ oui ($db)' : '❌ non')) . "\n";
if (!isset($pdo) && isset($db)) $pdo = $db;

// 3. Controller
try {
    require_once __DIR__ . '/modules/builder/builder/BuilderController.php';
    echo "4. ✅ BuilderController chargé\n";
} catch (Throwable $e) {
    echo "4. ❌ BuilderController ERREUR: " . $e->getMessage() . "\n"; die("</pre>");
}

// 4. Instanciation
try {
    $builder = new BuilderController($pdo);
    echo "5. ✅ BuilderController instancié\n";
} catch (Throwable $e) {
    echo "5. ❌ Instanciation ERREUR: " . $e->getMessage() . "\n"; die("</pre>");
}

// 5. CONTEXTS
echo "6. CONTEXTS = " . implode(', ', BuilderController::CONTEXTS) . "\n";
echo "7. 'landing' valide: " . (in_array('landing', BuilderController::CONTEXTS) ? '✅' : '❌') . "\n";

// 6. Méthodes
$tests = [
    'getLayouts'      => fn() => $builder->getLayouts('landing'),
    'getTemplates'    => fn() => $builder->getTemplates('landing'),
    'getBlockTypes'   => fn() => $builder->getBlockTypes('landing'),
    'loadContent'     => fn() => $builder->loadContent('landing', 69),
    'getSavedBlocks'  => fn() => $builder->getSavedBlocks('landing'),
    'getEntityTitle'  => fn() => $builder->getEntityTitle('landing', 69),
];

$step = 8;
foreach ($tests as $name => $fn) {
    try {
        $result = $fn();
        $info = is_array($result) ? count($result) . ' éléments' : ($result ?: 'null');
        echo "$step. ✅ $name → $info\n";
    } catch (Throwable $e) {
        echo "$step. ❌ $name ERREUR: " . $e->getMessage() . "\n";
    }
    $step++;
}

// 7. Tables utilisées par editor.php
echo "\n═══ TABLES REQUISES ═══\n\n";
$tablesToCheck = [
    'pages', 'articles', 'secteurs', 'captures',
    'builder_layouts', 'builder_templates', 'builder_block_types',
    'builder_content', 'builder_saved_blocks',
    'site_headers', 'headers', 'builder_pages',
    'site_footers', 'footers',
    'seo_scores',
];

foreach ($tablesToCheck as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "  ✅ $table ($count lignes)\n";
    } catch (PDOException $e) {
        echo "  ❌ $table → N'EXISTE PAS\n";
    }
}

// 8. Constantes
echo "\n═══ CONSTANTES ═══\n\n";
echo "  ANTHROPIC_API_KEY: " . (defined('ANTHROPIC_API_KEY') ? '✅ définie' : '⚠️ non définie') . "\n";

// 9. Page 69 existe ?
echo "\n═══ PAGE #69 ═══\n\n";
try {
    $stmt = $pdo->prepare("SELECT id, title, slug, status FROM pages WHERE id = ?");
    $stmt->execute([69]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($page) {
        echo "  ✅ Trouvée: \"{$page['title']}\" (/{$page['slug']}) [{$page['status']}]\n";
    } else {
        echo "  ❌ Page #69 n'existe pas!\n";
    }
} catch (PDOException $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n═══ FIN DIAGNOSTIC ═══\n";
echo "</pre>";