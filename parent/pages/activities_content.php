<!-- แจ้งเตือน -->
<div class="notification-banner">
    <span class="material-icons icon">bookmark</span>
    <div class="content">
        <div class="title">กิจกรรมทั้งหมด</div>
        <div class="message">รายการกิจกรรมการเข้าแถวทั้งหมดของนักเรียนในความดูแลของคุณ</div>
    </div>
</div>

<!-- ตัวกรองและการค้นหา -->
<div class="filter-section">
    <form method="get" action="activities.php" class="filter-form">
        <div class="filter-row">
            <div class="filter-group">
                <label for="student">นักเรียน:</label>
                <select id="student" name="student" class="filter-select">
                    <option value="0" <?php echo $filter_student == 0 ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <?php foreach($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>" <?php echo $filter_student == $student['id'] ? 'selected' : ''; ?>>
                            <?php echo $student['name']; ?> (<?php echo $student['class']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="type">ประเภท:</label>
                <select id="type" name="type" class="filter-select">
                    <option value="all" <?php echo $filter_type == 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                    <option value="present" <?php echo $filter_type == 'present' ? 'selected' : ''; ?>>เข้าแถว</option>
                    <option value="absent" <?php echo $filter_type == 'absent' ? 'selected' : ''; ?>>ขาดแถว</option>
                    <option value="late" <?php echo $filter_type == 'late' ? 'selected' : ''; ?>>มาสาย</option>
                    <option value="leave" <?php echo $filter_type == 'leave' ? 'selected' : ''; ?>>ลา</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="period">ช่วงเวลา:</label>
                <select id="period" name="period" class="filter-select">
                    <option value="today" <?php echo $filter_period == 'today' ? 'selected' : ''; ?>>วันนี้</option>
                    <option value="week" <?php echo $filter_period == 'week' ? 'selected' : ''; ?>>สัปดาห์นี้</option>
                    <option value="month" <?php echo $filter_period == 'month' ? 'selected' : ''; ?>>เดือนนี้</option>
                    <option value="semester" <?php echo $filter_period == 'semester' ? 'selected' : ''; ?>>ภาคเรียนนี้</option>
                    <option value="all" <?php echo $filter_period == 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                </select>
            </div>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="filter-button">
                <span class="material-icons">filter_list</span>
                กรองข้อมูล
            </button>
            
            <a href="activities.php" class="reset-button">
                <span class="material-icons">refresh</span>
                รีเซ็ต
            </a>
        </div>
    </form>
</div>

<!-- แสดงข้อมูลสรุป -->
<div class="summary-section">
    <div class="summary-card">
        <div class="summary-icon">
            <span class="material-icons">summarize</span>
        </div>
        <div class="summary-content">
            <div class="summary-title">จำนวนกิจกรรมทั้งหมด</div>
            <div class="summary-value"><?php echo number_format($total_items); ?> รายการ</div>
        </div>
    </div>
    
    <?php if($filter_student > 0 && isset($students)): ?>
        <?php 
        // หาข้อมูลนักเรียนที่เลือก
        $selected_student = null;
        foreach($students as $student) {
            if($student['id'] == $filter_student) {
                $selected_student = $student;
                break;
            }
        }
        ?>
        <?php if($selected_student): ?>
            <div class="summary-card">
                <div class="summary-icon student">
                    <span class="material-icons">person</span>
                </div>
                <div class="summary-content">
                    <div class="summary-title">นักเรียนที่เลือก</div>
                    <div class="summary-value"><?php echo $selected_student['name']; ?></div>
                    <div class="summary-subtitle"><?php echo $selected_student['class']; ?></div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <div class="summary-card">
        <div class="summary-icon period">
            <span class="material-icons">date_range</span>
        </div>
        <div class="summary-content">
            <div class="summary-title">ช่วงเวลา</div>
            <div class="summary-value">
                <?php 
                switch($filter_period) {
                    case 'today':
                        echo 'วันนี้ (' . date('d/m/Y') . ')';
                        break;
                    case 'week':
                        $start_of_week = date('d/m/Y', strtotime('this week monday'));
                        $end_of_week = date('d/m/Y', strtotime('this week sunday'));
                        echo 'สัปดาห์นี้ (' . $start_of_week . ' - ' . $end_of_week . ')';
                        break;
                    case 'month':
                        echo 'เดือน' . date('F Y');
                        break;
                    case 'semester':
                        echo 'ภาคเรียนนี้';
                        break;
                    case 'all':
                        echo 'ทั้งหมด';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- รายการกิจกรรม -->
<div class="activities-list">
    <?php if(!empty($activities)): ?>
        <?php foreach($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon <?php echo $activity['type']; ?>">
                    <span class="material-icons"><?php echo $activity['icon']; ?></span>
                </div>
                
                <div class="activity-content">
                    <div class="activity-header">
                        <div class="activity-title">
                            <?php echo $activity['student_name']; ?> 
                            <span class="status-badge <?php echo $activity['status_class']; ?>">
                                <?php echo $activity['status_text']; ?>
                            </span>
                        </div>
                        <div class="activity-time" data-timestamp="<?php echo $activity['timestamp']; ?>">
                            <?php echo $activity['time_text']; ?>
                        </div>
                    </div>
                    
                    <div class="activity-details">
                        <div class="detail-item">
                            <span class="detail-label">นักเรียน:</span>
                            <span class="detail-value"><?php echo $activity['student_name']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">ชั้นเรียน:</span>
                            <span class="detail-value"><?php echo $activity['student_details']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">วันที่:</span>
                            <span class="detail-value"><?php echo $activity['date']; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">เวลา:</span>
                            <span class="detail-value"><?php echo $activity['time']; ?> น.</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">วิธีเช็คชื่อ:</span>
                            <span class="detail-value"><?php echo $activity['method_text']; ?></span>
                        </div>
                        <?php if(!empty($activity['remarks'])): ?>
                            <div class="detail-item remarks">
                                <span class="detail-label">หมายเหตุ:</span>
                                <span class="detail-value"><?php echo $activity['remarks']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="activity-action">
                    <a href="students.php?id=<?php echo $activity['student_id']; ?>" class="view-student-button">
                        <span class="material-icons">visibility</span>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- การแบ่งหน้า -->
        <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php if($current_page > 1): ?>
                    <a href="activities.php?student=<?php echo $filter_student; ?>&type=<?php echo $filter_type; ?>&period=<?php echo $filter_period; ?>&page=<?php echo $current_page - 1; ?>" class="pagination-button prev">
                        <span class="material-icons">chevron_left</span>
                        ก่อนหน้า
                    </a>
                <?php endif; ?>
                
                <div class="pagination-numbers">
                    <?php for($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <a href="activities.php?student=<?php echo $filter_student; ?>&type=<?php echo $filter_type; ?>&period=<?php echo $filter_period; ?>&page=<?php echo $i; ?>" 
                           class="pagination-number <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                
                <?php if($current_page < $total_pages): ?>
                    <a href="activities.php?student=<?php echo $filter_student; ?>&type=<?php echo $filter_type; ?>&period=<?php echo $filter_period; ?>&page=<?php echo $current_page + 1; ?>" class="pagination-button next">
                        ถัดไป
                        <span class="material-icons">chevron_right</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="no-data">
            <div class="no-data-icon">
                <span class="material-icons">event_busy</span>
            </div>
            <div class="no-data-message">ไม่พบข้อมูลกิจกรรมตามเงื่อนไขที่เลือก</div>
            <div class="no-data-action">
                <a href="activities.php" class="reset-button">
                    <span class="material-icons">refresh</span>
                    รีเซ็ตตัวกรอง
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- เพิ่ม CSS อินไลน์ -->
<style>
/* สไตล์สำหรับหน้ากิจกรรม */
.filter-section {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
}

.filter-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-group label {
    display: block;
    font-weight: 500;
    margin-bottom: 5px;
    color: var(--text-dark);
    font-size: 14px;
}

.filter-select {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='black' width='18px' height='18px'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 20px;
}

.filter-actions {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.filter-button, .reset-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: background-color 0.2s;
}

.filter-button {
    background-color: var(--primary-color);
    color: white;
}

.filter-button:hover {
    background-color: var(--primary-color-dark);
}

.reset-button {
    background-color: var(--bg-light);
    color: var(--text-dark);
    text-decoration: none;
}

.reset-button:hover {
    background-color: #e5e5e5;
}

.filter-button .material-icons, .reset-button .material-icons {
    font-size: 18px;
    margin-right: 5px;
}

/* สรุปข้อมูล */
.summary-section {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.summary-card {
    flex: 1;
    min-width: 200px;
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
}

.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--primary-color-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.summary-icon .material-icons {
    color: var(--primary-color);
    font-size: 24px;
}

.summary-icon.student {
    background-color: var(--secondary-color-light);
}

.summary-icon.student .material-icons {
    color: var(--secondary-color);
}

.summary-icon.period {
    background-color: var(--warning-color-light);
}

.summary-icon.period .material-icons {
    color: var(--warning-color);
}

.summary-content {
    flex: 1;
}

.summary-title {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 5px;
}

.summary-value {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-dark);
}

.summary-subtitle {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 3px;
}

/* รายการกิจกรรม */
.activities-list {
    margin-bottom: 30px;
}

.activity-item {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
    box-shadow: var(--card-shadow);
    display: flex;
    position: relative;
    transition: transform 0.2s, box-shadow 0.2s;
}

.activity-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.activity-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: var(--secondary-color);
    flex-shrink: 0;
}

.activity-icon.check-in {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.activity-icon.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.activity-icon.late {
    background-color: var(--warning-color-light);
    color: var(--warning-color);
}

.activity-icon.leave {
    background-color: #f0f0f0;
    color: var(--text-muted);
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 16px;
    padding-right: 40px;
}

.status-badge {
    display: inline-block;
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 12px;
    margin-left: 5px;
    font-weight: 500;
}

.status-badge.present {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.status-badge.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.status-badge.late {
    background-color: var(--warning-color-light);
    color: var(--warning-color);
}

.status-badge.leave {
    background-color: #f0f0f0;
    color: var(--text-muted);
}

.activity-time {
    font-size: 12px;
    color: var(--text-muted);
    white-space: nowrap;
}

.activity-details {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    background-color: var(--bg-light);
    border-radius: 8px;
    padding: 10px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 12px;
    color: var(--text-light);
    margin-bottom: 2px;
}

.detail-value {
    font-size: 14px;
    color: var(--text-dark);
    font-weight: 500;
}

.detail-item.remarks {
    grid-column: 1 / -1;
}

.activity-action {
    position: absolute;
    top: 15px;
    right: 15px;
}

.view-student-button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: var(--primary-color-light);
    color: var(--primary-color);
    text-decoration: none;
    transition: background-color 0.2s;
}

.view-student-button:hover {
    background-color: var(--primary-color);
    color: white;
}

/* การแบ่งหน้า */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.pagination-button {
    display: flex;
    align-items: center;
    padding: 8px 15px;
    border-radius: 6px;
    background-color: white;
    color: var(--text-dark);
    font-weight: 500;
    text-decoration: none;
    box-shadow: var(--card-shadow);
    transition: background-color 0.2s;
}

.pagination-button:hover {
    background-color: var(--primary-color-light);
    color: var(--primary-color);
}

.pagination-button .material-icons {
    font-size: 18px;
}

.pagination-button.prev .material-icons {
    margin-right: 5px;
}

.pagination-button.next .material-icons {
    margin-left: 5px;
}

.pagination-numbers {
    display: flex;
    gap: 5px;
}

.pagination-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background-color: white;
    color: var(--text-dark);
    font-weight: 500;
    text-decoration: none;
    box-shadow: var(--card-shadow);
    transition: background-color 0.2s;
}

.pagination-number:hover {
    background-color: var(--primary-color-light);
    color: var(--primary-color);
}

.pagination-number.active {
    background-color: var(--primary-color);
    color: white;
}

/* สำหรับกรณีไม่มีข้อมูล */
.no-data {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 20px;
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #e0e0e0;
}

.no-data-message {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 15px;
}

.no-data-action {
    margin-top: 10px;
}

/* การตอบสนองต่อขนาดหน้าจอ */
@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .summary-section {
        flex-direction: column;
    }
    
    .activity-details {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        flex-direction: column;
    }
    
    .activity-icon {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .activity-header {
        flex-direction: column;
    }
    
    .activity-title {
        margin-right: 0;
        margin-bottom: 10px;
        padding-right: 0;
    }
    
    .activity-action {
        position: static;
        margin-top: 15px;
        display: flex;
        justify-content: flex-end;
    }
    
    .pagination-numbers {
        display: none;
    }
}

@media (max-width: 480px) {
    .filter-actions {
        flex-direction: column;
    }
    
    .summary-card {
        min-width: 100%;
    }
    
    .activity-title {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .status-badge {
        margin-left: 0;
        margin-top: 5px;
    }
}
</style>

<!-- JavaScript สำหรับหน้ากิจกรรม -->
<script>
// อัปเดตเวลาอัตโนมัติเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าเหตุการณ์เมื่อตัวกรองเปลี่ยนแปลง
    setupFilterChange();
    
    // อัปเดตเวลาแบบ Relative ทุกนาที
    setInterval(updateRelativeTimes, 60000);
    updateRelativeTimes(); // เรียกครั้งแรกทันที
    
    // ตั้งค่าการแสดงรายละเอียดเพิ่มเติม
    setupExpandableDetails();
});

// ตั้งค่าเหตุการณ์เมื่อตัวกรองเปลี่ยนแปลง
function setupFilterChange() {
    const filterSelects = document.querySelectorAll('.filter-select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // ส่งฟอร์มเมื่อมีการเปลี่ยนแปลงตัวกรอง
            document.querySelector('.filter-form').submit();
        });
    });
}

