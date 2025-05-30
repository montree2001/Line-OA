<!-- คอนเทนต์หน้าจัดการชั้นเรียนและแผนกวิชา (classes_content.php) -->

<?php
// ส่วนดีบักสำหรับตรวจสอบโครงสร้างข้อมูล (เปิดใช้งานด้วย ?debug=1)
if (isset($_GET['debug']) && $_GET['debug'] == '1'): 
?>
<div style="background:#f8f9fa;padding:15px;margin:15px 0;border:1px solid #ddd;border-radius:5px;">
    <h4>Debug: Data Structure</h4>
    <pre><?php print_r($data); ?></pre>
</div>
<?php endif; ?>

<!-- CSS เพิ่มเติมสำหรับแสดงครูที่ปรึกษา -->
<style>
    /* สไตล์สำหรับข้อมูลครูที่ปรึกษาในตาราง */
    .class-advisor-info {
        display: flex;
        align-items: center;
    }
    
    .advisor-icon {
        margin-right: 8px;
        font-size: 18px;
    }
    
    .advisor-name {
        flex: 1;
    }
    
    /* ปรับปรุงการแสดงรายการครูที่ปรึกษา */
    .advisor-item {
        display: flex;
        align-items: center;
        padding: 0.75rem;
        background-color: var(--light-color);
        border-radius: var(--border-radius);
        margin-bottom: 0.5rem;
        transition: all 0.2s;
    }
    
    .advisor-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    /* สไตล์ปุ่มจัดการครูที่ปรึกษา */
    .table-action-btn.success {
        background-color: var(--success-light);
        color: var(--success-color);
    }
    
    .table-action-btn.success:hover {
        background-color: var(--success-color);
        color: white;
    }
    
    /* สไตล์เพิ่มเติมสำหรับสถิติและแผนภูมิ */
    .chart-container {
        height: 220px;
        position: relative;
    }
    
    /* ปรับปรุงการแสดงสถิติการเข้าแถว */
    .attendance-bar-container {
        width: 100%;
        height: 20px;
        background-color: #f1f1f1;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 5px;
    }
    
    .no-advisor-message {
        text-align: center;
        padding: 15px;
    }
    
    .no-advisor-message .material-icons {
        font-size: 48px;
        color: #6c757d;
        margin-bottom: 10px;
    }
</style>

