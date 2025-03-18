<?php
// กำหนดข้อมูลหน้าปัจจุบัน
$current_page = 'dashboard';
$page_title = 'TEACHER-Prasat - หน้าหลัก';

// ข้อมูลครูผู้สอน
$teacher_info = [
    'name' => 'อาจารย์ใจดี มากเมตตา',
    'position' => 'ครูที่ปรึกษา ปวช.3/1',
    'avatar' => 'ค',
    'department' => 'แผนกวิชาเทคโนโลยีสารสนเทศ'
];

// ข้อมูลสรุปวันนี้
$today_summary = [
    'total_students' => 25,
    'present_students' => 23,
    'absent_students' => 2,
    'attendance_rate' => 92
];

// นักเรียนที่เสี่ยงตกกิจกรรม
$at_risk_students = [
    [
        'name' => 'นายธนกฤต สุขใจ',
        'class' => 'ปวช.3/1',
        'number' => 12,
        'avatar' => 'ธ',
        'attendance_rate' => 68.5,
        'absent_days' => 15,
        'last_absent' => '15 มี.ค. 2025',
        'notification_status' => 'ยังไม่แจ้ง'
    ],
    [
        'name' => 'นางสาวสมหญิง มีสุข',
        'class' => 'ปวช.3/1',
        'number' => 8,
        'avatar' => 'ส',
        'attendance_rate' => 70.2,
        'absent_days' => 14,
        'last_absent' => '16 มี.ค. 2025',
        'notification_status' => 'แจ้งแล้ว 1 ครั้ง'
    ]
];

// ประวัติการเช็คชื่อล่าสุด
$recent_attendances = [
    [
        'date' => '16 มี.ค. 2025',
        'time' => '07:45',
        'present' => 23,
        'absent' => 2,
        'rate' => 92
    ],
    [
        'date' => '15 มี.ค. 2025',
        'time' => '07:40',
        'present' => 24,
        'absent' => 1,
        'rate' => 96
    ],
    [
        'date' => '14 มี.ค. 2025',
        'time' => '07:38',
        'present' => 25,
        'absent' => 0,
        'rate' => 100
    ]
];

// ประกาศจากโรงเรียน
$announcements = [
    [
        'badge' => 'urgent',
        'badge_text' => 'ด่วน',
        'title' => 'แจ้งกำหนดการสอบปลายภาค',
        'content' => 'แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2568 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น.',
        'date' => '14 มี.ค. 2025'
    ],
    [
        'badge' => 'event',
        'badge_text' => 'กิจกรรม',
        'title' => 'ประชุมผู้ปกครองภาคเรียนที่ 2',
        'content' => 'ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน',
        'date' => '10 มี.ค. 2025'
    ]
];

