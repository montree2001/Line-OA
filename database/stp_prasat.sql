-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 06, 2025 at 04:51 PM
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
(1, 2568, 1, '2025-03-27', '2026-02-07', 80, 1, '2025-03-27 12:59:26');

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
  `action_type` enum('add_student','remove_student','assign_teacher','update_student_status','create_academic_year','promote_students','add_department','edit_department','remove_department','add_class','edit_class','remove_class','manage_advisors') NOT NULL COMMENT 'ประเภทการดำเนินการ',
  `action_details` text DEFAULT NULL COMMENT 'รายละเอียดการดำเนินการ',
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `date` date NOT NULL COMMENT 'วันที่เช็คชื่อ',
  `is_present` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'มาเข้าแถวหรือไม่',
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
(112, 'สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ', 'สวัสดีครับ/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ/คะ', 1, '2025-04-06 07:49:55', '2025-04-06 07:49:55'),
(113, 'เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว', 'คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู \"ตรวจสอบการเข้าแถว\" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล', 1, '2025-04-06 07:49:55', '2025-04-06 07:49:55'),
(114, 'ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน', 'คุณสามารถใช้งานระบบได้โดย:\n1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน\n2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน\n3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา\n4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ', 1, '2025-04-06 07:49:55', '2025-04-06 07:49:55');

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
(12, 1, 'ปวช.1', 6, 1, '', 1, '2025-03-30 10:16:16', '2025-04-06 14:48:08');

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
(12, 34, 1, '2025-04-06 14:47:31');

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
(2, 'MECH', 'ช่างกลโรงงาน', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(3, 'ELEC', 'ช่างไฟฟ้ากำลัง', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(4, 'ELECT', 'ช่างอิเล็กทรอนิกส์', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(5, 'ACC', 'การบัญชี', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(6, 'IT', 'เทคโนโลยีสารสนเทศ', 1, '2025-03-27 12:59:26', '2025-03-29 04:09:19'),
(7, 'HOTEL', 'การโรงแรม', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(8, 'WELD', 'ช่างเชื่อมโลหะ', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(9, 'ADMIN', 'บริหาร', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(10, 'GEN', 'สามัญ', 1, '2025-03-27 12:59:26', '2025-03-27 12:59:26');

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
(50, 182, '12345678911', 'นาย', 12, 'กำลังศึกษา', '2025-04-06 14:48:35', '2025-04-06 14:48:35');

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
(39, 50, 1, 12, 0, 0, NULL, '2025-04-06 14:48:35', '2025-04-06 14:48:35');

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
(1, 'system_name', 'น้องชูใจ', 'ชื่อระบบ', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(2, 'school_name', 'วิทยาลัยการอาชีพปราสาท', 'ชื่อสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(3, 'school_address', '', 'ที่อยู่สถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(4, 'school_phone', '', 'เบอร์โทรสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(5, 'school_email', '', 'อีเมลสถานศึกษา', 'general', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(6, 'attendance_start_time', '06:00', 'เวลาเริ่มต้นการเช็คชื่อเข้าแถว', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(7, 'attendance_end_time', '23:59', 'เวลาสิ้นสุดการเช็คชื่อเข้าแถว', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(8, 'gps_radius', '500', 'รัศมี GPS สำหรับการเช็คชื่อ (เมตร)', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-04-06 07:49:55'),
(9, 'require_photo', '0', 'กำหนดให้ต้องมีรูปภาพเมื่อเช็คชื่อหรือไม่', 'attendance', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(10, 'risk_threshold_low', '80', 'เกณฑ์ความเสี่ยงระดับต่ำ (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(11, 'risk_threshold_medium', '70', 'เกณฑ์ความเสี่ยงระดับกลาง (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(12, 'risk_threshold_high', '60', 'เกณฑ์ความเสี่ยงระดับสูง (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(13, 'risk_threshold_critical', '50', 'เกณฑ์ความเสี่ยงระดับวิกฤต (ร้อยละ)', 'risk_management', 1, NULL, '2025-03-27 12:59:26', '2025-03-27 12:59:26'),
(14, 'school_code', '10001', 'รหัสโรงเรียน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(15, 'admin_registration_code', 'ADMIN2025', 'รหัสสำหรับการลงทะเบียนแอดมิน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(16, 'school_website', 'http://www.prasatwittayakom.ac.th', 'เว็บไซต์โรงเรียน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(17, 'system_language', 'th', 'ภาษาระบบ', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(18, 'system_theme', 'green', 'ธีมสี', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(19, 'dark_mode', '0', 'โหมดกลางคืน', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(20, 'backup_frequency', 'weekly', 'ความถี่ในการสำรองข้อมูลอัตโนมัติ', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(21, 'backup_keep_count', '5', 'จำนวนการสำรองข้อมูลที่เก็บไว้', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(22, 'backup_path', 'backups/', 'พาธที่เก็บไฟล์สำรองข้อมูล', 'general', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(24, 'enable_notifications', '1', 'เปิดใช้งานการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(25, 'critical_notifications', '1', 'แจ้งเตือนกรณีฉุกเฉิน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(26, 'send_daily_summary', '0', 'ส่งสรุปรายวันให้ผู้ปกครอง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(27, 'send_weekly_summary', '1', 'ส่งสรุปรายสัปดาห์ให้ผู้ปกครอง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(28, 'absence_threshold', '5', 'จำนวนครั้งที่ขาดแถวก่อนการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(29, 'risk_notification_frequency', 'weekly', 'ช่วงเวลาแจ้งเตือนสำหรับนักเรียนเสี่ยง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(30, 'notification_time', '16:00', 'เวลาที่ส่งการแจ้งเตือน', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(31, 'auto_notifications', '1', 'แจ้งเตือนอัตโนมัติสำหรับนักเรียนที่เสี่ยงตกกิจกรรม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(32, 'risk_notification_message', 'เรียนผู้ปกครอง บุตรหลานของท่านขาดการเข้าแถวจำนวน {absent_count} ครั้ง ซึ่งมีความเสี่ยงที่จะไม่ผ่านกิจกรรม โปรดติดต่อครูที่ปรึกษา: {advisor_name} โทร: {advisor_phone}', 'ข้อความแจ้งเตือนสำหรับนักเรียนเสี่ยง', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(33, 'line_notification', '1', 'แจ้งเตือนผ่าน LINE', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(34, 'sms_notification', '0', 'แจ้งเตือนผ่าน SMS', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(35, 'email_notification', '0', 'แจ้งเตือนผ่านอีเมล', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(36, 'app_notification', '1', 'แจ้งเตือนในแอป', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(37, 'enable_bulk_notifications', '1', 'เปิดใช้งานการแจ้งเตือนแบบกลุ่ม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(38, 'max_bulk_recipients', '50', 'จำนวนผู้รับสูงสุดต่อการส่งแบบกลุ่ม', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(39, 'enable_scheduled_notifications', '0', 'เปิดใช้งานการตั้งเวลาส่งล่วงหน้า', 'notification', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(40, 'min_attendance_rate', 'custom', 'อัตราการเข้าแถวต่ำสุดที่ผ่านกิจกรรม', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(41, 'attendance_counting_period', 'semester', 'ระยะเวลาการนับการเข้าแถว', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(42, 'count_weekend', '1', 'นับเช็คชื่อในวันหยุดเสาร์-อาทิตย์', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(43, 'count_holidays', '0', 'นับเช็คชื่อในวันหยุดนักขัตฤกษ์', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(44, 'exemption_dates', '2025-01-01, 2025-04-13, 2025-04-14, 2025-04-15', 'วันที่ยกเว้นการเช็คชื่อ', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(45, 'enable_qr', '1', 'เปิดใช้งานการเช็คชื่อผ่าน QR Code', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(46, 'enable_pin', '1', 'เปิดใช้งานการเช็คชื่อผ่านรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(47, 'enable_gps', '1', 'เปิดใช้งานการเช็คชื่อผ่าน GPS', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(48, 'enable_photo', '1', 'เปิดใช้งานการเช็คชื่อพร้อมรูปถ่าย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(49, 'enable_manual', '1', 'เปิดใช้งานการเช็คชื่อแบบแมนนวล', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(50, 'pin_expiration', '10', 'อายุของรหัส PIN (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(51, 'pin_usage_limit', '3', 'จำนวนครั้งที่สามารถใช้ PIN ได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(52, 'pin_length', '4', 'ความยาวของรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(53, 'pin_type', 'numeric', 'ประเภทของรหัส PIN', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(54, 'qr_expiration', '5', 'อายุของ QR Code (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(55, 'qr_usage_limit', '1', 'จำนวนครั้งที่สามารถใช้ QR Code ได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(56, 'late_check', '1', 'อนุญาตให้เช็คชื่อล่าช้าได้', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(57, 'late_check_duration', '30', 'ระยะเวลาการเช็คชื่อล่าช้า (นาที)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(58, 'late_check_status', 'deduct_score', 'การบันทึกสถานะการมาสาย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(59, 'late_deduct_points', '1', 'จำนวนคะแนนที่หักเมื่อมาสาย', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(60, 'absent_deduct_points', '3', 'จำนวนคะแนนที่หักเมื่อขาด', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(61, 'require_attendance_photo', '0', 'บังคับให้มีรูปถ่ายประกอบการเช็คชื่อ', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(62, 'max_photo_size', '5000', 'ขนาดไฟล์รูปสูงสุด (KB)', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(63, 'allowed_photo_types', 'jpg,jpeg,png', 'ประเภทไฟล์ที่อนุญาต', 'attendance', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(66, 'school_latitude', '15.0353793', 'ละติจูดของโรงเรียน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(67, 'school_longitude', '103.5130582', 'ลองจิจูดของโรงเรียน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(68, 'gps_accuracy', '10', 'ความแม่นยำตำแหน่ง (±เมตร)', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(69, 'gps_check_interval', '5', 'ระยะเวลาในการตรวจสอบตำแหน่ง (วินาที)', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(70, 'gps_required', '1', 'บังคับใช้การยืนยันตำแหน่ง GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(71, 'gps_photo_required', '0', 'ถ่ายรูปประกอบการเช็คชื่อด้วย GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(72, 'gps_mock_detection', '1', 'ตรวจจับการปลอมแปลงตำแหน่ง GPS', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(73, 'allow_home_check', '0', 'อนุญาตให้เช็คชื่อจากที่บ้าน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(74, 'allow_parent_verification', '0', 'ให้ผู้ปกครองยืนยันตำแหน่ง', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(75, 'home_check_reasons', 'เจ็บป่วย, โควิด, อุบัติเหตุ, ไปราชการ', 'เหตุผลที่อนุญาตให้เช็คชื่อจากที่บ้าน', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(76, 'enable_multiple_locations', '0', 'อนุญาตให้มีจุดเช็คชื่อหลายจุด', 'gps', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(78, 'single_line_oa', '1', 'ใช้ LINE OA เดียวสำหรับทุกบทบาท', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(79, 'line_oa_name', 'น้องชูใจ AI', 'ชื่อ LINE OA', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(80, 'line_oa_id', '@chujai-ai', 'รหัส LINE OA', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(81, 'line_channel_id', '2007088707', 'Channel ID', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(82, 'line_channel_secret', 'ebd6dffa14e54908a835c59c3bd3a7cf', 'Channel Secret', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(83, 'line_access_token', '7eede0f45eaf5c1409711cc155afc859', 'Channel Access Token', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(84, 'line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน กรุณาเลือกบทบาทของคุณ (นักเรียน/ครู/ผู้ปกครอง)', 'ข้อความต้อนรับ', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(85, 'liff_id', '2007088707-5EJ0XDlr', 'LIFF ID', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(86, 'liff_type', 'tall', 'LIFF Type', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(87, 'liff_url', 'https://4eab-1-1-251-30.ngrok-free.app/line-oa/callback.php', 'LIFF URL', 'line', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(88, 'enable_sms', '0', 'เปิดใช้งานการส่ง SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(89, 'sms_provider', 'thsms', 'ผู้ให้บริการ SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(90, 'sms_api_key', '', 'API Key / Username', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(91, 'sms_api_secret', '', 'API Secret / Password', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(92, 'sms_api_url', 'https://api.thsms.com/api/send', 'API URL', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(93, 'sms_max_length', '160', 'จำนวนตัวอักษรสูงสุดต่อข้อความ', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(94, 'sms_sender_id', 'PRASAT', 'ชื่อผู้ส่ง (Sender ID)', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(95, 'sms_absence_template', 'แจ้งการขาดแถว: นักเรียน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง กรุณาติดต่อครูที่ปรึกษา โทร {advisor_phone}', 'ข้อความแจ้งเตือนการขาดแถว', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(96, 'sms_use_unicode', '1', 'ใช้งาน Unicode (รองรับภาษาไทย)', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(97, 'sms_delivery_report', '0', 'เปิดใช้งานรายงานการส่ง', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(98, 'sms_daily_limit', '100', 'จำนวน SMS สูงสุดต่อวัน', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(99, 'sms_send_time', 'office', 'เวลาที่อนุญาตให้ส่ง SMS', 'sms', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(100, 'enable_webhook', '1', 'เปิดใช้งาน Webhook', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(101, 'webhook_url', 'https://your-domain.com/line-oa/webhook.php', 'Webhook URL', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(102, 'webhook_secret', '', 'Secret Key', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(103, 'enable_auto_reply', '1', 'เปิดใช้งานการตอบกลับอัตโนมัติ', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(104, 'initial_greeting', 'สวัสดีครับ/ค่ะ ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบสามารถตอบคำถามเกี่ยวกับการเข้าแถวและข้อมูลนักเรียนได้ คุณสามารถสอบถามข้อมูลต่างๆ ได้โดยพิมพ์คำถามหรือเลือกจากเมนูด้านล่าง', 'ข้อความต้อนรับเมื่อเริ่มติดต่อครั้งแรก', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(105, 'fallback_message', 'ขออภัยครับ/ค่ะ ระบบไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้งหรือเลือกจากเมนูด้านล่าง', 'ข้อความสำหรับกรณีไม่เข้าใจคำสั่ง', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(106, 'enable_rich_menu', '1', 'เปิดใช้งาน Rich Menu', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(107, 'rich_menu_name', 'เมนูหลัก น้องชูใจ AI', 'ชื่อ Rich Menu', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(108, 'rich_menu_id', '', 'Rich Menu ID', 'webhook', 1, NULL, '2025-03-30 07:32:47', '2025-04-06 07:49:55'),
(109, 'current_academic_year', '2568', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(110, 'current_semester', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(111, 'semester_start_date', '2025-03-27', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(112, 'semester_end_date', '2026-02-07', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(113, 'auto_promote_students', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(114, 'reset_attendance_new_semester', '1', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(115, 'primary_color', '#28a745', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(116, 'secondary_color', '#6c757d', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(117, 'background_color', '#f8f9fa', NULL, 'general', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(118, 'custom_absence_threshold', '5', NULL, 'notification', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(119, 'custom_attendance_rate', '60', NULL, 'attendance', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(120, 'required_attendance_days', '90', NULL, 'attendance', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(121, 'custom_gps_radius', '100', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(122, 'location_name[]', 'สนามกีฬา', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(123, 'location_radius[]', '100', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(124, 'location_latitude[]', '14.9523000', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(125, 'location_longitude[]', '103.4919000', NULL, 'gps', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(126, 'parent_line_oa_name', 'SADD-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(127, 'parent_line_oa_id', '@sadd-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(128, 'parent_line_channel_id', '2007088707', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(129, 'parent_line_channel_secret', 'ebd6dffa14e54908a835c59c3bd3a7cf', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(130, 'parent_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(131, 'parent_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวและแจ้งเตือนสำหรับผู้ปกครอง', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(132, 'student_line_oa_name', 'STD-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(133, 'student_line_oa_id', '@std-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(134, 'student_line_channel_id', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(135, 'student_line_channel_secret', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(136, 'student_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(137, 'student_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับนักเรียน', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(138, 'teacher_line_oa_name', 'Teacher-Prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(139, 'teacher_line_oa_id', '@teacher-prasat', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(140, 'teacher_line_channel_id', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(141, 'teacher_line_channel_secret', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(142, 'teacher_line_access_token', '', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(143, 'teacher_line_welcome_message', 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับครูที่ปรึกษา', NULL, 'line', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(144, 'custom_sms_provider_name', '', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(145, 'sms_start_time', '08:00', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(146, 'sms_end_time', '17:00', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(147, 'test_phone_number', '', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(148, 'test_sms_message', 'ทดสอบการส่ง SMS จากระบบน้องชูใจ AI', NULL, 'sms', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55'),
(149, 'commands', '[{\"key\":\"สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ\",\"reply\":\"สวัสดีครับ\\/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ\\/คะ\"},{\"key\":\"เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว\",\"reply\":\"คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู \\\"ตรวจสอบการเข้าแถว\\\" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล\"},{\"key\":\"ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน\",\"reply\":\"คุณสามารถใช้งานระบบได้โดย:\\n1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน\\n2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน\\n3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา\\n4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ\"}]', NULL, 'webhook', 1, NULL, '2025-03-30 11:15:11', '2025-04-06 07:49:55');

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
(34, 183, 'นาย', '1320601295892', 6, 'ครูจ้างสอน', 'มนตรี', 'ศรีสุข', '2025-04-06 14:47:18', '2025-04-06 14:50:02');

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
(182, 'TEMP_12345678911_1743950915_4be7d4', 'student', 'นาย', 'มนตรี', 'ศรีสุข', NULL, '', '', 1, NULL, '2025-04-06 14:48:35', '2025-04-06 14:48:35', NULL),
(183, 'Uab9dff8376554a091aa4cfe6cc9791d6', 'teacher', 'นาย', 'มนตรี', 'ศรีสุข', 'https://profile.line-scdn.net/0hG24vO_T7GB0ZDwnP_yJmYmlfG3c6fkEPPWtQeS9bFSskNlpCZmFWeisOFSQkO1hNZ2gEKylcEi4VHG97B1nkKR4_RSwlOV5CPG9W_Q', '0956313677', '', 1, '2025-04-06 21:50:02', '2025-04-06 14:49:15', '2025-04-06 14:51:15', '2025-04-06 21:51:15');

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

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_students_with_class`  AS SELECT `s`.`student_id` AS `student_id`, `s`.`user_id` AS `user_id`, `s`.`student_code` AS `student_code`, `s`.`title` AS `title`, `u`.`first_name` AS `first_name`, `u`.`last_name` AS `last_name`, `u`.`phone_number` AS `phone_number`, `u`.`email` AS `email`, `s`.`current_class_id` AS `current_class_id`, `c`.`level` AS `level`, `d`.`department_name` AS `department_name`, `c`.`group_number` AS `group_number`, `ay`.`year` AS `academic_year`, `ay`.`semester` AS `semester`, (select count(0) from `attendance` `a` where `a`.`student_id` = `s`.`student_id` and `a`.`academic_year_id` = `c`.`academic_year_id` and `a`.`is_present` = 1) AS `attendance_count`, (select `sar`.`total_attendance_days` from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `total_attendance_days`, (select `sar`.`total_absence_days` from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `total_absence_days`, (select case when `sar`.`passed_activity` is null then 'รอประเมิน' when `sar`.`passed_activity` = 1 then 'ผ่าน' else 'ไม่ผ่าน' end from `student_academic_records` `sar` where `sar`.`student_id` = `s`.`student_id` and `sar`.`academic_year_id` = `c`.`academic_year_id`) AS `activity_status`, `s`.`status` AS `status` FROM ((((`students` `s` join `users` `u` on(`s`.`user_id` = `u`.`user_id`)) left join `classes` `c` on(`s`.`current_class_id` = `c`.`class_id`)) left join `departments` `d` on(`c`.`department_id` = `d`.`department_id`)) left join `academic_years` `ay` on(`c`.`academic_year_id` = `ay`.`academic_year_id`)) ;

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
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `student_date_unique` (`student_id`,`date`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `checker_user_id` (`checker_user_id`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `related_student_id` (`related_student_id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`);

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
-- AUTO_INCREMENT for table `additional_locations`
--
ALTER TABLE `additional_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=292;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bot_commands`
--
ALTER TABLE `bot_commands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `class_history`
--
ALTER TABLE `class_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `line_notifications`
--
ALTER TABLE `line_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parent_student_relation`
--
ALTER TABLE `parent_student_relation`
  MODIFY `relation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pins`
--
ALTER TABLE `pins`
  MODIFY `pin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `qr_code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `risk_students`
--
ALTER TABLE `risk_students`
  MODIFY `risk_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

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
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- Constraints for dumped tables
--

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
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`checker_user_id`) REFERENCES `users` (`user_id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
