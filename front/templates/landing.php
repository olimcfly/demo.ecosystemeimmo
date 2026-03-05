<?php
/**
 * LANDING TEMPLATE
 * Variables disponibles depuis index.php:
 * - $page_title
 * - $meta_description
 * - $page_content (contient tout le HTML inline)
 * - $current_slug
 * - $pdo (connexion DB)
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Landing'); ?></title>
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
        }
        
        /* MAIN */
        main {
            min-height: calc(100vh - 80px);
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

    <!-- LANDING CONTENT -->
    <main>
        <?php echo $page_content ?? ''; ?>
    </main>

    <!-- FOOTER -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Eduardo De Sul - Conseiller Immobilier eXp France</p>
        <p>Bordeaux Métropole · SIRET · Assurance RC Professionnelle</p>
        <p><a href="#mentions">Mentions légales</a> · <a href="#rgpd">Politique de confidentialité</a></p>
    </footer>
</body>
</html>