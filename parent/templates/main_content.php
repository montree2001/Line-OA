<div class="container">
    <!-- เนื้อหาเฉพาะหน้า -->
    <?php 
    if (isset($content_path) && file_exists($content_path)) {
        include $content_path;
    } else {
        echo '<div class="error-message">ไม่พบเนื้อหาที่ต้องการแสดงผล</div>';
    }
    ?>
</div>