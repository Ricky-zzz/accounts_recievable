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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.banks: ~3 rows (approximately)
DELETE FROM `banks`;
INSERT INTO `banks` (`id`, `bank_name`, `account_name`, `bank_number`, `created_at`, `updated_at`) VALUES
	(1, 'BDO', 'BDO', 'BDO-123', '2026-04-22 12:39:23', '2026-04-22 12:39:23'),
	(2, 'BPI', 'BPI', 'BPI-123', '2026-04-22 12:39:40', '2026-04-22 12:39:40'),
	(3, 'MBTC', 'MBTC', 'MBTC-123', '2026-04-22 12:39:58', '2026-04-22 12:39:58');

-- Dumping structure for table ar.boa
CREATE TABLE IF NOT EXISTS `boa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `payor` int unsigned NOT NULL,
  `reference` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_id` int unsigned NOT NULL,
  `MBTC` decimal(12,2) DEFAULT '0.00',
  `BPI` decimal(12,2) DEFAULT '0.00',
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.boa: ~3 rows (approximately)
DELETE FROM `boa`;
INSERT INTO `boa` (`id`, `date`, `payor`, `reference`, `payment_id`, `MBTC`, `BPI`, `BDO`, `ar_trade`, `ar_others`, `account_title`, `note`, `description`, `dr`, `cr`, `created_at`, `updated_at`) VALUES
	(1, '2026-04-22', 1, '1000', 1, 0.00, 0.00, 12000.00, 13000.00, 0.00, NULL, NULL, NULL, 0.00, 0.00, '2026-04-22 14:41:46', '2026-04-22 14:41:46'),
	(2, '2026-04-22', 1, NULL, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 'Commission Expenses', NULL, NULL, 1000.00, 0.00, '2026-04-22 14:41:46', '2026-04-22 14:41:46'),
	(3, '2026-04-22', 1, NULL, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 'Handling/Delivery Charges', NULL, NULL, 1000.00, 0.00, '2026-04-22 14:41:46', '2026-04-22 14:41:46');

-- Dumping structure for table ar.cashiers
CREATE TABLE IF NOT EXISTS `cashiers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.cashiers: ~1 rows (approximately)
DELETE FROM `cashiers`;
INSERT INTO `cashiers` (`id`, `name`, `username`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'cashier', 'cash123', '$2y$12$dh00quqf6bs..S3NrUp6/uTNk/qjldiBPwfmY5XmjCKEjRudzHdO.', 1, '2026-04-22 12:41:52', '2026-04-22 12:41:52');

-- Dumping structure for table ar.cashier_receipt_ranges
CREATE TABLE IF NOT EXISTS `cashier_receipt_ranges` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cashier_id` int unsigned NOT NULL,
  `start_no` int NOT NULL,
  `end_no` int NOT NULL,
  `next_no` int NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cashier_id` (`cashier_id`),
  CONSTRAINT `cashier_receipt_ranges_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.cashier_receipt_ranges: ~1 rows (approximately)
DELETE FROM `cashier_receipt_ranges`;
INSERT INTO `cashier_receipt_ranges` (`id`, `cashier_id`, `start_no`, `end_no`, `next_no`, `status`, `created_at`) VALUES
	(1, 1, 1000, 1999, 1001, 'active', NULL);

