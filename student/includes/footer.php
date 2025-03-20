<script>
        // Show loading indicator when form is submitted
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            const loading = document.getElementById('loading');
            
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    loading.classList.add('active');
                });
            });
            
            // Preview image when uploaded
            const fileInput = document.getElementById('profile_picture');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        var reader = new FileReader();
                        
                        reader.onload = function(e) {
                            document.getElementById('preview-img').src = e.target.result;
                            document.getElementById('image-preview').style.display = 'block';
                            document.querySelector('.upload-area').style.display = 'none';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Reset image
            const resetButton = document.querySelector('.btn.secondary[onclick="resetImage()"]');
            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    resetImage();
                });
            }
        });
        
        function resetImage() {
            const fileInput = document.getElementById('profile_picture');
            if (fileInput) {
                fileInput.value = '';
                document.getElementById('image-preview').style.display = 'none';
                document.querySelector('.upload-area').style.display = 'block';
            }
        }
        
        // Show privacy policy
        function showPrivacyPolicy() {
            alert('นโยบายความเป็นส่วนตัวของวิทยาลัยการอาชีพปราสาท\n\nวิทยาลัยการอาชีพปราสาทจะเก็บรวบรวมข้อมูลส่วนบุคคลของนักเรียนเพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์เท่านั้น โดยจะไม่เปิดเผยข้อมูลต่อบุคคลที่สาม ยกเว้นในกรณีที่จำเป็นต้องปฏิบัติตามกฎหมาย');
        }
    </script>
</body>
</html>