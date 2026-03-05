<?php
/**
 * journal-widget.php — Composant reutilisable
 * Inclus dans chaque module canal avec les variables :
 *   $journal_channel       → 'blog' | 'gmb' | 'facebook' | etc.
 *   $journal_channel_label → 'Blog / Article SEO' (optionnel, sinon auto)
 *   $journal_channel_icon  → 'fas fa-pen-fancy' (optionnel, sinon auto)
 *   $journal_channel_color → '#2c3e50' (optionnel, sinon auto)
 *   $journal_content_types → ['article-pilier','article-satellite'] (optionnel)
 *
 * Fichier : admin/modules/journal/journal-widget.php
 */

// ================================================================
// INIT
// ================================================================

if (empty($journal_channel)) {
    echo '<div class="alert alert-danger">Erreur : $journal_channel non defini</div>';
    return;
}

require_once __DIR__ . '/JournalController.php';

// DB — utilise $pdo global
if (!isset($pdo)) {
    echo '<div class="alert alert-danger">Erreur : connexion $pdo non disponible</div>';
    return;
}

$jCtrl = new JournalController($pdo);

// Verifier que la table existe
if (!$jCtrl->tableExists()) {
    echo '<div class="alert alert-warning" style="margin:20px;">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Module Journal Editorial</strong> — La table <code>editorial_journal</code> n\'existe pas encore.
        <br>Executez le fichier <code>modules/journal/sql/journal.sql</code> dans phpMyAdmin.
    </div>';
    return;
}

// Auto-remplir les variables depuis les constantes
$channelInfo = JournalController::CHANNELS[$journal_channel] ?? null;
if (!$channelInfo) {
    echo '<div class="alert alert-danger">Canal inconnu : ' . htmlspecialchars($journal_channel) . '</div>';
    return;
}

$journal_channel_label = $journal_channel_label ?? $channelInfo['label'];
$journal_channel_icon  = $journal_channel_icon  ?? $channelInfo['icon'];
$journal_channel_color = $journal_channel_color ?? $channelInfo['color'];
$journal_create_url    = $journal_create_url    ?? $channelInfo['create_url'];

// Charger les donnees
$channelStats = $jCtrl->getChannelStats($journal_channel);
$secteurs     = $jCtrl->getSecteurs();
$items        = $jCtrl->getList(['channel_id' => $journal_channel]);
$currentWeek  = JournalController::getCurrentWeek();
$csrfToken    = $_SESSION['csrf_token'] ?? '';

// Semaines presentes dans les donnees
$weeks = [];
foreach ($items as $item) {
    $wk = 'S' . $item['week_number'];
    if (!in_array($wk, $weeks)) $weeks[] = $wk;
}
?>

<!-- ================================================================ -->
<!-- CSS WIDGET JOURNAL -->
<!-- ================================================================ -->
<style>
.jw { --jw-color: <?= $journal_channel_color ?>; }
.jw-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; flex-wrap:wrap; gap:12px; }
.jw-header h3 { margin:0; font-size:1.25rem; display:flex; align-items:center; gap:10px; }
.jw-header h3 i { color:var(--jw-color); }
.jw-header-actions { display:flex; gap:8px; flex-wrap:wrap; }

