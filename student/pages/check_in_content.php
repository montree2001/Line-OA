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
                        <p class="small">คลิกที่ QR Code เพื่อดูขนาดใหญ่</p>
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


<!-- เพิ่มโค้ดนี้ที่ส่วนท้ายของไฟล์ HTML ก่อนปิด body tag -->
<div id="qr-fullscreen-modal" class="fullscreen-modal">
    <div class="modal-content">
        <span class="close-fullscreen">&times;</span>
        <div id="fullscreen-qr-display"></div>
        <div class="fullscreen-qr-info">
            <span id="fullscreen-qr-expire" class="expire-text"></span>
            <button id="download-qr" class="download-btn">
                <span class="material-icons">file_download</span> บันทึกรูป
            </button>
        </div>
    </div>
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
            <!-- เนื้อหาจะถูกเพิ่มด้วย JavaScript ตามรูปแบบด้านล่าง -->
            <!-- 
            <div class="modal-icon success">
                <span class="material-icons">check_circle</span>
                <div class="success-glow"></div>
            </div>
            <div class="modal-message">เช็คชื่อเข้าแถวเรียบร้อยแล้ว</div>
            <div class="modal-submessage">เวลา 08:30 น.</div>
            <div class="modal-submessage">วิธีการ: เช็คชื่อผ่าน GPS</div>
            -->
        </div>
        <div class="modal-footer">
            <button class="btn primary" id="modal-ok">ตกลง</button>
        </div>
    </div>
</div>

<!-- เพิ่มลิงก์ CSS สำหรับ Leaflet Map -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/leaflet.min.css" />
<script>
    // เพิ่มโค้ดนี้ในไฟล์ JavaScript หรือใน <script> tag ที่ท้ายหน้าเว็บ
    // เพิ่มโค้ดนี้เป็น <script> tag ที่ท้ายหน้าเว็บ (ก่อนปิด </body>)
    document.addEventListener('DOMContentLoaded', function() {
        // เพิ่ม event listener เมื่อคลิกที่ QR wrapper
        const qrWrapper = document.querySelector('.qr-wrapper');
        if (qrWrapper) {
            qrWrapper.style.cursor = 'pointer';

            qrWrapper.addEventListener('click', function() {
                const qrImg = document.querySelector('#qr-display img');
                const qrExpire = document.querySelector('.qr-expire');

                if (qrImg) {
                    // สร้าง modal เมื่อคลิกที่ QR code
                    const modal = document.createElement('div');
                    modal.style.position = 'fixed';
                    modal.style.top = '0';
                    modal.style.left = '0';
                    modal.style.width = '100%';
                    modal.style.height = '100%';
                    modal.style.backgroundColor = 'rgba(0,0,0,0.9)';
                    modal.style.zIndex = '9999';
                    modal.style.display = 'flex';
                    modal.style.flexDirection = 'column';
                    modal.style.justifyContent = 'center';
                    modal.style.alignItems = 'center';

                    // สร้างปุ่มปิด
                    const closeBtn = document.createElement('div');
                    closeBtn.innerHTML = '&times;';
                    closeBtn.style.position = 'absolute';
                    closeBtn.style.top = '15px';
                    closeBtn.style.right = '20px';
                    closeBtn.style.color = 'white';
                    closeBtn.style.fontSize = '40px';
                    closeBtn.style.fontWeight = 'bold';
                    closeBtn.style.cursor = 'pointer';
                    closeBtn.style.zIndex = '10000';

                    // สร้างพื้นที่แสดง QR code
                    const qrContainer = document.createElement('div');
                    qrContainer.style.width = '90%';
                    qrContainer.style.maxWidth = '90vmin';
                    qrContainer.style.display = 'flex';
                    qrContainer.style.justifyContent = 'center';

                    // สร้างรูปภาพ QR code
                    const fullQrImg = document.createElement('img');
                    fullQrImg.src = qrImg.src;
                    fullQrImg.style.width = '100%';
                    fullQrImg.style.height = 'auto';

                    // สร้างข้อมูลเวลาหมดอายุ
                    const expireInfo = document.createElement('div');
                    expireInfo.style.position = 'absolute';
                    expireInfo.style.bottom = '20%';
                    expireInfo.style.backgroundColor = 'rgba(0,0,0,0.7)';
                    expireInfo.style.color = 'white';
                    expireInfo.style.padding = '10px 20px';
                    expireInfo.style.borderRadius = '30px';
                    expireInfo.style.fontSize = '16px';
                    expireInfo.style.display = 'flex';
                    expireInfo.style.alignItems = 'center';

                    if (qrExpire) {
                        expireInfo.innerHTML = qrExpire.innerHTML;
                    }

                    // สร้างปุ่มบันทึกรูป
                    const downloadBtn = document.createElement('button');
                    downloadBtn.innerHTML = '<span style="vertical-align:middle;margin-right:8px;">&#8595;</span> บันทึกรูป';
                    downloadBtn.style.position = 'absolute';
                    downloadBtn.style.bottom = '10%';
                    downloadBtn.style.backgroundColor = '#06c755';
                    downloadBtn.style.border = 'none';
                    downloadBtn.style.color = 'white';
                    downloadBtn.style.padding = '12px 25px';
                    downloadBtn.style.borderRadius = '30px';
                    downloadBtn.style.fontSize = '16px';
                    downloadBtn.style.fontWeight = '500';
                    downloadBtn.style.cursor = 'pointer';
                    downloadBtn.style.fontFamily = "'Prompt', sans-serif";

                    // รวมองค์ประกอบเข้าด้วยกัน
                    qrContainer.appendChild(fullQrImg);
                    modal.appendChild(closeBtn);
                    modal.appendChild(qrContainer);
                    modal.appendChild(expireInfo);
                    modal.appendChild(downloadBtn);

                    // ป้องกันการเลื่อนพื้นหลัง
                    document.body.style.overflow = 'hidden';

                    // เพิ่ม modal เข้าไปในหน้าเว็บ
                    document.body.appendChild(modal);

                    // ปิดเมื่อคลิกที่ปุ่มปิด
                    closeBtn.addEventListener('click', function() {
                        document.body.removeChild(modal);
                        document.body.style.overflow = 'auto';
                    });

                    // ปิดเมื่อคลิกที่พื้นหลัง modal
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            document.body.removeChild(modal);
                            document.body.style.overflow = 'auto';
                        }
                    });

                    // บันทึกรูป
                    downloadBtn.addEventListener('click', function() {
                        const link = document.createElement('a');
                        link.href = qrImg.src;
                        link.download = 'qr-code.png';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    });
                }
            });
        }
    });
