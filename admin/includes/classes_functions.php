<?php
/**
 * classes_functions.php - ฟังก์ชันสำหรับจัดการชั้นเรียน
 */

// ดึงข้อมูลชั้นเรียนจากฐานข้อมูล
function getClassesFromDB() {
    global $conn;
    
    try {
        // ดึงข้อมูลชั้นเรียนพร้อมรายละเอียด
        $stmt = $conn->prepare("
            SELECT c.class_id, c.academic_year_id, c.level, c.department_id, 
                c.group_number, d.department_name
            FROM classes c
            JOIN departments d ON c.department_id = d.department_id
            ORDER BY c.academic_year_id DESC, c.level, c.department_id, c.group_number
        ");
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // เพิ่มข้อมูลครูที่ปรึกษาและจำนวนนักเรียน
        foreach ($classes as &$class) {
            // ดึงข้อมูลครูที่ปรึกษา
            $stmt = $conn->prepare("
                SELECT t.teacher_id, 
                    CONCAT(t.title, ' ', t.first_name, ' ', t.last_name) as name, 
                    ca.is_primary
                FROM class_advisors ca
                JOIN teachers t ON ca.teacher_id = t.teacher_id
                WHERE ca.class_id = ?
            ");
            $stmt->execute([$class['class_id']]);
            $class['advisors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // ดึงจำนวนนักเรียน
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count
                FROM students
                WHERE current_class_id = ? AND status = 'กำลังศึกษา'
            ");
            $stmt->execute([$class['class_id']]);
            $class['student_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // คำนวณอัตราการเข้าแถว
            $stmt = $conn->prepare("
                SELECT 
                    SUM(CASE WHEN a.is_present = 1 THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.is_present = 0 THEN 1 ELSE 0 END) as absent_days
                FROM attendance a
                JOIN students s ON a.student_id = s.student_id
                WHERE s.current_class_id = ?
            ");
            $stmt->execute([$class['class_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $presentDays = $result['present_days'] ?? 0;
            $absentDays = $result['absent_days'] ?? 0;
            
            $totalDays = $presentDays + $absentDays;
            if ($totalDays > 0) {
                $class['attendance_rate'] = ($presentDays / $totalDays) * 100;
            } else {
                $class['attendance_rate'] = 0;
            }
        }
        
        return $classes;
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// ดึงข้อมูลจำนวนนักเรียนที่เสี่ยงตกกิจกรรม
function getAtRiskStudentCount() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM risk_students
            WHERE risk_level IN ('high', 'critical')
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return 0;
    }
}

// ดึงข้อมูลการเลื่อนชั้น
function getPromotionCounts($activeYearId) {
    global $conn;
    
    try {
        $result = [];
        
        // ปวช.1 -> ปวช.2
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND c.level = 'ปวช.1' AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$activeYearId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $result[] = [
                'current_level' => 'ปวช.1',
                'new_level' => 'ปวช.2',
                'student_count' => $count
            ];
        }
        
        // ปวช.2 -> ปวช.3
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND c.level = 'ปวช.2' AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$activeYearId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $result[] = [
                'current_level' => 'ปวช.2',
                'new_level' => 'ปวช.3',
                'student_count' => $count
            ];
        }
        
        // ปวส.1 -> ปวส.2
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND c.level = 'ปวส.1' AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$activeYearId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $result[] = [
                'current_level' => 'ปวส.1',
                'new_level' => 'ปวส.2',
                'student_count' => $count
            ];
        }
        
        // ปวช.3 -> สำเร็จการศึกษา
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND c.level = 'ปวช.3' AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$activeYearId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $result[] = [
                'current_level' => 'ปวช.3',
                'new_level' => 'สำเร็จการศึกษา',
                'student_count' => $count
            ];
        }
        
        // ปวส.2 -> สำเร็จการศึกษา
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM students s
            JOIN classes c ON s.current_class_id = c.class_id
            WHERE c.academic_year_id = ? AND c.level = 'ปวส.2' AND s.status = 'กำลังศึกษา'
        ");
        $stmt->execute([$activeYearId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $result[] = [
                'current_level' => 'ปวส.2',
                'new_level' => 'สำเร็จการศึกษา',
                'student_count' => $count
            ];
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// สำหรับตัวอย่างการทดสอบ
function getSampleClasses() {
    return [
        [
            'class_id' => 1,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_id' => 1,
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => 1,
            'advisors' => [
                ['id' => 1, 'name' => 'อ.สมชาย มีทรัพย์', 'is_primary' => 1],
                ['id' => 2, 'name' => 'อ.แสงดาว พรายแก้ว', 'is_primary' => 0]
            ],
            'student_count' => 30,
            'attendance_rate' => 92.5
        ],
        [
            'class_id' => 2,
            'academic_year_id' => 1,
            'level' => 'ปวช.1',
            'department' => 'การบัญชี',
            'department_id' => 2,
            'department_name' => 'การบัญชี',
            'group_number' => 1,
            'advisors' => [
                ['id' => 3, 'name' => 'อ.อรทัย สุวรรณ', 'is_primary' => 1]
            ],
            'student_count' => 35,
            'attendance_rate' => 88.7
        ],
        [
            'class_id' => 3,
            'academic_year_id' => 1,
            'level' => 'ปวส.1',
            'department' => 'เทคโนโลยีสารสนเทศ',
            'department_id' => 1,
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'group_number' => 1,
            'advisors' => [
                ['id' => 4, 'name' => 'อ.วีระพงษ์ พลเดช', 'is_primary' => 1]
            ],
            'student_count' => 25,
            'attendance_rate' => 74.3
        ]
    ];
}