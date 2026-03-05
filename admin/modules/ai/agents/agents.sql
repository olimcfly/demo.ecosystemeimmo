-- ============================================================
-- MODULE : Agents IA (agents)
-- Fichier : agents.sql
-- Généré le : 2026-02-12
-- Tables existantes : 2
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : agents
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `agents`
--

CREATE TABLE IF NOT EXISTS `agents` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL COMMENT 'Prénom',
  `lastname` varchar(100) NOT NULL COMMENT 'Nom',
  `email` varchar(255) NOT NULL COMMENT 'Email professionnel',
  `phone` varchar(20) DEFAULT NULL COMMENT 'Téléphone',
  `mobile` varchar(20) DEFAULT NULL COMMENT 'Mobile',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Photo de profil',
  `title` varchar(100) DEFAULT 'Agent immobilier' COMMENT 'Titre/Poste',
  `bio` text DEFAULT NULL COMMENT 'Biographie',
  `specialties` varchar(255) DEFAULT NULL COMMENT 'Spécialités (JSON ou séparées par virgules)',
  `languages` varchar(100) DEFAULT 'Français' COMMENT 'Langues parlées',
  `rsac` varchar(50) DEFAULT NULL COMMENT 'Numéro RSAC',
  `social_linkedin` varchar(255) DEFAULT NULL,
  `social_facebook` varchar(255) DEFAULT NULL,
  `social_instagram` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0 COMMENT 'Ordre d''affichage',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '1=actif, 0=inactif',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : tasks
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `tasks`
--

CREATE TABLE IF NOT EXISTS `tasks` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


