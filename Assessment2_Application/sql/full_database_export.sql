/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: library_system
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



CREATE DATABASE /*!32312 IF NOT EXISTS*/ `library` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `library`;



DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `book_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `total_copies` int(11) NOT NULL DEFAULT 1,
  `available_copies` int(11) NOT NULL DEFAULT 1,
  `added_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`book_id`),
  UNIQUE KEY `isbn` (`isbn`),
  KEY `idx_books_category` (`category_id`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_copies` CHECK (`available_copies` >= 0 and `available_copies` <= `total_copies`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES
(1,'Clean Code','Robert C. Martin','9780132350884',1,3,3,'2026-09-26 11:19:47'),
(2,'Introduction to Algorithms','Cormen, Leiserson, Rivest, Stein','9780262033848',1,2,2,'2026-09-26 11:19:47'),
(3,'Database System Concepts','Silberschatz, Korth, Sudarshan','9780078022159',1,2,1,'2026-09-26 11:19:47'),
(4,'1984','George Orwell','9780451524935',2,4,3,'2026-09-26 11:19:47'),
(5,'To Kill a Mockingbird','Harper Lee','9780061120084',2,3,3,'2026-09-26 11:19:47'),
(6,'A Brief History of Time','Stephen Hawking','9780553380163',5,2,2,'2026-09-26 11:19:47'),
(7,'Sapiens','Yuval Noah Harari','9780062316097',4,3,2,'2026-09-26 11:19:47'),
(8,'Calculus: Early Transcendentals','James Stewart','9781285741550',3,2,2,'2026-09-26 11:19:47'),
(9,'The Pragmatic Programmer','David Thomas, Andrew Hunt','9780135957059',1,2,2,'2026-09-26 11:19:47'),
(10,'Linear Algebra Done Right','Sheldon Axler','9783319110790',3,2,2,'2026-09-26 11:19:47');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(80) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Computer Science'),
(2,'Fiction'),
(4,'History'),
(3,'Mathematics'),
(5,'Science');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;



DROP TABLE IF EXISTS `reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `reservation_date` datetime NOT NULL DEFAULT current_timestamp(),
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Collected','Returned','Cancelled') NOT NULL DEFAULT 'Pending',
  PRIMARY KEY (`reservation_id`),
  KEY `idx_res_user` (`user_id`),
  KEY `idx_res_book` (`book_id`),
  KEY `idx_res_status` (`status`),
  CONSTRAINT `fk_res_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_res_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



LOCK TABLES `reservations` WRITE;
/*!40000 ALTER TABLE `reservations` DISABLE KEYS */;
INSERT INTO `reservations` VALUES
(1,2,3,'2026-09-10 09:15:00','2026-09-24',NULL,'Collected'),
(2,3,7,'2026-09-12 14:02:00','2026-09-26',NULL,'Approved'),
(3,4,1,'2026-09-20 11:30:00','2026-10-10',NULL,'Approved'),
(4,2,5,'2026-08-01 10:00:00','2026-08-15','2026-08-14','Returned'),
(5,2,4,'2026-09-26 11:20:20',NULL,NULL,'Pending');
/*!40000 ALTER TABLE `reservations` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('member','librarian') NOT NULL DEFAULT 'member',
  `join_date` date NOT NULL DEFAULT curdate(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;



LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Sarah Thompson','sarah.librarian@library.edu','$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2','0400111222','librarian','2024-01-10','2026-09-26 11:19:47'),
(2,'Alex Nguyen','alex.student@library.edu','$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2','0400333444','member','2025-02-14','2026-09-26 11:19:47'),
(3,'Priya Sharma','priya.sharma@library.edu','$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2','0400555666','member','2025-03-01','2026-09-26 11:19:47'),
(4,'Jordan Lee','jordan.lee@library.edu','$2y$10$m6HbjZD1uuXTzKuYVg26geLKeJIz7ilEqrNjf2h55NgCE51kp4Ac2','0400777888','member','2025-05-20','2026-09-26 11:19:47');
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

-- Dump completed on 2026-09-26 11:25:45
