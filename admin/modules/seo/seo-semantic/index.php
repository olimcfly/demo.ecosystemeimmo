<?php
/**
 * ============================================================
 * MODULE ANALYSE SÉMANTIQUE UNIFIÉ v4.0
 * Pages + Articles + Google My Business
 * ============================================================
 * Fichier : /admin/modules/seo-semantic/index.php
 * 
 * Types de contenus :
 *   - page       → Table pages
 *   - article    → Table articles
 *   - gmb_post   → Table gmb_posts OU gmb_publications
 *   - gmb_avis   → Table gmb_avis
 *   - gmb_question → Table gmb_questions
 * 
 * Auto-détection des tables et colonnes existantes
 * Auto-migration des colonnes sémantiques manquantes
 * ============================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── CONFIG ───
$configPath = __DIR__ . '/../../../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ config.php introuvable</div>';
    return;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Erreur BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

$adminUrl = defined('ADMIN_URL') ? ADMIN_URL : '/admin';

// ─── DÉTECTION TABLES ───
$existingTables = [];
$res = $pdo->query("SHOW TABLES");
while ($r = $res->fetch(PDO::FETCH_NUM)) {
    $existingTables[] = $r[0];
}

$hasPages         = in_array('pages', $existingTables);
$hasArticles      = in_array('articles', $existingTables);
$hasGmbPosts      = in_array('gmb_posts', $existingTables);
$hasGmbPubs       = in_array('gmb_publications', $existingTables);
$hasGmbAvis       = in_array('gmb_avis', $existingTables);
$hasGmbQuestions   = in_array('gmb_questions', $existingTables);

$hasAnyContent = $hasPages || $hasArticles || $hasGmbPosts || $hasGmbPubs || $hasGmbAvis || $hasGmbQuestions;

if (!$hasAnyContent) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Aucune table de contenu trouvée (pages, articles, gmb_*).</div>';
    return;
}

// ─── HELPERS ───
function getCols($pdo, $table) {
    $cols = [];
    $r = $pdo->query("SHOW COLUMNS FROM `$table`");
    while ($c = $r->fetch()) $cols[] = $c['Field'];
    return $cols;
}

function hasCol($pdo, $table, $col) {
    static $cache = [];
    $key = "$table.$col";
    if (!isset($cache[$key])) {
        $cols = getCols($pdo, $table);
        foreach ($cols as $c) $cache["$table.$c"] = true;
        if (!isset($cache[$key])) $cache[$key] = false;
    }
    return $cache[$key];
}

// ─── AUTO-MIGRATION ───
function ensureSemCols($pdo, $table) {
    $needed = [
        'seo_score'            => "INT DEFAULT NULL",
        'semantic_score'       => "INT DEFAULT NULL",
        'semantic_data'        => "LONGTEXT DEFAULT NULL",
        'semantic_analyzed_at' => "DATETIME DEFAULT NULL",
        'noindex'              => "TINYINT(1) NOT NULL DEFAULT 0",
    ];
    $existing = getCols($pdo, $table);
    $added = 0;
    foreach ($needed as $col => $def) {
        if (!in_array($col, $existing)) {
            try { $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def"); $added++; }
            catch (Exception $e) { error_log("[SemMigrate] $table.$col: " . $e->getMessage()); }
        }
    }
    return $added;
}

$migrated = 0;
if ($hasPages)        $migrated += ensureSemCols($pdo, 'pages');
if ($hasArticles)     $migrated += ensureSemCols($pdo, 'articles');
if ($hasGmbPosts)     $migrated += ensureSemCols($pdo, 'gmb_posts');
if ($hasGmbPubs)      $migrated += ensureSemCols($pdo, 'gmb_publications');
if ($hasGmbAvis)      $migrated += ensureSemCols($pdo, 'gmb_avis');
if ($hasGmbQuestions)  $migrated += ensureSemCols($pdo, 'gmb_questions');

// ─── IA ───
$aiAvailable = (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY))
    || (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY));
$aiProvider = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) $aiProvider = 'Claude';
elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) $aiProvider = 'OpenAI';

// ─── FILTRES ───
$filterType     = $_GET['type'] ?? '';
$filterSeoScore = $_GET['seo'] ?? '';
$filterSemScore = $_GET['sem'] ?? '';
$filterVille    = $_GET['ville'] ?? '';
$filterCat      = $_GET['cat'] ?? '';
$search         = $_GET['q'] ?? '';

// ─── CHARGEMENT DES DONNÉES ───
$allItems = [];

// ════════ PAGES ════════
if ($hasPages && !in_array($filterType, ['article','gmb_post','gmb_avis','gmb_question'])) {
    $hasSem = hasCol($pdo, 'pages', 'semantic_score');
    $hasSeo = hasCol($pdo, 'pages', 'seo_score');
    
    $sql = "SELECT id, title, slug, "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at")
         . " FROM pages WHERE 1=1";
    $params = [];
    if ($search) { $sql .= " AND (title LIKE ? OR slug LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    $sql .= " ORDER BY title ASC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $p) {
        $allItems[] = [
            'id' => $p['id'], 'type' => 'page', 'title' => $p['title'], 'slug' => $p['slug'],
            'seo_score' => $p['seo_score'], 'semantic_score' => $p['semantic_score'],
            'analyzed_at' => $p['semantic_analyzed_at'],
            'ville' => null, 'categorie' => null, 'persona' => null, 'extra' => null,
        ];
    }
}

// ════════ ARTICLES ════════
if ($hasArticles && !in_array($filterType, ['page','gmb_post','gmb_avis','gmb_question'])) {
    $hasSem  = hasCol($pdo, 'articles', 'semantic_score');
    $hasSeo  = hasCol($pdo, 'articles', 'seo_score');
    $hasV    = hasCol($pdo, 'articles', 'ville');
    $hasR    = hasCol($pdo, 'articles', 'raison_vente');
    $hasP    = hasCol($pdo, 'articles', 'persona');
    
    $sql = "SELECT id, titre AS title, slug, "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at") . ", "
         . ($hasV ? "ville" : "NULL AS ville") . ", "
         . ($hasR ? "raison_vente" : "NULL AS raison_vente") . ", "
         . ($hasP ? "persona" : "NULL AS persona")
         . " FROM articles WHERE 1=1";
    $params = [];
    if ($search) { $sql .= " AND (titre LIKE ? OR slug LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
    if ($filterVille && $hasV) { $sql .= " AND ville = ?"; $params[] = $filterVille; }
    if ($filterCat && $hasR) { $sql .= " AND raison_vente = ?"; $params[] = $filterCat; }
    $sql .= " ORDER BY titre ASC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $a) {
        $allItems[] = [
            'id' => $a['id'], 'type' => 'article', 'title' => $a['title'], 'slug' => $a['slug'],
            'seo_score' => $a['seo_score'], 'semantic_score' => $a['semantic_score'],
            'analyzed_at' => $a['semantic_analyzed_at'],
            'ville' => $a['ville'], 'categorie' => $a['raison_vente'], 'persona' => $a['persona'], 'extra' => null,
        ];
    }
}

// ════════ GMB POSTS ════════
if ($hasGmbPosts && !in_array($filterType, ['page','article','gmb_avis','gmb_question'])) {
    $hasSem = hasCol($pdo, 'gmb_posts', 'semantic_score');
    $hasSeo = hasCol($pdo, 'gmb_posts', 'seo_score');
    $hasTitle = hasCol($pdo, 'gmb_posts', 'title');
    $hasContent = hasCol($pdo, 'gmb_posts', 'content');
    $hasType = hasCol($pdo, 'gmb_posts', 'post_type');
    $hasStatus = hasCol($pdo, 'gmb_posts', 'status');
    
    $titleExpr = $hasTitle ? "title" : "CONCAT('Post GMB #', id)";
    $sql = "SELECT id, $titleExpr AS title, "
         . ($hasContent ? "LEFT(content, 80) AS slug_preview" : "'' AS slug_preview") . ", "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at") . ", "
         . ($hasType ? "post_type" : "NULL AS post_type") . ", "
         . ($hasStatus ? "status" : "NULL AS status")
         . " FROM gmb_posts WHERE 1=1";
    $params = [];
    if ($search && $hasTitle) { $sql .= " AND title LIKE ?"; $params[] = "%$search%"; }
    if ($search && $hasContent && !$hasTitle) { $sql .= " AND content LIKE ?"; $params[] = "%$search%"; }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $g) {
        $allItems[] = [
            'id' => $g['id'], 'type' => 'gmb_post', 'title' => $g['title'],
            'slug' => $g['slug_preview'] ? mb_substr(strip_tags($g['slug_preview']), 0, 60) . '...' : 'Post #' . $g['id'],
            'seo_score' => $g['seo_score'], 'semantic_score' => $g['semantic_score'],
            'analyzed_at' => $g['semantic_analyzed_at'],
            'ville' => null, 'categorie' => $g['post_type'] ?? null, 'persona' => null,
            'extra' => $g['status'] ?? null,
        ];
    }
}

// ════════ GMB PUBLICATIONS ════════ (table alternative)
if ($hasGmbPubs && !$hasGmbPosts && !in_array($filterType, ['page','article','gmb_avis','gmb_question'])) {
    $hasSem = hasCol($pdo, 'gmb_publications', 'semantic_score');
    $hasSeo = hasCol($pdo, 'gmb_publications', 'seo_score');
    $hasCont = hasCol($pdo, 'gmb_publications', 'contenu');
    $hasType = hasCol($pdo, 'gmb_publications', 'type');
    $hasStatut = hasCol($pdo, 'gmb_publications', 'statut');
    
    $sql = "SELECT id, CONCAT('Publication #', id) AS title, "
         . ($hasCont ? "LEFT(contenu, 80) AS slug_preview" : "'' AS slug_preview") . ", "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at") . ", "
         . ($hasType ? "type" : "NULL AS pub_type") . ", "
         . ($hasStatut ? "statut" : "NULL AS statut")
         . " FROM gmb_publications WHERE 1=1";
    $params = [];
    if ($search && $hasCont) { $sql .= " AND contenu LIKE ?"; $params[] = "%$search%"; }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $g) {
        $allItems[] = [
            'id' => $g['id'], 'type' => 'gmb_post', 'title' => $g['title'],
            'slug' => mb_substr(strip_tags($g['slug_preview'] ?? ''), 0, 60),
            'seo_score' => $g['seo_score'], 'semantic_score' => $g['semantic_score'],
            'analyzed_at' => $g['semantic_analyzed_at'],
            'ville' => null, 'categorie' => $g['pub_type'] ?? $g['type'] ?? null, 'persona' => null,
            'extra' => $g['statut'] ?? null,
        ];
    }
}

// ════════ GMB AVIS ════════
if ($hasGmbAvis && !in_array($filterType, ['page','article','gmb_post','gmb_question'])) {
    $hasSem = hasCol($pdo, 'gmb_avis', 'semantic_score');
    $hasSeo = hasCol($pdo, 'gmb_avis', 'seo_score');
    $hasComment = hasCol($pdo, 'gmb_avis', 'commentaire');
    $hasAuteur = hasCol($pdo, 'gmb_avis', 'auteur_nom');
    $hasNote = hasCol($pdo, 'gmb_avis', 'note');
    $hasReponse = hasCol($pdo, 'gmb_avis', 'reponse_texte');
    $hasRepondu = hasCol($pdo, 'gmb_avis', 'repondu');
    
    $titleExpr = $hasAuteur ? "CONCAT('Avis de ', auteur_nom)" : "CONCAT('Avis #', id)";
    $sql = "SELECT id, $titleExpr AS title, "
         . ($hasComment ? "LEFT(commentaire, 80) AS slug_preview" : "'' AS slug_preview") . ", "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at") . ", "
         . ($hasNote ? "note" : "NULL AS note") . ", "
         . ($hasRepondu ? "repondu" : "NULL AS repondu") . ", "
         . ($hasReponse ? "LEFT(reponse_texte, 50) AS reponse_preview" : "NULL AS reponse_preview")
         . " FROM gmb_avis WHERE 1=1";
    $params = [];
    if ($search) {
        $searchFields = [];
        if ($hasComment) $searchFields[] = "commentaire LIKE ?";
        if ($hasAuteur)  $searchFields[] = "auteur_nom LIKE ?";
        if (!empty($searchFields)) {
            $sql .= " AND (" . implode(" OR ", $searchFields) . ")";
            foreach ($searchFields as $_) $params[] = "%$search%";
        }
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $a) {
        $stars = '';
        if ($a['note'] !== null) {
            $n = (int)$a['note'];
            $stars = str_repeat('★', $n) . str_repeat('☆', 5 - $n);
        }
        $allItems[] = [
            'id' => $a['id'], 'type' => 'gmb_avis', 'title' => $a['title'],
            'slug' => mb_substr(strip_tags($a['slug_preview'] ?? ''), 0, 60),
            'seo_score' => $a['seo_score'], 'semantic_score' => $a['semantic_score'],
            'analyzed_at' => $a['semantic_analyzed_at'],
            'ville' => null, 'categorie' => $stars ?: null, 'persona' => null,
            'extra' => $a['repondu'] ? 'répondu' : ($a['repondu'] === '0' || $a['repondu'] === 0 ? 'à répondre' : null),
        ];
    }
}

// ════════ GMB QUESTIONS ════════
if ($hasGmbQuestions && !in_array($filterType, ['page','article','gmb_post','gmb_avis'])) {
    $hasSem = hasCol($pdo, 'gmb_questions', 'semantic_score');
    $hasSeo = hasCol($pdo, 'gmb_questions', 'seo_score');
    $hasQ = hasCol($pdo, 'gmb_questions', 'question_texte');
    $hasAuteur = hasCol($pdo, 'gmb_questions', 'question_auteur');
    $hasRepondu = hasCol($pdo, 'gmb_questions', 'repondu');
    $hasReponse = hasCol($pdo, 'gmb_questions', 'reponse_texte');
    
    $titleExpr = $hasQ ? "LEFT(question_texte, 100)" : "CONCAT('Question #', id)";
    $sql = "SELECT id, $titleExpr AS title, "
         . ($hasAuteur ? "question_auteur" : "NULL AS question_auteur") . ", "
         . ($hasSeo ? "seo_score" : "NULL AS seo_score") . ", "
         . ($hasSem ? "semantic_score, semantic_analyzed_at" : "NULL AS semantic_score, NULL AS semantic_analyzed_at") . ", "
         . ($hasRepondu ? "repondu" : "NULL AS repondu")
         . " FROM gmb_questions WHERE 1=1";
    $params = [];
    if ($search && $hasQ) { $sql .= " AND question_texte LIKE ?"; $params[] = "%$search%"; }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    
    foreach ($stmt->fetchAll() as $q) {
        $allItems[] = [
            'id' => $q['id'], 'type' => 'gmb_question', 'title' => $q['title'],
            'slug' => $q['question_auteur'] ? 'par ' . $q['question_auteur'] : '',
            'seo_score' => $q['seo_score'], 'semantic_score' => $q['semantic_score'],
            'analyzed_at' => $q['semantic_analyzed_at'],
            'ville' => null, 'categorie' => null, 'persona' => null,
            'extra' => $q['repondu'] ? 'répondu' : ($q['repondu'] === '0' || $q['repondu'] === 0 ? 'à répondre' : null),
        ];
    }
}

// ─── FILTRES SCORE ───
if ($filterSeoScore) {
    $allItems = array_filter($allItems, function($i) use ($filterSeoScore) {
        $s = (int)($i['seo_score'] ?? 0);
        return match($filterSeoScore) {
            'excellent' => $s >= 80, 'good' => $s >= 60 && $s < 80,
            'warning' => $s >= 40 && $s < 60, 'error' => $s > 0 && $s < 40,
            'none' => $s === 0 || $s === null, default => true
        };
    });
}
if ($filterSemScore) {
    $allItems = array_filter($allItems, function($i) use ($filterSemScore) {
        $s = (int)($i['semantic_score'] ?? 0);
        return match($filterSemScore) {
            'excellent' => $s >= 80, 'good' => $s >= 60 && $s < 80,
            'warning' => $s >= 40 && $s < 60, 'error' => $s > 0 && $s < 40,
            'none' => $s === 0 || $s === null, default => true
        };
    });
}

// Tri
usort($allItems, function($a, $b) {
    $sa = (int)($a['semantic_score'] ?? 0);
    $sb = (int)($b['semantic_score'] ?? 0);
    if ($sa === 0 && $sb > 0) return -1;
    if ($sb === 0 && $sa > 0) return 1;
    return $sa - $sb;
});
$allItems = array_values($allItems);

// ─── STATS ───
$totalItems = count($allItems);
$countByType = ['page' => 0, 'article' => 0, 'gmb_post' => 0, 'gmb_avis' => 0, 'gmb_question' => 0];
$analyzedSem = 0; $avgSem = 0; $avgSeo = 0; $analyzedSeo = 0;
$semExcellent = 0; $semNeedWork = 0;
foreach ($allItems as $i) {
    $countByType[$i['type']] = ($countByType[$i['type']] ?? 0) + 1;
    $sem = (int)($i['semantic_score'] ?? 0);
    $seo = (int)($i['seo_score'] ?? 0);
    if ($sem > 0) { $analyzedSem++; $avgSem += $sem; if ($sem >= 80) $semExcellent++; if ($sem < 60) $semNeedWork++; }
    if ($seo > 0) { $analyzedSeo++; $avgSeo += $seo; }
}
$avgSem = $analyzedSem > 0 ? round($avgSem / $analyzedSem) : 0;
$avgSeo = $analyzedSeo > 0 ? round($avgSeo / $analyzedSeo) : 0;
$totalGmb = $countByType['gmb_post'] + $countByType['gmb_avis'] + $countByType['gmb_question'];

// Listes filtres
$villes = ($hasArticles && hasCol($pdo, 'articles', 'ville'))
    ? $pdo->query("SELECT DISTINCT ville FROM articles WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN)
    : [];
$categories = ($hasArticles && hasCol($pdo, 'articles', 'raison_vente'))
    ? $pdo->query("SELECT DISTINCT raison_vente FROM articles WHERE raison_vente IS NOT NULL AND raison_vente != '' ORDER BY raison_vente")->fetchAll(PDO::FETCH_COLUMN)
    : [];

// Type labels & config
$typeConfig = [
    'page'         => ['label' => 'Page',     'icon' => 'file-alt',         'class' => 'page',     'color' => '#1d4ed8', 'bg' => '#dbeafe'],
    'article'      => ['label' => 'Article',  'icon' => 'newspaper',        'class' => 'article',  'color' => '#be185d', 'bg' => '#fce7f3'],
    'gmb_post'     => ['label' => 'GMB Post', 'icon' => 'map-marker-alt',   'class' => 'gmb-post', 'color' => '#047857', 'bg' => '#d1fae5'],
    'gmb_avis'     => ['label' => 'GMB Avis', 'icon' => 'star',             'class' => 'gmb-avis', 'color' => '#b45309', 'bg' => '#fef3c7'],
    'gmb_question'  => ['label' => 'GMB Q&A', 'icon' => 'question-circle',  'class' => 'gmb-qa',   'color' => '#9333ea', 'bg' => '#f3e8ff'],
];

// Available filter types (only those that have data)
$availableTypes = [];
foreach ($typeConfig as $t => $cfg) {
    if (($countByType[$t] ?? 0) > 0 || ($t === 'page' && $hasPages) || ($t === 'article' && $hasArticles)) {
        $availableTypes[$t] = $cfg;
    }
}
?>

<style>
/* ════════════════════════════════════════════════
   SEMANTIC UNIFIED MODULE v4.0
   ════════════════════════════════════════════════ */
