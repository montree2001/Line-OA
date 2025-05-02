<?php if(isset($latest_check_in) && !empty($latest_check_in)): ?>
<div class="notification-banner <?php echo isset($notification_type) ? $notification_type : 'success'; ?>">
    <span class="material-icons icon">
        <?php 
        // กำหนดไอคอนตามประเภทการแจ้งเตือน
        if (isset($notification_type)) {
            switch ($notification_type) {
                case 'success':
                    echo 'check_circle';
                    break;
                case 'danger':
                    echo 'cancel';
                    break;
                case 'warning':
                    echo 'warning';
                    break;
                default:
                    echo 'info';
            }
        } else {
            echo 'check_circle'; // ไอคอนเริ่มต้น
        }
        ?>
    </span>

 



    <div class="content">
        <div class="title">
            <?php 
            // กำหนดหัวข้อตามประเภทการแจ้งเตือน
            if (isset($notification_type)) {
                switch ($notification_type) {
                    case 'success':
                        echo 'บุตรของท่านมาเรียนวันนี้';
                        break;
                    case 'danger':
                        echo 'บุตรของท่านขาดเรียนวันนี้';
                        break;
                    case 'warning':
                        if (strpos($latest_check_in, 'สาย') !== false) {
                            echo 'บุตรของท่านมาสายวันนี้';
                        } else if (strpos($latest_check_in, 'ลา') !== false) {
                            echo 'บุตรของท่านลาการเข้าแถววันนี้';
                        } else {
                            echo 'แจ้งเตือนการเข้าแถว';
                        }
                        break;
                    default:
                        echo 'แจ้งเตือนการเข้าแถว';
                }
            } else {
                echo 'บุตรของท่านมาเรียนวันนี้';
            }
            ?>
        </div>
        <div class="message"><?php echo $latest_check_in; ?></div>
    </div>
</div>
<?php endif; ?>

<div class="section-header">
        <h2>นักเรียนในความดูแล</h2>
        <a href="students.php" class="view-all">ดูทั้งหมด</a>
    </div>

