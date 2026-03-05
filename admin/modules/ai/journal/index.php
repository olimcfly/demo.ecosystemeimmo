<?php
/**
 * index.php — Hub Central "Ma Strategie Contenu"
 * Module Journal Editorial V3
 * Fichier : admin/modules/journal/index.php
 *
 * 4 onglets :
 *   - global     : Vue d'ensemble (cards par canal + dernieres idees)
 *   - matrice    : Matrice strategique profil x conscience
 *   - generate   : Generateur IA
 *   - performance: Stats et pipeline editorial
 *
 * Aussi utilise en mode "channel" pour afficher le widget d'un canal
 * quand on arrive via la sidebar (ex: ?page=articles-journal)
 */

// ================================================================
// INIT
// ================================================================

require_once __DIR__ . '/JournalController.php';

if (!isset($pdo)) {
    echo '<div class="alert alert-danger">Erreur : connexion $pdo non disponible</div>';
    return;
}

$jCtrl = new JournalController($pdo);

// Verifier la table
if (!$jCtrl->tableExists()) {
    echo '<div style="padding:40px;text-align:center;">
        <i class="fas fa-database" style="font-size:3rem;color:#d1d5db;"></i>
        <h3 style="margin-top:16px;">Module Journal Editorial</h3>
        <p style="color:#6b7280;">La table <code>editorial_journal</code> n\'existe pas encore.</p>
        <p>Executez <code>modules/journal/sql/journal.sql</code> dans phpMyAdmin.</p>
    </div>';
    return;
}

// ================================================================
// ROUTAGE : mode hub ou mode canal
// ================================================================

$tab     = $_GET['tab']     ?? 'global';
$channel = $_GET['channel'] ?? null;

// Si on arrive avec un canal specifique, afficher le widget
if ($tab === 'channel' && $channel) {
    $journal_channel = $channel;
    include __DIR__ . '/journal-widget.php';
    return;
}

// ================================================================
// DONNEES HUB
// ================================================================

$statsGlobal   = $jCtrl->getStatsGlobal();
$statsByChannel = $jCtrl->getStatsByChannel();
$matrixData    = $jCtrl->getMatrixData();
$config        = $jCtrl->getConfig();
$csrfToken     = $_SESSION['csrf_token'] ?? '';
$currentWeek   = JournalController::getCurrentWeek();

// Dernieres idees tous canaux (20 plus recentes)
$latestItems = $jCtrl->getList([], 20, 0);

// Indexer stats par canal
$channelStatsMap = [];
foreach ($statsByChannel as $cs) {
    $channelStatsMap[$cs['channel_id']] = $cs;
}
?>

