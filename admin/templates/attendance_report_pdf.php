<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายงานการเข้าแถว</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 14pt;
        }
        th {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .name-column {
            text-align: left;
            padding-left: 10px;
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
        .holiday {
            background-color: #f0f0f0;
            color: #777;
        }
        .present {
            background-color: #d4edda;
            color: #155724;
        }
        .absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        .late {
            background-color: #fff3cd;
            color: #856404;
        }
        .leave {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .bold {
            font-weight: bold;
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
            <strong>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</strong><br>
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
    
    <table>
        <thead>
            <tr>
                <th rowspan="2" style="width: 40px;">ลำดับ</th>
                <th rowspan="2" style="width: 100px;">รหัสนักศึกษา</th>
                <th rowspan="2" style="width: 200px;">ชื่อ-สกุล</th>
                <th colspan="<?php echo count($week_days); ?>">วันที่</th>
                <th rowspan="2" style="width: 50px;">รวม</th>
            </tr>
            <tr>
                <?php foreach ($week_days as $day): ?>
                <th style="width: 50px;">
                    <?php echo $day['day_name']; ?><br>
                    <?php echo $day['day_num']; ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            // กำหนดนักเรียนที่จะแสดงในหน้านี้
            $displayStudents = isset($pageStudents) ? $pageStudents : $students;
            $startNo = isset($startIndex) ? $startIndex + 1 : 1;
            
            foreach ($displayStudents as $index => $student): 
                // คำนวณจำนวนวันที่มาเรียน
                $totalPresent = 0;
                $no = $startNo + $index;
            ?>
            <tr>
                <td><?php echo $no; ?></td>
                <td><?php echo $student['student_code']; ?></td>
                <td class="name-column"><?php echo $student['display_title'] . $student['first_name'] . ' ' . $student['last_name']; ?></td>
                <?php foreach ($week_days as $day): ?>
                    <?php 
                    $cellClass = "";
                    $cellContent = "-";
                    
                    if ($day['is_holiday']) {
                        $cellClass = "holiday";
                        $cellContent = "หยุด";
                    } elseif (isset($attendance_data[$student['student_id']][$day['date']])) {
                        $attendanceStatus = $attendance_data[$student['student_id']][$day['date']];
                        
                        if ($attendanceStatus == 'present') {
                            $cellClass = "present";
                            $cellContent = "มา";
                            $totalPresent++;
                        } elseif ($attendanceStatus == 'absent') {
                            $cellClass = "absent";
                            $cellContent = "ขาด";
                        } elseif ($attendanceStatus == 'late') {
                            $cellClass = "late";
                            $cellContent = "สาย";
                            $totalPresent++; // นับสายเป็นมาเรียน
                        } elseif ($attendanceStatus == 'leave') {
                            $cellClass = "leave";
                            $cellContent = "ลา";
                        }
                    }
                    ?>
                    <td class="<?php echo $cellClass; ?>"><?php echo $cellContent; ?></td>
                <?php endforeach; ?>
                <td class="bold"><?php echo $totalPresent; ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php 
            // เพิ่มแถวว่างเพื่อความสวยงาม
            $emptyRows = 0;
            if (count($displayStudents) < 25) {
                $emptyRows = 25 - count($displayStudents);
                for ($i = 0; $i < $emptyRows; $i++) {
                    $no = $startNo + count($displayStudents) + $i;
                    echo '<tr>';
                    echo '<td>'.$no.'</td>';
                    echo '<td></td>';
                    echo '<td class="name-column"></td>';
                    
                    foreach ($week_days as $day) {
                        echo '<td></td>';
                    }
                    
                    echo '<td></td>';
                    echo '</tr>';
                }
            }
            ?>
        </tbody>
    </table>
    
    <?php if (!isset($pageStudents) || (isset($currentPage) && $currentPage == $totalPages)): ?>
    <!-- แสดงสรุปเฉพาะในหน้าสุดท้ายของแต่ละสัปดาห์ -->
    <div>
        <strong>สรุป</strong> จำนวนคน <u>&nbsp;&nbsp;<?php echo $total_count; ?>&nbsp;&nbsp;</u> คน 
        ชาย <u>&nbsp;&nbsp;<?php echo $male_count; ?>&nbsp;&nbsp;</u> คน 
        หญิง <u>&nbsp;&nbsp;<?php echo $female_count; ?>&nbsp;&nbsp;</u> คน
    </div>
    
    <?php
    // คำนวณอัตราการเข้าแถว
    $totalAttendanceRate = 0;
    if ($total_count > 0) {
        $totalAttendanceData = 0;
        $totalPossibleAttendance = 0;
        
        foreach ($students as $student) {
            foreach ($week_days as $day) {
                if (!$day['is_holiday']) {
                    $totalPossibleAttendance++;
                    if (isset($attendance_data[$student['student_id']][$day['date']])) {
                        $status = $attendance_data[$student['student_id']][$day['date']];
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
    
    <div style="margin-top: 10px;">
        <strong>สรุปจำนวนนักเรียนเข้าแถวร้อยละ</strong> <u>&nbsp;&nbsp;<?php echo number_format($totalAttendanceRate, 2); ?>&nbsp;&nbsp;</u>
    </div>
    <?php endif; ?>
    
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
        <div class="left">
            หน้าที่ <?php echo isset($currentPage) ? $currentPage : 1; ?>
            <?php if (isset($totalPages) && $totalPages > 1): ?>
            /<?php echo $totalPages; ?>
            <?php endif; ?>
        </div>
        <div class="right">พิมพ์เมื่อวันที่ <?php echo date('j/n/Y'); ?></div>
        <div class="clear"></div>
    </div>
</body>
</html>