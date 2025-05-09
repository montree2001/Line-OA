-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 10, 2025 at 04:17 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stp_prasat`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `academic_year_id` int(11) NOT NULL,
  `year` int(11) NOT NULL COMMENT 'ปีการศึกษา',
  `semester` tinyint(1) NOT NULL COMMENT 'ภาคเรียน (1 หรือ 2)',
  `start_date` date NOT NULL COMMENT 'วันเริ่มต้นปีการศึกษา',
  `end_date` date NOT NULL COMMENT 'วันสิ้นสุดปีการศึกษา',
  `required_attendance_days` int(11) NOT NULL DEFAULT 80 COMMENT 'จำนวนวันที่ต้องเข้าแถวเพื่อผ่านกิจกรรม',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'เป็นปีการศึกษาปัจจุบันหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`academic_year_id`, `year`, `semester`, `start_date`, `end_date`, `required_attendance_days`, `is_active`, `created_at`) VALUES
(1, 2568, 1, '2025-05-13', '2026-03-14', 80, 1, '2025-03-27 12:59:26');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `activity_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL COMMENT 'ชื่อกิจกรรม',
  `activity_date` date NOT NULL COMMENT 'วันที่จัดกิจกรรม',
  `activity_location` varchar(255) DEFAULT NULL COMMENT 'สถานที่จัดกิจกรรม',
  `description` text DEFAULT NULL COMMENT 'รายละเอียดกิจกรรม',
  `required_attendance` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'เป็นกิจกรรมบังคับหรือไม่',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `created_by` int(11) NOT NULL COMMENT 'ผู้สร้างกิจกรรม',
  `updated_by` int(11) DEFAULT NULL COMMENT 'ผู้แก้ไขกิจกรรมล่าสุด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT 'วันที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`activity_id`, `activity_name`, `activity_date`, `activity_location`, `description`, `required_attendance`, `academic_year_id`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 'กิจกรรมปฐมนิเทศนักศึกษาใหม่', '2025-05-08', 'หอประชุมวิทยาลัยการอาชีพปราสาท', '', 1, 1, 109, NULL, '2025-05-10 14:13:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_attendance`
--

