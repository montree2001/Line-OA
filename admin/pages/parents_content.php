<!-- แท็บสำหรับการจัดการข้อมูลผู้ปกครอง -->
<div class="tabs-container">
    <div class="tabs-header">
        <div class="tab active" data-tab="parent-list">รายชื่อผู้ปกครอง</div>
        <div class="tab" data-tab="parent-add">เพิ่มผู้ปกครอง</div>
        <div class="tab" data-tab="parent-import">นำเข้าข้อมูล</div>
    </div>
</div>

<!-- เนื้อหาแท็บรายชื่อผู้ปกครอง -->
<div id="parent-list-tab" class="tab-content active">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">family_restroom</span>
            ค้นหาผู้ปกครอง
        </div>
        <div class="filter-container">
            <form method="GET" action="parents.php" id="searchForm">
                <div class="filter-group">
                    <div class="filter-label">ชื่อ-นามสกุลผู้ปกครอง</div>
                    <input type="text" class="form-control" name="search" placeholder="ป้อนชื่อผู้ปกครอง..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="filter-group">
                    <div class="filter-label">หมายเลขโทรศัพท์</div>
                    <input type="text" class="form-control" name="phone" placeholder="ป้อนหมายเลขโทรศัพท์..." value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                </div>
                <div class="filter-group">
                    <div class="filter-label">ความสัมพันธ์</div>
                    <select class="form-control" name="relationship">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="พ่อ" <?php echo (isset($_GET['relationship']) && $_GET['relationship'] === 'พ่อ') ? 'selected' : ''; ?>>พ่อ</option>
                        <option value="แม่" <?php echo (isset($_GET['relationship']) && $_GET['relationship'] === 'แม่') ? 'selected' : ''; ?>>แม่</option>
                        <option value="ผู้ปกครอง" <?php echo (isset($_GET['relationship']) && $_GET['relationship'] === 'ผู้ปกครอง') ? 'selected' : ''; ?>>ผู้ปกครอง</option>
                        <option value="ญาติ" <?php echo (isset($_GET['relationship']) && $_GET['relationship'] === 'ญาติ') ? 'selected' : ''; ?>>ญาติ</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">สถานะ LINE</div>
                    <select class="form-control" name="line_status">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'connected') ? 'selected' : ''; ?>>เชื่อมต่อแล้ว</option>
                        <option value="disconnected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'disconnected') ? 'selected' : ''; ?>>ยังไม่เชื่อมต่อ</option>
                    </select>
                </div>
                <button type="submit" class="filter-button">
                    <span class="material-icons">search</span>
                    ค้นหา
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%">ลำดับ</th>
                        <th width="20%">ชื่อ-นามสกุล</th>
                        <th width="15%">หมายเลขโทรศัพท์</th>
                        <th width="15%">ความสัมพันธ์</th>
                        <th width="15%">จำนวนนักเรียนในปกครอง</th>
                        <th width="15%">LINE ผู้ปกครอง</th>
                        <th width="15%">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($parents)): ?>
                    <tr>
                        <td colspan="7" class="text-center">ไม่พบข้อมูลผู้ปกครอง</td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $start = ($current_page - 1) * $limit + 1;
                        foreach ($parents as $index => $parent): 
                            $display_index = $start + $index;
                            $initials = mb_substr($parent['first_name'], 0, 1, 'UTF-8');
                        ?>
                        <tr>
                            <td><?php echo $display_index; ?></td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar"><?php echo $initials; ?></div>
                                    <div class="student-details">
                                        <div class="student-name"><?php echo $parent['title'] . ' ' . $parent['first_name'] . ' ' . $parent['last_name']; ?></div>
                                        <div class="student-class">ผู้ปกครอง <?php echo $parent['student_count']; ?> คน</div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $parent['phone_number']; ?></td>
                            <td><?php echo $parent['relationship']; ?></td>
                            <td><?php echo $parent['student_count']; ?> คน</td>
                            <td>
                                <?php if (!empty($parent['line_id'])): ?>
                                <span class="status-badge success">เชื่อมต่อแล้ว</span>
                                <?php else: ?>
                                <span class="status-badge warning">ยังไม่เชื่อมต่อ</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="table-action-btn primary" title="ดูข้อมูล" onclick="viewParentDetails(<?php echo $parent['parent_id']; ?>)">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="table-action-btn success" title="แก้ไข" onclick="editParent(<?php echo $parent['parent_id']; ?>)">
                                        <span class="material-icons">edit</span>
                                    </button>
                                    <button class="table-action-btn danger" title="ลบ" onclick="deleteParent(<?php echo $parent['parent_id']; ?>)">
                                        <span class="material-icons">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&relationship=<?php echo urlencode($filters['relationship']); ?>&line_status=<?php echo urlencode($filters['line_status']); ?>" class="page-link">«</a>
                <?php endif; ?>
                
                <?php
                // แสดงปุ่มหน้า
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&relationship=<?php echo urlencode($filters['relationship']); ?>&line_status=<?php echo urlencode($filters['line_status']); ?>" class="page-link <?php echo ($i == $current_page) ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&relationship=<?php echo urlencode($filters['relationship']); ?>&line_status=<?php echo urlencode($filters['line_status']); ?>" class="page-link">»</a>
                <?php endif; ?>
            </div>
            <div class="page-info">
                แสดง <?php echo $start; ?>-<?php echo min($start + count($parents) - 1, $total_rows); ?> จาก <?php echo $total_rows; ?> รายการ
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- เนื้อหาแท็บเพิ่มผู้ปกครอง -->
<div id="parent-add-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">person_add</span>
            <span id="form-title">เพิ่มข้อมูลผู้ปกครองใหม่</span>
        </div>
        
        <form id="addParentForm" class="form-horizontal" method="post" action="parents.php">
            <input type="hidden" name="action" value="add_parent" id="form-action">
            <input type="hidden" name="parent_id" id="parent_id" value="">
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลส่วนตัว</h3>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">คำนำหน้า</label>
                            <select class="form-control" required name="title" id="title">
                                <option value="">-- เลือกคำนำหน้า --</option>
                                <option value="นาย">นาย</option>
                                <option value="นาง">นาง</option>
                                <option value="นางสาว">นางสาว</option>
                                <option value="ดร.">ดร.</option>
                                <option value="ผศ.">ผศ.</option>
                                <option value="รศ.">รศ.</option>
                                <option value="ศ.">ศ.</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">ความสัมพันธ์กับนักเรียน</label>
                            <select class="form-control" required name="relationship" id="relationship">
                                <option value="">-- เลือกความสัมพันธ์ --</option>
                                <option value="พ่อ">พ่อ</option>
                                <option value="แม่">แม่</option>
                                <option value="ผู้ปกครอง">ผู้ปกครอง</option>
                                <option value="ญาติ">ญาติ</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">ชื่อ</label>
                            <input type="text" class="form-control" placeholder="ชื่อผู้ปกครอง" required name="first_name" id="first_name">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">นามสกุล</label>
                            <input type="text" class="form-control" placeholder="นามสกุลผู้ปกครอง" required name="last_name" id="last_name">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการติดต่อ</h3>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">หมายเลขโทรศัพท์</label>
                            <input type="tel" class="form-control" placeholder="หมายเลขโทรศัพท์มือถือ" required name="phone_number" id="phone_number">
                            <div class="form-text">* ใช้สำหรับการเชื่อมต่อกับ LINE และการแจ้งเตือน</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" placeholder="อีเมล (ถ้ามี)" name="email" id="email">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลการเชื่อมต่อ LINE</h3>
                
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="generateQRCode" name="generate_qr_code">
                                <label class="form-check-label" for="generateQRCode">สร้าง QR Code สำหรับการเชื่อมต่อ LINE</label>
                            </div>
                            <div class="form-text">* ระบบจะสร้าง QR Code สำหรับการเชื่อมต่อบัญชี LINE ของผู้ปกครองกับระบบ</div>
                        </div>
                    </div>
                </div>
                
                <div id="qrCodeSection" style="display: none; text-align: center; margin-top: 15px;">
                    <img src="/api/placeholder/150/150" alt="QR Code ตัวอย่าง" style="margin-bottom: 10px;">
                    <p>สแกน QR Code นี้เพื่อเพิ่มเพื่อน LINE Official Account: SADD-Prasat</p>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">ข้อมูลนักเรียนในความปกครอง</h3>
                
                <div class="table-responsive">
                    <table class="data-table" id="studentTable">
                        <thead>
                            <tr>
                                <th width="5%">เลือก</th>
                                <th width="25%">ชื่อ-นามสกุลนักเรียน</th>
                                <th width="20%">ชั้น/ห้อง</th>
                                <th width="20%">เลขประจำตัว</th>
                                <th width="30%">ความสัมพันธ์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <button type="button" class="btn btn-secondary" onclick="searchStudents()">
                        <span class="material-icons">search</span>
                        ค้นหานักเรียน
                    </button>
                </div>
            </div>
            
            <div class="form-section">
                <h3 class="section-title">การตั้งค่าเพิ่มเติม</h3>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="enableNotifications" name="enable_notifications" checked>
                        <label class="form-check-label" for="enableNotifications">เปิดใช้งานการแจ้งเตือนผ่าน LINE</label>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกข้อมูล
                </button>
            </div>
        </form>
    </div>
