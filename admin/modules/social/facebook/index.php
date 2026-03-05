<?php
/**
 * MODULE: Communication Facebook Organique
 * =========================================
 * Stratégie d'attraction sur profil personnel
 * - Comprendre la stratégie (formation)
 * - Rédiger avec la méthode MERE
 * - Journal des publications par Persona
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    die('<div class="alert alert-danger">Erreur de connexion</div>');
}

$tab = $_GET['tab'] ?? 'strategie';
$action = $_GET['action'] ?? null;

// Récupérer les personas depuis la table neuropersonas
$personas = [];
try {
    $personas = $pdo->query("SELECT * FROM neuropersonas WHERE is_active = 1 ORDER BY type, name")->fetchAll();
} catch (Exception $e) {
    // Table pas encore créée
}

// Stats publications
$stats = ['total' => 0, 'this_month' => 0, 'planned' => 0];
try {
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM facebook_posts")->fetchColumn() ?: 0;
    $stats['this_month'] = $pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0;
    $stats['planned'] = $pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'planned'")->fetchColumn() ?: 0;
} catch (Exception $e) {}
?>

<style>
.fb-module {
    --fb-blue: #1877f2;
    --fb-dark: #1e293b;
    --primary: #6366f1;
    --success: #10b981;
    --warning: #f59e0b;
}

.fb-header {
    background: linear-gradient(135deg, #1877f2, #0d65d9);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
}

.fb-header .icon {
    width: 72px;
    height: 72px;
    background: rgba(255,255,255,0.2);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
}

.fb-header h1 { font-size: 24px; margin: 0 0 8px 0; }
.fb-header p { margin: 0; opacity: 0.9; font-size: 14px; }

.fb-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.fb-tab {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.fb-tab:hover { background: white; color: var(--fb-dark); }
.fb-tab.active { background: white; color: var(--fb-blue); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.fb-tab .badge { background: var(--fb-blue); color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; }

.stats-row {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
}

.stat-mini {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-mini .icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.stat-mini .value { font-size: 24px; font-weight: 700; color: var(--fb-dark); }
.stat-mini .label { font-size: 12px; color: #64748b; }

.content-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.content-card .card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.content-card .card-header h3 {
    font-size: 15px;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.content-card .card-body { padding: 24px; }

.btn {
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.btn-fb { background: #1877f2; color: white; }
.btn-fb:hover { background: #0d65d9; }
.btn-secondary { background: #f1f5f9; color: var(--fb-dark); border: 1px solid #e2e8f0; }
.btn-success { background: var(--success); color: white; }
.btn-sm { padding: 8px 14px; font-size: 12px; }
.btn-icon { width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: var(--fb-dark); }
.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}
.form-control:focus { outline: none; border-color: var(--fb-blue); box-shadow: 0 0 0 3px rgba(24,119,242,0.1); }
textarea.form-control { min-height: 100px; resize: vertical; font-family: inherit; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-help { font-size: 12px; color: #64748b; margin-top: 6px; }

.alert {
    padding: 16px 20px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
    font-size: 13px;
}

.data-table tr:hover { background: rgba(24,119,242,0.02); }

.persona-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.persona-badge.acheteur { background: #dbeafe; color: #1e40af; }
.persona-badge.vendeur { background: #fce7f3; color: #9d174d; }

.post-type-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.post-type-badge.attirer { background: #dcfce7; color: #166534; }
.post-type-badge.connecter { background: #fef3c7; color: #92400e; }
.post-type-badge.convertir { background: #f3e8ff; color: #7c3aed; }

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.planned { background: #dbeafe; color: #1e40af; }
.status-badge.published { background: #dcfce7; color: #166534; }
.status-badge.draft { background: #f1f5f9; color: #64748b; }

@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .stats-row { flex-wrap: wrap; }
    .fb-header { flex-direction: column; text-align: center; }
}
</style>

<div class="fb-module">
    <!-- Header -->
    <div class="fb-header">
        <div class="icon"><i class="fab fa-facebook-f"></i></div>
        <div>
            <h1>Communication Facebook Organique</h1>
            <p>Stratégie d'attraction sur votre profil personnel • Méthode MERE • Zéro publicité</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="icon" style="background: #dbeafe; color: #1877f2;"><i class="fas fa-pen"></i></div>
            <div>
                <div class="value"><?php echo $stats['total']; ?></div>
                <div class="label">Publications créées</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-calendar-check"></i></div>
            <div>
                <div class="value"><?php echo $stats['this_month']; ?></div>
                <div class="label">Ce mois-ci</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #fef3c7; color: #d97706;"><i class="fas fa-clock"></i></div>
            <div>
                <div class="value"><?php echo $stats['planned']; ?></div>
                <div class="label">Planifiées</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #f3e8ff; color: #7c3aed;"><i class="fas fa-users"></i></div>
            <div>
                <div class="value"><?php echo count($personas); ?></div>
                <div class="label">Personas actifs</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="fb-tabs">
        <a href="?page=facebook&tab=strategie" class="fb-tab <?php echo $tab === 'strategie' ? 'active' : ''; ?>">
            <i class="fas fa-graduation-cap"></i> Comprendre la stratégie
        </a>
        <a href="?page=facebook&tab=rediger" class="fb-tab <?php echo $tab === 'rediger' ? 'active' : ''; ?>">
            <i class="fas fa-pen-fancy"></i> Rédiger un post
        </a>
        <a href="?page=facebook&tab=journal" class="fb-tab <?php echo $tab === 'journal' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Journal
            <?php if ($stats['planned'] > 0): ?>
            <span class="badge"><?php echo $stats['planned']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=facebook&tab=idees" class="fb-tab <?php echo $tab === 'idees' ? 'active' : ''; ?>">
            <i class="fas fa-lightbulb"></i> Banque d'idées
        </a>
    </div>

    <!-- Contenu des tabs -->
    <?php if ($tab === 'strategie'): ?>
        <?php include __DIR__ . '/tabs/strategie.php'; ?>
    
    <?php elseif ($tab === 'rediger'): ?>
        <?php include __DIR__ . '/tabs/rediger.php'; ?>
    
    <?php elseif ($tab === 'journal'): ?>
        <?php include __DIR__ . '/tabs/journal.php'; ?>
    
    <?php elseif ($tab === 'idees'): ?>
        <?php include __DIR__ . '/tabs/idees.php'; ?>
    
    <?php endif; ?>
</div>