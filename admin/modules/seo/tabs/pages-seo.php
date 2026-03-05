<?php
/**
 * Tab Pages SEO - Liste des pages avec scores SEO
 */

// Pagination
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtres
$search = $_GET['search'] ?? '';
$scoreFilter = $_GET['score'] ?? '';

$whereClause = "WHERE p.status = 'published' $siteFilter";
$params = [];

if ($search) {
    $whereClause .= " AND (p.title LIKE ? OR p.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($scoreFilter) {
    switch ($scoreFilter) {
        case 'excellent':
            $whereClause .= " AND p.seo_score >= 80";
            break;
        case 'good':
            $whereClause .= " AND p.seo_score >= 60 AND p.seo_score < 80";
            break;
        case 'warning':
            $whereClause .= " AND p.seo_score >= 40 AND p.seo_score < 60";
            break;
        case 'error':
            $whereClause .= " AND p.seo_score < 40";
            break;
    }
}

// Count total
$countQuery = $pdo->prepare("SELECT COUNT(*) FROM pages p $whereClause");
$countQuery->execute($params);
$total = $countQuery->fetchColumn();
$totalPages = ceil($total / $perPage);

// Get pages
$query = $pdo->prepare("
    SELECT p.*, s.name as site_name, s.domain as site_domain
    FROM pages p 
    LEFT JOIN sites s ON p.site_id = s.id
    $whereClause
    ORDER BY p.seo_score DESC, p.updated_at DESC
    LIMIT $perPage OFFSET $offset
");
$query->execute($params);
$pages = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="pages-seo-content">
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <input type="text" class="search-input" placeholder="Rechercher une page..." 
               value="<?= htmlspecialchars($search) ?>" 
               onkeyup="debounceSearch(this.value)">
        
        <select onchange="filterByScore(this.value)">
            <option value="">Tous les scores</option>
            <option value="excellent" <?= $scoreFilter === 'excellent' ? 'selected' : '' ?>>Excellent (80+)</option>
            <option value="good" <?= $scoreFilter === 'good' ? 'selected' : '' ?>>Bon (60-79)</option>
            <option value="warning" <?= $scoreFilter === 'warning' ? 'selected' : '' ?>>À améliorer (40-59)</option>
            <option value="error" <?= $scoreFilter === 'error' ? 'selected' : '' ?>>Faible (-40)</option>
        </select>
        
        <span class="result-count"><?= $total ?> page(s)</span>
    </div>
    
    <!-- Table -->
    <table class="seo-table">
        <thead>
            <tr>
                <th>Page</th>
                <th>Site</th>
                <th>Score SEO</th>
                <th>Meta Title</th>
                <th>Meta Desc.</th>
                <th>Mot-clé</th>
                <th>Dernière analyse</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="8" class="no-results">Aucune page trouvée</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pages as $p): ?>
                    <?php $badge = SEOAnalyzer::getScoreBadge($p['seo_score']); ?>
                    <tr data-page-id="<?= $p['id'] ?>">
                        <td>
                            <div class="page-title-cell">
                                <span class="title"><?= htmlspecialchars($p['title']) ?></span>
                                <span class="slug">/<?= htmlspecialchars($p['slug']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="site-badge"><?= htmlspecialchars($p['site_name'] ?? 'N/A') ?></span>
                        </td>
                        <td>
                            <div class="score-cell">
                                <span class="score-badge <?= $badge['class'] ?>">
                                    <?= $p['seo_score'] ?>%
                                </span>
                                <div class="seo-progress">
                                    <div class="seo-progress-bar <?= $badge['class'] ?>" 
                                         style="width: <?= $p['seo_score'] ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $titleLen = mb_strlen($p['meta_title'] ?? '');
                            $titleStatus = $titleLen >= 50 && $titleLen <= 60 ? 'good' : ($titleLen > 0 ? 'warning' : 'error');
                            ?>
                            <span class="meta-status <?= $titleStatus ?>">
                                <?= $titleLen ?>/60
                                <i class="fas fa-<?= $titleStatus === 'good' ? 'check' : ($titleStatus === 'warning' ? 'exclamation' : 'times') ?>"></i>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $descLen = mb_strlen($p['meta_description'] ?? '');
                            $descStatus = $descLen >= 150 && $descLen <= 160 ? 'good' : ($descLen > 0 ? 'warning' : 'error');
                            ?>
                            <span class="meta-status <?= $descStatus ?>">
                                <?= $descLen ?>/160
                                <i class="fas fa-<?= $descStatus === 'good' ? 'check' : ($descStatus === 'warning' ? 'exclamation' : 'times') ?>"></i>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['focus_keyword']): ?>
                                <span class="keyword-badge"><?= htmlspecialchars($p['focus_keyword']) ?></span>
                            <?php else: ?>
                                <span class="no-keyword">Non défini</span>
                            <?php endif; ?>
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
                                <button class="action-btn analyze" onclick="analyzePage(<?= $p['id'] ?>)" title="Analyser">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button class="action-btn edit" onclick="openSEOEditor(<?= $p['id'] ?>)" title="Éditer SEO">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="<?= $p['site_domain'] ?>/<?= $p['slug'] ?>" target="_blank" 
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
                <a href="?tab=pages-seo&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&score=<?= $scoreFilter ?>" 
                   class="page-link">← Précédent</a>
            <?php endif; ?>
            
            <span class="page-info">Page <?= $page ?> / <?= $totalPages ?></span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?tab=pages-seo&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&score=<?= $scoreFilter ?>" 
                   class="page-link">Suivant →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