CREATE TABLE `activity_attendance` (
  `attendance_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL COMMENT 'รหัสกิจกรรม',
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `attendance_status` enum('present','absent') NOT NULL DEFAULT 'absent' COMMENT 'สถานะการเข้าร่วม',
  `recorder_id` int(11) NOT NULL COMMENT 'ผู้บันทึก',
  `record_time` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'เวลาที่บันทึก',
  `remarks` text DEFAULT NULL COMMENT 'หมายเหตุ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_attendance`
--

INSERT INTO `activity_attendance` (`attendance_id`, `activity_id`, `student_id`, `attendance_status`, `recorder_id`, `record_time`, `remarks`) VALUES
(1, 2, 41, 'absent', 109, '2025-05-10 14:16:06', ''),
(2, 2, 48, 'absent', 109, '2025-05-10 14:16:06', ''),
(3, 2, 50, 'absent', 109, '2025-05-10 14:16:06', ''),
(4, 2, 51, 'absent', 109, '2025-05-10 14:16:06', ''),
(5, 2, 52, 'absent', 109, '2025-05-10 14:16:06', '');

-- --------------------------------------------------------

--
-- Table structure for table `activity_target_departments`
--

CREATE TABLE `activity_target_departments` (
  `activity_id` int(11) NOT NULL COMMENT 'รหัสกิจกรรม',
  `department_id` int(11) NOT NULL COMMENT 'รหัสแผนกวิชา'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_target_levels`
--

CREATE TABLE `activity_target_levels` (
  `activity_id` int(11) NOT NULL COMMENT 'รหัสกิจกรรม',
  `level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้น'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `additional_locations`
--

CREATE TABLE `additional_locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'ชื่อสถานที่',
  `radius` int(11) NOT NULL DEFAULT 100 COMMENT 'รัศมีที่อนุญาต (เมตร)',
  `latitude` decimal(10,7) NOT NULL COMMENT 'ละติจูด',
  `longitude` decimal(10,7) NOT NULL COMMENT 'ลองจิจูด',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `additional_locations`
--

INSERT INTO `additional_locations` (`id`, `name`, `radius`, `latitude`, `longitude`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'สนามกีฬา', 100, 14.9523000, 103.4919000, 1, '2025-03-30 07:32:47', '2025-03-30 07:32:47');

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL COMMENT 'รหัสผู้ดูแลระบบ',
  `action_type` enum('add_student','remove_student','assign_teacher','update_student_status','create_academic_year','promote_students','add_department','edit_department','remove_department','add_class','edit_class','remove_class','manage_advisors','create_activity','edit_activity','delete_activity','record_activity_attendance') NOT NULL COMMENT 'ประเภทการดำเนินการ',
  `action_details` text DEFAULT NULL COMMENT 'รายละเอียดการดำเนินการ',
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_actions`
--

INSERT INTO `admin_actions` (`action_id`, `admin_id`, `action_type`, `action_details`, `action_date`) VALUES
(52, 109, 'add_student', 'hi', '2025-05-03 15:17:00'),
(54, 109, 'manage_advisors', '{\"class_id\":\"16\",\"changes\":[{\"action\":\"add\",\"teacher_id\":\"5\",\"is_primary\":true}]}', '2025-05-03 15:19:41'),
(55, 109, 'add_department', '{\"department_id\":\"12\",\"department_code\":\"TEST\",\"department_name\":\"\\u0e40\\u0e17\\u0e04\\u0e42\\u0e19\\u0e42\\u0e25\\u0e22\\u0e35\\u0e2a\\u0e32\\u0e23\\u0e2a\\u0e19\\u0e40\\u0e17\\u0e282\"}', '2025-05-03 15:36:45'),
(56, 109, 'remove_department', '{\"department_id\":\"12\",\"department_code\":\"TEST\",\"department_name\":\"\\u0e40\\u0e17\\u0e04\\u0e42\\u0e19\\u0e42\\u0e25\\u0e22\\u0e35\\u0e2a\\u0e32\\u0e23\\u0e2a\\u0e19\\u0e40\\u0e17\\u0e282\"}', '2025-05-03 15:59:27'),
(57, 109, 'remove_department', '{\"department_id\":\"9\",\"department_code\":\"ADMIN\",\"department_name\":\"\\u0e1a\\u0e23\\u0e34\\u0e2b\\u0e32\\u0e23\"}', '2025-05-03 16:08:30'),
(58, 109, 'manage_advisors', '{\"class_id\":\"12\",\"changes\":[{\"action\":\"add\",\"teacher_id\":\"6\",\"is_primary\":true}]}', '2025-05-04 05:42:14'),
(59, 109, 'manage_advisors', '{\"class_id\":\"12\",\"changes\":[{\"action\":\"add\",\"teacher_id\":\"7\",\"is_primary\":true}]}', '2025-05-04 05:48:02'),
(60, 109, 'manage_advisors', '{\"class_id\":\"14\",\"changes\":[{\"action\":\"add\",\"teacher_id\":\"7\",\"is_primary\":true}]}', '2025-05-05 15:45:28'),
(61, 109, 'add_department', '{\"department_id\":\"13\",\"department_code\":\"TEST\",\"department_name\":\"\\u0e40\\u0e17\\u0e04\\u0e42\\u0e19\\u0e42\\u0e25\\u0e22\\u0e35\\u0e2a\\u0e32\\u0e23\\u0e2a\\u0e19\\u0e40\\u0e17\\u0e282\"}', '2025-05-06 17:07:48'),
(62, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":5,\"method\":\"manual\"}', '2025-05-09 06:32:47'),
(63, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 07:05:18'),
(64, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 07:06:32'),
(65, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:53:36'),
(66, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:55:05'),
(67, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:55:30'),
(68, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:55:39'),
(69, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:55:40'),
(70, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-09\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 10:55:41'),
(71, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-08\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 13:53:39'),
(72, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-08\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 13:55:44'),
(73, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-08\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-09 13:55:49'),
(74, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-10\",\"student_count\":5,\"method\":\"manual\"}', '2025-05-10 09:03:10'),
(75, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-10\",\"student_count\":5,\"method\":\"manual\"}', '2025-05-10 12:07:21'),
(76, 109, 'update_student_status', '{\"type\":\"attendance\",\"date\":\"2025-05-10\",\"student_count\":1,\"method\":\"manual\"}', '2025-05-10 12:08:11'),
(77, 109, 'create_activity', '{\"activity_id\":\"1\",\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\",\"activity_date\":\"2025-05-09\"}', '2025-05-10 14:10:33'),
(78, 109, 'edit_activity', '{\"activity_id\":\"1\",\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\",\"activity_date\":\"2025-05-09\"}', '2025-05-10 14:10:41'),
(79, 109, 'edit_activity', '{\"activity_id\":\"1\",\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\",\"activity_date\":\"2025-05-09\"}', '2025-05-10 14:12:31'),
(80, 109, 'delete_activity', '{\"activity_id\":\"1\",\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\"}', '2025-05-10 14:12:33'),
(81, 109, 'create_activity', '{\"activity_id\":\"2\",\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\",\"activity_date\":\"2025-05-08\"}', '2025-05-10 14:13:01'),
(82, 109, 'record_activity_attendance', '{\"activity_id\":2,\"activity_name\":\"\\u0e01\\u0e34\\u0e08\\u0e01\\u0e23\\u0e23\\u0e21\\u0e1b\\u0e10\\u0e21\\u0e19\\u0e34\\u0e40\\u0e17\\u0e28\\u0e19\\u0e31\\u0e01\\u0e28\\u0e36\\u0e01\\u0e29\\u0e32\\u0e43\\u0e2b\\u0e21\\u0e48\",\"student_count\":5}', '2025-05-10 14:16:06');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL COMMENT 'ชื่อผู้ใช้สำหรับเข้าสู่ระบบ',
  `password` varchar(32) NOT NULL COMMENT 'รหัสผ่าน MD5',
  `title` varchar(20) DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(100) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(100) NOT NULL COMMENT 'นามสกุล',
  `email` varchar(100) DEFAULT NULL COMMENT 'อีเมล',
  `phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `role` varchar(50) DEFAULT 'admin' COMMENT 'บทบาท (admin, super_admin)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบล่าสุดเมื่อ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่สร้าง',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'วันที่แก้ไขล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password`, `title`, `first_name`, `last_name`, `email`, `phone`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'นาย', 'ผู้ดูแล', 'ระบบ', 'admin@prasat.ac.th', '0899999999', 'admin', 1, '2025-05-10 20:38:57', '2025-05-09 04:40:20', '2025-05-10 13:38:57');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` varchar(50) DEFAULT 'general',
  `status` varchar(20) DEFAULT 'active',
  `is_all_targets` tinyint(4) DEFAULT 1,
  `target_department` int(11) DEFAULT NULL,
  `target_level` varchar(10) DEFAULT NULL,
  `expiration_date` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `title`, `content`, `type`, `status`, `is_all_targets`, `target_department`, `target_level`, `expiration_date`, `scheduled_date`, `created_by`, `created_at`, `updated_at`) VALUES
(3, 'ทดสอบการออกประกาศ', 'ทดสอบ', 'general', 'active', 1, 6, '1', NULL, NULL, 91, '2025-04-01 05:24:23', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `date` date NOT NULL COMMENT 'วันที่เช็คชื่อ',
  `attendance_status` enum('present','absent','late','leave') NOT NULL DEFAULT 'absent' COMMENT 'สถานะการเข้าแถว (มา/ขาด/สาย/ลา)',
  `check_method` enum('GPS','QR_Code','PIN','Manual') NOT NULL COMMENT 'วิธีการเช็คชื่อ',
  `checker_user_id` int(11) DEFAULT NULL COMMENT 'ผู้ที่เช็คชื่อ (ครูหรือแอดมิน)',
  `location_lat` decimal(10,7) DEFAULT NULL COMMENT 'พิกัด GPS Latitude',
  `location_lng` decimal(10,7) DEFAULT NULL COMMENT 'พิกัด GPS Longitude',
  `photo_url` varchar(255) DEFAULT NULL COMMENT 'รูปภาพการเข้าแถว',
  `pin_code` varchar(10) DEFAULT NULL COMMENT 'รหัส PIN ที่ใช้',
  `check_time` time NOT NULL COMMENT 'เวลาที่เช็คชื่อ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL COMMENT 'หมายเหตุเพิ่มเติม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `academic_year_id`, `date`, `attendance_status`, `check_method`, `checker_user_id`, `location_lat`, `location_lng`, `photo_url`, `pin_code`, `check_time`, `created_at`, `remarks`) VALUES
(43, 41, 1, '2025-05-04', 'present', 'Manual', 121, NULL, NULL, NULL, NULL, '12:43:09', '2025-05-04 05:43:09', ''),
(44, 48, 1, '2025-05-04', 'present', 'Manual', 121, NULL, NULL, NULL, NULL, '12:43:56', '2025-05-04 05:43:56', ''),
(45, 50, 1, '2025-05-04', 'present', 'Manual', 121, NULL, NULL, NULL, NULL, '12:43:56', '2025-05-04 05:43:56', ''),
(46, 51, 1, '2025-05-04', 'present', 'Manual', 121, NULL, NULL, NULL, NULL, '12:43:58', '2025-05-04 05:43:58', ''),
(47, 52, 1, '2025-05-04', 'present', 'Manual', 121, NULL, NULL, NULL, NULL, '12:43:58', '2025-05-04 05:43:58', ''),
(48, 49, 1, '2025-05-06', 'present', 'GPS', NULL, 14.6160135, 103.3475743, NULL, NULL, '10:54:20', '2025-05-06 03:54:20', NULL),
(49, 41, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '13:32:47', '2025-05-09 06:32:47', ''),
(50, 48, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '13:32:47', '2025-05-09 06:32:47', ''),
(51, 50, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '13:32:47', '2025-05-09 06:32:47', ''),
(52, 51, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '13:32:47', '2025-05-09 06:32:47', ''),
(53, 52, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '13:32:47', '2025-05-09 06:32:47', ''),
(54, 49, 1, '2025-05-09', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '14:05:18', '2025-05-09 07:05:18', ''),
(55, 49, 1, '2025-05-08', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '20:53:39', '2025-05-09 13:53:39', ''),
(56, 41, 1, '2025-05-10', 'absent', 'Manual', 109, NULL, NULL, NULL, NULL, '16:03:10', '2025-05-10 09:03:10', ''),
(57, 48, 1, '2025-05-10', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '16:03:10', '2025-05-10 09:03:10', ''),
(58, 50, 1, '2025-05-10', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '16:03:10', '2025-05-10 09:03:10', ''),
(59, 51, 1, '2025-05-10', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '16:03:10', '2025-05-10 09:03:10', ''),
(60, 52, 1, '2025-05-10', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '16:03:10', '2025-05-10 09:03:10', ''),
(61, 49, 1, '2025-05-10', 'present', 'Manual', 109, NULL, NULL, NULL, NULL, '19:08:11', '2025-05-10 12:08:11', '');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_retroactive_history`
--

CREATE TABLE `attendance_retroactive_history` (
  `history_id` int(11) NOT NULL,
  `attendance_id` int(11) NOT NULL COMMENT 'รหัสการเช็คชื่อ',
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `retroactive_date` date NOT NULL COMMENT 'วันที่ทำการเช็คชื่อย้อนหลัง',
  `retroactive_status` enum('present','absent','late','leave') NOT NULL COMMENT 'สถานะการเช็คชื่อย้อนหลัง',
  `retroactive_reason` text NOT NULL COMMENT 'เหตุผลการเช็คชื่อย้อนหลัง',
  `created_by` int(11) NOT NULL COMMENT 'ผู้ที่ทำการเช็คชื่อย้อนหลัง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่บันทึก'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_retroactive_history`
--

INSERT INTO `attendance_retroactive_history` (`history_id`, `attendance_id`, `student_id`, `retroactive_date`, `retroactive_status`, `retroactive_reason`, `created_by`, `created_at`) VALUES
(4, 17, 41, '2025-04-29', 'present', 'ลืม', 111, '2025-04-30 15:44:16'),
(5, 34, 48, '2025-05-02', 'present', 'สาย', 118, '2025-05-03 16:24:46'),
(6, 35, 51, '2025-05-02', 'absent', 'สาย', 118, '2025-05-03 16:25:07');

-- --------------------------------------------------------

--
-- Table structure for table `attendance_settings`
--

CREATE TABLE `attendance_settings` (
  `setting_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `attendance_start_time` time NOT NULL DEFAULT '07:30:00' COMMENT 'เวลาเริ่มเช็คชื่อ',
  `attendance_end_time` time NOT NULL DEFAULT '08:30:00' COMMENT 'เวลาสิ้นสุดเช็คชื่อ',
  `gps_radius` int(11) NOT NULL DEFAULT 200 COMMENT 'รัศมี GPS ที่อนุญาตให้เช็คชื่อได้ (เมตร)',
  `gps_center_lat` decimal(10,7) DEFAULT NULL COMMENT 'ตำแหน่งกลาง GPS Latitude',
  `gps_center_lng` decimal(10,7) DEFAULT NULL COMMENT 'ตำแหน่งกลาง GPS Longitude',
  `require_photo` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ต้องมีรูปภาพหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bot_commands`
--

CREATE TABLE `bot_commands` (
  `id` int(11) NOT NULL,
  `command_key` varchar(255) NOT NULL COMMENT 'คีย์คำสั่ง (คั่นด้วย ,)',
  `command_reply` text NOT NULL COMMENT 'ข้อความตอบกลับ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bot_commands`
--

INSERT INTO `bot_commands` (`id`, `command_key`, `command_reply`, `is_active`, `created_at`, `updated_at`) VALUES
(154, 'สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ', 'สวัสดีครับ/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ/คะ', 1, '2025-05-09 04:02:15', '2025-05-09 04:02:15'),
(155, 'เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว', 'คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู \"ตรวจสอบการเข้าแถว\" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล', 1, '2025-05-09 04:02:15', '2025-05-09 04:02:15'),
(156, 'ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน', 'คุณสามารถใช้งานระบบได้โดย:\n1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน\n2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน\n3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา\n4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ', 1, '2025-05-09 04:02:15', '2025-05-09 04:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้น',
  `department_id` int(11) NOT NULL COMMENT 'รหัสแผนกวิชา',
  `group_number` int(11) NOT NULL COMMENT 'กลุ่มเรียน เช่น 1, 2, 3, 4, 5',
  `classroom` varchar(20) DEFAULT NULL COMMENT 'ห้องเรียนประจำ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `academic_year_id`, `level`, `department_id`, `group_number`, `classroom`, `is_active`, `created_at`, `updated_at`) VALUES
(12, 1, 'ปวช.1', 1, 1, NULL, 1, '2025-03-30 10:16:16', '2025-05-03 12:06:51'),
(14, 1, 'ปวช.1', 6, 2, NULL, 1, '2025-03-30 12:47:08', '2025-03-30 12:47:08'),
(16, 1, 'ปวช.1', 6, 1, 'IT', 1, '2025-05-03 12:41:10', '2025-05-03 12:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `class_advisors`
--

CREATE TABLE `class_advisors` (
  `class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียน',
  `teacher_id` int(11) NOT NULL COMMENT 'รหัสครู',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ครูที่ปรึกษาหลักหรือไม่',
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_advisors`
--

INSERT INTO `class_advisors` (`class_id`, `teacher_id`, `is_primary`, `assigned_date`) VALUES
(12, 7, 1, '2025-05-04 05:48:02'),
(14, 7, 1, '2025-05-05 15:45:28');

-- --------------------------------------------------------

--
-- Table structure for table `class_history`
--

CREATE TABLE `class_history` (
  `history_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `previous_class_id` int(11) DEFAULT NULL COMMENT 'รหัสชั้นเรียนเดิม',
  `new_class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียนใหม่',
  `previous_level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') DEFAULT NULL COMMENT 'ระดับชั้นเดิม',
  `new_level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้นใหม่',
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่เลื่อนชั้น',
  `academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาที่เลื่อนชั้น',
  `promotion_type` enum('promotion','retention','transfer','graduation') NOT NULL DEFAULT 'promotion' COMMENT 'ประเภทการเลื่อนชั้น',
  `promotion_notes` text DEFAULT NULL COMMENT 'บันทึกเพิ่มเติม',
  `created_by` int(11) NOT NULL COMMENT 'ผู้ดำเนินการ'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_code` varchar(10) NOT NULL COMMENT 'รหัสแผนกวิชา',
  `department_name` varchar(100) NOT NULL COMMENT 'ชื่อแผนกวิชา',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_code`, `department_name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'AUTO', 'ช่างยนต์', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(3, 'ELEC', 'ช่างไฟฟ้ากำลัง', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(4, 'ELECT', 'ช่างอิเล็กทรอนิกส์', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(6, 'IT', 'เทคโนโลยีสารสนเทศ', 1, '2025-03-27 12:59:26', '2025-03-29 04:09:19'),
(8, 'WELD', 'ช่างเชื่อมโลหะ', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(13, 'TEST', 'เทคโนโลยีสารสนเทศ2', 1, '2025-05-06 17:07:48', '2025-05-06 17:07:48');

-- --------------------------------------------------------

--
-- Table structure for table `line_notifications`
--

CREATE TABLE `line_notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้ที่จะส่งการแจ้งเตือนไปถึง',
  `message` text NOT NULL COMMENT 'ข้อความ',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'เวลาที่ส่ง',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending' COMMENT 'สถานะการส่ง',
  `error_message` text DEFAULT NULL COMMENT 'ข้อความแสดงข้อผิดพลาด',
  `notification_type` enum('attendance','risk_alert','system') NOT NULL DEFAULT 'system' COMMENT 'ประเภทการแจ้งเตือน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `message_templates`
--

CREATE TABLE `message_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('individual','group') NOT NULL,
  `category` varchar(50) DEFAULT 'attendance',
  `content` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message_templates`
--

INSERT INTO `message_templates` (`id`, `name`, `type`, `category`, `content`, `is_active`, `created_by`, `created_at`, `updated_at`, `last_used`) VALUES
(1, 'แจ้งเตือนความเสี่ยงรายบุคคล', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', 1, NULL, '2025-05-04 14:50:15', NULL, NULL),
(2, 'นัดประชุมผู้ปกครองกลุ่มเสี่ยง', 'group', 'meeting', 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', 1, NULL, '2025-05-04 14:50:15', NULL, NULL),
(3, 'แจ้งเตือนฉุกเฉิน', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', 1, NULL, '2025-05-04 14:50:15', NULL, NULL),
(4, 'รายงานสรุปประจำเดือน', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน{{เดือน}} {{ปี}}\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', 1, NULL, '2025-05-04 14:50:15', NULL, NULL),
(5, 'แจ้งข่าวกิจกรรมวิทยาลัย', 'group', 'activity', 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งข้อมูลข่าวสารเกี่ยวกับกิจกรรม{{ชื่อกิจกรรม}} ซึ่งจะจัดขึ้นในวันที่ {{วันที่}} เวลา {{เวลา}} ณ {{สถานที่}}\n\nนักเรียนจะต้อง{{รายละเอียด}}\n\nหากมีข้อสงสัยกรุณาติดต่อ {{ผู้รับผิดชอบ}} ที่เบอร์โทร {{เบอร์โทร}}\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', 1, NULL, '2025-05-04 14:50:15', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้ที่จะได้รับการแจ้งเตือน',
  `type` enum('attendance_alert','system_message','risk_alert') NOT NULL COMMENT 'ประเภทการแจ้งเตือน',
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อ',
  `notification_message` text NOT NULL COMMENT 'ข้อความ',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'อ่านแล้วหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `related_student_id` int(11) DEFAULT NULL COMMENT 'รหัสนักเรียนที่เกี่ยวข้อง (ถ้ามี)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_templates`
--

CREATE TABLE `notification_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('individual','group') NOT NULL DEFAULT 'individual',
  `category` varchar(50) NOT NULL DEFAULT 'attendance',
  `content` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_templates`
--

INSERT INTO `notification_templates` (`id`, `name`, `type`, `category`, `content`, `created_by`, `created_at`, `updated_at`, `last_used`) VALUES
(1, 'แจ้งการเข้าแถวประจำวัน', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(2, 'แจ้งเตือนความเสี่ยงตกกิจกรรม', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางวิทยาลัยขอแจ้งเตือนว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(3, 'แจ้งเตือนความเสี่ยงสูง', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\n[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} โดยด่วน เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(4, 'รายงานสรุปประจำเดือน', 'individual', 'attendance', 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nสรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน\n\nจำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)\nจำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน\nสถานะ: {{สถานะการเข้าแถว}}\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัย\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(5, 'แจ้งเตือนกลุ่มความเสี่ยง', 'group', 'attendance', 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด\n\nโดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา\n\nกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(6, 'แจ้งเตือนการประชุมกลุ่ม', 'group', 'meeting', 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอเรียนเชิญท่านผู้ปกครองทุกท่านเข้าร่วมการประชุมผู้ปกครอง ในวันศุกร์ที่ 21 มิถุนายน 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nวาระการประชุมประกอบด้วย\n1. รายงานความก้าวหน้าทางการเรียนของนักเรียน\n2. แนวทางการป้องกันและแก้ไขปัญหาการขาดเรียนและขาดกิจกรรม\n3. แนวทางความร่วมมือระหว่างผู้ปกครองและวิทยาลัย\n\nจึงเรียนมาเพื่อโปรดทราบและเข้าร่วมประชุมโดยพร้อมเพรียงกัน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL),
(7, 'แจ้งกิจกรรมพิเศษ', 'group', 'activity', 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}\n\nทางวิทยาลัยขอแจ้งให้ทราบว่าในวันศุกร์ที่ 21 มิถุนายน 2568 ทางวิทยาลัยจะจัดกิจกรรมพิเศษสำหรับนักเรียน โดยขอให้นักเรียนมาเข้าแถวตามปกติในเวลา 07:30 น.\n\nหากท่านมีข้อสงสัยประการใด กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}}\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท', NULL, '2025-05-05 22:34:25', '2025-05-05 22:34:25', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `relationship` enum('พ่อ','แม่','ผู้ปกครอง','ญาติ') NOT NULL COMMENT 'ความสัมพันธ์กับนักเรียน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_student_relation`
--

CREATE TABLE `parent_student_relation` (
  `relation_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL COMMENT 'รหัสผู้ปกครอง',
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pins`
--

CREATE TABLE `pins` (
  `pin_id` int(11) NOT NULL,
  `pin_code` varchar(10) NOT NULL COMMENT 'รหัส PIN',
  `creator_user_id` int(11) NOT NULL COMMENT 'ผู้สร้าง PIN',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `valid_from` datetime NOT NULL COMMENT 'มีผลตั้งแต่',
  `valid_until` datetime NOT NULL COMMENT 'มีผลถึง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ใช้งานได้หรือไม่',
  `class_id` int(11) DEFAULT NULL COMMENT 'หากระบุ class_id จะใช้ได้เฉพาะห้องเรียนนี้',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qr_codes`
--

CREATE TABLE `qr_codes` (
  `qr_code_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `qr_code_data` text NOT NULL COMMENT 'ข้อมูล QR Code',
  `valid_from` datetime NOT NULL COMMENT 'มีผลตั้งแต่',
  `valid_until` datetime NOT NULL COMMENT 'มีผลถึง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ใช้งานได้หรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_codes`
--

INSERT INTO `qr_codes` (`qr_code_id`, `student_id`, `qr_code_data`, `valid_from`, `valid_until`, `is_active`, `created_at`) VALUES
(38, 49, '{\"type\":\"student_link\",\"student_id\":\"49\",\"student_code\":\"67319010002\",\"token\":\"095e9a6a8dabaa0930623e0c627c2c76\",\"expire_time\":\"2025-05-13 05:43:48\"}', '2025-05-06 10:43:48', '2025-05-13 05:43:48', 1, '2025-05-06 03:43:48');

-- --------------------------------------------------------

--
-- Table structure for table `risk_students`
--

CREATE TABLE `risk_students` (
  `risk_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `absence_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ขาด',
  `risk_level` enum('low','medium','high','critical') NOT NULL COMMENT 'ระดับความเสี่ยง',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ส่งการแจ้งเตือนแล้วหรือไม่',
  `notification_date` datetime DEFAULT NULL COMMENT 'วันที่ส่งการแจ้งเตือน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `student_code` varchar(11) NOT NULL COMMENT 'รหัสนักศึกษา 11 หลัก',
  `title` enum('นาย','นางสาว','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `current_class_id` int(11) DEFAULT NULL COMMENT 'รหัสห้องเรียนปัจจุบัน',
  `status` enum('กำลังศึกษา','พักการเรียน','พ้นสภาพ','สำเร็จการศึกษา') NOT NULL DEFAULT 'กำลังศึกษา' COMMENT 'สถานะการศึกษา',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `student_code`, `title`, `current_class_id`, `status`, `created_at`, `updated_at`) VALUES
(41, 99, '17785785786', 'นาย', 12, 'กำลังศึกษา', '2025-04-01 02:39:41', '2025-05-05 15:47:41'),
(48, 106, '67319010001', 'นาย', 12, 'กำลังศึกษา', '2025-04-01 02:58:16', '2025-04-01 03:02:02'),
(49, 124, '67319010002', 'นาย', 14, 'กำลังศึกษา', '2025-04-01 02:58:16', '2025-05-09 14:18:24'),
(50, 108, '67319010003', 'นาย', 12, 'กำลังศึกษา', '2025-04-01 02:58:16', '2025-04-01 03:02:02'),
(51, 109, '67319010005', 'นางสาว', 12, 'กำลังศึกษา', '2025-04-01 02:58:16', '2025-05-05 15:47:22'),
(52, 110, '67319010006', 'นาย', 12, 'กำลังศึกษา', '2025-04-01 02:58:16', '2025-05-05 15:46:59');

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_records`
--

CREATE TABLE `student_academic_records` (
  `record_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียน',
  `total_attendance_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่มาเข้าแถว',
  `total_absence_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ขาด',
  `passed_activity` tinyint(1) DEFAULT NULL COMMENT 'ผ่านกิจกรรมหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_academic_records`
--

INSERT INTO `student_academic_records` (`record_id`, `student_id`, `academic_year_id`, `class_id`, `total_attendance_days`, `total_absence_days`, `passed_activity`, `created_at`, `updated_at`) VALUES
(34, 41, 1, 12, 2, 1, NULL, '2025-04-01 02:39:41', '2025-05-10 12:07:21'),
(36, 48, 1, 12, 3, 0, NULL, '2025-04-01 02:58:16', '2025-05-10 12:07:21'),
(37, 49, 1, 14, 4, 0, NULL, '2025-04-01 02:58:16', '2025-05-10 12:08:11'),
(38, 50, 1, 12, 3, 0, NULL, '2025-04-01 02:58:16', '2025-05-10 12:07:21'),
(39, 51, 1, 12, 3, 0, NULL, '2025-04-01 02:58:16', '2025-05-10 12:07:21'),
(40, 52, 1, 12, 3, 0, NULL, '2025-04-01 02:58:16', '2025-05-10 12:07:21');

-- --------------------------------------------------------

--
-- Table structure for table `student_pending`
--

CREATE TABLE `student_pending` (
  `id` int(11) NOT NULL,
  `student_code` varchar(11) NOT NULL COMMENT 'รหัสนักศึกษา 11 หลัก',
  `title` enum('นาย','นางสาว','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_pending`
--

INSERT INTO `student_pending` (`id`, `student_code`, `title`, `first_name`, `last_name`, `created_at`, `updated_at`) VALUES
(1, '12345678913', 'นาย', 'MONTREE', 'SRISUK', '2025-05-01 13:59:52', '2025-05-01 13:59:52'),
(2, '63222110017', 'นาย', 'มนตรี', 'ศรีสุข', '2025-05-02 14:24:44', '2025-05-03 05:46:26');

-- --------------------------------------------------------

--
-- Table structure for table `student_promotion_batch`
--

CREATE TABLE `student_promotion_batch` (
  `batch_id` int(11) NOT NULL,
  `from_academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาต้นทาง',
  `to_academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาปลายทาง',
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่ดำเนินการ',
  `students_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนนักเรียนที่เลื่อนชั้น',
  `graduates_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนนักเรียนที่จบการศึกษา',
  `status` enum('pending','in_progress','completed','failed') NOT NULL DEFAULT 'pending' COMMENT 'สถานะการดำเนินการ',
  `notes` text DEFAULT NULL COMMENT 'บันทึกเพิ่มเติม',
  `created_by` int(11) NOT NULL COMMENT 'ผู้ดำเนินการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL COMMENT 'คีย์การตั้งค่า',
  `setting_value` text DEFAULT NULL COMMENT 'ค่าการตั้งค่า',
  `setting_description` varchar(255) DEFAULT NULL COMMENT 'คำอธิบาย',
  `setting_group` varchar(50) NOT NULL DEFAULT 'general' COMMENT 'กลุ่มการตั้งค่า',
  `is_editable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สามารถแก้ไขได้หรือไม่',
  `updated_by` int(11) DEFAULT NULL COMMENT 'ผู้อัปเดตล่าสุด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_description`, `setting_group`, `is_editable`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'system_name', 'น้องชูใจ', 'ชื่อระบบ', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(2, 'school_name', 'วิทยาลัยการอาชีพปราสาท', 'ชื่อสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(3, 'school_address', '', 'ที่อยู่สถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(4, 'school_phone', '', 'เบอร์โทรสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(5, 'school_email', '', 'อีเมลสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(6, 'attendance_start_time', '07:30', 'เวลาเริ่มต้นการเช็คชื่อเข้าแถว', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(7, 'attendance_end_time', '14:20', 'เวลาสิ้นสุดการเช็คชื่อเข้าแถว', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(8, 'gps_radius', '50', 'รัศมี GPS สำหรับการเช็คชื่อ (เมตร)', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-05-09 04:02:15'),
(9, 'require_photo', '0', 'กำหนดให้ต้องมีรูปภาพเมื่อเช็คชื่อหรือไม่', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(10, 'risk_threshold_low', '80', 'เกณฑ์ความเสี่ยงระดับต่ำ (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(11, 'risk_threshold_medium', '70', 'เกณฑ์ความเสี่ยงระดับกลาง (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(12, 'risk_threshold_high', '60', 'เกณฑ์ความเสี่ยงระดับสูง (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(13, 'risk_threshold_critical', '50', 'เกณฑ์ความเสี่ยงระดับวิกฤต (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(14, 'school_code', '10001', 'รหัสโรงเรียน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(15, 'admin_registration_code', 'ADMIN2025', 'รหัสสำหรับการลงทะเบียนแอดมิน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(16, 'school_website', 'http://www.prasatwittayakom.ac.th', 'เว็บไซต์โรงเรียน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(17, 'system_language', 'th', 'ภาษาระบบ', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(18, 'system_theme', 'green', 'ธีมสี', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(19, 'dark_mode', '0', 'โหมดกลางคืน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(20, 'backup_frequency', 'weekly', 'ความถี่ในการสำรองข้อมูลอัตโนมัติ', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(21, 'backup_keep_count', '5', 'จำนวนการสำรองข้อมูลที่เก็บไว้', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(22, 'backup_path', 'backups/', 'พาธที่เก็บไฟล์สำรองข้อมูล', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(24, 'enable_notifications', '1', 'เปิดใช้งานการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(25, 'critical_notifications', '1', 'แจ้งเตือนกรณีฉุกเฉิน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(26, 'send_daily_summary', '0', 'ส่งสรุปรายวันให้ผู้ปกครอง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(27, 'send_weekly_summary', '1', 'ส่งสรุปรายสัปดาห์ให้ผู้ปกครอง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(28, 'absence_threshold', '5', 'จำนวนครั้งที่ขาดแถวก่อนการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(29, 'risk_notification_frequency', 'weekly', 'ช่วงเวลาแจ้งเตือนสำหรับนักเรียนเสี่ยง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(30, 'notification_time', '16:00', 'เวลาที่ส่งการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(31, 'auto_notifications', '1', 'แจ้งเตือนอัตโนมัติสำหรับนักเรียนที่เสี่ยงตกกิจกรรม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(32, 'risk_notification_message', 'เรียนผู้ปกครอง บุตรหลานของท่านขาดการเข้าแถวจำนวน {absent_count} ครั้ง ซึ่งมีความเสี่ยงที่จะไม่ผ่านกิจกรรม โปรดติดต่อครูที่ปรึกษา: {advisor_name} โทร: {advisor_phone}', 'ข้อความแจ้งเตือนสำหรับนักเรียนเสี่ยง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(33, 'line_notification', '1', 'แจ้งเตือนผ่าน LINE', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(34, 'sms_notification', '0', 'แจ้งเตือนผ่าน SMS', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(35, 'email_notification', '0', 'แจ้งเตือนผ่านอีเมล', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(36, 'app_notification', '1', 'แจ้งเตือนในแอป', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(37, 'enable_bulk_notifications', '1', 'เปิดใช้งานการแจ้งเตือนแบบกลุ่ม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(38, 'max_bulk_recipients', '50', 'จำนวนผู้รับสูงสุดต่อการส่งแบบกลุ่ม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(39, 'enable_scheduled_notifications', '0', 'เปิดใช้งานการตั้งเวลาส่งล่วงหน้า', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(40, 'min_attendance_rate', 'custom', 'อัตราการเข้าแถวต่ำสุดที่ผ่านกิจกรรม', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(41, 'attendance_counting_period', 'semester', 'ระยะเวลาการนับการเข้าแถว', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(42, 'count_weekend', '1', 'นับเช็คชื่อในวันหยุดเสาร์-อาทิตย์', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(43, 'count_holidays', '0', 'นับเช็คชื่อในวันหยุดนักขัตฤกษ์', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(44, 'exemption_dates', '2025-01-01, 2025-04-13, 2025-04-14, 2025-04-15', 'วันที่ยกเว้นการเช็คชื่อ', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(45, 'enable_qr', '1', 'เปิดใช้งานการเช็คชื่อผ่าน QR Code', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(46, 'enable_pin', '1', 'เปิดใช้งานการเช็คชื่อผ่านรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(47, 'enable_gps', '1', 'เปิดใช้งานการเช็คชื่อผ่าน GPS', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(48, 'enable_photo', '0', 'เปิดใช้งานการเช็คชื่อพร้อมรูปถ่าย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(49, 'enable_manual', '1', 'เปิดใช้งานการเช็คชื่อแบบแมนนวล', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(50, 'pin_expiration', '10', 'อายุของรหัส PIN (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(51, 'pin_usage_limit', '3', 'จำนวนครั้งที่สามารถใช้ PIN ได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(52, 'pin_length', '4', 'ความยาวของรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(53, 'pin_type', 'numeric', 'ประเภทของรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(54, 'qr_expiration', '1', 'อายุของ QR Code (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(55, 'qr_usage_limit', '1', 'จำนวนครั้งที่สามารถใช้ QR Code ได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(56, 'late_check', '1', 'อนุญาตให้เช็คชื่อล่าช้าได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(57, 'late_check_duration', '30', 'ระยะเวลาการเช็คชื่อล่าช้า (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(58, 'late_check_status', 'deduct_score', 'การบันทึกสถานะการมาสาย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(59, 'late_deduct_points', '1', 'จำนวนคะแนนที่หักเมื่อมาสาย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(60, 'absent_deduct_points', '3', 'จำนวนคะแนนที่หักเมื่อขาด', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(61, 'require_attendance_photo', '0', 'บังคับให้มีรูปถ่ายประกอบการเช็คชื่อ', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(62, 'max_photo_size', '5000', 'ขนาดไฟล์รูปสูงสุด (KB)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(63, 'allowed_photo_types', 'jpg,jpeg,png', 'ประเภทไฟล์ที่อนุญาต', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(66, 'school_latitude', '14.6160528', 'ละติจูดของโรงเรียน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(67, 'school_longitude', '103.3473834', 'ลองจิจูดของโรงเรียน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(68, 'gps_accuracy', '10', 'ความแม่นยำตำแหน่ง (±เมตร)', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(69, 'gps_check_interval', '5', 'ระยะเวลาในการตรวจสอบตำแหน่ง (วินาที)', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(70, 'gps_required', '1', 'บังคับใช้การยืนยันตำแหน่ง GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(71, 'gps_photo_required', '0', 'ถ่ายรูปประกอบการเช็คชื่อด้วย GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(72, 'gps_mock_detection', '1', 'ตรวจจับการปลอมแปลงตำแหน่ง GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(73, 'allow_home_check', '0', 'อนุญาตให้เช็คชื่อจากที่บ้าน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(74, 'allow_parent_verification', '0', 'ให้ผู้ปกครองยืนยันตำแหน่ง', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(75, 'home_check_reasons', 'เจ็บป่วย, โควิด, อุบัติเหตุ, ไปราชการ', 'เหตุผลที่อนุญาตให้เช็คชื่อจากที่บ้าน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(76, 'enable_multiple_locations', '0', 'อนุญาตให้มีจุดเช็คชื่อหลายจุด', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(78, 'single_line_oa', '1', 'ใช้ LINE OA เดียวสำหรับทุกบทบาท', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(79, 'line_oa_name', 'น้องชูใจ AI', 'ชื่อ LINE OA', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(80, 'line_oa_id', '@chujai-ai', 'รหัส LINE OA', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(81, 'line_channel_id', '2007088707', 'Channel ID', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(82, 'line_channel_secret', 'ebd6dffa14e54908a835c59c3bd3a7cf', 'Channel Secret', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(83, 'line_access_token', '7eede0f45eaf5c1409711cc155afc859', 'Channel Access Token', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(84, 'line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน กรุณาเลือกบทบาทของคุณ (นักเรียน/ครู/ผู้ปกครอง)', 'ข้อความต้อนรับ', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(85, 'liff_id', '2007088707-5EJ0XDlr', 'LIFF ID', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(86, 'liff_type', 'tall', 'LIFF Type', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(87, 'liff_url', 'https://4ba6-1-1-246-57.ngrok-free.app/line-oa/callback.php', 'LIFF URL', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(88, 'enable_sms', '0', 'เปิดใช้งานการส่ง SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(89, 'sms_provider', 'thsms', 'ผู้ให้บริการ SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(90, 'sms_api_key', '', 'API Key / Username', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(91, 'sms_api_secret', '', 'API Secret / Password', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(92, 'sms_api_url', 'https://api.thsms.com/api/send', 'API URL', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(93, 'sms_max_length', '160', 'จำนวนตัวอักษรสูงสุดต่อข้อความ', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(94, 'sms_sender_id', 'PRASAT', 'ชื่อผู้ส่ง (Sender ID)', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(95, 'sms_absence_template', 'แจ้งการขาดแถว: นักเรียน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง กรุณาติดต่อครูที่ปรึกษา โทร {advisor_phone}', 'ข้อความแจ้งเตือนการขาดแถว', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(96, 'sms_use_unicode', '1', 'ใช้งาน Unicode (รองรับภาษาไทย)', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(97, 'sms_delivery_report', '0', 'เปิดใช้งานรายงานการส่ง', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(98, 'sms_daily_limit', '100', 'จำนวน SMS สูงสุดต่อวัน', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(99, 'sms_send_time', 'office', 'เวลาที่อนุญาตให้ส่ง SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(100, 'enable_webhook', '1', 'เปิดใช้งาน Webhook', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(101, 'webhook_url', 'https://your-domain.com/line-oa/webhook.php', 'Webhook URL', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(102, 'webhook_secret', '', 'Secret Key', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(103, 'enable_auto_reply', '1', 'เปิดใช้งานการตอบกลับอัตโนมัติ', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(104, 'initial_greeting', 'สวัสดีครับ/ค่ะ ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบสามารถตอบคำถามเกี่ยวกับการเข้าแถวและข้อมูลนักเรียนได้ คุณสามารถสอบถามข้อมูลต่างๆ ได้โดยพิมพ์คำถามหรือเลือกจากเมนูด้านล่าง', 'ข้อความต้อนรับเมื่อเริ่มติดต่อครั้งแรก', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(105, 'fallback_message', 'ขออภัยครับ/ค่ะ ระบบไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้งหรือเลือกจากเมนูด้านล่าง', 'ข้อความสำหรับกรณีไม่เข้าใจคำสั่ง', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(106, 'enable_rich_menu', '1', 'เปิดใช้งาน Rich Menu', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(107, 'rich_menu_name', 'เมนูหลัก น้องชูใจ AI', 'ชื่อ Rich Menu', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(108, 'rich_menu_id', '', 'Rich Menu ID', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-05-09 04:02:15'),
(109, 'current_academic_year', '2568', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(110, 'current_semester', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(111, 'semester_start_date', '2025-05-13', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(112, 'semester_end_date', '2026-03-14', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(113, 'auto_promote_students', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(114, 'reset_attendance_new_semester', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(115, 'primary_color', '#28a745', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(116, 'secondary_color', '#6c757d', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(117, 'background_color', '#f8f9fa', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(118, 'custom_absence_threshold', '5', NULL, 'notification', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(119, 'custom_attendance_rate', '60', NULL, 'attendance', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(120, 'required_attendance_days', '90', NULL, 'attendance', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(121, 'custom_gps_radius', '100', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(122, 'location_name[]', 'สนามกีฬา', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(123, 'location_radius[]', '100', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(124, 'location_latitude[]', '14.9523000', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(125, 'location_longitude[]', '103.4919000', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(126, 'parent_line_oa_name', 'SADD-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(127, 'parent_line_oa_id', '@sadd-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(128, 'parent_line_channel_id', '2007088707', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(129, 'parent_line_channel_secret', 'ebd6dffa14e54908a835c59c3bd3a7cf', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(130, 'parent_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(131, 'parent_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวและแจ้งเตือนสำหรับผู้ปกครอง', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(132, 'student_line_oa_name', 'STD-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(133, 'student_line_oa_id', '@std-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(134, 'student_line_channel_id', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(135, 'student_line_channel_secret', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(136, 'student_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(137, 'student_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับนักเรียน', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(138, 'teacher_line_oa_name', 'Teacher-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(139, 'teacher_line_oa_id', '@teacher-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(140, 'teacher_line_channel_id', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(141, 'teacher_line_channel_secret', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(142, 'teacher_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(143, 'teacher_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับครูที่ปรึกษา', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(144, 'custom_sms_provider_name', '', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(145, 'sms_start_time', '08:00', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(146, 'sms_end_time', '17:00', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(147, 'test_phone_number', '', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(148, 'test_sms_message', 'ทดสอบการส่ง SMS จากระบบน้องชูใจ AI', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15'),
(149, 'commands', '[{\"key\":\"สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ\",\"reply\":\"สวัสดีครับ\\/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ\\/คะ\"},{\"key\":\"เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว\",\"reply\":\"คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู \\\"ตรวจสอบการเข้าแถว\\\" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล\"},{\"key\":\"ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน\",\"reply\":\"คุณสามารถใช้งานระบบได้โดย:\\n1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน\\n2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน\\n3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา\\n4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ\"}]', NULL, 'webhook', 1, NULL, '2025-03-30 11:15:11', '2025-05-09 04:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `national_id` varchar(13) DEFAULT NULL COMMENT 'เลขบัตรประชาชน',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสแผนกวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `title`, `national_id`, `department_id`, `position`, `first_name`, `last_name`, `created_at`, `updated_at`) VALUES
(7, 123, 'นาย', '1320601295892', 1, 'ข้าราชการ', 'MONTREE', 'SRISUK', '2025-05-04 05:47:34', '2025-05-04 05:47:34');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_pending`
--

CREATE TABLE `teacher_pending` (
  `id` int(11) NOT NULL,
  `national_id` varchar(13) NOT NULL COMMENT 'เลขบัตรประชาชน',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสแผนกวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `line_id` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher','parent','admin') NOT NULL COMMENT 'บทบาทผู้ใช้',
  `title` varchar(20) DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(255) DEFAULT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) DEFAULT NULL COMMENT 'นามสกุล',
  `profile_picture` varchar(255) DEFAULT NULL COMMENT 'URL รูปโปรไฟล์',
  `phone_number` varchar(15) DEFAULT NULL COMMENT 'เบอร์โทรศัพท์',
  `email` varchar(255) DEFAULT NULL COMMENT 'อีเมล',
  `gdpr_consent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ยินยอมเก็บข้อมูลส่วนบุคคล',
  `gdpr_consent_date` datetime DEFAULT NULL COMMENT 'วันที่ยินยอมเก็บข้อมูล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบครั้งล่าสุด'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `line_id`, `role`, `title`, `first_name`, `last_name`, `profile_picture`, `phone_number`, `email`, `gdpr_consent`, `gdpr_consent_date`, `created_at`, `updated_at`, `last_login`) VALUES
(91, 'TEMP_12345678911_1743341503_16506b', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '0956313677', 'workmontree@prasat.ac.th', 1, NULL, '2025-03-30 13:31:43', '2025-03-30 13:31:43', NULL),
(93, 'TEMP_13123131231_1743404563_a0cd57', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '0956313677', 'workmontree@prasat.ac.th', 1, NULL, '2025-03-31 07:02:43', '2025-03-31 07:02:43', NULL),
(95, 'TEMP_12345678910_1743407108_b7e03c', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '0956313677', '', 1, NULL, '2025-03-31 07:45:08', '2025-03-31 07:45:08', NULL),
(99, 'TEMP_17785785786_1743475181_218f1d', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '0956313677', '', 1, NULL, '2025-04-01 02:39:41', '2025-05-05 15:47:41', NULL),
(100, 'TEMP_12345678910_1743475198_0f2fe3', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '0956313677', 'montree@gmail.com', 1, NULL, '2025-04-01 02:39:58', '2025-04-01 02:39:58', NULL),
(101, 'TEMP_67319010001_1743476248_0238bb91', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234567', 'somchai@example.com', 1, NULL, '2025-04-01 02:57:28', '2025-04-01 02:57:28', NULL),
(102, 'TEMP_67319010002_1743476248_9dbb7d20', 'student', 'นางสาว', 'สมหญิง', 'รักเรียน', NULL, '0891234568', 'somying@example.com', 1, NULL, '2025-04-01 02:57:28', '2025-04-01 02:57:28', NULL),
(103, 'TEMP_67319010003_1743476248_258b031e', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234569', 'somchai@example.com', 1, NULL, '2025-04-01 02:57:28', '2025-04-01 02:57:28', NULL),
(104, 'TEMP_67319010005_1743476248_fbc17cdc', 'student', 'นางสาว', 'สมหญิง', 'รักเรียน', NULL, '0891234570', 'somying@example.com', 1, NULL, '2025-04-01 02:57:28', '2025-04-01 02:57:28', NULL),
(105, 'TEMP_67319010006_1743476248_0b4febcd', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234571', 'somchai@example.com', 1, NULL, '2025-04-01 02:57:28', '2025-04-01 02:57:28', NULL),
(106, 'TEMP_67319010001_1743476296_f7f9d903', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234567', 'somchai@example.com', 1, NULL, '2025-04-01 02:58:16', '2025-04-01 03:02:02', NULL),
(108, 'TEMP_67319010003_1743476296_1d7ab4a3', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234569', 'somchai@example.com', 1, NULL, '2025-04-01 02:58:16', '2025-04-01 03:02:02', NULL),
(109, 'TEMP_67319010005_1743476296_f37fc90a', 'student', 'นางสาว', 'สมหญิง', 'รักเรียน', NULL, '0891234570', 'somying@example.com', 1, NULL, '2025-04-01 02:58:16', '2025-05-05 15:47:22', NULL),
(110, 'TEMP_67319010006_1743476296_58d55de4', 'student', 'นาย', 'สมชาย', 'ใจดี', NULL, '0891234571', 'somchai@example.com', 1, NULL, '2025-04-01 02:58:16', '2025-05-05 15:46:59', NULL),
(123, 'TEMP_17463376549803', 'teacher', 'นาย', 'MONTREE', 'SRISUK', NULL, '0956313677', '', 1, NULL, '2025-05-04 05:47:34', '2025-05-04 05:47:34', NULL),
(124, 'Uab9dff8376554a091aa4cfe6cc9791d6', 'student', 'นาย', 'สมหญิง', 'รักเรียน', 'https://profile.line-scdn.net/0hG24vO_T7GB0ZDwnP_yJmYmlfG3c6fkEPPWtQeS9bFSskNlpCZmFWeisOFSQkO1hNZ2gEKylcEi4VHG97B1nkKR4_RSwlOV5CPG9W_Q', '0891234568', 'somying@example.com', 1, '2025-05-06 10:43:48', '2025-05-06 03:43:09', '2025-05-09 14:18:24', '2025-05-09 21:16:45');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_advisors_with_classes`
-- (See below for the actual view)
--
CREATE TABLE `view_advisors_with_classes` (
`teacher_id` int(11)
,`title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ')
,`first_name` varchar(255)
,`last_name` varchar(255)
,`teacher_department` varchar(100)
,`class_id` int(11)
,`is_primary` tinyint(1)
,`level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2')
,`class_department` varchar(100)
,`group_number` int(11)
,`academic_year` int(11)
,`semester` tinyint(1)
,`student_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_classes`
-- (See below for the actual view)
--
CREATE TABLE `view_classes` (
`class_id` int(11)
,`academic_year_id` int(11)
,`academic_year` int(11)
,`semester` tinyint(1)
,`level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2')
,`department_id` int(11)
,`department_name` varchar(100)
,`group_number` int(11)
,`classroom` varchar(20)
,`student_count` bigint(21)
,`advisor_count` bigint(21)
,`primary_advisor` mediumtext
,`is_active` tinyint(1)
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_students_with_class`
-- (See below for the actual view)
--
CREATE TABLE `view_students_with_class` (
`student_id` int(11)
,`user_id` int(11)
,`student_code` varchar(11)
,`title` enum('นาย','นางสาว','อื่นๆ')
,`first_name` varchar(255)
,`last_name` varchar(255)
,`phone_number` varchar(15)
,`email` varchar(255)
,`current_class_id` int(11)
,`level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2')
,`department_name` varchar(100)
,`group_number` int(11)
,`academic_year` int(11)
,`semester` tinyint(1)
,`attendance_count` bigint(21)
,`total_attendance_days` int(11)
,`total_absence_days` int(11)
,`activity_status` varchar(9)
,`status` enum('กำลังศึกษา','พักการเรียน','พ้นสภาพ','สำเร็จการศึกษา')
);

-- --------------------------------------------------------

--
-- Structure for view `view_advisors_with_classes`
--
DROP TABLE IF EXISTS `view_advisors_with_classes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_advisors_with_classes`  AS SELECT `t`.`teacher_id` AS `teacher_id`, `t`.`title` AS `title`, `t`.`first_name` AS `first_name`, `t`.`last_name` AS `last_name`, `d`.`department_name` AS `teacher_department`, `ca`.`class_id` AS `class_id`, `ca`.`is_primary` AS `is_primary`, `c`.`level` AS `level`, `d2`.`department_name` AS `class_department`, `c`.`group_number` AS `group_number`, `ay`.`year` AS `academic_year`, `ay`.`semester` AS `semester`, (select count(0) from `students` `s` where `s`.`current_class_id` = `c`.`class_id` and `s`.`status` = 'กำลังศึกษา') AS `student_count` FROM (((((`teachers` `t` left join `departments` `d` on(`t`.`department_id` = `d`.`department_id`)) left join `class_advisors` `ca` on(`t`.`teacher_id` = `ca`.`teacher_id`)) left join `classes` `c` on(`ca`.`class_id` = `c`.`class_id`)) left join `departments` `d2` on(`c`.`department_id` = `d2`.`department_id`)) left join `academic_years` `ay` on(`c`.`academic_year_id` = `ay`.`academic_year_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_classes`
--
DROP TABLE IF EXISTS `view_classes`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_classes`  AS SELECT `c`.`class_id` AS `class_id`, `c`.`academic_year_id` AS `academic_year_id`, `ay`.`year` AS `academic_year`, `ay`.`semester` AS `semester`, `c`.`level` AS `level`, `c`.`department_id` AS `department_id`, `d`.`department_name` AS `department_name`, `c`.`group_number` AS `group_number`, `c`.`classroom` AS `classroom`, (select count(0) from `students` `s` where `s`.`current_class_id` = `c`.`class_id` and `s`.`status` = 'กำลังศึกษา') AS `student_count`, (select count(0) from `class_advisors` `ca` where `ca`.`class_id` = `c`.`class_id`) AS `advisor_count`, (select group_concat(concat(`t`.`title`,' ',`t`.`first_name`,' ',`t`.`last_name`) separator ', ') from (`class_advisors` `ca` join `teachers` `t` on(`ca`.`teacher_id` = `t`.`teacher_id`)) where `ca`.`class_id` = `c`.`class_id` and `ca`.`is_primary` = 1) AS `primary_advisor`, `c`.`is_active` AS `is_active`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at` FROM ((`classes` `c` join `academic_years` `ay` on(`c`.`academic_year_id` = `ay`.`academic_year_id`)) join `departments` `d` on(`c`.`department_id` = `d`.`department_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `view_students_with_class`
--
DROP TABLE IF EXISTS `view_students_with_class`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_students_with_class`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`user_id` AS `user_id`, `s`.`student_code` AS `student_code`, `s`.`title` AS `title`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`phone_number` AS `phone_number`, `u`.`email` AS `email`, `s`.`current_class_id` AS `current_class_id`, `c`.`level` AS `level`, `d`.`department_name` AS `department_name`, `c`.`group_number` AS `group_number`, `ay`.`year` AS `academic_year`, `ay`.`semester` AS `semester`, (select count(0) from `attendance` `a` where `a`.`student_id` = `s`.`student_id` and `a`.`academic_year_id` = `c`.`academic_year_id` and `a`.`attendance_status` = 'present') AS `attendance_count`, (select `sar`.`total_attendance_days` from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `total_attendance_days`, (select `sar`.`total_absence_days` from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `total_absence_days`, (select case when `sar`.`passed_activity` is null then 'รอประเมิน' when `sar`.`passed_activity` = 1 then 'ผ่าน' else 'ไม่ผ่าน' end from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `activity_status`, `s`.`status` AS `status` FROM ((((`students` `s` join `users` `u` on(`s`.`user_id` = `u`.`user_id`)) left join `classes` `c` on(`s`.`current_class_id` = `c`.`class_id`)) left join `departments` `d` on(`c`.`department_id` = `d`.`department_id`)) left join `academic_years` `ay` on(`c`.`academic_year_id` = `ay`.`academic_year_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`academic_year_id`),
  ADD UNIQUE KEY `year_semester_unique` (`year`,`semester`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `activity_date` (`activity_date`);

--
-- Indexes for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `unique_attendance` (`activity_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `recorder_id` (`recorder_id`);

--
-- Indexes for table `activity_target_departments`
--
ALTER TABLE `activity_target_departments`
  ADD PRIMARY KEY (`activity_id`,`department_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `activity_target_levels`
--
ALTER TABLE `activity_target_levels`
  ADD PRIMARY KEY (`activity_id`,`level`);

--
-- Indexes for table `additional_locations`
--
ALTER TABLE `additional_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `target_department` (`target_department`),
  ADD KEY `idx_announcements_status` (`status`),
  ADD KEY `idx_announcements_type` (`type`),
  ADD KEY `idx_announcements_targets` (`is_all_targets`,`target_department`,`target_level`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `attendance_retroactive_history`
--
ALTER TABLE `attendance_retroactive_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `attendance_id` (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `bot_commands`
--
ALTER TABLE `bot_commands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `unique_class` (`academic_year_id`,`level`,`department_id`,`group_number`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `class_advisors`
--
ALTER TABLE `class_advisors`
  ADD PRIMARY KEY (`class_id`,`teacher_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `class_history`
--
ALTER TABLE `class_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `previous_class_id` (`previous_class_id`),
  ADD KEY `new_class_id` (`new_class_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code_unique` (`department_code`);

--
-- Indexes for table `line_notifications`
--
ALTER TABLE `line_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `message_templates`
--
ALTER TABLE `message_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_student_id` (`related_student_id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

--
-- Indexes for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `parent_student_relation`
--
ALTER TABLE `parent_student_relation`
  ADD PRIMARY KEY (`relation_id`),
  ADD UNIQUE KEY `unique_relation` (`parent_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `pins`
--
ALTER TABLE `pins`
  ADD PRIMARY KEY (`pin_id`),
  ADD KEY `creator_user_id` (`creator_user_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`qr_code_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `risk_students`
--
ALTER TABLE `risk_students`
  ADD PRIMARY KEY (`risk_id`),
  ADD UNIQUE KEY `unique_student_year` (`student_id`,`academic_year_id`),
  ADD KEY `academic_year_id` (`academic_year_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `current_class_id` (`current_class_id`);

--
-- Indexes for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `unique_student_year` (`student_id`,`academic_year_id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_pending`
--
ALTER TABLE `student_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`);

--
-- Indexes for table `student_promotion_batch`
--
ALTER TABLE `student_promotion_batch`
  ADD PRIMARY KEY (`batch_id`),
  ADD KEY `from_academic_year_id` (`from_academic_year_id`),
  ADD KEY `to_academic_year_id` (`to_academic_year_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key_unique` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `teacher_pending`
--
ALTER TABLE `teacher_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `line_id` (`line_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `additional_locations`
--
ALTER TABLE `additional_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `attendance_retroactive_history`
--
ALTER TABLE `attendance_retroactive_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_commands`
--
ALTER TABLE `bot_commands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `class_history`
--
ALTER TABLE `class_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `line_notifications`
--
ALTER TABLE `line_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `message_templates`
--
ALTER TABLE `message_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_templates`
--
ALTER TABLE `notification_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `parent_student_relation`
--
ALTER TABLE `parent_student_relation`
  MODIFY `relation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pins`
--
ALTER TABLE `pins`
  MODIFY `pin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `qr_code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `risk_students`
--
ALTER TABLE `risk_students`
  MODIFY `risk_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `student_pending`
--
ALTER TABLE `student_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_promotion_batch`
--
ALTER TABLE `student_promotion_batch`
  MODIFY `batch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teacher_pending`
--
ALTER TABLE `teacher_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_academic_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_attendance`
--
ALTER TABLE `activity_attendance`
  ADD CONSTRAINT `activity_attendance_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_attendance_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_target_departments`
--
ALTER TABLE `activity_target_departments`
  ADD CONSTRAINT `activity_target_departments_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_target_departments_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_target_levels`
--
ALTER TABLE `activity_target_levels`
  ADD CONSTRAINT `activity_target_levels_activity_fk` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`activity_id`) ON DELETE CASCADE;

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`target_department`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  ADD CONSTRAINT `attendance_settings_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `class_advisors`
--
ALTER TABLE `class_advisors`
  ADD CONSTRAINT `class_advisors_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_advisors_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

--
-- Constraints for table `class_history`
--
ALTER TABLE `class_history`
  ADD CONSTRAINT `class_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_history_ibfk_2` FOREIGN KEY (`previous_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `class_history_ibfk_3` FOREIGN KEY (`new_class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `class_history_ibfk_4` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `class_history_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `line_notifications`
--
ALTER TABLE `line_notifications`
  ADD CONSTRAINT `line_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL;

--
-- Constraints for table `notification_templates`
--
ALTER TABLE `notification_templates`
  ADD CONSTRAINT `notification_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `parent_student_relation`
--
ALTER TABLE `parent_student_relation`
  ADD CONSTRAINT `parent_student_relation_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_student_relation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `pins`
--
ALTER TABLE `pins`
  ADD CONSTRAINT `pins_ibfk_1` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `pins_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `pins_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL;

--
-- Constraints for table `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_students`
--
ALTER TABLE `risk_students`
  ADD CONSTRAINT `risk_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_students_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`current_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD CONSTRAINT `student_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_academic_records_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `student_academic_records_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `student_promotion_batch`
--
ALTER TABLE `student_promotion_batch`
  ADD CONSTRAINT `student_promotion_batch_ibfk_1` FOREIGN KEY (`from_academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `student_promotion_batch_ibfk_2` FOREIGN KEY (`to_academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `student_promotion_batch_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `teacher_pending`
--
ALTER TABLE `teacher_pending`
  ADD CONSTRAINT `teacher_pending_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
