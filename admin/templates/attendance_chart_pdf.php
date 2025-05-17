<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>กราฟการเข้าแถว</title>
    <style>
        body {
            font-family: 'thsarabun';
            font-size: 16pt;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .school-logo {
            float: left;
            width: 80px;
            height: 80px;
            border: 1px solid #000;
            border-radius: 50%;
            text-align: center;
            margin-right: 20px;
            padding-top: 20px;
            font-size: 14pt;
        }
        .clear {
            clear: both;
        }
        .chart-container {
            width: 100%;
            height: 400px;
            border: 1px solid #ddd;
            margin: 20px 0;
            padding: 10px;
        }
        .chart-placeholder {
            width: 100%;
            height: 100%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 16pt;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }
        .signature-box {
            float: left;
            width: 33%;
            text-align: center;
        }
        .signature-line {
            width: 80%;
            height: 1px;
            background-color: #000;
            margin: 50px auto 5px;
        }
        .page-footer {
            margin-top: 30px;
            font-size: 14pt;
        }
        .left {
            float: left;
        }
        .right {
            float: right;
        }
        .summary-section {
            margin: 20px 0;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .summary-table th {
            background-color: #f2f2f2;
        }
        .status-container {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
        }
        .status-box {
            text-align: center;
            width: 22%;
        }
        .status-value {
            font-size: 24pt;
            font-weight: bold;
        }
        .status-label {
            font-size: 14pt;
            color: #666;
        }
        .colored-box {
            display: inline-block;
            width: 15px;
            height: 15px;
            margin-right: 5px;
            vertical-align: middle;
        }
        .good {
            background-color: #4caf50;
        }
        .warning {
            background-color: #ff9800;
        }
        .danger {
            background-color: #f44336;
        }
        .info {
            background-color: #2196f3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">โลโก้<br>วิทยาลัย</div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงอัตราการเข้าแถวรายวัน</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?> เดือน <?php echo date('F', strtotime($week_days[0]['date'])); ?> พ.ศ. <?php echo date('Y', strtotime($week_days[0]['date'])) + 543; ?><br>
            ระหว่างวันที่ <?php echo date('j', strtotime($start_date)); ?> เดือน <?php echo date('F', strtotime($start_date)); ?> พ.ศ. <?php echo date('Y', strtotime($start_date)) + 543; ?> ถึง วันที่ <?php echo date('j', strtotime($end_date)); ?> เดือน <?php echo date('F', strtotime($end_date)); ?> พ.ศ. <?php echo date('Y', strtotime($end_date)) + 543; ?><br>
            ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <?php
    // คำนวณสถิติการเข้าแถวรายวัน
    $dailyStats = [];
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLate = 0;
    $totalLeave = 0;
    
    foreach ($week_days as $day) {
        $dayStats = [
            'date' => $day['date'],
            'day_name' => $day['day_name'],
            'is_holiday' => $day['is_holiday'],
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'leave' => 0,
            'attendance_rate' => 0
        ];
        
        if (!$day['is_holiday']) {
            $totalStudentsForDay = count($students);
            $presentCount = 0;
            
            foreach ($students as $student) {
                if (isset($attendance_data[$student['student_id']][$day['date']])) {
                    $status = $attendance_data[$student['student_id']][$day['date']];
                    if ($status == 'present') {
                        $dayStats['present']++;
                        $totalPresent++;
                        $presentCount++;
                    } elseif ($status == 'absent') {
                        $dayStats['absent']++;
                        $totalAbsent++;
                    } elseif ($status == 'late') {
                        $dayStats['late']++;
                        $totalLate++;
                        $presentCount++; // นับสายเป็นมาเรียน
                    } elseif ($status == 'leave') {
                        $dayStats['leave']++;
                        $totalLeave++;
                    }
                } else {
                    $dayStats['absent']++;
                    $totalAbsent++;
                }
            }
            
            // คำนวณอัตราการเข้าแถว
            if ($totalStudentsForDay > 0) {
                $dayStats['attendance_rate'] = ($presentCount / $totalStudentsForDay) * 100;
            }
        }
        
        $dailyStats[] = $dayStats;
    }
    
    // คำนวณอัตราการเข้าแถวรวม
    $totalAttendanceRate = 0;
    $totalDays = count(array_filter($week_days, function($day) {
        return !$day['is_holiday'];
    }));
    
    if ($totalDays > 0 && $total_count > 0) {
        $totalPossibleAttendance = $totalDays * $total_count;
        $totalAttendances = $totalPresent + $totalLate;
        $totalAttendanceRate = ($totalAttendances / $totalPossibleAttendance) * 100;
    }
    ?>
    
    <div class="status-container">
        <div class="status-box">
            <div class="status-value"><?php echo $total_count; ?></div>
            <div class="status-label">จำนวนนักเรียน</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo number_format($totalAttendanceRate, 1); ?>%</div>
            <div class="status-label">อัตราการเข้าแถวเฉลี่ย</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo $totalDays; ?></div>
            <div class="status-label">จำนวนวันเรียน</div>
        </div>
        <div class="status-box">
            <div class="status-value"><?php echo $totalPresent + $totalLate; ?>/<?php echo $totalDays * $total_count; ?></div>
            <div class="status-label">จำนวนครั้งเข้าแถว</div>
        </div>
    </div>
    
    <!-- กราฟแสดงอัตราการเข้าแถวรายวัน -->
    <div class="chart-container">
        <!-- ในไฟล์จริงจะแสดงกราฟ แต่ใน MPDF จะแสดงเป็นตาราง -->
        <div class="chart-placeholder">
            [กราฟแสดงอัตราการเข้าแถวรายวัน]<br>
            <small>* ในไฟล์ PDF จะแสดงเป็นตารางข้อมูลแทนกราฟ</small>
        </div>
    </div>
    
    <!-- ตารางแสดงข้อมูลรายวัน -->
    <div class="summary-section">
        <h3>ข้อมูลการเข้าแถวรายวัน</h3>
        <table class="summary-table">
            <thead>
                <tr>
                    <th>วัน</th>
                    <th>มา</th>
                    <th>ขาด</th>
                    <th>สาย</th>
                    <th>ลา</th>
                    <th>อัตราการเข้าแถว (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dailyStats as $dayStat): ?>
                <tr>
                    <td>
                        <?php echo $dayStat['day_name']; ?> 
                        <?php echo date('d/m/Y', strtotime($dayStat['date'])); ?>
                        <?php if ($dayStat['is_holiday']): ?>
                            <span style="color: red;">(หยุด)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['present']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['absent']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['late']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : $dayStat['leave']; ?></td>
                    <td><?php echo $dayStat['is_holiday'] ? '-' : number_format($dayStat['attendance_rate'], 1) . '%'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>รวม</th>
                    <th><?php echo $totalPresent; ?></th>
                    <th><?php echo $totalAbsent; ?></th>
                    <th><?php echo $totalLate; ?></th>
                    <th><?php echo $totalLeave; ?></th>
                    <th><?php echo number_format($totalAttendanceRate, 1); ?>%</th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- แผนภูมิแสดงสัดส่วนสถานะการเข้าแถว -->
    <div class="summary-section">
        <h3>สัดส่วนสถานะการเข้าแถว</h3>
        <div>
            <span class="colored-box good"></span> มาปกติ: <?php echo number_format(($totalPresent / ($totalPresent + $totalAbsent + $totalLate + $totalLeave)) * 100, 1); ?>%
            &nbsp;&nbsp;
            <span class="colored-box danger"></span> ขาด: <?php echo number_format(($totalAbsent / ($totalPresent + $totalAbsent + $totalLate + $totalLeave)) * 100, 1); ?>%
            &nbsp;&nbsp;
            <span class="colored-box warning"></span> มาสาย: <?php echo number_format(($totalLate / ($totalPresent + $totalAbsent + $totalLate + $totalLeave)) * 100, 1); ?>%
            &nbsp;&nbsp;
            <span class="colored-box info"></span> ลา: <?php echo number_format(($totalLeave / ($totalPresent + $totalAbsent + $totalLate + $totalLeave)) * 100, 1); ?>%
        </div>
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <?php if ($primary_advisor): ?>
            <div>(<?php echo $primary_advisor['title'] . $primary_advisor['first_name'] . ' ' . $primary_advisor['last_name']; ?>)</div>
            <?php else: ?>
            <div>(.......................................)</div>
            <?php endif; ?>
            <div>ครูที่ปรึกษา</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <div>(นายนนทศรี ศรีสุข)</div>
            <div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <div>(นายพงษ์ศักดิ์ สมใจรัก)</div>
            <div>รองผู้อำนวยการ</div>
            <div>ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
        </div>
    </div>
    
    <div class="clear"></div>
    
    <div class="page-footer">
        <div class="left">หน้าที่ 1</div>
        <div class="right">พิมพ์เมื่อวันที่ <?php echo date('j/n/Y'); ?></div>
        <div class="clear"></div>
    </div>
</body>
</html>