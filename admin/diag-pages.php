<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

echo "<h1>DIAG PAGES</h1><pre>";

// 1. Trouver database.php
echo "== 1. CHEMIN ==\n";
echo "Fichier: " . __FILE__ . "\n";
echo "Dir: " . __DIR__ . "\n\n";

$paths = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../../config/database.php',
    __DIR__ . '/../includes/Database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config/database.php',
];

$dbFile = null;
foreach ($paths as $p) {
    $exists = file_exists($p) ? 'OUI' : 'non';
    echo "  $p => $exists\n";
    if (file_exists($p) && !$dbFile) $dbFile = $p;
}
echo "\n";

// 2. Charger DB
echo "== 2. CONNEXION DB ==\n";
$db = null;
if ($dbFile) {
    echo "Chargement: $dbFile\n";
    try {
        require_once $dbFile;
        $db = $pdo ?? $db ?? null;
        if ($db) {
            echo "OK - connecte\n\n";
        } else {
            echo "Fichier charge mais pas de variable \$pdo ou \$db\n";
            echo "Variables: " . implode(', ', array_keys(get_defined_vars())) . "\n\n";
        }
    } catch (Exception $e) {
        echo "ERREUR: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "AUCUN fichier database trouve !\n\n";
}

if (!$db) {
    echo "== FIN - PAS DE DB ==\n</pre>";
    exit;
}

// 3. Tables
echo "== 3. TABLES ==\n";
try {
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "  - $t\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Colonnes pages
echo "== 4. COLONNES 'pages' ==\n";
try {
    $cols = $db->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} ({$c['Type']})\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Test SELECT
echo "== 5. SELECT pages LIMIT 3 ==\n";
try {
    $rows = $db->query("SELECT * FROM pages LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "OK - " . count($rows) . " lignes\n";
    foreach ($rows as $r) {
        echo "  ID:{$r['id']} | " . substr($r['title'] ?? '?', 0, 40) . "\n";
    }
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Fichier pages/index.php
echo "== 6. FICHIER pages/index.php ==\n";
$f = __DIR__ . '/modules/pages/index.php';
if (file_exists($f)) {
    echo "Existe: OUI\n";
    echo "Taille: " . filesize($f) . "\n";
    echo "Date: " . date('d/m/Y H:i', filemtime($f)) . "\n";
    $c = file_get_contents($f);
    echo "hasBuilderTemplates: " . (strpos($c, 'hasBuilderTemplates') !== false ? 'OUI (fix OK)' : 'NON (ancien!)') . "\n";
    echo "builder-pages refs: " . substr_count($c, 'builder-pages') . "\n";
} else {
    echo "NON TROUVE: $f\n";
}
echo "\n== FIN ==</pre>";
?>