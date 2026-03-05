<?php
/**
 * PARCOURS D — ORGANISATION & SYSTÈME
 * /admin/modules/launchpad/parcours-organisation.php
 * 
 * 5 Étapes pour structurer et automatiser votre activité
 * Accessible via ?page=parcours-organisation
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

// Récupérer progression
$progression = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM parcours_progression WHERE user_id = ? AND parcours_id = 'D'");
    $stmt->execute([$user_id]);
    $progression = $stmt->fetch() ?: [];
} catch (Exception $e) {}

$completed_steps = [];
if (!empty($progression['completed_steps'])) {
    $completed_steps = json_decode($progression['completed_steps'], true) ?: [];
}

// Sauvegarde AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $step_num = intval($input['step'] ?? 0);
    $action_id = $input['action_id'] ?? '';
    $status = $input['status'] ?? 'done';
    
    if ($step_num > 0 && $action_id) {
        if ($status === 'done' && !in_array($action_id, $completed_steps)) {
            $completed_steps[] = $action_id;
        } elseif ($status === 'undone') {
            $completed_steps = array_values(array_diff($completed_steps, [$action_id]));
        }
        
        $json_steps = json_encode($completed_steps);
        
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS parcours_progression (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                parcours_id VARCHAR(5) NOT NULL,
                completed_steps JSON,
                current_step INT DEFAULT 1,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_parcours (user_id, parcours_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $pdo->prepare("INSERT INTO parcours_progression (user_id, parcours_id, completed_steps, current_step) 
                VALUES (?, 'D', ?, ?)
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
$parcours_id = 'D';
$parcours_name = 'Organisation & Système';
$parcours_emoji = '⚙️';
$parcours_color = '#6366f1';
$parcours_gradient = 'linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%)';

$etapes = [
    1 => [
        'title' => 'Pipeline & CRM',
        'emoji' => '📊',
        'subtitle' => 'Structurer votre pipeline de vente de A à Z',
        'duration' => '1h30',
        'color' => '#6366f1',
        'gradient' => 'linear-gradient(135deg, #6366f1, #4f46e5)',
        'description' => 'Sans pipeline structuré, vous perdez des leads, oubliez des relances, et ne savez pas où vous en êtes. Le CRM est le centre nerveux de votre activité — tout passe par là. C\'est la première chose à mettre en place.',
        'actions' => [
            [
                'id' => 'D1-1',
                'title' => 'Configurer vos étapes de pipeline',
                'description' => 'Définissez les colonnes de votre Kanban : Nouveau → Contacté → RDV planifié → Estimation faite → Mandat signé → En vente → Offre reçue → Compromis → Vente conclue.',
                'type' => 'action',
                'module_link' => '?page=crm-pipeline',
                'tips' => 'Maximum 8-9 étapes. Chaque étape doit représenter une action concrète de VOTRE part, pas celle du client.'
            ],
            [
                'id' => 'D1-2',
                'title' => 'Importer vos contacts existants',
                'description' => 'Regroupez tous vos contacts (Excel, téléphone, ancien CRM, cartes de visite) dans le CRM. Même si c\'est en vrac, l\'important est de tout centraliser.',
                'type' => 'action',
                'module_link' => '?page=contact',
                'tips' => 'Ne perdez pas de temps à tout nettoyer maintenant. Importez d\'abord, triez ensuite. Le mieux est l\'ennemi du bien.'
            ],
            [
                'id' => 'D1-3',
                'title' => 'Créer les tags de qualification',
                'description' => 'Tags essentiels : Vendeur/Acheteur/Investisseur + Chaud/Tiède/Froid + Source (Google, recommandation, salon, etc.). Cela permet de filtrer et prioriser.',
                'type' => 'action',
                'module_link' => '?page=crm',
                'tips' => 'Les 3 catégories de tags minimum : Type de projet, Température, Source d\'acquisition. Tout le reste est bonus.'
            ],
            [
                'id' => 'D1-4',
                'title' => 'Planifier la revue hebdomadaire du pipeline',
                'description' => 'Bloquez 30 minutes chaque lundi matin pour passer en revue chaque colonne de votre pipeline : qui relancer, qui appeler, quoi préparer.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'C\'est LA routine qui change tout. 30 min/semaine de revue pipeline = 0 lead oublié = plus de ventes.'
            ]
        ]
    ],
    2 => [
        'title' => 'Automatisations Essentielles',
        'emoji' => '🤖',
        'subtitle' => 'Éliminer les tâches répétitives avec des automatisations',
        'duration' => '2h',
        'color' => '#8b5cf6',
        'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'description' => 'Chaque tâche que vous faites manuellement et qui peut être automatisée est du temps volé à votre prospection et à vos RDV. Commencez par les 3 automatisations qui ont le plus d\'impact.',
        'actions' => [
            [
                'id' => 'D2-1',
                'title' => 'Auto #1 : Email de bienvenue nouveau lead',
                'description' => 'Quand un lead arrive (formulaire, téléphone) → email automatique en moins de 2 minutes : confirmation + prochaine étape + votre coordonnées directes.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Les leads contactés dans les 5 premières minutes ont 21x plus de chances de convertir. L\'auto-email achète du temps.'
            ],
            [
                'id' => 'D2-2',
                'title' => 'Auto #2 : Relance J+3 sans réponse',
                'description' => 'Si le lead n\'a pas répondu après 3 jours → email automatique : "Bonjour [Prénom], avez-vous eu le temps de consulter mon message ? Je reste disponible pour en discuter."',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'La relance J+3 récupère 15-20% des leads silencieux. C\'est gratuit et ça se fait tout seul.'
            ],
            [
                'id' => 'D2-3',
                'title' => 'Auto #3 : Rappel RDV J-1',
                'description' => 'Quand un RDV est planifié → SMS ou email automatique la veille : "Bonjour [Prénom], je vous confirme notre RDV demain à [heure] à [lieu]. À demain !"',
                'type' => 'action',
                'module_link' => '?page=sms',
                'tips' => 'Le rappel J-1 divise les no-shows par 3. Un SMS est plus efficace qu\'un email pour les rappels.'
            ],
            [
                'id' => 'D2-4',
                'title' => 'Configurer les notifications internes',
                'description' => 'Nouveau lead → notification email/SMS à vous-même. Changement de statut → alerte. Formulaire soumis → notification temps réel.',
                'type' => 'action',
                'module_link' => '?page=settings',
                'tips' => 'Vous ne devez JAMAIS découvrir un lead 3 jours après. Activez les notifications push sur votre téléphone.'
            ],
            [
                'id' => 'D2-5',
                'title' => 'Documenter vos process dans un wiki interne',
                'description' => 'Créez une page "Mes Process" avec : comment traiter un nouveau lead, comment préparer un RDV estimation, comment rédiger une offre. Ça servira aussi si vous recrutez.',
                'type' => 'reflexion',
                'module_link' => '?page=pages',
                'tips' => 'Même seul, documenter vos process vous force à les clarifier. Un process clair = moins d\'erreurs = plus de régularité.'
            ]
        ]
    ],
    3 => [
        'title' => 'Templates & Scripts',
        'emoji' => '📄',
        'subtitle' => 'Préparer vos messages types pour gagner du temps',
        'duration' => '2h',
        'color' => '#0ea5e9',
        'gradient' => 'linear-gradient(135deg, #0ea5e9, #0284c7)',
        'description' => 'Vous réécrivez les mêmes messages 10 fois par semaine ? Les templates vous font gagner 5-10 heures par mois tout en maintenant une qualité constante. Préparez les messages types pour chaque situation.',
        'actions' => [
            [
                'id' => 'D3-1',
                'title' => 'Template : Premier contact vendeur',
                'description' => 'Email/SMS type quand un vendeur demande une estimation. Personnalisation : [Prénom], [Adresse], [Type bien]. Inclure : qui vous êtes, prochaine étape, RDV proposé.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Soyez humain, pas corporate. "Bonjour Marie, merci pour votre demande concernant votre appartement rue de la Paix..." > "Cher(e) prospect, nous accusons réception..."'
            ],
            [
                'id' => 'D3-2',
                'title' => 'Template : Premier contact acheteur',
                'description' => 'Message type pour un acheteur qui s\'inscrit. Inclure : confirmation de son projet, questions de qualification (budget, délai, financement), proposition de RDV découverte.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'L\'acheteur veut savoir que vous l\'avez compris. Reformulez son projet dans le message : "Vous cherchez un 3 pièces à Bordeaux centre, budget 250K..."'
            ],
            [
                'id' => 'D3-3',
                'title' => 'Template : Relance vendeur après estimation',
                'description' => 'J+3 après l\'estimation : "Avez-vous eu le temps de réfléchir ? Voici un récap de notre analyse...". J+7 : "Je reste disponible si vous avez des questions".',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'La relance post-estimation est le moment critique. 60% des mandats se signent entre J+3 et J+14. Ne lâchez pas.'
            ],
            [
                'id' => 'D3-4',
                'title' => 'Script : Appel téléphonique de découverte',
                'description' => 'Trame d\'appel structurée : Accroche (30s) → Questions ouvertes (3min) → Reformulation (1min) → Proposition de RDV (1min) → Confirmation (30s).',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Ne lisez jamais le script mot à mot. Connaissez la structure et adaptez. Le naturel convertit, le robotique fait fuir.'
            ],
            [
                'id' => 'D3-5',
                'title' => 'Template : Compte-rendu de visite',
                'description' => 'Email type après une visite acheteur : récap du bien, points forts/faibles discutés, prochaine étape, autres biens similaires à proposer.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Envoyez le compte-rendu dans l\'heure qui suit la visite. Le prospect est encore chaud, votre réactivité le rassure.'
            ]
        ]
    ],
    4 => [
        'title' => 'Dashboard & KPIs',
        'emoji' => '📈',
        'subtitle' => 'Piloter votre activité avec les bons indicateurs',
        'duration' => '1h',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'description' => 'Ce qui ne se mesure pas ne s\'améliore pas. Votre dashboard doit vous donner en 30 secondes une vision claire de votre activité : combien de leads, combien de RDV, combien de mandats, combien de ventes. Et surtout, où ça bloque.',
        'actions' => [
            [
                'id' => 'D4-1',
                'title' => 'Définir vos 5 KPIs essentiels',
                'description' => 'Les 5 chiffres à suivre chaque semaine : 1) Leads entrants, 2) RDV bookés, 3) Estimations réalisées, 4) Mandats signés, 5) Ventes conclues.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Calculez aussi vos taux de conversion entre chaque étape. 100 leads → 20 RDV → 10 estimations → 5 mandats → 2 ventes. Où perdez-vous le plus ?'
            ],
            [
                'id' => 'D4-2',
                'title' => 'Configurer le tableau de bord CRM',
                'description' => 'Personnalisez votre dashboard pour voir en un coup d\'œil : leads cette semaine, pipeline en cours, tâches du jour, prochains RDV.',
                'type' => 'action',
                'module_link' => '?page=crm',
                'tips' => 'Votre dashboard est la première chose que vous voyez le matin. Il doit répondre à : "Qu\'est-ce que je fais aujourd\'hui ?"'
            ],
            [
                'id' => 'D4-3',
                'title' => 'Mettre en place le suivi des sources',
                'description' => 'Tracez d\'où viennent vos leads : Google, recommandation, réseaux sociaux, salon, portails. Cela vous dira où investir votre temps et argent.',
                'type' => 'action',
                'module_link' => '?page=analytics',
                'tips' => 'La source la plus rentable n\'est pas toujours la plus volumineuse. 5 leads recommandation > 50 leads portail en taux de conversion.'
            ],
            [
                'id' => 'D4-4',
                'title' => 'Créer votre routine de reporting hebdomadaire',
                'description' => 'Chaque vendredi, notez vos 5 KPIs de la semaine dans un fichier simple. Comparez avec la semaine précédente. Identifiez une action corrective.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Un simple tableau : Semaine | Leads | RDV | Mandats | Ventes | Action corrective. En 3 mois, vous verrez les tendances.'
            ],
            [
                'id' => 'D4-5',
                'title' => 'Fixer vos objectifs mensuels',
                'description' => 'En partant de votre objectif de ventes : combien de mandats ? Combien de RDV ? Combien de leads ? Remontez la chaîne pour avoir vos objectifs quotidiens.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Objectif : 2 ventes/mois = 5 mandats = 12 estimations = 30 RDV = 150 leads. Maintenant vous savez combien de leads/jour il vous faut.'
            ]
        ]
    ],
    5 => [
        'title' => 'Routine & Discipline',
        'emoji' => '🎯',
        'subtitle' => 'Installer les habitudes qui garantissent vos résultats',
        'duration' => '30 min + pratique quotidienne',
        'color' => '#f43f5e',
        'gradient' => 'linear-gradient(135deg, #f43f5e, #e11d48)',
        'description' => 'Le système est en place. Maintenant, c\'est la régularité qui fera la différence. Les meilleurs agents ne sont pas les plus talentueux — ce sont les plus disciplinés. Cette étape installe les habitudes qui transforment le système en résultats.',
        'actions' => [
            [
                'id' => 'D5-1',
                'title' => 'Routine du matin (30 min)',
                'description' => '1) Ouvrir le dashboard CRM (2 min). 2) Traiter les leads de la nuit (10 min). 3) Passer en revue les tâches du jour (5 min). 4) Envoyer les relances prévues (10 min). 5) Planifier les RDV de la journée (3 min).',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Faites cette routine AVANT de consulter vos emails et réseaux sociaux. Les leads d\'abord, le reste après.'
            ],
            [
                'id' => 'D5-2',
                'title' => 'Bloc prospection quotidien (1h)',
                'description' => 'Bloquez 1 heure par jour dédiée uniquement à la prospection : appels, messages, publications. Pas de RDV, pas d\'administratif pendant cette heure.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Le meilleur créneau : 9h-10h le matin. Les gens sont au bureau, disponibles, dans un état d\'esprit "action".'
            ],
            [
                'id' => 'D5-3',
                'title' => 'Revue du vendredi (30 min)',
                'description' => 'Chaque vendredi : 1) Compter les KPIs de la semaine. 2) Identifier le blocage principal. 3) Planifier UNE action corrective pour la semaine suivante.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Ne cherchez pas à tout améliorer chaque semaine. UN seul point à améliorer. Après 4 mois, ça fait 16 améliorations.'
            ],
            [
                'id' => 'D5-4',
                'title' => 'Planifier le contenu de la semaine suivante',
                'description' => 'Chaque dimanche soir ou lundi matin : planifier 3-5 publications (1 article blog, 2-3 posts réseaux sociaux, 1 email newsletter).',
                'type' => 'action',
                'module_link' => '?page=articles',
                'tips' => 'Utilisez l\'IA pour générer les brouillons. Vous n\'avez plus qu\'à personnaliser et publier. Batch = efficacité.'
            ],
            [
                'id' => 'D5-5',
                'title' => 'Installer le système complet pendant 2 semaines',
                'description' => 'Engagez-vous à suivre toutes les routines pendant 14 jours sans exception. Après 2 semaines, l\'habitude est ancrée et les premiers résultats visibles.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Affichez votre checklist quotidienne sur votre bureau. Cochez chaque jour. La régularité bat l\'intensité.'
            ]
        ]
    ]
];

// Calcul progression
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

// Inclure le template commun
include __DIR__ . '/parcours-template.php';