<?php
/**
 * attendance_report_generator.php - สร้างรายงานและกราฟการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เชื่อมต่อฐานข้อมูล
if (!function_exists('getDB')) {
    require_once '../db_connect.php';
}

/**
 * ฟังก์ชันดึงข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $limit จำนวนรายการที่ต้องการ
 * @param int $offset ตำแหน่งเริ่มต้น
 * @param array $filters เงื่อนไขการกรอง (ชั้นเรียน, ห้อง, สถานะ)
 * @return array ข้อมูลนักเรียน
 */
function getAtRiskStudents($conn, $limit = 10, $offset = 0, $filters = []) {
    $params = [];
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    
    // เพิ่มเงื่อนไขการกรอง
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        if ($filters['risk_status'] == 'เสี่ยงตกกิจกรรม') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['risk_status'] == 'ต้องระวัง') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['risk_status'] == 'ปกติ') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 80";
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        if ($filters['attendance_rate'] == 'น้อยกว่า 70%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['attendance_rate'] == '70% - 80%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['attendance_rate'] == '80% - 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 80 AND 90";
        } elseif ($filters['attendance_rate'] == 'มากกว่า 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 90";
        }
    }
    
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%" . $filters['student_name'] . "%";
        $params[] = "%" . $filters['student_name'] . "%";
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
    $query = "
        SELECT 
            s.student_id,
            s.student_code,
            s.title,
            s.current_class_id,
            u.first_name,
            u.last_name,
            c.level,
            c.group_number,
            (SELECT GROUP_CONCAT(CONCAT(pu.first_name, ' ', pu.last_name, ' (', p.relationship, ')'))
             FROM parent_student_relation psr
             JOIN parents p ON psr.parent_id = p.parent_id
             JOIN users pu ON p.user_id = pu.user_id
             WHERE psr.student_id = s.student_id) as parents_info,
            sar.total_attendance_days,
            sar.total_absence_days,
            (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 as attendance_rate,
            (SELECT LINE_id FROM users WHERE user_id IN 
                (SELECT user_id FROM parents WHERE parent_id IN 
                    (SELECT parent_id FROM parent_student_relation WHERE student_id = s.student_id)
                )
            ) as parent_line_id
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
        ORDER BY 
            attendance_rate ASC
        LIMIT ?, ?
    ";
    
    $params[] = (int)$offset;
    $params[] = (int)$limit;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // เพิ่มข้อมูลสถานะความเสี่ยง
    foreach ($students as &$student) {
        $rate = $student['attendance_rate'];
        if ($rate < 60) {
            $student['status'] = 'เสี่ยงตกกิจกรรม';
            $student['status_class'] = 'danger';
        } elseif ($rate < 80) {
            $student['status'] = 'ต้องระวัง';
            $student['status_class'] = 'warning';
        } else {
            $student['status'] = 'ปกติ';
            $student['status_class'] = 'success';
        }
        
        // คำนวณจำนวนวันทั้งหมด
        $total_days = $student['total_attendance_days'] + $student['total_absence_days'];
        $student['attendance_days'] = $student['total_attendance_days'] . '/' . $total_days . ' วัน (' . round($rate) . '%)';
        
        // รหัสห้องเรียน
        $student['class'] = $student['level'] . '/' . $student['group_number'];
        
        // ตัวอักษรแรกของชื่อ
        $student['initial'] = mb_substr($student['first_name'], 0, 1, 'UTF-8');
        
        // ลำดับในห้อง (สมมติค่า)
        $student['class_number'] = rand(1, 30);
    }
    
    return $students;
}

/**
 * ฟังก์ชันนับจำนวนนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมด
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param array $filters เงื่อนไขการกรอง
 * @return int จำนวนนักเรียนทั้งหมด
 */
