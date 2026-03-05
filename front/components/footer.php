<?php
/**
 * FOOTER.PHP - FOOTER DYNAMIQUE
 * Emplacement: /front/components/footer.php
 * 
 * Récupère les paramètres et liens depuis la BDD via le module Menus
 */

// Inclure les fonctions de menu
$menuFunctionsPath = __DIR__ . '/../includes/functions/menu-functions.php';
if (!file_exists($menuFunctionsPath)) {
    $menuFunctionsPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/functions/menu-functions.php';
}
if (file_exists($menuFunctionsPath)) {
    include_once $menuFunctionsPath;
}

// Récupérer les paramètres du footer depuis la BDD
$companyName = function_exists('get_setting') ? get_setting('footer_company_name', 'Eduardo De Sul') : 'Eduardo De Sul';
$description = function_exists('get_setting') ? get_setting('footer_description', 'Conseiller immobilier indépendant spécialisé dans le marché bordelais.') : 'Conseiller immobilier indépendant spécialisé dans le marché bordelais.';
$address = function_exists('get_setting') ? get_setting('footer_address', '') : '';
$phone = function_exists('get_setting') ? get_setting('footer_phone', '+33 6 12 34 56 78') : '+33 6 12 34 56 78';
$email = function_exists('get_setting') ? get_setting('footer_email', 'contact@eduardo-desul-immobilier.fr') : 'contact@eduardo-desul-immobilier.fr';
$copyright = function_exists('get_setting') ? get_setting('footer_copyright', '© ' . date('Y') . ' Eduardo De Sul Immobilier. Tous droits réservés.') : '© ' . date('Y') . ' Eduardo De Sul Immobilier. Tous droits réservés.';

// Réseaux sociaux
$facebook = function_exists('get_setting') ? get_setting('social_facebook', '') : '';
$instagram = function_exists('get_setting') ? get_setting('social_instagram', '') : '';
$linkedin = function_exists('get_setting') ? get_setting('social_linkedin', '') : '';
$youtube = function_exists('get_setting') ? get_setting('social_youtube', '') : '';

// Récupérer les liens des colonnes du footer
$servicesLinks = function_exists('get_menu_items') ? get_menu_items('footer-col1') : [];
$resourcesLinks = function_exists('get_menu_items') ? get_menu_items('footer-col2') : [];
$legalLinks = function_exists('get_menu_items') ? get_menu_items('footer-col3') : [];
?>

