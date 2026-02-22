-- ─── Settings ───
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT NOT NULL DEFAULT 1,
  `registration_open` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verification_required` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`id`, `registration_open`, `email_verification_required`) VALUES (1, 1, 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Users ───
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `student_code` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('student','doctor') NOT NULL DEFAULT 'student',
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
  `user_id` INT NOT NULL,
  `title` VARCHAR(500) NOT NULL,
  `type` VARCHAR(255) NOT NULL DEFAULT '',
  `submission_date` DATETIME DEFAULT NULL,
  `status` ENUM('draft','under_review','accepted','rejected') NOT NULL DEFAULT 'draft',
  `group_number` INT DEFAULT NULL,
  `doctor_note` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_project_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Students ───
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `student_index` TINYINT NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `student_code` VARCHAR(50) DEFAULT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_student` (`project_id`, `student_index`),
  UNIQUE KEY `student_code` (`student_code`),
  CONSTRAINT `fk_student_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seed: Doctor account (password: doctor123) ───
INSERT INTO `users` (`name`, `email`, `password`, `role`, `email_verified`) VALUES
('دكتور', 'doctor@university.edu', '$2y$10$pN5x5nZ5vW1YwKzP5k5xU.L7TJ4rN5x5nZ5vW1YwKzP5k5xU.', 'doctor', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ─── Seed: Test student account (password: student123) ───
INSERT INTO `users` (`name`, `email`, `password`, `student_code`, `role`, `email_verified`) VALUES
('طالب تجريبي', 'student@university.edu', '$2y$12$G0I.az2W1wEaXlOvkN2xO.4CBHAAvgaMIR1BlGcrIqknnTPZk4Nua', '001', 'student', 1)
ON DUPLICATE KEY UPDATE `id` = `id`;
