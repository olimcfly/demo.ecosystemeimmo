<?php
/**
 * MODULE STRATEGY - Hub Stratégie Digitale
 * ÉCOSYSTÈME IMMO LOCAL+
 * 
 * Point d'entrée vers tous les modules stratégiques
 */

session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/init.php';

// Vérification authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

// Stats rapides pour les cards
$stats = [
    'personas_actifs' => 0,
    'campagnes_actives' => 0,
    'leads_mois' => 0
];

try {
    // Personas activés
    $result = $db->query("SELECT COUNT(*) as count FROM neuropersona_config WHERE user_id = ? AND actif = 1", [$userId])->fetch();
    $stats['personas_actifs'] = $result['count'] ?? 0;
    
    // Campagnes actives
    $result = $db->query("SELECT COUNT(*) as count FROM neuropersona_campagnes WHERE user_id = ? AND statut = 'active'", [$userId])->fetch();
    $stats['campagnes_actives'] = $result['count'] ?? 0;
    
    // Leads du mois (si table leads existe)
    $result = $db->query("SELECT COUNT(*) as count FROM leads WHERE user_id = ? AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", [$userId])->fetch();
    $stats['leads_mois'] = $result['count'] ?? 0;
} catch (Exception $e) {
    // Tables pas encore créées, on garde les valeurs par défaut
}

$pageTitle = "Stratégie Digitale";
include __DIR__ . '/../../layout/header.php';
?>

<style>
.strategy-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
}

.strategy-header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    color: white;
    position: relative;
    overflow: hidden;
}

.strategy-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.strategy-header h1 {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
    position: relative;
}

.strategy-header p {
    opacity: 0.9;
    font-size: 1rem;
    max-width: 600px;
    position: relative;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    text-align: center;
    border: 1px solid #e5e7eb;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.stat-label {
    color: #6b7280;
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

/* Modules Grid */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.module-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.module-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
    border-color: #6366f1;
}

.module-card.featured {
    border: 2px solid #6366f1;
    background: linear-gradient(135deg, rgba(99,102,241,0.03) 0%, rgba(139,92,246,0.03) 100%);
}

.module-card.featured::before {
    content: '⭐ Recommandé';
    position: absolute;
    top: 12px;
    right: -30px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    padding: 4px 40px;
    font-size: 0.7rem;
    font-weight: 600;
    transform: rotate(45deg);
}

.module-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 1rem;
}

.module-card h3 {
    color: #111827;
    margin-bottom: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.module-card p {
    color: #6b7280;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.module-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.module-tag {
    padding: 0.25rem 0.625rem;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #6b7280;
}

.module-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.module-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.module-btn.secondary {
    background: white;
    color: #6366f1;
    border: 1px solid #6366f1;
}

.module-btn.secondary:hover {
    background: rgba(99, 102, 241, 0.05);
}

.module-status {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    margin-left: 0.5rem;
}

.module-status.active {
    background: #dcfce7;
    color: #16a34a;
}

.module-status.beta {
    background: #fef3c7;
    color: #b45309;
}

.module-status.soon {
    background: #f3f4f6;
    color: #6b7280;
}

/* Conseil Box */
.conseil-box {
    background: linear-gradient(135deg, rgba(99,102,241,0.08) 0%, rgba(139,92,246,0.08) 100%);
    border: 1px solid rgba(99,102,241,0.2);
    border-radius: 12px;
    padding: 1.5rem;
}

.conseil-box h3 {
    color: #111827;
    margin-bottom: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.conseil-box p {
    color: #4b5563;
    font-size: 0.9rem;
    line-height: 1.6;
}

.conseil-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 1rem;
}

.conseil-step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 8px;
    font-size: 0.85rem;
    color: #374151;
}

