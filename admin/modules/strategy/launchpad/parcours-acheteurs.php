<?php
/**
 * PARCOURS B — ACHETEURS SOLVABLES
 * /admin/modules/launchpad/parcours-acheteurs.php
 * 
 * 5 Étapes pour capter et qualifier des acheteurs solvables
 * Accessible via ?page=parcours-acheteurs
 */

// Connexion DB
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Erreur de connexion à la base de données</div>';
    return;
}

$user_id = $_SESSION['admin_id'] ?? 1;
$current_step = isset($_GET['etape']) ? intval($_GET['etape']) : 0;

// ══════════════════════════════════════════════
// RÉCUPÉRER LA PROGRESSION DE L'UTILISATEUR
// ══════════════════════════════════════════════
$progression = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM parcours_progression WHERE user_id = ? AND parcours_id = 'B'");
    $stmt->execute([$user_id]);
    $progression = $stmt->fetch() ?: [];
} catch (Exception $e) {
    // Table pas encore créée — on gère silencieusement
}

$completed_steps = [];
if (!empty($progression['completed_steps'])) {
    $completed_steps = json_decode($progression['completed_steps'], true) ?: [];
}

// ══════════════════════════════════════════════
// SAUVEGARDE AJAX
// ══════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $step_num = intval($input['step'] ?? 0);
    $action_id = $input['action_id'] ?? '';
    $status = $input['status'] ?? 'done';
    
    if ($step_num > 0 && $action_id) {
        if (!in_array($action_id, $completed_steps)) {
            $completed_steps[] = $action_id;
        }
        
        $json_steps = json_encode($completed_steps);
        
        try {
            // Créer la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS parcours_progression (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                parcours_id VARCHAR(5) NOT NULL DEFAULT 'B',
                completed_steps JSON,
                current_step INT DEFAULT 1,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_parcours (user_id, parcours_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $pdo->prepare("INSERT INTO parcours_progression (user_id, parcours_id, completed_steps, current_step) 
                VALUES (?, 'B', ?, ?)
                ON DUPLICATE KEY UPDATE completed_steps = VALUES(completed_steps), current_step = VALUES(current_step), updated_at = NOW()");
            $stmt->execute([$user_id, $json_steps, $step_num]);
            
            echo json_encode(['success' => true, 'completed' => count($completed_steps)]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    }
    exit;
}

// ══════════════════════════════════════════════
// DÉFINITION DES 5 ÉTAPES
// ══════════════════════════════════════════════
$etapes = [
    1 => [
        'title' => 'Persona Acheteur Idéal',
        'emoji' => '🎯',
        'subtitle' => 'Définir précisément qui est votre acheteur solvable',
        'duration' => '45 min',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'description' => 'Avant de capter des acheteurs, vous devez savoir exactement QUI vous cherchez. Un acheteur solvable n\'est pas juste "quelqu\'un qui veut acheter" — c\'est un profil précis avec un budget validé, un projet clair et un délai défini.',
        'actions' => [
            [
                'id' => 'B1-1',
                'title' => 'Identifier les 3 profils d\'acheteurs de votre zone',
                'description' => 'Primo-accédant local, Investisseur, Secundo-accédant. Pour chaque profil : budget moyen, type de bien recherché, zone préférée.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Regardez vos 10 dernières ventes : quels profils revenaient le plus ?'
            ],
            [
                'id' => 'B1-2',
                'title' => 'Créer la fiche persona "Acheteur Solvable #1"',
                'description' => 'Utilisez le module NeuroPersona pour créer votre persona acheteur prioritaire avec ses douleurs, désirs et déclencheurs d\'achat.',
                'type' => 'action',
                'module_link' => '?page=neuropersona',
                'tips' => 'Concentrez-vous sur le profil qui achète le plus dans votre zone — pas le plus rentable, le plus fréquent.'
            ],
            [
                'id' => 'B1-3',
                'title' => 'Définir les critères de solvabilité',
                'description' => 'Listez les signaux qui indiquent qu\'un acheteur est solvable : apport minimum, pré-accord bancaire, profession stable, capacité d\'emprunt validée.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Un acheteur "motivé" sans financement = perte de temps. Mieux vaut 5 acheteurs qualifiés que 50 touristes immobiliers.'
            ],
            [
                'id' => 'B1-4',
                'title' => 'Cartographier le parcours d\'achat',
                'description' => 'De la première recherche à la signature : quelles étapes traverse votre acheteur ? À quel moment intervenir ?',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Les moments clés : début de recherche (rêve), visite du courtier (réalité budget), premières visites (comparaison), coup de cœur (décision).'
            ]
        ]
    ],
    2 => [
        'title' => 'Formulaire de Qualification',
        'emoji' => '📋',
        'subtitle' => 'Créer un système de capture qui trie automatiquement',
        'duration' => '1h30',
        'color' => '#3b82f6',
        'gradient' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
        'description' => 'Le formulaire de qualification est votre premier filtre. Il doit récupérer les informations essentielles pour déterminer si l\'acheteur est solvable AVANT de lui consacrer du temps.',
        'actions' => [
            [
                'id' => 'B2-1',
                'title' => 'Créer le formulaire "Mon Projet Immobilier"',
                'description' => 'Formulaire de capture avec les champs essentiels : type de bien, budget, zone, délai, situation financière (apport, pré-accord), coordonnées.',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'Maximum 8-10 champs. Trop long = abandon. Le budget et le délai sont les 2 champs les plus importants.'
            ],
            [
                'id' => 'B2-2',
                'title' => 'Créer la page de capture "Votre Projet Achat"',
                'description' => 'Landing page dédiée avec la promesse : "Recevez en avant-première les biens correspondant à votre recherche". Le formulaire est intégré dedans.',
                'type' => 'action',
                'module_link' => '?page=pages-capture&action=create',
                'tips' => 'La promesse doit résoudre un problème réel : "Ne ratez plus les bonnes affaires" ou "Accédez aux biens avant tout le monde".'
            ],
            [
                'id' => 'B2-3',
                'title' => 'Configurer le scoring automatique des leads',
                'description' => 'Attribuez des points selon les réponses : budget > 200K = +3pts, pré-accord bancaire = +5pts, délai < 3 mois = +3pts, apport > 10% = +2pts.',
                'type' => 'action',
                'module_link' => '?page=leads',
                'tips' => 'Score ≥ 10 = Lead chaud (appeler dans l\'heure). Score 5-9 = Tiède (nurturing). Score < 5 = Froid (séquence longue).'
            ],
            [
                'id' => 'B2-4',
                'title' => 'Créer le lead magnet "Guide de l\'Acheteur"',
                'description' => 'Un PDF de 10-15 pages : "Les 7 erreurs qui font perdre 20 000€ aux acheteurs" ou "Checklist complète pour acheter sereinement". Offert en échange du formulaire.',
                'type' => 'action',
                'module_link' => '?page=ressources',
                'tips' => 'Le guide doit apporter de la vraie valeur ET positionner votre expertise. Pas de pub déguisée.'
            ],
            [
                'id' => 'B2-5',
                'title' => 'Tester le parcours complet',
                'description' => 'Faites le parcours vous-même : arrivée sur la page → formulaire → email de confirmation → lead dans le CRM. Vérifiez que tout fonctionne.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Demandez aussi à 2-3 proches de tester et notez les frictions.'
            ]
        ]
    ],
    3 => [
        'title' => 'Partenariat Courtier',
        'emoji' => '🤝',
        'subtitle' => 'Mettre en place un workflow de qualification financière',
        'duration' => '2h + rendez-vous',
        'color' => '#8b5cf6',
        'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'description' => 'Le courtier est votre meilleur allié pour qualifier la solvabilité. Un acheteur qui a vu un courtier = acheteur sérieux avec un budget validé. Mettez en place un partenariat gagnant-gagnant.',
        'actions' => [
            [
                'id' => 'B3-1',
                'title' => 'Identifier 2-3 courtiers partenaires potentiels',
                'description' => 'Cherchez des courtiers locaux réactifs et bien notés. Critères : rapidité de réponse, taux d\'obtention crédit, disponibilité pour vos clients.',
                'type' => 'action',
                'module_link' => '?page=contact',
                'tips' => 'Regardez sur Google Maps les courtiers avec 4.5+ étoiles dans votre zone. Testez-en 3 en les appelant comme un client.'
            ],
            [
                'id' => 'B3-2',
                'title' => 'Préparer la proposition de partenariat',
                'description' => 'Créez un document simple : "Je vous envoie X acheteurs/mois, vous me confirmez leur capacité d\'emprunt sous 48h". Définir le process : qui appelle qui, quand, comment.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Le courtier gagne des clients sans prospection. Vous gagnez la qualification financière gratuite. Win-win.'
            ],
            [
                'id' => 'B3-3',
                'title' => 'Créer le workflow CRM "Qualification Courtier"',
                'description' => 'Dans le pipeline CRM : Nouveau lead → Formulaire rempli → Envoyé au courtier → Retour courtier (Solvable/Non solvable) → RDV visite ou Nurturing.',
                'type' => 'action',
                'module_link' => '?page=crm-pipeline',
                'tips' => 'Ajoutez un statut "En attente courtier" dans votre pipeline pour ne perdre aucun lead en route.'
            ],
            [
                'id' => 'B3-4',
                'title' => 'Rédiger l\'email d\'introduction courtier',
                'description' => 'Template d\'email que vous envoyez au courtier avec chaque nouveau lead qualifié : nom, budget estimé, projet, délai, coordonnées.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Soyez concis. Le courtier a besoin de : nom + téléphone + budget estimé + délai. Pas plus.'
            ],
            [
                'id' => 'B3-5',
                'title' => 'Décrocher le premier RDV courtier',
                'description' => 'Appelez votre courtier #1, proposez un café pour expliquer le partenariat. Objectif : signer un accord de principe oral.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Préparez 2-3 leads récents à lui présenter comme preuve de volume. Même fictifs au début, ça montre le potentiel.'
            ]
        ]
    ],
    4 => [
        'title' => 'Nurturing Automatisé',
        'emoji' => '📧',
        'subtitle' => 'Séquences email + alertes biens pour garder le lien',
        'duration' => '2h',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'description' => 'Un acheteur met en moyenne 3 à 6 mois pour acheter. Vous devez rester dans son esprit pendant toute cette période avec des contenus utiles et des alertes biens pertinentes.',
        'actions' => [
            [
                'id' => 'B4-1',
                'title' => 'Créer la séquence "Bienvenue Acheteur" (5 emails)',
                'description' => 'Email 1 (J+0) : Confirmation + guide PDF. Email 2 (J+2) : "Les 3 pièges à éviter". Email 3 (J+5) : Témoignage client. Email 4 (J+8) : "Comment bien visiter". Email 5 (J+12) : Proposition de RDV découverte.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Chaque email doit apporter de la valeur AVANT de demander quoi que ce soit. La confiance se construit en donnant d\'abord.'
            ],
            [
                'id' => 'B4-2',
                'title' => 'Configurer les alertes biens automatiques',
                'description' => 'Quand un nouveau bien entre dans votre stock et correspond aux critères d\'un acheteur enregistré → email automatique avec fiche du bien + lien de prise de RDV.',
                'type' => 'action',
                'module_link' => '?page=biens',
                'tips' => 'L\'alerte doit être personnalisée : "Bonjour [Prénom], un bien correspondant à votre recherche vient d\'arriver à [Quartier]".'
            ],
            [
                'id' => 'B4-3',
                'title' => 'Créer la séquence "Nurturing Long" (1 email/semaine)',
                'description' => 'Newsletter hebdomadaire pendant 3 mois : actualités marché local, nouveaux biens, conseils achat, évolution des taux, témoignages.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Alternez entre contenu éducatif (60%), biens (25%) et social proof (15%). Jamais 100% commercial.'
            ],
            [
                'id' => 'B4-4',
                'title' => 'Configurer les relances automatiques',
                'description' => 'Lead sans activité depuis 7 jours → relance "Où en êtes-vous ?". Sans activité 30 jours → relance "Votre projet est-il toujours d\'actualité ?". Sans activité 60 jours → archivage.',
                'type' => 'action',
                'module_link' => '?page=crm',
                'tips' => 'Les relances doivent être humaines, pas robotiques. Posez une vraie question, ne poussez pas à la vente.'
            ],
            [
                'id' => 'B4-5',
                'title' => 'Tester la séquence complète',
                'description' => 'Inscrivez-vous avec votre propre email, vérifiez que chaque email arrive au bon moment avec le bon contenu. Corrigez les erreurs.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Vérifiez aussi le rendu mobile — 70% des emails immobiliers sont lus sur smartphone.'
            ]
        ]
    ],
    5 => [
        'title' => 'Pages Secteurs & SEO',
        'emoji' => '🏘️',
        'subtitle' => 'Créer des pages quartiers pour attirer les acheteurs en recherche',
        'duration' => '3h',
        'color' => '#06b6d4',
        'gradient' => 'linear-gradient(135deg, #06b6d4, #0891b2)',
        'description' => 'Les acheteurs cherchent sur Google "acheter maison + [quartier]" ou "immobilier + [ville]". Vos pages secteurs/quartiers captent ce trafic organique et transforment les visiteurs en leads qualifiés.',
        'actions' => [
            [
                'id' => 'B5-1',
                'title' => 'Lister les 5 quartiers/secteurs prioritaires',
                'description' => 'Identifiez les 5 zones où vous avez le plus de biens et/ou le plus de demande. Classez-les par potentiel de trafic.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Cherchez sur Google "immobilier [quartier]" : si les résultats sont faibles, c\'est une opportunité SEO à saisir.'
            ],
            [
                'id' => 'B5-2',
                'title' => 'Créer la première page secteur avec le Builder',
                'description' => 'Utilisez le Builder pour créer une page quartier complète : présentation du quartier, prix au m², types de biens disponibles, commodités, témoignages, formulaire de recherche intégré.',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Structure recommandée : Hero + Pourquoi ce quartier + Prix + Commodités + Biens disponibles + CTA formulaire.'
            ],
            [
                'id' => 'B5-3',
                'title' => 'Optimiser le SEO de chaque page secteur',
                'description' => 'Titre H1 : "Immobilier [Quartier] — Acheter à [Ville]". Meta description. Mots-clés locaux. Liens internes vers vos biens et votre page de capture.',
                'type' => 'action',
                'module_link' => '?page=seo',
                'tips' => 'Mots-clés cibles : "acheter [quartier]", "prix m2 [quartier]", "immobilier [quartier] [ville]", "maison à vendre [quartier]".'
            ],
            [
                'id' => 'B5-4',
                'title' => 'Intégrer le CTA acheteur sur chaque page',
                'description' => 'Chaque page secteur doit avoir un CTA clair vers votre formulaire de qualification : "Vous cherchez un bien dans ce quartier ? Décrivez votre projet →".',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'Placez le CTA en haut (dans le hero), au milieu (après les prix) et en bas (après les commodités). 3 chances de conversion.'
            ],
            [
                'id' => 'B5-5',
                'title' => 'Publier les 3 premières pages secteurs',
                'description' => 'Publiez vos 3 premières pages, partagez-les sur vos réseaux sociaux, et ajoutez les liens dans votre fiche Google My Business.',
                'type' => 'validation',
                'module_link' => '?page=builder-pages',
                'tips' => 'Google met 2-4 semaines à indexer. Publiez vite et améliorez après — le contenu parfait qui n\'est jamais publié ne génère zéro lead.'
            ]
        ]
    ]
];

// Calcul de la progression globale
$total_actions = 0;
$done_actions = 0;
foreach ($etapes as $e) {
    foreach ($e['actions'] as $a) {
        $total_actions++;
        if (in_array($a['id'], $completed_steps)) {
            $done_actions++;
        }
    }
}
$progress_pct = $total_actions > 0 ? round(($done_actions / $total_actions) * 100) : 0;
?>

<style>
/* ══════════════════════════════════════════════
   PARCOURS B — ACHETEURS SOLVABLES
   ══════════════════════════════════════════════ */

.parcours-b-container {
    max-width: 900px;
    margin: 0 auto;
}

/* Header du parcours */
.parcours-hero {
    background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 30px;
    position: relative;
    overflow: hidden;
}

.parcours-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.08);
    border-radius: 50%;
}

.parcours-hero::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 200px;
    height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.parcours-hero-content {
    position: relative;
    z-index: 1;
}

.parcours-hero h1 {
    font-size: 28px;
    font-weight: 800;
    margin: 0 0 8px 0;
}

.parcours-hero .subtitle {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 24px;
}

/* Barre de progression */
.progress-bar-container {
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    height: 12px;
    overflow: hidden;
    margin-bottom: 12px;
}

.progress-bar-fill {
    height: 100%;
    background: white;
    border-radius: 10px;
    transition: width 0.5s ease;
}

.progress-stats {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    opacity: 0.9;
}

/* Navigation des étapes */
.etapes-nav {
    display: flex;
    gap: 8px;
    margin-bottom: 30px;
    flex-wrap: wrap;
}

.etape-nav-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
}

