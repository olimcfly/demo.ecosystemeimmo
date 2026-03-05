<?php
/**
 * TAB: Créer un Script Vidéo TikTok
 * ==================================
 * - Structure Hook → Corps → CTA
 * - Adapté au niveau de conscience
 * - Lié aux personas
 */

$editId = $_GET['id'] ?? null;
$editScript = null;

if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tiktok_scripts WHERE id = ?");
        $stmt->execute([$editId]);
        $editScript = $stmt->fetch();
    } catch (Exception $e) {}
}

// Pré-remplissage depuis la banque d'idées
$fromIdea = $_GET['from_idea'] ?? null;
$prefilledLevel = $_GET['level'] ?? ($editScript['consciousness_level'] ?? '');
$prefilledType = $_GET['type'] ?? ($editScript['video_type'] ?? 'hook');
$prefilledSubject = $_GET['subject'] ?? ($editScript['subject'] ?? '');
$prefilledHook = $_GET['hook'] ?? ($editScript['hook'] ?? '');
$prefilledBody = $_GET['body'] ?? ($editScript['body'] ?? '');
$prefilledCta = $_GET['cta'] ?? ($editScript['cta'] ?? '');

// Types de vidéos
$videoTypes = [
    'hook' => [
        'name' => 'Hook Choc',
        'icon' => '💥',
        'desc' => 'Vidéo courte qui capte l\'attention',
        'levels' => [1, 2],
        'duration' => '15-30s'
    ],
    'myth' => [
        'name' => 'Mythe vs Réalité',
        'icon' => '🤯',
        'desc' => 'Casser une idée reçue',
        'levels' => [1, 2],
        'duration' => '30-60s'
    ],
    'conseil' => [
        'name' => 'Conseil Pratique',
        'icon' => '💡',
        'desc' => 'Astuce actionnable',
        'levels' => [2, 3],
        'duration' => '30-60s'
    ],
    'story' => [
        'name' => 'Storytelling',
        'icon' => '📖',
        'desc' => 'Raconter une histoire client',
        'levels' => [2, 3],
        'duration' => '45-90s'
    ],
    'coulisse' => [
        'name' => 'Coulisses',
        'icon' => '🎬',
        'desc' => 'Journée type, visite, etc.',
        'levels' => [3, 4],
        'duration' => '30-60s'
    ],
    'temoignage' => [
        'name' => 'Témoignage',
        'icon' => '⭐',
        'desc' => 'Retour client satisfait',
        'levels' => [4, 5],
        'duration' => '30-60s'
    ],
];

// Niveaux de conscience
$levels = [
    1 => ['name' => 'Inconscient', 'color' => '#dc2626', 'bg' => '#fef2f2'],
    2 => ['name' => 'Problème', 'color' => '#ea580c', 'bg' => '#fff7ed'],
    3 => ['name' => 'Solution', 'color' => '#ca8a04', 'bg' => '#fefce8'],
    4 => ['name' => 'Produit', 'color' => '#059669', 'bg' => '#ecfdf5'],
    5 => ['name' => 'Très conscient', 'color' => '#2563eb', 'bg' => '#eff6ff'],
];
?>

