-- ============================================================
-- MODULE : TikTok (tiktok)
-- Fichier : tiktok.sql
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
-- Table : videos
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `videos`
--

CREATE TABLE IF NOT EXISTS `videos` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Titre de la vidéo',
  `description` text DEFAULT NULL,
  `video_type` enum('youtube','vimeo','upload','virtual_tour','drone','interview','other') DEFAULT 'youtube',
  `video_url` varchar(500) DEFAULT NULL COMMENT 'URL de la vidéo (YouTube, Vimeo)',
  `video_id` varchar(100) DEFAULT NULL COMMENT 'ID YouTube/Vimeo',
  `embed_code` text DEFAULT NULL COMMENT 'Code d''intégration',
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Chemin fichier (si upload)',
  `thumbnail` varchar(255) DEFAULT NULL COMMENT 'Miniature',
  `duration` int(11) DEFAULT NULL COMMENT 'Durée en secondes',
  `property_id` int(11) DEFAULT NULL COMMENT 'Bien associé',
  `category` varchar(100) DEFAULT NULL COMMENT 'Catégorie',
  `tags` varchar(255) DEFAULT NULL COMMENT 'Tags (séparés par virgules)',
  `views_count` int(11) DEFAULT 0,
  `likes_count` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Mise en avant',
  `is_active` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- ============================================================
-- NOUVELLES TABLES (à créer)
-- ============================================================

-- ------------------------------------------------------------
-- Table : tiktok_settings (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `tiktok_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : tiktok_posts (NOUVELLE)
-- ------------------------------------------------------------
CREATE TABLE `tiktok_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `status` enum('draft','scheduled','published','archived') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