-- Dumping structure for table ar.clients
CREATE TABLE IF NOT EXISTS `clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.clients: ~2 rows (approximately)
DELETE FROM `clients`;
INSERT INTO `clients` (`id`, `name`, `address`, `email`, `phone`, `created_at`, `updated_at`) VALUES
	(1, 'Joyce Anne', 'lipa city', 'j@gmail.com', '322313213213', '2026-04-22 12:38:19', '2026-04-22 12:38:19'),
	(2, 'Mark Anthony', 'lipa city', 'm@gmail.com', '232323213', '2026-04-22 12:38:37', '2026-04-22 12:38:37');

-- Dumping structure for table ar.deliveries
CREATE TABLE IF NOT EXISTS `deliveries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `dr_no` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `void_reason` text COLLATE utf8mb4_general_ci,
  `voided_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_id_dr_no` (`client_id`,`dr_no`),
  KEY `client_id_date` (`client_id`,`date`),
  CONSTRAINT `deliveries_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.deliveries: ~2 rows (approximately)
DELETE FROM `deliveries`;
INSERT INTO `deliveries` (`id`, `client_id`, `dr_no`, `date`, `total_amount`, `status`, `void_reason`, `voided_at`, `created_at`, `updated_at`) VALUES
	(1, 1, 'DR-101', '2026-04-22', 9000.00, 'open', NULL, NULL, '2026-04-22 13:05:03', '2026-04-22 13:05:03'),
	(2, 1, 'DR-102', '2026-04-22', 8712.00, 'open', NULL, NULL, '2026-04-22 13:05:35', '2026-04-22 13:05:35');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.delivery_items: ~1 rows (approximately)
DELETE FROM `delivery_items`;
INSERT INTO `delivery_items` (`id`, `delivery_id`, `product_id`, `qty`, `unit_price`, `line_total`) VALUES
	(1, 1, 1, 100.00, 90.00, 9000.00),
	(2, 2, 2, 99.00, 88.00, 8712.00);

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.ledger: ~3 rows (approximately)
DELETE FROM `ledger`;
INSERT INTO `ledger` (`id`, `client_id`, `entry_date`, `dr_no`, `pr_no`, `qty`, `price`, `amount`, `collection`, `balance`, `delivery_id`, `payment_id`, `created_at`) VALUES
	(1, 1, '2026-04-22', 'DR-101', NULL, 100.00, 90.00, 9000.00, 0.00, 9000.00, 1, NULL, '2026-04-22 13:05:03'),
	(2, 1, '2026-04-22', 'DR-102', NULL, 99.00, 88.00, 8712.00, 0.00, 17712.00, 2, NULL, '2026-04-22 13:05:35'),
	(3, 1, '2026-04-22', NULL, '1000', NULL, NULL, 0.00, 11000.00, 6712.00, NULL, 1, '2026-04-22 14:41:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.migrations: ~1 rows (approximately)
DELETE FROM `migrations`;
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
	(24, '2026-04-21-065247', 'App\\Database\\Migrations\\CreateUsers', 'default', 'App', 1776861302, 1),
	(25, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateBanks', 'default', 'App', 1776861302, 1),
	(26, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateClients', 'default', 'App', 1776861302, 1),
	(27, '2026-04-21-065248', 'App\\Database\\Migrations\\CreateProducts', 'default', 'App', 1776861302, 1),
	(28, '2026-04-21-072400', 'App\\Database\\Migrations\\CreateDeliveries', 'default', 'App', 1776861302, 1),
	(29, '2026-04-21-072400', 'App\\Database\\Migrations\\CreateDeliveryItems', 'default', 'App', 1776861302, 1),
	(30, '2026-04-21-135124', 'App\\Database\\Migrations\\CreateCashiers', 'default', 'App', 1776861302, 1),
	(31, '2026-04-21-135125', 'App\\Database\\Migrations\\CreateCashierReceiptRanges', 'default', 'App', 1776861302, 1),
	(32, '2026-04-21-135126', 'App\\Database\\Migrations\\CreatePayments', 'default', 'App', 1776861302, 1),
	(33, '2026-04-21-150100', 'App\\Database\\Migrations\\CreatePaymentAllocations', 'default', 'App', 1776861302, 1),
	(34, '2026-04-21-150200', 'App\\Database\\Migrations\\CreateLedger', 'default', 'App', 1776861302, 1),
	(35, '2026-04-21-155000', 'App\\Database\\Migrations\\CreateOtherAccounts', 'default', 'App', 1776861302, 1),
	(36, '2026-04-21-160000', 'App\\Database\\Migrations\\CreateBoa', 'default', 'App', 1776861302, 1),
	(37, '2026-04-22-090000', 'App\\Database\\Migrations\\UpdateOtherAccountsType', 'default', 'App', 1776861302, 1),
	(38, '2026-04-22-091000', 'App\\Database\\Migrations\\UpdateBoaForOtherAccounts', 'default', 'App', 1776861302, 1);

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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.other_accounts: ~11 rows (approximately)
DELETE FROM `other_accounts`;
INSERT INTO `other_accounts` (`id`, `account_code`, `name`, `type`, `created_at`, `updated_at`) VALUES
	(1, '1000', 'Accounts Receivable Trade old', 'cr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(2, '2000', 'Loans Payable', 'cr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(3, '3000', 'Interest Income', 'cr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(4, '3100', 'Miscellaneous Income', 'cr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(5, '4000', 'Handling/Delivery Charges', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(6, '4100', 'Salaries and Wages', 'cr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(7, '4200', 'Taxes and Licenses', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(8, '4300', 'Commission Expenses', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(9, '4400', 'Sales Discount', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(10, '4500', 'Household Expenses - CMR', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11'),
	(11, '5000', 'Retained Earnings', 'dr', '2026-04-22 12:35:11', '2026-04-22 12:35:11');

-- Dumping structure for table ar.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `cashier_id` int unsigned NOT NULL,
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
  UNIQUE KEY `cashier_id_pr_no` (`cashier_id`,`pr_no`),
  KEY `payments_deposit_bank_id_foreign` (`deposit_bank_id`),
  KEY `client_id_date` (`client_id`,`date`),
  CONSTRAINT `payments_cashier_id_foreign` FOREIGN KEY (`cashier_id`) REFERENCES `cashiers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `payments_deposit_bank_id_foreign` FOREIGN KEY (`deposit_bank_id`) REFERENCES `banks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.payments: ~1 rows (approximately)
DELETE FROM `payments`;
INSERT INTO `payments` (`id`, `client_id`, `cashier_id`, `pr_no`, `date`, `method`, `amount_received`, `amount_allocated`, `excess_used`, `payer_bank`, `check_no`, `deposit_bank_id`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 1000, '2026-04-22', 'cash', 12000.00, 11000.00, 0.00, NULL, NULL, 1, 'posted', '2026-04-22 14:41:46', '2026-04-22 14:41:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.payment_allocations: ~2 rows (approximately)
DELETE FROM `payment_allocations`;
INSERT INTO `payment_allocations` (`id`, `payment_id`, `delivery_id`, `amount`, `created_at`) VALUES
	(1, 1, 1, 9000.00, '2026-04-22 14:41:46'),
	(2, 1, 2, 2000.00, '2026-04-22 14:41:46');

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.products: ~2 rows (approximately)
DELETE FROM `products`;
INSERT INTO `products` (`id`, `product_id`, `product_name`, `unit_price`, `created_at`, `updated_at`) VALUES
	(1, 'PROD-1', 'OIL-1', 90.00, '2026-04-22 13:03:30', '2026-04-22 13:03:30'),
	(2, 'PROD-2', 'OIL-2', 88.00, '2026-04-22 13:03:40', '2026-04-22 13:03:40');

-- Dumping structure for table ar.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table ar.users: ~1 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `username`, `password_hash`, `is_active`, `created_at`, `updated_at`) VALUES
	(1, 'admin', '$2y$12$N.VQonoQb.YfTI6HATRYWO7EWL.mbqy/lzjNuufhgrbrBJVjVm9Te', 1, '2026-04-22 12:35:21', '2026-04-22 12:35:21');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
