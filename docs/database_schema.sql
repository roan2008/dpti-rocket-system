-- DPTI Rocket System Database Schema
-- Created: June 30, 2025
-- Database: dpti_rocket_prod

-- Set SQL mode and charset
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `dpti_rocket_prod` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dpti_rocket_prod`;

-- --------------------------------------------------------

-- Table structure for table `users`
CREATE TABLE `users` (
    `user_id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'engineer', 'staff') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `rockets`
CREATE TABLE `rockets` (
    `rocket_id` INT(11) NOT NULL AUTO_INCREMENT,
    `serial_number` VARCHAR(50) NOT NULL UNIQUE,
    `project_name` VARCHAR(100) NOT NULL,
    `current_status` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`rocket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `production_steps`
CREATE TABLE `production_steps` (
    `step_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rocket_id` INT(11) NOT NULL,
    `step_name` VARCHAR(100) NOT NULL,
    `data_json` JSON,
    `staff_id` INT(11) NOT NULL,
    `step_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`step_id`),
    KEY `fk_production_steps_rocket_id` (`rocket_id`),
    KEY `fk_production_steps_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Table structure for table `approvals`
CREATE TABLE `approvals` (
    `approval_id` INT(11) NOT NULL AUTO_INCREMENT,
    `step_id` INT(11) NOT NULL UNIQUE,
    `engineer_id` INT(11) NOT NULL,
    `status` ENUM('approved', 'rejected') NOT NULL,
    `comments` TEXT,
    `approval_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`approval_id`),
    KEY `fk_approvals_engineer_id` (`engineer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

-- Add Foreign Key Constraints

-- Foreign key for production_steps.rocket_id
ALTER TABLE `production_steps`
    ADD CONSTRAINT `fk_production_steps_rocket_id` 
    FOREIGN KEY (`rocket_id`) 
    REFERENCES `rockets` (`rocket_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Foreign key for production_steps.staff_id
ALTER TABLE `production_steps`
    ADD CONSTRAINT `fk_production_steps_staff_id` 
    FOREIGN KEY (`staff_id`) 
    REFERENCES `users` (`user_id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE;

-- Foreign key for approvals.step_id
ALTER TABLE `approvals`
    ADD CONSTRAINT `fk_approvals_step_id` 
    FOREIGN KEY (`step_id`) 
    REFERENCES `production_steps` (`step_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Foreign key for approvals.engineer_id
ALTER TABLE `approvals`
    ADD CONSTRAINT `fk_approvals_engineer_id` 
    FOREIGN KEY (`engineer_id`) 
    REFERENCES `users` (`user_id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE;

-- --------------------------------------------------------

-- Create indexes for better performance
CREATE INDEX `idx_users_role` ON `users` (`role`);
CREATE INDEX `idx_rockets_status` ON `rockets` (`current_status`);
CREATE INDEX `idx_production_steps_timestamp` ON `production_steps` (`step_timestamp`);
CREATE INDEX `idx_approvals_status` ON `approvals` (`status`);
CREATE INDEX `idx_approvals_timestamp` ON `approvals` (`approval_timestamp`);

-- --------------------------------------------------------

-- Sample data insertion (optional - uncomment if needed)
/*
-- Insert sample admin user
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample rocket
INSERT INTO `rockets` (`serial_number`, `project_name`, `current_status`) VALUES
('RKT-001', 'Apollo Mission Test', 'In Development');
*/

COMMIT;

-- End of database schema
