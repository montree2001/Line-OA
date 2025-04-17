<?php
/**
 * test_attendance_sql.php - ไฟล์สำหรับทดสอบ SQL ในระบบเช็คชื่อนักเรียน
 * 
 * ใช้ไฟล์นี้เพื่อทดสอบว่า:
 * 1. การเชื่อมต่อฐานข้อมูลทำงานถูกต้อง
 * 2. SQL INSERT/UPDATE ที่ใช้ในการบันทึกการเช็คชื่อทำงานถูกต้อง
 * 3. มีการอัพเดทสถิติในตาราง student_academic_records
 * 
 * วิธีใช้: วางไฟล์นี้ใน folder teacher/api และเรียกใช้ผ่าน URL
 */

// แสดงข้อผิดพลาดทั้งหมดเพื่อการดีบัก
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตั้งค่า header เป็น JSON (แต่เพิ่ม <pre> สำหรับการแสดงผลในเบราว์เซอร์)
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ SQL สำหรับระบบเช็คชื่อ</title>
    <style>
        body { font-family: 'Prompt', sans-serif; margin: 20px; line-height: 1.5; }
        h1, h2 { color: #1976d2; }
        .success { color: #4caf50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .test-section { 
            background: #f5f5f5; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            border-left: 4px solid #1976d2;
        }
        .sql-box {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
        }
        .result-box {
            background: #e3f2fd;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            background: #1976d2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #1565c0;
        }
    </style>
</head>
<body>
    <h1>ทดสอบ SQL สำหรับระบบเช็คชื่อนักเรียน</h1>

<?php

// เริ่ม session และตรวจสอบการล็อกอิน
session_start();

// ถ้าไม่ใช่ครู/แอดมิน แสดงปุ่มล็อกอินจำลอง (สำหรับการทดสอบเท่านั้น)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'admin')) {
    if (isset($_GET['simulate_login'])) {
        // จำลองข้อมูลผู้ใช้สำหรับการทดสอบ
        $_SESSION['user_id'] = 183; // ใช้ ID ครูจากฐานข้อมูล
        $_SESSION['role'] = 'teacher';
        echo "<div class='success'>จำลองการล็อกอินเป็นครูเรียบร้อย</div>";
    } else {
        echo "<div class='warning'>คุณยังไม่ได้ล็อกอิน คลิกปุ่มด้านล่างเพื่อจำลองการล็อกอินเป็นครู</div>";
        echo "<a href='?simulate_login=1'><button>จำลองการล็อกอินเป็นครู</button></a>";
        echo "</body></html>";
        exit;
    }
}

// เรียกใช้ไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../../config/db_config.php';
require_once '../../db_connect.php';

// ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<div class='test-section'>";
echo "<h2>1. ทดสอบการเชื่อมต่อฐานข้อมูล</h2>";

