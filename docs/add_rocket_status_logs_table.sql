-- Migration Script: Add rocket_status_logs table for audit trail
-- Created: July 8, 2025
-- Purpose: Track all rocket status changes for data integrity and accountability
-- Database: dpti_rocket_prod

USE `dpti_rocket_prod`;

-- --------------------------------------------------------
-- Table structure for table `rocket_status_logs`
-- Audit trail for rocket status changes to ensure data integrity
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `rocket_status_logs` (
    `log_id` INT(11) NOT NULL AUTO_INCREMENT,
    `rocket_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `previous_status` VARCHAR(100) NOT NULL,
    `new_status` VARCHAR(100) NOT NULL,
    `change_reason` TEXT NOT NULL COMMENT 'Reason for status change - required for audit purposes',
    `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `fk_rocket_status_logs_rocket_id` (`rocket_id`),
    KEY `fk_rocket_status_logs_user_id` (`user_id`),
    KEY `idx_rocket_status_logs_changed_at` (`changed_at`),
    KEY `idx_rocket_status_logs_rocket_date` (`rocket_id`, `changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Audit trail for rocket status changes';

-- --------------------------------------------------------
-- Add Foreign Key Constraints
-- --------------------------------------------------------

-- Foreign key for rocket_status_logs.rocket_id
ALTER TABLE `rocket_status_logs`
    ADD CONSTRAINT `fk_rocket_status_logs_rocket_id` 
    FOREIGN KEY (`rocket_id`) 
    REFERENCES `rockets` (`rocket_id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE;

-- Foreign key for rocket_status_logs.user_id
ALTER TABLE `rocket_status_logs`
    ADD CONSTRAINT `fk_rocket_status_logs_user_id` 
    FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE;

-- --------------------------------------------------------
-- Optional: Create a view for easy querying of status logs with user and rocket details
-- --------------------------------------------------------

CREATE OR REPLACE VIEW `rocket_status_audit_view` AS
SELECT 
    rsl.log_id,
    rsl.rocket_id,
    r.serial_number AS rocket_serial,
    r.project_name,
    rsl.user_id,
    u.username,
    u.full_name AS user_full_name,
    u.role AS user_role,
    rsl.previous_status,
    rsl.new_status,
    rsl.change_reason,
    rsl.changed_at
FROM rocket_status_logs rsl
INNER JOIN rockets r ON rsl.rocket_id = r.rocket_id
INNER JOIN users u ON rsl.user_id = u.user_id
ORDER BY rsl.changed_at DESC;

-- --------------------------------------------------------
-- Sample queries for testing (commented out)
-- --------------------------------------------------------

/*
-- Test insert (example)
INSERT INTO rocket_status_logs (rocket_id, user_id, previous_status, new_status, change_reason) 
VALUES (1, 1, 'In Development', 'In Production', 'All design reviews completed and approved');

-- Query all status changes for a specific rocket
SELECT * FROM rocket_status_audit_view WHERE rocket_id = 1;

-- Query recent status changes
SELECT * FROM rocket_status_audit_view WHERE changed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Query status changes by user
SELECT * FROM rocket_status_audit_view WHERE user_id = 1;
*/

-- End of migration script
