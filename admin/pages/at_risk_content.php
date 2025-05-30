<!-- แท็บสำหรับนักเรียนกลุ่มเสี่ยง -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="at-risk">เสี่ยงตกกิจกรรม <span class="badge"><?php echo $data['at_risk_count']; ?></span></div>
        <div class="tab" data-tab="frequently-absent">ขาดแถวบ่อย <span class="badge"><?php echo $data['frequently_absent_count']; ?></span></div>
        <div class="tab" data-tab="pending-notification">รอการแจ้งเตือน <span class="badge"><?php echo $data['pending_notification_count']; ?></span></div>
    </div>
</div>

<!-- เนื้อหาแท็บนักเรียนเสี่ยงตกกิจกรรม -->
<div id="at-risk-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">warning</span>
            นักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว
            <span class="subtitle">ปีการศึกษา <?php echo $data['academic_year']['year']; ?> ภาคเรียนที่ <?php echo $data['academic_year']['semester']; ?></span>
        </div>
        
        <div class="filter-container">
            <div class="filter-row">
                <div class="filter-group">
                    <div class="filter-label">แผนกวิชา</div>
                    <select class="form-control" id="departmentId">
                        <option value="">-- ทุกแผนก --</option>
                        <?php foreach ($data['departments'] as $department): ?>
                        <option value="<?php echo $department['department_id']; ?>" <?php echo ($data['filters']['department_id'] == $department['department_id']) ? 'selected' : ''; ?>><?php echo $department['department_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <div class="filter-label">ระดับชั้น</div>
                    <select class="form-control" id="classLevel">
                        <option value="">-- ทุกระดับชั้น --</option>
                        <?php foreach ($data['class_levels'] as $level): ?>
                        <option value="<?php echo $level; ?>" <?php echo ($data['filters']['class_level'] == $level) ? 'selected' : ''; ?>><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

           

                <div class="filter-group">
                    <div class="filter-label">กลุ่ม</div>
                    <select class="form-control" id="classRoom">
                        <option value="">-- ทุกกลุ่ม --</option>
                        <?php foreach ($data['class_rooms'] as $room): ?>
                        <option value="<?php echo $room; ?>" <?php echo ($data['filters']['class_room'] == $room) ? 'selected' : ''; ?>><?php echo $room; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="filter-row">
                <div class="filter-group">
                    <div class="filter-label">ครูที่ปรึกษา</div>
                    <select class="form-control" id="advisor">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($data['advisors'] as $advisor): ?>
                        <option value="<?php echo $advisor['teacher_id']; ?>" <?php echo ($data['filters']['advisor'] == $advisor['teacher_id']) ? 'selected' : ''; ?>>
                            <?php echo $advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group filter-range">
                    <div class="filter-label">อัตราการเข้าแถว (%)</div>
                    <div class="range-inputs">
                        <input type="number" id="minAttendance" class="form-control" placeholder="ต่ำสุด" min="0" max="100" value="<?php echo $data['filters']['min_attendance']; ?>">
                        <span class="range-separator">-</span>
                        <input type="number" id="maxAttendance" class="form-control" placeholder="สูงสุด" min="0" max="100" value="<?php echo $data['filters']['max_attendance']; ?>">
                    </div>
                </div>
                
                <div class="filter-group filter-buttons">
                    <button class="filter-button" onclick="filterStudents()">
                        <span class="material-icons">filter_list</span>
                        กรองข้อมูล
                    </button>
                    <button class="reset-button" onclick="resetFilters()">
                        <span class="material-icons">refresh</span>
                        รีเซ็ต
                    </button>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
        <table class="data-table" id="at-risk-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="12%">อัตราการเข้าแถว</th>
                        <th width="8%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="13%">การแจ้งเตือน</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['at_risk_students'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียนที่เสี่ยงตกกิจกรรม</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['at_risk_students'] as $student): ?>
                            <tr data-id="<?php echo $student['id']; ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"><?php echo $student['initial']; ?></div>
                                        <div class="student-details">
                                            <div class="student-name"><?php echo $student['name']; ?></div>
                                            <div class="student-class">รหัส <?php echo $student['student_code']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['class']; ?></td>
                                <td>
                                    <?php
                                        $status_class = 'success';
                                        if ($student['attendance_rate'] < $data['risk_settings']['high']) {
                                            $status_class = 'danger';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['medium']) {
                                            $status_class = 'warning';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['low']) {
                                            $status_class = 'primary';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $student['attendance_rate']; ?>%</span>
                                </td>
                                <td><?php echo $student['days_missed']; ?> วัน</td>
                                <td><?php echo $student['advisor']; ?></td>
                                <td><?php echo $student['notification_status']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(<?php echo $student['id']; ?>)">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>', '<?php echo $student['class']; ?>', <?php echo $student['attendance_rate']; ?>, <?php echo $student['days_present']; ?>, <?php echo $student['days_missed']; ?>, <?php echo $student['total_days']; ?>, '<?php echo htmlspecialchars($student['advisor']); ?>', '<?php echo htmlspecialchars($student['advisor_phone']); ?>')">
                                            <span class="material-icons">send</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            <?php if ($data['pagination']['total_pages'] > 1): ?>
                <div class="pagination">
                    <?php if ($data['pagination']['current_page'] > 1): ?>
                        <a href="?page=<?php echo $data['pagination']['current_page'] - 1; ?>" class="page-link">«</a>
                    <?php endif; ?>
                    
                    <?php
                        $start_page = max(1, $data['pagination']['current_page'] - 2);
                        $end_page = min($data['pagination']['total_pages'], $data['pagination']['current_page'] + 2);
                    ?>
                    
                    <?php if ($start_page > 1): ?>
                        <a href="?page=1" class="page-link">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="page-separator">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i == $data['pagination']['current_page']) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($end_page < $data['pagination']['total_pages']): ?>
                        <?php if ($end_page < $data['pagination']['total_pages'] - 1): ?>
                            <span class="page-separator">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $data['pagination']['total_pages']; ?>" class="page-link"><?php echo $data['pagination']['total_pages']; ?></a>
                    <?php endif; ?>
                    
                    <?php if ($data['pagination']['current_page'] < $data['pagination']['total_pages']): ?>
                        <a href="?page=<?php echo $data['pagination']['current_page'] + 1; ?>" class="page-link">»</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <button class="btn btn-primary bulk-action-btn" onclick="showBulkNotificationModal()">
                <span class="material-icons">send</span>
                ส่งรายงานไปยังผู้ปกครองทั้งหมด
            </button>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="card">
            <div class="card-title">
                <span class="material-icons">bar_chart</span>
                สถิติอัตราการเข้าแถวแยกตามระดับชั้น
            </div>
            
            <div class="chart-container" id="attendance-by-level-chart">
                <?php if (empty($data['attendance_by_level'])): ?>
                    <div class="no-data">ไม่มีข้อมูลสถิติการเข้าแถว</div>
                <?php else: ?>
                    <!-- ในทางปฏิบัติจริง จะใช้ JavaScript สร้างกราฟ -->
                    <div class="chart-bars">
                        <?php foreach ($data['attendance_by_level'] as $level => $rate): ?>
                            <div class="chart-bar-item">
                                <div class="chart-bar-container">
                                    <?php
                                        $color_class = 'bg-success';
                                        if ($rate < $data['risk_settings']['high']) {
                                            $color_class = 'bg-danger';
                                        } elseif ($rate < $data['risk_settings']['medium']) {
                                            $color_class = 'bg-warning';
                                        } elseif ($rate < $data['risk_settings']['low']) {
                                            $color_class = 'bg-primary';
                                        }
                                    ?>
                                    <div class="chart-bar <?php echo $color_class; ?>" style="height: <?php echo min(100, $rate); ?>%">
                                        <span class="chart-bar-value"><?php echo $rate; ?>%</span>
                                    </div>
                                </div>
                                <div class="chart-bar-label"><?php echo $level; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">
                <span class="material-icons">pie_chart</span>
                สถิติอัตราการเข้าแถวแยกตามแผนก
            </div>
            
            <div class="chart-container" id="attendance-by-department-chart">
                <?php if (empty($data['attendance_by_department'])): ?>
                    <div class="no-data">ไม่มีข้อมูลสถิติการเข้าแถว</div>
                <?php else: ?>
                    <!-- ในทางปฏิบัติจริง จะใช้ JavaScript สร้างกราฟ -->
                    <canvas id="departmentChart" width="400" height="250"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บนักเรียนขาดแถวบ่อย -->
<div id="frequently-absent-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">cancel</span>
            นักเรียนที่ขาดแถวบ่อย
            <span class="subtitle">แสดงนักเรียนที่ขาดแถว 5 วันขึ้นไป</span>
        </div>
        
        <div class="table-responsive">
        <table class="data-table" id="frequently-absent-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="12%">อัตราการเข้าแถว</th>
                        <th width="8%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="13%">การขาดแถวล่าสุด</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['frequently_absent'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียนที่ขาดแถวบ่อย</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['frequently_absent'] as $student): ?>
                            <tr data-id="<?php echo $student['id']; ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"><?php echo $student['initial']; ?></div>
                                        <div class="student-details">
                                            <div class="student-name"><?php echo $student['name']; ?></div>
                                            <div class="student-class">รหัส <?php echo $student['student_code']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['class']; ?></td>
                                <td>
                                    <?php
                                        $status_class = 'success';
                                        if ($student['attendance_rate'] < $data['risk_settings']['high']) {
                                            $status_class = 'danger';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['medium']) {
                                            $status_class = 'warning';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['low']) {
                                            $status_class = 'primary';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $student['attendance_rate']; ?>%</span>
                                </td>
                                <td><?php echo $student['days_missed']; ?> วัน</td>
                                <td><?php echo $student['advisor']; ?></td>
                                <td><?php echo $student['last_absence_date']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(<?php echo $student['id']; ?>)">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>', '<?php echo $student['class']; ?>', <?php echo $student['attendance_rate']; ?>, <?php echo $student['days_present']; ?>, <?php echo $student['days_missed']; ?>, <?php echo $student['total_days']; ?>, '<?php echo htmlspecialchars($student['advisor']); ?>', '<?php echo htmlspecialchars($student['advisor_phone']); ?>')">
                                            <span class="material-icons">send</span>
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

<!-- เนื้อหาแท็บนักเรียนรอการแจ้งเตือน -->
<div id="pending-notification-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">notifications_active</span>
            นักเรียนรอการแจ้งเตือน
            <span class="subtitle">นักเรียนที่ยังไม่ได้ส่งการแจ้งเตือน</span>
        </div>
        
        <div class="table-responsive">
        <table class="data-table" id="pending-notification-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="12%">อัตราการเข้าแถว</th>
                        <th width="8%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="13%">ความเร่งด่วน</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['pending_notification'])): ?>
                        <tr>
                            <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียนที่รอการแจ้งเตือน</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data['pending_notification'] as $student): ?>
                            <tr data-id="<?php echo $student['id']; ?>">
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar"><?php echo $student['initial']; ?></div>
                                        <div class="student-details">
                                            <div class="student-name"><?php echo $student['name']; ?></div>
                                            <div class="student-class">รหัส <?php echo $student['student_code']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $student['class']; ?></td>
                                <td>
                                    <?php
                                        $status_class = 'success';
                                        if ($student['attendance_rate'] < $data['risk_settings']['high']) {
                                            $status_class = 'danger';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['medium']) {
                                            $status_class = 'warning';
                                        } elseif ($student['attendance_rate'] < $data['risk_settings']['low']) {
                                            $status_class = 'primary';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo $student['attendance_rate']; ?>%</span>
                                </td>
                                <td><?php echo $student['days_missed']; ?> วัน</td>
                                <td><?php echo $student['advisor']; ?></td>
                                <td>
                                    <?php
                                        $urgency_class = 'primary';
                                        if ($student['urgency'] == 'สูง') {
                                            $urgency_class = 'danger';
                                        } elseif ($student['urgency'] == 'ปานกลาง') {
                                            $urgency_class = 'warning';
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $urgency_class; ?>"><?php echo $student['urgency']; ?></span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(<?php echo $student['id']; ?>)">
                                            <span class="material-icons">visibility</span>
                                        </button>
                                        <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>', '<?php echo $student['class']; ?>', <?php echo $student['attendance_rate']; ?>, <?php echo $student['days_present']; ?>, <?php echo $student['days_missed']; ?>, <?php echo $student['total_days']; ?>, '<?php echo htmlspecialchars($student['advisor']); ?>', '<?php echo htmlspecialchars($student['advisor_phone']); ?>')">
                                            <span class="material-icons">send</span>
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

<!-- โมดัลแสดงรายละเอียดนักเรียน -->
<div class="modal" id="studentDetailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('studentDetailModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="studentDetailTitle">ข้อมูลนักเรียน</h2>
        
        <div class="student-profile" id="studentProfileContainer">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>กำลังโหลดข้อมูล...</p>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('studentDetailModal')">ปิด</button>
            <button class="btn btn-primary" id="sendMessageButton">
                <span class="material-icons">send</span>
                ส่งข้อความแจ้งเตือน
            </button>
        </div>
    </div>
</div>

<!-- โมดัลส่งข้อความแจ้งเตือนกลุ่ม -->
<div class="modal" id="bulkNotificationModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('bulkNotificationModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ส่งข้อความแจ้งเตือนกลุ่ม</h2>
        
        <div class="form-group">
            <label class="form-label">เลือกเทมเพลตข้อความ</label>
            <select class="form-control" id="bulkTemplateSelect">
                <option value="">-- เลือกเทมเพลต --</option>
                <?php foreach ($data['message_templates'] as $template): ?>
                    <?php if ($template['type'] == 'group'): ?>
                        <option value="<?php echo $template['id']; ?>" data-content="<?php echo htmlspecialchars($template['content']); ?>"><?php echo $template['name']; ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">ข้อความ</label>
            <textarea class="message-textarea" id="bulkMessageText" rows="10"></textarea>
        </div>
        
        <div class="recipients-summary">
            <p>จำนวนนักเรียนที่จะได้รับข้อความ: <strong id="bulkRecipientCount"><?php echo $data['at_risk_count']; ?> คน</strong></p>
            <p id="bulkClassLevels">ระดับชั้น: <strong><?php echo $data['filters']['class_level'] ? $data['filters']['class_level'] : 'ทุกระดับชั้น'; ?></strong></p>
            <p id="bulkDepartment">แผนก: <strong>
                <?php 
                    if ($data['filters']['department_id']) {
                        foreach ($data['departments'] as $dept) {
                            if ($dept['department_id'] == $data['filters']['department_id']) {
                                echo $dept['department_name'];
                                break;
                            }
                        }
                    } else {
                        echo 'ทุกแผนก';
                    }
                ?>
            </strong></p>
            <p>สถานะ: <strong>เสี่ยงตกกิจกรรม</strong></p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('bulkNotificationModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="sendBulkNotification()">
                <span class="material-icons">send</span>
                ส่งข้อความ (<span id="bulkButtonCount"><?php echo $data['at_risk_count']; ?></span> ราย)
            </button>
        </div>
    </div>
</div>

<!-- โมดัลส่งข้อความแจ้งเตือนรายบุคคล -->
<div class="modal" id="sendMessageModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('sendMessageModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="sendMessageTitle">ส่งข้อความแจ้งเตือน</h2>
        
        <div class="template-buttons">
            <button class="template-btn active" onclick="selectModalTemplate('regular')">ข้อความปกติ</button>
            <button class="template-btn" onclick="selectModalTemplate('warning')">แจ้งเตือนความเสี่ยง</button>
            <button class="template-btn" onclick="selectModalTemplate('critical')">แจ้งเตือนฉุกเฉิน</button>
            <button class="template-btn" onclick="selectModalTemplate('summary')">รายงานสรุป</button>
        </div>
        
        <div class="form-group">
            <label class="form-label">ข้อความ</label>
            <textarea class="message-textarea" id="modalMessageText" rows="10"></textarea>
            <input type="hidden" id="studentIdField" value="">
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('sendMessageModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="sendIndividualMessage()">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<script>
// สร้างกราฟแผนกในกรณีที่มีข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($data['attendance_by_department']) && count($data['attendance_by_department']) > 0): ?>
    if (typeof Chart !== 'undefined' && document.getElementById('departmentChart')) {
        const departmentData = {
            labels: [<?php echo "'" . implode("', '", array_keys($data['attendance_by_department'])) . "'"; ?>],
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: [<?php echo implode(", ", array_values($data['attendance_by_department'])); ?>],
                backgroundColor: [
                    <?php 
                    foreach ($data['attendance_by_department'] as $rate) {
                        if ($rate < $data['risk_settings']['high']) {
                            echo "'rgba(220, 53, 69, 0.7)', ";
                        } elseif ($rate < $data['risk_settings']['medium']) {
                            echo "'rgba(255, 193, 7, 0.7)', ";
                        } elseif ($rate < $data['risk_settings']['low']) {
                            echo "'rgba(23, 162, 184, 0.7)', ";
                        } else {
                            echo "'rgba(40, 167, 69, 0.7)', ";
                        }
                    }
                    ?>
                ],
                borderWidth: 1
            }]
        };
        
        new Chart(document.getElementById('departmentChart'), {
            type: 'bar',
            data: departmentData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<style>
/* เพิ่มสไตล์เฉพาะสำหรับหน้านี้ */
.filter-container {
    padding: 15px 20px;
    background-color: var(--bg-light);
    margin-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.filter-row:last-child {
    margin-bottom: 0;
}

.filter-range {
    flex: 2;
}

.range-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
}

.range-separator {
    font-weight: bold;
}

.filter-buttons {
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.reset-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px 15px;
    background-color: var(--secondary-color);
    color: white;
    border: none;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    height: 38px;
}

.reset-button:hover {
    background-color: #5a6268;
}

.reset-button .material-icons {
    margin-right: 5px;
    font-size: 18px;
}

.charts-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.charts-container .card {
    flex: 1;
}

@media (max-width: 991px) {
    .charts-container {
        flex-direction: column;
    }
}
</style>