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
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
        ];
    }
    
    // ตรวจสอบค่า parameter
    if (empty($classId)) {
        return [
            'status' => 'error',
            'message' => 'ไม่ได้ระบุรหัสชั้นเรียน'
        ];
    }
    
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
                (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) as total,
                CASE 
                    WHEN (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) = 0 THEN 0
                    ELSE (COALESCE(sar.total_attendance_days, 0) / (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0))) * 100
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
        
        // แปลงค่า percent เป็นตัวเลขที่ถูกต้อง
        foreach ($students as &$student) {
            // ตรวจสอบว่า percent เป็นตัวเลข และถ้าไม่ใช่ให้แปลงเป็น 0
            if (isset($student['percent'])) {
                $student['percent'] = floatval($student['percent']);
            } else {
                $student['percent'] = 0;
            }
            
            // ตรวจสอบค่าอื่นๆ ให้แน่ใจว่าไม่เป็น null
            $student['attendance'] = intval($student['attendance'] ?? 0);
            $student['absence'] = intval($student['absence'] ?? 0);
            $student['total'] = intval($student['total'] ?? 0);
            $student['code'] = $student['code'] ?? '';
            $student['name'] = $student['name'] ?? '';
            $student['status'] = $student['status'] ?? 'รอประเมิน';
        }
        
        // ข้อมูลสถิติการเข้าแถว
        $attendanceStats = [
            'present_days' => 0,
            'absent_days' => 0,
            'overall_rate' => 0,
            'monthly' => []
        ];
        
        // ตรวจสอบว่ามีนักเรียนในชั้นเรียนหรือไม่
        if (!empty($students)) {
            try {
                // ดึงข้อมูลสถิติการเข้าแถว
                $stmt = $conn->prepare("
                    SELECT 
                        SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present_days,
                        SUM(CASE WHEN a.attendance_status IN ('absent', 'late', 'leave') THEN 1 ELSE 0 END) as absent_days
                    FROM attendance a
                    JOIN students s ON a.student_id = s.student_id
                    WHERE s.current_class_id = ? AND a.academic_year_id = ?
                ");
                $stmt->execute([$classId, $class['academic_year_id']]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($stats) {
                    $attendanceStats['present_days'] = intval($stats['present_days'] ?? 0);
                    $attendanceStats['absent_days'] = intval($stats['absent_days'] ?? 0);
                    
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
                            SUM(CASE WHEN a.attendance_status = 'present' THEN 1 ELSE 0 END) as present,
                            SUM(CASE WHEN a.attendance_status IN ('absent', 'late', 'leave') THEN 1 ELSE 0 END) as absent
                        FROM attendance a
                        JOIN students s ON a.student_id = s.student_id
                        WHERE s.current_class_id = ? AND a.academic_year_id = ?
                        GROUP BY month_year
                        ORDER BY month_year
                    ");
                    $stmt->execute([$classId, $class['academic_year_id']]);
                    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // แปลงค่าให้เป็นตัวเลขทั้งหมด
                    foreach ($monthlyStats as &$month) {
                        $month['present'] = intval($month['present'] ?? 0);
                        $month['absent'] = intval($month['absent'] ?? 0);
                    }
                    
                    $attendanceStats['monthly'] = $monthlyStats;
                }
            } catch (PDOException $e) {
                error_log("Error fetching attendance stats: " . $e->getMessage());
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
        error_log("Error in getDetailedClassInfo: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Unexpected error in getDetailedClassInfo: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด: ' . $e->getMessage()
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
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        return [
            'status' => 'error',
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
        ];
    }
    
    // ตรวจสอบค่า parameter
    if (empty($classId)) {
        return [
            'status' => 'error',
            'message' => 'ไม่ได้ระบุรหัสชั้นเรียน'
        ];
    }
    
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
                'status' => 'error',
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
            'status' => 'success',
            'class_name' => $className,
            'advisors' => $advisors
        ];
    } catch (PDOException $e) {
        error_log("Error in getClassAdvisors: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Unexpected error in getClassAdvisors: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด: ' . $e->getMessage()
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
    
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    if (!$conn) {
        return [
            'success' => false,
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
        ];
    }
    
    // ตรวจสอบค่า parameter
    if (empty($classId) || !is_array($changes)) {
        return [
            'success' => false,
            'message' => 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบพารามิเตอร์'
        ];
    }
    
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
                        break; // ข้ามไปรายการถัดไป
                    }
                    
                    // ตรวจสอบว่าครูเป็นที่ปรึกษาของชั้นเรียนนี้อยู่แล้วหรือไม่
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM class_advisors 
                        WHERE class_id = ? AND teacher_id = ?
                    ");
                    $stmt->execute([$classId, $teacherId]);
                    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                        break; // ข้ามไปรายการถัดไป
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
                        break; // ข้ามไปรายการถัดไป
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
            ], JSON_UNESCAPED_UNICODE)
        ]);
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'จัดการครูที่ปรึกษาเรียบร้อยแล้ว'
        ];
    } catch (PDOException $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Error in updateClassAdvisors: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการจัดการข้อมูล: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        error_log("Unexpected error in updateClassAdvisors: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด: ' . $e->getMessage()
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
                    WHEN (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0)) = 0 THEN 0
                    ELSE (COALESCE(sar.total_attendance_days, 0) / (COALESCE(sar.total_attendance_days, 0) + COALESCE(sar.total_absence_days, 0))) * 100
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
                round($student['percent'] ?? 0, 2),
                $student['activity_status'] ?? 'รอประเมิน'
            ]);
        }
        
        // ปิด output stream
        fclose($output);
        exit;
    } catch (PDOException $e) {
        error_log("Error in downloadClassReport: " . $e->getMessage());
        header('Content-Type: text/plain; charset=utf-8');
        echo "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Unexpected error in downloadClassReport: " . $e->getMessage());
        header('Content-Type: text/plain; charset=utf-8');
        echo "เกิดข้อผิดพลาดที่ไม่คาดคิด: " . $e->getMessage();
    }
}

