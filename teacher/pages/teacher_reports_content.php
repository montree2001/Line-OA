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
                <option value="<?php echo $class['class_id']; ?>" <?php echo ($class['class_id'] == $current_class_id) ? 'selected' : ''; ?>>
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
                        <?php echo $month['month']; ?> <?php echo $current_year + 543; ?>
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
                <?php if (is_array($daily_attendance) && !empty($daily_attendance)): ?>
                    <?php foreach ($daily_attendance as $day): ?>
                        <div class="chart-bar" style="height: <?php echo isset($day['percentage']) ? $day['percentage'] : 0; ?>%">
                            <div class="chart-bar-value"><?php echo isset($day['percentage']) ? $day['percentage'] : 0; ?>%</div>
                            <div class="chart-bar-label"><?php echo isset($day['day']) ? $day['day'] : ''; ?><br><?php echo isset($day['date']) ? $day['date'] : ''; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data-message">ไม่มีข้อมูลการเข้าแถวในช่วงนี้</div>
                <?php endif; ?>
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
            <!-- ลบปุ่ม "แจ้งเตือนผู้ปกครอง" ตามที่ต้องการ -->
        </div>
    </div>

    <!-- แท็บเมนู -->
    <div class="tab-menu">
        <div class="tab-button active" data-tab="table" onclick="switchTab('table')">รายการ</div>
        <div class="tab-button" data-tab="graph" onclick="switchTab('graph')">กราฟ</div>
        <div class="tab-button" data-tab="calendar" onclick="switchTab('calendar')">ปฏิทิน</div>
        <div class="tab-button" data-tab="activities" onclick="switchTab('activities')">กิจกรรมกลาง</div>
        <div class="tab-button" data-tab="risk" onclick="switchTab('risk')">ความเสี่ยง</div>
    </div>

<!-- เพิ่มส่วนแสดงผลกิจกรรมกลาง -->
<div class="activities-card" id="activities-view" style="display: none;">
    <div class="activities-header">
        <div class="activities-title">กิจกรรมกลางของนักเรียน</div>
        <div class="chart-controls">
            <button class="chart-button" onclick="downloadActivitiesReport()">
                <span class="material-icons">file_download</span> ดาวน์โหลด
            </button>
            <button class="chart-button" onclick="printActivitiesReport()">
                <span class="material-icons">print</span> พิมพ์
            </button>
        </div>
    </div>

    <div class="activities-table-wrapper">
        <?php if (empty($class_activities)): ?>
            <div class="no-data-message">ไม่พบข้อมูลกิจกรรมกลางในภาคเรียนนี้</div>
        <?php else: ?>
            <table class="activities-table">
                <thead>
                    <tr>
                        <th>กิจกรรม</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>นักเรียนเข้าร่วม</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_activities as $activity): ?>
                        <?php 
                            $participation_rate = ($activity['total_students'] > 0) ? 
                                round(($activity['participating_students'] / $activity['total_students']) * 100, 1) : 0;
                            
                            if ($participation_rate >= 80) {
                                $status_class = 'good';
                                $status_text = 'ดี';
                            } elseif ($participation_rate >= 70) {
                                $status_class = 'warning';
                                $status_text = 'พอใช้';
                            } else {
                                $status_class = 'danger';
                                $status_text = 'น้อย';
                            }
                            
                            // แปลงวันที่เป็นรูปแบบไทย
                            $activity_date = new DateTime($activity['activity_date']);
                            $thai_date = $activity_date->format('d/m/') . ($activity_date->format('Y') + 543);
                        ?>
                        <tr>
                            <td data-label="กิจกรรม"><?php echo $activity['activity_name']; ?></td>
                            <td data-label="วันที่"><?php echo $thai_date; ?></td>
                            <td data-label="สถานะ"><span class="status <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td data-label="นักเรียนเข้าร่วม"><?php echo $activity['participating_students']; ?>/<?php echo $activity['total_students']; ?> (<?php echo $participation_rate; ?>%)</td>
                            <td data-label="จัดการ">
                                <div class="action-buttons">
                                    <button class="action-button" title="ดูรายละเอียด" onclick="viewActivityDetail(<?php echo $activity['activity_id']; ?>)">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button" title="แจ้งเตือนผู้ปกครอง" onclick="notifyActivityParents(<?php echo $activity['activity_id']; ?>)">
                                        <span class="material-icons">notification_important</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- เพิ่มส่วนแสดงนักเรียนที่เสี่ยงตกกิจกรรม -->
