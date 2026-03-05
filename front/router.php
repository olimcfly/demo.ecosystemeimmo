<?php
/**
 * ========================================
 * ROUTEUR MULTI-DOMAINES
 * ========================================
 * 
 * Fichier: /front/router.php
 * 
 * Détecte le domaine/sous-domaine et charge
 * le bon site + la bonne page
 * 
 * Fonctionne comme Systeme.io / GHL
 * 
 * ========================================
 */

// Éviter les erreurs d'affichage en production
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Charger la configuration
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}

if (!file_exists($configPath)) {
    http_response_code(500);
    die('Configuration non trouvée');
}

require_once $configPath;

// Connexion BDD
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Erreur de connexion à la base de données');
}

// ========================================
// DÉTECTION DU DOMAINE
// ========================================

$currentHost = strtolower($_SERVER['HTTP_HOST']);
$currentDomain = preg_replace('/^www\./', '', $currentHost); // Enlever www.

// Domaine principal de la plateforme (à personnaliser)
define('MAIN_DOMAIN', 'ecosysteme-immo.fr'); // Ton domaine principal
define('ADMIN_SUBDOMAIN', 'admin'); // Sous-domaine admin

$website = null;
$isMainDomain = false;
$isSubdomain = false;

// ========================================
// LOGIQUE DE ROUTAGE
// ========================================

// 1. Vérifier si c'est le domaine admin
if ($currentDomain === ADMIN_SUBDOMAIN . '.' . MAIN_DOMAIN) {
    // Rediriger vers l'admin
    header('Location: /admin/');
    exit;
}

