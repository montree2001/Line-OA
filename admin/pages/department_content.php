<!-- คอนเทนต์หน้าจัดการแผนกวิชา -->
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
                <?php if(empty($data['departments'])): ?>
                <tr>
                    <td colspan="6" class="text-center">ไม่พบข้อมูลแผนกวิชา</td>
                </tr>
                <?php else: ?>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- โมดัลเพิ่ม/แก้ไขแผนกวิชา -->
<div class="modal" id="departmentModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="departmentModalTitle">เพิ่มแผนกวิชาใหม่</h2>
            <button class="modal-close" onclick="closeModal('departmentModal')">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="departmentForm">
                <input type="hidden" id="departmentId" name="department_id" value="">
                
                <div class="form-group">
                    <label class="form-label">รหัสแผนกวิชา</label>
                    <div class="input-with-hint">
                        <input type="text" id="departmentCode" class="form-control" name="department_code" placeholder="เช่น IT, AUTO, MECH">
                        <small class="form-helper-text">*หากไม่ระบุ ระบบจะสร้างรหัสให้อัตโนมัติ</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">ชื่อแผนกวิชา <span class="required">*</span></label>
                    <input type="text" id="departmentName" class="form-control" name="department_name" placeholder="กรอกชื่อแผนกวิชา" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('departmentModal')">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="saveDepartment()">
                <span class="material-icons">save</span>
                บันทึกแผนกวิชา
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">ยืนยันการลบ</h2>
            <button class="modal-close" onclick="closeModal('confirmDeleteModal')">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="confirm-delete-content">
                <div class="alert alert-danger">
                    <span class="material-icons">warning</span>
                    <div id="deleteWarningMessage">คุณต้องการลบรายการนี้ใช่หรือไม่?</div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">ยกเลิก</button>
            <button class="btn btn-danger" id="confirmDeleteButton">
                <span class="material-icons">delete</span>
                ยืนยันการลบ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลดูรายละเอียดแผนกวิชา -->
<div class="modal" id="departmentDetailsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="departmentDetailsTitle">รายละเอียดแผนกวิชา</h2>
            <button class="modal-close" onclick="closeModal('departmentDetailsModal')">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="department-details-content">
                <div class="row">
                    <div class="col-md-6">
                        <h3>ข้อมูลพื้นฐาน</h3>
                        <table class="details-table">
                            <tr>
                                <th>รหัสแผนก:</th>
                                <td id="detailDepartmentCode"></td>
                            </tr>
                            <tr>
                                <th>ชื่อแผนกวิชา:</th>
                                <td id="detailDepartmentName"></td>
                            </tr>
                            <tr>
                                <th>จำนวนนักเรียน:</th>
                                <td id="detailStudentCount"></td>
                            </tr>
                            <tr>
                                <th>จำนวนชั้นเรียน:</th>
                                <td id="detailClassCount"></td>
                            </tr>
                            <tr>
                                <th>จำนวนครู:</th>
                                <td id="detailTeacherCount"></td>
                            </tr>
                            <tr>
                                <th>สถานะ:</th>
                                <td id="detailStatus"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h3>ชั้นเรียนในแผนกวิชา</h3>
                        <div id="departmentClassesList" class="classes-list scrollable"></div>
                    </div>
                </div>
                
                <h3>ครูในแผนกวิชา</h3>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>รหัสครู</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ตำแหน่ง</th>
                                <th>ชั้นเรียนที่เป็นที่ปรึกษา</th>
                            </tr>
                        </thead>
                        <tbody id="departmentTeachersList">
                            <!-- จะถูกเติมข้อมูลด้วย JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('departmentDetailsModal')">ปิด</button>
            <button class="btn btn-primary" onclick="editDepartment(currentDepartmentId)">
                <span class="material-icons">edit</span>
                แก้ไขข้อมูล
            </button>
        </div>
    </div>
</div>