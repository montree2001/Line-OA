<!-- แท็บสำหรับรูปแบบการส่งข้อความ -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="individual">ส่งรายบุคคล</div>
        <div class="tab" data-tab="group">ส่งกลุ่ม</div>
        <div class="tab" data-tab="templates">จัดการเทมเพลต</div>
    </div>
</div>

<!-- เนื้อหาแท็บส่งรายบุคคล -->
<div id="individual-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">search</span>
            ค้นหานักเรียน
        </div>
        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ชื่อ-นามสกุลนักเรียน</div>
                <input type="text" class="form-control" placeholder="ป้อนชื่อนักเรียน..." name="student_name">
            </div>
            <div class="filter-group">
                <div class="filter-label">ระดับชั้น</div>
                <select class="form-control" name="class_level">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option>ปวช.1</option>
                    <option>ปวช.2</option>
                    <option>ปวช.3</option>
                    <option>ปวส.1</option>
                    <option>ปวส.2</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">กลุ่ม</div>
                <select class="form-control" name="class_group">
                    <option value="">-- ทุกกลุ่ม --</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">สถานะการเข้าแถว</div>
                <select class="form-control" name="risk_status">
                    <option value="">-- ทุกสถานะ --</option>
                    <option>เสี่ยงตกกิจกรรม</option>
                    <option>ต้องระวัง</option>
                    <option>ปกติ</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>

        <div class="filter-result-count">พบนักเรียนทั้งหมด <?php echo count($students); ?> คน</div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/กลุ่ม</th>
                        <th width="15%">เข้าแถว</th>
                        <th width="15%">สถานะ</th>
                        <th width="15%">ผู้ปกครอง</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <tr data-student-id="<?php echo $student['student_id']; ?>">
                        <td>
                            <input type="radio" name="student_select" value="<?php echo $student['student_id']; ?>" <?php echo ($index === 0) ? 'checked' : ''; ?>>
                        </td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar"><?php echo mb_substr($student['first_name'], 0, 1, 'UTF-8'); ?></div>
                                <div class="student-details">
                                    <div class="student-name"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                    <div class="student-class">เลขที่ <?php echo $student['class_number'] ?? '-'; ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $student['class']; ?></td>
                        <td><?php echo $student['attendance_days']; ?></td>
                        <td><span class="status-badge <?php echo $student['status_class']; ?>"><?php echo $student['status']; ?></span></td>
                        <td><?php echo $student['parents_info'] ?? '-'; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูประวัติการส่ง" onclick="showHistory()">
                                    <span class="material-icons">history</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <span class="material-icons">message</span>
            ส่งข้อความถึงผู้ปกครอง - <span class="student-name-display"><?php echo $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name']; ?></span> (<span class="student-class-display"><?php echo $students[0]['class']; ?></span>)
        </div>

        <div class="date-range-selector">
            <div class="date-range-title">
                <span class="material-icons">date_range</span>
                ช่วงวันที่การเข้าแถวที่ต้องการรายงาน
            </div>
            <div class="date-range-inputs">
                <div class="date-group">
                    <label>วันที่เริ่มต้น</label>
                    <input type="date" id="start-date" class="date-picker">
                </div>
                <div class="date-group">
                    <label>วันที่สิ้นสุด</label>
                    <input type="date" id="end-date" class="date-picker">
                </div>
            </div>
        </div>

        <div class="template-buttons">
            <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
            <button class="template-btn" data-template="warning">แจ้งเตือนความเสี่ยง</button>
            <button class="template-btn" data-template="critical">แจ้งเตือนฉุกเฉิน</button>
            <button class="template-btn" data-template="summary">รายงานสรุป</button>
            <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
        </div>

        <div class="message-form">
            <textarea class="message-textarea" id="messageText" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ผู้ปกครองของ <?php echo $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name']; ?>

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน <?php echo $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name']; ?> นักเรียนชั้น <?php echo $students[0]['class']; ?> ปัจจุบันเข้าร่วม <?php echo $students[0]['attendance_days']; ?>

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท</textarea>

            <div class="send-options">
                <div class="option-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="include-chart" checked>
                        <span class="checkmark"></span>
                        แนบกราฟสรุปการเข้าแถว
                    </label>
                </div>
                <div class="option-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="include-link" checked>
                        <span class="checkmark"></span>
                        แนบลิงก์ดูข้อมูลโดยละเอียด
                    </label>
                </div>
            </div>

            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="preview-button" onclick="showPreview()">
                        <span class="material-icons">visibility</span>
                        แสดงตัวอย่าง
                    </button>
                </div>
                <div class="preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p style="margin-top: 10px;">เรียน ผู้ปกครองของ <?php echo $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name']; ?><br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน <?php echo $students[0]['title'] . ' ' . $students[0]['first_name'] . ' ' . $students[0]['last_name']; ?> นักเรียนชั้น <?php echo $students[0]['class']; ?> ปัจจุบันเข้าร่วม <?php echo $students[0]['attendance_days']; ?><br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                    
                    <div class="chart-preview">
                        <canvas id="attendance-chart" width="400" height="200"></canvas>
                    </div>
                    
                    <div class="link-preview">
                        <a href="#" class="detail-link">
                            <span class="material-icons">open_in_new</span>
                            ดูข้อมูลโดยละเอียด
                        </a>
                    </div>
                </div>
            </div>

            <div class="message-cost">
                <div class="cost-title">ค่าใช้จ่ายในการส่ง</div>
                <div class="cost-details">
                    <div class="cost-item">
                        <span class="cost-label">ข้อความ:</span>
                        <span class="cost-value">0.075 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">รูปภาพกราฟ:</span>
                        <span class="cost-value">0.150 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">ลิงก์:</span>
                        <span class="cost-value">0.075 บาท</span>
                    </div>
                    <div class="cost-item total">
                        <span class="cost-label">รวม:</span>
                        <span class="cost-value">0.300 บาท</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="sendMessage()">
                    <span class="material-icons">send</span>
                    ส่งข้อความ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บส่งกลุ่ม -->
