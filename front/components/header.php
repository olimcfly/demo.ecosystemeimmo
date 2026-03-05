<?php
/**
 * HEADER.PHP - HEADER DYNAMIQUE
 * Emplacement: /front/components/header.php
 * 
 * Récupère les liens depuis la BDD via le module Menus
 */

// Inclure les fonctions de menu
$menuFunctionsPath = __DIR__ . '/../includes/functions/menu-functions.php';
if (!file_exists($menuFunctionsPath)) {
    $menuFunctionsPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/functions/menu-functions.php';
}
if (file_exists($menuFunctionsPath)) {
    include_once $menuFunctionsPath;
}

// Récupérer les paramètres du site
$siteName = function_exists('get_setting') ? get_setting('footer_company_name', 'Eduardo De Sul') : 'Eduardo De Sul';
$siteTagline = function_exists('get_setting') ? get_setting('site_tagline', 'Conseiller Immobilier') : 'Conseiller Immobilier';

// Récupérer les liens du menu principal
$menuItems = function_exists('get_menu_items') ? get_menu_items('header-main') : [];

// Page courante pour le menu actif
$currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
?>

<header class="main-header">
    <div class="header-container">
        
        <!-- LOGO & BRANDING -->
        <div class="logo-section">
            <a href="/" class="logo-link">
                <span class="logo-text"><?php echo htmlspecialchars($siteName); ?></span>
                <span class="logo-subtitle"><?php echo htmlspecialchars($siteTagline); ?></span>
            </a>
        </div>

        <!-- NAVIGATION PRINCIPALE -->
        <nav class="main-nav" id="mainNav">
            
            <?php if (!empty($menuItems)): ?>
                <!-- MENU DYNAMIQUE DEPUIS LA BDD -->
                <?php foreach ($menuItems as $item): 
                    $isActive = ($currentUrl === $item['url'] || ($item['url'] !== '/' && strpos($currentUrl, $item['url']) === 0));
                    $activeClass = $isActive ? ' active' : '';
                    $extraClass = !empty($item['css_class']) ? ' ' . htmlspecialchars($item['css_class']) : '';
                    $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
                ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                       class="nav-link<?php echo $activeClass . $extraClass; ?>"<?php echo $target; ?>>
                        <?php if (!empty($item['icon'])): ?>
                            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- MENU PAR DÉFAUT (fallback si BDD vide) -->
                <a href="/" class="nav-link<?php echo $currentUrl === '/' ? ' active' : ''; ?>">Accueil</a>
                
                <!-- VENDRE -->
                <div class="dropdown">
                    <a href="/vendre" class="nav-link dropdown-toggle">
                        Vendre
                        <span class="dropdown-icon">▾</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="/vendre" class="dropdown-item">Guide vendre</a>
                        <a href="/strategie-vente" class="dropdown-item">Stratégie vente</a>
                        <a href="/honoraires" class="dropdown-item">Tarifs & honoraires</a>
                        <a href="/estimation" class="dropdown-item cta-item">Estimer mon bien</a>
                    </div>
                </div>

                <!-- ACHETER -->
                <div class="dropdown">
                    <a href="/acheter" class="nav-link dropdown-toggle">
                        Acheter
                        <span class="dropdown-icon">▾</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="/acheter" class="dropdown-item">Guide acheteur</a>
                        <a href="/investir" class="dropdown-item">Investir</a>
                        <a href="/financement" class="dropdown-item">Financement</a>
                    </div>
                </div>

                <!-- BORDEAUX -->
                <div class="dropdown">
                    <a href="/secteurs" class="nav-link dropdown-toggle">
                        Bordeaux
                        <span class="dropdown-icon">▾</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="/bordeaux" class="dropdown-item">Guide Bordeaux</a>
                        <a href="/secteurs" class="dropdown-item">Tous les secteurs</a>
                        <a href="/rapport-marche" class="dropdown-item">Rapport marché</a>
                        <a href="/prix-immobilier" class="dropdown-item">Prix au m²</a>
                    </div>
                </div>

                <!-- RESSOURCES -->
                <div class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle">
                        Ressources
                        <span class="dropdown-icon">▾</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="/a-propos" class="dropdown-item">À propos</a>
                        <a href="/contact" class="dropdown-item">Nous contacter</a>
                    </div>
                </div>

                <!-- CONTACT -->
                <a href="/contact" class="nav-link nav-contact">Contact</a>
            <?php endif; ?>

        </nav>

        <!-- BOUTON TOGGLE MOBILE -->
        <button class="mobile-menu-toggle" id="menuToggle" aria-label="Menu">
            <span class="hamburger"></span>
            <span class="hamburger"></span>
            <span class="hamburger"></span>
        </button>

    </div>
