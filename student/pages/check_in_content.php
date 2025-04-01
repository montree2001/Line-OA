<div class="header">
    <button class="back-button" onclick="history.back()">
        <span class="material-icons">arrow_back</span>
    </button>
    <div class="header-title">เช็คชื่อเข้าแถว</div>
    <div class="header-spacer"></div>
</div>

<div class="container">
    <?php if ($attendance_info['already_checked_in']): ?>
        <!-- กรณีเช็คชื่อไปแล้ว -->
        <div class="check-in-card success-card">
            <div class="check-in-icon">
                <span class="material-icons success-icon">check_circle</span>
            </div>
            <div class="check-in-message">
                <h2>เช็คชื่อเข้าแถวสำเร็จ</h2>
                <p>คุณได้เช็คชื่อเข้าแถวของวันนี้เรียบร้อยแล้ว</p>
                <div class="check-in-details">
                    <div class="detail-item">
                        <span class="material-icons">access_time</span>
                        <span>เวลาเช็คชื่อ: <?php echo $attendance_info['check_in_time']; ?> น.</span>
                    </div>
                    <div class="detail-item">
                        <span class="material-icons">how_to_reg</span>
                        <span>วิธีการเช็คชื่อ: <?php echo $attendance_info['check_in_method']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="home.php" class="btn primary">
                <span class="material-icons">home</span> กลับหน้าหลัก
            </a>
            <a href="history.php" class="btn secondary">
                <span class="material-icons">history</span> ดูประวัติการเช็คชื่อ
            </a>
        </div>

    <?php elseif (!$attendance_info['check_in_available']): ?>
        <!-- กรณีไม่อยู่ในช่วงเวลาเช็คชื่อ -->
        <div class="check-in-card warning-card">
            <div class="check-in-icon">
                <span class="material-icons warning-icon">access_time</span>
            </div>
            <div class="check-in-message">
                <h2>ไม่อยู่ในช่วงเวลาเช็คชื่อ</h2>
                <p>ขณะนี้ไม่อยู่ในช่วงเวลาเช็คชื่อเข้าแถว</p>
                <div class="check-in-details">
                    <div class="detail-item">
                        <span class="material-icons">schedule</span>
                        <span>ช่วงเวลาเช็คชื่อ: <?php echo $attendance_info['start_time']; ?> - <?php echo $attendance_info['end_time']; ?> น.</span>
                    </div>
                    <div class="detail-item">
                        <span class="material-icons">today</span>
                        <span>วันที่: <?php echo date('d/m/Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-buttons">
            <a href="home.php" class="btn primary">
                <span class="material-icons">home</span> กลับหน้าหลัก
            </a>
        </div>

    <?php else: ?>
        <!-- กรณีพร้อมเช็คชื่อ -->
        <div class="profile-summary">
            <div class="profile-info">
                <?php if (!empty($student['profile_picture'])): ?>
                    <div class="profile-image profile-image-pic" style="background-image: url('<?php echo $student['profile_picture']; ?>');"></div>
                <?php else: ?>
                    <div class="profile-image"><?php echo $first_char; ?></div>
                <?php endif; ?>
                <div class="profile-details">
                    <div class="profile-name"><?php echo $student_info['name']; ?></div>
                    <div class="profile-class"><?php echo $student_info['class']; ?></div>
                </div>
            </div>
            <div class="check-time">
                <div class="time-label">เวลาเช็คชื่อ:</div>
                <div class="time-value"><?php echo $attendance_info['start_time']; ?> - <?php echo $attendance_info['end_time']; ?> น.</div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-header">
                <div class="tab-item active" data-tab="gps">
                    <span class="material-icons">gps_fixed</span>
                    <span>GPS</span>
                </div>
                <div class="tab-item" data-tab="qr">
                    <span class="material-icons">qr_code</span>
                    <span>QR Code</span>
                </div>
                <div class="tab-item" data-tab="pin">
                    <span class="material-icons">pin</span>
                    <span>PIN</span>
                </div>
                <div class="tab-item" data-tab="scan">
                    <span class="material-icons">qr_code_scanner</span>
                    <span>สแกน QR</span>
                </div>
            </div>

            <div class="tab-content">
                <!-- GPS Tab -->
                <div class="tab-pane active" id="gps-tab">
                    <div class="tab-description">
                        <p>เช็คชื่อด้วยตำแหน่ง GPS ของคุณ</p>
                        <p class="small">คุณต้องอยู่ในรัศมีที่กำหนดจากจุดศูนย์กลางของวิทยาลัย</p>
                    </div>

                    <div id="map" class="map-container"></div>

                    <div class="location-status">
                        <div class="status-icon">
                            <span class="material-icons" id="location-icon">location_searching</span>
                        </div>
                        <div class="status-text" id="location-status">กำลังค้นหาตำแหน่งของคุณ...</div>
                    </div>

                    <button id="check-in-gps" class="btn primary check-in-btn" disabled>
                        <span class="material-icons">gps_fixed</span> เช็คชื่อด้วย GPS
                    </button>

                    <input type="hidden" id="user-lat" value="">
                    <input type="hidden" id="user-lng" value="">
                    <input type="hidden" id="school-lat" value="<?php echo $gps_info['lat']; ?>">
                    <input type="hidden" id="school-lng" value="<?php echo $gps_info['lng']; ?>">
                    <input type="hidden" id="gps-radius" value="<?php echo $gps_info['radius']; ?>">
                    <input type="hidden" id="student-id" value="<?php echo $student_info['id']; ?>">
                </div>
<!-- QR Code Tab -->
<div class="tab-pane" id="qr-tab">
    <div class="tab-description">
        <p>สร้าง QR Code ให้ครูสแกนเพื่อเช็คชื่อ</p>
        <p class="small">QR Code จะหมดอายุภายใน 5 นาที หลังจากสร้าง</p>
    </div>
    
    <div class="qr-container">
        <div class="qr-wrapper">
            <div id="qr-display"></div>
            <div class="qr-placeholder">
                <span class="material-icons">qr_code</span>
                <span>กดปุ่มด้านล่างเพื่อสร้าง QR Code</span>
            </div>
        </div>
    </div>
    
    <button id="generate-qr" class="btn primary">
        <span class="material-icons">qr_code</span> สร้าง QR Code
    </button>
    
    <input type="hidden" id="student-id" value="<?php echo $student_info['id']; ?>">
</div>

                  

                <!-- PIN Tab -->
                <div class="tab-pane" id="pin-tab">
                    <div class="tab-description">
                        <p>กรอกรหัส PIN ที่ได้รับจากครู</p>
                        <p class="small">รหัส PIN ประกอบด้วยตัวเลข 4 หลัก</p>
                    </div>

                    <div class="pin-container">
                        <div class="pin-input-group">
                            <input type="text" class="pin-input" maxlength="1" data-index="0" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="1" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="2" pattern="[0-9]" inputmode="numeric">
                            <input type="text" class="pin-input" maxlength="1" data-index="3" pattern="[0-9]" inputmode="numeric">
                        </div>
                        <div class="pin-status" id="pin-status"></div>
                    </div>

                    <button id="submit-pin" class="btn primary" disabled>
                        <span class="material-icons">check_circle</span> ยืนยันรหัส PIN
                    </button>
                </div>

                <!-- Scan QR Tab -->
                <div class="tab-pane" id="scan-tab">
                    <div class="tab-description">
                        <p>สแกน QR Code จากครู</p>
                        <p class="small">อนุญาตการเข้าถึงกล้องเพื่อสแกน QR Code</p>
                    </div>

                    <div class="scanner-container">
                        <video id="qr-scanner" class="scanner"></video>
                        <div class="scanner-overlay">
                            <div class="scanner-frame"></div>
                        </div>
                    </div>

                    <button id="start-scan" class="btn primary">
                        <span class="material-icons">qr_code_scanner</span> เริ่มสแกน QR Code
                    </button>

                    <button id="stop-scan" class="btn secondary" style="display: none;">
                        <span class="material-icons">stop</span> หยุดสแกน
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal สำหรับแสดงผลการเช็คชื่อ -->
<div class="modal" id="result-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">ผลการเช็คชื่อ</h2>
            <button class="close-modal" id="close-modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body" id="modal-body">
            <!-- ข้อความจะถูกเพิ่มด้วย JavaScript -->
        </div>
        <div class="modal-footer">
            <button class="btn primary" id="modal-ok">ตกลง</button>
        </div>
    </div>
</div>

<!-- เพิ่มลิงก์ CSS สำหรับ Leaflet Map -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />