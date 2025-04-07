<?php
/**
 * functions.php - ไฟล์รวมฟังก์ชันทั่วไปสำหรับระบบน้องชูใจ AI
 */

/**
 * แสดงข้อความแบบปลอดภัยจาก XSS
 * @param string $str ข้อความที่ต้องการแสดงผล
 * @return string ข้อความที่ปลอดภัย
 */
function safe_echo($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * รับค่าจาก $_GET แบบปลอดภัย
 * @param string $key ชื่อคีย์
 * @param mixed $default ค่าเริ่มต้นถ้าไม่มีค่า
 * @return mixed ค่าที่รับมา
 */
function get_param($key, $default = '') {
    return isset($_GET[$key]) ? filter_var($_GET[$key], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : $default;
}

/**
 * รับค่าจาก $_POST แบบปลอดภัย
 * @param string $key ชื่อคีย์
 * @param mixed $default ค่าเริ่มต้นถ้าไม่มีค่า
 * @return mixed ค่าที่รับมา
 */
function post_param($key, $default = '') {
    return isset($_POST[$key]) ? filter_var($_POST[$key], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : $default;
}

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
 * @return bool true ถ้าล็อกอินแล้ว, false ถ้ายังไม่ล็อกอิน
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * ตรวจสอบว่าผู้ใช้มีบทบาทตามที่กำหนดหรือไม่
 * @param string|array $roles บทบาทที่ต้องการตรวจสอบ
 * @return bool true ถ้ามีบทบาทตามที่กำหนด, false ถ้าไม่มี
 */
function has_role($roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    } else {
        return $_SESSION['role'] === $roles;
    }
}

/**
 * สร้าง URL ด้วยพารามิเตอร์
 * @param string $base_url URL หลัก
 * @param array $params พารามิเตอร์เป็น key-value array
 * @return string URL ที่สร้างขึ้น
 */
function build_url($base_url, $params = []) {
    $query = http_build_query($params);
    return $base_url . ($query ? '?' . $query : '');
}

/**
 * แปลงวันที่เป็นรูปแบบไทย (พ.ศ.)
 * @param string $date วันที่ในรูปแบบ Y-m-d
 * @param bool $include_time รวมเวลาด้วยหรือไม่
 * @return string วันที่ในรูปแบบไทย
 */
function thai_date($date, $include_time = false) {
    if (empty($date)) return '';
    
    $thai_month_abbrs = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    
    $time_format = $include_time ? ' H:i' : '';
    $timestamp = strtotime($date);
    
    $thai_date = date('j', $timestamp) . ' ' . 
                 $thai_month_abbrs[date('n', $timestamp)] . ' ' . 
                 (date('Y', $timestamp) + 543);
    
    if ($include_time) {
        $thai_date .= ' ' . date('H:i', $timestamp) . ' น.';
    }
    
    return $thai_date;
}

/**
 * แปลงเวลาให้อยู่ในรูปแบบ "เมื่อ x นาที/ชั่วโมง/วัน ที่แล้ว"
 * @param string $datetime วันที่และเวลา
 * @return string ข้อความแสดงเวลาที่ผ่านมา
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $current = time();
    $diff = $current - $time;
    
    $intervals = [
        31536000 => 'ปี',
        2592000 => 'เดือน',
        604800 => 'สัปดาห์',
        86400 => 'วัน',
        3600 => 'ชั่วโมง',
        60 => 'นาที',
        1 => 'วินาที'
    ];
    
    foreach ($intervals as $seconds => $unit) {
        $count = floor($diff / $seconds);
        if ($count > 0) {
            return "เมื่อ $count $unit" . ($count > 1 && $unit !== 'เดือน' ? '' : '') . "ที่แล้ว";
        }
    }
    
    return 'เมื่อสักครู่';
}

/**
 * ตัดข้อความให้สั้นลงถ้ายาวเกินไป
 * @param string $text ข้อความที่ต้องการตัด
 * @param int $length ความยาวสูงสุดที่ต้องการ
 * @param string $append ข้อความที่ต้องการเพิ่มต่อท้าย
 * @return string ข้อความที่ถูกตัด
 */
function truncate_text($text, $length = 100, $append = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length, 'UTF-8') . $append;
}

/**
 * แปลงข้อความ Markdown เป็น HTML
 * @param string $text ข้อความในรูปแบบ Markdown
 * @return string ข้อความในรูปแบบ HTML
 */
function markdown_to_html($text) {
    // ถ้ามีการติดตั้ง Parsedown หรือไลบรารีอื่นๆ ให้ใช้ไลบรารีนั้น
    // สำหรับตัวอย่างนี้ ใช้การแปลงแบบง่ายๆ
    
    // แปลงหัวข้อ
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    
    // แปลงตัวหนา
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
    
    // แปลงตัวเอียง
    $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text);
    
    // แปลงลิงก์
    $text = preg_replace('/\[(.+?)\]\((.+?)\)/s', '<a href="$2">$1</a>', $text);
    
    // แปลงรายการ
    $text = preg_replace('/^- (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/((?:<li>.+<\/li>\n?)+)/', '<ul>$1</ul>', $text);
    
    // แปลงย่อหน้า
    $text = preg_replace('/^(?!<h|<ul|<li)(.+)$/m', '<p>$1</p>', $text);
    
    return $text;
}

