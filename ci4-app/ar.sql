-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table ar.banks
CREATE TABLE IF NOT EXISTS `banks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.banks: ~0 rows (approximately)
DELETE FROM `banks`;

-- Dumping structure for table ar.clients
CREATE TABLE IF NOT EXISTS `clients` (
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

-- Dumping data for table ar.clients: ~6 rows (approximately)
DELETE FROM `clients`;
INSERT INTO `clients` (`id`, `name`, `address`, `email`, `phone`, `credit_limit`, `payment_term`, `created_at`, `updated_at`) VALUES
	(1, 'Acme Corporation', '123 Business Ave, Suite 100, New York, NY 10001', 'contact@acmecorp.com', '+1-212-555-0101', 1000000.00, 30, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(2, 'Global Industries Inc', '456 Commerce Boulevard, Los Angeles, CA 90001', 'info@globalindustries.com', '+1-213-555-0202', 750000.00, 15, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(3, 'Tech Solutions Ltd', '789 Digital Drive, San Francisco, CA 94102', 'sales@techsolutions.com', '+1-415-555-0303', 500000.00, 30, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(4, 'Manufacturing Pro Services', '321 Industrial Park, Chicago, IL 60601', 'procurement@mfgpro.com', '+1-312-555-0404', 850000.00, 45, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(5, 'Enterprise Solutions Group', '654 Corporate Plaza, Houston, TX 77002', 'accounts@enterprisesolutions.com', '+1-713-555-0505', 1200000.00, 30, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(6, 'Premier Distribution Network', '987 Trade Center, Miami, FL 33101', 'orders@premierdist.com', '+1-305-555-0606', 650000.00, 20, '2026-04-25 09:23:04', '2026-04-25 09:23:04');

-- Dumping structure for table ar.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `product_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `product_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.products: ~6 rows (approximately)
DELETE FROM `products`;
INSERT INTO `products` (`id`, `product_id`, `product_name`, `unit_price`, `created_at`, `updated_at`) VALUES
	(1, 'PROD-001', 'Premium Steel Widgets', 45.99, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(2, 'PROD-002', 'Aluminum Components', 32.50, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(3, 'PROD-003', 'Industrial Fasteners', 12.75, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(4, 'PROD-004', 'Precision Bearings', 89.99, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(5, 'PROD-005', 'Electronic Modules', 155.00, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(6, 'PROD-006', 'Rubber Seals', 8.99, '2026-04-25 09:23:04', '2026-04-25 09:23:04');

-- Dumping structure for table ar.users
CREATE TABLE IF NOT EXISTS `users` (
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

-- Dumping data for table ar.users: ~7 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `type`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'System Admin', 'admin', '$2y$12$UEyYYcLVKm/ov47lQqGAc.Ju4Z9KDkOpeLSIpaIAWGjtfE9zSYVKK', 'admin', 1, '2026-04-25 09:23:03', '2026-04-25 09:23:03'),
	(2, 'Maria Santos', 'maria.santos', '$2y$12$96Efe20YDuCZHp.SzpqwwuweRiWDsEOxTaplP1477W3Dj5P3k4PcO', 'cashier', 1, '2026-04-25 09:23:03', '2026-04-25 09:23:03'),
	(3, 'Juan Rodriguez', 'juan.rodriguez', '$2y$12$rlgPbzR2UH5gDaaka9FIpuO9D59.fMP09CM6P7mDS95.nT2r/znAW', 'cashier', 1, '2026-04-25 09:23:03', '2026-04-25 09:23:03'),
	(4, 'Angela Martinez', 'angela.martinez', '$2y$12$9HcIYwoMxbgEeIMBPZ2Cc.OaAgm25BKi0dKI0EbkK4j77VUwG7Wqe', 'cashier', 1, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(5, 'Carlos Perez', 'carlos.perez', '$2y$12$3rPQ7ycnzm2KAnkKqUHoau0gLAy7kc.5C0/SVG.H5i.nOOG698c/O', 'cashier', 1, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(6, 'Isabel Gonzalez', 'isabel.gonzalez', '$2y$12$/jRW.O5SY6hrYfspmskvseZNyYOHHiP/Oo1XxQ/SjZffKnZOSPqdy', 'cashier', 1, '2026-04-25 09:23:04', '2026-04-25 09:23:04'),
	(7, 'Diego Lopez', 'diego.lopez', '$2y$12$8j9TbWUTiDbyzOEVmial3.hK70I/qfBiMW/qluirGIi4R6zxFxJDi', 'cashier', 0, '2026-04-25 09:23:04', '2026-04-25 09:23:04');

-- Dumping structure for table ar.other_accounts
CREATE TABLE IF NOT EXISTS `other_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('dr','cr') COLLATE utf8mb4_general_ci DEFAULT 'dr',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.other_accounts: ~0 rows (approximately)
DELETE FROM `other_accounts`;