function countAtRiskStudents($conn, $filters = []) {
    $params = [];
    $where_clauses = ["s.status = 'กำลังศึกษา'", "ay.is_active = 1"];
    
    // เพิ่มเงื่อนไขการกรอง (เหมือนกับฟังก์ชัน getAtRiskStudents)
    if (!empty($filters['class_level'])) {
        $where_clauses[] = "c.level = ?";
        $params[] = $filters['class_level'];
    }
    
    if (!empty($filters['class_group'])) {
        $where_clauses[] = "c.group_number = ?";
        $params[] = $filters['class_group'];
    }
    
    if (!empty($filters['risk_status'])) {
        if ($filters['risk_status'] == 'เสี่ยงตกกิจกรรม') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['risk_status'] == 'ต้องระวัง') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['risk_status'] == 'ปกติ') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 80";
        }
    }
    
    if (!empty($filters['attendance_rate'])) {
        if ($filters['attendance_rate'] == 'น้อยกว่า 70%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 < 70";
        } elseif ($filters['attendance_rate'] == '70% - 80%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 70 AND 80";
        } elseif ($filters['attendance_rate'] == '80% - 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 BETWEEN 80 AND 90";
        } elseif ($filters['attendance_rate'] == 'มากกว่า 90%') {
            $where_clauses[] = "(sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days)) * 100 > 90";
        }
    }
    
    if (!empty($filters['student_name'])) {
        $where_clauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ?)";
        $params[] = "%" . $filters['student_name'] . "%";
        $params[] = "%" . $filters['student_name'] . "%";
    }
    
    $where_clause = implode(" AND ", $where_clauses);
    
    $query = "
        SELECT 
            COUNT(*) as total
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
            JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = c.academic_year_id
            JOIN academic_years ay ON sar.academic_year_id = ay.academic_year_id
        WHERE 
            $where_clause
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['total'];
}

/**
 * ฟังก์ชันดึงข้อมูลการเข้าแถวรายวันของนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return array ข้อมูลการเข้าแถวรายวัน
 */
