<?php
/**
 * Tab Indexation - Statut d'indexation Google des pages
 */

// Filtres
$indexFilter = $_GET['indexed'] ?? '';

$whereClause = "WHERE p.status = 'published' $siteFilter";
if ($indexFilter === 'yes') {
    $whereClause .= " AND p.is_indexed = 1";
} elseif ($indexFilter === 'no') {
    $whereClause .= " AND p.is_indexed = 0";
} elseif ($indexFilter === 'pending') {
    $whereClause .= " AND p.last_seo_check IS NULL";
}

// Récupérer les pages
$indexQuery = $pdo->query("
    SELECT p.*, s.name as site_name, s.domain as site_domain
    FROM pages p 
    LEFT JOIN sites s ON p.site_id = s.id
    $whereClause
    ORDER BY p.is_indexed DESC, p.last_seo_check DESC
");
$indexPages = $indexQuery->fetchAll(PDO::FETCH_ASSOC);

// Stats indexation
$indexStats = [
    'indexed' => 0,
    'not_indexed' => 0,
    'pending' => 0,
    'total' => count($indexPages)
];

foreach ($indexPages as $p) {
    if ($p['last_seo_check'] === null) {
        $indexStats['pending']++;
    } elseif ($p['is_indexed']) {
        $indexStats['indexed']++;
    } else {
        $indexStats['not_indexed']++;
    }
}
?>

<div class="indexation-content">
    
    <!-- Stats -->
    <div class="index-stats-row">
        <div class="index-stat indexed" onclick="filterByIndexation('yes')">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="value"><?= $indexStats['indexed'] ?></span>
                <span class="label">Pages indexées</span>
            </div>
            <div class="stat-percent">
                <?= $indexStats['total'] > 0 ? round(($indexStats['indexed'] / $indexStats['total']) * 100) : 0 ?>%
            </div>
        </div>
        
        <div class="index-stat not-indexed" onclick="filterByIndexation('no')">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-info">
                <span class="value"><?= $indexStats['not_indexed'] ?></span>
                <span class="label">Non indexées</span>
            </div>
            <div class="stat-percent">
                <?= $indexStats['total'] > 0 ? round(($indexStats['not_indexed'] / $indexStats['total']) * 100) : 0 ?>%
            </div>
        </div>
        
        <div class="index-stat pending" onclick="filterByIndexation('pending')">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <span class="value"><?= $indexStats['pending'] ?></span>
                <span class="label">En attente</span>
            </div>
            <div class="stat-percent">
                <?= $indexStats['total'] > 0 ? round(($indexStats['pending'] / $indexStats['total']) * 100) : 0 ?>%
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <select onchange="filterByIndexation(this.value)">
            <option value="">Toutes les pages</option>
            <option value="yes" <?= $indexFilter === 'yes' ? 'selected' : '' ?>>Indexées</option>
            <option value="no" <?= $indexFilter === 'no' ? 'selected' : '' ?>>Non indexées</option>
            <option value="pending" <?= $indexFilter === 'pending' ? 'selected' : '' ?>>En attente de vérification</option>
        </select>
        
        <button class="btn btn-primary" onclick="checkAllIndexation()">
            <i class="fas fa-sync-alt"></i> Vérifier l'indexation
        </button>
        
        <button class="btn btn-secondary" onclick="requestIndexation()">
            <i class="fab fa-google"></i> Demander l'indexation
        </button>
    </div>
    
    <!-- Table -->
    <table class="seo-table">
        <thead>
            <tr>
                <th>Statut</th>
                <th>Page</th>
                <th>URL</th>
                <th>Site</th>
                <th>Dernière vérification</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($indexPages)): ?>
                <tr>
                    <td colspan="6" class="no-results">Aucune page trouvée</td>
                </tr>
            <?php else: ?>
                <?php foreach ($indexPages as $p): ?>
                    <?php 
                    if ($p['last_seo_check'] === null) {
                        $statusClass = 'pending';
                        $statusText = 'En attente';
                        $statusIcon = 'clock';
                    } elseif ($p['is_indexed']) {
                        $statusClass = 'indexed';
                        $statusText = 'Indexée';
                        $statusIcon = 'check-circle';
                    } else {
                        $statusClass = 'not-indexed';
                        $statusText = 'Non indexée';
                        $statusIcon = 'times-circle';
                    }
                    $fullUrl = rtrim($p['site_domain'] ?? '', '/') . '/' . ltrim($p['slug'], '/');
                    ?>
                    <tr data-page-id="<?= $p['id'] ?>">
                        <td>
                            <span class="index-status <?= $statusClass ?>">
                                <i class="fas fa-<?= $statusIcon ?>"></i>
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td>
                            <div class="page-title-cell">
                                <span class="title"><?= htmlspecialchars($p['title']) ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="<?= $fullUrl ?>" target="_blank" class="url-link">
                                <?= htmlspecialchars($fullUrl) ?>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </td>
                        <td>
                            <span class="site-badge"><?= htmlspecialchars($p['site_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <?php if ($p['last_seo_check']): ?>
                                <span class="date-text"><?= date('d/m/Y H:i', strtotime($p['last_seo_check'])) ?></span>
                            <?php else: ?>
                                <span class="never">Jamais vérifiée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn check" onclick="checkPageIndex(<?= $p['id'] ?>)" title="Vérifier">
                                    <i class="fas fa-search"></i>
                                </button>
                                <a href="https://www.google.fr/search?q=site:<?= urlencode($fullUrl) ?>" 
                                   target="_blank" class="action-btn view" title="Voir sur Google">
                                    <i class="fab fa-google"></i>
                                </a>
                                <?php if (!$p['is_indexed']): ?>
                                    <button class="action-btn" style="background:#fef3c7;color:#d97706;" 
                                            onclick="requestPageIndexation(<?= $p['id'] ?>)" title="Demander indexation">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Help Section -->
    <div class="help-section">
        <h3><i class="fas fa-question-circle"></i> Comment améliorer l'indexation ?</h3>
        <div class="help-grid">
            <div class="help-card">
                <i class="fas fa-sitemap"></i>
                <h4>Sitemap XML</h4>
                <p>Assurez-vous que votre sitemap est à jour et soumis à Google Search Console.</p>
            </div>
            <div class="help-card">
                <i class="fas fa-link"></i>
                <h4>Liens internes</h4>
                <p>Liez vos nouvelles pages depuis d'autres pages déjà indexées de votre site.</p>
            </div>
            <div class="help-card">
                <i class="fas fa-file-alt"></i>
                <h4>Contenu de qualité</h4>
                <p>Google privilégie les pages avec un contenu unique, utile et bien structuré.</p>
            </div>
            <div class="help-card">
                <i class="fas fa-robot"></i>
                <h4>Robots.txt</h4>
                <p>Vérifiez que votre fichier robots.txt n'empêche pas l'indexation de vos pages.</p>
            </div>
        </div>
    </div>
    
</div>

<style>
.index-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.index-stat {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border-radius: 12px;
    cursor: pointer;
    transition: transform 0.2s;
}

.index-stat:hover {
    transform: translateY(-2px);
}

.index-stat.indexed {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
}

.index-stat.not-indexed {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
}

.index-stat.pending {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
}

.index-stat .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.index-stat.indexed .stat-icon { background: rgba(5, 150, 105, 0.2); color: #059669; }
.index-stat.not-indexed .stat-icon { background: rgba(220, 38, 38, 0.2); color: #dc2626; }
.index-stat.pending .stat-icon { background: rgba(217, 119, 6, 0.2); color: #d97706; }

.index-stat .stat-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.index-stat .value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
}

.index-stat .label {
    font-size: 0.9rem;
    color: #475569;
}

.index-stat .stat-percent {
    font-size: 1.5rem;
    font-weight: 600;
    color: #64748b;
}

.url-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.url-link:hover {
    text-decoration: underline;
}

.url-link i {
    font-size: 0.7rem;
}

/* Help Section */
.help-section {
    margin-top: 30px;
    background: white;
    border-radius: 12px;
    padding: 25px;
}

.help-section h3 {
    margin: 0 0 20px;
    font-size: 1.1rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.help-card {
    padding: 20px;
    background: #f8fafc;
    border-radius: 10px;
    text-align: center;
}

.help-card i {
    font-size: 2rem;
    color: #3b82f6;
    margin-bottom: 15px;
}

.help-card h4 {
    margin: 0 0 10px;
    color: #1e293b;
}

.help-card p {
    margin: 0;
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.5;
}
</style>

<script>
function filterByIndexation(value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set('indexed', value);
    } else {
        url.searchParams.delete('indexed');
    }
    url.searchParams.set('tab', 'pages-indexed');
    window.location = url;
}

function checkPageIndex(pageId) {
    showLoader('Vérification...');
    fetch('api/check-index.php?id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification(data.indexed ? 'success' : 'warning',
                    data.indexed ? 'Page indexée !' : 'Page non indexée');
                location.reload();
            }
        });
}

function checkAllIndexation() {
    if (confirm('Vérifier l\'indexation de toutes les pages ?')) {
        showLoader('Vérification en cours...');
        fetch('api/check-all-index.php')
            .then(r => r.json())
            .then(data => {
                hideLoader();
                showNotification('success', 
                    data.indexed + '/' + data.total + ' pages indexées');
                location.reload();
            });
    }
}

function requestIndexation() {
    // Sélectionner les pages non indexées
    const notIndexedPages = document.querySelectorAll('tr[data-page-id] .index-status.not-indexed');
    if (notIndexedPages.length === 0) {
        showNotification('info', 'Toutes les pages sont déjà indexées !');
        return;
    }
    
    if (confirm('Demander l\'indexation de ' + notIndexedPages.length + ' page(s) non indexées ?')) {
        showLoader('Envoi des demandes...');
        fetch('api/request-all-indexation.php')
            .then(r => r.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showNotification('success', data.requested + ' demande(s) envoyée(s)');
                }
            });
    }
}

function requestPageIndexation(pageId) {
    showLoader('Envoi de la demande...');
    fetch('api/request-indexation.php?id=' + pageId)
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('success', 'Demande d\'indexation envoyée à Google');
            } else {
                showNotification('error', data.error || 'Erreur lors de l\'envoi');
            }
        });
}
</script>