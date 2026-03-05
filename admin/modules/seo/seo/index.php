<?php
/**
 * ========================================
 * MODULE SEO DES PAGES - AVEC IA
 * ========================================
 * 
 * Fichier: /admin/modules/seo/index.php
 * VERSION 2.2 - Toggle Indexation + Validation inline
 * 
 * NOUVEAUTÉS:
 * - Toggle Indexer/NoIndex cliquable dans la liste
 * - Bouton Valider SEO cliquable dans la liste
 * - Colonnes noindex + seo_validated
 * 
 * ========================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ========================================
// CONNEXION BASE DE DONNÉES
// ========================================

$configPath = __DIR__ . '/../../../config/config.php';
if (!file_exists($configPath)) {
    $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
}

if (file_exists($configPath)) {
    require_once $configPath;
} else {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Fichier de configuration non trouvé</div>';
    return;
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">❌ Erreur BDD: ' . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

// ========================================
// VÉRIFICATION & MIGRATION AUTO COLONNES SEO
// ========================================

$existingColumns = $pdo->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);

$seoColumns = [
    'seo_score' => "ALTER TABLE pages ADD COLUMN `seo_score` INT DEFAULT 0",
    'seo_title' => "ALTER TABLE pages ADD COLUMN `seo_title` VARCHAR(160)",
    'seo_description' => "ALTER TABLE pages ADD COLUMN `seo_description` VARCHAR(320)",
    'seo_keywords' => "ALTER TABLE pages ADD COLUMN `seo_keywords` VARCHAR(255)",
    'seo_analyzed_at' => "ALTER TABLE pages ADD COLUMN `seo_analyzed_at` DATETIME DEFAULT NULL",
    'seo_issues' => "ALTER TABLE pages ADD COLUMN `seo_issues` TEXT",
    // NOUVEAU v2.2
    'noindex' => "ALTER TABLE pages ADD COLUMN `noindex` TINYINT(1) NOT NULL DEFAULT 0",
    'seo_validated' => "ALTER TABLE pages ADD COLUMN `seo_validated` TINYINT(1) NOT NULL DEFAULT 0",
    'seo_validated_at' => "ALTER TABLE pages ADD COLUMN `seo_validated_at` DATETIME DEFAULT NULL"
];

foreach ($seoColumns as $col => $sql) {
    if (!in_array($col, $existingColumns)) {
        try { $pdo->exec($sql); } catch (Exception $e) {}
    }
}

// Refresh
$existingColumns = $pdo->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);

function hasColumn($col, $existingColumns) {
    return in_array($col, $existingColumns);
}

$hasNoindex = hasColumn('noindex', $existingColumns);
$hasValidated = hasColumn('seo_validated', $existingColumns);
$hasValidatedAt = hasColumn('seo_validated_at', $existingColumns);

// ========================================
// VÉRIFIER DISPONIBILITÉ IA
// ========================================

$aiAvailable = defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY);
$aiAvailable = $aiAvailable || (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY));
$aiProvider = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) {
    $aiProvider = 'Claude';
} elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY)) {
    $aiProvider = 'OpenAI';
}

// ========================================
// RÉCUPÉRATION DES DONNÉES
// ========================================

$websitesTableExists = $pdo->query("SHOW TABLES LIKE 'websites'")->fetch();
$websites = [];
if ($websitesTableExists) {
    $websites = $pdo->query("SELECT id, name, primary_color FROM websites ORDER BY name")->fetchAll();
}

$websiteFilter = isset($_GET['website_id']) ? (int)$_GET['website_id'] : null;
$filterScore = $_GET['filter_score'] ?? '';
$filterIndex = $_GET['filter_index'] ?? '';
$filterValidated = $_GET['filter_validated'] ?? '';
$search = $_GET['search'] ?? '';

// Construire la requête
$selectCols = ['p.id', 'p.title', 'p.slug', 'p.status'];
if (hasColumn('website_id', $existingColumns)) $selectCols[] = 'p.website_id';
if (hasColumn('seo_score', $existingColumns)) $selectCols[] = 'p.seo_score';
if (hasColumn('seo_title', $existingColumns)) $selectCols[] = 'p.seo_title';
if (hasColumn('seo_description', $existingColumns)) $selectCols[] = 'p.seo_description';
if (hasColumn('seo_keywords', $existingColumns)) $selectCols[] = 'p.seo_keywords';
if (hasColumn('seo_analyzed_at', $existingColumns)) $selectCols[] = 'p.seo_analyzed_at';
if (hasColumn('seo_issues', $existingColumns)) $selectCols[] = 'p.seo_issues';
if ($hasNoindex) $selectCols[] = 'p.noindex';
if ($hasValidated) $selectCols[] = 'p.seo_validated';
if ($hasValidatedAt) $selectCols[] = 'p.seo_validated_at';

if ($websitesTableExists && hasColumn('website_id', $existingColumns)) {
    $selectCols[] = 'w.name as website_name';
    $selectCols[] = 'w.primary_color';
}

$sql = "SELECT " . implode(', ', $selectCols) . " FROM pages p";
if ($websitesTableExists && hasColumn('website_id', $existingColumns)) {
    $sql .= " LEFT JOIN websites w ON p.website_id = w.id";
}

$sql .= " WHERE 1=1";
$params = [];

if ($websiteFilter && hasColumn('website_id', $existingColumns)) {
    $sql .= " AND p.website_id = ?";
    $params[] = $websiteFilter;
}

if ($filterScore && hasColumn('seo_score', $existingColumns)) {
    switch ($filterScore) {
        case 'excellent': $sql .= " AND p.seo_score >= 80"; break;
        case 'good': $sql .= " AND p.seo_score >= 60 AND p.seo_score < 80"; break;
        case 'warning': $sql .= " AND p.seo_score >= 40 AND p.seo_score < 60"; break;
        case 'error': $sql .= " AND p.seo_score < 40"; break;
        case 'not_analyzed': $sql .= " AND (p.seo_score = 0 OR p.seo_score IS NULL)"; break;
    }
}

if ($filterIndex !== '' && $hasNoindex) {
    $sql .= " AND p.noindex = ?";
    $params[] = (int)$filterIndex;
}

if ($filterValidated !== '' && $hasValidated) {
    $sql .= " AND p.seo_validated = ?";
    $params[] = (int)$filterValidated;
}

if ($search) {
    $sql .= " AND (p.title LIKE ? OR p.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= hasColumn('seo_score', $existingColumns) ? " ORDER BY p.seo_score ASC, p.title ASC" : " ORDER BY p.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll();

// Stats
$totalPages = count($pages);
$analyzedPages = 0;
$avgScore = 0;
$excellentPages = 0;
$needWorkPages = 0;
$indexedPages = 0;
$noindexPages = 0;
$validatedPages = 0;

foreach ($pages as $p) {
    $score = $p['seo_score'] ?? 0;
    if ($score > 0) { $analyzedPages++; $avgScore += $score; }
    if ($score >= 80) $excellentPages++;
    if ($score > 0 && $score < 60) $needWorkPages++;
    if ($hasNoindex) {
        if (($p['noindex'] ?? 0) == 0) $indexedPages++;
        else $noindexPages++;
    }
    if ($hasValidated && ($p['seo_validated'] ?? 0) == 1) $validatedPages++;
}
$avgScore = $analyzedPages > 0 ? round($avgScore / $analyzedPages) : 0;

$apiUrl = 'modules/seo/api.php';
?>

<style>
.seo-module {
    --primary: #6366f1;
    --secondary: #8b5cf6;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --light: #f8fafc;
    --border: #e2e8f0;
    --text: #1e293b;
    --text-sec: #64748b;
    --ai-gradient: linear-gradient(135deg, #8b5cf6, #ec4899);
}

.seo-module .header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.seo-module .header-bar h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}

.seo-module .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}

.seo-module .stat-card {
    background: white;
    border-radius: 12px;
    padding: 14px;
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 12px;
}

.seo-module .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.seo-module .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.seo-module .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
.seo-module .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
.seo-module .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
.seo-module .stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.seo-module .stat-icon.ai { background: var(--ai-gradient); }
.seo-module .stat-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.seo-module .stat-icon.slate { background: linear-gradient(135deg, #64748b, #475569); }

.seo-module .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--text); }
.seo-module .stat-label { font-size: 0.75rem; color: var(--text-sec); }

.seo-module .toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 24px;
    flex-wrap: wrap;
    align-items: center;
}

.seo-module .search-box {
    position: relative;
    flex: 1;
    max-width: 250px;
    min-width: 160px;
}

.seo-module .search-box input {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.seo-module .search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-sec);
}

.seo-module .filter-select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 13px;
    background: white;
}

.seo-module .btn {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.seo-module .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
}

.seo-module .btn-ai {
    background: var(--ai-gradient);
    color: white;
}

.seo-module .btn-ai:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(139, 92, 246, 0.4);
}

.seo-module .btn-secondary {
    background: white;
    border: 1px solid var(--border);
    color: var(--text);
}

.seo-module .btn-success {
    background: var(--success);
    color: white;
}

.seo-module .alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
}

.seo-module .alert-info { background: #dbeafe; color: #1e40af; }
.seo-module .alert-warning { background: #fef3c7; color: #92400e; }
.seo-module .alert-ai {
    background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(236,72,153,0.1));
    border: 1px solid rgba(139,92,246,0.3);
    color: #7c3aed;
}

.seo-module .table-card {
    background: white;
    border-radius: 12px;
    border: 1px solid var(--border);
    overflow-x: auto;
}

.seo-module table { width: 100%; border-collapse: collapse; }

.seo-module th {
    padding: 12px 12px;
    text-align: left;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-sec);
    background: var(--light);
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}

.seo-module td {
    padding: 12px 12px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.seo-module tr:hover { background: #fafbfc; }

.seo-module .page-title-cell { display: flex; flex-direction: column; gap: 2px; }
.seo-module .page-title-cell .title { color: var(--text); font-weight: 600; font-size: 13px; }
.seo-module .page-title-cell .slug { color: var(--text-sec); font-size: 11px; }

.seo-module .score-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
}

.seo-module .score-badge.excellent { background: #d1fae5; color: #059669; }
.seo-module .score-badge.good { background: #dcfce7; color: #16a34a; }
.seo-module .score-badge.warning { background: #fef3c7; color: #d97706; }
.seo-module .score-badge.error { background: #fee2e2; color: #dc2626; }
.seo-module .score-badge.not-analyzed { background: #f1f5f9; color: #64748b; }

.seo-module .seo-progress {
    width: 60px;
    height: 5px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-left: 4px;
}

.seo-module .seo-progress-bar { height: 100%; border-radius: 3px; }
.seo-module .seo-progress-bar.excellent { background: linear-gradient(90deg, #10b981, #059669); }
.seo-module .seo-progress-bar.good { background: linear-gradient(90deg, #22c55e, #16a34a); }
.seo-module .seo-progress-bar.warning { background: linear-gradient(90deg, #f59e0b, #d97706); }
.seo-module .seo-progress-bar.error { background: linear-gradient(90deg, #ef4444, #dc2626); }

.seo-module .issues-list { font-size: 11px; color: var(--text-sec); max-width: 180px; }
.seo-module .issues-list .issue { display: flex; align-items: center; gap: 4px; margin-bottom: 2px; }
.seo-module .issues-list .issue i { color: var(--warning); font-size: 9px; }

.seo-module .badge-website {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
}

.seo-module .actions-cell { display: flex; gap: 4px; flex-wrap: wrap; }

.seo-module .btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 12px;
}

.seo-module .btn-icon:hover { transform: scale(1.1); }

.seo-module .btn-analyze { background: #ede9fe; color: #7c3aed; }
.seo-module .btn-ai-icon { background: var(--ai-gradient); color: white; }
.seo-module .btn-details { background: #dbeafe; color: #2563eb; }
.seo-module .btn-edit { background: #fef3c7; color: #d97706; }
.seo-module .btn-view { background: #d1fae5; color: #059669; }

/* ========================================
   NOUVEAU v2.2 : Toggle Indexation
   ======================================== */
