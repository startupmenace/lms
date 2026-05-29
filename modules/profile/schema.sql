-- Notifications for the bell system
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `type` varchar(50) NOT NULL DEFAULT 'notice',
    `title` varchar(255) NOT NULL,
    `message` text DEFAULT NULL,
    `link` varchar(500) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `user_unread` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger notifications when a notice is published
-- (Alternative: inserted from PHP code after INSERT INTO notices)
