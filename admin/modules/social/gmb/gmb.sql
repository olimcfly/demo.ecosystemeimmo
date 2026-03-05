-- ============================================================
-- MODULE : Google My Business (gmb)
-- Fichier : gmb.sql
-- Généré le : 2026-02-12
-- Tables existantes : 16
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : gmb_avis
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_avis`
--

CREATE TABLE IF NOT EXISTS `gmb_avis` (
  `id` int(11) NOT NULL,
  `note` tinyint(1) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime NOT NULL,
  `auteur_nom` varchar(255) DEFAULT NULL,
  `auteur_photo` varchar(255) DEFAULT NULL,
  `auteur_profil_url` varchar(255) DEFAULT NULL,
  `repondu` tinyint(1) DEFAULT 0,
  `reponse_texte` text DEFAULT NULL,
  `reponse_date` datetime DEFAULT NULL,
  `reponse_par` int(11) DEFAULT NULL,
  `gmb_review_id` varchar(255) DEFAULT NULL,
  `gmb_account_id` varchar(255) DEFAULT NULL,
  `gmb_location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gmb_avis`
--

INSERT INTO `gmb_avis` (`id`, `note`, `commentaire`, `date_avis`, `auteur_nom`, `auteur_photo`, `auteur_profil_url`, `repondu`, `reponse_texte`, `reponse_date`, `reponse_par`, `gmb_review_id`, `gmb_account_id`, `gmb_location_id`, `created_at`, `updated_at`) VALUES
(1, 5, 'Excellent service ! Eduardo nous a accompagné tout au long de notre projet immobilier. Très professionnel et à l\'écoute.', '2026-01-07 04:39:53', 'Marie Dupont', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53'),
(2, 5, 'Je recommande vivement ! Nous avons trouvé notre appartement idéal grâce à Eduardo. Réactivité et disponibilité au top.', '2026-01-03 04:39:53', 'Jean Martin', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53'),
(3, 4, 'Bonne expérience globale. L\'agent est compétent et sympathique. Juste quelques délais un peu longs.', '2025-12-26 04:39:53', 'Sophie Bernard', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53'),
(4, 5, 'Service impeccable du début à la fin. Eduardo connaît parfaitement le marché bordelais.', '2025-12-11 04:39:53', 'Pierre Dubois', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53');

--
-- Déclencheurs `gmb_avis`
--
DELIMITER $$
CREATE TRIGGER `after_avis_insert` AFTER INSERT ON `gmb_avis` FOR EACH ROW BEGIN
  -- Vous pouvez ajouter ici du code pour mettre à jour des stats agrégées
  -- Par exemple, mettre à jour une table de synthèse
END
$$
DELIMITER ;


-- ------------------------------------------------------------
-- Table : gmb_contacts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_contacts`
--

