<?php
require_once __DIR__ . '/includes/init.php';

require_once __DIR__ . '/core/ModuleRegistry.php';
require_once __DIR__ . '/core/Migrator.php';

$registry = new ModuleRegistry(__DIR__ . '/modules');
$migrator = new Migrator($pdo);

$modules = $registry->listModules();

echo "<pre>";
foreach ($modules as $m) {
    if (empty($m['sql_files'])) continue;

    // Prefix par module pour que ce soit lisible dans la table
    $res = $migrator->applyMany($m['sql_files'], 'module:' . $m['name'] . ':');
    foreach ($res as $r) {
        echo ($r['ok'] ? '✅ ' : '❌ ') . $r['message'] . "\n";
    }
}
echo "</pre>";