// จำนวนนักเรียนเสี่ยงตก
$at_risk_count = count($at_risk_students);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/teacher-home.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="logo">
                    <span class="material-icons">school</span>
                    TEACHER-Prasat
                </a>
                <button class="sidebar-close" id="sidebarClose">
                    <span class="material-icons">close</span>
                </button>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-category">หน้าหลัก</div>
                <a href="index.php" class="menu-item <?php echo ($current_page == 'dashboard') ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span>
                    แดชบอร์ด
                </a>
                <a href="check_attendance.php" class="menu-item <?php echo ($current_page == 'check_attendance') ? 'active' : ''; ?>">
                    <span class="material-icons">how_to_reg</span>
                    เช็คชื่อนักเรียน
                </a>
                <a href="reports.php" class="menu-item <?php echo ($current_page == 'reports') ? 'active' : ''; ?>">
                    <span class="material-icons">assessment</span>
                    รายงานและสถิติ
                </a>
                <a href="send_notification.php" class="menu-item <?php echo ($current_page == 'send_notification') ? 'active' : ''; ?>">
                    <span class="material-icons">send</span>
                    ส่งรายงานผู้ปกครอง
                </a>
                <a href="at_risk.php" class="menu-item <?php echo ($current_page == 'at_risk') ? 'active' : ''; ?>">
                    <span class="material-icons">warning</span>
                    นักเรียนเสี่ยงตกกิจกรรม
                    <?php if (isset($at_risk_count) && $at_risk_count > 0): ?>
                        <span class="badge"><?php echo $at_risk_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <div class="menu-category">จัดการข้อมูล</div>
                <a href="students.php" class="menu-item <?php echo ($current_page == 'students') ? 'active' : ''; ?>">
                    <span class="material-icons">people</span>
                    นักเรียน
                </a>
                <a href="parents.php" class="menu-item <?php echo ($current_page == 'parents') ? 'active' : ''; ?>">
                    <span class="material-icons">family_restroom</span>
                    ผู้ปกครอง
                </a>
                <a href="classes.php" class="menu-item <?php echo ($current_page == 'classes') ? 'active' : ''; ?>">
                    <span class="material-icons">class</span>
                    ชั้นเรียน
                </a>
                
                <div class="menu-category">ตั้งค่า</div>
                <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings') ? 'active' : ''; ?>">
                    <span class="material-icons">settings</span>
                    ตั้งค่าระบบ
                </a>
                <a href="help.php" class="menu-item <?php echo ($current_page == 'help') ? 'active' : ''; ?>">
                    <span class="material-icons">help</span>
                    ช่วยเหลือ
                </a>
            </div>
            
            <div class="teacher-info">
                <div class="teacher-avatar"><?php echo $teacher_info['avatar']; ?></div>
                <div class="teacher-details">
                    <div class="teacher-name"><?php echo $teacher_info['name']; ?></div>
                    <div class="teacher-role"><?php echo $teacher_info['position']; ?></div>
                </div>
                <div class="teacher-menu" id="teacherMenuToggle">
                    <span class="material-icons">more_vert</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="menu-toggle" id="menuToggle">
                    <span class="material-icons">menu</span>
                </div>
                <div class="header-title">
                    <h1><?php echo $page_title; ?></h1>
                </div>
                <div class="header-actions">
                    <div class="notifications">
                        <span class="material-icons">notifications</span>
                        <?php if (isset($at_risk_count) && $at_risk_count > 0): ?>
                            <span class="notification-badge"><?php echo $at_risk_count; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo $teacher_info['avatar']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="content">
                <!-- ตัวอย่างเนื้อหา - สถิติด่วน -->
                <div class="page-header">
                    <h2>หน้าหลัก</h2>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="showPinModal()">
                            <span class="material-icons">pin</span>
                            สร้างรหัส PIN เช็คชื่อ
                        </button>
                        <button class="btn btn-secondary" onclick="window.location.href='check_attendance.php'">
                            <span class="material-icons">qr_code_scanner</span>
                            สแกน QR Code
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-3 col-md-6 col-sm-6">
                        <div class="card">
                            <div class="card-title">
                                <span class="material-icons">people</span>
                                นักเรียนทั้งหมด
                            </div>
                            <h2><?php echo $today_summary['total_students']; ?> คน</h2>
                            <p>นักเรียนในที่ปรึกษา</p>
                        </div>
                    </div>
                    <div class="col-3 col-md-6 col-sm-6">
                        <div class="card">
                            <div class="card-title">
                                <span class="material-icons">check_circle</span>
                                เข้าแถววันนี้
                            </div>
                            <h2><?php echo $today_summary['present_students']; ?> คน</h2>
                            <p>คิดเป็น <?php echo $today_summary['attendance_rate']; ?>%</p>
                        </div>
                    </div>
                    <div class="col-3 col-md-6 col-sm-6">
                        <div class="card">
                            <div class="card-title">
                                <span class="material-icons">cancel</span>
                                ขาดแถววันนี้
                            </div>
                            <h2><?php echo $today_summary['absent_students']; ?> คน</h2>
                            <p>นักเรียนที่ขาดเข้าแถว</p>
                        </div>
                    </div>
                    <div class="col-3 col-md-6 col-sm-6">
                        <div class="card">
                            <div class="card-title">
                                <span class="material-icons">warning</span>
                                เสี่ยงตกกิจกรรม
                            </div>
                            <h2><?php echo $at_risk_count; ?> คน</h2>
                            <p>ต้องได้รับการดูแล</p>
                        </div>
                    </div>
                </div>

                <!-- ตัวอย่างปุ่มทางลัด -->
                <div class="quick-actions">
                    <button class="quick-action-btn pin" onclick="showPinModal()">
                        <span class="material-icons">pin</span>
                        สร้างรหัส PIN เช็คชื่อ
                    </button>
                    <button class="quick-action-btn qr" onclick="window.location.href='scan_qr.php'">
                        <span class="material-icons">qr_code_scanner</span>
                        สแกน QR Code นักเรียน
                    </button>
                    <button class="quick-action-btn check" onclick="window.location.href='check_attendance.php'">
                        <span class="material-icons">check_circle</span>
                        เช็คชื่อนักเรียน
                    </button>
                    <button class="quick-action-btn alert" onclick="window.location.href='send_notification.php'">
                        <span class="material-icons">campaign</span>
                        แจ้งเตือนผู้ปกครอง
                    </button>
                </div>

                <!-- ตารางนักเรียนเสี่ยงตกกิจกรรม -->
                <div class="card">
                    <div class="card-title">
                        <span class="material-icons">warning</span>
                        นักเรียนเสี่ยงตกกิจกรรม
                        <a href="at_risk.php" class="view-all">ดูทั้งหมด</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>นักเรียน</th>
                                    <th>ชั้น/ห้อง</th>
                                    <th>ร้อยละการเข้าแถว</th>
                                    <th>วันที่ขาด</th>
                                    <th>ขาดล่าสุด</th>
                                    <th>การแจ้งเตือน</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($at_risk_students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar"><?php echo $student['avatar']; ?></div>
                                            <div class="student-details">
                                                <div class="student-name"><?php echo $student['name']; ?></div>
                                                <div class="student-class">เลขที่ <?php echo $student['number']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $student['class']; ?></td>
                                    <td><span class="status-badge danger"><?php echo $student['attendance_rate']; ?>%</span></td>
                                    <td><?php echo $student['absent_days']; ?> วัน</td>
                                    <td><?php echo $student['last_absent']; ?></td>
                                    <td><?php echo $student['notification_status']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="window.location.href='student_detail.php?id=<?php echo $student['number']; ?>'">
                                                <span class="material-icons">visibility</span>
                                            </button>
                                            <button class="table-action-btn success" title="ส่งข้อความ" onclick="sendNotification(<?php echo $student['number']; ?>)">
                                                <span class="material-icons">send</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer">
                        <button class="btn btn-primary" onclick="sendBulkNotifications()">
                            <span class="material-icons">send</span>
                            ส่งรายงานไปยังผู้ปกครองทั้งหมด
                        </button>
                    </div>
                </div>

                <!-- ประวัติการเช็คชื่อล่าสุด -->
                <div class="card">
                    <div class="card-title">
                        <span class="material-icons">history</span>
                        ประวัติการเช็คชื่อล่าสุด
                        <a href="attendance_history.php" class="view-all">ดูทั้งหมด</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>วันที่</th>
                                    <th>เวลาเช็คชื่อ</th>
                                    <th>มาเรียน</th>
                                    <th>ขาดเรียน</th>
                                    <th>อัตราการมาเรียน</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_attendances as $attendance): ?>
                                <tr>
                                    <td><?php echo $attendance['date']; ?></td>
                                    <td><?php echo $attendance['time']; ?> น.</td>
                                    <td><span class="status-badge success"><?php echo $attendance['present']; ?> คน</span></td>
                                    <td><span class="status-badge danger"><?php echo $attendance['absent']; ?> คน</span></td>
                                    <td><?php echo $attendance['rate']; ?>%</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="window.location.href='attendance_detail.php?date=<?php echo urlencode($attendance['date']); ?>'">
                                                <span class="material-icons">visibility</span>
                                            </button>
                                            <button class="table-action-btn download" title="ดาวน์โหลดรายงาน" onclick="downloadReport('<?php echo urlencode($attendance['date']); ?>')">
                                                <span class="material-icons">download</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ประกาศล่าสุด -->
                <div class="card">
                    <div class="card-title">
                        <span class="material-icons">campaign</span>
                        ประกาศล่าสุดจากวิทยาลัย
                        <a href="announcements.php" class="view-all">ดูทั้งหมด</a>
                    </div>
                    
                    <ul class="announcement-list">
                        <?php foreach($announcements as $announcement): ?>
                        <li class="announcement-item">
                            <div class="announcement-title">
                                <span class="announcement-badge badge-<?php echo $announcement['badge']; ?>">
                                    <?php echo $announcement['badge_text']; ?>
                                </span>
                                <?php echo $announcement['title']; ?>
                            </div>
                            <div class="announcement-content"><?php echo $announcement['content']; ?></div>
                            <div class="announcement-date">
                                <span class="material-icons">event</span> <?php echo $announcement['date']; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- โมดัลสร้างรหัส PIN -->
    <div class="modal" id="pinModal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">
                <span class="material-icons">close</span>
            </button>
            <h2 class="modal-title">สร้างรหัส PIN สำหรับการเช็คชื่อ</h2>
            <div class="pin-display" id="pinCode">5731</div>
            <div class="pin-info">
                รหัส PIN นี้สำหรับให้นักเรียนเช็คชื่อวันนี้<br>
                เท่านั้น และจะหมดอายุภายในเวลาที่กำหนด
            </div>
            <div class="timer">
                <span class="material-icons">timer</span>
                <span>หมดอายุใน 9:58 นาที</span>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">ปิด</button>
                <button class="btn btn-primary" onclick="generateNewPin()">สร้างรหัสใหม่</button>
            </div>
        </div>
    </div>

    <!-- Teacher Dropdown Menu -->
    <div class="teacher-dropdown" id="teacherDropdown">
        <a href="profile.php" class="teacher-dropdown-item">
            <span class="material-icons">account_circle</span>
            ข้อมูลส่วนตัว
        </a>
        <a href="change_password.php" class="teacher-dropdown-item">
            <span class="material-icons">lock</span>
            เปลี่ยนรหัสผ่าน
        </a>
        <div class="teacher-dropdown-divider"></div>
        <a href="logout.php" class="teacher-dropdown-item">
            <span class="material-icons">exit_to_app</span>
            ออกจากระบบ
        </a>
    </div>

    <!-- Overlay for Mobile -->
    <div class="overlay" id="overlay"></div>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/teacher-home.js"></script>
</body>
</html>