<div class="student-cards">
    <?php if(isset($students) && !empty($students)): ?>
        <?php foreach($students as $student): ?>
            <div class="student-card" data-id="<?php echo $student['id']; ?>">
                <div class="header">
                    <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                    <div class="student-info">
                        <div class="student-name"><?php echo $student['name']; ?></div>
                        <div class="student-class"><?php echo $student['class']; ?> <?php echo isset($student['number']) && $student['number'] > 0 ? 'เลขที่ ' . $student['number'] : ''; ?></div>
                    </div>
                    <div class="student-status <?php echo $student['present'] ? '' : 'absent'; ?>">
                        <span class="material-icons"><?php echo $student['present'] ? 'check_circle' : 'cancel'; ?></span>
                        <?php 
                        if ($student['has_today_data']) {
                            switch ($student['today_status']) {
                                case 'present':
                                    echo 'มาเรียน';
                                    break;
                                case 'late':
                                    echo 'มาสาย';
                                    break;
                                case 'absent':
                                    echo 'ขาดเรียน';
                                    break;
                                case 'leave':
                                    echo 'ลา';
                                    break;
                                default:
                                    echo $student['present'] ? 'มาเรียน' : 'ขาดเรียน';
                            }
                        } else {
                            echo 'รอเช็คชื่อ';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="attendance-details">
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันเข้าแถว:</div>
                        <div class="attendance-value"><?php echo $student['attendance_days']; ?> วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันขาดแถว:</div>
                        <div class="attendance-value"><?php echo $student['absent_days']; ?> วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">อัตราการเข้าแถว:</div>
                        <div class="attendance-value <?php echo $student['attendance_percentage'] >= 90 ? 'good' : ($student['attendance_percentage'] >= 80 ? 'warning' : 'danger'); ?>">
                            <?php echo $student['attendance_percentage']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<!-- กิจกรรมล่าสุด -->
<div class="recent-activities">
    <div class="section-header">
        <h2>กิจกรรมล่าสุด</h2>
        <a href="activities.php" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <?php if(isset($activities) && !empty($activities)): ?>
        <?php foreach($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon <?php echo $activity['type']; ?>">
                    <span class="material-icons"><?php echo $activity['icon']; ?></span>
                </div>
                <div class="activity-content">
                    <div class="activity-title"><?php echo $activity['title']; ?></div>
                    <div class="activity-time"><?php echo $activity['time']; ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data small">
            <div class="no-data-icon">
                <span class="material-icons">event_busy</span>
            </div>
            <div class="no-data-message">ไม่พบข้อมูลกิจกรรมล่าสุด</div>
        </div>
    <?php endif; ?>
</div>

<!-- ครูที่ปรึกษา -->
<div class="contact-teacher-section">
    <div class="section-header">
        <h2>ติดต่อครูที่ปรึกษา</h2>
        <a href="teachers.php" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <?php if(isset($teachers) && !empty($teachers)): ?>
        <?php foreach($teachers as $index => $teacher): ?>
            <?php if($index < 2): // แสดงเฉพาะ 2 คนแรกในหน้าหลัก ?>
                <div class="contact-teacher">
                    <div class="teacher-info">
                        <div class="teacher-avatar">
                            <?php if(isset($teacher['avatar']) && !empty($teacher['avatar'])): ?>
                                <img src="<?php echo $teacher['avatar']; ?>" alt="<?php echo $teacher['name']; ?>">
                            <?php else: ?>
                                <span class="material-icons">person</span>
                            <?php endif; ?>
                        </div>
                        <div class="teacher-details">
                            <div class="teacher-name"><?php echo $teacher['name']; ?></div>
                            <div class="teacher-position"><?php echo $teacher['position']; ?></div>
                            <div class="teacher-students">
                                <div class="students-label">นักเรียนในความดูแล:</div>
                                <div class="students-list">
                                    <?php echo implode(', ', $teacher['students']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="contact-buttons">
                        <a href="tel:<?php echo $teacher['phone']; ?>" class="contact-button call">
                            <span class="material-icons">call</span> โทร
                        </a>
                        <a href="messages.php?teacher=<?php echo $teacher['id']; ?>" class="contact-button message">
                            <span class="material-icons">chat</span> ข้อความ
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if(count($teachers) > 2): ?>
            <div class="more-teachers">
                <a href="teachers.php" class="view-more-button">
                    <span class="material-icons">people</span>
                    ดูครูที่ปรึกษาทั้งหมด (<?php echo count($teachers); ?> คน)
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-data small">
            <div class="no-data-icon">
                <span class="material-icons">person_off</span>
            </div>
            <div class="no-data-message">ไม่พบข้อมูลครูที่ปรึกษา</div>
        </div>
    <?php endif; ?>
</div>

<!-- ประกาศและข่าวสาร -->
<div class="announcements">
    <div class="section-header">
        <h2>ประกาศและข่าวสาร</h2>
        <a href="announcements.php" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <?php if(isset($announcements) && !empty($announcements)): ?>
        <?php foreach($announcements as $announcement): ?>
            <div class="announcement-item">
                <div class="announcement-header">
                    <div class="announcement-category <?php echo $announcement['category_class']; ?>"><?php echo $announcement['category']; ?></div>
                    <div class="announcement-date"><?php echo $announcement['date']; ?></div>
                </div>
                <div class="announcement-title"><?php echo $announcement['title']; ?></div>
                <div class="announcement-text"><?php echo $announcement['content']; ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-data small">
            <div class="no-data-icon">
                <span class="material-icons">campaign_off</span>
            </div>
            <div class="no-data-message">ไม่พบข้อมูลประกาศและข่าวสาร</div>
        </div>
    <?php endif; ?>
</div>

<style>
/* เพิ่มสไตล์สำหรับหน้า Dashboard */
.no-data {
    text-align: center;
    padding: 40px 20px;
    background-color: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    margin-bottom: 20px;
}

.no-data.small {
    padding: 20px;
}

.no-data-icon {
    margin-bottom: 15px;
}

.no-data-icon .material-icons {
    font-size: 48px;
    color: #e0e0e0;
}

.no-data.small .no-data-icon .material-icons {
    font-size: 36px;
}

.no-data-message {
    font-size: 16px;
    color: var(--text-light);
    margin-bottom: 15px;
}

.no-data-action {
    margin-top: 10px;
}

.add-student-button, .view-more-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
}

.add-student-button .material-icons, .view-more-button .material-icons {
    margin-right: 5px;
}

.teacher-students {
    margin-top: 8px;
    font-size: 14px;
    color: var(--text-light);
}

.students-label {
    font-weight: 500;
    margin-bottom: 3px;
}

.students-list {
    color: var(--text-light);
}

.more-teachers {
    text-align: center;
    margin-top: 15px;
}

.contact-teacher-section {
    margin-bottom: 20px;
}

.contact-teacher {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: var(--card-shadow);
}

@media (max-width: 768px) {
    .teacher-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .teacher-avatar {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .teacher-students {
        text-align: center;
    }
}
</style>