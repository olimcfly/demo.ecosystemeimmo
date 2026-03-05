-- ============================================================
-- MODULE : Sections (sections)
-- Fichier : sections.sql
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
-- Table : sections
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `sections`
--

CREATE TABLE IF NOT EXISTS `sections` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `template` longtext DEFAULT NULL,
  `fields_schema` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fields_schema`)),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ------------------------------------------------------------
-- Table : section_types
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `section_types`
--

CREATE TABLE IF NOT EXISTS `section_types` (
  `id` int(11) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `section_types`
--

INSERT INTO `section_types` (`id`, `slug`, `name`, `description`, `created_at`) VALUES
(1, 'hero', 'Hero', 'Section héro avec image, titre et CTA', '2026-01-23 22:26:16'),
(2, 'presentation', 'Présentation', 'Texte + contenu', '2026-01-23 22:26:16'),
(3, 'atouts', 'Atouts', '6 cards avec icône et texte', '2026-01-23 22:26:16'),
(4, 'marche', 'Marché', 'Prix et statistiques', '2026-01-23 22:26:16'),
(5, 'pour_qui', 'Pour qui ?', '3 cards cibles', '2026-01-23 22:26:16'),
(6, 'galerie', 'Galerie', '3 images', '2026-01-23 22:26:16'),
(7, 'conseils', 'Conseils', 'Liste de tips', '2026-01-23 22:26:16'),
(8, 'faq', 'FAQ', 'Questions/Réponses', '2026-01-23 22:26:16'),
(9, 'cta', 'CTA Final', 'Appel à action final', '2026-01-23 22:26:16');


-- ------------------------------------------------------------
-- Table : section_templates
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `section_templates`
--

CREATE TABLE IF NOT EXISTS `section_templates` (
  `id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail` varchar(500) DEFAULT NULL,
  `section_type` varchar(100) NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`content`)),
  `styles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`styles`)),
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `is_premium` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `section_templates`
--

