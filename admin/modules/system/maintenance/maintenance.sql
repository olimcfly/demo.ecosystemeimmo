-- ============================================================
-- MODULE : Maintenance (maintenance)
-- Fichier : maintenance.sql
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
-- Table : maintenance
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `maintenance`
--

CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = site public, 1 = maintenance active',
  `message` text DEFAULT NULL COMMENT 'Message affiché aux visiteurs',
  `allowed_ips` text DEFAULT NULL COMMENT 'IPs séparées par virgule',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `maintenance`
--

INSERT INTO `maintenance` (`id`, `is_active`, `message`, `allowed_ips`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 1, 'Notre site est actuellement en maintenance. Nous serons de retour très prochainement. Merci de votre patience.', '92.184.103.245, 92.184.103.162', '2026-02-06 19:51:39', NULL, '2026-02-06 18:19:50', '2026-02-10 21:57:31');


