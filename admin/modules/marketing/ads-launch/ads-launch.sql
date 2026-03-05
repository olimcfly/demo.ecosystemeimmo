-- ============================================================
-- MODULE : Publicités / Ads (ads-launch)
-- Fichier : ads-launch.sql
-- Généré le : 2026-02-12
-- Tables existantes : 20
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : ads_accounts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_accounts`
--

CREATE TABLE IF NOT EXISTS `ads_accounts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `business_manager_id` varchar(255) DEFAULT NULL,
  `ad_account_id` varchar(255) DEFAULT NULL,
  `facebook_page_id` varchar(255) DEFAULT NULL,
  `instagram_account_id` varchar(255) DEFAULT NULL,
  `pixel_id` varchar(255) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `domain_verified` tinyint(1) DEFAULT 0,
  `gtm_id` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'EUR',
  `timezone` varchar(100) DEFAULT 'Europe/Paris',
  `status` enum('setup','configured','active','paused','archived') DEFAULT 'setup',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_adsets
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_adsets`
--

CREATE TABLE IF NOT EXISTS `ads_adsets` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `audience_id` int(11) DEFAULT NULL,
  `targeting_age_min` int(11) DEFAULT 18,
  `targeting_age_max` int(11) DEFAULT 65,
  `targeting_gender` varchar(50) DEFAULT NULL,
  `targeting_countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_countries`)),
  `targeting_languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_languages`)),
  `targeting_interests` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_interests`)),
  `targeting_behaviors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`targeting_behaviors`)),
  `daily_budget` decimal(10,2) DEFAULT NULL,
  `lifetime_budget` decimal(10,2) DEFAULT NULL,
  `bid_strategy` varchar(100) DEFAULT NULL,
  `cost_cap` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `facebook_adset_id` varchar(255) DEFAULT NULL,
  `status` enum('draft','created','active','paused','ended','archived') DEFAULT 'draft',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_alerts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_alerts`
--

CREATE TABLE IF NOT EXISTS `ads_alerts` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `campaign_id` int(11) DEFAULT NULL,
  `adset_id` int(11) DEFAULT NULL,
  `creative_id` int(11) DEFAULT NULL,
  `alert_type` enum('low_performance','low_budget','high_cpc','high_frequency','naming_error','configuration_error','audience_error','missing_pixel','conversion_issue') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'warning',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `metric_name` varchar(100) DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `threshold` decimal(10,2) DEFAULT NULL,
  `status` enum('new','acknowledged','resolved','ignored') DEFAULT 'new',
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_audiences
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_audiences`
--

CREATE TABLE IF NOT EXISTS `ads_audiences` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `audience_type` enum('ci','lal','tnt','engagement','custom') NOT NULL,
  `temperature` enum('cold','warm','hot') NOT NULL,
  `facebook_audience_id` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `source_metric` varchar(100) DEFAULT NULL,
  `lookback_days` int(11) DEFAULT 180,
  `size_estimate` int(11) DEFAULT NULL,
  `size_actual` int(11) DEFAULT NULL,
  `configuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`configuration`)),
  `created_in_fb` tinyint(1) DEFAULT 0,
  `status` enum('draft','created','active','paused','archived') DEFAULT 'draft',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_campaigns
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_campaigns`
--

CREATE TABLE IF NOT EXISTS `ads_campaigns` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `objective` enum('traffic','lead','conversion','brand_awareness','reach') DEFAULT 'conversion',
  `temperature` enum('cold','warm','hot','retargeting') NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `conversion_goal` varchar(255) DEFAULT NULL,
  `daily_budget` decimal(10,2) DEFAULT NULL,
  `lifetime_budget` decimal(10,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `facebook_campaign_id` varchar(255) DEFAULT NULL,
  `status` enum('draft','created','active','paused','ended','archived') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_checklist
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_checklist`
--

CREATE TABLE IF NOT EXISTS `ads_checklist` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `step_number` int(11) DEFAULT NULL,
  `step_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_creatives
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_creatives`
--

