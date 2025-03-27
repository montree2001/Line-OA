<!-- คอนเทนต์หน้าจัดการชั้นเรียนและแผนกวิชา -->
<div class="container-fluid">
    <!-- Dashboard Summary Cards -->
    <div class="dashboard-summary">
        <div class="row">
            <div class="col-md-3">
                <div class="info-card primary">
                    <div class="info-card-inner">
                        <div class="icon-container">
                            <span class="material-icons">account_balance</span>
                        </div>
                        <div class="info-container">
                            <h3><?php echo count($data['departments']); ?></h3>
                            <p>แผนกวิชา</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card success">
                    <div class="info-card-inner">
                        <div class="icon-container">
                            <span class="material-icons">class</span>
                        </div>
                        <div class="info-container">
                            <h3><?php echo count($data['classes']); ?></h3>
                            <p>ชั้นเรียน</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card warning">
                    <div class="info-card-inner">
                        <div class="icon-container">
                            <span class="material-icons">person</span>
                        </div>
                        <div class="info-container">
                            <h3><?php echo count($data['teachers']); ?></h3>
                            <p>ครูที่ปรึกษา</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-card danger">
                    <div class="info-card-inner">
                        <div class="icon-container">
                            <span class="material-icons">warning</span>
                        </div>
                        <div class="info-container">
                            <h3><?php echo $at_risk_count; ?></h3>
                            <p>นักเรียนเสี่ยงตกกิจกรรม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ส่วนจัดการแผนกวิชา -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title"><span class="material-icons">account_balance</span> จัดการแผนกวิชา</h5>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="showDepartmentModal()">
                        <span class="material-icons">add</span> เพิ่มแผนกวิชาใหม่
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ส่วนจัดการแผนกวิชา -->
        <div class="card-body">
            <div class="table-responsive">
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
    </div>

    <!-- ส่วนจัดการชั้นเรียน -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title"><span class="material-icons">class</span> จัดการชั้นเรียน</h5>
                </div>
                <div>
                    <?php if ($data['has_new_academic_year']): ?>
                    <button class="btn btn-warning me-2" onclick="showPromoteStudentsModal()">
                        <span class="material-icons">upgrade</span> เลื่อนชั้นนักเรียน
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-primary" onclick="showAddClassModal()">
                        <span class="material-icons">add</span> เพิ่มชั้นเรียนใหม่
                    </button>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <!-- ตัวกรองข้อมูล -->
            <div class="filter-box mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ปีการศึกษา</label>
                            <select id="academicYearFilter" class="form-control" onchange="filterClasses()">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($data['academic_years'] as $year): ?>
                                <option value="<?php echo $year['academic_year_id']; ?>" <?php echo ($year['is_active'] ? 'selected' : ''); ?>>
                                    <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>ระดับชั้น</label>
                            <select id="levelFilter" class="form-control" onchange="filterClasses()">
                                <option value="">ทั้งหมด</option>
                                <option value="ปวช.1">ปวช.1</option>
                                <option value="ปวช.2">ปวช.2</option>
                                <option value="ปวช.3">ปวช.3</option>
                                <option value="ปวส.1">ปวส.1</option>
                                <option value="ปวส.2">ปวส.2</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>แผนกวิชา</label>
                            <select id="departmentFilter" class="form-control" onchange="filterClasses()">
                                <option value="">ทั้งหมด</option>
                                <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                                <option value="<?php echo $dept_key; ?>"><?php echo $dept['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-outline-primary form-control" onclick="filterClasses()">
                                <span class="material-icons">filter_list</span> กรองข้อมูล
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ตารางชั้นเรียน -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">รหัส</th>
                            <th width="25%">ชั้นเรียน</th>
                            <th width="25%">ครูที่ปรึกษา</th>
                            <th width="10%">จำนวนนักเรียน</th>
                            <th width="15%">อัตราการเข้าแถว</th>
                            <th width="10%">สถานะ</th>
                            <th width="10%">จัดการ</th>
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
                                <?php if (empty($class['advisors'])): ?>
                                    <span class="text-muted">ไม่มีครูที่ปรึกษา</span>
                                <?php else: ?>
                                    <?php foreach ($class['advisors'] as $index => $advisor): ?>
                                        <div class="mb-1">
                                            <?php echo ($advisor['is_primary'] ? '<strong>' : '') . $advisor['name'] . ($advisor['is_primary'] ? ' <span class="badge badge-primary">หลัก</span></strong>' : ''); ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-secondary mt-1" onclick="manageAdvisors(<?php echo $class['class_id']; ?>)">
                                    <span class="material-icons">people</span> จัดการครู
                                </button>
                            </td>
                            <td><?php echo $class['student_count']; ?> คน</td>
                            <td>
                                <div class="attendance-bar-container">
                                    <div class="attendance-bar" style="width: <?php echo $class['attendance_rate']; ?>%;">
                                        <span class="attendance-rate <?php 
                                            echo $class['attendance_rate'] > 90 ? 'good' : 
                                                ($class['attendance_rate'] > 75 ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo number_format($class['attendance_rate'], 1); ?>%
                                        </span>
                                    </div>
                                </div>
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
    </div>
</div>

<!-- โมดัลเพิ่ม/แก้ไขแผนกวิชา -->
<div class="modal fade" id="departmentModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentModalTitle">เพิ่มแผนกวิชาใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('departmentModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="departmentForm" onsubmit="return false;">
                <div class="modal-body">
                    <input type="hidden" id="departmentId" name="department_id" value="">
                    
                    <div class="form-group">
                        <label for="departmentCode">รหัสแผนกวิชา</label>
                        <input type="text" id="departmentCode" class="form-control" name="department_code" placeholder="รหัสแผนกวิชา เช่น IT, AUTO, MECH">
                        <small class="form-text text-muted">รหัสแผนกต้องเป็นภาษาอังกฤษและไม่ซ้ำกับรหัสที่มีอยู่แล้ว</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="departmentName">ชื่อแผนกวิชา</label>
                        <input type="text" id="departmentName" class="form-control" name="department_name" placeholder="ชื่อแผนกวิชา" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('departmentModal')">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveDepartment()">
                        <span class="material-icons">save</span> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลเพิ่ม/แก้ไขชั้นเรียน -->
<div class="modal fade" id="classModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classModalTitle">เพิ่มชั้นเรียนใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('classModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="classForm" onsubmit="return false;">
                <div class="modal-body">
                    <input type="hidden" id="classId" name="class_id" value="">
                    
                    <div class="form-group">
                        <label for="academicYear">ปีการศึกษา</label>
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
                        <label for="classLevel">ระดับชั้น</label>
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
                        <label for="classDepartment">แผนกวิชา</label>
                        <select id="classDepartment" class="form-control" name="department_id" required>
                            <option value="">เลือกแผนกวิชา</option>
                            <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                            <option value="<?php echo $dept_key; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="groupNumber">กลุ่มเรียน</label>
                        <input type="number" id="groupNumber" class="form-control" name="group_number" min="1" max="20" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="classroom">ห้องเรียนประจำ (ถ้ามี)</label>
                        <input type="text" id="classroom" class="form-control" name="classroom" placeholder="เช่น 202, ห้องปฏิบัติการคอมพิวเตอร์">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('classModal')">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveClass()">
                        <span class="material-icons">save</span> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลจัดการครูที่ปรึกษา -->
<div class="modal fade" id="advisorsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">จัดการครูที่ปรึกษา <span id="advisorsClassTitle" class="badge badge-primary"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('advisorsModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="advisor-management">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="current-advisors">
                                <h6 class="section-title">ครูที่ปรึกษาปัจจุบัน</h6>
                                <div id="currentAdvisorsList" class="advisor-items">
                                    <!-- จะถูกเติมด้วย JavaScript -->
                                    <div class="text-muted">กำลังโหลดข้อมูล...</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="add-advisor">
                                <h6 class="section-title">เพิ่มครูที่ปรึกษา</h6>
                                <div class="form-group">
                                    <label for="advisorSelect">เลือกครู</label>
                                    <select id="advisorSelect" class="form-control selectpicker" data-live-search="true">
                                        <option value="">-- เลือกครูที่ปรึกษา --</option>
                                        <?php foreach ($data['teachers'] as $teacher): ?>
                                        <option value="<?php echo $teacher['teacher_id']; ?>">
                                            <?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" id="isPrimaryAdvisor" class="form-check-input">
                                    <label for="isPrimaryAdvisor" class="form-check-label">ครูที่ปรึกษาหลัก</label>
                                    <small class="form-text text-muted">*ครูที่ปรึกษาหลักจะมีเพียงคนเดียวต่อชั้นเรียน</small>
                                </div>
                                <button class="btn btn-info btn-block" onclick="addAdvisor()">
                                    <span class="material-icons">add</span> เพิ่มครูที่ปรึกษา
                                </button>
                            </div>
                            
                            <div class="advisor-changes-summary mt-4">
                                <h6 class="section-title">สรุปการเปลี่ยนแปลง</h6>
                                <div id="changesLog" class="changes-log">
                                    <div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cancelAdvisorChanges()">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="saveAdvisorsChanges()">
                    <span class="material-icons">save</span> บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลรายละเอียดชั้นเรียน -->
<div class="modal fade" id="classDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classDetailsTitle">รายละเอียดชั้นเรียน</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('classDetailsModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="class-details-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title">ข้อมูลชั้นเรียน</h6>
                                </div>
                                <div class="card-body">
                                    <table class="details-table table">
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
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title">ครูที่ปรึกษา</h6>
                                </div>
                                <div class="card-body">
                                    <div id="advisorsList" class="advisors-list">
                                        <!-- จะถูกเติมด้วย JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title">สถิติการเข้าแถว</h6>
                                </div>
                                <div class="card-body">
                                    <div class="attendance-chart-container">
                                        <canvas id="classAttendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h6 class="card-title">สถิติตามเดือน</h6>
                                </div>
                                <div class="card-body">
                                    <div class="attendance-chart-container">
                                        <canvas id="monthlyAttendanceChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title">รายชื่อนักเรียน</h6>
                        </div>
                        <div class="card-body">
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
                                        <!-- จะถูกเติมด้วย JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('classDetailsModal')">ปิด</button>
                <button type="button" class="btn btn-info" onclick="downloadClassReport()">
                    <span class="material-icons">file_download</span> ดาวน์โหลดรายงาน
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลเลื่อนชั้นนักเรียน -->
<div class="modal fade" id="promoteStudentsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <span class="material-icons">upgrade</span> เลื่อนชั้นนักเรียน
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('promoteStudentsModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="d-flex">
                        <div class="me-3">
                            <span class="material-icons fs-4">info</span>
                        </div>
                        <div>
                            <h6 class="alert-heading">การเลื่อนชั้นนักเรียนจะดำเนินการดังนี้:</h6>
                            <ul class="mb-0">
                                <li>นักเรียนชั้น ปวช.1 จะเลื่อนขึ้นไป ปวช.2</li>
                                <li>นักเรียนชั้น ปวช.2 จะเลื่อนขึ้นไป ปวช.3</li>
                                <li>นักเรียนชั้น ปวส.1 จะเลื่อนขึ้นไป ปวส.2</li>
                                <li>นักเรียนชั้น ปวช.3 และ ปวส.2 จะถูกตั้งค่าเป็น "สำเร็จการศึกษา"</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="promotion-section row">
                    <div class="col-md-6">
                        <div class="form-section p-3 mb-3 border rounded">
                            <h6>ข้อมูลการเลื่อนชั้น</h6>
                            <div class="form-group">
                                <label for="fromAcademicYear">ปีการศึกษาต้นทาง</label>
                                <select id="fromAcademicYear" class="form-control">
                                    <?php foreach ($data['academic_years'] as $year): ?>
                                        <?php if ($year['is_active']): ?>
                                            <option value="<?php echo $year['academic_year_id']; ?>" selected>
                                                <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนออก</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="toAcademicYear">ปีการศึกษาปลายทาง</label>
                                <select id="toAcademicYear" class="form-control">
                                    <?php 
                                    // แสดงเฉพาะปีการศึกษาที่อยู่ถัดไป
                                    $nextYearFound = false;
                                    foreach ($data['academic_years'] as $year): 
                                        if ($year['is_active']) continue; // ข้ามปีปัจจุบัน
                                        
                                        if (!$nextYearFound):
                                            $nextYearFound = true;
                                    ?>
                                            <option value="<?php echo $year['academic_year_id']; ?>" selected>
                                                <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                            </option>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    
                                    // ถ้าไม่พบปีการศึกษาถัดไป ให้แสดงตัวเลือกเพิ่มปีการศึกษาใหม่
                                    if (!$nextYearFound):
                                    ?>
                                        <option value="new" class="text-success">+ เพิ่มปีการศึกษาใหม่</option>
                                    <?php endif; ?>
                                </select>
                                <small class="text-muted">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนเข้า</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="promotionNotes">หมายเหตุการเลื่อนชั้น</label>
                                <textarea id="promotionNotes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="promotion-summary p-3 border rounded">
                            <h6>สรุปจำนวนนักเรียนที่จะเลื่อนชั้น</h6>
                            <div id="promotionChart" class="chart-container mb-3">
                                <!-- จะถูกเติมด้วย JavaScript -->
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ระดับชั้นปัจจุบัน</th>
                                            <th class="text-center">จำนวนนักเรียน</th>
                                            <th>ระดับชั้นใหม่</th>
                                        </tr>
                                    </thead>
                                    <tbody id="promotionCountsBody">
                                        <?php if (isset($data['promotion_counts']) && is_array($data['promotion_counts'])): ?>
                                            <?php foreach ($data['promotion_counts'] as $promotion): ?>
                                                <tr>
                                                    <td><?php echo $promotion['current_level']; ?></td>
                                                    <td class="text-center"><?php echo $promotion['student_count']; ?> คน</td>
                                                    <td class="<?php echo $promotion['new_level'] === 'สำเร็จการศึกษา' ? 'text-success' : ''; ?>">
                                                        <?php echo $promotion['new_level']; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">ไม่พบข้อมูลนักเรียนที่จะเลื่อนชั้น</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>รวมทั้งหมด</th>
                                            <th class="text-center">
                                                <?php 
                                                    $total = 0;
                                                    if (isset($data['promotion_counts']) && is_array($data['promotion_counts'])) {
                                                        foreach ($data['promotion_counts'] as $promotion) {
                                                            $total += $promotion['student_count'];
                                                        }
                                                    }
                                                    echo $total . ' คน';
                                                ?>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('promoteStudentsModal')">ยกเลิก</button>
                <button id="promoteBtn" type="button" class="btn btn-warning" onclick="confirmPromoteStudents()">
                    <span class="material-icons">upgrade</span> ดำเนินการเลื่อนชั้น
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModal('confirmDeleteModal')">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <span class="material-icons">warning</span>
                    <div id="deleteWarningMessage">คุณต้องการลบรายการนี้ใช่หรือไม่?</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">ยกเลิก</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton">
                    <span class="material-icons">delete</span> ยืนยันการลบ
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* ปรับแต่งสไตล์เพิ่มเติมสำหรับหน้าจัดการชั้นเรียน */
.dashboard-summary {
    margin-bottom: 1.5rem;
}

.info-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
}

.info-card.primary {
    border-left: 4px solid #4a6cf7;
}

.info-card.success {
    border-left: 4px solid #2ecc71;
}

.info-card.warning {
    border-left: 4px solid #f39c12;
}

.info-card.danger {
    border-left: 4px solid #e74c3c;
}

.info-card-inner {
    display: flex;
    align-items: center;
    padding: 1.5rem;
    background-color: white;
}

.icon-container {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 1rem;
}

.info-card.primary .icon-container {
    background-color: rgba(74, 108, 247, 0.1);
    color: #4a6cf7;
}

.info-card.success .icon-container {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.info-card.warning .icon-container {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.info-card.danger .icon-container {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.info-container h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.info-container p {
    margin: 0;
    color: #777;
    font-size: 0.875rem;
}

.card-title {
    display: flex;
    align-items: center;
    margin-bottom: 0;
}

.card-title .material-icons {
    margin-right: 0.5rem;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.card-header {
    background-color: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.25rem;
}

.card-body {
    padding: 1.25rem;
}

.filter-box {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.data-table th,
.data-table td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.data-table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.data-table tbody tr {
    border-bottom: 1px solid #dee2e6;
    transition: all 0.3s;
}

.data-table tbody tr:hover {
    background-color: #f5f5f5;
}

.class-info {
    display: flex;
    align-items: center;
}

.class-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #4a6cf7;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
}

.class-details {
    flex: 1;
}

.class-name {
    font-weight: 600;
    margin-bottom: 3px;
}

.class-dept {
    font-size: 12px;
    color: #666;
}

.attendance-bar-container {
    width: 100%;
    height: 20px;
    background-color: #f5f5f5;
    border-radius: 10px;
    overflow: hidden;
}

.attendance-bar {
    height: 100%;
    background-color: #4a6cf7;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
}

.attendance-rate {
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.attendance-rate.good {
    color: white;
}

.attendance-rate.warning {
    color: white;
}

.attendance-rate.danger {
    color: white;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
}

.status-badge.success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.status-badge.warning {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.status-badge.danger {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.action-buttons {
    display: flex;
    gap: 5px;
    justify-content: center;
}

.table-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.table-action-btn.primary {
    background-color: rgba(74, 108, 247, 0.1);
    color: #4a6cf7;
}

.table-action-btn.success {
    background-color: rgba(46, 204, 113, 0.1);
    color: #2ecc71;
}

.table-action-btn.warning {
    background-color: rgba(243, 156, 18, 0.1);
    color: #f39c12;
}

.table-action-btn.danger {
    background-color: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
}

.table-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.table-action-btn.primary:hover {
    background-color: #4a6cf7;
    color: white;
}

.table-action-btn.success:hover {
    background-color: #2ecc71;
    color: white;
}

.table-action-btn.warning:hover {
    background-color: #f39c12;
    color: white;
}

.table-action-btn.danger:hover {
    background-color: #e74c3c;
    color: white;
}

.modal-content {
    border: none;
    border-radius: 8px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.5rem;
}

.modal-footer {
    border-top: 1px solid rgba(0, 0, 0, 0.05);
    padding: 1rem 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-control {
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #dee2e6;
    transition: all 0.2s;
}

.form-control:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 0.2rem rgba(74, 108, 247, 0.25);
}

.form-check-input {
    margin-top: 0.25rem;
}

.close {
    font-size: 1.25rem;
}

.btn {
    padding: 0.375rem 0.75rem;
    border-radius: 4px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn .material-icons {
    margin-right: 0.25rem;
    font-size: 18px;
}

.btn-primary {
    background-color: #4a6cf7;
    border-color: #4a6cf7;
}

.btn-primary:hover {
    background-color: #3a5de5;
    border-color: #3a5de5;
}

.btn-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}

.btn-secondary:hover {
    background-color: #5a6268;
    border-color: #5a6268;
}

.btn-info {
    background-color: #17a2b8;
    border-color: #17a2b8;
}

.btn-info:hover {
    background-color: #138496;
    border-color: #138496;
}

.btn-warning {
    background-color: #f39c12;
    border-color: #f39c12;
    color: white;
}

.btn-warning:hover {
    background-color: #d68910;
    border-color: #d68910;
    color: white;
}

.btn-danger {
    background-color: #e74c3c;
    border-color: #e74c3c;
}

.btn-danger:hover {
    background-color: #c0392b;
    border-color: #c0392b;
}

.btn-outline-primary {
    color: #4a6cf7;
    border-color: #4a6cf7;
}

.btn-outline-primary:hover {
    background-color: #4a6cf7;
    border-color: #4a6cf7;
    color: white;
}

.alert {
    display: flex;
    align-items: flex-start;
    border-radius: 8px;
    padding: 1rem;
}

.alert-info {
    background-color: rgba(23, 162, 184, 0.1);
    border-left: 4px solid #17a2b8;
}

.alert-danger {
    background-color: rgba(231, 76, 60, 0.1);
    border-left: 4px solid #e74c3c;
}

.alert .material-icons {
    margin-right: 0.5rem;
    margin-top: 0.125rem;
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    font-size: 1rem;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.5rem;
}

.advisor-management {
    background-color: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
}

.current-advisors, .add-advisor, .advisor-changes-summary {
    background-color: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.advisor-items {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.advisor-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.advisor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #4a6cf7;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    font-weight: bold;
}

.advisor-info {
    flex: 1;
}

.advisor-position {
    font-size: 12px;
    color: #666;
}

.advisor-action {
    display: flex;
    gap: 5px;
}

.changes-log {
    font-size: 14px;
    max-height: 200px;
    overflow-y: auto;
}

.change-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.change-item:last-child {
    border-bottom: none;
}

.change-add {
    color: #2ecc71;
}

.change-remove {
    color: #e74c3c;
}

.change-primary {
    color: #4a6cf7;
}

.advisors-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-primary {
    background-color: #4a6cf7;
    color: white;
}

.attendance-chart-container {
    height: 250px;
    background-color: #f8f9fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.chart-container {
    height: 200px;
    background-color: #f8f9fa;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1rem;
}

.promotion-section {
    margin-top: 1rem;
}

.text-success {
    color: #2ecc71;
}

.details-table {
    width: 100%;
}

.details-table th {
    width: 35%;
    font-weight: 600;
    color: #666;
}

@media (max-width: 767.98px) {
    .action-buttons {
        flex-wrap: wrap;
    }

    .card-header {
        padding: 1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .data-table th, 
    .data-table td {
        padding: 0.5rem;
    }

    .advisor-management {
        flex-direction: column;
    }

    .current-advisors, 
    .add-advisor {
        width: 100%;
    }
}
</style>