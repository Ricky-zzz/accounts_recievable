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
INSERT INTO `banks` VALUES (1,'BDO','BDO','BDO-123','2026-04-28 16:43:52','2026-04-28 16:43:52');
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `boa`
--

LOCK TABLES `boa` WRITE;
/*!40000 ALTER TABLE `boa` DISABLE KEYS */;
INSERT INTO `boa` VALUES (1,'2026-04-28',1,'10000',1,201.00,200.34,0.00,NULL,NULL,NULL,0.00,0.00,'2026-04-28 17:17:36','2026-04-28 17:17:36'),(2,'2026-04-28',1,'10000',1,0.00,0.00,0.66,NULL,NULL,'left',0.00,0.00,'2026-04-28 17:17:36','2026-04-28 17:17:36'),(3,'2026-04-28',5,'10001',2,3300.00,3300.00,0.00,NULL,NULL,NULL,0.00,0.00,'2026-04-28 17:42:43','2026-04-28 17:42:43'),(4,'2026-04-28',1,'10002',3,31000.00,32000.00,0.00,NULL,NULL,NULL,0.00,0.00,'2026-04-28 18:00:07','2026-04-28 18:00:07'),(5,'2026-04-28',1,'10002',3,0.00,0.00,0.00,'Sales Discount',NULL,NULL,1000.00,0.00,'2026-04-28 18:00:07','2026-04-28 18:00:07');
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
INSERT INTO `cashier_receipt_ranges` VALUES (1,1,10000,19999,10003,'active',NULL);
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
INSERT INTO `clients` VALUES (1,'Acme Corporation','123 Business Ave, Suite 100, New York, NY 10001','contact@acmecorp.com','+1-212-555-0101',1000000.00,30,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(2,'Global Industries Inc','456 Commerce Boulevard, Los Angeles, CA 90001','info@globalindustries.com','+1-213-555-0202',750000.00,15,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(3,'Tech Solutions Ltd','789 Digital Drive, San Francisco, CA 94102','sales@techsolutions.com','+1-415-555-0303',500000.00,30,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(4,'Manufacturing Pro Services','321 Industrial Park, Chicago, IL 60601','procurement@mfgpro.com','+1-312-555-0404',850000.00,45,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(5,'Enterprise Solutions Group','654 Corporate Plaza, Houston, TX 77002','accounts@enterprisesolutions.com','+1-713-555-0505',1200000.00,30,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(6,'Premier Distribution Network','987 Trade Center, Miami, FL 33101','orders@premierdist.com','+1-305-555-0606',650000.00,20,'2026-04-25 09:23:04','2026-04-25 09:23:04');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
INSERT INTO `deliveries` VALUES (1,1,'DR-101','2026-04-28',30,'2026-05-28',32.55,'active',NULL,NULL,'2026-04-28 15:28:34','2026-04-28 15:28:34'),(2,1,'DR-102','2026-04-28',30,'2026-05-28',155.00,'active',NULL,NULL,'2026-04-28 15:45:16','2026-04-28 15:45:16'),(3,1,'DR-104','2026-04-28',30,'2026-05-28',12.79,'active',NULL,NULL,'2026-04-28 15:50:23','2026-04-28 15:50:23'),(4,5,'DR-106','2026-04-28',30,'2026-05-28',3300.00,'active',NULL,NULL,'2026-04-28 17:41:07','2026-04-28 17:41:07'),(5,1,'DR-107','2026-04-28',30,'2026-05-28',32000.00,'active',NULL,NULL,'2026-04-28 17:59:47','2026-04-28 17:59:47'),(6,2,'DR-108','2026-04-28',15,'2026-05-13',155.00,'voided','mistake','2026-04-28 18:16:25','2026-04-28 18:16:17','2026-04-28 18:16:25'),(7,1,'DR-108','2026-04-28',30,'2026-05-28',1860.00,'active',NULL,NULL,'2026-04-28 19:00:36','2026-04-28 19:03:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_histories`
--

LOCK TABLES `delivery_histories` WRITE;
/*!40000 ALTER TABLE `delivery_histories` DISABLE KEYS */;
INSERT INTO `delivery_histories` VALUES (1,6,1,'void','{\"id\":\"6\",\"client_id\":\"2\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"15\",\"due_date\":\"2026-05-13\",\"total_amount\":\"155.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-28 18:16:17\",\"updated_at\":\"2026-04-28 18:16:17\",\"client_name\":\"Global Industries Inc\",\"allocated_amount\":\"0.00\",\"balance\":\"155.00\"}','[{\"id\":\"6\",\"delivery_id\":\"6\",\"product_id\":\"5\",\"qty\":\"1.00\",\"unit_price\":\"155.00\",\"line_total\":\"155.00\",\"product_name\":\"Electronic Modules\"}]','{\"id\":\"6\",\"client_id\":\"2\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"15\",\"due_date\":\"2026-05-13\",\"total_amount\":\"155.00\",\"status\":\"voided\",\"void_reason\":\"mistake\",\"voided_at\":\"2026-04-28 18:16:25\",\"created_at\":\"2026-04-28 18:16:17\",\"updated_at\":\"2026-04-28 18:16:25\",\"client_name\":\"Global Industries Inc\",\"allocated_amount\":\"0.00\",\"balance\":\"155.00\"}','[{\"id\":\"6\",\"delivery_id\":\"6\",\"product_id\":\"5\",\"qty\":\"1.00\",\"unit_price\":\"155.00\",\"line_total\":\"155.00\",\"product_name\":\"Electronic Modules\"}]','Voided delivery. Reason: mistake','2026-04-28 18:16:25'),(2,7,1,'edit','{\"id\":\"7\",\"client_id\":\"1\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"30\",\"due_date\":\"2026-05-28\",\"total_amount\":\"155.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-28 19:00:36\",\"updated_at\":\"2026-04-28 19:00:36\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"155.00\"}','[{\"id\":\"7\",\"delivery_id\":\"7\",\"product_id\":\"5\",\"qty\":\"1.00\",\"unit_price\":\"155.00\",\"line_total\":\"155.00\",\"product_name\":\"Electronic Modules\"}]','{\"id\":\"7\",\"client_id\":\"1\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"30\",\"due_date\":\"2026-05-28\",\"total_amount\":\"545.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-28 19:00:36\",\"updated_at\":\"2026-04-28 19:00:49\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"545.00\"}','[{\"id\":\"8\",\"delivery_id\":\"7\",\"product_id\":\"5\",\"qty\":\"1.00\",\"unit_price\":\"155.00\",\"line_total\":\"155.00\",\"product_name\":\"Electronic Modules\"},{\"id\":\"9\",\"delivery_id\":\"7\",\"product_id\":\"2\",\"qty\":\"12.00\",\"unit_price\":\"32.50\",\"line_total\":\"390.00\",\"product_name\":\"Aluminum Components\"}]','Total changed from 155.00 to 545.00; Item count changed from 1 to 2.','2026-04-28 19:00:49'),(3,7,1,'edit','{\"id\":\"7\",\"client_id\":\"1\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"30\",\"due_date\":\"2026-05-28\",\"total_amount\":\"545.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-28 19:00:36\",\"updated_at\":\"2026-04-28 19:00:49\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"545.00\"}','[{\"id\":\"8\",\"delivery_id\":\"7\",\"product_id\":\"5\",\"qty\":\"1.00\",\"unit_price\":\"155.00\",\"line_total\":\"155.00\",\"product_name\":\"Electronic Modules\"},{\"id\":\"9\",\"delivery_id\":\"7\",\"product_id\":\"2\",\"qty\":\"12.00\",\"unit_price\":\"32.50\",\"line_total\":\"390.00\",\"product_name\":\"Aluminum Components\"}]','{\"id\":\"7\",\"client_id\":\"1\",\"dr_no\":\"DR-108\",\"date\":\"2026-04-28\",\"payment_term\":\"30\",\"due_date\":\"2026-05-28\",\"total_amount\":\"1860.00\",\"status\":\"active\",\"void_reason\":null,\"voided_at\":null,\"created_at\":\"2026-04-28 19:00:36\",\"updated_at\":\"2026-04-28 19:03:46\",\"client_name\":\"Acme Corporation\",\"allocated_amount\":\"0.00\",\"balance\":\"1860.00\"}','[{\"id\":\"10\",\"delivery_id\":\"7\",\"product_id\":\"5\",\"qty\":\"12.00\",\"unit_price\":\"155.00\",\"line_total\":\"1860.00\",\"product_name\":\"Electronic Modules\"}]','Total changed from 545.00 to 1,860.00; Item count changed from 2 to 1.','2026-04-28 19:03:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_items`
--

LOCK TABLES `delivery_items` WRITE;
/*!40000 ALTER TABLE `delivery_items` DISABLE KEYS */;
INSERT INTO `delivery_items` VALUES (1,1,2,1.00,32.55,32.55),(2,2,5,1.00,155.00,155.00),(3,3,3,1.00,12.79,12.79),(4,4,2,100.00,33.00,3300.00),(5,5,2,1000.00,32.00,32000.00),(6,6,5,1.00,155.00,155.00),(10,7,5,12.00,155.00,1860.00);
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
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ledger`
--

LOCK TABLES `ledger` WRITE;
/*!40000 ALTER TABLE `ledger` DISABLE KEYS */;
INSERT INTO `ledger` VALUES (1,1,'2026-04-28','DR-101',NULL,1.00,32.55,32.55,0.00,NULL,0.00,32.55,1,NULL,'2026-04-28 15:28:34'),(2,1,'2026-04-28','DR-102',NULL,1.00,155.00,155.00,0.00,NULL,0.00,187.55,2,NULL,'2026-04-28 15:45:16'),(3,1,'2026-04-28','DR-104',NULL,1.00,12.79,12.79,0.00,NULL,0.00,200.34,3,NULL,'2026-04-28 15:50:23'),(4,1,'2026-04-28',NULL,'10000',NULL,NULL,0.00,200.34,NULL,0.00,0.00,NULL,1,'2026-04-28 17:17:36'),(5,5,'2026-04-28','DR-106',NULL,100.00,33.00,3300.00,0.00,NULL,0.00,3300.00,4,NULL,'2026-04-28 17:41:07'),(6,5,'2026-04-28',NULL,'10001',NULL,NULL,0.00,3300.00,NULL,0.00,0.00,NULL,2,'2026-04-28 17:42:43'),(7,1,'2026-04-28','DR-107',NULL,1000.00,32.00,32000.00,0.00,NULL,0.00,32000.00,5,NULL,'2026-04-28 17:59:47'),(8,1,'2026-04-28',NULL,'10002',NULL,NULL,0.00,31000.00,NULL,0.00,1000.00,NULL,3,'2026-04-28 18:00:07'),(9,1,'2026-04-28',NULL,'10002',NULL,NULL,0.00,0.00,'Sales Discount',1000.00,0.00,NULL,3,'2026-04-28 18:00:07'),(10,2,'2026-04-28','DR-108',NULL,1.00,155.00,155.00,0.00,NULL,0.00,155.00,6,NULL,'2026-04-28 18:16:17'),(11,2,'2026-04-28','DR-108',NULL,NULL,NULL,0.00,0.00,'Voided',155.00,0.00,6,NULL,'2026-04-28 18:16:25'),(12,1,'2026-04-28','DR-108',NULL,1.00,155.00,155.00,0.00,NULL,0.00,155.00,7,NULL,'2026-04-28 19:00:36'),(13,1,'2026-04-28','DR-108',NULL,NULL,NULL,390.00,0.00,'Delivery Adjustment',0.00,545.00,7,NULL,'2026-04-28 19:00:49'),(14,1,'2026-04-28','DR-108',NULL,NULL,NULL,1315.00,0.00,'Delivery Adjustment',0.00,1860.00,7,NULL,'2026-04-28 19:03:46');
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
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (91,'2026-04-21-065247','App\\Database\\Migrations\\CreateUsers','default','App',1777108973,1),(92,'2026-04-21-065248','App\\Database\\Migrations\\CreateBanks','default','App',1777108973,1),(93,'2026-04-21-065248','App\\Database\\Migrations\\CreateClients','default','App',1777108973,1),(94,'2026-04-21-065248','App\\Database\\Migrations\\CreateProducts','default','App',1777108973,1),(95,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveries','default','App',1777108973,1),(96,'2026-04-21-072400','App\\Database\\Migrations\\CreateDeliveryItems','default','App',1777108974,1),(97,'2026-04-21-135124','App\\Database\\Migrations\\CreateCashiers','default','App',1777108974,1),(98,'2026-04-21-135125','App\\Database\\Migrations\\CreateCashierReceiptRanges','default','App',1777108974,1),(99,'2026-04-21-135126','App\\Database\\Migrations\\CreatePayments','default','App',1777108974,1),(100,'2026-04-21-150100','App\\Database\\Migrations\\CreatePaymentAllocations','default','App',1777108974,1),(101,'2026-04-21-150200','App\\Database\\Migrations\\CreateLedger','default','App',1777108974,1),(102,'2026-04-21-155000','App\\Database\\Migrations\\CreateOtherAccounts','default','App',1777108974,1),(103,'2026-04-21-160000','App\\Database\\Migrations\\CreateBoa','default','App',1777108974,1),(104,'2026-04-22-090000','App\\Database\\Migrations\\UpdateOtherAccountsType','default','App',1777108974,1),(105,'2026-04-22-091000','App\\Database\\Migrations\\UpdateBoaForOtherAccounts','default','App',1777108974,1),(106,'2026-04-24-100000','App\\Database\\Migrations\\AddClientCreditFields','default','App',1777108974,1),(107,'2026-04-24-100100','App\\Database\\Migrations\\AddDeliveryTermsFields','default','App',1777108974,1),(108,'2026-04-25-090000','App\\Database\\Migrations\\AddOtherAccountsToLedger','default','App',1777108974,1),(109,'2026-04-29-000000','App\\Database\\Migrations\\CreateDeliveryHistories','default','App',1777399444,2);
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (1,1,1,32.55,'2026-04-28 17:17:36'),(2,1,2,155.00,'2026-04-28 17:17:36'),(3,1,3,12.79,'2026-04-28 17:17:36'),(4,2,4,3300.00,'2026-04-28 17:42:43'),(5,3,5,32000.00,'2026-04-28 18:00:07');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,1,1,10000,'2026-04-28','cash',201.00,200.34,0.00,NULL,NULL,1,'posted','2026-04-28 17:17:36','2026-04-28 17:17:36'),(2,5,1,10001,'2026-04-28','cash',3300.00,3300.00,0.00,NULL,NULL,1,'posted','2026-04-28 17:42:43','2026-04-28 17:42:43'),(3,1,1,10002,'2026-04-28','cash',31000.00,32000.00,0.00,NULL,NULL,1,'posted','2026-04-28 18:00:07','2026-04-28 18:00:07');
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
INSERT INTO `products` VALUES (1,'PROD-001','Premium Steel Widgets',45.99,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(2,'PROD-002','Aluminum Components',32.50,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(3,'PROD-003','Industrial Fasteners',12.75,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(4,'PROD-004','Precision Bearings',89.99,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(5,'PROD-005','Electronic Modules',155.00,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(6,'PROD-006','Rubber Seals',8.99,'2026-04-25 09:23:04','2026-04-25 09:23:04');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Admin','admin','$2y$12$UEyYYcLVKm/ov47lQqGAc.Ju4Z9KDkOpeLSIpaIAWGjtfE9zSYVKK','admin',1,'2026-04-25 09:23:03','2026-04-25 09:23:03'),(2,'Maria Santos','maria.santos','$2y$12$96Efe20YDuCZHp.SzpqwwuweRiWDsEOxTaplP1477W3Dj5P3k4PcO','cashier',1,'2026-04-25 09:23:03','2026-04-25 09:23:03'),(3,'Juan Rodriguez','juan.rodriguez','$2y$12$rlgPbzR2UH5gDaaka9FIpuO9D59.fMP09CM6P7mDS95.nT2r/znAW','cashier',1,'2026-04-25 09:23:03','2026-04-25 09:23:03'),(4,'Angela Martinez','angela.martinez','$2y$12$9HcIYwoMxbgEeIMBPZ2Cc.OaAgm25BKi0dKI0EbkK4j77VUwG7Wqe','cashier',1,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(5,'Carlos Perez','carlos.perez','$2y$12$3rPQ7ycnzm2KAnkKqUHoau0gLAy7kc.5C0/SVG.H5i.nOOG698c/O','cashier',1,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(6,'Isabel Gonzalez','isabel.gonzalez','$2y$12$/jRW.O5SY6hrYfspmskvseZNyYOHHiP/Oo1XxQ/SjZffKnZOSPqdy','cashier',1,'2026-04-25 09:23:04','2026-04-25 09:23:04'),(7,'Diego Lopez','diego.lopez','$2y$12$8j9TbWUTiDbyzOEVmial3.hK70I/qfBiMW/qluirGIi4R6zxFxJDi','cashier',0,'2026-04-25 09:23:04','2026-04-25 09:23:04');
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

-- Dump completed on 2026-04-29  3:37:46
