<?php
/**
 * Module Biens Immobiliers — /admin/modules/biens/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> '.$e->getMessage().'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db = $pdo;

$pdo->exec("CREATE TABLE IF NOT EXISTS biens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(50) DEFAULT NULL,
    titre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) DEFAULT NULL,
    type ENUM('appartement','maison','villa','terrain','commerce','parking','immeuble','autre') DEFAULT 'appartement',
    transaction_type ENUM('vente','location') DEFAULT 'vente',
    prix DECIMAL(12,2) DEFAULT 0,
    surface INT DEFAULT 0,
    pieces INT DEFAULT 0,
    chambres INT DEFAULT 0,
    sdb INT DEFAULT 0,
    etage VARCHAR(20) DEFAULT NULL,
    description LONGTEXT,
    adresse VARCHAR(255) DEFAULT NULL,
    ville VARCHAR(100) DEFAULT 'Bordeaux',
    code_postal VARCHAR(10) DEFAULT NULL,
    quartier VARCHAR(100) DEFAULT NULL,
    latitude DECIMAL(10,8) DEFAULT NULL,
    longitude DECIMAL(11,8) DEFAULT NULL,
    dpe VARCHAR(5) DEFAULT NULL,
    ges VARCHAR(5) DEFAULT NULL,
    atouts JSON DEFAULT NULL,
    images JSON DEFAULT NULL,
    image_principale VARCHAR(500) DEFAULT NULL,
    status ENUM('disponible','sous_offre','vendu','loue','archive') DEFAULT 'disponible',
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ref (reference),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_ville (ville),
    INDEX idx_prix (prix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$flash = ''; $flashType = 'success';
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $tk = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $tk !== $_SESSION['csrf_token']) {
        $flash = 'Erreur CSRF.'; $flashType = 'error';
    } else {
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    $r = $pdo->prepare("SELECT titre FROM biens WHERE id=?"); $r->execute([$id]); $t = $r->fetchColumn();
                    if (!$t) throw new Exception('Bien introuvable.');
                    $pdo->prepare("DELETE FROM biens WHERE id=?")->execute([$id]);
                    $flash = "Bien « {$t} » supprimé.";
                    break;
                case 'toggle_status':
                    $id = (int)($_POST['id'] ?? 0);
                    $ns = $_POST['new_status'] ?? 'disponible';
                    $allowed = ['disponible','sous_offre','vendu','loue','archive'];
                    if (!in_array($ns, $allowed)) throw new Exception('Statut invalide.');
                    $pdo->prepare("UPDATE biens SET status=? WHERE id=?")->execute([$ns, $id]);
                    $flash = "Statut mis à jour → " . ucfirst(str_replace('_',' ',$ns));
                    break;
                case 'toggle_featured':
                    $id = (int)($_POST['id'] ?? 0);
                    $pdo->prepare("UPDATE biens SET featured = NOT featured WHERE id=?")->execute([$id]);
                    $flash = "Mise en avant modifiée.";
                    break;
            }
        } catch (Exception $e) { $flash = $e->getMessage(); $flashType = 'error'; }
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$typeFilter = $_GET['type'] ?? '';
$villeFilter = $_GET['ville'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, (int)($_GET['pg'] ?? 1));
$perPage = 20;

$counts = ['all'=>0,'disponible'=>0,'vendu'=>0,'sous_offre'=>0,'loue'=>0,'archive'=>0];
try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM biens")->fetchColumn();
    foreach (['disponible','vendu','sous_offre','loue','archive'] as $s) {
        $counts[$s] = (int)$pdo->query("SELECT COUNT(*) FROM biens WHERE status='{$s}'")->fetchColumn();
    }
} catch(Exception $e) {}

$where = "WHERE 1=1";
$params = [];
if ($filter !== 'all' && isset($counts[$filter])) { $where .= " AND status = ?"; $params[] = $filter; }
if ($search) { $where .= " AND (titre LIKE ? OR reference LIKE ? OR ville LIKE ? OR quartier LIKE ?)"; $params = array_merge($params, ["%{$search}%","%{$search}%","%{$search}%","%{$search}%"]); }
if ($typeFilter) { $where .= " AND type = ?"; $params[] = $typeFilter; }
if ($villeFilter) { $where .= " AND ville = ?"; $params[] = $villeFilter; }

$totalFiltered = 0;
try {
    $cs = $pdo->prepare("SELECT COUNT(*) FROM biens {$where}"); $cs->execute($params); $totalFiltered = (int)$cs->fetchColumn();
} catch(Exception $e) {}
$totalPages = max(1, ceil($totalFiltered / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$allowedSort = ['titre','type','ville','prix','surface','status','created_at'];
if (!in_array($sort, $allowedSort)) $sort = 'created_at';

$biens = [];
try {
    $st = $pdo->prepare("SELECT * FROM biens {$where} ORDER BY featured DESC, {$sort} {$order} LIMIT {$perPage} OFFSET {$offset}");
    $st->execute($params); $biens = $st->fetchAll();
} catch(Exception $e) {}

$villes = [];
try { $villes = $pdo->query("SELECT DISTINCT ville FROM biens WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e) {}

$statusLabels = ['disponible'=>'Disponible','sous_offre'=>'Sous offre','vendu'=>'Vendu','loue'=>'Loué','archive'=>'Archivé'];
$statusColors = ['disponible'=>'active','sous_offre'=>'warning','vendu'=>'error','loue'=>'info','archive'=>'inactive'];
$typeLabels = ['appartement'=>'Appartement','maison'=>'Maison','villa'=>'Villa','terrain'=>'Terrain','commerce'=>'Commerce','parking'=>'Parking','immeuble'=>'Immeuble','autre'=>'Autre'];
$typeIcons = ['appartement'=>'building','maison'=>'home','villa'=>'hotel','terrain'=>'mountain','commerce'=>'store','parking'=>'car','immeuble'=>'city','autre'=>'cube'];

function biensSortUrl($col) {
    global $sort, $order;
    $newO = ($sort === $col && $order === 'DESC') ? 'ASC' : 'DESC';
    $p = $_GET; $p['sort'] = $col; $p['order'] = $newO; unset($p['pg']);
    return '?' . http_build_query($p);
}
function biensSortIcon($col) {
    global $sort, $order;
    if ($sort !== $col) return '<i class="fas fa-sort" style="opacity:.3"></i>';
    return $order === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
}
?>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-building"></i> Biens Immobiliers</h1>
        <p>Catalogue des propriétés en vente et location — Eduardo De Sul, Bordeaux</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= $counts['all'] ?></div><div class="mod-stat-label">Total</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $counts['disponible'] ?></div><div class="mod-stat-label">Disponibles</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $counts['vendu'] + $counts['loue'] ?></div><div class="mod-stat-label">Conclus</div></div>
    </div>
</div>

<?php if ($flash): ?>
<div class="mod-flash mod-flash-<?= $flashType ?>"><i class="fas fa-<?= $flashType==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="mod-toolbar">
    <div class="mod-toolbar-left">
        <div class="mod-filters">
            <a href="?page=biens&filter=all" class="mod-filter <?= $filter==='all'?'active':'' ?>"><i class="fas fa-layer-group"></i> Tous <span class="mod-badge mod-badge-inactive"><?= $counts['all'] ?></span></a>
            <a href="?page=biens&filter=disponible" class="mod-filter <?= $filter==='disponible'?'active':'' ?>"><i class="fas fa-check-circle"></i> Dispo <span class="mod-badge mod-badge-inactive"><?= $counts['disponible'] ?></span></a>
            <a href="?page=biens&filter=sous_offre" class="mod-filter <?= $filter==='sous_offre'?'active':'' ?>"><i class="fas fa-handshake"></i> Offre <span class="mod-badge mod-badge-inactive"><?= $counts['sous_offre'] ?></span></a>
            <a href="?page=biens&filter=vendu" class="mod-filter <?= $filter==='vendu'?'active':'' ?>"><i class="fas fa-gavel"></i> Vendus <span class="mod-badge mod-badge-inactive"><?= $counts['vendu'] ?></span></a>
            <a href="?page=biens&filter=loue" class="mod-filter <?= $filter==='loue'?'active':'' ?>"><i class="fas fa-key"></i> Loués <span class="mod-badge mod-badge-inactive"><?= $counts['loue'] ?></span></a>
        </div>
    </div>
    <div class="mod-toolbar-right">
        <form class="mod-search" method="GET">
            <input type="hidden" name="page" value="biens">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Titre, réf, ville...">
        </form>
        <select onchange="location.href='?page=biens&filter=<?= $filter ?>&type='+this.value" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
            <option value="">Tous types</option>
            <?php foreach ($typeLabels as $tk=>$tl): ?>
            <option value="<?= $tk ?>" <?= $typeFilter===$tk?'selected':'' ?>><?= $tl ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($villes)): ?>
        <select onchange="location.href='?page=biens&filter=<?= $filter ?>&ville='+this.value" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
            <option value="">Toutes villes</option>
            <?php foreach ($villes as $v): ?>
            <option value="<?= htmlspecialchars($v) ?>" <?= $villeFilter===$v?'selected':'' ?>><?= htmlspecialchars($v) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="?page=biens&action=create" class="mod-btn mod-btn-primary"><i class="fas fa-plus"></i> Ajouter un bien</a>
    </div>
</div>

<?php if (empty($biens)): ?>
<div class="mod-empty"><i class="fas fa-building"></i><h3>Aucun bien trouvé</h3><p><?= $search ? "Aucun résultat pour « {$search} »." : 'Ajoutez votre premier bien immobilier.' ?></p><a href="?page=biens&action=create" class="mod-btn mod-btn-primary mod-mt"><i class="fas fa-plus"></i> Ajouter un bien</a></div>
<?php else: ?>

<div class="mod-table-wrap">
    <table class="mod-table">
        <thead>
            <tr>
                <th><a href="<?= biensSortUrl('titre') ?>">Bien <?= biensSortIcon('titre') ?></a></th>
                <th><a href="<?= biensSortUrl('type') ?>">Type <?= biensSortIcon('type') ?></a></th>
                <th><a href="<?= biensSortUrl('ville') ?>">Localisation <?= biensSortIcon('ville') ?></a></th>
                <th><a href="<?= biensSortUrl('prix') ?>">Prix <?= biensSortIcon('prix') ?></a></th>
                <th><a href="<?= biensSortUrl('surface') ?>">Surface <?= biensSortIcon('surface') ?></a></th>
                <th><a href="<?= biensSortUrl('status') ?>">Statut <?= biensSortIcon('status') ?></a></th>
                <th><a href="<?= biensSortUrl('created_at') ?>">Date <?= biensSortIcon('created_at') ?></a></th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($biens as $b):
            $st = $b['status'] ?? 'disponible';
            $tp = $b['type'] ?? 'autre';
            $ref = $b['reference'] ?? '';
            $icon = $typeIcons[$tp] ?? 'cube';
        ?>
            <tr>
                <td>
                    <div class="mod-flex mod-items-center mod-gap-sm">
                        <?php if ($b['featured']): ?><i class="fas fa-star" style="color:var(--amber);font-size:.7rem" title="Mis en avant"></i><?php endif; ?>
                        <div>
                            <strong style="font-weight:600;color:var(--text)"><?= htmlspecialchars($b['titre'] ?? 'Sans titre') ?></strong>
                            <?php if ($ref): ?><div class="mod-text-xs mod-text-muted">Réf: <?= htmlspecialchars($ref) ?></div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><span class="mod-tag"><i class="fas fa-<?= $icon ?>" style="margin-right:4px"></i><?= $typeLabels[$tp] ?? ucfirst($tp) ?></span></td>
                <td>
                    <span class="mod-text-sm"><?= htmlspecialchars($b['ville'] ?? '') ?></span>
                    <?php if ($b['quartier'] ?? ''): ?><div class="mod-text-xs mod-text-muted"><?= htmlspecialchars($b['quartier']) ?></div><?php endif; ?>
                </td>
                <td><strong style="color:var(--text)"><?= number_format($b['prix'] ?? 0, 0, ',', ' ') ?> €</strong>
                    <?php if (($b['transaction_type'] ?? '') === 'location'): ?><div class="mod-text-xs mod-text-muted">/mois</div><?php endif; ?>
                </td>
                <td>
                    <?php if ($b['surface']): ?><span class="mod-text-sm"><?= $b['surface'] ?> m²</span><?php endif; ?>
                    <?php if ($b['pieces']): ?><div class="mod-text-xs mod-text-muted"><?= $b['pieces'] ?>p / <?= $b['chambres'] ?? '?' ?>ch</div><?php endif; ?>
                </td>
                <td><span class="mod-badge mod-badge-<?= $statusColors[$st] ?? 'inactive' ?>"><?= $statusLabels[$st] ?? ucfirst($st) ?></span></td>
                <td><span class="mod-date"><?= isset($b['created_at']) ? date('d/m/Y', strtotime($b['created_at'])) : '—' ?></span></td>
                <td class="col-actions">
                    <div class="mod-actions">
                        <a href="?page=biens&action=edit&id=<?= $b['id'] ?>" class="mod-btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
                        <?php if ($b['slug'] ?? ''): ?>
                        <a href="/biens/<?= htmlspecialchars($b['slug']) ?>" target="_blank" class="mod-btn-icon" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <form method="POST" class="mod-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="toggle_featured">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="mod-btn-icon <?= $b['featured'] ? 'warning' : '' ?>" title="Mettre en avant"><i class="fas fa-star"></i></button>
                        </form>
                        <form method="POST" class="mod-inline-form" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($b['titre'] ?? '')) ?> » ?')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" class="mod-btn-icon danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="mod-flex mod-items-center" style="justify-content:space-between;margin-top:16px">
    <span class="mod-text-xs mod-text-muted"><?= $totalFiltered ?> bien(s) — page <?= $page ?>/<?= $totalPages ?></span>
    <div class="mod-pagination">
        <?php if ($page > 1): $p=$_GET; $p['pg']=$page-1; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): $p=$_GET; $p['pg']=$i; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): $p=$_GET; $p['pg']=$page+1; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>