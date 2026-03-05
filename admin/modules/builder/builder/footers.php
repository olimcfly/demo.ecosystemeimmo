<?php
/**
 * Design — Footers Listing
 * /admin/modules/builder/footers.php
 * 
 * Tous les liens "Éditer" → Builder Pro
 * Plus de gestionnaire dédié.
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

$footers = [];
$dbError = null;
if ($connection) {
    try {
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $connection->query("SELECT * FROM footers ORDER BY is_default DESC, updated_at DESC");
        $footers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Exception $e) { $dbError = $e->getMessage(); }
}
?>

<style>
.df-nav{display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:10px;margin-bottom:24px;width:fit-content}
.df-nav a{padding:8px 18px;border-radius:7px;font-size:13px;font-weight:500;color:#64748b;text-decoration:none;transition:all .2s}
.df-nav a:hover{color:#334155;background:rgba(255,255,255,.5)}
.df-nav a.active{background:#fff;color:#1e293b;font-weight:600;box-shadow:0 1px 3px rgba(0,0,0,.08)}
.df-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.df-top h2{font-size:18px;font-weight:600;color:#1e293b;margin:0;display:flex;align-items:center;gap:10px}
.df-top h2 i{color:#8b5cf6}
.btn-create-f{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff!important;border:none;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;transition:all .2s;box-shadow:0 2px 8px rgba(139,92,246,.3)}
.btn-create-f:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(139,92,246,.4)}
.df-empty{text-align:center;padding:60px 20px;background:#f8fafc;border-radius:16px;border:2px dashed #e2e8f0}
.df-empty i{font-size:48px;color:#cbd5e1;margin-bottom:16px}
.df-empty p{color:#94a3b8;font-size:15px;margin:0 0 20px}
.df-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px}
.df-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;transition:all .25s;position:relative}
.df-card:hover{border-color:#c4b5fd;box-shadow:0 4px 20px rgba(139,92,246,.1);transform:translateY(-2px)}
.df-card.is-default{border-color:#8b5cf6;box-shadow:0 0 0 1px #8b5cf6}
.df-badge{position:absolute;top:12px;right:12px;background:#8b5cf6;color:#fff;font-size:11px;font-weight:600;padding:4px 10px;border-radius:6px;z-index:2;display:flex;align-items:center;gap:4px}
.df-preview{height:120px;background:#f8fafc;border-bottom:1px solid #f1f5f9;overflow:hidden;display:flex;align-items:center;justify-content:center}
.df-preview iframe{width:200%;height:200%;border:none;transform:scale(.5);transform-origin:top left;pointer-events:none}
.df-preview .ph{color:#cbd5e1;font-size:40px}
.df-body{padding:16px 18px}
.df-name{font-size:16px;font-weight:600;color:#1e293b;margin:0 0 6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.df-meta{display:flex;align-items:center;gap:12px;font-size:12px;color:#94a3b8;flex-wrap:wrap}
.df-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.df-dot.active{background:#22c55e}.df-dot.inactive{background:#ef4444}.df-dot.draft{background:#f59e0b}
.df-actions{display:flex;border-top:1px solid #f1f5f9}
.df-actions a,.df-actions button{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:11px 0;border:none;background:transparent;font-size:13px;font-weight:500;color:#64748b;cursor:pointer;text-decoration:none;transition:all .15s;font-family:inherit}
.df-actions a:hover,.df-actions button:hover{background:#f8fafc}
.df-actions .act-edit:hover{color:#8b5cf6}
.df-actions .act-def:hover{color:#3b82f6}
.df-actions .act-del:hover{color:#ef4444}
.df-sep{width:1px;background:#f1f5f9;flex:0}
.df-toast{position:fixed;bottom:30px;right:30px;padding:14px 24px;border-radius:12px;color:#fff;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;z-index:10001;box-shadow:0 8px 30px rgba(0,0,0,.15);transform:translateY(100px);opacity:0;transition:all .3s;pointer-events:none}
.df-toast.show{transform:translateY(0);opacity:1}
.df-toast.ok{background:#10b981}.df-toast.ko{background:#ef4444}
.df-ovl{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:10000;justify-content:center;align-items:center}
.df-ovl.show{display:flex}
.df-dlg{background:#fff;border-radius:16px;padding:30px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);text-align:center}
.df-dlg .ico{width:56px;height:56px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#ef4444;font-size:24px}
.df-dlg h3{font-size:18px;font-weight:600;color:#1f2937;margin:0 0 8px}
.df-dlg p{font-size:14px;color:#6b7280;margin:0 0 24px;line-height:1.5}
.df-dlg .hl{font-weight:600;color:#1f2937}
.df-btns{display:flex;gap:10px}
.df-btns button{flex:1;padding:11px 20px;border-radius:10px;font-size:14px;font-weight:500;cursor:pointer;border:none;transition:all .2s;font-family:inherit}
.df-btns .btn-c{background:#f3f4f6;color:#374151}.df-btns .btn-c:hover{background:#e5e7eb}
.df-btns .btn-d{background:#ef4444;color:#fff}.df-btns .btn-d:hover{background:#dc2626}
.df-card.out{opacity:0;transform:scale(.9);transition:all .3s}
.df-err{background:#fef2f2;border:1px solid #fca5a5;padding:14px 18px;border-radius:10px;margin:0 0 16px;font-size:13px;color:#991b1b;display:flex;align-items:center;gap:10px}
@media(max-width:768px){.df-grid{grid-template-columns:1fr}.df-top{flex-direction:column;gap:12px;align-items:flex-start}}
</style>

<div class="df-nav">
    <a href="/admin/dashboard.php?page=design-headers"><i class="fas fa-arrow-up"></i> Entêtes</a>
    <a href="/admin/dashboard.php?page=design-footers" class="active"><i class="fas fa-arrow-down"></i> Pieds de page</a>
</div>

<?php if (!$connection): ?>
    <div class="df-err"><i class="fas fa-exclamation-triangle"></i> Aucune connexion DB.</div>
<?php elseif ($dbError): ?>
    <div class="df-err"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($dbError) ?></div>
<?php endif; ?>

<div class="df-top">
    <h2><i class="fas fa-window-minimize"></i> Mes Footers (<?= count($footers) ?>)</h2>
    <!-- ★ Création → passe par edit-footer.php qui redirige vers Builder -->
    <a href="/admin/dashboard.php?page=design-edit-footer&action=create" class="btn-create-f">
        <i class="fas fa-plus"></i> Créer un footer
    </a>
</div>

<?php if (empty($footers)): ?>
    <div class="df-empty">
        <i class="fas fa-layer-group"></i>
        <p>Aucun footer créé pour le moment.</p>
        <a href="/admin/dashboard.php?page=design-edit-footer&action=create" class="btn-create-f">
            <i class="fas fa-plus"></i> Créer mon premier footer
        </a>
    </div>
<?php else: ?>
    <div class="df-grid">
        <?php foreach ($footers as $f):
            $st   = $f['status'] ?? 'draft';
            $tp   = $f['type'] ?? 'standard';
            $date = date('d/m/Y', strtotime($f['updated_at'] ?? $f['created_at'] ?? 'now'));
            $prev = $f['builder_content'] ?? ($f['content'] ?? ($f['custom_html'] ?? ''));
            $css  = $f['custom_css'] ?? '';
        ?>
            <div class="df-card <?= !empty($f['is_default']) ? 'is-default' : '' ?>" data-id="<?= (int)$f['id'] ?>">
                <?php if (!empty($f['is_default'])): ?>
                    <span class="df-badge"><i class="fas fa-star"></i> Par défaut</span>
                <?php endif; ?>

                <div class="df-preview">
                    <?php if (!empty($prev)): ?>
                        <iframe srcdoc="<!DOCTYPE html><html><head><meta charset='utf-8'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'><style>body{margin:0;font-family:Inter,sans-serif;font-size:14px;overflow:hidden}<?= htmlspecialchars($css) ?></style></head><body><?= htmlspecialchars($prev) ?></body></html>" sandbox="allow-same-origin" loading="lazy"></iframe>
                    <?php else: ?>
                        <i class="fas fa-window-minimize ph"></i>
                    <?php endif; ?>
                </div>

                <div class="df-body">
                    <h3 class="df-name"><?= htmlspecialchars($f['name']) ?></h3>
                    <div class="df-meta">
                        <span><span class="df-dot <?= $st ?>"></span> <?= ucfirst($st) ?></span>
                        <span><i class="fas fa-tag"></i> <?= ucfirst($tp) ?></span>
                        <span><i class="fas fa-calendar-alt"></i> <?= $date ?></span>
                    </div>
                </div>

                <div class="df-actions">
                    <!-- ★ ÉDITER → Builder direct -->
                    <a href="/admin/modules/builder/index.php?type=footer&id=<?= (int)$f['id'] ?>" class="act-edit">
                        <i class="fas fa-pen"></i> Éditer dans Builder
                    </a>
                    <span class="df-sep"></span>
                    <?php if (empty($f['is_default'])): ?>
                        <button class="act-def" onclick="dfSetDef(<?= (int)$f['id'] ?>)">
                            <i class="fas fa-star"></i> Par défaut
                        </button>
                        <span class="df-sep"></span>
                    <?php endif; ?>
                    <button class="act-del" onclick="dfAskDel(<?= (int)$f['id'] ?>, '<?= addslashes(htmlspecialchars($f['name'])) ?>')">
                        <i class="fas fa-trash-alt"></i> Suppr.
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="df-ovl" id="dfOvl">
    <div class="df-dlg">
        <div class="ico"><i class="fas fa-trash-alt"></i></div>
        <h3>Supprimer ce footer ?</h3>
        <p>Vous allez supprimer <span class="hl" id="dfDN"></span>.<br>Les pages liées seront remises au footer par défaut.</p>
        <div class="df-btns">
            <button class="btn-c" onclick="dfClose()">Annuler</button>
            <button class="btn-d" id="dfDB" onclick="dfDoDel()">Supprimer</button>
        </div>
    </div>
</div>
<div class="df-toast" id="dfT"></div>

<script>
const DF_API = '/admin/modules/builder/api';
let _dfDI = 0;

function dfSetDef(id) {
    fetch(DF_API + '/set-default.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ type: 'footer', id: id })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { dfToast(d.message || 'Footer par défaut mis à jour !', 'ok'); setTimeout(() => location.reload(), 800); }
        else dfToast(d.error || 'Erreur', 'ko');
    })
    .catch(() => dfToast('Erreur réseau', 'ko'));
}

function dfAskDel(id, name) { _dfDI = id; document.getElementById('dfDN').textContent = name; document.getElementById('dfOvl').classList.add('show'); }
function dfClose() { document.getElementById('dfOvl').classList.remove('show'); _dfDI = 0; }

function dfDoDel() {
    if (!_dfDI) return;
    const btn = document.getElementById('dfDB');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(DF_API + '/delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ type: 'footer', id: _dfDI, force: true })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            dfClose();
            const c = document.querySelector('.df-card[data-id="' + _dfDI + '"]');
            if (c) { c.classList.add('out'); setTimeout(() => c.remove(), 300); }
            dfToast(d.message || 'Footer supprimé !', 'ok');
        } else dfToast(d.error || 'Erreur', 'ko');
        btn.disabled = false; btn.innerHTML = 'Supprimer';
    })
    .catch(() => { dfToast('Erreur réseau', 'ko'); btn.disabled = false; btn.innerHTML = 'Supprimer'; });
}

function dfToast(m, t) {
    const e = document.getElementById('dfT');
    e.innerHTML = '<i class="fas fa-' + (t === 'ok' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + m;
    e.className = 'df-toast ' + t + ' show';
    setTimeout(() => e.classList.remove('show'), 3500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') dfClose(); });
document.getElementById('dfOvl')?.addEventListener('click', function(e) { if (e.target === this) dfClose(); });
</script>