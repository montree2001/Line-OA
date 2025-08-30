<?php
/**
 * attendance_report_simple.php - หน้ารายงานการเข้าแถวแบบง่าย (ทดสอบ)
 */

try {
    // เชื่อมต่อฐานข้อมูล
    require_once 'db_connect.php';
    $conn = getDB();
    
    // ดึงข้อมูลปีการศึกษา
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$academic_year) {
        throw new Exception("ไม่พบข้อมูลปีการศึกษา");
    }
    
    // คำนวณสัปดาห์
    $start_date = new DateTime($academic_year['start_date']);
    $end_date = new DateTime($academic_year['end_date']);
    $total_days = $start_date->diff($end_date)->days;
    $total_weeks = ceil($total_days / 7);
    
    // ดึงแผนกวิชา
    $query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
    $stmt = $conn->query($query);
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานการเข้าแถว - ทดสอบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">รายงานการเข้าแถว (ทดสอบ)</h1>
        
        <div class="card">
            <div class="card-header">
                <h5>ข้อมูลระบบ</h5>
            </div>
            <div class="card-body">
                <p><strong>ปีการศึกษา:</strong> <?php echo $academic_year['year']; ?> เทอม <?php echo $academic_year['semester']; ?></p>
                <p><strong>จำนวนสัปดาห์:</strong> <?php echo $total_weeks; ?> สัปดาห์</p>
                <p><strong>จำนวนแผนกวิชา:</strong> <?php echo count($departments); ?> แผนก</p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>ฟอร์มค้นหา</h5>
            </div>
            <div class="card-body">
                <form>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">แผนกวิชา</label>
                            <select class="form-select">
                                <option value="">-- เลือกแผนกวิชา --</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สัปดาห์</label>
                            <select class="form-select">
                                <option value="">-- เลือกสัปดาห์ --</option>
                                <?php for ($i = 1; $i <= $total_weeks; $i++): ?>
                                <option value="<?php echo $i; ?>">สัปดาห์ที่ <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary">ค้นหา (ทดสอบ)</button>
                        <button type="button" class="btn btn-warning">ประเมินผลกิจกรรม (ทดสอบ)</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="mt-4 text-center text-muted">
            <small>ไฟล์ทดสอบ - ถ้าหน้านี้แสดงได้แสดงว่าระบบพื้นฐานทำงานปกติ</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>