// 2. Vérifier si c'est un sous-domaine de la plateforme (client.ecosysteme-immo.fr)
if (preg_match('/^([a-z0-9-]+)\.' . preg_quote(MAIN_DOMAIN, '/') . '$/i', $currentDomain, $matches)) {
    $slug = $matches[1];
    
    // Exclure certains sous-domaines réservés
    $reserved = ['www', 'admin', 'api', 'mail', 'ftp', 'cpanel', 'webmail'];
    if (!in_array($slug, $reserved)) {
        $stmt = $pdo->prepare("SELECT * FROM websites WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $website = $stmt->fetch();
        $isSubdomain = true;
    }
}

// 3. Vérifier si c'est un domaine personnalisé (www.client-immobilier.fr)
if (!$website) {
    $stmt = $pdo->prepare("
        SELECT * FROM websites 
        WHERE (domain = ? OR domain = ? OR domain = ?)
        AND status = 'published'
        LIMIT 1
    ");
    // Chercher avec et sans www
    $stmt->execute([
        $currentDomain,
        'www.' . $currentDomain,
        str_replace('www.', '', $currentDomain)
    ]);
    $website = $stmt->fetch();
}

// 4. Vérifier si c'est le domaine principal (page d'accueil plateforme)
if (!$website && ($currentDomain === MAIN_DOMAIN || $currentDomain === 'www.' . MAIN_DOMAIN)) {
    $isMainDomain = true;
    // Charger le site principal ou la landing page plateforme
    $stmt = $pdo->prepare("SELECT * FROM websites WHERE slug = 'main' OR slug = 'accueil' LIMIT 1");
    $stmt->execute();
    $website = $stmt->fetch();
}

// ========================================
// SITE NON TROUVÉ
// ========================================

if (!$website && !$isMainDomain) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Site non trouvé</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 20px;
            }
            .container { max-width: 500px; }
            h1 { font-size: 72px; margin-bottom: 20px; opacity: 0.9; }
            h2 { font-size: 24px; margin-bottom: 16px; }
            p { opacity: 0.8; line-height: 1.6; margin-bottom: 24px; }
            .domain { 
                background: rgba(255,255,255,0.2); 
                padding: 8px 16px; 
                border-radius: 8px; 
                display: inline-block;
                font-family: monospace;
                margin: 16px 0;
            }
            a { 
                color: white; 
                background: rgba(255,255,255,0.2);
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                display: inline-block;
                transition: background 0.2s;
            }
            a:hover { background: rgba(255,255,255,0.3); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🌐</h1>
            <h2>Site non configuré</h2>
            <div class="domain"><?php echo htmlspecialchars($currentHost); ?></div>
            <p>Ce domaine n'est pas encore configuré sur notre plateforme.<br>
            Si vous êtes le propriétaire, vérifiez votre configuration DNS.</p>
            <a href="https://<?php echo MAIN_DOMAIN; ?>">Retour à l'accueil</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ========================================
// DÉMARRER LA SESSION ET STOCKER LE SITE
// ========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['current_website'] = $website;
$_SESSION['current_website_id'] = $website['id'] ?? null;

// ========================================
// RÉCUPÉRER L'URI DEMANDÉE
// ========================================

$requestUri = $_SERVER['REQUEST_URI'];
$uri = parse_url($requestUri, PHP_URL_PATH);
$uri = trim($uri, '/');
$uri = preg_replace('/\.php$/', '', $uri); // Enlever .php

// Nettoyer l'URI
$uri = preg_replace('/[^a-z0-9\-\/]/i', '', $uri);

// ========================================
// ROUTER VERS LA BONNE PAGE
// ========================================

$page = null;

if (empty($uri) || $uri === 'index') {
    // Page d'accueil du site
    $stmt = $pdo->prepare("
        SELECT * FROM pages 
        WHERE website_id = ? 
        AND (slug = 'accueil' OR slug = 'home' OR slug = 'index' OR slug = '')
        AND status = 'published'
        ORDER BY FIELD(slug, 'accueil', 'home', 'index', '') 
        LIMIT 1
    ");
    $stmt->execute([$website['id']]);
    $page = $stmt->fetch();
    
    // Si pas de page d'accueil, prendre la première page publiée
    if (!$page) {
        $stmt = $pdo->prepare("
            SELECT * FROM pages 
            WHERE website_id = ? AND status = 'published'
            ORDER BY created_at ASC
            LIMIT 1
        ");
        $stmt->execute([$website['id']]);
        $page = $stmt->fetch();
    }
} else {
    // Page spécifique par slug
    $stmt = $pdo->prepare("
        SELECT * FROM pages 
        WHERE website_id = ? AND slug = ? AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute([$website['id'], $uri]);
    $page = $stmt->fetch();
}

// ========================================
// PAGE NON TROUVÉE (404)
// ========================================

if (!$page) {
    http_response_code(404);
    
    // Chercher une page 404 personnalisée
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE website_id = ? AND slug = '404' LIMIT 1");
    $stmt->execute([$website['id']]);
    $page404 = $stmt->fetch();
    
    if ($page404) {
        $page = $page404;
    } else {
        // 404 générique
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Page non trouvée - <?php echo htmlspecialchars($website['name']); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: linear-gradient(135deg, <?php echo htmlspecialchars($website['primary_color'] ?? '#6366f1'); ?> 0%, <?php echo htmlspecialchars($website['secondary_color'] ?? '#8b5cf6'); ?> 100%);
                    color: white;
                    text-align: center;
                    padding: 20px;
                }
                .container { max-width: 500px; }
                h1 { font-size: 120px; margin-bottom: 20px; opacity: 0.9; font-weight: 700; }
                h2 { font-size: 24px; margin-bottom: 16px; }
                p { opacity: 0.8; line-height: 1.6; margin-bottom: 24px; }
                a { 
                    color: white; 
                    background: rgba(255,255,255,0.2);
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    display: inline-block;
                    transition: background 0.2s;
                }
                a:hover { background: rgba(255,255,255,0.3); }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>404</h1>
                <h2>Page non trouvée</h2>
                <p>La page que vous recherchez n'existe pas ou a été déplacée.</p>
                <a href="/">Retour à l'accueil</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ========================================
// PRÉPARER LES DONNÉES POUR LE TEMPLATE
// ========================================

// Décoder les données JSON
$heroData = [];
$sectionsData = [];
$ctaData = [];
$sidebarData = [];

if (!empty($page['hero_data'])) {
    $heroData = json_decode($page['hero_data'], true) ?: [];
}
if (!empty($page['sections_data'])) {
    $sectionsData = json_decode($page['sections_data'], true) ?: [];
}
if (!empty($page['cta_data'])) {
    $ctaData = json_decode($page['cta_data'], true) ?: [];
}
if (!empty($page['sidebar_data'])) {
    $sidebarData = json_decode($page['sidebar_data'], true) ?: [];
}

// Variables globales pour les templates
$GLOBALS['website'] = $website;
$GLOBALS['page'] = $page;
$GLOBALS['hero'] = $heroData;
$GLOBALS['sections'] = $sectionsData;
$GLOBALS['cta'] = $ctaData;
$GLOBALS['sidebar'] = $sidebarData;

// ========================================
// CHARGER LE TEMPLATE
// ========================================

// Déterminer le template à utiliser
$layout = $page['layout'] ?? $page['type'] ?? 'page';
$templatePath = __DIR__ . '/templates/' . $layout . '.php';

// Fallback sur le template par défaut
if (!file_exists($templatePath)) {
    $templatePath = __DIR__ . '/templates/page-template.php';
}

if (!file_exists($templatePath)) {
    $templatePath = __DIR__ . '/templates/default.php';
}

if (file_exists($templatePath)) {
    include $templatePath;
} else {
    // Template inline minimal si aucun template trouvé
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page['seo_title'] ?: $page['title']); ?> - <?php echo htmlspecialchars($website['name']); ?></title>
        <meta name="description" content="<?php echo htmlspecialchars($page['seo_description'] ?? ''); ?>">
        <link href="https://fonts.googleapis.com/css2?family=<?php echo urlencode($website['font_family'] ?? 'Inter'); ?>:wght@400;500;600;700&display=swap" rel="stylesheet">
        <?php if (!empty($website['tracking_code'])): ?>
        <?php echo $website['tracking_code']; ?>
        <?php endif; ?>
        <style>
            :root {
                --primary: <?php echo htmlspecialchars($website['primary_color'] ?? '#3B82F6'); ?>;
                --secondary: <?php echo htmlspecialchars($website['secondary_color'] ?? '#1E40AF'); ?>;
            }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: '<?php echo htmlspecialchars($website['font_family'] ?? 'Inter'); ?>', sans-serif;
                line-height: 1.6;
                color: #1e293b;
            }
            .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            header {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                padding: 20px 0;
            }
            header h1 { font-size: 24px; }
            main { padding: 60px 0; }
            h1, h2, h3 { color: var(--primary); margin-bottom: 16px; }
            <?php if (!empty($page['inline_css'])): ?>
            <?php echo $page['inline_css']; ?>
            <?php endif; ?>
        </style>
    </head>
    <body>
        <header>
            <div class="container">
                <h1><?php echo htmlspecialchars($website['name']); ?></h1>
            </div>
        </header>
        <main>
            <div class="container">
                <h1><?php echo htmlspecialchars($page['title']); ?></h1>
                <?php if (!empty($page['description'])): ?>
                <p><?php echo htmlspecialchars($page['description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($page['content'])): ?>
                <div class="content">
                    <?php echo $page['content']; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </body>
    </html>
    <?php
}