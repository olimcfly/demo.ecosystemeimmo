-- ============================================================
-- MODULE : Médiathèque (media)
-- Fichier : media.sql
-- Généré le : 2026-02-12
-- Tables existantes : 1
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : media
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `media`
--

CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `filepath` varchar(500) NOT NULL,
  `filetype` varchar(100) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `folder` varchar(100) DEFAULT 'general',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


