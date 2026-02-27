-- ─── Settings ───
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT NOT NULL DEFAULT 1,
  `registration_open` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verification_required` TINYINT(1) NOT NULL DEFAULT 1,
  `min_team_size` TINYINT NOT NULL DEFAULT 2,
  `max_team_size` TINYINT NOT NULL DEFAULT 7,
  `student_project_creation` TINYINT(1) NOT NULL DEFAULT 1,
  `show_reviewer_name` TINYINT(1) NOT NULL DEFAULT 0,
  `leader_transfer` TINYINT(1) NOT NULL DEFAULT 1,
  `enabled_languages` VARCHAR(50) NOT NULL DEFAULT 'ar',
  `login_methods` VARCHAR(20) NOT NULL DEFAULT 'both',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `registration_open`, `email_verification_required`, `min_team_size`, `max_team_size`, `student_project_creation`, `show_reviewer_name`, `leader_transfer`, `enabled_languages`, `login_methods`)
VALUES (1, 1, 1, 2, 7, 1, 0, 1, 'ar', 'both')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- Migration: add login_methods column if missing
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'login_methods');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `settings` ADD COLUMN `login_methods` VARCHAR(20) NOT NULL DEFAULT \'both\' AFTER `enabled_languages`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ─── Users (with profile fields) ───
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `student_code` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('student','doctor','admin') NOT NULL DEFAULT 'student',
  `gender` ENUM('male','female') DEFAULT NULL,
  `national_id` VARCHAR(20) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `governorate` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(500) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `year` VARCHAR(10) NOT NULL DEFAULT '4th',
  `section` VARCHAR(100) DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `card_image` VARCHAR(255) DEFAULT NULL,
  `national_id_image` VARCHAR(255) DEFAULT NULL,
  `receipt_image` VARCHAR(255) DEFAULT NULL,
  `profile_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `account_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `verification_token` VARCHAR(64) DEFAULT NULL,
  `token_expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_code` (`student_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Projects ───
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT DEFAULT NULL,
  `join_code` VARCHAR(8) NOT NULL,
  `submission_date` DATETIME DEFAULT NULL,
  `status` ENUM('draft','under_review','accepted','rejected') NOT NULL DEFAULT 'draft',
  `group_number` INT DEFAULT NULL,
  `doctor_note` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `allow_resubmit` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `join_code` (`join_code`),
  CONSTRAINT `fk_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Project Members ───
CREATE TABLE IF NOT EXISTS `project_members` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` ENUM('leader','member') NOT NULL DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_user` (`project_id`, `user_id`),
  CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Invitations ───
CREATE TABLE IF NOT EXISTS `invitations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `invited_by` INT NOT NULL,
  `invited_user_id` INT DEFAULT NULL,
  `token` VARCHAR(64) NOT NULL,
  `status` ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  CONSTRAINT `fk_inv_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_by` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Project Reviews (audit log) ───
CREATE TABLE IF NOT EXISTS `project_reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `reviewer_id` INT DEFAULT NULL,
  `action` ENUM('accepted','rejected') NOT NULL,
  `note` TEXT DEFAULT NULL,
  `allow_resubmit` TINYINT(1) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_project` (`project_id`),
  CONSTRAINT `fk_pr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Migrations ───

-- Add description column to projects (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'description');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `projects` ADD COLUMN `description` TEXT DEFAULT NULL AFTER `type`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add student_project_creation column to settings (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'student_project_creation');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `settings` ADD COLUMN `student_project_creation` TINYINT(1) NOT NULL DEFAULT 1 AFTER `max_team_size`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add profile_picture column to users (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL AFTER `section`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add reviewed_by column to projects (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'reviewed_by');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `projects` ADD COLUMN `reviewed_by` INT DEFAULT NULL AFTER `doctor_note`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add show_reviewer_name column to settings (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'show_reviewer_name');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `settings` ADD COLUMN `show_reviewer_name` TINYINT(1) NOT NULL DEFAULT 0 AFTER `student_project_creation`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add leader_transfer column to settings (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'settings' AND COLUMN_NAME = 'leader_transfer');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `settings` ADD COLUMN `leader_transfer` TINYINT(1) NOT NULL DEFAULT 1 AFTER `show_reviewer_name`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add allow_resubmit column to projects (safe for existing databases)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'allow_resubmit');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE `projects` ADD COLUMN `allow_resubmit` TINYINT(1) NOT NULL DEFAULT 1 AFTER `reviewed_by`', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Create project_reviews table (safe for existing databases)
CREATE TABLE IF NOT EXISTS `project_reviews` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `reviewer_id` INT DEFAULT NULL,
  `action` ENUM('accepted','rejected') NOT NULL,
  `note` TEXT DEFAULT NULL,
  `allow_resubmit` TINYINT(1) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_project` (`project_id`),
  CONSTRAINT `fk_pr_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pr_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Migrations ───
ALTER TABLE `settings` ADD COLUMN IF NOT EXISTS `enabled_languages` VARCHAR(50) NOT NULL DEFAULT 'ar' AFTER `leader_transfer`;

-- Add 'admin' to users role ENUM (safe for existing databases)
SET @has_admin = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' AND COLUMN_TYPE LIKE '%admin%');
SET @sql = IF(@has_admin = 0, "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('student','doctor','admin') NOT NULL DEFAULT 'student'", 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Upgrade first doctor to admin if no admin exists yet (for existing installations)
SET @admin_count = (SELECT COUNT(*) FROM `users` WHERE `role` = 'admin');
SET @first_doctor = (SELECT MIN(id) FROM `users` WHERE `role` = 'doctor');
SET @sql = IF(@admin_count = 0 AND @first_doctor IS NOT NULL, CONCAT('UPDATE `users` SET `role` = ''admin'' WHERE id = ', @first_doctor), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
