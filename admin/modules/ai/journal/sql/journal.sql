-- ══════════════════════════════════════════════════════════════
-- JOURNAL ÉDITORIAL V3 — Tables SQL + Seed Data Bordeaux
-- IMMO LOCAL+ — Eduardo De Sul
-- Fichier : admin/modules/journal/sql/journal.sql
-- ══════════════════════════════════════════════════════════════

-- ============================================================
-- TABLE PRINCIPALE : editorial_journal
-- ============================================================

CREATE TABLE IF NOT EXISTS editorial_journal (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    title                 VARCHAR(500) NOT NULL,
    description           TEXT NULL,
    keywords              VARCHAR(500) NULL,
    content_type          VARCHAR(50) DEFAULT 'post',
    profile_id            VARCHAR(50) NOT NULL COMMENT 'vendeur|acheteur|investisseur|primo',
    sector_id             VARCHAR(100) NULL COMMENT 'slug secteur',
    channel_id            VARCHAR(50) NOT NULL COMMENT 'blog|gmb|facebook|instagram|tiktok|linkedin|email',
    awareness_level       VARCHAR(50) NOT NULL DEFAULT 'problem' COMMENT 'unaware|problem|solution|product|most-aware',
    objective_id          VARCHAR(50) DEFAULT 'notoriete' COMMENT 'notoriete|trafic|leads|nurturing|conversion|fidelisation|seo-local|autorite',
    week_number           TINYINT UNSIGNED NOT NULL,
    year                  SMALLINT UNSIGNED NOT NULL,
    planned_date          DATE NULL,
    planned_time          TIME NULL,
    day_of_week           TINYINT UNSIGNED NULL COMMENT '1=lundi 7=dimanche',
    priority              TINYINT UNSIGNED DEFAULT 5 COMMENT '1=urgent 10=peut attendre',
    cta_type              VARCHAR(100) NULL COMMENT 'estimation|rdv|guide-pdf|newsletter|visite-virtuelle|checklist',
    cta_text              VARCHAR(255) NULL,
    lead_magnet_title     VARCHAR(255) NULL,
    status                ENUM('idea','planned','validated','writing','ready','published','rejected') DEFAULT 'idea',
    validated_at          DATETIME NULL,
    published_at          DATETIME NULL,
    persona_id            INT NULL COMMENT 'FK logique vers neuropersona_types.id',
    created_content_id    INT NULL COMMENT 'FK logique vers articles.id ou captures.id etc.',
    created_content_type  VARCHAR(50) NULL COMMENT 'article|capture|gmb_post|social_post|email|tiktok_script',
    published_url         VARCHAR(500) NULL,
    ai_generated          TINYINT(1) DEFAULT 0,
    source_diagnostic     VARCHAR(50) NULL COMMENT 'Parcours launchpad A|B|C|D|E',
    mere_hook             VARCHAR(500) NULL COMMENT 'Accroche M.E.R.E pre-generee',
    notes                 TEXT NULL,
    created_by            INT NULL COMMENT 'FK vers admins.id',
    created_at            DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_channel_status  (channel_id, status),
    INDEX idx_channel_week    (channel_id, year, week_number),
    INDEX idx_channel_profile (channel_id, profile_id),
    INDEX idx_profile         (profile_id),
    INDEX idx_sector          (sector_id),
    INDEX idx_awareness       (awareness_level),
    INDEX idx_status          (status),
    INDEX idx_year_week       (year, week_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE CONFIG
-- ============================================================

CREATE TABLE IF NOT EXISTS editorial_journal_config (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    config_key      VARCHAR(100) UNIQUE NOT NULL,
    config_value    TEXT,
    updated_at      DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO editorial_journal_config (config_key, config_value) VALUES
('active_channels',  '["blog","gmb","facebook","instagram","tiktok","linkedin","email"]'),
('active_profiles',  '["vendeur","acheteur","investisseur","primo"]'),
('active_sectors',   '["bordeaux-centre","chartrons","saint-pierre","bastide","cauderan","merignac","pessac","talence","begles","villenave","gradignan","le-bouscat","bruges","blanquefort"]'),
('posts_per_week',   '5'),
('auto_generate',    '0'),
('default_cta',      'estimation'),
('mere_enabled',     '1'),
('advisor_name',     'Eduardo De Sul'),
('advisor_city',     'Bordeaux')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);

-- ============================================================
-- SEED : 84 idees strategiques — Bordeaux — S1 a S8
-- ============================================================

INSERT INTO editorial_journal 
(title, profile_id, sector_id, channel_id, awareness_level, objective_id, content_type, week_number, year, cta_type, priority, ai_generated, status) 
VALUES

-- ═══ SEMAINE 1 — NOTORIETE ═══
('Les prix de l''immobilier a Bordeaux Centre : bilan et perspectives 2026', 'vendeur', 'bordeaux-centre', 'blog', 'unaware', 'seo-local', 'article-pilier', 1, 2026, 'estimation', 2, 1, 'idea'),
('Acheter dans les Chartrons : le guide complet du quartier', 'acheteur', 'chartrons', 'blog', 'problem', 'seo-local', 'article-pilier', 1, 2026, 'rdv', 3, 1, 'idea'),
('Estimation gratuite a Bordeaux : comment connaitre la valeur de votre bien', 'vendeur', 'bordeaux-centre', 'gmb', 'solution', 'seo-local', 'fiche-gmb', 1, 2026, 'estimation', 2, 1, 'idea'),
('Nouveaux biens disponibles a Merignac — selection de la semaine', 'acheteur', 'merignac', 'gmb', 'product', 'conversion', 'fiche-gmb', 1, 2026, 'rdv', 3, 1, 'idea'),
('Saviez-vous que les prix au m2 a Bordeaux Centre ont evolue de +8% cette annee ?', 'vendeur', 'bordeaux-centre', 'facebook', 'unaware', 'notoriete', 'post-court', 1, 2026, NULL, 4, 1, 'idea'),
('Les Chartrons en photos : pourquoi ce quartier attire tant de nouveaux residents', 'acheteur', 'chartrons', 'facebook', 'unaware', 'notoriete', 'post-court', 1, 2026, NULL, 4, 1, 'idea'),
('Bordeaux est la 2eme ville preferee des Francais pour investir dans l''immobilier', 'investisseur', 'bordeaux-centre', 'instagram', 'unaware', 'notoriete', 'post-court', 1, 2026, NULL, 5, 1, 'idea'),
('Les plus belles facades des Chartrons — balade photo du weekend', 'acheteur', 'chartrons', 'instagram', 'unaware', 'notoriete', 'story', 1, 2026, NULL, 5, 1, 'idea'),
('3 signes que c''est le bon moment pour vendre votre bien a Bordeaux', 'vendeur', 'bordeaux-centre', 'tiktok', 'unaware', 'notoriete', 'video-script', 1, 2026, NULL, 5, 1, 'idea'),
('Vivre a Cauderan en 60 secondes — le quartier famille de Bordeaux', 'acheteur', 'cauderan', 'tiktok', 'unaware', 'notoriete', 'video-script', 1, 2026, NULL, 5, 1, 'idea'),
('Marche immobilier Bordeaux Metropole : les chiffres cles du T1 2026', 'investisseur', 'bordeaux-centre', 'linkedin', 'unaware', 'autorite', 'post-court', 1, 2026, NULL, 4, 1, 'idea'),
('Votre veille immo : les tendances du marche bordelais ce mois-ci', 'acheteur', 'bordeaux-centre', 'email', 'problem', 'nurturing', 'email', 1, 2026, 'rdv', 5, 1, 'idea'),

-- ═══ SEMAINE 2 — PROBLEME ═══
('Vendre son appartement a Bordeaux : les 7 erreurs qui coutent cher', 'vendeur', 'bordeaux-centre', 'blog', 'problem', 'seo-local', 'article-pilier', 2, 2026, 'estimation', 1, 1, 'idea'),
('Premier achat a Pessac : budget realiste et quartiers recommandes', 'primo', 'pessac', 'blog', 'problem', 'seo-local', 'article-satellite', 2, 2026, 'guide-pdf', 3, 1, 'idea'),
('Combien vaut votre maison a Merignac ? Les criteres qui comptent vraiment', 'vendeur', 'merignac', 'gmb', 'problem', 'leads', 'fiche-gmb', 2, 2026, 'estimation', 2, 1, 'idea'),
('Quartier Bastide : les prix au m2 et l''evolution du marche', 'acheteur', 'bastide', 'gmb', 'problem', 'seo-local', 'fiche-gmb', 2, 2026, 'rdv', 3, 1, 'idea'),
('Pourquoi votre bien a Pessac ne se vend pas (et comment y remedier)', 'vendeur', 'pessac', 'facebook', 'problem', 'leads', 'post-court', 2, 2026, 'estimation', 3, 1, 'idea'),
('Acheter a Begles ou Villenave ? Comparatif prix et qualite de vie', 'acheteur', 'begles', 'facebook', 'problem', 'trafic', 'post-court', 2, 2026, NULL, 4, 1, 'idea'),
('Les 5 pieges a eviter quand on vend son appartement', 'vendeur', 'bordeaux-centre', 'instagram', 'problem', 'leads', 'post-court', 2, 2026, 'estimation', 3, 1, 'idea'),
('Budget premier achat a Bordeaux : combien faut-il vraiment prevoir ?', 'primo', 'bordeaux-centre', 'instagram', 'problem', 'trafic', 'reel', 2, 2026, 'guide-pdf', 4, 1, 'idea'),
('Devenir proprietaire a 25 ans a Bordeaux, c''est possible ?', 'primo', 'pessac', 'tiktok', 'problem', 'notoriete', 'video-script', 2, 2026, NULL, 5, 1, 'idea'),
('Le marche immobilier de La Bastide : analyse trimestrielle et perspectives', 'investisseur', 'bastide', 'linkedin', 'problem', 'autorite', 'post-court', 2, 2026, NULL, 3, 1, 'idea'),
('Les erreurs classiques des vendeurs : comment les eviter', 'vendeur', 'bordeaux-centre', 'email', 'problem', 'nurturing', 'email', 2, 2026, 'estimation', 4, 1, 'idea'),

-- ═══ SEMAINE 3 — SOLUTION ═══
('Guide complet : vendre son bien a Bordeaux avec un conseiller independant', 'vendeur', 'bordeaux-centre', 'blog', 'solution', 'leads', 'article-pilier', 3, 2026, 'rdv', 1, 1, 'idea'),
('Comment un chasseur immobilier vous fait gagner du temps a Bordeaux', 'acheteur', 'bordeaux-centre', 'blog', 'solution', 'leads', 'article-satellite', 3, 2026, 'rdv', 2, 1, 'idea'),
('Estimation offerte a Talence : obtenez le vrai prix de votre bien en 24h', 'vendeur', 'talence', 'gmb', 'solution', 'leads', 'fiche-gmb', 3, 2026, 'estimation', 1, 1, 'idea'),
('Biens selectionnes a Bruges cette semaine — accompagnement personnalise', 'acheteur', 'bruges', 'gmb', 'solution', 'leads', 'fiche-gmb', 3, 2026, 'rdv', 3, 1, 'idea'),
('Avant/Apres : comment le home staging a vendu ce bien a Begles en 15 jours', 'vendeur', 'begles', 'facebook', 'solution', 'leads', 'post-court', 3, 2026, 'estimation', 2, 1, 'idea'),
('Visite virtuelle : appartement T3 lumineux dans les Chartrons', 'acheteur', 'chartrons', 'facebook', 'solution', 'trafic', 'post-court', 3, 2026, 'rdv', 3, 1, 'idea'),
('Les etapes d''une vente reussie en 30 secondes', 'vendeur', 'bordeaux-centre', 'instagram', 'solution', 'leads', 'reel', 3, 2026, 'estimation', 3, 1, 'idea'),
('Visite : maison familiale a Blanquefort avec jardin', 'acheteur', 'blanquefort', 'instagram', 'solution', 'trafic', 'story', 3, 2026, 'rdv', 4, 1, 'idea'),
('POV : vous visitez un appartement avec vue a Bordeaux Centre', 'acheteur', 'bordeaux-centre', 'tiktok', 'solution', 'trafic', 'video-script', 3, 2026, NULL, 4, 1, 'idea'),
('Investir a La Bastide : le pari gagnant du Bordeaux rive droite', 'investisseur', 'bastide', 'linkedin', 'solution', 'autorite', 'post-court', 3, 2026, 'rdv', 3, 1, 'idea'),
('3 solutions pour vendre rapidement et au meilleur prix', 'vendeur', 'bordeaux-centre', 'email', 'solution', 'leads', 'email', 3, 2026, 'estimation', 3, 1, 'idea'),

-- ═══ SEMAINE 4 — PRODUIT ═══
('Pourquoi choisir Eduardo De Sul pour vendre votre bien a Bordeaux', 'vendeur', 'bordeaux-centre', 'blog', 'product', 'conversion', 'article-pilier', 4, 2026, 'rdv', 1, 1, 'idea'),
('L''accompagnement acheteur Eduardo De Sul : de la recherche aux cles', 'acheteur', 'bordeaux-centre', 'blog', 'product', 'conversion', 'article-satellite', 4, 2026, 'rdv', 2, 1, 'idea'),
('Temoignage : vente reussie au Bouscat en 3 semaines avec Eduardo', 'vendeur', 'le-bouscat', 'gmb', 'product', 'conversion', 'fiche-gmb', 4, 2026, 'estimation', 1, 1, 'idea'),
('Temoignage acheteur : comment j''ai trouve ma maison ideale a Gradignan', 'acheteur', 'gradignan', 'gmb', 'product', 'conversion', 'fiche-gmb', 4, 2026, 'rdv', 2, 1, 'idea'),
('Les avantages eXp France : technologie + conseiller local a votre service', 'vendeur', 'bordeaux-centre', 'facebook', 'product', 'conversion', 'post-court', 4, 2026, 'rdv', 2, 1, 'idea'),
('Temoignage : comment j''ai trouve mon appartement a Merignac en 2 semaines', 'acheteur', 'merignac', 'facebook', 'product', 'conversion', 'post-court', 4, 2026, 'rdv', 3, 1, 'idea'),
('Un jour dans la vie d''un conseiller immobilier a Bordeaux', 'vendeur', 'bordeaux-centre', 'instagram', 'product', 'autorite', 'reel', 4, 2026, 'rdv', 3, 1, 'idea'),
('Comment Eduardo a accompagne Marie, 28 ans, pour son premier achat', 'primo', 'merignac', 'instagram', 'product', 'conversion', 'story', 4, 2026, 'rdv', 3, 1, 'idea'),
('La difference entre une agence classique et un conseiller eXp France', 'vendeur', 'bordeaux-centre', 'tiktok', 'product', 'conversion', 'video-script', 4, 2026, 'rdv', 4, 1, 'idea'),
('Investissement locatif etudiant a Talence : analyse rentabilite complete', 'investisseur', 'talence', 'linkedin', 'product', 'leads', 'post-court', 4, 2026, 'rdv', 2, 1, 'idea'),
('Decouvrez l''accompagnement premium Eduardo De Sul', 'vendeur', 'bordeaux-centre', 'email', 'product', 'conversion', 'email', 4, 2026, 'rdv', 2, 1, 'idea'),

-- ═══ SEMAINE 5 — PRET A AGIR ═══
('Estimation offerte : prenez RDV en ligne avec Eduardo De Sul', 'vendeur', 'bordeaux-centre', 'blog', 'most-aware', 'conversion', 'article-satellite', 5, 2026, 'estimation', 1, 1, 'idea'),
('Votre projet d''achat a Bordeaux ? RDV gratuit et sans engagement', 'acheteur', 'bordeaux-centre', 'blog', 'most-aware', 'conversion', 'article-satellite', 5, 2026, 'rdv', 1, 1, 'idea'),
('Estimation offerte cette semaine — Bordeaux et toute la metropole', 'vendeur', 'bordeaux-centre', 'gmb', 'most-aware', 'conversion', 'fiche-gmb', 5, 2026, 'estimation', 1, 1, 'idea'),
('Dernieres opportunites : biens exclusifs a Cauderan', 'acheteur', 'cauderan', 'gmb', 'most-aware', 'conversion', 'fiche-gmb', 5, 2026, 'rdv', 2, 1, 'idea'),
('Offre speciale vendeurs Merignac : accompagnement premium 360', 'vendeur', 'merignac', 'facebook', 'most-aware', 'conversion', 'post-court', 5, 2026, 'estimation', 1, 1, 'idea'),
('Vous cherchez a acheter a Bordeaux ? On en parle cette semaine', 'acheteur', 'bordeaux-centre', 'facebook', 'most-aware', 'conversion', 'post-court', 5, 2026, 'rdv', 2, 1, 'idea'),
('Estimation gratuite en 24h — lien en bio', 'vendeur', 'bordeaux-centre', 'instagram', 'most-aware', 'conversion', 'story', 5, 2026, 'estimation', 2, 1, 'idea'),
('Votre projet immobilier merite un expert local — contactez Eduardo', 'acheteur', 'bordeaux-centre', 'tiktok', 'most-aware', 'conversion', 'video-script', 5, 2026, 'rdv', 3, 1, 'idea'),
('Votre projet d''achat a Bordeaux ? Parlons-en cette semaine', 'primo', 'bordeaux-centre', 'email', 'most-aware', 'conversion', 'email', 5, 2026, 'rdv', 1, 1, 'idea'),

-- ═══ SEMAINES 6-8 — CYCLE 2 ═══
('Acheter a Pessac en 2026 : prix, quartiers et conseils pratiques', 'acheteur', 'pessac', 'blog', 'problem', 'seo-local', 'article-pilier', 6, 2026, 'rdv', 2, 1, 'idea'),
('Diagnostics immobiliers obligatoires a Bordeaux : checklist vendeur', 'vendeur', 'bordeaux-centre', 'blog', 'solution', 'seo-local', 'article-satellite', 6, 2026, 'estimation', 3, 1, 'idea'),
('Prix au m2 a Saint-Michel : analyse du marche et opportunites', 'acheteur', 'saint-michel', 'gmb', 'problem', 'seo-local', 'fiche-gmb', 6, 2026, 'rdv', 3, 1, 'idea'),
('Saint-Pierre : le coeur historique de Bordeaux — 5 raisons d''y vivre', 'acheteur', 'saint-pierre', 'facebook', 'unaware', 'notoriete', 'post-court', 6, 2026, NULL, 4, 1, 'idea'),
('Begles en pleine mutation : pourquoi les investisseurs s''y interessent', 'investisseur', 'begles', 'instagram', 'problem', 'trafic', 'post-court', 6, 2026, NULL, 4, 1, 'idea'),
('Gradignan : le secret le mieux garde de la metropole bordelaise', 'acheteur', 'gradignan', 'tiktok', 'unaware', 'notoriete', 'video-script', 6, 2026, NULL, 5, 1, 'idea'),
('Credit immobilier 2026 : taux actuels et simulation pour Bordeaux', 'primo', 'bordeaux-centre', 'blog', 'solution', 'seo-local', 'article-pilier', 7, 2026, 'guide-pdf', 2, 1, 'idea'),
('Les frais de notaire a Bordeaux : calcul detaille et astuces', 'acheteur', 'bordeaux-centre', 'blog', 'problem', 'seo-local', 'article-satellite', 7, 2026, NULL, 3, 1, 'idea'),
('PTZ 2026 a Bordeaux : etes-vous eligible ? Verification gratuite', 'primo', 'bordeaux-centre', 'gmb', 'solution', 'leads', 'fiche-gmb', 7, 2026, 'rdv', 2, 1, 'idea'),
('Les aides pour les primo-accedants que personne ne connait a Bordeaux', 'primo', 'villenave', 'facebook', 'solution', 'leads', 'post-court', 7, 2026, 'guide-pdf', 3, 1, 'idea'),
('Rendement locatif a Bordeaux : quel quartier choisir en 2026 ?', 'investisseur', 'bordeaux-centre', 'linkedin', 'solution', 'autorite', 'post-court', 7, 2026, 'rdv', 3, 1, 'idea'),
('Vendre en ete a Bordeaux : bonne ou mauvaise idee ?', 'vendeur', 'bordeaux-centre', 'blog', 'unaware', 'seo-local', 'article-satellite', 8, 2026, 'estimation', 3, 1, 'idea'),
('Marche immobilier Bordeaux Metropole : les tendances du trimestre', 'vendeur', 'bordeaux-centre', 'gmb', 'unaware', 'seo-local', 'fiche-gmb', 8, 2026, 'estimation', 2, 1, 'idea'),
('Les 10 plus belles rues de Bordeaux pour vivre au quotidien', 'acheteur', 'bordeaux-centre', 'instagram', 'unaware', 'notoriete', 'reel', 8, 2026, NULL, 4, 1, 'idea'),
('Le Bouscat : vivre au vert a 5 minutes du centre de Bordeaux', 'acheteur', 'le-bouscat', 'facebook', 'unaware', 'notoriete', 'post-court', 8, 2026, NULL, 4, 1, 'idea'),
('Blanquefort : des maisons avec jardin a prix accessible', 'primo', 'blanquefort', 'tiktok', 'problem', 'trafic', 'video-script', 8, 2026, NULL, 5, 1, 'idea'),
('Votre bilan trimestriel : le marche immobilier a Bordeaux', 'vendeur', 'bordeaux-centre', 'email', 'unaware', 'nurturing', 'email', 8, 2026, 'estimation', 4, 1, 'idea');