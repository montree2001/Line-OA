<?php
// ตรวจสอบว่าเป็นการดูรายละเอียดนักเรียนหรือไม่
if (isset($selected_student)) {
    // แสดงรายละเอียดของนักเรียนที่เลือก
    include 'pages/student_detail_content.php';
} else {
    // แสดงรายการนักเรียนทั้งหมด
?>

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
        <input type="text" id="search-student" placeholder="ค้นหานักเรียน...">
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
                <label for="filter-high-school">มัธยมศึกษา</label>
            </div>
            <div class="filter-item">
                <input type="checkbox" id="filter-primary-school">
                <label for="filter-primary-school">ประถมศึกษา</label>
            </div>
        </div>
    </div>
</div>

<!-- รายการนักเรียน -->
<div class="student-list">
    <?php if(isset($students) && !empty($students)): ?>
        <?php foreach($students as $student): ?>
            <div class="student-card" data-id="<?php echo $student['id']; ?>">
                <div class="student-card-header">
                    <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                    <div class="student-basic-info">
                        <div class="student-name"><?php echo $student['name']; ?></div>
                        <div class="student-class"><?php echo $student['class']; ?> เลขที่ <?php echo $student['number']; ?></div>
                        <div class="student-id">รหัสนักเรียน: <?php echo $student['student_id']; ?></div>
                    </div>
                    <div class="student-status <?php echo $student['present'] ? 'present' : 'absent'; ?>">
                        <?php if($student['present']): ?>
                            <span class="material-icons">check_circle</span>
                            <span>มาเรียน</span>
                        <?php else: ?>
                            <span class="material-icons">cancel</span>
                            <span>ขาดเรียน</span>
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
                    <a href="students.php?id=<?php echo $student['id']; ?>" class="view-details-button">
                        <span class="material-icons">visibility</span>
                        ดูรายละเอียด
                    </a>
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
                <a href="#" class="add-student-button">
                    <span class="material-icons">add_circle</span>
                    เพิ่มนักเรียน
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ปุ่มเพิ่มนักเรียน -->
<div class="floating-action-button" id="add-student-fab">
    <span class="material-icons">add</span>
</div>

<script>
// เปิด/ปิดเมนูกรอง
document.getElementById('filter-toggle').addEventListener('click', function() {
    const filterMenu = document.getElementById('filter-menu');
    filterMenu.classList.toggle('active');
});

// ค้นหานักเรียน
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

// เพิ่มนักเรียนใหม่
document.getElementById('add-student-fab').addEventListener('click', function() {
    // ในการใช้งานจริงควรนำไปยังหน้าเพิ่มนักเรียน
    alert('กำลังนำไปยังหน้าเพิ่มนักเรียน');
    // window.location.href = 'add_student.php';
});

// ตรวจสอบการคลิกที่การ์ดนักเรียน
document.querySelectorAll('.student-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // ถ้าคลิกที่ปุ่มดูรายละเอียด ให้ดำเนินการตามปกติ
        if (e.target.closest('.view-details-button')) {
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
    margin-bottom: 80px; /* ให้มีพื้นที่ด้านล่างสำหรับ floating button */
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
    display: flex;
    align-items: center;
    font-size: 14px;
    font-weight: 500;
    padding: 5px 10px;
    border-radius: 20px;
}

.student-status.present {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.student-status.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.student-status .material-icons {
    font-size: 16px;
    margin-right: 5px;
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

.view-details-button {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color-light);
    color: var(--primary-color);
    padding: 10px;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: background-color 0.2s;
}

.view-details-button:hover {
    background-color: #e8dbef;
}

.view-details-button .material-icons {
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
}

.add-student-button .material-icons {
    margin-right: 5px;
}

.floating-action-button {
    position: fixed;
    bottom: 80px; /* ให้อยู่เหนือ bottom-nav */
    right: 20px;
    width: 56px;
    height: 56px;
    border-radius: 28px;
    background-color: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(142, 36, 170, 0.4);
    cursor: pointer;
    z-index: 100;
    transition: transform 0.2s, box-shadow 0.2s;
}

.floating-action-button:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 15px rgba(142, 36, 170, 0.5);
}

.floating-action-button .material-icons {
    font-size: 24px;
}

@media (max-width: 768px) {
    .search-section {
        flex-direction: column;
        gap: 10px;
    }
    
    .filter-button {
        width: 100%;
        justify-content: center;
        padding: 10px;
    }
    
    .student-card-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding-top: 25px;
    }
    
    .student-status {
        top: 10px;
        right: 10px;
        font-size: 12px;
        padding: 4px 8px;
    }
    
    .student-avatar {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .student-basic-info {
        margin-bottom: 10px;
    }
    
    .attendance-summary {
        justify-content: space-around;
    }
    
    .view-details-button {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .student-name {
        font-size: 16px;
    }
    
    .student-class, .student-id {
        font-size: 12px;
    }
    
    .attendance-value {
        font-size: 18px;
    }
    
    .attendance-label {
        font-size: 11px;
    }
    
    .floating-action-button {
        width: 48px;
        height: 48px;
        bottom: 70px;
    }
}
</style>

<?php 
} // ปิด else ของการตรวจสอบว่าเป็นการดูรายละเอียดนักเรียนหรือไม่
?>