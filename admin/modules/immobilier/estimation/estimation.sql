-- ============================================================
-- MODULE : Estimation (estimation)
-- Fichier : estimation.sql
-- Généré le : 2026-02-12
-- Tables existantes : 9
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : estimations
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimations`
--

CREATE TABLE IF NOT EXISTS `estimations` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `type_bien` enum('maison','appartement','terrain','commerce','autre') DEFAULT 'appartement',
  `surface` int(11) DEFAULT NULL,
  `pieces` int(11) DEFAULT NULL,
  `estimation_basse` decimal(12,2) DEFAULT NULL,
  `estimation_haute` decimal(12,2) DEFAULT NULL,
  `statut` enum('en_attente','traitee','convertie') DEFAULT 'en_attente',
  `notes` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : estimation_contacts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_contacts`
--

CREATE TABLE IF NOT EXISTS `estimation_contacts` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `contact_type` enum('email','phone','sms','rdv','message') DEFAULT 'email',
  `direction` enum('in','out') DEFAULT 'out',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `response_received` tinyint(1) DEFAULT 0,
  `response_text` text DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `response_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : estimation_leads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_leads`
--

CREATE TABLE IF NOT EXISTS `estimation_leads` (
  `id` int(11) NOT NULL,
  `reference` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `pref_contact` enum('telephone','email','sms') DEFAULT 'telephone',
  `property_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'type, surface, pièces, localisation...' CHECK (json_valid(`property_data`)),
  `estimation_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'prix bas/moyen/haut' CHECK (json_valid(`estimation_data`)),
  `bant_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'timeline, budget, authority, need détaillés' CHECK (json_valid(`bant_data`)),
  `bant_score` tinyint(3) UNSIGNED DEFAULT 0 COMMENT '0-100',
  `temperature` enum('hot','warm','cold') DEFAULT 'cold',
  `want_rdv` tinyint(1) DEFAULT 0,
  `comment` text DEFAULT NULL,
  `source` varchar(50) DEFAULT 'estimation',
  `gdpr_consent` tinyint(1) DEFAULT 0,
  `status` enum('new','contacted','qualified','converted','lost') DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `estimation_leads`
--

INSERT INTO `estimation_leads` (`id`, `reference`, `first_name`, `last_name`, `email`, `phone`, `pref_contact`, `property_data`, `estimation_data`, `bant_data`, `bant_score`, `temperature`, `want_rdv`, `comment`, `source`, `gdpr_consent`, `status`, `created_at`, `updated_at`) VALUES
(1, 'EST-7B16C6AD', 'Olivier', 'Colas', 'oliviercolas83@gmail.com', '07 85 61 17 00', 'telephone', '{\"type_bien\":\"maison\",\"projet\":\"vente\",\"surface\":100,\"pieces\":\"2\",\"chambres\":\"1\",\"etage\":\"\",\"annee\":\"avant1950\",\"etat\":\"renover\",\"exterieur\":[\"terrasse\"],\"adresse\":\"\",\"code_postal\":\"33000\",\"ville\":\"Bordeaux\",\"quartier\":\"bastide\"}', '{\"prix_bas\":\"355 000 €\",\"prix_moyen\":\"421 000 €\",\"prix_haut\":\"514 000 €\"}', '{\"timeline\":\"immediat\",\"besoin_financement\":\"oui\",\"demarches_banque\":\"\",\"proprietaire\":\"oui\",\"motivation\":\"vente_simple\",\"score\":78,\"temperature\":\"hot\"}', 78, 'hot', 0, '', 'estimation', 1, 'new', '2026-02-08 12:50:46', NULL);


-- ------------------------------------------------------------
-- Table : estimation_rdv
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_rdv`
--

CREATE TABLE IF NOT EXISTS `estimation_rdv` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `confirmed_date` datetime DEFAULT NULL,
  `confirmed_time` time DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT 45,
  `agent_id` int(11) DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `status` enum('proposed','planifie','confirmed','completed','cancelled') DEFAULT 'proposed',
  `cancellation_reason` text DEFAULT NULL,
  `report_id` int(11) DEFAULT NULL,
  `report_url` varchar(500) DEFAULT NULL,
  `follow_up` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : estimation_reports
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_reports`
--

CREATE TABLE IF NOT EXISTS `estimation_reports` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `rdv_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `bant_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bant_analysis`)),
  `comparable_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`comparable_properties`)),
  `price_analysis` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`price_analysis`)),
  `market_analysis` text DEFAULT NULL,
  `final_low_price` decimal(12,2) DEFAULT NULL,
  `final_mean_price` decimal(12,2) DEFAULT NULL,
  `final_high_price` decimal(12,2) DEFAULT NULL,
  `valuation_method` varchar(100) DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `improvement_suggestions` text DEFAULT NULL,
  `selling_strategy` text DEFAULT NULL,
  `pdf_url` varchar(500) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : estimation_requests
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_requests`
--

