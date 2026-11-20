-- Bookly Complete Database Dump
-- Generated on: 2026-08-20 23:02:38

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `books`;
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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `books` VALUES
('1', 'Red Queen', 'Victoria Aveyard', '2', '499.00', '20', 'A gripping tale of a kingdom divided by blood — red and silver — and the girl who could upend it all.', 'red_queen.jpg', '2026-07-31 00:40:31'),
('2', 'Nightshade', 'Andrea Cremer', '2', '399.00', '17', 'A world of pack loyalty, hidden magic, and forbidden love simmering beneath the surface.', 'nightshade.jpg', '2026-07-31 00:40:31'),
('3', 'Darknet', 'Matthew Mather', '3', '349.00', '12', 'A near-future thriller where the internet itself becomes the battlefield.', 'darknet.jpg', '2026-07-31 00:40:31'),
('4', 'Shattered', 'Kevin Hearne', '1', '299.00', '20', 'An urban fantasy adventure blending old-world myth with modern chaos.', 'shattered.jpg', '2026-07-31 00:40:31'),
('5', 'The Girl of Ink & Stars', 'Kiran Millwood Hargrave', '4', '349.00', '15', 'A map-maker\'s daughter sets out on a journey that will redraw the world she knows.', 'the_girl_of_ink_and_stars.jpg', '2026-07-31 00:40:31'),
('6', 'Freefall', 'Jessica Barry', '3', '379.00', '10', 'A woman presumed dead after a plane crash — and the mother who refuses to believe it.', 'freefall.jpg', '2026-07-31 00:40:31'),
('7', 'Boring Girls: A Novel', 'Sara Taylor', '1', '329.00', '0', 'A sharp, unflinching coming-of-age story about ambition, friendship, and rock and roll.', 'boring_girls_a_novel.jpg', '2026-07-31 00:40:31'),
('8', 'Clever Lands', 'Lucy Crehan', '5', '449.00', '9', 'A teacher\'s journey through the world\'s top-performing education systems.', 'clever_lands.jpg', '2026-07-31 00:40:31'),
('9', 'Economic Facts and Fallacies', 'Thomas Sowell', '5', '499.00', '16', 'A clear-eyed look at popular misconceptions in economics and public policy.', 'economic.jpg', '2026-07-31 00:40:31'),
('10', 'History of Modern Architecture', 'Kenneth Frampton', '5', '599.00', '8', 'A definitive survey of architectural movements shaping the modern built world.', 'history_of_modern_architecture.jpg', '2026-07-31 00:40:31'),
('11', 'Holy Ghosts', 'Gary Jansen', '5', '299.00', '11', 'A memoir of faith, doubt, and the search for meaning in the everyday.', 'holy_ghosts.jpg', '2026-07-31 00:40:31'),
('12', 'Radical Gardening', 'George McKay', '6', '379.00', '13', 'How gardens have been sites of protest, politics, and quiet rebellion.', 'radical_gardening.jpg', '2026-07-31 00:40:31'),
('13', 'Be Well, Bee', 'Rosie Sanders', '6', '249.00', '22', 'A gentle illustrated guide to the life and importance of bees.', 'be_well_bee.jpg', '2026-07-31 00:40:31'),
('14', 'The Happy Lemon', 'Cara Chen', '1', '279.00', '17', 'A whimsical, bittersweet story of family, memory, and citrus groves.', 'the_happy_lemon.jpg', '2026-07-31 00:40:31'),
('15', 'The World: A Traveler\'s Guide', 'Simon Winchester', '5', '549.00', '7', 'Essays on geography, culture, and the pull of the horizon.', 'the_world.jpg', '2026-07-31 00:40:31'),
('16', 'Bash & Lucy', 'Rita Rosen', '4', '229.00', '19', 'A heartwarming adventure of a dog, a girl, and the summer that changes everything.', 'bash_and_lucy-2.jpg', '2026-07-31 00:40:31');

DROP TABLE IF EXISTS `cart`;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `cart` VALUES
('2', '1', '4', '20', '2026-08-05 15:41:43'),
('5', '1', '1', '3', '2026-08-20 19:25:30'),
('6', '1', '2', '4', '2026-08-20 19:25:37'),
('7', '1', '3', '2', '2026-08-20 19:56:48'),
('9', '1', '16', '1', '2026-08-20 22:28:38'),
('10', '1', '15', '1', '2026-08-20 22:28:40');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `categories` VALUES
('2', 'Fantasy'),
('1', 'Fiction'),
('5', 'Non-Fiction'),
('6', 'Science & Nature'),
('3', 'Thriller'),
('4', 'Young Adult');

DROP TABLE IF EXISTS `coupons`;
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