</div>

<!-- Modal SEO Editor -->
<div id="seoEditorModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-search"></i> Éditer les métadonnées SEO</h2>
            <button class="modal-close" onclick="closeSEOEditor()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="seo-page-id">
            
            <div class="form-group">
                <label>Mot-clé principal</label>
                <input type="text" id="seo-focus-keyword" class="form-control" 
                       placeholder="Ex: immobilier Bordeaux">
                <small>Le mot-clé sur lequel vous souhaitez vous positionner</small>
            </div>
            
            <div class="form-group">
                <label>Meta Title <span class="char-count" id="title-count">0/60</span></label>
                <input type="text" id="seo-meta-title" class="form-control" maxlength="70"
                       oninput="updateCharCount(this, 'title-count', 60)">
                <small>Titre affiché dans les résultats Google (50-60 caractères idéal)</small>
            </div>
            
            <div class="form-group">
                <label>Meta Description <span class="char-count" id="desc-count">0/160</span></label>
                <textarea id="seo-meta-description" class="form-control" rows="3" maxlength="180"
                          oninput="updateCharCount(this, 'desc-count', 160)"></textarea>
                <small>Description affichée dans les résultats Google (150-160 caractères idéal)</small>
            </div>
            
            <div class="form-group">
                <label>URL canonique (optionnel)</label>
                <input type="text" id="seo-canonical" class="form-control" 
                       placeholder="https://...">
            </div>
            
            <div class="seo-preview">
                <h4>Aperçu Google</h4>
                <div class="google-preview">
                    <div class="preview-title" id="preview-title">Titre de la page</div>
                    <div class="preview-url" id="preview-url">https://example.com/page</div>
                    <div class="preview-desc" id="preview-desc">Description de la page...</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeSEOEditor()">Annuler</button>
            <button class="btn btn-primary" onclick="saveSEO()">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<style>
