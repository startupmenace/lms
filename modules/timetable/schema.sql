CREATE TABLE IF NOT EXISTS `timetable_periods` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `start_time` time NOT NULL,
    `end_time` time NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `timetable_periods` (`name`, `start_time`, `end_time`, `sort_order`) VALUES
('Period 1', '08:00', '08:40', 1),
('Period 2', '08:40', '09:20', 2),
('Period 3', '09:20', '10:00', 3),
('Break', '10:00', '10:30', 4),
('Period 4', '10:30', '11:10', 5),
('Period 5', '11:10', '11:50', 6),
('Period 6', '11:50', '12:30', 7),
('Lunch', '12:30', '13:30', 8),
('Period 7', '13:30', '14:10', 9),
('Period 8', '14:10', '14:50', 10);

CREATE TABLE IF NOT EXISTS `timetable_entries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `day_of_week` tinyint(4) NOT NULL COMMENT '0=Sun,1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat',
    `period_id` int(11) NOT NULL,
    `class_id` int(11) NOT NULL,
    `subject_id` int(11) DEFAULT NULL,
    `teacher_id` int(11) DEFAULT NULL,
    `room` varchar(100) DEFAULT NULL,
    `academic_year` varchar(20) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `period_id` (`period_id`),
    KEY `class_id` (`class_id`),
    KEY `subject_id` (`subject_id`),
    KEY `teacher_id` (`teacher_id`),
    CONSTRAINT `fk_tt_period` FOREIGN KEY (`period_id`) REFERENCES `timetable_periods` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timetable_disputes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `entry_id` int(11) NOT NULL,
    `teacher_id` int(11) NOT NULL,
    `reason` text NOT NULL,
    `status` enum('pending','resolved','dismissed') DEFAULT 'pending',
    `resolved_by` int(11) DEFAULT NULL,
    `resolution_notes` text DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    `resolved_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `entry_id` (`entry_id`),
    KEY `teacher_id` (`teacher_id`),
    CONSTRAINT `fk_td_entry` FOREIGN KEY (`entry_id`) REFERENCES `timetable_entries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_td_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
