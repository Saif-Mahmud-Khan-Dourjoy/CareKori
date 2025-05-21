-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 21, 2025 at 11:49 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `carekori`
--

-- --------------------------------------------------------

--
-- Table structure for table `add_banners`
--

CREATE TABLE `add_banners` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `add_image` varchar(255) NOT NULL,
  `add_for` varchar(255) DEFAULT NULL,
  `add_type` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `appointment_time` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointment_slots`
--

CREATE TABLE `appointment_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `availability_id` bigint(20) UNSIGNED NOT NULL,
  `slot_time` time NOT NULL,
  `is_booked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `common_profiles`
--

CREATE TABLE `common_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `bio` text DEFAULT NULL,
  `pricing` decimal(8,2) DEFAULT NULL,
  `availability` tinyint(1) NOT NULL DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `thana` varchar(255) DEFAULT NULL,
  `identification_no` varchar(255) NOT NULL COMMENT 'nid, passport',
  `active_from` time DEFAULT NULL,
  `active_to` time DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `common_speciality_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `common_provider_specialities`
--

CREATE TABLE `common_provider_specialities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `specialized_at` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_profiles`
--

CREATE TABLE `customer_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `gender` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `district` varchar(255) NOT NULL,
  `sub_district` varchar(255) NOT NULL,
  `union_name` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_profiles`
--

CREATE TABLE `doctor_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `doctor_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `doctor_speciality_id` bigint(20) UNSIGNED DEFAULT NULL,
  `doctor_title_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `pricing` decimal(8,2) DEFAULT NULL,
  `availability` tinyint(1) NOT NULL DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `thana` varchar(255) DEFAULT NULL,
  `identification_no` varchar(255) NOT NULL COMMENT 'nid, passport',
  `registration_no` varchar(255) NOT NULL,
  `active_from` time DEFAULT NULL,
  `active_to` time DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_profiles`
--

INSERT INTO `doctor_profiles` (`id`, `user_id`, `doctor_type_id`, `doctor_speciality_id`, `doctor_title_id`, `bio`, `pricing`, `availability`, `avatar`, `gender`, `dob`, `district`, `thana`, `identification_no`, `registration_no`, `active_from`, `active_to`, `active_status`, `created_at`, `updated_at`) VALUES
(1, 23, 2, 2, 2, NULL, '1000.00', 1, 'http://127.0.0.1:8000/images/doctor/1747207069_23.png', 'female', '2025-05-06', 'Dhaka', 'Pallabi', '63453531', '34343234', NULL, NULL, 0, '2025-05-14 00:39:22', '2025-05-14 01:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_specialities`
--

CREATE TABLE `doctor_specialities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `specialized_at` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_specialities`
--

INSERT INTO `doctor_specialities` (`id`, `specialized_at`, `icon`, `created_at`, `updated_at`) VALUES
(2, 'Cardiologist 2', NULL, '2025-05-13 23:33:06', '2025-05-13 23:33:06');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_titles`
--

CREATE TABLE `doctor_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_titles`
--

INSERT INTO `doctor_titles` (`id`, `title`, `created_at`, `updated_at`) VALUES
(2, 'Professor', '2025-05-13 23:29:35', '2025-05-13 23:29:35');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_types`
--

CREATE TABLE `doctor_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_types`
--

