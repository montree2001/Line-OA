<?php
// ตรวจสอบและกำจัดความซ้ำซ้อนของกิจกรรม
$unique_activities = [];
$seen_activity_ids = [];

// วนลูปเพื่อตรวจสอบความซ้ำซ้อนโดยใช้ activity_id เป็นเกณฑ์
if (!empty($activities) && is_array($activities)) {
    foreach ($activities as $act) {
        if (!in_array($act['activity_id'], $seen_activity_ids)) {
            $seen_activity_ids[] = $act['activity_id'];
            // ทำความสะอาดชื่อกิจกรรม ตัดเครื่องหมาย / ที่ท้ายชื่อออก
            $act['activity_name'] = rtrim($act['activity_name'], '/');
            $unique_activities[] = $act;
        }
    }
    // ใช้ข้อมูลที่ไม่ซ้ำแทนข้อมูลเดิม
    $activities = $unique_activities;
}

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
            <?php 
            // ตรวจสอบว่าไม่มีรายการกิจกรรมซ้ำกัน
            $displayed_activity_ids = []; 
            
            foreach ($activities as $activity): 
                // ข้ามรายการที่ซ้ำกัน
                if (in_array($activity['activity_id'], $displayed_activity_ids)) {
                    continue;
                }
                
                // เพิ่ม ID ที่กำลังแสดงในรายการที่แสดงแล้ว
                $displayed_activity_ids[] = $activity['activity_id'];
                
                $activity_date = new DateTime($activity['activity_date']);
                $today = new DateTime(date('Y-m-d'));
                $is_passed = $activity_date < $today;
                $is_today = $activity_date->format('Y-m-d') === $today->format('Y-m-d');
                
                $attendance_percent = 0;
                if ($activity['target_students'] > 0) {
                    $attendance_percent = ($activity['attendance_count'] / $activity['target_students']) * 100;
                }
            ?>
            <div class="activity-item" 
                 data-month="<?php echo date('m', strtotime($activity['activity_date'])); ?>"
                 data-status="<?php echo $is_passed ? 'passed' : ($is_today ? 'today' : 'upcoming'); ?>"
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
                                เช็คชื่อแล้ว: <?php echo $activity['attendance_count']; ?> คน 
                                (<?php echo number_format($attendance_percent, 1); ?>%)
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="material-icons">person</span>
                            <span>สร้างโดย: <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></span>
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
                            <select id="target_departments" name="target_departments[]" class="form-control" multiple>
                                <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="target_levels" class="form-label">ระดับชั้นเป้าหมาย</label>
                            <select id="target_levels" name="target_levels[]" class="form-control" multiple>
                                <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <label for="edit_target_departments" class="form-label">แผนกวิชาเป้าหมาย</label>
                            <select id="edit_target_departments" name="target_departments[]" class="form-control" multiple>
                                <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">ไม่เลือก = ทุกแผนก</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="edit_target_levels" class="form-label">ระดับชั้นเป้าหมาย</label>
                            <select id="edit_target_levels" name="target_levels[]" class="form-control" multiple>
                                <?php foreach ($levels as $level): ?>
                                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                                <?php endforeach; ?>
                            </select>
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
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
</style>

<!-- JavaScript เพื่อสนับสนุนฟังก์ชันการทำงาน -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ซ่อนแจ้งเตือนหลังจาก 3 วินาที
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 3000);
    });
    
    // ตั้งค่าวันที่เริ่มต้นเป็นวันนี้สำหรับฟอร์มเพิ่มกิจกรรม
    const addDateInput = document.getElementById('activity_date');
    if (addDateInput) {
        addDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // ตรวจสอบ Select2 และเริ่มต้นถ้ามี
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        // เริ่มต้น Select2 สำหรับ dropdown แบบเลือกหลายรายการ
        $('#target_departments, #edit_target_departments').select2({
            placeholder: 'เลือกแผนกวิชา',
            allowClear: true
        });
        
        $('#target_levels, #edit_target_levels').select2({
            placeholder: 'เลือกระดับชั้น',
            allowClear: true
        });
    }
    
    // เพิ่ม event listener สำหรับการตรวจสอบการส่งฟอร์ม
    const addForm = document.getElementById('addActivityForm');
    if (addForm) {
        addForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    const editForm = document.getElementById('editActivityForm');
    if (editForm) {
        editForm.addEventListener('submit', function(event) {
            if (!validateActivityForm(this)) {
                event.preventDefault();
            }
        });
    }
    
    // ตัวกรองกิจกรรมเริ่มต้น - ตรวจสอบ URL พารามิเตอร์
    initializeFilters();
    
    // สร้างกราฟถ้ามีไลบรารี Chart.js
    if (typeof Chart !== 'undefined') {
        createDepartmentChart();
        createLevelChart();
    }
});