<div id="group-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">filter_list</span>
            ตัวกรองนักเรียนสำหรับส่งข้อความกลุ่ม
        </div>
        
        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ระดับชั้น</div>
                <select class="form-control" name="group_class_level">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option>ปวช.1</option>
                    <option>ปวช.2</option>
                    <option>ปวช.3</option>
                    <option>ปวส.1</option>
                    <option>ปวส.2</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">กลุ่ม</div>
                <select class="form-control" name="group_class_group">
                    <option value="">-- ทุกกลุ่ม --</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">สถานะการเข้าแถว</div>
                <select class="form-control" name="group_risk_status">
                    <option value="">-- ทุกสถานะ --</option>
                    <option selected>เสี่ยงตกกิจกรรม</option>
                    <option>ต้องระวัง</option>
                    <option>ปกติ</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">อัตราการเข้าแถว</div>
                <select class="form-control" name="group_attendance_rate">
                    <option value="">-- ทั้งหมด --</option>
                    <option selected>น้อยกว่า 70%</option>
                    <option>70% - 80%</option>
                    <option>80% - 90%</option>
                    <option>มากกว่า 90%</option>
                </select>
            </div>
            <button class="filter-button" onclick="applyGroupFilters()">
                <span class="material-icons">filter_list</span>
                กรองข้อมูล
            </button>
        </div>
        
        <div class="filter-group-result">พบนักเรียนที่ตรงตามเงื่อนไข <?php echo count($at_risk_students); ?> คน</div>
        
        <div class="recipients-container">
            <?php foreach ($at_risk_students as $student): ?>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" value="<?php echo $student['student_id']; ?>" checked>
                    <div class="recipient-details">
                        <div class="student-name"><?php echo $student['title'] . ' ' . $student['first_name'] . ' ' . $student['last_name']; ?> (<?php echo $student['class']; ?>)</div>
                        <div class="parent-info">ผู้ปกครอง: <?php echo $student['parents_info'] ?? '-'; ?></div>
                    </div>
                </div>
                <span class="status-badge <?php echo $student['status_class']; ?>"><?php echo round($student['attendance_rate']); ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="batch-actions">
            <button class="btn btn-secondary" onclick="selectAllRecipients()">เลือกทั้งหมด</button>
            <button class="btn btn-secondary" onclick="clearAllRecipients()">ยกเลิกเลือกทั้งหมด</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">
            <span class="material-icons">send</span>
            ส่งข้อความถึงผู้ปกครองกลุ่ม (<span class="recipient-count"><?php echo count($at_risk_students); ?></span> คน)
        </div>

        <div class="date-range-selector">
            <div class="date-range-title">
                <span class="material-icons">date_range</span>
                ช่วงวันที่การเข้าแถวที่ต้องการรายงาน
            </div>
            <div class="date-range-inputs">
                <div class="date-group">
                    <label>วันที่เริ่มต้น</label>
                    <input type="date" id="start-date-group" class="date-picker">
                </div>
                <div class="date-group">
                    <label>วันที่สิ้นสุด</label>
                    <input type="date" id="end-date-group" class="date-picker">
                </div>
            </div>
        </div>

        <div class="template-buttons">
            <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
            <button class="template-btn" data-template="risk-warning">แจ้งเตือนกลุ่มเสี่ยง</button>
            <button class="template-btn" data-template="meeting">นัดประชุมผู้ปกครอง</button>
            <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
        </div>

        <div class="message-form">
            <textarea class="message-textarea" id="groupMessageText" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ท่านผู้ปกครองนักเรียนชั้น <?php echo $at_risk_students[0]['class'] ?? 'ปวช.1/1'; ?>

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา

