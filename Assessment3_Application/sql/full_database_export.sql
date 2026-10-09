-- Harbourlight Theatre Box Office - full export (schema + sample data), generated with mysqldump
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: harbourlight_tickets
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `harbourlight_tickets`
--

/*!40000 DROP DATABASE IF EXISTS `harbourlight_tickets`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `harbourlight_tickets` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `harbourlight_tickets`;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_ref` char(9) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `status` enum('Paid','Refunded') NOT NULL DEFAULT 'Paid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES
(1,'HL-K7Q2M9',3,'Paid','2026-10-05 03:16:07'),
(2,'HL-P3X8D1',4,'Paid','2026-10-07 03:16:07'),
(3,'HL-B6N4T2',5,'Paid','2026-10-08 03:16:07'),
(4,'HL-R9V1C5',3,'Paid','2026-10-09 03:16:07'),
(5,'HL-W2H7J3',4,'Paid','2026-10-09 03:16:07'),
(6,'HL-M5F8Z6',5,'Paid','2026-09-28 03:16:07'),
(7,'HL-T4L6Y8',3,'Paid','2026-09-29 03:16:07'),
(8,'HL-D1S3G7',4,'Paid','2026-09-30 03:16:07'),
(9,'HL-Q8K2N4',4,'Refunded','2026-10-01 03:16:07');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performances`
--

DROP TABLE IF EXISTS `performances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performances` (
  `performance_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `show_id` int(10) unsigned NOT NULL,
  `venue_id` int(10) unsigned NOT NULL,
  `starts_at` datetime NOT NULL,
  `status` enum('Scheduled','Cancelled') NOT NULL DEFAULT 'Scheduled',
  PRIMARY KEY (`performance_id`),
  KEY `idx_perf_start` (`starts_at`),
  KEY `fk_perf_show` (`show_id`),
  KEY `fk_perf_venue` (`venue_id`),
  CONSTRAINT `fk_perf_show` FOREIGN KEY (`show_id`) REFERENCES `shows` (`show_id`),
  CONSTRAINT `fk_perf_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performances`
--

LOCK TABLES `performances` WRITE;
/*!40000 ALTER TABLE `performances` DISABLE KEYS */;
INSERT INTO `performances` VALUES
(1,1,1,'2026-10-10 19:30:00','Scheduled'),
(2,1,1,'2026-10-13 19:30:00','Scheduled'),
(3,2,1,'2026-10-17 14:00:00','Scheduled'),
(4,2,1,'2026-10-17 19:30:00','Scheduled'),
(5,3,1,'2026-10-24 20:00:00','Scheduled'),
(6,1,1,'2026-10-03 19:30:00','Scheduled');
/*!40000 ALTER TABLE `performances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `price_tiers`
--

DROP TABLE IF EXISTS `price_tiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `price_tiers` (
  `tier_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `performance_id` int(10) unsigned NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `price` decimal(8,2) NOT NULL,
  PRIMARY KEY (`tier_id`),
  UNIQUE KEY `uq_tier` (`performance_id`,`section_id`),
  KEY `fk_tier_section` (`section_id`),
  CONSTRAINT `fk_tier_perf` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tier_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`),
  CONSTRAINT `chk_price_positive` CHECK (`price` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `price_tiers`
--

LOCK TABLES `price_tiers` WRITE;
/*!40000 ALTER TABLE `price_tiers` DISABLE KEYS */;
INSERT INTO `price_tiers` VALUES
(1,1,1,65.00),
(2,1,2,45.00),
(3,2,1,65.00),
(4,2,2,45.00),
(5,3,1,55.00),
(6,3,2,39.00),
(7,4,1,72.00),
(8,4,2,50.00),
(9,5,1,40.00),
(10,5,2,30.00),
(11,6,1,65.00),
(12,6,2,45.00);
/*!40000 ALTER TABLE `price_tiers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seat_holds`
--

DROP TABLE IF EXISTS `seat_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seat_holds` (
  `hold_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `performance_id` int(10) unsigned NOT NULL,
  `seat_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`hold_id`),
  UNIQUE KEY `uq_hold_seat` (`performance_id`,`seat_id`),
  KEY `idx_hold_expiry` (`expires_at`),
  KEY `fk_hold_seat` (`seat_id`),
  KEY `fk_hold_user` (`user_id`),
  CONSTRAINT `fk_hold_perf` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hold_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`seat_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hold_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seat_holds`
--

LOCK TABLES `seat_holds` WRITE;
/*!40000 ALTER TABLE `seat_holds` DISABLE KEYS */;
/*!40000 ALTER TABLE `seat_holds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `seats`
--

DROP TABLE IF EXISTS `seats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seats` (
  `seat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `row_label` char(2) NOT NULL,
  `seat_number` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`seat_id`),
  UNIQUE KEY `uq_seat_position` (`section_id`,`row_label`,`seat_number`),
  CONSTRAINT `fk_seat_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seats`
--

LOCK TABLES `seats` WRITE;
/*!40000 ALTER TABLE `seats` DISABLE KEYS */;
INSERT INTO `seats` VALUES
(1,1,'A',1),
(2,1,'A',2),
(3,1,'A',3),
(4,1,'A',4),
(5,1,'A',5),
(6,1,'A',6),
(7,1,'A',7),
(8,1,'A',8),
(9,1,'A',9),
(10,1,'A',10),
(11,1,'A',11),
(12,1,'A',12),
(13,1,'B',1),
(14,1,'B',2),
(15,1,'B',3),
(16,1,'B',4),
(17,1,'B',5),
(18,1,'B',6),
(19,1,'B',7),
(20,1,'B',8),
(21,1,'B',9),
(22,1,'B',10),
(23,1,'B',11),
(24,1,'B',12),
(25,1,'C',1),
(26,1,'C',2),
(27,1,'C',3),
(28,1,'C',4),
(29,1,'C',5),
(30,1,'C',6),
(31,1,'C',7),
(32,1,'C',8),
(33,1,'C',9),
(34,1,'C',10),
(35,1,'C',11),
(36,1,'C',12),
(37,1,'D',1),
(38,1,'D',2),
(39,1,'D',3),
(40,1,'D',4),
(41,1,'D',5),
(42,1,'D',6),
(43,1,'D',7),
(44,1,'D',8),
(45,1,'D',9),
(46,1,'D',10),
(47,1,'D',11),
(48,1,'D',12),
(49,1,'E',1),
(50,1,'E',2),
(51,1,'E',3),
(52,1,'E',4),
(53,1,'E',5),
(54,1,'E',6),
(55,1,'E',7),
(56,1,'E',8),
(57,1,'E',9),
(58,1,'E',10),
(59,1,'E',11),
(60,1,'E',12),
(61,1,'F',1),
(62,1,'F',2),
(63,1,'F',3),
(64,1,'F',4),
(65,1,'F',5),
(66,1,'F',6),
(67,1,'F',7),
(68,1,'F',8),
(69,1,'F',9),
(70,1,'F',10),
(71,1,'F',11),
(72,1,'F',12),
(73,2,'G',1),
(74,2,'G',2),
(75,2,'G',3),
(76,2,'G',4),
(77,2,'G',5),
(78,2,'G',6),
(79,2,'G',7),
(80,2,'G',8),
(81,2,'G',9),
(82,2,'G',10),
(83,2,'H',1),
(84,2,'H',2),
(85,2,'H',3),
(86,2,'H',4),
(87,2,'H',5),
(88,2,'H',6),
(89,2,'H',7),
(90,2,'H',8),
(91,2,'H',9),
(92,2,'H',10);
/*!40000 ALTER TABLE `seats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `section_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `venue_id` int(10) unsigned NOT NULL,
  `section_name` varchar(50) NOT NULL,
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_section_name` (`venue_id`,`section_name`),
  CONSTRAINT `fk_section_venue` FOREIGN KEY (`venue_id`) REFERENCES `venues` (`venue_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES
(2,1,'Circle'),
(1,1,'Stalls');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shows`
--

DROP TABLE IF EXISTS `shows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `shows` (
  `show_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `genre` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `duration_mins` smallint(5) unsigned NOT NULL,
  `age_rating` varchar(10) NOT NULL DEFAULT 'G',
  PRIMARY KEY (`show_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shows`
--

LOCK TABLES `shows` WRITE;
/*!40000 ALTER TABLE `shows` DISABLE KEYS */;
INSERT INTO `shows` VALUES
(1,'The Lighthouse Keeper\'s Daughter','Drama','A storm cuts a remote island off from the mainland, and a family secret surfaces with the tide.',125,'PG'),
(2,'Tide & Timber','Musical','A toe-tapping musical about a shipbuilding town that refuses to let its last shipyard close.',140,'G'),
(3,'Midwinter Comedy Gala','Comedy','Six local stand-up comedians, one compere, and no topic off limits.',110,'M');
/*!40000 ALTER TABLE `shows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tickets` (
  `ticket_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `performance_id` int(10) unsigned NOT NULL,
  `seat_id` int(10) unsigned NOT NULL,
  `price_paid` decimal(8,2) NOT NULL,
  `ticket_code` char(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `checked_in_at` datetime DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  UNIQUE KEY `uq_one_live_ticket` (`performance_id`,`seat_id`,`is_active`),
  KEY `fk_ticket_order` (`order_id`),
  KEY `fk_ticket_seat` (`seat_id`),
  CONSTRAINT `fk_ticket_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_perf` FOREIGN KEY (`performance_id`) REFERENCES `performances` (`performance_id`),
  CONSTRAINT `fk_ticket_seat` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`seat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES
(1,1,1,5,65.00,'T7A2K9Q1MX',1,NULL),
(2,1,1,6,65.00,'T4B8N2W6PL',1,NULL),
(3,2,1,39,65.00,'T9C3J5R1VD',1,NULL),
(4,2,1,40,65.00,'T2D6H8S4YB',1,NULL),
(5,2,1,41,65.00,'T5E1L7U3FK',1,NULL),
(6,2,1,42,65.00,'T8F4M2Z9GC',1,NULL),
(7,3,1,76,45.00,'T3G9P6X1HN',1,NULL),
(8,3,1,77,45.00,'T6H2Q8A5JR',1,NULL),
(9,4,2,31,65.00,'T1J7R3B9KS',1,NULL),
(10,4,2,32,65.00,'T4K5S1C6LT',1,NULL),
(11,5,4,13,72.00,'T7L8T4D2MU',1,NULL),
(12,5,4,14,72.00,'T2M3U9E7NV',1,NULL),
(13,5,4,15,72.00,'T5N6V2F3PW',1,NULL),
(14,6,6,1,65.00,'T8P1W7G8QX',1,'2026-10-03 19:10:00'),
(15,6,6,2,65.00,'T3Q4X3H4RY',1,'2026-10-03 19:10:00'),
(16,6,6,3,65.00,'T6R9Y8J1SZ',1,'2026-10-03 19:10:00'),
(17,6,6,4,65.00,'T1S2Z4K6TA',1,'2026-10-03 19:10:00'),
(18,7,6,55,65.00,'T4T7A9L2UB',1,'2026-10-03 19:10:00'),
(19,7,6,56,65.00,'T7U3B5M8VC',1,'2026-10-03 19:10:00'),
(20,8,6,83,45.00,'T2V6C1N3WD',1,'2026-10-03 19:10:00'),
(21,8,6,84,45.00,'T5W1D7P9XE',1,'2026-10-03 19:10:00'),
(22,8,6,85,45.00,'T8X4E2Q5YF',1,'2026-10-03 19:10:00'),
(23,9,2,61,65.00,'T3Y9F8R1ZG',NULL,NULL),
(24,9,2,62,65.00,'T6Z2G3S7AH',NULL,NULL);
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','staff','manager') NOT NULL DEFAULT 'customer',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Maya Okafor','maya.manager@harbourlight.test','$2y$10$r7S7aNX60zT7.s5NtzQoq.GDG7TEEgkVjTHZ9OLdohZEWeNUQ6XIm','0400 111 222','manager','2026-10-10 03:16:07'),
(2,'Sam Whitfield','sam.staff@harbourlight.test','$2y$10$r7S7aNX60zT7.s5NtzQoq.GDG7TEEgkVjTHZ9OLdohZEWeNUQ6XIm','0400 333 444','staff','2026-10-10 03:16:07'),
(3,'Chris Tan','chris.customer@harbourlight.test','$2y$10$r7S7aNX60zT7.s5NtzQoq.GDG7TEEgkVjTHZ9OLdohZEWeNUQ6XIm','0400 555 666','customer','2026-10-10 03:16:07'),
(4,'Jordan Lee','jordan.lee@harbourlight.test','$2y$10$r7S7aNX60zT7.s5NtzQoq.GDG7TEEgkVjTHZ9OLdohZEWeNUQ6XIm','0400 777 888','customer','2026-10-10 03:16:07'),
(5,'Priya Nair','priya.nair@harbourlight.test','$2y$10$r7S7aNX60zT7.s5NtzQoq.GDG7TEEgkVjTHZ9OLdohZEWeNUQ6XIm',NULL,'customer','2026-10-10 03:16:07');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `venues`
--

DROP TABLE IF EXISTS `venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `venues` (
  `venue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `venue_name` varchar(100) NOT NULL,
  `address` varchar(200) NOT NULL,
  PRIMARY KEY (`venue_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `venues`
--

LOCK TABLES `venues` WRITE;
/*!40000 ALTER TABLE `venues` DISABLE KEYS */;
INSERT INTO `venues` VALUES
(1,'Harbourlight Theatre','12 Wharf Street, Riverbend VIC 3000');
/*!40000 ALTER TABLE `venues` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'harbourlight_tickets'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