.seo-module .index-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}

.seo-module .index-toggle input { display: none; }

.seo-module .index-toggle .toggle-track {
    width: 44px;
    height: 24px;
    background: #ef4444;
    border-radius: 12px;
    position: relative;
    transition: background 0.3s;
}

.seo-module .index-toggle input:checked + .toggle-track {
    background: #10b981;
}

.seo-module .index-toggle .toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.seo-module .index-toggle input:checked + .toggle-track .toggle-thumb {
    transform: translateX(20px);
}

.seo-module .index-toggle .toggle-label {
    margin-left: 8px;
    font-size: 11px;
    font-weight: 600;
    min-width: 50px;
}

.seo-module .index-toggle .toggle-label.indexed { color: #059669; }
.seo-module .index-toggle .toggle-label.noindex { color: #dc2626; }

.seo-module .index-toggle.saving .toggle-track {
    opacity: 0.5;
}

/* ========================================
   NOUVEAU v2.2 : Bouton Validation SEO
   ======================================== */
.seo-module .validate-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 8px;
    border: 2px solid;
    cursor: pointer;
    font-size: 11px;
    font-weight: 600;
    transition: all 0.2s;
    background: white;
}

.seo-module .validate-btn.not-validated {
    border-color: #e2e8f0;
    color: #94a3b8;
}

.seo-module .validate-btn.not-validated:hover {
    border-color: #10b981;
    color: #10b981;
    background: #f0fdf4;
}

.seo-module .validate-btn.validated {
    border-color: #10b981;
    color: #059669;
    background: #d1fae5;
}

.seo-module .validate-btn.validated:hover {
    border-color: #f59e0b;
    color: #d97706;
    background: #fefce8;
}

.seo-module .validate-btn.saving {
    opacity: 0.5;
    pointer-events: none;
}

.seo-module .validate-btn .validate-date {
    font-size: 9px;
    font-weight: 400;
    opacity: 0.7;
    display: block;
}

/* Modal */
.seo-module .modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.seo-module .modal-overlay.active { display: flex; }

.seo-module .modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 85vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.seo-module .modal-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.seo-module .modal-header h3 { margin: 0; font-size: 1.1rem; color: var(--text); }

.seo-module .modal-close {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--light);
    border-radius: 8px;
    cursor: pointer;
    color: var(--text-sec);
}

