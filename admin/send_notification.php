<?php
/**
 * send_notification.php - หน้าส่งข้อความแจ้งเตือนผู้ปกครอง
 * สำหรับระบบน้องสัตบรรณ ดูแลผู้เรียน
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'teacher')) {
    header('Location: ../login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';

/**
 * ฟังก์ชันดึงข้อมูลนักเรียน
 * 
 * @param int $studentId - รหัสนักเรียน
 * @return array - ข้อมูลนักเรียน
 */
function getStudentInfo($studentId) {
    $conn = getDB();
    
    $query = "SELECT 
                s.student_id,
                s.student_code,
                s.title,
                u.first_name,
                u.last_name,
                u.phone_number,
                c.level,
                c.group_number,
                d.department_name,
                sar.total_attendance_days,
                sar.total_absence_days,
                t.first_name as advisor_first_name,
                t.last_name as advisor_last_name,
                t.phone_number as advisor_phone,
                CASE 
                    WHEN (sar.total_attendance_days + sar.total_absence_days) > 0 
                    THEN (sar.total_attendance_days / (sar.total_attendance_days + sar.total_absence_days) * 100) 
                    ELSE 100 
                END as attendance_rate
              FROM students s
              JOIN users u ON s.user_id = u.user_id
              LEFT JOIN classes c ON s.current_class_id = c.class_id
              LEFT JOIN departments d ON c.department_id = d.department_id
              LEFT JOIN student_academic_records sar ON s.student_id = sar.student_id AND sar.academic_year_id = (
                  SELECT academic_year_id FROM academic_years WHERE is_active = 1 LIMIT 1
              )
              LEFT JOIN class_advisors ca ON c.class_id = ca.class_id AND ca.is_primary = 1
              LEFT JOIN teachers t ON ca.teacher_id = t.teacher_id
              WHERE s.student_id = :student_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ฟังก์ชันดึงข้อมูลผู้ปกครองของนักเรียน
 * 
 * @param int $studentId - รหัสนักเรียน
 * @return array - ข้อมูลผู้ปกครอง
 */
function getParentInfo($studentId) {
    $conn = getDB();
    
    $query = "SELECT 
                p.parent_id,
                p.title,
                p.relationship,
                u.user_id,
                u.first_name,
                u.last_name,
                u.phone_number,
                u.email,
                u.line_id
              FROM parent_student_relation psr
              JOIN parents p ON psr.parent_id = p.parent_id
              JOIN users u ON p.user_id = u.user_id
              WHERE psr.student_id = :student_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ฟังก์ชันดึงข้อมูลเทมเพลตข้อความ
 * 
 * @return array - ข้อมูลเทมเพลตข้อความ
 */
function getMessageTemplates() {
    $conn = getDB();
    
    $query = "SELECT 
                id,
                name,
                type,
                category,
                content
              FROM message_templates
              WHERE is_active = 1
              ORDER BY id";
    
    $stmt = $conn->query($query);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ฟังก์ชันส่งข้อความแจ้งเตือนผ่าน LINE
 * 
 * @param array $data - ข้อมูลการส่งข้อความ
 * @return bool - สถานะการส่ง
 */
function sendLineNotification($data) {
    $conn = getDB();
    
    // บันทึกข้อมูลการส่งข้อความลงฐานข้อมูล
    $query = "INSERT INTO line_notifications (
                user_id,
                message,
                notification_type,
                status
              ) VALUES (
                :user_id,
                :message,
                :notification_type,
                'pending'
              )";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':message', $data['message'], PDO::PARAM_STR);
    $stmt->bindParam(':notification_type', $data['notification_type'], PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        $notificationId = $conn->lastInsertId();
        
        // ในทางปฏิบัติจริง ต้องส่งข้อความผ่าน LINE API ที่นี่
        // แต่ในตัวอย่างนี้จะจำลองว่าส่งสำเร็จ
        
        // อัพเดตสถานะการส่ง
        $updateQuery = "UPDATE line_notifications
                       SET status = 'sent'
                       WHERE notification_id = :notification_id";
        
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
        
        return $updateStmt->execute();
    }
    
    return false;
}

/**
 * ฟังก์ชันแทนที่ตัวแปรในเทมเพลตข้อความ
 * 
 * @param string $template - เทมเพลตข้อความ
 * @param array $data - ข้อมูลที่ใช้แทนที่
 * @return string - ข้อความที่แทนที่ตัวแปรแล้ว
 */
function replaceTemplateVariables($template, $data) {
    $variables = [
        '{{ชื่อนักเรียน}}' => $data['student_name'] ?? '',
        '{{ชั้นเรียน}}' => $data['class_name'] ?? '',
        '{{จำนวนวันเข้าแถว}}' => $data['present_days'] ?? '',
        '{{จำนวนวันทั้งหมด}}' => $data['total_days'] ?? '',
        '{{ร้อยละการเข้าแถว}}' => $data['attendance_rate'] ?? '',
        '{{ชื่อครูที่ปรึกษา}}' => $data['advisor_name'] ?? '',
        '{{เบอร์โทรครู}}' => $data['advisor_phone'] ?? '',
        '{{จำนวนวันขาด}}' => $data['absent_days'] ?? '',
        '{{สถานะการเข้าแถว}}' => $data['attendance_status'] ?? '',
        '{{เดือน}}' => date('F'),
        '{{ปี}}' => date('Y'),
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $template);
}

// ประมวลผลการส่งข้อความ
$success = false;
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $studentId = $_POST['student_id'] ?? null;
    $parentId = $_POST['parent_id'] ?? null;
    $messageType = $_POST['message_type'] ?? null;
    $templateId = $_POST['template_id'] ?? null;
    $customMessage = $_POST['custom_message'] ?? null;
    
    if (!$studentId) {
        $error = 'กรุณาระบุนักเรียน';
    } elseif (!$parentId) {
        $error = 'กรุณาระบุผู้รับข้อความ';
    } elseif (!$messageType) {
        $error = 'กรุณาระบุประเภทข้อความ';
    } else {
        // ดึงข้อมูลนักเรียนและผู้ปกครอง
        $student = getStudentInfo($studentId);
        
        if (!$student) {
            $error = 'ไม่พบข้อมูลนักเรียน';
        } else {
            $conn = getDB();
            
            // หาข้อมูลผู้ปกครองที่เลือก
            $query = "SELECT 
                        p.parent_id,
                        p.title,
                        p.relationship,
                        u.user_id,
                        u.first_name,
                        u.last_name,
                        u.phone_number,
                        u.line_id
                      FROM parents p
                      JOIN users u ON p.user_id = u.user_id
                      WHERE p.parent_id = :parent_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
            $stmt->execute();
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent) {
                $error = 'ไม่พบข้อมูลผู้ปกครอง';
            } elseif (!$parent['line_id'] || strpos($parent['line_id'], 'TEMP_') === 0) {
                $error = 'ผู้ปกครองยังไม่ได้เชื่อมต่อกับ LINE';
            } else {
                // เตรียมข้อมูลสำหรับแทนที่ตัวแปรในเทมเพลต
                $templateData = [
                    'student_name' => $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'],
                    'class_name' => $student['level'] . '/' . $student['group_number'],
                    'present_days' => $student['total_attendance_days'],
                    'total_days' => $student['total_attendance_days'] + $student['total_absence_days'],
                    'attendance_rate' => round($student['attendance_rate'], 1),
                    'advisor_name' => $student['advisor_first_name'] ? 'อ.' . $student['advisor_first_name'] . ' ' . $student['advisor_last_name'] : '-',
                    'advisor_phone' => $student['advisor_phone'] ?? '-',
                    'absent_days' => $student['total_absence_days'],
                    'attendance_status' => $student['attendance_rate'] < 70 ? 'เสี่ยงตกกิจกรรม' : ($student['attendance_rate'] < 80 ? 'ต้องระวัง' : 'ปกติ'),
                ];
                
                // กำหนดเนื้อหาข้อความที่จะส่ง
                if ($messageType === 'template' && $templateId) {
                    // ดึงข้อมูลเทมเพลต
                    $query = "SELECT content FROM message_templates WHERE id = :template_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':template_id', $templateId, PDO::PARAM_INT);
                    $stmt->execute();
                    $template = $stmt->fetchColumn();
                    
                    if ($template) {
                        $messageContent = replaceTemplateVariables($template, $templateData);
                    } else {
                        $error = 'ไม่พบเทมเพลตข้อความ';
                    }
                } elseif ($messageType === 'custom' && $customMessage) {
                    $messageContent = replaceTemplateVariables($customMessage, $templateData);
                } else {
                    $error = 'กรุณาระบุข้อความ';
                }
                
                if (!$error) {
                    // ส่งข้อความแจ้งเตือน
                    $notificationData = [
                        'user_id' => $parent['user_id'],
                        'message' => $messageContent,
                        'notification_type' => 'attendance',
                    ];
                    
                    if (sendLineNotification($notificationData)) {
                        $success = true;
                        $message = 'ส่งข้อความแจ้งเตือนสำเร็จ';
                        
                        // อัพเดตวันที่ส่งการแจ้งเตือนล่าสุด
                        $query = "UPDATE message_templates SET last_used = NOW() WHERE id = :template_id";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':template_id', $templateId, PDO::PARAM_INT);
                        $stmt->execute();
                    } else {
                        $error = 'เกิดข้อผิดพลาดในการส่งข้อความแจ้งเตือน';
                    }
                }
            }
        }
    }
}

