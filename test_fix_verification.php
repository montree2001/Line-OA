<?php
/**
 * test_fix_verification.php - ทดสอบว่าการแก้ไข academic_year_id ทำงานถูกต้อง
 */

session_start();
$_SESSION['user_id'] = 1;

require_once 'db_connect.php';
require_once 'admin/attendance_adjustment.php';

echo "<h2>🧪 ทดสอบการแก้ไข Academic Year ID</h2>";
echo "<p><em>ตรวจสอบว่า UPDATE ทำงานถูกต้องหลังแก้ไข</em></p>";

try {
    $conn = getDB();
    $conn->beginTransaction();
    
    // ใช้ข้อมูลจริงจากฐานข้อมูล
    $test_student_query = "SELECT id, academic_year_id, name FROM students LIMIT 1";
    $test_stmt = $conn->query($test_student_query);
    $test_student = $test_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_student) {
        echo "<p>❌ ไม่พบข้อมูลนักเรียนในระบบ</p>";
        exit;
    }
    
    $student_id = $test_student['id'];
    $student_academic_year = $test_student['academic_year_id'];
    $student_name = $test_student['name'];
    
    echo "<h3>📋 ข้อมูลทดสอบ</h3>";
    echo "<p><strong>Student:</strong> $student_name (ID: $student_id)</p>";
    echo "<p><strong>Student's Academic Year:</strong> $student_academic_year</p>";
    
    // ตรวจสอบปีการศึกษาที่ใช้งาน
    $active_year_query = "SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1";
    $active_stmt = $conn->query($active_year_query);
    $active_year = $active_stmt->fetchColumn();
    
    echo "<p><strong>System Active Year:</strong> $active_year</p>";
    echo "<p><strong>Match:</strong> " . ($student_academic_year == $active_year ? '✅ ตรงกัน' : '⚠️ ไม่ตรงกัน') . "</p>";
    
    // เตรียมข้อมูลทดสอบ - สร้างข้อมูล absent
    $test_dates = [
        date('Y-m-d', strtotime('-3 days')),
        date('Y-m-d', strtotime('-2 days')),
        date('Y-m-d', strtotime('-1 days'))
    ];
    
    // ล้างข้อมูลเก่า
    $cleanup = "DELETE FROM attendance WHERE student_id = ? AND date IN (?, ?, ?)";
    $cleanup_stmt = $conn->prepare($cleanup);
    $cleanup_stmt->execute([$student_id, $test_dates[0], $test_dates[1], $test_dates[2]]);
    
    // เพิ่มข้อมูล absent
    foreach ($test_dates as $date) {
        $insert = "INSERT INTO attendance (student_id, academic_year_id, date, attendance_status, check_method, created_at) VALUES (?, ?, ?, 'absent', 'System', NOW())";
        $insert_stmt = $conn->prepare($insert);
        $insert_stmt->execute([$student_id, $student_academic_year, $date]);
        echo "<p>✅ เพิ่มข้อมูล absent สำหรับวันที่: $date</p>";
    }
    
    echo "<h3>🔧 ทดสอบฟังก์ชัน adjustStudentAttendance</h3>";
    
    // ทดสอบฟังก์ชันที่แก้ไขแล้ว
    $days_to_adjust = 2;
    
    echo "<p>🔄 กำลังเรียกฟังก์ชัน adjustStudentAttendance($student_id, $days_to_adjust)...</p>";
    
    // เปลี่ยนไปใช้ transaction ใหม่เพื่อทดสอบ
    $conn->rollBack();
    $conn->beginTransaction();
    
    // เพิ่มข้อมูลอีกครั้ง
    foreach ($test_dates as $date) {
        $insert = "INSERT INTO attendance (student_id, academic_year_id, date, attendance_status, check_method, created_at) VALUES (?, ?, ?, 'absent', 'System', NOW())";
        $insert_stmt = $conn->prepare($insert);
        $insert_stmt->execute([$student_id, $student_academic_year, $date]);
    }
    
    // เรียกฟังก์ชันที่แก้ไขแล้ว
    $result = adjustStudentAttendance($student_id, $days_to_adjust);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ <strong>สำเร็จ!</strong></p>";
        echo "<p><strong>วันที่แก้ไขได้:</strong> {$result['days_adjusted']} วัน</p>";
        echo "<p><strong>ข้อความ:</strong> {$result['message']}</p>";
        
        if ($result['days_adjusted'] == $days_to_adjust) {
            echo "<p style='color: green; font-size: 16px;'><strong>🎯 การแก้ไขสำเร็จสมบูรณ์!</strong></p>";
        } else {
            echo "<p style='color: orange;'><strong>⚠️ แก้ไขได้บางส่วน</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>ล้มเหลว:</strong> {$result['message']}</p>";
    }
    
    // ตรวจสอบผลลัพธ์
    echo "<h3>📊 ตรวจสอบผลลัพธ์</h3>";
    $check_query = "SELECT date, attendance_status, remarks FROM attendance WHERE student_id = ? AND date IN (?, ?, ?) ORDER BY date";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$student_id, $test_dates[0], $test_dates[1], $test_dates[2]]);
    $results = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>วันที่</th><th>สถานะ</th><th>หมายเหตุ</th></tr>";
    $present_count = 0;
    foreach ($results as $row) {
        $color = $row['attendance_status'] === 'present' ? 'green' : 'red';
        echo "<tr><td>{$row['date']}</td><td style='color: $color;'><strong>{$row['attendance_status']}</strong></td><td>{$row['remarks']}</td></tr>";
        if ($row['attendance_status'] === 'present') {
            $present_count++;
        }
    }
    echo "</table>";
    
    echo "<p><strong>จำนวนที่เปลี่ยนเป็น present:</strong> $present_count วัน</p>";
    
    if ($present_count == $days_to_adjust) {
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h4 style='color: green;'>🎉 การทดสอบผ่าน!</h4>";
        echo "<p>ระบบสามารถแก้ไข absent เป็น present ได้ถูกต้องแล้ว</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 8px; margin-top: 20px;'>";
        echo "<h4 style='color: red;'>❌ การทดสอบไม่ผ่าน</h4>";
        echo "<p>ยังมีปัญหาในการแก้ไขข้อมูล</p>";
        echo "</div>";
    }
    
    // Rollback
    $conn->rollBack();
    echo "<p><em>ℹ️ ข้อมูลทดสอบถูก rollback แล้ว</em></p>";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ เกิดข้อผิดพลาด</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3, h4 { color: #2196f3; }
    table { margin: 10px 0; background: white; }
    th { background: #2196f3; color: white; padding: 8px; }
    td { padding: 8px; border: 1px solid #ddd; }
</style>