<?php
/**
 * ========================================
 * FIX.PHP - CORRECTION AUTOMATIQUE
 * ========================================
 * Corrige tous les problèmes détectés
 * ========================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Correction</title>';
echo '<style>
body { font-family: system-ui, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #f8fafc; }
h1 { color: #1e3a5f; }
h2 { color: #334155; margin-top: 30px; }
.ok { color: #059669; }
.error { color: #dc2626; }
.box { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 10px 0; }
.code { background: #1e293b; color: #38bdf8; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 12px; overflow-x: auto; }
.btn { display: inline-block; padding: 12px 25px; background: #1e3a5f; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px 10px 0; }
.btn:hover { background: #2d4a6f; }
.btn-danger { background: #dc2626; }
</style></head><body>';

echo '<h1>🔧 Correction automatique</h1>';

// Config
require_once __DIR__ . '/config/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$action = $_GET['action'] ?? '';

// ========================================
// ACTIONS
// ========================================

if ($action === 'create_site_design') {
    echo '<h2>Création table site_design</h2><div class="box">';
    
    $sql = "CREATE TABLE IF NOT EXISTS site_design (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NULL,
        header_html LONGTEXT,
        footer_html LONGTEXT,
        global_css LONGTEXT,
        primary_color VARCHAR(20) DEFAULT '#1e3a5f',
        secondary_color VARCHAR(20) DEFAULT '#2d4a6f',
        font_family VARCHAR(100) DEFAULT 'Inter',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<p class='ok'>✅ Table créée</p>";
    
    // Insérer un design par défaut
    $header = '<header style="background: #1e3a5f; padding: 15px 0;">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
        <a href="/" style="color: white; text-decoration: none; font-size: 1.4rem; font-weight: 700;">Eduardo De Sul</a>
        <nav style="display: flex; gap: 25px;">
            <a href="/" style="color: white; text-decoration: none;">Accueil</a>
            <a href="/a-propos" style="color: white; text-decoration: none;">À propos</a>
            <a href="/estimation" style="color: white; text-decoration: none;">Estimation</a>
            <a href="/contact" style="color: white; text-decoration: none;">Contact</a>
        </nav>
    </div>
</header>';

    $footer = '<footer style="background: #1e293b; color: white; padding: 40px 20px; text-align: center;">
    <p style="opacity: 0.7;">© ' . date('Y') . ' Eduardo De Sul - Conseiller immobilier indépendant</p>
</footer>';

    $stmt = $pdo->prepare("INSERT INTO site_design (header_html, footer_html, primary_color, secondary_color, font_family) VALUES (?, ?, '#1e3a5f', '#2d4a6f', 'Inter')");
    $stmt->execute([$header, $footer]);
    
    echo "<p class='ok'>✅ Design par défaut inséré</p>";
    echo '</div>';
}

if ($action === 'fix_slugs') {
    echo '<h2>Correction des slugs</h2><div class="box">';
    
    // Corriger index.php → accueil pour ID 11
    $pdo->exec("UPDATE pages SET slug = 'accueil-old' WHERE id = 11");
    echo "<p class='ok'>✅ Page ID:11 slug changé en 'accueil-old'</p>";
    
    // Supprimer ou renommer la page 65 qui a déjà le slug accueil
    $pdo->exec("UPDATE pages SET slug = 'accueil' WHERE id = 11");
    $pdo->exec("UPDATE pages SET slug = 'accueil-duplicate' WHERE id = 65 AND slug = 'accueil'");
    echo "<p class='ok'>✅ Slugs corrigés</p>";
    
    echo '</div>';
}

if ($action === 'fix_content') {
    echo '<h2>Nettoyage du contenu PHP</h2><div class="box">';
    
    // Vider les pages qui contiennent du PHP
    $stmt = $pdo->query("SELECT id, title FROM pages WHERE content LIKE '%<?php%' OR content LIKE '%&lt;?php%'");
    $pages = $stmt->fetchAll();
    
    foreach ($pages as $p) {
        $pdo->exec("UPDATE pages SET content = '' WHERE id = " . $p['id']);
        echo "<p class='ok'>✅ Page '{$p['title']}' (ID:{$p['id']}) - contenu vidé</p>";
    }
    
    if (empty($pages)) {
        echo "<p>Aucune page avec contenu PHP trouvée</p>";
    }
    
    echo '</div>';
}

if ($action === 'fix_htaccess') {
    echo '<h2>Correction .htaccess</h2><div class="box">';
    
    $htaccess = '# ============================================
# ROUTING PAGES DYNAMIQUES
# ============================================

RewriteEngine On
RewriteBase /

# Ne pas traiter les fichiers/dossiers existants
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Exclure les dossiers système
RewriteCond %{REQUEST_URI} !^/admin [NC]
RewriteCond %{REQUEST_URI} !^/api [NC]
RewriteCond %{REQUEST_URI} !^/config [NC]
RewriteCond %{REQUEST_URI} !^/includes [NC]
RewriteCond %{REQUEST_URI} !^/uploads [NC]
RewriteCond %{REQUEST_URI} !^/assets [NC]

# Router TOUTES les pages vers front/page.php
RewriteRule ^(.+)$ /front/page.php [L,QSA]
';
    
    file_put_contents(__DIR__ . '/.htaccess', $htaccess);
    echo "<p class='ok'>✅ .htaccess mis à jour</p>";
    echo "<p>Nouveau contenu :</p>";
    echo '<div class="code">' . htmlspecialchars($htaccess) . '</div>';
    
    echo '</div>';
}

if ($action === 'fix_all') {
    header('Location: ?action=create_site_design');
    // On ne peut pas tout faire en une fois à cause des redirections
}

// ========================================
// MENU
// ========================================

echo '<h2>Actions disponibles</h2>';
echo '<div class="box">';
echo '<a href="?action=create_site_design" class="btn">1. Créer table site_design</a>';
echo '<a href="?action=fix_slugs" class="btn">2. Corriger les slugs</a>';
echo '<a href="?action=fix_content" class="btn">3. Vider contenu PHP</a>';
echo '<a href="?action=fix_htaccess" class="btn">4. Corriger .htaccess</a>';
echo '</div>';

// ========================================
// STATUS ACTUEL
// ========================================

echo '<h2>Status actuel</h2>';
echo '<div class="box">';

// Table site_design
$tables = $pdo->query("SHOW TABLES LIKE 'site_design'")->fetchAll();
if (count($tables) > 0) {
    echo "<p class='ok'>✅ Table site_design existe</p>";
} else {
    echo "<p class='error'>❌ Table site_design manquante</p>";
}

// Pages avec PHP
$phpPages = $pdo->query("SELECT COUNT(*) as c FROM pages WHERE content LIKE '%<?php%' OR content LIKE '%&lt;?php%'")->fetch();
if ($phpPages['c'] > 0) {
    echo "<p class='error'>❌ {$phpPages['c']} pages contiennent du PHP</p>";
} else {
    echo "<p class='ok'>✅ Aucune page avec PHP</p>";
}

// Slug index.php
$badSlug = $pdo->query("SELECT COUNT(*) as c FROM pages WHERE slug LIKE '%.php%'")->fetch();
if ($badSlug['c'] > 0) {
    echo "<p class='error'>❌ {$badSlug['c']} pages ont un slug avec .php</p>";
} else {
    echo "<p class='ok'>✅ Slugs OK</p>";
}

// .htaccess
$htaccess = file_get_contents(__DIR__ . '/.htaccess');
if (strpos($htaccess, 'front/page.php') !== false) {
    echo "<p class='ok'>✅ .htaccess route vers page.php</p>";
} else {
    echo "<p class='error'>❌ .htaccess ne route pas vers page.php</p>";
}

echo '</div>';

echo '<p style="margin-top: 30px;"><a href="/diagnostic.php" class="btn">← Retour au diagnostic</a></p>';
echo '<p style="margin-top: 20px; color: #64748b;"><strong>⚠️ Supprimez fix.php après utilisation</strong></p>';

echo '</body></html>';