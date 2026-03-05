<?php
/**
 * admin/modules/diagnostic/index.php
 * Diagnostic visuel de tous les modules CRM/CMS
 */

// Forcer l'affichage des erreurs en dev
ini_set('display_errors', 1);
error_reporting(E_ALL);

// =========================================================================
// CHARGEMENT CONFIG & DB
// =========================================================================
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/database.php';

// Vérifier que $db est disponible (PDO via Database::getInstance())
if (!isset($db) || !($db instanceof PDO)) {
    die('<h1>Erreur : connexion DB non disponible</h1>');
}

// Session (si pas déjà démarrée)
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME ?? 'ECOSYSTEM_ADMIN');
    session_start();
}

// =========================================================================
// LANCER LE DIAGNOSTIC
// =========================================================================
require_once __DIR__ . '/ModuleDiagnostic.php';

$modulesPath = realpath(__DIR__ . '/../');
$diagnostic = new ModuleDiagnostic($db, $modulesPath);
$report = $diagnostic->runFullDiagnostic();

$summary = $report['summary'];
$modules = $report['modules'];
$dbHealth = $report['db_health'];

// Grouper par catégorie
$categories = [];
foreach ($modules as $slug => $mod) {
    $cat = $mod['category'];
    if (!isset($categories[$cat])) $categories[$cat] = [];
    $categories[$cat][$slug] = $mod;
}

$catOrder = ['CRM', 'CMS', 'Immobilier', 'SEO', 'Marketing', 'IA', 'Système', 'Non référencé'];
uksort($categories, function ($a, $b) use ($catOrder) {
    $ia = array_search($a, $catOrder);
    $ib = array_search($b, $catOrder);
    return ($ia === false ? 999 : $ia) - ($ib === false ? 999 : $ib);
});

// Score global
$scorePercent = $summary['total'] > 0
    ? round(($summary['ok'] / $summary['total']) * 100)
    : 0;

// =========================================================================
// LAYOUT ADMIN (header optionnel)
// =========================================================================
$layoutHeader = __DIR__ . '/../../layout/header.php';
$layoutFooter = __DIR__ . '/../../layout/footer.php';
// Force standalone - le layout admin nécessite un init complet
// Passer à true si tu veux réintégrer dans le layout admin
$hasLayout = false; // file_exists($layoutHeader);

if ($hasLayout) {
    $pageTitle = 'Diagnostic des Modules';
    include $layoutHeader;
} else {
    // Layout standalone si pas de layout admin
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Diagnostic des Modules - <?= SITE_TITLE ?? 'Admin' ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    </head>
    <body style="margin:0; background:#f8fafc; font-family:'Inter',sans-serif; color:#1f2937;">
    <?php
}
?>

<style>
/* ========== DIAGNOSTIC STYLES ========== */
.diag-wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }

