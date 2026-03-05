<?php
/**
 * Tab Overview - Vue d'ensemble SEO
 */

// Distribution des scores
$scoreDistribution = $pdo->query("
    SELECT 
        CASE 
            WHEN seo_score >= 80 THEN 'Excellent (80-100)'
            WHEN seo_score >= 60 THEN 'Bon (60-79)'
            WHEN seo_score >= 40 THEN 'À améliorer (40-59)'
            ELSE 'Faible (0-39)'
        END as range_label,
        COUNT(*) as count
    FROM pages 
    WHERE status = 'published' $siteFilter
    GROUP BY range_label
    ORDER BY MIN(seo_score) DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Top pages par score
$topPages = $pdo->query("
    SELECT p.*, s.name as site_name 
    FROM pages p 
    LEFT JOIN sites s ON p.site_id = s.id
    WHERE p.status = 'published' $siteFilter
    ORDER BY p.seo_score DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Pages à optimiser
$lowPages = $pdo->query("
    SELECT p.*, s.name as site_name 
    FROM pages p 
    LEFT JOIN sites s ON p.site_id = s.id
    WHERE p.status = 'published' AND p.seo_score < 60 $siteFilter
    ORDER BY p.seo_score ASC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Évolution indexation
$indexationTrend = $pdo->query("
    SELECT 
        DATE(check_date) as date,
        SUM(CASE WHEN is_indexed = 1 THEN 1 ELSE 0 END) as indexed,
        COUNT(*) as total
    FROM seo_history 
    WHERE content_type = 'page' 
    AND check_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(check_date)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="overview-content">
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Distribution des scores SEO</h3>
            <canvas id="scoreDistributionChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-line"></i> Évolution indexation (30 jours)</h3>
            <canvas id="indexationTrendChart" height="200"></canvas>
        </div>
    </div>
    
    <!-- Two columns -->
    <div class="overview-columns">
        <!-- Top Performers -->
        <div class="overview-card">
            <div class="card-header">
                <h3><i class="fas fa-trophy"></i> Meilleures performances</h3>
            </div>
            <div class="card-body">
                <?php if (empty($topPages)): ?>
                    <p class="no-data">Aucune page analysée</p>
                <?php else: ?>
                    <ul class="performance-list">
                        <?php foreach ($topPages as $page): ?>
                            <li class="performance-item">
                                <div class="item-info">
                                    <span class="item-title"><?= htmlspecialchars($page['title']) ?></span>
                                    <span class="item-site"><?= htmlspecialchars($page['site_name'] ?? 'N/A') ?></span>
                                </div>
                                <div class="item-score">
                                    <?php $badge = SEOAnalyzer::getScoreBadge($page['seo_score']); ?>
                                    <span class="score-badge <?= $badge['class'] ?>">
                                        <?= $page['seo_score'] ?>%
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Need Optimization -->
        <div class="overview-card warning">
            <div class="card-header">
                <h3><i class="fas fa-exclamation-triangle"></i> À optimiser en priorité</h3>
            </div>
            <div class="card-body">
                <?php if (empty($lowPages)): ?>
                    <p class="no-data success">Toutes les pages ont un bon score !</p>
                <?php else: ?>
                    <ul class="performance-list">
                        <?php foreach ($lowPages as $page): ?>
                            <li class="performance-item">
                                <div class="item-info">
                                    <span class="item-title"><?= htmlspecialchars($page['title']) ?></span>
                                    <span class="item-site"><?= htmlspecialchars($page['site_name'] ?? 'N/A') ?></span>
                                </div>
                                <div class="item-actions">
                                    <?php $badge = SEOAnalyzer::getScoreBadge($page['seo_score']); ?>
                                    <span class="score-badge <?= $badge['class'] ?>">
                                        <?= $page['seo_score'] ?>%
                                    </span>
                                    <a href="../pages/edit.php?id=<?= $page['id'] ?>" class="btn-small">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
        <div class="actions-grid">
            <button class="action-card" onclick="runFullAnalysis()">
                <i class="fas fa-sync-alt"></i>
                <span>Analyser toutes les pages</span>
            </button>
            <button class="action-card" onclick="checkAllIndexation()">
                <i class="fas fa-search"></i>
                <span>Vérifier l'indexation</span>
            </button>
            <button class="action-card" onclick="generateSitemap()">
                <i class="fas fa-sitemap"></i>
                <span>Regénérer sitemap</span>
            </button>
            <button class="action-card" onclick="exportSEOReport()">
                <i class="fas fa-file-export"></i>
                <span>Exporter rapport SEO</span>
            </button>
        </div>
    </div>
    
</div>

<style>
.overview-content {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.overview-columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.overview-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.overview-card.warning {
    border-left: 4px solid #f59e0b;
}

.card-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.card-header h3 {
    margin: 0;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 15px 20px;
}

.performance-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.performance-item:last-child {
    border-bottom: none;
}

.item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.item-title {
    font-weight: 500;
    color: #1e293b;
}

.item-site {
    font-size: 0.8rem;
    color: #64748b;
}

.item-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-small {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 6px;
    color: #64748b;
    text-decoration: none;
}

.btn-small:hover {
    background: #e2e8f0;
    color: #3b82f6;
}

.no-data {
    text-align: center;
    color: #64748b;
    padding: 20px;
}

.no-data.success {
    color: #10b981;
}

/* Quick Actions */
.quick-actions {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.quick-actions h3 {
    margin: 0 0 15px;
    font-size: 1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    padding: 20px;
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    border-color: #3b82f6;
}

.action-card i {
    font-size: 1.5rem;
    color: #3b82f6;
}

.action-card span {
    font-size: 0.9rem;
    color: #475569;
    text-align: center;
}
</style>

<script>
// Score Distribution Chart
const scoreDistData = <?= json_encode($scoreDistribution) ?>;
new Chart(document.getElementById('scoreDistributionChart'), {
    type: 'doughnut',
    data: {
        labels: scoreDistData.map(d => d.range_label),
        datasets: [{
            data: scoreDistData.map(d => d.count),
            backgroundColor: ['#10b981', '#22c55e', '#f59e0b', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Indexation Trend Chart
const trendData = <?= json_encode($indexationTrend) ?>;
new Chart(document.getElementById('indexationTrendChart'), {
    type: 'line',
    data: {
        labels: trendData.map(d => d.date),
        datasets: [{
            label: 'Pages indexées',
            data: trendData.map(d => d.indexed),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

function checkAllIndexation() {
    if (confirm('Vérifier l\'indexation de toutes les pages ?')) {
        showLoader('Vérification en cours...');
        fetch('api/check-all-index.php')
            .then(r => r.json())
            .then(data => {
                hideLoader();
                showNotification('success', data.indexed + '/' + data.total + ' pages indexées');
                location.reload();
            });
    }
}

function generateSitemap() {
    showLoader('Génération...');
    fetch('api/generate-sitemap.php')
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('success', 'Sitemap généré: ' + data.url);
            }
        });
}

function exportSEOReport() {
    window.location = 'api/export-report.php';
}
</script>