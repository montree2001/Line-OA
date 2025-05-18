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
            margin-right: 20px;
            text-align: center;
        }
        .school-logo img {
            width: 100%;
            height: auto;
            border-radius: 10px;
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
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .chart-placeholder {
            width: 100%;
            height: 100%;
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
        
        /* สร้างกราฟแท่ง */
        .bar-chart {
            width: 100%;
            height: 300px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            margin-top: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ccc;
        }
        .bar-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: <?php echo 100 / count($dailyStats); ?>%;
            max-width: 80px;
        }
        .bar {
            width: 40px;
            background-color: #4caf50;
            margin-bottom: 10px;
            border-radius: 5px 5px 0 0;
        }
        .bar-label {
            text-align: center;
            font-size: 12pt;
        }
        .bar-value {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
        }
        
        /* สร้างแผนภูมิวงกลม */
        .pie-container {
            width: 100%;
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        .pie-legend {
            width: 100%;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .legend-item {
            margin: 0 15px;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <?php if (file_exists('../uploads/logos/school_logo.png')): ?>
                <img src="../uploads/logos/school_logo.png" alt="Logo">
            <?php else: ?>
                <div style="width: 100%; height: 100%; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center;">โลโก้</div>
            <?php endif; ?>
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>กราฟแสดงอัตราการเข้าแถวรายวัน</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?><br>
            <?php 
            // แสดงชื่อเดือนภาษาไทย
            $first_day = $week_days[0];
            $last_day = $week_days[count($week_days)-1];
            
            $start_month_name = $first_day['month'];
            $end_month_name = $last_day['month'];
            $same_month = ($start_month_name == $end_month_name);
            
            echo "ระหว่างวันที่ ".$first_day['day_num']." ";
            if ($same_month) {
                echo "- ".$last_day['day_num']." เดือน".$start_month_name." ";
            } else {
                echo "เดือน".$start_month_name." - วันที่ ".$last_day['day_num']." เดือน".$end_month_name." ";
            }
            echo "พ.ศ. ".(date('Y', strtotime($start_date)) + 543);
            ?>
            <br>
            ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <!-- สถิติการเข้าแถว -->
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
        <h3 style="text-align: center; margin-top: 0;">กราฟแสดงอัตราการเข้าแถวรายวัน</h3>
        <div class="bar-chart">
            <?php foreach ($dailyStats as $dayStat): ?>
                <?php if (!$dayStat['is_holiday']): ?>
                    <?php
                    $height = isset($dayStat['attendance_rate']) ? round($dayStat['attendance_rate']) : 0;
                    $barHeight = ($height / 100) * 250; // Scale to max height of 250px
                    
                    // Set color based on attendance rate
                    if ($height >= 90) {
                        $color = '#4caf50'; // Green
                    } elseif ($height >= 80) {
                        $color = '#ff9800'; // Orange
                    } else {
                        $color = '#f44336'; // Red
                    }
                    ?>
                    <div class="bar-container">
                        <div class="bar-value"><?php echo number_format($dayStat['attendance_rate'], 1); ?>%</div>
                        <div class="bar" style="height: <?php echo $barHeight; ?>px; background-color: <?php echo $color; ?>;"></div>
                        <div class="bar-label">
                            <?php echo $dayStat['day_name']; ?> <?php echo $dayStat['day_num']; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bar-container">
                        <div class="bar-value">หยุด</div>
                        <div class="bar" style="height: 0; background-color: #ccc;"></div>
                        <div class="bar-label">
                            <?php echo $dayStat['day_name']; ?> <?php echo $dayStat['day_num']; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
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
        
        <div class="pie-legend">
            <?php
            $totalAttendanceCount = $totalPresent + $totalAbsent + $totalLate + $totalLeave;
            if ($totalAttendanceCount > 0):
                $presentPercent = ($totalPresent / $totalAttendanceCount) * 100;
                $absentPercent = ($totalAbsent / $totalAttendanceCount) * 100;
                $latePercent = ($totalLate / $totalAttendanceCount) * 100;
                $leavePercent = ($totalLeave / $totalAttendanceCount) * 100;
            ?>
            <div class="legend-item">
                <span class="colored-box good"></span> มาปกติ: <?php echo number_format($presentPercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box danger"></span> ขาด: <?php echo number_format($absentPercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box warning"></span> มาสาย: <?php echo number_format($latePercent, 1); ?>%
            </div>
            <div class="legend-item">
                <span class="colored-box info"></span> ลา: <?php echo number_format($leavePercent, 1); ?>%
            </div>
            <?php endif; ?>
        </div>
        
        <!-- แสดงแผนภาพวงกลมแบบ ASCII art -->
        <div class="pie-container">
            <div style="text-align: center; width: 60%; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; background-color: #f9f9f9;">
                <div style="font-weight: bold; margin-bottom: 10px;">การแสดงข้อมูลสัดส่วนการเข้าแถว</div>
                <div style="display: flex; justify-content: space-around; margin-top: 10px;">
                    <div style="background-color: #4caf50; height: 20px; width: <?php echo $presentPercent; ?>%; min-width: 10px;"></div>
                    <div style="background-color: #f44336; height: 20px; width: <?php echo $absentPercent; ?>%; min-width: 10px;"></div>
                    <div style="background-color: #ff9800; height: 20px; width: <?php echo $latePercent; ?>%; min-width: 10px;"></div>
                    <div style="background-color: #2196f3; height: 20px; width: <?php echo $leavePercent; ?>%; min-width: 10px;"></div>
                </div>
            </div>
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
            <?php if (isset($signers[0])): ?>
            <div>(<?php echo $signers[0]['title'] . $signers[0]['first_name'] . ' ' . $signers[0]['last_name']; ?>)</div>
            <div><?php echo $signers[0]['position']; ?></div>
            <?php else: ?>
            <div>(นายมนตรี ศรีสุข)</div>
            <div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
            <?php endif; ?>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div>ลงชื่อ...........................................</div>
            <?php if (isset($signers[1])): ?>
            <div>(<?php echo $signers[1]['title'] . $signers[1]['first_name'] . ' ' . $signers[1]['last_name']; ?>)</div>
            <div><?php echo $signers[1]['position']; ?></div>
            <?php else: ?>
            <div>(นายพงษ์ศักดิ์ สนโศรก)</div>
            <div>รองผู้อำนวยการ</div>
            <div>ฝ่ายพัฒนากิจการนักเรียนนักศึกษา</div>
            <?php endif; ?>
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