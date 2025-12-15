-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 15, 2025 at 05:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(1, 3, 1, 1, 7, '2025-11-17', 'present', NULL, '2025-11-17 03:13:57', '2025-11-17 03:13:57'),
(2, 3, 1, 1, 4, '2025-11-17', 'present', NULL, '2025-11-17 03:14:52', '2025-11-17 03:14:52'),
(3, 6, 1, 1, 4, '2025-11-17', 'late', NULL, '2025-11-17 03:15:00', '2025-11-17 03:15:00'),
(4, 3, 1, 1, 7, '2025-11-14', 'present', NULL, '2025-11-17 03:23:28', '2025-11-17 03:23:28'),
(5, 3, 1, 1, 2, '2025-11-14', 'present', NULL, '2025-11-17 03:23:33', '2025-11-17 03:23:33'),
(6, 3, 1, 1, 4, '2025-11-14', 'present', NULL, '2025-11-17 03:23:37', '2025-11-17 03:23:37'),
(7, 3, 1, 1, 2, '2025-11-27', 'present', NULL, '2025-11-27 16:31:14', '2025-11-27 16:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(0, 1, 'adviser_assigned', 'section', 2, '{\"section_name\":\"Grade 7 - Section B\",\"adviser_name\":\"bryle\",\"adviser_id\":48}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 02:16:48'),
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
(14, 1, 'student_created', 'student', 11, '{\"student_name\":\"Juan D Delacruz\",\"student_email\":\"jeffstudent04dddd@gmail.com\",\"student_number\":null,\"lrn\":\"108423080169\",\"grade_level\":\"7\",\"section_id\":\"2\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-07 13:00:16'),
(16, 1, 'student_created', 'student', 16, '{\"student_name\":\"john doe dimagiba\",\"student_email\":\"jddmagiba01@gmail.com\",\"student_id\":16,\"user_id\":\"18\",\"lrn\":\"108423080973\",\"grade_level\":\"12\",\"section_id\":\"12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 13:41:08'),
(17, 1, 'student_created', 'student', 17, '{\"student_name\":\"john doe dimagiba\",\"student_email\":\"jddmagiba01@gmail.com\",\"student_id\":17,\"user_id\":\"19\",\"lrn\":\"108423080973\",\"grade_level\":\"12\",\"section_id\":\"12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 13:45:00'),
(18, 1, 'student_created', 'student', 18, '{\"student_name\":\"Angela Mae Santos\",\"student_email\":\"angela.santos@example.com\",\"student_id\":18,\"user_id\":\"20\",\"lrn\":\"109834572301\",\"grade_level\":\"12\",\"section_id\":\"12\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-21 14:08:34'),
(19, 1, 'user_deleted', 'user', 23, '{\"deleted_role\":\"parent\",\"user_email\":\"maria.delacruz@example.com\",\"user_name\":\"Maria\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 10:59:11'),
(20, 1, 'user_deleted', 'user', 22, '{\"deleted_role\":\"parent\",\"user_email\":\"daniel.santos@example.com\",\"user_name\":\"Daniel Cruz Santos\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 10:59:36'),
(21, 1, 'section_created', 'section', 0, '{\"section_name\":\"SAMPLE CREATE SECTION\",\"grade_level\":9}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 14:32:08');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs_backup`
--

CREATE TABLE `audit_logs_backup` (
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
-- Dumping data for table `audit_logs_backup`
--

INSERT INTO `audit_logs_backup` (`id`, `user_id`, `action`, `target_type`, `target_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
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
(21, 1, 7, 0, '2025-2026', '1st', 'M 7:00 AM-7:30 AM', '315', 1, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(22, 1, 2, 0, '2025-2026', '1st', 'M 7:30 AM-8:30 AM', '315', 1, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(23, 1, 4, 2, '2025-2026', '1st', 'M 9:00 AM-10:30 AM', '315', 1, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(24, 1, 1, 3, '2025-2026', '1st', 'M 12:00 PM-2:00 PM', '315', 1, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(25, 12, 2, 0, '2025-2026', '1st', 'W 7:00 AM-8:30 AM', '319', 1, '2025-12-11 14:00:53', '2025-12-11 14:00:53'),
(26, 3, 3, 0, '2025-2026', '1st', 'T 7:00 AM-8:00 AM', '1113', 1, '2025-12-11 15:33:33', '2025-12-11 15:33:33'),
(27, 1, 5, 3, '2025-2026', '1st', 'T 3:30 PM-4:30 PM', '315', 1, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(28, 1, 3, 4, '2025-2026', '1st', 'W 1:00 PM-1:30 PM', '232', 1, '2025-12-15 01:01:52', '2025-12-15 01:01:52');

-- --------------------------------------------------------

--
-- Stand-in structure for view `final_grades_view`
-- (See below for the actual view)
--
CREATE TABLE `final_grades_view` (
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
(3, 3, 1, 4, 1, 'ww', 1, '2025-2026', 23.00, 100.00, NULL, NULL, '2025-11-16 03:58:35', '2025-11-16 03:58:35', '2025-11-21 14:31:32'),
(4, 8, 1, 7, 1, 'ww', 1, '2025-2026', 45.00, 100.00, 'ggsg', 'gsgsg', '2025-11-16 13:31:59', '2025-11-16 13:31:59', '2025-11-16 17:31:20'),
(5, 0, 11, 7, 1, 'pt', 1, '2025-2026', 87.00, 100.00, NULL, NULL, '2025-11-16 17:15:49', '2025-11-16 17:15:49', '2025-11-16 17:31:14'),
(6, 3, 1, 4, 1, 'qe', 1, '2025-2026', 97.00, 100.00, NULL, NULL, '2025-11-16 17:32:06', '2025-11-16 17:32:06', '2025-11-21 14:31:44'),
(7, 3, 1, 4, 1, 'ww', 1, '2025-2026', 89.00, 100.00, 'Seatwork 2', NULL, '2025-11-17 02:51:00', '2025-11-17 02:51:00', '2025-11-21 14:31:42'),
(8, 3, 1, 4, 1, 'pt', 1, '2025-2026', 87.00, 100.00, 'Roleplay', NULL, '2025-11-17 02:51:45', '2025-11-17 02:51:45', '2025-11-21 14:31:46'),
(9, 3, 1, 2, 1, 'qe', 1, '2025-2026', 91.00, 100.00, 'Periodical Test', NULL, '2025-11-17 02:53:13', '2025-11-17 02:53:13', '2025-11-21 14:31:51'),
(10, 3, 1, 6, 1, 'pt', 1, '2025-2026', 87.00, 100.00, 'adawdaadawdasd ', 'asfasfafas', '2025-11-17 03:46:30', '2025-11-17 03:46:30', '2025-11-21 14:31:53'),
(11, 3, 1, 6, 1, 'ww', 2, '2025-2026', 88.00, 100.00, 'Act 1', NULL, '2025-11-27 04:54:49', '2025-11-27 04:54:49', '2025-11-27 04:54:49'),
(12, 3, 1, 2, 1, 'ww', 1, '2025-2026', 88.00, 100.00, 'Act 4', NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(13, 3, 1, 2, 1, 'pt', 1, '2025-2026', 96.00, 100.00, 'Individual Project', NULL, '2025-11-27 16:30:12', '2025-11-27 16:30:12', '2025-11-27 16:30:12'),
(14, 3, 1, 6, 1, 'ww', 1, '2025-2026', 99.00, 100.00, NULL, NULL, '2025-11-27 18:12:43', '2025-11-27 18:12:43', '2025-11-27 18:12:43'),
(15, 3, 1, 6, 1, 'qe', 1, '2025-2026', 98.00, 100.00, NULL, NULL, '2025-11-27 18:15:01', '2025-11-27 18:15:01', '2025-11-27 18:15:01');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL COMMENT 'Recipient user ID',
  `type` enum('info','success','warning','error','grade','attendance','assignment','schedule','user','system') NOT NULL DEFAULT 'info',
  `category` enum('user_management','grade_update','assignment_new','assignment_due','attendance_alert','schedule_change','system_alert','approval_request','section_assignment','class_created','grade_submitted','attendance_marked','low_grade_alert') DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL COMMENT 'Optional link to related page',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data in JSON format',
  `created_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'User who triggered this notification',
  `expires_at` timestamp NULL DEFAULT NULL COMMENT 'Optional expiration date',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `category`, `title`, `message`, `icon`, `link`, `is_read`, `read_at`, `priority`, `metadata`, `created_by`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 21, 'success', 'user_management', 'Account Reactivated', 'Your account has been reactivated. You can now log in again.', 'fas fa-check-circle', '/login', 0, NULL, 'high', NULL, 1, NULL, '2025-11-27 05:58:01', '2025-11-27 05:58:01'),
(2, 1, 'info', 'user_management', 'User Reactivated', 'User Helen Santos (helen.santos@example.com) has been reactivated by System Administrator.', 'fas fa-info-circle', '/admin/users', 0, NULL, 'normal', NULL, 1, NULL, '2025-11-27 05:58:01', '2025-11-27 05:58:01'),
(3, 3, 'error', '', 'Academic Risk Alert: English', 'Student 谢以轩 is at risk in English. Current grade: 44.6. Reasons: Final grade (44.6) is below passing mark (75); Attendance (0%) is below threshold.', 'fas fa-times-circle', '/student/alerts', 0, NULL, 'high', '{\"alert_id\":1,\"student_id\":3,\"risk_level\":\"high\"}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(4, 3, 'error', '', 'Academic Risk Alert: Physical Education', 'Student 谢以轩 is at risk in Physical Education. Current grade: 43.5. Reasons: Final grade (43.5) is below passing mark (75); Attendance (0%) is below threshold.', 'fas fa-times-circle', '/student/alerts', 0, NULL, 'high', '{\"alert_id\":2,\"student_id\":3,\"risk_level\":\"high\"}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(5, 3, 'error', '', 'Overall Academic Risk Alert', 'Student 谢以轩 is at risk across multiple subjects. At-risk subjects: 2. Failing subjects: 2. Overall risk level: high.', 'fas fa-times-circle', '/student/alerts', 0, NULL, 'high', '{\"alert_id\":3,\"student_id\":3,\"risk_level\":\"high\"}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(6, 3, 'grade', 'grade_submitted', 'New Grade Posted', 'A new Written Work grade has been posted for English: 88/100 (88%)', 'fas fa-graduation-cap', '/student/grades?subject=2', 0, NULL, 'normal', '{\"grade_id\":12,\"subject_id\":2,\"subject_name\":\"English\",\"grade_type\":\"ww\",\"grade_type_label\":\"Written Work\",\"grade_value\":88,\"max_score\":100,\"percentage\":88,\"quarter\":1}', 2, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(7, 3, 'grade', 'grade_submitted', 'New Grade Posted', 'A new Performance Task grade has been posted for English: 96/100 (96%)', 'fas fa-graduation-cap', '/student/grades?subject=2', 0, NULL, 'normal', '{\"grade_id\":13,\"subject_id\":2,\"subject_name\":\"English\",\"grade_type\":\"pt\",\"grade_type_label\":\"Performance Task\",\"grade_value\":96,\"max_score\":100,\"percentage\":96,\"quarter\":1}', 2, NULL, '2025-11-27 16:30:13', '2025-11-27 16:30:13'),
(8, 3, 'error', '', 'Academic Risk Alert: Physical Education', 'Student 谢以轩 is at risk in Physical Education. Current grade: 26.4. Reasons: Final grade (26.4) is below passing mark (75); Attendance (0%) is below threshold; Performance is declining compared to previous quarter.', 'fas fa-times-circle', '/student/alerts', 0, NULL, 'high', '{\"alert_id\":4,\"student_id\":3,\"risk_level\":\"high\"}', NULL, NULL, '2025-11-27 16:31:14', '2025-11-27 16:31:14'),
(9, 3, 'grade', 'grade_submitted', 'New Grade Posted', 'A new Written Work grade has been posted for Physical Education: 99/100 (99%)', 'fas fa-graduation-cap', '/student/grades?subject=6', 0, NULL, 'normal', '{\"grade_id\":14,\"subject_id\":6,\"subject_name\":\"Physical Education\",\"grade_type\":\"ww\",\"grade_type_label\":\"Written Work\",\"grade_value\":99,\"max_score\":100,\"percentage\":99,\"quarter\":1}', 2, NULL, '2025-11-27 18:12:43', '2025-11-27 18:12:43'),
(10, 3, 'grade', 'grade_submitted', 'New Grade Posted', 'A new Quarterly Exam grade has been posted for Physical Education: 98/100 (98%)', 'fas fa-graduation-cap', '/student/grades?subject=6', 0, NULL, 'normal', '{\"grade_id\":15,\"subject_id\":6,\"subject_name\":\"Physical Education\",\"grade_type\":\"qe\",\"grade_type_label\":\"Quarterly Exam\",\"grade_value\":98,\"max_score\":100,\"percentage\":98,\"quarter\":1}', 2, NULL, '2025-11-27 18:15:01', '2025-11-27 18:15:01'),
(11, 25, 'success', 'user_management', 'Account Reactivated', 'Your account has been reactivated. You can now log in again.', 'fas fa-check-circle', '/login', 0, NULL, 'high', NULL, 1, NULL, '2025-11-28 14:31:04', '2025-11-28 14:31:04'),
(12, 1, 'info', 'user_management', 'User Reactivated', 'User mike lowe (mikelouieorbe@school.edu) has been reactivated by System Administrator.', 'fas fa-info-circle', '/admin/users', 0, NULL, 'normal', NULL, 1, NULL, '2025-11-28 14:31:04', '2025-11-28 14:31:04'),
(0, 24, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 8 - Section A. Schedule: M 7:00 AM-8:30 AM', 'fas fa-clock', '/teacher/classes?class=0', 0, NULL, 'high', '{\"class_id\":\"0\",\"subject\":\"English\"}', 1, NULL, '2025-12-01 14:11:36', '2025-12-01 14:11:36'),
(0, 4, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with Shin Da. Schedule: T 7:00 AM-9:00 AM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-01 14:28:51', '2025-12-01 14:28:51'),
(0, 4, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with Shin Da. Schedule: M 7:00 AM-9:00 AM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-01 14:29:43', '2025-12-01 14:29:43'),
(0, 2, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 10 - Section B. Schedule: S 7:00 AM-9:00 AM', 'fas fa-clock', '/teacher/classes?class=0', 0, NULL, 'high', '{\"class_id\":\"0\",\"subject\":\"English\"}', 1, NULL, '2025-12-01 14:55:42', '2025-12-01 14:55:42'),
(0, 24, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 10 - Section B. Schedule: M 7:00 AM-9:00 AM', 'fas fa-clock', '/teacher/classes?class=0', 0, NULL, 'high', '{\"class_id\":\"0\",\"subject\":\"English\"}', 1, NULL, '2025-12-01 14:57:33', '2025-12-01 14:57:33'),
(0, 25, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 10 - Section B. Schedule: M 7:00 AM-8:30 AM', 'fas fa-clock', '/teacher/classes?class=0', 0, NULL, 'high', '{\"class_id\":\"0\",\"subject\":\"English\"}', 1, NULL, '2025-12-01 15:11:58', '2025-12-01 15:11:58'),
(0, 24, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Computer Science for Grade 7 - Section A. Schedule: M 7:00 AM-7:30 AM', 'fas fa-clock', '/teacher/classes?class=21', 0, NULL, 'high', '{\"class_id\":\"21\",\"subject\":\"Computer Science\"}', 1, NULL, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with JEFF. Schedule: M 7:00 AM-7:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with JEFF. Schedule: M 7:00 AM-7:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with JEFF. Schedule: M 7:00 AM-7:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: Computer Science with JEFF. Schedule: M 7:00 AM-7:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(0, 24, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 7 - Section A. Schedule: M 7:30 AM-8:30 AM', 'fas fa-clock', '/teacher/classes?class=22', 0, NULL, 'high', '{\"class_id\":\"22\",\"subject\":\"English\"}', 1, NULL, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: M 7:30 AM-8:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: M 7:30 AM-8:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: M 7:30 AM-8:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: M 7:30 AM-8:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(0, 25, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Filipino for Grade 7 - Section A. Schedule: M 9:00 AM-10:30 AM', 'fas fa-clock', '/teacher/classes?class=23', 0, NULL, 'high', '{\"class_id\":\"23\",\"subject\":\"Filipino\"}', 1, NULL, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: Filipino with mike lowe. Schedule: M 9:00 AM-10:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: Filipino with mike lowe. Schedule: M 9:00 AM-10:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: Filipino with mike lowe. Schedule: M 9:00 AM-10:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: Filipino with mike lowe. Schedule: M 9:00 AM-10:30 AM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(0, 44, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Mathematics for Grade 7 - Section A. Schedule: M 12:00 PM-2:00 PM', 'fas fa-clock', '/teacher/classes?class=24', 0, NULL, 'high', '{\"class_id\":\"24\",\"subject\":\"Mathematics\"}', 1, NULL, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: Mathematics with luwiii. Schedule: M 12:00 PM-2:00 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: Mathematics with luwiii. Schedule: M 12:00 PM-2:00 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: Mathematics with luwiii. Schedule: M 12:00 PM-2:00 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: Mathematics with luwiii. Schedule: M 12:00 PM-2:00 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(0, 24, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach English for Grade 12 - Section B. Schedule: W 7:00 AM-8:30 AM', 'fas fa-clock', '/teacher/classes?class=25', 0, NULL, 'high', '{\"class_id\":\"25\",\"subject\":\"English\"}', 1, NULL, '2025-12-11 14:00:53', '2025-12-11 14:00:53'),
(0, 19, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: W 7:00 AM-8:30 AM, Room: 319', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-11 14:00:53', '2025-12-11 14:00:53'),
(0, 20, 'schedule', 'class_created', 'New Class Added', 'New class: English with JEFF. Schedule: W 7:00 AM-8:30 AM, Room: 319', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-11 14:00:53', '2025-12-11 14:00:53'),
(0, 45, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Science for Grade 8 - Section A. Schedule: T 7:00 AM-8:00 AM', 'fas fa-clock', '/teacher/classes?class=26', 0, NULL, 'high', '{\"class_id\":\"26\",\"subject\":\"Science\"}', 1, NULL, '2025-12-11 15:33:33', '2025-12-11 15:33:33'),
(0, 44, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Social Studies for Grade 7 - Section A. Schedule: T 3:30 PM-4:30 PM', 'fas fa-clock', '/teacher/classes?class=27', 0, NULL, 'high', '{\"class_id\":\"27\",\"subject\":\"Social Studies\"}', 1, NULL, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: Social Studies with luwiii. Schedule: T 3:30 PM-4:30 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: Social Studies with luwiii. Schedule: T 3:30 PM-4:30 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: Social Studies with luwiii. Schedule: T 3:30 PM-4:30 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: Social Studies with luwiii. Schedule: T 3:30 PM-4:30 PM, Room: 315', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(0, 48, 'schedule', 'class_created', 'New Class Assignment', 'You have been assigned to teach Science for Grade 7 - Section A. Schedule: W 1:00 PM-1:30 PM', 'fas fa-clock', '/teacher/classes?class=28', 0, NULL, 'high', '{\"class_id\":\"28\",\"subject\":\"Science\"}', 1, NULL, '2025-12-15 01:01:52', '2025-12-15 01:01:52'),
(0, 2, 'schedule', 'class_created', 'New Class Added', 'New class: Science with bryle. Schedule: W 1:00 PM-1:30 PM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-15 01:01:52', '2025-12-15 01:01:52'),
(0, 3, 'schedule', 'class_created', 'New Class Added', 'New class: Science with bryle. Schedule: W 1:00 PM-1:30 PM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-15 01:01:52', '2025-12-15 01:01:52'),
(0, 5, 'schedule', 'class_created', 'New Class Added', 'New class: Science with bryle. Schedule: W 1:00 PM-1:30 PM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-15 01:01:52', '2025-12-15 01:01:52'),
(0, 6, 'schedule', 'class_created', 'New Class Added', 'New class: Science with bryle. Schedule: W 1:00 PM-1:30 PM, Room: 232', 'fas fa-clock', '/student/schedule', 0, NULL, 'high', NULL, 1, NULL, '2025-12-15 01:01:52', '2025-12-15 01:01:52'),
(0, 48, 'success', 'section_assignment', 'Adviser Assignment', 'You have been assigned as the adviser for Grade 7 - Section B.', 'fas fa-check-circle', '/teacher/sections', 0, NULL, 'normal', '{\"section_id\":2,\"section_name\":\"Grade 7 - Section B\"}', 1, NULL, '2025-12-15 02:16:48', '2025-12-15 02:16:48'),
(0, 7, 'info', 'section_assignment', 'New Section Adviser', 'bryle is now your section adviser for Grade 7 - Section B.', 'fas fa-info-circle', '/student/dashboard', 0, NULL, 'normal', NULL, 1, NULL, '2025-12-15 02:16:48', '2025-12-15 02:16:48'),
(0, 48, 'info', 'section_assignment', 'New Section Adviser', 'bryle is now your section adviser for Grade 7 - Section B.', 'fas fa-info-circle', '/student/dashboard', 0, NULL, 'normal', NULL, 1, NULL, '2025-12-15 02:16:48', '2025-12-15 02:16:48');

-- --------------------------------------------------------

--
-- Table structure for table `performance_alerts`
--

CREATE TABLE `performance_alerts` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `teacher_id` int(10) UNSIGNED NOT NULL COMMENT 'User ID of teacher/adviser',
  `section_id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL for overall/attendance alerts',
  `alert_type` enum('academic_risk','overall_risk','attendance','grade_drop','trend_declining') NOT NULL DEFAULT 'academic_risk',
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `status` enum('active','resolved','dismissed') NOT NULL DEFAULT 'active',
  `quarter` tinyint(3) UNSIGNED NOT NULL COMMENT '1=1st, 2=2nd, 3=3rd, 4=4th',
  `academic_year` varchar(20) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional data (risk_score, reasons, etc.)' CHECK (json_valid(`metadata`)),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED DEFAULT NULL COMMENT 'User ID who resolved the alert',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI-generated performance alerts for at-risk students';

--
-- Dumping data for table `performance_alerts`
--

INSERT INTO `performance_alerts` (`id`, `student_id`, `teacher_id`, `section_id`, `subject_id`, `alert_type`, `title`, `description`, `severity`, `status`, `quarter`, `academic_year`, `metadata`, `resolved_at`, `resolved_by`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 1, 2, 'academic_risk', 'Academic Risk Alert: English', 'Student 谢以轩 is at risk in English. Current grade: 44.6. Reasons: Final grade (44.6) is below passing mark (75); Attendance (0%) is below threshold.', 'high', 'active', 1, '2025-2026', '{\"risk_level\":\"high\",\"risk_score\":80,\"final_grade\":44.6,\"reasons\":[\"Final grade (44.6) is below passing mark (75)\",\"Attendance (0%) is below threshold\"],\"trend\":\"unknown\"}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(2, 3, 2, 1, 6, 'academic_risk', 'Academic Risk Alert: Physical Education', 'Student 谢以轩 is at risk in Physical Education. Current grade: 43.5. Reasons: Final grade (43.5) is below passing mark (75); Attendance (0%) is below threshold.', 'high', 'active', 1, '2025-2026', '{\"risk_level\":\"high\",\"risk_score\":80,\"final_grade\":43.5,\"reasons\":[\"Final grade (43.5) is below passing mark (75)\",\"Attendance (0%) is below threshold\"],\"trend\":\"unknown\"}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(3, 3, 2, 1, NULL, 'overall_risk', 'Overall Academic Risk Alert', 'Student 谢以轩 is at risk across multiple subjects. At-risk subjects: 2. Failing subjects: 2. Overall risk level: high.', 'high', 'active', 1, '2025-2026', '{\"risk_level\":\"high\",\"risk_score\":62.33,\"at_risk_subjects_count\":2,\"failing_subjects_count\":2}', NULL, NULL, '2025-11-27 16:02:22', '2025-11-27 16:02:22'),
(4, 3, 2, 1, 6, 'academic_risk', 'Academic Risk Alert: Physical Education', 'Student 谢以轩 is at risk in Physical Education. Current grade: 26.4. Reasons: Final grade (26.4) is below passing mark (75); Attendance (0%) is below threshold; Performance is declining compared to previous quarter.', 'high', 'active', 2, '2025-2026', '{\"risk_level\":\"high\",\"risk_score\":80,\"final_grade\":26.4,\"reasons\":[\"Final grade (26.4) is below passing mark (75)\",\"Attendance (0%) is below threshold\",\"Performance is declining compared to previous quarter\"],\"trend\":\"declining\"}', NULL, NULL, '2025-11-27 16:31:14', '2025-11-27 16:31:14');

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
(1, 'Grade 7 - Section A', 7, 'Room 101', 45, '2025-2026', 2, 'Main Grade 7 section', 1, '2025-10-18 08:58:39'),
(2, 'Grade 7 - Section B', 7, 'Room 102', 30, '2025-2026', 48, 'Grade 7 Section B', 1, '2025-10-24 06:46:10'),
(3, 'Grade 8 - Section A', 8, 'Room 201', 40, '2025-2026', NULL, 'Grade 8 Section A', 1, '2025-10-24 06:46:10'),
(4, 'Grade 8 - Section B', 8, 'Room 202', 40, '2025-2026', NULL, 'Grade 8 Section B', 1, '2025-10-24 06:46:10'),
(5, 'Grade 9 - Section A', 9, 'Room 301', 40, '2025-2026', NULL, 'Grade 9 Section A', 1, '2025-10-24 06:46:10'),
(6, 'Grade 9 - Section B', 9, 'Room 302', 40, '2025-2026', NULL, 'Grade 9 Section B', 1, '2025-10-24 06:46:10'),
(7, 'Grade 10 - Section A', 10, 'Room 401', 40, '2025-2026', NULL, 'Grade 10 Section A', 1, '2025-10-24 06:46:10'),
(8, 'Grade 10 - Section B', 10, 'Room 402', 40, '2025-2026', NULL, 'Grade 10 Section B', 1, '2025-10-24 06:46:10'),
(9, 'Grade 11 - Section A', 11, 'Room 501', 40, '2025-2026', NULL, 'Grade 11 Section A', 1, '2025-10-24 06:46:10'),
(10, 'Grade 11 - Section B', 11, 'Room 502', 40, '2025-2026', NULL, 'Grade 11 Section B', 1, '2025-10-24 06:46:10'),
(11, 'Grade 12 - Section A', 12, 'Room 601', 40, '2025-2026', NULL, 'Grade 12 Section A', 1, '2025-10-24 06:46:10'),
(12, 'Grade 12 - Section B', 12, 'Room 602', 40, '2025-2026', NULL, 'Grade 12 Section B', 1, '2025-10-24 06:46:10'),
(13, 'myles', 1, '315', 20, '2025-2026', NULL, 'dfsvs', 1, '2025-12-15 04:38:11');

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
(3, 3, 'LRN000008', '以轩', '谢', NULL, NULL, NULL, NULL, NULL, 'Maria', '+639946312133', 'sibling', 7, 1, '2025-2026', 'enrolled', NULL, NULL, NULL, NULL, NULL, NULL, '/assets/profile_pictures/student_8_1761308860_9c9918cf381de7c0.jpg', NULL, NULL, NULL, 'enrolled', '2025-10-24 05:20:26', '2025-11-22 10:52:52'),
(4, 4, '1234234354', 'ariane', 'martirez', 'balani', '2006-12-16', 'female', '12343252452', 'tunasan', 'balanii', '23545422`', 'mother', 12, 11, '2025-2026', 'transferred', '', 'vdavadva', 'vbbdasva', 'dvbsbgsfge', '14654+645', 'grandfather', NULL, '', NULL, NULL, 'enrolled', '2025-11-16 13:38:33', '2025-11-21 13:30:33'),
(5, 5, '', 'Andrei', 'Garcia', 'Datoon', '2004-02-01', 'male', '+6391234567', 'ORDER ID TEST', 'jeff', '', 'other', 7, 1, '2025-2026', 'enrolled', 'Landayan', '', '', 'Jeff Mathew Datoon Garcia', '+6391234567', 'other', NULL, '', NULL, NULL, 'enrolled', '2025-10-24 06:52:07', '2025-11-21 13:31:17'),
(6, 6, '1234565412', 'aliyah', 'saragina', 'alolor', '2013-07-06', 'female', '131654654', 'gvjhfjhyjhl', 'dthdtdt', 'hgdytdty', 'grandfather', 7, 1, '2025-2026', 'enrolled', '', ' m vhbv ', 'GHCDHGH', 'gcghcgh', 'cghcfhghg', 'father', NULL, 'SGBDSB', NULL, NULL, 'enrolled', '2025-11-01 04:01:56', '2025-11-21 13:31:30'),
(7, 7, '108423080169', 'Juan', 'Delacruz', 'D', '2025-11-04', 'male', '+6309765231', 'dsadda', 'dad', 'dasdada', 'grandfather', 7, 2, '2025-2026', 'enrolled', 'aadadadsawda', 'Admin!is-me04', 'Admin!is-me04', 'adasda', 'admin@example.com', 'guardian', NULL, 'Admin!is-me04', NULL, NULL, 'enrolled', '2025-11-07 13:00:16', '2025-11-21 13:31:36'),
(17, 19, '108423080973', 'john', 'dimagiba', 'doe', '2020-10-07', 'male', '+6391234567', 'testt', 'dawda', 'adwa', 'grandmother', 12, 12, '2025-2026', 'enrolled', 'dwda', 'General!password-1', 'General!password-1', 'Shin Da', '+6391234567', 'uncle', NULL, 'General!password-1', NULL, NULL, 'enrolled', '2025-11-21 13:45:00', '2025-11-21 13:45:00'),
(18, 20, '109834572301', 'Angela', 'Santos', 'Mae', '2006-01-21', 'female', '+6391234567', '', 'dawda', 'adwa', 'grandfather', 12, 12, '2025-2026', 'enrolled', 'PLMUN', 'General!password-1\r\n', 'General!password-1\r\n', 'Shin Da', '+6391234567', 'grandfather', NULL, 'General!password-1', NULL, NULL, 'enrolled', '2025-11-21 14:08:34', '2025-11-21 14:08:34');

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
(1, 2, 'EMP002', 'General', NULL, 1, NULL, '2025-10-18 08:58:39'),
(2, 25, '3', 'history', 'history', 0, '2025-12-01', '2025-12-01 14:13:13'),
(3, 44, 'EMP0111', 'math', 'math', 0, '2025-12-01', '2025-12-08 07:49:30'),
(0, 45, 'EMP-02', 'history', 'history', 0, '2025-12-01', '2025-12-11 13:57:11'),
(0, 47, '345', 'science', 'Science', 0, '2025-12-01', '2025-12-11 15:31:41'),
(4, 48, 'EMP-143', 'arts', 'Arts', 1, '2025-11-30', '2025-12-15 01:00:24');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_schedules`
--

CREATE TABLE `teacher_schedules` (
  `id` int(11) NOT NULL,
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
(20, 0, 'Monday', '07:00:00', '07:30:00', 21, '2025-12-08 05:35:25', '2025-12-08 05:35:25'),
(21, 0, 'Monday', '07:30:00', '08:30:00', 22, '2025-12-08 05:35:58', '2025-12-08 05:35:58'),
(22, 2, 'Monday', '09:00:00', '10:30:00', 23, '2025-12-08 05:46:03', '2025-12-08 05:46:03'),
(23, 3, 'Monday', '12:00:00', '14:00:00', 24, '2025-12-08 07:53:39', '2025-12-08 07:53:39'),
(24, 0, 'Wednesday', '07:00:00', '08:30:00', 25, '2025-12-11 14:00:53', '2025-12-11 14:00:53'),
(25, 0, 'Tuesday', '07:00:00', '08:00:00', 26, '2025-12-11 15:33:33', '2025-12-11 15:33:33'),
(26, 3, 'Tuesday', '15:30:00', '16:30:00', 27, '2025-12-14 14:45:59', '2025-12-14 14:45:59'),
(27, 4, 'Wednesday', '13:00:00', '13:30:00', 28, '2025-12-15 01:01:52', '2025-12-15 01:01:52');

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
  `approved_at` timestamp NULL DEFAULT NULL,
  `linked_student_user_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_relationship` enum('father','mother','guardian','grandparent','other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `email`, `password_hash`, `name`, `phone`, `address`, `created_at`, `updated_at`, `status`, `requested_role`, `approved_by`, `approved_at`, `linked_student_user_id`, `parent_relationship`) VALUES
(1, 'admin', 'admin@school.edu', '$2y$10$kcVb2JJm.eD.91F2YFtiu.xJKCD.bZJV5n5CUB5ukf1yHcFeRBlZ6', 'System Administrator', NULL, NULL, '2025-10-16 19:37:51', '2025-10-16 14:21:19', 'active', NULL, NULL, NULL, NULL, NULL),
(2, 'teacher', 'teacher@gmail.com', '$2y$10$2X.vGcyfr7drEFSj5P5BZOQwF7bQfFF.CFLHKohz5GvYh7v7WAkhe', 'Shin Da', NULL, NULL, '2025-10-16 19:56:17', '2025-11-07 15:10:16', 'active', NULL, NULL, NULL, NULL, NULL),
(3, 'student', 'jeffstudent04@gmail.com', '$2y$10$8Ek3HSxaJV2PUhtHrvXK1.wiD011oYSEUzx4Q8aCxcydkZ3MaMpQ2', '谢以轩', NULL, NULL, '2025-10-24 05:19:52', '2025-11-21 12:43:59', 'active', 'student', 1, '2025-10-24 05:20:26', NULL, NULL),
(4, 'student', 'arianemartirez@gmail.com', '$2y$10$AFyeu02x1paFSmVhcaF81eUgaRQtsiYz.d66cB4xXF7N7R4uUw/cW', 'ariane balani martirez', NULL, NULL, '2025-11-16 13:38:33', '2025-11-21 13:30:50', 'active', NULL, 1, '2025-11-16 13:38:33', NULL, NULL),
(5, 'student', 'andrei@gmail.com', '$2y$10$f4iCQ9fJeEQwz22Va4q9uut/LGg99aXXKBXSEyUHm0JA8A.65wvj2', 'Andrei Datoon Garcia', NULL, NULL, '2025-10-24 06:52:07', '2025-11-21 13:31:10', 'active', NULL, 1, '2025-10-24 06:52:07', NULL, NULL),
(6, 'student', 'aliya@gmail.com', '$2y$10$yWGe8BOlyiul3aCx2XTT9eDsxiMpF3DFb6NUFDS0p6ad3oxsIlcu.', 'aliyah alolor saragina', NULL, NULL, '2025-11-01 04:01:56', '2025-11-21 13:31:38', 'active', NULL, 1, '2025-11-01 04:01:56', NULL, NULL),
(7, 'student', 'jeffstudent04dddd@gmail.com', '$2y$10$QUSaDxWHV1M2po4HC8nsjOGoDSAxCuHN3M2WBRkcdKU0GuumDYHKG', 'Juan D Delacruz', NULL, NULL, '2025-11-07 13:00:16', '2025-11-21 13:31:48', 'active', NULL, 1, '2025-11-07 13:00:16', NULL, NULL),
(19, 'student', 'jddmagiba01@gmail.com', '$2y$10$YB7KYSqVQiMIj3ZfQSV9HupFMWX2BLRRCt6oY4g0CBn1CXgKMPO7e', 'john doe dimagiba', NULL, NULL, '2025-11-21 13:45:00', '2025-11-21 13:45:00', 'active', NULL, 1, '2025-11-21 13:45:00', NULL, NULL),
(20, 'student', 'angela.santos@example.com', '$2y$10$6NjsjQMyKJZNYR14YvRYJu4nDy8iuHUecTT9fb5o/6aHYf3oaAYk2', 'Angela Mae Santos', NULL, NULL, '2025-11-21 14:08:34', '2025-11-21 14:08:34', 'active', NULL, 1, '2025-11-21 14:08:34', NULL, NULL),
(21, 'parent', 'helen.santos@example.com', '$2y$10$CmvXFnAhFWTGP6L5sBcy6.2TfTUKJOrgLZYGhSAIMQU0tdzLeEkdy', 'Helen Santos', NULL, NULL, '2025-11-22 10:06:31', '2025-11-27 05:58:01', 'active', NULL, 1, '2025-11-22 10:06:31', 3, 'mother'),
(25, 'teacher', 'mikelouieorbe@school.edu', '$2y$10$OCW..eyBCr5r9s9j2EJoo.p6ROx1hdmBRJVQDU9ET2Y5ERD9bxz.m', 'mike lowe', NULL, NULL, '2025-11-28 14:30:23', '2025-11-28 14:31:04', 'active', NULL, NULL, NULL, NULL, NULL),
(26, 'teacher', 'teacher3@gmail.com', '$2y$10$9kBsbHeuAx94F1q6eXmEl.zYbp9NTrVcr7e56E.wZFVJGMjINw11a', 'teacher', NULL, NULL, '2025-12-01 14:13:13', '2025-12-01 14:13:13', 'active', NULL, NULL, NULL, NULL, NULL),
(27, 'teacher', 'jeffteacher@gmail.com', '$2y$10$JA6y4c3yRgabD55qzPaaYeHgh3p30GIEKJcBCR1zZrwYcNK05Rzwe', 'jeff', NULL, NULL, '2025-12-01 14:56:53', '2025-12-01 14:56:53', 'active', NULL, NULL, NULL, NULL, NULL),
(28, 'teacher', 'km@gmail.com', '$2y$10$Y0tmjausYvpZIGe8Rh31GOrErSF6IQru9xefYMpN.qOK0Ve9jmkVO', 'mylees', NULL, NULL, '2025-12-01 15:10:31', '2025-12-01 15:10:31', 'active', NULL, NULL, NULL, NULL, NULL),
(29, 'adviser', 'luwii@gmail.com', '$2y$10$UP9kTr6PHrWzfKVb8voqxuGfpwZGouJn.kPsjmd.iM1iITM1CNcLa', 'luwii', NULL, NULL, '2025-12-01 15:13:33', '2025-12-01 15:13:33', 'active', NULL, NULL, NULL, NULL, NULL),
(30, 'teacher', 'jeffgarcia1@gmail.com', '$2y$10$14TWqlok00ym.4DsvbsuK.oe71jf3LgVSHYlfpyDqsiZm77EiQgku', 'jeff', NULL, NULL, '2025-12-01 15:14:23', '2025-12-01 15:14:23', 'active', NULL, NULL, NULL, NULL, NULL),
(31, 'teacher', 'john@gmail.com', '$2y$10$9OiEv78XPe11UzOerhFb5eanz0gfiDgRpcYGaVPdsyiOpMPnopbZC', 'john doe', NULL, NULL, '2025-12-01 15:18:27', '2025-12-01 15:18:27', 'active', NULL, NULL, NULL, NULL, NULL),
(44, 'teacher', 'luwiii@teacher.edu.ph', '$2y$10$vKOZl2RGDlwGyLpOgH.1gOwbhXSI.nal4Mzb/pVFXHnEM3MZS.NYi', 'luwiii', NULL, NULL, '2025-12-08 07:49:30', '2025-12-08 07:49:30', 'active', NULL, NULL, NULL, NULL, NULL),
(45, 'teacher', 'myleteacher@school.edu', '$2y$10$e1p6CSz3mfbZRr4OGfEP4.yOEra3VKFKyeUP/1CDU9j/iQeSLysna', 'myles', NULL, NULL, '2025-12-11 13:57:11', '2025-12-11 13:57:11', 'active', NULL, NULL, NULL, NULL, NULL),
(46, 'teacher', 'jeffgarcia67@gmail.com', '$2y$10$DItd0VqEmoy3uMdtsBwig.udtG23atu1qQUem4hAfkbdION6LDFJq', 'jeff', NULL, NULL, '2025-12-11 14:12:54', '2025-12-11 14:12:54', 'active', NULL, NULL, NULL, NULL, NULL),
(47, 'teacher', 'mackyteacher@school.ph', '$2y$10$CixTQyOO/rfWNV8pabCnQOAdNuzLuf.apijvCrILdBBdieZuUGHr6', 'macky', NULL, NULL, '2025-12-11 15:31:41', '2025-12-11 15:31:41', 'active', NULL, NULL, NULL, NULL, NULL),
(48, 'adviser', 'bryle1@gmail.com', '$2y$10$wA.cxHTetteKoMvAD/DZw./P.Jua6DjXDyjKCgNMpvDbTvacXpG86', 'bryle', NULL, NULL, '2025-12-15 01:00:24', '2025-12-15 02:16:48', 'active', NULL, NULL, NULL, NULL, NULL);

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `final_grades_view`  AS SELECT `qg`.`student_id` AS `student_id`, `qg`.`section_id` AS `section_id`, `qg`.`subject_id` AS `subject_id`, `qg`.`quarter` AS `quarter`, `qg`.`academic_year` AS `academic_year`, `qg`.`ww_average` AS `ww_average`, `qg`.`pt_average` AS `pt_average`, `qg`.`qe_average` AS `qe_average`, `s`.`ww_percent` AS `ww_percent`, `s`.`pt_percent` AS `pt_percent`, `s`.`qe_percent` AS `qe_percent`, `s`.`attendance_percent` AS `attendance_percent`, round(coalesce(`qg`.`ww_average`,0) * coalesce(`s`.`ww_percent`,20) / 100 + coalesce(`qg`.`pt_average`,0) * coalesce(`s`.`pt_percent`,50) / 100 + coalesce(`qg`.`qe_average`,0) * coalesce(`s`.`qe_percent`,20) / 100,2) AS `final_grade_without_attendance`, CASE WHEN round(coalesce(`qg`.`ww_average`,0) * coalesce(`s`.`ww_percent`,20) / 100 + coalesce(`qg`.`pt_average`,0) * coalesce(`s`.`pt_percent`,50) / 100 + coalesce(`qg`.`qe_average`,0) * coalesce(`s`.`qe_percent`,20) / 100,2) >= 75 THEN 'Passed' ELSE 'Failed' END AS `status_without_attendance` FROM (`quarterly_grades_view` `qg` join `subjects` `s` on(`qg`.`subject_id` = `s`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `student_profiles`
--
DROP TABLE IF EXISTS `student_profiles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_profiles`  AS SELECT `s`.`id` AS `student_id`, `s`.`user_id` AS `user_id`, `u`.`email` AS `email`, `u`.`name` AS `full_name`, `u`.`status` AS `user_status`, `u`.`created_at` AS `user_created_at`, `u`.`updated_at` AS `user_updated_at`, `s`.`lrn` AS `lrn`, `s`.`first_name` AS `first_name`, `s`.`last_name` AS `last_name`, `s`.`middle_name` AS `middle_name`, concat(coalesce(`s`.`first_name`,''),case when `s`.`middle_name` is not null and `s`.`middle_name` <> '' then concat(' ',`s`.`middle_name`) else '' end,case when `s`.`last_name` is not null and `s`.`last_name` <> '' then concat(' ',`s`.`last_name`) else '' end) AS `full_name_display`, `s`.`birth_date` AS `birth_date`, `s`.`gender` AS `gender`, `s`.`contact_number` AS `contact_number`, `s`.`address` AS `address`, `s`.`guardian_name` AS `guardian_name`, `s`.`guardian_contact` AS `guardian_contact`, `s`.`guardian_relationship` AS `guardian_relationship`, `s`.`grade_level` AS `grade_level`, `s`.`section_id` AS `section_id`, `sec`.`name` AS `section_name`, `sec`.`room` AS `section_room`, `s`.`school_year` AS `school_year`, `s`.`enrollment_status` AS `enrollment_status`, `s`.`previous_school` AS `previous_school`, `s`.`medical_conditions` AS `medical_conditions`, `s`.`allergies` AS `allergies`, `s`.`emergency_contact_name` AS `emergency_contact_name`, `s`.`emergency_contact_number` AS `emergency_contact_number`, `s`.`emergency_contact_relationship` AS `emergency_contact_relationship`, `s`.`profile_picture` AS `profile_picture`, `s`.`notes` AS `notes`, `s`.`date_enrolled` AS `date_enrolled`, `s`.`date_graduated` AS `date_graduated`, `s`.`status` AS `student_status`, `s`.`created_at` AS `created_at`, `s`.`updated_at` AS `updated_at` FROM ((`students` `s` left join `users` `u` on(`s`.`user_id` = `u`.`id`)) left join `sections` `sec` on(`s`.`section_id` = `sec`.`id`)) WHERE `u`.`role` = 'student\'student\'student\'student\'student\'student\'student\'student' ;

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
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_target` (`target_type`,`target_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `teacher_schedules`
--
ALTER TABLE `teacher_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
