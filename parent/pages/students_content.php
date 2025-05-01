<?php
// ตรวจสอบว่าเป็นการดูรายละเอียดนักเรียนหรือไม่
if (isset($selected_student)) {
    // แสดงรายละเอียดของนักเรียนที่เลือก
    include 'pages/student_detail_content.php';
} else {
    // แสดงรายการนักเรียนทั้งหมด
?>

<!-- แสดงข้อความแจ้งเตือน -->
<?php if(isset($success_message)): ?>
<div class="notification-banner success">
    <span class="material-icons icon">check_circle</span>
    <div class="content">
        <div class="title">สำเร็จ</div>
        <div class="message"><?php echo $success_message; ?></div>
    </div>
</div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
<div class="notification-banner danger">
    <span class="material-icons icon">error</span>
    <div class="content">
        <div class="title">เกิดข้อผิดพลาด</div>
        <div class="message"><?php echo $error_message; ?></div>
    </div>
</div>
<?php endif; ?>

<!-- แจ้งเตือน -->
<div class="notification-banner">
    <span class="material-icons icon">child_care</span>
    <div class="content">
        <div class="title">นักเรียนในความดูแล</div>
        <div class="message">รายชื่อนักเรียนทั้งหมดที่อยู่ในความดูแลของคุณ</div>
    </div>
</div>

<!-- ค้นหาและกรองข้อมูล -->
<div class="search-section">
    <div class="search-input">
        <span class="material-icons">search</span>
        <input type="text" id="search-student" placeholder="ค้นหานักเรียนในความดูแล...">
    </div>
    <div class="filter-dropdown">
        <button class="filter-button" id="filter-toggle">
            <span class="material-icons">filter_list</span>
            <span>กรอง</span>
        </button>
        <div class="filter-menu" id="filter-menu">
            <div class="filter-item">
                <input type="checkbox" id="filter-all" checked>
                <label for="filter-all">ทั้งหมด</label>
            </div>
            <div class="filter-item">
                <input type="checkbox" id="filter-high-school">
                <label for="filter-high-school">ระดับ ปวส.</label>
            </div>
            <div class="filter-item">
                <input type="checkbox" id="filter-primary-school">
                <label for="filter-primary-school">ระดับ ปวช.</label>
            </div>
        </div>
    </div>
</div>

<!-- รายการนักเรียนในความดูแล -->
<div class="section-header">
    <h2>นักเรียนในความดูแล</h2>
</div>

<div class="student-list">
    <?php if(!empty($students)): ?>
        <?php foreach($students as $student): ?>
            <div class="student-card" data-id="<?php echo $student['id']; ?>">
                <div class="student-card-header">
                    <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                    <div class="student-basic-info">
                        <div class="student-name"><?php echo $student['name']; ?></div>
                        <div class="student-class"><?php echo $student['class']; ?> เลขที่ <?php echo $student['number']; ?></div>
                        <div class="student-id">รหัสนักเรียน: <?php echo $student['student_code']; ?></div>
                    </div>
                    <div class="student-status <?php echo $student['status_class']; ?>" title="<?php echo $student['status']; ?> <?php echo $student['check_date']; ?> <?php echo $student['check_in_time']; ?>">
                        <span class="material-icons"><?php echo $student['status_icon']; ?></span>
                        <span><?php echo $student['status']; ?></span>
                        <?php if($student['check_in_time']): ?>
                            <span class="status-details"><?php echo $student['check_date']; ?>, <?php echo $student['check_in_time']; ?> น.</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="student-card-body">
                    <div class="attendance-summary">
                        <div class="attendance-item">
                            <div class="attendance-value"><?php echo $student['attendance_days']; ?></div>
                            <div class="attendance-label">วันเข้าแถว</div>
                        </div>
                        <div class="attendance-item">
                            <div class="attendance-value"><?php echo $student['absent_days']; ?></div>
                            <div class="attendance-label">วันขาดแถว</div>
                        </div>
                        <div class="attendance-item">
                            <div class="attendance-value percentage <?php echo $student['attendance_percentage'] >= 90 ? 'good' : ($student['attendance_percentage'] >= 80 ? 'warning' : 'danger'); ?>">
                                <?php echo number_format($student['attendance_percentage'], 1); ?>%
                            </div>
                            <div class="attendance-label">อัตราการเข้าแถว</div>
                        </div>
                    </div>
                    <div class="student-actions">
                        <a href="students.php?id=<?php echo $student['id']; ?>" class="view-details-button">
                            <span class="material-icons">visibility</span>
                            ดูรายละเอียด
                        </a>
                        <form method="post" class="remove-student-form" onsubmit="return confirm('คุณต้องการลบนักเรียนออกจากความดูแลหรือไม่?');">
                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                            <button type="submit" name="remove_student" class="remove-student-button">
                                <span class="material-icons">person_remove</span>
                                ลบออก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">
                <span class="material-icons">child_care</span>
            </div>
            <div class="no-data-message">ไม่พบข้อมูลนักเรียนในความดูแล</div>
            <div class="no-data-action">
                <a href="#add-student-section" class="add-student-button">
                    <span class="material-icons">add_circle</span>
                    เพิ่มนักเรียน
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ค้นหานักเรียนเพื่อเพิ่มเข้าความดูแล -->
<div id="add-student-section" class="section-header">
    <h2>เพิ่มนักเรียนเข้าสู่ความดูแล</h2>
</div>

<div class="search-add-section">
    <form method="get" class="search-form">
        <div class="search-input-large">
            <span class="material-icons">search</span>
            <input type="text" name="search" placeholder="ค้นหานักเรียนด้วยชื่อหรือรหัสนักเรียน..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit" class="search-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>
    </form>
    
    <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
        <div class="search-results">
            <h3>ผลการค้นหา "<?php echo htmlspecialchars($_GET['search']); ?>"</h3>
            
            <?php if(!empty($search_results)): ?>
                <div class="search-results-list">
                    <?php foreach($search_results as $result): ?>
                        <div class="search-result-item">
                            <div class="student-info">
                                <div class="student-name"><?php echo $result['name']; ?></div>
                                <div class="student-details">
                                    <span class="student-class">ชั้น <?php echo $result['class']; ?></span>
                                    <span class="student-department"><?php echo $result['department']; ?></span>
                                    <span class="student-code">รหัส <?php echo $result['student_code']; ?></span>
                                </div>
                            </div>
                            <form method="post">
                                <input type="hidden" name="student_id" value="<?php echo $result['id']; ?>">
                                <button type="submit" name="add_student" class="add-student-button">
                                    <span class="material-icons">person_add</span>
                                    เพิ่ม
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <span class="material-icons">search_off</span>
                    <p>ไม่พบนักเรียนที่ตรงกับคำค้นหา หรือนักเรียนอยู่ในความดูแลของคุณแล้ว</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// เปิด/ปิดเมนูกรอง
document.getElementById('filter-toggle').addEventListener('click', function() {
    const filterMenu = document.getElementById('filter-menu');
    filterMenu.classList.toggle('active');
});

// ปิดเมนูกรองเมื่อคลิกที่อื่น
document.addEventListener('click', function(e) {
    const filterMenu = document.getElementById('filter-menu');
    const filterToggle = document.getElementById('filter-toggle');
    
    if (!filterToggle.contains(e.target) && !filterMenu.contains(e.target)) {
        filterMenu.classList.remove('active');
    }
});

// ค้นหานักเรียนในความดูแล
document.getElementById('search-student').addEventListener('input', function() {
    const searchText = this.value.toLowerCase().trim();
    const studentCards = document.querySelectorAll('.student-card');
    
    studentCards.forEach(card => {
        const studentName = card.querySelector('.student-name').textContent.toLowerCase();
        const studentClass = card.querySelector('.student-class').textContent.toLowerCase();
        const studentId = card.querySelector('.student-id').textContent.toLowerCase();
        
        if (studentName.includes(searchText) || studentClass.includes(searchText) || studentId.includes(searchText)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// ตรวจสอบการคลิกที่การ์ดนักเรียน
document.querySelectorAll('.student-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // ถ้าคลิกที่ปุ่มดูรายละเอียดหรือปุ่มลบ ให้ดำเนินการตามปกติ
        if (e.target.closest('.view-details-button') || e.target.closest('.remove-student-button') || 
            e.target.closest('.remove-student-form')) {
            return;
        }
        
        // ถ้าคลิกที่ส่วนอื่นของการ์ด ให้ไปที่หน้ารายละเอียด
        const studentId = this.getAttribute('data-id');
        window.location.href = 'students.php?id=' + studentId;
    });
});
</script>

<style>
/* สไตล์สำหรับหน้ารายการนักเรียน */
.search-section {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    gap: 15px;
}

.search-input {
    flex: 1;
    display: flex;
    align-items: center;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 0 15px;
}

.search-input input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 10px;
    font-size: 14px;
    outline: none;
}

.search-input .material-icons {
    color: var(--text-light);
}

.filter-dropdown {
    position: relative;
}

.filter-button {
    display: flex;
    align-items: center;
    gap: 5px;
    background-color: var(--bg-light);
    border: none;
    border-radius: 8px;
    padding: 0 15px;
    height: 100%;
    cursor: pointer;
    color: var(--text-dark);
    font-weight: 500;
}

.filter-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 5px;
    background-color: white;
    border-radius: 8px;
    padding: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    min-width: 200px;
    z-index: 100;
    display: none;
}

.filter-menu.active {
    display: block;
}

.filter-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
}

