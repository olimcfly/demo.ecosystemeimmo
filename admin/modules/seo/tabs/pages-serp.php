<?php
/**
 * Tab Pages SERP - Positions dans les résultats Google
 */

// Récupérer les pages avec leur position SERP
$serpQuery = $pdo->query("
    SELECT p.*, s.name as site_name, s.domain as site_domain
    FROM pages p 
    LEFT JOIN sites s ON p.site_id = s.id
    WHERE p.status = 'published' 
    AND p.serp_keyword IS NOT NULL
    $siteFilter
    ORDER BY 
        CASE WHEN p.serp_position IS NULL THEN 1 ELSE 0 END,
        p.serp_position ASC
");
$serpPages = $serpQuery->fetchAll(PDO::FETCH_ASSOC);

// Stats SERP
$serpStats = [
    'top3' => 0,
    'top10' => 0,
    'top30' => 0,
    'beyond' => 0,
    'not_ranked' => 0
];

foreach ($serpPages as $p) {
    if ($p['serp_position'] === null) {
        $serpStats['not_ranked']++;
    } elseif ($p['serp_position'] <= 3) {
        $serpStats['top3']++;
    } elseif ($p['serp_position'] <= 10) {
        $serpStats['top10']++;
    } elseif ($p['serp_position'] <= 30) {
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
    
    <!-- Info Box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Comment fonctionne le suivi SERP ?</strong>
            <p>Définissez un mot-clé principal pour chaque page. Le système vérifiera périodiquement 
               la position de votre page dans les résultats Google pour ce mot-clé.</p>
        </div>
    </div>
    
    <!-- Table -->
    <table class="seo-table">
        <thead>
            <tr>
                <th>Position</th>
                <th>Page</th>
                <th>Mot-clé ciblé</th>
                <th>Site</th>
                <th>Évolution</th>
                <th>Dernière vérif.</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($serpPages)): ?>
                <tr>
                    <td colspan="7" class="no-results">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Aucune page avec mot-clé SERP</h3>
                            <p>Définissez un mot-clé principal dans l'éditeur SEO de vos pages pour commencer le suivi.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($serpPages as $p): ?>
                    <?php 
                    $position = $p['serp_position'];
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
                                <span class="title"><?= htmlspecialchars($p['title']) ?></span>
                                <span class="slug">/<?= htmlspecialchars($p['slug']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="keyword-badge large">
                                <?= htmlspecialchars($p['serp_keyword'] ?? $p['focus_keyword']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="site-badge"><?= htmlspecialchars($p['site_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <?php 
                            // Charger l'historique pour l'évolution
                            $histQuery = $pdo->prepare("
                                SELECT serp_position 
                                FROM seo_history 
                                WHERE content_type = 'page' AND content_id = ?
                                ORDER BY check_date DESC 
                                LIMIT 2
                            ");
                            $histQuery->execute([$p['id']]);
                            $history = $histQuery->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (count($history) >= 2) {
                                $diff = $history[1] - $history[0]; // positif = amélioration
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
                            <?php if ($p['last_seo_check']): ?>
                                <span class="date-text"><?= date('d/m H:i', strtotime($p['last_seo_check'])) ?></span>
                            <?php else: ?>
                                <span class="never">Jamais</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn check" onclick="checkSERP(<?= $p['id'] ?>)" title="Vérifier position">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="action-btn edit" onclick="editKeyword(<?= $p['id'] ?>)" title="Modifier mot-clé">
                                    <i class="fas fa-key"></i>
                                </button>
                                <a href="https://www.google.fr/search?q=<?= urlencode($p['serp_keyword'] ?? $p['focus_keyword']) ?>" 
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
        <button class="btn btn-secondary" onclick="checkAllSERP()">
            <i class="fas fa-sync-alt"></i> Vérifier toutes les positions
        </button>
        <button class="btn btn-outline" onclick="exportSERPReport()">
            <i class="fas fa-file-export"></i> Exporter rapport SERP
        </button>
    </div>
    
</div>

<style>
.serp-stats-row {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.serp-stat {
    flex: 1;
    min-width: 120px;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.serp-stat i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.serp-stat .value {
    font-size: 1.8rem;
    font-weight: 700;
}

.serp-stat .label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.serp-stat.top3 { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.serp-stat.top10 { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
.serp-stat.top30 { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; }
.serp-stat.beyond { background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #475569; }
.serp-stat.not-ranked { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }

.info-box {
    display: flex;
    gap: 15px;
    padding: 20px;
    background: #eff6ff;
    border-radius: 12px;
    margin-bottom: 25px;
    border-left: 4px solid #3b82f6;
}

.info-box i {
    color: #3b82f6;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.info-box strong {
    display: block;
    margin-bottom: 5px;
    color: #1e40af;
}

.info-box p {
    margin: 0;
    color: #1e40af;
    font-size: 0.9rem;
}

.keyword-badge.large {
    font-size: 0.9rem;
    padding: 6px 12px;
}

.evolution {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.85rem;
}

.evolution.up { background: #d1fae5; color: #059669; }
.evolution.down { background: #fee2e2; color: #dc2626; }
.evolution.stable { background: #f1f5f9; color: #64748b; }
.evolution.na { color: #94a3b8; }

.empty-state {
    text-align: center;
    padding: 60px 40px;
}

.empty-state i {
    font-size: 3rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h3 {
    margin: 0 0 10px;
    color: #475569;
}

.empty-state p {
    margin: 0;
    color: #64748b;
}

.bulk-actions {
    display: flex;
    gap: 15px;
    margin-top: 20px;
    padding: 15px 20px;
    background: white;
    border-radius: 12px;
}

.btn-outline {
    background: white;
    border: 1px solid #e2e8f0;
    color: #475569;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-outline:hover {
    border-color: #3b82f6;
    color: #3b82f6;
}
</style>

<script>
function checkSERP(pageId) {
    showLoader('Vérification position Google...');
    fetch('api/check-serp.php?id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                if (data.position) {
                    showNotification('success', 'Position : ' + data.position);
                } else {
                    showNotification('warning', 'Page non trouvée dans les 100 premiers résultats');
                }
                location.reload();
            } else {
                showNotification('error', data.error);
            }
        });
}

function checkAllSERP() {
    if (confirm('Vérifier la position SERP de toutes les pages ? Cette opération peut prendre plusieurs minutes.')) {
        showLoader('Vérification en cours...');
        fetch('api/check-all-serp.php')
            .then(r => r.json())
            .then(data => {
                hideLoader();
                showNotification('success', data.checked + ' pages vérifiées');
                location.reload();
            });
    }
}

function editKeyword(pageId) {
    const keyword = prompt('Entrez le mot-clé SERP à suivre :');
    if (keyword !== null) {
        fetch('api/save-serp-keyword.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: pageId, keyword: keyword })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function exportSERPReport() {
    window.location = 'api/export-serp.php';
}
</script>