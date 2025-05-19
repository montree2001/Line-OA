<!-- ตัวกรองข้อมูลการยกเลิกการเชื่อมต่อ LINE -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">link_off</span>
        ยกเลิกการเชื่อมต่อ LINE
    </div>
    
    <!-- ส่วนฟิลเตอร์ -->
    <div class="filter-section">
        <h3 class="section-title">เงื่อนไขการค้นหานักเรียน</h3>
        <p>เลือกเงื่อนไขในการค้นหานักเรียนที่ต้องการยกเลิกการเชื่อมต่อ LINE</p>
        
        <form id="disconnectFilterForm" method="post">
            <div class="row">
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">ค้นหาด้วยรหัสนักศึกษา</div>
                        <input type="text" class="form-control" id="studentCodeFilter" name="student_code" placeholder="รหัสนักศึกษา...">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">ค้นหาด้วยชื่อนักเรียน</div>
                        <input type="text" class="form-control" id="studentNameFilter" name="student_name" placeholder="ชื่อ หรือ นามสกุล...">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="clearFilterBtn" class="btn btn-secondary me-2">
                        <span class="material-icons">clear</span>
                        ล้างการค้นหา
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">ระดับชั้น</div>
                        <select class="form-control" id="levelFilter" name="level">
                            <option value="">-- ทุกระดับชั้น --</option>
                            <option value="ปวช.1">ปวช.1</option>
                            <option value="ปวช.2">ปวช.2</option>
                            <option value="ปวช.3">ปวช.3</option>
                            <option value="ปวส.1">ปวส.1</option>
                            <option value="ปวส.2">ปวส.2</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">กลุ่ม</div>
                        <select class="form-control" id="groupFilter" name="group_number">
                            <option value="">-- ทุกกลุ่ม --</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">แผนกวิชา</div>
                        <select class="form-control" id="departmentFilter" name="department_id">
                            <option value="">-- ทุกแผนก --</option>
                            <?php foreach ($data['departments'] as $department): ?>
                                <option value="<?php echo $department['department_id']; ?>"><?php echo $department['department_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-group">
                        <div class="filter-label">ครูที่ปรึกษา</div>
                        <select class="form-control" id="advisorFilter" name="advisor_id">
                            <option value="">-- ทุกครูที่ปรึกษา --</option>
                            <?php foreach ($data['advisors'] as $advisor): ?>
                                <option value="<?php echo $advisor['teacher_id']; ?>"><?php echo $advisor['title'] . $advisor['first_name'] . ' ' . $advisor['last_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">สถานะ LINE</div>
                        <select class="form-control" id="lineStatusFilter" name="line_status">
                            <option value="connected">เชื่อมต่อแล้วเท่านั้น</option>
                            <option value="all">ทั้งหมด</option>
                            <option value="not_connected">ยังไม่เชื่อมต่อเท่านั้น</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">สถานะการศึกษา</div>
                        <select class="form-control" id="studentStatusFilter" name="status">
                            <option value="กำลังศึกษา">กำลังศึกษา</option>
                            <option value="all">ทั้งหมด</option>
                            <option value="พักการเรียน">พักการเรียน</option>
                            <option value="พ้นสภาพ">พ้นสภาพ</option>
                            <option value="สำเร็จการศึกษา">สำเร็จการศึกษา</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" id="searchStudentsBtn" class="btn btn-primary btn-block">
                        <span class="material-icons">search</span>
                        ค้นหานักเรียน
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- ส่วนแสดงผลการค้นหา -->
    <div class="preview-section">
        <h3 class="section-title">รายชื่อนักเรียนที่จะยกเลิกการเชื่อมต่อ <span id="studentCount" class="count-badge">0</span></h3>
        <p>นักเรียนต่อไปนี้จะถูกยกเลิกการเชื่อมต่อ LINE แต่ข้อมูลจะยังคงอยู่ในระบบ</p>
        
        <div class="table-responsive">
            <table id="studentPreviewTable" class="data-table display">
                <thead>
                    <tr>
                        <th width="5%">
                            <input type="checkbox" id="selectAllStudents">
                        </th>
                        <th width="10%">รหัส</th>
                        <th width="30%">ชื่อ-นามสกุล</th>
                        <th width="20%">ชั้น/ห้อง</th>
                        <th width="20%">แผนกวิชา</th>
                        <th width="15%">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- จะถูกเติมด้วย JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ส่วนยืนยันการยกเลิกการเชื่อมต่อ -->
    <div class="confirmation-section">
        <h3 class="section-title">ยืนยันการยกเลิกการเชื่อมต่อ</h3>
        <p class="warning-text">
            <span class="material-icons">warning</span>
            คำเตือน: การดำเนินการนี้จะยกเลิกการเชื่อมต่อ LINE ของนักเรียนที่เลือกทั้งหมด และสร้างบัญชีชั่วคราวให้กับนักเรียนแทน
        </p>
        <p>นักเรียนจะสามารถเชื่อมต่อบัญชี LINE ใหม่ได้ในภายหลัง โดยข้อมูลของนักเรียนจะยังคงอยู่ในระบบ</p>
        
        <div class="form-group">
            <label class="form-label">ยืนยันรหัสผ่านผู้ดูแลระบบ</label>
            <input type="password" class="form-control" id="adminPassword" placeholder="ป้อนรหัสผ่านเพื่อยืนยัน">
        </div>
        
        <div class="d-flex justify-content-center mt-4">
            <button type="button" id="confirmDisconnectBtn" class="btn btn-danger disconnect-btn" disabled>
                <span class="material-icons">link_off</span>
                ยกเลิกการเชื่อมต่อ LINE
            </button>
        </div>
    </div>
</div>

<!-- Modal สำหรับแสดงผลการยกเลิกการเชื่อมต่อ -->
<div class="modal" id="resultModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('resultModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ผลการยกเลิกการเชื่อมต่อ LINE</h2>
        
        <div id="resultContent">
            <!-- จะถูกเติมด้วย JavaScript -->
        </div>
        
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" onclick="closeModal('resultModal')">ตกลง</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner"></div>
</div>

<!-- Alert Container -->
<div id="alertContainer" class="alert-container"></div>