.filter-item input {
    margin-right: 10px;
}

.student-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.student-card {
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.student-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.student-card-header {
    display: flex;
    padding: 15px;
    position: relative;
    border-bottom: 1px solid var(--border-color);
}

.student-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
    margin-right: 15px;
}

.student-basic-info {
    flex: 1;
}

.student-name {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.student-class, .student-id {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 2px;
}

.student-status {
    position: absolute;
    top: 15px;
    right: 15px;
    display: inline-flex;
    align-items: center;
    font-size: 12px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 20px;
    max-width: 110px;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.student-status .material-icons {
    font-size: 14px;
    margin-right: 4px;
    min-width: 14px;
}

.status-details {
    display: none; /* ซ่อนรายละเอียดวันที่และเวลาในมุมมองปกติ */
}

/* เพิ่มการแสดงรายละเอียดเมื่อเลื่อนเมาส์ไปที่สถานะ */
.student-status:hover {
    max-width: none;
    z-index: 10;
}

.student-status:hover .status-details {
    display: inline;
    font-size: 10px;
    margin-left: 5px;
    font-weight: normal;
}

.student-status.present {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.student-status.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.student-status.late {
    background-color: var(--warning-color-light);
    color: var(--warning-color);
}

.student-status.leave {
    background-color: #f0f0f0;
    color: var(--text-muted);
}

.student-status.unknown {
    background-color: #f0f0f0;
    color: var(--text-muted);
}

.student-card-body {
    padding: 15px;
}

.attendance-summary {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.attendance-item {
    text-align: center;
    flex: 1;
}

.attendance-value {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 5px;
}

.attendance-value.percentage {
    color: var(--primary-color);
}

.attendance-value.percentage.good {
    color: var(--success-color);
}

.attendance-value.percentage.warning {
    color: var(--warning-color);
}

.attendance-value.percentage.danger {
    color: var(--danger-color);
}

.attendance-label {
    font-size: 12px;
    color: var(--text-light);
}

.student-actions {
    display: flex;
    gap: 10px;
}

.view-details-button, .remove-student-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: background-color 0.2s;
    flex: 1;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.view-details-button {
    background-color: var(--primary-color-light);
    color: var(--primary-color);
}

.view-details-button:hover {
    background-color: #e8dbef;
}

.remove-student-button {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.remove-student-button:hover {
    background-color: #ffe6e6;
}

.view-details-button .material-icons, .remove-student-button .material-icons {
    font-size: 18px;
    margin-right: 5px;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
}

.no-data-icon {
    font-size: 60px;
    color: #e0e0e0;
    margin-bottom: 20px;
}

.no-data-icon .material-icons {
    font-size: 60px;
}

.no-data-message {
    font-size: 18px;
    color: var(--text-light);
    margin-bottom: 20px;
}

.add-student-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    border: none;
    cursor: pointer;
}

.add-student-button .material-icons {
    margin-right: 5px;
}

/* ส่วนค้นหาและเพิ่มนักเรียน */
.search-add-section {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: var(--card-shadow);
}

.search-form {
    margin-bottom: 20px;
}

.search-input-large {
    display: flex;
    align-items: center;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 0 15px;
    overflow: hidden;
}

.search-input-large input {
    flex: 1;
    border: none;
    background: transparent;
    padding: 12px;
    font-size: 15px;
    outline: none;
}

.search-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    font-weight: 500;
}

.search-button .material-icons {
    margin-right: 5px;
}

.search-results h3 {
    font-size: 16px;
    margin-bottom: 15px;
    color: var(--text-dark);
}

.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.search-result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background-color: var(--bg-light);
    border-radius: 8px;
}

.student-info {
    flex: 1;
}

.student-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    font-size: 14px;
    color: var(--text-light);
    margin-top: 3px;
}

.no-results {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 30px;
    text-align: center;
    color: var(--text-light);
}

.no-results .material-icons {
    font-size: 40px;
    margin-bottom: 10px;
    color: #ccc;
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .student-card-header {
        padding-right: 120px; /* เพิ่มพื้นที่ด้านขวาเพื่อไม่ให้สถานะบังเนื้อหา */
        position: relative;
    }
    
    .student-status {
        top: 15px; /* คงตำแหน่งด้านบนขวา */
        right: 15px;
        width: auto;
        transform: none;
    }
    
    .student-avatar {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .student-basic-info {
        margin-bottom: 10px;
        text-align: left; /* เปลี่ยนจาก center เป็น left */
        width: 100%;
    }
}

@media (max-width: 480px) {
    .student-card-header {
        flex-direction: row; /* เปลี่ยนกลับไปเป็นแนวนอน */
        align-items: flex-start;
        padding: 15px;
        padding-right: 80px; /* ให้พื้นที่สำหรับสถานะ */
    }
    
    .student-status {
        top: 15px;
        right: 10px;
        padding: 3px 6px;
        font-size: 10px;
        max-width: 70px;
    }
    
    .student-status .material-icons {
        font-size: 12px;
        margin-right: 2px;
    }
    
    .student-avatar {
        width: 45px;
        height: 45px;
        font-size: 18px;
        margin-right: 10px;
        margin-bottom: 0;
    }
    
    .student-name {
        font-size: 14px;
        margin-bottom: 3px;
    }
    
    .student-class, .student-id {
        font-size: 11px;
        margin-bottom: 1px;
    }
    
    .search-section {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-button {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }
    
    .attendance-summary {
        justify-content: space-around;
    }
    
    .student-actions {
        flex-direction: column;
    }
    
    .view-details-button, .remove-student-button {
        width: 100%;
    }
    
    .search-input-large {
        flex-direction: column;
        padding: 10px;
    }
    
    .search-button {
        width: 100%;
        border-radius: 8px;
        margin-top: 10px;
        justify-content: center;
    }
    
    .search-result-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .search-result-item form {
        width: 100%;
    }
    
    .search-result-item .add-student-button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php 
} // ปิด else ของการตรวจสอบว่าเป็นการดูรายละเอียดนักเรียนหรือไม่
?>