<!-- สรุปข้อมูล Dashboard -->
<div class="dashboard-summary row">
    <div class="col-md-3">
        <div class="info-card primary">
            <div class="info-card-inner">
                <div class="icon-container">
                    <span class="material-icons">groups</span>
                </div>
                <div class="info-container">
                    <h3><?php echo is_array($data['classes']) ? count($data['classes']) : 0; ?></h3>
                    <p>ชั้นเรียนทั้งหมด</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-card success">
            <div class="info-card-inner">
                <div class="icon-container">
                    <span class="material-icons">business</span>
                </div>
                <div class="info-container">
                    <h3><?php echo is_array($data['departments']) ? count($data['departments']) : 0; ?></h3>
                    <p>แผนกวิชา</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-card warning">
            <div class="info-card-inner">
                <div class="icon-container">
                    <span class="material-icons">supervisor_account</span>
                </div>
                <div class="info-container">
                    <h3><?php echo is_array($data['teachers']) ? count($data['teachers']) : 0; ?></h3>
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
                    <h3><?php echo isset($data['at_risk_count']) ? $data['at_risk_count'] : 0; ?></h3>
                    <p>นักเรียนเสี่ยงตกกิจกรรม</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- การ์ดจัดการชั้นเรียน -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <span class="material-icons">class</span>
            จัดการชั้นเรียน
        </div>
        <div class="card-actions">
            <button class="btn btn-primary btn-sm" onclick="showAddClassModal()">
                <span class="material-icons">add</span>
                เพิ่มชั้นเรียนใหม่
            </button>
            <?php if (isset($data['has_new_academic_year']) && $data['has_new_academic_year']): ?>
                <button class="btn btn-warning btn-sm" onclick="showModal('promoteStudentsModal')">
                    <span class="material-icons">upgrade</span>
                    เลื่อนชั้นนักเรียน
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card-body">
        <!-- ส่วนกรองข้อมูล -->
        <div class="filter-box">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="academicYearFilter">ปีการศึกษา</label>
                        <select id="academicYearFilter" class="form-control">
                            <option value="">ทั้งหมด</option>
                            <?php if (isset($data['academic_years']) && is_array($data['academic_years'])): ?>
                                <?php foreach ($data['academic_years'] as $year): ?>
                                    <option value="<?php echo $year['academic_year_id']; ?>" <?php echo (isset($year['is_active']) && $year['is_active'] ? 'selected' : ''); ?>>
                                        <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="levelFilter">ระดับชั้น</label>
                        <select id="levelFilter" class="form-control">
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
                        <label for="departmentFilter">แผนกวิชา</label>
                        <select id="departmentFilter" class="form-control">
                            <option value="">ทั้งหมด</option>
                            <?php if (isset($data['departments']) && is_array($data['departments'])): ?>
                                <?php foreach ($data['departments'] as $dept): ?>
                                    <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button onclick="filterClasses()" class="btn btn-primary form-control">
                            <span class="material-icons">filter_list</span>
                            กรองข้อมูล
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางข้อมูลชั้นเรียน -->
        <div class="table-responsive">
            <table id="classTable" class="data-table display responsive nowrap" width="100%">
                <thead>
                    <tr>
                        <th>รหัส</th>
                        <th>ชั้นเรียน</th>
                        <th>ครูที่ปรึกษา</th>
                        <th>จำนวนนักเรียน</th>
                        <th>การเข้าแถว</th>
                        <th>สถานะ</th>
                        <th width="120px">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="classTableBody">
                <?php if (empty($data['classes'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลชั้นเรียน</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['classes'] as $class): ?>
                            <tr class="class-row" 
                                data-academic-year="<?php echo $class['academic_year_id']; ?>"
                                data-level="<?php echo $class['level']; ?>"
                                data-department="<?php echo $class['department_id']; ?>">
                                
                                <td><?php echo $class['class_id']; ?></td>
                                
                                <td>
                                    <div class="class-info">
                                        <div class="class-avatar"><?php echo substr($class['level'], -2); ?></div>
                                        <div class="class-details">
                                            <div class="class-name"><?php echo $class['level']; ?> กลุ่ม <?php echo $class['group_number']; ?></div>
                                            <div class="class-dept"><?php echo $class['department_name']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if (empty($class['advisors'])): ?>
                                        <span class="text-muted">ไม่มีครูที่ปรึกษา</span>
                                    <?php else: ?>
                                        <?php foreach ($class['advisors'] as $index => $advisor): ?>
                                            <?php if ($index > 0): ?><br><?php endif; ?>
                                            <?php if ($advisor['is_primary']): ?>
                                                <strong><?php echo $advisor['name']; ?></strong>
                                                <span class="badge badge-primary">หลัก</span>
                                            <?php else: ?>
                                                <?php echo $advisor['name']; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center"><?php echo $class['student_count']; ?> คน</td>
                                
                                <td>
                                    <div class="attendance-bar-container">
                                        <div class="attendance-bar <?php 
                                            echo $class['attendance_rate'] > 90 ? 'good' : 
                                                ($class['attendance_rate'] > 75 ? 'warning' : 'danger'); 
                                        ?>" style="width: <?php echo $class['attendance_rate']; ?>%">
                                            <span class="attendance-rate"><?php echo round($class['attendance_rate']); ?>%</span>
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
                                                ($class['attendance_rate'] > 75 ? 'ต้องติดตาม' : 'เสี่ยง'); 
                                        ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="action-buttons">
                                        <button class="table-action-btn primary" 
                                                onclick="showClassDetails(<?php echo $class['class_id']; ?>)" 
                                                title="ดูรายละเอียด">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        
                                        <button class="table-action-btn warning" 
                                                onclick="manageAdvisors(<?php echo $class['class_id']; ?>)" 
                                                title="จัดการครูที่ปรึกษา">
                                            <span class="material-icons">people</span>
                                        </button>
                                        
                                        <button class="table-action-btn success" 
                                                onclick="editClass(<?php echo $class['class_id']; ?>)" 
                                                title="แก้ไข">
                                            <span class="material-icons">edit</span>
                                        </button>
                                        
                                        <button class="table-action-btn danger" 
                                                onclick="deleteClass(<?php echo $class['class_id']; ?>)" 
                                                title="ลบ">
                                            <span class="material-icons">delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                   
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- การ์ดจัดการแผนกวิชา -->
<div class="card mt-4">
    <div class="card-header">
        <div class="card-title">
            <span class="material-icons">business</span>
            จัดการแผนกวิชา
        </div>
        <div class="card-actions">
            <button class="btn btn-primary btn-sm" onclick="showDepartmentModal()">
                <span class="material-icons">add</span>
                เพิ่มแผนกวิชา
            </button>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table id="departmentTable" class="data-table display responsive nowrap" width="100%">
                <thead>
                    <tr>
                        <th>รหัสแผนกวิชา</th>
                        <th>ชื่อแผนกวิชา</th>
                        <th>จำนวนชั้นเรียน</th>
                        <th>จำนวนนักเรียน</th>
                        <th width="100px">การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="departmentTableBody">
                <?php if (empty($data['departments'])): ?>
        <tr>
            <td colspan="5" class="text-center">ไม่พบข้อมูลแผนกวิชา</td>
        </tr>
    <?php else: ?>
        <?php foreach ($data['departments'] as $dept): ?>
            <tr>
                <td><?php echo htmlspecialchars($dept['department_code'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($dept['department_name'] ?? 'ไม่ระบุ'); ?></td>
                <td class="text-center"><?php echo isset($dept['class_count']) ? $dept['class_count'] : 0; ?></td>
                <td class="text-center"><?php echo isset($dept['student_count']) ? $dept['student_count'] : 0; ?></td>
                <td>
                    <div class="action-buttons">
                        <button class="table-action-btn success" 
                                onclick="editDepartment('<?php echo $dept['department_id'] ?? ''; ?>')" 
                                title="แก้ไข">
                            <span class="material-icons">edit</span>
                        </button>
                        
                        <button class="table-action-btn danger" 
                                onclick="deleteDepartment('<?php echo $dept['department_id'] ?? ''; ?>')" 
                                title="ลบ">
                            <span class="material-icons">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- โมดัลเพิ่ม/แก้ไขชั้นเรียน -->
<div class="modal" id="classModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="classModalTitle">เพิ่มชั้นเรียนใหม่</h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="classForm">
                <input type="hidden" id="classId" name="class_id" value="">

                <div class="form-group">
                    <label for="academicYear">ปีการศึกษา <span class="text-danger">*</span></label>
                    <select id="academicYear" class="form-control" name="academic_year_id" required>
                        <option value="">เลือกปีการศึกษา</option>
                        <?php if (isset($data['academic_years']) && is_array($data['academic_years'])): ?>
                            <?php foreach ($data['academic_years'] as $year): ?>
                                <option value="<?php echo $year['academic_year_id']; ?>" <?php echo (isset($year['is_active']) && $year['is_active'] ? 'selected' : ''); ?>>
                                    <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="classLevel">ระดับชั้น <span class="text-danger">*</span></label>
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
                    <label for="classDepartment">แผนกวิชา <span class="text-danger">*</span></label>
                    <select id="classDepartment" class="form-control" name="department_id" required>
                        <option value="">เลือกแผนกวิชา</option>
                        <?php if (isset($data['departments']) && is_array($data['departments'])): ?>
                            <?php foreach ($data['departments'] as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>"><?php echo $dept['department_name']; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="groupNumber">กลุ่ม <span class="text-danger">*</span></label>
                    <input type="number" id="groupNumber" class="form-control" name="group_number" min="1" max="20" value="1" required>
                </div>

                <div class="form-group">
                    <label for="classroom">ห้องเรียนประจำ</label>
                    <input type="text" id="classroom" class="form-control" name="classroom" placeholder="เช่น 501, IT-Lab1">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="saveClass()">
                <span class="material-icons">save</span>
                บันทึกชั้นเรียน
            </button>
        </div>
    </div>
</div>

<!-- โมดัลเพิ่ม/แก้ไขแผนกวิชา -->
<div class="modal" id="departmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="departmentModalTitle">เพิ่มแผนกวิชา</h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="departmentForm">
                <input type="hidden" id="departmentId" name="department_id" value="">

                <div class="form-group">
                    <label for="departmentCode">รหัสแผนกวิชา <span class="text-danger">*</span></label>
                    <input type="text" id="departmentCode" class="form-control" name="department_code" placeholder="เช่น ELEC, IT" required>
                    <small class="form-text text-muted">รหัสแผนกวิชาควรเป็นตัวอักษรภาษาอังกฤษ 2-6 ตัว</small>
                </div>

                <div class="form-group">
                    <label for="departmentName">ชื่อแผนกวิชา <span class="text-danger">*</span></label>
                    <input type="text" id="departmentName" class="form-control" name="department_name" placeholder="เช่น ช่างไฟฟ้ากำลัง, เทคโนโลยีสารสนเทศ" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="saveDepartment()">
                <span class="material-icons">save</span>
                บันทึกแผนกวิชา
            </button>
        </div>
    </div>
</div>

<!-- โมดัลรายละเอียดชั้นเรียน -->
<div class="modal large-modal" id="classDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="classDetailsTitle">รายละเอียดชั้นเรียน</h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">info</span>
                                ข้อมูลชั้นเรียน
                            </div>
                        </div>
                        <div class="card-body">
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
                                    <th>ห้องเรียนประจำ:</th>
                                    <td id="detailClassroom"></td>
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
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">supervisor_account</span>
                                ครูที่ปรึกษา
                            </div>
                            <div class="card-actions">
                                <button class="btn btn-primary btn-sm" onclick="manageAdvisorsFromDetails()">
                                    <span class="material-icons">people</span>
                                    จัดการครูที่ปรึกษา
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="advisorsList" class="advisor-items">
                                <!-- จะถูกเติมโดย JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">bar_chart</span>
                                สถิติการเข้าแถว
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="classAttendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">date_range</span>
                                สถิติรายเดือน
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyAttendanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <div class="card-title">
                        <span class="material-icons">people</span>
                        รายชื่อนักเรียน
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-sm btn-primary" onclick="downloadClassReport()">
                            <span class="material-icons">file_download</span>
                            ดาวน์โหลดรายงาน
                        </button>
                    </div>
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
                                <!-- จะถูกเติมข้อมูลด้วย JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- เพิ่มคำแนะนำการใช้งาน -->
            <div class="alert alert-info mt-3">
                <div><strong>คำแนะนำการใช้งาน:</strong></div>
                <ul>
                    <li>จัดการครูที่ปรึกษาได้โดยคลิกที่ปุ่ม "จัดการครูที่ปรึกษา"</li>
                    <li>สามารถกำหนดครูที่ปรึกษาหลักได้ 1 คน และครูที่ปรึกษาร่วมได้หลายคน</li>
                    <li>ดาวน์โหลดรายงานข้อมูลชั้นเรียนได้โดยคลิกที่ปุ่ม "ดาวน์โหลดรายงาน"</li>
                </ul>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
        </div>
    </div>
</div>

<!-- โมดัลจัดการครูที่ปรึกษา -->
<div class="modal large-modal" id="advisorsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <span class="material-icons">people</span>
                จัดการครูที่ปรึกษา <span id="advisorsClassTitle"></span>
            </h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-5">
                    <div class="current-advisors card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">supervisor_account</span>
                                ครูที่ปรึกษาปัจจุบัน
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="currentAdvisorsList" class="advisor-items scrollable">
                                <!-- รายการครูที่ปรึกษาปัจจุบัน จะถูกเติมด้วย JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="add-advisor card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <span class="material-icons">person_add</span>
                                        เพิ่มครูที่ปรึกษา
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="form-search-box">
                                        <input type="text" id="teacherSearch" class="form-control" placeholder="ค้นหาครู...">
                                        <span class="material-icons search-icon">search</span>
                                    </div>
                                    <div class="form-group">
                                        <select id="advisorSelect" class="form-control" size="7">
                                            <option value="">-- เลือกครูที่ปรึกษา --</option>
                                            <?php if (isset($data['teachers']) && is_array($data['teachers'])): ?>
                                                <?php foreach ($data['teachers'] as $teacher): ?>
                                                    <option value="<?php echo $teacher['teacher_id']; ?>">
                                                        <?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                                        (<?php echo isset($teacher['department_name']) ? $teacher['department_name'] : 'ไม่ระบุแผนก'; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center mt-2">
                                        <div class="form-check primary-advisor-check me-3">
                                            <input type="checkbox" id="isPrimaryAdvisor" class="form-check-input">
                                            <label for="isPrimaryAdvisor" class="form-check-label">ตั้งเป็นครูที่ปรึกษาหลัก</label>
                                        </div>
                                        <button class="btn btn-primary ms-auto" onclick="addAdvisor()">
                                            <span class="material-icons">add</span>
                                            เพิ่มครูที่ปรึกษา
                                        </button>
                                    </div>
                                    <div class="form-helper-text mt-2">
                                        <small class="text-muted">* ครูที่ปรึกษาหลักจะมีเพียงคนเดียวต่อชั้นเรียน</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <div class="advisor-changes-summary card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <span class="material-icons">history</span>
                                        สรุปการเปลี่ยนแปลง
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="changesLog" class="changes-log">
                                        <div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cancelAdvisorChanges()">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="saveAdvisorsChanges()">
                <span class="material-icons">save</span>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </div>
</div>

<!-- โมดัลเลื่อนชั้นนักเรียน -->
<div class="modal large-modal" id="promoteStudentsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <span class="material-icons">upgrade</span>
                เลื่อนชั้นนักเรียน
            </h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <span class="material-icons">info</span>
                <div>
                    <p><strong>การเลื่อนชั้นนักเรียนจะดำเนินการดังนี้:</strong></p>
                    <ul>
                        <li>นักเรียนชั้น ปวช.1 จะเลื่อนขึ้นไป ปวช.2</li>
                        <li>นักเรียนชั้น ปวช.2 จะเลื่อนขึ้นไป ปวช.3</li>
                        <li>นักเรียนชั้น ปวส.1 จะเลื่อนขึ้นไป ปวส.2</li>
                        <li>นักเรียนชั้น ปวช.3 และ ปวส.2 จะถูกตั้งค่าเป็น "สำเร็จการศึกษา"</li>
                    </ul>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">settings</span>
                                ตั้งค่าการเลื่อนชั้น
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="fromAcademicYear">ปีการศึกษาต้นทาง</label>
                                <select id="fromAcademicYear" class="form-control">
                                    <?php if (isset($data['academic_years']) && is_array($data['academic_years'])): ?>
                                        <?php foreach ($data['academic_years'] as $year): ?>
                                            <?php if (isset($year['is_active']) && $year['is_active']): ?>
                                                <option value="<?php echo $year['academic_year_id']; ?>" selected>
                                                    <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนออก</small>
                            </div>

                            <div class="form-group mt-3">
                                <label for="toAcademicYear">ปีการศึกษาปลายทาง</label>
                                <select id="toAcademicYear" class="form-control">
                                    <?php
                                    // แสดงเฉพาะปีการศึกษาที่อยู่ถัดไป
                                    $nextYearFound = false;
                                    if (isset($data['academic_years']) && is_array($data['academic_years'])):
                                        foreach ($data['academic_years'] as $year):
                                            if (isset($year['is_active']) && $year['is_active']) continue; // ข้ามปีปัจจุบัน

                                            if (!$nextYearFound):
                                                $nextYearFound = true;
                                    ?>
                                                <option value="<?php echo $year['academic_year_id']; ?>" selected>
                                                    <?php echo $year['year']; ?> (ภาคเรียนที่ <?php echo $year['semester']; ?>)
                                                </option>
                                            <?php
                                            endif;
                                        endforeach;
                                    endif;

                                    // ถ้าไม่พบปีการศึกษาถัดไป ให้แสดงตัวเลือกเพิ่มปีการศึกษาใหม่
                                    if (!$nextYearFound):
                                        ?>
                                        <option value="new">+ เพิ่มปีการศึกษาใหม่</option>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนเข้า</small>
                            </div>

                            <?php if (!$nextYearFound): ?>
                                <div id="newAcademicYearForm" class="new-academic-year-form mt-3" style="display: none;">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <span class="material-icons">add_circle</span>
                                                เพิ่มปีการศึกษาใหม่
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label for="newYear">ปีการศึกษา</label>
                                                <input type="number" id="newYear" class="form-control" value="<?php echo (int)date('Y') + 544; ?>">
                                            </div>
                                            <div class="form-group mt-2">
                                                <label for="newSemester">ภาคเรียน</label>
                                                <select id="newSemester" class="form-control">
                                                    <option value="1">1</option>
                                                    <option value="2">2</option>
                                                </select>
                                            </div>
                                            <div class="form-group mt-2">
                                                <label for="newStartDate">วันเริ่มต้น</label>
                                                <input type="date" id="newStartDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                            <div class="form-group mt-2">
                                                <label for="newEndDate">วันสิ้นสุด</label>
                                                <input type="date" id="newEndDate" class="form-control" value="<?php echo date('Y-m-d', strtotime('+4 months')); ?>">
                                            </div>
                                            <button type="button" class="btn btn-primary mt-3" onclick="addNewAcademicYear()">
                                                <span class="material-icons">add</span>
                                                เพิ่มปีการศึกษา
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-group mt-3">
                                <label for="promotionNotes">หมายเหตุการเลื่อนชั้น</label>
                                <textarea id="promotionNotes" class="form-control" rows="3" placeholder="บันทึกหมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">
                                <span class="material-icons">stacked_bar_chart</span>
                                สรุปจำนวนนักเรียนที่จะเลื่อนชั้น
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="promotionChart" class="chart-container">
                                <!-- จะถูกเติมด้วย JS -->
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>ระดับชั้นปัจจุบัน</th>
                                            <th>จำนวนนักเรียน</th>
                                            <th>ระดับชั้นใหม่</th>
                                        </tr>
                                    </thead>
                                    <tbody id="promotionCountsBody">
                                        <?php if (isset($data['promotion_counts']) && is_array($data['promotion_counts']) && !empty($data['promotion_counts'])): ?>
                                            <?php foreach ($data['promotion_counts'] as $promotion): ?>
                                                <tr>
                                                    <td><?php echo isset($promotion['current_level']) ? $promotion['current_level'] : ''; ?></td>
                                                    <td class="text-center"><?php echo isset($promotion['student_count']) ? $promotion['student_count'] : 0; ?> คน</td>
                                                    <td class="<?php echo (isset($promotion['new_level']) && $promotion['new_level'] === 'สำเร็จการศึกษา') ? 'text-success' : ''; ?>">
                                                        <?php echo isset($promotion['new_level']) ? $promotion['new_level'] : ''; ?>
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
                                                if (isset($data['promotion_counts']) && is_array($data['promotion_counts']) && !empty($data['promotion_counts'])) {
                                                    foreach ($data['promotion_counts'] as $promotion) {
                                                        $total += isset($promotion['student_count']) ? (int)$promotion['student_count'] : 0;
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
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button id="promoteBtn" type="button" class="btn btn-warning" onclick="confirmPromoteStudents()">
                <span class="material-icons">upgrade</span>
                ดำเนินการเลื่อนชั้น
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">ยืนยันการลบ</h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert alert-danger">
                <span class="material-icons">warning</span>
                <div id="deleteWarningMessage">คุณต้องการลบรายการนี้ใช่หรือไม่?</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button id="confirmDeleteButton" type="button" class="btn btn-danger">
                <span class="material-icons">delete</span>
                ยืนยันการลบ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันทั่วไป -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">ยืนยันการดำเนินการ</h2>
            <button class="modal-close" data-dismiss="modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div id="confirmContent">
                คุณต้องการดำเนินการนี้ใช่หรือไม่?
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
            <button id="confirmButton" type="button" class="btn btn-primary">
                <span class="material-icons">check</span>
                ยืนยัน
            </button>
        </div>
    </div>
</div>

<script>
// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing DataTables');
    
    try {
        // เริ่มต้น DataTables สำหรับตารางชั้นเรียน
        const classTable = $('#classTable').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json',
            },
            columnDefs: [
                { responsivePriority: 1, targets: [1, 6] }, // ลำดับความสำคัญของคอลัมน์เมื่อหน้าจอเล็ก
                { orderable: false, targets: [6] } // คอลัมน์ที่ไม่ต้องการให้เรียงลำดับ (การจัดการ)
            ],
            order: [[1, 'asc']], // เรียงลำดับตามชั้นเรียน
            pageLength: 10, // จำนวนแถวต่อหน้า
            initComplete: function() {
                console.log('Class DataTable initComplete');
                // ตรวจสอบและเพิ่มปุ่มจัดการครูที่ปรึกษาหลังจาก DataTable ถูกสร้าง
                setTimeout(checkAndAddAdvisorManagementButtons, 500);
            }
        });
        
        // เริ่มต้น DataTables สำหรับตารางแผนกวิชา
        const deptTable = $('#departmentTable').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json',
            },
            columnDefs: [
                { responsivePriority: 1, targets: [1, 4] },
                { orderable: false, targets: [4] }
            ],
            order: [[1, 'asc']], // เรียงลำดับตามชื่อแผนกวิชา
            pageLength: 10,
            initComplete: function() {
                console.log('Department DataTable initComplete');
            }
        });
        
        console.log('DataTables initialized successfully');
    } catch (error) {
        console.error('Error initializing DataTables:', error);
    }
    
    // เริ่มต้น DataTables สำหรับตารางนักเรียนในโมดัลรายละเอียดชั้นเรียน
    $('#classDetailsModal').on('shown.bs.modal', function () {
        if (document.getElementById('studentTable')) {
            $('#studentTable').DataTable({
                responsive: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json',
                },
                destroy: true, // ทำลายตารางเดิมก่อนสร้างใหม่
                pageLength: 10,
            });
        }
    });
    
    // ตั้งค่า event เมื่อกดปุ่มกรองข้อมูล
    $('#filterClassesBtn').click(function() {
        filterClasses();
    });
    
    // ตั้งค่าตัวกรองตามคำค้นหา
    $('#classSearchInput').on('keyup', function() {
        filterClassesBySearch(this.value);
    });
    
    // ตั้งค่าค่าเริ่มต้นสำหรับโมดัลเลื่อนชั้น
    $('#toAcademicYear').on('change', function() {
        const selectedValue = $(this).val();
        if (selectedValue === 'new') {
            $('#newAcademicYearForm').show();
        } else {
            $('#newAcademicYearForm').hide();
        }
    });
});

