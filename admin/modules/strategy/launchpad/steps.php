<?php
/**
 * LAUNCHPAD STEPS ROUTER
 * /admin/modules/launchpad/steps.php
 * Affiche l'étape courante
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/LaunchpadManager.php';
require_once __DIR__ . '/LaunchpadAI.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $manager = new LaunchpadManager($pdo, $_SESSION['admin_id']);
    $requested_step = $_GET['step'] ?? $manager->getCurrentStep();
    $manager->goToStep($requested_step);
    
} catch (Exception $e) {
    die('Erreur: ' . $e->getMessage());
}

$step = intval($requested_step);
if ($step < 1 || $step > 5) {
    $step = 1;
}
?>

<style>
    .step-container {
        background: white;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .step-header {
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f4ff;
    }
    
    .step-header h2 {
        margin: 0 0 10px 0;
        font-size: 24px;
        font-weight: 700;
        color: #1a202c;
    }
    
    .step-header p {
        margin: 0;
        color: #6b7280;
        font-size: 14px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.2s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-item input[type="checkbox"] {
        width: auto;
        cursor: pointer;
    }
    
    .checkbox-item label {
        margin: 0;
        cursor: pointer;
        font-weight: 500;
    }
    
    .button-group {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }
    
    .btn-secondary {
        background: white;
        border: 1px solid #e5e7eb;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background: #f9fafb;
    }
    
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        border-left: 4px solid;
    }
    
    .alert-info {
        background: #eff6ff;
        border-color: #0c4a6e;
        color: #0c4a6e;
    }
    
    .alert-success {
        background: #d1fae5;
        border-color: #047857;
        color: #047857;
    }
    
    .alert-error {
        background: #fee2e2;
        border-color: #dc2626;
        color: #dc2626;
    }
</style>

<?php if ($step === 1): ?>
<!-- ========================================
     ÉTAPE 1: PROFIL & CONTEXTE
     ======================================== -->
<div class="step-container">
    <div class="step-header">
        <h2>👤 Étape 1 - Votre Profil</h2>
        <p>Aidez-nous à comprendre qui vous êtes et votre situation.</p>
    </div>
    
    <form id="step1Form">
        <div class="form-group">
            <label>Quel est votre métier ?</label>
            <select name="profession" required>
                <option value="">-- Sélectionnez --</option>
                <option value="agent">Agent Immobilier</option>
                <option value="mandataire">Mandataire</option>
                <option value="promoteur">Promoteur / Aménageur</option>
                <option value="chasseur">Chasseur Immobilier</option>
                <option value="investisseur">Investisseur</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Dans quelle ville / zone travaillez-vous ?</label>
            <input type="text" name="city" placeholder="Ex: Bordeaux" required>
        </div>
        
        <div class="form-group">
            <label>Type de zone</label>
            <select name="zone_type" required>
                <option value="">-- Sélectionnez --</option>
                <option value="town">Une ville précise</option>
                <option value="region">Une région</option>
                <option value="national">National</option>
                <option value="other">Autre</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Rayon de couverture (en km)</label>
            <input type="number" name="radius_km" placeholder="Ex: 10" min="0">
        </div>
        
        <div class="form-group">
            <label>Votre niveau d'expérience</label>
            <select name="experience_level" required>
                <option value="">-- Sélectionnez --</option>
                <option value="beginner">Débutant (0-2 ans)</option>
                <option value="intermediate">Intermédiaire (2-5 ans)</option>
                <option value="advanced">Avancé (5-10 ans)</option>
                <option value="expert">Expert (10+ ans)</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Votre objectif principal</label>
            <select name="main_objective" required>
                <option value="">-- Sélectionnez --</option>
                <option value="mandats">Trouver des mandats / listings</option>
                <option value="acheteurs">Attirer des acheteurs</option>
                <option value="investisseurs">Attirer des investisseurs</option>
                <option value="vendeurs">Attirer des vendeurs</option>
                <option value="credibilite">Établir ma crédibilité</option>
            </select>
        </div>
        
        <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="goToLaunchpad()">
                ← Retour
            </button>
            <button type="submit" class="btn btn-primary">
                Continuer → Persona
            </button>
        </div>
    </form>
    
    <script>
        document.getElementById('step1Form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const data = new FormData(e.target);
            const response = await fetch('/admin/api/launchpad/save-step.php', {
                method: 'POST',
                body: JSON.stringify({
                    step: 1,
                    data: Object.fromEntries(data)
                }),
                headers: {'Content-Type': 'application/json'}
            });
            
            if (response.ok) {
                window.location.href = '/admin/dashboard.php?page=launchpad&step=2';
            }
        });
        
        function goToLaunchpad() {
            window.location.href = '/admin/dashboard.php?page=launchpad';
        }
    </script>
</div>

<?php elseif ($step === 2): ?>
<!-- ========================================
     ÉTAPE 2: NEURO PERSONA
     ======================================== -->
<div class="step-container">
    <div class="step-header">
        <h2>🧠 Étape 2 - Votre Persona Prioritaire</h2>
        <p>Identifiez le persona principal que vous souhaitez cibler en priorité.</p>
    </div>
    
    <form id="step2Form">
        <div class="alert alert-info">
            💡 Vous pouvez ajouter d'autres personas plus tard. Concentrez-vous d'abord sur le plus important.
        </div>
        
        <div class="form-group">
            <label>Quel est votre persona principal ?</label>
            <select name="persona_type" required>
                <option value="">-- Sélectionnez --</option>
                <option value="urgent_seller">Vendeur Pressé</option>
                <option value="heritage_seller">Vendeur Patrimonial</option>
                <option value="first_buyer">Acheteur Primo-Accédant</option>
                <option value="investor">Investisseur</option>
                <option value="custom">Autre persona</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Nom ou description du persona</label>
            <input type="text" name="persona_name" placeholder="Ex: 'Couples jeunes cherchant à investir'" required>
        </div>
        
        <div class="form-group">
            <label>Niveau de conscience / connaissance du problème</label>
            <select name="consciousness_level" required>
                <option value="">-- Sélectionnez --</option>
                <option value="low">Faible - Ne sait pas qu'il a un problème</option>
                <option value="medium">Moyen - Connaît son problème</option>
                <option value="high">Élevé - Cherche activement une solution</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quels sont ses points douleur ? (freins) - Un par ligne</label>
            <textarea name="pain_points" placeholder="Peur de se faire arnaquer&#10;Manque de temps&#10;Manque de confiance..."></textarea>
        </div>
        
        <div class="form-group">
            <label>Quels sont ses désirs ? - Un par ligne</label>
            <textarea name="desires" placeholder="Vendre rapidement sans stress&#10;Maximiser le prix&#10;Avoir un accompagnement..."></textarea>
        </div>
        
        <div class="form-group">
            <label>Quels sont ses déclencheurs (ce qui le pousse à agir) ? - Un par ligne</label>
            <textarea name="triggers" placeholder="Urgent - mutation pro&#10;Décès familial&#10;Besoin d'argent..."></textarea>
        </div>
        
        <div class="button-group">
            <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                ← Étape 1
            </button>
            <button type="submit" class="btn btn-primary">
                Continuer → Offre
            </button>
        </div>
    </form>
    
    <script>
        document.getElementById('step2Form').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                persona_type: formData.get('persona_type'),
                persona_name: formData.get('persona_name'),
                consciousness_level: formData.get('consciousness_level'),
                pain_points: formData.get('pain_points').split('\n').filter(x => x.trim()),
                desires: formData.get('desires').split('\n').filter(x => x.trim()),
                triggers: formData.get('triggers').split('\n').filter(x => x.trim())
            };
            
            const response = await fetch('/admin/api/launchpad/save-step.php', {
                method: 'POST',
                body: JSON.stringify({
                    step: 2,
                    data: data
                }),
                headers: {'Content-Type': 'application/json'}
            });
            
            if (response.ok) {
                window.location.href = '/admin/dashboard.php?page=launchpad&step=3';
            }
        });
        
        function goToStep(step) {
            window.location.href = '/admin/dashboard.php?page=launchpad&step=' + step;
        }
    </script>
</div>

<?php elseif ($step === 3): ?>
<!-- ========================================
     ÉTAPE 3: PROMESSE & OFFRE
     ======================================== -->
<div class="step-container">
    <div class="step-header">
        <h2>📦 Étape 3 - Votre Promesse & Offre</h2>
        <p>L'IA génère votre promesse unique et votre offre structurée.</p>
    </div>
    
    <div id="promesse-section">
        <div class="alert alert-info">
            ⏳ Génération en cours... Cela peut prendre 10-15 secondes.
        </div>
    </div>
    
    <script>
        // Charger et générer la promesse
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('/admin/api/launchpad/generate-offre.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    renderPromiseAndOffer(result.data);
                } else {
                    document.getElementById('promesse-section').innerHTML = `
                        <div class="alert alert-error">
                            Erreur lors de la génération: ${result.error}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('promesse-section').innerHTML = `
                    <div class="alert alert-error">
                        Erreur: ${error.message}
                    </div>
                `;
            }
        });
        
        function renderPromiseAndOffer(data) {
            const html = `
                <form id="step3Form">
                    <div class="form-group">
                        <label>🔑 Votre Promesse (Générée par l'IA)</label>
                        <textarea name="promise" required>${data.promise}</textarea>
                        <small style="color: #6b7280; margin-top: 5px; display: block;">Vous pouvez modifier cette promesse si elle ne vous convient pas.</small>
                    </div>
                    
                    <div class="form-group">
                        <label>📌 Titre de l'offre</label>
                        <input type="text" name="offer_title" value="${data.offer.offer_title}" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🎯 Ce que vous faites</label>
                        <textarea name="offer_what" required>${data.offer.ce_que_tu_fais}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>👥 Pour qui</label>
                        <textarea name="offer_for_whom" required>${data.offer.pour_qui}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>⭐ Pourquoi vous ?</label>
                        <textarea name="offer_why" required>${data.offer.pourquoi_toi}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>✅ Résultat final garanti</label>
                        <textarea name="offer_result" required>${data.offer.resultat_final}</textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                            ← Étape 2
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="regenerateOffer()">
                            🔄 Régénérer
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Valider → Stratégie
                        </button>
                    </div>
                </form>
                
                <script>
                    document.getElementById('step3Form').addEventListener('submit', async (e) => {
                        e.preventDefault();
                        
                        const formData = new FormData(e.target);
                        const response = await fetch('/admin/api/launchpad/save-step.php', {
                            method: 'POST',
                            body: JSON.stringify({
                                step: 3,
                                data: Object.fromEntries(formData)
                            }),
                            headers: {'Content-Type': 'application/json'}
                        });
                        
                        if (response.ok) {
                            window.location.href = '/admin/dashboard.php?page=launchpad&step=4';
                        }
                    });
                    
                    function regenerateOffer() {
                        window.location.reload();
                    }
                    
                    function goToStep(step) {
                        window.location.href = '/admin/dashboard.php?page=launchpad&step=' + step;
                    }
                </script>
            `;
            
            document.getElementById('promesse-section').innerHTML = html;
        }
    </script>
</div>

<?php elseif ($step === 4): ?>
<!-- ========================================
     ÉTAPE 4: STRATÉGIE TRAFIC
     ======================================== -->
<div class="step-container">
    <div class="step-header">
        <h2>🎯 Étape 4 - Votre Stratégie Trafic</h2>
        <p>L'IA recommande la meilleure stratégie selon votre persona et objectif.</p>
    </div>
    
    <div id="strategie-section">
        <div class="alert alert-info">
            ⏳ Génération en cours... Cela peut prendre 10-15 secondes.
        </div>
    </div>
    
    <script>
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('/admin/api/launchpad/generate-strategie.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    renderStrategy(result.data);
                } else {
                    document.getElementById('strategie-section').innerHTML = `
                        <div class="alert alert-error">
                            Erreur: ${result.error}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('strategie-section').innerHTML = `
                    <div class="alert alert-error">
                        Erreur: ${error.message}
                    </div>
                `;
            }
        });
        
        function renderStrategy(data) {
            const canalsMap = {
                'organic_local': '🌐 SEO Local & Google Business Profile',
                'facebook_ads': '📱 Facebook / Instagram Ads',
                'google_ads': '🔍 Google Ads (Search & Display)',
                'hybrid': '🔀 Approche Hybride'
            };
            
            const canalLabel = canalsMap[data.canal_recommande] || data.canal_recommande;
            
            const html = `
                <form id="step4Form">
                    <div class="alert alert-success">
                        ✅ Recommandation: <strong>${canalLabel}</strong>
                    </div>
                    
                    <div class="form-group">
                        <label>💡 Pourquoi cette stratégie ?</label>
                        <textarea readonly style="background: #f9fafb; color: #6b7280;">${data.justification}</textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>🎯 Contenu recommandé</label>
                        <div class="checkbox-group">
                            ${data.contenus_recommandes.map(c => `
                                <div class="checkbox-item">
                                    <input type="checkbox" name="content_types" value="${c}" checked>
                                    <label>${c}</label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    
                    ${data.themes_locaux && data.themes_locaux.length > 0 ? `
                        <div class="form-group">
                            <label>📍 Thèmes SEO Locaux</label>
                            <textarea readonly style="background: #f9fafb;">${data.themes_locaux.join('\n')}</textarea>
                        </div>
                    ` : ''}
                    
                    <div class="form-group">
                        <label>📄 Pages à créer</label>
                        <textarea readonly style="background: #f9fafb;">${data.pages_a_creer.join('\n')}</textarea>
                    </div>
                    
                    <input type="hidden" name="traffic_choice" value="${data.canal_recommande}">
                    <input type="hidden" name="traffic_reasoning" value="${data.justification}">
                    
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(3)">
                            ← Étape 3
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Valider → Plan Final
                        </button>
                    </div>
                </form>
                
                <script>
                    document.getElementById('step4Form').addEventListener('submit', async (e) => {
                        e.preventDefault();
                        
                        const formData = new FormData(e.target);
                        const contentTypes = Array.from(formData.getAll('content_types'));
                        
                        const response = await fetch('/admin/api/launchpad/save-step.php', {
                            method: 'POST',
                            body: JSON.stringify({
                                step: 4,
                                data: {
                                    traffic_choice: formData.get('traffic_choice'),
                                    traffic_reasoning: formData.get('traffic_reasoning'),
                                    content_types: contentTypes
                                }
                            }),
                            headers: {'Content-Type': 'application/json'}
                        });
                        
                        if (response.ok) {
                            window.location.href = '/admin/dashboard.php?page=launchpad&step=5';
                        }
                    });
                    
                    function goToStep(step) {
                        window.location.href = '/admin/dashboard.php?page=launchpad&step=' + step;
                    }
                </script>
            `;
            
            document.getElementById('strategie-section').innerHTML = html;
        }
    </script>
</div>

<?php elseif ($step === 5): ?>
<!-- ========================================
     ÉTAPE 5: PLAN FINAL
     ======================================== -->
<div class="step-container">
    <div class="step-header">
        <h2>📋 Étape 5 - Votre Plan Final</h2>
        <p>Voici votre cahier stratégique complet, prêt à l'emploi.</p>
    </div>
    
    <div id="plan-section">
        <div class="alert alert-info">
            ⏳ Génération du plan final... Cela peut prendre 10-15 secondes.
        </div>
    </div>
    
    <script>
        window.addEventListener('load', async () => {
            try {
                const response = await fetch('/admin/api/launchpad/generate-plan-final.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({})
                });
                
                const result = await response.json();
                
                if (result.success) {
                    renderFinalPlan(result.data);
                } else {
                    document.getElementById('plan-section').innerHTML = `
                        <div class="alert alert-error">
                            Erreur: ${result.error}
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('plan-section').innerHTML = `
                    <div class="alert alert-error">
                        Erreur: ${error.message}
                    </div>
                `;
            }
        });
        
        function renderFinalPlan(data) {
            const html = `
                <form id="step5Form">
                    <div class="alert alert-success">
                        ✅ Votre cahier stratégique est prêt !
                    </div>
                    
                    <div style="background: #f0f4ff; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="margin: 0 0 15px 0; color: #4f46e5;">📋 ${data.titre}</h3>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Résumé Exécutif:</strong>
                            <p style="margin: 8px 0 0 0; color: #4b5563;">${data.resume_executif}</p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Profil:</strong>
                            <p style="margin: 8px 0 0 0; color: #4b5563;">${data.profil_et_persona.profil}</p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Persona Cible:</strong>
                            <p style="margin: 8px 0 0 0; color: #4b5563;">${data.profil_et_persona.persona}</p>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <strong>🎯 Prochaine Action Concrète (Semaine 1):</strong>
                            <p style="margin: 8px 0 0 0; color: #dc2626; font-weight: 600;">${data.prochaine_action}</p>
                        </div>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="goToStep(4)">
                            ← Étape 4
                        </button>
                        <button type="submit" class="btn btn-primary">
                            ✓ Finaliser
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="downloadPDF()">
                            ⬇️ Télécharger PDF
                        </button>
                    </div>
                </form>
                
                <script>
                    document.getElementById('step5Form').addEventListener('submit', async (e) => {
                        e.preventDefault();
                        
                        const response = await fetch('/admin/api/launchpad/save-step.php', {
                            method: 'POST',
                            body: JSON.stringify({
                                step: 5,
                                data: ${JSON.stringify(data)}
                            }),
                            headers: {'Content-Type': 'application/json'}
                        });
                        
                        if (response.ok) {
                            window.location.href = '/admin/dashboard.php?page=launchpad&complete=true';
                        }
                    });
                    
                    function downloadPDF() {
                        window.location.href = '/admin/api/launchpad/export-pdf.php';
                    }
                    
                    function goToStep(step) {
                        window.location.href = '/admin/dashboard.php?page=launchpad&step=' + step;
                    }
                </script>
            `;
            
            document.getElementById('plan-section').innerHTML = html;
        }
    </script>
</div>

<?php endif; ?>