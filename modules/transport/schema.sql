DROP TABLE IF EXISTS `transport_attendance`;
DROP TABLE IF EXISTS `transport_route_students`;
DROP TABLE IF EXISTS `transport_route_stops`;
DROP TABLE IF EXISTS `transport_routes`;
DROP TABLE IF EXISTS `transport_vehicles`;
DROP TABLE IF EXISTS `transport_drivers`;

CREATE TABLE `transport_vehicles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `vehicle_number` varchar(50) NOT NULL,
    `vehicle_type` enum('bus','van','car') DEFAULT 'bus',
    `capacity` int(11) NOT NULL DEFAULT 30,
    `model` varchar(255) DEFAULT NULL,
    `year` int(11) DEFAULT NULL,
    `fuel_type` enum('diesel','petrol','electric','cng') DEFAULT 'diesel',
    `insurance_expiry` date DEFAULT NULL,
    `last_maintenance` date DEFAULT NULL,
    `driver_id` int(11) DEFAULT NULL,
    `status` enum('active','maintenance','inactive') DEFAULT 'active',
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_drivers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `license_number` varchar(100) DEFAULT NULL,
    `license_expiry` date DEFAULT NULL,
    `date_of_birth` date DEFAULT NULL,
    `address` text DEFAULT NULL,
    `emergency_contact_name` varchar(255) DEFAULT NULL,
    `emergency_contact_phone` varchar(20) DEFAULT NULL,
    `photo` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `notes` text DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_routes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `vehicle_id` int(11) DEFAULT NULL,
    `driver_id` int(11) DEFAULT NULL,
    `start_point` varchar(255) DEFAULT NULL,
    `end_point` varchar(255) DEFAULT NULL,
    `departure_time` time DEFAULT NULL,
    `arrival_time` time DEFAULT NULL,
    `fee_amount` decimal(10,2) DEFAULT 0.00,
    `description` text DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `vehicle_id` (`vehicle_id`),
    KEY `driver_id` (`driver_id`),
    CONSTRAINT `fk_route_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_route_driver` FOREIGN KEY (`driver_id`) REFERENCES `transport_drivers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_route_stops` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `route_id` int(11) NOT NULL,
    `name` varchar(255) NOT NULL,
    `landmark` varchar(255) DEFAULT NULL,
    `latitude` decimal(10,8) DEFAULT NULL,
    `longitude` decimal(11,8) DEFAULT NULL,
    `stop_order` int(11) DEFAULT 0,
    `pickup_time` time DEFAULT NULL,
    `drop_time` time DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `route_id` (`route_id`),
    CONSTRAINT `fk_stop_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_route_students` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `route_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `stop_id` int(11) DEFAULT NULL,
    `fee_amount` decimal(10,2) DEFAULT 0.00,
    `session` varchar(20) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `route_id` (`route_id`),
    KEY `student_id` (`student_id`),
    KEY `stop_id` (`stop_id`),
    CONSTRAINT `fk_rs_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rs_stop` FOREIGN KEY (`stop_id`) REFERENCES `transport_route_stops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_attendance` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `route_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `date` date NOT NULL,
    `status` enum('present','absent','late') DEFAULT 'present',
    `trip_type` enum('pickup','drop') DEFAULT 'pickup',
    `remarks` text DEFAULT NULL,
    `marked_by` int(11) DEFAULT NULL,
    `created_at` datetime DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `route_id` (`route_id`),
    KEY `student_id` (`student_id`),
    KEY `marked_by` (`marked_by`),
    CONSTRAINT `fk_ta_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ta_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ta_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
