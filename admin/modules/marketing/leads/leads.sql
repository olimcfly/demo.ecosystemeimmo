-- ============================================================
-- MODULE : Leads / Prospects (leads)
-- Fichier : leads.sql
-- Généré le : 2026-02-12
-- Tables existantes : 8
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : leads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `leads`
--

CREATE TABLE IF NOT EXISTS `leads` (
  `id` int(11) NOT NULL,
  `partenaire_id` int(11) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `surface` varchar(50) DEFAULT NULL,
  `rooms` varchar(50) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `vmin` decimal(10,2) DEFAULT NULL,
  `vavg` decimal(10,2) DEFAULT NULL,
  `vmax` decimal(10,2) DEFAULT NULL,
  `estimation` longtext DEFAULT NULL,
  `status` enum('nouveau','contacté','converti','archivé') DEFAULT 'nouveau',
  `source` varchar(100) DEFAULT 'estimation',
  `raw_payload` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `pipeline_stage_id` int(11) DEFAULT 1,
  `estimated_value` decimal(10,2) DEFAULT 0.00,
  `next_action` varchar(255) DEFAULT NULL,
  `next_action_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `lost_reason` varchar(255) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `lost_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `score` int(11) DEFAULT 0,
  `temperature` enum('cold','warm','hot') DEFAULT 'cold',
  `score_updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `leads`
--

INSERT INTO `leads` (`id`, `partenaire_id`, `full_name`, `email`, `phone`, `address`, `city`, `postal_code`, `type`, `surface`, `rooms`, `state`, `vmin`, `vavg`, `vmax`, `estimation`, `status`, `source`, `raw_payload`, `created_at`, `updated_at`, `pipeline_stage_id`, `estimated_value`, `next_action`, `next_action_date`, `assigned_to`, `lost_reason`, `firstname`, `lastname`, `lost_date`, `notes`, `score`, `temperature`, `score_updated_at`) VALUES
(1, NULL, NULL, 'oliviercolas83@gmail.com', '0785611700', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'nouveau', 'Manuel', NULL, '2026-01-26 01:32:28', '2026-01-28 04:53:17', 5, 500.00, NULL, NULL, NULL, NULL, 'Olivier', 'Colas', NULL, '', 95, 'hot', '2026-01-28 03:53:24');


-- ------------------------------------------------------------
-- Table : leads_captures
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `leads_captures`
--

CREATE TABLE IF NOT EXISTS `leads_captures` (
  `id` int(11) NOT NULL,
  `capture_id` int(11) NOT NULL COMMENT 'ID de la page de capture',
  `nom` varchar(100) DEFAULT NULL COMMENT 'Nom du lead',
  `prenom` varchar(100) DEFAULT NULL COMMENT 'Prénom du lead',
  `email` varchar(255) NOT NULL COMMENT 'Email du lead',
  `telephone` varchar(20) DEFAULT NULL COMMENT 'Téléphone du lead',
  `adresse` text DEFAULT NULL COMMENT 'Adresse du bien',
  `type_bien` varchar(50) DEFAULT NULL COMMENT 'Type de bien',
  `surface` decimal(10,2) DEFAULT NULL COMMENT 'Surface en m²',
  `budget` decimal(12,2) DEFAULT NULL COMMENT 'Budget',
  `projet` varchar(50) DEFAULT NULL COMMENT 'Type de projet',
  `message` text DEFAULT NULL COMMENT 'Message ou commentaire',
  `donnees_supplementaires` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Autres données du formulaire' CHECK (json_valid(`donnees_supplementaires`)),
  `consent_rgpd` tinyint(1) DEFAULT 0 COMMENT 'Consentement RGPD',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` text DEFAULT NULL COMMENT 'User Agent du navigateur',
  `referer` varchar(255) DEFAULT NULL COMMENT 'Page de provenance',
  `utm_source` varchar(100) DEFAULT NULL COMMENT 'Source UTM',
  `utm_medium` varchar(100) DEFAULT NULL COMMENT 'Medium UTM',
  `utm_campaign` varchar(100) DEFAULT NULL COMMENT 'Campagne UTM',
  `statut` enum('nouveau','contacte','qualifie','converti','perdu') DEFAULT 'nouveau',
  `score_qualite` int(11) DEFAULT 0 COMMENT 'Score de qualité du lead (0-100)',
  `traite` tinyint(1) DEFAULT 0 COMMENT 'Lead traité ou non',
  `traite_par` int(11) DEFAULT NULL COMMENT 'ID de l''utilisateur qui a traité',
  `traite_le` timestamp NULL DEFAULT NULL COMMENT 'Date de traitement',
  `notes` text DEFAULT NULL COMMENT 'Notes internes',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `leads_captures`
--
DELIMITER $$
CREATE TRIGGER `update_conversion_rate` AFTER INSERT ON `leads_captures` FOR EACH ROW BEGIN
    DECLARE total_vues INT;
    DECLARE total_conversions INT;
    DECLARE nouveau_taux DECIMAL(5,2);
    
    -- Récupérer les statistiques actuelles
    SELECT vues, conversions INTO total_vues, total_conversions
    FROM captures
    WHERE id = NEW.capture_id;
    
    -- Calculer le nouveau taux
    IF total_vues > 0 THEN
        SET nouveau_taux = (total_conversions / total_vues) * 100;
    ELSE
        SET nouveau_taux = 0;
    END IF;
    
    -- Mettre à jour la table captures
    UPDATE captures
    SET conversions = conversions + 1,
        taux_conversion = nouveau_taux,
        last_conversion_at = NOW()
    WHERE id = NEW.capture_id;
END
$$
DELIMITER ;


-- ------------------------------------------------------------
-- Table : lead_activities
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_activities`
--

CREATE TABLE IF NOT EXISTS `lead_activities` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `type` enum('call','email','meeting','note','task','stage_change','other') DEFAULT 'note',
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : lead_assignment_history
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_assignment_history`
--

CREATE TABLE IF NOT EXISTS `lead_assignment_history` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `old_agent_id` int(11) DEFAULT NULL,
  `new_agent_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : lead_documents
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_documents`
--

CREATE TABLE IF NOT EXISTS `lead_documents` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `filepath` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : lead_interactions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_interactions`
--

CREATE TABLE IF NOT EXISTS `lead_interactions` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `type` enum('note','appel','email','rdv','sms','visite') DEFAULT 'note',
  `subject` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `interaction_date` datetime DEFAULT NULL,
  `outcome` enum('positif','neutre','negatif') DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_by_type` enum('agent','system','contact') DEFAULT 'agent',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : lead_scoring_history
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_scoring_history`
--

CREATE TABLE IF NOT EXISTS `lead_scoring_history` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `old_score` int(11) DEFAULT NULL,
  `new_score` int(11) DEFAULT NULL,
  `old_temperature` varchar(20) DEFAULT NULL,
  `new_temperature` varchar(20) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : lead_status_history
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `lead_status_history`
--

CREATE TABLE IF NOT EXISTS `lead_status_history` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


