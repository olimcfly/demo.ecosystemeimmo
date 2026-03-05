<?php
/**
 * Module Scoring Leads
 * /admin/modules/scoring/index.php
 */

// Connexion BDD
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('<div class="alert alert-danger">Erreur de connexion: ' . $e->getMessage() . '</div>');
}

// Créer la table des règles de scoring si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS scoring_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    operator VARCHAR(20) NOT NULL,
    field_value VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ajouter colonnes score et temperature si elles n'existent pas
try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS score INT DEFAULT 0");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS temperature ENUM('cold','warm','hot') DEFAULT 'cold'");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS score_updated_at TIMESTAMP NULL");
} catch (PDOException $e) {
    // Colonnes peuvent déjà exister
}

// Insérer les règles par défaut si vide
$rulesCount = $pdo->query("SELECT COUNT(*) FROM scoring_rules")->fetchColumn();
if ($rulesCount == 0) {
    $defaultRules = [
        // Engagement
        ['Email fourni', 'engagement', 'email', 'not_empty', null, 10],
        ['Téléphone fourni', 'engagement', 'phone', 'not_empty', null, 15],
        ['Notes renseignées', 'engagement', 'notes', 'not_empty', null, 5],
        
        // Source
        ['Source: Recommandation', 'source', 'source', 'equals', 'Recommandation', 25],
        ['Source: Site web', 'source', 'source', 'equals', 'Site web', 15],
        ['Source: Google', 'source', 'source', 'equals', 'Google', 10],
        ['Source: Facebook', 'source', 'source', 'equals', 'Facebook', 8],
        
        // Valeur
        ['Valeur > 100 000€', 'value', 'estimated_value', 'greater_than', '100000', 20],
        ['Valeur > 200 000€', 'value', 'estimated_value', 'greater_than', '200000', 30],
        ['Valeur > 500 000€', 'value', 'estimated_value', 'greater_than', '500000', 40],
        
        // Pipeline
        ['Étape: Premier contact', 'pipeline', 'pipeline_stage_id', 'equals', '2', 10],
        ['Étape: Qualification', 'pipeline', 'pipeline_stage_id', 'equals', '3', 20],
        ['Étape: Visite programmée', 'pipeline', 'pipeline_stage_id', 'equals', '4', 35],
        ['Étape: Offre en cours', 'pipeline', 'pipeline_stage_id', 'equals', '5', 50],
        
        // Activité
        ['Action planifiée', 'activity', 'next_action', 'not_empty', null, 10],
        ['Créé < 7 jours', 'activity', 'created_days', 'less_than', '7', 15],
        ['Créé < 30 jours', 'activity', 'created_days', 'less_than', '30', 5],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO scoring_rules (name, category, field_name, operator, field_value, points) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($defaultRules as $rule) {
        $stmt->execute($rule);
    }
}

// Fonction pour calculer le score d'un lead
function calculateLeadScore($lead, $rules) {
    $score = 0;
    $matchedRules = [];
    
    // Calculer les jours depuis création
    $createdDays = floor((time() - strtotime($lead['created_at'])) / 86400);
    
    foreach ($rules as $rule) {
        if (!$rule['is_active']) continue;
        
        $fieldValue = null;
        
        // Champ spécial pour les jours
        if ($rule['field_name'] === 'created_days') {
            $fieldValue = $createdDays;
        } else {
            $fieldValue = $lead[$rule['field_name']] ?? null;
        }
        
        $matched = false;
        
        switch ($rule['operator']) {
            case 'equals':
                $matched = ($fieldValue == $rule['field_value']);
                break;
            case 'not_equals':
                $matched = ($fieldValue != $rule['field_value']);
                break;
            case 'not_empty':
                $matched = !empty($fieldValue);
                break;
            case 'empty':
                $matched = empty($fieldValue);
                break;
            case 'greater_than':
                $matched = (floatval($fieldValue) > floatval($rule['field_value']));
                break;
            case 'less_than':
                $matched = (floatval($fieldValue) < floatval($rule['field_value']));
                break;
            case 'contains':
                $matched = (stripos($fieldValue, $rule['field_value']) !== false);
                break;
        }
        
        if ($matched) {
            $score += $rule['points'];
            $matchedRules[] = $rule;
        }
    }
    
    return ['score' => $score, 'rules' => $matchedRules];
}

// Fonction pour déterminer la température
function getTemperature($score) {
    if ($score >= 70) return 'hot';
    if ($score >= 35) return 'warm';
    return 'cold';
}

// Récupérer les règles actives
$rules = $pdo->query("SELECT * FROM scoring_rules ORDER BY category, points DESC")->fetchAll();

// Récupérer tous les leads
$leads = $pdo->query("
    SELECT l.*, ps.name as stage_name, ps.color as stage_color
    FROM leads l 
    LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id 
    ORDER BY l.score DESC, l.created_at DESC
")->fetchAll();

// Recalculer les scores pour tous les leads
$updatedLeads = [];
foreach ($leads as $lead) {
    $result = calculateLeadScore($lead, $rules);
    $temperature = getTemperature($result['score']);
    
    // Mettre à jour en BDD si le score a changé
    if ($lead['score'] != $result['score'] || $lead['temperature'] != $temperature) {
        $stmt = $pdo->prepare("UPDATE leads SET score = ?, temperature = ?, score_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$result['score'], $temperature, $lead['id']]);
    }
    
    $lead['score'] = $result['score'];
    $lead['temperature'] = $temperature;
    $lead['matched_rules'] = $result['rules'];
    $updatedLeads[] = $lead;
}

// Trier par score décroissant
usort($updatedLeads, fn($a, $b) => $b['score'] - $a['score']);

// Statistiques
$hotLeads = count(array_filter($updatedLeads, fn($l) => $l['temperature'] === 'hot'));
$warmLeads = count(array_filter($updatedLeads, fn($l) => $l['temperature'] === 'warm'));
$coldLeads = count(array_filter($updatedLeads, fn($l) => $l['temperature'] === 'cold'));
$avgScore = count($updatedLeads) > 0 ? round(array_sum(array_column($updatedLeads, 'score')) / count($updatedLeads)) : 0;

// Grouper les règles par catégorie
$rulesByCategory = [];
foreach ($rules as $rule) {
    $rulesByCategory[$rule['category']][] = $rule;
}
?>

<style>
.scoring-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.scoring-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e2e8f0;
    text-align: center;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-card.hot {
    border-left: 4px solid #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, white 100%);
}

.stat-card.warm {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(135deg, #fffbeb 0%, white 100%);
}

.stat-card.cold {
    border-left: 4px solid #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, white 100%);
}

.stat-card.avg {
    border-left: 4px solid #8b5cf6;
    background: linear-gradient(135deg, #f5f3ff 0%, white 100%);
}

.stat-value {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 4px;
}

.stat-card.hot .stat-value { color: #ef4444; }
.stat-card.warm .stat-value { color: #f59e0b; }
.stat-card.cold .stat-value { color: #3b82f6; }
.stat-card.avg .stat-value { color: #8b5cf6; }

.stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.stat-icon {
    font-size: 24px;
    margin-bottom: 8px;
}

/* Tabs */
.tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    width: fit-content;
}

.tab {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    color: #64748b;
    transition: all 0.2s ease;
}

.tab:hover {
    color: #1e293b;
}

.tab.active {
    background: white;
    color: #6366f1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Lead Table */
.leads-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.leads-table th {
    background: #f8fafc;
    padding: 14px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #e2e8f0;
}

.leads-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.leads-table tr:hover {
    background: #f8fafc;
}

.leads-table tr:last-child td {
    border-bottom: none;
}

/* Score Badge */
.score-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 14px;
}

.score-badge.hot {
    background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
    color: #991b1b;
}

.score-badge.warm {
    background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
    color: #92400e;
}

.score-badge.cold {
    background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
    color: #1e40af;
}

/* Temperature Icon */
.temp-icon {
    font-size: 16px;
}

.temp-icon.hot { color: #ef4444; }
.temp-icon.warm { color: #f59e0b; }
.temp-icon.cold { color: #3b82f6; }

/* Lead Info */
.lead-name {
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.lead-contact {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

/* Stage Badge */
.stage-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    background: #f1f5f9;
    color: #64748b;
}

/* Value */
.lead-value {
    font-weight: 600;
    color: #10b981;
}

/* Score Progress */
.score-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 4px;
}

.score-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.score-bar-fill.hot { background: linear-gradient(90deg, #f87171 0%, #ef4444 100%); }
.score-bar-fill.warm { background: linear-gradient(90deg, #fbbf24 0%, #f59e0b 100%); }
.score-bar-fill.cold { background: linear-gradient(90deg, #60a5fa 0%, #3b82f6 100%); }

/* Rules */
.rules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.rules-category {
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.rules-category-header {
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rules-category-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.rules-category-icon.engagement { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
.rules-category-icon.source { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.rules-category-icon.value { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.rules-category-icon.pipeline { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
.rules-category-icon.activity { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }

.rules-category-title {
    font-weight: 700;
    color: #1e293b;
    text-transform: capitalize;
}

.rules-list {
    padding: 12px;
}

.rule-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.rule-item:last-child {
    margin-bottom: 0;
}

.rule-item:hover {
    background: #f1f5f9;
}

.rule-item.inactive {
    opacity: 0.5;
}

.rule-name {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 8px;
}

.rule-points {
    font-size: 14px;
    font-weight: 700;
    color: #10b981;
    background: rgba(16,185,129,0.1);
    padding: 4px 10px;
    border-radius: 6px;
}

.rule-points.negative {
    color: #ef4444;
    background: rgba(239,68,68,0.1);
}

/* Toggle Switch */
.toggle {
    position: relative;
    width: 44px;
    height: 24px;
    background: #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle.active {
    background: #10b981;
}

.toggle::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.toggle.active::after {
    left: 22px;
}

/* Actions */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99,102,241,0.3);
}

.btn-secondary {
    background: white;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f8fafc;
    color: #1e293b;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Score Details Popup */
.score-details {
    position: relative;
}

.score-tooltip {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 8px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    padding: 16px;
    min-width: 280px;
    z-index: 100;
    border: 1px solid #e2e8f0;
}

.score-details:hover .score-tooltip {
    display: block;
}

.score-tooltip h4 {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.score-tooltip-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 13px;
}

.score-tooltip-item:last-child {
    border-bottom: none;
}

.score-tooltip-name {
    color: #374151;
}

.score-tooltip-points {
    font-weight: 600;
    color: #10b981;
}

/* Filter Pills */
.filter-pills {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

.filter-pill {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    transition: all 0.2s ease;
}

.filter-pill:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.filter-pill.active {
    background: #6366f1;
    border-color: #6366f1;
    color: white;
}

.filter-pill .count {
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 6px;
}

.filter-pill.active .count {
    background: rgba(255,255,255,0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 40px;
    color: #64748b;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

.empty-state h3 {
    font-size: 18px;
    color: #1e293b;
    margin-bottom: 8px;
}

/* Responsive */
@media (max-width: 1024px) {
    .scoring-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .rules-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .scoring-stats {
        grid-template-columns: 1fr;
    }
    
    .leads-table {
        display: block;
        overflow-x: auto;
    }
}
</style>

<!-- Header -->
<div class="scoring-header">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 4px;">
            🎯 Scoring des leads
        </h2>
        <p style="color: #64748b; font-size: 14px;">
            Identifiez vos prospects les plus qualifiés
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button class="btn btn-secondary" onclick="recalculateAllScores()">
            <i class="fas fa-sync-alt"></i> Recalculer
        </button>
        <button class="btn btn-primary" onclick="showTab('rules')">
            <i class="fas fa-cog"></i> Règles
        </button>
    </div>
</div>

<!-- Stats -->
<div class="scoring-stats">
    <div class="stat-card hot">
        <div class="stat-icon">🔥</div>
        <div class="stat-value"><?php echo $hotLeads; ?></div>
        <div class="stat-label">Leads chauds</div>
    </div>
    <div class="stat-card warm">
        <div class="stat-icon">☀️</div>
        <div class="stat-value"><?php echo $warmLeads; ?></div>
        <div class="stat-label">Leads tièdes</div>
    </div>
    <div class="stat-card cold">
        <div class="stat-icon">❄️</div>
        <div class="stat-value"><?php echo $coldLeads; ?></div>
        <div class="stat-label">Leads froids</div>
    </div>
    <div class="stat-card avg">
        <div class="stat-icon">📊</div>
        <div class="stat-value"><?php echo $avgScore; ?></div>
        <div class="stat-label">Score moyen</div>
    </div>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab active" onclick="showTab('leads')">
        <i class="fas fa-users"></i> Leads (<?php echo count($updatedLeads); ?>)
    </button>
    <button class="tab" onclick="showTab('rules')">
        <i class="fas fa-sliders-h"></i> Règles de scoring
    </button>
</div>

<!-- Tab: Leads -->
<div class="tab-content active" id="tab-leads">
    <!-- Filter Pills -->
    <div class="filter-pills">
        <button class="filter-pill active" data-filter="all" onclick="filterByTemp('all', this)">
            Tous <span class="count"><?php echo count($updatedLeads); ?></span>
        </button>
        <button class="filter-pill" data-filter="hot" onclick="filterByTemp('hot', this)">
            🔥 Chauds <span class="count"><?php echo $hotLeads; ?></span>
        </button>
        <button class="filter-pill" data-filter="warm" onclick="filterByTemp('warm', this)">
            ☀️ Tièdes <span class="count"><?php echo $warmLeads; ?></span>
        </button>
        <button class="filter-pill" data-filter="cold" onclick="filterByTemp('cold', this)">
            ❄️ Froids <span class="count"><?php echo $coldLeads; ?></span>
        </button>
    </div>
    
    <?php if (empty($updatedLeads)): ?>
        <div class="empty-state">
            <i class="fas fa-user-slash"></i>
            <h3>Aucun lead</h3>
            <p>Ajoutez des leads dans le pipeline pour voir leur scoring.</p>
        </div>
    <?php else: ?>
        <table class="leads-table">
            <thead>
                <tr>
                    <th>Lead</th>
                    <th>Score</th>
                    <th>Étape</th>
                    <th>Valeur</th>
                    <th>Source</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($updatedLeads as $lead): ?>
                    <tr data-temperature="<?php echo $lead['temperature']; ?>">
                        <td>
                            <div class="lead-name">
                                <span class="temp-icon <?php echo $lead['temperature']; ?>">
                                    <?php 
                                    echo match($lead['temperature']) {
                                        'hot' => '🔥',
                                        'warm' => '☀️',
                                        default => '❄️'
                                    };
                                    ?>
                                </span>
                                <?php echo htmlspecialchars(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')); ?>
                            </div>
                            <div class="lead-contact">
                                <?php if (!empty($lead['email'])): ?>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($lead['email']); ?>
                                <?php endif; ?>
                                <?php if (!empty($lead['phone'])): ?>
                                    &nbsp;•&nbsp; <i class="fas fa-phone"></i> <?php echo htmlspecialchars($lead['phone']); ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="score-details">
                                <div class="score-badge <?php echo $lead['temperature']; ?>">
                                    <?php echo $lead['score']; ?> pts
                                </div>
                                <div class="score-bar">
                                    <div class="score-bar-fill <?php echo $lead['temperature']; ?>" 
                                         style="width: <?php echo min($lead['score'], 100); ?>%"></div>
                                </div>
                                
                                <?php if (!empty($lead['matched_rules'])): ?>
                                <div class="score-tooltip">
                                    <h4>Détail du score</h4>
                                    <?php foreach ($lead['matched_rules'] as $rule): ?>
                                        <div class="score-tooltip-item">
                                            <span class="score-tooltip-name"><?php echo htmlspecialchars($rule['name']); ?></span>
                                            <span class="score-tooltip-points">+<?php echo $rule['points']; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($lead['stage_name'])): ?>
                                <span class="stage-badge" style="background: <?php echo $lead['stage_color'] ?? '#f1f5f9'; ?>20; color: <?php echo $lead['stage_color'] ?? '#64748b'; ?>">
                                    <?php echo htmlspecialchars($lead['stage_name']); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (($lead['estimated_value'] ?? 0) > 0): ?>
                                <span class="lead-value"><?php echo number_format($lead['estimated_value'], 0, ',', ' '); ?> €</span>
                            <?php else: ?>
                                <span style="color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($lead['source'] ?? '-'); ?>
                        </td>
                        <td>
                            <a href="?page=crm-pipeline" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Tab: Rules -->
<div class="tab-content" id="tab-rules">
    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <p style="color: #64748b; font-size: 14px;">
            <i class="fas fa-info-circle"></i> 
            Les points s'additionnent pour chaque règle validée. Score ≥ 70 = Chaud, ≥ 35 = Tiède, < 35 = Froid.
        </p>
        <button class="btn btn-primary" onclick="openAddRuleModal()">
            <i class="fas fa-plus"></i> Nouvelle règle
        </button>
    </div>
    
    <div class="rules-grid">
        <?php 
        $categoryLabels = [
            'engagement' => ['Engagement', 'fas fa-handshake'],
            'source' => ['Source', 'fas fa-globe'],
            'value' => ['Valeur', 'fas fa-euro-sign'],
            'pipeline' => ['Pipeline', 'fas fa-filter'],
            'activity' => ['Activité', 'fas fa-clock']
        ];
        
        foreach ($rulesByCategory as $category => $catRules): 
            $label = $categoryLabels[$category] ?? [ucfirst($category), 'fas fa-tag'];
        ?>
            <div class="rules-category">
                <div class="rules-category-header">
                    <div class="rules-category-icon <?php echo $category; ?>">
                        <i class="<?php echo $label[1]; ?>"></i>
                    </div>
                    <div class="rules-category-title"><?php echo $label[0]; ?></div>
                </div>
                <div class="rules-list">
                    <?php foreach ($catRules as $rule): ?>
                        <div class="rule-item <?php echo $rule['is_active'] ? '' : 'inactive'; ?>">
                            <div class="rule-name">
                                <div class="toggle <?php echo $rule['is_active'] ? 'active' : ''; ?>" 
                                     onclick="toggleRule(<?php echo $rule['id']; ?>, this)"></div>
                                <?php echo htmlspecialchars($rule['name']); ?>
                            </div>
                            <div class="rule-points <?php echo $rule['points'] < 0 ? 'negative' : ''; ?>">
                                <?php echo $rule['points'] > 0 ? '+' : ''; ?><?php echo $rule['points']; ?> pts
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Add Rule -->
<div class="modal-overlay" id="ruleModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 9999;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 500px; margin: 20px;">
        <div style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 18px; font-weight: 700;">Nouvelle règle de scoring</h3>
            <button onclick="closeRuleModal()" style="background: #f1f5f9; border: none; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; font-size: 18px;">&times;</button>
        </div>
        <form id="ruleForm" onsubmit="saveRule(event)" style="padding: 24px;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nom de la règle</label>
                <input type="text" name="name" required style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Catégorie</label>
                    <select name="category" required style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="engagement">Engagement</option>
                        <option value="source">Source</option>
                        <option value="value">Valeur</option>
                        <option value="pipeline">Pipeline</option>
                        <option value="activity">Activité</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Points</label>
                    <input type="number" name="points" value="10" required style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Champ</label>
                    <select name="field_name" required style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="email">Email</option>
                        <option value="phone">Téléphone</option>
                        <option value="source">Source</option>
                        <option value="estimated_value">Valeur estimée</option>
                        <option value="pipeline_stage_id">Étape pipeline</option>
                        <option value="next_action">Prochaine action</option>
                        <option value="notes">Notes</option>
                        <option value="created_days">Jours depuis création</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Opérateur</label>
                    <select name="operator" required style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="not_empty">N'est pas vide</option>
                        <option value="empty">Est vide</option>
                        <option value="equals">Égal à</option>
                        <option value="not_equals">Différent de</option>
                        <option value="greater_than">Supérieur à</option>
                        <option value="less_than">Inférieur à</option>
                        <option value="contains">Contient</option>
                    </select>
                </div>
            </div>
            
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Valeur (si applicable)</label>
                <input type="text" name="field_value" placeholder="Laisser vide si non applicable" style="width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeRuleModal()" class="btn btn-secondary">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const API_URL = '/admin/modules/scoring/api.php';

// Tabs
function showTab(tabName) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    document.querySelector(`.tab-content#tab-${tabName}`).classList.add('active');
    document.querySelectorAll('.tab').forEach(t => {
        if ((tabName === 'leads' && t.textContent.includes('Leads')) ||
            (tabName === 'rules' && t.textContent.includes('Règles'))) {
            t.classList.add('active');
        }
    });
}

// Filter by temperature
function filterByTemp(temp, btn) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    
    document.querySelectorAll('.leads-table tbody tr').forEach(row => {
        if (temp === 'all' || row.dataset.temperature === temp) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Toggle rule
function toggleRule(ruleId, element) {
    const isActive = element.classList.contains('active');
    
    fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle_rule&rule_id=${ruleId}&is_active=${isActive ? 0 : 1}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            element.classList.toggle('active');
            element.closest('.rule-item').classList.toggle('inactive');
            showNotification('Règle mise à jour', 'success');
        } else {
            showNotification('Erreur: ' + (data.error || 'Inconnue'), 'error');
        }
    });
}

// Recalculate all scores
function recalculateAllScores() {
    showNotification('Recalcul en cours...', 'info');
    setTimeout(() => location.reload(), 500);
}

// Rule Modal
function openAddRuleModal() {
    document.getElementById('ruleModal').style.display = 'flex';
}

function closeRuleModal() {
    document.getElementById('ruleModal').style.display = 'none';
}

function saveRule(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'add_rule');
    
    fetch(API_URL, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification('Règle ajoutée', 'success');
            closeRuleModal();
            setTimeout(() => location.reload(), 500);
        } else {
            showNotification('Erreur: ' + (data.error || 'Inconnue'), 'error');
        }
    });
}

// Notification
function showNotification(message, type = 'info') {
    const colors = { success: '#10b981', error: '#ef4444', info: '#6366f1' };
    
    const notif = document.createElement('div');
    notif.style.cssText = `
        position: fixed; top: 20px; right: 20px; padding: 14px 20px;
        background: ${colors[type] || colors.info}; color: white;
        border-radius: 10px; font-size: 14px; font-weight: 500;
        z-index: 99999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease;
    `;
    notif.textContent = message;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 300);
    }, 2000);
}

// Close modal on escape/outside click
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeRuleModal(); });
document.getElementById('ruleModal').addEventListener('click', function(e) {
    if (e.target === this) closeRuleModal();
});
</script>