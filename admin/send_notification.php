<?php
/**
 * send_notification.php - หน้าส่งข้อความแจ้งเตือนผู้ปกครอง
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

/* // ตรวจสอบการล็อกอิน (หากไม่ได้ล็อกอินให้ redirect ไปหน้า login.php)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header('Location: login.php');
    exit;
} */

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'send_notification';
$page_title = 'ส่งข้อความแจ้งเตือน';
$page_header = 'ส่งข้อความรายงานการเข้าแถว';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'ดูประวัติการส่งข้อความ',
        'icon' => 'history',
        'onclick' => 'showHistory()'
    ]
];

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/notification.css'
];

$extra_js = [
    'assets/js/notification.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/notification_content.php';

// ดึงข้อมูลนักเรียน (จริงๆ ควรดึงจากฐานข้อมูล)
// นี่เป็นตัวอย่างข้อมูล
$students = [
    [
        'id' => 1,
        'name' => 'นายธนกฤต สุขใจ',
        'class' => 'ม.6/2',
        'class_number' => 12,
        'attendance_rate' => 65,
        'attendance_days' => '26/40',
        'status' => 'เสี่ยงตกกิจกรรม',
        'parent' => 'นางวันดี สุขใจ (แม่)'
    ],
    [
        'id' => 2,
        'name' => 'นางสาวสมหญิง มีสุข',
        'class' => 'ม.5/3',
        'class_number' => 8,
        'attendance_rate' => 75,
        'attendance_days' => '30/40',
        'status' => 'ต้องระวัง',
        'parent' => 'นายสมชาย มีสุข (พ่อ)'
    ],
    [
        'id' => 3,
        'name' => 'นายพิชัย รักเรียน',
        'class' => 'ม.4/1',
        'class_number' => 15,
        'attendance_rate' => 95,
        'attendance_days' => '38/40',
        'status' => 'ปกติ',
        'parent' => 'นางรักดี รักเรียน (แม่)'
    ]
];

// รายชื่อนักเรียนกลุ่มเสี่ยงสำหรับการส่งข้อความกลุ่ม
$at_risk_students = [
    [
        'id' => 4,
        'name' => 'นายยศพล วงศ์ประเสริฐ',
        'class' => 'ม.5/1',
        'attendance_rate' => 65,
        'parent' => 'นางสาวสุนิสา วงศ์ประเสริฐ (แม่)'
    ],
    [
        'id' => 5,
        'name' => 'นางสาวนภัสวรรณ จันทรา',
        'class' => 'ม.5/1',
        'attendance_rate' => 75,
        'parent' => 'นายสมชาย จันทรา (พ่อ)'
    ],
    [
        'id' => 6,
        'name' => 'นายวีรยุทธ รักดี',
        'class' => 'ม.5/1',
        'attendance_rate' => 60,
        'parent' => 'นางวันดี รักดี (แม่)'
    ],
    [
        'id' => 7,
        'name' => 'นายชัยวัฒน์ ใจดี',
        'class' => 'ม.5/1',
        'attendance_rate' => 68,
        'parent' => 'นายสมศักดิ์ ใจดี (พ่อ)'
    ],
    [
        'id' => 8,
        'name' => 'นางสาวกันยา สุขศรี',
        'class' => 'ม.5/1',
        'attendance_rate' => 67,
        'parent' => 'นางนิภา สุขศรี (แม่)'
    ],
    [
        'id' => 9,
        'name' => 'นายอานนท์ ภักดี',
        'class' => 'ม.5/1',
        'attendance_rate' => 66,
        'parent' => 'นางสาวอรุณ ภักดี (แม่)'
    ],
    [
        'id' => 10,
        'name' => 'นางสาวรุ่งนภา พัฒนา',
        'class' => 'ม.5/1',
        'attendance_rate' => 62,
        'parent' => 'นายวิชัย พัฒนา (พ่อ)'
    ],
    [
        'id' => 11,
        'name' => 'นายอภิสิทธิ์ สงวนสิทธิ์',
        'class' => 'ม.5/1',
        'attendance_rate' => 69,
        'parent' => 'นางเพ็ญศรี สงวนสิทธิ์ (แม่)'
    ],
];

// ตัวอย่างเทมเพลตข้อความ
$templates = [
    [
        'id' => 1,
        'name' => 'แจ้งเตือนความเสี่ยงรายบุคคล',
        'type' => 'รายบุคคล',
        'created_at' => '10/03/2568',
        'last_used' => '16/03/2568',
        'status' => 'ใช้งาน',
        'content' => 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางโรงเรียนขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน ({{ร้อยละการเข้าแถว}}%)

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
    ],
    [
        'id' => 2,
        'name' => 'นัดประชุมผู้ปกครองกลุ่มเสี่ยง',
        'type' => 'กลุ่ม',
        'created_at' => '05/03/2568',
        'last_used' => '16/03/2568',
        'status' => 'ใช้งาน',
        'content' => 'เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม'
    ],
];

// ส่งข้อมูลไปยังเทมเพลต (ในทางปฏิบัติจริง ควรมีการจัดการข้อมูลที่ซับซ้อนกว่านี้)
$data = [
    'students' => $students,
    'at_risk_students' => $at_risk_students,
    'templates' => $templates
];

// ตรวจสอบการส่งข้อมูลผ่าน AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    // ส่งข้อความรายบุคคล
    if (isset($_POST['send_individual_message'])) {
        $student_id = $_POST['student_id'] ?? 0;
        $message = $_POST['message'] ?? '';
        
        // ในทางปฏิบัติจริง ควรมีการส่งข้อความผ่าน LINE Messaging API หรือวิธีอื่นๆ
        // นี่เป็นตัวอย่างการจำลองการส่งข้อความ
        
        $response = [
            'success' => true,
            'message' => 'ส่งข้อความเรียบร้อยแล้ว',
            'student_id' => $student_id
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // ส่งข้อความกลุ่ม
    if (isset($_POST['send_group_message'])) {
        $student_ids = $_POST['student_ids'] ?? [];
        $message = $_POST['message'] ?? '';
        
        // ในทางปฏิบัติจริง ควรมีการส่งข้อความผ่าน LINE Messaging API หรือวิธีอื่นๆ
        // นี่เป็นตัวอย่างการจำลองการส่งข้อความ
        
        $response = [
            'success' => true,
            'message' => 'ส่งข้อความกลุ่มเรียบร้อยแล้ว',
            'count' => count($student_ids)
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // บันทึกเทมเพลต
    if (isset($_POST['save_template'])) {
        $template_name = $_POST['template_name'] ?? '';
        $template_type = $_POST['template_type'] ?? '';
        $template_content = $_POST['template_content'] ?? '';
        
        // ในทางปฏิบัติจริง ควรมีการบันทึกลงฐานข้อมูล
        // นี่เป็นตัวอย่างการจำลองการบันทึก
        
        $response = [
            'success' => true,
            'message' => 'บันทึกเทมเพลตเรียบร้อยแล้ว',
            'template_id' => time() // ใช้เวลาปัจจุบันเป็น ID
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';