.etape-nav-btn:hover {
    border-color: #10b981;
    color: #10b981;
    transform: translateY(-1px);
}

.etape-nav-btn.active {
    border-color: #10b981;
    background: #ecfdf5;
    color: #059669;
}

.etape-nav-btn.completed {
    border-color: #10b981;
    background: #10b981;
    color: white;
}

.etape-nav-emoji {
    font-size: 16px;
}

.etape-nav-check {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

/* Vue d'ensemble (étape 0) */
.overview-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 30px;
}

.overview-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: inherit;
    display: block;
}

.overview-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: #10b981;
}

.overview-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
}

.overview-card-emoji {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.overview-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
}

.overview-card-duration {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

.overview-card-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 14px;
}

.overview-card-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.overview-card-progress-bar {
    flex: 1;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.overview-card-progress-fill {
    height: 100%;
    background: #10b981;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.overview-card-progress-text {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
}

/* Détail d'une étape */
.etape-detail {
    background: white;
    border-radius: 16px;
    padding: 30px;
    border: 1px solid #e2e8f0;
}

.etape-detail-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 10px;
}

.etape-detail-emoji {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
    flex-shrink: 0;
}

.etape-detail-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.etape-detail-header .etape-subtitle {
    font-size: 14px;
    color: #64748b;
    margin-top: 4px;
}

