-- Multi-tenant router database
-- Run this ONCE against the MySQL server (no database selected).
-- It creates the teachbetter_router database and tables.

CREATE DATABASE IF NOT EXISTS `teachbetter_router` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `teachbetter_router`;

CREATE TABLE IF NOT EXISTS `schools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subdomain` varchar(100) NOT NULL,
  `site_name` varchar(200) NOT NULL DEFAULT 'Ziada LMS',
  `admin_email` varchar(200) DEFAULT NULL,
  `admin_password` varchar(255) DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'Africa/Nairobi',
  `db_host` varchar(200) DEFAULT NULL,
  `db_port` int(11) DEFAULT 3306,
  `db_name` varchar(100) NOT NULL,
  `db_user` varchar(100) NOT NULL,
  `db_pass` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  UNIQUE KEY `db_name` (`db_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `super_admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