<footer class="main-footer">
    <div class="footer-container">
        
        <!-- FOOTER GRID -->
        <div class="footer-grid">
            
            <!-- COLONNE 1: À PROPOS -->
            <div class="footer-column footer-about">
                <h4 class="footer-title"><?php echo htmlspecialchars($companyName); ?></h4>
                <?php if ($description): ?>
                <p class="footer-text"><?php echo htmlspecialchars($description); ?></p>
                <?php endif; ?>
                
                <!-- Coordonnées -->
                <div class="footer-contact-info">
                    <?php if ($address): ?>
                    <div class="contact-item">
                        <span class="contact-icon">📍</span>
                        <span><?php echo nl2br(htmlspecialchars($address)); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($phone): ?>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>"><?php echo htmlspecialchars($phone); ?></a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($email): ?>
                    <div class="contact-item">
                        <span class="contact-icon">✉️</span>
                        <a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Réseaux sociaux -->
                <?php if ($facebook || $instagram || $linkedin || $youtube): ?>
                <div class="footer-socials">
                    <?php if ($facebook): ?>
                    <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" rel="noopener" class="social-link" title="Facebook">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($instagram): ?>
                    <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" rel="noopener" class="social-link" title="Instagram">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153.509.5.902 1.105 1.153 1.772.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 01-1.153 1.772c-.5.508-1.105.902-1.772 1.153-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 01-1.772-1.153 4.904 4.904 0 01-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 011.153-1.772A4.897 4.897 0 015.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2zm0 5a5 5 0 100 10 5 5 0 000-10zm6.5-.25a1.25 1.25 0 10-2.5 0 1.25 1.25 0 002.5 0zM12 9a3 3 0 110 6 3 3 0 010-6z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($linkedin): ?>
                    <a href="<?php echo htmlspecialchars($linkedin); ?>" target="_blank" rel="noopener" class="social-link" title="LinkedIn">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h14m-.5 15.5v-5.3a3.26 3.26 0 00-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 011.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 001.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 00-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($youtube): ?>
                    <a href="<?php echo htmlspecialchars($youtube); ?>" target="_blank" rel="noopener" class="social-link" title="YouTube">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- COLONNE 2: SERVICES (depuis BDD ou par défaut) -->
            <div class="footer-column">
                <h4 class="footer-title">Services</h4>
                <ul class="footer-list">
                    <?php if (!empty($servicesLinks)): ?>
                        <?php foreach ($servicesLinks as $link): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>"<?php echo $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>>
                                <?php echo htmlspecialchars($link['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Liens par défaut -->
                        <li><a href="/estimation">Estimation gratuite</a></li>
                        <li><a href="/vendre">Vendre mon bien</a></li>
                        <li><a href="/acheter">Acheter un bien</a></li>
                        <li><a href="/investir">Investir</a></li>
                        <li><a href="/financement">Financement</a></li>
                        <li><a href="/contact">Nous contacter</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- COLONNE 3: RESSOURCES / BORDEAUX (depuis BDD ou par défaut) -->
            <div class="footer-column">
                <h4 class="footer-title">Bordeaux</h4>
                <ul class="footer-list">
                    <?php if (!empty($resourcesLinks)): ?>
                        <?php foreach ($resourcesLinks as $link): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>"<?php echo $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>>
                                <?php echo htmlspecialchars($link['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Liens par défaut -->
                        <li><a href="/secteurs">Tous les secteurs</a></li>
                        <li><a href="/rapport-marche">Rapport marché</a></li>
                        <li><a href="/prix-immobilier">Prix au m²</a></li>
                        <li><a href="/a-propos">À propos</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- COLONNE 4: LÉGAL (depuis BDD ou par défaut) -->
            <div class="footer-column">
                <h4 class="footer-title">Informations légales</h4>
                <ul class="footer-list">
                    <?php if (!empty($legalLinks)): ?>
                        <?php foreach ($legalLinks as $link): ?>
                        <li>
                            <a href="<?php echo htmlspecialchars($link['url']); ?>"<?php echo $link['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>>
                                <?php echo htmlspecialchars($link['title']); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Liens par défaut -->
                        <li><a href="/mentions-legales">Mentions légales</a></li>
                        <li><a href="/politique-confidentialite">Confidentialité</a></li>
                        <li><a href="/cgu">CGU</a></li>
                        <li><a href="/mediation">Médiation</a></li>
                    <?php endif; ?>
                </ul>
            </div>

        </div>

        <!-- FOOTER BOTTOM -->
        <div class="footer-bottom">
            <div class="footer-copyright">
                <?php echo htmlspecialchars($copyright); ?>
            </div>
            <div class="footer-badge">
                Conseiller immobilier indépendant rattaché à eXp France
            </div>
        </div>

    </div>
</footer>

<style>
/* ========================================
   FOOTER STYLES
   ======================================== */
.main-footer {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    color: #94a3b8;
    padding: 60px 0 0;
    margin-top: 80px;
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.footer-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr;
    gap: 40px;
}

/* Footer About */
.footer-about {
    padding-right: 20px;
}

.footer-title {
    color: white;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
}

.footer-text {
    line-height: 1.7;
    margin-bottom: 20px;
    font-size: 14px;
}

/* Contact Info */
.footer-contact-info {
    margin-bottom: 20px;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 14px;
}

.contact-icon {
    font-size: 16px;
}

.contact-item a {
    color: #94a3b8;
    text-decoration: none;
    transition: color 0.2s;
}

.contact-item a:hover {
    color: #6366f1;
}

/* Réseaux sociaux */
.footer-socials {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.social-link {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.2s;
}

.social-link:hover {
    background: #6366f1;
    transform: translateY(-3px);
}

/* Footer Lists */
.footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-list li {
    margin-bottom: 12px;
}

.footer-list a {
    color: #94a3b8;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
    display: inline-block;
}

.footer-list a:hover {
    color: #6366f1;
    transform: translateX(4px);
}

/* Footer Bottom */
.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    margin-top: 50px;
    padding: 25px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-copyright {
    font-size: 13px;
}

.footer-badge {
    font-size: 12px;
    color: #64748b;
    background: rgba(255,255,255,0.05);
    padding: 6px 12px;
    border-radius: 20px;
}

/* Responsive */
@media (max-width: 1024px) {
    .footer-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .footer-about {
        grid-column: span 2;
    }
}

@media (max-width: 640px) {
    .main-footer {
        padding: 40px 0 0;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .footer-about {
        grid-column: span 1;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
    }
}
</style>