</div>

<!-- เนื้อหาแท็บนำเข้าข้อมูล -->
<div id="parent-import-tab" class="tab-content">
    <div class="card">
        <div class="card-title">
            <span class="material-icons">upload_file</span>
            นำเข้าข้อมูลผู้ปกครอง
        </div>
        
        <div class="import-container">
            <div class="upload-section">
                <p class="upload-info">อัปโหลดไฟล์ CSV หรือ Excel ที่มีข้อมูลผู้ปกครอง</p>
                <div class="upload-area">
                    <input type="file" id="fileUpload" class="file-input" accept=".csv, .xlsx, .xls">
                    <label for="fileUpload" class="file-label">
                        <span class="material-icons">cloud_upload</span>
                        <span>เลือกไฟล์หรือลากไฟล์มาวางที่นี่</span>
                        <span class="file-format">(รองรับไฟล์ CSV, Excel)</span>
                    </label>
                </div>
            </div>
            
            <div class="format-example">
                <h3 class="format-title">รูปแบบไฟล์ที่รองรับ</h3>
                <p>ไฟล์ที่อัปโหลดควรมีคอลัมน์ดังต่อไปนี้:</p>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>คำนำหน้า</th>
                                <th>ชื่อ</th>
                                <th>นามสกุล</th>
                                <th>เบอร์โทรศัพท์</th>
                                <th>อีเมล</th>
                                <th>ความสัมพันธ์</th>
                                <th>รหัสนักเรียน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>นาง</td>
                                <td>วันดี</td>
                                <td>สุขใจ</td>
                                <td>0812345678</td>
                                <td>example@mail.com</td>
                                <td>แม่</td>
                                <td>12345</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="template-download">
                    <a href="templates/parent_import_template.xlsx" class="btn btn-secondary btn-sm">
                        <span class="material-icons">file_download</span>
                        ดาวน์โหลดเทมเพลต Excel
                    </a>
                    <a href="templates/parent_import_template.csv" class="btn btn-secondary btn-sm">
                        <span class="material-icons">file_download</span>
                        ดาวน์โหลดเทมเพลต CSV
                    </a>
                </div>
            </div>
            
            <div class="import-actions">
                <button type="button" class="btn btn-secondary" onclick="resetImport()">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="importParents()" disabled id="importButton">
                    <span class="material-icons">upload</span>
                    นำเข้าข้อมูล
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแสดงรายละเอียดผู้ปกครอง -->
<div class="modal" id="parentDetailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('parentDetailModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title" id="parentDetailTitle">ข้อมูลผู้ปกครอง</h2>
        
        <div class="parent-profile">
            <div class="profile-header">
                <div class="profile-avatar" id="parentInitials"></div>
                <div class="profile-info">
                    <h3 id="parentName"></h3>
                    <p><strong>ความสัมพันธ์:</strong> <span id="parentRelationship"></span></p>
                    <p><strong>เบอร์โทรศัพท์:</strong> <span id="parentPhone"></span></p>
                    <p><strong>LINE สถานะ:</strong> <span id="parentLineStatus"></span></p>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>ข้อมูลส่วนตัว</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <tbody>
                            <tr>
                                <td width="200"><strong>อีเมล:</strong></td>
                                <td id="parentEmail">-</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>นักเรียนในความปกครอง</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ชื่อ-นามสกุล</th>
                                <th>ชั้น/ห้อง</th>
                                <th>เลขประจำตัว</th>
                                <th>ความสัมพันธ์</th>
                            </tr>
                        </thead>
                        <tbody id="parentStudentsList">
                            <tr>
                                <td colspan="4" class="text-center">ไม่พบข้อมูลนักเรียนในความปกครอง</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="profile-section">
                <h4>ประวัติการรับข้อความแจ้งเตือน</h4>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>เรื่อง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="parentNotificationsList">
                            <tr>
                                <td colspan="4" class="text-center">ไม่พบประวัติการแจ้งเตือน</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('parentDetailModal')">ปิด</button>
            <button class="btn btn-primary" id="editParentBtn">
                <span class="material-icons">edit</span>
                แก้ไขข้อมูล
            </button>
            <button class="btn btn-success" id="sendMessageBtn">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลค้นหานักเรียน -->
