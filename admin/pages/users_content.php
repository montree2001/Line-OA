<!-- ตัวกรองข้อมูลผู้ใช้งาน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">filter_list</span>
        ค้นหาและกรองข้อมูลผู้ใช้งาน
    </div>

    <div class="filter-container">
        <form method="get" action="users.php" class="filter-form">
            <div class="row">
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">ชื่อ-นามสกุล</div>
                        <input type="text" class="form-control" name="name" placeholder="ป้อนชื่อผู้ใช้..." value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">บทบาท</div>
                        <select class="form-control" name="role">
                            <option value="">-- ทุกบทบาท --</option>
                            <option value="student" <?php echo (isset($_GET['role']) && $_GET['role'] === 'student') ? 'selected' : ''; ?>>นักเรียน</option>
                            <option value="teacher" <?php echo (isset($_GET['role']) && $_GET['role'] === 'teacher') ? 'selected' : ''; ?>>ครู</option>
                            <option value="parent" <?php echo (isset($_GET['role']) && $_GET['role'] === 'parent') ? 'selected' : ''; ?>>ผู้ปกครอง</option>
                            <option value="admin" <?php echo (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="filter-group">
                        <div class="filter-label">การเชื่อมต่อ LINE</div>
                        <select class="form-control" name="line_status">
                            <option value="">-- ทุกสถานะ --</option>
                            <option value="connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'connected') ? 'selected' : ''; ?>>เชื่อมต่อแล้ว</option>
                            <option value="not_connected" <?php echo (isset($_GET['line_status']) && $_GET['line_status'] === 'not_connected') ? 'selected' : ''; ?>>ยังไม่เชื่อมต่อ</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <span class="material-icons">search</span>
                        ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- สรุปข้อมูลผู้ใช้งาน -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">ผู้ใช้งานทั้งหมด</div>
            <div class="stat-icon blue">
                <span class="material-icons">groups</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['total']) ? number_format($data['statistics']['total']) : 0; ?></div>
        <div class="stat-comparison">
            จำนวนผู้ใช้งานระบบทั้งหมด
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">นักเรียน</div>
            <div class="stat-icon blue">
                <span class="material-icons">school</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['students']) ? number_format($data['statistics']['students']) : 0; ?></div>
        <div class="stat-comparison">
            <?php
            $totalUsers = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
            $studentUsers = isset($data['statistics']['students']) ? $data['statistics']['students'] : 0;
            $studentPercent = ($totalUsers > 0) ? round(($studentUsers / $totalUsers) * 100) : 0;
            echo $studentPercent . '% ของผู้ใช้งานทั้งหมด';
            ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">ครู / ผู้ปกครอง</div>
            <div class="stat-icon green">
                <span class="material-icons">supervisor_account</span>
            </div>
        </div>
        <div class="stat-value">
            <?php 
            $teacherParentCount = 
                (isset($data['statistics']['teachers']) ? $data['statistics']['teachers'] : 0) + 
                (isset($data['statistics']['parents']) ? $data['statistics']['parents'] : 0);
            echo number_format($teacherParentCount); 
            ?>
        </div>
        <div class="stat-comparison">
            ครู: <?php echo isset($data['statistics']['teachers']) ? number_format($data['statistics']['teachers']) : 0; ?> | 
            ผู้ปกครอง: <?php echo isset($data['statistics']['parents']) ? number_format($data['statistics']['parents']) : 0; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-title">เชื่อมต่อ LINE</div>
            <div class="stat-icon green">
                <span class="material-icons">check_circle</span>
            </div>
        </div>
        <div class="stat-value"><?php echo isset($data['statistics']['line_connected']) ? number_format($data['statistics']['line_connected']) : 0; ?></div>
        <div class="stat-comparison">
            <?php
            $totalUsers = isset($data['statistics']['total']) ? $data['statistics']['total'] : 0;
            $lineConnected = isset($data['statistics']['line_connected']) ? $data['statistics']['line_connected'] : 0;
            $linePercent = ($totalUsers > 0) ? round(($lineConnected / $totalUsers) * 100) : 0;
            echo $linePercent . '% ของผู้ใช้งานทั้งหมด';
            ?>
        </div>
    </div>
</div>

