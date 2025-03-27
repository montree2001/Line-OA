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
                                                            echo $class['attendance_rate'] > 90 ? 'good' : ($class['attendance_rate'] > 75 ? 'warning' : 'danger');
                                                            ?>">
                                <?php echo number_format($class['attendance_rate'], 1); ?>%
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php
                                                        echo $class['attendance_rate'] > 90 ? 'success' : ($class['attendance_rate'] > 75 ? 'warning' : 'danger');
                                                        ?>">
                                <?php
                                echo $class['attendance_rate'] > 90 ? 'ปกติ' : ($class['attendance_rate'] > 75 ? 'ต้องระวัง' : 'เสี่ยง');
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
        <input type="text" 
               id="departmentName" 
               class="form-control" 
               name="department_name" 
               placeholder="กรอกชื่อแผนกวิชา" 
               required>
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
                    <div class="text-muted">กำลังโหลดข้อมูล...</div>
                </div>
            </div>

            <div class="add-advisor">
                <h3>เพิ่มครูที่ปรึกษา</h3>
                <div class="form-group">
                    <label>เลือกครู</label>
                    <select id="advisorSelect" class="form-control">
                        <option value="">-- เลือกครูที่ปรึกษา --</option>
                        <!-- รายการครู จะถูกเติมด้วย PHP -->
                    </select>
                </div>
                <div class="form-check">
                    <input type="checkbox" id="isPrimaryAdvisor" class="form-check-input">
                    <label for="isPrimaryAdvisor" class="form-check-label">ครูที่ปรึกษาหลัก</label>
                </div>
                <div class="form-helper-text">
                    <small class="text-muted">* ครูที่ปรึกษาหลักจะมีเพียงคนเดียวต่อชั้นเรียน</small>
                </div>
                <button class="btn btn-primary mt-2" onclick="addAdvisor()">
                    <span class="material-icons">add</span>
                    เพิ่มครูที่ปรึกษา
                </button>
            </div>
        </div>

        <div class="advisor-changes-summary">
            <h3>สรุปการเปลี่ยนแปลง</h3>
            <div id="changesLog" class="changes-log">
                <div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="cancelAdvisorChanges()">ยกเลิก</button>
            <button class="btn btn-primary" onclick="saveAdvisorsChanges()">
                <span class="material-icons">save</span>
                บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </div>
</div>

