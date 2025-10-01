-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 01, 2025 at 02:32 PM
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `is_main`, `created_at`, `updated_at`) VALUES
(1, 'Kilinochchi', 'MAIN', 1, '2025-09-13 06:30:56', '2025-09-13 07:15:56'),
(2, 'Colombo', 'BR-A', 0, '2025-09-13 06:30:56', '2025-09-13 07:16:27'),
(3, 'mullaitivu', 'BR-B', 0, '2025-09-13 06:30:56', '2025-09-13 07:16:47'),
(14, 'jaffna', '765', 0, '2025-09-20 06:05:41', '2025-09-20 06:05:41');

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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `delivery_location`, `place_id`, `lat`, `lng`, `customer_type`, `created_at`, `updated_at`) VALUES
(1, 'user user', '0778870135', 'Kilinochchi', 'kili', NULL, NULL, NULL, 'regular', '2025-09-13 07:12:54', '2025-09-13 08:53:55'),
(3, 'hh', '0798645352', 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'corporate', '2025-09-13 09:09:55', '2025-09-24 12:32:10'),
(4, 'yathu', '0765489736', 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-14 05:32:05', '2025-09-24 12:31:58'),
(5, 'moon', '0773859464', 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-09-15 15:24:00', '2025-09-15 15:24:00'),
(6, 'kujin', '0770000000', 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-20 05:30:44', '2025-09-24 12:31:52'),
(7, 'lux', '12345654589', 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'regular', '2025-09-24 12:07:43', '2025-09-24 12:07:43'),
(8, 'yathu', '6546444433225678', 'murasumoddai', 'mul', NULL, NULL, NULL, 'regular', '2025-09-26 05:29:58', '2025-09-26 05:29:58'),
(11, 'yathuy', '0707234561', 'murasumoddai', 'kilinochchi', NULL, NULL, NULL, 'corporate', '2025-09-26 05:35:20', '2025-10-01 12:01:03'),
(24, 'moonuuuuuuu', '0770000009', 'murasumoddai', 'paranthan', NULL, NULL, NULL, 'regular', '2025-09-30 06:39:54', '2025-10-01 12:00:56'),
(25, 'yathu', '75585859955', 'murasumoddai', 'fghj', NULL, NULL, NULL, 'regular', '2025-10-01 07:39:04', '2025-10-01 12:00:49');

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
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `delivery_notes`
--

INSERT INTO `delivery_notes` (`id`, `customer_id`, `branch_id`, `delivery_date`, `total_amount`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2025-09-13', 15000.00, '2025-09-13 07:13:07', '2025-09-15 15:47:03'),
(2, 1, 1, '2025-09-15', 5000.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(3, 1, 1, '2025-09-16', 7500.50, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(4, 1, 1, '2025-09-17', 12000.00, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(5, 1, 1, '2025-09-18', 2500.75, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(6, 1, 1, '2025-09-19', 9999.99, '2025-09-15 15:21:29', '2025-09-15 15:21:29'),
(9, 6, 1, '2025-09-20', 0.00, '2025-09-20 05:35:58', '2025-09-20 05:35:58'),
(10, 3, 1, '2025-09-24', 702.00, '2025-09-24 06:33:07', '2025-09-24 12:25:59'),
(11, 3, 1, '2025-09-17', 535620.00, '2025-09-24 06:35:17', '2025-09-24 06:35:17'),
(12, 7, 1, '2025-09-24', 0.00, '2025-09-24 12:08:56', '2025-09-24 12:08:56'),
(13, 6, 1, '2025-09-24', 0.00, '2025-09-24 12:17:38', '2025-09-24 12:17:38'),
(14, 5, 1, '2025-09-24', 3990.00, '2025-09-24 12:30:30', '2025-09-24 12:30:31'),
(15, 1, 1, '2025-09-24', 0.00, '2025-09-24 12:44:19', '2025-09-24 12:44:19'),
(16, 4, 1, '2025-09-16', 2363.00, '2025-09-24 12:45:11', '2025-09-24 12:45:11'),
(17, 1, 1, '2025-09-29', 8666.00, '2025-09-29 05:43:58', '2025-09-29 05:43:58'),
(18, 7, 1, '2025-09-29', 78.00, '2025-09-29 05:45:52', '2025-09-29 05:45:52'),
(19, 4, 1, '2025-09-29', 9999999999.99, '2025-09-29 05:46:41', '2025-09-29 05:46:41'),
(20, 8, 1, '2025-09-29', 0.00, '2025-09-29 05:46:49', '2025-09-29 05:46:49'),
(21, 3, 1, '2025-09-29', 751167846.00, '2025-09-29 05:47:02', '2025-09-29 05:47:02'),
(22, 4, 1, '2025-09-30', 0.00, '2025-09-30 04:42:58', '2025-09-30 04:42:58'),
(23, 1, 1, '2025-09-30', 0.00, '2025-09-30 04:43:09', '2025-09-30 04:43:09'),
(24, 6, 1, '2025-09-30', 10037825.00, '2025-09-30 04:44:22', '2025-09-30 04:44:22'),
(25, 8, 1, '2025-09-30', 0.00, '2025-09-30 06:54:32', '2025-09-30 06:54:32'),
(26, 7, 1, '2025-09-30', 0.00, '2025-09-30 06:57:24', '2025-09-30 06:57:24'),
(27, 7, 1, '2025-10-01', 2119553.00, '2025-10-01 12:44:50', '2025-10-01 12:44:50');

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
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(37, 27, 84, 840.00);

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
(37, '3456', 'gtdffgg', 'moon', '4t4t', 'nila20017@gmail.com', '0707234561', 'murasumoddai', 'HR', 'hgj', '6532', '2025-09-16', 13, 2, '2025-10-04', 'active', '2025-09-20 06:18:29', '2025-09-20 06:18:29'),
(40, 'EMP052', 'yathu', 'keerththi', 'yathu', 'yathunila2008761@gmail.com', '0765489736', 'murasumoddai', 'HR', 'manager', '6789', '2025-09-24', 7, 1, '2025-09-16', 'suspended', '2025-09-20 06:49:37', '2025-09-20 06:49:37'),
(48, 'EMP5279678', 'angelkutty', 'moon', 'yathu', 'yla2001@gmail.com', '0773859464', 'murasumoddai', 'HR', 'manager', '6789', '2025-09-09', 9, 14, '2025-09-09', 'active', '2025-09-20 14:52:02', '2025-09-20 14:52:02'),
(49, 'RRTY34', 'yathunilaaaaaal', 'ajjjjjjjjj', 'yathu', 'unila2001@gmail.com', '0707234561', 'murasumoddai', 'HR', 'driver', '6789', '2025-09-22', 10, 1, '2025-09-02', 'suspended', '2025-09-20 15:47:27', '2025-09-20 15:47:27'),
(50, 'EMP5279679', 'yathu', '', '', '', '', '', 'HR', '', '', NULL, NULL, 1, NULL, 'active', '2025-09-24 06:05:22', '2025-09-24 06:05:22');

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
  `approved_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_expenses_approver` (`approved_by`),
  KEY `idx_expenses_branch` (`branch_id`),
  KEY `idx_expenses_date` (`expense_date`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(11, 'office', 2000.00, 1, '2025-09-20', 'Office chairs', 1, '2025-09-15 15:27:24', '2025-09-15 15:27:24'),
(12, 'other', 4569.00, 1, '2025-09-20', 'Diesel refill', NULL, '2025-09-20 05:50:52', '2025-09-20 05:50:52'),
(15, '', 65443.00, 1, '2025-09-20', '', NULL, '2025-09-20 05:58:06', '2025-09-20 05:58:06'),
(17, 'fuel', 765.00, 1, '2025-09-20', '', 1, '2025-09-20 06:02:44', '2025-09-20 15:35:39'),
(18, 'tyu', 7645.00, 1, '2025-09-20', 'Diesel refill', 1, '2025-09-20 06:03:24', '2025-09-24 12:24:48');

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
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `parcels`
--

INSERT INTO `parcels` (`id`, `customer_id`, `supplier_id`, `from_branch_id`, `to_branch_id`, `weight`, `price`, `status`, `tracking_number`, `vehicle_no`, `created_at`, `updated_at`) VALUES
(26, 4, NULL, 2, 1, 29.00, 2363.00, 'delivered', NULL, '8', '2025-09-14 06:41:48', '2025-09-24 12:45:11'),
(29, 3, NULL, 1, 2, 6.00, 456.00, 'pending', NULL, '11', '2025-09-14 08:35:01', '2025-09-14 08:48:26'),
(30, 3, NULL, 1, 2, 89.00, 80100.00, 'delivered', NULL, '8989', '2025-09-14 08:41:33', '2025-09-14 08:46:13'),
(31, 1, NULL, 1, 2, 7.00, 6230.00, 'pending', NULL, '890', '2025-09-14 08:42:38', '2025-09-14 08:46:01'),
(34, 1, NULL, 1, 2, 78.00, 52962.00, 'delivered', NULL, '90', '2025-09-14 09:02:50', '2025-09-30 05:05:48'),
(35, 4, NULL, 2, 1, 67.00, 3995210.00, 'delivered', NULL, '14', '2025-09-14 09:17:06', '2025-09-14 10:00:02'),
(36, 4, NULL, 2, 1, 90.00, 611010.00, 'pending', NULL, '90', '2025-09-14 10:00:56', '2025-09-30 05:05:17'),
(37, 3, NULL, 2, 1, 78.00, 529542.00, 'delivered', NULL, '94', '2025-09-14 16:31:13', '2025-09-30 05:00:15'),
(38, 3, NULL, 2, 1, 3.00, 702.00, 'delivered', NULL, '115', '2025-09-14 16:33:09', '2025-09-24 12:25:59'),
(39, 3, NULL, 2, 1, 1.00, NULL, 'delivered', NULL, '42', '2025-09-14 16:34:07', '2025-09-29 05:47:02'),
(40, 3, NULL, 2, 1, 67.00, NULL, 'delivered', NULL, '80', '2025-09-15 05:49:47', '2025-09-29 05:47:02'),
(41, 3, NULL, 1, 2, 7.00, 315.00, 'in_transit', NULL, '', '2025-09-15 05:53:12', '2025-09-15 05:53:12'),
(42, 1, NULL, 2, 1, 90.00, 61020.00, 'delivered', NULL, '90', '2025-09-15 05:59:50', '2025-09-30 05:07:29'),
(43, 3, 3, 2, 1, 678.00, 535620.00, 'delivered', NULL, 'REG009', '2025-09-17 08:34:50', '2025-09-24 06:35:17'),
(44, 5, NULL, 2, 1, 7.00, 3990.00, 'delivered', NULL, 'REG009', '2025-09-17 13:23:27', '2025-09-24 12:30:31'),
(45, 6, 3, 1, 3, 45.00, 35505.00, 'pending', NULL, '12345', '2025-09-20 05:35:15', '2025-09-20 05:35:15'),
(46, 5, 3, 14, 14, 56.00, 37968.00, 'pending', NULL, '45678', '2025-09-20 06:07:13', '2025-09-20 06:07:13'),
(49, 1, NULL, 2, 1, 10.50, 500.00, 'delivered', 'COL-KIL-0001', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(50, 1, NULL, 2, 1, 11.00, 750.00, 'delivered', 'COL-KIL-0002', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(51, 1, NULL, 2, 1, 9.80, 600.00, 'delivered', 'COL-KIL-0003', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(52, 1, NULL, 2, 1, 12.10, 820.00, 'delivered', 'COL-KIL-0004', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(53, 1, NULL, 2, 1, 8.40, 450.00, 'delivered', 'COL-KIL-0005', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(54, 1, NULL, 2, 1, 10.00, 500.00, 'delivered', 'COL-KIL-0006', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(55, 1, NULL, 2, 1, 7.90, 400.00, 'delivered', 'COL-KIL-0007', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(56, 1, NULL, 2, 1, 13.25, 1000.00, 'delivered', 'COL-KIL-0008', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(57, 1, NULL, 2, 1, 6.75, 350.00, 'delivered', 'COL-KIL-0009', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(58, 1, NULL, 2, 1, 9.10, 520.00, 'delivered', 'COL-KIL-0010', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(59, 1, NULL, 2, 1, 5.25, 300.00, 'delivered', 'COL-KIL-0011', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(60, 1, NULL, 2, 1, 14.60, 1200.00, 'delivered', 'COL-KIL-0012', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(61, 1, NULL, 2, 1, 8.95, 475.00, 'delivered', 'COL-KIL-0013', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(62, 1, NULL, 2, 1, 0.00, 221.00, 'delivered', NULL, '', '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(63, 1, NULL, 2, 1, 9.50, 580.00, 'delivered', 'COL-KIL-0015', NULL, '2025-09-24 13:04:54', '2025-09-29 05:43:58'),
(64, 11, 1, 2, 1, 1.00, 680.00, 'pending', NULL, 'REG003', '2025-09-27 00:49:10', '2025-09-27 00:49:10'),
(65, 7, 3, 2, 1, 56.00, 78.00, 'delivered', NULL, 'REG006', '2025-09-27 00:52:51', '2025-09-29 05:45:52'),
(66, 3, 2, 2, 1, 78.00, 61542.00, 'delivered', NULL, '56432', '2025-09-27 01:02:52', '2025-09-29 05:47:02'),
(67, 6, 3, 2, 1, 89.00, 71289.00, 'delivered', NULL, '56432', '2025-09-27 01:09:54', '2025-09-30 04:44:22'),
(68, 6, 2, 2, 1, 78.00, 90000.00, 'delivered', NULL, 'REG009', '2025-09-27 01:13:42', '2025-09-30 04:44:22'),
(69, 4, 2, 2, 1, 80.00, 9999999999.99, 'delivered', NULL, 'REG009', '2025-09-27 01:31:55', '2025-09-29 05:46:41'),
(70, 8, 4, 1, 2, 9.00, 7209000.00, 'pending', NULL, 'REG010', '2025-09-27 01:33:07', '2025-09-27 01:37:23'),
(71, 3, 2, 2, 1, 78.00, 751106304.00, 'delivered', NULL, 'REG008', '2025-09-27 01:39:11', '2025-09-29 05:47:02'),
(73, 6, 3, 2, 1, 8.00, 9876536.00, 'delivered', NULL, 'REG009', '2025-09-27 01:45:39', '2025-09-30 04:44:22'),
(74, 4, 4, 2, 1, 78.00, 69420.00, 'delivered', NULL, 'REG006', '2025-09-27 01:48:05', '2025-09-29 05:46:41'),
(75, 25, 2, 14, 14, 16.00, 80080.00, 'pending', NULL, '12345', '2025-10-01 07:39:30', '2025-10-01 07:39:30'),
(76, 11, 2, 2, 1, 0.00, NULL, 'pending', NULL, 'REG003', '2025-10-01 08:19:49', '2025-10-01 08:19:49'),
(77, 6, NULL, 2, 1, 67.00, 52863.00, 'pending', NULL, '', '2025-10-01 11:21:28', '2025-10-01 11:21:28'),
(78, 7, 2, 2, 1, 7.00, 623.00, 'delivered', NULL, 'REG006', '2025-10-01 12:08:32', '2025-10-01 12:44:50'),
(79, 5, 3, 2, 1, 6.00, 540.00, 'pending', NULL, 'REG004', '2025-10-01 12:12:10', '2025-10-01 12:12:10'),
(80, 5, NULL, 14, 14, 0.00, NULL, 'pending', NULL, 'REG004', '2025-10-01 12:12:42', '2025-10-01 12:12:42'),
(81, 6, 4, 2, 1, 8.00, 720.00, 'pending', NULL, '', '2025-10-01 12:13:19', '2025-10-01 12:13:19'),
(82, 7, 2, 2, 1, 100.00, 2118090.00, 'delivered', NULL, 'REG006', '2025-10-01 12:17:05', '2025-10-01 12:44:50'),
(83, 8, 3, 1, 2, 18.00, 14040.00, 'pending', NULL, 'REG010', '2025-10-01 12:21:28', '2025-10-01 12:21:28'),
(84, 7, 4, 2, 1, 12.00, 840.00, 'delivered', NULL, '', '2025-10-01 12:36:55', '2025-10-01 12:44:50');

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
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(112, 84, 12.00, 'fdnvn', 70.00);

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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(15, 24, 10037825.00, '2025-10-01 11:48:00', 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `routes`
--

INSERT INTO `routes` (`id`, `name`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Customer', 'Diesel refill', '2025-09-30 07:11:54', '2025-09-30 07:11:54'),
(2, 'yyyy', 'Diesel refill', '2025-10-01 11:39:47', '2025-10-01 11:39:47');

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
) ENGINE=InnoDB AUTO_INCREMENT=2519 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `branch_id`, `supplier_code`, `created_at`, `updated_at`) VALUES
(1, 'yathu', '0765489736', 2, '3442', '2025-09-15 15:22:10', '2025-09-15 15:22:10'),
(2, 'hh', '0707234561', 3, '8765', '2025-09-15 15:23:05', '2025-09-15 15:23:05'),
(3, 'hhtyu', '0773859464', 1, '65432', '2025-09-15 15:23:26', '2025-09-15 15:23:26'),
(4, 'kkkyhth', '0773859464', 2, '8765', '2025-09-20 05:31:07', '2025-09-24 12:33:45');

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
(10, 'REG010', 'Lorry', 2000.00, 'active', '2025-09-17 06:54:49'),
(11, '12345', NULL, NULL, 'active', '2025-09-20 05:34:54'),
(12, '45678', NULL, NULL, 'active', '2025-09-20 06:06:54'),
(13, '567', NULL, NULL, 'active', '2025-09-20 06:16:37'),
(14, '56432', NULL, NULL, 'active', '2025-09-20 06:28:53');

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
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

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
