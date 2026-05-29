CREATE TABLE IF NOT EXISTS `chat_groups` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `type` enum('general','class','club') NOT NULL DEFAULT 'general',
    `class_id` int(11) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `avatar` varchar(255) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `is_reply_allowed` tinyint(1) DEFAULT 1,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `type` (`type`),
    KEY `class_id` (`class_id`),
    CONSTRAINT `fk_cg_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cg_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_group_members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `group_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `role` enum('member','admin') DEFAULT 'member',
    `joined_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `group_user` (`group_id`, `user_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_cgm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cgm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `sender_id` int(11) NOT NULL,
    `group_id` int(11) DEFAULT NULL,
    `receiver_id` int(11) DEFAULT NULL,
    `message` text NOT NULL,
    `attachment` varchar(255) DEFAULT NULL,
    `is_read` tinyint(1) DEFAULT 0,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `group_id` (`group_id`),
    KEY `receiver_id` (`receiver_id`),
    KEY `sender_id` (`sender_id`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `fk_cm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cm_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-create General group
INSERT IGNORE INTO `chat_groups` (`id`, `name`, `type`, `created_by`, `is_reply_allowed`, `description`) VALUES
(1, 'General Announcements', 'general', 1, 0, 'School-wide announcements from administration. Replies are disabled.');

-- Auto-create class-based groups
INSERT IGNORE INTO `chat_groups` (`name`, `type`, `class_id`, `created_by`, `is_reply_allowed`, `description`)
SELECT CONCAT(c.name, ' Chat'), 'class', c.id, 1, 1, CONCAT('Discussion group for ', c.name)
FROM classes c WHERE c.is_active = 1;
