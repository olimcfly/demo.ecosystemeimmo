<?php
/**
 * Design — Headers Listing
 * /admin/modules/builder/headers.php
 * Chargé via $designMapping['design-headers']
 */

$connection = null;
if (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
elseif (isset($db) && $db instanceof PDO) $connection = $db;
else {
    $dbConfig = __DIR__ . '/../../config/database.php';
    if (file_exists($dbConfig)) {
        require_once $dbConfig;
        if (isset($db) && $db instanceof PDO) $connection = $db;
        elseif (isset($pdo) && $pdo instanceof PDO) $connection = $pdo;
    }
}

$headers = [];
$dbError = null;
if ($connection) {
    try {
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $connection->query("SELECT * FROM headers ORDER BY is_default DESC, updated_at DESC");
        $headers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) { $dbError = $e->getMessage(); }
}
?>

<style>
.dh-nav{display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:10px;margin-bottom:24px;width:fit-content}
.dh-nav a{padding:8px 18px;border-radius:7px;font-size:13px;font-weight:500;color:#64748b;text-decoration:none;transition:all .2s}
.dh-nav a:hover{color:#334155;background:rgba(255,255,255,.5)}
.dh-nav a.active{background:#fff;color:#1e293b;font-weight:600;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.dh-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.dh-top h2{font-size:18px;font-weight:600;color:#1e293b;margin:0;display:flex;align-items:center;gap:10px}
.dh-top h2 i{color:#3b82f6}
.btn-create{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff!important;border:none;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;transition:all .2s;box-shadow:0 2px 8px rgba(59,130,246,.3)}
.btn-create:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,.4)}
.dh-empty{text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:2px dashed #e2e8f0}
.dh-empty i{font-size:48px;color:#cbd5e1;margin-bottom:16px}
.dh-empty p{color:#94a3b8;font-size:15px;margin:0 0 20px}
.dh-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
.dh-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;transition:all .25s;position:relative}
.dh-card:hover{border-color:#93c5fd;box-shadow:0 4px 20px rgba(59,130,246,.1);transform:translateY(-2px)}
.dh-card.is-default{border-color:#3b82f6;box-shadow:0 0 0 1px #3b82f6}
.dh-badge{position:absolute;top:12px;right:12px;background:#3b82f6;color:#fff;font-size:11px;font-weight:600;padding:4px 10px;border-radius:6px;z-index:2;display:flex;align-items:center;gap:4px}
.dh-preview{height:120px;background:#f8fafc;border-bottom:1px solid #f1f5f9;overflow:hidden;display:flex;align-items:center;justify-content:center}
.dh-preview iframe{width:200%;height:200%;border:none;transform:scale(.5);transform-origin:top left;pointer-events:none}
.dh-preview .ph{color:#cbd5e1;font-size:40px}
.dh-body{padding:16px 18px}
.dh-name{font-size:16px;font-weight:600;color:#1e293b;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dh-meta{display:flex;align-items:center;gap:12px;font-size:12px;color:#94a3b8;flex-wrap:wrap}
.dh-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.dh-dot.active{background:#22c55e}.dh-dot.inactive{background:#ef4444}.dh-dot.draft{background:#f59e0b}
.dh-actions{display:flex;border-top:1px solid #f1f5f9}
.dh-actions a,.dh-actions button{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:11px 0;border:none;background:transparent;font-size:13px;font-weight:500;color:#64748b;cursor:pointer;text-decoration:none;transition:all .15s;font-family:inherit}
.dh-actions a:hover,.dh-actions button:hover{background:#f8fafc}
.dh-actions .act-edit:hover{color:#3b82f6}
.dh-actions .act-def:hover{color:#8b5cf6}
.dh-actions .act-del:hover{color:#ef4444}
.dh-sep{width:1px;background:#f1f5f9;flex:0}
.dh-toast{position:fixed;bottom:30px;right:30px;padding:14px 24px;border-radius:12px;color:#fff;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;z-index:10001;box-shadow:0 8px 30px rgba(0,0,0,.15);transform:translateY(100px);opacity:0;transition:all .3s;pointer-events:none}
.dh-toast.show{transform:translateY(0);opacity:1}
.dh-toast.ok{background:#10b981}.dh-toast.ko{background:#ef4444}
.dh-ovl{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;justify-content:center;align-items:center}
.dh-ovl.show{display:flex}
.dh-dlg{background:#fff;border-radius:16px;padding:30px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center}
.dh-dlg .ico{width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#ef4444;font-size:24px}
.dh-dlg h3{font-size:18px;font-weight:600;color:#1f2937;margin:0 0 8px}
.dh-dlg p{font-size:14px;color:#6b7280;margin:0 0 24px;line-height:1.5}
.dh-dlg .hl{font-weight:600;color:#1f2937}
.dh-dlg .warn{background:#fef3cd;border:1px solid #fbbf24;padding:10px 14px;border-radius:8px;font-size:12px;color:#92400e;margin:0 0 16px;text-align:left}
.dh-dlg .warn i{margin-right:6px}
.dh-btns{display:flex;gap:10px}
.dh-btns button{flex:1;padding:11px 20px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;border:none;transition:all .2s;font-family:inherit}
.dh-btns .btn-c{background:#f3f4f6;color:#374151}.dh-btns .btn-c:hover{background:#e5e7eb}
.dh-btns .btn-d{background:#ef4444;color:#fff}.dh-btns .btn-d:hover{background:#dc2626}
.dh-card.out{opacity:0;transform:scale(.9);transition:all .3s}
.dh-err{background:#fef2f2;border:1px solid #fca5a5;padding:14px 18px;border-radius:10px;margin:0 0 16px;font-size:13px;color:#991b1b;display:flex;align-items:center;gap:10px}
@media(max-width:768px){.dh-grid{grid-template-columns:1fr}.dh-top{flex-direction:column;gap:12px;align-items:flex-start}}
</style>

<div class="dh-nav">
    <a href="/admin/dashboard.php?page=design-headers" class="active"><i class="fas fa-arrow-up"></i> Entêtes</a>
    <a href="/admin/dashboard.php?page=design-footers"><i class="fas fa-arrow-down"></i> Pieds de page</a>
</div>

<?php if (!$connection): ?>
    <div class="dh-err"><i class="fas fa-exclamation-triangle"></i> Aucune connexion DB.</div>
<?php elseif ($dbError): ?>
    <div class="dh-err"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<div class="dh-top">
    <h2><i class="fas fa-window-maximize"></i> Mes Headers (<?= count($headers) ?>)</h2>
    <a href="/admin/dashboard.php?page=design-edit-header&action=create" class="btn-create">
        <i class="fas fa-plus"></i> Créer un header
    </a>
</div>

<?php if (empty($headers)): ?>
    <div class="dh-empty">
        <i class="fas fa-layer-group"></i>
        <p>Aucun header créé pour le moment.</p>
        <a href="/admin/dashboard.php?page=design-edit-header&action=create" class="btn-create">
            <i class="fas fa-plus"></i> Créer mon premier header
        </a>
    </div>
<?php else: ?>
    <div class="dh-grid">
        <?php foreach ($headers as $h):
            $st   = $h['status'] ?? 'draft';
            $tp   = $h['type'] ?? 'standard';
            $date = date('d/m/Y', strtotime($h['updated_at'] ?? $h['created_at'] ?? 'now'));
            $prev = $h['builder_content'] ?? ($h['content'] ?? ($h['custom_html'] ?? ''));
            $css  = $h['custom_css'] ?? '';
        ?>
            <div class="dh-card <?= !empty($h['is_default']) ? 'is-default' : '' ?>" data-id="<?= (int)$h['id'] ?>">
                <?php if (!empty($h['is_default'])): ?>
                    <span class="dh-badge"><i class="fas fa-star"></i> Par défaut</span>
                <?php endif; ?>

                <div class="dh-preview">
                    <?php if (!empty($prev)): ?>
                        <iframe srcdoc="<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{margin:0;font-family:Inter,sans-serif;font-size:14px;overflow:hidden}<?= htmlspecialchars($css) ?></style></head><body><?= htmlspecialchars($prev) ?></body></html>" sandbox="allow-same-origin" loading="lazy"></iframe>
                    <?php else: ?>
                        <i class="fas fa-window-maximize ph"></i>
                    <?php endif; ?>
                </div>

                <div class="dh-body">
                    <h3 class="dh-name"><?= htmlspecialchars($h['name']) ?></h3>
                    <div class="dh-meta">
                        <span><span class="dh-dot <?= $st ?>"></span> <?= ucfirst($st) ?></span>
                        <span><i class="fas fa-tag"></i> <?= ucfirst($tp) ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?= $date ?></span>
                    </div>
                </div>

                <div class="dh-actions">
                    <a href="/admin/dashboard.php?page=design-edit-header&id=<?= (int)$h['id'] ?>" class="act-edit">
                        <i class="fas fa-pen"></i> Éditer
                    </a>
                    <span class="dh-sep"></span>
                    <?php if (empty($h['is_default'])): ?>
                        <button class="act-def" onclick="dhSetDef(<?= (int)$h['id'] ?>)">
                            <i class="fas fa-star"></i> Par défaut
                        </button>
                        <span class="dh-sep"></span>
                    <?php endif; ?>
                    <button class="act-del" onclick="dhAskDel(<?= (int)$h['id'] ?>, '<?= addslashes(htmlspecialchars($h['name'])) ?>', <?= !empty($h['is_default']) ? 'true' : 'false' ?>)">
                        <i class="fas fa-trash-alt"></i> Suppr.
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal suppression -->
<div class="dh-ovl" id="dhOvl">
    <div class="dh-dlg">
        <div class="ico"><i class="fas fa-trash-alt"></i></div>
        <h3>Supprimer ce header ?</h3>
        <div id="dhWarn" class="warn" style="display:none">
            <i class="fas fa-exclamation-triangle"></i>
            <span id="dhWarnTxt"></span>
        </div>
        <p>Vous allez supprimer <span class="hl" id="dhDN"></span>.<br>
           Les menus associés seront aussi supprimés.<br>
           Les pages liées seront remises au header par défaut.</p>
        <div class="dh-btns">
            <button class="btn-c" onclick="dhClose()">Annuler</button>
            <button class="btn-d" id="dhDB" onclick="dhDoDel()">Supprimer</button>
        </div>
    </div>
</div>
<div class="dh-toast" id="dhT"></div>

<script>
const DH_API = '/admin/modules/builder/api';
let _dhDI = 0, _dhForce = false;

// ─── Set default ───
function dhSetDef(id) {
    fetch(DH_API + '/set-default.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ type: 'header', id: id })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { dhToast(d.message || 'Header par défaut mis à jour !', 'ok'); setTimeout(() => location.reload(), 800); }
        else dhToast(d.error || 'Erreur', 'ko');
    })
    .catch(() => dhToast('Erreur réseau', 'ko'));
}

// ─── Delete ───
function dhAskDel(id, name, isDef) {
    _dhDI = id;
    _dhForce = false;
    document.getElementById('dhDN').textContent = name;
    document.getElementById('dhWarn').style.display = isDef ? 'block' : 'none';
    document.getElementById('dhWarnTxt').textContent = isDef ? 'C\'est le header par défaut. La suppression nécessitera force=true.' : '';
    document.getElementById('dhOvl').classList.add('show');
}
function dhClose() { document.getElementById('dhOvl').classList.remove('show'); _dhDI = 0; }

function dhDoDel() {
    if (!_dhDI) return;
    const btn = document.getElementById('dhDB');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(DH_API + '/delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ type: 'header', id: _dhDI, force: true })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            dhClose();
            const c = document.querySelector('.dh-card[data-id="' + _dhDI + '"]');
            if (c) { c.classList.add('out'); setTimeout(() => c.remove(), 300); }
            dhToast(d.message || 'Header supprimé !', 'ok');
        } else if (d.needs_confirmation) {
            // Afficher le warning et re-tenter avec force
            document.getElementById('dhWarn').style.display = 'block';
            document.getElementById('dhWarnTxt').textContent = d.error;
        } else {
            dhToast(d.error || 'Erreur', 'ko');
        }
        btn.disabled = false;
        btn.innerHTML = 'Supprimer';
    })
    .catch(() => { dhToast('Erreur réseau', 'ko'); btn.disabled = false; btn.innerHTML = 'Supprimer'; });
}

function dhToast(m, t) {
    const e = document.getElementById('dhT');
    e.innerHTML = '<i class="fas fa-' + (t === 'ok' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + m;
    e.className = 'dh-toast ' + t + ' show';
    setTimeout(() => e.classList.remove('show'), 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') dhClose(); });
document.getElementById('dhOvl').addEventListener('click', function(e) { if (e.target === this) dhClose(); });
</script>