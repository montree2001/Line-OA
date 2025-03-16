<!-- แผงค้นหาและกรองข้อมูล -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ตัวกรองรายงาน
    </div>
    
    <div class="filter-container">
        <div class="filter-group">
            <div class="filter-label">ประเภทรายงาน</div>
            <select class="form-control" id="reportType" onchange="changeReportType()">
                <option value="daily">รายงานประจำวัน</option>
                <option value="weekly">รายงานประจำสัปดาห์</option>
                <option value="monthly" selected>รายงานประจำเดือน</option>
                <option value="semester">รายงานประจำภาคเรียน</option>
                <option value="class">รายงานตามชั้นเรียน</option>
                <option value="student">รายงานรายบุคคล</option>
            </select>
        </div>
        
        <div class="filter-group">
            <div class="filter-label">ช่วงเวลา</div>
            <select class="form-control" id="reportPeriod">
                <option value="current" selected>เดือนปัจจุบัน (มีนาคม 2568)</option>
                <option value="prev">เดือนที่แล้ว (กุมภาพันธ์ 2568)</option>
                <option value="last3">3 เดือนย้อนหลัง</option>
                <option value="semester">ภาคเรียนที่ 2/2568</option>
                <option value="custom">กำหนดเอง</option>
            </select>
        </div>
        
        <div class="filter-group date-range" style="display: none;">
            <div class="filter-label">วันที่เริ่มต้น</div>
            <input type="date" class="form-control" id="startDate">
        </div>
        
        <div class="filter-group date-range" style="display: none;">
            <div class="filter-label">วันที่สิ้นสุด</div>
            <input type="date" class="form-control" id="endDate">
        </div>
        
        <div class="filter-group class-filter">
            <div class="filter-label">ระดับชั้น</div>
            <select class="form-control" id="classLevel">
                <option value="">ทุกระดับชั้น</option>
                <option value="lower">มัธยมต้น</option>
                <option value="upper" selected>มัธยมปลาย</option>
                <option value="m1">ม.1</option>
                <option value="m2">ม.2</option>
                <option value="m3">ม.3</option>
                <option value="m4">ม.4</option>
                <option value="m5">ม.5</option>
                <option value="m6">ม.6</option>
            </select>
        </div>
        
        <div class="filter-group class-filter">
            <div class="filter-label">ห้องเรียน</div>
            <select class="form-control" id="classRoom">
                <option value="">ทุกห้อง</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
        </div>
        
        <div class="filter-group student-filter" style="display: none;">
            <div class="filter-label">รหัส/ชื่อนักเรียน</div>
            <input type="text" class="form-control" placeholder="ป้อนรหัสหรือชื่อนักเรียน">
        </div>
        
        <button class="filter-button" onclick="generateReport()">
            <span class="material-icons">search</span>
            สร้างรายงาน
        </button>
    </div>
</div>

