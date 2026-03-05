-- ============================================================
-- MODULE : Intelligence Artificielle (ai)
-- Fichier : ai.sql
-- Généré le : 2026-02-12
-- Tables existantes : 7
-- Tables à créer : 0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ============================================================
-- TABLES EXISTANTES (extraites du dump)
-- ============================================================

-- ------------------------------------------------------------
-- Table : ai_generated_content
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_generated_content`
--

CREATE TABLE IF NOT EXISTS `ai_generated_content` (
  `id` int(11) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `content_type` varchar(100) NOT NULL,
  `content` longtext NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache du contenu généré par IA avec Claude API';


-- ------------------------------------------------------------
-- Table : ai_generations
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_generations`
--

CREATE TABLE IF NOT EXISTS `ai_generations` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `prompt_general_id` int(11) DEFAULT NULL,
  `prompt_article` text DEFAULT NULL,
  `focus_keyword` varchar(255) DEFAULT NULL,
  `secondary_keywords` text DEFAULT NULL,
  `target_word_count` int(11) DEFAULT 1500,
  `semantic_target` int(11) DEFAULT 60,
  `tone` varchar(50) DEFAULT 'professionnel',
  `generated_content` longtext DEFAULT NULL,
  `model_used` varchar(100) DEFAULT NULL,
  `tokens_used` int(11) DEFAULT NULL,
  `generation_time` float DEFAULT NULL,
  `seo_score` int(11) DEFAULT 0,
  `semantic_score` int(11) DEFAULT 0,
  `serp_score` int(11) DEFAULT 0,
  `status` enum('pending','completed','error','applied') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Table : ai_prompts
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_prompts`
--

CREATE TABLE IF NOT EXISTS `ai_prompts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('general','article','seo','custom') NOT NULL DEFAULT 'general',
  `prompt_text` longtext NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_prompts`
--

INSERT INTO `ai_prompts` (`id`, `name`, `type`, `prompt_text`, `description`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Conseiller Immobilier Bordeaux - Standard', 'general', 'Tu es un rédacteur expert en immobilier pour Eduardo De Sul, conseiller immobilier indépendant chez eXp France, spécialisé sur Bordeaux et son agglomération.\n\nRÈGLES GÉNÉRALES :\n- Écrire à la première personne du singulier ou en utilisant nous selon le contexte\n- Ton professionnel mais accessible et chaleureux\n- Mettre en avant l expertise locale de Bordeaux et ses quartiers\n- Intégrer naturellement les appels à l action (prise de rendez-vous, estimation gratuite)\n- Respecter la réglementation immobilière française (loi ALUR, diagnostics obligatoires, etc.)\n- Ne jamais faire de promesses sur les prix ou les délais de vente\n- Mentionner eXp France quand c est pertinent\n\nSTYLE D ÉCRITURE :\n- Phrases courtes et impactantes\n- Paragraphes de 3-4 lignes maximum\n- Utiliser des sous-titres H2/H3 pour structurer\n- Inclure des listes à puces quand pertinent\n- Ajouter des données chiffrées locales quand possible\n- Terminer par un CTA clair', 'Prompt standard pour la rédaction d articles immobiliers pour Eduardo De Sul à Bordeaux', 1, 1, '2026-02-10 01:16:25', NULL),
(2, 'SEO Immobilier - Optimisation Sémantique', 'seo', 'CONSIGNES SEO POUR L ARTICLE :\n\nSTRUCTURE OBLIGATOIRE :\n- 1 seul H1 (titre principal avec mot-clé focus)\n- 3 à 6 H2 (sous-sections principales)\n- H3 si nécessaire\n- Introduction de 150-200 mots avec le mot-clé dans les 100 premiers mots\n- Conclusion avec CTA\n\nOPTIMISATION MOT-CLÉ :\n- Densité mot-clé focus : 1-2% du texte\n- Mot-clé dans le H1, au moins 1 H2, introduction et conclusion\n- Variations sémantiques et synonymes\n- Mots-clés secondaires intégrés naturellement\n\nRICHESSE SÉMANTIQUE :\n- Utiliser le champ lexical complet du sujet\n- Inclure des termes techniques expliqués simplement\n- Varier le vocabulaire\n- Couvrir les questions de l internaute\n\nMETA DONNÉES (à la fin) :\n- Meta title : 50-60 caractères avec mot-clé\n- Meta description : 150-160 caractères\n- Slug URL optimisé', 'Instructions SEO pour optimisation des articles', 1, 1, '2026-02-10 01:16:38', NULL);


-- ------------------------------------------------------------
-- Table : ai_prompt_categories
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_prompt_categories`
--