<div class="modal" id="searchStudentModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('searchStudentModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ค้นหานักเรียน</h2>
        
        <div class="search-container">
            <div class="filter-container">
                <div class="filter-group">
                    <div class="filter-label">ชื่อ-นามสกุลนักเรียน</div>
                    <input type="text" class="form-control" id="studentSearchInput" placeholder="ป้อนชื่อนักเรียน...">
                </div>
                <div class="filter-group">
                    <div class="filter-label">ระดับชั้น</div>
                    <select class="form-control" id="studentLevelFilter">
                        <option value="">-- ทุกระดับชั้น --</option>
                        <?php 
                        $levels = getAllLevels();
                        foreach ($levels as $level):
                        ?>
                        <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">ห้องเรียน</div>
                    <select class="form-control" id="studentGroupFilter">
                        <option value="">-- ทุกห้อง --</option>
                        <?php 
                        $groups = getAllGroups();
                        foreach ($groups as $group):
                        ?>
                        <option value="<?php echo $group; ?>"><?php echo $group; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="filter-button" id="studentSearchBtn">
                    <span class="material-icons">search</span>
                    ค้นหา
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">เลือก</th>
                            <th width="25%">ชื่อ-นามสกุล</th>
                            <th width="10%">ชั้น/ห้อง</th>
                            <th width="15%">เลขประจำตัว</th>
                            <th width="15%">ผู้ปกครองปัจจุบัน</th>
                            <th width="20%">ความสัมพันธ์</th>
                        </tr>
                    </thead>
                    <tbody id="studentSearchResults">
                        <tr>
                            <td colspan="6" class="text-center">กรุณาค้นหานักเรียน</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('searchStudentModal')">ยกเลิก</button>
            <button class="btn btn-primary" onclick="addSelectedStudents()">
                <span class="material-icons">add</span>
                เพิ่มนักเรียนที่เลือก
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
        <h2 class="modal-title" id="sendMessageTitle">ส่งข้อความถึงผู้ปกครอง</h2>
        
        <div class="template-buttons">
            <button class="template-btn active" data-template="regular">ข้อความทั่วไป</button>
            <button class="template-btn" data-template="meeting">นัดประชุม</button>
            <button class="template-btn" data-template="warning">แจ้งเตือน</button>
            <button class="template-btn" data-template="report">รายงานผล</button>
        </div>
        
        <div class="message-form">
            <input type="hidden" id="messageUserId" value="">
            <textarea class="message-textarea" id="messageText">เรียน คุณวันดี สุขใจ

ทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท</textarea>
            
            <div class="message-preview">
                <div class="preview-header">
                    <span>ตัวอย่างข้อความที่จะส่ง</span>
                    <button class="preview-button" onclick="showMessagePreview()">
                        <span class="material-icons">visibility</span>
                        แสดงตัวอย่าง
                    </button>
                </div>
                <div class="preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p style="margin-top: 10px;">เรียน คุณวันดี สุขใจ<br><br>ทางโรงเรียนขอแจ้งให้ทราบว่า น.ส.ธนกฤต สุขใจ เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                </div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('sendMessageModal')">ยกเลิก</button>
            <button class="btn btn-primary" id="sendMessageConfirmBtn">
                <span class="material-icons">send</span>
                ส่งข้อความ
            </button>
        </div>
    </div>
</div>

<!-- โมดัลยืนยันการลบ -->
<div class="modal" id="confirmDeleteModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('confirmDeleteModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการลบข้อมูล</h2>
        
        <div class="confirmation-message">
            <p id="deleteConfirmText">คุณต้องการลบข้อมูลผู้ปกครองใช่หรือไม่?</p>
            <p class="warning-text">คำเตือน: การลบข้อมูลผู้ปกครองจะทำให้ไม่สามารถส่งข้อความถึงผู้ปกครองได้อีก และข้อมูลผู้ปกครองทั้งหมดจะถูกลบออกจากระบบ</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="closeModal('confirmDeleteModal')">ยกเลิก</button>
            <form method="post" action="parents.php" id="deleteParentForm">
                <input type="hidden" name="action" value="delete_parent">
                <input type="hidden" name="parent_id" id="deleteParentId" value="">
                <button type="submit" class="btn btn-danger">
                    <span class="material-icons">delete</span>
                    ยืนยันการลบ
                </button>
            </form>
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

