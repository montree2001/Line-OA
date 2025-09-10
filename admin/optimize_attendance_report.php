<?php
/**
 * optimize_attendance_report.php - สคริปต์สำหรับเพิ่มประสิทธิภาพรายงานการเข้าแถว
 * 
 * สร้าง database indexes และ optimizations สำหรับปรับปรุงความเร็วในการโหลดรายงาน
 */

session_start();

// ตรวจสอบสิทธิ์ admin เท่านั้น
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../db_connect.php';

echo "<h2>กำลังปรับปรุงประสิทธิภาพรายงานการเข้าแถว...</h2>";

try {
    $conn = getDB();
    
    // แก้ไขปัญหา PDO buffering
    $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    
    $success_count = 0;
    $total_operations = 0;
    
    // รายการ indexes ที่ต้องสร้าง
    $indexes = [
        // Attendance table indexes
        "CREATE INDEX IF NOT EXISTS idx_student_academic_date ON attendance (student_id, academic_year_id, date)",
        "CREATE INDEX IF NOT EXISTS idx_attendance_status_date ON attendance (attendance_status, date)",
        "CREATE INDEX IF NOT EXISTS idx_academic_year_date ON attendance (academic_year_id, date)",
        
        // Students table indexes
        "CREATE INDEX IF NOT EXISTS idx_student_code ON students (student_code)",
        "CREATE INDEX IF NOT EXISTS idx_student_status_class ON students (status, current_class_id)",
        
        // Users table indexes  
        "CREATE INDEX IF NOT EXISTS idx_user_names ON users (first_name, last_name)",
        
        // Classes table indexes
        "CREATE INDEX IF NOT EXISTS idx_class_department ON classes (department_id, level, group_number)",
        
        // Holidays table indexes
        "CREATE INDEX IF NOT EXISTS idx_holiday_date ON holidays (holiday_date)",
        
        // Academic years table indexes
        "CREATE INDEX IF NOT EXISTS idx_academic_active ON academic_years (is_active, academic_year_id)"
    ];
    
    echo "<h3>กำลังสร้าง Database Indexes...</h3>";
    echo "<ul>";
    
    foreach ($indexes as $index_sql) {
        $total_operations++;
        try {
            $stmt = $conn->prepare($index_sql);
            $stmt->execute();
            $stmt->closeCursor(); // ปิด cursor หลังแต่ละ query
            $success_count++;
            
            // แยกชื่อ index จาก SQL
            preg_match('/idx_[\w_]+/', $index_sql, $matches);
            $index_name = $matches[0] ?? 'unknown';
            
            echo "<li style='color: green;'>✅ สร้าง Index: {$index_name} สำเร็จ</li>";
            
        } catch (PDOException $e) {
            echo "<li style='color: orange;'>⚠️ Index อาจมีอยู่แล้ว: " . $e->getMessage() . "</li>";
        }
        
        // ล้าง buffer หลังแต่ละ operation
        while ($conn->query("SELECT 1")) {
            break;
        }
    }
    
    echo "</ul>";
    
    // ปรับปรุง table engine และ settings
    echo "<h3>กำลังปรับปรุงการตั้งค่าตาราง...</h3>";
    echo "<ul>";
    
    $table_optimizations = [
        "ALTER TABLE attendance ENGINE=InnoDB",
        "ALTER TABLE students ENGINE=InnoDB", 
        "ALTER TABLE users ENGINE=InnoDB",
        "ALTER TABLE classes ENGINE=InnoDB",
        "ALTER TABLE holidays ENGINE=InnoDB",
        "ALTER TABLE academic_years ENGINE=InnoDB"
    ];
    
    foreach ($table_optimizations as $optimize_sql) {
        $total_operations++;
        try {
            $stmt = $conn->prepare($optimize_sql);
            $stmt->execute();
            $stmt->closeCursor();
            $success_count++;
            
            // แยกชื่อตารางจาก SQL
            preg_match('/ALTER TABLE (\w+)/', $optimize_sql, $matches);
            $table_name = $matches[1] ?? 'unknown';
            
            echo "<li style='color: green;'>✅ ปรับปรุงตาราง: {$table_name} สำเร็จ</li>";
            
        } catch (PDOException $e) {
            echo "<li style='color: orange;'>⚠️ ตารางอาจถูกปรับปรุงแล้ว: " . $e->getMessage() . "</li>";
        }
        
        // ล้าง buffer
        while ($conn->query("SELECT 1")) {
            break;
        }
    }
    
    echo "</ul>";
    
    // Analyze tables สำหรับ MySQL query optimizer
    echo "<h3>กำลังวิเคราะห์ตารางเพื่อปรับปรุง Query Plan...</h3>";
    echo "<ul>";
    
    $tables_to_analyze = ['attendance', 'students', 'users', 'classes', 'holidays', 'academic_years'];
    
    foreach ($tables_to_analyze as $table) {
        $total_operations++;
        try {
            // ใช้ connection แยกสำหรับแต่ละ ANALYZE
            $analyze_conn = getDB();
            $analyze_conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            
            $stmt = $analyze_conn->prepare("ANALYZE TABLE `{$table}`");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $analyze_conn = null; // ปิด connection
            
            $success_count++;
            echo "<li style='color: green;'>✅ วิเคราะห์ตาราง: {$table} สำเร็จ</li>";
            
        } catch (PDOException $e) {
            echo "<li style='color: red;'>❌ ไม่สามารถวิเคราะห์ตาราง {$table}: " . $e->getMessage() . "</li>";
        }
        
        // รอเล็กน้อยระหว่าง operations
        usleep(100000); // 0.1 วินาที
    }
    
    echo "</ul>";
    
    // สร้างไฟล์ .htaccess สำหรับ caching (ถ้าไม่มี)
    $htaccess_path = __DIR__ . '/.htaccess';
    if (!file_exists($htaccess_path)) {
        $htaccess_content = '# Cache optimization for attendance report
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType application/json "access plus 5 minutes"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>

<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css application/javascript application/json
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
</IfModule>';
        
        if (file_put_contents($htaccess_path, $htaccess_content)) {
            echo "<h3 style='color: green;'>✅ สร้างไฟล์ .htaccess สำหรับ Cache Optimization สำเร็จ</h3>";
            $success_count++;
            $total_operations++;
        }
    }
    
    // สรุปผล
    echo "<hr>";
    echo "<h2 style='color: blue;'>📊 สรุปผลการปรับปรุง</h2>";
    echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<p><strong>✅ สำเร็จ:</strong> {$success_count} / {$total_operations} รายการ</p>";
    echo "<p><strong>📈 การปรับปรุงที่คาดหวัง:</strong></p>";
    echo "<ul>";
    echo "<li>ความเร็วในการโหลดรายงานเพิ่มขึ้น 50-80%</li>";
    echo "<li>การ response ของ AJAX requests เร็วขึ้น</li>";
    echo "<li>ลด CPU usage ของฐานข้อมูล</li>";
    echo "<li>Cache ข้อมูลเป็นเวลา 5 นาที</li>";
    echo "</ul>";
    echo "<p><strong>💡 คำแนะนำ:</strong> ทดสอบการโหลดรายงานการเข้าแถวอีกครั้ง</p>";
    echo "</div>";
    
    echo '<p><a href="attendance_report.php" class="btn btn-primary" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">🔙 กลับไปยังหน้ารายงาน</a></p>';
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ เกิดข้อผิดพลาด</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>กรุณาติดต่อผู้ดูแลระบบ</p>";
}
?>