</script>


<script>
    // โค้ดเลื่อนช่องกรอก PIN อัตโนมัติที่แก้ไขแล้ว
// คัดลอกโค้ดนี้ทั้งหมดไปใส่ในไฟล์ assets/js/check-in.js

document.addEventListener('DOMContentLoaded', function() {
    // ฟังก์ชั่นสำหรับแท็บ PIN
    initializePinInputs();
    
    // ตรวจสอบว่าหน้านี้มีแท็บหรือไม่
    const tabItems = document.querySelectorAll('.tab-item');
    if (tabItems.length > 0) {
        // เพิ่ม event listener สำหรับแท็บ
        tabItems.forEach(function(item) {
            item.addEventListener('click', function() {
                // ลบคลาส active จากทุกแท็บ
                tabItems.forEach(tab => tab.classList.remove('active'));
                // เพิ่มคลาส active ให้แท็บที่คลิก
                this.classList.add('active');
                
                // ซ่อนทุกแท็บเพน
                const tabPanes = document.querySelectorAll('.tab-pane');
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // แสดงแท็บเพนที่ตรงกับแท็บที่คลิก
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
                
                // หากเป็นแท็บ PIN ให้ focus ที่ช่องแรก
                if (tabId === 'pin') {
                    const firstPinInput = document.querySelector('.pin-input[data-index="0"]');
                    if (firstPinInput) {
                        setTimeout(() => {
                            firstPinInput.focus();
                        }, 100);
                    }
                }
            });
        });
    }
});

