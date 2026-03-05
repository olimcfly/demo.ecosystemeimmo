<?php
/**
 * TAB: Banque d'Idées de Publications
 * Suggestions de sujets par Persona et par Type
 */

// Idées prédéfinies par type de persona et type de post
$ideasBank = [
    'acheteur' => [
        'attirer' => [
            ['title' => 'Les 5 erreurs qui font perdre un bien', 'desc' => 'Erreurs classiques des acheteurs qui ratent leur coup de cœur'],
            ['title' => 'Le vrai coût d\'un achat immobilier', 'desc' => 'Frais de notaire, travaux, charges... Ce qu\'on oublie souvent'],
            ['title' => 'Acheter avant de vendre : bonne idée ?', 'desc' => 'Analyse des avantages et risques de cette stratégie'],
            ['title' => 'Négocier le prix : mes techniques', 'desc' => 'Comment j\'aide mes clients à obtenir le meilleur prix'],
            ['title' => 'Visiter un bien : les 10 points à vérifier', 'desc' => 'Check-list des éléments à ne pas oublier'],
            ['title' => 'Crédit immobilier : les banques resserrent', 'desc' => 'Ce que vous devez savoir sur les conditions actuelles'],
            ['title' => 'Primo-accédants : le guide de survie', 'desc' => 'Conseils pour un premier achat réussi'],
            ['title' => 'Coup de cœur vs Raison : comment choisir', 'desc' => 'Trouver l\'équilibre entre émotion et objectivité'],
        ],
        'connecter' => [
            ['title' => 'Mon premier achat immobilier', 'desc' => 'Racontez votre propre expérience d\'acheteur'],
            ['title' => 'Ce client qui a failli tout annuler', 'desc' => 'Histoire d\'un achat compliqué avec happy end'],
            ['title' => 'Pourquoi j\'adore accompagner les primo-accédants', 'desc' => 'Votre motivation à aider cette cible'],
            ['title' => 'Les coulisses d\'une recherche de bien', 'desc' => 'Ce que vous faites vraiment pour vos clients'],
            ['title' => 'Témoignage : la famille X a trouvé son bonheur', 'desc' => 'Retour d\'expérience client (avec autorisation)'],
            ['title' => 'Ma plus belle vente côté acheteur', 'desc' => 'Une histoire qui vous a marqué'],
        ],
        'convertir' => [
            ['title' => 'Votre recherche offerte', 'desc' => 'Proposez une recherche personnalisée gratuite'],
            ['title' => 'Guide gratuit : Réussir son achat', 'desc' => 'Offrez un lead magnet téléchargeable'],
            ['title' => 'Webinaire : Les secrets des acheteurs malins', 'desc' => 'Invitez à un événement en ligne'],
            ['title' => 'Nouveau bien exclusif', 'desc' => 'Présentez un bien avant tout le monde'],
        ],
    ],
    'vendeur' => [
        'attirer' => [
            ['title' => 'Estimer son bien : les 3 méthodes', 'desc' => 'Comparative, par le revenu, par le coût...'],
            ['title' => 'Vendre seul vs avec un agent : le vrai calcul', 'desc' => 'Démontrez la valeur ajoutée d\'un pro'],
            ['title' => 'Les 7 erreurs qui font fuir les acheteurs', 'desc' => 'Ce qui tue une annonce immobilière'],
            ['title' => 'Home staging : mythe ou réalité ?', 'desc' => 'L\'impact réel sur le prix et les délais'],
            ['title' => 'Le bon prix : ni trop haut, ni trop bas', 'desc' => 'L\'art de positionner un bien correctement'],
            ['title' => 'Délai de vente : ce qui fait vraiment la différence', 'desc' => 'Les facteurs qui accélèrent ou ralentissent'],
            ['title' => 'Diagnostics immobiliers : le guide complet', 'desc' => 'Ce qu\'il faut savoir avant de vendre'],
            ['title' => 'Mandat exclusif vs simple : mon avis tranché', 'desc' => 'Expliquez pourquoi l\'exclusif est gagnant'],
        ],
        'connecter' => [
            ['title' => 'Cette vente que j\'ai refusée', 'desc' => 'Pourquoi vous avez dit non à un mandat'],
            ['title' => 'Record : vendu en 48h', 'desc' => 'L\'histoire d\'une vente éclair'],
            ['title' => '8 mois sur le marché, 3 semaines avec moi', 'desc' => 'Comment vous avez sauvé une vente'],
            ['title' => 'Pourquoi je limite mon portefeuille', 'desc' => 'Votre philosophie qualité vs quantité'],
            ['title' => 'Témoignage : M. et Mme X ont vendu sereinement', 'desc' => 'Retour d\'expérience vendeur'],
            ['title' => 'Les coulisses d\'une estimation', 'desc' => 'Ce que vous analysez vraiment'],
        ],
        'convertir' => [
            ['title' => 'Estimation gratuite et sans engagement', 'desc' => 'L\'offre classique qui marche toujours'],
            ['title' => 'Audit de votre annonce actuelle', 'desc' => 'Proposez d\'analyser leur annonce existante'],
            ['title' => 'Guide : Préparer sa vente en 7 étapes', 'desc' => 'Lead magnet pour vendeurs'],
            ['title' => 'Votre bien mérite mieux', 'desc' => 'Cibler ceux qui sont déçus par leur agence'],
        ],
    ],
];

