-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 23 avr. 2026 à 21:29
-- Version du serveur : 8.0.31
-- Version de PHP : 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `e_parapheur`
--

-- --------------------------------------------------------

--
-- Structure de la table `act_request_submissions`
--

DROP TABLE IF EXISTS `act_request_submissions`;
CREATE TABLE IF NOT EXISTS `act_request_submissions` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `requested_act_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `emitter_administration_id` varchar(120) COLLATE utf8mb3_unicode_ci NOT NULL,
  `direction_code` varchar(120) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `requested_document_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `applicant_full_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `applicant_email` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applicant_phone` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applicant_field_values` json DEFAULT NULL,
  `required_documents_snapshot` json DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `note` text COLLATE utf8mb3_unicode_ci,
  `status` enum('pending','in_progress','treated','rejected') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'pending',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `act_request_submissions_requested_act_id_index` (`requested_act_id`),
  KEY `act_request_submissions_emitter_administration_id_index` (`emitter_administration_id`),
  KEY `act_request_submissions_direction_code_index` (`direction_code`),
  KEY `act_request_submissions_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `administration_profiles`
--

DROP TABLE IF EXISTS `administration_profiles`;
CREATE TABLE IF NOT EXISTS `administration_profiles` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `administration_profiles_administration_id_foreign` (`administration_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `administration_profiles`
--

INSERT INTO `administration_profiles` (`id`, `administration_id`, `name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
('019d9dcd-433b-72dc-ad26-7a72179e126c', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTEUR', 'RESPONSABLE D\'UNE DIRECTION CENTRALE', '{\"description\": \"RESPONSABLE D\'UNE DIRECTION CENTRALE\", \"menuPermissions\": [\"dashboard\", \"courrier.imputation\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"signatures\", \"signatures.view\", \"signatures.request\", \"signatures.sign\", \"signatures.reject\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-17 23:36:08', '2026-04-17 23:36:08'),
('019d9de0-61ea-717b-92ef-4901edfb9dff', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'AGENT COURRIER', 'agent chargé du courrier', '{\"description\": \"agent chargé du courrier\", \"menuPermissions\": [\"courrier.enregistrement\", \"courrier.liste\", \"courrier.traite\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-17 23:57:01', '2026-04-17 23:57:01'),
('019d9de1-cb5a-71fe-b61e-a8d8e831882b', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SUPER ADMIN', 'SUPER ADMIN', '{\"description\": \"SUPER ADMIN\", \"menuPermissions\": [\"dashboard\", \"courrier\", \"courrier.enregistrement\", \"courrier.liste\", \"courrier.imputation\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"templates-shared\", \"templates-shared.view\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"workflows\", \"workflows.view\", \"workflows.create\", \"workflows.validate\", \"workflows.delete\", \"signatures\", \"signatures.view\", \"signatures.request\", \"signatures.sign\", \"signatures.reject\", \"reception\", \"reception.view\", \"reception.process\", \"act-requests\", \"act-requests.view\", \"act-requests.process\", \"qrcode\", \"qrcode.scan\", \"administration\", \"administration.templates\", \"administration.emitters\", \"administration.recipients\", \"administration.requested-acts\", \"administration.routing\", \"administration.onlyoffice\", \"administration.users\", \"administration.theming\", \"administration.email-notifications\", \"administration.signature-provider\", \"administration.user-profiles\"]}', '2026-04-17 23:58:33', '2026-04-17 23:58:33'),
('019d9de3-872b-73da-b5dc-5717e0062a71', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'ASSISTANT', 'ASSISTANT', '{\"description\": \"ASSISTANT\", \"menuPermissions\": [\"dashboard\", \"courrier.liste\", \"courrier.traite\", \"templates-shared\", \"templates-shared.view\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"workflows\", \"workflows.view\", \"workflows.create\", \"workflows.validate\", \"workflows.delete\", \"act-requests\", \"act-requests.view\", \"act-requests.process\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-18 00:00:27', '2026-04-18 00:00:27'),
('019d9de5-4916-70a0-81b3-cce401a182c9', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTEUR GENERALE', 'DIRECTEUR GENERALE', '{\"description\": \"DIRECTEUR GENERALE\", \"menuPermissions\": [\"dashboard\", \"courrier.imputation\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"signatures\", \"signatures.view\", \"signatures.request\", \"signatures.sign\", \"signatures.reject\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-18 00:02:22', '2026-04-18 00:02:22'),
('019d9de6-afff-71fe-b964-a186bb1de78a', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTEUR DE CABINET', 'DIRECTEUR DE CABINET', '{\"description\": \"DIRECTEUR DE CABINET\", \"menuPermissions\": [\"dashboard\", \"courrier.imputation\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"signatures\", \"signatures.view\", \"signatures.request\", \"signatures.sign\", \"signatures.reject\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-18 00:03:54', '2026-04-18 00:03:54'),
('019d9dea-b7d6-711f-9981-e47018725652', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'ADMIN SOUS TUTELLE', 'ADMIN SOUS TUTELLE', '{\"description\": \"ADMIN SOUS TUTELLE\", \"menuPermissions\": [\"dashboard\", \"courrier\", \"courrier.enregistrement\", \"courrier.liste\", \"courrier.imputation\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"templates-shared\", \"templates-shared.view\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"workflows\", \"workflows.view\", \"workflows.create\", \"workflows.validate\", \"workflows.delete\", \"signatures\", \"signatures.view\", \"signatures.request\", \"signatures.sign\", \"signatures.reject\", \"reception\", \"reception.view\", \"reception.process\", \"act-requests\", \"act-requests.view\", \"act-requests.process\", \"qrcode\", \"qrcode.scan\", \"administration.templates\", \"administration.requested-acts\", \"administration.routing\", \"administration.users\", \"administration.theming\"]}', '2026-04-18 00:08:18', '2026-04-18 00:08:18'),
('019d9dec-0300-7300-9117-77dd96226c03', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SOUS DIRECTEUR', 'SOUS DIRECTEUR', '{\"description\": \"SOUS DIRECTEUR\", \"menuPermissions\": [\"dashboard\", \"courrier.en-traitement\", \"courrier.suivi-imputation\", \"courrier.traite\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"workflows\", \"workflows.view\", \"workflows.create\", \"workflows.validate\", \"workflows.delete\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-18 00:09:43', '2026-04-18 00:09:43'),
('019d9dee-0e2b-70a7-9857-a9d0c916d95c', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CHEF DE SERVICE', 'CHEF DE SERVICE', '{\"description\": \"CHEF DE SERVICE\", \"menuPermissions\": [\"dashboard\", \"courrier.en-traitement\", \"courrier.traite\", \"documents\", \"documents.view\", \"documents.upload\", \"documents.create-folder\", \"documents.share\", \"documents.edit-onlyoffice\", \"documents.delete\", \"workflows\", \"workflows.view\", \"workflows.create\", \"workflows.validate\", \"workflows.delete\", \"qrcode\", \"qrcode.scan\"]}', '2026-04-18 00:11:57', '2026-04-18 00:11:57');

-- --------------------------------------------------------

--
-- Structure de la table `administration_users`
--

DROP TABLE IF EXISTS `administration_users`;
CREATE TABLE IF NOT EXISTS `administration_users` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `profile_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `full_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `username` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `admin_role` enum('super_admin','admin','manager','user','signer') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'user',
  `status` enum('active','inactive') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `administration_users_email_unique` (`email`),
  UNIQUE KEY `administration_users_username_unique` (`username`),
  KEY `administration_users_administration_id_foreign` (`administration_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
CREATE TABLE IF NOT EXISTS `app_settings` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb3_unicode_ci,
  `description` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_settings_key_unique` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `app_settings`
--

INSERT INTO `app_settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
('019d99ac-5ff1-71b4-be4f-5475452caa49', 'app_name', 'E-Parapheur', NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d99ac-6017-7391-8c43-68a14e5eb35f', 'app_slogan', 'Connect & Sign', NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d99ac-601e-7013-ac52-52040e0d30bc', 'menu_color', '#eb8f0f', NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d99ac-6022-7209-a24b-cb40c018a0e4', 'app_url', NULL, NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d99ac-6026-7303-99a4-a225f5d13733', 'legal_notice_url', NULL, NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d99ac-6029-72bf-b629-51a071eee774', 'privacy_policy_url', NULL, NULL, '2026-04-17 04:21:44', '2026-04-17 04:21:44'),
('019d9a5e-3fd3-7285-8b32-3cf00c5f2abe', 'onlyoffice_doc_viewer', 'native', NULL, '2026-04-17 07:36:01', '2026-04-18 15:42:03'),
('019d9a5e-3fe7-720c-a82d-56bb361cda31', 'onlyoffice_server_url', 'https://onlyoffice.ci', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-3fea-7375-9188-b27a5dd8eff8', 'onlyoffice_disable_cert', '0', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-3fed-709c-8d8f-87d19f74d49e', 'onlyoffice_secret', '0e8Zg55FyiCDwej4WdC2bM4Wxb2PcR1W', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-3ff2-7208-b979-694824c28cae', 'qr_image_page', '-1', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-3ff9-705c-8613-0d12115df195', 'qr_image_x', '390', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-4000-7381-9f6a-3013e8556cc6', 'qr_image_y', '710', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-4005-718d-a731-4a740b04e1ba', 'qr_image_width', '150', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9a5e-4008-7347-9b9a-9e3dfd81541e', 'qr_image_height', '80', NULL, '2026-04-17 07:36:01', '2026-04-17 07:36:01'),
('019d9e30-2ee3-7080-9592-34b812084546', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_app_name', 'MEMFPMA', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 02:14:54'),
('019d9e30-2efc-714b-8d73-a90dac2e06e8', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_web_url', NULL, 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e30-2f0b-709c-aa16-a3a93f4e653a', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_slogan', NULL, 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e30-2f17-70bd-87d9-55f176f12ef3', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_menu_color', '#132ad8', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-21 20:55:48'),
('019d9e30-2f21-726d-bf6a-c5966de91cdc', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_bg_color', '#0b19da', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-21 20:55:48'),
('019d9e30-2f32-7053-88d0-8986042665be', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_legal_notice_url', NULL, 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e30-2f42-730d-8c87-27d4c364ccb6', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_privacy_policy_url', NULL, 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e30-2f50-7388-9bd6-65c6f7ae0acf', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_disable_user_theming', 'false', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e30-2f5c-7222-941f-866694e2ab7a', 'theme_menu_color', '#132ad8', 'Couleur globale du menu', '2026-04-18 01:24:11', '2026-04-21 20:55:48'),
('019d9e30-2f8c-7395-9123-ae8027c6a52f', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_logo', 'theming/emitter/019d9daf-61ed-70d4-8bfd-d65f58ec21c2/2SN2voWmaJk5B2LfF3vTphLd8F5XFzvrMYuzmakw.png', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:24:11', '2026-04-18 01:24:11'),
('019d9e4b-4baf-7120-a06a-7130a20d4355', 'theme_emitter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_login_background_image', 'theming/emitter/019d9daf-61ed-70d4-8bfd-d65f58ec21c2/TQHPVbIB7Q03PlfGLlnXR0szDuwzUlDl0QkqbN6D.jpg', 'Theming emitter 019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '2026-04-18 01:53:47', '2026-04-18 13:51:35'),
('019d9e4b-4bd5-7398-8050-ecac8db2fbf5', 'theme_login_background_image', 'theming/emitter/019d9daf-61ed-70d4-8bfd-d65f58ec21c2/TQHPVbIB7Q03PlfGLlnXR0szDuwzUlDl0QkqbN6D.jpg', 'Image globale de fond de connexion', '2026-04-18 01:53:47', '2026-04-18 13:51:35'),
('019da315-3f2c-700b-bd21-29c9289dc467', 'app_public_url', 'https://matcher-patronize-deuce.ngrok-free.dev/e-administration_laravel/public', NULL, '2026-04-19 00:12:51', '2026-04-23 01:15:57'),
('019da3ad-f3d2-70f5-858e-41e940c92186', 'template_share_map', '{\"019da357-8202-725f-8313-e00fcb2897c5\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da3af-9ebf-724e-b928-55ac95bca0b9\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da429-2b8d-73c0-9239-71857f53a6c6\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da436-b040-710a-bfec-97a46a1050d8\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da448-d7cf-719a-ab9c-72aa9f4b157a\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da468-20db-7012-b16d-de0d2a38fadb\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da4f1-dae2-7396-be1b-1017b0316257\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da7da-ec90-7192-a9a0-1011eab7bd32\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da7df-2f27-72f7-ac3b-ccf33ef5e2fe\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019da7f7-2543-7097-84f8-3995498fdac1\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019daf30-e1c0-73f4-b4a5-4fa0f643af03\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019daf52-7148-7279-a55b-f551ba6a486f\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019daf79-a874-735d-8b8d-83092904f6d6\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019dafc6-3fc5-7067-a107-68a7ee623a05\":[\"019d9e20-bfd8-7149-8260-e3b3be5d8c06\"],\"019db5d1-2f4f-732b-9211-755e57de9f58\":[\"019d98ca-f8f0-7255-a7ec-eb5647937711\"],\"019db732-5c7d-728e-8603-6186d6eff68d\":[\"019d98ca-f8f0-7255-a7ec-eb5647937711\"],\"019dbb4c-6249-70e1-b40a-c97e71591fa0\":[\"019d98ca-f8f0-7255-a7ec-eb5647937711\"],\"019dbb58-eea8-71b9-8957-e417cb544689\":[\"019d98ca-f8f0-7255-a7ec-eb5647937711\"]}', NULL, '2026-04-19 02:59:39', '2026-04-23 17:18:08'),
('019da42a-1f94-71b0-843b-f17d1dd810fc', 'doc_counter_019d9daf-61ed-70d4-8bfd-d65f58ec21c2_cab_min_2026', '3', 'Compteur documents MEMFPMA / CAB MIN 2026', '2026-04-19 05:15:17', '2026-04-19 22:28:52'),
('019db5df-bb32-71a9-90d8-67520f134a81', 'signature_qr_position', '{\"imagePage\":-1,\"imageX\":390,\"imageY\":710,\"imageWidth\":150,\"imageHeight\":80}', 'Position QR sur document PDF', '2026-04-22 15:47:11', '2026-04-22 15:47:11');

-- --------------------------------------------------------

--
-- Structure de la table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `entity_type` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `entity_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `changes` json DEFAULT NULL,
  `ip_address` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `sender_id` varchar(128) COLLATE utf8mb3_unicode_ci NOT NULL,
  `recipient_id` varchar(128) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sender_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `sender_initials` varchar(8) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `room` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'group',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chat_messages_room_index` (`room`),
  KEY `chat_messages_created_at_index` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `courriers`
--

DROP TABLE IF EXISTS `courriers`;
CREATE TABLE IF NOT EXISTS `courriers` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `numero` varchar(120) COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` enum('arrive','depart') COLLATE utf8mb3_unicode_ci NOT NULL,
  `objet` varchar(500) COLLATE utf8mb3_unicode_ci NOT NULL,
  `expediteur` text COLLATE utf8mb3_unicode_ci,
  `destinataire` text COLLATE utf8mb3_unicode_ci,
  `numero_emission` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `urgence` enum('normale','urgent','tres_urgent') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'normale',
  `date_emission` date NOT NULL,
  `observations` text COLLATE utf8mb3_unicode_ci,
  `statut` enum('en_attente','en_traitement','traite') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'en_attente',
  `enregistre_par` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sub_entity_code` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `impute_a` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `impute_par` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `impute_le` timestamp NULL DEFAULT NULL,
  `instruction_nom` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `instruction_desc` text COLLATE utf8mb3_unicode_ci,
  `delai_traitement` date DEFAULT NULL,
  `pieces_jointes` json DEFAULT NULL,
  `accuse_reception` varchar(1000) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fichier_reponse` varchar(1000) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `reponse_nom` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `reponse_statut` enum('en_attente_validation','validee','rejetee') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `traite_par` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `traite_le` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `courriers_numero_unique` (`numero`),
  KEY `courriers_statut_index` (`statut`),
  KEY `courriers_enregistre_par_index` (`enregistre_par`),
  KEY `courriers_administration_id_index` (`administration_id`),
  KEY `courriers_sub_entity_code_index` (`sub_entity_code`),
  KEY `courriers_traite_par_foreign` (`traite_par`),
  KEY `courriers_statut_impute_a_index` (`statut`,`impute_a`),
  KEY `courriers_impute_par_statut_index` (`impute_par`,`statut`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `courriers`
--

INSERT INTO `courriers` (`id`, `numero`, `type`, `objet`, `expediteur`, `destinataire`, `numero_emission`, `urgence`, `date_emission`, `observations`, `statut`, `enregistre_par`, `administration_id`, `sub_entity_code`, `impute_a`, `impute_par`, `impute_le`, `instruction_nom`, `instruction_desc`, `delai_traitement`, `pieces_jointes`, `accuse_reception`, `fichier_reponse`, `reponse_nom`, `reponse_statut`, `traite_par`, `traite_le`, `deleted_at`, `created_at`, `updated_at`) VALUES
('019da129-9593-73f0-ba38-ef48f9bedeec', 'A-0001-DIR001-2026', 'arrive', 'invitation massa', 'ministère de la culture et de la francophonie', NULL, 'c-20256', 'normale', '2026-04-18', NULL, 'en_attente', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '[\"courriers/pieces_jointes/cWKy4qbxu9EyOCrrlumCp34XPWb0nRh66ZPGsHOy.pdf\"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-18 15:15:50', '2026-04-18 15:15:50'),
('019da137-3921-73d8-b101-8c7dcc1f3111', 'A-CAB MIN-00001-2026', 'arrive', 'invitation massa', 'Ministère de la culture', NULL, 'c-20256', 'normale', '2026-04-18', NULL, 'en_attente', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CAB MIN', NULL, NULL, NULL, NULL, NULL, NULL, '[\"courriers/pieces_jointes/buhb3WMZVOcmCG1Dp5luR23jEWJ7qs6wOsgI7nQb.txt\"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-18 15:30:44', '2026-04-18 15:30:44'),
('019da14d-39c6-72fd-96ae-1ba88f131cad', 'A-CAB MIN-00002-2026', 'arrive', 'demande de rendez vous', 'ong toc toc', NULL, 't-1234', 'normale', '2026-04-18', NULL, 'en_attente', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CAB MIN', NULL, NULL, NULL, NULL, NULL, NULL, '[\"courriers/pieces_jointes/8onDt6fXeMtI77HcEplKyt6Ouq5O1tH0vyiqvBkB.pdf\"]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-18 15:54:46', '2026-04-18 15:54:46');

-- --------------------------------------------------------

--
-- Structure de la table `direction_types`
--

DROP TABLE IF EXISTS `direction_types`;
CREATE TABLE IF NOT EXISTS `direction_types` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `direction_types_name_unique` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `direction_types`
--

INSERT INTO `direction_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
('019d9da4-6c5f-7202-8e79-998570b2c133', 'CABINET MINISTRE', 'CABINET MINISTERIEL', '2026-04-17 22:51:31', '2026-04-17 22:51:31'),
('019d9da5-201c-72bf-bf71-a08cbebc238c', 'CABINET DIRCAB', 'CABINET DIRECTEUR DE CABINET', '2026-04-17 22:52:17', '2026-04-17 22:52:17'),
('019d9da5-c420-7058-9fdb-26e5d68d5065', 'CABINET DIRCAB ADJOINT', 'CABINET DIRECTEUR DE CABINET ADJOINT', '2026-04-17 22:52:59', '2026-04-17 22:52:59'),
('019d9da6-78aa-727b-aec7-7b5c4520aa97', 'DIRECTION GENERALE', 'DIRECTION GENERALE', '2026-04-17 22:53:46', '2026-04-17 22:53:46'),
('019d9da6-f4ef-70a2-b463-34e3b030b6fd', 'DIRECTION CENTRALE', 'DIRECTION CENTRALE', '2026-04-17 22:54:17', '2026-04-17 22:54:17'),
('019d9da7-9f51-7370-b371-4d88d4f0939e', 'DIRECTION REGIONALE', 'DIRECTION REGIONALE', '2026-04-17 22:55:01', '2026-04-17 22:55:01'),
('019d9da8-0d6f-7271-9f86-475e4a617d38', 'DIRECTION DEPARTEMENTALE', 'DIRECTION DEPARTEMENTALE', '2026-04-17 22:55:29', '2026-04-17 22:55:29'),
('019d9da8-cbc7-7258-9bde-6d6af9e06c89', 'SOUS DIRECTION', 'SOUS DIRECTION', '2026-04-17 22:56:18', '2026-04-17 22:56:18'),
('019d9da9-270d-728e-8643-2cba325e1bd7', 'SERVICE', 'SERVICE', '2026-04-17 22:56:41', '2026-04-17 22:56:41');

-- --------------------------------------------------------

--
-- Structure de la table `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(500) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `file_path` varchar(1000) COLLATE utf8mb3_unicode_ci NOT NULL,
  `file_size` bigint NOT NULL DEFAULT '0',
  `mime_type` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT 'application/pdf',
  `status` enum('draft','active','signed','archived','pending_signature') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'draft',
  `owner_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_by` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `issuing_administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `recipient_administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `document_number` varchar(120) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `qr_token` varchar(64) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sub_entity_code` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `documents_qr_token_unique` (`qr_token`),
  KEY `documents_status_index` (`status`),
  KEY `documents_owner_id_index` (`owner_id`),
  KEY `documents_recipient_administration_id_index` (`recipient_administration_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `documents`
--

INSERT INTO `documents` (`id`, `title`, `description`, `file_path`, `file_size`, `mime_type`, `status`, `owner_id`, `created_by`, `issuing_administration_id`, `recipient_administration_id`, `document_number`, `qr_token`, `sub_entity_code`, `signed_at`, `deleted_at`, `created_at`, `updated_at`) VALUES
('019d98cb-3d7f-72d5-a995-0873e5231b18', 'Rapport annuel 2025.pdf', NULL, '/storage/documents/test.pdf', 102400, 'application/pdf', 'active', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-17 00:15:49', '2026-04-17 00:15:49'),
('019d98cb-3d99-7128-ac15-212d7ee37dca', 'Budget previsionnel.xlsx', NULL, '/storage/documents/test2.xlsx', 51200, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'draft', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-17 00:15:49', '2026-04-17 00:15:49'),
('019d98cb-3d9d-7017-9cf5-1c0ebfa73763', 'Projets 2025', '[folder]', '', 0, 'application/x-folder', 'draft', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-17 00:15:49', '2026-04-17 00:15:49'),
('019d9964-f639-73c2-8cc0-9ce3a35871f3', '_2026-04-14', NULL, '/storage/documents/SnlLxnowkxgjEneVkA6go3RyQmODGY8amJwLV2lw.pdf', 140589, 'application/pdf', 'draft', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-21 17:40:38', '2026-04-17 03:03:43', '2026-04-21 17:40:38'),
('019d9973-52e3-7162-bc1e-0b80d46d0808', '_2026-04-14', NULL, '/storage/documents/qFXfNc8sCX1Uagyvmrq1pw1arDzUu6djk8tysdCb.pdf', 140589, 'application/pdf', 'draft', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-21 17:33:51', '2026-04-17 03:19:25', '2026-04-21 17:33:51'),
('019da3b8-c58c-7112-bca5-005cf0ff116a', 'test ccm — 19/04/2026 03:11', 'Généré depuis : test ccm', '/storage/documents/test-20260419-031128.txt', 3126, 'text/plain', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:10:33', '2026-04-19 03:11:28', '2026-04-23 09:10:33'),
('019da3d2-f4bd-72b2-b4b8-4433d96c52bd', 'test ccm — 19/04/2026 03:40', 'Généré depuis : test ccm', '/storage/documents/test-20260419-033950.pdf', 880917, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19 03:40:04', '2026-04-19 03:40:04'),
('019da3d6-a77e-7054-93d9-6a596c8deb8c', 'ccm de deux mfb (1).docx', NULL, '/storage/documents/tw2Rp7BP1Al7TZUptZ40DgdHL19dzAbBYGdPUtCC.docx', 35498, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-19 03:44:07', '2026-04-19 03:44:07'),
('019da3f6-9886-7372-8416-c5a463112106', 'test ccm — 19/04/2026 04:19', 'Généré depuis : test ccm', '/storage/documents/test-20260419-041848.pdf', 25124, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, 'ftPXSL0aSbl9dqdSYdkCv8iE7e5c3xAgPo49hmVX', NULL, NULL, NULL, '2026-04-19 04:19:00', '2026-04-19 04:19:00'),
('019da404-ad1b-73e4-a850-43fe94a42628', 'test ccm — 19/04/2026 04:34', 'Généré depuis : test ccm', '/storage/documents/test-20260419-043420.pdf', 24561, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, '57ZjhiChMgmsXpR1v1zXDfcWOIV9rCIi8yuaQlhV', NULL, NULL, NULL, '2026-04-19 04:34:23', '2026-04-19 04:34:23'),
('019da408-b3fc-716a-9830-de0e53265e55', 'test ccm — 19/04/2026 04:38', 'Généré depuis : test ccm', '/storage/documents/test-20260419-043843.pdf', 24520, 'application/pdf', 'active', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, 'v34OOGL2YehPTxmBz7rzxgDXpjgHZaRCn6rP8KLM', NULL, NULL, '2026-04-21 17:07:49', '2026-04-19 04:38:47', '2026-04-21 17:07:49'),
('019da413-71a5-71e0-89b4-bb743ea10525', 'test ccm — 19/04/2026 04:50', 'Généré depuis : test ccm', '/storage/documents/test-20260419-045027.pdf', 24550, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, 'Y6HZQSuDeTSxT9EDuKGHU6nBfumVcVWQ0LjESnhA', NULL, NULL, NULL, '2026-04-19 04:50:30', '2026-04-19 04:50:30'),
('019da420-ff30-732c-a858-ab2e87661fb5', 'test ccm — 19/04/2026 05:05', 'Généré depuis : test ccm', '/storage/documents/test-20260419-050516.pdf', 24583, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, 'TMzLkkku0hBJdQfWcZ1yzEsX0YWgzSPXx3XD2bnw', NULL, NULL, NULL, '2026-04-19 05:05:19', '2026-04-19 05:05:19'),
('019da42a-2b34-72ea-9fa5-53cf3f3d2302', '[MEMFPMA - CAB MIN - 00001 - 2026] test dfrc — 19/04/2026 05:15', 'Généré depuis : test dfrc', '/storage/documents/ccm-20260419-051518.pdf', 20936, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', NULL, 'MEMFPMA - CAB MIN - 00001 - 2026', 'FN2Eoom0i0IYkH15k0zkb2nsZXS79g0LVWsBdWVj', 'CAB MIN', NULL, '2026-04-23 09:09:05', '2026-04-19 05:15:20', '2026-04-23 09:09:05'),
('019da440-43ac-73eb-b03e-dde45870e7c9', '[MEMFPMA - CAB MIN - 00002 - 2026] kam — 19/04/2026 05:39', 'Généré depuis : kam', '/storage/documents/kam-20260419-053928.docx', 37780, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', NULL, 'MEMFPMA - CAB MIN - 00002 - 2026', '6enYcV8aFdD3c4VgGtGqr9F0GCiR3gb2xlGpbfhf', 'CAB MIN', NULL, '2026-04-23 09:09:11', '2026-04-19 05:39:28', '2026-04-23 09:09:11'),
('019da7dc-798f-715d-a7ef-ca98314f79b7', '[MEMFPMA - CAB MIN - 00003 - 2026] CCM DFRC — 19/04/2026 22:28', 'Généré depuis : CCM DFRC', '/storage/documents/CCM DFRC-20260419-222853.pdf', 20438, 'application/pdf', 'active', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', NULL, 'MEMFPMA - CAB MIN - 00003 - 2026', '5S9ZlO6UFGl9OzGIzjwr6nQeVSqCZrS8EFTQYRwg', 'CAB MIN', NULL, '2026-04-19 22:29:58', '2026-04-19 22:28:57', '2026-04-19 22:29:58'),
('019db129-1f22-717e-926b-51cb02e8a028', 'Demande de fichier 21/04/2026', NULL, '/storage/documents/5ff7d19b-e6db-43d6-89b2-bb64576dc9a5.docx', 0, 'application/octet-stream', 'draft', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-21 17:49:15', '2026-04-21 17:49:15'),
('019db258-ab48-728c-9c9b-98f071a5aaea', 'kam (1)', NULL, '/storage/documents/UIOMAUq8ZPFBnOWOgzbKp9gmMIXs4UJMT6y7owvw.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:07:34', '2026-04-21 23:20:48', '2026-04-23 09:07:34'),
('019db262-a8d5-73e3-bb63-9992def63340', 'kam (1)', NULL, '/storage/documents/IOBlP2xu7aFlgJwMmWQhJyw0K1kzG9MQgfG4FFR1.docx', 30052, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:07:25', '2026-04-21 23:31:43', '2026-04-23 09:07:25'),
('019db263-349e-7399-9f96-18f2f1b6eb2d', 'kam (1)', NULL, '/storage/documents/bCec2fUXS8DDjzwWa8N52MxcpOW9OOkqBAJGKo5Y.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:07:01', '2026-04-21 23:32:19', '2026-04-23 09:07:01'),
('019db26a-c3f6-7259-87a8-57d1d1472c66', 'kam (1)', NULL, '/storage/documents/RruesoCqwPBfqmh3gRderCnifhrlntRUJglMWsEO.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:06:56', '2026-04-21 23:40:34', '2026-04-23 09:06:56'),
('019db26b-98d4-7360-89fa-cc920ebfd3d9', 'kam (1)', NULL, '/storage/documents/XMPDsx9jYwG8eaf0ZfaIxEBMy54T6y2bMGDVFPtv.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:06:49', '2026-04-21 23:41:29', '2026-04-23 09:06:49'),
('019db27e-bb6a-726e-8e6c-b5810f327a4f', 'kam (1)', NULL, '/storage/documents/wZ3D0RIfUDkNHGrswiwDs9gjwfzcUXsP2K6XybU9.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:06:42', '2026-04-22 00:02:23', '2026-04-23 09:06:42'),
('019db281-bfdd-7322-bcfe-395b2b50081b', 'facture 2025', NULL, '/storage/documents/kvdn2jwUD2P4ClTDhpDP2Y83IR7MyPwsJ4hghY6Y.pdf', 260637, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:09:17', '2026-04-22 00:05:40', '2026-04-23 09:09:17'),
('019db291-d138-7235-b8d5-b1280f382072', 'kam (1)', NULL, '/storage/documents/sUn8zcDQREihO0pAue1HAhxALeZBV9YTUWIEzybd.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:09:24', '2026-04-22 00:23:13', '2026-04-23 09:09:24'),
('019db299-bfe5-739e-8e57-43d2e8a9a344', 'kam (1)', NULL, '/storage/documents/1Vn2s1inDOPxpH4JekyzVepn2t9apsHzGUmQDi4a.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 09:09:30', '2026-04-22 00:31:53', '2026-04-23 09:09:30'),
('019db2b1-6cb9-73c5-a85d-192ecc51ab55', 'kam (1)', NULL, '/storage/documents/NXOAenpwGGOcRubp6dtCEeKKGy1a4AZQUa74O9o4.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 00:57:45', '2026-04-22 00:57:45'),
('019db2b6-d135-7114-99b9-f0f1496c532f', 'P2244255M2600000006_2026-04-08 (1)', NULL, '/storage/documents/rK47Fe4B6Ez4o9EHgiDQpF9hpcqCZcGSHJCZ62i8.pdf', 140224, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:03:38', '2026-04-22 01:03:38'),
('019db2b9-9fbb-732e-8b9d-954e7f066247', 'kam (1)', NULL, '/storage/documents/Ph1yeKi9hmbTyepHvQGDUI9hAgskxmbpmNTLRy43.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:06:42', '2026-04-22 01:06:42'),
('019db2c7-c5a5-70eb-9bb2-0b62f99865ce', 'kam (1)', NULL, '/storage/documents/SAkMy9L0Rvmc0rgwb8xSQnJD6PHUpH0tzBQO1VLb.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:22:09', '2026-04-22 01:22:09'),
('019db2ca-656a-7012-b499-4a38603941b4', 'kam (1)', NULL, '/storage/documents/wxucn7LNVshF7KPxe88ADhV9Z8TwloJ6e2WJTSqw.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:25:01', '2026-04-22 01:25:01'),
('019db2d1-be87-72df-96f6-2e8ab4ca42b7', 'kam (1)', NULL, '/storage/documents/HKise2UWLLOqC15D8Fw47Qdlr9pk5roV5dB4Qh9E.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:33:03', '2026-04-22 01:33:03'),
('019db2d2-b93f-72f7-b50b-88e351864fc8', 'kam (1)', NULL, '/storage/documents/IJxR6ns4YW9rX3YIsN9knswPjqBd11q4Uhyyq2ek.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:34:07', '2026-04-22 01:34:07'),
('019db2de-df37-735d-9fa3-a27ebd4e5a52', 'kam (1)', NULL, '/storage/documents/frZmmwgloF7IvlOvpNCFIwJHIMcbYW6X6mWec7hj.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 01:47:23', '2026-04-22 01:47:23'),
('019db2ef-07c5-7006-96f1-03d8af7286d6', 'kam (1)', NULL, '/storage/documents/n31oFQysL1O6FloWK2vAeKxnPRgFDJ8KbJhj92yN.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 02:05:02', '2026-04-22 02:05:02'),
('019db451-9668-73bd-abdb-7b8fac37c0f2', 'kam (1)', NULL, '/storage/documents/mYgI52diHMIf4nUjWszUKRU13ewArZnwQcXmfjHF.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 08:32:19', '2026-04-22 08:32:19'),
('019db45a-0c60-72c5-8d8e-6a056a87668e', 'kam (1)', NULL, '/storage/documents/1IXRW3kR9THJdK8vPGpe0zD3phR6Ppm8F3LFSmkO.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 08:41:33', '2026-04-22 08:41:33'),
('019db465-fd8f-711f-8eca-54613fdfb52b', 'kam (1)', NULL, '/storage/documents/8yjgvge9f9cuRsa64gupa2a6k8ZoR5QmtdRqzuoR.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 08:54:36', '2026-04-22 08:54:36'),
('019db492-f3b7-7161-a05a-10ed497d7284', 'kam (1)', NULL, '/storage/documents/nxmVlObGNv5CJxkgvngtRlWS8WzRTnmiaxSkvFrM.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 09:43:42', '2026-04-22 09:43:42'),
('019db4a4-272b-70d4-aa6d-14b76233fa4b', 'kam (1)', NULL, '/storage/documents/YbP1szCuAn1ugC8bKlz6URTyItcfKXTG47sHILig.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:02:30', '2026-04-22 10:02:30'),
('019db4ad-b59e-7160-8c44-98c6a9f006a5', 'kam (1)', NULL, '/storage/documents/UJNnMniGwpPh1YXVuB1hMfHVxP7h7kBWLwBIiNE7.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:12:56', '2026-04-22 10:12:56'),
('019db4c0-bfd2-7252-98a3-61fbfd4fd506', 'kam (1)', NULL, '/storage/documents/aWKYNDRscfne8M2Q7zH7ZEVqg2fYsxJrhIefeUqN.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:33:44', '2026-04-22 10:33:44'),
('019db4c2-dd0a-72e6-b30c-29e94a9f9ad3', 'kam (1)', NULL, '/storage/documents/XoLLYP7e0A3z05erTyqoUHhuwmIn5il5fnZYlnfL.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:36:02', '2026-04-22 10:36:02'),
('019db4c8-71d1-7127-8137-383853a1702e', 'kam (1)', NULL, '/storage/documents/lelWa6j5AvrM5osWEkKIVFEl3kIndwCEXQnd8iiL.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:42:08', '2026-04-22 10:42:08'),
('019db4cc-2619-7124-ba92-cca37d1dfa1c', 'kam (1)', NULL, '/storage/documents/lYSH1P4MvI2w8ZI2WtUBELdu7AOe7ecSxAdo2LFJ.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 10:46:11', '2026-04-22 10:46:11'),
('019db51a-f65e-72b4-9498-3076ca8231cb', 'kam (1)', NULL, '/storage/documents/9uc4xbgdWmGpwgtDaY3lkygGlxe2Rpr6RzFXz8Wl.pdf', 132112, 'application/pdf', 'signed', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:55:01', NULL, '2026-04-22 12:12:16', '2026-04-22 12:55:01'),
('019db526-bdf9-70b5-ac41-73a78be715c7', 'kam (1)', NULL, '/storage/documents/T3cl338BVfrQ4Z4dLRfgJvriG00TQfkMBJMghVkx.pdf', 132112, 'application/pdf', 'signed', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:55:33', NULL, '2026-04-22 12:25:08', '2026-04-22 12:55:33'),
('019db527-9d08-73ae-82cf-ead1c8b095b4', 'facture DMOA 2026', NULL, '/storage/documents/wGAq0pbmKCQiILsHm7x9zeMbeNe4io72Izh5dVD0.pdf', 398222, 'application/pdf', 'signed', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:55:34', NULL, '2026-04-22 12:26:05', '2026-04-22 12:55:34'),
('019db536-fdf3-7326-8b91-6bb075be03d2', 'facture DMOA 2026', NULL, '/storage/documents/1BvdzFDOFGWZvBZYNLUELfDSkGAQGABeGIgiGaDa.pdf', 398222, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:42:53', '2026-04-22 12:42:53'),
('019db537-2299-715c-bf0d-074c8e449b63', 'kam (1)', NULL, '/storage/documents/syZAsSIKR6WehfP9lhCC20Nr7F5Utexdh91JJYmI.pdf', 132112, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:43:02', '2026-04-22 12:43:02'),
('019db537-854a-7357-a988-80b0e96a4042', 'facture Fiche technique', NULL, '/storage/documents/jrwP4fkBD1QU52DNh2Advv46sMgOehD5NV1vrOTl.pdf', 388713, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-22 12:43:27', '2026-04-22 12:43:27'),
('019db5d4-8bb7-728a-ab24-2c863ccea7ec', 'COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D — 22/04/2026 15:34', 'Généré depuis : COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D', '/storage/documents/COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D-20260422-153458.docx', 25507, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'active', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, 'aUVeqbJrJM8wkeHV7QMsYBYQIZwQgjd2JNj7acdR', NULL, NULL, NULL, '2026-04-22 15:34:58', '2026-04-22 15:34:58'),
('019db7f3-2996-7115-808b-b1a4557ca589', 'COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D — 23/04/2026 01:27', 'Généré depuis : COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D', '/storage/documents/COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D-20260423-012739.docx', 28226, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'active', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, NULL, NULL, 'GjVOcLU794DbpE8vmQW1fYxah3vpmB89lNwt06W9', NULL, NULL, NULL, '2026-04-23 01:27:39', '2026-04-23 01:27:39'),
('019db9ee-c384-715d-bb1b-ce9e8a37dd59', 'DRH ARCHIVAGE', '[folder]', '', 0, 'application/x-folder', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 10:42:05', '2026-04-23 10:42:05'),
('019db9ef-c10f-736f-877e-4d466de4d29b', 'BOrderaux de livraison.pdf', NULL, '/storage/documents/ZlFK8hWuYqpiQNzagi76hnET1WthBD4D4AoK2o64.pdf', 288970, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 10:43:10', '2026-04-23 10:43:10'),
('019dba7e-b994-72cf-8a64-c6f51ceb3af5', 'Test CCM.pdf', NULL, '/storage/documents/XWwJfwdOguphfNg5VOImfcERaJRF7xyjZa9WVQg8.pdf', 195600, 'application/pdf', 'draft', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-04-23 13:19:20', '2026-04-23 13:19:20');

-- --------------------------------------------------------

--
-- Structure de la table `document_shares`
--

DROP TABLE IF EXISTS `document_shares`;
CREATE TABLE IF NOT EXISTS `document_shares` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `shared_by` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `mode` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL,
  `recipient_name` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `recipient_email` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `recipient_administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applicant_full_name` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applicant_matricule` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `applicant_email` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `permission` varchar(20) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'lecture',
  `has_delay` tinyint(1) NOT NULL DEFAULT '0',
  `delay_value` int DEFAULT NULL,
  `delay_unit` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `document_shares_document_id_foreign` (`document_id`),
  KEY `document_shares_shared_by_foreign` (`shared_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `document_templates`
--

DROP TABLE IF EXISTS `document_templates`;
CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `file_type` enum('docx','xlsx','pptx','pdf') COLLATE utf8mb3_unicode_ci NOT NULL,
  `storage_path` varchar(1000) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb3_unicode_ci,
  `signature_zones` json DEFAULT NULL,
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_by` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `document_templates`
--

INSERT INTO `document_templates` (`id`, `name`, `file_name`, `file_type`, `storage_path`, `content`, `signature_zones`, `administration_id`, `created_by`, `created_at`, `updated_at`) VALUES
('019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'kam (1)', 'kam (1).docx', 'docx', 'templates/kam__1__1776791182.docx', '', NULL, NULL, '019d98ca-f8f0-7255-a7ec-eb5647937711', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019dbb28-4f51-724d-8abe-219ab5bc6f52', 'kam (1)', 'kam (1).pdf', 'pdf', 'templates/kam__1__1776961474.pdf', '', NULL, NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 16:24:34', '2026-04-23 16:24:34'),
('019dbb2e-9106-7353-9051-a457f0420bfc', 'kam (1)', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776961884.pdf', '', NULL, NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 16:31:24', '2026-04-23 16:31:24'),
('019dbb4b-097e-714c-8a5e-07d3f79f2466', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776963750.pdf', '', '[{\"h\": 48.3205, \"w\": 22, \"x\": 24.0855, \"y\": 30.3971, \"label\": \"\", \"sealed\": true}, {\"h\": 47.0223, \"w\": 20.5259, \"x\": 54.7178, \"y\": 30.6923, \"label\": \"\", \"sealed\": true}]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:02:30', '2026-04-23 17:03:39'),
('019dbb4c-6249-70e1-b40a-c97e71591fa0', 'CCM DFRC', 'CCM DFRC', 'pdf', NULL, '', NULL, '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:03:58', '2026-04-23 17:03:58'),
('019dbb57-74f4-7210-baa8-957113d893bb', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776964564.pdf', '', '[{\"h\": 55.4269, \"w\": 21.5905, \"x\": 19.4176, \"y\": 36.3191, \"label\": \"\", \"sealed\": true}, {\"h\": 54.9374, \"w\": 19.0519, \"x\": 41.6969, \"y\": 36.3447, \"label\": \"\", \"sealed\": true}, {\"h\": 54.9531, \"w\": 19.2157, \"x\": 60.9462, \"y\": 36.327, \"label\": \"\", \"sealed\": true}]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:16:04', '2026-04-23 17:17:09'),
('019dbb58-eea8-71b9-8957-e417cb544689', 'CCM DFRC 2', 'CCM DFRC 2', 'pdf', NULL, '', NULL, '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:17:40', '2026-04-23 17:17:40'),
('019dbb5c-7368-7091-a3cd-d70de8001c6d', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776964891.pdf', '', NULL, NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:21:31', '2026-04-23 17:21:31'),
('019dbb5d-06d7-71ab-aa1e-2cc5519952f2', 'CCM 2', 'CCM POUR 2.pdf', 'pdf', 'templates/CCM_POUR_2_1776964929.pdf', '', '[{\"h\": 47.0223, \"w\": 22.2457, \"x\": 46.8516, \"y\": 42.7031, \"label\": \"\", \"sealed\": true}]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:22:09', '2026-04-23 17:22:45'),
('019dbb5d-c8d4-7201-a3e8-49825d945546', 'CCM DFRC', 'CCM DFRC', 'pdf', NULL, '', NULL, '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 17:22:58', '2026-04-23 17:22:58'),
('019dbc16-5907-700b-92e9-f6abf5d563fd', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776977074.pdf', '', '[{\"h\": 50.7599, \"w\": 16.4312, \"x\": 23.1847, \"y\": 27.9721, \"label\": \"\", \"sealed\": true}]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 20:44:34', '2026-04-23 20:45:26'),
('019dbc17-6e19-7030-b516-5614f0e5c54d', 'CCM DFRC', 'CCM TEST', 'pdf', NULL, '', NULL, '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 20:45:45', '2026-04-23 20:45:45'),
('019dbc2f-a913-70c7-9abb-bd8dfbec8de9', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776978733.pdf', '', NULL, NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 21:12:13', '2026-04-23 21:12:13'),
('019dbc32-2621-7105-bad0-36cac82faa83', 'CCM 2', 'CCM 2.pdf', 'pdf', 'templates/CCM_2_1776978896.pdf', '', '[{\"h\": 56.8482, \"w\": 18.8062, \"x\": 19.909, \"y\": 23.3549, \"label\": \"\", \"sealed\": true}]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 21:14:56', '2026-04-23 21:15:14'),
('019dbc32-aafe-71eb-baec-876972461b22', 'SUPER ADMIN', 'ccm', 'pdf', NULL, '', NULL, '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-23 21:15:30', '2026-04-23 21:15:30');

-- --------------------------------------------------------

--
-- Structure de la table `document_user_preferences`
--

DROP TABLE IF EXISTS `document_user_preferences`;
CREATE TABLE IF NOT EXISTS `document_user_preferences` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `is_favorite` tinyint(1) NOT NULL DEFAULT '0',
  `label_codes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `document_user_preferences_user_id_document_id_unique` (`user_id`,`document_id`),
  KEY `document_user_preferences_document_id_foreign` (`document_id`),
  KEY `document_user_preferences_user_id_index` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `document_user_preferences`
--

INSERT INTO `document_user_preferences` (`id`, `user_id`, `document_id`, `is_favorite`, `label_codes`, `created_at`, `updated_at`) VALUES
('019d98ed-c4ad-71ff-9a40-e1d42b1912ef', '019d98ca-f8f0-7255-a7ec-eb5647937711', '019d98cb-3d7f-72d5-a995-0873e5231b18', 1, '[]', '2026-04-17 00:53:32', '2026-04-17 00:53:32'),
('019da3d6-562b-72bc-86ee-de5fecc77751', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019da3d2-f4bd-72b2-b4b8-4433d96c52bd', 0, '[\"CCM\"]', '2026-04-19 03:43:46', '2026-04-19 03:43:46'),
('019db9f0-935a-72d4-9acf-fcff1eac3139', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019db9ef-c10f-736f-877e-4d466de4d29b', 0, '[\"ATTESTATION DE PRÉSENCE\", \"2026\"]', '2026-04-23 10:44:04', '2026-04-23 13:17:56'),
('019dba7e-e957-71de-9b09-f584147c92cb', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '019dba7e-b994-72cf-8a64-c6f51ceb3af5', 0, '[\"ATTESTATION DE PRÉSENCE\"]', '2026-04-23 13:19:32', '2026-04-23 13:19:32');

-- --------------------------------------------------------

--
-- Structure de la table `document_versions`
--

DROP TABLE IF EXISTS `document_versions`;
CREATE TABLE IF NOT EXISTS `document_versions` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `version` int NOT NULL DEFAULT '1',
  `file_path` varchar(1000) COLLATE utf8mb3_unicode_ci NOT NULL,
  `creator_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `change_log` text COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `document_versions_document_id_foreign` (`document_id`),
  KEY `document_versions_creator_id_index` (`creator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `document_versions`
--

INSERT INTO `document_versions` (`id`, `document_id`, `version`, `file_path`, `creator_id`, `change_log`, `created_at`) VALUES
('019d9964-f657-723e-80bb-4a51b8162d5b', '019d9964-f639-73c2-8cc0-9ce3a35871f3', 1, '/storage/documents/SnlLxnowkxgjEneVkA6go3RyQmODGY8amJwLV2lw.pdf', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Version initiale', '2026-04-17 03:03:43'),
('019d9973-52f2-7108-8009-14e08dc96da2', '019d9973-52e3-7162-bc1e-0b80d46d0808', 1, '/storage/documents/qFXfNc8sCX1Uagyvmrq1pw1arDzUu6djk8tysdCb.pdf', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Version initiale', '2026-04-17 03:19:25'),
('019da3b8-c5c7-7351-8d21-109a0e8a32d3', '7a7d7ff4-3e59-446e-a24b-6060e333fabe', 1, '/storage/documents/test-20260419-031128.txt', NULL, NULL, '2026-04-19 03:11:28'),
('019da3d2-f532-7170-8448-792141696235', '91462fbd-2039-4ae5-91a2-a5c0801b7deb', 1, '/storage/documents/test-20260419-033950.pdf', NULL, NULL, '2026-04-19 03:40:04'),
('019da3d6-a791-72ab-a8b0-3e5f7ddf5568', '019da3d6-a77e-7054-93d9-6a596c8deb8c', 1, '/storage/documents/tw2Rp7BP1Al7TZUptZ40DgdHL19dzAbBYGdPUtCC.docx', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-19 03:44:07'),
('019da3f6-98dc-70b9-b81f-bdb6a2380a3b', '5f520e99-16ee-4fab-9bd9-680e3732adc4', 1, '/storage/documents/test-20260419-041848.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : test ccm', '2026-04-19 04:19:00'),
('019da404-ad74-7144-869e-348bf20b0e72', '3c9de0c9-30d0-46fa-8599-ba7a5edcc032', 1, '/storage/documents/test-20260419-043420.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : test ccm', '2026-04-19 04:34:23'),
('019da408-b430-7218-970a-9f32b3d91aef', '2817ac70-3fd3-4b8b-8bb1-7f2fd0ffb6c6', 1, '/storage/documents/test-20260419-043843.pdf', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Génération depuis template : test ccm', '2026-04-19 04:38:47'),
('019da413-71d5-7373-817b-ad82a6620f92', 'dbe3bbb4-8543-4793-bb36-5c939a4fe649', 1, '/storage/documents/test-20260419-045027.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : test ccm', '2026-04-19 04:50:30'),
('019da420-ff69-7386-a263-e5c1fb1ebb40', 'ccc2cda7-c8ea-4ad6-8566-433eecefe4cb', 1, '/storage/documents/test-20260419-050516.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : test ccm', '2026-04-19 05:05:19'),
('019da42a-2b5b-736e-8813-72012f79c188', 'd28dba5e-f2e6-4a8a-a475-09d37f557fe4', 1, '/storage/documents/ccm-20260419-051518.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : test dfrc', '2026-04-19 05:15:20'),
('019da440-43f3-739c-a4b9-23134e5f78c6', '6b122b38-fc19-4bcb-83a8-c8cb3c405c65', 1, '/storage/documents/kam-20260419-053928.docx', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : kam', '2026-04-19 05:39:28'),
('019da7dc-79bb-7207-8dab-486e6ec7cff8', 'f18b6ee6-32bd-4b90-9558-c77c150cebd1', 1, '/storage/documents/CCM DFRC-20260419-222853.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Génération depuis template : CCM DFRC', '2026-04-19 22:28:57'),
('019db129-1f40-70bd-90c4-b68000802c77', '019db129-1f22-717e-926b-51cb02e8a028', 1, '/storage/documents/5ff7d19b-e6db-43d6-89b2-bb64576dc9a5.docx', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Création', '2026-04-21 17:49:15'),
('019db258-ab78-72af-9dcb-89df484225ad', '019db258-ab48-728c-9c9b-98f071a5aaea', 1, '/storage/documents/UIOMAUq8ZPFBnOWOgzbKp9gmMIXs4UJMT6y7owvw.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-21 23:20:48'),
('019db262-a90d-71ad-b312-df4a2388f8f3', '019db262-a8d5-73e3-bb63-9992def63340', 1, '/storage/documents/IOBlP2xu7aFlgJwMmWQhJyw0K1kzG9MQgfG4FFR1.docx', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-21 23:31:43'),
('019db263-34c2-731c-aaa5-e5b83571a247', '019db263-349e-7399-9f96-18f2f1b6eb2d', 1, '/storage/documents/bCec2fUXS8DDjzwWa8N52MxcpOW9OOkqBAJGKo5Y.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-21 23:32:19'),
('019db26a-c424-73b7-8de0-6cdd730c3923', '019db26a-c3f6-7259-87a8-57d1d1472c66', 1, '/storage/documents/RruesoCqwPBfqmh3gRderCnifhrlntRUJglMWsEO.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-21 23:40:34'),
('019db26b-98f3-7341-83fe-3ad268f39adc', '019db26b-98d4-7360-89fa-cc920ebfd3d9', 1, '/storage/documents/XMPDsx9jYwG8eaf0ZfaIxEBMy54T6y2bMGDVFPtv.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-21 23:41:29'),
('019db27e-bb99-7254-8f91-538e13917c98', '019db27e-bb6a-726e-8e6c-b5810f327a4f', 1, '/storage/documents/wZ3D0RIfUDkNHGrswiwDs9gjwfzcUXsP2K6XybU9.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 00:02:23'),
('019db281-c025-7185-ad1d-0b8eb50239e0', '019db281-bfdd-7322-bcfe-395b2b50081b', 1, '/storage/documents/kvdn2jwUD2P4ClTDhpDP2Y83IR7MyPwsJ4hghY6Y.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 00:05:41'),
('019db291-d16d-70f5-8049-bac67499fa7f', '019db291-d138-7235-b8d5-b1280f382072', 1, '/storage/documents/sUn8zcDQREihO0pAue1HAhxALeZBV9YTUWIEzybd.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 00:23:14'),
('019db299-c01e-7067-b807-e9cec0a87f0f', '019db299-bfe5-739e-8e57-43d2e8a9a344', 1, '/storage/documents/1Vn2s1inDOPxpH4JekyzVepn2t9apsHzGUmQDi4a.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 00:31:53'),
('019db2b1-6cf7-71a5-bea2-d9afb098e52b', '019db2b1-6cb9-73c5-a85d-192ecc51ab55', 1, '/storage/documents/NXOAenpwGGOcRubp6dtCEeKKGy1a4AZQUa74O9o4.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 00:57:45'),
('019db2b6-d157-7191-a75a-3ce6eab7b579', '019db2b6-d135-7114-99b9-f0f1496c532f', 1, '/storage/documents/rK47Fe4B6Ez4o9EHgiDQpF9hpcqCZcGSHJCZ62i8.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:03:38'),
('019db2b9-9fd9-7158-ad87-201ff3a87f83', '019db2b9-9fbb-732e-8b9d-954e7f066247', 1, '/storage/documents/Ph1yeKi9hmbTyepHvQGDUI9hAgskxmbpmNTLRy43.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:06:42'),
('019db2c7-c64e-7181-890d-787090d4b3df', '019db2c7-c5a5-70eb-9bb2-0b62f99865ce', 1, '/storage/documents/SAkMy9L0Rvmc0rgwb8xSQnJD6PHUpH0tzBQO1VLb.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:22:10'),
('019db2ca-6598-7099-a3e2-96b38cebafc1', '019db2ca-656a-7012-b499-4a38603941b4', 1, '/storage/documents/wxucn7LNVshF7KPxe88ADhV9Z8TwloJ6e2WJTSqw.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:25:01'),
('019db2d1-beaf-7387-bb9f-74b9ef9cbc5d', '019db2d1-be87-72df-96f6-2e8ab4ca42b7', 1, '/storage/documents/HKise2UWLLOqC15D8Fw47Qdlr9pk5roV5dB4Qh9E.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:33:03'),
('019db2d2-b96e-736b-8e02-c4d60a04d014', '019db2d2-b93f-72f7-b50b-88e351864fc8', 1, '/storage/documents/IJxR6ns4YW9rX3YIsN9knswPjqBd11q4Uhyyq2ek.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:34:07'),
('019db2de-df66-7014-b7a6-056bb92cac26', '019db2de-df37-735d-9fa3-a27ebd4e5a52', 1, '/storage/documents/frZmmwgloF7IvlOvpNCFIwJHIMcbYW6X6mWec7hj.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 01:47:23'),
('019db2ef-07f3-7351-ba1c-f741cc5256f6', '019db2ef-07c5-7006-96f1-03d8af7286d6', 1, '/storage/documents/n31oFQysL1O6FloWK2vAeKxnPRgFDJ8KbJhj92yN.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 02:05:02'),
('019db451-9687-7202-8b84-97db5de02bdc', '019db451-9668-73bd-abdb-7b8fac37c0f2', 1, '/storage/documents/mYgI52diHMIf4nUjWszUKRU13ewArZnwQcXmfjHF.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 08:32:19'),
('019db45a-0c8b-7113-a77c-38d80e2a4c2e', '019db45a-0c60-72c5-8d8e-6a056a87668e', 1, '/storage/documents/1IXRW3kR9THJdK8vPGpe0zD3phR6Ppm8F3LFSmkO.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 08:41:33'),
('019db465-fdbf-70c1-89de-d8d4830cd5b4', '019db465-fd8f-711f-8eca-54613fdfb52b', 1, '/storage/documents/8yjgvge9f9cuRsa64gupa2a6k8ZoR5QmtdRqzuoR.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 08:54:36'),
('019db492-f406-709d-9c24-b1d4e8cc1185', '019db492-f3b7-7161-a05a-10ed497d7284', 1, '/storage/documents/nxmVlObGNv5CJxkgvngtRlWS8WzRTnmiaxSkvFrM.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 09:43:42'),
('019db4a4-277a-7362-8739-fb6aa65bcb1a', '019db4a4-272b-70d4-aa6d-14b76233fa4b', 1, '/storage/documents/YbP1szCuAn1ugC8bKlz6URTyItcfKXTG47sHILig.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:02:30'),
('019db4ad-b5df-7282-9a74-f88ba2ab8f3b', '019db4ad-b59e-7160-8c44-98c6a9f006a5', 1, '/storage/documents/UJNnMniGwpPh1YXVuB1hMfHVxP7h7kBWLwBIiNE7.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:12:56'),
('019db4c0-c00f-7286-bee9-7e1eb45a24b0', '019db4c0-bfd2-7252-98a3-61fbfd4fd506', 1, '/storage/documents/aWKYNDRscfne8M2Q7zH7ZEVqg2fYsxJrhIefeUqN.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:33:44'),
('019db4c2-dd2c-718d-9040-ec14f9cc435c', '019db4c2-dd0a-72e6-b30c-29e94a9f9ad3', 1, '/storage/documents/XoLLYP7e0A3z05erTyqoUHhuwmIn5il5fnZYlnfL.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:36:02'),
('019db4c8-71f3-72a4-86c3-202310624ee2', '019db4c8-71d1-7127-8137-383853a1702e', 1, '/storage/documents/lelWa6j5AvrM5osWEkKIVFEl3kIndwCEXQnd8iiL.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:42:08'),
('019db4cc-2652-725f-a496-7374974ec73d', '019db4cc-2619-7124-ba92-cca37d1dfa1c', 1, '/storage/documents/lYSH1P4MvI2w8ZI2WtUBELdu7AOe7ecSxAdo2LFJ.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 10:46:11'),
('019db51a-f68a-7229-8834-5d3ab25eff08', '019db51a-f65e-72b4-9498-3076ca8231cb', 1, '/storage/documents/9uc4xbgdWmGpwgtDaY3lkygGlxe2Rpr6RzFXz8Wl.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:12:16'),
('019db526-be22-7172-a37f-67a50ec28ce0', '019db526-bdf9-70b5-ac41-73a78be715c7', 1, '/storage/documents/T3cl338BVfrQ4Z4dLRfgJvriG00TQfkMBJMghVkx.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:25:08'),
('019db527-9d2c-73c3-88a3-333ed6685937', '019db527-9d08-73ae-82cf-ead1c8b095b4', 1, '/storage/documents/wGAq0pbmKCQiILsHm7x9zeMbeNe4io72Izh5dVD0.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:26:05'),
('019db536-fe1a-73f8-afd5-6995017620bd', '019db536-fdf3-7326-8b91-6bb075be03d2', 1, '/storage/documents/1BvdzFDOFGWZvBZYNLUELfDSkGAQGABeGIgiGaDa.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:42:53'),
('019db537-22b2-70f7-99ea-258073f48118', '019db537-2299-715c-bf0d-074c8e449b63', 1, '/storage/documents/syZAsSIKR6WehfP9lhCC20Nr7F5Utexdh91JJYmI.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:43:02'),
('019db537-8569-72dc-a715-5bb0ffc4f8c7', '019db537-854a-7357-a988-80b0e96a4042', 1, '/storage/documents/jrwP4fkBD1QU52DNh2Advv46sMgOehD5NV1vrOTl.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-22 12:43:27'),
('019db5d4-8be6-7071-b0c4-ad7a421cf42a', '9c72e073-99ee-480b-8f95-501185eac694', 1, '/storage/documents/COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D-20260422-153458.docx', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Génération depuis template : COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D', '2026-04-22 15:34:58'),
('019db7f3-29c1-709c-9195-3c7ce222cc43', 'abfdf202-86c0-4a55-a69f-02b5bbd61683', 1, '/storage/documents/COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D-20260423-012739.docx', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Génération depuis template : COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D', '2026-04-23 01:27:39'),
('019db9ef-c157-7155-9556-f555c99f6400', '019db9ef-c10f-736f-877e-4d466de4d29b', 1, '/storage/documents/ZlFK8hWuYqpiQNzagi76hnET1WthBD4D4AoK2o64.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-23 10:43:10'),
('019dba7e-b9bc-7319-a7ab-e96b5c436d91', '019dba7e-b994-72cf-8a64-c6f51ceb3af5', 1, '/storage/documents/XWwJfwdOguphfNg5VOImfcERaJRF7xyjZa9WVQg8.pdf', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'Version initiale', '2026-04-23 13:19:20');

-- --------------------------------------------------------

--
-- Structure de la table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `instructions`
--

DROP TABLE IF EXISTS `instructions`;
CREATE TABLE IF NOT EXISTS `instructions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `issuing_administrations`
--

DROP TABLE IF EXISTS `issuing_administrations`;
CREATE TABLE IF NOT EXISTS `issuing_administrations` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `code` varchar(100) COLLATE utf8mb3_unicode_ci NOT NULL,
  `sub_entity_code` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `document_number_prefix` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'DOC',
  `document_number_padding` int NOT NULL DEFAULT '6',
  `document_number_sequence` int NOT NULL DEFAULT '0',
  `logo` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issuing_administrations_name_unique` (`name`),
  UNIQUE KEY `issuing_administrations_code_unique` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `issuing_administrations`
--

INSERT INTO `issuing_administrations` (`id`, `name`, `code`, `sub_entity_code`, `is_active`, `document_number_prefix`, `document_number_padding`, `document_number_sequence`, `logo`, `metadata`, `created_at`, `updated_at`) VALUES
('019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'MINISTERE D\'ETAT MINISTERE DE LA FONCTION PUBLIQUE ET DE LA MODERNISATION DE L\'ADMINISTRATION', 'MEMFPMA', '', 1, 'DOC', 6, 0, '/storage/logos/sqgdNZqh7t9YM6PbMkwZijGqDVrwvQAkfafW92T2.png', '{\"tags\": null, \"apiKey\": \"admin123\", \"sector\": \"travail_emploi\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"UTC\", \"adminType\": \"nationale\", \"techEmail\": \"memfpma@parapheur.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmemfpma@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"fonction publique\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"portal\"}', '2026-04-17 23:03:30', '2026-04-23 20:00:04'),
('019dbb72-72a9-7184-a134-8f14697fdcc3', 'MINISTERE DE LA SANTE, DE L\'HYGIENE PUBLIQUE ET DE LA COUVERTURE MALADIE UNIVERSELLE', 'MSHPCMU', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea5abd08b8b.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"sante\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@sante.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmshpcmu@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"sante publique\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 17:45:33', '2026-04-23 17:45:33'),
('019dbbfc-0de1-7286-86d9-7b0a435cb7af', 'MINISTERE DES AFFAIRES ÉTRANGERES, DE L\'INTEGRATION AFRICAINE ET DES IVOIRIENS DE L\'EXTERIEUR', 'MAEIAIE', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea7df71dc5b.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"autre\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@diplomatie.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmae@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"diplomatie\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:15:51', '2026-04-23 20:15:51'),
('019dbc00-bfa3-70fb-b971-912028280e4e', 'MINISTERE DE L\'AGRICULTURE, DU DEVELOPPEMENT RURAL ET DES PRODUCTIONS VIVRIERES', 'MADRPPV', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea7f2ad2bb1.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"autre\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@agriculture.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosagriculture@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"agriculture\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:20:58', '2026-04-23 20:20:58'),
('019dbc05-6524-73d3-b57e-60f5ae830092', 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE', 'MESRS', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea805b5e6f9.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"education_formation\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@universite.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmesrs@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"enseignement\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:26:03', '2026-04-23 20:26:03'),
('019dbc09-e2a3-719d-9bbd-106ee24d2b91', 'MINISTERE DE L\'ÉDUCATION NATIONALE ET DE L\'ENSEIGNEMENT TECHNIQUE', 'MENET', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea81819a791.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"education_formation\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@education.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmenet@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"education\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:30:57', '2026-04-23 20:30:57'),
('019dbc0f-98d7-7010-99e5-986d5f5d71a1', 'MINISTERE DE L’URBANISME, DU LOGEMENT ET DU CADRE DE VIE', 'MULCV', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea82f7ebfca.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"urbanisme_logement\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@urbanisme.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmulcv@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"urbanisme\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:37:12', '2026-04-23 20:37:12'),
('019dbc15-6dca-73db-ba56-b97050535ceb', 'MINISTERE DE L\'ECONOMIE, DES FINANCES ET DU BUDGET', 'MEFB', 'CAB MIN', 1, 'DOC', 6, 0, 'images/logos/logo_69ea847629c5e.jpg', '{\"tags\": null, \"apiKey\": null, \"sector\": \"fiscalite_finance\", \"timeout\": 30, \"docTypes\": [\"pdf\", \"docx\", \"xml\", \"zip\"], \"timezone\": \"Europe/Paris\", \"adminType\": \"nationale\", \"techEmail\": \"infos@finances.ci\", \"authMethod\": \"api_key\", \"dataFormat\": \"json\", \"requireTls\": true, \"webhookUrl\": null, \"description\": null, \"enableAudit\": true, \"enableRetry\": true, \"endpointUrl\": null, \"ipWhitelist\": null, \"slaResponse\": \"24h\", \"trackingUrl\": null, \"contactEmail\": \"infosmefb@parapheur.ci\", \"contactPhone\": \"2721345678\", \"logRetention\": 365, \"businessHours\": null, \"dossierPrefix\": null, \"gdprCompliant\": true, \"postalAddress\": null, \"webhookSecret\": null, \"autoConvertPdf\": true, \"fileEncryption\": false, \"referentMetier\": \"finances et economie\", \"signatureLevel\": \"qualifiee\", \"defaultWorkflow\": null, \"externalRefField\": null, \"requiredMetadata\": null, \"duplicateHandling\": \"update\", \"transmissionMethod\": \"api\"}', '2026-04-23 20:43:34', '2026-04-23 20:43:34');

-- --------------------------------------------------------

--
-- Structure de la table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb3_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_04_16_222825_create_permission_tables', 1),
(5, '2026_04_16_222825_create_personal_access_tokens_table', 1),
(6, '2026_04_16_223057_create_issuing_administrations_table', 1),
(7, '2026_04_16_223058_create_recipient_administrations_table', 1),
(8, '2026_04_16_223059_create_app_settings_table', 1),
(9, '2026_04_16_223059_create_direction_types_table', 1),
(10, '2026_04_16_223100_add_fields_to_users_table', 1),
(11, '2026_04_16_223100_create_user_direction_assignments_table', 1),
(12, '2026_04_16_223121_create_document_versions_table', 1),
(13, '2026_04_16_223121_create_documents_table', 1),
(14, '2026_04_16_223122_create_document_user_preferences_table', 1),
(15, '2026_04_16_223123_create_document_templates_table', 1),
(16, '2026_04_16_223123_create_template_variables_table', 1),
(17, '2026_04_16_223132_create_workflows_table', 1),
(18, '2026_04_16_223133_create_workflow_steps_table', 1),
(19, '2026_04_16_223133_create_workflow_templates_table', 1),
(20, '2026_04_16_223134_create_signatures_table', 1),
(21, '2026_04_16_223134_create_workflow_executions_table', 1),
(22, '2026_04_16_223135_create_qr_codes_table', 1),
(23, '2026_04_16_223135_create_signature_requests_table', 1),
(24, '2026_04_16_223136_create_notifications_table', 1),
(25, '2026_04_16_223137_create_audit_logs_table', 1),
(26, '2026_04_16_223137_create_chat_messages_table', 1),
(27, '2026_04_16_223138_create_administration_profiles_table', 1),
(28, '2026_04_16_223138_create_administration_users_table', 1),
(29, '2026_04_16_223139_create_routing_rules_table', 1),
(30, '2026_04_17_021336_add_signature_zone_to_signature_requests_table', 2),
(31, '2026_04_17_044644_add_recipient_to_chat_messages_table', 3),
(32, '2026_04_17_060145_create_sub_entities_table', 4),
(33, '2026_04_17_062828_create_requested_acts_table', 5),
(34, '2026_04_17_085019_create_signature_provider_configs_table', 6),
(35, '2026_04_17_092709_add_description_to_administration_profiles_add_profile_id_to_users', 7),
(36, '2026_04_17_220314_create_instructions_table', 8),
(37, '2026_04_17_230000_create_courriers_table', 9),
(38, '2026_04_18_152648_add_sub_entity_code_to_courriers_table', 10),
(39, '2026_04_18_210409_add_signature_zones_to_document_templates_table', 11),
(40, '2026_04_19_040628_add_qr_token_to_documents_table', 12),
(41, '2026_04_19_040629_add_sub_entity_code_to_issuing_administrations_table', 12),
(42, '2026_04_21_150000_create_document_shares_table', 13),
(43, '2026_04_21_200000_alter_notifications_type_to_varchar', 14),
(44, '2026_04_21_210000_add_traitement_fields_to_courriers_table', 15),
(45, '2026_04_22_000100_create_act_request_submissions_table', 16),
(46, '2026_04_23_120000_create_act_request_submissions_table', 17);

-- --------------------------------------------------------

--
-- Structure de la table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `recipient_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'info',
  `workflow_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `execution_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `action_url` varchar(512) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notifications_recipient_id_index` (`recipient_id`),
  KEY `notifications_is_read_index` (`is_read`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `recipient_id`, `title`, `message`, `type`, `workflow_id`, `execution_id`, `action_url`, `is_read`, `created_at`) VALUES
('019db528-8195-7015-ad87-f1b291f13323', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Workflow : action requise', 'SUPER ADMIN vous a assigné à l\'étape « Signature 1 » du workflow « test 2 ».', 'workflow_assigned', '019db528-8126-727b-969b-4db0ff71d7bc', NULL, 'http://localhost/e-administration_laravel/public/workflows/019db528-8126-727b-969b-4db0ff71d7bc', 0, '2026-04-22 12:27:03'),
('019db5d2-f8cd-7120-94fd-e2f5480b111b', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Template partagé avec vous', 'SUPER ADMIN vous a donné accès au template « COURRIER DIR CAB ENRÖLEMENT NOUVEAUX SIGNATAIRES D ».', 'template_share', NULL, NULL, 'http://localhost/e-administration_laravel/public/shared-templates', 0, '2026-04-22 15:33:15'),
('019db733-0610-7237-aa27-5b85b3a4e2eb', '019d98ca-f8f0-7255-a7ec-eb5647937711', 'Template partagé avec vous', 'SUPER ADMIN vous a donné accès au template « CCM POUR 2 Demandeurs ».', 'template_share', NULL, NULL, 'http://localhost/e-administration_laravel/public/shared-templates', 0, '2026-04-22 21:57:47');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `guard_name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb3_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb3_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `qr_codes`
--

DROP TABLE IF EXISTS `qr_codes`;
CREATE TABLE IF NOT EXISTS `qr_codes` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `data` text COLLATE utf8mb3_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `verification_code` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `status` enum('active','revoked','expired') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'active',
  `scan_count` int NOT NULL DEFAULT '0',
  `created_by` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_codes_verification_code_unique` (`verification_code`),
  KEY `qr_codes_document_id_foreign` (`document_id`),
  KEY `qr_codes_status_index` (`status`),
  KEY `qr_codes_created_by_index` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recipient_administrations`
--

DROP TABLE IF EXISTS `recipient_administrations`;
CREATE TABLE IF NOT EXISTS `recipient_administrations` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `channel` enum('api','email','ler','application') COLLATE utf8mb3_unicode_ci NOT NULL,
  `api_endpoint` varchar(1000) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email_address` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipient_administrations_name_unique` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `requested_acts`
--

DROP TABLE IF EXISTS `requested_acts`;
CREATE TABLE IF NOT EXISTS `requested_acts` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `administration_id` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `direction_code` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `document_name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `required_documents` json DEFAULT NULL,
  `applicant_fields` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `guard_name` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `routing_rules`
--

DROP TABLE IF EXISTS `routing_rules`;
CREATE TABLE IF NOT EXISTS `routing_rules` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `template_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `recipient_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `condition_field` varchar(150) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `condition_operator` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `condition_value` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb3_unicode_ci,
  `payload` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('yRfT9tPjC0QxM7NdrL9oCC94nA3AdibhNlarmtNM', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoicVBaMWN6SFE2YmkwNGZtT29kVHdJNktWQ09zRnpYYTlua0w0ekx2YSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1776385017),
('bxad0gg9rSZhlVDcLPOGGr6VVXDeERFeXmfZrcEH', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiUmFVZElCYmE0QXN3MEVrWnFLN0FKV1k1Sld2ZlNhY3dvWFkxa3lJTyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoxMjM6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9hZG1pbj9zZWxlY3RlZF90ZW1wbGF0ZT0wMTlkYTM1Ny04MjAyLTcyNWYtODMxMy1lMDBmY2IyODk3YzUmdGFiPXRlbXBsYXRlcyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjEyMzoiaHR0cDovL2xvY2FsaG9zdC9lLWFkbWluaXN0cmF0aW9uX2xhcmF2ZWwvcHVibGljL2FkbWluP3NlbGVjdGVkX3RlbXBsYXRlPTAxOWRhMzU3LTgyMDItNzI1Zi04MzEzLWUwMGZjYjI4OTdjNSZ0YWI9dGVtcGxhdGVzIjtzOjU6InJvdXRlIjtzOjExOiJhZG1pbi5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776563911),
('8PiXfCXp6bzx9TVYuIXTDuYU6hJAw0gkgYAtvQ2C', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQVQxWWdQcjBCbGVZbkhVQ1FxaU1EcU5TRjdxSjR0RXRSaDFTcVZESiI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoxMjM6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9hZG1pbj9zZWxlY3RlZF90ZW1wbGF0ZT0wMTlkYTdkYS0yNmM2LTcwYjMtYjM5Zi01NmUxMWNiYzM2OTImdGFiPXRlbXBsYXRlcyI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjEyMzoiaHR0cDovL2xvY2FsaG9zdC9lLWFkbWluaXN0cmF0aW9uX2xhcmF2ZWwvcHVibGljL2FkbWluP3NlbGVjdGVkX3RlbXBsYXRlPTAxOWRhN2RhLTI2YzYtNzBiMy1iMzlmLTU2ZTExY2JjMzY5MiZ0YWI9dGVtcGxhdGVzIjtzOjU6InJvdXRlIjtzOjExOiJhZG1pbi5pbmRleCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776641667),
('u2Rfj7DlES3gIwjx9EzwnM7AixVhuBdZgQwrSBHY', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiakMydlNqMWl2VTlGbktzcUJsU0dYQzY4Nk9jWlZ4c2dJajVXUGRZWSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czo2NDoiaHR0cDovL2xvY2FsaG9zdC9lLWFkbWluaXN0cmF0aW9uX2xhcmF2ZWwvcHVibGljL3FyLXZlcmlmaWNhdGlvbiI7fXM6OToiX3ByZXZpb3VzIjthOjI6e3M6MzoidXJsIjtzOjY0OiJodHRwOi8vbG9jYWxob3N0L2UtYWRtaW5pc3RyYXRpb25fbGFyYXZlbC9wdWJsaWMvcXItdmVyaWZpY2F0aW9uIjtzOjU6InJvdXRlIjtzOjIxOiJxci12ZXJpZmljYXRpb24uaW5kZXgiO319', 1776804601),
('mlkCjZmYIba7FPMajwts2VshRgEInoLmYwTBe3Pb', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiUEV0UE9WOXhSeWVLZnB6ZFJBbDVxUDk5TnJCMlp5blJ0Y2tGMGxENiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1776822417),
('oUHkPLIt00z8SDCApF7oaeDPUf7HmMyT7qNM7ONK', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQ1dFVTROWjZaYTA5UmhaSmZVdks1SWFoQlRyMm9kczVYcW4yR0NSNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9ub3RpZmljYXRpb25zL2FqYXgtbGlzdCI7czo1OiJyb3V0ZSI7czoyMjoibm90aWZpY2F0aW9ucy5hamF4TGlzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776861689),
('o1svBhGYTdC35fT0VrNaIcOSLTWyn3zSVfdxKFSQ', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWtnVDNhUkpXSXdYZUxGa1ZraVNjUHp4N0ozNThsVnJKMEU2MjZlWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9ub3RpZmljYXRpb25zL2FqYXgtbGlzdCI7czo1OiJyb3V0ZSI7czoyMjoibm90aWZpY2F0aW9ucy5hamF4TGlzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776872628),
('G4h7B1ebVr1MFGc2pPcFDzZzKq9jz1QzKv1S9tJH', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiNVlPZmduWFdVN1RCYVNyMVBZVzc3ZEM1NUVRcm1Dc2hIQ2t4ajg0MiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1776893764),
('enTXXoyJHh00jQNwDXVLIEfiiFGvMfIKR29NkDl7', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiUHp5dkdtQ0NkMTNzYWVIak96Z0dnYTFPSUZpRml3eFpZM3I2QjRFTyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1776941074),
('dOl6kSlG6EliRUJnoiicGzZRmhG67sw17kcTf3AY', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieTVVWUliQTlmbHBaWDJ1RTc4bEZDdWl1SkdDZXVFWWF1bTNwN2RSTSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9ub3RpZmljYXRpb25zL2FqYXgtbGlzdCI7czo1OiJyb3V0ZSI7czoyMjoibm90aWZpY2F0aW9ucy5hamF4TGlzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776957064),
('OxvLQPo7BmtEiCDjLn6kclIXHz4DnoafF4zsaer7', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36 Edg/147.0.0.0', 'YToyOntzOjY6Il90b2tlbiI7czo0MDoiR2NPdWxQQ05weHhZWkxtQ0JsRmRRNWdzaGxNYThhanFna3ROajFiaiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1776964846),
('UT8c3TNzIx01crm9EM2dNAj0GMa52mOe2KYxNuzZ', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWVhCR213dHVuVDdCcUI3VzVRY0pCcEJFbkZubzhZakZKSUZWQndkSCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9ub3RpZmljYXRpb25zL2FqYXgtbGlzdCI7czo1OiJyb3V0ZSI7czoyMjoibm90aWZpY2F0aW9ucy5hamF4TGlzdCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1776965847),
('ZhtjC1GqfYO9O33dqsCXTnMIGNn6YIhgYI2MmnGC', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieUVHTmFMTkxEYTgzQ0c5VXRhM3doeHZMbERyZjdVWlozOHYwWmhVZCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NzI6Imh0dHA6Ly9sb2NhbGhvc3QvZS1hZG1pbmlzdHJhdGlvbl9sYXJhdmVsL3B1YmxpYy9ub3RpZmljYXRpb25zL2FqYXgtbGlzdCI7czo1OiJyb3V0ZSI7czoyMjoibm90aWZpY2F0aW9ucy5hamF4TGlzdCI7fX0=', 1776968391);

-- --------------------------------------------------------

--
-- Structure de la table `signatures`
--

DROP TABLE IF EXISTS `signatures`;
CREATE TABLE IF NOT EXISTS `signatures` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `signer_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `signature` longtext COLLATE utf8mb3_unicode_ci NOT NULL,
  `certificate` text COLLATE utf8mb3_unicode_ci,
  `signed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `location` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_valid` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('valid','revoked','expired') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'valid',
  `signature_algorithm` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `signatures_document_id_foreign` (`document_id`),
  KEY `signatures_signer_id_index` (`signer_id`),
  KEY `signatures_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `signatures`
--

INSERT INTO `signatures` (`id`, `document_id`, `signer_id`, `signature`, `certificate`, `signed_at`, `reason`, `location`, `is_valid`, `status`, `signature_algorithm`, `created_at`) VALUES
('019db542-1a34-7317-aaed-ce58d44bcc10', '019db51a-f65e-72b4-9498-3076ca8231cb', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '00278ddbfa014e0391db43a6613cfa6a2741f6a38cf7ed967555463812dc6463', NULL, '2026-04-22 12:55:01', 'Signature via workflow test demo', NULL, 1, 'valid', NULL, '2026-04-22 12:55:01'),
('019db542-9981-7339-9b7e-981553f55954', '019db526-bdf9-70b5-ac41-73a78be715c7', '019d98ca-f8f0-7255-a7ec-eb5647937711', '65caea5cd50fff347332fa7faab333d794593f9bd8e9832892cac1a8b8cf3732', NULL, '2026-04-22 12:55:33', 'Signature via workflow test 2', NULL, 1, 'valid', NULL, '2026-04-22 12:55:34'),
('019db542-99d9-7161-b19a-9d20a5014eda', '019db527-9d08-73ae-82cf-ead1c8b095b4', '019d98ca-f8f0-7255-a7ec-eb5647937711', '4affbbf4ac15a2cfc14b6632b661a9e0ae30d659bf8df78a3a7ad737129379a9', NULL, '2026-04-22 12:55:34', 'Signature via workflow test 2', NULL, 1, 'valid', NULL, '2026-04-22 12:55:34');

-- --------------------------------------------------------

--
-- Structure de la table `signature_provider_configs`
--

DROP TABLE IF EXISTS `signature_provider_configs`;
CREATE TABLE IF NOT EXISTS `signature_provider_configs` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT (uuid()),
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'NULL = config globale',
  `administration_type` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'emitter' COMMENT 'emitter|recipient',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `endpoint` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'URL de base ex: https://uvci.artci-sign.ci',
  `sign_path` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '/v1/sign',
  `api_key` text COLLATE utf8mb3_unicode_ci COMMENT 'Bearer token',
  `consent_page_id` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `signature_profile_id` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `provider_owner_user_id` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'Auto-découvert via /api/users/me si vide',
  `verify_ssl` tinyint(1) NOT NULL DEFAULT '1',
  `timeout_ms` int NOT NULL DEFAULT '30000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sig_provider_admin` (`administration_id`,`administration_type`),
  KEY `signature_provider_configs_administration_id_index` (`administration_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `signature_requests`
--

DROP TABLE IF EXISTS `signature_requests`;
CREATE TABLE IF NOT EXISTS `signature_requests` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `requested_by` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `requested_to` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb3_unicode_ci,
  `status` enum('pending','signed','declined','expired') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'pending',
  `expiry_date` timestamp NULL DEFAULT NULL,
  `zone_page` smallint UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Numéro de page (1-based)',
  `zone_x` float DEFAULT NULL COMMENT 'Position X en % de la largeur de la page',
  `zone_y` float DEFAULT NULL COMMENT 'Position Y en % de la hauteur de la page',
  `zone_width` float DEFAULT NULL COMMENT 'Largeur en % de la largeur de la page',
  `zone_height` float DEFAULT NULL COMMENT 'Hauteur en % de la hauteur de la page',
  `zone_label` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL COMMENT 'Texte affiché dans la zone (ex: nom du signataire)',
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `signature_requests_document_id_foreign` (`document_id`),
  KEY `signature_requests_requested_by_index` (`requested_by`),
  KEY `signature_requests_requested_to_index` (`requested_to`),
  KEY `signature_requests_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sub_entities`
--

DROP TABLE IF EXISTS `sub_entities`;
CREATE TABLE IF NOT EXISTS `sub_entities` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `scope_type` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `scope_id` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `parent_code` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `direction_type_id` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `manager_name` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `manager_email` varchar(191) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_entities_code_unique` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `sub_entities`
--

INSERT INTO `sub_entities` (`id`, `scope_type`, `scope_id`, `name`, `code`, `parent_code`, `direction_type_id`, `manager_name`, `manager_email`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
('019d9db1-3407-73e3-97d3-ec273a54debb', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CABINET MINISTRE', 'CAB MIN', NULL, '019d9da4-6c5f-7202-8e79-998570b2c133', 'ANNE OULOTO LAMISANA', 'a.ouloto@parapheur.ci', NULL, 1, '2026-04-17 23:05:29', '2026-04-17 23:05:29'),
('019d9db4-5136-721b-aa23-bac16af9f8f4', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CABINET DIRCAB', 'CAB DIRCAB', 'CAB MIN', '019d9da5-201c-72bf-bf71-a08cbebc238c', 'KABA NASSERE', 'n.kaba@parapheur.ci', NULL, 1, '2026-04-17 23:08:53', '2026-04-17 23:08:53'),
('019d9db8-d3a1-7181-9448-22d5a2b585dc', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CABINET DIRCAB ADJOINT', 'CAB DIRCAB ADJ', 'CAB MIN', '019d9da5-c420-7058-9fdb-26e5d68d5065', 'SOUMARE DAOUDA', 'd.soumare@parapheur.ci', NULL, 1, '2026-04-17 23:13:48', '2026-04-17 23:13:48'),
('019d9dbb-502d-71eb-bc63-2e14ae767216', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTION GENERALE DE LA TRANSFORMATION DU SERVICE PUBLIQUE', 'DGTSP', 'CAB DIRCAB', '019d9da6-78aa-727b-aec7-7b5c4520aa97', 'YEBOUET AUGUSTIN', 'a.yebouet@parapheur.ci', NULL, 1, '2026-04-17 23:16:31', '2026-04-17 23:16:31'),
('019d9dbc-f690-706f-83e8-343a4ee5d16c', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTION DE LA MODERNISATION , L\'ORGANISATION ADMINISTRATIVE', 'DMOA', 'DGTSP', '019d9da6-f4ef-70a2-b463-34e3b030b6fd', 'ANTOINE BESSIN', 'a.bessin@parapheur.ci', NULL, 1, '2026-04-17 23:18:20', '2026-04-17 23:18:20'),
('019d9dc0-c588-71e3-b843-66667d70d839', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTION DE L\'APPUI A LA PERFORMANCE DU SERVICE PUBLIQUE', 'DAPSP', 'DGTSP', '019d9da6-f4ef-70a2-b463-34e3b030b6fd', 'RABE HORTENSE', 'h.rabe@parapheur.ci', NULL, 1, '2026-04-17 23:22:29', '2026-04-17 23:22:29'),
('019d9dc2-30ce-72a6-8819-601433f5b09b', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'DIRECTION DES ETUDES ET METHODES', 'DEM', 'DGTSP', '019d9da6-f4ef-70a2-b463-34e3b030b6fd', 'SOPPI FRANCK', 'f.soppi@parapheur.ci', NULL, 1, '2026-04-17 23:24:02', '2026-04-17 23:24:02'),
('019d9dc4-e22d-721a-a18d-49e921ddf73d', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SOUS DIRECTION DE LA RESTRUCTUTION DES ORGANISATIONS', 'SDRO', 'DMOA', '019d9da8-cbc7-7258-9bde-6d6af9e06c89', 'ROUEN MARC', 'm.rouen@parapheur.ci', NULL, 1, '2026-04-17 23:26:59', '2026-04-17 23:26:59'),
('019d9dc6-c286-71a2-a208-0f7e988f7ef8', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SOUS DIRECTION DE LA SIMPLIFICATION DES PROCEDURES', 'SDSP', 'DMOA', '019d9da8-cbc7-7258-9bde-6d6af9e06c89', 'YEO CLARISSE', 'c.yeo@parapheur.ci', NULL, 1, '2026-04-17 23:29:02', '2026-04-17 23:29:02'),
('019d9dc8-a085-70d2-8c0c-d3234aefbb99', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SERVICE DE LA SIMPLIFICATION DES PROCEDURES', 'SSP', 'SDSP', '019d9da9-270d-728e-8643-2cba325e1bd7', 'MAHAN BERTINE', 'b.mahan@parapheur.ci', NULL, 1, '2026-04-17 23:31:04', '2026-04-17 23:31:04'),
('019d9dca-9ee0-738a-aebc-2bb83083e87b', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'SERVICE GESTION ELECTRONIQUE DES DONNEES', 'SGED', 'SDSP', '019d9da9-270d-728e-8643-2cba325e1bd7', 'KEITA NAMORY', 'n.keita@parapheur.ci', NULL, 1, '2026-04-17 23:33:15', '2026-04-17 23:33:15');

-- --------------------------------------------------------

--
-- Structure de la table `template_variables`
--

DROP TABLE IF EXISTS `template_variables`;
CREATE TABLE IF NOT EXISTS `template_variables` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `template_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(150) COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `field_type` enum('text','date','number','select','textarea') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `placeholder` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `default_value` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `options` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_variables_template_id_foreign` (`template_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `template_variables`
--

INSERT INTO `template_variables` (`id`, `template_id`, `key`, `label`, `field_type`, `required`, `placeholder`, `default_value`, `options`, `created_at`, `updated_at`) VALUES
('019da4f1-5c40-73e5-806c-8680ddc4ee7d', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5c59-70ac-a184-4a5bb13bc397', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5c69-7300-8ad1-c6ddbd57f48b', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5c79-7300-b3d4-ffee1bb1797d', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5c87-7359-a949-64171292d81b', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5c94-72f5-9d36-37353e86c4d8', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5ca2-7282-860c-860afe2c0407', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5cb0-70f6-a42f-f854609aef6d', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5cbd-72f9-871e-7eeccb36fdd2', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da4f1-5ccb-7128-81b0-d49fc2fc3721', '019da4f1-5c0e-71c0-a36b-0560d28b57b5', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-19 08:52:54', '2026-04-19 08:52:54'),
('019da7ab-9c2a-72f0-afa2-fb3372119bea', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:34', '2026-04-19 21:35:34'),
('019da7ab-9c3a-70b8-a99d-3d1aba3c51a6', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:34', '2026-04-19 21:35:34'),
('019da7ab-9c47-7344-ab59-4a94f1cdc4ca', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:34', '2026-04-19 21:35:34'),
('019da7ab-9c5a-70e8-a5a5-1afd6fca51b0', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9c6b-71d2-8605-9eca967ff688', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9c7b-711a-a527-9845bbaed456', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9c8d-73bd-b055-fd25d90c220d', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9c9b-7180-a1ad-6dd635a540ad', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9cac-70f0-93ed-60e332c53338', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7ab-9cc1-7018-9df4-2b873beb07aa', '019da7ab-9bf4-726e-a30e-fbcdf2abfb7a', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:35:35', '2026-04-19 21:35:35'),
('019da7be-b86d-7393-b3b7-7d3aca42bdc9', '019da7be-b80b-7289-82bd-55995fa12593', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b883-7178-83f9-91c28a7d592c', '019da7be-b80b-7289-82bd-55995fa12593', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b899-70f8-a08b-f0da349bcb5e', '019da7be-b80b-7289-82bd-55995fa12593', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b8ac-73a5-9f1d-32c69c8540d3', '019da7be-b80b-7289-82bd-55995fa12593', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b8be-7186-899e-84900d429114', '019da7be-b80b-7289-82bd-55995fa12593', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b8d2-7024-891b-9f350a601e4c', '019da7be-b80b-7289-82bd-55995fa12593', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b8e1-720d-b177-b6394e063a7f', '019da7be-b80b-7289-82bd-55995fa12593', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b8f1-70b0-9760-ce767cb31dfb', '019da7be-b80b-7289-82bd-55995fa12593', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b903-7054-98e7-05655015ebaa', '019da7be-b80b-7289-82bd-55995fa12593', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7be-b916-710d-a048-ce79f6a7a3a7', '019da7be-b80b-7289-82bd-55995fa12593', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-19 21:56:27', '2026-04-19 21:56:27'),
('019da7d7-f3f0-7348-955a-7a98692f7c14', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f407-7222-b555-d4df0314dc0c', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f414-73d7-b1eb-1e2ba6efeb75', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f423-70d9-8f5b-ce035945fb08', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f434-711f-bb0a-ba57a5418f86', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f446-72d9-b4ab-de9e5267db06', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f45a-73af-b8ea-ba3eaeba051b', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f46c-7080-bcd7-0eb000bea436', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f47d-721c-9473-aac1f2861b46', '019da7d7-f3af-72e6-be29-15b59b419cf8', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7d7-f48d-7188-bf1d-7ce8c9f5aebc', '019da7d7-f3af-72e6-be29-15b59b419cf8', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:24:01', '2026-04-19 22:24:01'),
('019da7da-26f2-70de-8815-237e927ed81c', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-26ff-72cb-8278-9d1143c6bb15', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-270c-730e-b554-07d7e3d969e0', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-271a-703c-81d4-7f4bc733c5b7', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-2727-7003-8bd0-f8db2aa4c5a8', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-2734-70b1-8dab-032487258e5c', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-2741-72a3-873e-0cec4e234d32', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-274e-71d3-8360-5abba890bbd0', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-275d-7023-8e36-159d06157e96', '019da7da-26c6-70b3-b39f-56e11cbc3692', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019da7da-276a-7021-8e55-07d26788fe3c', '019da7da-26c6-70b3-b39f-56e11cbc3692', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-19 22:26:25', '2026-04-19 22:26:25'),
('019dad49-3cf4-7002-a726-c56e02ad6bdd', '019dad49-3c96-7275-957f-8cb471e2886f', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d0d-722e-936d-783e839679b2', '019dad49-3c96-7275-957f-8cb471e2886f', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d1c-70bf-9df7-712bae7a65fb', '019dad49-3c96-7275-957f-8cb471e2886f', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d2b-7104-bb08-07c069e70db3', '019dad49-3c96-7275-957f-8cb471e2886f', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d39-72b7-9338-02f531d0c6ad', '019dad49-3c96-7275-957f-8cb471e2886f', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d46-7034-95e5-9d17e01e92da', '019dad49-3c96-7275-957f-8cb471e2886f', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d54-712d-83c1-1c12f5dc4463', '019dad49-3c96-7275-957f-8cb471e2886f', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d61-71b5-b88a-a0db28a6fd54', '019dad49-3c96-7275-957f-8cb471e2886f', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d70-732a-93d0-7f346e3380fb', '019dad49-3c96-7275-957f-8cb471e2886f', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019dad49-3d7e-7356-ae0b-85da37c7a6cd', '019dad49-3c96-7275-957f-8cb471e2886f', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-20 23:45:51', '2026-04-20 23:45:51'),
('019daf11-cc06-71a4-a08b-255b6d8655a0', '019daf11-cb9a-700c-8d32-12015adff608', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc1c-7082-85c3-a4448f57f036', '019daf11-cb9a-700c-8d32-12015adff608', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc2a-72b6-a2ac-02f45695290e', '019daf11-cb9a-700c-8d32-12015adff608', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc38-70d0-97d7-cc1a5837e795', '019daf11-cb9a-700c-8d32-12015adff608', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc46-73e7-8ff5-d95cfb9c03b8', '019daf11-cb9a-700c-8d32-12015adff608', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc5a-705c-b90b-22a6362db275', '019daf11-cb9a-700c-8d32-12015adff608', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc68-7068-8087-4115ca0f78f6', '019daf11-cb9a-700c-8d32-12015adff608', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc75-7001-bd81-76f271b415e7', '019daf11-cb9a-700c-8d32-12015adff608', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc83-7293-9c41-bb5aaf5fa9bb', '019daf11-cb9a-700c-8d32-12015adff608', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf11-cc90-7170-9bea-054e289aa030', '019daf11-cb9a-700c-8d32-12015adff608', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:04:32', '2026-04-21 08:04:32'),
('019daf1f-922a-7142-90c2-37bdd9c68643', '019daf1f-9191-737b-b773-1cad5a7f225b', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-9255-7053-8c2e-f53cd855c74c', '019daf1f-9191-737b-b773-1cad5a7f225b', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-926f-7128-92e6-45ab33e97eb3', '019daf1f-9191-737b-b773-1cad5a7f225b', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-928a-7374-b9a8-60fbffd1e0cd', '019daf1f-9191-737b-b773-1cad5a7f225b', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-92a0-7057-af96-d2ca4f941ed5', '019daf1f-9191-737b-b773-1cad5a7f225b', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-92b4-736a-9d66-254cd47214f7', '019daf1f-9191-737b-b773-1cad5a7f225b', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-92c6-7227-9c16-5841c3676a15', '019daf1f-9191-737b-b773-1cad5a7f225b', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-92db-729e-ae6e-e49ff8e8ec23', '019daf1f-9191-737b-b773-1cad5a7f225b', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-92f0-736e-b197-289d7522e6d6', '019daf1f-9191-737b-b773-1cad5a7f225b', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf1f-9303-7394-9552-7a6dd6600524', '019daf1f-9191-737b-b773-1cad5a7f225b', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:19:35', '2026-04-21 08:19:35'),
('019daf3f-6445-7004-8858-576b29f57f36', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-6460-7394-888d-6ce06a4f44e6', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-646f-70a4-a781-4673917a6412', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-647d-7210-86cf-066d0ef01614', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-648b-7309-b35c-839b05395f98', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-6498-70aa-83ad-3953f4a49e8b', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-64a6-7384-9479-5bffadbc5e11', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-64b4-7235-8b83-64ef8c2d4012', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-64c3-7103-9a7f-259ed6d306ea', '019daf3f-63e5-72ca-bfe4-b34630c0f429', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf3f-64d1-7252-9929-5ef212281385', '019daf3f-63e5-72ca-bfe4-b34630c0f429', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 08:54:20', '2026-04-21 08:54:20'),
('019daf50-9f84-701c-9c7a-9e12284255b7', '019daf50-9f3d-735a-bc07-19863048310d', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9f95-7371-bb69-a992158430b0', '019daf50-9f3d-735a-bc07-19863048310d', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9fa3-730c-99d7-df48a33e17f0', '019daf50-9f3d-735a-bc07-19863048310d', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9fb3-726a-8af2-bf873369d56b', '019daf50-9f3d-735a-bc07-19863048310d', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9fc1-7020-a64e-0c53b8397447', '019daf50-9f3d-735a-bc07-19863048310d', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9fd0-7351-8b65-9df9f1aa0f60', '019daf50-9f3d-735a-bc07-19863048310d', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9fe5-70fb-a9ac-d07db90f2898', '019daf50-9f3d-735a-bc07-19863048310d', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-9ffe-73b2-8b83-196ed4a922aa', '019daf50-9f3d-735a-bc07-19863048310d', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-a017-7208-b16b-2a81d634a184', '019daf50-9f3d-735a-bc07-19863048310d', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-a02d-7031-862a-8de487197788', '019daf50-9f3d-735a-bc07-19863048310d', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:09', '2026-04-21 09:13:09'),
('019daf50-daf3-7378-b007-9b8a20b18965', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:24', '2026-04-21 09:13:24'),
('019daf50-db03-72e1-b36f-1c9ece380ed1', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:24', '2026-04-21 09:13:24'),
('019daf50-db10-73e0-942c-d9536d510f0a', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db1e-711d-b72d-f28cf158a081', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db2b-7346-818d-08f4365a67c2', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db39-7263-99c4-9394ffaad48e', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db48-732e-be45-07fa6ad886fa', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db58-70c2-85a2-3b90f8b1018c', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db65-71c9-894f-283ef6427978', '019daf50-dac7-7000-b7b3-0771c77d1a38', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf50-db73-733a-ae75-9f366e60ffc8', '019daf50-dac7-7000-b7b3-0771c77d1a38', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:13:25', '2026-04-21 09:13:25'),
('019daf63-5e2d-7196-ba40-46ec79ccb150', '019daf52-7148-7279-a55b-f551ba6a486f', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e41-72b5-aad2-4044c52f504e', '019daf52-7148-7279-a55b-f551ba6a486f', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e4e-7353-976b-9d3ad8bb2ea2', '019daf52-7148-7279-a55b-f551ba6a486f', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e5c-70bb-94af-76da0ad36e15', '019daf52-7148-7279-a55b-f551ba6a486f', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e6a-717d-aeb5-a401b61ed899', '019daf52-7148-7279-a55b-f551ba6a486f', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e77-70c6-ac85-3c83943cf088', '019daf52-7148-7279-a55b-f551ba6a486f', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e85-70d5-b230-4a5d14be1168', '019daf52-7148-7279-a55b-f551ba6a486f', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5e93-72aa-9341-edd5df34c39a', '019daf52-7148-7279-a55b-f551ba6a486f', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('019daf63-5ea1-72ae-83db-5ecb46944f55', '019daf52-7148-7279-a55b-f551ba6a486f', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 09:33:38', '2026-04-21 09:33:38'),
('59702d49-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('5972b6e5-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('5972e29f-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('5973093c-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('597348d2-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('59736ccb-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('59739337-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('5973b4a3-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', 'france', 'France', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('5973d81b-3d6b-11f1-bbd3-0c5415f032db', '019daf63-01ba-728f-92bb-0e3f84bf4f17', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '[]', '2026-04-21 10:17:49', '2026-04-21 10:17:49'),
('019db101-dd71-73ce-9f45-843b78423ab2', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-dd89-725b-9b32-6bf966b2f3d5', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-dd98-72d8-a3bf-e5ff95322fd6', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-dda6-711c-8b02-3ae529be4118', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-ddb4-70bb-b72f-2857e98dc592', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-ddc3-72d5-aaa5-c55f79f73485', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-ddd2-7098-a32b-e8e8e5484c62', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-dde0-737a-a449-40bc1fc77e79', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-ddee-70d3-bcb6-678580ae59bf', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db101-ddfd-71d0-ba22-e001e9cb30b7', '019db101-dd20-715b-85e7-9ce3cdf2ebcd', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-21 17:06:22', '2026-04-21 17:06:22'),
('019db5d1-2fab-71cc-a14e-cb143682e005', '019db5d1-2f4f-732b-9211-755e57de9f58', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-22 15:31:18', '2026-04-22 15:31:18'),
('019db71d-987d-72bd-9863-24a98bed583b', '019db71d-9828-7307-94d7-61890518c7bc', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:34:23', '2026-04-22 21:34:23'),
('019db720-50cf-718a-a17e-9bfca0df5036', '019db720-504b-7335-9c52-fb9aca6c9709', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-50ec-732c-ac56-9a898d688301', '019db720-504b-7335-9c52-fb9aca6c9709', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-5107-72fd-ab2b-a408a0c63614', '019db720-504b-7335-9c52-fb9aca6c9709', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-5125-72f1-89c0-28e2049e9f70', '019db720-504b-7335-9c52-fb9aca6c9709', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-5143-71fd-bed6-bc93fff174e5', '019db720-504b-7335-9c52-fb9aca6c9709', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-517a-7341-b603-fedefcd11039', '019db720-504b-7335-9c52-fb9aca6c9709', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-519d-72d4-b611-2169c4757428', '019db720-504b-7335-9c52-fb9aca6c9709', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-51ba-7034-9272-5eab35ece05a', '019db720-504b-7335-9c52-fb9aca6c9709', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-51de-7266-89e4-54af72197f28', '019db720-504b-7335-9c52-fb9aca6c9709', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db720-51fd-7057-8f30-dc145efc8b94', '019db720-504b-7335-9c52-fb9aca6c9709', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-22 21:37:21', '2026-04-22 21:37:21'),
('019db7b3-00fc-721d-a324-9448442411a7', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:34', '2026-04-23 00:17:34'),
('019db7b3-0112-708c-b2a1-83651a0c5cd4', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:34', '2026-04-23 00:17:34'),
('019db7b3-0122-702e-9666-b6ef44cd3e07', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-013e-722a-95d1-e878ab7cd4a9', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-0159-706b-a7d6-ff39c3f30d37', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-0171-7383-ad4a-f3a26363acf1', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-018a-71d7-b32c-494e396eae37', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-01a3-72bc-a2ea-f82df49be6c2', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-01bf-7292-96a2-229142958cb0', '019db7b3-0054-70a1-95f8-091cc508c1bc', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7b3-01d9-7193-9e25-75ac7526b067', '019db7b3-0054-70a1-95f8-091cc508c1bc', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-23 00:17:35', '2026-04-23 00:17:35'),
('019db7df-0bff-7246-a306-9af1122a28f7', '019db7df-0bca-707b-974f-a68dff1870bc', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c12-70a0-adea-a68513a483b5', '019db7df-0bca-707b-974f-a68dff1870bc', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c22-705d-8bcf-360a3d18b696', '019db7df-0bca-707b-974f-a68dff1870bc', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c36-714d-b576-29ac8a85b156', '019db7df-0bca-707b-974f-a68dff1870bc', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c4d-7240-a903-2e5999c58d34', '019db7df-0bca-707b-974f-a68dff1870bc', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c5e-721c-9be8-2ad98ea53a9a', '019db7df-0bca-707b-974f-a68dff1870bc', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c75-7013-a48e-dc1557ccdb52', '019db7df-0bca-707b-974f-a68dff1870bc', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0c8b-71e2-b0ff-31fb832a02d6', '019db7df-0bca-707b-974f-a68dff1870bc', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0ca0-7317-a23c-33c46c14b052', '019db7df-0bca-707b-974f-a68dff1870bc', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7df-0cb2-702e-8a2b-f28886551bd6', '019db7df-0bca-707b-974f-a68dff1870bc', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:05:41', '2026-04-23 01:05:41'),
('019db7e5-2b8a-71e5-a0eb-84b0c4101464', '019db7e5-2b2e-7325-8321-690227366f6d', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2bab-7061-a725-1af91f7890c0', '019db7e5-2b2e-7325-8321-690227366f6d', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2bc9-73c5-ac1f-2ad5ba86b259', '019db7e5-2b2e-7325-8321-690227366f6d', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2be5-7373-ad8f-3ce66b78d505', '019db7e5-2b2e-7325-8321-690227366f6d', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c02-737f-af3a-6187efe5504b', '019db7e5-2b2e-7325-8321-690227366f6d', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c20-7030-af4f-1c0fe7b66044', '019db7e5-2b2e-7325-8321-690227366f6d', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c3d-7286-9c47-ed6829609f9f', '019db7e5-2b2e-7325-8321-690227366f6d', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c5a-7395-9826-3d05210bfeea', '019db7e5-2b2e-7325-8321-690227366f6d', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c76-7009-8361-8fb9fbdd6fdd', '019db7e5-2b2e-7325-8321-690227366f6d', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7e5-2c91-7193-a470-ce63985fcb0b', '019db7e5-2b2e-7325-8321-690227366f6d', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:12:22', '2026-04-23 01:12:22'),
('019db7ec-f202-72bf-947c-1d3fcc9f254a', '019db7ec-f1b0-702b-ac10-a440258776eb', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f21e-72e9-90db-7f6592cbf3b2', '019db7ec-f1b0-702b-ac10-a440258776eb', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f23b-71fc-b973-f583edd895a8', '019db7ec-f1b0-702b-ac10-a440258776eb', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f259-715d-a34f-4d656d61d0f2', '019db7ec-f1b0-702b-ac10-a440258776eb', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f277-7271-a4b0-0dfbe06b8a96', '019db7ec-f1b0-702b-ac10-a440258776eb', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f296-71a8-a2ac-c992c13a1768', '019db7ec-f1b0-702b-ac10-a440258776eb', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f2b3-7292-8dd9-f18c883de2d7', '019db7ec-f1b0-702b-ac10-a440258776eb', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f2d0-7230-b01f-7380432ed901', '019db7ec-f1b0-702b-ac10-a440258776eb', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f2ec-70a6-9292-ce52d2907a9a', '019db7ec-f1b0-702b-ac10-a440258776eb', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ec-f30a-716b-99c8-21e56c05098b', '019db7ec-f1b0-702b-ac10-a440258776eb', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:20:52', '2026-04-23 01:20:52'),
('019db7ef-59ce-7155-bf54-507e9bd7da26', '019db7ef-59a9-727c-9745-78009c96e177', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 01:23:29', '2026-04-23 01:23:29'),
('019dba7f-dddb-73e1-88fc-bf2ded018a9b', '019dba7f-dd93-71d6-b02a-e1eec6d63ca0', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 13:20:35', '2026-04-23 13:20:35'),
('019dba94-9b04-7344-8e73-f2e24f2d08e2', '019dba94-9aa9-72f6-a435-338225ee5a4c', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 13:43:14', '2026-04-23 13:43:14'),
('019dbabf-bd4e-7169-8a4e-9f71544f8900', '019dbabf-bd08-73e5-a38d-2ec70c51672a', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 14:30:21', '2026-04-23 14:30:21'),
('019dbae5-5039-7058-99c8-4a4733ac989a', '019dbae5-4ff6-7024-9032-05a192daadd8', 'n_djomon_ohouo_landry_marius', 'N’DJOMON Ohouo Landry Marius', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-5055-71ab-87e4-56edfe278b30', '019dbae5-4ff6-7024-9032-05a192daadd8', 'mle_365_792_h', '(Mle 365 792 H)', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-506d-712c-9e1f-2ccaf06c11ad', '019dbae5-4ff6-7024-9032-05a192daadd8', 'mle_323_387_z', '(Mle 323 387 Z)', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-5087-726b-8d4a-8f3eabb1d7ca', '019dbae5-4ff6-7024-9032-05a192daadd8', 'administrateurs_des_services_financiers', 'Administrateurs des Services Financiers', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-509d-73fb-8fcd-41f8f48d16ca', '019dbae5-4ff6-7024-9032-05a192daadd8', 'charg_es_d_etudes_au_cabinet', 'Chargés d’Etudes au Cabinet', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-50b7-718a-b011-fa159dbdcfc9', '019dbae5-4ff6-7024-9032-05a192daadd8', 'executive_master_march_es_de_capitaux_ifc_europlace_dauphine', '« EXECUTIVE MASTER MARCHÉS DE CAPITAUX IFC-EUROPLACE-DAUPHINE »', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-50cf-7234-ade6-777de117b7c3', '019dbae5-4ff6-7024-9032-05a192daadd8', 'paris_dauphine', 'Paris Dauphine', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-50e5-71a2-8397-2810034aded5', '019dbae5-4ff6-7024-9032-05a192daadd8', 'france', 'France', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-510e-7216-baa9-21689e27f5ae', '019dbae5-4ff6-7024-9032-05a192daadd8', '05_septembre_2024_au_15_juin_2025', '05 septembre 2024 au 15 juin 2025', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae5-5123-7219-86c1-6ddeb89f0ccd', '019dbae5-4ff6-7024-9032-05a192daadd8', 'document_number', 'document_number', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:11:23', '2026-04-23 15:11:23'),
('019dbae6-2d7c-7128-a553-ce1b54c8e378', '019dbae6-2d48-7284-ba78-f9adfd635c2f', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:12:20', '2026-04-23 15:12:20'),
('019dbafb-1077-732d-99d3-cce1f3e63f24', '019dbafb-1035-7167-b6c7-95f5234a3ce8', 'nom_du_directeur', 'nom du Directeur', 'text', 0, '', '', '\"[]\"', '2026-04-23 15:35:09', '2026-04-23 15:35:09');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `avatar` varchar(500) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `role` enum('admin','user','signer','manager') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'user',
  `profile_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'active',
  `quota` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT '5 Go',
  `bio` text COLLATE utf8mb3_unicode_ci,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `full_name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `avatar`, `role`, `profile_id`, `status`, `quota`, `bio`, `deleted_at`) VALUES
('019d98ca-f8f0-7255-a7ec-eb5647937711', 'Administrateur', 'Administrateur Système', 'admin@e-parapheur.local', NULL, '$2y$12$UcbWq7bahoHKK0Trdizqwej.cJLvYiT6jn4FGRHIea/1TUBHpQKiG', 'KfP89LRpeR7pz00KySVMeuxjFwc0Z3UJh5Zr9Sn3MHJ4jP5AT41au0PuAXRY', '2026-04-17 00:15:32', '2026-04-21 17:51:23', 'images/avatars/avatar_69e7b91b3e061.png', 'admin', NULL, 'active', '5 Go', NULL, NULL),
('019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'SUPER ADMIN', 'ABOU BAK KAMA', 'jjkam555@yahoo.fr', NULL, '$2y$12$L9ZDoBUX0Db5yTl.ej11zOaif6b5K.HMQoL1M3TE7scLyeVPYMaFS', NULL, '2026-04-18 01:07:19', '2026-04-23 09:13:47', 'images/avatars/avatar_69e9e2cbd9ed4.jpg', 'user', '019d9de1-cb5a-71fe-b61e-a8d8e831882b', 'active', 'Illimité', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user_direction_assignments`
--

DROP TABLE IF EXISTS `user_direction_assignments`;
CREATE TABLE IF NOT EXISTS `user_direction_assignments` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `direction_scope_type` enum('emitter','recipient') COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `direction_scope_id` varchar(120) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `sub_entity_code` varchar(120) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `direction_label` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_direction_assignments_user_id_index` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `user_direction_assignments`
--

INSERT INTO `user_direction_assignments` (`id`, `user_id`, `direction_scope_type`, `direction_scope_id`, `sub_entity_code`, `direction_label`, `created_at`, `updated_at`) VALUES
('019d9e20-c02f-717c-a2eb-9b7d04030281', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', 'emitter', '019d9daf-61ed-70d4-8bfd-d65f58ec21c2', 'CAB MIN', 'CABINET MINISTRE', '2026-04-18 01:07:19', '2026-04-18 01:07:19');

-- --------------------------------------------------------

--
-- Structure de la table `workflows`
--

DROP TABLE IF EXISTS `workflows`;
CREATE TABLE IF NOT EXISTS `workflows` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(500) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `status` enum('active','inactive','archived') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'active',
  `docs_to_sign` json DEFAULT NULL,
  `attached_docs` json DEFAULT NULL,
  `uploaded_signature_files` json DEFAULT NULL,
  `created_by` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflows_status_index` (`status`),
  KEY `workflows_created_by_index` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `workflows`
--

INSERT INTO `workflows` (`id`, `name`, `description`, `status`, `docs_to_sign`, `attached_docs`, `uploaded_signature_files`, `created_by`, `created_at`, `updated_at`) VALUES
('019db51b-a761-727b-8f5f-d51d462c9a4d', 'test demo', NULL, 'active', '[\"019db51a-f65e-72b4-9498-3076ca8231cb\"]', '[]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-22 12:13:01', '2026-04-22 12:13:01'),
('019db528-8126-727b-969b-4db0ff71d7bc', 'test 2', NULL, 'active', '[\"019db526-bdf9-70b5-ac41-73a78be715c7\", \"019db527-9d08-73ae-82cf-ead1c8b095b4\"]', '[]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-22 12:27:03', '2026-04-22 12:27:03'),
('019db539-3f71-7329-8e6f-c42660d3827d', 'test V', NULL, 'active', '[\"019db536-fdf3-7326-8b91-6bb075be03d2\", \"019db537-2299-715c-bf0d-074c8e449b63\", \"019db537-854a-7357-a988-80b0e96a4042\"]', '[]', NULL, '019d9e20-bfd8-7149-8260-e3b3be5d8c06', '2026-04-22 12:45:21', '2026-04-22 12:45:21');

-- --------------------------------------------------------

--
-- Structure de la table `workflow_executions`
--

DROP TABLE IF EXISTS `workflow_executions`;
CREATE TABLE IF NOT EXISTS `workflow_executions` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `workflow_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `document_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `current_step` int NOT NULL DEFAULT '1',
  `status` enum('in_progress','completed','rejected','paused','cancelled') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'in_progress',
  `step_data` json DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_executions_workflow_id_foreign` (`workflow_id`),
  KEY `workflow_executions_document_id_foreign` (`document_id`),
  KEY `workflow_executions_status_index` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `workflow_executions`
--

INSERT INTO `workflow_executions` (`id`, `workflow_id`, `document_id`, `current_step`, `status`, `step_data`, `started_at`, `completed_at`) VALUES
('019db51b-a9c6-73a2-b3bc-681e0d37ce5a', '019db51b-a761-727b-8f5f-d51d462c9a4d', '019db51a-f65e-72b4-9498-3076ca8231cb', 3, 'completed', '{\"doc_zones\": {\"019db51a-f65e-72b4-9498-3076ca8231cb\": [{\"h\": 16.992, \"w\": 29.435, \"x\": 62.549, \"y\": 65.924, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:13:02', '2026-04-22 12:55:01'),
('019db528-8340-70a0-90ca-79f592b34378', '019db528-8126-727b-969b-4db0ff71d7bc', '019db526-bdf9-70b5-ac41-73a78be715c7', 3, 'completed', '{\"doc_zones\": {\"019db526-bdf9-70b5-ac41-73a78be715c7\": [{\"h\": 19.034, \"w\": 27.464, \"x\": 60.447, \"y\": 65.274, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db527-9d08-73ae-82cf-ead1c8b095b4\": [{\"h\": 18.942, \"w\": 28.084, \"x\": 52.362, \"y\": 38.872, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:27:04', '2026-04-22 12:55:33'),
('019db528-834f-7125-8f18-74b927c571a1', '019db528-8126-727b-969b-4db0ff71d7bc', '019db527-9d08-73ae-82cf-ead1c8b095b4', 3, 'completed', '{\"doc_zones\": {\"019db526-bdf9-70b5-ac41-73a78be715c7\": [{\"h\": 19.034, \"w\": 27.464, \"x\": 60.447, \"y\": 65.274, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db527-9d08-73ae-82cf-ead1c8b095b4\": [{\"h\": 18.942, \"w\": 28.084, \"x\": 52.362, \"y\": 38.872, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:27:04', '2026-04-22 12:55:34'),
('019db539-4207-711c-803d-1b4becda97fd', '019db539-3f71-7329-8e6f-c42660d3827d', '019db536-fdf3-7326-8b91-6bb075be03d2', 1, 'in_progress', '{\"doc_zones\": {\"019db536-fdf3-7326-8b91-6bb075be03d2\": [{\"h\": 20.799, \"w\": 31.234, \"x\": 52.887, \"y\": 39.151, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-2299-715c-bf0d-074c8e449b63\": [{\"h\": 18.384, \"w\": 30.88, \"x\": 47.438, \"y\": 42.34, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-854a-7357-a988-80b0e96a4042\": [{\"h\": 12.999, \"w\": 36.352, \"x\": 62.073, \"y\": 79.109, \"_pct\": true, \"page\": 5, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:45:21', NULL),
('019db539-4216-7322-afaf-7365554432ee', '019db539-3f71-7329-8e6f-c42660d3827d', '019db537-2299-715c-bf0d-074c8e449b63', 1, 'in_progress', '{\"doc_zones\": {\"019db536-fdf3-7326-8b91-6bb075be03d2\": [{\"h\": 20.799, \"w\": 31.234, \"x\": 52.887, \"y\": 39.151, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-2299-715c-bf0d-074c8e449b63\": [{\"h\": 18.384, \"w\": 30.88, \"x\": 47.438, \"y\": 42.34, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-854a-7357-a988-80b0e96a4042\": [{\"h\": 12.999, \"w\": 36.352, \"x\": 62.073, \"y\": 79.109, \"_pct\": true, \"page\": 5, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:45:21', NULL),
('019db539-421e-7353-b1fd-5b58f88f41ee', '019db539-3f71-7329-8e6f-c42660d3827d', '019db537-854a-7357-a988-80b0e96a4042', 1, 'in_progress', '{\"doc_zones\": {\"019db536-fdf3-7326-8b91-6bb075be03d2\": [{\"h\": 20.799, \"w\": 31.234, \"x\": 52.887, \"y\": 39.151, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-2299-715c-bf0d-074c8e449b63\": [{\"h\": 18.384, \"w\": 30.88, \"x\": 47.438, \"y\": 42.34, \"_pct\": true, \"page\": 2, \"label\": \"Signature 1\"}], \"019db537-854a-7357-a988-80b0e96a4042\": [{\"h\": 12.999, \"w\": 36.352, \"x\": 62.073, \"y\": 79.109, \"_pct\": true, \"page\": 5, \"label\": \"Signature 1\"}]}}', '2026-04-22 12:45:21', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `workflow_steps`
--

DROP TABLE IF EXISTS `workflow_steps`;
CREATE TABLE IF NOT EXISTS `workflow_steps` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `workflow_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `order` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `type` enum('review','sign','approve','reject','notify') COLLATE utf8mb3_unicode_ci NOT NULL,
  `assignee_id` char(36) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `requires_signature` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `workflow_steps_workflow_id_order_unique` (`workflow_id`,`order`),
  KEY `workflow_steps_assignee_id_index` (`assignee_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `workflow_steps`
--

INSERT INTO `workflow_steps` (`id`, `workflow_id`, `order`, `name`, `type`, `assignee_id`, `description`, `requires_signature`, `created_at`) VALUES
('019db51b-a796-73de-bcc5-f231eb349838', '019db51b-a761-727b-8f5f-d51d462c9a4d', 1, 'Validation 1', 'approve', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, 0, '2026-04-22 12:13:01'),
('019db51b-a7b0-7251-9176-4cb3e654539f', '019db51b-a761-727b-8f5f-d51d462c9a4d', 2, 'Signature 1', 'sign', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, 1, '2026-04-22 12:13:01'),
('019db528-814c-733a-a313-66a503d77c24', '019db528-8126-727b-969b-4db0ff71d7bc', 1, 'Validation 1', 'approve', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, 0, '2026-04-22 12:27:03'),
('019db528-8157-706b-ae69-44a7ce3683b1', '019db528-8126-727b-969b-4db0ff71d7bc', 2, 'Signature 1', 'sign', '019d98ca-f8f0-7255-a7ec-eb5647937711', NULL, 1, '2026-04-22 12:27:03'),
('019db539-3f9d-71b7-8ab8-a4ec7eb4aa39', '019db539-3f71-7329-8e6f-c42660d3827d', 1, 'Validation 1', 'approve', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, 0, '2026-04-22 12:45:21'),
('019db539-3fa9-7107-b846-2efe901d2e1f', '019db539-3f71-7329-8e6f-c42660d3827d', 2, 'Signature 1', 'sign', '019d9e20-bfd8-7149-8260-e3b3be5d8c06', NULL, 1, '2026-04-22 12:45:21');

-- --------------------------------------------------------

--
-- Structure de la table `workflow_templates`
--

DROP TABLE IF EXISTS `workflow_templates`;
CREATE TABLE IF NOT EXISTS `workflow_templates` (
  `id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `administration_id` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(500) COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb3_unicode_ci,
  `validation_steps` json DEFAULT NULL,
  `signature_steps` json DEFAULT NULL,
  `notification_config` json DEFAULT NULL,
  `status` enum('active','archived') COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` char(36) COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_templates_administration_id_index` (`administration_id`),
  KEY `workflow_templates_created_by_index` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
