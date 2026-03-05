-- ============================================================
-- MODULE : Pages de Capture (captures)
-- Fichier : captures.sql
-- Généré le : 2026-02-12
-- Tables existantes : 5
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : captures
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `captures`
--

CREATE TABLE IF NOT EXISTS `captures` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `guide_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`guide_ids`)),
  `description` text DEFAULT NULL,
  `type` enum('estimation','contact','newsletter','guide') DEFAULT 'contact',
  `template` varchar(50) DEFAULT 'simple',
  `contenu` text DEFAULT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `sous_titre` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `cta_text` varchar(100) DEFAULT NULL,
  `champs_formulaire` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`champs_formulaire`)),
  `page_merci_url` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `actif` tinyint(1) DEFAULT 1,
  `vues` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `taux_conversion` decimal(5,2) DEFAULT 0.00,
  `last_conversion_at` datetime DEFAULT NULL,
  `status` enum('active','inactive','archived') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `captures`
--

INSERT INTO `captures` (`id`, `titre`, `slug`, `guide_ids`, `description`, `type`, `template`, `contenu`, `headline`, `sous_titre`, `image_url`, `cta_text`, `champs_formulaire`, `page_merci_url`, `active`, `actif`, `vues`, `conversions`, `created_at`, `updated_at`, `taux_conversion`, `last_conversion_at`, `status`) VALUES
(1, 'demande de devis', 'demande-de-devis', NULL, 'testt', 'guide', 'simple', '', 'Titre principal (Headline)', 'Sous-titre\r\n', '', 'Demander mon estimation', NULL, '/merci', 1, 1, 0, 0, '2025-12-09 04:28:42', '2025-12-09 04:28:42', 0.00, NULL, 'active'),
(2, 'test', 'test', NULL, NULL, 'contact', 'simple', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 0, '2026-02-01 01:29:53', '2026-02-01 01:29:53', 0.00, NULL, 'active'),
(4, 'test', 'test-2', NULL, NULL, 'contact', 'simple', '', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 0, '2026-02-01 01:52:22', '2026-02-01 01:52:22', 0.00, NULL, 'active'),
(5, 'test', 'test-3', NULL, NULL, 'contact', 'simple', '', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 0, '2026-02-01 01:59:29', '2026-02-01 01:59:29', 0.00, NULL, 'active'),
(6, 'test', 'test-4', NULL, NULL, 'contact', 'simple', '', NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, 0, '2026-02-01 01:59:53', '2026-02-01 01:59:53', 0.00, NULL, 'active');


-- ------------------------------------------------------------
-- Table : captures_stats
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `captures_stats`
--

CREATE TABLE IF NOT EXISTS `captures_stats` (
  `id` int(11) NOT NULL,
  `capture_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `vues` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `taux_conversion` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : capture_pages
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `capture_pages`
--

CREATE TABLE IF NOT EXISTS `capture_pages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `template` varchar(50) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `subheadline` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `custom_css` longtext DEFAULT NULL,
  `custom_js` longtext DEFAULT NULL,
  `external_css` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`external_css`)),
  `external_js` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`external_js`)),
  `cta_text` varchar(100) DEFAULT 'Je veux en savoir plus',
  `thank_you_message` text DEFAULT NULL,
  `header_id` int(11) DEFAULT NULL,
  `footer_id` int(11) DEFAULT NULL,
  `meta_title` varchar(160) DEFAULT NULL,
  `meta_description` varchar(320) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `views` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : landings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `landings`
--

CREATE TABLE IF NOT EXISTS `landings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `hero_text` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `cta_text` varchar(255) DEFAULT NULL,
  `form_shortcode` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : landing_pages
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `landing_pages`
--

CREATE TABLE IF NOT EXISTS `landing_pages` (
  `id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `texte_accroche` text NOT NULL,
  `bullet_points` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `formulaire_titre` varchar(255) DEFAULT 'Recevoir le guide',
  `formulaire_bouton` varchar(255) DEFAULT 'Télécharger maintenant',
  `page_remerciement_message` text DEFAULT NULL,
  `statut` enum('brouillon','en_ligne') DEFAULT 'brouillon',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


