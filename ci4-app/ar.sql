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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `banks`
--

LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
INSERT INTO `banks` VALUES (1,'BDO','BDO','BDO-123','2026-05-05 15:22:20','2026-05-05 15:22:20');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boa`
--

LOCK TABLES `boa` WRITE;
/*!40000 ALTER TABLE `boa` DISABLE KEYS */;
INSERT INTO `boa` VALUES (1,'2026-05-06',6,'1000',2,100000.00,20000.00,0.00,NULL,NULL,NULL,0.00,0.00,'2026-05-06 17:35:17','2026-05-06 17:35:17');
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
INSERT INTO `cashier_receipt_ranges` VALUES (1,1,1000,1999,1001,'active',NULL);
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
  `forwarded_balance` decimal(12,2) DEFAULT '0.00',
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
INSERT INTO `clients` VALUES (1,'Acme Corporation','123 Business Ave, Suite 100, New York, NY 10001','contact@acmecorp.com','+1-212-555-0101',1000000.00,30,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(2,'Global Industries Inc','456 Commerce Boulevard, Los Angeles, CA 90001','info@globalindustries.com','+1-213-555-0202',750000.00,15,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(3,'Tech Solutions Ltd','789 Digital Drive, San Francisco, CA 94102','sales@techsolutions.com','+1-415-555-0303',500000.00,30,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(4,'Manufacturing Pro Services','321 Industrial Park, Chicago, IL 60601','procurement@mfgpro.com','+1-312-555-0404',850000.00,45,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(5,'Enterprise Solutions Group','654 Corporate Plaza, Houston, TX 77002','accounts@enterprisesolutions.com','+1-713-555-0505',1200000.00,30,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(6,'Premier Distribution Network','987 Trade Center, Miami, FL 33101','orders@premierdist.com','+1-305-555-0606',650000.00,20,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03');
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
INSERT INTO `deliveries` VALUES (1,6,'DR-109','2026-05-06',20,'2026-05-26',20000.00,'active',NULL,NULL,'2026-05-06 17:36:32','2026-05-06 17:36:32');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_histories`
--

LOCK TABLES `delivery_histories` WRITE;
/*!40000 ALTER TABLE `delivery_histories` DISABLE KEYS */;
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
  `qty` decimal(12,5) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `delivery_items_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `delivery_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_items`
--

LOCK TABLES `delivery_items` WRITE;
/*!40000 ALTER TABLE `delivery_items` DISABLE KEYS */;
INSERT INTO `delivery_items` VALUES (1,1,2,1000.00000,20.00,20000.00);
/*!40000 ALTER TABLE `delivery_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_pickup_allocations`
--

DROP TABLE IF EXISTS `delivery_pickup_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_pickup_allocations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int unsigned NOT NULL,
  `purchase_order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty_allocated` decimal(12,5) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_id` (`delivery_id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `delivery_pickup_allocations_delivery_id_foreign` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `delivery_pickup_allocations_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `delivery_pickup_allocations_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_pickup_allocations`
--

LOCK TABLES `delivery_pickup_allocations` WRITE;
/*!40000 ALTER TABLE `delivery_pickup_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `delivery_pickup_allocations` ENABLE KEYS */;
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
  `qty` decimal(12,5) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger`
--

LOCK TABLES `ledger` WRITE;
/*!40000 ALTER TABLE `ledger` DISABLE KEYS */;
INSERT INTO `ledger` VALUES (1,6,'2026-05-06',NULL,'1000',NULL,NULL,0.00,100000.00,NULL,0.00,-100000.00,NULL,2,'2026-05-06 17:35:17'),(2,6,'2026-05-06','DR-109',NULL,1000.00000,20.00,20000.00,0.00,NULL,0.00,-80000.00,1,NULL,'2026-05-06 17:36:32');
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
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (203,'2026-04-21-065247','App\\Database\\Migrations\\CreateUsers','default','App',1777994495,1),(204,'2026-04-21-065248','App\\Database\\Migrations\\CreateBanks','default','App',1777994496,1),(205,'2026-04-21-065248','App\\Database\\Migrations\\CreateClients','default','App',1777994496,1),(206,'2026-04-21-065248','App\\Database\\Migrations\\CreateProducts','default','App',1777994496,1),(207,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveries','default','App',1777994496,1),(208,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveryItems','default','App',1777994496,1),(209,'2026-04-21-135124','App\\Database\\Migrations\\CreateCashiers','default','App',1777994496,1),(210,'2026-04-21-135125','App\\Database\\Migrations\\CreateCashierReceiptRanges','default','App',1777994496,1),(211,'2026-04-21-135126','App\\Database\\Migrations\\CreatePayments','default','App',1777994496,1),(212,'2026-04-21-150100','App\\Database\\Migrations\\CreatePaymentAllocations','default','App',1777994496,1),(213,'2026-04-21-150200','App\\Database\\Migrations\\CreateLedger','default','App',1777994496,1),(214,'2026-04-21-155000','App\\Database\\Migrations\\CreateOtherAccounts','default','App',1777994496,1),(215,'2026-04-21-160000','App\\Database\\Migrations\\CreateBoa','default','App',1777994496,1),(216,'2026-04-22-090000','App\\Database\\Migrations\\UpdateOtherAccountsType','default','App',1777994496,1),(217,'2026-04-22-091000','App\\Database\\Migrations\\UpdateBoaForOtherAccounts','default','App',1777994496,1),(218,'2026-04-24-100000','App\\Database\\Migrations\\AddClientCreditFields','default','App',1777994496,1),(219,'2026-04-24-100100','App\\Database\\Migrations\\AddDeliveryTermsFields','default','App',1777994496,1),(220,'2026-04-25-090000','App\\Database\\Migrations\\AddOtherAccountsToLedger','default','App',1777994496,1),(221,'2026-04-29-000000','App\\Database\\Migrations\\CreateDeliveryHistories','default','App',1777994496,1),(222,'2026-04-29-010000','App\\Database\\Migrations\\CreatePayablesTables','default','App',1777994496,1),(223,'2026-05-02-000000','App\\Database\\Migrations\\CreateProductClientPrices','default','App',1777994496,1),(224,'2026-05-04-000000','App\\Database\\Migrations\\CreateSupplierOrdersAndPickupLinks','default','App',1777994497,1),(225,'2026-05-04-020000','App\\Database\\Migrations\\CreateDeliveryPickupAllocations','default','App',1777994497,1),(226,'2026-05-05-000000','App\\Database\\Migrations\\CreateSupplierOrderHistories','default','App',1777994497,1),(227,'2026-05-06-120000','App\\Database\\Migrations\\UpdateQtyPrecision','default','App',1778087621,2),(228,'2026-05-06-130000','App\\Database\\Migrations\\AddForwardedBalances','default','App',1778087621,2);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payable_allocations`
--