// แสดงข้อมูลผู้ปกครอง
function viewParentDetails(parentId) {
    // ส่ง AJAX request เพื่อดึงข้อมูลผู้ปกครอง
    fetch('api/parents_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_parent&parent_id=${parentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const parent = data.data.parent;
            const students = data.data.students;
            const notifications = data.data.notifications;
            
            // แสดงข้อมูลผู้ปกครอง
            document.getElementById('parentDetailTitle').textContent = `ข้อมูลผู้ปกครอง - ${parent.title} ${parent.first_name} ${parent.last_name}`;
            document.getElementById('parentName').textContent = `${parent.title} ${parent.first_name} ${parent.last_name}`;
            document.getElementById('parentInitials').textContent = parent.first_name.charAt(0);
            document.getElementById('parentRelationship').textContent = parent.relationship;
            document.getElementById('parentPhone').textContent = parent.phone_number;
            
            // แสดงสถานะ LINE
            const lineStatusElement = document.getElementById('parentLineStatus');
            if (parent.line_status === 'connected') {
                lineStatusElement.innerHTML = '<span class="status-badge success">เชื่อมต่อแล้ว</span>';
            } else {
                lineStatusElement.innerHTML = '<span class="status-badge warning">ยังไม่เชื่อมต่อ</span>';
            }
            
            // แสดงอีเมล
            document.getElementById('parentEmail').textContent = parent.email || '-';
            
            // แสดงรายชื่อนักเรียนในความปกครอง
            const studentsList = document.getElementById('parentStudentsList');
            if (students.length > 0) {
                let studentsHtml = '';
                students.forEach(student => {
                    const initial = student.first_name.charAt(0);
                    studentsHtml += `
                        <tr>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">${initial}</div>
                                    <div class="student-details">
                                        <div class="student-name">${student.student_title} ${student.first_name} ${student.last_name}</div>
                                    </div>
                                </div>
                            </td>
                            <td>${student.level}/${student.group_number}</td>
                            <td>${student.student_code}</td>
                            <td>${parent.relationship}</td>
                        </tr>
                    `;
                });
                studentsList.innerHTML = studentsHtml;
            } else {
                studentsList.innerHTML = '<tr><td colspan="4" class="text-center">ไม่พบข้อมูลนักเรียนในความปกครอง</td></tr>';
            }
            
            // แสดงประวัติการแจ้งเตือน
            const notificationsList = document.getElementById('parentNotificationsList');
            if (notifications.length > 0) {
                let notificationsHtml = '';
                notifications.forEach(notification => {
                    const date = new Date(notification.sent_at);
                    const formattedDate = `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear() + 543} ${date.getHours()}:${String(date.getMinutes()).padStart(2, '0')}`;
                    
                    let type = '';
                    switch (notification.notification_type) {
                        case 'attendance':
                            type = 'แจ้งเตือนการเข้าแถว';
                            break;
                        case 'risk_alert':
                            type = 'แจ้งเตือนความเสี่ยง';
                            break;
                        case 'system':
                        default:
                            type = 'แจ้งเตือนทั่วไป';
                            break;
                    }
                    
                    const status = notification.status === 'sent' ? 
                        '<span class="status-badge success">อ่านแล้ว</span>' : 
                        '<span class="status-badge warning">ยังไม่อ่าน</span>';
                    
                    notificationsHtml += `
                        <tr>
                            <td>${formattedDate}</td>
                            <td>${type}</td>
                            <td>${notification.message.substring(0, 30)}...</td>
                            <td>${status}</td>
                        </tr>
                    `;
                });
                notificationsList.innerHTML = notificationsHtml;
            } else {
                notificationsList.innerHTML = '<tr><td colspan="4" class="text-center">ไม่พบประวัติการแจ้งเตือน</td></tr>';
            }
            
            // ตั้งค่าปุ่มแก้ไขและส่งข้อความ
            document.getElementById('editParentBtn').onclick = () => editParent(parentId);
            document.getElementById('sendMessageBtn').onclick = () => sendDirectMessage(parentId, parent.user_id, `${parent.title} ${parent.first_name} ${parent.last_name}`);
            
            // แสดงโมดัล
            showModal('parentDetailModal');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

// แก้ไขข้อมูลผู้ปกครอง
function editParent(parentId) {
    // ส่ง AJAX request เพื่อดึงข้อมูลผู้ปกครอง
    fetch('api/parents_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_parent&parent_id=${parentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const parent = data.data.parent;
            const students = data.data.students;
            
            // เปลี่ยนหัวข้อฟอร์ม
            document.getElementById('form-title').textContent = `แก้ไขข้อมูลผู้ปกครอง - ${parent.title} ${parent.first_name} ${parent.last_name}`;
            
            // เปลี่ยนการทำงานของฟอร์ม
            document.getElementById('form-action').value = 'edit_parent';
            document.getElementById('parent_id').value = parentId;
            
            // กรอกข้อมูลในฟอร์ม
            document.getElementById('title').value = parent.title;
            document.getElementById('relationship').value = parent.relationship;
            document.getElementById('first_name').value = parent.first_name;
            document.getElementById('last_name').value = parent.last_name;
            document.getElementById('phone_number').value = parent.phone_number;
            document.getElementById('email').value = parent.email || '';
            
            // เพิ่มนักเรียนในความปกครองลงในตาราง
            const studentTable = document.getElementById('studentTable').getElementsByTagName('tbody')[0];
            if (students.length > 0) {
                let studentsHtml = '';
                students.forEach(student => {
                    studentsHtml += `
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="${student.student_id}" checked></td>
                            <td>${student.student_title} ${student.first_name} ${student.last_name}</td>
                            <td>${student.level || '-'}/${student.group_number || '-'}</td>
                            <td>${student.student_code}</td>
                            <td>${parent.relationship}</td>
                        </tr>
                    `;
                });
                studentTable.innerHTML = studentsHtml;
            } else {
                studentTable.innerHTML = '<tr><td colspan="5" class="text-center">กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ</td></tr>';
            }
            
            // ปิดโมดัลแสดงรายละเอียด (ถ้าเปิดอยู่)
            closeModal('parentDetailModal');
            
            // เปลี่ยนไปยังแท็บเพิ่มข้อมูล (แต่จะใช้สำหรับการแก้ไขแทน)
            showTab('parent-add');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

// ลบข้อมูลผู้ปกครอง
function deleteParent(parentId) {
    // ดึงข้อมูลผู้ปกครองเพื่อแสดงในโมดัลยืนยันการลบ
    fetch('api/parents_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_parent&parent_id=${parentId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const parent = data.data.parent;
            
            // แสดงชื่อผู้ปกครองในโมดัลยืนยันการลบ
            document.getElementById('deleteConfirmText').textContent = `คุณต้องการลบข้อมูลผู้ปกครอง "${parent.title} ${parent.first_name} ${parent.last_name}" ใช่หรือไม่?`;
            
            // ตั้งค่ารหัสผู้ปกครองที่จะลบ
            document.getElementById('deleteParentId').value = parentId;
            
            // แสดงโมดัลยืนยันการลบ
            showModal('confirmDeleteModal');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

// ค้นหานักเรียน (สำหรับเพิ่มในรายการผู้ปกครอง)
function searchStudents() {
    showModal('searchStudentModal');
    
    // ตั้งค่า event listener สำหรับปุ่มค้นหา
    document.getElementById('studentSearchBtn').onclick = function() {
        const search = document.getElementById('studentSearchInput').value;
        const level = document.getElementById('studentLevelFilter').value;
        const group = document.getElementById('studentGroupFilter').value;
        
        // ส่ง AJAX request เพื่อค้นหานักเรียน
        fetch('api/parents_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=search_students&search=${encodeURIComponent(search)}&level=${encodeURIComponent(level)}&group=${encodeURIComponent(group)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const students = data.data;
                const resultsTable = document.getElementById('studentSearchResults');
                
                if (students.length > 0) {
                    let studentsHtml = '';
                    students.forEach(student => {
                        const initial = student.first_name.charAt(0);
                        studentsHtml += `
                            <tr>
                                <td><input type="checkbox" class="student-select" data-id="${student.student_id}" data-name="${student.student_title} ${student.first_name} ${student.last_name}" data-class="${student.level || '-'}/${student.group_number || '-'}" data-code="${student.student_code}"></td>
                                <td>
                                    <div class="student-info">
                                        <div class="student-avatar">${initial}</div>
                                        <div class="student-details">
                                            <div class="student-name">${student.student_title} ${student.first_name} ${student.last_name}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>${student.level || '-'}/${student.group_number || '-'}</td>
                                <td>${student.student_code}</td>
                                <td>${student.current_parent || '-'}</td>
                                <td>
                                    <select class="form-control form-control-sm student-relationship" data-id="${student.student_id}">
                                        <option value="">-- เลือก --</option>
                                        <option value="พ่อ">พ่อ</option>
                                        <option value="แม่">แม่</option>
                                        <option value="ผู้ปกครอง">ผู้ปกครอง</option>
                                        <option value="ญาติ">ญาติ</option>
                                    </select>
                                </td>
                            </tr>
                        `;
                    });
                    resultsTable.innerHTML = studentsHtml;
                    
                    // ตั้งค่าการทำงานเมื่อเลือกความสัมพันธ์
                    document.querySelectorAll('.student-relationship').forEach(select => {
                        select.addEventListener('change', function() {
                            const studentId = this.getAttribute('data-id');
                            const checkbox = document.querySelector(`.student-select[data-id="${studentId}"]`);
                            if (this.value) {
                                checkbox.checked = true;
                            }
                        });
                    });
                    
                    // ตั้งค่าการทำงานเมื่อเลือกนักเรียน
                    document.querySelectorAll('.student-select').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            const studentId = this.getAttribute('data-id');
                            const select = document.querySelector(`.student-relationship[data-id="${studentId}"]`);
                            if (this.checked && !select.value) {
                                select.value = document.getElementById('relationship').value || 'ผู้ปกครอง';
                            }
                        });
                    });
                } else {
                    resultsTable.innerHTML = '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลนักเรียน</td></tr>';
                }
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        });
    };
}

// เพิ่มนักเรียนที่เลือกไว้ลงในตาราง
function addSelectedStudents() {
    const selectedStudents = document.querySelectorAll('.student-select:checked');
    
    if (selectedStudents.length === 0) {
        showAlert('กรุณาเลือกนักเรียนอย่างน้อย 1 คน', 'warning');
        return;
    }
    
    const studentTable = document.getElementById('studentTable').getElementsByTagName('tbody')[0];
    let studentsHtml = '';
    
    selectedStudents.forEach(student => {
        const studentId = student.getAttribute('data-id');
        const studentName = student.getAttribute('data-name');
        const studentClass = student.getAttribute('data-class');
        const studentCode = student.getAttribute('data-code');
        const relationship = document.querySelector(`.student-relationship[data-id="${studentId}"]`).value || document.getElementById('relationship').value || 'ผู้ปกครอง';
        
        studentsHtml += `
            <tr>
                <td><input type="checkbox" name="student_ids[]" value="${studentId}" checked></td>
                <td>${studentName}</td>
                <td>${studentClass}</td>
                <td>${studentCode}</td>
                <td>${relationship}</td>
            </tr>
        `;
    });
    
    // ลบข้อความแจ้งเตือนถ้ามี
    if (studentTable.innerHTML.includes('กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ')) {
        studentTable.innerHTML = '';
    }
    
    // เพิ่มนักเรียนลงในตาราง
    studentTable.innerHTML += studentsHtml;
    
    // ปิดโมดัล
    closeModal('searchStudentModal');
}

// แสดงตัวอย่างข้อความที่จะส่ง
function showMessagePreview() {
    const messageText = document.getElementById('messageText').value;
    
    // อัปเดตตัวอย่างข้อความ
    document.querySelector('.preview-content p').innerHTML = messageText.replace(/\n/g, '<br>');
}

// ส่งข้อความถึงผู้ปกครอง
function sendDirectMessage(parentId, userId, parentName) {
    // ตั้งค่าชื่อผู้ปกครองในหัวข้อโมดัล
    document.getElementById('sendMessageTitle').textContent = `ส่งข้อความถึงผู้ปกครอง - ${parentName}`;
    
    // ตั้งค่า user_id สำหรับการส่งข้อความ
    document.getElementById('messageUserId').value = userId;
    
    // อัปเดตเทมเพลตข้อความ
    selectTemplate('regular', parentName);
    
    // ตั้งค่าปุ่มส่งข้อความ
    document.getElementById('sendMessageConfirmBtn').onclick = function() {
        const message = document.getElementById('messageText').value;
        
        if (!message.trim()) {
            showAlert('กรุณากรอกข้อความที่ต้องการส่ง', 'warning');
            return;
        }
        
        // ส่ง AJAX request เพื่อส่งข้อความ
        fetch('api/parents_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=send_message&user_id=${userId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                closeModal('sendMessageModal');
                showAlert('ส่งข้อความเรียบร้อยแล้ว', 'success');
                
                // รีเฟรชข้อมูลผู้ปกครอง (ถ้าโมดัลแสดงรายละเอียดเปิดอยู่)
                if (document.getElementById('parentDetailModal').classList.contains('active')) {
                    viewParentDetails(parentId);
                }
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
        });
    };
    
    // แสดงโมดัล
    showModal('sendMessageModal');
}

