-- ─── Settings ───
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT NOT NULL DEFAULT 1,
  `registration_open` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verification_required` TINYINT(1) NOT NULL DEFAULT 1,
  `min_team_size` TINYINT NOT NULL DEFAULT 2,
  `max_team_size` TINYINT NOT NULL DEFAULT 7,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `registration_open`, `email_verification_required`, `min_team_size`, `max_team_size`)
VALUES (1, 1, 1, 2, 7)
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
  `join_code` VARCHAR(8) NOT NULL,
  `submission_date` DATETIME DEFAULT NULL,
  `status` ENUM('draft','under_review','accepted','rejected') NOT NULL DEFAULT 'draft',
  `group_number` INT DEFAULT NULL,
  `doctor_note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `join_code` (`join_code`)
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

-- ─── Seed: Doctor account (password: doctor123) ───
INSERT INTO `users` (`name`, `email`, `password`, `role`, `email_verified`) VALUES
('دكتور', 'doctor@university.edu', '$2y$10$1hH9/Y0Noq//YW5rA9Xwbu5K9yPtX8VlbYXKqiwlZa77.LxsmGHHy', 'doctor', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Seed: Test student account (password: student123) ───
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('طالب تجريبي', 'student@university.edu', '$2y$10$yqqDmhPnvBeLXswbIx3dfepMRnibuYMv/UuUb8hp8T3NAWn0yNepO', '001', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
