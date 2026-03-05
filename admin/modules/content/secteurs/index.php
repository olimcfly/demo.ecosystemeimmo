<?php
/**
 * MODULE SECTEURS - Liste des quartiers/communes
 * /admin/modules/secteurs/index.php
 * 
 * v2.0 - CSS intégré inline (plus de dépendance fichier externe)
 * Connecté à la table `secteurs`
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/classes/Database.php';

$db = Database::getInstance();

// ─── TRAITER LES ACTIONS POST ───
$action = $_POST['action'] ?? '';
$itemId = intval($_POST['id'] ?? 0);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($action === 'delete' && $itemId > 0) {
        try {
            $stmt = $db->prepare("DELETE FROM secteurs WHERE id = ?");
            if ($stmt->execute([$itemId])) {
                header("Location: /admin/dashboard.php?page=secteurs&success=deleted");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur suppression: " . $e->getMessage();
        }
    }
    
    if ($action === 'toggle_status' && $itemId > 0) {
        try {
            $stmt = $db->prepare("UPDATE secteurs SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?");
            $stmt->execute([$itemId]);
            header("Location: /admin/dashboard.php?page=secteurs&success=status_updated");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur changement statut: " . $e->getMessage();
        }
    }
    
    if ($action === 'duplicate' && $itemId > 0) {
        try {
            $stmt = $db->prepare("SELECT * FROM secteurs WHERE id = ?");
            $stmt->execute([$itemId]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($original) {
                unset($original['id']);
                $original['nom'] = $original['nom'] . ' (copie)';
                $original['slug'] = $original['slug'] . '-copie-' . time();
                $original['status'] = 'draft';
                
                $cols = array_keys($original);
                $placeholders = array_fill(0, count($cols), '?');
                $sql = "INSERT INTO secteurs (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute(array_values($original));
                
                header("Location: /admin/dashboard.php?page=secteurs&success=duplicated");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur duplication: " . $e->getMessage();
        }
    }
}

// ─── FILTRES & TRI ───
$filterStatus = $_GET['status'] ?? '';
$filterType = $_GET['type_secteur'] ?? '';
$filterVille = $_GET['ville'] ?? '';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'nom';
$order = strtoupper($_GET['order'] ?? 'ASC');
$order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';

$allowedSorts = ['nom', 'ville', 'type_secteur', 'status', 'created_at', 'id'];
if (!in_array($sort, $allowedSorts)) $sort = 'nom';

// ─── REQUÊTE SECTEURS ───
$where = [];
$params = [];

if ($filterStatus) {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}
if ($filterType) {
    $where[] = "type_secteur = ?";
    $params[] = $filterType;
}
if ($filterVille) {
    $where[] = "ville = ?";
    $params[] = $filterVille;
}
if ($search) {
    $where[] = "(nom LIKE ? OR slug LIKE ? OR ville LIKE ? OR meta_title LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$sql = "SELECT * FROM secteurs $whereClause ORDER BY $sort $order";

$secteurs = [];
$stats = ['total' => 0, 'published' => 0, 'draft' => 0, 'quartiers' => 0, 'communes' => 0];
$villes = [];
$types = [];

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmtStats = $db->query("SELECT 
        COUNT(*) as total,
        SUM(status = 'published') as published,
        SUM(status = 'draft') as draft,
        SUM(type_secteur = 'quartier') as quartiers,
        SUM(type_secteur = 'commune') as communes
        FROM secteurs");
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);
    
    $stmtVilles = $db->query("SELECT DISTINCT ville FROM secteurs WHERE ville IS NOT NULL AND ville != '' ORDER BY ville");
    $villes = $stmtVilles->fetchAll(PDO::FETCH_COLUMN);
    
    $stmtTypes = $db->query("SELECT DISTINCT type_secteur FROM secteurs WHERE type_secteur IS NOT NULL AND type_secteur != '' ORDER BY type_secteur");
    $types = $stmtTypes->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $error = "Erreur base de données: " . $e->getMessage();
}

// Messages de succès
$success = $_GET['success'] ?? '';
$successMessages = [
    'created'        => '✅ Secteur créé avec succès !',
    'updated'        => '✅ Secteur mis à jour !',
    'deleted'        => '✅ Secteur supprimé',
    'duplicated'     => '✅ Secteur dupliqué (en brouillon)',
    'status_updated' => '✅ Statut mis à jour'
];
?>

<style>
/* ══════════════════════════════════════════════════════════
   MODULE SECTEURS - Styles intégrés
   ══════════════════════════════════════════════════════════ */

