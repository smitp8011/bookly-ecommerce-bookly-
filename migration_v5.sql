-- =====================================================
-- Bookly — Migration v5
-- Run this in phpMyAdmin (or `mysql -u root -p bookly_db < migration_v5.sql`)
-- Allows multiple reviews per user on the same book
-- =====================================================
USE `bookly_db`;

-- Step 1: Add standard index on book_id so the foreign key fk_reviews_book remains satisfied
ALTER TABLE `reviews` ADD INDEX `idx_reviews_book_id` (`book_id`);

-- Step 2: Drop the unique constraint that previously limited 1 review per user per book
ALTER TABLE `reviews` DROP INDEX `one_review_per_user_per_book`;

-- Step 3: Add composite index for efficient multi-review queries
ALTER TABLE `reviews` ADD INDEX `idx_reviews_book_user` (`book_id`, `user_id`);
