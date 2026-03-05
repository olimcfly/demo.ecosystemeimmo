<?php
/**
 * ============================================================
 * MODULE SEO DES PAGES
 * ============================================================
 * Fichier : /admin/modules/seo/pages.php
 * Route   : dashboard.php?page=seo-pages
 * 
 * Même design que articles/index.php (filter chips, table,
 * score circles, toggle index, toggle validation AJAX)
 * 
 * VERSION 4.0 — ÉCOSYSTÈME IMMO LOCAL+
 * ============================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!defined('DB_HOST')) {
    $cfgPaths = [__DIR__ . '/../../../config/config.php', $_SERVER['DOCUMENT_ROOT'] . '/config/config.php'];
    foreach ($cfgPaths as $p) { if (file_exists($p)) { require_once $p; break; } }
}
if (!defined('DB_HOST')) { echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ config.php introuvable</div>'; return; }

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $e) { echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Erreur BDD</div>'; return; }

$tableExists = false;
try { $pdo->query("SELECT 1 FROM pages LIMIT 1"); $tableExists = true; } catch (Exception $e) {}

if ($tableExists) {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM pages")->fetchAll(), 'Field');
    $needed = [
        'seo_score' => "ADD COLUMN seo_score INT DEFAULT 0",
        'semantic_score' => "ADD COLUMN semantic_score INT DEFAULT 0",
        'noindex' => "ADD COLUMN noindex TINYINT(1) DEFAULT 0",
        'is_indexed' => "ADD COLUMN is_indexed TINYINT(1) DEFAULT 1",
        'seo_validated' => "ADD COLUMN seo_validated TINYINT(1) DEFAULT 0",
        'seo_validated_at' => "ADD COLUMN seo_validated_at DATETIME NULL",
        'internal_links' => "ADD COLUMN internal_links INT DEFAULT 0",
        'external_links' => "ADD COLUMN external_links INT DEFAULT 0",
        'keywords_count' => "ADD COLUMN keywords_count INT DEFAULT 0",
        'serp_position' => "ADD COLUMN serp_position INT DEFAULT 0",
        'semantic_data' => "ADD COLUMN semantic_data JSON NULL",
        'semantic_analyzed_at' => "ADD COLUMN semantic_analyzed_at DATETIME NULL",
        'template' => "ADD COLUMN template VARCHAR(100) NULL",
    ];
    foreach ($needed as $col => $ddl) {
        if (!in_array($col, $cols)) { try { $pdo->exec("ALTER TABLE pages $ddl"); } catch (Exception $e) {} }
    }
    $cols = array_column($pdo->query("SHOW COLUMNS FROM pages")->fetchAll(), 'Field');
}

$titleCol = in_array('title', $cols ?? []) ? 'title' : (in_array('titre', $cols ?? []) ? 'titre' : 'title');

// ─── Detect AI ───
$aiAvailable = false; $aiProvider = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) { $aiAvailable = true; $aiProvider = 'Claude'; }
elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) { $aiAvailable = true; $aiProvider = 'OpenAI'; }

// ─── Detect websites table ───
$hasWebsites = false; $websites = [];
try { $pdo->query("SELECT 1 FROM websites LIMIT 1"); $hasWebsites = true; $websites = $pdo->query("SELECT id, name FROM websites ORDER BY name")->fetchAll(); } catch (Exception $e) {}

// ─── AJAX TOGGLES ───
if (isset($_GET['ajax_action'])) {
    header('Content-Type: application/json');
    $aid = intval($_GET['id'] ?? 0);

    if ($_GET['ajax_action'] === 'toggle-noindex' && $aid > 0) {
        try {
            $v = (int)$pdo->query("SELECT noindex FROM pages WHERE id=$aid")->fetchColumn();
            $n = $v ? 0 : 1;
            $pdo->prepare("UPDATE pages SET noindex=?, is_indexed=? WHERE id=?")->execute([$n, $n?0:1, $aid]);
            echo json_encode(['success'=>true,'noindex'=>$n]);
        } catch (Exception $e) { echo json_encode(['success'=>false]); }
        exit;
    }
    if ($_GET['ajax_action'] === 'toggle-validation' && $aid > 0) {
        try {
            $v = (int)$pdo->query("SELECT seo_validated FROM pages WHERE id=$aid")->fetchColumn();
            $n = $v ? 0 : 1; $at = $n ? date('Y-m-d H:i:s') : null;
            $pdo->prepare("UPDATE pages SET seo_validated=?, seo_validated_at=? WHERE id=?")->execute([$n, $at, $aid]);
            echo json_encode(['success'=>true,'validated'=>$n,'at'=>$at]);
        } catch (Exception $e) { echo json_encode(['success'=>false]); }
        exit;
    }
    echo json_encode(['success'=>false]); exit;
}

// ─── FILTERS ───
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$filterIdx = $_GET['idx'] ?? '';
$filterVal = $_GET['val'] ?? '';
$filterSite = $_GET['site'] ?? '';
$filterScore = $_GET['score'] ?? '';

$pages = []; $counts = ['all'=>0,'published'=>0,'draft'=>0,'indexed'=>0,'noindex'=>0,'validated'=>0,'not_validated'=>0];

if ($tableExists) {
    try {
        $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
        $counts['published'] = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
        $counts['draft'] = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE status='draft' OR status IS NULL")->fetchColumn();
        $counts['indexed'] = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE (noindex=0 OR noindex IS NULL)")->fetchColumn();
        $counts['noindex'] = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE noindex=1")->fetchColumn();
        $counts['validated'] = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE seo_validated=1")->fetchColumn();
        $counts['not_validated'] = $counts['all'] - $counts['validated'];

        // Stats for header
        $totalPages = $counts['all'];
        $analyzedPages = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE seo_score > 0")->fetchColumn();
        $avgScore = (int)$pdo->query("SELECT COALESCE(AVG(seo_score),0) FROM pages WHERE seo_score > 0")->fetchColumn();
        $excellentPages = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE seo_score >= 80")->fetchColumn();
        $needWorkPages = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE seo_score > 0 AND seo_score < 40")->fetchColumn();

        $sql = "SELECT * FROM pages WHERE 1=1"; $params = [];
        switch ($filter) {
            case 'published': $sql .= " AND status='published'"; break;
            case 'draft': $sql .= " AND (status='draft' OR status IS NULL)"; break;
        }
        if ($filterIdx === 'indexed') $sql .= " AND (noindex=0 OR noindex IS NULL)";
        if ($filterIdx === 'noindex') $sql .= " AND noindex=1";
        if ($filterVal === 'validated') $sql .= " AND seo_validated=1";
        if ($filterVal === 'not_validated') $sql .= " AND (seo_validated=0 OR seo_validated IS NULL)";
        if (!empty($filterSite)) { $sql .= " AND website_id=?"; $params[] = intval($filterSite); }
        if ($filterScore === 'excellent') $sql .= " AND seo_score >= 80";
        elseif ($filterScore === 'good') $sql .= " AND seo_score >= 60 AND seo_score < 80";
        elseif ($filterScore === 'warning') $sql .= " AND seo_score >= 1 AND seo_score < 60";
        elseif ($filterScore === 'not_analyzed') $sql .= " AND (seo_score = 0 OR seo_score IS NULL)";
        if (!empty($search)) { $sql .= " AND ($titleCol LIKE ? OR slug LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $sql .= " ORDER BY updated_at DESC, created_at DESC";
        $stmt = $pdo->prepare($sql); $stmt->execute($params); $pages = $stmt->fetchAll();
    } catch (Exception $e) {}
}

function spScoreClass($s) { if($s>=80) return 'excellent'; if($s>=60) return 'good'; if($s>=40) return 'warning'; return 'error'; }
?>

<style>
.mod-seo-pages{padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}

/* ─── Stats Header ─── */
.sp-stats{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap}
.sp-stat{flex:1;min-width:140px;background:#fff;border-radius:16px;padding:20px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:14px;transition:all .2s}
.sp-stat:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(0,0,0,.06)}
.sp-stat-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px}
.sp-stat-icon.blue{background:#dbeafe;color:#2563eb}.sp-stat-icon.green{background:#d1fae5;color:#059669}
.sp-stat-icon.purple{background:#ede9fe;color:#7c3aed}.sp-stat-icon.yellow{background:#fef3c7;color:#d97706}
.sp-stat-icon.red{background:#fee2e2;color:#dc2626}.sp-stat-icon.teal{background:#ccfbf1;color:#0d9488}
.sp-stat-icon.slate{background:#f1f5f9;color:#475569}.sp-stat-icon.ai{background:linear-gradient(135deg,#c084fc,#818cf8);color:#fff}
.sp-sv{font-size:24px;font-weight:800;color:#0f172a;line-height:1}
.sp-sl{font-size:12px;color:#64748b;margin-top:2px}

/* ─── Toolbar ─── */
.sp-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.sp-toolbar .sp-search{display:flex;align-items:center;background:#fff;border:2px solid #e2e8f0;border-radius:12px;padding:0 14px;transition:all .2s}
.sp-toolbar .sp-search:focus-within{border-color:#10b981;box-shadow:0 0 0 4px rgba(16,185,129,.08)}
.sp-toolbar .sp-search i{color:#94a3b8;font-size:14px}
.sp-toolbar .sp-search input{border:none;padding:10px;font-size:14px;width:160px;outline:none;background:transparent}
.sp-select{padding:10px 14px;border:2px solid #e2e8f0;border-radius:12px;font-size:13px;color:#475569;background:#fff;cursor:pointer;outline:none;transition:all .2s}
.sp-select:focus{border-color:#10b981}
.sp-btn-search{width:40px;height:40px;border-radius:12px;border:2px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s}
.sp-btn-search:hover{border-color:#10b981;color:#10b981}

/* ─── AI Banner ─── */
.sp-ai-banner{display:flex;align-items:center;gap:10px;padding:12px 20px;border-radius:12px;margin-bottom:20px;font-size:13px;font-weight:500}
.sp-ai-banner.active{background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #a7f3d0;color:#065f46}
.sp-ai-banner.inactive{background:#fef3c7;border:1px solid #fde68a;color:#92400e}

/* ─── Action Buttons Top ─── */
.sp-top-actions{display:flex;gap:12px;margin-bottom:20px;justify-content:flex-end}
.sp-btn-action{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;border:none;transition:all .25s}
.sp-btn-optimize{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;box-shadow:0 4px 15px rgba(139,92,246,.3)}
.sp-btn-optimize:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(139,92,246,.4);color:#fff}
.sp-btn-analyze{background:linear-gradient(135deg,#10b981,#059669);color:#fff;box-shadow:0 4px 15px rgba(16,185,129,.3)}
.sp-btn-analyze:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(16,185,129,.4);color:#fff}

/* ─── Table (même style que articles) ─── */
.sp-tw{background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.03)}
.sp-tw table{width:100%;border-collapse:collapse}
.sp-tw thead th{padding:16px 14px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;background:#fafbfc;border-bottom:2px solid #f1f5f9}
.sp-tw tbody td{padding:16px 14px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.sp-tw tbody tr{transition:background .15s}
.sp-tw tbody tr:hover{background:#fafbff}
.sp-tw tbody tr:last-child td{border-bottom:none}

/* Page Cell */
.sp-pc{display:flex;align-items:flex-start;gap:10px;max-width:320px}
.sp-pc-name{font-weight:600;color:#0f172a;font-size:14px;line-height:1.3}
.sp-pc-slug{font-size:12px;color:#94a3b8;margin-top:2px}

/* Site badge */
.sp-site{display:inline-block;padding:4px 10px;border-radius:8px;font-size:11px;font-weight:600;background:#eef2ff;color:#4f46e5;white-space:nowrap}

/* Score (même style) */
.sp-sw{display:flex;align-items:center;gap:6px;white-space:nowrap}
.sp-sc{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff}
.sp-sc.excellent{background:#10b981}.sp-sc.good{background:#3b82f6}.sp-sc.warning{background:#f59e0b}.sp-sc.error{background:#ef4444}
.sp-sm{font-size:12px;color:#94a3b8}
.sp-sb{width:60px;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.sp-sf{height:100%;border-radius:3px;transition:width .4s}
.sp-sf.excellent{background:#10b981}.sp-sf.good{background:#3b82f6}.sp-sf.warning{background:#f59e0b}.sp-sf.error{background:#ef4444}
.sp-no-score{font-size:13px;color:#94a3b8;display:flex;align-items:center;gap:4px}

/* Index Toggle */
.sp-idx{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .2s;white-space:nowrap}
.sp-idx.indexed{background:#dcfce7;color:#166534}
.sp-idx.noindex{background:#fee2e2;color:#991b1b}
.sp-idx:hover{opacity:.85;transform:scale(1.03)}

/* Problemes */
.sp-problems{font-size:12px;color:#64748b;max-width:200px}
.sp-prob-item{display:flex;align-items:center;gap:4px;margin-bottom:2px}
.sp-prob-item .dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.sp-prob-item .dot.red{background:#ef4444}.sp-prob-item .dot.yellow{background:#f59e0b}
.sp-prob-more{color:#6366f1;font-size:11px;cursor:pointer}

/* Validation */
.sp-val{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .2s;white-space:nowrap}
.sp-val.validated{background:#dcfce7;color:#166534}
.sp-val.not-validated{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
.sp-val:hover{opacity:.85;transform:scale(1.03)}

/* Analyzed date */
.sp-date{font-size:12px;color:#94a3b8;white-space:nowrap}

/* Actions */
.sp-actions{display:flex;gap:4px;flex-wrap:wrap}
.sp-act{width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;text-decoration:none;font-size:13px;transition:all .2s}
.sp-act:hover{transform:translateY(-1px);box-shadow:0 3px 8px rgba(0,0,0,.08)}
.sp-act.green:hover{background:#ecfdf5;border-color:#6ee7b7;color:#10b981}
.sp-act.blue:hover{background:#eef2ff;border-color:#818cf8;color:#6366f1}
.sp-act.purple:hover{background:#faf5ff;border-color:#c084fc;color:#a855f7}
.sp-act.cyan:hover{background:#f0f9ff;border-color:#7dd3fc;color:#0ea5e9}

.sp-empty{text-align:center;padding:80px 20px}
.sp-empty i{font-size:56px;color:#e2e8f0;margin-bottom:16px}

@media(max-width:768px){.sp-stats{flex-direction:column}.sp-toolbar{flex-direction:column;align-items:stretch}.sp-tw{overflow-x:auto}}
@keyframes spSlide{from{transform:translateX(80px);opacity:0}to{transform:translateX(0);opacity:1}}
</style>

<div class="mod-seo-pages">
<?php if (!$tableExists): ?>
<div style="background:linear-gradient(135deg,#10b981,#059669);border-radius:20px;padding:3rem;color:#fff;text-align:center"><div style="font-size:4rem;margin-bottom:1rem">📄</div><h3 style="font-size:1.5rem">Table <code>pages</code> non trouvée</h3></div>
<?php else: ?>

<!-- ═══ TOP ACTIONS ═══ -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div>
        <h2 style="font-size:22px;font-weight:700;color:#0f172a;margin:0;display:flex;align-items:center;gap:10px"><i class="fas fa-chart-line" style="color:#10b981"></i> SEO des Pages</h2>
        <p style="font-size:14px;color:#64748b;margin:4px 0 0">Analysez, indexez et validez le SEO de vos pages</p>
    </div>
    <div class="sp-top-actions" style="margin-bottom:0">
        <a href="?page=seo-pages&action=optimize-all" class="sp-btn-action sp-btn-optimize"><i class="fas fa-magic"></i> Optimiser tout (IA)</a>
        <a href="?page=seo-pages&action=analyze-all" class="sp-btn-action sp-btn-analyze"><i class="fas fa-brain"></i> Analyser tout</a>
    </div>
</div>

<!-- ═══ STATS ═══ -->
<div class="sp-stats">
    <div class="sp-stat"><div class="sp-stat-icon blue"><i class="fas fa-file-alt"></i></div><div><div class="sp-sv"><?=$totalPages??0?></div><div class="sp-sl">Pages totales</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon purple"><i class="fas fa-search"></i></div><div><div class="sp-sv"><?=$analyzedPages??0?></div><div class="sp-sl">Analysées</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon <?=($avgScore??0)>=60?'green':'yellow'?>"><i class="fas fa-chart-line"></i></div><div><div class="sp-sv"><?=$avgScore??0?>%</div><div class="sp-sl">Score moyen</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon green"><i class="fas fa-trophy"></i></div><div><div class="sp-sv"><?=$excellentPages??0?></div><div class="sp-sl">Excellentes</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon red"><i class="fas fa-exclamation-triangle"></i></div><div><div class="sp-sv"><?=$needWorkPages??0?></div><div class="sp-sl">À optimiser</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon teal"><i class="fas fa-sitemap"></i></div><div><div class="sp-sv"><?=$counts['indexed']?>/<?=$counts['all']?></div><div class="sp-sl">Indexées</div></div></div>
    <div class="sp-stat"><div class="sp-stat-icon slate"><i class="fas fa-check-double"></i></div><div><div class="sp-sv"><?=$counts['validated']?>/<?=$counts['all']?></div><div class="sp-sl">Validées</div></div></div>
    <?php if ($aiAvailable): ?><div class="sp-stat"><div class="sp-stat-icon ai"><i class="fas fa-robot"></i></div><div><div class="sp-sv"><?=$aiProvider?></div><div class="sp-sl">IA Active</div></div></div><?php endif; ?>
</div>

<!-- ═══ TOOLBAR ═══ -->
<form method="GET" class="sp-toolbar">
    <input type="hidden" name="page" value="seo-pages">
    <div class="sp-search"><i class="fas fa-search"></i><input type="text" name="search" placeholder="Rechercher..." value="<?=htmlspecialchars($search)?>"></div>
    <?php if ($hasWebsites && count($websites) > 0): ?>
    <select name="site" class="sp-select" onchange="this.form.submit()">
        <option value="">Tous les sites</option>
        <?php foreach($websites as $w): ?><option value="<?=$w['id']?>" <?=$filterSite==$w['id']?'selected':''?>><?=htmlspecialchars($w['name'])?></option><?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="score" class="sp-select" onchange="this.form.submit()">
        <option value="">Tous les scores</option>
        <option value="excellent" <?=$filterScore==='excellent'?'selected':''?>>🏆 Excellent (80%+)</option>
        <option value="good" <?=$filterScore==='good'?'selected':''?>>✅ Bon (60-79%)</option>
        <option value="warning" <?=$filterScore==='warning'?'selected':''?>>⚠️ À améliorer</option>
        <option value="not_analyzed" <?=$filterScore==='not_analyzed'?'selected':''?>>🔍 Non analysé</option>
    </select>
    <select name="idx" class="sp-select" onchange="this.form.submit()">
        <option value="">Indexation: Tous</option>
        <option value="indexed" <?=$filterIdx==='indexed'?'selected':''?>>✅ Indexées</option>
        <option value="noindex" <?=$filterIdx==='noindex'?'selected':''?>>🚫 NoIndex</option>
    </select>
    <select name="val" class="sp-select" onchange="this.form.submit()">
        <option value="">Validation: Tous</option>
        <option value="validated" <?=$filterVal==='validated'?'selected':''?>>✅ Validées</option>
        <option value="not_validated" <?=$filterVal==='not_validated'?'selected':''?>>⏳ Non validées</option>
    </select>
    <button type="submit" class="sp-btn-search"><i class="fas fa-search"></i></button>
</form>

<!-- ═══ AI BANNER ═══ -->
<?php if ($aiAvailable): ?>
<div class="sp-ai-banner active"><i class="fas fa-magic"></i> <strong><?=$aiProvider?> activé !</strong> — Cliquez <i class="fas fa-robot"></i> pour optimiser. Utilisez les toggles pour gérer l'indexation et la validation directement.</div>
<?php else: ?>
<div class="sp-ai-banner inactive"><i class="fas fa-exclamation-triangle"></i> <strong>IA non configurée.</strong> Ajoutez <code>ANTHROPIC_API_KEY</code> ou <code>OPENAI_API_KEY</code> dans config.php.</div>
<?php endif; ?>

<!-- ═══ TABLE ═══ -->
<div class="sp-tw">
<?php if (empty($pages)): ?>
    <div class="sp-empty"><i class="fas fa-file-alt"></i><h3 style="font-size:18px;color:#1e293b">Aucune page</h3><p style="color:#94a3b8">Créez des pages dans le module CMS</p></div>
<?php else: ?>
    <table>
        <thead><tr>
            <th>PAGE</th>
            <th>SITE</th>
            <th>SCORE</th>
            <th>INDEXATION</th>
            <th>PROBLÈMES</th>
            <th>VALIDATION</th>
            <th>ANALYSÉ</th>
            <th>ACTIONS</th>
        </tr></thead>
        <tbody>
        <?php foreach ($pages as $p):
            $title = htmlspecialchars($p[$titleCol] ?? 'Sans titre');
            $slug = $p['slug'] ?? '';
            $seo = intval($p['seo_score'] ?? 0);
            $noindex = intval($p['noindex'] ?? 0);
            $validated = intval($p['seo_validated'] ?? 0);
            $validAt = $p['seo_validated_at'] ?? '';
            $semAt = $p['semantic_analyzed_at'] ?? '';
            $sc = spScoreClass($seo);
            $websiteName = '';
            if ($hasWebsites && !empty($p['website_id'])) {
                foreach ($websites as $w) { if ($w['id'] == $p['website_id']) { $websiteName = $w['name']; break; } }
            }
            // Detect problems
            $problems = [];
            $metaTitle = $p['meta_title'] ?? '';
            $metaDesc = $p['meta_description'] ?? '';
            if (!empty($metaTitle) && (strlen($metaTitle) < 30 || strlen($metaTitle) > 65)) $problems[] = ['type' => strlen($metaTitle)<30?'red':'yellow', 'text' => 'Titre: longueur non optimale'];
            if (!empty($metaDesc) && (strlen($metaDesc) < 120 || strlen($metaDesc) > 160)) $problems[] = ['type' => 'yellow', 'text' => 'Meta title: longueur non optimale'];
            if (empty($metaTitle)) $problems[] = ['type' => 'red', 'text' => 'Meta titre manquant'];
            if (empty($metaDesc)) $problems[] = ['type' => 'yellow', 'text' => 'Meta description manquante'];
        ?>
        <tr>
            <td><div class="sp-pc"><div><div class="sp-pc-name"><?=$title?></div><div class="sp-pc-slug">/<?=htmlspecialchars($slug)?></div></div></div></td>
            <td><?php if($websiteName):?><span class="sp-site"><?=htmlspecialchars($websiteName)?></span><?php else:?>—<?php endif;?></td>
            <td><?php if($seo > 0):?><div class="sp-sw"><span class="sp-sc <?=$sc?>"><?=$seo?>%</span><div class="sp-sb"><div class="sp-sf <?=$sc?>" style="width:<?=$seo?>%"></div></div></div><?php else:?><span class="sp-no-score">? —</span><?php endif;?></td>
            <td><button class="sp-idx <?=$noindex?'noindex':'indexed'?>" onclick="toggleNoindex(<?=$p['id']?>,this)"><i class="fas fa-<?=$noindex?'times-circle':'check-circle'?>"></i> <?=$noindex?'NoIndex':'Index'?></button></td>
            <td>
                <div class="sp-problems">
                    <?php $shown=0; foreach($problems as $pr): if($shown>=2) break; ?>
                    <div class="sp-prob-item"><span class="dot <?=$pr['type']?>"></span> <?=htmlspecialchars($pr['text'])?></div>
                    <?php $shown++; endforeach; ?>
                    <?php if(count($problems)>2):?><span class="sp-prob-more">+<?=count($problems)-2?> autres</span><?php endif;?>
                    <?php if(empty($problems)):?>—<?php endif;?>
                </div>
            </td>
            <td><button class="sp-val <?=$validated?'validated':'not-validated'?>" onclick="toggleVal(<?=$p['id']?>,this)"><i class="fas fa-<?=$validated?'check-circle':'circle'?>"></i> <?=$validated?'Validé':'Valider'?></button></td>
            <td><span class="sp-date"><?=$semAt?date('d/m H:i',strtotime($semAt)):'—'?></span></td>
            <td><div class="sp-actions">
                <a href="?page=seo-semantic&analyze=page&id=<?=$p['id']?>" class="sp-act green" title="Analyser SEO"><i class="fas fa-search"></i></a>
                <a href="?page=pages&action=edit&id=<?=$p['id']?>" class="sp-act blue" title="Éditer"><i class="fas fa-pen"></i></a>
                <a href="?page=seo-pages&action=optimize&id=<?=$p['id']?>" class="sp-act purple" title="Optimiser IA"><i class="fas fa-robot"></i></a>
                <?php if($slug):?><a href="/<?=htmlspecialchars($slug)?>" target="_blank" class="sp-act cyan" title="Voir"><i class="fas fa-external-link-alt"></i></a><?php endif;?>
            </div></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>
<?php endif; ?>
</div>

<script>
async function toggleNoindex(id,btn){btn.disabled=true;btn.style.opacity='.5';try{const r=await fetch(`?page=seo-pages&ajax_action=toggle-noindex&id=${id}`);const d=await r.json();if(d.success){if(d.noindex){btn.className='sp-idx noindex';btn.innerHTML='<i class="fas fa-times-circle"></i> NoIndex'}else{btn.className='sp-idx indexed';btn.innerHTML='<i class="fas fa-check-circle"></i> Index'}spToast(d.noindex?'Page passée en NoIndex':'Page indexée',d.noindex?'warning':'success')}}catch(e){spToast('Erreur réseau','error')}btn.disabled=false;btn.style.opacity='1'}

async function toggleVal(id,btn){btn.disabled=true;btn.style.opacity='.5';try{const r=await fetch(`?page=seo-pages&ajax_action=toggle-validation&id=${id}`);const d=await r.json();if(d.success){if(d.validated){btn.className='sp-val validated';btn.innerHTML='<i class="fas fa-check-circle"></i> Validé'}else{btn.className='sp-val not-validated';btn.innerHTML='<i class="fas fa-circle"></i> Valider'}spToast(d.validated?'Page validée SEO':'Validation retirée',d.validated?'success':'info')}}catch(e){spToast('Erreur réseau','error')}btn.disabled=false;btn.style.opacity='1'}

function spToast(m,t){const bg=t==='success'?'#10b981':t==='warning'?'#f59e0b':t==='info'?'#3b82f6':'#ef4444';const d=document.createElement('div');d.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:12px;color:#fff;font-weight:600;z-index:9999;box-shadow:0 4px 15px rgba(0,0,0,.15);background:${bg};font-size:14px;animation:spSlide .3s ease`;d.innerHTML=`<i class="fas fa-check-circle" style="margin-right:8px"></i>${m}`;document.body.appendChild(d);setTimeout(()=>{d.style.opacity='0';d.style.transform='translateX(80px)';setTimeout(()=>d.remove(),300)},3000)}

console.log('SEO Pages v4.0 — <?=count($pages)?> pages');
</script>