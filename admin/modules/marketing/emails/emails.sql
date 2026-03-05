-- ============================================================
-- MODULE : Emails Marketing (emails)
-- Fichier : emails.sql
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
-- Table : emails
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `emails`
--

CREATE TABLE IF NOT EXISTS `emails` (
  `id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL COMMENT 'Objet de l''email',
  `content` text NOT NULL COMMENT 'Contenu HTML',
  `content_text` text DEFAULT NULL COMMENT 'Contenu texte brut',
  `from_email` varchar(255) DEFAULT NULL COMMENT 'Email expéditeur',
  `from_name` varchar(100) DEFAULT NULL COMMENT 'Nom expéditeur',
  `to_email` varchar(255) DEFAULT NULL COMMENT 'Destinataire (si individuel)',
  `to_list` varchar(50) DEFAULT NULL COMMENT 'Liste de diffusion (leads, contacts, etc.)',
  `campaign_id` int(11) DEFAULT NULL COMMENT 'ID campagne associée',
  `template` varchar(100) DEFAULT NULL COMMENT 'Template utilisé',
  `status` enum('draft','scheduled','sending','sent','failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL COMMENT 'Date d''envoi programmé',
  `sent_at` datetime DEFAULT NULL COMMENT 'Date d''envoi effectif',
  `sent_count` int(11) DEFAULT 0 COMMENT 'Nombre d''envois',
  `open_count` int(11) DEFAULT 0 COMMENT 'Nombre d''ouvertures',
  `click_count` int(11) DEFAULT 0 COMMENT 'Nombre de clics',
  `bounce_count` int(11) DEFAULT 0 COMMENT 'Nombre de bounces',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : mailing_list
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `mailing_list`
--

CREATE TABLE IF NOT EXISTS `mailing_list` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `prenom` varchar(255) DEFAULT NULL,
  `guide_id` int(11) DEFAULT NULL,
  `landing_page_id` int(11) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'guide',
  `ville` varchar(100) DEFAULT 'Bordeaux',
  `statut` enum('actif','desinscrit') DEFAULT 'actif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


