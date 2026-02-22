-- ============================================
-- Graduation Project Management System
-- Database Schema
-- ============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- -------------------------------------------
-- Settings table (single row)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT PRIMARY KEY DEFAULT 1,
    `registration_open` TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `registration_open`) VALUES (1, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- -------------------------------------------
-- Users table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `student_code` VARCHAR(50) DEFAULT NULL UNIQUE,
    `role` ENUM('student', 'doctor') NOT NULL DEFAULT 'student',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Projects table
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(500) NOT NULL,
    `type` VARCHAR(255) NOT NULL DEFAULT '',
    `submission_date` DATETIME DEFAULT NULL,
    `status` ENUM('draft', 'under_review', 'accepted', 'rejected') NOT NULL DEFAULT 'draft',
    `group_number` INT DEFAULT NULL,
    `doctor_note` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Students table (7 per project)
-- -------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `student_index` TINYINT NOT NULL DEFAULT 0,
    `name` VARCHAR(255) NOT NULL,
    `student_code` VARCHAR(50) NOT NULL UNIQUE,
    `gender` ENUM('male', 'female') NOT NULL,
    `national_id` VARCHAR(20) NOT NULL,
    `birth_date` DATE NOT NULL,
    `governorate` VARCHAR(100) NOT NULL,
    `address` VARCHAR(500) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `year` VARCHAR(10) NOT NULL DEFAULT '4th',
    `section` VARCHAR(100) NOT NULL,
    `card_image` VARCHAR(255) DEFAULT NULL,
    `national_id_image` VARCHAR(255) DEFAULT NULL,
    `receipt_image` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------
-- Seed: Doctor account
-- Password: doctor123 (bcrypt hash)
-- -------------------------------------------
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`) VALUES
('د. أحمد محمد', 'doctor@university.edu', '$2y$12$G0I.az2W1wEaXlOvkN2xO.4CBHAAvgaMIR1BlGcrIqknnTPZk4Nua', NULL, 'doctor')
ON DUPLICATE KEY UPDATE `id` = `id`;
