-- ============================================================
-- MODULE : Biens immobiliers (biens)
-- Fichier : biens.sql
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
-- Table : biens
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `biens`
--

CREATE TABLE IF NOT EXISTS `biens` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type_bien` varchar(50) NOT NULL,
  `type_transaction` varchar(20) DEFAULT 'vente',
  `prix` decimal(12,2) NOT NULL,
  `surface` decimal(8,2) DEFAULT NULL,
  `surface_terrain` decimal(10,2) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `complement_adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) NOT NULL,
  `departement` varchar(100) DEFAULT NULL,
  `region` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `nb_pieces` int(3) DEFAULT NULL,
  `nb_chambres` int(3) DEFAULT NULL,
  `nb_salles_bain` int(2) DEFAULT NULL,
  `nb_wc` int(2) DEFAULT NULL,
  `etage` varchar(20) DEFAULT NULL,
  `nb_etages_total` int(3) DEFAULT NULL,
  `annee_construction` int(4) DEFAULT NULL,
  `ascenseur` tinyint(1) DEFAULT 0,
  `balcon` tinyint(1) DEFAULT 0,
  `terrasse` tinyint(1) DEFAULT 0,
  `jardin` tinyint(1) DEFAULT 0,
  `piscine` tinyint(1) DEFAULT 0,
  `garage` tinyint(1) DEFAULT 0,
  `parking` tinyint(1) DEFAULT 0,
  `cave` tinyint(1) DEFAULT 0,
  `meuble` tinyint(1) DEFAULT 0,
  `dpe_energie` varchar(1) DEFAULT NULL,
  `dpe_ges` varchar(1) DEFAULT NULL,
  `consommation_energie` decimal(6,2) DEFAULT NULL,
  `emission_ges` decimal(6,2) DEFAULT NULL,
  `chauffage` varchar(50) DEFAULT NULL,
  `charges_mensuelles` decimal(8,2) DEFAULT NULL,
  `taxe_fonciere` decimal(8,2) DEFAULT NULL,
  `copropriete` tinyint(1) DEFAULT 0,
  `nb_lots_copropriete` int(5) DEFAULT NULL,
  `honoraires_charge` varchar(20) DEFAULT NULL,
  `prix_hors_honoraires` decimal(12,2) DEFAULT NULL,
  `taux_commission` decimal(5,2) DEFAULT NULL,
  `photo_principale` varchar(255) DEFAULT NULL,
  `photos` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `visite_virtuelle_url` varchar(255) DEFAULT NULL,
  `documents` text DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `statut` enum('brouillon','publie','reserve','vendu','archive') DEFAULT 'brouillon',
  `date_disponibilite` date DEFAULT NULL,
  `date_vente` date DEFAULT NULL,
  `exclusivite` tinyint(1) DEFAULT 0,
  `coup_de_coeur` tinyint(1) DEFAULT 0,
  `urgent` tinyint(1) DEFAULT 0,
  `proprietaire_nom` varchar(255) DEFAULT NULL,
  `proprietaire_email` varchar(255) DEFAULT NULL,
  `proprietaire_telephone` varchar(20) DEFAULT NULL,
  `mandat_numero` varchar(50) DEFAULT NULL,
  `mandat_date_debut` date DEFAULT NULL,
  `mandat_date_fin` date DEFAULT NULL,
  `nb_vues` int(11) DEFAULT 0,
  `nb_contacts` int(11) DEFAULT 0,
  `nb_visites` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `biens`
--

