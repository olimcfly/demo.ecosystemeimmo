<?php
/**
 * Module CRM Pipeline — Vue Kanban
 * /admin/modules/crm/index.php
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

$stages = []; $leads = [];
try { $stages = $pdo->query("SELECT * FROM pipeline_stages ORDER BY position ASC")->fetchAll(); } catch(PDOException $e) {}
try { $leads = $pdo->query("SELECT l.*, ps.name as stage_name, ps.color as stage_color FROM leads l LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id ORDER BY l.created_at DESC")->fetchAll(); } catch(PDOException $e) {}

$leadsByStage = [];
foreach ($stages as $s) $leadsByStage[$s['id']] = [];
foreach ($leads as $l) { $sid = $l['pipeline_stage_id'] ?: 1; if (isset($leadsByStage[$sid])) $leadsByStage[$sid][] = $l; }

$totalLeads = count($leads);
$totalValue = array_sum(array_column($leads, 'estimated_value'));
$wonStage = array_filter($stages, fn($s) => $s['is_won'] ?? false); $wonStage = reset($wonStage);
$wonLeads = $wonStage ? count($leadsByStage[$wonStage['id']] ?? []) : 0;
$wonValue = ($wonStage && isset($leadsByStage[$wonStage['id']])) ? array_sum(array_column($leadsByStage[$wonStage['id']], 'estimated_value')) : 0;
?>

<style>
.crm-pipe{display:flex;gap:12px;overflow-x:auto;padding-bottom:16px;min-height:550px}
.crm-col{min-width:270px;max-width:270px;background:var(--surface-2);border-radius:var(--radius-lg);display:flex;flex-direction:column}
.crm-col-head{padding:12px 14px;border-bottom:3px solid;display:flex;align-items:center;justify-content:space-between;border-radius:var(--radius-lg) var(--radius-lg) 0 0;background:var(--surface)}
.crm-col-title{font-size:.78rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:6px}
.crm-col-count{background:rgba(0,0,0,.08);padding:1px 7px;border-radius:10px;font-size:.65rem;font-weight:600}
.crm-col-val{font-size:.68rem;color:var(--text-3);font-weight:500}
.crm-col-cards{flex:1;padding:10px;overflow-y:auto;min-height:300px}
.crm-col-cards.drag-over{background:var(--accent-bg)}
.crm-col.is-won .crm-col-head{background:var(--green-bg)}
.crm-col.is-lost .crm-col-head{background:var(--red-bg)}
.crm-col.is-lost .crm-card{opacity:.65}
.crm-card{background:var(--surface);border-radius:var(--radius);padding:12px;margin-bottom:8px;cursor:grab;transition:all .2s;border:1px solid var(--border);box-shadow:0 1px 2px rgba(0,0,0,.04)}
.crm-card:hover{box-shadow:var(--shadow);transform:translateY(-2px)}
.crm-card.dragging{opacity:.4;transform:rotate(2deg)}
.crm-card-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px}
.crm-card-name{font-size:.82rem;font-weight:600;color:var(--text)}
.crm-card-value{font-size:.7rem;font-weight:700;color:var(--green);background:var(--green-bg);padding:2px 7px;border-radius:5px}
.crm-card-info{display:flex;flex-direction:column;gap:3px;margin-bottom:8px}
.crm-card-info-item{font-size:.72rem;color:var(--text-3);display:flex;align-items:center;gap:5px}
.crm-card-info-item i{width:12px;color:var(--text-3);font-size:.6rem}
.crm-card-src{font-size:.6rem;padding:2px 7px;border-radius:3px;background:var(--surface-2);color:var(--text-3);font-weight:500}
.crm-card-next{font-size:.68rem;padding:4px 7px;border-radius:5px;margin-top:6px;display:flex;align-items:center;gap:4px;background:var(--amber-bg);color:var(--amber)}
.crm-card-next.overdue{background:var(--red-bg);color:var(--red)}
.crm-card-foot{display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:8px;border-top:1px solid var(--surface-2)}
.crm-card-date{font-size:.65rem;color:var(--text-3)}
.crm-card-acts{display:flex;gap:3px}
.crm-card-btn{width:26px;height:26px;border-radius:5px;display:flex;align-items:center;justify-content:center;background:var(--surface-2);color:var(--text-3);border:none;cursor:pointer;transition:all .2s;font-size:.65rem}
.crm-card-btn:hover{background:var(--accent);color:#fff}
.crm-card-btn.danger:hover{background:var(--red)}
.crm-filters{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;display:none}
.crm-filters.show{display:flex}
@media(max-width:768px){.crm-col{min-width:250px}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-columns"></i> Pipeline CRM</h1>
        <p>Gérez vos leads par étape de conversion — glissez-déposez entre colonnes</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= $totalLeads ?></div><div class="mod-stat-label">Leads</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= number_format($totalValue,0,',',' ') ?> €</div><div class="mod-stat-label">Pipeline</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $wonLeads ?></div><div class="mod-stat-label">Gagnés</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= number_format($wonValue,0,',',' ') ?> €</div><div class="mod-stat-label">CA gagné</div></div>
    </div>
</div>

<div class="mod-toolbar">
    <div class="mod-toolbar-left">
        <button class="mod-btn mod-btn-secondary mod-btn-sm" onclick="document.getElementById('filtersBar').classList.toggle('show')"><i class="fas fa-filter"></i> Filtres</button>
    </div>
    <div class="mod-toolbar-right">
        <button class="mod-btn mod-btn-primary" onclick="openAddModal()"><i class="fas fa-plus"></i> Nouveau lead</button>
    </div>
</div>

<div class="crm-filters" id="filtersBar">
    <div class="mod-search"><i class="fas fa-search"></i><input type="text" id="searchFilter" placeholder="Rechercher un lead..." onkeyup="filterLeads()"></div>
    <select id="sourceFilter" onchange="filterLeads()" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
        <option value="">Toutes sources</option>
        <option value="Site web">Site web</option><option value="Facebook">Facebook</option><option value="Google">Google</option><option value="Recommandation">Recommandation</option><option value="Manuel">Manuel</option>
    </select>
</div>

<?php if (empty($stages)): ?>
<div class="mod-empty"><i class="fas fa-exclamation-triangle"></i><h3>Tables non configurées</h3><p>Créez les tables pipeline_stages et leads pour activer le CRM.</p></div>
<?php else: ?>
<div class="crm-pipe">
    <?php foreach ($stages as $stage):
        $sLeads = $leadsByStage[$stage['id']] ?? [];
        $sValue = array_sum(array_column($sLeads, 'estimated_value'));
        $wonCls = ($stage['is_won'] ?? 0) ? ' is-won' : '';
        $lostCls = ($stage['is_lost'] ?? 0) ? ' is-lost' : '';
    ?>
    <div class="crm-col<?= $wonCls.$lostCls ?>" data-stage-id="<?= $stage['id'] ?>">
        <div class="crm-col-head" style="border-color:<?= htmlspecialchars($stage['color']) ?>">
            <div class="crm-col-title">
                <span style="color:<?= htmlspecialchars($stage['color']) ?>">●</span>
                <?= htmlspecialchars($stage['name']) ?>
                <span class="crm-col-count"><?= count($sLeads) ?></span>
            </div>
            <div class="crm-col-val"><?= number_format($sValue,0,',',' ') ?> €</div>
        </div>
        <div class="crm-col-cards" data-stage-id="<?= $stage['id'] ?>" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
            <?php if (empty($sLeads)): ?>
            <div class="mod-empty" style="padding:24px"><i class="fas fa-inbox" style="font-size:1.5rem"></i><p class="mod-text-xs">Aucun lead</p></div>
            <?php else: foreach ($sLeads as $lead):
                $overdue = !empty($lead['next_action_date']) && strtotime($lead['next_action_date']) < strtotime('today');
            ?>
            <div class="crm-card" draggable="true" data-lead-id="<?= $lead['id'] ?>" data-name="<?= htmlspecialchars(strtolower(($lead['firstname']??'').' '.($lead['lastname']??''))) ?>" data-source="<?= htmlspecialchars($lead['source']??'') ?>" data-value="<?= $lead['estimated_value']??0 ?>" ondragstart="handleDragStart(event)" ondragend="handleDragEnd(event)">
                <div class="crm-card-head">
                    <div class="crm-card-name"><?= htmlspecialchars(($lead['firstname']??'').' '.($lead['lastname']??'')) ?></div>
                    <?php if (($lead['estimated_value']??0) > 0): ?><div class="crm-card-value"><?= number_format($lead['estimated_value'],0,',',' ') ?> €</div><?php endif; ?>
                </div>
                <div class="crm-card-info">
                    <?php if ($lead['email']??''): ?><div class="crm-card-info-item"><i class="fas fa-envelope"></i><?= htmlspecialchars($lead['email']) ?></div><?php endif; ?>
                    <?php if ($lead['phone']??''): ?><div class="crm-card-info-item"><i class="fas fa-phone"></i><?= htmlspecialchars($lead['phone']) ?></div><?php endif; ?>
                </div>
                <?php if ($lead['source']??''): ?><span class="crm-card-src"><?= htmlspecialchars($lead['source']) ?></span><?php endif; ?>
                <?php if ($lead['next_action']??''): ?>
                <div class="crm-card-next<?= $overdue?' overdue':'' ?>"><i class="fas fa-clock"></i><?= htmlspecialchars($lead['next_action']) ?><?php if ($lead['next_action_date']??''): ?> — <?= date('d/m', strtotime($lead['next_action_date'])) ?><?php endif; ?></div>
                <?php endif; ?>
                <div class="crm-card-foot">
                    <span class="crm-card-date"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($lead['created_at'])) ?></span>
                    <div class="crm-card-acts">
                        <button class="crm-card-btn" onclick="openEditModal(<?= $lead['id'] ?>)" title="Modifier"><i class="fas fa-pen"></i></button>
                        <?php if (!($stage['is_lost']??0) && !($stage['is_won']??0)): ?>
                        <button class="crm-card-btn danger" onclick="openLostModal(<?= $lead['id'] ?>)" title="Perdu"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Lead -->
<div class="mod-overlay" id="leadModal">
    <div class="mod-modal" style="max-width:600px">
        <div class="mod-modal-header"><h3><i class="fas fa-user-plus" style="color:var(--accent)"></i> <span id="modalTitle">Nouveau lead</span></h3><button class="mod-modal-close" onclick="closeModal()">×</button></div>
        <form id="leadForm">
        <div class="mod-modal-body">
            <input type="hidden" id="leadId" name="lead_id" value="">
            <div class="mod-form-grid">
                <div class="mod-form-group"><label>Prénom *</label><input type="text" id="firstname" name="firstname" required></div>
                <div class="mod-form-group"><label>Nom *</label><input type="text" id="lastname" name="lastname" required></div>
            </div>
            <div class="mod-form-grid">
                <div class="mod-form-group"><label>Email</label><input type="email" id="email" name="email"></div>
                <div class="mod-form-group"><label>Téléphone</label><input type="tel" id="phone" name="phone"></div>
            </div>
            <div class="mod-form-grid">
                <div class="mod-form-group"><label>Valeur estimée (€)</label><input type="number" id="estimated_value" name="estimated_value" min="0" step="100" value="0"></div>
                <div class="mod-form-group"><label>Source</label><select id="source" name="source"><option value="Manuel">Manuel</option><option value="Site web">Site web</option><option value="Facebook">Facebook</option><option value="Google">Google</option><option value="LinkedIn">LinkedIn</option><option value="Recommandation">Recommandation</option><option value="Téléphone">Téléphone</option><option value="Email">Email</option></select></div>
            </div>
            <div class="mod-form-group" id="stageGroup" style="display:none"><label>Étape</label><select id="pipeline_stage_id" name="pipeline_stage_id"><?php foreach ($stages as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
            <div class="mod-form-grid">
                <div class="mod-form-group"><label>Prochaine action</label><input type="text" id="next_action" name="next_action" placeholder="Ex: Appeler pour RDV"></div>
                <div class="mod-form-group"><label>Date action</label><input type="date" id="next_action_date" name="next_action_date"></div>
            </div>
            <div class="mod-form-group"><label>Notes</label><textarea id="notes" name="notes" rows="3" placeholder="Informations..."></textarea></div>
        </div>
        <div class="mod-modal-footer"><button type="button" class="mod-btn mod-btn-secondary" onclick="closeModal()">Annuler</button><button type="submit" class="mod-btn mod-btn-primary" id="submitBtn"><i class="fas fa-save"></i> Enregistrer</button></div>
        </form>
    </div>
</div>

<!-- Modal Lost -->
<div class="mod-overlay" id="lostModal">
    <div class="mod-modal" style="max-width:450px">
        <div class="mod-modal-header"><h3><i class="fas fa-times-circle" style="color:var(--red)"></i> Marquer comme perdu</h3><button class="mod-modal-close" onclick="closeLostModal()">×</button></div>
        <form id="lostForm">
        <div class="mod-modal-body">
            <input type="hidden" id="lostLeadId" name="lead_id">
            <div class="mod-form-group"><label>Raison de la perte</label><select id="lostReason" name="reason"><option value="Prix trop élevé">Prix trop élevé</option><option value="Pas de financement">Pas de financement</option><option value="A choisi un concurrent">A choisi un concurrent</option><option value="Projet abandonné">Projet abandonné</option><option value="Pas de réponse">Pas de réponse</option><option value="Hors cible">Hors cible</option><option value="Autre">Autre</option></select></div>
        </div>
        <div class="mod-modal-footer"><button type="button" class="mod-btn mod-btn-secondary" onclick="closeLostModal()">Annuler</button><button type="submit" class="mod-btn mod-btn-primary" style="background:var(--red);border-color:var(--red)"><i class="fas fa-times"></i> Confirmer</button></div>
        </form>
    </div>
</div>

<script>
const API_URL='/admin/modules/crm/api.php';
let draggedElement=null;

function handleDragStart(e){draggedElement=e.target;e.target.classList.add('dragging');e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain',e.target.dataset.leadId)}
function handleDragEnd(e){e.target.classList.remove('dragging');document.querySelectorAll('.crm-col-cards').forEach(c=>c.classList.remove('drag-over'))}
function handleDragOver(e){e.preventDefault();e.currentTarget.classList.add('drag-over')}
function handleDragLeave(e){e.currentTarget.classList.remove('drag-over')}
function handleDrop(e){
    e.preventDefault();e.currentTarget.classList.remove('drag-over');
    const leadId=e.dataTransfer.getData('text/plain'),newStageId=e.currentTarget.dataset.stageId;
    const empty=e.currentTarget.querySelector('.mod-empty');if(empty)empty.remove();
    e.currentTarget.appendChild(draggedElement);
    const fd=new FormData();fd.append('action','move_lead');fd.append('lead_id',leadId);fd.append('stage_id',newStageId);
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){showNotif('Lead déplacé','success');updateCounts()}else showNotif(d.error||'Erreur','error');
    }).catch(()=>showNotif('Erreur connexion','error'));
}

function openAddModal(){document.getElementById('modalTitle').textContent='Nouveau lead';document.getElementById('leadForm').reset();document.getElementById('leadId').value='';document.getElementById('stageGroup').style.display='none';document.getElementById('leadModal').classList.add('show')}
function closeModal(){document.getElementById('leadModal').classList.remove('show')}
function openLostModal(id){document.getElementById('lostLeadId').value=id;document.getElementById('lostModal').classList.add('show')}
function closeLostModal(){document.getElementById('lostModal').classList.remove('show')}

function openEditModal(id){
    document.getElementById('modalTitle').textContent='Modifier le lead';
    document.getElementById('leadId').value=id;
    document.getElementById('stageGroup').style.display='block';
    fetch(API_URL+'?action=get_lead&lead_id='+id).then(r=>r.json()).then(d=>{
        if(!d.success||!d.lead)return showNotif('Lead non trouvé','error');const l=d.lead;
        ['firstname','lastname','email','phone','estimated_value','source','pipeline_stage_id','next_action','next_action_date','notes'].forEach(f=>{const el=document.getElementById(f);if(el&&l[f]!=null)el.value=l[f]});
        document.getElementById('leadModal').classList.add('show');
    }).catch(()=>showNotif('Erreur connexion','error'));
}

document.getElementById('leadForm').addEventListener('submit',function(e){
    e.preventDefault();const btn=document.getElementById('submitBtn');btn.disabled=true;
    const id=document.getElementById('leadId').value,fd=new FormData(this);
    fd.append('action',id?'update_lead':'add_lead');
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        btn.disabled=false;if(d.success){showNotif(d.message||(id?'Mis à jour':'Ajouté'),'success');closeModal();setTimeout(()=>location.reload(),800)}else showNotif(d.error||'Erreur','error');
    }).catch(()=>{btn.disabled=false;showNotif('Erreur connexion','error')});
});

document.getElementById('lostForm').addEventListener('submit',function(e){
    e.preventDefault();const fd=new FormData(this);fd.append('action','mark_lost');
    fetch(API_URL,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success){showNotif('Lead marqué perdu','success');closeLostModal();setTimeout(()=>location.reload(),800)}else showNotif(d.error||'Erreur','error');
    }).catch(()=>showNotif('Erreur connexion','error'));
});

function filterLeads(){
    const s=document.getElementById('searchFilter').value.toLowerCase(),src=document.getElementById('sourceFilter').value;
    document.querySelectorAll('.crm-card').forEach(c=>{let ok=true;if(s&&!(c.dataset.name||'').includes(s))ok=false;if(src&&(c.dataset.source||'')!==src)ok=false;c.style.display=ok?'':'none'});
    updateCounts();
}
function updateCounts(){
    document.querySelectorAll('.crm-col').forEach(col=>{
        const cards=col.querySelectorAll('.crm-card:not([style*="display: none"])');
        col.querySelector('.crm-col-count').textContent=cards.length;
        let t=0;cards.forEach(c=>t+=parseFloat(c.dataset.value)||0);
        col.querySelector('.crm-col-val').textContent=new Intl.NumberFormat('fr-FR').format(t)+' €';
    });
}
function showNotif(msg,type='info'){const c={success:'var(--green)',error:'var(--red)',info:'var(--accent)'},n=document.createElement('div');n.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 20px;background:${c[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;n.textContent=msg;document.body.appendChild(n);setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),300)},2500)}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeModal();closeLostModal()}});
document.querySelectorAll('.mod-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this){closeModal();closeLostModal()}}));
</script>