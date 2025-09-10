<?php
// Debug script to check attendance adjustment data
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

try {
    $conn = getDB();
    echo "<h2>🔍 Debug ข้อมูลสำหรับ Attendance Adjustment</h2>";
    
    // 1. ตรวจสอบตารางที่มี
    echo "<h3>1. ตารางในฐานข้อมูล:</h3>";
    $stmt = $conn->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "📋 " . $row[0] . "<br>";
    }
    
    // 2. ตรวจสอบปีการศึกษา
    echo "<br><h3>2. ปีการศึกษา:</h3>";
    $stmt = $conn->query("SELECT * FROM academic_years WHERE is_active = 1");
    $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($academic_years) {
        foreach ($academic_years as $ay) {
            echo "📅 ปีการศึกษา: {$ay['year']} ภาค: {$ay['semester']} (ID: {$ay['academic_year_id']})<br>";
        }
    } else {
        echo "❌ ไม่พบปีการศึกษาที่ active<br>";
        // แสดงปีการศึกษาทั้งหมด
        $stmt = $conn->query("SELECT * FROM academic_years LIMIT 5");
        $all_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($all_years) {
            echo "ปีการศึกษาทั้งหมด:<br>";
            foreach ($all_years as $ay) {
                echo "- ปี: {$ay['year']} ภาค: {$ay['semester']} Active: {$ay['is_active']}<br>";
            }
        }
    }
    
    // 3. ตรวจสอบข้อมูลนักเรียน
    echo "<br><h3>3. ข้อมูลนักเรียน (5 คนแรก):</h3>";
    $stmt = $conn->query("
        SELECT 
            s.student_id, s.student_code, s.status,
            u.first_name, u.last_name
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.user_id 
        LIMIT 5
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($students) {
        foreach ($students as $student) {
            echo "👤 ID: {$student['student_id']}, รหัส: {$student['student_code']}, ชื่อ: {$student['first_name']} {$student['last_name']}, สถานะ: {$student['status']}<br>";
        }
        
        // นับจำนวนนักเรียนทั้งหมด
        $stmt = $conn->query("SELECT COUNT(*) as total FROM students");
        $total = $stmt->fetch()['total'];
        echo "<strong>รวมนักเรียนทั้งหมด: {$total} คน</strong><br>";
        
        // นับจำนวนนักเรียนแต่ละสถานะ
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM students GROUP BY status");
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "จำนวนตามสถานะ:<br>";
        foreach ($status_counts as $sc) {
            echo "- {$sc['status']}: {$sc['count']} คน<br>";
        }
        
    } else {
        echo "❌ ไม่พบข้อมูลนักเรียน<br>";
    }
    
    // 4. ตรวจสอบข้อมูล student_academic_records
    echo "<br><h3>4. ข้อมูล Student Academic Records:</h3>";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM student_academic_records");
    $sar_total = $stmt->fetch()['total'];
    echo "จำนวน records ทั้งหมด: {$sar_total}<br>";
    
    if ($sar_total > 0) {
        // ตัวอย่างข้อมูล
        $stmt = $conn->query("
            SELECT 
                sar.student_id, sar.academic_year_id,
                sar.total_attendance_days, sar.total_absence_days,
                sar.attendance_rate
            FROM student_academic_records sar 
            LIMIT 5
        ");
        $sar_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sar_samples as $sar) {
            echo "📊 นักเรียน ID: {$sar['student_id']}, ปีการศึกษา: {$sar['academic_year_id']}, เข้า: {$sar['total_attendance_days']}, ขาด: {$sar['total_absence_days']}, %: {$sar['attendance_rate']}<br>";
        }
        
        // ตรวจสอบข้อมูลสำหรับปีการศึกษาที่ active
        if ($academic_years) {
            $ay_id = $academic_years[0]['academic_year_id'];
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM student_academic_records 
                WHERE academic_year_id = ?
            ");
            $stmt->execute([$ay_id]);
            $ay_count = $stmt->fetch()['count'];
            echo "<strong>ข้อมูลสำหรับปีการศึกษาปัจจุบัน (ID: {$ay_id}): {$ay_count} records</strong><br>";
        }
        
    } else {
        echo "❌ ไม่พบข้อมูล student_academic_records<br>";
    }
    
    // 5. ทดสอบ query สำหรับนักเรียนที่เข้าแถวต่ำกว่า 60%
    echo "<br><h3>5. ทดสอบ Query นักเรียนที่เข้าแถวต่ำกว่า 60%:</h3>";
    
    if ($academic_years) {
        $ay_id = $academic_years[0]['academic_year_id'];
        $total_days = 48;
        
        $query = "
            SELECT 
                s.student_id,
                s.student_code,
                u.first_name,
                u.last_name,
                COALESCE(sar.total_attendance_days, 0) AS attended_days,
                ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) AS attendance_percentage
            FROM 
                students s
            LEFT JOIN 
                users u ON s.user_id = u.user_id
            LEFT JOIN 
                student_academic_records sar ON s.student_id = sar.student_id 
                AND sar.academic_year_id = ?
            WHERE 
                ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) < 60
            ORDER BY 
                attendance_percentage ASC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$total_days, $ay_id, $total_days]);
        $under_60_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($under_60_students) {
            echo "<strong>พบนักเรียนที่เข้าแถวต่ำกว่า 60%: " . count($under_60_students) . " คน</strong><br>";
            foreach ($under_60_students as $student) {
                echo "🔴 {$student['student_code']}: {$student['first_name']} {$student['last_name']} - เข้าแถว: {$student['attended_days']}/{$total_days} วัน ({$student['attendance_percentage']}%)<br>";
            }
        } else {
            echo "✅ ไม่พบนักเรียนที่เข้าแถวต่ำกว่า 60% หรือข้อมูลไม่ถูกต้อง<br>";
            
            // ลองดูข้อมูลทั้งหมด
            echo "<br>ตรวจสอบข้อมูลทั้งหมด (10 คนแรก):<br>";
            $stmt = $conn->prepare("
                SELECT 
                    s.student_id,
                    s.student_code,
                    u.first_name,
                    u.last_name,
                    COALESCE(sar.total_attendance_days, 0) AS attended_days,
                    ROUND((COALESCE(sar.total_attendance_days, 0) / ?) * 100, 2) AS attendance_percentage
                FROM 
                    students s
                LEFT JOIN 
                    users u ON s.user_id = u.user_id
                LEFT JOIN 
                    student_academic_records sar ON s.student_id = sar.student_id 
                    AND sar.academic_year_id = ?
                LIMIT 10
            ");
            $stmt->execute([$total_days, $ay_id]);
            $all_samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($all_samples as $student) {
                echo "📊 {$student['student_code']}: {$student['first_name']} {$student['last_name']} - เข้าแถว: {$student['attended_days']}/{$total_days} วัน ({$student['attendance_percentage']}%)<br>";
            }
        }
    } else {
        echo "❌ ไม่สามารถทดสอบ query ได้เนื่องจากไม่พบปีการศึกษา<br>";
    }
    
} catch (Exception $e) {
    echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>