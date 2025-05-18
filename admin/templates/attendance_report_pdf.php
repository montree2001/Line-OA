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
            text-align: center;
            margin-right: 20px;
            padding-top: 20px;
            font-size: 14pt;
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
        }
        th {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        .name-column {
            text-align: left;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="school-logo">
            <?php if (file_exists('../uploads/logos/school_logo.png')): ?>
                <img src="../uploads/logos/school_logo.png" alt="Logo" style="width: 100%; height: auto;">
            <?php else: ?>
                โลโก้<br>วิทยาลัย
            <?php endif; ?>
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?><br>
            <?php 
            // แสดงชื่อเดือนภาษาไทย
            $start_month_name = $week_days[0]['month'];
            $end_month_name = $week_days[count($week_days)-1]['month'];
            $same_month = ($start_month_name == $end_month_name);
            ?>
            ระหว่างวันที่ <?php echo (int)date('j', strtotime($start_date)); ?> 
            <?php if ($same_month): ?>
                - <?php echo (int)date('j', strtotime($end_date)); ?> เดือน<?php echo $start_month_name; ?> 
            <?php else: ?>
                เดือน<?php echo $start_month_name; ?> - วันที่ <?php echo (int)date('j', strtotime($end_date)); ?> เดือน<?php echo $end_month_name; ?> 
            <?php endif; ?>
            พ.ศ. <?php echo date('Y', strtotime($start_date)) + 543; ?><br>
            ระดับชั้น <?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?> แผนกวิชา<?php echo $department['department_name']; ?>
        </p>
    </div>
    
    <div class="clear"></div>
    
    <table>
        <thead>
            <tr>
                <th rowspan="2">ลำดับที่</th>
                <th rowspan="2">รหัสนักศึกษา</th>
                <th rowspan="2">ชื่อ-สกุล</th>
                <th colspan="<?php echo count($week_days); ?>">สัปดาห์ที่ <?php echo $week_number; ?></th>
                <th rowspan="2">รวม</th>
            </tr>
            <tr>
                <?php foreach ($week_days as $day): ?>
                <th>
                    <?php echo $day['day_num']; ?><br>
                    <?php echo $day['day_name']; ?>
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
                    <td>
                        <?php 
                        if ($day['is_holiday']) {
                            echo 'หยุด';
                        } elseif (isset($attendance_data[$student['student_id']][$day['date']])) {
                            $attendanceStatus = $attendance_data[$student['student_id']][$day['date']];
                            
                            if ($attendanceStatus == 'present') {
                                echo 'มา';
                                $totalPresent++;
                            } elseif ($attendanceStatus == 'absent') {
                                echo 'ขาด';
                            } elseif ($attendanceStatus == 'late') {
                                echo 'สาย';
                                $totalPresent++; // นับสายเป็นมาเรียน
                            } elseif ($attendanceStatus == 'leave') {
                                echo 'ลา';
                            }
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                <td><?php echo $totalPresent; ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php 
            // เพิ่มแถวว่างถ้ามีนักเรียนน้อยกว่า 5 คน
            if (count($displayStudents) < 5) {
                $emptyRows = 5 - count($displayStudents);
                for ($i = 0; $i < $emptyRows; $i++) {
                    $no = $startNo + count($displayStudents) + $i;
                    echo '<tr>';
                    echo '<td>' . $no . '</td>';
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
        <strong>สรุป</strong> จำนวนคน........... <?php echo $total_count; ?> ...........ชาย............. <?php echo $male_count; ?> .............หญิง............. <?php echo $female_count; ?> ..............
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
            $totalAttendanceRate = ($totalAttendanceData / ($totalPossibleAttendance * $total_count)) * 100;
        }
    }
    ?>
    
    <div>
        <strong>สรุปจำนวนนักเรียนเข้าแถวร้อยละ</strong>........... <?php echo number_format($totalAttendanceRate, 2); ?> ...........
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