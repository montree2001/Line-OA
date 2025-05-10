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

<!-- ปุ่มกลับไปหน้ากิจกรรม -->
<div class="action-buttons">
    <a href="activities.php" class="btn btn-secondary">
        <span class="material-icons">arrow_back</span>
        กลับไปหน้ากิจกรรม
    </a>
</div>

<!-- รายละเอียดกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">event</span>
        รายละเอียดกิจกรรม
    </div>
    <div class="card-body">
        <div class="activity-detail-card">
            <div class="activity-header">
                <div class="activity-title">
                    <h2><?php echo htmlspecialchars($activity['activity_name']); ?></h2>
                    <div class="activity-meta">
                        <span class="activity-date">
                            <span class="material-icons">event</span>
                            <?php echo date('d F Y', strtotime($activity['activity_date'])); ?>
                        </span>
                        <?php if (!empty($activity['activity_location'])): ?>
                        <span class="activity-location">
                            <span class="material-icons">place</span>
                            <?php echo htmlspecialchars($activity['activity_location']); ?>
                        </span>
                        <?php endif; ?>
                        <span class="activity-creator">
                            <span class="material-icons">person</span>
                            สร้างโดย: <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                        </span>
                    </div>
                </div>
                <div class="activity-badge <?php echo $activity['required_attendance'] ? 'required' : 'optional'; ?>">
                    <?php echo $activity['required_attendance'] ? 'กิจกรรมบังคับ' : 'กิจกรรมไม่บังคับ'; ?>
                </div>
            </div>
            
            <?php if (!empty($activity['description'])): ?>
            <div class="activity-description">
                <h3>รายละเอียด</h3>
                <p><?php echo nl2br(htmlspecialchars($activity['description'])); ?></p>
            </div>
            <?php endif; ?>
            
            <div class="activity-targets">
                <h3>กลุ่มเป้าหมาย</h3>
                <div class="targets-info">
                    <div class="target-group">
                        <span class="target-label">แผนกวิชา:</span>
                        <span class="target-value">
                            <?php 
                            if (empty($activity['target_departments'])) {
                                echo 'ทุกแผนก';
                            } else {
                                echo implode(', ', $activity['target_departments']);
                            }
                            ?>
                        </span>
                    </div>
                    <div class="target-group">
                        <span class="target-label">ระดับชั้น:</span>
                        <span class="target-value">
                            <?php 
                            if (empty($activity['target_levels'])) {
                                echo 'ทุกระดับชั้น';
                            } else {
                                echo implode(', ', $activity['target_levels']);
                            }
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ส่วนของฟิลเตอร์การค้นหา -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ตัวกรองข้อมูล
    </div>
    <div class="filter-container">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterDepartment" class="form-label">แผนกวิชา</label>
                    <select id="filterDepartment" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterLevel" class="form-label">ระดับชั้น</label>
                    <select id="filterLevel" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($levels as $level): ?>
                        <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterClass" class="form-label">กลุ่มเรียน</label>
                    <select id="filterClass" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        <!-- จะเติมตัวเลือกด้วย JavaScript -->
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="filterSearch" class="form-label">ค้นหา</label>
                    <div class="input-group">
                        <input type="text" id="filterSearch" class="form-control" placeholder="รหัสนักเรียน, ชื่อ">
                        <button class="btn btn-primary" id="btnSearch">
                            <span class="material-icons">search</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- แสดงรายชื่อนักเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อนักเรียน
        <span id="classTitle"></span>
    </div>
    
    <div id="loadingIndicator" style="display: none;">
        <div class="text-center p-4">
            <div class="spinner"></div>
            <p>กำลังโหลดข้อมูล...</p>
        </div>
    </div>
    
    <div id="studentListContainer" style="display: none;">
        <form id="attendanceForm" method="post">
            <input type="hidden" name="activity_id" value="<?php echo $activity['activity_id']; ?>">
            
            <div class="bulk-actions">
                <button type="button" class="btn btn-secondary mr-2" onclick="checkAllStudents()">
                    <span class="material-icons">done_all</span>
                    เลือกทั้งหมด
                </button>
                <button type="button" class="btn btn-secondary mr-2" onclick="uncheckAllStudents()">
                    <span class="material-icons">remove_done</span>
                    ยกเลิกเลือกทั้งหมด
                </button>
                <div class="btn-group">
                    <button type="button" class="btn btn-success" onclick="setAllStatus('present')">
                        <span class="material-icons">check_circle</span>
                        เลือกทั้งหมด: เข้าร่วม
                    </button>
                    <button type="button" class="btn btn-danger" onclick="setAllStatus('absent')">
                        <span class="material-icons">cancel</span>
                        เลือกทั้งหมด: ไม่เข้าร่วม
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="5%">เลือก</th>
                            <th width="10%">รหัสนักเรียน</th>
                            <th width="25%">ชื่อ-นามสกุล</th>
                            <th width="15%">ระดับชั้น/ห้อง</th>
                            <th width="15%">สถานะการเข้าร่วม</th>
                            <th width="25%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody id="studentList">
                        <tr>
                            <td colspan="7" class="text-center">กรุณาเลือกแผนกวิชา ระดับชั้น และกลุ่มเรียน แล้วกดค้นหา</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- สรุปข้อมูลการเช็คชื่อ -->
            <div class="attendance-summary" id="attendanceSummary" style="display: none;">
                <div class="row">
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="presentCount">0</div>
                            <div class="attendance-stat-label">เข้าร่วม</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="absentCount">0</div>
                            <div class="attendance-stat-label">ไม่เข้าร่วม</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value" id="totalCount">0</div>
                            <div class="attendance-stat-label">ทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions" id="formActions" style="display: none;">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <span class="material-icons">refresh</span>
                    ยกเลิก
                </button>
                <button type="submit" name="save_attendance" value="1" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการเข้าร่วมกิจกรรม
                </button>
            </div>
        </form>
    </div>
    
    <div id="noStudentsMessage" style="display: none;">
        <div class="text-center p-4">
            <span class="material-icons" style="font-size: 48px; color: #ccc;">person_off</span>
            <p>ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่เลือก</p>
        </div>
    </div>