CREATE TABLE IF NOT EXISTS `ai_prompt_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_prompt_categories`
--

INSERT INTO `ai_prompt_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `created_at`) VALUES
(1, 'Articles & Blog', 'articles', 'Prompts pour la génération d\'articles de blog et contenus longs', 'file-text', '#3B82F6', 1, '2026-01-13 14:30:36'),
(2, 'Descriptions Biens', 'descriptions', 'Prompts pour décrire des biens immobiliers de manière vendeuse', 'home', '#10B981', 2, '2026-01-13 14:30:36'),
(3, 'SEO & Meta', 'seo', 'Prompts pour l\'optimisation SEO (titres, meta, mots-clés)', 'search', '#8B5CF6', 3, '2026-01-13 14:30:36'),
(4, 'Réseaux Sociaux', 'social', 'Prompts pour créer du contenu pour les réseaux sociaux', 'share-2', '#EC4899', 4, '2026-01-13 14:30:36'),
(5, 'Emails & Communication', 'emails', 'Prompts pour la rédaction d\'emails et communications', 'mail', '#F59E0B', 5, '2026-01-13 14:30:36'),
(6, 'Quartiers & Secteurs', 'quartiers', 'Prompts spécialisés pour les pages de quartiers', 'map-pin', '#EF4444', 6, '2026-01-13 14:30:36'),
(7, 'Personnalisés', 'custom', 'Vos prompts personnalisés', 'edit-3', '#6B7280', 99, '2026-01-13 14:30:36');


-- ------------------------------------------------------------
-- Table : ai_providers
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_providers`
--

CREATE TABLE IF NOT EXISTS `ai_providers` (
  `id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `docs_url` varchar(255) DEFAULT NULL,
  `api_key_encrypted` text DEFAULT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `default_model` varchar(100) DEFAULT NULL,
  `available_models` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`available_models`)),
  `max_tokens` int(11) DEFAULT 4096,
  `temperature` decimal(2,1) DEFAULT 0.7,
  `capabilities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`capabilities`)),
  `is_enabled` tinyint(1) DEFAULT 0,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_providers`
--

INSERT INTO `ai_providers` (`id`, `provider_key`, `name`, `description`, `logo_url`, `website_url`, `docs_url`, `api_key_encrypted`, `api_endpoint`, `default_model`, `available_models`, `max_tokens`, `temperature`, `capabilities`, `is_enabled`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'claude', 'Claude (Anthropic)', 'IA de rédaction avancée, excellente qualité en français. Idéale pour la génération d\'articles, l\'amélioration de contenu et les suggestions SEO.', NULL, 'https://console.anthropic.com/', 'https://docs.anthropic.com/', NULL, 'https://api.anthropic.com/v1/messages', 'claude-sonnet-4-20250514', '[\"claude-sonnet-4-20250514\", \"claude-opus-4-20250514\", \"claude-haiku-4-20250514\"]', 4096, 0.7, '{\"text\": true, \"images\": false, \"web_search\": false, \"code\": true}', 1, 1, '2026-01-13 14:30:36', NULL),
(2, 'perplexity', 'Perplexity', 'IA avec accès Internet en temps réel. Parfaite pour rechercher des informations actuelles, enrichir des articles avec des données récentes et faire de la veille.', NULL, 'https://www.perplexity.ai/', 'https://docs.perplexity.ai/', NULL, 'https://api.perplexity.ai/chat/completions', 'llama-3.1-sonar-large-128k-online', '[\"llama-3.1-sonar-small-128k-online\", \"llama-3.1-sonar-large-128k-online\", \"llama-3.1-sonar-huge-128k-online\"]', 4096, 0.7, '{\"text\": true, \"images\": false, \"web_search\": true, \"citations\": true}', 1, 0, '2026-01-13 14:30:36', NULL),
(3, 'openai', 'OpenAI (GPT + DALL-E)', 'Modèles GPT pour le texte et DALL-E pour la génération d\'images. Large écosystème et bonne polyvalence.', NULL, 'https://platform.openai.com/', 'https://platform.openai.com/docs', NULL, 'https://api.openai.com/v1/chat/completions', 'gpt-4-turbo-preview', '[\"gpt-4-turbo-preview\", \"gpt-4o\", \"gpt-3.5-turbo\", \"dall-e-3\"]', 4096, 0.7, '{\"text\": true, \"images\": true, \"web_search\": false, \"vision\": true}', 0, 0, '2026-01-13 14:30:36', NULL),
(4, 'mistral', 'Mistral AI', 'Alternative européenne (France), RGPD friendly. Rapide et économique, idéal pour les gros volumes.', NULL, 'https://mistral.ai/', 'https://docs.mistral.ai/', NULL, 'https://api.mistral.ai/v1/chat/completions', 'mistral-large-latest', '[\"mistral-large-latest\", \"mistral-medium-latest\", \"mistral-small-latest\"]', 4096, 0.7, '{\"text\": true, \"images\": false, \"web_search\": false, \"code\": true}', 0, 0, '2026-01-13 14:30:36', NULL);


-- ------------------------------------------------------------
-- Table : ai_settings
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_settings`
--

