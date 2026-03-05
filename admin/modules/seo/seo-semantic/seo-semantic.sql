-- ============================================================
-- MODULE : Analyse Sémantique (seo-semantic)
-- Fichier : seo-semantic.sql
-- Généré le : 2026-02-12
-- Tables existantes : 1
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : semantic_analysis
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `semantic_analysis`
--

CREATE TABLE IF NOT EXISTS `semantic_analysis` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `score_semantic` int(11) DEFAULT 0,
  `analysis_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis_data`)),
  `analyzed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Déchargement des données de la table `semantic_analysis`
--

INSERT INTO `semantic_analysis` (`id`, `page_id`, `score_semantic`, `analysis_data`, `analyzed_at`) VALUES
(1, 66, 72, '{\"score_semantic\":72,\"score_label\":\"Bon\",\"topic_detected\":\"Pr\\u00e9sentation d\'un conseiller immobilier \\u00e0 Bordeaux\",\"topic_relevance\":88,\"lexical_field\":{\"covered\":[\"conseiller immobilier\",\"Bordeaux\",\"bordelais\",\"vente\",\"bien\",\"march\\u00e9\",\"propri\\u00e9taires\",\"expertise locale\",\"quartier\",\"accompagnement\",\"ind\\u00e9pendant\",\"eXp France\"],\"covered_count\":12,\"missing_critical\":[\"achat\",\"estimation\",\"n\\u00e9gociation\",\"transaction\",\"commission\",\"mandat\"],\"missing_secondary\":[\"investissement\",\"maison\",\"appartement\",\"prix du march\\u00e9\",\"diagnostic\",\"notaire\"]},\"keyword_analysis\":{\"target_keyword\":\"\\u00c0 propos \\u2013 Eduardo De Sul, conseiller immobilier Bordeaux\",\"density\":1.5,\"occurrences\":3,\"in_title\":true,\"in_h1\":true,\"in_first_paragraph\":true,\"variations_found\":[\"conseiller immobilier ind\\u00e9pendant\",\"march\\u00e9 bordelais\",\"propri\\u00e9taires bordelais\"],\"variations_missing\":[\"agent immobilier Bordeaux\",\"immobilier Gironde\",\"vente immobili\\u00e8re Bordeaux\"]},\"semantic_suggestions\":{\"words_to_add\":[{\"word\":\"estimation\",\"importance\":\"haute\",\"context\":\"service d\'\\u00e9valuation gratuite des biens\"},{\"word\":\"transaction\",\"importance\":\"haute\",\"context\":\"accompagnement complet jusqu\'\\u00e0 la signature\"},{\"word\":\"n\\u00e9gociation\",\"importance\":\"moyenne\",\"context\":\"expertise dans la n\\u00e9gociation des prix\"}],\"expressions_to_add\":[{\"expression\":\"estimation gratuite\",\"importance\":\"haute\"},{\"expression\":\"vente immobili\\u00e8re \\u00e0 Bordeaux\",\"importance\":\"haute\"},{\"expression\":\"march\\u00e9 immobilier bordelais\",\"importance\":\"moyenne\"}],\"questions_to_answer\":[\"Quels sont vos tarifs et commissions ?\",\"Dans quels quartiers de Bordeaux intervenez-vous ?\",\"Combien de temps faut-il pour vendre un bien ?\",\"Quels services proposez-vous concr\\u00e8tement ?\"]},\"content_recommendations\":{\"word_count_current\":204,\"word_count_recommended\":500,\"paragraphs_to_add\":[\"Section sur les tarifs et la r\\u00e9mun\\u00e9ration\",\"T\\u00e9moignages clients ou r\\u00e9sultats obtenus\",\"Description d\\u00e9taill\\u00e9e des services propos\\u00e9s\"],\"sections_missing\":[\"Zones d\'intervention pr\\u00e9cises \\u00e0 Bordeaux\",\"Formations et certifications professionnelles\",\"M\\u00e9thode de travail \\u00e9tape par \\u00e9tape\"]},\"quick_wins\":[\"Ajouter \'estimation gratuite\' dans le premier paragraphe\",\"Mentionner les quartiers sp\\u00e9cifiques de Bordeaux couverts\",\"Inclure une phrase sur les \'tarifs transparents\' ou \'honoraires n\\u00e9goci\\u00e9s\'\",\"Ajouter \'vente immobili\\u00e8re\' comme variation du mot-cl\\u00e9 principal\"],\"overall_assessment\":\"Page bien structur\\u00e9e avec une approche humaine convaincante, mais manque de d\\u00e9tails techniques sur les services et les sp\\u00e9cificit\\u00e9s du march\\u00e9 bordelais. Le contenu est trop court pour une page \'\\u00c0 propos\' professionnelle et gagnerait \\u00e0 \\u00eatre enrichi avec des \\u00e9l\\u00e9ments concrets (zones d\'intervention, m\\u00e9thode, tarifs).\"}', '2026-02-08 04:02:24'),
(4, 69, 72, '{\"score_semantic\":72,\"score_label\":\"Bon\",\"topic_detected\":\"Services d\'accompagnement \\u00e0 l\'achat immobilier \\u00e0 Bordeaux\",\"topic_relevance\":88,\"lexical_field\":{\"covered\":[\"acheter\",\"achat\",\"acquisition\",\"bien\",\"immobilier\",\"bordeaux\",\"conseiller\",\"n\\u00e9gociation\",\"prix\",\"march\\u00e9\",\"s\\u00e9curisation\",\"juridique\",\"recherche\",\"vendeurs\",\"acheteurs\",\"v\\u00e9rifications\",\"chasseur immobilier\"],\"covered_count\":17,\"missing_critical\":[\"appartement\",\"maison\",\"investissement\",\"financement\",\"pr\\u00eat immobilier\",\"notaire\"],\"missing_secondary\":[\"quartiers\",\"diagnostics\",\"compromis de vente\",\"frais d\'acquisition\",\"plus-value\"]},\"keyword_analysis\":{\"target_keyword\":\"Acheter un bien\",\"density\":1.600000000000000088817841970012523233890533447265625,\"occurrences\":8,\"in_title\":true,\"in_h1\":true,\"in_first_paragraph\":true,\"variations_found\":[\"achat\",\"acquisition\",\"acheter \\u00e0 Bordeaux\",\"acheteurs\"],\"variations_missing\":[\"achat immobilier\",\"acheter appartement\",\"acheter maison\",\"acquisition immobili\\u00e8re\"]},\"semantic_suggestions\":{\"words_to_add\":[{\"word\":\"appartement\",\"importance\":\"haute\",\"context\":\"Pr\\u00e9ciser les types de biens disponibles \\u00e0 l\'achat\"},{\"word\":\"maison\",\"importance\":\"haute\",\"context\":\"Compl\\u00e9ter l\'offre de biens immobiliers\"},{\"word\":\"financement\",\"importance\":\"haute\",\"context\":\"Accompagnement dans le processus d\'achat complet\"},{\"word\":\"pr\\u00eat immobilier\",\"importance\":\"moyenne\",\"context\":\"Aspect financier de l\'acquisition\"},{\"word\":\"quartiers\",\"importance\":\"moyenne\",\"context\":\"Expertise locale sur Bordeaux\"}],\"expressions_to_add\":[{\"expression\":\"achat immobilier\",\"importance\":\"haute\"},{\"expression\":\"premi\\u00e8re acquisition\",\"importance\":\"moyenne\"},{\"expression\":\"investissement locatif\",\"importance\":\"moyenne\"}],\"questions_to_answer\":[\"Quels sont les meilleurs quartiers pour acheter \\u00e0 Bordeaux ?\",\"Comment obtenir le meilleur financement pour son achat ?\",\"Quels sont les frais d\'acquisition \\u00e0 pr\\u00e9voir ?\",\"Comment \\u00e9viter les pi\\u00e8ges lors de l\'achat d\'un bien ancien ?\"]},\"content_recommendations\":{\"word_count_current\":503,\"word_count_recommended\":800,\"paragraphs_to_add\":[\"Section sur les types de biens disponibles (appartements, maisons)\",\"Paragraphe sur l\'accompagnement au financement\",\"Section sur les quartiers recommand\\u00e9s \\u00e0 Bordeaux\",\"T\\u00e9moignages clients ou exemples concrets\"],\"sections_missing\":[\"Les \\u00e9tapes d\\u00e9taill\\u00e9es du processus d\'achat\",\"Les frais d\'acquisition et co\\u00fbts cach\\u00e9s\",\"Crit\\u00e8res de choix d\'un bien immobilier\"]},\"quick_wins\":[\"Ajouter les mots \'appartement\' et \'maison\' dans le contenu\",\"Inclure l\'expression \'achat immobilier\' 2-3 fois\",\"Mentionner les quartiers de Bordeaux sp\\u00e9cifiquement\",\"Ajouter une section sur le financement et les pr\\u00eats\",\"Pr\\u00e9ciser les types de v\\u00e9rifications juridiques effectu\\u00e9es\"],\"overall_assessment\":\"Page bien structur\\u00e9e avec un bon focus sur l\'accompagnement acheteur \\u00e0 Bordeaux. Le contenu manque de sp\\u00e9cificit\\u00e9 sur les types de biens et l\'aspect financier. L\'expertise locale pourrait \\u00eatre mieux valoris\\u00e9e avec des mentions de quartiers sp\\u00e9cifiques. L\'ajout de 200-300 mots sur ces aspects am\\u00e9liorerait significativement le r\\u00e9f\\u00e9rencement.\"}', '2026-02-08 03:43:22');


