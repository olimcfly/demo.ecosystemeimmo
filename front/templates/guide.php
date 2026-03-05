<?php
/**
 * GUIDE TEMPLATE
 * Pour les guides, tutoriels, ressources téléchargeables
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
    <title><?php echo htmlspecialchars($page_title ?? 'Guide'); ?></title>
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
        
        /* CONTAINER DEUX COLONNES */
        .guide-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
        }
        
        /* SIDEBAR */
        .guide-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .toc-box {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary);
        }
        
        .toc-box h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .toc-box ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .toc-box li {
            margin-bottom: 8px;
        }
        
        .toc-box a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
            display: block;
            padding: 5px 0;
        }
        
        .toc-box a:hover {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* CTA BOX */
        .download-box {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-top: 25px;
            text-align: center;
        }
        
        .download-box h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .download-box p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        
        .download-btn {
            background: white;
            color: var(--primary);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
        }
        
        /* GUIDE CONTENT */
        .guide-content {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .guide-content h1 {
            font-size: 42px;
            font-weight: 800;
            margin-bottom: 25px;
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        .guide-intro {
            font-size: 1.1rem;
            color: var(--text-secondary);
            line-height: 1.9;
            padding-bottom: 30px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 40px;
        }
        
        .guide-content h2 {
            font-size: 32px;
            font-weight: 700;
            margin: 50px 0 20px;
            line-height: 1.2;
            color: var(--text-primary);
        }
        
        .guide-content h3 {
            font-size: 22px;
            font-weight: 600;
            margin: 30px 0 15px;
            color: var(--text-primary);
        }
        
        .guide-content p {
            margin-bottom: 18px;
            line-height: 1.9;
            color: var(--text-secondary);
            font-size: 1.05rem;
        }
        
        .guide-content ul,
        .guide-content ol {
            margin: 25px 0 25px 30px;
        }
        
        .guide-content li {
            margin-bottom: 12px;
            line-height: 1.8;
            color: var(--text-secondary);
        }
        
        .guide-content strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .guide-content a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .guide-content a:hover {
            text-decoration: underline;
        }
        
        /* HIGHLIGHT BOX */
        .guide-highlight {
            background: rgba(99, 102, 241, 0.05);
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .guide-highlight p {
            margin: 0;
            font-weight: 500;
        }
        
        .guide-content img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 40px 0;
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
        @media (max-width: 968px) {
            .guide-wrapper {
                grid-template-columns: 1fr;
            }
            
            .guide-sidebar {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            header { padding: 15px 20px; }
            .guide-wrapper { padding: 40px 20px; }
            .guide-content { padding: 25px; }
            .guide-content h1 { font-size: 32px; }
            .guide-content h2 { font-size: 24px; }
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

    <!-- GUIDE WRAPPER -->
    <div class="guide-wrapper">
        <!-- MAIN CONTENT -->
        <div class="guide-content">
            <h1><?php echo htmlspecialchars($page_h1 ?? ''); ?></h1>
            
            <div class="guide-intro">
                <?php echo htmlspecialchars($meta_description ?? ''); ?>
            </div>
            
            <?php echo $page_content ?? ''; ?>
        </div>
        
        <!-- SIDEBAR -->
        <aside class="guide-sidebar">
            <div class="toc-box">
                <h3>📑 Table des matières</h3>
                <ul>
                    <li><a href="#section1">Section 1</a></li>
                    <li><a href="#section2">Section 2</a></li>
                    <li><a href="#section3">Section 3</a></li>
                    <li><a href="#section4">Section 4</a></li>
                </ul>
            </div>
            
            <div class="download-box">
                <h4>📥 Télécharger ce guide</h4>
                <p>Accédez à la version PDF complète</p>
                <a href="#" class="download-btn">Télécharger</a>
            </div>
        </aside>
    </div>

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
      "@type": "Guide",
      "headline": "<?php echo addslashes(htmlspecialchars($page_h1 ?? '')); ?>",
      "description": "<?php echo addslashes(htmlspecialchars($meta_description ?? '')); ?>",
      "author": {
        "@type": "Person",
        "name": "Eduardo De Sul"
      }
    }
    </script>
</body>
</html>