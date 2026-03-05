<?php
/**
 * ══════════════════════════════════════════════════════════════════════
 * MODULE ADS-LAUNCH — Lancement Publicitaire (Méthode BizzBizz.io)
 * /admin/modules/ads-launch/index.php
 * ══════════════════════════════════════════════════════════════════════
 */

// ─── Connexion DB (héritée du dashboard ou standalone) ───
if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> Erreur DB: ' . $e->getMessage() . '</div>';
        return;
    }
}
if (isset($pdo) && !isset($db)) $db = $pdo;
if (isset($db) && !isset($pdo)) $pdo = $db;

// ─── Service Ads ───
$accounts = [];
$serviceFile = __DIR__ . '/AdsLaunchService.php';
if (file_exists($serviceFile)) {
    require_once $serviceFile;
    try {
        $ads = new AdsLaunchService($db, $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);
        $accounts = $ads->getAccounts();
    } catch (Exception $e) {
        $accounts = [];
    }
}

// ─── Tab active ───
$activeTab = $_GET['tab'] ?? 'checklist';
$allowedTabs = ['checklist', 'prerequisites', 'audiences', 'campaigns', 'analytics', 'budget'];
if (!in_array($activeTab, $allowedTabs)) $activeTab = 'checklist';
?>

<!-- ═══ HERO ═══ -->
<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-rocket"></i> Lancement Publicitaire</h1>
        <p>Structure, configure et pilote tes campagnes Facebook & Google Ads — Méthode BizzBizz.io</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat">
            <div class="mod-stat-value"><?= count($accounts) ?></div>
            <div class="mod-stat-label">Comptes</div>
        </div>
        <div class="mod-stat">
            <div class="mod-stat-value">5</div>
            <div class="mod-stat-label">Étapes</div>
        </div>
    </div>
</div>

<!-- ═══ SÉLECTION COMPTE ═══ -->
<div class="mod-toolbar">
    <div class="mod-toolbar-left">
        <div class="mod-form-group" style="margin:0">
            <select id="account-select" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:.85rem;font-family:var(--font);min-width:280px;background:var(--surface)">
                <?php if (empty($accounts)): ?>
                <option value="">— Créer un nouveau compte —</option>
                <?php else: ?>
                <option value="">— Sélectionner un compte —</option>
                <?php foreach ($accounts as $acc): ?>
                <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?> (<?= $acc['status'] ?>)</option>
                <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    <div class="mod-toolbar-right">
        <button class="mod-btn mod-btn-primary" id="btn-new-account"><i class="fas fa-plus"></i> Nouveau Compte</button>
    </div>
</div>

<!-- ═══ ONGLETS ═══ -->
<div class="mod-tabs" style="margin-bottom:20px">
    <a href="?page=ads-overview&tab=checklist" class="mod-tab <?= $activeTab === 'checklist' ? 'active' : '' ?>" data-tab="checklist"><i class="fas fa-check-circle"></i> Checklist</a>
    <a href="?page=ads-overview&tab=prerequisites" class="mod-tab <?= $activeTab === 'prerequisites' ? 'active' : '' ?>" data-tab="prerequisites"><i class="fas fa-wrench"></i> Prérequis</a>
    <a href="?page=ads-overview&tab=audiences" class="mod-tab <?= $activeTab === 'audiences' ? 'active' : '' ?>" data-tab="audiences"><i class="fas fa-users"></i> Audiences</a>
    <a href="?page=ads-overview&tab=campaigns" class="mod-tab <?= $activeTab === 'campaigns' ? 'active' : '' ?>" data-tab="campaigns"><i class="fas fa-chart-bar"></i> Campagnes</a>
    <a href="?page=ads-overview&tab=analytics" class="mod-tab <?= $activeTab === 'analytics' ? 'active' : '' ?>" data-tab="analytics"><i class="fas fa-chart-line"></i> Analytics</a>
</div>

