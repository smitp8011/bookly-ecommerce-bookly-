-- =====================================================
-- BookNest — Complete Database Schema & Initial Data
-- Book Ordering and Delivery System
-- =====================================================
CREATE DATABASE IF NOT EXISTS `bookly_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bookly_db`;
-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bookly_db
-- ------------------------------------------------------
-- Server version	8.0.44

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
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `books` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `author` varchar(150) NOT NULL,
  `category_id` int unsigned NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int unsigned NOT NULL DEFAULT '0',
  `description` text,
  `cover_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `idx_books_created` (`created_at`),
  KEY `idx_books_price` (`price`),
  KEY `idx_books_title_author` (`title`,`author`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES (1,'Red Queen','Victoria Aveyard',2,499.00,50,'A gripping tale of a kingdom divided by blood — red and silver — and the girl who could upend it all.','red_queen.jpg','2026-07-30 19:10:31'),(2,'Nightshade','Andrea Cremer',2,399.00,10,'A world of pack loyalty, hidden magic, and forbidden love simmering beneath the surface.','nightshade.jpg','2026-07-30 19:10:31'),(3,'Darknet','Matthew Mather',3,349.00,12,'A near-future thriller where the internet itself becomes the battlefield.','darknet.jpg','2026-07-30 19:10:31'),(4,'Shattered','Kevin Hearne',1,299.00,20,'An urban fantasy adventure blending old-world myth with modern chaos.','shattered.jpg','2026-07-30 19:10:31'),(5,'The Girl of Ink & Stars','Kiran Millwood Hargrave',4,349.00,15,'A map-maker\'s daughter sets out on a journey that will redraw the world she knows.','the_girl_of_ink_and_stars.jpg','2026-07-30 19:10:31'),(6,'Freefall','Jessica Barry',3,379.00,10,'A woman presumed dead after a plane crash — and the mother who refuses to believe it.','freefall.jpg','2026-07-30 19:10:31'),(7,'Boring Girls: A Novel','Sara Taylor',1,329.00,50,'A sharp, unflinching coming-of-age story about ambition, friendship, and rock and roll.','boring_girls_a_novel.jpg','2026-07-30 19:10:31'),(8,'Clever Lands','Lucy Crehan',5,449.00,9,'A teacher\'s journey through the world\'s top-performing education systems.','clever_lands.jpg','2026-07-30 19:10:31'),(9,'Economic Facts and Fallacies','Thomas Sowell',5,499.00,16,'A clear-eyed look at popular misconceptions in economics and public policy.','economic.jpg','2026-07-30 19:10:31'),(10,'History of Modern Architecture','Kenneth Frampton',5,599.00,8,'A definitive survey of architectural movements shaping the modern built world.','history_of_modern_architecture.jpg','2026-07-30 19:10:31'),(11,'Holy Ghosts','Gary Jansen',5,299.00,11,'A memoir of faith, doubt, and the search for meaning in the everyday.','holy_ghosts.jpg','2026-07-30 19:10:31'),(12,'Radical Gardening','George McKay',6,379.00,13,'How gardens have been sites of protest, politics, and quiet rebellion.','radical_gardening.jpg','2026-07-30 19:10:31'),(13,'Be Well, Bee','Rosie Sanders',6,249.00,22,'A gentle illustrated guide to the life and importance of bees.','be_well_bee.jpg','2026-07-30 19:10:31'),(14,'The Happy Lemon','Cara Chen',1,279.00,17,'A whimsical, bittersweet story of family, memory, and citrus groves.','the_happy_lemon.jpg','2026-07-30 19:10:31'),(15,'The World: A Traveler\'s Guide','Simon Winchester',5,549.00,7,'Essays on geography, culture, and the pull of the horizon.','the_world.jpg','2026-07-30 19:10:31'),(16,'Bash & Lucy','Rita Rosen',4,229.00,15,'A heartwarming adventure of a dog, a girl, and the summer that changes everything.','bash_and_lucy-2.jpg','2026-07-30 19:10:31'),(17,'Atomic Habits','James Clear',5,449.00,20,'Atomic Habits by James Clear is the definitive guide to breaking bad behaviors and adopting good ones in four steps, showing you how small, incremental, everyday routines compound into massive, life-altering positive changes over time. With actionable strategies drawn from biology, psychology, and neuroscience, this book makes habit formation simple and intuitive.','atomic_habits.jpg','2026-08-22 12:29:01'),(18,'The Psychology of Money','Morgan Housel',5,399.00,18,'Timeless lessons on wealth, greed, and happiness. Doing well with money isn\'t necessarily about what you know. It\'s about how you behave. And behavior is hard to teach, even to really smart people. Morgan Housel shares 19 short stories exploring the strange ways people think about money and teaches you how to make better sense of one of life\'s most important topics.','psychology_of_money.jpg','2026-08-22 12:29:02'),(19,'The Silent Patient','Alex Michaelides',3,379.00,12,'Alicia Berenson’s life is seemingly perfect. A famous painter married to an in-demand fashion photographer, she lives in a grand house with big windows overlooking a park in one of London’s most desirable areas. One evening her husband returns home late from a fashion shoot, and Alicia shoots him five times in the face and then never speaks another word. A shocking psychological thriller with an unforgettable twist.','the_silent_patient.jpg','2026-08-22 12:29:06'),(20,'The Alchemist','Paulo Coelho',1,299.00,25,'Paulo Coelho\'s masterpiece tells the mystical story of Santiago, an Andalusian shepherd boy who yearns to travel in search of a worldly treasure. His quest will lead him to riches far different—and far more satisfying—than he ever imagined. Santiago\'s journey teaches us about the essential wisdom of listening to our hearts, of recognizing opportunity and learning to read the omens strewn along life\'s path, and, above all, following our dreams.','the_alchemist.jpg','2026-08-22 12:29:07'),(21,'Ikigai: The Japanese Secret to a Long Life','Héctor García & Francesc Miralles',5,349.00,15,'According to the Japanese, everyone has an ikigai—a reason for living. And according to the residents of the Japanese village with the world’s longest-living people, finding it is the key to a happier and longer life. Having a strong sense of ikigai means that each day is infused with meaning. It’s the reason we get up in the morning. This book reveals the secret to their longevity and happiness.','ikigai.jpg','2026-08-22 12:29:09'),(22,'Dune','Frank Herbert',2,599.00,11,'Set on the desert planet Arrakis, Dune is the story of the boy Paul Atreides, heir to a noble family tasked with ruling an inhospitable world where the only thing of value is the \'spice\' melange, a drug capable of extending life and enhancing consciousness. A stunning blend of adventure and mysticism, environmentalism and politics, Dune won the first Nebula Award and shared the Hugo Award.','dune.jpg','2026-08-22 12:29:10');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '1',
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (26,4,1,1,'2026-11-26 13:24:03'),(27,4,2,1,'2026-11-26 13:24:03');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (2,'Fantasy'),(1,'Fiction'),(5,'Non-Fiction'),(6,'Science & Nature'),(3,'Thriller'),(4,'Young Adult');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `discount_type` enum('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_uses` int unsigned DEFAULT NULL,
  `used_count` int unsigned NOT NULL DEFAULT '0',
  `expires_at` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_coupons_active_exp` (`active`,`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'FLAT10','percent',10.00,300.00,NULL,0,NULL,1,'2026-07-30 19:10:51'),(2,'FLAT50','flat',50.00,400.00,200,0,NULL,1,'2026-07-30 19:10:51');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `quantity` int unsigned NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (2,2,1,'Red Queen',1,499.00),(3,2,2,'Nightshade',1,399.00),(18,10,1,'Red Queen',1,499.00),(19,11,1,'Red Queen',1,499.00),(20,11,2,'Nightshade',1,399.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(500) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL,
  `coupon_code` varchar(40) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled','replacement_requested','replacement_approved','replaced','replacement_rejected') NOT NULL DEFAULT 'pending',
  `delivered_on` timestamp NULL DEFAULT NULL,
  `replacement_status` enum('none','requested','approved','rejected','replaced') NOT NULL DEFAULT 'none',
  `replacement_reason` varchar(255) DEFAULT NULL,
  `replacement_notes` text,
  `replacement_requested_on` timestamp NULL DEFAULT NULL,
  `placed_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_gift` tinyint(1) NOT NULL DEFAULT '0',
  `gift_price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `gift_recipient` varchar(100) DEFAULT NULL,
  `gift_sender` varchar(100) DEFAULT NULL,
  `gift_message` text,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_placed_on` (`placed_on`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (2,4,'viraj','9924227725','virajparmar487@gmail.com','230,jani restaurant','Cash on Delivery',NULL,NULL,0.00,898.00,'replaced','2026-11-26 12:58:23','replaced','Missing or Blank Pages','','2026-11-26 12:58:39','2026-08-20 12:45:34',0,0.00,NULL,NULL,NULL),(10,4,'viraj parmar','9924227725','virajparmar487@gmail.com','230,jani restaurant','UPI / QR (Instant Paid)','pay_upi_1219F758E7',NULL,0.00,548.00,'delivered','2026-11-26 13:02:24','replaced','Print Quality / Binding Defect','','2026-11-26 13:02:36','2026-11-26 13:00:47',1,49.00,'','',''),(11,4,'viraj parmar','9924227725','virajparmar487@gmail.com','230,jani restaurant, Local, State - 400001','Debit/Credit Card (Paid)','pay_card_60F11B6841',NULL,0.00,898.00,'shipped','2026-11-26 14:34:12','none',NULL,NULL,NULL,'2026-11-26 13:23:40',0,0.00,NULL,NULL,NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (3,4,'$2y$10$nGfbB4xDxNAsCeRVDNK3y.PIgx7CDrne4X/gCGjUmoJY.bDTzHAwm','2026-08-20 12:46:07',1,'2026-08-20 10:36:07'),(4,4,'$2y$10$tRRR/3UwKUtvMts.xx4izeeS24ywvv4XJe.jVFCka7b/nUPiwQt5.','2026-08-20 12:46:52',1,'2026-08-20 10:36:52'),(5,4,'$2y$10$LRLaOOPmMPpk3uQDo2itaebGtX4oWBEQfDuxs3mCWowqlAGKON7sm','2026-08-20 13:29:16',1,'2026-08-20 11:19:16'),(6,4,'$2y$10$yDfnGiZlsEHfz0qRzku95ejXqgo3aRIZTJDBS7zroRlf0J0/P2RUi','2026-08-20 13:30:03',1,'2026-08-20 11:20:03'),(7,4,'$2y$10$jkf4FWE0ealEuGSe5suGP.v6o5wcU1Ma0UhssvDbP3ksxoMyeaRDG','2026-08-20 13:47:18',1,'2026-08-20 11:37:18'),(8,4,'$2y$10$t1xFxU/7VaTr2GWKXF9.Ye0hQ/CJMbIaXtHcUhnQzd92s5mIThfWu','2026-08-20 18:24:08',1,'2026-08-20 12:44:09'),(9,4,'$2y$10$CxCZ3ps4bfCM/0E2WA.I9uk6QWoO1LiXimh7.4WRTiZWtA.gjk0PO','2026-11-26 18:34:26',1,'2026-11-26 12:54:26');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recently_viewed`
--

DROP TABLE IF EXISTS `recently_viewed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recently_viewed` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  KEY `fk_recently_viewed_book` (`book_id`),
  CONSTRAINT `fk_recently_viewed_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recently_viewed_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recently_viewed`
--

LOCK TABLES `recently_viewed` WRITE;
/*!40000 ALTER TABLE `recently_viewed` DISABLE KEYS */;
INSERT INTO `recently_viewed` VALUES (26,4,1,'2026-11-26 14:58:02');
/*!40000 ALTER TABLE `recently_viewed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `book_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `review_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_reviews_rating` (`rating`),
  KEY `idx_reviews_book_id` (`book_id`),
  KEY `idx_reviews_book_user` (`book_id`,`user_id`),
  CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating_range` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (7,1,4,4,'best book',NULL,'2026-11-26 14:58:02');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_addresses`
--

DROP TABLE IF EXISTS `user_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_addresses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `address_type` enum('home','office','other') NOT NULL DEFAULT 'home',
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address_line` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `pincode` varchar(20) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_addresses`
--

LOCK TABLES `user_addresses` WRITE;
/*!40000 ALTER TABLE `user_addresses` DISABLE KEYS */;
INSERT INTO `user_addresses` VALUES (1,1,'home','user1','9924227725','230,jani restaurant','Local','State','400001',1,'2026-08-20 20:38:11'),(2,4,'home','viraj parmar','9924227725','230,jani restaurant','Local','State','400001',1,'2026-11-26 13:00:47');
/*!40000 ALTER TABLE `user_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_bookshelf`
--

DROP TABLE IF EXISTS `user_bookshelf`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_bookshelf` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `status` enum('want_to_read','reading','completed') NOT NULL DEFAULT 'want_to_read',
  `progress_pct` int NOT NULL DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `user_bookshelf_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_bookshelf_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_bookshelf`
--

LOCK TABLES `user_bookshelf` WRITE;
/*!40000 ALTER TABLE `user_bookshelf` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_bookshelf` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_reading_challenge`
--

DROP TABLE IF EXISTS `user_reading_challenge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_reading_challenge` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `year` int NOT NULL,
  `target_books` int NOT NULL DEFAULT '12',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_year` (`user_id`,`year`),
  CONSTRAINT `user_reading_challenge_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_reading_challenge`
--

LOCK TABLES `user_reading_challenge` WRITE;
/*!40000 ALTER TABLE `user_reading_challenge` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_reading_challenge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (4,'viraj','virajparmar487@gmail.com','9924227725','230,jani restaurant','$2y$10$nVZ8XqGVXBGGp.BlwpaMZOaaW28a1stgdTGfXNdRyMFG28eTFoVGq','user','2026-08-20 10:35:39'),(6,'Smit Nayankumar Parmar','smitp8011@gmail.com',NULL,NULL,'$2y$10$ZCHAX8WhXQydIYKr/c3KuOSPFburu9O3FAyVXQQCeOEDR/FkKGxm2','admin','2026-11-26 14:47:26');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wishlist` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `book_id` int unsigned NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlist`
--

LOCK TABLES `wishlist` WRITE;
/*!40000 ALTER TABLE `wishlist` DISABLE KEYS */;
INSERT INTO `wishlist` VALUES (6,4,1,'2026-11-26 14:26:30');
/*!40000 ALTER TABLE `wishlist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'bookly_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-26 20:51:52