</header>

<style>
/* ========================================
   HEADER STYLES
   ======================================== */
.main-header {
    background: white;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 75px;
}

/* Logo */
.logo-section .logo-link {
    text-decoration: none;
    display: flex;
    flex-direction: column;
}

.logo-text {
    font-size: 22px;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.logo-subtitle {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
}

/* Navigation */
.main-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}

.nav-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    color: #1e293b;
    text-decoration: none;
    font-weight: 500;
    font-size: 15px;
    border-radius: 8px;
    transition: all 0.2s;
    white-space: nowrap;
}

.nav-link:hover,
.nav-link.active {
    background: #eef2ff;
    color: #6366f1;
}

.nav-link.nav-contact,
.nav-link.btn-cta {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white !important;
    margin-left: 8px;
}

.nav-link.nav-contact:hover,
.nav-link.btn-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.4);
}

/* Dropdowns */
.dropdown {
    position: relative;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-icon {
    font-size: 10px;
    margin-left: 4px;
    transition: transform 0.2s;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    padding: 8px;
    min-width: 200px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.2s;
    z-index: 100;
}

.dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown:hover .dropdown-icon {
    transform: rotate(180deg);
}

.dropdown-item {
    display: block;
    padding: 10px 14px;
    color: #1e293b;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background: #f1f5f9;
    color: #6366f1;
}

.dropdown-item.cta-item {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    margin-top: 8px;
    font-weight: 600;
}

.dropdown-item.cta-item:hover {
    transform: translateX(4px);
}

/* Mobile Toggle */
.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    gap: 5px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    z-index: 1001;
}

.hamburger {
    width: 24px;
    height: 3px;
    background: #1e293b;
    border-radius: 2px;
    transition: all 0.3s;
}

.mobile-menu-toggle.active .hamburger:nth-child(1) {
    transform: rotate(45deg) translate(5px, 6px);
}

.mobile-menu-toggle.active .hamburger:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active .hamburger:nth-child(3) {
    transform: rotate(-45deg) translate(5px, -6px);
}

/* Responsive */
@media (max-width: 968px) {
    .mobile-menu-toggle {
        display: flex;
    }
    
    .main-nav {
        display: none;
        position: fixed;
        top: 75px;
        left: 0;
        right: 0;
        bottom: 0;
        background: white;
        flex-direction: column;
        padding: 20px;
        gap: 8px;
        overflow-y: auto;
    }
    
    .main-nav.active {
        display: flex;
    }
    
    .nav-link {
        width: 100%;
        padding: 14px 16px;
        justify-content: space-between;
    }
    
    .dropdown {
        width: 100%;
    }
    
    .dropdown-menu {
        position: static;
        box-shadow: none;
        opacity: 1;
        visibility: visible;
        transform: none;
        display: none;
        padding-left: 16px;
        background: #f8fafc;
        margin-top: 4px;
    }
    
    .dropdown.active .dropdown-menu {
        display: block;
    }
    
    .dropdown:hover .dropdown-menu {
        display: none;
    }
    
    .dropdown.active .dropdown-icon {
        transform: rotate(180deg);
    }
    
    .nav-link.nav-contact {
        margin: 16px 0 0 0;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');

    if (menuToggle && mainNav) {
        // Toggle menu mobile
        menuToggle.addEventListener('click', function() {
            menuToggle.classList.toggle('active');
            mainNav.classList.toggle('active');
            document.body.style.overflow = mainNav.classList.contains('active') ? 'hidden' : '';
        });

        // Fermer en cliquant sur un lien (sauf dropdown toggle)
        mainNav.querySelectorAll('a:not(.dropdown-toggle)').forEach(link => {
            link.addEventListener('click', function() {
                menuToggle.classList.remove('active');
                mainNav.classList.remove('active');
                document.body.style.overflow = '';
            });
        });

        // Gestion dropdowns mobile
        const dropdowns = mainNav.querySelectorAll('.dropdown');
        dropdowns.forEach(dropdown => {
            const toggle = dropdown.querySelector('.dropdown-toggle');
            if (toggle) {
                toggle.addEventListener('click', function(e) {
                    if (window.innerWidth <= 968) {
                        e.preventDefault();
                        // Fermer les autres dropdowns
                        dropdowns.forEach(d => {
                            if (d !== dropdown) d.classList.remove('active');
                        });
                        dropdown.classList.toggle('active');
                    }
                });
            }
        });

        // Fermer menu si on clique en dehors
        document.addEventListener('click', function(e) {
            if (!mainNav.contains(e.target) && !menuToggle.contains(e.target)) {
                menuToggle.classList.remove('active');
                mainNav.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
});
</script>