INSERT INTO `doctor_types` (`id`, `type`, `created_at`, `updated_at`) VALUES
(2, 'Medical', '2025-05-13 23:19:31', '2025-05-13 23:19:31'),
(3, 'Medical 2', '2025-05-13 23:26:02', '2025-05-13 23:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `document_link` varchar(255) NOT NULL,
  `type` enum('verification','public','private') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `language_states`
--

CREATE TABLE `language_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `state` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lawyer_profiles`
--

CREATE TABLE `lawyer_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `lawyer_title_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `pricing` decimal(8,2) DEFAULT NULL,
  `availability` tinyint(1) NOT NULL DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `thana` varchar(255) DEFAULT NULL,
  `practice_area` varchar(255) DEFAULT NULL,
  `identification_no` varchar(255) NOT NULL COMMENT 'nid, passport',
  `bar_registration_no` varchar(255) NOT NULL,
  `active_from` time DEFAULT NULL,
  `active_to` time DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lawyer_speciality_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lawyer_specialities`
--

CREATE TABLE `lawyer_specialities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `specialized_at` varchar(255) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lawyer_titles`
--

CREATE TABLE `lawyer_titles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lawyer_titles`
--

INSERT INTO `lawyer_titles` (`id`, `title`, `created_at`, `updated_at`) VALUES
(1, 'Advocate', '2025-05-06 22:46:00', '2025-05-06 22:46:00');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_05_05_053846_create_roles_table', 1),
(2, '2014_10_12_000000_create_users_table', 2),
(3, '2014_10_12_100000_create_password_reset_tokens_table', 2),
(4, '2019_08_19_000000_create_failed_jobs_table', 2),
(5, '2019_12_14_000001_create_personal_access_tokens_table', 2),
(6, '2025_05_05_054636_create_customer_profiles_table', 2),
(7, '2025_05_05_055003_create_otp_codes_table', 2),
(9, '2025_05_05_163458_add_avatar_and_active_status_to_customer_profiles_table', 3),
(10, '2025_05_06_040902_create_moderator_profiles_table', 4),
(11, '2025_05_06_041548_create_moderator_profiles_table', 5),
(12, '2025_05_06_093430_create_doctor_types_table', 5),
(13, '2025_05_06_100153_create_doctor_specialities_table', 6),
(14, '2025_05_06_110600_create_doctor_titles_table', 7),
(15, '2025_05_06_110700_create_doctor_profiles_table', 7),
(16, '2025_05_07_031756_create_lawyer_titles_table', 8),
(17, '2025_05_07_031926_create_lawyer_profiles_table', 8),
(20, '2025_05_09_102536_create_common_profiles_table', 9),
(21, '2025_05_09_103027_create_unique_identifications_table', 9),
(22, '2025_05_10_175241_add_unique_user_id_to_users_table', 10),
(23, '2025_05_16_055028_create_wallets_table', 11),
(24, '2025_05_16_055439_create_service_provider_availabilities_table', 11),
(25, '2025_05_16_055538_create_appointments_table', 11),
(26, '2025_05_16_055556_create_appointment_slots_table', 11),
(27, '2025_05_19_044226_create_documents_table', 11),
(28, '2025_05_19_044242_create_private_documents_table', 11),
(29, '2025_05_19_061204_create_reviews_table', 11),
(30, '2025_05_19_095053_create_lawyer_specialities_table', 11),
(31, '2025_05_19_095408_add_lawyer_speciality_id_to_lawyer_profiles', 11),
(32, '2025_05_19_112146_create_add_banners_table', 12),
(33, '2025_05_20_135615_create_common_provider_specialities_table', 12),
(34, '2025_05_20_144050_add_common_speciality_id_to_common_profiles', 12),
(35, '2025_05_21_031819_make_foreign_keys_nullable_in_doctor_profiles_table', 12),
(36, '2025_05_21_032254_make_foreign_keys_nullable_in_lawyer_profiles_table', 12),
(37, '2025_05_21_033151_make_foreign_keys_nullable_in_common_profiles_table', 12),
(38, '2025_05_21_040804_add_icon_to_roles_table', 12),
(39, '2025_05_21_041448_add_icon_to_doctor_specialities_table', 12),
(40, '2025_05_21_041632_add_icon_to_lawyer_specialities_table', 12),
(41, '2025_05_21_041723_add_icon_to_common_provider_specialities_table', 12),
(42, '2025_05_21_063723_create_language_states_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `moderator_profiles`
--

CREATE TABLE `moderator_profiles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `moderator_profiles`
--

INSERT INTO `moderator_profiles` (`id`, `user_id`, `gender`, `dob`, `avatar`, `active_status`, `created_at`, `updated_at`) VALUES
(1, 19, NULL, NULL, NULL, 1, '2025-05-13 06:32:27', '2025-05-13 06:32:27');

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `phone` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `phone`, `code`, `is_verified`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, '01675332900', '4882', 1, '2025-05-13 10:22:17', '2025-05-05 03:41:47', '2025-05-13 04:22:17');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 1, 'carekori-token', 'c8b3b0a5c559af2bb73cc36c3a3af1d8341273c9e4e1176d1ffb4de1007b4827', '[\"*\"]', NULL, NULL, '2025-05-05 21:02:23', '2025-05-05 21:02:23'),
(2, 'App\\Models\\User', 1, 'carekori-token', '9ed4e19de41923b0fc029749b38a4d830a570fac875da60cab14e5417cdb4c2f', '[\"*\"]', NULL, NULL, '2025-05-05 21:18:47', '2025-05-05 21:18:47'),
(3, 'App\\Models\\User', 1, 'carekori-token', '0331ef8b2941e765f1f47cb31c92f33823c91c3b7ff87b477608e73753adae82', '[\"*\"]', NULL, NULL, '2025-05-05 21:21:26', '2025-05-05 21:21:26'),
(4, 'App\\Models\\User', 1, 'carekori-token', 'fd246bb6fe784a96b5e14cb036be3f220b1445edaf42579faab9a6042c685321', '[\"*\"]', NULL, NULL, '2025-05-05 21:42:48', '2025-05-05 21:42:48'),
(5, 'App\\Models\\User', 1, 'carekori-token', 'c1790628e2f4a18f443e342a5acdf7dfb759eced090a6019bf582d9e39ec5265', '[\"*\"]', '2025-05-13 04:34:54', NULL, '2025-05-05 21:50:44', '2025-05-13 04:34:54'),
(6, 'App\\Models\\User', 4, 'carekori-token', '93a0b522b326bf0be819ae039325010f5e8a9aa3b456c58ba3cf7d8daa7ca1d3', '[\"*\"]', NULL, NULL, '2025-05-06 00:24:37', '2025-05-06 00:24:37'),
(7, 'App\\Models\\User', 8, 'carekori-token', 'e5518bd8641e698c1bd19b7a3dadee4d46f7458796d549389e69bf3343de999a', '[\"*\"]', NULL, NULL, '2025-05-06 00:36:29', '2025-05-06 00:36:29'),
(8, 'App\\Models\\User', 1, 'carekori-token', 'a51e239a51d5749d49697fe65d072057a798ce4770b16ad35359f43cb65d247d', '[\"*\"]', '2025-05-06 03:11:22', NULL, '2025-05-06 00:39:14', '2025-05-06 03:11:22'),
(9, 'App\\Models\\User', 8, 'carekori-token', 'e7d17246e75bf7edff9b9a2c58608bba9ad89cb0e59b53db847d829c53edf446', '[\"*\"]', '2025-05-06 02:59:12', NULL, '2025-05-06 02:56:53', '2025-05-06 02:59:12'),
(10, 'App\\Models\\User', 1, 'carekori-token', '21df23d5979331d538a60ddc7765a2f38fe42e0756b688e340e75eb2a709f867', '[\"*\"]', '2025-05-13 23:33:05', NULL, '2025-05-06 03:10:38', '2025-05-13 23:33:05'),
(11, 'App\\Models\\User', 1, 'carekori-token', '4fd7bfafa61a973441f4accad4d36293c69548fa4d1e9e67d578afc534622b38', '[\"*\"]', NULL, NULL, '2025-05-06 14:24:08', '2025-05-06 14:24:08'),
(12, 'App\\Models\\User', 1, 'carekori-token', 'fbdf7688179770b07819bbc83c76ddd506490bb864c0be8758db42e2532acd63', '[\"*\"]', '2025-05-13 23:29:35', NULL, '2025-05-06 22:44:33', '2025-05-13 23:29:35'),
(13, 'App\\Models\\User', 11, 'carekori-token', 'f804169c8302d071719caa7a708fdcf05a5078c6a766369393b262628bd5668e', '[\"*\"]', NULL, NULL, '2025-05-06 22:51:35', '2025-05-06 22:51:35'),
(14, 'App\\Models\\User', 11, 'carekori-token', 'a9889def19c12d88e0ba8074056c5429127ae3d8ab06f23e4a62f10cc46f8f42', '[\"*\"]', NULL, NULL, '2025-05-06 22:52:08', '2025-05-06 22:52:08'),
(15, 'App\\Models\\User', 11, 'carekori-token', 'abe3b6c9440b8f121126b29c0c87d2408327e88fb91d76dadf0aeb372ff71ef0', '[\"*\"]', NULL, NULL, '2025-05-06 22:53:12', '2025-05-06 22:53:12'),
(16, 'App\\Models\\User', 11, 'carekori-token', 'a612e83f69fae50a9c91978db0da4c287b7c189fa0cc8f784ff7eb1799538191', '[\"*\"]', NULL, NULL, '2025-05-06 22:53:50', '2025-05-06 22:53:50'),
(17, 'App\\Models\\User', 1, 'carekori-token', '1c3c23517603119c270a552406937703494efc85c6ea2c880b63d86f3f3d834c', '[\"*\"]', NULL, NULL, '2025-05-07 04:51:26', '2025-05-07 04:51:26'),
(18, 'App\\Models\\User', 12, 'carekori-token', 'f2a375bf778737778408fbd7c2070cefe431406205286012aeb4a95d3025b39e', '[\"*\"]', NULL, NULL, '2025-05-08 10:30:33', '2025-05-08 10:30:33'),
(19, 'App\\Models\\User', 12, 'carekori-token', 'a8ca8430060b70cb6653cea0765531a245a098218cdcb1aeda076efd914133bd', '[\"*\"]', NULL, NULL, '2025-05-08 10:37:09', '2025-05-08 10:37:09'),
(20, 'App\\Models\\User', 12, 'carekori-token', '4ad9cfddf41e7122251f54783ac4d4fb7ae8e24e5abcfec82f6c69b351dd5009', '[\"*\"]', NULL, NULL, '2025-05-08 11:03:39', '2025-05-08 11:03:39'),
(21, 'App\\Models\\User', 13, 'carekori-token', 'f3ad27e9df89fd05c2dc568b9858576e5aecc3ec2d25fa88956c1986a7269b42', '[\"*\"]', NULL, NULL, '2025-05-09 00:20:58', '2025-05-09 00:20:58'),
(22, 'App\\Models\\User', 1, 'carekori-token', '1e18b332d3efc1ece6c524a4a3b46252dc66b8d6970b6728fae67c1289185232', '[\"*\"]', '2025-05-10 05:29:43', NULL, '2025-05-10 05:29:03', '2025-05-10 05:29:43'),
(23, 'App\\Models\\User', 17, 'carekori-token', 'a4225dd9c75404ac6bdf3f66e2d74433cd240fafabf64005b350659fbd9caf2f', '[\"*\"]', NULL, NULL, '2025-05-10 06:58:42', '2025-05-10 06:58:42'),
(24, 'App\\Models\\User', 17, 'carekori-token', '64202e59f0eacc10694e6a9e20cdcde0c46b9bbe9cdf1df85cf8968c1623906b', '[\"*\"]', NULL, NULL, '2025-05-10 07:30:56', '2025-05-10 07:30:56'),
(25, 'App\\Models\\User', 17, 'carekori-token', '0498016ddf151646cff4a3ccb35f75ce97a0d6fa18fe583673b78ad6959574b5', '[\"*\"]', NULL, NULL, '2025-05-10 07:42:20', '2025-05-10 07:42:20'),
(26, 'App\\Models\\User', 17, 'carekori-token', 'b2ac21a6160aa6e5cd08c55bea0e521951f78c008fbb7283492c3d597b8d552b', '[\"*\"]', NULL, NULL, '2025-05-10 07:44:37', '2025-05-10 07:44:37'),
(27, 'App\\Models\\User', 17, 'carekori-token', 'cf29e15641b089e9d3abacdd0955eda81457afa29f0a60ff04330ddc2add801c', '[\"*\"]', NULL, NULL, '2025-05-10 08:10:05', '2025-05-10 08:10:05'),
(28, 'App\\Models\\User', 17, 'carekori-token', '4de46d406fd76fa18572a1d42914f9bef97094997758bdd5b76ce00bcd78638e', '[\"*\"]', NULL, NULL, '2025-05-10 08:12:47', '2025-05-10 08:12:47'),
(29, 'App\\Models\\User', 17, 'carekori-token', '41663854ddaab3ce6ce8b3faded5f306c2ad51fdec7883fac119b983e8723e92', '[\"*\"]', NULL, NULL, '2025-05-10 08:13:33', '2025-05-10 08:13:33'),
(30, 'App\\Models\\User', 18, 'carekori-token', 'a0dd1e9f5ec8becd7dab78f33f8b92c977fd01f0bf234d97d1652124b1885b58', '[\"*\"]', NULL, NULL, '2025-05-10 21:52:11', '2025-05-10 21:52:11'),
(31, 'App\\Models\\User', 18, 'carekori-token', '8b7da22b8268fb32da3489fcd549105469ccbb8d3d2e06a62e658585eb8a0dc5', '[\"*\"]', '2025-05-10 22:07:21', NULL, '2025-05-10 22:04:50', '2025-05-10 22:07:21'),
(32, 'App\\Models\\User', 1, 'carekori-token', 'b756858e9bd1944465146605523db8f637dd76863c491337df3e2c7c7495a415', '[\"*\"]', '2025-05-13 23:42:17', NULL, '2025-05-10 22:07:58', '2025-05-13 23:42:17'),
(33, 'App\\Models\\User', 1, 'carekori-token', 'b3ee09583b101f2e244f3702a328890de7e3aa88978c1ab13037378211997c38', '[\"*\"]', '2025-05-14 01:24:48', NULL, '2025-05-13 04:26:11', '2025-05-14 01:24:48'),
(34, 'App\\Models\\User', 20, 'carekori-token', '4445e1217643653eec7798a8cd0118c1ec118e9f958bf9bbd81f820420b9df6e', '[\"*\"]', NULL, NULL, '2025-05-13 23:45:45', '2025-05-13 23:45:45'),
(35, 'App\\Models\\User', 1, 'carekori-token', 'f7fd26aff66ccbdc98ce1a945feb6437e3ee818b87ddcc5952769ce7da685f98', '[\"*\"]', NULL, NULL, '2025-05-13 23:47:12', '2025-05-13 23:47:12'),
(36, 'App\\Models\\User', 1, 'carekori-token', '928c6e685825bbd2cda74fb66733e8cec13712c4cf26cd6ba28f73cf2e4839e4', '[\"*\"]', '2025-05-14 00:35:13', NULL, '2025-05-13 23:49:08', '2025-05-14 00:35:13'),
(37, 'App\\Models\\User', 23, 'carekori-token', 'b658abf0502cdb271ad34aae9d9b4365b73a5b2b92eb5ddb3f4159c5758bbd8d', '[\"*\"]', NULL, NULL, '2025-05-14 00:39:22', '2025-05-14 00:39:22'),
(38, 'App\\Models\\User', 23, 'carekori-token', '6b0fd6184751e06607ef69655347a11c8a8ef681a9ec1f261e424130bfc68ef0', '[\"*\"]', '2025-05-14 00:50:25', NULL, '2025-05-14 00:40:06', '2025-05-14 00:50:25'),
(39, 'App\\Models\\User', 23, 'carekori-token', 'ba7985167cc6d19f1526c06ea798ceffc4559f61343de9d6bfb0dbe5a81e3b62', '[\"*\"]', '2025-05-14 01:24:55', NULL, '2025-05-14 00:56:13', '2025-05-14 01:24:55');

