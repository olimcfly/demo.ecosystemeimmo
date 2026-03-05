-- ============================================================
-- MODULE : Clés API (api-keys)
-- Fichier : api-keys.sql
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
-- Table : api_keys
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `api_keys`
--

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL,
  `service_key` varchar(50) NOT NULL COMMENT 'Identifiant unique du service (ex: openai, claude, google_maps)',
  `service_name` varchar(100) NOT NULL COMMENT 'Nom affichable (ex: OpenAI GPT)',
  `api_key_encrypted` text DEFAULT NULL COMMENT 'Clé API chiffrée AES-256',
  `category` enum('ai','google','social','analytics','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `last_verified_at` datetime DEFAULT NULL COMMENT 'Dernière vérification de validité',
  `verification_status` enum('unknown','valid','invalid','expired') DEFAULT 'unknown',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `api_keys`
--

INSERT INTO `api_keys` (`id`, `service_key`, `service_name`, `api_key_encrypted`, `category`, `is_active`, `last_verified_at`, `verification_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'openai', 'OpenAI (GPT-4, DALL-E)', NULL, 'ai', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(2, 'claude', 'Claude (Anthropic)', NULL, 'ai', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(3, 'perplexity', 'Perplexity AI', NULL, 'ai', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(4, 'mistral', 'Mistral AI', NULL, 'ai', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(5, 'google_maps', 'Google Maps Platform', NULL, 'google', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(6, 'google_analytics', 'Google Analytics (GA4)', NULL, 'google', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(7, 'google_search_console', 'Google Search Console', NULL, 'google', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(8, 'google_ads', 'Google Ads API', NULL, 'google', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(9, 'google_my_business', 'Google My Business', NULL, 'google', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(10, 'facebook_app', 'Facebook / Meta API', NULL, 'social', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(11, 'instagram_api', 'Instagram Graph API', NULL, 'social', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(12, 'tiktok_api', 'TikTok API', NULL, 'social', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(13, 'mailjet', 'Mailjet (Emails)', NULL, 'other', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(14, 'sendinblue', 'Brevo / Sendinblue', NULL, 'other', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(15, 'stripe', 'Stripe (Paiements)', NULL, 'other', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08'),
(16, 'twilio', 'Twilio (SMS)', NULL, 'other', 1, NULL, 'unknown', NULL, '2026-02-11 11:42:08', '2026-02-11 11:42:08');


-- ------------------------------------------------------------
-- Table : api_logs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `api_logs`
--

CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `service_name` varchar(50) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_body` longtext DEFAULT NULL,
  `request_headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`request_headers`)),
  `response_status` int(11) DEFAULT NULL,
  `response_body` longtext DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `error_code` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `user_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : api_usage
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `api_usage`
--

CREATE TABLE IF NOT EXISTS `api_usage` (
  `id` int(11) NOT NULL,
  `integration_id` int(11) NOT NULL,
  `service_name` varchar(50) DEFAULT NULL,
  `calls_today` int(11) DEFAULT 0,
  `calls_month` int(11) DEFAULT 0,
  `requests_quota` int(11) DEFAULT NULL,
  `cost_this_month` decimal(10,2) DEFAULT 0.00,
  `cost_limit` decimal(10,2) DEFAULT NULL,
  `last_reset_date` date DEFAULT NULL,
  `month_start` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