<!-- ================================================================ -->
<!-- CSS HUB -->
<!-- ================================================================ -->
<style>
/* Onglets */
.jh-tabs { display:flex; gap:4px; border-bottom:2px solid var(--border, #e5e7eb); margin-bottom:24px; padding-bottom:0; }
.jh-tab { padding:10px 20px; font-size:.88rem; font-weight:600; color:var(--text-secondary, #6b7280); background:none; border:none; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:all .2s; border-radius:8px 8px 0 0; }
.jh-tab:hover { color:var(--text, #1a1a2e); background:var(--bg-hover, #f8fafc); }
.jh-tab.active { color:var(--accent, #3b82f6); border-bottom-color:var(--accent, #3b82f6); }
.jh-panel { display:none; }
.jh-panel.active { display:block; }

/* Header page */
.jh-page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.jh-page-header h2 { margin:0; font-size:1.4rem; display:flex; align-items:center; gap:10px; }

/* Stats globales */
.jh-global-stats { display:flex; gap:14px; margin-bottom:28px; flex-wrap:wrap; }
.jh-gstat { background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:12px; padding:16px 22px; text-align:center; min-width:110px; flex:1; }
.jh-gstat-val { font-size:1.8rem; font-weight:800; color:var(--text, #1a1a2e); }
.jh-gstat-label { font-size:.78rem; color:var(--text-secondary, #6b7280); margin-top:4px; }

/* Cards canaux */
.jh-channels { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:16px; margin-bottom:32px; }
.jh-ch-card { background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:14px; padding:20px; transition:all .2s; cursor:pointer; position:relative; overflow:hidden; }
.jh-ch-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.06); }
.jh-ch-card-accent { position:absolute; top:0; left:0; right:0; height:4px; }
.jh-ch-card h4 { margin:8px 0 12px; font-size:1rem; display:flex; align-items:center; gap:8px; }
.jh-ch-card h4 i { font-size:1.1rem; }
.jh-ch-card-stats { display:grid; grid-template-columns:1fr 1fr 1fr; gap:6px; }
.jh-ch-mini { text-align:center; }
.jh-ch-mini-val { font-size:1.1rem; font-weight:700; }
.jh-ch-mini-label { font-size:.68rem; color:var(--text-secondary, #6b7280); }
.jh-ch-card-footer { margin-top:14px; padding-top:12px; border-top:1px solid var(--border-light, #f0f0f0); display:flex; justify-content:space-between; align-items:center; }
.jh-ch-link { font-size:.82rem; font-weight:600; color:var(--accent, #3b82f6); text-decoration:none; display:flex; align-items:center; gap:4px; }
.jh-ch-link:hover { text-decoration:underline; }
.jh-ch-progress { width:100%; height:6px; background:var(--border-light, #e5e7eb); border-radius:3px; margin-top:10px; overflow:hidden; }
.jh-ch-progress-bar { height:100%; border-radius:3px; transition:width .5s; }

/* Table dernieres idees */
.jh-latest-title { font-size:1.05rem; font-weight:700; margin:0 0 14px; display:flex; align-items:center; gap:8px; }
.jh-latest-table { width:100%; border-collapse:collapse; font-size:.83rem; background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:12px; overflow:hidden; }
.jh-latest-table thead th { background:var(--bg-alt, #f8fafc); padding:10px 12px; text-align:left; font-weight:600; font-size:.76rem; text-transform:uppercase; color:var(--text-secondary, #6b7280); }
.jh-latest-table tbody tr { border-bottom:1px solid var(--border-light, #f0f0f0); }
.jh-latest-table tbody tr:hover { background:var(--bg-hover, #f8fafc); }
.jh-latest-table tbody td { padding:9px 12px; }
.jh-badge { display:inline-flex; align-items:center; gap:3px; padding:2px 9px; border-radius:20px; font-size:.72rem; font-weight:600; color:#fff; white-space:nowrap; }

/* Matrice */
.jh-matrix-wrap { overflow-x:auto; }
.jh-matrix { width:100%; border-collapse:collapse; background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:12px; overflow:hidden; }
.jh-matrix th { padding:12px; font-size:.78rem; text-transform:uppercase; font-weight:700; background:var(--bg-alt, #f8fafc); color:var(--text-secondary, #6b7280); }
.jh-matrix td { padding:14px; text-align:center; border:1px solid var(--border-light, #f0f0f0); }
.jh-matrix-cell { display:flex; flex-direction:column; align-items:center; gap:4px; }
.jh-matrix-count { font-size:1.3rem; font-weight:800; }
.jh-matrix-pub { font-size:.7rem; color:var(--text-secondary, #6b7280); }
.jh-matrix-empty { color:#d1d5db; }
.jh-matrix-low { color:#e67e22; }
.jh-matrix-ok { color:#3498db; }
.jh-matrix-good { color:#2ecc71; }
.jh-matrix .jh-profile-th { text-align:left; font-size:.88rem; font-weight:700; color:var(--text, #1a1a2e); }

/* Generateur */
.jh-gen { background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:14px; padding:28px; max-width:600px; }
.jh-gen h3 { margin:0 0 20px; font-size:1.1rem; }
.jh-gen-row { margin-bottom:16px; }
.jh-gen-row label { display:block; font-size:.82rem; font-weight:600; color:var(--text-secondary, #6b7280); margin-bottom:6px; }
.jh-gen-row select { width:100%; padding:10px 14px; border:1px solid var(--border, #d1d5db); border-radius:10px; font-size:.9rem; }
.jh-gen-btn { display:inline-flex; align-items:center; gap:8px; padding:12px 28px; background:linear-gradient(135deg, #8b5cf6, #6366f1); color:#fff; border:none; border-radius:12px; font-size:.95rem; font-weight:700; cursor:pointer; transition:all .2s; }
.jh-gen-btn:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(99,102,241,.3); }
.jh-gen-btn:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.jh-gen-result { margin-top:16px; padding:12px 16px; border-radius:10px; font-size:.88rem; display:none; }
.jh-gen-result.success { display:block; background:#d5f5e3; color:#1e8449; }
.jh-gen-result.error { display:block; background:#fdedec; color:#c0392b; }

/* Performance */
.jh-perf-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:16px; margin-bottom:28px; }
.jh-perf-card { background:var(--surface, #fff); border:1px solid var(--border, #e0e0e0); border-radius:12px; padding:20px; text-align:center; }
.jh-perf-val { font-size:2rem; font-weight:800; }
.jh-perf-label { font-size:.8rem; color:var(--text-secondary, #6b7280); margin-top:4px; }
.jh-perf-pipeline { display:flex; align-items:center; gap:0; margin-top:20px; border-radius:12px; overflow:hidden; height:40px; background:var(--border-light, #e5e7eb); }
.jh-perf-pipeline-seg { height:100%; display:flex; align-items:center; justify-content:center; font-size:.72rem; font-weight:700; color:#fff; transition:width .5s; min-width:0; overflow:hidden; }

@media (max-width:768px) {
    .jh-channels { grid-template-columns:1fr 1fr; }
    .jh-global-stats { gap:8px; }
    .jh-gstat { min-width:80px; padding:12px; }
    .jh-gstat-val { font-size:1.3rem; }
    .jh-tabs { overflow-x:auto; }
}
</style>

<!-- ================================================================ -->
<!-- PAGE HEADER -->
<!-- ================================================================ -->
<div class="jh-page-header">
    <h2><i class="fas fa-newspaper" style="color:var(--accent, #3b82f6)"></i> Ma Strategie Contenu</h2>
    <span style="font-size:.85rem;color:var(--text-secondary,#6b7280);">
        Semaine <?= $currentWeek['week'] ?> — <?= $currentWeek['year'] ?>
    </span>
</div>

<!-- ================================================================ -->
<!-- ONGLETS -->
<!-- ================================================================ -->
<div class="jh-tabs">
    <button class="jh-tab <?= $tab === 'global' ? 'active' : '' ?>" onclick="jhTab('global')">
        <i class="fas fa-th-large"></i> Vue globale
    </button>
    <button class="jh-tab <?= $tab === 'matrice' ? 'active' : '' ?>" onclick="jhTab('matrice')">
        <i class="fas fa-border-all"></i> Matrice
    </button>
    <button class="jh-tab <?= $tab === 'generate' ? 'active' : '' ?>" onclick="jhTab('generate')">
        <i class="fas fa-magic"></i> Generateur IA
    </button>
    <button class="jh-tab <?= $tab === 'performance' ? 'active' : '' ?>" onclick="jhTab('performance')">
        <i class="fas fa-chart-bar"></i> Performance
    </button>
</div>

<!-- ================================================================ -->
<!-- TAB 1 : VUE GLOBALE -->
<!-- ================================================================ -->
<div class="jh-panel <?= $tab === 'global' ? 'active' : '' ?>" id="jh-panel-global">

    <!-- Stats globales -->
    <div class="jh-global-stats">
        <div class="jh-gstat">
            <div class="jh-gstat-val"><?= (int)$statsGlobal['total'] ?></div>
            <div class="jh-gstat-label">Total idees</div>
        </div>
        <div class="jh-gstat">
            <div class="jh-gstat-val"><?= (int)$statsGlobal['ideas'] + (int)$statsGlobal['planned'] ?></div>
            <div class="jh-gstat-label">A traiter</div>
        </div>
        <div class="jh-gstat">
            <div class="jh-gstat-val"><?= (int)$statsGlobal['validated'] + (int)$statsGlobal['writing'] ?></div>
            <div class="jh-gstat-label">En cours</div>
        </div>
        <div class="jh-gstat">
            <div class="jh-gstat-val"><?= (int)$statsGlobal['ready'] ?></div>
            <div class="jh-gstat-label">Prets</div>
        </div>
        <div class="jh-gstat">
            <div class="jh-gstat-val" style="color:#27ae60;"><?= (int)$statsGlobal['published'] ?></div>
            <div class="jh-gstat-label">Publies</div>
        </div>
    </div>

    <!-- Cards par canal -->
    <div class="jh-channels">
        <?php foreach (JournalController::CHANNELS as $chId => $chInfo):
            $cs = $channelStatsMap[$chId] ?? ['total' => 0, 'ideas' => 0, 'validated' => 0, 'ready' => 0, 'published' => 0, 'writing' => 0];
            $total    = max((int)($cs['total'] ?? 0), 1);
            $pubPct   = round(((int)($cs['published'] ?? 0) / $total) * 100);
            $readyPct = round(((int)($cs['ready'] ?? 0) / $total) * 100);

            // Determiner l'URL du journal de ce canal
            $journalUrlMap = [
                'blog'      => '?page=articles-journal',
                'gmb'       => '?page=local-gmb-journal',
                'facebook'  => '?page=facebook-journal',
                'instagram' => '?page=instagram-journal',
                'tiktok'    => '?page=tiktok-journal',
                'linkedin'  => '?page=linkedin-journal',
                'email'     => '?page=emails-journal',
            ];
            $journalUrl = $journalUrlMap[$chId] ?? '?page=journal&tab=channel&channel=' . $chId;
        ?>
        <div class="jh-ch-card" onclick="window.location='<?= $journalUrl ?>'">
            <div class="jh-ch-card-accent" style="background:<?= $chInfo['color'] ?>"></div>
            <h4><i class="<?= $chInfo['icon'] ?>" style="color:<?= $chInfo['color'] ?>"></i> <?= $chInfo['label'] ?></h4>
            <div class="jh-ch-card-stats">
                <div class="jh-ch-mini">
                    <div class="jh-ch-mini-val"><?= (int)($cs['ideas'] ?? 0) + (int)($cs['planned'] ?? 0) ?></div>
                    <div class="jh-ch-mini-label">Idees</div>
                </div>
                <div class="jh-ch-mini">
                    <div class="jh-ch-mini-val"><?= (int)($cs['validated'] ?? 0) + (int)($cs['writing'] ?? 0) + (int)($cs['ready'] ?? 0) ?></div>
                    <div class="jh-ch-mini-label">En cours</div>
                </div>
                <div class="jh-ch-mini">
                    <div class="jh-ch-mini-val" style="color:#27ae60;"><?= (int)($cs['published'] ?? 0) ?></div>
                    <div class="jh-ch-mini-label">Publies</div>
                </div>
            </div>
            <div class="jh-ch-progress">
                <div class="jh-ch-progress-bar" style="width:<?= $pubPct + $readyPct ?>%;background:<?= $chInfo['color'] ?>;"></div>
            </div>
            <div class="jh-ch-card-footer">
                <span style="font-size:.75rem;color:var(--text-secondary,#6b7280);"><?= $pubPct ?>% publie</span>
                <a href="<?= $journalUrl ?>" class="jh-ch-link" onclick="event.stopPropagation()">
                    Voir <i class="fas fa-arrow-right" style="font-size:.7rem;"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dernieres idees -->
    <h3 class="jh-latest-title"><i class="fas fa-clock"></i> Dernieres idees (tous canaux)</h3>
    <?php if (empty($latestItems)): ?>
        <p style="color:var(--text-secondary,#6b7280);text-align:center;padding:20px;">
            Aucune idee. Utilisez le Generateur IA pour demarrer.
        </p>
    <?php else: ?>
        <table class="jh-latest-table">
            <thead>
                <tr>
                    <th>Sem.</th>
                    <th>Canal</th>
                    <th>Titre</th>
                    <th>Profil</th>
                    <th>Conscience</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($latestItems, 0, 15) as $item):
                    $profile   = JournalController::PROFILES[$item['profile_id']] ?? ['label' => '?', 'color' => '#999'];
                    $awareness = JournalController::AWARENESS[$item['awareness_level']] ?? ['short' => '?', 'color' => '#999'];
                    $status    = JournalController::STATUSES[$item['status']] ?? ['label' => '?', 'color' => '#999'];
                    $chInfo    = JournalController::CHANNELS[$item['channel_id']] ?? ['icon' => 'fas fa-file', 'color' => '#999', 'label' => '?'];
                ?>
                <tr>
                    <td style="font-weight:700;font-size:.8rem;">S<?= (int)$item['week_number'] ?></td>
                    <td><i class="<?= $chInfo['icon'] ?>" style="color:<?= $chInfo['color'] ?>" title="<?= $chInfo['label'] ?>"></i></td>
                    <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($item['title']) ?></td>
                    <td><span class="jh-badge" style="background:<?= $profile['color'] ?>"><?= $profile['label'] ?></span></td>
                    <td><span style="color:<?= $awareness['color'] ?>;font-weight:600;font-size:.8rem;"><?= $awareness['short'] ?></span></td>
                    <td><span class="jh-badge" style="background:<?= $status['color'] ?>"><?= $status['label'] ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ================================================================ -->
<!-- TAB 2 : MATRICE STRATEGIQUE -->
<!-- ================================================================ -->
<div class="jh-panel <?= $tab === 'matrice' ? 'active' : '' ?>" id="jh-panel-matrice">

    <p style="color:var(--text-secondary,#6b7280);margin-bottom:20px;font-size:.88rem;">
        <i class="fas fa-info-circle"></i>
        Identifiez les trous dans votre strategie : les cases vides ou rouges sont des opportunites manquees.
    </p>

    <div class="jh-matrix-wrap">
        <table class="jh-matrix">
            <thead>
                <tr>
                    <th style="min-width:140px;">Profil</th>
                    <?php foreach (JournalController::AWARENESS as $aKey => $aInfo): ?>
                        <th style="min-width:100px;">
                            <span style="color:<?= $aInfo['color'] ?>"><?= $aInfo['short'] ?></span>
                            <br><span style="font-size:.65rem;font-weight:400;">Niv. <?= $aInfo['step'] ?></span>
                        </th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (JournalController::PROFILES as $pKey => $pInfo):
                    $rowTotal = 0;
                ?>
                <tr>
                    <td class="jh-profile-th">
                        <span style="color:<?= $pInfo['color'] ?>">&#9679;</span>
                        <?= $pInfo['label'] ?>
                    </td>
                    <?php foreach (JournalController::AWARENESS as $aKey => $aInfo):
                        $cell = $matrixData[$pKey][$aKey] ?? ['cnt' => 0, 'published' => 0];
                        $cnt  = (int)$cell['cnt'];
                        $pub  = (int)$cell['published'];
                        $rowTotal += $cnt;

                        // Couleur cellule
                        if ($cnt === 0)     $cellClass = 'jh-matrix-empty';
                        elseif ($cnt <= 2)  $cellClass = 'jh-matrix-low';
                        elseif ($cnt <= 5)  $cellClass = 'jh-matrix-ok';
                        else                $cellClass = 'jh-matrix-good';
                    ?>
                    <td>
                        <div class="jh-matrix-cell">
                            <span class="jh-matrix-count <?= $cellClass ?>">
                                <?= $cnt === 0 ? '—' : $cnt ?>
                            </span>
                            <?php if ($pub > 0): ?>
                                <span class="jh-matrix-pub"><?= $pub ?> pub.</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endforeach; ?>
                    <td style="font-weight:800;"><?= $rowTotal ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;display:flex;gap:20px;font-size:.78rem;color:var(--text-secondary,#6b7280);flex-wrap:wrap;">
        <span><span style="color:#d1d5db;">&#9679;</span> — = trou strategique</span>
        <span><span style="color:#e67e22;">&#9679;</span> 1-2 = a renforcer</span>
        <span><span style="color:#3498db;">&#9679;</span> 3-5 = correct</span>
        <span><span style="color:#2ecc71;">&#9679;</span> 6+ = bien couvert</span>
    </div>
</div>

<!-- ================================================================ -->
<!-- TAB 3 : GENERATEUR IA -->
<!-- ================================================================ -->
<div class="jh-panel <?= $tab === 'generate' ? 'active' : '' ?>" id="jh-panel-generate">

    <div class="jh-gen">
        <h3><i class="fas fa-magic" style="color:#8b5cf6;"></i> Generateur d'idees IA</h3>
        <p style="color:var(--text-secondary,#6b7280);font-size:.88rem;margin-bottom:20px;">
            Generez automatiquement des idees de contenu basees sur vos personas,
            secteurs et niveaux de conscience. Les doublons sont automatiquement evites.
        </p>

        <div class="jh-gen-row">
            <label>Canal</label>
            <select id="jh-gen-channel">
                <option value="">Tous les canaux</option>
                <?php foreach (JournalController::CHANNELS as $chId => $chInfo): ?>
                    <option value="<?= $chId ?>"><i class="<?= $chInfo['icon'] ?>"></i> <?= $chInfo['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="jh-gen-row">
            <label>Nombre de semaines</label>
            <select id="jh-gen-weeks">
                <option value="2">2 semaines</option>
                <option value="4" selected>4 semaines</option>
                <option value="8">8 semaines</option>
                <option value="12">12 semaines (3 mois)</option>
            </select>
        </div>

        <button class="jh-gen-btn" id="jh-gen-btn" onclick="jhGenerate()">
            <i class="fas fa-magic"></i> Generer les idees
        </button>

        <div class="jh-gen-result" id="jh-gen-result"></div>
    </div>
</div>

<!-- ================================================================ -->
<!-- TAB 4 : PERFORMANCE -->
<!-- ================================================================ -->
<div class="jh-panel <?= $tab === 'performance' ? 'active' : '' ?>" id="jh-panel-performance">

    <?php
    $totalAll = max((int)$statsGlobal['total'], 1);
    $pctPub   = round(((int)$statsGlobal['published'] / $totalAll) * 100);
    $pctReady = round(((int)$statsGlobal['ready'] / $totalAll) * 100);
    $pctWork  = round((((int)$statsGlobal['validated'] + (int)$statsGlobal['writing']) / $totalAll) * 100);
    $pctIdea  = 100 - $pctPub - $pctReady - $pctWork;
    if ($pctIdea < 0) $pctIdea = 0;
    ?>

    <!-- Metriques cles -->
    <div class="jh-perf-grid">
        <div class="jh-perf-card">
            <div class="jh-perf-val"><?= (int)$statsGlobal['total'] ?></div>
            <div class="jh-perf-label">Total idees</div>
        </div>
        <div class="jh-perf-card">
            <div class="jh-perf-val" style="color:#27ae60;"><?= (int)$statsGlobal['published'] ?></div>
            <div class="jh-perf-label">Contenus publies</div>
        </div>
        <div class="jh-perf-card">
            <div class="jh-perf-val" style="color:#3498db;"><?= $pctPub ?>%</div>
            <div class="jh-perf-label">Taux de publication</div>
        </div>
        <div class="jh-perf-card">
            <div class="jh-perf-val" style="color:#e67e22;"><?= (int)$statsGlobal['ready'] ?></div>
            <div class="jh-perf-label">Prets a publier</div>
        </div>
    </div>

    <!-- Pipeline visuel -->
    <h3 class="jh-latest-title"><i class="fas fa-funnel-dollar"></i> Pipeline editorial</h3>
    <div class="jh-perf-pipeline">
        <?php if ($pctIdea > 0): ?>
        <div class="jh-perf-pipeline-seg" style="width:<?= $pctIdea ?>%;background:#95a5a6;">
            <?= $pctIdea > 8 ? $pctIdea . '% Idees' : '' ?>
        </div>
        <?php endif; ?>
        <?php if ($pctWork > 0): ?>
        <div class="jh-perf-pipeline-seg" style="width:<?= $pctWork ?>%;background:#9b59b6;">
            <?= $pctWork > 8 ? $pctWork . '% En cours' : '' ?>
        </div>
        <?php endif; ?>
        <?php if ($pctReady > 0): ?>
        <div class="jh-perf-pipeline-seg" style="width:<?= $pctReady ?>%;background:#3498db;">
            <?= $pctReady > 8 ? $pctReady . '% Prets' : '' ?>
        </div>
        <?php endif; ?>
        <?php if ($pctPub > 0): ?>
        <div class="jh-perf-pipeline-seg" style="width:<?= $pctPub ?>%;background:#2ecc71;">
            <?= $pctPub > 8 ? $pctPub . '% Publies' : '' ?>
        </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:8px;display:flex;gap:16px;font-size:.75rem;color:var(--text-secondary,#6b7280);flex-wrap:wrap;">
        <span><span style="color:#95a5a6;">&#9679;</span> Idees</span>
        <span><span style="color:#9b59b6;">&#9679;</span> En cours</span>
        <span><span style="color:#3498db;">&#9679;</span> Prets</span>
        <span><span style="color:#2ecc71;">&#9679;</span> Publies</span>
    </div>

    <!-- Tableau par canal -->
    <h3 class="jh-latest-title" style="margin-top:28px;"><i class="fas fa-chart-line"></i> Detail par canal</h3>
    <table class="jh-latest-table">
        <thead>
            <tr>
                <th>Canal</th>
                <th>Idees</th>
                <th>En cours</th>
                <th>Prets</th>
                <th>Publies</th>
                <th>Total</th>
                <th>% Publie</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (JournalController::CHANNELS as $chId => $chInfo):
                $cs = $channelStatsMap[$chId] ?? [];
                $chTotal = max((int)($cs['total'] ?? 0), 1);
                $chPub   = (int)($cs['published'] ?? 0);
                $chPct   = round(($chPub / $chTotal) * 100);
            ?>
            <tr>
                <td><i class="<?= $chInfo['icon'] ?>" style="color:<?= $chInfo['color'] ?>;margin-right:6px;"></i> <?= $chInfo['label'] ?></td>
                <td><?= (int)($cs['ideas'] ?? 0) + (int)($cs['planned'] ?? 0) ?></td>
                <td><?= (int)($cs['validated'] ?? 0) + (int)($cs['writing'] ?? 0) ?></td>
                <td><?= (int)($cs['ready'] ?? 0) ?></td>
                <td style="font-weight:700;color:#27ae60;"><?= $chPub ?></td>
                <td><?= (int)($cs['total'] ?? 0) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
                            <div style="height:100%;width:<?= $chPct ?>%;background:<?= $chInfo['color'] ?>;border-radius:3px;"></div>
                        </div>
                        <span style="font-size:.78rem;font-weight:600;"><?= $chPct ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ================================================================ -->
<!-- JAVASCRIPT HUB -->
<!-- ================================================================ -->
<script>
const JH_API = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/modules/journal/api/journal.php';
const JH_CSRF = '<?= $csrfToken ?>';

// Onglets
function jhTab(tab) {
    document.querySelectorAll('.jh-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.jh-panel').forEach(p => p.classList.remove('active'));

    document.querySelector('.jh-tab[onclick*="' + tab + '"]').classList.add('active');
    document.getElementById('jh-panel-' + tab).classList.add('active');

    // Mettre a jour URL sans recharger
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    history.replaceState(null, '', url);
}

// Generateur
async function jhGenerate() {
    const btn    = document.getElementById('jh-gen-btn');
    const result = document.getElementById('jh-gen-result');
    const ch     = document.getElementById('jh-gen-channel').value;
    const weeks  = parseInt(document.getElementById('jh-gen-weeks').value);

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generation en cours...';
    result.style.display = 'none';

    try {
        const res = await fetch(JH_API + '?action=generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': JH_CSRF,
            },
            body: JSON.stringify({
                channel_id: ch || null,
                weeks: weeks,
            }),
        });
        const data = await res.json();

        if (data.success) {
            result.className = 'jh-gen-result success';
            result.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
            result.style.display = 'block';
            setTimeout(() => location.reload(), 1500);
        } else {
            result.className = 'jh-gen-result error';
            result.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.error || 'Erreur');
            result.style.display = 'block';
        }
    } catch (e) {
        result.className = 'jh-gen-result error';
        result.innerHTML = '<i class="fas fa-exclamation-circle"></i> Erreur reseau';
        result.style.display = 'block';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic"></i> Generer les idees';
}
</script>