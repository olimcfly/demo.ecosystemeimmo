<?php
/**
 * ============================================================================
 * RENDU FRONT-END PAGES BUILDER
 * ============================================================================
 * 
 * Fichier: /front/builder-page.php
 * 
 * Affiche les pages créées avec le builder
 * 
 * ============================================================================
 */

// Récupérer le slug depuis l'URL
$request_uri = $_SERVER['REQUEST_URI'];
$slug = trim(parse_url($request_uri, PHP_URL_PATH), '/');

// Ignorer les fichiers statiques
if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2)$/i', $slug)) {
    return false;
}

// Connexion BDD
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}
require_once $configPath;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    die('Erreur de connexion');
}

// Chercher la page
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

// Décoder les données
$heroData = !empty($page['hero_data']) ? json_decode($page['hero_data'], true) : [];
$sectionsData = !empty($page['sections_data']) ? json_decode($page['sections_data'], true) : [];

// Variables pour le template
$page_title = $page['title'];
$meta_description = $page['meta_description'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php if ($meta_description): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1e3a5f;
            --primary-light: #2d5a8a;
            --secondary: #c9a227;
            --text: #333333;
            --text-light: #666666;
            --text-muted: #999999;
            --bg: #ffffff;
            --bg-light: #f8f9fa;
            --bg-beige: #faf8f5;
            --border: #e5e7eb;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: var(--text);
            line-height: 1.6;
            background: var(--bg);
        }

        /* ========================================
           HEADER
           ======================================== */
        .site-header {
            background: var(--primary);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-size: 20px;
            font-weight: 700;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            gap: 30px;
            list-style: none;
        }

        .nav-menu a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-menu a:hover {
            color: var(--secondary);
        }

        /* ========================================
           HERO
           ======================================== */
        .hero {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 80px 20px;
            text-align: center;
        }

        .hero.has-image {
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .hero.has-image::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(30, 58, 95, 0.85);
        }

        .hero > * {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero .subtitle {
            font-size: 20px;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--secondary);
            color: var(--primary);
        }

        .btn-primary:hover {
            background: #b8911f;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.25);
        }

        /* ========================================
           SECTIONS
           ======================================== */
        .section {
            padding: 80px 20px;
        }

        .section.bg-light {
            background: var(--bg-light);
        }

        .section.bg-beige {
            background: var(--bg-beige);
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 50px;
            color: var(--primary);
        }

        /* Text Section */
        .text-section h2 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .text-section p {
            margin-bottom: 15px;
            font-size: 17px;
            line-height: 1.8;
        }

        .text-section ul {
            margin: 20px 0;
            padding-left: 0;
            list-style: none;
        }

        .text-section ul li {
            padding: 8px 0;
            font-size: 17px;
        }

        .text-section strong {
            color: var(--primary);
        }

        /* Features */
        .features-grid {
            display: grid;
            gap: 30px;
        }

        .features-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
        .features-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }
        .features-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }

        @media (max-width: 900px) {
            .features-grid.cols-3,
            .features-grid.cols-4 { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 600px) {
            .features-grid { grid-template-columns: 1fr; }
        }

        .feature-card {
            background: white;
            padding: 35px 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.6;
        }

        /* Stats */
        .stats-grid {
            display: flex;
            justify-content: center;
            gap: 80px;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 52px;
            font-weight: 700;
            color: var(--secondary);
            line-height: 1;
        }

        .stat-label {
            font-size: 16px;
            color: var(--text-light);
            margin-top: 10px;
        }

        /* Steps */
        .steps-grid {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .step-card {
            flex: 1;
            min-width: 200px;
            max-width: 250px;
            text-align: center;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            margin: 0 auto 20px;
        }

        .step-card h3 {
            font-size: 18px;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .step-card p {
            color: var(--text-light);
            font-size: 15px;
        }

        /* CTA */
        .cta-section {
            padding: 70px 40px;
            text-align: center;
            border-radius: 16px;
        }

        .cta-section.style-gradient {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .cta-section.style-simple {
            background: var(--bg-light);
        }

        .cta-section h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }

        .cta-section p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-section .btn-primary {
            background: var(--secondary);
            color: var(--primary);
        }

        .style-simple .btn-primary {
            background: var(--primary);
            color: white;
        }

        /* Cards */
        .cards-grid {
            display: grid;
            gap: 30px;
        }

        .cards-grid.cols-2 { grid-template-columns: repeat(2, 1fr); }
        .cards-grid.cols-3 { grid-template-columns: repeat(3, 1fr); }

        @media (max-width: 768px) {
            .cards-grid { grid-template-columns: 1fr; }
        }

        .card-item {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 35px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .card-icon {
            font-size: 50px;
            margin-bottom: 20px;
        }

        .card-item h3 {
            font-size: 20px;
            color: var(--primary);
            margin-bottom: 12px;
        }

        .card-item p {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.6;
        }

        /* Testimonials */
        .testimonials-grid {
            display: grid;
            gap: 30px;
        }

        .testimonial-card {
            background: white;
            border-radius: 12px;
            padding: 35px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .testimonial-text {
            font-size: 18px;
            line-height: 1.7;
            font-style: italic;
            margin-bottom: 25px;
            color: var(--text);
        }

        .testimonial-author strong {
            display: block;
            font-size: 16px;
            color: var(--primary);
        }

        .testimonial-author span {
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Contact Split */
        .contact-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .contact-split { grid-template-columns: 1fr; }
        }

        .contact-info h3 {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 30px;
        }

        .contact-info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .contact-info-item i {
            color: var(--secondary);
            width: 24px;
            font-size: 18px;
        }

        .contact-form {
            background: var(--bg-light);
            padding: 35px;
            border-radius: 12px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            margin-bottom: 15px;
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .contact-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .contact-form button {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .contact-form button:hover {
            background: var(--primary-light);
        }

        /* Divider & Spacer */
        .divider-section {
            height: 1px;
            background: var(--border);
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ========================================
           FOOTER
           ======================================== */
        .site-footer {
            background: var(--primary);
            color: white;
            padding: 50px 20px 30px;
            margin-top: 60px;
        }

        .footer-container {
            max-width: 1100px;
            margin: 0 auto;
            text-align: center;
        }

        .footer-container p {
            opacity: 0.8;
            font-size: 14px;
        }

        /* ========================================
           RESPONSIVE
           ======================================== */
        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .hero .subtitle { font-size: 17px; }
            .section { padding: 60px 20px; }
            .section-title { font-size: 26px; }
            .stats-grid { gap: 40px; }
            .stat-value { font-size: 40px; }
            .nav-menu { display: none; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="header-container">
        <a href="/" class="logo">Eduardo De Sul</a>
        <nav>
            <ul class="nav-menu">
                <li><a href="/">Accueil</a></li>
                <li><a href="/a-propos">À propos</a></li>
                <li><a href="/estimation">Estimation</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </nav>
    </div>
</header>

<!-- HERO -->
<?php if ($heroData): ?>
<section class="hero <?php echo !empty($heroData['image']) ? 'has-image' : ''; ?>" 
         <?php echo !empty($heroData['image']) ? 'style="background-image: url(\'' . htmlspecialchars($heroData['image']) . '\')"' : ''; ?>>
    <div class="container">
        <h1><?php echo htmlspecialchars($heroData['title'] ?? ''); ?></h1>
        <?php if (!empty($heroData['subtitle'])): ?>
        <p class="subtitle"><?php echo htmlspecialchars($heroData['subtitle']); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($heroData['cta_primary']['text']) || !empty($heroData['cta_secondary']['text'])): ?>
        <div class="hero-buttons">
            <?php if (!empty($heroData['cta_primary']['text'])): ?>
            <a href="<?php echo htmlspecialchars($heroData['cta_primary']['url'] ?? '#'); ?>" class="btn btn-primary">
                <?php echo htmlspecialchars($heroData['cta_primary']['text']); ?>
            </a>
            <?php endif; ?>
            <?php if (!empty($heroData['cta_secondary']['text'])): ?>
            <a href="<?php echo htmlspecialchars($heroData['cta_secondary']['url'] ?? '#'); ?>" class="btn btn-secondary">
                <?php echo htmlspecialchars($heroData['cta_secondary']['text']); ?>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- SECTIONS -->
<?php foreach ($sectionsData as $section): ?>
<?php 
    $bgClass = '';
    if (!empty($section['bgColor'])) {
        if ($section['bgColor'] === '#faf8f5' || $section['bgColor'] === '#FAF8F5') {
            $bgClass = 'bg-beige';
        } elseif ($section['bgColor'] === '#f8f9fa' || $section['bgColor'] === '#F8F9FA') {
            $bgClass = 'bg-light';
        }
    }
    $cssId = !empty($section['cssId']) ? 'id="' . htmlspecialchars($section['cssId']) . '"' : '';
?>

<?php if ($section['type'] === 'text'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container text-section">
        <?php if (!empty($section['title'])): ?>
        <h2><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div><?php echo $section['content'] ?? ''; ?></div>
    </div>
</section>

<?php elseif ($section['type'] === 'features'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <?php if (!empty($section['title'])): ?>
        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div class="features-grid cols-<?php echo $section['cols'] ?? 3; ?>">
            <?php foreach ($section['items'] ?? [] as $item): ?>
            <div class="feature-card">
                <div class="feature-icon"><?php echo $item['icon'] ?? '✨'; ?></div>
                <h3><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($item['text'] ?? ''); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'stats'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <?php if (!empty($section['title'])): ?>
        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div class="stats-grid">
            <?php foreach ($section['items'] ?? [] as $item): ?>
            <div class="stat-item">
                <div class="stat-value"><?php echo htmlspecialchars($item['value'] ?? ''); ?></div>
                <div class="stat-label"><?php echo htmlspecialchars($item['label'] ?? ''); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'steps'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <?php if (!empty($section['title'])): ?>
        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div class="steps-grid">
            <?php foreach ($section['items'] ?? [] as $index => $item): ?>
            <div class="step-card">
                <div class="step-number"><?php echo $index + 1; ?></div>
                <h3><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($item['text'] ?? ''); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'cta'): ?>
<section class="section" <?php echo $cssId; ?>>
    <div class="container">
        <div class="cta-section style-<?php echo $section['style'] ?? 'gradient'; ?>">
            <h2><?php echo htmlspecialchars($section['title'] ?? ''); ?></h2>
            <?php if (!empty($section['text'])): ?>
            <p><?php echo htmlspecialchars($section['text']); ?></p>
            <?php endif; ?>
            <?php if (!empty($section['button_text'])): ?>
            <a href="<?php echo htmlspecialchars($section['button_url'] ?? '#'); ?>" class="btn btn-primary">
                <?php echo htmlspecialchars($section['button_text']); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'cards'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <?php if (!empty($section['title'])): ?>
        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div class="cards-grid cols-<?php echo $section['cols'] ?? 3; ?>">
            <?php foreach ($section['items'] ?? [] as $item): ?>
            <div class="card-item">
                <div class="card-icon"><?php echo $item['icon'] ?? '📦'; ?></div>
                <h3><?php echo htmlspecialchars($item['title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($item['text'] ?? ''); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'testimonials'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <?php if (!empty($section['title'])): ?>
        <h2 class="section-title"><?php echo htmlspecialchars($section['title']); ?></h2>
        <?php endif; ?>
        <div class="testimonials-grid">
            <?php foreach ($section['items'] ?? [] as $item): ?>
            <div class="testimonial-card">
                <p class="testimonial-text">"<?php echo htmlspecialchars($item['text'] ?? ''); ?>"</p>
                <div class="testimonial-author">
                    <strong><?php echo htmlspecialchars($item['author'] ?? ''); ?></strong>
                    <?php if (!empty($item['location'])): ?>
                    <span><?php echo htmlspecialchars($item['location']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'contact_split'): ?>
<section class="section <?php echo $bgClass; ?>" <?php echo $cssId; ?>>
    <div class="container">
        <div class="contact-split">
            <div class="contact-info">
                <h3>Contactez-moi</h3>
                <?php if (!empty($section['phone'])): ?>
                <div class="contact-info-item">
                    <i class="fas fa-phone"></i>
                    <span><?php echo htmlspecialchars($section['phone']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($section['email'])): ?>
                <div class="contact-info-item">
                    <i class="fas fa-envelope"></i>
                    <span><?php echo htmlspecialchars($section['email']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($section['address'])): ?>
                <div class="contact-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span><?php echo htmlspecialchars($section['address']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($section['hours'])): ?>
                <div class="contact-info-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo htmlspecialchars($section['hours']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="contact-form">
                <form action="/api/contact.php" method="POST">
                    <input type="text" name="name" placeholder="Votre nom" required>
                    <input type="email" name="email" placeholder="Votre email" required>
                    <input type="tel" name="phone" placeholder="Votre téléphone">
                    <textarea name="message" placeholder="Votre message" required></textarea>
                    <button type="submit">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php elseif ($section['type'] === 'divider'): ?>
<div class="divider-section"></div>

<?php elseif ($section['type'] === 'spacer'): ?>
<div style="height: <?php echo (int)($section['height'] ?? 60); ?>px;"></div>

<?php endif; ?>
<?php endforeach; ?>

<!-- FOOTER -->
<footer class="site-footer">
    <div class="footer-container">
        <p>&copy; <?php echo date('Y'); ?> Eduardo De Sul - Conseiller immobilier indépendant</p>
    </div>
</footer>

</body>
</html>