<!-- ═══ SECTION: CHECKLIST ═══ -->
<div id="section-checklist" class="ads-section" style="<?= $activeTab !== 'checklist' ? 'display:none' : '' ?>">
    <div class="mod-card">
        <div class="mod-card-header"><h3>Checklist Lancement</h3></div>
        <div class="mod-card-body">
            <?php
            $checklist = [
                ['done' => true,  'title' => '1. Prérequis Techniques',      'desc' => 'Configuration Pixel Facebook & GTM'],
                ['done' => false, 'title' => '2. Structure du Compte',       'desc' => 'Business Manager & Compte Ads'],
                ['done' => false, 'title' => '3. Audiences Stratégiques',    'desc' => 'CI, LAL 180j, TNT'],
                ['done' => false, 'title' => '4. Campagnes & Nomenclature',  'desc' => 'Créer selon la nomenclature officielle'],
                ['done' => false, 'title' => '5. Optimisation & Suivi',      'desc' => 'KPIs & Ajustements budgétaires'],
            ];
            foreach ($checklist as $item): ?>
            <div class="mod-flex mod-items-center mod-gap" style="padding:14px 0;border-bottom:1px solid var(--border)">
                <div style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                    background:<?= $item['done'] ? 'var(--green-bg)' : 'var(--surface-2)' ?>;
                    color:<?= $item['done'] ? 'var(--green)' : 'var(--text-3)' ?>">
                    <i class="fas fa-<?= $item['done'] ? 'check' : 'circle' ?>" style="font-size:.7rem"></i>
                </div>
                <div>
                    <strong style="font-size:.9rem;color:var(--text);font-weight:600"><?= $item['title'] ?></strong>
                    <div class="mod-text-xs mod-text-muted"><?= $item['desc'] ?></div>
                </div>
                <span class="mod-badge <?= $item['done'] ? 'mod-badge-active' : 'mod-badge-inactive' ?>" style="margin-left:auto">
                    <?= $item['done'] ? 'Fait' : 'À faire' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══ SECTION: PRÉREQUIS ═══ -->
<div id="section-prerequisites" class="ads-section" style="<?= $activeTab !== 'prerequisites' ? 'display:none' : '' ?>">
    <div class="mod-card">
        <div class="mod-card-header">
            <h3>Prérequis Techniques</h3>
            <span class="mod-badge mod-badge-draft" id="prereq-progress-badge">0%</span>
        </div>
        <div class="mod-card-body">
            <div style="height:6px;background:var(--surface-2);border-radius:3px;overflow:hidden;margin-bottom:20px">
                <div id="prereq-progress" style="height:100%;width:0%;background:var(--green);border-radius:3px;transition:width .4s"></div>
            </div>
            <div id="prerequisites-checklist">
                <div class="mod-empty">
                    <i class="fas fa-cog"></i>
                    <h3>Sélectionnez un compte</h3>
                    <p>Choisissez un compte publicitaire pour voir les prérequis.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ SECTION: AUDIENCES ═══ -->