-- --------------------------------------------------------

--
-- Table structure for table `private_documents`
--

CREATE TABLE `private_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_for` bigint(20) UNSIGNED NOT NULL,
  `appointment_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `service_provider_id` bigint(20) UNSIGNED NOT NULL,
  `review` text DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `identification_placeholder` varchar(255) DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `identification_placeholder`, `icon`, `created_at`, `updated_at`) VALUES
(2, 'super admin', NULL, NULL, '2025-05-05 10:31:25', '2025-05-05 10:31:25'),
(3, 'moderator', NULL, NULL, '2025-05-05 21:56:28', '2025-05-05 21:56:28'),
(4, 'doctor', NULL, NULL, '2025-05-05 22:00:05', '2025-05-05 22:00:05'),
(5, 'customer', NULL, NULL, '2025-05-06 00:17:50', '2025-05-06 00:17:50'),
(6, 'lawyer', NULL, NULL, '2025-05-06 22:21:06', '2025-05-06 22:21:06');

-- --------------------------------------------------------

--
-- Table structure for table `service_provider_availabilities`
--

CREATE TABLE `service_provider_availabilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider_id` bigint(20) UNSIGNED NOT NULL,
  `availability_type` enum('appointment','instant_consultation') NOT NULL,
  `day` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) NOT NULL DEFAULT 10,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `unique_identifications`
--

CREATE TABLE `unique_identifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `common_profile_id` bigint(20) UNSIGNED NOT NULL,
  `unique_identification_no` varchar(255) NOT NULL,
  `other_data` varchar(1000) DEFAULT NULL COMMENT 'Json Data',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `unique_user_id` bigint(20) NOT NULL COMMENT 'Unique user ID for each user',
  `phone` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `name`, `email`, `unique_user_id`, `phone`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 2, 'Super Admin', 'superadmin@example.com', 564666675, '01671111111', '$2y$12$VBdliEe4qHhfuUOwy18wCu6uvxaiQPuoXJHqAsceq68G/TYMHC0bO', NULL, '2025-05-05 10:31:27', '2025-05-05 10:31:27'),
