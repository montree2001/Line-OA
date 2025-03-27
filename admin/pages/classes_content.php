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
    // ดึงข้อมูลแผนกวิชา
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
        // ส่งคำขอลบแผนกวิชา
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'classes.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'form_action';
        actionInput.value = 'delete_department';
        
        const departmentIdInput = document.createElement('input');
        departmentIdInput.type = 'hidden';
        departmentIdInput.name = 'department_id';
        departmentIdInput.value = departmentId;
        
        form.appendChild(actionInput);
        form.appendChild(departmentIdInput);
        document.body.appendChild(form);
        form.submit();
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
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
    
    // ดึงข้อมูลชั้นเรียน
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
    
    document.getElementById('classModalTitle').textContent = 'แก้ไขชั้นเรียน';
    showModal('addClassModal');
}

// ฟังก์ชันลบชั้นเรียน
function deleteClass(classId) {
    document.getElementById('deleteWarningMessage').innerHTML = `
        คุณต้องการลบชั้นเรียนรหัส <strong>${classId}</strong> ใช่หรือไม่?<br>
        <strong class="text-danger">คำเตือน:</strong> การลบชั้นเรียนจะส่งผลต่อนักเรียนทั้งหมดในชั้นเรียนนี้
    `;
    
    deleteCallback = () => {
        // ส่งคำขอลบชั้นเรียน
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'classes.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'form_action';
        actionInput.value = 'delete_class';
        
        const classIdInput = document.createElement('input');
        classIdInput.type = 'hidden';
        classIdInput.name = 'class_id';
        classIdInput.value = classId;
        
        form.appendChild(actionInput);
        form.appendChild(classIdInput);
        document.body.appendChild(form);
        form.submit();
    };
    
    document.getElementById('confirmDeleteButton').onclick = deleteCallback;
    showModal('confirmDeleteModal');
}

// ฟังก์ชันแสดงรายละเอียดชั้นเรียน
function showClassDetails(classId) {
    currentClassId = classId;
    
    // ส่งคำขอดูรายละเอียดชั้นเรียน
    window.location.href = `classes.php?action=view_class&class_id=${classId}`;
}

// ฟังก์ชันจัดการครูที่ปรึกษา
function manageAdvisors(classId) {
    currentClassId = classId;
    
    // ส่งคำขอจัดการครูที่ปรึกษา
    window.location.href = `classes.php?action=manage_advisors&class_id=${classId}`;
}

// ฟังก์ชันแสดงโมดัลเลื่อนชั้นนักเรียน
function showPromoteStudentsModal() {
    showModal('promoteStudentsModal');
}

// ฟังก์ชันยืนยันการเลื่อนชั้นนักเรียน
function confirmPromoteStudents() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการเลื่อนชั้นนักเรียน? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
        // ส่งคำขอเลื่อนชั้นนักเรียน
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'classes.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'form_action';
        actionInput.value = 'promote_students';
        
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// =============== ฟังก์ชันทั่วไป ===============

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
    window.location.href = `classes.php?action=download_report&class_id=${currentClassId}`;
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
    // แก้ไขฟอร์มแผนกวิชา
    const departmentForm = document.getElementById('departmentForm');
    if (departmentForm) {
        departmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const departmentId = document.getElementById('departmentId').value;
            const departmentName = document.getElementById('departmentName').value;
            
            // ตรวจสอบข้อมูล
            if (!departmentName) {
                alert('กรุณาระบุชื่อแผนกวิชา');
                return;
            }
            
            // เพิ่มฟิลด์ form_action
            const formAction = document.createElement('input');
            formAction.type = 'hidden';
            formAction.name = 'form_action';
            formAction.value = departmentId ? 'edit_department' : 'add_department';
            this.appendChild(formAction);
            
            // ส่งฟอร์ม
            this.method = 'POST';
            this.action = 'classes.php';
            this.submit();
        });
    }
    
    // แก้ไขฟอร์มชั้นเรียน
    const classForm = document.getElementById('classForm');
    if (classForm) {
        classForm.addEventListener('submit', function(e) {
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
            
            // เพิ่มฟิลด์ form_action
            const formAction = document.createElement('input');
            formAction.type = 'hidden';
            formAction.name = 'form_action';
            formAction.value = classId ? 'edit_class' : 'add_class';
            this.appendChild(formAction);
            
            // เปลี่ยนชื่อฟิลด์ department เป็น department_id
            const departmentField = document.getElementById('classDepartment');
            departmentField.name = 'department_id';
            
            // ส่งฟอร์ม
            this.method = 'POST';
            this.action = 'classes.php';
            this.submit();
        });
    }
    
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