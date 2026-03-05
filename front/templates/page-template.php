<?php
/**
 * PAGE TEMPLATE
 * Variables disponibles depuis index.php:
 * - $page_title
 * - $meta_description
 * - $page_h1
 * - $page_content
 * - $current_slug
 * - $pdo (connexion DB)
 */
 
 require_once ROOT_PATH . '/front/rende-sections.php';

// Si tu as des sections en DB:
if (!empty($page_content)) {
    $sections = json_decode($page_content, true);
    renderSections($sections, $pdo);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Page'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? ''); ?>">
    <meta name="theme-color" content="#6366f1">
    <meta name="robots" content="index, follow">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --dark: #0f172a;
            --light: #f8fafc;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* HEADER */
        header {
            background: white;
            padding: 20px 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 22px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        nav a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        nav a:hover {
            color: var(--primary);
        }
        
        nav a.active {
            color: var(--primary);
            font-weight: 700;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 5px;
        }
        
        /* MAIN */
        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 40px;
            min-height: calc(100vh - 200px);
        }
        
        main h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 30px;
            line-height: 1.1;
            color: var(--text-primary);
        }
        
        main h2 {
            font-size: 36px;
            font-weight: 700;
            margin: 60px 0 25px;
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        main h3 {
            font-size: 24px;
            font-weight: 600;
            margin: 40px 0 15px;
            color: var(--text-primary);
        }
        
        main p {
            margin-bottom: 18px;
            line-height: 1.8;
            color: var(--text-secondary);
            font-size: 1.05rem;
        }
        
        main ul, main ol {
            margin: 25px 0 25px 30px;
        }
        
        main li {
            margin-bottom: 10px;
            line-height: 1.7;
            color: var(--text-secondary);
        }
        
        main strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        main a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        main a:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        main img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 30px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* FOOTER */
        footer {
            background: var(--dark);
            color: white;
            padding: 50px 40px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 100px;
        }
        
        footer p {
            margin: 5px 0;
            opacity: 0.8;
            font-size: 0.95rem;
        }
        
        footer a {
            color: var(--primary);
            text-decoration: none;
        }
        
        footer a:hover {
            text-decoration: underline;
        }
        
        /* RESPONSIVE */
        @media (max-width: 768px) {
            header { padding: 15px 20px; }
            main { padding: 40px 20px; }
            main h1 { font-size: 32px; }
            main h2 { font-size: 26px; }
            nav { gap: 15px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="header-content">
            <a href="/" class="logo">
                <span>🏠</span>
                <span>Eduardo De Sul</span>
            </a>
            <nav>
                <a href="/" <?php echo ($current_slug === 'home') ? 'class="active"' : ''; ?>>Home</a>
                <a href="/vendre" <?php echo ($current_slug === 'vendre') ? 'class="active"' : ''; ?>>Vendre</a>
                <a href="/acheter" <?php echo ($current_slug === 'acheter') ? 'class="active"' : ''; ?>>Acheter</a>
                <a href="/estimation" <?php echo ($current_slug === 'estimation') ? 'class="active"' : ''; ?>>Estimation</a>
                <a href="#contact">Contact</a>
            </nav>
        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main>
        <?php if (!empty($page_h1)): ?>
            <h1><?php echo htmlspecialchars($page_h1); ?></h1>
        <?php endif; ?>
        
        <div class="page-content">
            <?php echo $page_content ?? ''; ?>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Eduardo De Sul - Conseiller Immobilier eXp France</p>
        <p>Bordeaux Métropole · SIRET · Assurance RC Professionnelle</p>
        <p><a href="#mentions">Mentions légales</a> · <a href="#rgpd">Politique de confidentialité</a></p>
    </footer>

    <!-- Schema.org -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "Eduardo De Sul - Conseiller Immobilier",
      "description": "Conseiller immobilier indépendant eXp France à Bordeaux Métropole",
      "address": {
        "@type": "PostalAddress",
        "addressLocality": "Bordeaux",
        "addressRegion": "Nouvelle-Aquitaine",
        "addressCountry": "FR"
      }
    }
    </script>
</body>
</html>