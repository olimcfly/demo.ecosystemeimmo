-- ============================================================
-- MODULE : Scraper GMB (scraper-gmb)
-- Fichier : scraper-gmb.sql
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
-- Table : gmb_scraper_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_scraper_settings`
--

CREATE TABLE IF NOT EXISTS `gmb_scraper_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `gmb_scraper_settings`
--

INSERT INTO `gmb_scraper_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'google_places_api_key', '', '2026-02-11 12:43:56'),
(2, 'default_search_location', 'Bordeaux, France', '2026-02-11 12:43:56'),
(3, 'default_search_radius', '30', '2026-02-11 12:43:56'),
(4, 'smtp_host', '', '2026-02-11 12:43:56'),
(5, 'smtp_port', '587', '2026-02-11 12:43:56'),
(6, 'smtp_username', '', '2026-02-11 12:43:56'),
(7, 'smtp_password', '', '2026-02-11 12:43:56'),
(8, 'smtp_from_email', '', '2026-02-11 12:43:56'),
(9, 'smtp_from_name', 'Eduardo De Sul', '2026-02-11 12:43:56'),
(10, 'daily_scrape_limit', '100', '2026-02-11 12:43:56'),
(11, 'email_validation_enabled', '1', '2026-02-11 12:43:56'),
(12, 'default_broker_partner', '2L Courtage', '2026-02-11 12:50:53');


-- ------------------------------------------------------------
-- Table : gmb_scrape_jobs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_scrape_jobs`
--

CREATE TABLE IF NOT EXISTS `gmb_scrape_jobs` (
  `id` int(11) NOT NULL,
  `search_query` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `radius_km` int(11) DEFAULT 30,
  `results_found` int(11) DEFAULT 0,
  `results_saved` int(11) DEFAULT 0,
  `status` enum('pending','running','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_searches
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_searches`
--

CREATE TABLE IF NOT EXISTS `gmb_searches` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `radius` int(11) DEFAULT 10,
  `results_count` int(11) DEFAULT 0,
  `status` enum('pending','running','completed','error') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_sequence_steps
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_sequence_steps`
--

CREATE TABLE IF NOT EXISTS `gmb_sequence_steps` (
  `id` int(11) NOT NULL,
  `sequence_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `delay_days` int(11) DEFAULT 0 COMMENT 'Jours après étape précédente',
  `delay_hours` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gmb_sequence_steps`
--

INSERT INTO `gmb_sequence_steps` (`id`, `sequence_id`, `step_order`, `subject`, `body_html`, `delay_days`, `delay_hours`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Échange de liens Google entre professionnels - {{business_name}}', '<p>Bonjour {{contact_name}},</p>\r\n<p>Je suis Eduardo De Sul, conseiller immobilier chez eXp France à Bordeaux.</p>\r\n<p>J\'ai découvert <strong>{{business_name}}</strong> sur Google et votre note de {{rating}}/5 est impressionnante.</p>\r\n<p>Je vous contacte car un <strong>échange de liens Google</strong> entre nos fiches pourrait améliorer notre référencement local à tous les deux.</p>\r\n<p>Concrètement : je vous ajoute en recommandation sur ma fiche Google, et vous faites de même. C\'est rapide (5 min) et gratuit.</p>\r\n<p>Intéressé(e) ?</p>\r\n<p>Cordialement,<br>Eduardo De Sul<br>eXp France - Bordeaux</p>', 0, 0, '2026-02-11 13:06:33', NULL),
(2, 1, 2, 'Relance : Échange de liens Google - {{business_name}}', '<p>Bonjour {{contact_name}},</p>\r\n<p>Je reviens vers vous concernant l\'échange de liens Google entre nos fiches professionnelles.</p>\r\n<p>C\'est une démarche rapide qui profite aux deux parties.</p>\r\n<p>Qu\'en pensez-vous ?</p>\r\n<p>Cordialement,<br>Eduardo De Sul</p>', 3, 0, '2026-02-11 13:06:33', NULL),
(3, 1, 3, 'Dernière relance - {{business_name}}', '<p>Bonjour {{contact_name}},</p>\r\n<p>Dernier message à ce sujet. Si l\'échange de liens ne vous intéresse pas, aucun souci !</p>\r\n<p>N\'hésitez pas à me contacter si vous changez d\'avis.</p>\r\n<p>Belle continuation à {{business_name}} !</p>\r\n<p>Eduardo De Sul<br>eXp France - Bordeaux</p>', 5, 0, '2026-02-11 13:06:33', NULL);


