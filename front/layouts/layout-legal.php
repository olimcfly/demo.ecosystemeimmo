<?php
/**
 * ========================================
 * LAYOUT LEGAL - COMPATIBLE CMS
 * ========================================
 * 
 * Fichier: /front/layouts/layout-legal.php
 * 
 * Variables attendues:
 * - $page_title, $meta_description
 * - $hero (array)
 * - $sections (array) - sections légales
 * - $updated_date (string)
 * 
 * ========================================
 */

$page_title = $page_title ?? 'Page légale';
$meta_description = $meta_description ?? '';
$hero = $hero ?? [];
$sections = $sections ?? [];
$updated_date = $updated_date ?? date('d/m/Y');
$inline_css = $inline_css ?? '';

$page_type = 'legal';

ob_start();
?>

<!-- ===== HERO LEGAL ===== -->
<section class="hero-legal">
    <div class="container">
        <h1><?php echo $hero['title'] ?? htmlspecialchars($page_title); ?></h1>
        <?php if (!empty($hero['subtitle'])): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ===== CONTENU LÉGAL ===== -->
<section class="section-legal">
    <div class="container container-narrow">
        
        <div class="legal-content">
            <?php foreach ($sections as $section): ?>
                <article class="legal-section" <?php echo !empty($section['id']) ? 'id="' . htmlspecialchars($section['id']) . '"' : ''; ?>>
                    
                    <?php if (!empty($section['title'])): ?>
                        <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($section['content'])): ?>
                        <div class="legal-text">
                            <?php echo $section['content']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php // Liste à puces ?>
                    <?php if (!empty($section['list'])): ?>
                        <ul class="legal-list">
                            <?php foreach ($section['list'] as $item): ?>
                                <li><?php echo $item; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <?php // Sous-sections ?>
                    <?php if (!empty($section['subsections'])): ?>
                        <?php foreach ($section['subsections'] as $sub): ?>
                            <div class="legal-subsection">
                                <?php if (!empty($sub['title'])): ?>
                                    <h3><?php echo htmlspecialchars($sub['title']); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($sub['content'])): ?>
                                    <?php echo $sub['content']; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php // Box info ?>
                    <?php if (!empty($section['info_box'])): ?>
                        <div class="info-box">
                            <?php if (!empty($section['info_box']['title'])): ?>
                                <h4><?php echo htmlspecialchars($section['info_box']['title']); ?></h4>
                            <?php endif; ?>
                            <?php echo $section['info_box']['content'] ?? ''; ?>
                        </div>
                    <?php endif; ?>
                    
                </article>
            <?php endforeach; ?>
            
            <!-- Date de mise à jour -->
            <footer class="legal-footer">
                <p>Dernière mise à jour : <?php echo htmlspecialchars($updated_date); ?></p>
            </footer>
            
        </div>
        
    </div>
</section>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/front.php';