.sem-u4 {
    --primary: #6366f1; --secondary: #8b5cf6;
    --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #3b82f6;
    --light: #f8fafc; --border: #e2e8f0; --text: #1e293b; --text-sec: #64748b;
    --grad: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --grad-ai: linear-gradient(135deg, #8b5cf6, #ec4899);
    --gmb-blue: #4285f4; --gmb-green: #34a853; --gmb-yellow: #fbbc04; --gmb-red: #ea4335;
}
.sem-u4 * { box-sizing: border-box; }

/* Header */
.sem-u4 .hdr {
    background: var(--grad); border-radius: 16px; padding: 28px 30px;
    color: white; margin-bottom: 24px;
    display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
}
.sem-u4 .hdr h2 { margin: 0; font-size: 1.6rem; display: flex; align-items: center; gap: 12px; }
.sem-u4 .hdr p { margin: 4px 0 0; opacity: 0.9; font-size: 14px; }
.sem-u4 .ai-b {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
}
.sem-u4 .hdr-act { display: flex; gap: 10px; flex-wrap: wrap; }

/* Stats */
.sem-u4 .stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px; margin-bottom: 24px;
}
.sem-u4 .st {
    background: white; border-radius: 12px; padding: 12px;
    border: 1px solid var(--border); display: flex; align-items: center; gap: 10px;
}
.sem-u4 .st-i {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem; color: white; flex-shrink: 0;
}
.sem-u4 .st-v { font-size: 1.15rem; font-weight: 700; color: var(--text); }
.sem-u4 .st-l { font-size: 0.65rem; color: var(--text-sec); text-transform: uppercase; letter-spacing: 0.3px; }

