-- ============================================================
-- MODULE : NeuroPersona (neuropersona)
-- Fichier : neuropersona.sql
-- Généré le : 2026-02-12
-- Tables existantes : 4
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : neuropersona_campagnes
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `neuropersona_campagnes`
--

CREATE TABLE IF NOT EXISTS `neuropersona_campagnes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_type_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `canal` varchar(50) NOT NULL,
  `objectif` varchar(50) DEFAULT 'leads',
  `duree` int(11) DEFAULT 30,
  `frequence` int(11) DEFAULT 3,
  `contexte` text DEFAULT NULL,
  `contenu` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenu`)),
  `statut` enum('brouillon','planifiee','active','terminee','archivee') DEFAULT 'brouillon',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : neuropersona_config
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `neuropersona_config`
--

CREATE TABLE IF NOT EXISTS `neuropersona_config` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_type_id` int(11) NOT NULL,
  `priorite` int(11) DEFAULT 1,
  `objectif_mensuel` int(11) DEFAULT 10,
  `notes` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : neuropersona_posts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `neuropersona_posts`
--

CREATE TABLE IF NOT EXISTS `neuropersona_posts` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `numero` int(11) NOT NULL,
  `date_publication` date DEFAULT NULL,
  `heure_publication` time DEFAULT NULL,
  `contenu` text NOT NULL,
  `hashtags` text DEFAULT NULL,
  `cta` varchar(255) DEFAULT NULL,
  `media_type` enum('texte','image','video','carousel') DEFAULT 'texte',
  `media_url` varchar(500) DEFAULT NULL,
  `statut` enum('brouillon','planifie','publie','archive') DEFAULT 'brouillon',
  `stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`stats`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : neuropersona_types
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `neuropersona_types`
--

CREATE TABLE IF NOT EXISTS `neuropersona_types` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('acheteur','vendeur') NOT NULL,
  `icone` varchar(10) DEFAULT '?',
  `couleur` varchar(7) DEFAULT '#6366f1',
  `age_moyen` varchar(50) DEFAULT NULL,
  `situation_familiale` varchar(100) DEFAULT NULL,
  `budget_moyen` varchar(100) DEFAULT NULL,
  `cycle_decision` varchar(100) DEFAULT NULL,
  `niveau_urgence` int(11) DEFAULT 5,
  `motivations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`motivations`)),
  `problemes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`problemes`)),
  `solutions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`solutions`)),
  `messages_cles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`messages_cles`)),
  `contenus_recommandes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenus_recommandes`)),
  `canaux_prioritaires` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`canaux_prioritaires`)),
  `description` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `ordre` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `neuropersona_types`
--

