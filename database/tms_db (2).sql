-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 16, 2025 at 06:23 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `phone` varchar(20) NOT NULL,
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
  KEY `idx_customers_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `delivery_location`, `place_id`, `lat`, `lng`, `customer_type`, `created_at`, `updated_at`) VALUES
(1, 'user user', '0778870135', 'Kilinochchi', 'kili', NULL, NULL, NULL, 'regular', '2025-09-13 07:12:54', '2025-09-13 08:53:55'),
(3, 'hh', '0798645352', '', '', NULL, NULL, NULL, NULL, '2025-09-13 09:09:55', '2025-09-13 09:09:55'),
(4, 'yathu', '0765489736', 'murasumoddai', '', NULL, NULL, NULL, 'regular', '2025-09-14 05:32:05', '2025-09-14 05:32:05'),
(5, 'moon', '0773859464', 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-09-15 15:24:00', '2025-09-15 15:24:00');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dn_customer_date_branch` (`customer_id`,`delivery_date`,`branch_id`),
  KEY `fk_dn_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_notes`
--

INSERT INTO `delivery_notes` (`id`, `customer_id`, `branch_id`, `delivery_date`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-09-13', 15000.00, '2025-09-13 07:13:07', '2025-09-15 15:47:03'),
(2, 1, 1, '2025-09-15', 5000.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(3, 1, 1, '2025-09-16', 7500.50, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(4, 1, 1, '2025-09-17', 12000.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(5, 1, 1, '2025-09-18', 2500.75, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(6, 1, 1, '2025-09-19', 9999.99, '2025-09-15 15:21:29', '2025-09-15 15:21:29');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
CREATE TABLE IF NOT EXISTS `employees` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `position` varchar(80) NOT NULL,
  `salary_amount` decimal(12,2) NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_employees_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `name`, `position`, `salary_amount`, `branch_id`, `created_at`, `updated_at`) VALUES
(1, 'Arun Kumar', 'Manager', 0.00, 1, '2025-09-15 07:13:38', '2025-09-15 07:13:38'),
(2, 'Siva Raj', 'Accountant', 0.00, 1, '2025-09-15 07:13:38', '2025-09-15 07:13:38'),
(3, 'Meena Devi', 'HR Executive', 0.00, 2, '2025-09-15 07:13:38', '2025-09-15 07:13:38'),
(4, 'New Employee', 'HR', 5000000.00, 3, '2025-09-15 07:31:06', '2025-09-15 07:44:59'),
(5, 'Customer', 'HR', 67890.00, 3, '2025-09-15 15:04:23', '2025-09-15 15:04:23'),
(6, 'yathu', 'HR', 45000.00, 1, '2025-09-15 15:04:45', '2025-09-15 15:04:45'),
(7, 'lux', 'HR', 9999999999.99, 3, '2025-09-15 15:05:03', '2025-09-15 15:06:22');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `expense_type` enum('fuel','vehicle_maintenance','office','utilities','other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `expense_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_expenses_approver` (`approved_by`),
  KEY `idx_expenses_branch` (`branch_id`),
  KEY `idx_expenses_date` (`expense_date`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_type`, `amount`, `branch_id`, `expense_date`, `notes`, `approved_by`, `created_at`, `updated_at`) VALUES
(1, 'fuel', 5000.00, 1, '2025-09-15', 'Diesel refill', 4, '2025-09-15 15:26:34', '2025-09-15 15:28:09'),
(2, 'fuel', 5000.00, 1, '2025-09-15', 'Diesel refill', 4, '2025-09-15 15:27:24', '2025-09-15 15:28:05'),
(3, 'vehicle_maintenance', 12000.50, 1, '2025-09-16', 'Engine checkup', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(4, 'office', 2500.75, 1, '2025-09-16', 'Stationery purchase', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(5, 'utilities', 8000.00, 1, '2025-09-17', 'Electricity bill', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(6, 'fuel', 4500.25, 1, '2025-09-17', 'Petrol refill', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(7, 'office', 1500.00, 1, '2025-09-18', 'Printer ink', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(8, 'vehicle_maintenance', 9000.00, 1, '2025-09-18', 'Tire replacement', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(9, 'utilities', 7000.50, 1, '2025-09-19', 'Water bill', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(10, 'fuel', 4800.00, 1, '2025-09-19', 'Diesel refill', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(11, 'office', 2000.00, 1, '2025-09-20', 'Office chairs', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24');

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
  `weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','in_transit','delivered') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(50) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `fk_parcel_supplier` (`supplier_id`),
  KEY `fk_parcel_from_branch` (`from_branch_id`),
  KEY `idx_parcels_customer` (`customer_id`),
  KEY `idx_parcels_status` (`status`),
  KEY `idx_parcels_to_branch` (`to_branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `customer_id`, `supplier_id`, `from_branch_id`, `to_branch_id`, `weight`, `price`, `status`, `tracking_number`, `vehicle_no`, `created_at`, `updated_at`) VALUES
(23, 3, NULL, 2, 3, 79.00, 343255.00, 'pending', NULL, '89', '2025-09-14 06:06:36', '2025-09-14 08:49:52'),
(26, 4, NULL, 2, 1, 29.00, 2363.00, 'in_transit', NULL, '8', '2025-09-14 06:41:48', '2025-09-14 08:48:57'),
(29, 3, NULL, 1, 2, 6.00, 456.00, 'pending', NULL, '11', '2025-09-14 08:35:01', '2025-09-14 08:48:26'),
(30, 3, NULL, 1, 2, 89.00, 80100.00, 'delivered', NULL, '8989', '2025-09-14 08:41:33', '2025-09-14 08:46:13'),
(31, 1, NULL, 1, 2, 7.00, 6230.00, 'pending', NULL, '890', '2025-09-14 08:42:38', '2025-09-14 08:46:01'),
(32, 4, NULL, 2, 1, 79.00, NULL, 'pending', NULL, '14', '2025-09-14 08:50:58', '2025-09-14 08:53:19'),
(33, 1, NULL, 2, 2, 79.00, NULL, 'pending', NULL, '', '2025-09-14 08:53:51', '2025-09-14 08:53:51'),
(34, 1, NULL, 1, 2, 78.00, NULL, 'delivered', NULL, '90', '2025-09-14 09:02:50', '2025-09-15 05:48:56'),
(35, 4, NULL, 2, 1, 67.00, 3995210.00, 'delivered', NULL, '14', '2025-09-14 09:17:06', '2025-09-14 10:00:02'),
(36, 4, NULL, 2, 1, 90.00, NULL, 'pending', NULL, '90', '2025-09-14 10:00:56', '2025-09-14 10:00:56'),
(37, 3, NULL, 2, 2, 78.00, NULL, 'delivered', NULL, '94', '2025-09-14 16:31:13', '2025-09-14 16:31:38'),
(38, 3, NULL, 2, 1, 3.00, 702.00, 'in_transit', NULL, '115', '2025-09-14 16:33:09', '2025-09-15 06:00:46'),
(39, 3, NULL, 1, 2, 1.00, NULL, 'in_transit', NULL, '42', '2025-09-14 16:34:07', '2025-09-15 05:48:47'),
(40, 3, NULL, 2, 2, 67.00, 804.00, 'pending', NULL, '80', '2025-09-15 05:49:47', '2025-09-15 05:49:47'),
(41, 3, NULL, 1, 2, 7.00, 315.00, 'in_transit', NULL, '', '2025-09-15 05:53:12', '2025-09-15 05:53:12'),
(42, 1, NULL, 2, 1, 90.00, NULL, 'delivered', NULL, '90', '2025-09-15 05:59:50', '2025-09-15 05:59:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(52, 23, 79.00, 'hggu', 4345.00),
(57, 32, 79.00, 'aaaaaaa', NULL),
(58, 33, 79.00, 'fdnvn', NULL),
(63, 35, 67.00, 'vgbhnjm', 59630.00),
(64, 36, 90.00, 'mmm', NULL),
(66, 37, 78.00, 'vgbhnjm', NULL),
(70, 39, 1.00, 'tgghbnm', 0.00),
(71, 34, 78.00, 'vgbhnjm', 0.00),
(72, 40, 67.00, 'dfghj', 0.00),
(73, 41, 7.00, 'tg', 0.00),
(75, 42, 90.00, 'aaaaaaa', 0.00),
(76, 38, 3.00, '5tytmyhjnm', 234.00);

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `delivery_note_id`, `amount`, `paid_at`, `received_by`) VALUES
(7, 1, 5000.00, '2025-09-15 20:49:08', 1),
(8, 1, 7500.50, '2025-09-10 14:30:00', 4),
(9, 1, 12000.00, '2025-09-12 09:15:00', 5),
(10, 1, 2500.75, '2025-09-13 16:45:00', 6),
(11, 1, 9999.99, '2025-09-15 11:00:00', 7),
(12, 2, 5000.00, '2025-09-15 15:24:00', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(52, 4, '2000', 0, NULL, 'pending', 5000000.00, '2025-09-15 07:45:35', '2025-09-15 07:45:35');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `branch_id`, `supplier_code`, `created_at`, `updated_at`) VALUES
(1, 'yathu', '0765489736', 2, '3442', '2025-09-15 15:22:10', '2025-09-15 15:22:10'),
(2, 'hh', '0707234561', 3, '8765', '2025-09-15 15:23:05', '2025-09-15 15:23:05'),
(3, 'hhtyu', '0773859464', 1, '65432', '2025-09-15 15:23:26', '2025-09-15 15:23:26');

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
  `role` enum('admin','staff','accountant','cashier','collector','parcel_user') NOT NULL DEFAULT 'staff',
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `is_main_branch` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Constraints for table `delivery_note_parcels`
--
ALTER TABLE `delivery_note_parcels`
  ADD CONSTRAINT `fk_dnp_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dnp_parcel` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_expenses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `parcels`
--
ALTER TABLE `parcels`
  ADD CONSTRAINT `fk_parcel_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `fk_parcel_from_branch` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`),
  ADD CONSTRAINT `fk_parcel_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `fk_parcel_to_branch` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`);

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
-- Constraints for table `salaries`
--
ALTER TABLE `salaries`
  ADD CONSTRAINT `fk_salaries_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

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
