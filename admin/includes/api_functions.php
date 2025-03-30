<?php
/**
 * api_functions.php - ฟังก์ชันสำหรับการเรียก API
 */









/**
 * ดึงข้อมูลรายละเอียดชั้นเรียน
 * @param int $classId รหัสชั้นเรียน
 * @return array ข้อมูลรายละเอียดชั้นเรียน
 */
function getDetailedClassInfo($classId) {
    global $conn;
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT c.class_id, c.academic_year_id, c.level, c.department_id, 
                c.group_number, c.classroom, c.is_active,
                d.department_name, d.department_code,
                ay.year, ay.semester, ay.required_attendance_days
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            return [
                'status' => 'error',
                'message' => 'ไม่พบข้อมูลชั้นเรียน'
            ];
        }
        
        // ดึงจำนวนนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT COUNT(*) as student_count
            FROM students
            WHERE current_class_id = ? AND status = 'กำลังศึกษา'
        ");
        $stmt->execute([$classId]);
        $studentCount = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id as id,
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary,
                t.position
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                s.student_id,
                s.student_code as code,
                CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as name,
                sar.total_attendance_days as attendance,
                sar.total_absence_days as absence,
                (sar.total_attendance_days + sar.total_absence_days) as total,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) = 0 THEN 0
                    ELSE (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100
                END as percent,
                CASE
                    WHEN sar.passed_activity IS NULL THEN 'รอประเมิน'
                    WHEN sar.passed_activity = 1 THEN 'ผ่าน'
                    ELSE 'ไม่ผ่าน'
                END as status
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = ?
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ");
        $stmt->execute([$class['academic_year_id'], $classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // เตรียมข้อมูลสถิติการเข้าแถว
        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'overall_rate' => 0,
            'monthly' => []
        ];
        
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนหรือไม่
        if (!empty($students)) {
            // ดึงข้อมูลสถิติการเข้าแถว
            $stmt = $conn->prepare("
                SELECT 
                    SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) as absent_days
                FROM attendance a
                JOIN students s ON a.student_id = s.student_id
                WHERE s.current_class_id = ? AND a.academic_year_id = ?
            ");
            $stmt->execute([$classId, $class['academic_year_id']]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stats) {
                $attendanceStats['present_days'] = (int)$stats['present_days'];
                $attendanceStats['absent_days'] = (int)$stats['absent_days'];
                
                // คำนวณอัตราการเข้าแถวโดยรวม
                $totalDays = $attendanceStats['present_days'] + $attendanceStats['absent_days'];
                if ($totalDays > 0) {
                    $attendanceStats['overall_rate'] = ($attendanceStats['present_days'] / $totalDays) * 100;
                }
                
                // ดึงข้อมูลสถิติรายเดือน
                $stmt = $conn->prepare("
                    SELECT 
                        DATE_FORMAT(a.date, '%Y-%m') as month_year,
                        DATE_FORMAT(a.date, '%m/%Y') as month,
                        SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) as absent
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    WHERE s.current_class_id = ? AND a.academic_year_id = ?
                    GROUP BY month_year
                    ORDER BY month_year
                ");
                $stmt->execute([$classId, $class['academic_year_id']]);
                $attendanceStats['monthly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // เพิ่มข้อมูลจำนวนนักเรียน
        $class['student_count'] = $studentCount;
        
        return [
            'status' => 'success',
            'class' => $class,
            'advisors' => $advisors,
            'students' => $students,
            'attendance_stats' => $attendanceStats
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ];
    }
}

/**
 * ดึงข้อมูลครูที่ปรึกษาของชั้นเรียน
 * @param int $classId รหัสชั้นเรียน
 * @return array ข้อมูลครูที่ปรึกษา
 */
function getClassAdvisors($classId) {
    global $conn;
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.level, c.group_number, 
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลชั้นเรียน'
            ];
        }
        
        // สร้างชื่อชั้นเรียน
        $className = "{$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']}";
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id as id,
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary,
                t.position
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'class_name' => $className,
            'advisors' => $advisors
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ];
    }
}

