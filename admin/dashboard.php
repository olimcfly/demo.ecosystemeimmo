<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  DASHBOARD ADMIN — IMMO LOCAL+
 *  /admin/dashboard.php — v8.0
 *  - Sidebar flat (niveau 1 only, no duplicated icons)
 *  - Messagerie intégrée CRM
 *  - Module Manager (Rank Math style)
 *  - Improved dashboard design
 * ══════════════════════════════════════════════════════════════
 */

session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

define('ADMIN_ROUTER', true);
require_once __DIR__ . '/../config/config.php';

// ============================================
// MODULE REGISTRY
// ============================================
$registry = require __DIR__ . '/config/modules-registry.php';
$categories = $registry['__categories'] ?? [];
unset($registry['__categories']);

// ============================================
// ROUTAGE
// ============================================
$originalModule = preg_replace('/[^a-z0-9_-]/i', '', $_GET['page'] ?? $_GET['module'] ?? 'dashboard');
$action = preg_replace('/[^a-z0-9_-]/i', '', $_GET['action'] ?? 'index');

// ─── Aliases (backward compat) ───
$aliases = [
    'blog'=>'articles','properties'=>'biens','scraper-gmb'=>'scraper-gmb',
    'social-strategie'=>'reseaux-sociaux','guides'=>'ressources',
    'design'=>'design-headers','crm-pipeline'=>'crm',
];

// ─── Sub-routing maps ───
$subRoutes = [
    'design-headers'     => ['file'=>'builder/design/index.php', 'extra'=>['type'=>'headers']],
    'design-footers'     => ['file'=>'builder/design/index.php', 'extra'=>['type'=>'footers']],
    'design-edit-header' => ['file'=>'builder/builder/edit-header.php'],
    'design-edit-footer' => ['file'=>'builder/builder/edit-footer.php'],
    'templates'          => ['file'=>'builder/builder/templates.php'],
    'seo'            => ['file'=>'seo/seo/index.php',      'extra'=>['tab'=>'overview']],
    'seo-pages'      => ['file'=>'seo/seo/index.php',      'extra'=>['tab'=>'pages-seo']],
    'seo-articles'   => ['file'=>'content/articles/index.php'],
    'seo-semantic'   => ['file'=>'seo/seo-semantic/index.php'],
    'seo-serp'       => ['file'=>'seo/seo/index.php',      'extra'=>['tab'=>'pages-serp']],
    'seo-indexation' => ['file'=>'seo/seo/index.php',      'extra'=>['tab'=>'pages-indexed']],
    'local-seo'        => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'publications']],
    'local-gmb-posts'  => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'publications']],
    'local-gmb-avis'   => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'avis']],
    'local-gmb-qa'     => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'qa']],
    'local-partners'   => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'partners']],
    'local-guide'      => ['file'=>'seo/local-seo/index.php', 'extra'=>['tab'=>'guide']],
    'gmb'          => ['file'=>'social/gmb/index.php'],
    'gmb-contacts' => ['file'=>'social/gmb/contacts.php'],
    'gmb-sequences'=> ['file'=>'social/gmb/sequences.php'],
    'ads-overview' => ['file'=>'marketing/ads-launch/index.php'],
    'ads-budget'   => ['file'=>'marketing/ads-launch/index.php', 'extra'=>['tab'=>'budget']],
    'google-ads'   => ['file'=>'marketing/ads-launch/index.php', 'extra'=>['tab'=>'google']],
    'facebook-ads' => ['file'=>'social/facebook/index.php',      'extra'=>['tab'=>'ads']],
    'parcours-vendeurs'     => ['file'=>'strategy/launchpad/parcours-vendeurs.php'],
    'parcours-acheteurs'    => ['file'=>'strategy/launchpad/parcours-acheteurs.php'],
    'parcours-conversion'   => ['file'=>'strategy/launchpad/parcours-conversion.php'],
    'parcours-organisation' => ['file'=>'strategy/launchpad/parcours-organisation.php'],
    'parcours-scale'        => ['file'=>'strategy/launchpad/parcours-scale.php'],
    'journal'            => ['file'=>'ai/journal/index.php'],
    'journal-matrice'    => ['file'=>'ai/journal/index.php',          'extra'=>['tab'=>'matrice']],
    'journal-generate'   => ['file'=>'ai/journal/index.php',          'extra'=>['tab'=>'generate']],
    'journal-perf'       => ['file'=>'ai/journal/index.php',          'extra'=>['tab'=>'performance']],
    'articles-journal'   => ['file'=>'content/articles/tabs/journal.php'],
    'local-gmb-journal'  => ['file'=>'seo/local-seo/tabs/journal.php'],
    'facebook-journal'   => ['file'=>'social/facebook/tabs/journal.php'],
    'instagram-journal'  => ['file'=>'social/instagram/tabs/journal.php'],
    'tiktok-journal'     => ['file'=>'social/tiktok/tabs/journal.php'],
    'linkedin-journal'   => ['file'=>'social/linkedin/tabs/journal.php'],
    'emails-journal'     => ['file'=>'marketing/emails/tabs/journal.php'],
    'facebook'  => ['file'=>'social/facebook/index.php'],
    'instagram' => ['file'=>'social/instagram/index.php'],
    'linkedin'  => ['file'=>'social/linkedin/index.php'],
    'tiktok'    => ['file'=>'social/tiktok/index.php'],
    'settings-email'    => ['file'=>'system/settings/index.php', 'extra'=>['subpage'=>'email']],
    'settings-identity' => ['file'=>'system/settings/site-identity.php'],
    'messagerie' => ['file'=>'crm/messagerie/index.php'],
    'email-auto' => ['file'=>'crm/email-auto/index.php'],
    'module-manager' => ['file'=>'system/module-manager/index.php'],
];

$module = isset($aliases[$originalModule]) ? $aliases[$originalModule] : $originalModule;

// ============================================
// DB + STATS
// ============================================
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch(Exception $e) { $pdo = null; }
$db = $pdo;