CREATE TABLE IF NOT EXISTS `estimation_requests` (
  `id` int(11) NOT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `address` varchar(255) NOT NULL,
  `property_type` enum('appartement','maison','studio','loft','villa','duplex') DEFAULT 'appartement',
  `surface` decimal(8,2) NOT NULL,
  `rooms` int(11) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `condition` enum('neuf','bon','moyen','renovation') DEFAULT 'moyen',
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `special_features` text DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `bant_budget` enum('150-300k','300-500k','500k-1m','1m+') DEFAULT NULL,
  `bant_authority` enum('moi','couple','famille','other') DEFAULT NULL,
  `bant_need` enum('oui','peut-etre','non','heritage') DEFAULT NULL,
  `bant_timeline` enum('immédiat','futur','curiosité') DEFAULT NULL,
  `seller_type` enum('proprietaire','investisseur','succession','autre') DEFAULT 'proprietaire',
  `estimated_price_low` decimal(12,2) DEFAULT NULL,
  `estimated_price_mean` decimal(12,2) DEFAULT NULL,
  `estimated_price_high` decimal(12,2) DEFAULT NULL,
  `estimation_justification` text DEFAULT NULL,
  `estimation_date` datetime DEFAULT NULL,
  `status` enum('nouveau','en-cours','rdv-planifie','estimation-envoyee','avis-demande','termine','abandonne') DEFAULT 'nouveau',
  `priority` enum('basse','normal','haute','urgente') DEFAULT 'normal',
  `assigned_agent` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `contacted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : estimation_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_settings`
--

CREATE TABLE IF NOT EXISTS `estimation_settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `type` enum('string','number','boolean','json') DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `estimation_settings`
--

INSERT INTO `estimation_settings` (`id`, `key`, `value`, `type`, `created_at`, `updated_at`) VALUES
(1, 'openai_api_key', '', 'string', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(2, 'perplexity_api_key', '', 'string', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(3, 'enable_free_estimation', 'true', 'boolean', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(4, 'enable_appraisal_rdv', 'true', 'boolean', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(5, 'estimation_response_time', '24', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(6, 'price_adjustment_factor', '1.0', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(7, 'bant_weight_need', '40', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(8, 'bant_weight_timeline', '35', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(9, 'bant_weight_authority', '15', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(10, 'bant_weight_budget', '10', 'number', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(11, 'notification_email', '', 'string', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(12, 'from_email', 'estimation@immolocal.com', 'string', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(13, 'from_name', 'ÉCOSYSTÈME IMMO LOCAL+', 'string', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(14, 'auto_send_estimation', 'false', 'boolean', '2026-01-24 01:38:53', '2026-01-24 01:38:53');


-- ------------------------------------------------------------
-- Table : estimation_templates
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `estimation_templates`
--

CREATE TABLE IF NOT EXISTS `estimation_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('confirmation','rdv','estimation','followup','custom') DEFAULT 'custom',
  `status` enum('actif','inactif') DEFAULT 'actif',
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `estimation_templates`
--

INSERT INTO `estimation_templates` (`id`, `name`, `type`, `status`, `subject`, `body`, `variables`, `created_at`, `updated_at`) VALUES
(1, 'confirmation_estimation', 'confirmation', 'actif', 'Confirmez votre estimation immobilière', 'Bonjour {{name}},\n\nMerci de votre intérêt pour notre service d\'estimation. Vos informations ont bien été reçues.\n\nVous recevrez votre estimation dans les prochaines 24 heures.\n\nCordialement,\nL\'équipe IMMO LOCAL+', '{\"name\": \"Nom du client\", \"property\": \"Adresse du bien\"}', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(2, 'rdv_proposal', 'rdv', 'actif', 'Proposition de rendez-vous pour votre avis de valeur', 'Bonjour {{name}},\n\nNous serions ravis de vous proposer un rendez-vous pour un avis de valeur approfondi.\n\nPropositions:\n- {{date1}} à {{time1}}\n- {{date2}} à {{time2}}\n\nPouvez-vous nous confirmer?', '{\"name\": \"Nom\", \"date1\": \"Date\", \"time1\": \"Heure\", \"date2\": \"Date\", \"time2\": \"Heure\"}', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(3, 'send_estimation', 'estimation', 'actif', 'Votre estimation immobilière - {{address}}', 'Bonjour {{name}},\n\nVeuillez trouver ci-joint votre estimation pour le bien situé à {{address}}.\n\nEstimation: {{price_low}}€ à {{price_high}}€\n\nN\'hésitez pas à nous contacter pour plus d\'informations.', '{\"name\": \"Nom\", \"address\": \"Adresse\", \"price_low\": \"Prix bas\", \"price_high\": \"Prix haut\"}', '2026-01-24 01:38:53', '2026-01-24 01:38:53'),
(4, 'followup', 'followup', 'actif', 'Avez-vous d\'autres questions sur votre estimation?', 'Bonjour {{name}},\n\nJe vous relance concernant votre estimation du {{date}}. Avez-vous d\'autres questions?\n\nJe suis disponible pour discuter des résultats quand vous le souhaiter.', '{\"name\": \"Nom\", \"date\": \"Date\"}', '2026-01-24 01:38:53', '2026-01-24 01:38:53');


-- ------------------------------------------------------------
-- Table : demandes_estimation
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `demandes_estimation`
--

CREATE TABLE IF NOT EXISTS `demandes_estimation` (
  `id` int(10) UNSIGNED NOT NULL,
  `type_bien` varchar(100) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `cp` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `etat` varchar(50) NOT NULL,
  `surface` int(10) UNSIGNED NOT NULL,
  `estimation_basse` int(10) UNSIGNED DEFAULT NULL,
  `estimation_moyenne` int(10) UNSIGNED DEFAULT NULL,
  `estimation_haute` int(10) UNSIGNED DEFAULT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'en-attente',
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `source` varchar(50) DEFAULT 'formulaire',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `demandes_estimation`
--

INSERT INTO `demandes_estimation` (`id`, `type_bien`, `adresse`, `cp`, `ville`, `etat`, `surface`, `estimation_basse`, `estimation_moyenne`, `estimation_haute`, `statut`, `email`, `telephone`, `source`, `created_at`) VALUES
(1, 'Appartement', '33 charle feiges', '74120', 'Megeve', 'Bon état', 100, NULL, NULL, NULL, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 16:05:23'),
(2, 'Appartement', '33 charle feiges', '74120', 'Megeve', 'Bon état', 100, NULL, NULL, NULL, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 16:08:24'),
(3, 'Appartement', '26 bis rue de la jaunaie', '44640', 'Le Pellerin', 'Neuf', 100, NULL, NULL, NULL, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 16:09:53'),
(4, 'Appartement', '231 Rue Saint-Honoré', '75001', 'Paris', 'Neuf', 100, 466000, 518000, 569000, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 16:25:39'),
(5, 'Appartement', '231 Rue Saint-Honoré', '75001', 'Paris', 'Neuf', 100, 466000, 518000, 569000, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 20:03:02'),
(6, 'Appartement', '78 Rue des freres lumières', '83500', 'LA SEYNE-SUR-MER', 'Neuf', 100, 466000, 518000, 569000, 'en-attente', NULL, NULL, 'formulaire', '2025-11-12 21:56:51'),
(7, 'Appartement', '231 Rue Saint-Honoré', '75001', 'Paris', 'Bon état', 100, 405000, 450000, 495000, 'en-attente', NULL, NULL, 'formulaire', '2025-11-13 01:35:39'),
(8, 'Appartement', '78 Rue des freres lumières', '83500', 'LA SEYNE-SUR-MER', 'Excellent état', 100, 446000, 495000, 545000, 'en-attente', NULL, NULL, 'formulaire', '2025-11-13 16:47:35');


