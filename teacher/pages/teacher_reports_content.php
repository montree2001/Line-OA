<div class="header">
    <a href="#" onclick="goBack()" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>รายงานการเข้าแถว</h1>
    <div class="header-icon" onclick="toggleOptions()">
        <span class="material-icons">more_vert</span>
    </div>
</div>

<div class="container">
    <!-- ตัวเลือกเปลี่ยนห้องเรียน -->
    <div class="class-selector">
        <label for="class-select">เลือกห้องเรียน:</label>
        <select id="class-select" onchange="changeClass(this.value)">
            <?php foreach ($teacher_classes as $class): ?>
                <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $current_class_id) ? 'selected' : ''; ?>>
                    <?php echo $class['name']; ?> (<?php echo $class['total_students']; ?> คน)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ข้อมูลชั้นเรียน -->
    <div class="class-info">
        <div class="class-details">
            <h2><?php echo $current_class['name']; ?></h2>
            <p>นักเรียนทั้งหมด <?php echo $current_class['total_students']; ?> คน</p>
        </div>
        <div class="date-select">
            <label>เลือกเดือน:</label>
            <select id="month-select" onchange="changeMonth()">
                <?php foreach ($monthly_attendance as $month): ?>
                    <option value="<?php echo $month['value']; ?>" <?php echo ($month['value'] == $current_month) ? 'selected' : ''; ?>>
                        <?php echo $month['month']; ?> <?php echo $current_year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- สถิติการเข้าแถว -->
    <div class="stats-container">
        <div class="stat-card blue">
            <div class="stat-value"><?php echo $current_class['total_students']; ?></div>
            <div class="stat-label">นักเรียนทั้งหมด</div>
        </div>
        <div class="stat-card green">
            <div class="stat-value"><?php echo $attendance_stats['average_rate']; ?>%</div>
            <div class="stat-label">อัตราการเข้าแถวเฉลี่ย</div>
        </div>
        <div class="stat-card red">
            <div class="stat-value"><?php echo $attendance_stats['problem_count']; ?></div>
            <div class="stat-label">นักเรียนที่มีปัญหา</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-value"><?php echo $attendance_stats['school_days']; ?></div>
            <div class="stat-label">วันเรียนในเดือนนี้</div>
        </div>
    </div>

    <!-- ตารางสรุปเปอร์เซ็นต์การเข้าแถว -->
    <div class="chart-card">
        <div class="chart-header">
            <div class="chart-title">อัตราการเข้าแถวรายวัน (7 วันล่าสุด)</div>
            <div class="chart-controls">
                <button class="chart-button" onclick="downloadDailyChart()">
                    <span class="material-icons">file_download</span> ดาวน์โหลด
                </button>
                <button class="chart-button" onclick="printDailyChart()">
                    <span class="material-icons">print</span> พิมพ์
                </button>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-bars">
                <?php foreach ($daily_attendance as $day): ?>
                <div class="chart-bar" style="height: <?php echo $day['percentage']; ?>%">
                    <div class="chart-bar-value"><?php echo $day['percentage']; ?>%</div>
                    <div class="chart-bar-label"><?php echo $day['day']; ?><br><?php echo $day['date']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-x-axis"></div>
        </div>
    </div>

    <!-- แถบควบคุม -->
    <div class="control-bar">
        <div class="control-title">รายงานการเข้าแถวของนักเรียน</div>
        <div class="control-actions">
            <button class="control-button" onclick="downloadReport()">
                <span class="material-icons">file_download</span> ดาวน์โหลดรายงาน
            </button>
            <button class="control-button orange" onclick="notifyParents()">
                <span class="material-icons">notification_important</span> แจ้งเตือนผู้ปกครอง
            </button>
        </div>
    </div>

    <!-- แท็บเมนู -->
    <div class="tab-menu">
        <div class="tab-button active" onclick="switchTab('table')">รายการ</div>
        <div class="tab-button" onclick="switchTab('graph')">กราฟ</div>
        <div class="tab-button" onclick="switchTab('calendar')">ปฏิทิน</div>
    </div>

    <!-- ตารางรายชื่อนักเรียน -->
    <div class="student-table-card" id="table-view">
        <div class="table-header">
            <div class="table-title">รายชื่อนักเรียน</div>
            <div class="search-bar">
                <span class="material-icons">search</span>
                <input type="text" placeholder="ค้นหานักเรียน..." id="student-search" onkeyup="searchStudents()">
            </div>
        </div>
        
        <table class="student-table">
            <thead>
                <tr>
                    <th>เลขที่</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>จำนวนวันเข้าแถว</th>
                    <th>อัตราการเข้าแถว</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo $student['number']; ?></td>
                    <td><?php echo $student['name']; ?></td>
                    <td><?php echo $student['attendance_days']; ?></td>
                    <td><span class="attendance-percent <?php echo $student['status']; ?>"><?php echo $student['percentage']; ?>%</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-button" title="ดูรายละเอียด" onclick="viewStudentDetail(<?php echo $student['id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="action-button" title="ส่งข้อความถึงผู้ปกครอง" onclick="contactParent(<?php echo $student['id']; ?>)">
                                <span class="material-icons">mail</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- แสดงเป็นกราฟ -->
    <div class="graph-card" id="graph-view" style="display: none;">
        <div class="graph-header">
            <div class="graph-title">อัตราการเข้าแถวของนักเรียนรายคน</div>
            <div class="chart-controls">
                <button class="chart-button" onclick="downloadStudentChart()">
                    <span class="material-icons">file_download</span> ดาวน์โหลด
                </button>
                <button class="chart-button" onclick="printStudentChart()">
                    <span class="material-icons">print</span> พิมพ์
                </button>
            </div>
        </div>
        
        <div class="graph-container">
            <div class="student-bars">
                <?php foreach ($students as $index => $student): ?>
                <?php if ($index < 10): // แสดงเฉพาะ 10 คนแรก ?>
                <div class="student-bar-container">
                    <div class="student-bar-label"><?php echo $student['number']; ?>. <?php echo substr($student['name'], 0, 15); ?></div>
                    <div class="student-bar-chart">
                        <div class="student-bar <?php echo $student['status']; ?>" style="width: <?php echo $student['percentage']; ?>%">
                            <span class="student-bar-value"><?php echo $student['percentage']; ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-color good"></div>
                    <span>ดี (มากกว่า 80%)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color warning"></div>
                    <span>เตือน (70-80%)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color danger"></div>
                    <span>อันตราย (น้อยกว่า 70%)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- แสดงเป็นปฏิทิน -->
    <div class="calendar-card" id="calendar-view" style="display: none;">
        <div class="calendar-header">
            <div class="calendar-title">ปฏิทินการเข้าแถวประจำเดือน</div>
            <div class="calendar-nav">
                <button class="nav-button" onclick="prevMonth()">
                    <span class="material-icons">chevron_left</span>
                </button>
                <span class="calendar-month">
                    <?php 
                    $month_names = [
                        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 
                        4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน', 
                        7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 
                        10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
                    ];
                    echo $month_names[$current_month] . ' ' . $current_year; 
                    ?>
                </span>
                <button class="nav-button" onclick="nextMonth()">
                    <span class="material-icons">chevron_right</span>
                </button>
            </div>
        </div>
        
        <div class="calendar-grid">
            <!-- วันในสัปดาห์ -->
            <div class="calendar-day-header">จ</div>
            <div class="calendar-day-header">อ</div>
            <div class="calendar-day-header">พ</div>
            <div class="calendar-day-header">พฤ</div>
            <div class="calendar-day-header">ศ</div>
            <div class="calendar-day-header">ส</div>
            <div class="calendar-day-header">อา</div>
            
            <!-- วันในเดือน -->
            <?php foreach ($calendar_data as $day): ?>
            <div class="calendar-day <?php echo (!$day['current_month']) ? 'inactive' : ''; ?> <?php echo (date('j') == $day['day'] && date('n') == $day['month'] && date('Y') == $day['year']) ? 'today' : ''; ?>">
                <div class="calendar-day-number"><?php echo $day['day']; ?></div>
                <?php if ($day['is_school_day'] && $day['current_month']): ?>
                <div class="attendance-summary">
                    <div class="attendance-row">
                        <span class="attendance-label">มา:</span>
                        <span class="attendance-value good"><?php echo $day['present']; ?></span>
                    </div>
                    <div class="attendance-row">
                        <span class="attendance-label">ขาด:</span>
                        <span class="attendance-value <?php echo ($day['absent'] > 0) ? 'danger' : ''; ?>"><?php echo $day['absent']; ?></span>
                    </div>
                    <div class="attendance-row">
                        <span class="attendance-label">อัตรา:</span>
                        <span class="attendance-value <?php 
                            if ($day['percentage'] >= 90) echo 'good';
                            else if ($day['percentage'] >= 80) echo 'warning';
                            else echo 'danger';
                        ?>"><?php echo $day['percentage']; ?>%</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal แจ้งเตือนผู้ปกครอง -->
<div class="modal" id="notify-parents-modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('notify-parents-modal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แจ้งเตือนผู้ปกครอง</h2>
        
        <div class="notification-options">
            <div class="option-group">
                <label class="option-label">เลือกประเภทการแจ้งเตือน:</label>
                <div class="radio-options">
                    <label class="radio-container">
                        <input type="radio" name="notification-type" value="all" checked>
                        <span class="radio-label">แจ้งเตือนผู้ปกครองทั้งหมด</span>
                    </label>
                    <label class="radio-container">
                        <input type="radio" name="notification-type" value="problem">
                        <span class="radio-label">แจ้งเตือนเฉพาะผู้ปกครองนักเรียนที่มีปัญหา</span>
                    </label>
                </div>
            </div>
            
            <div class="option-group">
                <label class="option-label">ข้อความแจ้งเตือน:</label>
                <textarea id="message-text" rows="5" placeholder="ระบุข้อความที่ต้องการแจ้งเตือนไปยังผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครอง โรงเรียนขอแจ้งข้อมูลการเข้าแถวของนักเรียนในเดือนนี้ กรุณาตรวจสอบสถิติการเข้าแถวของนักเรียนในความปกครองของท่าน</textarea>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="modal-button cancel" onclick="closeModal('notify-parents-modal')">ยกเลิก</button>
            <button class="modal-button primary" onclick="sendNotification()">ส่งการแจ้งเตือน</button>
        </div>
    </div>
</div>

<!-- Modal ดูรายละเอียดนักเรียน -->
<div class="modal" id="student-detail-modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('student-detail-modal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">รายละเอียดการเข้าแถวของนักเรียน</h2>
        
        <div id="student-detail-content">
            <!-- จะถูกเติมด้วย JS เมื่อเรียกใช้ -->
            <div class="student-profile">
                <div class="student-avatar">ส</div>
                <div class="student-info">
                    <h3 class="student-name">นายสมชาย เรียนดี</h3>
                    <p>เลขที่ 3 ห้อง ม.6/1</p>
                </div>
            </div>
            
            <div class="attendance-stats">
                <div class="stat-item">
                    <span class="stat-label">วันเข้าแถวทั้งหมด:</span>
                    <span class="stat-value">21/23 วัน</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">อัตราการเข้าแถว:</span>
                    <span class="stat-value good">91.3%</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">ขาดล่าสุด:</span>
                    <span class="stat-value">14 มี.ค. 2025</span>
                </div>
            </div>
            
            <div class="attendance-history">
                <h4>ประวัติการเข้าแถว</h4>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                            <th>เวลา</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>16 มี.ค. 2025</td>
                            <td class="status present">มา</td>
                            <td>07:45 น.</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>15 มี.ค. 2025</td>
                            <td class="status present">มา</td>
                            <td>07:42 น.</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>14 มี.ค. 2025</td>
                            <td class="status absent">ขาด</td>
                            <td>-</td>
                            <td>ป่วย (มีใบรับรองแพทย์)</td>
                        </tr>
                        <tr>
                            <td>13 มี.ค. 2025</td>
                            <td class="status present">มา</td>
                            <td>07:50 น.</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td>12 มี.ค. 2025</td>
                            <td class="status present">มา</td>
                            <td>07:38 น.</td>
                            <td>-</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="modal-button secondary" onclick="contactStudentParent()">
                <span class="material-icons">mail</span> ติดต่อผู้ปกครอง
            </button>
            <button class="modal-button primary" onclick="printStudentReport()">
                <span class="material-icons">print</span> พิมพ์รายงาน
            </button>
        </div>
    </div>
</div>

<!-- Modal ติดต่อผู้ปกครอง -->
<div class="modal" id="contact-parent-modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('contact-parent-modal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ติดต่อผู้ปกครอง</h2>
        
        <div id="parent-detail">
            <div class="parent-profile">
                <div class="parent-avatar">ส</div>
                <div class="parent-info">
                    <h3 class="parent-name">นายสมบัติ เรียนดี</h3>
                    <p>ผู้ปกครองของ นายสมชาย เรียนดี</p>
                    <p>ความสัมพันธ์: บิดา</p>
                    <p>โทรศัพท์: 081-234-5678</p>
                    <p>LINE ID: @sombat123</p>
                </div>
            </div>
            
            <div class="message-form">
                <label class="option-label">ข้อความถึงผู้ปกครอง:</label>
                <textarea id="parent-message" rows="5" placeholder="ระบุข้อความที่ต้องการส่งถึงผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครอง ขอแจ้งข้อมูลการเข้าแถวของนักเรียนในความปกครองของท่าน...</textarea>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="modal-button cancel" onclick="closeModal('contact-parent-modal')">ยกเลิก</button>
            <button class="modal-button primary" onclick="sendParentMessage()">ส่งข้อความ</button>
        </div>
    </div>
</div>