(19, 3, 'Moderator 2', NULL, 666759334, '01679999999', '$2y$12$HVt4.l5PzqpGkH9R8l9Ktefp3mjSWMleeOc/kFPB3q6jLpZqvgW3W', NULL, '2025-05-13 06:32:27', '2025-05-13 06:32:27'),
(23, 4, 'Doctor 1', NULL, 479183173, '01675555555', '$2y$12$sJZAuJ03aBsS8EHt8n7C9uXNMUYRkZfXBmTRDyqyY6FkldvUzsNzy', NULL, '2025-05-14 00:39:22', '2025-05-14 01:05:01');

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `add_banners`
--
ALTER TABLE `add_banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointments_customer_id_foreign` (`customer_id`),
  ADD KEY `appointments_provider_id_foreign` (`provider_id`);

--
-- Indexes for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_slots_availability_id_foreign` (`availability_id`);

--
-- Indexes for table `common_profiles`
--
ALTER TABLE `common_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `common_profiles_user_id_foreign` (`user_id`),
  ADD KEY `common_profiles_common_speciality_id_foreign` (`common_speciality_id`);

--
-- Indexes for table `common_provider_specialities`
--
ALTER TABLE `common_provider_specialities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `common_provider_specialities_specialized_at_unique` (`specialized_at`),
  ADD KEY `common_provider_specialities_category_id_foreign` (`category_id`);

--
-- Indexes for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_profiles_user_id_foreign` (`user_id`);