CREATE TABLE IF NOT EXISTS `ai_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ai_settings`
--

INSERT INTO `ai_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'default_provider', 'claude', 'string', 'Provider IA par défaut', NULL),
(2, 'cache_enabled', '0', 'boolean', 'Activer le cache des réponses', NULL),
(3, 'cache_duration', '3600', 'number', 'Durée du cache en secondes', NULL),
(4, 'log_requests', '1', 'boolean', 'Logger les requêtes IA', NULL),
(5, 'max_requests_per_hour', '100', 'number', 'Limite de requêtes par heure', NULL),
(6, 'show_token_usage', '1', 'boolean', 'Afficher l\'utilisation des tokens', NULL),
(7, 'brand_name', 'Eduardo De Sul', 'string', 'Nom de marque pour les prompts', NULL),
(8, 'brand_location', 'Bordeaux', 'string', 'Localisation pour les prompts', NULL),
(9, 'brand_context', 'Conseiller immobilier indépendant avec eXp France', 'string', 'Contexte métier', NULL),
(11, 'openai_model', 'gpt-4o', 'string', NULL, NULL),
(12, 'claude_model', 'claude-sonnet-4-20250514', 'string', NULL, NULL),
(16, 'max_tokens_per_generation', '4000', 'string', NULL, NULL),
(25, 'ai_enabled', '1', '', 'Activer/désactiver IA', NULL),
(26, 'ai_provider', 'claude', '', 'Provider par défaut', NULL),
(27, 'default_word_count', '1500', '', 'Nombre de mots cible', NULL),
(28, 'default_semantic_target', '60', '', 'Objectif sémantique', NULL),
(29, 'default_tone', 'professionnel', '', 'Ton par défaut', NULL),
(30, 'openai_api_key', '', '', 'Clé API OpenAI', NULL),
(31, 'claude_api_key', '', '', 'Clé API Claude/Anthropic', NULL);


-- ------------------------------------------------------------
-- Table : ai_usage_logs
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `ai_usage_logs`
--

CREATE TABLE IF NOT EXISTS `ai_usage_logs` (
  `id` bigint(20) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `prompt_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `input_tokens` int(11) DEFAULT NULL,
  `output_tokens` int(11) DEFAULT NULL,
  `total_tokens` int(11) DEFAULT NULL,
  `estimated_cost_cents` int(11) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 1,
  `error_message` text DEFAULT NULL,
  `response_time_ms` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


