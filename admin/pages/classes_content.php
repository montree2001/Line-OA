<!-- คอนเทนต์หน้าจัดการชั้นเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">class</span>
        รายการชั้นเรียน
    </div>
    
    <div class="filter-container">
        <div class="filter-group">
            <div class="filter-label">ระดับชั้น</div>
            <select class="form-control">
                <option value="">ทั้งหมด</option>
                <option>ม.1</option>
                <option>ม.2</option>
                <option>ม.3</option>
                <option>ม.4</option>
                <option>ม.5</option>
                <option>ม.6</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">ครูที่ปรึกษา</div>
            <select class="form-control">
                <option value="">ทั้งหมด</option>
                <option>อาจารย์ใจดี มากเมตตา</option>
                <option>อาจารย์ราตรี นอนดึก</option>
                <option>อาจารย์มานะ พยายาม</option>
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
                    <th>ชั้นเรียน</th>
                    <th>ครูที่ปรึกษา</th>
                    <th>จำนวนนักเรียน</th>
                    <th>อัตราการเข้าแถว</th>
                    <th>สถานะ</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['classes'] as $class): ?>
                <tr>
                    <td>
                        <div class="class-info">
                            <div class="class-avatar">
                                <?php echo substr($class['level'], 0, 1); ?>
                            </div>
                            <div class="class-details">
                                <div class="class-name"><?php echo $class['level'] . '/' . $class['room']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo $class['advisor']; ?></td>
                    <td><?php echo $class['total_students']; ?></td>
                    <td>
                        <span class="attendance-rate <?php 
                            echo $class['status'] === 'warning' ? 'warning' : 
                                 ($class['status'] === 'good' ? 'good' : 'danger'); 
                        ?>">
                            <?php echo number_format($class['attendance_rate'], 1); ?>%
                        </span>
                    </td>
                    <td>
                        <span class="status-badge <?php 
                            echo $class['status'] === 'warning' ? 'warning' : 
                                 ($class['status'] === 'good' ? 'success' : 'danger'); 
                        ?>">
                            <?php 
                            echo $class['status'] === 'warning' ? 'ต้องระวัง' : 
                                 ($class['status'] === 'good' ? 'ปกติ' : 'เสี่ยง'); 
                            ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="table-action-btn primary" onclick="showClassDetails('<?php echo $class['level'] . '/' . $class['room']; ?>')">
                                <span class="material-icons">visibility</span>
                            </button>
                            <button class="table-action-btn success" onclick="editClass('<?php echo $class['level'] . '/' . $class['room']; ?>')">
                                <span class="material-icons">edit</span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- โมดัลเพิ่มชั้นเรียน -->
<div class="modal" id="addClassModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('addClassModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">เพิ่มชั้นเรียนใหม่</h2>
        
        <div class="form-group">
            <label class="form-label">ระดับชั้น</label>
            <select class="form-control">
                <option value="">เลือกระดับชั้น</option>
                <option>ม.1</option>
                <option>ม.2</option>
                <option>ม.3</option>
                <option>ม.4</option>
                <option>ม.5</option>
                <option>ม.6</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">ห้องเรียน</label>
            <input type="text" class="form-control" placeholder="กรอกหมายเลขห้อง">
        </div>
        
        <div class="form-group">
            <label class="form-label">ครูที่ปรึกษา</label>
            <select class="form-control">
                <option value="">เลือกครูที่ปรึกษา</option>
                <option>อาจารย์ใจดี มากเมตตา</option>
                <option>อาจารย์ราตรี นอนดึก</option>
                <option>อาจารย์มานะ พยายาม</option>
            </select>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('addClassModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="saveClass()">
                <span class="material-icons">save</span>
                บันทึกชั้นเรียน
            </button>
        </div>
    </div>
</div>

<!-- โมดัลรายละเอียดชั้นเรียน -->
<div class="modal" id="classDetailsModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('classDetailsModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">รายละเอียดชั้น ม.6/1</h2>
        
        <div class="class-details-content">
            <div class="row">
                <div class="col-6">
                    <h3>ข้อมูลชั้นเรียน</h3>
                    <table class="details-table">
                        <tr>
                            <th>ครูที่ปรึกษา:</th>
                            <td>อาจารย์ใจดี มากเมตตา</td>
                        </tr>
                        <tr>
                            <th>จำนวนนักเรียน:</th>
                            <td>35 คน</td>
                        </tr>
                        <tr>
                            <th>นักเรียนชาย:</th>
                            <td>20 คน</td>
                        </tr>
                        <tr>
                            <th>นักเรียนหญิง:</th>
                            <td>15 คน</td>
                        </tr>
                    </table>
                </div>
                <div class="col-6">
                    <h3>สถิติการเข้าแถว</h3>
                    <div class="attendance-chart-container">
                        <canvas id="classAttendanceChart"></canvas>
                    </div>
                </div>
            </div>
            
            <h3>รายชื่อนักเรียน</h3>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>เลขที่</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>การเข้าแถว</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>นายอภิสิทธิ์ สงวนสิทธิ์</td>
                            <td>38/40 วัน (95%)</td>
                            <td><span class="status-badge success">ปกติ</span></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>นายธนกฤต สุขใจ</td>
                            <td>26/40 วัน (65%)</td>
                            <td><span class="status-badge danger">เสี่ยงตกกิจกรรม</span></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>นางสาวพิมพ์ใจ ร่าเริง</td>
                            <td>30/40 วัน (75%)</td>
                            <td><span class="status-badge warning">ต้องระวัง</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('classDetailsModal')">ปิด</button>
            <button class="btn btn-primary" onclick="downloadClassReport()">
                <span class="material-icons">file_download</span>
                ดาวน์โหลดรายงาน
            </button>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันแสดงโมดัลเพิ่มชั้นเรียน
function showAddClassModal() {
    showModal('addClassModal');
}

// ฟังก์ชันปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// ฟังก์ชันแสดงโมดัลเพิ่มโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
    }
}

