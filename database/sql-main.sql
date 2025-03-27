-- -----------------------------------------------------
-- สร้างฐานข้อมูล STP-Prasat ใหม่
-- -----------------------------------------------------

-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS `stp_prasat` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `stp_prasat`;

-- -----------------------------------------------------
-- 1. สร้างตาราง users
-- -----------------------------------------------------
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `last_login` datetime DEFAULT NULL COMMENT 'เข้าสู่ระบบครั้งล่าสุด',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `line_id` (`line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 2. สร้างตาราง departments (ตารางใหม่)
-- -----------------------------------------------------
CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_code` varchar(10) NOT NULL COMMENT 'รหัสแผนกวิชา',
  `department_name` varchar(100) NOT NULL COMMENT 'ชื่อแผนกวิชา',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `department_code_unique` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 3. สร้างตาราง academic_years
-- -----------------------------------------------------
CREATE TABLE `academic_years` (
  `academic_year_id` int(11) NOT NULL AUTO_INCREMENT,
  `year` int(11) NOT NULL COMMENT 'ปีการศึกษา',
  `semester` tinyint(1) NOT NULL COMMENT 'ภาคเรียน (1 หรือ 2)',
  `start_date` date NOT NULL COMMENT 'วันเริ่มต้นปีการศึกษา',
  `end_date` date NOT NULL COMMENT 'วันสิ้นสุดปีการศึกษา',
  `required_attendance_days` int(11) NOT NULL DEFAULT 80 COMMENT 'จำนวนวันที่ต้องเข้าแถวเพื่อผ่านกิจกรรม',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'เป็นปีการศึกษาปัจจุบันหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`academic_year_id`),
  UNIQUE KEY `year_semester_unique` (`year`,`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 4. สร้างตาราง classes (ปรับปรุงโครงสร้าง)
-- -----------------------------------------------------
CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้น',
  `department_id` int(11) NOT NULL COMMENT 'รหัสแผนกวิชา',
  `group_number` int(11) NOT NULL COMMENT 'กลุ่มเรียน เช่น 1, 2, 3, 4, 5',
  `classroom` varchar(20) DEFAULT NULL COMMENT 'ห้องเรียนประจำ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`class_id`),
  UNIQUE KEY `unique_class` (`academic_year_id`,`level`,`department_id`,`group_number`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 5. สร้างตาราง teachers
-- -----------------------------------------------------
CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `national_id` varchar(13) DEFAULT NULL COMMENT 'เลขบัตรประชาชน',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสแผนกวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `national_id` (`national_id`),
  KEY `user_id` (`user_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `teachers_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 6. สร้างตาราง class_advisors
-- -----------------------------------------------------
CREATE TABLE `class_advisors` (
  `class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียน',
  `teacher_id` int(11) NOT NULL COMMENT 'รหัสครู',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ครูที่ปรึกษาหลักหรือไม่',
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`class_id`,`teacher_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `class_advisors_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE,
  CONSTRAINT `class_advisors_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 7. สร้างตาราง students
-- -----------------------------------------------------
CREATE TABLE `students` (
  `student_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `student_code` varchar(11) NOT NULL COMMENT 'รหัสนักศึกษา 11 หลัก',
  `title` enum('นาย','นางสาว','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `current_class_id` int(11) DEFAULT NULL COMMENT 'รหัสห้องเรียนปัจจุบัน',
  `status` enum('กำลังศึกษา','พักการเรียน','พ้นสภาพ','สำเร็จการศึกษา') NOT NULL DEFAULT 'กำลังศึกษา' COMMENT 'สถานะการศึกษา',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `student_code` (`student_code`),
  KEY `user_id` (`user_id`),
  KEY `current_class_id` (`current_class_id`),
  CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `students_ibfk_2` FOREIGN KEY (`current_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 8. สร้างตาราง student_academic_records
-- -----------------------------------------------------
CREATE TABLE `student_academic_records` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียน',
  `total_attendance_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่มาเข้าแถว',
  `total_absence_days` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ขาด',
  `passed_activity` tinyint(1) DEFAULT NULL COMMENT 'ผ่านกิจกรรมหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`record_id`),
  UNIQUE KEY `unique_student_year` (`student_id`,`academic_year_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `student_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `student_academic_records_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `student_academic_records_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 9. สร้างตาราง class_history (ตารางใหม่)
-- -----------------------------------------------------
CREATE TABLE `class_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `previous_class_id` int(11) DEFAULT NULL COMMENT 'รหัสชั้นเรียนเดิม',
  `new_class_id` int(11) NOT NULL COMMENT 'รหัสชั้นเรียนใหม่',
  `previous_level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') DEFAULT NULL COMMENT 'ระดับชั้นเดิม',
  `new_level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้นใหม่',
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่เลื่อนชั้น',
  `academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาที่เลื่อนชั้น',
  `promotion_type` enum('promotion','retention','transfer','graduation') NOT NULL DEFAULT 'promotion' COMMENT 'ประเภทการเลื่อนชั้น',
  `promotion_notes` text DEFAULT NULL COMMENT 'บันทึกเพิ่มเติม',
  `created_by` int(11) NOT NULL COMMENT 'ผู้ดำเนินการ',
  PRIMARY KEY (`history_id`),
  KEY `student_id` (`student_id`),
  KEY `previous_class_id` (`previous_class_id`),
  KEY `new_class_id` (`new_class_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `class_history_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `class_history_ibfk_2` FOREIGN KEY (`previous_class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL,
  CONSTRAINT `class_history_ibfk_3` FOREIGN KEY (`new_class_id`) REFERENCES `classes` (`class_id`),
  CONSTRAINT `class_history_ibfk_4` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `class_history_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 10. สร้างตาราง attendance
-- -----------------------------------------------------
CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `remarks` text DEFAULT NULL COMMENT 'หมายเหตุเพิ่มเติม',
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `student_date_unique` (`student_id`,`date`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `checker_user_id` (`checker_user_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`checker_user_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 11. สร้างตาราง attendance_settings
-- -----------------------------------------------------
CREATE TABLE `attendance_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `attendance_start_time` time NOT NULL DEFAULT '07:30:00' COMMENT 'เวลาเริ่มเช็คชื่อ',
  `attendance_end_time` time NOT NULL DEFAULT '08:30:00' COMMENT 'เวลาสิ้นสุดเช็คชื่อ',
  `gps_radius` int(11) NOT NULL DEFAULT 200 COMMENT 'รัศมี GPS ที่อนุญาตให้เช็คชื่อได้ (เมตร)',
  `gps_center_lat` decimal(10,7) DEFAULT NULL COMMENT 'ตำแหน่งกลาง GPS Latitude',
  `gps_center_lng` decimal(10,7) DEFAULT NULL COMMENT 'ตำแหน่งกลาง GPS Longitude',
  `require_photo` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ต้องมีรูปภาพหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  KEY `academic_year_id` (`academic_year_id`),
  CONSTRAINT `attendance_settings_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 12. สร้างตาราง risk_students
-- -----------------------------------------------------
CREATE TABLE `risk_students` (
  `risk_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `absence_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนวันที่ขาด',
  `risk_level` enum('low','medium','high','critical') NOT NULL COMMENT 'ระดับความเสี่ยง',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'ส่งการแจ้งเตือนแล้วหรือไม่',
  `notification_date` datetime DEFAULT NULL COMMENT 'วันที่ส่งการแจ้งเตือน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`risk_id`),
  UNIQUE KEY `unique_student_year` (`student_id`,`academic_year_id`),
  KEY `academic_year_id` (`academic_year_id`),
  CONSTRAINT `risk_students_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `risk_students_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 13. สร้างตาราง parents
-- -----------------------------------------------------
CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `relationship` enum('พ่อ','แม่','ผู้ปกครอง','ญาติ') NOT NULL COMMENT 'ความสัมพันธ์กับนักเรียน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`parent_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 14. สร้างตาราง parent_student_relation
-- -----------------------------------------------------
CREATE TABLE `parent_student_relation` (
  `relation_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL COMMENT 'รหัสผู้ปกครอง',
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `unique_relation` (`parent_id`,`student_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `parent_student_relation_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE CASCADE,
  CONSTRAINT `parent_student_relation_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 15. สร้างตาราง notifications
-- -----------------------------------------------------
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้ที่จะได้รับการแจ้งเตือน',
  `type` enum('attendance_alert','system_message','risk_alert') NOT NULL COMMENT 'ประเภทการแจ้งเตือน',
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อ',
  `notification_message` text NOT NULL COMMENT 'ข้อความ',
  `is_read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'อ่านแล้วหรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `related_student_id` int(11) DEFAULT NULL COMMENT 'รหัสนักเรียนที่เกี่ยวข้อง (ถ้ามี)',
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `related_student_id` (`related_student_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`related_student_id`) REFERENCES `students` (`student_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 16. สร้างตาราง line_notifications
-- -----------------------------------------------------
CREATE TABLE `line_notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'รหัสผู้ใช้ที่จะส่งการแจ้งเตือนไปถึง',
  `message` text NOT NULL COMMENT 'ข้อความ',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'เวลาที่ส่ง',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending' COMMENT 'สถานะการส่ง',
  `error_message` text DEFAULT NULL COMMENT 'ข้อความแสดงข้อผิดพลาด',
  `notification_type` enum('attendance','risk_alert','system') NOT NULL DEFAULT 'system' COMMENT 'ประเภทการแจ้งเตือน',
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `line_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 17. สร้างตาราง pins
-- -----------------------------------------------------
CREATE TABLE `pins` (
  `pin_id` int(11) NOT NULL AUTO_INCREMENT,
  `pin_code` varchar(10) NOT NULL COMMENT 'รหัส PIN',
  `creator_user_id` int(11) NOT NULL COMMENT 'ผู้สร้าง PIN',
  `academic_year_id` int(11) NOT NULL COMMENT 'รหัสปีการศึกษา',
  `valid_from` datetime NOT NULL COMMENT 'มีผลตั้งแต่',
  `valid_until` datetime NOT NULL COMMENT 'มีผลถึง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ใช้งานได้หรือไม่',
  `class_id` int(11) DEFAULT NULL COMMENT 'หากระบุ class_id จะใช้ได้เฉพาะห้องเรียนนี้',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`pin_id`),
  KEY `creator_user_id` (`creator_user_id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `pins_ibfk_1` FOREIGN KEY (`creator_user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `pins_ibfk_2` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `pins_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 18. สร้างตาราง qr_codes
-- -----------------------------------------------------
CREATE TABLE `qr_codes` (
  `qr_code_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL COMMENT 'รหัสนักเรียน',
  `qr_code_data` text NOT NULL COMMENT 'ข้อมูล QR Code',
  `valid_from` datetime NOT NULL COMMENT 'มีผลตั้งแต่',
  `valid_until` datetime NOT NULL COMMENT 'มีผลถึง',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'ใช้งานได้หรือไม่',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`qr_code_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 19. สร้างตาราง student_pending
-- -----------------------------------------------------
CREATE TABLE `student_pending` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_code` varchar(11) NOT NULL COMMENT 'รหัสนักศึกษา 11 หลัก',
  `title` enum('นาย','นางสาว','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_code` (`student_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 20. สร้างตาราง teacher_pending
-- -----------------------------------------------------
CREATE TABLE `teacher_pending` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `national_id` varchar(13) NOT NULL COMMENT 'เลขบัตรประชาชน',
  `title` enum('นาย','นาง','นางสาว','ดร.','ผศ.','รศ.','ศ.','อื่นๆ') DEFAULT NULL COMMENT 'คำนำหน้า',
  `first_name` varchar(255) NOT NULL COMMENT 'ชื่อจริง',
  `last_name` varchar(255) NOT NULL COMMENT 'นามสกุล',
  `department_id` int(11) DEFAULT NULL COMMENT 'รหัสแผนกวิชา',
  `position` varchar(100) DEFAULT NULL COMMENT 'ตำแหน่ง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `national_id` (`national_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `teacher_pending_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 21. สร้างตาราง admin_actions
-- -----------------------------------------------------
CREATE TABLE `admin_actions` (
  `action_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT 'รหัสผู้ดูแลระบบ',
  `action_type` enum('add_student','remove_student','assign_teacher','update_student_status','create_academic_year','promote_students','add_department','edit_department','remove_department','add_class','edit_class','remove_class','manage_advisors') NOT NULL COMMENT 'ประเภทการดำเนินการ',
  `action_details` text DEFAULT NULL COMMENT 'รายละเอียดการดำเนินการ',
  `action_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`action_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 22. สร้างตาราง student_promotion_batch
-- -----------------------------------------------------
CREATE TABLE `student_promotion_batch` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาต้นทาง',
  `to_academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษาปลายทาง',
  `promotion_date` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'วันที่ดำเนินการ',
  `students_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนนักเรียนที่เลื่อนชั้น',
  `graduates_count` int(11) NOT NULL DEFAULT 0 COMMENT 'จำนวนนักเรียนที่จบการศึกษา',
  `status` enum('pending','in_progress','completed','failed') NOT NULL DEFAULT 'pending' COMMENT 'สถานะการดำเนินการ',
  `notes` text DEFAULT NULL COMMENT 'บันทึกเพิ่มเติม',
  `created_by` int(11) NOT NULL COMMENT 'ผู้ดำเนินการ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`batch_id`),
  KEY `from_academic_year_id` (`from_academic_year_id`),
  KEY `to_academic_year_id` (`to_academic_year_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `student_promotion_batch_ibfk_1` FOREIGN KEY (`from_academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `student_promotion_batch_ibfk_2` FOREIGN KEY (`to_academic_year_id`) REFERENCES `academic_years` (`academic_year_id`),
  CONSTRAINT `student_promotion_batch_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- 23. สร้างตาราง system_settings
-- -----------------------------------------------------
CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL COMMENT 'คีย์การตั้งค่า',
  `setting_value` text DEFAULT NULL COMMENT 'ค่าการตั้งค่า',
  `setting_description` varchar(255) DEFAULT NULL COMMENT 'คำอธิบาย',
  `setting_group` varchar(50) NOT NULL DEFAULT 'general' COMMENT 'กลุ่มการตั้งค่า',
  `is_editable` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'สามารถแก้ไขได้หรือไม่',
  `updated_by` int(11) DEFAULT NULL COMMENT 'ผู้อัปเดตล่าสุด',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key_unique` (`setting_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- เพิ่มข้อมูลตั้งต้น
-- -----------------------------------------------------

-- เพิ่มข้อมูลแผนกวิชา
INSERT INTO `departments` (`department_code`, `department_name`) VALUES
('AUTO', 'ช่างยนต์'),
('MECH', 'ช่างกลโรงงาน'),
('ELEC', 'ช่างไฟฟ้ากำลัง'),
('ELECT', 'ช่างอิเล็กทรอนิกส์'),
('ACC', 'การบัญชี'),
('IT', 'เทคโนโลยีสารสนเทศ'),
('HOTEL', 'การโรงแรม'),
('WELD', 'ช่างเชื่อมโลหะ'),
('ADMIN', 'บริหาร'),
('GEN', 'สามัญ');

-- เพิ่มข้อมูลตั้งค่าระบบ
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_description`, `setting_group`) VALUES
('system_name', 'STP-Prasat', 'ชื่อระบบ', 'general'),
('school_name', 'วิทยาลัยการอาชีพปราสาท', 'ชื่อสถานศึกษา', 'general'),
('school_address', '', 'ที่อยู่สถานศึกษา', 'general'),
('school_phone', '', 'เบอร์โทรสถานศึกษา', 'general'),
('school_email', '', 'อีเมลสถานศึกษา', 'general'),
('attendance_start_time', '07:30:00', 'เวลาเริ่มต้นการเช็คชื่อเข้าแถว', 'attendance'),
('attendance_end_time', '08:30:00', 'เวลาสิ้นสุดการเช็คชื่อเข้าแถว', 'attendance'),
('gps_radius', '200', 'รัศมี GPS สำหรับการเช็คชื่อ (เมตร)', 'attendance'),
('require_photo', '0', 'กำหนดให้ต้องมีรูปภาพเมื่อเช็คชื่อหรือไม่', 'attendance'),
('risk_threshold_low', '80', 'เกณฑ์ความเสี่ยงระดับต่ำ (ร้อยละ)', 'risk_management'),
('risk_threshold_medium', '70', 'เกณฑ์ความเสี่ยงระดับกลาง (ร้อยละ)', 'risk_management'),
('risk_threshold_high', '60', 'เกณฑ์ความเสี่ยงระดับสูง (ร้อยละ)', 'risk_management'),
('risk_threshold_critical', '50', 'เกณฑ์ความเสี่ยงระดับวิกฤต (ร้อยละ)', 'risk_management');

-- เพิ่มข้อมูลปีการศึกษาปัจจุบัน
INSERT INTO `academic_years` (`year`, `semester`, `start_date`, `end_date`, `required_attendance_days`, `is_active`) VALUES
(2568, 1, NOW(), DATE_ADD(NOW(), INTERVAL 4 MONTH), 80, 1);

-- -----------------------------------------------------
-- สร้าง VIEWS
-- -----------------------------------------------------

-- VIEW สำหรับดูข้อมูลชั้นเรียนพร้อมรายละเอียด
CREATE OR REPLACE VIEW `view_classes` AS
SELECT 
    c.`class_id`,
    c.`academic_year_id`,
    ay.`year` AS `academic_year`,
    ay.`semester` AS `semester`,
    c.`level`,
    c.`department_id`,
    d.`department_name`,
    c.`group_number`,
    c.`classroom`,
    (SELECT COUNT(*) FROM `students` s WHERE s.`current_class_id` = c.`class_id` AND s.`status` = 'กำลังศึกษา') AS `student_count`,
    (SELECT COUNT(*) FROM `class_advisors` ca WHERE ca.`class_id` = c.`class_id`) AS `advisor_count`,
    (SELECT GROUP_CONCAT(CONCAT(t.`title`, ' ', t.`first_name`, ' ', t.`last_name`) SEPARATOR ', ')
     FROM `class_advisors` ca
     JOIN `teachers` t ON ca.`teacher_id` = t.`teacher_id`
     WHERE ca.`class_id` = c.`class_id` AND ca.`is_primary` = 1
    ) AS `primary_advisor`,
    c.`is_active`,
    c.`created_at`,
    c.`updated_at`
FROM 
    `classes` c
    JOIN `academic_years` ay ON c.`academic_year_id` = ay.`academic_year_id`
    JOIN `departments` d ON c.`department_id` = d.`department_id`;

-- VIEW สำหรับดูข้อมูลนักเรียนพร้อมรายละเอียดชั้นเรียน
CREATE OR REPLACE VIEW `view_students_with_class` AS
SELECT 
    s.`student_id`,
    s.`user_id`,
    s.`student_code`,
    s.`title`,
    u.`first_name`,
    u.`last_name`,
    u.`phone_number`,
    u.`email`,
    s.`current_class_id`,
    c.`level`,
    d.`department_name`,
    c.`group_number`,
    ay.`year` AS `academic_year`,
    ay.`semester`,
    (SELECT COUNT(*) FROM `attendance` a 
     WHERE a.`student_id` = s.`student_id` AND a.`academic_year_id` = c.`academic_year_id` AND a.`is_present` = 1) AS `attendance_count`,
    (SELECT sar.`total_attendance_days` FROM `student_academic_records` sar 
     WHERE sar.`student_id` = s.`student_id` AND sar.`academic_year_id` = c.`academic_year_id`) AS `total_attendance_days`,
    (SELECT sar.`total_absence_days` FROM `student_academic_records` sar 
     WHERE sar.`student_id` = s.`student_id` AND sar.`academic_year_id` = c.`academic_year_id`) AS `total_absence_days`,
    (SELECT 
        CASE 
            WHEN sar.`passed_activity` IS NULL THEN 'รอประเมิน'
            WHEN sar.`passed_activity` = 1 THEN 'ผ่าน'
            ELSE 'ไม่ผ่าน'
        END
     FROM `student_academic_records` sar 
     WHERE sar.`student_id` = s.`student_id` AND sar.`academic_year_id` = c.`academic_year_id`) AS `activity_status`,
    s.`status`
FROM 
    `students` s
    JOIN `users` u ON s.`user_id` = u.`user_id`
    LEFT JOIN `classes` c ON s.`current_class_id` = c.`class_id`
    LEFT JOIN `departments` d ON c.`department_id` = d.`department_id`
    LEFT JOIN `academic_years` ay ON c.`academic_year_id` = ay.`academic_year_id`;

-- VIEW สำหรับดูข้อมูลครูที่ปรึกษาและชั้นเรียนที่รับผิดชอบ
CREATE OR REPLACE VIEW `view_advisors_with_classes` AS
SELECT 
    t.`teacher_id`,
    t.`title`,
    t.`first_name`,
    t.`last_name`,
    d.`department_name` AS `teacher_department`,
    ca.`class_id`,
    ca.`is_primary`,
    c.`level`,
    d2.`department_name` AS `class_department`,
    c.`group_number`,
    ay.`year` AS `academic_year`,
    ay.`semester`,
    (SELECT COUNT(*) FROM `students` s WHERE s.`current_class_id` = c.`class_id` AND s.`status` = 'กำลังศึกษา') AS `student_count`
FROM 
    `teachers` t
    LEFT JOIN `departments` d ON t.`department_id` = d.`department_id`
    LEFT JOIN `class_advisors` ca ON t.`teacher_id` = ca.`teacher_id`
    LEFT JOIN `classes` c ON ca.`class_id` = c.`class_id`
    LEFT JOIN `departments` d2 ON c.`department_id` = d2.`department_id`
    LEFT JOIN `academic_years` ay ON c.`academic_year_id` = ay.`academic_year_id`;

-- -----------------------------------------------------
-- สร้าง Stored Procedures
-- -----------------------------------------------------

DELIMITER //

-- Stored Procedure สำหรับอัปเดตสถานะความเสี่ยงของนักเรียน
CREATE PROCEDURE `update_student_risk_status`(
    IN p_student_id INT,
    IN p_academic_year_id INT
)
BEGIN
    DECLARE v_attendance_days INT;
    DECLARE v_absence_days INT;
    DECLARE v_total_days INT;
    DECLARE v_attendance_rate DECIMAL(5,2);
    DECLARE v_risk_level VARCHAR(20);
    DECLARE v_low_threshold INT;
    DECLARE v_medium_threshold INT;
    DECLARE v_high_threshold INT;
    DECLARE v_critical_threshold INT;
    
    -- รับค่าเกณฑ์ความเสี่ยง
    SELECT 
        CAST(setting_value AS UNSIGNED) INTO v_low_threshold
    FROM `system_settings`
    WHERE `setting_key` = 'risk_threshold_low';
    
    SELECT 
        CAST(setting_value AS UNSIGNED) INTO v_medium_threshold
    FROM `system_settings`
    WHERE `setting_key` = 'risk_threshold_medium';
    
    SELECT 
        CAST(setting_value AS UNSIGNED) INTO v_high_threshold
    FROM `system_settings`
    WHERE `setting_key` = 'risk_threshold_high';
    
    SELECT 
        CAST(setting_value AS UNSIGNED) INTO v_critical_threshold
    FROM `system_settings`
    WHERE `setting_key` = 'risk_threshold_critical';
    
    -- หากไม่มีค่า ให้กำหนดค่าเริ่มต้น
    IF v_low_threshold IS NULL THEN SET v_low_threshold = 80; END IF;
    IF v_medium_threshold IS NULL THEN SET v_medium_threshold = 70; END IF;
    IF v_high_threshold IS NULL THEN SET v_high_threshold = 60; END IF;
    IF v_critical_threshold IS NULL THEN SET v_critical_threshold = 50; END IF;
    
    -- รับข้อมูลการเข้าแถว
    SELECT 
        IFNULL(`total_attendance_days`, 0),
        IFNULL(`total_absence_days`, 0)
    INTO 
        v_attendance_days,
        v_absence_days
    FROM `student_academic_records`
    WHERE `student_id` = p_student_id
      AND `academic_year_id` = p_academic_year_id;
    
    -- คำนวณอัตราการเข้าแถว
    SET v_total_days = v_attendance_days + v_absence_days;
    
    IF v_total_days > 0 THEN
        SET v_attendance_rate = (v_attendance_days / v_total_days) * 100;
    ELSE
        SET v_attendance_rate = 100;
    END IF;
    
    -- กำหนดระดับความเสี่ยง
    IF v_attendance_rate >= v_low_threshold THEN
        SET v_risk_level = 'low';
    ELSEIF v_attendance_rate >= v_medium_threshold THEN
        SET v_risk_level = 'medium';
    ELSEIF v_attendance_rate >= v_high_threshold THEN
        SET v_risk_level = 'high';
    ELSE
        SET v_risk_level = 'critical';
    END IF;
    
    -- อัปเดตหรือเพิ่มข้อมูลในตาราง risk_students
    INSERT INTO `risk_students` (
        `student_id`,
        `academic_year_id`,
        `absence_count`,
        `risk_level`,
        `updated_at`
    ) VALUES (
        p_student_id,
        p_academic_year_id,
        v_absence_days,
        v_risk_level,
        CURRENT_TIMESTAMP
    )
    ON DUPLICATE KEY UPDATE
        `absence_count` = v_absence_days,
        `risk_level` = v_risk_level,
        `updated_at` = CURRENT_TIMESTAMP;
END//

-- Stored Procedure สำหรับการเลื่อนชั้นนักเรียน
CREATE PROCEDURE `promote_students`(
    IN p_from_academic_year_id INT,
    IN p_to_academic_year_id INT,
    IN p_admin_id INT,
    IN p_notes TEXT
)
BEGIN
    DECLARE v_batch_id INT;
    DECLARE v_student_count INT DEFAULT 0;
    DECLARE v_graduate_count INT DEFAULT 0;
    
    -- เริ่ม transaction
    START TRANSACTION;
    
    -- สร้างรายการ batch
    INSERT INTO `student_promotion_batch` (
        `from_academic_year_id`,
        `to_academic_year_id`,
        `status`,
        `notes`,
        `created_by`
    ) VALUES (
        p_from_academic_year_id,
        p_to_academic_year_id,
        'in_progress',
        p_notes,
        p_admin_id
    );
    
    -- รับ batch_id ที่เพิ่งสร้าง
    SET v_batch_id = LAST_INSERT_ID();
    
    -- บันทึกการดำเนินการในตาราง admin_actions
    INSERT INTO `admin_actions` (
        `admin_id`,
        `action_type`,
        `action_details`
    ) VALUES (
        p_admin_id,
        'promote_students',
        CONCAT('{"batch_id":', v_batch_id, ', "from_academic_year":', p_from_academic_year_id, ', "to_academic_year":', p_to_academic_year_id, '}')
    );
    
    -- โค้ดเลื่อนชั้นนักเรียนจะอยู่ตรงนี้
    -- (ส่วนนี้จะต้องเขียนเพิ่มเติมตามตรรกะการเลื่อนชั้นที่ต้องการ)
    
    -- อัปเดตสถานะ batch
    UPDATE `student_promotion_batch`
    SET 
        `status` = 'completed',
        `students_count` = v_student_count,
        `graduates_count` = v_graduate_count
    WHERE `batch_id` = v_batch_id;
    
    -- Commit transaction
    COMMIT;
    
    -- ส่งค่า batch_id กลับ
    SELECT v_batch_id AS batch_id;
END//

DELIMITER ;

-- -----------------------------------------------------
-- สร้าง Triggers
-- -----------------------------------------------------

DELIMITER //

-- Trigger สำหรับอัปเดตข้อมูลสถิติการเข้าแถวเมื่อมีการบันทึกการเข้าแถว
CREATE TRIGGER `after_attendance_insert` 
AFTER INSERT ON `attendance`
FOR EACH ROW
BEGIN
    -- อัปเดตสถิติการเข้าแถวในตาราง student_academic_records
    IF NEW.`is_present` = 1 THEN
        UPDATE `student_academic_records`
        SET `total_attendance_days` = `total_attendance_days` + 1,
            `updated_at` = CURRENT_TIMESTAMP
        WHERE `student_id` = NEW.`student_id` 
          AND `academic_year_id` = NEW.`academic_year_id`;
    ELSE
        UPDATE `student_academic_records`
        SET `total_absence_days` = `total_absence_days` + 1,
            `updated_at` = CURRENT_TIMESTAMP
        WHERE `student_id` = NEW.`student_id` 
          AND `academic_year_id` = NEW.`academic_year_id`;
    END IF;
    
    -- ตรวจสอบและอัปเดตสถานะความเสี่ยง
    CALL update_student_risk_status(NEW.`student_id`, NEW.`academic_year_id`);
END//

DELIMITER ;