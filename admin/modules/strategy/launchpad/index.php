<?php
/**
 * Module Launchpad - GPS Stratégique v2.0
 * Diagnostic intelligent + Parcours personnalisé
 * /admin/modules/launchpad/index.php
 *
 * CHANGELOG v2.0:
 * - "Route" → "Parcours" partout
 * - Sauvegarde diagnostic en DB (table auto-créée)
 * - Résultats persistants entre sessions
 * - API AJAX pour sauvegarder sans rechargement
 * - Vue résumé sur l'overview si diagnostic déjà fait
 * - Barres de scores visuelles sur les résultats
 */

// ══════════════════════════════════════════════
// CONNEXION DB
// ══════════════════════════════════════════════
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo '<div style="padding:20px;color:#dc2626;background:#fef2f2;border-radius:12px;margin:20px;">Erreur de connexion à la base de données</div>';
    return;
}

// ══════════════════════════════════════════════
// AUTO-CRÉATION TABLE DIAGNOSTIC
// ══════════════════════════════════════════════
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `launchpad_diagnostic` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `answers` JSON DEFAULT NULL COMMENT 'Réponses {q1: idx, q2: idx, ...}',
        `scores` JSON DEFAULT NULL COMMENT 'Scores {A:x, B:x, C:x, D:x, E:x}',
        `parcours_principal` CHAR(1) DEFAULT NULL,
        `parcours_secondaire1` CHAR(1) DEFAULT NULL,
        `parcours_secondaire2` CHAR(1) DEFAULT NULL,
        `score_max` INT DEFAULT 0,
        `completed_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_user` (`user_id`),
        INDEX `idx_completed` (`user_id`, `completed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) { /* existe déjà */ }

$user_id = $_SESSION['admin_id'] ?? 1;
$action  = $_GET['action'] ?? 'overview';

// ══════════════════════════════════════════════
// API AJAX : Sauvegarder le diagnostic
// ══════════════════════════════════════════════
if ($action === 'save-diagnostic' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    $answers = $input['answers'] ?? [];
    $scores  = $input['scores'] ?? [];
    
    if (empty($scores)) {
        echo json_encode(['success' => false, 'error' => 'Scores manquants']);
        exit;
    }
    
    arsort($scores);
    $sorted = array_keys($scores);
    $pp  = $sorted[0] ?? 'A';
    $ps1 = $sorted[1] ?? null;
    $ps2 = $sorted[2] ?? null;
    $sm  = $scores[$pp] ?? 0;
    
    try {
        $pdo->prepare("DELETE FROM launchpad_diagnostic WHERE user_id = ?")->execute([$user_id]);
        
        $stmt = $pdo->prepare("INSERT INTO launchpad_diagnostic 
            (user_id, answers, scores, parcours_principal, parcours_secondaire1, parcours_secondaire2, score_max, completed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, json_encode($answers), json_encode($scores), $pp, $ps1, $ps2, $sm]);
        
        echo json_encode(['success' => true, 'parcours' => $pp]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ══════════════════════════════════════════════
// Récupérer diagnostic existant
// ══════════════════════════════════════════════
$diagnostic = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM launchpad_diagnostic WHERE user_id = ? AND completed_at IS NOT NULL ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $diagnostic = $stmt->fetch();
} catch (Exception $e) { }

// ══════════════════════════════════════════════
// LES 5 PARCOURS
// ══════════════════════════════════════════════
$parcours = [
    'A' => [
        'id' => 'A', 'name' => 'Conquête Vendeurs', 'emoji' => '🏠',
        'color' => '#ef4444', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)',
        'description' => 'Attirer des propriétaires qui veulent vendre',
        'modules' => ['Persona vendeurs', 'Landing estimation', 'Google Business Profile', 'Scripts appels/SMS', 'Séquences email', 'Retargeting'],
        'quick_wins' => ['Optimiser votre fiche Google My Business', 'Créer votre page Estimation en ligne', 'Planifier 10 demandes d\'avis clients'],
        'duration' => '7 jours'
    ],
    'B' => [
        'id' => 'B', 'name' => 'Acheteurs Solvables', 'emoji' => '💰',
        'color' => '#10b981', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'description' => 'Capter des acheteurs qualifiés avec budget',
        'modules' => ['Capture projet+budget', 'Partenariat courtier', 'Pages secteurs/quartiers', 'Séquences nurturing', 'Alertes biens'],
        'quick_wins' => ['Créer un formulaire projet acheteur', 'Mettre en place le workflow courtier', 'Configurer l\'email de bienvenue auto'],
        'duration' => '5 jours'
    ],
    'C' => [
        'id' => 'C', 'name' => 'Conversion & Copy', 'emoji' => '🎯',
        'color' => '#f59e0b', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'description' => 'Transformer vos visiteurs en leads',
        'modules' => ['Audit landing + copy MERE', 'CTA + formulaires', 'Tracking conversions', 'Preuves sociales', 'Offre packagée'],
        'quick_wins' => ['Refaire 1 landing page avec copy MERE', 'Ajouter un CTA clair sur chaque page', 'Intégrer 3 témoignages clients'],
        'duration' => '4 jours'
    ],
    'D' => [
        'id' => 'D', 'name' => 'Organisation & Système', 'emoji' => '⚙️',
        'color' => '#6366f1', 'gradient' => 'linear-gradient(135deg, #6366f1, #4f46e5)',
        'description' => 'Structurer et automatiser votre activité',
        'modules' => ['CRM pipeline', 'Tâches & relances', 'Automations', 'Dashboard KPI', 'Templates messages'],
        'quick_wins' => ['Configurer votre pipeline CRM', 'Créer 3 automatisations de relance', 'Mettre en place un tableau de bord KPI'],
        'duration' => '3 jours'
    ],
    'E' => [
        'id' => 'E', 'name' => 'Scale & Domination', 'emoji' => '🚀',
        'color' => '#8b5cf6', 'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'description' => 'Devenir #1 sur votre zone',
        'modules' => ['SEO territorial', 'Pages quartiers', 'Stratégie contenu', 'Ads Meta/Google', 'Retargeting', 'Partenariats'],
        'quick_wins' => ['Rédiger un plan contenu 30 jours', 'Créer 3 pages quartiers SEO', 'Installer le pixel de retargeting'],
        'duration' => '14 jours'
    ]
];

// ══════════════════════════════════════════════
// LES 10 QUESTIONS
// ══════════════════════════════════════════════
$questions = [
    1 => ['question' => 'Combien de mandats signez-vous par mois en moyenne ?', 'answers' => [
        ['text' => 'Moins de 2', 'scores' => ['A' => 4, 'D' => 1]],
        ['text' => '2 à 5', 'scores' => ['A' => 2, 'C' => 1]],
        ['text' => '5 à 10', 'scores' => ['E' => 2, 'B' => 1]],
        ['text' => 'Plus de 10', 'scores' => ['E' => 4]]
    ]],
    2 => ['question' => 'D\'où viennent principalement vos contacts ?', 'answers' => [
        ['text' => 'Bouche à oreille uniquement', 'scores' => ['A' => 2, 'E' => 2]],
        ['text' => 'Portails (SeLoger, LeBonCoin...)', 'scores' => ['B' => 2, 'C' => 1]],
        ['text' => 'Mon site web / réseaux', 'scores' => ['C' => 2, 'E' => 1]],
        ['text' => 'Un peu de tout, sans stratégie claire', 'scores' => ['D' => 3, 'A' => 1]]
    ]],
    3 => ['question' => 'Votre plus gros problème aujourd\'hui ?', 'answers' => [
        ['text' => 'Pas assez de vendeurs', 'scores' => ['A' => 5]],
        ['text' => 'Des acheteurs mais pas solvables', 'scores' => ['B' => 4, 'C' => 1]],
        ['text' => 'Du trafic mais peu de conversions', 'scores' => ['C' => 5]],
        ['text' => 'Débordé, pas organisé', 'scores' => ['D' => 5]],
        ['text' => 'Plafonné, je veux scaler', 'scores' => ['E' => 5]]
    ]],
    4 => ['question' => 'Avez-vous un CRM en place ?', 'answers' => [
        ['text' => 'Non, je gère au feeling', 'scores' => ['D' => 4]],
        ['text' => 'Oui mais je ne l\'utilise pas bien', 'scores' => ['D' => 3, 'C' => 1]],
        ['text' => 'Oui et il est bien configuré', 'scores' => ['E' => 2, 'A' => 1]],
        ['text' => 'Oui avec automations', 'scores' => ['E' => 3]]
    ]],
    5 => ['question' => 'Votre présence sur Google Business Profile ?', 'answers' => [
        ['text' => 'Pas de fiche ou inactive', 'scores' => ['A' => 3, 'E' => 1]],
        ['text' => 'Fiche basique, peu d\'avis', 'scores' => ['A' => 2, 'C' => 1]],
        ['text' => 'Active avec +20 avis', 'scores' => ['E' => 2]],
        ['text' => 'Top 3 local, +50 avis', 'scores' => ['E' => 3, 'B' => 1]]
    ]],
    6 => ['question' => 'Avez-vous une page d\'estimation en ligne ?', 'answers' => [
        ['text' => 'Non', 'scores' => ['A' => 4]],
        ['text' => 'Oui mais elle convertit mal', 'scores' => ['C' => 4, 'A' => 1]],
        ['text' => 'Oui et elle génère des leads', 'scores' => ['E' => 2, 'B' => 1]]
    ]],
    7 => ['question' => 'Travaillez-vous avec un courtier partenaire ?', 'answers' => [
        ['text' => 'Non', 'scores' => ['B' => 3]],
        ['text' => 'Oui mais pas de process', 'scores' => ['B' => 2, 'D' => 2]],
        ['text' => 'Oui avec workflow automatisé', 'scores' => ['E' => 2]]
    ]],
    8 => ['question' => 'Combien de temps consacrez-vous au marketing/prospection ?', 'answers' => [
        ['text' => 'Quasi rien, pas le temps', 'scores' => ['D' => 4, 'A' => 1]],
        ['text' => '1-2h par semaine', 'scores' => ['A' => 2, 'C' => 1]],
        ['text' => '5h+ par semaine', 'scores' => ['E' => 2, 'C' => 1]],
        ['text' => 'J\'ai une équipe/assistant', 'scores' => ['E' => 4]]
    ]],
    9 => ['question' => 'Faites-vous de la publicité payante ?', 'answers' => [
        ['text' => 'Non, jamais', 'scores' => ['A' => 1, 'C' => 1]],
        ['text' => 'Testé mais arrêté', 'scores' => ['C' => 3, 'E' => 1]],
        ['text' => 'Oui, budget modeste', 'scores' => ['E' => 2, 'C' => 1]],
        ['text' => 'Oui, budget conséquent', 'scores' => ['E' => 4]]
    ]],
    10 => ['question' => 'Votre objectif principal pour les 90 prochains jours ?', 'answers' => [
        ['text' => 'Rentrer plus de mandats', 'scores' => ['A' => 5]],
        ['text' => 'Qualifier mes acheteurs', 'scores' => ['B' => 5]],
        ['text' => 'Améliorer mes conversions', 'scores' => ['C' => 5]],
        ['text' => 'M\'organiser enfin', 'scores' => ['D' => 5]],
        ['text' => 'Dominer ma zone', 'scores' => ['E' => 5]]
    ]]
];
?>

<style>
.launchpad-module { max-width: 1200px; margin: 0 auto; }
.launchpad-hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 20px; padding: 40px; margin-bottom: 30px; color: white; position: relative; overflow: hidden; }
.launchpad-hero::before { content: ''; position: absolute; top: -50%; right: -20%; width: 400px; height: 400px; background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%); border-radius: 50%; }
.launchpad-hero-content { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; }
.launchpad-hero h1 { font-size: 32px; font-weight: 800; margin-bottom: 12px; }
.launchpad-hero p { font-size: 16px; opacity: 0.9; max-width: 500px; }
.hero-cta { display: inline-flex; align-items: center; gap: 10px; padding: 16px 32px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 16px; transition: all 0.3s; border: none; cursor: pointer; }
.hero-cta:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(59,130,246,0.4); }
.status-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
.status-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
.status-card.active { border-color: #3b82f6; box-shadow: 0 4px 20px rgba(59,130,246,0.15); }
.status-card.done { border-color: #10b981; }
.status-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
.status-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; }
.status-card-title { font-size: 14px; font-weight: 700; color: #1e293b; }
.status-card-subtitle { font-size: 12px; color: #64748b; }
.status-card-content { font-size: 13px; color: #475569; line-height: 1.6; }
.status-badge { position: absolute; top: 16px; right: 16px; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.done { background: #dcfce7; color: #166534; }
.diagnostic-container { background: white; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; margin-bottom: 30px; }
.diagnostic-progress { display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; }
.progress-dot { width: 12px; height: 12px; border-radius: 50%; background: #e2e8f0; transition: all 0.3s; }
.progress-dot.active { background: #3b82f6; transform: scale(1.3); }
.progress-dot.done { background: #10b981; }
.question-container { text-align: center; max-width: 600px; margin: 0 auto; }
.question-number { font-size: 14px; color: #6366f1; font-weight: 600; margin-bottom: 12px; }
.question-text { font-size: 24px; font-weight: 700; color: #1e293b; margin-bottom: 32px; line-height: 1.4; }
.answers-grid { display: flex; flex-direction: column; gap: 12px; }
.answer-btn { padding: 18px 24px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 15px; font-weight: 500; color: #1e293b; cursor: pointer; transition: all 0.2s; text-align: left; }
.answer-btn:hover { border-color: #3b82f6; background: #eff6ff; transform: translateX(5px); }
.answer-btn.selected { border-color: #3b82f6; background: #3b82f6; color: white; }
.results-container { background: white; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; }
.results-header { padding: 40px; text-align: center; color: white; }
.results-header h2 { font-size: 28px; font-weight: 800; margin-bottom: 8px; }
.results-header p { font-size: 16px; opacity: 0.9; }
.results-score { display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 20px; }
.score-circle { width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; }
.results-body { padding: 40px; }
.parcours-card { background: #f8fafc; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
.parcours-card-header { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; }
.parcours-emoji { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: white; }
.parcours-info h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.parcours-info p { font-size: 13px; color: #64748b; }
.parcours-modules { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.parcours-module-tag { padding: 6px 12px; background: white; border-radius: 20px; font-size: 12px; font-weight: 500; color: #475569; border: 1px solid #e2e8f0; }
.quick-wins-section { background: white; border-radius: 12px; padding: 20px; }
.quick-wins-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.quick-win-item { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
.quick-win-item:last-child { border: none; }
.quick-win-check { width: 24px; height: 24px; border-radius: 6px; background: #dcfce7; color: #166534; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.quick-win-text { font-size: 14px; color: #374151; }
.parcours-overview { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 30px; }
.parcours-mini-card { background: white; border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px; text-align: center; transition: all 0.3s; cursor: pointer; }
.parcours-mini-card:hover { transform: translateY(-4px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
.parcours-mini-card.recommended { border-color: #3b82f6; box-shadow: 0 4px 20px rgba(59,130,246,0.2); }
.parcours-mini-emoji { font-size: 36px; margin-bottom: 12px; }
.parcours-mini-name { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.parcours-mini-desc { font-size: 11px; color: #64748b; }
.recommended-badge { display: inline-block; padding: 4px 10px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; border-radius: 20px; font-size: 10px; font-weight: 600; margin-top: 10px; }
.diagnostic-summary { background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 2px solid #10b981; border-radius: 16px; padding: 24px; margin-bottom: 30px; }
.diagnostic-summary-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
.diagnostic-summary h3 { font-size: 18px; font-weight: 700; color: #065f46; }
.diagnostic-summary .parcours-tag { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 10px; color: white; font-weight: 700; font-size: 14px; }
.diagnostic-summary-qw { display: flex; gap: 12px; flex-wrap: wrap; }
.diagnostic-summary-qw span { padding: 6px 14px; background: white; border-radius: 20px; font-size: 12px; font-weight: 500; color: #065f46; border: 1px solid #a7f3d0; }
.start-plan-btn { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 20px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 14px; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s; text-decoration: none; }
.start-plan-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(16,185,129,0.4); }
.diagnostic-nav { display: flex; justify-content: space-between; margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; }
.nav-btn { display: flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
.nav-btn-back { background: #f1f5f9; color: #475569; }
.nav-btn-back:hover { background: #e2e8f0; }
.nav-btn-next { background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: white; }
.nav-btn-next:hover { transform: translateX(3px); }
.nav-btn-next:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.saving-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.85); z-index: 9999; align-items: center; justify-content: center; }
.saving-overlay.active { display: flex; }
.saving-spinner { width: 48px; height: 48px; border: 4px solid #e2e8f0; border-top-color: #3b82f6; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (max-width: 1024px) { .parcours-overview { grid-template-columns: repeat(3, 1fr); } .status-cards { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .parcours-overview { grid-template-columns: repeat(2, 1fr); } .launchpad-hero-content { flex-direction: column; text-align: center; gap: 24px; } .diagnostic-summary-header { flex-direction: column; } }
</style>

<!-- Loading overlay -->
<div class="saving-overlay" id="savingOverlay">
    <div style="text-align:center;">
        <div class="saving-spinner"></div>
        <p style="margin-top:16px;font-weight:600;color:#3b82f6;">Analyse en cours...</p>
    </div>
</div>

<div class="launchpad-module">

<?php
// ══════════════════════════════════════════════
// VUE 1 : DIAGNOSTIC (Questions)
// ══════════════════════════════════════════════
if ($action === 'diagnostic' || isset($_GET['q'])): 
    $current_q = max(1, min(10, intval($_GET['q'] ?? 1)));
    $q_data = $questions[$current_q];
?>
    <div class="diagnostic-container">
        <div class="diagnostic-progress">
            <?php for ($i = 1; $i <= 10; $i++): ?>
            <div class="progress-dot <?= $i < $current_q ? 'done' : ($i == $current_q ? 'active' : '') ?>"></div>
            <?php endfor; ?>
        </div>
        
        <div class="question-container">
            <div class="question-number">Question <?= $current_q ?> sur 10</div>
            <h2 class="question-text"><?= htmlspecialchars($q_data['question']) ?></h2>
            
            <div class="answers-grid">
                <?php foreach ($q_data['answers'] as $idx => $answer): ?>
                <button class="answer-btn" 
                        data-scores='<?= json_encode($answer['scores']) ?>'
                        onclick="selectAnswer(this, <?= $current_q ?>, <?= $idx ?>)">
                    <?= htmlspecialchars($answer['text']) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="diagnostic-nav">
            <?php if ($current_q > 1): ?>
            <a href="?page=launchpad&action=diagnostic&q=<?= $current_q - 1 ?>" class="nav-btn nav-btn-back">
                <i class="fas fa-arrow-left"></i> Précédent
            </a>
            <?php else: ?>
            <a href="?page=launchpad" class="nav-btn nav-btn-back">
                <i class="fas fa-times"></i> Annuler
            </a>
            <?php endif; ?>
            
            <button class="nav-btn nav-btn-next" id="nextBtn" disabled onclick="nextQuestion(<?= $current_q ?>)">
                <?= $current_q < 10 ? 'Suivant' : 'Voir mes résultats' ?> <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>

<?php
// ══════════════════════════════════════════════
// VUE 2 : RÉSULTATS
// ══════════════════════════════════════════════
elseif ($action === 'results'):
    // Lire depuis DB prioritairement, sinon GET fallback
    if ($diagnostic) {
        $scores = json_decode($diagnostic['scores'], true);
    } else {
        $scores = json_decode($_GET['scores'] ?? '{}', true) ?: ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
    }
    
    arsort($scores);
    $sorted_keys = array_keys($scores);
    $top_parcours = $sorted_keys[0];
    $p_data = $parcours[$top_parcours];
    $secondary = array_slice($sorted_keys, 1, 2);
?>
    <div class="results-container">
        <div class="results-header" style="background: <?= $p_data['gradient'] ?>">
            <h2>🎯 Votre Priorité #1</h2>
            <p>Basé sur vos réponses, voici votre parcours personnalisé</p>
            <div class="results-score">
                <div class="score-circle"><?= $p_data['emoji'] ?></div>
            </div>
        </div>
        
        <div class="results-body">
            <!-- Parcours principal -->
            <div class="parcours-card" style="border-left: 4px solid <?= $p_data['color'] ?>">
                <div class="parcours-card-header">
                    <div class="parcours-emoji" style="background: <?= $p_data['gradient'] ?>"><?= $p_data['emoji'] ?></div>
                    <div class="parcours-info">
                        <h3>Parcours <?= $top_parcours ?> — <?= $p_data['name'] ?></h3>
                        <p><?= $p_data['description'] ?> • Durée estimée : <?= $p_data['duration'] ?></p>
                    </div>
                </div>
                
                <div class="parcours-modules">
                    <?php foreach ($p_data['modules'] as $mod): ?>
                    <span class="parcours-module-tag"><?= $mod ?></span>
                    <?php endforeach; ?>
                </div>
                
                <div class="quick-wins-section">
                    <div class="quick-wins-title">
                        <i class="fas fa-bolt" style="color: #f59e0b;"></i> Quick Wins à faire maintenant
                    </div>
                    <?php foreach ($p_data['quick_wins'] as $win): ?>
                    <div class="quick-win-item">
                        <div class="quick-win-check"><i class="fas fa-check"></i></div>
                        <span class="quick-win-text"><?= $win ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Parcours secondaires -->
            <h4 style="font-size: 14px; color: #64748b; margin-bottom: 16px;">
                <i class="fas fa-compass"></i> Parcours complémentaires recommandés
            </h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 30px;">
                <?php foreach ($secondary as $p_id): $r = $parcours[$p_id]; ?>
                <div class="parcours-card" style="margin-bottom: 0; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="parcours-emoji" style="background: <?= $r['gradient'] ?>; width: 40px; height: 40px; font-size: 20px;"><?= $r['emoji'] ?></div>
                        <div>
                            <div style="font-weight: 600; font-size: 14px; color: #1e293b;">Parcours <?= $p_id ?> — <?= $r['name'] ?></div>
                            <div style="font-size: 12px; color: #64748b;"><?= $r['description'] ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Barres de scores -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 30px;">
                <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">
                    <i class="fas fa-chart-bar" style="color: #6366f1;"></i> Détail de votre profil
                </h4>
                <?php 
                $max_score = max(1, max(array_values($scores)));
                foreach ($scores as $letter => $score):
                    $p = $parcours[$letter];
                    $pct = round(($score / $max_score) * 100);
                ?>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div style="width: 28px; font-size: 16px; text-align: center;"><?= $p['emoji'] ?></div>
                    <div style="width: 120px; font-size: 12px; font-weight: 600; color: #475569;"><?= $p['name'] ?></div>
                    <div style="flex: 1; background: #e2e8f0; border-radius: 6px; height: 8px; overflow: hidden;">
                        <div style="width: <?= $pct ?>%; height: 100%; background: <?= $p['color'] ?>; border-radius: 6px; transition: width 0.8s;"></div>
                    </div>
                    <div style="width: 30px; text-align: right; font-size: 12px; font-weight: 700; color: <?= $p['color'] ?>;"><?= $score ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- CTA -->
            <a href="?page=launchpad" class="start-plan-btn">
                <i class="fas fa-rocket"></i> Voir mon tableau de bord
            </a>
            <p style="text-align: center; margin-top: 16px; font-size: 13px; color: #64748b;">
                <a href="?page=launchpad&action=diagnostic&q=1" style="color: #3b82f6;" 
                   onclick="localStorage.removeItem('launchpad_scores');localStorage.removeItem('launchpad_answers');">
                    Refaire le diagnostic
                </a>
            </p>
        </div>
    </div>

<?php
// ══════════════════════════════════════════════
// VUE 3 : OVERVIEW
// ══════════════════════════════════════════════
else:
    $diag_parcours = $diagnostic ? ($parcours[$diagnostic['parcours_principal']] ?? null) : null;
?>
    <!-- Hero -->
    <div class="launchpad-hero">
        <div class="launchpad-hero-content">
            <div>
                <h1>🧭 GPS Stratégique</h1>
                <?php if ($diagnostic && $diag_parcours): ?>
                <p>Votre diagnostic est complété ! Parcours recommandé : <strong><?= $diag_parcours['emoji'] ?> <?= $diag_parcours['name'] ?></strong></p>
                <?php else: ?>
                <p>Répondez en 2 minutes. Je vous donne un plan clair + j'active les bons modules pour vous.</p>
                <?php endif; ?>
            </div>
            <?php if ($diagnostic): ?>
            <a href="?page=launchpad&action=results" class="hero-cta" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="fas fa-eye"></i> Voir mes résultats
            </a>
            <?php else: ?>
            <a href="?page=launchpad&action=diagnostic&q=1" class="hero-cta">
                <i class="fas fa-play"></i> Lancer le diagnostic
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($diagnostic && $diag_parcours): ?>
    <!-- Résumé diagnostic complété -->
    <div class="diagnostic-summary">
        <div class="diagnostic-summary-header">
            <h3>✅ Diagnostic complété</h3>
            <span class="parcours-tag" style="background: <?= $diag_parcours['gradient'] ?>;">
                <?= $diag_parcours['emoji'] ?> Parcours <?= $diagnostic['parcours_principal'] ?> — <?= $diag_parcours['name'] ?>
            </span>
        </div>
        <div class="quick-wins-title" style="color: #065f46; margin-bottom: 10px;">
            <i class="fas fa-bolt" style="color: #f59e0b;"></i> Vos 3 Quick Wins prioritaires
        </div>
        <div class="diagnostic-summary-qw">
            <?php foreach ($diag_parcours['quick_wins'] as $win): ?>
            <span><?= $win ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status Cards -->
    <div class="status-cards">
        <div class="status-card <?= $diagnostic ? 'done' : '' ?>">
            <span class="status-badge <?= $diagnostic ? 'done' : 'pending' ?>">
                <?= $diagnostic ? '✅ Complété' : 'À faire' ?>
            </span>
            <div class="status-card-header">
                <div class="status-card-icon" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6);">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div>
                    <div class="status-card-title">1. Diagnostic</div>
                    <div class="status-card-subtitle">10 questions • 2 min</div>
                </div>
            </div>
            <div class="status-card-content">
                Identifiez votre priorité #1, votre niveau et vos contraintes pour un plan sur-mesure.
            </div>
        </div>
        
        <div class="status-card <?= $diagnostic ? 'done' : '' ?>">
            <span class="status-badge <?= $diagnostic ? 'done' : 'pending' ?>">
                <?= $diagnostic ? '✅ Assigné' : 'En attente' ?>
            </span>
            <div class="status-card-header">
                <div class="status-card-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-map-signs"></i>
                </div>
                <div>
                    <div class="status-card-title">2. Votre Parcours</div>
                    <div class="status-card-subtitle">Personnalisé selon vos réponses</div>
                </div>
            </div>
            <div class="status-card-content">
                Découvrez les modules activés et vos 3 quick wins prioritaires à réaliser immédiatement.
            </div>
        </div>
        
        <div class="status-card">
            <span class="status-badge pending">En attente</span>
            <div class="status-card-header">
                <div class="status-card-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-tasks"></i>
                </div>
                <div>
                    <div class="status-card-title">3. Plan d'action</div>
                    <div class="status-card-subtitle">Checklist quotidienne</div>
                </div>
            </div>
            <div class="status-card-content">
                Suivez votre progression jour par jour avec des actions concrètes et mesurables.
            </div>
        </div>
    </div>
    
    <!-- Les 5 Parcours -->
    <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-compass" style="color: #6366f1;"></i> Les 5 Parcours disponibles
    </h3>
    
    <div class="parcours-overview">
        <?php foreach ($parcours as $id => $p): 
            $is_rec = ($diagnostic && $diagnostic['parcours_principal'] === $id);
        ?>
        <div class="parcours-mini-card <?= $is_rec ? 'recommended' : '' ?>" 
             onclick="window.location.href='?page=launchpad&action=results'">
            <div class="parcours-mini-emoji"><?= $p['emoji'] ?></div>
            <div class="parcours-mini-name">Parcours <?= $id ?></div>
            <div class="parcours-mini-name"><?= $p['name'] ?></div>
            <div class="parcours-mini-desc"><?= $p['description'] ?></div>
            <?php if ($is_rec): ?>
            <span class="recommended-badge">✨ Recommandé pour vous</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Comment ça marche -->
    <div style="background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0;">
        <h4 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 16px;">
            <i class="fas fa-info-circle" style="color: #3b82f6;"></i> Comment ça marche ?
        </h4>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <?php
            $steps_info = [
                ['num' => '1', 'label' => 'Diagnostic', 'sub' => '10 questions rapides', 'bg' => '#dbeafe,#bfdbfe', 'color' => '#3b82f6'],
                ['num' => '2', 'label' => 'Scoring', 'sub' => 'Calcul de votre profil', 'bg' => '#f3e8ff,#e9d5ff', 'color' => '#8b5cf6'],
                ['num' => '3', 'label' => 'Parcours', 'sub' => 'Plan personnalisé', 'bg' => '#dcfce7,#bbf7d0', 'color' => '#10b981'],
                ['num' => '4', 'label' => 'Action', 'sub' => 'Quick wins + plan', 'bg' => '#ffedd5,#fed7aa', 'color' => '#f59e0b'],
            ];
            foreach ($steps_info as $si):
            ?>
            <div style="text-align: center;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, <?= $si['bg'] ?>); color: <?= $si['color'] ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 20px; font-weight: 700;"><?= $si['num'] ?></div>
                <div style="font-weight: 600; font-size: 13px; color: #1e293b;"><?= $si['label'] ?></div>
                <div style="font-size: 12px; color: #64748b;"><?= $si['sub'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
<?php endif; ?>
</div>

<script>
let diagnosticScores = JSON.parse(localStorage.getItem('launchpad_scores') || '{"A":0,"B":0,"C":0,"D":0,"E":0}');
let selectedAnswers = JSON.parse(localStorage.getItem('launchpad_answers') || '{}');

function selectAnswer(btn, questionNum, answerIdx) {
    document.querySelectorAll('.answer-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    const scores = JSON.parse(btn.dataset.scores);
    selectedAnswers[questionNum] = { answerIdx, scores };
    localStorage.setItem('launchpad_answers', JSON.stringify(selectedAnswers));
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) nextBtn.disabled = false;
}

function nextQuestion(currentQ) {
    diagnosticScores = { A: 0, B: 0, C: 0, D: 0, E: 0 };
    Object.values(selectedAnswers).forEach(answer => {
        Object.entries(answer.scores).forEach(([p, score]) => {
            diagnosticScores[p] = (diagnosticScores[p] || 0) + score;
        });
    });
    localStorage.setItem('launchpad_scores', JSON.stringify(diagnosticScores));
    
    if (currentQ < 10) {
        window.location.href = '?page=launchpad&action=diagnostic&q=' + (currentQ + 1);
    } else {
        saveDiagnosticToDB();
    }
}

async function saveDiagnosticToDB() {
    const overlay = document.getElementById('savingOverlay');
    if (overlay) overlay.classList.add('active');
    
    const simplifiedAnswers = {};
    Object.entries(selectedAnswers).forEach(([q, data]) => {
        simplifiedAnswers[q] = data.answerIdx;
    });
    
    try {
        const response = await fetch('?page=launchpad&action=save-diagnostic', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ answers: simplifiedAnswers, scores: diagnosticScores })
        });
        const result = await response.json();
        
        if (result.success) {
            localStorage.removeItem('launchpad_scores');
            localStorage.removeItem('launchpad_answers');
            window.location.href = '?page=launchpad&action=results';
        } else {
            window.location.href = '?page=launchpad&action=results&scores=' + encodeURIComponent(JSON.stringify(diagnosticScores));
        }
    } catch (err) {
        window.location.href = '?page=launchpad&action=results&scores=' + encodeURIComponent(JSON.stringify(diagnosticScores));
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const currentQ = parseInt(new URLSearchParams(window.location.search).get('q'));
    if (currentQ && selectedAnswers[currentQ]) {
        const btns = document.querySelectorAll('.answer-btn');
        if (btns[selectedAnswers[currentQ].answerIdx]) {
            btns[selectedAnswers[currentQ].answerIdx].classList.add('selected');
            const nextBtn = document.getElementById('nextBtn');
            if (nextBtn) nextBtn.disabled = false;
        }
    }
});
</script>