INSERT INTO `neuropersona_types` (`id`, `code`, `nom`, `categorie`, `icone`, `couleur`, `age_moyen`, `situation_familiale`, `budget_moyen`, `cycle_decision`, `niveau_urgence`, `motivations`, `problemes`, `solutions`, `messages_cles`, `contenus_recommandes`, `canaux_prioritaires`, `description`, `actif`, `ordre`, `created_at`, `updated_at`) VALUES
(1, 'primo_accedant', 'Primo-Accédant', 'acheteur', '🏠', '#10b981', '25-35 ans', 'Jeune couple ou célibataire', '150 000€ - 280 000€', '3 à 6 mois', 7, '[\"Devenir propriétaire pour la première fois\", \"Arrêter de payer un loyer à fonds perdus\", \"Se constituer un patrimoine\", \"Avoir un chez-soi personnalisable\", \"Bénéficier des aides (PTZ, Action Logement)\"]', '[\"Méconnaissance des étapes d\'achat\", \"Peur de faire une mauvaise affaire\", \"Difficulté à obtenir un financement\", \"Budget serré vs marché tendu\", \"Manque de temps pour les visites\"]', '[\"Accompagnement pédagogique étape par étape\", \"Explication claire du processus d\'achat\", \"Partenariats avec courtiers pour optimiser le financement\", \"Sélection de biens adaptés au budget\", \"Visites virtuelles pour gagner du temps\"]', '[\"Votre premier achat mérite un accompagnement expert\", \"Transformez votre loyer en patrimoine\", \"Le PTZ peut financer jusqu\'à 40% de votre projet\", \"Je vous guide pas à pas vers votre première propriété\"]', '[\"Guide complet du primo-accédant\", \"Simulateur PTZ et aides\", \"Checklist des étapes d\'achat\", \"FAQ questions fréquentes\", \"Témoignages de primo-accédants\"]', '[\"facebook\", \"instagram\", \"gmb\"]', 'Jeunes actifs ou couples qui souhaitent acheter leur premier bien immobilier. Très sensibles au budget et aux aides disponibles.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(2, 'famille_expansion', 'Famille en Expansion', 'acheteur', '👨‍👩‍👧‍👦', '#3b82f6', '30-45 ans', 'Couple avec enfants', '280 000€ - 450 000€', '2 à 4 mois', 8, '[\"Plus d\'espace pour les enfants\", \"Jardin ou extérieur\", \"Proximité des écoles\", \"Quartier calme et sécurisé\", \"Anticiper les besoins futurs\"]', '[\"Vendre avant d\'acheter ou l\'inverse ?\", \"Trouver le bon timing\", \"Coordonner déménagement et rentrée scolaire\", \"Budget travaux à prévoir\", \"Compromis taille/emplacement\"]', '[\"Accompagnement vente + achat coordonné\", \"Connaissance des secteurs familiaux\", \"Réseau artisans pour travaux\", \"Timing optimisé\", \"Visite des quartiers et écoles\"]', '[\"Plus d\'espace pour voir grandir vos enfants\", \"Les meilleurs quartiers famille du secteur\", \"Vendez et achetez en toute sérénité\", \"Votre maison familiale vous attend\"]', '[\"Guide des quartiers famille\", \"Comparatif écoles du secteur\", \"Checklist maison familiale\", \"Témoignages familles\", \"Calculateur budget famille\"]', '[\"facebook\", \"gmb\", \"instagram\"]', 'Familles avec enfants cherchant plus d\'espace, souvent pour passer d\'un appartement à une maison.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(3, 'investisseur_locatif', 'Investisseur Locatif', 'acheteur', '📈', '#8b5cf6', '35-55 ans', 'CSP+ avec patrimoine', '100 000€ - 300 000€', '1 à 3 mois', 6, '[\"Générer des revenus passifs\", \"Optimisation fiscale\", \"Constitution de patrimoine\", \"Préparation retraite\", \"Diversification des placements\"]', '[\"Trouver le bon rendement\", \"Gestion locative chronophage\", \"Risques d\'impayés\", \"Fiscalité complexe\", \"Marché concurrentiel\"]', '[\"Analyse rentabilité détaillée\", \"Partenariat gestion locative\", \"Sélection biens à fort potentiel\", \"Conseil fiscal personnalisé\", \"Veille marché proactive\"]', '[\"Investissez malin, pas seulement dans la pierre\", \"Rendement locatif optimisé sur ce secteur\", \"Votre patrimoine immobilier clé en main\", \"Les meilleures opportunités d\'investissement\"]', '[\"Simulateur rentabilité locative\", \"Guide fiscalité immobilière\", \"Analyse marché locatif local\", \"Comparatif dispositifs fiscaux\", \"ROI par quartier\"]', '[\"linkedin\", \"facebook\", \"gmb\"]', 'Investisseurs cherchant à placer leur argent dans l\'immobilier locatif avec une approche rationnelle et chiffrée.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(4, 'senior_transition', 'Senior en Transition', 'acheteur', '🏡', '#f59e0b', '55-70 ans', 'Couple ou veuf(ve)', '200 000€ - 400 000€', '3 à 6 mois', 5, '[\"Logement adapté au vieillissement\", \"Réduire l\'entretien\", \"Proximité commerces et santé\", \"Rapprochement des enfants\", \"Libérer du capital\"]', '[\"Attachement au logement actuel\", \"Peur du changement\", \"Complexité administrative\", \"Trouver un bien adapté\", \"Gestion des affaires d\'une vie\"]', '[\"Écoute et accompagnement bienveillant\", \"Sélection biens de plain-pied/ascenseur\", \"Aide au tri et déménagement\", \"Partenaires de confiance\", \"Rythme adapté\"]', '[\"Un nouveau chapitre de vie serein\", \"Votre confort est notre priorité\", \"Simplifiez votre quotidien\", \"Accompagnement personnalisé et humain\"]', '[\"Guide de la transition immobilière senior\", \"Checklist logement adapté\", \"Témoignages transitions réussies\", \"Services partenaires seniors\", \"FAQ senior\"]', '[\"gmb\", \"facebook\"]', 'Seniors souhaitant adapter leur logement à leur nouvelle étape de vie, souvent pour plus de praticité.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(5, 'expatrie_retour', 'Expatrié de Retour', 'acheteur', '✈️', '#06b6d4', '35-50 ans', 'Famille ou couple', '300 000€ - 600 000€', '1 à 4 mois', 9, '[\"Retrouver ses racines\", \"Scolariser les enfants en France\", \"Investir son épargne expatriation\", \"Qualité de vie française\", \"Proximité famille\"]', '[\"Méconnaissance du marché local actuel\", \"Achat à distance\", \"Délais serrés (mutation)\", \"Décalage horaire pour les échanges\", \"Réadaptation administrative\"]', '[\"Visites virtuelles détaillées\", \"Disponibilité horaires décalés\", \"Connaissance pointue du marché\", \"Accompagnement administratif\", \"Réactivité maximale\"]', '[\"Votre retour en France parfaitement accompagné\", \"Achetez sereinement depuis l\'étranger\", \"Expert du marché local pour expatriés\", \"Votre projet, notre priorité absolue\"]', '[\"Guide retour expatrié\", \"Marché immobilier actuel\", \"Checklist installation France\", \"Visites virtuelles 360°\", \"FAQ expatriés\"]', '[\"linkedin\", \"facebook\", \"instagram\"]', 'Français expatriés qui reviennent s\'installer en France, souvent avec un budget confortable mais peu de temps.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(6, 'vendeur_urgent', 'Vendeur Urgent', 'vendeur', '⚡', '#ef4444', '35-55 ans', 'Variable (divorce, mutation, décès)', 'Variable', '1 à 2 mois', 10, '[\"Vendre rapidement\", \"Débloquer une situation\", \"Éviter les frais supplémentaires\", \"Tourner la page\", \"Récupérer du cash\"]', '[\"Manque de temps\", \"Stress de la situation\", \"Peur de brader\", \"Coordination complexe\", \"Pression émotionnelle\"]', '[\"Estimation rapide et juste\", \"Mise en vente express\", \"Communication offensive\", \"Accompagnement humain\", \"Solutions créatives (portage, etc.)\"]', '[\"Vendez vite sans sacrifier le prix\", \"Votre urgence est ma priorité\", \"Accompagnement express et humain\", \"Solutions rapides pour situations complexes\"]', '[\"Guide vente rapide\", \"Checklist vente express\", \"Témoignages ventes rapides\", \"FAQ urgence\", \"Estimation express\"]', '[\"gmb\", \"facebook\"]', 'Vendeurs devant vendre rapidement suite à un événement de vie (divorce, mutation, succession, difficultés financières).', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(7, 'vendeur_serein', 'Vendeur Serein', 'vendeur', '🧘', '#22c55e', '45-65 ans', 'Propriétaire établi', 'Variable', '3 à 6 mois', 4, '[\"Obtenir le meilleur prix\", \"Vendre dans de bonnes conditions\", \"Trouver le bon acheteur\", \"Projet mûrement réfléchi\", \"Valoriser son bien\"]', '[\"Pas pressé = exigence élevée\", \"Attachement émotionnel au bien\", \"Comparaison avec les voisins\", \"Visites dérangeantes\", \"Peur des curieux\"]', '[\"Stratégie de valorisation\", \"Home staging conseillé\", \"Sélection rigoureuse des visiteurs\", \"Communication premium\", \"Patience et régularité\"]', '[\"Votre bien mérite une vente d\'exception\", \"Prenons le temps de bien faire\", \"Valorisation maximale de votre patrimoine\", \"Chaque visite compte\"]', '[\"Guide valorisation bien\", \"Conseils home staging\", \"Analyse prix marché\", \"Témoignages ventes réussies\", \"Checklist préparation vente\"]', '[\"gmb\", \"facebook\", \"instagram\"]', 'Vendeurs qui ont le temps et veulent maximiser leur prix de vente, souvent très attachés à leur bien.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(8, 'investisseur_sortant', 'Investisseur Sortant', 'vendeur', '💼', '#6366f1', '45-65 ans', 'Investisseur multi-biens', 'Variable', '2 à 4 mois', 6, '[\"Réaliser une plus-value\", \"Arbitrage patrimonial\", \"Réinvestir ailleurs\", \"Simplifier la gestion\", \"Optimiser fiscalement\"]', '[\"Timing fiscal\", \"Locataire en place\", \"Calcul plus-value complexe\", \"Trouver un investisseur acheteur\", \"Négociation rationnelle\"]', '[\"Analyse fiscale de la vente\", \"Recherche investisseurs qualifiés\", \"Gestion vente avec locataire\", \"Accompagnement notarial\", \"Conseil réinvestissement\"]', '[\"Optimisez votre arbitrage patrimonial\", \"Vendez votre investissement au bon moment\", \"Conseil expert pour investisseurs\", \"Plus-value maximisée, fiscalité optimisée\"]', '[\"Guide vente bien locatif\", \"Simulateur plus-value\", \"Stratégies arbitrage\", \"FAQ investisseur vendeur\", \"Analyse marché investissement\"]', '[\"linkedin\", \"gmb\"]', 'Propriétaires bailleurs souhaitant vendre un bien d\'investissement, approche très rationnelle et chiffrée.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(9, 'heritier', 'Héritier', 'vendeur', '📜', '#a855f7', '40-60 ans', 'Héritier(s) seul ou en indivision', 'Variable', '2 à 6 mois', 7, '[\"Régler la succession\", \"Partager équitablement\", \"Se libérer de la gestion\", \"Éviter les conflits familiaux\", \"Récupérer sa part\"]', '[\"Indivision complexe\", \"Décisions collectives\", \"État du bien (parfois vétuste)\", \"Attachement émotionnel\", \"Délais succession\"]', '[\"Médiation familiale si besoin\", \"Accompagnement indivision\", \"Solutions pour bien en l\'état\", \"Estimation objective\", \"Patience et diplomatie\"]', '[\"Accompagnement bienveillant des successions\", \"Vendez sereinement en indivision\", \"Votre héritage entre de bonnes mains\", \"Solutions adaptées à chaque situation familiale\"]', '[\"Guide vente succession\", \"FAQ indivision\", \"Checklist succession\", \"Témoignages successions\", \"Partenaires notaires\"]', '[\"gmb\", \"facebook\"]', 'Héritiers devant vendre un bien de famille, souvent en indivision avec plusieurs décisionnaires.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56'),
(10, 'proprio_fatigue', 'Propriétaire Locatif Fatigué', 'vendeur', '😩', '#f97316', '50-70 ans', 'Propriétaire bailleur', 'Variable', '1 à 3 mois', 8, '[\"Arrêter les soucis locatifs\", \"Récupérer sa tranquillité\", \"Placer autrement son argent\", \"Éviter les impayés futurs\", \"Simplifier sa vie\"]', '[\"Locataire difficile\", \"Travaux à faire\", \"Rentabilité en baisse\", \"Réglementation contraignante\", \"Épuisement moral\"]', '[\"Vente avec locataire en place\", \"Solutions locataire sortant\", \"Estimation réaliste\", \"Accompagnement serein\", \"Conseil réinvestissement alternatif\"]', '[\"Récupérez votre sérénité\", \"Fini les soucis de gestion locative\", \"Vendez votre locatif l\'esprit tranquille\", \"Il est temps de passer à autre chose\"]', '[\"Guide sortie locatif\", \"Droits et devoirs bailleur vendeur\", \"Solutions locataire\", \"Alternatives placement\", \"Témoignages libération\"]', '[\"gmb\", \"facebook\"]', 'Propriétaires bailleurs épuisés par la gestion locative qui souhaitent vendre pour retrouver leur tranquillité.', 1, 0, '2026-01-29 02:17:56', '2026-01-29 02:17:56');


