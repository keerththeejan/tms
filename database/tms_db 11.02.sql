-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 02, 2025 at 06:13 AM
-- Server version: 8.3.0
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `is_main`, `created_at`, `updated_at`) VALUES
(1, 'Kilinochchi', 'MAIN', 1, '2025-09-13 06:30:56', '2025-09-13 07:15:56'),
(2, 'Colombo', 'BR-A', 0, '2025-09-13 06:30:56', '2025-09-13 07:16:27'),
(3, 'mullaitivu', 'BR-B', 0, '2025-09-13 06:30:56', '2025-09-13 07:16:47');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `delivery_location` varchar(120) DEFAULT NULL,
  `place_id` varchar(191) DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `customer_type` enum('regular','corporate') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_customers_phone` (`phone`),
  KEY `idx_customers_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=98 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `delivery_location`, `place_id`, `lat`, `lng`, `customer_type`, `created_at`, `updated_at`) VALUES
(1, 'user user', '0778870135', NULL, 'Kilinochchi', 'kili', NULL, NULL, NULL, 'regular', '2025-09-13 07:12:54', '2025-09-13 08:53:55'),
(3, 'hh', '0798645352', NULL, 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'corporate', '2025-09-13 09:09:55', '2025-09-24 12:32:10'),
(4, 'yathu', '0765489736', NULL, 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-14 05:32:05', '2025-09-24 12:31:58'),
(5, 'moon', '0773859464', NULL, 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-09-15 15:24:00', '2025-09-15 15:24:00'),
(6, 'kujin', '0770000000', NULL, 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-20 05:30:44', '2025-09-24 12:31:52'),
(7, 'lux', '12345654589', NULL, 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-24 12:07:43', '2025-09-24 12:07:43'),
(8, 'yathu', '6546444433225678', NULL, 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-09-26 05:29:58', '2025-09-26 05:29:58'),
(11, 'yathuy', '0707234561', NULL, 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'corporate', '2025-09-26 05:35:20', '2025-10-01 12:01:03'),
(24, 'moonuuuuuuu', '0770000009', NULL, 'murasumoddai', 'paranthan', NULL, NULL, NULL, 'regular', '2025-09-30 06:39:54', '2025-10-01 12:00:56'),
(25, 'yathu', '75585859955', NULL, 'murasumoddai', 'fghj', NULL, NULL, NULL, 'regular', '2025-10-01 07:39:04', '2025-10-01 12:00:49'),
(29, 'Green Logistics', '0759988776', NULL, '88, High Level Rd, Galle', 'Galle', 'place004', 6.0535190, 80.2209780, 'corporate', '2025-10-07 05:36:53', '2025-10-07 05:36:53'),
(43, 'Anitha Enterprises', '0761060648', 'yathunila2001@gmail.com', '45, Market Road, Kandy', 'Kandy', 'place002', 7.2905720, 80.6337280, 'corporate', '2025-10-07 05:47:29', '2025-10-07 05:47:29'),
(44, 'Suresh & Co', '0712395678', 'sureshco@yahoo.com', '22, Station Road, Jaffna', 'Jaffna', 'place003', 9.6614980, 80.0255130, 'regular', '2025-10-07 05:47:29', '2025-10-07 05:47:29'),
(46, 'Kumar Transport', '0784485667', 'kumar.trans@gmail.com', '10, Temple Road, Batticaloa', 'Batticaloa', 'place005', 7.7101880, 81.6923960, 'regular', '2025-10-07 05:47:29', '2025-10-07 05:47:29'),
(47, 'Royal Distributors', '0765541332', 'royal.distributors@gmail.com', '55, Market Street, Trincomalee', 'Trincomalee', 'place006', 8.5711000, 81.2335000, 'corporate', '2025-10-07 05:47:29', '2025-10-07 05:47:29'),
(49, 'nilalux', '564343746734', NULL, 'jaffna', 'jaffna', NULL, NULL, NULL, NULL, '2025-10-13 05:53:03', '2025-10-13 05:53:03'),
(50, 'nilani', '45678987', NULL, 'murasumoddai', 'Jaffna', NULL, NULL, NULL, 'regular', '2025-10-13 05:54:02', '2025-10-13 05:54:02'),
(51, 'mooooooonuuiii', '5767878799', NULL, 'murasumoddai', 'Galle', NULL, NULL, NULL, NULL, '2025-10-13 05:55:12', '2025-10-13 05:55:12'),
(52, 'kumar', '7878778888', NULL, 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 06:03:04', '2025-10-13 06:03:04'),
(53, 'kannan', '5465898609', NULL, 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 06:11:41', '2025-10-13 06:11:41'),
(54, 'nuy', '54678900', NULL, 'murasumoddai', 'Kandy', NULL, NULL, NULL, NULL, '2025-10-13 06:13:19', '2025-10-13 06:13:19'),
(55, 'zooooooooooo', '978888888888888', NULL, 'murasumoddai', 'Galle', NULL, NULL, NULL, 'corporate', '2025-10-13 06:14:09', '2025-10-13 06:14:09'),
(56, 'yathu', '4567890-', NULL, 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 06:17:04', '2025-10-13 06:17:04'),
(57, 'yathu', '6587689', NULL, 'murasumoddai', 'Batticaloa', NULL, NULL, NULL, 'regular', '2025-10-13 06:17:42', '2025-10-13 06:17:42'),
(58, 'nnnnnnnnnnn', '67890', NULL, 'murasumoddai', 'Galle', NULL, NULL, NULL, NULL, '2025-10-13 06:18:13', '2025-10-13 06:18:13'),
(59, 'nnnnnnnnnnnnnnnnnn', '4567890-987', 'yathunila2001@gmail.com', 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 06:23:07', '2025-10-13 06:23:07'),
(60, 'yathu', '5555555554', 'yathu2001@gmail.com', 'murasumoddai', 'Batticaloa', NULL, NULL, NULL, 'regular', '2025-10-13 06:39:45', '2025-10-13 06:39:45'),
(61, 'nilamoon', '09875645247', 'yathunila2001@gmail.com', 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 06:53:53', '2025-10-13 06:53:53'),
(62, 'nuyyyyyyyyyyyyyyyyyyyye', '76475845784', 'yathunila2001@gmail.com', 'murasumoddai', 'Batticaloa', NULL, NULL, NULL, 'regular', '2025-10-13 06:54:45', '2025-10-13 06:54:45'),
(68, 'yathu', '676768768888', 'yathunila2001@gmail.com', 'murasumoddai', 'jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 07:08:20', '2025-10-13 07:08:20'),
(69, 'mooooonuy', '757587485475', 'yathunila2001@gmail.com', 'murasumoddai', 'Batticaloa', NULL, NULL, NULL, 'corporate', '2025-10-13 07:09:05', '2025-10-13 07:09:05'),
(74, 'yathu', '7745634534', 'yathunila2001@gmail.com', 'murasumoddai', 'Jaffna', NULL, NULL, NULL, 'corporate', '2025-10-13 10:28:32', '2025-10-13 10:28:32'),
(75, 'yathunila', '454536455', 'yathunila2001@gmail.com', 'murasumoddai', 'Kandy', NULL, NULL, NULL, 'corporate', '2025-10-13 10:28:53', '2025-10-13 10:28:53'),
(78, 'keerththi', '0778870138', 'keerththeejan@gmail.com', 'iranamadu', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-10-18 09:31:56', '2025-10-18 09:31:56'),
(79, 'yathu', '0770000006', 'yathunila2001@gmail.com', 'murasumoddai', 'paranthan', NULL, NULL, NULL, 'regular', '2025-10-18 12:54:52', '2025-10-18 12:54:52'),
(80, 'yathu', '0987654325', 'yathunila2002@gmail.com', 'murasumoddai', 'Kandy', NULL, NULL, NULL, 'regular', '2025-10-18 12:55:31', '2025-10-18 12:55:31'),
(81, 'angel', '0770000008', 'yathunila2002@gmail.com', 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-10-18 12:56:57', '2025-10-18 12:56:57'),
(82, 'yathu', 'yugi79ui', 'yathunila2001@gmail.com', 'murasumoddai', 'paranthan', NULL, NULL, NULL, 'corporate', '2025-10-29 08:20:44', '2025-10-29 08:20:44'),
(83, 'nilanika', 'NA1761726597-603', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 08:29:57', '2025-10-29 08:29:57'),
(84, 'ajithaa', '0761089897', NULL, '', 'bvdbfgdhh', NULL, NULL, NULL, NULL, '2025-10-29 08:30:42', '2025-11-02 05:31:29'),
(85, 'moyu', 'NA1761727083-104', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 08:38:03', '2025-10-29 08:38:03'),
(86, 'niloo', '', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 08:46:19', '2025-10-29 08:46:19'),
(93, 'qwertyu', 'NA1761728418-825', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 09:00:18', '2025-10-29 09:00:18'),
(94, 'qazwse', 'NA1761728552-519', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 09:02:32', '2025-10-29 09:02:32'),
(95, 'mithu', 'NA1761728774-337', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 09:06:14', '2025-10-29 09:06:14'),
(96, 'thenu', 'NA1761728832-059', NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 09:07:12', '2025-10-29 09:07:12'),
(97, 'mithumi', NULL, NULL, '', '', NULL, NULL, NULL, NULL, '2025-10-29 10:04:59', '2025-10-29 10:04:59');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_notes`
--

DROP TABLE IF EXISTS `delivery_notes`;
CREATE TABLE IF NOT EXISTS `delivery_notes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `delivery_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_email_status` varchar(10) DEFAULT NULL,
  `last_emailed_at` datetime DEFAULT NULL,
  `last_email_subject` varchar(255) DEFAULT NULL,
  `last_email_text` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dn_customer_date_branch` (`customer_id`,`delivery_date`,`branch_id`),
  KEY `fk_dn_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_notes`
--

INSERT INTO `delivery_notes` (`id`, `customer_id`, `branch_id`, `delivery_date`, `total_amount`, `discount`, `created_at`, `updated_at`, `last_email_status`, `last_emailed_at`, `last_email_subject`, `last_email_text`) VALUES
(1, 1, 1, '2025-09-13', 15000.00, 0.00, '2025-09-13 07:13:07', '2025-09-15 15:47:03', NULL, NULL, NULL, NULL),
(2, 1, 1, '2025-09-15', 5000.00, 0.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29', NULL, NULL, NULL, NULL),
(3, 1, 1, '2025-09-16', 7500.50, 0.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29', NULL, NULL, NULL, NULL),
(4, 1, 1, '2025-09-17', 12000.00, 0.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29', NULL, NULL, NULL, NULL),
(5, 1, 1, '2025-09-18', 2500.75, 0.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29', NULL, NULL, NULL, NULL),
(6, 1, 1, '2025-09-19', 9999.99, 0.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29', NULL, NULL, NULL, NULL),
(9, 6, 1, '2025-09-20', 0.00, 0.00, '2025-09-20 05:35:58', '2025-09-20 05:35:58', NULL, NULL, NULL, NULL),
(10, 3, 1, '2025-09-24', 702.00, 0.00, '2025-09-24 06:33:07', '2025-09-24 12:25:59', NULL, NULL, NULL, NULL),
(11, 3, 1, '2025-09-17', 535620.00, 0.00, '2025-09-24 06:35:17', '2025-09-24 06:35:17', NULL, NULL, NULL, NULL),
(12, 7, 1, '2025-09-24', 0.00, 0.00, '2025-09-24 12:08:56', '2025-09-24 12:08:56', NULL, NULL, NULL, NULL),
(13, 6, 1, '2025-09-24', 0.00, 0.00, '2025-09-24 12:17:38', '2025-09-24 12:17:38', NULL, NULL, NULL, NULL),
(14, 5, 1, '2025-09-24', 3990.00, 0.00, '2025-09-24 12:30:30', '2025-09-24 12:30:31', NULL, NULL, NULL, NULL),
(15, 1, 1, '2025-09-24', 0.00, 0.00, '2025-09-24 12:44:19', '2025-09-24 12:44:19', NULL, NULL, NULL, NULL),
(16, 4, 1, '2025-09-16', 2363.00, 0.00, '2025-09-24 12:45:11', '2025-09-24 12:45:11', NULL, NULL, NULL, NULL),
(17, 1, 1, '2025-09-29', 8666.00, 0.00, '2025-09-29 05:43:58', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(18, 7, 1, '2025-09-29', 78.00, 0.00, '2025-09-29 05:45:52', '2025-09-29 05:45:52', NULL, NULL, NULL, NULL),
(19, 4, 1, '2025-09-29', 9999999999.99, 0.00, '2025-09-29 05:46:41', '2025-09-29 05:46:41', NULL, NULL, NULL, NULL),
(20, 8, 1, '2025-09-29', 0.00, 0.00, '2025-09-29 05:46:49', '2025-09-29 05:46:49', NULL, NULL, NULL, NULL),
(21, 3, 1, '2025-09-29', 751167846.00, 0.00, '2025-09-29 05:47:02', '2025-09-29 05:47:02', NULL, NULL, NULL, NULL),
(22, 4, 1, '2025-09-30', 0.00, 0.00, '2025-09-30 04:42:58', '2025-09-30 04:42:58', NULL, NULL, NULL, NULL),
(23, 1, 1, '2025-09-30', 0.00, 0.00, '2025-09-30 04:43:09', '2025-09-30 04:43:09', NULL, NULL, NULL, NULL),
(24, 6, 1, '2025-09-30', 10037825.00, 0.00, '2025-09-30 04:44:22', '2025-09-30 04:44:22', NULL, NULL, NULL, NULL),
(25, 8, 1, '2025-09-30', 0.00, 0.00, '2025-09-30 06:54:32', '2025-09-30 06:54:32', NULL, NULL, NULL, NULL),
(26, 7, 1, '2025-09-30', 0.00, 0.00, '2025-09-30 06:57:24', '2025-09-30 06:57:24', NULL, NULL, NULL, NULL),
(27, 7, 1, '2025-10-01', 2118713.00, 0.00, '2025-10-01 12:44:50', '2025-10-06 09:11:24', NULL, NULL, NULL, NULL),
(28, 7, 1, '2025-10-04', 7200.00, 0.00, '2025-10-04 09:12:57', '2025-10-14 13:41:01', NULL, NULL, NULL, NULL),
(29, 5, 1, '2025-10-04', 540.00, 0.00, '2025-10-04 09:13:27', '2025-10-04 09:13:27', NULL, NULL, NULL, NULL),
(30, 6, 1, '2025-10-04', 53583.00, 0.00, '2025-10-04 09:16:39', '2025-10-04 09:16:39', NULL, NULL, NULL, NULL),
(31, 11, 1, '2025-10-04', 680.00, 0.00, '2025-10-04 09:16:47', '2025-10-04 09:16:47', NULL, NULL, NULL, NULL),
(32, 6, 1, '2025-10-05', 5899.00, 0.00, '2025-10-05 13:58:33', '2025-10-11 06:26:21', NULL, NULL, NULL, NULL),
(33, 7, 1, '2025-10-05', 56.00, 3.00, '2025-10-05 13:58:53', '2025-10-11 13:46:26', NULL, NULL, NULL, NULL),
(34, 29, 1, '2025-10-11', 78210.00, 0.00, '2025-10-11 05:14:03', '2025-10-11 05:14:03', NULL, NULL, NULL, NULL),
(41, 24, 1, '2025-10-11', 660.00, -22.00, '2025-10-11 12:30:24', '2025-10-11 13:45:07', NULL, NULL, NULL, NULL),
(44, 46, 1, '2025-10-11', 467152.00, 90.00, '2025-10-11 14:39:01', '2025-10-11 14:49:57', NULL, NULL, NULL, NULL),
(46, 3, 1, '2025-10-11', 5208.00, 90.00, '2025-10-11 14:52:27', '2025-10-11 14:52:45', NULL, NULL, NULL, NULL),
(52, 11, 1, '2025-10-13', 45493.00, 0.00, '2025-10-13 06:41:02', '2025-10-13 06:41:02', NULL, NULL, NULL, NULL),
(66, 43, 1, '2025-10-13', 932980.00, -980.00, '2025-10-13 10:01:07', '2025-10-16 07:10:26', NULL, NULL, NULL, NULL),
(68, 25, 1, '2025-10-13', 6312.00, 0.00, '2025-10-13 10:25:24', '2025-10-13 10:25:25', NULL, NULL, NULL, NULL),
(69, 75, 1, '2025-10-13', 512339.05, -900.00, '2025-10-13 10:30:19', '2025-10-13 10:30:45', NULL, NULL, NULL, NULL),
(70, 6, 1, '2025-10-13', 47905.00, -905.00, '2025-10-13 10:33:31', '2025-10-16 07:07:17', NULL, NULL, NULL, NULL),
(71, 53, 1, '2025-10-13', 72000.00, 0.00, '2025-10-13 12:42:33', '2025-10-20 05:55:17', 'sent', '2025-10-20 11:25:17', 'Delivery Note #71 — 72,000.00', 'Delivery Note #71Customer: kannanDate: 2025-10-13ParcelAmount#10872,000.00Net72,000.00'),
(73, 75, 1, '2025-10-14', 133700.00, 789.00, '2025-10-14 02:24:49', '2025-10-20 05:54:27', 'sent', '2025-10-20 11:24:27', 'Delivery Note #73 — 134,489.00', 'Delivery Note #73Customer: yathunilaDate: 2025-10-14ParcelAmount#115133,700.00Net134,489.00'),
(74, 43, 1, '2025-10-14', 1440.00, -440.00, '2025-10-14 12:39:51', '2025-10-29 05:18:54', 'sent', '2025-10-15 16:55:24', NULL, NULL),
(75, 43, 1, '2025-10-15', 142388.00, 388.00, '2025-10-15 11:04:25', '2025-10-16 07:03:48', 'sent', '2025-10-15 17:17:36', NULL, NULL),
(76, 53, 1, '2025-10-15', 6400.00, -400.00, '2025-10-15 11:48:55', '2025-10-16 05:37:34', NULL, NULL, NULL, NULL),
(77, 52, 1, '2025-10-15', 7200.00, -70.00, '2025-10-15 11:55:50', '2025-10-20 05:53:58', 'sent', '2025-10-20 11:23:58', 'Delivery Note #77 — 7,130.00', 'Delivery Note #77Customer: kumarDate: 2025-10-15ParcelAmount#1267,200.00Net7,130.00'),
(78, 43, 3, '2025-10-15', 0.00, 0.00, '2025-10-15 12:48:30', '2025-10-15 12:48:30', NULL, NULL, NULL, NULL),
(79, 7, 1, '2025-10-18', 525418608.00, 0.00, '2025-10-18 05:58:27', '2025-10-18 05:58:53', 'sent', '2025-10-18 11:28:53', NULL, NULL),
(80, 78, 1, '2025-10-18', 3200.00, -200.00, '2025-10-18 10:23:19', '2025-10-18 10:24:48', 'sent', '2025-10-18 15:53:30', NULL, NULL),
(81, 43, 1, '2025-10-18', 3710.00, -360.00, '2025-10-18 12:43:11', '2025-10-18 12:52:23', 'sent', '2025-10-18 18:21:50', NULL, NULL),
(82, 81, 1, '2025-10-18', 1600.00, -800.00, '2025-10-18 12:59:15', '2025-10-29 06:04:35', 'sent', '2025-10-18 18:29:21', NULL, NULL),
(85, 43, 1, '2025-10-29', 623000.00, -5000.00, '2025-10-29 05:36:45', '2025-10-29 05:50:48', 'sent', '2025-10-29 11:06:51', 'Delivery Note #85 — 623,000.00', 'Delivery Note #85\\nTotal: 623,000.00'),
(86, 29, 1, '2025-10-29', 1350.00, -350.00, '2025-10-29 06:05:10', '2025-10-29 06:05:44', NULL, NULL, NULL, NULL),
(87, 6, 1, '2025-10-29', 64000.00, -7000.00, '2025-10-29 06:12:03', '2025-10-29 06:35:39', NULL, NULL, NULL, NULL),
(88, 84, 1, '2025-10-29', 61285.49, -285.00, '2025-10-29 10:05:57', '2025-10-29 10:06:29', NULL, NULL, NULL, NULL),
(89, 81, 1, '2025-10-29', 10680.00, -9000.00, '2025-10-29 10:14:51', '2025-10-29 13:04:43', 'sent', '2025-10-29 15:44:56', 'Delivery Note #89 — 10,680.00', 'Delivery Note #89\\nTotal: 10,680.00'),
(90, 97, 1, '2025-10-29', 3393.35, -393.00, '2025-10-29 13:26:19', '2025-10-29 13:50:12', NULL, NULL, NULL, NULL),
(91, 84, 1, '2025-11-02', 0.00, 0.00, '2025-11-02 05:40:02', '2025-11-02 05:40:02', NULL, NULL, NULL, NULL),
(92, 25, 1, '2025-11-02', 0.00, 0.00, '2025-11-02 05:56:30', '2025-11-02 05:56:30', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_note_emails`
--

DROP TABLE IF EXISTS `delivery_note_emails`;
CREATE TABLE IF NOT EXISTS `delivery_note_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint UNSIGNED NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext,
  `text_body` mediumtext,
  `status` enum('sent','failed') NOT NULL,
  `error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dn` (`delivery_note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_note_emails`
--

INSERT INTO `delivery_note_emails` (`id`, `delivery_note_id`, `to_email`, `subject`, `html_body`, `text_body`, `status`, `error`, `created_at`) VALUES
(1, 77, 'yathunila2001@gmail.com', 'Delivery Note #77 — 7,130.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #77</h3><div style=\"color:#555;margin:0 0 6px;\">Customer: kumar</div><div style=\"color:#555;margin:0 12px 12px 0;\">Date: 2025-10-15</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody><tr><td>#126</td><td class=\"text-end\">7,200.00</td></tr></tbody><tfoot><tr><th class=\"text-end\">Net</th><th class=\"text-end\">7,130.00</th></tr></tfoot></table></div>', 'Delivery Note #77Customer: kumarDate: 2025-10-15ParcelAmount#1267,200.00Net7,130.00', 'sent', NULL, '2025-10-20 05:53:48'),
(2, 77, 'yathunila2001@gmail.com', 'Delivery Note #77 — 7,130.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #77</h3><div style=\"color:#555;margin:0 0 6px;\">Customer: kumar</div><div style=\"color:#555;margin:0 12px 12px 0;\">Date: 2025-10-15</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody><tr><td>#126</td><td class=\"text-end\">7,200.00</td></tr></tbody><tfoot><tr><th class=\"text-end\">Net</th><th class=\"text-end\">7,130.00</th></tr></tfoot></table></div>', 'Delivery Note #77Customer: kumarDate: 2025-10-15ParcelAmount#1267,200.00Net7,130.00', 'sent', NULL, '2025-10-20 05:53:53'),
(3, 77, 'yathunila2001@gmail.com', 'Delivery Note #77 — 7,130.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #77</h3><div style=\"color:#555;margin:0 0 6px;\">Customer: kumar</div><div style=\"color:#555;margin:0 12px 12px 0;\">Date: 2025-10-15</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody><tr><td>#126</td><td class=\"text-end\">7,200.00</td></tr></tbody><tfoot><tr><th class=\"text-end\">Net</th><th class=\"text-end\">7,130.00</th></tr></tfoot></table></div>', 'Delivery Note #77Customer: kumarDate: 2025-10-15ParcelAmount#1267,200.00Net7,130.00', 'sent', NULL, '2025-10-20 05:53:58'),
(4, 73, 'yathunila2001@gmail.com', 'Delivery Note #73 — 134,489.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #73</h3><div style=\"color:#555;margin:0 0 6px;\">Customer: yathunila</div><div style=\"color:#555;margin:0 12px 12px 0;\">Date: 2025-10-14</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody><tr><td>#115</td><td class=\"text-end\">133,700.00</td></tr></tbody><tfoot><tr><th class=\"text-end\">Net</th><th class=\"text-end\">134,489.00</th></tr></tfoot></table></div>', 'Delivery Note #73Customer: yathunilaDate: 2025-10-14ParcelAmount#115133,700.00Net134,489.00', 'sent', NULL, '2025-10-20 05:54:27'),
(5, 71, 'yathunila2001@gmail.com', 'Delivery Note #71 — 72,000.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #71</h3><div style=\"color:#555;margin:0 0 6px;\">Customer: kannan</div><div style=\"color:#555;margin:0 12px 12px 0;\">Date: 2025-10-13</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Amount</th></tr></thead><tbody><tr><td>#108</td><td class=\"text-end\">72,000.00</td></tr></tbody><tfoot><tr><th class=\"text-end\">Net</th><th class=\"text-end\">72,000.00</th></tr></tfoot></table></div>', 'Delivery Note #71Customer: kannanDate: 2025-10-13ParcelAmount#10872,000.00Net72,000.00', 'sent', NULL, '2025-10-20 05:55:17'),
(13, 85, 'yathunila2001@gmail.com', 'Delivery Note #85 — 623,000.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #85</h3><div style=\"margin:0 0 6px;color:#555;\">Branch: Kilinochchi</div><div style=\"margin:0 0 12px;color:#555;\">Customer: Anitha Enterprises</div><table cellspacing=\"0\" cellpadding=\"6\" border=\"1\" style=\"border-collapse:collapse;width:100%;\"><thead style=\"background:#f1f1f1;\"><tr><th align=\"left\">Parcel</th><th align=\"right\">Amount</th></tr></thead><tbody><tr><td>#127</td><td class=\"text-end\">623,000.00</td></tr></tbody><tfoot><tr style=\"background:#fafafa;\"><td align=\"right\"><strong>Total</strong></td><td align=\"right\"><strong>623,000.00</strong></td></tr><tr><td align=\"right\">Paid</td><td align=\"right\">0.00</td></tr><tr><td align=\"right\">Due</td><td align=\"right\">623,000.00</td></tr></tfoot></table></div>', 'Delivery Note #85\\nTotal: 623,000.00', 'sent', NULL, '2025-10-29 05:36:51'),
(14, 89, 'yathunila2002@gmail.com', 'Delivery Note #89 — 10,680.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Delivery Note #89</h3><div style=\"margin:0 0 6px;color:#555;\">Branch: Kilinochchi</div><div style=\"margin:0 0 12px;color:#555;\">Customer: angel</div><table cellspacing=\"0\" cellpadding=\"6\" border=\"1\" style=\"border-collapse:collapse;width:100%;\"><thead style=\"background:#f1f1f1;\"><tr><th align=\"left\">Parcel</th><th align=\"right\">Amount</th></tr></thead><tbody><tr><td>#138</td><td class=\"text-end\">5,340.00</td></tr><tr><td>#139</td><td class=\"text-end\">5,340.00</td></tr></tbody><tfoot><tr style=\"background:#fafafa;\"><td align=\"right\"><strong>Total</strong></td><td align=\"right\"><strong>10,680.00</strong></td></tr><tr><td align=\"right\">Paid</td><td align=\"right\">0.00</td></tr><tr><td align=\"right\">Due</td><td align=\"right\">10,680.00</td></tr></tfoot></table></div>', 'Delivery Note #89\\nTotal: 10,680.00', 'sent', NULL, '2025-10-29 10:14:56');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_note_parcels`
--

DROP TABLE IF EXISTS `delivery_note_parcels`;
CREATE TABLE IF NOT EXISTS `delivery_note_parcels` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint UNSIGNED NOT NULL,
  `parcel_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `parcel_id` (`parcel_id`),
  KEY `idx_dnp_dn` (`delivery_note_id`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_note_parcels`
--

INSERT INTO `delivery_note_parcels` (`id`, `delivery_note_id`, `parcel_id`, `amount`) VALUES
(1, 11, 43, 535620.00),
(2, 10, 38, 702.00),
(3, 14, 44, 3990.00),
(6, 16, 26, 2363.00),
(8, 16, 36, 0.00),
(9, 17, 49, 500.00),
(10, 17, 50, 750.00),
(11, 17, 51, 600.00),
(12, 17, 52, 820.00),
(13, 17, 53, 450.00),
(14, 17, 54, 500.00),
(15, 17, 55, 400.00),
(16, 17, 56, 1000.00),
(17, 17, 57, 350.00),
(18, 17, 58, 520.00),
(19, 17, 59, 300.00),
(20, 17, 60, 1200.00),
(21, 17, 61, 475.00),
(22, 17, 62, 221.00),
(23, 17, 63, 580.00),
(24, 18, 65, 78.00),
(25, 19, 69, 9999999999.99),
(26, 19, 74, 69420.00),
(27, 21, 37, 0.00),
(28, 21, 39, 0.00),
(29, 21, 40, 0.00),
(30, 21, 66, 61542.00),
(31, 21, 71, 751106304.00),
(32, 24, 67, 71289.00),
(33, 24, 68, 90000.00),
(34, 24, 73, 9876536.00),
(35, 27, 78, 623.00),
(36, 27, 82, 2118090.00),
(38, 29, 79, 540.00),
(39, 30, 77, 52863.00),
(40, 30, 81, 720.00),
(41, 31, 64, 680.00),
(42, 31, 76, 0.00),
(43, 33, 86, 56.00),
(44, 34, 98, 78210.00),
(46, 32, 92, 5899.00),
(48, 41, 87, 630.00),
(53, 44, 105, 233576.00),
(54, 44, 106, 233576.00),
(55, 46, 104, 4578.00),
(56, 46, 107, 630.00),
(57, 52, 109, 45493.00),
(58, 66, 93, 789.00),
(59, 66, 94, 810.00),
(60, 66, 110, 13041.00),
(61, 66, 111, 871000.00),
(62, 66, 112, 47340.00),
(65, 68, 102, 6312.00),
(66, 69, 114, 512339.05),
(67, 70, 45, 35505.00),
(68, 70, 97, 6170.00),
(69, 70, 101, 6230.00),
(70, 71, 108, 72000.00),
(71, 73, 115, 133700.00),
(72, 74, 116, 720.00),
(73, 74, 117, 720.00),
(74, 28, 121, 7200.00),
(75, 75, 120, 468.00),
(76, 75, 122, 3950.00),
(77, 75, 118, 936.00),
(78, 75, 119, 137034.00),
(80, 76, 124, 6400.00),
(81, 77, 126, 7200.00),
(82, 79, 130, 525418608.00),
(83, 80, 131, 3200.00),
(84, 81, 129, 1350.00),
(85, 81, 132, 1000.00),
(86, 81, 133, 1360.00),
(87, 82, 134, 1600.00),
(88, 85, 127, 623000.00),
(89, 86, 128, 1350.00),
(90, 87, 125, 64000.00),
(91, 88, 140, 61285.49),
(92, 89, 138, 5340.00),
(93, 89, 139, 5340.00),
(94, 90, 141, 3393.35);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_route_assignments`
--

DROP TABLE IF EXISTS `delivery_route_assignments`;
CREATE TABLE IF NOT EXISTS `delivery_route_assignments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `delivery_date` date NOT NULL,
  `vehicle_no` varchar(60) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_customer_branch_date` (`customer_id`,`branch_id`,`delivery_date`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_route_assignments`
--

INSERT INTO `delivery_route_assignments` (`id`, `customer_id`, `branch_id`, `delivery_date`, `vehicle_no`, `updated_at`) VALUES
(1, 46, 1, '2025-10-11', '890', '2025-10-11 12:26:40'),
(2, 47, 1, '2025-10-11', '345', '2025-10-11 12:30:03'),
(3, 43, 1, '2025-10-11', '7854', '2025-10-11 12:55:05'),
(4, 25, 1, '2025-10-11', '890', '2025-10-11 13:08:47'),
(5, 29, 1, '2025-10-11', '843', '2025-10-11 13:52:08'),
(6, 7, 1, '2025-10-11', '754', '2025-10-11 13:57:07'),
(7, 5, 1, '2025-10-11', '876', '2025-10-11 13:57:25'),
(8, 3, 1, '2025-10-11', '678', '2025-10-11 14:52:15'),
(9, 6, 1, '2025-10-11', '879', '2025-10-11 14:06:17'),
(10, 24, 1, '2025-10-11', '54323', '2025-10-11 14:08:54'),
(12, 3, 1, '2025-10-12', '789', '2025-10-12 13:55:41'),
(13, 53, 1, '2025-10-13', '879', '2025-10-13 06:36:47'),
(16, 11, 1, '2025-10-13', '765', '2025-10-13 06:40:57'),
(17, 43, 1, '2025-10-13', '667', '2025-10-13 06:58:56'),
(20, 69, 1, '2025-10-13', '890', '2025-10-13 08:45:44'),
(21, 62, 1, '2025-10-13', '789', '2025-10-13 07:51:55'),
(23, 75, 1, '2025-10-13', '5678', '2025-10-13 10:30:15'),
(24, 75, 1, '2025-10-14', '780', '2025-10-14 02:24:41'),
(25, 43, 1, '2025-10-15', '5008', '2025-10-15 11:47:39'),
(27, 53, 1, '2025-10-15', '45000', '2025-10-15 11:48:51'),
(28, 52, 1, '2025-10-15', '90', '2025-10-15 11:55:46'),
(29, 43, 3, '2025-10-15', '680', '2025-10-15 12:48:22'),
(30, 7, 1, '2025-10-18', '800', '2025-10-18 05:58:23'),
(31, 78, 1, '2025-10-18', '5ut858', '2025-10-18 10:23:13'),
(32, 43, 1, '2025-10-18', '790', '2025-10-18 12:43:05'),
(33, 81, 1, '2025-10-29', 'REG005', '2025-10-29 10:12:34'),
(34, 43, 1, '2025-10-29', '790', '2025-10-29 05:36:39'),
(35, 29, 1, '2025-10-29', '600', '2025-10-29 06:11:36'),
(36, 84, 1, '2025-10-29', '7890', '2025-10-29 10:13:01'),
(41, 97, 1, '2025-10-29', '678', '2025-10-29 13:26:14');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `emp_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `position` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `vehicle_id` bigint UNSIGNED DEFAULT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `join_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_code` (`emp_code`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_phone` (`phone`),
  KEY `idx_branch` (`branch_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `idx_employees_email` (`email`),
  KEY `idx_employees_phone` (`phone`),
  KEY `idx_employees_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `emp_code`, `name`, `first_name`, `last_name`, `email`, `phone`, `address`, `position`, `role`, `license_number`, `license_expiry`, `vehicle_id`, `branch_id`, `join_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'EMP001', 'Kumar Raja', 'Kumar', 'Raja', 'kumar@example.com', '0771234567', NULL, 'Driver', 'driver', NULL, NULL, NULL, 1, NULL, 'active', '2025-09-17 06:36:37', '2025-09-17 06:36:37'),
(3, 'EMP002', 'Sita Nair', 'Sita', 'Nair', 'sita@example.com', '0772345678', NULL, 'Mechanic', 'mechanic', NULL, NULL, NULL, 1, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(4, 'EMP003', 'Arun Kumar', 'Arun', 'Kumar', 'arun@example.com', '0773456789', NULL, 'Driver', 'driver', NULL, NULL, NULL, 2, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(5, 'EMP004', 'Meena Iyer', 'Meena', 'Iyer', 'meena@example.com', '0774567890', NULL, 'Clerk', 'admin', NULL, NULL, NULL, 2, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(6, 'EMP005', 'Ravi Shankar', 'Ravi', 'Shankar', 'ravi@example.com', '0775678901', NULL, 'Driver', 'driver', NULL, NULL, NULL, 1, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(7, 'EMP006', 'Lakshmi Menon', 'Lakshmi', 'Menon', 'lakshmi@example.com', '0776789012', NULL, 'Mechanic', 'mechanic', NULL, NULL, NULL, 3, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(8, 'EMP007', 'Vikram Das', 'Vikram', 'Das', 'vikram@example.com', '0777890123', NULL, 'Driver', 'driver', NULL, NULL, NULL, 3, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(9, 'EMP008', 'Priya Raj', 'Priya', 'Raj', 'priya@example.com', '0778901234', NULL, 'Clerk', 'admin', NULL, NULL, NULL, 2, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(10, 'EMP009', 'Sanjay Kumar', 'Sanjay', 'Kumar', 'sanjay@example.com', '0779012345', NULL, 'Driver', 'driver', NULL, NULL, NULL, 1, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(11, 'EMP010', 'Anitha Varma', 'Anitha', 'Varma', 'anitha@example.com', '0770123456', NULL, 'Mechanic', 'mechanic', NULL, NULL, NULL, 3, NULL, 'active', '2025-09-17 06:37:43', '2025-09-17 06:37:43'),
(13, 'EMP012', 'Divya Menon', 'Divya', 'Menon', 'divya@example.com', '0772222222', NULL, 'Mechanic', 'mechanic', NULL, NULL, NULL, 2, NULL, 'active', '2025-09-17 06:39:44', '2025-09-17 06:39:44'),
(15, 'EMP014', 'Megha Iyer', 'Megha', 'Iyer', 'megha@example.com', '0774444444', NULL, 'Clerk', 'admin', NULL, NULL, NULL, 3, NULL, 'active', '2025-09-17 06:39:44', '2025-09-17 06:39:44'),
(17, 'EMP016', 'Nithya Raj', 'Nithya', 'Raj', 'nithya@example.com', '0776666666', 'paranthan', 'Mechanic', 'mechanic', '6789', '2025-10-03', 7, 1, '2025-10-03', 'active', '2025-09-17 06:39:44', '2025-09-17 08:02:37'),
(27, 'EMP020', 'yathu', 'moon', 'yathu', 'yathunila2001@gmail.com', '0765489736', 'murasumoddai', 'HR', 'manager', '6789', '2025-10-01', 10, 1, '2025-09-17', 'active', '2025-09-17 07:51:28', '2025-09-17 08:01:40'),
(30, 'EMP050', 'yathu', 'angel', 'yathu', 'yathu2001@gmail.com', '0773859464', 'murasumoddai', 'Mechanic', 'driver', '1234', '2025-10-01', 6, 1, '2025-09-24', 'active', '2025-09-17 08:11:22', '2025-09-17 08:11:22'),
(35, 'EMP051', 'yathu', 'moon', 'yathu', 'yathunila2000@gmail.com', '0707234561', 'murasumoddai', 'HR', 'clerk', '6532', '2025-09-12', 9, 1, '2025-09-20', 'active', '2025-09-17 08:20:15', '2025-09-17 08:20:32'),
(37, '3456', 'gtdffgg', 'moon', '4t4t', 'nila20017@gmail.com', '0707234561', 'murasumoddai', 'HR', 'hgj', '6532', '2025-09-16', NULL, 2, '2025-10-04', 'active', '2025-09-20 06:18:29', '2025-09-20 06:18:29'),
(40, 'EMP052', 'yathu', 'keerththi', 'yathu', 'yathunila2008761@gmail.com', '0765489736', 'murasumoddai', 'HR', 'manager', '6789', '2025-09-24', 7, 1, '2025-09-16', 'suspended', '2025-09-20 06:49:37', '2025-09-20 06:49:37'),
(48, 'EMP5279678', 'angelkutty', 'moon', 'yathu', 'yla2001@gmail.com', '0773859464', 'murasumoddai', 'HR', 'manager', '6789', '2025-09-09', 9, 14, '2025-09-09', 'active', '2025-09-20 14:52:02', '2025-09-20 14:52:02'),
(49, 'RRTY34', 'yathunilaaaaaal', 'ajjjjjjjjj', 'yathu', 'unila2001@gmail.com', '0707234561', 'murasumoddai', 'HR', 'driver', '6789', '2025-09-22', 10, 1, '2025-09-02', 'suspended', '2025-09-20 15:47:27', '2025-09-20 15:47:27'),
(50, 'EMP5279679', 'yathu', '', '', '', '', '', 'HR', '', '', NULL, NULL, 1, NULL, 'active', '2025-09-24 06:05:22', '2025-09-24 06:05:22');

-- --------------------------------------------------------

--
-- Table structure for table `employee_advances`
--

DROP TABLE IF EXISTS `employee_advances`;
CREATE TABLE IF NOT EXISTS `employee_advances` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `advance_date` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `settled` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_adv_employee` (`employee_id`),
  KEY `idx_emp_adv_branch` (`branch_id`),
  KEY `idx_emp_adv_date` (`advance_date`),
  KEY `fk_emp_adv_user` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_advances`
--

INSERT INTO `employee_advances` (`id`, `employee_id`, `branch_id`, `amount`, `advance_date`, `purpose`, `settled`, `created_by`, `created_at`) VALUES
(1, 30, 1, 7890.00, '2025-10-17', 'food', 1, 1, '2025-10-17 13:55:45'),
(2, 37, 1, 7890.00, '2025-10-17', 'fuel', 1, 1, '2025-10-17 13:56:24'),
(3, 11, 1, 5000.00, '2025-10-17', 'food', 1, 1, '2025-10-17 13:57:12');

-- --------------------------------------------------------

--
-- Table structure for table `employee_advance_payments`
--

DROP TABLE IF EXISTS `employee_advance_payments`;
CREATE TABLE IF NOT EXISTS `employee_advance_payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `advance_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adv_pay_adv` (`advance_id`),
  KEY `idx_adv_pay_paid_at` (`paid_at`),
  KEY `fk_adv_pay_user` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_advance_payments`
--

INSERT INTO `employee_advance_payments` (`id`, `advance_id`, `amount`, `paid_at`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 7890.00, '2025-10-17 13:55:59', NULL, 1, '2025-10-17 13:55:59'),
(2, 2, 7890.00, '2025-10-17 13:56:41', '7000', 1, '2025-10-17 13:56:41'),
(3, 3, 5000.00, '2025-10-17 13:57:24', '7000', 1, '2025-10-17 13:57:24');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payroll`
--

DROP TABLE IF EXISTS `employee_payroll`;
CREATE TABLE IF NOT EXISTS `employee_payroll` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` bigint UNSIGNED NOT NULL,
  `month_year` char(7) NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `epf_employee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `epf_employer` decimal(12,2) NOT NULL DEFAULT '0.00',
  `etf` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allowance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(12,2) GENERATED ALWAYS AS ((((`basic_salary` + `allowance`) - `deductions`) - `epf_employee`)) STORED,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_month` (`employee_id`,`month_year`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employee_payroll`
--

INSERT INTO `employee_payroll` (`id`, `employee_id`, `month_year`, `basic_salary`, `epf_employee`, `epf_employer`, `etf`, `allowance`, `deductions`, `created_at`) VALUES
(3, 46, '2025-09', 789.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-09-20 15:20:22'),
(4, 49, '2025-09', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-09-20 15:48:27'),
(5, 35, '2025-09', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, '2025-09-23 13:07:17');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `expense_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `credit_party` varchar(150) DEFAULT NULL,
  `credit_due_date` date DEFAULT NULL,
  `credit_settled` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_credit` tinyint(1) NOT NULL DEFAULT '0',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `settled_at` datetime DEFAULT NULL,
  `payment_mode` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_expenses_approver` (`approved_by`),
  KEY `idx_expenses_branch` (`branch_id`),
  KEY `idx_expenses_date` (`expense_date`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `amount`, `branch_id`, `expense_date`, `notes`, `credit_party`, `credit_due_date`, `credit_settled`, `approved_by`, `created_at`, `updated_at`, `is_credit`, `paid_amount`, `settled_at`, `payment_mode`) VALUES
(1, 'fuel', 5000.00, 1, '2025-09-15', 'Diesel refill', NULL, NULL, 0, 4, '2025-09-15 15:26:34', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(2, 'fuel', 5000.00, 1, '2025-09-15', 'Diesel refill', NULL, NULL, 0, 4, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(3, 'vehicle_maintenance', 12000.50, 1, '2025-09-16', 'Engine checkup', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(4, 'office', 2500.75, 1, '2025-09-16', 'Stationery purchase', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(5, 'utilities', 8000.00, 1, '2025-09-17', 'Electricity bill', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(6, 'fuel', 4500.25, 1, '2025-09-17', 'Petrol refill', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(7, 'office', 1500.00, 1, '2025-09-18', 'Printer ink', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(8, 'vehicle_maintenance', 9000.00, 1, '2025-09-18', 'Tire replacement', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(9, 'utilities', 7000.50, 1, '2025-09-19', 'Water bill', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(10, 'fuel', 4800.00, 1, '2025-09-19', 'Diesel refill', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(11, 'office', 2000.00, 1, '2025-09-20', 'Office chairs', NULL, NULL, 0, 1, '2025-09-15 15:27:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(12, 'other', 4569.00, 1, '2025-09-20', 'Diesel refill', NULL, NULL, 0, NULL, '2025-09-20 05:50:52', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(15, '', 65443.00, 1, '2025-09-20', '', NULL, NULL, 0, NULL, '2025-09-20 05:58:06', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(17, 'fuel', 765.00, 1, '2025-09-20', '', NULL, NULL, 0, 1, '2025-09-20 06:02:44', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(18, 'tyu', 7645.00, 1, '2025-09-20', 'Diesel refill', NULL, NULL, 0, 1, '2025-09-20 06:03:24', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(19, 'other', 700000.00, 1, '2025-10-02', 'fgh', NULL, NULL, 0, NULL, '2025-10-02 08:08:26', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(20, 'other', 500.00, 2, '2025-10-09', 'Fuel', NULL, NULL, 0, NULL, '2025-10-09 12:47:34', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(21, 'office', 890.00, 1, '2025-10-17', '', NULL, NULL, 0, 1, '2025-10-17 12:23:00', '2025-10-17 12:23:22', 1, 890.00, '2025-10-17 12:23:19', 'credit'),
(22, 'other', 7890.00, 1, '2025-10-17', 'Fuel', NULL, NULL, 0, 1, '2025-10-17 13:14:01', '2025-10-28 12:14:34', 0, 0.00, NULL, 'cash'),
(23, 'other', 1234.00, 1, '2025-10-17', 'Diesel refill', NULL, NULL, 0, 1, '2025-10-17 13:14:41', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(24, 'office', 789.00, 1, '2025-10-17', 'fgh', NULL, NULL, 0, 1, '2025-10-17 13:19:45', '2025-10-17 14:33:19', 0, 0.00, NULL, 'cash'),
(25, 'office', 789.00, 1, '2025-10-17', 'fgh', 'jkj', '2025-10-01', 1, 1, '2025-10-17 13:27:48', '2025-10-17 14:34:56', 0, 0.00, NULL, 'credit'),
(26, 'other', 6789.00, 1, '2025-10-17', 'bkjbk', 'hbhkjk', '2025-10-17', 1, 1, '2025-10-17 14:35:24', '2025-10-17 14:35:46', 0, 0.00, NULL, 'credit');

-- --------------------------------------------------------

--
-- Table structure for table `expense_payments`
--

DROP TABLE IF EXISTS `expense_payments`;
CREATE TABLE IF NOT EXISTS `expense_payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `paid_by` bigint UNSIGNED DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_exp_pay_user` (`paid_by`),
  KEY `idx_exp_pay_expense` (`expense_id`),
  KEY `idx_exp_pay_paid_at` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expense_payments`
--

INSERT INTO `expense_payments` (`id`, `expense_id`, `amount`, `paid_at`, `paid_by`, `notes`, `created_at`) VALUES
(1, 21, 890.00, '2025-10-17 13:10:39', 1, NULL, '2025-10-17 13:10:39'),
(2, 25, 789.00, '2025-10-17 14:34:56', 1, NULL, '2025-10-17 14:34:56'),
(3, 26, 6789.00, '2025-10-17 14:35:46', 1, '7000', '2025-10-17 14:35:46');

-- --------------------------------------------------------

--
-- Table structure for table `parcels`
--

DROP TABLE IF EXISTS `parcels`;
CREATE TABLE IF NOT EXISTS `parcels` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `from_branch_id` bigint UNSIGNED NOT NULL,
  `to_branch_id` bigint UNSIGNED NOT NULL,
  `route_id` bigint UNSIGNED DEFAULT NULL,
  `load_number` varchar(50) DEFAULT NULL,
  `is_return_load` tinyint(1) NOT NULL DEFAULT '0',
  `return_route_id` bigint UNSIGNED DEFAULT NULL,
  `return_load_number` varchar(50) DEFAULT NULL,
  `weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','in_transit','delivered') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(50) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_email_status` varchar(10) DEFAULT NULL,
  `last_emailed_at` datetime DEFAULT NULL,
  `last_email_subject` varchar(255) DEFAULT NULL,
  `last_email_text` mediumtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `fk_parcel_supplier` (`supplier_id`),
  KEY `fk_parcel_from_branch` (`from_branch_id`),
  KEY `idx_parcels_customer` (`customer_id`),
  KEY `idx_parcels_status` (`status`),
  KEY `idx_parcels_to_branch` (`to_branch_id`),
  KEY `idx_parcels_route` (`route_id`),
  KEY `idx_parcels_load_number` (`load_number`),
  KEY `idx_parcels_return_load` (`is_return_load`,`return_load_number`),
  KEY `fk_parcel_return_route` (`return_route_id`)
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `customer_id`, `supplier_id`, `from_branch_id`, `to_branch_id`, `route_id`, `load_number`, `is_return_load`, `return_route_id`, `return_load_number`, `weight`, `price`, `status`, `tracking_number`, `vehicle_no`, `created_at`, `updated_at`, `last_email_status`, `last_emailed_at`, `last_email_subject`, `last_email_text`) VALUES
(26, 4, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 29.00, 2363.00, 'delivered', NULL, '8', '2025-09-14 06:41:48', '2025-09-24 12:45:11', NULL, NULL, NULL, NULL),
(29, 3, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 6.00, 456.00, 'pending', NULL, '11', '2025-09-14 08:35:01', '2025-09-14 08:48:26', NULL, NULL, NULL, NULL),
(30, 3, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 89.00, 80100.00, 'delivered', NULL, '8989', '2025-09-14 08:41:33', '2025-09-14 08:46:13', NULL, NULL, NULL, NULL),
(31, 1, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 7.00, 6230.00, 'pending', NULL, '890', '2025-09-14 08:42:38', '2025-09-14 08:46:01', NULL, NULL, NULL, NULL),
(34, 1, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 78.00, 52962.00, 'delivered', NULL, '90', '2025-09-14 09:02:50', '2025-09-30 05:05:48', NULL, NULL, NULL, NULL),
(35, 4, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 67.00, 3995210.00, 'delivered', NULL, '14', '2025-09-14 09:17:06', '2025-09-14 10:00:02', NULL, NULL, NULL, NULL),
(36, 4, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 90.00, 611010.00, 'pending', NULL, '90', '2025-09-14 10:00:56', '2025-09-30 05:05:17', NULL, NULL, NULL, NULL),
(37, 3, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 78.00, 529542.00, 'delivered', NULL, '94', '2025-09-14 16:31:13', '2025-09-30 05:00:15', NULL, NULL, NULL, NULL),
(38, 3, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 3.00, 702.00, 'delivered', NULL, '115', '2025-09-14 16:33:09', '2025-09-24 12:25:59', NULL, NULL, NULL, NULL),
(39, 3, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 1.00, NULL, 'delivered', NULL, '42', '2025-09-14 16:34:07', '2025-09-29 05:47:02', NULL, NULL, NULL, NULL),
(40, 3, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 67.00, NULL, 'delivered', NULL, '80', '2025-09-15 05:49:47', '2025-09-29 05:47:02', NULL, NULL, NULL, NULL),
(41, 3, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 7.00, 315.00, 'in_transit', NULL, '', '2025-09-15 05:53:12', '2025-09-15 05:53:12', NULL, NULL, NULL, NULL),
(42, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 90.00, 61020.00, 'delivered', NULL, '90', '2025-09-15 05:59:50', '2025-09-30 05:07:29', NULL, NULL, NULL, NULL),
(43, 3, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 678.00, 535620.00, 'delivered', NULL, 'REG009', '2025-09-17 08:34:50', '2025-09-24 06:35:17', NULL, NULL, NULL, NULL),
(44, 5, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 7.00, 3990.00, 'delivered', NULL, 'REG009', '2025-09-17 13:23:27', '2025-09-24 12:30:31', NULL, NULL, NULL, NULL),
(45, 6, 3, 1, 3, NULL, NULL, 0, NULL, NULL, 45.00, 35505.00, 'delivered', NULL, '12345', '2025-09-20 05:35:15', '2025-10-13 10:33:31', NULL, NULL, NULL, NULL),
(46, 5, 3, 14, 14, NULL, NULL, 0, NULL, NULL, 56.00, 37968.00, 'pending', NULL, '45678', '2025-09-20 06:07:13', '2025-09-20 06:07:13', NULL, NULL, NULL, NULL),
(49, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 10.50, 500.00, 'delivered', 'COL-KIL-0001', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(50, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 11.00, 750.00, 'delivered', 'COL-KIL-0002', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(51, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 9.80, 600.00, 'delivered', 'COL-KIL-0003', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(52, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 12.10, 820.00, 'delivered', 'COL-KIL-0004', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(53, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 8.40, 450.00, 'delivered', 'COL-KIL-0005', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(54, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 10.00, 500.00, 'delivered', 'COL-KIL-0006', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(55, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 7.90, 400.00, 'delivered', 'COL-KIL-0007', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(56, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 13.25, 1000.00, 'delivered', 'COL-KIL-0008', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(57, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 6.75, 350.00, 'delivered', 'COL-KIL-0009', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(58, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 9.10, 520.00, 'delivered', 'COL-KIL-0010', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(59, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 5.25, 300.00, 'delivered', 'COL-KIL-0011', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(60, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 14.60, 1200.00, 'delivered', 'COL-KIL-0012', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(61, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 8.95, 475.00, 'delivered', 'COL-KIL-0013', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(62, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 0.00, 221.00, 'delivered', NULL, '', '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(63, 1, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 9.50, 580.00, 'delivered', 'COL-KIL-0015', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58', NULL, NULL, NULL, NULL),
(64, 11, 1, 2, 1, NULL, NULL, 0, NULL, NULL, 1.00, 680.00, 'delivered', NULL, 'REG003', '2025-09-27 00:49:10', '2025-10-04 09:16:47', NULL, NULL, NULL, NULL),
(65, 7, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 56.00, 78.00, 'delivered', NULL, 'REG006', '2025-09-27 00:52:51', '2025-09-29 05:45:52', NULL, NULL, NULL, NULL),
(66, 3, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 78.00, 61542.00, 'delivered', NULL, '56432', '2025-09-27 01:02:52', '2025-09-29 05:47:02', NULL, NULL, NULL, NULL),
(67, 6, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 89.00, 71289.00, 'delivered', NULL, '56432', '2025-09-27 01:09:54', '2025-09-30 04:44:22', NULL, NULL, NULL, NULL),
(68, 6, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 78.00, 90000.00, 'delivered', NULL, 'REG009', '2025-09-27 01:13:42', '2025-09-30 04:44:22', NULL, NULL, NULL, NULL),
(69, 4, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 80.00, 9999999999.99, 'delivered', NULL, 'REG009', '2025-09-27 01:31:55', '2025-09-29 05:46:41', NULL, NULL, NULL, NULL),
(70, 8, 4, 1, 2, NULL, NULL, 0, NULL, NULL, 9.00, 7209000.00, 'pending', NULL, 'REG010', '2025-09-27 01:33:07', '2025-09-27 01:37:23', NULL, NULL, NULL, NULL),
(71, 3, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 78.00, 751106304.00, 'delivered', NULL, 'REG008', '2025-09-27 01:39:11', '2025-09-29 05:47:02', NULL, NULL, NULL, NULL),
(73, 6, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 8.00, 9876536.00, 'delivered', NULL, 'REG009', '2025-09-27 01:45:39', '2025-09-30 04:44:22', NULL, NULL, NULL, NULL),
(74, 4, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 78.00, 69420.00, 'delivered', NULL, 'REG006', '2025-09-27 01:48:05', '2025-09-29 05:46:41', NULL, NULL, NULL, NULL),
(75, 25, 2, 14, 14, NULL, NULL, 0, NULL, NULL, 16.00, 80080.00, 'pending', NULL, '12345', '2025-10-01 07:39:30', '2025-10-01 07:39:30', NULL, NULL, NULL, NULL),
(76, 11, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 0.00, NULL, 'delivered', NULL, 'REG003', '2025-10-01 08:19:49', '2025-10-04 09:16:47', NULL, NULL, NULL, NULL),
(77, 6, NULL, 2, 1, NULL, NULL, 0, NULL, NULL, 67.00, 52863.00, 'delivered', NULL, '78', '2025-10-01 11:21:28', '2025-10-11 13:51:32', NULL, NULL, NULL, NULL),
(78, 7, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 7.00, 623.00, 'delivered', NULL, 'REG006', '2025-10-01 12:08:32', '2025-10-01 12:44:50', NULL, NULL, NULL, NULL),
(79, 5, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 6.00, 540.00, 'delivered', NULL, 'REG004', '2025-10-01 12:12:10', '2025-10-04 09:13:27', NULL, NULL, NULL, NULL),
(80, 5, NULL, 14, 14, NULL, NULL, 0, NULL, NULL, 0.00, NULL, 'pending', NULL, 'REG004', '2025-10-01 12:12:42', '2025-10-01 12:12:42', NULL, NULL, NULL, NULL),
(81, 6, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 8.00, 720.00, 'delivered', NULL, '78', '2025-10-01 12:13:19', '2025-10-11 13:51:32', NULL, NULL, NULL, NULL),
(82, 7, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 100.00, 2118090.00, 'delivered', NULL, 'REG006', '2025-10-01 12:17:05', '2025-10-01 12:44:50', NULL, NULL, NULL, NULL),
(83, 8, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 18.00, 14040.00, 'pending', NULL, '89765', '2025-10-01 12:21:28', '2025-10-11 12:51:38', NULL, NULL, NULL, NULL),
(86, 7, 4, 1, 1, NULL, NULL, 0, NULL, NULL, 1.00, 56.00, 'delivered', NULL, '456787', '2025-10-06 09:03:12', '2025-10-11 12:47:48', NULL, NULL, NULL, NULL),
(87, 24, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 7.00, 630.00, 'delivered', NULL, '456788', '2025-10-06 09:10:53', '2025-10-11 13:32:07', NULL, NULL, NULL, NULL),
(88, 7, NULL, 14, 1, NULL, NULL, 0, NULL, NULL, 9.00, 801.00, 'delivered', NULL, 'REG10', '2025-10-06 12:26:00', '2025-10-11 13:10:14', NULL, NULL, NULL, NULL),
(89, 5, NULL, 1, 1, NULL, NULL, 0, NULL, NULL, 8.00, 720.00, 'delivered', NULL, 'REG006', '2025-10-06 12:39:08', '2025-10-11 06:34:27', NULL, NULL, NULL, NULL),
(90, 7, NULL, 1, 1, NULL, NULL, 0, NULL, NULL, 9.00, 801.00, 'delivered', NULL, 'REG10', '2025-10-06 12:41:21', '2025-10-11 13:10:14', NULL, NULL, NULL, NULL),
(91, 7, NULL, 1, 1, NULL, NULL, 0, NULL, NULL, 7.00, 5523.00, 'delivered', NULL, 'REG10', '2025-10-06 12:44:50', '2025-10-11 13:10:14', NULL, NULL, NULL, NULL),
(92, 6, 3, 1, 1, NULL, NULL, 0, NULL, NULL, 17.00, 5899.00, 'delivered', NULL, '42', '2025-10-07 04:56:59', '2025-10-11 06:26:21', NULL, NULL, NULL, NULL),
(93, 43, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 1.00, 789.00, 'delivered', NULL, '567', '2025-10-07 05:55:55', '2025-10-13 10:02:53', NULL, NULL, NULL, NULL),
(94, 43, 2, 1, 2, NULL, NULL, 0, NULL, NULL, 9.00, 810.00, 'delivered', NULL, '567', '2025-10-07 06:02:26', '2025-10-13 10:02:53', NULL, NULL, NULL, NULL),
(95, 3, NULL, 1, 1, NULL, NULL, 0, NULL, NULL, 4.00, 268.00, 'delivered', NULL, '890', '2025-10-09 12:15:54', '2025-10-11 06:25:00', NULL, NULL, NULL, NULL),
(97, 6, 4, 1, 20, NULL, NULL, 0, NULL, NULL, 5.00, 6170.00, 'delivered', NULL, '12345', '2025-10-09 13:21:39', '2025-10-13 10:33:57', NULL, NULL, NULL, NULL),
(98, 29, 4, 19, 1, NULL, NULL, 0, NULL, NULL, 98.00, 78210.00, 'delivered', NULL, '567', '2025-10-10 10:56:28', '2025-10-11 05:14:03', NULL, NULL, NULL, NULL),
(99, 3, 5, 1, 2, NULL, NULL, 0, NULL, NULL, 7.00, 6230.00, 'pending', NULL, '564325', '2025-10-11 06:39:42', '2025-10-11 12:53:47', NULL, NULL, NULL, NULL),
(100, 29, NULL, 1, 2, NULL, NULL, 0, NULL, NULL, 8.00, 7120.00, 'delivered', NULL, '564325', '2025-10-11 06:40:01', '2025-10-13 10:03:40', NULL, NULL, NULL, NULL),
(101, 6, 4, 1, 2, NULL, NULL, 0, NULL, NULL, 7.00, 6230.00, 'delivered', NULL, '12345', '2025-10-11 06:42:04', '2025-10-13 10:33:57', NULL, NULL, NULL, NULL),
(102, 25, 4, 1, 2, NULL, NULL, 0, NULL, NULL, 8.00, 6312.00, 'delivered', NULL, 'REG004', '2025-10-11 13:07:24', '2025-10-13 10:25:25', NULL, NULL, NULL, NULL),
(103, 7, 6, 1, 1, NULL, NULL, 0, NULL, NULL, 8.00, 7120.00, 'delivered', NULL, 'REG003', '2025-10-11 13:09:59', '2025-10-11 13:10:14', NULL, NULL, NULL, NULL),
(104, 3, 3, 1, 1, NULL, NULL, 0, NULL, NULL, 7.00, 4578.00, 'delivered', NULL, '678', '2025-10-11 14:37:51', '2025-10-11 14:52:27', NULL, NULL, NULL, NULL),
(105, 46, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 43.00, 233576.00, 'delivered', NULL, '765', '2025-10-11 14:38:33', '2025-10-11 14:39:19', NULL, NULL, NULL, NULL),
(106, 46, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 43.00, 233576.00, 'delivered', NULL, '765', '2025-10-11 14:38:35', '2025-10-11 14:39:19', NULL, NULL, NULL, NULL),
(107, 3, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 7.00, 630.00, 'delivered', NULL, '678', '2025-10-11 14:51:51', '2025-10-11 14:52:27', NULL, NULL, NULL, NULL),
(108, 53, 6, 1, 2, NULL, NULL, 0, NULL, NULL, 8.00, 72000.00, 'delivered', NULL, '765', '2025-10-13 06:15:31', '2025-10-13 12:42:33', NULL, NULL, NULL, NULL),
(109, 11, 4, 1, 1, NULL, NULL, 0, NULL, NULL, 67.00, 45493.00, 'delivered', NULL, '765', '2025-10-13 06:40:42', '2025-10-13 06:41:02', NULL, NULL, NULL, NULL),
(110, 43, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 23.00, 13041.00, 'delivered', NULL, '765', '2025-10-13 06:55:50', '2025-10-13 10:02:53', NULL, NULL, NULL, NULL),
(111, 43, 2, 1, 2, NULL, NULL, 0, NULL, NULL, 67.00, 871000.00, 'delivered', NULL, '765', '2025-10-13 06:57:20', '2025-10-13 10:02:53', NULL, NULL, NULL, NULL),
(112, 43, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 6.00, 47340.00, 'delivered', NULL, '765', '2025-10-13 06:58:37', '2025-10-13 10:02:53', NULL, NULL, NULL, NULL),
(113, 69, 8, 1, 2, NULL, NULL, 0, NULL, NULL, 8.00, 7125.36, 'pending', NULL, '765', '2025-10-13 07:10:23', '2025-10-13 07:38:39', NULL, NULL, NULL, NULL),
(114, 75, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 10.00, 512339.05, 'delivered', NULL, '5678', '2025-10-13 10:29:57', '2025-10-13 10:30:19', NULL, NULL, NULL, NULL),
(115, 75, 2, 2, 1, NULL, NULL, 0, NULL, NULL, 156.00, 133700.00, 'delivered', NULL, '780', '2025-10-14 02:24:15', '2025-10-14 10:12:00', 'sent', '2025-10-14 15:42:00', NULL, NULL),
(116, 43, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 8.00, 720.00, 'delivered', NULL, '790', '2025-10-14 02:35:30', '2025-10-15 12:47:12', NULL, NULL, NULL, NULL),
(117, 43, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 8.00, 720.00, 'delivered', NULL, '790', '2025-10-14 02:36:01', '2025-10-15 12:47:12', NULL, NULL, NULL, NULL),
(118, 43, 6, 1, 2, NULL, NULL, 0, NULL, NULL, 4.00, 936.00, 'delivered', NULL, '', '2025-10-14 03:58:00', '2025-10-15 11:33:28', 'sent', '2025-10-14 15:39:54', NULL, NULL),
(119, 43, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 3.00, 137034.00, 'delivered', NULL, '', '2025-10-14 10:27:24', '2025-10-15 11:33:28', 'sent', '2025-10-14 15:57:30', NULL, NULL),
(120, 43, 2, 1, 1, NULL, NULL, 0, NULL, NULL, 6.00, 468.00, 'delivered', NULL, '', '2025-10-14 13:23:04', '2025-10-15 11:04:25', 'sent', '2025-10-14 18:53:10', NULL, NULL),
(121, 7, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 8.00, 7200.00, 'pending', NULL, '', '2025-10-14 13:32:53', '2025-10-14 13:33:59', 'sent', '2025-10-14 19:03:59', NULL, NULL),
(122, 43, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 5.00, 3950.00, 'delivered', NULL, '', '2025-10-14 13:36:21', '2025-10-15 11:04:25', 'sent', '2025-10-14 19:06:26', NULL, NULL),
(124, 53, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 8.00, 6400.00, 'delivered', NULL, '', '2025-10-15 11:48:31', '2025-10-15 11:48:55', NULL, NULL, NULL, NULL),
(125, 6, 4, 1, 1, NULL, NULL, 0, NULL, NULL, 8.00, 64000.00, 'delivered', NULL, '', '2025-10-15 11:54:34', '2025-10-29 06:12:03', 'sent', '2025-10-20 11:12:45', 'Parcel Order #125 — 64,000.00', 'Parcel Order #125Order Time: 2025-10-15 17:24:34From: Kilinochchi &rarr; To: KilinochchiVehicle:  | Items: 1 | Price: 64,000.00ItemQtyRateAmounttg8.008,000.0064,000.00Total64,000.00'),
(126, 52, 6, 1, 3, NULL, NULL, 0, NULL, NULL, 8.00, 7200.00, 'delivered', NULL, '90', '2025-10-15 11:55:29', '2025-10-16 05:37:22', NULL, NULL, NULL, NULL),
(127, 43, 6, 1, 2, NULL, NULL, 0, NULL, NULL, 7.00, 623000.00, 'delivered', NULL, '', '2025-10-15 12:02:15', '2025-10-29 05:36:45', 'sent', '2025-10-15 17:32:21', NULL, NULL),
(128, 29, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 3.00, 1350.00, 'delivered', NULL, '7000', '2025-10-18 05:32:18', '2025-10-29 06:05:29', NULL, NULL, NULL, NULL),
(129, 43, 7, 2, 1, NULL, NULL, 0, NULL, NULL, 3.00, 1350.00, 'delivered', NULL, '790', '2025-10-18 05:35:00', '2025-10-18 12:43:11', 'sent', '2025-10-18 11:05:05', NULL, NULL),
(130, 7, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 6.00, 525418608.00, 'delivered', NULL, '800', '2025-10-18 05:57:35', '2025-10-18 05:58:27', NULL, NULL, NULL, NULL),
(131, 78, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 4.00, 3200.00, 'delivered', NULL, '5ut858', '2025-10-18 09:33:25', '2025-10-18 10:23:19', 'sent', '2025-10-18 15:03:30', NULL, NULL),
(132, 43, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 2.00, 1000.00, 'delivered', NULL, '790', '2025-10-18 12:42:26', '2025-10-18 12:43:11', 'sent', '2025-10-18 18:12:32', NULL, NULL),
(133, 43, 6, 2, 1, NULL, NULL, 0, NULL, NULL, 2.00, 1360.00, 'delivered', NULL, 'REG004', '2025-10-18 12:50:51', '2025-10-18 12:51:45', 'sent', '2025-10-18 18:20:57', NULL, NULL),
(134, 81, 6, 2, 1, NULL, NULL, 0, NULL, NULL, 2.00, 1600.00, 'delivered', NULL, 'REG004', '2025-10-18 12:57:57', '2025-10-28 13:11:31', 'sent', '2025-10-28 18:41:31', 'Parcel Order #134 — 1,600.00', 'Parcel Order #134Order Time: 2025-10-18 18:27:57From: Colombo &rarr; To: KilinochchiVehicle: REG004 | Items: 1 | Price: 1,600.00ItemQtyRateAmountuerggyhrut2.00800.001,600.00Total1,600.00'),
(135, 94, 3, 1, 2, NULL, NULL, 0, NULL, NULL, 6.00, 47340.00, 'pending', NULL, 'REG004', '2025-10-29 09:03:32', '2025-10-29 09:03:32', NULL, NULL, NULL, NULL),
(136, 95, 5, 1, 2, NULL, NULL, 0, NULL, NULL, 6.00, 4536.00, 'pending', NULL, '', '2025-10-29 09:06:53', '2025-10-29 09:06:53', NULL, NULL, NULL, NULL),
(137, 96, 4, 1, 1, NULL, NULL, 0, NULL, NULL, 12.00, 45684.45, 'pending', NULL, 'REG002', '2025-10-29 09:08:18', '2025-10-29 09:08:18', NULL, NULL, NULL, NULL),
(138, 81, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 6.00, 5340.00, 'delivered', NULL, 'REG005', '2025-10-29 09:14:45', '2025-10-29 10:14:51', 'sent', '2025-10-29 14:44:50', 'Parcel Order #138 — 5,340.00', 'Parcel Order #138\nOrder Time: 2025-10-29 14:44:45\nFrom: Colombo -> To: Kilinochchi\nVehicle: REG005\nItems: 1\nPrice: 5,340.00'),
(139, 81, 4, 2, 1, NULL, NULL, 0, NULL, NULL, 6.00, 5340.00, 'delivered', NULL, 'REG005', '2025-10-29 09:14:50', '2025-10-29 10:14:51', 'sent', '2025-10-29 14:44:55', 'Parcel Order #139 — 5,340.00', 'Parcel Order #139\nOrder Time: 2025-10-29 14:44:50\nFrom: Colombo -> To: Kilinochchi\nVehicle: REG005\nItems: 1\nPrice: 5,340.00'),
(140, 84, 6, 2, 1, NULL, NULL, 0, NULL, NULL, 7.00, 61285.49, 'delivered', '7890', '7890', '2025-10-29 09:15:55', '2025-10-29 10:05:57', NULL, NULL, NULL, NULL),
(141, 97, 3, 2, 1, NULL, NULL, 0, NULL, NULL, 5.00, 3393.35, 'delivered', '890', '678', '2025-10-28 18:30:00', '2025-10-29 13:26:19', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parcel_emails`
--

DROP TABLE IF EXISTS `parcel_emails`;
CREATE TABLE IF NOT EXISTS `parcel_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint UNSIGNED NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext,
  `text_body` mediumtext,
  `status` enum('sent','failed') NOT NULL,
  `error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parcel` (`parcel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcel_emails`
--

INSERT INTO `parcel_emails` (`id`, `parcel_id`, `to_email`, `subject`, `html_body`, `text_body`, `status`, `error`, `created_at`) VALUES
(1, 125, 'yathunila2001@gmail.com', 'Parcel Order #125 — 64,000.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Parcel Order #125</h3><div style=\"color:#555;margin:0 0 6px;\">Order Time: 2025-10-15 17:24:34</div><div style=\"color:#555;margin:0 0 6px;\">From: Kilinochchi &rarr; To: Kilinochchi</div><div style=\"color:#555;margin:0 0 12px;\">Vehicle:  | Items: 1 | Price: 64,000.00</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><tr><td>tg</td><td>8.00</td><td class=\"text-end\">8,000.00</td><td class=\"text-end\">64,000.00</td></tr></tbody><tfoot><tr><th colspan=\"3\" style=\"text-align:right\">Total</th><th class=\"text-end\">64,000.00</th></tr></tfoot></table></div>', 'Parcel Order #125Order Time: 2025-10-15 17:24:34From: Kilinochchi &rarr; To: KilinochchiVehicle:  | Items: 1 | Price: 64,000.00ItemQtyRateAmounttg8.008,000.0064,000.00Total64,000.00', 'sent', NULL, '2025-10-20 05:42:45'),
(2, 134, 'yathunila2001@gmail.com', 'Parcel Order #134 — 1,600.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Parcel Order #134</h3><div style=\"color:#555;margin:0 0 6px;\">Order Time: 2025-10-18 18:27:57</div><div style=\"color:#555;margin:0 0 6px;\">From: Colombo &rarr; To: Kilinochchi</div><div style=\"color:#555;margin:0 0 12px;\">Vehicle: REG004 | Items: 1 | Price: 1,600.00</div><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><tr><td>uerggyhrut</td><td>2.00</td><td class=\"text-end\">800.00</td><td class=\"text-end\">1,600.00</td></tr></tbody><tfoot><tr><th colspan=\"3\" style=\"text-align:right\">Total</th><th class=\"text-end\">1,600.00</th></tr></tfoot></table></div>', 'Parcel Order #134Order Time: 2025-10-18 18:27:57From: Colombo &rarr; To: KilinochchiVehicle: REG004 | Items: 1 | Price: 1,600.00ItemQtyRateAmountuerggyhrut2.00800.001,600.00Total1,600.00', 'sent', NULL, '2025-10-28 13:11:31'),
(3, 138, 'yathunila2002@gmail.com', 'Parcel Order #138 — 5,340.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Parcel Order #138</h3><div style=\"color:#555;margin:0 0 6px;\">Order Time: 2025-10-29 14:44:45</div><div style=\"color:#555;margin:0 0 6px;\">From: Colombo &rarr; To: Kilinochchi</div><div style=\"color:#555;margin:0 0 12px;\">Vehicle: REG005 | Items: 1 | Price: 5,340.00</div><p>Dear angel, the following item(s) were recorded for your parcel:</p><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><tr><td>jbhnmk</td><td>6.00</td><td class=\"text-end\">890.00</td><td class=\"text-end\">5,340.00</td></tr></tbody><tfoot><tr><th colspan=\"3\" style=\"text-align:right\">Total</th><th class=\"text-end\">5,340.00</th></tr></tfoot></table><h4 style=\"margin-top:16px\">Recent History</h4><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Date</th><th>Total Price</th></tr></thead><tbody><tr><td>#138</td><td>2025-10-29 14:44:45</td><td class=\"text-end\">5,340.00</td></tr><tr><td>#134</td><td>2025-10-18 18:27:57</td><td class=\"text-end\">1,600.00</td></tr></tbody></table></div>', 'Parcel Order #138\nOrder Time: 2025-10-29 14:44:45\nFrom: Colombo -> To: Kilinochchi\nVehicle: REG005\nItems: 1\nPrice: 5,340.00', 'sent', NULL, '2025-10-29 09:14:50'),
(4, 139, 'yathunila2002@gmail.com', 'Parcel Order #139 — 5,340.00', '<div style=\"font-family:Arial,sans-serif\"><h3 style=\"margin:0 0 8px;\">Parcel Order #139</h3><div style=\"color:#555;margin:0 0 6px;\">Order Time: 2025-10-29 14:44:50</div><div style=\"color:#555;margin:0 0 6px;\">From: Colombo &rarr; To: Kilinochchi</div><div style=\"color:#555;margin:0 0 12px;\">Vehicle: REG005 | Items: 1 | Price: 5,340.00</div><p>Dear angel, the following item(s) were recorded for your parcel:</p><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead><tbody><tr><td>jbhnmk</td><td>6.00</td><td class=\"text-end\">890.00</td><td class=\"text-end\">5,340.00</td></tr></tbody><tfoot><tr><th colspan=\"3\" style=\"text-align:right\">Total</th><th class=\"text-end\">5,340.00</th></tr></tfoot></table><h4 style=\"margin-top:16px\">Recent History</h4><table border=\"1\" cellpadding=\"6\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%\"><thead><tr><th>Parcel</th><th>Date</th><th>Total Price</th></tr></thead><tbody><tr><td>#139</td><td>2025-10-29 14:44:50</td><td class=\"text-end\">5,340.00</td></tr><tr><td>#138</td><td>2025-10-29 14:44:45</td><td class=\"text-end\">5,340.00</td></tr><tr><td>#134</td><td>2025-10-18 18:27:57</td><td class=\"text-end\">1,600.00</td></tr></tbody></table></div>', 'Parcel Order #139\nOrder Time: 2025-10-29 14:44:50\nFrom: Colombo -> To: Kilinochchi\nVehicle: REG005\nItems: 1\nPrice: 5,340.00', 'sent', NULL, '2025-10-29 09:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `parcel_items`
--

DROP TABLE IF EXISTS `parcel_items`;
CREATE TABLE IF NOT EXISTS `parcel_items` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint UNSIGNED NOT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) NOT NULL,
  `rate` decimal(12,2) DEFAULT NULL,
  `amount` decimal(12,2) GENERATED ALWAYS AS ((ifnull(`qty`,0) * ifnull(`rate`,0))) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_parcel_items_parcel` (`parcel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcel_items`
--

INSERT INTO `parcel_items` (`id`, `parcel_id`, `qty`, `description`, `rate`) VALUES
(41, 31, 7.00, 'vgbhnjm', 890.00),
(42, 30, 89.00, 'fdnvn', 900.00),
(43, 29, 6.00, '5tytmy', 76.00),
(44, 26, 6.00, 'fdnvn', 78.00),
(45, 26, 9.00, 'fvghjk', 90.00),
(46, 26, 4.00, 'sdfg', 65.00),
(47, 26, 7.00, 'tgyh', 90.00),
(48, 26, 3.00, 'tyuik', 65.00),
(63, 35, 67.00, 'vgbhnjm', 59630.00),
(71, 34, 78.00, 'vgbhnjm', 0.00),
(73, 41, 7.00, 'tg', 0.00),
(75, 42, 90.00, 'aaaaaaa', 0.00),
(76, 38, 3.00, '5tytmyhjnm', 234.00),
(77, 43, 678.00, 'rtgyhujik', 0.00),
(79, 44, 7.00, 'fghj', 570.00),
(80, 45, 45.00, 'tg', 0.00),
(81, 46, 56.00, 'efrgt', 0.00),
(83, 36, 90.00, 'mmm', 0.00),
(85, 37, 78.00, 'vgbhnjm', 0.00),
(86, 39, 1.00, 'tgghbnm', 0.00),
(87, 40, 67.00, 'dfghj', 0.00),
(88, 64, 1.00, 'aaaaaaannnnnn', 0.00),
(91, 65, 56.00, 'rrrrrrrrrrrrrrrrrrrrrrrrrrrrrrr', 0.00),
(92, 66, 78.00, 'nnnnnnnnnnn', 789.00),
(93, 67, 89.00, 'fdnvn', 9.00),
(96, 68, 78.00, 'i888888888888', NULL),
(97, 69, 80.00, 'iiiiiiiiiiiiiiiiiiii', 1230000000.00),
(98, 70, 9.00, 'uu', 89000.00),
(99, 71, 78.00, 'yyyyyyyyyyyyyyy', 123456.00),
(101, 73, 8.00, 'aaaaaaa', NULL),
(102, 74, 78.00, 'i888888888888', 890.00),
(103, 75, 16.00, 'moonu', 5005.00),
(104, 77, 67.00, 'tg', 789.00),
(105, 78, 7.00, 'hggu', 89.00),
(106, 79, 6.00, 'tg', 90.00),
(107, 80, 0.00, 'fdnvn', 0.00),
(108, 81, 8.00, 'hgyg', 90.00),
(109, 82, 10.00, 'fdnvn', 678.00),
(110, 82, 90.00, 'fvghjk', 23459.00),
(111, 83, 18.00, 'hggu', 780.00),
(114, 86, 1.00, 'yyyyyyy', 56.00),
(115, 87, 7.00, 'fdnvn', 0.00),
(116, 88, 9.00, 'hhhhhhhhhhhhhhhhhhhi', 89.00),
(117, 89, 8.00, 'tg', 90.00),
(118, 90, 9.00, 'hggu', 89.00),
(119, 91, 7.00, 'i888888888888', 789.00),
(120, 92, 17.00, 'vgbhnjm', 347.00),
(121, 93, 1.00, 'tytytyty', 789.00),
(122, 94, 9.00, 'tg', 90.00),
(123, 95, 4.00, 'fdnvn', 67.00),
(124, 97, 5.00, 'fdnvn', 1234.00),
(125, 98, 8.00, 'flow', 900.00),
(126, 98, 90.00, '8kjk', 789.00),
(127, 99, 7.00, 'aaaaaaa', 890.00),
(128, 100, 8.00, 'ikiki', 890.00),
(129, 101, 7.00, 'aaaaaaa', 890.00),
(130, 102, 8.00, 'nila', 789.00),
(131, 103, 8.00, 'tgui', 890.00),
(132, 104, 7.00, 'aaaaaaa', 654.00),
(133, 105, 43.00, 'angel', 5432.00),
(134, 106, 43.00, 'angel', 5432.00),
(135, 107, 7.00, 'jhhj', 90.00),
(136, 108, 8.00, 'huuyuyu', 9000.00),
(137, 109, 67.00, 'moooo', 679.00),
(138, 110, 23.00, 'ghjgjhfh', 567.00),
(139, 111, 67.00, 'aaaaaaa', 13000.00),
(140, 112, 6.00, 'uuuuuuuuuuuu', 7890.00),
(141, 113, 8.00, 'nilooooooo', 890.67),
(142, 114, 5.00, 'aaaaaaa', 56789.67),
(143, 114, 5.00, 'bbbbbb', 45678.14),
(144, 115, 67.00, 'moon', 800.00),
(145, 115, 89.00, 'uio', 900.00),
(146, 116, 8.00, 'hjhjhhk', 90.00),
(147, 117, 8.00, 'hjhjhhk', 90.00),
(148, 118, 4.00, 'fgrthrtht', 234.00),
(149, 119, 3.00, 'hjhuh', 45678.00),
(150, 120, 6.00, 'ytytytytyttg', 78.00),
(151, 121, 8.00, 'jhjhkjokpikpo', 900.00),
(152, 122, 5.00, 'hjgjgkhhil', 790.00),
(154, 124, 8.00, 'yuyiyiyi', 800.00),
(155, 125, 8.00, 'tg', 8000.00),
(156, 126, 8.00, 'i888888888888', 900.00),
(157, 127, 7.00, 'nilllo', 89000.00),
(158, 128, 3.00, 'fuihidufhdiuf', 450.00),
(159, 129, 3.00, 'yrtertyreite', 450.00),
(160, 130, 6.00, 'fghdfgkf', 87569768.00),
(161, 131, 4.00, 'i888888888888', 800.00),
(162, 132, 2.00, 'hfrfhu', 500.00),
(163, 133, 2.00, 'hfkfhvkdf', 680.00),
(164, 134, 2.00, 'uerggyhrut', 800.00),
(165, 135, 6.00, 'kjfsdfdk', 7890.00),
(166, 136, 6.00, 'hdjdjhgjf', 756.00),
(167, 137, 5.00, 'jknjkf', 7890.89),
(168, 137, 7.00, 'fbgf', 890.00),
(169, 138, 6.00, 'jbhnmk', 890.00),
(170, 139, 6.00, 'jbhnmk', 890.00),
(171, 140, 7.00, '6gf', 8755.07);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payments_user` (`received_by`),
  KEY `idx_payments_dn` (`delivery_note_id`),
  KEY `idx_payments_paid_at` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `delivery_note_id`, `amount`, `paid_at`, `received_by`) VALUES
(7, 1, 5000.00, '2025-09-15 20:49:08', 1),
(8, 1, 7500.50, '2025-09-10 14:30:00', 4),
(9, 1, 12000.00, '2025-09-12 09:15:00', 5),
(10, 1, 2500.75, '2025-09-13 16:45:00', 6),
(11, 1, 9999.99, '2025-09-15 11:00:00', 7),
(12, 2, 5000.00, '2025-09-15 15:24:00', 1),
(13, 4, 12000.00, '2025-09-17 08:21:00', 1),
(14, 6, 9999.99, '2025-09-24 12:26:00', 1),
(15, 24, 10037825.00, '2025-10-01 11:48:00', 1),
(16, 31, 80.00, '2025-10-05 13:20:00', 1),
(17, 30, 5358.00, '2025-10-05 13:21:00', 1),
(18, 18, 50.00, '2025-10-05 13:21:00', 1),
(19, 1, 1500.00, '2025-10-09 18:17:34', NULL),
(20, 46, 5208.00, '2025-10-12 13:56:00', 1),
(21, 70, 47905.00, '2025-10-13 10:34:00', 1),
(22, 69, 512339.05, '2025-10-13 10:38:00', 1),
(23, 68, 6300.00, '2025-10-13 10:38:00', 1),
(24, 81, 2000.00, '2025-10-18 12:44:00', 1),
(25, 82, 1000.00, '2025-10-18 13:00:00', 1),
(26, 89, 1000.00, '2025-10-29 13:25:00', 1),
(27, 90, 3000.00, '2025-11-02 06:12:50', 1);

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `due_date` date NOT NULL,
  `repeat_interval` enum('none','monthly','quarterly','yearly') NOT NULL DEFAULT 'none',
  `notify_before_days` int NOT NULL DEFAULT '7',
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('open','done') NOT NULL DEFAULT 'open',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `repeat_every_days` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rem_due` (`due_date`),
  KEY `idx_rem_cat` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `title`, `category`, `due_date`, `repeat_interval`, `notify_before_days`, `notes`, `status`, `created_by`, `created_at`, `repeat_every_days`) VALUES
(1, 'insurance', 'insurance', '2025-10-17', 'monthly', 7, 'Fueljhgygy', 'done', 1, '2025-10-17 14:07:59', NULL),
(2, 'insurance', 'insurance', '2025-11-17', 'monthly', 7, 'Fueljhgygy', 'open', 1, '2025-10-17 14:08:10', NULL),
(3, 'kggh', 'hghghgh', '2025-10-17', 'none', 7, 'Fuel', 'open', 1, '2025-10-17 14:10:20', NULL),
(4, 'bhbmhn', 'bnvbn', '2025-10-17', 'none', 7, 'Diesel refill', 'open', 1, '2025-10-17 14:15:58', 19);

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
CREATE TABLE IF NOT EXISTS `routes` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Customer', 'Diesel refill', '2025-09-30 07:11:54', '2025-09-30 07:11:54'),
(2, 'yyyy', 'Diesel refill', '2025-10-01 11:39:47', '2025-10-01 11:39:47'),
(3, 'boss', 'ghruhghgrtgr', '2025-10-11 04:51:35', '2025-10-11 04:51:35'),
(4, 'angelkutty', 'ugrtrtryr', '2025-10-11 04:56:14', '2025-10-11 04:56:38'),
(6, 'angelkuttyyyy', 'Diesel refill', '2025-10-11 05:17:41', '2025-10-11 05:17:41'),
(9, 'bossy', 'Diesel refill', '2025-10-11 06:01:32', '2025-10-11 06:01:32');

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

DROP TABLE IF EXISTS `salaries`;
CREATE TABLE IF NOT EXISTS `salaries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` bigint UNSIGNED NOT NULL,
  `month` year NOT NULL,
  `month_num` tinyint UNSIGNED NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('paid','pending') NOT NULL DEFAULT 'pending',
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salary_emp_month` (`employee_id`,`month`,`month_num`)
) ENGINE=InnoDB AUTO_INCREMENT=3317 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `salaries`
--

INSERT INTO `salaries` (`id`, `employee_id`, `month`, `month_num`, `payment_date`, `status`, `amount`, `created_at`, `updated_at`) VALUES
(8, 2, '0000', 1, '2025-09-15', 'paid', 45000.00, '2025-09-15 07:16:31', '2025-09-15 07:16:53'),
(9, 3, '0000', 2, '2025-02-28', 'paid', 60000.00, '2025-09-15 07:16:31', '2025-09-15 07:16:31'),
(43, 1, '2025', 1, '2025-01-31', 'paid', 55000.00, '2025-09-15 07:28:17', '2025-09-15 07:28:17'),
(44, 2, '2025', 2, '2025-02-28', 'paid', 45000.00, '2025-09-15 07:28:17', '2025-09-15 07:28:17'),
(45, 3, '2025', 3, '2025-03-31', 'pending', 60000.00, '2025-09-15 07:28:17', '2025-09-15 07:28:17'),
(48, 4, '2025', 4, '2025-04-30', 'paid', 70000.00, '2025-09-15 07:31:14', '2025-09-15 07:31:14'),
(49, 1, '2000', 0, NULL, 'pending', 0.00, '2025-09-15 07:45:35', '2025-09-15 07:45:35'),
(50, 2, '2000', 0, NULL, 'pending', 0.00, '2025-09-15 07:45:35', '2025-09-15 07:45:35'),
(51, 3, '2000', 0, NULL, 'pending', 0.00, '2025-09-15 07:45:35', '2025-09-15 07:45:35'),
(52, 4, '2000', 0, NULL, 'pending', 5000000.00, '2025-09-15 07:45:35', '2025-09-15 07:45:35'),
(85, 1, '2025', 9, '2025-09-24', 'paid', 50000.00, '2025-09-17 08:08:12', '2025-09-24 06:15:33'),
(86, 3, '2025', 9, '2025-09-24', 'paid', 45000.00, '2025-09-17 08:08:12', '2025-09-24 06:15:50'),
(87, 4, '2025', 9, '2025-09-24', 'paid', 52000.00, '2025-09-17 08:08:12', '2025-09-24 06:14:11'),
(88, 5, '2025', 9, NULL, 'pending', 40000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:12'),
(89, 6, '2025', 9, NULL, 'pending', 51000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:12'),
(90, 7, '2025', 9, '2025-09-17', 'paid', 47000.00, '2025-09-17 08:08:12', '2025-09-17 08:09:05'),
(91, 8, '2025', 9, NULL, 'pending', 53000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:12'),
(92, 9, '2025', 9, '2025-09-17', 'paid', 42000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:54'),
(93, 10, '2025', 9, '2025-09-17', 'paid', 54000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:57'),
(94, 11, '2025', 9, NULL, 'pending', 46000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:12'),
(95, 13, '2025', 9, '2025-09-17', 'paid', 47000.00, '2025-09-17 08:08:12', '2025-09-17 08:09:08'),
(96, 15, '2025', 9, NULL, 'pending', 42000.00, '2025-09-17 08:08:12', '2025-09-17 08:08:12'),
(97, 17, '2025', 9, '2025-09-17', 'paid', 46000.00, '2025-09-17 08:08:12', '2025-09-17 08:09:00'),
(98, 27, '2025', 9, '2025-09-17', 'paid', 45000.00, '2025-09-17 08:08:12', '2025-09-17 08:09:03'),
(225, 30, '2025', 9, NULL, 'pending', 55000.00, '2025-09-17 08:20:40', '2025-09-17 08:20:40'),
(226, 35, '2025', 9, NULL, 'pending', 56700.00, '2025-09-17 08:20:40', '2025-09-17 08:20:40'),
(403, 37, '2025', 9, NULL, 'pending', 56789.00, '2025-09-20 06:34:00', '2025-09-20 06:34:00'),
(404, 38, '2025', 9, '2025-09-24', 'paid', 56.00, '2025-09-20 06:34:00', '2025-09-24 06:14:29'),
(548, 40, '2025', 9, NULL, 'pending', 65.00, '2025-09-20 09:07:07', '2025-09-20 09:07:07'),
(549, 41, '2025', 9, '2025-09-24', 'paid', 236.00, '2025-09-20 09:07:07', '2025-09-24 06:15:10'),
(550, 43, '2025', 9, NULL, 'pending', 678.00, '2025-09-20 09:07:07', '2025-09-20 09:07:07'),
(551, 46, '2025', 9, '2025-09-24', 'paid', 0.00, '2025-09-20 09:07:07', '2025-09-24 06:14:18'),
(908, 48, '2025', 9, NULL, 'pending', 0.00, '2025-09-20 14:54:01', '2025-09-20 14:54:01'),
(1067, 50, '2025', 9, '2025-09-24', 'paid', 0.00, '2025-09-24 06:10:28', '2025-09-24 06:14:04'),
(1069, 49, '2025', 9, NULL, 'pending', 0.00, '2025-09-24 06:10:28', '2025-09-24 06:10:28'),
(2414, 1, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2415, 3, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2416, 4, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2417, 5, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2418, 6, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2419, 7, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2420, 8, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2421, 9, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2422, 10, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2423, 11, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2424, 13, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2425, 15, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2426, 17, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2427, 27, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2428, 30, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2429, 35, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2430, 37, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2431, 48, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2432, 50, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2433, 40, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25'),
(2434, 49, '2025', 10, NULL, 'pending', 0.00, '2025-10-01 11:25:25', '2025-10-01 11:25:25');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `supplier_code` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_branch` (`branch_id`),
  KEY `idx_suppliers_code` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `branch_id`, `supplier_code`, `created_at`, `updated_at`) VALUES
(1, 'yathu', '0765489736', 2, '3442', '2025-09-15 15:22:10', '2025-09-15 15:22:10'),
(2, 'hh', '0707234561', 3, '8765', '2025-09-15 15:23:05', '2025-09-15 15:23:05'),
(3, 'hhtyu', '0773859464', 1, '65432', '2025-09-15 15:23:26', '2025-09-15 15:23:26'),
(4, 'kkkyhth', '0773859464', 2, '8765', '2025-09-20 05:31:07', '2025-09-24 12:33:45'),
(5, 'jjjj', '', 1, '', '2025-10-09 05:35:49', '2025-10-09 05:35:49'),
(6, 'moon', '0976457467', 1, '', '2025-10-09 05:36:14', '2025-10-09 05:36:14'),
(7, 'moooooooooo', '23456787654567', 1, '', '2025-10-09 05:38:38', '2025-10-09 05:38:38'),
(8, 'Test Supplier', '0779876543', 19, 'TSUP-001', '2025-10-09 12:47:34', '2025-10-09 12:47:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `is_main_branch` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `role`, `branch_id`, `is_main_branch`, `active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$4lCTWMXSEXV5kGIgzKrkt.8iyQSSf.ic0lTIIri2SMs8mC9HQdFzm', 'Administrator', 'admin', 1, 1, 1, '2025-09-13 06:30:56', '2025-09-14 08:07:35'),
(4, 'col_admin', '$2y$10$6730x7hdVyKeXlAvFHJDTuMGTwEjtaGpHylwPCzigleDKtI8h3syq', 'Colombo Admin', 'admin', 2, 0, 1, '2025-09-14 08:07:35', '2025-09-14 08:15:14'),
(5, 'mul_admin', '$2y$10$flwLd3d26Sek7Vr/KYzicuPTYKg0lPPhajD8CfiuF7Bv4mYxgekPu', 'Mullaitivu Admin', 'admin', 3, 0, 1, '2025-09-14 08:07:35', '2025-09-14 08:15:14'),
(6, 'account', '$2y$10$0EDLzzRa6Z/mrHRHmnYi4udvTQ2XlBib1Z720M/nfJhPnDPS80hl2', 'accountant', 'accountant', 1, 0, 1, '2025-09-15 06:08:04', '2025-09-15 06:09:44'),
(7, 'cashier', '$2y$10$F56K9NRjkcDWvi4lnCbigOIDjF9bUPXNmlZ1pux1KoaKl0WLn0UVu', 'cashier', 'cashier', 1, 0, 1, '2025-09-15 06:10:20', '2025-09-15 06:10:20'),
(8, 'duecollector', '$2y$10$cPHq/NSQm.OxW2TLE13MK.X29CXXeC5R.vVFHiZqyAMWCqAY3KN6m', 'due collector', 'collector', 1, 0, 1, '2025-09-15 06:11:31', '2025-09-15 06:11:31'),
(9, 'parceluser', '$2y$10$HaGYqB2s69nR1Y8WOnIjgOCqHS6eAL5ipARAfVf.GlsCM8/aML6AS', 'parcel user', 'parcel_user', 1, 0, 1, '2025-09-15 06:12:15', '2025-09-15 06:12:15');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `reg_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_number` (`reg_number`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `reg_number`, `type`, `capacity`, `status`, `created_at`) VALUES
(1, 'REG001', 'Lorry', 2000.00, 'active', '2025-09-17 06:54:49'),
(2, 'REG002', 'Van', 1000.00, 'active', '2025-09-17 06:54:49'),
(3, 'REG003', 'Truck', 2500.00, 'inactive', '2025-09-17 06:54:49'),
(4, 'REG004', 'Lorry', 1800.00, 'active', '2025-09-17 06:54:49'),
(5, 'REG005', 'Van', 1200.00, 'active', '2025-09-17 06:54:49'),
(6, 'REG006', 'Truck', 3000.00, 'inactive', '2025-09-17 06:54:49'),
(7, 'REG007', 'Lorry', 2200.00, 'active', '2025-09-17 06:54:49'),
(8, 'REG008', 'Van', 1100.00, 'active', '2025-09-17 06:54:49'),
(9, 'REG009', 'Truck', 2800.00, 'active', '2025-09-17 06:54:49'),
(10, 'REG010', 'Lorry', 2000.00, 'active', '2025-09-17 06:54:49');

--
-- Constraints for dumped tables
--

--
-- Constraints for table `delivery_notes`
--
ALTER TABLE `delivery_notes`
  ADD CONSTRAINT `fk_dn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_dn_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `delivery_note_emails`
--
ALTER TABLE `delivery_note_emails`
  ADD CONSTRAINT `fk_dn_emails_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_note_parcels`
--
ALTER TABLE `delivery_note_parcels`
  ADD CONSTRAINT `fk_dnp_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dnp_parcel` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_advances`
--
ALTER TABLE `employee_advances`
  ADD CONSTRAINT `fk_emp_adv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_emp_adv_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fk_emp_adv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employee_advance_payments`
--
ALTER TABLE `employee_advance_payments`
  ADD CONSTRAINT `fk_adv_pay_adv` FOREIGN KEY (`advance_id`) REFERENCES `employee_advances` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_adv_pay_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_expenses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `expense_payments`
--
ALTER TABLE `expense_payments`
  ADD CONSTRAINT `fk_exp_pay_expense` FOREIGN KEY (`expense_id`) REFERENCES `expenses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_exp_pay_user` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `fk_parcel_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_parcel_from_branch` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_parcel_return_route` FOREIGN KEY (`return_route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parcel_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parcel_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_parcel_to_branch` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `parcel_emails`
--
ALTER TABLE `parcel_emails`
  ADD CONSTRAINT `fk_parcel_emails_parcel` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `parcel_items`
--
ALTER TABLE `parcel_items`
  ADD CONSTRAINT `fk_parcel_items_parcel` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
