-- =====================================================
-- Bookly — Major Project Database Schema
-- =====================================================
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `bookly_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bookly_db`;

-- --------------------------------------------------------
-- users
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- categories
-- --------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- books
-- --------------------------------------------------------
CREATE TABLE `books` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `author` VARCHAR(150) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `stock` INT UNSIGNED NOT NULL DEFAULT 0,
  `description` TEXT,
  `cover_image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- reviews
-- --------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `book_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` VARCHAR(1000) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_book_user` (`book_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_reviews_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating_range` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- cart
-- --------------------------------------------------------
CREATE TABLE `cart` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- wishlist
-- --------------------------------------------------------
CREATE TABLE `wishlist` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- orders
-- --------------------------------------------------------
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `address` VARCHAR(500) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `placed_on` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- order_items
-- --------------------------------------------------------
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `price_at_purchase` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- messages (contact form)
-- --------------------------------------------------------
CREATE TABLE `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `message` VARCHAR(1000) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Seed data
-- =====================================================

INSERT INTO `categories` (`name`) VALUES
('Fiction'),('Fantasy'),('Thriller'),('Young Adult'),('Non-Fiction'),('Science & Nature');

-- NOTE: no admin account is seeded here on purpose (a real bcrypt hash
-- must be generated by PHP's password_hash() at install time, not pasted
-- into SQL). Run create_admin.php once after importing this file — it
-- creates the first admin account securely, then delete that file.

INSERT INTO `books` (`title`,`author`,`category_id`,`price`,`stock`,`description`,`cover_image`) VALUES
('Red Queen','Victoria Aveyard',2,499.00,25,'A gripping tale of a kingdom divided by blood — red and silver — and the girl who could upend it all.','red_queen.jpg'),
('Nightshade','Andrea Cremer',2,399.00,18,'A world of pack loyalty, hidden magic, and forbidden love simmering beneath the surface.','nightshade.jpg'),
('Darknet','Matthew Mather',3,349.00,12,'A near-future thriller where the internet itself becomes the battlefield.','darknet.jpg'),
('Shattered','Kevin Hearne',1,299.00,20,'An urban fantasy adventure blending old-world myth with modern chaos.','shattered.jpg'),
('The Girl of Ink & Stars','Kiran Millwood Hargrave',4,349.00,15,'A map-maker''s daughter sets out on a journey that will redraw the world she knows.','the_girl_of_ink_and_stars.jpg'),
('Freefall','Jessica Barry',3,379.00,10,'A woman presumed dead after a plane crash — and the mother who refuses to believe it.','freefall.jpg'),
('Boring Girls: A Novel','Sara Taylor',1,329.00,14,'A sharp, unflinching coming-of-age story about ambition, friendship, and rock and roll.','boring_girls_a_novel.jpg'),
('Clever Lands','Lucy Crehan',5,449.00,9,'A teacher''s journey through the world''s top-performing education systems.','clever_lands.jpg'),
('Economic Facts and Fallacies','Thomas Sowell',5,499.00,16,'A clear-eyed look at popular misconceptions in economics and public policy.','economic.jpg'),
('History of Modern Architecture','Kenneth Frampton',5,599.00,8,'A definitive survey of architectural movements shaping the modern built world.','history_of_modern_architecture.jpg'),
('Holy Ghosts','Gary Jansen',5,299.00,11,'A memoir of faith, doubt, and the search for meaning in the everyday.','holy_ghosts.jpg'),
('Radical Gardening','George McKay',6,379.00,13,'How gardens have been sites of protest, politics, and quiet rebellion.','radical_gardening.jpg'),
('Be Well, Bee','Rosie Sanders',6,249.00,22,'A gentle illustrated guide to the life and importance of bees.','be_well_bee.jpg'),
('The Happy Lemon','Cara Chen',1,279.00,17,'A whimsical, bittersweet story of family, memory, and citrus groves.','the_happy_lemon.jpg'),
('The World: A Traveler''s Guide','Simon Winchester',5,549.00,7,'Essays on geography, culture, and the pull of the horizon.','the_world.jpg'),
('Bash & Lucy','Rita Rosen',4,229.00,19,'A heartwarming adventure of a dog, a girl, and the summer that changes everything.','bash_and_lucy-2.jpg');

COMMIT;
