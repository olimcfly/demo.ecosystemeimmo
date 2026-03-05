-- ============================================================
-- MODULE : Launchpad (launchpad)
-- Fichier : launchpad.sql
-- Généré le : 2026-02-12
-- Tables existantes : 11
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : launchpad_ai_generations
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_ai_generations`
--

CREATE TABLE IF NOT EXISTS `launchpad_ai_generations` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `step` int(11) NOT NULL,
  `type` enum('promesse','offre','strategie','plan') NOT NULL,
  `prompt` text NOT NULL,
  `response` longtext NOT NULL,
  `tokens_used` int(11) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_conversions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_conversions`
--

CREATE TABLE IF NOT EXISTS `launchpad_conversions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `step_completed` int(11) DEFAULT NULL,
  `conversion_type` enum('plan_downloaded','formation_started','coaching_booked','autre') DEFAULT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `converted_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_metiers_library
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_metiers_library`
--

CREATE TABLE IF NOT EXISTS `launchpad_metiers_library` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `personas_recommandes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`personas_recommandes`)),
  `strategies_recommandes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`strategies_recommandes`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `launchpad_metiers_library`
--

INSERT INTO `launchpad_metiers_library` (`id`, `code`, `label`, `description`, `personas_recommandes`, `strategies_recommandes`, `created_at`) VALUES
(1, 'agent', 'Agent Immobilier', NULL, '[\"vendeur_presse\", \"vendeur_patrimonial\", \"acheteur_primo\"]', NULL, '2026-01-24 00:52:35'),
(2, 'mandataire', 'Mandataire', NULL, '[\"vendeur_presse\", \"investisseur\"]', NULL, '2026-01-24 00:52:35'),
(3, 'promoteur', 'Promoteur', NULL, '[\"acheteur_primo\", \"investisseur\"]', NULL, '2026-01-24 00:52:35'),
(4, 'chasseur', 'Chasseur Immobilier', NULL, '[\"vendeur_presse\", \"acheteur_primo\"]', NULL, '2026-01-24 00:52:35'),
(5, 'investisseur', 'Investisseur', NULL, '[\"investisseur\"]', NULL, '2026-01-24 00:52:35');


-- ------------------------------------------------------------
-- Table : launchpad_personas_library
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_personas_library`
--

CREATE TABLE IF NOT EXISTS `launchpad_personas_library` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `freins_defaut` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`freins_defaut`)),
  `desirs_defaut` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`desirs_defaut`)),
  `declencheurs_defaut` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`declencheurs_defaut`)),
  `contenu_recommande` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenu_recommande`)),
  `canal_recommande` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `launchpad_personas_library`
--

INSERT INTO `launchpad_personas_library` (`id`, `code`, `label`, `description`, `freins_defaut`, `desirs_defaut`, `declencheurs_defaut`, `contenu_recommande`, `canal_recommande`, `created_at`) VALUES
(1, 'vendeur_presse', 'Vendeur Pressé', 'Situation urgente, besoin de vendre rapidement', NULL, NULL, NULL, NULL, NULL, '2026-01-24 00:52:35'),
(2, 'vendeur_patrimonial', 'Vendeur Patrimonial', 'Bien de famille, vente importante, réflexion', NULL, NULL, NULL, NULL, NULL, '2026-01-24 00:52:35'),
(3, 'acheteur_primo', 'Acheteur Primo-Accédant', 'Premier achat, peu d\'expérience, beaucoup de questions', NULL, NULL, NULL, NULL, NULL, '2026-01-24 00:52:35'),
(4, 'investisseur', 'Investisseur', 'Recherche rentabilité, analyse comparée, besoin chiffres', NULL, NULL, NULL, NULL, NULL, '2026-01-24 00:52:35');


-- ------------------------------------------------------------
-- Table : launchpad_sessions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_sessions`
--

CREATE TABLE IF NOT EXISTS `launchpad_sessions` (
  `id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','completed','abandoned') DEFAULT 'active',
  `current_step` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_step1_profil
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_step1_profil`
--

CREATE TABLE IF NOT EXISTS `launchpad_step1_profil` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `metier` enum('agent','mandataire','promoteur','chasseur','investisseur','autre') NOT NULL,
  `zone_geo` varchar(255) NOT NULL,
  `zone_rayon_km` int(11) DEFAULT NULL,
  `experience_level` enum('débutant','intermédiaire','expert') NOT NULL,
  `objectif_principal` varchar(255) NOT NULL,
  `secteurs_interets` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`secteurs_interets`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_step2_persona
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_step2_persona`
--

CREATE TABLE IF NOT EXISTS `launchpad_step2_persona` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `persona_choisi` enum('vendeur_pressé','vendeur_patrimonial','acheteur_primo','investisseur') NOT NULL,
  `profondeur_conscience` enum('faible','moyen','élevé') NOT NULL,
  `freins` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`freins`)),
  `desirs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`desirs`)),
  `declencheurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`declencheurs`)),
  `persona_secondaires` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`persona_secondaires`)),
  `notes` text DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_step3_offre
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_step3_offre`
--

