<?php
/**
 * attendance_check_content.php - เนื้อหาของหน้าเช็คชื่อนักเรียน
 * 
 * คุณสมบัติ:
 * - เช็คชื่อได้ 4 สถานะ: มา/ขาด/สาย/ลา
 * - เช็คชื่อนักเรียนที่ปรึกษาได้ พร้อมเลือกห้องเรียน
 * - สร้างรหัส PIN 4 หลักเพื่อให้นักเรียนเช็คชื่อ
 * - สแกน QR Code ของนักเรียน
 * - เช็คชื่อย้อนหลังได้พร้อมบันทึกประวัติ
 * - แสดงสรุปข้อมูลนักเรียนรายบุคคล
 * - ดาวน์โหลดรายงานได้
 * - ดูข้อมูลผู้ปกครองได้
 * - แสดงปีการศึกษาเป็น พ.ศ.
 */
?>

<!-- ส่วนหัวของหน้า -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clipboard-check text-primary me-2"></i>
                            เช็คชื่อนักเรียน
                        </h5>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="actionMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-three-dots-vertical"></i> ตัวเลือกเพิ่มเติม
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="actionMenu">
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="downloadReport()"><i class="bi bi-download me-2"></i>ดาวน์โหลดรายงาน</a></li>
                                <li><a class="dropdown-item" href="student_history.php?class_id=<?php echo $current_class_id; ?>"><i class="bi bi-clock-history me-2"></i>ประวัติการเช็คชื่อ</a></li>
                                <li><a class="dropdown-item" href="class_parents.php?class_id=<?php echo $current_class_id; ?>"><i class="bi bi-people me-2"></i>ข้อมูลผู้ปกครอง</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showHelp()"><i class="bi bi-question-circle me-2"></i>วิธีใช้งาน</a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="classSelect" class="form-label">ห้องเรียน</label>
                            <select id="classSelect" class="form-select" onchange="changeClass(this.value)">
                                <?php foreach ($teacher_classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo ($class['id'] == $current_class_id) ? 'selected' : ''; ?>>
                                        <?php echo $class['name']; ?> (<?php echo $class['total_students']; ?> คน)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="dateSelect" class="form-label">วันที่เช็คชื่อ</label>
                            <div class="input-group">
                                <input type="date" id="dateSelect" class="form-control" value="<?php echo $check_date; ?>" max="<?php echo date('Y-m-d'); ?>" onchange="changeDate(this.value)">
                                <?php if ($is_retroactive): ?>
                                    <span class="input-group-text bg-warning text-dark">
                                        <i class="bi bi-clock-history me-1"></i> เช็คชื่อย้อนหลัง
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- สรุปข้อมูลการเช็คชื่อ -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">ทั้งหมด</h6>
                        <h3 class="mb-0"><?php echo $total_students; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">มาเรียน</h6>
                        <h3 class="mb-0"><?php echo $present_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                        <i class="bi bi-clock fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">มาสาย</h6>
                        <h3 class="mb-0"><?php echo $late_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-file-text fs-4 text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">ลา</h6>
                        <h3 class="mb-0"><?php echo $leave_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-x-circle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">ขาดเรียน</h6>
                        <h3 class="mb-0"><?php echo $absent_count; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-hourglass-split fs-4 text-secondary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted">รอเช็ค</h6>
                        <h3 class="mb-0"><?php echo $not_checked; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ปุ่มดำเนินการเช็คชื่อ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-primary" onclick="createPIN()">
                        <i class="bi bi-key me-1"></i> สร้างรหัส PIN
                    </button>
                    <button type="button" class="btn btn-info text-white" onclick="scanQR()">
                        <i class="bi bi-qr-code-scan me-1"></i> สแกน QR Code
                    </button>
                    <button type="button" class="btn btn-success" onclick="markAllAttendance()">
                        <i class="bi bi-check-all me-1"></i> เช็คชื่อทั้งหมด
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveAttendance()">
                        <i class="bi bi-save me-1"></i> บันทึกการเช็คชื่อ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ช่องค้นหา -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0" placeholder="ค้นหานักเรียน..." oninput="searchStudents()">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- แท็บการเช็คชื่อ -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="attendanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center" id="waiting-tab" data-bs-toggle="tab" data-bs-target="#waitingTab" type="button" role="tab" aria-controls="waitingTab" aria-selected="true">
                        <i class="bi bi-hourglass me-2"></i> รอเช็คชื่อ
                        <span class="badge bg-secondary rounded-pill ms-2"><?php echo count($unchecked_students); ?></span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center" id="checked-tab" data-bs-toggle="tab" data-bs-target="#checkedTab" type="button" role="tab" aria-controls="checkedTab" aria-selected="false">
                        <i class="bi bi-check-all me-2"></i> เช็คชื่อแล้ว
                        <span class="badge bg-primary rounded-pill ms-2"><?php echo count($checked_students); ?></span>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="attendanceTabsContent">
                <!-- แท็บ: รอเช็คชื่อ -->
                <div class="tab-pane fade show active" id="waitingTab" role="tabpanel" aria-labelledby="waiting-tab">
                    <?php if (empty($unchecked_students)): ?>
                        <div class="text-center p-5">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h4>เช็คชื่อครบทุกคนแล้ว!</h4>
                            <p class="text-muted">ทุกคนได้รับการเช็คชื่อเรียบร้อยแล้ว</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($unchecked_students as $student): ?>
                                <div class="list-group-item student-card p-3" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <?php echo $student['number']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col" onclick="showDetailAttendance(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')">
                                            <div class="d-flex align-items-center">
                                                <?php if ($student['profile_picture']): ?>
                                                    <div class="rounded-circle me-3" style="width: 40px; height: 40px; background-image: url('<?php echo $student['profile_picture']; ?>'); background-size: cover;"></div>
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo mb_substr(str_replace(['นาย', 'นางสาว', 'นาง', 'เด็กชาย', 'เด็กหญิง'], '', $student['name']), 0, 1, 'UTF-8'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo $student['name']; ?></h6>
                                                    <small class="text-muted">รหัส: <?php echo $student['code']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col text-end">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAttendance(this, 'present', <?php echo $student['id']; ?>)" title="มาเรียน">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAttendance(this, 'absent', <?php echo $student['id']; ?>)" title="ขาดเรียน">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="ตัวเลือกเพิ่มเติม">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="showDetailAttendance(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>')"><i class="bi bi-pencil me-2"></i>เช็คชื่อแบบละเอียด</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="markAttendance(this, 'late', <?php echo $student['id']; ?>)"><i class="bi bi-clock me-2"></i>มาสาย</a></li>
                                                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="markAttendance(this, 'leave', <?php echo $student['id']; ?>)"><i class="bi bi-file-text me-2"></i>ลา</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- แท็บ: เช็คชื่อแล้ว -->
                <div class="tab-pane fade" id="checkedTab" role="tabpanel" aria-labelledby="checked-tab">
                    <?php if (empty($checked_students)): ?>
                        <div class="text-center p-5">
                            <div class="mb-3">
                                <i class="bi bi-hourglass text-secondary" style="font-size: 3rem;"></i>
                            </div>
                            <h4>ยังไม่มีการเช็คชื่อ</h4>
                            <p class="text-muted">ยังไม่มีนักเรียนที่ได้รับการเช็คชื่อในวันที่เลือก</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($checked_students as $student): ?>
                                <?php
                                // กำหนดคลาสและไอคอนตามสถานะ
                                $status_class = '';
                                $status_icon = '';
                                $status_text = '';
                                $status_bg = '';

                                switch ($student['status']) {
                                    case 'present':
                                        $status_class = 'success';
                                        $status_icon = 'check-circle';
                                        $status_text = 'มาเรียน';
                                        $status_bg = 'bg-success bg-opacity-10';
                                        break;
                                    case 'late':
                                        $status_class = 'warning';
                                        $status_icon = 'clock';
                                        $status_text = 'มาสาย';
                                        $status_bg = 'bg-warning bg-opacity-10';
                                        break;
                                    case 'leave':
                                        $status_class = 'info';
                                        $status_icon = 'file-text';
                                        $status_text = 'ลา';
                                        $status_bg = 'bg-info bg-opacity-10';
                                        break;
                                    case 'absent':
                                        $status_class = 'danger';
                                        $status_icon = 'x-circle';
                                        $status_text = 'ขาดเรียน';
                                        $status_bg = 'bg-danger bg-opacity-10';
                                        break;
                                }
                                ?>
                                <div class="list-group-item student-card p-3 border-start border-<?php echo $status_class; ?> border-3" data-id="<?php echo $student['id']; ?>" data-name="<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo $student['status']; ?>" data-attendance-id="<?php echo $student['attendance_id']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <?php echo $student['number']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-5 col" onclick="editAttendance(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo $student['status']; ?>', '<?php echo htmlspecialchars($student['remarks'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')">
                                            <div class="d-flex align-items-center">
                                                <?php if ($student['profile_picture']): ?>
                                                    <div class="rounded-circle me-3" style="width: 40px; height: 40px; background-image: url('<?php echo $student['profile_picture']; ?>'); background-size: cover;"></div>
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                        <?php echo mb_substr(str_replace(['นาย', 'นางสาว', 'นาง', 'เด็กชาย', 'เด็กหญิง'], '', $student['name']), 0, 1, 'UTF-8'); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?php echo $student['name']; ?></h6>
                                                    <?php if (!empty($student['remarks'])): ?>
                                                        <small class="text-muted fst-italic"><?php echo $student['remarks']; ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">รหัส: <?php echo $student['code']; ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-7 text-end">
                                            <span class="badge <?php echo $status_bg; ?> text-<?php echo $status_class; ?> px-3 py-2">
                                                <i class="bi bi-<?php echo $status_icon; ?> me-1"></i>
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3 col-5 text-end">
                                            <small class="d-block text-muted">
                                                <i class="bi bi-clock me-1"></i> <?php echo $student['time_checked']; ?>
                                            </small>
                                            <small class="d-block text-muted">
                                                <?php
                                                switch ($student['check_method']) {
                                                    case 'Manual':
                                                        echo '<i class="bi bi-person me-1"></i> ครู';
                                                        break;
                                                    case 'PIN':
                                                        echo '<i class="bi bi-key me-1"></i> PIN';
                                                        break;
                                                    case 'QR_Code':
                                                        echo '<i class="bi bi-qr-code me-1"></i> QR';
                                                        break;
                                                    case 'GPS':
                                                        echo '<i class="bi bi-geo-alt me-1"></i> GPS';
                                                        break;
                                                    default:
                                                        echo $student['check_method'];
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สร้าง PIN -->
<div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pinModalLabel">สร้างรหัส PIN สำหรับเช็คชื่อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="pin-display d-flex justify-content-center gap-3 my-4">
                    <div class="pin-digit display-4 fw-bold bg-light rounded p-3" style="width: 60px; height: 80px;">-</div>
                    <div class="pin-digit display-4 fw-bold bg-light rounded p-3" style="width: 60px; height: 80px;">-</div>
                    <div class="pin-digit display-4 fw-bold bg-light rounded p-3" style="width: 60px; height: 80px;">-</div>
                    <div class="pin-digit display-4 fw-bold bg-light rounded p-3" style="width: 60px; height: 80px;">-</div>
                </div>
                <p class="mb-1 text-muted">รหัส PIN จะหมดอายุใน <span id="expireTime" class="fw-bold text-danger">10</span> นาที</p>
                <p class="text-primary fw-bold"><?php echo $current_class['name']; ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" onclick="generateNewPIN()">สร้างใหม่</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal สแกน QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">สแกน QR Code นักเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="qr-scanner-container bg-light rounded text-center p-5 mb-3" id="qrScannerContainer">
                    <i class="bi bi-qr-code-scan display-1 text-secondary mb-3"></i>
                    <p class="text-muted">กำลังเรียกใช้กล้อง...</p>
                </div>
                <div class="qr-result-container alert alert-success d-none" id="qrResultContainer">
                    <div class="result-info" id="qrResultInfo"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อละเอียด -->
<div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-labelledby="attendanceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceDetailModalLabel">เช็คชื่อนักเรียน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h4 id="studentNameDetail" class="text-center mb-4 pb-2 border-bottom"></h4>

                <div class="status-options mb-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="attendanceStatus" id="status-present" value="present" checked>
                                <label class="form-check-label d-flex p-3 border rounded" for="status-present">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-2">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <span>มาเรียน</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="attendanceStatus" id="status-late" value="late">
                                <label class="form-check-label d-flex p-3 border rounded" for="status-late">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-2">
                                        <i class="bi bi-clock"></i>
                                    </div>
                                    <span>มาสาย</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="attendanceStatus" id="status-leave" value="leave">
                                <label class="form-check-label d-flex p-3 border rounded" for="status-leave">
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-2">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <span>ลา</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="attendanceStatus" id="status-absent" value="absent">
                                <label class="form-check-label d-flex p-3 border rounded" for="status-absent">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <span>ขาดเรียน</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 d-none" id="remarksContainer">
                    <label for="attendanceRemarks" class="form-label">หมายเหตุ</label>
                    <textarea id="attendanceRemarks" class="form-control" rows="3" placeholder="ระบุหมายเหตุ เช่น สาเหตุการมาสาย, เหตุผลการลา ฯลฯ"></textarea>
                </div>

                <?php if ($is_retroactive): ?>
                    <div class="mb-3 alert alert-warning">
                        <label for="retroactiveNote" class="form-label fw-bold">หมายเหตุการเช็คย้อนหลัง (จำเป็น)</label>
                        <textarea id="retroactiveNote" class="form-control" rows="2" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>

                <input type="hidden" id="studentIdDetail" value="">
                <input type="hidden" id="attendanceIdDetail" value="">
                <input type="hidden" id="isEditMode" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="confirmDetailAttendance()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal เช็คชื่อทั้งหมด -->
<div class="modal fade" id="markAllModal" tabindex="-1" aria-labelledby="markAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markAllModalLabel">เช็คชื่อนักเรียนทั้งหมด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    เลือกสถานะสำหรับนักเรียนที่ยังไม่ได้เช็คชื่อทั้งหมด <span class="badge bg-secondary"><?php echo $not_checked; ?></span> คน
                </div>

                <div class="status-options mb-4">
                    <div class="row g-2">
                        <div class="col-12">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="markAllStatus" id="all-present" value="present" checked>
                                <label class="form-check-label d-flex p-3 border rounded" for="all-present">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2 me-2">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <span>เช็คเป็น "มาเรียน" ทั้งหมด</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="markAllStatus" id="all-late" value="late">
                                <label class="form-check-label d-flex p-3 border rounded" for="all-late">
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-2">
                                        <i class="bi bi-clock"></i>
                                    </div>
                                    <span>เช็คเป็น "มาสาย" ทั้งหมด</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="markAllStatus" id="all-leave" value="leave">
                                <label class="form-check-label d-flex p-3 border rounded" for="all-leave">
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2 me-2">
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <span>เช็คเป็น "ลา" ทั้งหมด</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check custom-option">
                                <input type="radio" class="form-check-input visually-hidden" name="markAllStatus" id="all-absent" value="absent">
                                <label class="form-check-label d-flex p-3 border rounded" for="all-absent">
                                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-2 me-2">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <span>เช็คเป็น "ขาดเรียน" ทั้งหมด</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($is_retroactive): ?>
                    <div class="mb-3 alert alert-warning">
                        <label for="markAllRetroactiveNote" class="form-label fw-bold">หมายเหตุการเช็คย้อนหลัง (จำเป็น)</label>
                        <textarea id="markAllRetroactiveNote" class="form-control" rows="2" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="confirmMarkAll()">เช็คชื่อทั้งหมด</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal บันทึกการเช็คชื่อ -->
<div class="modal fade" id="saveAttendanceModal" tabindex="-1" aria-labelledby="saveAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="saveAttendanceModalLabel">บันทึกการเช็คชื่อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="row">
                        <div class="col-4">
                            <div class="border rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-primary text-white" style="width: 60px; height: 60px;">
                                <h3 class="mb-0"><?php echo $total_students; ?></h3>
                            </div>
                            <p class="mb-0">ทั้งหมด</p>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-success text-white" style="width: 60px; height: 60px;">
                                <h3 class="mb-0" id="saveCheckedCount"><?php echo $checked_count; ?></h3>
                            </div>
                            <p class="mb-0">เช็คแล้ว</p>
                        </div>
                        <div class="col-4">
                            <div class="border rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center bg-warning text-white" style="width: 60px; height: 60px;">
                                <h3 class="mb-0" id="saveRemainingCount"><?php echo $not_checked; ?></h3>
                            </div>
                            <p class="mb-0">คงเหลือ</p>
                        </div>
                    </div>
                </div>

                <?php if ($not_checked > 0): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>ยังมีนักเรียนที่ยังไม่ได้เช็คชื่อ <?php echo $not_checked; ?> คน</strong>
                        <p class="mb-0 mt-2">นักเรียนที่ยังไม่ได้เช็คชื่อจะถูกบันทึกเป็น "ขาด" โดยอัตโนมัติ</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>เช็คชื่อครบทุกคนแล้ว</strong>
                    </div>
                <?php endif; ?>

                <p class="mb-3 text-center">คุณต้องการบันทึกการเช็คชื่อนี้หรือไม่?</p>

                <?php if ($is_retroactive): ?>
                    <div class="mb-3 alert alert-warning">
                        <label for="saveRetroactiveNote" class="form-label fw-bold">หมายเหตุการเช็คย้อนหลัง (จำเป็น)</label>
                        <textarea id="saveRetroactiveNote" class="form-control" rows="2" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="saveAttendanceBtn" onclick="confirmSaveAttendance()">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- คำแนะนำการใช้งาน Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalLabel">วิธีใช้งานระบบเช็คชื่อ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-check-circle-fill text-success me-2"></i>การเช็คชื่อรายบุคคล</h5>
                                <p class="card-text">คลิกปุ่ม <i class="bi bi-check-lg"></i> เพื่อเช็คว่านักเรียนมาเรียน หรือคลิกปุ่ม <i class="bi bi-x-lg"></i> เพื่อเช็คว่านักเรียนขาดเรียน</p>
                                <p class="card-text">สำหรับการเช็คชื่อแบบมาสายหรือลา ให้คลิกที่ <i class="bi bi-three-dots"></i> แล้วเลือกสถานะที่ต้องการ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-people-fill text-primary me-2"></i>การเช็คชื่อทั้งห้อง</h5>
                                <p class="card-text">คลิกปุ่ม "เช็คชื่อทั้งหมด" เพื่อกำหนดสถานะให้กับนักเรียนที่ยังไม่ได้เช็คชื่อทั้งหมดพร้อมกัน</p>
                                <p class="card-text">เลือกสถานะ (มาเรียน/มาสาย/ลา/ขาด) ที่ต้องการกำหนดให้กับนักเรียนทั้งหมด</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-key-fill text-warning me-2"></i>การใช้รหัส PIN</h5>
                                <p class="card-text">คลิกปุ่ม "สร้างรหัส PIN" เพื่อสร้างรหัส 4 หลักให้นักเรียนใช้เช็คชื่อผ่านแอปนักเรียน</p>
                                <p class="card-text">PIN จะมีอายุการใช้งาน 10 นาที</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-qr-code-scan text-info me-2"></i>การสแกน QR Code</h5>
                                <p class="card-text">คลิกปุ่ม "สแกน QR Code" เพื่อเปิดกล้องสำหรับสแกน QR Code ของนักเรียน</p>
                                <p class="card-text">หลังจากสแกนสำเร็จ ระบบจะเช็คชื่อนักเรียนโดยอัตโนมัติ</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-pencil-fill text-primary me-2"></i>การแก้ไขการเช็คชื่อ</h5>
                                <p class="card-text">คลิกที่ชื่อนักเรียนในแท็บ "เช็คชื่อแล้ว" เพื่อแก้ไขสถานะการเช็คชื่อ</p>
                                <p class="card-text">เลือกสถานะใหม่และบันทึกการเปลี่ยนแปลง</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-save-fill text-success me-2"></i>การบันทึกข้อมูล</h5>
                                <p class="card-text">คลิกปุ่ม "บันทึกการเช็คชื่อ" เพื่อบันทึกการเช็คชื่อทั้งหมด</p>
                                <p class="card-text">นักเรียนที่ยังไม่ได้เช็คชื่อจะถูกบันทึกเป็น "ขาด" โดยอัตโนมัติ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">เข้าใจแล้ว</button>
            </div>
        </div>
    </div>
</div>

<script>
// ตัวแปรกลาง
const currentClassId = <?php echo $current_class_id; ?>;
const checkDate = '<?php echo $check_date; ?>';
const isRetroactive = <?php echo $is_retroactive ? 'true' : 'false'; ?>;
const teacherId = <?php echo $teacher_id; ?>;

// แสดง/ซ่อนหมายเหตุตามสถานะการเช็คชื่อที่เลือก
document.addEventListener('DOMContentLoaded', function() {
    // จัดการแสดง/ซ่อนช่องหมายเหตุเมื่อเลือกสถานะใน Modal
    const statusInputs = document.querySelectorAll('input[name="attendanceStatus"]');
    const remarksContainer = document.getElementById('remarksContainer');
    
    statusInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'late' || this.value === 'leave') {
                remarksContainer.classList.remove('d-none');
            } else {
                remarksContainer.classList.add('d-none');
            }
        });
    });

    // ตรวจสอบค่าเริ่มต้น
    const checkedStatus = document.querySelector('input[name="attendanceStatus"]:checked');
    if (checkedStatus) {
        if (checkedStatus.value === 'late' || checkedStatus.value === 'leave') {
            remarksContainer.classList.remove('d-none');
        } else {
            remarksContainer.classList.add('d-none');
        }
    }
});
</script>