.secteurs-module {
    padding: 24px;
    max-width: 100%;
}

/* ─── ALERTS ─── */
.sm-alert {
    padding: 14px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 500;
    animation: smSlideDown 0.3s ease;
}

@keyframes smSlideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.sm-alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.sm-alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.sm-alert-close {
    margin-left: auto;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    opacity: 0.6;
    color: inherit;
    line-height: 1;
}

.sm-alert-close:hover { opacity: 1; }

/* ─── MODULE HEADER ─── */
.sm-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e2e8f0;
}

.sm-header__left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sm-header__left h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.sm-header__left h2 i {
    color: #3b82f6;
    font-size: 18px;
}

.sm-count {
    background: #e2e8f0;
    color: #64748b;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.sm-btn-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white !important;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
}

.sm-btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
    color: white !important;
    text-decoration: none;
}

/* ─── STATS CARDS ─── */
.sm-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.sm-stat {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.sm-stat:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.sm-stat__icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.sm-stat__data {
    display: flex;
    flex-direction: column;
}

.sm-stat__value {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.1;
}

.sm-stat__label {
    font-size: 11px;
    color: #94a3b8;
    font-weight: 500;
    margin-top: 2px;
}

/* ─── FILTERS BAR ─── */
.sm-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.sm-filters__left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    flex: 1;
}

.sm-filters__right {
    display: flex;
    align-items: center;
    gap: 10px;
}

.sm-search {
    position: relative;
    min-width: 220px;
}

.sm-search i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
    pointer-events: none;
}

.sm-search input {
    width: 100%;
    padding: 9px 12px 9px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    color: #1e293b;
    background: white;
    transition: all 0.2s;
}

.sm-search input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sm-select {
    padding: 9px 30px 9px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    color: #475569;
    background: white;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}

