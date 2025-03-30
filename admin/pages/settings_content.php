<!-- แท็บการตั้งค่าระบบ -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="system">การตั้งค่าระบบ</div>
        <div class="tab" data-tab="notification">การแจ้งเตือน</div>
        <div class="tab" data-tab="attendance">การเช็คชื่อ</div>
        <div class="tab" data-tab="gps">การตั้งค่า GPS</div>
        <div class="tab" data-tab="line">การเชื่อมต่อ LINE</div>
        <div class="tab" data-tab="sms">การเชื่อมต่อ SMS</div>
        <div class="tab" data-tab="webhook">Webhook และการตอบกลับ</div>
    </div>
</div>

<!-- คอนเทนเนอร์สำหรับแสดงการแจ้งเตือน -->
<div class="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

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
                        <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo isset($settings['system_name']) ? $settings['system_name'] : 'น้องชูใจ AI ดูแลผู้เรียน'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อโรงเรียน</label>
                        <input type="text" class="form-control" id="school_name" name="school_name" value="<?php echo isset($settings['school_name']) ? $settings['school_name'] : 'วิทยาลัยการอาชีพปราสาท'; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัสโรงเรียน</label>
                        <input type="text" class="form-control" id="school_code" name="school_code" value="<?php echo isset($settings['school_code']) ? $settings['school_code'] : '10001'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">รหัสสำหรับการลงทะเบียนแอดมิน</label>
                        <input type="text" class="form-control" id="admin_registration_code" name="admin_registration_code" value="<?php echo isset($settings['admin_registration_code']) ? $settings['admin_registration_code'] : 'ADMIN2025'; ?>">
                        <small class="form-text text-muted">รหัสนี้ใช้สำหรับการลงทะเบียนเป็นแอดมินในครั้งแรกเท่านั้น</small>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ที่อยู่โรงเรียน</label>
                        <input type="text" class="form-control" id="school_address" name="school_address" value="<?php echo isset($settings['school_address']) ? $settings['school_address'] : '123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="school_phone" name="school_phone" value="<?php echo isset($settings['school_phone']) ? $settings['school_phone'] : '044-511234'; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อีเมลโรงเรียน</label>
                        <input type="email" class="form-control" id="school_email" name="school_email" value="<?php echo isset($settings['school_email']) ? $settings['school_email'] : 'prasat_school@example.com'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เว็บไซต์โรงเรียน</label>
                        <input type="text" class="form-control" id="school_website" name="school_website" value="<?php echo isset($settings['school_website']) ? $settings['school_website'] : 'http://www.prasatwittayakom.ac.th'; ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php
        // ดึงข้อมูลปีการศึกษาจากฐานข้อมูล
        $academic_years = [];
        try {
            $stmt = $conn->prepare("SELECT academic_year_id, year, semester, is_active FROM academic_years ORDER BY year DESC, semester DESC");
            $stmt->execute();
            $academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
        }
        
        // หาปีการศึกษาปัจจุบัน
        $current_year = date('Y') + 543; // แปลงเป็นพ.ศ.
        $current_semester = (date('n') >= 5 && date('n') <= 10) ? 1 : 2;
        
        foreach($academic_years as $year) {
            if($year['is_active'] == 1) {
                $current_year = $year['year'];
                $current_semester = $year['semester'];
                $current_academic_year_id = $year['academic_year_id'];
                break;
            }
        }
        ?>

        <div class="settings-section">
            <h3>ปีการศึกษา</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ปีการศึกษาปัจจุบัน</label>
                        <select class="form-control" id="current_academic_year" name="current_academic_year">
                            <?php foreach($academic_years as $year): ?>
                                <option value="<?php echo $year['year']; ?>" <?php echo ($year['year'] == $current_year) ? 'selected' : ''; ?>>
                                    <?php echo $year['year'] . ' (' . ($year['year'] - 543) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ภาคเรียน</label>
                        <select class="form-control" id="current_semester" name="current_semester">
                            <?php foreach($academic_years as $year): ?>
                                <?php if($year['year'] == $current_year): ?>
                                    <option value="<?php echo $year['semester']; ?>" <?php echo ($year['semester'] == $current_semester) ? 'selected' : ''; ?>>
                                        ภาคเรียนที่ <?php echo $year['semester'] . '/' . $year['year']; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <?php
            // ดึงข้อมูลวันเริ่มต้นและสิ้นสุดของภาคเรียนปัจจุบัน
            $semester_start_date = date('Y-m-d');
            $semester_end_date = date('Y-m-d', strtotime('+4 months'));
            
            try {
                if (isset($current_academic_year_id)) {
                    $stmt = $conn->prepare("SELECT start_date, end_date FROM academic_years WHERE academic_year_id = ?");
                    $stmt->bindParam(1, $current_academic_year_id);
                    $stmt->execute();
                    $semester_dates = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($semester_dates) {
                        $semester_start_date = $semester_dates['start_date'];
                        $semester_end_date = $semester_dates['end_date'];
                    }
                }
            } catch(PDOException $e) {
                // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
            }
            ?>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">วันเริ่มต้นภาคเรียน</label>
                        <input type="date" class="form-control" id="semester_start_date" name="semester_start_date" value="<?php echo $semester_start_date; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">วันสิ้นสุดภาคเรียน</label>
                        <input type="date" class="form-control" id="semester_end_date" name="semester_end_date" value="<?php echo $semester_end_date; ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="auto_promote_students" name="auto_promote_students" <?php echo (isset($settings['auto_promote_students']) && $settings['auto_promote_students'] == '1') ? 'checked' : ''; ?>>
                    <label for="auto_promote_students">เลื่อนชั้นนักเรียนอัตโนมัติเมื่อเปลี่ยนปีการศึกษา</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="reset_attendance_new_semester" name="reset_attendance_new_semester" <?php echo (isset($settings['reset_attendance_new_semester']) && $settings['reset_attendance_new_semester'] == '1') ? 'checked' : ''; ?>>
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
                            <option value="th" <?php echo (isset($settings['system_language']) && $settings['system_language'] == 'th') ? 'selected' : ''; ?>>ไทย</option>
                            <option value="en" <?php echo (isset($settings['system_language']) && $settings['system_language'] == 'en') ? 'selected' : ''; ?>>English</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ธีมสี</label>
                        <select class="form-control" id="system_theme" name="system_theme">
                            <option value="green" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'green') ? 'selected' : ''; ?>>เขียว (ค่าเริ่มต้น)</option>
                            <option value="blue" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'blue') ? 'selected' : ''; ?>>น้ำเงิน</option>
                            <option value="red" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'red') ? 'selected' : ''; ?>>แดง</option>
                            <option value="orange" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'orange') ? 'selected' : ''; ?>>ส้ม</option>
                            <option value="purple" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'purple') ? 'selected' : ''; ?>>ม่วง</option>
                            <option value="custom" <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row" id="custom-theme-colors" style="display: <?php echo (isset($settings['system_theme']) && $settings['system_theme'] == 'custom') ? 'flex' : 'none'; ?>;">
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีหลัก</label>
                        <input type="color" class="form-control" id="primary_color" name="primary_color" value="<?php echo isset($settings['primary_color']) ? $settings['primary_color'] : '#28a745'; ?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีรอง</label>
                        <input type="color" class="form-control" id="secondary_color" name="secondary_color" value="<?php echo isset($settings['secondary_color']) ? $settings['secondary_color'] : '#6c757d'; ?>">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label class="form-label">สีพื้นหลัง</label>
                        <input type="color" class="form-control" id="background_color" name="background_color" value="<?php echo isset($settings['background_color']) ? $settings['background_color'] : '#f8f9fa'; ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="dark_mode" name="dark_mode" <?php echo (isset($settings['dark_mode']) && $settings['dark_mode'] == '1') ? 'checked' : ''; ?>>
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
                            <option value="daily" <?php echo (isset($settings['backup_frequency']) && $settings['backup_frequency'] == 'daily') ? 'selected' : ''; ?>>ทุกวัน</option>
                            <option value="weekly" <?php echo (isset($settings['backup_frequency']) && $settings['backup_frequency'] == 'weekly') ? 'selected' : ((isset($settings['backup_frequency'])) ? '' : 'selected'); ?>>ทุกสัปดาห์</option>
                            <option value="monthly" <?php echo (isset($settings['backup_frequency']) && $settings['backup_frequency'] == 'monthly') ? 'selected' : ''; ?>>ทุกเดือน</option>
                            <option value="never" <?php echo (isset($settings['backup_frequency']) && $settings['backup_frequency'] == 'never') ? 'selected' : ''; ?>>ไม่ต้องสำรองอัตโนมัติ</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนการสำรองข้อมูลที่เก็บไว้</label>
                        <select class="form-control" id="backup_keep_count" name="backup_keep_count">
                            <option value="3" <?php echo (isset($settings['backup_keep_count']) && $settings['backup_keep_count'] == '3') ? 'selected' : ''; ?>>3 ชุดล่าสุด</option>
                            <option value="5" <?php echo (isset($settings['backup_keep_count']) && $settings['backup_keep_count'] == '5') ? 'selected' : ((isset($settings['backup_keep_count'])) ? '' : 'selected'); ?>>5 ชุดล่าสุด</option>
                            <option value="10" <?php echo (isset($settings['backup_keep_count']) && $settings['backup_keep_count'] == '10') ? 'selected' : ''; ?>>10 ชุดล่าสุด</option>
                            <option value="0" <?php echo (isset($settings['backup_keep_count']) && $settings['backup_keep_count'] == '0') ? 'selected' : ''; ?>>เก็บทั้งหมด</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">พาธที่เก็บไฟล์สำรองข้อมูล</label>
                        <input type="text" class="form-control" id="backup_path" name="backup_path" value="<?php echo isset($settings['backup_path']) ? $settings['backup_path'] : 'backups/'; ?>">
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
                    <input type="checkbox" id="enable_notifications" name="enable_notifications" <?php echo (isset($settings['enable_notifications']) && $settings['enable_notifications'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_notifications">เปิดใช้งานการแจ้งเตือน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="critical_notifications" name="critical_notifications" <?php echo (isset($settings['critical_notifications']) && $settings['critical_notifications'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="critical_notifications">แจ้งเตือนกรณีฉุกเฉิน</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="send_daily_summary" name="send_daily_summary" <?php echo (isset($settings['send_daily_summary']) && $settings['send_daily_summary'] == '1') ? 'checked' : ''; ?>>
                    <label for="send_daily_summary">ส่งสรุปรายวันให้ผู้ปกครอง</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="send_weekly_summary" name="send_weekly_summary" <?php echo (isset($settings['send_weekly_summary']) && $settings['send_weekly_summary'] == '1') ? 'checked' : 'checked'; ?>>
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
                            <option value="3" <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == '3') ? 'selected' : ''; ?>>3 ครั้ง</option>
                            <option value="5" <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == '5') ? 'selected' : ((isset($settings['absence_threshold'])) ? '' : 'selected'); ?>>5 ครั้ง</option>
                            <option value="10" <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == '10') ? 'selected' : ''; ?>>10 ครั้ง</option>
                            <option value="15" <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == '15') ? 'selected' : ''; ?>>15 ครั้ง</option>
                            <option value="custom" <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-absence-threshold" style="display: <?php echo (isset($settings['absence_threshold']) && $settings['absence_threshold'] == 'custom') ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label class="form-label">กำหนดจำนวนครั้งเอง</label>
                        <input type="number" class="form-control" id="custom_absence_threshold" name="custom_absence_threshold" min="1" value="<?php echo isset($settings['custom_absence_threshold']) ? $settings['custom_absence_threshold'] : '5'; ?>">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ช่วงเวลาแจ้งเตือนสำหรับนักเรียนเสี่ยง</label>
                        <select class="form-control" id="risk_notification_frequency" name="risk_notification_frequency">
                            <option value="daily" <?php echo (isset($settings['risk_notification_frequency']) && $settings['risk_notification_frequency'] == 'daily') ? 'selected' : ''; ?>>ทุกวัน</option>
                            <option value="weekly" <?php echo (isset($settings['risk_notification_frequency']) && $settings['risk_notification_frequency'] == 'weekly') ? 'selected' : ((isset($settings['risk_notification_frequency'])) ? '' : 'selected'); ?>>สัปดาห์ละครั้ง</option>
                            <option value="biweekly" <?php echo (isset($settings['risk_notification_frequency']) && $settings['risk_notification_frequency'] == 'biweekly') ? 'selected' : ''; ?>>2 สัปดาห์ต่อครั้ง</option>
                            <option value="monthly" <?php echo (isset($settings['risk_notification_frequency']) && $settings['risk_notification_frequency'] == 'monthly') ? 'selected' : ''; ?>>เดือนละครั้ง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาที่ส่งการแจ้งเตือน</label>
                        <input type="time" class="form-control" id="notification_time" name="notification_time" value="<?php echo isset($settings['notification_time']) ? $settings['notification_time'] : '16:00'; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="auto_notifications" name="auto_notifications" <?php echo (isset($settings['auto_notifications']) && $settings['auto_notifications'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="auto_notifications">แจ้งเตือนอัตโนมัติสำหรับนักเรียนที่เสี่ยงตกกิจกรรม</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความแจ้งเตือนสำหรับนักเรียนเสี่ยง</label>
                <textarea class="form-control" id="risk_notification_message" name="risk_notification_message" rows="3"><?php echo isset($settings['risk_notification_message']) ? $settings['risk_notification_message'] : 'เรียนผู้ปกครอง บุตรหลานของท่านขาดการเข้าแถวจำนวน {absent_count} ครั้ง ซึ่งมีความเสี่ยงที่จะไม่ผ่านกิจกรรม โปรดติดต่อครูที่ปรึกษา: {advisor_name} โทร: {advisor_phone}'; ?></textarea>
                <small class="form-text text-muted">คุณสามารถใช้ตัวแปร {student_name}, {absent_count}, {advisor_name}, {advisor_phone}, {school_name} ในข้อความได้</small>
            </div>
        </div>

        <div class="settings-section">
            <h3>ช่องทางการแจ้งเตือน</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="line_notification" name="line_notification" <?php echo (isset($settings['line_notification']) && $settings['line_notification'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="line_notification">LINE Official Account</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_notification" name="sms_notification" <?php echo (isset($settings['sms_notification']) && $settings['sms_notification'] == '1') ? 'checked' : ''; ?>>
                    <label for="sms_notification">SMS</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="email_notification" name="email_notification" <?php echo (isset($settings['email_notification']) && $settings['email_notification'] == '1') ? 'checked' : ''; ?>>
                    <label for="email_notification">อีเมล</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="app_notification" name="app_notification" <?php echo (isset($settings['app_notification']) && $settings['app_notification'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="app_notification">การแจ้งเตือนในแอป</label>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การแจ้งเตือนรายบุคคล/รายกลุ่ม</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_bulk_notifications" name="enable_bulk_notifications" <?php echo (isset($settings['enable_bulk_notifications']) && $settings['enable_bulk_notifications'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_bulk_notifications">เปิดใช้งานการแจ้งเตือนแบบกลุ่ม</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">จำนวนผู้รับสูงสุดต่อการส่งแบบกลุ่ม</label>
                <input type="number" class="form-control" id="max_bulk_recipients" name="max_bulk_recipients" min="1" value="<?php echo isset($settings['max_bulk_recipients']) ? $settings['max_bulk_recipients'] : '50'; ?>">
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_scheduled_notifications" name="enable_scheduled_notifications" <?php echo (isset($settings['enable_scheduled_notifications']) && $settings['enable_scheduled_notifications'] == '1') ? 'checked' : ''; ?>>
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
                            <option value="70" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == '70') ? 'selected' : ''; ?>>70%</option>
                            <option value="75" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == '75') ? 'selected' : ''; ?>>75%</option>
                            <option value="80" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == '80') ? 'selected' : ((isset($settings['min_attendance_rate'])) ? '' : 'selected'); ?>>80%</option>
                            <option value="85" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == '85') ? 'selected' : ''; ?>>85%</option>
                            <option value="90" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == '90') ? 'selected' : ''; ?>>90%</option>
                            <option value="custom" <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-attendance-rate" style="display: <?php echo (isset($settings['min_attendance_rate']) && $settings['min_attendance_rate'] == 'custom') ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label class="form-label">กำหนดอัตราเอง (%)</label>
                        <input type="number" class="form-control" id="custom_attendance_rate" name="custom_attendance_rate" min="1" max="100" value="<?php echo isset($settings['custom_attendance_rate']) ? $settings['custom_attendance_rate'] : '80'; ?>">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนวันที่ต้องเข้าแถวเพื่อผ่านกิจกรรม</label>
                        <input type="number" class="form-control" id="required_attendance_days" name="required_attendance_days" min="1" value="<?php echo isset($settings['required_attendance_days']) ? $settings['required_attendance_days'] : '80'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการนับการเข้าแถว</label>
                        <select class="form-control" id="attendance_counting_period" name="attendance_counting_period">
                            <option value="semester" <?php echo (isset($settings['attendance_counting_period']) && $settings['attendance_counting_period'] == 'semester') ? 'selected' : ((isset($settings['attendance_counting_period'])) ? '' : 'selected'); ?>>ภาคเรียน</option>
                            <option value="academic_year" <?php echo (isset($settings['attendance_counting_period']) && $settings['attendance_counting_period'] == 'academic_year') ? 'selected' : ''; ?>>ปีการศึกษา</option>
                            <option value="custom" <?php echo (isset($settings['attendance_counting_period']) && $settings['attendance_counting_period'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="count_weekend" name="count_weekend" <?php echo (isset($settings['count_weekend']) && $settings['count_weekend'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="count_weekend">นับเช็คชื่อในวันหยุดเสาร์-อาทิตย์</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="count_holidays" name="count_holidays" <?php echo (isset($settings['count_holidays']) && $settings['count_holidays'] == '1') ? 'checked' : ''; ?>>
                    <label for="count_holidays">นับเช็คชื่อในวันหยุดนักขัตฤกษ์</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">วันที่ยกเว้นการเช็คชื่อ (ใส่วันที่คั่นด้วยเครื่องหมาย ,)</label>
                <input type="text" class="form-control" id="exemption_dates" name="exemption_dates" value="<?php echo isset($settings['exemption_dates']) ? $settings['exemption_dates'] : '2025-01-01, 2025-04-13, 2025-04-14, 2025-04-15'; ?>">
                <small class="form-text text-muted">รูปแบบวันที่: YYYY-MM-DD (ปี-เดือน-วัน)</small>
            </div>
        </div>

        <div class="settings-section">
            <h3>วิธีการเช็คชื่อ</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_qr" name="enable_qr" <?php echo (isset($settings['enable_qr']) && $settings['enable_qr'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_qr">เปิดใช้งานการเช็คชื่อผ่าน QR Code</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_pin" name="enable_pin" <?php echo (isset($settings['enable_pin']) && $settings['enable_pin'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_pin">เปิดใช้งานการเช็คชื่อผ่านรหัส PIN</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_gps" name="enable_gps" <?php echo (isset($settings['enable_gps']) && $settings['enable_gps'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_gps">เปิดใช้งานการเช็คชื่อผ่าน GPS</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_photo" name="enable_photo" <?php echo (isset($settings['enable_photo']) && $settings['enable_photo'] == '1') ? 'checked' : ''; ?>>
                    <label for="enable_photo">เปิดใช้งานการเช็คชื่อพร้อมรูปถ่าย</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_manual" name="enable_manual" <?php echo (isset($settings['enable_manual']) && $settings['enable_manual'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_manual">เปิดใช้งานการเช็คชื่อแบบแมนนวล (สำหรับครูและแอดมิน)</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อายุของรหัส PIN</label>
                        <select class="form-control" id="pin_expiration" name="pin_expiration">
                            <option value="5" <?php echo (isset($settings['pin_expiration']) && $settings['pin_expiration'] == '5') ? 'selected' : ''; ?>>5 นาที</option>
                            <option value="10" <?php echo (isset($settings['pin_expiration']) && $settings['pin_expiration'] == '10') ? 'selected' : ((isset($settings['pin_expiration'])) ? '' : 'selected'); ?>>10 นาที</option>
                            <option value="15" <?php echo (isset($settings['pin_expiration']) && $settings['pin_expiration'] == '15') ? 'selected' : ''; ?>>15 นาที</option>
                            <option value="30" <?php echo (isset($settings['pin_expiration']) && $settings['pin_expiration'] == '30') ? 'selected' : ''; ?>>30 นาที</option>
                            <option value="60" <?php echo (isset($settings['pin_expiration']) && $settings['pin_expiration'] == '60') ? 'selected' : ''; ?>>1 ชั่วโมง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่สามารถใช้ PIN ได้</label>
                        <select class="form-control" id="pin_usage_limit" name="pin_usage_limit">
                            <option value="1" <?php echo (isset($settings['pin_usage_limit']) && $settings['pin_usage_limit'] == '1') ? 'selected' : ''; ?>>ใช้ได้ครั้งเดียว</option>
                            <option value="3" <?php echo (isset($settings['pin_usage_limit']) && $settings['pin_usage_limit'] == '3') ? 'selected' : ((isset($settings['pin_usage_limit'])) ? '' : 'selected'); ?>>ใช้ได้ 3 ครั้ง</option>
                            <option value="5" <?php echo (isset($settings['pin_usage_limit']) && $settings['pin_usage_limit'] == '5') ? 'selected' : ''; ?>>ใช้ได้ 5 ครั้ง</option>
                            <option value="10" <?php echo (isset($settings['pin_usage_limit']) && $settings['pin_usage_limit'] == '10') ? 'selected' : ''; ?>>ใช้ได้ 10 ครั้ง</option>
                            <option value="0" <?php echo (isset($settings['pin_usage_limit']) && $settings['pin_usage_limit'] == '0') ? 'selected' : ''; ?>>ใช้ได้ไม่จำกัด</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความยาวของรหัส PIN</label>
                        <select class="form-control" id="pin_length" name="pin_length">
                            <option value="4" <?php echo (isset($settings['pin_length']) && $settings['pin_length'] == '4') ? 'selected' : ((isset($settings['pin_length'])) ? '' : 'selected'); ?>>4 หลัก</option>
                            <option value="6" <?php echo (isset($settings['pin_length']) && $settings['pin_length'] == '6') ? 'selected' : ''; ?>>6 หลัก</option>
                            <option value="8" <?php echo (isset($settings['pin_length']) && $settings['pin_length'] == '8') ? 'selected' : ''; ?>>8 หลัก</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ประเภทของรหัส PIN</label>
                        <select class="form-control" id="pin_type" name="pin_type">
                            <option value="numeric" <?php echo (isset($settings['pin_type']) && $settings['pin_type'] == 'numeric') ? 'selected' : ((isset($settings['pin_type'])) ? '' : 'selected'); ?>>ตัวเลขเท่านั้น</option>
                            <option value="alphanumeric" <?php echo (isset($settings['pin_type']) && $settings['pin_type'] == 'alphanumeric') ? 'selected' : ''; ?>>ตัวอักษรและตัวเลข</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">อายุของ QR Code</label>
                        <select class="form-control" id="qr_expiration" name="qr_expiration">
                            <option value="1" <?php echo (isset($settings['qr_expiration']) && $settings['qr_expiration'] == '1') ? 'selected' : ''; ?>>1 นาที</option>
                            <option value="5" <?php echo (isset($settings['qr_expiration']) && $settings['qr_expiration'] == '5') ? 'selected' : ((isset($settings['qr_expiration'])) ? '' : 'selected'); ?>>5 นาที</option>
                            <option value="10" <?php echo (isset($settings['qr_expiration']) && $settings['qr_expiration'] == '10') ? 'selected' : ''; ?>>10 นาที</option>
                            <option value="30" <?php echo (isset($settings['qr_expiration']) && $settings['qr_expiration'] == '30') ? 'selected' : ''; ?>>30 นาที</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนครั้งที่สามารถใช้ QR Code ได้</label>
                        <select class="form-control" id="qr_usage_limit" name="qr_usage_limit">
                            <option value="1" <?php echo (isset($settings['qr_usage_limit']) && $settings['qr_usage_limit'] == '1') ? 'selected' : ((isset($settings['qr_usage_limit'])) ? '' : 'selected'); ?>>ใช้ได้ครั้งเดียว</option>
                            <option value="3" <?php echo (isset($settings['qr_usage_limit']) && $settings['qr_usage_limit'] == '3') ? 'selected' : ''; ?>>ใช้ได้ 3 ครั้ง</option>
                            <option value="0" <?php echo (isset($settings['qr_usage_limit']) && $settings['qr_usage_limit'] == '0') ? 'selected' : ''; ?>>ใช้ได้ไม่จำกัด</option>
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
                        <input type="time" class="form-control" id="attendance_start_time" name="attendance_start_time" value="<?php echo isset($settings['attendance_start_time']) ? $settings['attendance_start_time'] : '07:30'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาสิ้นสุดการเช็คชื่อ</label>
                        <input type="time" class="form-control" id="attendance_end_time" name="attendance_end_time" value="<?php echo isset($settings['attendance_end_time']) ? $settings['attendance_end_time'] : '08:20'; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="late_check" name="late_check" <?php echo (isset($settings['late_check']) && $settings['late_check'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="late_check">อนุญาตให้เช็คชื่อล่าช้าได้</label>
                </div>
            </div>

            <div class="row" id="late-check-options" style="display: <?php echo (isset($settings['late_check']) && $settings['late_check'] == '0') ? 'none' : 'flex'; ?>;">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาการเช็คชื่อล่าช้า</label>
                        <select class="form-control" id="late_check_duration" name="late_check_duration">
                            <option value="15" <?php echo (isset($settings['late_check_duration']) && $settings['late_check_duration'] == '15') ? 'selected' : ''; ?>>15 นาที</option>
                            <option value="30" <?php echo (isset($settings['late_check_duration']) && $settings['late_check_duration'] == '30') ? 'selected' : ((isset($settings['late_check_duration'])) ? '' : 'selected'); ?>>30 นาที</option>
                            <option value="45" <?php echo (isset($settings['late_check_duration']) && $settings['late_check_duration'] == '45') ? 'selected' : ''; ?>>45 นาที</option>
                            <option value="60" <?php echo (isset($settings['late_check_duration']) && $settings['late_check_duration'] == '60') ? 'selected' : ''; ?>>1 ชั่วโมง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">การบันทึกสถานะการมา</label>
                        <select class="form-control" id="late_check_status" name="late_check_status">
                            <option value="late" <?php echo (isset($settings['late_check_status']) && $settings['late_check_status'] == 'late') ? 'selected' : ''; ?>>มาสาย</option>
                            <option value="deduct_score" <?php echo (isset($settings['late_check_status']) && $settings['late_check_status'] == 'deduct_score') ? 'selected' : ((isset($settings['late_check_status'])) ? '' : 'selected'); ?>>ลดคะแนนความประพฤติ</option>
                            <option value="no_penalty" <?php echo (isset($settings['late_check_status']) && $settings['late_check_status'] == 'no_penalty') ? 'selected' : ''; ?>>ไม่มีผลใดๆ</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนคะแนนที่หักเมื่อมาสาย</label>
                        <input type="number" class="form-control" id="late_deduct_points" name="late_deduct_points" min="0" value="<?php echo isset($settings['late_deduct_points']) ? $settings['late_deduct_points'] : '1'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนคะแนนที่หักเมื่อขาด</label>
                        <input type="number" class="form-control" id="absent_deduct_points" name="absent_deduct_points" min="0" value="<?php echo isset($settings['absent_deduct_points']) ? $settings['absent_deduct_points'] : '3'; ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การอัพโหลดรูปภาพการเข้าแถว</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="require_attendance_photo" name="require_attendance_photo" <?php echo (isset($settings['require_attendance_photo']) && $settings['require_attendance_photo'] == '1') ? 'checked' : ''; ?>>
                    <label for="require_attendance_photo">บังคับให้มีรูปถ่ายประกอบการเช็คชื่อ</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ขนาดไฟล์รูปสูงสุด (KB)</label>
                        <input type="number" class="form-control" id="max_photo_size" name="max_photo_size" min="100" value="<?php echo isset($settings['max_photo_size']) ? $settings['max_photo_size'] : '5000'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ประเภทไฟล์ที่อนุญาต</label>
                        <input type="text" class="form-control" id="allowed_photo_types" name="allowed_photo_types" value="<?php echo isset($settings['allowed_photo_types']) ? $settings['allowed_photo_types'] : 'jpg,jpeg,png'; ?>">
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
                        <input type="text" class="form-control" id="school_latitude" name="school_latitude" value="<?php echo isset($settings['school_latitude']) ? $settings['school_latitude'] : '14.9523'; ?>" placeholder="กรอกค่าละติจูด">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ลองจิจูด</label>
                        <input type="text" class="form-control" id="school_longitude" name="school_longitude" value="<?php echo isset($settings['school_longitude']) ? $settings['school_longitude'] : '103.4919'; ?>" placeholder="กรอกค่าลองจิจูด">
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
            <div id="map-container" class="mt-3" style="display: none; height: 400px; border-radius: 8px; overflow: hidden;">
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
                            <option value="50" <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == '50') ? 'selected' : ''; ?>>50 เมตร</option>
                            <option value="100" <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == '100') ? 'selected' : ((isset($settings['gps_radius'])) ? '' : 'selected'); ?>>100 เมตร</option>
                            <option value="200" <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == '200') ? 'selected' : ''; ?>>200 เมตร</option>
                            <option value="500" <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == '500') ? 'selected' : ''; ?>>500 เมตร</option>
                            <option value="custom" <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-gps-radius" style="display: <?php echo (isset($settings['gps_radius']) && $settings['gps_radius'] == 'custom') ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label class="form-label">กำหนดรัศมีเอง (เมตร)</label>
                        <input type="number" class="form-control" id="custom_gps_radius" name="custom_gps_radius" min="10" value="<?php echo isset($settings['custom_gps_radius']) ? $settings['custom_gps_radius'] : '100'; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ความแม่นยำตำแหน่ง</label>
                        <select class="form-control" id="gps_accuracy" name="gps_accuracy">
                            <option value="5" <?php echo (isset($settings['gps_accuracy']) && $settings['gps_accuracy'] == '5') ? 'selected' : ''; ?>>±5 เมตร</option>
                            <option value="10" <?php echo (isset($settings['gps_accuracy']) && $settings['gps_accuracy'] == '10') ? 'selected' : ((isset($settings['gps_accuracy'])) ? '' : 'selected'); ?>>±10 เมตร</option>
                            <option value="20" <?php echo (isset($settings['gps_accuracy']) && $settings['gps_accuracy'] == '20') ? 'selected' : ''; ?>>±20 เมตร</option>
                            <option value="50" <?php echo (isset($settings['gps_accuracy']) && $settings['gps_accuracy'] == '50') ? 'selected' : ''; ?>>±50 เมตร</option>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ระยะเวลาในการตรวจสอบตำแหน่ง (วินาที)</label>
                        <input type="number" class="form-control" id="gps_check_interval" name="gps_check_interval" min="1" value="<?php echo isset($settings['gps_check_interval']) ? $settings['gps_check_interval'] : '5'; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_required" name="gps_required" <?php echo (isset($settings['gps_required']) && $settings['gps_required'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="gps_required">บังคับใช้การยืนยันตำแหน่ง GPS</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_photo_required" name="gps_photo_required" <?php echo (isset($settings['gps_photo_required']) && $settings['gps_photo_required'] == '1') ? 'checked' : ''; ?>>
                    <label for="gps_photo_required">ถ่ายรูปประกอบการเช็คชื่อด้วย GPS</label>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="gps_mock_detection" name="gps_mock_detection" <?php echo (isset($settings['gps_mock_detection']) && $settings['gps_mock_detection'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="gps_mock_detection">ตรวจจับการปลอมแปลงตำแหน่ง GPS</label>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การอนุญาตเช็คชื่อจากตำแหน่งอื่น</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow_home_check" name="allow_home_check" <?php echo (isset($settings['allow_home_check']) && $settings['allow_home_check'] == '1') ? 'checked' : ''; ?>>
                    <label for="allow_home_check">อนุญาตให้เช็คชื่อจากที่บ้าน</label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="allow_parent_verification" name="allow_parent_verification" <?php echo (isset($settings['allow_parent_verification']) && $settings['allow_parent_verification'] == '1') ? 'checked' : ''; ?>>
                    <label for="allow_parent_verification">ให้ผู้ปกครองยืนยันตำแหน่ง</label>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label class="form-label">เหตุผลที่อนุญาตให้เช็คชื่อจากที่บ้าน (คั่นด้วยเครื่องหมาย ,)</label>
                        <input type="text" class="form-control" id="home_check_reasons" name="home_check_reasons" value="<?php echo isset($settings['home_check_reasons']) ? $settings['home_check_reasons'] : 'เจ็บป่วย, โควิด, อุบัติเหตุ, ไปราชการ'; ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>จุดเช็คชื่อเพิ่มเติม</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_multiple_locations" name="enable_multiple_locations" <?php echo (isset($settings['enable_multiple_locations']) && $settings['enable_multiple_locations'] == '1') ? 'checked' : ''; ?>>
                    <label for="enable_multiple_locations">อนุญาตให้มีจุดเช็คชื่อหลายจุด</label>
                </div>
            </div>
            
            <div id="additional-locations" class="mt-3" style="display: <?php echo (isset($settings['enable_multiple_locations']) && $settings['enable_multiple_locations'] == '1') ? 'block' : 'none'; ?>;">
                <?php
                // ดึงข้อมูลตำแหน่งเพิ่มเติมจากฐานข้อมูล
                $additional_locations = [];
                try {
                    $stmt = $conn->prepare("SELECT id, name, radius, latitude, longitude FROM additional_locations ORDER BY id");
                    $stmt->execute();
                    $additional_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch(PDOException $e) {
                    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล หรือยังไม่มีตาราง
                }
                
                // หากไม่มีตำแหน่งเพิ่มเติม ให้สร้างตำแหน่งเริ่มต้น 1 ตำแหน่ง
                if (empty($additional_locations)) {
                    $additional_locations = [[
                        'name' => 'สนามกีฬา',
                        'radius' => 100,
                        'latitude' => isset($settings['school_latitude']) ? $settings['school_latitude'] : '14.9523',
                        'longitude' => isset($settings['school_longitude']) ? $settings['school_longitude'] : '103.4919'
                    ]];
                }
                
                // แสดงตำแหน่งเพิ่มเติม
                foreach ($additional_locations as $location) {
                ?>
                <div class="additional-location-item mb-3 p-3 border rounded">
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ชื่อสถานที่</label>
                                <input type="text" class="form-control" name="location_name[]" value="<?php echo $location['name']; ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">รัศมี (เมตร)</label>
                                <input type="number" class="form-control" name="location_radius[]" value="<?php echo $location['radius']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ละติจูด</label>
                                <input type="text" class="form-control" name="location_latitude[]" value="<?php echo $location['latitude']; ?>">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">ลองจิจูด</label>
                                <input type="text" class="form-control" name="location_longitude[]" value="<?php echo $location['longitude']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button class="btn btn-sm btn-secondary pick-location">
                                <span class="material-icons">map</span>
                                เลือกตำแหน่งจากแผนที่
                            </button>
                            <button class="btn btn-sm btn-danger remove-location">
                                <span class="material-icons">delete</span>
                                ลบสถานที่
                            </button>
                        </div>
                    </div>
                </div>
                <?php } ?>
                
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
            <h3>การตั้งค่า LINE Official Account</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="single_line_oa" name="single_line_oa" <?php echo (isset($settings['single_line_oa']) && $settings['single_line_oa'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="single_line_oa">ใช้ LINE OA เดียวสำหรับทุกบทบาท (นักเรียน, ครู, ผู้ปกครอง)</label>
                    <small class="form-text text-muted">การใช้ LINE OA เดียวจะช่วยลดค่าใช้จ่ายและความซับซ้อนในการจัดการ</small>
                </div>
            </div>
            
            <!-- กรณีใช้ LINE OA เดียว -->
            <div id="single-oa-section" style="display: <?php echo (!isset($settings['single_line_oa']) || $settings['single_line_oa'] == '1') ? 'block' : 'none'; ?>;">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ชื่อ LINE OA</label>
                            <input type="text" class="form-control" id="line_oa_name" name="line_oa_name" value="<?php echo isset($settings['line_oa_name']) ? $settings['line_oa_name'] : 'น้องชูใจ AI'; ?>" placeholder="ชื่อ LINE Official Account">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">รหัส LINE OA</label>
                            <input type="text" class="form-control" id="line_oa_id" name="line_oa_id" value="<?php echo isset($settings['line_oa_id']) ? $settings['line_oa_id'] : '@chujai-ai'; ?>" placeholder="@ชื่อบัญชี">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel ID</label>
                            <input type="text" class="form-control" id="line_channel_id" name="line_channel_id" value="<?php echo isset($settings['line_channel_id']) ? $settings['line_channel_id'] : '2007088707'; ?>" placeholder="Channel ID">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel Secret</label>
                            <input type="text" class="form-control" id="line_channel_secret" name="line_channel_secret" value="<?php echo isset($settings['line_channel_secret']) ? $settings['line_channel_secret'] : 'ebd6dffa14e54908a835c59c3bd3a7cf'; ?>" placeholder="Channel Secret">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Channel Access Token</label>
                    <input type="text" class="form-control" id="line_access_token" name="line_access_token" value="<?php echo isset($settings['line_access_token']) ? $settings['line_access_token'] : ''; ?>" placeholder="ใส่ Channel Access Token">
                </div>

                <div class="form-group">
                    <label class="form-label">ข้อความต้อนรับ</label>
                    <textarea class="form-control" id="line_welcome_message" name="line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ"><?php echo isset($settings['line_welcome_message']) ? $settings['line_welcome_message'] : 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน กรุณาเลือกบทบาทของคุณ (นักเรียน/ครู/ผู้ปกครอง)'; ?></textarea>
                </div>
            </div>
            
            <!-- กรณีใช้หลาย LINE OA -->
            <div id="multiple-oa-section" style="display: <?php echo (isset($settings['single_line_oa']) && $settings['single_line_oa'] == '0') ? 'block' : 'none'; ?>;">
                <div class="alert alert-warning">
                    <span class="material-icons">warning</span>
                    <strong>คำเตือน:</strong> การใช้หลาย LINE OA อาจเพิ่มค่าใช้จ่ายและความซับซ้อนในการจัดการ
                </div>
                
                <h4 class="mt-4">LINE Official Account สำหรับผู้ปกครอง</h4>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ชื่อ LINE OA</label>
                            <input type="text" class="form-control" id="parent_line_oa_name" name="parent_line_oa_name" value="<?php echo isset($settings['parent_line_oa_name']) ? $settings['parent_line_oa_name'] : 'SADD-Prasat'; ?>" placeholder="ชื่อ LINE Official Account">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">รหัส LINE OA</label>
                            <input type="text" class="form-control" id="parent_line_oa_id" name="parent_line_oa_id" value="<?php echo isset($settings['parent_line_oa_id']) ? $settings['parent_line_oa_id'] : '@sadd-prasat'; ?>" placeholder="@ชื่อบัญชี">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel ID</label>
                            <input type="text" class="form-control" id="parent_line_channel_id" name="parent_line_channel_id" value="<?php echo isset($settings['parent_line_channel_id']) ? $settings['parent_line_channel_id'] : '2007088707'; ?>" placeholder="Channel ID">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel Secret</label>
                            <input type="text" class="form-control" id="parent_line_channel_secret" name="parent_line_channel_secret" value="<?php echo isset($settings['parent_line_channel_secret']) ? $settings['parent_line_channel_secret'] : 'ebd6dffa14e54908a835c59c3bd3a7cf'; ?>" placeholder="Channel Secret">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Channel Access Token</label>
                    <input type="text" class="form-control" id="parent_line_access_token" name="parent_line_access_token" value="<?php echo isset($settings['parent_line_access_token']) ? $settings['parent_line_access_token'] : ''; ?>" placeholder="ใส่ Channel Access Token">
                </div>

                <div class="form-group">
                    <label class="form-label">ข้อความต้อนรับ</label>
                    <textarea class="form-control" id="parent_line_welcome_message" name="parent_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ"><?php echo isset($settings['parent_line_welcome_message']) ? $settings['parent_line_welcome_message'] : 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวและแจ้งเตือนสำหรับผู้ปกครอง'; ?></textarea>
                </div>
                
                <h4 class="mt-4">LINE Official Account สำหรับนักเรียน</h4>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ชื่อ LINE OA</label>
                            <input type="text" class="form-control" id="student_line_oa_name" name="student_line_oa_name" value="<?php echo isset($settings['student_line_oa_name']) ? $settings['student_line_oa_name'] : 'STD-Prasat'; ?>" placeholder="ชื่อ LINE Official Account">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">รหัส LINE OA</label>
                            <input type="text" class="form-control" id="student_line_oa_id" name="student_line_oa_id" value="<?php echo isset($settings['student_line_oa_id']) ? $settings['student_line_oa_id'] : '@std-prasat'; ?>" placeholder="@ชื่อบัญชี">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel ID</label>
                            <input type="text" class="form-control" id="student_line_channel_id" name="student_line_channel_id" value="<?php echo isset($settings['student_line_channel_id']) ? $settings['student_line_channel_id'] : ''; ?>" placeholder="Channel ID">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel Secret</label>
                            <input type="text" class="form-control" id="student_line_channel_secret" name="student_line_channel_secret" value="<?php echo isset($settings['student_line_channel_secret']) ? $settings['student_line_channel_secret'] : ''; ?>" placeholder="Channel Secret">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Channel Access Token</label>
                    <input type="text" class="form-control" id="student_line_access_token" name="student_line_access_token" value="<?php echo isset($settings['student_line_access_token']) ? $settings['student_line_access_token'] : ''; ?>" placeholder="ใส่ Channel Access Token">
                </div>

                <div class="form-group">
                    <label class="form-label">ข้อความต้อนรับ</label>
                    <textarea class="form-control" id="student_line_welcome_message" name="student_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ"><?php echo isset($settings['student_line_welcome_message']) ? $settings['student_line_welcome_message'] : 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับนักเรียน'; ?></textarea>
                </div>
                
                <h4 class="mt-4">LINE Official Account สำหรับครู</h4>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">ชื่อ LINE OA</label>
                            <input type="text" class="form-control" id="teacher_line_oa_name" name="teacher_line_oa_name" value="<?php echo isset($settings['teacher_line_oa_name']) ? $settings['teacher_line_oa_name'] : 'Teacher-Prasat'; ?>" placeholder="ชื่อ LINE Official Account">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">รหัส LINE OA</label>
                            <input type="text" class="form-control" id="teacher_line_oa_id" name="teacher_line_oa_id" value="<?php echo isset($settings['teacher_line_oa_id']) ? $settings['teacher_line_oa_id'] : '@teacher-prasat'; ?>" placeholder="@ชื่อบัญชี">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel ID</label>
                            <input type="text" class="form-control" id="teacher_line_channel_id" name="teacher_line_channel_id" value="<?php echo isset($settings['teacher_line_channel_id']) ? $settings['teacher_line_channel_id'] : ''; ?>" placeholder="Channel ID">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Channel Secret</label>
                            <input type="text" class="form-control" id="teacher_line_channel_secret" name="teacher_line_channel_secret" value="<?php echo isset($settings['teacher_line_channel_secret']) ? $settings['teacher_line_channel_secret'] : ''; ?>" placeholder="Channel Secret">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Channel Access Token</label>
                    <input type="text" class="form-control" id="teacher_line_access_token" name="teacher_line_access_token" value="<?php echo isset($settings['teacher_line_access_token']) ? $settings['teacher_line_access_token'] : ''; ?>" placeholder="ใส่ Channel Access Token">
                </div>

                <div class="form-group">
                    <label class="form-label">ข้อความต้อนรับ</label>
                    <textarea class="form-control" id="teacher_line_welcome_message" name="teacher_line_welcome_message" rows="3" placeholder="ข้อความต้อนรับเมื่อผู้ใช้เริ่มติดต่อ"><?php echo isset($settings['teacher_line_welcome_message']) ? $settings['teacher_line_welcome_message'] : 'ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบติดตามการเข้าแถวสำหรับครูที่ปรึกษา'; ?></textarea>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>การตั้งค่า LIFF (LINE Front-end Framework)</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">LIFF ID</label>
                        <input type="text" class="form-control" id="liff_id" name="liff_id" value="<?php echo isset($settings['liff_id']) ? $settings['liff_id'] : '2007088707-5EJ0XDlr'; ?>" placeholder="LIFF ID">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">LIFF Type</label>
                        <select class="form-control" id="liff_type" name="liff_type">
                            <option value="full" <?php echo (isset($settings['liff_type']) && $settings['liff_type'] == 'full') ? 'selected' : ''; ?>>Full</option>
                            <option value="tall" <?php echo (isset($settings['liff_type']) && $settings['liff_type'] == 'tall') ? 'selected' : ((isset($settings['liff_type'])) ? '' : 'selected'); ?>>Tall</option>
                            <option value="compact" <?php echo (isset($settings['liff_type']) && $settings['liff_type'] == 'compact') ? 'selected' : ''; ?>>Compact</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">LIFF URL</label>
                <input type="text" class="form-control" id="liff_url" name="liff_url" value="<?php echo isset($settings['liff_url']) ? $settings['liff_url'] : 'https://8daa-202-29-240-27.ngrok-free.app/line-oa/callback.php'; ?>" placeholder="LIFF URL">
                <small class="form-text text-muted">URL ที่เรียกเมื่อผู้ใช้เปิด LIFF จาก LINE</small>
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
                    <input type="checkbox" id="enable_sms" name="enable_sms" <?php echo (isset($settings['enable_sms']) && $settings['enable_sms'] == '1') ? 'checked' : ''; ?>>
                    <label for="enable_sms">เปิดใช้งานการส่ง SMS</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ผู้ให้บริการ SMS</label>
                        <select class="form-control" id="sms_provider" name="sms_provider">
                            <option value="thsms" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'thsms') ? 'selected' : ((isset($settings['sms_provider'])) ? '' : 'selected'); ?>>THSMS</option>
                            <option value="thaibulksms" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'thaibulksms') ? 'selected' : ''; ?>>ThaiBulkSMS</option>
                            <option value="twilio" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'twilio') ? 'selected' : ''; ?>>Twilio</option>
                            <option value="custom" <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'custom') ? 'selected' : ''; ?>>อื่นๆ (กำหนดเอง)</option>
                        </select>
                    </div>
                </div>
                <div class="col-6" id="custom-sms-provider" style="display: <?php echo (isset($settings['sms_provider']) && $settings['sms_provider'] == 'custom') ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ให้บริการ</label>
                        <input type="text" class="form-control" id="custom_sms_provider_name" name="custom_sms_provider_name" value="<?php echo isset($settings['custom_sms_provider_name']) ? $settings['custom_sms_provider_name'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">API Key / Username</label>
                        <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="<?php echo isset($settings['sms_api_key']) ? $settings['sms_api_key'] : ''; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">API Secret / Password</label>
                        <input type="password" class="form-control" id="sms_api_secret" name="sms_api_secret" value="<?php echo isset($settings['sms_api_secret']) ? $settings['sms_api_secret'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">API URL</label>
                <input type="text" class="form-control" id="sms_api_url" name="sms_api_url" value="<?php echo isset($settings['sms_api_url']) ? $settings['sms_api_url'] : 'https://api.thsms.com/api/send'; ?>">
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตั้งค่าข้อความ SMS</h3>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวนตัวอักษรสูงสุดต่อข้อความ</label>
                        <input type="number" class="form-control" id="sms_max_length" name="sms_max_length" min="1" value="<?php echo isset($settings['sms_max_length']) ? $settings['sms_max_length'] : '160'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อผู้ส่ง (Sender ID)</label>
                        <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id" value="<?php echo isset($settings['sms_sender_id']) ? $settings['sms_sender_id'] : 'PRASAT'; ?>">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">ข้อความแจ้งเตือนการขาดแถว (สำหรับ SMS)</label>
                <textarea class="form-control" id="sms_absence_template" name="sms_absence_template" rows="3"><?php echo isset($settings['sms_absence_template']) ? $settings['sms_absence_template'] : 'แจ้งการขาดแถว: นักเรียน {student_name} ขาดการเข้าแถวจำนวน {absent_count} ครั้ง กรุณาติดต่อครูที่ปรึกษา โทร {advisor_phone}'; ?></textarea>
                <small class="form-text text-muted">คุณสามารถใช้ตัวแปร {student_name}, {absent_count}, {advisor_name}, {advisor_phone}, {school_name} ในข้อความได้</small>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>การตั้งค่าการส่ง SMS</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_use_unicode" name="sms_use_unicode" <?php echo (isset($settings['sms_use_unicode']) && $settings['sms_use_unicode'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="sms_use_unicode">ใช้งาน Unicode (รองรับภาษาไทย)</label>
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="sms_delivery_report" name="sms_delivery_report" <?php echo (isset($settings['sms_delivery_report']) && $settings['sms_delivery_report'] == '1') ? 'checked' : ''; ?>>
                    <label for="sms_delivery_report">เปิดใช้งานรายงานการส่ง</label>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">จำนวน SMS สูงสุดต่อวัน</label>
                        <input type="number" class="form-control" id="sms_daily_limit" name="sms_daily_limit" min="0" value="<?php echo isset($settings['sms_daily_limit']) ? $settings['sms_daily_limit'] : '100'; ?>">
                        <small class="form-text text-muted">ใส่ 0 สำหรับไม่จำกัด</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาที่อนุญาตให้ส่ง SMS</label>
                        <select class="form-control" id="sms_send_time" name="sms_send_time">
                            <option value="anytime" <?php echo (isset($settings['sms_send_time']) && $settings['sms_send_time'] == 'anytime') ? 'selected' : ''; ?>>ตลอดเวลา</option>
                            <option value="office" <?php echo (isset($settings['sms_send_time']) && $settings['sms_send_time'] == 'office') ? 'selected' : ((isset($settings['sms_send_time'])) ? '' : 'selected'); ?>>เวลาทำการ (8:00-17:00)</option>
                            <option value="custom" <?php echo (isset($settings['sms_send_time']) && $settings['sms_send_time'] == 'custom') ? 'selected' : ''; ?>>กำหนดเอง</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row" id="custom-sms-time" style="display: <?php echo (isset($settings['sms_send_time']) && $settings['sms_send_time'] == 'custom') ? 'flex' : 'none'; ?>;">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาเริ่มต้น</label>
                        <input type="time" class="form-control" id="sms_start_time" name="sms_start_time" value="<?php echo isset($settings['sms_start_time']) ? $settings['sms_start_time'] : '08:00'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">เวลาสิ้นสุด</label>
                        <input type="time" class="form-control" id="sms_end_time" name="sms_end_time" value="<?php echo isset($settings['sms_end_time']) ? $settings['sms_end_time'] : '17:00'; ?>">
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
                    <input type="checkbox" id="enable_webhook" name="enable_webhook" <?php echo (isset($settings['enable_webhook']) && $settings['enable_webhook'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_webhook">เปิดใช้งาน Webhook</label>
                </div>
            </div>
            
            <?php if (!isset($settings['single_line_oa']) || $settings['single_line_oa'] == '1'): ?>
            <!-- กรณีใช้ LINE OA เดียว -->
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL</label>
                        <input type="text" class="form-control" id="webhook_url" name="webhook_url" value="<?php echo isset($settings['webhook_url']) ? $settings['webhook_url'] : 'https://your-domain.com/line-oa/webhook.php'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key</label>
                        <input type="text" class="form-control" id="webhook_secret" name="webhook_secret" value="<?php echo isset($settings['webhook_secret']) ? $settings['webhook_secret'] : ''; ?>">
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- กรณีใช้หลาย LINE OA -->
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_webhook_url" name="parent_webhook_url" value="<?php echo isset($settings['parent_webhook_url']) ? $settings['parent_webhook_url'] : 'https://your-domain.com/line-oa/webhook_parent.php'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_webhook_secret" name="parent_webhook_secret" value="<?php echo isset($settings['parent_webhook_secret']) ? $settings['parent_webhook_secret'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_webhook_url" name="student_webhook_url" value="<?php echo isset($settings['student_webhook_url']) ? $settings['student_webhook_url'] : 'https://your-domain.com/line-oa/webhook_student.php'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_webhook_secret" name="student_webhook_secret" value="<?php echo isset($settings['student_webhook_secret']) ? $settings['student_webhook_secret'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Webhook URL สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_webhook_url" name="teacher_webhook_url" value="<?php echo isset($settings['teacher_webhook_url']) ? $settings['teacher_webhook_url'] : 'https://your-domain.com/line-oa/webhook_teacher.php'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Secret Key สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_webhook_secret" name="teacher_webhook_secret" value="<?php echo isset($settings['teacher_webhook_secret']) ? $settings['teacher_webhook_secret'] : ''; ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="settings-section">
            <h3>การตอบกลับอัตโนมัติ</h3>
            <div class="form-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="enable_auto_reply" name="enable_auto_reply" <?php echo (isset($settings['enable_auto_reply']) && $settings['enable_auto_reply'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_auto_reply">เปิดใช้งานการตอบกลับอัตโนมัติ</label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความต้อนรับเมื่อเริ่มติดต่อครั้งแรก</label>
                <textarea class="form-control" id="initial_greeting" name="initial_greeting" rows="3"><?php echo isset($settings['initial_greeting']) ? $settings['initial_greeting'] : 'สวัสดีครับ/ค่ะ ยินดีต้อนรับสู่ระบบน้องชูใจ AI ดูแลผู้เรียน ระบบสามารถตอบคำถามเกี่ยวกับการเข้าแถวและข้อมูลนักเรียนได้ คุณสามารถสอบถามข้อมูลต่างๆ ได้โดยพิมพ์คำถามหรือเลือกจากเมนูด้านล่าง'; ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">ข้อความสำหรับกรณีไม่เข้าใจคำสั่ง</label>
                <textarea class="form-control" id="fallback_message" name="fallback_message" rows="3"><?php echo isset($settings['fallback_message']) ? $settings['fallback_message'] : 'ขออภัยครับ/ค่ะ ระบบไม่เข้าใจคำสั่ง โปรดลองใหม่อีกครั้งหรือเลือกจากเมนูด้านล่าง'; ?></textarea>
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
                        <?php
                        // ดึงข้อมูลคำสั่งและการตอบกลับจากฐานข้อมูล
                        $commands = [];
                        try {
                            $stmt = $conn->prepare("SELECT command_key, command_reply FROM bot_commands ORDER BY id");
                            $stmt->execute();
                            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch(PDOException $e) {
                            // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล หรือยังไม่มีตาราง
                        }
                        
                        // หากไม่มีคำสั่ง ให้สร้างคำสั่งเริ่มต้น
                        if (empty($commands)) {
                            $commands = [
                                [
                                    'command_key' => 'สวัสดี,hi,hello,สวัสดีครับ,สวัสดีค่ะ',
                                    'command_reply' => 'สวัสดีครับ/ค่ะ มีอะไรให้ช่วยเหลือไหมครับ/คะ'
                                ],
                                [
                                    'command_key' => 'เช็คชื่อ,ดูการเข้าแถว,ตรวจสอบการเข้าแถว',
                                    'command_reply' => 'คุณสามารถตรวจสอบข้อมูลการเข้าแถวได้ที่เมนู "ตรวจสอบการเข้าแถว" ด้านล่าง หรือพิมพ์รหัสนักเรียนเพื่อดูข้อมูลเฉพาะบุคคล'
                                ],
                                [
                                    'command_key' => 'ขอความช่วยเหลือ,help,ช่วยเหลือ,วิธีใช้งาน',
                                    'command_reply' => "คุณสามารถใช้งานระบบได้โดย:\n1. เช็คการเข้าแถว - ดูรายละเอียดการเข้าแถวของนักเรียน\n2. ดูคะแนนความประพฤติ - ตรวจสอบคะแนนความประพฤติของนักเรียน\n3. ติดต่อครู - ส่งข้อความถึงครูที่ปรึกษา\n4. ตั้งค่าการแจ้งเตือน - ปรับแต่งการแจ้งเตือนที่คุณต้องการรับ"
                                ]
                            ];
                        }
                        
                        // แสดงคำสั่งและการตอบกลับ
                        foreach ($commands as $command) {
                        ?>
                        <tr>
                            <td>
                                <input type="text" class="form-control" name="command_key[]" value="<?php echo $command['command_key']; ?>">
                                <small class="form-text text-muted">คั่นคำหลายคำด้วยเครื่องหมาย ,</small>
                            </td>
                            <td>
                                <textarea class="form-control" name="command_reply[]" rows="2"><?php echo $command['command_reply']; ?></textarea>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-danger remove-command">
                                    <span class="material-icons">delete</span>
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
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
                    <input type="checkbox" id="enable_rich_menu" name="enable_rich_menu" <?php echo (isset($settings['enable_rich_menu']) && $settings['enable_rich_menu'] == '1') ? 'checked' : 'checked'; ?>>
                    <label for="enable_rich_menu">เปิดใช้งาน Rich Menu</label>
                </div>
            </div>
            
            <?php if (!isset($settings['single_line_oa']) || $settings['single_line_oa'] == '1'): ?>
            <!-- กรณีใช้ LINE OA เดียว -->
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu</label>
                        <input type="text" class="form-control" id="rich_menu_name" name="rich_menu_name" value="<?php echo isset($settings['rich_menu_name']) ? $settings['rich_menu_name'] : 'เมนูหลัก น้องชูใจ AI'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID</label>
                        <input type="text" class="form-control" id="rich_menu_id" name="rich_menu_id" value="<?php echo isset($settings['rich_menu_id']) ? $settings['rich_menu_id'] : ''; ?>">
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- กรณีใช้หลาย LINE OA -->
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_rich_menu_name" name="parent_rich_menu_name" value="<?php echo isset($settings['parent_rich_menu_name']) ? $settings['parent_rich_menu_name'] : 'เมนูผู้ปกครอง'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับผู้ปกครอง</label>
                        <input type="text" class="form-control" id="parent_rich_menu_id" name="parent_rich_menu_id" value="<?php echo isset($settings['parent_rich_menu_id']) ? $settings['parent_rich_menu_id'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_rich_menu_name" name="student_rich_menu_name" value="<?php echo isset($settings['student_rich_menu_name']) ? $settings['student_rich_menu_name'] : 'เมนูนักเรียน'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับนักเรียน</label>
                        <input type="text" class="form-control" id="student_rich_menu_id" name="student_rich_menu_id" value="<?php echo isset($settings['student_rich_menu_id']) ? $settings['student_rich_menu_id'] : ''; ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">ชื่อ Rich Menu สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_rich_menu_name" name="teacher_rich_menu_name" value="<?php echo isset($settings['teacher_rich_menu_name']) ? $settings['teacher_rich_menu_name'] : 'เมนูครู'; ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label">Rich Menu ID สำหรับครู</label>
                        <input type="text" class="form-control" id="teacher_rich_menu_id" name="teacher_rich_menu_id" value="<?php echo isset($settings['teacher_rich_menu_id']) ? $settings['teacher_rich_menu_id'] : ''; ?>">
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <button class="btn btn-primary" id="update-rich-menu">
                    <span class="material-icons">update</span>
                    อัปเดต Rich Menu
                </button>
            </div>
        </div>
    </div>
</div>