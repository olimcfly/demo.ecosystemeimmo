-- ============================================================
-- MODULE : Facebook / Social (facebook)
-- Fichier : facebook.sql
-- Généré le : 2026-02-12
-- Tables existantes : 4
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : facebook_ideas
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `facebook_ideas`
--

CREATE TABLE IF NOT EXISTS `facebook_ideas` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `persona_type` enum('acheteur','vendeur') NOT NULL,
  `post_type` enum('attirer','connecter','convertir') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_used` tinyint(1) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `facebook_ideas`
--

INSERT INTO `facebook_ideas` (`id`, `website_id`, `persona_type`, `post_type`, `title`, `description`, `is_used`, `used_count`, `created_at`) VALUES
(1, NULL, 'acheteur', 'attirer', 'Les frais cachés d\'un achat immobilier', 'Liste des frais auxquels on ne pense pas : notaire, taxe foncière, travaux...', 0, 0, '2026-01-30 01:15:11'),
(2, NULL, 'vendeur', 'connecter', 'Ma plus grosse erreur en début de carrière', 'Storytelling personnel sur un apprentissage', 0, 0, '2026-01-30 01:15:11'),
(3, NULL, 'vendeur', 'convertir', 'Défi : je vends votre bien en 30 jours', 'Offre avec engagement de résultat', 0, 0, '2026-01-30 01:15:11');


-- ------------------------------------------------------------
-- Table : facebook_posts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `facebook_posts`
--

CREATE TABLE IF NOT EXISTS `facebook_posts` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `persona_id` int(11) DEFAULT NULL,
  `post_type` enum('attirer','connecter','convertir') NOT NULL DEFAULT 'attirer',
  `motivation` text NOT NULL COMMENT 'Hook / Accroche',
  `explication` text NOT NULL COMMENT 'Contexte / Histoire',
  `resultat` text NOT NULL COMMENT 'Transformation / Bénéfice',
  `exercice` text NOT NULL COMMENT 'Engagement / Question',
  `full_content` text DEFAULT NULL COMMENT 'Post complet assemblé',
  `image_suggestion` text DEFAULT NULL COMMENT 'Suggestion image ou reel',
  `scheduled_date` date NOT NULL,
  `status` enum('draft','planned','published') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `facebook_posts`
--

INSERT INTO `facebook_posts` (`id`, `website_id`, `persona_id`, `post_type`, `motivation`, `explication`, `resultat`, `exercice`, `full_content`, `image_suggestion`, `scheduled_date`, `status`, `published_at`, `notes`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'attirer', 'J\'ai vu tellement d\'acheteurs rater leur coup de cœur à cause de cette erreur...', 'La semaine dernière, un couple adorable visitait une maison parfaite pour eux. Luminosité, jardin, quartier calme. Tout y était.\r\n\r\nMais ils ont voulu \"réfléchir encore quelques jours\".\r\n\r\nRésultat ? Un autre acheteur l\'a signée le lendemain.', 'En immobilier, la bonne affaire n\'attend pas. Ce n\'est pas de la pression commerciale, c\'est juste la réalité d\'un marché où les bons biens partent vite.\r\n\r\nMon conseil : visitez préparé. Connaissez votre budget, vos priorités. Et quand c\'est le bon, foncez.', 'Et vous, avez-vous déjà laissé passer un bien que vous regrettez encore ? 👇', NULL, NULL, '2026-02-02', 'planned', NULL, NULL, '2026-01-30 01:15:11', '2026-01-30 01:15:11'),
(2, NULL, 2, 'connecter', 'J\'ai refusé un mandat la semaine dernière. Voici pourquoi.', 'Un propriétaire m\'a contacté pour vendre son appartement. \r\n\r\nLe problème ? Il voulait un prix 40 000€ au-dessus du marché \"pour voir\". \r\n\r\nJ\'aurais pu dire oui, prendre le mandat, faire des visites qui n\'aboutissent jamais, et attendre qu\'il accepte de baisser dans 6 mois.', 'Mais ce n\'est pas ma façon de travailler.\r\n\r\nJe préfère être honnête dès le départ : un bien mal positionné en prix fait perdre du temps à tout le monde. Et surtout, il finit par se vendre MOINS cher qu\'au bon prix dès le départ.\r\n\r\nC\'est pour ça que je sélectionne mes mandats.', 'Vous seriez plutôt du genre \"prix élevé pour négocier\" ou \"bon prix dès le départ\" ? Curieux d\'avoir vos avis 👇', NULL, NULL, '2026-02-06', 'planned', NULL, NULL, '2026-01-30 01:15:11', '2026-01-30 01:15:11'),
(3, NULL, 1, 'convertir', 'Vous cherchez à acheter et vous ne savez pas par où commencer ?', 'Rechercher un bien immobilier peut vite devenir un parcours du combattant :\r\n- Annonces périmées\r\n- Biens qui ne correspondent pas\r\n- Visites à perte de temps\r\n- Stress des démarches\r\n\r\nEt pendant ce temps, les bonnes affaires vous passent sous le nez.', 'C\'est pour ça que je propose à 3 personnes motivées une recherche personnalisée OFFERTE.\r\n\r\nJe prends le temps de comprendre votre projet, votre budget, vos critères. Et je vous envoie uniquement les biens qui correspondent vraiment.', 'Intéressé(e) ? Envoyez-moi un message avec \"RECHERCHE\" et je vous recontacte cette semaine.', NULL, NULL, '2026-02-09', 'planned', NULL, NULL, '2026-01-30 01:15:11', '2026-01-30 01:15:11');


-- ------------------------------------------------------------
-- Table : facebook_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `facebook_settings`
--

CREATE TABLE IF NOT EXISTS `facebook_settings` (
  `id` int(11) NOT NULL,
  `website_id` int(11) NOT NULL,
  `reminder_enabled` tinyint(1) DEFAULT 1,
  `reminder_email` varchar(255) DEFAULT NULL,
  `reminder_days_before` int(11) DEFAULT 1,
  `default_persona_id` int(11) DEFAULT NULL,
  `posts_per_week` int(11) DEFAULT 2,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : social_posts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `social_posts`
--

CREATE TABLE IF NOT EXISTS `social_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL COMMENT 'Titre interne',
  `content` text NOT NULL COMMENT 'Contenu du post',
  `content_facebook` text DEFAULT NULL COMMENT 'Contenu spécifique Facebook',
  `content_instagram` text DEFAULT NULL COMMENT 'Contenu spécifique Instagram',
  `content_linkedin` text DEFAULT NULL COMMENT 'Contenu spécifique LinkedIn',
  `platforms` varchar(100) DEFAULT 'facebook' COMMENT 'Plateformes (facebook,instagram,linkedin)',
  `media_type` enum('none','image','video','carousel','story','reel') DEFAULT 'image',
  `media_url` varchar(500) DEFAULT NULL COMMENT 'URL du média principal',
  `media_urls` text DEFAULT NULL COMMENT 'URLs des médias (JSON pour carousel)',
  `hashtags` text DEFAULT NULL COMMENT 'Hashtags',
  `link_url` varchar(500) DEFAULT NULL COMMENT 'Lien à inclure',
  `property_id` int(11) DEFAULT NULL COMMENT 'Bien immobilier associé',
  `article_id` int(11) DEFAULT NULL COMMENT 'Article associé',
  `status` enum('draft','scheduled','publishing','published','failed','archived') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `fb_post_id` varchar(100) DEFAULT NULL COMMENT 'ID post Facebook',
  `ig_post_id` varchar(100) DEFAULT NULL COMMENT 'ID post Instagram',
  `li_post_id` varchar(100) DEFAULT NULL COMMENT 'ID post LinkedIn',
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `shares_count` int(11) DEFAULT 0,
  `reach_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