<!-- โมดัลเลื่อนชั้นนักเรียน -->
<div class="modal large-modal" id="promoteStudentsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('promoteStudentsModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">
            <span class="material-icons">upgrade</span>
            เลื่อนชั้นนักเรียน
        </h2>

        <div class="promotion-info">
            <div class="alert alert-info">
                <span class="material-icons">info</span>
                <div class="alert-content">
                    <p><strong>การเลื่อนชั้นนักเรียนจะดำเนินการดังนี้:</strong></p>
                    <p>- นักเรียนชั้น ปวช.1 จะเลื่อนขึ้นไป ปวช.2</p>
                    <p>- นักเรียนชั้น ปวช.2 จะเลื่อนขึ้นไป ปวช.3</p>
                    <p>- นักเรียนชั้น ปวส.1 จะเลื่อนขึ้นไป ปวส.2</p>
                    <p>- นักเรียนชั้น ปวช.3 และ ปวส.2 จะถูกตั้งค่าเป็น "สำเร็จการศึกษา"</p>
                </div>
            </div>

            <div class="form-section">
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
                    <small class="helper-text">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนออก</small>
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
                    <small class="helper-text">ปีการศึกษาที่จะเลื่อนชั้นนักเรียนเข้า</small>
                </div>

                <div class="form-group">
                    <label for="promotionNotes">หมายเหตุการเลื่อนชั้น</label>
                    <textarea id="promotionNotes" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="promotion-summary">
                <h3>สรุปจำนวนนักเรียนที่จะเลื่อนชั้น</h3>

                <div class="promotion-stats">
                    <div class="promotion-chart">
                        <!-- กราฟแสดงจำนวนนักเรียนแต่ละระดับชั้น -->
                        <div id="promotionChart" class="chart-container">
                            <!-- จะถูกเติมด้วย JS -->
                        </div>
                    </div>

                    <div class="promotion-table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ระดับชั้นปัจจุบัน</th>
                                    <th>จำนวนนักเรียน</th>
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

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('promoteStudentsModal')">ยกเลิก</button>
            <button id="promoteBtn" class="btn btn-warning" onclick="confirmPromoteStudents()">
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
    .large-modal .modal-content {
        width: 80%;
        max-width: 1000px;
    }

    .promotion-info {
        margin-bottom: 20px;
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
        border-left: 4px solid #1976d2;
    }

    .alert .material-icons {
        margin-right: 15px;
        margin-top: 2px;
    }

    .alert-content {
        flex: 1;
    }

    .alert-content p {
        margin: 5px 0;
    }

    .form-section {
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .helper-text {
        color: #666;
    }

    .promotion-summary {
        background-color: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .promotion-summary h3 {
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }

    .promotion-stats {
        display: flex;
        gap: 20px;
    }

    .promotion-chart {
        flex: 1;
        min-height: 200px;
    }

    .promotion-table {
        flex: 1;
    }

    .chart-container {
        height: 250px;
        background-color: #f9f9f9;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
    }

    .text-success {
        color: #4caf50;
    }

    @media (max-width: 768px) {
        .promotion-stats {
            flex-direction: column;
        }

        .large-modal .modal-content {
            width: 95%;
        }
    }

    .advisors-management {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .current-advisors,
    .add-advisor {
        flex: 1;
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .advisor-items {
        display: flex;
        flex-direction: column;
        gap: 10px;
        max-height: 300px;
        overflow-y: auto;
        padding-right: 5px;
    }

    .advisor-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .advisor-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .advisor-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--secondary-color-light);
        color: var(--secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
        font-weight: bold;
        font-size: 16px;
    }

    .advisor-info {
        flex: 1;
    }

    .advisor-action {
        display: flex;
        gap: 5px;
    }

    .primary-badge {
        background-color: #4caf50;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 10px;
    }

    .advisor-changes-summary {
        background-color: #f5f5f5;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        max-height: 200px;
        overflow-y: auto;
    }

    .changes-log {
        font-size: 14px;
    }

    .change-item {
        padding: 8px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .change-item:last-child {
        border-bottom: none;
    }

    .change-add {
        color: #4caf50;
    }

    .change-remove {
        color: #f44336;
    }

    .change-primary {
        color: #2196f3;
    }

    .form-helper-text {
        margin-top: 5px;
        font-size: 12px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .advisors-management {
            flex-direction: column;
        }
    }

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

    function editDepartment(departmentCode) {
    // หาแผนกวิชาจากตาราง
    let departmentName = '';
    const departmentRows = document.querySelectorAll('#departmentTableBody tr');
    for (const row of departmentRows) {
        if (row.cells[0].textContent === departmentCode) {
            departmentName = row.cells[1].textContent;
            break;
        }
    }
    
    // ตรวจสอบว่าพบข้อมูลหรือไม่
    const departmentNameInput = document.getElementById('departmentName');
    const departmentIdInput = document.getElementById('departmentId');
    
    if (departmentNameInput && departmentIdInput) {
        document.getElementById('departmentModalTitle').textContent = 'แก้ไขแผนกวิชา';
        departmentNameInput.value = departmentName;
        departmentIdInput.value = departmentCode;
        showModal('departmentModal');
    } else {
        console.error('ไม่พบ element สำหรับแก้ไขแผนกวิชา');
        showNotification('เกิดข้อผิดพลาดในการแก้ไขข้อมูล', 'error');
    }
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
    function renderCurrentAdvisors(advisors) {
        const currentAdvisorsList = document.getElementById('currentAdvisorsList');
        currentAdvisorsList.innerHTML = '';

        if (!advisors || advisors.length === 0) {
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
                <div class="advisor-position">${advisor.position || 'ครู'}</div>
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

    // อัพเดทการแสดงการเปลี่ยนแปลง
    function updateChangesLog() {
        const changesLog = document.getElementById('changesLog');

        if (!advisorsChanges || advisorsChanges.length === 0) {
            changesLog.innerHTML = '<div class="text-muted">ยังไม่มีการเปลี่ยนแปลง</div>';
            return;
        }

        changesLog.innerHTML = '';
        advisorsChanges.forEach((change, index) => {
            const changeItem = document.createElement('div');
            changeItem.className = 'change-item';

            let iconClass = '';
            let changeText = '';

            // หาชื่อครูจาก select
            let teacherName = 'ครูรหัส ' + change.teacher_id;
            const teacherOption = document.querySelector(`#advisorSelect option[value="${change.teacher_id}"]`);
            if (teacherOption) {
                teacherName = teacherOption.textContent;
            }

            switch (change.action) {
                case 'add':
                    iconClass = 'change-add';
                    changeText = `<span class="material-icons">person_add</span> เพิ่ม ${teacherName} ${change.is_primary ? '(ที่ปรึกษาหลัก)' : ''}`;
                    break;
                case 'remove':
                    iconClass = 'change-remove';
                    changeText = `<span class="material-icons">person_remove</span> ลบ ${teacherName}`;
                    break;
                case 'set_primary':
                    iconClass = 'change-primary';
                    changeText = `<span class="material-icons">stars</span> ตั้ง ${teacherName} เป็นที่ปรึกษาหลัก`;
                    break;
            }

            changeItem.innerHTML = `
            <div class="${iconClass}">
                ${changeText}
                <button class="btn-icon" onclick="removeChange(${index})">
                    <span class="material-icons">cancel</span>
                </button>
            </div>
        `;

            changesLog.appendChild(changeItem);
        });
    }

    // ลบการเปลี่ยนแปลง
    function removeChange(index) {
        if (index >= 0 && index < advisorsChanges.length) {
            advisorsChanges.splice(index, 1);
            updateChangesLog();
        }
    }

    // ยกเลิกการเปลี่ยนแปลงทั้งหมด
    function cancelAdvisorChanges() {
        if (advisorsChanges.length > 0) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกการเปลี่ยนแปลงทั้งหมด?')) {
                advisorsChanges = [];
                closeModal('advisorsModal');
            }
        } else {
            closeModal('advisorsModal');
        }
    }

    // อัพเดทฟังก์ชันเพิ่มครูที่ปรึกษา
    function addAdvisor() {
        const advisorId = document.getElementById('advisorSelect').value;
        const isPrimary = document.getElementById('isPrimaryAdvisor').checked;

        if (!advisorId) {
            showNotification('กรุณาเลือกครูที่ปรึกษา', 'warning');
            return;
        }

        // ตรวจสอบว่าเลือกซ้ำหรือไม่
        if (advisorsChanges.some(change => change.action === 'add' && change.teacher_id == advisorId)) {
            showNotification('ครูที่ปรึกษาท่านนี้มีอยู่ในรายการเพิ่มแล้ว', 'warning');
            return;
        }

        // ตรวจสอบว่ามีอยู่ในรายการปัจจุบันแล้วหรือไม่
        const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
        let isDuplicate = false;

        currentAdvisorItems.forEach(item => {
            const actionButton = item.querySelector('.advisor-action button:last-child');
            if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
                isDuplicate = true;
            }
        });

        if (isDuplicate) {
            showNotification('ครูที่ปรึกษาท่านนี้เป็นที่ปรึกษาของชั้นเรียนนี้อยู่แล้ว', 'warning');
            return;
        }

        // บันทึกการเปลี่ยนแปลง
        advisorsChanges.push({
            action: 'add',
            teacher_id: advisorId,
            is_primary: isPrimary
        });

        // อัพเดทการแสดงการเปลี่ยนแปลง
        updateChangesLog();

        // จำลองการแสดงผล (ในตัวอย่างจริง ควรจะดึงข้อมูลครูจาก API)
        const advisorName = document.querySelector(`#advisorSelect option[value="${advisorId}"]`).textContent;

        // เพิ่มรายการใหม่ลงในรายการครูที่ปรึกษาปัจจุบัน
        const currentAdvisorsList = document.getElementById('currentAdvisorsList');
        const noAdvisorMessage = currentAdvisorsList.querySelector('.text-muted');
        if (noAdvisorMessage) {
            currentAdvisorsList.innerHTML = '';
        }

        const advisorEl = document.createElement('div');
        advisorEl.className = 'advisor-item';
        advisorEl.innerHTML = `
        <div class="advisor-avatar">${advisorName.charAt(0)}</div>
        <div class="advisor-info">
            <div>${advisorName} ${isPrimary ? '<span class="primary-badge">หลัก</span>' : ''}</div>
            <div class="advisor-position">เพิ่มใหม่</div>
        </div>
        <div class="advisor-action">
            ${!isPrimary ? `
            <button class="table-action-btn success" onclick="setAsPrimaryAdvisor(${advisorId})">
                <span class="material-icons">stars</span>
            </button>` : ''}
            <button class="table-action-btn danger" onclick="removeNewAdvisor(this, ${advisorId})">
                <span class="material-icons">delete</span>
            </button>
        </div>
    `;
        currentAdvisorsList.appendChild(advisorEl);

        // รีเซ็ตฟอร์ม
        document.getElementById('advisorSelect').value = '';
        document.getElementById('isPrimaryAdvisor').checked = false;

        showNotification('เพิ่มครูที่ปรึกษาใหม่ในรายการแล้ว', 'success');
    }

    // อัพเดทฟังก์ชันตั้งเป็นครูที่ปรึกษาหลัก
    function setAsPrimaryAdvisor(advisorId) {
        // ตรวจสอบว่ามีครูที่ถูกเพิ่มใหม่และตั้งเป็นที่ปรึกษาหลักหรือไม่
        const hasPrimaryInChanges = advisorsChanges.some(change =>
            change.action === 'add' && change.is_primary
        );

        // หากมีการตั้งครูที่ปรึกษาหลักไปแล้วในการเปลี่ยนแปลง ให้ยกเลิก
        if (hasPrimaryInChanges) {
            advisorsChanges = advisorsChanges.map(change => {
                if (change.action === 'add') {
                    return {
                        ...change,
                        is_primary: false
                    };
                }
                return change;
            });
        }

        // บันทึกการเปลี่ยนแปลง
        advisorsChanges.push({
            action: 'set_primary',
            teacher_id: advisorId
        });

        // อัพเดทการแสดงการเปลี่ยนแปลง
        updateChangesLog();

        // อัพเดท UI
        // ล้างครูที่ปรึกษาหลักเดิม
        const primaryBadges = document.querySelectorAll('#currentAdvisorsList .primary-badge');
        primaryBadges.forEach(badge => {
            badge.remove();
        });

        // แสดงปุ่มตั้งเป็นที่ปรึกษาหลักทั้งหมด
        const setPrimaryButtons = document.querySelectorAll('#currentAdvisorsList .table-action-btn.success');
        setPrimaryButtons.forEach(button => {
            button.style.display = '';
        });

        // ตั้งครูคนนี้เป็นที่ปรึกษาหลัก
        const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
        currentAdvisorItems.forEach(item => {
            const actionButton = item.querySelector('.advisor-action button:last-child');
            if (actionButton && (actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`) ||
                    actionButton.getAttribute('onclick').includes(`removeNewAdvisor(this, ${advisorId})`))) {
                const nameElement = item.querySelector('.advisor-info div:first-child');
                nameElement.innerHTML = nameElement.textContent + ' <span class="primary-badge">หลัก</span>';

                // ซ่อนปุ่มตั้งเป็นครูที่ปรึกษาหลัก
                const setPrimaryButton = item.querySelector('.table-action-btn.success');
                if (setPrimaryButton) {
                    setPrimaryButton.style.display = 'none';
                }
            }
        });

        showNotification('ตั้งเป็นครูที่ปรึกษาหลักแล้ว', 'success');
    }

    // อัพเดทฟังก์ชันลบครูที่ปรึกษา
    function removeAdvisor(advisorId) {
        if (confirm(`ต้องการลบครูที่ปรึกษาออกจากชั้นเรียนนี้หรือไม่?`)) {
            // บันทึกการเปลี่ยนแปลง
            advisorsChanges.push({
                action: 'remove',
                teacher_id: advisorId
            });

            // อัพเดทการแสดงการเปลี่ยนแปลง
            updateChangesLog();

            // ลบรายการจาก DOM
            const currentAdvisorItems = document.querySelectorAll('#currentAdvisorsList .advisor-item');
            currentAdvisorItems.forEach(item => {
                const actionButton = item.querySelector('.advisor-action button:last-child');
                if (actionButton && actionButton.getAttribute('onclick').includes(`removeAdvisor(${advisorId})`)) {
                    item.remove();
                }
            });

            // ตรวจสอบว่ายังมีครูที่ปรึกษาในรายการหรือไม่
            const currentAdvisorsList = document.getElementById('currentAdvisorsList');
            if (currentAdvisorsList.children.length === 0) {
                currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
            }

            showNotification('ลบครูที่ปรึกษาออกจากชั้นเรียนแล้ว', 'success');
        }
    }

    // อัพเดทฟังก์ชันลบครูที่ปรึกษาที่เพิ่งเพิ่มใหม่
    function removeNewAdvisor(buttonElement, advisorId) {
        // ลบรายการจาก DOM
        const advisorItem = buttonElement.closest('.advisor-item');
        advisorItem.remove();

        // ลบการเปลี่ยนแปลงจากรายการ
        advisorsChanges = advisorsChanges.filter(change => {
            return !(change.action === 'add' && change.teacher_id == advisorId);
        });

        // อัพเดทการแสดงการเปลี่ยนแปลง
        updateChangesLog();

        // ตรวจสอบว่ายังมีครูที่ปรึกษาในรายการหรือไม่
        const currentAdvisorsList = document.getElementById('currentAdvisorsList');
        if (currentAdvisorsList.children.length === 0) {
            currentAdvisorsList.innerHTML = '<div class="text-muted">ยังไม่มีครูที่ปรึกษา</div>';
        }

        showNotification('ลบครูที่ปรึกษาออกจากรายการแล้ว', 'info');
    }

    // ฟังก์ชันแสดงโมดัลเลื่อนชั้นนักเรียน
    function showPromoteStudentsModal() {
        showModal('promoteStudentsModal');
    }

    // ฟังก์ชันยืนยันการเลื่อนชั้นนักเรียน

    // ฟังก์ชันสำหรับยืนยันการเลื่อนชั้นนักเรียน
    function confirmPromoteStudents() {
        // ตรวจสอบว่ามีปีการศึกษาปลายทางหรือไม่
        const toAcademicYear = document.getElementById('toAcademicYear').value;
        const fromAcademicYear = document.getElementById('fromAcademicYear').value;
        const promotionNotes = document.getElementById('promotionNotes').value;

        if (toAcademicYear === 'new') {
            showModal('newAcademicYearModal');
            return;
        }

        if (!toAcademicYear || !fromAcademicYear) {
            showNotification('กรุณาเลือกปีการศึกษาต้นทางและปลายทาง', 'warning');
            return;
        }

        if (!confirm('คุณแน่ใจหรือไม่ที่จะดำเนินการเลื่อนชั้นนักเรียน? การดำเนินการนี้ไม่สามารถย้อนกลับได้')) {
            return;
        }

        // แสดงสถานะกำลังโหลด
        const promoteBtn = document.getElementById('promoteBtn');
        promoteBtn.disabled = true;
        promoteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังดำเนินการ...';

        // เตรียมข้อมูลสำหรับส่ง
        const formData = new FormData();
        formData.append('action', 'promote_students');
        formData.append('from_academic_year_id', fromAcademicYear);
        formData.append('to_academic_year_id', toAcademicYear);
        formData.append('notes', promotionNotes);

        // ส่งข้อมูลผ่าน AJAX
        ajaxRequest(
            'api/class_manager.php',
            'POST',
            formData,
            function(data) {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    closeModal('promoteStudentsModal');

                    // รอสักครู่แล้วโหลดหน้าใหม่
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message || 'เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ', 'error');
                    promoteBtn.disabled = false;
                    promoteBtn.innerHTML = '<span class="material-icons">upgrade</span> ดำเนินการเลื่อนชั้น';
                }
            },
            function(error) {
                console.error('Error:', error);
                showNotification('เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน: ' + error, 'error');
                promoteBtn.disabled = false;
                promoteBtn.innerHTML = '<span class="material-icons">upgrade</span> ดำเนินการเลื่อนชั้น';
            }
        );
    }

    // ฟังก์ชันสร้างกราฟแสดงจำนวนนักเรียนที่จะเลื่อนชั้น
    function renderPromotionChart() {
        const chartContainer = document.getElementById('promotionChart');

        // ดึงข้อมูลจากตาราง
        const dataRows = document.querySelectorAll('#promotionCountsBody tr');
        if (dataRows.length === 0) {
            chartContainer.innerHTML = '<div class="text-muted">ไม่มีข้อมูลสำหรับแสดงกราฟ</div>';
            return;
        }

        // สร้างข้อมูลสำหรับกราฟ
        const chartData = [];
        let totalStudents = 0;

        dataRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 3) {
                const currentLevel = cells[0].textContent.trim();
                const studentCount = parseInt(cells[1].textContent);
                const newLevel = cells[2].textContent.trim();

                if (!isNaN(studentCount)) {
                    chartData.push({
                        level: currentLevel,
                        students: studentCount,
                        newLevel: newLevel
                    });
                    totalStudents += studentCount;
                }
            }
        });

        // สร้างกราฟแท่ง
        let chartHtml = '';

        chartData.forEach(item => {
            const barHeight = (item.students / totalStudents) * 200;
            const percentage = ((item.students / totalStudents) * 100).toFixed(1);

            const barColor = item.newLevel === 'สำเร็จการศึกษา' ?
                '#4caf50' // สีเขียวสำหรับนักเรียนที่จบการศึกษา
                :
                '#2196f3'; // สีฟ้าสำหรับนักเรียนที่เลื่อนชั้น

            chartHtml += `
            <div class="chart-bar-container">
                <div class="chart-bar" style="height: ${barHeight}px; background-color: ${barColor};">
                    <span class="chart-value">${item.students}</span>
                </div>
                <div class="chart-label">${item.level}</div>
                <div class="chart-percentage">${percentage}%</div>
            </div>
        `;
        });

        // เพิ่ม CSS เฉพาะสำหรับกราฟ
        chartHtml = `
        <style>
            .chart-wrapper {
                display: flex;
                height: 220px;
                align-items: flex-end;
                justify-content: space-around;
                margin-top: 20px;
            }
            
            .chart-bar-container {
                display: flex;
                flex-direction: column;
                align-items: center;
                width: 60px;
            }
            
            .chart-bar {
                width: 40px;
                min-height: 30px;
                border-radius: 4px 4px 0 0;
                position: relative;
            }
            
            .chart-value {
                position: absolute;
                top: -20px;
                left: 50%;
                transform: translateX(-50%);
                font-weight: bold;
            }
            
            .chart-label {
                margin-top: 5px;
                font-size: 12px;
                text-align: center;
            }
            
            .chart-percentage {
                font-size: 10px;
                color: #666;
            }
        </style>
        <div class="chart-wrapper">
            ${chartHtml}
        </div>
    `;

        chartContainer.innerHTML = chartHtml;
    }

    // เมื่อเปิดโมดัลเลื่อนชั้น ให้แสดงกราฟ
    document.addEventListener('DOMContentLoaded', function() {
        // เพิ่ม event listener สำหรับการแสดงกราฟเมื่อเปิดโมดัล
        const promoteStudentsModal = document.getElementById('promoteStudentsModal');
        if (promoteStudentsModal) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.attributeName === 'style' &&
                        promoteStudentsModal.style.display === 'flex') {
                        renderPromotionChart();
                    }
                });
            });

            observer.observe(promoteStudentsModal, {
                attributes: true
            });
        }
    });

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
    const departmentForm = document.getElementById('departmentForm');
    if (departmentForm) {
        departmentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const departmentId = document.getElementById('departmentId').value;
            const departmentName = document.getElementById('departmentName').value;
            
            if (!departmentName) {
                showNotification('กรุณาระบุชื่อแผนกวิชา', 'warning');
                return;
            }
            
            // เตรียมข้อมูล
            const formData = new FormData();
            formData.append('form_action', departmentId ? 'edit_department' : 'add_department');
            formData.append('department_id', departmentId);
            formData.append('department_name', departmentName);
            
            // ส่งข้อมูล
            fetch('classes.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('departmentModal');
                    // รีโหลดหน้าหลังจากบันทึกสำเร็จ
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('เกิดข้อผิดพลาดในการส่งข้อมูล', 'error');
            });
        });
    }
});
</script>