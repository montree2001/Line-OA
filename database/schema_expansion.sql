-- ==========================================
-- Schema Expansion for AI Student Care System
-- ระบบน้องห่วงใย AI ดูแลผู้เรียน
-- ==========================================

-- 1. Academic Results Tables (ตารางผลการเรียน)
-- ==========================================

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL COMMENT 'รหัสวิชา',
  `subject_name` varchar(255) NOT NULL COMMENT 'ชื่อวิชา',
  `credits` int(11) NOT NULL COMMENT 'หน่วยกิต',
  `department_id` int(11) NOT NULL COMMENT 'แผนกวิชา',
  `semester` tinyint(1) NOT NULL COMMENT 'ภาคเรียน',
  `year_level` enum('ปวช.1','ปวช.2','ปวช.3','ปวส.1','ปวส.2') NOT NULL COMMENT 'ระดับชั้น',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL AUTO_INCREMENT,
  `subject_id` int(11) NOT NULL COMMENT 'รหัสวิชา',
  `teacher_id` int(11) NOT NULL COMMENT 'รหัสครูผู้สอน',
  `academic_year_id` int(11) NOT NULL COMMENT 'ปีการศึกษา',
  `section` varchar(10) DEFAULT NULL COMMENT 'หมู่เรียน',
  `schedule_day` enum('จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์','อาทิตย์') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL COMMENT 'ห้องเรียน',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`class_id`),
  FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`subject_id`),
  FOREIGN KEY (`teacher_id`) REFERENCES `teachers`(`teacher_id`),
  FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years`(`academic_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `class_enrollments` (
  `enrollment_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('enrolled','dropped','completed') NOT NULL DEFAULT 'enrolled',
  PRIMARY KEY (`enrollment_id`),
  UNIQUE KEY `unique_enrollment` (`class_id`, `student_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `class_attendance` (
  `attendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','late','excused') NOT NULL DEFAULT 'absent',
  `pin_code` varchar(4) DEFAULT NULL COMMENT '4-digit PIN for attendance',
  `check_in_time` datetime DEFAULT NULL,
  `recorded_by` int(11) NOT NULL COMMENT 'ครูที่บันทึก',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `unique_daily_attendance` (`class_id`, `student_id`, `attendance_date`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
  FOREIGN KEY (`recorded_by`) REFERENCES `teachers`(`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `academic_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `midterm_score` decimal(5,2) DEFAULT NULL COMMENT 'คะแนนกลางภาค',
  `final_score` decimal(5,2) DEFAULT NULL COMMENT 'คะแนนปลายภาค',
  `assignment_score` decimal(5,2) DEFAULT NULL COMMENT 'คะแนนงาน',
  `total_score` decimal(5,2) DEFAULT NULL COMMENT 'คะแนนรวม',
  `grade` varchar(2) DEFAULT NULL COMMENT 'เกรด (A, B+, B, C+, C, D+, D, F)',
  `grade_point` decimal(3,2) DEFAULT NULL COMMENT 'คะแนนเกรด',
  `status` enum('pass','fail','incomplete','withdraw') DEFAULT NULL,
  `submitted_by` int(11) NOT NULL COMMENT 'ครูที่ส่งผล',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`result_id`),
  UNIQUE KEY `unique_result` (`student_id`, `class_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
  FOREIGN KEY (`class_id`) REFERENCES `classes`(`class_id`),
  FOREIGN KEY (`submitted_by`) REFERENCES `teachers`(`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Health and Mental Health Tables (ตารางสุขภาพและสุขภาพจิต)
-- ==========================================

CREATE TABLE `health_records` (
  `health_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `height` decimal(5,2) DEFAULT NULL COMMENT 'ส่วนสูง (ซม.)',
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'น้ำหนัก (กก.)',
  `blood_pressure_systolic` int(11) DEFAULT NULL COMMENT 'ความดันโลหิตตัวบน',
  `blood_pressure_diastolic` int(11) DEFAULT NULL COMMENT 'ความดันโลหิตตัวล่าง',
  `heart_rate` int(11) DEFAULT NULL COMMENT 'อัตราการเต้นของหัวใจ',
  `blood_sugar` decimal(5,2) DEFAULT NULL COMMENT 'ระดับน้ำตาลในเลือด',
  `chronic_diseases` text DEFAULT NULL COMMENT 'โรคประจำตัว',
  `allergies` text DEFAULT NULL COMMENT 'ภูมิแพ้',
  `medications` text DEFAULT NULL COMMENT 'ยาที่ใช้ประจำ',
  `emergency_contact` varchar(255) DEFAULT NULL COMMENT 'ผู้ติดต่อฉุกเฉิน',
  `emergency_phone` varchar(20) DEFAULT NULL COMMENT 'เบอร์ติดต่อฉุกเฉิน',
  `recorded_date` date NOT NULL,
  `recorded_by` int(11) DEFAULT NULL COMMENT 'ผู้บันทึก (student_id หรือ teacher_id)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`health_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `mental_health_assessments` (
  `assessment_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `depression_score` int(11) DEFAULT NULL COMMENT 'คะแนนซึมเศร้า (0-27)',
  `anxiety_score` int(11) DEFAULT NULL COMMENT 'คะแนนวิตกกังวล (0-21)',
  `stress_score` int(11) DEFAULT NULL COMMENT 'คะแนนความเครียด (0-21)',
  `sleep_quality` enum('ดีมาก','ดี','ปานกลาง','แย่','แย่มาก') DEFAULT NULL,
  `appetite_changes` enum('ไม่เปลี่ยนแปลง','เพิ่มขึ้น','ลดลง') DEFAULT NULL,
  `social_withdrawal` tinyint(1) DEFAULT 0 COMMENT 'หลีกเลี่ยงสังคม',
  `academic_performance_concern` tinyint(1) DEFAULT 0 COMMENT 'กังวลผลการเรียน',
  `family_issues` tinyint(1) DEFAULT 0 COMMENT 'ปัญหาครอบครัว',
  `financial_stress` tinyint(1) DEFAULT 0 COMMENT 'ความเครียดทางการเงิน',
  `risk_level` enum('ต่ำ','ปานกลาง','สูง','วิกฤต') NOT NULL DEFAULT 'ต่ำ',
  `recommendations` text DEFAULT NULL COMMENT 'คำแนะนำ',
  `follow_up_required` tinyint(1) DEFAULT 0 COMMENT 'ต้องติดตาม',
  `follow_up_date` date DEFAULT NULL,
  `assessed_by` enum('self','teacher','counselor','ai') NOT NULL DEFAULT 'self',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assessment_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. AI Chat Bot Tables (ตาราง AI Chat Bot)
-- ==========================================

CREATE TABLE `ai_chat_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('student','parent','teacher','admin') NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_time` timestamp NULL DEFAULT NULL,
  `session_duration` int(11) DEFAULT NULL COMMENT 'ระยะเวลาการสนทนา (นาที)',
  `topic_category` enum('academic','health','personal','technical','general') DEFAULT NULL,
  `mood_start` enum('very_positive','positive','neutral','negative','very_negative') DEFAULT NULL,
  `mood_end` enum('very_positive','positive','neutral','negative','very_negative') DEFAULT NULL,
  `satisfaction_score` int(11) DEFAULT NULL COMMENT 'คะแนนความพอใจ (1-5)',
  `is_resolved` tinyint(1) DEFAULT 0 COMMENT 'ปัญหาได้รับการแก้ไข',
  PRIMARY KEY (`session_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ai_chat_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('user','ai') NOT NULL,
  `message_text` text NOT NULL,
  `message_type` enum('text','image','file','quick_reply') DEFAULT 'text',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `sentiment_score` decimal(3,2) DEFAULT NULL COMMENT 'คะแนนอารมณ์ (-1 ถึง 1)',
  `intent_category` varchar(50) DEFAULT NULL COMMENT 'หมวดหมู่ความตั้งใจ',
  `confidence_score` decimal(3,2) DEFAULT NULL COMMENT 'ความมั่นใจของ AI',
  `requires_followup` tinyint(1) DEFAULT 0 COMMENT 'ต้องติดตาม',
  PRIMARY KEY (`message_id`),
  FOREIGN KEY (`session_id`) REFERENCES `ai_chat_sessions`(`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ai_analysis_results` (
  `analysis_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `analysis_date` date NOT NULL,
  `chat_frequency` int(11) DEFAULT 0 COMMENT 'ความถี่ในการแชท (ครั้ง/สัปดาห์)',
  `avg_session_duration` int(11) DEFAULT 0 COMMENT 'เวลาเฉลี่ยต่อการสนทนา (นาที)',
  `negative_sentiment_ratio` decimal(3,2) DEFAULT 0 COMMENT 'อัตราส่วนอารมณ์เชิงลบ',
  `academic_concern_mentions` int(11) DEFAULT 0 COMMENT 'การพูดถึงปัญหาการเรียน',
  `health_concern_mentions` int(11) DEFAULT 0 COMMENT 'การพูดถึงปัญหาสุขภาพ',
  `family_concern_mentions` int(11) DEFAULT 0 COMMENT 'การพูดถึงปัญหาครอบครัว',
  `social_concern_mentions` int(11) DEFAULT 0 COMMENT 'การพูดถึงปัญหาสังคม',
  `overall_risk_score` decimal(3,2) DEFAULT 0 COMMENT 'คะแนนความเสี่ยงรวม (0-1)',
  `ai_recommendations` text DEFAULT NULL COMMENT 'คำแนะนำจาก AI',
  `alert_triggered` tinyint(1) DEFAULT 0 COMMENT 'เกิดการแจ้งเตือน',
  `reviewed_by_human` tinyint(1) DEFAULT 0 COMMENT 'มนุษย์ตรวจสอบแล้ว',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`analysis_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Notification and Alert Tables (ตารางการแจ้งเตือน)
-- ==========================================

CREATE TABLE `care_alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `alert_type` enum('academic','health','mental_health','attendance','behavioral') NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `triggered_by` enum('system','teacher','ai_analysis','manual') NOT NULL,
  `triggered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('new','in_progress','resolved','dismissed') NOT NULL DEFAULT 'new',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'มอบหมายให้ครู/ที่ปรึกษา',
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT 0,
  `parent_notification_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`alert_id`),
  FOREIGN KEY (`student_id`) REFERENCES `students`(`student_id`),
  FOREIGN KEY (`assigned_to`) REFERENCES `teachers`(`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. System Configuration Tables (ตารางการตั้งค่าระบบ)
-- ==========================================

CREATE TABLE `ai_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default AI settings
INSERT INTO `ai_settings` (`setting_key`, `setting_value`, `description`, `updated_by`) VALUES
('depression_threshold_medium', '10', 'คะแนนซึมเศร้าระดับปานกลาง', 1),
('depression_threshold_high', '15', 'คะแนนซึมเศร้าระดับสูง', 1),
('depression_threshold_critical', '20', 'คะแนนซึมเศร้าระดับวิกฤต', 1),
('chat_frequency_concern', '15', 'ความถี่การแชทที่น่ากังวล (ครั้ง/สัปดาห์)', 1),
('negative_sentiment_threshold', '0.6', 'เกณฑ์อารมณ์เชิงลบที่น่ากังวล', 1),
('ai_analysis_frequency', '7', 'ความถี่การวิเคราะห์ AI (วัน)', 1);

-- 6. View for comprehensive student data
-- ==========================================

CREATE VIEW `view_student_care_summary` AS
SELECT
    s.student_id,
    s.student_code,
    u.first_name,
    u.last_name,
    c.department_name,
    c.level,

    -- Academic Performance
    ROUND(AVG(ar.grade_point), 2) as avg_gpa,
    COUNT(CASE WHEN ar.status = 'fail' THEN 1 END) as failed_subjects,

    -- Attendance
    rs.absence_count,
    rs.risk_level as attendance_risk,

    -- Health Status
    mha.risk_level as mental_health_risk,
    mha.depression_score,
    mha.follow_up_required,

    -- AI Chat Analysis
    aar.overall_risk_score as ai_risk_score,
    aar.negative_sentiment_ratio,
    aar.alert_triggered as ai_alert_active,

    -- Active Alerts
    COUNT(ca.alert_id) as active_alerts,
    MAX(ca.severity) as highest_alert_severity

FROM students s
JOIN users u ON s.user_id = u.user_id
LEFT JOIN classes_view c ON s.current_class_id = c.class_id
LEFT JOIN risk_students rs ON s.student_id = rs.student_id AND rs.academic_year_id = 1
LEFT JOIN academic_results ar ON s.student_id = ar.student_id
LEFT JOIN mental_health_assessments mha ON s.student_id = mha.student_id
    AND mha.assessment_date = (
        SELECT MAX(assessment_date)
        FROM mental_health_assessments
        WHERE student_id = s.student_id
    )
LEFT JOIN ai_analysis_results aar ON s.student_id = aar.student_id
    AND aar.analysis_date = (
        SELECT MAX(analysis_date)
        FROM ai_analysis_results
        WHERE student_id = s.student_id
    )
LEFT JOIN care_alerts ca ON s.student_id = ca.student_id
    AND ca.status IN ('new', 'in_progress')
GROUP BY s.student_id;