.seo-module .modal-body { padding: 20px; overflow-y: auto; }
.seo-module .modal-footer {
    padding: 16px 20px;
    background: var(--light);
    display: flex;
    gap: 12px;
    justify-content: space-between;
    flex-wrap: wrap;
}

/* AI Preview */
.seo-module .ai-preview-item {
    background: var(--light);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 14px;
    border-left: 4px solid var(--primary);
}

.seo-module .ai-preview-item.improved { border-left-color: var(--success); }

.seo-module .ai-preview-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-sec);
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.seo-module .ai-preview-label .char-count {
    font-weight: 400;
    background: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
}

.seo-module .ai-preview-old {
    font-size: 12px;
    color: var(--danger);
    text-decoration: line-through;
    margin-bottom: 6px;
    opacity: 0.7;
}

.seo-module .ai-preview-new {
    font-size: 14px;
    color: var(--text);
    font-weight: 500;
}

.seo-module .ai-suggestions {
    background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(236,72,153,0.1));
    border-radius: 10px;
    padding: 14px;
    margin-top: 16px;
}

.seo-module .ai-suggestions h4 {
    font-size: 13px;
    color: #7c3aed;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.seo-module .ai-suggestions ul {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: var(--text);
}

.seo-module .ai-suggestions li { margin-bottom: 6px; }

