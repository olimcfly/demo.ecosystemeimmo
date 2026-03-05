-- ============================================================
-- MODULE : Scoring Leads (scoring)
-- Fichier : scoring.sql
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
-- Table : scoring_rules
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `scoring_rules`
--

CREATE TABLE IF NOT EXISTS `scoring_rules` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `field_name` varchar(100) NOT NULL,
  `operator` varchar(20) NOT NULL,
  `field_value` varchar(255) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `scoring_rules`
--

INSERT INTO `scoring_rules` (`id`, `name`, `category`, `field_name`, `operator`, `field_value`, `points`, `is_active`, `created_at`) VALUES
(1, 'Email fourni', 'engagement', 'email', 'not_empty', NULL, 10, 1, '2026-01-26 00:39:06'),
(2, 'Téléphone fourni', 'engagement', 'phone', 'not_empty', NULL, 15, 1, '2026-01-26 00:39:06'),
(3, 'Notes renseignées', 'engagement', 'notes', 'not_empty', NULL, 5, 1, '2026-01-26 00:39:06'),
(4, 'Source: Recommandation', 'source', 'source', 'equals', 'Recommandation', 25, 1, '2026-01-26 00:39:06'),
(5, 'Source: Site web', 'source', 'source', 'equals', 'Site web', 15, 1, '2026-01-26 00:39:06'),
(6, 'Source: Google', 'source', 'source', 'equals', 'Google', 10, 1, '2026-01-26 00:39:06'),
(7, 'Source: Facebook', 'source', 'source', 'equals', 'Facebook', 8, 1, '2026-01-26 00:39:06'),
(8, 'Valeur > 100 000€', 'value', 'estimated_value', 'greater_than', '100000', 20, 1, '2026-01-26 00:39:06'),
(9, 'Valeur > 200 000€', 'value', 'estimated_value', 'greater_than', '200000', 30, 1, '2026-01-26 00:39:06'),
(10, 'Valeur > 500 000€', 'value', 'estimated_value', 'greater_than', '500000', 40, 1, '2026-01-26 00:39:06'),
(11, 'Étape: Premier contact', 'pipeline', 'pipeline_stage_id', 'equals', '2', 10, 1, '2026-01-26 00:39:06'),
(12, 'Étape: Qualification', 'pipeline', 'pipeline_stage_id', 'equals', '3', 20, 1, '2026-01-26 00:39:06'),
(13, 'Étape: Visite programmée', 'pipeline', 'pipeline_stage_id', 'equals', '4', 35, 1, '2026-01-26 00:39:06'),
(14, 'Étape: Offre en cours', 'pipeline', 'pipeline_stage_id', 'equals', '5', 50, 1, '2026-01-26 00:39:06'),
(15, 'Action planifiée', 'activity', 'next_action', 'not_empty', NULL, 10, 1, '2026-01-26 00:39:06'),
(16, 'Créé < 7 jours', 'activity', 'created_days', 'less_than', '7', 15, 1, '2026-01-26 00:39:06'),
(17, 'Créé < 30 jours', 'activity', 'created_days', 'less_than', '30', 5, 1, '2026-01-26 00:39:06');


