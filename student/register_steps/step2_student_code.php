<?php
/**
 * step2_student_search.php - หน้าค้นหาข้อมูลนักเรียนด้วยรหัสนักศึกษาหรือชื่อ
 * แสดงแท็บค้นหาด้วยชื่อเป็นค่าเริ่มต้น
 */
?>

<div class="card">
    <div class="card-title">ค้นหาข้อมูลนักเรียน</div>
    
    <p class="mb-20">
        กรุณาค้นหาข้อมูลนักเรียนด้วยชื่อ-นามสกุล หรือรหัสนักศึกษา เพื่อลงทะเบียนใช้งานระบบ
    </p>
    
    <ul class="tabs">
        <li class="tab <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'code') ? 'active' : ''; ?>" 
            onclick="location.href='register.php?step=2&tab=code'">
            <span class="material-icons">credit_card</span> ค้นหาด้วยรหัสนักศึกษา
        </li>
        <li class="tab <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'name') ? 'active' : ''; ?>" 
            onclick="location.href='register.php?step=2&tab=name'">
            <span class="material-icons">person_search</span> ค้นหาด้วยชื่อ-นามสกุล
        </li>
    </ul>
    
    <div class="tab-content">
        <?php if (isset($_GET['tab']) && $_GET['tab'] == 'code'): ?>
            <!-- ค้นหาด้วยรหัสนักศึกษา -->
            <form method="post" action="">
                <input type="hidden" name="search_type" value="code">
                <div class="input-container">
                    <label class="input-label" for="student_code">รหัสนักศึกษา 11 หลัก</label>
                    <input type="text" id="student_code" name="student_code" class="input-field" 
                           placeholder="กรอกรหัสนักศึกษา 11 หลัก" 
                           value="<?php echo isset($_POST['student_code']) ? htmlspecialchars($_POST['student_code']) : ''; ?>" 
                           maxlength="11" required>
                    <div class="help-text">ตัวอย่างเช่น: 65201230001</div>
                </div>
                
                <button type="submit" name="search_student" class="btn primary">
                    ค้นหารหัสนักศึกษา
                    <span class="material-icons">search</span>
                </button>
            </form>
        <?php else: ?>
            <!-- ค้นหาด้วยชื่อ-นามสกุล (แสดงเป็นค่าเริ่มต้น) -->
            <form method="post" action="">
                <input type="hidden" name="search_type" value="name">
                <div class="input-container">
                    <label class="input-label" for="student_name">ชื่อ-นามสกุล</label>
                    <input type="text" id="student_name" name="student_name" class="input-field" 
                           placeholder="กรอกชื่อหรือนามสกุล" 
                           value="<?php echo isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : ''; ?>" 
                           required>
                    <div class="help-text">เช่น สมชาย, ใจดี (ต้องกรอกอย่างน้อย 2 ตัวอักษร)</div>
                </div>
                
                <button type="submit" name="search_student_name" class="btn primary">
                    ค้นหาชื่อ-นามสกุล
                    <span class="material-icons">search</span>
                </button>
            </form>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['search_results']) && !empty($_SESSION['search_results'])): ?>
            <div class="search-results mt-20">
                <h3 class="mb-10">ผลการค้นหา</h3>
                
                <form method="post" action="">
                    <?php foreach ($_SESSION['search_results'] as $student): ?>
                    <div class="student-card">
                        <div class="radio-container">
                            <input type="radio" id="student_<?php echo $student['student_id']; ?>" 
                                   name="selected_student_id" value="<?php echo $student['student_id']; ?>" required>
                            <label for="student_<?php echo $student['student_id']; ?>">
                                <div class="student-name">
                                    <?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?>
                                </div>
                                <div class="student-details">
                                    รหัสนักศึกษา: <?php echo htmlspecialchars($student['student_code']); ?> | 
                                    <?php echo !empty($student['level']) ? htmlspecialchars($student['level']) : ''; ?> 
                                    <?php echo !empty($student['department_name']) ? htmlspecialchars($student['department_name']) : ''; ?> 
                                    <?php echo !empty($student['group_number']) ? 'กลุ่ม ' . htmlspecialchars($student['group_number']) : ''; ?>
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <button type="submit" name="select_student" class="btn primary mt-20">
                        เลือกนักเรียนนี้
                        <span class="material-icons">check</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="text-center mt-30">
        <p>หากไม่พบข้อมูลนักเรียน หรือเป็นนักเรียนใหม่ สามารถกดปุ่มด้านล่างเพื่อกรอกข้อมูลด้วยตนเอง</p>
        <a href="register.php?step=3manual" class="btn secondary mt-10">กรอกข้อมูลด้วยตนเอง</a>
    </div>
</div>

<div class="contact-admin">
    หากคุณมีปัญหาในการลงทะเบียน กรุณาติดต่อครูที่ปรึกษา หรือเจ้าหน้าที่ทะเบียน
</div>

<style>
    .tabs {
        display: flex;
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
        border-bottom: 1px solid #ddd;
    }
    
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        display: flex;
        align-items: center;
        margin-right: 10px;
    }
    
    .tab.active {
        border-bottom: 2px solid #28a745;
        color: #28a745;
        font-weight: bold;
    }
    
    .tab .material-icons {
        margin-right: 5px;
    }
    
    .student-card {
        background-color: #f9f9f9;
        border-radius: 10px;
        margin-bottom: 10px;
        overflow: hidden;
    }
    
    .student-card .radio-container {
        padding: 15px;
    }
    
    .student-name {
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .student-details {
        font-size: 0.9em;
        color: #666;
    }
</style>