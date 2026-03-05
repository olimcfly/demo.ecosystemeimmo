<?php
/**
 * DIAGNOSTIC DU BUILDER
 * Placer dans /admin/modules/pages/diagnostic-builder.php
 * Accéder via: /admin/modules/pages/diagnostic-builder.php
 */

echo "<h1>🔍 Diagnostic Builder</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warn{color:orange;} pre{background:#f5f5f5;padding:15px;border-radius:8px;}</style>";

// 1. Vérifier que le dossier builder existe
$builderPath = __DIR__ . '/../builder';
$builderIndex = $builderPath . '/index.php';

echo "<h2>1. Vérification du dossier Builder</h2>";

if (is_dir($builderPath)) {
    echo "<p class='ok'>✅ Dossier /admin/modules/builder/ existe</p>";
} else {
    echo "<p class='error'>❌ Dossier /admin/modules/builder/ N'EXISTE PAS</p>";
    echo "<p>Chemin vérifié: " . realpath(__DIR__) . "/../builder</p>";
}

if (file_exists($builderIndex)) {
    echo "<p class='ok'>✅ Fichier /admin/modules/builder/index.php existe</p>";
} else {
    echo "<p class='error'>❌ Fichier /admin/modules/builder/index.php N'EXISTE PAS</p>";
}

// 2. Lister le contenu du dossier builder
echo "<h2>2. Contenu du dossier Builder</h2>";
if (is_dir($builderPath)) {
    $files = scandir($builderPath);
    echo "<pre>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $builderPath . '/' . $file;
            $type = is_dir($fullPath) ? '📁' : '📄';
            echo "$type $file\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p class='error'>Impossible de lister le contenu</p>";
}

// 3. Tester la création d'une page
echo "<h2>3. Test de création de page</h2>";

require_once __DIR__ . '/../../../config/database.php';

$db = $pdo ?? $db ?? null;
if ($db) {
    echo "<p class='ok'>✅ Connexion DB OK</p>";
    
    // Dernière page créée
    $stmt = $db->query("SELECT id, title, slug, created_at FROM pages ORDER BY id DESC LIMIT 3");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Dernières pages créées:</h3>";
    echo "<pre>";
    foreach ($pages as $p) {
        echo "ID: {$p['id']} | Slug: {$p['slug']} | Titre: {$p['title']} | Créée: {$p['created_at']}\n";
    }
    echo "</pre>";
} else {
    echo "<p class='error'>❌ Connexion DB échouée</p>";
}

// 4. URLs de test
echo "<h2>4. URLs à tester</h2>";
echo "<ul>";
echo "<li><a href='/admin/modules/builder/index.php' target='_blank'>/admin/modules/builder/index.php</a></li>";
echo "<li><a href='/admin/modules/builder/index.php?id=9' target='_blank'>/admin/modules/builder/index.php?id=9</a> (page Accueil)</li>";
echo "</ul>";

// 5. Vérifier les logs
echo "<h2>5. Dernières erreurs PHP</h2>";
$logFile = __DIR__ . '/../../../logs/php_errors.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    echo "<pre style='max-height:200px;overflow:auto;'>";
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p class='warn'>⚠️ Fichier de log non trouvé: $logFile</p>";
}

// 6. Test formulaire
echo "<h2>6. Test de création rapide</h2>";
echo "<form method='POST' action='/admin/modules/pages/create.php' style='background:#f5f5f5;padding:20px;border-radius:8px;'>";
echo "<p><label>Titre: <input type='text' name='title' value='Test Page " . date('His') . "' required></label></p>";
echo "<p><label>Slug (optionnel): <input type='text' name='slug' placeholder='auto-généré'></label></p>";
echo "<p><label>Template: <select name='template'><option value='default'>Standard</option><option value='landing'>Landing</option></select></label></p>";
echo "<p><button type='submit' style='background:#6366f1;color:white;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;'>Créer et aller au Builder</button></p>";
echo "</form>";

echo "<hr><p><a href='/admin/modules/pages/index.php'>← Retour à la liste des pages</a></p>";