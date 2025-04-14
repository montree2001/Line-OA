<?php
// ตรวจสอบว่าเป็นหน้าเช็คชื่อใหม่หรือไม่
$is_new_check_attendance = (strpos($content_path, 'new_check_attendance_content.php') !== false);
?>

<?php if ($is_new_check_attendance): ?>
    <!-- ไม่ใช้ container เดิมกับหน้าเช็คชื่อใหม่ -->
    <?php require_once $content_path; ?>
<?php else: ?>
    <!-- ใช้ container เดิมกับหน้าอื่นๆ -->
    <main class="content">
        <div class="container-fluid">
            <?php require_once $content_path; ?>
        </div>
    </main>
<?php endif; ?>