// รับข้อมูลนักเรียนจาก URL
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$student = null;
$parents = [];
$messageTemplates = [];

if ($studentId) {
    $student = getStudentInfo($studentId);
    
    if ($student) {
        $parents = getParentInfo($studentId);
        $messageTemplates = getMessageTemplates();
    }
}

// กำหนดตัวแปรสำหรับเทมเพลต
$page_title = "ส่งข้อความแจ้งเตือนผู้ปกครอง";
$current_page = "notifications";

// ไฟล์ CSS และ JS เพิ่มเติม
$extra_css = [
    "assets/css/notifications.css"
];

$extra_js = [
    "assets/js/notifications.js"
];

// โหลดเทมเพลต
include "templates/header.php";
include "templates/sidebar.php";
?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <span class="material-icons">send</span>
            ส่งข้อความแจ้งเตือนผู้ปกครอง
        </h1>
        
        <div class="breadcrumb">
            <a href="index.php">หน้าหลัก</a>
            <span class="separator">/</span>
            <a href="reports.php">รายงาน</a>
            <span class="separator">/</span>
            <span>ส่งข้อความแจ้งเตือน</span>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <span class="material-icons">check_circle</span>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <span class="material-icons">error</span>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($student): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">ข้อมูลนักเรียน</h2>
            </div>
            <div class="card-body">
                <div class="student-info-container">
                    <div class="student-avatar">
                        <?php echo substr($student['first_name'], 0, 1); ?>
                    </div>
                    <div class="student-details">
                        <h3><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></h3>
                        <div class="student-info-row">
                            <div class="student-info-item">
                                <span class="info-label">รหัสนักเรียน:</span>
                                <span class="info-value"><?php echo $student['student_code']; ?></span>
                            </div>
                            <div class="student-info-item">
                                <span class="info-label">ชั้น/ห้อง:</span>
                                <span class="info-value"><?php echo $student['level'] . '/' . $student['group_number']; ?></span>
                            </div>
                            <div class="student-info-item">
                                <span class="info-label">แผนกวิชา:</span>
                                <span class="info-value"><?php echo $student['department_name']; ?></span>
                            </div>
                        </div>
                        <div class="student-info-row">
                            <div class="student-info-item">
                                <span class="info-label">อัตราการเข้าแถว:</span>
                                <span class="info-value attendance-rate <?php echo ($student['attendance_rate'] < 70) ? 'low' : (($student['attendance_rate'] < 80) ? 'medium' : 'high'); ?>">
                                    <?php echo round($student['attendance_rate'], 1); ?>%
                                </span>
                            </div>
                            <div class="student-info-item">
                                <span class="info-label">วันที่เข้าแถว:</span>
                                <span class="info-value"><?php echo $student['total_attendance_days']; ?> วัน</span>
                            </div>
                            <div class="student-info-item">
                                <span class="info-label">วันที่ขาดแถว:</span>
                                <span class="info-value"><?php echo $student['total_absence_days']; ?> วัน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($parents)): ?>
            <div class="alert alert-warning">
                <span class="material-icons">warning</span>
                ไม่พบข้อมูลผู้ปกครองของนักเรียนคนนี้ กรุณาเพิ่มข้อมูลผู้ปกครองก่อนส่งข้อความแจ้งเตือน
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">ส่งข้อความแจ้งเตือน</h2>
                </div>
                <div class="card-body">
                    <form action="send_notification.php" method="POST">
                        <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                        
                        <div class="form-group">
                            <label for="parent_id">เลือกผู้รับข้อความ:</label>
                            <select name="parent_id" id="parent_id" class="form-control" required>
                                <option value="">-- เลือกผู้รับข้อความ --</option>
                                <?php foreach ($parents as $parent): ?>
                                    <option value="<?php echo $parent['parent_id']; ?>" <?php echo (isset($_POST['parent_id']) && $_POST['parent_id'] == $parent['parent_id']) ? 'selected' : ''; ?>>
                                        <?php echo $parent['title'] . ' ' . $parent['first_name'] . ' ' . $parent['last_name']; ?> 
                                        (<?php echo $parent['relationship']; ?>)
                                        <?php if (!$parent['line_id'] || strpos($parent['line_id'], 'TEMP_') === 0): ?>
                                            - <span class="text-danger">ยังไม่ได้เชื่อมต่อ LINE</span>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>ประเภทข้อความ:</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="message_type" value="template" <?php echo (!isset($_POST['message_type']) || $_POST['message_type'] === 'template') ? 'checked' : ''; ?> onchange="toggleMessageType()">
                                    เลือกจากเทมเพลต
                                </label>
                                <label>
                                    <input type="radio" name="message_type" value="custom" <?php echo (isset($_POST['message_type']) && $_POST['message_type'] === 'custom') ? 'checked' : ''; ?> onchange="toggleMessageType()">
                                    เขียนข้อความเอง
                                </label>
                            </div>
                        </div>
                        
                        <div id="template-section" class="form-group" style="display: <?php echo (!isset($_POST['message_type']) || $_POST['message_type'] === 'template') ? 'block' : 'none'; ?>;">
                            <label for="template_id">เลือกเทมเพลตข้อความ:</label>
                            <select name="template_id" id="template_id" class="form-control" onchange="previewTemplate()">
                                <option value="">-- เลือกเทมเพลตข้อความ --</option>
                                <?php foreach ($messageTemplates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>" data-content="<?php echo htmlspecialchars($template['content']); ?>" <?php echo (isset($_POST['template_id']) && $_POST['template_id'] == $template['id']) ? 'selected' : ''; ?>>
                                        <?php echo $template['name']; ?> 
                                        (<?php echo ($template['type'] === 'individual') ? 'รายบุคคล' : 'กลุ่ม'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div id="custom-section" class="form-group" style="display: <?php echo (isset($_POST['message_type']) && $_POST['message_type'] === 'custom') ? 'block' : 'none'; ?>;">
                            <label for="custom_message">ข้อความแจ้งเตือน:</label>
                            <textarea name="custom_message" id="custom_message" class="form-control" rows="10"><?php echo $_POST['custom_message'] ?? ''; ?></textarea>
                            <div class="template-variables">
                                <p>ตัวแปรที่ใช้ได้:</p>
                                <ul>
                                    <li><code>{{ชื่อนักเรียน}}</code> - ชื่อของนักเรียน</li>
                                    <li><code>{{ชั้นเรียน}}</code> - ชั้นเรียนของนักเรียน</li>
                                    <li><code>{{จำนวนวันเข้าแถว}}</code> - จำนวนวันที่นักเรียนเข้าแถว</li>
                                    <li><code>{{จำนวนวันทั้งหมด}}</code> - จำนวนวันทั้งหมดที่มีการเช็คชื่อ</li>
                                    <li><code>{{ร้อยละการเข้าแถว}}</code> - อัตราการเข้าแถวเป็นร้อยละ</li>
                                    <li><code>{{ชื่อครูที่ปรึกษา}}</code> - ชื่อครูที่ปรึกษา</li>
                                    <li><code>{{เบอร์โทรครู}}</code> - เบอร์โทรของครูที่ปรึกษา</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>ตัวอย่างข้อความ:</label>
                            <div id="message-preview" class="message-preview">
                                <div class="preview-header">
                                    <div class="preview-avatar">S</div>
                                    <div class="preview-name">ระบบ น้องสัตบรรณ</div>
                                </div>
                                <div class="preview-content">
                                    <p id="preview-text">
                                        กรุณาเลือกเทมเพลตหรือเขียนข้อความเพื่อดูตัวอย่าง
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="reports.php" class="btn btn-secondary">
                                <span class="material-icons">arrow_back</span>
                                กลับไปยังรายงาน
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-icons">send</span>
                                ส่งข้อความแจ้งเตือน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-danger">
            <span class="material-icons">error</span>
            ไม่พบข้อมูลนักเรียน
        </div>
        
        <div class="card">
            <div class="card-body text-center">
                <p>กรุณาเลือกนักเรียนจากรายงานเพื่อส่งข้อความแจ้งเตือน</p>
                <a href="reports.php" class="btn btn-primary">
                    <span class="material-icons">list</span>
                    ไปยังหน้ารายงาน
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleMessageType() {
    const messageType = document.querySelector('input[name="message_type"]:checked').value;
    const templateSection = document.getElementById('template-section');
    const customSection = document.getElementById('custom-section');
    
    if (messageType === 'template') {
        templateSection.style.display = 'block';
        customSection.style.display = 'none';
        previewTemplate();
    } else {
        templateSection.style.display = 'none';
        customSection.style.display = 'block';
        previewCustomMessage();
    }
}

function previewTemplate() {
    const templateSelect = document.getElementById('template_id');
    const previewText = document.getElementById('preview-text');
    
    if (templateSelect.value) {
        const selectedOption = templateSelect.options[templateSelect.selectedIndex];
        let templateContent = selectedOption.getAttribute('data-content');
        
        // แทนที่ตัวแปรด้วยข้อมูลจริง
        templateContent = replaceTemplateVariables(templateContent);
        
        previewText.innerHTML = templateContent.replace(/\n/g, '<br>');
    } else {
        previewText.innerHTML = 'กรุณาเลือกเทมเพลตเพื่อดูตัวอย่าง';
    }
}

function previewCustomMessage() {
    const customMessage = document.getElementById('custom_message').value;
    const previewText = document.getElementById('preview-text');
    
    if (customMessage) {
        // แทนที่ตัวแปรด้วยข้อมูลจริง
        const messageWithVariables = replaceTemplateVariables(customMessage);
        
        previewText.innerHTML = messageWithVariables.replace(/\n/g, '<br>');
    } else {
        previewText.innerHTML = 'กรุณาเขียนข้อความเพื่อดูตัวอย่าง';
    }
}

function replaceTemplateVariables(template) {
    // ข้อมูลนักเรียนจาก PHP
    const studentData = {
        'ชื่อนักเรียน': '<?php echo $student ? $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'] : 'ชื่อนักเรียน'; ?>',
        'ชั้นเรียน': '<?php echo $student ? $student['level'] . '/' . $student['group_number'] : 'ชั้นเรียน'; ?>',
        'จำนวนวันเข้าแถว': '<?php echo $student ? $student['total_attendance_days'] : '0'; ?>',
        'จำนวนวันทั้งหมด': '<?php echo $student ? $student['total_attendance_days'] + $student['total_absence_days'] : '0'; ?>',
        'ร้อยละการเข้าแถว': '<?php echo $student ? round($student['attendance_rate'], 1) : '0'; ?>',
        'ชื่อครูที่ปรึกษา': '<?php echo $student && $student['advisor_first_name'] ? 'อ.' . $student['advisor_first_name'] . ' ' . $student['advisor_last_name'] : 'ชื่อครูที่ปรึกษา'; ?>',
        'เบอร์โทรครู': '<?php echo $student && $student['advisor_phone'] ? $student['advisor_phone'] : 'เบอร์โทรครู'; ?>',
        'จำนวนวันขาด': '<?php echo $student ? $student['total_absence_days'] : '0'; ?>',
        'สถานะการเข้าแถว': '<?php echo $student ? ($student['attendance_rate'] < 70 ? 'เสี่ยงตกกิจกรรม' : ($student['attendance_rate'] < 80 ? 'ต้องระวัง' : 'ปกติ')) : 'สถานะการเข้าแถว'; ?>',
        'เดือน': '<?php echo date('F'); ?>',
        'ปี': '<?php echo date('Y'); ?>',
    };
    
    // แทนที่ตัวแปรทั้งหมด
    let replacedTemplate = template;
    
    for (const [key, value] of Object.entries(studentData)) {
        replacedTemplate = replacedTemplate.replace(new RegExp(`{{${key}}}`, 'g'), value);
    }
    
    return replacedTemplate;
}

// เพิ่ม event listener สำหรับการพิมพ์ข้อความเอง
document.getElementById('custom_message').addEventListener('input', previewCustomMessage);

// แสดงตัวอย่างเริ่มต้น
document.addEventListener('DOMContentLoaded', function() {
    // แสดงตัวอย่างตามประเภทข้อความที่เลือก
    const messageType = document.querySelector('input[name="message_type"]:checked').value;
    
    if (messageType === 'template') {
        previewTemplate();
    } else {
        previewCustomMessage();
    }
});
</script>

<?php include "templates/footer.php"; ?>