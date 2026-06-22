-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2026 at 09:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `teachbetter_lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','leave') DEFAULT 'present',
  `remark` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_groups`
--

CREATE TABLE `chat_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` enum('general','class','club') NOT NULL DEFAULT 'general',
  `class_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `is_reply_allowed` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_groups`
--

INSERT INTO `chat_groups` (`id`, `name`, `type`, `class_id`, `description`, `avatar`, `created_by`, `is_reply_allowed`, `is_active`, `created_at`) VALUES
(1, 'General Announcements', 'general', NULL, 'School-wide announcements from administration. Replies are disabled.', NULL, 1, 0, 1, '2026-05-28 19:48:41'),
(2, 'Pre Primary Chat', 'class', 1, 'Discussion group for Pre Primary', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(3, 'Primary Chat', 'class', 2, 'Discussion group for Primary', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(4, 'Middle Chat', 'class', 3, 'Discussion group for Middle', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(5, 'Secondary Chat', 'class', 4, 'Discussion group for Secondary', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(6, 'Class 8 Chat', 'class', 5, 'Discussion group for Class 8', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(7, 'Class 9 Chat', 'class', 6, 'Discussion group for Class 9', NULL, 1, 1, 1, '2026-05-28 19:48:41'),
(8, 'Class 10 Chat', 'class', 7, 'Discussion group for Class 10', NULL, 1, 1, 1, '2026-05-28 19:48:41');

-- --------------------------------------------------------

--
-- Table structure for table `chat_group_members`
--

CREATE TABLE `chat_group_members` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('member','admin') DEFAULT 'member',
  `joined_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_group_members`
--

INSERT INTO `chat_group_members` (`id`, `group_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 1, 'admin', '2026-05-28 19:55:13'),
(2, 2, 1, 'admin', '2026-05-28 19:55:13'),
(3, 3, 1, 'admin', '2026-05-28 19:55:13'),
(4, 4, 1, 'admin', '2026-05-28 19:55:13'),
(5, 5, 1, 'admin', '2026-05-28 19:55:13'),
(6, 6, 1, 'admin', '2026-05-28 19:55:14'),
(7, 7, 1, 'admin', '2026-05-28 19:55:14'),
(8, 8, 1, 'admin', '2026-05-28 19:55:14'),
(81, 2, 2, 'member', '2026-05-28 19:58:15'),
(82, 3, 2, 'member', '2026-05-28 19:58:15'),
(83, 4, 2, 'member', '2026-05-28 19:58:16'),
(84, 5, 2, 'member', '2026-05-28 19:58:16'),
(85, 6, 2, 'member', '2026-05-28 19:58:16'),
(86, 7, 2, 'member', '2026-05-28 19:58:16'),
(87, 8, 2, 'member', '2026-05-28 19:58:16'),
(88, 1, 2, 'member', '2026-05-28 19:58:16'),
(89, 2, 3, 'member', '2026-05-28 19:59:52'),
(90, 1, 3, 'member', '2026-05-28 19:59:52');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `group_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `group_id`, `receiver_id`, `message`, `attachment`, `is_read`, `created_at`) VALUES
(1, 1, 1, NULL, 'Announcement:Dining hall will be out of bounce this evening 🚨', NULL, 1, '2026-05-28 19:56:05'),
(2, 1, 8, NULL, 'Hey We&#039;ll be having no school tomorrow', NULL, 0, '2026-05-28 20:40:59'),
(3, 1, 8, NULL, 'Hey We&#039;ll be having no school tomorrow', NULL, 0, '2026-05-28 20:41:07'),
(4, 3, 2, NULL, 'cxcccccccc', NULL, 0, '2026-05-28 21:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `section` varchar(10) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `category`, `section`, `description`, `is_active`, `created_at`) VALUES
(1, 'Pre Primary', 'Pre Primary', NULL, NULL, 1, '2026-05-23 17:49:33'),
(2, 'Primary', 'Primary', NULL, NULL, 1, '2026-05-23 17:49:33'),
(3, 'Middle', 'Middle', NULL, NULL, 1, '2026-05-23 17:49:33'),
(4, 'Secondary', 'Secondary', NULL, NULL, 1, '2026-05-23 17:49:33'),
(5, 'Class 8', 'Secondary', NULL, NULL, 1, '2026-05-23 17:49:33'),
(6, 'Class 9', 'Secondary', NULL, NULL, 1, '2026-05-23 17:49:33'),
(7, 'Class 10', 'Secondary', NULL, NULL, 1, '2026-05-23 17:49:33');

-- --------------------------------------------------------

--
-- Table structure for table `class_subjects`
--

CREATE TABLE `class_subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diary_entries`
--

CREATE TABLE `diary_entries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `entry_date` date NOT NULL,
  `category` varchar(50) DEFAULT 'general',
  `is_private` tinyint(1) DEFAULT 0,
  `shared_with` int(11) DEFAULT NULL,
  `shared_at` datetime DEFAULT NULL,
  `teacher_feedback` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diary_entries`
--

INSERT INTO `diary_entries` (`id`, `user_id`, `title`, `content`, `entry_date`, `category`, `is_private`, `shared_with`, `shared_at`, `teacher_feedback`, `created_at`, `updated_at`) VALUES
(1, 1, 'today', 'i was chill', '2026-05-28', 'general', 0, NULL, NULL, NULL, '2026-05-28 14:33:53', '2026-05-28 14:33:53');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `class_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `title`, `class_id`, `exam_date`, `start_time`, `end_time`, `description`, `created_by`, `is_active`, `created_at`) VALUES
(1, 'Mid term', 1, '2026-06-23', '21:25:00', '10:25:00', NULL, 2, 1, '2026-05-23 18:26:00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_subjects`
--

CREATE TABLE `exam_subjects` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `max_marks` int(11) DEFAULT 100,
  `pass_marks` int(11) DEFAULT 33
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_subjects`
--

INSERT INTO `exam_subjects` (`id`, `exam_id`, `subject_id`, `max_marks`, `pass_marks`) VALUES
(1, 1, 9, 100, 33),
(2, 1, 8, 100, 33);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `receipt_start` int(11) DEFAULT 1,
  `frequency` enum('monthly','quarterly','half_yearly','yearly') DEFAULT 'monthly',
  `due_day` int DEFAULT NULL COMMENT 'Day of month for monthly due (1-31)',
  `term_config` text DEFAULT NULL COMMENT 'JSON: per-term prefix + due_date overrides',
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`id`, `name`, `prefix`, `receipt_start`, `frequency`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'anual fee', 'INV', 1, 'monthly', 1, 1, '2026-05-23 18:01:12'),
(2, 'Annual fee', 'INV', 1, 'quarterly', 1, 1, '2026-05-23 19:19:49'),
(3, 'pesaflow', 'INV', 1, 'monthly', 1, 2, '2026-05-24 08:08:37'),
(4, 'Termly fee', 'INV001', 1001, 'monthly', 1, 2, '2026-05-24 14:25:46'),
(5, 'Annual fee', 'INV002', 1002, 'monthly', 1, 2, '2026-05-24 14:28:21'),
(6, 'Sports', 'INV 001', 1, 'monthly', 1, 1, '2026-05-28 17:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure_classes`
--

CREATE TABLE `fee_structure_classes` (
  `id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structure_classes`
--

INSERT INTO `fee_structure_classes` (`id`, `fee_structure_id`, `class_id`) VALUES
(1, 1, 7),
(2, 2, 1),
(3, 3, 1),
(4, 4, 1),
(5, 5, 1),
(6, 6, 7),
(7, 6, 5),
(8, 6, 6);

-- --------------------------------------------------------

--
-- Table structure for table `fee_types`
--

CREATE TABLE `fee_types` (
  `id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `tax_percent` decimal(5,2) DEFAULT 0.00,
  `is_optional` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_types`
--

INSERT INTO `fee_types` (`id`, `fee_structure_id`, `category`, `amount`, `tax_percent`, `is_optional`) VALUES
(1, 1, 'tuition', 400.00, 0.00, 0),
(2, 2, 'tuition', 50000.00, 0.00, 0),
(3, 3, 'tuition', 1000.00, 0.00, 0),
(4, 3, 'term1', 500.00, 0.00, 0),
(5, 3, 'term2', 400.00, 0.00, 0),
(6, 4, 'Tuition', 50000.00, 16.00, 0),
(7, 4, 'Swimming', 2000.00, 16.00, 0),
(8, 4, 'Bus', 6000.00, 16.00, 0),
(9, 4, 'Lunch', 2000.00, 16.00, 0),
(10, 5, 'Test', 30.00, 0.00, 0),
(11, 6, 'Tennis', 3000.00, 16.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `type` enum('public','school','event') DEFAULT 'public',
  `is_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `title`, `description`, `date`, `type`, `is_recurring`, `created_by`, `created_at`) VALUES
(1, 'New Year\'s Day', 'Start of the calendar year', '2026-01-01', 'public', 1, NULL, '2026-05-28 11:02:51'),
(2, 'Labour Day', 'International Workers\' Day', '2026-05-01', 'public', 1, NULL, '2026-05-28 11:02:51'),
(3, 'Madaraka Day', 'Commemorates Kenya attaining internal self-rule in 1963', '2026-06-01', 'public', 1, NULL, '2026-05-28 11:02:51'),
(4, 'Mazingira Day', 'National environment conservation day', '2026-10-10', 'public', 1, NULL, '2026-05-28 11:02:51'),
(5, 'Mashujaa Day', 'Heroes\' Day û honours all who contributed to Kenya\'s independence', '2026-10-20', 'public', 1, NULL, '2026-05-28 11:02:51'),
(6, 'Jamhuri Day', 'Kenya\'s Independence Day / Republic Day', '2026-12-12', 'public', 1, NULL, '2026-05-28 11:02:51'),
(7, 'Christmas Day', 'Christian celebration of the birth of Jesus Christ', '2026-12-25', 'public', 1, NULL, '2026-05-28 11:02:51'),
(8, 'Boxing Day', 'Day after Christmas', '2026-12-26', 'public', 1, NULL, '2026-05-28 11:02:51'),
(9, 'Idd-ul-Fitr', 'Eid al-Fitr û marks the end of Ramadan (subject to moon sighting)', '2026-03-20', 'public', 0, NULL, '2026-05-28 11:02:51'),
(10, 'Good Friday', 'Christian observance of the crucifixion of Jesus Christ', '2026-04-03', 'public', 0, NULL, '2026-05-28 11:02:51'),
(11, 'Easter Monday', 'Day after Easter Sunday', '2026-04-06', 'public', 0, NULL, '2026-05-28 11:02:51'),
(12, 'Idd-ul-Azha', 'Eid al-Adha û Feast of Sacrifice (subject to moon sighting)', '2026-05-27', 'public', 0, NULL, '2026-05-28 11:02:51'),
(13, 'Diwali', 'Hindu festival of lights', '2026-11-08', 'public', 0, NULL, '2026-05-28 11:02:51'),
(14, 'Term 1 Break', 'End of Term 1 school holiday', '2026-04-10', 'school', 0, NULL, '2026-05-28 11:02:51'),
(15, 'Term 2 Break', 'Mid-term school holiday', '2026-08-14', 'school', 0, NULL, '2026-05-28 11:02:51'),
(16, 'Term 3 Break', 'End of year school holiday', '2026-11-20', 'school', 0, NULL, '2026-05-28 11:02:51');

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `id` int(11) NOT NULL,
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
  `applied_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_applications`
--

INSERT INTO `leave_applications` (`id`, `user_id`, `leave_type_id`, `reason`, `from_date`, `to_date`, `total_days`, `status`, `reviewed_by`, `review_notes`, `reviewed_at`, `applied_at`) VALUES
(1, 1, 4, 'ball', '2026-05-28', '2026-06-30', 34, 'approved', 1, '', '2026-05-28 18:31:32', '2026-05-28 18:31:22');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `max_days_per_year` int(11) NOT NULL DEFAULT 30,
  `color` varchar(7) DEFAULT '#0d9488',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`id`, `name`, `description`, `max_days_per_year`, `color`, `is_active`, `created_at`) VALUES
(1, 'Sick Leave', 'Medical or health-related absence', 15, '#dc2626', 1, '2026-05-28 13:29:45'),
(2, 'Annual Leave', 'Yearly vacation or personal days', 30, '#2563eb', 1, '2026-05-28 13:29:45'),
(3, 'Casual Leave', 'Short notice personal leave', 12, '#d97706', 1, '2026-05-28 13:29:45'),
(4, 'Maternity Leave', 'Maternity-related absence', 90, '#7c3aed', 1, '2026-05-28 13:29:45'),
(5, 'Paternity Leave', 'Paternity-related absence', 14, '#0891b2', 1, '2026-05-28 13:29:45'),
(6, 'Study Leave', 'Professional development or exams', 20, '#059669', 1, '2026-05-28 13:29:45'),
(7, 'Sick Leave', 'Medical or health-related absence', 15, '#dc2626', 1, '2026-05-28 14:27:59'),
(8, 'Annual Leave', 'Yearly vacation or personal days', 30, '#2563eb', 1, '2026-05-28 14:27:59'),
(9, 'Casual Leave', 'Short notice personal leave', 12, '#d97706', 1, '2026-05-28 14:27:59'),
(10, 'Maternity Leave', 'Maternity-related absence', 90, '#7c3aed', 1, '2026-05-28 14:27:59'),
(11, 'Paternity Leave', 'Paternity-related absence', 14, '#0891b2', 1, '2026-05-28 14:27:59'),
(12, 'Study Leave', 'Professional development or exams', 20, '#059669', 1, '2026-05-28 14:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `live_classes`
--

CREATE TABLE `live_classes` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `meeting_url` varchar(500) DEFAULT NULL,
  `scheduled_at` datetime NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `status` enum('scheduled','live','completed','cancelled') DEFAULT 'scheduled',
  `recording_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `live_classes`
--

INSERT INTO `live_classes` (`id`, `title`, `class_id`, `subject_id`, `teacher_id`, `meeting_url`, `scheduled_at`, `duration_minutes`, `status`, `recording_url`, `created_at`) VALUES
(1, 'going live', 7, 9, 2, 'https://meet.google.com', '2026-05-30 20:57:00', 60, 'live', NULL, '2026-05-23 17:58:13'),
(2, 'Algebra', NULL, 9, 2, '', '2026-05-23 22:39:00', 60, 'live', NULL, '2026-05-23 19:39:15'),
(3, 'Algebra', 1, 3, 2, '', '2026-05-24 12:00:00', 60, 'live', NULL, '2026-05-24 08:15:12'),
(4, 'Test', 1, 1, 2, '', '2026-05-24 17:32:00', 10, 'live', NULL, '2026-05-24 14:30:21'),
(5, 'Test with Emz', 1, 1, 2, '', '2026-05-24 17:43:00', 60, 'live', NULL, '2026-05-24 14:42:21');

-- --------------------------------------------------------

--
-- Table structure for table `live_class_attendance`
--

CREATE TABLE `live_class_attendance` (
  `id` int(11) NOT NULL,
  `live_class_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` datetime NOT NULL,
  `left_at` datetime DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `status` enum('active','completed') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `live_class_attendance`
--

INSERT INTO `live_class_attendance` (`id`, `live_class_id`, `user_id`, `joined_at`, `left_at`, `duration_seconds`, `status`) VALUES
(1, 5, 1, '2026-05-28 20:48:19', NULL, 0, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `group_type` enum('class','all') DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `group_type`, `class_id`, `subject`, `message`, `is_read`, `created_at`) VALUES
(1, 2, 1, NULL, NULL, NULL, 'heloooo', 0, '2026-05-23 17:57:00'),
(2, 3, 1, NULL, NULL, NULL, 'Thanks for reading! You can now support me on PigaHustle.', 0, '2026-05-23 18:10:36'),
(3, 3, 1, NULL, NULL, NULL, 'hey', 0, '2026-05-25 19:12:29'),
(4, 1, 1, NULL, NULL, NULL, 'helooo', 1, '2026-05-28 10:25:19');

-- --------------------------------------------------------

--
-- Table structure for table `notices`
--

CREATE TABLE `notices` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_audience` enum('all','teacher','student') DEFAULT 'all',
  `class_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notices`
--

INSERT INTO `notices` (`id`, `title`, `content`, `target_audience`, `class_id`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'wharrrr', 'b cvgx', 'all', NULL, 1, 2, '2026-05-23 17:57:26'),
(2, 'Notice 🚨🚨🚨🚨', 'This goes to all users', 'all', NULL, 1, 1, '2026-05-28 18:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'notice',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'notice', 'Notice 🚨🚨🚨🚨', 'This goes to all users', 'http://localhost:8000/modules/noticeboard/index.php', 0, '2026-05-28 21:17:02'),
(2, 2, 'notice', 'Notice 🚨🚨🚨🚨', 'This goes to all users', 'http://localhost:8000/modules/noticeboard/index.php', 0, '2026-05-28 21:17:02'),
(3, 3, 'notice', 'Notice 🚨🚨🚨🚨', 'This goes to all users', 'http://localhost:8000/modules/noticeboard/index.php', 0, '2026-05-28 21:17:02');

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_config`
--

CREATE TABLE `payment_gateway_config` (
  `id` int(11) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `config_data` text NOT NULL,
  `instructions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateway_config`
--

INSERT INTO `payment_gateway_config` (`id`, `gateway_name`, `label`, `config_data`, `instructions`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'mpesa', 'M-Pesa', '{\"paybill\":\"4954100\",\"till\":\"\",\"account_format\":\"Student Name - Invoice No\"}', 'Send payment via M-Pesa Paybill 247247. Use your name and invoice number as the account number.', 1, '2026-05-23 19:16:21', '2026-05-24 16:00:14'),
(2, 'bank_transfer', 'Bank Transfer', '{\"bank_name\":\"Equity Bank\",\"account_name\":\"Jewel House School\",\"account_number\":\"1234567890\",\"branch\":\"Nairobi\"}', 'Transfer to our Equity Bank account. Upload the payment receipt/screenshot below.', 1, '2026-05-23 19:16:21', '2026-05-24 09:02:56');

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(11) NOT NULL,
  `live_class_id` int(11) DEFAULT NULL,
  `question` varchar(500) NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`options`)),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `polls`
--

INSERT INTO `polls` (`id`, `live_class_id`, `question`, `options`, `is_active`, `created_at`) VALUES
(1, 5, 'How&#039;s your day', '[\"Great\",\"Good\",\"Bad\",\"Horrible\"]', 1, '2026-05-24 14:48:11');

-- --------------------------------------------------------

--
-- Table structure for table `poll_responses`
--

CREATE TABLE `poll_responses` (
  `id` int(11) NOT NULL,
  `poll_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `selected_option` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `poll_responses`
--

INSERT INTO `poll_responses` (`id`, `poll_id`, `user_id`, `selected_option`, `created_at`) VALUES
(1, 1, 2, 0, '2026-05-24 14:48:48');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `type` enum('mcq','subjective') NOT NULL,
  `question_text` text NOT NULL,
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `correct_answer` text DEFAULT NULL,
  `marks` int(11) DEFAULT 1,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `test_id`, `type`, `question_text`, `options`, `correct_answer`, `marks`, `difficulty`, `sort_order`) VALUES
(1, 1, 'mcq', 'what is photosynthesis', '[\"one\",\"2\",\"3\",\"4\"]', '', 1, 'medium', 0),
(2, 2, 'subjective', 'what is the start of the universe', '[]', 'it is groot', 1, 'hard', 0);

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

CREATE TABLE `staff_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','half-day','leave') DEFAULT 'present',
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_beneficiaries`
--

CREATE TABLE `staff_beneficiaries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_details`
--

CREATE TABLE `staff_details` (
  `id` int(11) NOT NULL,
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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_next_of_kin`
--

CREATE TABLE `staff_next_of_kin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `relationship` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `enrollment_id` varchar(50) NOT NULL,
  `admission_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `pincode` varchar(10) DEFAULT NULL,
  `parent_name` varchar(100) DEFAULT NULL,
  `parent_phone` varchar(20) DEFAULT NULL,
  `parent_email` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `class_id`, `enrollment_id`, `admission_date`, `date_of_birth`, `gender`, `blood_group`, `address`, `city`, `state`, `pincode`, `parent_name`, `parent_phone`, `parent_email`, `guardian_name`, `guardian_phone`, `is_active`, `created_at`) VALUES
(1, 3, 1, 'STU-2026-01001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:40'),
(2, NULL, 1, 'STU-2026-01002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:40'),
(3, NULL, 1, 'STU-2026-01003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:40'),
(4, NULL, 1, 'STU-2026-01004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:40'),
(5, NULL, 1, 'STU-2026-01005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:40'),
(6, NULL, 1, 'STU-2026-01006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:40'),
(7, NULL, 1, 'STU-2026-01007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:40'),
(8, NULL, 1, 'STU-2026-01008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:40'),
(9, NULL, 1, 'STU-2026-01009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:40'),
(10, NULL, 1, 'STU-2026-01010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:40'),
(11, NULL, 1, 'STU-2026-01011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:40'),
(12, NULL, 1, 'STU-2026-01012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:40'),
(13, NULL, 1, 'STU-2026-01013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:40'),
(14, NULL, 1, 'STU-2026-01014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:41'),
(15, NULL, 1, 'STU-2026-01015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:41'),
(16, NULL, 2, 'STU-2026-02001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:41'),
(17, NULL, 2, 'STU-2026-02002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:41'),
(18, NULL, 2, 'STU-2026-02003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:41'),
(19, NULL, 2, 'STU-2026-02004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:41'),
(20, NULL, 2, 'STU-2026-02005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:41'),
(21, NULL, 2, 'STU-2026-02006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:41'),
(22, NULL, 2, 'STU-2026-02007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:41'),
(23, NULL, 2, 'STU-2026-02008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:41'),
(24, NULL, 2, 'STU-2026-02009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:41'),
(25, NULL, 2, 'STU-2026-02010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:41'),
(26, NULL, 2, 'STU-2026-02011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:41'),
(27, NULL, 2, 'STU-2026-02012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:41'),
(28, NULL, 2, 'STU-2026-02013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:41'),
(29, NULL, 2, 'STU-2026-02014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:41'),
(30, NULL, 2, 'STU-2026-02015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:41'),
(31, NULL, 3, 'STU-2026-03001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:42'),
(32, NULL, 3, 'STU-2026-03002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:42'),
(33, NULL, 3, 'STU-2026-03003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:42'),
(34, NULL, 3, 'STU-2026-03004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:42'),
(35, NULL, 3, 'STU-2026-03005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:42'),
(36, NULL, 3, 'STU-2026-03006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:42'),
(37, NULL, 3, 'STU-2026-03007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:42'),
(38, NULL, 3, 'STU-2026-03008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:42'),
(39, NULL, 3, 'STU-2026-03009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:42'),
(40, NULL, 3, 'STU-2026-03010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:42'),
(41, NULL, 3, 'STU-2026-03011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:42'),
(42, NULL, 3, 'STU-2026-03012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:42'),
(43, NULL, 3, 'STU-2026-03013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:42'),
(44, NULL, 3, 'STU-2026-03014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:42'),
(45, NULL, 3, 'STU-2026-03015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:42'),
(46, NULL, 4, 'STU-2026-04001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:42'),
(47, NULL, 4, 'STU-2026-04002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:42'),
(48, NULL, 4, 'STU-2026-04003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:43'),
(49, NULL, 4, 'STU-2026-04004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:43'),
(50, NULL, 4, 'STU-2026-04005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:43'),
(51, NULL, 4, 'STU-2026-04006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:43'),
(52, NULL, 4, 'STU-2026-04007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:43'),
(53, NULL, 4, 'STU-2026-04008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:43'),
(54, NULL, 4, 'STU-2026-04009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:43'),
(55, NULL, 4, 'STU-2026-04010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:43'),
(56, NULL, 4, 'STU-2026-04011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:43'),
(57, NULL, 4, 'STU-2026-04012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:43'),
(58, NULL, 4, 'STU-2026-04013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:43'),
(59, NULL, 4, 'STU-2026-04014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:43'),
(60, NULL, 4, 'STU-2026-04015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:43'),
(61, NULL, 5, 'STU-2026-05001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:43'),
(62, NULL, 5, 'STU-2026-05002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:43'),
(63, NULL, 5, 'STU-2026-05003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:43'),
(64, NULL, 5, 'STU-2026-05004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:43'),
(65, NULL, 5, 'STU-2026-05005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:43'),
(66, NULL, 5, 'STU-2026-05006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:43'),
(67, NULL, 5, 'STU-2026-05007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:43'),
(68, NULL, 5, 'STU-2026-05008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:43'),
(69, NULL, 5, 'STU-2026-05009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:43'),
(70, NULL, 5, 'STU-2026-05010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:43'),
(71, NULL, 5, 'STU-2026-05011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:43'),
(72, NULL, 5, 'STU-2026-05012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:43'),
(73, NULL, 5, 'STU-2026-05013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:44'),
(74, NULL, 5, 'STU-2026-05014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:44'),
(75, NULL, 5, 'STU-2026-05015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:44'),
(76, NULL, 6, 'STU-2026-06001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:44'),
(77, NULL, 6, 'STU-2026-06002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:44'),
(78, NULL, 6, 'STU-2026-06003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:44'),
(79, NULL, 6, 'STU-2026-06004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:44'),
(80, NULL, 6, 'STU-2026-06005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:44'),
(81, NULL, 6, 'STU-2026-06006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:44'),
(82, NULL, 6, 'STU-2026-06007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:44'),
(83, NULL, 6, 'STU-2026-06008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:44'),
(84, NULL, 6, 'STU-2026-06009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:44'),
(85, NULL, 6, 'STU-2026-06010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:44'),
(86, NULL, 6, 'STU-2026-06011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:44'),
(87, NULL, 6, 'STU-2026-06012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:44'),
(88, NULL, 6, 'STU-2026-06013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:44'),
(89, NULL, 6, 'STU-2026-06014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:44'),
(90, NULL, 6, 'STU-2026-06015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:44'),
(91, NULL, 7, 'STU-2026-07001', '2025-04-01', '2016-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aarav Patel', '9876500100', 'aarav.patel@email.com', 'Mr. Guardian', '9123400100', 1, '2026-05-23 17:49:44'),
(92, NULL, 7, 'STU-2026-07002', '2025-04-01', '2015-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vivaan Singh', '9876500101', 'vivaan.singh@email.com', 'Mr. Guardian', '9123400101', 1, '2026-05-23 17:49:44'),
(93, NULL, 7, 'STU-2026-07003', '2025-04-01', '2014-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aditya Kumar', '9876500102', 'aditya.kumar@email.com', 'Mr. Guardian', '9123400102', 1, '2026-05-23 17:49:44'),
(94, NULL, 7, 'STU-2026-07004', '2025-04-01', '2013-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Vihaan Sharma', '9876500103', 'vihaan.sharma@email.com', 'Mr. Guardian', '9123400103', 1, '2026-05-23 17:49:44'),
(95, NULL, 7, 'STU-2026-07005', '2025-04-01', '2012-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Arjun Verma', '9876500104', 'arjun.verma@email.com', 'Mr. Guardian', '9123400104', 1, '2026-05-23 17:49:44'),
(96, NULL, 7, 'STU-2026-07006', '2025-04-01', '2011-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sai Reddy', '9876500105', 'sai.reddy@email.com', 'Mr. Guardian', '9123400105', 1, '2026-05-23 17:49:44'),
(97, NULL, 7, 'STU-2026-07007', '2025-04-01', '2010-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ananya Gupta', '9876500106', 'ananya.gupta@email.com', 'Mr. Guardian', '9123400106', 1, '2026-05-23 17:49:44'),
(98, NULL, 7, 'STU-2026-07008', '2025-04-01', '2009-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Diya Joshi', '9876500107', 'diya.joshi@email.com', 'Mr. Guardian', '9123400107', 1, '2026-05-23 17:49:44'),
(99, NULL, 7, 'STU-2026-07009', '2025-04-01', '2008-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Ishita Mehta', '9876500108', 'ishita.mehta@email.com', 'Mr. Guardian', '9123400108', 1, '2026-05-23 17:49:45'),
(100, NULL, 7, 'STU-2026-07010', '2025-04-01', '2007-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Riya Saxena', '9876500109', 'riya.saxena@email.com', 'Mr. Guardian', '9123400109', 1, '2026-05-23 17:49:45'),
(101, NULL, 7, 'STU-2026-07011', '2025-04-01', '2006-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Aisha Khan', '9876500110', 'aisha.khan@email.com', 'Mr. Guardian', '9123400110', 1, '2026-05-23 17:49:45'),
(102, NULL, 7, 'STU-2026-07012', '2025-04-01', '2005-05-23', 'female', 'AB-', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Myra Nair', '9876500111', 'myra.nair@email.com', 'Mr. Guardian', '9123400111', 1, '2026-05-23 17:49:45'),
(103, NULL, 7, 'STU-2026-07013', '2025-04-01', '2004-05-23', 'male', 'A+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Kiara Das', '9876500112', 'kiara.das@email.com', 'Mr. Guardian', '9123400112', 1, '2026-05-23 17:49:45'),
(104, NULL, 7, 'STU-2026-07014', '2025-04-01', '2003-05-23', 'female', 'B+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Navya Choudhury', '9876500113', 'navya.choudhury@email.com', 'Mr. Guardian', '9123400113', 1, '2026-05-23 17:49:45'),
(105, NULL, 7, 'STU-2026-07015', '2025-04-01', '2002-05-23', 'male', 'O+', '123, Sample Street', 'Mumbai', 'Maharashtra', NULL, 'Sara Iyer', '9876500114', 'sara.iyer@email.com', 'Mr. Guardian', '9123400114', 1, '2026-05-23 17:49:45'),
(106, NULL, 7, 'STU-2026-6A18793C2AF12', '2026-05-28', '2013-07-02', 'male', 'O+', '', 'Nairobi', '', NULL, 'Johnson', '0722697238', 'migwitapa@gmail.com', '', '', 1, '2026-05-28 17:19:56');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `description`, `is_active`) VALUES
(1, 'Mathematics', 'MATH', NULL, 1),
(2, 'Science', 'SCI', NULL, 1),
(3, 'English', 'ENG', NULL, 1),
(4, 'Hindi', 'HIN', NULL, 1),
(5, 'Social Studies', 'SST', NULL, 1),
(6, 'Computer Science', 'CS', NULL, 1),
(7, 'Physics', 'PHY', NULL, 1),
(8, 'Chemistry', 'CHEM', NULL, 1),
(9, 'Biology', 'BIO', NULL, 1),
(10, 'History', 'HIST', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `total_marks` int(11) DEFAULT 100,
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium',
  `mcq_count` int(11) DEFAULT 0,
  `subjective_count` int(11) DEFAULT 0,
  `duration_minutes` int(11) DEFAULT 60,
  `instructions` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`id`, `title`, `class_id`, `subject_id`, `total_marks`, `difficulty`, `mcq_count`, `subjective_count`, `duration_minutes`, `instructions`, `created_by`, `is_active`, `created_at`) VALUES
(1, 'bio1', 7, 9, 100, 'easy', 5, 3, 60, '', 1, 1, '2026-05-23 18:07:40'),
(2, 'Sample test', 1, 8, 100, 'hard', 5, 3, 60, '', 2, 1, '2026-05-23 18:24:23');

-- --------------------------------------------------------

--
-- Table structure for table `test_submissions`
--

CREATE TABLE `test_submissions` (
  `id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `answers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`answers`)),
  `total_marks_obtained` decimal(5,2) DEFAULT NULL,
  `status` enum('pending','evaluated','resubmitted') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `evaluated_at` datetime DEFAULT NULL,
  `evaluated_by` int(11) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `test_submissions`
--

INSERT INTO `test_submissions` (`id`, `test_id`, `student_id`, `answers`, `total_marks_obtained`, `status`, `submitted_at`, `evaluated_at`, `evaluated_by`, `feedback`) VALUES
(1, 2, 1, '{\"q_2\":\"john does\"}', 0.00, 'pending', '2026-05-28 16:59:33', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_disputes`
--

CREATE TABLE `timetable_disputes` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','resolved','dismissed') DEFAULT 'pending',
  `resolved_by` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` int(11) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL COMMENT '0=Sun,1=Mon,2=Tue,3=Wed,4=Thu,5=Fri,6=Sat',
  `period_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_periods`
--

CREATE TABLE `timetable_periods` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable_periods`
--

INSERT INTO `timetable_periods` (`id`, `name`, `start_time`, `end_time`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Period 1', '08:00:00', '08:40:00', 1, 1, '2026-05-28 13:40:05'),
(2, 'Period 2', '08:40:00', '09:20:00', 2, 1, '2026-05-28 13:40:05'),
(3, 'Period 3', '09:20:00', '10:00:00', 3, 1, '2026-05-28 13:40:05'),
(4, 'Break', '10:00:00', '10:30:00', 4, 1, '2026-05-28 13:40:05'),
(5, 'Period 4', '10:30:00', '11:10:00', 5, 1, '2026-05-28 13:40:05'),
(6, 'Period 5', '11:10:00', '11:50:00', 6, 1, '2026-05-28 13:40:05'),
(7, 'Period 6', '11:50:00', '12:30:00', 7, 1, '2026-05-28 13:40:05'),
(8, 'Lunch', '12:30:00', '13:30:00', 8, 1, '2026-05-28 13:40:05'),
(9, 'Period 7', '13:30:00', '14:10:00', 9, 1, '2026-05-28 13:40:05'),
(10, 'Period 8', '14:10:00', '14:50:00', 10, 1, '2026-05-28 13:40:05'),
(11, 'Period 1', '08:00:00', '08:40:00', 1, 1, '2026-05-28 14:27:59'),
(12, 'Period 2', '08:40:00', '09:20:00', 2, 1, '2026-05-28 14:27:59'),
(13, 'Period 3', '09:20:00', '10:00:00', 3, 1, '2026-05-28 14:27:59'),
(14, 'Break', '10:00:00', '10:30:00', 4, 1, '2026-05-28 14:27:59'),
(15, 'Period 4', '10:30:00', '11:10:00', 5, 1, '2026-05-28 14:27:59'),
(16, 'Period 5', '11:10:00', '11:50:00', 6, 1, '2026-05-28 14:27:59'),
(17, 'Period 6', '11:50:00', '12:30:00', 7, 1, '2026-05-28 14:27:59'),
(18, 'Lunch', '12:30:00', '13:30:00', 8, 1, '2026-05-28 14:27:59'),
(19, 'Period 7', '13:30:00', '14:10:00', 9, 1, '2026-05-28 14:27:59'),
(20, 'Period 8', '14:10:00', '14:50:00', 10, 1, '2026-05-28 14:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `term` varchar(50) DEFAULT NULL,
  `session_year` varchar(20) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `line_items` text DEFAULT NULL,
  `paid_amount` decimal(10,2) NOT NULL,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('paid','partial','pending') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `payment_note` text DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_attendance`
--

CREATE TABLE `transport_attendance` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late') DEFAULT 'present',
  `trip_type` enum('pickup','drop') DEFAULT 'pickup',
  `remarks` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_drivers`
--

CREATE TABLE `transport_drivers` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_routes`
--

CREATE TABLE `transport_routes` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_route_stops`
--

CREATE TABLE `transport_route_stops` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `landmark` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `stop_order` int(11) DEFAULT 0,
  `pickup_time` time DEFAULT NULL,
  `drop_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_route_students`
--

CREATE TABLE `transport_route_students` (
  `id` int(11) NOT NULL,
  `route_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `stop_id` int(11) DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT 0.00,
  `session` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_vehicles`
--

CREATE TABLE `transport_vehicles` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','teacher','student') NOT NULL DEFAULT 'student',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `role`, `avatar`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@jewelhouse.sc.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', NULL, 'admin', NULL, 1, '2026-05-23 17:49:32', '2026-05-24 09:05:59'),
(2, 'teacher', 'teacher@jewelhouse.sc.ke', '$2y$12$prBgv3Qzn9.20Cotm1g63.EgE36mbC5WuL/hhKUPKZQtDqeF39zsW', 'Rahul Sharma', '9876543210', 'teacher', NULL, 1, '2026-05-23 17:49:39', '2026-05-24 09:05:59'),
(3, 'student', 'student@jewelhouse.sc.ke', '$2y$12$PS6eYOXfPB./9ZeIN.t4Ze.JwmoZUvDVo4CI7UyW2CxxY.cOcaYTG', 'Aarav Patel', NULL, 'student', NULL, 1, '2026-05-23 18:07:46', '2026-05-24 09:05:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`date`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `type` (`type`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `fk_cg_creator` (`created_by`);

--
-- Indexes for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_user` (`group_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_id` (`class_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `entry_date` (`entry_date`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `fee_structure_classes`
--
ALTER TABLE `fee_structure_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `fee_types`
--
ALTER TABLE `fee_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `date` (`date`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `leave_type_id` (`leave_type_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `live_classes`
--
ALTER TABLE `live_classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `live_class_attendance`
--
ALTER TABLE `live_class_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `live_class_id` (`live_class_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notices`
--
ALTER TABLE `notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_unread` (`user_id`,`is_read`);

--
-- Indexes for table `payment_gateway_config`
--
ALTER TABLE `payment_gateway_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `gateway_name` (`gateway_name`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `live_class_id` (`live_class_id`);

--
-- Indexes for table `poll_responses`
--
ALTER TABLE `poll_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `poll_id` (`poll_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date` (`user_id`,`date`),
  ADD KEY `date` (`date`),
  ADD KEY `fk_sa_marked_by` (`marked_by`);

--
-- Indexes for table `staff_beneficiaries`
--
ALTER TABLE `staff_beneficiaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `staff_details`
--
ALTER TABLE `staff_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `staff_next_of_kin`
--
ALTER TABLE `staff_next_of_kin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrollment_id` (`enrollment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `test_submissions`
--
ALTER TABLE `test_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `test_id` (`test_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `evaluated_by` (`evaluated_by`);

--
-- Indexes for table `timetable_disputes`
--
ALTER TABLE `timetable_disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entry_id` (`entry_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `period_id` (`period_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetable_periods`
--
ALTER TABLE `timetable_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`);

--
-- Indexes for table `transport_attendance`
--
ALTER TABLE `transport_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `marked_by` (`marked_by`);

--
-- Indexes for table `transport_drivers`
--
ALTER TABLE `transport_drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transport_routes`
--
ALTER TABLE `transport_routes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `transport_route_stops`
--
ALTER TABLE `transport_route_stops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`);

--
-- Indexes for table `transport_route_students`
--
ALTER TABLE `transport_route_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `stop_id` (`stop_id`);

--
-- Indexes for table `transport_vehicles`
--
ALTER TABLE `transport_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chat_groups`
--
ALTER TABLE `chat_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `class_subjects`
--
ALTER TABLE `class_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diary_entries`
--
ALTER TABLE `diary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fee_structure_classes`
--
ALTER TABLE `fee_structure_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_types`
--
ALTER TABLE `fee_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `leave_applications`
--
ALTER TABLE `leave_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `live_classes`
--
ALTER TABLE `live_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `live_class_attendance`
--
ALTER TABLE `live_class_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notices`
--
ALTER TABLE `notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_gateway_config`
--
ALTER TABLE `payment_gateway_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `poll_responses`
--
ALTER TABLE `poll_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_beneficiaries`
--
ALTER TABLE `staff_beneficiaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_details`
--
ALTER TABLE `staff_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_next_of_kin`
--
ALTER TABLE `staff_next_of_kin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tests`
--
ALTER TABLE `tests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `test_submissions`
--
ALTER TABLE `test_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable_disputes`
--
ALTER TABLE `timetable_disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable_periods`
--
ALTER TABLE `timetable_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_attendance`
--
ALTER TABLE `transport_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_drivers`
--
ALTER TABLE `transport_drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_routes`
--
ALTER TABLE `transport_routes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_route_stops`
--
ALTER TABLE `transport_route_stops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_route_students`
--
ALTER TABLE `transport_route_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_vehicles`
--
ALTER TABLE `transport_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `chat_groups`
--
ALTER TABLE `chat_groups`
  ADD CONSTRAINT `fk_cg_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cg_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_group_members`
--
ALTER TABLE `chat_group_members`
  ADD CONSTRAINT `fk_cgm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cgm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_cm_group` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cm_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_subjects`
--
ALTER TABLE `class_subjects`
  ADD CONSTRAINT `class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `diary_entries`
--
ALTER TABLE `diary_entries`
  ADD CONSTRAINT `fk_diary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exams_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `exam_subjects`
--
ALTER TABLE `exam_subjects`
  ADD CONSTRAINT `exam_subjects_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `fee_structure_classes`
--
ALTER TABLE `fee_structure_classes`
  ADD CONSTRAINT `fee_structure_classes_ibfk_1` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_structure_classes_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_types`
--
ALTER TABLE `fee_types`
  ADD CONSTRAINT `fee_types_ibfk_1` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `holidays`
--
ALTER TABLE `holidays`
  ADD CONSTRAINT `holidays_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD CONSTRAINT `fk_leave_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_leave_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_leave_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_classes`
--
ALTER TABLE `live_classes`
  ADD CONSTRAINT `live_classes_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `live_classes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `live_class_attendance`
--
ALTER TABLE `live_class_attendance`
  ADD CONSTRAINT `live_class_attendance_ibfk_1` FOREIGN KEY (`live_class_id`) REFERENCES `live_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `live_class_attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notices`
--
ALTER TABLE `notices`
  ADD CONSTRAINT `notices_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `polls_ibfk_1` FOREIGN KEY (`live_class_id`) REFERENCES `live_classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_responses`
--
ALTER TABLE `poll_responses`
  ADD CONSTRAINT `poll_responses_ibfk_1` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `poll_responses_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD CONSTRAINT `fk_sa_marked_by` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_sa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_beneficiaries`
--
ALTER TABLE `staff_beneficiaries`
  ADD CONSTRAINT `fk_beneficiary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_details`
--
ALTER TABLE `staff_details`
  ADD CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff_next_of_kin`
--
ALTER TABLE `staff_next_of_kin`
  ADD CONSTRAINT `fk_nok_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tests_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tests_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `test_submissions`
--
ALTER TABLE `test_submissions`
  ADD CONSTRAINT `test_submissions_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `test_submissions_ibfk_3` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `timetable_disputes`
--
ALTER TABLE `timetable_disputes`
  ADD CONSTRAINT `fk_td_entry` FOREIGN KEY (`entry_id`) REFERENCES `timetable_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_td_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD CONSTRAINT `fk_tt_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tt_period` FOREIGN KEY (`period_id`) REFERENCES `timetable_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tt_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transport_attendance`
--
ALTER TABLE `transport_attendance`
  ADD CONSTRAINT `fk_ta_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ta_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ta_user` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transport_routes`
--
ALTER TABLE `transport_routes`
  ADD CONSTRAINT `fk_route_driver` FOREIGN KEY (`driver_id`) REFERENCES `transport_drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_route_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transport_route_stops`
--
ALTER TABLE `transport_route_stops`
  ADD CONSTRAINT `fk_stop_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transport_route_students`
--
ALTER TABLE `transport_route_students`
  ADD CONSTRAINT `fk_rs_route` FOREIGN KEY (`route_id`) REFERENCES `transport_routes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rs_stop` FOREIGN KEY (`stop_id`) REFERENCES `transport_route_stops` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
