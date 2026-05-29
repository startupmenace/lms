-- Leave Management
CREATE TABLE IF NOT EXISTS `leave_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `max_days_per_year` int(11) NOT NULL DEFAULT 30,
    `color` varchar(7) DEFAULT '#0d9488',
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `leave_types` (`name`, `description`, `max_days_per_year`, `color`) VALUES
('Sick Leave', 'Medical or health-related absence', 15, '#dc2626'),
('Annual Leave', 'Yearly vacation or personal days', 30, '#2563eb'),
('Casual Leave', 'Short notice personal leave', 12, '#d97706'),
('Maternity Leave', 'Maternity-related absence', 90, '#7c3aed'),
('Paternity Leave', 'Paternity-related absence', 14, '#0891b2'),
('Study Leave', 'Professional development or exams', 20, '#059669');

CREATE TABLE IF NOT EXISTS `leave_applications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `leave_type_id` int(11) NOT NULL,
    `reason` text NOT NULL,
    `from_date` date NOT NULL,
    `to_date` date NOT NULL,
    `total_days` int(11) NOT NULL,
    `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
    `reviewed_by` int(11) DEFAULT NULL,
    `review_notes` text DEFAULT NULL,
    `reviewed_at` datetime DEFAULT NULL,
    `applied_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `leave_type_id` (`leave_type_id`),
    KEY `reviewed_by` (`reviewed_by`),
    CONSTRAINT `fk_leave_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_leave_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diary Module
CREATE TABLE IF NOT EXISTS `diary_entries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `content` text NOT NULL,
    `entry_date` date NOT NULL,
    `category` varchar(50) DEFAULT 'general',
    `is_private` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT current_timestamp(),
    `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `entry_date` (`entry_date`),
    CONSTRAINT `fk_diary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
