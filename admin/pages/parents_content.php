<!-- แท็บสำหรับการจัดการข้อมูลผู้ปกครอง -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="all-parents">ผู้ปกครองทั้งหมด</div>
        <div class="tab" data-tab="communication">การสื่อสาร</div>
        <div class="tab" data-tab="settings">ตั้งค่า</div>
    </div>
</div>

<!-- เนื้อหาแท็บผู้ปกครองทั้งหมด -->
<div id="all-parents-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">search</span>
            ค้นหาผู้ปกครอง
        </div>
        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ชื่อ-นามสกุลผู้ปกครอง</div>
                <input type="text" class="form-control" placeholder="ป้อนชื่อผู้ปกครอง...">
            </div>
            <div class="filter-group">
                <div class="filter-label">ชื่อนักเรียน</div>
                <input type="text" class="form-control" placeholder="ป้อนชื่อนักเรียน...">
            </div>
            <div class="filter-group">
                <div class="filter-label">ระดับชั้น</div>
                <select class="form-control">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option>ม.1</option>
                    <option>ม.2</option>
                    <option>ม.3</option>
                    <option>ม.4</option>
                    <option>ม.5</option>
                    <option>ม.6</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">ห้องเรียน</div>
                <select class="form-control">
                    <option value="">-- ทุกห้อง --</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="20%">ชื่อ-นามสกุล</th>
                        <th width="10%">ความเกี่ยวข้อง</th>
                        <th width="15%">เบอร์โทรศัพท์</th>
                        <th width="15%">ช่องทางติดต่อ</th>
                        <th width="20%">นักเรียนในปกครอง</th>
                        <th width="10%">สถานะ</th>
                        <th width="10%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['parents'] as $parent): ?>
                        <tr>
                            <td>
                                <div class="parent-info">
                                    <div class="parent-avatar"><?php echo mb_substr($parent['name'], 0, 1, 'UTF-8'); ?></div>
                                    <div class="parent-details">
                                        <div class="parent-name"><?php echo $parent['name']; ?></div>
                                        <div class="parent-email"><?php echo $parent['email']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $parent['relation']; ?></td>
                            <td><?php echo $parent['phone']; ?></td>
                            <td>
                                <?php
                                $channels = explode(', ', $parent['notification_channels']);
                                foreach ($channels as $channel) {
                                    $badge_class = 'info';
                                    if ($channel == 'LINE') $badge_class = 'success';
                                    if ($channel == 'SMS') $badge_class = 'warning';
                                    if ($channel == 'Email') $badge_class = 'primary';
                                    echo '<span class="status-badge ' . $badge_class . '">' . $channel . '</span> ';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="student-info-mini">
                                    <div class="student-name"><?php echo $parent['student']; ?></div>
                                    <div class="student-class"><?php echo $parent['student_class']; ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge success"><?php echo $parent['status']; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="table-action-btn primary" title="แก้ไขข้อมูล" onclick="showEditParentModal(<?php echo $parent['id']; ?>)">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(<?php echo $parent['id']; ?>)">
                                        <span class="material-icons">send</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination-container">
            <div class="pagination">
                <a href="#" class="page-item">
                    <span class="material-icons">chevron_left</span>
                </a>
                <a href="#" class="page-item active">1</a>
                <a href="#" class="page-item">2</a>
                <a href="#" class="page-item">3</a>
                <span class="page-item">...</span>
                <a href="#" class="page-item">10</a>
                <a href="#" class="page-item">
                    <span class="material-icons">chevron_right</span>
                </a>
            </div>
            <div class="pagination-info">
                แสดง 1-5 จาก 52 รายการ
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บการสื่อสาร -->
<div id="communication-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">message</span>
            ประวัติการสื่อสารกับผู้ปกครอง
        </div>

        <div class="filter-container">
            <div class="filter-group">
                <div class="filter-label">ตั้งแต่วันที่</div>
                <input type="date" class="form-control" value="2025-03-01">
            </div>
            <div class="filter-group">
                <div class="filter-label">ถึงวันที่</div>
                <input type="date" class="form-control" value="2025-03-16">
            </div>
            <div class="filter-group">
                <div class="filter-label">ประเภทข้อความ</div>
                <select class="form-control">
                    <option value="">-- ทั้งหมด --</option>
                    <option>แจ้งเตือนทั่วไป</option>
                    <option>แจ้งเตือนความเสี่ยง</option>
                    <option>แจ้งเตือนฉุกเฉิน</option>
                    <option>รายงานสรุป</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>วันที่ส่ง</th>
                        <th>ประเภท</th>
                        <th>ผู้ปกครอง</th>
                        <th>นักเรียน</th>
                        <th>ช่องทาง</th>
                        <th>ผู้ส่ง</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>16/03/2568 09:30</td>
                        <td>แจ้งเตือนความเสี่ยง</td>
                        <td>นางวันดี สุขใจ</td>
                        <td>นายธนกฤต สุขใจ</td>
                        <td><span class="status-badge success">LINE</span></td>
                        <td>จารุวรรณ บุญมี</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>15/03/2568 14:45</td>
                        <td>รายงานสรุป</td>
                        <td>นายสมชาย มีสุข</td>
                        <td>นางสาวสมหญิง มีสุข</td>
                        <td><span class="status-badge success">LINE</span></td>
                        <td>จารุวรรณ บุญมี</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>14/03/2568 10:15</td>
                        <td>แจ้งเตือนทั่วไป</td>
                        <td>นางรักดี รักเรียน</td>
                        <td>นายพิชัย รักเรียน</td>
                        <td>
                            <span class="status-badge success">LINE</span>
                            <span class="status-badge warning">SMS</span>
                        </td>
                        <td>อิศรา สุขใจ</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ">
                                <span class="material-icons">visibility</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="message-stats">
            <div class="row">
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">ข้อความทั้งหมด</div>
                        <div class="stat-value">356</div>
                        <div class="stat-footer">ในช่วงวันที่ที่เลือก</div>
                    </div>
                </div>
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">ส่งสำเร็จ</div>
                        <div class="stat-value success">352</div>
                        <div class="stat-footer">คิดเป็น 98.88%</div>
                    </div>
                </div>
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">ส่งไม่สำเร็จ</div>
                        <div class="stat-value danger">4</div>
                        <div class="stat-footer">คิดเป็น 1.12%</div>
                    </div>
                </div>
                <div class="col-3 col-md-6 col-sm-12">
                    <div class="stat-card">
                        <div class="stat-title">ผู้ปกครองที่ติดต่อได้</div>
                        <div class="stat-value">48</div>
                        <div class="stat-footer">จาก 52 คน (92.31%)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เนื้อหาแท็บตั้งค่า -->
<div id="settings-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">settings</span>
            ตั้งค่าการส่งข้อความถึงผู้ปกครอง
        </div>

        <div class="settings-form">
            <div class="form-group">
                <label class="form-label">ช่องทางการส่งข้อความเริ่มต้น</label>
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="channel_line" checked>
                        <label for="channel_line">LINE Official Account</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="channel_sms" checked>
                        <label for="channel_sms">SMS</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="channel_email">
                        <label for="channel_email">Email</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ข้อความแนะนำตัวเริ่มต้น</label>
                <textarea class="form-control" rows="3">เรียน ผู้ปกครอง [ชื่อนักเรียน]</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">ลายเซ็นข้อความเริ่มต้น</label>
                <textarea class="form-control" rows="3">ด้วยความเคารพฝ่ายกิจการนักเรียนโรงเรียนประสาทวิทยาคม</textarea>
            </div>

            <div class="form-group">
                <label class="form-label">รายงานผู้บริหารเมื่อส่งข้อความ</label>
                <select class="form-control">
                    <option>ทุกครั้ง</option>
                    <option>เฉพาะข้อความกลุ่ม</option>
                    <option>เฉพาะกรณีเร่งด่วน</option>
                    <option>ไม่ต้องรายงาน</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">บันทึกประวัติการส่งข้อความ</label>
                <select class="form-control">
                    <option>เก็บทุกรายการ</option>
                    <option selected>เก็บ 3 เดือนล่าสุด</option>
                    <option>เก็บ 6 เดือนล่าสุด</option>
                    <option>เก็บ 1 ปีล่าสุด</option>
                </select>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary">รีเซ็ตเป็นค่าเริ่มต้น</button>
                <button class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <span class="material-icons">app_registration</span>
            ตั้งค่า LINE Official Account
        </div>

        <div class="settings-form">
            <div class="form-group">
                <label class="form-label">Channel ID</label>
                <input type="text" class="form-control" value="1234567890">
            </div>

            <div class="form-group">
                <label class="form-label">Channel Secret</label>
                <input type="password" class="form-control" value="************">
            </div>

            <div class="form-group">
                <label class="form-label">Access Token</label>
                <input type="password" class="form-control" value="************************">
            </div>

            <div class="form-group">
                <label class="form-label">สถานะการเชื่อมต่อ</label>
                <div class="connection-status">
                    <span class="status-badge success">เชื่อมต่อแล้ว</span>
                    <span class="connection-info">อัปเดตล่าสุด: 16/03/2568 08:30</span>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary">ทดสอบการเชื่อมต่อ</button>
                <button class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการตั้งค่า
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลเพิ่มผู้ปกครอง -->
<div class="modal" id="addParentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('addParentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">เพิ่มข้อมูลผู้ปกครอง</h2>

        <div class="form-group">
            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
            <input type="text" class="form-control" placeholder="กรุณากรอกชื่อ-นามสกุลผู้ปกครอง">
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">ความเกี่ยวข้องกับนักเรียน <span class="text-danger">*</span></label>
                    <select class="form-control">
                        <option value="">-- เลือกความเกี่ยวข้อง --</option>
                        <option>พ่อ</option>
                        <option>แม่</option>
                        <option>ปู่</option>
                        <option>ย่า</option>
                        <option>ตา</option>
                        <option>ยาย</option>
                        <option>ลุง</option>
                        <option>ป้า</option>
                        <option>น้า</option>
                        <option>อา</option>
                        <option>พี่</option>
                        <option>อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" placeholder="กรุณากรอกเบอร์โทรศัพท์">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" class="form-control" placeholder="กรุณากรอกอีเมล (ถ้ามี)">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">LINE ID</label>
                    <input type="text" class="form-control" placeholder="กรุณากรอก LINE ID (ถ้ามี)">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">ที่อยู่</label>
            <textarea class="form-control" rows="3" placeholder="กรุณากรอกที่อยู่"></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">นักเรียนในปกครอง <span class="text-danger">*</span></label>
            <select class="form-control">
                <option value="">-- เลือกนักเรียนในปกครอง --</option>
                <option>นายธนกฤต สุขใจ (ม.6/2)</option>
                <option>นางสาวสมหญิง มีสุข (ม.5/3)</option>
                <option>นายพิชัย รักเรียน (ม.4/1)</option>
                <option>นางสาวรุ่งนภา พัฒนา (ม.5/1)</option>
                <option>นายอานนท์ ภักดี (ม.5/1)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">ช่องทางการแจ้งเตือน <span class="text-danger">*</span></label>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="notification_line" checked>
                    <label for="notification_line">LINE</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="notification_sms" checked>
                    <label for="notification_sms">SMS</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="notification_email">
                    <label for="notification_email">Email</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">สถานะ</label>
            <select class="form-control">
                <option>ใช้งาน</option>
                <option>ระงับการใช้งาน</option>
            </select>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('addParentModal')">ยกเลิก</button>
            <button class="btn btn-primary">
                <span class="material-icons">save</span>
                บันทึกข้อมูล
            </button>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขข้อมูลผู้ปกครอง -->
<div class="modal" id="editParentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('editParentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แก้ไขข้อมูลผู้ปกครอง</h2>

        <div class="form-group">
            <label class="form-label">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="นางวันดี สุขใจ">
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">ความเกี่ยวข้องกับนักเรียน <span class="text-danger">*</span></label>
                    <select class="form-control">
                        <option>พ่อ</option>
                        <option selected>แม่</option>
                        <option>ปู่</option>
                        <option>ย่า</option>
                        <option>ตา</option>
                        <option>ยาย</option>
                        <option>ลุง</option>
                        <option>ป้า</option>
                        <option>น้า</option>
                        <option>อา</option>
                        <option>พี่</option>
                        <option>อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" value="089-765-4321">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">อีเมล</label>
                    <input type="email" class="form-control" value="wandee@example.com">
                </div>
            </div>
            <div class="col-6">
                <div class="form-group">
                    <label class="form-label">LINE ID</label>
                    <input type="text" class="form-control" value="wandee123">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">ที่อยู่</label>
            <textarea class="form-control" rows="3">123 หมู่ 4 ตำบลปราสาท อำเภอเมือง จังหวัดสุรินทร์ 32000</textarea>
        </div>

        <div class="form-group">
            <label class="form-label">นักเรียนในปกครอง <span class="text-danger">*</span></label>
            <select class="form-control">
                <option selected>นายธนกฤต สุขใจ (ม.6/2)</option>
                <option>นางสาวสมหญิง มีสุข (ม.5/3)</option>
                <option>นายพิชัย รักเรียน (ม.4/1)</option>
                <option>นางสาวรุ่งนภา พัฒนา (ม.5/1)</option>
                <option>นายอานนท์ ภักดี (ม.5/1)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">ช่องทางการแจ้งเตือน <span class="text-danger">*</span></label>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="edit_notification_line" checked>
                    <label for="edit_notification_line">LINE</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="edit_notification_sms" checked>
                    <label for="edit_notification_sms">SMS</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="edit_notification_email">
                    <label for="edit_notification_email">Email</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">สถานะ</label>
            <select class="form-control">
                <option selected>ใช้งาน</option>
                <option>ระงับการใช้งาน</option>
            </select>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('editParentModal')">ยกเลิก</button>
            <button class="btn btn-primary">
                <span class="material-icons">save</span>
                บันทึกข้อมูล
            </button>
        </div>
    </div>
</div>

<!-- โมดัลส่งข้อความถึงผู้ปกครอง -->
<div class="modal" id="sendMessageModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('sendMessageModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ส่งข้อความถึงผู้ปกครอง - นางวันดี สุขใจ</h2>

        <div class="template-buttons">
            <button class="template-btn active" onclick="selectMessageTemplate('regular')">ข้อความปกติ</button>
            <button class="template-btn" onclick="selectMessageTemplate('warning')">แจ้งเตือนความเสี่ยง</button>
            <button class="template-btn" onclick="selectMessageTemplate('critical')">แจ้งเตือนฉุกเฉิน</button>
            <button class="template-btn" onclick="selectMessageTemplate('summary')">รายงานสรุป</button>
        </div>

        <div class="form-group">
            <label class="form-label">เนื้อหาข้อความ</label>
            <textarea class="message-textarea" id="messageText">เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ

ทางโรงเรียนขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ปัจจุบันเข้าร่วม 26 จาก 40 วัน (65%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>
        </div>

        <div class="form-group">
            <label class="form-label">ช่องทางการส่ง</label>
            <div class="checkbox-group">
                <div class="checkbox-item">
                    <input type="checkbox" id="send_line" checked>
                    <label for="send_line">LINE (wandee123)</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="send_sms" checked>
                    <label for="send_sms">SMS (089-765-4321)</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="send_email">
                    <label for="send_email">Email (wandee@example.com)</label>
                </div>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('sendMessageModal')">ยกเลิก</button>
            <button class="btn btn-primary">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลนำเข้า CSV -->
<div class="modal" id="importModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('importModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">นำเข้าข้อมูลผู้ปกครองจากไฟล์ CSV</h2>

        <div class="upload-container">
            <div class="upload-area" id="uploadArea">
                <span class="material-icons">cloud_upload</span>
                <p>คลิกหรือลากไฟล์ CSV มาที่นี่</p>
                <input type="file" id="fileUpload" style="display: none;" accept=".csv">
            </div>
            <div class="upload-info">
                <p>รองรับไฟล์ CSV เท่านั้น ขนาดสูงสุด 10MB</p>
                <p>ดาวน์โหลด <a href="#" class="download-link">เทมเพลตไฟล์ CSV</a></p>
            </div>
        </div>

        <div class="import-options">
            <div class="form-group">
                <label class="form-label">การนำเข้าข้อมูล</label>
                <select class="form-control">
                    <option>เพิ่มข้อมูลใหม่เท่านั้น</option>
                    <option>อัปเดตข้อมูลเดิม (ตรวจสอบจากเบอร์โทรศัพท์)</option>
                    <option>ลบข้อมูลเดิมและนำเข้าทั้งหมดใหม่</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">ตัวคั่นข้อมูล</label>
                <select class="form-control">
                    <option selected>,</option>
                    <option>;</option>
                    <option>|</option>
                    <option>TAB</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">การเข้ารหัส</label>
                <select class="form-control">
                    <option selected>UTF-8</option>
                    <option>TIS-620</option>
                    <option>Windows-874</option>
                </select>
            </div>
        </div>

        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('importModal')">ยกเลิก</button>
            <button class="btn btn-primary" disabled>
                <span class="material-icons">file_upload</span>
                นำเข้าข้อมูล
            </button>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันสำหรับแท็บ
    function showTab(tabId) {
        // ซ่อนแท็บทั้งหมด
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // ยกเลิกการเลือกแท็บทั้งหมด
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });

        // แสดงแท็บที่ต้องการและเลือกแท็บนั้น
        document.getElementById(tabId + '-tab').classList.add('active');
        document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
    }

    // แสดงโมดัลเพิ่มผู้ปกครอง
    function showAddParentModal() {
        showModal('addParentModal');
    }

    // แสดงโมดัลแก้ไขข้อมูลผู้ปกครอง
    function showEditParentModal(parentId) {
        showModal('editParentModal');
    }

    // แสดงโมดัลส่งข้อความถึงผู้ปกครอง
    function showSendMessageModal(parentId) {
        showModal('sendMessageModal');
    }

    // แสดงโมดัลนำเข้า CSV
    function showImportModal() {
        showModal('importModal');
    }

    // แสดงโมดัล
    function showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }

    // ปิดโมดัล
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    // เลือกเทมเพลตข้อความ
    function selectMessageTemplate(templateType) {
        // ยกเลิกการเลือกเทมเพลตทั้งหมด
        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // เลือกเทมเพลตที่คลิก
        event.target.classList.add('active');

        // เปลี่ยนข้อความตามเทมเพลตที่เลือก
        const messageText = document.getElementById('messageText');

        switch (templateType) {
            case 'regular':
                messageText.value = 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ปัจจุบันเข้าร่วม 26 จาก 40 วัน (65%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
                break;
            case 'warning':
                messageText.value = 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
                break;
            case 'critical':
                messageText.value = 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\n[ข้อความด่วน] ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
                break;
            case 'summary':
                messageText.value = 'เรียน คุณวันดี สุขใจ ผู้ปกครองของ นายธนกฤต สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 10 วัน จากทั้งหมด 22 วัน (45.45%)\nจำนวนวันขาดแถว: 12 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
                break;
        }
    }

    // เมื่อโหลดหน้าเสร็จ ให้เรียกฟังก์ชันเพื่อตั้งค่าแท็บและอื่นๆ
    document.addEventListener('DOMContentLoaded', function() {
        // ตั้งค่าแท็บ
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                showTab(tabId);
            });
        });

        // ตั้งค่าการอัปโหลดไฟล์
        const uploadArea = document.getElementById('uploadArea');
        const fileUpload = document.getElementById('fileUpload');

        if (uploadArea && fileUpload) {
            uploadArea.addEventListener('click', function() {
                fileUpload.click();
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('upload-area-drag');
            });

            uploadArea.addEventListener('dragleave', function() {
                this.classList.remove('upload-area-drag');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('upload-area-drag');

                if (e.dataTransfer.files.length) {
                    fileUpload.files = e.dataTransfer.files;
                    handleFileUpload(e.dataTransfer.files[0]);
                }
            });

            fileUpload.addEventListener('change', function() {
                if (this.files.length) {
                    handleFileUpload(this.files[0]);
                }
            });
        }
    });

    // จัดการกับไฟล์ที่อัปโหลด
    function handleFileUpload(file) {
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            alert('กรุณาอัปโหลดไฟล์ CSV เท่านั้น');
            return;
        }

        const uploadArea = document.getElementById('uploadArea');
        const importButton = document.querySelector('#importModal .btn-primary');

        if (uploadArea) {
            uploadArea.innerHTML = `
            <span class="material-icons">description</span>
            <p>${file.name} (${formatFileSize(file.size)})</p>
            <button type="button" class="btn btn-sm btn-secondary" onclick="resetFileUpload(event)">เปลี่ยนไฟล์</button>
        `;
        }

        if (importButton) {
            importButton.disabled = false;
        }
    }

    // รีเซ็ตการอัปโหลดไฟล์
    function resetFileUpload(event) {
        event.stopPropagation();

        const uploadArea = document.getElementById('uploadArea');
        const fileUpload = document.getElementById('fileUpload');
        const importButton = document.querySelector('#importModal .btn-primary');

        if (uploadArea) {
            uploadArea.innerHTML = `
            <span class="material-icons">cloud_upload</span>
            <p>คลิกหรือลากไฟล์ CSV มาที่นี่</p>
        `;
        }

        if (fileUpload) {
            fileUpload.value = '';
        }

        if (importButton) {
            importButton.disabled = true;
        }
    }

    // ฟอร์แมตขนาดไฟล์
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' bytes';
        else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
        else return (bytes / 1048576).toFixed(2) + ' MB';
    }


    
