<?php
/**
 * เครื่องมือทดสอบ API สร้าง PIN โดยไม่ต้องผ่าน session
 * (ใช้เฉพาะการทดสอบเท่านั้น)
 */

// แสดงข้อผิดพลาดทั้งหมดสำหรับการดีบัก
ini_set('display_errors', 1);
error_reporting(E_ALL);

// เชื่อมต่อฐานข้อมูล (ปรับค่าตามการตั้งค่าของคุณ)
define('DB_HOST', 'localhost');
define('DB_NAME', 'stp_prasat');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// สำหรับทดสอบโดยไม่ต้องล็อกอิน
// จำลองข้อมูล session
$user_id = 183; // เปลี่ยนเป็น user_id ของครูในระบบของคุณ
$role = 'teacher';
$class_id = 12; // เปลี่ยนเป็น class_id ที่ตรงกับข้อมูลในฐานข้อมูลของคุณ

// ฟังก์ชันสำหรับแสดงข้อความในรูปแบบที่อ่านง่าย
function showMessage($message, $type = 'info') {
    $colors = [
        'info' => 'blue',
        'success' => 'green',
        'error' => 'red',
        'warning' => 'orange'
    ];
    
    echo "<div style='margin: 10px 0; padding: 10px; border-left: 5px solid {$colors[$type]}; background-color: #f8f9fa;'>";
    echo $message;
    echo "</div>";
}

// เชื่อมต่อฐานข้อมูล
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    
    showMessage("1. เชื่อมต่อกับฐานข้อมูลสำเร็จ", 'success');
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
    exit;
}

// ทดสอบดึงข้อมูลปีการศึกษาปัจจุบัน
try {
    $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1";
    $academic_year_result = $conn->query($academic_year_query);
    
    if ($academic_year_result->num_rows === 0) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษาปัจจุบัน");
    }
    
    $academic_year_data = $academic_year_result->fetch_assoc();
    $academic_year_id = $academic_year_data['academic_year_id'];
    
    showMessage("2. พบข้อมูลปีการศึกษาปัจจุบัน: ID = {$academic_year_id}", 'success');
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
    exit;
}