INSERT INTO `section_templates` (`id`, `category`, `name`, `description`, `thumbnail`, `section_type`, `content`, `styles`, `settings`, `is_premium`, `sort_order`, `created_at`) VALUES
(1, 'header', 'Header Simple', 'En-tête avec logo et menu', NULL, 'header', '{\"logo\": {\"type\": \"text\", \"value\": \"VOTRE LOGO\"}, \"menu\": [{\"label\": \"Accueil\", \"url\": \"#\"}, {\"label\": \"Services\", \"url\": \"#services\"}, {\"label\": \"Contact\", \"url\": \"#contact\"}], \"cta\": {\"text\": \"Contactez-nous\", \"url\": \"#contact\", \"style\": \"primary\"}}', '{\"background\": \"#ffffff\", \"textColor\": \"#1f2937\", \"padding\": \"1rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(2, 'header', 'Header avec CTA', 'En-tête avec bouton d\'action', NULL, 'header', '{\"logo\": {\"type\": \"text\", \"value\": \"VOTRE LOGO\"}, \"menu\": [{\"label\": \"Accueil\", \"url\": \"#\"}, {\"label\": \"À propos\", \"url\": \"#about\"}, {\"label\": \"Services\", \"url\": \"#services\"}], \"cta\": {\"text\": \"Estimation gratuite\", \"url\": \"#estimation\", \"style\": \"gradient\"}}', '{\"background\": \"linear-gradient(135deg, #667eea 0%, #764ba2 100%)\", \"textColor\": \"#ffffff\", \"padding\": \"1rem 2rem\"}', NULL, 0, 2, '2026-01-26 16:21:10'),
(3, 'hero', 'Hero Immobilier', 'Section héros pour l\'immobilier', NULL, 'hero', '{\"title\": \"Votre Expert Immobilier Local\", \"subtitle\": \"Estimation gratuite et accompagnement personnalisé pour votre projet immobilier\", \"image\": \"/assets/images/hero-immo.jpg\", \"cta\": {\"primary\": {\"text\": \"Estimation gratuite\", \"url\": \"#estimation\"}, \"secondary\": {\"text\": \"Nos biens\", \"url\": \"#biens\"}}}', '{\"background\": \"linear-gradient(135deg, #1e3a5f 0%, #2d5a8b 100%)\", \"textColor\": \"#ffffff\", \"padding\": \"5rem 2rem\", \"minHeight\": \"80vh\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(4, 'hero', 'Hero avec Formulaire', 'Section héros avec formulaire de capture', NULL, 'hero-form', '{\"title\": \"Estimez votre bien en 2 minutes\", \"subtitle\": \"Recevez une estimation gratuite et sans engagement\", \"form\": {\"fields\": [{\"type\": \"text\", \"name\": \"address\", \"placeholder\": \"Adresse du bien\", \"required\": true}, {\"type\": \"select\", \"name\": \"type\", \"options\": [\"Appartement\", \"Maison\", \"Terrain\"], \"required\": true}, {\"type\": \"email\", \"name\": \"email\", \"placeholder\": \"Votre email\", \"required\": true}], \"button\": \"Estimer mon bien\"}}', '{\"background\": \"#f8fafc\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 2, '2026-01-26 16:21:10'),
(5, 'services', 'Services 3 colonnes', 'Présentation de 3 services', NULL, 'services-grid', '{\"title\": \"Nos Services\", \"subtitle\": \"Un accompagnement complet pour votre projet\", \"services\": [{\"icon\": \"home\", \"title\": \"Vente\", \"description\": \"Vendez votre bien au meilleur prix\"}, {\"icon\": \"search\", \"title\": \"Achat\", \"description\": \"Trouvez le bien de vos rêves\"}, {\"icon\": \"file-text\", \"title\": \"Estimation\", \"description\": \"Connaissez la valeur de votre bien\"}]}', '{\"background\": \"#ffffff\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(6, 'services', 'Services avec icônes', 'Grille de services avec icônes', NULL, 'services-icons', '{\"title\": \"Pourquoi nous choisir ?\", \"services\": [{\"icon\": \"award\", \"title\": \"Expertise locale\", \"description\": \"15 ans d\'expérience dans votre secteur\"}, {\"icon\": \"users\", \"title\": \"Accompagnement personnalisé\", \"description\": \"Un conseiller dédié à votre projet\"}, {\"icon\": \"trending-up\", \"title\": \"Résultats prouvés\", \"description\": \"98% de clients satisfaits\"}, {\"icon\": \"shield\", \"title\": \"Transparence\", \"description\": \"Des honoraires clairs et justes\"}]}', '{\"background\": \"#f1f5f9\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 2, '2026-01-26 16:21:10'),
(7, 'testimonials', 'Témoignages Slider', 'Carrousel de témoignages', NULL, 'testimonials-slider', '{\"title\": \"Ce que disent nos clients\", \"testimonials\": [{\"name\": \"Marie D.\", \"text\": \"Un accompagnement exceptionnel du début à la fin\", \"rating\": 5, \"photo\": null}, {\"name\": \"Pierre L.\", \"text\": \"Vente rapide et au prix souhaité\", \"rating\": 5, \"photo\": null}, {\"name\": \"Sophie M.\", \"text\": \"Très professionnel, je recommande\", \"rating\": 5, \"photo\": null}]}', '{\"background\": \"#1e3a5f\", \"textColor\": \"#ffffff\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(8, 'contact', 'Contact Formulaire', 'Section contact avec formulaire', NULL, 'contact-form', '{\"title\": \"Contactez-nous\", \"subtitle\": \"Nous sommes là pour répondre à vos questions\", \"form\": {\"fields\": [{\"type\": \"text\", \"name\": \"name\", \"placeholder\": \"Votre nom\", \"required\": true}, {\"type\": \"email\", \"name\": \"email\", \"placeholder\": \"Votre email\", \"required\": true}, {\"type\": \"tel\", \"name\": \"phone\", \"placeholder\": \"Votre téléphone\"}, {\"type\": \"textarea\", \"name\": \"message\", \"placeholder\": \"Votre message\", \"required\": true}], \"button\": \"Envoyer\"}, \"info\": {\"address\": \"123 Rue de l\'Immobilier, 75000 Paris\", \"phone\": \"01 23 45 67 89\", \"email\": \"contact@votreagence.fr\"}}', '{\"background\": \"#ffffff\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(9, 'footer', 'Footer Complet', 'Pied de page avec colonnes', NULL, 'footer-columns', '{\"logo\": \"VOTRE LOGO\", \"columns\": [{\"title\": \"Navigation\", \"links\": [{\"label\": \"Accueil\", \"url\": \"/\"}, {\"label\": \"Nos biens\", \"url\": \"/biens\"}, {\"label\": \"Estimation\", \"url\": \"/estimation\"}]}, {\"title\": \"Contact\", \"content\": [\"123 Rue Example\", \"75000 Paris\", \"01 23 45 67 89\"]}, {\"title\": \"Suivez-nous\", \"social\": [{\"platform\": \"facebook\", \"url\": \"#\"}, {\"platform\": \"instagram\", \"url\": \"#\"}, {\"platform\": \"linkedin\", \"url\": \"#\"}]}], \"copyright\": \"© 2025 Votre Agence. Tous droits réservés.\"}', '{\"background\": \"#1f2937\", \"textColor\": \"#ffffff\", \"padding\": \"3rem 2rem 1rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(10, 'cta', 'CTA Simple', 'Appel à l\'action centré', NULL, 'cta-centered', '{\"title\": \"Prêt à démarrer votre projet ?\", \"subtitle\": \"Contactez-nous dès maintenant pour une estimation gratuite\", \"button\": {\"text\": \"Commencer\", \"url\": \"#contact\", \"style\": \"primary\"}}', '{\"background\": \"linear-gradient(135deg, #667eea 0%, #764ba2 100%)\", \"textColor\": \"#ffffff\", \"padding\": \"4rem 2rem\", \"textAlign\": \"center\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(11, 'properties', 'Grille de biens', 'Affichage de biens en grille', NULL, 'properties-grid', '{\"title\": \"Nos biens à la vente\", \"subtitle\": \"Découvrez notre sélection\", \"displayCount\": 6, \"showFilters\": true, \"linkMore\": {\"text\": \"Voir tous les biens\", \"url\": \"/biens\"}}', '{\"background\": \"#f8fafc\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(12, 'faq', 'FAQ Accordéon', 'Questions fréquentes en accordéon', NULL, 'faq-accordion', '{\"title\": \"Questions fréquentes\", \"questions\": [{\"question\": \"Comment estimer mon bien ?\", \"answer\": \"Nous proposons une estimation gratuite et sans engagement...\"}, {\"question\": \"Quels sont vos honoraires ?\", \"answer\": \"Nos honoraires sont transparents et compétitifs...\"}, {\"question\": \"Combien de temps pour vendre ?\", \"answer\": \"Le délai moyen de vente est de 3 mois...\"}]}', '{\"background\": \"#ffffff\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(13, 'content', 'Texte avec image', 'Bloc texte/image', NULL, 'text-image', '{\"title\": \"À propos de nous\", \"text\": \"Notre agence vous accompagne dans tous vos projets immobiliers depuis plus de 15 ans...\", \"image\": {\"url\": \"/assets/images/about.jpg\", \"alt\": \"Notre équipe\"}, \"imagePosition\": \"right\"}', '{\"background\": \"#ffffff\", \"textColor\": \"#1f2937\", \"padding\": \"4rem 2rem\"}', NULL, 0, 1, '2026-01-26 16:21:10'),
(14, 'content', 'Texte centré', 'Bloc de texte centré', NULL, 'text-centered', '{\"title\": \"Notre mission\", \"text\": \"Vous accompagner dans la réussite de votre projet immobilier avec expertise et bienveillance.\", \"alignment\": \"center\"}', '{\"background\": \"#f8fafc\", \"textColor\": \"#1f2937\", \"padding\": \"3rem 2rem\", \"maxWidth\": \"800px\"}', NULL, 0, 2, '2026-01-26 16:21:10');


