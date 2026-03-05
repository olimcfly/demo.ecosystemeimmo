<?php
/**
 * DIAGNOSTIC Builder Editor — à placer dans :
 * /admin/modules/builder/builder/diag-editor.php
 * 
 * Accès : /admin/modules/builder/builder/diag-editor.php?context=landing&entity_id=69
 * 
 * Supprimez ce fichier après diagnostic !
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#f8f9fa'>";
echo "═══ DIAGNOSTIC EDITOR.PHP ═══\n\n";

// 1. Session
echo "1. Session... ";
if (session_status() === PHP_SESSION_NONE) session_start();
echo (isset($_SESSION['admin_id']) ? "✅ admin_id=" . $_SESSION['admin_id'] : "❌ PAS DE SESSION") . "\n";

// 2. init.php
echo "2. init.php... ";
$initPath = __DIR__ . '/../../includes/init.php';
echo "   Chemin: " . realpath($initPath) . "\n";
if (file_exists($initPath)) {
    try {
        require_once $initPath;
        echo "   ✅ Chargé\n";
    } catch (Throwable $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
} else {
    echo "   ❌ FICHIER INTROUVABLE\n";
    // Essayer d'autres chemins
    $alts = [
        __DIR__ . '/../../../includes/init.php',
        __DIR__ . '/../../../config/config.php',
        __DIR__ . '/../../../../config/config.php',
    ];
    foreach ($alts as $alt) {
        echo "   Essai: $alt → " . (file_exists($alt) ? "EXISTE" : "non") . "\n";
    }
}

// 3. PDO
echo "\n3. Connexion PDO... ";
if (isset($pdo)) {
    echo "✅ \$pdo existe\n";
} elseif (isset($db)) {
    echo "✅ \$db existe (pas \$pdo)\n";
    $pdo = $db;
} else {
    echo "❌ Aucune connexion DB\n";
    // Tenter directement
    echo "   Tentative directe... ";
    $cfgPaths = [
        __DIR__ . '/../../../../config/config.php',
        __DIR__ . '/../../../config/config.php',
        __DIR__ . '/../../config/config.php',
    ];
    foreach ($cfgPaths as $cp) {
        if (file_exists($cp)) {
            echo "\n   Trouvé config: $cp\n";
            try {
                require_once $cp;
                if (defined('DB_HOST')) {
                    $pdo = new PDO(
                        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                        DB_USER, DB_PASS,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    echo "   ✅ Connexion PDO directe OK\n";
                }
            } catch (Throwable $e) {
                echo "   ❌ " . $e->getMessage() . "\n";
            }
            break;
        }
    }
}

// 4. BuilderController
echo "\n4. BuilderController... ";
$bcPath = __DIR__ . '/BuilderController.php';
echo "   Chemin: " . realpath($bcPath) . "\n";
if (file_exists($bcPath)) {
    try {
        require_once $bcPath;
        echo "   ✅ Fichier chargé\n";
        
        if (class_exists('BuilderController')) {
            echo "   ✅ Classe BuilderController trouvée\n";
            
            if (isset($pdo)) {
                try {
                    $builder = new BuilderController($pdo);
                    echo "   ✅ Instanciation OK\n";
                    echo "   CONTEXTS = " . implode(', ', BuilderController::CONTEXTS) . "\n";
                } catch (Throwable $e) {
                    echo "   ❌ Instanciation: " . $e->getMessage() . "\n";
                    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
                }
            }
        } else {
            echo "   ❌ Classe BuilderController NON trouvée\n";
        }
    } catch (Throwable $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   ❌ FICHIER INTROUVABLE\n";
}

// 5. Paramètres GET
echo "\n5. Paramètres... ";
$context  = $_GET['context'] ?? '';
$entityId = (int)($_GET['entity_id'] ?? 0);
echo "context='$context' entity_id=$entityId\n";
if (class_exists('BuilderController')) {
    echo "   Contexte valide: " . (in_array($context, BuilderController::CONTEXTS) ? "✅" : "❌ '$context' pas dans CONTEXTS") . "\n";
}

// 6. Tables requises
echo "\n6. Tables Builder... ";
if (isset($pdo)) {
    $tables = ['builder_layouts', 'builder_templates', 'builder_block_types', 'builder_content', 
               'builder_saved_blocks', 'headers', 'footers', 'pages', 'seo_scores'];
    foreach ($tables as $t) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "\n   ✅ $t ($cnt lignes)";
        } catch (PDOException $e) {
            echo "\n   ❌ $t → " . $e->getMessage();
        }
    }
}

// 7. Fichiers assets référencés
echo "\n\n7. Assets CSS/JS... ";
$assets = [
    '/admin/modules/builder/assets/css/design-clone.css',
];
$docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '/home/mahe6420/public_html';
foreach ($assets as $a) {
    $full = $docRoot . $a;
    echo "\n   " . (file_exists($full) ? "✅" : "❌ MANQUANT") . " $a";
}

// 8. API Anthropic
echo "\n\n8. API Key Claude... ";
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) {
    echo "✅ définie (" . strlen(ANTHROPIC_API_KEY) . " chars)";
} else {
    echo "⚠️ non définie (IA désactivée)";
}

// 9. Test complet editor.php (sans output)
echo "\n\n9. Simulation editor.php chargement...\n";
if (isset($pdo) && class_exists('BuilderController') && $context && $entityId) {
    try {
        $builder     = new BuilderController($pdo);
        $layouts     = $builder->getLayouts($context);
        echo "   ✅ getLayouts → " . count($layouts) . " éléments\n";
        
        $templates   = $builder->getTemplates($context);
        echo "   ✅ getTemplates → " . count($templates) . " éléments\n";
        
        $blocks      = $builder->getBlockTypes($context);
        echo "   ✅ getBlockTypes → " . count($blocks) . " éléments\n";
        
        $content     = $builder->loadContent($context, $entityId);
        echo "   ✅ loadContent → " . ($content ? 'données' : 'null') . "\n";
        
        $saved       = $builder->getSavedBlocks($context);
        echo "   ✅ getSavedBlocks → " . count($saved) . " éléments\n";
        
        $entityTitle = $builder->getEntityTitle($context, $entityId);
        echo "   ✅ getEntityTitle → '$entityTitle'\n";
        
        echo "\n   🎉 TOUT OK — L'editor devrait fonctionner !\n";
        
    } catch (Throwable $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "   ⚠️ Conditions manquantes pour la simulation\n";
    if (!isset($pdo)) echo "   → Pas de PDO\n";
    if (!class_exists('BuilderController')) echo "   → BuilderController absent\n";
    if (!$context) echo "   → context vide (ajoutez ?context=landing&entity_id=69)\n";
    if (!$entityId) echo "   → entity_id vide\n";
}

echo "\n═══ FIN DIAGNOSTIC ═══\n";
echo "</pre>";