// ฟังก์ชันแสดงรายละเอียดชั้นเรียน
function showClassDetails(className) {
    const modal = document.getElementById('classDetailsModal');
    if (modal) {
        // อัปเดตชื่อชั้นเรียนในโมดัล
        const titleElement = modal.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = `รายละเอียดชั้น ${className}`;
        }
        
        // แสดงโมดัล
        showModal('classDetailsModal');
        
        // สร้างกราฟ (ในทางปฏิบัติจริงจะใช้ Chart.js)
        createClassAttendanceChart();
    }
}

// ฟังก์ชันแก้ไขชั้นเรียน
function editClass(className) {
    alert(`กำลังแก้ไขชั้นเรียน ${className}`);
}

// ฟังก์ชันบันทึกชั้นเรียน
function saveClass() {
    alert('กำลังบันทึกชั้นเรียน');
    closeModal('addClassModal');
}

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadClassReport() {
    alert('กำลังดาวน์โหลดรายงานชั้นเรียน');
}

// ฟังก์ชันสร้างกราฟการเข้าแถว
function createClassAttendanceChart() {
    // Mock-up chart data (ในทางปฏิบัติจะใช้ Chart.js)
    const ctx = document.getElementById('classAttendanceChart');
    if (!ctx) return;

    ctx.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <div style="display: inline-flex; align-items: center; gap: 10px;">
                <div style="width: 20px; height: 20px; background-color: #4caf50;"></div>
                <span>เข้าแถว</span>
                <div style="width: 20px; height: 20px; background-color: #f44336;"></div>
                <span>ขาดแถว</span>
            </div>
            <div style="height: 200px; background: linear-gradient(to right, #4caf50 95%, #f44336 5%); margin-top: 10px;">
                <div style="text-align: center; font-size: 24px; color: white; padding-top: 80px;">
                    95% <small style="font-size: 14px;">เข้าแถว</small>
                </div>
            </div>
        </div>
    `;
}

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม event listener สำหรับปุ่มปิดโมดัล
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });

    // ปิดโมดัลเมื่อคลิกนอกโมดัล
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});
</script>

<style>
/* สไตล์เพิ่มเติมสำหรับหน้าจัดการชั้นเรียน */
.class-info {
    display: flex;
    align-items: center;
}

.class-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e3f2fd;
    color: #1976d2;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    font-weight: bold;
}

.class-details {
    flex: 1;
}

.class-name {
    font-weight: 600;
}

.details-table {
    width: 100%;
}

.details-table th {
    width: 40%;
    text-align: right;
    padding-right: 10px;
    color: #666;
}

.details-table td {
    width: 60%;
}

.attendance-chart-container {
    height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f9f9f9;
    border-radius: 8px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .details-table th {
        text-align: left;
        padding-right: 0;
    }
    
    .details-table {
        margin-bottom: 20px;
    }
}
</style>