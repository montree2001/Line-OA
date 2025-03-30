<?php
// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($_SESSION['class_id']) || !isset($_SESSION['student_code'])) {
    header('Location: register.php?step=2');
    exit;
}

// ดึงข้อมูลชั้นเรียนที่เลือก
$class_id = $_SESSION['class_id'] ?? 0;
$class_name = "";

try {
    if ($class_id > 0) {
        $class_sql = "SELECT c.level, d.department_name, c.group_number 
                     FROM classes c
                     JOIN departments d ON c.department_id = d.department_id
                     WHERE c.class_id = :class_id";
        $class_stmt = $conn->prepare($class_sql);
        $class_stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $class_stmt->execute();
        $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($class) {
            $class_name = $class['level'] . ' แผนก' . $class['department_name'] . ' กลุ่ม ' . $class['group_number'];
        }
    }
} catch (PDOException $e) {
    $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูลชั้นเรียน: " . $e->getMessage();
}

// ดึงข้อมูลผู้ใช้ (ถ้ามี)
$user_data = [];
if (isset($_SESSION['student_id'])) {
    try {
        $user_sql = "SELECT u.phone_number, u.email 
                    FROM users u 
                    JOIN students s ON u.user_id = s.user_id 
                    WHERE s.student_id = :student_id";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
        $user_stmt->execute();
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // ไม่ต้องทำอะไร ใช้ค่าว่างต่อไป
    }
}
?>

<div class="card">
    <h2 class="card-title">ข้อมูลเพิ่มเติม</h2>
    
    <div class="profile-info-section mb-20">
        <h3>สรุปข้อมูลการลงทะเบียน</h3>
        
        <div class="info-item">
            <div class="info-label">รหัสนักศึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_code']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">ชื่อ-นามสกุล:</div>
            <div class="info-value">
                <?php 
                echo htmlspecialchars($_SESSION['student_title'] . ' ' . 
                                      $_SESSION['student_first_name'] . ' ' . 
                                      $_SESSION['student_last_name']); 
                ?>
            </div>
        </div>
        
        <div class="info-item">
            <div class="info-label">ชั้นเรียน:</div>
            <div class="info-value"><?php echo htmlspecialchars($class_name); ?></div>
        </div>
    </div>
    
    <p class="mb-10">
        กรุณากรอกข้อมูลติดต่อของคุณเพื่อให้ระบบสามารถแจ้งเตือนและติดต่อคุณได้
    </p>
    
    <form method="post" action="register.php?step=6" id="additional-info-form">
        <input type="hidden" name="action" value="additional_info">
        
        <div class="input-container">
            <label for="phone" class="input-label">เบอร์โทรศัพท์</label>
            <input type="tel" id="phone" name="phone" class="input-field" 
                   placeholder="เบอร์โทรศัพท์" 
                   value="<?php echo isset($user_data['phone_number']) ? htmlspecialchars($user_data['phone_number']) : ''; ?>">
            <div class="help-text">เบอร์โทรศัพท์ที่ติดต่อได้ จะใช้ในการแจ้งเตือนกรณีฉุกเฉิน</div>
        </div>
        
        <div class="input-container">
            <label for="email" class="input-label">อีเมล</label>
            <input type="email" id="email" name="email" class="input-field" 
                   placeholder="อีเมล" 
                   value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>">
            <div class="help-text">อีเมลที่ใช้ติดต่อและรับข่าวสารจากทางวิทยาลัย</div>
        </div>
        
        <div class="checkbox-container mb-20">
            <input type="checkbox" id="gdpr_consent" name="gdpr_consent" required>
            <label for="gdpr_consent" class="checkbox-label">
                ฉันยินยอมให้เก็บข้อมูลส่วนบุคคลเพื่อใช้ในระบบเช็คชื่อและแจ้งเตือนผู้ปกครอง โดยข้อมูลจะถูกเก็บเป็นความลับตาม พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล
            </label>
        </div>
        
        <div class="text-center mt-30">
            <a href="register.php?step=<?php echo isset($_SESSION['teacher_classes']) ? '5' : '55'; ?>" class="btn secondary">
                <span class="material-icons">arrow_back</span> กลับ
            </a>
            <button type="submit" class="btn primary">
                บันทึกข้อมูล <span class="material-icons">check</span>
            </button>
        </div>
    </form>
</div>

<script>
    // ตรวจสอบความถูกต้องของฟอร์ม
    document.getElementById('additional-info-form').addEventListener('submit', function(e) {
        const phone = document.getElementById('phone').value.trim();
        const gdprConsent = document.getElementById('gdpr_consent').checked;
        
        if (!gdprConsent) {
            e.preventDefault();
            alert('กรุณายินยอมให้เก็บข้อมูลส่วนบุคคล');
            return;
        }
        
        // ตรวจสอบรูปแบบเบอร์โทรศัพท์ถ้ามีการกรอก
        if (phone !== '') {
            const phonePattern = /^[0-9]{9,10}$/;
            if (!phonePattern.test(phone.replace(/[-\s]/g, ''))) {
                e.preventDefault();
                alert('เบอร์โทรศัพท์ไม่ถูกต้อง กรุณากรอกเบอร์โทรศัพท์ 9-10 หลัก');
                return;
            }
        }
    });
</script>