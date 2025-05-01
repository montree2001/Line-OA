<!-- แจ้งเตือน -->
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



<!-- ข้อมูลนักเรียน -->
<div class="student-section">
    <div class="section-header">
        <h2>บุตรของฉัน</h2>
        <a href="students.php" class="view-all">ดูทั้งหมด</a>
    </div>
    
    <div class="student-cards">
        <?php if(isset($students) && !empty($students)): ?>
            <?php foreach($students as $student): ?>
                <div class="student-card">
                    <div class="header">
                        <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                        <div class="student-info">
                            <div class="student-name"><?php echo $student['name']; ?></div>
                            <div class="student-class"><?php echo $student['class']; ?> เลขที่ <?php echo $student['number']; ?></div>
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
        <?php else: ?>
            <!-- ข้อมูลตัวอย่างกรณีไม่มีข้อมูลจริง -->
            <div class="student-card">
                <div class="header">
                    <div class="student-avatar">อ</div>
                    <div class="student-info">
                        <div class="student-name">นายเอกชัย รักเรียน</div>
                        <div class="student-class">ม.6/1 เลขที่ 15</div>
                    </div>
                    <div class="student-status">
                        <span class="material-icons">check_circle</span>
                        มาเรียน
                    </div>
                </div>
                
                <div class="attendance-details">
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันเข้าแถว:</div>
                        <div class="attendance-value">97 วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันขาดแถว:</div>
                        <div class="attendance-value">0 วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">อัตราการเข้าแถว:</div>
                        <div class="attendance-value good">100%</div>
                    </div>
                </div>
            </div>
            
            <div class="student-card">
                <div class="header">
                    <div class="student-avatar">ส</div>
                    <div class="student-info">
                        <div class="student-name">นางสาวสมหญิง รักเรียน</div>
                        <div class="student-class">ม.4/2 เลขที่ 8</div>
                    </div>
                    <div class="student-status">
                        <span class="material-icons">check_circle</span>
                        มาเรียน
                    </div>
                </div>
                
                <div class="attendance-details">
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันเข้าแถว:</div>
                        <div class="attendance-value">95 วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">จำนวนวันขาดแถว:</div>
                        <div class="attendance-value">2 วัน</div>
                    </div>
                    <div class="attendance-item">
                        <div class="attendance-label">อัตราการเข้าแถว:</div>
                        <div class="attendance-value good">97.9%</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
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
        <!-- ข้อมูลตัวอย่างกรณีไม่มีข้อมูลจริง -->
        <div class="activity-item">
            <div class="activity-icon check-in">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="activity-content">
                <div class="activity-title">นายเอกชัย รักเรียน เช็คชื่อเข้าแถว</div>
                <div class="activity-time">วันนี้, 07:45 น.</div>
            </div>
        </div>
        
        <div class="activity-item">
            <div class="activity-icon check-in">
                <span class="material-icons">check_circle</span>
            </div>
            <div class="activity-content">
                <div class="activity-title">นางสาวสมหญิง รักเรียน เช็คชื่อเข้าแถว</div>
                <div class="activity-time">วันนี้, 07:40 น.</div>
            </div>
        </div>
        
        <div class="activity-item">
            <div class="activity-icon announcement">
                <span class="material-icons">campaign</span>
            </div>
            <div class="activity-content">
                <div class="activity-title">ประกาศ: แจ้งกำหนดการสอบปลายภาค</div>
                <div class="activity-time">เมื่อวาน, 10:30 น.</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ติดต่อครูประจำชั้น -->
<div class="contact-teacher">
    <div class="section-header">
        <h2>ติดต่อครูประจำชั้น</h2>
    </div>
    
    <?php if(isset($teacher) && !empty($teacher)): ?>
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
            </div>
        </div>
    <?php else: ?>
        <!-- ข้อมูลตัวอย่างกรณีไม่มีข้อมูลจริง -->
        <div class="teacher-info">
            <div class="teacher-avatar">
                <span class="material-icons">person</span>
            </div>
            <div class="teacher-details">
                <div class="teacher-name">อาจารย์ใจดี มากเมตตา</div>
                <div class="teacher-position">ครูประจำชั้น ม.6/1</div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="contact-buttons">
        <button class="contact-button call" onclick="callTeacher()">
            <span class="material-icons">call</span> โทร
        </button>
        <button class="contact-button message" onclick="messageTeacher()">
            <span class="material-icons">chat</span> ข้อความ
        </button>
    </div>
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
        <!-- ข้อมูลตัวอย่างกรณีไม่มีข้อมูลจริง -->
        <div class="announcement-item">
            <div class="announcement-header">
                <div class="announcement-category exam">สอบ</div>
                <div class="announcement-date">14 มี.ค. 2568</div>
            </div>
            <div class="announcement-title">แจ้งกำหนดการสอบปลายภาค</div>
            <div class="announcement-text">แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2567 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น.</div>
        </div>
        
        <div class="announcement-item">
            <div class="announcement-header">
                <div class="announcement-category event">กิจกรรม</div>
                <div class="announcement-date">10 มี.ค. 2568</div>
            </div>
            <div class="announcement-title">ประชุมผู้ปกครองภาคเรียนที่ 2</div>
            <div class="announcement-text">ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน</div>
        </div>
    <?php endif; ?>
</div>