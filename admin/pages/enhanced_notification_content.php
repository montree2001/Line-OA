<!-- หน้าส่งข้อความแจ้งเตือนผู้ปกครองแบบปรับปรุง -->
<div class="page-header">
    <div class="page-title">
        <h1>
            <i class="material-icons">notifications</i>
            <?= $page_title ?>
        </h1>
        <p class="page-subtitle">ส่งข้อความแจ้งเตือนและรายงานการเข้าแถวถึงผู้ปกครองผ่าน LINE</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" id="btnSendHistory">
            <i class="material-icons">history</i>
            ประวัติการส่งข้อความ
        </button>
    </div>
</div>

<!-- แท็บสำหรับรูปแบบการส่งข้อความ -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="individual">
            <i class="material-icons">person</i> ส่งรายบุคคล
        </div>
        <div class="tab" data-tab="group">
            <i class="material-icons">group</i> ส่งกลุ่ม
        </div>
        <div class="tab" data-tab="templates">
            <i class="material-icons">description</i> จัดการเทมเพลต
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บส่งรายบุคคล -->
<div id="individual-tab" class="tab-content active">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="material-icons">search</i>
                ค้นหานักเรียน
            </div>
            <div class="card-actions">
                <button class="btn btn-sm btn-secondary" id="btnResetFilter">
                    <i class="material-icons">refresh</i> รีเซ็ต
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <form id="studentSearchForm" class="filter-form">
                <div class="filter-container">
                    <div class="filter-group">
                        <label for="student_name">ชื่อ/รหัสนักเรียน</label>
                        <input type="text" class="form-control" id="student_name" name="student_name" placeholder="ชื่อ / รหัสนักเรียน">
                    </div>
                    
                    <div class="filter-group">
                        <label for="department_id">แผนกวิชา</label>
                        <select class="form-control" id="department_id" name="department_id">
                            <option value="">-- ทุกแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= $dept['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="class_level">ระดับชั้น</label>
                        <select class="form-control" id="class_level" name="class_level">
                            <option value="">-- ทุกระดับชั้น --</option>
                            <?php foreach ($levels as $level): ?>
                            <option value="<?= $level ?>"><?= $level ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="class_group">กลุ่ม</label>
                        <select class="form-control" id="class_group" name="class_group">
                            <option value="">-- ทุกกลุ่ม --</option>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="advisor_id">ครูที่ปรึกษา</label>
                        <select class="form-control" id="advisor_id" name="advisor_id">
                            <option value="">-- ทุกคน --</option>
                            <?php foreach ($advisors as $advisor): ?>
                            <option value="<?= $advisor['teacher_id'] ?>"><?= $advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="risk_status">สถานะความเสี่ยง</label>
                        <select class="form-control" id="risk_status" name="risk_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="เสี่ยงตกกิจกรรม" selected>เสี่ยงตกกิจกรรม</option>
                            <option value="ต้องระวัง">ต้องระวัง</option>
                            <option value="ปกติ">ปกติ</option>
                        </select>
                    </div>
                    
                    <div class="filter-group filter-action">
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons">search</i>
                            ค้นหา
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="filter-result-info">
                <div class="filter-result-count">พบนักเรียนทั้งหมด <span id="totalStudents"><?= $total_students ?></span> คน</div>
                <div class="filter-result-pagination">
                    <select id="pageSize" class="form-control form-control-sm">
                        <option value="10">10 รายการ</option>
                        <option value="20" selected>20 รายการ</option>
                        <option value="50">50 รายการ</option>
                        <option value="100">100 รายการ</option>
                    </select>
                    <div class="pagination-controls">
                        <button id="prevPage" class="btn btn-sm btn-icon" disabled>
                            <i class="material-icons">chevron_left</i>
                        </button>
                        <span id="pageInfo">หน้า <span id="currentPage">1</span> จาก <span id="totalPages">1</span></span>
                        <button id="nextPage" class="btn btn-sm btn-icon" disabled>
                            <i class="material-icons">chevron_right</i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table" id="studentsTable">
                    <thead>
                        <tr>
                            <th width="5%"></th>
                            <th width="25%">นักเรียน</th>
                            <th width="15%">ชั้น/แผนก</th>
                            <th width="15%">เข้าแถว</th>
                            <th width="15%">สถานะ</th>
                            <th width="15%">ผู้ปกครอง</th>
                            <th width="10%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $index => $student): ?>
                            <tr data-student-id="<?= $student['student_id'] ?>">
                                <td>
                                    <input type="radio" name="student_select" value="<?= $student['student_id'] ?>" <?= ($index === 0) ? 'checked' : '' ?>>
                                </td>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"><?= $student['initial'] ?></div>
                                        <div class="student-details">
                                            <div class="student-name"><?= $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name'] ?></div>
                                            <div class="student-code">รหัส <?= $student['student_code'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= $student['class'] ?><br>
                                    <small class="text-muted"><?= $student['department_name'] ?></small>
                                </td>
                                <td><?= $student['attendance_days'] ?></td>
                                <td><span class="status-badge <?= $student['status_class'] ?>"><?= $student['status'] ?></span></td>
                                <td>
                                    <?php if (!empty($student['parents_info'])): ?>
                                    <div class="parent-info">
                                        <span class="parent-count"><?= $student['parent_count'] ?> คน</span>
                                        <span class="parent-names"><?= $student['parents_info'] ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-danger">ไม่พบข้อมูลผู้ปกครอง</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-icon history-btn" title="ดูประวัติการส่ง" data-student-id="<?= $student['student_id'] ?>">
                                            <i class="material-icons">history</i>
                                        </button>
                                        <button class="btn-icon send-btn" title="ส่งข้อความ" data-student-id="<?= $student['student_id'] ?>">
                                            <i class="material-icons">send</i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียน</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header">
            <div class="card-title">
                <i class="material-icons">message</i>
                ส่งข้อความถึงผู้ปกครอง - 
                <span class="student-name-display">
                <?php if (!empty($students)): ?>
                    <?= $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name'] ?>
                <?php else: ?>
                    เลือกนักเรียน
                <?php endif; ?>
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <div class="date-range-selector">
                <div class="date-range-title">
                    <i class="material-icons">date_range</i>
                    ช่วงวันที่การเข้าแถวที่ต้องการรายงาน
                </div>
                <div class="date-range-inputs">
                    <div class="date-group">
                        <label for="start-date">วันที่เริ่มต้น</label>
                        <input type="date" id="start-date" class="form-control">
                    </div>
                    <div class="date-group">
                        <label for="end-date">วันที่สิ้นสุด</label>
                        <input type="date" id="end-date" class="form-control">
                    </div>
                    <button id="btnUpdateDateRange" class="btn btn-outline-primary">
                        <i class="material-icons">refresh</i> อัปเดตข้อมูล
                    </button>
                </div>
            </div>
            
            <div class="template-selector">
                <label for="templateSelect">เลือกเทมเพลตข้อความ</label>
                <select id="templateSelect" class="form-control">
                    <option value="">-- เลือกเทมเพลต --</option>
                    <?php foreach ($individual_templates as $template): ?>
                    <option value="<?= $template['id'] ?>"><?= $template['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="message-type-selector">
                <div class="btn-group template-buttons">
                    <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
                    <button class="template-btn" data-template="warning">แจ้งเตือนความเสี่ยง</button>
                    <button class="template-btn" data-template="critical">แจ้งเตือนฉุกเฉิน</button>
                    <button class="template-btn" data-template="summary">รายงานสรุป</button>
                    <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
                </div>
            </div>

            <div class="message-editor">
                <label for="messageText">ข้อความที่ต้องการส่ง</label>
                <textarea class="form-control message-textarea" id="messageText" rows="8" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
<?= $school_name ?></textarea>
                
                <div class="variable-helper">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                        <i class="material-icons">code</i> ตัวแปรที่ใช้ได้
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" data-variable="{{ชื่อนักเรียน}}">{{ชื่อนักเรียน}} - ชื่อนักเรียน</a>
                        <a class="dropdown-item" href="#" data-variable="{{ชั้นเรียน}}">{{ชั้นเรียน}} - ระดับชั้น/กลุ่ม</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันเข้าแถว}}">{{จำนวนวันเข้าแถว}} - จำนวนวันเข้าแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันทั้งหมด}}">{{จำนวนวันทั้งหมด}} - จำนวนวันทั้งหมด</a>
                        <a class="dropdown-item" href="#" data-variable="{{ร้อยละการเข้าแถว}}">{{ร้อยละการเข้าแถว}} - ร้อยละการเข้าแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันขาด}}">{{จำนวนวันขาด}} - จำนวนวันขาดแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{สถานะการเข้าแถว}}">{{สถานะการเข้าแถว}} - สถานะความเสี่ยง</a>
                        <a class="dropdown-item" href="#" data-variable="{{ชื่อครูที่ปรึกษา}}">{{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา</a>
                        <a class="dropdown-item" href="#" data-variable="{{เบอร์โทรครู}}">{{เบอร์โทรครู}} - เบอร์โทรศัพท์ครู</a>
                    </div>
                </div>
            </div>
            
            <div class="message-options">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-chart" checked>
                                        <label class="custom-control-label" for="include-chart">แนบกราฟสรุปการเข้าแถว</label>
                                    </div>
                                    <small class="form-text text-muted">เพิ่มกราฟแสดงอัตราการเข้าแถวในช่วงเวลาที่เลือก</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-link" checked>
                                        <label class="custom-control-label" for="include-link">แนบลิงก์ดูข้อมูลโดยละเอียด</label>
                                    </div>
                                    <small class="form-text text-muted">เพิ่มลิงก์ให้ผู้ปกครองดูข้อมูลการเข้าแถวโดยละเอียด</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="btn btn-sm btn-outline-primary" id="btnShowPreview">
                        <i class="material-icons">visibility</i>
                        แสดงตัวอย่างเต็ม
                    </button>
                </div>
                <div class="preview-content">
                    <div class="preview-line-header">
                        <img src="assets/images/line-logo.png" alt="LINE" width="24" height="24">
                        <strong>LINE Official Account: SADD-Prasat</strong>
                    </div>
                    <div class="preview-message">
                        <p>
                        <?php if (!empty($students)): ?>
                            เรียน ผู้ปกครองของ <?= $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name'] ?><br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน <?= $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name'] ?> นักเรียนชั้น <?= $students[0]['class'] ?> ปัจจุบันเข้าร่วม <?= $students[0]['attendance_days'] ?><br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br><?= $school_name ?>
                        <?php else: ?>
                            เรียน ผู้ปกครองของ (ชื่อนักเรียน)<br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน (ชื่อนักเรียน) นักเรียนชั้น (ระดับชั้น) ปัจจุบันเข้าร่วม (จำนวนวัน) วัน จากทั้งหมด (จำนวนวันทั้งหมด) วัน ((ร้อยละ)%)<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br><?= $school_name ?>
                        <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="preview-chart">
                        <canvas id="attendance-chart" width="100%" height="200"></canvas>
                    </div>
                    
                    <div class="preview-link">
                        <a href="#" class="detail-link">
                            <i class="material-icons">open_in_new</i>
                            ดูข้อมูลโดยละเอียด
                        </a>
                    </div>
                </div>
            </div>

            <div class="message-cost">
                <div class="cost-title">
                    <i class="material-icons">monetization_on</i>
                    ค่าใช้จ่ายในการส่ง
                </div>
                <div class="cost-details">
                    <div class="cost-item">
                        <span class="cost-label">ข้อความ:</span>
                        <span class="cost-value">0.075 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">รูปภาพกราฟ:</span>
                        <span class="cost-value">0.150 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">ลิงก์:</span>
                        <span class="cost-value">0.075 บาท</span>
                    </div>
                    <div class="cost-item total">
                        <span class="cost-label">รวม:</span>
                        <span class="cost-value">0.300 บาท</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" id="btnResetMessage">
                    <i class="material-icons">refresh</i> รีเซ็ตข้อความ
                </button>
                <button class="btn btn-primary" id="btnSendIndividual">
                    <i class="material-icons">send</i>
                    ส่งข้อความ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บส่งกลุ่ม -->
<div id="group-tab" class="tab-content">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="material-icons">filter_list</i>
                ตัวกรองนักเรียนสำหรับส่งข้อความกลุ่ม
            </div>
        </div>
        
        <div class="card-body">
            <form id="groupFilterForm" class="filter-form">
                <div class="filter-container">
                    <div class="filter-group">
                        <label for="group_department_id">แผนกวิชา</label>
                        <select class="form-control" id="group_department_id" name="department_id">
                            <option value="">-- ทุกแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= $dept['department_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="group_class_level">ระดับชั้น</label>
                        <select class="form-control" id="group_class_level" name="class_level">
                            <option value="">-- ทุกระดับชั้น --</option>
                            <?php foreach ($levels as $level): ?>
                            <option value="<?= $level ?>"><?= $level ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="group_class_group">กลุ่ม</label>
                        <select class="form-control" id="group_class_group" name="class_group">
                            <option value="">-- ทุกกลุ่ม --</option>
                            <?php for ($i = 1; $i <= 9; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="group_advisor_id">ครูที่ปรึกษา</label>
                        <select class="form-control" id="group_advisor_id" name="advisor_id">
                            <option value="">-- ทุกคน --</option>
                            <?php foreach ($advisors as $advisor): ?>
                            <option value="<?= $advisor['teacher_id'] ?>"><?= $advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="group_risk_status">สถานะความเสี่ยง</label>
                        <select class="form-control" id="group_risk_status" name="risk_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="เสี่ยงตกกิจกรรม" selected>เสี่ยงตกกิจกรรม</option>
                            <option value="ต้องระวัง">ต้องระวัง</option>
                            <option value="ปกติ">ปกติ</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="group_attendance_rate">อัตราการเข้าแถว</label>
                        <select class="form-control" id="group_attendance_rate" name="attendance_rate">
                            <option value="">-- ทั้งหมด --</option>
                            <option value="น้อยกว่า 70%" selected>น้อยกว่า 70%</option>
                            <option value="70% - 80%">70% - 80%</option>
                            <option value="80% - 90%">80% - 90%</option>
                            <option value="มากกว่า 90%">มากกว่า 90%</option>
                        </select>
                    </div>
                    
                    <div class="filter-group filter-action">
                        <button type="submit" class="btn btn-primary">
                            <i class="material-icons">filter_list</i>
                            กรองข้อมูล
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="filter-group-result">พบนักเรียนที่ตรงตามเงื่อนไข <span id="groupTotalStudents">0</span> คน</div>
            
            <div class="recipients-container" id="recipientsContainer">
                <!-- รายการนักเรียนที่จะส่งข้อความจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                <div class="no-recipients">กรุณาใช้ตัวกรองเพื่อค้นหานักเรียน</div>
            </div>
            
            <div class="batch-actions">
                <button class="btn btn-secondary" id="btnSelectAllRecipients">
                    <i class="material-icons">select_all</i> เลือกทั้งหมด
                </button>
                <button class="btn btn-secondary" id="btnClearAllRecipients">
                    <i class="material-icons">clear_all</i> ยกเลิกเลือกทั้งหมด
                </button>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <div class="card-title">
                <i class="material-icons">send</i>
                ส่งข้อความถึงผู้ปกครองกลุ่ม (<span class="recipient-count">0</span> คน)
            </div>
        </div>
        
        <div class="card-body">
            <div class="date-range-selector">
                <div class="date-range-title">
                    <i class="material-icons">date_range</i>
                    ช่วงวันที่การเข้าแถวที่ต้องการรายงาน
                </div>
                <div class="date-range-inputs">
                    <div class="date-group">
                        <label for="start-date-group">วันที่เริ่มต้น</label>
                        <input type="date" id="start-date-group" class="form-control">
                    </div>
                    <div class="date-group">
                        <label for="end-date-group">วันที่สิ้นสุด</label>
                        <input type="date" id="end-date-group" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="template-selector">
                <label for="groupTemplateSelect">เลือกเทมเพลตข้อความ</label>
                <select id="groupTemplateSelect" class="form-control">
                    <option value="">-- เลือกเทมเพลต --</option>
                    <?php foreach ($group_templates as $template): ?>
                    <option value="<?= $template['id'] ?>"><?= $template['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="message-type-selector">
                <div class="btn-group template-buttons">
                    <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
                    <button class="template-btn" data-template="risk-warning">แจ้งเตือนกลุ่มเสี่ยง</button>
                    <button class="template-btn" data-template="meeting">นัดประชุมผู้ปกครอง</button>
                    <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
                </div>
            </div>

            <div class="message-editor">
                <label for="groupMessageText">ข้อความที่ต้องการส่ง</label>
                <textarea class="form-control message-textarea" id="groupMessageText" rows="8" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
<?= $school_name ?></textarea>
                
                <div class="variable-helper">
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                        <i class="material-icons">code</i> ตัวแปรที่ใช้ได้
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" data-variable="{{ชื่อนักเรียน}}">{{ชื่อนักเรียน}} - ชื่อนักเรียน</a>
                        <a class="dropdown-item" href="#" data-variable="{{ชั้นเรียน}}">{{ชั้นเรียน}} - ระดับชั้น/กลุ่ม</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันเข้าแถว}}">{{จำนวนวันเข้าแถว}} - จำนวนวันเข้าแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันทั้งหมด}}">{{จำนวนวันทั้งหมด}} - จำนวนวันทั้งหมด</a>
                        <a class="dropdown-item" href="#" data-variable="{{ร้อยละการเข้าแถว}}">{{ร้อยละการเข้าแถว}} - ร้อยละการเข้าแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{จำนวนวันขาด}}">{{จำนวนวันขาด}} - จำนวนวันขาดแถว</a>
                        <a class="dropdown-item" href="#" data-variable="{{สถานะการเข้าแถว}}">{{สถานะการเข้าแถว}} - สถานะความเสี่ยง</a>
                        <a class="dropdown-item" href="#" data-variable="{{ชื่อครูที่ปรึกษา}}">{{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา</a>
                        <a class="dropdown-item" href="#" data-variable="{{เบอร์โทรครู}}">{{เบอร์โทรครู}} - เบอร์โทรศัพท์ครู</a>
                    </div>
                </div>
            </div>
            
            <div class="message-options">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="form-row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-chart-group" checked>
                                        <label class="custom-control-label" for="include-chart-group">แนบกราฟสรุปการเข้าแถวแยกรายบุคคล</label>
                                    </div>
                                    <small class="form-text text-muted">เพิ่มกราฟแสดงอัตราการเข้าแถวสำหรับแต่ละคน</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="include-link-group" checked>
                                        <label class="custom-control-label" for="include-link-group">แนบลิงก์ดูข้อมูลโดยละเอียด</label>
                                    </div>
                                    <small class="form-text text-muted">เพิ่มลิงก์ให้ผู้ปกครองดูข้อมูลการเข้าแถวโดยละเอียด</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="btn btn-sm btn-outline-primary" id="btnShowGroupPreview">
                        <i class="material-icons">visibility</i>
                        แสดงตัวอย่างเต็ม
                    </button>
                </div>
                <div class="preview-content">
                    <div class="preview-line-header">
                        <img src="assets/images/line-logo.png" alt="LINE" width="24" height="24">
                        <strong>LINE Official Account: SADD-Prasat</strong>
                    </div>
                    <div class="preview-message">
                        <p>เรียน ท่านผู้ปกครองนักเรียนชั้น ปวช.1/1<br><br>ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด<br><br>โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา<br><br>กรุณาติดต่อครูที่ปรึกษาประจำชั้น ปวช.1/1 ครูอิศรา สุขใจ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br><?= $school_name ?></p>
                    </div>
                    
                    <div class="preview-chart">
                        <div class="group-chart-note">* แต่ละนักเรียนจะได้รับกราฟข้อมูลการเข้าแถวเฉพาะของตนเอง</div>
                        <canvas id="group-attendance-chart" width="100%" height="200"></canvas>
                    </div>
                    
                    <div class="preview-link">
                        <a href="#" class="detail-link">
                            <i class="material-icons">open_in_new</i>
                            ดูข้อมูลโดยละเอียด
                        </a>
                    </div>
                </div>
            </div>

            <div class="message-cost">
                <div class="cost-title">
                    <i class="material-icons">monetization_on</i>
                    ประมาณการค่าใช้จ่ายในการส่ง
                </div>
                <div class="cost-details">
                    <div class="cost-item">
                        <span class="cost-label">ข้อความ (0 คน):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">รูปภาพกราฟ (0 รูป):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">ลิงก์ (0 ลิงก์):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item total">
                        <span class="cost-label">รวม:</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" id="btnResetGroupMessage">
                    <i class="material-icons">refresh</i> รีเซ็ตข้อความ
                </button>
                <button class="btn btn-primary" id="btnSendGroup" disabled>
                    <i class="material-icons">send</i>
                    ส่งข้อความ (0 ราย)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บจัดการเทมเพลต -->
<div id="templates-tab" class="tab-content">
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="material-icons">description</i>
                จัดการเทมเพลตข้อความแจ้งเตือน
            </div>
            <div class="card-actions">
                <button class="btn btn-primary" id="btnCreateTemplate">
                    <i class="material-icons">add</i>
                    สร้างเทมเพลตใหม่
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <div class="template-filters">
                <div class="btn-group template-categories mb-3">
                    <button class="category-btn active" data-category="all">ทั้งหมด</button>
                    <button class="category-btn" data-category="attendance">การเข้าแถว</button>
                    <button class="category-btn" data-category="meeting">การประชุม</button>
                    <button class="category-btn" data-category="activity">กิจกรรม</button>
                    <button class="category-btn" data-category="other">อื่นๆ</button>
                </div>
                
                <div class="btn-group template-types mb-3 ml-2">
                    <button class="type-btn active" data-type="all">ทุกประเภท</button>
                    <button class="type-btn" data-type="individual">รายบุคคล</button>
                    <button class="type-btn" data-type="group">กลุ่ม</button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table" id="templatesTable">
                    <thead>
                        <tr>
                            <th width="30%">ชื่อเทมเพลต</th>
                            <th width="15%">ประเภท</th>
                            <th width="15%">หมวดหมู่</th>
                            <th width="15%">สร้างเมื่อ</th>
                            <th width="15%">ใช้งานล่าสุด</th>
                            <th width="10%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $all_templates = [];
                        if (method_exists($template_manager, 'getTemplatesByTypeAndCategory')) {
                            $individual = $template_manager->getTemplatesByTypeAndCategory('individual');
                            $group = $template_manager->getTemplatesByTypeAndCategory('group');
                            if (is_array($individual) && is_array($group)) {
                                $all_templates = array_merge($individual, $group);
                            }
                        }
                        
                        if (!empty($all_templates)):
                            foreach ($all_templates as $template): 
                        ?>
                        <tr data-template-id="<?= $template['id'] ?>" data-type="<?= $template['type'] ?>" data-category="<?= $template['category'] ?>">
                            <td>
                                <div class="template-name">
                                    <i class="material-icons template-icon"><?= $template['type'] == 'individual' ? 'person' : 'group' ?></i>
                                    <span><?= $template['name'] ?></span>
                                </div>
                            </td>
                            <td><?= $template['type'] == 'individual' ? 'รายบุคคล' : 'กลุ่ม' ?></td>
                            <td>
                                <?php
                                switch ($template['category']) {
                                    case 'attendance': echo 'การเข้าแถว'; break;
                                    case 'meeting': echo 'การประชุม'; break;
                                    case 'activity': echo 'กิจกรรม'; break;
                                    case 'other': 
                                    default: echo 'อื่นๆ'; break;
                                }
                                ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($template['created_at'])) ?></td>
                            <td><?= $template['last_used'] ? date('d/m/Y H:i', strtotime($template['last_used'])) : '-' ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-icon edit-template-btn" title="แก้ไข" data-template-id="<?= $template['id'] ?>">
                                        <i class="material-icons">edit</i>
                                    </button>
                                    <button class="btn-icon preview-template-btn" title="ดูตัวอย่าง" data-template-id="<?= $template['id'] ?>">
                                        <i class="material-icons">visibility</i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="6" class="text-center">ไม่พบข้อมูลเทมเพลต</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลสร้าง/แก้ไขเทมเพลต -->
<div class="modal" id="templateModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">สร้างเทมเพลตข้อความใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="templateForm">
                    <input type="hidden" id="template_id" name="template_id">
                    
                    <div class="form-group">
                        <label for="template_name">ชื่อเทมเพลต</label>
                        <input type="text" class="form-control" id="template_name" name="name" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="template_type">ประเภท</label>
                            <select class="form-control" id="template_type" name="type" required>
                                <option value="individual">รายบุคคล</option>
                                <option value="group">กลุ่ม</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="template_category">หมวดหมู่</label>
                            <select class="form-control" id="template_category" name="category" required>
                                <option value="attendance">การเข้าแถว</option>
                                <option value="meeting">การประชุม</option>
                                <option value="activity">กิจกรรม</option>
                                <option value="other">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="template_content">เนื้อหาข้อความ</label>
                        <textarea class="form-control message-textarea" id="template_content" name="content" rows="12" required></textarea>
                        <div class="form-text text-muted">
                            คุณสามารถใช้ตัวแปรในข้อความได้ เช่น {{ชื่อนักเรียน}}, {{ชั้นเรียน}}, {{ร้อยละการเข้าแถว}}
                        </div>
                    </div>
                    
                    <div class="variable-helper mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                            <i class="material-icons">code</i> ตัวแปรที่ใช้ได้
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{ชื่อนักเรียน}}">{{ชื่อนักเรียน}} - ชื่อนักเรียน</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{ชั้นเรียน}}">{{ชั้นเรียน}} - ระดับชั้น/กลุ่ม</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{จำนวนวันเข้าแถว}}">{{จำนวนวันเข้าแถว}} - จำนวนวันเข้าแถว</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{จำนวนวันทั้งหมด}}">{{จำนวนวันทั้งหมด}} - จำนวนวันทั้งหมด</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{ร้อยละการเข้าแถว}}">{{ร้อยละการเข้าแถว}} - ร้อยละการเข้าแถว</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{จำนวนวันขาด}}">{{จำนวนวันขาด}} - จำนวนวันขาดแถว</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{สถานะการเข้าแถว}}">{{สถานะการเข้าแถว}} - สถานะความเสี่ยง</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{ชื่อครูที่ปรึกษา}}">{{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา</a>
                            <a class="dropdown-item" href="#" data-target="#template_content" data-variable="{{เบอร์โทรครู}}">{{เบอร์โทรครู}} - เบอร์โทรศัพท์ครู</a>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="btnSaveTemplate">
                    <i class="material-icons">save</i>
                    บันทึกเทมเพลต
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงตัวอย่างข้อความ -->
<div class="modal" id="previewModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ตัวอย่างข้อความที่จะส่ง</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="preview-line-content">
                    <div class="preview-line-header">
                        <img src="assets/images/line-logo.png" alt="LINE" width="24" height="24">
                        <strong>LINE Official Account: SADD-Prasat</strong>
                    </div>
                    <div id="previewText" class="mt-3">
                        <!-- เนื้อหาข้อความจะถูกแทรกที่นี่ด้วย JavaScript -->
                    </div>
                    
                    <div id="previewChartContainer" class="mt-3">
                        <canvas id="preview-attendance-chart" width="100%" height="200"></canvas>
                    </div>
                    
                    <div id="previewLinkContainer" class="mt-3">
                        <a href="#" class="detail-link">
                            <i class="material-icons">open_in_new</i>
                            ดูข้อมูลโดยละเอียด
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลประวัติการส่งข้อความ -->
<div class="modal" id="historyModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ประวัติการส่งข้อความ - <span class="history-student-name">นักเรียน</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="data-table" id="historyTable">
                        <thead>
                            <tr>
                                <th width="20%">วันที่ส่ง</th>
                                <th width="20%">ประเภท</th>
                                <th width="20%">ผู้ส่ง</th>
                                <th width="15%">สถานะ</th>
                                <th width="15%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ข้อมูลจะถูกเพิ่มด้วย JavaScript -->
                            <tr>
                                <td colspan="5" class="text-center">ไม่พบประวัติการส่งข้อความ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงผลการส่งข้อความ -->
<div class="modal" id="resultModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ผลลัพธ์การส่งข้อความ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="result-summary">
                    <div class="result-item success">
                        <i class="material-icons">check_circle</i>
                        <div class="result-value">0</div>
                        <div class="result-label">สำเร็จ</div>
                    </div>
                    <div class="result-item error">
                        <i class="material-icons">error</i>
                        <div class="result-value">0</div>
                        <div class="result-label">ล้มเหลว</div>
                    </div>
                    <div class="result-item cost">
                        <i class="material-icons">payments</i>
                        <div class="result-value">0.00 บาท</div>
                        <div class="result-label">ค่าใช้จ่าย</div>
                    </div>
                </div>
                
                <div class="result-details">
                    <div class="table-responsive">
                        <table class="data-table" id="resultTable">
                            <thead>
                                <tr>
                                    <th>นักเรียน</th>
                                    <th>ชั้น</th>
                                    <th>จำนวนผู้ปกครอง</th>
                                    <th>สถานะ</th>
                                    <th>ค่าใช้จ่าย</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- ข้อมูลจะถูกเพิ่มด้วย JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<!-- หน้าโหลดระหว่างส่งข้อความ -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-container">
        <div class="loading-spinner"></div>
        <div class="loading-text">กำลังส่งข้อความ <span id="sendingProgress">0/0</span></div>
        <div class="loading-subtitle">กรุณารอสักครู่...</div>
    </div>
</div>