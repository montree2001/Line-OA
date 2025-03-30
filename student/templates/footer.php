<!-- Footer -->
<div class="footer" style="text-align: center; margin-top: 20px; padding: 10px; font-size: 12px; color: #666;">
        &copy; <?php echo date('Y'); ?> STD-Prasat - ระบบเช็คชื่อเข้าแถวออนไลน์ <br>
        วิทยาลัยการอาชีพปราสาท
    </div>

    <!-- JavaScript -->
    <script>
        // ฟังก์ชันย้อนกลับ
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = "../index.php";
            }
        }
        
        // แสดง/ซ่อน Loading Indicator
        function showLoading() {
            document.getElementById('loading-indicator').classList.add('active');
        }
        
        function hideLoading() {
            document.getElementById('loading-indicator').classList.remove('active');
        }
        
        // เพิ่ม Event Listener เมื่อ Submit Form
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    // แสดง Loading เมื่อ Submit Form
                    showLoading();
                });
            });
        });
    </script>
    
    <!-- Extra JS -->
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>