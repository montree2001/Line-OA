<!-- Main scripts -->
<script src="assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    <!-- Custom scripts for specific pages -->
    <?php if(isset($extra_js)): ?>
        <?php foreach($extra_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
        // Toggle sidebar on mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('overlay').classList.toggle('active');
        });
        
        // Close sidebar when clicking overlay
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('overlay').classList.remove('active');
        });
        
        // Close sidebar when clicking a menu item on mobile
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    document.getElementById('sidebar').classList.remove('active');
                    document.getElementById('overlay').classList.remove('active');
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                document.getElementById('sidebar').classList.remove('active');
                document.getElementById('overlay').classList.remove('active');
            }
        });
        
        // Toggle admin dropdown
        const adminMenuToggle = document.getElementById('adminMenuToggle');
        const adminDropdown = document.getElementById('adminDropdown');
        
        if (adminMenuToggle && adminDropdown) {
            adminMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                adminDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                adminDropdown.classList.remove('active');
            });
            
            // Prevent dropdown from closing when clicking inside
            adminDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ตรวจสอบว่าใช้ Bootstrap เวอร์ชันใด
        var bootstrapVersion = '';
        
        if (typeof bootstrap !== 'undefined') {
            bootstrapVersion = '5.x';
        } else if (typeof jQuery !== 'undefined' && typeof jQuery.fn.modal !== 'undefined') {
            bootstrapVersion = '4.x';
        } else {
            bootstrapVersion = 'ไม่พบ Bootstrap';
        }
        
        console.log('Bootstrap Version:', bootstrapVersion);
        
        // แก้ไขการเรียก Modal ตามเวอร์ชัน Bootstrap
        if (typeof $ !== 'undefined') {
            $(document).on('click', '#direct-create-btn', function() {
                console.log('Create button clicked - jQuery handler');
                if (bootstrapVersion === '5.x' && typeof bootstrap !== 'undefined') {
                    var modalElement = document.getElementById('announcementModal');
                    var modalObj = new bootstrap.Modal(modalElement);
                    modalObj.show();
                } else if (bootstrapVersion === '4.x') {
                    $('#announcementModal').modal('show');
                } else {
                    alert('ไม่พบ Bootstrap ที่สามารถใช้งาน Modal ได้');
                }
            });
        }
    });
    </script>
</body>
</html>