กรุณาติดต่อครูที่ปรึกษาประจำชั้น <?php echo $at_risk_students[0]['class'] ?? 'ปวช.1/1'; ?> ครูอิศรา สุขใจ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท</textarea>

            <div class="send-options">
                <div class="option-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="include-chart-group" checked>
                        <span class="checkmark"></span>
                        แนบกราฟสรุปการเข้าแถวแยกรายบุคคล
                    </label>
                </div>
                <div class="option-group">
                    <label class="checkbox-container">
                        <input type="checkbox" id="include-link-group" checked>
                        <span class="checkmark"></span>
                        แนบลิงก์ดูข้อมูลโดยละเอียด
                    </label>
                </div>
            </div>

            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="preview-button" onclick="showGroupPreview()">
                        <span class="material-icons">visibility</span>
                        แสดงตัวอย่าง
                    </button>
                </div>
                <div class="preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p style="margin-top: 10px;">เรียน ท่านผู้ปกครองนักเรียนชั้น <?php echo $at_risk_students[0]['class'] ?? 'ปวช.1/1'; ?><br><br>ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด<br><br>โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา<br><br>กรุณาติดต่อครูที่ปรึกษาประจำชั้น <?php echo $at_risk_students[0]['class'] ?? 'ปวช.1/1'; ?> ครูอิศรา สุขใจ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                    <div class="chart-preview">
                        <div class="group-chart-note">* แต่ละนักเรียนจะได้รับกราฟข้อมูลการเข้าแถวเฉพาะของตนเอง</div>
                        <canvas id="group-attendance-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="message-cost">
                <div class="cost-title">ประมาณการค่าใช้จ่ายในการส่ง</div>
                <div class="cost-details">
                    <div class="cost-item">
                        <span class="cost-label">ข้อความ (<?php echo count($at_risk_students); ?> คน):</span>
                        <span class="cost-value"><?php echo number_format(0.075 * count($at_risk_students), 2); ?> บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">รูปภาพกราฟ (<?php echo count($at_risk_students); ?> รูป):</span>
                        <span class="cost-value"><?php echo number_format(0.15 * count($at_risk_students), 2); ?> บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">ลิงก์ (<?php echo count($at_risk_students); ?> ลิงก์):</span>
                        <span class="cost-value"><?php echo number_format(0.075 * count($at_risk_students), 2); ?> บาท</span>
                    </div>
                    <div class="cost-item total">
                        <span class="cost-label">รวม:</span>
                        <span class="cost-value"><?php echo number_format(0.3 * count($at_risk_students), 2); ?> บาท</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" onclick="resetGroupForm()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="sendGroupMessage()">
                    <span class="material-icons">send</span>
                    ส่งข้อความ (<?php echo count($at_risk_students); ?> ราย)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บจัดการเทมเพลต -->
