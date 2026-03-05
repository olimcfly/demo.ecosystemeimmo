<?php
/**
 * Module Email Client — /admin/modules/email/index.php
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

$pdo->exec("CREATE TABLE IF NOT EXISTS email_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) DEFAULT NULL,
    folder ENUM('inbox','sent','drafts','trash','starred') DEFAULT 'inbox',
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) DEFAULT NULL,
    to_email VARCHAR(500) NOT NULL,
    cc VARCHAR(500) DEFAULT NULL,
    subject VARCHAR(500) DEFAULT NULL,
    body_text LONGTEXT DEFAULT NULL,
    body_html LONGTEXT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_starred TINYINT(1) DEFAULT 0,
    has_attachment TINYINT(1) DEFAULT 0,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_folder (folder),
    INDEX idx_read (is_read),
    INDEX idx_starred (is_starred),
    INDEX idx_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Config email
$emailCfg = ['smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'','smtp_from'=>'','smtp_from_name'=>'','imap_host'=>'','imap_port'=>'993','imap_user'=>'','imap_pass'=>''];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE category='email'")->fetchAll();
    foreach ($rows as $r) if (isset($emailCfg[$r['setting_key']])) $emailCfg[$r['setting_key']] = $r['setting_value'];
} catch(Exception $e) {}
$isConfigured = !empty($emailCfg['smtp_host']) && !empty($emailCfg['smtp_user']);

// Counts
$counts = ['inbox'=>0,'sent'=>0,'drafts'=>0,'trash'=>0,'starred'=>0,'unread'=>0];
try {
    foreach (['inbox','sent','drafts','trash'] as $f) $counts[$f] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE folder='{$f}'")->fetchColumn();
    $counts['starred'] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE is_starred=1")->fetchColumn();
    $counts['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE folder='inbox' AND is_read=0")->fetchColumn();
} catch(Exception $e) {}

// Folder/filter
$folder = $_GET['folder'] ?? 'inbox';
$search = trim($_GET['eq'] ?? '');
$page = max(1,(int)($_GET['pg'] ?? 1));
$perPage = 25;
$offset = ($page-1)*$perPage;

$where = "WHERE 1=1"; $params = [];
if ($folder === 'starred') { $where .= " AND is_starred=1"; }
else { $where .= " AND folder=?"; $params[] = $folder; }
if ($search) { $where .= " AND (subject LIKE ? OR from_email LIKE ? OR from_name LIKE ? OR body_text LIKE ?)"; $s="%{$search}%"; $params = array_merge($params,[$s,$s,$s,$s]); }

$total = 0; $emails = [];
try {
    $cs = $pdo->prepare("SELECT COUNT(*) FROM email_messages {$where}"); $cs->execute($params); $total = (int)$cs->fetchColumn();
    $st = $pdo->prepare("SELECT * FROM email_messages {$where} ORDER BY sent_at DESC, created_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $st->execute($params); $emails = $st->fetchAll();
} catch(Exception $e) {}
$totalPages = max(1,ceil($total/$perPage));

// POST actions
$flash = ''; $flashType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_action'])) {
    try {
        switch ($_POST['email_action']) {
            case 'delete':
                $id = (int)($_POST['id']??0);
                $cur = $pdo->prepare("SELECT folder FROM email_messages WHERE id=?"); $cur->execute([$id]); $cf = $cur->fetchColumn();
                if ($cf === 'trash') $pdo->prepare("DELETE FROM email_messages WHERE id=?")->execute([$id]);
                else $pdo->prepare("UPDATE email_messages SET folder='trash' WHERE id=?")->execute([$id]);
                $flash = 'Email supprimé.'; break;
            case 'star':
                $id = (int)($_POST['id']??0);
                $pdo->prepare("UPDATE email_messages SET is_starred = NOT is_starred WHERE id=?")->execute([$id]);
                $flash = 'Favori modifié.'; break;
            case 'read':
                $id = (int)($_POST['id']??0);
                $pdo->prepare("UPDATE email_messages SET is_read=1 WHERE id=?")->execute([$id]);
                break;
            case 'mark_all_read':
                $pdo->exec("UPDATE email_messages SET is_read=1 WHERE folder='inbox' AND is_read=0");
                $flash = 'Tout marqué comme lu.'; break;
        }
    } catch(Exception $e) { $flash = $e->getMessage(); $flashType = 'error'; }
}

$folderLabels = ['inbox'=>['Boîte de réception','fa-inbox'],'sent'=>['Envoyés','fa-paper-plane'],'drafts'=>['Brouillons','fa-file-alt'],'trash'=>['Corbeille','fa-trash'],'starred'=>['Favoris','fa-star']];
$fl = $folderLabels[$folder] ?? $folderLabels['inbox'];

function emailTimeAgo($d) {
    if (!$d) return '';
    $t = strtotime($d); $diff = time()-$t;
    if ($diff < 86400 && date('d')==date('d',$t)) return date('H:i',$t);
    if ($diff < 172800) return 'Hier';
    if ($diff < 604800) return date('D',$t);
    return date('d/m/Y',$t);
}
?>

<style>
.eml-layout{display:grid;grid-template-columns:220px 1fr;gap:0;min-height:560px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.eml-sidebar{background:var(--surface-2);border-right:1px solid var(--border);padding:14px 0}
.eml-sidebar-btn{display:block;width:calc(100% - 24px);margin:0 12px 12px;padding:10px;background:var(--accent);color:#fff;border:none;border-radius:var(--radius);cursor:pointer;font-size:.8rem;font-weight:600;font-family:var(--font);text-align:center;transition:all .15s}
.eml-sidebar-btn:hover{opacity:.9}
.eml-folder{display:flex;align-items:center;gap:8px;padding:9px 16px;cursor:pointer;font-size:.78rem;color:var(--text-2);transition:all .1s;text-decoration:none;border-left:3px solid transparent}
.eml-folder:hover{background:var(--surface);color:var(--text)}
.eml-folder.active{background:var(--accent-bg);border-left-color:var(--accent);color:var(--accent);font-weight:600}
.eml-folder i{width:16px;text-align:center;font-size:.75rem}
.eml-folder-count{margin-left:auto;font-size:.65rem;background:var(--surface);padding:1px 6px;border-radius:10px;color:var(--text-3);font-weight:600}
.eml-folder.active .eml-folder-count{background:var(--accent);color:#fff}
.eml-main{display:flex;flex-direction:column}
.eml-topbar{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border);background:var(--surface)}
.eml-topbar h3{font-size:.85rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:6px}
.eml-topbar h3 i{color:var(--accent)}
.eml-search{flex:1;max-width:280px;position:relative}
.eml-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:.7rem}
.eml-search input{width:100%;padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:var(--radius);font-size:.75rem;font-family:var(--font);background:var(--surface)}
.eml-search input:focus{outline:none;border-color:var(--accent)}
.eml-list{flex:1;overflow-y:auto}
.eml-row{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--surface-2);cursor:pointer;transition:background .1s;text-decoration:none}
.eml-row:hover{background:var(--surface-2)}
.eml-row.unread{background:var(--accent-bg)}
.eml-row.unread .eml-subject{font-weight:700;color:var(--text)}
.eml-star{color:var(--border);font-size:.7rem;cursor:pointer;flex-shrink:0;background:none;border:none;padding:2px}
.eml-star.on{color:var(--amber)}
.eml-from{font-size:.78rem;font-weight:500;color:var(--text);min-width:140px;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0}
.eml-subject{font-size:.78rem;color:var(--text-2);flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.eml-date{font-size:.68rem;color:var(--text-3);flex-shrink:0;min-width:55px;text-align:right}
.eml-attach{color:var(--text-3);font-size:.6rem;flex-shrink:0}
.eml-footer{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;border-top:1px solid var(--border);background:var(--surface)}
@media(max-width:768px){.eml-layout{grid-template-columns:1fr}.eml-sidebar{display:none}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-envelope"></i> Email</h1>
        <p>Messagerie intégrée — envoi, réception et suivi des échanges</p>
    </div>
    <div class="mod-hero-actions">
        <?php if ($counts['unread']): ?>
        <form method="POST" style="display:inline"><input type="hidden" name="email_action" value="mark_all_read"><button class="mod-btn mod-btn-hero" type="submit"><i class="fas fa-check-double"></i> Tout lire</button></form>
        <?php endif; ?>
        <button class="mod-btn mod-btn-hero" onclick="openCompose()"><i class="fas fa-pen"></i> Nouveau</button>
    </div>
</div>

<?php if ($flash): ?>
<div class="mod-flash mod-flash-<?= $flashType ?>"><i class="fas fa-<?= $flashType==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<?php if (!$isConfigured): ?>
<div class="mod-flash mod-flash-info" style="margin-bottom:16px"><i class="fas fa-info-circle"></i> Email non configuré. <a href="?page=settings-email" style="color:var(--accent);font-weight:600">Configurer SMTP/IMAP →</a></div>
<?php endif; ?>

<div class="eml-layout">
    <div class="eml-sidebar">
        <button class="eml-sidebar-btn" onclick="openCompose()"><i class="fas fa-pen" style="margin-right:6px"></i> Nouveau message</button>
        <?php foreach ($folderLabels as $fk => $fv): ?>
        <a href="?page=email&folder=<?= $fk ?>" class="eml-folder <?= $folder===$fk?'active':'' ?>">
            <i class="fas <?= $fv[1] ?>"></i> <?= $fv[0] ?>
            <?php $cnt = $fk==='starred' ? $counts['starred'] : ($counts[$fk]??0); if ($cnt || ($fk==='inbox' && $counts['unread'])): ?>
            <span class="eml-folder-count"><?= $fk==='inbox' && $counts['unread'] ? $counts['unread'] : $cnt ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="eml-main">
        <div class="eml-topbar">
            <h3><i class="fas <?= $fl[1] ?>"></i> <?= $fl[0] ?></h3>
            <form class="eml-search" method="GET">
                <input type="hidden" name="page" value="email">
                <input type="hidden" name="folder" value="<?= htmlspecialchars($folder) ?>">
                <i class="fas fa-search"></i>
                <input type="text" name="eq" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher...">
            </form>
            <span class="mod-text-xs mod-text-muted"><?= $total ?> message<?= $total>1?'s':'' ?></span>
        </div>

        <div class="eml-list">
            <?php if (empty($emails)): ?>
            <div class="mod-empty" style="padding:40px"><i class="fas fa-inbox"></i><h3>Aucun message</h3><p><?= $search ? "Aucun résultat pour « {$search} »." : 'Ce dossier est vide.' ?></p></div>
            <?php else: foreach ($emails as $em): ?>
            <div class="eml-row <?= !$em['is_read']?'unread':'' ?>" onclick="viewEmail(<?= $em['id'] ?>)">
                <form method="POST" onclick="event.stopPropagation()" style="display:inline"><input type="hidden" name="email_action" value="star"><input type="hidden" name="id" value="<?= $em['id'] ?>"><button type="submit" class="eml-star <?= $em['is_starred']?'on':'' ?>"><i class="fas fa-star"></i></button></form>
                <div class="eml-from"><?= htmlspecialchars($em['from_name'] ?: $em['from_email']) ?></div>
                <div class="eml-subject"><?= htmlspecialchars($em['subject'] ?: '(sans objet)') ?></div>
                <?php if ($em['has_attachment']): ?><span class="eml-attach"><i class="fas fa-paperclip"></i></span><?php endif; ?>
                <div class="eml-date"><?= emailTimeAgo($em['sent_at'] ?: $em['created_at']) ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="eml-footer">
            <span class="mod-text-xs mod-text-muted">Page <?= $page ?>/<?= $totalPages ?></span>
            <div class="mod-pagination">
                <?php if ($page>1): $p=$_GET;$p['pg']=$page-1; ?><a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                <?php if ($page<$totalPages): $p=$_GET;$p['pg']=$page+1; ?><a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Compose -->
<div class="mod-overlay" id="composeModal">
    <div class="mod-modal" style="max-width:650px">
        <div class="mod-modal-header"><h3><i class="fas fa-pen" style="color:var(--accent)"></i> Nouveau message</h3><button class="mod-modal-close" onclick="closeCompose()">×</button></div>
        <form id="composeForm" method="POST" action="/admin/modules/email/api.php">
        <div class="mod-modal-body">
            <input type="hidden" name="action" value="send">
            <div class="mod-form-group"><label>À *</label><input type="email" name="to" required placeholder="destinataire@email.com"></div>
            <div class="mod-form-group"><label>Cc</label><input type="text" name="cc" placeholder="cc@email.com"></div>
            <div class="mod-form-group"><label>Objet</label><input type="text" name="subject" placeholder="Objet du message"></div>
            <div class="mod-form-group"><label>Message</label><textarea name="body" rows="10" placeholder="Votre message..."></textarea></div>
        </div>
        <div class="mod-modal-footer">
            <button type="button" class="mod-btn mod-btn-secondary" onclick="closeCompose()">Annuler</button>
            <button type="submit" class="mod-btn mod-btn-primary"><i class="fas fa-paper-plane"></i> Envoyer</button>
        </div>
        </form>
    </div>
</div>

<!-- Modal View -->
<div class="mod-overlay" id="viewModal">
    <div class="mod-modal" style="max-width:700px">
        <div class="mod-modal-header"><h3><i class="fas fa-envelope-open" style="color:var(--accent)"></i> <span id="viewSubject"></span></h3><button class="mod-modal-close" onclick="closeView()">×</button></div>
        <div class="mod-modal-body" id="viewContent"></div>
        <div class="mod-modal-footer" id="viewFooter"></div>
    </div>
</div>

<script>
function openCompose(){document.getElementById('composeModal').classList.add('show')}
function closeCompose(){document.getElementById('composeModal').classList.remove('show')}
function closeView(){document.getElementById('viewModal').classList.remove('show')}

function viewEmail(id){
    // Mark as read
    const fd=new FormData();fd.append('email_action','read');fd.append('id',id);
    fetch(location.href,{method:'POST',body:fd});

    fetch('/admin/modules/email/api.php?action=get&id='+id).then(r=>r.json()).then(d=>{
        if(!d.success)return;const e=d.email;
        document.getElementById('viewSubject').textContent=e.subject||'(sans objet)';
        document.getElementById('viewContent').innerHTML=`
            <div class="mod-flex mod-items-center mod-gap" style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border)">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0">${(e.from_name||e.from_email||'?').charAt(0).toUpperCase()}</div>
                <div style="flex:1"><div class="mod-text-sm" style="font-weight:600">${e.from_name||e.from_email}</div><div class="mod-text-xs mod-text-muted">${e.from_email} → ${e.to_email}</div></div>
                <div class="mod-text-xs mod-text-muted">${e.sent_at||''}</div>
            </div>
            <div style="font-size:.85rem;line-height:1.6;color:var(--text)">${e.body_html||('<pre style="white-space:pre-wrap;font-family:var(--font)">'+(e.body_text||'')+'</pre>')}</div>`;
        document.getElementById('viewFooter').innerHTML=`
            <button class="mod-btn mod-btn-secondary" onclick="closeView()">Fermer</button>
            <form method="POST" style="display:inline"><input type="hidden" name="email_action" value="delete"><input type="hidden" name="id" value="${e.id}"><button type="submit" class="mod-btn" style="background:var(--red);color:#fff;border-color:var(--red)"><i class="fas fa-trash"></i> Supprimer</button></form>`;
        document.getElementById('viewModal').classList.add('show');
    });
}

document.getElementById('composeForm').addEventListener('submit',function(e){
    e.preventDefault();
    fetch(this.action,{method:'POST',body:new FormData(this)}).then(r=>r.json()).then(d=>{
        if(d.success){closeCompose();showNotif('Email envoyé','success');setTimeout(()=>location.reload(),800)}
        else showNotif(d.error||'Erreur envoi','error');
    }).catch(()=>showNotif('Erreur connexion','error'));
});

function showNotif(msg,type='info'){const c={success:'var(--green)',error:'var(--red)',info:'var(--accent)'},n=document.createElement('div');n.style.cssText=`position:fixed;top:20px;right:20px;padding:14px 20px;background:${c[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;n.textContent=msg;document.body.appendChild(n);setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),300)},2500)}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){closeCompose();closeView()}});
document.querySelectorAll('.mod-overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this){closeCompose();closeView()}}));
</script>