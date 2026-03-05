<?php
/**
 * ══════════════════════════════════════════════════════════════
 * sidebar.php — Navigation admin complete
 * IMMO LOCAL+ — Eduardo De Sul
 * Fichier : admin/includes/sidebar.php
 *
 * Integre : Journal Editorial V3 (section + sous-items canaux)
 * ══════════════════════════════════════════════════════════════
 */

$modules = [
    'dashboard' => ['icon' => 'fas fa-home', 'label' => 'Dashboard', 'url' => '/admin/dashboard.php'],
    
    'blog' => ['icon' => 'fas fa-blog', 'label' => 'Blog & Articles', 'submenu' => [
        'articles' => ['icon' => 'fas fa-newspaper', 'label' => 'Articles', 'url' => '/admin/modules/articles/'],
        'categories' => ['icon' => 'fas fa-tags', 'label' => 'Categories', 'url' => '/admin/modules/articles/categories.php'],
        'articles-journal' => ['icon' => 'fas fa-pen-fancy', 'label' => '↳ Journal Blog', 'url' => '?page=articles-journal', 'class' => 'sidebar-subitem-link'],
    ]],
    
    'immobilier' => ['icon' => 'fas fa-building', 'label' => 'Immobilier', 'submenu' => [
        'biens' => ['icon' => 'fas fa-home', 'label' => 'Biens', 'url' => '/admin/modules/properties/'],
        'mandats' => ['icon' => 'fas fa-file-contract', 'label' => 'Mandats', 'url' => '/admin/modules/mandats/'],
        'estimations' => ['icon' => 'fas fa-calculator', 'label' => 'Estimations', 'url' => '/admin/modules/estimations/'],
        'ventes' => ['icon' => 'fas fa-handshake', 'label' => 'Ventes', 'url' => '/admin/modules/ventes/'],
    ]],
    
    'leads' => ['icon' => 'fas fa-users', 'label' => 'Prospects & Leads', 'submenu' => [
        'leads' => ['icon' => 'fas fa-user-tie', 'label' => 'Tous les leads', 'url' => '/admin/modules/leads/'],
        'contacts' => ['icon' => 'fas fa-address-book', 'label' => 'Contacts', 'url' => '/admin/modules/contacts/'],
        'mailing' => ['icon' => 'fas fa-envelope', 'label' => 'Mailing List', 'url' => '/admin/modules/mailing_list/'],
    ]],

    // ═══════════════════════════════════════
    // JOURNAL EDITORIAL V3 — Section dediee
    // ═══════════════════════════════════════
    'strategie' => ['icon' => 'fas fa-newspaper', 'label' => 'Strategie Contenu', 'submenu' => [
        'journal' => ['icon' => 'fas fa-th-large', 'label' => 'Vue Globale', 'url' => '?page=journal', 'badge' => 'NEW'],
        'journal-matrice' => ['icon' => 'fas fa-border-all', 'label' => 'Matrice Strategique', 'url' => '?page=journal&tab=matrice'],
        'journal-generate' => ['icon' => 'fas fa-magic', 'label' => 'Generateur IA', 'url' => '?page=journal&tab=generate', 'badge' => 'IA'],
        'journal-perf' => ['icon' => 'fas fa-chart-bar', 'label' => 'Performance', 'url' => '?page=journal&tab=performance'],
    ]],
    
    'marketing' => ['icon' => 'fas fa-bullhorn', 'label' => 'Marketing', 'submenu' => [
        'captures' => ['icon' => 'fas fa-window-restore', 'label' => 'Landing Pages', 'url' => '/admin/modules/captures/'],
        'campagnes' => ['icon' => 'fas fa-rocket', 'label' => 'Campagnes', 'url' => '/admin/modules/campagnes/'],
        'seo' => ['icon' => 'fas fa-search', 'label' => 'SEO', 'url' => '/admin/modules/seo/'],
        'social' => ['icon' => 'fas fa-share-alt', 'label' => 'Reseaux Sociaux', 'url' => '/admin/modules/social/'],
        // --- Sous-items journal reseaux sociaux ---
        'facebook-journal' => ['icon' => 'fab fa-facebook', 'label' => '↳ Journal Facebook', 'url' => '?page=facebook-journal', 'class' => 'sidebar-subitem-link'],
        'instagram-journal' => ['icon' => 'fab fa-instagram', 'label' => '↳ Journal Instagram', 'url' => '?page=instagram-journal', 'class' => 'sidebar-subitem-link'],
        'tiktok-journal' => ['icon' => 'fab fa-tiktok', 'label' => '↳ Journal TikTok', 'url' => '?page=tiktok-journal', 'class' => 'sidebar-subitem-link'],
        'linkedin-journal' => ['icon' => 'fab fa-linkedin', 'label' => '↳ Journal LinkedIn', 'url' => '?page=linkedin-journal', 'class' => 'sidebar-subitem-link'],
        'analytics' => ['icon' => 'fas fa-chart-line', 'label' => 'Analytics', 'url' => '/admin/modules/analytics/'],
        'email' => ['icon' => 'fas fa-mail-bulk', 'label' => 'Email', 'url' => '/admin/modules/email/'],
        'emails-journal' => ['icon' => 'fas fa-envelope', 'label' => '↳ Journal Emails', 'url' => '?page=emails-journal', 'class' => 'sidebar-subitem-link'],
        'sms' => ['icon' => 'fas fa-sms', 'label' => 'SMS', 'url' => '/admin/modules/sms/'],
    ]],

    'local-seo' => ['icon' => 'fas fa-map-marker-alt', 'label' => 'SEO Local', 'submenu' => [
        'local-seo-main' => ['icon' => 'fas fa-map-pin', 'label' => 'Google My Business', 'url' => '/admin/modules/local-seo/'],
        'local-gmb-journal' => ['icon' => 'fas fa-map-marker-alt', 'label' => '↳ Journal GMB', 'url' => '?page=local-gmb-journal', 'class' => 'sidebar-subitem-link'],
    ]],
    
    'ia' => ['icon' => 'fas fa-brain', 'label' => 'Intelligence Artificielle', 'submenu' => [
        'ia_general' => ['icon' => 'fas fa-wand-magic-sparkles', 'label' => 'Outils IA', 'url' => '/admin/modules/ai/'],
        'prompts' => ['icon' => 'fas fa-lightbulb', 'label' => 'Prompts', 'url' => '/admin/modules/ai/prompts.php'],
        'integrations' => ['icon' => 'fas fa-plug', 'label' => 'Integrations', 'url' => '/admin/modules/integrations/'],
    ]],
    
    'contenu' => ['icon' => 'fas fa-file-alt', 'label' => 'Contenu', 'submenu' => [
        'pages' => ['icon' => 'fas fa-file', 'label' => 'Pages', 'url' => '/admin/modules/pages/'],
        'guides' => ['icon' => 'fas fa-book', 'label' => 'Guides', 'url' => '/admin/modules/guides/'],
        'medias' => ['icon' => 'fas fa-image', 'label' => 'Medias', 'url' => '/admin/modules/medias/'],
        'videos' => ['icon' => 'fas fa-video', 'label' => 'Videos', 'url' => '/admin/modules/videos/'],
    ]],
    
    'donnees' => ['icon' => 'fas fa-database', 'label' => 'Donnees & CRM', 'submenu' => [
        'rdv' => ['icon' => 'fas fa-calendar', 'label' => 'RDV', 'url' => '/admin/modules/rdv/'],
        'users' => ['icon' => 'fas fa-user-circle', 'label' => 'Utilisateurs', 'url' => '/admin/modules/users/'],
        'partenaires' => ['icon' => 'fas fa-handshake', 'label' => 'Partenaires', 'url' => '/admin/modules/partenaires/'],
    ]],
    
    'outils' => ['icon' => 'fas fa-tools', 'label' => 'Outils & Utilitaires', 'submenu' => [
        'settings' => ['icon' => 'fas fa-cog', 'label' => 'Parametres', 'url' => '/admin/modules/settings/'],
        'launchpad' => ['icon' => 'fas fa-rocket', 'label' => 'Launchpad', 'url' => '/admin/modules/launchpad/'],
        'maintenance' => ['icon' => 'fas fa-wrench', 'label' => 'Maintenance', 'url' => '/admin/modules/maintenance/'],
    ]],
];