// Idées personnalisées stockées en BDD
$customIdeas = [];
try {
    $customIdeas = $pdo->query("SELECT * FROM facebook_ideas ORDER BY persona_type, post_type, title")->fetchAll();
} catch (Exception $e) {}
?>

<div class="info-box" style="background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(251,191,36,0.1)); border-color: rgba(245,158,11,0.2);">
    <div class="icon-box" style="background: white;"><i class="fas fa-lightbulb" style="color: #f59e0b;"></i></div>
    <div style="flex: 1;">
        <h4>En panne d'inspiration ?</h4>
        <p>
            Voici des idées de sujets classés par <strong>persona</strong> (acheteur/vendeur) et par <strong>type de post</strong> (attirer/connecter/convertir).
            Cliquez sur une idée pour commencer à rédiger !
        </p>
    </div>
</div>

<!-- Tabs Acheteur / Vendeur -->
<div style="display: flex; gap: 8px; margin-bottom: 24px;">
    <button class="persona-tab active" data-target="acheteur" style="background: #dbeafe; color: #1e40af; border: 2px solid #3b82f6;">
        🏠 Personas Acheteurs
    </button>
    <button class="persona-tab" data-target="vendeur" style="background: #fce7f3; color: #9d174d; border: 2px solid transparent;">
        🔑 Personas Vendeurs
    </button>
</div>

<!-- Contenu Acheteurs -->
<div id="content-acheteur" class="persona-content">
    <?php foreach (['attirer', 'connecter', 'convertir'] as $type): 
        $typeInfo = [
            'attirer' => ['icon' => 'magnet', 'color' => '#10b981', 'label' => 'ATTIRER', 'ratio' => '60%'],
            'connecter' => ['icon' => 'heart', 'color' => '#f59e0b', 'label' => 'CONNECTER', 'ratio' => '30%'],
            'convertir' => ['icon' => 'bullseye', 'color' => '#8b5cf6', 'label' => 'CONVERTIR', 'ratio' => '10%'],
        ][$type];
    ?>
    <div class="ideas-section" style="border-left: 4px solid <?php echo $typeInfo['color']; ?>;">
        <div class="ideas-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: <?php echo $typeInfo['color']; ?>22; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-<?php echo $typeInfo['icon']; ?>" style="color: <?php echo $typeInfo['color']; ?>;"></i>
                </div>
                <div>
                    <strong style="color: <?php echo $typeInfo['color']; ?>;"><?php echo $typeInfo['label']; ?></strong>
                    <span style="font-size: 11px; color: #64748b; margin-left: 8px;"><?php echo $typeInfo['ratio']; ?> de vos posts</span>
                </div>
            </div>
            <span style="font-size: 12px; color: #64748b;"><?php echo count($ideasBank['acheteur'][$type]); ?> idées</span>
        </div>
        <div class="ideas-grid">
            <?php foreach ($ideasBank['acheteur'][$type] as $idea): ?>
            <div class="idea-card" onclick="useIdea('<?php echo addslashes($idea['title']); ?>', 'acheteur', '<?php echo $type; ?>')">
                <h4><?php echo htmlspecialchars($idea['title']); ?></h4>
                <p><?php echo htmlspecialchars($idea['desc']); ?></p>
                <button class="use-btn">
                    <i class="fas fa-pen"></i> Utiliser
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Contenu Vendeurs -->
<div id="content-vendeur" class="persona-content" style="display: none;">
    <?php foreach (['attirer', 'connecter', 'convertir'] as $type): 
        $typeInfo = [
            'attirer' => ['icon' => 'magnet', 'color' => '#10b981', 'label' => 'ATTIRER', 'ratio' => '60%'],
            'connecter' => ['icon' => 'heart', 'color' => '#f59e0b', 'label' => 'CONNECTER', 'ratio' => '30%'],
            'convertir' => ['icon' => 'bullseye', 'color' => '#8b5cf6', 'label' => 'CONVERTIR', 'ratio' => '10%'],
        ][$type];
    ?>
    <div class="ideas-section" style="border-left: 4px solid <?php echo $typeInfo['color']; ?>;">
        <div class="ideas-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: <?php echo $typeInfo['color']; ?>22; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-<?php echo $typeInfo['icon']; ?>" style="color: <?php echo $typeInfo['color']; ?>;"></i>
                </div>
                <div>
                    <strong style="color: <?php echo $typeInfo['color']; ?>;"><?php echo $typeInfo['label']; ?></strong>
                    <span style="font-size: 11px; color: #64748b; margin-left: 8px;"><?php echo $typeInfo['ratio']; ?> de vos posts</span>
                </div>
            </div>
            <span style="font-size: 12px; color: #64748b;"><?php echo count($ideasBank['vendeur'][$type]); ?> idées</span>
        </div>
        <div class="ideas-grid">
            <?php foreach ($ideasBank['vendeur'][$type] as $idea): ?>
            <div class="idea-card" onclick="useIdea('<?php echo addslashes($idea['title']); ?>', 'vendeur', '<?php echo $type; ?>')">
                <h4><?php echo htmlspecialchars($idea['title']); ?></h4>
                <p><?php echo htmlspecialchars($idea['desc']); ?></p>
                <button class="use-btn">
                    <i class="fas fa-pen"></i> Utiliser
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Créer sa propre idée -->
<div style="margin-top: 32px; padding: 24px; background: #f8fafc; border-radius: 12px; text-align: center;">
    <h4 style="margin: 0 0 8px 0;">Vous avez une idée originale ?</h4>
    <p style="color: #64748b; margin: 0 0 16px 0;">Enregistrez-la dans votre banque personnelle pour ne pas l'oublier</p>
    <button class="btn btn-secondary" onclick="showAddIdea()">
        <i class="fas fa-plus"></i> Ajouter une idée
    </button>
