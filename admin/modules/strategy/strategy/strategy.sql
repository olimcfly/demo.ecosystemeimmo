-- ============================================================
-- MODULE : Stratégie Marketing (strategy)
-- Fichier : strategy.sql
-- Généré le : 2026-02-12
-- Tables existantes : 6
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : strategy_canaux
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_canaux`
--

CREATE TABLE IF NOT EXISTS `strategy_canaux` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `type` enum('email','seo','gmb','social','ads','autre') NOT NULL,
  `description` text DEFAULT NULL,
  `cout_relatif` enum('gratuit','faible','moyen','eleve') DEFAULT 'faible',
  `effort_relatif` enum('faible','moyen','eleve') DEFAULT 'moyen',
  `icon` varchar(50) DEFAULT NULL,
  `couleur` varchar(7) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : strategy_communications
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_communications`
--

CREATE TABLE IF NOT EXISTS `strategy_communications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `sujet_id` int(11) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `canal` varchar(100) DEFAULT NULL,
  `type_action` enum('organique','payant','mixte') DEFAULT 'organique',
  `contexte` enum('discovery','consideration','conversion','retention','advocacy') DEFAULT 'discovery',
  `audience_specifique` varchar(255) DEFAULT NULL,
  `message_principal` text DEFAULT NULL,
  `appel_action` varchar(255) DEFAULT NULL,
  `frequence` varchar(100) DEFAULT NULL,
  `budget_estime` varchar(100) DEFAULT NULL,
  `kpis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`kpis`)),
  `statut` enum('draft','active','pause','archive') DEFAULT 'draft',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : strategy_mapping
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_mapping`
--

CREATE TABLE IF NOT EXISTS `strategy_mapping` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `sujet_id` int(11) DEFAULT NULL,
  `offre_id` int(11) DEFAULT NULL,
  `communication_id` int(11) DEFAULT NULL,
  `structure_id` int(11) DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : strategy_offres
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_offres`
--

CREATE TABLE IF NOT EXISTS `strategy_offres` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `valeur_principale` text DEFAULT NULL,
  `avantages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`avantages`)),
  `diferenciateurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`diferenciateurs`)),
  `prix_ou_cout` varchar(100) DEFAULT NULL,
  `conditions` text DEFAULT NULL,
  `urgence` varchar(100) DEFAULT NULL,
  `garanties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`garanties`)),
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : strategy_structures
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_structures`
--

CREATE TABLE IF NOT EXISTS `strategy_structures` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `communication_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_format` enum('email','landing','blog','video','infographie','lead_magnet','case_study','webinaire','sms','autre') NOT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  `structure_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`structure_json`)),
  `elements_cles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`elements_cles`)),
  `timing` varchar(100) DEFAULT NULL,
  `duree_vie` varchar(100) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `statut` enum('draft','active','archive') DEFAULT 'draft',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : strategy_sujets
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `strategy_sujets`
--

CREATE TABLE IF NOT EXISTS `strategy_sujets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `persona_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pertinence` enum('haute','moyenne','basse') DEFAULT 'moyenne',
  `intent` enum('informatif','commercial','transactionnel','navigationnel') DEFAULT 'informatif',
  `mots_cles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`mots_cles`)),
  `questions_cibles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`questions_cibles`)),
  `position_actuelle` varchar(100) DEFAULT NULL,
  `position_cible` int(11) DEFAULT NULL,
  `volume_recherche` int(11) DEFAULT NULL,
  `competitivite` varchar(50) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


