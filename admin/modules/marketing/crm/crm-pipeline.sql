-- ============================================================
-- MODULE : Pipeline CRM (crm-pipeline)
-- Fichier : crm-pipeline.sql
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
-- Table : pipeline_stages
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `pipeline_stages`
--

CREATE TABLE IF NOT EXISTS `pipeline_stages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#6366f1',
  `position` int(11) DEFAULT 0,
  `is_won` tinyint(1) DEFAULT 0,
  `is_lost` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `pipeline_stages`
--

INSERT INTO `pipeline_stages` (`id`, `name`, `color`, `position`, `is_won`, `is_lost`, `created_at`) VALUES
(1, 'Nouveau lead', '#6366f1', 1, 0, 0, '2026-01-26 00:12:18'),
(2, 'Premier contact', '#8b5cf6', 2, 0, 0, '2026-01-26 00:12:18'),
(3, 'Qualification', '#ec4899', 3, 0, 0, '2026-01-26 00:12:18'),
(4, 'Visite programmée', '#f59e0b', 4, 0, 0, '2026-01-26 00:12:18'),
(5, 'Offre en cours', '#10b981', 5, 0, 0, '2026-01-26 00:12:18'),
(6, 'Négociation', '#06b6d4', 6, 0, 0, '2026-01-26 00:12:18'),
(7, 'Compromis signé', '#22c55e', 7, 0, 0, '2026-01-26 00:12:18'),
(8, 'Gagné', '#16a34a', 8, 1, 0, '2026-01-26 00:12:18'),
(9, 'Perdu', '#ef4444', 9, 0, 1, '2026-01-26 00:12:18'),
(10, 'Nouveau lead', '#6366f1', 1, 0, 0, '2026-01-26 00:16:40'),
(11, 'Premier contact', '#8b5cf6', 2, 0, 0, '2026-01-26 00:16:40'),
(12, 'Qualification', '#ec4899', 3, 0, 0, '2026-01-26 00:16:40'),
(13, 'Visite programmée', '#f59e0b', 4, 0, 0, '2026-01-26 00:16:40'),
(14, 'Offre en cours', '#10b981', 5, 0, 0, '2026-01-26 00:16:40'),
(15, 'Négociation', '#06b6d4', 6, 0, 0, '2026-01-26 00:16:40'),
(16, 'Compromis signé', '#22c55e', 7, 0, 0, '2026-01-26 00:16:40'),
(17, 'Gagné', '#16a34a', 8, 1, 0, '2026-01-26 00:16:40'),
(18, 'Perdu', '#ef4444', 9, 0, 1, '2026-01-26 00:16:40');