CREATE TABLE IF NOT EXISTS `launchpad_step3_offre` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `promesse` longtext NOT NULL,
  `offre_principale` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`offre_principale`)),
  `offres_complementaires` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`offres_complementaires`)),
  `version` int(11) DEFAULT 1,
  `validated_at` timestamp NULL DEFAULT NULL,
  `user_modifications` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_step4_strategie
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_step4_strategie`
--

CREATE TABLE IF NOT EXISTS `launchpad_step4_strategie` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `trafic_channel` enum('organic_local','facebook_ads','google_ads','hybrid','autre') NOT NULL,
  `justification_canal` longtext DEFAULT NULL,
  `contenus_recommandes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`contenus_recommandes`)),
  `pages_a_creer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pages_a_creer`)),
  `budget_estimé` int(11) DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_step5_plan
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_step5_plan`
--

CREATE TABLE IF NOT EXISTS `launchpad_step5_plan` (
  `id` int(11) NOT NULL,
  `session_id` varchar(36) NOT NULL,
  `cahier_strategique` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`cahier_strategique`)),
  `pdf_url` varchar(255) DEFAULT NULL,
  `next_action_concrete` longtext DEFAULT NULL,
  `next_action_date` date DEFAULT NULL,
  `export_count` int(11) DEFAULT 0,
  `last_export_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : launchpad_tasks
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `launchpad_tasks`
--

CREATE TABLE IF NOT EXISTS `launchpad_tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Titre de la tâche',
  `description` text DEFAULT NULL COMMENT 'Description détaillée',
  `category` enum('configuration','marketing','contenu','seo','reseaux','juridique','autre') DEFAULT 'autre',
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `completed` tinyint(1) DEFAULT 0 COMMENT '1=terminé, 0=en cours',
  `completed_at` datetime DEFAULT NULL,
  `due_date` date DEFAULT NULL COMMENT 'Date limite',
  `link_module` varchar(50) DEFAULT NULL COMMENT 'Module admin lié',
  `link_page` varchar(50) DEFAULT NULL COMMENT 'Page admin liée',
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `launchpad_tasks`
--

INSERT INTO `launchpad_tasks` (`id`, `title`, `description`, `category`, `priority`, `completed`, `completed_at`, `due_date`, `link_module`, `link_page`, `display_order`, `created_at`, `updated_at`) VALUES
(1, 'Configurer le profil agence', 'Renseigner les informations de base : nom, adresse, téléphone, email, logo', 'configuration', 'high', 0, NULL, NULL, 'settings', 'index', 1, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(2, 'Créer la fiche Google My Business', 'Configurer et optimiser votre fiche GMB pour le référencement local', 'seo', 'high', 0, NULL, NULL, 'gmb', 'index', 2, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(3, 'Ajouter les pages principales', 'Créer les pages Accueil, À propos, Services, Contact', 'contenu', 'high', 0, NULL, NULL, 'cms-pages', 'ajouter', 3, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(4, 'Créer une page de capture', 'Mettre en place une landing page avec formulaire pour générer des leads', 'marketing', 'high', 0, NULL, NULL, 'captures', 'ajouter', 4, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(5, 'Connecter les réseaux sociaux', 'Lier vos comptes Facebook, Instagram, LinkedIn', 'reseaux', 'medium', 0, NULL, NULL, 'settings', 'index', 5, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(6, 'Publier le premier article', 'Rédiger un article de blog optimisé SEO', 'contenu', 'medium', 0, NULL, NULL, 'articles', 'ajouter', 6, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(7, 'Ajouter les premiers biens', 'Importer ou créer vos annonces immobilières', 'contenu', 'high', 0, NULL, NULL, 'biens', 'ajouter', 7, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(8, 'Définir le persona client', 'Identifier et documenter votre client idéal', 'marketing', 'medium', 0, NULL, NULL, 'strategy', 'index', 8, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(9, 'Configurer les mentions légales', 'Ajouter SIRET, RCS, carte pro, garantie financière', 'juridique', 'high', 0, NULL, NULL, 'settings', 'index', 9, '2026-01-12 03:47:56', '2026-01-12 03:47:56'),
(10, 'Installer Google Analytics', 'Configurer le suivi des statistiques du site', 'seo', 'medium', 0, NULL, NULL, 'settings', 'index', 10, '2026-01-12 03:47:56', '2026-01-12 03:47:56');