/**
 * สร้างเลขสุ่ม
 * @param int $length ความยาวของเลขสุ่ม
 * @return string เลขสุ่มที่สร้างขึ้น
 */
function generate_random_number($length = 4) {
    $min = pow(10, $length - 1);
    $max = pow(10, $length) - 1;
    return str_pad(rand($min, $max), $length, '0', STR_PAD_LEFT);
}

/**
 * คำนวณเปอร์เซ็นต์
 * @param int $value ค่าที่ต้องการคำนวณ
 * @param int $total ค่าทั้งหมด
 * @param int $decimals จำนวนทศนิยม
 * @return float เปอร์เซ็นต์
 */
function calculate_percentage($value, $total, $decimals = 1) {
    if ($total == 0) {
        return 0;
    }
    
    return round(($value / $total) * 100, $decimals);
}

/**
 * เช็คว่าอยู่ในช่วงที่กำหนดหรือไม่
 * @param string $time เวลาที่ต้องการตรวจสอบ (รูปแบบ H:i:s)
 * @param string $start_time เวลาเริ่มต้น (รูปแบบ H:i:s)
 * @param string $end_time เวลาสิ้นสุด (รูปแบบ H:i:s)
 * @return bool true ถ้าอยู่ในช่วงเวลา, false ถ้าไม่อยู่
 */
function is_time_between($time, $start_time, $end_time) {
    $time = strtotime($time);
    $start_time = strtotime($start_time);
    $end_time = strtotime($end_time);
    
    if ($start_time <= $end_time) {
        return $time >= $start_time && $time <= $end_time;
    } else {
        // กรณีข้ามวัน (เช่น 23:00 - 06:00)
        return $time >= $start_time || $time <= $end_time;
    }
}

/**
 * ตรวจสอบว่าวันที่อยู่ในเทอมการศึกษาหรือไม่
 * @param string $date วันที่ที่ต้องการตรวจสอบ (รูปแบบ Y-m-d)
 * @param string $semester_start วันเริ่มต้นเทอม (รูปแบบ Y-m-d)
 * @param string $semester_end วันสิ้นสุดเทอม (รูปแบบ Y-m-d)
 * @return bool true ถ้าอยู่ในเทอม, false ถ้าไม่อยู่
 */
function is_date_in_semester($date, $semester_start, $semester_end) {
    $date = strtotime($date);
    $semester_start = strtotime($semester_start);
    $semester_end = strtotime($semester_end);
    
    return $date >= $semester_start && $date <= $semester_end;
}

/**
 * ตรวจสอบว่าเป็นวันหยุดหรือไม่
 * @param string $date วันที่ที่ต้องการตรวจสอบ (รูปแบบ Y-m-d)
 * @param array $holidays รายการวันหยุด (array ของวันที่ในรูปแบบ Y-m-d)
 * @param bool $count_weekend นับเสาร์-อาทิตย์เป็นวันหยุดหรือไม่
 * @return bool true ถ้าเป็นวันหยุด, false ถ้าไม่ใช่
 */
function is_holiday($date, $holidays = [], $count_weekend = true) {
    $timestamp = strtotime($date);
    $day_of_week = date('N', $timestamp);
    
    // เช็คว่าเป็นเสาร์-อาทิตย์หรือไม่
    if ($count_weekend && ($day_of_week == 6 || $day_of_week == 7)) {
        return true;
    }
    
    // เช็คว่าอยู่ในรายการวันหยุดหรือไม่
    return in_array(date('Y-m-d', $timestamp), $holidays);
}
?>