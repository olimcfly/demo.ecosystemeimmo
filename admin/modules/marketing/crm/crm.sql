-- ============================================================
-- MODULE : CRM (crm)
-- Fichier : crm.sql
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
-- Table : crm_config
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `crm_config`
--

CREATE TABLE IF NOT EXISTS `crm_config` (
  `id` int(11) NOT NULL,
  `setting_name` varchar(255) DEFAULT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `crm_config`
--

INSERT INTO `crm_config` (`id`, `setting_name`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'scoring_rules', '{\"interactions_weight\": 30, \"budget_weight\": 20, \"status_weight\": 30, \"recency_weight\": 20}', 'json', '2026-01-24 17:06:47'),
(2, 'pipeline_stages', '[\"nouveau\", \"qualifie\", \"en_negociation\", \"gagne\", \"perdu\"]', 'json', '2026-01-24 17:06:47'),
(3, 'default_agent', '1', 'int', '2026-01-24 17:06:47'),
(4, 'auto_assign_enabled', '0', 'boolean', '2026-01-24 17:06:47');


-- ------------------------------------------------------------
-- Table : crm_exports
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `crm_exports`
--

CREATE TABLE IF NOT EXISTS `crm_exports` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `format` enum('csv','xlsx','pdf') DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `lead_count` int(11) DEFAULT NULL,
  `exported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


