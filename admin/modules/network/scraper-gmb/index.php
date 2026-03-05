<?php
/**
 * Module Scraper Google My Business
 * /admin/modules/gmb/index.php
 * Prospection via Google Maps
 */

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur de connexion</div>');
}

// Stats
$stats = [
    'searches' => $pdo->query("SELECT COUNT(*) FROM gmb_searches")->fetchColumn(),
    'results' => $pdo->query("SELECT COUNT(*) FROM gmb_results")->fetchColumn(),
    'converted' => $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE is_converted=1")->fetchColumn(),
    'with_phone' => $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE phone IS NOT NULL AND phone!=''")->fetchColumn(),
    'high_rating' => $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE rating>=4")->fetchColumn(),
];

// Recherches récentes
$searches = $pdo->query("SELECT s.*, COUNT(r.id) as cnt FROM gmb_searches s LEFT JOIN gmb_results r ON s.id=r.search_id GROUP BY s.id ORDER BY s.created_at DESC LIMIT 10")->fetchAll();

// Résultats
$selectedSearch = null;
$results = [];
$searchId = (int)($_GET['search_id'] ?? 0);
$filterRating = $_GET['rating'] ?? '';
$filterPhone = $_GET['phone'] ?? '';

if ($searchId) {
    $stmt = $pdo->prepare("SELECT * FROM gmb_searches WHERE id=?");
    $stmt->execute([$searchId]);
    $selectedSearch = $stmt->fetch();
    
    if ($selectedSearch) {
        $where = ['search_id=?'];
        $params = [$searchId];
        if ($filterRating) { $where[] = "rating>=?"; $params[] = (float)$filterRating; }
        if ($filterPhone === '1') { $where[] = "phone IS NOT NULL AND phone!=''"; }
        
        $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE ".implode(' AND ', $where)." ORDER BY rating DESC, reviews_count DESC");
        $stmt->execute($params);
        $results = $stmt->fetchAll();
    }
}

$categories = ['Notaires'=>'notaire','Agences immo'=>'agence immobilière','Courtiers'=>'courtier','Banques'=>'banque','Architectes'=>'architecte','Diagnostiqueurs'=>'diagnostic immobilier','Artisans'=>'artisan bâtiment','Syndics'=>'syndic copropriété'];
?>

