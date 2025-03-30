<?php
/**
 * classes_api.php - API สำหรับจัดการข้อมูลชั้นเรียน
 */

// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../../db_connect.php';

// ตั้งค่า header สำหรับ JSON
header('Content-Type: application/json; charset=UTF-8');

// ตรวจสอบการร้องขอ
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_classes_with_advisors':
            getClassesWithAdvisors();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'ไม่พบ action ที่ระบุ'
            ]);
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่ระบุ action'
    ]);
}

/**
 * ดึงข้อมูลชั้นเรียนพร้อมข้อมูลครูที่ปรึกษา
 */
function getClassesWithAdvisors() {
    try {
        $db = getDB();
        
        // ดึงข้อมูลชั้นเรียนที่กำลังใช้งาน (ปีการศึกษาปัจจุบัน)
        $query = "SELECT 
                  c.class_id as classId,
                  c.level as level,
                  c.group_number as groupNumber,
                  d.department_name as departmentName,
                  CASE 
                    WHEN c.level = 'ปวช.1' THEN 'ปวช.1'
                    WHEN c.level = 'ปวช.2' THEN 'ปวช.2'
                    WHEN c.level = 'ปวช.3' THEN 'ปวช.3'
                    WHEN c.level = 'ปวส.1' THEN 'ปวส.1'
                    WHEN c.level = 'ปวส.2' THEN 'ปวส.2'
                    ELSE c.level
                  END as levelName,
                  
                  (SELECT CONCAT(t.title, '', t.first_name, ' ', t.last_name) 
                   FROM class_advisors ca 
                   JOIN teachers t ON ca.teacher_id = t.teacher_id 
                   WHERE ca.class_id = c.class_id AND ca.is_primary = 1
                   LIMIT 1) as advisorName
                  
                  FROM classes c
                  JOIN departments d ON c.department_id = d.department_id
                  JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
                  WHERE ay.is_active = 1 AND c.is_active = 1
                  ORDER BY c.level, c.group_number, d.department_name";
        
        $stmt = $db->query($query);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'classes' => $classes
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน: ' . $e->getMessage()
        ]);
    }
}
?>