-- ============================================================
-- MODULE : Partenaires (partenaires)
-- Fichier : partenaires.sql
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
-- Table : partenaires
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `partenaires`
--

CREATE TABLE IF NOT EXISTS `partenaires` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `entreprise` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `commission` decimal(5,2) DEFAULT NULL,
  `zone` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : partenaire_leads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `partenaire_leads`
--

CREATE TABLE IF NOT EXISTS `partenaire_leads` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'envoyé',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