// Detection page active
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_query_page = $_GET['page'] ?? '';
$current_tab = $_GET['tab'] ?? '';

/**
 * Determiner si un item est actif
 */
function isItemActive(string $key, string $url, string $currentPage, string $queryPage, string $tab): bool
{
    // Pages journal avec ?page=xxx
    $journalPages = [
        'journal', 'journal-matrice', 'journal-generate', 'journal-perf',
        'articles-journal', 'local-gmb-journal', 'facebook-journal',
        'instagram-journal', 'tiktok-journal', 'linkedin-journal', 'emails-journal'
    ];

    if (in_array($key, $journalPages)) {
        // Pages hub journal
        if ($key === 'journal' && $queryPage === 'journal' && empty($tab)) return true;
        if ($key === 'journal-matrice' && $queryPage === 'journal' && $tab === 'matrice') return true;
        if ($key === 'journal-generate' && $queryPage === 'journal' && $tab === 'generate') return true;
        if ($key === 'journal-perf' && $queryPage === 'journal' && $tab === 'performance') return true;
        
        // Pages canal journal
        if ($key === $queryPage) return true;
        
        return false;
    }

    // Pages classiques
    if (!empty($url) && $url !== '#') {
        $urlPath = parse_url($url, PHP_URL_PATH);
        $urlBase = basename($urlPath ?? '', '.php');
        if ($urlBase === $currentPage) return true;
    }

    return false;
}