</div>

<!-- Modal Ajouter idée -->
<div id="addIdeaModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 450px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
            <h3 style="margin: 0;"><i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Nouvelle idée de sujet</h3>
        </div>
        <form id="addIdeaForm" style="padding: 20px;">
            <div class="form-group">
                <label class="form-label">Titre du sujet</label>
                <input type="text" name="title" class="form-control" placeholder="Ex: Mon secret pour des photos qui vendent" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Persona</label>
                    <select name="persona_type" class="form-control">
                        <option value="acheteur">🏠 Acheteur</option>
                        <option value="vendeur">🔑 Vendeur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="post_type" class="form-control">
                        <option value="attirer">Attirer</option>
                        <option value="connecter">Connecter</option>
                        <option value="convertir">Convertir</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Description (optionnel)</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Notes sur le contenu..."></textarea>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeAddIdea()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.persona-tab {
    padding: 12px 24px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}
.persona-tab:not(.active) { opacity: 0.7; }
.persona-tab:hover { opacity: 1; }

.ideas-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.ideas-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.ideas-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}

.idea-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    cursor: pointer;
    transition: all 0.2s;
    position: relative;
}

.idea-card:hover {
    border-color: #1877f2;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24,119,242,0.15);
}

.idea-card h4 {
    font-size: 13px;
    font-weight: 600;
    margin: 0 0 6px 0;
    color: #1e293b;
}

.idea-card p {
    font-size: 11px;
    color: #64748b;
    margin: 0;
    line-height: 1.5;
}

.idea-card .use-btn {
    display: none;
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: #1877f2;
    color: white;
    border: none;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.idea-card:hover .use-btn { display: flex; align-items: center; gap: 4px; }

@media (max-width: 1200px) {
    .ideas-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
    .ideas-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<script>
// Toggle tabs
document.querySelectorAll('.persona-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.persona-tab').forEach(t => {
            t.classList.remove('active');
            t.style.borderColor = 'transparent';
        });
        this.classList.add('active');
        this.style.borderColor = this.dataset.target === 'acheteur' ? '#3b82f6' : '#ec4899';
        
        document.querySelectorAll('.persona-content').forEach(c => c.style.display = 'none');
        document.getElementById('content-' + this.dataset.target).style.display = 'block';
    });
});

// Utiliser une idée
function useIdea(title, personaType, postType) {
    // Stocker en sessionStorage et rediriger vers rédacteur
    sessionStorage.setItem('fb_idea', JSON.stringify({title, personaType, postType}));
    window.location.href = '?page=facebook&tab=rediger';
}

// Modal ajouter idée
function showAddIdea() {
    document.getElementById('addIdeaModal').style.display = 'flex';
}

function closeAddIdea() {
    document.getElementById('addIdeaModal').style.display = 'none';
}

document.getElementById('addIdeaForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const result = await fetch('api/facebook/save-idea.php', {
            method: 'POST',
            body: formData
        }).then(r => r.json());
        
        if (result.success) {
            closeAddIdea();
            location.reload();
        }
    } catch (error) {
        alert('Erreur lors de l\'enregistrement');
    }
});
</script>