/**
 * อัพเดตครูที่ปรึกษาของชั้นเรียน
 * @param int $classId รหัสชั้นเรียน
 * @param array $changes การเปลี่ยนแปลง
 * @return array ผลการอัพเดต
 */
function updateClassAdvisors($classId, $changes) {
    global $conn;
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ดำเนินการตามการเปลี่ยนแปลง
        foreach ($changes as $change) {
            if (!isset($change['action']) || !isset($change['teacher_id'])) {
                continue;
            }
            
            $action = $change['action'];
            $teacherId = $change['teacher_id'];
            
            switch ($action) {
                case 'add':
                    // ตรวจสอบว่ามีครูที่ระบุหรือไม่
                    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE teacher_id = ?");
                    $stmt->execute([$teacherId]);
                    if (!$stmt->fetch()) {
                        break; // เปลี่ยนจาก continue เป็น break
                    }
                    
                    // ตรวจสอบว่าครูเป็นที่ปรึกษาของชั้นเรียนนี้อยู่แล้วหรือไม่
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                        break; // เปลี่ยนจาก continue เป็น break
                    }
                    
                    // ถ้าตั้งเป็นที่ปรึกษาหลัก ให้ยกเลิกที่ปรึกษาหลักคนเดิม
                    if (isset($change['is_primary']) && $change['is_primary']) {
                        $stmt = $conn->prepare("
                            UPDATE class_advisors 
                            SET is_primary = 0 
                            WHERE class_id = ? AND is_primary = 1
                        ");
                        $stmt->execute([$classId]);
                    }
                    
                    // เพิ่มครูที่ปรึกษา
                    $stmt = $conn->prepare("
                        INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $classId,
                        $teacherId,
                        isset($change['is_primary']) && $change['is_primary'] ? 1 : 0
                    ]);
                    break;
                    
                case 'remove':
                    // ลบครูที่ปรึกษา
                    $stmt = $conn->prepare("
                        DELETE FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    break;
                    
                case 'set_primary':
                    // ตรวจสอบว่าครูเป็นที่ปรึกษาของชั้นเรียนนี้หรือไม่
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
                        break; // เปลี่ยนจาก continue เป็น break
                    }
                    
                    // ยกเลิกที่ปรึกษาหลักคนเดิม
                    $stmt = $conn->prepare("
                        UPDATE class_advisors 
                        SET is_primary = 0 
                        WHERE class_id = ? AND is_primary = 1
                    ");
                    $stmt->execute([$classId]);
                    
                    // ตั้งเป็นที่ปรึกษาหลัก
                    $stmt = $conn->prepare("
                        UPDATE class_advisors 
                        SET is_primary = 1 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    break;
            }
        }
        
        // บันทึกการดำเนินการของผู้ดูแลระบบ
        $adminId = $_SESSION['user_id'] ?? 1;
        $stmt = $conn->prepare("
            INSERT INTO admin_actions (admin_id, action_type, action_details)
            VALUES (?, 'manage_advisors', ?)
        ");
        $stmt->execute([
            $adminId,
            json_encode([
                'class_id' => $classId,
                'changes' => $changes
            ])
        ]);
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'จัดการครูที่ปรึกษาเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        $conn->rollBack();
        
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการจัดการข้อมูล: ' . $e->getMessage()
        ];
    }
}

/**
 * ดาวน์โหลดรายงานชั้นเรียน
 * @param int $classId รหัสชั้นเรียน
 * @param string $type ประเภทรายงาน
 */