// ฟังก์ชันเพิ่มปุ่มจัดการครูที่ปรึกษาในตาราง
function checkAndAddAdvisorManagementButtons() {
    // ตรวจสอบว่า DataTable ถูกสร้างเรียบร้อยแล้ว
    if (!document.querySelector('.action-buttons')) {
        console.log('Waiting for action buttons to be ready...');
        setTimeout(checkAndAddAdvisorManagementButtons, 500);
        return;
    }
    
    console.log('Adding advisor management buttons...');
    // ตรวจสอบและเพิ่มปุ่มจัดการครูที่ปรึกษาหากยังไม่มี
    document.querySelectorAll('.action-buttons').forEach(function(actionDiv) {
        if (!actionDiv.querySelector('button[title="จัดการครูที่ปรึกษา"]')) {
            const detailsButton = actionDiv.querySelector('button[onclick^="showClassDetails"]');
            if (detailsButton) {
                const classIdMatch = detailsButton.getAttribute('onclick').match(/\d+/);
                
                if (classIdMatch) {
                    const classId = classIdMatch[0];
                    const advisorBtn = document.createElement('button');
                    advisorBtn.className = 'table-action-btn success';
                    advisorBtn.setAttribute('onclick', `manageAdvisors(${classId})`);
                    advisorBtn.setAttribute('title', 'จัดการครูที่ปรึกษา');
                    advisorBtn.innerHTML = '<span class="material-icons">people</span>';
                    
                    // แทรกปุ่มหลังปุ่มดูรายละเอียด
                    actionDiv.insertBefore(advisorBtn, detailsButton.nextSibling);
                }
            }
        }
    });
}

