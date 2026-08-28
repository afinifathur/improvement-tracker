-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: improvement_tracker
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `area_assignments`
--

DROP TABLE IF EXISTS `area_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `area_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `area_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` date NOT NULL,
  `ended_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `area_assignments_area_id_ended_at_index` (`area_id`,`ended_at`),
  KEY `area_assignments_user_id_ended_at_index` (`user_id`,`ended_at`),
  CONSTRAINT `area_assignments_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `area_assignments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `area_assignments`
--

LOCK TABLES `area_assignments` WRITE;
/*!40000 ALTER TABLE `area_assignments` DISABLE KEYS */;
INSERT INTO `area_assignments` VALUES (1,1,1,'manager','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(2,1,2,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(3,1,3,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(4,2,4,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(5,3,5,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(6,4,6,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(7,5,7,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(8,6,8,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(9,7,9,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(10,8,10,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(11,8,11,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(12,8,12,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(13,9,13,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(14,10,13,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(15,11,14,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(16,12,15,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(17,13,16,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(18,14,17,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(19,14,18,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(20,15,19,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(21,16,20,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(22,17,21,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(23,17,22,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(24,18,23,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(25,19,24,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(26,20,25,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(27,21,26,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(28,22,27,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(29,23,28,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(30,24,29,'manager','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(31,25,30,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(32,26,31,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(33,27,32,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(34,28,33,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(35,29,34,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(36,30,35,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(37,31,36,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(38,32,37,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(39,33,38,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(40,34,39,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(41,31,40,'spv','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55'),(42,35,41,'kabag','2026-01-01',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55');
/*!40000 ALTER TABLE `area_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `areas`
--

DROP TABLE IF EXISTS `areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deactivated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `areas_code_unique` (`code`),
  KEY `areas_department_id_foreign` (`department_id`),
  KEY `areas_is_active_index` (`is_active`),
  CONSTRAINT `areas_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `areas`
--

