<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debug Step 2</h1><pre>";

$configPath = __DIR__ . '/../../../config/config.php';
$dbPath = __DIR__ . '/../../../config/database.php';

echo "=== STEP 1 : Charger config.php ===\n";
$constsBefore = get_defined_constants(true)['user'] ?? [];
try {
    require_once $configPath;
    echo "config.php chargé OK\n";
    $constsAfter = get_defined_constants(true)['user'] ?? [];
    $newConsts = array_diff_key($constsAfter, $constsBefore);
    if (!empty($newConsts)) {
        echo "Constantes :\n";
        foreach ($newConsts as $k => $v) {
            $display = (stripos($k, 'pass') !== false || stripos($k, 'secret') !== false) 
                       ? '***MASQUÉ***' : $v;
            echo "  {$k} = {$display}\n";
        }
    }
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}

echo "\n=== STEP 2 : Charger database.php ===\n";
$constsBefore2 = get_defined_constants(true)['user'] ?? [];
try {
    require_once $dbPath;
    echo "database.php chargé OK\n";
    
    $dbVarNames = ['pdo', 'db', 'conn', 'connection', 'database', 'mysqli', 'dbh', 'link'];
    echo "\nRecherche variable connexion DB :\n";
    foreach ($dbVarNames as $name) {
        if (isset($$name)) {
            echo "  \${$name} TROUVÉ → " . (is_object($$name) ? get_class($$name) : gettype($$name)) . "\n";
        }
    }

    $constsAfter2 = get_defined_constants(true)['user'] ?? [];
    $newConsts2 = array_diff_key($constsAfter2, $constsBefore2);
    if (!empty($newConsts2)) {
        echo "\nNouvelles constantes après database.php :\n";
        foreach ($newConsts2 as $k => $v) {
            $display = (stripos($k, 'pass') !== false || stripos($k, 'secret') !== false) 
                       ? '***MASQUÉ***' : $v;
            echo "  {$k} = {$display}\n";
        }
    }

    echo "\nToutes les variables disponibles :\n";
    foreach (get_defined_vars() as $k => $v) {
        if (in_array($k, ['configPath', 'dbPath', 'constsBefore', 'constsAfter', 'constsBefore2',
                          'constsAfter2', 'newConsts', 'newConsts2', 'dbVarNames', 'name', 
                          'display', 'e', 'k', 'v'])) continue;
        $type = is_object($v) ? get_class($v) : gettype($v);
        echo "  \${$k} → {$type}\n";
    }

} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}

echo "\n=== STEP 3 : Aperçu database.php (sans secrets) ===\n";
$content = file_get_contents($dbPath);
$content = preg_replace("/'([^']{3,})'/", "'***'", $content);
echo htmlspecialchars(substr($content, 0, 2000));

echo "\n\n=== STEP 4 : Structure admin/ ===\n";
$adminDir = __DIR__ . '/../../';
foreach (scandir($adminDir) as $f) {
    if ($f[0] !== '.') {
        $type = is_dir($adminDir . $f) ? '[DIR]' : '[FILE]';
        echo "  {$type} {$f}\n";
    }
}

echo "\n=== FIN ===</pre>";