<div id="templates-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">description</span>
            จัดการเทมเพลตข้อความแจ้งเตือน
        </div>

        <div class="form-actions" style="justify-content: flex-start; margin-bottom: 20px;">
            <button class="btn btn-primary" onclick="createNewTemplate()">
                <span class="material-icons">add</span>
                สร้างเทมเพลตใหม่
            </button>
        </div>

        <div class="template-categories">
            <button class="category-btn active" data-category="all">ทั้งหมด</button>
            <button class="category-btn" data-category="attendance">การเข้าแถว</button>
            <button class="category-btn" data-category="meeting">การประชุม</button>
            <button class="category-btn" data-category="activity">กิจกรรม</button>
            <button class="category-btn" data-category="other">อื่นๆ</button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="30%">ชื่อเทมเพลต</th>
                        <th width="15%">ประเภท</th>
                        <th width="15%">หมวดหมู่</th>
                        <th width="10%">สร้างเมื่อ</th>
                        <th width="10%">ใช้งานล่าสุด</th>
                        <th width="10%">สถานะ</th>
                        <th width="10%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?php echo $template['name']; ?></td>
                        <td><?php echo $template['type']; ?></td>
                        <td><?php echo $template['category'] ?? 'การเข้าแถว'; ?></td>
                        <td><?php echo $template['created_at']; ?></td>
                        <td><?php echo $template['last_used']; ?></td>
                        <td><span class="status-badge <?php echo ($template['status'] == 'ใช้งาน') ? 'success' : 'warning'; ?>"><?php echo $template['status']; ?></span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข" onclick="editTemplate(<?php echo $template['id']; ?>)">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง" onclick="previewTemplate(<?php echo $template['id']; ?>)">
                                    <span class="material-icons">visibility</span>
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