.etape-detail-desc {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0 25px;
    font-size: 14px;
    color: #475569;
    line-height: 1.7;
    border-left: 4px solid #10b981;
}

/* Actions / Tâches */
.action-card {
    background: #fafbfc;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.3s ease;
}

.action-card:hover {
    border-color: #10b981;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
}

.action-card.done {
    background: #f0fdf4;
    border-color: #86efac;
}

.action-card.done .action-title {
    text-decoration: line-through;
    color: #6b7280;
}

.action-header {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.action-check {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    border: 2px solid #d1d5db;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    flex-shrink: 0;
    margin-top: 2px;
    font-size: 14px;
    color: transparent;
}

.action-check:hover {
    border-color: #10b981;
    background: #ecfdf5;
}

.action-check.checked {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.action-content {
    flex: 1;
}

.action-title {
    font-size: 15px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 6px;
}

.action-type-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

.action-type-badge.reflexion {
    background: #fef3c7;
    color: #92400e;
}

.action-type-badge.action {
    background: #dbeafe;
    color: #1e40af;
}

.action-type-badge.validation {
    background: #d1fae5;
    color: #065f46;
}

.action-desc {
    font-size: 13px;
    color: #64748b;
    line-height: 1.6;
    margin-bottom: 10px;
}

.action-tips {
    background: white;
    border-radius: 8px;
    padding: 12px 14px;
    font-size: 12px;
    color: #6b7280;
    border: 1px dashed #e2e8f0;
    line-height: 1.5;
}

.action-tips::before {
    content: '💡 ';
}

.action-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 6px 14px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

/* Navigation bas de page */
.etape-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.etape-footer .btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.btn-back {
    background: white;
    border: 1px solid #e2e8f0 !important;
    color: #64748b;
}

.btn-back:hover {
    background: #f8fafc;
}

.btn-next {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-next:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Quick wins récap */
.quick-wins-recap {
    background: linear-gradient(135deg, #fefce8, #fef9c3);
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 20px;
    margin-top: 30px;
}

.quick-wins-recap h4 {
    font-size: 14px;
    font-weight: 700;
    color: #92400e;
    margin: 0 0 12px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-wins-recap ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.quick-wins-recap li {
    padding: 6px 0;
    font-size: 13px;
    color: #78716c;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-wins-recap li::before {
    content: '⚡';
}

/* Responsive */
@media (max-width: 768px) {
    .parcours-hero { padding: 24px; }
    .parcours-hero h1 { font-size: 22px; }
    .overview-grid { grid-template-columns: 1fr; }
    .etapes-nav { flex-direction: column; }
    .etape-footer { flex-direction: column; gap: 10px; }
}
</style>

<div class="parcours-b-container">

    <!-- ══════════════════════════════════════════════
         HEADER HERO
         ══════════════════════════════════════════════ -->
    <div class="parcours-hero">
        <div class="parcours-hero-content">
            <h1>💰 Parcours B — Acheteurs Solvables</h1>
            <p class="subtitle">Captez des acheteurs qualifiés, vérifiez leur solvabilité et transformez-les en clients</p>
            
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: <?= $progress_pct ?>%"></div>
            </div>
            <div class="progress-stats">
                <span><?= $done_actions ?> / <?= $total_actions ?> actions complétées</span>
                <span><?= $progress_pct ?>%</span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════
         NAVIGATION DES ÉTAPES
         ══════════════════════════════════════════════ -->
    <div class="etapes-nav">
        <a href="?page=parcours-acheteurs" 
           class="etape-nav-btn <?= $current_step === 0 ? 'active' : '' ?>">
            <span class="etape-nav-emoji">📊</span> Vue d'ensemble
        </a>
        <?php foreach ($etapes as $num => $etape): 
            $etape_actions = array_column($etape['actions'], 'id');
            $etape_done = count(array_intersect($etape_actions, $completed_steps));
            $etape_total = count($etape_actions);
            $all_done = ($etape_done === $etape_total && $etape_total > 0);
            $is_active = ($current_step === $num);
        ?>
        <a href="?page=parcours-acheteurs&etape=<?= $num ?>" 
           class="etape-nav-btn <?= $is_active ? 'active' : '' ?> <?= $all_done ? 'completed' : '' ?>">
            <span class="etape-nav-emoji"><?= $etape['emoji'] ?></span>
            Étape <?= $num ?>
            <?php if ($all_done): ?>
            <span class="etape-nav-check">✓</span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($current_step === 0): ?>
    <!-- ══════════════════════════════════════════════
         VUE D'ENSEMBLE
         ══════════════════════════════════════════════ -->
    <div class="overview-grid">
        <?php foreach ($etapes as $num => $etape): 
            $etape_actions = array_column($etape['actions'], 'id');
            $etape_done = count(array_intersect($etape_actions, $completed_steps));
            $etape_total = count($etape_actions);
            $etape_pct = $etape_total > 0 ? round(($etape_done / $etape_total) * 100) : 0;
        ?>
        <a href="?page=parcours-acheteurs&etape=<?= $num ?>" class="overview-card">
            <div class="overview-card-header">
                <div class="overview-card-emoji" style="background: <?= $etape['gradient'] ?>">
                    <?= $etape['emoji'] ?>
                </div>
                <div>
                    <div class="overview-card-title">Étape <?= $num ?> — <?= $etape['title'] ?></div>
                    <div class="overview-card-duration">⏱ <?= $etape['duration'] ?> • <?= $etape_total ?> actions</div>
                </div>
            </div>
            <div class="overview-card-desc"><?= $etape['subtitle'] ?></div>
            <div class="overview-card-progress">
                <div class="overview-card-progress-bar">
                    <div class="overview-card-progress-fill" style="width: <?= $etape_pct ?>%"></div>
                </div>
                <span class="overview-card-progress-text"><?= $etape_done ?>/<?= $etape_total ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Quick Wins récap -->
    <div class="quick-wins-recap">
        <h4>⚡ Quick Wins — À faire en premier</h4>
        <ul>
            <li>Créer votre formulaire "Mon Projet Immobilier" (Étape 2)</li>
            <li>Contacter un courtier partenaire (Étape 3)</li>
            <li>Envoyer votre premier email de bienvenue automatique (Étape 4)</li>
        </ul>
    </div>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════
         DÉTAIL D'UNE ÉTAPE
         ══════════════════════════════════════════════ -->
    <?php $etape = $etapes[$current_step] ?? null; ?>
    <?php if ($etape): ?>
    <div class="etape-detail">
        <div class="etape-detail-header">
            <div class="etape-detail-emoji" style="background: <?= $etape['gradient'] ?>">
                <?= $etape['emoji'] ?>
            </div>
            <div>
                <h2>Étape <?= $current_step ?> — <?= $etape['title'] ?></h2>
                <div class="etape-subtitle"><?= $etape['subtitle'] ?> • ⏱ <?= $etape['duration'] ?></div>
            </div>
        </div>

        <div class="etape-detail-desc">
            <?= $etape['description'] ?>
        </div>

        <!-- Liste des actions -->
        <?php foreach ($etape['actions'] as $action): 
            $is_done = in_array($action['id'], $completed_steps);
        ?>
        <div class="action-card <?= $is_done ? 'done' : '' ?>" data-action-id="<?= $action['id'] ?>">
            <div class="action-header">
                <div class="action-check <?= $is_done ? 'checked' : '' ?>" 
                     onclick="toggleAction('<?= $action['id'] ?>', <?= $current_step ?>)">
                    ✓
                </div>
                <div class="action-content">
                    <div class="action-title"><?= $action['title'] ?></div>
                    <span class="action-type-badge <?= $action['type'] ?>">
                        <?php 
                        $type_labels = ['reflexion' => '🧠 Réflexion', 'action' => '🔧 Action', 'validation' => '✅ Validation'];
                        echo $type_labels[$action['type']] ?? $action['type'];
                        ?>
                    </span>
                    <div class="action-desc"><?= $action['description'] ?></div>
                    
                    <?php if ($action['tips']): ?>
                    <div class="action-tips"><?= $action['tips'] ?></div>
                    <?php endif; ?>
                    
                    <?php if ($action['module_link']): ?>
                    <a href="<?= $action['module_link'] ?>" class="action-link">
                        <i class="fas fa-external-link-alt"></i> Ouvrir le module
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Navigation bas de page -->
        <div class="etape-footer">
            <?php if ($current_step > 1): ?>
            <a href="?page=parcours-acheteurs&etape=<?= $current_step - 1 ?>" class="btn btn-back">
                ← Étape <?= $current_step - 1 ?>
            </a>
            <?php else: ?>
            <a href="?page=parcours-acheteurs" class="btn btn-back">
                ← Vue d'ensemble
            </a>
            <?php endif; ?>

            <?php if ($current_step < 5): ?>
            <a href="?page=parcours-acheteurs&etape=<?= $current_step + 1 ?>" class="btn btn-next">
                Étape <?= $current_step + 1 ?> →
            </a>
            <?php else: ?>
            <a href="?page=parcours-acheteurs" class="btn btn-next">
                ✓ Voir le récap
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<script>
/**
 * Cocher/décocher une action
 */
async function toggleAction(actionId, stepNum) {
    const card = document.querySelector(`[data-action-id="${actionId}"]`);
    const check = card.querySelector('.action-check');
    const isDone = check.classList.contains('checked');
    
    // Toggle visuel immédiat
    if (isDone) {
        check.classList.remove('checked');
        card.classList.remove('done');
    } else {
        check.classList.add('checked');
        card.classList.add('done');
    }
    
    // Sauvegarder en DB
    try {
        const response = await fetch('?page=parcours-acheteurs&ajax=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                step: stepNum,
                action_id: actionId,
                status: isDone ? 'undone' : 'done'
            })
        });
        
        const result = await response.json();
        if (!result.success) {
            console.error('Erreur sauvegarde:', result.error);
            // Rollback visuel
            if (isDone) {
                check.classList.add('checked');
                card.classList.add('done');
            } else {
                check.classList.remove('checked');
                card.classList.remove('done');
            }
        }
    } catch (error) {
        console.error('Erreur réseau:', error);
    }
}
</script>