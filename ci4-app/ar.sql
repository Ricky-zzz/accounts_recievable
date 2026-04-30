-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: localhost    Database: ar
-- ------------------------------------------------------
-- Server version	8.0.30

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
-- Current Database: `ar`
--


--
-- Table structure for table `banks`
--

DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banks`
--

LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
INSERT INTO `banks` VALUES (1,'BDO','BDO','BDO-123','2026-04-29 16:53:37','2026-04-29 16:53:37'),(2,'MBTC','MBTC','MBTC-123','2026-04-29 16:53:44','2026-04-29 16:53:44'),(3,'BPI','BPI','BPI-123','2026-04-29 16:53:50','2026-04-29 16:53:50');
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `boa`
--

DROP TABLE IF EXISTS `boa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `boa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `payor` int unsigned NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_id` int unsigned NOT NULL,
  `BPI` decimal(12,2) DEFAULT '0.00',
  `MBTC` decimal(12,2) DEFAULT '0.00',
  `BDO` decimal(12,2) DEFAULT '0.00',
  `ar_trade` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ar_others` decimal(12,2) NOT NULL DEFAULT '0.00',
  `account_title` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `note` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dr` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cr` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payor_date` (`payor`,`date`),
  KEY `reference` (`reference`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `boa_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `boa_payor_foreign` FOREIGN KEY (`payor`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boa`
--

LOCK TABLES `boa` WRITE;
/*!40000 ALTER TABLE `boa` DISABLE KEYS */;
INSERT INTO `boa` VALUES (1,'2026-04-29',1,'1001',1,0.00,0.00,15000.00,16000.00,0.00,NULL,NULL,NULL,0.00,0.00,'2026-04-29 16:54:50','2026-04-29 16:54:50'),(2,'2026-04-29',1,'1001',1,0.00,0.00,0.00,0.00,0.00,'Sales Discount',NULL,NULL,1000.00,0.00,'2026-04-29 16:54:50','2026-04-29 16:54:50'),(3,'2026-04-29',5,'1003',2,0.00,0.00,16500.00,15500.00,0.00,NULL,NULL,NULL,0.00,0.00,'2026-04-29 17:07:04','2026-04-29 17:07:04'),(4,'2026-04-29',5,'1003',2,0.00,0.00,0.00,0.00,1000.00,NULL,NULL,'others',0.00,0.00,'2026-04-29 17:07:04','2026-04-29 17:07:04');
/*!40000 ALTER TABLE `boa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashier_receipt_ranges`
--

DROP TABLE IF EXISTS `cashier_receipt_ranges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashier_receipt_ranges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `start_no` int NOT NULL,
  `end_no` int NOT NULL,
  `next_no` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cashier_receipt_ranges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashier_receipt_ranges`
--

LOCK TABLES `cashier_receipt_ranges` WRITE;
/*!40000 ALTER TABLE `cashier_receipt_ranges` DISABLE KEYS */;
INSERT INTO `cashier_receipt_ranges` VALUES (1,1,1001,1999,1004,'active',NULL);
/*!40000 ALTER TABLE `cashier_receipt_ranges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT NULL,
  `payment_term` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,'Acme Corporation','123 Business Ave, Suite 100, New York, NY 10001','contact@acmecorp.com','+1-212-555-0101',1000000.00,30,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(2,'Global Industries Inc','456 Commerce Boulevard, Los Angeles, CA 90001','info@globalindustries.com','+1-213-555-0202',750000.00,15,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(3,'Tech Solutions Ltd','789 Digital Drive, San Francisco, CA 94102','sales@techsolutions.com','+1-415-555-0303',500000.00,30,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(4,'Manufacturing Pro Services','321 Industrial Park, Chicago, IL 60601','procurement@mfgpro.com','+1-312-555-0404',850000.00,45,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(5,'Enterprise Solutions Group','654 Corporate Plaza, Houston, TX 77002','accounts@enterprisesolutions.com','+1-713-555-0505',1200000.00,30,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(6,'Premier Distribution Network','987 Trade Center, Miami, FL 33101','orders@premierdist.com','+1-305-555-0606',650000.00,20,'2026-04-29 12:49:18','2026-04-29 12:49:18');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliveries`
--

DROP TABLE IF EXISTS `deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliveries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `dr_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `payment_term` int unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `void_reason` text COLLATE utf8mb4_general_ci,
  `voided_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id_dr_no` (`client_id`,`dr_no`),
  KEY `client_id_date` (`client_id`,`date`),
  KEY `idx_deliveries_due_date` (`due_date`),
  CONSTRAINT `deliveries_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
INSERT INTO `deliveries` VALUES (1,1,'DR-101','2026-04-29',30,'2026-05-29',16000.00,'active',NULL,NULL,'2026-04-29 16:54:25','2026-04-29 16:54:36'),(2,5,'DR-103','2026-04-29',30,'2026-05-29',15500.00,'active',NULL,NULL,'2026-04-29 17:06:43','2026-04-29 17:06:43');
/*!40000 ALTER TABLE `deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_histories`
--

DROP TABLE IF EXISTS `delivery_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int unsigned NOT NULL,
  `edited_by` int unsigned DEFAULT NULL,
  `action` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'edit',
  `old_delivery_json` longtext COLLATE utf8mb4_general_ci,
  `old_items_json` longtext COLLATE utf8mb4_general_ci,
  `new_delivery_json` longtext COLLATE utf8mb4_general_ci,
  `new_items_json` longtext COLLATE utf8mb4_general_ci,
  `change_summary` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_id_created_at` (`delivery_id`,`created_at`),
  KEY `edited_by` (`edited_by`),
  KEY `action` (`action`),
  CONSTRAINT `delivery_histories_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `delivery_histories_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_histories`
--

LOCK TABLES `delivery_histories` WRITE;
/*!40000 ALTER TABLE `delivery_histories` DISABLE KEYS */;
INSERT INTO `delivery_histories` VALUES (1,1,1,'edit','{\"id\":\"1\",\"client_id\":\"1\",\"dr_no\":\"DR-101\",\"date\":\"2026-04-29\",\"payment_term\":\"30\",\"due_date\":\"2026-05-29\",\"total_amount\":\"17000.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-29 16:54:25\",\"updated_at\":\"2026-04-29 16:54:25\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"17000.00\"}','[{\"id\":\"1\",\"delivery_id\":\"1\",\"product_id\":\"5\",\"qty\":\"100.00\",\"unit_price\":\"170.00\",\"line_total\":\"17000.00\",\"product_name\":\"Electronic Modules\"}]','{\"id\":\"1\",\"client_id\":\"1\",\"dr_no\":\"DR-101\",\"date\":\"2026-04-29\",\"payment_term\":\"30\",\"due_date\":\"2026-05-29\",\"total_amount\":\"16000.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-29 16:54:25\",\"updated_at\":\"2026-04-29 16:54:36\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"16000.00\"}','[{\"id\":\"2\",\"delivery_id\":\"1\",\"product_id\":\"5\",\"qty\":\"100.00\",\"unit_price\":\"160.00\",\"line_total\":\"16000.00\",\"product_name\":\"Electronic Modules\"}]','Total changed from 17,000.00 to 16,000.00.','2026-04-29 16:54:36');
/*!40000 ALTER TABLE `delivery_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_items`
--

DROP TABLE IF EXISTS `delivery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `delivery_items_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `delivery_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_items`
--

LOCK TABLES `delivery_items` WRITE;
/*!40000 ALTER TABLE `delivery_items` DISABLE KEYS */;
INSERT INTO `delivery_items` VALUES (2,1,5,100.00,160.00,16000.00),(3,2,5,100.00,155.00,15500.00);
/*!40000 ALTER TABLE `delivery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ledger`
--

DROP TABLE IF EXISTS `ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `dr_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qty` decimal(12,2) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `collection` decimal(12,2) NOT NULL DEFAULT '0.00',
  `account_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_accounts` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `delivery_id` int unsigned DEFAULT NULL,
  `payment_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id_entry_date` (`client_id`,`entry_date`),
  KEY `delivery_id` (`delivery_id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `ledger_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `ledger_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `ledger_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger`
--

LOCK TABLES `ledger` WRITE;
/*!40000 ALTER TABLE `ledger` DISABLE KEYS */;
INSERT INTO `ledger` VALUES (1,1,'2026-04-29','DR-101',NULL,100.00,170.00,17000.00,0.00,NULL,0.00,17000.00,1,NULL,'2026-04-29 16:54:25'),(2,1,'2026-04-29','DR-101',NULL,NULL,NULL,0.00,0.00,'Delivery Adjustment',1000.00,16000.00,1,NULL,'2026-04-29 16:54:36'),(3,1,'2026-04-29',NULL,'1001',NULL,NULL,0.00,15000.00,NULL,0.00,1000.00,NULL,1,'2026-04-29 16:54:50'),(4,1,'2026-04-29',NULL,'1001',NULL,NULL,0.00,0.00,'Sales Discount',1000.00,0.00,NULL,1,'2026-04-29 16:54:50'),(5,5,'2026-04-29','DR-103',NULL,100.00,155.00,15500.00,0.00,NULL,0.00,15500.00,2,NULL,'2026-04-29 17:06:43'),(6,5,'2026-04-29',NULL,'1003',NULL,NULL,0.00,15500.00,NULL,0.00,0.00,NULL,2,'2026-04-29 17:07:04');
/*!40000 ALTER TABLE `ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=131 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (111,'2026-04-21-065247','App\\Database\\Migrations\\CreateUsers','default','App',1777466936,1),(112,'2026-04-21-065248','App\\Database\\Migrations\\CreateBanks','default','App',1777466936,1),(113,'2026-04-21-065248','App\\Database\\Migrations\\CreateClients','default','App',1777466936,1),(114,'2026-04-21-065248','App\\Database\\Migrations\\CreateProducts','default','App',1777466936,1),(115,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveries','default','App',1777466936,1),(116,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveryItems','default','App',1777466936,1),(117,'2026-04-21-135124','App\\Database\\Migrations\\CreateCashiers','default','App',1777466936,1),(118,'2026-04-21-135125','App\\Database\\Migrations\\CreateCashierReceiptRanges','default','App',1777466936,1),(119,'2026-04-21-135126','App\\Database\\Migrations\\CreatePayments','default','App',1777466936,1),(120,'2026-04-21-150100','App\\Database\\Migrations\\CreatePaymentAllocations','default','App',1777466937,1),(121,'2026-04-21-150200','App\\Database\\Migrations\\CreateLedger','default','App',1777466937,1),(122,'2026-04-21-155000','App\\Database\\Migrations\\CreateOtherAccounts','default','App',1777466937,1),(123,'2026-04-21-160000','App\\Database\\Migrations\\CreateBoa','default','App',1777466937,1),(124,'2026-04-22-090000','App\\Database\\Migrations\\UpdateOtherAccountsType','default','App',1777466937,1),(125,'2026-04-22-091000','App\\Database\\Migrations\\UpdateBoaForOtherAccounts','default','App',1777466937,1),(126,'2026-04-24-100000','App\\Database\\Migrations\\AddClientCreditFields','default','App',1777466937,1),(127,'2026-04-24-100100','App\\Database\\Migrations\\AddDeliveryTermsFields','default','App',1777466937,1),(128,'2026-04-25-090000','App\\Database\\Migrations\\AddOtherAccountsToLedger','default','App',1777466937,1),(129,'2026-04-29-000000','App\\Database\\Migrations\\CreateDeliveryHistories','default','App',1777466937,1),(130,'2026-04-29-010000','App\\Database\\Migrations\\CreatePayablesTables','default','App',1777466937,1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `other_accounts`
--

DROP TABLE IF EXISTS `other_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `other_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('dr','cr') COLLATE utf8mb4_general_ci DEFAULT 'dr',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `other_accounts`
--

LOCK TABLES `other_accounts` WRITE;
/*!40000 ALTER TABLE `other_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `other_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payable_allocations`
--

DROP TABLE IF EXISTS `payable_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payable_allocations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payable_id` int unsigned NOT NULL,
  `purchase_order_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payable_id` (`payable_id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  CONSTRAINT `payable_allocations_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payable_allocations_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payable_allocations`
--

LOCK TABLES `payable_allocations` WRITE;
/*!40000 ALTER TABLE `payable_allocations` DISABLE KEYS */;
INSERT INTO `payable_allocations` VALUES (1,1,1,3300.00,'2026-04-29 16:56:42');
/*!40000 ALTER TABLE `payable_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payable_ledger`
--

DROP TABLE IF EXISTS `payable_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payable_ledger` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `po_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pr_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qty` decimal(12,2) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `payables` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `account_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_accounts` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `purchase_order_id` int unsigned DEFAULT NULL,
  `payable_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_id_entry_date` (`supplier_id`,`entry_date`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `payable_id` (`payable_id`),
  CONSTRAINT `payable_ledger_payable_id_foreign` FOREIGN KEY (`payable_id`) REFERENCES `payables` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payable_ledger_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payable_ledger_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payable_ledger`
--

LOCK TABLES `payable_ledger` WRITE;
/*!40000 ALTER TABLE `payable_ledger` DISABLE KEYS */;
INSERT INTO `payable_ledger` VALUES (1,4,'2026-04-29','PO-100',NULL,100.00,33.00,3300.00,0.00,NULL,0.00,3300.00,1,NULL,'2026-04-29 15:14:24'),(2,4,'2026-04-29',NULL,'1002',NULL,NULL,0.00,3300.00,NULL,0.00,0.00,NULL,1,'2026-04-29 16:56:42'),(3,4,'2026-04-30','PO-101',NULL,100.00,33.00,3300.00,0.00,NULL,0.00,3300.00,2,NULL,'2026-04-29 17:00:34');
/*!40000 ALTER TABLE `payable_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payables`
--

DROP TABLE IF EXISTS `payables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payables` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `pr_no` int NOT NULL,
  `date` date NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_allocated` decimal(12,2) NOT NULL DEFAULT '0.00',
  `excess_used` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payer_bank` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `check_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deposit_bank_id` int unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'posted',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_pr_no` (`user_id`,`pr_no`),
  KEY `payables_deposit_bank_id_foreign` (`deposit_bank_id`),
  KEY `supplier_id_date` (`supplier_id`,`date`),
  CONSTRAINT `payables_deposit_bank_id_foreign` FOREIGN KEY (`deposit_bank_id`) REFERENCES `banks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payables_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payables`
--

LOCK TABLES `payables` WRITE;
/*!40000 ALTER TABLE `payables` DISABLE KEYS */;
INSERT INTO `payables` VALUES (1,4,1,1002,'2026-04-29','cash',3300.00,3300.00,0.00,NULL,NULL,1,'posted','2026-04-29 16:56:42','2026-04-29 16:56:42');
/*!40000 ALTER TABLE `payables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` int unsigned NOT NULL,
  `delivery_id` int unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  KEY `delivery_id` (`delivery_id`),
  CONSTRAINT `payment_allocations_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payment_allocations_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (1,1,1,16000.00,'2026-04-29 16:54:50'),(2,2,2,15500.00,'2026-04-29 17:07:04');
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `pr_no` int NOT NULL,
  `date` date NOT NULL,
  `method` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_allocated` decimal(12,2) NOT NULL DEFAULT '0.00',
  `excess_used` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payer_bank` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `check_no` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `deposit_bank_id` int unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'posted',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_pr_no` (`user_id`,`pr_no`),
  KEY `payments_deposit_bank_id_foreign` (`deposit_bank_id`),
  KEY `client_id_date` (`client_id`,`date`),
  CONSTRAINT `payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payments_deposit_bank_id_foreign` FOREIGN KEY (`deposit_bank_id`) REFERENCES `banks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,1,1001,'2026-04-29','cash',15000.00,16000.00,0.00,NULL,NULL,1,'posted','2026-04-29 16:54:50','2026-04-29 16:54:50'),(2,5,1,1003,'2026-04-29','cash',16500.00,15500.00,0.00,NULL,NULL,1,'posted','2026-04-29 17:07:04','2026-04-29 17:07:04');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'PROD-001','Premium Steel Widgets',45.99,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(2,'PROD-002','Aluminum Components',32.50,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(3,'PROD-003','Industrial Fasteners',12.75,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(4,'PROD-004','Precision Bearings',89.99,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(5,'PROD-005','Electronic Modules',155.00,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(6,'PROD-006','Rubber Seals',8.99,'2026-04-29 12:49:18','2026-04-29 12:49:18');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_histories`
--

DROP TABLE IF EXISTS `purchase_order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `edited_by` int unsigned DEFAULT NULL,
  `action` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'edit',
  `old_purchase_order_json` longtext COLLATE utf8mb4_general_ci,
  `old_items_json` longtext COLLATE utf8mb4_general_ci,
  `new_purchase_order_json` longtext COLLATE utf8mb4_general_ci,
  `new_items_json` longtext COLLATE utf8mb4_general_ci,
  `change_summary` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id_created_at` (`purchase_order_id`,`created_at`),
  KEY `edited_by` (`edited_by`),
  KEY `action` (`action`),
  CONSTRAINT `purchase_order_histories_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE SET NULL,
  CONSTRAINT `purchase_order_histories_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_histories`
--

LOCK TABLES `purchase_order_histories` WRITE;
/*!40000 ALTER TABLE `purchase_order_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchase_order_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` decimal(12,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,2,100.00,33.00,3300.00),(2,2,2,100.00,33.00,3300.00);
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int unsigned NOT NULL,
  `po_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `payment_term` int unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `void_reason` text COLLATE utf8mb4_general_ci,
  `voided_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_id_po_no` (`supplier_id`,`po_no`),
  KEY `supplier_id_date` (`supplier_id`,`date`),
  KEY `due_date` (`due_date`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,4,'PO-100','2026-04-29',45,'2026-06-13',3300.00,'active',NULL,NULL,'2026-04-29 15:14:24','2026-04-29 15:14:24'),(2,4,'PO-101','2026-04-30',20,'2026-05-20',3300.00,'active',NULL,NULL,'2026-04-29 17:00:34','2026-04-29 17:00:34');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_limit` decimal(12,2) DEFAULT NULL,
  `payment_term` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Northstar Paper Supply','112 Warehouse Road, Quezon City','orders@northstarpaper.test','+63-2-8555-1101',500000.00,30,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(2,'Metro Packaging Traders','45 Trade Avenue, Pasig City','billing@metropackaging.test','+63-2-8555-2202',350000.00,15,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(3,'Prime Office Goods','78 Supply Street, Makati City','accounts@primeoffice.test','+63-2-8555-3303',250000.00,30,'2026-04-29 12:49:18','2026-04-29 12:49:18'),(4,'Harbor Industrial Materials','9 Portside Lane, Manila','ap@harborindustrial.test','+63-2-8555-4404',750000.00,45,'2026-04-29 12:49:18','2026-04-29 12:49:18');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'cashier',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Admin','admin','$2y$12$AgnNCqIuHzHpXBCTcPgxB.FdLSQ0b8EhIY4ZspuPGQ7zBS.YmyQZa','admin',1,'2026-04-29 12:49:16','2026-04-29 12:49:16'),(2,'Maria Santos','maria.santos','$2y$12$44Swvw.y0ZFk9h9raSekk.r56dLfA1NXWBbxWyUot4/8bBDxsgyiK','cashier',1,'2026-04-29 12:49:17','2026-04-29 12:49:17'),(3,'Juan Rodriguez','juan.rodriguez','$2y$12$NEV7Eq9f/zqXsm5QAo1gPOVjUn7uNkIDxuc.GMs3CuMxWOqiZGKN2','cashier',1,'2026-04-29 12:49:17','2026-04-29 12:49:17'),(4,'Angela Martinez','angela.martinez','$2y$12$aBqSvjLVYqW3QyDJzTNBXOzhNqi.OIpzNWZv6OzNmViy3o94i5vee','cashier',1,'2026-04-29 12:49:17','2026-04-29 12:49:17'),(5,'Carlos Perez','carlos.perez','$2y$12$V13NxX4Dgjj/R.IQzrMyReTonKuZqrHKQzWJtYowJuZeFrFwqTyLS','cashier',1,'2026-04-29 12:49:17','2026-04-29 12:49:17'),(6,'Isabel Gonzalez','isabel.gonzalez','$2y$12$E89nwnqs2uc4ANgEXXKmb..rD4HWT33JQT8LUjsKx2IE1RTj2d42W','cashier',1,'2026-04-29 12:49:17','2026-04-29 12:49:17'),(7,'Diego Lopez','diego.lopez','$2y$12$so2LFgspiQ7jdpeNP3vQcOiBliVZ6UQuI/HbRYF2CWHO28oMsTC0i','cashier',0,'2026-04-29 12:49:17','2026-04-29 12:49:17');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-30  1:43:30
