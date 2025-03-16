<!-- แท็บการตั้งค่าระบบ -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="system">การตั้งค่าระบบ</div>
        <div class="tab" data-tab="notification">การแจ้งเตือน</div>
        <div class="tab" data-tab="attendance">การเช็คชื่อ</div>
        <div class="tab" data-tab="gps">การตั้งค่า GPS</div>
        <div class="tab" data-tab="line">การเชื่อมต่อ LINE</div>
    </div>
</div>
<!-- การตั้งค่าระบบทั่วไป -->
<div id="system-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">settings</span>
            การตั้งค่าระบบทั่วไป
        </div>
        <div class="settings-section">
            <h3>ข้อมูลโรงเรียน</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อโรงเรียน</label>
                        <input type="text" class="form-control" value="โรงเรียนประสาทวิทยาคม">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัสโรงเรียน</label>
                        <input type="text" class="form-control" value="10001">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ที่อยู่โรงเรียน</label>
                        <input type="text" class="form-control" value="123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" value="044-511234">
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>ปีการศึกษา</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ปีการศึกษาปัจจุบัน</label>
                        <select class="form-control">
                            <option>2568 (2025)</option>
                            <option>2567 (2024)</option>
                            <option>2566 (2023)</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ภาคเรียน</label>
                        <select class="form-control">
                            <option>ภาคเรียนที่ 2/2568</option>
                            <option>ภาคเรียนที่ 1/2568</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การแสดงผล</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ภาษา</label>
                        <select class="form-control">
                            <option>ไทย</option>
                            <option>English</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ธีมสี</label>
                        <select class="form-control">
                            <option>เขียว (ค่าเริ่มต้น)</option>
                            <option>น้ำเงิน</option>
                            <option>แดง</option>
                            <option>ส้ม</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- การตั้งค่าการแจ้งเตือน -->
<div id="notification-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">notifications</span>
            การตั้งค่าการแจ้งเตือน
        </div>
        <div class="settings-section">
            <h3>การแจ้งเตือนทั่วไป</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable-notifications" checked>
                    <label for="enable-notifications">เปิดใช้งานการแจ้งเตือน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="critical-notifications" checked>
                    <label for="critical-notifications">แจ้งเตือนกรณีฉุกเฉิน</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การแจ้งเตือนการเข้าแถว</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่ขาดแถวก่อนการแจ้งเตือน</label>
                        <select class="form-control">
                            <option>5 ครั้ง</option>
                            <option>10 ครั้ง</option>
                            <option>15 ครั้ง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ช่วงเวลาแจ้งเตือนสำหรับนักเรียนเสี่ยง</label>
                        <select class="form-control">
                            <option>สัปดาห์ละครั้ง</option>
                            <option>2 สัปดาห์ต่อครั้ง</option>
                            <option>เดือนละครั้ง</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="auto-notifications" checked>
                    <label for="auto-notifications">แจ้งเตือนอัตโนมัติสำหรับนักเรียนที่เสี่ยงตกกิจกรรม</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>ช่องทางการแจ้งเตือน</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="line-notification" checked>
                    <label for="line-notification">LINE Official Account</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms-notification">
                    <label for="sms-notification">SMS</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="email-notification">
                    <label for="email-notification">อีเมล</label>
                </div>
            </div>
        </div>

    </div>
    <!-- การตั้งค่าการเช็คชื่อ -->
