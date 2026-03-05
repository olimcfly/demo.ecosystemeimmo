<?php
/**
 * ========================================
 * LAYOUT PAGE - COMPATIBLE CMS
 * ========================================
 * 
 * Fichier: /front/layouts/layout-page.php
 * 
 * Variables attendues:
 * - $page_title, $meta_description
 * - $hero (array)
 * - $content_main (string HTML)
 * - $sidebar (array)
 * - $sections (array)
 * - $cta_final (array)
 * - $breadcrumb (array)
 * 
 * ========================================
 */

$page_title = $page_title ?? 'Page';
$meta_description = $meta_description ?? '';
$hero = $hero ?? [];
$content_main = $content_main ?? '';
$sidebar = $sidebar ?? [];
$sections = $sections ?? [];
$cta_final = $cta_final ?? [];
$breadcrumb = $breadcrumb ?? [];
$inline_css = $inline_css ?? '';

$page_type = 'page';

ob_start();
?>

<!-- ===== HERO PAGE ===== -->
<section class="hero-page">
    <div class="container">
        <?php if (!empty($hero['subtitle_top'])): ?>
            <span class="hero-badge"><?php echo htmlspecialchars($hero['subtitle_top']); ?></span>
        <?php endif; ?>
        
        <h1><?php echo $hero['title'] ?? htmlspecialchars($page_title); ?></h1>
        
        <?php if (!empty($hero['subtitle'])): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== BREADCRUMB ===== -->
<?php if (!empty($breadcrumb)): ?>
<nav class="breadcrumb-nav">
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="/">Accueil</a></li>
            <?php foreach ($breadcrumb as $crumb): ?>
                <?php if (!empty($crumb['url'])): ?>
                    <li><a href="<?php echo htmlspecialchars($crumb['url']); ?>"><?php echo htmlspecialchars($crumb['label']); ?></a></li>
                <?php else: ?>
                    <li class="active"><?php echo htmlspecialchars($crumb['label']); ?></li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
<?php endif; ?>

<!-- ===== CONTENU PRINCIPAL ===== -->
<section class="section-content">
    <div class="container">
        <div class="page-layout <?php echo !empty($sidebar) ? 'with-sidebar' : ''; ?>">
            
            <!-- Contenu -->
            <main class="main-content">
                <?php if (!empty($content_main)): ?>
                    <article class="article-content">
                        <?php echo $content_main; ?>
                    </article>
                <?php endif; ?>
            </main>
            
            <!-- Sidebar -->
            <?php if (!empty($sidebar)): ?>
            <aside class="sidebar">
                <?php // Bloc Estimation ?>
                <?php if (!empty($sidebar['estimation'])): ?>
                    <div class="sidebar-box sidebar-estimation">
                        <h4><?php echo htmlspecialchars($sidebar['estimation']['title'] ?? '📊 Estimation gratuite'); ?></h4>
                        <?php if (!empty($sidebar['estimation']['text'])): ?>
                            <p><?php echo htmlspecialchars($sidebar['estimation']['text']); ?></p>
                        <?php endif; ?>
                        <a href="<?php echo htmlspecialchars($sidebar['estimation']['url'] ?? '/estimation'); ?>" class="btn btn-primary btn-block">
                            <?php echo htmlspecialchars($sidebar['estimation']['button'] ?? 'Estimer mon bien →'); ?>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php // Bloc Contact ?>
                <?php if (!empty($sidebar['phone']) || !empty($sidebar['email'])): ?>
                    <div class="sidebar-box sidebar-contact">
                        <h4><?php echo htmlspecialchars($sidebar['title'] ?? '📞 Me contacter'); ?></h4>
                        <?php if (!empty($sidebar['phone'])): ?>
                            <p><strong>Téléphone</strong><br>
                            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $sidebar['phone']); ?>"><?php echo htmlspecialchars($sidebar['phone']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($sidebar['email'])): ?>
                            <p><strong>Email</strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($sidebar['email']); ?>"><?php echo htmlspecialchars($sidebar['email']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($sidebar['address'])): ?>
                            <p><strong>Adresse</strong><br>
                            <?php echo nl2br(htmlspecialchars($sidebar['address'])); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php // Bloc Liens ?>
                <?php if (!empty($sidebar['links'])): ?>
                    <div class="sidebar-box sidebar-links">
                        <h4><?php echo htmlspecialchars($sidebar['links']['title'] ?? '📍 Liens utiles'); ?></h4>
                        <ul>
                            <?php foreach ($sidebar['links']['items'] ?? [] as $link): ?>
                                <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['label']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php // Bloc Info ?>
                <?php if (!empty($sidebar['info_box'])): ?>
                    <div class="sidebar-box sidebar-info">
                        <h4><?php echo htmlspecialchars($sidebar['info_box']['title'] ?? '💡 Information'); ?></h4>
                        <div><?php echo $sidebar['info_box']['content'] ?? ''; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php // Contenu custom ?>
                <?php if (!empty($sidebar['custom'])): ?>
                    <?php echo $sidebar['custom']; ?>
                <?php endif; ?>
            </aside>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<!-- ===== SECTIONS ADDITIONNELLES ===== -->
<?php foreach ($sections as $section): ?>
    <?php 
    $sectionBg = $section['bg'] ?? 'white';
    $sectionType = $section['type'] ?? 'content';
    ?>
    
    <section class="section section-<?php echo $sectionBg; ?>">
        <div class="container">
            <?php if (!empty($section['title'])): ?>
                <div class="section-header">
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                </div>
            <?php endif; ?>
            
            <?php if ($sectionType === 'cards' && !empty($section['cards'])): ?>
                <div class="cards-wrapper cols-<?php echo $section['cols'] ?? 3; ?>">
                    <?php foreach ($section['cards'] as $card): ?>
                        <div class="card">
                            <?php if (!empty($card['icon'])): ?><div class="card-icon"><?php echo $card['icon']; ?></div><?php endif; ?>
                            <?php if (!empty($card['title'])): ?><h3><?php echo htmlspecialchars($card['title']); ?></h3><?php endif; ?>
                            <?php if (!empty($card['text'])): ?><p><?php echo $card['text']; ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($section['content'])): ?>
                <div class="content-block"><?php echo $section['content']; ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>

<!-- ===== CTA FINAL ===== -->
<?php if (!empty($cta_final['title'])): ?>
<section class="section-cta">
    <div class="container">
        <div class="cta-box">
            <h2><?php echo htmlspecialchars($cta_final['title']); ?></h2>
            <?php if (!empty($cta_final['text'])): ?>
                <p><?php echo htmlspecialchars($cta_final['text']); ?></p>
            <?php endif; ?>
            <?php if (!empty($cta_final['button_text'])): ?>
                <a href="<?php echo htmlspecialchars($cta_final['button_url'] ?? '/contact'); ?>" class="btn btn-primary btn-lg">
                    <?php echo htmlspecialchars($cta_final['button_text']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/front.php';