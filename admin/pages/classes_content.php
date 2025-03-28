<!-- คอนเทนต์หน้าจัดการชั้นเรียนและแผนกวิชา -->

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

<!-- โมดัลเพิ่มชั้นเรียน -->
<div class="modal" id="classModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="classModalTitle">เพิ่มชั้นเรียนใหม่</h2>
            <button class="modal-close" onclick="closeModal('classModal')">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <form id="classForm">
                <input type="hidden" id="classId" name="class_id" value="">

                <div class="form-group">
                    <label class="form-label">ปีการศึกษา <span class="required">*</span></label>
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
                    <label class="form-label">ระดับชั้น <span class="required">*</span></label>
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
                    <label class="form-label">แผนกวิชา <span class="required">*</span></label>
                    <select id="classDepartment" class="form-control" name="department_id" required>
                        <option value="">เลือกแผนกวิชา</option>
                        <?php foreach ($data['departments'] as $dept_key => $dept): ?>
                            <option value="<?php echo $dept_key; ?>"><?php echo $dept['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">กลุ่ม <span class="required">*</span></label>
                    <input type="number" id="groupNumber" class="form-control" name="group_number" min="1" max="20" value="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ห้องเรียนประจำ</label>
                    <input type="text" id="classroom" class="form-control" name="classroom" placeholder="เช่น 501, IT-Lab1">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('classModal')">ยกเลิก</button>
            <button type="button" class="btn btn-primary" onclick="saveClass()">
                <span class="material-icons">save</span>
                บันทึกชั้นเรียน
            </button>
        </div>
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
<div class="modal large-modal" id="advisorsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">จัดการครูที่ปรึกษา <span id="advisorsClassTitle"></span></h2>
            <button class="modal-close" onclick="closeModal('advisorsModal')">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="advisors-management">
                <div class="current-advisors">
                    <h3>ครูที่ปรึกษาปัจจุบัน</h3>
                    <div id="currentAdvisorsList" class="advisor-items scrollable">
                        <!-- รายการครูที่ปรึกษาปัจจุบัน จะถูกเติมด้วย JavaScript -->
                    </div>
                </div>

                <div class="add-advisor">
                    <h3>เพิ่มครูที่ปรึกษา</h3>
                    <div class="form-search-box">
                        <input type="text" id="teacherSearch" class="form-control" placeholder="ค้นหาครู...">
                        <span class="material-icons search-icon">search</span>
                    </div>
                    <div class="form-group">
                        <select id="advisorSelect" class="form-control" size="8">
                            <option value="">-- เลือกครูที่ปรึกษา --</option>
                            <?php foreach ($data['teachers'] as $teacher): ?>
                                <option value="<?php echo $teacher['teacher_id']; ?>">
                                    <?php echo $teacher['title'] . ' ' . $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                                    (<?php echo $teacher['department_name'] ?? 'ไม่ระบุแผนก'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check primary-advisor-check">
                        <input type="checkbox" id="isPrimaryAdvisor" class="form-check-input">
                        <label for="isPrimaryAdvisor" class="form-check-label">ตั้งเป็นครูที่ปรึกษาหลัก</label>
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
        </div>
        <div class="modal-footer">
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
    /* ปรับปรุงการแสดงผลรายการครูที่ปรึกษา */
.advisor-item {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.advisor-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.advisor-item.primary {
    border-left-color: var(--primary-color);
}

.advisor-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--primary-light);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-weight: 600;
    font-size: 16px;
}

.advisor-info {
    flex: 1;
}

.advisor-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.advisor-position {
    font-size: 12px;
    color: var(--text-muted);
}

.advisor-action {
    display: flex;
    gap: 5px;
}

.badge-primary {
    display: inline-flex;
    align-items: center;
    background-color: var(--primary-color);
    color: white;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.badge-primary .material-icons {
    font-size: 12px;
    margin-right: 2px;
}

/* ปรับปรุงการแสดงผลตารางชั้นเรียน */
.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 20px;
}

.data-table th,
.data-table td {
    padding: 12px 15px;
    vertical-align: middle;
}

.data-table thead th {
    background-color: var(--light-color);
    color: var(--secondary-color);
    font-weight: 600;
    border-bottom: 2px solid var(--border-color);
    position: sticky;
    top: 0;
    z-index: 10;
}

.data-table tbody tr {
    transition: all 0.2s;
    border-bottom: 1px solid var(--border-color);
}

.data-table tbody tr:hover {
    background-color: rgba(74, 108, 247, 0.05);
}

.class-info {
    display: flex;
    align-items: center;
}

.class-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    font-weight: 600;
    font-size: 16px;
}

.class-details {
    flex: 1;
}

