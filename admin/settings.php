<?php
/**
 * settings.php - หน้าตั้งค่าระบบ STUDENT-Prasat
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// เริ่ม session (ใช้สำหรับการเก็บข้อมูลผู้ใช้ที่ล็อกอินและข้อมูลอื่นๆ)
session_start();

// เพิ่มการตรวจสอบข้อผิดพลาด
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ตรวจสอบการล็อกอิน (แสดงความคิดเห็นออกไปเพื่อการทดสอบ)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// กำหนดข้อมูลสำหรับหน้าปัจจุบัน
$current_page = 'settings';
$page_title = 'ตั้งค่าระบบ';
$page_header = 'การตั้งค่าระบบ STUDENT-Prasat';

// ข้อมูลเกี่ยวกับเจ้าหน้าที่ (จริงๆ ควรดึงจากฐานข้อมูล)
$admin_info = [
    'name' => $_SESSION['user_name'] ?? 'เจ้าหน้าที่',
    'role' => $_SESSION['user_role'] ?? 'ผู้ดูแลระบบ',
    'initials' => 'A',
];
// ปุ่มบนส่วนหัว - เปลี่ยนเป็นใช้ ID เฉพาะเพื่อเชื่อมต่อกับ JavaScript
$header_buttons = [
    [
        'text' => 'บันทึกการตั้งค่า',
        'icon' => 'save',
        'id' => 'save-settings-button', // เพิ่ม ID สำหรับอ้างอิงใน JavaScript
        'class' => 'btn btn-primary save-button' // เพิ่ม class สำหรับการจัดรูปแบบ
    ]
];

// เชื่อมต่อฐานข้อมูล - ตรวจสอบว่าไฟล์และฟังก์ชัน getDB() มีอยู่จริง
require_once '../db_connect.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
try {
    $conn = getDB();
    // ทดสอบการเชื่อมต่อ
    $conn->query("SELECT 1");
} catch (PDOException $e) {
    die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage());
}

// ดึงข้อมูลการตั้งค่าจากฐานข้อมูล
try {
    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings");
    $stmt->execute();
    $settings_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // แปลงให้อยู่ในรูปแบบ key => value
    $settings = [];
    foreach ($settings_rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาดในการดึงข้อมูล
    $settings = [];
    error_log("Error fetching settings: " . $e->getMessage());
}

// ตรวจสอบการส่งฟอร์มบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    // รับข้อมูลที่ส่งมา
    $settingsData = isset($_POST['settings_data']) ? json_decode($_POST['settings_data'], true) : null;
    
    // ตรวจสอบ AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // หากมีข้อมูลการตั้งค่า
    if ($settingsData) {
        try {
            // เชื่อมต่อฐานข้อมูล
            $db = getDB();
            
            // เริ่ม transaction
            $db->beginTransaction();
            
            // ประมวลผลและบันทึกการตั้งค่าแต่ละหมวดหมู่
            $successCount = 0;
            
            // บันทึกการตั้งค่าระบบ
            if (isset($settingsData['system'])) {
                saveSystemSettings($db, $settingsData['system']);
                $successCount++;
            }
            
            // บันทึกการตั้งค่าการแจ้งเตือน
            if (isset($settingsData['notification'])) {
                saveSettingsByGroup($db, $settingsData['notification'], 'notification');
                $successCount++;
            }
            
            // บันทึกการตั้งค่าการเช็คชื่อ
            if (isset($settingsData['attendance'])) {
                saveSettingsByGroup($db, $settingsData['attendance'], 'attendance');
                $successCount++;
            }
            
            // บันทึกการตั้งค่า GPS
            if (isset($settingsData['gps'])) {
                saveSettingsByGroup($db, $settingsData['gps'], 'gps');
                $successCount++;
            }
            
            // บันทึกการตั้งค่า LINE
            if (isset($settingsData['line'])) {
                saveSettingsByGroup($db, $settingsData['line'], 'line');
                $successCount++;
            }
            
            // บันทึกการตั้งค่า SMS
            if (isset($settingsData['sms'])) {
                saveSettingsByGroup($db, $settingsData['sms'], 'sms');
                $successCount++;
            }
            
            // บันทึกการตั้งค่า webhook
            if (isset($settingsData['webhook'])) {
                saveWebhookSettings($db, $settingsData['webhook']);
                $successCount++;
            }
            
            // Commit transaction
            $db->commit();
            
            if ($isAjax) {
                // สำหรับ AJAX request
                echo json_encode(['success' => true, 'message' => "บันทึกการตั้งค่าเรียบร้อยแล้ว ($successCount หมวดหมู่)"]);
                exit;
            } else {
                // สำหรับ form submit ปกติ
                $_SESSION['settings_message'] = [
                    'type' => 'success',
                    'text' => "บันทึกการตั้งค่าเรียบร้อยแล้ว ($successCount หมวดหมู่)"
                ];
            }
        } catch (Exception $e) {
            // Rollback transaction เมื่อเกิดข้อผิดพลาด
            if (isset($db)) {
                $db->rollBack();
            }
            
            if ($isAjax) {
                // สำหรับ AJAX request
                http_response_code(500);
                echo json_encode(['error' => true, 'message' => 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage()]);
                exit;
            } else {
                // สำหรับ form submit ปกติ
                $_SESSION['settings_message'] = [
                    'type' => 'error',
                    'text' => 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' . $e->getMessage()
                ];
            }
        }
    } else {
        if ($isAjax) {
            // สำหรับ AJAX request
            http_response_code(400);
            echo json_encode(['error' => true, 'message' => 'ไม่พบข้อมูลการตั้งค่า']);
            exit;
        } else {
            // สำหรับ form submit ปกติ
            $_SESSION['settings_message'] = [
                'type' => 'error',
                'text' => 'ไม่พบข้อมูลการตั้งค่า'
            ];
        }
    }
    
    // Redirect เพื่อป้องกันการ resubmit form (เฉพาะกรณี form submit ปกติ)
    if (!$isAjax) {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่าระบบ
function saveSystemSettings($db, $settings) {
    // จัดการกับการตั้งค่าพิเศษของปีการศึกษา
    if (isset($settings['current_academic_year']) && isset($settings['current_semester'])) {
        try {
            // ตรวจสอบว่ามีปีการศึกษาอยู่แล้วหรือไม่
            $stmt = $db->prepare("SELECT academic_year_id FROM academic_years WHERE year = ? AND semester = ?");
            $stmt->execute([$settings['current_academic_year'], $settings['current_semester']]);
            $academicYear = $stmt->fetch();
            
            if ($academicYear) {
                // อัปเดตปีการศึกษาที่มีอยู่
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 1, 
                               start_date = ?, end_date = ?, 
                               required_attendance_days = ? 
                               WHERE academic_year_id = ?");
                $stmt->execute([
                    $settings['semester_start_date'] ?? date('Y-m-d'),
                    $settings['semester_end_date'] ?? date('Y-m-d', strtotime('+4 months')),
                    $settings['required_attendance_days'] ?? 80,
                    $academicYear['academic_year_id']
                ]);
                
                // ยกเลิกการใช้งานปีการศึกษาอื่น
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 0 WHERE academic_year_id != ?");
                $stmt->execute([$academicYear['academic_year_id']]);
            } else {
                // สร้างปีการศึกษาใหม่และตั้งเป็นปีที่ใช้งาน
                $stmt = $db->prepare("INSERT INTO academic_years 
                               (year, semester, start_date, end_date, required_attendance_days, is_active) 
                               VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $settings['current_academic_year'],
                    $settings['current_semester'],
                    $settings['semester_start_date'] ?? date('Y-m-d'),
                    $settings['semester_end_date'] ?? date('Y-m-d', strtotime('+4 months')),
                    $settings['required_attendance_days'] ?? 80
                ]);
                
                // ยกเลิกการใช้งานปีการศึกษาอื่น
                $stmt = $db->prepare("UPDATE academic_years SET is_active = 0 WHERE year != ? OR semester != ?");
                $stmt->execute([$settings['current_academic_year'], $settings['current_semester']]);
            }
        } catch (PDOException $e) {
            error_log('Error updating academic year: ' . $e->getMessage());
            throw $e;
        }
    }
    
    // บันทึกการตั้งค่าระบบอื่นๆ
    saveSettingsByGroup($db, $settings, 'general');
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่าตามหมวดหมู่
function saveSettingsByGroup($db, $settings, $group) {
    foreach ($settings as $key => $value) {
        // แปลงค่า boolean เป็น 0/1
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }
        
        // แปลงค่า checkbox
        if ($value === 'on') {
            $value = '1';
        } else if ($value === 'off') {
            $value = '0';
        }
        
        // แปลงค่า array เป็น JSON
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        try {
            // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
            $stmt = $db->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            
            if ($stmt->fetch()) {
                // อัปเดตการตั้งค่าที่มีอยู่
                $stmt = $db->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                // เพิ่มการตั้งค่าใหม่
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_group, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$key, $value, $group]);
            }
        } catch (PDOException $e) {
            error_log('Error saving setting ' . $key . ': ' . $e->getMessage());
            // ทำต่อไปกับการตั้งค่าอื่น แม้จะมีข้อผิดพลาดกับการตั้งค่านี้
            continue;
        }
    }
}

// ฟังก์ชันสำหรับบันทึกการตั้งค่า webhook และคำสั่งตอบกลับ
function saveWebhookSettings($db, $settings) {
    // บันทึกการตั้งค่าพื้นฐาน
    saveSettingsByGroup($db, $settings, 'webhook');
    
    // จัดการกับคำสั่งและการตอบกลับ
    if (isset($settings['commands']) && is_array($settings['commands'])) {
        try {
            // ลบคำสั่งทั้งหมดที่มีอยู่เดิม
            $stmt = $db->prepare("DELETE FROM bot_commands");
            $stmt->execute();
            
            // เพิ่มคำสั่งใหม่
            foreach ($settings['commands'] as $command) {
                if (!empty($command['key']) && !empty($command['reply'])) {
                    $stmt = $db->prepare("INSERT INTO bot_commands (command_key, command_reply, is_active, created_at) 
                                         VALUES (?, ?, 1, NOW())");
                    $stmt->execute([$command['key'], $command['reply']]);
                }
            }
        } catch (PDOException $e) {
            error_log('Error saving webhook commands: ' . $e->getMessage());
            throw $e;
        }
    }
}

// แสดงข้อความแจ้งเตือนหลังจากบันทึกการตั้งค่า
if (isset($_SESSION['settings_message'])) {
    $message = $_SESSION['settings_message'];
    unset($_SESSION['settings_message']);
    
    // นำไปแสดงใน JavaScript
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        const message = " . json_encode($message) . ";
        if (message.type === 'success') {
            if (typeof showSuccessMessage === 'function') {
                showSuccessMessage(message.text);
            } else {
                alert('สำเร็จ: ' + message.text);
            }
        } else {
            if (typeof showErrorMessage === 'function') {
                showErrorMessage(message.text);
            } else {
                alert('ข้อผิดพลาด: ' + message.text);
            }
        }
    });
    </script>";
}

// จำนวนนักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_count = 12;

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    'assets/css/settings.css'
];

$extra_js = [
    'assets/js/settings.js'
];

// กำหนดเส้นทางไปยังไฟล์เนื้อหาเฉพาะหน้า
$content_path = 'pages/settings_content.php';

// โหลดเทมเพลตส่วนหัว
require_once 'templates/header.php';

// โหลดเทมเพลตเมนูด้านข้าง
require_once 'templates/sidebar.php';

// โหลดเทมเพลตเนื้อหาหลัก
require_once 'templates/main_content.php';

// โหลดเทมเพลตส่วนท้าย
require_once 'templates/footer.php';

// เพิ่มส่วนนี้เพื่อแก้ไขปัญหาการบันทึกไม่ได้
?>
<script>
// เพิ่ม event listener สำหรับปุ่มบันทึกเมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ใช้ทั้ง ID และ class ในการเลือกปุ่ม เผื่อกรณีที่ template สร้างปุ่มด้วยโครงสร้างต่างกัน
    const saveButtons = document.querySelectorAll('#save-settings-button, .save-button, button[onclick="saveSettings()"]');
    
    // เพิ่ม event listener ให้กับทุกปุ่มที่เจอ
    saveButtons.forEach(button => {
        // ลบ event ที่อาจมีอยู่ก่อนหน้า
        button.removeAttribute('onclick');
        
        // เพิ่ม event ใหม่
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Saving settings...');
            
            // ตรวจสอบว่ามีฟังก์ชัน saveSettings หรือไม่
            if (typeof saveSettings === 'function') {
                saveSettings();
            } else {
                alert('ไม่พบฟังก์ชัน saveSettings กรุณาตรวจสอบไฟล์ settings.js');
                console.error('Function saveSettings not found');
            }
        });
    });
    
    // เพิ่มข้อความแจ้งเตือนในหน้า
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        const alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }
    
    console.log('Settings page initialized');
});

// ฟังก์ชัน saveSettings แบบเบสิกเพื่อให้แน่ใจว่ามีฟังก์ชันนี้ ในกรณีที่ไฟล์ settings.js ไม่ถูกโหลด
if (typeof saveSettings !== 'function') {
    function saveSettings() {
        console.log('Using fallback saveSettings function');
        
        // รวบรวมข้อมูลการตั้งค่า
        const formData = new FormData();
        formData.append('action', 'save_settings');
        
        // จัดเตรียมข้อมูลการตั้งค่าตามแท็บ
        const settingsData = collectSettingsData();
        formData.append('settings_data', JSON.stringify(settingsData));
        
        // เพิ่มการบันทึกล็อก
        console.log('Settings data:', settingsData);
        console.log('Form data:', Object.fromEntries(formData));

        // เพิ่มการตรวจสอบขนาดข้อมูล
        const jsonData = JSON.stringify(settingsData);
        console.log('Data size:', jsonData.length, 'bytes');

        // ถ้าข้อมูลมีขนาดใหญ่เกินไป ให้แสดงข้อความเตือน
        if (jsonData.length > 1000000) {
            console.warn('Warning: Data size might be too large');
        }
        
        // แสดง loading
        showLoadingIndicator();
        
        // แก้ไขส่วนการส่งข้อมูล
        fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                console.error('Server response:', response.status, response.statusText);
                throw new Error('การเชื่อมต่อล้มเหลว (HTTP ' + response.status + ')');
            }
            return response.text();  // เปลี่ยนจาก response.json() เป็น response.text()
        })
        .then(data => {
            console.log('Server response:', data);
            // ตรวจสอบว่ามีข้อความ error หรือไม่
            if (data.includes('error') || data.includes('Error')) {
                throw new Error('เซิร์ฟเวอร์ส่งข้อผิดพลาด: ' + data);
            }
            // แสดงข้อความสำเร็จ
            showSuccessMessage('บันทึกการตั้งค่าเรียบร้อยแล้ว');
            // รีเฟรชหน้าหลังจาก 2 วินาที
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoadingIndicator();
            showErrorMessage('เกิดข้อผิดพลาดในการบันทึกการตั้งค่า: ' + error.message);
        });
    }
}

// ฟังก์ชันรวบรวมข้อมูลการตั้งค่า
function collectSettingsData() {
    // สร้างออบเจ็กต์สำหรับเก็บการตั้งค่าทั้งหมด
    const settings = {
        system: {},
        notification: {},
        attendance: {},
        gps: {},
        line: {},
        sms: {},
        webhook: {}
    };

    // รวบรวมการตั้งค่าระบบ
    const systemTab = document.getElementById('system-tab');
    if (systemTab) {
        systemTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.system[input.name] = input.checked;
                } else {
                    settings.system[input.name] = input.value;
                }
            }
        });
    }

    // รวบรวมการตั้งค่าการแจ้งเตือน
    const notificationTab = document.getElementById('notification-tab');
    if (notificationTab) {
        notificationTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.notification[input.name] = input.checked;
                } else {
                    settings.notification[input.name] = input.value;
                }
            }
        });
    }

    // รวบรวมการตั้งค่าการเช็คชื่อ
    const attendanceTab = document.getElementById('attendance-tab');
    if (attendanceTab) {
        attendanceTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.attendance[input.name] = input.checked;
                } else {
                    settings.attendance[input.name] = input.value;
                }
            }
        });
    }

    // รวบรวมการตั้งค่า GPS
    const gpsTab = document.getElementById('gps-tab');
    if (gpsTab) {
        gpsTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.gps[input.name] = input.checked;
                } else {
                    settings.gps[input.name] = input.value;
                }
            }
        });
        
        // รวบรวมข้อมูลตำแหน่งเพิ่มเติม
        if (settings.gps.enable_multiple_locations) {
            settings.gps.locations = [];
            gpsTab.querySelectorAll('.additional-location-item').forEach(item => {
                const nameInput = item.querySelector('[name="location_name[]"]');
                const radiusInput = item.querySelector('[name="location_radius[]"]');
                const latInput = item.querySelector('[name="location_latitude[]"]');
                const lngInput = item.querySelector('[name="location_longitude[]"]');
                
                if (nameInput && radiusInput && latInput && lngInput) {
                    settings.gps.locations.push({
                        name: nameInput.value,
                        radius: radiusInput.value,
                        latitude: latInput.value,
                        longitude: lngInput.value
                    });
                }
            });
        }
    }

    // รวบรวมการตั้งค่า LINE
    const lineTab = document.getElementById('line-tab');
    if (lineTab) {
        lineTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.line[input.name] = input.checked;
                } else {
                    settings.line[input.name] = input.value;
                }
            }
        });
    }

    // รวบรวมการตั้งค่า SMS
    const smsTab = document.getElementById('sms-tab');
    if (smsTab) {
        smsTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '') {
                if (input.type === 'checkbox') {
                    settings.sms[input.name] = input.checked;
                } else {
                    settings.sms[input.name] = input.value;
                }
            }
        });
    }

    // รวบรวมการตั้งค่า Webhook
    const webhookTab = document.getElementById('webhook-tab');
    if (webhookTab) {
        webhookTab.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.name && input.name.trim() !== '' && input.name !== 'command_key[]' && input.name !== 'command_reply[]') {
                if (input.type === 'checkbox') {
                    settings.webhook[input.name] = input.checked;
                } else {
                    settings.webhook[input.name] = input.value;
                }
            }
        });
        
        // รวบรวมคำสั่งและการตอบกลับ
        settings.webhook.commands = [];
        const commandsContainer = document.getElementById('commands-container');
        if (commandsContainer) {
            const rows = commandsContainer.querySelectorAll('tr');
            rows.forEach(row => {
                const keyInput = row.querySelector('[name="command_key[]"]');
                const replyInput = row.querySelector('[name="command_reply[]"]');
                
                if (keyInput && replyInput && keyInput.value.trim() !== '') {
                    settings.webhook.commands.push({
                        key: keyInput.value,
                        reply: replyInput.value
                    });
                }
            });
        }
    }

    return settings;
}

// ฟังก์ชันแสดง loading indicator
function showLoadingIndicator() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.style.position = 'fixed';
    loadingOverlay.style.top = '0';
    loadingOverlay.style.left = '0';
    loadingOverlay.style.width = '100%';
    loadingOverlay.style.height = '100%';
    loadingOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    loadingOverlay.style.display = 'flex';
    loadingOverlay.style.justifyContent = 'center';
    loadingOverlay.style.alignItems = 'center';
    loadingOverlay.style.zIndex = '9999';
    
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.innerHTML = '<div class="spinner-border text-light" role="status"><span class="sr-only">กำลังบันทึก...</span></div>';
    loadingOverlay.appendChild(spinner);
    
    document.body.appendChild(loadingOverlay);
}

// ฟังก์ชันซ่อน loading indicator
function hideLoadingIndicator() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        document.body.removeChild(loadingOverlay);
    }
}

// ฟังก์ชันแสดงข้อความสำเร็จ
function showSuccessMessage(message) {
    const alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show';
    alert.innerHTML = `
        <strong>สำเร็จ!</strong> ${message}
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// ฟังก์ชันแสดงข้อความผิดพลาด
function showErrorMessage(message) {
    const alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-danger alert-dismissible fade show';
    alert.innerHTML = `
        <strong>ข้อผิดพลาด!</strong> ${message}
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>