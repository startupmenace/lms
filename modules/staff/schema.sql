CREATE TABLE IF NOT EXISTS `staff_details` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `employee_id` varchar(50) DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `gender` enum('male','female','other') DEFAULT NULL,
    `address` text DEFAULT NULL,
    `qualification` varchar(255) DEFAULT NULL,
    `date_of_joining` date DEFAULT NULL,
    `kra_pin` varchar(20) DEFAULT NULL,
    `bank_name` varchar(100) DEFAULT NULL,
    `bank_account` varchar(50) DEFAULT NULL,
    `bank_branch` varchar(100) DEFAULT NULL,
    `sha_number` varchar(50) DEFAULT NULL,
    `nssf_number` varchar(50) DEFAULT NULL,
    `tsc_number` varchar(50) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_next_of_kin` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `relationship` varchar(100) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `is_primary` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_nok_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_beneficiaries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `relationship` varchar(100) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `percentage` decimal(5,2) DEFAULT 0.00,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_beneficiary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `status` enum('present','absent','late','half-day','leave') DEFAULT 'present',
    `check_in` time DEFAULT NULL,
    `check_out` time DEFAULT NULL,
    `remarks` text DEFAULT NULL,
    `marked_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_date` (`user_id`, `date`),
    KEY `date` (`date`),
    CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sa_marked_by` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