-- Dumping structure for table ar.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.migrations: ~16 rows (approximately)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
	(91, '2026-04-21-065247', 'App\\Database\\Migrations\\CreateUsers', 'default', 'App', 1777108973, 1),
	(92, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateBanks', 'default', 'App', 1777108973, 1),
	(93, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateClients', 'default', 'App', 1777108973, 1),
	(94, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateProducts', 'default', 'App', 1777108973, 1),
	(95, '2026-04-21-072400', 'App\\Database\\Migrations\\CreateDeliveries', 'default', 'App', 1777108973, 1),
	(96, '2026-04-21-072400', 'App\\Database\\Migrations\\CreateDeliveryItems', 'default', 'App', 1777108974, 1),
	(97, '2026-04-21-135124', 'App\\Database\\Migrations\\CreateCashiers', 'default', 'App', 1777108974, 1),
	(98, '2026-04-21-135125', 'App\\Database\\Migrations\\CreateCashierReceiptRanges', 'default', 'App', 1777108974, 1),
	(99, '2026-04-21-135126', 'App\\Database\\Migrations\\CreatePayments', 'default', 'App', 1777108974, 1),
	(100, '2026-04-21-150100', 'App\\Database\\Migrations\\CreatePaymentAllocations', 'default', 'App', 1777108974, 1),
	(101, '2026-04-21-150200', 'App\\Database\\Migrations\\CreateLedger', 'default', 'App', 1777108974, 1),
	(102, '2026-04-21-155000', 'App\\Database\\Migrations\\CreateOtherAccounts', 'default', 'App', 1777108974, 1),
	(103, '2026-04-21-160000', 'App\\Database\\Migrations\\CreateBoa', 'default', 'App', 1777108974, 1),
	(104, '2026-04-22-090000', 'App\\Database\\Migrations\\UpdateOtherAccountsType', 'default', 'App', 1777108974, 1),
	(105, '2026-04-22-091000', 'App\\Database\\Migrations\\UpdateBoaForOtherAccounts', 'default', 'App', 1777108974, 1),
	(106, '2026-04-24-100000', 'App\\Database\\Migrations\\AddClientCreditFields', 'default', 'App', 1777108974, 1),
	(107, '2026-04-24-100100', 'App\\Database\\Migrations\\AddDeliveryTermsFields', 'default', 'App', 1777108974, 1),
	(108, '2026-04-25-090000', 'App\\Database\\Migrations\\AddOtherAccountsToLedger', 'default', 'App', 1777108974, 1);

-- Dumping structure for table ar.deliveries
CREATE TABLE IF NOT EXISTS `deliveries` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.deliveries: ~0 rows (approximately)
DELETE FROM `deliveries`;

-- Dumping structure for table ar.cashier_receipt_ranges
CREATE TABLE IF NOT EXISTS `cashier_receipt_ranges` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.cashier_receipt_ranges: ~0 rows (approximately)
DELETE FROM `cashier_receipt_ranges`;

-- Dumping structure for table ar.payments
CREATE TABLE IF NOT EXISTS `payments` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.payments: ~0 rows (approximately)
DELETE FROM `payments`;

-- Dumping structure for table ar.delivery_items
CREATE TABLE IF NOT EXISTS `delivery_items` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.delivery_items: ~0 rows (approximately)
DELETE FROM `delivery_items`;

-- Dumping structure for table ar.payment_allocations
CREATE TABLE IF NOT EXISTS `payment_allocations` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.payment_allocations: ~0 rows (approximately)
DELETE FROM `payment_allocations`;

-- Dumping structure for table ar.ledger
CREATE TABLE IF NOT EXISTS `ledger` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.ledger: ~0 rows (approximately)
DELETE FROM `ledger`;

-- Dumping structure for table ar.boa
CREATE TABLE IF NOT EXISTS `boa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `payor` int unsigned NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_id` int unsigned NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.boa: ~0 rows (approximately)
DELETE FROM `boa`;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;