</div>
<!-- การตั้งค่าการเช็คชื่อ -->
<div id="attendance-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">how_to_reg</span>
            การตั้งค่าการเช็คชื่อ
        </div>
        <div class="settings-section">
            <h3>กฎเกณฑ์การเข้าแถว</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อัตราการเข้าแถวต่ำสุดที่ผ่านกิจกรรม</label>
                        <select class="form-control">
                            <option>80%</option>
                            <option>85%</option>
                            <option>90%</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการนับการเข้าแถว</label>
                        <select class="form-control">
                            <option>ภาคเรียน</option>
                            <option>ปีการศึกษา</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="count-weekend" checked>
                    <label for="count-weekend">นับเช็คชื่อในวันหยุดราชการ</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>วิธีการเช็คชื่อ</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable-qr" checked>
                    <label for="enable-qr">เปิดใช้งานการเช็คชื่อผ่าน QR Code</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable-pin" checked>
                    <label for="enable-pin">เปิดใช้งานการเช็คชื่อผ่านรหัส PIN</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อายุของรหัส PIN</label>
                        <select class="form-control">
                            <option>5 นาที</option>
                            <option selected>10 นาที</option>
                            <option>15 นาที</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่สามารถใช้ PIN ได้</label>
                        <select class="form-control">
                            <option>ใช้ได้ครั้งเดียว</option>
                            <option selected>ใช้ได้ 3 ครั้ง</option>
                            <option>ใช้ได้ไม่จำกัด</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การเข้าแถวช่วงเวลา</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาเริ่มเช็คชื่อ</label>
                        <input type="time" class="form-control" value="07:30">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาสิ้นสุดการเช็คชื่อ</label>
                        <input type="time" class="form-control" value="08:20">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="late-check" checked>
                    <label for="late-check">อนุญาตให้เช็คชื่อล่าช้าได้</label>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการเช็คชื่อล่าช้า</label>
                        <select class="form-control">
                            <option>15 นาที</option>
                            <option selected>30 นาที</option>
                            <option>45 นาที</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">การบันทึกสถานะการมา</label>
                        <select class="form-control">
                            <option>มาสาย</option>
                            <option selected>ลดคะแนนความประพฤติ</option>
                            <option>ไม่มีผลใดๆ</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- การตั้งค่า GPS -->
<div id="gps-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">location_on</span>
            การตั้งค่า GPS
        </div>

        <div class="settings-section">
            <h3>ตำแหน่งโรงเรียน</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ละติจูด</label>
                        <input type="text" class="form-control" value="14.0065" placeholder="กรอกค่าละติจูด">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ลองจิจูด</label>
                        <input type="text" class="form-control" value="100.5018" placeholder="กรอกค่าลองจิจูด">
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การตั้งค่าการตรวจสอบ GPS</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รัศมีที่อนุญาต (เมตร)</label>
                        <select class="form-control">
                            <option>50 เมตร</option>
                            <option selected>100 เมตร</option>
                            <option>200 เมตร</option>
                            <option>500 เมตร</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความแม่นยำตำแหน่ง</label>
                        <select class="form-control">
                            <option>±5 เมตร</option>
                            <option selected>±10 เมตร</option>
                            <option>±20 เมตร</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps-required" checked>
                    <label for="gps-required">บังคับใช้การยืนยันตำแหน่ง GPS</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps-photo-required">
                    <label for="gps-photo-required">ถ่ายรูปประกอบการเช็คชื่อ</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การอนุญาตเช็คชื่อจากตำแหน่งอื่น</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow-home-check">
                    <label for="allow-home-check">อนุญาตให้เช็คชื่อจากที่บ้าน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow-parent-verification">
                    <label for="allow-parent-verification">ให้ผู้ปกครองยืนยันตำแหน่ง</label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- การเชื่อมต่อ LINE -->
<div id="line-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">settings_applications</span>
            การตั้งค่าการเชื่อมต่อ LINE
        </div>
        <div class="settings-section">
            <h3>LINE Official Account</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ LINE OA</label>
                        <input type="text" class="form-control" value="SADD-Prasat" placeholder="ชื่อ LINE Official Account">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัส LINE OA</label>
                        <input type="text" class="form-control" value="@sadd-prasat" placeholder="@ชื่อบัญชี">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับ</label>
                <textarea class="form-control" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ">ยินดีต้อนรับสู่ระบบ STUDENT-Prasat ระบบติดตามการเข้าแถวและแจ้งเตือนสำหรับนักเรียนและผู้ปกครอง</textarea>
            </div>
        </div>
    </div>
</div>





    <script>
        // การเปลี่ยนแท็บ
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelector('.tab.active').classList.remove('active');
                document.querySelector('.tab-content.active').classList.remove('active');
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-tab').classList.add('active');
            });
        });
    </script>