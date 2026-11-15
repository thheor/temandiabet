
CREATE DATABASE IF NOT EXISTS `temandiabet`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `temandiabet`;

-- 1. Users table
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `diabetes_type` ENUM('type_1', 'type_2', 'prediabetes', 'caregiver') NOT NULL DEFAULT 'type_2',
  `target_glucose_min` INT UNSIGNED NOT NULL DEFAULT 80,
  `target_glucose_max` INT UNSIGNED NOT NULL DEFAULT 130,
  `avatar_path` VARCHAR(255) NULL DEFAULT NULL,
  `remember_token` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Blood glucose records table
CREATE TABLE `blood_glucose_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `glucose_mg_dl` INT UNSIGNED NOT NULL,
  `measurement_timing` ENUM('fasting', 'random', 'post_meal') NOT NULL,
  `status_category` ENUM('hypoglycemia', 'normal', 'elevated', 'high') NOT NULL,
  `notes` VARCHAR(255) NULL DEFAULT NULL,
  `measured_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_glucose_user_measured` (`user_id`, `measured_at`),
  CONSTRAINT `fk_glucose_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Food logs table
CREATE TABLE `food_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `food_name` VARCHAR(150) NOT NULL,
  `meal_type` ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
  `estimated_calories` INT UNSIGNED NOT NULL DEFAULT 0,
  `carbohydrates_grams` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `photo_path` VARCHAR(255) NULL DEFAULT NULL,
  `eaten_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_food_user_date` (`user_id`, `eaten_date`),
  CONSTRAINT `fk_food_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Medication schedules table
CREATE TABLE `medication_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `medication_name` VARCHAR(120) NOT NULL,
  `dosage` VARCHAR(60) NOT NULL,
  `scheduled_time` TIME NOT NULL,
  `is_taken` TINYINT(1) NOT NULL DEFAULT 0,
  `scheduled_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_med_user_date` (`user_id`, `scheduled_date`, `scheduled_time`),
  CONSTRAINT `fk_medication_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Inner circles table (patient <-> caregiver links)
CREATE TABLE `inner_circles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` INT UNSIGNED NOT NULL,
  `caregiver_id` INT UNSIGNED NULL DEFAULT NULL,
  `relationship` VARCHAR(50) NOT NULL,
  `invite_code` VARCHAR(12) NOT NULL,
  `status` ENUM('pending', 'accepted') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inner_invite_code` (`invite_code`),
  UNIQUE KEY `uk_patient_caregiver` (`patient_id`, `caregiver_id`),
  KEY `idx_inner_caregiver` (`caregiver_id`),
  CONSTRAINT `fk_inner_patient`
    FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_inner_caregiver`
    FOREIGN KEY (`caregiver_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