--
-- Indexes for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `doctor_profiles_user_id_foreign` (`user_id`),
  ADD KEY `doctor_profiles_doctor_type_id_foreign` (`doctor_type_id`),
  ADD KEY `doctor_profiles_doctor_speciality_id_foreign` (`doctor_speciality_id`),
  ADD KEY `doctor_profiles_doctor_title_id_foreign` (`doctor_title_id`);

--
-- Indexes for table `doctor_specialities`
--
ALTER TABLE `doctor_specialities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_specialities_specialized_at_unique` (`specialized_at`);

--
-- Indexes for table `doctor_titles`
--
ALTER TABLE `doctor_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_titles_title_unique` (`title`);

--
-- Indexes for table `doctor_types`
--
ALTER TABLE `doctor_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doctor_types_type_unique` (`type`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_user_id_foreign` (`user_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `language_states`
--
ALTER TABLE `language_states`
  ADD PRIMARY KEY (`id`),
  ADD KEY `language_states_user_id_foreign` (`user_id`);

--
-- Indexes for table `lawyer_profiles`
--
ALTER TABLE `lawyer_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lawyer_profiles_user_id_foreign` (`user_id`),
  ADD KEY `lawyer_profiles_lawyer_speciality_id_foreign` (`lawyer_speciality_id`),
  ADD KEY `lawyer_profiles_lawyer_title_id_foreign` (`lawyer_title_id`);

--
-- Indexes for table `lawyer_specialities`
--
ALTER TABLE `lawyer_specialities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lawyer_specialities_specialized_at_unique` (`specialized_at`);

--
-- Indexes for table `lawyer_titles`
--
ALTER TABLE `lawyer_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lawyer_titles_title_unique` (`title`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `moderator_profiles`
--
ALTER TABLE `moderator_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `moderator_profiles_user_id_foreign` (`user_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otp_codes_phone_index` (`phone`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `private_documents`
--
ALTER TABLE `private_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `private_documents_document_id_foreign` (`document_id`),
  ADD KEY `private_documents_created_by_foreign` (`created_by`),
  ADD KEY `private_documents_created_for_foreign` (`created_for`),
  ADD KEY `private_documents_appointment_id_foreign` (`appointment_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_customer_id_foreign` (`customer_id`),
  ADD KEY `reviews_service_provider_id_foreign` (`service_provider_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `service_provider_availabilities`
--
ALTER TABLE `service_provider_availabilities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_provider_availabilities_provider_id_foreign` (`provider_id`);

--
-- Indexes for table `unique_identifications`
--
ALTER TABLE `unique_identifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unique_identifications_common_profile_id_foreign` (`common_profile_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_phone_unique` (`phone`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_id_foreign` (`role_id`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallets_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `add_banners`
--
ALTER TABLE `add_banners`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `common_profiles`
--
ALTER TABLE `common_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `common_provider_specialities`
--
ALTER TABLE `common_provider_specialities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `doctor_specialities`
--
ALTER TABLE `doctor_specialities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_titles`
--
ALTER TABLE `doctor_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `doctor_types`
--
ALTER TABLE `doctor_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `language_states`
--
ALTER TABLE `language_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lawyer_profiles`
--
ALTER TABLE `lawyer_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lawyer_specialities`
--
ALTER TABLE `lawyer_specialities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lawyer_titles`
--
ALTER TABLE `lawyer_titles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `moderator_profiles`
--
ALTER TABLE `moderator_profiles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `private_documents`
--
ALTER TABLE `private_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service_provider_availabilities`
--
ALTER TABLE `service_provider_availabilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unique_identifications`
--
ALTER TABLE `unique_identifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointment_slots`
--
ALTER TABLE `appointment_slots`
  ADD CONSTRAINT `appointment_slots_availability_id_foreign` FOREIGN KEY (`availability_id`) REFERENCES `service_provider_availabilities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `common_profiles`
--
ALTER TABLE `common_profiles`
  ADD CONSTRAINT `common_profiles_common_speciality_id_foreign` FOREIGN KEY (`common_speciality_id`) REFERENCES `common_provider_specialities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `common_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `common_provider_specialities`
--
ALTER TABLE `common_provider_specialities`
  ADD CONSTRAINT `common_provider_specialities_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_profiles`
--
ALTER TABLE `customer_profiles`
  ADD CONSTRAINT `customer_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_profiles`
--
ALTER TABLE `doctor_profiles`
  ADD CONSTRAINT `doctor_profiles_doctor_speciality_id_foreign` FOREIGN KEY (`doctor_speciality_id`) REFERENCES `doctor_specialities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_profiles_doctor_title_id_foreign` FOREIGN KEY (`doctor_title_id`) REFERENCES `doctor_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_profiles_doctor_type_id_foreign` FOREIGN KEY (`doctor_type_id`) REFERENCES `doctor_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `language_states`
--
ALTER TABLE `language_states`
  ADD CONSTRAINT `language_states_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lawyer_profiles`
--
ALTER TABLE `lawyer_profiles`
  ADD CONSTRAINT `lawyer_profiles_lawyer_speciality_id_foreign` FOREIGN KEY (`lawyer_speciality_id`) REFERENCES `lawyer_specialities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lawyer_profiles_lawyer_title_id_foreign` FOREIGN KEY (`lawyer_title_id`) REFERENCES `lawyer_titles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lawyer_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `moderator_profiles`
--
ALTER TABLE `moderator_profiles`
  ADD CONSTRAINT `moderator_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `private_documents`
--
ALTER TABLE `private_documents`
  ADD CONSTRAINT `private_documents_appointment_id_foreign` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_documents_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_documents_created_for_foreign` FOREIGN KEY (`created_for`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `private_documents_document_id_foreign` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_service_provider_id_foreign` FOREIGN KEY (`service_provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_provider_availabilities`
--
ALTER TABLE `service_provider_availabilities`
  ADD CONSTRAINT `service_provider_availabilities_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unique_identifications`
--
ALTER TABLE `unique_identifications`
  ADD CONSTRAINT `unique_identifications_common_profile_id_foreign` FOREIGN KEY (`common_profile_id`) REFERENCES `common_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
