<!-- แท็บการตั้งค่าระบบ -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="system">การตั้งค่าระบบ</div>
        <div class="tab" data-tab="notification">การแจ้งเตือน</div>
        <div class="tab" data-tab="attendance">การเช็คชื่อ</div>
        <div class="tab" data-tab="gps">การตั้งค่า GPS</div>
        <div class="tab" data-tab="line">การเชื่อมต่อ LINE</div>
        <div class="tab" data-tab="sms">การเชื่อมต่อ SMS</div>
        <div class="tab" data-tab="webhook">Webhook และการตอบกลับอัตโนมัติ</div>
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
                        <label class="form-label">ชื่อระบบ</label>
                        <input type="text" class="form-control" id="system_name" name="system_name" value="น้องชูใจ AI ดูแลผู้เรียน">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อโรงเรียน</label>
                        <input type="text" class="form-control" id="school_name" name="school_name" value="โรงเรียนประสาทวิทยาคม">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัสโรงเรียน</label>
                        <input type="text" class="form-control" id="school_code" name="school_code" value="10001">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัสสำหรับการลงทะเบียนแอดมิน</label>
                        <input type="text" class="form-control" id="admin_registration_code" name="admin_registration_code" value="ADMIN2025">
                        <small class="form-text text-muted">รหัสนี้ใช้สำหรับการลงทะเบียนเป็นแอดมินในครั้งแรกเท่านั้น</small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ที่อยู่โรงเรียน</label>
                        <input type="text" class="form-control" id="school_address" name="school_address" value="123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="school_phone" name="school_phone" value="044-511234">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อีเมลโรงเรียน</label>
                        <input type="email" class="form-control" id="school_email" name="school_email" value="prasat_school@example.com">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เว็บไซต์โรงเรียน</label>
                        <input type="text" class="form-control" id="school_website" name="school_website" value="http://www.prasatwittayakom.ac.th">
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
                        <select class="form-control" id="current_academic_year" name="current_academic_year">
                            <option value="2568">2568 (2025)</option>
                            <option value="2567">2567 (2024)</option>
                            <option value="2566">2566 (2023)</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ภาคเรียน</label>
                        <select class="form-control" id="current_semester" name="current_semester">
                            <option value="2">ภาคเรียนที่ 2/2568</option>
                            <option value="1">ภาคเรียนที่ 1/2568</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">วันเริ่มต้นภาคเรียน</label>
                        <input type="date" class="form-control" id="semester_start_date" name="semester_start_date" value="2024-11-01">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">วันสิ้นสุดภาคเรียน</label>
                        <input type="date" class="form-control" id="semester_end_date" name="semester_end_date" value="2025-04-01">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="auto_promote_students" name="auto_promote_students" checked>
                    <label for="auto_promote_students">เลื่อนชั้นนักเรียนอัตโนมัติเมื่อเปลี่ยนปีการศึกษา</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="reset_attendance_new_semester" name="reset_attendance_new_semester" checked>
                    <label for="reset_attendance_new_semester">เริ่มนับการเข้าแถวใหม่เมื่อเข้าสู่ภาคเรียนใหม่</label>
                </div>
            </div>
            <div class="form-group">
                <a href="#" class="btn btn-primary" id="add-academic-year">
                    <span class="material-icons">add</span>
                    เพิ่มปีการศึกษาใหม่
                </a>
            </div>
        </div>

        <div class="settings-section">
            <h3>การแสดงผล</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ภาษา</label>
                        <select class="form-control" id="system_language" name="system_language">
                            <option value="th">ไทย</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ธีมสี</label>
                        <select class="form-control" id="system_theme" name="system_theme">
                            <option value="green">เขียว (ค่าเริ่มต้น)</option>
                            <option value="blue">น้ำเงิน</option>
                            <option value="red">แดง</option>
                            <option value="orange">ส้ม</option>
                            <option value="purple">ม่วง</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row" id="custom-theme-colors" style="display: none;">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีหลัก</label>
                        <input type="color" class="form-control" id="primary_color" name="primary_color" value="#28a745">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีรอง</label>
                        <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="#6c757d">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีพื้นหลัง</label>
                        <input type="color" class="form-control" id="background_color" name="background_color" value="#f8f9fa">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="dark_mode" name="dark_mode">
                    <label for="dark_mode">เปิดใช้งานโหมดกลางคืน</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การสำรองข้อมูล</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความถี่ในการสำรองข้อมูลอัตโนมัติ</label>
                        <select class="form-control" id="backup_frequency" name="backup_frequency">
                            <option value="daily">ทุกวัน</option>
                            <option value="weekly" selected>ทุกสัปดาห์</option>
                            <option value="monthly">ทุกเดือน</option>
                            <option value="never">ไม่ต้องสำรองอัตโนมัติ</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนการสำรองข้อมูลที่เก็บไว้</label>
                        <select class="form-control" id="backup_keep_count" name="backup_keep_count">
                            <option value="3">3 ชุดล่าสุด</option>
                            <option value="5" selected>5 ชุดล่าสุด</option>
                            <option value="10">10 ชุดล่าสุด</option>
                            <option value="0">เก็บทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">พาธที่เก็บไฟล์สำรองข้อมูล</label>
                        <input type="text" class="form-control" id="backup_path" name="backup_path" value="backups/">
                    </div>
                </div>
            </div>
            <div class="form-group mt-3">
                <button class="btn btn-primary" id="backup-now">
                    <span class="material-icons">backup</span>
                    สำรองข้อมูลทันที
                </button>
                <button class="btn btn-secondary ml-2" id="restore-backup">
                    <span class="material-icons">restore</span>
                    กู้คืนข้อมูล
                </button>
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
                    <input type="checkbox" id="enable_notifications" name="enable_notifications" checked>
                    <label for="enable_notifications">เปิดใช้งานการแจ้งเตือน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="critical_notifications" name="critical_notifications" checked>
                    <label for="critical_notifications">แจ้งเตือนกรณีฉุกเฉิน</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="send_daily_summary" name="send_daily_summary">
                    <label for="send_daily_summary">ส่งสรุปรายวันให้ผู้ปกครอง</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="send_weekly_summary" name="send_weekly_summary" checked>
                    <label for="send_weekly_summary">ส่งสรุปรายสัปดาห์ให้ผู้ปกครอง</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การแจ้งเตือนการเข้าแถว</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่ขาดแถวก่อนการแจ้งเตือน</label>
                        <select class="form-control" id="absence_threshold" name="absence_threshold">
                            <option value="3">3 ครั้ง</option>
                            <option value="5" selected>5 ครั้ง</option>
                            <option value="10">10 ครั้ง</option>
                            <option value="15">15 ครั้ง</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-absence-threshold" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">กำหนดจำนวนครั้งเอง</label>
                        <input type="number" class="form-control" id="custom_absence_threshold" name="custom_absence_threshold" min="1" value="5">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ช่วงเวลาแจ้งเตือนสำหรับนักเรียนเสี่ยง</label>
                        <select class="form-control" id="risk_notification_frequency" name="risk_notification_frequency">
                            <option value="daily">ทุกวัน</option>
                            <option value="weekly" selected>สัปดาห์ละครั้ง</option>
                            <option value="biweekly">2 สัปดาห์ต่อครั้ง</option>
                            <option value="monthly">เดือนละครั้ง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาที่ส่งการแจ้งเตือน</label>
                        <input type="time" class="form-control" id="notification_time" name="notification_time" value="16:00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="auto_notifications" name="auto_notifications" checked>
                    <label for="auto_notifications">แจ้งเตือนอัตโนมัติสำหรับนักเรียนที่เสี่ยงตกกิจกรรม</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความแจ้งเตือนสำหรับนักเรียนเสี่ยง</label>
                <textarea class="form-control" id="risk_notification_message" name="risk_notification_message" rows="3">เรียนผู้ปกครอง บุตรหลานของท่านขาดการเข้าแถวจำนวน {absent_count} ครั้ง ซึ่งมีความเสี่ยงที่จะไม่ผ่านกิจกรรม โปรดติดต่อครูที่ปรึกษา: {advisor_name} โทร: {advisor_phone}</textarea>
                <small class="form-text text-muted">คุณสามารถใช้ตัวแปร {student_name}, {absent_count}, {advisor_name}, {advisor_phone}, {school_name} ในข้อความได้</small>
            </div>
        </div>

        <div class="settings-section">
            <h3>ช่องทางการแจ้งเตือน</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="line_notification" name="line_notification" checked>
                    <label for="line_notification">LINE Official Account</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_notification" name="sms_notification">
                    <label for="sms_notification">SMS</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="email_notification" name="email_notification">
                    <label for="email_notification">อีเมล</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="app_notification" name="app_notification" checked>
                    <label for="app_notification">การแจ้งเตือนในแอป</label>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การแจ้งเตือนรายบุคคล/รายกลุ่ม</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_bulk_notifications" name="enable_bulk_notifications" checked>
                    <label for="enable_bulk_notifications">เปิดใช้งานการแจ้งเตือนแบบกลุ่ม</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">จำนวนผู้รับสูงสุดต่อการส่งแบบกลุ่ม</label>
                <input type="number" class="form-control" id="max_bulk_recipients" name="max_bulk_recipients" min="1" value="50">
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_scheduled_notifications" name="enable_scheduled_notifications">
                    <label for="enable_scheduled_notifications">เปิดใช้งานการตั้งเวลาส่งล่วงหน้า</label>
                </div>
            </div>
        </div>
    </div>
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
                        <label class="form-label">อัตราการเข้าแถวต่ำสุดที่ผ่านกิจกรรม (%)</label>
                        <select class="form-control" id="min_attendance_rate" name="min_attendance_rate">
                            <option value="70">70%</option>
                            <option value="75">75%</option>
                            <option value="80" selected>80%</option>
                            <option value="85">85%</option>
                            <option value="90">90%</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-attendance-rate" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">กำหนดอัตราเอง (%)</label>
                        <input type="number" class="form-control" id="custom_attendance_rate" name="custom_attendance_rate" min="1" max="100" value="80">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนวันที่ต้องเข้าแถวเพื่อผ่านกิจกรรม</label>
                        <input type="number" class="form-control" id="required_attendance_days" name="required_attendance_days" min="1" value="80">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการนับการเข้าแถว</label>
                        <select class="form-control" id="attendance_counting_period" name="attendance_counting_period">
                            <option value="semester" selected>ภาคเรียน</option>
                            <option value="academic_year">ปีการศึกษา</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="count_weekend" name="count_weekend" checked>
                    <label for="count_weekend">นับเช็คชื่อในวันหยุดราชการ</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="count_holidays" name="count_holidays">
                    <label for="count_holidays">นับเช็คชื่อในวันหยุดนักขัตฤกษ์</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">วันที่ยกเว้นการเช็คชื่อ (ใส่วันที่คั่นด้วยเครื่องหมาย ,)</label>
                <input type="text" class="form-control" id="exemption_dates" name="exemption_dates" value="2025-01-01, 2025-04-13, 2025-04-14, 2025-04-15">
                <small class="form-text text-muted">รูปแบบวันที่: YYYY-MM-DD (ปี-เดือน-วัน)</small>
            </div>
        </div>

        <div class="settings-section">
            <h3>วิธีการเช็คชื่อ</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_qr" name="enable_qr" checked>
                    <label for="enable_qr">เปิดใช้งานการเช็คชื่อผ่าน QR Code</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_pin" name="enable_pin" checked>
                    <label for="enable_pin">เปิดใช้งานการเช็คชื่อผ่านรหัส PIN</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_gps" name="enable_gps" checked>
                    <label for="enable_gps">เปิดใช้งานการเช็คชื่อผ่าน GPS</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_photo" name="enable_photo">
                    <label for="enable_photo">เปิดใช้งานการเช็คชื่อพร้อมรูปถ่าย</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_manual" name="enable_manual" checked>
                    <label for="enable_manual">เปิดใช้งานการเช็คชื่อแบบแมนนวล (สำหรับครูและแอดมิน)</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อายุของรหัส PIN</label>
                        <select class="form-control" id="pin_expiration" name="pin_expiration">
                            <option value="5">5 นาที</option>
                            <option value="10" selected>10 นาที</option>
                            <option value="15">15 นาที</option>
                            <option value="30">30 นาที</option>
                            <option value="60">1 ชั่วโมง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่สามารถใช้ PIN ได้</label>
                        <select class="form-control" id="pin_usage_limit" name="pin_usage_limit">
                            <option value="1">ใช้ได้ครั้งเดียว</option>
                            <option value="3" selected>ใช้ได้ 3 ครั้ง</option>
                            <option value="5">ใช้ได้ 5 ครั้ง</option>
                            <option value="10">ใช้ได้ 10 ครั้ง</option>
                            <option value="0">ใช้ได้ไม่จำกัด</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความยาวของรหัส PIN</label>
                        <select class="form-control" id="pin_length" name="pin_length">
                            <option value="4" selected>4 หลัก</option>
                            <option value="6">6 หลัก</option>
                            <option value="8">8 หลัก</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ประเภทของรหัส PIN</label>
                        <select class="form-control" id="pin_type" name="pin_type">
                            <option value="numeric" selected>ตัวเลขเท่านั้น</option>
                            <option value="alphanumeric">ตัวอักษรและตัวเลข</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อายุของ QR Code</label>
                        <select class="form-control" id="qr_expiration" name="qr_expiration">
                            <option value="1">1 นาที</option>
                            <option value="5" selected>5 นาที</option>
                            <option value="10">10 นาที</option>
                            <option value="30">30 นาที</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่สามารถใช้ QR Code ได้</label>
                        <select class="form-control" id="qr_usage_limit" name="qr_usage_limit">
                            <option value="1" selected>ใช้ได้ครั้งเดียว</option>
                            <option value="3">ใช้ได้ 3 ครั้ง</option>
                            <option value="0">ใช้ได้ไม่จำกัด</option>
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
                        <input type="time" class="form-control" id="attendance_start_time" name="attendance_start_time" value="07:30">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาสิ้นสุดการเช็คชื่อ</label>
                        <input type="time" class="form-control" id="attendance_end_time" name="attendance_end_time" value="08:20">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="late_check" name="late_check" checked>
                    <label for="late_check">อนุญาตให้เช็คชื่อล่าช้าได้</label>
                </div>
            </div>

            <div class="row" id="late-check-options">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการเช็คชื่อล่าช้า</label>
                        <select class="form-control" id="late_check_duration" name="late_check_duration">
                            <option value="15">15 นาที</option>
                            <option value="30" selected>30 นาที</option>
                            <option value="45">45 นาที</option>
                            <option value="60">1 ชั่วโมง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">การบันทึกสถานะการมา</label>
                        <select class="form-control" id="late_check_status" name="late_check_status">
                            <option value="late">มาสาย</option>
                            <option value="deduct_score" selected>ลดคะแนนความประพฤติ</option>
                            <option value="no_penalty">ไม่มีผลใดๆ</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนคะแนนที่หักเมื่อมาสาย</label>
                        <input type="number" class="form-control" id="late_deduct_points" name="late_deduct_points" min="0" value="1">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนคะแนนที่หักเมื่อขาด</label>
                        <input type="number" class="form-control" id="absent_deduct_points" name="absent_deduct_points" min="0" value="3">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การอัพโหลดรูปภาพการเข้าแถว</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="require_attendance_photo" name="require_attendance_photo">
                    <label for="require_attendance_photo">บังคับให้มีรูปถ่ายประกอบการเช็คชื่อ</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ขนาดไฟล์รูปสูงสุด (KB)</label>
                        <input type="number" class="form-control" id="max_photo_size" name="max_photo_size" min="100" value="5000">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ประเภทไฟล์ที่อนุญาต</label>
                        <input type="text" class="form-control" id="allowed_photo_types" name="allowed_photo_types" value="jpg,jpeg,png">
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
                        <input type="text" class="form-control" id="school_latitude" name="school_latitude" value="14.0065" placeholder="กรอกค่าละติจูด">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ลองจิจูด</label>
                        <input type="text" class="form-control" id="school_longitude" name="school_longitude" value="100.5018" placeholder="กรอกค่าลองจิจูด">
                    </div>
                </div>
            </div>
            <div class="form-group mt-3">
                <button class="btn btn-secondary" id="get-current-location">
                    <span class="material-icons">my_location</span>
                    ใช้ตำแหน่งปัจจุบัน
                </button>
                <button class="btn btn-primary ml-2" id="show-map">
                    <span class="material-icons">map</span>
                    เลือกจากแผนที่
                </button>
            </div>
            <div id="map-container" class="mt-3" style="display: none; height: 300px; border-radius: 8px; overflow: hidden;">
                <!-- แผนที่จะแสดงที่นี่ผ่าน JavaScript -->
            </div>
        </div>

        <div class="settings-section">
            <h3>การตั้งค่าการตรวจสอบ GPS</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รัศมีที่อนุญาต (เมตร)</label>
                        <select class="form-control" id="gps_radius" name="gps_radius">
                            <option value="50">50 เมตร</option>
                            <option value="100" selected>100 เมตร</option>
                            <option value="200">200 เมตร</option>
                            <option value="500">500 เมตร</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-gps-radius" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">กำหนดรัศมีเอง (เมตร)</label>
                        <input type="number" class="form-control" id="custom_gps_radius" name="custom_gps_radius" min="10" value="100">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความแม่นยำตำแหน่ง</label>
                        <select class="form-control" id="gps_accuracy" name="gps_accuracy">
                            <option value="5">±5 เมตร</option>
                            <option value="10" selected>±10 เมตร</option>
                            <option value="20">±20 เมตร</option>
                            <option value="50">±50 เมตร</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาในการตรวจสอบตำแหน่ง (วินาที)</label>
                        <input type="number" class="form-control" id="gps_check_interval" name="gps_check_interval" min="1" value="5">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_required" name="gps_required" checked>
                    <label for="gps_required">บังคับใช้การยืนยันตำแหน่ง GPS</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_photo_required" name="gps_photo_required">
                    <label for="gps_photo_required">ถ่ายรูปประกอบการเช็คชื่อด้วย GPS</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_mock_detection" name="gps_mock_detection" checked>
                    <label for="gps_mock_detection">ตรวจจับการปลอมแปลงตำแหน่ง GPS</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การอนุญาตเช็คชื่อจากตำแหน่งอื่น</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow_home_check" name="allow_home_check">
                    <label for="allow_home_check">อนุญาตให้เช็คชื่อจากที่บ้าน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow_parent_verification" name="allow_parent_verification">
                    <label for="allow_parent_verification">ให้ผู้ปกครองยืนยันตำแหน่ง</label>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">เหตุผลที่อนุญาตให้เช็คชื่อจากที่บ้าน (คั่นด้วยเครื่องหมาย ,)</label>
                        <input type="text" class="form-control" id="home_check_reasons" name="home_check_reasons" value="เจ็บป่วย, โควิด, อุบัติเหตุ, ไปราชการ">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>จุดเช็คชื่อเพิ่มเติม</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_multiple_locations" name="enable_multiple_locations">
                    <label for="enable_multiple_locations">อนุญาตให้มีจุดเช็คชื่อหลายจุด</label>
                </div>
            </div>
            
            <div id="additional-locations" class="mt-3" style="display: none;">
                <div class="additional-location-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ชื่อสถานที่</label>
                                <input type="text" class="form-control" name="location_name[]" value="สนามกีฬา">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">รัศมี (เมตร)</label>
                                <input type="number" class="form-control" name="location_radius[]" value="100">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ละติจูด</label>
                                <input type="text" class="form-control" name="location_latitude[]" value="14.0070">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ลองจิจูด</label>
                                <input type="text" class="form-control" name="location_longitude[]" value="100.5030">
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-danger remove-location">
                        <span class="material-icons">delete</span>
                        ลบสถานที่
                    </button>
                </div>
                
                <button class="btn btn-primary" id="add-location">
                    <span class="material-icons">add_location</span>
                    เพิ่มสถานที่
                </button>
            </div>
        </div>
    </div>