$stats = ['pages'=>0,'leads'=>0,'contacts'=>0,'biens'=>0,'articles'=>0,'templates'=>0,'captures'=>0,'seo_avg'=>0,'diagnostic'=>null,'parcours'=>[],'headers'=>0,'footers'=>0,'secteurs'=>0,'journal_total'=>0,'journal_published'=>0,'emails_unread'=>0];
if ($pdo) {
    $countQueries = [
        'pages'=>"SELECT COUNT(*) FROM pages",
        'leads'=>"SELECT COUNT(*) FROM leads",
        'contacts'=>"SELECT COUNT(*) FROM contacts",
        'biens'=>"SELECT COUNT(*) FROM biens",
        'templates'=>"SELECT COUNT(*) FROM builder_templates WHERE status='active'",
        'headers'=>"SELECT COUNT(*) FROM headers",
        'footers'=>"SELECT COUNT(*) FROM footers",
        'secteurs'=>"SELECT COUNT(*) FROM secteurs",
    ];
    foreach ($countQueries as $k=>$sql) { try { $stats[$k]=(int)$pdo->query($sql)->fetchColumn(); } catch(Exception $e) {} }
    try { $stats['articles']=(int)$pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn(); } catch(Exception $e) { try { $stats['articles']=(int)$pdo->query("SELECT COUNT(*) FROM blog_articles")->fetchColumn(); } catch(Exception $e) {} }
    try { $stats['captures']=(int)$pdo->query("SELECT COUNT(*) FROM captures")->fetchColumn(); } catch(Exception $e) { try { $stats['captures']=(int)$pdo->query("SELECT COUNT(*) FROM capture_pages")->fetchColumn(); } catch(Exception $e) {} }
    try { $stats['seo_avg']=(int)($pdo->query("SELECT ROUND(AVG(seo_score)) FROM pages WHERE seo_score IS NOT NULL")->fetchColumn()?:0); } catch(Exception $e) {}
    try { $d=$pdo->prepare("SELECT * FROM launchpad_diagnostic WHERE user_id=? ORDER BY created_at DESC LIMIT 1"); $d->execute([$_SESSION['admin_id']]); $stats['diagnostic']=$d->fetch(); } catch(Exception $e) {}
    try { $stmt=$pdo->prepare("SELECT parcours_id, completed_steps FROM parcours_progression WHERE user_id=?"); $stmt->execute([$_SESSION['admin_id']]); while($row=$stmt->fetch()) { $done=json_decode($row['completed_steps']??'[]',true); $stats['parcours'][$row['parcours_id']]=count($done?:[]); } } catch(Exception $e) {}
    try { $stats['journal_total']=(int)$pdo->query("SELECT COUNT(*) FROM editorial_journal WHERE status!='rejected'")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['journal_published']=(int)$pdo->query("SELECT COUNT(*) FROM editorial_journal WHERE status='published'")->fetchColumn(); } catch(Exception $e) {}
    try { $stats['emails_unread']=(int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE is_read=0 AND direction='inbound'")->fetchColumn(); } catch(Exception $e) {}
}

// License
$licenseFile = __DIR__ . '/../config/license.json';
$licenseData = file_exists($licenseFile) ? json_decode(file_get_contents($licenseFile), true) : null;
$hasLicense = ($licenseData !== null && !empty($licenseData['license_key']));
$licenseActive = $hasLicense && ($licenseData['status'] ?? '') === 'active';

// ============================================
// MODULE MANAGER DATA
// ============================================
$allModules = [
    // ─── Site & Contenu ───
    ['id'=>'pages',       'name'=>'Pages CMS',            'desc'=>'Gestion des pages du site',        'category'=>'site',     'icon'=>'fa-file-alt',       'color'=>'#3b82f6', 'table'=>'pages',           'critical'=>true],
    ['id'=>'articles',    'name'=>'Blog / Articles',      'desc'=>'Rédaction SEO avec IA',            'category'=>'site',     'icon'=>'fa-pen-fancy',      'color'=>'#6366f1', 'table'=>'articles',        'critical'=>false],
    ['id'=>'captures',    'name'=>'Pages de Capture',     'desc'=>'Génération de leads',              'category'=>'site',     'icon'=>'fa-magnet',         'color'=>'#ef4444', 'table'=>'captures',        'critical'=>false],
    ['id'=>'secteurs',    'name'=>'Quartiers / Secteurs', 'desc'=>'Pages géolocalisées',              'category'=>'site',     'icon'=>'fa-map-pin',        'color'=>'#14b8a6', 'table'=>'secteurs',        'critical'=>false],
    ['id'=>'builder',     'name'=>'Website Builder',      'desc'=>'Construction de pages drag & drop', 'category'=>'site',    'icon'=>'fa-wand-magic-sparkles', 'color'=>'#8b5cf6', 'table'=>'builder_templates', 'critical'=>false],
    // ─── Design ───
    ['id'=>'templates',   'name'=>'Templates',            'desc'=>'Modèles de design',                'category'=>'design',   'icon'=>'fa-palette',        'color'=>'#ec4899', 'table'=>'builder_templates', 'critical'=>false],
    ['id'=>'headers',     'name'=>'Headers & Navigation', 'desc'=>'Entêtes du site',                  'category'=>'design',   'icon'=>'fa-window-maximize','color'=>'#f59e0b', 'table'=>'headers',         'critical'=>false],
    ['id'=>'footers',     'name'=>'Footers',              'desc'=>'Pieds de page',                    'category'=>'design',   'icon'=>'fa-window-minimize','color'=>'#78716c', 'table'=>'footers',         'critical'=>false],
    ['id'=>'menus',       'name'=>'Menus',                'desc'=>'Navigation du site',               'category'=>'design',   'icon'=>'fa-bars',           'color'=>'#64748b', 'table'=>'menus',           'critical'=>false],
    // ─── CRM ───
    ['id'=>'crm',         'name'=>'CRM Contacts',         'desc'=>'Gestion complète des contacts',    'category'=>'crm',      'icon'=>'fa-address-book',   'color'=>'#f59e0b', 'table'=>'contacts',        'critical'=>true],
    ['id'=>'leads',       'name'=>'Prospects',            'desc'=>'Nouveaux leads entrants',          'category'=>'crm',      'icon'=>'fa-user-plus',      'color'=>'#22c55e', 'table'=>'leads',           'critical'=>true],
    ['id'=>'messagerie',  'name'=>'Messagerie',           'desc'=>'Boîte mail intégrée au CRM',       'category'=>'crm',      'icon'=>'fa-inbox',          'color'=>'#0ea5e9', 'table'=>'crm_emails',      'critical'=>false],
    ['id'=>'email-auto',  'name'=>'Emails Automatiques',  'desc'=>'Séquences, templates & file d\'attente','category'=>'crm', 'icon'=>'fa-paper-plane',    'color'=>'#6366f1', 'table'=>'email_sequences', 'critical'=>false],
    ['id'=>'scoring',     'name'=>'Lead Scoring',         'desc'=>'Score BANT automatique',           'category'=>'crm',      'icon'=>'fa-bullseye',       'color'=>'#a855f7', 'table'=>'lead_scoring',    'critical'=>false],
    ['id'=>'rdv',         'name'=>'Agenda / RDV',         'desc'=>'Gestion des rendez-vous',          'category'=>'crm',      'icon'=>'fa-calendar-check', 'color'=>'#06b6d4', 'table'=>'appointments',    'critical'=>false],
    // ─── Immobilier ───
    ['id'=>'biens',       'name'=>'Annonces Immobilières','desc'=>'Catalogue de biens',               'category'=>'immo',     'icon'=>'fa-building',       'color'=>'#0d9488', 'table'=>'biens',           'critical'=>true],
    ['id'=>'estimation',  'name'=>'Estimations',          'desc'=>'Estimation en ligne',              'category'=>'immo',     'icon'=>'fa-calculator',     'color'=>'#16a34a', 'table'=>'estimations',     'critical'=>false],
    ['id'=>'financement', 'name'=>'Financement',          'desc'=>'Simulation de prêt',               'category'=>'immo',     'icon'=>'fa-hand-holding-usd','color'=>'#ca8a04','table'=>'financing',       'critical'=>false],
    // ─── SEO ───
    ['id'=>'seo',         'name'=>'SEO Global',           'desc'=>'Analyse et optimisation SEO',      'category'=>'seo',      'icon'=>'fa-search',         'color'=>'#0d9488', 'table'=>null,              'critical'=>false],
    ['id'=>'seo-semantic','name'=>'Analyse Sémantique',   'desc'=>'Richesse sémantique des contenus', 'category'=>'seo',      'icon'=>'fa-brain',          'color'=>'#7c3aed', 'table'=>null,              'critical'=>false],
    ['id'=>'local-seo',   'name'=>'SEO Local / GMB',      'desc'=>'Google My Business & local',       'category'=>'seo',      'icon'=>'fa-map-marker-alt', 'color'=>'#dc2626', 'table'=>null,              'critical'=>false],
    // ─── Marketing ───
    ['id'=>'journal',     'name'=>'Stratégie Contenu',    'desc'=>'Journal éditorial multi-canal',    'category'=>'marketing','icon'=>'fa-newspaper',      'color'=>'#e11d48', 'table'=>'editorial_journal','critical'=>false],
    ['id'=>'emails',      'name'=>'Séquences Email',      'desc'=>'Nurturing automatisé',             'category'=>'marketing','icon'=>'fa-envelope-open-text','color'=>'#dc2626','table'=>'email_sequences', 'critical'=>false],
    ['id'=>'reseaux-sociaux','name'=>'Réseaux Sociaux',   'desc'=>'Hub social media',                 'category'=>'marketing','icon'=>'fa-share-nodes',    'color'=>'#3b82f6', 'table'=>null,              'critical'=>false],
    ['id'=>'ads-overview','name'=>'Publicité',            'desc'=>'Google Ads & Facebook Ads',        'category'=>'marketing','icon'=>'fa-bullhorn',       'color'=>'#f97316', 'table'=>null,              'critical'=>false],
    // ─── Prospection B2B ───
    ['id'=>'gmb',         'name'=>'Prospection GMB',      'desc'=>'Scraping & séquences B2B',         'category'=>'b2b',      'icon'=>'fa-crosshairs',     'color'=>'#6366f1', 'table'=>'gmb_prospects',   'critical'=>false],
    // ─── Stratégie ───
    ['id'=>'launchpad',   'name'=>'Launchpad',            'desc'=>'Diagnostic & parcours guidés',     'category'=>'strategy', 'icon'=>'fa-rocket',         'color'=>'#ef4444', 'table'=>null,              'critical'=>false],
    ['id'=>'neuropersona','name'=>'NeuroPersona™',        'desc'=>'Profils d\'acheteurs IA',          'category'=>'strategy', 'icon'=>'fa-user-circle',    'color'=>'#8b5cf6', 'table'=>null,              'critical'=>false],
    // ─── Système ───
    ['id'=>'settings',    'name'=>'Configuration',        'desc'=>'Paramètres du site',               'category'=>'system',   'icon'=>'fa-gear',           'color'=>'#64748b', 'table'=>null,              'critical'=>true],
    ['id'=>'ia',          'name'=>'Assistant IA',          'desc'=>'Chat IA intégré',                  'category'=>'system',   'icon'=>'fa-sparkles',       'color'=>'#06b6d4', 'table'=>null,              'critical'=>false],
    ['id'=>'analytics',   'name'=>'Statistiques',         'desc'=>'Analytics & performances',         'category'=>'system',   'icon'=>'fa-chart-bar',      'color'=>'#0ea5e9', 'table'=>null,              'critical'=>false],
    ['id'=>'module-manager','name'=>'Gestionnaire Modules','desc'=>'Activer/désactiver les modules',  'category'=>'system',   'icon'=>'fa-puzzle-piece',   'color'=>'#a855f7', 'table'=>null,              'critical'=>true],
];

// Load module states from DB or file
$moduleStatesFile = __DIR__ . '/../config/module-states.json';
$moduleStates = file_exists($moduleStatesFile) ? json_decode(file_get_contents($moduleStatesFile), true) : [];
// Check health of each module
foreach ($allModules as &$mod) {
    $mod['enabled'] = $moduleStates[$mod['id']]['enabled'] ?? true;
    $mod['health'] = 'unknown';
    if ($pdo && $mod['table']) {
        try {
            $pdo->query("SELECT 1 FROM {$mod['table']} LIMIT 1");
            $mod['health'] = 'ok';
        } catch(Exception $e) {
            $mod['health'] = 'missing_table';
        }
    } elseif (!$mod['table']) {
        $mod['health'] = 'ok'; // No table needed
    }
}
unset($mod);

// Module categories labels
$moduleCategoryLabels = [
    'site'=>'Site & Contenu', 'design'=>'Design', 'crm'=>'CRM & Contacts',
    'immo'=>'Immobilier', 'seo'=>'SEO & Référencement', 'marketing'=>'Marketing',
    'b2b'=>'Prospection B2B', 'strategy'=>'Stratégie', 'system'=>'Système'
];

// Page title
$page_title = 'Tableau de bord';
$titleOverrides = [
    'design-edit-header'=>'Éditeur de Header','design-edit-footer'=>'Éditeur de Footer',
    'journal'=>'Stratégie Contenu','journal-matrice'=>'Matrice Stratégique',
    'journal-generate'=>'Générateur IA','journal-perf'=>'Performance Éditoriale',
    'messagerie'=>'Messagerie','email-auto'=>'Emails Automatiques','module-manager'=>'Gestionnaire de Modules',
];
if (isset($titleOverrides[$originalModule])) {
    $page_title = $titleOverrides[$originalModule];
} elseif (isset($registry[$module]['label'])) {
    $page_title = $registry[$module]['label'];
}

$adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_email'] ?? 'Admin';
$adminInitial = strtoupper(substr($adminName,0,1));
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');

// ============================================
// SIDEBAR — Flat level-1 only, no icon dups
// ============================================
$usedIcons = [];
function getUniqueIcon($icon, &$used) {
    if (in_array($icon, $used)) return null; // skip duplicate
    $used[] = $icon;
    return $icon;
}

$sidebarSections = [
    ['type'=>'item', 'slug'=>'dashboard',  'label'=>'Tableau de bord',    'icon'=>'fa-th-large'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'SITE & CONTENU'],
    ['type'=>'item', 'slug'=>'pages',      'label'=>'Pages',              'icon'=>'fa-file-alt'],
    ['type'=>'item', 'slug'=>'articles',   'label'=>'Articles',           'icon'=>'fa-pen-fancy'],
    ['type'=>'item', 'slug'=>'captures',   'label'=>'Pages de capture',   'icon'=>'fa-magnet'],
    ['type'=>'item', 'slug'=>'secteurs',   'label'=>'Quartiers',          'icon'=>'fa-map-pin'],
    ['type'=>'item', 'slug'=>'builder',    'label'=>'Website Builder',    'icon'=>'fa-wand-magic-sparkles', 'badge'=>'PRO'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'DESIGN'],
    ['type'=>'item', 'slug'=>'templates',       'label'=>'Templates',     'icon'=>'fa-palette'],
    ['type'=>'item', 'slug'=>'design-headers',  'label'=>'Headers',       'icon'=>'fa-window-maximize'],
    ['type'=>'item', 'slug'=>'design-footers',  'label'=>'Footers',       'icon'=>'fa-window-minimize'],
    ['type'=>'item', 'slug'=>'menus',           'label'=>'Menus',         'icon'=>'fa-bars'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'CRM & CONTACTS'],
    ['type'=>'item', 'slug'=>'crm',         'label'=>'Tous les contacts', 'icon'=>'fa-address-book'],
    ['type'=>'item', 'slug'=>'leads',       'label'=>'Prospects',         'icon'=>'fa-user-plus'],
    ['type'=>'item', 'slug'=>'messagerie',  'label'=>'Messagerie',        'icon'=>'fa-inbox',          'badge_count'=>$stats['emails_unread']],
    ['type'=>'item', 'slug'=>'email-auto',  'label'=>'Emails Auto',       'icon'=>'fa-paper-plane'],
    ['type'=>'item', 'slug'=>'scoring',     'label'=>'Lead Scoring',      'icon'=>'fa-bullseye'],
    ['type'=>'item', 'slug'=>'rdv',         'label'=>'Agenda',            'icon'=>'fa-calendar-check'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'IMMOBILIER'],
    ['type'=>'item', 'slug'=>'estimation',  'label'=>'Estimations',       'icon'=>'fa-calculator'],
    ['type'=>'item', 'slug'=>'biens',       'label'=>'Annonces',          'icon'=>'fa-building'],
    ['type'=>'item', 'slug'=>'financement', 'label'=>'Financement',       'icon'=>'fa-hand-holding-usd'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'SEO'],
    ['type'=>'item', 'slug'=>'seo',          'label'=>'Vue d\'ensemble',  'icon'=>'fa-search'],
    ['type'=>'item', 'slug'=>'seo-semantic', 'label'=>'Sémantique',       'icon'=>'fa-brain'],
    ['type'=>'item', 'slug'=>'local-seo',    'label'=>'SEO Local & GMB',  'icon'=>'fa-map-marker-alt'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'MARKETING'],
    ['type'=>'item', 'slug'=>'journal',          'label'=>'Stratégie Contenu','icon'=>'fa-newspaper',    'badge'=>'NEW'],
    ['type'=>'item', 'slug'=>'reseaux-sociaux',  'label'=>'Réseaux Sociaux', 'icon'=>'fa-share-nodes'],
    ['type'=>'item', 'slug'=>'emails',           'label'=>'Séquences Email', 'icon'=>'fa-envelope-open-text'],
    ['type'=>'item', 'slug'=>'ads-overview',     'label'=>'Publicité',       'icon'=>'fa-bullhorn'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'PROSPECTION'],
    ['type'=>'item', 'slug'=>'gmb',          'label'=>'Prospection B2B',  'icon'=>'fa-crosshairs'],
    ['type'=>'item', 'slug'=>'launchpad',    'label'=>'Launchpad',        'icon'=>'fa-rocket'],
    ['type'=>'sep'],
    ['type'=>'group', 'label'=>'SYSTÈME'],
    ['type'=>'item', 'slug'=>'module-manager','label'=>'Modules',         'icon'=>'fa-puzzle-piece'],
    ['type'=>'item', 'slug'=>'settings',     'label'=>'Configuration',    'icon'=>'fa-gear'],
    ['type'=>'item', 'slug'=>'ia',           'label'=>'Assistant IA',     'icon'=>'fa-sparkles',       'badge'=>'BETA'],
    ['type'=>'item', 'slug'=>'analytics',    'label'=>'Statistiques',     'icon'=>'fa-chart-bar'],
];

// Active module detection for sidebar highlight
$activeSlug = $originalModule;
// Map sub-routes to parent module for highlight
$highlightMap = [
    'seo-pages'=>'seo','seo-articles'=>'seo','seo-serp'=>'seo','seo-indexation'=>'seo',
    'local-gmb-posts'=>'local-seo','local-gmb-avis'=>'local-seo','local-gmb-qa'=>'local-seo','local-partners'=>'local-seo',
    'gmb-contacts'=>'gmb','gmb-sequences'=>'gmb',
    'facebook'=>'reseaux-sociaux','instagram'=>'reseaux-sociaux','linkedin'=>'reseaux-sociaux','tiktok'=>'reseaux-sociaux',
    'facebook-journal'=>'journal','instagram-journal'=>'journal','tiktok-journal'=>'journal','linkedin-journal'=>'journal',
    'articles-journal'=>'journal','local-gmb-journal'=>'journal','emails-journal'=>'journal',
    'journal-matrice'=>'journal','journal-generate'=>'journal','journal-perf'=>'journal',
    'google-ads'=>'ads-overview','facebook-ads'=>'ads-overview','ads-budget'=>'ads-overview',
    'parcours-vendeurs'=>'launchpad','parcours-acheteurs'=>'launchpad','parcours-conversion'=>'launchpad','parcours-organisation'=>'launchpad','parcours-scale'=>'launchpad',
    'settings-email'=>'settings','settings-identity'=>'settings',
];
$highlightSlug = $highlightMap[$activeSlug] ?? $activeSlug;

// Count enabled modules
$enabledCount = count(array_filter($allModules, fn($m) => $m['enabled']));
$totalModuleCount = count($allModules);
$healthyCount = count(array_filter($allModules, fn($m) => $m['health'] === 'ok'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — IMMO LOCAL+</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/admin/assets/css/admin-components.css">
    <script src="/admin/assets/js/admin-components.js" defer></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        :root{
            --bg:#f6f5f2;--surface:#ffffff;--surface-2:#f0efec;--surface-3:#e8e6e1;
            --border:rgba(0,0,0,.06);--border-h:rgba(0,0,0,.12);
            --text:#1a1816;--text-2:#57534e;--text-3:#a8a29e;
            --accent:#4f46e5;--accent-l:#818cf8;--accent-bg:rgba(79,70,229,.04);
            --green:#059669;--green-bg:rgba(5,150,105,.06);
            --red:#dc2626;--red-bg:rgba(220,38,38,.05);
            --amber:#d97706;--amber-bg:rgba(217,119,6,.06);
            --blue:#2563eb;--blue-bg:rgba(37,99,235,.05);
            --rose:#e11d48;--rose-bg:rgba(225,29,72,.05);
            --teal:#0d9488;--teal-bg:rgba(13,148,136,.06);
            --violet:#7c3aed;--violet-bg:rgba(124,58,237,.05);
            --sb-w:252px;
            --sb-bg:#111110;--sb-surface:rgba(255,255,255,.04);--sb-border:rgba(255,255,255,.06);
            --sb-text:rgba(255,255,255,.58);--sb-text-active:#ffffff;--sb-text-muted:rgba(255,255,255,.28);
            --radius:8px;--radius-lg:12px;--radius-xl:16px;
            --shadow-xs:0 1px 2px rgba(0,0,0,.03);
            --shadow:0 1px 3px rgba(0,0,0,.04),0 4px 16px rgba(0,0,0,.03);
            --shadow-lg:0 4px 24px rgba(0,0,0,.07);
            --font:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,sans-serif;
            --font-display:'Space Grotesk','Plus Jakarta Sans',sans-serif;
            --mono:ui-monospace,SFMono-Regular,'Cascadia Code',monospace;
            --ease:cubic-bezier(.4,0,.2,1);
        }
        html,body{height:100%}
        body{font-family:var(--font);background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased}
        .app{display:flex;height:100vh;overflow:hidden}

        /* ═══════════════════════ SIDEBAR ═══════════════════════ */
        .sidebar{width:var(--sb-w);background:var(--sb-bg);display:flex;flex-direction:column;position:fixed;left:0;top:0;height:100vh;z-index:1000;transition:transform .3s var(--ease)}
        .sb-hd{padding:18px 16px;display:flex;align-items:center;gap:11px;flex-shrink:0;border-bottom:1px solid var(--sb-border)}
        .sb-logo{width:32px;height:32px;background:linear-gradient(135deg,#6366f1,#a78bfa);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;font-weight:800;flex-shrink:0}
        .sb-brand h1{font-family:var(--font-display);font-size:13px;font-weight:700;color:#fff;letter-spacing:-.01em;line-height:1.2}
        .sb-brand span{font-size:9.5px;color:var(--sb-text-muted);letter-spacing:.02em}

        .sb-nav{flex:1;padding:8px 8px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.06) transparent}
        .sb-nav::-webkit-scrollbar{width:3px}.sb-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.06);border-radius:2px}

        /* Group label */
        .sb-group{padding:16px 12px 5px;font-size:9px;font-weight:700;color:var(--sb-text-muted);letter-spacing:.1em;text-transform:uppercase}
        .sb-sep{height:1px;background:var(--sb-border);margin:4px 10px}

        /* Nav item — flat level 1 */
        .sb-item{display:flex;align-items:center;gap:10px;padding:8px 12px;color:var(--sb-text);text-decoration:none;font-size:12.5px;font-weight:500;border-radius:var(--radius);transition:all .12s;margin-bottom:1px;position:relative}
        .sb-item:hover{background:var(--sb-surface);color:rgba(255,255,255,.85)}
        .sb-item.active{background:rgba(99,102,241,.14);color:#c7d2fe;font-weight:600}
        .sb-item.active::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:18px;background:#818cf8;border-radius:0 3px 3px 0}
        .sb-item i{width:16px;text-align:center;font-size:11px;opacity:.55;flex-shrink:0}
        .sb-item.active i{opacity:1;color:#a5b4fc}
        .sb-item .sb-label{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sb-badge{font-size:7px;padding:2px 5px;border-radius:4px;font-weight:700;letter-spacing:.3px;flex-shrink:0;text-transform:uppercase}
        .sb-badge.pro{background:linear-gradient(135deg,var(--accent),#7c3aed);color:#fff}
        .sb-badge.beta{background:rgba(217,119,6,.2);color:#fbbf24}
        .sb-badge.new{background:rgba(5,150,105,.2);color:#6ee7b7}
        .sb-badge.ia{background:rgba(6,182,212,.2);color:#67e8f9}
        /* Unread count badge */
        .sb-count{min-width:18px;height:18px;border-radius:9px;background:#ef4444;color:#fff;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;padding:0 5px}

        /* Footer */
        .sb-ft{padding:10px 8px;border-top:1px solid var(--sb-border);flex-shrink:0}
        .sb-user{display:flex;align-items:center;gap:9px;padding:8px 10px;background:var(--sb-surface);border-radius:var(--radius);margin-bottom:6px}
        .sb-avatar{width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#6366f1,#ec4899);display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:12px;flex-shrink:0}
        .sb-uname{font-size:11px;font-weight:600;color:#fff;line-height:1.2}.sb-urole{font-size:9px;color:var(--sb-text-muted)}
        .sb-logout{display:flex;align-items:center;justify-content:center;gap:6px;color:rgba(255,255,255,.3);text-decoration:none;font-size:10.5px;padding:7px;border-radius:var(--radius);transition:all .2s;cursor:pointer;width:100%;font-family:inherit;background:0;border:0}
        .sb-logout:hover{color:#fca5a5;background:rgba(239,68,68,.08)}

        /* ═══════════════════════ MAIN ═══════════════════════ */
        .main{flex:1;margin-left:var(--sb-w);display:flex;flex-direction:column;overflow:hidden}
        .topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 24px;display:flex;justify-content:space-between;align-items:center;height:52px;flex-shrink:0}
        .topbar-left{display:flex;align-items:center;gap:12px}
        .topbar-left h1{font-family:var(--font-display);font-size:16px;font-weight:700;letter-spacing:-.02em}
        .topbar-right{display:flex;align-items:center;gap:6px}
        .tb-search{position:relative}
        .tb-search input{padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:var(--radius);font-size:11.5px;width:180px;font-family:inherit;background:var(--surface-2);transition:all .2s}
        .tb-search input:focus{outline:0;border-color:var(--accent);background:var(--surface);width:220px}
        .tb-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:10px}
        .tb-icon{width:32px;height:32px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;color:var(--text-3);text-decoration:none;transition:all .12s;font-size:12px}
        .tb-icon:hover{background:var(--surface-2);color:var(--accent)}
        .content{flex:1;overflow-y:auto;padding:24px 28px}
        .mobile-toggle{display:none;background:0;border:0;font-size:17px;color:var(--text);cursor:pointer;padding:4px}

        /* ═══════════════════════ DASHBOARD ═══════════════════════ */
        .dash-hero{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:20px;position:relative;overflow:hidden}
        .dash-hero::after{content:'';position:absolute;top:-60px;right:-30px;width:200px;height:200px;background:radial-gradient(circle,rgba(99,102,241,.05),transparent 70%);border-radius:50%;pointer-events:none}
        .hero-left{position:relative;z-index:1}
        .hero-left h2{font-family:var(--font-display);font-size:22px;font-weight:700;letter-spacing:-.03em;margin-bottom:4px}
        .hero-left p{font-size:13px;color:var(--text-2);line-height:1.5}
        .hero-left p strong{color:var(--text);font-weight:700}
        .hero-actions{display:flex;gap:8px;flex-shrink:0;position:relative;z-index:1}
        .h-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:var(--radius);font-family:var(--font);font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;cursor:pointer;border:none}
        .h-btn-p{background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(79,70,229,.18)}.h-btn-p:hover{background:#4338ca;transform:translateY(-1px)}
        .h-btn-s{background:var(--surface-2);color:var(--text);border:1px solid var(--border)}.h-btn-s:hover{background:var(--surface-3)}

        /* License bar */
        .license-bar{display:flex;align-items:center;gap:12px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 18px;margin-bottom:20px;font-size:12px}
        .lic-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .lic-dot.on{background:var(--green);box-shadow:0 0 0 3px rgba(5,150,105,.15);animation:pulse-g 2s infinite}
        .lic-dot.off{background:var(--amber)}
        @keyframes pulse-g{0%,100%{box-shadow:0 0 0 3px rgba(5,150,105,.15)}50%{box-shadow:0 0 0 6px rgba(5,150,105,0)}}
        .lic-info{flex:1;display:flex;align-items:center;gap:14px}
        .lic-info strong{font-weight:700}
        .lic-plan{font-family:var(--mono);font-size:10px;font-weight:700;text-transform:uppercase;padding:2px 7px;border-radius:4px;background:var(--accent-bg);color:var(--accent)}
        .lic-meta{font-size:11px;color:var(--text-3)}
        .lic-link{font-size:11px;color:var(--accent);text-decoration:none;font-weight:600}
        .lic-warn{background:var(--amber-bg);border-color:rgba(217,119,6,.12)}

        /* Metric cards */
        .metrics-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px}
        .metric-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;transition:all .2s;cursor:pointer;text-decoration:none;color:inherit;display:block}
        .metric-card:hover{border-color:var(--border-h);transform:translateY(-1px);box-shadow:var(--shadow)}
        .mc-icon{width:34px;height:34px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:14px;margin-bottom:10px}
        .mc-val{font-family:var(--font-display);font-size:26px;font-weight:700;letter-spacing:-.03em;line-height:1}
        .mc-label{font-size:10.5px;color:var(--text-3);margin-top:3px;font-weight:500}

        /* Health strip */
        .health-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
        .health-item{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:14px 16px;display:flex;align-items:center;gap:10px}
        .h-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .h-dot.ok{background:var(--green);box-shadow:0 0 0 3px var(--green-bg)}
        .h-dot.warn{background:var(--amber);box-shadow:0 0 0 3px var(--amber-bg)}
        .h-info{font-size:11.5px;font-weight:600;line-height:1.3}
        .h-sub{font-size:10px;color:var(--text-3);font-weight:400}

        /* Section titles */
        .sec-title{font-family:var(--font-display);font-size:15px;font-weight:700;letter-spacing:-.01em;margin-bottom:12px;display:flex;align-items:center;gap:7px}
        .sec-title i{color:var(--accent);font-size:13px}

        /* Parcours row */
        .parcours-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:24px}
        .parc{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px 14px;text-decoration:none;color:inherit;transition:all .25s;text-align:center;position:relative;overflow:hidden}
        .parc::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--pc-color);opacity:.6}
        .parc:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg);border-color:var(--pc-color)}
        .parc-emoji{font-size:26px;margin-bottom:6px;display:block}
        .parc-id{font-size:8px;font-weight:800;text-transform:uppercase;letter-spacing:.1em;color:var(--pc-color);margin-bottom:2px}
        .parc-name{font-size:11px;font-weight:700;margin-bottom:8px}
        .parc-bar{height:3px;background:var(--surface-2);border-radius:2px;overflow:hidden;margin-bottom:3px}
        .parc-fill{height:100%;border-radius:2px;background:var(--pc-color);transition:width .5s var(--ease)}
        .parc-pct{font-size:9px;color:var(--text-3);font-weight:600}

        /* Quick grid */
        .quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:24px}
        .qk{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;text-decoration:none;color:inherit;transition:all .2s;text-align:center}
        .qk:hover{border-color:var(--accent);transform:translateY(-1px);box-shadow:0 6px 20px rgba(79,70,229,.05)}
        .qk-icon{width:38px;height:38px;margin:0 auto 8px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff}
        .qk h4{font-size:11.5px;font-weight:700;margin-bottom:2px}.qk p{font-size:10px;color:var(--text-3)}

        /* Two col */
        .two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
        .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
        .card-hd{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .card-hd h3{font-family:var(--font-display);font-size:13px;font-weight:700}
        .card-hd a{font-size:10px;color:var(--accent);text-decoration:none;font-weight:600}
        .card-body{padding:14px 18px}
        .act{display:flex;align-items:center;gap:10px;padding:10px;background:var(--surface-2);border-radius:var(--radius);text-decoration:none;color:inherit;margin-bottom:6px;transition:all .12s}
        .act:last-child{margin-bottom:0}.act:hover{background:var(--accent-bg);transform:translateX(2px)}
        .act-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;color:#fff}
        .act-text strong{display:block;font-size:11px;font-weight:700;margin-bottom:1px}
        .act-text span{font-size:9.5px;color:var(--text-3)}

        /* ═══════════════════════ MODULE MANAGER ═══════════════════════ */
        .mm-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
        .mm-stats{display:flex;gap:20px}
        .mm-stat{text-align:center}
        .mm-stat-val{font-family:var(--font-display);font-size:28px;font-weight:700}
        .mm-stat-label{font-size:10px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;font-weight:600}
        .mm-score{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:22px;font-weight:800;color:#fff}
        .mm-cat-title{font-family:var(--font-display);font-size:14px;font-weight:700;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--border);color:var(--text);display:flex;align-items:center;gap:8px}
        .mm-cat-title i{font-size:12px;color:var(--accent)}
        .mm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px;margin-bottom:28px}
        .mm-mod{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;display:flex;align-items:center;gap:14px;transition:all .15s}
        .mm-mod:hover{border-color:var(--border-h);box-shadow:var(--shadow-xs)}
        .mm-mod.disabled{opacity:.5}
        .mm-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0}
        .mm-info{flex:1;min-width:0}
        .mm-name{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:6px}
        .mm-desc{font-size:10.5px;color:var(--text-3);margin-top:1px}
        .mm-status{display:flex;align-items:center;gap:8px;flex-shrink:0}
        .mm-health{width:8px;height:8px;border-radius:50%}
        .mm-health.ok{background:var(--green)}
        .mm-health.warn{background:var(--amber)}
        .mm-health.err{background:var(--red)}
        /* Toggle switch */
        .toggle{position:relative;width:36px;height:20px;flex-shrink:0}
        .toggle input{opacity:0;width:0;height:0}
        .toggle-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:var(--surface-3);border-radius:10px;transition:all .2s}
        .toggle-slider::before{content:'';position:absolute;height:14px;width:14px;left:3px;bottom:3px;background:white;border-radius:50%;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.1)}
        .toggle input:checked + .toggle-slider{background:var(--accent)}
        .toggle input:checked + .toggle-slider::before{transform:translateX(16px)}
        .toggle input:disabled + .toggle-slider{opacity:.5;cursor:not-allowed}
        .mm-critical{font-size:7px;padding:1px 5px;border-radius:3px;background:var(--red-bg);color:var(--red);font-weight:700;text-transform:uppercase;letter-spacing:.03em}

        /* ═══════════════════════ MESSAGERIE ═══════════════════════ */
        .msg-layout{display:grid;grid-template-columns:300px 1fr;height:calc(100vh - 52px - 48px);gap:0;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
        .msg-sidebar{border-right:1px solid var(--border);display:flex;flex-direction:column}
        .msg-sb-hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .msg-sb-hd h3{font-family:var(--font-display);font-size:14px;font-weight:700}
        .msg-tabs{display:flex;gap:0;border-bottom:1px solid var(--border)}
        .msg-tab{flex:1;padding:10px;text-align:center;font-size:11px;font-weight:600;color:var(--text-3);cursor:pointer;border-bottom:2px solid transparent;transition:all .15s}
        .msg-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
        .msg-tab:hover{color:var(--text)}
        .msg-search{padding:10px 14px;border-bottom:1px solid var(--border)}
        .msg-search input{width:100%;padding:8px 10px 8px 30px;border:1px solid var(--border);border-radius:var(--radius);font-size:11px;font-family:inherit;background:var(--surface-2)}
        .msg-list{flex:1;overflow-y:auto}
        .msg-item{display:flex;align-items:start;gap:10px;padding:12px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:background .1s}
        .msg-item:hover{background:var(--surface-2)}
        .msg-item.active{background:var(--accent-bg);border-left:3px solid var(--accent)}
        .msg-item.unread .msg-sender{font-weight:700}
        .msg-item.unread::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0;margin-top:6px}
        .msg-av{width:34px;height:34px;border-radius:8px;background:var(--surface-3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--text-2);flex-shrink:0}
        .msg-preview{flex:1;min-width:0}
        .msg-sender{font-size:12px;font-weight:600;margin-bottom:1px;display:flex;justify-content:space-between}
        .msg-time{font-size:9px;color:var(--text-3);font-weight:400}
        .msg-subj{font-size:11px;color:var(--text-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .msg-excerpt{font-size:10px;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
        .msg-contact-tag{font-size:8px;padding:1px 5px;border-radius:3px;background:var(--blue-bg);color:var(--blue);font-weight:600;margin-left:6px}
        .msg-content{display:flex;flex-direction:column}
        .msg-content-hd{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
        .msg-content-hd h2{font-size:15px;font-weight:700}
        .msg-content-body{flex:1;padding:20px;overflow-y:auto}
        .msg-actions{display:flex;gap:6px}
        .msg-btn{padding:7px 14px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;display:flex;align-items:center;gap:5px}
        .msg-btn:hover{background:var(--surface-2)}
        .msg-btn-p{background:var(--accent);color:#fff;border-color:var(--accent)}.msg-btn-p:hover{background:#4338ca}
        .msg-compose{padding:16px 20px;border-top:1px solid var(--border)}
        .msg-compose textarea{width:100%;height:80px;border:1px solid var(--border);border-radius:var(--radius);padding:10px;font-size:12px;font-family:inherit;resize:none}
        .msg-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--text-3)}
        .msg-empty i{font-size:40px;margin-bottom:12px;opacity:.2}
        .msg-empty p{font-size:13px}

        /* Module wrapper */
        .mx{background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);min-height:350px}
        .mx-b{padding:24px}
        .es{text-align:center;padding:50px 30px}
        .es i{font-size:40px;color:var(--text-3);opacity:.2;margin-bottom:12px}
        .es h3{font-size:14px;margin-bottom:5px}
        .es p{font-size:11px;color:var(--text-3)}
        .es-btn{display:inline-block;margin-top:12px;padding:9px 18px;background:var(--accent);color:#fff;border-radius:var(--radius);text-decoration:none;font-size:11px;font-weight:600}
        .es-btn:hover{background:#4338ca}

        /* Animations */
        @keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        .anim{animation:fadeUp .4s var(--ease) both}
        .d1{animation-delay:.03s}.d2{animation-delay:.06s}.d3{animation-delay:.09s}.d4{animation-delay:.12s}.d5{animation-delay:.15s}.d6{animation-delay:.18s}

        /* Responsive */
        @media(max-width:1200px){
            .metrics-grid{grid-template-columns:repeat(3,1fr)}
            .quick-grid{grid-template-columns:repeat(2,1fr)}
            .two-col{grid-template-columns:1fr}
            .parcours-grid{grid-template-columns:repeat(3,1fr)}
            .health-strip{grid-template-columns:1fr 1fr}
        }
        @media(max-width:768px){
            .sidebar{transform:translateX(-100%)}
            .sidebar.open{transform:translateX(0);box-shadow:4px 0 20px rgba(0,0,0,.2)}
            .main{margin-left:0}
            .mobile-toggle{display:block}
            .metrics-grid,.quick-grid,.parcours-grid{grid-template-columns:1fr 1fr}
            .dash-hero{flex-direction:column;text-align:center}
            .hero-actions{justify-content:center}
            .license-bar{flex-direction:column;text-align:center}
            .health-strip{grid-template-columns:1fr}
            .msg-layout{grid-template-columns:1fr}
            .msg-content{display:none}
        }
    </style>
</head>
<body>
<div class="app">

    <!-- ═══════════════════════ SIDEBAR ═══════════════════════ -->
    <aside class="sidebar" id="sidebar">
        <div class="sb-hd">
            <div class="sb-logo">E</div>
            <div class="sb-brand"><h1>IMMO LOCAL+</h1><span>Écosystème v8.0</span></div>
        </div>
        <nav class="sb-nav">
            <?php foreach ($sidebarSections as $sec):
                if ($sec['type'] === 'sep'): ?>
                    <div class="sb-sep"></div>
                <?php elseif ($sec['type'] === 'group'): ?>
                    <div class="sb-group"><?= $sec['label'] ?></div>
                <?php elseif ($sec['type'] === 'item'):
                    $isActive = ($highlightSlug === $sec['slug'] || $originalModule === $sec['slug']);
                    $iconCls = (str_starts_with($sec['icon']??'','fab ')) ? $sec['icon'] : 'fas '.$sec['icon'];
                ?>
                    <a href="?page=<?= $sec['slug'] ?>" class="sb-item<?= $isActive?' active':'' ?>">
                        <i class="<?= $iconCls ?>"></i>
                        <span class="sb-label"><?= htmlspecialchars($sec['label']) ?></span>
                        <?php if (!empty($sec['badge'])): ?>
                            <span class="sb-badge <?= strtolower($sec['badge']) ?>"><?= $sec['badge'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($sec['badge_count']) && $sec['badge_count'] > 0): ?>
                            <span class="sb-count"><?= $sec['badge_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sb-ft">
            <div class="sb-user">
                <div class="sb-avatar"><?= $adminInitial ?></div>
                <div><div class="sb-uname"><?= htmlspecialchars(substr($adminName,0,22)) ?></div><div class="sb-urole">Administrateur</div></div>
            </div>
            <button class="sb-logout" onclick="location.href='/admin/logout.php'"><i class="fas fa-sign-out-alt"></i> Déconnexion</button>
        </div>
    </aside>

    <!-- ═══════════════════════ MAIN AREA ═══════════════════════ -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                <h1><?= htmlspecialchars($page_title) ?></h1>
            </div>
            <div class="topbar-right">
                <div class="tb-search"><i class="fas fa-search"></i><input type="text" placeholder="Rechercher…" id="globalSearch"></div>
                <a href="?page=messagerie" class="tb-icon" title="Messagerie" style="position:relative">
                    <i class="fas fa-inbox"></i>
                    <?php if ($stats['emails_unread'] > 0): ?>
                        <span style="position:absolute;top:2px;right:2px;width:8px;height:8px;border-radius:50%;background:#ef4444;border:2px solid var(--surface)"></span>
                    <?php endif; ?>
                </a>
                <a href="/" target="_blank" class="tb-icon" title="Voir le site"><i class="fas fa-external-link-alt"></i></a>
                <a href="?page=settings" class="tb-icon" title="Paramètres"><i class="fas fa-gear"></i></a>
            </div>
        </header>

        <div class="content">

<?php if ($originalModule === 'dashboard'): ?>
<!-- ═══════════════════════ DASHBOARD ═══════════════════════ -->

            <div class="dash-hero anim">
                <div class="hero-left">
                    <h2><?= $greeting ?>, <?= htmlspecialchars(explode('@',$adminName)[0]) ?> 👋</h2>
                    <p><strong><?= $enabledCount ?>/<?= $totalModuleCount ?></strong> modules actifs · <strong><?= $stats['pages']+$stats['articles'] ?></strong> contenus · <strong><?= $stats['leads']+$stats['contacts'] ?></strong> contacts
                    <?php if ($stats['journal_total']>0): ?> · <strong><?= $stats['journal_total'] ?></strong> idées éditoriales<?php endif; ?></p>
                </div>
                <div class="hero-actions">
                    <a href="?page=launchpad" class="h-btn h-btn-p"><i class="fas fa-rocket"></i> Launchpad</a>
                    <a href="?page=journal" class="h-btn h-btn-s"><i class="fas fa-newspaper"></i> Stratégie</a>
                    <a href="/" target="_blank" class="h-btn h-btn-s"><i class="fas fa-eye"></i> Mon site</a>
                </div>
            </div>

            <?php if ($hasLicense && $licenseActive): ?>
            <div class="license-bar anim d1"><div class="lic-dot on"></div><div class="lic-info"><strong>Licence active</strong><span class="lic-plan"><?= strtoupper(htmlspecialchars($licenseData['plan']??'beta')) ?></span><span class="lic-meta">Expire : <?= $licenseData['expires_at'] ? htmlspecialchars($licenseData['expires_at']) : 'Illimitée' ?></span></div><a href="?page=license" class="lic-link">Gérer →</a></div>
            <?php elseif (!$hasLicense): ?>
            <div class="license-bar lic-warn anim d1"><div class="lic-dot off"></div><div class="lic-info"><strong>Aucune licence activée</strong><span class="lic-meta">Activez pour débloquer toutes les fonctionnalités</span></div><a href="?page=license" class="h-btn h-btn-p" style="font-size:11px;padding:7px 14px"><i class="fas fa-key"></i> Activer</a></div>
            <?php endif; ?>

            <div class="metrics-grid anim d2">
                <a href="?page=pages" class="metric-card"><div class="mc-icon" style="background:var(--blue-bg);color:var(--blue)"><i class="fas fa-file-alt"></i></div><div class="mc-val"><?= $stats['pages'] ?></div><div class="mc-label">Pages</div></a>
                <a href="?page=articles" class="metric-card"><div class="mc-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-pen-fancy"></i></div><div class="mc-val"><?= $stats['articles'] ?></div><div class="mc-label">Articles</div></a>
                <a href="?page=leads" class="metric-card"><div class="mc-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-user-plus"></i></div><div class="mc-val"><?= $stats['leads'] ?></div><div class="mc-label">Prospects</div></a>
                <a href="?page=crm" class="metric-card"><div class="mc-icon" style="background:var(--amber-bg);color:var(--amber)"><i class="fas fa-address-book"></i></div><div class="mc-val"><?= $stats['contacts'] ?></div><div class="mc-label">Contacts</div></a>
                <a href="?page=seo" class="metric-card"><div class="mc-icon" style="background:var(--teal-bg);color:var(--teal)"><i class="fas fa-search"></i></div><div class="mc-val"><?= $stats['seo_avg'] ?>%</div><div class="mc-label">Score SEO</div></a>
                <a href="?page=messagerie" class="metric-card"><div class="mc-icon" style="background:var(--rose-bg);color:var(--rose)"><i class="fas fa-inbox"></i></div><div class="mc-val"><?= $stats['emails_unread'] ?></div><div class="mc-label">Non lus</div></a>
            </div>

            <div class="health-strip anim d3">
                <div class="health-item"><div class="h-dot ok"></div><div><div class="h-info">Site en ligne</div><div class="h-sub"><?= $stats['pages'] ?> pages publiées</div></div></div>
                <div class="health-item"><div class="h-dot <?= $stats['seo_avg']>=50?'ok':'warn' ?>"></div><div><div class="h-info">SEO <?= $stats['seo_avg']>=50?'OK':'à améliorer' ?></div><div class="h-sub">Score moyen <?= $stats['seo_avg'] ?>%</div></div></div>
                <div class="health-item"><div class="h-dot <?= $stats['leads']>0?'ok':'warn' ?>"></div><div><div class="h-info">Capture leads</div><div class="h-sub"><?= $stats['captures'] ?> page(s) active(s)</div></div></div>
                <div class="health-item"><div class="h-dot <?= $healthyCount>=$enabledCount*0.8?'ok':'warn' ?>"></div><div><div class="h-info">Santé modules</div><div class="h-sub"><?= $healthyCount ?>/<?= $totalModuleCount ?> opérationnels</div></div></div>
            </div>

            <?php $parcours_info=['A'=>['name'=>'Conquête Vendeurs','emoji'=>'🏠','color'=>'#ef4444','slug'=>'parcours-vendeurs','total'=>23],'B'=>['name'=>'Acheteurs Solvables','emoji'=>'💰','color'=>'#059669','slug'=>'parcours-acheteurs','total'=>23],'C'=>['name'=>'Conversion & Copy','emoji'=>'🎯','color'=>'#d97706','slug'=>'parcours-conversion','total'=>24],'D'=>['name'=>'Organisation','emoji'=>'⚙️','color'=>'#6366f1','slug'=>'parcours-organisation','total'=>24],'E'=>['name'=>'Scale & Domination','emoji'=>'🚀','color'=>'#8b5cf6','slug'=>'parcours-scale','total'=>25]]; ?>
            <div class="sec-title anim d4"><i class="fas fa-compass"></i> Parcours de progression</div>
            <div class="parcours-grid anim d4">
                <?php foreach ($parcours_info as $pid=>$pi): $done=$stats['parcours'][$pid]??0; $pct=$pi['total']>0?round(($done/$pi['total'])*100):0; ?>
                <a href="?page=<?= $pi['slug'] ?>" class="parc" style="--pc-color:<?= $pi['color'] ?>"><span class="parc-emoji"><?= $pi['emoji'] ?></span><div class="parc-id">Parcours <?= $pid ?></div><div class="parc-name"><?= $pi['name'] ?></div><div class="parc-bar"><div class="parc-fill" style="width:<?= $pct ?>%"></div></div><div class="parc-pct"><?= $pct ?>%</div></a>
                <?php endforeach; ?>
            </div>

            <div class="sec-title anim d5"><i class="fas fa-bolt"></i> Accès rapides</div>
            <div class="quick-grid anim d5">
                <a href="?page=launchpad" class="qk"><div class="qk-icon" style="background:linear-gradient(135deg,var(--accent),#7c3aed)"><i class="fas fa-rocket"></i></div><h4>Launchpad</h4><p>Démarrage guidé</p></a>
                <a href="?page=journal-generate" class="qk"><div class="qk-icon" style="background:linear-gradient(135deg,#06b6d4,#8b5cf6)"><i class="fas fa-sparkles"></i></div><h4>Générateur IA</h4><p>Idées automatiques</p></a>
                <a href="?page=articles&action=create" class="qk"><div class="qk-icon" style="background:linear-gradient(135deg,var(--green),#047857)"><i class="fas fa-pen-fancy"></i></div><h4>Nouvel article</h4><p>Contenu SEO en 5 min</p></a>
                <a href="?page=module-manager" class="qk"><div class="qk-icon" style="background:linear-gradient(135deg,#a855f7,#6d28d9)"><i class="fas fa-puzzle-piece"></i></div><h4>Modules</h4><p><?= $enabledCount ?> actifs / <?= $totalModuleCount ?></p></a>
            </div>

            <div class="two-col anim d6">
                <div class="card">
                    <div class="card-hd"><h3>Actions prioritaires</h3><a href="?page=launchpad">Mon parcours →</a></div>
                    <div class="card-body">
                        <?php if ($stats['diagnostic']): ?>
                        <div style="text-align:center;padding:6px"><div style="font-size:24px;margin-bottom:4px">✅</div><p style="color:var(--green);font-weight:700;font-size:12px">Diagnostic complété</p><p style="font-size:10px;color:var(--text-3)">Route : <strong><?= htmlspecialchars($stats['diagnostic']['parcours_principal']??$stats['diagnostic']['route_principale']??'A') ?></strong></p></div>
                        <?php else: ?>
                        <a href="?page=launchpad" class="act"><span class="act-icon" style="background:var(--accent)"><i class="fas fa-rocket"></i></span><div class="act-text"><strong>Faire le diagnostic</strong><span>2 min pour connaître votre priorité</span></div></a>
                        <?php endif; ?>
                        <a href="?page=journal" class="act"><span class="act-icon" style="background:#e11d48"><i class="fas fa-newspaper"></i></span><div class="act-text"><strong>Planifier la stratégie contenu</strong><span>Journal éditorial multi-canal</span></div></a>
                        <a href="?page=messagerie" class="act"><span class="act-icon" style="background:#0ea5e9"><i class="fas fa-inbox"></i></span><div class="act-text"><strong>Consulter la messagerie</strong><span><?= $stats['emails_unread'] ?> message(s) non lu(s)</span></div></a>
                        <a href="?page=captures" class="act"><span class="act-icon" style="background:var(--amber)"><i class="fas fa-magnet"></i></span><div class="act-text"><strong>Créer une page de capture</strong><span>Générer des leads qualifiés</span></div></a>
                    </div>
                </div>
                <div class="card">
                    <div class="card-hd"><h3>Outils avancés</h3></div>
                    <div class="card-body">
                        <a href="?page=journal-matrice" class="act"><span class="act-icon" style="background:#8b5cf6"><i class="fas fa-border-all"></i></span><div class="act-text"><strong>Matrice Stratégique</strong><span>Profils × niveaux de conscience</span></div></a>
                        <a href="?page=crm" class="act"><span class="act-icon" style="background:#ec4899"><i class="fas fa-columns"></i></span><div class="act-text"><strong>Pipeline Kanban</strong><span>Suivi visuel de vos deals</span></div></a>
                        <a href="?page=emails" class="act"><span class="act-icon" style="background:var(--red)"><i class="fas fa-envelope-open-text"></i></span><div class="act-text"><strong>Séquences emails</strong><span>Nurturing automatisé</span></div></a>
                        <a href="?page=module-manager" class="act"><span class="act-icon" style="background:#a855f7"><i class="fas fa-puzzle-piece"></i></span><div class="act-text"><strong>Gestionnaire de modules</strong><span>Activer, désactiver, diagnostiquer</span></div></a>
                    </div>
                </div>
            </div>

<?php elseif ($originalModule === 'module-manager'): ?>
<!-- ═══════════════════════ MODULE MANAGER v2 — Full Diagnostic ═══════════════════════ -->

            <?php
                $healthScore = $totalModuleCount > 0 ? round(($healthyCount / $totalModuleCount) * 100) : 0;
                $scoreColor = $healthScore >= 80 ? '#059669' : ($healthScore >= 50 ? '#d97706' : '#dc2626');
                $grouped = [];
                foreach ($allModules as $m) { $grouped[$m['category']][] = $m; }
                $warnCount = count(array_filter($allModules, fn($m) => $m['health'] === 'missing_table'));
                $disabledCount = count(array_filter($allModules, fn($m) => !$m['enabled']));
            ?>
            <style>
                .mm-page{max-width:1200px}
                .mm-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:16px}
                .mm-top-left h2{font-family:var(--font-display);font-size:20px;font-weight:700;margin-bottom:4px}
                .mm-top-left p{font-size:12px;color:var(--text-2)}
                .mm-top-right{display:flex;align-items:center;gap:20px}
                .mm-top-stat{text-align:center}.mm-top-stat .v{font-family:var(--font-display);font-size:26px;font-weight:700}.mm-top-stat .l{font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.08em;font-weight:600}
                .mm-ring{width:68px;height:68px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-display);font-size:20px;font-weight:800;color:#fff;position:relative}
                .mm-ring::before{content:'';position:absolute;inset:-3px;border-radius:50%;border:3px solid rgba(0,0,0,.05)}
                .mm-summary{display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap}
                .mm-sum-card{flex:1;min-width:180px;padding:12px 16px;border-radius:var(--radius);display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;border:1px solid}
                .mm-sum-ok{background:var(--green-bg);border-color:rgba(5,150,105,.1);color:var(--green)}
                .mm-sum-warn{background:var(--amber-bg);border-color:rgba(217,119,6,.1);color:var(--amber)}
                .mm-sum-off{background:var(--surface-2);border-color:var(--border);color:var(--text-3)}
                .mm-actions-bar{display:flex;gap:8px;margin-bottom:24px}
                .mm-action-btn{padding:8px 16px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;display:inline-flex;align-items:center;gap:6px}
                .mm-action-btn:hover{background:var(--surface-2);border-color:var(--border-h)}
                .mm-action-btn.primary{background:var(--accent);color:#fff;border-color:var(--accent)}.mm-action-btn.primary:hover{background:#4338ca}
                .mm-action-btn.danger{color:var(--red)}.mm-action-btn.danger:hover{background:var(--red-bg)}
                /* Category */
                .mm-cat{margin-bottom:20px}
                .mm-cat-hd{font-family:var(--font-display);font-size:13px;font-weight:700;padding:8px 0;border-bottom:2px solid var(--border);margin-bottom:10px;display:flex;align-items:center;gap:8px;color:var(--text)}
                .mm-cat-hd i{font-size:11px;color:var(--accent)}
                /* Module card expanded */
                .mm-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);margin-bottom:8px;transition:all .15s;overflow:hidden}
                .mm-card:hover{border-color:var(--border-h)}
                .mm-card.disabled{opacity:.45}
                .mm-card-main{display:flex;align-items:center;gap:14px;padding:14px 18px;cursor:pointer}
                .mm-card-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;flex-shrink:0}
                .mm-card-info{flex:1;min-width:0}
                .mm-card-name{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
                .mm-card-desc{font-size:10.5px;color:var(--text-3);margin-top:1px}
                .mm-card-table{font-family:var(--mono);font-size:9px;padding:2px 6px;border-radius:3px;background:var(--surface-2);color:var(--text-2);margin-left:4px;white-space:nowrap}
                .mm-card-table.missing{background:var(--red-bg);color:var(--red)}
                .mm-card-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
                .mm-card-health{display:flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:3px 8px;border-radius:4px}
                .mm-card-health.ok{background:var(--green-bg);color:var(--green)}
                .mm-card-health.warn{background:var(--amber-bg);color:var(--amber)}
                .mm-card-health.fail{background:var(--red-bg);color:var(--red)}
                .mm-card-health.skip{background:var(--surface-2);color:var(--text-3)}
                .mm-card-health i{font-size:8px}
                .mm-diag-btn{width:30px;height:30px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;color:var(--text-3);transition:all .12s;flex-shrink:0}
                .mm-diag-btn:hover{background:var(--accent-bg);color:var(--accent);border-color:var(--accent)}
                .mm-diag-btn.spinning i{animation:spin .8s linear infinite}
                @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
                .mm-critical-tag{font-size:7px;padding:1px 5px;border-radius:3px;background:var(--red-bg);color:var(--red);font-weight:700;text-transform:uppercase;letter-spacing:.03em}
                /* Expand panel */
                .mm-expand{max-height:0;overflow:hidden;transition:max-height .35s ease}
                .mm-expand.open{max-height:1200px}
                .mm-expand-inner{padding:0 18px 16px;border-top:1px solid var(--border)}
                /* Diagnostic results */
                .diag-results{margin-top:12px}
                .diag-check{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:var(--radius);margin-bottom:4px;font-size:11.5px;transition:background .1s}
                .diag-check:hover{background:var(--surface-2)}
                .diag-check-icon{width:22px;height:22px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0}
                .diag-check-icon.ok{background:var(--green-bg);color:var(--green)}
                .diag-check-icon.fail{background:var(--red-bg);color:var(--red)}
                .diag-check-icon.warning{background:var(--amber-bg);color:var(--amber)}
                .diag-check-icon.skip{background:var(--surface-2);color:var(--text-3)}
                .diag-check-name{font-weight:600;min-width:140px}
                .diag-check-detail{color:var(--text-2);flex:1}
                /* Columns table */
                .cols-table{width:100%;border-collapse:collapse;font-size:10px;margin-top:8px}
                .cols-table th{background:var(--surface-2);padding:6px 8px;text-align:left;font-weight:700;font-size:9px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3);border-bottom:1px solid var(--border)}
                .cols-table td{padding:5px 8px;border-bottom:1px solid var(--border);font-family:var(--mono);font-size:10px}
                .cols-table tr:hover td{background:var(--surface-2)}
                .cols-table .key-pri{color:var(--accent);font-weight:700}
                .cols-table .key-idx{color:var(--green)}
                /* Create table button */
                .create-table-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:var(--radius);background:var(--green);color:#fff;font-size:10px;font-weight:600;border:none;cursor:pointer;font-family:inherit;margin-top:8px;transition:all .15s}
                .create-table-btn:hover{background:#047857}
                /* SMTP section */
                .smtp-section{margin-top:16px;padding:16px;background:var(--surface-2);border-radius:var(--radius-lg)}
                .smtp-title{font-family:var(--font-display);font-size:13px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px}
                .smtp-title i{color:var(--accent)}
            </style>

            <div class="mm-page">
                <!-- Header -->
                <div class="mm-top anim">
                    <div class="mm-top-left">
                        <h2>Gestionnaire de Modules</h2>
                        <p>Activez, désactivez et diagnostiquez chaque module. Les tables DB et opérations CRUD sont vérifiées en temps réel.</p>
                    </div>
                    <div class="mm-top-right">
                        <div class="mm-top-stat"><div class="v" style="color:var(--accent)"><?= $enabledCount ?></div><div class="l">Actifs</div></div>
                        <div class="mm-top-stat"><div class="v"><?= $totalModuleCount ?></div><div class="l">Total</div></div>
                        <div class="mm-ring" style="background:<?= $scoreColor ?>"><?= $healthScore ?>%</div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="mm-summary anim d1">
                    <div class="mm-sum-card mm-sum-ok"><i class="fas fa-check-circle"></i> <?= $healthyCount ?> module(s) opérationnel(s)</div>
                    <?php if ($warnCount > 0): ?><div class="mm-sum-card mm-sum-warn"><i class="fas fa-exclamation-triangle"></i> <?= $warnCount ?> table(s) manquante(s)</div><?php endif; ?>
                    <?php if ($disabledCount > 0): ?><div class="mm-sum-card mm-sum-off"><i class="fas fa-pause-circle"></i> <?= $disabledCount ?> module(s) désactivé(s)</div><?php endif; ?>
                </div>

                <!-- Actions -->
                <div class="mm-actions-bar anim d1">
                    <button class="mm-action-btn primary" onclick="runFullDiagnostic()"><i class="fas fa-stethoscope"></i> Diagnostic complet</button>
                    <button class="mm-action-btn" onclick="testSmtp()"><i class="fas fa-envelope"></i> Tester SMTP</button>
                    <button class="mm-action-btn" onclick="createAllMissing()"><i class="fas fa-database"></i> Créer tables manquantes</button>
                </div>

                <!-- Module Cards by Category -->
                <?php foreach ($grouped as $catKey => $mods): ?>
                <div class="mm-cat anim d2">
                    <div class="mm-cat-hd"><i class="fas fa-layer-group"></i> <?= $moduleCategoryLabels[$catKey] ?? ucfirst($catKey) ?> <span style="font-size:10px;color:var(--text-3);font-weight:400;margin-left:auto"><?= count($mods) ?> modules</span></div>
                    <?php foreach ($mods as $m):
                        $healthClass = $m['health']==='ok' ? 'ok' : ($m['health']==='missing_table' ? 'warn' : 'fail');
                        $healthLabel = $m['health']==='ok' ? 'Opérationnel' : ($m['health']==='missing_table' ? 'Table manquante' : 'Inconnu');
                        if (!$m['table']) { $healthClass = 'skip'; $healthLabel = 'Sans DB'; }
                    ?>
                    <div class="mm-card<?= !$m['enabled']?' disabled':'' ?>" id="mm-<?= $m['id'] ?>" data-table="<?= htmlspecialchars($m['table'] ?? '') ?>" data-module="<?= $m['id'] ?>">
                        <div class="mm-card-main" onclick="toggleExpand('<?= $m['id'] ?>')">
                            <div class="mm-card-icon" style="background:<?= $m['color'] ?>"><i class="fas <?= $m['icon'] ?>"></i></div>
                            <div class="mm-card-info">
                                <div class="mm-card-name">
                                    <?= htmlspecialchars($m['name']) ?>
                                    <?php if ($m['critical']): ?><span class="mm-critical-tag">Critique</span><?php endif; ?>
                                    <?php if ($m['table']): ?>
                                        <span class="mm-card-table<?= $m['health']==='missing_table'?' missing':'' ?>"><?= $m['table'] ?></span>
                                    <?php else: ?>
                                        <span class="mm-card-table" style="opacity:.4">pas de table</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mm-card-desc"><?= htmlspecialchars($m['desc']) ?></div>
                            </div>
                            <div class="mm-card-right">
                                <div class="mm-card-health <?= $healthClass ?>"><i class="fas fa-circle"></i> <?= $healthLabel ?></div>
                                <button class="mm-diag-btn" onclick="event.stopPropagation();runDiagnose('<?= $m['id'] ?>','<?= $m['table'] ?? '' ?>')" title="Lancer le diagnostic"><i class="fas fa-stethoscope"></i></button>
                                <label class="toggle" onclick="event.stopPropagation()">
                                    <input type="checkbox" <?= $m['enabled']?'checked':'' ?> <?= $m['critical']?'disabled':'' ?> data-module="<?= $m['id'] ?>" onchange="toggleModule(this)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        <div class="mm-expand" id="expand-<?= $m['id'] ?>">
                            <div class="mm-expand-inner">
                                <div class="diag-results" id="diag-<?= $m['id'] ?>">
                                    <p style="font-size:11px;color:var(--text-3);padding:8px 0"><i class="fas fa-info-circle"></i> Cliquez sur <strong>🩺</strong> pour lancer le diagnostic de ce module</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- SMTP Diagnostic Section -->
                <div class="smtp-section anim d3" id="smtpSection" style="display:none">
                    <div class="smtp-title"><i class="fas fa-envelope"></i> Diagnostic Email / SMTP</div>
                    <div id="smtpResults"></div>
                </div>

                <!-- System Info -->
                <div id="systemInfo" style="display:none;margin-top:16px;padding:16px;background:var(--surface-2);border-radius:var(--radius-lg)" class="anim">
                    <div class="smtp-title"><i class="fas fa-server"></i> Informations Système</div>
                    <div id="systemInfoContent"></div>
                </div>
            </div>

            <script>
            const CSRF = '<?= $_SESSION['csrf_token'] ?>';
            const API_BASE = '/admin/api/module-diagnostic.php';

            // ─── Toggle expand panel ───
            function toggleExpand(modId) {
                const panel = document.getElementById('expand-' + modId);
                document.querySelectorAll('.mm-expand.open').forEach(p => { if (p !== panel) p.classList.remove('open'); });
                panel.classList.toggle('open');
            }

            // ─── Toggle module on/off ───
            function toggleModule(el) {
                const modId = el.dataset.module;
                const enabled = el.checked ? '1' : '0';
                const card = document.getElementById('mm-' + modId);
                fetch(API_BASE + '?action=toggle', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `module=${modId}&enable=${enabled}&csrf_token=${CSRF}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) card.classList.toggle('disabled', !data.enabled);
                    else el.checked = !el.checked;
                })
                .catch(() => { el.checked = !el.checked; });
            }

            // ─── Run single module diagnostic ───
            function runDiagnose(modId, table) {
                const btn = document.querySelector(`#mm-${modId} .mm-diag-btn`);
                const container = document.getElementById('diag-' + modId);
                const panel = document.getElementById('expand-' + modId);
                
                btn.classList.add('spinning');
                panel.classList.add('open');
                container.innerHTML = '<p style="font-size:11px;color:var(--text-3);padding:8px"><i class="fas fa-spinner fa-spin"></i> Diagnostic en cours…</p>';
                
                fetch(API_BASE + `?action=diagnose&module=${modId}&table=${table}`)
                .then(r => r.json())
                .then(data => {
                    btn.classList.remove('spinning');
                    let html = '';
                    
                    // Overall status
                    const oc = data.overall === 'ok' ? 'ok' : (data.overall === 'warning' ? 'warning' : 'fail');
                    const ol = data.overall === 'ok' ? '✅ Tout est fonctionnel' : (data.overall === 'warning' ? '⚠️ Avertissements détectés' : '❌ Problèmes détectés');
                    html += `<div style="padding:8px 10px;border-radius:var(--radius);margin-bottom:10px;font-size:12px;font-weight:700;background:var(--${oc==='ok'?'green':oc==='warning'?'amber':'red'}-bg);color:var(--${oc==='ok'?'green':oc==='warning'?'amber':'red'})">${ol}</div>`;
                    
                    // Checks
                    if (data.checks && data.checks.length) {
                        data.checks.forEach(c => {
                            const icon = c.status === 'ok' ? 'fa-check' : (c.status === 'fail' ? 'fa-times' : (c.status === 'warning' ? 'fa-exclamation' : 'fa-minus'));
                            html += `<div class="diag-check">
                                <div class="diag-check-icon ${c.status}"><i class="fas ${icon}"></i></div>
                                <div class="diag-check-name">${c.name}</div>
                                <div class="diag-check-detail">${c.detail}</div>
                            </div>`;
                        });
                    }
                    
                    // Row count
                    if (data.row_count !== undefined) {
                        html += `<div style="margin-top:8px;font-size:11px;color:var(--text-2)"><strong>${data.row_count}</strong> enregistrement(s) dans <code>${data.table}</code></div>`;
                    }
                    
                    // Columns table
                    if (data.columns && data.columns.length) {
                        html += `<details style="margin-top:10px"><summary style="font-size:11px;font-weight:600;cursor:pointer;color:var(--accent)"><i class="fas fa-columns"></i> Structure de la table (${data.column_count} colonnes)</summary>`;
                        html += '<table class="cols-table"><thead><tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr></thead><tbody>';
                        data.columns.forEach(col => {
                            const keyCls = col.key === 'PRI' ? 'key-pri' : (col.key === 'MUL' || col.key === 'UNI' ? 'key-idx' : '');
                            html += `<tr><td><strong>${col.name}</strong></td><td>${col.type}</td><td>${col.null}</td><td class="${keyCls}">${col.key||'—'}</td><td>${col.default ?? 'NULL'}</td></tr>`;
                        });
                        html += '</tbody></table></details>';
                    }
                    
                    // Create table button if missing
                    if (data.overall === 'fail' && table) {
                        html += `<button class="create-table-btn" onclick="createTable('${table}','${modId}')"><i class="fas fa-plus-circle"></i> Créer la table "${table}"</button>`;
                    }
                    
                    container.innerHTML = html;
                    
                    // Update health badge
                    const badge = document.querySelector(`#mm-${modId} .mm-card-health`);
                    if (badge) {
                        badge.className = 'mm-card-health ' + (data.overall === 'ok' ? 'ok' : (data.overall === 'warning' ? 'warn' : 'fail'));
                        badge.innerHTML = `<i class="fas fa-circle"></i> ${data.overall === 'ok' ? 'Opérationnel' : (data.overall === 'warning' ? 'Avertissement' : 'Erreur')}`;
                    }
                })
                .catch(err => {
                    btn.classList.remove('spinning');
                    container.innerHTML = `<p style="color:var(--red);font-size:11px">Erreur: ${err.message}</p>`;
                });
            }

            // ─── Create missing table ───
            function createTable(table, modId) {
                if (!confirm(`Créer la table "${table}" ?`)) return;
                fetch(API_BASE + '?action=create-table', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `table=${table}&csrf_token=${CSRF}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        runDiagnose(modId, table); // Re-run diagnostic
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                });
            }

            // ─── Create all missing tables ───
            function createAllMissing() {
                const cards = document.querySelectorAll('.mm-card[data-table]');
                const missing = [];
                cards.forEach(c => {
                    const health = c.querySelector('.mm-card-health');
                    if (health && (health.classList.contains('warn') || health.classList.contains('fail'))) {
                        const t = c.dataset.table;
                        if (t) missing.push({table: t, module: c.dataset.module});
                    }
                });
                if (missing.length === 0) { alert('Toutes les tables existent déjà !'); return; }
                if (!confirm(`Créer ${missing.length} table(s) manquante(s) ?\n\n${missing.map(m=>m.table).join(', ')}`)) return;
                
                let done = 0;
                missing.forEach(m => {
                    fetch(API_BASE + '?action=create-table', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `table=${m.table}&csrf_token=${CSRF}`
                    })
                    .then(r => r.json())
                    .then(() => { done++; if (done === missing.length) location.reload(); });
                });
            }

            // ─── Test SMTP ───
            function testSmtp() {
                const section = document.getElementById('smtpSection');
                const results = document.getElementById('smtpResults');
                section.style.display = 'block';
                results.innerHTML = '<p style="font-size:11px;color:var(--text-3)"><i class="fas fa-spinner fa-spin"></i> Test SMTP en cours…</p>';
                section.scrollIntoView({behavior:'smooth'});
                
                fetch(API_BASE + '?action=test-smtp')
                .then(r => r.json())
                .then(data => {
                    let html = '';
                    if (data.checks) {
                        data.checks.forEach(c => {
                            const icon = c.status === 'ok' ? 'fa-check' : (c.status === 'fail' ? 'fa-times' : 'fa-exclamation');
                            const cls = c.status === 'ok' ? 'ok' : (c.status === 'fail' ? 'fail' : 'warning');
                            html += `<div class="diag-check">
                                <div class="diag-check-icon ${cls}"><i class="fas ${icon}"></i></div>
                                <div class="diag-check-name">${c.name}</div>
                                <div class="diag-check-detail">${c.detail}</div>
                            </div>`;
                        });
                    }
                    if (!data.config_found) {
                        html += '<p style="color:var(--amber);font-size:11px;margin-top:8px"><i class="fas fa-exclamation-triangle"></i> Aucune configuration SMTP trouvée. Vérifiez les paramètres dans <a href="?page=settings-email" style="color:var(--accent)">Configuration Email</a>.</p>';
                    }
                    results.innerHTML = html;
                });
            }

            // ─── Full diagnostic (all modules) ───
            function runFullDiagnostic() {
                const cards = document.querySelectorAll('.mm-card');
                let idx = 0;
                function next() {
                    if (idx >= cards.length) {
                        // Also show system info
                        showSystemInfo();
                        return;
                    }
                    const card = cards[idx];
                    const modId = card.dataset.module;
                    const table = card.dataset.table;
                    idx++;
                    if (table) {
                        runDiagnose(modId, table);
                        setTimeout(next, 400); // stagger
                    } else {
                        next();
                    }
                }
                next();
                testSmtp();
            }

            // ─── System info ───
            function showSystemInfo() {
                const el = document.getElementById('systemInfo');
                const content = document.getElementById('systemInfoContent');
                el.style.display = 'block';
                content.innerHTML = '<p style="font-size:11px;color:var(--text-3)"><i class="fas fa-spinner fa-spin"></i> Chargement…</p>';
                
                fetch(API_BASE + '?action=full-diagnostic')
                .then(r => r.json())
                .then(data => {
                    let html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:11px">';
                    html += `<div><strong>PHP:</strong> ${data.php_version}</div>`;
                    html += `<div><strong>MySQL:</strong> ${data.db_version}</div>`;
                    html += `<div><strong>Base:</strong> ${data.db_name}</div>`;
                    html += `<div><strong>Serveur:</strong> ${data.server}</div>`;
                    html += `<div><strong>Espace disque:</strong> ${data.disk_free}</div>`;
                    html += `<div><strong>Mémoire PHP:</strong> ${data.memory_limit}</div>`;
                    html += `<div><strong>Upload max:</strong> ${data.max_upload}</div>`;
                    html += '</div>';
                    
                    html += '<div style="margin-top:10px;font-size:11px"><strong>Extensions PHP:</strong> ';
                    Object.entries(data.extensions || {}).forEach(([ext, ok]) => {
                        html += `<span style="display:inline-flex;align-items:center;gap:3px;margin-right:10px;color:${ok?'var(--green)':'var(--red)'}"><i class="fas ${ok?'fa-check':'fa-times'}" style="font-size:8px"></i>${ext}</span>`;
                    });
                    html += '</div>';
                    content.innerHTML = html;
                });
            }
            </script>

<?php elseif ($originalModule === 'email-auto'): ?>
<!-- ═══════════════════════ EMAILS AUTOMATIQUES — Module CRM ═══════════════════════ -->
            <?php
                // Load email data
                $emailStats = ['sequences'=>0,'templates'=>0,'queue_pending'=>0,'queue_sent'=>0,'queue_failed'=>0,'logs_total'=>0,'logs_opened'=>0];
                if ($pdo) {
                    try { $emailStats['sequences'] = (int)$pdo->query("SELECT COUNT(*) FROM email_sequences")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['templates'] = (int)$pdo->query("SELECT COUNT(*) FROM email_templates")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['queue_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM email_queue WHERE status='pending'")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['queue_sent'] = (int)$pdo->query("SELECT COUNT(*) FROM email_queue WHERE status='sent'")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['queue_failed'] = (int)$pdo->query("SELECT COUNT(*) FROM email_queue WHERE status='failed'")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['logs_total'] = (int)$pdo->query("SELECT COUNT(*) FROM email_logs")->fetchColumn(); } catch(Exception $e) {}
                    try { $emailStats['logs_opened'] = (int)$pdo->query("SELECT COUNT(*) FROM email_logs WHERE event='opened'")->fetchColumn(); } catch(Exception $e) {}
                }
                
                // Load sequences
                $sequences = [];
                try { $sequences = $pdo->query("SELECT * FROM email_sequences ORDER BY created_at DESC")->fetchAll(); } catch(Exception $e) {}
                
                // Load templates
                $templates = [];
                try { $templates = $pdo->query("SELECT * FROM email_templates ORDER BY created_at DESC")->fetchAll(); } catch(Exception $e) {}
                
                // Load queue
                $queue = [];
                try { $queue = $pdo->query("SELECT * FROM email_queue ORDER BY created_at DESC LIMIT 50")->fetchAll(); } catch(Exception $e) {}
            ?>
            <style>
                .ea-tabs{display:flex;gap:0;border-bottom:2px solid var(--border);margin-bottom:20px}
                .ea-tab{padding:10px 18px;font-size:12px;font-weight:600;color:var(--text-3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .12s;display:flex;align-items:center;gap:6px}
                .ea-tab:hover{color:var(--text)}
                .ea-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
                .ea-tab .count{min-width:18px;height:18px;border-radius:9px;background:var(--surface-2);font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 5px}
                .ea-tab.active .count{background:var(--accent-bg);color:var(--accent)}
                .ea-panel{display:none}.ea-panel.active{display:block}
                /* Stats row */
                .ea-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}
                .ea-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;text-align:center}
                .ea-stat-v{font-family:var(--font-display);font-size:24px;font-weight:700}
                .ea-stat-l{font-size:10px;color:var(--text-3);margin-top:2px;font-weight:500}
                /* Sequence card */
                .seq-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 18px;margin-bottom:8px;display:flex;align-items:center;gap:14px;transition:all .12s}
                .seq-card:hover{border-color:var(--border-h);box-shadow:var(--shadow-xs)}
                .seq-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
                .seq-info{flex:1;min-width:0}
                .seq-name{font-size:12.5px;font-weight:700;display:flex;align-items:center;gap:6px}
                .seq-meta{font-size:10.5px;color:var(--text-3);margin-top:2px;display:flex;gap:12px}
                .seq-status{font-size:9px;padding:2px 7px;border-radius:4px;font-weight:700;text-transform:uppercase}
                .seq-status.active{background:var(--green-bg);color:var(--green)}
                .seq-status.paused{background:var(--amber-bg);color:var(--amber)}
                .seq-status.draft{background:var(--surface-2);color:var(--text-3)}
                .seq-actions{display:flex;gap:4px}
                .seq-btn{width:30px;height:30px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;color:var(--text-3);transition:all .12s}
                .seq-btn:hover{background:var(--accent-bg);color:var(--accent);border-color:var(--accent)}
                /* Template card */
                .tpl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}
                .tpl-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px;transition:all .12s}
                .tpl-card:hover{border-color:var(--border-h);box-shadow:var(--shadow-xs)}
                .tpl-cat{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:2px 6px;border-radius:3px;background:var(--accent-bg);color:var(--accent);margin-bottom:6px;display:inline-block}
                .tpl-name{font-size:12.5px;font-weight:700;margin-bottom:2px}
                .tpl-subject{font-size:10.5px;color:var(--text-2);margin-bottom:6px}
                .tpl-footer{display:flex;justify-content:space-between;align-items:center;font-size:10px;color:var(--text-3)}
                /* Queue table */
                .q-table{width:100%;border-collapse:collapse;font-size:11px}
                .q-table th{background:var(--surface-2);padding:8px 10px;text-align:left;font-weight:700;font-size:9px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3);border-bottom:1px solid var(--border)}
                .q-table td{padding:7px 10px;border-bottom:1px solid var(--border)}
                .q-table tr:hover td{background:var(--surface-2)}
                .q-status{font-size:9px;padding:2px 6px;border-radius:4px;font-weight:700}
                .q-status.pending{background:var(--blue-bg);color:var(--blue)}
                .q-status.sent{background:var(--green-bg);color:var(--green)}
                .q-status.failed{background:var(--red-bg);color:var(--red)}
                .q-status.cancelled{background:var(--surface-2);color:var(--text-3)}
                .ea-empty{text-align:center;padding:40px 20px;color:var(--text-3)}
                .ea-empty i{font-size:36px;opacity:.2;margin-bottom:10px}
                .ea-empty p{font-size:12px;margin-bottom:12px}
                .ea-create-btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s}
                .ea-create-btn:hover{background:#4338ca}
                .ea-trigger-map{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px}
                .ea-trigger{padding:12px;border:1px solid var(--border);border-radius:var(--radius);text-align:center;cursor:pointer;transition:all .12s;background:var(--surface)}
                .ea-trigger:hover{border-color:var(--accent);background:var(--accent-bg)}
                .ea-trigger i{font-size:18px;color:var(--accent);display:block;margin-bottom:4px}
                .ea-trigger span{font-size:10px;font-weight:600}
            </style>

            <!-- Tabs -->
            <div class="ea-tabs">
                <div class="ea-tab active" onclick="switchEaTab('overview')"><i class="fas fa-chart-pie" style="font-size:11px"></i> Vue d'ensemble</div>
                <div class="ea-tab" onclick="switchEaTab('sequences')"><i class="fas fa-list-ol" style="font-size:11px"></i> Séquences <span class="count"><?= $emailStats['sequences'] ?></span></div>
                <div class="ea-tab" onclick="switchEaTab('templates')"><i class="fas fa-envelope-open-text" style="font-size:11px"></i> Templates <span class="count"><?= $emailStats['templates'] ?></span></div>
                <div class="ea-tab" onclick="switchEaTab('queue')"><i class="fas fa-clock" style="font-size:11px"></i> File d'attente <span class="count"><?= $emailStats['queue_pending'] ?></span></div>
            </div>

            <!-- OVERVIEW -->
            <div class="ea-panel active" id="ea-overview">
                <div class="ea-stats anim">
                    <div class="ea-stat"><div class="ea-stat-v" style="color:var(--accent)"><?= $emailStats['sequences'] ?></div><div class="ea-stat-l">Séquences</div></div>
                    <div class="ea-stat"><div class="ea-stat-v" style="color:var(--blue)"><?= $emailStats['templates'] ?></div><div class="ea-stat-l">Templates</div></div>
                    <div class="ea-stat"><div class="ea-stat-v" style="color:var(--amber)"><?= $emailStats['queue_pending'] ?></div><div class="ea-stat-l">En attente</div></div>
                    <div class="ea-stat"><div class="ea-stat-v" style="color:var(--green)"><?= $emailStats['queue_sent'] ?></div><div class="ea-stat-l">Envoyés</div></div>
                    <div class="ea-stat"><div class="ea-stat-v" style="color:var(--red)"><?= $emailStats['queue_failed'] ?></div><div class="ea-stat-l">Échoués</div></div>
                </div>

                <div class="sec-title anim d1"><i class="fas fa-bolt"></i> Déclencheurs automatiques</div>
                <p style="font-size:12px;color:var(--text-2);margin-bottom:12px">Chaque séquence se déclenche automatiquement selon l'événement CRM configuré :</p>
                <div class="ea-trigger-map anim d1">
                    <div class="ea-trigger"><i class="fas fa-user-plus"></i><span>Nouveau lead</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Formulaire / Estimation</div></div>
                    <div class="ea-trigger"><i class="fas fa-bullseye"></i><span>Lead scoré</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Score BANT > seuil</div></div>
                    <div class="ea-trigger"><i class="fas fa-magnet"></i><span>Capture</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Page de capture soumise</div></div>
                    <div class="ea-trigger"><i class="fas fa-calculator"></i><span>Estimation</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Estimation en ligne</div></div>
                    <div class="ea-trigger"><i class="fas fa-calendar-check"></i><span>RDV pris</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Confirmation RDV</div></div>
                    <div class="ea-trigger"><i class="fas fa-tag"></i><span>Tag CRM</span><div style="font-size:9px;color:var(--text-3);margin-top:2px">Contact tagué</div></div>
                </div>
            </div>

            <!-- SEQUENCES -->
            <div class="ea-panel" id="ea-sequences">
                <?php if (empty($sequences)): ?>
                <div class="ea-empty">
                    <i class="fas fa-list-ol"></i>
                    <p>Aucune séquence email créée</p>
                    <button class="ea-create-btn" onclick="alert('Formulaire de création à implémenter')"><i class="fas fa-plus"></i> Créer une séquence</button>
                </div>
                <?php else: ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <p style="font-size:12px;color:var(--text-2)"><?= count($sequences) ?> séquence(s)</p>
                    <button class="ea-create-btn" style="font-size:11px;padding:7px 14px"><i class="fas fa-plus"></i> Nouvelle</button>
                </div>
                <?php foreach ($sequences as $seq):
                    $steps = json_decode($seq['steps'] ?? '[]', true);
                    $stepCount = is_array($steps) ? count($steps) : 0;
                ?>
                <div class="seq-card">
                    <div class="seq-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-list-ol"></i></div>
                    <div class="seq-info">
                        <div class="seq-name">
                            <?= htmlspecialchars($seq['name']) ?>
                            <span class="seq-status <?= $seq['status'] ?>"><?= strtoupper($seq['status']) ?></span>
                        </div>
                        <div class="seq-meta">
                            <span><i class="fas fa-bolt"></i> <?= htmlspecialchars($seq['trigger_type']) ?></span>
                            <span><i class="fas fa-layer-group"></i> <?= $stepCount ?> étape(s)</span>
                            <span><i class="fas fa-users"></i> <?= $seq['total_enrolled'] ?? 0 ?> inscrits</span>
                            <span><i class="fas fa-check"></i> <?= $seq['total_completed'] ?? 0 ?> terminés</span>
                        </div>
                    </div>
                    <div class="seq-actions">
                        <button class="seq-btn" title="Éditer"><i class="fas fa-pen"></i></button>
                        <button class="seq-btn" title="Dupliquer"><i class="fas fa-copy"></i></button>
                        <button class="seq-btn" title="<?= $seq['status']==='active'?'Pause':'Activer' ?>"><i class="fas <?= $seq['status']==='active'?'fa-pause':'fa-play' ?>"></i></button>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <!-- TEMPLATES -->
            <div class="ea-panel" id="ea-templates">
                <?php if (empty($templates)): ?>
                <div class="ea-empty">
                    <i class="fas fa-envelope-open-text"></i>
                    <p>Aucun template email créé</p>
                    <button class="ea-create-btn" onclick="alert('Formulaire de création à implémenter')"><i class="fas fa-plus"></i> Créer un template</button>
                </div>
                <?php else: ?>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <p style="font-size:12px;color:var(--text-2)"><?= count($templates) ?> template(s)</p>
                    <button class="ea-create-btn" style="font-size:11px;padding:7px 14px"><i class="fas fa-plus"></i> Nouveau</button>
                </div>
                <div class="tpl-grid">
                <?php foreach ($templates as $tpl): ?>
                <div class="tpl-card">
                    <span class="tpl-cat"><?= htmlspecialchars($tpl['category'] ?? 'custom') ?></span>
                    <div class="tpl-name"><?= htmlspecialchars($tpl['name']) ?></div>
                    <div class="tpl-subject"><?= htmlspecialchars($tpl['subject'] ?? '') ?></div>
                    <div class="tpl-footer">
                        <span><i class="fas fa-paper-plane"></i> <?= $tpl['usage_count'] ?? 0 ?> envois</span>
                        <span><?= $tpl['status'] ?? 'active' ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- QUEUE -->
            <div class="ea-panel" id="ea-queue">
                <?php if (empty($queue)): ?>
                <div class="ea-empty">
                    <i class="fas fa-clock"></i>
                    <p>File d'attente vide — les emails seront envoyés automatiquement</p>
                </div>
                <?php else: ?>
                <table class="q-table">
                    <thead><tr><th>Destinataire</th><th>Sujet</th><th>Séquence</th><th>Priorité</th><th>Statut</th><th>Programmé</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($queue as $q): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($q['to_name'] ?: $q['to_email']) ?></strong><br><span style="font-size:9px;color:var(--text-3)"><?= htmlspecialchars($q['to_email']) ?></span></td>
                        <td><?= htmlspecialchars(substr($q['subject'],0,50)) ?></td>
                        <td><?= $q['sequence_id'] ? '#'.$q['sequence_id'] : '—' ?></td>
                        <td style="text-align:center"><?= $q['priority'] ?? 5 ?></td>
                        <td><span class="q-status <?= $q['status'] ?>"><?= strtoupper($q['status']) ?></span></td>
                        <td style="font-size:10px"><?= $q['scheduled_at'] ? date('d/m H:i', strtotime($q['scheduled_at'])) : '—' ?></td>
                        <td>
                            <?php if ($q['status'] === 'pending'): ?>
                            <button class="seq-btn" title="Annuler" style="width:24px;height:24px"><i class="fas fa-times" style="font-size:9px"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <script>
            function switchEaTab(tab) {
                document.querySelectorAll('.ea-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.ea-panel').forEach(p => p.classList.remove('active'));
                document.getElementById('ea-' + tab).classList.add('active');
                event.target.closest('.ea-tab').classList.add('active');
            }
            </script>

<?php elseif ($originalModule === 'messagerie'): ?>
<!-- ═══════════════════════ MESSAGERIE INTÉGRÉE CRM ═══════════════════════ -->

            <div class="msg-layout anim">
                <div class="msg-sidebar">
                    <div class="msg-sb-hd">
                        <h3><i class="fas fa-inbox" style="color:var(--accent);margin-right:6px"></i> Messagerie</h3>
                        <button class="msg-btn msg-btn-p" onclick="composeEmail()" style="font-size:10px;padding:5px 10px"><i class="fas fa-plus"></i> Nouveau</button>
                    </div>
                    <div class="msg-tabs">
                        <div class="msg-tab active" data-tab="inbox">Boîte de réception</div>
                        <div class="msg-tab" data-tab="sent">Envoyés</div>
                        <div class="msg-tab" data-tab="all">Tout</div>
                    </div>
                    <div class="msg-search"><div style="position:relative"><i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:10px"></i><input type="text" placeholder="Rechercher un contact ou un email…" style="padding-left:28px"></div></div>
                    <div class="msg-list" id="msgList">
                        <!-- Populated via JS/AJAX -->
                        <div class="msg-item unread active">
                            <div class="msg-av">JD</div>
                            <div class="msg-preview">
                                <div class="msg-sender">Jean Dupont <span class="msg-contact-tag">CRM</span><span class="msg-time">14:23</span></div>
                                <div class="msg-subj">Re: Estimation appartement Chartrons</div>
                                <div class="msg-excerpt">Bonjour Eduardo, suite à notre échange j'aimerais prendre rendez-vous pour…</div>
                            </div>
                        </div>
                        <div class="msg-item unread">
                            <div class="msg-av">ML</div>
                            <div class="msg-preview">
                                <div class="msg-sender">Marie Lefèvre <span class="msg-contact-tag">CRM</span><span class="msg-time">11:05</span></div>
                                <div class="msg-subj">Recherche maison Saint-Médard</div>
                                <div class="msg-excerpt">J'ai vu votre annonce sur votre site, nous cherchons une maison 4 chambres…</div>
                            </div>
                        </div>
                        <div class="msg-item">
                            <div class="msg-av">PB</div>
                            <div class="msg-preview">
                                <div class="msg-sender">Pierre Bernard<span class="msg-time">Hier</span></div>
                                <div class="msg-subj">Documents de vente</div>
                                <div class="msg-excerpt">Veuillez trouver ci-joint les documents pour la signature du compromis…</div>
                            </div>
                        </div>
                        <div class="msg-item">
                            <div class="msg-av">SH</div>
                            <div class="msg-preview">
                                <div class="msg-sender">Stéphanie Hulen<span class="msg-time">Mar</span></div>
                                <div class="msg-subj">Formation eXp France</div>
                                <div class="msg-excerpt">Salut Eduardo, je voulais te parler de la prochaine session de formation…</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="msg-content">
                    <div class="msg-content-hd">
                        <div>
                            <h2>Re: Estimation appartement Chartrons</h2>
                            <p style="font-size:11px;color:var(--text-3);margin-top:2px">Jean Dupont · jean.dupont@email.com · <a href="?page=crm&action=view&id=42" style="color:var(--accent);text-decoration:none;font-weight:600">Voir fiche CRM →</a></p>
                        </div>
                        <div class="msg-actions">
                            <button class="msg-btn"><i class="fas fa-reply"></i> Répondre</button>
                            <button class="msg-btn"><i class="fas fa-share"></i> Transférer</button>
                            <button class="msg-btn"><i class="fas fa-tag"></i> Étiqueter</button>
                            <button class="msg-btn"><i class="fas fa-archive"></i></button>
                        </div>
                    </div>
                    <div class="msg-content-body">
                        <div style="padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:16px">
                            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
                                <div class="msg-av" style="width:38px;height:38px;font-size:14px">JD</div>
                                <div>
                                    <div style="font-size:13px;font-weight:700">Jean Dupont</div>
                                    <div style="font-size:10px;color:var(--text-3)">Aujourd'hui à 14:23 · Lead Score: <span style="color:var(--green);font-weight:700">78/100</span></div>
                                </div>
                            </div>
                            <div style="font-size:13px;line-height:1.7;color:var(--text-2)">
                                <p>Bonjour Eduardo,</p>
                                <p style="margin-top:8px">Suite à notre échange téléphonique, j'aimerais prendre rendez-vous pour une visite de l'appartement T3 rue Notre-Dame dans le quartier des Chartrons.</p>
                                <p style="margin-top:8px">Nous sommes disponibles ce samedi matin ou mardi en fin d'après-midi. Est-ce que l'un de ces créneaux vous conviendrait ?</p>
                                <p style="margin-top:8px">Cordialement,<br>Jean Dupont</p>
                            </div>
                        </div>
                    </div>
                    <div class="msg-compose">
                        <textarea placeholder="Répondre à Jean Dupont…"></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                            <div style="display:flex;gap:6px">
                                <button class="msg-btn" style="font-size:10px"><i class="fas fa-paperclip"></i></button>
                                <button class="msg-btn" style="font-size:10px"><i class="fas fa-sparkles"></i> IA</button>
                            </div>
                            <button class="msg-btn msg-btn-p"><i class="fas fa-paper-plane"></i> Envoyer</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            // Tab switching
            document.querySelectorAll('.msg-tab').forEach(t => {
                t.addEventListener('click', function() {
                    document.querySelectorAll('.msg-tab').forEach(x => x.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            // Message selection
            document.querySelectorAll('.msg-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.msg-item').forEach(x => x.classList.remove('active'));
                    this.classList.add('active');
                    this.classList.remove('unread');
                });
            });
            function composeEmail() {
                // Would open compose modal
                alert('Ouvrir le compositeur d\'email — À implémenter avec le CRM');
            }
            </script>

<?php else: ?>
<!-- ═══════════════════════ OTHER MODULES ═══════════════════════ -->
            <div class="mx"><div class="mx-b">
            <?php
                $module_file = null;

                if (isset($subRoutes[$originalModule])) {
                    $sr = $subRoutes[$originalModule];
                    if (isset($sr['extra'])) foreach ($sr['extra'] as $k=>$v) $_GET[$k] = $v;
                    $f = __DIR__ . '/modules/' . $sr['file'];
                    if (file_exists($f)) $module_file = $f;
                }

                if (!$module_file && isset($registry[$module]) && $registry[$module]['file']) {
                    $f = __DIR__ . '/modules/' . $registry[$module]['file'];
                    if (isset($registry[$module]['extra'])) foreach ($registry[$module]['extra'] as $k=>$v) $_GET[$k] = $v;
                    if (file_exists($f)) $module_file = $f;
                }

                if (!$module_file && in_array($action, ['edit','create'])) {
                    foreach (['form.php', $action.'.php'] as $try) {
                        $dirs = glob(__DIR__ . '/modules/*/' . $module);
                        foreach ($dirs as $d) {
                            $f = $d . '/' . $try;
                            if (file_exists($f)) { $module_file = $f; break 2; }
                        }
                        $f = __DIR__ . '/modules/' . $module . '/' . $try;
                        if (file_exists($f)) { $module_file = $f; break; }
                    }
                }

                if (!$module_file) {
                    $f = __DIR__ . '/modules/' . $module . '/index.php';
                    if (file_exists($f)) $module_file = $f;
                }

                if ($module_file) {
                    include $module_file;
                } else { ?>
                    <div class="es">
                        <i class="fas fa-folder-open"></i>
                        <h3>Module en préparation</h3>
                        <p>Cette fonctionnalité sera disponible prochainement.</p>
                        <p style="font-size:10px;color:var(--text-3);margin-top:8px">Module : <?= htmlspecialchars($originalModule) ?></p>
                        <a href="?page=dashboard" class="es-btn">← Retour au tableau de bord</a>
                    </div>
                <?php } ?>
            </div></div>
<?php endif; ?>

        </div>
    </div>
</div>

<script>
// Global search — filter sidebar items
const searchInput = document.getElementById('globalSearch');
if (searchInput) {
    let timer;
    searchInput.addEventListener('input', function() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.sb-item').forEach(el => {
                el.style.display = (!q || el.textContent.toLowerCase().includes(q)) ? '' : 'none';
            });
            // Also show/hide group labels
            document.querySelectorAll('.sb-group').forEach(g => {
                let next = g.nextElementSibling;
                let hasVisible = false;
                while (next && !next.classList.contains('sb-group') && !next.classList.contains('sb-sep')) {
                    if (next.classList.contains('sb-item') && next.style.display !== 'none') hasVisible = true;
                    next = next.nextElementSibling;
                }
                g.style.display = (!q || hasVisible) ? '' : 'none';
            });
            document.querySelectorAll('.sb-sep').forEach(s => {
                s.style.display = q ? 'none' : '';
            });
        }, 200);
    });
}
</script>
</body>
</html>