<!-- ตารางแสดงข้อมูลผู้ใช้งาน -->
<div class="card">
    <div class="card-title">
        <span class="material-icons">people</span>
        รายชื่อผู้ใช้งาน
        <span class="badge"><?php echo count($data['users']); ?> รายการ</span>
    </div>

    <!-- ตารางข้อมูลผู้ใช้งาน -->
    <div class="table-responsive">
        <table id="userDataTable" class="data-table display">
            <thead>
                <tr>
                    <th width="5%">ID</th>
                    <th width="15%">ชื่อ-นามสกุล</th>
                    <th width="10%">บทบาท</th>
                    <th width="10%">รหัส</th>
                    <th width="15%">ข้อมูลติดต่อ</th>
                    <th width="15%">LINE ID</th>
                    <th width="10%">วันที่สร้าง</th>
                    <th width="10%">เข้าสู่ระบบล่าสุด</th>
                    <th width="10%">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['users'] as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="<?php echo htmlspecialchars($user['first_name']); ?>" class="profile-image">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['first_name'] ?? '', 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="user-details">
                                    <div class="user-name">
                                        <?php
                                        echo htmlspecialchars(($user['title'] ?? '') . ' ' .
                                            ($user['first_name'] ?? '') . ' ' .
                                            ($user['last_name'] ?? ''));
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $roleBadgeClass = '';
                            $roleText = '';
                            
                            switch($user['role']) {
                                case 'student':
                                    $roleBadgeClass = 'primary';
                                    $roleText = 'นักเรียน';
                                    break;
                                case 'teacher':
                                    $roleBadgeClass = 'success';
                                    $roleText = 'ครู';
                                    break;
                                case 'parent':
                                    $roleBadgeClass = 'info';
                                    $roleText = 'ผู้ปกครอง';
                                    break;
                                case 'admin':
                                    $roleBadgeClass = 'danger';
                                    $roleText = 'ผู้ดูแลระบบ';
                                    break;
                                default:
                                    $roleBadgeClass = 'secondary';
                                    $roleText = $user['role'];
                            }
                            ?>
                            <span class="status-badge <?php echo $roleBadgeClass; ?>"><?php echo $roleText; ?></span>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'student' && !empty($user['student_code'])): ?>
                                <?php echo htmlspecialchars($user['student_code']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($user['phone_number'])): ?>
                                <div><i class="material-icons small">phone</i> <?php echo htmlspecialchars($user['phone_number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($user['email'])): ?>
                                <div><i class="material-icons small">email</i> <?php echo htmlspecialchars($user['email']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($user['line_id']) && strpos($user['line_id'], 'TEMP_') === false): ?>
                                <span class="status-badge success">เชื่อมต่อแล้ว</span>
                                <div class="line-id-text"><?php echo htmlspecialchars($user['line_id']); ?></div>
                            <?php else: ?>
                                <span class="status-badge warning">ยังไม่เชื่อมต่อ</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php
                            if (!empty($user['last_login'])) {
                                echo date('d/m/Y H:i', strtotime($user['last_login']));
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-icon btn-info" onclick="viewUser('<?php echo $user['user_id']; ?>')" title="ดูข้อมูล">
                                    <span class="material-icons">visibility</span>
                                </button>
                                <button class="btn btn-icon btn-warning" onclick="editUser('<?php echo $user['user_id']; ?>')" title="แก้ไข">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button class="btn btn-icon btn-danger" onclick="deleteUser('<?php echo $user['user_id']; ?>', '<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>')" title="ลบ">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($data['users'])): ?>
                    <tr>
                        <td colspan="9" class="text-center">ไม่พบข้อมูลผู้ใช้งาน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- โมดัลดูข้อมูลผู้ใช้ -->
<div class="modal" id="viewUserModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('viewUserModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ข้อมูลผู้ใช้งาน</h2>

        <div class="user-profile">
            <div class="user-profile-header">
                <div class="user-profile-avatar" id="view_avatar">
                    <!-- รูปโปรไฟล์จะถูกเพิ่มโดย JavaScript -->
                </div>
                <div class="user-profile-info">
                    <h3 id="view_full_name"></h3>
                    <p id="view_role"></p>
                    <p id="view_user_code"></p>
                </div>
            </div>

            <div class="info-sections">
                <div class="info-section">
                    <h4>ข้อมูลติดต่อ</h4>
                    <p id="view_phone"><strong>เบอร์โทรศัพท์:</strong> <span></span></p>
                    <p id="view_email"><strong>อีเมล:</strong> <span></span></p>
                    <p id="view_line"><strong>LINE:</strong> <span></span></p>
                </div>

                <div class="info-section">
                    <h4>ข้อมูลบัญชี</h4>
                    <p id="view_created"><strong>สร้างเมื่อ:</strong> <span></span></p>
                    <p id="view_last_login"><strong>เข้าสู่ระบบล่าสุด:</strong> <span></span></p>
                    <p id="view_consent"><strong>ยินยอมข้อมูลส่วนบุคคล:</strong> <span></span></p>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('viewUserModal')">ปิด</button>
                <button type="button" class="btn btn-warning" id="edit_btn" onclick="">
                    <span class="material-icons">edit</span>
                    แก้ไขข้อมูล
                </button>
                <button type="button" class="btn btn-primary" id="reset_line_btn" onclick="">
                    <span class="material-icons">refresh</span>
                    รีเซ็ตการเชื่อมต่อ LINE
                </button>
            </div>
        </div>
    </div>
</div>

<!-- โมดัลแก้ไขข้อมูลผู้ใช้ -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('editUserModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">แก้ไขข้อมูลผู้ใช้งาน</h2>

        <form id="editUserForm" method="post" action="users.php">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="edit_user_id">
            <input type="hidden" name="role" id="edit_role">

            <div class="form-section">
                <h3 class="section-title">ข้อมูลส่วนตัว</h3>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">คำนำหน้า<span class="text-danger">*</span></label>
                            <select class="form-control" name="title" id="edit_title" required>
                                <option value="">-- เลือก --</option>
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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">ชื่อ<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">นามสกุล<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 student-only-field">
                        <div class="form-group">
                            <label class="form-label">รหัสนักเรียน<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_code" id="edit_student_code">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" name="phone_number" id="edit_phone_number">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">อีเมล</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">save</span>
                    บันทึกการแก้ไข
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลลบผู้ใช้ -->
<div class="modal" id="deleteUserModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('deleteUserModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการลบข้อมูล</h2>

        <div class="confirmation-message">
            <p>คุณต้องการลบข้อมูลผู้ใช้ <strong id="delete_user_name">-</strong> ใช่หรือไม่?</p>
            <p class="warning-text text-danger">คำเตือน: การลบข้อมูลจะไม่สามารถกู้คืนได้ และจะลบข้อมูลที่เกี่ยวข้องทั้งหมด เช่น ประวัติการเข้าแถว, ความสัมพันธ์กับผู้ปกครอง ฯลฯ</p>
        </div>

        <form id="deleteUserForm" method="post" action="users.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="delete_user_id">

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteUserModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-danger">
                    <span class="material-icons">delete</span>
                    ยืนยันการลบ
                </button>
            </div>
        </form>
    </div>
</div>

<!-- โมดัลรีเซ็ตการเชื่อมต่อ LINE -->
<div class="modal" id="resetLineModal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('resetLineModal')">
            <span class="material-icons">close</span>
        </button>
        <h2 class="modal-title">ยืนยันการรีเซ็ตการเชื่อมต่อ LINE</h2>

        <div class="confirmation-message">
            <p>คุณต้องการรีเซ็ตการเชื่อมต่อ LINE ของผู้ใช้ <strong id="reset_line_user_name">-</strong> ใช่หรือไม่?</p>
            <p class="warning-text">หลังจากรีเซ็ต ผู้ใช้จะต้องเชื่อมต่อ LINE ใหม่อีกครั้ง</p>
        </div>

        <form id="resetLineForm" method="post" action="users.php">
            <input type="hidden" name="action" value="reset_line">
            <input type="hidden" name="user_id" id="reset_line_user_id">

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('resetLineModal')">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <span class="material-icons">refresh</span>
                    ยืนยันการรีเซ็ต
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ส่วนแสดงการแจ้งเตือน -->
<div id="alertContainer" class="alert-container"></div>

<!-- แสดงข้อความแจ้งเตือน (ถ้ามี) -->
<?php if (isset($success_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('<?php echo $success_message; ?>', 'success');
        });
    </script>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showAlert('<?php echo $error_message; ?>', 'error');
        });
    </script>
<?php endif; ?>