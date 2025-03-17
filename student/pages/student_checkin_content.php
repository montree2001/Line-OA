<div class="header">
        <a href="#" onclick="goBack()" class="header-icon">
            <span class="material-icons">arrow_back</span>
        </a>
        <h1>เช็คชื่อ</h1>
        <div class="header-icon">
            <span class="material-icons">notifications</span>
        </div>
    </div>


<div class="container">
    <!-- การ์ดเวลาและสถานะ -->
    <div class="status-card">
        <div class="date-display">
            <div class="current-date"><?php echo $thai_date; ?></div>
        </div>
        <div class="time-display" id="current-time">--:--:--</div>
        <div class="time-description">เวลาเช็คชื่อ: <?php echo $check_in_time_range; ?></div>
        <div class="status-indicator <?php echo $check_in_open ? 'status-open' : 'status-closed'; ?>">
            <?php echo $check_in_open ? 'เปิดให้เช็คชื่อ' : 'ปิดการเช็คชื่อแล้ว'; ?>
        </div>
    </div>

    <!-- วิธีการเช็คชื่อ -->
    <div class="check-methods-card">
        <div class="card-title">
            <span class="material-icons">how_to_reg</span> เลือกวิธีการเช็คชื่อ
        </div>
        
        <div class="method-grid">
            <div class="method-card" onclick="showMethod('gps')">
                <div class="method-icon gps">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="method-name">GPS</div>
                <div class="method-description">เช็คชื่อด้วยตำแหน่งที่ตั้ง</div>
            </div>
            
            <div class="method-card" onclick="showMethod('qr')">
                <div class="method-icon qr">
                    <span class="material-icons">qr_code_2</span>
                </div>
                <div class="method-name">QR Code</div>
                <div class="method-description">สร้าง QR เพื่อให้ครูสแกน</div>
            </div>
            
            <div class="method-card" onclick="showMethod('pin')">
                <div class="method-icon pin">
                    <span class="material-icons">pin</span>
                </div>
                <div class="method-name">รหัส PIN</div>
                <div class="method-description">ใส่รหัส PIN จากครู</div>
            </div>
            
            <div class="method-card" onclick="showMethod('photo')">
                <div class="method-icon photo">
                    <span class="material-icons">add_a_photo</span>
                </div>
                <div class="method-name">ถ่ายรูป</div>
                <div class="method-description">อัพโหลดรูปเข้าแถว</div>
            </div>
        </div>
    </div>

    <!-- GPS Method -->
    <div class="gps-card" id="gps-method">
        <div class="card-title">
            <span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS
        </div>
        
        <div class="gps-status">
            <div class="gps-icon">
                <span class="material-icons">gps_fixed</span>
            </div>
            <div class="gps-text">กำลังตรวจสอบตำแหน่ง</div>
            <div class="gps-subtext">โปรดรอสักครู่...</div>
        </div>
        
        <div class="gps-details">
            <div class="gps-detail-item">
                <div class="gps-detail-label">สถานะ GPS:</div>
                <div class="gps-detail-value">พร้อมใช้งาน</div>
            </div>
            <div class="gps-detail-item">
                <div class="gps-detail-label">ระยะห่างจากโรงเรียน:</div>
                <div class="gps-detail-value">45 เมตร</div>
            </div>
            <div class="gps-detail-item">
                <div class="gps-detail-label">ความแม่นยำ:</div>
                <div class="gps-detail-value">± 10 เมตร</div>
            </div>
        </div>
        
        <button class="gps-action">
            <span class="material-icons">check</span> ยืนยันการเช็คชื่อด้วย GPS
        </button>
    </div>

    <!-- QR Code Method -->
    <div class="qr-card" id="qr-method">
        <div class="card-title">
            <span class="material-icons">qr_code_2</span> เช็คชื่อด้วย QR Code
        </div>
        
        <div class="qr-container">
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?data=STD-16536-16032025-0745&size=200x200" alt="QR Code" id="qr-image">
            </div>
            <div class="qr-info">
                QR Code นี้จะหมดอายุในอีก <span id="qr-timer">5:00</span> นาที<br>
                แสดงให้ครูสแกนเพื่อเช็คชื่อ
            </div>
        </div>
        
        <button class="qr-refresh" id="refresh-qr">
            <span class="material-icons">refresh</span> สร้าง QR Code ใหม่
        </button>
    </div>

    <!-- PIN Method -->
    <div class="pin-card" id="pin-method">
        <div class="card-title">
            <span class="material-icons">pin</span> เช็คชื่อด้วยรหัส PIN
        </div>
        
        <div class="pin-input-container">
            <div class="pin-input">
                <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
                <input type="text" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off">
            </div>
            <div class="pin-info">
                กรอกรหัส PIN 4 หลักที่ได้รับจากครู
            </div>
        </div>
        
        <button class="pin-submit" id="submit-pin">
            <span class="material-icons">check</span> ยืนยันรหัส PIN
        </button>
    </div>

    <!-- อัพโหลดรูปภาพ -->
    <div class="upload-card" id="photo-method">
        <div class="card-title">
            <span class="material-icons">add_a_photo</span> อัพโหลดรูปภาพการเข้าแถว
        </div>
        
        <div class="upload-area" onclick="document.getElementById('file-upload').click()">
            <input type="file" id="file-upload" style="display: none;" accept="image/*">
            <div class="upload-icon">
                <span class="material-icons">cloud_upload</span>
            </div>
            <div class="upload-text">คลิกเพื่ออัพโหลดภาพถ่ายการเข้าแถว</div>
            <div class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</div>
        </div>
        
        <div class="upload-preview" id="image-preview">
            <div class="preview-title">
                <span class="material-icons">photo</span> ภาพตัวอย่าง
            </div>
            <img src="#" class="preview-image" id="preview-img" alt="ภาพตัวอย่าง">
            
            <div class="upload-actions">
                <button class="upload-button secondary" id="reset-image">
                    <span class="material-icons">refresh</span> เลือกใหม่
                </button>
                <button class="upload-button primary" id="upload-image">
                    <span class="material-icons">file_upload</span> อัพโหลด
                </button>
            </div>
        </div>
    </div>
</div>