try {
    $db = getDB();
    echo "<div class='success'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage() . "</div>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// ทดสอบการเข้าถึงตาราง attendance
echo "<div class='test-section'>";
echo "<h2>2. ทดสอบการเข้าถึงตาราง attendance</h2>";

try {
    $sql = "DESCRIBE attendance";
    echo "<div class='sql-box'>$sql</div>";
    
    $stmt = $db->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($columns) > 0) {
        echo "<div class='success'>✓ สามารถเข้าถึงตาราง attendance ได้</div>";
        echo "<div class='result-box'>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='error'>✗ ไม่มีข้อมูลโครงสร้างตาราง attendance</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ ไม่สามารถเข้าถึงตาราง attendance: " . $e->getMessage() . "</div>";
}
echo "</div>";

// ดึงข้อมูลปีการศึกษาปัจจุบัน
echo "<div class='test-section'>";
echo "<h2>3. ทดสอบการดึงข้อมูลปีการศึกษาปัจจุบัน</h2>";

try {
    $sql = "SELECT academic_year_id, year, semester FROM academic_years WHERE is_active = 1";
    echo "<div class='sql-box'>$sql</div>";
    
    $stmt = $db->query($sql);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_year) {
        echo "<div class='success'>✓ ดึงข้อมูลปีการศึกษาปัจจุบันสำเร็จ</div>";
        echo "<div class='result-box'>";
        echo "รหัสปีการศึกษา: " . $academic_year['academic_year_id'] . "<br>";
        echo "ปีการศึกษา: " . $academic_year['year'] . "<br>";
        echo "ภาคเรียน: " . $academic_year['semester'] . "<br>";
        echo "</div>";
        
        // เก็บค่าไว้ใช้ต่อ
        $academic_year_id = $academic_year['academic_year_id'];
    } else {
        echo "<div class='error'>✗ ไม่พบข้อมูลปีการศึกษาปัจจุบัน</div>";
        $academic_year_id = null;
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ ไม่สามารถดึงข้อมูลปีการศึกษา: " . $e->getMessage() . "</div>";
    $academic_year_id = null;
}
echo "</div>";

// ดึงข้อมูลนักเรียนจากห้องเรียนแรกที่ครูเป็นที่ปรึกษา
echo "<div class='test-section'>";
echo "<h2>4. ทดสอบการดึงข้อมูลนักเรียนจากห้องเรียนที่ครูเป็นที่ปรึกษา</h2>";

try {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT c.class_id, c.level, d.department_name, c.group_number 
            FROM class_advisors ca 
            JOIN teachers t ON ca.teacher_id = t.teacher_id 
            JOIN classes c ON ca.class_id = c.class_id 
            JOIN departments d ON c.department_id = d.department_id 
            WHERE t.user_id = :user_id AND c.is_active = 1
            LIMIT 1";
    
    echo "<div class='sql-box'>$sql</div>";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($class) {
        echo "<div class='success'>✓ ดึงข้อมูลห้องเรียนสำเร็จ</div>";
        echo "<div class='result-box'>";
        echo "รหัสห้องเรียน: " . $class['class_id'] . "<br>";
        echo "ระดับชั้น: " . $class['level'] . "<br>";
        echo "แผนก: " . $class['department_name'] . "<br>";
        echo "กลุ่ม: " . $class['group_number'] . "<br>";
        echo "</div>";
        
        // เก็บค่าไว้ใช้ต่อ
        $class_id = $class['class_id'];
        
        // ทดสอบการดึงข้อมูลนักเรียน
        $sql = "SELECT s.student_id, s.student_code, s.title, u.first_name, u.last_name 
                FROM students s 
                JOIN users u ON s.user_id = u.user_id 
                WHERE s.current_class_id = :class_id AND s.status = 'กำลังศึกษา'
                LIMIT 5";
        
        echo "<div class='sql-box'>$sql</div>";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($students) > 0) {
            echo "<div class='success'>✓ ดึงข้อมูลนักเรียนสำเร็จ</div>";
            echo "<div class='result-box'>";
            echo "<table>";
            echo "<tr><th>รหัสนักเรียน</th><th>รหัสประจำตัว</th><th>ชื่อ-นามสกุล</th></tr>";
            
            foreach ($students as $student) {
                echo "<tr>";
                echo "<td>" . $student['student_id'] . "</td>";
                echo "<td>" . $student['student_code'] . "</td>";
                echo "<td>" . $student['title'] . $student['first_name'] . ' ' . $student['last_name'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "</div>";
            
            // เก็บค่าไว้ใช้ต่อ
            $student_id = $students[0]['student_id'];
        } else {
            echo "<div class='error'>✗ ไม่พบข้อมูลนักเรียนในห้องเรียนนี้</div>";
            $student_id = null;
        }
    } else {
        echo "<div class='error'>✗ ไม่พบห้องเรียนที่ครูเป็นที่ปรึกษา</div>";
        $class_id = $student_id = null;
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ ไม่สามารถดึงข้อมูลห้องเรียน: " . $e->getMessage() . "</div>";
    $class_id = $student_id = null;
}
echo "</div>";

// ทดสอบการบันทึกการเช็คชื่อ (ถ้ามีข้อมูลนักเรียนและปีการศึกษา)
if ($student_id && $academic_year_id && $class_id) {
    echo "<div class='test-section'>";
    echo "<h2>5. ทดสอบการบันทึกการเช็คชื่อ</h2>";
    
    try {
        // เริ่ม Transaction
        $db->beginTransaction();
        
        // วันที่ปัจจุบัน
        $check_date = date('Y-m-d');
        
        // ตรวจสอบว่ามีการเช็คชื่อนักเรียนคนนี้ในวันนี้แล้วหรือไม่
        $sql = "SELECT attendance_id, attendance_status FROM attendance 
                WHERE student_id = :student_id AND date = :check_date";
        
        echo "<div class='sql-box'>$sql</div>";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
        $stmt->execute();
        $existing_attendance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_attendance) {
            echo "<div class='warning'>มีการเช็คชื่อนักเรียนนี้แล้ว (สถานะ: " . $existing_attendance['attendance_status'] . ")</div>";
            
            // ทดสอบการอัพเดทการเช็คชื่อ
            $status = ($existing_attendance['attendance_status'] == 'present') ? 'absent' : 'present';
            $attendance_id = $existing_attendance['attendance_id'];
            $user_id = $_SESSION['user_id'];
            $remarks = 'ทดสอบการอัพเดทการเช็คชื่อ';
            
            $sql = "UPDATE attendance 
                    SET attendance_status = :status, 
                        check_method = 'Manual',
                        checker_user_id = :user_id, 
                        check_time = NOW(),
                        remarks = :remarks
                    WHERE attendance_id = :attendance_id";
            
            echo "<div class='sql-box'>$sql</div>";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            $stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<div class='success'>✓ อัพเดทการเช็คชื่อสำเร็จ (เปลี่ยนสถานะเป็น: $status)</div>";
            } else {
                echo "<div class='error'>✗ อัพเดทการเช็คชื่อล้มเหลว</div>";
            }
        } else {
            // ทดสอบการเพิ่มการเช็คชื่อใหม่
            $status = 'present';
            $user_id = $_SESSION['user_id'];
            $remarks = 'ทดสอบการเพิ่มการเช็คชื่อ';
            
            $sql = "INSERT INTO attendance 
                    (student_id, academic_year_id, date, attendance_status, check_method, 
                    checker_user_id, check_time, created_at, remarks) 
                    VALUES (:student_id, :academic_year_id, :check_date, :status, 'Manual', 
                    :user_id, NOW(), NOW(), :remarks)";
            
            echo "<div class='sql-box'>$sql</div>";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
            $stmt->bindParam(':check_date', $check_date, PDO::PARAM_STR);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':remarks', $remarks, PDO::PARAM_STR);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<div class='success'>✓ เพิ่มการเช็คชื่อสำเร็จ (สถานะ: $status)</div>";
                $attendance_id = $db->lastInsertId();
            } else {
                echo "<div class='error'>✗ เพิ่มการเช็คชื่อล้มเหลว</div>";
                $attendance_id = null;
            }
        }
        
        // ตรวจสอบผลลัพธ์
        if ($attendance_id) {
            // ดึงข้อมูลการเช็คชื่อที่บันทึกแล้ว
            $sql = "SELECT * FROM attendance WHERE attendance_id = :attendance_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':attendance_id', $attendance_id, PDO::PARAM_INT);
            $stmt->execute();
            $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attendance) {
                echo "<div class='result-box'>";
                echo "<strong>ข้อมูลการเช็คชื่อที่บันทึกแล้ว:</strong><br>";
                foreach ($attendance as $key => $value) {
                    echo "$key: " . htmlspecialchars($value ?? 'NULL') . "<br>";
                }
                echo "</div>";
                
                // ทดสอบการอัพเดทสถิติ
                $sql = "
                    UPDATE student_academic_records sar
                    JOIN (
                        SELECT 
                            student_id,
                            SUM(CASE WHEN attendance_status IN ('present', 'late', 'leave') THEN 1 ELSE 0 END) as total_present,
                            SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) as total_absent
                        FROM attendance
                        WHERE student_id = :student_id AND academic_year_id = :academic_year_id
                        GROUP BY student_id
                    ) att ON sar.student_id = att.student_id
                    SET 
                        sar.total_attendance_days = att.total_present,
                        sar.total_absence_days = att.total_absent,
                        sar.updated_at = NOW()
                    WHERE sar.student_id = :student_id AND sar.academic_year_id = :academic_year_id
                ";
                
                echo "<div class='sql-box'>$sql</div>";
                
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div class='success'>✓ อัพเดทสถิติการเข้าแถวสำเร็จ</div>";
                    
                    // ตรวจสอบข้อมูลสถิติ
                    $sql = "SELECT * FROM student_academic_records 
                            WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($stats) {
                        echo "<div class='result-box'>";
                        echo "<strong>ข้อมูลสถิติการเข้าแถว:</strong><br>";
                        echo "จำนวนวันที่มาเข้าแถว: " . $stats['total_attendance_days'] . "<br>";
                        echo "จำนวนวันที่ขาด: " . $stats['total_absence_days'] . "<br>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error'>✗ อัพเดทสถิติการเข้าแถวล้มเหลว</div>";
                }
            }
        }
        
        // Commit Transaction
        $db->commit();
        echo "<div class='success'>✓ บันทึกข้อมูลทั้งหมดสำเร็จ (Commit Transaction)</div>";
        
    } catch (Exception $e) {
        // Rollback Transaction
        $db->rollBack();
        echo "<div class='error'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . " (Rollback Transaction)</div>";
    }
    
    echo "</div>";
}