/* Score Summary */
.seo-module .score-summary {
    text-align: center;
    padding: 20px;
    border-radius: 12px;
    color: white;
    margin-bottom: 20px;
}

.seo-module .score-summary.excellent { background: linear-gradient(135deg, #10b981, #059669); }
.seo-module .score-summary.good { background: linear-gradient(135deg, #22c55e, #16a34a); }
.seo-module .score-summary.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.seo-module .score-summary.error { background: linear-gradient(135deg, #ef4444, #dc2626); }

.seo-module .score-summary .big-score { font-size: 2.5rem; font-weight: 700; }
.seo-module .score-summary .score-label { font-size: 0.85rem; opacity: 0.9; }

/* SEO Check Item */
.seo-module .seo-check-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: var(--light);
    border-radius: 8px;
    margin-bottom: 8px;
}

.seo-module .seo-check-icon {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
}

.seo-module .seo-check-icon.success { background: #d1fae5; color: #059669; }
.seo-module .seo-check-icon.warning { background: #fef3c7; color: #d97706; }
.seo-module .seo-check-icon.error { background: #fee2e2; color: #dc2626; }

.seo-module .seo-check-label { font-weight: 600; font-size: 13px; color: var(--text); }
.seo-module .seo-check-message { font-size: 12px; color: var(--text-sec); margin-top: 2px; }

/* Loading */
.seo-module .loading-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,0.95);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
}

.seo-module .loading-overlay.active { display: flex; }

.seo-module .loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.seo-module .loading-ai {
    background: var(--ai-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 600;
    font-size: 1.1rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Empty State */
.seo-module .empty-state {
    text-align: center;
    padding: 60px 40px;
    color: var(--text-sec);
}

.seo-module .empty-state i { font-size: 50px; opacity: 0.2; margin-bottom: 16px; display: block; }
.seo-module .empty-state h3 { color: var(--text); margin-bottom: 8px; }
</style>

<div class="seo-module">

<!-- Loading -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <span id="loadingText">Chargement...</span>
</div>

<!-- Header -->
<div class="header-bar">
    <div>
        <h2><i class="fas fa-chart-line"></i> SEO des Pages</h2>
        <p style="color: var(--text-sec); margin: 4px 0 0; font-size: 14px;">
            Analysez, indexez et validez le SEO de vos pages
        </p>
    </div>
    <div style="display: flex; gap: 12px;">
        <?php if ($aiAvailable): ?>
        <button class="btn btn-ai" onclick="optimizeAllWithAI()" title="Optimiser toutes les pages non optimales">
            <i class="fas fa-magic"></i> Optimiser tout (IA)
        </button>
        <?php endif; ?>
        <button class="btn btn-primary" onclick="analyzeAllPages()">
            <i class="fas fa-sync-alt"></i> Analyser tout
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $totalPages; ?></span>
            <span class="stat-label">Pages totales</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-search"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $analyzedPages; ?></span>
            <span class="stat-label">Analysées</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?php echo $avgScore >= 60 ? 'green' : 'orange'; ?>"><i class="fas fa-tachometer-alt"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $avgScore; ?>%</span>
            <span class="stat-label">Score moyen</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-trophy"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $excellentPages; ?></span>
            <span class="stat-label">Excellentes</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $needWorkPages; ?></span>
            <span class="stat-label">À optimiser</span>
        </div>
    </div>
    <?php if ($hasNoindex): ?>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-sitemap"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $indexedPages; ?>/<?php echo $totalPages; ?></span>
            <span class="stat-label">Indexées</span>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($hasValidated): ?>
    <div class="stat-card">
        <div class="stat-icon slate"><i class="fas fa-check-double"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $validatedPages; ?>/<?php echo $totalPages; ?></span>
            <span class="stat-label">Validées</span>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($aiAvailable): ?>
    <div class="stat-card">
        <div class="stat-icon ai"><i class="fas fa-robot"></i></div>
        <div class="stat-content">
            <span class="stat-value"><?php echo $aiProvider; ?></span>
            <span class="stat-label">IA Active</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Toolbar -->
<div class="toolbar">
    <form method="GET" style="display: contents;">
        <input type="hidden" name="page" value="seo-pages">
        
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <?php if (!empty($websites)): ?>
        <select name="website_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Tous les sites</option>
            <?php foreach ($websites as $w): ?>
            <option value="<?php echo $w['id']; ?>" <?php echo $websiteFilter == $w['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($w['name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        
        <select name="filter_score" class="filter-select" onchange="this.form.submit()">
            <option value="">Tous les scores</option>
            <option value="excellent" <?php echo $filterScore === 'excellent' ? 'selected' : ''; ?>>🏆 Excellent (80%+)</option>
            <option value="good" <?php echo $filterScore === 'good' ? 'selected' : ''; ?>>✅ Bon (60-79%)</option>
            <option value="warning" <?php echo $filterScore === 'warning' ? 'selected' : ''; ?>>⚠️ À améliorer</option>
            <option value="error" <?php echo $filterScore === 'error' ? 'selected' : ''; ?>>❌ Critique</option>
            <option value="not_analyzed" <?php echo $filterScore === 'not_analyzed' ? 'selected' : ''; ?>>🔍 Non analysé</option>
        </select>

        <?php if ($hasNoindex): ?>
        <select name="filter_index" class="filter-select" onchange="this.form.submit()">
            <option value="">Indexation: Tous</option>
            <option value="0" <?php echo $filterIndex === '0' ? 'selected' : ''; ?>>✅ Indexées</option>
            <option value="1" <?php echo $filterIndex === '1' ? 'selected' : ''; ?>>🚫 NoIndex</option>
        </select>
        <?php endif; ?>

        <?php if ($hasValidated): ?>
        <select name="filter_validated" class="filter-select" onchange="this.form.submit()">
            <option value="">Validation: Tous</option>
            <option value="1" <?php echo $filterValidated === '1' ? 'selected' : ''; ?>>✅ Validées</option>
            <option value="0" <?php echo $filterValidated === '0' ? 'selected' : ''; ?>>⏳ Non validées</option>
        </select>
        <?php endif; ?>
        
        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
    </form>
</div>

<!-- Info -->
<?php if ($aiAvailable): ?>
<div class="alert alert-ai">
    <i class="fas fa-magic"></i>
    <strong><?php echo $aiProvider; ?> activé !</strong> — Cliquez <i class="fas fa-robot"></i> pour optimiser. Utilisez les toggles pour gérer l'indexation et la validation directement.
</div>
<?php else: ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>IA non configurée.</strong> Ajoutez <code>OPENAI_API_KEY</code> ou <code>ANTHROPIC_API_KEY</code> dans config.php.
</div>
<?php endif; ?>

<!-- Table -->
<?php if (!empty($pages)): ?>
<div class="table-card">
    <table>
        <thead>
            <tr>
                <th style="width: 22%;">Page</th>
                <?php if (!empty($websites)): ?><th>Site</th><?php endif; ?>
                <th>Score</th>
                <?php if ($hasNoindex): ?>
                <th style="text-align: center;">Indexation</th>
                <?php endif; ?>
                <th>Problèmes</th>
                <?php if ($hasValidated): ?>
                <th style="text-align: center;">Validation</th>
                <?php endif; ?>
                <th>Analysé</th>
                <th style="width: 160px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $p): 
                $score = $p['seo_score'] ?? 0;
                $grade = $score >= 80 ? 'excellent' : ($score >= 60 ? 'good' : ($score >= 40 ? 'warning' : ($score > 0 ? 'error' : 'not-analyzed')));
                $issues = !empty($p['seo_issues']) ? json_decode($p['seo_issues'], true) : [];
                $needsOptimization = $score > 0 && $score < 80;
                $isIndexed = $hasNoindex ? (($p['noindex'] ?? 0) == 0) : true;
                $isValidated = $hasValidated ? (($p['seo_validated'] ?? 0) == 1) : false;
                $validatedAt = $hasValidatedAt ? ($p['seo_validated_at'] ?? null) : null;
            ?>
            <tr id="row-<?php echo $p['id']; ?>">
                <td>
                    <div class="page-title-cell">
                        <span class="title"><?php echo htmlspecialchars($p['title']); ?></span>
                        <span class="slug">/<?php echo htmlspecialchars($p['slug']); ?></span>
                    </div>
                </td>
                <?php if (!empty($websites)): ?>
                <td>
                    <?php if (!empty($p['website_name'])): ?>
                    <span class="badge-website" style="background: <?php echo htmlspecialchars($p['primary_color'] ?? '#6366f1'); ?>22; color: <?php echo htmlspecialchars($p['primary_color'] ?? '#6366f1'); ?>;">
                        <?php echo htmlspecialchars($p['website_name']); ?>
                    </span>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <?php endif; ?>
                <td>
                    <span class="score-badge <?php echo $grade; ?>" id="score-<?php echo $p['id']; ?>">
                        <?php if ($score > 0): ?>
                            <?php echo $score; ?>%
                            <div class="seo-progress"><div class="seo-progress-bar <?php echo $grade; ?>" style="width: <?php echo $score; ?>%;"></div></div>
                        <?php else: ?>
                            <i class="fas fa-question"></i> —
                        <?php endif; ?>
                    </span>
                </td>

                <!-- ===== NOUVEAU : Toggle Indexation ===== -->
                <?php if ($hasNoindex): ?>
                <td style="text-align: center;">
                    <label class="index-toggle" id="toggle-index-<?php echo $p['id']; ?>">
                        <input type="checkbox" 
                               <?php echo $isIndexed ? 'checked' : ''; ?> 
                               onchange="toggleNoindex(<?php echo $p['id']; ?>, this)">
                        <div class="toggle-track">
                            <div class="toggle-thumb"></div>
                        </div>
                        <span class="toggle-label <?php echo $isIndexed ? 'indexed' : 'noindex'; ?>" 
                              id="label-index-<?php echo $p['id']; ?>">
                            <?php echo $isIndexed ? 'Index' : 'NoIndex'; ?>
                        </span>
                    </label>
                </td>
                <?php endif; ?>

                <td>
                    <div class="issues-list" id="issues-<?php echo $p['id']; ?>">
                        <?php if (!empty($issues)): ?>
                            <?php foreach (array_slice($issues, 0, 2) as $issue): ?>
                            <div class="issue"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($issue); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($issues) > 2): ?>
                            <div style="color: var(--primary); cursor: pointer; font-size: 10px;" onclick="showDetails(<?php echo $p['id']; ?>)">+<?php echo count($issues) - 2; ?> autres</div>
                            <?php endif; ?>
                        <?php elseif ($score > 0): ?>
                            <span style="color: var(--success); font-size: 11px;"><i class="fas fa-check"></i> OK</span>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                </td>

                <!-- ===== NOUVEAU : Bouton Validation ===== -->
                <?php if ($hasValidated): ?>
                <td style="text-align: center;">
                    <button type="button" 
                            class="validate-btn <?php echo $isValidated ? 'validated' : 'not-validated'; ?>" 
                            id="validate-btn-<?php echo $p['id']; ?>"
                            onclick="toggleValidation(<?php echo $p['id']; ?>, this)"
                            title="<?php echo $isValidated ? 'Cliquez pour invalider' : 'Cliquez pour valider le SEO'; ?>">
                        <i class="fas fa-<?php echo $isValidated ? 'check-circle' : 'circle'; ?>"></i>
                        <span>
                            <?php echo $isValidated ? 'Validé' : 'Valider'; ?>
                            <?php if ($validatedAt): ?>
                                <span class="validate-date"><?php echo date('d/m H:i', strtotime($validatedAt)); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                </td>
                <?php endif; ?>

                <td style="font-size: 11px; color: var(--text-sec);" id="date-<?php echo $p['id']; ?>">
                    <?php echo $p['seo_analyzed_at'] ? date('d/m H:i', strtotime($p['seo_analyzed_at'])) : '—'; ?>
                </td>
                <td>
                    <div class="actions-cell">
                        <button type="button" class="btn-icon btn-analyze" onclick="analyzePage(<?php echo $p['id']; ?>)" title="Analyser">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <?php if ($aiAvailable): ?>
                        <button type="button" class="btn-icon btn-ai-icon" onclick="optimizeWithAI(<?php echo $p['id']; ?>)" title="Optimiser avec IA" <?php echo !$needsOptimization && $score > 0 ? 'style="opacity:0.5"' : ''; ?>>
                            <i class="fas fa-magic"></i>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn-icon btn-details" onclick="showDetails(<?php echo $p['id']; ?>)" title="Détails">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <a href="?page=pages&action=edit&id=<?php echo $p['id']; ?>" class="btn-icon btn-edit" title="Éditer">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="/<?php echo $p['slug']; ?>" target="_blank" class="btn-icon btn-view" title="Voir">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="table-card">
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Aucune page trouvée</h3>
        <p>Créez d'abord des pages dans le module "Mes Pages"</p>
    </div>
</div>
<?php endif; ?>

<!-- Modal Détails -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Détails SEO</h3>
            <button class="modal-close" onclick="closeModal('detailsModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="modalBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('detailsModal')">Fermer</button>
            <div style="display: flex; gap: 10px;">
                <?php if ($aiAvailable): ?>
                <button class="btn btn-ai" id="modalOptimizeBtn" onclick="optimizeCurrentPage()">
                    <i class="fas fa-magic"></i> Optimiser avec IA
                </button>
                <?php endif; ?>
                <a href="#" class="btn btn-primary" id="modalEditLink">
                    <i class="fas fa-edit"></i> Éditer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Prévisualisation IA -->
<div class="modal-overlay" id="aiPreviewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-magic"></i> Optimisation IA</h3>
            <button class="modal-close" onclick="closeModal('aiPreviewModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" id="aiPreviewBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('aiPreviewModal')">Annuler</button>
            <button class="btn btn-success" id="applyAiBtn" onclick="applyAISuggestions()">
                <i class="fas fa-check"></i> Appliquer les modifications
            </button>
        </div>
    </div>
</div>

</div>

<script>
const API_URL = '<?php echo $apiUrl; ?>';
let currentPageId = null;
let pendingAIResult = null;

// ========================================
// TOGGLE NOINDEX (NOUVEAU v2.2)
// ========================================

function toggleNoindex(pageId, checkbox) {
    const isIndexed = checkbox.checked; // checked = indexer (noindex=0)
    const noindexValue = isIndexed ? 0 : 1;
    const toggleEl = document.getElementById('toggle-index-' + pageId);
    const labelEl = document.getElementById('label-index-' + pageId);
    
    toggleEl.classList.add('saving');
    
    fetch(API_URL + '?action=toggle-noindex&id=' + pageId + '&noindex=' + noindexValue)
        .then(r => r.json())
        .then(data => {
            toggleEl.classList.remove('saving');
            if (data.success) {
                labelEl.textContent = isIndexed ? 'Index' : 'NoIndex';
                labelEl.className = 'toggle-label ' + (isIndexed ? 'indexed' : 'noindex');
                showNotification('success', isIndexed ? '✅ Page indexée' : '🚫 Page en NoIndex');
            } else {
                // Revert
                checkbox.checked = !isIndexed;
                showNotification('error', data.error || 'Erreur');
            }
        })
        .catch(err => {
            toggleEl.classList.remove('saving');
            checkbox.checked = !isIndexed;
            showNotification('error', err.message);
        });
}

// ========================================
// TOGGLE VALIDATION SEO (NOUVEAU v2.2)
// ========================================

function toggleValidation(pageId, btn) {
    const isCurrentlyValidated = btn.classList.contains('validated');
    const newValue = isCurrentlyValidated ? 0 : 1;
    
    btn.classList.add('saving');
    
    fetch(API_URL + '?action=toggle-validation&id=' + pageId + '&validated=' + newValue)
        .then(r => r.json())
        .then(data => {
            btn.classList.remove('saving');
            if (data.success) {
                if (newValue === 1) {
                    btn.className = 'validate-btn validated';
                    btn.title = 'Cliquez pour invalider';
                    const now = new Date();
                    const dateStr = now.toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit'}) + ' ' + now.toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit'});
                    btn.innerHTML = '<i class="fas fa-check-circle"></i><span>Validé<span class="validate-date">' + dateStr + '</span></span>';
                    showNotification('success', '✅ SEO validé !');
                } else {
                    btn.className = 'validate-btn not-validated';
                    btn.title = 'Cliquez pour valider le SEO';
                    btn.innerHTML = '<i class="fas fa-circle"></i><span>Valider</span>';
                    showNotification('info', 'Validation retirée');
                }
            } else {
                showNotification('error', data.error || 'Erreur');
            }
        })
        .catch(err => {
            btn.classList.remove('saving');
            showNotification('error', err.message);
        });
}

// ========================================
// ANALYSE
// ========================================

function analyzePage(pageId) {
    showLoading('Analyse en cours...');
    fetch(API_URL + '?action=analyze&id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                updatePageRow(pageId, data.result);
                showNotification('success', 'Score: ' + data.result.percentage + '%');
            } else {
                showNotification('error', data.error);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', 'Erreur: ' + err.message);
        });
}

function analyzeAllPages() {
    if (!confirm('Analyser toutes les pages ?')) return;
    showLoading('Analyse de toutes les pages...');
    fetch(API_URL + '?action=analyze-all')
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification('success', data.analyzed + ' pages analysées');
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', err.message);
        });
}

// ========================================
// OPTIMISATION IA
// ========================================

function optimizeWithAI(pageId) {
    currentPageId = pageId;
    showLoading('🤖 L\'IA génère les optimisations SEO...');
    
    fetch(API_URL + '?action=preview-seo&id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                pendingAIResult = data.preview;
                renderAIPreview(data.current, data.preview, data.ai_provider);
            } else {
                showNotification('error', data.error);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', err.message);
        });
}

function renderAIPreview(current, preview, aiProvider) {
    const fields = [
        { key: 'seo_title', label: 'Meta Title', ideal: '50-60 car.' },
        { key: 'seo_description', label: 'Meta Description', ideal: '140-155 car.' },
        { key: 'seo_keywords', label: 'Mots-clés', ideal: '5-7 mots-clés' },
        { key: 'description', label: 'Description courte', ideal: '100-150 car.' }
    ];
    
    let html = `<div style="text-align:center;margin-bottom:16px;">
        <span style="background: var(--ai-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 700;">
            <i class="fas fa-robot"></i> Généré par ${aiProvider}
        </span>
    </div>`;
    
    fields.forEach(f => {
        const oldVal = current[f.key] || '';
        const newVal = preview[f.key] || '';
        const improved = newVal && newVal !== oldVal;
        
        html += `
            <div class="ai-preview-item ${improved ? 'improved' : ''}">
                <div class="ai-preview-label">
                    ${f.label}
                    <span class="char-count">${newVal.length} car. (idéal: ${f.ideal})</span>
                </div>
                ${oldVal ? `<div class="ai-preview-old">${escapeHtml(oldVal)}</div>` : '<div class="ai-preview-old" style="font-style:italic">(vide)</div>'}
                <div class="ai-preview-new">${escapeHtml(newVal)}</div>
            </div>
        `;
    });
    
    if (preview.suggestions && preview.suggestions.length > 0) {
        html += `
            <div class="ai-suggestions">
                <h4><i class="fas fa-lightbulb"></i> Suggestions d'amélioration</h4>
                <ul>
                    ${preview.suggestions.map(s => `<li>${escapeHtml(s)}</li>`).join('')}
                </ul>
            </div>
        `;
    }
    
    document.getElementById('aiPreviewBody').innerHTML = html;
    document.getElementById('aiPreviewModal').classList.add('active');
}

function applyAISuggestions() {
    if (!currentPageId) return;
    
    closeModal('aiPreviewModal');
    showLoading('Application des modifications...');
    
    fetch(API_URL + '?action=generate-seo&id=' + currentPageId)
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                updatePageRow(currentPageId, data.new_analysis);
                showNotification('success', '✨ SEO optimisé ! Nouveau score: ' + data.new_score + '%');
            } else {
                showNotification('error', data.error);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', err.message);
        });
}

