-- ============================================================
-- MODULE : SMS Marketing (sms)
-- Fichier : sms.sql
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
-- Table : sms
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms`
--

CREATE TABLE IF NOT EXISTS `sms` (
  `id` int(11) NOT NULL,
  `recipient_phone` varchar(20) NOT NULL COMMENT 'Numéro destinataire',
  `recipient_name` varchar(100) DEFAULT NULL COMMENT 'Nom du destinataire',
  `lead_id` int(11) DEFAULT NULL COMMENT 'Lead associé',
  `contact_id` int(11) DEFAULT NULL COMMENT 'Contact associé',
  `message` text NOT NULL COMMENT 'Contenu du SMS (max 160/480 car)',
  `message_type` enum('transactional','marketing','reminder','notification') DEFAULT 'transactional',
  `campaign_id` int(11) DEFAULT NULL COMMENT 'Campagne associée',
  `template` varchar(100) DEFAULT NULL COMMENT 'Template utilisé',
  `status` enum('draft','scheduled','sending','sent','delivered','failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL COMMENT 'Fournisseur SMS (Twilio, OVH, etc.)',
  `provider_id` varchar(100) DEFAULT NULL COMMENT 'ID du message chez le provider',
  `cost` decimal(5,3) DEFAULT NULL COMMENT 'Coût du SMS',
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : sms_campagnes
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms_campagnes`
--

CREATE TABLE IF NOT EXISTS `sms_campagnes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `sender_name` varchar(20) DEFAULT NULL,
  `recipients_count` int(11) DEFAULT 0,
  `status` enum('draft','scheduled','sending','sent','delivered','failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `sms_campagnes`
--

INSERT INTO `sms_campagnes` (`id`, `name`, `message`, `sender_name`, `recipients_count`, `status`, `scheduled_at`, `sent_at`, `created_at`, `updated_at`) VALUES
(1, 'test', 'test{prenom}', 'Olivier Col', 1, 'scheduled', '0000-00-00 00:00:00', NULL, '2025-12-05 23:16:14', '2025-12-05 23:16:14');


-- ------------------------------------------------------------
-- Table : sms_queue
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms_queue`
--

CREATE TABLE IF NOT EXISTS `sms_queue` (
  `id` int(10) UNSIGNED NOT NULL,
  `to_phone` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `context_type` varchar(50) DEFAULT NULL,
  `context_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `provider_message_id` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `cost` decimal(8,4) DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : sms_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms_settings`
--

CREATE TABLE IF NOT EXISTS `sms_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `api_url` varchar(255) NOT NULL,
  `api_key` varchar(255) NOT NULL,
  `sender_name` varchar(20) DEFAULT NULL,
  `default_country_prefix` varchar(10) DEFAULT '+33',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `test_phone` varchar(30) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : sms_templates
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms_templates`
--

CREATE TABLE IF NOT EXISTS `sms_templates` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : sms_triggers
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sms_triggers`
--

CREATE TABLE IF NOT EXISTS `sms_triggers` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `event_code` varchar(100) NOT NULL,
  `template_id` int(10) UNSIGNED NOT NULL,
  `delay_minutes` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


