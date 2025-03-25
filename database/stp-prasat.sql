-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2025 at 07:04 AM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stp-prasat`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`academic_year_id`, `year`, `semester`, `start_date`, `end_date`, `required_attendance_days`, `is_active`, `created_at`) VALUES
(1, 2568, 1, '0000-00-00', '0000-00-00', 80, 1, '2025-03-20 03:58:24');

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL COMMENT 'รหัสผู้ดูแลระบบ',
  `action_type` enum('add_student','remove_student','assign_teacher','update_student_status','create_academic_year','promote_students') NOT NULL COMMENT 'ประเภทการดำเนินการ',
  `action_details` text DEFAULT NULL COMMENT 'รายละเอียดการดำเนินการ',
  `action_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `date` date NOT NULL COMMENT 'วันที่เช็คชื่อ',
  `is_present` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'มาเรียนหรือไม่',
  `check_method` enum('GPS','QR_Code','PIN','Manual') NOT NULL COMMENT 'วิธีการเช็คชื่อ',
  `checker_user_id` int(11) DEFAULT NULL COMMENT 'ผู้ที่เช็คชื่อ (ครูหรือแอดมิน)',
  `location_lat` decimal(10,7) DEFAULT NULL COMMENT 'พิกัด GPS Latitude',
  `location_lng` decimal(10,7) DEFAULT NULL COMMENT 'พิกัด GPS Longitude',
  `photo_url` varchar(255) DEFAULT NULL COMMENT 'รูปภาพการเข้าแถว',
  `pin_code` varchar(10) DEFAULT NULL COMMENT 'รหัส PIN ที่ใช้',
  `check_time` time NOT NULL COMMENT 'เวลาที่เช็คชื่อ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL COMMENT 'หมายเหตุเพิ่มเติม'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้น',
  `department` enum('ช่างยนต์','ช่างกลโรงงาน','ช่างไฟฟ้ากำลัง','ช่างอิเล็กทรอนิกส์','การบัญชี','เทคโนโลยีสารสนเทศ','การโรงแรม','ช่างเชื่อมโลหะ') NOT NULL COMMENT 'สาขาวิชา',
  `group_number` int(11) NOT NULL COMMENT 'กลุ่มเรียน เช่น 1, 2, 3, 4, 5',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `academic_year_id`, `level`, `department`, `group_number`, `created_at`) VALUES
(1, 1, 'ปวช.1', 'เทคโนโลยีสารสนเทศ', 33210, '2025-03-20 07:56:46'),
(2, 1, 'ปวช.3', 'เทคโนโลยีสารสนเทศ', 1, '2025-03-20 08:52:58'),
(3, 1, 'ปวช.2', 'เทคโนโลยีสารสนเทศ', 1, '2025-03-20 08:55:36');

-- --------------------------------------------------------

--
-- Table structure for table `class_advisors`
--

CREATE TABLE `class_advisors` (
  `class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียน',
  `teacher_id` int(11) NOT NULL COMMENT 'รหัสครู',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ครูที่ปรึกษาหลักหรือไม่',
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `class_advisors`
--

INSERT INTO `class_advisors` (`class_id`, `teacher_id`, `is_primary`, `assigned_date`) VALUES
(1, 2, 0, '2025-03-20 08:07:53');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `parent_student_relation`
--

CREATE TABLE `parent_student_relation` (
  `relation_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL COMMENT 'รหัสผู้ปกครอง',
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `student_code`, `title`, `current_class_id`, `status`, `created_at`, `updated_at`) VALUES
(10, 16, '12345678910', 'นาย', 1, 'กำลังศึกษา', '2025-03-25 06:03:30', '2025-03-25 06:03:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `student_academic_records`
--

INSERT INTO `student_academic_records` (`record_id`, `student_id`, `academic_year_id`, `class_id`, `total_attendance_days`, `total_absence_days`, `passed_activity`, `created_at`, `updated_at`) VALUES
(10, 10, 1, 1, 0, 0, NULL, '2025-03-25 06:03:30', '2025-03-25 06:03:30');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `national_id` varchar(13) DEFAULT NULL COMMENT 'เลขบัตรประชาชน',
  `department` enum('ช่างยนต์','ช่างกลโรงงาน','ช่างไฟฟ้ากำลัง','ช่างอิเล็กทรอนิกส์','การบัญชี','เทคโนโลยีสารสนเทศ','การโรงแรม','ช่างเชื่อมโลหะ','บริหาร','สามัญ','อื่นๆ') DEFAULT NULL COMMENT 'สาขาวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `title`, `national_id`, `department`, `position`, `first_name`, `last_name`, `created_at`, `updated_at`) VALUES
(2, 7, 'นาย', '1320601295892', 'เทคโนโลยีสารสนเทศ', 'ครูจ้างสอน', 'มนตรี', 'ศรีสุข', '2025-03-20 07:55:37', '2025-03-20 07:55:37');

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
  `department` enum('ช่างยนต์','ช่างกลโรงงาน','ช่างไฟฟ้ากำลัง','ช่างอิเล็กทรอนิกส์','การบัญชี','เทคโนโลยีสารสนเทศ','การโรงแรม','ช่างเชื่อมโลหะ','บริหาร','สามัญ','อื่นๆ') DEFAULT NULL COMMENT 'สาขาวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `line_id` varchar(255) NOT NULL COMMENT 'ID ของ LINE ผู้ใช้',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `line_id`, `role`, `title`, `first_name`, `last_name`, `profile_picture`, `phone_number`, `email`, `gdpr_consent`, `gdpr_consent_date`, `created_at`, `updated_at`, `last_login`) VALUES
(7, '5464565465465465465464654646', 'teacher', 'นาย', 'มนตรี', 'ศรีสุข', '444524545', '0812345678', 'workmontree@gmail.com', 0, '2025-03-20 14:54:15', '2025-03-20 07:55:06', '2025-03-20 07:55:06', NULL),
(16, 'Uab9dff8376554a091aa4cfe6cc9791d6', 'student', 'นาย', 'มนตรี', 'ศรีสุข', 'https://profile.line-scdn.net/0hG24vO_T7GB0ZDwnP_yJmYmlfG3c6fkEPPWtQeS9bFSskNlpCZmFWeisOFSQkO1hNZ2gEKylcEi4VHG97B1nkKR4_RSwlOV5CPG9W_Q', '0644523474', 'workmontree@gmail.com', 1, '2025-03-25 13:03:30', '2025-03-21 08:19:56', '2025-03-25 06:04:19', '2025-03-25 13:04:19');

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
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`action_id`),
  ADD KEY `admin_id` (`admin_id`);

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
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `unique_class` (`academic_year_id`,`level`,`department`,`group_number`);

--
-- Indexes for table `class_advisors`
--
ALTER TABLE `class_advisors`
  ADD PRIMARY KEY (`class_id`,`teacher_id`),
  ADD KEY `teacher_id` (`teacher_id`);

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
  ADD KEY `related_student_id` (`related_student_id`);

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
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `national_id` (`national_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `teacher_pending`
--
ALTER TABLE `teacher_pending`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `national_id` (`national_id`);

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
  MODIFY `academic_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `action_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance_settings`
--
ALTER TABLE `attendance_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `pin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `qr_code_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_students`
--
ALTER TABLE `risk_students`
  MODIFY `risk_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_pending`
--
ALTER TABLE `student_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_pending`
--
ALTER TABLE `teacher_pending`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`);

--
-- Constraints for table `class_advisors`
--
ALTER TABLE `class_advisors`
  ADD CONSTRAINT `class_advisors_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_advisors_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE;

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
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