<!-- โมดัลสร้างเทมเพลตใหม่ -->
<div class="modal" id="templateModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('templateModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">สร้างเทมเพลตข้อความใหม่</h2>
        
        <div class="form-group">
            <label class="form-label">ชื่อเทมเพลต</label>
            <input type="text" class="form-control" placeholder="กรุณากรอกชื่อเทมเพลต">
        </div>
        
        <div class="form-group">
            <label class="form-label">ประเภท</label>
            <select class="form-control">
                <option value="individual">รายบุคคล</option>
                <option value="group">กลุ่ม</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">หมวดหมู่</label>
            <select class="form-control">
                <option value="attendance">การเข้าแถว</option>
                <option value="meeting">การประชุม</option>
                <option value="activity">กิจกรรม</option>
                <option value="other">อื่นๆ</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">เนื้อหาข้อความ</label>
            <textarea class="message-textarea" rows="10" placeholder="กรุณากรอกเนื้อหาข้อความเทมเพลต"></textarea>
            <p class="form-text">* คุณสามารถใช้ตัวแปรในข้อความได้ เช่น {{ชื่อนักเรียน}}, {{ชั้นเรียน}}, {{ร้อยละการเข้าแถว}}</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('templateModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="saveTemplate()">
                <span class="material-icons">save</span>
                บันทึกเทมเพลต
            </button>
        </div>
    </div>
</div>

<!-- โมดัลตัวอย่างข้อความ -->
<div class="modal" id="previewModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('previewModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ตัวอย่างข้อความที่จะส่ง</h2>
        
        <div class="form-group">
            <div class="preview-line-content">
                <strong>LINE Official Account: SADD-Prasat</strong>
                <div id="previewText" style="margin-top: 15px; line-height: 1.6;">
                    <!-- เนื้อหาข้อความจะถูกแทรกที่นี่ด้วย JavaScript -->
                </div>
                
                <div id="previewChart" style="margin-top: 20px; max-width: 100%;">
                    <canvas id="preview-attendance-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('previewModal')">ปิด</button>
        </div>
    </div>
</div>

<!-- โมดัลประวัติการส่งข้อความ -->
<div class="modal" id="historyModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('historyModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ประวัติการส่งข้อความ - <span class="history-student-name">นายธนกฤต สุขใจ</span></h2>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="20%">วันที่ส่ง</th>
                        <th width="20%">ประเภท</th>
                        <th width="20%">ผู้ส่ง</th>
                        <th width="15%">สถานะ</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>16/03/2568 08:45</td>
                        <td>แจ้งเตือนความเสี่ยง</td>
                        <td>จารุวรรณ บุญมี</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ" onclick="viewNotificationMessage(1)">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>10/03/2568 09:30</td>
                        <td>แจ้งเตือนปกติ</td>
                        <td>อิศรา สุขใจ</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ" onclick="viewNotificationMessage(2)">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>01/03/2568 14:15</td>
                        <td>แจ้งเตือนปกติ</td>
                        <td>จารุวรรณ บุญมี</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ" onclick="viewNotificationMessage(3)">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('historyModal')">ปิด</button>
        </div>
    </div>
</div>

<!-- โมดัลแสดงผลการส่งข้อความ -->
<div class="modal" id="resultModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('resultModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ผลลัพธ์การส่งข้อความ</h2>
        
        <div class="result-summary">
            <div class="result-item success">
                <span class="material-icons">check_circle</span>
                <div class="result-value">0</div>
                <div class="result-label">สำเร็จ</div>
            </div>
            <div class="result-item error">
                <span class="material-icons">error</span>
                <div class="result-value">0</div>
                <div class="result-label">ล้มเหลว</div>
            </div>
            <div class="result-item cost">
                <span class="material-icons">payments</span>
                <div class="result-value">0.00 บาท</div>
                <div class="result-label">ค่าใช้จ่าย</div>
            </div>
        </div>
        
        <div class="result-details">
            <!-- รายละเอียดการส่งข้อความจะถูกแทรกที่นี่ด้วย JavaScript -->
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="closeModal('resultModal')">ตกลง</button>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันสำหรับจัดการแท็บ
document.addEventListener('DOMContentLoaded', function() {
    // สร้างกราฟตัวอย่าง
    const chartCtx = document.getElementById('attendance-chart').getContext('2d');
    new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: ['1 เม.ย.', '8 เม.ย.', '15 เม.ย.', '22 เม.ย.', '29 เม.ย.'],
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: [65, 62, 68, 65, 70],
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // สร้างกราฟตัวอย่างสำหรับกลุ่ม
    const groupChartCtx = document.getElementById('group-attendance-chart').getContext('2d');
    new Chart(groupChartCtx, {
        type: 'bar',
        data: {
            labels: ['1-7 เม.ย.', '8-14 เม.ย.', '15-21 เม.ย.', '22-28 เม.ย.', '29-30 เม.ย.'],
            datasets: [{
                label: 'อัตราการเข้าแถวเฉลี่ย (%)',
                data: [63, 65, 66, 68, 69],
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgb(75, 192, 192)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // สร้างกราฟตัวอย่างสำหรับ preview
    const previewChartCtx = document.getElementById('preview-attendance-chart').getContext('2d');
    new Chart(previewChartCtx, {
        type: 'line',
        data: {
            labels: ['1 เม.ย.', '8 เม.ย.', '15 เม.ย.', '22 เม.ย.', '29 เม.ย.'],
            datasets: [{
                label: 'อัตราการเข้าแถว (%)',
                data: [65, 62, 68, 65, 70],
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
});

// ตั้งค่า event listeners สำหรับแท็บ
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        showTab(tabId);
    });
});

// ตั้งค่า event listeners สำหรับหมวดหมู่เทมเพลต
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        filterTemplatesByCategory(this.getAttribute('data-category'));
    });
});

// ฟังก์ชันกรองเทมเพลตตามหมวดหมู่
function filterTemplatesByCategory(category) {
    const templateRows = document.querySelectorAll('#templates-tab .data-table tbody tr');
    templateRows.forEach(row => {
        const categoryCell = row.querySelector('td:nth-child(3)');
        if (category === 'all' || categoryCell.textContent.toLowerCase() === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// ฟังก์ชันแก้ไขเทมเพลต
function editTemplate(templateId) {
    // ในทางปฏิบัติจริง ควรดึงข้อมูลเทมเพลตจากฐานข้อมูล
    // สำหรับตัวอย่าง ใช้ข้อมูลตัวอย่าง
    const templateModal = document.getElementById('templateModal');
    const titleInput = templateModal.querySelector('input.form-control');
    const typeSelect = templateModal.querySelector('select:nth-of-type(1)');
    const categorySelect = templateModal.querySelector('select:nth-of-type(2)');
    const contentTextarea = templateModal.querySelector('textarea');
    
    // ตั้งค่าข้อมูลตัวอย่าง
    titleInput.value = 'แจ้งเตือนความเสี่ยงรายบุคคล';
    typeSelect.value = 'individual';
    categorySelect.value = 'attendance';
    contentTextarea.value = 'เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}\n\nทางโรงเรียนขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน ({{ร้อยละการเข้าแถว}}%)\n\nกรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
    
    // เปลี่ยนชื่อปุ่มบันทึก
    const saveButton = templateModal.querySelector('.btn-primary');
    saveButton.innerHTML = '<span class="material-icons">save</span> อัปเดตเทมเพลต';
    
    // เปลี่ยนหัวข้อโมดัล
    templateModal.querySelector('.modal-title').textContent = 'แก้ไขเทมเพลตข้อความ';
    
    showModal('templateModal');
}

// ฟังก์ชันดูตัวอย่างเทมเพลต
function previewTemplate(templateId) {
    // ในทางปฏิบัติจริง ควรดึงข้อมูลเทมเพลตจากฐานข้อมูล
    // สำหรับตัวอย่าง ใช้ข้อมูลตัวอย่าง
    const previewText = document.getElementById('previewText');
    
    const templateContent = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
    
    if (previewText) {
        previewText.innerHTML = templateContent.replace(/\n/g, '<br>');
    }
    
    // แสดงกราฟในตัวอย่าง
    document.getElementById('previewChart').style.display = 'block';
    
    showModal('previewModal');
}

// ฟังก์ชันบันทึกเทมเพลตใหม่
function saveTemplate() {
    const templateModal = document.getElementById('templateModal');
    const titleInput = templateModal.querySelector('input.form-control');
    const typeSelect = templateModal.querySelector('select:nth-of-type(1)');
    const categorySelect = templateModal.querySelector('select:nth-of-type(2)');
    const contentTextarea = templateModal.querySelector('textarea');
    
    // ตรวจสอบข้อมูล
    if (!titleInput.value.trim()) {
        alert('กรุณากรอกชื่อเทมเพลต');
        return;
    }
    
    if (!contentTextarea.value.trim()) {
        alert('กรุณากรอกเนื้อหาข้อความเทมเพลต');
        return;
    }
    
    // ในทางปฏิบัติจริง ควรส่งข้อมูลไปบันทึกในฐานข้อมูล
    // สำหรับตัวอย่าง แสดงข้อความสำเร็จและปิดโมดัล
    
    closeModal('templateModal');
    
    // แสดงข้อความแจ้งเตือน
    alert('บันทึกเทมเพลตเรียบร้อยแล้ว');
    
    // รีเซ็ตฟอร์ม
    titleInput.value = '';
    typeSelect.selectedIndex = 0;
    categorySelect.selectedIndex = 0;
    contentTextarea.value = '';
}

// ฟังก์ชันอัปเดตค่าใช้จ่ายในการส่งข้อความ
function updateMessageCost() {
    const includeChart = document.getElementById('include-chart').checked;
    const includeLink = document.getElementById('include-link').checked;
    
    const messageCost = 0.075; // บาทต่อข้อความ
    const chartCost = 0.15; // บาทต่อรูปภาพ
    const linkCost = 0.075; // บาทต่อลิงก์
    
    let totalCost = messageCost;
    
    if (includeChart) {
        totalCost += chartCost;
    }
    
    if (includeLink) {
        totalCost += linkCost;
    }
    
    // อัปเดตการแสดงผล
    const costValueElements = document.querySelectorAll('#individual-tab .message-cost .cost-item.total .cost-value');
    costValueElements.forEach(element => {
        element.textContent = totalCost.toFixed(2) + ' บาท';
    });
}

// ฟังก์ชันอัปเดตค่าใช้จ่ายในการส่งข้อความกลุ่ม
function updateGroupMessageCost() {
    const includeChart = document.getElementById('include-chart-group').checked;
    const includeLink = document.getElementById('include-link-group').checked;
    
    const messageCost = 0.075; // บาทต่อข้อความ
    const chartCost = 0.15; // บาทต่อรูปภาพ
    const linkCost = 0.075; // บาทต่อลิงก์
    
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    
    let costPerRecipient = messageCost;
    
    if (includeChart) {
        costPerRecipient += chartCost;
    }
    
    if (includeLink) {
        costPerRecipient += linkCost;
    }
    
    const totalCost = costPerRecipient * selectedCount;
    
    // อัปเดตการแสดงผล
    const costValueElements = document.querySelectorAll('#group-tab .message-cost .cost-item.total .cost-value');
    costValueElements.forEach(element => {
        element.textContent = totalCost.toFixed(2) + ' บาท';
    });
    
    // อัปเดตรายละเอียดค่าใช้จ่าย
    document.querySelector('#group-tab .cost-item:nth-child(1) .cost-label').textContent = `ข้อความ (${selectedCount} คน):`;
    document.querySelector('#group-tab .cost-item:nth-child(1) .cost-value').textContent = (messageCost * selectedCount).toFixed(2) + ' บาท';
    
    document.querySelector('#group-tab .cost-item:nth-child(2) .cost-label').textContent = `รูปภาพกราฟ (${includeChart ? selectedCount : 0} รูป):`;
    document.querySelector('#group-tab .cost-item:nth-child(2) .cost-value').textContent = (chartCost * (includeChart ? selectedCount : 0)).toFixed(2) + ' บาท';
    
    document.querySelector('#group-tab .cost-item:nth-child(3) .cost-label').textContent = `ลิงก์ (${includeLink ? selectedCount : 0} ลิงก์):`;
    document.querySelector('#group-tab .cost-item:nth-child(3) .cost-value').textContent = (linkCost * (includeLink ? selectedCount : 0)).toFixed(2) + ' บาท';
}

// ตั้งค่า event listeners สำหรับปุ่มในหน้า
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า event listeners สำหรับการเลือกนักเรียน
    document.querySelectorAll('input[name="student_select"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                updateSelectedStudent(this.closest('tr'));
            }
        });
    });
    
    // ตั้งค่า event listeners สำหรับการเปลี่ยนแปลงค่าใช้จ่าย
    const chartCheckbox = document.getElementById('include-chart');
    const linkCheckbox = document.getElementById('include-link');
    
    if (chartCheckbox) {
        chartCheckbox.addEventListener('change', updateMessageCost);
    }
    
    if (linkCheckbox) {
        linkCheckbox.addEventListener('change', updateMessageCost);
    }
    
    // ตั้งค่า event listeners สำหรับการเปลี่ยนแปลงค่าใช้จ่ายกลุ่ม
    const groupChartCheckbox = document.getElementById('include-chart-group');
    const groupLinkCheckbox = document.getElementById('include-link-group');
    
    if (groupChartCheckbox) {
        groupChartCheckbox.addEventListener('change', updateGroupMessageCost);
    }
    
    if (groupLinkCheckbox) {
        groupLinkCheckbox.addEventListener('change', updateGroupMessageCost);
    }
    
    // ตั้งค่า event listeners สำหรับการเลือกผู้รับกลุ่ม
    document.querySelectorAll('.recipients-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRecipientCount();
            updateGroupMessageCost();
        });
    });
    
    // กำหนดค่าเริ่มต้นสำหรับ date pickers
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    const startDateGroup = document.getElementById('start-date-group');
    const endDateGroup = document.getElementById('end-date-group');
    
    if (startDate && endDate) {
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        startDate.valueAsDate = firstDayOfMonth;
        endDate.valueAsDate = today;
    }
    
    if (startDateGroup && endDateGroup) {
        const today = new Date();
        const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        
        startDateGroup.valueAsDate = firstDayOfMonth;
        endDateGroup.valueAsDate = today;
    }
});