/* Toolbar */
.sem-u4 .tb { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
.sem-u4 .sb { position: relative; flex: 1; max-width: 200px; min-width: 120px; }
.sem-u4 .sb input {
    width: 100%; padding: 9px 12px 9px 34px;
    border: 1px solid var(--border); border-radius: 8px; font-size: 13px;
}
.sem-u4 .sb input:focus { outline: none; border-color: var(--primary); }
.sem-u4 .sb i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-sec); font-size: 12px; }
.sem-u4 .fsel {
    padding: 9px 10px; border: 1px solid var(--border); border-radius: 8px;
    font-size: 12px; background: white; max-width: 155px;
}

/* Buttons */
.sem-u4 .btn {
    padding: 9px 16px; border: none; border-radius: 8px; cursor: pointer;
    font-weight: 600; font-size: 12px; text-decoration: none;
    display: inline-flex; align-items: center; gap: 7px; transition: all 0.2s;
}
.sem-u4 .btn:hover { transform: translateY(-1px); }
.sem-u4 .btn-p { background: var(--grad); color: white; }
.sem-u4 .btn-p:hover { box-shadow: 0 6px 20px rgba(102,126,234,0.4); }
.sem-u4 .btn-s { background: white; border: 1px solid var(--border); color: var(--text); }
.sem-u4 .btn-ai { background: var(--grad-ai); color: white; }
.sem-u4 .btn-ok { background: var(--success); color: white; }
.sem-u4 .btn-sm { padding: 6px 12px; font-size: 11px; }
.sem-u4 .btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; }