<div class="risk-card" id="risk-view" style="display: none;">
    <div class="risk-header">
        <div class="risk-title">นักเรียนที่มีความเสี่ยงตกกิจกรรมเข้าแถว</div>
        <div class="chart-controls">
            <button class="control-button orange" onclick="notifyParents()">
                <span class="material-icons">notification_important</span> แจ้งเตือนผู้ปกครอง
            </button>
        </div>
    </div>

    <div class="risk-table-wrapper">
        <?php if (empty($risk_students)): ?>
            <div class="no-data-message">ไม่พบนักเรียนที่มีความเสี่ยงในขณะนี้</div>
        <?php else: ?>
            <table class="risk-table">
                <thead>
                    <tr>
                        <th>เลขที่</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>จำนวนวันขาด</th>
                        <th>อัตราการเข้าแถว</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($risk_students as $student): ?>
                        <?php 
                            $attendance_rate = $student['attendance_percentage'];
                            if ($attendance_rate >= 70) {
                                $status_class = 'warning';
                            } else {
                                $status_class = 'danger';
                            }
                        ?>
                        <tr>
                            <td data-label="เลขที่"><?php echo $student['number']; ?></td>
                            <td data-label="ชื่อ-นามสกุล"><?php echo $student['title'] . $student['first_name'] . ' ' . $student['last_name']; ?></td>
                            <td data-label="จำนวนวันขาด"><?php echo $student['absent_count']; ?></td>
                            <td data-label="อัตราการเข้าแถว"><span class="attendance-percent <?php echo $status_class; ?>"><?php echo $attendance_rate; ?>%</span></td>
                            <td data-label="จัดการ">
                                <div class="action-buttons">
                                    <button class="action-button" title="ดูรายละเอียด" onclick="viewStudentDetail(<?php echo $student['student_id']; ?>)">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button" title="ส่งข้อความถึงผู้ปกครอง" onclick="contactParent(<?php echo $student['student_id']; ?>)">
                                        <span class="material-icons">mail</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">ไม่พบข้อมูลนักเรียน</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td data-label="เลขที่"><?php echo $student['number']; ?></td>
                            <td data-label="ชื่อ-นามสกุล"><?php echo $student['name']; ?></td>
                            <td data-label="จำนวนวันเข้าแถว"><?php echo $student['attendance_days']; ?></td>
                            <td data-label="อัตราการเข้าแถว"><span class="attendance-percent <?php echo $student['status']; ?>"><?php echo $student['percentage']; ?>%</span></td>
                            <td data-label="จัดการ">
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
                <?php endif; ?>
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
                <?php
                $count = 0;
                foreach ($students as $student):
                    if ($count < 10): // แสดงเฉพาะ 10 คนแรก
                        $count++;
                ?>
                        <div class="student-bar-container">
                            <div class="student-bar-label"><?php echo $student['number']; ?>. <?php echo substr($student['name'], 0, 15); ?></div>
                            <div class="student-bar-chart">
                                <div class="student-bar <?php echo $student['status']; ?>" style="width: <?php echo $student['percentage']; ?>%">
                                    <span class="student-bar-value"><?php echo $student['percentage']; ?>%</span>
                                </div>
                            </div>
                        </div>
                <?php
                    endif;
                endforeach;
                ?>
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
                        1 => 'มกราคม',
                        2 => 'กุมภาพันธ์',
                        3 => 'มีนาคม',
                        4 => 'เมษายน',
                        5 => 'พฤษภาคม',
                        6 => 'มิถุนายน',
                        7 => 'กรกฎาคม',
                        8 => 'สิงหาคม',
                        9 => 'กันยายน',
                        10 => 'ตุลาคม',
                        11 => 'พฤศจิกายน',
                        12 => 'ธันวาคม'
                    ];
                    echo $month_names[$current_month] . ' ' . ($current_year + 543);
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



<!-- Modal ดูรายละเอียดนักเรียน -->
<div class="modal" id="student-detail-modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('student-detail-modal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">รายละเอียดการเข้าแถวของนักเรียน</h2>

        <div id="student-detail-content">
            <!-- จะถูกเติมด้วย JavaScript เมื่อเรียกใช้ -->
            <div class="loading">กำลังโหลดข้อมูล...</div>
        </div>

        <!-- แสดงข้อมูลผู้ปกครอง -->
        <div id="parent-detail-section" style="display: none;">
            <h3 class="section-title">ข้อมูลผู้ปกครอง</h3>
            <div id="parent-detail-content">
                <!-- จะถูกเติมด้วย JavaScript เมื่อเรียกใช้ -->
            </div>
        </div>

        <div class="modal-actions">

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
                <div class="parent-avatar">ผ</div>
                <div class="parent-info">
                    <h3 class="parent-name">ผู้ปกครองตัวอย่าง</h3>
                    <p>โหลดข้อมูล...</p>
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


<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // เริ่มต้นระบบแท็บ
        initTabMenu();

        // เพิ่ม Event Listener สำหรับค้นหานักเรียน
        const searchInput = document.getElementById('student-search');
        if (searchInput) {
            searchInput.addEventListener('input', searchStudents);
        }
    });

    // ฟังก์ชันสำหรับระบบแท็บ
    function initTabMenu() {
        // เริ่มต้นแสดงแท็บแรก
        switchTab('table');

        // เพิ่ม Event Listener ให้กับปุ่มแท็บ
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.textContent.toLowerCase().includes('รายการ') ? 'table' :
                    this.textContent.toLowerCase().includes('กราฟ') ? 'graph' : 'calendar';
                switchTab(tabName);
            });
        });
    }



    // ฟังก์ชันปิด Modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // ฟังก์ชันค้นหานักเรียน
    function searchStudents() {
        const searchInput = document.getElementById('student-search');
        if (!searchInput) return;

        const searchText = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('.student-table tbody tr');

        rows.forEach(row => {
            const name = row.querySelector('[data-label="ชื่อ-นามสกุล"]')?.textContent.toLowerCase() || '';
            const number = row.querySelector('[data-label="เลขที่"]')?.textContent.toLowerCase() || '';

            if (name.includes(searchText) || number.includes(searchText)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>