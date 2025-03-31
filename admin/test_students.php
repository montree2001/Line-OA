<?php
// เริ่ม session
session_start();

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

// ดึงข้อมูลนักเรียน
$students = [];
try {
    $conn = getDB();
    
    // ใช้คำสั่ง SQL อย่างง่าย
    $query = "SELECT DISTINCT
        s.student_id,
        s.student_code,
        s.status,
        u.title,
        u.first_name,
        u.last_name,
        c.level,
        c.group_number,
        d.department_name
    FROM students s
    INNER JOIN users u ON s.user_id = u.user_id 
    LEFT JOIN classes c ON s.current_class_id = c.class_id
    LEFT JOIN departments d ON c.department_id = d.department_id
    WHERE s.student_code IS NOT NULL
    ORDER BY s.student_code";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: แสดงคำสั่ง SQL
    echo "<h3>SQL Query:</h3>";
    echo "<pre>" . $query . "</pre>";
    
    // Debug: แสดงจำนวนข้อมูลที่ได้
    echo "<h3>จำนวนข้อมูลที่พบ: " . count($students) . " รายการ</h3>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบแสดงข้อมูลนักเรียน</title>
    <style>
        body { font-family: 'Sarabun', sans-serif; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .debug-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>ทดสอบแสดงข้อมูลนักเรียน</h1>

    <div class="debug-info">
        <h3>ข้อมูลการเชื่อมต่อ:</h3>
        <?php
        try {
            echo "สถานะการเชื่อมต่อ: เชื่อมต่อสำเร็จ<br>";
            echo "PHP Version: " . phpversion() . "<br>";
            echo "PDO Driver: " . $conn->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>รหัสนักเรียน</th>
                <th>ชื่อ-นามสกุล</th>
                <th>ชั้น/ห้อง</th>
                <th>แผนกวิชา</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['student_code']); ?></td>
                    <td>
                        <?php 
                        echo htmlspecialchars($student['title'] . ' ' . 
                             $student['first_name'] . ' ' . 
                             $student['last_name']); 
                        ?>
                    </td>
                    <td>
                        <?php 
                        echo htmlspecialchars(
                            ($student['level'] ? $student['level'] : '-') . '/' . 
                            ($student['group_number'] ? $student['group_number'] : '-')
                        ); 
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($student['department_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($student['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="debug-info">
        <h3>ข้อมูลดิบ (Raw Data):</h3>
        <pre><?php print_r($students); ?></pre>
    </div>
</body>
</html> 