.conseil-step-num {
    width: 24px;
    height: 24px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .strategy-container {
        padding: 1rem;
    }
    
    .strategy-header {
        padding: 1.5rem;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
    
    .conseil-steps {
        flex-direction: column;
    }
}
</style>

<div class="strategy-container">
    <!-- Header -->
    <div class="strategy-header">
        <h1>🎯 Stratégie Digitale</h1>
        <p>Méthodologie complète pour devenir le leader de votre zone : Persona → Offre → Canaux → Structures</p>
    </div>
    
    <!-- Stats rapides -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['personas_actifs'] ?></div>
            <div class="stat-label">Personas activés</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['campagnes_actives'] ?></div>
            <div class="stat-label">Campagnes actives</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['leads_mois'] ?></div>
            <div class="stat-label">Leads ce mois</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">4</div>
            <div class="stat-label">Canaux disponibles</div>
        </div>
    </div>
    
    <!-- Modules Grid -->
    <div class="modules-grid">
        
        <!-- NeuroPersona - Featured -->
        <div class="module-card featured">
            <div class="module-icon" style="background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(139,92,246,0.15));">
                🧠
            </div>
            <h3>
                NeuroPersona
                <span class="module-status active">● Actif</span>
            </h3>
            <p>Cartographie complète de vos personas acheteurs et vendeurs basée sur les neurosciences. Identifiez les motivations profondes et générez des campagnes ciblées.</p>
            <div class="module-tags">
                <span class="module-tag">10 Personas</span>
                <span class="module-tag">Campagnes IA</span>
                <span class="module-tag">Multi-canal</span>
            </div>
            <a href="/admin/modules/neuropersona/" class="module-btn">
                Accéder au module →
            </a>
        </div>
        
        <!-- Structure MERE -->
        <div class="module-card">
            <div class="module-icon" style="background: rgba(16, 185, 129, 0.1);">
                ✍️
            </div>
            <h3>
                Structure MERE
                <span class="module-status beta">Beta</span>
            </h3>
            <p>Composez des messages persuasifs basés sur la neuroscience : Motivation → Émotion → Raison → Engagement. Framework éprouvé pour convertir.</p>
            <div class="module-tags">
                <span class="module-tag">Copywriting</span>
                <span class="module-tag">Templates</span>
                <span class="module-tag">IA Assistée</span>
            </div>
            <a href="/admin/modules/strategy/mere/" class="module-btn secondary">
                Accéder →
            </a>
        </div>
        
        <!-- Traffic & SEO Local -->
        <div class="module-card">
            <div class="module-icon" style="background: rgba(245, 158, 11, 0.1);">
                🚀
            </div>
            <h3>
                Traffic & SEO Local
                <span class="module-status active">● Actif</span>
            </h3>
            <p>Optimisation Google My Business, SEO local, et stratégie multi-canal pour attirer des prospects qualifiés sur votre zone.</p>
            <div class="module-tags">
                <span class="module-tag">GMB</span>
                <span class="module-tag">SEO Local</span>
                <span class="module-tag">Réseaux sociaux</span>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="/admin/modules/seo/" class="module-btn secondary">
                    SEO →
                </a>
                <a href="/admin/modules/reseaux-sociaux/" class="module-btn secondary">
                    Social →
                </a>
            </div>
        </div>
        
        <!-- KPI & Analytics -->
        <div class="module-card">
            <div class="module-icon" style="background: rgba(236, 72, 153, 0.1);">
                📊
            </div>
            <h3>
                KPI & Métriques
                <span class="module-status soon">Bientôt</span>
            </h3>
            <p>Tableau de bord unifié pour suivre vos KPI en temps réel : trafic, conversions, ROI des campagnes et performance par canal.</p>
            <div class="module-tags">
                <span class="module-tag">Analytics</span>
                <span class="module-tag">ROI</span>
                <span class="module-tag">Rapports</span>
            </div>
            <button class="module-btn secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                Bientôt disponible
            </button>
        </div>
        
        <!-- Offres & Landing -->
        <div class="module-card">
            <div class="module-icon" style="background: rgba(6, 182, 212, 0.1);">
                🎁
            </div>
            <h3>
                Offres & Landing Pages
                <span class="module-status active">● Actif</span>
            </h3>
            <p>Créez des pages de capture et des offres irrésistibles pour convertir vos visiteurs en leads qualifiés.</p>
            <div class="module-tags">
                <span class="module-tag">Pages Capture</span>
                <span class="module-tag">Lead Magnets</span>
                <span class="module-tag">Conversion</span>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="/admin/modules/pages-capture/" class="module-btn secondary">
                    Captures →
                </a>
                <a href="/admin/modules/builder/" class="module-btn secondary">
                    Builder →
                </a>
            </div>
        </div>
        
        <!-- Email Marketing -->
        <div class="module-card">
            <div class="module-icon" style="background: rgba(139, 92, 246, 0.1);">
                📧
            </div>
            <h3>
                Email Marketing
                <span class="module-status active">● Actif</span>
            </h3>
            <p>Séquences email automatisées pour nurturing vos leads et relances intelligentes basées sur le comportement.</p>
            <div class="module-tags">
                <span class="module-tag">Séquences</span>
                <span class="module-tag">Automation</span>
                <span class="module-tag">Templates</span>
            </div>
            <a href="/admin/modules/emails/" class="module-btn secondary">
                Accéder →
            </a>
        </div>
        
    </div>
    
    <!-- Conseil Box -->
    <div class="conseil-box">
        <h3>💡 Méthodologie Recommandée</h3>
        <p>Suivez cette approche étape par étape pour maximiser vos résultats et devenir le leader de votre zone géographique.</p>
        <div class="conseil-steps">
            <div class="conseil-step">
                <span class="conseil-step-num">1</span>
                Définir vos Personas prioritaires
            </div>
            <div class="conseil-step">
                <span class="conseil-step-num">2</span>
                Crafter vos messages (Structure MERE)
            </div>
            <div class="conseil-step">
                <span class="conseil-step-num">3</span>
                Activer vos canaux de Traffic
            </div>
            <div class="conseil-step">
                <span class="conseil-step-num">4</span>
                Suivre vos KPI régulièrement
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>