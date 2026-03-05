<?php
/**
 * ========================================
 * LAYOUT SECTEUR - COMPATIBLE CMS
 * ========================================
 * 
 * Fichier: /front/layouts/layout-secteur.php
 * 
 * Variables attendues:
 * - $page_title, $meta_description
 * - $hero (array)
 * - $content_main (string HTML)
 * - $quartier (array) - données du quartier
 * - $sidebar_links (array)
 * - $cta_final (array)
 * 
 * ========================================
 */

$page_title = $page_title ?? 'Secteur';
$meta_description = $meta_description ?? '';
$hero = $hero ?? [];
$content_main = $content_main ?? '';
$quartier = $quartier ?? [];
$sidebar_links = $sidebar_links ?? [];
$cta_final = $cta_final ?? [];
$inline_css = $inline_css ?? '';

$page_type = 'secteur';

// Valeurs par défaut quartier
$quartier = array_merge([
    'nom' => 'Quartier',
    'ville' => 'Bordeaux',
    'code_postal' => '',
    'prix_min' => 0,
    'prix_max' => 0,
    'prix_moyen' => 0,
    'rendement_min' => 0,
    'rendement_max' => 0,
], $quartier);

ob_start();
?>

<!-- ===== HERO SECTEUR ===== -->
<section class="hero-secteur">
    <div class="hero-background">
        <?php if (!empty($quartier['hero_image'])): ?>
            <img src="<?php echo htmlspecialchars($quartier['hero_image']); ?>" alt="<?php echo htmlspecialchars($quartier['nom']); ?>">
        <?php endif; ?>
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content container">
        <?php if (!empty($hero['subtitle_top'])): ?>
            <span class="hero-badge"><?php echo htmlspecialchars($hero['subtitle_top']); ?></span>
        <?php endif; ?>
        
        <h1><?php echo $hero['title'] ?? htmlspecialchars($quartier['nom']); ?></h1>
        
        <?php if (!empty($hero['subtitle'])): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></p>
        <?php endif; ?>
        
        <!-- Données clés du quartier -->
        <?php if ($quartier['prix_min'] > 0): ?>
        <div class="hero-data-cards">
            <div class="data-card">
                <span class="data-value"><?php echo number_format($quartier['prix_min'], 0, ',', ' '); ?> - <?php echo number_format($quartier['prix_max'], 0, ',', ' '); ?> €/m²</span>
                <span class="data-label">Prix au m²</span>
            </div>
            <div class="data-card">
                <span class="data-value"><?php echo $quartier['rendement_min']; ?> - <?php echo $quartier['rendement_max']; ?>%</span>
                <span class="data-label">Rendement locatif</span>
            </div>
            <div class="data-card">
                <span class="data-value"><?php echo htmlspecialchars($quartier['ville']); ?></span>
                <span class="data-label"><?php echo htmlspecialchars($quartier['code_postal']); ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== BREADCRUMB ===== -->
<nav class="breadcrumb-nav">
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="/">Accueil</a></li>
            <li><a href="/secteurs">Secteurs</a></li>
            <li class="active"><?php echo htmlspecialchars($quartier['nom']); ?></li>
        </ol>
    </div>
</nav>

<!-- ===== CONTENU PRINCIPAL ===== -->
<section class="section-content">
    <div class="container">
        <div class="page-layout with-sidebar">
            
            <!-- Contenu principal -->
            <main class="main-content">
                <?php if (!empty($content_main)): ?>
                    <div class="article-content">
                        <?php echo $content_main; ?>
                    </div>
                <?php endif; ?>
            </main>
            
            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Bloc Estimation -->
                <div class="sidebar-box sidebar-estimation">
                    <h4>📊 Estimer mon bien</h4>
                    <p>Estimation gratuite à <?php echo htmlspecialchars($quartier['nom']); ?></p>
                    <a href="/estimation?secteur=<?php echo htmlspecialchars($quartier['slug'] ?? ''); ?>" class="btn btn-primary btn-block">
                        Estimation gratuite →
                    </a>
                </div>
                
                <!-- Données marché -->
                <?php if ($quartier['prix_moyen'] > 0): ?>
                <div class="sidebar-box sidebar-market">
                    <h4>📈 Marché immobilier</h4>
                    <ul class="market-data">
                        <li>
                            <span class="label">Prix moyen</span>
                            <span class="value"><?php echo number_format($quartier['prix_moyen'], 0, ',', ' '); ?> €/m²</span>
                        </li>
                        <li>
                            <span class="label">Fourchette</span>
                            <span class="value"><?php echo number_format($quartier['prix_min'], 0, ',', ' '); ?> - <?php echo number_format($quartier['prix_max'], 0, ',', ' '); ?> €</span>
                        </li>
                        <li>
                            <span class="label">Rendement</span>
                            <span class="value"><?php echo $quartier['rendement_min']; ?> - <?php echo $quartier['rendement_max']; ?>%</span>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Liens quartiers -->
                <?php if (!empty($sidebar_links)): ?>
                <div class="sidebar-box sidebar-links">
                    <h4>📍 Autres quartiers</h4>
                    <ul>
                        <?php foreach ($sidebar_links as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['label']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Contact rapide -->
                <div class="sidebar-box sidebar-contact">
                    <h4>📞 Une question ?</h4>
                    <p>Contactez-moi pour discuter de votre projet à <?php echo htmlspecialchars($quartier['nom']); ?></p>
                    <a href="/contact?secteur=<?php echo htmlspecialchars($quartier['slug'] ?? ''); ?>" class="btn btn-outline btn-block">
                        Me contacter →
                    </a>
                </div>
            </aside>
            
        </div>
    </div>
</section>

<!-- ===== CTA FINAL ===== -->
<?php if (!empty($cta_final['title'])): ?>
<section class="section-cta">
    <div class="container">
        <div class="cta-box">
            <h2><?php echo htmlspecialchars($cta_final['title']); ?></h2>
            <?php if (!empty($cta_final['text'])): ?>
                <p><?php echo htmlspecialchars($cta_final['text']); ?></p>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($cta_final['button_url'] ?? '/contact'); ?>" class="btn btn-primary btn-lg">
                <?php echo htmlspecialchars($cta_final['button_text'] ?? 'Prendre rendez-vous'); ?>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Schema.org pour SEO local -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Place",
    "name": "<?php echo htmlspecialchars($quartier['nom']); ?>",
    "address": {
        "@type": "PostalAddress",
        "addressLocality": "<?php echo htmlspecialchars($quartier['ville']); ?>",
        "postalCode": "<?php echo htmlspecialchars($quartier['code_postal']); ?>",
        "addressCountry": "FR"
    }
}
</script>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/front.php';