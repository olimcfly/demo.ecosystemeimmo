-- ============================================================
-- MODULE : Rendez-vous (rdv)
-- Fichier : rdv.sql
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
-- Table : rdv
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `rdv`
--

CREATE TABLE IF NOT EXISTS `rdv` (
  `id` int(11) NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `nom_client` varchar(255) NOT NULL,
  `email_client` varchar(255) NOT NULL,
  `telephone_client` varchar(50) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'réservé',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : appointments
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `appointments`
--

CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('visite','estimation','signature','prospection','suivi','autre') DEFAULT 'visite',
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `contact_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `status` enum('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
  `reminder_sent` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#6366f1',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `appointments`
--

INSERT INTO `appointments` (`id`, `title`, `description`, `type`, `start_datetime`, `end_datetime`, `location`, `lead_id`, `contact_id`, `property_id`, `status`, `reminder_sent`, `notes`, `color`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'test', NULL, 'estimation', '2026-01-26 09:00:00', '2026-01-26 10:00:00', NULL, 1, NULL, NULL, 'scheduled', 0, NULL, '#6366f1', 1, '2026-01-26 00:51:17', '2026-01-26 00:51:17');


