<?php
// ตรวจสอบว่ามีข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาดเพื่อแสดง
$alert_success = $save_success ?? false;
$alert_error = $save_error ?? false;
?>

<!-- แสดงข้อความแจ้งเตือนความสำเร็จหรือข้อผิดพลาด -->
<?php if ($alert_success): ?>
    <div class="alert alert-success" id="success-alert">
        <span class="material-icons">check_circle</span>
        <div class="alert-message"><?php echo $response_message ?? 'บันทึกข้อมูลเรียบร้อยแล้ว'; ?></div>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">
            <span class="material-icons">close</span>
        </button>
    </div>
<?php endif; ?>

<?php if ($alert_error): ?>
    <div class="alert alert-error" id="error-alert">
        <span class="material-icons">error</span>
        <div class="alert-message">เกิดข้อผิดพลาด: <?php echo htmlspecialchars($error_message ?? 'ไม่สามารถบันทึกข้อมูลได้'); ?></div>
        <button class="alert-close" onclick="this.parentElement.style.display='none'">
            <span class="material-icons">close</span>
        </button>
    </div>
<?php endif; ?>

<!-- คำอธิบายหน้าเว็บ -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">info</span>
        เกี่ยวกับหน้านี้
    </div>
    <div class="card-body">
        <p>หน้านี้ใช้สำหรับจัดการกิจกรรมกลางของวิทยาลัย เช่น กิจกรรมหน้าเสาธง กิจกรรมวันสำคัญ หรือกิจกรรมพิเศษอื่นๆ</p>
        <p>คุณสามารถสร้าง แก้ไข หรือลบกิจกรรม รวมถึงเลือกกลุ่มเป้าหมายของกิจกรรมได้</p>
        <p>หลังจากสร้างกิจกรรมแล้ว คุณสามารถเช็คชื่อนักเรียนที่เข้าร่วมกิจกรรมได้ที่หน้า "บันทึกการเข้าร่วมกิจกรรม"</p>
    </div>
</div>


<!-- ปุ่มเพิ่มกิจกรรมใหม่ -->
<div class="action-buttons">
    <button class="btn btn-primary" onclick="openAddActivityModal()">
        <span class="material-icons">add</span>
        เพิ่มกิจกรรมใหม่
    </button>
</div>

