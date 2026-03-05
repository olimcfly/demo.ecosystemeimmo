-- ============================================================
-- MODULE : Paramètres (settings)
-- Fichier : settings.sql
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
-- Table : settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `settings`
--

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `api_openai` varchar(255) DEFAULT NULL,
  `api_perplexity` varchar(255) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `domain_main` varchar(255) DEFAULT NULL,
  `domain_cdn` varchar(255) DEFAULT NULL,
  `dns_info` text DEFAULT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_user` varchar(255) DEFAULT NULL,
  `smtp_pass` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `email_sender` varchar(255) DEFAULT NULL,
  `ip_whitelist` text DEFAULT NULL,
  `admin_email_security` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(4) DEFAULT 0,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`, `created_at`, `updated_at`, `api_openai`, `api_perplexity`, `webhook_url`, `domain_main`, `domain_cdn`, `dns_info`, `smtp_host`, `smtp_user`, `smtp_pass`, `smtp_port`, `email_sender`, `ip_whitelist`, `admin_email_security`, `two_factor_enabled`, `setting_key`, `setting_value`) VALUES
(51, 'site_name', 'Estimation Immobilier Bordeaux', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(52, 'site_slogan', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(53, 'email_support', 'contact@estimation-immobilier-bordeaux.fr', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(54, 'phone', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(55, 'color_primary', '#ffb400', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(56, 'color_primary_hex', '#ffb400', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(57, 'color_secondary', '#1b2a3b', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(58, 'color_secondary_hex', '#1b2a3b', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(59, 'logo_url', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(60, 'favicon_url', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(61, 'mail_from_name', 'ImmoLocal+', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(62, 'mail_from_email', 'noreply@estimation-immobilier-bordeaux.fr', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(63, 'smtp_host', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(64, 'smtp_port', '587', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(65, 'site_domain', 'estimation-immobilier-bordeaux.fr', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(66, 'app_url', 'https://estimation-immobilier-bordeaux.fr', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(67, 'seo_title', 'Estimation immobilier Bordeaux - Expert local', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(68, 'seo_description', 'Expert en estimation immobilière à Bordeaux. Obtenez une estimation précise et gratuite de votre bien.', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(69, 'facebook', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(70, 'instagram', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(71, 'linkedin', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(72, 'gtm_code', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(73, 'facebook_pixel', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(74, 'allowed_ips', '', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(75, 'maintenance_mode', '1', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(76, 'maintenance_message', 'Site en maintenance. Nous revenons bientôt !', '2025-11-16 03:37:20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(77, 'site_logo', '', '2026-02-06 20:30:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(78, 'site_favicon', '', '2026-02-06 20:30:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(79, 'site_logo_width', '180', '2026-02-06 20:30:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(80, 'site_logo_height', 'auto', '2026-02-06 20:30:54', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL);


-- ------------------------------------------------------------
-- Table : site_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `site_settings`
--

CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : admin_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `admin_settings`
--

CREATE TABLE IF NOT EXISTS `admin_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : integrations
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `integrations`
--

CREATE TABLE IF NOT EXISTS `integrations` (
  `id` int(11) NOT NULL,
  `service_name` varchar(50) NOT NULL,
  `service_label` varchar(100) NOT NULL,
  `api_key` varchar(500) DEFAULT NULL,
  `api_secret` varchar(500) DEFAULT NULL,
  `api_token` varchar(500) DEFAULT NULL,
  `project_id` varchar(255) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_active` tinyint(1) DEFAULT 0,
  `last_tested` datetime DEFAULT NULL,
  `test_status` enum('success','failed','pending') DEFAULT 'pending',
  `test_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `integrations`
--

INSERT INTO `integrations` (`id`, `service_name`, `service_label`, `api_key`, `api_secret`, `api_token`, `project_id`, `settings`, `is_active`, `last_tested`, `test_status`, `test_message`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'openai', 'OpenAI GPT', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(2, 'perplexity', 'Perplexity AI', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(3, 'claude', 'Claude.ai (Anthropic)', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(4, 'google_analytics', 'Google Analytics & GTM', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(5, 'facebook', 'Facebook Pixel & API', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(6, 'google_ads', 'Google Ads', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL),
(7, 'slack', 'Slack Webhooks', NULL, NULL, NULL, NULL, NULL, 0, NULL, 'pending', NULL, '2026-01-21 11:36:28', NULL, NULL);