CREATE TABLE IF NOT EXISTS `ads_creatives` (
  `id` int(11) NOT NULL,
  `adset_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `angle` varchar(255) DEFAULT NULL,
  `visual_description` varchar(255) DEFAULT NULL,
  `headline` varchar(255) DEFAULT NULL,
  `primary_text` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cta_button` varchar(100) DEFAULT NULL,
  `cta_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `creative_type` enum('image','video','carousel','collection') DEFAULT 'image',
  `facebook_creative_id` varchar(255) DEFAULT NULL,
  `status` enum('draft','created','active','paused','rejected','archived') DEFAULT 'draft',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_naming_templates
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_naming_templates`
--

CREATE TABLE IF NOT EXISTS `ads_naming_templates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `entity_type` enum('campaign','adset','creative') NOT NULL,
  `template` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `example` varchar(255) DEFAULT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_performance
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_performance`
--

CREATE TABLE IF NOT EXISTS `ads_performance` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `adset_id` int(11) DEFAULT NULL,
  `creative_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `impressions` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `spend` decimal(10,2) DEFAULT 0.00,
  `conversions` int(11) DEFAULT 0,
  `leads` int(11) DEFAULT 0,
  `revenue` decimal(10,2) DEFAULT 0.00,
  `cpc` decimal(10,2) DEFAULT NULL,
  `cpm` decimal(10,2) DEFAULT NULL,
  `ctr` decimal(5,2) DEFAULT NULL,
  `cpa` decimal(10,2) DEFAULT NULL,
  `roas` decimal(5,2) DEFAULT NULL,
  `frequency` decimal(5,2) DEFAULT NULL,
  `video_views` int(11) DEFAULT 0,
  `video_watched_25_pct` int(11) DEFAULT 0,
  `video_watched_50_pct` int(11) DEFAULT 0,
  `video_watched_75_pct` int(11) DEFAULT 0,
  `video_watched_100_pct` int(11) DEFAULT 0,
  `add_to_cart` int(11) DEFAULT 0,
  `initiate_checkout` int(11) DEFAULT 0,
  `purchases` int(11) DEFAULT 0,
  `quality_score` int(11) DEFAULT NULL,
  `relevance_score` int(11) DEFAULT NULL,
  `facebook_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`facebook_data`)),
  `sync_status` enum('pending','synced','error') DEFAULT 'pending',
  `last_synced` datetime DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ads_prerequisites
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ads_prerequisites`
--

CREATE TABLE IF NOT EXISTS `ads_prerequisites` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `pixel_installed` tinyint(1) DEFAULT 0,
  `pixel_code_copied` tinyint(1) DEFAULT 0,
  `pixel_tested` tinyint(1) DEFAULT 0,
  `gtm_installed` tinyint(1) DEFAULT 0,
  `gtm_code_copied` tinyint(1) DEFAULT 0,
  `gtm_tested` tinyint(1) DEFAULT 0,
  `conversion_purchase` tinyint(1) DEFAULT 0,
  `conversion_lead` tinyint(1) DEFAULT 0,
  `conversion_viewcontent` tinyint(1) DEFAULT 0,
  `conversion_addtocart` tinyint(1) DEFAULT 0,
  `custom_events_configured` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_events_configured`)),
  `validation_notes` text DEFAULT NULL,
  `last_test_date` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completion_percentage` int(11) DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagnes
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagnes`
--

CREATE TABLE IF NOT EXISTS `campagnes` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `type` enum('cold','warm','hot') NOT NULL DEFAULT 'cold',
  `produit` varchar(150) DEFAULT NULL,
  `objectif` varchar(100) DEFAULT NULL COMMENT 'Detection, Qualification, Conversion',
  `budget_jour` decimal(10,2) DEFAULT NULL,
  `budget_total` decimal(10,2) DEFAULT NULL,
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('brouillon','active','pause','termine') DEFAULT 'brouillon',
  `quality_score_moyen` decimal(3,1) DEFAULT NULL,
  `landing_page_1` varchar(500) DEFAULT NULL,
  `landing_page_2` varchar(500) DEFAULT NULL,
  `conversion_action_id` varchar(100) DEFAULT NULL,
  `plateforme` varchar(50) DEFAULT 'Facebook' COMMENT 'Facebook, Instagram, etc',
  `pixel_installe` tinyint(1) DEFAULT 0,
  `gtm_configure` tinyint(1) DEFAULT 0,
  `ga4_connecte` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_ads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_ads`
--

CREATE TABLE IF NOT EXISTS `campagne_ads` (
  `id` int(11) NOT NULL,
  `adset_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `angle_copy` varchar(100) DEFAULT NULL COMMENT 'Ex: Probleme, Transformation, Benefices',
  `visuel` varchar(100) DEFAULT NULL COMMENT 'Ex: Image1, Video1',
  `nomenclature` varchar(255) DEFAULT NULL COMMENT 'txt_Angle-img_Visuel',
  `titre` varchar(255) DEFAULT NULL,
  `texte` text DEFAULT NULL,
  `url_destination` varchar(500) DEFAULT NULL,
  `cta` varchar(50) DEFAULT NULL COMMENT 'En savoir plus, Télécharger, etc',
  `statut` enum('actif','pause','termine') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_adsets
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_adsets`
--

CREATE TABLE IF NOT EXISTS `campagne_adsets` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `type_audience` enum('CI','LAL','TNT','Visiteurs','Interactions','Prospects','Clients') NOT NULL,
  `audience_detail` varchar(255) DEFAULT NULL COMMENT 'Ex: Coaching-25-44-FR',
  `budget_jour` decimal(10,2) DEFAULT NULL,
  `age_min` int(3) DEFAULT 25,
  `age_max` int(3) DEFAULT 65,
  `localisation` varchar(100) DEFAULT 'FR',
  `statut` enum('actif','pause','termine') DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_kpis
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_kpis`
--

