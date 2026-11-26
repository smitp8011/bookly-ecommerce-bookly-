-- =====================================================
-- Bookly — Migration v5
-- Run this in phpMyAdmin (or `mysql -u root -p bookly_db < migration_v5.sql`)
-- Allows multiple reviews per user on the same book
-- =====================================================
USE `bookly_db`;

-- Drop unique constraint that previously limited 1 review per user per book
ALTER TABLE `reviews` DROP INDEX `one_review_per_user_per_book`;

-- Add standard non-unique index for fast retrieval
ALTER TABLE `reviews` ADD INDEX `idx_reviews_book_user` (`book_id`, `user_id`);
