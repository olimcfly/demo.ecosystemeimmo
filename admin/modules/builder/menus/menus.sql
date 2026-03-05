-- ============================================================
-- MODULE : Menus (menus)
-- Fichier : menus.sql
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
-- Table : menus
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE IF NOT EXISTS `menus` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menus`
--

INSERT INTO `menus` (`id`, `name`, `slug`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Menu Principal', 'header-main', 'Navigation principale du site', '2026-01-28 03:32:51', '2026-01-28 03:32:51'),
(2, 'Footer - Services', 'footer-col1', 'Première colonne du footer', '2026-01-28 03:32:51', '2026-01-28 03:32:51'),
(3, 'Footer - Ressources', 'footer-col2', 'Deuxième colonne du footer', '2026-01-28 03:32:51', '2026-01-28 03:32:51'),
(4, 'Footer - Légal', 'footer-col3', 'Liens légaux du footer', '2026-01-28 03:32:51', '2026-01-28 03:32:51');


-- ------------------------------------------------------------
-- Table : menu_items
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `menu_items`
--

CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(500) NOT NULL,
  `target` enum('_self','_blank') DEFAULT '_self',
  `icon` varchar(100) DEFAULT NULL,
  `css_class` varchar(100) DEFAULT NULL,
  `position` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