</div>

<!-- การตั้งค่าการเชื่อมต่อ LINE -->
<div id="line-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">settings_applications</span>
            การตั้งค่าการเชื่อมต่อ LINE
        </div>
        <div class="settings-section">
            <h3>LINE Official Account สำหรับผู้ปกครอง</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ LINE OA</label>
                        <input type="text" class="form-control" id="parent_line_oa_name" name="parent_line_oa_name" value="SADD-Prasat" placeholder="ชื่อ LINE Official Account">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัส LINE OA</label>
                        <input type="text" class="form-control" id="parent_line_oa_id" name="parent_line_oa_id" value="@sadd-prasat" placeholder="@ชื่อบัญชี">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel ID</label>
                        <input type="text" class="form-control" id="parent_line_channel_id" name="parent_line_channel_id" value="2007088707" placeholder="Channel ID">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel Secret</label>
                        <input type="text" class="form-control" id="parent_line_channel_secret" name="parent_line_channel_secret" value="ebd6dffa14e54908a835c59c3bd3a7cf" placeholder="Channel Secret">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Channel Access Token</label>
                <input type="text" class="form-control" id="parent_line_access_token" name="parent_line_access_token" value="" placeholder="ใส่ Channel Access Token">
            </div>

            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับ</label>
                <textarea class="form-control" id="parent_line_welcome_message" name="parent_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ">ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวและแจ้งเตือนสำหรับผู้ปกครอง</textarea>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>LINE Official Account สำหรับนักเรียน</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ LINE OA</label>
                        <input type="text" class="form-control" id="student_line_oa_name" name="student_line_oa_name" value="STD-Prasat" placeholder="ชื่อ LINE Official Account">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัส LINE OA</label>
                        <input type="text" class="form-control" id="student_line_oa_id" name="student_line_oa_id" value="@std-prasat" placeholder="@ชื่อบัญชี">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel ID</label>
                        <input type="text" class="form-control" id="student_line_channel_id" name="student_line_channel_id" value="" placeholder="Channel ID">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel Secret</label>
                        <input type="text" class="form-control" id="student_line_channel_secret" name="student_line_channel_secret" value="" placeholder="Channel Secret">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Channel Access Token</label>
                <input type="text" class="form-control" id="student_line_access_token" name="student_line_access_token" value="" placeholder="ใส่ Channel Access Token">
            </div>

            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับ</label>
                <textarea class="form-control" id="student_line_welcome_message" name="student_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ">ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับนักเรียน</textarea>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>LINE Official Account สำหรับครู</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ LINE OA</label>
                        <input type="text" class="form-control" id="teacher_line_oa_name" name="teacher_line_oa_name" value="Teacher-Prasat" placeholder="ชื่อ LINE Official Account">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัส LINE OA</label>
                        <input type="text" class="form-control" id="teacher_line_oa_id" name="teacher_line_oa_id" value="@teacher-prasat" placeholder="@ชื่อบัญชี">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel ID</label>
                        <input type="text" class="form-control" id="teacher_line_channel_id" name="teacher_line_channel_id" value="" placeholder="Channel ID">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Channel Secret</label>
                        <input type="text" class="form-control" id="teacher_line_channel_secret" name="teacher_line_channel_secret" value="" placeholder="Channel Secret">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Channel Access Token</label>
                <input type="text" class="form-control" id="teacher_line_access_token" name="teacher_line_access_token" value="" placeholder="ใส่ Channel Access Token">
            </div>

            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับ</label>
                <textarea class="form-control" id="teacher_line_welcome_message" name="teacher_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ">ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับครูที่ปรึกษา</textarea>
            </div>
        </div>

        <div class="settings-section">
            <h3>การตั้งค่า LIFF (LINE Front-end Framework)</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">LIFF ID</label>
                        <input type="text" class="form-control" id="liff_id" name="liff_id" value="2007088707-5EJ0XDlr" placeholder="LIFF ID">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">LIFF Type</label>
                        <select class="form-control" id="liff_type" name="liff_type">
                            <option value="full">Full</option>
                            <option value="tall" selected>Tall</option>
                            <option value="compact">Compact</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">LIFF URL</label>
                <input type="text" class="form-control" id="liff_url" name="liff_url" value="https://8daa-202-29-240-27.ngrok-free.app/line-oa/callback.php" placeholder="LIFF URL">
            </div>
            <div class="form-group">
                <button class="btn btn-primary" id="update-liff">
                    <span class="material-icons">update</span>
                    อัปเดตการตั้งค่า LIFF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- การตั้งค่าการเชื่อมต่อ SMS -->
