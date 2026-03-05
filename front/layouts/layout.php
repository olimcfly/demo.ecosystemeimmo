<?php
/**
 * ========================================
 * FRONT.PHP - LAYOUT PRINCIPAL
 * ========================================
 * 
 * Fichier: /front/layouts/front.php
 * 
 * Ce fichier est inclus par tous les layouts
 * Il génère le HTML complet avec header/footer
 * 
 * Variables attendues:
 * - $page_title
 * - $meta_description
 * - $page_type (pour chargement CSS)
 * - $page_content (contenu HTML du layout)
 * - $inline_css (optionnel)
 * 
 * ========================================
 */

$page_title = $page_title ?? 'Eduardo De Sul - Immobilier Bordeaux';
$meta_description = $meta_description ?? '';
$meta_keywords = $meta_keywords ?? '';
$canonical_url = $canonical_url ?? '';
$page_type = $page_type ?? 'page';
$inline_css = $inline_css ?? '';

// Mapping des CSS selon le type de page
$css_mapping = [
    'landing' => ['style.css', 'components.css', 'landing.css'],
    'page'    => ['style.css', 'components.css', 'pages.css'],
    'legal'   => ['style.css', 'components.css', 'legal.css'],
    'secteur' => ['style.css', 'components.css', 'secteurs.css'],
    'form'    => ['style.css', 'components.css', 'forms.css'],
];

$css_files = $css_mapping[$page_type] ?? ['style.css', 'components.css'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php if ($meta_description): ?>
        <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    <?php if ($meta_keywords): ?>
        <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php endif; ?>
    <?php if ($canonical_url): ?>
        <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <?php endif; ?>
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    
    <!-- Favicon -->
    <link rel="icon" href="/front/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <?php foreach ($css_files as $css): ?>
        <link rel="stylesheet" href="/front/assets/css/<?php echo $css; ?>">
    <?php endforeach; ?>
    
    <!-- CSS personnalisé de la page -->
    <?php if ($inline_css): ?>
    <style>
        <?php echo $inline_css; ?>
    </style>
    <?php endif; ?>
</head>
<body class="page-<?php echo $page_type; ?>">

    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <a href="/" class="logo">
                    <img src="/front/assets/images/logo.png" alt="Eduardo De Sul" class="logo-img">
                    <span class="logo-text">Eduardo De Sul</span>
                </a>
                
                <!-- Navigation -->
                <nav class="nav-main" id="navMain">
                    <ul class="nav-list">
                        <li><a href="/vendre">Vendre</a></li>
                        <li><a href="/acheter">Acheter</a></li>
                        <li><a href="/investir">Investir</a></li>
                        <li><a href="/secteurs">Secteurs</a></li>
                        <li><a href="/a-propos">À propos</a></li>
                    </ul>
                </nav>
                
                <!-- CTA Header -->
                <div class="header-cta">
                    <a href="/estimation" class="btn btn-primary">Estimation gratuite</a>
                </div>
                
                <!-- Menu burger mobile -->
                <button class="nav-toggle" id="navToggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- ===== CONTENU PRINCIPAL ===== -->
    <main class="main">
        <?php echo $page_content ?? ''; ?>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Colonne 1: Logo & Description -->
                <div class="footer-col">
                    <a href="/" class="footer-logo">
                        <span>Eduardo De Sul</span>
                    </a>
                    <p>Conseiller immobilier indépendant eXp France. Votre partenaire de confiance pour vendre, acheter ou investir à Bordeaux et en Gironde.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                        <a href="#" aria-label="Instagram"><svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.5"/></svg></a>
                        <a href="#" aria-label="LinkedIn"><svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg></a>
                    </div>
                </div>
                
                <!-- Colonne 2: Services -->
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="/vendre">Vendre</a></li>
                        <li><a href="/acheter">Acheter</a></li>
                        <li><a href="/investir">Investir</a></li>
                        <li><a href="/financer">Financer</a></li>
                        <li><a href="/estimation">Estimation gratuite</a></li>
                    </ul>
                </div>
                
                <!-- Colonne 3: Secteurs -->
                <div class="footer-col">
                    <h4>Secteurs</h4>
                    <ul>
                        <li><a href="/bordeaux">Bordeaux</a></li>
                        <li><a href="/secteurs/chartrons-bordeaux">Les Chartrons</a></li>
                        <li><a href="/secteurs/cauderan-bordeaux">Caudéran</a></li>
                        <li><a href="/secteurs/bastide-bordeaux">La Bastide</a></li>
                        <li><a href="/secteurs">Tous les secteurs →</a></li>
                    </ul>
                </div>
                
                <!-- Colonne 4: Contact -->
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul class="footer-contact">
                        <li>📱 06 12 34 56 78</li>
                        <li>📧 contact@votre-domaine.fr</li>
                        <li>📍 123 Rue de la Paix<br>33000 Bordeaux</li>
                    </ul>
                </div>
            </div>
            
            <!-- Bas de page -->
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> Eduardo De Sul - Tous droits réservés</p>
                <ul class="footer-legal">
                    <li><a href="/mentions-legales">Mentions légales</a></li>
                    <li><a href="/politique-confidentialite">Confidentialité</a></li>
                    <li><a href="/cgu">CGU</a></li>
                    <li><a href="/honoraires">Honoraires</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <!-- ===== SCRIPTS ===== -->
    <script>
        // Menu mobile toggle
        document.getElementById('navToggle')?.addEventListener('click', function() {
            this.classList.toggle('active');
            document.getElementById('navMain').classList.toggle('active');
        });
        
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
    
    <?php if (!empty($footer_scripts)): ?>
        <?php echo $footer_scripts; ?>
    <?php endif; ?>

</body>
</html>