<!-- รายการกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">event</span>
        รายการกิจกรรมกลาง ปีการศึกษา <?php echo $academic_year_display; ?>
    </div>
    <div class="card-body">
        <div class="filter-container">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filterMonth" class="form-label">เดือน</label>
                        <select id="filterMonth" class="form-control" onchange="filterActivities()">
                            <option value="">-- ทุกเดือน --</option>
                            <option value="01">มกราคม</option>
                            <option value="02">กุมภาพันธ์</option>
                            <option value="03">มีนาคม</option>
                            <option value="04">เมษายน</option>
                            <option value="05">พฤษภาคม</option>
                            <option value="06">มิถุนายน</option>
                            <option value="07">กรกฎาคม</option>
                            <option value="08">สิงหาคม</option>
                            <option value="09">กันยายน</option>
                            <option value="10">ตุลาคม</option>
                            <option value="11">พฤศจิกายน</option>
                            <option value="12">ธันวาคม</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filterStatus" class="form-label">สถานะ</label>
                        <select id="filterStatus" class="form-control" onchange="filterActivities()">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="upcoming">กิจกรรมที่ยังไม่จัด</option>
                            <option value="passed">กิจกรรมที่จัดไปแล้ว</option>
                            <option value="today">กิจกรรมวันนี้</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="filterSearch" class="form-label">ค้นหา</label>
                        <input type="text" id="filterSearch" class="form-control" placeholder="ค้นหาชื่อกิจกรรม" oninput="filterActivities()">
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($activities)): ?>
            <div class="empty-state">
                <span class="material-icons">event_busy</span>
                <p>ไม่พบข้อมูลกิจกรรม กรุณาเพิ่มกิจกรรมใหม่</p>
            </div>
        <?php else: ?>




            <div class="activity-list">
                <?php foreach ($activities as $activity): ?>
                    <div class="activity-item"
                        data-month="<?php echo date('m', strtotime($activity['activity_date'])); ?>"
                        data-status="<?php
                                        $activity_date = new DateTime($activity['activity_date']);
                                        $today = new DateTime(date('Y-m-d'));
                                        $is_passed = $activity_date < $today;
                                        $is_today = $activity_date->format('Y-m-d') === $today->format('Y-m-d');
                                        echo $is_passed ? 'passed' : ($is_today ? 'today' : 'upcoming');
                                        ?>"
                        data-name="<?php echo strtolower($activity['activity_name']); ?>"
                        data-id="<?php echo $activity['activity_id']; ?>">
                        <div class="activity-date">
                            <div class="date-day"><?php echo date('d', strtotime($activity['activity_date'])); ?></div>
                            <div class="date-month"><?php echo date('M', strtotime($activity['activity_date'])); ?></div>
                            <div class="date-year"><?php echo date('Y', strtotime($activity['activity_date'])); ?></div>
                            <?php if ($is_today): ?>
                                <div class="date-badge today">วันนี้</div>
                            <?php elseif ($is_passed): ?>
                                <div class="date-badge passed">ผ่านไปแล้ว</div>
                            <?php else: ?>
                                <div class="date-badge upcoming">กำลังจะมาถึง</div>
                            <?php endif; ?>
                        </div>
                        <div class="activity-details">
                            <h3 class="activity-name">
                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                                <small class="activity-id">(รหัส: <?php echo $activity['activity_id']; ?>)</small>
                            </h3>
                            <div class="activity-info">
                                <div class="info-item">
                                    <span class="material-icons">place</span>
                                    <span><?php echo htmlspecialchars($activity['activity_location'] ?: 'ไม่ระบุสถานที่'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="material-icons">group</span>
                                    <span>
                                        กลุ่มเป้าหมาย:
                                        <?php
                                        if (empty($activity['target_departments']) && empty($activity['target_levels'])) {
                                            echo 'ทุกแผนก/ทุกระดับชั้น';
                                        } else {
                                            $targets = [];
                                            if (!empty($activity['target_departments'])) {
                                                $targets[] = implode(', ', $activity['target_departments']);
                                            }
                                            if (!empty($activity['target_levels'])) {
                                                $targets[] = implode(', ', $activity['target_levels']);
                                            }
                                            echo implode(' / ', $targets);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="material-icons">how_to_reg</span>
                                    <span>
                                        เช็คชื่อแล้ว: <?php echo $activity['attendance_count'] ?? 0; ?> คน
                                        (<?php
                                            $attendance_percent = 0;
                                            if (isset($activity['target_students']) && $activity['target_students'] > 0) {
                                                $attendance_percent = ($activity['attendance_count'] / $activity['target_students']) * 100;
                                            }
                                            echo number_format($attendance_percent, 1);
                                            ?>%)
                                    </span>
                                </div>
                                <!-- ในส่วนที่แสดงข้อมูลผู้สร้างกิจกรรม -->
                                <div class="info-item">
                                    <span class="material-icons">person</span>
                                    <span>สร้างโดย: <?php echo htmlspecialchars(($activity['title'] ? $activity['title'] . ' ' : '') . $activity['first_name'] . ' ' . $activity['last_name']); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($activity['description'])): ?>
                                <div class="activity-description">
                                    <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="activity-actions">
                            <a href="activity_attendance.php?id=<?php echo $activity['activity_id']; ?>" class="btn btn-primary" title="บันทึกการเข้าร่วม">
                                <span class="material-icons">how_to_reg</span>
                            </a>
                            <button class="btn btn-info" onclick="openEditActivityModal(<?php echo $activity['activity_id']; ?>)" title="แก้ไข">
                                <span class="material-icons">edit</span>
                            </button>
                            <button class="btn btn-danger" onclick="confirmDeleteActivity(<?php echo $activity['activity_id']; ?>, '<?php echo addslashes($activity['activity_name']); ?>')" title="ลบ">
                                <span class="material-icons">delete</span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="no-results-message" class="empty-state" style="display: none;">
                    <span class="material-icons">search_off</span>
                    <p>ไม่พบกิจกรรมที่ตรงกับเงื่อนไขการค้นหา</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- โมดัลเพิ่มกิจกรรม -->
<div id="addActivityModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>เพิ่มกิจกรรมใหม่</h2>
            <span class="close" onclick="closeModal('addActivityModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addActivityForm" method="post">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                            <input type="text" id="activity_name" name="activity_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
                            <input type="date" id="activity_date" name="activity_date" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
                            <input type="text" id="activity_location" name="activity_location" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">บังคับเข้าร่วม</label>
                            <div class="form-check">
                                <input type="checkbox" id="required_attendance" name="required_attendance" class="form-check-input">
                                <label for="required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="target_departments" class="form-label">แผนกวิชาเป้าหมาย</label>
                            <div class="checkbox-container">
                                <?php foreach ($departments as $department): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="dept_<?php echo $department['department_id']; ?>" name="target_departments[]" value="<?php echo $department['department_id']; ?>" class="form-check-input">
                                        <label for="dept_<?php echo $department['department_id']; ?>" class="form-check-label"><?php echo $department['department_name']; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="target_levels" class="form-label">ระดับชั้นเป้าหมาย</label>
                            <div class="checkbox-container">
                                <?php foreach ($levels as $level): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="level_<?php echo str_replace('.', '_', $level); ?>" name="target_levels[]" value="<?php echo $level; ?>" class="form-check-input">
                                        <label for="level_<?php echo str_replace('.', '_', $level); ?>" class="form-check-label"><?php echo $level; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="activity_description" class="form-label">รายละเอียดกิจกรรม</label>
                    <textarea id="activity_description" name="activity_description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addActivityModal')">ยกเลิก</button>
                    <button type="submit" name="save_activity" class="btn btn-primary">
                        <span class="material-icons">save</span>
                        บันทึกกิจกรรม
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขกิจกรรม -->
<div id="editActivityModal" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h2>แก้ไขกิจกรรม</h2>
            <span class="close" onclick="closeModal('editActivityModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editActivityForm" method="post">
                <input type="hidden" id="edit_activity_id" name="activity_id">

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="edit_activity_name" class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                            <input type="text" id="edit_activity_name" name="activity_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="edit_activity_date" class="form-label">วันที่จัดกิจกรรม <span class="text-danger">*</span></label>
                            <input type="date" id="edit_activity_date" name="activity_date" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="edit_activity_location" class="form-label">สถานที่จัดกิจกรรม</label>
                            <input type="text" id="edit_activity_location" name="activity_location" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">บังคับเข้าร่วม</label>
                            <div class="form-check">
                                <input type="checkbox" id="edit_required_attendance" name="required_attendance" class="form-check-input">
                                <label for="edit_required_attendance" class="form-check-label">เป็นกิจกรรมบังคับ (มีผลต่อการจบการศึกษา)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">แผนกวิชาเป้าหมาย</label>
                            <div class="checkbox-container" id="edit_departments_container">
                                <?php foreach ($departments as $department): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="edit_dept_<?php echo $department['department_id']; ?>" name="target_departments[]" value="<?php echo $department['department_id']; ?>" class="form-check-input">
                                        <label for="edit_dept_<?php echo $department['department_id']; ?>" class="form-check-label"><?php echo $department['department_name']; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">ระดับชั้นเป้าหมาย</label>
                            <div class="checkbox-container" id="edit_levels_container">
                                <?php foreach ($levels as $level): ?>
                                    <div class="form-check">
                                        <input type="checkbox" id="edit_level_<?php echo str_replace('.', '_', $level); ?>" name="target_levels[]" value="<?php echo $level; ?>" class="form-check-input">
                                        <label for="edit_level_<?php echo str_replace('.', '_', $level); ?>" class="form-check-label"><?php echo $level; ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">ไม่เลือก = ทุกระดับชั้น</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_activity_description" class="form-label">รายละเอียดกิจกรรม</label>
                    <textarea id="edit_activity_description" name="activity_description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editActivityModal')">ยกเลิก</button>
                    <button type="submit" name="edit_activity" class="btn btn-primary">
                        <span class="material-icons">save</span>
                        บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- โมดัลลบกิจกรรม -->
<div id="deleteActivityModal" class="modal">
    <div class="modal-content modal-sm">
        <div class="modal-header">
            <h2>ยืนยันการลบกิจกรรม</h2>
            <span class="close" onclick="closeModal('deleteActivityModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form id="deleteActivityForm" method="post">
                <input type="hidden" id="delete_activity_id" name="activity_id">

                <div class="alert alert-warning">
                    <span class="material-icons">warning</span>
                    <span>คุณกำลังจะลบกิจกรรม: <strong id="delete_activity_name"></strong></span>
                </div>

                <p>การลบกิจกรรมจะลบข้อมูลการเข้าร่วมกิจกรรมของนักเรียนทั้งหมดด้วย และไม่สามารถเรียกคืนได้</p>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteActivityModal')">ยกเลิก</button>
                    <button type="submit" name="delete_activity" class="btn btn-danger">
                        <span class="material-icons">delete</span>
                        ยืนยันการลบ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- สรุปภาพรวมกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">assessment</span>
        สรุปภาพรวมกิจกรรม ปีการศึกษา <?php echo $academic_year_display; ?>
    </div>
    <div class="card-body">
        <!-- สรุปจำนวนกิจกรรม -->
        <div class="activity-summary">
            <div class="row">
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-icon">
                            <span class="material-icons">event</span>
                        </div>
                        <div class="summary-content">
                            <div class="summary-value"><?php echo count($activities); ?></div>
                            <div class="summary-label">กิจกรรมทั้งหมด</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-icon upcoming">
                            <span class="material-icons">event_upcoming</span>
                        </div>
                        <div class="summary-content">
                            <?php
                            $today = date('Y-m-d');
                            $upcoming_count = 0;
                            foreach ($activities as $activity) {
                                if ($activity['activity_date'] > $today) {
                                    $upcoming_count++;
                                }
                            }
                            ?>
                            <div class="summary-value"><?php echo $upcoming_count; ?></div>
                            <div class="summary-label">กิจกรรมที่กำลังจะมาถึง</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-icon today">
                            <span class="material-icons">today</span>
                        </div>
                        <div class="summary-content">
                            <?php
                            $today_count = 0;
                            foreach ($activities as $activity) {
                                if ($activity['activity_date'] === $today) {
                                    $today_count++;
                                }
                            }
                            ?>
                            <div class="summary-value"><?php echo $today_count; ?></div>
                            <div class="summary-label">กิจกรรมวันนี้</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-card">
                        <div class="summary-icon passed">
                            <span class="material-icons">event_available</span>
                        </div>
                        <div class="summary-content">
                            <?php
                            $passed_count = 0;
                            foreach ($activities as $activity) {
                                if ($activity['activity_date'] < $today) {
                                    $passed_count++;
                                }
                            }
                            ?>
                            <div class="summary-value"><?php echo $passed_count; ?></div>
                            <div class="summary-label">กิจกรรมที่ผ่านไปแล้ว</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟแสดงการมีส่วนร่วมกิจกรรม -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="chart-container">
                    <h3 class="chart-title">การเข้าร่วมกิจกรรมตามแผนกวิชา</h3>
                    <canvas id="departmentChart" height="250"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h3 class="chart-title">การเข้าร่วมกิจกรรมตามระดับชั้น</h3>
                    <canvas id="levelChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- สไตล์สำหรับส่วนสรุป -->
<style>
    .activity-summary {
        margin-bottom: 20px;
    }

    .summary-card {
        display: flex;
        align-items: center;
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        height: 100%;
    }

    .summary-icon {
        background-color: var(--primary-color, #06c755);
        color: white;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .summary-icon.upcoming {
        background-color: #4caf50;
    }

    .summary-icon.today {
        background-color: #ff9800;
    }

    .summary-icon.passed {
        background-color: #9e9e9e;
    }

    .summary-icon .material-icons {
        font-size: 24px;
    }

    .summary-content {
        display: flex;
        flex-direction: column;
    }

    .summary-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .summary-label {
        font-size: 14px;
        color: #666;
    }

    .chart-container {
        background-color: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        height: 100%;
    }

    .chart-title {
        font-size: 16px;
        text-align: center;
        margin-bottom: 15px;
        color: #333;
    }

    .activity-id {
        font-size: 0.75em;
        color: #666;
        font-weight: normal;
        margin-left: 5px;
    }

    /* กล่องตัวเลือกแบบ checkbox */
    .checkbox-container {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        background-color: #f9f9f9;
    }

    .checkbox-container .form-check {
        margin-bottom: 8px;
    }

    .checkbox-container .form-check:last-child {
        margin-bottom: 0;
    }

    /* ปรับปรุงรูปแบบโมดัล */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        position: relative;
        background-color: #fff;
        margin: auto;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    /* ป้องกันการปิดโมดัลเมื่อคลิกภายนอก */
    .modal.prevent-close {
        pointer-events: none;
    }

    .modal.prevent-close .modal-content {
        pointer-events: auto;
    }

    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            max-height: 95vh;
        }

        .checkbox-container {
            max-height: 150px;
        }

        .row {
            flex-direction: column;
        }

        .col-md-3,
        .col-md-4,
        .col-md-6,
        .col-md-8 {
            width: 100%;
            margin-bottom: 15px;
        }
    }
</style>

<!-- JavaScript เพื่อสนับสนุนฟังก์ชันการทำงาน -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ซ่อนแจ้งเตือนหลังจาก 3 วินาที
        const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
        alerts.forEach(alert => {
            setTimeout(function() {
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            }, 3000);
        });

        // ป้องกันการปิดโมดัลเมื่อคลิกภายนอก
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.addEventListener('click', function(event) {
                if (event.target === this) {
                    // ไม่ให้ปิดโมดัลเมื่อคลิกพื้นหลัง
                    event.stopPropagation();
                }
            });
        });

        // ป้องกันการกด ESC เพื่อปิดโมดัล
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                return false;
            }
        });
    });
</script>