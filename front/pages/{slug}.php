<?php
/**
 * ============================================================================
 * PAGE DYNAMIQUE - Affiche une page par slug
 * ============================================================================
 * URL: /page/{slug}
 * Exemple: /a-propos, /contact, /bacalan, etc.
 */

// Récupérer le slug depuis l'URL
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug = basename($request_uri);

// Éviter les accès directs aux fichiers système
if (empty($slug) || strpos($slug, '.') !== false) {
    http_response_code(404);
    die('Page non trouvée');
}

require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Récupérer la page
    $stmt = $pdo->prepare("
        SELECT * FROM pages 
        WHERE slug = ? AND status = 'published' AND page_category = 'frontend'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        http_response_code(404);
        die('Page non trouvée');
    }
    
    // Récupérer les sections
    $sections_stmt = $pdo->prepare("
        SELECT * FROM pages_sections 
        WHERE page_id = ? 
        ORDER BY `order` ASC
    ");
    $sections_stmt->execute([$page['id']]);
    $sections = $sections_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // SEO
    $page_title = $page['meta_title'] ?? $page['title'];
    $meta_description = $page['meta_description'] ?? '';
    $h1 = $page['h1'] ?? $page['title'];
    
} catch (Exception $e) {
    error_log("Erreur page: " . $e->getMessage());
    http_response_code(500);
    die('Erreur serveur');
}

// ============================================================================
// CONTENU - Afficher les sections
// ============================================================================

ob_start();
?>

<!-- Breadcrumb -->
<div style="background: #f9fafb; padding: 15px 0; border-bottom: 1px solid #e5e7eb;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; font-size: 13px; color: #6b7280;">
        <a href="/" style="color: #6366f1; text-decoration: none;">Accueil</a>
        <span style="margin: 0 8px;">›</span>
        <span><?php echo htmlspecialchars($page['title']); ?></span>
    </div>
</div>

<?php
// Afficher les sections
require_once __DIR__ . '/templates/render-sections.php';
renderSections($sections, $pdo);
?>

<?php
$content = ob_get_clean();

// ============================================================================
// CHARGER LE LAYOUT
// ============================================================================

require_once __DIR__ . '/../layouts/layout.php';