/**
 * เลื่อนชั้นนักเรียน
 * @param array $data ข้อมูลการเลื่อนชั้น
 * @return array ผลการเลื่อนชั้น
 */
function promoteStudents($data) {
    global $conn;
    
    try {
        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($data['from_academic_year_id']) || empty($data['to_academic_year_id'])) {
            return ['success' => false, 'message' => 'กรุณาระบุปีการศึกษาต้นทางและปลายทาง'];
        }
        
        $fromAcademicYearId = $data['from_academic_year_id'];
        $toAcademicYearId = $data['to_academic_year_id'];
        $notes = $data['notes'] ?? '';
        $adminId = $data['admin_id'] ?? ($_SESSION['user_id'] ?? 1);
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // สร้างบันทึกการเลื่อนชั้น
        $batchQuery = "INSERT INTO student_promotion_batch (
                         from_academic_year_id,
                         to_academic_year_id,
                         status,
                         notes,
                         created_by
                     ) VALUES (
                         :from_academic_year_id,
                         :to_academic_year_id,
                         'in_progress',
                         :notes,
                         :admin_id
                     )";
        $batchStmt = $conn->prepare($batchQuery);
        $batchStmt->bindParam(':from_academic_year_id', $fromAcademicYearId, PDO::PARAM_INT);
        $batchStmt->bindParam(':to_academic_year_id', $toAcademicYearId, PDO::PARAM_INT);
        $batchStmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $batchStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        $batchStmt->execute();
        
        $batchId = $conn->lastInsertId();
        
        // ดึงข้อมูลชั้นเรียนปัจจุบันทั้งหมดในปีการศึกษาต้นทาง
        $classesQuery = "SELECT 
                        c.class_id, 
                        c.level, 
                        c.department_id, 
                        c.group_number,
                        d.department_name
                        FROM classes c
                        JOIN departments d ON c.department_id = d.department_id
                        WHERE c.academic_year_id = :from_academic_year_id";
        $classesStmt = $conn->prepare($classesQuery);
        $classesStmt->bindParam(':from_academic_year_id', $fromAcademicYearId, PDO::PARAM_INT);
        $classesStmt->execute();
        $classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // สร้างชั้นเรียนใหม่ในปีการศึกษาปลายทาง
        $newClasses = [];
        $studentCount = 0;
        $graduateCount = 0;
        
        foreach ($classes as $class) {
            // กำหนดระดับชั้นใหม่
            $currentLevel = $class['level'];
            $newLevel = null;
            $promotionType = 'promotion';
            
            switch ($currentLevel) {
                case 'ปวช.1':
                    $newLevel = 'ปวช.2';
                    break;
                case 'ปวช.2':
                    $newLevel = 'ปวช.3';
                    break;
                case 'ปวส.1':
                    $newLevel = 'ปวส.2';
                    break;
                case 'ปวช.3':
                case 'ปวส.2':
                    $newLevel = $currentLevel; // ยังคงระดับชั้นเดิม แต่จะเปลี่ยนสถานะเป็นสำเร็จการศึกษา
                    $promotionType = 'graduation';
                    break;
                default:
                    $newLevel = $currentLevel;
                    break;
            }
            
            if ($newLevel && $promotionType === 'promotion') {
                // ตรวจสอบว่ามีชั้นเรียนใหม่ในปีการศึกษาปลายทางหรือไม่
                $newClassQuery = "SELECT class_id 
                               FROM classes 
                               WHERE academic_year_id = :to_academic_year_id 
                               AND level = :level 
                               AND department_id = :department_id 
                               AND group_number = :group_number";
                $newClassStmt = $conn->prepare($newClassQuery);
                $newClassStmt->bindParam(':to_academic_year_id', $toAcademicYearId, PDO::PARAM_INT);
                $newClassStmt->bindParam(':level', $newLevel, PDO::PARAM_STR);
                $newClassStmt->bindParam(':department_id', $class['department_id'], PDO::PARAM_INT);
                $newClassStmt->bindParam(':group_number', $class['group_number'], PDO::PARAM_INT);
                $newClassStmt->execute();
                
                if ($newClassStmt->rowCount() > 0) {
                    // ถ้ามีชั้นเรียนใหม่แล้ว
                    $newClass = $newClassStmt->fetch(PDO::FETCH_ASSOC);
                    $newClassId = $newClass['class_id'];
                } else {
                    // สร้างชั้นเรียนใหม่
                    $createClassQuery = "INSERT INTO classes (
                                       academic_year_id,
                                       level,
                                       department_id,
                                       group_number,
                                       is_active
                                   ) VALUES (
                                       :academic_year_id,
                                       :level,
                                       :department_id,
                                       :group_number,
                                       1
                                   )";
                    $createClassStmt = $conn->prepare($createClassQuery);
                    $createClassStmt->bindParam(':academic_year_id', $toAcademicYearId, PDO::PARAM_INT);
                    $createClassStmt->bindParam(':level', $newLevel, PDO::PARAM_STR);
                    $createClassStmt->bindParam(':department_id', $class['department_id'], PDO::PARAM_INT);
                    $createClassStmt->bindParam(':group_number', $class['group_number'], PDO::PARAM_INT);
                    $createClassStmt->execute();
                    
                    $newClassId = $conn->lastInsertId();
                    
                    // โอนครูที่ปรึกษา
                    $transferAdvisorsQuery = "INSERT INTO class_advisors (class_id, teacher_id, is_primary)
                                          SELECT :new_class_id, teacher_id, is_primary
                                          FROM class_advisors
                                          WHERE class_id = :old_class_id";
                    $transferAdvisorsStmt = $conn->prepare($transferAdvisorsQuery);
                    $transferAdvisorsStmt->bindParam(':new_class_id', $newClassId, PDO::PARAM_INT);
                    $transferAdvisorsStmt->bindParam(':old_class_id', $class['class_id'], PDO::PARAM_INT);
                    $transferAdvisorsStmt->execute();
                }
                
                $newClasses[$class['class_id']] = [
                    'new_class_id' => $newClassId,
                    'new_level' => $newLevel,
                    'promotion_type' => $promotionType
                ];
            }
            
            // ดึงรายชื่อนักเรียนในชั้นเรียนปัจจุบัน
            $studentsQuery = "SELECT student_id
                          FROM students
                          WHERE current_class_id = :class_id
                          AND status = 'กำลังศึกษา'";
            $studentsStmt = $conn->prepare($studentsQuery);
            $studentsStmt->bindParam(':class_id', $class['class_id'], PDO::PARAM_INT);
            $studentsStmt->execute();
            $students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($students as $student) {
                // เลื่อนชั้นนักเรียน
                if ($promotionType === 'graduation') {
                    // กรณีจบการศึกษา
                    $updateStudentQuery = "UPDATE students
                                        SET status = 'สำเร็จการศึกษา'
                                        WHERE student_id = :student_id";
                    $updateStudentStmt = $conn->prepare($updateStudentQuery);
                    $updateStudentStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $updateStudentStmt->execute();
                    
                    $graduateCount++;
                } else {
                    // กรณีเลื่อนชั้น
                    $updateStudentQuery = "UPDATE students
                                        SET current_class_id = :new_class_id
                                        WHERE student_id = :student_id";
                    $updateStudentStmt = $conn->prepare($updateStudentQuery);
                    $updateStudentStmt->bindParam(':new_class_id', $newClasses[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                    $updateStudentStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $updateStudentStmt->execute();
                    
                    $studentCount++;
                }
                
                // บันทึกประวัติการเลื่อนชั้น
                $historyQuery = "INSERT INTO class_history (
                               student_id,
                               previous_class_id,
                               new_class_id,
                               previous_level,
                               new_level,
                               promotion_date,
                               academic_year_id,
                               promotion_type,
                               promotion_notes,
                               created_by
                           ) VALUES (
                               :student_id,
                               :previous_class_id,
                               :new_class_id,
                               :previous_level,
                               :new_level,
                               NOW(),
                               :academic_year_id,
                               :promotion_type,
                               :promotion_notes,
                               :created_by
                           )";
                $historyStmt = $conn->prepare($historyQuery);
                $historyStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                $historyStmt->bindParam(':previous_class_id', $class['class_id'], PDO::PARAM_INT);
                
                if ($promotionType === 'graduation') {
                    $historyStmt->bindParam(':new_class_id', $class['class_id'], PDO::PARAM_INT);
                } else {
                    $historyStmt->bindParam(':new_class_id', $newClasses[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                }
                
                $historyStmt->bindParam(':previous_level', $currentLevel, PDO::PARAM_STR);
                $historyStmt->bindParam(':new_level', $newLevel, PDO::PARAM_STR);
                $historyStmt->bindParam(':academic_year_id', $toAcademicYearId, PDO::PARAM_INT);
                $historyStmt->bindParam(':promotion_type', $promotionType, PDO::PARAM_STR);
                $historyStmt->bindParam(':promotion_notes', $notes, PDO::PARAM_STR);
                $historyStmt->bindParam(':created_by', $adminId, PDO::PARAM_INT);
                $historyStmt->execute();
                
                // สร้างประวัติการศึกษาใหม่สำหรับปีการศึกษาใหม่
                if ($promotionType === 'promotion') {
                    $academicRecordQuery = "INSERT INTO student_academic_records (
                                         student_id,
                                         academic_year_id,
                                         class_id,
                                         total_attendance_days,
                                         total_absence_days,
                                         passed_activity
                                     ) VALUES (
                                         :student_id,
                                         :academic_year_id,
                                         :class_id,
                                         0,
                                         0,
                                         NULL
                                     )";
                    $academicRecordStmt = $conn->prepare($academicRecordQuery);
                    $academicRecordStmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $academicRecordStmt->bindParam(':academic_year_id', $toAcademicYearId, PDO::PARAM_INT);
                    $academicRecordStmt->bindParam(':class_id', $newClasses[$class['class_id']]['new_class_id'], PDO::PARAM_INT);
                    $academicRecordStmt->execute();
                }
            }
        }
        
        // อัปเดตสถานะการเลื่อนชั้น
        $updateBatchQuery = "UPDATE student_promotion_batch
                          SET status = 'completed',
                              students_count = :students_count,
                              graduates_count = :graduates_count
                          WHERE batch_id = :batch_id";
        $updateBatchStmt = $conn->prepare($updateBatchQuery);
        $updateBatchStmt->bindParam(':students_count', $studentCount, PDO::PARAM_INT);
        $updateBatchStmt->bindParam(':graduates_count', $graduateCount, PDO::PARAM_INT);
        $updateBatchStmt->bindParam(':batch_id', $batchId, PDO::PARAM_INT);
        $updateBatchStmt->execute();
        
        // บันทึกการดำเนินการในตาราง admin_actions
        $details = json_encode([
            'batch_id' => $batchId,
            'from_academic_year_id' => $fromAcademicYearId,
            'to_academic_year_id' => $toAcademicYearId,
            'students_count' => $studentCount,
            'graduates_count' => $graduateCount
        ], JSON_UNESCAPED_UNICODE);
        
        $actionQuery = "INSERT INTO admin_actions (admin_id, action_type, action_details) 
                      VALUES (:admin_id, 'promote_students', :details)";
        $actionStmt = $conn->prepare($actionQuery);
        $actionStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
        $actionStmt->bindParam(':details', $details, PDO::PARAM_STR);
        $actionStmt->execute();
        
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => "เลื่อนชั้นนักเรียนสำเร็จ ($studentCount คน) และจบการศึกษา ($graduateCount คน)",
            'batch_id' => $batchId,
            'promoted_count' => $studentCount,
            'graduated_count' => $graduateCount
        ];
    } catch (PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Error promoting students: ' . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()
        ];
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('Unexpected error promoting students: ' . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาดที่ไม่คาดคิดในการเลื่อนชั้นนักเรียน: ' . $e->getMessage()
        ];
    }
}