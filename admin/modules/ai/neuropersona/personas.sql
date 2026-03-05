-- ============================================================
-- MODULE : Personas (personas)
-- Fichier : personas.sql
-- Généré le : 2026-02-12
-- Tables existantes : 3
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : personas
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `personas`
--

CREATE TABLE IF NOT EXISTS `personas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('acheteur','vendeur') NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `age_moyen` varchar(50) DEFAULT NULL,
  `situation_familiale` varchar(255) DEFAULT NULL,
  `revenus` varchar(100) DEFAULT NULL,
  `motivations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`motivations`)),
  `objections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`objections`)),
  `problemes_clefs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`problemes_clefs`)),
  `aspirations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`aspirations`)),
  `canaux_preferes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`canaux_preferes`)),
  `avatar_url` varchar(255) DEFAULT NULL,
  `couleur` varchar(7) DEFAULT '#6366f1',
  `icone` varchar(50) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : personas_vendeurs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `personas_vendeurs`
--

CREATE TABLE IF NOT EXISTS `personas_vendeurs` (
  `id` int(11) NOT NULL,
  `raison` varchar(120) NOT NULL,
  `persona` varchar(200) NOT NULL,
  `ville` varchar(120) NOT NULL DEFAULT 'Bordeaux'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `personas_vendeurs`
--

INSERT INTO `personas_vendeurs` (`id`, `raison`, `persona`, `ville`) VALUES
(1, 'Divorce / Séparation', 'Vendeur en divorce', 'Bordeaux'),
(2, 'Divorce / Séparation', 'Divorce / séparation conflictuelle', 'Bordeaux'),
(3, 'Divorce / Séparation', 'Vente forcée (décision judiciaire)', 'Bordeaux'),
(4, 'Divorce / Séparation', 'Rachat impossible d’une part', 'Bordeaux'),
(5, 'Divorce / Séparation', 'L’un des deux bloque la vente', 'Bordeaux'),
(6, 'Succession / Héritage', 'Succession simple', 'Bordeaux'),
(7, 'Succession / Héritage', 'Succession multi-héritiers', 'Bordeaux'),
(8, 'Succession / Héritage', 'Héritiers en désaccord', 'Bordeaux'),
(9, 'Succession / Héritage', 'Bien occupé par un héritier', 'Bordeaux'),
(10, 'Succession / Héritage', 'Vente urgente pour payer les droits', 'Bordeaux'),
(11, 'Difficulté financière', 'Vente avant saisie', 'Bordeaux'),
(12, 'Difficulté financière', 'Surendettement', 'Bordeaux'),
(13, 'Difficulté financière', 'Crédit relais trop lourd', 'Bordeaux'),
(14, 'Difficulté financière', 'Achat déjà signé', 'Bordeaux'),
(15, 'Difficulté financière', 'Vente express', 'Bordeaux'),
(16, 'Mutation professionnelle', 'Mutation à l’étranger depuis Bordeaux', 'Bordeaux'),
(17, 'Mutation professionnelle', 'Changement de ville urgent', 'Bordeaux'),
(18, 'Mutation professionnelle', 'Nouveau poste imposé', 'Bordeaux'),
(19, 'Mutation professionnelle', 'Entreprise qui déménage', 'Bordeaux'),
(20, 'Mutation professionnelle', 'Vente rapide pour relocation', 'Bordeaux'),
(21, 'Retraite', 'Maison trop grande à entretenir', 'Bordeaux'),
(22, 'Retraite', 'Vente pour résidence sénior', 'Bordeaux'),
(23, 'Retraite', 'Personne âgée seule', 'Bordeaux'),
(24, 'Retraite', 'Fragilité / accessibilité', 'Bordeaux'),
(25, 'Retraite', 'Plus envie de gérer un bien', 'Bordeaux'),
(26, 'Maison trop grande', 'Départ des enfants', 'Bordeaux'),
(27, 'Maison trop grande', 'Nouvel équilibre familial', 'Bordeaux'),
(28, 'Maison trop grande', 'Entretien difficile', 'Bordeaux'),
(29, 'Maison trop grande', 'Coûts trop élevés', 'Bordeaux'),
(30, 'Maison trop grande', 'Déménagement vers plus petit', 'Bordeaux'),
(31, 'Agrandissement famille', 'Appartement trop petit', 'Bordeaux'),
(32, 'Agrandissement famille', 'Besoin d’un jardin', 'Bordeaux'),
(33, 'Agrandissement famille', 'Besoin de calme', 'Bordeaux'),
(34, 'Agrandissement famille', 'Recherche d’école ciblée', 'Bordeaux'),
(35, 'Agrandissement famille', 'Contraintes de transport (tram)', 'Bordeaux'),
(36, 'Bailleur', 'Bien loué à Bordeaux', 'Bordeaux'),
(37, 'Bailleur', 'Locataires difficiles', 'Bordeaux'),
(38, 'Bailleur', 'Fin de bail compliquée', 'Bordeaux'),
(39, 'Bailleur', 'Appartement à rénover', 'Bordeaux'),
(40, 'Bailleur', 'Rentabilité trop faible', 'Bordeaux'),
(41, 'Investisseur', 'Arbitrage de patrimoine', 'Bordeaux'),
(42, 'Investisseur', 'Vente après rénovation', 'Bordeaux'),
(43, 'Investisseur', 'Immeuble de rapport', 'Bordeaux'),
(44, 'Investisseur', 'Rendement faible', 'Bordeaux'),
(45, 'Investisseur', 'Fin de stratégie locative', 'Bordeaux'),
(46, 'Mandat expiré', 'Mandat expiré sans visite', 'Bordeaux'),
(47, 'Mandat expiré', 'Mauvaise estimation précédente', 'Bordeaux'),
(48, 'Mandat expiré', 'Photos non professionnelles', 'Bordeaux'),
(49, 'Mandat expiré', 'Trop de touristes immobiliers', 'Bordeaux'),
(50, 'Mandat expiré', 'Découragé par une agence', 'Bordeaux'),
(51, 'Bien à rénover', 'Travaux lourds impossibles', 'Bordeaux'),
(52, 'Bien à rénover', 'Échoppe ancienne à refaire', 'Bordeaux'),
(53, 'Bien à rénover', 'Maison délabrée héritée', 'Bordeaux'),
(54, 'Bien à rénover', 'Budget rénovation trop élevé', 'Bordeaux'),
(55, 'Bien à rénover', 'Logement classé F ou G', 'Bordeaux'),
(56, 'Événement difficile', 'Deuil', 'Bordeaux'),
(57, 'Événement difficile', 'Burn-out / surcharge', 'Bordeaux'),
(58, 'Événement difficile', 'Séparation non réglée', 'Bordeaux'),
(59, 'Événement difficile', 'Nécessité émotionnelle de vendre', 'Bordeaux');


-- ------------------------------------------------------------
-- Table : audiences
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `audiences`
--

CREATE TABLE IF NOT EXISTS `audiences` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `type` enum('CI','LAL','Site','Video','Interaction','Prospect','Client') NOT NULL,
  `source` varchar(100) DEFAULT NULL COMMENT 'Facebook, Instagram, Site Web, etc',
  `criteres` text DEFAULT NULL COMMENT 'JSON des critères de ciblage',
  `taille_estimee` int(11) DEFAULT NULL,
  `periode_jours` int(11) DEFAULT NULL COMMENT '7, 30, 180 jours',
  `statut` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


