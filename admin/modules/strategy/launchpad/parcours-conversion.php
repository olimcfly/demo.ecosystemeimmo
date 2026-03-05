<?php
/**
 * PARCOURS C — CONVERSION & COPY
 * /admin/modules/launchpad/parcours-conversion.php
 * 
 * 5 Étapes pour transformer vos visiteurs en leads qualifiés
 * Accessible via ?page=parcours-conversion
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
// RÉCUPÉRER LA PROGRESSION
// ══════════════════════════════════════════════
$progression = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM parcours_progression WHERE user_id = ? AND parcours_id = 'C'");
    $stmt->execute([$user_id]);
    $progression = $stmt->fetch() ?: [];
} catch (Exception $e) {}

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
                parcours_id VARCHAR(5) NOT NULL DEFAULT 'C',
                completed_steps JSON,
                current_step INT DEFAULT 1,
                started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_parcours (user_id, parcours_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $stmt = $pdo->prepare("INSERT INTO parcours_progression (user_id, parcours_id, completed_steps, current_step) 
                VALUES (?, 'C', ?, ?)
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
$parcours_id = 'C';
$parcours_name = 'Conversion & Copy';
$parcours_emoji = '🎯';
$parcours_color = '#f59e0b';
$parcours_gradient = 'linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%)';

$etapes = [
    1 => [
        'title' => 'Audit de Conversion',
        'emoji' => '🔍',
        'subtitle' => 'Identifier pourquoi vos visiteurs ne convertissent pas',
        'duration' => '1h',
        'color' => '#f59e0b',
        'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
        'description' => 'Vous avez du trafic mais peu de leads ? Le problème est dans votre tunnel de conversion. Avant de changer quoi que ce soit, il faut diagnostiquer précisément OÙ les visiteurs décrochent et POURQUOI.',
        'actions' => [
            [
                'id' => 'C1-1',
                'title' => 'Analyser le taux de rebond de vos pages clés',
                'description' => 'Vérifiez dans Google Analytics (ou votre outil) : page d\'accueil, page estimation, pages secteurs. Un taux > 70% = problème de pertinence ou de vitesse.',
                'type' => 'reflexion',
                'module_link' => '?page=analytics',
                'tips' => 'Si vous n\'avez pas encore Analytics, installez-le maintenant. Sans données, vous pilotez à l\'aveugle.'
            ],
            [
                'id' => 'C1-2',
                'title' => 'Lister vos CTA actuels et leur performance',
                'description' => 'Faites l\'inventaire de tous vos appels à l\'action : boutons, formulaires, liens. Notez combien de clics/soumissions pour chacun.',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'Pas de CTA = pas de conversion. Si votre page n\'a qu\'un seul CTA tout en bas, c\'est votre problème #1.'
            ],
            [
                'id' => 'C1-3',
                'title' => 'Tester le parcours utilisateur vous-même',
                'description' => 'Ouvrez votre site en navigation privée, sur mobile. Chronométrez : combien de temps pour trouver le formulaire de contact ? Combien de clics ? Le formulaire fonctionne-t-il ?',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'Faites aussi le test avec 3 personnes de votre entourage non-tech. Leurs frictions sont celles de vos prospects.'
            ],
            [
                'id' => 'C1-4',
                'title' => 'Identifier les 3 pages à optimiser en priorité',
                'description' => 'Classez vos pages par impact potentiel : la page avec le plus de trafic ET le moins de conversion = priorité #1.',
                'type' => 'reflexion',
                'module_link' => '?page=analytics',
                'tips' => 'En immobilier, les pages les plus rentables sont souvent : estimation, secteur/quartier, et les landing pages d\'offre.'
            ]
        ]
    ],
    2 => [
        'title' => 'Copywriting MERE',
        'emoji' => '✍️',
        'subtitle' => 'Maîtriser le framework Magnétique / Émotion / Raison / Engagement',
        'duration' => '2h',
        'color' => '#ec4899',
        'gradient' => 'linear-gradient(135deg, #ec4899, #db2777)',
        'description' => 'Le copywriting est l\'art de transformer des mots en actions. Le framework MERE (Magnétique → Émotion → Raison → Engagement) est votre structure pour tout : pages, emails, posts, scripts. Chaque élément de texte sur votre site doit suivre cette logique.',
        'actions' => [
            [
                'id' => 'C2-1',
                'title' => 'Comprendre le framework MERE',
                'description' => 'M = Magnétique (titre qui arrête le scroll). E = Émotion (toucher le cœur/la peur/le désir). R = Raison (preuves, chiffres, logique). E = Engagement (CTA clair et irrésistible).',
                'type' => 'reflexion',
                'module_link' => '?page=strategy',
                'tips' => 'Chaque section de vos pages doit avoir ces 4 éléments. Si un manque, la conversion chute.'
            ],
            [
                'id' => 'C2-2',
                'title' => 'Réécrire votre titre principal (Hero)',
                'description' => 'Votre titre doit être Magnétique : spécifique, orienté bénéfice, avec un élément de curiosité. Ex: "Vendez votre bien au meilleur prix en 45 jours — Estimation gratuite en 2 minutes".',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Testez 3 versions de titres. Le bon titre peut doubler votre taux de conversion à lui seul.'
            ],
            [
                'id' => 'C2-3',
                'title' => 'Créer votre bloc Émotion',
                'description' => 'Section qui parle des frustrations de votre prospect : "Vous en avez assez de...", "Vous méritez un accompagnement qui...". Utilisez SES mots, pas du jargon immobilier.',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Reprenez les pain points de votre persona (Launchpad étape 2). Les mots de vos clients = les meilleurs mots pour vendre.'
            ],
            [
                'id' => 'C2-4',
                'title' => 'Ajouter vos blocs Raison (preuves sociales)',
                'description' => 'Témoignages clients, nombre de ventes, durée moyenne de vente, note Google, logos partenaires. Chaque preuve réduit l\'anxiété du prospect.',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Minimum 3 témoignages avec prénom + photo + résultat concret. "Vendu en 23 jours" > "Super agent".'
            ],
            [
                'id' => 'C2-5',
                'title' => 'Optimiser vos CTA (Engagement)',
                'description' => 'Chaque CTA doit dire exactement ce qui va se passer : "Recevoir mon estimation gratuite" > "Envoyer". Ajoutez une micro-garantie : "Sans engagement • Résultat en 24h".',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'Placez un CTA visible sans scroller (above the fold). Couleur contrastée. Texte orienté bénéfice, pas action technique.'
            ]
        ]
    ],
    3 => [
        'title' => 'Landing Pages Optimisées',
        'emoji' => '🚀',
        'subtitle' => 'Créer des pages qui convertissent à 15%+',
        'duration' => '2h30',
        'color' => '#6366f1',
        'gradient' => 'linear-gradient(135deg, #6366f1, #4f46e5)',
        'description' => 'Une landing page n\'est pas une page de votre site — c\'est une page avec UN seul objectif : convertir le visiteur en lead. Pas de menu, pas de distractions, juste votre offre et un formulaire. Le taux moyen en immobilier est 3-5%. Votre objectif : 10-15%.',
        'actions' => [
            [
                'id' => 'C3-1',
                'title' => 'Créer la landing "Estimation Gratuite"',
                'description' => 'La landing #1 en immobilier : Hero avec promesse + formulaire court (adresse, type, surface, email, téléphone). Structure MERE complète en dessous.',
                'type' => 'action',
                'module_link' => '?page=pages-capture&action=create',
                'tips' => 'Formulaire en 2 étapes : étape 1 = adresse + type (facile), étape 2 = coordonnées. Le multi-step augmente les conversions de 30%.'
            ],
            [
                'id' => 'C3-2',
                'title' => 'Créer la landing "Guide Acheteur/Vendeur"',
                'description' => 'Offre de valeur gratuite en échange de l\'email. Hero : "Téléchargez le guide des X erreurs à éviter". Formulaire minimaliste : prénom + email.',
                'type' => 'action',
                'module_link' => '?page=pages-capture&action=create',
                'tips' => 'La landing guide a un taux de conversion plus élevé (15-25%) car la barrière d\'engagement est plus faible que l\'estimation.'
            ],
            [
                'id' => 'C3-3',
                'title' => 'Ajouter les éléments de confiance',
                'description' => 'Sur chaque landing : badge "100% gratuit", nombre de clients satisfaits, logo réseau, avis Google, photo professionnelle. Chaque élément réduit la friction.',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'La photo de vous en action (pas en costume/studio) convertit mieux. Les gens achètent à des humains, pas à des logos.'
            ],
            [
                'id' => 'C3-4',
                'title' => 'Optimiser la vitesse de chargement',
                'description' => 'Testez sur PageSpeed Insights (Google). Score mobile < 50 = vous perdez 40% de vos visiteurs. Compressez les images, réduisez le code.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'En immobilier mobile-first : 70% du trafic vient du smartphone. Si votre page met plus de 3 secondes, c\'est terminé.'
            ],
            [
                'id' => 'C3-5',
                'title' => 'Configurer le tracking de conversion',
                'description' => 'Installez le suivi : Google Analytics (événement formulaire), Facebook Pixel (si ads), et vérifiez que chaque soumission est bien trackée.',
                'type' => 'validation',
                'module_link' => '?page=analytics',
                'tips' => 'Sans tracking, vous ne saurez jamais ce qui marche. Même basique, un compteur de formulaires soumis suffit pour commencer.'
            ]
        ]
    ],
    4 => [
        'title' => 'Formulaires & CTA Avancés',
        'emoji' => '📝',
        'subtitle' => 'Maximiser chaque point de contact avec vos visiteurs',
        'duration' => '1h30',
        'color' => '#14b8a6',
        'gradient' => 'linear-gradient(135deg, #14b8a6, #0d9488)',
        'description' => 'Chaque page de votre site est une opportunité de conversion. Les formulaires et CTA ne doivent pas être un ajout — ils sont l\'objectif. Cette étape vous apprend à placer les bons formulaires aux bons endroits avec les bons mots.',
        'actions' => [
            [
                'id' => 'C4-1',
                'title' => 'Créer 3 types de formulaires adaptés',
                'description' => 'Court (email seul = newsletter), Moyen (email + téléphone + type projet = estimation), Long (complet = demande de RDV). Chaque page reçoit le formulaire adapté à l\'intention.',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'Page blog → formulaire court. Page estimation → moyen. Page "Nous contacter" → long. Ne mettez jamais un formulaire long sur un article de blog.'
            ],
            [
                'id' => 'C4-2',
                'title' => 'Ajouter des pop-ups d\'intention de sortie',
                'description' => 'Quand le visiteur s\'apprête à partir : pop-up avec offre de valeur. "Attendez ! Téléchargez gratuitement notre guide avant de partir". Taux de récupération : 5-10% des sorties.',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'Maximum 1 pop-up par session. Délai de 30 secondes minimum. Sur mobile : bannière en bas plutôt que pop-up intrusif.'
            ],
            [
                'id' => 'C4-3',
                'title' => 'Optimiser les messages de confirmation',
                'description' => 'Après soumission du formulaire : page de remerciement avec prochaine étape claire. "Merci ! Vous recevrez votre estimation sous 24h. En attendant, découvrez nos biens."',
                'type' => 'action',
                'module_link' => '?page=pages-capture',
                'tips' => 'La page de confirmation est une opportunité cachée. Proposez-y votre guide, vos réseaux sociaux, ou un bouton de prise de RDV.'
            ],
            [
                'id' => 'C4-4',
                'title' => 'Intégrer le chat/WhatsApp',
                'description' => 'Ajoutez un bouton WhatsApp flottant sur votre site. Les prospects immobiliers préfèrent souvent le message rapide au formulaire.',
                'type' => 'action',
                'module_link' => null,
                'tips' => 'WhatsApp convertit 3x mieux que le formulaire classique en immobilier. Répondez en moins de 5 minutes pour maximiser la conversion.'
            ],
            [
                'id' => 'C4-5',
                'title' => 'A/B tester vos éléments clés',
                'description' => 'Testez 2 versions de votre page la plus visitée : changez UN élément (titre OU CTA OU image). Mesurez pendant 2 semaines. Gardez le gagnant.',
                'type' => 'validation',
                'module_link' => '?page=analytics',
                'tips' => 'Ne testez qu\'un seul élément à la fois. Commencez par le titre — c\'est ce qui a le plus d\'impact sur la conversion.'
            ]
        ]
    ],
    5 => [
        'title' => 'Preuves Sociales & Garanties',
        'emoji' => '⭐',
        'subtitle' => 'Éliminer les dernières objections de vos prospects',
        'duration' => '1h30',
        'color' => '#ef4444',
        'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)',
        'description' => 'Le prospect est presque convaincu mais hésite encore. Les preuves sociales et garanties sont ce qui fait basculer la décision. C\'est la différence entre "intéressant" et "je remplis le formulaire maintenant".',
        'actions' => [
            [
                'id' => 'C5-1',
                'title' => 'Collecter et publier 5 témoignages clients',
                'description' => 'Contactez vos 5 derniers clients satisfaits. Demandez un avis écrit avec résultat concret : "Vendu en X jours", "Prix obtenu : +X% par rapport à l\'estimation initiale".',
                'type' => 'action',
                'module_link' => '?page=contact',
                'tips' => 'Le meilleur moment pour demander un avis : le jour de la signature. Envoyez un SMS avec le lien Google Avis dans l\'heure.'
            ],
            [
                'id' => 'C5-2',
                'title' => 'Créer vos études de cas',
                'description' => 'Transformez 2-3 ventes en études de cas : situation initiale → problème → votre intervention → résultat. Format : 500 mots + photos avant/après si possible.',
                'type' => 'action',
                'module_link' => '?page=articles&action=create',
                'tips' => 'Une étude de cas vendeur + une étude de cas acheteur. Le prospect se projette dans l\'histoire de quelqu\'un qui lui ressemble.'
            ],
            [
                'id' => 'C5-3',
                'title' => 'Formuler votre garantie de service',
                'description' => 'Ex: "Estimation gratuite sous 24h ou je vous offre un diagnostic complet", "Accompagnement de A à Z — je m\'occupe de tout", "Mandat résiliable à tout moment".',
                'type' => 'reflexion',
                'module_link' => null,
                'tips' => 'La garantie n\'a pas besoin d\'être financière. Elle doit réduire le risque perçu : "Si vous n\'êtes pas satisfait, vous êtes libre".'
            ],
            [
                'id' => 'C5-4',
                'title' => 'Ajouter les preuves sur toutes les pages clés',
                'description' => 'Intégrez témoignages, études de cas, garantie et chiffres clés (nombre de ventes, note Google, années d\'expérience) sur vos landing pages, page d\'accueil et page estimation.',
                'type' => 'action',
                'module_link' => '?page=builder-pages',
                'tips' => 'Les chiffres ronds sont moins crédibles. "147 familles accompagnées" est plus convaincant que "150 ventes".'
            ],
            [
                'id' => 'C5-5',
                'title' => 'Vérifier le parcours complet',
                'description' => 'Faites le test final : un visiteur arrive sur votre page → lit le titre → voit les preuves → comprend la garantie → remplit le formulaire. Chaque étape est fluide ?',
                'type' => 'validation',
                'module_link' => null,
                'tips' => 'Demandez à un ami de naviguer votre site pendant que vous regardez. Ne l\'aidez pas. Notez chaque hésitation.'
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