// ฟังก์ชันอัปเดตข้อมูลนักเรียนที่เลือก
function updateSelectedStudent(studentRow) {
    const studentName = studentRow.querySelector('.student-name').textContent;
    const studentClass = studentRow.querySelector('td:nth-child(3)').textContent;
    const attendanceDays = studentRow.querySelector('td:nth-child(4)').textContent;
    
    // อัปเดตชื่อนักเรียนในส่วนหัวของฟอร์มส่งข้อความ
    const studentNameDisplay = document.querySelector('.student-name-display');
    const studentClassDisplay = document.querySelector('.student-class-display');
    
    if (studentNameDisplay) {
        studentNameDisplay.textContent = studentName;
    }
    
    if (studentClassDisplay) {
        studentClassDisplay.textContent = studentClass;
    }
    
    // อัปเดตข้อความตามเทมเพลตที่เลือก
    const activeTemplateBtn = document.querySelector('#individual-tab .template-btn.active');
    if (activeTemplateBtn) {
        const templateType = activeTemplateBtn.getAttribute('data-template') || activeTemplateBtn.textContent.trim().toLowerCase();
        selectTemplate(templateType);
    }
}

// ฟังก์ชันอัปเดตจำนวนผู้รับข้อความกลุ่ม
function updateRecipientCount() {
    const selectedCount = document.querySelectorAll('.recipients-container input[type="checkbox"]:checked').length;
    
    // อัปเดตจำนวนผู้รับในหัวข้อ
    const recipientCountElement = document.querySelector('.recipient-count');
    if (recipientCountElement) {
        recipientCountElement.textContent = selectedCount;
    }
    
    // อัปเดตปุ่มส่งข้อความ
    const sendButton = document.querySelector('#group-tab .form-actions .btn-primary');
    if (sendButton) {
        sendButton.innerHTML = `<span class="material-icons">send</span> ส่งข้อความ (${selectedCount} ราย)`;
    }
}

// ฟังก์ชันเลือกผู้รับทั้งหมด
function selectAllRecipients() {
    document.querySelectorAll('.recipients-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = true;
    });
    
    updateRecipientCount();
    updateGroupMessageCost();
}

// ฟังก์ชันยกเลิกการเลือกผู้รับทั้งหมด
function clearAllRecipients() {
    document.querySelectorAll('.recipients-container input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    updateRecipientCount();
    updateGroupMessageCost();
}

// ฟังก์ชันใช้ตัวกรองสำหรับแท็บกลุ่ม
function applyGroupFilters() {
    // ในทางปฏิบัติจริง ควรใช้ AJAX เพื่อดึงข้อมูลจากเซิร์ฟเวอร์
    // สำหรับตัวอย่าง แสดงการโหลด
    
    alert('กำลังโหลดข้อมูลนักเรียนตามเงื่อนไข...');
    
    // จำลองการดึงข้อมูล
    setTimeout(function() {
        alert('พบนักเรียนที่ตรงตามเงื่อนไข 8 คน');
    }, 500);
}
</script>