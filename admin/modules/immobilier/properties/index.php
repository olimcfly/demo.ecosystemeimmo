<?php
/**
 * MODULE PROPERTIES - Liste des biens immobiliers
 * Chemin : /admin/modules/properties/index.php
 * 
 * Chargé par le routeur admin via :
 *   case 'properties': require __DIR__ . '/modules/properties/index.php';
 * 
 * Variables disponibles depuis le routeur :
 *   $pdo, $current_module, $current_page
 */

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ====================================================
// VARIABLES LAYOUT
// ====================================================
$page_title    = "Biens immobiliers";
$current_module = "properties";
$current_page   = "index";

// ====================================================
// CHARGER LE CONTROLLER
// ====================================================
require_once __DIR__ . '/PropertyController.php';
$controller = new PropertyController($pdo);

// ====================================================
// TRAITEMENT DES ACTIONS POST (AJAX ou classique)
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'];
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        switch ($action) {
            case 'delete':
                if ($id > 0) {
                    $result = $controller->delete($id);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Bien supprimé.' : 'Erreur.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
                }
                break;
            
            case 'update_status':
                $status = $_POST['status'] ?? '';
                if ($id > 0 && $status) {
                    $result = $controller->updateStatus($id, $status);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Statut mis à jour.' : 'Erreur.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
                }
                break;
            
            case 'toggle_featured':
                if ($id > 0) {
                    $result = $controller->toggleFeatured($id);
                    echo json_encode(['success' => $result, 'message' => $result ? 'Mise en avant modifiée.' : 'Erreur.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
                }
                break;
            
            default:
                echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit;
}

// ====================================================
// RÉCUPÉRER LES FILTRES DEPUIS L'URL
// ====================================================
$filters = [
    'search'      => trim($_GET['search'] ?? ''),
    'status'      => $_GET['status'] ?? '',
    'type'        => $_GET['type'] ?? '',
    'transaction' => $_GET['transaction'] ?? '',
    'city'        => $_GET['city'] ?? '',
    'price_min'   => $_GET['price_min'] ?? '',
    'price_max'   => $_GET['price_max'] ?? '',
    'surface_min' => $_GET['surface_min'] ?? '',
    'rooms_min'   => $_GET['rooms_min'] ?? '',
    'featured'    => $_GET['featured'] ?? '',
    'sort'        => $_GET['sort'] ?? 'created_at',
    'order'       => $_GET['order'] ?? 'DESC',
    'page'        => max(1, (int)($_GET['p'] ?? 1)),
    'per_page'    => 20,
];

// ====================================================
// RÉCUPÉRER LES DONNÉES
// ====================================================
$result = $controller->getAll($filters);
$properties = $result['data'];
$total       = $result['total'];
$currentPage = $result['page'];
$totalPages  = $result['total_pages'];
$perPage     = $result['per_page'];

// Récupérer les villes et stats pour les filtres
$cities = $controller->getCities();
$stats  = $controller->getStats();

// ====================================================
// FONCTIONS UTILITAIRES
// ====================================================
function statusBadge($status) {
    $map = [
        'draft'       => ['label' => 'Brouillon',  'color' => '#6b7280', 'bg' => '#f3f4f6'],
        'available'   => ['label' => 'Disponible',  'color' => '#059669', 'bg' => '#d1fae5'],
        'under_offer' => ['label' => 'Sous offre',  'color' => '#d97706', 'bg' => '#fef3c7'],
        'sold'        => ['label' => 'Vendu',        'color' => '#dc2626', 'bg' => '#fee2e2'],
        'rented'      => ['label' => 'Loué',         'color' => '#7c3aed', 'bg' => '#ede9fe'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
    return '<span class="badge" style="background:' . $s['bg'] . '; color:' . $s['color'] . '; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;">' . $s['label'] . '</span>';
}

function typeBadge($type) {
    $map = [
        'appartement' => ['icon' => '🏢', 'label' => 'Appartement'],
        'maison'      => ['icon' => '🏠', 'label' => 'Maison'],
        'terrain'     => ['icon' => '🌳', 'label' => 'Terrain'],
        'commerce'    => ['icon' => '🏪', 'label' => 'Commerce'],
        'autre'       => ['icon' => '📦', 'label' => 'Autre'],
    ];
    $t = $map[$type] ?? ['icon' => '📦', 'label' => ucfirst($type)];
    return $t['icon'] . ' ' . $t['label'];
}

function formatPrice($price) {
    if (!$price) return '<span style="color:#9ca3af;">—</span>';
    return number_format((float)$price, 0, ',', ' ') . ' €';
}

function buildFilterUrl($overrides = []) {
    global $filters;
    $params = array_merge([
        'module'      => 'properties',
        'page'        => 'index',
        'search'      => $filters['search'],
        'status'      => $filters['status'],
        'type'        => $filters['type'],
        'transaction' => $filters['transaction'],
        'city'        => $filters['city'],
        'price_min'   => $filters['price_min'],
        'price_max'   => $filters['price_max'],
        'surface_min' => $filters['surface_min'],
        'rooms_min'   => $filters['rooms_min'],
        'featured'    => $filters['featured'],
        'sort'        => $filters['sort'],
        'order'       => $filters['order'],
    ], $overrides);
    
    // Nettoyer les params vides
    $params = array_filter($params, function($v) { return $v !== '' && $v !== null; });
    
    return '/admin/index.php?' . http_build_query($params);
}

function sortLink($column, $label) {
    global $filters;
    $isActive = $filters['sort'] === $column;
    $newOrder = ($isActive && $filters['order'] === 'ASC') ? 'DESC' : 'ASC';
    $icon = '';
    if ($isActive) {
        $icon = $filters['order'] === 'ASC' ? ' ↑' : ' ↓';
    }
    $url = buildFilterUrl(['sort' => $column, 'order' => $newOrder, 'p' => 1]);
    $style = $isActive ? 'color:var(--color-primary, #2563eb); font-weight:700;' : '';
    return '<a href="' . htmlspecialchars($url) . '" style="text-decoration:none; ' . $style . '">' . $label . $icon . '</a>';
}

// ====================================================
// DÉBUT DU CONTENU (intégré dans layout.php)
// ====================================================
ob_start();
?>

<style>
/* ── Properties Module Styles ── */
.props-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.props-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.props-stats-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.props-stat-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px 20px;
    flex: 1;
    min-width: 140px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.props-stat-card .stat-value {
    font-size: 28px;
    font-weight: 800;
    color: #1f2937;
}
.props-stat-card .stat-label {
    font-size: 13px;
    color: #6b7280;
}

/* Filtres */
.props-filters {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}
.props-filters-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.props-filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.props-filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.props-filter-group input,
.props-filter-group select {
    height: 38px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 14px;
    color: #1f2937;
    background: #fff;
    transition: border-color 0.2s;
}
.props-filter-group input:focus,
.props-filter-group select:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}
.props-filter-group input[type="text"] { width: 200px; }
.props-filter-group input[type="number"] { width: 110px; }
.props-filter-group select { min-width: 130px; }

/* Tableau */
.props-table-wrapper {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
}
.props-table {
    width: 100%;
    border-collapse: collapse;
}
.props-table thead th {
    background: #f9fafb;
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e5e7eb;
    white-space: nowrap;
}
.props-table tbody td {
    padding: 14px 16px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.props-table tbody tr:hover {
    background: #f9fafb;
}
.props-table tbody tr:last-child td {
    border-bottom: none;
}

/* Property row */
.prop-title-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.prop-thumb {
    width: 60px;
    height: 45px;
    border-radius: 6px;
    object-fit: cover;
    background: #f3f4f6;
    flex-shrink: 0;
}
.prop-thumb-placeholder {
    width: 60px;
    height: 45px;
    border-radius: 6px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.prop-title-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.prop-title-text strong {
    color: #1f2937;
    font-size: 14px;
}
.prop-title-text small {
    color: #9ca3af;
    font-size: 12px;
}

/* Actions */
.prop-actions {
    display: flex;
    gap: 6px;
}
.prop-actions .btn-icon {
    width: 32px;
    height: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 14px;
    text-decoration: none;
    color: #6b7280;
}
.prop-actions .btn-icon:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
    color: #1f2937;
}
.prop-actions .btn-icon.btn-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #dc2626;
}
.prop-actions .btn-icon.btn-star {
    color: #d1d5db;
}
.prop-actions .btn-icon.btn-star.active {
    color: #f59e0b;
}

/* Pagination */
.props-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    font-size: 14px;
    color: #6b7280;
}
.props-pagination-links {
    display: flex;
    gap: 4px;
}
.props-pagination-links a,
.props-pagination-links span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
}
.props-pagination-links a:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}
.props-pagination-links .active {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    font-weight: 700;
}

