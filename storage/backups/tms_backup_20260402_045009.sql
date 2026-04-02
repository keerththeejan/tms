-- MySQL dump 10.13  Distrib 8.4.7, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: tms_db
-- ------------------------------------------------------
-- Server version	8.4.7

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address_tamil` varchar(500) DEFAULT NULL,
  `address_english` varchar(500) DEFAULT NULL,
  `phones` varchar(255) DEFAULT NULL,
  `settings_slot` tinyint DEFAULT NULL COMMENT '0-2 Settings letterhead slots',
  `code` varchar(20) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (24,'Colombo','இல. 71 A, ஆட்டுப்பட்டித்தெரு, கொழும்பு -13.','No. 71A, Wolfendhal Street, Colombo-13.','077 2474 905 | 077 2474 177',NULL,'BR-CDA18A9250',NULL,0,1,0,'2026-03-22 04:20:58','2026-03-22 04:51:51'),(26,'Mullaitivu','ஒட்டுசுட்டான் வீதி, புதுக்குடியிருப்பு, முல்லைத்தீவு.','Oddusuddaan Road, Puthukudiyiruppu, Mullaitivu.','077 2474 205 | 077 2480 830',NULL,'BR-56F24C624C',NULL,0,1,0,'2026-03-22 04:20:58','2026-03-22 04:22:26'),(28,'Kilinochchi','பரவிப்பாஞ்சான், கிளிநொச்சி.','Paravippanchan, Kilinochchi.','021 720 1757 | 077 2474 605',1,'BR-724FA1C77F',NULL,1,1,1,'2026-03-22 04:22:26','2026-03-22 04:51:51');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashbook_accounts`
--

