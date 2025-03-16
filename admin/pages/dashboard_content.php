<!-- ตัวอย่างเนื้อหา - สถิติด่วน -->
<div class="row">
    <div class="col-3 col-md-6 col-sm-6">
        <div class="card">
            <div class="card-title">
                <span class="material-icons">people</span>
                นักเรียนทั้งหมด
            </div>
            <h2>1,250 คน</h2>
            <p>เพิ่มขึ้น 2.5% จากปีที่แล้ว</p>
        </div>
    </div>
    <div class="col-3 col-md-6 col-sm-6">
        <div class="card">
            <div class="card-title">
                <span class="material-icons">check_circle</span>
                เข้าแถววันนี้
            </div>
            <h2>1,187 คน</h2>
            <p>คิดเป็น 94.96%</p>
        </div>
    </div>
    <div class="col-3 col-md-6 col-sm-6">
        <div class="card">
            <div class="card-title">
                <span class="material-icons">cancel</span>
                ขาดแถววันนี้
            </div>
            <h2>63 คน</h2>
            <p>ลดลง 1.2% จากเดือนที่แล้ว</p>
        </div>
    </div>
    <div class="col-3 col-md-6 col-sm-6">
        <div class="card">
            <div class="card-title">
                <span class="material-icons">warning</span>
                เสี่ยงตกกิจกรรม
            </div>
            <h2>12 คน</h2>
            <p>ลดลง 5 คนจากสัปดาห์ที่แล้ว</p>
        </div>
    </div>
</div>

<!-- ตัวอย่างปุ่มทางลัด -->
<div class="quick-actions">
    <button class="quick-action-btn pin" onclick="showPinModal()">
        <span class="material-icons">pin</span>
        สร้างรหัส PIN เช็คชื่อ
    </button>
    <button class="quick-action-btn qr">
        <span class="material-icons">qr_code_scanner</span>
        สแกน QR Code นักเรียน
    </button>
    <button class="quick-action-btn check">
        <span class="material-icons">check_circle</span>
        เช็คชื่อนักเรียน
    </button>
    <button class="quick-action-btn alert">
        <span class="material-icons">campaign</span>
        แจ้งเตือนผู้ปกครอง
    </button>
</div>

<!-- ตัวอย่างตาราง -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">warning</span>
        นักเรียนเสี่ยงตกกิจกรรม
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>นักเรียน</th>
                    <th>ชั้น/ห้อง</th>
                    <th>ร้อยละการเข้าแถว</th>
                    <th>วันที่ขาด</th>
                    <th>ครูที่ปรึกษา</th>
                    <th>การแจ้งเตือน</th>
                    <th>การจัดการ</th>
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
                            <button class="table-action-btn primary" title="ดูรายละเอียด">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" title="ส่งข้อความ">
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
                            <button class="table-action-btn primary" title="ดูรายละเอียด">
                                <span class="material-icons">visibility</span>
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
    
    <div class="card-footer">
        <button class="btn btn-primary">
            <span class="material-icons">send</span>
            ส่งรายงานไปยังผู้ปกครองทั้งหมด
        </button>
    </div>
</div>

<!-- โมดัลสร้างรหัส PIN -->
<div class="modal" id="pinModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">สร้างรหัส PIN สำหรับการเช็คชื่อ</h2>
        <div class="pin-display" id="pinCode">5731</div>
        <div class="pin-info">
            รหัส PIN นี้สำหรับให้นักเรียนเช็คชื่อวันนี้<br>
            เท่านั้น และจะหมดอายุภายในเวลาที่กำหนด
        </div>
        <div class="timer">
            <span class="material-icons">timer</span>
            <span>หมดอายุใน 9:58 นาที</span>
        </div>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal()">ปิด</button>
            <button class="btn btn-primary" onclick="generateNewPin()">สร้างรหัสใหม่</button>
        </div>
    </div>
</div>