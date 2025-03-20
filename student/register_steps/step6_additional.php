<!-- ขั้นตอนกรอกข้อมูลเพิ่มเติม -->
<div class="card">
    <div class="card-title">กรอกข้อมูลเพิ่มเติม</div>
    <div class="card-content">
        <form method="POST" action="register.php?step=6" enctype="multipart/form-data">
            <div class="input-container">
                <label class="input-label">เบอร์โทรศัพท์ (ไม่บังคับ)</label>
                <input type="tel" class="input-field" name="phone_number" placeholder="กรอกเบอร์โทรศัพท์" pattern="[0-9]{10}" maxlength="10">
                <div class="help-text">กรอกเฉพาะตัวเลข 10 หลัก</div>
            </div>

            <div class="input-container">
                <label class="input-label">อีเมล (ไม่บังคับ)</label>
                <input type="email" class="input-field" name="email" placeholder="example@email.com">
            </div>

            <div class="input-container">
                <label class="input-label">รูปโปรไฟล์ (ไม่บังคับ)</label>
                <div class="upload-area" onclick="document.getElementById('profile_picture').click()">
                    <input type="file" id="profile_picture" name="profile_picture" style="display: none;" accept="image/*">
                    <div class="upload-icon">
                        <span class="material-icons">cloud_upload</span>
                    </div>
                    <div class="upload-text">คลิกเพื่ออัพโหลดรูปโปรไฟล์</div>
                    <div class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</div>
                </div>
                <div id="image-preview" style="display: none;">
                    <img id="preview-img" src="#" alt="รูปโปรไฟล์" class="responsive-img">
                    <button type="button" class="btn secondary" onclick="resetImage()">
                        <span class="material-icons">refresh</span> เลือกรูปใหม่
                    </button>
                </div>
            </div>

            <div class="checkbox-container">
                <input type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
                <label for="gdpr_consent" class="checkbox-label">
                    ข้าพเจ้ายินยอมให้วิทยาลัยการอาชีพปราสาทเก็บข้อมูลส่วนบุคคลของข้าพเจ้า เพื่อใช้ในระบบเช็คชื่อเข้าแถวออนไลน์
                    <a href="#" onclick="showPrivacyPolicy()">นโยบายความเป็นส่วนตัว</a>
                </label>
            </div>

            <button type="submit" class="btn primary">
                ลงทะเบียน <span class="material-icons">check</span>
            </button>
        </form>
    </div>
</div>