function optimizeCurrentPage() {
    if (currentPageId) {
        closeModal('detailsModal');
        optimizeWithAI(currentPageId);
    }
}

function optimizeAllWithAI() {
    if (!confirm('Optimiser automatiquement toutes les pages avec un score < 80% ?\n\nCela peut prendre du temps et consommer des crédits API.')) return;
    
    const rows = document.querySelectorAll('tr[id^="row-"]');
    let toOptimize = [];
    
    rows.forEach(row => {
        const scoreEl = row.querySelector('.score-badge');
        const scoreText = scoreEl?.textContent || '';
        const score = parseInt(scoreText) || 0;
        
        if (score > 0 && score < 80) {
            const id = row.id.replace('row-', '');
            toOptimize.push(id);
        }
    });
    
    if (toOptimize.length === 0) {
        showNotification('info', 'Toutes les pages sont déjà optimisées !');
        return;
    }
    
    showLoading(`🤖 Optimisation de ${toOptimize.length} pages...`);
    
    let processed = 0;
    const processNext = () => {
        if (processed >= toOptimize.length) {
            hideLoading();
            showNotification('success', `${processed} pages optimisées !`);
            setTimeout(() => location.reload(), 1500);
            return;
        }
        
        document.getElementById('loadingText').textContent = `🤖 Optimisation ${processed + 1}/${toOptimize.length}...`;
        
        fetch(API_URL + '?action=generate-seo&id=' + toOptimize[processed])
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    updatePageRow(toOptimize[processed], data.new_analysis);
                }
                processed++;
                setTimeout(processNext, 500);
            })
            .catch(() => {
                processed++;
                setTimeout(processNext, 500);
            });
    };
    
    processNext();
}

// ========================================
// DÉTAILS
// ========================================

function showDetails(pageId) {
    currentPageId = pageId;
    showLoading('Chargement...');
    
    fetch(API_URL + '?action=details&id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                renderDetails(data.page, data.seo);
            }
        })
        .catch(err => {
            hideLoading();
            showNotification('error', err.message);
        });
}

function renderDetails(page, seo) {
    document.getElementById('modalTitle').textContent = page.title;
    document.getElementById('modalEditLink').href = '?page=pages&action=edit&id=' + page.id;
    
    const labels = {
        'title': 'Titre', 'seo_title': 'Meta Title', 'seo_description': 'Meta Description',
        'slug': 'URL', 'content': 'Contenu', 'description': 'Description', 'keywords': 'Mots-clés'
    };
    
    let html = `
        <div class="score-summary ${seo.grade}">
            <div class="big-score">${seo.percentage}%</div>
            <div class="score-label">Score SEO</div>
        </div>
    `;
    
    for (const [key, check] of Object.entries(seo.checks)) {
        html += `
            <div class="seo-check-item">
                <div class="seo-check-icon ${check.status}">
                    <i class="fas fa-${check.status === 'success' ? 'check' : (check.status === 'warning' ? 'exclamation' : 'times')}"></i>
                </div>
                <div>
                    <div class="seo-check-label">${labels[key] || key}</div>
                    <div class="seo-check-message">${check.message}</div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('detailsModal').classList.add('active');
}

// ========================================
// HELPERS
// ========================================

function updatePageRow(pageId, result) {
    const scoreEl = document.getElementById('score-' + pageId);
    const issuesEl = document.getElementById('issues-' + pageId);
    const dateEl = document.getElementById('date-' + pageId);
    
    if (scoreEl) {
        scoreEl.className = 'score-badge ' + result.grade;
        scoreEl.innerHTML = `${result.percentage}%<div class="seo-progress"><div class="seo-progress-bar ${result.grade}" style="width: ${result.percentage}%;"></div></div>`;
    }
    
    if (issuesEl) {
        if (result.issues?.length > 0) {
            issuesEl.innerHTML = result.issues.slice(0, 2).map(i => `<div class="issue"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(i)}</div>`).join('');
        } else {
            issuesEl.innerHTML = '<span style="color: var(--success); font-size: 11px;"><i class="fas fa-check"></i> OK</span>';
        }
    }
    
    if (dateEl) {
        const now = new Date();
        dateEl.textContent = now.toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit'}) + ' ' + now.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
    }
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function showLoading(text) {
    document.getElementById('loadingText').innerHTML = text || 'Chargement...';
    document.getElementById('loadingOverlay').classList.add('active');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('active');
}

function showNotification(type, msg) {
    document.querySelectorAll('.seo-notif').forEach(n => n.remove());
    const n = document.createElement('div');
    n.className = 'seo-notif';
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:10px;color:white;font-weight:500;z-index:3000;animation:slideIn .3s ease;box-shadow:0 4px 12px rgba(0,0,0,.15);background:${type === 'success' ? '#10b981' : type === 'info' ? '#3b82f6' : '#ef4444'}`;
    n.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'info' ? 'info-circle' : 'exclamation-circle'}"></i> ${msg}`;
    document.body.appendChild(n);
    setTimeout(() => { n.style.animation = 'slideOut .3s ease'; setTimeout(() => n.remove(), 300); }, 3500);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('active'); });
});

const style = document.createElement('style');
style.textContent = `@keyframes slideIn{from{transform:translateX(100px);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(100px);opacity:0}}`;
document.head.appendChild(style);

console.log('Module SEO v2.2 + Toggle Indexation + Validation chargé');
</script>