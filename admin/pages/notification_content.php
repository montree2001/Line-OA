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
            <button class="filter-button" onclick="applyFilters()">
                <span class="material-icons">search</span>
                ค้นหา
            </button>
        </div>

        <div class="filter-result-count">พบนักเรียนทั้งหมด <span id="student-count">0</span> คน</div>

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
                <tbody id="student-list">
                    <!-- ข้อมูลนักเรียนจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                    <tr>
                        <td colspan="7" class="text-center">ไม่พบข้อมูลนักเรียน กรุณาค้นหาด้วยเงื่อนไขด้านบน</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" id="message-card">
        <div class="card-title">
            <span class="material-icons">message</span>
            ส่งข้อความถึงผู้ปกครอง - <span class="student-name-display">-</span> (<span class="student-class-display">-</span>)
            <span style="display:none" class="attendance-days-display">0/0</span>
            <span style="display:none" class="attendance-rate-display">0</span>
            <span style="display:none" class="advisor-name-display">-</span>
            <span style="display:none" class="advisor-phone-display">-</span>
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
            <textarea class="message-textarea" id="messageText" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

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
                    <p style="margin-top: 10px;">เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}<br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                    
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
        
        <div class="filter-group-result">พบนักเรียนที่ตรงตามเงื่อนไข <span class="recipient-count">0</span> คน</div>
        
        <div class="recipients-container" id="group-recipients">
            <!-- ข้อมูลนักเรียนจะถูกเพิ่มที่นี่ด้วย JavaScript -->
            <div class="text-center" style="padding: 20px;">ไม่พบข้อมูลนักเรียน กรุณากรองข้อมูลด้วยเงื่อนไขด้านบน</div>
        </div>
        
        <div class="batch-actions">
            <button class="btn btn-secondary" onclick="selectAllRecipients()">เลือกทั้งหมด</button>
            <button class="btn btn-secondary" onclick="clearAllRecipients()">ยกเลิกเลือกทั้งหมด</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">
            <span class="material-icons">send</span>
            ส่งข้อความถึงผู้ปกครองกลุ่ม (<span class="recipient-count">0</span> คน)
            <span style="display:none" class="class-info-display">ปวช.1/1</span>
            <span style="display:none" class="advisor-name-display">ครูอิศรา สุขใจ</span>
            <span style="display:none" class="advisor-phone-display">081-234-5678</span>
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
            <textarea class="message-textarea" id="groupMessageText" placeholder="พิมพ์ข้อความที่ต้องการส่ง...">เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่าในวันศุกร์ที่ 21 มีนาคม 2568 นักเรียนชั้น {{ชั้นเรียน}} จะต้องมาวิทยาลัยก่อนเวลา 07:30 น. เพื่อเตรียมความพร้อมในการเข้าแถวพิเศษสำหรับกิจกรรมวันภาษาไทย

จึงเรียนมาเพื่อทราบ

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
                    <p style="margin-top: 10px;">เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}<br><br>ทางวิทยาลัยขอแจ้งว่าในวันศุกร์ที่ 21 มีนาคม 2568 นักเรียนชั้น {{ชั้นเรียน}} จะต้องมาวิทยาลัยก่อนเวลา 07:30 น. เพื่อเตรียมความพร้อมในการเข้าแถวพิเศษสำหรับกิจกรรมวันภาษาไทย<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
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
                        <span class="cost-label">ข้อความ (0 คน):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">รูปภาพกราฟ (0 รูป):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item">
                        <span class="cost-label">ลิงก์ (0 ลิงก์):</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                    <div class="cost-item total">
                        <span class="cost-label">รวม:</span>
                        <span class="cost-value">0.00 บาท</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" onclick="resetGroupForm()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="sendGroupMessage()">
                    <span class="material-icons">send</span>
                    ส่งข้อความ (0 ราย)
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
                <tbody id="template-list">
                    <!-- เทมเพลตจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                    <tr>
                        <td>แจ้งเตือนความเสี่ยงรายบุคคล</td>
                        <td>รายบุคคล</td>
                        <td>การเข้าแถว</td>
                        <td>01/03/2568</td>
                        <td>20/04/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข" onclick="editTemplate(1)">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง" onclick="previewTemplate(1)">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>นัดประชุมผู้ปกครองกลุ่มเสี่ยง</td>
                        <td>กลุ่ม</td>
                        <td>การประชุม</td>
                        <td>01/03/2568</td>
                        <td>15/04/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข" onclick="editTemplate(2)">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง" onclick="previewTemplate(2)">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
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
        <h2 class="modal-title">ประวัติการส่งข้อความ - <span class="history-student-name">-</span></h2>
        
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
                    <!-- ประวัติการส่งจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                    <tr>
                        <td colspan="5" class="text-center">ไม่พบประวัติการส่งข้อความ</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('historyModal')">ปิด</button>
        </div>
    </div>
</div>

<!-- เพิ่ม script สำหรับ Chart.js และไฟล์ JavaScript -->
<script src="assets/js/chart.min.js"></script>
<script src="assets/js/notification_enhanced.js"></script>