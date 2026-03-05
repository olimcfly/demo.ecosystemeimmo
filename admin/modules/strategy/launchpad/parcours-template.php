<?php
/**
 * PARCOURS — TEMPLATE COMMUN
 * /admin/modules/launchpad/parcours-template.php
 * 
 * Inclus par parcours-conversion.php, parcours-organisation.php, parcours-scale.php
 * Attend les variables : $parcours_id, $parcours_name, $parcours_emoji, $parcours_color, 
 *                        $parcours_gradient, $etapes, $completed_steps, $current_step,
 *                        $total_actions, $done_actions, $progress_pct
 */

// Définir le slug pour les liens (ex: parcours-conversion, parcours-organisation, parcours-scale)
$parcours_slugs = [
    'B' => 'parcours-acheteurs',
    'C' => 'parcours-conversion',
    'D' => 'parcours-organisation',
    'E' => 'parcours-scale'
];
$current_slug = $parcours_slugs[$parcours_id] ?? 'parcours-' . strtolower($parcours_id);

// Quick wins par parcours
$quick_wins_map = [
    'C' => [
        'Réécrire votre titre Hero avec le framework MERE (Étape 2)',
        'Créer votre landing page "Estimation Gratuite" (Étape 3)',
        'Ajouter 3 témoignages clients sur vos pages clés (Étape 5)'
    ],
    'D' => [
        'Configurer votre pipeline CRM avec les bonnes étapes (Étape 1)',
        'Activer l\'email automatique de bienvenue (Étape 2)',
        'Créer vos 3 templates de messages types (Étape 3)'
    ],
    'E' => [
        'Publier votre première page quartier optimisée SEO (Étape 1)',
        'Tourner et publier 3 vidéos courtes cette semaine (Étape 2)',
        'Contacter 3 prescripteurs potentiels (Étape 4)'
    ]
];
$quick_wins = $quick_wins_map[$parcours_id] ?? [];
?>

<style>
/* ══════════════════════════════════════════════
   PARCOURS — TEMPLATE COMMUN
   ══════════════════════════════════════════════ */

.parcours-container {
    max-width: 900px;
    margin: 0 auto;
}

.parcours-hero {
    background: <?= $parcours_gradient ?>;
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.parcours-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
}

.parcours-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.parcours-hero-content {
    position: relative;
    z-index: 1;
}

.parcours-hero h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.parcours-hero .subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 24px;
}

.progress-bar-container {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    height: 12px;
    overflow: hidden;
    margin-bottom: 12px;
}

.progress-bar-fill {
    height: 100%;
    background: white;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    opacity: 0.9;
}

/* Navigation étapes */
.etapes-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.etape-nav-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
}

.etape-nav-btn:hover {
    border-color: <?= $parcours_color ?>;
    color: <?= $parcours_color ?>;
    transform: translateY(-1px);
}

.etape-nav-btn.active {
    border-color: <?= $parcours_color ?>;
    background: <?= $parcours_color ?>15;
    color: <?= $parcours_color ?>;
}

.etape-nav-btn.completed {
    border-color: <?= $parcours_color ?>;
    background: <?= $parcours_color ?>;
    color: white;
}

.etape-nav-emoji { font-size: 16px; }

.etape-nav-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

/* Vue d'ensemble */
.overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 30px;
}

.overview-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}

.overview-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: <?= $parcours_color ?>;
}

.overview-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

.overview-card-emoji {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.overview-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}

.overview-card-duration {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

.overview-card-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 14px;
}