CREATE TABLE IF NOT EXISTS `campagne_kpis` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clics` int(11) DEFAULT 0,
  `cpc` decimal(10,4) DEFAULT NULL COMMENT 'Coût par clic',
  `cpm` decimal(10,4) DEFAULT NULL COMMENT 'Coût pour 1000 impressions',
  `ctr` decimal(5,2) DEFAULT NULL COMMENT 'Taux de clic %',
  `leads` int(11) DEFAULT 0,
  `cout_par_lead` decimal(10,2) DEFAULT NULL,
  `ventes` int(11) DEFAULT 0,
  `ca` decimal(12,2) DEFAULT 0.00 COMMENT 'Chiffre affaires',
  `roas` decimal(10,2) DEFAULT NULL COMMENT 'Return On Ad Spend',
  `depense` decimal(12,2) DEFAULT 0.00,
  `frequence` decimal(5,2) DEFAULT NULL COMMENT 'Nombre moyen de fois qu une personne voit la pub',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_google_ads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_google_ads`
--

CREATE TABLE IF NOT EXISTS `campagne_google_ads` (
  `id` int(11) NOT NULL,
  `groupe_id` int(11) NOT NULL,
  `titre_1` varchar(30) NOT NULL,
  `titre_2` varchar(30) DEFAULT NULL,
  `titre_3` varchar(30) DEFAULT NULL,
  `description_1` varchar(90) NOT NULL,
  `description_2` varchar(90) DEFAULT NULL,
  `url_affichee` varchar(255) DEFAULT NULL,
  `url_finale` varchar(500) NOT NULL,
  `impressions` int(11) DEFAULT 0,
  `clics` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `ctr` decimal(5,2) DEFAULT NULL,
  `statut` enum('actif','pause','supprime') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_google_extensions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_google_extensions`
--

CREATE TABLE IF NOT EXISTS `campagne_google_extensions` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `type_extension` enum('accroche','extrait','lien_site','appel','lieu','prix') NOT NULL,
  `texte` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `numero_tel` varchar(20) DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `statut` enum('actif','pause','supprime') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_google_groups
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_google_groups`
--

CREATE TABLE IF NOT EXISTS `campagne_google_groups` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `intention` enum('problem','solution','product') NOT NULL,
  `cpc_max` decimal(10,2) DEFAULT NULL,
  `budget_jour` decimal(10,2) DEFAULT NULL,
  `statut` enum('actif','pause','supprime') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_google_keywords
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_google_keywords`
--

CREATE TABLE IF NOT EXISTS `campagne_google_keywords` (
  `id` int(11) NOT NULL,
  `groupe_id` int(11) NOT NULL,
  `mot_cle` varchar(255) NOT NULL,
  `type_correspondance` enum('large','expression','exacte') DEFAULT 'expression',
  `cpc_max` decimal(10,2) DEFAULT NULL,
  `qualite_score` decimal(3,1) DEFAULT NULL,
  `impressions` int(11) DEFAULT 0,
  `clics` int(11) DEFAULT 0,
  `conversions` int(11) DEFAULT 0,
  `cout_total` decimal(10,2) DEFAULT 0.00,
  `statut` enum('actif','pause','supprime') DEFAULT 'actif',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : campagne_google_negatives
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `campagne_google_negatives`
--

CREATE TABLE IF NOT EXISTS `campagne_google_negatives` (
  `id` int(11) NOT NULL,
  `campagne_id` int(11) NOT NULL,
  `mot_cle_negatif` varchar(255) NOT NULL,
  `niveau` enum('campagne','groupe') DEFAULT 'campagne',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : copy_angles
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `copy_angles`
--

CREATE TABLE IF NOT EXISTS `copy_angles` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `categorie` enum('probleme','transformation','benefices','urgence','preuve_sociale','autorite') NOT NULL,
  `template` text DEFAULT NULL,
  `exemple` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `copy_angles`
--

INSERT INTO `copy_angles` (`id`, `nom`, `categorie`, `template`, `exemple`, `notes`, `created_at`) VALUES
(1, 'Douleur + Solution', 'probleme', 'Vous en avez marre de [PROBLEME] ? Découvrez [SOLUTION]', 'Vous en avez marre de payer trop cher votre bien ? Découvrez notre estimation gratuite', NULL, '2025-11-16 02:23:35'),
(2, 'Avant/Après', 'transformation', 'De [AVANT] à [APRES] en [TEMPS]', 'De locataire à propriétaire en 6 mois', NULL, '2025-11-16 02:23:35'),
(3, 'Bénéfice principal', 'benefices', '[BENEFICE] sans [CONTRAINTE]', 'Vendez votre bien rapidement sans commission', NULL, '2025-11-16 02:23:35'),
(4, 'Urgence limitée', 'urgence', 'Plus que [X] places pour [OFFRE]', 'Plus que 5 estimations gratuites ce mois-ci', NULL, '2025-11-16 02:23:35'),
(5, 'Preuve sociale', 'preuve_sociale', '[X] personnes ont déjà [ACTION]', '127 propriétaires ont déjà obtenu leur estimation', NULL, '2025-11-16 02:23:35'),
(6, 'Expert local', 'autorite', 'Expert [DOMAINE] depuis [X] ans à [VILLE]', 'Expert immobilier depuis 15 ans à Bordeaux', NULL, '2025-11-16 02:23:35');


