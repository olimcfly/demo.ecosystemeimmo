-- ============================================================
-- MODULE : Vues Estimation (virtuelles) (estimation-views)
-- Fichier : estimation-views.sql
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
-- Table : v_estimation_bant_score
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_estimation_bant_score`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE IF NOT EXISTS `v_estimation_bant_score` (
`id` int(11)
,`name` varchar(255)
,`email` varchar(255)
,`address` varchar(255)
,`bant_score` int(5)
,`temperature` varchar(9)
);


-- ------------------------------------------------------------
-- Table : v_estimation_summary
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_estimation_summary`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE IF NOT EXISTS `v_estimation_summary` (
`id` int(11)
,`request_id` varchar(50)
,`name` varchar(255)
,`email` varchar(255)
,`address` varchar(255)
,`property_type` enum('appartement','maison','studio','loft','villa','duplex')
,`surface` decimal(8,2)
,`rooms` int(11)
,`bant_need` enum('oui','peut-etre','non','heritage')
,`seller_type` enum('proprietaire','investisseur','succession','autre')
,`status` enum('nouveau','en-cours','rdv-planifie','estimation-envoyee','avis-demande','termine','abandonne')
,`priority` enum('basse','normal','haute','urgente')
,`created_at` timestamp
,`temperature` varchar(6)
,`contact_count` bigint(21)
,`next_rdv` datetime
);


-- ------------------------------------------------------------
-- Table : v_estimation_to_contact
-- ------------------------------------------------------------

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_estimation_to_contact`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE IF NOT EXISTS `v_estimation_to_contact` (
`id` int(11)
,`request_id` varchar(50)
,`address` varchar(255)
,`property_type` enum('appartement','maison','studio','loft','villa','duplex')
,`surface` decimal(8,2)
,`rooms` int(11)
,`floor` varchar(50)
,`condition` enum('neuf','bon','moyen','renovation')
,`amenities` longtext
,`special_features` text
,`name` varchar(255)
,`email` varchar(255)
,`phone` varchar(20)
,`bant_budget` enum('150-300k','300-500k','500k-1m','1m+')
,`bant_authority` enum('moi','couple','famille','other')
,`bant_need` enum('oui','peut-etre','non','heritage')
,`bant_timeline` enum('immédiat','futur','curiosité')
,`seller_type` enum('proprietaire','investisseur','succession','autre')
,`estimated_price_low` decimal(12,2)
,`estimated_price_mean` decimal(12,2)
,`estimated_price_high` decimal(12,2)
,`estimation_justification` text
,`estimation_date` datetime
,`status` enum('nouveau','en-cours','rdv-planifie','estimation-envoyee','avis-demande','termine','abandonne')
,`priority` enum('basse','normal','haute','urgente')
,`assigned_agent` int(11)
,`notes` text
,`created_at` timestamp
,`updated_at` timestamp
,`contacted_at` datetime
);