<!-- สรุปข้อมูลรายงาน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">assessment</span>
        สรุปรายงานประจำเดือนมีนาคม 2568
    </div>
    
    <div class="monthly-summary">
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">จำนวนนักเรียนทั้งหมด</div>
            <div class="monthly-summary-value">1,250</div>
            <div class="monthly-summary-subtext">นักเรียนมัธยมปลาย</div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">จำนวนวันเข้าแถว</div>
            <div class="monthly-summary-value">22</div>
            <div class="monthly-summary-subtext">วันเข้าแถวเดือนมีนาคม</div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">อัตราการเข้าแถวเฉลี่ย</div>
            <div class="monthly-summary-value success">92.7%</div>
            <div class="monthly-summary-subtext">เพิ่มขึ้น 1.2% จากเดือนที่แล้ว</div>
        </div>
        
        <div class="monthly-summary-item">
            <div class="monthly-summary-title">นักเรียนเสี่ยงตกกิจกรรม</div>
            <div class="monthly-summary-value danger">35</div>
            <div class="monthly-summary-subtext">ลดลง 5 คนจากเดือนที่แล้ว</div>
        </div>
    </div>
    
    <div class="charts-row">
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">แนวโน้มการเข้าแถวตลอดปีการศึกษา</div>
                <div class="chart-actions">
                    <button class="chart-action-btn">
                        <span class="material-icons">refresh</span>
                        รีเฟรช
                    </button>
                    <button class="chart-action-btn">
                        <span class="material-icons">download</span>
                        ดาวน์โหลด
                    </button>
                </div>
            </div>
            
            <div class="chart-container" id="yearlyTrendChart">
                <!-- Chart will be rendered here by JavaScript -->
                <!-- ในทางปฏิบัติจริง ควรใช้ Chart.js หรือ library อื่นๆ -->
                <div style="display: flex; height: 250px; align-items: flex-end; justify-content: space-around; padding-bottom: 30px; border-bottom: 1px solid #eee;">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 200px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">95%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">พ.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 195px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">94%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">มิ.ย.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 190px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">92%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ก.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 185px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">91%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ส.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 190px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">93%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ก.ย.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 194px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">94%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ต.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 186px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">90%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">พ.ย.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 184px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">89%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ธ.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 190px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">92%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ม.ค.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 188px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">91%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">ก.พ.</div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="height: 192px; width: 30px; background-color: #e3f2fd; border-radius: 5px 5px 0 0; position: relative;">
                            <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #1976d2;">93%</div>
                        </div>
                        <div style="margin-top: 10px; font-size: 12px; color: #666;">มี.ค.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chart-card">
            <div class="chart-header">
                <div class="chart-title">สาเหตุการขาดแถว</div>
            </div>
            
            <div class="chart-container" id="absenceReasonChart">
                <!-- Pie chart will be rendered here by JavaScript -->
                <!-- ในทางปฏิบัติจริง ควรใช้ Chart.js หรือ library อื่นๆ -->
                <div style="display: flex; flex-direction: column; align-items: center; padding: 20px;">
                    <div style="position: relative; width: 200px; height: 200px; border-radius: 50%; background: conic-gradient(#2196f3 0% 42%, #ff9800 42% 70%, #9c27b0 70% 85%, #f44336 85% 100%);">
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100px; height: 100px; background-color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;"></div>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; justify-content: center; margin-top: 20px; gap: 15px;">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 16px; height: 16px; margin-right: 8px; background-color: #2196f3; border-radius: 4px;"></div>
                            <span>ป่วย (42%)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 16px; height: 16px; margin-right: 8px; background-color: #ff9800; border-radius: 4px;"></div>
                            <span>ธุระส่วนตัว (28%)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 16px; height: 16px; margin-right: 8px; background-color: #9c27b0; border-radius: 4px;"></div>
                            <span>มาสาย (15%)</span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <div style="width: 16px; height: 16px; margin-right: 8px; background-color: #f44336; border-radius: 4px;"></div>
                            <span>ไม่ทราบสาเหตุ (15%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- เปรียบเทียบอัตราการเข้าแถวตามชั้นเรียน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">bar_chart</span>
        อัตราการเข้าแถวแยกตามระดับชั้น
    </div>
    
    <div class="chart-container" id="classComparisonChart">
        <!-- Class comparison chart will be rendered here by JavaScript -->
        <!-- ในทางปฏิบัติจริง ควรใช้ Chart.js หรือ library อื่นๆ -->
        <div class="class-attendance-chart">
            <div class="class-bar" style="height: 92%;">
                <div class="class-bar-value">92%</div>
                <div class="class-bar-label">ม.4/1</div>
            </div>
            <div class="class-bar" style="height: 90%;">
                <div class="class-bar-value">90%</div>
                <div class="class-bar-label">ม.4/2</div>
            </div>
            <div class="class-bar" style="height: 88%;">
                <div class="class-bar-value">88%</div>
                <div class="class-bar-label">ม.4/3</div>
            </div>
            <div class="class-bar" style="height: 94%;">
                <div class="class-bar-value">94%</div>
                <div class="class-bar-label">ม.5/1</div>
            </div>
            <div class="class-bar" style="height: 89%;">
                <div class="class-bar-value">89%</div>
                <div class="class-bar-label">ม.5/2</div>
            </div>
            <div class="class-bar" style="height: 82%;">
                <div class="class-bar-value">82%</div>
                <div class="class-bar-label">ม.5/3</div>
            </div>
            <div class="class-bar" style="height: 93%;">
                <div class="class-bar-value">93%</div>
                <div class="class-bar-label">ม.6/1</div>
            </div>
            <div class="class-bar" style="height: 85%;">
                <div class="class-bar-value">85%</div>
                <div class="class-bar-label">ม.6/2</div>
            </div>
            <div class="class-bar" style="height: 90%;">
                <div class="class-bar-value">90%</div>
                <div class="class-bar-label">ม.6/3</div>
            </div>
        </div>
    </div>
</div>

<!-- รายชื่อนักเรียนที่เสี่ยงตกกิจกรรม -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">warning</span>
        รายชื่อนักเรียนที่เสี่ยงตกหรือตกกิจกรรมเข้าแถว
    </div>
    
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="20%">นักเรียน</th>
                    <th width="10%">ชั้น/ห้อง</th>
                    <th width="10%">อัตราการเข้าแถว</th>
                    <th width="10%">วันที่ขาด</th>
                    <th width="15%">ครูที่ปรึกษา</th>
                    <th width="15%">สถานะ</th>
                    <th width="15%">การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
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
                    <td><span class="attendance-percent danger">68.5%</span></td>
                    <td>15 วัน</td>
                    <td>อ.ประสิทธิ์ ดีเลิศ</td>
                    <td><span class="status-badge danger">ตกกิจกรรม</span></td>
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
                    <td>2</td>
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
                    <td><span class="attendance-percent danger">70.2%</span></td>
                    <td>14 วัน</td>
                    <td>อ.วันดี สดใส</td>
                    <td><span class="status-badge danger">ตกกิจกรรม</span></td>
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
                    <td>3</td>
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
                    <td><span class="attendance-percent warning">75.3%</span></td>
                    <td>12 วัน</td>
                    <td>อ.ใจดี มากเมตตา</td>
                    <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
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
                    <td>4</td>
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
                    <td><span class="attendance-percent warning">73.5%</span></td>
                    <td>13 วัน</td>
                    <td>อ.วิชัย สุขสวัสดิ์</td>
                    <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
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
                    <td>5</td>
                    <td>
                        <div class="student-info">
                            <div class="student-avatar">ช</div>
                            <div class="student-details">
                                <div class="student-name">นายชาติชาย รักชาติ</div>
                                <div class="student-class">เลขที่ 7</div>
                            </div>
                        </div>
                    </td>
                    <td>ม.6/3</td>
                    <td><span class="attendance-percent warning">76.8%</span></td>
                    <td>11 วัน</td>
                    <td>อ.สมใจ นึกแปลก</td>
                    <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
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
        <div class="pagination">
            <a href="#" class="page-link active">1</a>
            <a href="#" class="page-link">2</a>
            <a href="#" class="page-link">3</a>
            <span class="page-separator">...</span>
            <a href="#" class="page-link">7</a>
        </div>
        
        <div class="page-info">
            แสดง 1-5 จาก 35 รายการ
        </div>
    </div>
</div>

<!-- ตัวแทน Modal ที่จะถูกเรียกใช้จาก JavaScript -->
<div class="modal" id="studentDetailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('studentDetailModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ข้อมูลการเข้าแถวละเอียด - นายธนกฤต สุขใจ</h2>
        
        <div class="student-profile">
            <div class="student-profile-header">
                <div class="student-profile-avatar">ธ</div>
                <div class="student-profile-info">
                    <h3>นายธนกฤต สุขใจ</h3>
                    <p>รหัสนักเรียน: 16478</p>
                    <p>ชั้น ม.6/2 เลขที่ 12</p>
                    <p>อัตราการเข้าแถว: <span class="status-badge danger">68.5%</span></p>
                </div>
            </div>
            
            <div class="student-attendance-summary">
                <h4>สรุปการเข้าแถวประจำเดือนมีนาคม 2568</h4>
                <div class="row">
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">15</div>
                            <div class="attendance-stat-label">วันที่เข้าแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">7</div>
                            <div class="attendance-stat-label">วันที่ขาดแถว</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="attendance-stat">
                            <div class="attendance-stat-value">22</div>
                            <div class="attendance-stat-label">วันทั้งหมด</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="attendance-history">
                <h4>ประวัติการเข้าแถวรายวัน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                                <th>หมายเหตุ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>16/03/2568</td>
                                <td><span class="status-badge success">มา</span></td>
                                <td>08:02</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>15/03/2568</td>
                                <td><span class="status-badge success">มา</span></td>
                                <td>08:05</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td>14/03/2568</td>
                                <td><span class="status-badge danger">ขาด</span></td>
                                <td>-</td>
                                <td>ไม่มาโรงเรียน</td>
                            </tr>
                            <tr>
                                <td>13/03/2568</td>
                                <td><span class="status-badge warning">มาสาย</span></td>
                                <td>08:32</td>
                                <td>รถติด</td>
                            </tr>
                            <tr>
                                <td>12/03/2568</td>
                                <td><span class="status-badge success">มา</span></td>
                                <td>08:10</td>
                                <td>-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="attendance-chart">
                <h4>แนวโน้มการเข้าแถวรายเดือน</h4>
                <div class="chart-container" style="height: 250px;">
                    <!-- ในทางปฏิบัติจริง ควรใช้ Chart.js สร้างกราฟเส้น -->
                    <div style="display: flex; height: 200px; align-items: flex-end; justify-content: space-between; padding-bottom: 30px; border-bottom: 1px solid #eee;">
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <div style="height: 136px; width: 30px; background-color: #ffebee; border-radius: 5px 5px 0 0; position: relative;">
                                <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #f44336;">68%</div>
                            </div>
                            <div style="margin-top: 10px; font-size: 12px; color: #666;">ม.ค.</div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <div style="height: 140px; width: 30px; background-color: #ffebee; border-radius: 5px 5px 0 0; position: relative;">
                                <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #f44336;">70%</div>
                            </div>
                            <div style="margin-top: 10px; font-size: 12px; color: #666;">ก.พ.</div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: center;">
                            <div style="height: 137px; width: 30px; background-color: #ffebee; border-radius: 5px 5px 0 0; position: relative;">
                                <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px; font-weight: 600; color: #f44336;">68.5%</div>
                            </div>
                            <div style="margin-top: 10px; font-size: 12px; color: #666;">มี.ค.</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="notification-history">
                <h4>ประวัติการแจ้งเตือนผู้ปกครอง</h4>
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
                                <td>16/03/2568</td>
                                <td>แจ้งเตือนความเสี่ยง</td>
                                <td>จารุวรรณ บุญมี</td>
                                <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                            </tr>
                            <tr>
                                <td>01/03/2568</td>
                                <td>แจ้งเตือนปกติ</td>
                                <td>อ.ประสิทธิ์ ดีเลิศ</td>
                                <td><span class="status-badge success">ส่งสำเร็จ</span></td>
                            </tr>
                            <tr>
                                <td>15/02/2568</td>
                                <td>แจ้งเตือนปกติ</td>
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
            <button class="btn btn-primary" onclick="openSendMessageModal()">
                <span class="material-icons">send</span>
                ส่งข้อความแจ้งเตือน
            </button>
            <button class="btn btn-primary" onclick="printStudentReport()">
                <span class="material-icons">print</span>
                พิมพ์รายงาน
            </button>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันเปลี่ยนประเภทรายงาน
function changeReportType() {
    const reportType = document.getElementById('reportType').value;
    const studentFilter = document.querySelectorAll('.student-filter');
    const classFilter = document.querySelectorAll('.class-filter');
    const dateRange = document.querySelectorAll('.date-range');
    
    // แสดง/ซ่อนตัวกรองตามประเภทรายงาน
    if (reportType === 'student') {
        studentFilter.forEach(filter => filter.style.display = 'block');
    } else {
        studentFilter.forEach(filter => filter.style.display = 'none');
    }
    
    // ซ่อน/แสดงตัวกรองวันที่
    const reportPeriod = document.getElementById('reportPeriod');
    if (reportPeriod.value === 'custom') {
        dateRange.forEach(filter => filter.style.display = 'block');
    } else {
        dateRange.forEach(filter => filter.style.display = 'none');
    }
}

// ฟังก์ชันสร้างรายงาน
function generateReport() {
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    const classLevel = document.getElementById('classLevel').value;
    const classRoom = document.getElementById('classRoom').value;
    
    // ในทางปฏิบัติจริง จะเป็นการส่ง AJAX request ไปยัง backend
    console.log(`สร้างรายงานประเภท ${reportType} ช่วงเวลา ${reportPeriod} ชั้น ${classLevel} ห้อง ${classRoom}`);
    
    // แสดงข้อความกำลังโหลด
    alert('กำลังสร้างรายงาน...');
    
    // Simulating reload with new data
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// ฟังก์ชันแสดงรายละเอียดนักเรียน
function showStudentDetail(studentId) {
    // ในทางปฏิบัติจริง จะเป็นการส่ง AJAX request ไปขอข้อมูลนักเรียนจาก backend
    showModal('studentDetailModal');
}

// ฟังก์ชันแสดงโมดัล
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

// ฟังก์ชันปิดโมดัล
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

// ฟังก์ชันเปิดหน้าส่งข้อความ
function openSendMessageModal() {
    // ในทางปฏิบัติจริง จะเป็นการนำทางไปยังหน้าส่งข้อความหรือแสดงโมดัลส่งข้อความ
    window.location.href = 'send_notification.php?student_id=16478';
}

// ฟังก์ชันพิมพ์รายงานนักเรียน
function printStudentReport() {
    // ในทางปฏิบัติจริง จะเป็นการเปิดหน้าต่างพิมพ์หรือดาวน์โหลด PDF
    window.print();
}

// ฟังก์ชันดาวน์โหลดรายงาน
function downloadReportData() {
    // ในทางปฏิบัติจริง จะเป็นการส่ง request ไปยัง endpoint ที่สร้างไฟล์รายงาน
    const reportType = document.getElementById('reportType').value;
    const reportPeriod = document.getElementById('reportPeriod').value;
    
    alert(`กำลังดาวน์โหลดรายงาน ${reportType} สำหรับช่วง ${reportPeriod}`);
}

// เมื่อโหลดหน้าเสร็จ ให้เรียกฟังก์ชันเพื่อตั้งค่าแท็บและอื่นๆ
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าการแสดงผลตัวกรอง
    const reportPeriodSelect = document.getElementById('reportPeriod');
    if (reportPeriodSelect) {
        reportPeriodSelect.addEventListener('change', function() {
            const dateRange = document.querySelectorAll('.date-range');
            if (this.value === 'custom') {
                dateRange.forEach(filter => filter.style.display = 'block');
            } else {
                dateRange.forEach(filter => filter.style.display = 'none');
            }
        });
    }
    
    // ตั้งค่าการแสดงผลตัวกรองนักเรียน
    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        reportTypeSelect.addEventListener('change', changeReportType);
    }
    
    // เพิ่ม event listener ให้กับปุ่มดูรายละเอียดนักเรียน
    const detailButtons = document.querySelectorAll('.table-action-btn.primary');
    detailButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ในทางปฏิบัติจริง จะต้องดึง id ของนักเรียนจากแต่ละแถว
            showStudentDetail(1);
        });
    });
});
</script>

