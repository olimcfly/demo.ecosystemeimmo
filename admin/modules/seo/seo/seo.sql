-- ============================================================
-- MODULE : SEO (seo)
-- Fichier : seo.sql
-- Généré le : 2026-02-12
-- Tables existantes : 3
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : seo_history
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `seo_history`
--

CREATE TABLE IF NOT EXISTS `seo_history` (
  `id` int(11) NOT NULL,
  `content_type` enum('page','article') NOT NULL,
  `content_id` int(11) NOT NULL,
  `seo_score` int(11) NOT NULL,
  `serp_position` int(11) DEFAULT NULL,
  `is_indexed` tinyint(1) DEFAULT 0,
  `check_date` datetime DEFAULT current_timestamp(),
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Détails analyse SEO' CHECK (json_valid(`details`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : seo_keywords
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `seo_keywords`
--

CREATE TABLE IF NOT EXISTS `seo_keywords` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL COMMENT 'Mot-clé à suivre',
  `page_id` int(11) DEFAULT NULL COMMENT 'Page cible associée',
  `page_url` varchar(500) DEFAULT NULL COMMENT 'URL de la page cible',
  `search_volume` int(11) DEFAULT NULL COMMENT 'Volume de recherche mensuel',
  `difficulty` int(11) DEFAULT NULL COMMENT 'Difficulté SEO (0-100)',
  `current_position` int(11) DEFAULT NULL COMMENT 'Position actuelle Google',
  `previous_position` int(11) DEFAULT NULL COMMENT 'Position précédente',
  `best_position` int(11) DEFAULT NULL COMMENT 'Meilleure position atteinte',
  `target_position` int(11) DEFAULT 10 COMMENT 'Position cible',
  `location` varchar(100) DEFAULT 'France' COMMENT 'Localisation du suivi',
  `device` enum('desktop','mobile','both') DEFAULT 'both',
  `category` varchar(100) DEFAULT NULL COMMENT 'Catégorie/thématique',
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `notes` text DEFAULT NULL,
  `last_check` datetime DEFAULT NULL COMMENT 'Dernière vérification',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : seo_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `seo_settings`
--

CREATE TABLE IF NOT EXISTS `seo_settings` (
  `id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `google_site_verification` varchar(255) DEFAULT NULL,
  `bing_site_verification` varchar(255) DEFAULT NULL,
  `google_analytics_id` varchar(50) DEFAULT NULL,
  `google_search_console_api` text DEFAULT NULL COMMENT 'Clé API GSC cryptée',
  `default_og_image` varchar(255) DEFAULT NULL,
  `robots_txt` text DEFAULT NULL,
  `sitemap_frequency` enum('always','hourly','daily','weekly','monthly') DEFAULT 'weekly',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


