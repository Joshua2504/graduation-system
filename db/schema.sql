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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `registration_open`, `email_verification_required`, `min_team_size`, `max_team_size`, `student_project_creation`, `show_reviewer_name`, `leader_transfer`)
VALUES (1, 1, 1, 2, 7, 1, 0, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Users (with profile fields) ───
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `student_code` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('student','doctor') NOT NULL DEFAULT 'student',
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

-- ─── Seed: Doctor account (password: doctor123) ───
INSERT INTO `users` (`name`, `email`, `password`, `role`, `email_verified`) VALUES
('Doctor', 'doctor@treudler.net', '$2y$10$1hH9/Y0Noq//YW5rA9Xwbu5K9yPtX8VlbYXKqiwlZa77.LxsmGHHy', 'doctor', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Seed: Demo student accounts (password: student123) ───
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('Student 1', 'student1@treudler.net', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '001', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('Student 2', 'student2@treudler.net', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '002', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('Student 3', 'student3@treudler.net', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '003', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('Student 4', 'student4@treudler.net', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '004', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('Student 5', 'student5@treudler.net', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '005', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Seed: Demo projects ───
INSERT INTO `projects` (`title`, `type`, `description`, `join_code`, `status`, `group_number`, `submission_date`, `reviewed_by`)
SELECT 'Library Management System', 'Web Application',
       'A comprehensive library management system that allows registering books and members, handling borrowing and return operations, and generating periodic reports. The system includes a user-friendly interface for patrons and an advanced admin panel for librarians.',
       'DEMO0001', 'accepted', 1, NOW(),
       (SELECT `id` FROM `users` WHERE `email` = 'doctor@treudler.net' LIMIT 1)
FROM dual WHERE NOT EXISTS (SELECT 1 FROM `projects` WHERE `join_code` = 'DEMO0001');

INSERT INTO `projects` (`title`, `type`, `description`, `join_code`, `status`, `submission_date`)
SELECT 'Fitness Tracking App', 'Mobile Application',
       'A smartphone application that helps users track their physical activity, log workouts, count calories, and monitor progress toward their health goals.',
       'DEMO0002', 'under_review', NOW()
FROM dual WHERE NOT EXISTS (SELECT 1 FROM `projects` WHERE `join_code` = 'DEMO0002');

-- ─── Seed: Demo project members ───
-- Project 1 (Library System): student1 = leader, student2 & student3 = members
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`)
SELECT p.id, u.id, 'leader' FROM `projects` p, `users` u WHERE p.join_code = 'DEMO0001' AND u.email = 'student1@treudler.net';
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`)
SELECT p.id, u.id, 'member' FROM `projects` p, `users` u WHERE p.join_code = 'DEMO0001' AND u.email = 'student2@treudler.net';
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`)
SELECT p.id, u.id, 'member' FROM `projects` p, `users` u WHERE p.join_code = 'DEMO0001' AND u.email = 'student3@treudler.net';

-- Project 2 (Fitness App): student4 = leader, student5 = member
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`)
SELECT p.id, u.id, 'leader' FROM `projects` p, `users` u WHERE p.join_code = 'DEMO0002' AND u.email = 'student4@treudler.net';
INSERT IGNORE INTO `project_members` (`project_id`, `user_id`, `role`)
SELECT p.id, u.id, 'member' FROM `projects` p, `users` u WHERE p.join_code = 'DEMO0002' AND u.email = 'student5@treudler.net';

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