<div id="sms-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">sms</span>
            การตั้งค่าการเชื่อมต่อ SMS
        </div>
        <div class="settings-section">
            <h3>การตั้งค่า SMS API</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_sms" name="enable_sms">
                    <label for="enable_sms">เปิดใช้งานการส่ง SMS</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ผู้ให้บริการ SMS</label>
                        <select class="form-control" id="sms_provider" name="sms_provider">
                            <option value="thsms">THSMS</option>
                            <option value="thaibulksms">ThaiBulkSMS</option>
                            <option value="twilio">Twilio</option>
                            <option value="custom">อื่นๆ (กำหนดเอง)</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-sms-provider" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ให้บริการ</label>
                        <input type="text" class="form-control" id="custom_sms_provider_name" name="custom_sms_provider_name" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">API Key / Username</label>
                        <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">API Secret / Password</label>
                        <input type="password" class="form-control" id="sms_api_secret" name="sms_api_secret" value="">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">API URL</label>
                <input type="text" class="form-control" id="sms_api_url" name="sms_api_url" value="https://api.thsms.com/api/send">
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตั้งค่าข้อความ SMS</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนตัวอักษรสูงสุดต่อข้อความ</label>
                        <input type="number" class="form-control" id="sms_max_length" name="sms_max_length" min="1" value="160">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ส่ง (Sender ID)</label>
                        <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id" value="PRASAT">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">ข้อความแจ้งเตือนการขาดแถว (สำหรับ SMS)</label>
                <textarea class="form-control" id="sms_absence_template" name="sms_absence_template" rows="3">แจ้งการขาดแถว: นักเรียน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง กรุณาติดต่อครูที่ปรึกษา โทร {advisor_phone}</textarea>
                <small class="form-text text-muted">คุณสามารถใช้ตัวแปร {student_name}, {absent_count}, {advisor_name}, {advisor_phone}, {school_name} ในข้อความได้</small>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตั้งค่าการส่ง SMS</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_use_unicode" name="sms_use_unicode" checked>
                    <label for="sms_use_unicode">ใช้งาน Unicode (รองรับภาษาไทย)</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_delivery_report" name="sms_delivery_report">
                    <label for="sms_delivery_report">เปิดใช้งานรายงานการส่ง</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวน SMS สูงสุดต่อวัน</label>
                        <input type="number" class="form-control" id="sms_daily_limit" name="sms_daily_limit" min="0" value="100">
                        <small class="form-text text-muted">ใส่ 0 สำหรับไม่จำกัด</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาที่อนุญาตให้ส่ง SMS</label>
                        <select class="form-control" id="sms_send_time" name="sms_send_time">
                            <option value="anytime">ตลอดเวลา</option>
                            <option value="office" selected>เวลาทำการ (8:00-17:00)</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row" id="custom-sms-time" style="display: none;">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาเริ่มต้น</label>
                        <input type="time" class="form-control" id="sms_start_time" name="sms_start_time" value="08:00">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาสิ้นสุด</label>
                        <input type="time" class="form-control" id="sms_end_time" name="sms_end_time" value="17:00">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>ทดสอบการส่ง SMS</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทรศัพท์ทดสอบ</label>
                        <input type="text" class="form-control" id="test_phone_number" name="test_phone_number" placeholder="0812345678">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ข้อความทดสอบ</label>
                        <input type="text" class="form-control" id="test_sms_message" name="test_sms_message" value="ทดสอบการส่ง SMS จากระบบน้องชูใจ AI">
                    </div>
                </div>
            </div>
            <div class="form-group mt-3">
                <button class="btn btn-primary" id="send-test-sms">
                    <span class="material-icons">send</span>
                    ส่ง SMS ทดสอบ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- การตั้งค่า Webhook และการตอบกลับอัตโนมัติ -->
