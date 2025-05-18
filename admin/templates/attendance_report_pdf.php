<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>รายงานการเข้าแถว</title>
    <!-- icon ติกถูก -->
  

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
            <img src="../uploads/logos/school_logo_1747545769.png" alt="Logo" style="width: 100%; height: auto;">
        </div>
        <p>
            <strong>งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท</strong><br>
            <strong>แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง</strong><br>
            ภาคเรียนที่ <?php echo $academic_year['semester']; ?> ปีการศึกษา <?php echo $academic_year['year']; ?> สัปดาห์ที่ <?php echo $week_number; ?> เดือน <?php echo date('F', strtotime($week_days[0]['date'])); ?> พ.ศ. <?php echo date('Y', strtotime($week_days[0]['date'])) + 543; ?><br>
            ระหว่างวันที่ <?php echo date('j', strtotime($start_date)); ?> เดือน <?php echo date('F', strtotime($start_date)); ?> พ.ศ. <?php echo date('Y', strtotime($start_date)) + 543; ?> ถึง วันที่ <?php echo date('j', strtotime($end_date)); ?> เดือน <?php echo date('F', strtotime($end_date)); ?> พ.ศ. <?php echo date('Y', strtotime($end_date)) + 543; ?><br>
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
            $no = 1;
            foreach ($students as $student): 
                // คำนวณจำนวนวันที่มาเรียน
                $totalPresent = 0;
            ?>
            <tr>
                <td><?php echo $no; ?></td>
                <td><?php echo $student['student_code']; ?></td>
                <td class="name-column"><?php echo $student['title'] . $student['first_name'] . ' ' . $student['last_name']; ?></td>
                <?php foreach ($week_days as $day): ?>
                    <td>
                        <?php 
                        $status = '';
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
            <?php 
            $no++;
            endforeach; 
            
            // เพิ่มแถวว่างถ้ามีนักเรียนน้อย
            if (count($students) < 5) {
                $emptyRows = 5 - count($students);
                for ($i = 0; $i < $emptyRows; $i++) {
                    echo '<tr>';
                    echo '<td>' . $no . '</td>';
                    echo '<td></td>';
                    echo '<td class="name-column"></td>';
                    
                    foreach ($week_days as $day) {
                        echo '<td></td>';
                    }
                    
                    echo '<td></td>';
                    echo '</tr>';
                    $no++;
                }
            }
            ?>
        </tbody>
    </table>
    
    <div>
        สรุป จำนวนคน........... <?php echo $total_count; ?> ...........ชาย............. <?php echo $male_count; ?> .............หญิง............. <?php echo $female_count; ?> ..............
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
    
    <div>
        สรุปจำนวนนักเรียนเข้าแถวร้อยละ........... <?php echo number_format($totalAttendanceRate, 2); ?> ...........
    </div>
    
    <div class="signature-section">
        <div class="signature-box">
            
            <div>ลงชื่อ...........................................</div>
            <?php if ($primary_advisor): ?>
            <div>(<?php echo $primary_advisor['title'] . $primary_advisor['first_name'] . ' ' . $primary_advisor['last_name']; ?>)</div>
            <?php else: ?>
            <div>(.......................................)</div>
            <?php endif; ?>
            <div>ครูที่ปรึกษา</div>
        </div>
        
        <div class="signature-box">
            
            <div>ลงชื่อ...........................................</div>
            <div>(นายมนตรี ศรีสุข)</div>
            <div>หัวหน้างานกิจกรรมนักเรียน นักศึกษา</div>
        </div>
        
        <div class="signature-box">
           
            <div>ลงชื่อ...........................................</div>
            <div>(นายพงษ์ศักดิ์ สนโศรก)</div>
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