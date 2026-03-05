<?php
/**
 * Tab Articles SERP - Positions SERP des articles
 */

// Récupérer les articles avec leur position SERP
$serpQuery = $pdo->query("
    SELECT a.*, s.name as site_name, s.domain as site_domain
    FROM articles a 
    LEFT JOIN sites s ON a.site_id = s.id
    WHERE a.status = 'published' 
    AND a.focus_keyword IS NOT NULL
    $siteFilter
    ORDER BY 
        CASE WHEN a.serp_position IS NULL THEN 1 ELSE 0 END,
        a.serp_position ASC
");
$serpArticles = $serpQuery->fetchAll(PDO::FETCH_ASSOC);

// Stats SERP articles
$serpStats = [
    'top3' => 0,
    'top10' => 0,
    'top30' => 0,
    'beyond' => 0,
    'not_ranked' => 0
];

foreach ($serpArticles as $a) {
    if ($a['serp_position'] === null) {
        $serpStats['not_ranked']++;
    } elseif ($a['serp_position'] <= 3) {
        $serpStats['top3']++;
    } elseif ($a['serp_position'] <= 10) {
        $serpStats['top10']++;
    } elseif ($a['serp_position'] <= 30) {
        $serpStats['top30']++;
    } else {
        $serpStats['beyond']++;
    }
}
?>

<div class="serp-content">
    
    <!-- SERP Stats -->
    <div class="serp-stats-row">
        <div class="serp-stat top3">
            <i class="fas fa-trophy"></i>
            <span class="value"><?= $serpStats['top3'] ?></span>
            <span class="label">Top 3</span>
        </div>
        <div class="serp-stat top10">
            <i class="fas fa-medal"></i>
            <span class="value"><?= $serpStats['top10'] ?></span>
            <span class="label">Top 10</span>
        </div>
        <div class="serp-stat top30">
            <i class="fas fa-chart-line"></i>
            <span class="value"><?= $serpStats['top30'] ?></span>
            <span class="label">Top 30</span>
        </div>
        <div class="serp-stat beyond">
            <i class="fas fa-chevron-down"></i>
            <span class="value"><?= $serpStats['beyond'] ?></span>
            <span class="label">Au-delà</span>
        </div>
        <div class="serp-stat not-ranked">
            <i class="fas fa-question"></i>
            <span class="value"><?= $serpStats['not_ranked'] ?></span>
            <span class="label">Non classé</span>
        </div>
    </div>
    
    <!-- Table -->
    <table class="seo-table">
        <thead>
            <tr>
                <th>Position</th>
                <th>Article</th>
                <th>Mot-clé ciblé</th>
                <th>Site</th>
                <th>Évolution</th>
                <th>Dernière vérif.</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($serpArticles)): ?>
                <tr>
                    <td colspan="7" class="no-results">
                        <div class="empty-state">
                            <i class="fas fa-newspaper"></i>
                            <h3>Aucun article avec mot-clé</h3>
                            <p>Définissez un mot-clé principal dans vos articles pour commencer le suivi SERP.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($serpArticles as $a): ?>
                    <?php 
                    $position = $a['serp_position'];
                    $posClass = 'none';
                    if ($position !== null) {
                        if ($position <= 3) $posClass = 'top3';
                        elseif ($position <= 10) $posClass = 'top10';
                        elseif ($position <= 30) $posClass = 'top30';
                        else $posClass = 'low';
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="serp-position <?= $posClass ?>">
                                <?= $position !== null ? $position : '-' ?>
                            </div>
                        </td>
                        <td>
                            <div class="page-title-cell">
                                <span class="title"><?= htmlspecialchars($a['title']) ?></span>
                                <span class="slug">/blog/<?= htmlspecialchars($a['slug']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="keyword-badge large">
                                <?= htmlspecialchars($a['serp_keyword'] ?? $a['focus_keyword']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="site-badge"><?= htmlspecialchars($a['site_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <?php 
                            $histQuery = $pdo->prepare("
                                SELECT serp_position 
                                FROM seo_history 
                                WHERE content_type = 'article' AND content_id = ?
                                ORDER BY check_date DESC 
                                LIMIT 2
                            ");
                            $histQuery->execute([$a['id']]);
                            $history = $histQuery->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (count($history) >= 2) {
                                $diff = $history[1] - $history[0];
                                if ($diff > 0) {
                                    echo '<span class="evolution up"><i class="fas fa-arrow-up"></i> +' . $diff . '</span>';
                                } elseif ($diff < 0) {
                                    echo '<span class="evolution down"><i class="fas fa-arrow-down"></i> ' . $diff . '</span>';
                                } else {
                                    echo '<span class="evolution stable"><i class="fas fa-minus"></i> 0</span>';
                                }
                            } else {
                                echo '<span class="evolution na">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($a['last_seo_check']): ?>
                                <span class="date-text"><?= date('d/m H:i', strtotime($a['last_seo_check'])) ?></span>
                            <?php else: ?>
                                <span class="never">Jamais</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn check" onclick="checkArticleSERP(<?= $a['id'] ?>)" title="Vérifier">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <a href="https://www.google.fr/search?q=<?= urlencode($a['focus_keyword']) ?>" 
                                   target="_blank" class="action-btn view" title="Voir sur Google">
                                    <i class="fab fa-google"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Bulk Actions -->
    <div class="bulk-actions">
        <button class="btn btn-secondary" onclick="checkAllArticleSERP()">
            <i class="fas fa-sync-alt"></i> Vérifier toutes les positions
        </button>
    </div>
    
</div>

<script>
function checkArticleSERP(articleId) {
    showLoader('Vérification...');
    fetch('api/check-article-serp.php?id=' + articleId)
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                if (data.position) {
                    showNotification('success', 'Position : ' + data.position);
                } else {
                    showNotification('warning', 'Article non trouvé dans les 100 premiers résultats');
                }
                location.reload();
            }
        });
}

function checkAllArticleSERP() {
    if (confirm('Vérifier la position SERP de tous les articles ?')) {
        showLoader('Vérification en cours...');
        fetch('api/check-all-article-serp.php')
            .then(r => r.json())
            .then(data => {
                hideLoader();
                showNotification('success', data.checked + ' articles vérifiés');
                location.reload();
            });
    }
}
</script>