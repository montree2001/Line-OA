<?php
/**
 * teacher_functions.php - ฟังก์ชันสำหรับจัดการข้อมูลครู
 */

// ดึงข้อมูลครูจากฐานข้อมูล
function getTeachersFromDB() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                t.teacher_id, t.title, t.first_name, t.last_name, t.position,
                d.department_name
            FROM teachers t
            LEFT JOIN departments d ON t.department_id = d.department_id
            ORDER BY t.first_name, t.last_name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// สำหรับตัวอย่างการทดสอบ
function getSampleTeachers() {
    return [
        [
            'teacher_id' => 1,
            'title' => 'นาย',
            'first_name' => 'สมชาย',
            'last_name' => 'มีทรัพย์',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'position' => 'ครูชำนาญการ'
        ],
        [
            'teacher_id' => 2,
            'title' => 'นางสาว',
            'first_name' => 'แสงดาว',
            'last_name' => 'พรายแก้ว',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'position' => 'ครูชำนาญการพิเศษ'
        ],
        [
            'teacher_id' => 3,
            'title' => 'นาง',
            'first_name' => 'อรทัย',
            'last_name' => 'สุวรรณ',
            'department_name' => 'การบัญชี',
            'position' => 'ครูชำนาญการ'
        ],
        [
            'teacher_id' => 4,
            'title' => 'นาย',
            'first_name' => 'วีระพงษ์',
            'last_name' => 'พลเดช',
            'department_name' => 'เทคโนโลยีสารสนเทศ',
            'position' => 'ครู'
        ],
        [
            'teacher_id' => 5,
            'title' => 'นาย',
            'first_name' => 'สมศักดิ์',
            'last_name' => 'ใจดี',
            'department_name' => 'ช่างยนต์',
            'position' => 'ครูชำนาญการ'
        ],
        [
            'teacher_id' => 6,
            'title' => 'นางสาว',
            'first_name' => 'มณีรัตน์',
            'last_name' => 'แสงจันทร์',
            'department_name' => 'การบัญชี',
            'position' => 'ครู'
        ],
        [
            'teacher_id' => 7,
            'title' => 'นาย',
            'first_name' => 'กฤษณะ',
            'last_name' => 'พิริยะ',
            'department_name' => 'ช่างไฟฟ้ากำลัง',
            'position' => 'ครูชำนาญการ'
        ],
        [
            'teacher_id' => 8,
            'title' => 'นางสาว',
            'first_name' => 'จารุวรรณ',
            'last_name' => 'บุญมี',
            'department_name' => 'สามัญ',
            'position' => 'ครูชำนาญการ'
        ]
    ];
}