<div class="content-card">
    <div class="card-header">
        <h3>
            <i class="fas fa-video" style="color: #fe2c55;"></i>
            <?php echo $editScript ? 'Modifier le script' : 'Créer un nouveau script vidéo'; ?>
        </h3>
        <button type="button" class="btn btn-secondary btn-sm" onclick="generateWithAI()">
            <i class="fas fa-magic"></i> Générer avec l'IA
        </button>
    </div>
    <div class="card-body">
        <form id="scriptForm">
            <input type="hidden" name="id" value="<?php echo $editScript['id'] ?? ''; ?>">
            
            <!-- Ligne 1 : Persona + Niveau de conscience -->
            <div class="form-row" style="margin-bottom: 24px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">
                        <i class="fas fa-user-tag" style="color: #6366f1;"></i> Pour quel Persona ? *
                    </label>
                    <select name="persona_id" id="personaSelect" class="form-control" required>
                        <option value="">-- Sélectionnez un persona --</option>
                        <?php 
                        $acheteurs = array_filter($personas, fn($p) => $p['type'] === 'acheteur');
                        $vendeurs = array_filter($personas, fn($p) => $p['type'] === 'vendeur');
                        ?>
                        <?php if (!empty($acheteurs)): ?>
                        <optgroup label="🏠 Personas Acheteurs">
                            <?php foreach ($acheteurs as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($editScript && $editScript['persona_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($vendeurs)): ?>
                        <optgroup label="🔑 Personas Vendeurs">
                            <?php foreach ($vendeurs as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($editScript && $editScript['persona_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($personas)): ?>
                    <p class="form-help" style="color: #dc2626;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <a href="?page=neuropersona">Créez vos personas d'abord</a>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">
                        <i class="fas fa-brain" style="color: #8b5cf6;"></i> Niveau de conscience ciblé *
                    </label>
                    <select name="consciousness_level" id="levelSelect" class="form-control" required>
                        <option value="">-- Sélectionnez --</option>
                        <?php foreach ($levels as $num => $level): ?>
                        <option value="<?php echo $num; ?>" data-color="<?php echo $level['color']; ?>" <?php echo ($prefilledLevel == $num) ? 'selected' : ''; ?>>
                            Niveau <?php echo $num; ?> - <?php echo $level['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help">TikTok cible principalement les niveaux 1-2 (découverte)</p>
                </div>
            </div>
            
            <!-- Type de vidéo -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-film" style="color: #fe2c55;"></i> Type de vidéo *
                </label>
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;">
                    <?php foreach ($videoTypes as $key => $type): ?>
                    <label class="video-type-card" data-type="<?php echo $key; ?>" data-levels="<?php echo implode(',', $type['levels']); ?>">
                        <input type="radio" name="video_type" value="<?php echo $key; ?>" 
                               <?php echo ($prefilledType === $key) ? 'checked' : ''; ?>>
                        <div class="type-emoji"><?php echo $type['icon']; ?></div>
                        <strong><?php echo $type['name']; ?></strong>
                        <span class="type-duration"><?php echo $type['duration']; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sujet -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-heading" style="color: #f59e0b;"></i> Sujet de la vidéo *
                </label>
                <input type="text" name="subject" id="subjectField" class="form-control" 
                       value="<?php echo htmlspecialchars($prefilledSubject); ?>"
                       placeholder="Ex: Les 3 erreurs qui font perdre de l'argent aux vendeurs" required>
            </div>
            
            <!-- Structure du script -->
            <div style="background: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h4 style="font-size: 14px; font-weight: 600; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-scroll" style="color: #fe2c55;"></i>
                    Structure du Script
                </h4>
                
                <!-- HOOK (0-3 secondes) -->
                <div class="script-section" style="border-left: 4px solid #dc2626;">
                    <div class="script-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="script-time" style="background: #dc2626;">0-3s</span>
                            <div>
                                <strong>🎣 HOOK</strong>
                                <span>L'accroche qui arrête le scroll</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="suggestHook()">
                            <i class="fas fa-lightbulb"></i> Idées
                        </button>
                    </div>
                    <textarea name="hook" id="hookField" class="form-control" rows="2" 
                              placeholder="Ex: &quot;Vous allez perdre 20 000€ si vous faites cette erreur...&quot;" required><?php echo htmlspecialchars($prefilledHook); ?></textarea>
                    <div class="hook-tips" id="hookTips" style="display: none;">
                        <button type="button" onclick="insertHook('question')">❓ Question choc</button>
                        <button type="button" onclick="insertHook('stat')">📊 Statistique</button>
                        <button type="button" onclick="insertHook('contre')">🔄 Contre-intuitif</button>
                        <button type="button" onclick="insertHook('secret')">🤫 Secret révélé</button>
                        <button type="button" onclick="insertHook('erreur')">❌ Erreur courante</button>
                    </div>
                </div>
                
                <!-- CORPS (3-45 secondes) -->
                <div class="script-section" style="border-left: 4px solid #f59e0b;">
                    <div class="script-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="script-time" style="background: #f59e0b;">3-45s</span>
                            <div>
                                <strong>📝 CORPS</strong>
                                <span>Le contenu principal (parlez comme à un ami)</span>
                            </div>
                        </div>
                    </div>
                    <textarea name="body" id="bodyField" class="form-control" rows="6" 
                              placeholder="Développez votre propos de manière conversationnelle. Utilisez des phrases courtes. Parlez comme vous parleriez à un ami." required><?php echo htmlspecialchars($prefilledBody); ?></textarea>
                    <p class="form-help">
                        💡 Astuce : Écrivez comme vous parlez. Phrases courtes. Ton naturel.
                    </p>
                </div>
                
                <!-- CTA (dernières secondes) -->
                <div class="script-section" style="border-left: 4px solid #10b981;">
                    <div class="script-header">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="script-time" style="background: #10b981;">Fin</span>
                            <div>
                                <strong>👆 CALL TO ACTION</strong>
                                <span>Ce que vous voulez qu'ils fassent</span>
                            </div>
                        </div>
                    </div>
                    <textarea name="cta" id="ctaField" class="form-control" rows="2" 
                              placeholder="Ex: &quot;Suivez-moi pour plus de conseils immo, et dites-moi en commentaire votre plus grande peur quand vous pensez à acheter.&quot;" required><?php echo htmlspecialchars($prefilledCta); ?></textarea>
                    <div class="cta-suggestions">
                        <button type="button" onclick="insertCTA('follow')">➕ Suivez-moi</button>
                        <button type="button" onclick="insertCTA('comment')">💬 Commentez</button>
                        <button type="button" onclick="insertCTA('share')">🔄 Partagez</button>
                        <button type="button" onclick="insertCTA('save')">🔖 Enregistrez</button>
                    </div>
                </div>
            </div>
            
            <!-- Conseils de tournage -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-camera" style="color: #6366f1;"></i> Notes de tournage (optionnel)
                </label>
                <textarea name="filming_notes" class="form-control" rows="2" 
                          placeholder="Ex: Filmer en extérieur devant une maison, utiliser le mode portrait, regarder la caméra..."><?php echo htmlspecialchars($editScript['filming_notes'] ?? ''); ?></textarea>
            </div>
            
            <!-- Méthode de création -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-magic" style="color: #8b5cf6;"></i> Comment allez-vous créer cette vidéo ?
                </label>
                <div style="display: flex; gap: 12px;">
                    <label class="method-card">
                        <input type="radio" name="creation_method" value="self" checked>
                        <div class="method-icon">🎥</div>
                        <strong>Je me filme</strong>
                        <span>Authentique et personnel</span>
                    </label>
                    <label class="method-card">
                        <input type="radio" name="creation_method" value="clone">
                        <div class="method-icon">🤖</div>
                        <strong>Clone IA (ElevenLabs)</strong>
                        <span>Avec ma voix clonée</span>
                    </label>
                </div>
            </div>
            
            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: space-between; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="?page=tiktok&tab=bibliotheque" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <div style="display: flex; gap: 12px;">
                    <button type="button" class="btn btn-secondary" onclick="previewScript()">
                        <i class="fas fa-eye"></i> Prévisualiser
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="copyScript()">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                    <button type="submit" class="btn btn-tiktok">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Preview -->
<div id="previewModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #161823; border-radius: 20px; width: 100%; max-width: 380px; overflow: hidden;">
        <!-- Simulation TikTok -->
        <div style="padding: 16px; display: flex; justify-content: space-between; align-items: center;">
            <span style="color: white; font-size: 14px;">Prévisualisation Script</span>
            <button onclick="closePreview()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">×</button>
        </div>
        <div style="background: #000; aspect-ratio: 9/16; max-height: 500px; display: flex; flex-direction: column; justify-content: center; padding: 24px; color: white;">
            <div id="previewHook" style="font-size: 18px; font-weight: 700; margin-bottom: 16px; line-height: 1.4;"></div>
            <div id="previewBody" style="font-size: 14px; line-height: 1.6; opacity: 0.9; margin-bottom: 16px; max-height: 200px; overflow-y: auto;"></div>
            <div id="previewCTA" style="font-size: 14px; font-weight: 600; color: #25f4ee;"></div>
        </div>
        <div style="padding: 16px; display: flex; gap: 8px; justify-content: center;">
            <button onclick="copyScript()" class="btn btn-secondary btn-sm">
                <i class="fas fa-copy"></i> Copier le script
            </button>
            <button onclick="closePreview()" class="btn btn-tiktok btn-sm">
                <i class="fas fa-check"></i> OK
            </button>
        </div>
    </div>
</div>

<!-- Modal IA -->
<div id="aiModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 500px;">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0;"><i class="fas fa-magic" style="color: #8b5cf6;"></i> Générer un script avec l'IA</h3>
        </div>
        <div style="padding: 20px;">
            <div class="form-group">
                <label class="form-label">Décrivez le sujet de votre vidéo</label>
                <textarea id="aiPrompt" class="form-control" rows="3" placeholder="Ex: Je veux expliquer aux primo-accédants pourquoi ils devraient visiter au moins 10 biens avant de faire une offre..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeAiModal()">Annuler</button>
                <button type="button" class="btn btn-primary" onclick="callAI()">
                    <i class="fas fa-magic"></i> Générer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.video-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px 8px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.video-type-card:hover { border-color: #fe2c55; background: rgba(254,44,85,0.02); }
.video-type-card:has(input:checked) { border-color: #fe2c55; background: rgba(254,44,85,0.05); }
.video-type-card input { display: none; }
.video-type-card .type-emoji { font-size: 24px; margin-bottom: 6px; }
.video-type-card strong { font-size: 11px; display: block; margin-bottom: 2px; }
.video-type-card .type-duration { font-size: 10px; color: #64748b; }

.script-section {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}
.script-section:last-child { margin-bottom: 0; }

.script-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.script-time {
    padding: 4px 10px;
    border-radius: 6px;
    color: white;
    font-size: 11px;
    font-weight: 700;
}
.script-header strong { display: block; font-size: 13px; }
.script-header span { display: block; font-size: 11px; color: #64748b; }

.hook-tips, .cta-suggestions {
    display: flex;
    gap: 6px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.hook-tips button, .cta-suggestions button {
    padding: 4px 10px;
    font-size: 11px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
}
.hook-tips button:hover, .cta-suggestions button:hover { background: #f1f5f9; }

.method-card {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.method-card:hover { border-color: #94a3b8; }
.method-card:has(input:checked) { border-color: #fe2c55; background: rgba(254,44,85,0.05); }
.method-card input { display: none; }
.method-card .method-icon { font-size: 32px; margin-bottom: 8px; }
.method-card strong { font-size: 13px; display: block; margin-bottom: 4px; }
.method-card span { font-size: 11px; color: #64748b; }
</style>

<script>
// Afficher/masquer suggestions hook
function suggestHook() {
    const tips = document.getElementById('hookTips');
    tips.style.display = tips.style.display === 'none' ? 'flex' : 'none';
}

// Insérer un hook prédéfini
function insertHook(type) {
    const hooks = {
        question: "Savez-vous pourquoi 80% des acheteurs passent à côté de leur bien idéal ?",
        stat: "93% des vendeurs font cette erreur qui leur coûte des milliers d'euros.",
        contre: "Non, le prix n'est PAS le critère numéro 1 des acheteurs. Voici ce qui compte vraiment.",
        secret: "Je vais vous révéler ce que les agents immobiliers ne vous disent jamais...",
        erreur: "Arrêtez de faire cette erreur quand vous visitez un bien !"
    };
    document.getElementById('hookField').value = hooks[type] || '';
}

// Insérer un CTA prédéfini
function insertCTA(type) {
    const ctas = {
        follow: "Suivez-moi pour plus de conseils immo qui peuvent vous faire économiser des milliers d'euros.",
        comment: "Dites-moi en commentaire votre plus grande peur quand vous pensez à acheter.",
        share: "Partagez cette vidéo à quelqu'un qui envisage de vendre son bien.",
        save: "Enregistrez cette vidéo, vous en aurez besoin le jour où vous vendrez."
    };
    document.getElementById('ctaField').value = ctas[type] || '';
}

// Prévisualiser le script
function previewScript() {
    document.getElementById('previewHook').textContent = document.getElementById('hookField').value;
    document.getElementById('previewBody').textContent = document.getElementById('bodyField').value;
    document.getElementById('previewCTA').textContent = document.getElementById('ctaField').value;
    document.getElementById('previewModal').style.display = 'flex';
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

// Copier le script
function copyScript() {
    const hook = document.getElementById('hookField').value;
    const body = document.getElementById('bodyField').value;
    const cta = document.getElementById('ctaField').value;
    
    const fullScript = `🎣 HOOK:\n${hook}\n\n📝 CORPS:\n${body}\n\n👆 CTA:\n${cta}`;
    
    navigator.clipboard.writeText(fullScript).then(() => {
        alert('✅ Script copié !');
    });
}

// Modal IA
function generateWithAI() {
    document.getElementById('aiModal').style.display = 'flex';
}

function closeAiModal() {
    document.getElementById('aiModal').style.display = 'none';
}

function callAI() {
    const prompt = document.getElementById('aiPrompt').value;
    if (!prompt) {
        alert('Décrivez votre sujet de vidéo');
        return;
    }
    // À connecter avec l'API IA
    alert('Fonctionnalité IA à connecter');
    closeAiModal();
}

// Soumission formulaire
document.getElementById('scriptForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/tiktok/save-script.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '?page=tiktok&tab=bibliotheque';
        } else {
            alert('Erreur: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (error) {
        alert('Erreur de connexion');
    }
});
</script>