/* Alert */
.sem-u4 .alrt {
    padding: 11px 16px; border-radius: 10px; margin-bottom: 18px;
    display: flex; align-items: center; gap: 10px; font-size: 13px;
}
.sem-u4 .alrt-ok  { background: #d1fae5; color: #065f46; }
.sem-u4 .alrt-w   { background: #fef3c7; color: #92400e; }

/* Table */
.sem-u4 .tw {
    background: white; border-radius: 12px;
    border: 1px solid var(--border); overflow-x: auto;
}
.sem-u4 table { width: 100%; border-collapse: collapse; min-width: 950px; }
.sem-u4 th {
    padding: 11px 12px; text-align: left; font-weight: 600;
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--text-sec); background: var(--light);
    border-bottom: 2px solid var(--border); white-space: nowrap;
}
.sem-u4 td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.sem-u4 tr:hover { background: #fafbfc; }
.sem-u4 tr.anim { background: rgba(99,102,241,0.04); }

/* Type badges */
.sem-u4 .tp {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 6px;
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px;
    white-space: nowrap;
}
<?php foreach ($typeConfig as $t => $cfg): ?>
.sem-u4 .tp.<?= $cfg['class'] ?> { background: <?= $cfg['bg'] ?>; color: <?= $cfg['color'] ?>; }
<?php endforeach; ?>

/* Title cell */
.sem-u4 .tc { display: flex; flex-direction: column; gap: 1px; max-width: 320px; }
.sem-u4 .tc .t { font-weight: 600; font-size: 12px; color: var(--text); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sem-u4 .tc .s { font-size: 10px; color: var(--text-sec); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sem-u4 .tc .tg { display: flex; gap: 3px; flex-wrap: wrap; margin-top: 2px; }
.sem-u4 .mt {
    display: inline-block; padding: 1px 5px; border-radius: 8px;
    font-size: 8px; font-weight: 600; max-width: 90px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.sem-u4 .mt.v  { background: #dbeafe; color: #1d4ed8; }
.sem-u4 .mt.c  { background: #fef3c7; color: #92400e; }
.sem-u4 .mt.p  { background: #ede9fe; color: #7c3aed; }
.sem-u4 .mt.ex { background: #f1f5f9; color: #475569; }

/* Score */
.sem-u4 .sc { text-align: center; }
.sem-u4 .sp {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 3px 9px; border-radius: 20px;
    font-weight: 700; font-size: 11px; min-width: 48px; justify-content: center;
}
.sem-u4 .sp.exc { background: #d1fae5; color: #059669; }
.sem-u4 .sp.goo { background: #dcfce7; color: #16a34a; }
.sem-u4 .sp.war { background: #fef3c7; color: #d97706; }
.sem-u4 .sp.err { background: #fee2e2; color: #dc2626; }
.sem-u4 .sp.non { background: #f1f5f9; color: #94a3b8; }
.sem-u4 .sb2 { width: 46px; height: 3px; background: #e2e8f0; border-radius: 2px; overflow: hidden; margin: 3px auto 0; }
.sem-u4 .sb2f { height: 100%; border-radius: 2px; transition: width 0.6s; }
.sem-u4 .sb2f.exc { background: #10b981; } .sem-u4 .sb2f.goo { background: #22c55e; }
.sem-u4 .sb2f.war { background: #f59e0b; } .sem-u4 .sb2f.err { background: #ef4444; }

/* Analyze button */
.sem-u4 .abtn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 8px; border: none;
    cursor: pointer; font-size: 11px; font-weight: 600;
    background: var(--grad); color: white; transition: all 0.25s;
}
.sem-u4 .abtn:hover { transform: scale(1.05); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
.sem-u4 .abtn.run { pointer-events: none; opacity: 0.7; background: linear-gradient(135deg, #94a3b8, #64748b); }
.sem-u4 .abtn .spin {
    display: none; width: 12px; height: 12px;
    border: 2px solid rgba(255,255,255,0.3); border-top-color: white;
    border-radius: 50%; animation: s4spin 0.8s linear infinite;
}
.sem-u4 .abtn.run .spin { display: inline-block; }
.sem-u4 .abtn.run .btx { display: none; }
@keyframes s4spin { to { transform: rotate(360deg); } }

/* Action icons */
.sem-u4 .ai2 { display: flex; gap: 3px; }
.sem-u4 .ai2 a, .sem-u4 .ai2 button {
    width: 28px; height: 28px; border-radius: 6px; border: none;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.2s; text-decoration: none; font-size: 10px;
}
.sem-u4 .ai2 .ed  { background: #fef3c7; color: #d97706; }
.sem-u4 .ai2 .vw  { background: #d1fae5; color: #059669; }
.sem-u4 .ai2 .dt  { background: #dbeafe; color: #2563eb; }
.sem-u4 .ai2 a:hover, .sem-u4 .ai2 button:hover { transform: scale(1.1); }

.sem-u4 .dm { font-size: 10px; color: var(--text-sec); white-space: nowrap; }

/* Loading */
.sem-u4 .lo {
    display: none; position: fixed; inset: 0;
    background: rgba(255,255,255,0.95); z-index: 2000;
    align-items: center; justify-content: center; flex-direction: column; gap: 14px;
}
.sem-u4 .lo.on { display: flex; }
.sem-u4 .lo .lsp {
    width: 48px; height: 48px;
    border: 4px solid var(--border); border-top-color: var(--primary);
    border-radius: 50%; animation: s4spin 1s linear infinite;
}
.sem-u4 .lo .lt { font-size: 1rem; color: var(--text); font-weight: 500; }
.sem-u4 .lo .ls { color: var(--text-sec); font-size: 0.85rem; }

/* Modal */
.sem-u4 .mo { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 3000;
    align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.sem-u4 .mo.on { display: flex; }
.sem-u4 .mb {
    background: white; border-radius: 16px; width: 95%; max-width: 720px;
    max-height: 85vh; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}
.sem-u4 .mh { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.sem-u4 .mh h3 { margin: 0; font-size: 1rem; color: var(--text); }
.sem-u4 .mx { width: 30px; height: 30px; border: none; background: var(--light); border-radius: 8px; cursor: pointer; color: var(--text-sec); display: flex; align-items: center; justify-content: center; font-size: 15px; }
.sem-u4 .mbd { padding: 20px; overflow-y: auto; flex: 1; }
.sem-u4 .mf { padding: 14px 20px; border-top: 1px solid var(--border); background: var(--light); display: flex; gap: 10px; justify-content: space-between; flex-wrap: wrap; }

/* Detail elements */
.sem-u4 .sh { text-align: center; padding: 20px; border-radius: 12px; color: white; margin-bottom: 16px; }
.sem-u4 .sh.exc { background: linear-gradient(135deg, #10b981, #059669); }
.sem-u4 .sh.goo { background: linear-gradient(135deg, #22c55e, #16a34a); }
.sem-u4 .sh.war { background: linear-gradient(135deg, #f59e0b, #d97706); }
.sem-u4 .sh.err { background: linear-gradient(135deg, #ef4444, #dc2626); }
.sem-u4 .sh.non { background: linear-gradient(135deg, #94a3b8, #64748b); }
.sem-u4 .sh .big { font-size: 2.5rem; font-weight: 700; }
.sem-u4 .kw { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 10px; }
.sem-u4 .kw span { padding: 4px 9px; border-radius: 14px; font-size: 11px; font-weight: 500; display: inline-flex; align-items: center; gap: 3px; }
.sem-u4 .kw .ok  { background: #d1fae5; color: #059669; }
.sem-u4 .kw .ms  { background: #fee2e2; color: #dc2626; }
.sem-u4 .kw .sg  { background: #ede9fe; color: #7c3aed; }
.sem-u4 .ds { margin-bottom: 14px; }
.sem-u4 .ds h5 { margin: 0 0 6px; font-size: 12px; color: var(--primary); display: flex; align-items: center; gap: 5px; }
.sem-u4 .wi { padding: 7px 10px; background: var(--light); border-radius: 6px; margin-bottom: 5px; font-size: 12px; color: var(--text); display: flex; align-items: flex-start; gap: 7px; }
.sem-u4 .wn { width: 20px; height: 20px; background: var(--grad); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; flex-shrink: 0; }

/* Empty */
.sem-u4 .emp { text-align: center; padding: 50px 30px; color: var(--text-sec); }
.sem-u4 .emp i { font-size: 44px; opacity: 0.15; display: block; margin-bottom: 14px; }

@media (max-width: 768px) {
    .sem-u4 .hdr { flex-direction: column; text-align: center; }
    .sem-u4 .stats { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="sem-u4">

<!-- Loading -->
<div class="lo" id="sLo">
    <div class="lsp"></div>
    <div class="lt" id="sLoT">Analyse en cours...</div>
    <div class="ls" id="sLoS"></div>
</div>

<!-- Modal -->
<div class="mo" id="sMo">
    <div class="mb">
        <div class="mh">
            <h3 id="sMoT"><i class="fas fa-brain"></i> Détails sémantiques</h3>
            <button class="mx" onclick="sClose()"><i class="fas fa-times"></i></button>
        </div>
        <div class="mbd" id="sMoB"></div>
        <div class="mf">
            <button class="btn btn-s btn-sm" onclick="sClose()">Fermer</button>
            <div style="display:flex;gap:8px;">
                <a href="#" class="btn btn-sm btn-s" id="sMoEd"><i class="fas fa-edit"></i> Éditer</a>
                <button class="btn btn-sm btn-p" id="sMoAn"><i class="fas fa-sync-alt"></i> Re-analyser</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════ HEADER ═══════ -->
<div class="hdr">
    <div>
        <h2>
            <i class="fas fa-brain"></i> Analyse Sémantique
            <?php if ($aiProvider): ?>
                <span class="ai-b"><i class="fas fa-robot"></i> <?= $aiProvider ?></span>
            <?php endif; ?>
        </h2>
        <p><?= $totalItems ?> contenus — Pages, Articles & Google My Business</p>
    </div>
    <div class="hdr-act">
        <?php if ($aiAvailable): ?>
        <button class="btn btn-ai" onclick="sBatchMissing()">
            <i class="fas fa-magic"></i> Analyser non analysés
        </button>
        <?php endif; ?>
        <button class="btn" style="background:rgba(255,255,255,0.2);color:white;" onclick="sBatchAll()">
            <i class="fas fa-sync-alt"></i> Tout analyser
        </button>
    </div>
</div>

<!-- ═══════ STATS ═══════ -->
<div class="stats">
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="fas fa-layer-group"></i></div>
        <div><span class="st-v"><?= $totalItems ?></span><br><span class="st-l">Total</span></div>
    </div>
    <?php if ($countByType['page'] > 0): ?>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#14b8a6,#0d9488);"><i class="fas fa-file-alt"></i></div>
        <div><span class="st-v"><?= $countByType['page'] ?></span><br><span class="st-l">Pages</span></div>
    </div>
    <?php endif; ?>
    <?php if ($countByType['article'] > 0): ?>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#ec4899,#be185d);"><i class="fas fa-newspaper"></i></div>
        <div><span class="st-v"><?= $countByType['article'] ?></span><br><span class="st-l">Articles</span></div>
    </div>
    <?php endif; ?>
    <?php if ($totalGmb > 0): ?>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,<?= $typeConfig['gmb_post']['color'] ?>,#065f46);"><i class="fab fa-google"></i></div>
        <div><span class="st-v"><?= $totalGmb ?></span><br><span class="st-l">GMB</span></div>
    </div>
    <?php endif; ?>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><i class="fas fa-brain"></i></div>
        <div><span class="st-v"><?= $analyzedSem ?>/<?= $totalItems ?></span><br><span class="st-l">Analysés</span></div>
    </div>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,<?= $avgSem >= 60 ? '#10b981,#059669' : '#f59e0b,#d97706' ?>);"><i class="fas fa-tachometer-alt"></i></div>
        <div><span class="st-v"><?= $avgSem ?>%</span><br><span class="st-l">Moy. Sém.</span></div>
    </div>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-trophy"></i></div>
        <div><span class="st-v"><?= $semExcellent ?></span><br><span class="st-l">Excellents</span></div>
    </div>
    <div class="st">
        <div class="st-i" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-exclamation-triangle"></i></div>
        <div><span class="st-v"><?= $semNeedWork ?></span><br><span class="st-l">À optimiser</span></div>
    </div>
    <?php if ($aiProvider): ?>
    <div class="st">
        <div class="st-i" style="background:var(--grad-ai);"><i class="fas fa-robot"></i></div>
        <div><span class="st-v"><?= $aiProvider ?></span><br><span class="st-l">IA Active</span></div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══════ TOOLBAR ═══════ -->
<div class="tb">
    <form method="GET" style="display:contents;">
        <input type="hidden" name="page" value="seo-semantic">
        <div class="sb">
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="type" class="fsel" onchange="this.form.submit()">
            <option value="">Tous les types</option>
            <?php foreach ($availableTypes as $t => $cfg): ?>
            <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                <?= $cfg['label'] ?> (<?= $countByType[$t] ?? 0 ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <select name="sem" class="fsel" onchange="this.form.submit()">
            <option value="">Score Sémantique</option>
            <option value="excellent" <?= $filterSemScore === 'excellent' ? 'selected' : '' ?>>🏆 ≥80%</option>
            <option value="good" <?= $filterSemScore === 'good' ? 'selected' : '' ?>>✅ 60-79%</option>
            <option value="warning" <?= $filterSemScore === 'warning' ? 'selected' : '' ?>>⚠️ 40-59%</option>
            <option value="error" <?= $filterSemScore === 'error' ? 'selected' : '' ?>>❌ &lt;40%</option>
            <option value="none" <?= $filterSemScore === 'none' ? 'selected' : '' ?>>🔍 Non analysé</option>
        </select>
        <select name="seo" class="fsel" onchange="this.form.submit()">
            <option value="">Score SEO</option>
            <option value="excellent" <?= $filterSeoScore === 'excellent' ? 'selected' : '' ?>>🏆 ≥80%</option>
            <option value="good" <?= $filterSeoScore === 'good' ? 'selected' : '' ?>>✅ 60-79%</option>
            <option value="warning" <?= $filterSeoScore === 'warning' ? 'selected' : '' ?>>⚠️ 40-59%</option>
            <option value="error" <?= $filterSeoScore === 'error' ? 'selected' : '' ?>>❌ &lt;40%</option>
            <option value="none" <?= $filterSeoScore === 'none' ? 'selected' : '' ?>>🔍 N/A</option>
        </select>
        <?php if (!empty($villes)): ?>
        <select name="ville" class="fsel" onchange="this.form.submit()">
            <option value="">Ville</option>
            <?php foreach ($villes as $v): ?>
            <option value="<?= htmlspecialchars($v) ?>" <?= $filterVille === $v ? 'selected' : '' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if (!empty($categories)): ?>
        <select name="cat" class="fsel" onchange="this.form.submit()">
            <option value="">Catégorie</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $filterCat === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="btn btn-s btn-sm"><i class="fas fa-filter"></i></button>
    </form>
</div>

<?php if ($migrated > 0): ?>
<div class="alrt alrt-ok"><i class="fas fa-database"></i> <strong><?= $migrated ?> colonnes ajoutées.</strong> Lancez une analyse pour calculer les scores.</div>
<?php endif; ?>

<?php if (!$aiAvailable): ?>
<div class="alrt alrt-w"><i class="fas fa-exclamation-triangle"></i> <strong>IA non configurée.</strong> Ajoutez <code>OPENAI_API_KEY</code> ou <code>ANTHROPIC_API_KEY</code> dans config.php.</div>
<?php endif; ?>

<!-- ═══════ TABLE ═══════ -->
<?php if (!empty($allItems)): ?>
<div class="tw">
    <table>
        <thead>
            <tr>
                <th style="width:75px;">Type</th>
                <th>Titre / Contenu</th>
                <th style="text-align:center;">Score SEO</th>
                <th style="text-align:center;">Score Sémantique</th>
                <th style="text-align:center;">Analyse</th>
                <th>Analysé le</th>
                <th style="width:100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($allItems as $item):
            $seo = (int)($item['seo_score'] ?? 0);
            $sem = (int)($item['semantic_score'] ?? 0);
            $seoG = $seo >= 80 ? 'exc' : ($seo >= 60 ? 'goo' : ($seo >= 40 ? 'war' : ($seo > 0 ? 'err' : 'non')));
            $semG = $sem >= 80 ? 'exc' : ($sem >= 60 ? 'goo' : ($sem >= 40 ? 'war' : ($sem > 0 ? 'err' : 'non')));
            $tc = $typeConfig[$item['type']] ?? $typeConfig['page'];
            $uid = $item['type'] . '-' . $item['id'];
            
            // Edit URL
            $editUrl = match($item['type']) {
                'page'         => '?page=pages&action=edit&id=' . $item['id'],
                'article'      => '?page=blog&action=edit&id=' . $item['id'],
                'gmb_post'     => '?page=gmb&action=edit-post&id=' . $item['id'],
                'gmb_avis'     => '?page=gmb&action=view-avis&id=' . $item['id'],
                'gmb_question' => '?page=gmb&action=view-question&id=' . $item['id'],
                default        => '#'
            };
            // View URL
            $viewUrl = match($item['type']) {
                'page'    => '/' . htmlspecialchars($item['slug']),
                'article' => '/blog/' . htmlspecialchars($item['slug']),
                default   => null
            };
        ?>
        <tr id="r-<?= $uid ?>">
            <td>
                <span class="tp <?= $tc['class'] ?>">
                    <i class="fas fa-<?= $tc['icon'] ?>"></i>
                    <?= $tc['label'] ?>
                </span>
            </td>
            <td>
                <div class="tc">
                    <span class="t" title="<?= htmlspecialchars($item['title']) ?>"><?= htmlspecialchars($item['title']) ?></span>
                    <?php if ($item['slug']): ?>
                    <span class="s"><?= htmlspecialchars(mb_substr($item['slug'], 0, 60)) ?></span>
                    <?php endif; ?>
                    <?php if ($item['ville'] || $item['categorie'] || $item['persona'] || $item['extra']): ?>
                    <div class="tg">
                        <?php if ($item['ville']): ?><span class="mt v"><?= htmlspecialchars($item['ville']) ?></span><?php endif; ?>
                        <?php if ($item['categorie']): ?><span class="mt c"><?= htmlspecialchars($item['categorie']) ?></span><?php endif; ?>
                        <?php if ($item['persona']): ?><span class="mt p"><?= htmlspecialchars($item['persona']) ?></span><?php endif; ?>
                        <?php if ($item['extra']): ?><span class="mt ex"><?= htmlspecialchars($item['extra']) ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="sc">
                <div id="seo-<?= $uid ?>">
                    <span class="sp <?= $seoG ?>"><?= $seo > 0 ? $seo . '%' : '—' ?></span>
                    <?php if ($seo > 0): ?><div class="sb2"><div class="sb2f <?= $seoG ?>" style="width:<?= $seo ?>%;"></div></div><?php endif; ?>
                </div>
            </td>
            <td class="sc">
                <div id="sem-<?= $uid ?>">
                    <span class="sp <?= $semG ?>">
                        <?php if ($sem > 0): ?><i class="fas fa-brain" style="font-size:9px;"></i> <?= $sem ?>%<?php else: ?>—<?php endif; ?>
                    </span>
                    <?php if ($sem > 0): ?><div class="sb2"><div class="sb2f <?= $semG ?>" style="width:<?= $sem ?>%;"></div></div><?php endif; ?>
                </div>
            </td>
            <td style="text-align:center;">
                <button class="abtn" id="b-<?= $uid ?>"
                        onclick="sAn('<?= $item['type'] ?>',<?= $item['id'] ?>,this)"
                        <?= !$aiAvailable ? 'disabled title="IA non configurée"' : '' ?>>
                    <span class="spin"></span>
                    <span class="btx"><i class="fas fa-search"></i> Analyser</span>
                </button>
            </td>
            <td>
                <span class="dm" id="d-<?= $uid ?>">
                    <?= !empty($item['analyzed_at']) ? date('d/m/y H:i', strtotime($item['analyzed_at'])) : '—' ?>
                </span>
            </td>
            <td>
                <div class="ai2">
                    <?php if ($sem > 0): ?>
                    <button class="dt" onclick="sDetail('<?= $item['type'] ?>',<?= $item['id'] ?>)" title="Détails"><i class="fas fa-info-circle"></i></button>
                    <?php endif; ?>
                    <a href="<?= $editUrl ?>" class="ed" title="Éditer"><i class="fas fa-edit"></i></a>
                    <?php if ($viewUrl): ?>
                    <a href="<?= $viewUrl ?>" target="_blank" class="vw" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="tw">
    <div class="emp">
        <i class="fas fa-brain"></i>
        <h3>Aucun contenu trouvé</h3>
        <p>Modifiez vos filtres ou créez des pages / articles / posts GMB.</p>
    </div>
</div>
<?php endif; ?>

</div><!-- /.sem-u4 -->

<script>
/**
 * ════════════════════════════════════════
 * JS MODULE SÉMANTIQUE UNIFIÉ v4.0
 * ════════════════════════════════════════
 */
const SA = '<?= $adminUrl ?>/modules/seo-semantic/api.php';

// ═══ ANALYSE INDIVIDUELLE ═══
function sAn(type, id, btn) {
    const uid = type + '-' + id;
    btn.classList.add('run');
    document.getElementById('r-' + uid)?.classList.add('anim');
    
    fetch(SA + '?action=analyze&content_type=' + type + '&id=' + id)
        .then(r => r.text())
        .then(text => {
            let d;
            try { d = JSON.parse(text); } catch(e) {
                console.error('Parse:', text.substring(0, 300));
                throw new Error('Réponse invalide');
            }
            btn.classList.remove('run');
            document.getElementById('r-' + uid)?.classList.remove('anim');
            
            if (d.success) {
                const sc = d.analysis?.score_semantic || d.score || 0;
                sUpScore(uid, sc);
                sUpDate(uid);
                sNotif('ok', '✅ ' + sc + '% — ' + (d.analysis?.score_label || 'Analysé'));
            } else {
                sNotif('err', d.error || 'Erreur');
            }
        })
        .catch(e => {
            btn.classList.remove('run');
            document.getElementById('r-' + uid)?.classList.remove('anim');
            sNotif('err', e.message);
        });
}

// ═══ ANALYSE MASSE ═══
function sBatchAll() {
    if (!confirm('Ré-analyser tous les <?= $totalItems ?> contenus ?')) return;
    sBatch(false);
}
function sBatchMissing() { sBatch(true); }

function sBatch(onlyMissing) {
    const rows = document.querySelectorAll('tr[id^="r-"]');
    let q = [];
    rows.forEach(r => {
        const uid = r.id.replace('r-', '');
        const [type, ...idParts] = uid.split('-');
        const id = idParts.join('-');
        if (onlyMissing) {
            const el = document.getElementById('sem-' + uid);
            if (el && (el.textContent.trim() === '—' || el.textContent.trim() === '')) q.push({type, id, uid});
        } else {
            q.push({type, id, uid});
        }
    });
    
    if (!q.length) { sNotif('ok', 'Tous les contenus sont déjà analysés !'); return; }
    
    sShowL('Analyse sémantique...', '0/' + q.length);
    let i = 0, ok = 0;
    
    const next = () => {
        if (i >= q.length) {
            sHideL();
            sNotif('ok', '✅ ' + ok + '/' + q.length + ' analysés !');
            setTimeout(() => location.reload(), 1500);
            return;
        }
        document.getElementById('sLoS').textContent = (i+1) + '/' + q.length + ' — ' + q[i].type + ' #' + q[i].id;
        const btn = document.getElementById('b-' + q[i].uid);
        if (btn) btn.classList.add('run');
        
        fetch(SA + '?action=analyze&content_type=' + q[i].type + '&id=' + q[i].id)
            .then(r => r.json())
            .then(d => {
                if (btn) btn.classList.remove('run');
                if (d.success) {
                    sUpScore(q[i].uid, d.analysis?.score_semantic || d.score || 0);
                    sUpDate(q[i].uid);
                    ok++;
                }
                i++; setTimeout(next, 300);
            })
            .catch(() => { if (btn) btn.classList.remove('run'); i++; setTimeout(next, 300); });
    };
    next();
}

// ═══ DÉTAILS ═══
function sDetail(type, id) {
    const uid = type + '-' + id;
    sShowL('Chargement...');
    
    fetch(SA + '?action=details&content_type=' + type + '&id=' + id)
        .then(r => r.json())
        .then(d => {
            sHideL();
            if (!d.success) { sNotif('err', d.error || 'Erreur'); return; }
            
            const a = d.analysis || {};
            const sc = a.score_semantic || 0;
            const g = sc >= 80 ? 'exc' : sc >= 60 ? 'goo' : sc >= 40 ? 'war' : sc > 0 ? 'err' : 'non';
            const title = d.title || '#' + id;
            
            document.getElementById('sMoT').innerHTML = '<i class="fas fa-brain"></i> ' + esc(title);
            
            const editUrl = type === 'page' ? '?page=pages&action=edit&id=' + id 
                : type === 'article' ? '?page=blog&action=edit&id=' + id 
                : '?page=gmb&action=edit&id=' + id;
            document.getElementById('sMoEd').href = editUrl;
            document.getElementById('sMoAn').onclick = () => {
                sClose();
                const btn = document.getElementById('b-' + uid);
                if (btn) sAn(type, id, btn);
            };
            
            let h = '';
            h += '<div class="sh ' + g + '"><div class="big">' + sc + '%</div><div>' + (a.score_label || 'Score') + '</div>';
            if (a.topic_detected) h += '<div style="opacity:0.8;font-size:12px;margin-top:3px;">Sujet : ' + esc(a.topic_detected) + '</div>';
            h += '</div>';
            
            const covered = a.lexical_field?.covered || [];
            if (covered.length) {
                h += '<div class="ds"><h5><i class="fas fa-check-circle" style="color:var(--success)"></i> Champ lexical couvert (' + covered.length + ')</h5>';
                h += '<div class="kw">' + covered.map(w => '<span class="ok"><i class="fas fa-check"></i> ' + esc(w) + '</span>').join('') + '</div></div>';
            }
            
            const miss = a.lexical_field?.missing_critical || [];
            if (miss.length) {
                h += '<div class="ds"><h5><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> Mots manquants critiques</h5>';
                h += '<div class="kw">' + miss.map(w => '<span class="ms"><i class="fas fa-times"></i> ' + esc(w) + '</span>').join('') + '</div></div>';
            }
            
            const words = a.semantic_suggestions?.words_to_add || [];
            if (words.length) {
                h += '<div class="ds"><h5><i class="fas fa-lightbulb" style="color:var(--secondary)"></i> Mots à ajouter</h5>';
                h += '<div class="kw">' + words.map(w => '<span class="sg">' + esc(typeof w === 'string' ? w : w.word) + '</span>').join('') + '</div></div>';
            }
            
            const expr = a.semantic_suggestions?.expressions_to_add || [];
            if (expr.length) {
                h += '<div class="ds"><h5><i class="fas fa-quote-right" style="color:var(--info)"></i> Expressions</h5>';
                h += '<div class="kw">' + expr.map(e => '<span class="sg">' + esc(typeof e === 'string' ? e : e.expression) + '</span>').join('') + '</div></div>';
            }
            
            const qs = a.semantic_suggestions?.questions_to_answer || [];
            if (qs.length) {
                h += '<div class="ds"><h5><i class="fas fa-question-circle" style="color:var(--warning)"></i> Questions</h5>';
                qs.forEach(q => { h += '<div class="wi"><i class="fas fa-question-circle" style="color:var(--warning);flex-shrink:0;"></i> ' + esc(q) + '</div>'; });
                h += '</div>';
            }
            
            const wins = a.quick_wins || [];
            if (wins.length) {
                h += '<div class="ds"><h5><i class="fas fa-bolt" style="color:var(--success)"></i> Actions rapides</h5>';
                wins.forEach((w, i) => { h += '<div class="wi"><span class="wn">' + (i+1) + '</span> ' + esc(w) + '</div>'; });
                h += '</div>';
            }
            
            if (a.overall_assessment) {
                h += '<div class="ds" style="background:rgba(99,102,241,0.05);padding:14px;border-radius:10px;border:1px solid rgba(99,102,241,0.15);">';
                h += '<h5><i class="fas fa-clipboard-check" style="color:var(--primary)"></i> Évaluation</h5>';
                h += '<p style="margin:0;font-size:13px;line-height:1.6;color:var(--text);">' + esc(a.overall_assessment) + '</p></div>';
            }
            
            document.getElementById('sMoB').innerHTML = h;
            document.getElementById('sMo').classList.add('on');
        })
        .catch(e => { sHideL(); sNotif('err', e.message); });
}

// ═══ UI ═══
function sUpScore(uid, sc) {
    const el = document.getElementById('sem-' + uid);
    if (!el) return;
    const g = sc >= 80 ? 'exc' : sc >= 60 ? 'goo' : sc >= 40 ? 'war' : sc > 0 ? 'err' : 'non';
    el.innerHTML = '<span class="sp ' + g + '"><i class="fas fa-brain" style="font-size:9px;"></i> ' + sc + '%</span>'
        + '<div class="sb2"><div class="sb2f ' + g + '" style="width:' + sc + '%;"></div></div>';
    
    // Add detail button if missing
    const row = document.getElementById('r-' + uid);
    if (row && sc > 0) {
        const act = row.querySelector('.ai2');
        if (act && !act.querySelector('.dt')) {
            const parts = uid.split('-');
            const btn = document.createElement('button');
            btn.className = 'dt'; btn.title = 'Détails';
            btn.innerHTML = '<i class="fas fa-info-circle"></i>';
            btn.onclick = () => sDetail(parts[0], parts.slice(1).join('-'));
            act.insertBefore(btn, act.firstChild);
        }
    }
}

function sUpDate(uid) {
    const el = document.getElementById('d-' + uid);
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit',year:'2-digit'})
        + ' ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
}

function sClose() { document.getElementById('sMo').classList.remove('on'); }
function sShowL(t, s) { document.getElementById('sLoT').textContent = t || ''; document.getElementById('sLoS').textContent = s || ''; document.getElementById('sLo').classList.add('on'); }
function sHideL() { document.getElementById('sLo').classList.remove('on'); }

function sNotif(type, msg) {
    document.querySelectorAll('.s4n').forEach(n => n.remove());
    const el = document.createElement('div'); el.className = 's4n';
    const bg = type === 'ok' ? '#10b981' : '#ef4444';
    const ic = type === 'ok' ? 'check-circle' : 'exclamation-circle';
    el.style.cssText = 'position:fixed;top:20px;right:20px;padding:12px 18px;border-radius:10px;color:white;font-weight:500;z-index:5000;animation:s4in .3s;box-shadow:0 8px 24px rgba(0,0,0,.2);background:'+bg+';max-width:360px;font-size:13px;';
    el.innerHTML = '<i class="fas fa-'+ic+'"></i> ' + msg;
    document.body.appendChild(el);
    setTimeout(() => { el.style.animation = 's4out .3s'; setTimeout(() => el.remove(), 300); }, 4000);
}

function esc(s) { return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : ''; }

document.getElementById('sMo')?.addEventListener('click', function(e) { if (e.target === this) sClose(); });

const s4css = document.createElement('style');
s4css.textContent = '@keyframes s4in{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes s4out{from{opacity:1}to{opacity:0;transform:translateX(100px)}}';
document.head.appendChild(s4css);

console.log('🧠 Sémantique Unifié v4.0 — <?= $totalItems ?> contenus (<?= $countByType['page'] ?>P + <?= $countByType['article'] ?>A + <?= $totalGmb ?>GMB)');
</script>