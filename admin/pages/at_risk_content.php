<!-- แท็บสำหรับนักเรียนกลุ่มเสี่ยง -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="at-risk">เสี่ยงตกกิจกรรม <span class="badge">12</span></div>
        <div class="tab" data-tab="frequently-absent">ขาดแถวบ่อย <span class="badge">23</span></div>
        <div class="tab" data-tab="pending-notification">รอการแจ้งเตือน <span class="badge">8</span></div>
    </div>
</div>

<!-- เนื้อหาแท็บนักเรียนเสี่ยงตกกิจกรรม -->
<div id="at-risk-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">warning</span>
            นักเรียนที่เสี่ยงตกกิจกรรมเข้าแถว
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
                <div class="filter-label">ครูที่ปรึกษา</div>
                <select class="form-control">
                    <option value="">-- ทั้งหมด --</option>
                    <option>อ.ประสิทธิ์ ดีเลิศ</option>
                    <option>อ.วันดี สดใส</option>
                    <option>อ.อิศรา สุขใจ</option>
                    <option>อ.ใจดี มากเมตตา</option>
                </select>
            </div>
            <div class="filter-group">
                <div class="filter-label">อัตราการเข้าแถว</div>
                <select class="form-control">
                    <option value="">-- ทั้งหมด --</option>
                    <option>ต่ำกว่า 60%</option>
                    <option>60% - 70%</option>
                    <option>70% - 80%</option>
                </select>
            </div>
            <button class="filter-button">
                <span class="material-icons">filter_list</span>
                กรองข้อมูล
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="15%">อัตราการเข้าแถว</th>
                        <th width="10%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="10%">การแจ้งเตือน</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
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
                        <td><span class="status-badge danger">68.5%</span></td>
                        <td>15 วัน</td>
                        <td>อ.ประสิทธิ์ ดีเลิศ</td>
                        <td>ยังไม่แจ้ง</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(1)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(1)">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
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
                        <td><span class="status-badge danger">70.2%</span></td>
                        <td>14 วัน</td>
                        <td>อ.วันดี สดใส</td>
                        <td>แจ้งแล้ว 1 ครั้ง</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(2)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(2)">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
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
                        <td><span class="status-badge warning">75.3%</span></td>
                        <td>12 วัน</td>
                        <td>อ.ใจดี มากเมตตา</td>
                        <td>แจ้งแล้ว 2 ครั้ง</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(3)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(3)">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ว</div>
                                <div class="student-details">
                                    <div class="student-name">นางสาววรรณา ชาติไทย</div>
                                    <div class="student-class">เลขที่ 10</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.5/2</td>
                        <td><span class="status-badge warning">73.5%</span></td>
                        <td>13 วัน</td>
                        <td>อ.วิชัย สุขสวัสดิ์</td>
                        <td>แจ้งแล้ว 1 ครั้ง</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด" onclick="showStudentDetail(4)">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ" onclick="showSendMessageModal(4)">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card-footer">
            <div class="pagination">
                <a href="#" class="page-link active">1</a>
                <a href="#" class="page-link">2</a>
                <a href="#" class="page-link">3</a>
                <span class="page-separator">...</span>
                <a href="#" class="page-link">5</a>
            </div>
            
            <button class="btn btn-primary bulk-action-btn" onclick="showBulkNotificationModal()">
                <span class="material-icons">send</span>
                ส่งรายงานไปยังผู้ปกครองทั้งหมด
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-title">
            <span class="material-icons">bar_chart</span>
            สถิติอัตราการเข้าแถวแยกตามระดับชั้น
        </div>
        
        <div class="chart-container" style="height: 300px;">
            <!-- ในทางปฏิบัติจริง จะใช้ JavaScript สร้างกราฟ -->
            <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 100%;">
                <div style="text-align: center;">
                    <div style="height: 200px; width: 50px; background-color: #e3f2fd; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">92%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.1</div>
                </div>
                <div style="text-align: center;">
                    <div style="height: 180px; width: 50px; background-color: #e3f2fd; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">88%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.2</div>
                </div>
                <div style="text-align: center;">
                    <div style="height: 160px; width: 50px; background-color: #e8f5e9; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">82%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.3</div>
                </div>
                <div style="text-align: center;">
                    <div style="height: 150px; width: 50px; background-color: #fff8e1; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">78%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.4</div>
                </div>
                <div style="text-align: center;">
                    <div style="height: 140px; width: 50px; background-color: #ffebee; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">72%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.5</div>
                </div>
                <div style="text-align: center;">
                    <div style="height: 130px; width: 50px; background-color: #ffebee; margin: 0 auto; position: relative;">
                        <div style="position: absolute; top: -25px; left: 0; right: 0; text-align: center;">65%</div>
                    </div>
                    <div style="margin-top: 10px;">ม.6</div>
                </div>
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
        </div>
        
        <!-- เนื้อหาคล้ายกับแท็บแรก แต่แสดงข้อมูลนักเรียนที่ขาดแถวบ่อย -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="15%">อัตราการเข้าแถว</th>
                        <th width="10%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="10%">การขาดแถวล่าสุด</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ข้อมูลตัวอย่าง -->
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ก</div>
                                <div class="student-details">
                                    <div class="student-name">นายก้องเกียรติ มีเกียรติ</div>
                                    <div class="student-class">เลขที่ 3</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.3/2</td>
                        <td><span class="status-badge warning">78.5%</span></td>
                        <td>10 วัน</td>
                        <td>อ.สมศรี ใจดี</td>
                        <td>16/03/2568</td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- ข้อมูลเพิ่มเติม -->
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
        </div>
        
        <!-- เนื้อหาคล้ายกับแท็บแรก แต่แสดงข้อมูลนักเรียนที่รอการแจ้งเตือน -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="25%">นักเรียน</th>
                        <th width="10%">ชั้น/ห้อง</th>
                        <th width="15%">อัตราการเข้าแถว</th>
                        <th width="10%">วันที่ขาด</th>
                        <th width="15%">ครูที่ปรึกษา</th>
                        <th width="10%">ความเร่งด่วน</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- ข้อมูลตัวอย่าง -->
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar">ม</div>
                                <div class="student-details">
                                    <div class="student-name">นายมานะ พากเพียร</div>
                                    <div class="student-class">เลขที่ 7</div>
                                </div>
                            </div>
                        </td>
                        <td>ม.4/3</td>
                        <td><span class="status-badge danger">71.5%</span></td>
                        <td>12 วัน</td>
                        <td>อ.รักดี มากเมตตา</td>
                        <td><span class="status-badge danger">สูง</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="table-action-btn primary" title="ดูรายละเอียด">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="table-action-btn success" title="ส่งข้อความ">
                                    <span class="material-icons">send</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- ข้อมูลเพิ่มเติม -->
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
        <h2 class="modal-title">ข้อมูลนักเรียน - นายธนกฤต สุขใจ</h2>
        
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar">ธ</div>
                <div class="student-profile-info">
                    <h3>นายธนกฤต สุขใจ</h3>
                    <p>รหัสนักเรียน: 12345</p>
                    <p>ชั้น ม.6/2 เลขที่ 12</p>
                    <p>อัตราการเข้าแถว: <span class="status-badge danger">68.5%</span></p>
                </div>
            </div>
            
            <div class="student-attendance-summary">
                <h4>สรุปการเข้าแถว</h4>
                <div class="row">
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">26</div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">15</div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">40</div>
                            <div class="attendance-stat-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="student-attendance-history">
                <h4>ประวัติการเข้าแถว</h4>
                <div class="attendance-calendar">
                    <!-- รายละเอียดปฏิทินการเข้าแถว -->
                    <div class="attendance-month">เดือนมีนาคม 2568</div>
                    <div class="attendance-days">
                        <!-- วันที่ในเดือน -->
                        <div class="attendance-day present">1</div>
                        <div class="attendance-day present">2</div>
                        <div class="attendance-day weekend">3</div>
                        <div class="attendance-day weekend">4</div>
                        <div class="attendance-day present">5</div>
                        <div class="attendance-day absent">6</div>
                        <div class="attendance-day present">7</div>
                        <div class="attendance-day present">8</div>
                        <div class="attendance-day absent">9</div>
                        <div class="attendance-day weekend">10</div>
                        <div class="attendance-day weekend">11</div>
                        <div class="attendance-day present">12</div>
                        <div class="attendance-day present">13</div>
                        <div class="attendance-day absent">14</div>
                        <div class="attendance-day present">15</div>
                        <div class="attendance-day present">16</div>
                        <!-- วันที่เพิ่มเติม -->
                    </div>
                </div>
            </div>
            
            <div class="student-contact-info">
                <h4>ข้อมูลติดต่อ</h4>
                <div class="row">
                    <div class="col-6">
                        <p><strong>ครูที่ปรึกษา:</strong> อ.ประสิทธิ์ ดีเลิศ</p>
                        <p><strong>เบอร์โทรครู:</strong> 081-234-5678</p>
                    </div>
                    <div class="col-6">
                        <p><strong>ผู้ปกครอง:</strong> นางวันดี สุขใจ (แม่)</p>
                        <p><strong>เบอร์โทรผู้ปกครอง:</strong> 089-765-4321</p>
                    </div>
                </div>
            </div>
            
            <div class="student-notification-history">
                <h4>ประวัติการแจ้งเตือน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>ผู้ส่ง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>10/03/2568</td>
                                <td>แจ้งเตือนปกติ</td>
                                <td>อ.ประสิทธิ์ ดีเลิศ</td>
                                <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                            </tr>
                            <tr>
                                <td>01/03/2568</td>
                                <td>แจ้งเตือนเบื้องต้น</td>
                                <td>อ.ประสิทธิ์ ดีเลิศ</td>
                                <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('studentDetailModal')">ปิด</button>
            <button class="btn btn-primary" onclick="showSendMessageModal(1)">
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
            <select class="form-control">
                <option value="">-- เลือกเทมเพลต --</option>
                <option value="risk-warning">แจ้งเตือนกลุ่มเสี่ยง</option>
                <option value="meeting">นัดประชุมผู้ปกครอง</option>
                <option value="reminder">แจ้งเตือนทั่วไป</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">ข้อความ</label>
            <textarea class="message-textarea" rows="10">เรียน ท่านผู้ปกครองนักเรียน

ทางโรงเรียนขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางโรงเรียนจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้

1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา
2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว
3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ

กรุณาติดต่อฝ่ายกิจการนักเรียน โทร. 02-123-4567 หากมีข้อสงสัยเพิ่มเติม

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>
        </div>
        
        <div class="recipients-summary">
            <p>จำนวนนักเรียนที่จะได้รับข้อความ: <strong>12 คน</strong></p>
            <p>ระดับชั้น: <strong>ม.4 - ม.6</strong></p>
            <p>สถานะ: <strong>เสี่ยงตกกิจกรรม</strong></p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('bulkNotificationModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="sendBulkNotification()">
                <span class="material-icons">send</span>
                ส่งข้อความ (12 ราย)
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
        <h2 class="modal-title">ส่งข้อความแจ้งเตือน - นายธนกฤต สุขใจ</h2>
        
        <div class="template-buttons">
            <button class="template-btn active" onclick="selectModalTemplate('regular')">ข้อความปกติ</button>
            <button class="template-btn" onclick="selectModalTemplate('warning')">แจ้งเตือนความเสี่ยง</button>
            <button class="template-btn" onclick="selectModalTemplate('critical')">แจ้งเตือนฉุกเฉิน</button>
            <button class="template-btn" onclick="selectModalTemplate('summary')">รายงานสรุป</button>
        </div>
        
        <div class="form-group">
            <textarea class="message-textarea" id="modalMessageText">เรียน ผู้ปกครองของ นายธนกฤต สุขใจ

ทางโรงเรียนขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ม.6/2 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)

กรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
โรงเรียนประสาทวิทยาคม</textarea>
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

// ฟังก์ชันแสดงรายละเอียดนักเรียน
function showStudentDetail(studentId) {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    showModal('studentDetailModal');
}

// ฟังก์ชันแสดงโมดัลส่งข้อความ
function showSendMessageModal(studentId) {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    showModal('sendMessageModal');
}

// ฟังก์ชันแสดงโมดัลส่งข้อความกลุ่ม
function showBulkNotificationModal() {
    showModal('bulkNotificationModal');
}

// ฟังก์ชันส่งข้อความแจ้งเตือนรายบุคคล
function sendIndividualMessage() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    closeModal('sendMessageModal');
    alert('ส่งข้อความแจ้งเตือนเรียบร้อยแล้ว');
}

// ฟังก์ชันส่งข้อความแจ้งเตือนกลุ่ม
function sendBulkNotification() {
    // ในทางปฏิบัติจริง จะมีการส่ง AJAX request ไปยัง backend
    closeModal('bulkNotificationModal');
    alert('ส่งข้อความแจ้งเตือนกลุ่มเรียบร้อยแล้ว');
}

// ฟังก์ชันเลือกเทมเพลตในโมดัล
function selectModalTemplate(templateType) {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    event.target.classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('modalMessageText');
    
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
});
</script>

<style>
/* เพิ่มเติมสำหรับหน้านักเรียนเสี่ยงตกกิจกรรม */
.card-footer {
    margin-top: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination {
    display: flex;
    gap: 5px;
}

.page-link {
    padding: 5px 10px;
    border-radius: 3px;
    background-color: #f5f5f5;
    color: var(--text-dark);
    text-decoration: none;
}

.page-link.active {
    background-color: var(--primary-color);
    color: white;
}

.page-separator {
    padding: 5px;
    color: var(--text-light);
}

.bulk-action-btn {
    margin-left: auto;
}

.student-profile {
    margin-top: 20px;
}

.student-profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.student-profile-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background-color: var(--secondary-color-light);
    color: var(--secondary-color);
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 20px;
}

.student-profile-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.student-profile-info p {
    margin: 0 0 5px 0;
    color: var(--text-light);
}

.student-attendance-summary,
.student-attendance-history,
.student-contact-info,
.student-notification-history {
    margin-bottom: 20px;
}

.student-attendance-summary h4,
.student-attendance-history h4,
.student-contact-info h4,
.student-notification-history h4 {
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

.attendance-stat {
    text-align: center;
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}

.attendance-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: var(--primary-color);
}

.attendance-stat-label {
    font-size: 12px;
    color: var(--text-light);
}

.attendance-calendar {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
}

.attendance-month {
    font-weight: bold;
    margin-bottom: 10px;
    text-align: center;
}

.attendance-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
}

.attendance-day {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.attendance-day.present {
    background-color: var(--success-color-light);
    color: var(--success-color);
}

.attendance-day.absent {
    background-color: var(--danger-color-light);
    color: var(--danger-color);
}

.attendance-day.weekend {
    background-color: #eee;
    color: #999;
}

.recipients-summary {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

.recipients-summary p {
    margin: 5px 0;
}
</style>