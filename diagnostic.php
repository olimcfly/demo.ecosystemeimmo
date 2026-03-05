<?php
/**
 * ========================================
 * DIAGNOSTIC.PHP - DÉBUGAGE COMPLET
 * ========================================
 * Upload ce fichier à la racine et accède à :
 * https://eduardo-desul-immobilier.fr/diagnostic.php
 * ========================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Diagnostic</title>';
echo '<style>
body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 40px auto; padding: 20px; background: #f8fafc; }
h1 { color: #1e3a5f; border-bottom: 3px solid #1e3a5f; padding-bottom: 10px; }
h2 { color: #334155; margin-top: 30px; background: #e2e8f0; padding: 10px 15px; border-radius: 8px; }
.ok { color: #059669; font-weight: bold; }
.error { color: #dc2626; font-weight: bold; }
.warn { color: #d97706; font-weight: bold; }
.box { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 10px 0; }
.code { background: #1e293b; color: #38bdf8; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto; white-space: pre-wrap; word-break: break-all; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #e2e8f0; padding: 10px; text-align: left; }
th { background: #f1f5f9; }
.truncate { max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style></head><body>';

echo '<h1>🔍 Diagnostic du système de pages</h1>';
echo '<p>Date : ' . date('Y-m-d H:i:s') . '</p>';

// ========================================
// 1. CONFIG
// ========================================
echo '<h2>1. Configuration</h2>';
echo '<div class="box">';

$configPaths = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/config/database.php',
    $_SERVER['DOCUMENT_ROOT'] . '/config/config.php'
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        echo "<p class='ok'>✅ Config trouvée : $path</p>";
        require_once $path;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    echo "<p class='error'>❌ Aucun fichier de config trouvé !</p>";
    echo "<p>Chemins testés :</p><ul>";
    foreach ($configPaths as $p) echo "<li>$p</li>";
    echo "</ul>";
}

// Vérifier les constantes
$constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
echo '<p><strong>Constantes définies :</strong></p><ul>';
foreach ($constants as $c) {
    if (defined($c)) {
        $val = $c === 'DB_PASS' ? '****' : constant($c);
        echo "<li class='ok'>✅ $c = $val</li>";
    } else {
        echo "<li class='error'>❌ $c non définie</li>";
    }
}
echo '</ul></div>';

// ========================================
// 2. CONNEXION BDD
// ========================================
echo '<h2>2. Connexion Base de Données</h2>';
echo '<div class="box">';

$pdo = null;
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    echo "<p class='ok'>✅ Connexion BDD réussie !</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur connexion : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

if (!$pdo) {
    echo '<p class="error">Impossible de continuer sans connexion BDD.</p></body></html>';
    exit;
}

// ========================================
// 3. TABLE PAGES
// ========================================
echo '<h2>3. Table `pages`</h2>';
echo '<div class="box">';

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'pages'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p class='ok'>✅ Table `pages` existe</p>";
        
        // Structure
        echo '<p><strong>Structure :</strong></p>';
        $cols = $pdo->query("DESCRIBE pages")->fetchAll();
        echo '<table><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Key</th></tr>';
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo '</table>';
        
        // Nombre de pages
        $count = $pdo->query("SELECT COUNT(*) as c FROM pages")->fetch()['c'];
        echo "<p><strong>Nombre de pages :</strong> $count</p>";
        
    } else {
        echo "<p class='error'>❌ Table `pages` n'existe pas !</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ========================================
// 4. LISTE DES PAGES
// ========================================
echo '<h2>4. Liste des pages</h2>';
echo '<div class="box">';

try {
    $pages = $pdo->query("SELECT id, title, slug, status, LENGTH(content) as content_length, LEFT(content, 100) as content_preview FROM pages ORDER BY id")->fetchAll();
    
    if (count($pages) > 0) {
        echo '<table><tr><th>ID</th><th>Titre</th><th>Slug</th><th>Status</th><th>Contenu (octets)</th><th>Aperçu contenu</th></tr>';
        foreach ($pages as $p) {
            $statusClass = $p['status'] === 'published' ? 'ok' : 'warn';
            $preview = htmlspecialchars(substr($p['content_preview'], 0, 80));
            $hasPhp = strpos($p['content_preview'], '<?php') !== false ? "<span class='error'>[CONTIENT PHP!]</span>" : '';
            echo "<tr>
                <td>{$p['id']}</td>
                <td>{$p['title']}</td>
                <td><code>{$p['slug']}</code></td>
                <td class='$statusClass'>{$p['status']}</td>
                <td>{$p['content_length']}</td>
                <td class='truncate'>{$hasPhp} {$preview}...</td>
            </tr>";
        }
        echo '</table>';
    } else {
        echo "<p class='warn'>⚠️ Aucune page dans la table</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ========================================
// 5. TABLE SITE_DESIGN
// ========================================
echo '<h2>5. Table `site_design`</h2>';
echo '<div class="box">';

try {
    $tables = $pdo->query("SHOW TABLES LIKE 'site_design'")->fetchAll();
    if (count($tables) > 0) {
        echo "<p class='ok'>✅ Table `site_design` existe</p>";
        
        $design = $pdo->query("SELECT *, LENGTH(header_html) as header_len, LENGTH(footer_html) as footer_len FROM site_design LIMIT 1")->fetch();
        if ($design) {
            echo "<p><strong>Header HTML :</strong> {$design['header_len']} octets</p>";
            echo "<p><strong>Footer HTML :</strong> {$design['footer_len']} octets</p>";
            echo "<p><strong>Primary color :</strong> " . ($design['primary_color'] ?? 'non défini') . "</p>";
            echo "<p><strong>Font family :</strong> " . ($design['font_family'] ?? 'non défini') . "</p>";
        } else {
            echo "<p class='warn'>⚠️ Table vide - pas de design configuré</p>";
        }
    } else {
        echo "<p class='warn'>⚠️ Table `site_design` n'existe pas</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ========================================
// 6. FICHIERS FRONT
// ========================================
echo '<h2>6. Fichiers Front</h2>';
echo '<div class="box">';

$files = [
    '/index.php' => __DIR__ . '/index.php',
    '/front/page.php' => __DIR__ . '/front/page.php',
    '/public/page.php' => __DIR__ . '/public/page.php',
    '/front/index.php' => __DIR__ . '/front/index.php',
    '/front/.htaccess' => __DIR__ . '/front/.htaccess',
    '/.htaccess' => __DIR__ . '/.htaccess',
];

echo '<table><tr><th>Fichier</th><th>Existe</th><th>Taille</th><th>Modifié</th></tr>';
foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $modified = date('Y-m-d H:i', filemtime($path));
        echo "<tr><td>$name</td><td class='ok'>✅ Oui</td><td>$size octets</td><td>$modified</td></tr>";
    } else {
        echo "<tr><td>$name</td><td class='error'>❌ Non</td><td>-</td><td>-</td></tr>";
    }
}
echo '</table>';
echo '</div>';

// ========================================
// 7. CONTENU .HTACCESS
// ========================================
echo '<h2>7. Contenu .htaccess (racine)</h2>';
echo '<div class="box">';

$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo '<div class="code">' . htmlspecialchars(file_get_contents($htaccess)) . '</div>';
} else {
    echo "<p class='error'>❌ Fichier .htaccess non trouvé à la racine</p>";
}
echo '</div>';

// ========================================
// 8. TEST PAGE ACCUEIL
// ========================================
echo '<h2>8. Test chargement page Accueil</h2>';
echo '<div class="box">';

try {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug IN ('accueil', 'home', 'index', 'index.php', '') ORDER BY id LIMIT 1");
    $stmt->execute();
    $accueil = $stmt->fetch();
    
    if ($accueil) {
        echo "<p class='ok'>✅ Page accueil trouvée (ID: {$accueil['id']}, slug: {$accueil['slug']})</p>";
        echo "<p><strong>Status :</strong> {$accueil['status']}</p>";
        echo "<p><strong>Taille contenu :</strong> " . strlen($accueil['content']) . " octets</p>";
        
        // Vérifier si c'est du PHP
        if (strpos($accueil['content'], '<?php') !== false) {
            echo "<p class='error'>❌ PROBLÈME : Le contenu contient du code PHP au lieu de HTML !</p>";
            echo "<p>Aperçu :</p>";
            echo '<div class="code">' . htmlspecialchars(substr($accueil['content'], 0, 500)) . '...</div>';
            echo "<p class='warn'>👉 Solution : Videz ce contenu et régénérez avec l'IA du Builder</p>";
        } else if (empty($accueil['content'])) {
            echo "<p class='warn'>⚠️ Le contenu est vide</p>";
        } else {
            echo "<p class='ok'>✅ Le contenu semble être du HTML valide</p>";
            echo "<p>Aperçu :</p>";
            echo '<div class="code">' . htmlspecialchars(substr($accueil['content'], 0, 500)) . '...</div>';
        }
    } else {
        echo "<p class='error'>❌ Aucune page accueil trouvée !</p>";
        echo "<p>Slugs testés : 'accueil', 'home', 'index', 'index.php', ''</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo '</div>';

// ========================================
// 9. TEST URL ROUTING
// ========================================
echo '<h2>9. Test URL actuelle</h2>';
echo '<div class="box">';

echo '<table>';
echo '<tr><th>Variable</th><th>Valeur</th></tr>';
echo '<tr><td>REQUEST_URI</td><td>' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</td></tr>';
echo '<tr><td>SCRIPT_NAME</td><td>' . htmlspecialchars($_SERVER['SCRIPT_NAME']) . '</td></tr>';
echo '<tr><td>DOCUMENT_ROOT</td><td>' . htmlspecialchars($_SERVER['DOCUMENT_ROOT']) . '</td></tr>';
echo '<tr><td>__DIR__</td><td>' . htmlspecialchars(__DIR__) . '</td></tr>';
echo '<tr><td>$_GET</td><td>' . htmlspecialchars(print_r($_GET, true)) . '</td></tr>';
echo '</table>';
echo '</div>';

// ========================================
// 10. RECOMMANDATIONS
// ========================================
echo '<h2>10. 🔧 Actions recommandées</h2>';
echo '<div class="box">';

$recommendations = [];

// Vérifier contenu PHP dans pages
try {
    $phpPages = $pdo->query("SELECT id, title, slug FROM pages WHERE content LIKE '%<?php%'")->fetchAll();
    if (count($phpPages) > 0) {
        $recommendations[] = [
            'type' => 'error',
            'text' => 'Pages avec contenu PHP (à corriger) :',
            'sql' => "UPDATE pages SET content = '' WHERE id IN (" . implode(',', array_column($phpPages, 'id')) . ");"
        ];
    }
} catch (Exception $e) {}

// Vérifier slugs problématiques
try {
    $badSlugs = $pdo->query("SELECT id, title, slug FROM pages WHERE slug LIKE '%.php%' OR slug LIKE '%/%/%'")->fetchAll();
    if (count($badSlugs) > 0) {
        $recommendations[] = [
            'type' => 'warn',
            'text' => 'Pages avec slugs problématiques (contiennent .php ou trop de /) :'
        ];
    }
} catch (Exception $e) {}

if (empty($recommendations)) {
    echo "<p class='ok'>✅ Aucun problème majeur détecté dans la configuration</p>";
} else {
    echo '<ul>';
    foreach ($recommendations as $r) {
        echo "<li class='{$r['type']}'>{$r['text']}";
        if (isset($r['sql'])) {
            echo "<br><code>{$r['sql']}</code>";
        }
        echo "</li>";
    }
    echo '</ul>';
}

echo '<h3>SQL pour corriger les pages avec PHP :</h3>';
echo '<div class="code">-- Vider le contenu PHP des pages
UPDATE pages SET content = \'\' WHERE content LIKE \'%&lt;?php%\';

-- Corriger le slug de la page accueil
UPDATE pages SET slug = \'accueil\' WHERE slug = \'index.php\' OR slug LIKE \'%index%\';

-- Vérifier
SELECT id, title, slug, status, LENGTH(content) as len FROM pages;</div>';

echo '</div>';

echo '<p style="margin-top: 40px; text-align: center; color: #64748b;">
    <strong>⚠️ Supprimez ce fichier après utilisation !</strong><br>
    Il expose des informations sensibles.
</p>';

echo '</body></html>';