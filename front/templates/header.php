<?php
/**
 * HEADER DYNAMIQUE
 * /front/templates/header.php
 * 
 * Charge le template "header" depuis la table `templates`
 */

// Éviter les inclusions multiples
if (defined('HEADER_LOADED')) return;
define('HEADER_LOADED', true);

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
        // Fallback silencieux
        return;
    }
}

// Utiliser $db si disponible (singleton)
$conn = $pdo ?? $db ?? null;
if (!$conn) return;

// Charger le template header
try {
    $stmt = $conn->prepare("SELECT content, custom_css, custom_js FROM templates WHERE slug = 'header' AND is_active = 1 LIMIT 1");
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
    
    // Contenu HTML
    echo $template['content'];
    
    // JS du template
    if (!empty($template['custom_js'])) {
        echo '<script>' . $template['custom_js'] . '</script>';
    }
} else {
    // Fallback: header par défaut si pas de template en DB
    ?>
    <style>
    .header-fallback {
        background: #1e3a5f;
        color: white;
        padding: 15px 20px;
    }
    .header-fallback .container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-fallback a {
        color: white;
        text-decoration: none;
    }
    .header-fallback nav {
        display: flex;
        gap: 25px;
    }
    </style>
    <header class="header-fallback">
        <div class="container">
            <a href="/" style="font-size: 1.4rem; font-weight: 700;">Eduardo De Sul</a>
            <nav>
                <a href="/">Accueil</a>
                <a href="/contact">Contact</a>
            </nav>
        </div>
    </header>
    <?php
}

// Script mobile menu (toujours inclus)
?>
<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) menu.classList.toggle('open');
}
</script>