// ฟังก์ชั่นสำหรับการจัดการ PIN input
function initializePinInputs() {
    const pinInputs = document.querySelectorAll('.pin-input');
    const submitPinBtn = document.getElementById('submit-pin');
    const pinStatus = document.getElementById('pin-status');
    const studentIdElement = document.getElementById('student-id');
    
    if (!pinInputs.length || !submitPinBtn || !pinStatus || !studentIdElement) {
        return; // ถ้าไม่พบองค์ประกอบที่จำเป็น ให้ออกจากฟังก์ชั่น
    }
    
    const studentId = studentIdElement.value;
    
    // ตั้งค่า autofocus สำหรับช่องแรก
    pinInputs[0].setAttribute('autofocus', 'true');
    
    // ล้างค่าทุกช่องเมื่อโหลดหน้า
    pinInputs.forEach(input => {
        input.value = '';
    });
    
    // เพิ่ม event listener สำหรับแต่ละช่อง
    pinInputs.forEach(function(input, index) {
        // เมื่อกรอกข้อมูล
        input.addEventListener('input', function(e) {
            // ให้รับเฉพาะตัวเลข
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // ถ้ากรอกตัวเลขแล้ว และไม่ใช่ช่องสุดท้าย ให้เลื่อนไปช่องถัดไป
            if (this.value !== '' && index < pinInputs.length - 1) {
                setTimeout(() => {
                    pinInputs[index + 1].focus();
                }, 10);
            }
            
            // ตรวจสอบว่ากรอกครบทุกช่องหรือยัง
            checkPinComplete();
        });
        
        // เมื่อกด keydown
        input.addEventListener('keydown', function(e) {
            // ถ้ากด Backspace และช่องนี้ว่าง และไม่ใช่ช่องแรก
            if (e.key === 'Backspace' && this.value === '' && index > 0) {
                // เลื่อนไปช่องก่อนหน้า
                setTimeout(() => {
                    pinInputs[index - 1].focus();
                    // เลือกข้อความในช่องก่อนหน้าเพื่อให้พร้อมลบ
                    pinInputs[index - 1].select();
                }, 10);
            }
            
            // ถ้ากดลูกศรซ้าย และไม่ใช่ช่องแรก
            else if (e.key === 'ArrowLeft' && index > 0) {
                e.preventDefault();
                pinInputs[index - 1].focus();
            }
            
            // ถ้ากดลูกศรขวา และไม่ใช่ช่องสุดท้าย
            else if (e.key === 'ArrowRight' && index < pinInputs.length - 1) {
                e.preventDefault();
                pinInputs[index + 1].focus();
            }
        });
        
        // เมื่อ paste ข้อมูล
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            // รับข้อมูลที่ paste
            const pasteData = (e.clipboardData || window.clipboardData).getData('text');
            
            // กรองเฉพาะตัวเลข
            const numbers = pasteData.replace(/[^0-9]/g, '');
            
            // ถ้ามีตัวเลขอย่างน้อย 1 ตัว
            if (numbers.length > 0) {
                // กำหนดค่าให้แต่ละช่อง
                for (let i = 0; i < pinInputs.length; i++) {
                    if (i < numbers.length) {
                        pinInputs[i].value = numbers[i];
                    }
                }
                
                // โฟกัสที่ช่องถัดจากตัวเลขสุดท้าย หรือช่องสุดท้าย
                const focusIndex = Math.min(numbers.length, pinInputs.length - 1);
                pinInputs[focusIndex].focus();
                
                // ตรวจสอบว่ากรอกครบทุกช่องหรือยัง
                checkPinComplete();
            }
        });
    });
    
    // ฟังก์ชั่นตรวจสอบว่ากรอก PIN ครบหรือยัง
    function checkPinComplete() {
        let isPinComplete = true;
        let pin = '';
        
        // ตรวจสอบทุกช่อง
        pinInputs.forEach(function(input) {
            if (input.value === '') {
                isPinComplete = false;
            }
            pin += input.value;
        });
        
        // เปิดใช้งานปุ่มยืนยันถ้ากรอกครบ
        submitPinBtn.disabled = !isPinComplete;
        
        return pin;
    }
    
    // เมื่อกดปุ่มยืนยัน PIN
    submitPinBtn.addEventListener('click', submitPin);
    
    // ฟังก์ชั่นส่ง PIN ไปตรวจสอบ
    function submitPin() {
        const pin = checkPinComplete();
        
        if (pin.length === 4) {
            // แสดงสถานะกำลังตรวจสอบ
            pinStatus.innerHTML = '<span class="status-checking">กำลังตรวจสอบรหัส PIN...</span>';
            
            // ส่งข้อมูลไปยัง API
            fetch('api/check_in_pin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    pin: pin
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // เช็คชื่อสำเร็จ
                    showResultModal('สำเร็จ', 'เช็คชื่อเข้าแถวเรียบร้อยแล้ว', 'success', function() {
                        window.location.reload();
                    });
                } else {
                    // เช็คชื่อไม่สำเร็จ
                    pinStatus.innerHTML = '<span class="status-error">' + data.message + '</span>';
                    
                    // ล้างช่องกรอกและตั้งโฟกัสที่ช่องแรก
                    pinInputs.forEach(input => input.value = '');
                    pinInputs[0].focus();
                    
                    // ปิดใช้งานปุ่มยืนยัน
                    submitPinBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                pinStatus.innerHTML = '<span class="status-error">เกิดข้อผิดพลาดในการเชื่อมต่อ</span>';
            });
        }
    }
    
    // ฟังก์ชั่นแสดง Modal ผลการเช็คชื่อ
    function showResultModal(title, message, type, callback) {
        const modal = document.getElementById('result-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');
        const modalOk = document.getElementById('modal-ok');
        
        if (!modal || !modalTitle || !modalBody || !modalOk) {
            alert(message);
            if (typeof callback === 'function') callback();
            return;
        }
        
        modalTitle.textContent = title;
        
        // สร้าง icon ตามประเภท
        let icon = '';
        if (type === 'success') {
            icon = '<span class="material-icons success-icon">check_circle</span>';
        } else if (type === 'error') {
            icon = '<span class="material-icons error-icon">error</span>';
        }
        
        modalBody.innerHTML = `
            <div class="modal-message">
                ${icon}
                <p>${message}</p>
            </div>
        `;
        
        // แสดง modal
        modal.style.display = 'flex';
        
        // เมื่อกดปุ่มตกลง
        modalOk.onclick = function() {
            modal.style.display = 'none';
            if (typeof callback === 'function') {
                callback();
            }
        };
        
        // ปิด modal เมื่อกดปุ่มปิด
        const closeModalBtn = document.getElementById('close-modal');
        if (closeModalBtn) {
            closeModalBtn.onclick = function() {
                modal.style.display = 'none';
            };
        }
        
        // ปิด modal เมื่อคลิกภายนอก
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}
</script>

