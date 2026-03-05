<?php
/**
 * ========================================
 * LAYOUT FORM - COMPATIBLE CMS
 * ========================================
 * 
 * Fichier: /front/layouts/layout-form.php
 * 
 * Variables attendues:
 * - $page_title, $meta_description
 * - $hero (array)
 * - $form_type (string) - 'contact', 'estimation', 'simulation'
 * - $form_content (string HTML optionnel)
 * - $sidebar (array)
 * 
 * ========================================
 */

$page_title = $page_title ?? 'Formulaire';
$meta_description = $meta_description ?? '';
$hero = $hero ?? [];
$form_type = $form_type ?? 'contact';
$form_content = $form_content ?? '';
$sidebar = $sidebar ?? [];
$inline_css = $inline_css ?? '';

$page_type = 'form';

ob_start();
?>

<!-- ===== HERO FORM ===== -->
<section class="hero-form">
    <div class="container">
        <?php if (!empty($hero['subtitle_top'])): ?>
            <span class="hero-badge"><?php echo htmlspecialchars($hero['subtitle_top']); ?></span>
        <?php endif; ?>
        
        <h1><?php echo $hero['title'] ?? htmlspecialchars($page_title); ?></h1>
        
        <?php if (!empty($hero['subtitle'])): ?>
            <p class="hero-subtitle"><?php echo htmlspecialchars($hero['subtitle']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($hero['benefits'])): ?>
            <div class="hero-benefits">
                <?php foreach ($hero['benefits'] as $benefit): ?>
                    <span class="benefit">
                        <?php echo $benefit['icon'] ?? '✓'; ?> 
                        <?php echo htmlspecialchars($benefit['text']); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===== FORMULAIRE ===== -->
<section class="section-form">
    <div class="container">
        <div class="form-layout <?php echo !empty($sidebar) ? 'with-sidebar' : ''; ?>">
            
            <!-- Zone formulaire -->
            <div class="form-container">
                <?php if (!empty($form_content)): ?>
                    <!-- Formulaire personnalisé -->
                    <?php echo $form_content; ?>
                <?php else: ?>
                    <!-- Formulaire par défaut selon type -->
                    <?php if ($form_type === 'contact'): ?>
                        <?php include __DIR__ . '/../templates/form-contact.php'; ?>
                    <?php elseif ($form_type === 'estimation'): ?>
                        <?php include __DIR__ . '/../templates/form-estimation.php'; ?>
                    <?php elseif ($form_type === 'simulation'): ?>
                        <?php include __DIR__ . '/../templates/form-simulation.php'; ?>
                    <?php else: ?>
                        <!-- Formulaire contact générique -->
                        <form action="/api/contact.php" method="POST" class="form-modern">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="nom">Nom *</label>
                                    <input type="text" id="nom" name="nom" required>
                                </div>
                                <div class="form-group">
                                    <label for="prenom">Prénom *</label>
                                    <input type="text" id="prenom" name="prenom" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label for="telephone">Téléphone</label>
                                    <input type="tel" id="telephone" name="telephone">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sujet">Sujet</label>
                                <select id="sujet" name="sujet">
                                    <option value="">Choisir...</option>
                                    <option value="vendre">Je souhaite vendre</option>
                                    <option value="acheter">Je souhaite acheter</option>
                                    <option value="estimation">Demande d'estimation</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="rgpd" required>
                                    J'accepte que mes données soient traitées conformément à la <a href="/politique-confidentialite" target="_blank">politique de confidentialité</a>. *
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                Envoyer ma demande
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <?php if (!empty($sidebar)): ?>
            <aside class="form-sidebar">
                <div class="sidebar-box">
                    <h4><?php echo htmlspecialchars($sidebar['title'] ?? '📞 Me joindre'); ?></h4>
                    
                    <?php if (!empty($sidebar['phone'])): ?>
                        <div class="contact-item">
                            <span class="icon">📱</span>
                            <div>
                                <strong>Téléphone</strong><br>
                                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $sidebar['phone']); ?>">
                                    <?php echo htmlspecialchars($sidebar['phone']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($sidebar['email'])): ?>
                        <div class="contact-item">
                            <span class="icon">📧</span>
                            <div>
                                <strong>Email</strong><br>
                                <a href="mailto:<?php echo htmlspecialchars($sidebar['email']); ?>">
                                    <?php echo htmlspecialchars($sidebar['email']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($sidebar['address'])): ?>
                        <div class="contact-item">
                            <span class="icon">📍</span>
                            <div>
                                <strong>Adresse</strong><br>
                                <?php echo nl2br(htmlspecialchars($sidebar['address'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($sidebar['hours'])): ?>
                        <div class="contact-item">
                            <span class="icon">🕐</span>
                            <div>
                                <strong>Horaires</strong><br>
                                <?php echo nl2br(htmlspecialchars($sidebar['hours'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($sidebar['social'])): ?>
                <div class="sidebar-box">
                    <h4>🌐 Réseaux sociaux</h4>
                    <div class="social-links">
                        <?php foreach ($sidebar['social'] as $network => $url): ?>
                            <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener" class="social-link social-<?php echo $network; ?>">
                                <?php echo ucfirst($network); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="sidebar-box sidebar-trust">
                    <h4>✅ Mes engagements</h4>
                    <ul class="trust-list">
                        <li>Réponse sous 24h garantie</li>
                        <li>Estimation gratuite et sans engagement</li>
                        <li>Accompagnement personnalisé</li>
                        <li>Confidentialité de vos données</li>
                    </ul>
                </div>
            </aside>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<?php
$page_content = ob_get_clean();
require_once __DIR__ . '/front.php';