-- ============================================================
-- MODULE : Multi-sites (websites)
-- Fichier : websites.sql
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
-- Table : websites
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `websites`
--

CREATE TABLE IF NOT EXISTS `websites` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `domain_verified` tinyint(1) DEFAULT 0,
  `domain_verified_at` datetime DEFAULT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `favicon` varchar(500) DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT '#3B82F6',
  `secondary_color` varchar(20) DEFAULT '#1E40AF',
  `font_family` varchar(100) DEFAULT 'Inter',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `homepage_id` int(11) DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `tracking_code` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `websites`
--

INSERT INTO `websites` (`id`, `name`, `slug`, `domain`, `domain_verified`, `domain_verified_at`, `logo`, `favicon`, `primary_color`, `secondary_color`, `font_family`, `status`, `homepage_id`, `settings`, `seo_title`, `seo_description`, `tracking_code`, `created_at`, `updated_at`) VALUES
(1, 'Eduardo Desul Immobilier', 'eduardo-desul', 'www.eduardo-desul-immobilier.fr', 0, NULL, NULL, NULL, '#3B82F6', '#1E40AF', 'Inter', 'published', NULL, NULL, 'Eduardo Desul - Conseiller Immobilier', NULL, NULL, '2026-01-28 19:58:00', '2026-01-28 19:58:00');


-- ------------------------------------------------------------
-- Table : website_pages
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `website_pages`
--