DROP TABLE IF EXISTS `cashbook_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbook_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` int unsigned DEFAULT NULL,
  `type` enum('cash','bank','branch','customer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` int unsigned DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cashbook_acc_customer` (`customer_id`),
  UNIQUE KEY `uq_cashbook_acc_customer` (`customer_id`),
  KEY `idx_cashbook_acc_branch` (`branch_id`),
  KEY `idx_cashbook_acc_sort` (`sort_order`),
  KEY `idx_cashbook_acc_active` (`is_active`,`sort_order`),
  KEY `idx_cashbook_acc_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_accounts`
--

LOCK TABLES `cashbook_accounts` WRITE;
/*!40000 ALTER TABLE `cashbook_accounts` DISABLE KEYS */;
INSERT INTO `cashbook_accounts` VALUES (1,'Cash Book',NULL,'cash',500.00,1,NULL,'active',1,'2026-03-22 12:20:41'),(2,'T.S',NULL,'cash',0.00,2,NULL,'active',1,'2026-03-22 12:20:41'),(3,'user user',NULL,'customer',0.00,3,99,'active',1,'2026-04-02 09:50:37'),(4,'Rasenthiram Pavuthira',NULL,'customer',0.00,4,100,'active',1,'2026-04-02 09:50:37');
/*!40000 ALTER TABLE `cashbook_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashbook_transactions`
--

DROP TABLE IF EXISTS `cashbook_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbook_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `txn_type` enum('income','expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `occurred_at` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `parcel_id` int unsigned DEFAULT NULL,
  `items_json` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_txn_account_time` (`account_id`,`occurred_at`),
  KEY `idx_cashbook_txn_parcel` (`parcel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_transactions`
--

LOCK TABLES `cashbook_transactions` WRITE;
/*!40000 ALTER TABLE `cashbook_transactions` DISABLE KEYS */;
INSERT INTO `cashbook_transactions` VALUES (1,1,'income',500.00,'2026-04-02 10:18:00',NULL,NULL,NULL,NULL,'2026-04-02 10:18:47');
/*!40000 ALTER TABLE `cashbook_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashbook_transfers`
--

DROP TABLE IF EXISTS `cashbook_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbook_transfers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from_account_id` int unsigned NOT NULL,
  `to_account_id` int unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `occurred_at` datetime NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_tr_from` (`from_account_id`,`occurred_at`),
  KEY `idx_cashbook_tr_to` (`to_account_id`,`occurred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_transfers`
--

LOCK TABLES `cashbook_transfers` WRITE;
/*!40000 ALTER TABLE `cashbook_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashbook_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (99,'user user',NULL,NULL,'Kilinochchi','Iranamadu',NULL,NULL,NULL,NULL,'2026-02-02 02:44:46','2026-02-17 08:31:11'),(100,'Rasenthiram Pavuthira','0798645352','emmentagrossist@gmail.com','Schwandgasse 16','k8',NULL,NULL,NULL,'regular','2026-02-16 10:14:32','2026-02-16 10:14:32');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_note_emails`
--

DROP TABLE IF EXISTS `delivery_note_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_note_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint unsigned NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext,
  `text_body` mediumtext,
  `status` enum('sent','failed') NOT NULL,
  `error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dn` (`delivery_note_id`),
  CONSTRAINT `fk_dn_emails_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_note_emails`
--

LOCK TABLES `delivery_note_emails` WRITE;
/*!40000 ALTER TABLE `delivery_note_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_note_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_note_parcels`
--

DROP TABLE IF EXISTS `delivery_note_parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_note_parcels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint unsigned NOT NULL,
  `parcel_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `parcel_id` (`parcel_id`),
  KEY `idx_dnp_dn` (`delivery_note_id`),
  CONSTRAINT `fk_dnp_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dnp_parcel` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_note_parcels`
--

LOCK TABLES `delivery_note_parcels` WRITE;
/*!40000 ALTER TABLE `delivery_note_parcels` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_note_parcels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_notes`
--

DROP TABLE IF EXISTS `delivery_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_notes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
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
  KEY `fk_dn_branch` (`branch_id`),
  CONSTRAINT `fk_dn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_dn_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=123 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_notes`
--

LOCK TABLES `delivery_notes` WRITE;
/*!40000 ALTER TABLE `delivery_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_route_assignments`
--

DROP TABLE IF EXISTS `delivery_route_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_route_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `delivery_date` date NOT NULL,
  `vehicle_no` varchar(60) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_customer_branch_date` (`customer_id`,`branch_id`,`delivery_date`),
  KEY `fk_dra_branch` (`branch_id`),
  CONSTRAINT `fk_dra_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_dra_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_route_assignments`
--

LOCK TABLES `delivery_route_assignments` WRITE;
/*!40000 ALTER TABLE `delivery_route_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_route_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_routes`
--

DROP TABLE IF EXISTS `delivery_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_routes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_routes`
--

LOCK TABLES `delivery_routes` WRITE;
/*!40000 ALTER TABLE `delivery_routes` DISABLE KEYS */;
INSERT INTO `delivery_routes` VALUES (1,'Iranamadu','2026-02-17 08:30:49'),(2,'jaffna','2026-02-17 08:30:54'),(3,'paranthan','2026-02-17 08:31:00');
/*!40000 ALTER TABLE `delivery_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_advance_payments`
--

DROP TABLE IF EXISTS `employee_advance_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_advance_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `advance_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adv_pay_adv` (`advance_id`),
  KEY `idx_adv_pay_paid_at` (`paid_at`),
  KEY `fk_adv_pay_user` (`created_by`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_advance_payments`
--

LOCK TABLES `employee_advance_payments` WRITE;
/*!40000 ALTER TABLE `employee_advance_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_advance_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_advances`
--

DROP TABLE IF EXISTS `employee_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_advances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `advance_date` date NOT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `settled` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_adv_employee` (`employee_id`),
  KEY `idx_emp_adv_branch` (`branch_id`),
  KEY `idx_emp_adv_date` (`advance_date`),
  KEY `fk_emp_adv_user` (`created_by`),
  CONSTRAINT `fk_emp_adv_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_emp_adv_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `fk_emp_adv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_advances`
--

LOCK TABLES `employee_advances` WRITE;
/*!40000 ALTER TABLE `employee_advances` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_payroll`
--

DROP TABLE IF EXISTS `employee_payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_payroll` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payroll`
--

LOCK TABLES `employee_payroll` WRITE;
/*!40000 ALTER TABLE `employee_payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
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
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_payments`
--

DROP TABLE IF EXISTS `expense_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_exp_pay_user` (`paid_by`),
  KEY `idx_exp_pay_expense` (`expense_id`),
  KEY `idx_exp_pay_paid_at` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_payments`
--

LOCK TABLES `expense_payments` WRITE;
/*!40000 ALTER TABLE `expense_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `expense_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `credit_party` varchar(150) DEFAULT NULL,
  `credit_due_date` date DEFAULT NULL,
  `credit_settled` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint unsigned DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcel_activity_log`
--

DROP TABLE IF EXISTS `parcel_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcel_activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint unsigned NOT NULL,
  `action` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `meta_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parcel_activity_parcel` (`parcel_id`),
  KEY `idx_parcel_activity_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_activity_log`
--

LOCK TABLES `parcel_activity_log` WRITE;
/*!40000 ALTER TABLE `parcel_activity_log` DISABLE KEYS */;
INSERT INTO `parcel_activity_log` VALUES (1,191,'created',27,'{\"weight\":5,\"status\":\"pending\",\"tracking\":\"\"}','2026-03-22 05:53:09');
/*!40000 ALTER TABLE `parcel_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcel_emails`
--

DROP TABLE IF EXISTS `parcel_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcel_emails` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint unsigned NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` mediumtext,
  `text_body` mediumtext,
  `status` enum('sent','failed') NOT NULL,
  `error` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_parcel` (`parcel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_emails`
--

LOCK TABLES `parcel_emails` WRITE;
/*!40000 ALTER TABLE `parcel_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcel_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcel_items`
--

DROP TABLE IF EXISTS `parcel_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcel_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint unsigned NOT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` varchar(255) NOT NULL,
  `rate` decimal(12,2) DEFAULT NULL,
  `amount` decimal(12,2) GENERATED ALWAYS AS ((ifnull(`qty`,0) * ifnull(`rate`,0))) STORED,
  `additional_amount` decimal(12,2) DEFAULT NULL,
  `additional_amounts` text,
  PRIMARY KEY (`id`),
  KEY `idx_parcel_items_parcel` (`parcel_id`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_items`
--

LOCK TABLES `parcel_items` WRITE;
/*!40000 ALTER TABLE `parcel_items` DISABLE KEYS */;
INSERT INTO `parcel_items` (`id`, `parcel_id`, `qty`, `description`, `rate`, `additional_amount`, `additional_amounts`) VALUES (214,186,1.00,'box',2.00,1.00,'[1]'),(215,186,3.00,'test',5.00,NULL,NULL),(224,190,4.00,'dd',3.00,NULL,NULL),(225,190,46.00,'ee',44.00,NULL,NULL),(226,191,5.00,'test',5.00,NULL,NULL);
/*!40000 ALTER TABLE `parcel_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcels`
--

DROP TABLE IF EXISTS `parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `from_branch_id` bigint unsigned NOT NULL,
  `to_branch_id` bigint unsigned NOT NULL,
  `weight` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price` decimal(12,2) DEFAULT NULL,
  `status` enum('pending','in_transit','delivered','cancelled','returned','failed','on_hold','out_for_delivery') NOT NULL DEFAULT 'pending',
  `tracking_number` varchar(50) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_email_status` varchar(10) DEFAULT NULL,
  `last_emailed_at` datetime DEFAULT NULL,
  `last_email_subject` varchar(255) DEFAULT NULL,
  `last_email_text` mediumtext,
  `invoice_no` int unsigned DEFAULT NULL,
  `delivery_route` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tracking_number` (`tracking_number`),
  KEY `fk_parcel_supplier` (`supplier_id`),
  KEY `fk_parcel_from_branch` (`from_branch_id`),
  KEY `idx_parcels_customer` (`customer_id`),
  KEY `idx_parcels_status` (`status`),
  KEY `idx_parcels_to_branch` (`to_branch_id`),
  KEY `idx_parcels_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=192 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcels`
--

LOCK TABLES `parcels` WRITE;
/*!40000 ALTER TABLE `parcels` DISABLE KEYS */;
INSERT INTO `parcels` VALUES (186,99,NULL,22,23,4.00,NULL,'in_transit','SR260319-00186',NULL,'2026-03-18 18:30:00','2026-03-22 05:47:01',NULL,NULL,NULL,NULL,1,NULL),(190,99,NULL,21,23,50.00,NULL,'in_transit','SR260321-00190',NULL,'2026-03-20 18:30:00','2026-03-22 05:46:54',NULL,NULL,NULL,NULL,5,'Iranamadu'),(191,99,10,24,28,5.00,NULL,'pending','SR260322-00191','55','2026-03-21 18:30:00','2026-03-22 05:53:17',NULL,NULL,NULL,NULL,6,'Iranamadu');
/*!40000 ALTER TABLE `parcels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `delivery_note_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` datetime NOT NULL,
  `received_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_payments_user` (`received_by`),
  KEY `idx_payments_dn` (`delivery_note_id`),
  KEY `idx_payments_paid_at` (`paid_at`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reminders`
--

DROP TABLE IF EXISTS `reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(180) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `due_date` date NOT NULL,
  `repeat_interval` enum('none','monthly','quarterly','yearly') NOT NULL DEFAULT 'none',
  `notify_before_days` int NOT NULL DEFAULT '7',
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('open','done') NOT NULL DEFAULT 'open',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `repeat_every_days` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rem_due` (`due_date`),
  KEY `idx_rem_cat` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reminders`
--

LOCK TABLES `reminders` WRITE;
/*!40000 ALTER TABLE `reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `routes`
--

LOCK TABLES `routes` WRITE;
/*!40000 ALTER TABLE `routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salaries`
--

DROP TABLE IF EXISTS `salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `month` year NOT NULL,
  `month_num` tinyint unsigned NOT NULL,
  `payment_date` date DEFAULT NULL,
  `status` enum('paid','pending') NOT NULL DEFAULT 'pending',
  `amount` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salary_emp_month` (`employee_id`,`month`,`month_num`),
  CONSTRAINT `fk_salaries_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3395 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salaries`
--

LOCK TABLES `salaries` WRITE;
/*!40000 ALTER TABLE `salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `supplier_code` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_branch` (`branch_id`),
  KEY `idx_suppliers_code` (`supplier_code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (9,'user','+94778870135',24,'','2026-03-22 05:01:02','2026-03-22 05:01:02'),(10,'test','+94778870135',24,'','2026-03-22 05:51:54','2026-03-22 05:52:07');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `role` varchar(50) DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `is_main_branch` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `fk_users_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (27,'admin','$2y$10$toMJAoFEZm17/Fp33f92ru1GFN5EWGvf3d1Y/I7/ts1LK/imNySdW','Administrator','admin',21,0,1,'2026-02-02 02:43:56','2026-03-22 04:28:30');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reg_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_number` (`reg_number`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'tms_db'
--

--
-- Dumping routines for database 'tms_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-02 10:20:09
