<?php
// กำหนดเส้นทางฐาน (BASE_PATH) ถ้ายังไม่ได้กำหนด
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'announcements';
$page_title = 'จัดการประกาศ';
$page_header = 'จัดการประกาศ';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่
$admin_info = [
    'name' => 'จารุวรรณ บุญมี',
    'role' => 'เจ้าหน้าที่กิจการนักเรียน',
    'initials' => 'จ'
];

// ปุ่มบนส่วนหัว
$header_buttons = [
    [
        'text' => 'เพิ่มประกาศใหม่',
        'icon' => 'add_circle_outline',
        'onclick' => 'openAnnouncementModal()'
    ],
    [
        'text' => 'ดาวน์โหลดรายงาน',
        'icon' => 'file_download',
        'onclick' => 'downloadAnnouncementReport()'
    ]
];

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/announcements.css',
    'assets/plugins/datatables/dataTables.bootstrap4.min.css',
    'https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css'
];

$extra_js = [
    'assets/plugins/datatables/jquery.dataTables.min.js',
    'assets/plugins/datatables/dataTables.bootstrap4.min.js',
    'https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js',
    'assets/js/announcements.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/announcements_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// เพิ่ม CDN fallback กรณีที่ไฟล์ local ไม่ทำงาน
?>
<script>
// ตรวจสอบว่า jQuery ถูกโหลดหรือไม่
if (typeof jQuery === 'undefined') {
    document.write('<script src="https://code.jquery.com/jquery-3.6.0.min.js"><\/script>');
}

// ตรวจสอบว่า DataTable ถูกโหลดหรือไม่
setTimeout(function() {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.DataTable === 'undefined') {
        document.write('<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"><\/script>');
        document.write('<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"><\/script>');
    }
}, 500);

// ตรวจสอบว่า Summernote ถูกโหลดหรือไม่
setTimeout(function() {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.summernote === 'undefined') {
        document.write('<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">');
        document.write('<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"><\/script>');
    }
}, 1000);
</script>
<?php
// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';
?>