.class-name {
    font-weight: 500;
    margin-bottom: 2px;
}

.class-dept {
    font-size: 12px;
    color: var(--text-muted);
}

/* ปรับปรุงโมดัล */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    overflow-y: auto;
    padding: 20px;
}

.modal-content {
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: modalFadeIn 0.3s;
}

.large-modal .modal-content {
    max-width: 900px;
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.modal-title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
}

.modal-body {
    padding: 20px;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 5px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.modal-close:hover {
    background-color: rgba(0, 0, 0, 0.05);
    color: var(--text-color);
}

@keyframes modalFadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* ปรับปรุงแถบกรองข้อมูล */
.filter-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.filter-label {
    font-weight: 500;
    margin-bottom: 5px;
    font-size: 0.9rem;
}

.filter-button {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    height: 38px;
    margin-top: 22px;
}

.filter-button:hover {
    background-color: #3a5de5;
}

.filter-button .material-icons {
    margin-right: 5px;
    font-size: 18px;
}

/* ตัวบ่งชี้การโหลดข้อมูล */
.loader {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* การแจ้งเตือน */
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notification {
    display: flex;
    align-items: flex-start;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 15px;
    min-width: 300px;
    max-width: 400px;
    animation: slideIn 0.3s forwards;
    border-left: 4px solid var(--primary-color);
}

.notification-icon {
    margin-right: 12px;
    margin-top: 2px;
}

.notification-message {
    flex: 1;
    line-height: 1.4;
}

.notification-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 3px;
    margin-left: 10px;
}

.notification.success {
    border-left-color: var(--success-color);
}

.notification.success .notification-icon {
    color: var(--success-color);
}

.notification.warning {
    border-left-color: var(--warning-color);
}

.notification.warning .notification-icon {
    color: var(--warning-color);
}

.notification.error {
    border-left-color: var(--danger-color);
}

.notification.error .notification-icon {
    color: var(--danger-color);
}

.notification.notification-closing {
    animation: slideOut 0.3s forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* ส่วนเพิ่มเติมเฉพาะหน้า */
.scrollable {
    max-height: 400px;
    overflow-y: auto;
    padding-right: 5px;
}

.scrollable::-webkit-scrollbar {
    width: 6px;
}

.scrollable::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.scrollable::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.scrollable::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

.form-search-box {
    position: relative;
    margin-bottom: 10px;
}

.form-search-box .search-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
}

.required {
    color: var(--danger-color);
}

.input-with-hint {
    position: relative;
}

.form-helper-text {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 5px;
}

.spinning {
    animation: spin 1s linear infinite;
}

/* ส่วนสำหรับการเลื่อนชั้น */
.promotion-confirm {
    background-color: var(--light-color);
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.promotion-summary {
    margin-top: 20px;
}

.promotion-chart {
    background-color: var(--light-color);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
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
   // ฟังก์ชันยืนยันการเลื่อนชั้นนักเรียน
function confirmPromoteStudents() {
    // ตรวจสอบว่ามีปีการศึกษาปลายทางหรือไม่
    const toAcademicYear = document.getElementById('toAcademicYear').value;
    const fromAcademicYear = document.getElementById('fromAcademicYear').value;
    const promotionNotes = document.getElementById('promotionNotes').value;
    
    if (toAcademicYear === 'new') {
        showNotification('กรุณาเพิ่มปีการศึกษาใหม่ก่อนทำการเลื่อนชั้น', 'warning');
        return;
    }
    
    if (!toAcademicYear || !fromAcademicYear) {
        showNotification('กรุณาเลือกปีการศึกษาต้นทางและปลายทาง', 'warning');
        return;
    }
    
    // แสดงข้อความยืนยันพร้อมรายละเอียด
    const confirmMessage = `
        <div class="promotion-confirm">
            <p><strong>กรุณายืนยันการเลื่อนชั้นนักเรียน</strong></p>
            <p>ปีการศึกษาต้นทาง: <strong>${document.querySelector(`#fromAcademicYear option[value="${fromAcademicYear}"]`).textContent}</strong></p>
            <p>ปีการศึกษาปลายทาง: <strong>${document.querySelector(`#toAcademicYear option[value="${toAcademicYear}"]`).textContent}</strong></p>
            <p class="text-danger">คำเตือน: การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
        </div>
    `;
    
    document.getElementById('confirmContent').innerHTML = confirmMessage;
    document.getElementById('confirmButton').onclick = function() {
        executePromoteStudents(fromAcademicYear, toAcademicYear, promotionNotes);
    };
    
    showModal('confirmModal');
}

// ฟังก์ชันดำเนินการเลื่อนชั้นนักเรียน
function executePromoteStudents(fromAcademicYear, toAcademicYear, promotionNotes) {
    // แสดงสถานะกำลังโหลด
    const promoteBtn = document.getElementById('promoteBtn');
    const originalBtnHtml = promoteBtn.innerHTML;
    promoteBtn.disabled = true;
    promoteBtn.innerHTML = '<span class="material-icons spinning">sync</span> กำลังดำเนินการ...';
    
    // เตรียมข้อมูลสำหรับส่ง
    const formData = new FormData();
    formData.append('action', 'promote_students');
    formData.append('from_academic_year_id', fromAcademicYear);
    formData.append('to_academic_year_id', toAcademicYear);
    formData.append('notes', promotionNotes);
    
    // ส่งข้อมูลไปยังเซิร์ฟเวอร์
    fetch('api/class_manager.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            closeModal('confirmModal');
            closeModal('promoteStudentsModal');
            showNotification(`<div>เลื่อนชั้นนักเรียนสำเร็จ<br>- นักเรียนเลื่อนชั้น: ${data.promoted_count} คน<br>- นักเรียนสำเร็จการศึกษา: ${data.graduated_count} คน</div>`, 'success');
            
            // รีโหลดหน้าหลังจากบันทึกสำเร็จ
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showNotification(data.message, 'error');
            promoteBtn.disabled = false;
            promoteBtn.innerHTML = originalBtnHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('เกิดข้อผิดพลาดในการเลื่อนชั้นนักเรียน', 'error');
        promoteBtn.disabled = false;
        promoteBtn.innerHTML = originalBtnHtml;
    });
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



    function showNotification(message, type = 'info') {
    // สร้าง container ถ้ายังไม่มี
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        document.body.appendChild(notificationContainer);
    }
    
    // สร้างการแจ้งเตือน
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    
    // เลือกไอคอนตามประเภท
    let icon = 'info';
    switch (type) {
        case 'success': icon = 'check_circle'; break;
        case 'warning': icon = 'warning'; break;
        case 'error': icon = 'error'; break;
    }
    
    notification.innerHTML = `
        <span class="material-icons notification-icon">${icon}</span>
        <div class="notification-message">${message}</div>
        <button class="notification-close"><span class="material-icons">close</span></button>
    `;
    
    // เพิ่มลงใน container
    notificationContainer.appendChild(notification);
    
    // ตั้งค่าปุ่มปิด
    const closeButton = notification.querySelector('.notification-close');
    closeButton.addEventListener('click', () => {
        notification.classList.add('notification-closing');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    });
    
    // ปิดอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (notification.parentNode) {
            notification.classList.add('notification-closing');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    const teacherSearch = document.getElementById('teacherSearch');
    if (teacherSearch) {
        teacherSearch.addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const options = document.querySelectorAll('#advisorSelect option');
            
            options.forEach(option => {
                if (option.value === '') return; // ข้ามตัวเลือกแรก
                
                const text = option.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
        });
    }
    
    // ผูกเหตุการณ์กับโมดัล
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});


// ปรับปรุงฟังก์ชันค้นหานักเรียน
function filterClasses() {
    const academicYearFilter = document.getElementById('academicYearFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const departmentFilter = document.getElementById('departmentFilter').value;
    
    const classRows = document.querySelectorAll('.class-row');
    let visibleCount = 0;
    
    classRows.forEach(row => {
        const academicYear = row.getAttribute('data-academic-year');
        const level = row.getAttribute('data-level');
        const department = row.getAttribute('data-department');
        
        const academicYearMatch = !academicYearFilter || academicYear === academicYearFilter;
        const levelMatch = !levelFilter || level === levelFilter;
        const departmentMatch = !departmentFilter || department === departmentFilter;
        
        if (academicYearMatch && levelMatch && departmentMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // แสดงข้อความเมื่อไม่พบข้อมูล
    const noDataMessage = document.getElementById('noDataMessage');
    if (noDataMessage) {
        if (visibleCount === 0) {
            noDataMessage.style.display = 'block';
        } else {
            noDataMessage.style.display = 'none';
        }
    }
    
    showNotification(`กรองข้อมูลสำเร็จ แสดง ${visibleCount} รายการ`, 'info');
}



    // ฟังก์ชันดาวน์โหลดรายงานชั้นเรียน
    function downloadClassReport() {
        window.location.href = `classes.php?action=download_report&class_id=${currentClassId}`;
    }

    // ฟังก์ชันแสดงโมดัล
    function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        document.body.classList.add('modal-open');
        modal.style.display = 'flex';
        
        // Focus ที่ input แรกในฟอร์ม (ถ้ามี)
        setTimeout(() => {
            const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        document.body.classList.remove('modal-open');
        modal.style.display = 'none';
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