/**
 * ตรวจสอบความถูกต้องของฟอร์มกิจกรรม
 */
function validateActivityForm(form) {
    const name = form.querySelector('[name="activity_name"]').value.trim();
    const date = form.querySelector('[name="activity_date"]').value.trim();
    
    if (!name) {
        alert('กรุณาระบุชื่อกิจกรรม');
        return false;
    }
    
    if (!date) {
        alert('กรุณาระบุวันที่จัดกิจกรรม');
        return false;
    }
    
    return true;
}

/**
 * เปิดโมดัลเพิ่มกิจกรรม
 */
function openAddActivityModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addActivityForm').reset();
    
    // กำหนดวันที่เป็นวันนี้
    document.getElementById('activity_date').value = new Date().toISOString().split('T')[0];
    
    // รีเซ็ต Select2 ถ้ามี
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('#target_departments').val(null).trigger('change');
        $('#target_levels').val(null).trigger('change');
    }
    
    // เปิดโมดัล
    document.getElementById('addActivityModal').style.display = 'flex';
}

/**
 * เปิดโมดัลแก้ไขกิจกรรม
 */
function openEditActivityModal(activityId) {
    // แสดงการโหลด
    const modal = document.getElementById('editActivityModal');
    const form = document.getElementById('editActivityForm');
    
    // แสดงโมดัล
    modal.style.display = 'flex';
    
    // ดึงข้อมูลกิจกรรม
    fetch(`ajax/get_activity.php?activity_id=${activityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const activity = data.activity;
                
                // กำหนดค่าให้ฟอร์ม
                document.getElementById('edit_activity_id').value = activity.activity_id;
                document.getElementById('edit_activity_name').value = rtrim(activity.activity_name, '/');
                document.getElementById('edit_activity_date').value = activity.activity_date;
                document.getElementById('edit_activity_location').value = activity.activity_location || '';
                document.getElementById('edit_required_attendance').checked = (activity.required_attendance == 1);
                document.getElementById('edit_activity_description').value = activity.description || '';
                
                // กำหนดแผนกวิชาและระดับชั้นเป้าหมาย
                if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
                    $('#edit_target_departments').val(activity.target_departments).trigger('change');
                    $('#edit_target_levels').val(activity.target_levels).trigger('change');
                } else {
                    // กำหนดค่าแบบทั่วไปหากไม่มี Select2
                    const deptSelect = document.getElementById('edit_target_departments');
                    if (deptSelect) {
                        Array.from(deptSelect.options).forEach(option => {
                            option.selected = activity.target_departments.includes(parseInt(option.value));
                        });
                    }
                    
                    const levelSelect = document.getElementById('edit_target_levels');
                    if (levelSelect) {
                        Array.from(levelSelect.options).forEach(option => {
                            option.selected = activity.target_levels.includes(option.value);
                        });
                    }
                }
            } else {
                // แสดงข้อความผิดพลาด
                alert(data.error || 'ไม่สามารถดึงข้อมูลกิจกรรมได้');
                closeModal('editActivityModal');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการดึงข้อมูลกิจกรรม');
            closeModal('editActivityModal');
        });
}

/**
 * ยืนยันการลบกิจกรรม
 */
function confirmDeleteActivity(activityId, activityName) {
    document.getElementById('delete_activity_id').value = activityId;
    document.getElementById('delete_activity_name').textContent = activityName;
    document.getElementById('deleteActivityModal').style.display = 'flex';
}

/**
 * ปิดโมดัล
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

/**
 * กรองรายการกิจกรรม
 */
function filterActivities() {
    const month = document.getElementById('filterMonth').value;
    const status = document.getElementById('filterStatus').value;
    const search = document.getElementById('filterSearch').value.toLowerCase();
    
    // บันทึกค่าลงใน URL เพื่อให้สามารถคงค่าการกรองได้เมื่อโหลดหน้าใหม่
    const url = new URL(window.location);
    if (month) url.searchParams.set('month', month);
    else url.searchParams.delete('month');
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    window.history.replaceState({}, '', url);
    
    // กรองรายการกิจกรรม
    const activities = document.querySelectorAll('.activity-item');
    let visibleCount = 0;
    
    activities.forEach(activity => {
        const activityMonth = activity.dataset.month;
        const activityStatus = activity.dataset.status;
        const activityName = activity.dataset.name;
        const activityId = activity.dataset.id || '';
        
        let isVisible = true;
        
        if (month && activityMonth !== month) {
            isVisible = false;
        }
        
        if (status && activityStatus !== status) {
            isVisible = false;
        }
        
        // ค้นหาทั้งในชื่อและรหัสกิจกรรม
        if (search && !activityName.includes(search) && !activityId.includes(search)) {
            isVisible = false;
        }
        
        activity.style.display = isVisible ? 'flex' : 'none';
        
        if (isVisible) {
            visibleCount++;
        }
    });
    
    // แสดงข้อความเมื่อไม่พบกิจกรรม
    document.getElementById('no-results-message').style.display = (visibleCount === 0) ? 'block' : 'none';
}

/**
 * เริ่มต้นตัวกรองจาก URL
 */
function initializeFilters() {
    const url = new URL(window.location);
    
    // ตั้งค่าตัวกรองตาม URL
    if (url.searchParams.has('month')) {
        document.getElementById('filterMonth').value = url.searchParams.get('month');
    }
    
    if (url.searchParams.has('status')) {
        document.getElementById('filterStatus').value = url.searchParams.get('status');
    }
    
    if (url.searchParams.has('search')) {
        document.getElementById('filterSearch').value = url.searchParams.get('search');
    }
    
    // ใช้ตัวกรองทันที
    if (url.searchParams.has('month') || url.searchParams.has('status') || url.searchParams.has('search')) {
        filterActivities();
    }
}

/**
 * ฟังก์ชันตัดอักขระที่ระบุออกจากท้ายข้อความ
 */
function rtrim(str, chars) {
    chars = chars || '\\s';
    str = str || '';
    return str.replace(new RegExp('[' + chars + ']+$', 'g'), '');
}

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามแผนกวิชา
 */
function createDepartmentChart() {
    // สร้าง AJAX request เพื่อดึงข้อมูล
    fetch('ajax/get_activity_summary_by_department.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('departmentChart').getContext('2d');
                
                // ข้อมูลกราฟ
                const chartData = {
                    labels: data.department_names,
                    datasets: [
                        {
                            label: 'นักเรียนทั้งหมด',
                            data: data.total_students,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'เข้าร่วมกิจกรรม',
                            data: data.participants,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                };
                
                // สร้างกราฟ
                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                console.error('Error loading department data:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // สร้างกราฟตัวอย่างหากไม่สามารถดึงข้อมูลได้
            const ctx = document.getElementById('departmentChart').getContext('2d');
            
            // ข้อมูลตัวอย่าง
            const sampleData = {
                labels: ['ช่างยนต์', 'ช่างไฟฟ้า', 'ช่างอิเล็กทรอนิกส์', 'เทคโนโลยีสารสนเทศ', 'ช่างเชื่อมโลหะ'],
                datasets: [
                    {
                        label: 'นักเรียนทั้งหมด',
                        data: [50, 45, 30, 40, 25],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'เข้าร่วมกิจกรรม',
                        data: [40, 35, 25, 30, 20],
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            };
            
            // สร้างกราฟตัวอย่าง
            new Chart(ctx, {
                type: 'bar',
                data: sampleData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
}

/**
 * สร้างกราฟการเข้าร่วมกิจกรรมตามระดับชั้น
 */
function createLevelChart() {
    // สร้าง AJAX request เพื่อดึงข้อมูล
    fetch('ajax/get_activity_summary_by_level.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('levelChart').getContext('2d');
                
                // ข้อมูลกราฟ
                const chartData = {
                    labels: data.levels,
                    datasets: [
                        {
                            label: 'นักเรียนทั้งหมด',
                            data: data.total_students,
                            backgroundColor: 'rgba(153, 102, 255, 0.5)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'เข้าร่วมกิจกรรม',
                            data: data.participants,
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }
                    ]
                };
                
                // สร้างกราฟ
                new Chart(ctx, {
                    type: 'bar',
                    data: chartData,
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            } else {
                console.error('Error loading level data:', data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // สร้างกราฟตัวอย่างหากไม่สามารถดึงข้อมูลได้
            const ctx = document.getElementById('levelChart').getContext('2d');
            
            // ข้อมูลตัวอย่าง
            const sampleData = {
                labels: ['ปวช.1', 'ปวช.2', 'ปวช.3', 'ปวส.1', 'ปวส.2'],
                datasets: [
                    {
                        label: 'นักเรียนทั้งหมด',
                        data: [60, 55, 50, 40, 35],
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'เข้าร่วมกิจกรรม',
                        data: [50, 45, 40, 30, 25],
                        backgroundColor: 'rgba(255, 159, 64, 0.5)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    }
                ]
            };
            
            // สร้างกราฟตัวอย่าง
            new Chart(ctx, {
                type: 'bar',
                data: sampleData,
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
}
</script>