</script>

<style>
    /* เพิ่มเติมสำหรับหน้าจัดการข้อมูลผู้ปกครอง */
    .parent-info {
        display: flex;
        align-items: center;
    }

    .parent-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary-color-light);
        color: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 10px;
    }

    .parent-details {
        flex: 1;
    }

    .parent-name {
        font-weight: 500;
    }

    .parent-email {
        font-size: 12px;
        color: var(--text-light);
    }

    .student-info-mini {
        display: flex;
        flex-direction: column;
    }

    .student-info-mini .student-name {
        font-weight: 500;
    }

    .student-info-mini .student-class {
        font-size: 12px;
        color: var(--text-light);
    }

    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
    }

    .pagination {
        display: flex;
        gap: 5px;
    }

    .page-item {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        background-color: #f5f5f5;
        color: var(--text-dark);
        text-decoration: none;
        font-size: 14px;
    }

    .page-item.active {
        background-color: var(--primary-color);
        color: white;
    }

    .page-item:hover:not(.active) {
        background-color: #e0e0e0;
    }

    .pagination-info {
        font-size: 14px;
        color: var(--text-light);
    }

    .message-stats {
        margin-top: 20px;
    }

    .stat-card {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        height: 100%;
    }

    .stat-title {
        font-size: 14px;
        color: var(--text-light);
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .stat-value.success {
        color: var(--success-color);
    }

    .stat-value.danger {
        color: var(--danger-color);
    }

    .stat-footer {
        font-size: 12px;
        color: var(--text-light);
    }

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
    }

    .checkbox-item input[type="checkbox"] {
        margin-right: 5px;
    }

    .settings-form {
        margin-top: 10px;
    }

    .settings-form .form-group {
        margin-bottom: 20px;
    }

    .connection-status {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .connection-info {
        font-size: 14px;
        color: var(--text-light);
    }

    .text-danger {
        color: var(--danger-color);
    }

    .upload-container {
        margin-bottom: 20px;
    }

    .upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 10px;
    }

    .upload-area:hover {
        border-color: var(--primary-color);
    }

    .upload-area-drag {
        border-color: var(--primary-color);
        background-color: var(--primary-color-light);
    }

    .upload-area .material-icons {
        font-size: 48px;
        color: var(--text-light);
        margin-bottom: 10px;
    }

    .upload-info {
        text-align: center;
        font-size: 14px;
        color: var(--text-light);
    }

    .download-link {
        color: var(--primary-color);
        text-decoration: none;
    }

    .download-link:hover {
        text-decoration: underline;
    }

    .import-options {
        margin: 20px 0;
    }

    /* สำหรับอุปกรณ์เคลื่อนที่ */
    @media (max-width: 992px) {
        .filter-container {
            flex-direction: column;
        }

        .filter-group {
            width: 100%;
        }

        .checkbox-group {
            flex-direction: column;
            gap: 10px;
        }
    }

    @media (max-width: 768px) {
        .parent-avatar {
            width: 35px;
            height: 35px;
            font-size: 14px;
        }

        .pagination-container {
            flex-direction: column;
            gap: 10px;
        }

        .pagination {
            justify-content: center;
        }

        .pagination-info {
            text-align: center;
        }
    }

    @media (max-width: 576px) {
        .tab {
            padding: 10px;
            font-size: 14px;
        }

        .template-buttons {
            flex-direction: column;
            gap: 5px;
        }

        .template-btn {
            width: 100%;
            text-align: center;
        }

        .upload-area {
            padding: 15px;
        }
    }
</style>