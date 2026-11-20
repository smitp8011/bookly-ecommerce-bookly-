-- =====================================================
-- Bookly — Migration v4
-- Run this in phpMyAdmin (or `mysql -u root -p bookly_db < migration_v4.sql`)
-- Adds: phone and address fields to users table for profile and saved delivery details.
-- =====================================================
USE `bookly_db`;

ALTER TABLE `users`
  ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL AFTER `email`,
  ADD COLUMN `address` VARCHAR(500) DEFAULT NULL AFTER `phone`;