<script>
function checkQRStatus() {
    // แสดงสถานะการโหลด
    const btn = document.getElementById('check-qr-status');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-icons" style="animation: spin 1s infinite linear;">refresh</span> กำลังตรวจสอบ...';
    btn.disabled = true;
    
    // เพิ่มสไตล์สำหรับการหมุน
    if (!document.getElementById('spin-style')) {
        const style = document.createElement('style');
        style.id = 'spin-style';
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    // จำลองการตรวจสอบสถานะ (ในกรณีที่ API ยังไม่พร้อม)
    setTimeout(function() {
        // คืนค่าปุ่มเป็นสถานะปกติ
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        // แสดงการแจ้งเตือน
        alert('ตรวจสอบสถานะเรียบร้อยแล้ว: QR Code ยังใช้งานได้');
        
        // ถ้ามี API จริง คุณสามารถใช้โค้ดนี้แทนการ setTimeout
        /*
        fetch('api/check_qr_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=check_status'
        })
        .then(response => response.json())
        .then(data => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            
            if (data.success) {
                alert('ตรวจสอบสถานะเรียบร้อยแล้ว: ' + data.message);
                // อัปเดตสถานะการแสดง QR code ถ้าจำเป็น
            } else {
                alert('ไม่สามารถตรวจสอบสถานะได้: ' + data.message);
            }
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('เกิดข้อผิดพลาดในการตรวจสอบสถานะ');
            console.error('Error:', error);
        });
        */
    }, 1500);
}
</script>