.overview-card-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.overview-card-progress-bar {
    flex: 1;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.overview-card-progress-fill {
    height: 100%;
    background: <?= $parcours_color ?>;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.overview-card-progress-text {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

/* Détail étape */
.etape-detail {
    background: white;
    border-radius: 16px;
    padding: 30px;
    border: 1px solid #e2e8f0;
}

.etape-detail-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 10px;
}

.etape-detail-emoji {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    flex-shrink: 0;
}

.etape-detail-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.etape-detail-header .etape-subtitle {
    font-size: 14px;
    color: #64748b;
    margin-top: 4px;
}

.etape-detail-desc {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0 25px;
    font-size: 14px;
    color: #475569;
    line-height: 1.7;
    border-left: 4px solid <?= $parcours_color ?>;
}

/* Actions */
.action-card {
    background: #fafbfc;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.action-card:hover {
    border-color: <?= $parcours_color ?>;
    box-shadow: 0 2px 8px <?= $parcours_color ?>15;
}

.action-card.done {
    background: #f0fdf4;
    border-color: #86efac;
}

.action-card.done .action-title {
    text-decoration: line-through;
    color: #6b7280;
}

.action-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.action-check {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
    font-size: 14px;
    color: transparent;
}

.action-check:hover {
    border-color: <?= $parcours_color ?>;
    background: <?= $parcours_color ?>10;
}

.action-check.checked {
    background: <?= $parcours_color ?>;
    border-color: <?= $parcours_color ?>;
    color: white;
}

.action-content { flex: 1; }

.action-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}

.action-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

.action-type-badge.reflexion { background: #fef3c7; color: #92400e; }
.action-type-badge.action { background: #dbeafe; color: #1e40af; }
.action-type-badge.validation { background: #d1fae5; color: #065f46; }

.action-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 10px;
}

.action-tips {
    background: white;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 12px;
    color: #6b7280;
    border: 1px dashed #e2e8f0;
    line-height: 1.5;
}

.action-tips::before { content: '💡 '; }

.action-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 6px 14px;
    background: <?= $parcours_gradient ?>;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px <?= $parcours_color ?>40;
}

/* Footer navigation */
.etape-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.etape-footer .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.btn-back {
    background: white;
    border: 1px solid #e2e8f0 !important;
    color: #64748b;
}

.btn-back:hover { background: #f8fafc; }

.btn-next {
    background: <?= $parcours_gradient ?>;
    color: white;
}

.btn-next:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px <?= $parcours_color ?>40;
}

/* Quick wins */
.quick-wins-recap {
    background: linear-gradient(135deg, #fefce8, #fef9c3);
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 20px;
    margin-top: 30px;
}

.quick-wins-recap h4 {
    font-size: 14px;
    font-weight: 700;
    color: #92400e;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-wins-recap ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.quick-wins-recap li {
    padding: 6px 0;
    font-size: 13px;
    color: #78716c;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-wins-recap li::before { content: '⚡'; }

/* Responsive */
@media (max-width: 768px) {
    .parcours-hero { padding: 24px; }
    .parcours-hero h1 { font-size: 22px; }
    .overview-grid { grid-template-columns: 1fr; }
    .etapes-nav { flex-direction: column; }
    .etape-footer { flex-direction: column; gap: 10px; }
}
</style>

<div class="parcours-container">

    <!-- HEADER HERO -->
    <div class="parcours-hero">
        <div class="parcours-hero-content">
            <h1><?= $parcours_emoji ?> Parcours <?= $parcours_id ?> — <?= $parcours_name ?></h1>
            <p class="subtitle"><?= $etapes[1]['subtitle'] ?? 'Suivez les étapes pour atteindre votre objectif' ?></p>
            
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?= $progress_pct ?>%"></div>
            </div>
            <div class="progress-stats">
                <span><?= $done_actions ?> / <?= $total_actions ?> actions complétées</span>
                <span><?= $progress_pct ?>%</span>
            </div>
        </div>
    </div>

    <!-- NAVIGATION DES ÉTAPES -->
    <div class="etapes-nav">
        <a href="?page=<?= $current_slug ?>" 
           class="etape-nav-btn <?= $current_step === 0 ? 'active' : '' ?>">
            <span class="etape-nav-emoji">📊</span> Vue d'ensemble
        </a>
        <?php foreach ($etapes as $num => $etape): 
            $etape_actions = array_column($etape['actions'], 'id');
            $etape_done = count(array_intersect($etape_actions, $completed_steps));
            $etape_total = count($etape_actions);
            $all_done = ($etape_done === $etape_total && $etape_total > 0);
            $is_active = ($current_step === $num);
        ?>
        <a href="?page=<?= $current_slug ?>&etape=<?= $num ?>" 
           class="etape-nav-btn <?= $is_active ? 'active' : '' ?> <?= $all_done ? 'completed' : '' ?>">
            <span class="etape-nav-emoji"><?= $etape['emoji'] ?></span>
            Étape <?= $num ?>
            <?php if ($all_done): ?>
            <span class="etape-nav-check">✓</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($current_step === 0): ?>
    <!-- VUE D'ENSEMBLE -->
    <div class="overview-grid">
        <?php foreach ($etapes as $num => $etape): 
            $etape_actions = array_column($etape['actions'], 'id');
            $etape_done = count(array_intersect($etape_actions, $completed_steps));
            $etape_total = count($etape_actions);
            $etape_pct = $etape_total > 0 ? round(($etape_done / $etape_total) * 100) : 0;
        ?>
        <a href="?page=<?= $current_slug ?>&etape=<?= $num ?>" class="overview-card">
            <div class="overview-card-header">
                <div class="overview-card-emoji" style="background: <?= $etape['gradient'] ?>">
                    <?= $etape['emoji'] ?>
                </div>
                <div>
                    <div class="overview-card-title">Étape <?= $num ?> — <?= $etape['title'] ?></div>
                    <div class="overview-card-duration">⏱ <?= $etape['duration'] ?> • <?= $etape_total ?> actions</div>
                </div>
            </div>
            <div class="overview-card-desc"><?= $etape['subtitle'] ?></div>
            <div class="overview-card-progress">
                <div class="overview-card-progress-bar">
                    <div class="overview-card-progress-fill" style="width: <?= $etape_pct ?>%"></div>
                </div>
                <span class="overview-card-progress-text"><?= $etape_done ?>/<?= $etape_total ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($quick_wins)): ?>
    <div class="quick-wins-recap">
        <h4>⚡ Quick Wins — À faire en premier</h4>
        <ul>
            <?php foreach ($quick_wins as $qw): ?>
            <li><?= $qw ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- DÉTAIL D'UNE ÉTAPE -->
    <?php $etape = $etapes[$current_step] ?? null; ?>
    <?php if ($etape): ?>
    <div class="etape-detail">
        <div class="etape-detail-header">
            <div class="etape-detail-emoji" style="background: <?= $etape['gradient'] ?>">
                <?= $etape['emoji'] ?>
            </div>
            <div>
                <h2>Étape <?= $current_step ?> — <?= $etape['title'] ?></h2>
                <div class="etape-subtitle"><?= $etape['subtitle'] ?> • ⏱ <?= $etape['duration'] ?></div>
            </div>
        </div>

        <div class="etape-detail-desc">
            <?= $etape['description'] ?>
        </div>

        <?php foreach ($etape['actions'] as $action): 
            $is_done = in_array($action['id'], $completed_steps);
        ?>
        <div class="action-card <?= $is_done ? 'done' : '' ?>" data-action-id="<?= $action['id'] ?>">
            <div class="action-header">
                <div class="action-check <?= $is_done ? 'checked' : '' ?>" 
                     onclick="toggleAction('<?= $action['id'] ?>', <?= $current_step ?>)">
                    ✓
                </div>
                <div class="action-content">
                    <div class="action-title"><?= $action['title'] ?></div>
                    <span class="action-type-badge <?= $action['type'] ?>">
                        <?php 
                        $type_labels = ['reflexion' => '🧠 Réflexion', 'action' => '🔧 Action', 'validation' => '✅ Validation'];
                        echo $type_labels[$action['type']] ?? $action['type'];
                        ?>
                    </span>
                    <div class="action-desc"><?= $action['description'] ?></div>
                    
                    <?php if (!empty($action['tips'])): ?>
                    <div class="action-tips"><?= $action['tips'] ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($action['module_link'])): ?>
                    <a href="<?= $action['module_link'] ?>" class="action-link">
                        <i class="fas fa-external-link-alt"></i> Ouvrir le module
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Navigation bas -->
        <div class="etape-footer">
            <?php if ($current_step > 1): ?>
            <a href="?page=<?= $current_slug ?>&etape=<?= $current_step - 1 ?>" class="btn btn-back">
                ← Étape <?= $current_step - 1 ?>
            </a>
            <?php else: ?>
            <a href="?page=<?= $current_slug ?>" class="btn btn-back">
                ← Vue d'ensemble
            </a>
            <?php endif; ?>

            <?php if ($current_step < 5): ?>
            <a href="?page=<?= $current_slug ?>&etape=<?= $current_step + 1 ?>" class="btn btn-next">
                Étape <?= $current_step + 1 ?> →
            </a>
            <?php else: ?>
            <a href="?page=<?= $current_slug ?>" class="btn btn-next">
                ✓ Voir le récap
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
/**
 * Cocher/décocher une action — commun à tous les parcours
 */
async function toggleAction(actionId, stepNum) {
    const card = document.querySelector(`[data-action-id="${actionId}"]`);
    const check = card.querySelector('.action-check');
    const isDone = check.classList.contains('checked');
    
    // Toggle visuel immédiat
    if (isDone) {
        check.classList.remove('checked');
        card.classList.remove('done');
    } else {
        check.classList.add('checked');
        card.classList.add('done');
    }
    
    // Sauvegarder en DB
    try {
        const currentPage = new URLSearchParams(window.location.search).get('page');
        const response = await fetch(`?page=${currentPage}&ajax=1`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                step: stepNum,
                action_id: actionId,
                status: isDone ? 'undone' : 'done'
            })
        });
        
        const result = await response.json();
        if (!result.success) {
            console.error('Erreur sauvegarde:', result.error);
            // Rollback
            if (isDone) {
                check.classList.add('checked');
                card.classList.add('done');
            } else {
                check.classList.remove('checked');
                card.classList.remove('done');
            }
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
}
</script>