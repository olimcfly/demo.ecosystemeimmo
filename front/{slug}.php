<?php
/**
 * AFFICHAGE PUBLIC - /front/{slug}.php
 * PAGE DYNAMIQUE AVEC CONTENU BDD
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // CONFIG
    require_once dirname(dirname(__FILE__)) . '/config/config.php';
    
    // BDD
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // PAGEBUILDER CLASS
    require_once dirname(dirname(__FILE__)) . '/includes/classes/PageBuilder.php';
    $builder = new PageBuilder($pdo);
    
    // RÉCUPÉRER LE SLUG
    $slug = $_GET['slug'] ?? basename($_SERVER['REQUEST_URI'], '.php');
    $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
    
    if (!$slug) {
        header('Location: /');
        exit;
    }
    
    // CHARGER LA PAGE
    $page = $builder->getPageBySlug($slug);
    
    if (!$page) {
        // PAGE N'EXISTE PAS
        http_response_code(404);
        include dirname(__FILE__) . '/404.php';
        exit;
    }
    
    // VÉRIFIER LE STATUT
    $isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';
    $isAdmin = isset($_SESSION['user_id']); // À adapter selon ta session
    
    if ($page['status'] === 'draft' && !$isPreview && !$isAdmin) {
        // PAGE EN BROUILLON - REFUSER L'ACCÈS
        http_response_code(404);
        include dirname(__FILE__) . '/404.php';
        exit;
    }
    
    // DONNÉES SÛRES
    $title = htmlspecialchars($page['title'] ?? '');
    $metaTitle = htmlspecialchars($page['meta_title'] ?? $title);
    $metaDesc = htmlspecialchars($page['meta_description'] ?? '');
    $content = $page['content'] ?? '';
    $customCss = $page['custom_css'] ?? '';
    $customJs = $page['custom_js'] ?? '';
    $headerEnabled = intval($page['header_enabled'] ?? 1);
    $footerEnabled = intval($page['footer_enabled'] ?? 1);
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur serveur: ' . htmlspecialchars($e->getMessage()));
}

?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $metaDesc ?>">
    <title><?= $metaTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        /* PREVIEW BANNER */
        .preview-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #fff;
            padding: 12px 20px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        /* HEADER */
        header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 20px;
            text-align: center;
        }
        
        header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        header p {
            color: var(--muted);
            font-size: 0.9rem;
        }
        
        /* FOOTER */
        footer {
            background: #1e293b;
            color: #fff;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }
        
        footer p {
            margin: 10px 0;
            font-size: 0.9rem;
        }
        
        /* CONTENU DYNAMIQUE */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* SECTIONS */
        section {
            margin-bottom: 60px;
        }
        
        section h2 {
            font-size: 2rem;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        section h3 {
            font-size: 1.3rem;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        p { margin-bottom: 15px; }
        
        ul, ol {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        
        li { margin-bottom: 8px; }
        
        a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        a:hover { text-decoration: underline; }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            main { padding: 20px 15px; }
            section h2 { font-size: 1.5rem; }
            section h3 { font-size: 1.1rem; }
        }
    </style>
    
    <?php if (!empty($customCss)): ?>
    <style>
        <?= $customCss ?>
    </style>
    <?php endif; ?>
</head>
<body>
    
    <?php if ($isPreview): ?>
    <div class="preview-banner">
        📋 Mode aperçu (statut: <?= $page['status'] === 'published' ? '✅ Publié' : '📝 Brouillon' ?>)
    </div>
    <?php endif; ?>
    
    <?php if ($headerEnabled): ?>
    <header>
        <h1><?= $title ?></h1>
        <p>Votre assistant immobilier en ligne</p>
    </header>
    <?php endif; ?>
    
    <main>
        <?php if (!empty($content)): ?>
            <?= $content ?>
        <?php else: ?>
            <section>
                <h2>Contenu vide</h2>
                <p>Générez du contenu via le builder IA.</p>
            </section>
        <?php endif; ?>
    </main>
    
    <?php if ($footerEnabled): ?>
    <footer>
        <p>&copy; 2026 - <?= htmlspecialchars(SITE_NAME ?? 'ÉCOSYSTÈME IMMO LOCAL+') ?></p>
        <p>Tous droits réservés</p>
    </footer>
    <?php endif; ?>
    
    <?php if (!empty($customJs)): ?>
    <script>
        <?= $customJs ?>
    </script>
    <?php endif; ?>
    
</body>
</html>