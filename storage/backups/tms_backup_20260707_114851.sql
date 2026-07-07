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
-- Table structure for table `account_groups`
--

DROP TABLE IF EXISTS `account_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `group_type` enum('ASSETS','LIABILITIES','CAPITAL','INCOME','EXPENSES') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nature` enum('DEBIT','CREDIT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_code` (`group_code`),
  KEY `idx_group_code` (`group_code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_group_type` (`group_type`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_groups`
--

LOCK TABLES `account_groups` WRITE;
/*!40000 ALTER TABLE `account_groups` DISABLE KEYS */;
INSERT INTO `account_groups` VALUES (1,'ASSETS','Assets',NULL,'ASSETS','DEBIT',1,1,1,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(2,'LIABILITIES','Liabilities',NULL,'LIABILITIES','CREDIT',1,1,2,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(3,'CAPITAL','Equity',NULL,'CAPITAL','CREDIT',1,1,3,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(4,'INCOME','Income',NULL,'INCOME','CREDIT',1,1,4,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(5,'EXPENSES','Expenses',NULL,'EXPENSES','DEBIT',1,1,5,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(6,'CURRENT_ASSETS','Current Assets',1,'ASSETS','DEBIT',0,1,10,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(7,'FIXED_ASSETS','Fixed Assets',1,'ASSETS','DEBIT',0,1,11,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(8,'CURRENT_LIABILITIES','Current Liabilities',2,'LIABILITIES','CREDIT',0,1,20,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(9,'LONG_TERM_LIABILITIES','Long-Term Liabilities',2,'LIABILITIES','CREDIT',0,1,21,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(10,'SALES_INCOME','Sales Revenue',4,'INCOME','CREDIT',0,1,30,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(11,'SERVICE_INCOME','Service Revenue',4,'INCOME','CREDIT',0,1,31,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(12,'ADMIN_EXPENSES','Administrative Expenses',5,'EXPENSES','DEBIT',0,1,40,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(13,'TRANSPORT_EXPENSES','Transport Expenses',5,'EXPENSES','DEBIT',0,1,41,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(14,'SALARY_EXPENSES','Salary Expenses',5,'EXPENSES','DEBIT',0,1,42,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(15,'CASH','Cash',6,'ASSETS','DEBIT',0,1,100,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(16,'BANK','Bank',6,'ASSETS','DEBIT',0,1,101,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(17,'SUNDRY_DEBTORS','Sundry Debtors',6,'ASSETS','DEBIT',0,1,102,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(18,'SUNDRY_CREDITORS','Sundry Creditors',8,'LIABILITIES','CREDIT',0,1,200,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(19,'FUEL_EXPENSES','Fuel Expenses',13,'EXPENSES','DEBIT',0,1,300,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(20,'VEHICLE_EXPENSES','Vehicle Expenses',13,'EXPENSES','DEBIT',0,1,301,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(21,'DRIVER_SALARY','Driver Salary',14,'EXPENSES','DEBIT',0,1,302,NULL,1,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL);
/*!40000 ALTER TABLE `account_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_audit_log`
--

DROP TABLE IF EXISTS `accounting_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounting_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` bigint unsigned NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_audit_log`
--

LOCK TABLES `accounting_audit_log` WRITE;
/*!40000 ALTER TABLE `accounting_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounting_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounting_payment_mode_accounts`
--

DROP TABLE IF EXISTS `accounting_payment_mode_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounting_payment_mode_accounts` (
  `payment_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_mode`),
  KEY `idx_apma_account` (`account_id`),
  CONSTRAINT `fk_apma_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounting_payment_mode_accounts`
--

LOCK TABLES `accounting_payment_mode_accounts` WRITE;
/*!40000 ALTER TABLE `accounting_payment_mode_accounts` DISABLE KEYS */;
INSERT INTO `accounting_payment_mode_accounts` VALUES ('BANK',3,'2026-07-07 06:16:43'),('CASH',1,'2026-07-07 06:16:43'),('CHEQUE',13,'2026-07-07 06:16:43');
/*!40000 ALTER TABLE `accounting_payment_mode_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_group_id` bigint unsigned NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `opening_balance_type` enum('DEBIT','CREDIT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DEBIT',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `idx_account_code` (`account_code`),
  KEY `idx_account_name` (`account_name`),
  KEY `idx_account_group_id` (`account_group_id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_accounts_group` FOREIGN KEY (`account_group_id`) REFERENCES `account_groups` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'CASH_MAIN','Main Cash Account',15,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(2,'CASH_PETTY','Petty Cash',15,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(3,'BANK_MAIN','Main Bank Account',16,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(4,'BANK_SAVINGS','Savings Bank Account',16,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(5,'CAPITAL_OWNER','Owner Capital',3,0.00,'CREDIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(6,'FUEL_DIESEL','Diesel Fuel Expense',19,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(7,'FUEL_PETROL','Petrol Fuel Expense',19,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(8,'VEH_MAINTENANCE','Vehicle Maintenance',20,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(9,'VEH_REPAIRS','Vehicle Repairs',20,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(10,'DRIVER_SALARY_WAGES','Driver Salary & Wages',21,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(11,'SALES_FREIGHT','Freight Sales',10,0.00,'CREDIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(12,'SALES_LOADING','Loading Charges',10,0.00,'CREDIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL),(13,'CHEQUE_CLEARING','Cheque Clearing Account',16,0.00,'DEBIT',0.00,1,1,NULL,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43',NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Colombo',NULL,NULL,NULL,0,'COL',NULL,0,1,1,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(2,'Kilinochchi',NULL,NULL,NULL,1,'KIL',NULL,1,1,0,'2026-07-07 06:16:43','2026-07-07 06:18:51'),(3,'Mullaitivu',NULL,NULL,NULL,2,'MUL',NULL,0,1,0,'2026-07-07 06:16:43','2026-07-07 06:16:43');
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
  `description` text COLLATE utf8mb4_unicode_ci,
  `branch_id` int unsigned DEFAULT NULL,
  `type` enum('cash','bank','branch','customer','supplier','employee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `account_kind` enum('cash','bank','digital','receivable') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` int unsigned DEFAULT NULL,
  `supplier_id` int unsigned DEFAULT NULL,
  `employee_id` int unsigned DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cashbook_acc_customer` (`customer_id`),
  UNIQUE KEY `uq_cashbook_acc_supplier` (`supplier_id`),
  UNIQUE KEY `uq_cashbook_acc_employee` (`employee_id`),
  KEY `idx_cashbook_acc_branch` (`branch_id`),
  KEY `idx_cashbook_acc_sort` (`sort_order`),
  KEY `idx_cashbook_acc_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_accounts`
--

LOCK TABLES `cashbook_accounts` WRITE;
/*!40000 ALTER TABLE `cashbook_accounts` DISABLE KEYS */;
INSERT INTO `cashbook_accounts` VALUES (1,'Cash Book',NULL,NULL,'cash','cash',0.00,0.00,1,NULL,NULL,NULL,'active',1,'2026-07-07 11:46:43'),(2,'T.S',NULL,NULL,'cash','cash',0.00,0.00,2,NULL,NULL,NULL,'active',1,'2026-07-07 11:46:43');
/*!40000 ALTER TABLE `cashbook_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashbook_audit_logs`
--

DROP TABLE IF EXISTS `cashbook_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbook_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `entity` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_audit_entity` (`entity`,`entity_id`),
  KEY `idx_cashbook_audit_created` (`created_at`),
  KEY `idx_cashbook_audit_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_audit_logs`
--

LOCK TABLES `cashbook_audit_logs` WRITE;
/*!40000 ALTER TABLE `cashbook_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cashbook_audit_logs` ENABLE KEYS */;
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
  `reference_no` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parcel_id` int unsigned DEFAULT NULL,
  `items_json` text COLLATE utf8mb4_unicode_ci,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_txn_account_time` (`account_id`,`occurred_at`),
  KEY `idx_cashbook_txn_parcel` (`parcel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook_transactions`
--

LOCK TABLES `cashbook_transactions` WRITE;
/*!40000 ALTER TABLE `cashbook_transactions` DISABLE KEYS */;
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
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cost_center_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_center_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cost_center_code` (`cost_center_code`),
  KEY `idx_cost_center_code` (`cost_center_code`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_branch_id` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_ledger`
--

DROP TABLE IF EXISTS `customer_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_ledger` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `ledger_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ledger_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Accounts Receivable',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_ledger_customer` (`customer_id`),
  UNIQUE KEY `uq_customer_ledger_account` (`account_id`),
  UNIQUE KEY `uq_customer_ledger_code` (`ledger_code`),
  KEY `idx_customer_ledger_code` (`ledger_code`),
  CONSTRAINT `fk_customer_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_customer_ledger_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_ledger`
--

LOCK TABLES `customer_ledger` WRITE;
/*!40000 ALTER TABLE `customer_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_ledger` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
  `invoice_number` varchar(32) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_routes`
--

LOCK TABLES `delivery_routes` WRITE;
/*!40000 ALTER TABLE `delivery_routes` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_advances`
--

LOCK TABLES `employee_advances` WRITE;
/*!40000 ALTER TABLE `employee_advances` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_documents`
--

DROP TABLE IF EXISTS `employee_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` bigint unsigned NOT NULL,
  `doc_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_docs_employee` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_documents`
--

LOCK TABLES `employee_documents` WRITE;
/*!40000 ALTER TABLE `employee_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_payments`
--

DROP TABLE IF EXISTS `employee_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to payment voucher',
  `employee_id` bigint unsigned NOT NULL COMMENT 'Employee being paid',
  `salary_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Salary component',
  `advance_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Advance payment',
  `bonus_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Bonus/incentive',
  `ot_payment` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Overtime payment',
  `allowance_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Allowances',
  `deduction_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Deductions (loans, etc)',
  `total_payment` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total payment amount',
  `employee_balance_before` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Employee ledger balance before payment',
  `employee_balance_after` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Employee ledger balance after payment',
  `payment_date` date NOT NULL COMMENT 'Date payment was made',
  `payment_status` enum('PENDING','POSTED','RECONCILED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_payment_status` (`payment_status`),
  CONSTRAINT `fk_employee_payments_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_employee_payments_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee payment tracking and breakdown';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_payments`
--

LOCK TABLES `employee_payments` WRITE;
/*!40000 ALTER TABLE `employee_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_payments` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
  `code_mode` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'auto',
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nic_passport` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marital_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT 'Sri Lankan',
  `blood_group` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `religion` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `district` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `province` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `designation_id` int unsigned DEFAULT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'permanent',
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `join_date` date DEFAULT NULL,
  `confirmation_date` date DEFAULT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `allowance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `overtime_rate` decimal(12,2) NOT NULL DEFAULT '0.00',
  `epf_employee` decimal(12,2) NOT NULL DEFAULT '0.00',
  `epf_employer` decimal(12,2) NOT NULL DEFAULT '0.00',
  `etf_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `net_salary` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bank_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_holder` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `system_user_id` bigint unsigned DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_code` (`emp_code`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_phone` (`phone`),
  KEY `idx_branch` (`branch_id`),
  KEY `vehicle_id` (`vehicle_id`),
  KEY `idx_employees_email` (`email`),
  KEY `idx_employees_phone` (`phone`),
  KEY `idx_employees_status` (`status`),
  KEY `idx_employees_nic` (`nic_passport`),
  KEY `idx_employees_department` (`department_id`),
  KEY `idx_employees_designation` (`designation_id`),
  KEY `idx_employees_employment_type` (`employment_type`),
  KEY `idx_employees_deleted` (`deleted_at`),
  KEY `idx_employees_gender` (`gender`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expense_categories_code` (`code`),
  KEY `idx_expense_categories_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=324 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'fuel','Fuel',NULL,1,1,10,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(2,'transport','Transport',NULL,1,1,20,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(3,'office','Office',NULL,1,1,30,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(4,'electricity','Utilities',NULL,1,1,40,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(5,'water','Water',NULL,1,1,50,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(6,'internet','Internet',NULL,1,1,60,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(7,'telephone','Telephone',NULL,1,1,70,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(8,'vehicle_repairs','Vehicle Maintenance',NULL,1,1,80,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(9,'vehicle_insurance','Vehicle Insurance',NULL,1,1,90,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(10,'tyres','Tyres',NULL,1,1,100,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(11,'staff_salary','Staff Salary',NULL,1,1,110,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(12,'meals','Meals',NULL,1,1,120,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(13,'accommodation','Accommodation',NULL,1,1,130,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(14,'maintenance','Maintenance',NULL,1,1,140,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(15,'marketing','Marketing',NULL,1,1,150,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(16,'printing','Printing',NULL,1,1,160,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(17,'stationery','Stationery',NULL,1,1,170,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(18,'cleaning','Cleaning',NULL,1,1,180,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(19,'miscellaneous','Other',NULL,1,1,190,'2026-07-07 06:16:43','2026-07-07 06:16:43');
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
  `expense_number` varchar(32) DEFAULT NULL,
  `expense_type` varchar(50) DEFAULT NULL,
  `category_id` int unsigned DEFAULT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `reference_number` varchar(64) DEFAULT NULL,
  `description` text,
  `amount` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(20) NOT NULL DEFAULT 'cash',
  `payment_account_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `expense_date` date NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `credit_party` varchar(150) DEFAULT NULL,
  `credit_due_date` date DEFAULT NULL,
  `credit_settled` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_credit` tinyint(1) NOT NULL DEFAULT '0',
  `paid_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `voucher_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `payment_mode` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expenses_number` (`expense_number`),
  KEY `fk_expenses_approver` (`approved_by`),
  KEY `idx_expenses_branch` (`branch_id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_category` (`category_id`),
  KEY `idx_expenses_supplier` (`supplier_id`),
  KEY `idx_expenses_status` (`status`),
  KEY `idx_expenses_payment_method` (`payment_method`),
  KEY `idx_expenses_voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_departments`
--

DROP TABLE IF EXISTS `hr_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr_departments_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_departments`
--

LOCK TABLES `hr_departments` WRITE;
/*!40000 ALTER TABLE `hr_departments` DISABLE KEYS */;
INSERT INTO `hr_departments` VALUES (1,'operations','Operations',NULL,1,10,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(2,'finance','Finance',NULL,1,20,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(3,'hr','Human Resources',NULL,1,30,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(4,'maintenance','Maintenance',NULL,1,40,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(5,'admin','Administration',NULL,1,50,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(6,'logistics','Logistics',NULL,1,60,'2026-07-07 06:16:43','2026-07-07 06:16:43');
/*!40000 ALTER TABLE `hr_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_designations`
--

DROP TABLE IF EXISTS `hr_designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_designations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hr_designations_code` (`code`),
  KEY `idx_hr_designations_dept` (`department_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_designations`
--

LOCK TABLES `hr_designations` WRITE;
/*!40000 ALTER TABLE `hr_designations` DISABLE KEYS */;
INSERT INTO `hr_designations` VALUES (1,'driver','Driver',NULL,1,10,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(2,'manager','Manager',NULL,1,20,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(3,'clerk','Clerk',NULL,1,30,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(4,'mechanic','Mechanic',NULL,1,40,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(5,'accountant','Accountant',NULL,1,50,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(6,'supervisor','Supervisor',NULL,1,60,'2026-07-07 06:16:43','2026-07-07 06:16:43');
/*!40000 ALTER TABLE `hr_designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_day_sequences`
--

DROP TABLE IF EXISTS `invoice_day_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_day_sequences` (
  `bill_date` date NOT NULL,
  `last_seq` int unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bill_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_day_sequences`
--

LOCK TABLES `invoice_day_sequences` WRITE;
/*!40000 ALTER TABLE `invoice_day_sequences` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_day_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `invoice_id` int unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `from_branch_id` int unsigned DEFAULT NULL,
  `to_branch_id` int unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `invoice_no` varchar(32) NOT NULL,
  `parcel_count` int unsigned NOT NULL DEFAULT '0',
  `total_quantity` decimal(14,2) NOT NULL DEFAULT '0.00',
  `total_weight` decimal(14,2) NOT NULL DEFAULT '0.00',
  `freight_charges` decimal(14,2) NOT NULL DEFAULT '0.00',
  `delivery_charges` decimal(14,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('open','closed','cancelled') NOT NULL DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`),
  UNIQUE KEY `uq_invoices_invoice_no` (`invoice_no`),
  UNIQUE KEY `uq_invoices_bill_group` (`customer_id`,`invoice_date`,`from_branch_id`,`to_branch_id`),
  KEY `idx_invoices_date` (`invoice_date`),
  KEY `idx_invoices_customer` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ledger_accounts`
--

DROP TABLE IF EXISTS `ledger_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique account code/GL code',
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Account name',
  `account_type` enum('ASSET','LIABILITY','EQUITY','INCOME','EXPENSE','BANK','CASH','CUSTOMER','SUPPLIER','EMPLOYEE','SUSPENSE') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of account',
  `parent_account_id` bigint unsigned DEFAULT NULL COMMENT 'Parent account for hierarchy',
  `account_level` int NOT NULL DEFAULT '1' COMMENT 'Hierarchy level',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Current account balance',
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Opening balance',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether account is active',
  `allow_manual_entry` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Allow manual journal entries',
  `is_header` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Is this a header/group account',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Account description',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `idx_account_code` (`account_code`),
  KEY `idx_account_type` (`account_type`),
  KEY `idx_parent_account_id` (`parent_account_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master ledger accounts for accounting system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger_accounts`
--

LOCK TABLES `ledger_accounts` WRITE;
/*!40000 ALTER TABLE `ledger_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ledger_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ledger_entries`
--

DROP TABLE IF EXISTS `ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `voucher_detail_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `voucher_type` enum('PAYMENT','RECEIPT','JOURNAL','CONTRA','TRANSFER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_type` enum('DEBIT','CREDIT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `running_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `narration` text COLLATE utf8mb4_unicode_ci,
  `reference_id` bigint unsigned DEFAULT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_entry_date` (`entry_date`),
  KEY `idx_voucher_number` (`voucher_number`),
  KEY `idx_voucher_type` (`voucher_type`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_reference` (`reference_id`,`reference_type`),
  CONSTRAINT `fk_le_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_le_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger_entries`
--

LOCK TABLES `ledger_entries` WRITE;
/*!40000 ALTER TABLE `ledger_entries` DISABLE KEYS */;
INSERT INTO `ledger_entries` VALUES (1,1,1,1,'2026-07-01','JOURNAL','JR-2026-2027-000001',50000.00,0.00,'DEBIT',0.00,'Opening balances (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(2,1,2,3,'2026-07-01','JOURNAL','JR-2026-2027-000001',200000.00,0.00,'DEBIT',0.00,'Opening balances (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(3,1,3,5,'2026-07-01','JOURNAL','JR-2026-2027-000001',0.00,250000.00,'CREDIT',0.00,'Opening balances (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(4,2,4,1,'2026-06-15','RECEIPT','RC-2026-2027-000001',120000.00,0.00,'DEBIT',0.00,'Freight collection (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(5,2,5,11,'2026-06-15','RECEIPT','RC-2026-2027-000001',0.00,120000.00,'CREDIT',0.00,'Freight collection (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(6,3,6,6,'2026-06-15','PAYMENT','PY-2026-2027-000001',45000.00,0.00,'DEBIT',0.00,'Fuel expense (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(7,3,7,1,'2026-06-15','PAYMENT','PY-2026-2027-000001',0.00,45000.00,'CREDIT',0.00,'Fuel expense (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(8,4,8,3,'2026-07-07','RECEIPT','RC-2026-2027-000002',85000.00,0.00,'DEBIT',0.00,'Freight sales MTD (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(9,4,9,11,'2026-07-07','RECEIPT','RC-2026-2027-000002',0.00,85000.00,'CREDIT',0.00,'Freight sales MTD (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(10,5,10,6,'2026-07-07','PAYMENT','PY-2026-2027-000002',32000.00,0.00,'DEBIT',0.00,'Diesel purchase MTD (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21'),(11,5,11,1,'2026-07-07','PAYMENT','PY-2026-2027-000002',0.00,32000.00,'CREDIT',0.00,'Diesel purchase MTD (demo)',NULL,NULL,NULL,'2026-07-07 06:18:21');
/*!40000 ALTER TABLE `ledger_entries` ENABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_activity_log`
--

LOCK TABLES `parcel_activity_log` WRITE;
/*!40000 ALTER TABLE `parcel_activity_log` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcel_items`
--

LOCK TABLES `parcel_items` WRITE;
/*!40000 ALTER TABLE `parcel_items` DISABLE KEYS */;
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
  `route_id` bigint unsigned DEFAULT NULL,
  `load_number` varchar(50) DEFAULT NULL,
  `is_return_load` tinyint(1) NOT NULL DEFAULT '0',
  `return_route_id` bigint unsigned DEFAULT NULL,
  `return_load_number` varchar(50) DEFAULT NULL,
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
  `invoice_number` varchar(32) DEFAULT NULL,
  `invoice_id` int unsigned DEFAULT NULL,
  `delivery_route` varchar(255) DEFAULT NULL,
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
  KEY `fk_parcel_return_route` (`return_route_id`),
  KEY `idx_parcels_invoice_id` (`invoice_id`),
  CONSTRAINT `fk_parcel_return_route` FOREIGN KEY (`return_route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_parcel_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcels`
--

LOCK TABLES `parcels` WRITE;
/*!40000 ALTER TABLE `parcels` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_audit_logs`
--

DROP TABLE IF EXISTS `transaction_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to voucher',
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Action performed (CREATE, UPDATE, DELETE, APPROVE, REJECT, POST, etc)',
  `action_type` enum('CREATE','UPDATE','DELETE','APPROVE','REJECT','POST','REVERT','EXPORT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREATE',
  `old_values` json DEFAULT NULL COMMENT 'Previous values (JSON)',
  `new_values` json DEFAULT NULL COMMENT 'New values (JSON)',
  `changed_fields` json DEFAULT NULL COMMENT 'Array of changed field names',
  `user_id` bigint unsigned DEFAULT NULL COMMENT 'User who performed action',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'IP address of user',
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Browser user agent',
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Session identifier',
  `reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Reason for action (approval/rejection/etc)',
  `status_before` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status before action',
  `status_after` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Status after action',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_action` (`action`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_audit_search` (`voucher_id`,`created_at`,`user_id`),
  CONSTRAINT `fk_transaction_audit_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for all voucher transactions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_audit_logs`
--

LOCK TABLES `transaction_audit_logs` WRITE;
/*!40000 ALTER TABLE `transaction_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaction_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transfer_vouchers`
--

DROP TABLE IF EXISTS `transfer_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transfer_vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_no` int unsigned NOT NULL,
  `fiscal_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_date` date NOT NULL,
  `from_account_id` int unsigned NOT NULL,
  `to_account_id` int unsigned NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `narration` text COLLATE utf8mb4_unicode_ci,
  `status` enum('DRAFT','POSTED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `cashbook_transfer_id` int unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `posted_by` int unsigned DEFAULT NULL,
  `cancelled_by` int unsigned DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transfer_voucher_no` (`voucher_no`),
  UNIQUE KEY `uq_transfer_voucher_seq` (`fiscal_year`,`sequence_no`),
  KEY `idx_transfer_voucher_status` (`status`),
  KEY `idx_transfer_voucher_date` (`voucher_date`),
  KEY `idx_transfer_voucher_created_by` (`created_by`),
  KEY `idx_transfer_voucher_posted_at` (`posted_at`),
  KEY `idx_transfer_voucher_cashbook_transfer` (`cashbook_transfer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transfer_vouchers`
--

LOCK TABLES `transfer_vouchers` WRITE;
/*!40000 ALTER TABLE `transfer_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `transfer_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_voucher_mapping`
--

DROP TABLE IF EXISTS `transport_voucher_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_voucher_mapping` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `transport_type` enum('FUEL','VEHICLE_EXPENSE','CUSTOMER_INVOICE','SUPPLIER_PAYMENT','DRIVER_SALARY') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transport_id` bigint unsigned NOT NULL,
  `mapping_details` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_transport` (`transport_type`,`transport_id`),
  CONSTRAINT `fk_tvm_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_voucher_mapping`
--

LOCK TABLES `transport_voucher_mapping` WRITE;
/*!40000 ALTER TABLE `transport_voucher_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_voucher_mapping` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$qvyP9sjmjfHPpeYVZS3PEO.LIJKJ2mq4IaGe7wHLReI4rsujs5PCG','Administrator','admin',2,1,1,'2026-07-07 06:16:45','2026-07-07 06:16:54');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_approvals`
--

DROP TABLE IF EXISTS `voucher_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to voucher',
  `first_approver_id` bigint unsigned DEFAULT NULL COMMENT 'First level approver',
  `first_approval_at` timestamp NULL DEFAULT NULL COMMENT 'First approval timestamp',
  `first_approval_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'First approver comments',
  `second_approver_id` bigint unsigned DEFAULT NULL COMMENT 'Second level approver',
  `second_approval_at` timestamp NULL DEFAULT NULL COMMENT 'Second approval timestamp',
  `second_approval_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Second approver comments',
  `final_approver_id` bigint unsigned DEFAULT NULL COMMENT 'Final approver',
  `final_approval_at` timestamp NULL DEFAULT NULL COMMENT 'Final approval timestamp',
  `final_approval_notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Final approver comments',
  `rejected_by_id` bigint unsigned DEFAULT NULL COMMENT 'User who rejected',
  `rejected_at` timestamp NULL DEFAULT NULL COMMENT 'Rejection timestamp',
  `rejection_reason` text COLLATE utf8mb4_unicode_ci COMMENT 'Reason for rejection',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_id` (`voucher_id`),
  KEY `idx_voucher_id` (`voucher_id`),
  CONSTRAINT `fk_voucher_approvals_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Approval workflow tracking for vouchers';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_approvals`
--

LOCK TABLES `voucher_approvals` WRITE;
/*!40000 ALTER TABLE `voucher_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_details`
--

DROP TABLE IF EXISTS `voucher_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL,
  `line_number` int NOT NULL,
  `account_id` bigint unsigned NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `narration` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_center_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_line_number` (`line_number`),
  CONSTRAINT `fk_vd_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_vd_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_details`
--

LOCK TABLES `voucher_details` WRITE;
/*!40000 ALTER TABLE `voucher_details` DISABLE KEYS */;
INSERT INTO `voucher_details` VALUES (1,1,1,1,50000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(2,1,2,3,200000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(3,1,3,5,0.00,250000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(4,2,1,1,120000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(5,2,2,11,0.00,120000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(6,3,1,6,45000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(7,3,2,1,0.00,45000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(8,4,1,3,85000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(9,4,2,11,0.00,85000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(10,5,1,6,32000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(11,5,2,1,0.00,32000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(12,6,1,6,5000.00,0.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21'),(13,6,2,1,0.00,5000.00,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21');
/*!40000 ALTER TABLE `voucher_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_drafts`
--

DROP TABLE IF EXISTS `voucher_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_drafts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL COMMENT 'User creating draft',
  `voucher_id` bigint unsigned DEFAULT NULL COMMENT 'Reference to voucher if linked',
  `draft_data` json NOT NULL COMMENT 'Complete voucher data as JSON',
  `draft_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'User-given name for draft',
  `status` enum('ACTIVE','ARCHIVED','CONVERTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE' COMMENT 'Draft status',
  `last_saved_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last auto-save time',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` timestamp NULL DEFAULT NULL COMMENT 'When draft expires (30 days after creation)',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-save drafts for voucher entries';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_drafts`
--

LOCK TABLES `voucher_drafts` WRITE;
/*!40000 ALTER TABLE `voucher_drafts` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_drafts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_items`
--

DROP TABLE IF EXISTS `voucher_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint unsigned NOT NULL COMMENT 'Reference to voucher',
  `line_number` int NOT NULL COMMENT 'Line item sequence number',
  `ledger_account_id` bigint unsigned DEFAULT NULL COMMENT 'Reference to ledger account',
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Account name for quick display',
  `account_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Account code/GL code',
  `employee_id` bigint unsigned DEFAULT NULL COMMENT 'If payment to employee',
  `customer_id` bigint unsigned DEFAULT NULL COMMENT 'If payment to/from customer',
  `supplier_id` bigint unsigned DEFAULT NULL COMMENT 'If payment to supplier',
  `debit_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Debit amount for this line',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Credit amount for this line',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Line item description/narration',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_voucher_id` (`voucher_id`),
  KEY `idx_account_code` (`account_code`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_supplier_id` (`supplier_id`),
  KEY `idx_line_number` (`line_number`),
  KEY `idx_voucher_items_search` (`voucher_id`,`account_code`,`employee_id`),
  CONSTRAINT `fk_voucher_items_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_voucher_items_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Line items for each voucher - double-entry rows';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_items`
--

LOCK TABLES `voucher_items` WRITE;
/*!40000 ALTER TABLE `voucher_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `voucher_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_series`
--

DROP TABLE IF EXISTS `voucher_series`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `voucher_series` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `series_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `voucher_type` enum('PAYMENT','RECEIPT','JOURNAL','CONTRA','TRANSFER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `starting_number` int NOT NULL DEFAULT '1',
  `current_number` int NOT NULL DEFAULT '0',
  `reset_type` enum('NONE','YEARLY','MONTHLY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'YEARLY',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `branch_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `series_name` (`series_name`),
  KEY `idx_series_name` (`series_name`),
  KEY `idx_voucher_type` (`voucher_type`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_series`
--

LOCK TABLES `voucher_series` WRITE;
/*!40000 ALTER TABLE `voucher_series` DISABLE KEYS */;
INSERT INTO `voucher_series` VALUES (1,'PAYMENT_SERIES','PAYMENT','PY-',1,2,'YEARLY',1,NULL,'2026-07-07 06:16:43','2026-07-07 06:18:21'),(2,'RECEIPT_SERIES','RECEIPT','RC-',1,2,'YEARLY',1,NULL,'2026-07-07 06:16:43','2026-07-07 06:18:21'),(3,'JOURNAL_SERIES','JOURNAL','JR-',1,2,'YEARLY',1,NULL,'2026-07-07 06:16:43','2026-07-07 06:18:21'),(4,'CONTRA_SERIES','CONTRA','CT-',1,0,'YEARLY',1,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43'),(5,'TRANSFER_SERIES','TRANSFER','TR-',1,0,'YEARLY',1,NULL,'2026-07-07 06:16:43','2026-07-07 06:16:43');
/*!40000 ALTER TABLE `voucher_series` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `voucher_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique voucher number - auto-generated',
  `voucher_type` enum('RECEIPT','PAYMENT','JOURNAL','TRANSFER','CONTRA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PAYMENT' COMMENT 'Type of voucher',
  `fiscal_year` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Fiscal year for validation',
  `voucher_date` date NOT NULL COMMENT 'Date of voucher transaction',
  `payment_mode` enum('CASH','BANK','CHEQUE','ONLINE','PETTY_CASH','OTHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CASH' COMMENT 'Mode of payment',
  `cheque_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cheque number if payment mode is CHEQUE',
  `cheque_date` date DEFAULT NULL COMMENT 'Cheque maturity date',
  `cheque_bank` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cheque issuing bank',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'External reference (PO, Bill, etc)',
  `narration` text COLLATE utf8mb4_unicode_ci COMMENT 'Description/memo for the voucher',
  `status` enum('DRAFT','SUBMITTED','APPROVED','POSTED','REJECTED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT' COMMENT 'Voucher status',
  `approval_status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT 'Approval workflow status',
  `total_debit` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total debit amount',
  `total_credit` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Total credit amount',
  `balance_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Remaining balance to balance',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'User who created voucher',
  `approved_by` bigint unsigned DEFAULT NULL COMMENT 'User who approved voucher',
  `posted_by` bigint unsigned DEFAULT NULL COMMENT 'User who posted voucher',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `posted_at` timestamp NULL DEFAULT NULL COMMENT 'When voucher was posted to ledger',
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete timestamp',
  `series_id` bigint unsigned DEFAULT NULL,
  `bank_account_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_number` (`voucher_number`),
  KEY `idx_voucher_date` (`voucher_date`),
  KEY `idx_status` (`status`),
  KEY `idx_approval_status` (`approval_status`),
  KEY `idx_fiscal_year` (`fiscal_year`),
  KEY `idx_payment_mode` (`payment_mode`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_voucher_type` (`voucher_type`),
  KEY `idx_vouchers_search` (`voucher_date`,`status`,`created_by`,`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Master voucher records';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vouchers`
--

LOCK TABLES `vouchers` WRITE;
/*!40000 ALTER TABLE `vouchers` DISABLE KEYS */;
INSERT INTO `vouchers` VALUES (1,'JR-2026-2027-000001','JOURNAL','2026-2027','2026-07-01','CASH',NULL,NULL,NULL,NULL,'Opening balances (demo)','POSTED','PENDING',250000.00,250000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(2,'RC-2026-2027-000001','RECEIPT','2026-2027','2026-06-15','CASH',NULL,NULL,NULL,'DEMO-RCP-001','Freight collection (demo)','POSTED','PENDING',120000.00,120000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(3,'PY-2026-2027-000001','PAYMENT','2026-2027','2026-06-15','CASH',NULL,NULL,NULL,'DEMO-PAY-001','Fuel expense (demo)','POSTED','PENDING',45000.00,45000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(4,'RC-2026-2027-000002','RECEIPT','2026-2027','2026-07-07','BANK',NULL,NULL,NULL,'DEMO-RCP-002','Freight sales MTD (demo)','POSTED','PENDING',85000.00,85000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(5,'PY-2026-2027-000002','PAYMENT','2026-2027','2026-07-07','CASH',NULL,NULL,NULL,'DEMO-PAY-002','Diesel purchase MTD (demo)','POSTED','PENDING',32000.00,32000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL),(6,'JR-2026-2027-000002','JOURNAL','2026-2027','2026-07-07','CASH',NULL,NULL,NULL,NULL,'Draft voucher pending approval (demo)','DRAFT','PENDING',5000.00,5000.00,0.00,NULL,NULL,NULL,'2026-07-07 06:18:21','2026-07-07 06:18:21',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `vouchers` ENABLE KEYS */;
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

-- Dump completed on 2026-07-07 11:48:51
