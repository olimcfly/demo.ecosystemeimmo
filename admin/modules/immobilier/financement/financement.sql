-- ============================================================
-- MODULE : Financement (financement)
-- Fichier : financement.sql
-- Généré le : 2026-02-12
-- Tables existantes : 5
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : financements
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `financements`
--

CREATE TABLE IF NOT EXISTS `financements` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) DEFAULT NULL COMMENT 'Lead associé',
  `contact_id` int(11) DEFAULT NULL COMMENT 'Contact associé',
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `project_type` enum('achat_residence','achat_investissement','rachat_credit','travaux','autre') DEFAULT 'achat_residence',
  `property_type` varchar(50) DEFAULT NULL COMMENT 'Type de bien visé',
  `property_price` decimal(12,2) DEFAULT NULL COMMENT 'Prix du bien',
  `personal_contribution` decimal(12,2) DEFAULT NULL COMMENT 'Apport personnel',
  `loan_amount` decimal(12,2) DEFAULT NULL COMMENT 'Montant à emprunter',
  `loan_duration` int(11) DEFAULT NULL COMMENT 'Durée en mois',
  `monthly_income` decimal(10,2) DEFAULT NULL COMMENT 'Revenus mensuels',
  `monthly_charges` decimal(10,2) DEFAULT NULL COMMENT 'Charges mensuelles',
  `employment_status` enum('cdi','cdd','independant','fonctionnaire','retraite','autre') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('new','contacted','studying','submitted','approved','refused','cancelled') DEFAULT 'new',
  `partner_id` int(11) DEFAULT NULL COMMENT 'Courtier/partenaire assigné',
  `source` varchar(100) DEFAULT NULL COMMENT 'Source de la demande',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : financement_courtiers
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `financement_courtiers`
--

CREATE TABLE IF NOT EXISTS `financement_courtiers` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL COMMENT 'Nom du courtier/société',
  `contact_nom` varchar(255) DEFAULT NULL COMMENT 'Nom du contact principal',
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'URL du logo',
  `taux_commission` decimal(5,2) DEFAULT 1.00 COMMENT 'Taux de commission par défaut en %',
  `notes` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `financement_courtiers`
--

INSERT INTO `financement_courtiers` (`id`, `nom`, `contact_nom`, `email`, `telephone`, `adresse`, `logo`, `taux_commission`, `notes`, `actif`, `created_at`, `updated_at`) VALUES
(1, '2L Courtage', 'Laurent Dupont', 'contact@2lcourtage.fr', '01 23 45 67 89', NULL, NULL, 1.50, 'Partenaire principal - Double compétence immobilier/courtage', 1, '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(2, 'Cafpi', 'Service Pro', 'pro@cafpi.fr', '01 00 00 00 00', NULL, NULL, 1.00, 'Courtier national', 1, '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(3, 'Meilleurtaux Pro', 'Partenariats', 'partenaires@meilleurtaux.com', '01 00 00 00 01', NULL, NULL, 1.20, 'Courtier en ligne', 1, '2026-01-26 12:18:32', '2026-01-26 12:18:32');


-- ------------------------------------------------------------
-- Table : financement_leads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `financement_leads`
--

CREATE TABLE IF NOT EXISTS `financement_leads` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(50) NOT NULL,
  `type_projet` enum('achat_residence','achat_investissement','rachat_credit','renégociation','construction','travaux','autre') DEFAULT 'achat_residence',
  `montant_projet` decimal(12,2) DEFAULT NULL COMMENT 'Montant du projet en €',
  `apport` decimal(12,2) DEFAULT NULL COMMENT 'Apport personnel en €',
  `revenus` decimal(10,2) DEFAULT NULL COMMENT 'Revenus mensuels en €',
  `courtier_id` int(11) DEFAULT NULL,
  `statut` enum('nouveau','transmis','en_cours','finance','commission_percue','perdu') DEFAULT 'nouveau',
  `date_transmission` datetime DEFAULT NULL COMMENT 'Date de transmission au courtier',
  `date_financement` datetime DEFAULT NULL COMMENT 'Date de validation du financement',
  `commission_montant` decimal(10,2) DEFAULT NULL COMMENT 'Montant de la commission en €',
  `taux_commission` decimal(5,2) DEFAULT NULL COMMENT 'Taux de commission appliqué en %',
  `date_commission` datetime DEFAULT NULL COMMENT 'Date de perception de la commission',
  `source` varchar(100) DEFAULT NULL COMMENT 'Source du lead (formulaire, téléphone, etc.)',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `financement_leads`
--

INSERT INTO `financement_leads` (`id`, `nom`, `prenom`, `email`, `telephone`, `type_projet`, `montant_projet`, `apport`, `revenus`, `courtier_id`, `statut`, `date_transmission`, `date_financement`, `commission_montant`, `taux_commission`, `date_commission`, `source`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Martin', 'Jean', 'jean.martin@email.com', '06 12 34 56 78', 'achat_residence', 250000.00, 25000.00, 3500.00, 1, 'nouveau', NULL, NULL, NULL, NULL, NULL, NULL, 'Premier achat, jeune couple', '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(2, 'Dubois', 'Marie', 'marie.dubois@email.com', '06 98 76 54 32', 'achat_investissement', 180000.00, 40000.00, 4200.00, 1, 'transmis', NULL, NULL, 1800.00, NULL, NULL, NULL, 'Investissement locatif Pinel', '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(3, 'Bernard', 'Pierre', 'pierre.bernard@email.com', '07 11 22 33 44', 'rachat_credit', 320000.00, 0.00, 5500.00, 2, 'en_cours', NULL, NULL, 2500.00, NULL, NULL, NULL, 'Rachat + trésorerie travaux', '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(4, 'Petit', 'Sophie', 'sophie.petit@email.com', '06 55 44 33 22', 'achat_residence', 420000.00, 80000.00, 6200.00, 1, 'finance', NULL, NULL, 4200.00, NULL, NULL, NULL, 'Maison avec terrain', '2026-01-26 12:18:32', '2026-01-26 12:18:32'),
(5, 'Moreau', 'Thomas', 'thomas.moreau@email.com', '06 77 88 99 00', 'construction', 350000.00, 50000.00, 4800.00, 3, 'commission_percue', NULL, NULL, 3500.00, NULL, NULL, NULL, 'Construction neuve', '2026-01-26 12:18:32', '2026-01-26 12:18:32');


-- ------------------------------------------------------------
-- Table : financement_leads_logs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `financement_leads_logs`
--

CREATE TABLE IF NOT EXISTS `financement_leads_logs` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'Type d action: create, update, status_change, delete',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Données associées à l action' CHECK (json_valid(`data`)),
  `user_id` int(11) DEFAULT NULL COMMENT 'ID de l utilisateur qui a fait l action',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : simulation_leads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `simulation_leads`
--

CREATE TABLE IF NOT EXISTS `simulation_leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `revenus` decimal(10,2) NOT NULL,
  `credits` decimal(10,2) NOT NULL,
  `apport` decimal(10,2) NOT NULL,
  `duree` int(11) NOT NULL,
  `capacite` decimal(12,2) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'simulation_achat',
  `ip` varchar(50) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