/* Header */
.diag-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
.diag-header h1 { font-size:26px; font-weight:800; margin:0; display:flex; align-items:center; gap:10px; }
.diag-header-actions { display:flex; gap:8px; align-items:center; }
.diag-btn {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 18px; border:none; border-radius:8px;
    font-size:13px; font-weight:600; cursor:pointer; transition:all 0.2s;
    text-decoration:none;
}
.diag-btn-primary { background:#6366f1; color:#fff; }
.diag-btn-primary:hover { background:#4f46e5; }
.diag-btn-secondary { background:#374151; color:#fff; }
.diag-btn-secondary:hover { background:#1f2937; }
.diag-timestamp { color:#9ca3af; font-size:12px; }

/* Score circle */
.diag-score-section { display:flex; gap:24px; margin-bottom:28px; flex-wrap:wrap; }
.diag-score-circle {
    width:140px; height:140px; border-radius:50%;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    background: conic-gradient(
        #22c55e 0% <?= $scorePercent ?>%,
        #e5e7eb <?= $scorePercent ?>% 100%
    );
    position:relative; flex-shrink:0;
}
.diag-score-inner {
    width:110px; height:110px; border-radius:50%; background:#fff;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.diag-score-inner .score-num { font-size:36px; font-weight:800; color:#1f2937; line-height:1; }
.diag-score-inner .score-label { font-size:11px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; }

/* Summary cards */
.diag-summary-cards { display:flex; gap:12px; flex:1; min-width:300px; flex-wrap:wrap; }
.diag-stat {
    flex:1; min-width:120px; background:#fff; border-radius:12px; padding:18px 20px;
    box-shadow:0 1px 4px rgba(0,0,0,0.05); border-left:4px solid #e5e7eb;
    display:flex; flex-direction:column; gap:4px;
}
.diag-stat.s-total   { border-left-color:#6366f1; }
.diag-stat.s-ok      { border-left-color:#22c55e; }
.diag-stat.s-warning { border-left-color:#f59e0b; }
.diag-stat.s-error   { border-left-color:#ef4444; }
.diag-stat .stat-num { font-size:32px; font-weight:800; line-height:1; }
.diag-stat.s-total .stat-num   { color:#6366f1; }
.diag-stat.s-ok .stat-num      { color:#22c55e; }
.diag-stat.s-warning .stat-num { color:#f59e0b; }
.diag-stat.s-error .stat-num   { color:#ef4444; }
.diag-stat .stat-label { font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; }

/* Progress bar */
.diag-progress { background:#f3f4f6; border-radius:999px; height:10px; overflow:hidden; display:flex; margin-bottom:24px; }
.diag-progress .p-ok      { background:#22c55e; transition:width 0.6s; }
.diag-progress .p-warning { background:#f59e0b; transition:width 0.6s; }
.diag-progress .p-error   { background:#ef4444; transition:width 0.6s; }

/* Filters */
.diag-filters { display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap; }
.diag-filter {
    padding:6px 14px; border-radius:999px; border:1px solid #e5e7eb;
    background:#fff; font-size:13px; font-weight:500; cursor:pointer;
    transition:all 0.15s; display:flex; align-items:center; gap:6px;
}
.diag-filter:hover { background:#f9fafb; }
.diag-filter.active { background:#1f2937; color:#fff; border-color:#1f2937; }
.diag-filter .cnt { background:rgba(0,0,0,0.08); padding:1px 7px; border-radius:999px; font-size:11px; }
.diag-filter.active .cnt { background:rgba(255,255,255,0.2); }

/* DB Health */
.diag-db { background:#fff; border-radius:12px; padding:20px 24px; box-shadow:0 1px 4px rgba(0,0,0,0.05); margin-bottom:28px; }
.diag-db h3 { font-size:16px; font-weight:700; margin:0 0 14px 0; display:flex; align-items:center; gap:8px; }
.diag-db table { width:100%; border-collapse:collapse; }
.diag-db td { padding:8px 14px; border-bottom:1px solid #f3f4f6; font-size:13px; }
.diag-db tr:last-child td { border-bottom:none; }
.db-dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
.db-dot.ok      { background:#22c55e; }
.db-dot.warning { background:#f59e0b; }
.db-dot.error   { background:#ef4444; }

/* Category */
.diag-cat { margin-bottom:28px; }
.diag-cat-title {
    font-size:17px; font-weight:700; margin-bottom:14px; padding-bottom:8px;
    border-bottom:2px solid #e5e7eb; display:flex; align-items:center; gap:10px;
}
.diag-cat-title .cat-cnt { background:#f3f4f6; color:#6b7280; font-size:12px; font-weight:500; padding:2px 10px; border-radius:999px; }

/* Module cards */
.diag-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(360px, 1fr)); gap:14px; }

.diag-card {
    background:#fff; border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,0.05);
    overflow:hidden; transition:box-shadow 0.2s; cursor:pointer; border:1px solid #f3f4f6;
}
.diag-card:hover { box-shadow:0 4px 14px rgba(0,0,0,0.08); }

.diag-card .c-head { display:flex; align-items:center; padding:14px 18px; gap:12px; }
.diag-card .c-icon {
    width:38px; height:38px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; flex-shrink:0;
}
.diag-card.st-ok .c-icon      { background:#dcfce7; color:#16a34a; }
.diag-card.st-warning .c-icon { background:#fef3c7; color:#d97706; }
.diag-card.st-error .c-icon   { background:#fee2e2; color:#dc2626; }

.diag-card .c-info { flex:1; min-width:0; }
.diag-card .c-name { font-weight:600; font-size:14px; }
.diag-card .c-slug { font-size:11px; color:#9ca3af; font-family:'Fira Code',monospace; }
.diag-card .c-badge {
    margin-left:auto; padding:3px 10px; border-radius:999px;
    font-size:11px; font-weight:700; text-transform:uppercase; flex-shrink:0;
}
.diag-card.st-ok .c-badge      { background:#dcfce7; color:#16a34a; }
.diag-card.st-warning .c-badge { background:#fef3c7; color:#d97706; }
.diag-card.st-error .c-badge   { background:#fee2e2; color:#dc2626; }

/* Expandable details */
.diag-card .c-details {
    max-height:0; overflow:hidden; transition:max-height 0.3s ease;
    border-top:1px solid transparent;
}
.diag-card.open .c-details { max-height:800px; border-top-color:#f3f4f6; }
.diag-card .c-details-inner { padding:10px 18px 14px; }

.chk { display:flex; align-items:flex-start; gap:7px; padding:5px 0; font-size:12px; color:#4b5563; border-bottom:1px solid #fafafa; }
.chk:last-child { border-bottom:none; }
.chk-dot { width:8px; height:8px; border-radius:50%; margin-top:4px; flex-shrink:0; }
.chk-dot.ok      { background:#22c55e; }
.chk-dot.warning { background:#f59e0b; }
.chk-dot.error   { background:#ef4444; }

@media (max-width:768px) {
    .diag-grid { grid-template-columns:1fr; }
    .diag-score-section { flex-direction:column; align-items:center; }
}
</style>

<div class="diag-wrap">

    <!-- HEADER -->
    <div class="diag-header">
        <h1>
            <i class="fas fa-stethoscope" style="color:#6366f1;"></i>
            Diagnostic des Modules
        </h1>
        <div class="diag-header-actions">
            <span class="diag-timestamp"><?= htmlspecialchars($report['timestamp']) ?></span>
            <button class="diag-btn diag-btn-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Relancer
            </button>
            <button class="diag-btn diag-btn-primary" onclick="exportJSON()">
                <i class="fas fa-download"></i> Export JSON
            </button>
        </div>
    </div>

    <!-- SCORE + SUMMARY -->
    <div class="diag-score-section">
        <div class="diag-score-circle">
            <div class="diag-score-inner">
                <span class="score-num"><?= $scorePercent ?>%</span>
                <span class="score-label">Santé</span>
            </div>
        </div>
        <div class="diag-summary-cards">
            <div class="diag-stat s-total">
                <span class="stat-num"><?= $summary['total'] ?></span>
                <span class="stat-label">Modules</span>
            </div>
            <div class="diag-stat s-ok">
                <span class="stat-num"><?= $summary['ok'] ?></span>
                <span class="stat-label">OK</span>
            </div>
            <div class="diag-stat s-warning">
                <span class="stat-num"><?= $summary['warning'] ?></span>
                <span class="stat-label">Warnings</span>
            </div>
            <div class="diag-stat s-error">
                <span class="stat-num"><?= $summary['error'] ?></span>
                <span class="stat-label">Erreurs</span>
            </div>
        </div>
    </div>

    <!-- PROGRESS BAR -->
    <?php if ($summary['total'] > 0): ?>
    <div class="diag-progress">
        <div class="p-ok" style="width:<?= round($summary['ok'] / $summary['total'] * 100) ?>%"></div>
        <div class="p-warning" style="width:<?= round($summary['warning'] / $summary['total'] * 100) ?>%"></div>
        <div class="p-error" style="width:<?= round($summary['error'] / $summary['total'] * 100) ?>%"></div>
    </div>
    <?php endif; ?>

    <!-- FILTERS -->
    <div class="diag-filters">
        <button class="diag-filter active" data-f="all">Tous <span class="cnt"><?= $summary['total'] ?></span></button>
        <button class="diag-filter" data-f="ok"><span style="color:#22c55e;">&#9679;</span> OK <span class="cnt"><?= $summary['ok'] ?></span></button>
        <button class="diag-filter" data-f="warning"><span style="color:#f59e0b;">&#9679;</span> Warnings <span class="cnt"><?= $summary['warning'] ?></span></button>
        <button class="diag-filter" data-f="error"><span style="color:#ef4444;">&#9679;</span> Erreurs <span class="cnt"><?= $summary['error'] ?></span></button>
    </div>

    <!-- DB HEALTH -->
    <div class="diag-db">
        <h3><i class="fas fa-database" style="color:#6366f1;"></i> Base de données</h3>
        <table>
            <?php foreach ($dbHealth as $row): ?>
            <tr>
                <td style="width:24px;"><span class="db-dot <?= $row['status'] ?>"></span></td>
                <td style="font-weight:500;"><?= htmlspecialchars($row['check']) ?></td>
                <td style="color:#6b7280; text-align:right;"><?= htmlspecialchars($row['value'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- MODULES PAR CATÉGORIE -->
    <?php foreach ($categories as $catName => $catModules): ?>
    <div class="diag-cat" data-cat="<?= htmlspecialchars($catName) ?>">
        <div class="diag-cat-title">
            <?= htmlspecialchars($catName) ?>
            <span class="cat-cnt"><?= count($catModules) ?></span>
        </div>
        <div class="diag-grid">
            <?php foreach ($catModules as $slug => $mod): ?>
            <div class="diag-card st-<?= $mod['status'] ?>" data-st="<?= $mod['status'] ?>" onclick="this.classList.toggle('open')">
                <div class="c-head">
                    <div class="c-icon"><i class="<?= htmlspecialchars($mod['icon']) ?>"></i></div>
                    <div class="c-info">
                        <div class="c-name"><?= htmlspecialchars($mod['label']) ?></div>
                        <div class="c-slug">/modules/<?= htmlspecialchars($slug) ?>/</div>
                    </div>
                    <span class="c-badge">
                        <?php if ($mod['status'] === 'ok'): ?>&#10003; OK
                        <?php elseif ($mod['status'] === 'warning'): ?>&#9888; Warn
                        <?php else: ?>&#10007; Err<?php endif; ?>
                    </span>
                </div>
                <div class="c-details">
                    <div class="c-details-inner">
                        <?php foreach ($mod['checks'] as $chk): ?>
                        <div class="chk">
                            <span class="chk-dot <?= $chk['status'] ?>"></span>
                            <span><?= htmlspecialchars($chk['message']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div><!-- /diag-wrap -->

<script>
// Filters
document.querySelectorAll('.diag-filter').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.diag-filter').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.f;
        document.querySelectorAll('.diag-card').forEach(c => {
            c.style.display = (f === 'all' || c.dataset.st === f) ? '' : 'none';
        });
        document.querySelectorAll('.diag-cat').forEach(cat => {
            const visible = cat.querySelectorAll('.diag-card:not([style*="display: none"])');
            cat.style.display = visible.length === 0 ? 'none' : '';
        });
    });
});

// Export
function exportJSON() {
    const data = <?= json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?>;
    const blob = new Blob([JSON.stringify(data, null, 2)], {type:'application/json'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'diagnostic-' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
}
</script>

<?php
if ($hasLayout && file_exists($layoutFooter)) {
    include $layoutFooter;
} else {
    echo '</body></html>';
}
?>