<?php
/**
 * ============================================================================
 * PAGE DE CAPTURE DYNAMIQUE (Landing Pages)
 * ============================================================================
 * Affiche une page de capture sans header/footer
 * 
 * URLs ACCEPTÉES:
 * - https://ecosystemeimmo.fr/capture/lead-immobilier
 * - https://ecosystemeimmo.fr/capture/strategie-marketing
 */

// ============================================
// 1. RÉCUPÉRER LE SLUG DEPUIS L'URL
// ============================================
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = str_replace('/capture/', '', $request_uri);

// Nettoyer le slug
$slug = trim(parse_url($request_uri, PHP_URL_PATH), '/');
$slug = explode('?', $slug)[0];
$slug = explode('#', $slug)[0];

// Sécurité
if (empty($slug) || preg_match('/[^a-z0-9-]/', $slug)) {
    http_response_code(404);
    die('Page de capture non trouvée');
}

// ============================================
// 2. CHARGER LA CONFIG ET LA BASE DE DONNÉES
// ============================================
require_once __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données');
}

// ============================================
// 3. RÉCUPÉRER LA PAGE DE CAPTURE DEPUIS LA DB
// ============================================
try {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, content, meta_title, meta_description, h1, status 
        FROM pages 
        WHERE slug = :slug AND status = 'published' AND page_category = 'capture'
        LIMIT 1
    ");
    $stmt->execute(['slug' => $slug]);
    $page = $stmt->fetch();
    
    if (!$page) {
        http_response_code(404);
        die('Page de capture non trouvée');
    }
    
} catch (PDOException $e) {
    error_log("Erreur capture.php: " . $e->getMessage());
    http_response_code(500);
    die('Erreur serveur');
}

// ============================================
// 4. META TAGS POUR LE SEO
// ============================================
$page_title = $page['meta_title'] ?? $page['title'];
$meta_description = $page['meta_description'] ?? substr(strip_tags($page['content']), 0, 155);

// ============================================
// 5. AFFICHER LA PAGE DE CAPTURE (SANS HEADER/FOOTER)
// ============================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="robots" content="noindex, follow">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            width: 100%;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            background: white;
            overflow-x: hidden;
        }
        
        /* ============================================
           LAYOUT FULL WIDTH
        ============================================ */
        .capture-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .capture-content {
            width: 100%;
            max-width: 1000px;
            padding: 40px 20px;
            margin: 0 auto;
        }
        
        /* ============================================
           TYPOGRAPHY
        ============================================ */
        h1, h2, h3 {
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        h1 {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        h2 {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        p {
            font-size: 16px;
            line-height: 1.8;
            color: var(--text-secondary);
            margin-bottom: 16px;
        }
        
        ul, ol {
            margin: 20px 0 20px 30px;
        }
        
        li {
            margin-bottom: 12px;
            line-height: 1.6;
        }
        
        /* ============================================
           FORMS & BUTTONS
        ============================================ */
        form {
            margin: 30px 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="phone"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="phone"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        button, .btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        button:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(99,102,241,0.3);
        }
        
        button:active, .btn:active {
            transform: translateY(0);
        }
        
        /* ============================================
           BLOCKS & CARDS
        ============================================ */
        .card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .card.highlight {
            background: linear-gradient(135deg, rgba(99,102,241,0.05) 0%, rgba(139,92,246,0.05) 100%);
            border-color: var(--primary);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
        }
        
        .alert.success {
            background: rgba(16,185,129,0.1);
            border-left-color: var(--success);
            color: var(--success);
        }
        
        .alert.info {
            background: rgba(99,102,241,0.1);
            border-left-color: var(--primary);
            color: var(--primary);
        }
        
        .alert.warning {
            background: rgba(245,158,11,0.1);
            border-left-color: #f59e0b;
            color: #f59e0b;
        }
        
        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 768px) {
            h1 {
                font-size: 32px;
            }
            
            h2 {
                font-size: 24px;
            }
            
            h3 {
                font-size: 18px;
            }
            
            .capture-content {
                padding: 20px 16px;
            }
            
            button, .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="capture-container">
        <div class="capture-content">
            <?php 
            // Afficher le contenu
            echo $page['content']; 
            ?>
        </div>
    </div>
</body>
</html>