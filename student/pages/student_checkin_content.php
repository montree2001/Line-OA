<!-- ส่วนหัว -->
<div class="header">
    <a href="home.php" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>เช็คชื่อเข้าแถว</h1>
    <div class="header-icon">
        <span class="material-icons">help_outline</span>
    </div>
</div>

<div class="container">
    <!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-success">
        <span class="material-icons">check_circle</span>
        <span class="alert-message"><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error">
        <span class="material-icons">error</span>
        <span class="alert-message"><?php echo $error; ?></span>
    </div>
    <?php endif; ?>

    <!-- การ์ดเวลาและสถานะ -->
    <div class="status-card">
        <div class="date-display">
            <div class="current-date"><?php echo $thai_date; ?></div>
        </div>
        <div class="time-display" id="current-time">--:--:--</div>
        <div class="time-description">เวลาเช็คชื่อ: <?php echo $check_in_time_range; ?></div>
        <div class="status-indicator <?php echo $check_in_open ? 'status-open' : 'status-closed'; ?>"
             data-start-time="<?php echo $start_time; ?>"
             data-end-time="<?php echo $end_time; ?>">
            <?php echo $check_in_open ? 'เปิดให้เช็คชื่อ' : 'ปิดการเช็คชื่อแล้ว'; ?>
        </div>
    </div>

    <!-- วิธีการเช็คชื่อ -->
    <div class="check-methods-card">
        <div class="card-title">
            <span class="material-icons">how_to_reg</span> เลือกวิธีการเช็คชื่อ
        </div>
        
        <div class="method-grid">
            <?php if ($enable_gps): ?>
            <div class="method-card" onclick="showMethod('gps')">
                <div class="method-icon gps">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="method-name">GPS</div>
                <div class="method-description">เช็คชื่อด้วยตำแหน่งที่ตั้ง</div>
            </div>
            <?php endif; ?>
            
            <?php if ($enable_qr): ?>
            <div class="method-card" onclick="showMethod('qr')">
                <div class="method-icon qr">
                    <span class="material-icons">qr_code_2</span>
                </div>
                <div class="method-name">QR Code</div>
                <div class="method-description">สร้าง QR เพื่อให้ครูสแกน</div>
            </div>
            <?php endif; ?>
            
            <?php if ($enable_pin): ?>
            <div class="method-card" onclick="showMethod('pin')">
                <div class="method-icon pin">
                    <span class="material-icons">pin</span>
                </div>
                <div class="method-name">รหัส PIN</div>
                <div class="method-description">ใส่รหัส PIN จากครู</div>
            </div>
            <?php endif; ?>
            
            <?php if ($enable_photo): ?>
            <div class="method-card" onclick="showMethod('photo')">
                <div class="method-icon photo">
                    <span class="material-icons">add_a_photo</span>
                </div>
                <div class="method-name">ถ่ายรูป</div>
                <div class="method-description">อัพโหลดรูปเข้าแถว</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GPS Method -->
    <div class="gps-card" id="gps-method">
        <div class="card-title">
            <span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS
        </div>
        
        <form method="post" action="check-in.php">
            <input type="hidden" name="check_method" value="gps">
            <input type="hidden" name="latitude" id="gps-latitude" value="">
            <input type="hidden" name="longitude" id="gps-longitude" value="">
            <input type="hidden" name="accuracy" id="gps-accuracy" value="">
            
            <div class="gps-status">
                <div class="gps-icon">
                    <span class="material-icons">gps_fixed</span>
                </div>
                <div class="gps-text" id="gps-status-text">กำลังตรวจสอบตำแหน่ง</div>
                <div class="gps-subtext" id="gps-status-subtext">โปรดรอสักครู่...</div>
            </div>
            
            <div class="gps-details">
                <div class="gps-detail-item">
                    <div class="gps-detail-label">สถานะ GPS:</div>
                    <div class="gps-detail-value" id="gps-status-value">กำลังตรวจสอบ...</div>
                </div>
                <div class="gps-detail-item">
                    <div class="gps-detail-label">ระยะห่างจากโรงเรียน:</div>
                    <div class="gps-detail-value" id="gps-distance-value">กำลังคำนวณ...</div>
                </div>
                <div class="gps-detail-item">
                    <div class="gps-detail-label">ความแม่นยำ:</div>
                    <div class="gps-detail-value" id="gps-accuracy-value">รอข้อมูล...</div>
                </div>
            </div>
            
            <button type="submit" class="gps-action" id="gps-submit-button" disabled>
                <span class="material-icons">check</span> ยืนยันการเช็คชื่อด้วย GPS
            </button>
        </form>
    </div>

    <!-- QR Code Method -->
    <div class="qr-card" id="qr-method">
        <div class="card-title">
            <span class="material-icons">qr_code_2</span> เช็คชื่อด้วย QR Code
        </div>
        
        <form method="post" action="check-in.php">
            <input type="hidden" name="check_method" value="qr">
            <input type="hidden" name="qr_action" value="generate">
            
            <div class="qr-container">
                <?php if (isset($qr_code_id)): ?>
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qr_data); ?>&size=200x200" alt="QR Code" id="qr-image">
                </div>
                <div class="qr-info">
                    QR Code นี้จะหมดอายุในอีก 7 วัน<br>
                    แสดงให้ครูสแกนเพื่อเช็คชื่อ
                </div>
                <?php else: ?>
                <div class="qr-code">
                    <img src="assets/img/qr-placeholder.png" alt="QR Code Placeholder" id="qr-image-placeholder" style="opacity: 0.5;">
                </div>
                <div class="qr-info">
                    คลิกปุ่มด้านล่างเพื่อสร้าง QR Code<br>
                    แล้วแสดงให้ครูสแกนเพื่อเช็คชื่อ
                </div>
                <?php endif; ?>
            </div>
            
            <button type="submit" class="qr-refresh" id="refresh-qr">
                <span class="material-icons">refresh</span> 
                <?php echo isset($qr_code_id) ? 'สร้าง QR Code ใหม่' : 'สร้าง QR Code'; ?>
            </button>
        </form>
    </div>

    <!-- PIN Method -->
    <div class="pin-card" id="pin-method">
        <div class="card-title">
            <span class="material-icons">pin</span> เช็คชื่อด้วยรหัส PIN
        </div>
        
        <form method="post" action="check-in.php">
            <input type="hidden" name="check_method" value="pin">
            
            <div class="pin-input-container">
                <div class="pin-input">
                    <input type="text" name="pin[]" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                    <input type="text" name="pin[]" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                    <input type="text" name="pin[]" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                    <input type="text" name="pin[]" class="pin-digit" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="off" required>
                </div>
                <div class="pin-info">
                    กรอกรหัส PIN 4 หลักที่ได้รับจากครู
                </div>
            </div>
            
            <button type="submit" class="pin-submit" id="submit-pin">
                <span class="material-icons">check</span> ยืนยันรหัส PIN
            </button>
        </form>
    </div>

    <!-- อัพโหลดรูปภาพ -->
    <div class="upload-card" id="photo-method">
        <div class="card-title">
            <span class="material-icons">add_a_photo</span> อัพโหลดรูปภาพการเข้าแถว
        </div>
        
        <form method="post" action="check-in.php" enctype="multipart/form-data">
            <input type="hidden" name="check_method" value="photo">
            
            <div class="upload-area" onclick="document.getElementById('file-upload').click()">
                <input type="file" id="file-upload" name="attendance_photo" style="display: none;" accept="image/*" onchange="previewImage(this)">
                <div class="upload-icon">
                    <span class="material-icons">cloud_upload</span>
                </div>
                <div class="upload-text">คลิกเพื่ออัพโหลดภาพถ่ายการเข้าแถว</div>
                <div class="upload-subtext">รองรับไฟล์ JPG, PNG ขนาดไม่เกิน 5MB</div>
            </div>
            
            <div class="upload-preview" id="image-preview" style="display: none;">
                <div class="preview-title">
                    <span class="material-icons">photo</span> ภาพตัวอย่าง
                </div>
                <img src="#" class="preview-image" id="preview-img" alt="ภาพตัวอย่าง">
                
                <div class="upload-actions">
                    <button type="button" class="upload-button secondary" onclick="resetImage()">
                        <span class="material-icons">refresh</span> เลือกใหม่
                    </button>
                    <button type="submit" class="upload-button primary">
                        <span class="material-icons">file_upload</span> อัพโหลด
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>