<?php
/**
 * ══════════════════════════════════════════════════════════════════════
 * MODULE LEADS — Gestion complète des prospects
 * /admin/modules/leads/index.php
 * ✅ Migré vers admin-components.css — CSS inline supprimé (~400 lignes)
 *    Seul un mini-bloc de ~50 lignes reste pour les éléments spécifiques
 *    au module leads (avatar, score, température, select filtres)
 * ══════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════
// CONNEXION BDD (héritée du dashboard ou standalone)
// ═══════════════════════════════════════
if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        die('<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> Erreur de connexion: ' . $e->getMessage() . '</div>');
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;

// ═══════════════════════════════════════
// TABLE AUTO-CREATE
// ═══════════════════════════════════════
$pdo->exec("CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    source ENUM('site_web','telephone','recommandation','salon','pub_facebook','pub_google','flyer','boitage','autre') DEFAULT 'site_web',
    type ENUM('vendeur','acheteur','investisseur','locataire','bailleur','autre') DEFAULT 'vendeur',
    status ENUM('new','contacted','qualified','proposal','negotiation','won','lost') DEFAULT 'new',
    temperature ENUM('cold','warm','hot') DEFAULT 'warm',
    score INT DEFAULT 0,
    budget_min INT DEFAULT NULL,
    budget_max INT DEFAULT NULL,
    property_type VARCHAR(100) DEFAULT NULL,
    surface_min INT DEFAULT NULL,
    surface_max INT DEFAULT NULL,
    rooms_min INT DEFAULT NULL,
    bedrooms_min INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    tags VARCHAR(255) DEFAULT NULL,
    assigned_to INT DEFAULT NULL,
    last_contact DATE DEFAULT NULL,
    next_action VARCHAR(255) DEFAULT NULL,
    next_action_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status), INDEX idx_type (type),
    INDEX idx_temperature (temperature), INDEX idx_source (source),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ═══════════════════════════════════════
// FILTRES & PAGINATION
// ═══════════════════════════════════════
$search       = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterType   = $_GET['type'] ?? '';
$filterSource = $_GET['source'] ?? '';
$filterTemp   = $_GET['temperature'] ?? '';
$sortBy       = $_GET['sort'] ?? 'created_at';
$sortOrder    = $_GET['order'] ?? 'DESC';
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 20;
$offset       = ($page - 1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)";
    $t = "%{$search}%";
    $params = array_merge($params, [$t, $t, $t, $t, $t]);
}
if ($filterStatus)  { $where[] = "status = ?";      $params[] = $filterStatus; }
if ($filterType)    { $where[] = "type = ?";         $params[] = $filterType; }
if ($filterSource)  { $where[] = "source = ?";       $params[] = $filterSource; }
if ($filterTemp)    { $where[] = "temperature = ?";  $params[] = $filterTemp; }

$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE {$whereClause}");
$countStmt->execute($params);
$totalLeads = $countStmt->fetchColumn();
$totalPages = ceil($totalLeads / $perPage);

$allowedSorts = ['created_at','lastname','score','status','temperature','last_contact'];
$sortBy    = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
$sortOrder = $sortOrder === 'ASC' ? 'ASC' : 'DESC';

$stmt = $pdo->prepare("SELECT * FROM leads WHERE {$whereClause} ORDER BY {$sortBy} {$sortOrder} LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$leads = $stmt->fetchAll();

// ═══════════════════════════════════════
// STATISTIQUES
// ═══════════════════════════════════════
$stats = [
    'total'      => $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
    'new'        => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn(),
    'hot'        => $pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'hot'")->fetchColumn(),
    'won'        => $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'won'")->fetchColumn(),
    'this_month' => $pdo->query("SELECT COUNT(*) FROM leads WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn(),
];

// ═══════════════════════════════════════
// LABELS / CONFIG
// ═══════════════════════════════════════
$statusLabels = [
    'new'         => ['label' => 'Nouveau',     'color' => '#6366f1', 'bg' => '#e0e7ff'],
    'contacted'   => ['label' => 'Contacté',    'color' => '#0891b2', 'bg' => '#cffafe'],
    'qualified'   => ['label' => 'Qualifié',    'color' => '#7c3aed', 'bg' => '#ede9fe'],
    'proposal'    => ['label' => 'Proposition',  'color' => '#f59e0b', 'bg' => '#fef3c7'],
    'negotiation' => ['label' => 'Négociation', 'color' => '#ec4899', 'bg' => '#fce7f3'],
    'won'         => ['label' => 'Gagné',       'color' => '#10b981', 'bg' => '#d1fae5'],
    'lost'        => ['label' => 'Perdu',       'color' => '#64748b', 'bg' => '#f1f5f9'],
];
$typeLabels = [
    'vendeur'      => ['label' => 'Vendeur',      'icon' => 'tag',          'color' => '#6366f1'],
    'acheteur'     => ['label' => 'Acheteur',     'icon' => 'shopping-cart', 'color' => '#10b981'],
    'investisseur' => ['label' => 'Investisseur', 'icon' => 'chart-line',   'color' => '#f59e0b'],
    'locataire'    => ['label' => 'Locataire',    'icon' => 'key',          'color' => '#06b6d4'],
    'bailleur'     => ['label' => 'Bailleur',     'icon' => 'building',     'color' => '#8b5cf6'],
    'autre'        => ['label' => 'Autre',        'icon' => 'user',         'color' => '#64748b'],
];
$sourceLabels = [
    'site_web' => 'Site web', 'telephone' => 'Téléphone', 'recommandation' => 'Recommandation',
    'salon' => 'Salon/Événement', 'pub_facebook' => 'Pub Facebook', 'pub_google' => 'Pub Google',
    'flyer' => 'Flyer', 'boitage' => 'Boîtage', 'autre' => 'Autre',
];
$tempLabels = [
    'cold' => ['label' => 'Froid', 'color' => '#0ea5e9', 'icon' => 'snowflake'],
    'warm' => ['label' => 'Tiède', 'color' => '#f59e0b', 'icon' => 'sun'],
    'hot'  => ['label' => 'Chaud', 'color' => '#ef4444', 'icon' => 'fire-alt'],
];

// Helper: query string pour tri/pagination
function leadsSortUrl($col, $curSort, $curOrder, $extra = []) {
    $order = ($curSort === $col && $curOrder === 'DESC') ? 'ASC' : 'DESC';
    if ($col === 'lastname') $order = ($curSort === 'lastname' && $curOrder === 'ASC') ? 'DESC' : 'ASC';
    return '?page=leads&sort=' . $col . '&order=' . $order . '&' . http_build_query(array_filter($extra));
}
$sortIcon = function($col) use ($sortBy, $sortOrder) {
    if ($sortBy !== $col) return '';
    return ' <i class="fas fa-sort-' . ($sortOrder === 'ASC' ? 'up' : 'down') . '"></i>';
};
$paginationQS = array_filter(['search' => $search, 'status' => $filterStatus, 'type' => $filterType, 'sort' => $sortBy, 'order' => $sortOrder]);
?>

<!-- ═══════════════════════════════════════
     CSS COMPLÉMENTAIRE — Éléments spécifiques Leads
     (~50 lignes au lieu de ~400)
     ═══════════════════════════════════════ -->
<style>
/* Avatar lead */
.lead-avatar{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0}
/* Badges spécifiques */
.lead-status-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:.7rem;font-weight:600}
.lead-temp-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:600}
.lead-temp-badge.cold{background:#e0f2fe;color:#0369a1}
.lead-temp-badge.warm{background:#fef3c7;color:#b45309}
.lead-temp-badge.hot{background:#fee2e2;color:#dc2626}
.lead-score{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:26px;border-radius:8px;font-size:.8rem;font-weight:700}
.lead-score.high{background:#dcfce7;color:#16a34a}
.lead-score.medium{background:#fef3c7;color:#d97706}
.lead-score.low{background:var(--surface-2);color:var(--text-3)}
/* Filtres select */
.lead-select{padding:10px 32px 10px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:.8rem;background:var(--surface);cursor:pointer;min-width:130px;appearance:none;font-family:var(--font);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2394a3b8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center}
.lead-select:focus{outline:0;border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,70,229,.1)}
/* Contact cell */
.lead-contact a{color:var(--accent);text-decoration:none;font-size:.8rem}
.lead-contact a:hover{text-decoration:underline}
.lead-contact-phone{display:flex;align-items:center;gap:5px;color:var(--text-3);margin-top:3px;font-size:.75rem}
.lead-contact-phone i{font-size:.65rem}
/* Checkbox */
.lead-cb{width:40px}.lead-cb input{width:16px;height:16px;cursor:pointer;accent-color:var(--accent)}
/* Stats row inline */
.lead-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px}
.lead-stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:18px;display:flex;align-items:center;gap:14px;transition:all .2s}
.lead-stat-card:hover{transform:translateY(-2px);box-shadow:var(--shadow)}
.lead-stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px}
.lead-stat-val{font-family:var(--font-display);font-size:26px;font-weight:700;color:var(--text)}
.lead-stat-label{font-size:.78rem;color:var(--text-3)}
/* Form sections modal */
.lead-form-section{margin-bottom:22px}
.lead-form-section-title{font-size:.85rem;font-weight:700;color:var(--text);margin-bottom:14px;padding-bottom:6px;border-bottom:2px solid var(--border);display:flex;align-items:center;gap:8px}
.lead-form-section-title i{color:var(--accent)}
.lead-form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.lead-form-row-3{grid-template-columns:1fr 1fr 1fr}
/* Pagination wrapper */
.lead-pag-wrap{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-top:1px solid var(--border)}
.lead-pag-info{font-size:.78rem;color:var(--text-3)}
/* Responsive */
@media(max-width:1200px){.lead-stats{grid-template-columns:repeat(3,1fr)}}
@media(max-width:768px){.lead-stats{grid-template-columns:1fr 1fr}.lead-form-row,.lead-form-row-3{grid-template-columns:1fr}}
</style>

<!-- ═══ HERO ═══ -->
<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-users"></i> Gestion des Leads</h1>
        <p>Centralisez et gérez tous vos prospects immobiliers</p>
    </div>
    <div class="mod-hero-actions">
        <button class="mod-btn mod-btn-hero" onclick="exportLeads()"><i class="fas fa-download"></i> Exporter</button>
        <button class="mod-btn mod-btn-hero" onclick="openImportModal()"><i class="fas fa-upload"></i> Importer</button>
        <button class="mod-btn mod-btn-hero" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau Lead</button>
    </div>
</div>

<!-- ═══ STATS ═══ -->
<div class="lead-stats">
    <div class="lead-stat-card">
        <div class="lead-stat-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-users"></i></div>
        <div><div class="lead-stat-val"><?= $stats['total'] ?></div><div class="lead-stat-label">Total leads</div></div>
    </div>
    <div class="lead-stat-card">
        <div class="lead-stat-icon" style="background:var(--blue-bg);color:var(--blue)"><i class="fas fa-user-plus"></i></div>
        <div><div class="lead-stat-val"><?= $stats['new'] ?></div><div class="lead-stat-label">Nouveaux</div></div>
    </div>
    <div class="lead-stat-card">
        <div class="lead-stat-icon" style="background:var(--red-bg);color:var(--red)"><i class="fas fa-fire"></i></div>
        <div><div class="lead-stat-val"><?= $stats['hot'] ?></div><div class="lead-stat-label">Leads chauds</div></div>
    </div>
    <div class="lead-stat-card">
        <div class="lead-stat-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-trophy"></i></div>
        <div><div class="lead-stat-val"><?= $stats['won'] ?></div><div class="lead-stat-label">Convertis</div></div>
    </div>
    <div class="lead-stat-card">
        <div class="lead-stat-icon" style="background:var(--amber-bg);color:var(--amber)"><i class="fas fa-calendar"></i></div>
        <div><div class="lead-stat-val"><?= $stats['this_month'] ?></div><div class="lead-stat-label">Ce mois</div></div>
    </div>
</div>

<!-- ═══ FILTRES ═══ -->
<div class="mod-toolbar">
    <form class="mod-flex mod-wrap mod-gap mod-items-center" method="GET" action="" style="width:100%">
        <input type="hidden" name="page" value="leads">
        <div class="mod-search" style="flex:1;min-width:220px">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher par nom, email, téléphone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="lead-select" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <?php foreach ($statusLabels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="lead-select" onchange="this.form.submit()">
            <option value="">Tous les types</option>
            <?php foreach ($typeLabels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="temperature" class="lead-select" onchange="this.form.submit()">
            <option value="">Température</option>
            <?php foreach ($tempLabels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filterTemp === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="source" class="lead-select" onchange="this.form.submit()">
            <option value="">Toutes sources</option>
            <?php foreach ($sourceLabels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filterSource === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($search || $filterStatus || $filterType || $filterTemp || $filterSource): ?>
        <a href="?page=leads" class="mod-btn mod-btn-secondary mod-btn-sm"><i class="fas fa-times"></i> Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- ═══ TABLE ═══ -->
<div class="mod-table-wrap">
    <?php if (empty($leads)): ?>
    <div class="mod-empty">
        <i class="fas fa-user-slash"></i>
        <h3>Aucun lead trouvé</h3>
        <p>Ajoutez votre premier lead ou modifiez vos filtres</p>
        <button class="mod-btn mod-btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Ajouter un lead</button>
    </div>
    <?php else: ?>
    <table class="mod-table">
        <thead>
            <tr>
                <th class="lead-cb"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                <th><a href="<?= leadsSortUrl('lastname', $sortBy, $sortOrder, $paginationQS) ?>">Lead<?= $sortIcon('lastname') ?></a></th>
                <th>Contact</th>
                <th>Type</th>
                <th><a href="<?= leadsSortUrl('status', $sortBy, $sortOrder, $paginationQS) ?>">Statut<?= $sortIcon('status') ?></a></th>
                <th>Température</th>
                <th><a href="<?= leadsSortUrl('score', $sortBy, $sortOrder, $paginationQS) ?>">Score<?= $sortIcon('score') ?></a></th>
                <th>Source</th>
                <th><a href="<?= leadsSortUrl('created_at', $sortBy, $sortOrder, $paginationQS) ?>">Date<?= $sortIcon('created_at') ?></a></th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $lead):
                $si = $statusLabels[$lead['status']]      ?? $statusLabels['new'];
                $ti = $typeLabels[$lead['type']]           ?? $typeLabels['autre'];
                $te = $tempLabels[$lead['temperature']]    ?? $tempLabels['warm'];
                $initials   = strtoupper(substr($lead['firstname'],0,1) . substr($lead['lastname'],0,1));
                $scoreClass = $lead['score'] >= 70 ? 'high' : ($lead['score'] >= 40 ? 'medium' : 'low');
            ?>
            <tr data-id="<?= $lead['id'] ?>">
                <td class="lead-cb"><input type="checkbox" class="lead-checkbox" value="<?= $lead['id'] ?>"></td>
                <td>
                    <div class="mod-flex mod-items-center mod-gap-sm">
                        <div class="lead-avatar" style="background:<?= $ti['color'] ?>"><?= $initials ?></div>
                        <div>
                            <strong style="font-weight:600;color:var(--text)"><?= htmlspecialchars($lead['firstname'] . ' ' . $lead['lastname']) ?></strong>
                            <div class="mod-text-xs mod-text-muted"><?= htmlspecialchars($lead['city'] ?? '') ?></div>
                        </div>
                    </div>
                </td>
                <td class="lead-contact">
                    <?php if ($lead['email']): ?><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a><?php endif; ?>
                    <?php if ($lead['phone']): ?><div class="lead-contact-phone"><i class="fas fa-phone"></i> <a href="tel:<?= htmlspecialchars($lead['phone']) ?>"><?= htmlspecialchars($lead['phone']) ?></a></div><?php endif; ?>
                </td>
                <td><span class="mod-tag"><i class="fas fa-<?= $ti['icon'] ?>"></i> <?= $ti['label'] ?></span></td>
                <td><span class="lead-status-badge" style="background:<?= $si['bg'] ?>;color:<?= $si['color'] ?>"><?= $si['label'] ?></span></td>
                <td><span class="lead-temp-badge <?= $lead['temperature'] ?>"><i class="fas fa-<?= $te['icon'] ?>"></i> <?= $te['label'] ?></span></td>
                <td><span class="lead-score <?= $scoreClass ?>"><?= $lead['score'] ?></span></td>
                <td><span class="mod-text-xs mod-text-muted"><?= $sourceLabels[$lead['source']] ?? $lead['source'] ?></span></td>
                <td>
                    <div class="mod-date"><?= date('d/m/Y', strtotime($lead['created_at'])) ?></div>
                    <div class="mod-text-xs mod-text-muted"><?= date('H:i', strtotime($lead['created_at'])) ?></div>
                </td>
                <td class="col-actions">
                    <div class="mod-actions">
                        <button class="mod-btn-icon" onclick="viewLead(<?= $lead['id'] ?>)" title="Voir"><i class="fas fa-eye"></i></button>
                        <button class="mod-btn-icon" onclick="editLead(<?= $lead['id'] ?>)" title="Modifier"><i class="fas fa-edit"></i></button>
                        <?php if ($lead['phone']): ?><a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="mod-btn-icon success" title="Appeler"><i class="fas fa-phone"></i></a><?php endif; ?>
                        <button class="mod-btn-icon danger" onclick="deleteLead(<?= $lead['id'] ?>)" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="lead-pag-wrap">
        <div class="lead-pag-info">
            Affichage de <?= min($offset + 1, $totalLeads) ?> à <?= min($offset + $perPage, $totalLeads) ?> sur <?= $totalLeads ?> leads
        </div>
        <div class="mod-pagination">
            <?php if ($page > 1): ?>
            <a href="?page=leads&p=1&<?= http_build_query($paginationQS) ?>"><i class="fas fa-angle-double-left"></i></a>
            <a href="?page=leads&p=<?= $page - 1 ?>&<?= http_build_query($paginationQS) ?>"><i class="fas fa-angle-left"></i></a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
                <?php else: ?>
                <a href="?page=leads&p=<?= $i ?>&<?= http_build_query($paginationQS) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=leads&p=<?= $page + 1 ?>&<?= http_build_query($paginationQS) ?>"><i class="fas fa-angle-right"></i></a>
            <a href="?page=leads&p=<?= $totalPages ?>&<?= http_build_query($paginationQS) ?>"><i class="fas fa-angle-double-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ MODAL — AJOUTER / MODIFIER LEAD ═══ -->
<div class="mod-overlay" id="leadModal">
    <div class="mod-modal" style="max-width:700px">
        <div class="mod-modal-header">
            <h3><i class="fas fa-user-plus"></i> <span id="modalTitle">Nouveau lead</span></h3>
            <button class="mod-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="leadForm">
            <div class="mod-modal-body">
                <input type="hidden" id="leadId" name="id">

                <div class="lead-form-section">
                    <div class="lead-form-section-title"><i class="fas fa-user"></i> Informations personnelles</div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Prénom *</label><input type="text" id="firstname" name="firstname" required></div>
                        <div class="mod-form-group"><label>Nom *</label><input type="text" id="lastname" name="lastname" required></div>
                    </div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Email</label><input type="email" id="email" name="email"></div>
                        <div class="mod-form-group"><label>Téléphone</label><input type="tel" id="phone" name="phone"></div>
                    </div>
                </div>

                <div class="lead-form-section">
                    <div class="lead-form-section-title"><i class="fas fa-map-marker-alt"></i> Localisation</div>
                    <div class="mod-form-group"><label>Adresse</label><input type="text" id="address" name="address"></div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Ville</label><input type="text" id="city" name="city"></div>
                        <div class="mod-form-group"><label>Code postal</label><input type="text" id="postal_code" name="postal_code"></div>
                    </div>
                </div>

                <div class="lead-form-section">
                    <div class="lead-form-section-title"><i class="fas fa-tags"></i> Classification</div>
                    <div class="lead-form-row lead-form-row-3">
                        <div class="mod-form-group"><label>Type</label><select id="type" name="type"><?php foreach ($typeLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v['label'] ?></option><?php endforeach; ?></select></div>
                        <div class="mod-form-group"><label>Statut</label><select id="status" name="status"><?php foreach ($statusLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v['label'] ?></option><?php endforeach; ?></select></div>
                        <div class="mod-form-group"><label>Température</label><select id="temperature" name="temperature"><?php foreach ($tempLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v['label'] ?></option><?php endforeach; ?></select></div>
                    </div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Source</label><select id="source" name="source"><?php foreach ($sourceLabels as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
                        <div class="mod-form-group"><label>Score</label><input type="number" id="score" name="score" min="0" max="100" value="0"></div>
                    </div>
                </div>

                <div class="lead-form-section">
                    <div class="lead-form-section-title"><i class="fas fa-home"></i> Projet immobilier</div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Budget min (€)</label><input type="number" id="budget_min" name="budget_min"></div>
                        <div class="mod-form-group"><label>Budget max (€)</label><input type="number" id="budget_max" name="budget_max"></div>
                    </div>
                    <div class="lead-form-row lead-form-row-3">
                        <div class="mod-form-group"><label>Type de bien</label><select id="property_type" name="property_type"><option value="">--</option><option value="appartement">Appartement</option><option value="maison">Maison</option><option value="villa">Villa</option><option value="terrain">Terrain</option><option value="local">Local commercial</option><option value="immeuble">Immeuble</option></select></div>
                        <div class="mod-form-group"><label>Surface min (m²)</label><input type="number" id="surface_min" name="surface_min"></div>
                        <div class="mod-form-group"><label>Pièces min</label><input type="number" id="rooms_min" name="rooms_min"></div>
                    </div>
                </div>

                <div class="lead-form-section">
                    <div class="lead-form-section-title"><i class="fas fa-sticky-note"></i> Notes & Suivi</div>
                    <div class="mod-form-group"><label>Notes</label><textarea id="notes" name="notes" rows="4" placeholder="Informations complémentaires..."></textarea></div>
                    <div class="lead-form-row">
                        <div class="mod-form-group"><label>Prochaine action</label><input type="text" id="next_action" name="next_action" placeholder="Ex: Rappeler pour RDV"></div>
                        <div class="mod-form-group"><label>Date prochaine action</label><input type="date" id="next_action_date" name="next_action_date"></div>
                    </div>
                </div>
            </div>
            <div class="mod-modal-footer">
                <button type="button" class="mod-btn mod-btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" class="mod-btn mod-btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL — VUE LEAD ═══ -->
<div class="mod-overlay" id="viewModal">
    <div class="mod-modal" style="max-width:600px">
        <div class="mod-modal-header">
            <h3><i class="fas fa-user"></i> <span id="viewModalTitle">Détails du lead</span></h3>
            <button class="mod-modal-close" onclick="closeViewModal()">&times;</button>
        </div>
        <div class="mod-modal-body" id="viewModalContent"></div>
        <div class="mod-modal-footer">
            <button type="button" class="mod-btn mod-btn-secondary" onclick="closeViewModal()">Fermer</button>
            <button type="button" class="mod-btn mod-btn-primary" id="editFromViewBtn"><i class="fas fa-edit"></i> Modifier</button>
        </div>
    </div>
</div>

<script>
const API_URL = '/admin/modules/leads/api.php';
const statusLabels = <?= json_encode($statusLabels) ?>;
const typeLabels   = <?= json_encode($typeLabels) ?>;
const tempLabels   = <?= json_encode($tempLabels) ?>;
const sourceLabels = <?= json_encode($sourceLabels) ?>;
let currentLeadId  = null;

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Nouveau lead';
    document.getElementById('leadForm').reset();
    document.getElementById('leadId').value = '';
    document.getElementById('leadModal').classList.add('show');
}
function closeModal() { document.getElementById('leadModal').classList.remove('show'); }

function editLead(id) {
    currentLeadId = id;
    fetch(API_URL + '?action=get_lead&id=' + id).then(r=>r.json()).then(data => {
        if (!data.success || !data.lead) return;
        const l = data.lead;
        document.getElementById('modalTitle').textContent = 'Modifier le lead';
        document.getElementById('leadId').value = l.id;
        ['firstname','lastname','email','phone','address','city','postal_code','type','status','temperature','source','score','budget_min','budget_max','property_type','surface_min','rooms_min','notes','next_action','next_action_date'].forEach(f => {
            const el = document.getElementById(f);
            if (el && l[f] !== null) el.value = l[f];
        });
        document.getElementById('leadModal').classList.add('show');
    });
}

function viewLead(id) {
    currentLeadId = id;
    fetch(API_URL + '?action=get_lead&id=' + id).then(r=>r.json()).then(data => {
        if (!data.success || !data.lead) return;
        const l = data.lead;
        const si = statusLabels[l.status] || statusLabels.new;
        const ti = typeLabels[l.type]     || typeLabels.autre;
        const te = tempLabels[l.temperature] || tempLabels.warm;
        document.getElementById('viewModalTitle').textContent = l.firstname + ' ' + l.lastname;
        const sc = l.score >= 70 ? 'high' : (l.score >= 40 ? 'medium' : 'low');
        let h = `<div class="mod-flex mod-gap-lg mod-mb-lg">
            <div class="lead-avatar" style="background:${ti.color};width:72px;height:72px;font-size:24px;border-radius:14px">${l.firstname.charAt(0)}${l.lastname.charAt(0)}</div>
            <div style="flex:1"><h3 style="font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:8px">${l.firstname} ${l.lastname}</h3>
            <div class="mod-flex mod-gap-sm mod-wrap">
                <span class="lead-status-badge" style="background:${si.bg};color:${si.color}">${si.label}</span>
                <span class="lead-temp-badge ${l.temperature}"><i class="fas fa-${te.icon}"></i> ${te.label}</span>
                <span class="lead-score ${sc}">${l.score} pts</span>
            </div></div></div>`;
        const box = (label, val) => `<div style="padding:14px;background:var(--surface-2);border-radius:var(--radius)"><div class="mod-text-xs mod-text-muted" style="margin-bottom:3px">${label}</div><div style="font-size:.85rem;font-weight:500">${val}</div></div>`;
        h += `<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            ${box('Email', l.email || '—')}
            ${box('Téléphone', l.phone ? `<a href="tel:${l.phone}" style="color:var(--accent)">${l.phone}</a>` : '—')}
            ${box('Type', `<i class="fas fa-${ti.icon}" style="color:${ti.color};margin-right:4px"></i>${ti.label}`)}
            ${box('Source', sourceLabels[l.source] || l.source)}
        </div>`;
        if (l.city) h += box('<i class="fas fa-map-marker-alt" style="margin-right:4px"></i>Localisation', [l.address, l.postal_code, l.city].filter(Boolean).join(', ')) + '<div style="margin-bottom:12px"></div>';
        if (l.budget_min || l.budget_max) h += box('<i class="fas fa-euro-sign" style="margin-right:4px"></i>Budget', (l.budget_min ? Number(l.budget_min).toLocaleString('fr-FR') + ' €' : '') + (l.budget_min && l.budget_max ? ' — ' : '') + (l.budget_max ? Number(l.budget_max).toLocaleString('fr-FR') + ' €' : '')) + '<div style="margin-bottom:12px"></div>';
        if (l.notes) h += `<div style="padding:14px;background:var(--amber-bg);border-radius:var(--radius);margin-bottom:12px"><div class="mod-text-xs" style="color:#92400e;margin-bottom:3px"><i class="fas fa-sticky-note" style="margin-right:4px"></i>Notes</div><div style="font-size:.85rem;color:#78350f;white-space:pre-wrap">${l.notes}</div></div>`;
        if (l.next_action) h += `<div style="padding:14px;background:var(--blue-bg);border-radius:var(--radius)"><div class="mod-text-xs" style="color:#1e40af;margin-bottom:3px"><i class="fas fa-tasks" style="margin-right:4px"></i>Prochaine action</div><div style="font-size:.85rem;font-weight:500;color:#1e3a8a">${l.next_action}${l.next_action_date ? ' — ' + new Date(l.next_action_date).toLocaleDateString('fr-FR') : ''}</div></div>`;
        document.getElementById('viewModalContent').innerHTML = h;
        document.getElementById('editFromViewBtn').onclick = () => { closeViewModal(); editLead(id); };
        document.getElementById('viewModal').classList.add('show');
    });
}
function closeViewModal() { document.getElementById('viewModal').classList.remove('show'); }

function deleteLead(id) {
    if (!confirm('Supprimer ce lead ?')) return;
    const fd = new FormData(); fd.append('action','delete_lead'); fd.append('id', id);
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d => {
        showNotification(d.success ? 'Lead supprimé' : 'Erreur: '+(d.error||'Inconnue'), d.success ? 'success' : 'error');
        if (d.success) setTimeout(() => location.reload(), 500);
    });
}

document.getElementById('leadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const id = document.getElementById('leadId').value;
    const fd = new FormData(this);
    fd.append('action', id ? 'update_lead' : 'add_lead');
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d => {
        showNotification(d.success ? (id ? 'Lead modifié' : 'Lead créé') : 'Erreur: '+(d.error||'Inconnue'), d.success ? 'success' : 'error');
        if (d.success) { closeModal(); setTimeout(() => location.reload(), 500); }
    });
});

function toggleSelectAll() {
    const c = document.getElementById('selectAll').checked;
    document.querySelectorAll('.lead-checkbox').forEach(cb => cb.checked = c);
}
function exportLeads()    { window.location.href = API_URL + '?action=export'; }
function openImportModal() { showNotification('Import CSV à venir', 'info'); }

function showNotification(msg, type = 'info') {
    const colors = {success:'var(--green)', error:'var(--red)', info:'var(--accent)'};
    const n = document.createElement('div');
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 20px;background:${colors[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => { n.style.opacity = '0'; setTimeout(() => n.remove(), 300); }, 2500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeViewModal(); } });
document.querySelectorAll('.mod-overlay').forEach(o => o.addEventListener('click', function(e) { if (e.target === this) { closeModal(); closeViewModal(); } }));
</script>