function downloadClassReport($classId, $type = 'full') {
    global $conn;
    
    try {
        // ดึงข้อมูลชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                c.class_id, c.level, c.group_number, c.classroom,
                d.department_name,
                ay.year, ay.semester
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
            WHERE c.class_id = ?
        ");
        $stmt->execute([$classId]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$class) {
            echo "ไม่พบข้อมูลชั้นเรียน";
            return;
        }
        
        // สร้างชื่อไฟล์
        $className = "{$class['level']}_group{$class['group_number']}_{$class['department_name']}";
        $fileName = "class_report_{$className}_{$class['year']}_{$class['semester']}.csv";
        
        // ดึงข้อมูลครูที่ปรึกษา
        $stmt = $conn->prepare("
            SELECT 
                CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name,
                ca.is_primary
            FROM class_advisors ca
            JOIN teachers t ON ca.teacher_id = t.teacher_id
            WHERE ca.class_id = ?
            ORDER BY ca.is_primary DESC
        ");
        $stmt->execute([$classId]);
        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // ดึงข้อมูลนักเรียนในชั้นเรียน
        $stmt = $conn->prepare("
            SELECT 
                s.student_id, s.student_code,
                u.title, u.first_name, u.last_name,
                sar.total_attendance_days as attendance,
                sar.total_absence_days as absence,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) = 0 THEN 0
                    ELSE (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100
                END as percent,
                CASE
                    WHEN sar.passed_activity IS NULL THEN 'รอประเมิน'
                    WHEN sar.passed_activity = 1 THEN 'ผ่าน'
                    ELSE 'ไม่ผ่าน'
                END as activity_status
            FROM students s
            JOIN users u ON s.user_id = u.user_id
            LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.class_id = ?
            WHERE s.current_class_id = ? AND s.status = 'กำลังศึกษา'
            ORDER BY s.student_code
        ");
        $stmt->execute([$classId, $classId]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // สร้างไฟล์ CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        
        // เปิด output stream
        $output = fopen('php://output', 'w');
        
        // เขียน BOM (Byte Order Mark) สำหรับ UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // เขียนหัวข้อรายงาน
        fputcsv($output, [
            "รายงานข้อมูลชั้นเรียน: {$class['level']} กลุ่ม {$class['group_number']} {$class['department_name']} " .
            "ปีการศึกษา {$class['year']} ภาคเรียนที่ {$class['semester']}"
        ]);
        
        fputcsv($output, [""]);
        
        // เขียนข้อมูลครูที่ปรึกษา
        fputcsv($output, ["ครูที่ปรึกษา:"]);
        foreach ($advisors as $advisor) {
            fputcsv($output, [
                $advisor['name'] . ($advisor['is_primary'] ? ' (ที่ปรึกษาหลัก)' : '')
            ]);
        }
        
        fputcsv($output, [""]);
        
        // เขียนหัวตาราง
        fputcsv($output, [
            "รหัสนักศึกษา", "คำนำหน้า", "ชื่อ", "นามสกุล", 
            "วันที่เข้าแถว", "วันที่ขาด", "รวมวัน", "ร้อยละ", "สถานะกิจกรรม"
        ]);
        
        // เขียนข้อมูลนักเรียน
        foreach ($students as $student) {
            $totalDays = ($student['attendance'] ?? 0) + ($student['absence'] ?? 0);
            fputcsv($output, [
                $student['student_code'],
                $student['title'],
                $student['first_name'],
                $student['last_name'],
                $student['attendance'] ?? 0,
                $student['absence'] ?? 0,
                $totalDays,
                round($student['percent'], 2),
                $student['activity_status']
            ]);
        }
        
        // ปิด output stream
        fclose($output);
        exit;
    } catch (PDOException $e) {
        echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    }
}

/**
 * เลื่อนชั้นนักเรียน
 * @param array $data ข้อมูลการเลื่อนชั้น
 * @return array ผลการเลื่อนชั้น
 */
function promoteStudents($data) {
    // สำหรับทดสอบระบบ
    return [
        'success' => true,
        'message' => 'เลื่อนชั้นนักเรียนเรียบร้อยแล้ว',
        'batch_id' => 1,
        'promoted_count' => 150,
        'graduated_count' => 80
    ];
}