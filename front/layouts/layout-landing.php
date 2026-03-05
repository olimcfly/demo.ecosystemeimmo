<?php
/**
 * ========================================
 * LAYOUT LANDING - COMPATIBLE CMS
 * ========================================
 * 
 * Fichier: /front/layouts/layout-landing.php
 * 
 * Variables attendues (de page.php ou définies manuellement):
 * - $page_title, $meta_description
 * - $hero (array)
 * - $sections (array)
 * - $cta_final (array)
 * - $inline_css (string)
 * 
 * ========================================
 */

// Vérifier que les variables existent
$page_title = $page_title ?? 'Page';
$meta_description = $meta_description ?? '';
$hero = $hero ?? [];
$sections = $sections ?? [];
$cta_final = $cta_final ?? [];
$inline_css = $inline_css ?? '';

// Type de page pour le CSS
$page_type = 'landing';

// Démarrer la capture du contenu
ob_start();
?>

<!-- ===== HERO LANDING ===== -->
<section class="hero-landing">
    <div class="hero-background">
        <?php if (!empty($hero['image'])): ?>
            <img src="<?php echo htmlspecialchars($hero['image']); ?>" alt="<?php echo htmlspecialchars($page_title); ?>">
        <?php endif; ?>
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content container">
        <?php if (!empty($hero['subtitle_top'])): ?>
            <span class="hero-badge"><?php echo htmlspecialchars($hero['subtitle_top']); ?></span>
        <?php endif; ?>
        
        <?php if (!empty($hero['title'])): ?>
            <h1><?php echo $hero['title']; ?></h1>
        <?php endif; ?>
        
        <?php if (!empty($hero['subtitle'])): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($hero['boxes'])): ?>
            <div class="hero-boxes">
                <?php foreach ($hero['boxes'] as $box): ?>
                    <div class="hero-box">
                        <strong><?php echo htmlspecialchars($box['title']); ?></strong>
                        <span><?php echo htmlspecialchars($box['text']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($hero['cta_primary']) || !empty($hero['cta_secondary'])): ?>
            <div class="hero-ctas">
                <?php if (!empty($hero['cta_primary']['text'])): ?>
                    <a href="<?php echo htmlspecialchars($hero['cta_primary']['url'] ?? '#'); ?>" class="btn btn-primary btn-lg">
                        <?php echo htmlspecialchars($hero['cta_primary']['text']); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($hero['cta_secondary']['text'])): ?>
                    <a href="<?php echo htmlspecialchars($hero['cta_secondary']['url'] ?? '#'); ?>" class="btn btn-outline btn-lg">
                        <?php echo htmlspecialchars($hero['cta_secondary']['text']); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== SECTIONS DYNAMIQUES ===== -->
<?php foreach ($sections as $section): ?>
    <?php 
    $sectionId = $section['id'] ?? '';
    $sectionBg = $section['bg'] ?? 'white';
    $sectionType = $section['type'] ?? 'content';
    ?>
    
    <section class="section section-<?php echo $sectionBg; ?>" <?php echo $sectionId ? 'id="' . htmlspecialchars($sectionId) . '"' : ''; ?>>
        <div class="container">
            
            <?php if (!empty($section['title'])): ?>
                <div class="section-header">
                    <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                    <?php if (!empty($section['subtitle'])): ?>
                        <p><?php echo htmlspecialchars($section['subtitle']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php // === TYPE: CARDS === ?>
            <?php if ($sectionType === 'cards' && !empty($section['cards'])): ?>
                <div class="cards-wrapper cols-<?php echo $section['cols'] ?? 3; ?>">
                    <?php foreach ($section['cards'] as $card): ?>
                        <div class="card">
                            <?php if (!empty($card['icon'])): ?>
                                <div class="card-icon"><?php echo $card['icon']; ?></div>
                            <?php endif; ?>
                            <?php if (!empty($card['title'])): ?>
                                <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                            <?php endif; ?>
                            <?php if (!empty($card['text'])): ?>
                                <p><?php echo $card['text']; ?></p>
                            <?php endif; ?>
                            <?php if (!empty($card['link'])): ?>
                                <a href="<?php echo htmlspecialchars($card['link']['url'] ?? '#'); ?>" class="card-link">
                                    <?php echo htmlspecialchars($card['link']['text'] ?? 'En savoir plus →'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php // === TYPE: STEPS === ?>
            <?php if ($sectionType === 'steps' && !empty($section['steps'])): ?>
                <div class="steps-wrapper">
                    <?php foreach ($section['steps'] as $index => $step): ?>
                        <div class="step">
                            <div class="step-number"><?php echo $index + 1; ?></div>
                            <div class="step-content">
                                <h3><?php echo htmlspecialchars($step['title']); ?></h3>
                                <p><?php echo htmlspecialchars($step['text']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php // === TYPE: HIGHLIGHT === ?>
            <?php if ($sectionType === 'highlight'): ?>
                <div class="highlight-box">
                    <?php if (!empty($section['highlight_title'])): ?>
                        <h3><?php echo htmlspecialchars($section['highlight_title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($section['content'])): ?>
                        <div class="highlight-content"><?php echo $section['content']; ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php // === TYPE: CONTENT === ?>
            <?php if ($sectionType === 'content' && !empty($section['content'])): ?>
                <div class="content-block">
                    <?php echo $section['content']; ?>
                </div>
            <?php endif; ?>
            
            <?php // === TYPE: FEATURES === ?>
            <?php if ($sectionType === 'features' && !empty($section['features'])): ?>
                <div class="features-grid">
                    <?php foreach ($section['features'] as $feature): ?>
                        <div class="feature-item">
                            <?php if (!empty($feature['icon'])): ?>
                                <span class="feature-icon"><?php echo $feature['icon']; ?></span>
                            <?php endif; ?>
                            <span class="feature-text"><?php echo htmlspecialchars($feature['text']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </section>
<?php endforeach; ?>

<!-- ===== CTA FINAL ===== -->
<?php if (!empty($cta_final['title']) || !empty($cta_final['button_text'])): ?>
<section class="section-cta">
    <div class="container">
        <div class="cta-box">
            <?php if (!empty($cta_final['title'])): ?>
                <h2><?php echo htmlspecialchars($cta_final['title']); ?></h2>
            <?php endif; ?>
            
            <?php if (!empty($cta_final['text'])): ?>
                <p><?php echo htmlspecialchars($cta_final['text']); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($cta_final['button_text'])): ?>
                <a href="<?php echo htmlspecialchars($cta_final['button_url'] ?? '/contact'); ?>" class="btn btn-primary btn-lg">
                    <?php echo htmlspecialchars($cta_final['button_text']); ?>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($cta_final['urgency'])): ?>
                <p class="cta-urgency"><?php echo htmlspecialchars($cta_final['urgency']); ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Capturer le contenu
$page_content = ob_get_clean();

// Inclure le layout principal
require_once __DIR__ . '/front.php';