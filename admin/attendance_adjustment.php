<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../db_connect.php';

$current_page = 'attendance_adjustment';
$page_title = 'ปรับข้อมูลเข้าแถว';
$page_header = 'ระบบช่วยนักเรียนที่เข้าแถวไม่ถึง 60%';

$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'auto_adjust_all') {
        $result = autoAdjustAllStudents();
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'adjust_single') {
        $student_id = $_POST['student_id'] ?? 0;
        if ($student_id > 0) {
            $result = autoAdjustSingleStudent($student_id);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

function getStudentsUnder60Percent() {
    try {
        $conn = getDB();
        
        $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($academic_year_query);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'] ?? 1;
        
        $total_days = 48;
        
        // ปรับปรุง Query ให้ใช้ JOIN แทน Subquery เพื่อความเร็ว
        $query = "
            SELECT 
                s.student_id,
                s.student_code,
                COALESCE(s.title, '') AS title,
                u.first_name,
                u.last_name,
                COALESCE(c.level, 'ม.6') AS level,
                COALESCE(c.group_number, '1') AS group_number,
                COALESCE(d.department_name, 'สามัญ') AS department_name,
                CONCAT(COALESCE(c.level, 'ม.6'), '/', COALESCE(c.group_number, '1')) AS class,
                COALESCE(att_summary.attended_days, 0) AS attended_days,
                COALESCE(att_summary.absent_days, 0) AS absent_days,
                ROUND((COALESCE(att_summary.attended_days, 0) / ?) * 100, 2) AS attendance_percentage
            FROM students s
            LEFT JOIN users u ON s.user_id = u.user_id
            LEFT JOIN classes c ON s.current_class_id = c.class_id
            LEFT JOIN departments d ON c.department_id = d.department_id
            LEFT JOIN (
                SELECT 
                    student_id,
                    SUM(CASE WHEN attendance_status = 'present' THEN 1 ELSE 0 END) AS attended_days,
                    SUM(CASE WHEN attendance_status = 'absent' THEN 1 ELSE 0 END) AS absent_days
                FROM attendance 
                WHERE academic_year_id = ?
                GROUP BY student_id
            ) att_summary ON s.student_id = att_summary.student_id
            WHERE s.status = 'กำลังศึกษา'
            HAVING attendance_percentage < 60
            ORDER BY attendance_percentage ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$total_days, $academic_year_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($students as &$student) {
            $student['total_days'] = $total_days;
            $target_days = ceil($total_days * 0.6);
            $current_attended = (int)$student['attended_days'];
            $student['days_needed'] = max(0, $target_days - $current_attended);
            $student['projected_percentage'] = round((($current_attended + $student['days_needed']) / $total_days) * 100, 2);
            
            if (empty($student['first_name'])) {
                $student['first_name'] = 'นักเรียน';
                $student['last_name'] = 'รหัส' . $student['student_code'];
            }
        }
        
        return $students;
        
    } catch (Exception $e) {
        error_log("Error in getStudentsUnder60Percent: " . $e->getMessage());
        return [];
    }
}

function autoAdjustSingleStudent($student_id) {
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($academic_year_query);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'] ?? 1;
        
        $student_query = "SELECT s.*, u.first_name, u.last_name FROM students s 
                         LEFT JOIN users u ON s.user_id = u.user_id 
                         WHERE s.student_id = ?";
        $stmt = $conn->prepare($student_query);
        $stmt->execute([$student_id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student) {
            throw new Exception('ไม่พบข้อมูลนักเรียน');
        }
        
        $total_days = 48;
        $target_days = ceil($total_days * 0.6);
        
        $current_attendance_query = "
            SELECT COUNT(*) as present_days 
            FROM attendance 
            WHERE student_id = ? AND academic_year_id = ? AND attendance_status = 'present'
        ";
        $stmt = $conn->prepare($current_attendance_query);
        $stmt->execute([$student_id, $academic_year_id]);
        $current_present = $stmt->fetch(PDO::FETCH_ASSOC)['present_days'];
        
        $days_needed = max(0, $target_days - $current_present);
        
        if ($days_needed == 0) {
            $conn->rollBack();
            return [
                'success' => false,
                'message' => 'นักเรียนมีการเข้าแถวมากกว่า 60% แล้ว'
            ];
        }
        
        $absent_days_query = "
            SELECT date 
            FROM attendance 
            WHERE student_id = ? AND academic_year_id = ? AND attendance_status = 'absent'
            ORDER BY date DESC
            LIMIT ?
        ";
        $stmt = $conn->prepare($absent_days_query);
        $stmt->execute([$student_id, $academic_year_id, $days_needed]);
        $absent_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $updated_days = 0;
        foreach ($absent_dates as $date) {
            $update_query = "
                UPDATE attendance 
                SET attendance_status = 'present',
                    check_method = 'Manual',
                    check_time = '08:00:00',
                    remarks = 'ปรับจาก absent เป็น present เพื่อให้เกิน 60%'
                WHERE student_id = ? AND date = ? AND attendance_status = 'absent'
            ";
            
            $stmt = $conn->prepare($update_query);
            if ($stmt->execute([$student_id, $date])) {
                $updated_days++;
            }
        }
        
        if (table_exists($conn, 'student_academic_records')) {
            $update_records_query = "
                UPDATE student_academic_records 
                SET 
                    total_attendance_days = COALESCE(total_attendance_days, 0) + ?,
                    total_absence_days = GREATEST(0, COALESCE(total_absence_days, 0) - ?)
                WHERE student_id = ? AND academic_year_id = ?
            ";
            $stmt = $conn->prepare($update_records_query);
            $stmt->execute([$updated_days, $updated_days, $student_id, $academic_year_id]);
        }
        
        $conn->commit();
        
        $new_percentage = round((($current_present + $updated_days) / $total_days) * 100, 2);
        $student_name = $student['first_name'] . ' ' . $student['last_name'];
        
        return [
            'success' => true,
            'message' => "✅ ปรับสถานะสำเร็จ! {$student_name} - อัปเดต {$updated_days} วัน เปอร์เซ็นต์ใหม่: {$new_percentage}%"
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) $conn->rollBack();
        error_log("Error in autoAdjustSingleStudent: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

function autoAdjustAllStudents() {
    try {
        $conn = getDB();
        $conn->beginTransaction();
        
        $academic_year_query = "SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1";
        $stmt = $conn->query($academic_year_query);
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $academic_year_id = $academic_year['academic_year_id'] ?? 1;
        
        $students = getStudentsUnder60Percent();
        $students_to_process = array_filter($students, function($s) { return $s['days_needed'] > 0; });
        
        if (empty($students_to_process)) {
            $conn->rollBack();
            return [
                'success' => false,
                'message' => 'ไม่พบนักเรียนที่ต้องปรับข้อมูล'
            ];
        }
        
        $total_updated = 0;
        $processed_students = [];
        
        // ใช้ batch processing แทนการ loop แต่ละคน
        foreach ($students_to_process as $student) {
            $student_id = $student['student_id'];
            $days_needed = $student['days_needed'];
            
            // ดึงวันที่ขาดเรียนล่าสุดตามจำนวนที่ต้องการ
            $absent_days_query = "
                SELECT date 
                FROM attendance 
                WHERE student_id = ? AND academic_year_id = ? AND attendance_status = 'absent'
                ORDER BY date DESC
                LIMIT ?
            ";
            $stmt = $conn->prepare($absent_days_query);
            $stmt->execute([$student_id, $academic_year_id, $days_needed]);
            $absent_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($absent_dates)) {
                // ใช้ IN clause เพื่อ update หลาย record พร้อมกัน
                $date_placeholders = str_repeat('?,', count($absent_dates) - 1) . '?';
                $update_query = "
                    UPDATE attendance 
                    SET attendance_status = 'present',
                        check_method = 'Manual',
                        check_time = '08:00:00',
                        remarks = 'ปรับจาก absent เป็น present เพื่อให้เกิน 60% (Batch Update)'
                    WHERE student_id = ? AND academic_year_id = ? AND date IN ($date_placeholders) AND attendance_status = 'absent'
                ";
                
                $params = array_merge([$student_id, $academic_year_id], $absent_dates);
                $stmt = $conn->prepare($update_query);
                
                if ($stmt->execute($params)) {
                    $updated_rows = $stmt->rowCount();
                    $total_updated += $updated_rows;
                    $processed_students[] = [
                        'name' => $student['first_name'] . ' ' . $student['last_name'],
                        'updated_days' => $updated_rows
                    ];
                }
            }
        }
        
        // อัพเดท student_academic_records ถ้าตารางมีอยู่
        if (table_exists($conn, 'student_academic_records')) {
            foreach ($students_to_process as $student) {
                $student_id = $student['student_id'];
                $days_needed = min($student['days_needed'], $student['absent_days']);
                
                $update_records_query = "
                    UPDATE student_academic_records 
                    SET 
                        total_attendance_days = COALESCE(total_attendance_days, 0) + ?,
                        total_absence_days = GREATEST(0, COALESCE(total_absence_days, 0) - ?)
                    WHERE student_id = ? AND academic_year_id = ?
                ";
                $stmt = $conn->prepare($update_records_query);
                $stmt->execute([$days_needed, $days_needed, $student_id, $academic_year_id]);
            }
        }
        
        $conn->commit();
        
        $success_count = count($processed_students);
        $summary = "✅ ประมวลผลสำเร็จ! อัปเดต {$success_count} คน รวม {$total_updated} วัน";
        
        return [
            'success' => $success_count > 0,
            'message' => $summary
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) $conn->rollBack();
        error_log("Error in autoAdjustAllStudents: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

function table_exists($conn, $table_name) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table_name]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

$students_under_60 = getStudentsUnder60Percent();

// กำหนดตัวแปรสำหรับเทมเพลต
$extra_css = [
    'https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css',
    'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css',
    'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css'
];
$extra_js = [
    'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
    'https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
    'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js',
    'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js',
    'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js'
];

// เริ่มต้น output buffering สำหรับ content
ob_start();
?>

<style>
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        border-radius: 7px;
        padding: 25px;
        box-shadow: 0px 15px 30px rgba(0,0,0,0.12);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-number {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .stat-number.danger { color: #FA896B; }
    .stat-number.warning { color: #FFAE1F; }
    .stat-number.success { color: #13DEB9; }
    .stat-number.info { color: #539BFF; }
    
    .stat-label {
        color: #5A6A85;
        font-size: 14px;
        font-weight: 500;
    }
    
    .main-card {
        background: white;
        border-radius: 7px;
        box-shadow: 0px 15px 30px rgba(0,0,0,0.12);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .card-header {
        background: linear-gradient(135deg, #5D87FF, #4a6ccc);
        color: white;
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .card-header h3 {
        margin: 0;
        font-size: 20px;
        font-weight: 600;
        flex: 1;
    }
    
    .auto-adjust-btn {
        background: linear-gradient(135deg, #13DEB9, #0fb294);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 7px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }
    
    .auto-adjust-btn:hover {
        background: linear-gradient(135deg, #0fb294, #13DEB9);
        transform: translateY(-1px);
        color: white;
        box-shadow: 0px 15px 30px rgba(19, 222, 185, 0.3);
    }
    
    .auto-adjust-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .btn-loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .btn-loading .btn-text {
        opacity: 0;
    }
    
    .btn-loading::after {
        content: "";
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    .progress-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .progress-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 400px;
        width: 90%;
    }
    
    .progress-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #2A3547;
    }
    
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #f0f0f0;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(135deg, #5D87FF, #4a6ccc);
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .progress-text {
        color: #5A6A85;
        font-size: 14px;
    }
    
    .students-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .students-table th {
        background: #F6F9FC;
        padding: 15px;
        text-align: left;
        font-weight: 600;
        color: #2A3547;
        border-bottom: 1px solid #ebf1f6;
    }
    
    .students-table td {
        padding: 15px;
        border-bottom: 1px solid #ebf1f6;
        vertical-align: middle;
    }
    
    .students-table tbody tr:hover {
        background: #F6F9FC;
    }
    
    .student-info {
        display: flex;
        flex-direction: column;
    }
    
    .student-name {
        font-weight: 600;
        color: #2A3547;
        margin-bottom: 4px;
    }
    
    .student-code {
        color: #5A6A85;
        font-size: 13px;
    }
    
    .attendance-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        min-width: 60px;
    }
    
    .attendance-badge.critical {
        background: #FBF2EF;
        color: #FA896B;
    }
    
    .attendance-badge.warning {
        background: #FEF5E5;
        color: #FFAE1F;
    }
    
    .attendance-badge.success {
        background: #E6FFFA;
        color: #13DEB9;
    }
    
    .adjust-btn {
        background: linear-gradient(135deg, #5D87FF, #4a6ccc);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 7px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
        position: relative;
    }
    
    .adjust-btn:hover {
        background: linear-gradient(135deg, #4a6ccc, #5D87FF);
        transform: translateY(-1px);
        box-shadow: 0px 15px 30px rgba(93, 135, 255, 0.3);
    }
    
    .adjust-btn:disabled {
        background: #6c757d;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .adjust-btn.btn-loading {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .adjust-btn.btn-loading .btn-text {
        opacity: 0;
    }
    
    .adjust-btn.btn-loading::after {
        content: "";
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s linear infinite;
    }
    
    .no-students {
        text-align: center;
        padding: 60px 30px;
        color: #5A6A85;
    }
    
    .no-students .material-icons {
        font-size: 64px;
        color: #13DEB9;
        margin-bottom: 20px;
        opacity: 0.7;
    }
    
    .no-students h3 {
        color: #2A3547;
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .days-needed {
        text-align: center;
        color: #FA896B;
        font-weight: 600;
    }
    
    .page-header {
        background: linear-gradient(135deg, #5D87FF 0%, #4a6ccc 100%);
        color: white;
        padding: 30px;
        border-radius: 7px;
        margin-bottom: 30px;
        box-shadow: 0px 15px 30px rgba(93, 135, 255, 0.3);
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .page-header .material-icons {
        font-size: 32px;
    }
    
    .page-header p {
        opacity: 0.9;
        font-size: 16px;
        margin: 0;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 7px;
        margin-bottom: 20px;
        border-left: 4px solid;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .alert-success {
        background-color: #E6FFFA;
        border-color: #13DEB9;
        color: #0fb294;
    }
    
    .alert-danger {
        background-color: #FBF2EF;
        border-color: #FA896B;
        color: #c86e56;
    }
    
    .alert .material-icons {
        font-size: 20px;
    }
    
    /* DataTable Custom Styles */
    .dataTables_wrapper .dt-buttons {
        margin-bottom: 15px;
    }
    
    .dataTables_wrapper .dt-buttons .btn {
        margin-right: 10px;
        margin-bottom: 5px;
    }
    
    .dataTables_wrapper .form-select {
        margin-left: 15px;
        width: auto;
        display: inline-block;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 15px;
    }
    
    .dataTables_wrapper .dataTables_length label,
    .dataTables_wrapper .dataTables_filter label {
        font-weight: 500;
        color: #2A3547;
    }
    
    .dataTables_wrapper .dataTables_length select,
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #ddd;
        border-radius: 7px;
        padding: 5px 10px;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        border-radius: 7px !important;
        margin: 0 2px;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: linear-gradient(135deg, #5D87FF, #4a6ccc) !important;
        border-color: #5D87FF !important;
        color: white !important;
    }
    
    .dataTables_wrapper .dataTables_info {
        color: #5A6A85;
        font-weight: 500;
    }
    
    #studentsTable_wrapper .table thead th {
        background: #F6F9FC;
        color: #2A3547;
        font-weight: 600;
        border-bottom: 2px solid #5D87FF;
    }
    
    #studentsTable tbody tr:hover {
        background-color: #F6F9FC;
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <h1>
        <span class="material-icons">auto_fix_high</span>
        <?php echo $page_title; ?>
    </h1>
    <p><?php echo $page_header; ?></p>
</div>

<!-- Alert Messages -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?>">
        <span class="material-icons">
            <?php echo $message_type === 'success' ? 'check_circle' : 'error'; ?>
        </span>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if (count($students_under_60) > 0): ?>
    <!-- Statistics Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-number danger"><?php echo count($students_under_60); ?></div>
            <div class="stat-label">นักเรียนที่ต้องปรับ</div>
        </div>
        <div class="stat-card">
            <div class="stat-number warning">
                <?php echo count(array_filter($students_under_60, function($s) { return $s['attendance_percentage'] >= 40; })); ?>
            </div>
            <div class="stat-label">เข้าแถว 40-59%</div>
        </div>
        <div class="stat-card">
            <div class="stat-number danger">
                <?php echo count(array_filter($students_under_60, function($s) { return $s['attendance_percentage'] < 40; })); ?>
            </div>
            <div class="stat-label">เข้าแถวต่ำกว่า 40%</div>
        </div>
        <div class="stat-card">
            <div class="stat-number success">60%</div>
            <div class="stat-label">เป้าหมาย</div>
        </div>
    </div>
    
    <!-- Students Table -->
    <div class="main-card">
        <div class="card-header">
            <h3>รายชื่อนักเรียนที่ต้องปรับข้อมูล</h3>
            <form method="POST" style="margin: 0;" id="batchForm">
                <input type="hidden" name="action" value="auto_adjust_all">
                <button type="button" class="auto-adjust-btn" id="batchBtn"
                        onclick="confirmBatchUpdate(<?php echo count($students_under_60); ?>)">
                    <span class="material-icons">auto_fix_high</span>
                    <span class="btn-text">ปรับทุกคนอัตโนมัติ</span>
                </button>
            </form>
        </div>
        
        <div style="padding: 20px;">
            <table id="studentsTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>นักเรียน</th>
                        <th>ชั้นเรียน</th>
                        <th>แผนกวิชา</th>
                        <th>การเข้าแถวปัจจุบัน</th>
                        <th>เปอร์เซ็นต์</th>
                        <th>ต้องเพิ่ม (วัน)</th>
                        <th>หลังปรับ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_under_60 as $student): ?>
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-name">
                                        <?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>
                                    </div>
                                    <div class="student-code"><?php echo htmlspecialchars($student['student_code']); ?></div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($student['class']); ?></td>
                            <td><?php echo htmlspecialchars($student['department_name']); ?></td>
                            <td><?php echo $student['attended_days']; ?>/<?php echo $student['total_days']; ?> วัน</td>
                            <td>
                                <span class="attendance-badge <?php echo $student['attendance_percentage'] < 40 ? 'critical' : 'warning'; ?>">
                                    <?php echo $student['attendance_percentage']; ?>%
                                </span>
                            </td>
                            <td class="days-needed"><?php echo $student['days_needed']; ?> วัน</td>
                            <td>
                                <span class="attendance-badge success">
                                    <?php echo $student['projected_percentage']; ?>%
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="adjust_single">
                                    <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                    <button type="button" class="adjust-btn" 
                                            onclick="adjustSingleStudent(this, 
                                                <?php echo $student['student_id']; ?>, 
                                                '<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'], ENT_QUOTES); ?>', 
                                                <?php echo $student['attended_days']; ?>, 
                                                <?php echo $student['attendance_percentage']; ?>, 
                                                <?php echo ($student['attended_days'] + $student['days_needed']); ?>, 
                                                <?php echo $student['projected_percentage']; ?>)">
                                        <span class="material-icons" style="font-size: 16px;">check</span>
                                        <span class="btn-text">ปรับเดี่ยว</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
<?php else: ?>
    <!-- No Students Found -->
    <div class="main-card">
        <div class="no-students">
            <div class="material-icons">sentiment_very_satisfied</div>
            <h3>ไม่พบนักเรียนที่ต้องปรับข้อมูล</h3>
            <p>นักเรียนทุกคนมีการเข้าแถวมากกว่าหรือเท่ากับ 60% แล้ว</p>
        </div>
    </div>
<?php endif; ?>

<!-- Progress Overlay -->
<div class="progress-overlay" id="progressOverlay">
    <div class="progress-content">
        <div class="progress-title" id="progressTitle">กำลังปรับข้อมูลการเข้าแถว</div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        <div class="progress-text" id="progressText">กรุณารอสักครู่...</div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#studentsTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6] // Exclude action column
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6] // Exclude action column
                },
                customize: function (doc) {
                    doc.defaultStyle.font = 'THSarabunNew';
                }
            },
            {
                extend: 'print',
                text: '<i class="fas fa-print"></i> พิมพ์',
                className: 'btn btn-info btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6] // Exclude action column
                }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/th.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "ทั้งหมด"]],
        order: [[4, 'asc']], // Sort by attendance percentage (ascending)
        columnDefs: [
            {
                targets: [4, 6], // Percentage columns
                type: 'num',
                render: function(data, type, row) {
                    if (type === 'display' || type === 'type') {
                        return data;
                    }
                    // For sorting, extract numeric value
                    return parseFloat(data.toString().replace('%', ''));
                }
            },
            {
                targets: [5], // Days needed column
                type: 'num'
            },
            {
                targets: [7], // Action column
                orderable: false,
                searchable: false
            }
        ],
        initComplete: function () {
            // Add custom search for class filter
            this.api().columns([1]).every(function () {
                var column = this;
                var select = $('<select class="form-select form-select-sm"><option value="">ทุกชั้นเรียน</option></select>')
                    .appendTo($('.dt-buttons'))
                    .on('change', function () {
                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                    });

                column.data().unique().sort().each(function (d, j) {
                    if (d) {
                        select.append('<option value="' + d + '">' + d + '</option>');
                    }
                });
            });
        }
    });

    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 500);
        });
    }, 5000);
});

// Batch update confirmation and progress
function confirmBatchUpdate(studentCount) {
    if (confirm(`คุณต้องการปรับข้อมูลให้นักเรียนทั้งหมด ${studentCount} คน ให้มีการเข้าแถวเกิน 60% หรือไม่?\n\nระบบจะทำการเปลี่ยนสถานะจาก absent เป็น present ในวันที่ขาดเรียน\n\nยืนยันการดำเนินการ?`)) {
        startBatchUpdate(studentCount);
    }
}

function startBatchUpdate(studentCount) {
    const btn = document.getElementById('batchBtn');
    const overlay = document.getElementById('progressOverlay');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    // Show loading state
    btn.classList.add('btn-loading');
    btn.disabled = true;
    overlay.style.display = 'flex';
    
    // Simulate progress (since we can't track real progress in PHP)
    let progress = 0;
    const increment = 100 / Math.max(studentCount, 10);
    
    const progressInterval = setInterval(() => {
        progress += increment;
        if (progress <= 95) {
            progressFill.style.width = progress + '%';
            progressText.textContent = `ประมวลผล ${Math.floor(progress)}% (${Math.floor((progress/100) * studentCount)}/${studentCount} คน)`;
        }
    }, 100);
    
    // Submit form
    setTimeout(() => {
        clearInterval(progressInterval);
        progressFill.style.width = '100%';
        progressText.textContent = 'เสร็จสิ้น! กำลังบันทึกข้อมูล...';
        
        setTimeout(() => {
            document.getElementById('batchForm').submit();
        }, 500);
    }, Math.max(2000, studentCount * 50));
}

// Individual student adjustment with loading
function adjustSingleStudent(button, studentId, studentName, currentDays, currentPercentage, targetDays, targetPercentage) {
    if (confirm(`ปรับข้อมูลให้ ${studentName}?\n\nปัจจุบัน: ${currentDays} วัน (${currentPercentage}%)\nจะปรับเป็น: ${targetDays} วัน (${targetPercentage}%)\n\nยืนยัน?`)) {
        
        // Show loading state
        button.classList.add('btn-loading');
        button.disabled = true;
        
        // Submit form
        button.closest('form').submit();
    }
}
</script>

<?php
// จบการ buffer และเก็บ content
$content = ob_get_clean();

// โหลด templates
include "templates/header.php";
include "templates/sidebar.php";
echo '<div class="main-content">' . $content . '</div>';
include "templates/footer.php";
?>