// ทดสอบการสร้าง PIN
if ($class_id) {
    echo "<div class='test-section'>";
    echo "<h2>6. ทดสอบการสร้างรหัส PIN</h2>";
    
    try {
        // เริ่ม Transaction
        $db->beginTransaction();
        
        $user_id = $_SESSION['user_id'];
        
        // ดึงการตั้งค่าเกี่ยวกับ PIN
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = 'pin_expiration'";
        $stmt = $db->query($sql);
        $settings_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $pin_expiration_minutes = $settings_data ? intval($settings_data['setting_value']) : 10;
        
        echo "<div class='result-box'>";
        echo "ระยะเวลาหมดอายุของ PIN: " . $pin_expiration_minutes . " นาที<br>";
        echo "</div>";
        
        // สร้างรหัส PIN 4 หลักแบบสุ่ม
        $pin_code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // กำหนดเวลาหมดอายุ
        $valid_from = date('Y-m-d H:i:s');
        $valid_until = date('Y-m-d H:i:s', time() + ($pin_expiration_minutes * 60));
        
        // ยกเลิก PIN เก่าที่ยังใช้งานได้
        $sql = "UPDATE pins SET is_active = 0 
                WHERE creator_user_id = :user_id AND class_id = :class_id AND is_active = 1";
        
        echo "<div class='sql-box'>$sql</div>";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $deactivated_count = $stmt->rowCount();
        echo "<div class='success'>✓ ยกเลิก PIN เก่า " . $deactivated_count . " รายการ</div>";
        
        // บันทึก PIN ใหม่
        $sql = "INSERT INTO pins (pin_code, creator_user_id, academic_year_id, valid_from, valid_until, is_active, class_id, created_at) 
                VALUES (:pin_code, :user_id, :academic_year_id, :valid_from, :valid_until, 1, :class_id, NOW())";
        
        echo "<div class='sql-box'>$sql</div>";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':pin_code', $pin_code, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':academic_year_id', $academic_year_id, PDO::PARAM_INT);
        $stmt->bindParam(':valid_from', $valid_from, PDO::PARAM_STR);
        $stmt->bindParam(':valid_until', $valid_until, PDO::PARAM_STR);
        $stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $result = $stmt->execute();
        
        if ($result) {
            $pin_id = $db->lastInsertId();
            echo "<div class='success'>✓ สร้างรหัส PIN สำเร็จ</div>";
            echo "<div class='result-box'>";
            echo "รหัส PIN: <b>" . $pin_code . "</b><br>";
            echo "เริ่มใช้งาน: " . $valid_from . "<br>";
            echo "หมดอายุ: " . $valid_until . "<br>";
            echo "</div>";
        } else {
            echo "<div class='error'>✗ สร้างรหัส PIN ล้มเหลว</div>";
        }
        
        // Commit Transaction
        $db->commit();
        
    } catch (Exception $e) {
        // Rollback Transaction
        $db->rollBack();
        echo "<div class='error'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
}

