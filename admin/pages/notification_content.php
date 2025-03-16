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
            <div class="filter-group">
                <div class="filter-label">สถานะการเข้าแถว</div>
                <select class="form-control">
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

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="15%">เข้าแถว</th>
                        <th width="15%">สถานะ</th>
                        <th width="15%">ผู้ปกครอง</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="radio" name="student_select" checked>
                        </td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ธ</div>
                                <div class="student-details">
                                    <div class="student-name">นายธนกฤต สุขใจ</div>
                                    <div class="student-class">เลขที่ 12</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.6/2</td>
                        <td>26/40 วัน (65%)</td>
                        <td><span class="status-badge danger">เสี่ยงตกกิจกรรม</span></td>
                        <td>นางวันดี สุขใจ (แม่)</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูประวัติการส่ง">
                                    <span class="material-icons">history</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" name="student_select">
                        </td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ส</div>
                                <div class="student-details">
                                    <div class="student-name">นางสาวสมหญิง มีสุข</div>
                                    <div class="student-class">เลขที่ 8</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.5/3</td>
                        <td>30/40 วัน (75%)</td>
                        <td><span class="status-badge warning">ต้องระวัง</span></td>
                        <td>นายสมชาย มีสุข (พ่อ)</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูประวัติการส่ง">
                                    <span class="material-icons">history</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" name="student_select">
                        </td>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">พ</div>
                                <div class="student-details">
                                    <div class="student-name">นายพิชัย รักเรียน</div>
                                    <div class="student-class">เลขที่ 15</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.4/1</td>
                        <td>38/40 วัน (95%)</td>
                        <td><span class="status-badge success">ปกติ</span></td>
                        <td>นางรักดี รักเรียน (แม่)</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูประวัติการส่ง">
                                    <span class="material-icons">history</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-title">
            <span class="material-icons">message</span>
            ส่งข้อความถึงผู้ปกครอง - นายธนกฤต สุขใจ (ม.6/2)
        </div>

        <div class="template-buttons">
            <button class="template-btn active" onclick="selectTemplate('regular')">ข้อความปกติ</button>
            <button class="template-btn" onclick="selectTemplate('warning')">แจ้งเตือนความเสี่ยง</button>
            <button class="template-btn" onclick="selectTemplate('critical')">แจ้งเตือนฉุกเฉิน</button>
            <button class="template-btn" onclick="selectTemplate('summary')">รายงานสรุป</button>
        </div>

        <div class="message-form">
            <textarea class="message-textarea" id="messageText">เรียน ผู้ปกครองของ นายธนกฤต สุขใจ

ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)

กรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>

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
                    <p style="margin-top: 10px;">เรียน ผู้ปกครองของ นายธนกฤต สุขใจ<br><br>ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)<br><br>กรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>โรงเรียนประสาทวิทยาคม</p>
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
                <select class="form-control">
                    <option value="">-- ทุกระดับชั้น --</option>
                    <option>ม.1</option>
                    <option>ม.2</option>
                    <option>ม.3</option>
                    <option>ม.4</option>
                    <option selected>ม.5</option>
                    <option>ม.6</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">ห้องเรียน</div>
                <select class="form-control">
                    <option value="">-- ทุกห้อง --</option>
                    <option selected>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">สถานะการเข้าแถว</div>
                <select class="form-control">
                    <option value="">-- ทุกสถานะ --</option>
                    <option selected>เสี่ยงตกกิจกรรม</option>
                    <option>ต้องระวัง</option>
                    <option>ปกติ</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">อัตราการเข้าแถว</div>
                <select class="form-control">
                    <option value="">-- ทั้งหมด --</option>
                    <option selected>น้อยกว่า 70%</option>
                    <option>70% - 80%</option>
                    <option>80% - 90%</option>
                    <option>มากกว่า 90%</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">filter_list</span>
                กรองข้อมูล
            </button>
        </div>
        
        <p><strong>พบนักเรียนที่ตรงตามเงื่อนไข 8 คน</strong></p>
        
        <div class="recipients-container">
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นายยศพล วงศ์ประเสริฐ (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นางสาวสุนิสา วงศ์ประเสริฐ (แม่)</div>
                    </div>
                </div>
                <span class="status-badge danger">65%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นางสาวนภัสวรรณ จันทรา (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นายสมชาย จันทรา (พ่อ)</div>
                    </div>
                </div>
                <span class="status-badge warning">75%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นายวีรยุทธ รักดี (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นางวันดี รักดี (แม่)</div>
                    </div>
                </div>
                <span class="status-badge danger">60%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นายชัยวัฒน์ ใจดี (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นายสมศักดิ์ ใจดี (พ่อ)</div>
                    </div>
                </div>
                <span class="status-badge danger">68%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นางสาวกันยา สุขศรี (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นางนิภา สุขศรี (แม่)</div>
                    </div>
                </div>
                <span class="status-badge danger">67%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นายอานนท์ ภักดี (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นางสาวอรุณ ภักดี (แม่)</div>
                    </div>
                </div>
                <span class="status-badge danger">66%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นางสาวรุ่งนภา พัฒนา (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นายวิชัย พัฒนา (พ่อ)</div>
                    </div>
                </div>
                <span class="status-badge danger">62%</span>
            </div>
            <div class="recipient-item">
                <div class="recipient-info">
                    <input type="checkbox" checked>
                    <div class="recipient-details">
                        <div class="student-name">นายอภิสิทธิ์ สงวนสิทธิ์ (ม.5/1)</div>
                        <div class="parent-info">ผู้ปกครอง: นางเพ็ญศรี สงวนสิทธิ์ (แม่)</div>
                    </div>
                </div>
                <span class="status-badge danger">69%</span>
            </div>
        </div>
        
        <div class="batch-actions">
            <button class="btn btn-secondary" onclick="selectAllRecipients()">เลือกทั้งหมด</button>
            <button class="btn btn-secondary" onclick="clearAllRecipients()">ยกเลิกเลือกทั้งหมด</button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">
            <span class="material-icons">send</span>
            ส่งข้อความถึงผู้ปกครองกลุ่ม (8 คน)
        </div>

        <div class="template-buttons">
            <button class="template-btn active" onclick="selectGroupTemplate('regular')">ข้อความปกติ</button>
            <button class="template-btn" onclick="selectGroupTemplate('risk-warning')">แจ้งเตือนกลุ่มเสี่ยง</button>
            <button class="template-btn" onclick="selectGroupTemplate('meeting')">นัดประชุมผู้ปกครอง</button>
            <button class="template-btn" onclick="selectGroupTemplate('reminder')">แจ้งเตือนทั่วไป</button>
        </div>

        <div class="message-form">
            <textarea class="message-textarea" id="groupMessageText">เรียน ท่านผู้ปกครองนักเรียนชั้น ม.5/1

ทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้

1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา
2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว
3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น ม.5/1 ครูอิศรา สุขใจ โทร. 081-234-5678 หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>

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
                    <p style="margin-top: 10px;">เรียน ท่านผู้ปกครองนักเรียนชั้น ม.5/1<br><br>ทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด<br><br>ทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้<br><br>1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา<br>2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว<br>3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ<br><br>กรุณาติดต่อครูที่ปรึกษาประจำชั้น ม.5/1 ครูอิศรา สุขใจ โทร. 081-234-5678 หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>โรงเรียนประสาทวิทยาคม</p>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" onclick="resetGroupForm()">ยกเลิก</button>
                <button class="btn btn-primary" onclick="sendGroupMessage()">
                    <span class="material-icons">send</span>
                    ส่งข้อความ (8 ราย)
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

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="30%">ชื่อเทมเพลต</th>
                        <th width="15%">ประเภท</th>
                        <th width="15%">สร้างเมื่อ</th>
                        <th width="15%">ใช้งานล่าสุด</th>
                        <th width="10%">สถานะ</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>แจ้งเตือนความเสี่ยงรายบุคคล</td>
                        <td>รายบุคคล</td>
                        <td>10/03/2568</td>
                        <td>16/03/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>นัดประชุมผู้ปกครองกลุ่มเสี่ยง</td>
                        <td>กลุ่ม</td>
                        <td>05/03/2568</td>
                        <td>16/03/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>แจ้งเตือนฉุกเฉิน</td>
                        <td>รายบุคคล</td>
                        <td>01/02/2568</td>
                        <td>10/03/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>รายงานสรุปประจำเดือน</td>
                        <td>กลุ่ม</td>
                        <td>15/01/2568</td>
                        <td>01/03/2568</td>
                        <td><span class="status-badge success">ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง">
                                    <span class="material-icons">visibility</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>แจ้งนักเรียนลาป่วย</td>
                        <td>รายบุคคล</td>
                        <td>05/01/2568</td>
                        <td>-</td>
                        <td><span class="status-badge warning">ไม่ได้ใช้งาน</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="table-action-btn success" title="ดูตัวอย่าง">
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
            <label class="form-label">เนื้อหาข้อความ</label>
            <textarea class="message-textarea" rows="10" placeholder="กรุณากรอกเนื้อหาข้อความเทมเพลต"></textarea>
            <p class="form-text">* คุณสามารถใช้ตัวแปรในข้อความได้ เช่น {{ชื่อนักเรียน}}, {{ชั้นเรียน}}, {{ร้อยละการเข้าแถว}}</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('templateModal')">ยกเลิก</button>
            <button class="btn btn-primary">
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
            <div class="preview-content">
                <strong>LINE Official Account: SADD-Prasat</strong>
                <div id="previewText" style="margin-top: 15px; line-height: 1.6;">
                    <!-- เนื้อหาข้อความจะถูกแทรกที่นี่ด้วย JavaScript -->
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
        <h2 class="modal-title">ประวัติการส่งข้อความ - นายธนกฤต สุขใจ</h2>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>วันที่ส่ง</th>
                        <th>ประเภท</th>
                        <th>ผู้ส่ง</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>16/03/2568 08:45</td>
                        <td>แจ้งเตือนความเสี่ยง</td>
                        <td>จารุวรรณ บุญมี</td>
                        <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                        <td>
                            <button class="table-action-btn primary" title="ดูข้อความ">
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
                            <button class="table-action-btn primary" title="ดูข้อความ">
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
                            <button class="table-action-btn primary" title="ดูข้อความ">
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

// ฟังก์ชันสำหรับเลือกเทมเพลต
function selectTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    event.target.classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    
    switch(templateType) {
        case 'regular':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ปัจจุบันเข้าร่วม 26 จาก 40 วัน (65%)\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'warning':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'critical':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\n[ข้อความด่วน] ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
        case 'summary':
            messageText.value = 'เรียน ผู้ปกครองของ นายธนกฤต สุขใจ\n\nสรุปข้อมูลการเข้าแถวของ นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 10 วัน จากทั้งหมด 22 วัน (45.45%)\nจำนวนวันขาดแถว: 12 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nหมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nโรงเรียนประสาทวิทยาคม';
            break;
    }
}

// ฟังก์ชันแสดงโมดัลสร้างเทมเพลตใหม่
function createNewTemplate() {
    showModal('templateModal');
}

// ฟังก์ชันสำหรับแสดงตัวอย่างข้อความ
function showPreview() {
    const messageText = document.getElementById('messageText').value;
    const previewText = document.getElementById('previewText');
    
    if (previewText) {
        previewText.innerHTML = messageText.replace(/\n/g, '<br>');
    }
    
    showModal('previewModal');
}

// ฟังก์ชันสำหรับแสดงประวัติการส่งข้อความ
function showHistory() {
    showModal('historyModal');
}
</script>