/* Stats mini */
.jw-stats { display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.jw-stat { background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:10px; padding:12px 18px; text-align:center; min-width:90px; }
.jw-stat-val { font-size:1.4rem; font-weight:700; color:var(--text, #1a1a2e); }
.jw-stat-label { font-size:.75rem; color:var(--text-secondary, #6b7280); margin-top:2px; }

/* Filtres */
.jw-filters { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center; }
.jw-filters select { padding:6px 10px; border:1px solid var(--border, #d1d5db); border-radius:8px; font-size:.82rem; background:var(--surface, #fff); color:var(--text, #1a1a2e); }
.jw-filters select:focus { outline:none; border-color:var(--jw-color); box-shadow:0 0 0 2px rgba(0,0,0,.05); }

/* Table */
.jw-table-wrap { overflow-x:auto; background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:12px; }
.jw-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.jw-table thead th { background:var(--bg-alt, #f8fafc); padding:10px 12px; text-align:left; font-weight:600; font-size:.78rem; text-transform:uppercase; color:var(--text-secondary, #6b7280); border-bottom:2px solid var(--border, #e0e0e0); white-space:nowrap; }
.jw-table tbody tr { border-bottom:1px solid var(--border-light, #f0f0f0); transition:background .15s; }
.jw-table tbody tr:hover { background:var(--bg-hover, #f8fafc); }
.jw-table tbody td { padding:10px 12px; vertical-align:middle; }
.jw-table tbody tr.jw-current-week { background:rgba(59,130,246,.04); }

/* Checkbox colonne */
.jw-cb { width:18px; height:18px; accent-color:var(--jw-color); cursor:pointer; }

/* Badges */
.jw-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; white-space:nowrap; }
.jw-badge-profile { color:#fff; }
.jw-badge-awareness { border:1px solid; background:transparent; }
.jw-badge-status { color:#fff; }
.jw-badge-week { background:var(--bg-alt, #f0f4f8); color:var(--text-secondary, #6b7280); font-weight:700; font-size:.7rem; padding:2px 8px; border-radius:6px; }
.jw-badge-week.jw-week-current { background:var(--jw-color); color:#fff; }
.jw-badge-type { background:#f0f4f8; color:#475569; font-size:.7rem; padding:2px 8px; border-radius:6px; }

/* Titre editable */
.jw-title { font-weight:500; max-width:320px; }
.jw-title a { color:var(--text, #1a1a2e); text-decoration:none; }
.jw-title a:hover { color:var(--jw-color); }

/* Actions */
.jw-actions { display:flex; gap:4px; }
.jw-btn-sm { display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; border:1px solid var(--border, #d1d5db); border-radius:8px; background:var(--surface, #fff); color:var(--text-secondary, #6b7280); cursor:pointer; transition:all .15s; font-size:.8rem; }
.jw-btn-sm:hover { border-color:var(--jw-color); color:var(--jw-color); background:var(--bg-hover, #f8fafc); }
.jw-btn-sm.jw-btn-validate:hover { border-color:#2ecc71; color:#2ecc71; }
.jw-btn-sm.jw-btn-create:hover { border-color:var(--jw-color); color:#fff; background:var(--jw-color); }
.jw-btn-sm.jw-btn-reject:hover { border-color:#e74c3c; color:#e74c3c; }
.jw-btn-sm.jw-btn-delete:hover { border-color:#e74c3c; color:#fff; background:#e74c3c; }

/* Boutons principaux */
.jw-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border:none; border-radius:10px; font-size:.85rem; font-weight:600; cursor:pointer; transition:all .2s; }
.jw-btn-primary { background:var(--jw-color); color:#fff; }
.jw-btn-primary:hover { opacity:.9; transform:translateY(-1px); }
.jw-btn-outline { background:transparent; border:1px solid var(--border, #d1d5db); color:var(--text, #1a1a2e); }
.jw-btn-outline:hover { border-color:var(--jw-color); color:var(--jw-color); }
.jw-btn-success { background:#2ecc71; color:#fff; }
.jw-btn-success:hover { background:#27ae60; }
.jw-btn-danger { background:#e74c3c; color:#fff; }

/* Toolbar masse */
.jw-bulk-bar { display:none; align-items:center; gap:12px; padding:10px 16px; background:var(--jw-color); color:#fff; border-radius:10px; margin-bottom:12px; font-size:.85rem; }
.jw-bulk-bar.active { display:flex; }
.jw-bulk-bar .jw-btn { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.3); }
.jw-bulk-bar .jw-btn:hover { background:rgba(255,255,255,.35); }
.jw-bulk-count { font-weight:700; }

/* Vide */
.jw-empty { text-align:center; padding:48px 20px; color:var(--text-secondary, #6b7280); }
.jw-empty i { font-size:2.5rem; color:var(--border, #d1d5db); display:block; margin-bottom:12px; }

/* Modal */
.jw-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9998; justify-content:center; align-items:center; }
.jw-modal-overlay.active { display:flex; }
.jw-modal { background:var(--surface, #fff); border-radius:16px; padding:28px; width:580px; max-width:95vw; max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.jw-modal h3 { margin:0 0 20px; font-size:1.1rem; display:flex; align-items:center; gap:8px; }
.jw-modal-row { margin-bottom:14px; }
.jw-modal-row label { display:block; font-size:.8rem; font-weight:600; margin-bottom:4px; color:var(--text-secondary, #6b7280); }
.jw-modal-row input, .jw-modal-row select, .jw-modal-row textarea { width:100%; padding:8px 12px; border:1px solid var(--border, #d1d5db); border-radius:8px; font-size:.88rem; background:var(--surface, #fff); color:var(--text, #1a1a2e); box-sizing:border-box; }
.jw-modal-row textarea { min-height:70px; resize:vertical; }
.jw-modal-row input:focus, .jw-modal-row select:focus, .jw-modal-row textarea:focus { outline:none; border-color:var(--jw-color); }
.jw-modal-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
.jw-modal-actions { display:flex; gap:8px; justify-content:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid var(--border-light, #f0f0f0); }

/* Toast */
.jw-toast { position:fixed; bottom:24px; right:24px; z-index:9999; padding:12px 20px; border-radius:10px; color:#fff; font-size:.88rem; font-weight:500; box-shadow:0 8px 24px rgba(0,0,0,.15); transform:translateY(100px); opacity:0; transition:all .3s; }
.jw-toast.active { transform:translateY(0); opacity:1; }
.jw-toast.success { background:#2ecc71; }
.jw-toast.error { background:#e74c3c; }

/* Published link */
.jw-published-link { color:#27ae60; font-size:.75rem; }
.jw-published-link:hover { text-decoration:underline; }

/* Responsive */
@media (max-width:768px) {
    .jw-stats { gap:6px; }
    .jw-stat { min-width:70px; padding:8px 10px; }
    .jw-stat-val { font-size:1.1rem; }
    .jw-modal-grid { grid-template-columns:1fr; }
    .jw-title { max-width:180px; }
    .jw-table { font-size:.8rem; }
}
</style>

<!-- ================================================================ -->
<!-- HTML WIDGET -->
<!-- ================================================================ -->
<div class="jw" id="jw-<?= $journal_channel ?>">

    <!-- HEADER -->
    <div class="jw-header">
        <h3>
            <i class="<?= htmlspecialchars($journal_channel_icon) ?>"></i>
            Journal <?= htmlspecialchars($journal_channel_label) ?>
        </h3>
        <div class="jw-header-actions">
            <button class="jw-btn jw-btn-outline" onclick="jwOpenModal('<?= $journal_channel ?>')">
                <i class="fas fa-plus"></i> Nouvelle idee
            </button>
            <button class="jw-btn jw-btn-primary" onclick="jwGenerate('<?= $journal_channel ?>')">
                <i class="fas fa-magic"></i> Generer IA
            </button>
        </div>
    </div>

    <!-- STATS MINI -->
    <div class="jw-stats">
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['total'] ?></div>
            <div class="jw-stat-label">Total</div>
        </div>
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['ideas'] ?></div>
            <div class="jw-stat-label">Idees</div>
        </div>
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['validated'] ?></div>
            <div class="jw-stat-label">Valides</div>
        </div>
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['writing'] ?></div>
            <div class="jw-stat-label">En cours</div>
        </div>
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['ready'] ?></div>
            <div class="jw-stat-label">Prets</div>
        </div>
        <div class="jw-stat">
            <div class="jw-stat-val"><?= (int)$channelStats['published'] ?></div>
            <div class="jw-stat-label">Publies</div>
        </div>
    </div>

    <!-- FILTRES -->
    <div class="jw-filters">
        <select id="jw-f-status-<?= $journal_channel ?>" onchange="jwFilter('<?= $journal_channel ?>')">
            <option value="">Tous les statuts</option>
            <?php foreach (JournalController::STATUSES as $k => $s): if ($k === 'rejected') continue; ?>
                <option value="<?= $k ?>"><?= $s['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select id="jw-f-profile-<?= $journal_channel ?>" onchange="jwFilter('<?= $journal_channel ?>')">
            <option value="">Tous les profils</option>
            <?php foreach (JournalController::PROFILES as $k => $p): ?>
                <option value="<?= $k ?>"><?= $p['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select id="jw-f-awareness-<?= $journal_channel ?>" onchange="jwFilter('<?= $journal_channel ?>')">
            <option value="">Tous les niveaux</option>
            <?php foreach (JournalController::AWARENESS as $k => $a): ?>
                <option value="<?= $k ?>"><?= $a['short'] ?></option>
            <?php endforeach; ?>
        </select>
        <select id="jw-f-sector-<?= $journal_channel ?>" onchange="jwFilter('<?= $journal_channel ?>')">
            <option value="">Tous les secteurs</option>
            <?php foreach ($secteurs as $s): ?>
                <option value="<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['nom']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="jw-f-week-<?= $journal_channel ?>" onchange="jwFilter('<?= $journal_channel ?>')">
            <option value="">Toutes les semaines</option>
            <?php for ($w = 1; $w <= 52; $w++): ?>
                <option value="<?= $w ?>" <?= ($w === $currentWeek['week']) ? 'selected' : '' ?>>S<?= $w ?><?= ($w === $currentWeek['week']) ? ' (en cours)' : '' ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <!-- TOOLBAR MASSE -->
    <div class="jw-bulk-bar" id="jw-bulk-<?= $journal_channel ?>">
        <span><span class="jw-bulk-count" id="jw-bulk-count-<?= $journal_channel ?>">0</span> selectionne(s)</span>
        <button class="jw-btn" onclick="jwBulkAction('<?= $journal_channel ?>', 'bulk-validate')">
            <i class="fas fa-check"></i> Valider
        </button>
        <button class="jw-btn" onclick="jwBulkAction('<?= $journal_channel ?>', 'bulk-reject')">
            <i class="fas fa-times"></i> Rejeter
        </button>
        <button class="jw-btn" onclick="jwBulkAction('<?= $journal_channel ?>', 'bulk-delete')">
            <i class="fas fa-trash"></i> Supprimer
        </button>
    </div>

    <!-- TABLE -->
    <div class="jw-table-wrap">
        <?php if (empty($items)): ?>
            <div class="jw-empty">
                <i class="<?= htmlspecialchars($journal_channel_icon) ?>"></i>
                Aucune idee pour ce canal.<br>
                <button class="jw-btn jw-btn-primary" style="margin-top:16px;" onclick="jwGenerate('<?= $journal_channel ?>')">
                    <i class="fas fa-magic"></i> Generer des idees avec l'IA
                </button>
            </div>
        <?php else: ?>
            <table class="jw-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" class="jw-cb" onchange="jwToggleAll('<?= $journal_channel ?>', this)"></th>
                        <th style="width:50px;">Sem.</th>
                        <th>Titre</th>
                        <th style="width:100px;">Profil</th>
                        <th style="width:90px;">Conscience</th>
                        <th style="width:100px;">Secteur</th>
                        <th style="width:80px;">Type</th>
                        <th style="width:100px;">Statut</th>
                        <th style="width:120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="jw-body-<?= $journal_channel ?>">
                    <?php foreach ($items as $item):
                        $profile   = JournalController::PROFILES[$item['profile_id']] ?? ['label' => $item['profile_id'], 'color' => '#999'];
                        $awareness = JournalController::AWARENESS[$item['awareness_level']] ?? ['short' => '?', 'color' => '#999'];
                        $status    = JournalController::STATUSES[$item['status']] ?? ['label' => '?', 'color' => '#999'];
                        $type      = JournalController::CONTENT_TYPES[$item['content_type']] ?? $item['content_type'];
                        $isCurrentWeek = ((int)$item['week_number'] === $currentWeek['week'] && (int)$item['year'] === $currentWeek['year']);

                        // Trouver le nom du secteur
                        $sectorName = '';
                        foreach ($secteurs as $sec) {
                            if ($sec['slug'] === $item['sector_id']) { $sectorName = $sec['nom']; break; }
                        }
                    ?>
                    <tr class="jw-row <?= $isCurrentWeek ? 'jw-current-week' : '' ?>"
                        data-id="<?= (int)$item['id'] ?>"
                        data-status="<?= htmlspecialchars($item['status']) ?>"
                        data-profile="<?= htmlspecialchars($item['profile_id']) ?>"
                        data-awareness="<?= htmlspecialchars($item['awareness_level']) ?>"
                        data-sector="<?= htmlspecialchars($item['sector_id']) ?>"
                        data-week="<?= (int)$item['week_number'] ?>">
                        
                        <!-- Checkbox -->
                        <td><input type="checkbox" class="jw-cb jw-row-cb" value="<?= (int)$item['id'] ?>" onchange="jwUpdateBulk('<?= $journal_channel ?>')"></td>
                        
                        <!-- Semaine -->
                        <td>
                            <span class="jw-badge-week <?= $isCurrentWeek ? 'jw-week-current' : '' ?>">
                                S<?= (int)$item['week_number'] ?>
                            </span>
                        </td>
                        
                        <!-- Titre -->
                        <td class="jw-title">
                            <a href="javascript:void(0)" onclick="jwEdit(<?= (int)$item['id'] ?>, '<?= $journal_channel ?>')" title="Modifier">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                            <?php if ($item['status'] === 'published' && !empty($item['published_url'])): ?>
                                <br><a href="<?= htmlspecialchars($item['published_url']) ?>" target="_blank" class="jw-published-link">
                                    <i class="fas fa-external-link-alt"></i> Voir en ligne
                                </a>
                            <?php endif; ?>
                        </td>
                        
                        <!-- Profil -->
                        <td>
                            <span class="jw-badge jw-badge-profile" style="background:<?= $profile['color'] ?>">
                                <?= $profile['label'] ?>
                            </span>
                        </td>
                        
                        <!-- Conscience -->
                        <td>
                            <span class="jw-badge jw-badge-awareness" style="color:<?= $awareness['color'] ?>;border-color:<?= $awareness['color'] ?>">
                                <?= $awareness['short'] ?>
                            </span>
                        </td>
                        
                        <!-- Secteur -->
                        <td style="font-size:.78rem;">
                            <?= htmlspecialchars($sectorName ?: '-') ?>
                        </td>
                        
                        <!-- Type contenu -->
                        <td>
                            <span class="jw-badge-type"><?= htmlspecialchars($type) ?></span>
                        </td>
                        
                        <!-- Statut -->
                        <td>
                            <span class="jw-badge jw-badge-status" style="background:<?= $status['color'] ?>">
                                <i class="<?= $status['icon'] ?>" style="font-size:.65rem;"></i>
                                <?= $status['label'] ?>
                            </span>
                        </td>
                        
                        <!-- Actions -->
                        <td>
                            <div class="jw-actions">
                                <?php if (in_array($item['status'], ['idea', 'planned'])): ?>
                                    <button class="jw-btn-sm jw-btn-validate" onclick="jwStatus(<?= (int)$item['id'] ?>, 'validated', '<?= $journal_channel ?>')" title="Valider">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if (in_array($item['status'], ['validated', 'writing'])): ?>
                                    <a href="<?= htmlspecialchars($jCtrl->getCreateContentUrl($item)) ?>" class="jw-btn-sm jw-btn-create" title="Creer le contenu">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($item['status'] === 'ready'): ?>
                                    <button class="jw-btn-sm jw-btn-validate" onclick="jwStatus(<?= (int)$item['id'] ?>, 'published', '<?= $journal_channel ?>')" title="Marquer publie">
                                        <i class="fas fa-rocket"></i>
                                    </button>
                                <?php endif; ?>

                                <button class="jw-btn-sm" onclick="jwEdit(<?= (int)$item['id'] ?>, '<?= $journal_channel ?>')" title="Modifier">
                                    <i class="fas fa-pen"></i>
                                </button>

                                <?php if (in_array($item['status'], ['idea', 'planned'])): ?>
                                    <button class="jw-btn-sm jw-btn-reject" onclick="jwStatus(<?= (int)$item['id'] ?>, 'rejected', '<?= $journal_channel ?>')" title="Rejeter">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>

                                <button class="jw-btn-sm jw-btn-delete" onclick="jwDelete(<?= (int)$item['id'] ?>, '<?= $journal_channel ?>')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- ================================================================ -->
<!-- MODAL CREATION / EDITION -->
<!-- ================================================================ -->
<div class="jw-modal-overlay" id="jw-modal-overlay-<?= $journal_channel ?>" onclick="if(event.target===this)jwCloseModal('<?= $journal_channel ?>')">
    <div class="jw-modal">
        <h3>
            <i class="<?= htmlspecialchars($journal_channel_icon) ?>" style="color:<?= $journal_channel_color ?>"></i>
            <span id="jw-modal-title-<?= $journal_channel ?>">Nouvelle idee</span>
        </h3>
        
        <input type="hidden" id="jw-modal-id-<?= $journal_channel ?>" value="0">
        
        <div class="jw-modal-row">
            <label>Titre *</label>
            <input type="text" id="jw-modal-field-title-<?= $journal_channel ?>" placeholder="Titre du contenu...">
        </div>
        
        <div class="jw-modal-row">
            <label>Description</label>
            <textarea id="jw-modal-field-description-<?= $journal_channel ?>" placeholder="Description, brief, angle..."></textarea>
        </div>
        
        <div class="jw-modal-grid">
            <div class="jw-modal-row">
                <label>Profil cible *</label>
                <select id="jw-modal-field-profile_id-<?= $journal_channel ?>">
                    <?php foreach (JournalController::PROFILES as $k => $p): ?>
                        <option value="<?= $k ?>"><?= $p['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Niveau de conscience</label>
                <select id="jw-modal-field-awareness_level-<?= $journal_channel ?>">
                    <?php foreach (JournalController::AWARENESS as $k => $a): ?>
                        <option value="<?= $k ?>"><?= $a['short'] ?> — <?= $a['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Secteur</label>
                <select id="jw-modal-field-sector_id-<?= $journal_channel ?>">
                    <option value="">— Aucun —</option>
                    <?php foreach ($secteurs as $s): ?>
                        <option value="<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Type de contenu</label>
                <select id="jw-modal-field-content_type-<?= $journal_channel ?>">
                    <?php foreach (JournalController::CONTENT_TYPES as $k => $t): ?>
                        <option value="<?= $k ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Objectif</label>
                <select id="jw-modal-field-objective_id-<?= $journal_channel ?>">
                    <?php foreach (JournalController::OBJECTIVES as $k => $o): ?>
                        <option value="<?= $k ?>"><?= $o['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Semaine</label>
                <select id="jw-modal-field-week_number-<?= $journal_channel ?>">
                    <?php for ($w = 1; $w <= 52; $w++): ?>
                        <option value="<?= $w ?>" <?= ($w === $currentWeek['week']) ? 'selected' : '' ?>>S<?= $w ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>CTA</label>
                <select id="jw-modal-field-cta_type-<?= $journal_channel ?>">
                    <option value="">— Aucun —</option>
                    <?php foreach (JournalController::CTA_TYPES as $k => $t): ?>
                        <option value="<?= $k ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jw-modal-row">
                <label>Priorite (1=urgent, 10=bas)</label>
                <select id="jw-modal-field-priority-<?= $journal_channel ?>">
                    <?php for ($p = 1; $p <= 10; $p++): ?>
                        <option value="<?= $p ?>" <?= ($p === 5) ? 'selected' : '' ?>><?= $p ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        
        <div class="jw-modal-row">
            <label>Mots-cles SEO</label>
            <input type="text" id="jw-modal-field-keywords-<?= $journal_channel ?>" placeholder="mot1, mot2, mot3...">
        </div>
        
        <div class="jw-modal-row">
            <label>Notes</label>
            <textarea id="jw-modal-field-notes-<?= $journal_channel ?>" placeholder="Notes internes..."></textarea>
        </div>
        
        <div class="jw-modal-actions">
            <button class="jw-btn jw-btn-outline" onclick="jwCloseModal('<?= $journal_channel ?>')">Annuler</button>
            <button class="jw-btn jw-btn-primary" id="jw-modal-save-<?= $journal_channel ?>" onclick="jwSave('<?= $journal_channel ?>')">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="jw-toast" id="jw-toast-<?= $journal_channel ?>"></div>

<!-- ================================================================ -->
<!-- JAVASCRIPT WIDGET
<!-- ================================================================ -->
<script>
(function(){
    const CH = '<?= $journal_channel ?>';
    const API = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/modules/journal/api/journal.php';
    const CSRF = '<?= $csrfToken ?>';
    const YEAR = <?= $currentWeek['year'] ?>;

    // ── API Helper ──
    async function api(action, method = 'GET', body = null) {
        const opts = {
            method,
            headers: { 'X-CSRF-Token': CSRF }
        };
        let url = API + '?action=' + action;

        if (method === 'POST' && body) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        } else if (method === 'GET' && body) {
            const params = new URLSearchParams(body);
            url += '&' + params.toString();
        }

        const res = await fetch(url);
        return res.json();
    }

    // ── Toast ──
    window.jwToast = function(ch, msg, type = 'success') {
        const t = document.getElementById('jw-toast-' + ch);
        t.textContent = msg;
        t.className = 'jw-toast ' + type + ' active';
        setTimeout(() => t.classList.remove('active'), 3000);
    };

    // ── Filtres (cote client) ──
    window.jwFilter = function(ch) {
        const status    = document.getElementById('jw-f-status-' + ch).value;
        const profile   = document.getElementById('jw-f-profile-' + ch).value;
        const awareness = document.getElementById('jw-f-awareness-' + ch).value;
        const sector    = document.getElementById('jw-f-sector-' + ch).value;
        const week      = document.getElementById('jw-f-week-' + ch).value;

        const rows = document.querySelectorAll('#jw-body-' + ch + ' .jw-row');
        rows.forEach(row => {
            let show = true;
            if (status && row.dataset.status !== status)       show = false;
            if (profile && row.dataset.profile !== profile)    show = false;
            if (awareness && row.dataset.awareness !== awareness) show = false;
            if (sector && row.dataset.sector !== sector)       show = false;
            if (week && row.dataset.week !== week)             show = false;
            row.style.display = show ? '' : 'none';
        });
    };

    // ── Checkboxes ──
    window.jwToggleAll = function(ch, master) {
        const cbs = document.querySelectorAll('#jw-body-' + ch + ' .jw-row-cb');
        cbs.forEach(cb => {
            if (cb.closest('.jw-row').style.display !== 'none') {
                cb.checked = master.checked;
            }
        });
        jwUpdateBulk(ch);
    };

    window.jwUpdateBulk = function(ch) {
        const checked = document.querySelectorAll('#jw-body-' + ch + ' .jw-row-cb:checked');
        const bar = document.getElementById('jw-bulk-' + ch);
        const count = document.getElementById('jw-bulk-count-' + ch);
        if (checked.length > 0) {
            bar.classList.add('active');
            count.textContent = checked.length;
        } else {
            bar.classList.remove('active');
        }
    };

    // ── Actions en masse ──
    window.jwBulkAction = async function(ch, action) {
        const checked = document.querySelectorAll('#jw-body-' + ch + ' .jw-row-cb:checked');
        const ids = Array.from(checked).map(cb => parseInt(cb.value));
        if (!ids.length) return;

        const labels = { 'bulk-validate': 'valider', 'bulk-reject': 'rejeter', 'bulk-delete': 'supprimer' };
        if (!confirm('Voulez-vous ' + labels[action] + ' ' + ids.length + ' element(s) ?')) return;

        const res = await api(action, 'POST', { ids });
        if (res.success) {
            jwToast(ch, res.message, 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            jwToast(ch, res.error || 'Erreur', 'error');
        }
    };

    // ── Changer statut ──
    window.jwStatus = async function(id, status, ch) {
        const res = await api('status', 'POST', { id, status });
        if (res.success) {
            jwToast(ch, res.message, 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            jwToast(ch, res.error || 'Erreur', 'error');
        }
    };

    // ── Supprimer ──
    window.jwDelete = async function(id, ch) {
        if (!confirm('Supprimer cette idee ?')) return;
        const res = await api('delete', 'POST', { id });
        if (res.success) {
            jwToast(ch, 'Supprime', 'success');
            const row = document.querySelector('#jw-body-' + ch + ' .jw-row[data-id="' + id + '"]');
            if (row) row.remove();
        } else {
            jwToast(ch, res.error || 'Erreur', 'error');
        }
    };

    // ── Modal : Ouvrir (creation) ──
    window.jwOpenModal = function(ch) {
        document.getElementById('jw-modal-overlay-' + ch).classList.add('active');
        document.getElementById('jw-modal-title-' + ch).textContent = 'Nouvelle idee';
        document.getElementById('jw-modal-id-' + ch).value = '0';
        // Reset tous les champs
        const fields = ['title','description','keywords','notes'];
        fields.forEach(f => {
            const el = document.getElementById('jw-modal-field-' + f + '-' + ch);
            if (el) el.value = '';
        });
    };

    // ── Modal : Ouvrir (edition) ──
    window.jwEdit = async function(id, ch) {
        const res = await api('get', 'GET', { id });
        if (!res.success || !res.data) { jwToast(ch, 'Introuvable', 'error'); return; }
        
        const d = res.data;
        document.getElementById('jw-modal-overlay-' + ch).classList.add('active');
        document.getElementById('jw-modal-title-' + ch).textContent = 'Modifier l\'idee #' + id;
        document.getElementById('jw-modal-id-' + ch).value = id;

        const map = ['title','description','profile_id','awareness_level','sector_id',
                      'content_type','objective_id','week_number','cta_type','priority','keywords','notes'];
        map.forEach(f => {
            const el = document.getElementById('jw-modal-field-' + f + '-' + ch);
            if (el && d[f] !== null && d[f] !== undefined) el.value = d[f];
        });
    };

    // ── Modal : Fermer ──
    window.jwCloseModal = function(ch) {
        document.getElementById('jw-modal-overlay-' + ch).classList.remove('active');
    };

    // ── Modal : Sauvegarder ──
    window.jwSave = async function(ch) {
        const id = parseInt(document.getElementById('jw-modal-id-' + ch).value);
        const fields = ['title','description','profile_id','awareness_level','sector_id',
                        'content_type','objective_id','week_number','cta_type','priority','keywords','notes'];
        
        const data = { channel_id: ch, year: YEAR };
        fields.forEach(f => {
            const el = document.getElementById('jw-modal-field-' + f + '-' + ch);
            if (el) data[f] = el.value;
        });

        if (!data.title || !data.title.trim()) {
            jwToast(ch, 'Le titre est requis', 'error');
            return;
        }

        let res;
        if (id > 0) {
            data.id = id;
            res = await api('update', 'POST', data);
        } else {
            res = await api('create', 'POST', data);
        }

        if (res.success) {
            jwToast(ch, res.message, 'success');
            jwCloseModal(ch);
            setTimeout(() => location.reload(), 600);
        } else {
            jwToast(ch, (res.errors || [res.error]).join(', '), 'error');
        }
    };

    // ── Generer IA ──
    window.jwGenerate = async function(ch) {
        if (!confirm('Generer des idees IA pour ' + ch + ' (4 prochaines semaines) ?')) return;
        
        const btn = event.target.closest('.jw-btn');
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generation...';
        btn.disabled = true;

        const res = await api('generate', 'POST', { channel_id: ch, weeks: 4 });
        
        btn.innerHTML = oldHtml;
        btn.disabled = false;

        if (res.success) {
            jwToast(ch, res.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            jwToast(ch, res.error || 'Erreur generation', 'error');
        }
    };

})();
</script>