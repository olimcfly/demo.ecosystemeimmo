<?php
/**
 * Tab Articles SEO - Liste des articles avec scores SEO
 */

// Pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtres
$search = $_GET['search_article'] ?? '';
$scoreFilter = $_GET['score_article'] ?? '';

$whereClause = "WHERE a.status = 'published' $siteFilter";
$params = [];

if ($search) {
    $whereClause .= " AND (a.title LIKE ? OR a.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($scoreFilter) {
    switch ($scoreFilter) {
        case 'excellent':
            $whereClause .= " AND a.seo_score >= 80";
            break;
        case 'good':
            $whereClause .= " AND a.seo_score >= 60 AND a.seo_score < 80";
            break;
        case 'warning':
            $whereClause .= " AND a.seo_score >= 40 AND a.seo_score < 60";
            break;
        case 'error':
            $whereClause .= " AND a.seo_score < 40";
            break;
    }
}

// Count total
$countQuery = $pdo->prepare("SELECT COUNT(*) FROM articles a $whereClause");
$countQuery->execute($params);
$total = $countQuery->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get articles
$query = $pdo->prepare("
    SELECT a.*, s.name as site_name, s.domain as site_domain
    FROM articles a 
    LEFT JOIN sites s ON a.site_id = s.id
    $whereClause
    ORDER BY a.seo_score DESC, a.updated_at DESC
    LIMIT $perPage OFFSET $offset
");
$query->execute($params);
$articles = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="articles-seo-content">
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <input type="text" class="search-input" placeholder="Rechercher un article..." 
               value="<?= htmlspecialchars($search) ?>" 
               onkeyup="debounceSearchArticle(this.value)">
        
        <select onchange="filterArticleByScore(this.value)">
            <option value="">Tous les scores</option>
            <option value="excellent" <?= $scoreFilter === 'excellent' ? 'selected' : '' ?>>Excellent (80+)</option>
            <option value="good" <?= $scoreFilter === 'good' ? 'selected' : '' ?>>Bon (60-79)</option>
            <option value="warning" <?= $scoreFilter === 'warning' ? 'selected' : '' ?>>À améliorer (40-59)</option>
            <option value="error" <?= $scoreFilter === 'error' ? 'selected' : '' ?>>Faible (-40)</option>
        </select>
        
        <span class="result-count"><?= $total ?> article(s)</span>
    </div>
    
    <!-- Table -->
    <table class="seo-table">
        <thead>
            <tr>
                <th>Article</th>
                <th>Site</th>
                <th>Score SEO</th>
                <th>Meta Title</th>
                <th>Meta Desc.</th>
                <th>Mot-clé</th>
                <th>Indexé</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($articles)): ?>
                <tr>
                    <td colspan="8" class="no-results">Aucun article trouvé</td>
                </tr>
            <?php else: ?>
                <?php foreach ($articles as $a): ?>
                    <?php $badge = SEOAnalyzer::getScoreBadge($a['seo_score'] ?? 0); ?>
                    <tr data-article-id="<?= $a['id'] ?>">
                        <td>
                            <div class="page-title-cell">
                                <span class="title"><?= htmlspecialchars($a['title']) ?></span>
                                <span class="slug">/blog/<?= htmlspecialchars($a['slug']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="site-badge"><?= htmlspecialchars($a['site_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <div class="score-cell">
                                <span class="score-badge <?= $badge['class'] ?>">
                                    <?= $a['seo_score'] ?? 0 ?>%
                                </span>
                                <div class="seo-progress">
                                    <div class="seo-progress-bar <?= $badge['class'] ?>" 
                                         style="width: <?= $a['seo_score'] ?? 0 ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $titleLen = mb_strlen($a['meta_title'] ?? '');
                            $titleStatus = $titleLen >= 50 && $titleLen <= 60 ? 'good' : ($titleLen > 0 ? 'warning' : 'error');
                            ?>
                            <span class="meta-status <?= $titleStatus ?>">
                                <?= $titleLen ?>/60
                            </span>
                        </td>
                        <td>
                            <?php 
                            $descLen = mb_strlen($a['meta_description'] ?? '');
                            $descStatus = $descLen >= 150 && $descLen <= 160 ? 'good' : ($descLen > 0 ? 'warning' : 'error');
                            ?>
                            <span class="meta-status <?= $descStatus ?>">
                                <?= $descLen ?>/160
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($a['focus_keyword'])): ?>
                                <span class="keyword-badge"><?= htmlspecialchars($a['focus_keyword']) ?></span>
                            <?php else: ?>
                                <span class="no-keyword">Non défini</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a['is_indexed'] ?? false): ?>
                                <span class="index-status indexed">
                                    <i class="fas fa-check"></i>
                                </span>
                            <?php else: ?>
                                <span class="index-status not-indexed">
                                    <i class="fas fa-times"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn analyze" onclick="analyzeArticle(<?= $a['id'] ?>)" title="Analyser">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <a href="../articles/edit.php?id=<?= $a['id'] ?>" class="action-btn edit" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= $a['site_domain'] ?>/blog/<?= $a['slug'] ?>" target="_blank" 
                                   class="action-btn view" title="Voir">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?tab=articles-seo&p=<?= $page - 1 ?>" class="page-link">← Précédent</a>
            <?php endif; ?>
            <span class="page-info">Page <?= $page ?> / <?= $totalPages ?></span>
            <?php if ($page < $totalPages): ?>
                <a href="?tab=articles-seo&p=<?= $page + 1 ?>" class="page-link">Suivant →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
</div>

<script>
let articleSearchTimeout;

function debounceSearchArticle(value) {
    clearTimeout(articleSearchTimeout);
    articleSearchTimeout = setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.set('search_article', value);
        url.searchParams.set('tab', 'articles-seo');
        url.searchParams.set('p', '1');
        window.location = url;
    }, 500);
}

function filterArticleByScore(score) {
    const url = new URL(window.location);
    if (score) {
        url.searchParams.set('score_article', score);
    } else {
        url.searchParams.delete('score_article');
    }
    url.searchParams.set('tab', 'articles-seo');
    window.location = url;
}

function analyzeArticle(articleId) {
    showLoader('Analyse...');
    fetch('api/analyze-article.php?id=' + articleId)
        .then(r => r.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('success', 'Score SEO: ' + data.result.percentage + '%');
                location.reload();
            }
        });
}
</script>