<?php
/**
 * Registre des modules — /admin/config/modules-registry.php
 * 
 * Correspond à la structure réelle sur le serveur après réorganisation.
 * Les chemins 'file' sont relatifs à /admin/modules/
 * 
 * USAGE dans dashboard.php :
 *   $registry = require __DIR__ . '/config/modules-registry.php';
 *   $cats = $registry['__categories']; unset($registry['__categories']);
 *   $page = $_GET['page'] ?? 'dashboard';
 *   $mod  = $registry[$page] ?? null;
 *   if ($mod && $mod['enabled'] && $mod['file']) {
 *       if (isset($mod['extra'])) foreach ($mod['extra'] as $k=>$v) $_GET[$k] = $v;
 *       $path = __DIR__ . '/modules/' . $mod['file'];
 *       if (file_exists($path)) include $path;
 *   }
 */

return [

    '__categories' => [
        'main'       => ['label' => '',                'icon' => ''],
        'content'    => ['label' => 'Contenu',         'icon' => 'fa-pen-fancy'],
        'marketing'  => ['label' => 'Marketing & CRM', 'icon' => 'fa-bullhorn'],
        'social'     => ['label' => 'Réseaux Sociaux', 'icon' => 'fa-share-alt'],
        'immobilier' => ['label' => 'Immobilier',      'icon' => 'fa-building'],
        'seo'        => ['label' => 'SEO & Analytics', 'icon' => 'fa-search'],
        'ai'         => ['label' => 'Intelligence IA', 'icon' => 'fa-robot'],
        'network'    => ['label' => 'Réseau Pro',      'icon' => 'fa-handshake'],
        'builder'    => ['label' => 'Construction',    'icon' => 'fa-hammer'],
        'strategy'   => ['label' => 'Stratégie',       'icon' => 'fa-chess'],
        'system'     => ['label' => 'Système',         'icon' => 'fa-cog'],
    ],

    // ═══════════════════════════════════════════
    //  PRINCIPAL
    // ═══════════════════════════════════════════
    'dashboard' => ['file'=>null, 'category'=>'main', 'label'=>'Dashboard', 'icon'=>'fa-chart-line', 'order'=>0, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  CONTENU — content/
    // ═══════════════════════════════════════════
    'pages'          => ['file'=>'content/pages/index.php',          'category'=>'content', 'label'=>'Pages',            'icon'=>'fa-file-alt',       'order'=>10, 'enabled'=>true],
    'pages-create'   => ['file'=>'content/pages/create.php',         'category'=>'content', 'label'=>'Créer page',       'icon'=>'fa-plus',           'order'=>11, 'enabled'=>true, 'hidden'=>true],
    'pages-edit'     => ['file'=>'content/pages/edit.php',           'category'=>'content', 'label'=>'Modifier page',    'icon'=>'fa-edit',           'order'=>12, 'enabled'=>true, 'hidden'=>true],
    'articles'       => ['file'=>'content/articles/index.php',       'category'=>'content', 'label'=>'Articles',         'icon'=>'fa-pen-fancy',      'order'=>13, 'enabled'=>true],
    'articles-edit'  => ['file'=>'content/articles/edit.php',        'category'=>'content', 'label'=>'Éditer article',   'icon'=>'fa-edit',           'order'=>14, 'enabled'=>true, 'hidden'=>true],
    'blog'           => ['file'=>'content/blog/index.php',           'category'=>'content', 'label'=>'Blog',             'icon'=>'fa-newspaper',      'order'=>15, 'enabled'=>true],
    'secteurs'       => ['file'=>'content/secteurs/index.php',       'category'=>'content', 'label'=>'Quartiers',        'icon'=>'fa-map-marker-alt', 'order'=>16, 'enabled'=>true],
    'secteurs-edit'  => ['file'=>'content/secteurs/edit.php',        'category'=>'content', 'label'=>'Éditer quartier',  'icon'=>'fa-edit',           'order'=>17, 'enabled'=>true, 'hidden'=>true],
    'captures'       => ['file'=>'content/pages-capture/index.php',  'category'=>'content', 'label'=>'Pages de capture', 'icon'=>'fa-magnet',         'order'=>18, 'enabled'=>true],
    'captures-create'=> ['file'=>'content/pages-capture/create.php', 'category'=>'content', 'label'=>'Créer capture',    'icon'=>'fa-plus',           'order'=>19, 'enabled'=>true, 'hidden'=>true],
    'captures-edit'  => ['file'=>'content/pages-capture/edit.php',   'category'=>'content', 'label'=>'Éditer capture',   'icon'=>'fa-edit',           'order'=>20, 'enabled'=>true, 'hidden'=>true],
    'templates'      => ['file'=>'content/templates/index.php',      'category'=>'content', 'label'=>'Templates',        'icon'=>'fa-palette',        'order'=>21, 'enabled'=>true],
    'sections'       => ['file'=>'content/sections/index.php',       'category'=>'content', 'label'=>'Sections',         'icon'=>'fa-puzzle-piece',   'order'=>22, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  MARKETING & CRM — marketing/
    // ═══════════════════════════════════════════
    'leads'      => ['file'=>'marketing/leads/index.php',      'category'=>'marketing', 'label'=>'Leads',           'icon'=>'fa-user-plus',  'order'=>30, 'enabled'=>true],
    'crm'        => ['file'=>'marketing/crm/index.php',        'category'=>'marketing', 'label'=>'Pipeline CRM',    'icon'=>'fa-columns',    'order'=>31, 'enabled'=>true],
    'scoring'    => ['file'=>'marketing/scoring/index.php',     'category'=>'marketing', 'label'=>'Scoring BANT',    'icon'=>'fa-bullseye',   'order'=>32, 'enabled'=>true],
    'emails'     => ['file'=>'marketing/emails/index.php',     'category'=>'marketing', 'label'=>'Séquences Email', 'icon'=>'fa-mail-bulk',  'order'=>33, 'enabled'=>true],
    'sequences'  => ['file'=>'marketing/sequences/index.php',  'category'=>'marketing', 'label'=>'Séquences Auto',  'icon'=>'fa-list-ol',    'order'=>34, 'enabled'=>true],
    'ads-launch' => ['file'=>'marketing/ads-launch/index.php', 'category'=>'marketing', 'label'=>'Publicité',       'icon'=>'fa-ad',         'order'=>35, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  RÉSEAUX SOCIAUX — social/
    // ═══════════════════════════════════════════
    'reseaux-sociaux' => ['file'=>'social/reseaux-sociaux/index.php', 'category'=>'social', 'label'=>'Hub Social',       'icon'=>'fa-share-alt',      'order'=>40, 'enabled'=>true],
    'facebook'        => ['file'=>'social/facebook/index.php',        'category'=>'social', 'label'=>'Facebook',         'icon'=>'fab fa-facebook-f', 'order'=>41, 'enabled'=>true],
    'instagram'       => ['file'=>'social/instagram/index.php',       'category'=>'social', 'label'=>'Instagram',        'icon'=>'fab fa-instagram',  'order'=>42, 'enabled'=>true],
    'linkedin'        => ['file'=>'social/linkedin/index.php',        'category'=>'social', 'label'=>'LinkedIn',         'icon'=>'fab fa-linkedin-in','order'=>43, 'enabled'=>true],
    'tiktok'          => ['file'=>'social/tiktok/index.php',          'category'=>'social', 'label'=>'TikTok',           'icon'=>'fab fa-tiktok',     'order'=>44, 'enabled'=>true],
    'gmb'             => ['file'=>'social/gmb/index.php',             'category'=>'social', 'label'=>'Prospection GMB',  'icon'=>'fab fa-google',     'order'=>45, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  IMMOBILIER — immobilier/
    // ═══════════════════════════════════════════
    'biens'       => ['file'=>'immobilier/biens/index.php',       'category'=>'immobilier', 'label'=>'Biens',        'icon'=>'fa-building',         'order'=>50, 'enabled'=>true],
    'properties'  => ['file'=>'immobilier/properties/index.php',  'category'=>'immobilier', 'label'=>'Properties',   'icon'=>'fa-home',             'order'=>51, 'enabled'=>false],
    'estimation'  => ['file'=>'immobilier/estimation/index.php',  'category'=>'immobilier', 'label'=>'Estimations',  'icon'=>'fa-calculator',       'order'=>52, 'enabled'=>true],
    'financement' => ['file'=>'immobilier/financement/index.php', 'category'=>'immobilier', 'label'=>'Financement',  'icon'=>'fa-hand-holding-usd', 'order'=>53, 'enabled'=>true],
    'rdv'         => ['file'=>'immobilier/rdv/index.php',         'category'=>'immobilier', 'label'=>'Rendez-vous',  'icon'=>'fa-calendar-check',   'order'=>54, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  SEO & ANALYTICS — seo/
    // ═══════════════════════════════════════════
    'seo'          => ['file'=>'seo/seo/index.php',          'category'=>'seo', 'label'=>'SEO',             'icon'=>'fa-search',    'order'=>60, 'enabled'=>true],
    'seo-semantic' => ['file'=>'seo/seo-semantic/index.php', 'category'=>'seo', 'label'=>'SEO Sémantique',  'icon'=>'fa-brain',     'order'=>61, 'enabled'=>true],
    'local-seo'    => ['file'=>'seo/local-seo/index.php',    'category'=>'seo', 'label'=>'SEO Local / GMB', 'icon'=>'fa-map-pin',   'order'=>62, 'enabled'=>true],
    'analytics'    => ['file'=>'seo/analytics/index.php',    'category'=>'seo', 'label'=>'Analytics',        'icon'=>'fa-chart-bar', 'order'=>63, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  INTELLIGENCE IA — ai/
    // ═══════════════════════════════════════════
    'agents'       => ['file'=>'ai/agents/index.php',       'category'=>'ai', 'label'=>'Agents IA',         'icon'=>'fa-robot',       'order'=>70, 'enabled'=>true],
    'ia'           => ['file'=>'ai/ai/index.php',           'category'=>'ai', 'label'=>'Générateur IA',     'icon'=>'fa-magic',       'order'=>71, 'enabled'=>true],
    'ai-prompts'   => ['file'=>'ai/ai-prompts/index.php',   'category'=>'ai', 'label'=>'Prompts',           'icon'=>'fa-terminal',    'order'=>72, 'enabled'=>true],
    'neuropersona' => ['file'=>'ai/neuropersona/index.php',  'category'=>'ai', 'label'=>'NeuroPersona',      'icon'=>'fa-user-circle', 'order'=>73, 'enabled'=>true],
    'journal'      => ['file'=>'ai/journal/index.php',      'category'=>'ai', 'label'=>'Journal Éditorial', 'icon'=>'fa-book',        'order'=>74, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  RÉSEAU PRO — network/
    // ═══════════════════════════════════════════
    'contact'    => ['file'=>'network/contact/index.php',    'category'=>'network', 'label'=>'Contacts',      'icon'=>'fa-address-book', 'order'=>80, 'enabled'=>true],
    'scraper-gmb'=> ['file'=>'network/scraper-gmb/index.php','category'=>'network', 'label'=>'Scraper GMB',   'icon'=>'fa-crosshairs',   'order'=>81, 'enabled'=>true],
    'websites'   => ['file'=>'network/websites/index.php',   'category'=>'network', 'label'=>'Sites clients', 'icon'=>'fa-globe',        'order'=>82, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  BUILDER & DESIGN — builder/
    // ═══════════════════════════════════════════
    'builder'        => ['file'=>'builder/builder/index.php',  'category'=>'builder', 'label'=>'Website Builder', 'icon'=>'fa-bolt',            'order'=>90, 'enabled'=>true],
    'builder-editor' => ['file'=>'builder/builder/editor.php', 'category'=>'builder', 'label'=>'Éditeur',        'icon'=>'fa-edit',            'order'=>91, 'enabled'=>true, 'hidden'=>true],
    'builder-create' => ['file'=>'builder/builder/create.php', 'category'=>'builder', 'label'=>'Créer',          'icon'=>'fa-plus',            'order'=>92, 'enabled'=>true, 'hidden'=>true],
    'design-headers' => ['file'=>'builder/design/index.php',   'category'=>'builder', 'label'=>'Headers',        'icon'=>'fa-window-maximize', 'order'=>93, 'enabled'=>true, 'extra'=>['type'=>'headers']],
    'design-footers' => ['file'=>'builder/design/index.php',   'category'=>'builder', 'label'=>'Footers',        'icon'=>'fa-window-minimize', 'order'=>94, 'enabled'=>true, 'extra'=>['type'=>'footers']],
    'menus'          => ['file'=>'builder/menus/index.php',    'category'=>'builder', 'label'=>'Menus',          'icon'=>'fa-bars',            'order'=>95, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  STRATÉGIE — strategy/
    // ═══════════════════════════════════════════
    'strategy'   => ['file'=>'strategy/strategy/index.php',           'category'=>'strategy', 'label'=>'Stratégie',  'icon'=>'fa-chess',     'order'=>100, 'enabled'=>true],
    'launchpad'  => ['file'=>'strategy/launchpad/index.php',          'category'=>'strategy', 'label'=>'Launchpad',  'icon'=>'fa-rocket',    'order'=>101, 'enabled'=>true],
    'ressources' => ['file'=>'strategy/strategy/ressources/index.php','category'=>'strategy', 'label'=>'Ressources', 'icon'=>'fa-book-open', 'order'=>102, 'enabled'=>true],

    // ═══════════════════════════════════════════
    //  SYSTÈME — system/
    // ═══════════════════════════════════════════
    'settings'    => ['file'=>'system/settings/index.php',    'category'=>'system', 'label'=>'Paramètres',  'icon'=>'fa-cog',   'order'=>110, 'enabled'=>true],
    'maintenance' => ['file'=>'system/maintenance/index.php', 'category'=>'system', 'label'=>'Maintenance', 'icon'=>'fa-tools', 'order'=>111, 'enabled'=>true],
    'license'     => ['file'=>'license/index.php',            'category'=>'system', 'label'=>'Licence',     'icon'=>'fa-key',   'order'=>112, 'enabled'=>true],
];