.pages-seo-content .filter-bar {
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.result-count {
    color: #64748b;
    font-size: 0.9rem;
}

.score-cell {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.meta-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85rem;
    padding: 4px 8px;
    border-radius: 4px;
}

.meta-status.good { background: #d1fae5; color: #059669; }
.meta-status.warning { background: #fef3c7; color: #d97706; }
.meta-status.error { background: #fee2e2; color: #dc2626; }

.keyword-badge {
    background: #ede9fe;
    color: #7c3aed;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
}

.no-keyword {
    color: #94a3b8;
    font-style: italic;
    font-size: 0.85rem;
}

.date-text {
    color: #64748b;
    font-size: 0.85rem;
}

.never {
    color: #f59e0b;
    font-size: 0.85rem;
}

.site-badge {
    background: #dbeafe;
    color: #2563eb;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 20px;
    padding: 15px;
    background: white;
    border-radius: 8px;
}

.page-link {
    padding: 8px 16px;
    background: #f1f5f9;
    border-radius: 6px;
    color: #475569;
    text-decoration: none;
}

.page-link:hover {
    background: #3b82f6;
    color: white;
}

.page-info {
    color: #64748b;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 600px;
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

.char-count {
    font-weight: normal;
    color: #64748b;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.95rem;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #64748b;
    font-size: 0.8rem;
}

/* Google Preview */
.seo-preview {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.seo-preview h4 {
    margin: 0 0 15px;
    font-size: 0.9rem;
    color: #64748b;
}

.google-preview {
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    font-family: Arial, sans-serif;
}

.preview-title {
    color: #1a0dab;
    font-size: 1.1rem;
    margin-bottom: 3px;
    cursor: pointer;
}

.preview-title:hover {
    text-decoration: underline;
}

.preview-url {
    color: #006621;
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.preview-desc {
    color: #545454;
    font-size: 0.9rem;
    line-height: 1.4;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #64748b;
}
</style>

<script>
let searchTimeout;

function debounceSearch(value) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const url = new URL(window.location);
        url.searchParams.set('search', value);
        url.searchParams.set('p', '1');
        window.location = url;
    }, 500);
}

function filterByScore(score) {
    const url = new URL(window.location);
    if (score) {
        url.searchParams.set('score', score);
    } else {
        url.searchParams.delete('score');
    }
    url.searchParams.set('p', '1');
    window.location = url;
}

function openSEOEditor(pageId) {
    document.getElementById('seoEditorModal').style.display = 'flex';
    document.getElementById('seo-page-id').value = pageId;
    
    // Charger les données actuelles
    fetch('api/get-page-seo.php?id=' + pageId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('seo-focus-keyword').value = data.focus_keyword || '';
            document.getElementById('seo-meta-title').value = data.meta_title || '';
            document.getElementById('seo-meta-description').value = data.meta_description || '';
            document.getElementById('seo-canonical').value = data.canonical_url || '';
            
            updateCharCount(document.getElementById('seo-meta-title'), 'title-count', 60);
            updateCharCount(document.getElementById('seo-meta-description'), 'desc-count', 160);
            updatePreview();
        });
}

function closeSEOEditor() {
    document.getElementById('seoEditorModal').style.display = 'none';
}

function updateCharCount(input, countId, max) {
    const count = input.value.length;
    const elem = document.getElementById(countId);
    elem.textContent = count + '/' + max;
    elem.style.color = count > max ? '#ef4444' : (count >= max * 0.8 ? '#f59e0b' : '#64748b');
    updatePreview();
}

function updatePreview() {
    const title = document.getElementById('seo-meta-title').value || 'Titre de la page';
    const desc = document.getElementById('seo-meta-description').value || 'Description de la page...';
    
    document.getElementById('preview-title').textContent = title;
    document.getElementById('preview-desc').textContent = desc;
}

function saveSEO() {
    const pageId = document.getElementById('seo-page-id').value;
    const data = {
        id: pageId,
        focus_keyword: document.getElementById('seo-focus-keyword').value,
        meta_title: document.getElementById('seo-meta-title').value,
        meta_description: document.getElementById('seo-meta-description').value,
        canonical_url: document.getElementById('seo-canonical').value
    };
    
    fetch('api/save-page-seo.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            closeSEOEditor();
            showNotification('success', 'SEO mis à jour');
            // Relancer l'analyse
            analyzePage(pageId);
        } else {
            showNotification('error', result.error);
        }
    });
}
</script>