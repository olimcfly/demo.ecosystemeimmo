<?php
/**
 * ARTICLE TEMPLATE
 * Pour les articles de blog, actualités, conseils
 * Variables disponibles:
 * - $page_title, $meta_description
 * - $page_h1, $page_content
 * - $current_slug
 * - $pdo (connexion DB)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Article'); ?></title>
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
        
        /* HERO ARTICLE */
        .article-hero {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            padding: 60px 40px;
            margin-bottom: 60px;
        }
        
        .article-hero-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .article-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }
        
        .article-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .article-hero h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.1;
            color: var(--text-primary);
        }
        
        .article-hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            line-height: 1.8;
        }
        
        /* MAIN CONTENT */
        main {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 40px 80px;
        }
        
        .article-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin: 60px 0 25px;
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        .article-content h3 {
            font-size: 24px;
            font-weight: 600;
            margin: 40px 0 15px;
            color: var(--text-primary);
        }
        
        .article-content p {
            margin-bottom: 20px;
            line-height: 1.9;
            color: var(--text-secondary);
            font-size: 1.05rem;
        }
        
        .article-content ul,
        .article-content ol {
            margin: 25px 0 25px 30px;
        }
        
        .article-content li {
            margin-bottom: 10px;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        .article-content strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .article-content a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .article-content a:hover {
            text-decoration: underline;
        }
        
        .article-content blockquote {
            border-left: 4px solid var(--primary);
            padding-left: 20px;
            margin: 30px 0;
            color: var(--text-secondary);
            font-style: italic;
            background: rgba(99, 102, 241, 0.05);
            padding: 20px;
            border-radius: 8px;
        }
        
        .article-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 40px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .article-content code {
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Monaco', monospace;
            font-size: 0.9em;
            color: #e53e3e;
        }
        
        .article-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 30px 0;
            line-height: 1.5;
        }
        
        .article-content pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
        
        /* ARTICLE FOOTER */
        .article-footer {
            border-top: 1px solid var(--border);
            padding-top: 40px;
            margin-top: 60px;
        }
        
        .author-box {
            background: rgba(99, 102, 241, 0.05);
            padding: 25px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .author-box h4 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .author-box p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
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
            .article-hero { padding: 40px 20px; }
            main { padding: 0 20px 60px; }
            .article-hero h1 { font-size: 32px; }
            .article-content h2 { font-size: 26px; }
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
                <a href="/">Home</a>
                <a href="/vendre">Vendre</a>
                <a href="/acheter">Acheter</a>
                <a href="/estimation">Estimation</a>
            </nav>
        </div>
    </header>

    <!-- ARTICLE HERO -->
    <div class="article-hero">
        <div class="article-hero-content">
            <div class="article-meta">
                <span>📅 <?php echo date('d/m/Y'); ?></span>
                <span>✍️ Eduardo De Sul</span>
                <span>⏱️ 5 min de lecture</span>
            </div>
            <h1><?php echo htmlspecialchars($page_h1 ?? ''); ?></h1>
            <p><?php echo htmlspecialchars($meta_description ?? ''); ?></p>
        </div>
    </div>

    <!-- ARTICLE CONTENT -->
    <main>
        <div class="article-content">
            <?php echo $page_content ?? ''; ?>
        </div>
        
        <!-- AUTHOR BOX -->
        <div class="article-footer">
            <div class="author-box">
                <h4>✍️ À Propos de l'Auteur</h4>
                <p>Eduardo De Sul est un conseiller immobilier indépendant eXp France basé à Bordeaux. Spécialiste de l'immobilier local, il accompagne ses clients dans chaque étape de leur projet immobilier.</p>
            </div>
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
      "@type": "BlogPosting",
      "headline": "<?php echo addslashes(htmlspecialchars($page_h1 ?? '')); ?>",
      "description": "<?php echo addslashes(htmlspecialchars($meta_description ?? '')); ?>",
      "author": {
        "@type": "Person",
        "name": "Eduardo De Sul"
      },
      "datePublished": "<?php echo date('Y-m-d'); ?>"
    }
    </script>
</body>
</html>