// อัปเดตเวลาแบบ Relative (เช่น "2 นาทีที่แล้ว", "เมื่อวาน")
function updateRelativeTimes() {
    const timeElements = document.querySelectorAll('.activity-time[data-timestamp]');
    
    timeElements.forEach(el => {
        const timestamp = parseInt(el.getAttribute('data-timestamp'));
        if (timestamp) {
            el.textContent = getRelativeTimeString(timestamp);
        }
    });
}

// แปลงเวลาเป็นข้อความแบบ Relative
function getRelativeTimeString(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;
    
    if (diff < 60) {
        return 'เมื่อสักครู่';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `${minutes} นาทีที่แล้ว`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} ชั่วโมงที่แล้ว`;
    } else if (diff < 172800) {
        return 'เมื่อวาน';
    } else {
        // สร้างข้อความวันที่
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }
}

// ตั้งค่าการแสดงรายละเอียดเพิ่มเติม
function setupExpandableDetails() {
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach(item => {
        const details = item.querySelector('.activity-details');
        
        // สร้างปุ่มขยาย/ย่อรายละเอียด
        const toggleButton = document.createElement('button');
        toggleButton.className = 'toggle-details';
        toggleButton.innerHTML = '<span class="material-icons">expand_more</span>';
        toggleButton.setAttribute('aria-label', 'แสดงรายละเอียดเพิ่มเติม');
        
        // เพิ่มสไตล์ CSS สำหรับปุ่ม
        toggleButton.style.border = 'none';
        toggleButton.style.background = 'none';
        toggleButton.style.color = 'var(--text-light)';
        toggleButton.style.cursor = 'pointer';
        toggleButton.style.display = 'flex';
        toggleButton.style.alignItems = 'center';
        toggleButton.style.justifyContent = 'center';
        toggleButton.style.width = '100%';
        toggleButton.style.padding = '5px 0';
        toggleButton.style.marginTop = '10px';
        
        // แทรกปุ่มเข้าไปหลังรายละเอียด
        details.parentNode.insertBefore(toggleButton, details.nextSibling);
        
        // ซ่อนรายละเอียดเริ่มต้น
        details.style.display = 'none';
        
        // ตั้งค่าเหตุการณ์เมื่อคลิกปุ่ม
        toggleButton.addEventListener('click', function() {
            const isExpanded = details.style.display !== 'none';
            details.style.display = isExpanded ? 'none' : 'grid';
            
            // เปลี่ยนไอคอน
            this.querySelector('.material-icons').textContent = isExpanded ? 'expand_more' : 'expand_less';
            
            // เปลี่ยนคำอธิบาย
            this.setAttribute('aria-label', isExpanded ? 'แสดงรายละเอียดเพิ่มเติม' : 'ซ่อนรายละเอียด');
        });
    });
}
</script>