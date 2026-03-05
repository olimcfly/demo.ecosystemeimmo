-- ============================================================
-- MODULE : Analytics (analytics)
-- Fichier : analytics.sql
-- Généré le : 2026-02-12
-- Tables existantes : 1
-- Tables à créer : 2
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : visiteurs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `visiteurs`
--

CREATE TABLE IF NOT EXISTS `visiteurs` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `session_id` varchar(64) DEFAULT NULL,
  `page_url` varchar(255) DEFAULT NULL,
  `referrer` varchar(255) DEFAULT NULL,
  `date_visite` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ============================================================
-- NOUVELLES TABLES (à créer)
-- ============================================================

-- ------------------------------------------------------------
-- Table : analytics_events (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `analytics_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `event_category` varchar(100) DEFAULT NULL,
  `event_action` varchar(100) DEFAULT NULL,
  `event_label` varchar(255) DEFAULT NULL,
  `event_value` decimal(10,2) DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `device_type` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : analytics_goals (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `analytics_goals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` enum('pageview','event','duration','scroll') NOT NULL,
  `target_value` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `conversions_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