// ดึงการตั้งค่าเกี่ยวกับ PIN
try {
    $settings_query = "SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'";
    $settings_result = $conn->query($settings_query);
    if (!$settings_result) {
        throw new Exception("คำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    $settings_data = $settings_result->fetch_assoc();
    $pin_expiration_minutes = $settings_data ? intval($settings_data['setting_value']) : 10;
    
    showMessage("3. ดึงการตั้งค่าสำเร็จ: เวลาหมดอายุ PIN = {$pin_expiration_minutes} นาที", 'success');
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
    // ใช้ค่าเริ่มต้นถ้าเกิดข้อผิดพลาด
    $pin_expiration_minutes = 10;
}

// สร้างรหัส PIN 4 หลักแบบสุ่ม
$pin_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

// กำหนดเวลาหมดอายุ
$valid_from = date('Y-m-d H:i:s');
$valid_until = date('Y-m-d H:i:s', time() + ($pin_expiration_minutes * 60));

showMessage("4. สร้างรหัส PIN: {$pin_code}, หมดอายุเวลา: {$valid_until}", 'info');

// ยกเลิก PIN เก่าที่ยังใช้งานได้
try {
    $deactivate_pins_query = "UPDATE pins SET is_active = 0 
                             WHERE creator_user_id = ? AND class_id = ? AND is_active = 1";
    $deactivate_stmt = $conn->prepare($deactivate_pins_query);
    
    if (!$deactivate_stmt) {
        throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
    }
    
    $deactivate_stmt->bind_param("ii", $user_id, $class_id);
    $deactivate_stmt->execute();
    
    $affected_rows = $deactivate_stmt->affected_rows;
    $deactivate_stmt->close();
    
    showMessage("5. ยกเลิก PIN เก่า: {$affected_rows} รายการ", 'success');
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
    // ดำเนินการต่อแม้จะมีข้อผิดพลาด
}

// เพิ่ม PIN ใหม่
try {
    // แสดง SQL ที่จะรัน (สำหรับการดีบัก)
    $sql_preview = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id) 
                   VALUES ('$pin_code', $user_id, $academic_year_id, '$valid_from', '$valid_until', 1, $class_id)";
    
    showMessage("SQL ที่จะรัน: " . $sql_preview, 'info');
    
    // ทดลองเพิ่มข้อมูลโดยตรงไม่ผ่าน prepare statement (เพื่อดีบัก)
    if (isset($_GET['direct']) && $_GET['direct'] == 1) {
        $direct_result = $conn->query($sql_preview);
        if ($direct_result) {
            $new_pin_id = $conn->insert_id;
            showMessage("6a. เพิ่ม PIN ด้วย direct query สำเร็จ: ID = {$new_pin_id}", 'success');
        } else {
            showMessage("6a. เพิ่ม PIN ด้วย direct query ล้มเหลว: " . $conn->error, 'error');
        }
    } else {
        // ใช้ prepared statement (วิธีที่แนะนำ)
        $insert_pin_query = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id) 
                           VALUES (?, ?, ?, ?, ?, 1, ?)";
        $insert_stmt = $conn->prepare($insert_pin_query);
        
        if (!$insert_stmt) {
            throw new Exception("เตรียมคำสั่ง SQL ล้มเหลว: " . $conn->error);
        }
        
        // ใช้ bind_param ด้วยชนิดข้อมูลที่ถูกต้อง: siissi
        // s = string, i = integer
        $insert_stmt->bind_param("siissi", $pin_code, $user_id, $academic_year_id, $valid_from, $valid_until, $class_id);
        
        if ($insert_stmt->execute()) {
            $new_pin_id = $conn->insert_id;
            showMessage("6b. เพิ่ม PIN ด้วย prepared statement สำเร็จ: ID = {$new_pin_id}", 'success');
        } else {
            throw new Exception("บันทึกข้อมูลล้มเหลว: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
    }
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
}

// ทดสอบดึงข้อมูล PIN ล่าสุด
try {
    $pin_query = "SELECT p.pin_id, p.pin_code, 
                         u.first_name, u.last_name,
                         p.valid_from, p.valid_until, 
                         TIMESTAMPDIFF(MINUTE, NOW(), p.valid_until) as expire_in_minutes,
                         c.level, d.department_name, c.group_number
                 FROM pins p
                 JOIN users u ON p.creator_user_id = u.user_id
                 JOIN classes c ON p.class_id = c.class_id
                 JOIN departments d ON c.department_id = d.department_id
                 WHERE p.is_active = 1
                 ORDER BY p.created_at DESC
                 LIMIT 5";
    
    $pin_result = $conn->query($pin_query);
    
    if ($pin_result->num_rows === 0) {
        throw new Exception("ไม่พบข้อมูล PIN ที่ใช้งานได้");
    }
    
    echo "<h3>รายการ PIN ล่าสุด:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>PIN ID</th>
            <th>รหัส PIN</th>
            <th>ผู้สร้าง</th>
            <th>ชั้นเรียน</th>
            <th>เริ่มใช้งาน</th>
            <th>หมดอายุ</th>
            <th>เหลือเวลา (นาที)</th>
          </tr>";
    
    while ($pin_data = $pin_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$pin_data['pin_id']}</td>";
        echo "<td>{$pin_data['pin_code']}</td>";
        echo "<td>{$pin_data['first_name']} {$pin_data['last_name']}</td>";
        echo "<td>{$pin_data['level']}/{$pin_data['department_name']}/{$pin_data['group_number']}</td>";
        echo "<td>{$pin_data['valid_from']}</td>";
        echo "<td>{$pin_data['valid_until']}</td>";
        echo "<td>{$pin_data['expire_in_minutes']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    showMessage("7. ดึงข้อมูล PIN ล่าสุดสำเร็จ", 'success');
} catch (Exception $e) {
    showMessage("เกิดข้อผิดพลาด: " . $e->getMessage(), 'error');
}

// สร้างข้อมูลส่งกลับเหมือน API จริง
$response = [
    'success' => true,
    'pin_code' => $pin_code,
    'expire_minutes' => $pin_expiration_minutes,
    'valid_until' => $valid_until
];

echo "<h3>ข้อมูลตอบกลับ API:</h3>";
echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// ปิดการเชื่อมต่อ
$conn->close();

showMessage("การทดสอบเสร็จสิ้น", 'info');

// แสดงลิงก์ทดสอบด้วย Direct Query
echo '<p><a href="?direct=1" style="color: blue;">ทดสอบด้วย Direct Query</a> | <a href="?" style="color: blue;">ทดสอบด้วย Prepared Statement</a></p>';
?>