<?php
/**
 * Module Analytics — Tableau de bord du trafic
 * /admin/modules/analytics/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> '.$e->getMessage().'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db = $pdo;

$pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(255) DEFAULT NULL,
    referrer VARCHAR(500) DEFAULT NULL,
    source VARCHAR(100) DEFAULT 'direct',
    medium VARCHAR(100) DEFAULT NULL,
    campaign VARCHAR(100) DEFAULT NULL,
    device VARCHAR(20) DEFAULT 'desktop',
    browser VARCHAR(100) DEFAULT NULL,
    country VARCHAR(5) DEFAULT 'FR',
    city VARCHAR(100) DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    user_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_page (page_url(191)),
    INDEX idx_source (source),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS conversion_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_label VARCHAR(255) DEFAULT NULL,
    page_url VARCHAR(500) DEFAULT NULL,
    lead_id INT DEFAULT NULL,
    value DECIMAL(10,2) DEFAULT 0,
    session_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (event_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$period = $_GET['period'] ?? '30d';
$periodMap = [
    '7d'  => ['label' => '7 jours',   'days' => 7],
    '30d' => ['label' => '30 jours',  'days' => 30],
    '90d' => ['label' => '90 jours',  'days' => 90],
    '12m' => ['label' => '12 mois',   'days' => 365],
];
$pd = $periodMap[$period] ?? $periodMap['30d'];
$daysBack = $pd['days'];
$dateFrom = date('Y-m-d', strtotime("-{$daysBack} days"));

$q = fn($sql) => (int)($pdo->query($sql)->fetchColumn() ?? 0);
$safe = fn($sql) => (function() use ($pdo, $sql) { try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e) { return []; } })();

$totalViews     = $q("SELECT COUNT(*) FROM page_views WHERE created_at >= '{$dateFrom}'");
$uniqueVisitors = $q("SELECT COUNT(DISTINCT session_id) FROM page_views WHERE created_at >= '{$dateFrom}' AND session_id IS NOT NULL");
$totalSessions  = $q("SELECT COUNT(DISTINCT session_id) FROM page_views WHERE created_at >= '{$dateFrom}'");
$conversions    = $q("SELECT COUNT(*) FROM conversion_events WHERE created_at >= '{$dateFrom}'");
$bounceRate     = $totalSessions > 0 ? round(($q("SELECT COUNT(*) FROM (SELECT session_id FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY session_id HAVING COUNT(*) = 1) t") / $totalSessions) * 100, 1) : 0;
$avgPages       = $totalSessions > 0 ? round($totalViews / $totalSessions, 1) : 0;
$convRate       = $totalSessions > 0 ? round(($conversions / $totalSessions) * 100, 2) : 0;

$prevFrom = date('Y-m-d', strtotime("-" . ($daysBack * 2) . " days"));
$prevTo   = $dateFrom;
$prevViews     = $q("SELECT COUNT(*) FROM page_views WHERE created_at >= '{$prevFrom}' AND created_at < '{$prevTo}'");
$prevVisitors  = $q("SELECT COUNT(DISTINCT session_id) FROM page_views WHERE created_at >= '{$prevFrom}' AND created_at < '{$prevTo}' AND session_id IS NOT NULL");
$pctViews    = $prevViews > 0 ? round((($totalViews - $prevViews) / $prevViews) * 100, 1) : 0;
$pctVisitors = $prevVisitors > 0 ? round((($uniqueVisitors - $prevVisitors) / $prevVisitors) * 100, 1) : 0;

if ($daysBack <= 31) {
    $chartData = $safe("SELECT DATE(created_at) AS d, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY DATE(created_at) ORDER BY d");
} else {
    $chartData = $safe("SELECT DATE_FORMAT(created_at, '%Y-%m') AS d, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY d");
}

$topPages = $safe("SELECT page_url, page_title, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY page_url, page_title ORDER BY views DESC LIMIT 10");

$sources = $safe("SELECT source, COUNT(*) AS views, COUNT(DISTINCT session_id) AS visitors FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY source ORDER BY views DESC LIMIT 8");

$devices = $safe("SELECT device, COUNT(*) AS cnt FROM page_views WHERE created_at >= '{$dateFrom}' GROUP BY device ORDER BY cnt DESC");

$topReferrers = $safe("SELECT referrer, COUNT(*) AS cnt FROM page_views WHERE created_at >= '{$dateFrom}' AND referrer IS NOT NULL AND referrer != '' GROUP BY referrer ORDER BY cnt DESC LIMIT 8");

$recentConversions = $safe("SELECT * FROM conversion_events WHERE created_at >= '{$dateFrom}' ORDER BY created_at DESC LIMIT 10");

$gaId = '';
try {
    $r = $pdo->query("SELECT setting_value FROM settings WHERE category='analytics' AND setting_key='ga_id'")->fetchColumn();
    if ($r) $gaId = $r;
} catch(Exception $e) {}

$chartLabels = json_encode(array_map(fn($r) => $daysBack <= 31 ? date('d/m', strtotime($r['d'])) : date('M Y', strtotime($r['d'].'-01')), $chartData));
$chartViews = json_encode(array_column($chartData, 'views'));
$chartVisitors = json_encode(array_column($chartData, 'visitors'));

$sourceLabels = json_encode(array_column($sources, 'source'));
$sourceValues = json_encode(array_map('intval', array_column($sources, 'views')));

$deviceLabels = json_encode(array_column($devices, 'device'));
$deviceValues = json_encode(array_map('intval', array_column($devices, 'cnt')));

$trendIcon = fn($v) => $v > 0 ? '<i class="fas fa-arrow-up" style="color:var(--green)"></i>' : ($v < 0 ? '<i class="fas fa-arrow-down" style="color:var(--red)"></i>' : '<i class="fas fa-minus" style="color:var(--text-3)"></i>');
?>

<style>
.an-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}
.an-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:all .2s}
.an-stat:hover{transform:translateY(-2px);box-shadow:var(--shadow)}
.an-stat-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.an-stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
.an-stat-trend{font-size:.7rem;font-weight:600;display:flex;align-items:center;gap:3px}
.an-stat-val{font-size:1.8rem;font-weight:800;color:var(--text);line-height:1}
.an-stat-label{font-size:.75rem;color:var(--text-3);margin-top:4px}
.an-chart-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;margin-bottom:20px}
.an-chart-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
.an-chart-head h3{font-size:.95rem;font-weight:700;color:var(--text)}
.an-split{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.an-mini-chart{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px}
.an-mini-chart h4{font-size:.85rem;font-weight:700;color:var(--text);margin-bottom:14px}
.an-source-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--surface-2)}
.an-source-row:last-child{border:0}
.an-source-bar{flex:1;height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden}
.an-source-fill{height:100%;border-radius:3px;background:var(--accent);transition:width .4s}
.an-source-pct{font-size:.7rem;font-weight:600;color:var(--text-3);min-width:36px;text-align:right}
.an-source-name{font-size:.78rem;font-weight:500;color:var(--text);min-width:80px}
.an-source-val{font-size:.7rem;color:var(--text-3);min-width:50px;text-align:right}
.an-period-btn{padding:5px 12px;border:1px solid var(--border);border-radius:6px;background:var(--surface);font-size:.7rem;font-weight:600;cursor:pointer;color:var(--text-3);font-family:var(--font);transition:all .15s;text-decoration:none}
.an-period-btn:hover{border-color:var(--accent);color:var(--accent)}
.an-period-btn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
@media(max-width:1024px){.an-stats{grid-template-columns:repeat(2,1fr)}.an-split{grid-template-columns:1fr}}
@media(max-width:768px){.an-stats{grid-template-columns:1fr}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-chart-line"></i> Analytics</h1>
        <p>Trafic, conversions et performance de votre site immobilier</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= number_format($totalViews) ?></div><div class="mod-stat-label">Pages vues</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= number_format($uniqueVisitors) ?></div><div class="mod-stat-label">Visiteurs</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $convRate ?>%</div><div class="mod-stat-label">Conversion</div></div>
    </div>
</div>

<div class="mod-toolbar">
    <div class="mod-toolbar-left mod-flex mod-gap-sm">
        <?php foreach ($periodMap as $pk => $pv): ?>
        <a href="?page=analytics&period=<?= $pk ?>" class="an-period-btn <?= $period === $pk ? 'active' : '' ?>"><?= $pv['label'] ?></a>
        <?php endforeach; ?>
    </div>
    <div class="mod-toolbar-right">
        <?php if ($gaId): ?>
        <span class="mod-badge mod-badge-active"><i class="fab fa-google" style="margin-right:4px"></i> GA: <?= htmlspecialchars($gaId) ?></span>
        <?php else: ?>
        <a href="?page=settings-analytics" class="mod-btn mod-btn-secondary mod-btn-sm"><i class="fas fa-cog"></i> Configurer GA</a>
        <?php endif; ?>
    </div>
</div>

<div class="an-stats">
    <div class="an-stat">
        <div class="an-stat-top">
            <div class="an-stat-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-eye"></i></div>
            <div class="an-stat-trend"><?= $trendIcon($pctViews) ?> <?= abs($pctViews) ?>%</div>
        </div>
        <div class="an-stat-val"><?= number_format($totalViews) ?></div>
        <div class="an-stat-label">Pages vues</div>
    </div>
    <div class="an-stat">
        <div class="an-stat-top">
            <div class="an-stat-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-users"></i></div>
            <div class="an-stat-trend"><?= $trendIcon($pctVisitors) ?> <?= abs($pctVisitors) ?>%</div>
        </div>
        <div class="an-stat-val"><?= number_format($uniqueVisitors) ?></div>
        <div class="an-stat-label">Visiteurs uniques</div>
    </div>
    <div class="an-stat">
        <div class="an-stat-top">
            <div class="an-stat-icon" style="background:var(--amber-bg);color:var(--amber)"><i class="fas fa-sign-out-alt"></i></div>
        </div>
        <div class="an-stat-val"><?= $bounceRate ?>%</div>
        <div class="an-stat-label">Taux de rebond</div>
    </div>
    <div class="an-stat">
        <div class="an-stat-top">
            <div class="an-stat-icon" style="background:var(--red-bg);color:var(--red)"><i class="fas fa-bullseye"></i></div>
        </div>
        <div class="an-stat-val"><?= $conversions ?></div>
        <div class="an-stat-label">Conversions (<?= $convRate ?>%)</div>
    </div>
</div>

<div class="an-chart-wrap">
    <div class="an-chart-head">
        <h3><i class="fas fa-chart-area" style="color:var(--accent);margin-right:6px"></i> Évolution du trafic — <?= $pd['label'] ?></h3>
        <span class="mod-text-xs mod-text-muted"><?= $avgPages ?> pages/session en moyenne</span>
    </div>
    <canvas id="trafficChart" height="90"></canvas>
</div>

<div class="an-split">
    <div class="an-mini-chart">
        <h4><i class="fas fa-share-alt" style="color:var(--accent);margin-right:4px"></i> Sources de trafic</h4>
        <?php if (empty($sources)): ?>
        <div class="mod-empty" style="padding:30px"><i class="fas fa-globe"></i><p>Aucune donnée</p></div>
        <?php else:
            $maxS = max(array_column($sources, 'views')) ?: 1;
            $sourceIcons = ['google' => 'fab fa-google', 'facebook' => 'fab fa-facebook', 'instagram' => 'fab fa-instagram', 'direct' => 'fas fa-globe', 'referral' => 'fas fa-link', 'email' => 'fas fa-envelope', 'organic' => 'fas fa-search', 'ads' => 'fas fa-ad'];
            foreach ($sources as $s):
                $pct = round(($s['views'] / $totalViews) * 100, 1);
                $icon = $sourceIcons[strtolower($s['source'])] ?? 'fas fa-external-link-alt';
        ?>
        <div class="an-source-row">
            <i class="<?= $icon ?>" style="font-size:.75rem;color:var(--text-3);width:16px;text-align:center"></i>
            <span class="an-source-name"><?= htmlspecialchars(ucfirst($s['source'])) ?></span>
            <div class="an-source-bar"><div class="an-source-fill" style="width:<?= round(($s['views']/$maxS)*100) ?>%"></div></div>
            <span class="an-source-val"><?= number_format($s['views']) ?></span>
            <span class="an-source-pct"><?= $pct ?>%</span>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <div class="an-mini-chart">
        <h4><i class="fas fa-mobile-alt" style="color:var(--accent);margin-right:4px"></i> Appareils</h4>
        <canvas id="deviceChart" height="180"></canvas>
    </div>
</div>

<div class="an-split">
    <div class="an-mini-chart">
        <h4><i class="fas fa-file-alt" style="color:var(--accent);margin-right:4px"></i> Pages les plus vues</h4>
        <?php if (empty($topPages)): ?>
        <div class="mod-empty" style="padding:30px"><i class="fas fa-file"></i><p>Aucune donnée</p></div>
        <?php else: ?>
        <div class="mod-table-wrap" style="border:0">
            <table class="mod-table">
                <thead><tr><th>Page</th><th>Vues</th><th>Visiteurs</th></tr></thead>
                <tbody>
                <?php foreach ($topPages as $pg): ?>
                <tr>
                    <td>
                        <strong class="mod-text-sm"><?= htmlspecialchars($pg['page_title'] ?: $pg['page_url']) ?></strong>
                        <div class="mod-text-xs mod-text-muted"><?= htmlspecialchars($pg['page_url']) ?></div>
                    </td>
                    <td><strong><?= number_format($pg['views']) ?></strong></td>
                    <td class="mod-text-muted"><?= number_format($pg['visitors']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="an-mini-chart">
        <h4><i class="fas fa-bullseye" style="color:var(--accent);margin-right:4px"></i> Dernières conversions</h4>
        <?php if (empty($recentConversions)): ?>
        <div class="mod-empty" style="padding:30px"><i class="fas fa-bullseye"></i><p>Aucune conversion enregistrée</p></div>
        <?php else: ?>
        <?php foreach ($recentConversions as $cv): ?>
        <div class="an-source-row">
            <div style="width:28px;height:28px;border-radius:8px;background:var(--green-bg);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0"><i class="fas fa-check"></i></div>
            <div style="flex:1;min-width:0">
                <div class="mod-text-sm" style="font-weight:600"><?= htmlspecialchars(ucfirst($cv['event_type'])) ?></div>
                <div class="mod-text-xs mod-text-muted"><?= htmlspecialchars($cv['event_label'] ?? '') ?></div>
            </div>
            <div class="mod-text-xs mod-text-muted"><?= date('d/m H:i', strtotime($cv['created_at'])) ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php if (!empty($topReferrers)): ?>
<div class="an-mini-chart">
    <h4><i class="fas fa-link" style="color:var(--accent);margin-right:4px"></i> Top Referrers</h4>
    <?php $maxR = max(array_column($topReferrers, 'cnt')) ?: 1;
    foreach ($topReferrers as $ref):
        $domain = parse_url($ref['referrer'], PHP_URL_HOST) ?: $ref['referrer'];
    ?>
    <div class="an-source-row">
        <span class="an-source-name"><?= htmlspecialchars($domain) ?></span>
        <div class="an-source-bar"><div class="an-source-fill" style="width:<?= round(($ref['cnt']/$maxR)*100) ?>%;background:var(--green)"></div></div>
        <span class="an-source-val"><?= number_format($ref['cnt']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartColors = {
    accent: getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#6366f1',
    green: '#10b981',
    border: getComputedStyle(document.documentElement).getPropertyValue('--border').trim() || '#e2e8f0'
};

new Chart(document.getElementById('trafficChart'), {
    type: 'line',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Pages vues',
            data: <?= $chartViews ?>,
            borderColor: chartColors.accent,
            backgroundColor: chartColors.accent + '18',
            fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 6, borderWidth: 2
        },{
            label: 'Visiteurs',
            data: <?= $chartVisitors ?>,
            borderColor: chartColors.green,
            backgroundColor: chartColors.green + '18',
            fill: true, tension: .35, pointRadius: 3, pointHoverRadius: 6, borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: chartColors.border }, ticks: { font: { size: 10 } } }
        },
        interaction: { intersect: false, mode: 'index' }
    }
});

const devLabels = <?= $deviceLabels ?>;
const devValues = <?= $deviceValues ?>;
if (devLabels.length > 0) {
    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: devLabels,
            datasets: [{ data: devValues, backgroundColor: ['#6366f1','#10b981','#f59e0b','#ec4899','#06b6d4'], borderWidth: 0, hoverOffset: 8 }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, usePointStyle: true, pointStyle: 'circle', padding: 12 } } }
        }
    });
} else {
    document.getElementById('deviceChart').parentElement.innerHTML += '<div style="text-align:center;color:var(--text-3);padding:40px;font-size:.85rem">Aucune donnée appareil</div>';
}
</script>