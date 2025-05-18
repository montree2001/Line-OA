<div class="report-control-panel">
    <form action="" method="get" id="report-form">
        <div class="row">
            <?php if ($_SESSION['user_role'] == 'admin'): ?>
            <div class="col-md-4 col-lg-3 mb-3">
                <label for="department_id" class="form-label">แผนกวิชา</label>
                <select name="department_id" id="department_id" class="form-select select2">
                    <option value="">-- เลือกแผนกวิชา --</option>
                    <?php foreach ($departments as $dept): ?>
                    <option value="<?php echo $dept['department_id']; ?>" <?php echo ($department_id == $dept['department_id']) ? 'selected' : ''; ?>>
                        <?php echo $dept['department_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="col-md-4 col-lg-3 mb-3">
                <label for="class_id" class="form-label">ห้องเรียน</label>
                <select name="class_id" id="class_id" class="form-select select2" required>
                    <option value="">-- เลือกห้องเรียน --</option>
                    <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['class_id']; ?>" <?php echo ($class_id == $class['class_id']) ? 'selected' : ''; ?>>
                        <?php echo $class['class_name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4 col-lg-3 mb-3">
                <label for="start_week" class="form-label">เริ่มต้นสัปดาห์ที่</label>
                <select name="start_week" id="start_week" class="form-select select2" required>
                    <?php foreach ($week_options as $week): ?>
                    <option value="<?php echo $week['number']; ?>" <?php echo ($start_week == $week['number']) ? 'selected' : ''; ?>>
                        <?php echo $week['text']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4 col-lg-3 mb-3">
                <label for="end_week" class="form-label">สิ้นสุดสัปดาห์ที่</label>
                <select name="end_week" id="end_week" class="form-select select2" required>
                    <?php foreach ($week_options as $week): ?>
                    <option value="<?php echo $week['number']; ?>" <?php echo ($end_week == $week['number']) ? 'selected' : ''; ?>>
                        <?php echo $week['text']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-8 col-lg-9 mb-3">
                <label for="search" class="form-label">ค้นหานักเรียน</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="search" name="search" placeholder="ค้นหาตามรหัสนักศึกษา ชื่อ หรือนามสกุล" value="<?php echo htmlspecialchars($search_term); ?>">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">search</span> ค้นหา
                    </button>
                </div>
            </div>
            
            <div class="col-md-4 col-lg-3 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <span class="material-icons">filter_alt</span> แสดงรายงาน
                </button>
            </div>
        </div>
    </form>
</div>

<?php if (!empty($report_data)): ?>
<div class="report-preview">
    <div class="report-header">
        <h4 class="text-center">รายงานการเข้าแถวของนักเรียน</h4>
        <p class="text-center">
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> 
            สัปดาห์ที่ <?php echo $report_data['week_number']; ?><br>
            ระหว่างวันที่ <?php echo date('j/n/Y', strtotime($report_data['start_date'])); ?> ถึง 
            วันที่ <?php echo date('j/n/Y', strtotime($report_data['end_date'])); ?><br>
            ระดับชั้น <?php echo $selected_class['level']; ?> กลุ่ม <?php echo $selected_class['group_number']; ?> 
            แผนกวิชา<?php echo $selected_class['department_name']; ?>
        </p>
    </div>
    
    <div class="table-responsive mt-3">
        <table class="table table-bordered table-striped attendance-table" id="attendance-table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">ลำดับที่</th>
                    <th rowspan="2" class="align-middle">รหัสนักศึกษา</th>
                    <th rowspan="2" class="align-middle">ชื่อ-สกุล</th>
                    <th colspan="<?php echo count($report_data['week_days']); ?>" class="text-center">สัปดาห์ที่ <?php echo $report_data['week_number']; ?></th>
                    <th rowspan="2" class="align-middle">รวม</th>
                </tr>
                <tr>
                    <?php foreach ($report_data['week_days'] as $day): ?>
                    <th class="text-center">
                        <?php echo $day['day_num']; ?><br>
                        <?php echo $day['day_name']; ?>
                        <?php if ($day['is_holiday']): ?>
                            <br><small class="text-danger">(หยุด)</small>
                        <?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                foreach ($report_data['students'] as $student): 
                    // คำนวณจำนวนวันที่มาเรียน
                    $totalPresent = 0;
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td><?php echo $student['student_code']; ?></td>
                    <td><?php echo $student['display_title'] . $student['first_name'] . ' ' . $student['last_name']; ?></td>
                    <?php foreach ($report_data['week_days'] as $day): ?>
                        <td class="text-center">
                            <?php 
                            if ($day['is_holiday']) {
                                echo '<span class="text-danger">หยุด</span>';
                            } elseif (isset($report_data['attendance_data'][$student['student_id']][$day['date']])) {
                                $attendanceStatus = $report_data['attendance_data'][$student['student_id']][$day['date']];
                                
                                if ($attendanceStatus == 'present') {
                                    echo '<span class="text-success attendance-icon">&#10004;</span>';
                                    $totalPresent++;
                                } elseif ($attendanceStatus == 'absent') {
                                    echo '<span class="text-danger attendance-icon">&#10008;</span>';
                                } elseif ($attendanceStatus == 'late') {
                                    echo '<span class="text-warning attendance-icon">&#8987;</span>';
                                    $totalPresent++; // นับสายเป็นมาเรียน
                                } elseif ($attendanceStatus == 'leave') {
                                    echo '<span class="text-primary attendance-icon">&#9993;</span>';
                                }
                            } else {
                                echo '<span class="text-secondary">-</span>';
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-center"><?php echo $totalPresent; ?></td>
                </tr>
                <?php 
                $no++;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
    
    <div class="report-footer mt-4">
        <div class="row">
            <div class="col-md-4">
                <p>
                    <strong>สรุป</strong> จำนวนคน <?php echo $report_data['total_count']; ?> คน 
                    ชาย <?php echo $report_data['male_count']; ?> คน 
                    หญิง <?php echo $report_data['female_count']; ?> คน
                </p>
            </div>
            <div class="col-md-8">
                <?php
                // คำนวณอัตราการเข้าแถว
                $totalAttendanceRate = 0;
                if ($report_data['total_count'] > 0) {
                    $totalAttendanceData = 0;
                    $totalPossibleAttendance = 0;
                    
                    foreach ($report_data['students'] as $student) {
                        foreach ($report_data['week_days'] as $day) {
                            if (!$day['is_holiday']) {
                                $totalPossibleAttendance++;
                                if (isset($report_data['attendance_data'][$student['student_id']][$day['date']])) {
                                    $status = $report_data['attendance_data'][$student['student_id']][$day['date']];
                                    if ($status == 'present' || $status == 'late') {
                                        $totalAttendanceData++;
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($totalPossibleAttendance > 0) {
                        $totalAttendanceRate = ($totalAttendanceData / $totalPossibleAttendance) * 100;
                    }
                }
                ?>
                <p>
                    <strong>สรุปอัตราการเข้าแถว</strong>: <?php echo number_format($totalAttendanceRate, 2); ?>%
                </p>
            </div>
        </div>
        
        <!-- กราฟแสดงอัตราการเข้าแถวรายวัน -->
        <div class="attendance-chart-container mt-4">
            <h5 class="text-center">กราฟแสดงอัตราการเข้าแถวรายวัน</h5>
            <canvas id="attendanceChart" style="width: 100%; height: 300px;"></canvas>
        </div>
    </div>
</div>

<!-- ฟอร์มสำหรับส่งข้อมูลไปยังหน้าพิมพ์ -->
<form id="print-form" action="print_attendance_report.php" method="post" target="_blank" style="display: none;">
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <input type="hidden" name="start_date" value="<?php echo $report_data['start_date']; ?>">
    <input type="hidden" name="end_date" value="<?php echo $report_data['end_date']; ?>">
    <input type="hidden" name="week_number" value="<?php echo str_replace(['(', ')'], '', $report_data['week_number']); ?>">
    <input type="hidden" name="report_type" value="attendance">
</form>

<form id="chart-form" action="print_attendance_chart.php" method="post" target="_blank" style="display: none;">
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <input type="hidden" name="start_date" value="<?php echo $report_data['start_date']; ?>">
    <input type="hidden" name="end_date" value="<?php echo $report_data['end_date']; ?>">
    <input type="hidden" name="week_number" value="<?php echo str_replace(['(', ')'], '', $report_data['week_number']); ?>">
    <input type="hidden" name="report_type" value="chart">
</form>

<form id="excel-form" action="export_attendance.php" method="post" style="display: none;">
    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
    <input type="hidden" name="start_date" value="<?php echo $report_data['start_date']; ?>">
    <input type="hidden" name="end_date" value="<?php echo $report_data['end_date']; ?>">
    <input type="hidden" name="week_number" value="<?php echo str_replace(['(', ')'], '', $report_data['week_number']); ?>">
    <input type="hidden" name="export_type" value="excel">
</form>

<script>
    // ข้อมูลสำหรับกราฟ
    var chartData = {
        labels: [
            <?php 
            $labels = [];
            foreach ($report_data['week_days'] as $day) {
                if (!$day['is_holiday']) {
                    $labels[] = "'" . $day['day_name'] . " " . $day['day_num'] . "'";
                }
            }
            echo implode(', ', $labels);
            ?>
        ],
        datasets: [{
            label: 'อัตราการเข้าแถว (%)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
            data: [
                <?php 
                $data = [];
                foreach ($report_data['week_days'] as $day) {
                    if (!$day['is_holiday']) {
                        $presentCount = 0;
                        $totalStudents = count($report_data['students']);
                        
                        foreach ($report_data['students'] as $student) {
                            if (isset($report_data['attendance_data'][$student['student_id']][$day['date']])) {
                                $status = $report_data['attendance_data'][$student['student_id']][$day['date']];
                                if ($status == 'present' || $status == 'late') {
                                    $presentCount++;
                                }
                            }
                        }
                        
                        $rate = ($totalStudents > 0) ? ($presentCount / $totalStudents) * 100 : 0;
                        $data[] = number_format($rate, 1);
                    }
                }
                echo implode(', ', $data);
                ?>
            ]
        }]
    };

    // ฟังก์ชันสำหรับแสดงกราฟเมื่อโหลดเสร็จ
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'เปอร์เซ็นต์การเข้าแถว (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'วันที่'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>

<?php else: ?>
<div class="alert alert-info">
    <p>กรุณาเลือกห้องเรียนและช่วงสัปดาห์เพื่อแสดงรายงานการเข้าแถว</p>
</div>
<?php endif; ?>

<style>
    .report-preview {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .attendance-table th, .attendance-table td {
        text-align: center;
        vertical-align: middle;
    }
    
    .attendance-icon {
        font-size: 18px;
        font-weight: bold;
    }
    
    /* ไอคอนตัวทดแทน */
    .attendance-success:before {
        content: "\2714"; /* ✔ */
        color: #4caf50;
    }
    
    .attendance-danger:before {
        content: "\2718"; /* ✘ */
        color: #f44336;
    }
    
    .attendance-warning:before {
        content: "\231B"; /* ⌛ */
        color: #ff9800;
    }
    
    .attendance-primary:before {
        content: "\2709"; /* ✉ */
        color: #2196f3;
    }
    
    .select2-container {
        width: 100% !important;
    }
</style>

<script>
    function printAttendanceReport() {
        document.getElementById('print-form').submit();
    }
    
    function printAttendanceChart() {
        document.getElementById('chart-form').submit();
    }
    
    function downloadExcel() {
        document.getElementById('excel-form').submit();
    }
    
    // ทำให้ select2 ทำงาน
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        
        // เมื่อเลือกแผนกวิชา ให้โหลดข้อมูลห้องเรียนใหม่
        $('#department_id').change(function() {
            const departmentId = $(this).val();
            if (departmentId) {
                // โหลดข้อมูลห้องเรียนตามแผนกวิชา
                $.ajax({
                    url: 'ajax/get_classes_by_department.php',
                    type: 'GET',
                    data: {
                        department_id: departmentId
                    },
                    dataType: 'json',
                    success: function(data) {
                        let options = '<option value="">-- เลือกห้องเรียน --</option>';
                        data.forEach(function(cls) {
                            options += `<option value="${cls.class_id}">${cls.class_name}</option>`;
                        });
                        $('#class_id').html(options).trigger('change');
                    }
                });
            }
        });
        
        // DataTable สำหรับตารางการเข้าแถว
        $('#attendance-table').DataTable({
            responsive: true,
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
                "zeroRecords": "ไม่พบข้อมูล",
                "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจาก _MAX_ รายการทั้งหมด)",
                "search": "ค้นหา:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "pageLength": 25
        });
        
        // เลือกสัปดาห์สิ้นสุดให้ไม่น้อยกว่าสัปดาห์เริ่มต้น
        $('#start_week').change(function() {
            const startWeek = parseInt($(this).val());
            const endWeek = parseInt($('#end_week').val());
            
            if (endWeek < startWeek) {
                $('#end_week').val(startWeek).trigger('change');
            }
        });
    });
</script>