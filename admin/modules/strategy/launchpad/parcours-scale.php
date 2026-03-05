<?php
/**
 * PARCOURS E — SCALE & DOMINATION
 * /admin/modules/launchpad/parcours-scale.php
 * 
 * 5 Étapes pour devenir #1 sur votre zone
 * Accessible via ?page=parcours-scale
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
    $stmt = $pdo->prepare("SELECT * FROM parcours_progression WHERE user_id = ? AND parcours_id = 'E'");
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
                VALUES (?, 'E', ?, ?)
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
$parcours_id = 'E';
$parcours_name = 'Scale & Domination';
$parcours_emoji = '🚀';
$parcours_color = '#8b5cf6';
$parcours_gradient = 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%)';

$etapes = [
    1 => [
        'title' => 'SEO Territorial',
        'emoji' => '🌐',
        'subtitle' => 'Dominer les résultats Google sur votre zone',
        'duration' => '3h',
        'color' => '#8b5cf6',
        'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'description' => 'Quand quelqu\'un tape "immobilier + [votre ville]" sur Google, vous devez apparaître en premier. Le SEO territorial est votre arme de domination locale : chaque page bien positionnée est un aimant à leads gratuit qui travaille 24h/24.',
        'actions' => [
            [
                'id' => 'E1-1',
                'title' => 'Auditer votre positionnement actuel',
                'description' => 'Tapez vos 10 mots-clés cibles sur Google (navigation privée) : "immobilier [ville]", "estimation [ville]", "acheter maison [quartier]". Notez votre position pour chacun.',
                'type' => 'reflexion',
                'module_link' => '?page=seo',
                'tips' => 'Si vous n\'apparaissez pas en page 1, vous n\'existez pas pour 95% des chercheurs. C\'est le point de départ réaliste.'
            ],
            [
                'id' => 'E1-2',
                'title' => 'Créer 5 pages quartiers/secteurs',
                'description' => 'Une page par quartier stratégique avec : présentation, prix au m², types de biens, commodités, galerie photos, formulaire de recherche. Chaque page cible "immobilier + [quartier]".',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Commencez par le quartier où vous avez le plus de biens. Le contenu doit être unique, pas copié de Wikipedia.'
            ],
            [
                'id' => 'E1-3',
                'title' => 'Optimiser votre Google Business Profile',
                'description' => 'Complétez à 100% : photos pro (10+), description optimisée, catégories correctes, horaires, zone de couverture. Publiez 2 posts/semaine (biens, conseils, témoignages).',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Le Google Business Profile est votre arme #1 en SEO local. 50% des recherches immobilières locales passent par la carte Google Maps.'
            ],
            [
                'id' => 'E1-4',
                'title' => 'Lancer votre stratégie de backlinks locaux',
                'description' => 'Inscrivez-vous sur les annuaires locaux (PagesJaunes, mairie, CCI), proposez des articles invités aux blogs locaux, participez aux événements et demandez des liens retour.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Un lien du site de votre mairie ou de la CCI locale vaut plus que 100 liens d\'annuaires génériques. Privilégiez la qualité.'
            ],
            [
                'id' => 'E1-5',
                'title' => 'Planifier 4 articles de blog SEO par mois',
                'description' => 'Thèmes : "Prix immobilier [ville] [année]", "Meilleurs quartiers pour acheter à [ville]", "Guide achat appartement [ville]", études de marché locales.',
                'type' => 'action',
                'module_link' => '?page=articles&action=create',
                'tips' => 'Utilisez l\'IA pour rédiger les brouillons, puis personnalisez avec vos données locales et votre expertise terrain.'
            ]
        ]
    ],
    2 => [
        'title' => 'Contenu Vidéo & Réseaux',
        'emoji' => '🎬',
        'subtitle' => 'Devenir le visage de l\'immobilier dans votre zone',
        'duration' => '2h + production continue',
        'color' => '#ec4899',
        'gradient' => 'linear-gradient(135deg, #ec4899, #db2777)',
        'description' => 'La vidéo est le format #1 pour créer de la confiance à distance. TikTok, Instagram Reels, YouTube Shorts — les plateformes poussent le contenu vidéo. Un agent immobilier qui publie régulièrement des vidéos locales devient la référence de sa zone en quelques mois.',
        'actions' => [
            [
                'id' => 'E2-1',
                'title' => 'Définir votre ligne éditoriale vidéo',
                'description' => '4 piliers de contenu : 1) Visites de biens (30%), 2) Conseils immobiliers (30%), 3) Vie locale / quartier (25%), 4) Coulisses de votre métier (15%).',
                'type' => 'reflexion',
                'module_link' => '?page=strategy',
                'tips' => 'Le ratio 70% éducatif/lifestyle — 30% commercial fonctionne le mieux. Ne faites pas que des pubs de biens.'
            ],
            [
                'id' => 'E2-2',
                'title' => 'Créer votre premier lot de 10 vidéos',
                'description' => 'Tournez 10 vidéos courtes (30-60 secondes) en une seule session : 3 visites, 3 conseils, 2 quartiers, 2 coulisses. Planifiez la publication sur 2 semaines.',
                'type' => 'action',
                'module_link' => '?page=reseaux-sociaux',
                'tips' => 'Filmez au smartphone en format portrait. Pas besoin de matériel pro. L\'authenticité bat la production sur les réseaux sociaux.'
            ],
            [
                'id' => 'E2-3',
                'title' => 'Optimiser vos profils réseaux sociaux',
                'description' => 'Instagram, TikTok, Facebook, LinkedIn : bio claire avec votre spécialité et zone, lien vers votre page de capture, photo professionnelle, highlights organisés.',
                'type' => 'action',
                'module_link' => '?page=reseaux-sociaux',
                'tips' => 'Votre bio doit répondre à : Qui je suis + Où + Pour qui + Comment me contacter. En 150 caractères max.'
            ],
            [
                'id' => 'E2-4',
                'title' => 'Installer une routine de publication',
                'description' => 'Minimum : 3 posts/semaine sur Instagram, 2 vidéos TikTok, 1 post LinkedIn. Utilisez un outil de planification pour publier sans y penser.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'La régularité bat la qualité. 3 vidéos "correctes" par semaine > 1 vidéo "parfaite" par mois. L\'algorithme récompense la constance.'
            ],
            [
                'id' => 'E2-5',
                'title' => 'Analyser et itérer après 30 jours',
                'description' => 'Après 1 mois : quelles vidéos ont le plus de vues/engagement ? Quel type de contenu génère des DM/contacts ? Doublez ce qui marche, arrêtez ce qui ne marche pas.',
                'type' => 'validation',
                'module_link' => '?page=analytics',
                'tips' => 'Ne jugez pas avant 30 jours et 20+ publications. L\'algorithme a besoin de données pour vous pousser.'
            ]
        ]
    ],
    3 => [
        'title' => 'Publicité Payante',
        'emoji' => '💰',
        'subtitle' => 'Accélérer avec Facebook Ads et Google Ads',
        'duration' => '2h + budget mensuel',
        'color' => '#f43f5e',
        'gradient' => 'linear-gradient(135deg, #f43f5e, #e11d48)',
        'description' => 'Le SEO prend 3-6 mois. La pub payante génère des leads demain. L\'idéal est de combiner les deux : la pub pour le court terme, le SEO pour le long terme. Commencez petit (5-10€/jour) et scalez ce qui fonctionne.',
        'actions' => [
            [
                'id' => 'E3-1',
                'title' => 'Configurer le Facebook Pixel sur votre site',
                'description' => 'Installez le pixel de suivi Facebook/Meta sur toutes vos pages. C\'est indispensable pour mesurer les conversions et créer des audiences de retargeting.',
                'type' => 'action',
                'module_link' => '?page=settings',
                'tips' => 'Installez le pixel MAINTENANT même si vous ne faites pas de pub tout de suite. Il collecte des données dès l\'installation.'
            ],
            [
                'id' => 'E3-2',
                'title' => 'Créer votre première campagne Facebook "Estimation"',
                'description' => 'Objectif : Génération de leads. Audience : propriétaires dans votre zone (rayon 15km), 35-65 ans. Offre : estimation gratuite. Budget : 5-10€/jour pendant 14 jours.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Utilisez un visuel de votre zone (photo réelle, pas stock) + texte orienté bénéfice. Testez 3 visuels différents.'
            ],
            [
                'id' => 'E3-3',
                'title' => 'Lancer une campagne Google Ads Search',
                'description' => 'Mots-clés : "estimation immobilière [ville]", "vendre maison [ville]", "agent immobilier [ville]". Landing page dédiée. Budget : 10-15€/jour.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Google Ads capte l\'intention. Le prospect tape activement sa recherche = il est chaud. Le coût par lead est plus élevé mais la qualité aussi.'
            ],
            [
                'id' => 'E3-4',
                'title' => 'Mettre en place le retargeting',
                'description' => 'Les visiteurs de votre site qui n\'ont pas converti voient vos pubs sur Facebook/Instagram pendant 30 jours. Contenu : témoignages, biens vendus, rappel de votre offre.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Le retargeting a le meilleur ROI en publicité. Ces gens vous connaissent déjà — il suffit d\'un rappel pour les faire revenir.'
            ],
            [
                'id' => 'E3-5',
                'title' => 'Analyser les résultats et optimiser',
                'description' => 'Après 14 jours : coût par lead, taux de conversion, qualité des leads. Coupez ce qui ne marche pas, doublez le budget de ce qui marche.',
                'type' => 'validation',
                'module_link' => '?page=analytics',
                'tips' => 'Un coût par lead acceptable en immobilier : 5-15€ (estimation), 2-5€ (guide gratuit). Au-dessus = optimisez. En dessous = scalez.'
            ]
        ]
    ],
    4 => [
        'title' => 'Réseau de Prescripteurs',
        'emoji' => '🤝',
        'subtitle' => 'Construire un réseau de partenaires qui vous envoient des clients',
        'duration' => '3h + relationnel continu',
        'color' => '#0ea5e9',
        'gradient' => 'linear-gradient(135deg, #0ea5e9, #0284c7)',
        'description' => 'Les leads recommandés convertissent 5x mieux que les leads froids. Votre réseau de prescripteurs (notaires, courtiers, artisans, commerçants) est un canal d\'acquisition gratuit, pérenne, et de haute qualité. C\'est l\'arme secrète des agents #1.',
        'actions' => [
            [
                'id' => 'E4-1',
                'title' => 'Cartographier vos prescripteurs potentiels',
                'description' => 'Listez les professionnels qui voient vos prospects AVANT vous : notaires, courtiers, banquiers, diagnostiqueurs, déménageurs, architectes, artisans, syndics.',
                'type' => 'reflexion',
                'module_link' => '?page=contact',
                'tips' => 'Les notaires et courtiers sont les plus évidents, mais ne négligez pas les artisans (ils sont souvent dans les maisons avant la mise en vente).'
            ],
            [
                'id' => 'E4-2',
                'title' => 'Créer votre "offre prescripteur"',
                'description' => 'Que proposez-vous en échange ? Recommandation réciproque, commission d\'apport, visibilité sur votre site, invitation événements. Formalisez la proposition.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Le plus efficace : la réciprocité simple. "Je vous envoie mes clients, vous m\'envoyez les vôtres." Pas besoin de commission au début.'
            ],
            [
                'id' => 'E4-3',
                'title' => 'Contacter vos 10 premiers prescripteurs',
                'description' => 'Email ou appel personnalisé : présentez-vous, expliquez le bénéfice mutuel, proposez un café ou un déjeuner. Objectif : 10 contacts → 5 RDV → 3 partenariats actifs.',
                'type' => 'action',
                'module_link' => '?page=contact',
                'tips' => 'Ne commencez pas par demander. Commencez par donner : envoyez-leur un client, partagez un contact utile, offrez un avis sur leur projet.'
            ],
            [
                'id' => 'E4-4',
                'title' => 'Créer un événement local de networking',
                'description' => 'Organisez un petit-déjeuner ou afterwork avec 10-15 professionnels de votre zone. Thème : "Échanges entre pros de l\'immobilier et de l\'habitat". Coût : 100-200€ = investissement rentable.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Pas besoin d\'un grand événement. Un café dans un lieu sympa avec 10 personnes triées sur le volet = plus de valeur qu\'un salon de 500 personnes.'
            ],
            [
                'id' => 'E4-5',
                'title' => 'Systématiser le suivi prescripteurs',
                'description' => 'Dans votre CRM : créez une catégorie "Prescripteurs" avec relance trimestrielle. Chaque trimestre : un café, un message, ou l\'envoi d\'un bien qui pourrait intéresser leurs clients.',
                'type' => 'action',
                'module_link' => '?page=crm',
                'tips' => 'Le réseau de prescripteurs est un jardin : il faut l\'arroser régulièrement. Un contact par trimestre suffit pour rester en tête.'
            ]
        ]
    ],
    5 => [
        'title' => 'Plan 90 Jours de Domination',
        'emoji' => '👑',
        'subtitle' => 'Assembler tous les leviers pour devenir #1 sur votre zone',
        'duration' => '1h planification + 90 jours d\'exécution',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'description' => 'Vous avez maintenant tous les outils : SEO, contenu vidéo, publicité payante, réseau de prescripteurs. Cette dernière étape assemble tout dans un plan de 90 jours pour devenir la référence incontournable de votre zone.',
        'actions' => [
            [
                'id' => 'E5-1',
                'title' => 'Mois 1 : Les Fondations (semaines 1-4)',
                'description' => 'Publier 5 pages quartiers. Lancer les premières campagnes pub (5€/jour). Contacter 10 prescripteurs. Publier 3 vidéos/semaine. Objectif : 50 leads.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Le mois 1 est le plus dur. Vous plantez des graines. Les résultats viendront au mois 2-3. Patience + régularité.'
            ],
            [
                'id' => 'E5-2',
                'title' => 'Mois 2 : L\'Accélération (semaines 5-8)',
                'description' => 'Doubler le budget pub de ce qui marche. Ajouter 3 pages quartiers. Signer 3 partenariats prescripteurs. Lancer le retargeting. Objectif : 100 leads cumulés.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Au mois 2, vous avez des données. Analysez sans émotion : coupez ce qui ne marche pas, scalez ce qui marche. Pas d\'attachement.'
            ],
            [
                'id' => 'E5-3',
                'title' => 'Mois 3 : La Domination (semaines 9-12)',
                'description' => 'Consolider le SEO (premiers résultats visibles). Scaler les pubs rentables. Automatiser les séquences email. Événement prescripteurs. Objectif : 200 leads cumulés, 10+ mandats.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Au mois 3, vous commencez à être visible partout : Google, réseaux, recommandations. L\'effet boule de neige commence.'
            ],
            [
                'id' => 'E5-4',
                'title' => 'Créer votre tableau de bord de domination',
                'description' => 'Un document avec vos KPIs de scale : parts de marché estimées, positionnement Google, nombre de prescripteurs actifs, leads par source, coût d\'acquisition, taux de conversion global.',
                'type' => 'action',
                'module_link' => '?page=analytics',
                'tips' => 'Comparez vos chiffres à ceux de la concurrence. Si vous êtes #1 sur Google Maps, #1 en avis clients, et #1 en contenu local — vous avez gagné.'
            ],
            [
                'id' => 'E5-5',
                'title' => 'Verrouiller votre zone',
                'description' => 'Une fois la domination établie : maintenez la cadence (contenu + pub + prescripteurs), augmentez vos prix/honoraires, sélectionnez vos mandats. Vous êtes passé de chasseur à référence.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'La domination zonale signifie que les prospects viennent à vous. Le coût d\'acquisition baisse. La marge augmente. C\'est le cercle vertueux.'
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