// ฟังก์ชันกรองข้อมูลชั้นเรียน
function filterClasses() {
    const academicYear = $('#academicYearFilter').val();
    const level = $('#levelFilter').val();
    const department = $('#departmentFilter').val();
    
    let table = $('#classTable').DataTable();
    
    // รีเซ็ตการกรอง
    table.search('').columns().search('').draw();
    
    // ใช้ API ของ DataTables ในการกรอง
    if (academicYear) {
        table.column(0).search(academicYear, true, false);
    }
    if (level) {
        table.column(1).search(level, true, false);
    }
    if (department) {
        table.column(1).search(department, true, false);
    }
    
    table.draw();
    
    // แสดงการแจ้งเตือน
    showNotification(`กรองข้อมูลสำเร็จ แสดง ${table.page.info().recordsDisplay} รายการ`, 'info');
}

// ฟังก์ชันกรองตามคำค้นหา
function filterClassesBySearch(searchText) {
    let table = $('#classTable').DataTable();
    table.search(searchText).draw();
}

// ปรับปรุงฟังก์ชัน populateAdvisorsList ให้แสดงผลดีขึ้น
function populateAdvisorsList(advisors) {
    const advisorsList = document.getElementById('advisorsList');
    if (!advisorsList) return;
    
    advisorsList.innerHTML = '';
    
    if (!advisors || advisors.length === 0) {
        advisorsList.innerHTML = `
            <div class="text-center py-3 no-advisor-message">
                <span class="material-icons">person_off</span>
                <p class="text-muted mt-2">ยังไม่มีครูที่ปรึกษา</p>
                <button class="btn btn-primary btn-sm mt-2" onclick="manageAdvisorsFromDetails()">
                    <span class="material-icons">add</span> เพิ่มครูที่ปรึกษา
                </button>
            </div>
        `;
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        
        // ใช้ตัวอักษรแรกของชื่อเป็นอักษรย่อ
        const initial = advisor.name ? advisor.name.charAt(0) : 'A';
        
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${initial}</div>
            <div class="advisor-info">
                <div>${advisor.name || 'ไม่ระบุชื่อ'} ${advisor.is_primary ? '<span class="badge badge-primary">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position || 'ครูผู้สอน'}</div>
            </div>
        `;
        advisorsList.appendChild(advisorEl);
    });
}

// ปรับปรุงฟังก์ชัน renderCurrentAdvisors ให้แสดงผลสวยงามขึ้น
function renderCurrentAdvisors(advisors) {
    const currentAdvisorsList = document.getElementById('currentAdvisorsList');
    if (!currentAdvisorsList) {
        console.error('Element #currentAdvisorsList not found');
        return;
    }
    
    currentAdvisorsList.innerHTML = '';
    
    if (!advisors || advisors.length === 0) {
        currentAdvisorsList.innerHTML = `
            <div class="text-center py-3 no-advisor-message">
                <span class="material-icons">person_off</span>
                <p class="text-muted mt-2">ยังไม่มีครูที่ปรึกษา</p>
                <p class="text-muted small">กรุณาเลือกครูที่ปรึกษาจากรายการด้านขวา</p>
            </div>
        `;
        return;
    }
    
    advisors.forEach(advisor => {
        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
            <div class="advisor-avatar">${advisor.name.charAt(0)}</div>
            <div class="advisor-info">
                <div>${advisor.name} ${advisor.is_primary ? '<span class="badge badge-primary">หลัก</span>' : ''}</div>
                <div class="advisor-position">${advisor.position || 'ครูผู้สอน'}</div>
            </div>
            <div class="advisor-action">
                ${!advisor.is_primary ? `
                <button class="table-action-btn primary" onclick="setAsPrimaryAdvisor(${advisor.id})">
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
</script>