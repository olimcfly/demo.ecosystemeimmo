<?php
/**
 * ========================================
 * PATCH API SEO - Actions inline
 * ========================================
 * 
 * Fichier: /admin/modules/seo/api.php
 * 
 * AJOUTER ces 2 cases dans le switch($action) 
 * de votre fichier api.php existant.
 * 
 * Si tu n'as pas encore de api.php, utilise ce fichier complet.
 * ========================================
 */

// ========================================
// À AJOUTER dans le switch($action) existant de api.php
// ========================================

/*
 * INSTRUCTIONS:
 * 
 * Dans ton fichier /admin/modules/seo/api.php, trouve le bloc:
 * 
 *   switch ($action) {
 *       case 'analyze': ...
 *       case 'analyze-all': ...
 *       case 'details': ...
 *       case 'preview-seo': ...
 *       case 'generate-seo': ...
 *   }
 * 
 * Ajoute ces 2 cases AVANT le default:
 */

// ============================================
// CASE 1: Toggle NoIndex
// ============================================
// case 'toggle-noindex':
//     ... (voir code complet ci-dessous)

// ============================================
// CASE 2: Toggle Validation SEO  
// ============================================
// case 'toggle-validation':
//     ... (voir code complet ci-dessous)

// ========================================
// FICHIER API COMPLET (si pas encore créé)
// ========================================

header('Content-Type: application/json');
header('Cache-Control: no-cache');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config
$configPath = __DIR__ . '/../../../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}
if (file_exists($configPath)) {
    require_once $configPath;
}

// DB
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