<div id="section-audiences" class="ads-section" style="<?= $activeTab !== 'audiences' ? 'display:none' : '' ?>">
    <div class="mod-card">
        <div class="mod-card-header">
            <h3>Audiences Stratégiques</h3>
            <button class="mod-btn mod-btn-primary mod-btn-sm" id="btn-create-audiences"><i class="fas fa-magic"></i> Créer les audiences</button>
        </div>
        <div class="mod-card-body">
            <div id="audiences-container">
                <div class="mod-grid mod-grid-3" style="margin-bottom:16px">
                    <?php
                    $audiences = [
                        ['name' => 'Custom Intent (CI)',  'desc' => 'Visiteurs site web + interactions', 'icon' => 'bullseye',  'color' => 'var(--accent)'],
                        ['name' => 'Lookalike 180j (LAL)','desc' => 'Similaires à vos clients',         'icon' => 'users',     'color' => 'var(--green)'],
                        ['name' => 'TNT (Test & Target)', 'desc' => 'Centres d\'intérêt ciblés',        'icon' => 'crosshairs','color' => 'var(--amber)'],
                    ];
                    foreach ($audiences as $aud): ?>
                    <div class="mod-card">
                        <div class="mod-card-body" style="text-align:center;padding:24px">
                            <div style="width:48px;height:48px;border-radius:12px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:<?= $aud['color'] ?>;background:color-mix(in srgb, <?= $aud['color'] ?> 10%, transparent)">
                                <i class="fas fa-<?= $aud['icon'] ?>"></i>
                            </div>
                            <strong style="font-size:.9rem;display:block;margin-bottom:4px"><?= $aud['name'] ?></strong>
                            <span class="mod-text-xs mod-text-muted"><?= $aud['desc'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ SECTION: CAMPAGNES ═══ -->
<div id="section-campaigns" class="ads-section" style="<?= $activeTab !== 'campaigns' ? 'display:none' : '' ?>">
    <div class="mod-card">
        <div class="mod-card-header"><h3>Campagnes & Nomenclature</h3></div>
        <div class="mod-card-body">
            <div class="mod-form-grid">
                <div class="mod-form-group">
                    <label>N° d'ordre</label>
                    <input type="number" id="camp-order" value="1" min="1">
                </div>
                <div class="mod-form-group">
                    <label>Température</label>
                    <select id="camp-temperature">
                        <option value="Cold">Cold</option>
                        <option value="Warm">Warm</option>
                        <option value="Hot">Hot</option>
                    </select>
                </div>
                <div class="mod-form-group">
                    <label>Objectif</label>
                    <select id="camp-objective">
                        <option value="Leads">Leads</option>
                        <option value="Traffic">Traffic</option>
                        <option value="Conversions">Conversions</option>
                        <option value="Awareness">Awareness</option>
                    </select>
                </div>
                <div class="mod-form-group">
                    <label>Nom généré</label>
                    <input type="text" id="camp-generated-name" readonly placeholder="Cliquer sur Générer...">
                </div>
            </div>
            <div style="margin-top:14px">
                <button class="mod-btn mod-btn-primary" id="btn-generate-campaign-name"><i class="fas fa-magic"></i> Générer le nom</button>
                <button class="mod-btn mod-btn-secondary" id="btn-copy-campaign-name" style="margin-left:8px"><i class="fas fa-copy"></i> Copier</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══ SECTION: ANALYTICS ═══ -->
<div id="section-analytics" class="ads-section" style="<?= $activeTab !== 'analytics' ? 'display:none' : '' ?>">
    <div class="mod-card">
        <div class="mod-card-header"><h3>Analytics & Alertes</h3></div>
        <div class="mod-card-body">
            <div id="alerts-container">
                <div class="mod-empty">
                    <i class="fas fa-chart-line"></i>
                    <h3>Aucune donnée</h3>
                    <p>Les analytics apparaîtront ici une fois vos campagnes lancées.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ═══ Tab switching (JS côté client pour rester réactif) ═══
document.querySelectorAll('.mod-tab[data-tab]').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        const target = this.dataset.tab;
        // Tabs
        document.querySelectorAll('.mod-tab[data-tab]').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        // Sections
        document.querySelectorAll('.ads-section').forEach(s => s.style.display = 'none');
        const section = document.getElementById('section-' + target);
        if (section) section.style.display = '';
        // Update URL sans reload
        const url = new URL(window.location);
        url.searchParams.set('tab', target);
        history.replaceState(null, '', url);
    });
});

// ═══ Générateur de nom de campagne ═══
document.getElementById('btn-generate-campaign-name')?.addEventListener('click', function() {
    const order = document.getElementById('camp-order')?.value || '1';
    const temp  = document.getElementById('camp-temperature')?.value || 'Cold';
    const obj   = document.getElementById('camp-objective')?.value || 'Leads';
    const date  = new Date().toISOString().slice(0,10).replace(/-/g,'');
    const name  = `C${String(order).padStart(2,'0')}_${temp}_${obj}_${date}`;
    document.getElementById('camp-generated-name').value = name;
});

document.getElementById('btn-copy-campaign-name')?.addEventListener('click', function() {
    const input = document.getElementById('camp-generated-name');
    if (input?.value) {
        navigator.clipboard.writeText(input.value);
        this.innerHTML = '<i class="fas fa-check"></i> Copié !';
        setTimeout(() => this.innerHTML = '<i class="fas fa-copy"></i> Copier', 1500);
    }
});

// ═══ Charger le JS externe s'il existe ═══
(function() {
    const s = document.createElement('script');
    s.src = '/admin/modules/ads-launch/assets/js/ads-launch.js';
    s.onerror = () => console.log('ads-launch.js non trouvé — fonctions de base embarquées');
    document.body.appendChild(s);
})();
</script>