function getStudentDailyAttendance($conn, $student_id, $start_date = null, $end_date = null) {
    // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
    if (empty($start_date) || empty($end_date)) {
        $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $start_date = $academic_year['start_date'];
        $end_date = $academic_year['end_date'];
    }
    
    // ดึงข้อมูลการเข้าแถว
    $stmt = $conn->prepare("
        SELECT 
            a.date,
            a.attendance_status,
            a.check_method,
            a.check_time,
            a.location_lat,
            a.location_lng,
            a.photo_url
        FROM 
            attendance a
            JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
        WHERE 
            a.student_id = ? 
            AND a.date BETWEEN ? AND ?
            AND ay.is_active = 1
        ORDER BY 
            a.date ASC
    ");
    
    $stmt->execute([$student_id, $start_date, $end_date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ฟังก์ชันสร้างข้อมูลสำหรับกราฟการเข้าแถวรายสัปดาห์
 * 
 * @param array $daily_attendance ข้อมูลการเข้าแถวรายวัน
 * @return array ข้อมูลสำหรับกราฟ
 */
function generateWeeklyAttendanceData($daily_attendance) {
    // จัดกลุ่มข้อมูลตามสัปดาห์
    $weekly_data = [];
    $current_week = '';
    $present_count = 0;
    $total_count = 0;
    
    foreach ($daily_attendance as $record) {
        $date = new DateTime($record['date']);
        $week_number = $date->format('W');
        $year = $date->format('Y');
        $week_key = $year . '-W' . $week_number;
        
        if ($current_week != $week_key) {
            // เริ่มสัปดาห์ใหม่
            if ($current_week != '') {
                // บันทึกข้อมูลสัปดาห์เก่า
                $weekly_data[] = [
                    'week' => $current_week,
                    'present' => $present_count,
                    'total' => $total_count,
                    'rate' => ($total_count > 0) ? ($present_count / $total_count) * 100 : 0
                ];
            }
            
            // เริ่มนับใหม่
            $current_week = $week_key;
            $present_count = ($record['attendance_status'] == 'present') ? 1 : 0;
            $total_count = 1;
        } else {
            // สัปดาห์เดิม
            if ($record['attendance_status'] == 'present') {
                $present_count++;
            }
            $total_count++;
        }
    }
    
    // บันทึกสัปดาห์สุดท้าย
    if ($current_week != '') {
        $weekly_data[] = [
            'week' => $current_week,
            'present' => $present_count,
            'total' => $total_count,
            'rate' => ($total_count > 0) ? ($present_count / $total_count) * 100 : 0
        ];
    }
    
    return $weekly_data;
}

/**
 * ฟังก์ชันสร้างข้อมูลสำหรับกราฟการเข้าแถวรายเดือน
 * 
 * @param array $daily_attendance ข้อมูลการเข้าแถวรายวัน
 * @return array ข้อมูลสำหรับกราฟ
 */
function generateMonthlyAttendanceData($daily_attendance) {
    // จัดกลุ่มข้อมูลตามเดือน
    $monthly_data = [];
    $current_month = '';
    $present_count = 0;
    $total_count = 0;
    
    foreach ($daily_attendance as $record) {
        $date = new DateTime($record['date']);
        $month = $date->format('Y-m');
        
        if ($current_month != $month) {
            // เริ่มเดือนใหม่
            if ($current_month != '') {
                // บันทึกข้อมูลเดือนเก่า
                $monthly_data[] = [
                    'month' => $current_month,
                    'present' => $present_count,
                    'total' => $total_count,
                    'rate' => ($total_count > 0) ? ($present_count / $total_count) * 100 : 0
                ];
            }
            
            // เริ่มนับใหม่
            $current_month = $month;
            $present_count = ($record['attendance_status'] == 'present') ? 1 : 0;
            $total_count = 1;
        } else {
            // เดือนเดิม
            if ($record['attendance_status'] == 'present') {
                $present_count++;
            }
            $total_count++;
        }
    }
    
    // บันทึกเดือนสุดท้าย
    if ($current_month != '') {
        $monthly_data[] = [
            'month' => $current_month,
            'present' => $present_count,
            'total' => $total_count,
            'rate' => ($total_count > 0) ? ($present_count / $total_count) * 100 : 0
        ];
    }
    
    return $monthly_data;
}

/**
 * ฟังก์ชันสร้าง URL สำหรับกราฟแนวโน้มการเข้าแถวด้วย QuickChart API
 * 
 * @param array $attendance_data ข้อมูลการเข้าแถว
 * @param string $student_name ชื่อนักเรียน
 * @return string URL ของกราฟ
 */
function generateAttendanceChart($student_id, $start_date = null, $end_date = null) {
    $conn = getDB();
    
    // ดึงข้อมูลการเข้าแถวรายวัน
    $daily_attendance = getStudentDailyAttendance($conn, $student_id, $start_date, $end_date);
    
    // สร้างข้อมูลรายเดือน
    $monthly_data = generateMonthlyAttendanceData($daily_attendance);
    
    // เตรียมข้อมูลสำหรับกราฟ
    $labels = [];
    $rates = [];
    
    foreach ($monthly_data as $month) {
        $date = new DateTime($month['month'] . '-01');
        $labels[] = $date->format('M Y'); // เช่น "Jan 2023"
        $rates[] = round($month['rate'], 1);
    }
    
    // ถ้าไม่มีข้อมูล ใส่ข้อมูลตัวอย่าง
    if (empty($labels)) {
        $current_date = new DateTime();
        for ($i = 2; $i >= 0; $i--) {
            $month_date = clone $current_date;
            $month_date->modify("-$i month");
            $labels[] = $month_date->format('M Y');
            $rates[] = rand(60, 95);
        }
    }
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT 
            s.title, u.first_name, u.last_name,
            c.level, c.group_number
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
        WHERE 
            s.student_id = ?
    ");
    
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // สร้างชื่อนักเรียนเต็ม
    $student_name = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
    $class_info = $student['level'] . '/' . $student['group_number'];
    
    // กำหนดข้อมูลกราฟ
    $chart_data = [
        'type' => 'line',
        'data' => [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'อัตราการเข้าแถว (%)',
                    'data' => $rates,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ],
        'options' => [
            'title' => [
                'display' => true,
                'text' => [
                    'การเข้าแถวของ ' . $student_name,
                    'ชั้น ' . $class_info
                ],
                'fontSize' => 16
            ],
            'scales' => [
                'yAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true,
                            'max' => 100
                        ],
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'อัตราการเข้าแถว (%)'
                        ]
                    ]
                ],
                'xAxes' => [
                    [
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => 'เดือน'
                        ]
                    ]
                ]
            ]
        ]
    ];
    
    // ในกรณีจริง ควรใช้ QuickChart API จริง
    // $quickchart_url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chart_data));
    
    // สำหรับตัวอย่าง ใช้ URL สมมติ
    $chart_url = 'https://chart.example.com/attendance_' . $student_id . '_' . time() . '.png';
    
    return $chart_url;
}