INSERT INTO `coupons` VALUES
('1', 'WELCOME10', 'percent', '10.00', '300.00', NULL, '0', NULL, '1', '2026-07-31 00:40:51'),
('2', 'FLAT50', 'flat', '50.00', '400.00', '200', '0', NULL, '1', '2026-07-31 00:40:51');

DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `message` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `order_items`;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `order_items` VALUES
('1', '1', '7', 'Boring Girls: A Novel', '14', '329.00'),
('2', '2', '1', 'Red Queen', '1', '499.00'),
('3', '2', '2', 'Nightshade', '1', '399.00');

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) NOT NULL,
  `address` varchar(500) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `coupon_code` varchar(40) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `placed_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_placed_on` (`placed_on`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `orders` VALUES
('1', '1', 'user1', '7383293199', 'user1@gmail.com', 'jani', 'Card', NULL, '0.00', '4606.00', 'shipped', '2026-07-31 00:44:26'),
('2', '4', 'viraj', '9924227725', 'virajparmar487@gmail.com', '230,jani restaurant', 'Cash on Delivery', NULL, '0.00', '898.00', 'shipped', '2026-08-20 18:15:34');

DROP TABLE IF EXISTS `password_resets`;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `password_resets` VALUES
('1', '3', '$2y$10$ivK6rw7ElBAbJ8N4eLx7FefSmZ6v8nA9H.G7nYQYqo.eJylqhXope', '2026-08-20 12:41:48', '1', '2026-08-20 16:01:48'),
('2', '3', '$2y$10$X5AdfsmTi.t2zdTQcvloluG0PdXIKisBTZ2ctvu9s2qJFBtjPphfW', '2026-08-20 12:44:00', '0', '2026-08-20 16:04:00'),
('3', '4', '$2y$10$nGfbB4xDxNAsCeRVDNK3y.PIgx7CDrne4X/gCGjUmoJY.bDTzHAwm', '2026-08-20 12:46:07', '1', '2026-08-20 16:06:07'),
('4', '4', '$2y$10$tRRR/3UwKUtvMts.xx4izeeS24ywvv4XJe.jVFCka7b/nUPiwQt5.', '2026-08-20 12:46:52', '1', '2026-08-20 16:06:52'),
('5', '4', '$2y$10$LRLaOOPmMPpk3uQDo2itaebGtX4oWBEQfDuxs3mCWowqlAGKON7sm', '2026-08-20 13:29:16', '1', '2026-08-20 16:49:16'),
('6', '4', '$2y$10$yDfnGiZlsEHfz0qRzku95ejXqgo3aRIZTJDBS7zroRlf0J0/P2RUi', '2026-08-20 13:30:03', '1', '2026-08-20 16:50:03'),
('7', '4', '$2y$10$jkf4FWE0ealEuGSe5suGP.v6o5wcU1Ma0UhssvDbP3ksxoMyeaRDG', '2026-08-20 13:47:18', '1', '2026-08-20 17:07:18'),
('8', '4', '$2y$10$t1xFxU/7VaTr2GWKXF9.Ye0hQ/CJMbIaXtHcUhnQzd92s5mIThfWu', '2026-08-20 18:24:08', '1', '2026-08-20 18:14:09');

DROP TABLE IF EXISTS `recently_viewed`;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `recently_viewed` VALUES
('1', '1', '2', '2026-08-20 20:00:27'),
('2', '2', '7', '2026-07-31 00:50:51'),
('3', '1', '1', '2026-08-20 20:00:18'),
('6', '1', '14', '2026-08-20 22:47:16');

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `book_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `review_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_review_per_user_per_book` (`book_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_reviews_rating` (`rating`),
  CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating_range` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `users`;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `users` VALUES
('1', 'user1', 'user1@gmail.com', NULL, NULL, '$2y$10$o4yCSbCKmMLRAx/rczciVezFRH7Vu1tXjC7w2riJSwCpb0YgaGHKS', 'user', '2026-07-31 00:42:31'),
('2', 'admin1', 'admin1@gmail.com', NULL, NULL, '$2y$10$byvyMkQj8QmE/.HaQRUGqeYPke7dtkCgx/d7Y6xLqF3K3kWLHTC6e', 'admin', '2026-07-31 00:47:59'),
('3', 'Smit Parmar', 'smartysmitu23@gmail.com', NULL, NULL, '$2y$10$1igndO8MDqjGJBmbJ2MExeblcZ61nV58f9uc/ZEJnqsUwL/ZV6JCS', 'user', '2026-08-20 16:01:40'),
('4', 'viraj', 'virajparmar487@gmail.com', NULL, NULL, '$2y$10$.wOjdmHX9sbaejnYf2a66OdfzNaXhdXMjyr5gtHcrXGTliYS5hCE.', 'user', '2026-08-20 16:05:39');

DROP TABLE IF EXISTS `wishlist`;
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SET FOREIGN_KEY_CHECKS=1;
