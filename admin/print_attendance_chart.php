<?php
/**
 * print_attendance_chart.php - หน้าวิเคราะห์ข้อมูลการเข้าแถวแบบ Interactive
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

require_once '../db_connect.php';

// เชื่อมต่อฐานข้อมูล
$conn = getDB();

// รับพารามิเตอร์จาก URL หรือ POST
$class_id = $_REQUEST['class_id'] ?? '';
$start_date = $_REQUEST['start_date'] ?? '';
$end_date = $_REQUEST['end_date'] ?? '';
$week_number = $_REQUEST['week_number'] ?? '1';
$end_week = $_REQUEST['end_week'] ?? $week_number;
$search = $_REQUEST['search'] ?? '';
$search_type = $_REQUEST['search_type'] ?? 'class';

// ถ้าไม่มีข้อมูลเริ่มต้น ให้ใช้ค่าเริ่มต้น
if (empty($start_date) || empty($end_date)) {
    // ดึงปีการศึกษาปัจจุบัน
    $query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years WHERE is_active = 1 LIMIT 1";
    $stmt = $conn->query($query);
    $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($academic_year) {
        $start_date = $start_date ?: $academic_year['start_date'];
        $end_date = $end_date ?: $academic_year['end_date'];
    }
}

// ดึงข้อมูลแผนก
$query = "SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name";
$stmt = $conn->query($query);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลปีการศึกษา
$query = "SELECT academic_year_id, year, semester, start_date, end_date FROM academic_years ORDER BY year DESC, semester DESC";
$stmt = $conn->query($query);
$academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ถ้ามีการเลือกข้อมูล ให้วิเคราะห์
$chart_data = null;
$class_info = null;
$students = [];

if (!empty($class_id) || !empty($search)) {
    // ดึงข้อมูลและวิเคราะห์
    include 'includes/attendance_analysis.php';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>วิเคราะห์ข้อมูลการเข้าแถว - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DateRangePicker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Kanit', sans-serif;
        }
        
        .main-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1400px;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .chart-section {
            padding: 20px;
        }
        
        .stats-cards {
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
            margin-bottom: 15px;
        }
        
        .stat-card.present { border-left-color: #28a745; }
        .stat-card.late { border-left-color: #ffc107; }
        .stat-card.absent { border-left-color: #dc3545; }
        .stat-card.total { border-left-color: #007bff; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
        }
        
        .btn-print:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
            color: white;
        }
        
        .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 12px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: 10px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .analysis-tabs {
            margin-top: 20px;
        }
        
        .trend-chart {
            height: 400px;
        }
        
        .comparison-chart {
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header -->
            <div class="header-section">
                <h1><i class="material-icons" style="vertical-align: middle; margin-right: 10px;">analytics</i>วิเคราะห์ข้อมูลการเข้าแถว</h1>
                <p class="mb-0">ระบบวิเคราะห์และรายงานการเข้าแถวแบบครอบคลุม</p>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form id="filterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">แผนกวิชา</label>
                            <select id="department" name="department_id" class="form-select">
                                <option value="">เลือกแผนกวิชา</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">ห้องเรียน</label>
                            <select id="class" name="class_id" class="form-select">
                                <option value="">เลือกห้องเรียน</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">ช่วงเวลา</label>
                            <input type="text" id="daterange" name="daterange" class="form-control" placeholder="เลือกช่วงวันที่">
                            <input type="hidden" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                            <input type="hidden" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="material-icons">search</i> วิเคราะห์
                                </button>
                                <button type="button" id="resetBtn" class="btn btn-outline-secondary">
                                    <i class="material-icons">refresh</i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="compareMode" name="compare_mode">
                                <label class="form-check-label" for="compareMode">
                                    เปรียบเทียบกับช่วงเวลาอื่น
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <select name="analysis_type" class="form-select" id="analysisType">
                                <option value="overview">ภาพรวม</option>
                                <option value="trend">แนวโน้ม</option>
                                <option value="individual">รายบุคคล</option>
                                <option value="comparison">เปรียบเทียบ</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Main Content -->
            <div class="chart-section">
                <?php if ($chart_data): ?>
                    <!-- Statistics Cards -->
                    <div class="stats-cards">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="stat-card present">
                                    <div class="stat-number text-success"><?php echo $chart_data['totalPresent']; ?></div>
                                    <div class="stat-label">มาแถว</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card late">
                                    <div class="stat-number text-warning"><?php echo $chart_data['totalLate']; ?></div>
                                    <div class="stat-label">สาย</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card absent">
                                    <div class="stat-number text-danger"><?php echo $chart_data['totalAbsent']; ?></div>
                                    <div class="stat-label">ขาด</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card total">
                                    <div class="stat-number text-primary"><?php echo number_format($chart_data['attendanceRate'], 1); ?>%</div>
                                    <div class="stat-label">อัตราการเข้าแถว</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="material-icons text-primary" style="vertical-align: middle;">donut_small</i>
                                    สัดส่วนการเข้าแถว
                                </h5>
                                <canvas id="pieChart" height="300"></canvas>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5 class="mb-3">
                                    <i class="material-icons text-primary" style="vertical-align: middle;">bar_chart</i>
                                    แนวโน้มรายวัน
                                </h5>
                                <canvas id="lineChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analysis Tabs -->
                    <div class="analysis-tabs">
                        <ul class="nav nav-tabs" id="analysisTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="summary-tab" data-bs-toggle="tab" data-bs-target="#summary" type="button">
                                    สรุปผล
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">
                                    รายละเอียด
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="insights-tab" data-bs-toggle="tab" data-bs-target="#insights" type="button">
                                    ข้อเสนอแนะ
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="analysisTabContent">
                            <div class="tab-pane fade show active" id="summary" role="tabpanel">
                                <div class="chart-container">
                                    <h6>สรุปผลการวิเคราะห์</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>จำนวนนักเรียนทั้งหมด:</strong> <?php echo count($students); ?> คน</p>
                                            <p><strong>ช่วงเวลาที่วิเคราะห์:</strong> <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                                            <p><strong>จำนวนวันเรียน:</strong> <?php echo $chart_data['totalDays']; ?> วัน</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>อัตราการเข้าแถวเฉลี่ย:</strong> <?php echo number_format($chart_data['attendanceRate'], 1); ?>%</p>
                                            <p><strong>จำนวนครั้งที่เข้าแถว:</strong> <?php echo $chart_data['totalPresent'] + $chart_data['totalLate']; ?> ครั้ง</p>
                                            <p><strong>จำนวนครั้งที่ขาดแถว:</strong> <?php echo $chart_data['totalAbsent']; ?> ครั้ง</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="details" role="tabpanel">
                                <div class="chart-container">
                                    <h6>รายละเอียดการเข้าแถว</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>วันที่</th>
                                                    <th>มา</th>
                                                    <th>สาย</th>
                                                    <th>ขาด</th>
                                                    <th>อัตรา (%)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detailsTable">
                                                <!-- จะถูกเติมด้วย JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="insights" role="tabpanel">
                                <div class="chart-container">
                                    <h6>ข้อเสนอแนะและข้อสังเกต</h6>
                                    <div id="insightsContent">
                                        <?php
                                        $insights = [];
                                        if ($chart_data['attendanceRate'] >= 95) {
                                            $insights[] = "🎉 อัตราการเข้าแถวอยู่ในระดับดีเยี่ยม (≥95%)";
                                        } elseif ($chart_data['attendanceRate'] >= 90) {
                                            $insights[] = "✅ อัตราการเข้าแถวอยู่ในระดับดี (90-94%)";
                                        } elseif ($chart_data['attendanceRate'] >= 80) {
                                            $insights[] = "⚠️ อัตราการเข้าแถวอยู่ในระดับปานกลาง (80-89%) ควรติดตามอย่างใกล้ชิด";
                                        } else {
                                            $insights[] = "❌ อัตราการเข้าแถวต่ำ (<80%) ต้องดำเนินการปรับปรุงอย่างเร่งด่วน";
                                        }
                                        
                                        if ($chart_data['totalLate'] > $chart_data['totalPresent'] * 0.3) {
                                            $insights[] = "⏰ มีปัญหาการมาสายค่อนข้างมาก ควรหาแนวทางแก้ไข";
                                        }
                                        
                                        if ($chart_data['totalAbsent'] > $chart_data['totalPresent'] * 0.2) {
                                            $insights[] = "📞 ควรติดต่อผู้ปกครองเพื่อติดตามการขาดเรียนที่มีจำนวนมาก";
                                        }
                                        ?>
                                        
                                        <ul class="list-unstyled">
                                            <?php foreach ($insights as $insight): ?>
                                                <li class="mb-2 p-2 bg-light rounded"><?php echo $insight; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Print Button -->
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-print btn-lg" onclick="printReport()">
                            <i class="material-icons" style="vertical-align: middle;">print</i>
                            พิมพ์รายงาน PDF
                        </button>
                    </div>
                    
                <?php else: ?>
                    <div class="no-data">
                        <i class="material-icons" style="font-size: 64px; color: #dee2e6;">bar_chart</i>
                        <h4 class="mt-3">เลือกข้อมูลเพื่อเริ่มการวิเคราะห์</h4>
                        <p>กรุณาเลือกแผนกวิชา ห้องเรียน และช่วงเวลาที่ต้องการวิเคราะห์</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    
    <script>
        // Chart data from PHP
        const chartData = <?php echo json_encode($chart_data ?: []); ?>;
        
        $(document).ready(function() {
            // Initialize Select2
            $('#department, #class, #analysisType').select2();
            
            // Initialize DateRangePicker
            $('#daterange').daterangepicker({
                startDate: '<?php echo $start_date; ?>',
                endDate: '<?php echo $end_date; ?>',
                locale: {
                    format: 'DD/MM/YYYY',
                    separator: ' - ',
                    applyLabel: 'ตกลง',
                    cancelLabel: 'ยกเลิก',
                    fromLabel: 'จาก',
                    toLabel: 'ถึง',
                    customRangeLabel: 'กำหนดเอง',
                    weekLabel: 'W',
                    daysOfWeek: ['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'],
                    monthNames: ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'],
                    firstDay: 1
                }
            });
            
            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
                $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
            });
            
            // Department change handler
            $('#department').change(function() {
                loadClasses($(this).val());
            });
            
            // Reset button
            $('#resetBtn').click(function() {
                $('#filterForm')[0].reset();
                $('#class').empty().append('<option value="">เลือกห้องเรียน</option>');
                $('#department, #class, #analysisType').val('').trigger('change');
            });
            
            // Load charts if data exists
            if (chartData && Object.keys(chartData).length > 0) {
                initializeCharts();
                loadDetailTable();
            }
        });
        
        function loadClasses(departmentId) {
            if (!departmentId) {
                $('#class').empty().append('<option value="">เลือกห้องเรียน</option>');
                return;
            }
            
            $.ajax({
                url: 'ajax/get_classes.php',
                method: 'POST',
                data: { department_id: departmentId },
                success: function(response) {
                    const classes = JSON.parse(response);
                    $('#class').empty().append('<option value="">เลือกห้องเรียน</option>');
                    
                    classes.forEach(function(cls) {
                        $('#class').append(`<option value="${cls.class_id}">${cls.level}/${cls.group_number}</option>`);
                    });
                }
            });
        }
        
        function initializeCharts() {
            // Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: ['มาแถว', 'สาย', 'ขาด'],
                    datasets: [{
                        data: [chartData.totalPresent, chartData.totalLate, chartData.totalAbsent],
                        backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Line Chart
            if (chartData.dailyStats) {
                const lineCtx = document.getElementById('lineChart').getContext('2d');
                const dates = chartData.dailyStats.map(day => day.date);
                const rates = chartData.dailyStats.map(day => day.attendance_rate);
                
                new Chart(lineCtx, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'อัตราการเข้าแถว (%)',
                            data: rates,
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    callback: function(value, index) {
                                        const date = this.getLabelForValue(value);
                                        return new Date(date).toLocaleDateString('th-TH', { 
                                            month: 'short', 
                                            day: 'numeric' 
                                        });
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `อัตราการเข้าแถว: ${context.parsed.y.toFixed(1)}%`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        
        function loadDetailTable() {
            if (!chartData.dailyStats) return;
            
            const tbody = document.getElementById('detailsTable');
            tbody.innerHTML = '';
            
            chartData.dailyStats.forEach(function(day) {
                const row = `
                    <tr>
                        <td>${new Date(day.date).toLocaleDateString('th-TH')}</td>
                        <td><span class="badge bg-success">${day.present}</span></td>
                        <td><span class="badge bg-warning">${day.late}</span></td>
                        <td><span class="badge bg-danger">${day.absent}</span></td>
                        <td>${day.attendance_rate.toFixed(1)}%</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }
        
        function printReport() {
            const params = new URLSearchParams(window.location.search);
            params.append('print', '1');
            window.open('print_attendance_chart_pdf.php?' + params.toString(), '_blank');
        }
    </script>
</body>
</html>