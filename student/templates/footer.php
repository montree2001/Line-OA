<!-- Main scripts -->
<script src="assets/js/main.js"></script>
    
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
</body>
</html>