LOCK TABLES `areas` WRITE;
/*!40000 ALTER TABLE `areas` DISABLE KEYS */;
INSERT INTO `areas` VALUES (1,'PPIC','PPIC',1,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(2,'IT-K3','IT DAN K3',2,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(3,'CCTV','CCTV',2,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(4,'HR','HR',2,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(5,'BHN-BK','BAHAN BAKU',1,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(6,'GD-FL','GUDANG FLANGE',1,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(7,'GD-FIT','GUDANG FITTING',1,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(8,'COR-FL','COR FLANGE',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(9,'NT-FL','NETTO FLANGE',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(10,'NT-FIT','NETTO FITTING',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(11,'KM','KIMIA',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(12,'CNC-FIT','CNC FITTING',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(13,'SRV-FL','SERVICE FLANGE',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(14,'BBT-OT','BUBUT OTOMATIS',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(15,'QC-SLD','QC SOLDER',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(16,'BOR-FL','BOR FLANGE',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(17,'CNC-FL','CNC FLANGE',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(18,'UMUM','UMUM',5,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(19,'MNT','MAINTENANCE',6,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(20,'MNT-FIT','MAINTENANCE FITTING',6,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(21,'MNT-COR-FL','MAINTENANCE COR FLANGE',6,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(22,'MNT-BBT-CNC','MAINTENANCE BUBUT CNC',6,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(23,'QC-FIT','QC FITTING',7,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(24,'QA-QC','QA/QC',7,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(25,'QA-FIT','QA FITTING',7,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(26,'QA-FL','QA FLANGE',7,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(27,'MIL-CNC','MILLING CNC',8,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(28,'BESI','BESI',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(29,'AL','ALUMINIUM',9,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(30,'COR-FIT','COR FITTING',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(31,'FIT','FITTING',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(32,'LPS','LAPISAN',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(33,'MRK','MARKING',3,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(34,'LLN','LILIN',4,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43'),(35,'SEC','SECURITY',2,1,NULL,'2026-08-17 20:06:43','2026-08-17 20:06:43');
/*!40000 ALTER TABLE `areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_report_issue`
--

DROP TABLE IF EXISTS `daily_report_issue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_report_issue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `daily_report_id` bigint unsigned NOT NULL,
  `issue_id` bigint unsigned NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `reported_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `daily_report_issue_daily_report_id_issue_id_unique` (`daily_report_id`,`issue_id`),
  KEY `daily_report_issue_issue_id_foreign` (`issue_id`),
  CONSTRAINT `daily_report_issue_daily_report_id_foreign` FOREIGN KEY (`daily_report_id`) REFERENCES `daily_reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `daily_report_issue_issue_id_foreign` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_report_issue`
--

LOCK TABLES `daily_report_issue` WRITE;
/*!40000 ALTER TABLE `daily_report_issue` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_report_issue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_reports`
--

DROP TABLE IF EXISTS `daily_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_date` date NOT NULL,
  `reported_by` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `today_result` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `daily_reports_created_by_foreign` (`created_by`),
  KEY `daily_reports_updated_by_foreign` (`updated_by`),
  KEY `daily_reports_report_date_index` (`report_date`),
  KEY `daily_reports_department_id_index` (`department_id`),
  KEY `daily_reports_area_id_foreign` (`area_id`),
  KEY `daily_reports_reported_by_index` (`reported_by`),
  CONSTRAINT `daily_reports_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `daily_reports_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `daily_reports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `daily_reports_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `daily_reports_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_reports`
--

LOCK TABLES `daily_reports` WRITE;
/*!40000 ALTER TABLE `daily_reports` DISABLE KEYS */;
INSERT INTO `daily_reports` VALUES (1,'2026-08-18',1,1,NULL,42,42,'2026-08-17 20:15:20','2026-08-17 20:15:20',1),(2,'2026-08-18',29,7,NULL,42,42,'2026-08-17 20:23:47','2026-08-17 20:23:47',24),(3,'2026-08-18',7,1,'KENDALA: TANJEK 304 TELAT, MENGURANGI PENGGUNAAN TANJEK 100KG JADI 50KG',42,42,'2026-08-17 21:38:36','2026-08-18 03:13:35',5),(4,'2026-08-18',41,2,NULL,42,42,'2026-08-17 21:47:08','2026-08-17 21:47:08',35),(5,'2026-08-18',33,3,NULL,42,42,'2026-08-17 21:51:17','2026-08-17 21:51:17',28),(6,'2026-08-18',34,9,NULL,42,42,'2026-08-17 21:51:58','2026-08-17 21:51:58',29),(7,'2026-08-18',12,3,NULL,42,42,'2026-08-17 21:52:51','2026-08-17 21:52:51',8),(8,'2026-08-18',10,3,NULL,42,42,'2026-08-17 21:53:51','2026-08-17 21:53:51',8),(9,'2026-08-18',6,2,NULL,42,42,'2026-08-17 21:54:38','2026-08-17 21:54:38',4),(10,'2026-08-18',5,2,NULL,42,42,'2026-08-18 02:14:36','2026-08-18 02:14:36',3),(11,'2026-08-19',5,2,NULL,42,42,'2026-08-18 02:18:48','2026-08-18 02:18:48',3),(12,'2026-08-18',38,3,'KENDALA: ANGIN-ANGIN KURANG STABIL MENYEBABKAN REWORK.',42,42,'2026-08-18 02:27:16','2026-08-18 02:27:16',33),(13,'2026-08-18',24,6,NULL,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19),(14,'2026-08-18',26,6,NULL,42,42,'2026-08-18 03:07:31','2026-08-18 03:07:31',21),(15,'2026-08-18',31,7,NULL,42,42,'2026-08-18 03:11:55','2026-08-18 03:11:55',26),(16,'2026-08-18',15,4,'SISA BARANG E04 DI COR: 2 1/2\", 3\" ELBOW 90 MF, 3\" HOSE 90, 3\" TEFLON , 4\" U DRAT, 4\" RING',42,42,'2026-08-18 03:16:39','2026-08-18 03:16:39',12),(17,'2026-08-18',20,3,NULL,42,42,'2026-08-18 03:39:12','2026-08-18 03:39:12',16),(18,'2026-08-18',22,3,'KENDALA: BARANG 10\" PN10BL 14408 E02 BANYAK YANG CEMBUNG JADI OPERATOR HARUS BUBT MANUAL TERLEBIH DAHULU. SOLUSI: DARI COR DIPERBAIKI/DIKASARI DULU DI MANUAL SEBELUM MASUK CNC',42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17),(19,'2026-08-18',18,3,NULL,42,42,'2026-08-18 18:34:27','2026-08-18 18:34:27',14),(20,'2026-08-18',28,7,NULL,42,42,'2026-08-18 18:50:00','2026-08-18 18:50:00',23),(21,'2026-08-18',13,4,'KENDALA: MESIN BLASTING HASIL TIDAK MAKSIMAL, BANYAK YANG BOCOR',42,42,'2026-08-18 18:56:59','2026-08-18 18:56:59',10),(22,'2026-08-18',13,3,'2 OPERATOR TIDAK MASUK',42,42,'2026-08-18 18:59:41','2026-08-18 18:59:41',9),(23,'2026-08-18',2,1,NULL,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1),(24,'2026-08-18',4,2,NULL,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2),(25,'2026-08-18',8,1,'HASIL LOKAL 25.6 TON TAMBAH KIRIM BESOK 28 TON. TARGET MINGGU INI 35 TON. HASIL E02=10 TON',42,42,'2026-08-18 19:09:44','2026-08-18 19:09:44',6),(26,'2026-08-19',23,5,NULL,42,42,'2026-08-18 19:16:23','2026-08-18 19:16:23',18),(27,'2026-08-18',23,5,NULL,42,42,'2026-08-18 19:20:33','2026-08-18 19:20:33',18),(28,'2026-08-18',39,4,NULL,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34);
/*!40000 ALTER TABLE `daily_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deactivated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`),
  UNIQUE KEY `departments_code_unique` (`code`),
  KEY `departments_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'PPIC','PPIC','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(2,'HRD','HRD','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(3,'PRODUKSI FLANGE','PRD-FL','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(4,'PRODUKSI FITTING','PRD-FIT','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(5,'UMUM','UMUM','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(6,'MAINTENANCE','MTC','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(7,'QA/QC','QA-QC','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(8,'MILLING CNC','CNC','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL),(9,'ALUMINIUM','AL','2026-08-17 20:06:43','2026-08-17 20:06:43',1,NULL);
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `issues`
--

DROP TABLE IF EXISTS `issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `issues` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `department_id` bigint unsigned DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `first_reported_at` timestamp NOT NULL,
  `source_daily_report_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issues_created_by_foreign` (`created_by`),
  KEY `issues_updated_by_foreign` (`updated_by`),
  KEY `issues_status_index` (`status`),
  KEY `issues_department_id_index` (`department_id`),
  KEY `issues_source_daily_report_id_index` (`source_daily_report_id`),
  KEY `issues_area_id_foreign` (`area_id`),
  CONSTRAINT `issues_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `issues_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_source_daily_report_id_foreign` FOREIGN KEY (`source_daily_report_id`) REFERENCES `daily_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `issues`
--

LOCK TABLES `issues` WRITE;
/*!40000 ALTER TABLE `issues` DISABLE KEYS */;
/*!40000 ALTER TABLE `issues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_03_30_000001_create_weekly_plans_table',1),(5,'2026_03_30_000002_create_plan_proofs_table',1),(6,'2026_03_30_000003_create_plan_scores_table',1),(7,'2026_03_30_095024_create_personal_access_tokens_table',1),(8,'2026_08_14_000001_create_departments_table',1),(9,'2026_08_14_000002_add_department_and_manager_to_users_table',1),(10,'2026_08_14_000003_create_daily_reports_table',1),(11,'2026_08_14_000004_create_work_items_table',1),(12,'2026_08_14_000005_create_issues_table',1),(13,'2026_08_14_000006_create_daily_report_issue_table',1),(14,'2026_08_14_000007_create_work_item_schedule_changes_table',1),(15,'2026_08_14_000008_add_missing_foreign_key_indexes',1),(16,'2026_08_15_000001_add_lifecycle_to_departments_table',1),(17,'2026_08_15_000002_create_areas_table',1),(18,'2026_08_15_000003_create_area_assignments_table',1),(19,'2026_08_15_000004_add_area_id_to_daily_reports_table',1),(20,'2026_08_15_000005_add_area_id_to_work_items_table',1),(21,'2026_08_15_000006_add_area_id_to_issues_table',1),(22,'2026_08_15_000007_add_work_type_to_work_items_table',1),(23,'2026_08_15_000008_drop_daily_reports_reporter_date_unique',1),(24,'2026_08_17_000000_add_weekly_plan_id_to_work_items_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan_proofs`
--

DROP TABLE IF EXISTS `plan_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_proofs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `weekly_plan_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plan_proofs_weekly_plan_id_index` (`weekly_plan_id`),
  CONSTRAINT `plan_proofs_weekly_plan_id_foreign` FOREIGN KEY (`weekly_plan_id`) REFERENCES `weekly_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_proofs`
--

LOCK TABLES `plan_proofs` WRITE;
/*!40000 ALTER TABLE `plan_proofs` DISABLE KEYS */;
/*!40000 ALTER TABLE `plan_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plan_scores`
--

DROP TABLE IF EXISTS `plan_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `weekly_plan_id` bigint unsigned NOT NULL,
  `base_score` int NOT NULL,
  `multiplier` decimal(3,2) NOT NULL,
  `final_score` decimal(8,2) NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plan_scores_weekly_plan_id_unique` (`weekly_plan_id`),
  CONSTRAINT `plan_scores_weekly_plan_id_foreign` FOREIGN KEY (`weekly_plan_id`) REFERENCES `weekly_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plan_scores`
--

LOCK TABLES `plan_scores` WRITE;
/*!40000 ALTER TABLE `plan_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `plan_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('vXdb9YOqhZPOOcdhVmlsrXNe1wVMuyN3dEiJMgjh',43,'10.88.8.97','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','eyJfdG9rZW4iOiJvYmduenBYUTIzb3dlVGRleXAxTXVzVzlvUUh4Vk9Fa3QwbjNaS29hIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEwLjg4LjguOTdcL2ltcHJvdmVtZW50LXRyYWNrZXJcL3B1YmxpY1wvaW5kZXgucGhwXC9kYXNoYm9hcmQiLCJyb3V0ZSI6ImRhc2hib2FyZC5pbmRleCJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo0M30=',1787281263),('yDZsbKo2UCaBRWVOjs53aqy1yGcu6GIUNnQnbvTL',42,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36 Edg/151.0.0.0','eyJfdG9rZW4iOiI4MFJwb0xRU1dVU1hMZlVQUTJsOFhJWENJcmVDd0NGTGN1REl0YVZrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMVwvaW1wcm92ZW1lbnQtdHJhY2tlclwvcHVibGljXC9pbmRleC5waHBcL2Rhc2hib2FyZCIsInJvdXRlIjoiZGFzaGJvYXJkLmluZGV4In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjQyfQ==',1787280181);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','director','manager','kabag','spv') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'spv',
  `department_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `manager_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_index` (`role`),
  KEY `users_department_id_index` (`department_id`),
  KEY `users_manager_id_index` (`manager_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'AFIN','afin@peroniks.com',NULL,'$2y$12$OShmMP4.WXPq6kN77e.ZTuInkadK1JI6NEP40HYrun9A8r7bFGHLG','manager','PPIC',NULL,'2026-08-17 20:06:47','2026-08-17 20:06:55',1,NULL),(2,'EKO','eko@peroniks.com',NULL,'$2y$12$YjBZtYyoOwPJEny8x9O8hefPL/klPGmJLfZwBzFjwQ0ZHTKuSFd.6','spv','PPIC',NULL,'2026-08-17 20:06:47','2026-08-17 20:06:55',1,1),(3,'IKA','ika@peroniks.com',NULL,'$2y$12$VzqSEYIxSkcEIg8zVRiOSubYeijpKSfT/WJmrcUhbZMwlSoSR3nQ2','spv','PPIC',NULL,'2026-08-17 20:06:47','2026-08-17 20:06:55',1,1),(4,'AGRIN','agrin@peroniks.com',NULL,'$2y$12$mh6Dm9sX3n.M0pqH/pYxreiDvrS4U5Q8hTdaAqHIdl1CrghdehNBW','spv','HRD',NULL,'2026-08-17 20:06:48','2026-08-17 20:06:55',2,41),(5,'NISA','nisa@peroniks.com',NULL,'$2y$12$FOMLBQvTbvd2nKmUyAbTy.NPygfzZJ0iQLhj2EYVXILL/00qJ6Fka','spv','HRD',NULL,'2026-08-17 20:06:48','2026-08-17 20:06:55',2,41),(6,'ROKI','roki@peroniks.com',NULL,'$2y$12$EcAtLcUujv/cMhI0ruipBOxcUo/F26XqUefwcfdQ8VsCF92OL.hCu','spv','HRD',NULL,'2026-08-17 20:06:48','2026-08-17 20:06:55',2,41),(7,'RIKI','riki@peroniks.com',NULL,'$2y$12$qJBJQrgAGV8ZscMqJXgHWOAl02LjsXR7.WjuG0ht0ZXZBnmSczlia','spv','PPIC',NULL,'2026-08-17 20:06:48','2026-08-17 20:06:55',1,1),(8,'HERMAN','herman@peroniks.com',NULL,'$2y$12$XXT4.RuPG2kStwofHKGYeuR268ueDF7WEtlzNuKzfwwME3EdJ6cRq','spv','PPIC',NULL,'2026-08-17 20:06:48','2026-08-17 20:06:55',1,1),(9,'DANI','dani@peroniks.com',NULL,'$2y$12$tAx53Mlz4QDkPqI4ib5wpO0hSLYTc/U.Rxzx0hdZYF8By7IDU3aLy','spv','PPIC',NULL,'2026-08-17 20:06:49','2026-08-17 20:06:55',1,1),(10,'RUKASIM','rukasim@peroniks.com',NULL,'$2y$12$y80x9h/RhbTicHNBpBBY1eWXtGoPUDL6k/Ma7iaqOJGiGHLC1aHZi','kabag','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:49','2026-08-17 20:06:55',3,1),(11,'ROJI','roji@peroniks.com',NULL,'$2y$12$.ZxZ4Ib2mWjm3GLE6Qw87OQzmfN6y/MmT/YuIivdOUCzvqk6oTu3K','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:49','2026-08-17 20:06:55',3,10),(12,'MAJID','majid@peroniks.com',NULL,'$2y$12$s0MSSp9MYBTvWshFXn0HE.fgQT/ELidY5Udgp9P663BHDXsPuV9H.','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:49','2026-08-17 20:06:55',3,10),(13,'HUDA','huda@peroniks.com',NULL,'$2y$12$khrqur/qscXOSZGqIT2DregoJ7vDpgw8jU5WAxDm19Wchoa/V8Km2','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:49','2026-08-17 20:06:55',3,15),(14,'AININ','ainin@peroniks.com',NULL,'$2y$12$4Lh1zixuLu1DndXfLQRPZuZatH0CjmLub/5O9L2oWM4tzqHkWJ8ka','spv','PRODUKSI FITTING',NULL,'2026-08-17 20:06:50','2026-08-17 20:06:55',4,15),(15,'ALFIAN','alfian@peroniks.com',NULL,'$2y$12$C7vGc3rXZYFMh.A2UK8bteqo52MF5qMlgiSev8b18u/cO6Gyq1fze','kabag','PRODUKSI FITTING',NULL,'2026-08-17 20:06:50','2026-08-17 20:06:55',4,1),(16,'EDI','edi@peroniks.com',NULL,'$2y$12$XZhOCGpcRkPmiae3B3aLLu3yVlBghiqtiaJSXh8eQQj72OtrGr2PC','kabag','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:50','2026-08-17 20:06:55',3,1),(17,'SODIQ','sodiq@peroniks.com',NULL,'$2y$12$64QuUKUIZRFxeEoaVrO5auKTcbTZGOySRoleq0lJ.ecGWVCKVmNSS','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:50','2026-08-17 20:06:55',3,10),(18,'BAMBANG','bambang@peroniks.com',NULL,'$2y$12$.E4eLDs1sgpq7rEhVayR3eQDg7tBuruRkB4.gUZ4UdTx4IOebhkMO','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:51','2026-08-17 20:06:55',3,10),(19,'NURI','nuri@peroniks.com',NULL,'$2y$12$.gsjUMAuhYZ5cKZ8mfLObOxXyW8yo54yF6uGfpfbF3.31gDqqF94O','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:51','2026-08-17 20:06:55',3,10),(20,'TEGUH','teguh@peroniks.com',NULL,'$2y$12$LobKgAovcwJXUsKGpmPcAeVZzVRjBvTHc10atrsdDHidxdgxY6S26','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:51','2026-08-17 20:06:55',3,10),(21,'SAHRUL','sahrul@peroniks.com',NULL,'$2y$12$lhnJvqCn30yqlm2RfkKHte75RpAFLQoVjgZrAW7p3IdoC44XUghgy','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:51','2026-08-17 20:06:55',3,10),(22,'BAGUS','bagus@peroniks.com',NULL,'$2y$12$y7.jZ87dJ9zSw.5rOfIZWeLxgOqw7V1THgXxy8K1MMSZbuPYRh3hC','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:51','2026-08-17 20:06:55',3,10),(23,'ARSENG','arseng@peroniks.com',NULL,'$2y$12$SEK4d7Bxd9h2XxJh80YoauUlMxAMksjyIcCuhoJV13WBm1lv/OX9a','spv','UMUM',NULL,'2026-08-17 20:06:52','2026-08-17 20:06:55',5,1),(24,'TRI HARDI','trihardi@peroniks.com',NULL,'$2y$12$XJvNkTSRTlJNipFMtwN39eZrnc7shgqNSfxUWoU0SEptwLMLhqBmO','kabag','MAINTENANCE',NULL,'2026-08-17 20:06:52','2026-08-17 20:06:55',6,1),(25,'DENY','deny@peroniks.com',NULL,'$2y$12$g32NgXZmm4oNn7qr/rvyhODNiukM69iDuIl.JogLKJgwdbl1HIrga','spv','MAINTENANCE',NULL,'2026-08-17 20:06:52','2026-08-17 20:06:55',6,24),(26,'FIRNANDA','firnanda@peroniks.com',NULL,'$2y$12$lz407DPzpS65shSDzmnFmOJ0VYqgpipViXuDtIgpBn0D6MuAeSrGq','spv','MAINTENANCE',NULL,'2026-08-17 20:06:52','2026-08-17 20:06:55',6,24),(27,'UTOMO','utomo@peroniks.com',NULL,'$2y$12$Y3tTVBmBDQEV7Ckqrndo0utC63TF9bktmUC0ySMJWRaOAmpApE3vC','spv','MAINTENANCE',NULL,'2026-08-17 20:06:52','2026-08-17 20:06:55',6,24),(28,'ARIS','aris@peroniks.com',NULL,'$2y$12$v3shvJYri/dI2Vcmn/qwduHSHvG6PMbPvNXoWvWaPjtEi3.rzapAK','spv','QA/QC',NULL,'2026-08-17 20:06:53','2026-08-17 20:06:55',7,29),(29,'YAYAK','yayak@peroniks.com',NULL,'$2y$12$pMS2HKERhXw5JZAd6KwNbuhOWfuTwa29tmLpnEC7aG0pDn0AmD6b.','manager','QA/QC',NULL,'2026-08-17 20:06:53','2026-08-17 20:06:55',7,NULL),(30,'ANDRE','andre@peroniks.com',NULL,'$2y$12$pPw6QnO1Ou5hH6eNbHlaJ.AE0WAJxGuun9Ls.9ZPy.b60DDPDKZZG','spv','QA/QC',NULL,'2026-08-17 20:06:53','2026-08-17 20:06:55',7,29),(31,'ADI','adi@peroniks.com',NULL,'$2y$12$lUdzvgaMZalPW.n202f9QuX0LNGI48i6D5oG2DnAWltRLzTIrxR1a','spv','QA/QC',NULL,'2026-08-17 20:06:53','2026-08-17 20:06:55',7,29),(32,'FAISAL','faisal@peroniks.com',NULL,'$2y$12$yQ0k1Nd572k84DWoiZaDZuv2.Jar6JWsk4hr5iz98P8FCCXQp2Y7C','spv','MILLING CNC',NULL,'2026-08-17 20:06:53','2026-08-17 20:06:55',8,1),(33,'EKO RIRIT','ekoririt@peroniks.com',NULL,'$2y$12$BbYmgnEDza1w270/EP/GOuhpCoPRP15SSYyVOhSLnhXdQMAEphXzW','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:54','2026-08-17 20:06:55',3,10),(34,'ULIL','ulil@peroniks.com',NULL,'$2y$12$hPAjD4IrARvC0u5OucH0Qe9Z8OwhoH5CJUYTnpbfZ9Qf37QencBt.','spv','ALUMINIUM',NULL,'2026-08-17 20:06:54','2026-08-17 20:06:55',9,1),(35,'DWIAN','dwian@peroniks.com',NULL,'$2y$12$IgpxLnUk2tAq/p7x8bdUHuCCGplonyngmH0WVnGkj2sCoDkcHw3xe','kabag','PRODUKSI FITTING',NULL,'2026-08-17 20:06:54','2026-08-17 20:06:55',4,1),(36,'RAVY','ravy@peroniks.com',NULL,'$2y$12$U4y/lWr6iaFsooDXBpBjOurrY1SY.K7QeDW4aDiNgmDZAts8LP35y','spv','PRODUKSI FITTING',NULL,'2026-08-17 20:06:54','2026-08-17 20:06:55',4,15),(37,'LINGGA','lingga@peroniks.com',NULL,'$2y$12$EYzLJ3oMb5444L0nNh5O..EW407/g.eaH6qtfRECKIJMA0/5tYmpC','spv','PRODUKSI FITTING',NULL,'2026-08-17 20:06:54','2026-08-17 20:06:55',4,15),(38,'AGUS','agus@peroniks.com',NULL,'$2y$12$BTB8.bH6CaX7BXuYltFJqeZZmWdSUuEG3nOfJjIULguPgHWBQ1bWi','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55',3,10),(39,'DEVI','devi@peroniks.com',NULL,'$2y$12$/XEOtes4quUuo/WNIxZmMep8r0vZxWxZEVoU06f0iyKv7A7sCKbJC','spv','PRODUKSI FITTING',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55',4,15),(40,'JOKO','joko@peroniks.com',NULL,'$2y$12$78YCFHc1crQUSXo0mW7Alu5xb1C3tEHtNhpXQ34FhN96P14JqY/yi','spv','PRODUKSI FITTING',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55',4,15),(41,'ROUD','roud@peroniks.com',NULL,'$2y$12$Xurxz.cmRLYKs9uGlU.38e2n8apzTkNBMQNSuGUNz0C9v5hTcW3Zu','kabag','HRD',NULL,'2026-08-17 20:06:55','2026-08-17 20:06:55',2,1),(42,'Admin PPIC','adminppic@peroniks.com',NULL,'$2y$12$9fhh.VoJHyeyF/S0tUZOKegdmtt68DwK643WojThMsOpwnBFxWKJG','admin','PPIC',NULL,'2026-08-17 20:07:03','2026-08-17 20:07:03',1,NULL),(43,'MR Manager','mr@peroniks.com',NULL,'$2y$12$HBDC0uv6OdS72zJabdmfk.E5NT4ED8r8QEKSlgFmZnPbGJv3tfc.O','manager','QA/QC',NULL,'2026-08-17 20:07:04','2026-08-17 20:07:04',7,NULL),(44,'Direktur Utama','direktur@peroniks.com',NULL,'$2y$12$ObGj90OsBsK1Acz4BOY7F.HglfZfQ6KKo5Fpo4Ejshszqt5yGb7n.','director',NULL,NULL,'2026-08-17 20:07:04','2026-08-17 20:07:04',NULL,NULL),(45,'System Admin','admin@kaizen.com',NULL,'$2y$12$.RckMG2PeBCF5P2A/UEnz.Lj3udPXc6T9Z1KczUd0VCHORpg4yabK','admin',NULL,NULL,'2026-08-17 20:07:04','2026-08-17 20:07:04',NULL,NULL),(46,'Supervisor A','spv_a@kaizen.com',NULL,'$2y$12$n9ePkk51L3EOApdGdO9QheSfmBteYc9Ah0lT5H5.xYX0V6fvoNgei','spv','PRODUKSI FLANGE',NULL,'2026-08-17 20:07:04','2026-08-17 20:07:04',3,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `weekly_plans`
--

DROP TABLE IF EXISTS `weekly_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `weekly_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expected_output` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('improvement','problem','maintenance') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_corrected` enum('improvement','problem','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `impact_level` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL,
  `week_start_date` date NOT NULL,
  `week_end_date` date NOT NULL,
  `status` enum('planned','completed','completed_no_impact','not_completed','extended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planned',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weekly_plans_created_by_foreign` (`created_by`),
  KEY `weekly_plans_updated_by_foreign` (`updated_by`),
  KEY `weekly_plans_user_id_week_start_date_index` (`user_id`,`week_start_date`),
  KEY `weekly_plans_week_start_date_index` (`week_start_date`),
  KEY `weekly_plans_status_index` (`status`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `weekly_plans_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `weekly_plans_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `weekly_plans`
--

LOCK TABLES `weekly_plans` WRITE;
/*!40000 ALTER TABLE `weekly_plans` DISABLE KEYS */;
INSERT INTO `weekly_plans` VALUES (1,1,'menyelesaikan software pengganti Notion','selesai versi 1 minimun vailable product','problem',NULL,'medium','2026-08-17','2026-08-23','planned',NULL,42,42,'2026-08-17 20:13:08','2026-08-17 20:13:08');
/*!40000 ALTER TABLE `weekly_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_item_schedule_changes`
--

DROP TABLE IF EXISTS `work_item_schedule_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_item_schedule_changes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `work_item_id` bigint unsigned NOT NULL,
  `old_start_date` date DEFAULT NULL,
  `old_end_date` date NOT NULL,
  `new_start_date` date NOT NULL,
  `new_end_date` date NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_note` text COLLATE utf8mb4_unicode_ci,
  `changed_by` bigint unsigned NOT NULL,
  `changed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_item_schedule_changes_changed_by_foreign` (`changed_by`),
  KEY `work_item_schedule_changes_work_item_id_changed_at_index` (`work_item_id`,`changed_at`),
  CONSTRAINT `work_item_schedule_changes_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `work_item_schedule_changes_work_item_id_foreign` FOREIGN KEY (`work_item_id`) REFERENCES `work_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_item_schedule_changes`
--

LOCK TABLES `work_item_schedule_changes` WRITE;
/*!40000 ALTER TABLE `work_item_schedule_changes` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_item_schedule_changes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_items`
--

DROP TABLE IF EXISTS `work_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `owner_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `original_start_date` date NOT NULL,
  `original_end_date` date NOT NULL,
  `planned_start_date` date NOT NULL,
  `planned_end_date` date NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `completed_at` timestamp NULL DEFAULT NULL,
  `blocked_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blocked_reason_note` text COLLATE utf8mb4_unicode_ci,
  `blocked_at` timestamp NULL DEFAULT NULL,
  `blocked_by_department_id` bigint unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancel_reason_note` text COLLATE utf8mb4_unicode_ci,
  `carried_from_id` bigint unsigned DEFAULT NULL,
  `source_daily_report_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `area_id` bigint unsigned DEFAULT NULL,
  `work_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weekly_plan_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `work_items_created_by_foreign` (`created_by`),
  KEY `work_items_updated_by_foreign` (`updated_by`),
  KEY `work_items_owner_id_planned_start_date_index` (`owner_id`,`planned_start_date`),
  KEY `work_items_status_index` (`status`),
  KEY `work_items_planned_end_date_index` (`planned_end_date`),
  KEY `work_items_carried_from_id_index` (`carried_from_id`),
  KEY `work_items_blocked_by_department_id_index` (`blocked_by_department_id`),
  KEY `work_items_department_id_index` (`department_id`),
  KEY `work_items_source_daily_report_id_index` (`source_daily_report_id`),
  KEY `work_items_area_id_foreign` (`area_id`),
  KEY `work_items_work_type_index` (`work_type`),
  KEY `work_items_weekly_plan_id_index` (`weekly_plan_id`),
  CONSTRAINT `work_items_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_blocked_by_department_id_foreign` FOREIGN KEY (`blocked_by_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_carried_from_id_foreign` FOREIGN KEY (`carried_from_id`) REFERENCES `work_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `work_items_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `work_items_source_daily_report_id_foreign` FOREIGN KEY (`source_daily_report_id`) REFERENCES `daily_reports` (`id`) ON DELETE SET NULL,
  CONSTRAINT `work_items_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `work_items_weekly_plan_id_foreign` FOREIGN KEY (`weekly_plan_id`) REFERENCES `weekly_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_items`
--

LOCK TABLES `work_items` WRITE;
/*!40000 ALTER TABLE `work_items` DISABLE KEYS */;
INSERT INTO `work_items` VALUES (1,'uji coba tahap 1 sistem pengganti notion',NULL,1,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,42,42,'2026-08-17 20:15:20','2026-08-18 18:18:04',1,NULL,1),(2,'cek sensor difitting',NULL,1,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,42,42,'2026-08-17 20:15:20','2026-08-18 18:18:04',1,NULL,NULL),(3,'cek uji coba moulding','moulding dari 2\" PN 16 dan 3\" 10 KA hasil cor 15/8',29,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:24:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,42,42,'2026-08-17 20:23:47','2026-08-18 18:24:15',24,NULL,NULL),(4,'mengansur square flange','karena barang sudah mulai masuk area qc finish dan diangsur pengerjannya',29,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:24:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,42,42,'2026-08-17 20:23:47','2026-08-18 18:24:15',24,NULL,NULL),(5,'Proses bahan',NULL,7,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,42,42,'2026-08-17 21:38:36','2026-08-18 18:18:34',5,NULL,NULL),(6,'Potong 304,316',NULL,7,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,42,42,'2026-08-17 21:44:59','2026-08-18 18:18:34',5,NULL,NULL),(7,'Press 304, Besi',NULL,7,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:34',NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,42,42,'2026-08-17 21:44:59','2026-08-18 18:18:34',5,NULL,NULL),(8,'Bersihkan area parkir atas dan bawah',NULL,41,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,42,42,'2026-08-17 21:47:08','2026-08-18 18:19:12',35,NULL,NULL),(9,'Briefing satpam',NULL,41,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,42,42,'2026-08-17 21:47:08','2026-08-18 18:19:12',35,NULL,NULL),(10,'Setting 2 1/2\" PN16 RF',NULL,33,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:25:22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,42,42,'2026-08-17 21:51:17','2026-08-18 03:25:22',28,NULL,NULL),(11,'Setting 3/4\"PN16',NULL,33,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:25:22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,42,42,'2026-08-17 21:51:17','2026-08-18 03:25:22',28,NULL,NULL),(12,'Setting 3\" LBRF BL',NULL,33,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:25:22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,42,42,'2026-08-17 21:51:17','2026-08-18 03:25:22',28,NULL,NULL),(13,'PROSES 1 1/2\" LB RF 70 PCS/MESIN',NULL,33,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:25:22',NULL,NULL,NULL,NULL,NULL,NULL,NULL,5,42,42,'2026-08-17 21:51:17','2026-08-18 03:25:22',28,NULL,NULL),(14,'SHIFT 1, 2 BUBUT DAN SERONG DN 200 ISO E01,  DN 50 MET E01',NULL,34,9,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:20:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,42,42,'2026-08-17 21:51:58','2026-08-18 18:20:24',29,NULL,NULL),(15,'DAPUR 2: PO LOKAL 8” PN16 RF, PO E01 1” PN16 LOOSE DAN 16” PN16',NULL,12,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:10:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,42,42,'2026-08-17 21:52:51','2026-08-18 18:10:56',8,NULL,NULL),(16,'DAPUR 3: PO LOKAL 12” INKA, 10” 10KB, 3” PN40 RF, 3” PN 10 RF BL, 4” INKA',NULL,12,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:10:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,7,42,42,'2026-08-17 21:52:51','2026-08-18 18:10:56',8,NULL,NULL),(17,'DAPUR 2 JALAN AISI 304 PO LOKAL 8” PN16 RF, E01 1” PN16 LOOSE, E02 20” PN10 RT',NULL,10,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:11:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,42,42,'2026-08-17 21:53:51','2026-08-18 18:11:27',8,NULL,NULL),(18,'DAPUR 3: JALAN AISI 304 PO LOKAL 12” 10KA, 3” PN 40 RF, 3” PN10 BL, 4” 10KA.',NULL,10,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:11:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,42,42,'2026-08-17 21:53:51','2026-08-18 18:11:27',8,NULL,NULL),(19,'SHIFT 3 JALAN 304 PO A02 8” ISO SORF, 8” TABLE E',NULL,10,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:11:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,42,42,'2026-08-17 21:53:51','2026-08-18 18:11:27',8,NULL,NULL),(20,'BAHAN: 304 30 PAKET, BESI 2 PAKET.',NULL,10,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:11:27',NULL,NULL,NULL,NULL,NULL,NULL,NULL,8,42,42,'2026-08-17 21:53:51','2026-08-18 18:11:27',8,NULL,NULL),(21,'INTERVIEW KARYAWAN BARU',NULL,6,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9,42,42,'2026-08-17 21:54:38','2026-08-18 18:19:00',4,NULL,NULL),(22,'SCREENING KARYAWAN BARU',NULL,6,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9,42,42,'2026-08-17 21:54:38','2026-08-18 18:19:00',4,NULL,NULL),(23,'UPDATE LAPORAN',NULL,6,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,9,42,42,'2026-08-17 21:54:38','2026-08-18 18:19:00',4,NULL,NULL),(24,'Coding ulang Kanban',NULL,1,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,42,42,'2026-08-18 01:58:03','2026-08-18 18:18:04',1,NULL,NULL),(25,'Meeting dengan TQ',NULL,1,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,42,42,'2026-08-18 01:58:03','2026-08-18 18:18:04',1,NULL,NULL),(26,'Cek Lokal dan Lokal Urgent',NULL,1,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:18:04',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,42,42,'2026-08-18 01:58:03','2026-08-18 18:18:04',1,NULL,NULL),(27,'Playback CCTV shift 2 dan 3 tanggal 14/8/26',NULL,5,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:14:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,42,42,'2026-08-18 02:14:36','2026-08-18 02:14:36',3,NULL,NULL),(28,'PENGAWASAN CCTV 18/8/26',NULL,5,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:14:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,42,42,'2026-08-18 02:14:36','2026-08-18 02:14:36',3,NULL,NULL),(29,'FOLLOW UP JIKA ADA TEMUAN',NULL,5,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:14:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,42,42,'2026-08-18 02:14:36','2026-08-18 02:14:36',3,NULL,NULL),(30,'PENEGECEKAN LIMBAH B3',NULL,5,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:14:36',NULL,NULL,NULL,NULL,NULL,NULL,NULL,10,42,42,'2026-08-18 02:14:36','2026-08-18 02:14:36',3,NULL,NULL),(31,'Playback CCTV shift 2 dan 3 tanggal 17/8/26',NULL,5,2,'2026-08-19','2026-08-19','2026-08-19','2026-08-19','not_started',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,11,42,42,'2026-08-18 02:18:48','2026-08-18 02:18:48',3,NULL,NULL),(32,'PENGAWASAN CCTV 19/8/26',NULL,5,2,'2026-08-19','2026-08-19','2026-08-19','2026-08-19','not_started',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,11,42,42,'2026-08-18 02:18:48','2026-08-18 02:18:48',3,NULL,NULL),(33,'FOLLOW UP JIKA ADA TEMUAN',NULL,5,2,'2026-08-19','2026-08-19','2026-08-19','2026-08-19','not_started',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,11,42,42,'2026-08-18 02:18:48','2026-08-18 02:18:48',3,NULL,NULL),(34,'PENEGECEKAN LIMBAH B3',NULL,5,2,'2026-08-19','2026-08-19','2026-08-19','2026-08-19','not_started',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,11,42,42,'2026-08-18 02:18:48','2026-08-18 02:18:48',3,NULL,NULL),(35,'MARKING KERJAKAN E02, E01, A02, A00, E04',NULL,38,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:27:16',NULL,NULL,NULL,NULL,NULL,NULL,NULL,12,42,42,'2026-08-18 02:27:16','2026-08-18 02:27:16',33,NULL,NULL),(36,'MARKING MANUAL SIZE BESAR E02, A00',NULL,38,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:27:16',NULL,NULL,NULL,NULL,NULL,NULL,NULL,12,42,42,'2026-08-18 02:27:16','2026-08-18 02:27:16',33,NULL,NULL),(37,'SIAPKAN BARANG UNTUK SHIFT 3',NULL,38,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 02:27:16',NULL,NULL,NULL,NULL,NULL,NULL,NULL,12,42,42,'2026-08-18 02:27:16','2026-08-18 02:27:16',33,NULL,NULL),(38,'BUBUT AS ENGSEL AYAKAN',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(39,'SCRAP MATRAS ALMINI DAN PASANG',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(40,'SERVIS AYAKAN SAND TREATMENT',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(41,'SERVIS LGK 300 NETTO FL, GANTI KAPASITOR 3.000 V, TRAFO.',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(42,'BUAT TABUNG 500KG',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(43,'RAKIT PANEL UNTUK NETTO FLANGE',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(44,'LEPAS PANEL MOLDING',NULL,24,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:05:56',NULL,NULL,NULL,NULL,NULL,NULL,NULL,13,42,42,'2026-08-18 03:05:56','2026-08-18 03:05:56',19,NULL,NULL),(45,'LANJUT PANEL NETTO FL',NULL,26,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:07:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,14,42,42,'2026-08-18 03:07:31','2026-08-18 03:07:31',21,NULL,NULL),(46,'PERBAIKAN ARGON 01 NETTO FL',NULL,26,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:07:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,14,42,42,'2026-08-18 03:07:31','2026-08-18 03:07:31',21,NULL,NULL),(47,'PERBAIKAN GEARBOX AYAKAN PASIR COR',NULL,26,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:07:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,14,42,42,'2026-08-18 03:07:31','2026-08-18 03:07:31',21,NULL,NULL),(48,'INSTALASI STOPKONTAK BENGKEL',NULL,26,6,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:07:31',NULL,NULL,NULL,NULL,NULL,NULL,NULL,14,42,42,'2026-08-18 03:07:31','2026-08-18 03:07:31',21,NULL,NULL),(49,'DAPUR: NORMAL, NAMUN KWH KENDALA SIKLUS MELTING -POURING 17/8/26 SHIFT 3 TIDAK TERDTEKSI, 10.00 KWH METER NORMAL',NULL,31,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:11:55',NULL,NULL,NULL,NULL,NULL,NULL,NULL,15,42,42,'2026-08-18 03:11:55','2026-08-18 03:11:55',26,NULL,NULL),(50,'PASIR: MULAI 15/8/26 MULAI MENGGUNAKAN SILICA J5 4B NILAI PERMEABILITY 267',NULL,31,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:11:55',NULL,NULL,NULL,NULL,NULL,NULL,NULL,15,42,42,'2026-08-18 03:11:55','2026-08-18 03:11:55',26,NULL,NULL),(51,'UJI TARIK: HEAT NUMBER A2150826, A3150826,A4150826 DAN A2170826 TELAH DIUJIKAN',NULL,31,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:11:55',NULL,NULL,NULL,NULL,NULL,NULL,NULL,15,42,42,'2026-08-18 03:11:55','2026-08-18 03:11:55',26,NULL,NULL),(52,'PROSES UNION 3\" JALAN DRAT DAN TEFLON RING 3\" PROSES SHIFT 3 ATAU 19/8/26',NULL,15,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:19:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,42,42,'2026-08-18 03:16:39','2026-08-18 18:19:44',12,NULL,NULL),(53,'SHIFT 3:SERONG, QC DN 200 ISO',NULL,34,9,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:20:24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,6,42,42,'2026-08-18 03:20:13','2026-08-18 18:20:24',29,NULL,NULL),(54,'20\" 10KA=2, 16\" PN10 RT=12, 18\" PN10 RT=4, 6\" PN16=71, 4\" 10KB=103, 1\" 10KA= 618, 10\" PN16 RF=10, 8\" PN10 RT=80, 4\" PN6= 46, 4\" PN 6 BL=89, 5\" PN16 RT=40.',NULL,20,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 03:39:12',NULL,NULL,NULL,NULL,NULL,NULL,NULL,17,42,42,'2026-08-18 03:39:12','2026-08-18 03:39:12',16,NULL,NULL),(55,'PEMETAAN QC FINISH BASED ON CUSTOMER DAN PROSES URUTAN BARANG MASUK SERONG',NULL,29,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:24:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,42,42,'2026-08-18 18:24:15','2026-08-18 18:24:15',24,NULL,NULL),(56,'QC FINISH MENDAHULUKAN BARANG URGENT',NULL,29,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:24:15',NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,42,42,'2026-08-18 18:24:15','2026-08-18 18:24:15',24,NULL,NULL),(57,'2\" PN16 14308 E01',NULL,22,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:29:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,18,42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17,NULL,NULL),(58,'10\" PN10BL 14408 E02',NULL,22,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:29:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,18,42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17,NULL,NULL),(59,'3\" PN16 14308 E01',NULL,22,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:29:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,18,42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17,NULL,NULL),(60,'8\" PN10RT 14308 E01',NULL,22,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:29:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,18,42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17,NULL,NULL),(61,'12\" PN16 LOOSE 14308 E02',NULL,22,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:29:57',NULL,NULL,NULL,NULL,NULL,NULL,NULL,18,42,42,'2026-08-18 18:29:57','2026-08-18 18:29:57',17,NULL,NULL),(62,'LINE 1: 1\", 2\" PN16 LOOSE 14308 E02',NULL,18,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:47:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,19,42,42,'2026-08-18 18:34:27','2026-08-18 18:47:03',14,NULL,NULL),(63,'LINE 2: 2 1/2\" PN 6 14408 E02,  2 1/2\" PN16 14308 E02',NULL,18,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:47:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,19,42,42,'2026-08-18 18:34:27','2026-08-18 18:47:03',14,NULL,NULL),(64,'LINE 3: 6\" 10KASCH; 8\",10\" PN10 14308 E01',NULL,18,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:47:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,19,42,42,'2026-08-18 18:34:27','2026-08-18 18:47:03',14,NULL,NULL),(65,'MANUAL BARU: 2\" SORF CF8, 2\" 10KA SCH',NULL,18,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:47:03',NULL,NULL,NULL,NULL,NULL,NULL,NULL,19,42,42,'2026-08-18 18:34:27','2026-08-18 18:47:03',14,NULL,NULL),(66,'A00=398, E04=193, E02=379 . TOTAL 970 PCS',NULL,28,7,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:50:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,20,42,42,'2026-08-18 18:50:00','2026-08-18 18:50:00',23,NULL,NULL),(67,'DISTRIBUSI NETTO DALAM 2.700/2.600, LANJUT BESOK -+2.800 PCS. KIMIA -+ 1.800 LANJUT BESOK',NULL,13,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:56:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,21,42,42,'2026-08-18 18:56:59','2026-08-18 18:56:59',10,NULL,NULL),(68,'OPERATOR BARU DITEMPATKAN DI G.FINISH DAN G.SERVIS',NULL,13,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:56:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,21,42,42,'2026-08-18 18:56:59','2026-08-18 18:56:59',10,NULL,NULL),(69,'BARANG POTONG SISA 20 TANJEK',NULL,13,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:56:59',NULL,NULL,NULL,NULL,NULL,NULL,NULL,21,42,42,'2026-08-18 18:56:59','2026-08-18 18:56:59',10,NULL,NULL),(70,'DISTRIBUSI 1.000/1.200 (2 OPR TIDAK MASUK)',NULL,13,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:59:41',NULL,NULL,NULL,NULL,NULL,NULL,NULL,22,42,42,'2026-08-18 18:59:41','2026-08-18 18:59:41',9,NULL,NULL),(71,'20\" PN16 BELUM TERPOTONG, PROSES MALAM',NULL,13,3,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 18:59:41',NULL,NULL,NULL,NULL,NULL,NULL,NULL,22,42,42,'2026-08-18 18:59:41','2026-08-18 18:59:41',9,NULL,NULL),(72,'HASIL CETAK SUDAH UPDATE, KENDALA 2 OPERATOR TIDAK MASUK',NULL,2,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:02:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,23,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1,NULL,NULL),(73,'UPDATE KURANG COR',NULL,2,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:02:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,23,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1,NULL,NULL),(74,'UPDATE KURANG PRODUKSI',NULL,2,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:02:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,23,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1,NULL,NULL),(75,'UPDATE KURANG QC FINISH',NULL,2,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:02:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,23,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1,NULL,NULL),(76,'KEJAR KEKURANGAN SQUARE DI KIMIA',NULL,2,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:02:00',NULL,NULL,NULL,NULL,NULL,NULL,NULL,23,42,42,'2026-08-18 19:02:00','2026-08-18 19:02:00',1,NULL,NULL),(77,'PERBAIKAN KONEKSI SPECTRO PUTUS',NULL,4,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:06:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,24,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2,NULL,NULL),(78,'PENARIKAN DAN PERAPIAN KABEL POWER METER TPS 2 KE HEATREATMENT',NULL,4,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:06:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,24,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2,NULL,NULL),(79,'PENARIKAN DAN PERAPIAN KABEL, SERTA PASANG SENSOR SUHU FITTING BARAT DAN TIMUR',NULL,4,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:06:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,24,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2,NULL,NULL),(80,'PERBAIKAN EXCEL PAJAK ERROR',NULL,4,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:06:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,24,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2,NULL,NULL),(81,'SAFETY INDUCTION DAN PENYERAHAN APD 4 KARYAWAN BARU NETTO DAN 1 BUBUT CNC',NULL,4,2,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:06:35',NULL,NULL,NULL,NULL,NULL,NULL,NULL,24,42,42,'2026-08-18 19:06:35','2026-08-18 19:06:35',2,NULL,NULL),(82,'PACKING LOKAL KIRIM BESOK 2.5 TON',NULL,8,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:09:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,25,42,42,'2026-08-18 19:09:44','2026-08-18 19:09:44',6,NULL,NULL),(83,'PACKING E02',NULL,8,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:09:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,25,42,42,'2026-08-18 19:09:44','2026-08-18 19:09:44',6,NULL,NULL),(84,'SERVIS OD: 3\" 10K (200 PCS), 1 1/2\" 10K (100 PCS)',NULL,8,1,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:09:44',NULL,NULL,NULL,NULL,NULL,NULL,NULL,25,42,42,'2026-08-18 19:09:44','2026-08-18 19:09:44',6,NULL,NULL),(85,'SAND: SHIFT 1 22S=151.3KG, 35S=133.1KG',NULL,23,5,'2026-08-19','2026-08-19','2026-08-18','2026-08-18','completed','2026-08-18 19:18:14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,26,42,42,'2026-08-18 19:16:23','2026-08-18 19:18:14',18,NULL,NULL),(86,'DRILL, BLASTING: SELESAIKAN 118/115 TANJEK (6JAM 50 MENIT)',NULL,23,5,'2026-08-19','2026-08-19','2026-08-18','2026-08-18','completed','2026-08-18 19:18:14',NULL,NULL,NULL,NULL,NULL,NULL,NULL,26,42,42,'2026-08-18 19:16:23','2026-08-18 19:18:14',18,NULL,NULL),(87,'SAND: SHIFT 1 22S=151.3KG, 35S=133.1KG',NULL,23,5,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:20:33',NULL,NULL,NULL,NULL,NULL,NULL,NULL,27,42,42,'2026-08-18 19:20:33','2026-08-18 19:20:33',18,NULL,NULL),(88,'DRILL, BLASTING: SELESAIKAN 118/115 TANJEK (6JAM 50 MENIT)',NULL,23,5,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:20:33',NULL,NULL,NULL,NULL,NULL,NULL,NULL,27,42,42,'2026-08-18 19:20:33','2026-08-18 19:20:33',18,NULL,NULL),(89,'UJI COBA EFISIENSI TANJEK PENGURANGAN KEPALA TANJEK BAGIAN BAWAH SEPARUH',NULL,39,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:25:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,28,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34,NULL,NULL),(90,'MENYELESAIKAN E02',NULL,39,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:25:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,28,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34,NULL,NULL),(91,'FLANGE SS DAN BESI 4\" KEBAWAH',NULL,39,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:25:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,28,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34,NULL,NULL),(92,'FITTING LOKAL DAN EXPORT',NULL,39,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:25:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,28,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34,NULL,NULL),(93,'TARGET HARIAN: FIT L= 2.536, EX=400; LEM',NULL,39,4,'2026-08-18','2026-08-18','2026-08-18','2026-08-18','completed','2026-08-18 19:25:20',NULL,NULL,NULL,NULL,NULL,NULL,NULL,28,42,42,'2026-08-18 19:25:20','2026-08-18 19:25:20',34,NULL,NULL);
/*!40000 ALTER TABLE `work_items` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-28 18:13:24