/* Status dropdown */
.status-dropdown {
    position: relative;
    display: inline-block;
}
.status-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
    z-index: 50;
    min-width: 160px;
    padding: 4px;
}
.status-dropdown-menu.show {
    display: block;
}
.status-dropdown-menu button {
    display: block;
    width: 100%;
    text-align: left;
    padding: 8px 12px;
    border: none;
    background: none;
    font-size: 13px;
    color: #374151;
    cursor: pointer;
    border-radius: 6px;
}
.status-dropdown-menu button:hover {
    background: #f3f4f6;
}

/* Empty state */
.props-empty {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}
.props-empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
}
.props-empty h3 {
    font-size: 18px;
    color: #6b7280;
    margin-bottom: 8px;
}

/* Buttons */
.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.2s;
}
.btn-primary:hover {
    background: #1d4ed8;
    color: #fff;
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #fff;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* Toast */
.toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 14px 24px;
    background: #1f2937;
    color: #fff;
    border-radius: 10px;
    font-size: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 9999;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s ease;
}
.toast.show {
    transform: translateY(0);
    opacity: 1;
}
.toast.toast-error {
    background: #dc2626;
}
.toast.toast-success {
    background: #059669;
}

/* Featured star */
.star-active { color: #f59e0b !important; }

/* Responsive */
@media (max-width: 768px) {
    .props-header { flex-direction: column; align-items: stretch; }
    .props-stats-row { flex-direction: column; }
    .props-filters-row { flex-direction: column; }
    .props-filter-group input[type="text"],
    .props-filter-group input[type="number"],
    .props-filter-group select { width: 100%; }
    .props-table { font-size: 13px; }
    .prop-thumb, .prop-thumb-placeholder { display: none; }
}
</style>

<!-- ====================================================
     HEADER
     ==================================================== -->
<div class="props-header">
    <div>
        <h1>🏠 Biens immobiliers</h1>
        <p style="color:#6b7280; margin:4px 0 0; font-size:14px;">
            <?= $total ?> bien<?= $total > 1 ? 's' : '' ?> au total
        </p>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="/admin/index.php?module=properties&page=create" class="btn-primary">
            ＋ Ajouter un bien
        </a>
    </div>
</div>

<!-- ====================================================
     STATS RAPIDES
     ==================================================== -->
<div class="props-stats-row">
    <div class="props-stat-card">
        <span class="stat-value"><?= $stats['total'] ?? 0 ?></span>
        <span class="stat-label">Total biens</span>
    </div>
    <div class="props-stat-card" style="border-left: 3px solid #059669;">
        <span class="stat-value"><?= $stats['by_status']['available'] ?? 0 ?></span>
        <span class="stat-label">Disponibles</span>
    </div>
    <div class="props-stat-card" style="border-left: 3px solid #d97706;">
        <span class="stat-value"><?= $stats['by_status']['under_offer'] ?? 0 ?></span>
        <span class="stat-label">Sous offre</span>
    </div>
    <div class="props-stat-card" style="border-left: 3px solid #dc2626;">
        <span class="stat-value"><?= ($stats['by_status']['sold'] ?? 0) + ($stats['by_status']['rented'] ?? 0) ?></span>
        <span class="stat-label">Vendus / Loués</span>
    </div>
    <div class="props-stat-card" style="border-left: 3px solid #2563eb;">
        <span class="stat-value"><?= number_format($stats['total_views'] ?? 0) ?></span>
        <span class="stat-label">Vues totales</span>
    </div>
</div>

<!-- ====================================================
     FILTRES
     ==================================================== -->
<div class="props-filters">
    <form method="GET" action="/admin/index.php" id="filtersForm">
        <input type="hidden" name="module" value="properties">
        <input type="hidden" name="page" value="index">
        <input type="hidden" name="sort" value="<?= htmlspecialchars($filters['sort']) ?>">
        <input type="hidden" name="order" value="<?= htmlspecialchars($filters['order']) ?>">
        
        <div class="props-filters-row">
            <!-- Recherche -->
            <div class="props-filter-group" style="flex:2;">
                <label>Recherche</label>
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" 
                       placeholder="Titre, référence, ville, adresse..." style="width:100%;">
            </div>
            
            <!-- Statut -->
            <div class="props-filter-group">
                <label>Statut</label>
                <select name="status">
                    <option value="">Tous</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="available" <?= $filters['status'] === 'available' ? 'selected' : '' ?>>Disponible</option>
                    <option value="under_offer" <?= $filters['status'] === 'under_offer' ? 'selected' : '' ?>>Sous offre</option>
                    <option value="sold" <?= $filters['status'] === 'sold' ? 'selected' : '' ?>>Vendu</option>
                    <option value="rented" <?= $filters['status'] === 'rented' ? 'selected' : '' ?>>Loué</option>
                </select>
            </div>
            
            <!-- Type -->
            <div class="props-filter-group">
                <label>Type</label>
                <select name="type">
                    <option value="">Tous</option>
                    <option value="appartement" <?= $filters['type'] === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                    <option value="maison" <?= $filters['type'] === 'maison' ? 'selected' : '' ?>>Maison</option>
                    <option value="terrain" <?= $filters['type'] === 'terrain' ? 'selected' : '' ?>>Terrain</option>
                    <option value="commerce" <?= $filters['type'] === 'commerce' ? 'selected' : '' ?>>Commerce</option>
                    <option value="autre" <?= $filters['type'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            
            <!-- Transaction -->
            <div class="props-filter-group">
                <label>Transaction</label>
                <select name="transaction">
                    <option value="">Toutes</option>
                    <option value="vente" <?= $filters['transaction'] === 'vente' ? 'selected' : '' ?>>Vente</option>
                    <option value="location" <?= $filters['transaction'] === 'location' ? 'selected' : '' ?>>Location</option>
                </select>
            </div>
            
            <!-- Ville -->
            <div class="props-filter-group">
                <label>Ville</label>
                <select name="city">
                    <option value="">Toutes</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>" <?= $filters['city'] === $city ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Ligne 2 : prix, surface, pièces -->
        <div class="props-filters-row" style="margin-top: 12px;">
            <div class="props-filter-group">
                <label>Prix min</label>
                <input type="number" name="price_min" value="<?= htmlspecialchars($filters['price_min']) ?>" placeholder="50 000">
            </div>
            <div class="props-filter-group">
                <label>Prix max</label>
                <input type="number" name="price_max" value="<?= htmlspecialchars($filters['price_max']) ?>" placeholder="500 000">
            </div>
            <div class="props-filter-group">
                <label>Surface min (m²)</label>
                <input type="number" name="surface_min" value="<?= htmlspecialchars($filters['surface_min']) ?>" placeholder="30">
            </div>
            <div class="props-filter-group">
                <label>Pièces min</label>
                <input type="number" name="rooms_min" value="<?= htmlspecialchars($filters['rooms_min']) ?>" placeholder="2">
            </div>
            <div class="props-filter-group">
                <label>Mis en avant</label>
                <select name="featured">
                    <option value="">Tous</option>
                    <option value="1" <?= $filters['featured'] === '1' ? 'selected' : '' ?>>⭐ Oui</option>
                    <option value="0" <?= $filters['featured'] === '0' ? 'selected' : '' ?>>Non</option>
                </select>
            </div>
            
            <!-- Boutons -->
            <div class="props-filter-group" style="flex-direction:row; gap:8px; align-items:flex-end;">
                <button type="submit" class="btn-primary" style="height:38px; padding:0 16px;">
                    🔍 Filtrer
                </button>
                <a href="/admin/index.php?module=properties&page=index" class="btn-secondary" style="height:38px; padding:0 16px;">
                    ✕ Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- ====================================================
     TABLEAU DES BIENS
     ==================================================== -->
<div class="props-table-wrapper">
    <?php if (empty($properties)): ?>
        <div class="props-empty">
            <div class="props-empty-icon">🏠</div>
            <h3>Aucun bien trouvé</h3>
            <p>Ajoutez votre premier bien ou modifiez les filtres.</p>
            <a href="/admin/index.php?module=properties&page=create" class="btn-primary" style="margin-top:16px;">
                ＋ Ajouter un bien
            </a>
        </div>
    <?php else: ?>
        <table class="props-table">
            <thead>
                <tr>
                    <th style="width:30px;">⭐</th>
                    <th style="min-width:250px;"><?= sortLink('title', 'Bien') ?></th>
                    <th><?= sortLink('reference', 'Réf.') ?></th>
                    <th>Type</th>
                    <th><?= sortLink('price', 'Prix') ?></th>
                    <th><?= sortLink('surface', 'Surface') ?></th>
                    <th>Pièces</th>
                    <th><?= sortLink('city', 'Ville') ?></th>
                    <th><?= sortLink('status', 'Statut') ?></th>
                    <th><?= sortLink('views', 'Vues') ?></th>
                    <th><?= sortLink('created_at', 'Créé le') ?></th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($properties as $prop): ?>
                    <?php 
                        $images = $prop['images'];
                        $thumb = !empty($images[0]) ? $images[0] : null;
                        $isFeatured = !empty($prop['featured']);
                    ?>
                    <tr data-id="<?= $prop['id'] ?>">
                        <!-- Featured -->
                        <td>
                            <button class="btn-icon btn-star <?= $isFeatured ? 'active star-active' : '' ?>" 
                                    onclick="toggleFeatured(<?= $prop['id'] ?>, this)"
                                    title="<?= $isFeatured ? 'Retirer la mise en avant' : 'Mettre en avant' ?>">
                                ★
                            </button>
                        </td>
                        
                        <!-- Titre + thumb -->
                        <td>
                            <div class="prop-title-cell">
                                <?php if ($thumb): ?>
                                    <img src="<?= htmlspecialchars($thumb) ?>" class="prop-thumb" alt="" loading="lazy">
                                <?php else: ?>
                                    <div class="prop-thumb-placeholder">🏠</div>
                                <?php endif; ?>
                                <div class="prop-title-text">
                                    <strong>
                                        <a href="/admin/index.php?module=properties&page=edit&id=<?= $prop['id'] ?>" 
                                           style="color:inherit; text-decoration:none;">
                                            <?= htmlspecialchars($prop['title']) ?>
                                        </a>
                                    </strong>
                                    <small><?= htmlspecialchars($prop['address'] ?: $prop['neighborhood'] ?: '') ?></small>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Référence -->
                        <td>
                            <code style="font-size:12px; background:#f3f4f6; padding:2px 6px; border-radius:4px;">
                                <?= htmlspecialchars($prop['reference'] ?? '—') ?>
                            </code>
                        </td>
                        
                        <!-- Type -->
                        <td style="white-space:nowrap;"><?= typeBadge($prop['type']) ?></td>
                        
                        <!-- Prix -->
                        <td style="font-weight:600; white-space:nowrap;"><?= formatPrice($prop['price']) ?></td>
                        
                        <!-- Surface -->
                        <td><?= $prop['surface'] ? $prop['surface'] . ' m²' : '—' ?></td>
                        
                        <!-- Pièces -->
                        <td>
                            <?php if ($prop['rooms']): ?>
                                <?= $prop['rooms'] ?>p
                                <?= $prop['bedrooms'] ? '/ ' . $prop['bedrooms'] . 'ch' : '' ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        
                        <!-- Ville -->
                        <td><?= htmlspecialchars($prop['city'] ?: '—') ?></td>
                        
                        <!-- Statut -->
                        <td>
                            <div class="status-dropdown">
                                <span onclick="toggleStatusMenu(this)" style="cursor:pointer;">
                                    <?= statusBadge($prop['status']) ?>
                                </span>
                                <div class="status-dropdown-menu">
                                    <button onclick="changeStatus(<?= $prop['id'] ?>, 'draft', this)">📝 Brouillon</button>
                                    <button onclick="changeStatus(<?= $prop['id'] ?>, 'available', this)">✅ Disponible</button>
                                    <button onclick="changeStatus(<?= $prop['id'] ?>, 'under_offer', this)">⏳ Sous offre</button>
                                    <button onclick="changeStatus(<?= $prop['id'] ?>, 'sold', this)">🔴 Vendu</button>
                                    <button onclick="changeStatus(<?= $prop['id'] ?>, 'rented', this)">🟣 Loué</button>
                                </div>
                            </div>
                        </td>
                        
                        <!-- Vues -->
                        <td style="color:#9ca3af;"><?= number_format($prop['views'] ?? 0) ?></td>
                        
                        <!-- Date -->
                        <td style="white-space:nowrap; color:#9ca3af; font-size:13px;">
                            <?= date('d/m/Y', strtotime($prop['created_at'])) ?>
                        </td>
                        
                        <!-- Actions -->
                        <td>
                            <div class="prop-actions">
                                <a href="/admin/index.php?module=properties&page=edit&id=<?= $prop['id'] ?>" 
                                   class="btn-icon" title="Modifier">✏️</a>
                                <?php if ($prop['slug']): ?>
                                    <a href="/biens/<?= htmlspecialchars($prop['slug']) ?>" 
                                       class="btn-icon" title="Voir le bien" target="_blank">👁️</a>
                                <?php endif; ?>
                                <button class="btn-icon btn-danger" title="Supprimer" 
                                        onclick="deleteProp(<?= $prop['id'] ?>, '<?= htmlspecialchars(addslashes($prop['title'])) ?>')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="props-pagination">
                <span>
                    Affichage <?= (($currentPage - 1) * $perPage) + 1 ?>–<?= min($currentPage * $perPage, $total) ?> 
                    sur <?= $total ?> biens
                </span>
                <div class="props-pagination-links">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= buildFilterUrl(['p' => $currentPage - 1]) ?>">← Préc.</a>
                    <?php endif; ?>
                    
                    <?php
                    $startP = max(1, $currentPage - 2);
                    $endP = min($totalPages, $currentPage + 2);
                    if ($startP > 1) echo '<span>...</span>';
                    for ($i = $startP; $i <= $endP; $i++):
                    ?>
                        <?php if ($i === $currentPage): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= buildFilterUrl(['p' => $i]) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($endP < $totalPages) echo '<span>...</span>'; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= buildFilterUrl(['p' => $currentPage + 1]) ?>">Suiv. →</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Toast notification -->
<div id="toast" class="toast"></div>

<!-- ====================================================
     JAVASCRIPT
     ==================================================== -->
<script>
// Toast
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast toast-' + type + ' show';
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Toggle featured
function toggleFeatured(id, btn) {
    fetch('/admin/index.php?module=properties&page=index', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=toggle_featured&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active');
            btn.classList.toggle('star-active');
            showToast(data.message);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
}

// Change status
function changeStatus(id, status, btn) {
    fetch('/admin/index.php?module=properties&page=index', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_status&id=' + id + '&status=' + status
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Recharger après un court délai
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
    
    // Fermer le menu
    const menu = btn.closest('.status-dropdown-menu');
    if (menu) menu.classList.remove('show');
}

// Toggle status dropdown menu
function toggleStatusMenu(el) {
    // Fermer tous les autres
    document.querySelectorAll('.status-dropdown-menu.show').forEach(m => {
        if (m !== el.nextElementSibling) m.classList.remove('show');
    });
    const menu = el.nextElementSibling;
    menu.classList.toggle('show');
}

// Fermer les menus au clic extérieur
document.addEventListener('click', (e) => {
    if (!e.target.closest('.status-dropdown')) {
        document.querySelectorAll('.status-dropdown-menu.show').forEach(m => m.classList.remove('show'));
    }
});

// Delete property
function deleteProp(id, title) {
    if (!confirm('Supprimer le bien "' + title + '" ?\nCette action est irréversible.')) return;
    
    fetch('/admin/index.php?module=properties&page=index', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            // Supprimer la ligne du tableau
            const row = document.querySelector('tr[data-id="' + id + '"]');
            if (row) {
                row.style.opacity = '0.3';
                row.style.transition = 'opacity 0.3s';
                setTimeout(() => row.remove(), 300);
            }
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
}
</script>

<?php
// ====================================================
// INTÉGRATION DANS LE LAYOUT
// ====================================================
$content = ob_get_clean();

// Charger le layout
include __DIR__ . '/../../includes/layout.php';
?>