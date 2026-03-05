-- ============================================================
-- MODULE : Guides / Ressources (guides)
-- Fichier : guides.sql
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
-- Table : guides
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `guides`
--

CREATE TABLE IF NOT EXISTS `guides` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `contenu` longtext DEFAULT NULL,
  `persona` varchar(255) DEFAULT NULL,
  `raison_vente` varchar(255) DEFAULT NULL,
  `categorie` varchar(255) NOT NULL,
  `niveau_conscience` varchar(100) DEFAULT NULL,
  `objectif` text DEFAULT NULL,
  `focus_keyword` varchar(255) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `fichier_pdf` varchar(500) DEFAULT NULL,
  `statut` enum('brouillon','publie') DEFAULT 'brouillon',
  `date_publication` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('published','draft','archived') DEFAULT 'draft',
  `downloads_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `guides`
--

INSERT INTO `guides` (`id`, `titre`, `slug`, `description`, `contenu`, `persona`, `raison_vente`, `categorie`, `niveau_conscience`, `objectif`, `focus_keyword`, `seo_title`, `seo_description`, `image`, `fichier_pdf`, `statut`, `date_publication`, `created_at`, `updated_at`, `status`, `downloads_count`) VALUES
(1, 'Comment estimer son bien à Bordeaux', 'estimer-bien-bordeaux', NULL, NULL, NULL, NULL, 'Prix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(2, 'Prix au m² à Bordeaux : comprendre et analyser', 'prix-m2-bordeaux', NULL, NULL, NULL, NULL, 'Prix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(3, 'Pourquoi les estimations en ligne se trompent', 'erreurs-estimations-en-ligne', NULL, NULL, NULL, NULL, 'Prix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(4, 'Choisir le bon prix pour vendre vite', 'choisir-bon-prix', NULL, NULL, NULL, NULL, 'Prix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(5, 'Estimer un bien avec travaux', 'estimer-bien-travaux', NULL, NULL, NULL, NULL, 'Prix', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(6, 'Les 15 étapes pour vendre à Bordeaux', 'etapes-vente-bordeaux', NULL, NULL, NULL, NULL, 'Processus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(7, 'La check-list avant de vendre', 'checklist-vente', NULL, NULL, NULL, NULL, 'Processus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(8, 'Les diagnostics obligatoires', 'diagnostics-obligatoires', NULL, NULL, NULL, NULL, 'Processus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(9, 'Documents nécessaires pour vendre', 'documents-vente', NULL, NULL, NULL, NULL, 'Processus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(10, 'Sécuriser sa vente immobilière', 'securiser-vente', NULL, NULL, NULL, NULL, 'Processus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(11, 'Comment décider le bon moment pour vendre', 'moment-pour-vendre', NULL, NULL, NULL, NULL, 'Psychologie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(12, 'Gérer le stress d\'une vente', 'stress-vente', NULL, NULL, NULL, NULL, 'Psychologie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(13, 'Gérer les conflits familiaux', 'conflits-vente', NULL, NULL, NULL, NULL, 'Psychologie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(14, 'Les erreurs émotionnelles d’une vente', 'erreurs-emotionnelles-vente', NULL, NULL, NULL, NULL, 'Psychologie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(15, 'Vendre avec travaux : guide complet', 'vendre-avec-travaux', NULL, NULL, NULL, NULL, 'Rénovation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(16, 'Home staging intelligent', 'home-staging', NULL, NULL, NULL, NULL, 'Rénovation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(17, 'Que rénover avant de vendre ?', 'renover-avant-vendre', NULL, NULL, NULL, NULL, 'Rénovation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(18, 'Vendre un DPE F ou G', 'vendre-dpe-f-g', NULL, NULL, NULL, NULL, 'Rénovation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(19, 'Vendre un bien ancien', 'vendre-bien-ancien', NULL, NULL, NULL, NULL, 'Rénovation', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(20, 'Créer une annonce irrésistible', 'annonce-irresistible', NULL, NULL, NULL, NULL, 'Stratégie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(21, 'Attirer les bons acheteurs', 'attirer-acheteurs', NULL, NULL, NULL, NULL, 'Stratégie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(22, 'Éviter les touristes immobiliers', 'eviter-touristes-immobiliers', NULL, NULL, NULL, NULL, 'Stratégie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(23, 'Négocier intelligemment', 'negociation-immobiliere', NULL, NULL, NULL, NULL, 'Stratégie', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(24, 'Vendre un bien occupé', 'vendre-bien-occupe', NULL, NULL, NULL, NULL, 'Cas particuliers', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(25, 'Vendre rapidement', 'vendre-rapidement', NULL, NULL, NULL, NULL, 'Cas particuliers', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(26, 'Vendre après changement de vie', 'vente-changement-de-vie', NULL, NULL, NULL, NULL, 'Cas particuliers', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(27, 'Vendre un bien à contraintes', 'bien-a-contraintes', NULL, NULL, NULL, NULL, 'Cas particuliers', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(28, 'Erreurs juridiques à éviter', 'erreurs-juridiques-vente', NULL, NULL, NULL, NULL, 'Légal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(29, 'Comprendre promesse et acte', 'promesse-acte', NULL, NULL, NULL, NULL, 'Légal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(30, 'Éviter les litiges entre héritiers', 'litiges-heritiers', NULL, NULL, NULL, NULL, 'Légal', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(31, 'Les 5 profils d’acheteurs à Bordeaux', 'profils-acheteurs-bordeaux', NULL, NULL, NULL, NULL, 'Acheteurs', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(32, 'Dossier bancaire solide', 'dossier-bancaire-solide', NULL, NULL, NULL, NULL, 'Acheteurs', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(33, 'Préparer les visites', 'preparer-visites', NULL, NULL, NULL, NULL, 'Acheteurs', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(34, 'Vendre vite sans perdre d’argent', 'vendre-vite-sans-perdre', NULL, NULL, NULL, NULL, 'Rapide', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0),
(35, 'Créer l’urgence (méthode éthique)', 'creer-urgence-ethique', NULL, NULL, NULL, NULL, 'Rapide', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'brouillon', NULL, '2025-11-14 06:58:01', '2025-11-14 06:58:01', 'draft', 0);


-- ------------------------------------------------------------
-- Table : guide_downloads
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `guide_downloads`
--

CREATE TABLE IF NOT EXISTS `guide_downloads` (
  `id` int(11) NOT NULL,
  `mailing_id` int(11) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `capture_id` int(11) DEFAULT NULL,
  `date_telechargement` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