LOCK TABLES `payable_allocations` WRITE;
/*!40000 ALTER TABLE `payable_allocations` DISABLE KEYS */;
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
  `qty` decimal(12,5) DEFAULT NULL,
  `price` decimal(12,2) DEFAULT NULL,
  `payables` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment` decimal(12,2) NOT NULL DEFAULT '0.00',
  `account_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `other_accounts` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `supplier_order_id` int unsigned DEFAULT NULL,
  `supplier_order_item_id` int unsigned DEFAULT NULL,
  `po_balance` decimal(12,5) DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payable_ledger`
--

LOCK TABLES `payable_ledger` WRITE;
/*!40000 ALTER TABLE `payable_ledger` DISABLE KEYS */;
INSERT INTO `payable_ledger` VALUES (1,4,'2026-05-05','RR-100',NULL,1000.00000,33.00,33000.00,0.00,NULL,0.00,33000.00,1,1,9999999.99999,1,NULL,'2026-05-05 15:23:36');
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payables`
--

LOCK TABLES `payables` WRITE;
/*!40000 ALTER TABLE `payables` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (1,2,1,20000.00,'2026-05-06 17:37:03');
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
INSERT INTO `payments` VALUES (2,6,1,1000,'2026-05-06','cash',100000.00,20000.00,0.00,NULL,NULL,1,'posted','2026-05-06 17:35:17','2026-05-06 17:37:03');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_client_prices`
--

DROP TABLE IF EXISTS `product_client_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_client_prices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int unsigned NOT NULL,
  `client_id` int unsigned NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id_client_id` (`product_id`,`client_id`),
  KEY `product_id` (`product_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `product_client_prices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_client_prices_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_client_prices`
--

LOCK TABLES `product_client_prices` WRITE;
/*!40000 ALTER TABLE `product_client_prices` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_client_prices` ENABLE KEYS */;
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
INSERT INTO `products` VALUES (1,'PROD-001','Premium Steel Widgets',45.99,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(2,'PROD-002','Aluminum Components',32.50,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(3,'PROD-003','Industrial Fasteners',12.75,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(4,'PROD-004','Precision Bearings',89.99,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(5,'PROD-005','Electronic Modules',155.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(6,'PROD-006','Rubber Seals',8.99,'2026-05-05 15:22:03','2026-05-05 15:22:03');
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
  `supplier_order_item_id` int unsigned DEFAULT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` decimal(12,5) DEFAULT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `po_qty_balance_after` decimal(12,5) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,1,2,1000.00000,33.00,33000.00,9999999.99999);
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
  `supplier_order_id` int unsigned DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,4,1,'RR-100','2026-05-05',45,'2026-06-19',33000.00,'active',NULL,NULL,'2026-05-05 15:23:36','2026-05-05 15:23:36');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_order_histories`
--

DROP TABLE IF EXISTS `supplier_order_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_order_histories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_order_id` int unsigned NOT NULL,
  `edited_by` int unsigned DEFAULT NULL,
  `action` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'edit',
  `old_supplier_order_json` longtext COLLATE utf8mb4_general_ci,
  `old_items_json` longtext COLLATE utf8mb4_general_ci,
  `new_supplier_order_json` longtext COLLATE utf8mb4_general_ci,
  `new_items_json` longtext COLLATE utf8mb4_general_ci,
  `change_summary` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_order_id_created_at` (`supplier_order_id`,`created_at`),
  KEY `edited_by` (`edited_by`),
  KEY `action` (`action`),
  CONSTRAINT `supplier_order_histories_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE SET NULL,
  CONSTRAINT `supplier_order_histories_supplier_order_id_foreign` FOREIGN KEY (`supplier_order_id`) REFERENCES `supplier_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_order_histories`
--

LOCK TABLES `supplier_order_histories` WRITE;
/*!40000 ALTER TABLE `supplier_order_histories` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_order_histories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_order_items`
--

DROP TABLE IF EXISTS `supplier_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_order_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty_ordered` decimal(12,5) DEFAULT NULL,
  `qty_picked_up` decimal(12,5) DEFAULT '0.00000',
  `qty_balance` decimal(12,5) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_order_id` (`supplier_order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `supplier_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `supplier_order_items_supplier_order_id_foreign` FOREIGN KEY (`supplier_order_id`) REFERENCES `supplier_orders` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_order_items`
--

LOCK TABLES `supplier_order_items` WRITE;
/*!40000 ALTER TABLE `supplier_order_items` DISABLE KEYS */;
INSERT INTO `supplier_order_items` VALUES (1,1,2,9999999.99999,1000.00000,9999999.99999,'2026-05-05 15:23:02','2026-05-05 15:23:36');
/*!40000 ALTER TABLE `supplier_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_orders`
--

DROP TABLE IF EXISTS `supplier_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supplier_orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int unsigned NOT NULL,
  `po_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `void_reason` text COLLATE utf8mb4_general_ci,
  `voided_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supplier_id_po_no` (`supplier_id`,`po_no`),
  KEY `supplier_id_date` (`supplier_id`,`date`),
  CONSTRAINT `supplier_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_orders`
--

LOCK TABLES `supplier_orders` WRITE;
/*!40000 ALTER TABLE `supplier_orders` DISABLE KEYS */;
INSERT INTO `supplier_orders` VALUES (1,4,'PO-100','2026-05-05','active',NULL,NULL,'2026-05-05 15:23:02','2026-05-05 15:23:02');
/*!40000 ALTER TABLE `supplier_orders` ENABLE KEYS */;
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
  `forwarded_balance` decimal(12,2) DEFAULT '0.00',
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
INSERT INTO `suppliers` VALUES (1,'Northstar Paper Supply','112 Warehouse Road, Quezon City','orders@northstarpaper.test','+63-2-8555-1101',500000.00,30,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(2,'Metro Packaging Traders','45 Trade Avenue, Pasig City','billing@metropackaging.test','+63-2-8555-2202',350000.00,15,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(3,'Prime Office Goods','78 Supply Street, Makati City','accounts@primeoffice.test','+63-2-8555-3303',250000.00,30,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03'),(4,'Harbor Industrial Materials','9 Portside Lane, Manila','ap@harborindustrial.test','+63-2-8555-4404',750000.00,45,0.00,'2026-05-05 15:22:03','2026-05-05 15:22:03');
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
INSERT INTO `users` VALUES (1,'System Admin','admin','$2y$12$IHZsXihcYy670b9g3Y7s3eJ9j6YCvUmOHrCSksbhrePXl8H6EImT.','admin',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(2,'Maria Santos','maria.santos','$2y$12$jLcrlZ9/gUCh5h6y7xvTYeViCiERFM5JQwRk3G43S9ACdxEO/7ECq','cashier',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(3,'Juan Rodriguez','juan.rodriguez','$2y$12$ozAwqhe3GCnDKbXf1D79o.WwaMTaPCWSWRIC0REf6ttIbYjRYt/eC','cashier',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(4,'Angela Martinez','angela.martinez','$2y$12$yt08LI.k3aRFVziSNKJ/0u0OgnrbK4nS9/Be.lP7NjdzQRwy4OhTO','cashier',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(5,'Carlos Perez','carlos.perez','$2y$12$uyWHm8WE6Xfnl7aUYnm9fupi2q.PmFgFGMVD4WwkNJPpTzFZggATK','cashier',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(6,'Isabel Gonzalez','isabel.gonzalez','$2y$12$xrHvZ.neJUKV5Kj6/0h7cuoKwNyXKFZo4TnvoXYpCAXfUMWyBBJtu','cashier',1,'2026-05-05 15:22:02','2026-05-05 15:22:02'),(7,'Diego Lopez','diego.lopez','$2y$12$r1OUMG.XoMH33erBUbC6de.kOeHZS2ZNJg8UC.0czFdqXwgprwriW','cashier',0,'2026-05-05 15:22:02','2026-05-05 15:22:02');
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

-- Dump completed on 2026-05-07  1:42:54
