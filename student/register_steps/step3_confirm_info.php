<div class="card">
    <h2 class="card-title">ยืนยันข้อมูลของคุณ</h2>
    
    <div class="alert alert-success">
        <span class="material-icons">check_circle</span>
        <span>พบข้อมูลของคุณในระบบแล้ว กรุณาตรวจสอบความถูกต้อง</span>
    </div>
    
    <div class="profile-info-section">
        <h3>ข้อมูลนักศึกษา</h3>
        
        <div class="info-item">
            <div class="info-label">รหัสนักศึกษา:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_code']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">คำนำหน้า:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_title']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">ชื่อ:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_first_name']); ?></div>
        </div>
        
        <div class="info-item">
            <div class="info-label">นามสกุล:</div>
            <div class="info-value"><?php echo htmlspecialchars($_SESSION['student_last_name']); ?></div>
        </div>
        
        <?php if (isset($_SESSION['current_class_id']) && $_SESSION['current_class_id']): ?>
        <div class="info-item">
            <div class="info-label">ชั้นเรียน:</div>
            <div class="info-value">
                <?php
                // แสดงข้อมูลชั้นเรียน (ถ้ามี)
                try {
                    $class_sql = "SELECT c.level, d.department_name, c.group_number 
                                FROM classes c
                                JOIN departments d ON c.department_id = d.department_id
                                WHERE c.class_id = :class_id";
                    $class_stmt = $conn->prepare($class_sql);
                    $class_stmt->bindParam(':class_id', $_SESSION['current_class_id'], PDO::PARAM_INT);
                    $class_stmt->execute();
                    $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($class) {
                        echo htmlspecialchars($class['level'] . ' แผนก' . $class['department_name'] . ' กลุ่ม ' . $class['group_number']);
                    } else {
                        echo "ไม่ระบุ";
                    }
                } catch (PDOException $e) {
                    echo "ไม่สามารถดึงข้อมูลชั้นเรียนได้";
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="mt-20">
        <p class="mb-10">หากข้อมูลข้างต้นถูกต้อง คลิกปุ่ม "ถัดไป" เพื่อไปยังขั้นตอนต่อไป</p>
        <p class="mb-10">หากข้อมูลไม่ถูกต้อง กรุณาติดต่อเจ้าหน้าที่ทะเบียนเพื่อแก้ไขข้อมูล</p>
    </div>
    
    <div class="text-center mt-30">
        <a href="register.php?step=2" class="btn secondary">
            <span class="material-icons">arrow_back</span> กลับ
        </a>
        <a href="register.php?step=4" class="btn primary">
            ถัดไป <span class="material-icons">arrow_forward</span>
        </a>
    </div>
</div>