-- ============================================================
-- MODULE : Contacts (contact)
-- Fichier : contact.sql
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
-- Table : contacts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `contacts`
--

CREATE TABLE IF NOT EXISTS `contacts` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `civility` enum('M.','Mme','Dr','Me') DEFAULT 'M.',
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `mobile` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'France',
  `company` varchar(200) DEFAULT NULL,
  `job_title` varchar(150) DEFAULT NULL,
  `category` enum('client','prospect','partenaire','notaire','banque','artisan','fournisseur','presse','autre') DEFAULT 'client',
  `status` enum('active','inactive','vip','blacklist') DEFAULT 'active',
  `birthday` date DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `tags` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `last_contact` date DEFAULT NULL,
  `next_followup` date DEFAULT NULL,
  `rating` int(11) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : contact_messages
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `contact_messages`
--

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL,
  `profil` varchar(100) DEFAULT NULL,
  `objectif` varchar(255) DEFAULT NULL,
  `type_bien` varchar(100) DEFAULT NULL,
  `secteur` varchar(255) DEFAULT NULL,
  `budget` varchar(100) DEFAULT NULL,
  `accompagnement` varchar(100) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `guide` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


