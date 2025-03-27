<!-- คอนเทนต์หน้าจัดการชั้นเรียนและแผนกวิชา -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">account_balance</span>
        จัดการแผนกวิชา
    </div>
    
    <!-- ส่วนจัดการแผนกวิชา -->
    <div class="table-responsive">
        <div class="action-bar">
            <button class="btn btn-primary" onclick="showDepartmentModal()">
                <span class="material-icons">add</span>
                เพิ่มแผนกวิชาใหม่
            </button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รหัสแผนก</th>
                    <th>ชื่อแผนกวิชา</th>
                    <th>จำนวนนักเรียน</th>
                    <th>จำนวนชั้นเรียน</th>
                    <th>จำนวนครู</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody id="departmentTableBody">
                <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                <tr>
                    <td><?php echo $dept_key; ?></td>
                    <td><?php echo $dept['name']; ?></td>
                    <td><?php echo $dept['student_count']; ?></td>
                    <td><?php echo $dept['class_count']; ?></td>
                    <td><?php echo $dept['teacher_count']; ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="table-action-btn primary" onclick="viewDepartmentDetails('<?php echo $dept_key; ?>')">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" onclick="editDepartment('<?php echo $dept_key; ?>')">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="table-action-btn danger" onclick="deleteDepartment('<?php echo $dept_key; ?>')">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-title">
        <span class="material-icons">class</span>
        จัดการชั้นเรียน
    </div>
    
    <div class="filter-container">
        <div class="filter-group">
            <div class="filter-label">ปีการศึกษา</div>
            <select id="academicYearFilter" class="form-control" onchange="filterClasses()">
                <option value="">ทั้งหมด</option>
                <?php foreach ($data['academic_years'] as $year): ?>
                <option value="<?php echo $year['academic_year_id']; ?>" <?php echo ($year['is_active'] ? 'selected' : ''); ?>>
                    <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">ระดับชั้น</div>
            <select id="levelFilter" class="form-control" onchange="filterClasses()">
                <option value="">ทั้งหมด</option>
                <option value="ปวช.1">ปวช.1</option>
                <option value="ปวช.2">ปวช.2</option>
                <option value="ปวช.3">ปวช.3</option>
                <option value="ปวส.1">ปวส.1</option>
                <option value="ปวส.2">ปวส.2</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">แผนกวิชา</div>
            <select id="departmentFilter" class="form-control" onchange="filterClasses()">
                <option value="">ทั้งหมด</option>
                <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                <option value="<?php echo $dept_key; ?>"><?php echo $dept['name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="filter-button" onclick="filterClasses()">
            <span class="material-icons">filter_list</span>
            กรองข้อมูล
        </button>
    </div>
    
    <div class="action-bar">
        <button class="btn btn-primary" onclick="showAddClassModal()">
            <span class="material-icons">add</span>
            เพิ่มชั้นเรียนใหม่
        </button>
        <?php if ($data['has_new_academic_year']): ?>
        <button class="btn btn-warning" onclick="showPromoteStudentsModal()">
            <span class="material-icons">upgrade</span>
            เลื่อนชั้นนักเรียน
        </button>
        <?php endif; ?>
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>รหัสชั้นเรียน</th>
                    <th>ชั้นเรียน</th>
                    <th>ครูที่ปรึกษา</th>
                    <th>จำนวนนักเรียน</th>
                    <th>อัตราการเข้าแถว</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody id="classTableBody">
                <?php foreach ($data['classes'] as $class): ?>
                <tr class="class-row" 
                    data-academic-year="<?php echo $class['academic_year_id']; ?>"
                    data-level="<?php echo $class['level']; ?>"
                    data-department="<?php echo $class['department']; ?>">
                    <td><?php echo $class['class_id']; ?></td>
                    <td>
                        <div class="class-info">
                            <div class="class-avatar">
                                <?php echo substr($class['level'], 0, 1); ?>
                            </div>
                            <div class="class-details">
                                <div class="class-name"><?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?></div>
                                <div class="class-dept"><?php echo $class['department']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php foreach ($class['advisors'] as $index => $advisor): ?>
                            <?php echo ($index > 0 ? '<br>' : '') . ($advisor['is_primary'] ? '<strong>' : '') . $advisor['name'] . ($advisor['is_primary'] ? '</strong>' : ''); ?>
                        <?php endforeach; ?>
                    </td>
                    <td><?php echo $class['student_count']; ?></td>
                    <td>
                        <span class="attendance-rate <?php 
                            echo $class['attendance_rate'] > 90 ? 'good' : 
                                 ($class['attendance_rate'] > 75 ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo number_format($class['attendance_rate'], 1); ?>%
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php 
                            echo $class['attendance_rate'] > 90 ? 'success' : 
                                 ($class['attendance_rate'] > 75 ? 'warning' : 'danger'); 
                        ?>">
                            <?php 
                            echo $class['attendance_rate'] > 90 ? 'ปกติ' : 
                                 ($class['attendance_rate'] > 75 ? 'ต้องระวัง' : 'เสี่ยง'); 
                            ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="table-action-btn primary" onclick="showClassDetails(<?php echo $class['class_id']; ?>)">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" onclick="editClass(<?php echo $class['class_id']; ?>)">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="table-action-btn warning" onclick="manageAdvisors(<?php echo $class['class_id']; ?>)">
                                <span class="material-icons">people</span>
                            </button>
                            <button class="table-action-btn danger" onclick="deleteClass(<?php echo $class['class_id']; ?>)">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- โมดัลเพิ่ม/แก้ไขแผนกวิชา -->
<div class="modal" id="departmentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('departmentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="departmentModalTitle">เพิ่มแผนกวิชาใหม่</h2>
        
        <form id="departmentForm">
            <input type="hidden" id="departmentId" name="department_id" value="">
            
            <div class="form-group">
                <label class="form-label">ชื่อแผนกวิชา</label>
                <input type="text" id="departmentName" class="form-control" name="department_name" placeholder="กรอกชื่อแผนกวิชา" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('departmentModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกแผนกวิชา
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลเพิ่มชั้นเรียน -->
<div class="modal" id="addClassModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('addClassModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="classModalTitle">เพิ่มชั้นเรียนใหม่</h2>
        
        <form id="classForm">
            <input type="hidden" id="classId" name="class_id" value="">
            
            <div class="form-group">
                <label class="form-label">ปีการศึกษา</label>
                <select id="academicYear" class="form-control" name="academic_year_id" required>
                    <option value="">เลือกปีการศึกษา</option>
                    <?php foreach ($data['academic_years'] as $year): ?>
                    <option value="<?php echo $year['academic_year_id']; ?>" <?php echo ($year['is_active'] ? 'selected' : ''); ?>>
                        <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">ระดับชั้น</label>
                <select id="classLevel" class="form-control" name="level" required>
                    <option value="">เลือกระดับชั้น</option>
                    <option value="ปวช.1">ปวช.1</option>
                    <option value="ปวช.2">ปวช.2</option>
                    <option value="ปวช.3">ปวช.3</option>
                    <option value="ปวส.1">ปวส.1</option>
                    <option value="ปวส.2">ปวส.2</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">แผนกวิชา</label>
                <select id="classDepartment" class="form-control" name="department" required>
                    <option value="">เลือกแผนกวิชา</option>
                    <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                    <option value="<?php echo $dept_key; ?>"><?php echo $dept['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">กลุ่ม</label>
                <input type="number" id="groupNumber" class="form-control" name="group_number" min="1" max="20" value="1" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addClassModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกชั้นเรียน
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลรายละเอียดชั้นเรียน -->
<div class="modal large-modal" id="classDetailsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('classDetailsModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="classDetailsTitle">รายละเอียดชั้นเรียน</h2>
        
        <div class="class-details-content">
            <div class="row">
                <div class="col-6">
                    <h3>ข้อมูลชั้นเรียน</h3>
                    <table class="details-table">
                        <tr>
                            <th>ปีการศึกษา:</th>
                            <td id="detailAcademicYear"></td>
                        </tr>
                        <tr>
                            <th>ระดับชั้น:</th>
                            <td id="detailLevel"></td>
                        </tr>
                        <tr>
                            <th>แผนกวิชา:</th>
                            <td id="detailDepartment"></td>
                        </tr>
                        <tr>
                            <th>กลุ่ม:</th>
                            <td id="detailGroup"></td>
                        </tr>
                        <tr>
                            <th>จำนวนนักเรียน:</th>
                            <td id="detailStudentCount"></td>
                        </tr>
                    </table>
                </div>
                <div class="col-6">
                    <h3>ครูที่ปรึกษา</h3>
                    <div id="advisorsList" class="advisors-list"></div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-6">
                    <h3>สถิติการเข้าแถว</h3>
                    <div class="attendance-chart-container">
                        <canvas id="classAttendanceChart"></canvas>
                    </div>
                </div>
                <div class="col-6">
                    <h3>สถิติตามเดือน</h3>
                    <div class="attendance-chart-container">
                        <canvas id="monthlyAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
            
            <h3>รายชื่อนักเรียน</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>รหัสนักศึกษา</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>การเข้าแถว</th>
                            <th>ร้อยละ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        <!-- จะถูกเติมข้อมูลด้วย JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('classDetailsModal')">ปิด</button>
            <button class="btn btn-primary" onclick="downloadClassReport()">
                <span class="material-icons">file_download</span>
                ดาวน์โหลดรายงาน
            </button>
        </div>
    </div>
</div>

<!-- โมดัลจัดการครูที่ปรึกษา -->
<div class="modal" id="advisorsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('advisorsModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">จัดการครูที่ปรึกษา <span id="advisorsClassTitle"></span></h2>
        
        <div class="advisors-management">
            <div class="current-advisors">
                <h3>ครูที่ปรึกษาปัจจุบัน</h3>
                <div id="currentAdvisorsList" class="advisor-items">
                    <!-- รายการครูที่ปรึกษาปัจจุบัน จะถูกเติมด้วย JavaScript -->
                </div>
            </div>
            
            <div class="add-advisor">
                <h3>เพิ่มครูที่ปรึกษา</h3>
                <div class="form-group">
                    <label>เลือกครู</label>
                    <select id="advisorSelect" class="form-control">
                        <option value="">-- เลือกครูที่ปรึกษา --</option>
                        <?php foreach ($data['teachers'] as $teacher): ?>
                        <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="isPrimaryAdvisor" class="form-check-input">
                    <label for="isPrimaryAdvisor" class="form-check-label">ครูที่ปรึกษาหลัก</label>
                </div>
                <button class="btn btn-primary mt-2" onclick="addAdvisor()">
                    <span class="material-icons">add</span>
                    เพิ่มครูที่ปรึกษา
                </button>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('advisorsModal')">ปิด</button>
            <button class="btn btn-primary" onclick="saveAdvisorsChanges()">
                <span class="material-icons">save</span>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </div>
</div>

<!-- โมดัลเลื่อนชั้นนักเรียน -->
<div class="modal" id="promoteStudentsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('promoteStudentsModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">เลื่อนชั้นนักเรียน</h2>
        
        <div class="promotion-info">
            <div class="alert alert-info">
                <span class="material-icons">info</span>
                <div class="alert-content">
                    <p>ระบบจะดำเนินการเลื่อนชั้นนักเรียนจากปีการศึกษา <?php echo $data['current_academic_year']; ?> ไปยังปีการศึกษา <?php echo $data['next_academic_year']; ?></p>
                    <p>- นักเรียนชั้น ปวช.1 จะเลื่อนขึ้นไป ปวช.2</p>
                    <p>- นักเรียนชั้น ปวช.2 จะเลื่อนขึ้นไป ปวช.3</p>
                    <p>- นักเรียนชั้น ปวส.1 จะเลื่อนขึ้นไป ปวส.2</p>
                    <p>- นักเรียนชั้น ปวช.3 และ ปวส.2 จะถูกตั้งค่าเป็น "สำเร็จการศึกษา"</p>
                </div>
            </div>
            
            <div class="promotion-count-table">
                <h3>สรุปจำนวนนักเรียนที่จะเลื่อนชั้น</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ระดับชั้นปัจจุบัน</th>
                            <th>จำนวนนักเรียน</th>
                            <th>ระดับชั้นใหม่</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['promotion_counts'] as $promotion): ?>
                        <tr>
                            <td><?php echo $promotion['current_level']; ?></td>
                            <td><?php echo $promotion['student_count']; ?></td>
                            <td><?php echo $promotion['new_level']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('promoteStudentsModal')">ยกเลิก</button>
            <button class="btn btn-warning" onclick="confirmPromoteStudents()">
                <span class="material-icons">upgrade</span>
                ดำเนินการเลื่อนชั้น
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('confirmDeleteModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการลบ</h2>
        
        <div class="confirm-delete-content">
            <div class="alert alert-danger">
                <span class="material-icons">warning</span>
                <div id="deleteWarningMessage">คุณต้องการลบรายการนี้ใช่หรือไม่?</div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">ยกเลิก</button>
            <button class="btn btn-danger" id="confirmDeleteButton">
                <span class="material-icons">delete</span>
                ยืนยันการลบ
            </button>
        </div>
    </div>
</div>

<style>
/* สไตล์เพิ่มเติมสำหรับหน้าจัดการชั้นเรียน */
.class-dept {
    font-size: 12px;
    color: #666;
}

.action-bar {
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
}

.advisors-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.advisor-item {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: #f5f5f5;
    border-radius: 8px;
}

.advisor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e3f2fd;
    color: #1976d2;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
}

.advisor-info {
    flex: 1;
}

.primary-badge {
    background-color: #4caf50;
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 10px;
}

.advisors-management {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.current-advisors {
    flex: 1;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.add-advisor {
    flex: 1;
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 8px;
}

.advisor-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
}

.advisor-action {
    display: flex;
    gap: 5px;
}

.alert {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-info {
    background-color: #e3f2fd;
    color: #0d47a1;
}

.alert-danger {
    background-color: #ffebee;
    color: #b71c1c;
}

.alert .material-icons {
    margin-right: 10px;
    margin-top: 2px;
}

.alert-content {
    flex: 1;
}

.alert-content p {
    margin: 5px 0;
}

.promotion-count-table {
    margin-top: 20px;
}

.large-modal .modal-content {
    width: 80%;
    max-width: 1000px;
}

@media (max-width: 768px) {
    .advisors-management {
        flex-direction: column;
    }
    
    .large-modal .modal-content {
        width: 95%;
    }
}

/* Styles for table action buttons */
.table-action-btn.danger {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.table-action-btn.warning {
    background-color: var(--warning-color-light);
    color: var(--warning-color);
}
</style>

<script>
// ตัวแปรสำหรับเก็บข้อมูลชั่วคราว
let currentClassId = null;
let currentDepartmentId = null;
let advisorsChanges = [];
let deleteCallback = null;

// =============== ฟังก์ชันเกี่ยวกับแผนกวิชา ===============

// ฟังก์ชันแสดงโมดัลเพิ่มแผนกวิชาใหม่
function showDepartmentModal() {
    document.getElementById('departmentModalTitle').textContent = 'เพิ่มแผนกวิชาใหม่';
    document.getElementById('departmentId').value = '';
    document.getElementById('departmentName').value = '';
    showModal('departmentModal');
}

// ฟังก์ชันแก้ไขแผนกวิชา
function editDepartment(departmentId) {
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API
    let departmentName = '';
    const departmentRows = document.querySelectorAll('#departmentTableBody tr');
    for (const row of departmentRows) {
        if (row.cells[0].textContent === departmentId) {
            departmentName = row.cells[1].textContent;
            break;
        }
    }
    
    document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
    document.getElementById('departmentId').value = departmentId;
    document.getElementById('departmentName').value = departmentName;
    showModal('departmentModal');
}

// ฟังก์ชันลบแผนกวิชา
function deleteDepartment(departmentId) {
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบแผนกวิชารหัส <strong>${departmentId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบแผนกวิชาจะส่งผลต่อชั้นเรียนและนักเรียนทั้งหมดในแผนกนี้
    `;
    
    deleteCallback = () => {
        // ส่งคำขอ API เพื่อลบแผนกวิชา
        alert(`ลบแผนกวิชารหัส ${departmentId} แล้ว`);
        closeModal('confirmDeleteModal');
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

// ฟังก์ชันดูรายละเอียดแผนกวิชา
function viewDepartmentDetails(departmentId) {
    alert(`ดูรายละเอียดแผนกวิชารหัส ${departmentId}`);
    // ควรเปิดหน้าแสดงรายละเอียดแผนกวิชา
}

// =============== ฟังก์ชันเกี่ยวกับชั้นเรียน ===============

// ฟังก์ชันเปิดโมดัลเพิ่มชั้นเรียนใหม่
function showAddClassModal() {
    document.getElementById('classModalTitle').textContent = 'เพิ่มชั้นเรียนใหม่';
    document.getElementById('classId').value = '';
    document.getElementById('classForm').reset();
    
    // ตั้งค่าปีการศึกษาปัจจุบันเป็นค่าเริ่มต้น
    const activeYear = document.querySelector('#academicYear option[selected]');
    if (activeYear) {
        document.getElementById('academicYear').value = activeYear.value;
    }
    
    showModal('addClassModal');
}

// ฟังก์ชันแก้ไขชั้นเรียน
function editClass(classId) {
    currentClassId = classId;
    document.getElementById('classModalTitle').textContent = 'แก้ไขชั้นเรียน';
    
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API
    // จำลองการดึงข้อมูล
    const classRow = document.querySelector(`tr[data-class-id="${classId}"]`);
    if (classRow) {
        const academicYearId = classRow.getAttribute('data-academic-year');
        const level = classRow.getAttribute('data-level');
        const department = classRow.getAttribute('data-department');
        const groupNumber = classRow.querySelector('.class-name').textContent.split(' กลุ่ม ')[1];
        
        document.getElementById('classId').value = classId;
        document.getElementById('academicYear').value = academicYearId;
        document.getElementById('classLevel').value = level;
        document.getElementById('classDepartment').value = department;
        document.getElementById('groupNumber').value = groupNumber;
    }
    
    showModal('addClassModal');
}

// ฟังก์ชันลบชั้นเรียน
function deleteClass(classId) {
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียนรหัส <strong>${classId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนทั้งหมดในชั้นเรียนนี้
    `;
    
    deleteCallback = () => {
        // ส่งคำขอ API เพื่อลบชั้นเรียน
        alert(`ลบชั้นเรียนรหัส ${classId} แล้ว`);
        closeModal('confirmDeleteModal');
        // ในสถานการณ์จริง ควรโหลดข้อมูลใหม่หรืออัปเดต DOM
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

// ฟังก์ชันแสดงรายละเอียดชั้นเรียน
function showClassDetails(classId) {
    currentClassId = classId;
    
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API แล้วเติมข้อมูลใน DOM
    // จำลองข้อมูล
    const classData = {
        academic_year: '2568 (ภาคเรียนที่ 1)',
        level: 'ปวช.2',
        department: 'เทคโนโลยีสารสนเทศ',
        group_number: '1',
        student_count: 32,
        advisors: [
            { id: 1, name: 'นายมนตรี ศรีสุข', position: 'ครูจ้างสอน', is_primary: true },
            { id: 2, name: 'นางสาวใจดี มีเมตตา', position: 'ครูประจำ', is_primary: false }
        ],
        students: [
            { id: 1, code: '12345678910', name: 'นายทดสอบ ระบบดี', attendance: 38, total: 40, percent: 95, status: 'ปกติ' },
            { id: 2, code: '12345678911', name: 'นายทดลอง การเขียน', attendance: 32, total: 40, percent: 80, status: 'ต้องระวัง' },
            { id: 3, code: '12345678912', name: 'นางสาวทดสอบ การเขียน', attendance: 24, total: 40, percent: 60, status: 'เสี่ยง' }
        ],
        monthly_stats: [
            { month: 'ม.ค.', present: 90, absent: 10 },
            { month: 'ก.พ.', present: 85, absent: 15 },
            { month: 'มี.ค.', present: 88, absent: 12 },
            { month: 'เม.ย.', present: 92, absent: 8 },
            { month: 'พ.ค.', present: 94, absent: 6 }
        ]
    };
    
    // เติมข้อมูลพื้นฐาน
    document.getElementById('classDetailsTitle').textContent = `รายละเอียดชั้น ${classData.level} กลุ่ม ${classData.group_number} ${classData.department}`;
    document.getElementById('detailAcademicYear').textContent = classData.academic_year;
    document.getElementById('detailLevel').textContent = classData.level;
    document.getElementById('detailDepartment').textContent = classData.department;
    document.getElementById('detailGroup').textContent = classData.group_number;
    document.getElementById('detailStudentCount').textContent = `${classData.student_count} คน`;
    
    // เติมข้อมูลครูที่ปรึกษา
    const advisorsList = document.getElementById('advisorsList');
    advisorsList.innerHTML = '';
    classData.advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position}</div>
            </div>
        `;
        advisorsList.appendChild(advisorEl);
    });
    
    // เติมข้อมูลนักเรียน
    const studentTableBody = document.getElementById('studentTableBody');
    studentTableBody.innerHTML = '';
    classData.students.forEach(student => {
        const statusClass = student.percent > 90 ? 'success' : (student.percent > 75 ? 'warning' : 'danger');
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.code}</td>
            <td>${student.name}</td>
            <td>${student.attendance}/${student.total} วัน</td>
            <td>${student.percent.toFixed(1)}%</td>
            <td><span class="status-badge ${statusClass}">${student.status}</span></td>
        `;
        studentTableBody.appendChild(row);
    });
    
    // แสดงโมดัล
    showModal('classDetailsModal');
    
    // สร้างกราฟเมื่อโมดัลแสดงแล้ว
    setTimeout(() => {
        createAttendanceCharts(classData);
    }, 300);
}

// ฟังก์ชันสร้างกราฟการเข้าแถว
function createAttendanceCharts(classData) {
    // ในสถานการณ์จริง ควรใช้ Chart.js
    // จำลองกราฟด้วย HTML
    const overallChart = document.getElementById('classAttendanceChart');
    if (overallChart) {
        // คำนวณอัตราการเข้าแถวรวม
        const totalPresent = classData.students.reduce((sum, student) => sum + student.attendance, 0);
        const totalPossible = classData.students.reduce((sum, student) => sum + student.total, 0);
        const attendanceRate = (totalPresent / totalPossible * 100).toFixed(1);
        
        overallChart.innerHTML = `
            <div style="text-align: center; padding: 20px;">
                <div style="display: inline-flex; align-items: center; gap: 10px;">
                    <div style="width: 20px; height: 20px; background-color: #4caf50;"></div>
                    <span>เข้าแถว</span>
                    <div style="width: 20px; height: 20px; background-color: #f44336;"></div>
                    <span>ขาดแถว</span>
                </div>
                <div style="height: 200px; background: linear-gradient(to right, #4caf50 ${attendanceRate}%, #f44336 ${100-attendanceRate}%); margin-top: 10px; border-radius: 8px;">
                    <div style="text-align: center; font-size: 24px; color: white; padding-top: 80px;">
                        ${attendanceRate}% <small style="font-size: 14px;">เข้าแถว</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    // กราฟรายเดือน
    const monthlyChart = document.getElementById('monthlyAttendanceChart');
    if (monthlyChart) {
        let barsHtml = '';
        const months = classData.monthly_stats.map(item => item.month);
        const presentRates = classData.monthly_stats.map(item => (item.present / (item.present + item.absent) * 100).toFixed(1));
        
        for (let i = 0; i < months.length; i++) {
            barsHtml += `
                <div style="display: flex; flex-direction: column; align-items: center; flex: 1;">
                    <div style="height: ${presentRates[i] * 1.5}px; width: 30px; background-color: #4caf50; margin-bottom: 5px;"></div>
                    <div style="font-size: 12px;">${months[i]}</div>
                    <div style="font-size: 10px;">${presentRates[i]}%</div>
                </div>
            `;
        }
        
        monthlyChart.innerHTML = `
            <div style="padding: 20px;">
                <div style="display: flex; align-items: flex-end; justify-content: space-around; height: 180px;">
                    ${barsHtml}
                </div>
            </div>
        `;
    }
}

// =============== ฟังก์ชันเกี่ยวกับครูที่ปรึกษา ===============

// ฟังก์ชันเปิดโมดัลจัดการครูที่ปรึกษา
function manageAdvisors(classId) {
    currentClassId = classId;
    advisorsChanges = []; // รีเซ็ตการเปลี่ยนแปลง
    
    // ในสถานการณ์จริง ควรดึงข้อมูลจาก API
    // จำลองการดึงข้อมูล
    const classRow = document.querySelector(`tr[data-class-id="${classId}"]`);
    let className = '';
    if (classRow) {
        const level = classRow.getAttribute('data-level');
        const department = classRow.getAttribute('data-department');
        const groupNumber = classRow.querySelector('.class-name').textContent.split(' กลุ่ม ')[1];
        className = `${level} กลุ่ม ${groupNumber} ${department}`;
    } else {
        className = 'ปวช.2 กลุ่ม 1 เทคโนโลยีสารสนเทศ'; // ค่าเริ่มต้นถ้าไม่พบชั้นเรียน
    }
    
    document.getElementById('advisorsClassTitle').textContent = className;
    
    // จำลองข้อมูลครูที่ปรึกษาปัจจุบัน
    const currentAdvisors = [
        { id: 1, name: 'นายมนตรี ศรีสุข', position: 'ครูจ้างสอน', is_primary: true },
        { id: 2, name: 'นางสาวใจดี มีเมตตา', position: 'ครูประจำ', is_primary: false }
    ];
    
    // เติมข้อมูลครูที่ปรึกษาปัจจุบัน
    renderCurrentAdvisors(currentAdvisors);
    
    // แสดงโมดัล
    showModal('advisorsModal');
}

// ฟังก์ชันแสดงรายการครูที่ปรึกษาปัจจุบัน
function renderCurrentAdvisors(advisors) {
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    currentAdvisorsList.innerHTML = '';
    
    if (advisors.length === 0) {
        currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position}</div>
            </div>
            <div class="advisor-action">
                ${!advisor.is_primary ? `
                <button class="table-action-btn success" onclick="setAsPrimaryAdvisor(${advisor.id})">
                    <span class="material-icons">stars</span>
                </button>` : ''}
                <button class="table-action-btn danger" onclick="removeAdvisor(${advisor.id})">
                    <span class="material-icons">delete</span>
                </button>
            </div>
        `;
        currentAdvisorsList.appendChild(advisorEl);
    });
}

// ฟังก์ชันเพิ่มครูที่ปรึกษา
function addAdvisor() {
    const advisorId = document.getElementById('advisorSelect').value;
    const isPrimary = document.getElementById('isPrimaryAdvisor').checked;
    
    if (!advisorId) {
        alert('กรุณาเลือกครูที่ปรึกษา');
        return;
    }
    
    // ในสถานการณ์จริง ควรตรวจสอบว่าเลือกซ้ำหรือไม่
    // จำลองการเพิ่มครูที่ปรึกษา
    alert(`เพิ่มครูที่ปรึกษา ID: ${advisorId}, หลัก: ${isPrimary}`);
    
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'add',
        teacher_id: advisorId,
        is_primary: isPrimary
    });
    
    // รีเซ็ตฟอร์ม
    document.getElementById('advisorSelect').value = '';
    document.getElementById('isPrimaryAdvisor').checked = false;
}

// ฟังก์ชันตั้งเป็นครูที่ปรึกษาหลัก
function setAsPrimaryAdvisor(advisorId) {
    alert(`ตั้งครู ID: ${advisorId} เป็นครูที่ปรึกษาหลัก`);
    
    // บันทึกการเปลี่ยนแปลง
    advisorsChanges.push({
        action: 'set_primary',
        teacher_id: advisorId
    });
}

// ฟังก์ชันลบครูที่ปรึกษา
function removeAdvisor(advisorId) {
    if (confirm(`ต้องการลบครูที่ปรึกษา ID: ${advisorId} ออกจากชั้นเรียนนี้หรือไม่?`)) {
        alert(`ลบครูที่ปรึกษา ID: ${advisorId}`);
        
        // บันทึกการเปลี่ยนแปลง
        advisorsChanges.push({
            action: 'remove',
            teacher_id: advisorId
        });
    }
}

// ฟังก์ชันบันทึกการเปลี่ยนแปลงครูที่ปรึกษา
function saveAdvisorsChanges() {
    if (advisorsChanges.length === 0) {
        alert('ไม่มีการเปลี่ยนแปลง');
        closeModal('advisorsModal');
        return;
    }
    
    // ในสถานการณ์จริง ควรส่งข้อมูลไปยัง API
    console.log('บันทึกการเปลี่ยนแปลงครูที่ปรึกษา:', {
        class_id: currentClassId,
        changes: advisorsChanges
    });
    
    alert('บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว');
    closeModal('advisorsModal');
}

// =============== ฟังก์ชันเกี่ยวกับการเลื่อนชั้น ===============

// ฟังก์ชันแสดงโมดัลเลื่อนชั้นนักเรียน
function showPromoteStudentsModal() {
    showModal('promoteStudentsModal');
}

// ฟังก์ชันยืนยันการเลื่อนชั้นนักเรียน
function confirmPromoteStudents() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการเลื่อนชั้นนักเรียน? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        // ในสถานการณ์จริง ควรส่งคำขอไปยัง API
        alert('กำลังดำเนินการเลื่อนชั้นนักเรียน');
        closeModal('promoteStudentsModal');
    }
}

// =============== ฟังก์ชันการกรองและการจัดการโมดัล ===============

// ฟังก์ชันกรองชั้นเรียน
function filterClasses() {
    const academicYearFilter = document.getElementById('academicYearFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const departmentFilter = document.getElementById('departmentFilter').value;
    
    const classRows = document.querySelectorAll('.class-row');
    
    classRows.forEach(row => {
        const academicYear = row.getAttribute('data-academic-year');
        const level = row.getAttribute('data-level');
        const department = row.getAttribute('data-department');
        
        const academicYearMatch = !academicYearFilter || academicYear === academicYearFilter;
        const levelMatch = !levelFilter || level === levelFilter;
        const departmentMatch = !departmentFilter || department === departmentFilter;
        
        if (academicYearMatch && levelMatch && departmentMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ฟังก์ชันดาวน์โหลดรายงานชั้นเรียน
function downloadClassReport() {
    alert(`กำลังดาวน์โหลดรายงานชั้นเรียนรหัส ${currentClassId}`);
}

// ฟังก์ชันแสดงโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

// ฟังก์ชันปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listener สำหรับฟอร์ม
    document.getElementById('departmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const departmentId = document.getElementById('departmentId').value;
        const departmentName = document.getElementById('departmentName').value;
        
        // ตรวจสอบข้อมูล
        if (!departmentName) {
            alert('กรุณาระบุชื่อแผนกวิชา');
            return;
        }
        
        // ในสถานการณ์จริง ควรส่งข้อมูลไปยัง API
        if (departmentId) {
            // กรณีแก้ไข
            alert(`แก้ไขแผนกวิชา ID: ${departmentId}, ชื่อ: ${departmentName}`);
        } else {
            // กรณีเพิ่มใหม่
            alert(`เพิ่มแผนกวิชาใหม่ ชื่อ: ${departmentName}`);
        }
        
        closeModal('departmentModal');
    });
    
    document.getElementById('classForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const classId = document.getElementById('classId').value;
        const academicYear = document.getElementById('academicYear').value;
        const level = document.getElementById('classLevel').value;
        const department = document.getElementById('classDepartment').value;
        const groupNumber = document.getElementById('groupNumber').value;
        
        // ตรวจสอบข้อมูล
        if (!academicYear || !level || !department || !groupNumber) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }
        
        // ในสถานการณ์จริง ควรส่งข้อมูลไปยัง API
        if (classId) {
            // กรณีแก้ไข
            alert(`แก้ไขชั้นเรียน ID: ${classId}, ปีการศึกษา: ${academicYear}, ระดับ: ${level}, แผนก: ${department}, กลุ่ม: ${groupNumber}`);
        } else {
            // กรณีเพิ่มใหม่
            alert(`เพิ่มชั้นเรียนใหม่ ปีการศึกษา: ${academicYear}, ระดับ: ${level}, แผนก: ${department}, กลุ่ม: ${groupNumber}`);
        }
        
        closeModal('addClassModal');
    });
    
    // ตั้งค่า event listener สำหรับปุ่มปิดโมดัล
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // ปิดโมดัลเมื่อคลิกนอกกรอบ
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                this.style.display = 'none';
            }
        });
    });
});
</script>