<style>
/* สไตล์เฉพาะสำหรับหน้ารายงาน */
.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-card {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.monthly-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.monthly-summary-item {
    flex: 1;
    min-width: 200px;
    background-color: white;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.monthly-summary-title {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-dark);
}

.monthly-summary-value {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 5px;
}

.monthly-summary-value.success {
    color: var(--success-color);
}

.monthly-summary-value.warning {
    color: var(--warning-color);
}

.monthly-summary-value.danger {
    color: var(--danger-color);
}

.monthly-summary-subtext {
    font-size: 12px;
    color: var(--text-light);
}

.class-attendance-chart {
    height: 300px;
    display: flex;
    align-items: flex-end;
    justify-content: space-around;
    padding: 20px 0;
    margin-bottom: 20px;
}

.class-bar {
    width: 60px;
    background-color: var(--secondary-color-light);
    border-radius: 5px 5px 0 0;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.class-bar-label {
    position: absolute;
    bottom: -30px;
    white-space: nowrap;
    font-weight: 600;
}

.class-bar-value {
    position: absolute;
    top: -25px;
    font-weight: 600;
    color: var(--primary-color);
}

.attendance-percent {
    font-weight: 600;
}

.attendance-percent.good {
    color: var(--success-color);
}

.attendance-percent.warning {
    color: var(--warning-color);
}

.attendance-percent.danger {
    color: var(--danger-color);
}

.attendance-history h4,
.attendance-chart h4,
.notification-history h4,
.student-attendance-summary h4 {
    font-size: 16px;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid var(--border-color);
}

@media (max-width: 992px) {
    .charts-row {
        grid-template-columns: 1fr;
    }
    
    .monthly-summary-item {
        min-width: 100%;
    }
}

@media (max-width: 768px) {
    .monthly-summary {
        flex-direction: column;
    }
    
    .charts-row {
        gap: 15px;
    }
    
    .class-attendance-chart {
        overflow-x: auto;
        justify-content: flex-start;
        padding: 20px;
    }
    
    .class-bar {
        margin: 0 10px;
    }
}
</style>