CREATE TABLE IF NOT EXISTS `gmb_contacts` (
  `id` int(11) NOT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_category` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `reviews_count` int(11) DEFAULT 0,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `google_maps_url` varchar(500) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `email_status` enum('unknown','valid','invalid','catch_all','disposable') DEFAULT 'unknown',
  `email_validated_at` datetime DEFAULT NULL,
  `secondary_email` varchar(255) DEFAULT NULL,
  `secondary_phone` varchar(50) DEFAULT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `contact_type` enum('agent_immobilier','courtier','notaire','diagnostiqueur','architecte','decorateur','demenageur','artisan','syndic','promoteur','photographe','home_stager','autre') DEFAULT 'autre',
  `prospect_status` enum('nouveau','a_contacter','contacte','interesse','partenaire','refuse','inactif') DEFAULT 'nouveau',
  `partnership_type` enum('echange_liens','guide_local','courtier_partenaire','partenariat_global','aucun') DEFAULT 'aucun',
  `partner_reference` varchar(255) DEFAULT NULL COMMENT 'Ex: 2L Courtage par défaut pour courtiers',
  `notes` text DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `scraped_at` datetime DEFAULT current_timestamp(),
  `last_enriched_at` datetime DEFAULT NULL,
  `scrape_source` varchar(100) DEFAULT 'google_places',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_contact_lists
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_contact_lists`
--

CREATE TABLE IF NOT EXISTS `gmb_contact_lists` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3B82F6',
  `icon` varchar(50) DEFAULT 'folder',
  `contacts_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gmb_contact_lists`
--

INSERT INTO `gmb_contact_lists` (`id`, `name`, `description`, `color`, `icon`, `contacts_count`, `created_at`, `updated_at`) VALUES
(1, 'Agents Immobiliers Bordeaux', 'Conseillers et agents immobiliers zone Bordeaux', '#EF4444', 'home', 0, '2026-02-11 12:50:53', NULL),
(2, 'Courtiers & Banques', 'Courtiers en crédit et partenaires bancaires', '#F59E0B', 'credit-card', 0, '2026-02-11 12:50:53', NULL),
(3, 'Artisans & Rénovation', 'Artisans, entrepreneurs, rénovation', '#10B981', 'wrench', 0, '2026-02-11 12:50:53', NULL),
(4, 'Diagnostiqueurs', 'Diagnostiqueurs immobiliers certifiés', '#8B5CF6', 'clipboard-check', 0, '2026-02-11 12:50:53', NULL),
(5, 'Notaires & Juristes', 'Notaires et professionnels juridiques', '#6366F1', 'scale', 0, '2026-02-11 12:50:53', NULL),
(6, 'Partenaires Actifs', 'Partenaires avec collaboration en cours', '#059669', 'handshake', 0, '2026-02-11 12:50:53', NULL),
(7, 'Échange de Liens', 'Prospects pour échange de liens Google', '#0EA5E9', 'link', 0, '2026-02-11 12:50:53', NULL);


-- ------------------------------------------------------------
-- Table : gmb_contact_list_members
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_contact_list_members`
--

CREATE TABLE IF NOT EXISTS `gmb_contact_list_members` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `added_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_email_logs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_email_logs`
--

CREATE TABLE IF NOT EXISTS `gmb_email_logs` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `sequence_id` int(11) DEFAULT NULL,
  `step_id` int(11) DEFAULT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `status` enum('queued','sent','delivered','opened','clicked','replied','bounced','failed') DEFAULT 'queued',
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `bounced_reason` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `tracking_hash` varchar(64) DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : gmb_email_sends
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_email_sends`
--

CREATE TABLE IF NOT EXISTS `gmb_email_sends` (
  `id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `sequence_id` int(11) NOT NULL,
  `step_id` int(11) NOT NULL,
  `list_id` int(11) DEFAULT NULL,
  `status` enum('queued','sent','delivered','opened','clicked','replied','bounced','failed') DEFAULT 'queued',
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `bounced_at` datetime DEFAULT NULL,
  `subject_sent` varchar(255) DEFAULT NULL,
  `body_sent` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_email_sequences
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_email_sequences`
--

CREATE TABLE IF NOT EXISTS `gmb_email_sequences` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sequence_type` enum('echange_liens','guide_local','partenariat_courtier','partenariat_general','custom') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `total_steps` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gmb_email_sequences`
--

INSERT INTO `gmb_email_sequences` (`id`, `name`, `description`, `sequence_type`, `is_active`, `total_steps`, `created_at`, `updated_at`) VALUES
(1, 'Échange de Liens Google', 'Proposer un échange de liens Google My Business pour améliorer le référencement local mutuel.', 'echange_liens', 1, 0, '2026-02-11 12:57:04', NULL),
(2, 'Partenariat Local', 'Proposer un partenariat entre professionnels de l\'immobilier locaux pour recommandations croisées.', '', 1, 0, '2026-02-11 12:57:04', NULL),
(3, 'Invitation Guide Local', 'Inviter des professionnels à rejoindre notre Guide Local des partenaires immobiliers.', 'guide_local', 1, 0, '2026-02-11 12:57:04', NULL);


-- ------------------------------------------------------------
-- Table : gmb_email_sequence_steps
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_email_sequence_steps`
--

CREATE TABLE IF NOT EXISTS `gmb_email_sequence_steps` (
  `id` int(11) NOT NULL,
  `sequence_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT 1,
  `subject` varchar(255) NOT NULL,
  `body_html` longtext NOT NULL,
  `body_text` text DEFAULT NULL,
  `delay_days` int(11) DEFAULT 0,
  `send_time` time DEFAULT '09:00:00',
  `sent_count` int(11) DEFAULT 0,
  `opened_count` int(11) DEFAULT 0,
  `clicked_count` int(11) DEFAULT 0,
  `replied_count` int(11) DEFAULT 0,
  `bounced_count` int(11) DEFAULT 0,
  `stop_on_reply` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : gmb_posts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_posts`
--

CREATE TABLE IF NOT EXISTS `gmb_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Titre du post',
  `content` text NOT NULL COMMENT 'Contenu du post (max 1500 car)',
  `post_type` enum('whats_new','event','offer','product') DEFAULT 'whats_new',
  `image` varchar(255) DEFAULT NULL COMMENT 'Image du post',
  `cta_type` enum('none','book','order','shop','learn_more','sign_up','call') DEFAULT 'none',
  `cta_url` varchar(500) DEFAULT NULL COMMENT 'URL du bouton CTA',
  `event_title` varchar(255) DEFAULT NULL COMMENT 'Titre événement (si type=event)',
  `event_start` datetime DEFAULT NULL COMMENT 'Début événement',
  `event_end` datetime DEFAULT NULL COMMENT 'Fin événement',
  `offer_code` varchar(50) DEFAULT NULL COMMENT 'Code promo (si type=offer)',
  `offer_terms` text DEFAULT NULL COMMENT 'Conditions de l''offre',
  `status` enum('draft','scheduled','published','expired','failed') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `gmb_post_id` varchar(255) DEFAULT NULL COMMENT 'ID du post sur GMB',
  `views_count` int(11) DEFAULT 0,
  `clicks_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_prospects
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_prospects`
--

CREATE TABLE IF NOT EXISTS `gmb_prospects` (
  `id` int(11) NOT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `reviews_count` int(11) DEFAULT 0,
  `activity` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('new','contacted','interested','converted','not_interested') DEFAULT 'new',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : gmb_publications
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_publications`
--

CREATE TABLE IF NOT EXISTS `gmb_publications` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `type` varchar(50) DEFAULT 'post',
  `photo` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `lien_cta` varchar(255) DEFAULT NULL,
  `texte_cta` varchar(100) DEFAULT NULL,
  `type_cta` varchar(50) DEFAULT NULL,
  `code_promo` varchar(50) DEFAULT NULL,
  `date_debut_offre` datetime DEFAULT NULL,
  `date_fin_offre` datetime DEFAULT NULL,
  `conditions_offre` text DEFAULT NULL,
  `titre_evenement` varchar(255) DEFAULT NULL,
  `date_debut_evenement` datetime DEFAULT NULL,
  `date_fin_evenement` datetime DEFAULT NULL,
  `date_publication` datetime DEFAULT NULL,
  `statut` enum('brouillon','programmee','publiee','expiree') DEFAULT 'brouillon',
  `vues` int(11) DEFAULT 0,
  `clics` int(11) DEFAULT 0,
  `interactions` int(11) DEFAULT 0,
  `gmb_post_id` varchar(255) DEFAULT NULL,
  `gmb_account_id` varchar(255) DEFAULT NULL,
  `gmb_location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gmb_publications`
--

INSERT INTO `gmb_publications` (`id`, `contenu`, `type`, `photo`, `video`, `lien_cta`, `texte_cta`, `type_cta`, `code_promo`, `date_debut_offre`, `date_fin_offre`, `conditions_offre`, `titre_evenement`, `date_debut_evenement`, `date_fin_evenement`, `date_publication`, `statut`, `vues`, `clics`, `interactions`, `gmb_post_id`, `gmb_account_id`, `gmb_location_id`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 'Nouveau bien disponible ! Magnifique appartement T3 au coeur de Bordeaux. Contactez-nous pour plus d\'informations.', 'post', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 04:39:53', 'publiee', 245, 12, 0, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53', NULL),
(2, '🎉 Offre spéciale du mois ! Frais de notaire offerts sur tous nos biens jusqu\'au 31 mars. Profitez-en !', 'offre', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-10 04:39:53', 'publiee', 532, 28, 0, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53', NULL),
(3, 'Portes ouvertes ce samedi de 10h à 18h. Venez découvrir notre nouveau programme immobilier à Mérignac !', 'evenement', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-12 04:39:53', 'programmee', 0, 0, 0, NULL, NULL, NULL, '2026-01-10 03:39:53', '2026-01-10 03:39:53', NULL);


-- ------------------------------------------------------------
-- Table : gmb_questions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_questions`
--

CREATE TABLE IF NOT EXISTS `gmb_questions` (
  `id` int(11) NOT NULL,
  `question_texte` text NOT NULL,
  `question_auteur` varchar(255) DEFAULT NULL,
  `question_date` datetime NOT NULL,
  `repondu` tinyint(1) DEFAULT 0,
  `reponse_texte` text DEFAULT NULL,
  `reponse_date` datetime DEFAULT NULL,
  `reponse_par` int(11) DEFAULT NULL,
  `gmb_question_id` varchar(255) DEFAULT NULL,
  `gmb_account_id` varchar(255) DEFAULT NULL,
  `gmb_location_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_results
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_results`
--

CREATE TABLE IF NOT EXISTS `gmb_results` (
  `id` int(11) NOT NULL,
  `search_id` int(11) NOT NULL,
  `place_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `reviews_count` int(11) DEFAULT 0,
  `category` varchar(255) DEFAULT NULL,
  `hours` text DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `is_converted` tinyint(1) DEFAULT 0,
  `lead_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : gmb_reviews
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_reviews`
--

CREATE TABLE IF NOT EXISTS `gmb_reviews` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `gmb_review_id` varchar(255) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_photo_url` varchar(500) DEFAULT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `response_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `synced_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : gmb_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_settings`
--

CREATE TABLE IF NOT EXISTS `gmb_settings` (
  `id` int(11) NOT NULL,
  `website_id` int(11) NOT NULL,
  `gmb_account_id` varchar(255) DEFAULT NULL,
  `gmb_location_id` varchar(255) DEFAULT NULL,
  `api_key` text DEFAULT NULL,
  `reminder_email` varchar(255) DEFAULT NULL,
  `reminder_days_before` int(11) DEFAULT 0,
  `auto_sync_reviews` tinyint(1) DEFAULT 0,
  `last_sync_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : gmb_stats_daily
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `gmb_stats_daily`
--

CREATE TABLE IF NOT EXISTS `gmb_stats_daily` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `vues_recherche` int(11) DEFAULT 0,
  `vues_maps` int(11) DEFAULT 0,
  `vues_total` int(11) DEFAULT 0,
  `clics_site_web` int(11) DEFAULT 0,
  `clics_telephone` int(11) DEFAULT 0,
  `clics_itineraire` int(11) DEFAULT 0,
  `vues_photos` int(11) DEFAULT 0,
  `vues_photos_proprietaire` int(11) DEFAULT 0,
  `vues_photos_clients` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


