-- ============================================================
-- MODULE : Newsletters (newsletters)
-- Fichier : newsletters.sql
-- Généré le : 2026-02-12
-- Tables existantes : 0
-- Tables à créer : 4
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;


-- ============================================================
-- NOUVELLES TABLES (à créer)
-- ============================================================

-- ------------------------------------------------------------
-- Table : newsletters (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `newsletters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `status` enum('draft','scheduled','sending','sent','cancelled') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `recipients_count` int(11) DEFAULT 0,
  `opens_count` int(11) DEFAULT 0,
  `clicks_count` int(11) DEFAULT 0,
  `bounces_count` int(11) DEFAULT 0,
  `unsubscribes_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : newsletter_subscribers (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
  `source` varchar(100) DEFAULT NULL,
  `gdpr_consent` tinyint(1) DEFAULT 0,
  `consent_date` datetime DEFAULT NULL,
  `unsubscribed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : newsletter_templates (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `newsletter_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `html_content` longtext NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : newsletter_logs (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `newsletter_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newsletter_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `event` enum('sent','opened','clicked','bounced','unsubscribed') NOT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_newsletter_id` (`newsletter_id`),
  KEY `idx_subscriber_id` (`subscriber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