INSERT INTO `biens` (`id`, `reference`, `titre`, `description`, `type_bien`, `type_transaction`, `prix`, `surface`, `surface_terrain`, `adresse`, `complement_adresse`, `code_postal`, `ville`, `departement`, `region`, `pays`, `latitude`, `longitude`, `nb_pieces`, `nb_chambres`, `nb_salles_bain`, `nb_wc`, `etage`, `nb_etages_total`, `annee_construction`, `ascenseur`, `balcon`, `terrasse`, `jardin`, `piscine`, `garage`, `parking`, `cave`, `meuble`, `dpe_energie`, `dpe_ges`, `consommation_energie`, `emission_ges`, `chauffage`, `charges_mensuelles`, `taxe_fonciere`, `copropriete`, `nb_lots_copropriete`, `honoraires_charge`, `prix_hors_honoraires`, `taux_commission`, `photo_principale`, `photos`, `video_url`, `visite_virtuelle_url`, `documents`, `slug`, `meta_title`, `meta_description`, `keywords`, `statut`, `date_disponibilite`, `date_vente`, `exclusivite`, `coup_de_coeur`, `urgent`, `proprietaire_nom`, `proprietaire_email`, `proprietaire_telephone`, `mandat_numero`, `mandat_date_debut`, `mandat_date_fin`, `nb_vues`, `nb_contacts`, `nb_visites`, `created_at`, `updated_at`, `published_at`, `created_by`) VALUES
(1, 'APPT-2025-001', 'Appartement T3 centre-ville Bordeaux', 'Magnifique appartement rénové au cœur du centre historique de Bordeaux. Proximité immédiate des commerces et transports.', 'Appartement', 'vente', 320000.00, 75.50, NULL, NULL, NULL, '33000', 'Bordeaux', NULL, NULL, 'France', NULL, NULL, 3, 2, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'appartement-t3-centre-bordeaux-001', NULL, NULL, NULL, 'publie', NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2026-01-10 03:29:44', '2026-01-10 03:29:44', NULL, NULL),
(2, 'MAIS-2025-001', 'Maison familiale avec jardin', 'Belle maison de 120m² avec jardin arboré de 500m². Idéale pour une famille.', 'Maison', 'vente', 450000.00, 120.00, NULL, NULL, NULL, '33700', 'Mérignac', NULL, NULL, 'France', NULL, NULL, 5, 4, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'maison-familiale-jardin-merignac-001', NULL, NULL, NULL, 'publie', NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2026-01-10 03:29:44', '2026-01-10 03:29:44', NULL, NULL),
(3, 'TERR-2025-001', 'Terrain constructible viabilisé', 'Terrain de 800m² viabilisé, prêt à construire. CU obtenu.', 'Terrain', 'vente', 120000.00, 0.00, NULL, NULL, NULL, '33600', 'Pessac', NULL, NULL, 'France', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'terrain-constructible-pessac-001', NULL, NULL, NULL, 'publie', NULL, NULL, 0, 0, 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, '2026-01-10 03:29:44', '2026-01-10 03:29:44', NULL, NULL);

--
-- Déclencheurs `biens`
--
DELIMITER $$
CREATE TRIGGER `before_insert_bien` BEFORE INSERT ON `biens` FOR EACH ROW BEGIN
  IF NEW.reference IS NULL OR NEW.reference = '' THEN
    SET NEW.reference = CONCAT(
      UPPER(SUBSTRING(NEW.type_bien, 1, 4)), 
      '-', 
      YEAR(NOW()), 
      '-', 
      LPAD((SELECT COUNT(*) + 1 FROM biens WHERE YEAR(created_at) = YEAR(NOW())), 4, '0')
    );
  END IF;
  
  -- Auto-générer le slug si non fourni
  IF NEW.slug IS NULL OR NEW.slug = '' THEN
    SET NEW.slug = CONCAT(
      LOWER(REPLACE(NEW.titre, ' ', '-')), 
      '-', 
      NEW.reference
    );
  END IF;
END
$$
DELIMITER ;


-- ------------------------------------------------------------
-- Table : bien_photos
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `bien_photos`
--

CREATE TABLE IF NOT EXISTS `bien_photos` (
  `id` int(11) NOT NULL,
  `bien_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `titre` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ordre` int(3) DEFAULT 0,
  `principale` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : bien_demandes_visite
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `bien_demandes_visite`
--

CREATE TABLE IF NOT EXISTS `bien_demandes_visite` (
  `id` int(11) NOT NULL,
  `bien_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_souhaitee` date DEFAULT NULL,
  `heure_souhaitee` time DEFAULT NULL,
  `message` text DEFAULT NULL,
  `statut` enum('nouveau','confirme','realise','annule') DEFAULT 'nouveau',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : bien_favoris
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `bien_favoris`
--

CREATE TABLE IF NOT EXISTS `bien_favoris` (
  `id` int(11) NOT NULL,
  `bien_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : properties
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `properties`
--

CREATE TABLE IF NOT EXISTS `properties` (
  `id` int(11) NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `type` enum('appartement','maison','terrain','commerce','autre') DEFAULT 'appartement',
  `transaction` enum('vente','location') DEFAULT 'vente',
  `price` decimal(12,2) DEFAULT NULL,
  `charges` decimal(10,2) DEFAULT NULL,
  `surface` int(11) DEFAULT NULL,
  `land_surface` int(11) DEFAULT NULL,
  `rooms` int(11) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `total_floors` int(11) DEFAULT NULL,
  `construction_year` int(11) DEFAULT NULL,
  `energy_class` char(1) DEFAULT NULL,
  `ges_class` char(1) DEFAULT NULL,
  `description` longtext DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `neighborhood` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `virtual_tour_url` varchar(255) DEFAULT NULL,
  `status` enum('available','under_offer','sold','rented','draft') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : mandats
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `mandats`
--

CREATE TABLE IF NOT EXISTS `mandats` (
  `id` int(11) NOT NULL,
  `lead_id` int(11) NOT NULL,
  `type_bien` varchar(100) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `cp` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `prix_demande` int(11) DEFAULT NULL,
  `date_signature` date DEFAULT NULL,
  `statut` enum('actif','vendu','retiré') DEFAULT 'actif',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : transactions
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL,
  `mandat_id` int(11) NOT NULL,
  `prix_final` int(11) NOT NULL,
  `date_vente` date DEFAULT NULL,
  `acquereur_nom` varchar(150) DEFAULT NULL,
  `acquereur_email` varchar(150) DEFAULT NULL,
  `commission_agence` int(11) DEFAULT NULL,
  `commission_partenaire` int(11) DEFAULT NULL,
  `partenaire_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- ------------------------------------------------------------
-- Table : ventes
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

CREATE TABLE IF NOT EXISTS `ventes` (
  `id` int(11) NOT NULL,
  `bien` varchar(255) NOT NULL,
  `client` varchar(255) NOT NULL,
  `montant` int(11) NOT NULL,
  `statut` varchar(50) DEFAULT 'en-cours',
  `created_at` datetime DEFAULT current_timestamp(),
  `prix_vente` int(11) DEFAULT NULL,
  `honoraires_montant` int(11) DEFAULT NULL,
  `honoraires_pourcentage` decimal(5,2) DEFAULT NULL,
  `honoraires_support` varchar(20) DEFAULT 'vendeur',
  `commission_nette` int(11) DEFAULT NULL,
  `date_compromis` date DEFAULT NULL,
  `date_acte` date DEFAULT NULL,
  `numero_mandat` varchar(100) DEFAULT NULL,
  `type_mandat` varchar(100) DEFAULT NULL,
  `surface_carrez` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `bien`, `client`, `montant`, `statut`, `created_at`, `prix_vente`, `honoraires_montant`, `honoraires_pourcentage`, `honoraires_support`, `commission_nette`, `date_compromis`, `date_acte`, `numero_mandat`, `type_mandat`, `surface_carrez`) VALUES
(1, 'Maisom', 'Olivier COlas', 250000, 'en-cours', '2025-12-08 22:22:19', NULL, NULL, NULL, 'vendeur', NULL, NULL, NULL, NULL, NULL, NULL);


