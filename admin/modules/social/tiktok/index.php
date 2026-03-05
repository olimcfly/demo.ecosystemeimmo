<?php
/**
 * MODULE: TikTok - Scripts Vidéo
 * ==============================
 * - Stratégie multi-plateformes & niveaux de conscience
 * - Scripts vidéo pour se filmer
 * - Option clonage vocal ElevenLabs
 * - Bibliothèque par persona
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

// Récupérer les personas
$personas = [];
try {
    $personas = $pdo->query("SELECT * FROM neuropersonas WHERE is_active = 1 ORDER BY type, name")->fetchAll();
} catch (Exception $e) {}

// Stats scripts
$stats = ['total' => 0, 'this_month' => 0, 'filmed' => 0];
try {
    $stats['total'] = $pdo->query("SELECT COUNT(*) FROM tiktok_scripts")->fetchColumn() ?: 0;
    $stats['this_month'] = $pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0;
    $stats['filmed'] = $pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE status = 'filmed'")->fetchColumn() ?: 0;
} catch (Exception $e) {}
?>

<style>
.tiktok-module {
    --tiktok-pink: #fe2c55;
    --tiktok-cyan: #25f4ee;
    --tiktok-dark: #161823;
    --primary: #6366f1;
    --success: #10b981;
    --warning: #f59e0b;
}

.tiktok-header {
    background: linear-gradient(135deg, #161823, #2d2d3a);
    border-radius: 16px;
    padding: 32px;
    color: white;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
    position: relative;
    overflow: hidden;
}

.tiktok-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: linear-gradient(135deg, #fe2c55, #25f4ee);
    border-radius: 50%;
    opacity: 0.1;
}

.tiktok-header .icon {
    width: 72px;
    height: 72px;
    background: linear-gradient(135deg, #fe2c55, #25f4ee);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    position: relative;
    z-index: 1;
}

.tiktok-header h1 { font-size: 24px; margin: 0 0 8px 0; position: relative; z-index: 1; }
.tiktok-header p { margin: 0; opacity: 0.8; font-size: 14px; position: relative; z-index: 1; }

.tiktok-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 12px;
    margin-bottom: 24px;
}

.tiktok-tab {
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

.tiktok-tab:hover { background: white; color: #1e293b; }
.tiktok-tab.active { 
    background: linear-gradient(135deg, #161823, #2d2d3a); 
    color: white; 
    box-shadow: 0 2px 8px rgba(0,0,0,0.15); 
}
.tiktok-tab .badge { background: #fe2c55; color: white; font-size: 10px; padding: 2px 8px; border-radius: 10px; }

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

.stat-mini .value { font-size: 24px; font-weight: 700; color: #1e293b; }
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
.btn-tiktok { background: linear-gradient(135deg, #fe2c55, #ff6b81); color: white; }
.btn-tiktok:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(254,44,85,0.3); }
.btn-secondary { background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; }
.btn-success { background: var(--success); color: white; }
.btn-sm { padding: 8px 14px; font-size: 12px; }
.btn-icon { width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center; }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #1e293b; }
.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}
.form-control:focus { outline: none; border-color: #fe2c55; box-shadow: 0 0 0 3px rgba(254,44,85,0.1); }
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

.platform-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.platform-badge.tiktok { background: linear-gradient(135deg, rgba(254,44,85,0.1), rgba(37,244,238,0.1)); color: #161823; }
.platform-badge.facebook { background: #e7f0ff; color: #1877f2; }
.platform-badge.instagram { background: linear-gradient(135deg, rgba(131,58,180,0.1), rgba(253,29,29,0.1)); color: #c13584; }
.platform-badge.gmb { background: #e8f5e9; color: #34a853; }

.consciousness-level {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.consciousness-level.level-1 { background: #fee2e2; color: #dc2626; }
.consciousness-level.level-2 { background: #fef3c7; color: #d97706; }
.consciousness-level.level-3 { background: #fef9c3; color: #ca8a04; }
.consciousness-level.level-4 { background: #dcfce7; color: #16a34a; }
.consciousness-level.level-5 { background: #dbeafe; color: #2563eb; }

@media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .stats-row { flex-wrap: wrap; }
    .tiktok-header { flex-direction: column; text-align: center; }
}
</style>

<div class="tiktok-module">
    <!-- Header -->
    <div class="tiktok-header">
        <div class="icon"><i class="fab fa-tiktok"></i></div>
        <div>
            <h1>TikTok - Scripts Vidéo</h1>
            <p>Créez des scripts vidéo percutants • Filmez-vous ou clonez votre voix • Touchez une nouvelle audience</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="icon" style="background: linear-gradient(135deg, rgba(254,44,85,0.2), rgba(37,244,238,0.2)); color: #161823;">
                <i class="fas fa-scroll"></i>
            </div>
            <div>
                <div class="value"><?php echo $stats['total']; ?></div>
                <div class="label">Scripts créés</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #dcfce7; color: #16a34a;"><i class="fas fa-video"></i></div>
            <div>
                <div class="value"><?php echo $stats['filmed']; ?></div>
                <div class="label">Vidéos filmées</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #fef3c7; color: #d97706;"><i class="fas fa-calendar"></i></div>
            <div>
                <div class="value"><?php echo $stats['this_month']; ?></div>
                <div class="label">Ce mois-ci</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="icon" style="background: #f3e8ff; color: #7c3aed;"><i class="fas fa-users"></i></div>
            <div>
                <div class="value"><?php echo count($personas); ?></div>
                <div class="label">Personas</div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tiktok-tabs">
        <a href="?page=tiktok&tab=strategie" class="tiktok-tab <?php echo $tab === 'strategie' ? 'active' : ''; ?>">
            <i class="fas fa-graduation-cap"></i> Stratégie & Réseaux
        </a>
        <a href="?page=tiktok&tab=scripts" class="tiktok-tab <?php echo $tab === 'scripts' ? 'active' : ''; ?>">
            <i class="fas fa-pen-fancy"></i> Créer un script
        </a>
        <a href="?page=tiktok&tab=bibliotheque" class="tiktok-tab <?php echo $tab === 'bibliotheque' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Bibliothèque
            <?php if ($stats['total'] > 0): ?>
            <span class="badge"><?php echo $stats['total']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=tiktok&tab=clonage" class="tiktok-tab <?php echo $tab === 'clonage' ? 'active' : ''; ?>">
            <i class="fas fa-microphone-alt"></i> Clonage vocal
        </a>
    </div>

    <!-- Contenu des tabs -->
    <?php if ($tab === 'strategie'): ?>
        <?php include __DIR__ . '/tabs/strategie.php'; ?>
    
    <?php elseif ($tab === 'scripts'): ?>
        <?php include __DIR__ . '/tabs/scripts.php'; ?>
    
    <?php elseif ($tab === 'bibliotheque'): ?>
        <?php include __DIR__ . '/tabs/bibliotheque.php'; ?>
    
    <?php elseif ($tab === 'clonage'): ?>
        <?php include __DIR__ . '/tabs/clonage.php'; ?>
    
    <?php endif; ?>
</div>