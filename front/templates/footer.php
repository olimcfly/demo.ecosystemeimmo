<?php
/**
 * FOOTER DYNAMIQUE
 * /front/templates/footer.php
 * 
 * Charge le template "footer" depuis la table `templates`
 */

// Éviter les inclusions multiples
if (defined('FOOTER_LOADED')) return;
define('FOOTER_LOADED', true);

// Connexion DB si pas déjà faite
if (!isset($pdo) && !isset($db)) {
    require_once dirname(dirname(__DIR__)) . '/config/config.php';
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        return;
    }
}

// Utiliser $db si disponible (singleton)
$conn = $pdo ?? $db ?? null;
if (!$conn) return;

// Charger le template footer
try {
    $stmt = $conn->prepare("SELECT content, custom_css, custom_js FROM templates WHERE slug = 'footer' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $template = null;
}

// Si template trouvé, l'afficher
if ($template) {
    // CSS du template
    if (!empty($template['custom_css'])) {
        echo '<style>' . $template['custom_css'] . '</style>';
    }
    
    // Contenu HTML (avec année dynamique)
    $content = str_replace(['2024', '2025'], date('Y'), $template['content']);
    echo $content;
    
    // JS du template
    if (!empty($template['custom_js'])) {
        echo '<script>' . $template['custom_js'] . '</script>';
    }
} else {
    // Fallback: footer par défaut si pas de template en DB
    ?>
    <style>
    .footer-fallback {
        background: #1e3a5f;
        color: white;
        padding: 40px 20px;
        text-align: center;
        margin-top: 60px;
    }
    .footer-fallback a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
    }
    </style>
    <footer class="footer-fallback">
        <p style="margin-bottom: 10px;"><strong>Eduardo De Sul</strong> - Conseiller Immobilier</p>
        <p style="opacity: 0.7; font-size: 0.9rem;">© <?= date('Y') ?> Tous droits réservés</p>
    </footer>
    <?php
}