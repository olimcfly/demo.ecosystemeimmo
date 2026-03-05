<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE ARTICLES — Mon Blog  v2.1 PATCH DB
 * /admin/modules/articles/index.php
 *
 * CORRECTIFS v2.1 :
 *  ✅ `titre`        au lieu de `title`   (vrai nom en DB)
 *  ✅ `contenu`      au lieu de `content`
 *  ✅ `focus_keyword` + `main_keyword`    (les deux existent)
 *  ✅ `statut`       (brouillon/publie)   + `status` (published/draft) — double gestion
 *  ✅ `score_semantique` en plus de `semantic_score`
 *  ✅ Filtres adaptés aux deux conventions de statut
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Détecter table ───
$tableName   = 'articles';
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM articles LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->query("SELECT 1 FROM blog_articles LIMIT 1");
        $tableName = 'blog_articles';
    } catch (PDOException $e2) {
        $tableExists = false;
    }
}

// ─── Colonnes disponibles ───
$availCols = [];
if ($tableExists) {
    try {
        $availCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Mapping colonnes réelles (DB eduardo = noms FR) ───
// titre vs title
$colTitle    = in_array('titre',   $availCols) ? 'titre'   : (in_array('title',   $availCols) ? 'title'   : 'titre');
// contenu vs content
$colContent  = in_array('contenu', $availCols) ? 'contenu' : (in_array('content', $availCols) ? 'content' : 'contenu');
// statut (brouillon/publie) vs status (draft/published)
$hasStatut   = in_array('statut',  $availCols); // champ FR original
$hasStatus   = in_array('status',  $availCols); // champ EN ajouté
// keyword
$colKeyword  = in_array('focus_keyword', $availCols) ? 'focus_keyword'
             : (in_array('main_keyword', $availCols) ? 'main_keyword' : null);
// score sémantique
$colSemantic = in_array('score_semantique', $availCols) ? 'score_semantique'
             : (in_array('semantic_score',  $availCols) ? 'semantic_score' : null);
// seo score
$colSeoScore = in_array('seo_score',       $availCols) ? 'seo_score'
             : (in_array('score_technique', $availCols) ? 'score_technique' : null);

// Flags autres colonnes
$hasWordCount     = in_array('word_count',     $availCols);
$hasGoogleIndexed = in_array('google_indexed', $availCols);
$hasIsIndexed     = in_array('is_indexed',     $availCols);
$hasCategory      = in_array('category',       $availCols);
$hasIsFeatured    = in_array('is_featured',    $availCols);
$hasUpdatedAt     = in_array('updated_at',     $availCols);

// ─── Table seo_scores ───
$hasSeoScoresTable = false;
try {
    $pdo->query("SELECT 1 FROM seo_scores LIMIT 1");
    $hasSeoScoresTable = true;
} catch (PDOException $e) {}

// ══════════════════════════════════════════════════════════════
// ─── ROUTING : edit / create / delete → edit.php ───
// ══════════════════════════════════════════════════════════════
$routeAction = $_GET['action'] ?? '';

if (in_array($routeAction, ['edit', 'create', 'delete'])) {
    // Chercher edit.php dans le même dossier
    $editFile = __DIR__ . '/edit.php';
    if (file_exists($editFile)) {
        require $editFile;
        return; // Stoppe l'exécution du reste de l'index (liste)
    } else {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:20px;border-radius:10px;margin:20px;font-family:sans-serif;">
            <strong>⚠️ Fichier manquant :</strong> <code>/admin/modules/articles/edit.php</code><br><br>
            Déposez le fichier <code>edit.php</code> dans le dossier <code>/admin/modules/articles/</code>
        </div>';
        return;
    }
}

// ─── Filtres URL ───
$filterStatus  = $_GET['status']   ?? 'all';
$filterIndexed = $_GET['indexed']  ?? 'all';
$filterCat     = $_GET['category'] ?? 'all';
$searchQuery   = trim($_GET['q']   ?? '');
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$perPage       = 25;
$offset        = ($currentPage - 1) * $perPage;

// ─── Catégories ───
$categories = [];
if ($tableExists && $hasCategory) {
    try {
        $categories = $pdo->query(
            "SELECT DISTINCT category FROM `{$tableName}` WHERE category IS NOT NULL AND category != '' ORDER BY category"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Construction WHERE ───
// Gestion du double champ statut/status
// On utilise une logique OR : si status='published' OU statut='publie'
$where  = [];
$params = [];

if ($filterStatus !== 'all') {
    if ($filterStatus === 'published') {
        $cond = [];
        if ($hasStatus)  { $cond[] = "a.status = ?";  $params[] = 'published'; }
        if ($hasStatut)  { $cond[] = "a.statut = ?";  $params[] = 'publie'; }
        if ($cond) $where[] = '(' . implode(' OR ', $cond) . ')';
    } elseif ($filterStatus === 'draft') {
        $cond = [];
        if ($hasStatus)  { $cond[] = "a.status = ?";  $params[] = 'draft'; }
        if ($hasStatut)  { $cond[] = "a.statut = ?";  $params[] = 'brouillon'; }
        if ($cond) $where[] = '(' . implode(' OR ', $cond) . ')';
    } elseif ($filterStatus === 'archived') {
        if ($hasStatus)  { $where[] = "a.status = ?"; $params[] = 'archived'; }
    }
}
if ($filterIndexed !== 'all' && $hasGoogleIndexed && in_array($filterIndexed, ['yes','no','pending','unknown'])) {
    $where[] = "a.google_indexed = ?"; $params[] = $filterIndexed;
} elseif ($filterIndexed === 'yes' && $hasIsIndexed && !$hasGoogleIndexed) {
    $where[] = "a.is_indexed = 1";
}
if ($filterCat !== 'all' && $hasCategory) {
    $where[] = "a.category = ?"; $params[] = $filterCat;
}
if ($searchQuery !== '') {
    $w  = "(a.`{$colTitle}` LIKE ?";  $params[] = "%{$searchQuery}%";
    $w .= " OR a.slug LIKE ?";        $params[] = "%{$searchQuery}%";
    if ($colKeyword) { $w .= " OR a.`{$colKeyword}` LIKE ?"; $params[] = "%{$searchQuery}%"; }
    $w .= ")";
    $where[] = $w;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Stats globales ───
$stats = [
    'total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0,
    'avg_seo' => 0, 'avg_semantic' => 0, 'indexed_count' => 0, 'featured_count' => 0,
];
if ($tableExists) {
    try {
        // Comptes statuts (gestion double champ)
        $total = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
        $stats['total'] = $total;

        // Publiés = status='published' OU statut='publie'
        $pubCond = [];
        if ($hasStatus) $pubCond[] = "status = 'published'";
        if ($hasStatut) $pubCond[] = "statut = 'publie'";
        if ($pubCond) {
            $stats['published'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE " . implode(' OR ', $pubCond))->fetchColumn();
        }

        // Brouillons = status='draft' OU statut='brouillon' (et PAS publie)
        $draftCond = [];
        if ($hasStatus) $draftCond[] = "status = 'draft'";
        if ($hasStatut) $draftCond[] = "statut = 'brouillon'";
        if ($draftCond) {
            $stats['draft'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE " . implode(' OR ', $draftCond))->fetchColumn();
        }

        // Archivés
        if ($hasStatus) {
            $stats['archived'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE status = 'archived'")->fetchColumn();
        }

        // SEO moyen
        if ($colSeoScore) {
            $stats['avg_seo'] = (int)$pdo->query("SELECT ROUND(AVG(NULLIF(`{$colSeoScore}`, 0)), 0) FROM `{$tableName}`")->fetchColumn();
        }
        // Sémantique moyen
        if ($colSemantic) {
            $stats['avg_semantic'] = (int)$pdo->query("SELECT ROUND(AVG(NULLIF(`{$colSemantic}`, 0)), 0) FROM `{$tableName}`")->fetchColumn();
        }
        // Indexés
        if ($hasGoogleIndexed) {
            $stats['indexed_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE google_indexed = 'yes'")->fetchColumn();
        } elseif ($hasIsIndexed) {
            $stats['indexed_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE is_indexed = 1")->fetchColumn();
        }
        // Featured
        if ($hasIsFeatured) {
            $stats['featured_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE is_featured = 1")->fetchColumn();
        }
    } catch (PDOException $e) {
        // silencieux
    }
}

// ─── Total filtré ───
$totalFiltered = 0;
$articles      = [];
$totalPages    = 1;

if ($tableExists) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` a {$whereSQL}");
        $stmtCount->execute($params);
        $totalFiltered = (int) $stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        // ── SELECT avec vrais noms de colonnes ──
        $selectParts = [
            "a.id",
            "a.`{$colTitle}` AS title",   // alias → toujours 'title' dans le PHP
            "a.slug",
            "a.created_at",
        ];
        if ($hasUpdatedAt)     $selectParts[] = "a.updated_at";
        if ($hasStatus)        $selectParts[] = "a.status";
        if ($hasStatut)        $selectParts[] = "a.statut";
        if ($colSeoScore)      $selectParts[] = "a.`{$colSeoScore}` AS seo_score";
        if ($colSemantic)      $selectParts[] = "a.`{$colSemantic}` AS semantic_score";
        if ($hasWordCount)     $selectParts[] = "a.word_count";
        if ($hasIsIndexed)     $selectParts[] = "a.is_indexed";
        if ($hasGoogleIndexed) $selectParts[] = "a.google_indexed";
        if ($hasCategory)      $selectParts[] = "a.category";
        if ($hasIsFeatured)    $selectParts[] = "a.is_featured";
        if ($colKeyword)       $selectParts[] = "a.`{$colKeyword}` AS main_keyword";

        if ($hasSeoScoresTable) {
            $selectParts[] = "ss.score_semantique AS ss_semantic";
        }

        $colsSQL  = implode(', ', $selectParts);
        $joinSQL  = $hasSeoScoresTable
            ? "LEFT JOIN seo_scores ss ON ss.context = 'article' AND ss.entity_id = a.id"
            : "";
        $orderSQL = "ORDER BY a.created_at DESC";

        $stmt = $pdo->prepare("SELECT {$colsSQL} FROM `{$tableName}` a {$joinSQL} {$whereSQL} {$orderSQL} LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log l'erreur pour debug
        error_log("[Articles Index] SQL Error: " . $e->getMessage());
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Normaliser le status pour l'affichage (fusionner statut + status) ───
// Retourne 'published', 'draft', ou 'archived'
function normalizeArticleStatus(array $a): string {
    // Priorité à status (EN) s'il est défini et pas 'draft' par défaut
    $s  = $a['status']  ?? '';
    $st = $a['statut']  ?? '';
    if ($s === 'published') return 'published';
    if ($s === 'archived')  return 'archived';
    if ($st === 'publie')   return 'published';
    if ($st === 'brouillon') return 'draft';
    return 'draft';
}

// ─── Flash ───
$flash = $_GET['msg'] ?? '';
?>

<!-- ═══════════════════════════════════════════════════════════ -->
<!--  MODULE ARTICLES — LISTING  v2.1                           -->
<!-- ═══════════════════════════════════════════════════════════ -->
<style>
/* Articles Module — Light Theme v2.1 */
.arm-wrap { font-family: var(--font); }

/* ═══ BANNER ═══ */
.arm-banner {
    background: var(--surface);
    border-radius: var(--radius-xl);
    padding: 26px 30px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}
.arm-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f59e0b, #ef4444, #8b5cf6);
    opacity: .75;
}
.arm-banner::after {
    content: '';
    position: absolute;
    top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(245,158,11,.05), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.arm-banner-left { position: relative; z-index: 1; }
.arm-banner-left h2 {
    font-family: var(--font-display);
    font-size: 1.35rem; font-weight: 700;
    color: var(--text); margin: 0 0 4px;
    display: flex; align-items: center; gap: 10px;
    letter-spacing: -.02em;
}
.arm-banner-left h2 i { font-size: 16px; color: #f59e0b; }
.arm-banner-left p { color: var(--text-2); font-size: 0.85rem; margin: 0; }

.arm-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.arm-stat {
    text-align: center; padding: 10px 16px;
    background: var(--surface-2); border-radius: var(--radius-lg);
    border: 1px solid var(--border); min-width: 72px;
    transition: all .2s var(--ease);
}
.arm-stat:hover { border-color: var(--border-h); box-shadow: var(--shadow-xs); }
.arm-stat .num {
    font-family: var(--font-display); font-size: 1.45rem;
    font-weight: 800; line-height: 1; color: var(--text); letter-spacing: -.03em;
}
.arm-stat .num.blue   { color: var(--accent); }
.arm-stat .num.green  { color: var(--green); }
.arm-stat .num.amber  { color: #f59e0b; }
.arm-stat .num.teal   { color: var(--teal, #0d9488); }
.arm-stat .num.violet { color: var(--violet, #7c3aed); }
.arm-stat .lbl {
    font-size: 0.58rem; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px;
}

/* ═══ TOOLBAR ═══ */
.arm-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 10px;
}
.arm-filters {
    display: flex; gap: 3px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 3px; flex-wrap: wrap;
}
.arm-fbtn {
    padding: 7px 15px; border: none; background: transparent;
    color: var(--text-2); font-size: 0.78rem; font-weight: 600;
    border-radius: 6px; cursor: pointer; transition: all .15s var(--ease);
    font-family: var(--font); display: flex; align-items: center; gap: 5px; text-decoration: none;
}
.arm-fbtn:hover { color: var(--text); background: var(--surface-2); }
.arm-fbtn.active { background: #f59e0b; color: #fff; box-shadow: 0 1px 4px rgba(245,158,11,.25); }
.arm-fbtn .badge {
    font-size: 0.68rem; padding: 1px 7px; border-radius: 10px;
    background: var(--surface-2); font-weight: 700; color: var(--text-3);
}
.arm-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* Sub-filters */
.arm-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.arm-subfilter { display: flex; align-items: center; gap: 5px; font-size: 0.75rem; color: var(--text-2); }
.arm-subfilter select {
    padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px;
    background: var(--surface); color: var(--text); font-size: 0.75rem;
    font-family: var(--font); cursor: pointer; transition: border-color .15s;
}
.arm-subfilter select:focus { outline: none; border-color: #f59e0b; }
.arm-subfilter i { font-size: 0.7rem; color: var(--text-3); }

.arm-toolbar-r { display: flex; align-items: center; gap: 10px; }
.arm-search { position: relative; }
.arm-search input {
    padding: 8px 12px 8px 34px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius);
    color: var(--text); font-size: 0.82rem; width: 220px;
    font-family: var(--font); transition: all .2s var(--ease);
}
.arm-search input:focus {
    outline: none; border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245,158,11,.1); width: 250px;
}
.arm-search input::placeholder { color: var(--text-3); }
.arm-search i {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%); color: var(--text-3); font-size: 0.75rem;
}

/* ═══ BUTTONS ═══ */
.arm-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: var(--radius);
    font-size: 0.82rem; font-weight: 600; cursor: pointer;
    border: none; transition: all .15s var(--ease);
    font-family: var(--font); text-decoration: none; line-height: 1.3;
}
.arm-btn-primary { background: #f59e0b; color: #fff; box-shadow: 0 1px 4px rgba(245,158,11,.22); }
.arm-btn-primary:hover { background: #d97706; transform: translateY(-1px); color: #fff; box-shadow: 0 3px 12px rgba(245,158,11,.28); }
.arm-btn-outline { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); }
.arm-btn-outline:hover { border-color: #f59e0b; color: #f59e0b; background: rgba(245,158,11,.06); }
.arm-btn-sm { padding: 5px 12px; font-size: 0.75rem; }

/* ═══ TABLE ═══ */
.arm-table-wrap {
    background: var(--surface); border-radius: var(--radius-lg);
    border: 1px solid var(--border); overflow: hidden;
}
.arm-table { width: 100%; border-collapse: collapse; }
.arm-table thead th {
    padding: 11px 14px; font-size: 0.65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; color: var(--text-3);
    background: var(--surface-2); border-bottom: 1px solid var(--border);
    text-align: left; white-space: nowrap;
}
.arm-table tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
.arm-table tbody tr:hover { background: rgba(245,158,11,.03); }
.arm-table tbody tr:last-child { border-bottom: none; }
.arm-table td { padding: 12px 14px; font-size: 0.83rem; color: var(--text); vertical-align: middle; }

.arm-article-title { font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 8px; line-height: 1.3; }
.arm-article-title a { color: var(--text); text-decoration: none; transition: color .15s; }
.arm-article-title a:hover { color: #f59e0b; }
.arm-slug { font-family: var(--mono); font-size: 0.73rem; color: var(--text-3); }

.arm-keyword {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: 20px;
    font-size: 0.7rem; font-weight: 600; color: var(--text-2);
    max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.arm-keyword i { font-size: 0.6rem; color: var(--text-3); flex-shrink: 0; }

.arm-featured {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 7px; background: #fef9c3; border: 1px solid #fde047;
    border-radius: 4px; font-size: 0.58rem; font-weight: 700; color: #a16207;
    text-transform: uppercase; letter-spacing: .04em;
}
.arm-category {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; background: rgba(79,70,229,.07); color: var(--accent);
    border-radius: 5px; font-size: 0.65rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .03em;
    max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.arm-status {
    padding: 3px 10px; border-radius: 12px; font-size: 0.63rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block;
}
.arm-status.published { background: var(--green-bg); color: var(--green); }
.arm-status.draft     { background: var(--amber-bg); color: #d97706; }
.arm-status.archived  { background: var(--surface-2); color: var(--text-3); }

.arm-seo { font-weight: 700; font-size: 0.83rem; font-family: var(--font-display); }
.arm-seo.good { color: var(--green); }
.arm-seo.ok   { color: #f59e0b; }
.arm-seo.bad  { color: var(--red); }
.arm-seo.none { color: var(--text-3); }

.arm-semantic { display: flex; align-items: center; gap: 6px; }
.arm-semantic-bar { width: 48px; height: 6px; background: var(--surface-2); border-radius: 3px; overflow: hidden; flex-shrink: 0; }
.arm-semantic-fill { height: 100%; border-radius: 3px; transition: width .3s; }
.arm-semantic-fill.good { background: var(--green); }
.arm-semantic-fill.ok   { background: #f59e0b; }
.arm-semantic-fill.bad  { background: var(--red); }
.arm-semantic-val { font-size: 0.75rem; font-weight: 700; font-family: var(--font-display); min-width: 28px; }

.arm-indexed {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 10px; font-size: 0.6rem;
    font-weight: 700; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap;
}
.arm-indexed.yes     { background: #ecfdf5; color: #059669; }
.arm-indexed.no      { background: var(--red-bg); color: var(--red); }
.arm-indexed.pending { background: #fff7ed; color: #ea580c; }
.arm-indexed.unknown { background: var(--surface-2); color: var(--text-3); }

.arm-words { font-size: 0.73rem; color: var(--text-3); font-variant-numeric: tabular-nums; white-space: nowrap; }
.arm-words.good { color: var(--green); font-weight: 600; }
.arm-words.ok   { color: #f59e0b; }
.arm-date { font-size: 0.73rem; color: var(--text-3); white-space: nowrap; }

.arm-actions { display: flex; gap: 3px; justify-content: flex-end; }
.arm-actions a, .arm-actions button {
    width: 30px; height: 30px; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3); background: transparent; border: 1px solid transparent;
    cursor: pointer; transition: all .12s var(--ease); text-decoration: none; font-size: 0.78rem;
}
.arm-actions a:hover, .arm-actions button:hover { color: #f59e0b; border-color: var(--border); background: rgba(245,158,11,.07); }
.arm-actions button.del:hover { color: var(--red); border-color: rgba(220,38,38,.2); background: var(--red-bg); }

.arm-bulk {
    display: none; align-items: center; gap: 12px; padding: 10px 16px;
    background: rgba(245,158,11,.06); border: 1px solid rgba(245,158,11,.15);
    border-radius: var(--radius); margin-bottom: 12px;
    font-size: 0.78rem; color: #d97706; font-weight: 600;
}
.arm-bulk.active { display: flex; }
.arm-bulk select {
    padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px;
    background: var(--surface); color: var(--text); font-size: 0.75rem; font-family: var(--font);
}
.arm-table input[type="checkbox"] { accent-color: #f59e0b; width: 14px; height: 14px; cursor: pointer; }

.arm-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-top: 1px solid var(--border);
    font-size: 0.78rem; color: var(--text-3);
}
.arm-pagination a {
    padding: 6px 12px; border: 1px solid var(--border); border-radius: var(--radius);
    color: var(--text-2); text-decoration: none; font-weight: 600;
    transition: all .15s var(--ease); font-size: 0.78rem;
}
.arm-pagination a:hover { border-color: #f59e0b; color: #f59e0b; background: rgba(245,158,11,.06); }
.arm-pagination a.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }

.arm-flash {
    padding: 12px 18px; border-radius: var(--radius); font-size: 0.85rem; font-weight: 600;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    animation: armFlashIn .3s var(--ease);
}
.arm-flash.success { background: var(--green-bg); color: var(--green); border: 1px solid rgba(5,150,105,.12); }
.arm-flash.error   { background: var(--red-bg); color: var(--red); border: 1px solid rgba(220,38,38,.12); }
@keyframes armFlashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

.arm-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.arm-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.arm-empty h3 { font-family: var(--font-display); color: var(--text-2); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
.arm-empty p { font-size: 0.85rem; }
.arm-empty a { color: #f59e0b; }

@media (max-width: 1100px) { .arm-table .col-semantic, .arm-table .col-indexed, .arm-table .col-words { display: none; } }
@media (max-width: 900px) {
    .arm-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .arm-toolbar { flex-direction: column; align-items: flex-start; }
    .arm-table-wrap { overflow-x: auto; }
}
</style>

<div class="arm-wrap">

<?php if ($flash === 'deleted'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article supprimé avec succès</div>
<?php elseif ($flash === 'created'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article mis à jour</div>
<?php elseif ($flash === 'error'): ?>
    <div class="arm-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:var(--red-bg);border:1px solid rgba(220,38,38,.12);border-radius:var(--radius-lg);padding:28px;text-align:center;color:var(--red)">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:6px">Table articles introuvable</h3>
    <p style="font-size:0.83rem;opacity:.75">Vérifiez que la table <code>articles</code> existe dans votre base de données.</p>
</div>
<?php else: ?>

<!-- Banner -->
<div class="arm-banner">
    <div class="arm-banner-left">
        <h2><i class="fas fa-pen-fancy"></i> Mon Blog</h2>
        <p>Articles, contenus SEO et stratégie de contenu pour votre site immobilier</p>
    </div>
    <div class="arm-stats">
        <div class="arm-stat"><div class="num blue"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="arm-stat"><div class="num green"><?= $stats['published'] ?></div><div class="lbl">Publiés</div></div>
        <div class="arm-stat"><div class="num amber"><?= $stats['draft'] ?></div><div class="lbl">Brouillons</div></div>
        <?php if ($stats['indexed_count'] > 0): ?>
        <div class="arm-stat"><div class="num teal"><?= $stats['indexed_count'] ?></div><div class="lbl">Indexés</div></div>
        <?php endif; ?>
        <?php if ($colSeoScore): ?>
        <div class="arm-stat" title="Score SEO moyen"><div class="num teal"><?= $stats['avg_seo'] ?><span style="font-size:.6em;opacity:.6">%</span></div><div class="lbl">SEO Moy.</div></div>
        <?php endif; ?>
        <?php if ($colSemantic): ?>
        <div class="arm-stat" title="Score sémantique moyen"><div class="num violet"><?= $stats['avg_semantic'] ?><span style="font-size:.6em;opacity:.6">%</span></div><div class="lbl">Sémantiqu.</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- Toolbar -->
<div class="arm-toolbar">
    <div class="arm-filters">
        <?php
        $filters = [
            'all'       => ['icon' => 'fa-layer-group', 'label' => 'Tous',       'count' => $stats['total']],
            'published' => ['icon' => 'fa-check-circle','label' => 'Publiés',    'count' => $stats['published']],
            'draft'     => ['icon' => 'fa-pencil-alt',  'label' => 'Brouillons', 'count' => $stats['draft']],
            'archived'  => ['icon' => 'fa-archive',     'label' => 'Archivés',   'count' => $stats['archived']],
        ];
        foreach ($filters as $key => $f):
            $active = ($filterStatus === $key) ? ' active' : '';
            $url = '?page=articles' . ($key !== 'all' ? '&status=' . $key : '');
            if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
            if ($filterIndexed !== 'all') $url .= '&indexed=' . $filterIndexed;
            if ($filterCat !== 'all') $url .= '&category=' . urlencode($filterCat);
        ?>
            <a href="<?= $url ?>" class="arm-fbtn<?= $active ?>">
                <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
                <span class="badge"><?= (int)$f['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="arm-toolbar-r">
        <form class="arm-search" method="GET">
            <input type="hidden" name="page" value="articles">
            <?php if ($filterStatus !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Titre, slug, mot-clé..." value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=articles&action=create" class="arm-btn arm-btn-primary"><i class="fas fa-plus"></i> Nouvel article</a>
    </div>
</div>

<!-- Sub-filters -->
<div class="arm-subfilters">
    <?php if ($hasGoogleIndexed): ?>
    <div class="arm-subfilter">
        <i class="fab fa-google"></i>
        <select onchange="ARM.filterBy('indexed', this.value)">
            <option value="all" <?= $filterIndexed==='all' ? 'selected':'' ?>>Toutes indexations</option>
            <option value="yes"     <?= $filterIndexed==='yes'     ? 'selected':'' ?>>✅ Indexé</option>
            <option value="no"      <?= $filterIndexed==='no'      ? 'selected':'' ?>>❌ Non indexé</option>
            <option value="pending" <?= $filterIndexed==='pending' ? 'selected':'' ?>>⏳ En attente</option>
            <option value="unknown" <?= $filterIndexed==='unknown' ? 'selected':'' ?>>❓ Inconnu</option>
        </select>
    </div>
    <?php endif; ?>
    <?php if ($hasCategory && !empty($categories)): ?>
    <div class="arm-subfilter">
        <i class="fas fa-tag"></i>
        <select onchange="ARM.filterBy('category', this.value)">
            <option value="all" <?= $filterCat==='all' ? 'selected':'' ?>>Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCat===$cat ? 'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>

<!-- Bulk actions -->
<div class="arm-bulk" id="armBulkBar">
    <input type="checkbox" id="armSelectAll" onchange="ARM.toggleAll(this.checked)">
    <span id="armBulkCount">0</span> sélectionné(s)
    <select id="armBulkAction">
        <option value="">— Action groupée —</option>
        <option value="publish">Publier</option>
        <option value="draft">Brouillon</option>
        <option value="archive">Archiver</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="arm-btn arm-btn-sm arm-btn-outline" onclick="ARM.bulkExecute()"><i class="fas fa-check"></i> Appliquer</button>
</div>

<!-- Table -->
<div class="arm-table-wrap">
    <?php if (empty($articles)): ?>
        <div class="arm-empty">
            <i class="fas fa-pen-fancy"></i>
            <h3>Aucun article trouvé</h3>
            <p>
                <?php if ($searchQuery): ?>
                    Aucun résultat pour « <?= htmlspecialchars($searchQuery) ?> ». <a href="?page=articles">Effacer</a>
                <?php else: ?>
                    Rédigez votre premier article de blog.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <table class="arm-table">
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" onchange="ARM.toggleAll(this.checked)"></th>
                    <th>Article</th>
                    <th>Mot-clé</th>
                    <th>Statut</th>
                    <th>SEO</th>
                    <th class="col-semantic">Sémantique</th>
                    <th class="col-words">Mots</th>
                    <th class="col-indexed">Google</th>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $a):
                    // ── Status normalisé (fusion statut + status) ──
                    $statusNorm = normalizeArticleStatus($a);

                    // ── SEO ──
                    $seo  = (int)($a['seo_score'] ?? 0);
                    $seoC = $seo >= 70 ? 'good' : ($seo >= 40 ? 'ok' : ($seo > 0 ? 'bad' : 'none'));

                    // ── Sémantique ──
                    $semantic = (int)($a['semantic_score'] ?? 0);
                    if ($semantic === 0 && isset($a['ss_semantic'])) $semantic = (int)$a['ss_semantic'];
                    $semC = $semantic >= 50 ? 'good' : ($semantic >= 30 ? 'ok' : ($semantic > 0 ? 'bad' : 'none'));

                    // ── Indexation ──
                    $indexed = $a['google_indexed'] ?? ($a['is_indexed'] ? 'yes' : 'unknown');
                    $idxLabels = [
                        'yes'     => ['icon'=>'fa-check-circle',    'label'=>'Indexé',     'cls'=>'yes'],
                        'no'      => ['icon'=>'fa-times-circle',    'label'=>'Non indexé', 'cls'=>'no'],
                        'pending' => ['icon'=>'fa-clock',           'label'=>'En attente', 'cls'=>'pending'],
                        'unknown' => ['icon'=>'fa-question-circle', 'label'=>'Inconnu',    'cls'=>'unknown'],
                    ];
                    $idxInfo = $idxLabels[$indexed] ?? $idxLabels['unknown'];

                    // ── Mots ──
                    $words  = (int)($a['word_count'] ?? 0);
                    $wordsC = $words >= 800 ? 'good' : ($words >= 400 ? 'ok' : '');

                    // ── Keyword, catégorie, featured ──
                    $keyword  = $a['main_keyword'] ?? '';
                    $category = $a['category'] ?? '';
                    $featured = !empty($a['is_featured']);

                    // ── Date ──
                    $date = !empty($a['created_at']) ? date('d/m/Y', strtotime($a['created_at'])) : '—';

                    // ── URLs ──
                    $editUrl = "?page=articles&action=edit&id={$a['id']}";
                    $viewUrl = "/blog/" . htmlspecialchars($a['slug'] ?? '');

                    // ── Labels status ──
                    $statusLabels = ['published'=>'Publié','draft'=>'Brouillon','archived'=>'Archivé'];
                ?>
                <tr data-id="<?= (int)$a['id'] ?>">
                    <td><input type="checkbox" class="arm-cb" value="<?= (int)$a['id'] ?>" onchange="ARM.updateBulk()"></td>
                    <td>
                        <div class="arm-article-title">
                            <a href="<?= htmlspecialchars($editUrl) ?>"><?= htmlspecialchars($a['title'] ?? 'Sans titre') ?></a>
                            <?php if ($featured): ?><span class="arm-featured"><i class="fas fa-star"></i> Top</span><?php endif; ?>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                            <span class="arm-slug">/blog/<?= htmlspecialchars($a['slug'] ?? '') ?></span>
                            <?php if ($category): ?>
                                <span class="arm-category"><i class="fas fa-tag"></i> <?= htmlspecialchars($category) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($keyword): ?>
                            <span class="arm-keyword"><i class="fas fa-key"></i><?= htmlspecialchars($keyword) ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-3);font-size:.75rem">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="arm-status <?= $statusNorm ?>"><?= $statusLabels[$statusNorm] ?? $statusNorm ?></span></td>
                    <td><span class="arm-seo <?= $seoC ?>"><?= $seo > 0 ? $seo.'%' : '—' ?></span></td>
                    <td class="col-semantic">
                        <?php if ($semantic > 0): ?>
                            <div class="arm-semantic">
                                <div class="arm-semantic-bar"><div class="arm-semantic-fill <?= $semC ?>" style="width:<?= min(100,$semantic) ?>%"></div></div>
                                <span class="arm-semantic-val arm-seo <?= $semC ?>"><?= $semantic ?>%</span>
                            </div>
                        <?php else: ?>
                            <span class="arm-seo none">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-words">
                        <?php if ($words > 0): ?>
                            <span class="arm-words <?= $wordsC ?>"><?= number_format($words,0,',',' ') ?> mots</span>
                        <?php else: ?>
                            <span class="arm-words">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="col-indexed">
                        <span class="arm-indexed <?= $idxInfo['cls'] ?>">
                            <i class="fas <?= $idxInfo['icon'] ?>"></i> <?= $idxInfo['label'] ?>
                        </span>
                    </td>
                    <td><span class="arm-date"><?= $date ?></span></td>
                    <td>
                        <div class="arm-actions">
                            <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                            <button onclick="ARM.duplicate(<?= (int)$a['id'] ?>)" title="Dupliquer"><i class="fas fa-copy"></i></button>
                            <button onclick="ARM.toggleStatus(<?= (int)$a['id'] ?>, '<?= $statusNorm ?>')"
                                    title="<?= $statusNorm==='published' ? 'Dépublier' : 'Publier' ?>">
                                <i class="fas <?= $statusNorm==='published' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                            </button>
                            <?php if (!empty($a['slug'])): ?>
                            <a href="<?= $viewUrl ?>" target="_blank" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                            <?php endif; ?>
                            <button class="del" onclick="ARM.deleteArticle(<?= (int)$a['id'] ?>, '<?= addslashes(htmlspecialchars($a['title'] ?? '')) ?>')" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="arm-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> articles</span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1;$i<=$totalPages;$i++):
                    $pUrl = '?page=articles&p='.$i;
                    if ($filterStatus!=='all') $pUrl .= '&status='.$filterStatus;
                    if ($filterIndexed!=='all') $pUrl .= '&indexed='.$filterIndexed;
                    if ($filterCat!=='all') $pUrl .= '&category='.urlencode($filterCat);
                    if ($searchQuery) $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                    <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php endif; ?>
</div>

<script>
const ARM = {
    apiUrl: '/admin/modules/articles/api/articles.php',
    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },
    toggleAll(checked) {
        document.querySelectorAll('.arm-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.arm-cb:checked');
        const bar = document.getElementById('armBulkBar');
        document.getElementById('armBulkCount').textContent = checked.length;
        bar.classList.toggle('active', checked.length > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('armBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.arm-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete' && !confirm(`Supprimer ${ids.length} article(s) ?`)) return;
        const fd = new FormData();
        fd.append('action', action === 'delete' ? 'bulk_delete' : 'bulk_status');
        if (action !== 'delete') fd.append('status', {publish:'published',draft:'draft',archive:'archived'}[action]);
        fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    },
    async deleteArticle(id, title) {
        if (!confirm(`Supprimer « ${title} » ?`)) return;
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id', id);
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        if (d.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) { row.style.cssText='opacity:0;transform:translateX(20px);transition:all .3s'; setTimeout(()=>row.remove(),300); }
        } else { alert(d.error || 'Erreur'); }
    },
    async toggleStatus(id, currentStatus) {
        const newStatus = currentStatus === 'published' ? 'draft' : 'published';
        const fd = new FormData();
        fd.append('action','toggle_status'); fd.append('id',id); fd.append('status',newStatus);
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    },
    async duplicate(id) {
        if (!confirm('Dupliquer cet article ?')) return;
        const fd = new FormData();
        fd.append('action','duplicate'); fd.append('id',id);
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    }
};
</script>