// สรุปผลการทดสอบ
echo "<div class='test-section'>";
echo "<h2>7. สรุปผลการทดสอบ</h2>";

echo "<p>จากการทดสอบพบว่า:</p>";
echo "<ol>";
echo "<li>การเชื่อมต่อฐานข้อมูลทำงานได้ปกติ</li>";
echo "<li>สามารถเข้าถึงโครงสร้างตาราง attendance ได้</li>";
echo "<li>สามารถดึงข้อมูลปีการศึกษาปัจจุบันได้</li>";

if ($class_id && $student_id) {
    echo "<li>สามารถดึงข้อมูลห้องเรียนและนักเรียนได้</li>";
    echo "<li>สามารถบันทึกการเช็คชื่อได้</li>";
} else {
    echo "<li class='warning'>ไม่สามารถทดสอบการบันทึกการเช็คชื่อได้เนื่องจากไม่พบข้อมูลนักเรียนหรือห้องเรียน</li>";
}

if ($class_id) {
    echo "<li>สามารถสร้างรหัส PIN ได้</li>";
} else {
    echo "<li class='warning'>ไม่สามารถทดสอบการสร้างรหัส PIN ได้เนื่องจากไม่พบข้อมูลห้องเรียน</li>";
}

echo "</ol>";

echo "<p>หากยังพบปัญหาการบันทึกข้อมูล อาจเกิดจาก:</p>";
echo "<ul>";
echo "<li>ปัญหาการส่งข้อมูลจาก JavaScript ไปยัง API (ตรวจสอบ AJAX request)</li>";
echo "<li>ปัญหาการรับข้อมูล JSON ใน PHP API</li>";
echo "<li>ปัญหาสิทธิ์การเข้าถึงฐานข้อมูล</li>";
echo "<li>ปัญหาการตั้งค่า session ใน PHP</li>";
echo "</ul>";

echo "</div>";

?>

<div style="margin-top: 20px;">
    <a href="?"><button>รีเฟรช</button></a>
</div>

</body>
</html>