// เลือกเทมเพลตข้อความ
function selectTemplate(templateType, parentName = 'คุณวันดี สุขใจ', studentName = 'นายธนกฤต สุขใจ') {
    // ยกเลิกการเลือกเทมเพลตทั้งหมด
    document.querySelectorAll('.template-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // เลือกเทมเพลตที่คลิก
    document.querySelector(`.template-btn[data-template="${templateType}"]`).classList.add('active');
    
    // เปลี่ยนข้อความตามเทมเพลตที่เลือก
    const messageText = document.getElementById('messageText');
    
    switch(templateType) {
        case 'regular':
            messageText.value = `เรียน ${parentName}\n\nทางโรงเรียนขอแจ้งให้ทราบว่า ${studentName} เข้าร่วมกิจกรรมหน้าเสาธงประจำวันที่ 16 มีนาคม 2568 เรียบร้อยแล้ว\n\nจึงเรียนมาเพื่อทราบ\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'meeting':
            messageText.value = `เรียน ${parentName}\n\nทางโรงเรียนขอเรียนเชิญท่านเข้าร่วมประชุมผู้ปกครองนักเรียนในวันศุกร์ที่ 21 มีนาคม 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ\n\nจึงเรียนมาเพื่อทราบและขอเชิญเข้าร่วมประชุมตามวันและเวลาดังกล่าว\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'warning':
            messageText.value = `เรียน ${parentName}\n\nทางโรงเรียนขอแจ้งให้ทราบว่า ${studentName} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 จาก 40 วัน (65%)\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
        case 'report':
            messageText.value = `เรียน ${parentName}\n\nสรุปข้อมูลการเข้าแถวของ ${studentName} ประจำเดือนมีนาคม 2568\n\nจำนวนวันเข้าแถว: 26 วัน จากทั้งหมด 40 วัน (65%)\nจำนวนวันขาดแถว: 14 วัน\nสถานะ: เสี่ยงตกกิจกรรมเข้าแถว\n\nกรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nฝ่ายกิจการนักเรียน\nวิทยาลัยการอาชีพปราสาท`;
            break;
    }
    
    // อัปเดตตัวอย่างข้อความ
    showMessagePreview();
}

// รีเซ็ตฟอร์ม
function resetForm() {
    document.getElementById('addParentForm').reset();
    document.getElementById('form-title').textContent = 'เพิ่มข้อมูลผู้ปกครองใหม่';
    document.getElementById('form-action').value = 'add_parent';
    document.getElementById('parent_id').value = '';
    
    // ล้างตารางนักเรียน
    const studentTable = document.getElementById('studentTable').getElementsByTagName('tbody')[0];
    studentTable.innerHTML = '<tr><td colspan="5" class="text-center">กรุณาค้นหานักเรียนเพื่อเพิ่มในรายการ</td></tr>';
    
    // ซ่อน QR Code
    document.getElementById('qrCodeSection').style.display = 'none';
}

// รีเซ็ตฟอร์มนำเข้าข้อมูล
function resetImport() {
    document.getElementById('fileUpload').value = '';
    document.getElementById('importButton').disabled = true;
}

// นำเข้าข้อมูลผู้ปกครอง
function importParents() {
    const fileInput = document.getElementById('fileUpload');
    
    if (!fileInput.files.length) {
        showAlert('กรุณาเลือกไฟล์ที่ต้องการนำเข้า', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    const formData = new FormData();
    formData.append('file', file);
    formData.append('action', 'import_parents');
    
    showAlert('กำลังนำเข้าข้อมูล โปรดรอสักครู่...', 'info');
    
    // จำลองการนำเข้าข้อมูล (ควรใช้ AJAX จริงในการใช้งานจริง)
    setTimeout(() => {
        showAlert('นำเข้าข้อมูลผู้ปกครองเรียบร้อยแล้ว', 'success');
        resetImport();
    }, 2000);
}

// ส่งออกข้อมูลผู้ปกครอง
function exportParentsData() {
    showAlert('กำลังสร้างไฟล์ส่งออก โปรดรอสักครู่...', 'info');
    
    // ส่ง AJAX request เพื่อส่งออกข้อมูล
    fetch('api/parents_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=export_parents'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // จำลองการดาวน์โหลดไฟล์ (ควรใช้ window.location หรือ Blob ในการใช้งานจริง)
            showAlert('ส่งออกข้อมูลเรียบร้อยแล้ว ไฟล์จะถูกดาวน์โหลดอัตโนมัติ', 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
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

// แสดงการแจ้งเตือน
function showAlert(message, type = 'info') {
    // สร้าง alert container ถ้ายังไม่มี
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <div class="alert-content">${message}</div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // ปุ่มปิด alert
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alert.classList.add('alert-closing');
        setTimeout(() => {
            if (alertContainer.contains(alert)) {
                alertContainer.removeChild(alert);
            }
        }, 300);
    });
    
    // ให้ alert ปิดโดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alert.classList.add('alert-closing');
            setTimeout(() => {
                if (alertContainer.contains(alert)) {
                    alertContainer.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
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
    
    // ตั้งค่าปุ่มเทมเพลต
    const templateButtons = document.querySelectorAll('.template-btn');
    templateButtons.forEach(button => {
        button.addEventListener('click', function() {
            const templateType = this.getAttribute('data-template');
            selectTemplate(templateType);
        });
    });
    
    // แสดง QR Code สำหรับการเชื่อมต่อ LINE
    document.getElementById('generateQRCode').addEventListener('change', function() {
        const qrCodeSection = document.getElementById('qrCodeSection');
        if (this.checked) {
            qrCodeSection.style.display = 'block';
        } else {
            qrCodeSection.style.display = 'none';
        }
    });
    
    // ตั้งค่าการอัปโหลดไฟล์
    const fileInput = document.getElementById('fileUpload');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const importButton = document.getElementById('importButton');
            if (importButton) {
                importButton.disabled = !this.files.length;
            }
        });
    }
});
</script>