-- =====================================================
-- Bookly — Migration v2
-- Run this once in phpMyAdmin (or `mysql -u root -p bookly_db < migration_v2.sql`)
-- Adds: coupons, order discount tracking, recently-viewed table.
-- Safe to run on your existing database — it does not touch existing rows.
-- =====================================================
USE `bookly_db`;

-- --------------------------------------------------------
-- coupons
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL UNIQUE,
  `discount_type` ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_value` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `max_uses` INT UNSIGNED DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATE DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `coupons` (`code`,`discount_type`,`discount_value`,`min_order_value`,`max_uses`,`expires_at`) VALUES
('WELCOME10','percent',10.00,300.00,NULL,NULL),
('FLAT50','flat',50.00,400.00,200,NULL);

-- --------------------------------------------------------
-- orders: track which coupon (if any) was applied
-- (No "IF NOT EXISTS" here — older MySQL/MariaDB, including most
--  XAMPP installs, doesn't support that clause on ADD COLUMN. If you
--  ever re-run this file after it already succeeded once, just skip
--  this ALTER TABLE block — running it twice will error with
--  "Duplicate column name", which is harmless and expected.)
-- --------------------------------------------------------
ALTER TABLE `orders`
  ADD COLUMN `coupon_code` VARCHAR(40) DEFAULT NULL AFTER `payment_method`,
  ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER `coupon_code`;

-- --------------------------------------------------------
-- recently_viewed — powers the "Recently viewed" strip
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recently_viewed` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `one_row_per_user_per_book` (`user_id`,`book_id`),
  CONSTRAINT `fk_recently_viewed_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recently_viewed_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