.sm-select:focus { outline: none; border-color: #3b82f6; }

.sm-btn-reset {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #fee2e2;
    color: #dc2626;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.sm-btn-reset:hover { background: #fecaca; color: #dc2626; text-decoration: none; }

.sm-view-toggle {
    display: flex;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.sm-view-btn {
    padding: 8px 12px;
    background: white;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.sm-view-btn + .sm-view-btn { border-left: 1px solid #e2e8f0; }

.sm-view-btn.active { background: #3b82f6; color: white; }
.sm-view-btn:hover:not(.active) { background: #f8fafc; color: #475569; }

/* ─── VIEW CONTAINERS ─── */
.sm-view { display: none; }
.sm-view.active { display: block; }

/* ─── TABLE ─── */
.sm-table-wrap {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.sm-table {
    width: 100%;
    border-collapse: collapse;
}

.sm-table thead { background: #f8fafc; }

.sm-table th {
    padding: 14px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.sm-th-check { width: 40px; text-align: center; }
.sm-th-img { width: 60px; }

.sm-th-sort {
    cursor: pointer;
    user-select: none;
    transition: color 0.2s;
}

.sm-th-sort:hover { color: #3b82f6; }
.sm-th-sort.sorted { color: #3b82f6; }
.sm-th-sort i { margin-left: 4px; font-size: 10px; }

.sm-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.sm-table tbody tr:hover { background: #f8fafc; }
.sm-table tbody tr:last-child { border-bottom: none; }

.sm-table td {
    padding: 12px 16px;
    font-size: 13px;
    color: #1e293b;
    vertical-align: middle;
}

.sm-td-check { text-align: center; }

/* Thumbnails */
.sm-thumb {
    width: 48px;
    height: 36px;
    border-radius: 6px;
    background-size: cover;
    background-position: center;
    border: 1px solid #e2e8f0;
}

.sm-thumb-empty {
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 14px;
}

/* Secteur info */
.sm-info { display: flex; flex-direction: column; gap: 2px; }

.sm-name {
    font-weight: 600;
    color: #1e293b;
    text-decoration: none;
    transition: color 0.2s;
}

.sm-name:hover { color: #3b82f6; text-decoration: none; }

.sm-slug {
    font-size: 11px;
    color: #94a3b8;
    font-family: 'Monaco', 'Consolas', monospace;
}

.sm-hero-title {
    font-size: 11px;
    color: #64748b;
    font-style: italic;
}

/* Ville tag */
.sm-ville {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    background: #f0f9ff;
    color: #0369a1;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
}

/* Badges */
.sm-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.sm-badge-quartier { background: #fef3c7; color: #92400e; }
.sm-badge-commune { background: #ede9fe; color: #6d28d9; }

/* Status badge (clickable) */
.sm-badge-status {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    background: none;
    font-family: inherit;
}

.sm-badge-status.published { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.sm-badge-status.published:hover { background: #bbf7d0; }
.sm-badge-status.draft { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.sm-badge-status.draft:hover { background: #fde68a; }

/* SEO indicator */
.sm-seo {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sm-seo-dots { display: flex; gap: 3px; }

.sm-seo-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e2e8f0;
}

.sm-seo-dot.filled { background: #10b981; }

.sm-seo-good .sm-seo-score { color: #059669; }
.sm-seo-partial .sm-seo-score { color: #d97706; }
.sm-seo-none .sm-seo-score { color: #dc2626; }

.sm-seo-score { font-size: 11px; font-weight: 600; }

/* Actions cell */
.sm-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
}

.sm-btn-action {
    padding: 7px 10px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    font-size: 12px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

.sm-btn-action:hover { border-color: #cbd5e1; color: #1e293b; background: #f8fafc; text-decoration: none; }
.sm-btn-edit:hover { border-color: #93c5fd; color: #2563eb; background: #eff6ff; }
.sm-btn-builder:hover { border-color: #c4b5fd; color: #7c3aed; background: #f5f3ff; }
.sm-btn-view:hover { border-color: #6ee7b7; color: #059669; background: #ecfdf5; }

.sm-btn-more { padding: 7px 8px; }

/* Dropdown */
.sm-dropdown-wrap { position: relative; }

.sm-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    margin-top: 4px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    min-width: 160px;
    z-index: 50;
    display: none;
    overflow: hidden;
}

.sm-dropdown.open { display: block; }
.sm-dropdown hr { margin: 0; border: none; border-top: 1px solid #f1f5f9; }

.sm-dropdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 16px;
    background: none;
    border: none;
    font-size: 12px;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
    text-align: left;
    font-family: inherit;
}

.sm-dropdown-item:hover { background: #f8fafc; color: #1e293b; }
.sm-dropdown-item.danger { color: #dc2626; }
.sm-dropdown-item.danger:hover { background: #fef2f2; }

/* ─── BULK ACTIONS ─── */
.sm-bulk {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    background: #1e293b;
    border-radius: 10px;
    margin-top: 16px;
    animation: smSlideUp 0.3s ease;
}

@keyframes smSlideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.sm-bulk-count { color: white; font-size: 13px; font-weight: 500; margin-right: 8px; }

.sm-btn-bulk {
    padding: 7px 14px;
    border: none;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    font-family: inherit;
}

.sm-bulk-publish { background: #10b981; color: white; }
.sm-bulk-publish:hover { background: #059669; }
.sm-bulk-draft { background: #f59e0b; color: white; }
.sm-bulk-draft:hover { background: #d97706; }
.sm-bulk-delete { background: #ef4444; color: white; }
.sm-bulk-delete:hover { background: #dc2626; }

/* ─── GRID VIEW ─── */
.sm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.sm-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
    transition: all 0.3s;
}

.sm-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
}

.sm-card__img {
    height: 160px;
    background-size: cover;
    background-position: center;
    background-color: #f1f5f9;
    position: relative;
}

.sm-card__no-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cbd5e1;
    font-size: 32px;
}

.sm-card__overlay {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    justify-content: space-between;
}

.sm-card__badge-sm {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

.sm-card__badge-sm.published { background: rgba(16, 185, 129, 0.9); color: white; }
.sm-card__badge-sm.draft { background: rgba(245, 158, 11, 0.9); color: white; }

.sm-card__type-sm {
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.9);
    color: #475569;
}

.sm-card__body { padding: 16px; }

.sm-card__title { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0; }

.sm-card__ville {
    font-size: 12px;
    color: #64748b;
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.sm-card__ville i { color: #3b82f6; font-size: 11px; }

.sm-card__subtitle { font-size: 12px; color: #94a3b8; margin: 0; line-height: 1.4; }

.sm-card__footer {
    padding: 12px 16px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    gap: 8px;
}

.sm-card__footer .sm-btn-action {
    flex: 1;
    justify-content: center;
    font-size: 11px;
    padding: 6px 8px;
}

/* ─── EMPTY STATE ─── */
.sm-empty {
    text-align: center;
    padding: 60px 40px;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px dashed #cbd5e1;
}

.sm-empty i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
.sm-empty h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; }
.sm-empty p { font-size: 13px; color: #64748b; margin: 0 0 20px 0; }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .sm-header { flex-direction: column; gap: 16px; align-items: flex-start; }
    .sm-filters { flex-direction: column; align-items: stretch; }
    .sm-filters__left { flex-direction: column; }
    .sm-search { min-width: 100%; }
    .sm-stats { grid-template-columns: repeat(2, 1fr); }
    .sm-grid { grid-template-columns: 1fr; }
}

@media (max-width: 1100px) {
    .sm-table .sm-col-img { display: none; }
}

@media (max-width: 900px) {
    .sm-table .sm-col-seo { display: none; }
}
</style>

<div class="secteurs-module">

    <!-- ══════ MESSAGES ══════ -->
    <?php if ($success && isset($successMessages[$success])): ?>
    <div class="sm-alert sm-alert-success">
        <span><?= $successMessages[$success] ?></span>
        <button onclick="this.parentElement.remove()" class="sm-alert-close">&times;</button>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="sm-alert sm-alert-error">
        <i class="fas fa-exclamation-triangle"></i>
        <span><?= htmlspecialchars($error) ?></span>
        <button onclick="this.parentElement.remove()" class="sm-alert-close">&times;</button>
    </div>
    <?php endif; ?>

    <!-- ══════ HEADER ══════ -->
    <div class="sm-header">
        <div class="sm-header__left">
            <h2><i class="fas fa-map-marker-alt"></i> Mes secteurs</h2>
            <span class="sm-count"><?= $stats['total'] ?? 0 ?> secteur<?= ($stats['total'] ?? 0) > 1 ? 's' : '' ?></span>
        </div>
        <div class="sm-header__right">
            <a href="/admin/modules/secteurs/edit.php?action=create" class="sm-btn-create">
                <i class="fas fa-plus"></i> Nouveau secteur
            </a>
        </div>
    </div>

    <!-- ══════ STATS CARDS ══════ -->
    <div class="sm-stats">
        <div class="sm-stat">
            <div class="sm-stat__icon" style="background: #dbeafe; color: #2563eb;">
                <i class="fas fa-globe-europe"></i>
            </div>
            <div class="sm-stat__data">
                <span class="sm-stat__value"><?= $stats['total'] ?? 0 ?></span>
                <span class="sm-stat__label">Total secteurs</span>
            </div>
        </div>
        <div class="sm-stat">
            <div class="sm-stat__icon" style="background: #d1fae5; color: #059669;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="sm-stat__data">
                <span class="sm-stat__value"><?= $stats['published'] ?? 0 ?></span>
                <span class="sm-stat__label">Publiés</span>
            </div>
        </div>
        <div class="sm-stat">
            <div class="sm-stat__icon" style="background: #fef3c7; color: #d97706;">
                <i class="fas fa-map-pin"></i>
            </div>
            <div class="sm-stat__data">
                <span class="sm-stat__value"><?= $stats['quartiers'] ?? 0 ?></span>
                <span class="sm-stat__label">Quartiers</span>
            </div>
        </div>
        <div class="sm-stat">
            <div class="sm-stat__icon" style="background: #ede9fe; color: #7c3aed;">
                <i class="fas fa-city"></i>
            </div>
            <div class="sm-stat__data">
                <span class="sm-stat__value"><?= $stats['communes'] ?? 0 ?></span>
                <span class="sm-stat__label">Communes</span>
            </div>
        </div>
    </div>

    <!-- ══════ FILTRES & RECHERCHE ══════ -->
    <div class="sm-filters">
        <div class="sm-filters__left">
            <div class="sm-search">
                <i class="fas fa-search"></i>
                <input type="text" id="smSearchInput" placeholder="Rechercher un secteur..." 
                       value="<?= htmlspecialchars($search) ?>" 
                       onkeydown="if(event.key==='Enter') smApplyFilters()">
            </div>
            
            <select id="smFilterStatus" class="sm-select" onchange="smApplyFilters()">
                <option value="">Tous les statuts</option>
                <option value="published" <?= $filterStatus === 'published' ? 'selected' : '' ?>>🟢 Publiés</option>
                <option value="draft" <?= $filterStatus === 'draft' ? 'selected' : '' ?>>🟡 Brouillons</option>
            </select>
            
            <select id="smFilterType" class="sm-select" onchange="smApplyFilters()">
                <option value="">Tous les types</option>
                <?php foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                    <?= $t === 'quartier' ? '🏘️ Quartier' : '🏙️ Commune' ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="smFilterVille" class="sm-select" onchange="smApplyFilters()">
                <option value="">Toutes les villes</option>
                <?php foreach ($villes as $v): ?>
                <option value="<?= htmlspecialchars($v) ?>" <?= $filterVille === $v ? 'selected' : '' ?>>
                    <?= htmlspecialchars($v) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="sm-filters__right">
            <?php if ($filterStatus || $filterType || $filterVille || $search): ?>
            <a href="/admin/dashboard.php?page=secteurs" class="sm-btn-reset">
                <i class="fas fa-times"></i> Réinitialiser
            </a>
            <?php endif; ?>
            
            <div class="sm-view-toggle">
                <button type="button" class="sm-view-btn active" onclick="smSetView('table', this)" title="Tableau"><i class="fas fa-list"></i></button>
                <button type="button" class="sm-view-btn" onclick="smSetView('grid', this)" title="Grille"><i class="fas fa-th-large"></i></button>
            </div>
        </div>
    </div>

    <!-- ══════ VUE TABLEAU ══════ -->
    <div id="smViewTable" class="sm-view active">
        <?php if (count($secteurs) > 0): ?>
        <div class="sm-table-wrap">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th class="sm-th-check"><input type="checkbox" id="smCheckAll" onchange="smToggleAll(this)"></th>
                        <th class="sm-th-img sm-col-img">Image</th>
                        <th class="sm-th-sort <?= $sort === 'nom' ? 'sorted' : '' ?>" onclick="smSortBy('nom')">
                            Secteur <?php if ($sort === 'nom'): ?><i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                        </th>
                        <th class="sm-th-sort <?= $sort === 'ville' ? 'sorted' : '' ?>" onclick="smSortBy('ville')">
                            Ville <?php if ($sort === 'ville'): ?><i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                        </th>
                        <th class="sm-th-sort <?= $sort === 'type_secteur' ? 'sorted' : '' ?>" onclick="smSortBy('type_secteur')">
                            Type <?php if ($sort === 'type_secteur'): ?><i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                        </th>
                        <th class="sm-col-seo">SEO</th>
                        <th class="sm-th-sort <?= $sort === 'status' ? 'sorted' : '' ?>" onclick="smSortBy('status')">
                            Statut <?php if ($sort === 'status'): ?><i class="fas fa-sort-<?= $order === 'ASC' ? 'up' : 'down' ?>"></i><?php endif; ?>
                        </th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($secteurs as $s): ?>
                    <?php 
                        $heroImg = $s['hero_image'] ?? '';
                        $hasMetaTitle = !empty($s['meta_title']);
                        $hasMetaDesc = !empty($s['meta_description']);
                        $hasMetaKeys = !empty($s['meta_keywords']);
                        $seoScore = ($hasMetaTitle ? 1 : 0) + ($hasMetaDesc ? 1 : 0) + ($hasMetaKeys ? 1 : 0);
                        $seoClass = $seoScore === 3 ? 'sm-seo-good' : ($seoScore >= 1 ? 'sm-seo-partial' : 'sm-seo-none');
                        $typeBadge = ($s['type_secteur'] ?? '') === 'commune' 
                            ? '<span class="sm-badge sm-badge-commune"><i class="fas fa-city"></i> Commune</span>' 
                            : '<span class="sm-badge sm-badge-quartier"><i class="fas fa-map-pin"></i> Quartier</span>';
                    ?>
                    <tr>
                        <td class="sm-td-check"><input type="checkbox" class="sm-row-check" value="<?= $s['id'] ?>"></td>
                        <td class="sm-col-img">
                            <?php if ($heroImg): ?>
                            <div class="sm-thumb" style="background-image: url('<?= htmlspecialchars($heroImg) ?>')"></div>
                            <?php else: ?>
                            <div class="sm-thumb sm-thumb-empty"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="sm-info">
                                <a href="/admin/modules/secteurs/edit.php?id=<?= $s['id'] ?>" class="sm-name">
                                    <?= htmlspecialchars($s['nom']) ?>
                                </a>
                                <span class="sm-slug">/<?= htmlspecialchars($s['slug']) ?></span>
                                <?php if (!empty($s['hero_title'])): ?>
                                <span class="sm-hero-title"><?= htmlspecialchars(mb_substr($s['hero_title'], 0, 60)) ?><?= mb_strlen($s['hero_title'] ?? '') > 60 ? '...' : '' ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><span class="sm-ville"><?= htmlspecialchars($s['ville'] ?? '—') ?></span></td>
                        <td><?= $typeBadge ?></td>
                        <td class="sm-col-seo">
                            <div class="sm-seo <?= $seoClass ?>" title="<?= $seoScore ?>/3 critères SEO">
                                <div class="sm-seo-dots">
                                    <span class="sm-seo-dot <?= $hasMetaTitle ? 'filled' : '' ?>"></span>
                                    <span class="sm-seo-dot <?= $hasMetaDesc ? 'filled' : '' ?>"></span>
                                    <span class="sm-seo-dot <?= $hasMetaKeys ? 'filled' : '' ?>"></span>
                                </div>
                                <span class="sm-seo-score"><?= $seoScore ?>/3</span>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" class="sm-badge-status <?= $s['status'] ?>" title="Cliquer pour changer">
                                    <?= $s['status'] === 'published' ? '🟢 Publié' : '🟡 Brouillon' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="sm-actions">
                                <?php if ($s['status'] === 'published'): ?>
                                <a href="/<?= htmlspecialchars($s['slug']) ?>" target="_blank" class="sm-btn-action sm-btn-view" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                                <?php endif; ?>
                                <a href="/admin/modules/secteurs/edit.php?id=<?= $s['id'] ?>" class="sm-btn-action sm-btn-edit" title="Éditer"><i class="fas fa-edit"></i></a>
                                <a href="/admin/modules/builder-pages/index.php?type=secteur&id=<?= $s['id'] ?>" class="sm-btn-action sm-btn-builder" title="Builder Pro"><i class="fas fa-magic"></i></a>
                                
                                <div class="sm-dropdown-wrap">
                                    <button type="button" class="sm-btn-action sm-btn-more" onclick="smToggleDropdown(this)" title="Plus"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="sm-dropdown">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="duplicate">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="sm-dropdown-item"><i class="fas fa-copy"></i> Dupliquer</button>
                                        </form>
                                        <hr>
                                        <form method="POST" onsubmit="return confirm('⚠️ Supprimer « <?= htmlspecialchars(addslashes($s['nom'])) ?> » ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" class="sm-dropdown-item danger"><i class="fas fa-trash"></i> Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Bulk actions -->
        <div class="sm-bulk" id="smBulk" style="display: none;">
            <span class="sm-bulk-count"><span id="smBulkCount">0</span> sélectionné(s)</span>
            <button type="button" class="sm-btn-bulk sm-bulk-publish" onclick="smBulkAction('publish')"><i class="fas fa-check"></i> Publier</button>
            <button type="button" class="sm-btn-bulk sm-bulk-draft" onclick="smBulkAction('draft')"><i class="fas fa-eye-slash"></i> Brouillon</button>
            <button type="button" class="sm-btn-bulk sm-bulk-delete" onclick="smBulkAction('delete')"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
        
        <?php else: ?>
        <div class="sm-empty">
            <?php if ($search || $filterStatus || $filterType || $filterVille): ?>
            <i class="fas fa-search"></i>
            <h3>Aucun résultat</h3>
            <p>Aucun secteur ne correspond à vos critères</p>
            <a href="/admin/dashboard.php?page=secteurs" class="sm-btn-create" style="display: inline-flex;"><i class="fas fa-times"></i> Réinitialiser</a>
            <?php else: ?>
            <i class="fas fa-map-marker-alt"></i>
            <h3>Aucun secteur créé</h3>
            <p>Créez votre premier secteur pour Bordeaux et alentours</p>
            <a href="/admin/modules/secteurs/edit.php?action=create" class="sm-btn-create" style="display: inline-flex;"><i class="fas fa-plus"></i> Créer</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════ VUE GRILLE ══════ -->
    <div id="smViewGrid" class="sm-view">
        <?php if (count($secteurs) > 0): ?>
        <div class="sm-grid">
            <?php foreach ($secteurs as $s): ?>
            <?php $heroImg = $s['hero_image'] ?? ''; ?>
            <div class="sm-card">
                <div class="sm-card__img" style="<?= $heroImg ? "background-image: url('" . htmlspecialchars($heroImg) . "')" : '' ?>">
                    <?php if (!$heroImg): ?><div class="sm-card__no-img"><i class="fas fa-image"></i></div><?php endif; ?>
                    <div class="sm-card__overlay">
                        <span class="sm-card__badge-sm <?= $s['status'] ?>"><?= $s['status'] === 'published' ? '🟢 Publié' : '🟡 Brouillon' ?></span>
                        <span class="sm-card__type-sm"><?= ($s['type_secteur'] ?? '') === 'commune' ? '🏙️' : '🏘️' ?> <?= ucfirst($s['type_secteur'] ?? 'quartier') ?></span>
                    </div>
                </div>
                <div class="sm-card__body">
                    <h4 class="sm-card__title"><?= htmlspecialchars($s['nom']) ?></h4>
                    <p class="sm-card__ville"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($s['ville'] ?? '—') ?></p>
                    <?php if (!empty($s['hero_subtitle'])): ?>
                    <p class="sm-card__subtitle"><?= htmlspecialchars(mb_substr($s['hero_subtitle'], 0, 80)) ?>...</p>
                    <?php endif; ?>
                </div>
                <div class="sm-card__footer">
                    <a href="/admin/modules/secteurs/edit.php?id=<?= $s['id'] ?>" class="sm-btn-action sm-btn-edit"><i class="fas fa-edit"></i> Éditer</a>
                    <a href="/admin/modules/builder-pages/index.php?type=secteur&id=<?= $s['id'] ?>" class="sm-btn-action sm-btn-builder"><i class="fas fa-magic"></i> Builder</a>
                    <?php if ($s['status'] === 'published'): ?>
                    <a href="/<?= htmlspecialchars($s['slug']) ?>" target="_blank" class="sm-btn-action sm-btn-view"><i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ══════ JAVASCRIPT ══════ -->
<script>
function smApplyFilters() {
    const p = new URLSearchParams();
    p.set('page', 'secteurs');
    const q = document.getElementById('smSearchInput')?.value?.trim();
    const s = document.getElementById('smFilterStatus')?.value;
    const t = document.getElementById('smFilterType')?.value;
    const v = document.getElementById('smFilterVille')?.value;
    if (q) p.set('q', q);
    if (s) p.set('status', s);
    if (t) p.set('type_secteur', t);
    if (v) p.set('ville', v);
    window.location.href = '/admin/dashboard.php?' + p.toString();
}

function smSortBy(col) {
    const p = new URLSearchParams(window.location.search);
    const cs = p.get('sort');
    const co = p.get('order') || 'ASC';
    p.set('page', 'secteurs');
    p.set('sort', col);
    p.set('order', (cs === col && co === 'ASC') ? 'DESC' : 'ASC');
    window.location.href = '/admin/dashboard.php?' + p.toString();
}

function smSetView(view, btn) {
    document.querySelectorAll('.sm-view-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.querySelectorAll('.sm-view').forEach(v => v.classList.remove('active'));
    document.getElementById(view === 'grid' ? 'smViewGrid' : 'smViewTable')?.classList.add('active');
    localStorage.setItem('sm_view', view);
}

function smToggleAll(master) {
    document.querySelectorAll('.sm-row-check').forEach(cb => cb.checked = master.checked);
    smUpdateBulk();
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('sm-row-check')) smUpdateBulk();
});

function smUpdateBulk() {
    const checked = document.querySelectorAll('.sm-row-check:checked');
    const bulk = document.getElementById('smBulk');
    const count = document.getElementById('smBulkCount');
    if (checked.length > 0) { bulk.style.display = 'flex'; count.textContent = checked.length; }
    else { bulk.style.display = 'none'; }
}

async function smBulkAction(action) {
    const ids = Array.from(document.querySelectorAll('.sm-row-check:checked')).map(cb => cb.value);
    if (!ids.length) return;
    if (action === 'delete' && !confirm('⚠️ Supprimer ' + ids.length + ' secteur(s) ?')) return;
    try {
        const res = await fetch('/admin/modules/secteurs/api/bulk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ids })
        });
        const data = await res.json();
        if (data.success) window.location.reload();
        else alert('Erreur: ' + (data.error || '?'));
    } catch (e) { alert('Erreur réseau'); }
}

function smToggleDropdown(btn) {
    const menu = btn.nextElementSibling;
    document.querySelectorAll('.sm-dropdown.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
    menu.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.sm-dropdown-wrap')) {
        document.querySelectorAll('.sm-dropdown.open').forEach(m => m.classList.remove('open'));
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const sv = localStorage.getItem('sm_view');
    if (sv === 'grid') smSetView('grid', document.querySelector('.sm-view-btn:last-child'));
    document.querySelectorAll('.sm-alert').forEach(a => {
        setTimeout(() => { a.style.opacity = '0'; a.style.transition = 'opacity 0.3s'; setTimeout(() => a.remove(), 300); }, 4000);
    });
});
</script>