/**
 * ฟังก์ชันสร้าง URL สำหรับดูรายละเอียดการเข้าแถว
 * 
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string URL สำหรับดูรายละเอียด
 */
function generateDetailUrl($student_id, $start_date = null, $end_date = null) {
    $base_url = 'https://student-prasat.example.com/parents/attendance_detail.php';
    $url = $base_url . '?student_id=' . $student_id;
    
    if (!empty($start_date) && !empty($end_date)) {
        $url .= '&start_date=' . $start_date . '&end_date=' . $end_date;
    }
    
    return $url;
}

/**
 * ฟังก์ชันสร้างรายงาน CSV ของการเข้าแถว
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string เนื้อหาไฟล์ CSV
 */
function generateAttendanceCSV($conn, $student_id, $start_date = null, $end_date = null) {
    // ดึงข้อมูลการเข้าแถวรายวัน
    $daily_attendance = getStudentDailyAttendance($conn, $student_id, $start_date, $end_date);
    
    // ดึงข้อมูลนักเรียน
    $stmt = $conn->prepare("
        SELECT 
            s.student_code, s.title, u.first_name, u.last_name,
            c.level, c.group_number
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
            JOIN classes c ON s.current_class_id = c.class_id
        WHERE 
            s.student_id = ?
    ");
    
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // สร้างเนื้อหา CSV
    $csv_content = "รหัสนักศึกษา,ชื่อ-นามสกุล,ชั้น/กลุ่ม,วันที่,สถานะ,วิธีเช็คชื่อ,เวลา\n";
    
    $student_name = $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'];
    $class_info = $student['level'] . '/' . $student['group_number'];
    
    // แปลสถานะภาษาอังกฤษเป็นไทย
    $status_mapping = [
        'present' => 'มา',
        'absent' => 'ขาด',
        'late' => 'สาย',
        'leave' => 'ลา'
    ];
    
    // แปลวิธีเช็คชื่อภาษาอังกฤษเป็นไทย
    $method_mapping = [
        'GPS' => 'GPS',
        'QR_Code' => 'QR Code',
        'PIN' => 'รหัส PIN',
        'Manual' => 'ครูเช็ค'
    ];
    
    foreach ($daily_attendance as $record) {
        $status = $status_mapping[$record['attendance_status']] ?? $record['attendance_status'];
        $method = $method_mapping[$record['check_method']] ?? $record['check_method'];
        
        $csv_content .= "\"{$student['student_code']}\",\"{$student_name}\",\"{$class_info}\",\"{$record['date']}\",\"{$status}\",\"{$method}\",\"{$record['check_time']}\"\n";
    }
    
    return $csv_content;
}

/**
 * ฟังก์ชันสร้างรายงาน PDF ของการเข้าแถว
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $student_id รหัสนักเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return string ชื่อไฟล์ PDF ที่สร้าง
 */
function generateAttendancePDF($conn, $student_id, $start_date = null, $end_date = null) {
    // ในทางปฏิบัติจริง ควรใช้ TCPDF, FPDF หรือ library อื่นๆ
    // สำหรับตัวอย่าง แค่จำลองไว้
    $filename = 'attendance_' . $student_id . '_' . time() . '.pdf';
    
    return $filename;
}

/**
 * ฟังก์ชันสร้างรายงานการเข้าแถวสำหรับกลุ่มนักเรียน
 * 
 * @param PDO $conn เชื่อมต่อฐานข้อมูล
 * @param int $class_id รหัสชั้นเรียน
 * @param string $start_date วันที่เริ่มต้น
 * @param string $end_date วันที่สิ้นสุด
 * @return array ข้อมูลรายงาน
 */
function generateClassAttendanceReport($conn, $class_id, $start_date = null, $end_date = null) {
    // ถ้าไม่ระบุช่วงเวลา ใช้ข้อมูลทั้งภาคเรียนปัจจุบัน
    if (empty($start_date) || empty($end_date)) {
        $stmt = $conn->query("SELECT start_date, end_date FROM academic_years WHERE is_active = 1");
        $academic_year = $stmt->fetch(PDO::FETCH_ASSOC);
        $start_date = $academic_year['start_date'];
        $end_date = $academic_year['end_date'];
    }
    
    // ดึงข้อมูลนักเรียนในชั้นเรียน
    $stmt = $conn->prepare("
        SELECT 
            s.student_id, s.student_code, s.title, u.first_name, u.last_name
        FROM 
            students s
            JOIN users u ON s.user_id = u.user_id
        WHERE 
            s.current_class_id = ? AND s.status = 'กำลังศึกษา'
        ORDER BY
            u.first_name ASC
    ");
    
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงข้อมูลชั้นเรียน
    $stmt = $conn->prepare("
        SELECT 
            c.level, c.group_number, d.department_name,
            ay.year, ay.semester
        FROM 
            classes c
            JOIN departments d ON c.department_id = d.department_id
            JOIN academic_years ay ON c.academic_year_id = ay.academic_year_id
        WHERE 
            c.class_id = ?
    ");
    
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // สร้างข้อมูลรายงาน
    $report = [
        'class' => $class,
        'date_range' => [
            'start_date' => $start_date,
            'end_date' => $end_date
        ],
        'students' => []
    ];
    
    // ดึงข้อมูลการเข้าแถวของแต่ละคน
    foreach ($students as $student) {
        // ดึงข้อมูลการเข้าแถวรายวัน
        $stmt = $conn->prepare("
            SELECT 
                a.date,
                a.attendance_status
            FROM 
                attendance a
                JOIN academic_years ay ON a.academic_year_id = ay.academic_year_id
            WHERE 
                a.student_id = ? 
                AND a.date BETWEEN ? AND ?
                AND ay.is_active = 1
            ORDER BY 
                a.date ASC
        ");
        
        $stmt->execute([$student['student_id'], $start_date, $end_date]);
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // นับจำนวนวันตามสถานะ
        $present_count = 0;
        $absent_count = 0;
        $late_count = 0;
        $leave_count = 0;
        
        foreach ($attendance as $record) {
            switch ($record['attendance_status']) {
                case 'present':
                    $present_count++;
                    break;
                case 'absent':
                    $absent_count++;
                    break;
                case 'late':
                    $late_count++;
                    break;
                case 'leave':
                    $leave_count++;
                    break;
            }
        }
        
        $total_days = $present_count + $absent_count + $late_count + $leave_count;
        $attendance_rate = ($total_days > 0) ? round(($present_count / $total_days) * 100, 2) : 0;
        
        // กำหนดสถานะการเข้าแถว
        $status = 'ปกติ';
        $status_class = 'success';
        if ($attendance_rate < 60) {
            $status = 'เสี่ยงตกกิจกรรม';
            $status_class = 'danger';
        } elseif ($attendance_rate < 80) {
            $status = 'ต้องระวัง';
            $status_class = 'warning';
        }
        
        // เพิ่มข้อมูลนักเรียนในรายงาน
        $report['students'][] = [
            'student_id' => $student['student_id'],
            'student_code' => $student['student_code'],
            'student_name' => $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'],
            'attendance' => [
                'present' => $present_count,
                'absent' => $absent_count,
                'late' => $late_count,
                'leave' => $leave_count,
                'total' => $total_days,
                'rate' => $attendance_rate
            ],
            'status' => $status,
            'status_class' => $status_class
        ];
    }
    
    return $report;
}