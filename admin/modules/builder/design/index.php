<?php
/**
 * Module Design — Headers & Footers
 * /admin/modules/design/index.php
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

$activeTab = $_GET['type'] ?? 'headers';
if (!in_array($activeTab, ['headers','footers'])) $activeTab = 'headers';

$flashMsg = $_GET['msg'] ?? '';
$flashMap = ['deleted'=>['success','Élément supprimé.'],'default_set'=>['success','Élément par défaut mis à jour.'],'error'=>['error','Une erreur est survenue.']];

$headers = $footers = [];
try { $headers = $pdo->query("SELECT id,name,slug,status,is_default,html_content,created_at,updated_at FROM headers ORDER BY is_default DESC,updated_at DESC")->fetchAll(); } catch(Exception $e) {}
try { $footers = $pdo->query("SELECT id,name,slug,status,is_default,html_content,created_at,updated_at FROM footers ORDER BY is_default DESC,updated_at DESC")->fetchAll(); } catch(Exception $e) {}
?>

<style>
.dsn-preview{height:110px;background:var(--surface-2);border-bottom:1px solid var(--border);overflow:hidden;position:relative;display:flex;align-items:center;justify-content:center}
.dsn-preview iframe{width:200%;height:200%;border:none;transform:scale(.5);transform-origin:top left;pointer-events:none}
.dsn-preview-ph{color:var(--text-3);font-size:2rem;opacity:.4}
.dsn-card{background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);overflow:hidden;transition:all .25s;position:relative}
.dsn-card:hover{border-color:var(--accent);box-shadow:var(--shadow);transform:translateY(-2px)}
.dsn-card.is-default{border-color:var(--accent);box-shadow:0 0 0 1px var(--accent)}
.dsn-default-badge{position:absolute;top:10px;right:10px;background:var(--accent);color:#fff;font-size:.65rem;font-weight:600;padding:3px 9px;border-radius:5px;z-index:2;display:flex;align-items:center;gap:3px}
.dsn-card-body{padding:14px 16px}
.dsn-card-name{font-size:.9rem;font-weight:600;color:var(--text);margin-bottom:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dsn-card-meta{display:flex;align-items:center;gap:10px;font-size:.7rem;color:var(--text-3)}
.dsn-card-meta .dot{width:7px;height:7px;border-radius:50%;display:inline-block}
.dsn-card-meta .dot.active{background:var(--green)}.dsn-card-meta .dot.inactive{background:var(--red)}.dsn-card-meta .dot.draft{background:var(--amber)}
.dsn-card-actions{display:flex;border-top:1px solid var(--surface-2)}
.dsn-card-actions a,.dsn-card-actions button{flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:10px 0;border:none;background:transparent;font-size:.75rem;font-weight:500;color:var(--text-3);cursor:pointer;text-decoration:none;transition:all .15s;font-family:var(--font)}
.dsn-card-actions a:hover,.dsn-card-actions button:hover{background:var(--surface-2)}
.dsn-card-actions .act-edit:hover{color:var(--accent)}
.dsn-card-actions .act-default:hover{color:var(--amber)}
.dsn-card-actions .act-delete:hover{color:var(--red)}
.dsn-card-actions .sep{width:1px;background:var(--surface-2);flex:0}
.dsn-confirm-icon{width:50px;height:50px;border-radius:50%;background:var(--red-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--red);font-size:20px}
.dsn-card.deleting{opacity:0;transform:scale(.9);transition:all .3s}
@media(max-width:768px){.mod-grid-3{grid-template-columns:1fr!important}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-palette"></i> Design</h1>
        <p>Gérez les entêtes et pieds de page de votre site</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= count($headers) ?></div><div class="mod-stat-label">Headers</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= count($footers) ?></div><div class="mod-stat-label">Footers</div></div>
    </div>
</div>

<?php if (isset($flashMap[$flashMsg])): ?>
<div class="mod-flash mod-flash-<?= $flashMap[$flashMsg][0] ?>"><i class="fas fa-<?= $flashMap[$flashMsg][0]==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= $flashMap[$flashMsg][1] ?></div>
<?php endif; ?>

<div class="mod-toolbar">
    <div class="mod-toolbar-left">
        <div class="mod-tabs">
            <button class="mod-tab <?= $activeTab==='headers'?'active':'' ?>" onclick="switchDesignTab('headers')"><i class="fas fa-arrow-up"></i> Entêtes <span class="mod-badge mod-badge-inactive"><?= count($headers) ?></span></button>
            <button class="mod-tab <?= $activeTab==='footers'?'active':'' ?>" onclick="switchDesignTab('footers')"><i class="fas fa-arrow-down"></i> Pieds de page <span class="mod-badge mod-badge-inactive"><?= count($footers) ?></span></button>
        </div>
    </div>
    <div class="mod-toolbar-right">
        <a href="?page=builder&type=<?= $activeTab==='headers'?'header':'footer' ?>&action=create" class="mod-btn mod-btn-primary" id="dsnCreateBtn"><i class="fas fa-plus"></i> Créer</a>
    </div>
</div>

<!-- TAB HEADERS -->
<div id="tab-headers" style="<?= $activeTab!=='headers'?'display:none':'' ?>">
    <?php if (empty($headers)): ?>
    <div class="mod-empty"><i class="fas fa-window-maximize"></i><h3>Aucun header créé</h3><p>Créez votre premier entête de site.</p><a href="?page=builder&type=header&action=create" class="mod-btn mod-btn-primary"><i class="fas fa-plus"></i> Créer un header</a></div>
    <?php else: ?>
    <div class="mod-grid mod-grid-3">
        <?php foreach ($headers as $h): ?>
        <div class="dsn-card <?= $h['is_default']?'is-default':'' ?>" data-id="<?= $h['id'] ?>" data-type="headers" data-name="<?= htmlspecialchars($h['name']) ?>">
            <?php if ($h['is_default']): ?><span class="dsn-default-badge"><i class="fas fa-star"></i> Par défaut</span><?php endif; ?>
            <div class="dsn-preview">
                <?php if (!empty($h['html_content'])): ?>
                <iframe srcdoc="<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{margin:0;font-family:Inter,sans-serif;font-size:14px;overflow:hidden}</style></head><body><?= htmlspecialchars($h['html_content']) ?></body></html>" sandbox="allow-same-origin"></iframe>
                <?php else: ?>
                <i class="fas fa-window-maximize dsn-preview-ph"></i>
                <?php endif; ?>
            </div>
            <div class="dsn-card-body">
                <div class="dsn-card-name" title="<?= htmlspecialchars($h['name']) ?>"><?= htmlspecialchars($h['name']) ?></div>
                <div class="dsn-card-meta">
                    <span><span class="dot <?= $h['status']??'active' ?>"></span> <?= ucfirst($h['status']??'active') ?></span>
                    <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($h['created_at'])) ?></span>
                    <span><i class="fas fa-code"></i> <?= htmlspecialchars($h['slug']) ?></span>
                </div>
            </div>
            <div class="dsn-card-actions">
                <a href="?page=builder&type=header&id=<?= $h['id'] ?>" class="act-edit"><i class="fas fa-pen"></i> Éditer</a>
                <span class="sep"></span>
                <?php if (!$h['is_default']): ?>
                <button class="act-default" onclick="dhSetDefault(<?= $h['id'] ?>,'headers')"><i class="fas fa-star"></i> Défaut</button>
                <span class="sep"></span>
                <?php endif; ?>
                <button class="act-delete" onclick="dhConfirmDelete(<?= $h['id'] ?>,'headers','<?= addslashes($h['name']) ?>')"><i class="fas fa-trash-alt"></i> Suppr.</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- TAB FOOTERS -->
<div id="tab-footers" style="<?= $activeTab!=='footers'?'display:none':'' ?>">
    <?php if (empty($footers)): ?>
    <div class="mod-empty"><i class="fas fa-window-minimize"></i><h3>Aucun footer créé</h3><p>Créez votre premier pied de page.</p><a href="?page=builder&type=footer&action=create" class="mod-btn mod-btn-primary"><i class="fas fa-plus"></i> Créer un footer</a></div>
    <?php else: ?>
    <div class="mod-grid mod-grid-3">
        <?php foreach ($footers as $f): ?>
        <div class="dsn-card <?= $f['is_default']?'is-default':'' ?>" data-id="<?= $f['id'] ?>" data-type="footers" data-name="<?= htmlspecialchars($f['name']) ?>">
            <?php if ($f['is_default']): ?><span class="dsn-default-badge"><i class="fas fa-star"></i> Par défaut</span><?php endif; ?>
            <div class="dsn-preview">
                <?php if (!empty($f['html_content'])): ?>
                <iframe srcdoc="<!DOCTYPE html><html><head><meta charset='utf-8'><style>body{margin:0;font-family:Inter,sans-serif;font-size:14px;overflow:hidden}</style></head><body><?= htmlspecialchars($f['html_content']) ?></body></html>" sandbox="allow-same-origin"></iframe>
                <?php else: ?>
                <i class="fas fa-window-minimize dsn-preview-ph"></i>
                <?php endif; ?>
            </div>
            <div class="dsn-card-body">
                <div class="dsn-card-name" title="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></div>
                <div class="dsn-card-meta">
                    <span><span class="dot <?= $f['status']??'active' ?>"></span> <?= ucfirst($f['status']??'active') ?></span>
                    <span><i class="fas fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($f['created_at'])) ?></span>
                    <span><i class="fas fa-code"></i> <?= htmlspecialchars($f['slug']) ?></span>
                </div>
            </div>
            <div class="dsn-card-actions">
                <a href="?page=builder&type=footer&id=<?= $f['id'] ?>" class="act-edit"><i class="fas fa-pen"></i> Éditer</a>
                <span class="sep"></span>
                <?php if (!$f['is_default']): ?>
                <button class="act-default" onclick="dhSetDefault(<?= $f['id'] ?>,'footers')"><i class="fas fa-star"></i> Défaut</button>
                <span class="sep"></span>
                <?php endif; ?>
                <button class="act-delete" onclick="dhConfirmDelete(<?= $f['id'] ?>,'footers','<?= addslashes($f['name']) ?>')"><i class="fas fa-trash-alt"></i> Suppr.</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Confirm Modal -->
<div class="mod-overlay" id="dhOverlay">
    <div class="mod-modal" style="max-width:420px">
        <div class="mod-modal-body" style="text-align:center;padding:28px">
            <div class="dsn-confirm-icon"><i class="fas fa-trash-alt"></i></div>
            <h3 style="font-size:1.05rem;font-weight:600;color:var(--text);margin-bottom:6px">Supprimer cet élément ?</h3>
            <p class="mod-text-sm mod-text-muted" style="margin-bottom:20px">Vous allez supprimer <strong id="dhDeleteName"></strong>. Irréversible.</p>
            <div class="mod-flex mod-gap" style="justify-content:center">
                <button class="mod-btn mod-btn-secondary" onclick="dhCloseConfirm()">Annuler</button>
                <button class="mod-btn" id="dhConfirmBtn" onclick="dhExecuteDelete()" style="background:var(--red);color:#fff;border-color:var(--red)"><i class="fas fa-trash"></i> Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
function switchDesignTab(tab){
    const url=new URL(window.location);url.searchParams.set('page','design-'+tab);window.history.pushState({},'',url);
    document.querySelectorAll('.mod-tab').forEach((t,i)=>t.classList.toggle('active',(i===0&&tab==='headers')||(i===1&&tab==='footers')));
    document.getElementById('tab-headers').style.display=tab==='headers'?'':'none';
    document.getElementById('tab-footers').style.display=tab==='footers'?'':'none';
    document.getElementById('dsnCreateBtn').href='?page=builder&type='+(tab==='headers'?'header':'footer')+'&action=create';
}

function dhSetDefault(id,type){
    fetch('/admin/modules/design/api/set-default.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,table:type})})
    .then(r=>r.json()).then(d=>{if(d.success){showNotif('Par défaut mis à jour','success');setTimeout(()=>location.reload(),800)}else showNotif(d.error||'Erreur','error')})
    .catch(()=>showNotif('Erreur réseau','error'));
}

let deleteId=0,deleteType='';
function dhConfirmDelete(id,type,name){deleteId=id;deleteType=type;document.getElementById('dhDeleteName').textContent=name;document.getElementById('dhOverlay').classList.add('show')}
function dhCloseConfirm(){document.getElementById('dhOverlay').classList.remove('show');deleteId=0;deleteType=''}

function dhExecuteDelete(){
    if(!deleteId||!deleteType)return;
    const card=document.querySelector(`.dsn-card[data-id="${deleteId}"][data-type="${deleteType}"]`),btn=document.getElementById('dhConfirmBtn');
    btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    fetch('/admin/modules/design/api/delete.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:deleteId,table:deleteType})})
    .then(r=>r.json()).then(d=>{
        if(d.success){dhCloseConfirm();if(card){card.classList.add('deleting');setTimeout(()=>card.remove(),300)}showNotif('Supprimé','success');
            const idx=deleteType==='headers'?0:1,el=document.querySelectorAll('.mod-tab')[idx]?.querySelector('.mod-badge');
            if(el)el.textContent=Math.max(0,parseInt(el.textContent||'0')-1);
        }else showNotif(d.error||'Erreur','error');
        btn.disabled=false;btn.innerHTML='<i class="fas fa-trash"></i> Supprimer';
    }).catch(()=>{showNotif('Erreur réseau','error');btn.disabled=false;btn.innerHTML='<i class="fas fa-trash"></i> Supprimer'});
}

function showNotif(msg,type='info'){const c={success:'var(--green)',error:'var(--red)',info:'var(--accent)'},n=document.createElement('div');n.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 20px;background:${c[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;n.textContent=msg;document.body.appendChild(n);setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),300)},2500)}
document.addEventListener('keydown',e=>{if(e.key==='Escape')dhCloseConfirm()});
document.getElementById('dhOverlay').addEventListener('click',function(e){if(e.target===this)dhCloseConfirm()});
</script>