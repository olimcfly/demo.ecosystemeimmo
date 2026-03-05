<?php
/**
 * TAB: Rédiger une Publication Facebook
 * Structure MERE + Lien avec NeuroPersonas
 */

$editId = $_GET['id'] ?? null;
$editPost = null;

if ($editId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM facebook_posts WHERE id = ?");
        $stmt->execute([$editId]);
        $editPost = $stmt->fetch();
    } catch (Exception $e) {}
}

// Types de posts
$postTypes = [
    'attirer' => ['name' => 'Attirer', 'icon' => 'magnet', 'color' => '#10b981', 'desc' => 'Conseils, valeur gratuite, opinions'],
    'connecter' => ['name' => 'Connecter', 'icon' => 'heart', 'color' => '#f59e0b', 'desc' => 'Storytelling, coulisses, témoignages'],
    'convertir' => ['name' => 'Convertir', 'icon' => 'bullseye', 'color' => '#8b5cf6', 'desc' => 'Lead magnet, offre, événement']
];
?>

<div class="content-card">
    <div class="card-header">
        <h3>
            <i class="fas fa-pen-fancy" style="color: #1877f2;"></i>
            <?php echo $editPost ? 'Modifier la publication' : 'Nouvelle publication Facebook'; ?>
        </h3>
        <?php if (!$editPost): ?>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-sm" onclick="generateWithAI()">
                <i class="fas fa-magic"></i> Générer avec l'IA
            </button>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form id="postForm" method="POST" action="api/facebook/save-post.php">
            <input type="hidden" name="id" value="<?php echo $editPost['id'] ?? ''; ?>">
            
            <!-- Sélection Persona et Type -->
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
                            <option value="<?php echo $p['id']; ?>" data-type="acheteur" <?php echo ($editPost && $editPost['persona_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                        <?php if (!empty($vendeurs)): ?>
                        <optgroup label="🔑 Personas Vendeurs">
                            <?php foreach ($vendeurs as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-type="vendeur" <?php echo ($editPost && $editPost['persona_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; ?>
                    </select>
                    <?php if (empty($personas)): ?>
                    <p class="form-help" style="color: #dc2626;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Aucun persona trouvé. <a href="?page=neuropersona">Créez vos personas d'abord</a>.
                    </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">
                        <i class="fas fa-bullseye" style="color: #f59e0b;"></i> Type de publication *
                    </label>
                    <div style="display: flex; gap: 8px;">
                        <?php foreach ($postTypes as $key => $type): ?>
                        <label class="type-card" data-type="<?php echo $key; ?>">
                            <input type="radio" name="post_type" value="<?php echo $key; ?>" 
                                   <?php echo (!$editPost && $key === 'attirer') || ($editPost && $editPost['post_type'] === $key) ? 'checked' : ''; ?> 
                                   style="display:none;">
                            <div class="type-icon" style="background: <?php echo $type['color']; ?>22; color: <?php echo $type['color']; ?>;">
                                <i class="fas fa-<?php echo $type['icon']; ?>"></i>
                            </div>
                            <strong><?php echo $type['name']; ?></strong>
                            <span><?php echo $type['desc']; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Structure MERE -->
            <div style="background: linear-gradient(135deg, rgba(99,102,241,0.05), rgba(139,92,246,0.05)); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h4 style="font-size: 14px; font-weight: 600; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                    <span style="background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        Méthode MERE
                    </span>
                    <span style="font-size: 11px; color: #64748b; font-weight: 400;">Motivation • Explication • Résultat • Exercice</span>
                </h4>
                
                <!-- M - Motivation -->
                <div class="mere-section" style="border-left: 4px solid #f59e0b;">
                    <div class="mere-header">
                        <span class="mere-letter" style="background: #f59e0b;">M</span>
                        <div>
                            <strong>MOTIVATION</strong>
                            <span>L'accroche qui arrête le scroll (1-2 lignes)</span>
                        </div>
                    </div>
                    <textarea name="motivation" id="motivationField" class="form-control" rows="2" placeholder="Ex: J'ai failli refuser ce mandat. Voici pourquoi j'ai bien fait de changer d'avis..." required><?php echo htmlspecialchars($editPost['motivation'] ?? ''); ?></textarea>
                    <div class="mere-tips">
                        <button type="button" class="tip-btn" onclick="insertTip('motivation', 'question')">❓ Question</button>
                        <button type="button" class="tip-btn" onclick="insertTip('motivation', 'choc')">💥 Choc</button>
                        <button type="button" class="tip-btn" onclick="insertTip('motivation', 'contre')">🔄 Contre-intuitif</button>
                        <button type="button" class="tip-btn" onclick="insertTip('motivation', 'histoire')">📖 Histoire</button>
                    </div>
                </div>
                
                <!-- E - Explication -->
                <div class="mere-section" style="border-left: 4px solid #3b82f6;">
                    <div class="mere-header">
                        <span class="mere-letter" style="background: #3b82f6;">E</span>
                        <div>
                            <strong>EXPLICATION</strong>
                            <span>Le contexte, l'histoire, le problème (3-5 lignes)</span>
                        </div>
                    </div>
                    <textarea name="explication" id="explicationField" class="form-control" rows="4" placeholder="Ex: Ce vendeur avait été déçu par 2 agences. Son bien était sur le marché depuis 8 mois sans aucune offre sérieuse. Quand il m'a appelé, j'ai d'abord hésité parce que..." required><?php echo htmlspecialchars($editPost['explication'] ?? ''); ?></textarea>
                </div>
                
                <!-- R - Résultat -->
                <div class="mere-section" style="border-left: 4px solid #10b981;">
                    <div class="mere-header">
                        <span class="mere-letter" style="background: #10b981;">R</span>
                        <div>
                            <strong>RÉSULTAT</strong>
                            <span>La transformation, le bénéfice obtenu (2-3 lignes)</span>
                        </div>
                    </div>
                    <textarea name="resultat" id="resultatField" class="form-control" rows="3" placeholder="Ex: Résultat : vendu en 3 semaines, 15 000€ au-dessus du prix estimé par les autres agences. Le vendeur n'en revenait pas." required><?php echo htmlspecialchars($editPost['resultat'] ?? ''); ?></textarea>
                </div>
                
                <!-- E - Exercice -->
                <div class="mere-section" style="border-left: 4px solid #8b5cf6;">
                    <div class="mere-header">
                        <span class="mere-letter" style="background: #8b5cf6;">E</span>
                        <div>
                            <strong>EXERCICE</strong>
                            <span>L'engagement, la question qui fait réagir (1-2 lignes)</span>
                        </div>
                    </div>
                    <textarea name="exercice" id="exerciceField" class="form-control" rows="2" placeholder="Ex: Et vous, quelle a été votre meilleure décision immobilière ? 👇" required><?php echo htmlspecialchars($editPost['exercice'] ?? ''); ?></textarea>
                    <div class="mere-tips">
                        <button type="button" class="tip-btn" onclick="insertExercice('question')">❓ Question ouverte</button>
                        <button type="button" class="tip-btn" onclick="insertExercice('sondage')">📊 Sondage</button>
                        <button type="button" class="tip-btn" onclick="insertExercice('partage')">🔄 Demande de partage</button>
                        <button type="button" class="tip-btn" onclick="insertExercice('avis')">💬 Demande d'avis</button>
                    </div>
                </div>
            </div>

            <!-- Prévisualisation -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-eye" style="color: #1877f2;"></i> Prévisualisation du post complet
                </label>
                <div id="preview" style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; min-height: 150px;">
                    <div style="display: flex; gap: 12px; margin-bottom: 12px;">
                        <div style="width: 44px; height: 44px; background: #e2e8f0; border-radius: 50%;"></div>
                        <div>
                            <div style="font-weight: 600;">Votre Nom</div>
                            <div style="font-size: 12px; color: #64748b;">Maintenant · <i class="fas fa-globe-europe"></i></div>
                        </div>
                    </div>
                    <div id="previewContent" style="font-size: 14px; line-height: 1.6; white-space: pre-wrap;"></div>
                </div>
                <input type="hidden" name="full_content" id="fullContent">
            </div>

            <!-- Suggestion image -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-image" style="color: #10b981;"></i> Suggestion d'image/visuel
                </label>
                <textarea name="image_suggestion" class="form-control" rows="2" placeholder="Ex: Photo d'une belle maison vendue, selfie devant le panneau VENDU, image de vous avec les clients satisfaits..."><?php echo htmlspecialchars($editPost['image_suggestion'] ?? ''); ?></textarea>
                <p class="form-help">
                    💡 Conseil : Les posts avec image ont 2x plus d'engagement. Privilégiez les photos authentiques (vous, vos clients, vos biens).
                </p>
            </div>

            <!-- Planification -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date de publication prévue</label>
                    <input type="date" name="scheduled_date" class="form-control" value="<?php echo $editPost['scheduled_date'] ?? date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?php echo ($editPost && $editPost['status'] === 'draft') ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="planned" <?php echo ($editPost && $editPost['status'] === 'planned') ? 'selected' : ''; ?>>Planifié</option>
                        <option value="published" <?php echo ($editPost && $editPost['status'] === 'published') ? 'selected' : ''; ?>>Publié</option>
                    </select>
                </div>
            </div>

            <!-- Actions -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="?page=facebook&tab=journal" class="btn btn-secondary">Annuler</a>
                <button type="button" class="btn btn-secondary" onclick="copyToClipboard()">
                    <i class="fas fa-copy"></i> Copier le texte
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal IA -->
<div id="aiModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow: auto;">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-magic" style="color: #8b5cf6;"></i> Générer avec l'IA
            </h3>
        </div>
        <div style="padding: 20px;">
            <div class="form-group">
                <label class="form-label">Sujet de votre publication</label>
                <input type="text" id="aiSubject" class="form-control" placeholder="Ex: Une vente difficile que j'ai réussie, Un conseil pour les primo-accédants...">
            </div>
            <div class="form-group">
                <label class="form-label">Persona ciblé</label>
                <select id="aiPersona" class="form-control">
                    <option value="">-- Sélectionnez --</option>
                    <?php foreach ($personas as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (<?php echo $p['type']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Type de post</label>
                <select id="aiType" class="form-control">
                    <option value="attirer">Attirer (conseil, valeur)</option>
                    <option value="connecter">Connecter (histoire, coulisses)</option>
                    <option value="convertir">Convertir (offre, lead magnet)</option>
                </select>
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
.type-card {
    flex: 1;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.type-card:hover { border-color: #94a3b8; }
.type-card:has(input:checked) { border-color: var(--fb-blue); background: rgba(24,119,242,0.05); }
.type-card .type-icon { width: 36px; height: 36px; border-radius: 8px; margin: 0 auto 8px; display: flex; align-items: center; justify-content: center; }
.type-card strong { display: block; font-size: 13px; }
.type-card span { display: block; font-size: 10px; color: #64748b; margin-top: 4px; }

.mere-section {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}
.mere-section:last-child { margin-bottom: 0; }

.mere-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.mere-letter {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    flex-shrink: 0;
}
.mere-header strong { display: block; font-size: 13px; }
.mere-header span { display: block; font-size: 11px; color: #64748b; }

.mere-tips {
    display: flex;
    gap: 6px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.tip-btn {
    padding: 4px 10px;
    font-size: 11px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}
.tip-btn:hover { background: #f1f5f9; border-color: #94a3b8; }
</style>

<script>
// Mise à jour prévisualisation en temps réel
function updatePreview() {
    const motivation = document.getElementById('motivationField').value;
    const explication = document.getElementById('explicationField').value;
    const resultat = document.getElementById('resultatField').value;
    const exercice = document.getElementById('exerciceField').value;
    
    const fullText = [motivation, '', explication, '', resultat, '', exercice].filter(Boolean).join('\n');
    
    document.getElementById('previewContent').textContent = fullText;
    document.getElementById('fullContent').value = fullText;
}

// Écouter les changements
['motivationField', 'explicationField', 'resultatField', 'exerciceField'].forEach(id => {
    document.getElementById(id).addEventListener('input', updatePreview);
});

// Initial
updatePreview();

// Tips d'accroche
function insertTip(field, type) {
    const tips = {
        motivation: {
            question: "Savez-vous pourquoi 80% des vendeurs sous-estiment leur bien ?",
            choc: "J'ai perdu une vente à cause d'une erreur stupide. Et je ne veux pas que ça vous arrive.",
            contre: "Non, le prix n'est PAS le critère numéro 1 des acheteurs.",
            histoire: "Il y a 3 ans, j'ai reçu un appel qui a changé ma façon de travailler..."
        }
    };
    
    if (tips[field] && tips[field][type]) {
        document.getElementById(field + 'Field').value = tips[field][type];
        updatePreview();
    }
}

function insertExercice(type) {
    const exercices = {
        question: "Et vous, quelle a été votre plus grande surprise en immobilier ? 👇",
        sondage: "Vous êtes plutôt :\n🏠 Maison avec jardin\n🏢 Appartement en centre-ville\n\nDites-moi en commentaire !",
        partage: "Si ce conseil peut aider quelqu'un de votre entourage, partagez-lui ce post 🙏",
        avis: "Qu'est-ce que vous en pensez ? J'aimerais vraiment avoir votre avis 👇"
    };
    
    if (exercices[type]) {
        document.getElementById('exerciceField').value = exercices[type];
        updatePreview();
    }
}

// Copier dans le presse-papier
function copyToClipboard() {
    const text = document.getElementById('fullContent').value;
    navigator.clipboard.writeText(text).then(() => {
        alert('✅ Post copié ! Collez-le sur Facebook.');
    });
}

// Modal IA
function generateWithAI() {
    document.getElementById('aiModal').style.display = 'flex';
}

function closeAiModal() {
    document.getElementById('aiModal').style.display = 'none';
}

async function callAI() {
    const subject = document.getElementById('aiSubject').value;
    const persona = document.getElementById('aiPersona').value;
    const type = document.getElementById('aiType').value;
    
    if (!subject) {
        alert('Veuillez entrer un sujet');
        return;
    }
    
    // Ici on appellerait l'API IA
    alert('Fonctionnalité IA à connecter avec votre API (OpenAI, Anthropic...)');
    closeAiModal();
}
</script>