<div id="webhook-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">webhook</span>
            การตั้งค่า Webhook และการตอบกลับอัตโนมัติ
        </div>
        <div class="settings-section">
            <h3>การตั้งค่า Webhook</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_webhook" name="enable_webhook" checked>
                    <label for="enable_webhook">เปิดใช้งาน Webhook</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_webhook_url" name="parent_webhook_url" value="https://8daa-202-29-240-27.ngrok-free.app/line-oa/webhook_parent.php">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_webhook_secret" name="parent_webhook_secret" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_webhook_url" name="student_webhook_url" value="https://8daa-202-29-240-27.ngrok-free.app/line-oa/webhook_student.php">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_webhook_secret" name="student_webhook_secret" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_webhook_url" name="teacher_webhook_url" value="https://8daa-202-29-240-27.ngrok-free.app/line-oa/webhook_teacher.php">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_webhook_secret" name="teacher_webhook_secret" value="">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตอบกลับอัตโนมัติ</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_auto_reply" name="enable_auto_reply" checked>
                    <label for="enable_auto_reply">เปิดใช้งานการตอบกลับอัตโนมัติ</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับเมื่อเริ่มติดต่อครั้งแรก</label>
                <textarea class="form-control" id="initial_greeting" name="initial_greeting" rows="3">สวัสดีครับ/ค่ะ ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบสามารถตอบคำถามเกี่ยวกับการเข้าแถวและข้อมูลนักเรียนได้ คุณสามารถสอบถามข้อมูลต่างๆ ได้โดยพิมพ์คำถามหรือเลือกจากเมนูด้านล่าง</textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความสำหรับกรณีไม่เข้าใจคำสั่ง</label>
                <textarea class="form-control" id="fallback_message" name="fallback_message" rows="3">ขออภัยครับ/ค่ะ ระบบไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้งหรือเลือกจากเมนูด้านล่าง</textarea>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>คำสั่งและการตอบกลับ</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="30%">คำสั่ง/คำถาม</th>
                            <th width="60%">การตอบกลับ</th>
                            <th width="10%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody id="commands-container">
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="command_key[]" value="สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ">
                                <small class="form-text text-muted">คั่นคำหลายคำด้วยเครื่องหมาย ,</small>
                            </td>
                            <td>
                                <textarea class="form-control" name="command_reply[]" rows="2">สวัสดีครับ/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ/คะ</textarea>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger remove-command">
                                    <span class="material-icons">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="command_key[]" value="เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว">
                            </td>
                            <td>
                                <textarea class="form-control" name="command_reply[]" rows="2">คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู "ตรวจสอบการเข้าแถว" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล</textarea>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger remove-command">
                                    <span class="material-icons">delete</span>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="command_key[]" value="ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน">
                            </td>
                            <td>
                                <textarea class="form-control" name="command_reply[]" rows="2">คุณสามารถใช้งานระบบได้โดย:
1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน
2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน
3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา
4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ</textarea>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger remove-command">
                                    <span class="material-icons">delete</span>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="form-group mt-3">
                <button class="btn btn-primary" id="add-command">
                    <span class="material-icons">add</span>
                    เพิ่มคำสั่ง
                </button>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตั้งค่า Rich Menu</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_rich_menu" name="enable_rich_menu" checked>
                    <label for="enable_rich_menu">เปิดใช้งาน Rich Menu</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_rich_menu_name" name="parent_rich_menu_name" value="เมนูผู้ปกครอง">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_rich_menu_id" name="parent_rich_menu_id" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_rich_menu_name" name="student_rich_menu_name" value="เมนูนักเรียน">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_rich_menu_id" name="student_rich_menu_id" value="">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_rich_menu_name" name="teacher_rich_menu_name" value="เมนูครู">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_rich_menu_id" name="teacher_rich_menu_id" value="">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <button class="btn btn-primary" id="update-rich-menu">
                    <span class="material-icons">update</span>
                    อัปเดต Rich Menu
                </button>
            </div>
        </div>
    </div>
</div>