</div>

<!-- สรุปการเข้าร่วมกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">insights</span>
        สรุปการเข้าร่วมกิจกรรม
    </div>
    <div class="card-body">
        <div class="attendance-summary-large">
            <div id="loadingAttendanceSummary">
                <div class="text-center p-4">
                    <div class="spinner"></div>
                    <p>กำลังโหลดข้อมูล...</p>
                </div>
            </div>
            
            <div id="attendanceSummaryContent" style="display: none;">
                <div class="attendance-stats">
                    <div class="attendance-stat-large">
                        <div class="stat-value" id="summary-total">0</div>
                        <div class="stat-label">จำนวนนักเรียนทั้งหมด</div>
                    </div>
                    <div class="attendance-stat-large">
                        <div class="stat-value" id="summary-present">0</div>
                        <div class="stat-label">เข้าร่วมกิจกรรม</div>
                    </div>
                    <div class="attendance-stat-large">
                        <div class="stat-value" id="summary-absent">0</div>
                        <div class="stat-label">ไม่เข้าร่วมกิจกรรม</div>
                    </div>
                    <div class="attendance-stat-large">
                        <div class="stat-value" id="summary-percent">0%</div>
                        <div class="stat-label">เปอร์เซ็นต์การเข้าร่วม</div>
                    </div>
                </div>
                
                <!-- แสดงกราฟหรือแผนภูมิสรุปตามแผนกและระดับชั้นที่นี่ -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h3>การเข้าร่วมกิจกรรมตามแผนกวิชา</h3>
                            <canvas id="departmentChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h3>การเข้าร่วมกิจกรรมตามระดับชั้น</h3>
                            <canvas id="levelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="noAttendanceSummary" style="display: none;">
                <div class="text-center p-4">
                    <span class="material-icons" style="font-size: 48px; color: #ccc;">event_busy</span>
                    <p>ยังไม่มีข้อมูลการเข้าร่วมกิจกรรม</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- สคริปต์เพิ่มเติมเฉพาะหน้านี้ -->
<style>
/* รูปแบบการ์ดรายละเอียดกิจกรรม */
.activity-detail-card {
    background-color: white;
    border-radius: 8px;
    padding: 20px;
}

.activity-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

.activity-title h2 {
    margin: 0 0 10px 0;
    color: var(--primary-color, #06c755);
}

.activity-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    color: #666;
}

.activity-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.activity-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    height: fit-content;
}

.activity-badge.required {
    background-color: #ff9800;
    color: white;
}

.activity-badge.optional {
    background-color: #4caf50;
    color: white;
}

.activity-description, .activity-targets {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.activity-description h3, .activity-targets h3 {
    font-size: 18px;
    margin: 0 0 10px 0;
    color: #333;
}

.targets-info {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.target-group {
    display: flex;
    flex-direction: column;
    min-width: 200px;
}

.target-label {
    font-weight: bold;
    color: #666;
}

.target-value {
    color: #333;
}

/* รูปแบบสรุปการเข้าร่วมกิจกรรม */
.attendance-summary-large {
    padding: 20px 0;
}

.attendance-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
}

.attendance-stat-large {
    text-align: center;
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    flex: 1;
    margin: 0 10px;
}

.stat-value {
    font-size: 36px;
    font-weight: bold;
    color: var(--primary-color, #06c755);
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
}

.chart-container {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.chart-container h3 {
    font-size: 16px;
    margin: 0 0 15px 0;
    color: #333;
    text-align: center;
}

canvas {
    width: 100% !important;
    max-height: 300px;
}
</style>