/**
 * Determiner si une section parente a un item actif
 */
function isSectionActive(array $submenu, string $currentPage, string $queryPage, string $tab): bool
{
    foreach ($submenu as $key => $item) {
        if (isItemActive($key, $item['url'] ?? '', $currentPage, $queryPage, $tab)) {
            return true;
        }
    }
    return false;
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-home"></i>
            <span>Eduardo De Sul</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($modules as $key => $module): 
            $hasSubmenu = isset($module['submenu']);
            $sectionActive = $hasSubmenu 
                ? isSectionActive($module['submenu'], $current_page, $current_query_page, $current_tab) 
                : isItemActive($key, $module['url'] ?? '', $current_page, $current_query_page, $current_tab);
        ?>
            <div class="nav-section <?= $sectionActive ? 'open' : '' ?>">
                <!-- Item principal -->
                <div class="nav-item <?= $sectionActive ? 'active' : '' ?>"
                     <?= $hasSubmenu ? 'onclick="toggleSubmenu(this)"' : '' ?>>
                    <a href="<?= $module['url'] ?? '#' ?>">
                        <i class="<?= $module['icon'] ?>"></i>
                        <span class="nav-label"><?= $module['label'] ?></span>
                        <?php if ($hasSubmenu): ?>
                            <i class="fas fa-chevron-down arrow"></i>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Sous-menu -->
                <?php if ($hasSubmenu): ?>
                    <div class="submenu" <?= $sectionActive ? 'style="display:block;"' : '' ?>>
                        <?php foreach ($module['submenu'] as $subKey => $sub): 
                            $subActive = isItemActive($subKey, $sub['url'] ?? '', $current_page, $current_query_page, $current_tab);
                            $isSubitem = isset($sub['class']) && $sub['class'] === 'sidebar-subitem-link';
                            $hasBadge  = isset($sub['badge']);
                        ?>
                            <a href="<?= $sub['url'] ?>" 
                               class="submenu-item <?= $subActive ? 'active' : '' ?> <?= $isSubitem ? 'submenu-subitem' : '' ?>">
                                <i class="<?= $sub['icon'] ?>" <?= $isSubitem ? 'style="font-size:.7rem;"' : '' ?>></i>
                                <span><?= $sub['label'] ?></span>
                                <?php if ($hasBadge): ?>
                                    <span class="badge-<?= strtolower($sub['badge']) === 'ia' ? 'ia' : 'new' ?>">
                                        <?= $sub['badge'] ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode(get_admin_email()) ?>&background=667eea&color=fff" alt="Avatar">
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars(explode('@', get_admin_email())[0]) ?></div>
                <small class="user-email"><?= htmlspecialchars(get_admin_email()) ?></small>
            </div>
        </div>
        <a href="/admin/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Deconnexion</span>
        </a>
    </div>
</aside>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- CSS ADDITIONS — Journal V3 sidebar styles                    -->
<!-- ══════════════════════════════════════════════════════════════ -->
<style>
/* Sous-items journal (liens ↳ Journal xxx) */
.submenu-subitem {
    padding-left: 38px !important;
    font-size: .82rem !important;
    opacity: .85;
}
.submenu-subitem:hover {
    opacity: 1;
}
.submenu-subitem.active {
    opacity: 1;
    font-weight: 600;
}
.submenu-subitem i {
    width: 14px;
    text-align: center;
}

/* Section ouverte automatiquement */
.nav-section.open .submenu {
    display: block;
}
.nav-section.open .arrow {
    transform: rotate(180deg);
}

/* Badge NEW */
.badge-new {
    background: #2ecc71;
    color: #fff;
    font-size: .58rem;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
    margin-left: auto;
    text-transform: uppercase;
    letter-spacing: .5px;
    line-height: 1.6;
}

/* Badge IA */
.badge-ia {
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    color: #fff;
    font-size: .58rem;
    padding: 1px 6px;
    border-radius: 10px;
    font-weight: 700;
    margin-left: auto;
    text-transform: uppercase;
    letter-spacing: .5px;
    line-height: 1.6;
}
</style>