switch ($action) {

    // ========================================
    // TOGGLE NOINDEX (NOUVEAU v2.2)
    // ========================================
    case 'toggle-noindex':
        if ($pageId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID page requis']);
            exit;
        }
        
        $noindex = isset($_GET['noindex']) ? (int)$_GET['noindex'] : 0;
        $noindex = ($noindex === 1) ? 1 : 0; // Sanitize
        
        try {
            // Vérifier que la colonne existe
            $colCheck = $pdo->query("SHOW COLUMNS FROM pages LIKE 'noindex'");
            if ($colCheck->rowCount() === 0) {
                $pdo->exec("ALTER TABLE pages ADD COLUMN `noindex` TINYINT(1) NOT NULL DEFAULT 0");
            }
            
            $stmt = $pdo->prepare("UPDATE pages SET noindex = ? WHERE id = ?");
            $stmt->execute([$noindex, $pageId]);
            
            // Log
            error_log("[SEO] Page #{$pageId} noindex set to {$noindex}");
            
            echo json_encode([
                'success' => true,
                'page_id' => $pageId,
                'noindex' => $noindex,
                'message' => $noindex ? 'Page mise en NoIndex' : 'Page indexée'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ========================================
    // TOGGLE VALIDATION SEO (NOUVEAU v2.2)
    // ========================================
    case 'toggle-validation':
        if ($pageId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID page requis']);
            exit;
        }
        
        $validated = isset($_GET['validated']) ? (int)$_GET['validated'] : 0;
        $validated = ($validated === 1) ? 1 : 0; // Sanitize
        
        try {
            // Vérifier que les colonnes existent
            $colCheck = $pdo->query("SHOW COLUMNS FROM pages LIKE 'seo_validated'");
            if ($colCheck->rowCount() === 0) {
                $pdo->exec("ALTER TABLE pages ADD COLUMN `seo_validated` TINYINT(1) NOT NULL DEFAULT 0");
            }
            $colCheck2 = $pdo->query("SHOW COLUMNS FROM pages LIKE 'seo_validated_at'");
            if ($colCheck2->rowCount() === 0) {
                $pdo->exec("ALTER TABLE pages ADD COLUMN `seo_validated_at` DATETIME DEFAULT NULL");
            }
            
            if ($validated === 1) {
                $stmt = $pdo->prepare("UPDATE pages SET seo_validated = 1, seo_validated_at = NOW() WHERE id = ?");
                $stmt->execute([$pageId]);
            } else {
                $stmt = $pdo->prepare("UPDATE pages SET seo_validated = 0, seo_validated_at = NULL WHERE id = ?");
                $stmt->execute([$pageId]);
            }
            
            // Log
            error_log("[SEO] Page #{$pageId} validation set to {$validated}");
            
            echo json_encode([
                'success' => true,
                'page_id' => $pageId,
                'validated' => $validated,
                'validated_at' => $validated ? date('Y-m-d H:i:s') : null,
                'message' => $validated ? 'SEO validé' : 'Validation retirée'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ========================================
    // ANALYSE SEO (existant)
    // ========================================
    case 'analyze':
        if ($pageId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID page requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch();
        
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Page introuvable']);
            exit;
        }
        
        $result = analyzeSeoPage($page);
        
        // Sauvegarder le score
        $updateStmt = $pdo->prepare("
            UPDATE pages SET 
                seo_score = ?,
                seo_issues = ?,
                seo_analyzed_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([
            $result['percentage'],
            json_encode($result['issues']),
            $pageId
        ]);
        
        echo json_encode(['success' => true, 'result' => $result]);
        break;

    // ========================================
    // ANALYSE TOUTES LES PAGES (existant)
    // ========================================
    case 'analyze-all':
        $pages = $pdo->query("SELECT * FROM pages")->fetchAll();
        $analyzed = 0;
        
        foreach ($pages as $page) {
            $result = analyzeSeoPage($page);
            $updateStmt = $pdo->prepare("
                UPDATE pages SET seo_score = ?, seo_issues = ?, seo_analyzed_at = NOW() WHERE id = ?
            ");
            $updateStmt->execute([
                $result['percentage'],
                json_encode($result['issues']),
                $page['id']
            ]);
            $analyzed++;
        }
        
        echo json_encode(['success' => true, 'analyzed' => $analyzed]);
        break;

    // ========================================
    // DÉTAILS SEO (existant)
    // ========================================
    case 'details':
        if ($pageId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID page requis']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$pageId]);
        $page = $stmt->fetch();
        
        if (!$page) {
            echo json_encode(['success' => false, 'error' => 'Page introuvable']);
            exit;
        }
        
        $result = analyzeSeoPage($page);
        
        echo json_encode([
            'success' => true,
            'page' => [
                'id' => $page['id'],
                'title' => $page['title'],
                'slug' => $page['slug']
            ],
            'seo' => $result
        ]);
        break;

    // ========================================
    // PREVIEW SEO IA (existant - adapter selon ton code IA)
    // ========================================
    case 'preview-seo':
        // Ce case dépend de ta logique IA existante
        // Renvoyer les suggestions IA pour validation avant application
        echo json_encode(['success' => false, 'error' => 'Implémentation IA requise']);
        break;

    // ========================================
    // APPLIQUER SEO IA (existant - adapter selon ton code IA)
    // ========================================
    case 'generate-seo':
        // Ce case dépend de ta logique IA existante
        echo json_encode(['success' => false, 'error' => 'Implémentation IA requise']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action inconnue: ' . $action]);
}

// ========================================
// FONCTION D'ANALYSE SEO
// ========================================

function analyzeSeoPage($page) {
    $checks = [];
    $score = 0;
    $maxScore = 0;
    $issues = [];
    
    // 1. Titre (20 pts)
    $maxScore += 20;
    $title = $page['title'] ?? '';
    $titleLen = mb_strlen($title);
    if ($titleLen >= 30 && $titleLen <= 70) {
        $checks['title'] = ['status' => 'success', 'message' => "Titre OK ({$titleLen} car.)"];
        $score += 20;
    } elseif ($titleLen > 0) {
        $checks['title'] = ['status' => 'warning', 'message' => "Titre: longueur non optimale ({$titleLen} car., idéal 30-70)"];
        $score += 10;
        $issues[] = "Titre: longueur non optimale";
    } else {
        $checks['title'] = ['status' => 'error', 'message' => "Titre manquant"];
        $issues[] = "Titre manquant";
    }
    
    // 2. Meta Title (15 pts)
    $maxScore += 15;
    $metaTitle = $page['seo_title'] ?? $page['meta_title'] ?? '';
    $mtLen = mb_strlen($metaTitle);
    if ($mtLen >= 40 && $mtLen <= 65) {
        $checks['seo_title'] = ['status' => 'success', 'message' => "Meta title OK ({$mtLen} car.)"];
        $score += 15;
    } elseif ($mtLen > 0) {
        $checks['seo_title'] = ['status' => 'warning', 'message' => "Meta title: longueur non optimale ({$mtLen} car., idéal 40-65)"];
        $score += 7;
        $issues[] = "Meta title: longueur non optimale";
    } else {
        $checks['seo_title'] = ['status' => 'error', 'message' => "Meta title manquant"];
        $issues[] = "Meta title manquant";
    }
    
    // 3. Meta Description (15 pts)
    $maxScore += 15;
    $metaDesc = $page['seo_description'] ?? $page['meta_description'] ?? '';
    $mdLen = mb_strlen($metaDesc);
    if ($mdLen >= 120 && $mdLen <= 160) {
        $checks['seo_description'] = ['status' => 'success', 'message' => "Meta description OK ({$mdLen} car.)"];
        $score += 15;
    } elseif ($mdLen > 0) {
        $checks['seo_description'] = ['status' => 'warning', 'message' => "Meta description: longueur non optimale ({$mdLen} car., idéal 120-160)"];
        $score += 7;
        $issues[] = "Meta description: longueur non optimale";
    } else {
        $checks['seo_description'] = ['status' => 'error', 'message' => "Meta description manquante"];
        $issues[] = "Meta description manquante";
    }
    
    // 4. Slug (10 pts)
    $maxScore += 10;
    $slug = $page['slug'] ?? '';
    if (!empty($slug) && mb_strlen($slug) <= 80 && !preg_match('/[A-Z]/', $slug)) {
        $checks['slug'] = ['status' => 'success', 'message' => "URL propre: /{$slug}"];
        $score += 10;
    } elseif (!empty($slug)) {
        $checks['slug'] = ['status' => 'warning', 'message' => "URL pourrait être optimisée"];
        $score += 5;
        $issues[] = "URL à optimiser";
    } else {
        $checks['slug'] = ['status' => 'error', 'message' => "Slug manquant"];
        $issues[] = "Slug manquant";
    }
    
    // 5. Contenu (25 pts)
    $maxScore += 25;
    $content = $page['content'] ?? '';
    $contentText = strip_tags($content);
    $wordCount = str_word_count($contentText);
    if ($wordCount >= 300) {
        $checks['content'] = ['status' => 'success', 'message' => "Contenu riche ({$wordCount} mots)"];
        $score += 25;
    } elseif ($wordCount >= 100) {
        $checks['content'] = ['status' => 'warning', 'message' => "Contenu insuffisant ({$wordCount} mots, idéal 300+)"];
        $score += 12;
        $issues[] = "Contenu très insuffisant";
    } elseif ($wordCount > 0) {
        $checks['content'] = ['status' => 'error', 'message' => "Contenu trop court ({$wordCount} mots)"];
        $score += 5;
        $issues[] = "Contenu trop court";
    } else {
        $checks['content'] = ['status' => 'error', 'message' => "Aucun contenu"];
        $issues[] = "Aucun contenu";
    }
    
    // 6. Mots-clés (15 pts)
    $maxScore += 15;
    $keywords = $page['seo_keywords'] ?? '';
    if (!empty($keywords)) {
        $kwCount = count(array_filter(array_map('trim', explode(',', $keywords))));
        if ($kwCount >= 3) {
            $checks['keywords'] = ['status' => 'success', 'message' => "{$kwCount} mots-clés définis"];
            $score += 15;
        } else {
            $checks['keywords'] = ['status' => 'warning', 'message' => "Seulement {$kwCount} mot(s)-clé(s) (idéal 3+)"];
            $score += 7;
            $issues[] = "Pas assez de mots-clés";
        }
    } else {
        $checks['keywords'] = ['status' => 'error', 'message' => "Mots-clés non définis"];
        $issues[] = "Mots-clés manquants";
    }
    
    // Calcul final
    $percentage = $maxScore > 0 ? round(($score / $maxScore) * 100) : 0;
    $grade = $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : ($percentage >= 40 ? 'warning' : 'error'));
    
    return [
        'score' => $score,
        'max_score' => $maxScore,
        'percentage' => $percentage,
        'grade' => $grade,
        'checks' => $checks,
        'issues' => $issues
    ];
}