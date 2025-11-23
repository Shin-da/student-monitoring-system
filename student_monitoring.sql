-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 18, 2025 at 01:55 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assignment_type` enum('quiz','homework','project','exam','activity','other') DEFAULT 'homework',
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `teacher_id`, `section_id`, `subject_id`, `attendance_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 6, 1, 1, 7, '2025-11-17', 'present', NULL, '2025-11-17 03:13:57', '2025-11-17 03:13:57'),
(2, 6, 1, 1, 4, '2025-11-17', 'present', NULL, '2025-11-17 03:14:52', '2025-11-17 03:14:52'),
(3, 8, 1, 1, 4, '2025-11-17', 'late', NULL, '2025-11-17 03:15:00', '2025-11-17 03:15:00'),
(4, 6, 1, 1, 7, '2025-11-14', 'present', NULL, '2025-11-17 03:23:28', '2025-11-17 03:23:28'),
(5, 6, 1, 1, 2, '2025-11-14', 'present', NULL, '2025-11-17 03:23:33', '2025-11-17 03:23:33'),
(6, 6, 1, 1, 4, '2025-11-14', 'present', NULL, '2025-11-17 03:23:37', '2025-11-17 03:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` int(10) UNSIGNED NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(0, 1, 'student_created', 'student', 0, '{\"student_name\":\"ariane balani martirez\",\"student_email\":\"arianemartirez@gmail.com\",\"student_number\":null,\"lrn\":\"1234234354\",\"grade_level\":\"12\",\"section_id\":\"11\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-16 13:38:33'),
(1, 1, 'user_approved', 'user', 3, '{\"approved_role\":\"student\",\"user_email\":\"test@example.com\",\"user_name\":\"Test User\"}', NULL, NULL, '2025-10-17 04:47:17'),
(2, 1, 'user_approved', 'user', 4, '{\"approved_role\":\"student\",\"user_email\":\"johncalrbeee@gmail.com\",\"user_name\":\"John Carlbe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 04:48:34'),
(3, 1, 'user_deleted', 'user', 3, '{\"deleted_role\":\"student\",\"user_email\":\"test@example.com\",\"user_name\":\"Test User\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 04:48:53'),
(4, 1, 'user_deleted', 'user', 4, '{\"deleted_role\":\"student\",\"user_email\":\"johncalrbeee@gmail.com\",\"user_name\":\"John Carlbe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 13:43:16'),
(5, 1, 'user_approved', 'user', 5, '{\"approved_role\":\"student\",\"user_email\":\"johncarlbeg@gmail.com\",\"user_name\":\"John Carlbe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-17 13:52:30'),
(6, 1, 'user_deleted', 'user', 6, '{\"deleted_role\":\"parent\",\"user_email\":\"dad@da.com\",\"user_name\":\"Ssdada ddawS\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 07:44:23'),
(7, 1, 'user_approved', 'user', 7, '{\"approved_role\":\"student\",\"user_email\":\"jeffstudent04@gmail.com\",\"user_name\":\"Jeff Garcia\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-18 08:19:49'),
(8, 1, 'user_deleted', 'user', 5, '{\"deleted_role\":\"student\",\"user_email\":\"johncarlbeg@gmail.com\",\"user_name\":\"John Carlbe\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 04:33:18'),
(9, 1, 'user_deleted', 'user', 7, '{\"deleted_role\":\"student\",\"user_email\":\"jeffstudent04@gmail.com\",\"user_name\":\"Jeff Garcia\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:19:19'),
(10, 1, 'user_approved', 'user', 8, '{\"approved_role\":\"student\",\"user_email\":\"jeffstudent04@gmail.com\",\"user_name\":\"Jeff Mathew Garcia\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 05:20:26'),
(11, 1, 'student_created', 'student', 9, '{\"student_name\":\"Andrei Datoon Garcia\",\"student_email\":\"andrei@gmail.com\",\"student_number\":\"\",\"lrn\":\"\",\"grade_level\":\"7\",\"section_id\":\"1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 06:52:07'),
(12, 1, 'adviser_assigned', 'section', 1, '{\"section_name\":\"Grade 7 - Section A\",\"adviser_name\":\"Shin Da\",\"adviser_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-24 08:18:45'),
(13, 1, 'student_created', 'student', 10, '{\"student_name\":\"aliyah alolor saragina\",\"student_email\":\"aliya@gmail.com\",\"student_number\":null,\"lrn\":\"1234565412\",\"grade_level\":\"7\",\"section_id\":\"1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-01 04:01:56'),
(14, 1, 'student_created', 'student', 11, '{\"student_name\":\"Juan D Delacruz\",\"student_email\":\"jeffstudent04dddd@gmail.com\",\"student_number\":null,\"lrn\":\"108423080169\",\"grade_level\":\"7\",\"section_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-07 13:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `backup_students_old`
--

CREATE TABLE `backup_students_old` (
  `id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` int(10) UNSIGNED NOT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `section_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `backup_students_old`
--

INSERT INTO `backup_students_old` (`id`, `user_id`, `lrn`, `grade_level`, `section_id`, `created_at`, `updated_at`) VALUES
(4, 5, 'LRN000005', 7, 1, '2025-10-17 13:52:30', '2025-10-17 13:52:30'),
(5, 7, 'LRN000007', 7, 1, '2025-10-18 08:19:49', '2025-10-18 08:19:49'),
(4, 5, 'LRN000005', 7, 1, '2025-10-17 13:52:30', '2025-10-17 13:52:30'),
(5, 7, 'LRN000007', 7, 1, '2025-10-18 08:19:49', '2025-10-18 08:19:49');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `school_year` varchar(10) NOT NULL DEFAULT '2025-2026',
  `semester` enum('1st','2nd') DEFAULT '1st',
  `schedule` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `section_id`, `subject_id`, `teacher_id`, `school_year`, `semester`, `schedule`, `room`, `is_active`, `created_at`, `updated_at`) VALUES
(0, 11, 7, 1, '2025-2026', '1st', 'W 7:00 AM-8:30 AM', '232', 1, '2025-11-16 13:43:52', '2025-11-16 13:43:52'),
(10, 1, 7, 1, '2025-2026', '1st', 'M 7:00 AM-8:30 AM', '315', 1, '2025-11-01 03:28:58', '2025-11-01 03:28:58'),
(11, 1, 2, 1, '2025-2026', '1st', 'M 8:30 AM-11:00 AM', '315', 1, '2025-11-01 03:29:20', '2025-11-01 03:29:20'),
(12, 1, 4, 1, '2025-2026', '1st', 'T 7:00 AM-8:30 AM', '315', 1, '2025-11-01 03:29:42', '2025-11-01 03:29:42'),
(13, 1, 6, 1, '2025-2026', '1st', 'M 11:00 AM-12:30 PM', '315', 1, '2025-11-01 03:33:14', '2025-11-01 03:33:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `final_grades_view`
-- (See below for the actual view)
--
CREATE TABLE `final_grades_view` (
`student_id` int(10) unsigned
,`section_id` int(10) unsigned
,`subject_id` int(10) unsigned
,`quarter` tinyint(3) unsigned
,`academic_year` varchar(20)
,`ww_average` decimal(18,10)
,`pt_average` decimal(18,10)
,`qe_average` decimal(18,10)
,`ww_percent` tinyint(3) unsigned
,`pt_percent` tinyint(3) unsigned
,`qe_percent` tinyint(3) unsigned
,`attendance_percent` tinyint(3) unsigned
,`final_grade_without_attendance` decimal(16,2)
,`status_without_attendance` varchar(6)
);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `grade_type` enum('ww','pt','qe') NOT NULL COMMENT 'Written Work, Performance Task, Quarterly Exam',
  `quarter` tinyint(3) UNSIGNED NOT NULL COMMENT '1=1st, 2=2nd, 3=3rd, 4=4th',
  `academic_year` varchar(20) NOT NULL DEFAULT '2024-2025',
  `grade_value` decimal(5,2) NOT NULL COMMENT 'Actual score',
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Maximum possible score',
  `description` varchar(255) DEFAULT NULL COMMENT 'Assignment/activity name',
  `remarks` text DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `student_id`, `section_id`, `subject_id`, `teacher_id`, `grade_type`, `quarter`, `academic_year`, `grade_value`, `max_score`, `description`, `remarks`, `graded_at`, `created_at`, `updated_at`) VALUES
(3, 6, 1, 4, 1, 'ww', 1, '2025-2026', '23.00', '100.00', NULL, NULL, '2025-11-16 03:58:35', '2025-11-16 03:58:35', '2025-11-16 17:31:26'),
(4, 8, 1, 7, 1, 'ww', 1, '2025-2026', '45.00', '100.00', 'ggsg', 'gsgsg', '2025-11-16 13:31:59', '2025-11-16 13:31:59', '2025-11-16 17:31:20'),
(5, 0, 11, 7, 1, 'pt', 1, '2025-2026', '87.00', '100.00', NULL, NULL, '2025-11-16 17:15:49', '2025-11-16 17:15:49', '2025-11-16 17:31:14'),
(6, 6, 1, 4, 1, 'qe', 1, '2025-2026', '97.00', '100.00', NULL, NULL, '2025-11-16 17:32:06', '2025-11-16 17:32:06', '2025-11-16 17:32:06'),
(7, 6, 1, 4, 1, 'ww', 1, '2025-2026', '89.00', '100.00', 'Seatwork 2', NULL, '2025-11-17 02:51:00', '2025-11-17 02:51:00', '2025-11-17 02:51:00'),
(8, 6, 1, 4, 1, 'pt', 1, '2025-2026', '87.00', '100.00', 'Roleplay', NULL, '2025-11-17 02:51:45', '2025-11-17 02:51:45', '2025-11-17 02:51:45'),
(9, 6, 1, 2, 1, 'qe', 1, '2025-2026', '91.00', '100.00', 'Periodical Test', NULL, '2025-11-17 02:53:13', '2025-11-17 02:53:13', '2025-11-17 02:53:13'),
(10, 6, 1, 6, 1, 'pt', 1, '2025-2026', '87.00', '100.00', 'adawdaadawdasd ', 'asfasfafas', '2025-11-17 03:46:30', '2025-11-17 03:46:30', '2025-11-17 03:46:30');

-- --------------------------------------------------------

--
-- Stand-in structure for view `quarterly_grades_view`
-- (See below for the actual view)
--
CREATE TABLE `quarterly_grades_view` (
`student_id` int(10) unsigned
,`section_id` int(10) unsigned
,`subject_id` int(10) unsigned
,`quarter` tinyint(3) unsigned
,`academic_year` varchar(20)
,`ww_average` decimal(18,10)
,`pt_average` decimal(18,10)
,`qe_average` decimal(18,10)
,`ww_count` bigint(21)
,`pt_count` bigint(21)
,`qe_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `max_students` int(11) DEFAULT 50,
  `school_year` varchar(10) NOT NULL DEFAULT '2025-2026',
  `adviser_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `grade_level`, `room`, `max_students`, `school_year`, `adviser_id`, `description`, `is_active`, `created_at`) VALUES
(1, 'Grade 7 - Section A', 7, 'Room 101', 40, '2025-2026', 2, 'Main Grade 7 section', 1, '2025-10-18 08:58:39'),
(2, 'Grade 7 - Section B', 7, 'Room 102', 40, '2025-2026', NULL, 'Grade 7 Section B', 1, '2025-10-24 06:46:10'),
(3, 'Grade 8 - Section A', 8, 'Room 201', 40, '2025-2026', NULL, 'Grade 8 Section A', 1, '2025-10-24 06:46:10'),
(4, 'Grade 8 - Section B', 8, 'Room 202', 40, '2025-2026', NULL, 'Grade 8 Section B', 1, '2025-10-24 06:46:10'),
(5, 'Grade 9 - Section A', 9, 'Room 301', 40, '2025-2026', NULL, 'Grade 9 Section A', 1, '2025-10-24 06:46:10'),
(6, 'Grade 9 - Section B', 9, 'Room 302', 40, '2025-2026', NULL, 'Grade 9 Section B', 1, '2025-10-24 06:46:10'),
(7, 'Grade 10 - Section A', 10, 'Room 401', 40, '2025-2026', NULL, 'Grade 10 Section A', 1, '2025-10-24 06:46:10'),
(8, 'Grade 10 - Section B', 10, 'Room 402', 40, '2025-2026', NULL, 'Grade 10 Section B', 1, '2025-10-24 06:46:10'),
(9, 'Grade 11 - Section A', 11, 'Room 501', 40, '2025-2026', NULL, 'Grade 11 Section A', 1, '2025-10-24 06:46:10'),
(10, 'Grade 11 - Section B', 11, 'Room 502', 40, '2025-2026', NULL, 'Grade 11 Section B', 1, '2025-10-24 06:46:10'),
(11, 'Grade 12 - Section A', 12, 'Room 601', 40, '2025-2026', NULL, 'Grade 12 Section A', 1, '2025-10-24 06:46:10'),
(12, 'Grade 12 - Section B', 12, 'Room 602', 40, '2025-2026', NULL, 'Grade 12 Section B', 1, '2025-10-24 06:46:10');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `lrn` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(191) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `section_id` int(10) UNSIGNED DEFAULT NULL,
  `school_year` varchar(10) DEFAULT '2025-2026',
  `enrollment_status` enum('enrolled','transferred','dropped','graduated') DEFAULT 'enrolled',
  `previous_school` varchar(191) DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `emergency_contact_name` varchar(191) DEFAULT NULL,
  `emergency_contact_number` varchar(20) DEFAULT NULL,
  `emergency_contact_relationship` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_enrolled` date DEFAULT NULL,
  `date_graduated` date DEFAULT NULL,
  `status` enum('enrolled','transferred','graduated','dropped') DEFAULT 'enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `lrn`, `first_name`, `last_name`, `middle_name`, `birth_date`, `gender`, `contact_number`, `address`, `guardian_name`, `guardian_contact`, `guardian_relationship`, `grade_level`, `section_id`, `school_year`, `enrollment_status`, `previous_school`, `medical_conditions`, `allergies`, `emergency_contact_name`, `emergency_contact_number`, `emergency_contact_relationship`, `profile_picture`, `notes`, `date_enrolled`, `date_graduated`, `status`, `created_at`, `updated_at`) VALUES
(0, 0, '1234234354', 'ariane', 'martirez', 'balani', '2006-12-16', 'female', '12343252452', 'tunasan', 'balanii', '23545422`', 'mother', 12, 11, '2025-2026', 'transferred', '', 'vdavadva', 'vbbdasva', 'dvbsbgsfge', '14654+645', 'grandfather', NULL, '', NULL, NULL, 'enrolled', '2025-11-16 13:38:33', '2025-11-16 13:38:33'),
(6, 8, 'LRN000008', '以轩', '谢', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 1, '2025-2026', 'enrolled', NULL, NULL, NULL, NULL, NULL, NULL, '/assets/profile_pictures/student_8_1761308860_9c9918cf381de7c0.jpg', NULL, NULL, NULL, 'enrolled', '2025-10-24 05:20:26', '2025-10-24 12:27:40'),
(7, 9, '', 'Andrei', 'Garcia', 'Datoon', '2004-02-01', 'male', '+6391234567', 'ORDER ID TEST', 'jeff', '', 'other', 7, 1, '2025-2026', 'enrolled', 'Landayan', '', '', 'Jeff Mathew Datoon Garcia', '+6391234567', 'other', NULL, '', NULL, NULL, 'enrolled', '2025-10-24 06:52:07', '2025-10-24 06:52:07'),
(8, 10, '1234565412', 'aliyah', 'saragina', 'alolor', '2013-07-06', 'female', '131654654', 'gvjhfjhyjhl', 'dthdtdt', 'hgdytdty', 'grandfather', 7, 1, '2025-2026', 'enrolled', '', ' m vhbv ', 'GHCDHGH', 'gcghcgh', 'cghcfhghg', 'father', NULL, 'SGBDSB', NULL, NULL, 'enrolled', '2025-11-01 04:01:56', '2025-11-01 04:01:56'),
(9, 11, '108423080169', 'Juan', 'Delacruz', 'D', '2025-11-04', 'male', '+6309765231', 'dsadda', 'dad', 'dasdada', 'grandfather', 7, 2, '2025-2026', 'enrolled', 'aadadadsawda', 'Admin!is-me04', 'Admin!is-me04', 'adasda', 'admin@example.com', 'guardian', NULL, 'Admin!is-me04', NULL, NULL, 'enrolled', '2025-11-07 13:00:16', '2025-11-07 13:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `student_classes`
--

CREATE TABLE `student_classes` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `class_id` int(10) UNSIGNED NOT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','dropped','completed') DEFAULT 'enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_profiles`
-- (See below for the actual view)
--
CREATE TABLE `student_profiles` (
`student_id` int(10) unsigned
,`user_id` int(10) unsigned
,`email` varchar(191)
,`full_name` varchar(191)
,`user_status` enum('pending','active','suspended')
,`user_created_at` timestamp
,`user_updated_at` timestamp
,`lrn` varchar(20)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`middle_name` varchar(100)
,`full_name_display` varchar(302)
,`birth_date` date
,`gender` enum('male','female','other')
,`contact_number` varchar(20)
,`address` text
,`guardian_name` varchar(191)
,`guardian_contact` varchar(20)
,`guardian_relationship` varchar(50)
,`grade_level` tinyint(3) unsigned
,`section_id` int(10) unsigned
,`section_name` varchar(100)
,`section_room` varchar(50)
,`school_year` varchar(10)
,`enrollment_status` enum('enrolled','transferred','dropped','graduated')
,`previous_school` varchar(191)
,`medical_conditions` text
,`allergies` text
,`emergency_contact_name` varchar(191)
,`emergency_contact_number` varchar(20)
,`emergency_contact_relationship` varchar(50)
,`profile_picture` varchar(255)
,`notes` text
,`date_enrolled` date
,`date_graduated` date
,`student_status` enum('enrolled','transferred','graduated','dropped')
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `code` varchar(20) NOT NULL,
  `grade_level` tinyint(3) UNSIGNED NOT NULL,
  `description` text DEFAULT NULL,
  `ww_percent` tinyint(3) UNSIGNED DEFAULT 30,
  `pt_percent` tinyint(3) UNSIGNED DEFAULT 50,
  `qe_percent` tinyint(3) UNSIGNED DEFAULT 20,
  `attendance_percent` tinyint(3) UNSIGNED DEFAULT 10,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `grade_level`, `description`, `ww_percent`, `pt_percent`, `qe_percent`, `attendance_percent`, `is_active`, `created_at`) VALUES
(1, 'Mathematics', 'MATH7', 7, 'Basic Mathematics for Grade 7', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(2, 'English', 'ENG7', 7, 'English Language and Literature', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(3, 'Science', 'SCI7', 7, 'General Science', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(4, 'Filipino', 'FIL7', 7, 'Filipino Language', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(5, 'Social Studies', 'SS7', 7, 'Social Studies and History', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(6, 'Physical Education', 'PE7', 7, 'Physical Education and Health', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(7, 'Computer Science', 'CS7', 7, 'Introduction to Computer Science', 30, 50, 20, 10, 1, '2025-10-18 08:58:39'),
(8, 'Values Education', 'VE7', 7, 'Values and Character Education', 30, 50, 20, 10, 1, '2025-10-18 08:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `specialization` varchar(191) DEFAULT NULL,
  `is_adviser` tinyint(1) DEFAULT 0,
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `employee_id`, `department`, `specialization`, `is_adviser`, `hire_date`, `created_at`) VALUES
(1, 2, 'EMP002', 'General', NULL, 1, NULL, '2025-10-18 08:58:39');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedules`
--

CREATE TABLE `teacher_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `class_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_schedules`
--

INSERT INTO `teacher_schedules` (`id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `class_id`, `created_at`, `updated_at`) VALUES
(0, 1, 'Wednesday', '07:00:00', '08:30:00', 0, '2025-11-16 13:43:52', '2025-11-16 13:43:52'),
(9, 1, 'Monday', '07:00:00', '08:30:00', 10, '2025-11-01 03:28:58', '2025-11-01 03:28:58'),
(10, 1, 'Monday', '08:30:00', '11:00:00', 11, '2025-11-01 03:29:20', '2025-11-01 03:29:20'),
(11, 1, 'Tuesday', '07:00:00', '08:30:00', 12, '2025-11-01 03:29:42', '2025-11-01 03:29:42'),
(12, 1, 'Monday', '11:00:00', '12:30:00', 13, '2025-11-01 03:33:14', '2025-11-01 03:33:14');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role` enum('admin','teacher','adviser','student','parent') NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `requested_role` enum('admin','teacher','adviser','student','parent') DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password_hash`, `name`, `phone`, `address`, `created_at`, `updated_at`, `status`, `requested_role`, `approved_by`, `approved_at`) VALUES
(0, 'student', 'arianemartirez@gmail.com', '$2y$10$AFyeu02x1paFSmVhcaF81eUgaRQtsiYz.d66cB4xXF7N7R4uUw/cW', 'ariane balani martirez', NULL, NULL, '2025-11-16 13:38:33', '2025-11-16 13:38:33', 'active', NULL, 1, '2025-11-16 13:38:33'),
(1, 'admin', 'admin@school.edu', '$2y$10$kcVb2JJm.eD.91F2YFtiu.xJKCD.bZJV5n5CUB5ukf1yHcFeRBlZ6', 'System Administrator', NULL, NULL, '2025-10-16 19:37:51', '2025-10-16 14:21:19', 'active', NULL, NULL, NULL),
(2, 'teacher', 'teacher@gmail.com', '$2y$10$2X.vGcyfr7drEFSj5P5BZOQwF7bQfFF.CFLHKohz5GvYh7v7WAkhe', 'Shin Da', NULL, NULL, '2025-10-16 19:56:17', '2025-11-07 15:10:16', 'active', NULL, NULL, NULL),
(8, 'student', 'jeffstudent04@gmail.com', '$2y$10$8Ek3HSxaJV2PUhtHrvXK1.wiD011oYSEUzx4Q8aCxcydkZ3MaMpQ2', '谢以轩', NULL, NULL, '2025-10-24 05:19:52', '2025-10-24 12:22:49', 'active', 'student', 1, '2025-10-24 05:20:26'),
(9, 'student', 'andrei@gmail.com', '$2y$10$f4iCQ9fJeEQwz22Va4q9uut/LGg99aXXKBXSEyUHm0JA8A.65wvj2', 'Andrei Datoon Garcia', NULL, NULL, '2025-10-24 06:52:07', '2025-10-24 06:52:07', 'active', NULL, 1, '2025-10-24 06:52:07'),
(10, 'student', 'aliya@gmail.com', '$2y$10$yWGe8BOlyiul3aCx2XTT9eDsxiMpF3DFb6NUFDS0p6ad3oxsIlcu.', 'aliyah alolor saragina', NULL, NULL, '2025-11-01 04:01:56', '2025-11-01 04:01:56', 'active', NULL, 1, '2025-11-01 04:01:56'),
(11, 'student', 'jeffstudent04dddd@gmail.com', '$2y$10$QUSaDxWHV1M2po4HC8nsjOGoDSAxCuHN3M2WBRkcdKU0GuumDYHKG', 'Juan D Delacruz', NULL, NULL, '2025-11-07 13:00:16', '2025-11-07 13:00:16', 'active', NULL, 1, '2025-11-07 13:00:16');

-- --------------------------------------------------------

--
-- Table structure for table `user_requests`
--

CREATE TABLE `user_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `requested_role` enum('admin','teacher','adviser','student','parent') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`additional_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_requests`
--

INSERT INTO `user_requests` (`id`, `user_id`, `requested_role`, `status`, `requested_at`, `processed_at`, `processed_by`, `rejection_reason`, `additional_data`) VALUES
(1, 0, 'student', 'pending', '2025-10-17 04:30:20', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 08:25:14\"}'),
(2, 0, 'student', 'pending', '2025-10-17 04:30:20', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 21:20:03\"}'),
(3, 0, 'student', 'pending', '2025-10-17 04:39:50', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 08:25:14\"}'),
(4, 0, 'student', 'pending', '2025-10-17 04:39:50', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 21:20:03\"}'),
(5, 0, 'student', 'pending', '2025-10-17 04:40:22', NULL, NULL, NULL, '{\"test\":true}'),
(8, 5, 'student', 'approved', '2025-10-17 13:52:24', '2025-10-17 13:52:30', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"registration_time\":\"2025-10-17 15:52:24\"}'),
(9, 7, 'student', 'approved', '2025-10-18 08:19:41', '2025-10-18 08:19:49', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"registration_time\":\"2025-10-18 10:19:41\"}'),
(10, 8, 'student', 'approved', '2025-10-24 05:19:52', '2025-10-24 05:20:26', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36 Edg\\/141.0.0.0\",\"registration_time\":\"2025-10-24 07:19:52\"}'),
(1, 0, 'student', 'pending', '2025-10-17 04:30:20', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 08:25:14\"}'),
(2, 0, 'student', 'pending', '2025-10-17 04:30:20', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 21:20:03\"}'),
(3, 0, 'student', 'pending', '2025-10-17 04:39:50', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 08:25:14\"}'),
(4, 0, 'student', 'pending', '2025-10-17 04:39:50', NULL, NULL, NULL, '{\"registration_ip\":null,\"registration_user_agent\":null,\"registration_time\":\"2025-10-16 21:20:03\"}'),
(5, 0, 'student', 'pending', '2025-10-17 04:40:22', NULL, NULL, NULL, '{\"test\":true}'),
(8, 5, 'student', 'approved', '2025-10-17 13:52:24', '2025-10-17 13:52:30', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"registration_time\":\"2025-10-17 15:52:24\"}'),
(9, 7, 'student', 'approved', '2025-10-18 08:19:41', '2025-10-18 08:19:49', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36\",\"registration_time\":\"2025-10-18 10:19:41\"}'),
(10, 8, 'student', 'approved', '2025-10-24 05:19:52', '2025-10-24 05:20:26', 1, NULL, '{\"registration_ip\":\"::1\",\"registration_user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/141.0.0.0 Safari\\/537.36 Edg\\/141.0.0.0\",\"registration_time\":\"2025-10-24 07:19:52\"}');

-- --------------------------------------------------------

--
-- Structure for view `final_grades_view`
--
DROP TABLE IF EXISTS `final_grades_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `final_grades_view`  AS SELECT `qg`.`student_id` AS `student_id`, `qg`.`section_id` AS `section_id`, `qg`.`subject_id` AS `subject_id`, `qg`.`quarter` AS `quarter`, `qg`.`academic_year` AS `academic_year`, `qg`.`ww_average` AS `ww_average`, `qg`.`pt_average` AS `pt_average`, `qg`.`qe_average` AS `qe_average`, `s`.`ww_percent` AS `ww_percent`, `s`.`pt_percent` AS `pt_percent`, `s`.`qe_percent` AS `qe_percent`, `s`.`attendance_percent` AS `attendance_percent`, round(coalesce(`qg`.`ww_average`,0) * coalesce(`s`.`ww_percent`,20) / 100 + coalesce(`qg`.`pt_average`,0) * coalesce(`s`.`pt_percent`,50) / 100 + coalesce(`qg`.`qe_average`,0) * coalesce(`s`.`qe_percent`,20) / 100,2) AS `final_grade_without_attendance`, CASE WHEN round(coalesce(`qg`.`ww_average`,0) * coalesce(`s`.`ww_percent`,20) / 100 + coalesce(`qg`.`pt_average`,0) * coalesce(`s`.`pt_percent`,50) / 100 + coalesce(`qg`.`qe_average`,0) * coalesce(`s`.`qe_percent`,20) / 100,2) >= 75 THEN 'Passed' ELSE 'Failed' END AS `status_without_attendance` FROM (`quarterly_grades_view` `qg` join `subjects` `s` on(`qg`.`subject_id` = `s`.`id`))  ;

-- --------------------------------------------------------

--
-- Structure for view `quarterly_grades_view`
--
DROP TABLE IF EXISTS `quarterly_grades_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `quarterly_grades_view`  AS SELECT `g`.`student_id` AS `student_id`, `g`.`section_id` AS `section_id`, `g`.`subject_id` AS `subject_id`, `g`.`quarter` AS `quarter`, `g`.`academic_year` AS `academic_year`, avg(case when `g`.`grade_type` = 'ww' then `g`.`grade_value` / nullif(`g`.`max_score`,0) * 100 else NULL end) AS `ww_average`, avg(case when `g`.`grade_type` = 'pt' then `g`.`grade_value` / nullif(`g`.`max_score`,0) * 100 else NULL end) AS `pt_average`, avg(case when `g`.`grade_type` = 'qe' then `g`.`grade_value` / nullif(`g`.`max_score`,0) * 100 else NULL end) AS `qe_average`, count(case when `g`.`grade_type` = 'ww' then 1 end) AS `ww_count`, count(case when `g`.`grade_type` = 'pt' then 1 end) AS `pt_count`, count(case when `g`.`grade_type` = 'qe' then 1 end) AS `qe_count` FROM `grades` AS `g` GROUP BY `g`.`student_id`, `g`.`section_id`, `g`.`subject_id`, `g`.`quarter`, `g`.`academic_year``academic_year`  ;

-- --------------------------------------------------------

--
-- Structure for view `student_profiles`
--
DROP TABLE IF EXISTS `student_profiles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_profiles`  AS SELECT `s`.`id` AS `student_id`, `s`.`user_id` AS `user_id`, `u`.`email` AS `email`, `u`.`name` AS `full_name`, `u`.`status` AS `user_status`, `u`.`created_at` AS `user_created_at`, `u`.`updated_at` AS `user_updated_at`, `s`.`lrn` AS `lrn`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `s`.`middle_name` AS `middle_name`, concat(coalesce(`s`.`first_name`,''),case when `s`.`middle_name` is not null and `s`.`middle_name` <> '' then concat(' ',`s`.`middle_name`) else '' end,case when `s`.`last_name` is not null and `s`.`last_name` <> '' then concat(' ',`s`.`last_name`) else '' end) AS `full_name_display`, `s`.`birth_date` AS `birth_date`, `s`.`gender` AS `gender`, `s`.`contact_number` AS `contact_number`, `s`.`address` AS `address`, `s`.`guardian_name` AS `guardian_name`, `s`.`guardian_contact` AS `guardian_contact`, `s`.`guardian_relationship` AS `guardian_relationship`, `s`.`grade_level` AS `grade_level`, `s`.`section_id` AS `section_id`, `sec`.`name` AS `section_name`, `sec`.`room` AS `section_room`, `s`.`school_year` AS `school_year`, `s`.`enrollment_status` AS `enrollment_status`, `s`.`previous_school` AS `previous_school`, `s`.`medical_conditions` AS `medical_conditions`, `s`.`allergies` AS `allergies`, `s`.`emergency_contact_name` AS `emergency_contact_name`, `s`.`emergency_contact_number` AS `emergency_contact_number`, `s`.`emergency_contact_relationship` AS `emergency_contact_relationship`, `s`.`profile_picture` AS `profile_picture`, `s`.`notes` AS `notes`, `s`.`date_enrolled` AS `date_enrolled`, `s`.`date_graduated` AS `date_graduated`, `s`.`status` AS `student_status`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM ((`students` `s` left join `users` `u` on(`s`.`user_id` = `u`.`id`)) left join `sections` `sec` on(`s`.`section_id` = `sec`.`id`)) WHERE `u`.`role` = 'student\'student\'student\'student''student\'student\'student\'student'  ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_section_subject` (`section_id`,`subject_id`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `assignments_subject_fk` (`subject_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`section_id`,`subject_id`,`attendance_date`),
  ADD KEY `idx_student_section_subject_date` (`student_id`,`section_id`,`subject_id`,`attendance_date`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_section_subject` (`section_id`,`subject_id`),
  ADD KEY `idx_date` (`attendance_date`),
  ADD KEY `attendance_subject_fk` (`subject_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `target_type` (`target_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_class_assignment` (`section_id`,`subject_id`,`teacher_id`,`school_year`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `school_year` (`school_year`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_subject_quarter` (`student_id`,`subject_id`,`quarter`,`academic_year`),
  ADD KEY `idx_section_subject` (`section_id`,`subject_id`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_quarter_year` (`quarter`,`academic_year`),
  ADD KEY `grades_subject_fk` (`subject_id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_level` (`grade_level`),
  ADD KEY `adviser_id` (`adviser_id`),
  ADD KEY `school_year` (`school_year`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lrn` (`lrn`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `grade_level` (`grade_level`),
  ADD KEY `school_year` (`school_year`),
  ADD KEY `idx_first_name` (`first_name`),
  ADD KEY `idx_last_name` (`last_name`),
  ADD KEY `idx_enrollment_status` (`enrollment_status`);

--
-- Indexes for table `student_classes`
--
ALTER TABLE `student_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_class` (`student_id`,`class_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `grade_level` (`grade_level`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`);

--
-- Indexes for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`teacher_id`,`day_of_week`,`start_time`,`end_time`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_teacher_day` (`teacher_id`,`day_of_week`),
  ADD KEY `idx_time_range` (`start_time`,`end_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_users_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_section_fk` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
