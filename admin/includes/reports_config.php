<?php
/**
 * config.php - การกำหนดค่าสำหรับรายงานแบบละเอียด
 * 
 * ส่วนหนึ่งของระบบน้องสัตบรรณ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// กำหนดเส้นทางไฟล์
$config = [
    // เส้นทางหลัก
    'base_path' => dirname(__FILE__),
    
    // เส้นทางไฟล์รายงาน
    'reports' => [
        'standard' => [
            'php'     => 'reports.php',
            'js'      => 'assets/js/reports.js',
            'css'     => 'assets/css/reports.css',
            'content' => 'pages/reports_content.php'
        ],
        'enhanced' => [
            'php'     => 'enhanced_reports.php',
            'js'      => 'assets/js/enhanced_reports.js',
            'css'     => 'assets/css/enhanced_reports.css',
            'content' => 'pages/enhanced_reports_content.php'
        ]
    ],
    
    // การตั้งค่าการแสดงผล
    'display' => [
        'charts_per_page' => 2,
        'risk_students_limit' => 5,
        'class_ranking_limit' => 10,
        'default_period' => 'month',
        'enable_print' => true,
        'enable_export' => true,
        'enable_notifications' => true
    ],
    
    // คำอธิบายระดับความเสี่ยง
    'risk_levels' => [
        'normal' => [
            'min' => 80,
            'max' => 100,
            'description' => 'ปกติ',
            'color' => '#4caf50',
            'class' => 'success'
        ],
        'risk' => [
            'min' => 70,
            'max' => 79.99,
            'description' => 'เสี่ยงตกกิจกรรม',
            'color' => '#ffc107',
            'class' => 'warning'
        ],
        'failed' => [
            'min' => 0,
            'max' => 69.99,
            'description' => 'ตกกิจกรรม',
            'color' => '#dc3545',
            'class' => 'danger'
        ]
    ],
    
    // เงื่อนไขการออกรายงาน
    'report_options' => [
        'formats' => ['pdf', 'excel', 'csv'],
        'periods' => [
            'day' => 'วันนี้',
            'yesterday' => 'เมื่อวาน',
            'week' => 'สัปดาห์นี้',
            'month' => 'เดือนนี้',
            'semester' => 'ภาคเรียนนี้',
            'custom' => 'กำหนดเอง'
        ]
    ],
    
    // แม่แบบการแจ้งเตือน
    'notification_templates' => [
        'risk_alert' => [
            'title' => 'แจ้งเตือนความเสี่ยงตกกิจกรรม',
            'variables' => ['ชื่อนักเรียน', 'รหัสนักเรียน', 'ชั้นเรียน', 'จำนวนวันเข้าแถว', 'จำนวนวันขาด', 'จำนวนวันทั้งหมด', 'ร้อยละการเข้าแถว', 'ชื่อครูที่ปรึกษา', 'เบอร์โทรครู']
        ],
        'absence_alert' => [
            'title' => 'แจ้งเตือนการขาดเรียน',
            'variables' => ['ชื่อนักเรียน', 'รหัสนักเรียน', 'ชั้นเรียน', 'วันที่', 'ชื่อครูที่ปรึกษา', 'เบอร์โทรครู']
        ],
        'monthly_report' => [
            'title' => 'รายงานประจำเดือน',
            'variables' => ['ชื่อนักเรียน', 'รหัสนักเรียน', 'ชั้นเรียน', 'จำนวนวันเข้าแถว', 'จำนวนวันขาด', 'จำนวนวันทั้งหมด', 'ร้อยละการเข้าแถว', 'สถานะการเข้าแถว', 'ชื่อครูที่ปรึกษา', 'เบอร์โทรครู', 'เดือน', 'ปี']
        ],
        'custom' => [
            'title' => 'ข้อความกำหนดเอง',
            'variables' => []
        ]
    ]
];

/**
 * ฟังก์ชันสำหรับเข้าถึงการตั้งค่า
 * @param string $key คีย์การตั้งค่าที่ต้องการ
 * @param mixed $default ค่าเริ่มต้นหากไม่พบการตั้งค่า
 * @return mixed ค่าการตั้งค่า
 */
function getReportConfig($key, $default = null) {
    global $config;
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * ฟังก์ชันสำหรับระบุระดับความเสี่ยงจากอัตราการเข้าแถว
 * @param float $rate อัตราการเข้าแถว (เปอร์เซ็นต์)
 * @return array ข้อมูลระดับความเสี่ยง
 */
function getRiskLevel($rate) {
    global $config;
    
    foreach ($config['risk_levels'] as $level => $info) {
        if ($rate >= $info['min'] && $rate <= $info['max']) {
            return array_merge(['level' => $level], $info);
        }
    }
    
    return $config['risk_levels']['failed']; // ค่าเริ่มต้นหากไม่พบระดับที่ตรงกัน
}

/**
 * ฟังก์ชันแปลงเดือนเป็นภาษาไทย
 * @param int $month เดือน (1-12)
 * @return string ชื่อเดือนภาษาไทย
 */
function getThaiMonthName($month) {
    $thaiMonths = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    
    return isset($thaiMonths[$month]) ? $thaiMonths[$month] : '';
}

/**
 * ฟังก์ชันแปลงวันที่เป็นรูปแบบไทย
 * @param string $date วันที่ในรูปแบบ Y-m-d
 * @return string วันที่ในรูปแบบไทย
 */
function formatThaiDate($date) {
    if (empty($date)) return '';
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = getThaiMonthName(date('n', $timestamp));
    $year = date('Y', $timestamp) + 543;
    
    return "$day $month $year";
}

/**
 * ฟังก์ชันแทนที่ตัวแปรในข้อความแจ้งเตือน
 * @param string $template ข้อความแม่แบบ
 * @param array $data ข้อมูลสำหรับแทนที่
 * @return string ข้อความที่แทนที่ตัวแปรแล้ว
 */
function replaceNotificationVariables($template, $data) {
    $variables = [
        '{{ชื่อนักเรียน}}' => $data['student_name'] ?? 'นักเรียน',
        '{{รหัสนักเรียน}}' => $data['student_code'] ?? '',
        '{{ชั้นเรียน}}' => $data['class_name'] ?? '',
        '{{จำนวนวันเข้าแถว}}' => $data['attendance_days'] ?? '0',
        '{{จำนวนวันขาด}}' => $data['absence_days'] ?? '0',
        '{{จำนวนวันทั้งหมด}}' => $data['total_days'] ?? '0',
        '{{ร้อยละการเข้าแถว}}' => $data['attendance_rate'] ?? '0',
        '{{ชื่อครูที่ปรึกษา}}' => $data['advisor_name'] ?? 'ครูที่ปรึกษา',
        '{{เบอร์โทรครู}}' => $data['advisor_phone'] ?? '',
        '{{เดือน}}' => $data['month'] ?? getThaiMonthName(date('n')),
        '{{ปี}}' => $data['year'] ?? (date('Y') + 543),
        '{{สถานะการเข้าแถว}}' => $data['status'] ?? 'ไม่ระบุ',
        '{{วันที่}}' => $data['date'] ?? formatThaiDate(date('Y-m-d'))
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $template);
}
?>