CREATE TABLE IF NOT EXISTS `website_pages` (
  `id` int(11) NOT NULL,
  `website_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `type` enum('page','landing','blog','funnel') DEFAULT 'page',
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `is_homepage` tinyint(1) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(500) DEFAULT NULL,
  `seo_image` varchar(500) DEFAULT NULL,
  `seo_author` varchar(255) DEFAULT NULL,
  `seo_noindex` tinyint(1) DEFAULT 0,
  `language` varchar(10) DEFAULT 'fr',
  `tracking_code` text DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_admins_username` (`username`),
  ADD UNIQUE KEY `uk_admins_email` (`email`),
  ADD KEY `idx_admins_role` (`role`),
  ADD KEY `idx_admins_active` (`is_active`);

--
-- Index pour la table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `ads_accounts`
--
ALTER TABLE `ads_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_account` (`user_id`,`ad_account_id`),
  ADD KEY `idx_ads_account_user` (`user_id`);

--
-- Index pour la table `ads_adsets`
--
ALTER TABLE `ads_adsets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audience_id` (`audience_id`),
  ADD KEY `adset_search` (`campaign_id`,`status`),
  ADD KEY `idx_ads_adsets_campaign` (`campaign_id`);

--
-- Index pour la table `ads_alerts`
--
ALTER TABLE `ads_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `adset_id` (`adset_id`),
  ADD KEY `creative_id` (`creative_id`),
  ADD KEY `alert_search` (`account_id`,`status`),
  ADD KEY `idx_ads_alerts_account` (`account_id`);

--
-- Index pour la table `ads_audiences`
--
ALTER TABLE `ads_audiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audience_search` (`account_id`,`temperature`,`audience_type`),
  ADD KEY `idx_ads_audiences_account` (`account_id`);

--
-- Index pour la table `ads_campaigns`
--
ALTER TABLE `ads_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_search` (`account_id`,`temperature`,`status`),
  ADD KEY `idx_ads_campaigns_account` (`account_id`);

--
-- Index pour la table `ads_checklist`
--
ALTER TABLE `ads_checklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_search` (`account_id`,`step_number`);

--
-- Index pour la table `ads_creatives`
--
ALTER TABLE `ads_creatives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creative_search` (`adset_id`,`status`),
  ADD KEY `idx_ads_creatives_adset` (`adset_id`);

--
-- Index pour la table `ads_naming_templates`
--
ALTER TABLE `ads_naming_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_templates` (`user_id`,`entity_type`);

--
-- Index pour la table `ads_performance`
--
ALTER TABLE `ads_performance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adset_id` (`adset_id`),
  ADD KEY `creative_id` (`creative_id`),
  ADD KEY `perf_search` (`campaign_id`,`date`),
  ADD KEY `idx_ads_perf_date` (`campaign_id`,`date`);

--
-- Index pour la table `ads_prerequisites`
--
ALTER TABLE `ads_prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_prereq` (`account_id`),
  ADD KEY `idx_ads_prereq_account` (`account_id`);

--
-- Index pour la table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Index pour la table `ai_generated_content`
--
ALTER TABLE `ai_generated_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_content` (`page_name`,`content_type`),
  ADD KEY `page_name` (`page_name`),
  ADD KEY `content_type` (`content_type`),
  ADD KEY `generated_at` (`generated_at`),
  ADD KEY `idx_page_type` (`page_name`,`content_type`),
  ADD KEY `idx_recent` (`generated_at` DESC);

--
-- Index pour la table `ai_generations`
--
ALTER TABLE `ai_generations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prompt_general_id` (`prompt_general_id`);

--
-- Index pour la table `ai_prompts`
--
ALTER TABLE `ai_prompts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `ai_prompt_categories`
--
ALTER TABLE `ai_prompt_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `ai_providers`
--
ALTER TABLE `ai_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `provider_key` (`provider_key`),
  ADD KEY `idx_provider_key` (`provider_key`),
  ADD KEY `idx_is_enabled` (`is_enabled`);

--
-- Index pour la table `ai_settings`
--
ALTER TABLE `ai_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `ai_usage_logs`
--
ALTER TABLE `ai_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_provider` (`provider_key`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_prompt` (`prompt_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_action` (`action`);

--
-- Index pour la table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_key` (`service_key`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_service_key` (`service_key`);

--
-- Index pour la table `api_logs`
--
ALTER TABLE `api_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `integration_id` (`integration_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_service` (`service_name`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_status` (`response_status`);

--
-- Index pour la table `api_usage`
--
ALTER TABLE `api_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_service_month` (`integration_id`,`month_start`);

--
-- Index pour la table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_start` (`start_datetime`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_articles_status` (`status`),
  ADD KEY `idx_articles_slug` (`slug`),
  ADD KEY `idx_articles_date` (`date_publication`);

--
-- Index pour la table `article_categories`
--
ALTER TABLE `article_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_active` (`is_active`);

--
-- Index pour la table `audiences`
--
ALTER TABLE `audiences`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `biens`
--
ALTER TABLE `biens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type_bien` (`type_bien`),
  ADD KEY `idx_ville` (`ville`),
  ADD KEY `idx_prix` (`prix`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_reference` (`reference`),
  ADD KEY `idx_recherche` (`ville`,`type_bien`,`prix`,`statut`),
  ADD KEY `idx_published` (`published_at`,`statut`),
  ADD KEY `idx_coup_coeur` (`coup_de_coeur`,`statut`);

--
-- Index pour la table `bien_demandes_visite`
--
ALTER TABLE `bien_demandes_visite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bien_id` (`bien_id`);

--
-- Index pour la table `bien_favoris`
--
ALTER TABLE `bien_favoris`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favori` (`bien_id`,`user_id`),
  ADD KEY `idx_bien_id` (`bien_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Index pour la table `bien_photos`
--
ALTER TABLE `bien_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bien_id` (`bien_id`);

--
-- Index pour la table `builder_block_types`
--
ALTER TABLE `builder_block_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `builder_content`
--
ALTER TABLE `builder_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_context_entity` (`context`,`entity_id`),
  ADD KEY `layout_id` (`layout_id`);

--
-- Index pour la table `builder_global_variables`
--
ALTER TABLE `builder_global_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `var_key` (`var_key`);

--
-- Index pour la table `builder_layouts`
--
ALTER TABLE `builder_layouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `builder_revisions`
--
ALTER TABLE `builder_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`);

--
-- Index pour la table `builder_saved_blocks`
--
ALTER TABLE `builder_saved_blocks`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `builder_sections`
--
ALTER TABLE `builder_sections`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `builder_templates`
--
ALTER TABLE `builder_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `layout_id` (`layout_id`);

--
-- Index pour la table `builder_variables`
--
ALTER TABLE `builder_variables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_item_var` (`item_type`,`item_id`,`var_key`),
  ADD KEY `idx_item` (`item_type`,`item_id`);

--
-- Index pour la table `campagnes`
--
ALTER TABLE `campagnes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `campagne_ads`
--
ALTER TABLE `campagne_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_adset` (`adset_id`);

--
-- Index pour la table `campagne_adsets`
--
ALTER TABLE `campagne_adsets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_campagne` (`campagne_id`);

--
-- Index pour la table `campagne_google_ads`
--
ALTER TABLE `campagne_google_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_groupe` (`groupe_id`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `campagne_google_extensions`
--
ALTER TABLE `campagne_google_extensions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campagne` (`campagne_id`),
  ADD KEY `idx_type` (`type_extension`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `campagne_google_groups`
--
ALTER TABLE `campagne_google_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_campagne` (`campagne_id`),
  ADD KEY `idx_intention` (`intention`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `campagne_google_keywords`
--
ALTER TABLE `campagne_google_keywords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_groupe` (`groupe_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type` (`type_correspondance`);

--
-- Index pour la table `campagne_google_negatives`
--
ALTER TABLE `campagne_google_negatives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_negative` (`campagne_id`,`mot_cle_negatif`),
  ADD KEY `idx_campagne` (`campagne_id`);

--
-- Index pour la table `campagne_kpis`
--
ALTER TABLE `campagne_kpis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_campagne_kpi` (`campagne_id`),
  ADD KEY `idx_date` (`date`);

--
-- Index pour la table `captures`
--
ALTER TABLE `captures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `captures_stats`
--
ALTER TABLE `captures_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_capture_date` (`capture_id`,`date`),
  ADD KEY `idx_date` (`date`);

--
-- Index pour la table `capture_pages`
--
ALTER TABLE `capture_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `copy_angles`
--
ALTER TABLE `copy_angles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `crm_config`
--
ALTER TABLE `crm_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name` (`setting_name`);

--
-- Index pour la table `crm_exports`
--
ALTER TABLE `crm_exports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Index pour la table `demandes_estimation`
--
ALTER TABLE `demandes_estimation`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_at`),
  ADD KEY `idx_campaign` (`campaign_id`);

--
-- Index pour la table `estimations`
--
ALTER TABLE `estimations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_statut` (`statut`);

--
-- Index pour la table `estimation_contacts`
--
ALTER TABLE `estimation_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Index pour la table `estimation_leads`
--
ALTER TABLE `estimation_leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD KEY `idx_temperature` (`temperature`),
  ADD KEY `idx_bant_score` (`bant_score`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `estimation_rdv`
--
ALTER TABLE `estimation_rdv`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_confirmed_date` (`confirmed_date`),
  ADD KEY `agent_id` (`agent_id`);

--
-- Index pour la table `estimation_reports`
--
ALTER TABLE `estimation_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request_id` (`request_id`),
  ADD KEY `rdv_id` (`rdv_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `estimation_requests`
--
ALTER TABLE `estimation_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_id` (`request_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_bant_need` (`bant_need`),
  ADD KEY `idx_seller_type` (`seller_type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `assigned_agent` (`assigned_agent`);
ALTER TABLE `estimation_requests` ADD FULLTEXT KEY `idx_fulltext` (`name`,`email`,`address`);

--
-- Index pour la table `estimation_settings`
--
ALTER TABLE `estimation_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `idx_key` (`key`);

--
-- Index pour la table `estimation_templates`
--
ALTER TABLE `estimation_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `facebook_ideas`
--
ALTER TABLE `facebook_ideas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_persona_type` (`persona_type`),
  ADD KEY `idx_post_type` (`post_type`);

--
-- Index pour la table `facebook_posts`
--
ALTER TABLE `facebook_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_persona` (`persona_id`),
  ADD KEY `idx_type` (`post_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`scheduled_date`);

--
-- Index pour la table `facebook_settings`
--
ALTER TABLE `facebook_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_website` (`website_id`);

--
-- Index pour la table `financements`
--
ALTER TABLE `financements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_contact` (`contact_id`);

--
-- Index pour la table `financement_courtiers`
--
ALTER TABLE `financement_courtiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_actif` (`actif`),
  ADD KEY `idx_nom` (`nom`);

--
-- Index pour la table `financement_leads`
--
ALTER TABLE `financement_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_courtier` (`courtier_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type_projet` (`type_projet`);

--
-- Index pour la table `financement_leads_logs`
--
ALTER TABLE `financement_leads_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Index pour la table `footers`
--
ALTER TABLE `footers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `status` (`status`),
  ADD KEY `is_default` (`is_default`);

--
-- Index pour la table `gmb_avis`
--
ALTER TABLE `gmb_avis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gmb_review` (`gmb_review_id`),
  ADD KEY `idx_note` (`note`),
  ADD KEY `idx_date_avis` (`date_avis`),
  ADD KEY `idx_repondu` (`repondu`),
  ADD KEY `idx_pending_reviews` (`repondu`,`date_avis`);

--
-- Index pour la table `gmb_contacts`
--
ALTER TABLE `gmb_contacts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `place_id` (`place_id`),
  ADD KEY `idx_email_status` (`email_status`),
  ADD KEY `idx_contact_type` (`contact_type`),
  ADD KEY `idx_prospect_status` (`prospect_status`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_business_name` (`business_name`);

--
-- Index pour la table `gmb_contact_lists`
--
ALTER TABLE `gmb_contact_lists`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gmb_contact_list_members`
--
ALTER TABLE `gmb_contact_list_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_member` (`contact_id`,`list_id`),
  ADD KEY `list_id` (`list_id`);

--
-- Index pour la table `gmb_email_logs`
--
ALTER TABLE `gmb_email_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_hash` (`tracking_hash`),
  ADD KEY `idx_contact` (`contact_id`),
  ADD KEY `idx_sequence_step` (`sequence_id`,`step_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`),
  ADD KEY `idx_tracking` (`tracking_hash`);

--
-- Index pour la table `gmb_email_sends`
--
ALTER TABLE `gmb_email_sends`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sequence_id` (`sequence_id`),
  ADD KEY `step_id` (`step_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_contact_sequence` (`contact_id`,`sequence_id`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Index pour la table `gmb_email_sequences`
--
ALTER TABLE `gmb_email_sequences`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gmb_email_sequence_steps`
--
ALTER TABLE `gmb_email_sequence_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sequence_order` (`sequence_id`,`step_order`);

--
-- Index pour la table `gmb_posts`
--
ALTER TABLE `gmb_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`post_type`),
  ADD KEY `idx_scheduled` (`scheduled_at`);

--
-- Index pour la table `gmb_prospects`
--
ALTER TABLE `gmb_prospects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `place_id` (`place_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_activity` (`activity`),
  ADD KEY `idx_location` (`location`);

--
-- Index pour la table `gmb_publications`
--
ALTER TABLE `gmb_publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date_publication` (`date_publication`),
  ADD KEY `idx_active_posts` (`statut`,`date_publication`);

--
-- Index pour la table `gmb_questions`
--
ALTER TABLE `gmb_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gmb_question` (`gmb_question_id`),
  ADD KEY `idx_repondu` (`repondu`),
  ADD KEY `idx_question_date` (`question_date`);

--
-- Index pour la table `gmb_results`
--
ALTER TABLE `gmb_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_search` (`search_id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_converted` (`is_converted`);

--
-- Index pour la table `gmb_reviews`
--
ALTER TABLE `gmb_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_gmb_review` (`gmb_review_id`),
  ADD KEY `idx_website` (`website_id`),
  ADD KEY `idx_rating` (`rating`);

--
-- Index pour la table `gmb_scraper_settings`
--
ALTER TABLE `gmb_scraper_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `gmb_scrape_jobs`
--
ALTER TABLE `gmb_scrape_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `gmb_searches`
--
ALTER TABLE `gmb_searches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_searches_date` (`created_at`);

--
-- Index pour la table `gmb_sequence_steps`
--
ALTER TABLE `gmb_sequence_steps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_step` (`sequence_id`,`step_order`);

--
-- Index pour la table `gmb_settings`
--
ALTER TABLE `gmb_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_website` (`website_id`);

--
-- Index pour la table `gmb_stats_daily`
--
ALTER TABLE `gmb_stats_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_period_stats` (`date`,`vues_total`);

--
-- Index pour la table `guides`
--
ALTER TABLE `guides`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_guides_status` (`status`),
  ADD KEY `idx_guides_slug` (`slug`);

--
-- Index pour la table `guide_downloads`
--
ALTER TABLE `guide_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mailing_id` (`mailing_id`),
  ADD KEY `guide_id` (`guide_id`),
  ADD KEY `idx_capture_id` (`capture_id`);

--
-- Index pour la table `headers`
--
ALTER TABLE `headers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `status` (`status`),
  ADD KEY `is_default` (`is_default`);

--
-- Index pour la table `integrations`
--
ALTER TABLE `integrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_name` (`service_name`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_service_name` (`service_name`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_service_status` (`service_name`,`test_status`);

--
-- Index pour la table `internal_links`
--
ALTER TABLE `internal_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_link` (`source_type`,`source_id`,`target_type`,`target_id`),
  ADD KEY `idx_source` (`source_type`,`source_id`),
  ADD KEY `idx_target` (`target_type`,`target_id`);

--
-- Index pour la table `landings`
--
ALTER TABLE `landings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `landing_pages`
--
ALTER TABLE `landing_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `guide_id` (`guide_id`);

--
-- Index pour la table `launchpad_ai_generations`
--
ALTER TABLE `launchpad_ai_generations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_type` (`type`);

--
-- Index pour la table `launchpad_conversions`
--
ALTER TABLE `launchpad_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Index pour la table `launchpad_metiers_library`
--
ALTER TABLE `launchpad_metiers_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `launchpad_personas_library`
--
ALTER TABLE `launchpad_personas_library`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `launchpad_sessions`
--
ALTER TABLE `launchpad_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `launchpad_step1_profil`
--
ALTER TABLE `launchpad_step1_profil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Index pour la table `launchpad_step2_persona`
--
ALTER TABLE `launchpad_step2_persona`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Index pour la table `launchpad_step3_offre`
--
ALTER TABLE `launchpad_step3_offre`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_version` (`version`);

--
-- Index pour la table `launchpad_step4_strategie`
--
ALTER TABLE `launchpad_step4_strategie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Index pour la table `launchpad_step5_plan`
--
ALTER TABLE `launchpad_step5_plan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Index pour la table `launchpad_tasks`
--
ALTER TABLE `launchpad_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_completed` (`completed`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_priority` (`priority`);

--
-- Index pour la table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_leads_partenaire` (`partenaire_id`);

--
-- Index pour la table `leads_captures`
--
ALTER TABLE `leads_captures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_capture_id` (`capture_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_traite` (`traite`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Index pour la table `lead_activities`
--
ALTER TABLE `lead_activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_type` (`type`);

--
-- Index pour la table `lead_assignment_history`
--
ALTER TABLE `lead_assignment_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Index pour la table `lead_documents`
--
ALTER TABLE `lead_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Index pour la table `lead_interactions`
--
ALTER TABLE `lead_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`interaction_date`),
  ADD KEY `idx_agent` (`agent_id`),
  ADD KEY `idx_interactions_lead_date` (`lead_id`,`interaction_date`);

--
-- Index pour la table `lead_scoring_history`
--
ALTER TABLE `lead_scoring_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Index pour la table `lead_status_history`
--
ALTER TABLE `lead_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`);

--
-- Index pour la table `local_guide`
--
ALTER TABLE `local_guide`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_website` (`website_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_city` (`city`);

--
-- Index pour la table `local_partners`
--
ALTER TABLE `local_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_website` (`website_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_backlink` (`backlink_status`);

--
-- Index pour la table `login_codes`
--
ALTER TABLE `login_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Index pour la table `mailing_list`
--
ALTER TABLE `mailing_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `guide_id` (`guide_id`),
  ADD KEY `landing_page_id` (`landing_page_id`);

--
-- Index pour la table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mandats`
--
ALTER TABLE `mandats`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `folder` (`folder`),
  ADD KEY `filetype` (`filetype`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_position` (`menu_id`,`position`);

--
-- Index pour la table `neuropersona_campagnes`
--
ALTER TABLE `neuropersona_campagnes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `persona_type_id` (`persona_type_id`),
  ADD KEY `idx_neuropersona_campagnes_user` (`user_id`),
  ADD KEY `idx_neuropersona_campagnes_statut` (`statut`);

--
-- Index pour la table `neuropersona_config`
--
ALTER TABLE `neuropersona_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_persona` (`user_id`,`persona_type_id`),
  ADD KEY `persona_type_id` (`persona_type_id`),
  ADD KEY `idx_neuropersona_config_user` (`user_id`);

--
-- Index pour la table `neuropersona_posts`
--
ALTER TABLE `neuropersona_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campagne_id` (`campagne_id`);

--
-- Index pour la table `neuropersona_types`
--
ALTER TABLE `neuropersona_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_neuropersona_types_categorie` (`categorie`),
  ADD KEY `idx_neuropersona_types_actif` (`actif`);

--
-- Index pour la table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`),
  ADD KEY `is_file_based` (`is_file_based`),
  ADD KEY `idx_template` (`template`),
  ADD KEY `idx_website_id` (`website_id`),
  ADD KEY `idx_pages_builder_mode` (`builder_mode`),
  ADD KEY `fk_pages_header` (`header_id`),
  ADD KEY `fk_pages_footer` (`footer_id`),
  ADD KEY `fk_pages_template` (`template_id`),
  ADD KEY `idx_noindex` (`noindex`),
  ADD KEY `idx_seo_validated` (`seo_validated`);

--
-- Index pour la table `pages_sections`
--
ALTER TABLE `pages_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_id` (`page_id`),
  ADD KEY `type` (`type`);

--
-- Index pour la table `pages_seo`
--
ALTER TABLE `pages_seo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page` (`page_id`),
  ADD KEY `idx_seo_score` (`seo_score`),
  ADD KEY `idx_is_indexed` (`is_indexed`),
  ADD KEY `idx_serp_position` (`serp_position`);

--
-- Index pour la table `page_sections`
--
ALTER TABLE `page_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_page` (`page_id`),
  ADD KEY `idx_position` (`position`);

--
-- Index pour la table `page_templates`
--
ALTER TABLE `page_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`);

--
-- Index pour la table `partenaires`
--
ALTER TABLE `partenaires`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `partenaire_leads`
--
ALTER TABLE `partenaire_leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partenaire_id` (`partenaire_id`),
  ADD KEY `lead_id` (`lead_id`);

--
-- Index pour la table `personas`
--
ALTER TABLE `personas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_persona` (`user_id`,`type`),
  ADD KEY `idx_persona_user` (`user_id`);

--
-- Index pour la table `personas_vendeurs`
--
ALTER TABLE `personas_vendeurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pipeline_stages`
--
ALTER TABLE `pipeline_stages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reference` (`reference`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_transaction` (`transaction`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_price` (`price`);

--
-- Index pour la table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_scope_key` (`scope`,`key_value`);

--
-- Index pour la table `rdv`
--
ALTER TABLE `rdv`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `scoring_rules`
--
ALTER TABLE `scoring_rules`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `secteurs`
--
ALTER TABLE `secteurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `status` (`status`),
  ADD KEY `type_secteur` (`type_secteur`),
  ADD KEY `ville` (`ville`),
  ADD KEY `idx_secteurs_status` (`status`),
  ADD KEY `idx_secteurs_type` (`type_secteur`),
  ADD KEY `idx_secteurs_ville` (`ville`),
  ADD KEY `idx_secteurs_slug` (`slug`),
  ADD KEY `fk_secteurs_template` (`template_id`);

--
-- Index pour la table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `section_templates`
--
ALTER TABLE `section_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_type` (`section_type`);

--
-- Index pour la table `section_types`
--
ALTER TABLE `section_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `semantic_analysis`
--
ALTER TABLE `semantic_analysis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `page_id` (`page_id`);

--
-- Index pour la table `seo_history`
--
ALTER TABLE `seo_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content` (`content_type`,`content_id`),
  ADD KEY `idx_date` (`check_date`);

--
-- Index pour la table `seo_keywords`
--
ALTER TABLE `seo_keywords`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_keyword` (`keyword`),
  ADD KEY `idx_position` (`current_position`),
  ADD KEY `idx_active` (`is_active`);

--
-- Index pour la table `seo_settings`
--
ALTER TABLE `seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_site` (`site_id`);

--
-- Index pour la table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`key_name`);

--
-- Index pour la table `simulation_leads`
--
ALTER TABLE `simulation_leads`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `site_design`
--
ALTER TABLE `site_design`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Index pour la table `sms`
--
ALTER TABLE `sms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_phone` (`recipient_phone`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_scheduled` (`scheduled_at`);

--
-- Index pour la table `sms_campagnes`
--
ALTER TABLE `sms_campagnes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sms_queue`
--
ALTER TABLE `sms_queue`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sms_settings`
--
ALTER TABLE `sms_settings`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `sms_triggers`
--
ALTER TABLE `sms_triggers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sms_triggers_template` (`template_id`);

--
-- Index pour la table `social_posts`
--
ALTER TABLE `social_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_scheduled` (`scheduled_at`),
  ADD KEY `idx_property` (`property_id`);

--
-- Index pour la table `strategy_canaux`
--
ALTER TABLE `strategy_canaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_canal` (`user_id`,`nom`);

--
-- Index pour la table `strategy_communications`
--
ALTER TABLE `strategy_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sujet_id` (`sujet_id`),
  ADD KEY `persona_strategies` (`persona_id`,`user_id`),
  ADD KEY `idx_communications_user` (`user_id`);

--
-- Index pour la table `strategy_mapping`
--
ALTER TABLE `strategy_mapping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `persona_id` (`persona_id`),
  ADD KEY `sujet_id` (`sujet_id`),
  ADD KEY `offre_id` (`offre_id`),
  ADD KEY `communication_id` (`communication_id`),
  ADD KEY `structure_id` (`structure_id`),
  ADD KEY `user_mapping` (`user_id`,`persona_id`);

--
-- Index pour la table `strategy_offres`
--
ALTER TABLE `strategy_offres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `persona_offres` (`persona_id`,`user_id`),
  ADD KEY `idx_offres_user` (`user_id`);

--
-- Index pour la table `strategy_structures`
--
ALTER TABLE `strategy_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `communication_id` (`communication_id`),
  ADD KEY `user_structures` (`user_id`,`communication_id`),
  ADD KEY `idx_structures_user` (`user_id`);

--
-- Index pour la table `strategy_sujets`
--
ALTER TABLE `strategy_sujets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `persona_sujets` (`persona_id`,`user_id`),
  ADD KEY `idx_sujets_user` (`user_id`);

--
-- Index pour la table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lead` (`lead_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `site_id` (`site_id`),
  ADD KEY `type` (`type`),
  ADD KEY `status` (`status`),
  ADD KEY `header_id` (`header_id`),
  ADD KEY `footer_id` (`footer_id`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`video_type`),
  ADD KEY `idx_property` (`property_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Index pour la table `visiteurs`
--
ALTER TABLE `visiteurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_date` (`date_visite`);

--
-- Index pour la table `websites`
--
ALTER TABLE `websites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_domain` (`domain`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `website_media`
--
ALTER TABLE `website_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_website` (`website_id`),
  ADD KEY `idx_type` (`file_type`);

--
-- Index pour la table `website_pages`
--
ALTER TABLE `website_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_page_slug` (`website_id`,`slug`),
  ADD KEY `idx_website` (`website_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`type`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_accounts`
--
ALTER TABLE `ads_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_adsets`
--
ALTER TABLE `ads_adsets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_alerts`
--
ALTER TABLE `ads_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_audiences`
--
ALTER TABLE `ads_audiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_campaigns`
--
ALTER TABLE `ads_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_checklist`
--
ALTER TABLE `ads_checklist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_creatives`
--
ALTER TABLE `ads_creatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_naming_templates`
--
ALTER TABLE `ads_naming_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_performance`
--
ALTER TABLE `ads_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ads_prerequisites`
--
ALTER TABLE `ads_prerequisites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ai_generated_content`
--
ALTER TABLE `ai_generated_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ai_generations`
--
ALTER TABLE `ai_generations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ai_prompts`
--
ALTER TABLE `ai_prompts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `ai_prompt_categories`
--
ALTER TABLE `ai_prompt_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `ai_providers`
--
ALTER TABLE `ai_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `ai_settings`
--
ALTER TABLE `ai_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `ai_usage_logs`
--
ALTER TABLE `ai_usage_logs`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `api_logs`
--
ALTER TABLE `api_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `api_usage`
--
ALTER TABLE `api_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT pour la table `article_categories`
--
ALTER TABLE `article_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `audiences`
--
ALTER TABLE `audiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `biens`
--
ALTER TABLE `biens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `bien_demandes_visite`
--
ALTER TABLE `bien_demandes_visite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bien_favoris`
--
ALTER TABLE `bien_favoris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bien_photos`
--
ALTER TABLE `bien_photos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `builder_block_types`
--
ALTER TABLE `builder_block_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pour la table `builder_content`
--
ALTER TABLE `builder_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `builder_global_variables`
--
ALTER TABLE `builder_global_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pour la table `builder_layouts`
--
ALTER TABLE `builder_layouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `builder_revisions`
--
ALTER TABLE `builder_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `builder_saved_blocks`
--
ALTER TABLE `builder_saved_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `builder_sections`
--
ALTER TABLE `builder_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `builder_templates`
--
ALTER TABLE `builder_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `builder_variables`
--
ALTER TABLE `builder_variables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagnes`
--
ALTER TABLE `campagnes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_ads`
--
ALTER TABLE `campagne_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_adsets`
--
ALTER TABLE `campagne_adsets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_google_ads`
--
ALTER TABLE `campagne_google_ads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_google_extensions`
--
ALTER TABLE `campagne_google_extensions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_google_groups`
--
ALTER TABLE `campagne_google_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_google_keywords`
--
ALTER TABLE `campagne_google_keywords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_google_negatives`
--
ALTER TABLE `campagne_google_negatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `campagne_kpis`
--
ALTER TABLE `campagne_kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `captures`
--
ALTER TABLE `captures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `captures_stats`
--
ALTER TABLE `captures_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `capture_pages`
--
ALTER TABLE `capture_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `copy_angles`
--
ALTER TABLE `copy_angles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `crm_config`
--
ALTER TABLE `crm_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `crm_exports`
--
ALTER TABLE `crm_exports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `demandes_estimation`
--
ALTER TABLE `demandes_estimation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `emails`
--
ALTER TABLE `emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimations`
--
ALTER TABLE `estimations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimation_contacts`
--
ALTER TABLE `estimation_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimation_leads`
--
ALTER TABLE `estimation_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `estimation_rdv`
--
ALTER TABLE `estimation_rdv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimation_reports`
--
ALTER TABLE `estimation_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimation_requests`
--
ALTER TABLE `estimation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `estimation_settings`
--
ALTER TABLE `estimation_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `estimation_templates`
--
ALTER TABLE `estimation_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `facebook_ideas`
--
ALTER TABLE `facebook_ideas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `facebook_posts`
--
ALTER TABLE `facebook_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `facebook_settings`
--
ALTER TABLE `facebook_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `financements`
--
ALTER TABLE `financements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `financement_courtiers`
--
ALTER TABLE `financement_courtiers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `financement_leads`
--
ALTER TABLE `financement_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `financement_leads_logs`
--
ALTER TABLE `financement_leads_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `footers`
--
ALTER TABLE `footers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `gmb_avis`
--
ALTER TABLE `gmb_avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `gmb_contacts`
--
ALTER TABLE `gmb_contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_contact_lists`
--
ALTER TABLE `gmb_contact_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `gmb_contact_list_members`
--
ALTER TABLE `gmb_contact_list_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_email_logs`
--
ALTER TABLE `gmb_email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_email_sends`
--
ALTER TABLE `gmb_email_sends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_email_sequences`
--
ALTER TABLE `gmb_email_sequences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `gmb_email_sequence_steps`
--
ALTER TABLE `gmb_email_sequence_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_posts`
--
ALTER TABLE `gmb_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_prospects`
--
ALTER TABLE `gmb_prospects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_publications`
--
ALTER TABLE `gmb_publications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `gmb_questions`
--
ALTER TABLE `gmb_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_results`
--
ALTER TABLE `gmb_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_reviews`
--
ALTER TABLE `gmb_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_scraper_settings`
--
ALTER TABLE `gmb_scraper_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `gmb_scrape_jobs`
--
ALTER TABLE `gmb_scrape_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_searches`
--
ALTER TABLE `gmb_searches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_sequence_steps`
--
ALTER TABLE `gmb_sequence_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `gmb_settings`
--
ALTER TABLE `gmb_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `gmb_stats_daily`
--
ALTER TABLE `gmb_stats_daily`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `guides`
--
ALTER TABLE `guides`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `guide_downloads`
--
ALTER TABLE `guide_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `headers`
--
ALTER TABLE `headers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `integrations`
--
ALTER TABLE `integrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `internal_links`
--
ALTER TABLE `internal_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `landings`
--
ALTER TABLE `landings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `landing_pages`
--
ALTER TABLE `landing_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_ai_generations`
--
ALTER TABLE `launchpad_ai_generations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_conversions`
--
ALTER TABLE `launchpad_conversions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_metiers_library`
--
ALTER TABLE `launchpad_metiers_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `launchpad_personas_library`
--
ALTER TABLE `launchpad_personas_library`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `launchpad_step1_profil`
--
ALTER TABLE `launchpad_step1_profil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_step2_persona`
--
ALTER TABLE `launchpad_step2_persona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_step3_offre`
--
ALTER TABLE `launchpad_step3_offre`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_step4_strategie`
--
ALTER TABLE `launchpad_step4_strategie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_step5_plan`
--
ALTER TABLE `launchpad_step5_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `launchpad_tasks`
--
ALTER TABLE `launchpad_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `leads_captures`
--
ALTER TABLE `leads_captures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_activities`
--
ALTER TABLE `lead_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_assignment_history`
--
ALTER TABLE `lead_assignment_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_documents`
--
ALTER TABLE `lead_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_interactions`
--
ALTER TABLE `lead_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_scoring_history`
--
ALTER TABLE `lead_scoring_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `lead_status_history`
--
ALTER TABLE `lead_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `local_guide`
--
ALTER TABLE `local_guide`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `local_partners`
--
ALTER TABLE `local_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `login_codes`
--
ALTER TABLE `login_codes`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `mailing_list`
--
ALTER TABLE `mailing_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `mandats`
--
ALTER TABLE `mandats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `neuropersona_campagnes`
--
ALTER TABLE `neuropersona_campagnes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `neuropersona_config`
--
ALTER TABLE `neuropersona_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `neuropersona_posts`
--
ALTER TABLE `neuropersona_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `neuropersona_types`
--
ALTER TABLE `neuropersona_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT pour la table `pages_sections`
--
ALTER TABLE `pages_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `pages_seo`
--
ALTER TABLE `pages_seo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `page_sections`
--
ALTER TABLE `page_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `page_templates`
--
ALTER TABLE `page_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `partenaires`
--
ALTER TABLE `partenaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `partenaire_leads`
--
ALTER TABLE `partenaire_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personas`
--
ALTER TABLE `personas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personas_vendeurs`
--
ALTER TABLE `personas_vendeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT pour la table `pipeline_stages`
--
ALTER TABLE `pipeline_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `rdv`
--
ALTER TABLE `rdv`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `scoring_rules`
--
ALTER TABLE `scoring_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `secteurs`
--
ALTER TABLE `secteurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `section_templates`
--
ALTER TABLE `section_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `section_types`
--
ALTER TABLE `section_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `semantic_analysis`
--
ALTER TABLE `semantic_analysis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `seo_history`
--
ALTER TABLE `seo_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seo_keywords`
--
ALTER TABLE `seo_keywords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT pour la table `simulation_leads`
--
ALTER TABLE `simulation_leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `site_design`
--
ALTER TABLE `site_design`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms`
--
ALTER TABLE `sms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_campagnes`
--
ALTER TABLE `sms_campagnes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sms_queue`
--
ALTER TABLE `sms_queue`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_settings`
--
ALTER TABLE `sms_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sms_triggers`
--
ALTER TABLE `sms_triggers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `social_posts`
--
ALTER TABLE `social_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_canaux`
--
ALTER TABLE `strategy_canaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_communications`
--
ALTER TABLE `strategy_communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_mapping`
--
ALTER TABLE `strategy_mapping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_offres`
--
ALTER TABLE `strategy_offres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_structures`
--
ALTER TABLE `strategy_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `strategy_sujets`
--
ALTER TABLE `strategy_sujets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `visiteurs`
--
ALTER TABLE `visiteurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `websites`
--
ALTER TABLE `websites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `website_media`
--
ALTER TABLE `website_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `website_pages`
--
ALTER TABLE `website_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


-- ------------------------------------------------------------
-- Table : website_media
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Structure de la table `website_media`
--

CREATE TABLE IF NOT EXISTS `website_media` (
  `id` int(11) NOT NULL,
  `website_id` int(11) DEFAULT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