<style>
*{box-sizing:border-box}
.gmb-header{background:linear-gradient(135deg,#4285f4 0%,#34a853 50%,#ea4335 100%);border-radius:20px;padding:32px 40px;color:#fff;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;position:relative;overflow:hidden}
.gmb-header::before{content:'';position:absolute;top:-50%;right:-5%;width:300px;height:300px;background:rgba(255,255,255,.1);border-radius:50%}
.gmb-header h1{font-size:28px;font-weight:800;margin-bottom:8px;display:flex;align-items:center;gap:12px}
.gmb-header p{opacity:.9;font-size:14px}
.header-actions{display:flex;gap:12px;z-index:1}

.gmb-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:#fff;border-radius:16px;padding:20px;border:1px solid #e2e8f0;display:flex;align-items:center;gap:16px;transition:all .2s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px}
.stat-icon.blue{background:rgba(66,133,244,.1);color:#4285f4}
.stat-icon.green{background:rgba(52,168,83,.1);color:#34a853}
.stat-icon.red{background:rgba(234,67,53,.1);color:#ea4335}
.stat-icon.yellow{background:rgba(251,188,5,.1);color:#fbbc05}
.stat-icon.purple{background:rgba(139,92,246,.1);color:#8b5cf6}
.stat-value{font-size:28px;font-weight:800;color:#1e293b}
.stat-label{font-size:13px;color:#64748b}

.gmb-layout{display:grid;grid-template-columns:340px 1fr;gap:24px}
.sidebar-card{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden;margin-bottom:20px}
.sidebar-header{padding:16px 20px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px}
.sidebar-header i{color:#4285f4}
.sidebar-body{padding:20px}

.form-group{margin-bottom:16px}
.form-label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
.form-input,.form-select{width:100%;padding:12px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;transition:all .2s}
.form-input:focus,.form-select:focus{outline:none;border-color:#4285f4;box-shadow:0 0 0 3px rgba(66,133,244,.1)}
.input-icon{position:relative}
.input-icon i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8}
.input-icon input{padding-left:42px}

.quick-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.quick-tag{padding:6px 12px;background:#f1f5f9;border-radius:20px;font-size:11px;color:#64748b;cursor:pointer;transition:all .2s;border:none}
.quick-tag:hover{background:#4285f4;color:#fff}

.recent-list{list-style:none;padding:0;margin:0}
.recent-item{padding:12px;border-radius:10px;margin-bottom:8px;background:#f8fafc;cursor:pointer;transition:all .2s;display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:inherit}
.recent-item:hover{background:#e0e7ff}
.recent-item.active{background:#4285f4;color:#fff}
.recent-item.active .recent-meta,.recent-item.active .recent-loc{color:rgba(255,255,255,.8)}
.recent-query{font-weight:600;font-size:13px;margin-bottom:2px}
.recent-loc{font-size:11px;color:#64748b}
.recent-meta{text-align:right}
.recent-count{font-weight:700;font-size:14px}
.recent-date{font-size:10px;color:#94a3b8}

.main-panel{background:#fff;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden}
.main-header{padding:20px 24px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;background:linear-gradient(135deg,#f8fafc,#f1f5f9)}
.main-title{font-size:18px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px}
.main-title i{color:#4285f4}
.main-actions{display:flex;gap:10px}

.filters-bar{padding:14px 24px;border-bottom:1px solid #e2e8f0;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.filter-chip{padding:7px 14px;background:#f1f5f9;border-radius:20px;font-size:12px;color:#64748b;cursor:pointer;transition:all .2s;border:none;display:flex;align-items:center;gap:6px}
.filter-chip:hover,.filter-chip.active{background:#4285f4;color:#fff}
.filter-chip i{font-size:10px}
.search-in{padding:8px 14px;border:1px solid #e2e8f0;border-radius:20px;font-size:12px;width:200px;margin-left:auto}
.search-in:focus{outline:none;border-color:#4285f4}

.results-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;padding:20px 24px;max-height:calc(100vh - 400px);overflow-y:auto}

.result-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;transition:all .2s;position:relative}
.result-card:hover{border-color:#4285f4;box-shadow:0 4px 12px rgba(66,133,244,.15)}
.result-card.converted{border-color:#34a853;background:linear-gradient(135deg,#f0fdf4,#dcfce7)}
.result-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.result-name{font-weight:700;font-size:15px;color:#1e293b;margin-bottom:4px;line-height:1.3;max-width:200px}
.result-category{font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px}
.result-category i{font-size:9px;color:#94a3b8}
.result-rating{display:flex;align-items:center;gap:4px;background:#fef3c7;padding:4px 10px;border-radius:20px}
.result-rating .star{color:#f59e0b;font-size:11px}
.result-rating .score{font-weight:700;font-size:12px;color:#92400e}
.result-rating .cnt{font-size:10px;color:#a16207}

.result-info{display:flex;flex-direction:column;gap:6px;margin-bottom:14px}
.result-info-item{display:flex;align-items:flex-start;gap:8px;font-size:12px}
.result-info-item i{width:14px;color:#94a3b8;margin-top:2px;font-size:11px}
.result-info-item a{color:#4285f4;text-decoration:none}
.result-info-item a:hover{text-decoration:underline}

.result-actions{display:flex;gap:8px;padding-top:14px;border-top:1px solid #e2e8f0}
.result-btn{flex:1;padding:8px 10px;border:none;border-radius:8px;font-size:11px;font-weight:600;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:5px;text-decoration:none}
.result-btn.primary{background:#4285f4;color:#fff}
.result-btn.primary:hover{background:#3367d6}
.result-btn.success{background:#d1fae5;color:#059669}
.result-btn.secondary{background:#f1f5f9;color:#64748b}
.result-btn.secondary:hover{background:#e2e8f0}

.result-checkbox{position:absolute;top:14px;right:14px}
.result-checkbox input{width:16px;height:16px;cursor:pointer;accent-color:#4285f4}

.empty-state{text-align:center;padding:60px 20px;color:#94a3b8}
.empty-state i{font-size:64px;margin-bottom:20px;opacity:.3}
.empty-state h3{font-size:20px;color:#64748b;margin-bottom:10px}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:all .2s;text-decoration:none}
.btn-google{background:linear-gradient(135deg,#4285f4,#34a853);color:#fff}
.btn-google:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(66,133,244,.3)}
.btn-white{background:#fff;color:#4285f4}
.btn-secondary{background:#f1f5f9;color:#64748b}
.btn-secondary:hover{background:#e2e8f0}
.btn-sm{padding:8px 14px;font-size:12px}

.modal-overlay{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;backdrop-filter:blur(4px)}
.modal-overlay.active{display:flex}
.modal{background:#fff;border-radius:20px;width:100%;max-width:500px;max-height:90vh;overflow:hidden;box-shadow:0 25px 80px rgba(0,0,0,.3)}
.modal-header{padding:24px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(135deg,#f8fafc,#f1f5f9)}
.modal-title{font-size:18px;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px}
.modal-title i{color:#4285f4}
.modal-close{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#fff;color:#64748b;border:1px solid #e2e8f0;cursor:pointer;font-size:16px}
.modal-close:hover{background:#ef4444;color:#fff;border-color:#ef4444}
.modal-body{padding:24px}
.modal-footer{padding:20px 24px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:12px;background:#f8fafc}

.progress-box{text-align:center;padding:30px}
.spinner{width:50px;height:50px;border:4px solid #e2e8f0;border-top-color:#4285f4;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 16px}
@keyframes spin{to{transform:rotate(360deg)}}
.progress-text{font-size:16px;font-weight:600;color:#1e293b}
.progress-sub{font-size:13px;color:#64748b;margin-top:6px}

@media(max-width:1200px){.gmb-stats{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1024px){.gmb-layout{grid-template-columns:1fr}.gmb-header{flex-direction:column;text-align:center;gap:20px}}
@media(max-width:768px){.gmb-stats{grid-template-columns:1fr 1fr}.results-grid{grid-template-columns:1fr}}
</style>

<!-- Header -->
<div class="gmb-header">
    <div>
        <h1><i class="fab fa-google"></i> Scraper Google My Business</h1>
        <p>Trouvez des entreprises sur Google Maps pour votre prospection immobilière</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-white" onclick="openSettingsModal()"><i class="fas fa-cog"></i> Paramètres</button>
        <button class="btn btn-google" onclick="openSearchModal()" style="background:#fff;color:#4285f4"><i class="fas fa-search"></i> Nouvelle Recherche</button>
    </div>
</div>

<!-- Stats -->
<div class="gmb-stats">
    <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-search"></i></div><div><div class="stat-value"><?= $stats['searches'] ?></div><div class="stat-label">Recherches</div></div></div>
    <div class="stat-card"><div class="stat-icon green"><i class="fas fa-building"></i></div><div><div class="stat-value"><?= $stats['results'] ?></div><div class="stat-label">Entreprises</div></div></div>
    <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-user-plus"></i></div><div><div class="stat-value"><?= $stats['converted'] ?></div><div class="stat-label">Convertis</div></div></div>
    <div class="stat-card"><div class="stat-icon yellow"><i class="fas fa-phone"></i></div><div><div class="stat-value"><?= $stats['with_phone'] ?></div><div class="stat-label">Avec téléphone</div></div></div>
    <div class="stat-card"><div class="stat-icon red"><i class="fas fa-star"></i></div><div><div class="stat-value"><?= $stats['high_rating'] ?></div><div class="stat-label">Note ≥ 4.0</div></div></div>
</div>

<!-- Layout -->
<div class="gmb-layout">
    <!-- Sidebar -->
    <div>
        <!-- Quick Search -->
        <div class="sidebar-card">
            <div class="sidebar-header"><i class="fas fa-bolt"></i> Recherche Rapide</div>
            <div class="sidebar-body">
                <form id="quickSearchForm">
                    <div class="form-group">
                        <label class="form-label">Activité</label>
                        <div class="input-icon">
                            <i class="fas fa-briefcase"></i>
                            <input type="text" class="form-input" id="qQuery" placeholder="ex: notaire, banque...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ville</label>
                        <div class="input-icon">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" class="form-input" id="qLocation" placeholder="ex: Bordeaux, 33000...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Rayon</label>
                        <select class="form-select" id="qRadius">
                            <option value="2000">2 km</option>
                            <option value="5000" selected>5 km</option>
                            <option value="10000">10 km</option>
                            <option value="20000">20 km</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-google" style="width:100%"><i class="fab fa-google"></i> Rechercher</button>
                </form>
                <div class="quick-tags">
                    <?php foreach($categories as $l=>$v): ?><button class="quick-tag" onclick="setQuery('<?=$v?>')"><?=$l?></button><?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent -->
        <div class="sidebar-card">
            <div class="sidebar-header"><i class="fas fa-history"></i> Historique</div>
            <div class="sidebar-body">
                <?php if(empty($searches)): ?>
                    <p style="color:#94a3b8;font-size:13px;text-align:center">Aucune recherche</p>
                <?php else: ?>
                    <ul class="recent-list">
                        <?php foreach($searches as $s): ?>
                            <a href="?page=gmb&search_id=<?=$s['id']?>" class="recent-item <?=($selectedSearch && $selectedSearch['id']==$s['id'])?'active':''?>">
                                <div>
                                    <div class="recent-query"><?=htmlspecialchars($s['query'])?></div>
                                    <div class="recent-loc"><i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($s['location'])?></div>
                                </div>
                                <div class="recent-meta">
                                    <div class="recent-count"><?=$s['cnt']?></div>
                                    <div class="recent-date"><?=date('d/m',strtotime($s['created_at']))?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Main -->
    <div class="main-panel">
        <?php if($selectedSearch && !empty($results)): ?>
            <div class="main-header">
                <div class="main-title">
                    <i class="fas fa-building"></i>
                    <?=htmlspecialchars($selectedSearch['query'])?> - <?=htmlspecialchars($selectedSearch['location'])?>
                    <span style="font-weight:400;color:#64748b;font-size:14px">(<?=count($results)?> résultats)</span>
                </div>
                <div class="main-actions">
                    <button class="btn btn-secondary btn-sm" onclick="selectAll()"><i class="fas fa-check-double"></i> Tout sélectionner</button>
                    <button class="btn btn-secondary btn-sm" onclick="exportCSV()"><i class="fas fa-download"></i> CSV</button>
                    <button class="btn btn-google btn-sm" onclick="convertSelected()"><i class="fas fa-user-plus"></i> Convertir</button>
                </div>
            </div>
            
            <div class="filters-bar">
                <span style="font-size:12px;color:#64748b">Filtres:</span>
                <button class="filter-chip <?=$filterPhone==='1'?'active':''?>" onclick="toggleFilter('phone','1')"><i class="fas fa-phone"></i> Avec téléphone</button>
                <button class="filter-chip <?=$filterRating==='4'?'active':''?>" onclick="toggleFilter('rating','4')"><i class="fas fa-star"></i> ≥ 4.0</button>
                <button class="filter-chip <?=$filterRating==='4.5'?'active':''?>" onclick="toggleFilter('rating','4.5')"><i class="fas fa-star"></i> ≥ 4.5</button>
                <input type="text" class="search-in" placeholder="Rechercher..." onkeyup="if(event.key==='Enter')filterQ(this.value)">
            </div>
            
            <div class="results-grid">
                <?php foreach($results as $r): ?>
                    <div class="result-card <?=$r['is_converted']?'converted':''?>" data-id="<?=$r['id']?>">
                        <div class="result-checkbox"><input type="checkbox" class="rcheck" value="<?=$r['id']?>"></div>
                        <div class="result-header">
                            <div>
                                <div class="result-name"><?=htmlspecialchars($r['name'])?></div>
                                <div class="result-category"><i class="fas fa-tag"></i> <?=htmlspecialchars($r['category']?:'Non catégorisé')?></div>
                            </div>
                            <?php if($r['rating']): ?>
                                <div class="result-rating">
                                    <span class="star"><i class="fas fa-star"></i></span>
                                    <span class="score"><?=number_format($r['rating'],1)?></span>
                                    <span class="cnt">(<?=$r['reviews_count']?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="result-info">
                            <?php if($r['address']): ?><div class="result-info-item"><i class="fas fa-map-marker-alt"></i><span><?=htmlspecialchars($r['address'])?></span></div><?php endif; ?>
                            <?php if($r['phone']): ?><div class="result-info-item"><i class="fas fa-phone"></i><a href="tel:<?=htmlspecialchars($r['phone'])?>"><?=htmlspecialchars($r['phone'])?></a></div><?php endif; ?>
                            <?php if($r['website']): ?><div class="result-info-item"><i class="fas fa-globe"></i><a href="<?=htmlspecialchars($r['website'])?>" target="_blank">Site web</a></div><?php endif; ?>
                        </div>
                        <div class="result-actions">
                            <?php if($r['is_converted']): ?>
                                <button class="result-btn success" disabled><i class="fas fa-check"></i> Converti</button>
                            <?php else: ?>
                                <button class="result-btn primary" onclick="convertOne(<?=$r['id']?>)"><i class="fas fa-user-plus"></i> Lead</button>
                            <?php endif; ?>
                            <button class="result-btn secondary" onclick="viewDetails(<?=$r['id']?>)"><i class="fas fa-eye"></i></button>
                            <?php if($r['phone']): ?><a href="tel:<?=htmlspecialchars($r['phone'])?>" class="result-btn secondary"><i class="fas fa-phone"></i></a><?php endif; ?>
                            <?php if($r['latitude']): ?><a href="https://maps.google.com/?q=<?=$r['latitude']?>,<?=$r['longitude']?>" target="_blank" class="result-btn secondary"><i class="fas fa-map"></i></a><?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif($selectedSearch): ?>
            <div class="empty-state"><i class="fas fa-search"></i><h3>Aucun résultat</h3><p>Cette recherche n'a retourné aucun résultat</p></div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fab fa-google"></i>
                <h3>Prospection Google My Business</h3>
                <p>Lancez une recherche pour trouver des entreprises sur Google Maps</p>
                <button class="btn btn-google" onclick="openSearchModal()"><i class="fas fa-search"></i> Nouvelle Recherche</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Search -->
<div class="modal-overlay" id="searchModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fab fa-google"></i> Nouvelle Recherche GMB</h3>
            <button class="modal-close" onclick="closeModal('searchModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="searchForm">
                <div class="form-group">
                    <label class="form-label">Activité / Mot-clé *</label>
                    <input type="text" class="form-input" id="mQuery" required placeholder="ex: notaire, agence immobilière...">
                </div>
                <div class="form-group">
                    <label class="form-label">Ville / Localisation *</label>
                    <input type="text" class="form-input" id="mLocation" required placeholder="ex: Bordeaux, Lyon 69001...">
                </div>
                <div class="form-group">
                    <label class="form-label">Rayon</label>
                    <select class="form-select" id="mRadius">
                        <option value="2000">2 km</option>
                        <option value="5000" selected>5 km</option>
                        <option value="10000">10 km</option>
                        <option value="20000">20 km</option>
                        <option value="50000">50 km</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre max de résultats</label>
                    <select class="form-select" id="mLimit">
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('searchModal')">Annuler</button>
            <button class="btn btn-google" onclick="startSearch()"><i class="fab fa-google"></i> Lancer</button>
        </div>
    </div>
</div>

<!-- Modal Progress -->
<div class="modal-overlay" id="progressModal">
    <div class="modal" style="max-width:380px">
        <div class="modal-body">
            <div class="progress-box">
                <div class="spinner"></div>
                <div class="progress-text">Recherche en cours...</div>
                <div class="progress-sub" id="progressSub">Connexion à Google Maps</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Details -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fas fa-building"></i> Détails</h3>
            <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
        </div>
        <div class="modal-body" id="detailsContent"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('detailsModal')">Fermer</button>
            <button class="btn btn-google" id="detailsConvertBtn"><i class="fas fa-user-plus"></i> Convertir</button>
        </div>
    </div>
</div>

<script>
const API = '/admin/modules/gmb/api.php';

function setQuery(q) { document.getElementById('qQuery').value = q; }

document.getElementById('quickSearchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    doSearch(document.getElementById('qQuery').value, document.getElementById('qLocation').value, document.getElementById('qRadius').value, 50);
});

function openSearchModal() { document.getElementById('searchModal').classList.add('active'); }
function openSettingsModal() { alert('Configurez votre clé API SerpAPI dans les paramètres système'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

function startSearch() {
    const q = document.getElementById('mQuery').value;
    const l = document.getElementById('mLocation').value;
    const r = document.getElementById('mRadius').value;
    const lim = document.getElementById('mLimit').value;
    if (!q || !l) { alert('Remplissez tous les champs'); return; }
    closeModal('searchModal');
    doSearch(q, l, r, lim);
}

function doSearch(query, location, radius, limit) {
    document.getElementById('progressModal').classList.add('active');
    document.getElementById('progressSub').textContent = 'Connexion à Google Maps...';
    
    const fd = new FormData();
    fd.append('action', 'search');
    fd.append('query', query);
    fd.append('location', location);
    fd.append('radius', radius);
    fd.append('limit', limit);
    
    fetch(API, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        closeModal('progressModal');
        if (data.success) {
            showNotif(`${data.count} entreprises trouvées !`, 'success');
            window.location.href = '?page=gmb&search_id=' + data.search_id;
        } else {
            showNotif('Erreur: ' + (data.error || 'Inconnue'), 'error');
        }
    })
    .catch(() => { closeModal('progressModal'); showNotif('Erreur connexion', 'error'); });
}

function toggleFilter(name, val) {
    const url = new URL(location.href);
    url.searchParams.get(name) === val ? url.searchParams.delete(name) : url.searchParams.set(name, val);
    location.href = url.toString();
}

function filterQ(q) {
    const url = new URL(location.href);
    q ? url.searchParams.set('q', q) : url.searchParams.delete('q');
    location.href = url.toString();
}

function selectAll() {
    const cbs = document.querySelectorAll('.rcheck');
    const allChecked = Array.from(cbs).every(c => c.checked);
    cbs.forEach(c => c.checked = !allChecked);
}

function exportCSV() {
    const ids = Array.from(document.querySelectorAll('.rcheck:checked')).map(c => c.value);
    if (!ids.length) { alert('Sélectionnez des entreprises'); return; }
    location.href = API + '?action=export&ids=' + ids.join(',');
}

function convertOne(id) {
    if (!confirm('Convertir en lead ?')) return;
    const fd = new FormData();
    fd.append('action', 'convert');
    fd.append('id', id);
    fetch(API, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showNotif('Lead créé !', 'success'); setTimeout(() => location.reload(), 500); }
        else showNotif('Erreur', 'error');
    });
}

function convertSelected() {
    const ids = Array.from(document.querySelectorAll('.rcheck:checked')).map(c => c.value);
    if (!ids.length) { alert('Sélectionnez des entreprises'); return; }
    if (!confirm(`Convertir ${ids.length} entreprise(s) en leads ?`)) return;
    
    const fd = new FormData();
    fd.append('action', 'convert_bulk');
    fd.append('ids', JSON.stringify(ids));
    fetch(API, { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) { showNotif(`${data.converted} leads créés !`, 'success'); setTimeout(() => location.reload(), 500); }
        else showNotif('Erreur', 'error');
    });
}

function viewDetails(id) {
    fetch(API + '?action=get&id=' + id)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const r = data.result;
            document.getElementById('detailsContent').innerHTML = `
                <h3 style="font-size:20px;font-weight:700;margin-bottom:8px">${r.name}</h3>
                ${r.category ? `<p style="color:#64748b;margin-bottom:16px"><i class="fas fa-tag"></i> ${r.category}</p>` : ''}
                ${r.rating ? `<div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;padding:10px;background:#fef3c7;border-radius:8px"><i class="fas fa-star" style="color:#f59e0b"></i><strong>${parseFloat(r.rating).toFixed(1)}</strong><span>(${r.reviews_count} avis)</span></div>` : ''}
                <div style="display:flex;flex-direction:column;gap:10px">
                    ${r.address ? `<div><i class="fas fa-map-marker-alt" style="color:#ea4335;width:20px"></i> ${r.address}</div>` : ''}
                    ${r.phone ? `<div><i class="fas fa-phone" style="color:#34a853;width:20px"></i> <a href="tel:${r.phone}">${r.phone}</a></div>` : ''}
                    ${r.website ? `<div><i class="fas fa-globe" style="color:#4285f4;width:20px"></i> <a href="${r.website}" target="_blank">Voir le site</a></div>` : ''}
                </div>
                ${r.latitude ? `<a href="https://maps.google.com/?q=${r.latitude},${r.longitude}" target="_blank" class="btn btn-secondary" style="width:100%;margin-top:16px"><i class="fas fa-map"></i> Voir sur Google Maps</a>` : ''}
            `;
            document.getElementById('detailsConvertBtn').onclick = () => { closeModal('detailsModal'); convertOne(id); };
            document.getElementById('detailsConvertBtn').style.display = r.is_converted ? 'none' : 'flex';
            document.getElementById('detailsModal').classList.add('active');
        }
    });
}

function showNotif(msg, type = 'info') {
    const colors = { success: '#34a853', error: '#ea4335', info: '#4285f4' };
    const n = document.createElement('div');
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 20px;background:${colors[type]};color:#fff;border-radius:10px;font-size:14px;font-weight:500;z-index:99999;box-shadow:0 4px 12px rgba(0,0,0,.2)`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => { n.style.opacity = '0'; setTimeout(() => n.remove(), 300); }, 3000);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active')); });
document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); }));
</script>