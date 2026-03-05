<?php
/**
 * PARCOURS A — CONQUÊTE VENDEURS
 * /admin/modules/launchpad/parcours-vendeurs.php
 * 
 * 5 Étapes pour attirer des propriétaires qui veulent vendre
 * Accessible via ?page=parcours-vendeurs
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
    $stmt = $pdo->prepare("SELECT * FROM parcours_progression WHERE user_id = ? AND parcours_id = 'A'");
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
                VALUES (?, 'A', ?, ?)
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
$parcours_id = 'A';
$parcours_name = 'Conquête Vendeurs';
$parcours_emoji = '🏠';
$parcours_color = '#ef4444';
$parcours_gradient = 'linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%)';

$etapes = [
    1 => [
        'title' => 'Persona Vendeur',
        'emoji' => '🎯',
        'subtitle' => 'Identifier précisément votre vendeur idéal',
        'duration' => '45 min',
        'color' => '#ef4444',
        'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)',
        'description' => 'Avant de prospecter, vous devez savoir exactement QUI vous cherchez. Un vendeur pressé par une mutation n\'a pas les mêmes besoins qu\'un couple qui se sépare ou qu\'un héritier qui veut vendre un bien familial. Chaque profil nécessite une approche différente.',
        'actions' => [
            [
                'id' => 'A1-1',
                'title' => 'Identifier les 3 profils vendeurs de votre zone',
                'description' => 'Les profils classiques : Vendeur pressé (mutation, divorce, urgence financière), Vendeur patrimonial (succession, retraite, optimisation), Vendeur opportuniste (marché haut, plus-value). Pour chaque profil : motivation, délai, sensibilité au prix.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Analysez vos 10 derniers mandats : quel profil revient le plus souvent ? C\'est votre persona #1.'
            ],
            [
                'id' => 'A1-2',
                'title' => 'Créer la fiche persona "Vendeur #1" dans NeuroPersona',
                'description' => 'Utilisez le module NeuroPersona pour créer votre persona vendeur prioritaire : ses peurs (vendre en dessous du prix, se faire arnaquer), ses désirs (vendre vite, au meilleur prix, sans stress), ses déclencheurs (mutation, divorce, succession).',
                'type' => 'action',
                'module_link' => '?page=neuropersona',
                'tips' => 'Les peurs du vendeur sont plus puissantes que ses désirs. Parlez d\'abord à ses angoisses, ensuite à ses rêves.'
            ],
            [
                'id' => 'A1-3',
                'title' => 'Cartographier le parcours de décision du vendeur',
                'description' => 'Les 4 phases : 1) "Et si je vendais ?" (idée vague), 2) "Combien vaut mon bien ?" (recherche active), 3) "Quel agent choisir ?" (comparaison), 4) "Je signe le mandat" (décision). À quel moment intervenez-vous ?',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'La phase 2 est le moment d\'or : le vendeur cherche une estimation. Si vous êtes là à ce moment, vous avez 80% de chances de décrocher le mandat.'
            ],
            [
                'id' => 'A1-4',
                'title' => 'Définir votre promesse unique vendeur',
                'description' => 'Qu\'est-ce qui vous différencie des autres agents ? Exemples : "Vendu en 45 jours ou honoraires réduits", "Estimation précise grâce à 15 ans d\'expertise locale", "Accompagnement de A à Z, zéro stress".',
                'type' => 'reflexion',
                'module_link' => '?page=launchpad&step=3',
                'tips' => 'Votre promesse doit être spécifique (chiffre ou engagement concret), crédible (basée sur votre track record) et mémorable (facile à retenir).'
            ]
        ]
    ],
    2 => [
        'title' => 'Page Estimation en Ligne',
        'emoji' => '🏷️',
        'subtitle' => 'Créer votre aimant à vendeurs #1',
        'duration' => '2h',
        'color' => '#f97316',
        'gradient' => 'linear-gradient(135deg, #f97316, #ea580c)',
        'description' => 'La page d\'estimation en ligne est votre arme #1 pour capter des vendeurs. Quand un propriétaire se demande "combien vaut mon bien ?", il va sur Google. Votre page doit apparaître et le convertir en lead. C\'est le tunnel le plus rentable en immobilier.',
        'actions' => [
            [
                'id' => 'A2-1',
                'title' => 'Créer la landing page "Estimation Gratuite"',
                'description' => 'Structure : Hero avec promesse ("Estimation gratuite en 2 min") + Formulaire court (adresse, type, surface, email, tél) + Preuves sociales (avis, nombre de ventes) + Garantie ("100% gratuit, sans engagement").',
                'type' => 'action',
                'module_link' => '?page=pages-capture&action=create',
                'tips' => 'Formulaire en 2 étapes : étape 1 = adresse + type (facile, engagement minimal), étape 2 = coordonnées. Le multi-step augmente les conversions de 30%.'
            ],
            [
                'id' => 'A2-2',
                'title' => 'Rédiger le contenu avec le framework MERE',
                'description' => 'M (Magnétique) = "Découvrez la valeur réelle de votre bien en 2 minutes". E (Émotion) = "Ne passez pas à côté de milliers d\'euros". R (Raison) = "Basé sur les ventes réelles du quartier". E (Engagement) = "Recevoir mon estimation gratuite".',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Le titre est l\'élément le plus important. Testez 3 versions différentes et gardez celle qui convertit le mieux.'
            ],
            [
                'id' => 'A2-3',
                'title' => 'Configurer l\'email automatique post-estimation',
                'description' => 'Quand le vendeur remplit le formulaire → email automatique immédiat : "Merci [Prénom], votre estimation est en cours. Un conseiller vous contacte sous 24h. En attendant, voici notre guide vendeur."',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'L\'email de confirmation est votre première impression. Soyez pro, rapide et rassurant. Incluez votre photo et vos coordonnées directes.'
            ],
            [
                'id' => 'A2-4',
                'title' => 'Créer le module "Avis de valeur" téléchargeable',
                'description' => 'Préparez un template PDF professionnel d\'avis de valeur que vous personnaliserez pour chaque demande. Inclure : fourchette de prix, comparables, tendances du marché local, votre recommandation.',
                'type' => 'action',
                'module_link' => '?page=estimation',
                'tips' => 'L\'avis de valeur est votre cheval de Troie : il démontre votre expertise et crée un prétexte pour le RDV physique.'
            ],
            [
                'id' => 'A2-5',
                'title' => 'Tester le tunnel complet',
                'description' => 'Parcourez vous-même le tunnel : Google → page estimation → formulaire → email de confirmation → lead dans le CRM. Tout fonctionne ? L\'email arrive ? Le lead est bien enregistré ?',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Testez sur mobile aussi — 70% des recherches immobilières se font sur smartphone.'
            ]
        ]
    ],
    3 => [
        'title' => 'Google Business Profile',
        'emoji' => '📍',
        'subtitle' => 'Dominer la recherche locale avec votre fiche Google',
        'duration' => '1h30',
        'color' => '#10b981',
        'gradient' => 'linear-gradient(135deg, #10b981, #059669)',
        'description' => 'Votre fiche Google Business Profile (ex-Google My Business) est souvent le PREMIER contact entre un vendeur et vous. 50% des recherches immobilières locales passent par Google Maps. Une fiche optimisée = des appels entrants gratuits chaque semaine.',
        'actions' => [
            [
                'id' => 'A3-1',
                'title' => 'Optimiser votre fiche Google à 100%',
                'description' => 'Complétez TOUS les champs : description optimisée avec mots-clés locaux, catégories correctes (Agent immobilier + Estimation immobilière), horaires, zone de couverture, attributs, lien vers votre page estimation.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'La description doit contenir : votre ville, vos services, votre spécialité. Ex: "Agent immobilier à Bordeaux — Estimation gratuite, accompagnement vente et achat dans la métropole bordelaise".'
            ],
            [
                'id' => 'A3-2',
                'title' => 'Ajouter 15+ photos professionnelles',
                'description' => 'Photos de vous en action (pas en studio), de vos biens vendus, de votre bureau, de quartiers emblématiques de votre zone. Google favorise les fiches avec beaucoup de photos.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Les fiches avec 10+ photos reçoivent 42% plus de demandes d\'itinéraire et 35% plus de clics vers le site web.'
            ],
            [
                'id' => 'A3-3',
                'title' => 'Planifier 10 demandes d\'avis clients',
                'description' => 'Contactez vos 10 derniers clients satisfaits et demandez un avis Google. Envoyez-leur le lien direct par SMS juste après la conversation. Objectif : 10+ avis avec 4.8+ de moyenne.',
                'type' => 'action',
                'module_link' => '?page=contact',
                'tips' => 'Le meilleur moment : le jour de la signature chez le notaire. Le client est heureux, reconnaissant, et dit oui à tout.'
            ],
            [
                'id' => 'A3-4',
                'title' => 'Publier 2 posts Google par semaine',
                'description' => 'Types de posts : biens vendus (avec prix et délai), conseils vendeur, actualités marché local, témoignages. Chaque post renforce votre visibilité dans les résultats locaux.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Les posts Google expirent après 7 jours. C\'est pourquoi la régularité est essentielle — planifiez vos posts à l\'avance.'
            ],
            [
                'id' => 'A3-5',
                'title' => 'Répondre à tous les avis (positifs ET négatifs)',
                'description' => 'Répondez à chaque avis dans les 24h. Avis positif : remerciement personnalisé. Avis négatif : réponse professionnelle, empathique, avec proposition de résolution.',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Un avis négatif bien géré peut devenir un atout : les prospects voient que vous prenez soin de vos clients même quand ça ne va pas.'
            ]
        ]
    ],
    4 => [
        'title' => 'Scripts & Séquences',
        'emoji' => '📞',
        'subtitle' => 'Préparer vos scripts d\'appel et séquences de relance',
        'duration' => '2h',
        'color' => '#8b5cf6',
        'gradient' => 'linear-gradient(135deg, #8b5cf6, #7c3aed)',
        'description' => 'Un lead vendeur qui demande une estimation ne signe pas le mandat le même jour. Il faut le relancer, le rassurer, lui démontrer votre valeur. Vos scripts d\'appel et séquences email sont les outils qui transforment un lead froid en mandat signé.',
        'actions' => [
            [
                'id' => 'A4-1',
                'title' => 'Script : Premier appel après demande d\'estimation',
                'description' => 'Structure en 5 temps : 1) Accroche chaleureuse (30s), 2) Reformulation de son projet (1min), 3) Questions de qualification : motivation, délai, prix souhaité (3min), 4) Proposition de RDV estimation (1min), 5) Confirmation (30s).',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Appelez dans l\'heure qui suit la demande. Les agents qui appellent dans les 5 premières minutes ont 21x plus de chances de convertir.'
            ],
            [
                'id' => 'A4-2',
                'title' => 'Séquence email vendeur (5 emails sur 14 jours)',
                'description' => 'J+0 : Confirmation + guide vendeur PDF. J+2 : "Les 3 erreurs qui font perdre 20K€ aux vendeurs". J+5 : Témoignage client vendeur. J+8 : "Prix du marché dans votre quartier ce mois-ci". J+14 : "Prêt à avancer ? Prenez RDV".',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'Chaque email doit apporter de la valeur AVANT de pousser à l\'action. Le vendeur doit se dire "cet agent connaît son sujet" à chaque message.'
            ],
            [
                'id' => 'A4-3',
                'title' => 'Template SMS de relance',
                'description' => 'SMS J+1 : "Bonjour [Prénom], suite à votre demande d\'estimation pour [adresse]. Je suis disponible pour en discuter. [Votre prénom] — [Tél]". SMS J+7 : "Des nouvelles de votre projet de vente ? Je reste à votre disposition."',
                'type' => 'action',
                'module_link' => '?page=sms',
                'tips' => 'Le SMS a un taux d\'ouverture de 98% contre 20% pour l\'email. Utilisez-le pour les relances urgentes, pas pour le contenu long.'
            ],
            [
                'id' => 'A4-4',
                'title' => 'Script : RDV estimation sur place',
                'description' => 'Trame du RDV : 1) Visite du bien avec le vendeur (20min), 2) Questions sur son projet et ses attentes (10min), 3) Présentation de votre méthode et vos résultats (10min), 4) Annonce du prix estimé (5min), 5) Proposition de mandat (5min).',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Ne donnez JAMAIS le prix par téléphone ou email. Le RDV physique est indispensable pour créer la confiance et signer le mandat.'
            ],
            [
                'id' => 'A4-5',
                'title' => 'Séquence post-estimation (mandat non signé)',
                'description' => 'J+1 : Envoi de l\'avis de valeur PDF par email. J+3 : Appel de suivi "Avez-vous des questions ?". J+7 : Email avec un comparable vendu récemment. J+14 : "Je reste disponible, voici mon calendrier de RDV".',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => '60% des mandats se signent entre J+3 et J+14 après l\'estimation. Ne lâchez pas pendant cette fenêtre critique.'
            ]
        ]
    ],
    5 => [
        'title' => 'Retargeting & Nurturing',
        'emoji' => '🔄',
        'subtitle' => 'Rester visible auprès des vendeurs qui hésitent encore',
        'duration' => '1h30',
        'color' => '#0ea5e9',
        'gradient' => 'linear-gradient(135deg, #0ea5e9, #0284c7)',
        'description' => 'Tous les vendeurs ne sont pas prêts à signer maintenant. Certains hésitent pendant des semaines, voire des mois. Le retargeting et le nurturing vous gardent dans leur tête jusqu\'au moment de la décision. Quand ils seront prêts, c\'est vous qu\'ils appelleront.',
        'actions' => [
            [
                'id' => 'A5-1',
                'title' => 'Installer le Facebook Pixel sur votre site',
                'description' => 'Le pixel Facebook/Meta trace les visiteurs de votre site. Indispensable pour leur montrer vos publicités ensuite. Installez-le sur TOUTES vos pages, en particulier la page estimation.',
                'type' => 'action',
                'module_link' => '?page=settings',
                'tips' => 'Installez le pixel MAINTENANT même si vous ne faites pas de pub. Il collecte des données dès l\'installation — chaque jour sans pixel est un jour de données perdues.'
            ],
            [
                'id' => 'A5-2',
                'title' => 'Créer une audience retargeting "Visiteurs Estimation"',
                'description' => 'Dans Facebook Ads Manager : créez une audience personnalisée = visiteurs de votre page estimation des 30 derniers jours qui n\'ont PAS rempli le formulaire. Ce sont des vendeurs intéressés mais pas encore convertis.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Cette audience est en or : ces gens ont déjà montré leur intention de vendre. Ils vous connaissent. Il suffit d\'un rappel.'
            ],
            [
                'id' => 'A5-3',
                'title' => 'Créer 3 publicités de retargeting',
                'description' => 'Pub 1 : Témoignage vidéo d\'un client vendeur satisfait. Pub 2 : "X biens vendus ce mois-ci dans votre quartier". Pub 3 : Rappel de votre offre d\'estimation gratuite avec une urgence.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Budget retargeting = 3-5€/jour. C\'est le meilleur ROI possible en publicité car l\'audience est déjà qualifiée.'
            ],
            [
                'id' => 'A5-4',
                'title' => 'Configurer la newsletter mensuelle vendeur',
                'description' => 'Un email par mois à tous vos leads vendeurs non convertis : évolution des prix dans leur quartier, biens vendus récemment, conseils de préparation à la vente, témoignages.',
                'type' => 'action',
                'module_link' => '?page=emails',
                'tips' => 'La newsletter maintient le lien sans être intrusive. Certains vendeurs mettent 6-12 mois à se décider. Soyez patient et régulier.'
            ],
            [
                'id' => 'A5-5',
                'title' => 'Mesurer et optimiser votre tunnel vendeur',
                'description' => 'Après 30 jours, faites le bilan : combien de visiteurs sur la page estimation ? Taux de conversion du formulaire ? Taux de prise de RDV ? Taux de signature de mandat ? Identifiez le maillon faible.',
                'type' => 'validation',
                'module_link' => '?page=analytics',
                'tips' => 'Concentrez-vous sur le taux le plus faible. Si beaucoup visitent mais peu remplissent → problème de page. Si beaucoup remplissent mais peu de RDV → problème de relance.'
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