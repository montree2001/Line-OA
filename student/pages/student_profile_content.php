<div class="header">
    <a href="home.php" class="header-icon">
        <span class="material-icons">arrow_back</span>
    </a>
    <h1>โปรไฟล์ของฉัน</h1>
    <div class="header-icon">
        <span class="material-icons">notifications</span>
    </div>
</div>

<div class="container">
    <!-- การ์ดโปรไฟล์ -->
    <div class="profile-card">
        <a href="edit_profile.php" class="edit-button">
            <span class="material-icons">edit</span> แก้ไข
        </a>

        <div class="profile-header">
            <div class="profile-image-container">
                <?php if (!empty($student_info['profile_image'])): ?>
                <div class="profile-image">
                    <img src="<?php echo $student_info['profile_image']; ?>" alt="<?php echo $student_info['full_name']; ?>" id="profile-img">
                </div>
                <?php else: ?>
                <div class="profile-image">
                    <?php echo $student_info['avatar']; ?>
                </div>
                <?php endif; ?>
                <label for="upload-photo" class="change-photo">
                    <span class="material-icons">photo_camera</span>
                </label>
                <input type="file" id="upload-photo" accept="image/*" style="display: none;">
            </div>
            <div class="profile-info">
                <div class="profile-name"><?php echo $student_info['full_name']; ?></div>
                <div class="profile-details"><?php echo $student_info['class']; ?> ID <?php echo $student_info['id']; ?></div>
                <div class="profile-details">แผนก: <?php echo $student_info['department']; ?></div>
                <div class="profile-details">รหัสนักเรียน: <?php echo $student_info['student_code']; ?></div>
            </div>
        </div>

        <div class="stats-section">
            <div class="section-title">
                <span class="material-icons">bar_chart</span> สถิติการเข้าแถว
            </div>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $attendance_stats['required_days']; ?></div>
                    <div class="stat-label">วันเรียนทั้งหมด</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $attendance_stats['attended_days']; ?></div>
                    <div class="stat-label">วันเข้าแถว</div>
                </div>
                <div class="stat-box">
                    <?php
                    $percentage = $attendance_stats['attendance_percentage'];
                    $status_class = '';
                    if ($percentage >= 90) {
                        $status_class = 'good';
                    } elseif ($percentage >= 75) {
                        $status_class = 'warning';
                    } else {
                        $status_class = 'danger';
                    }
                    ?>
                    <div class="stat-value <?php echo $status_class; ?>"><?php echo $percentage; ?>%</div>
                    <div class="stat-label">อัตราการเข้าแถว</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ประวัติการศึกษา -->
    <div class="info-card">
        <div class="info-title">
            <span class="material-icons">school</span>
            ข้อมูลส่วนตัว
        </div>
        <div class="info-content">
            <div class="info-item">
                <div class="info-label">วันเกิด:</div>
                <div class="info-value"><?php echo $student_info['birth_date']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">กรุ๊ปเลือด:</div>
                <div class="info-value"><?php echo $student_info['blood_type']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">สัญชาติ:</div>
                <div class="info-value"><?php echo $student_info['nationality']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">ศาสนา:</div>
                <div class="info-value"><?php echo $student_info['religion']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">เบอร์โทรศัพท์:</div>
                <div class="info-value"><?php echo $student_info['phone']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">อีเมล:</div>
                <div class="info-value"><?php echo $student_info['email']; ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">ไลน์ไอดี:</div>
                <div class="info-value"><?php echo $student_info['line_id']; ?></div>
            </div>
        </div>
    </div>

    <!-- ครูที่ปรึกษา -->
    <div class="advisor-card">
        <div class="card-title">
            <span class="material-icons">person</span>
            ครูที่ปรึกษา
        </div>
        <div class="advisor-content">
            <div class="advisor-photo">
                <?php if (!empty($advisor_info['profile_image'])): ?>
                <img src="<?php echo $advisor_info['profile_image']; ?>" alt="<?php echo $advisor_info['full_name']; ?>">
                <?php else: ?>
                <div class="advisor-initial"><?php echo $advisor_info['avatar']; ?></div>
                <?php endif; ?>
            </div>
            <div class="advisor-info">
                <div class="advisor-name"><?php echo $advisor_info['full_name']; ?></div>
                <div class="advisor-details"><?php echo $advisor_info['department']; ?></div>
                <?php if (!empty($advisor_info['phone'])): ?>
                <div class="advisor-contact">
                    <span class="material-icons">phone</span>
                    <?php echo $advisor_info['phone']; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($advisor_info['email'])): ?>
                <div class="advisor-contact">
                    <span class="material-icons">email</span>
                    <?php echo $advisor_info['email']; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($advisor_info['line_id'])): ?>
                <div class="advisor-contact">
                    <span class="material-icons">chat</span>
                    #
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ผู้ปกครอง -->
    <div class="parent-card">
        <div class="card-title">
            <span class="material-icons">family_restroom</span>
            ผู้ปกครอง
        </div>
        <div class="parent-content">
            <div class="parent-photo">
                <?php if (!empty($parent_info['profile_image'])): ?>
                <img src="<?php echo $parent_info['profile_image']; ?>" alt="<?php echo $parent_info['full_name']; ?>">
                <?php else: ?>
                <div class="parent-initial"><?php echo $parent_info['avatar']; ?></div>
                <?php endif; ?>
            </div>
            <div class="parent-info">
                <div class="parent-name"><?php echo $parent_info['full_name']; ?></div>
                <div class="parent-relationship">ความสัมพันธ์: <?php echo $parent_info['relationship']; ?></div>
                <?php if (!empty($parent_info['phone'])): ?>
                <div class="parent-contact">
                    <span class="material-icons">phone</span>
                    <?php echo $parent_info['phone']; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($parent_info['email'])): ?>
                <div class="parent-contact">
                    <span class="material-icons">email</span>
                    <?php echo $parent_info['email']; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($parent_info['line_id'])): ?>
                <div class="parent-contact">
                    <span class="material-icons">chat</span>
                   #
                </div>
                <?php endif; ?>
                <?php if (!empty($parent_info['address'])): ?>
                <div class="parent-address">
                    <span class="material-icons">home</span>
                    <?php echo $parent_info['address']; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ข้อมูลเวอร์ชัน -->
    <div class="version-info">
        <p>STD-Prasat v1.0.0</p>
        <p>© 2025 วิทยาลัยการอาชีพปราสาท</p>
    </div>
</div>

<!-- Modal สำหรับแสดงผลการอัพโหลดรูปภาพ -->
<div id="upload-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>อัพโหลดรูปโปรไฟล์</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div class="image-preview-container">
                <img id="image-preview" src="#" alt="ตัวอย่างรูปภาพ">
            </div>
            <div class="image-controls">
                <div class="slider-container">
                    <span>ขนาด: </span>
                    <input type="range" id="zoom-slider" min="100" max="150" value="100">
                </div>
            </div>
            <div class="upload-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text">0%</div>
            </div>
        </div>
        <div class="modal-footer">
            <button id="cancel-upload" class="btn-cancel">ยกเลิก</button>
            <button id="confirm-upload" class="btn-confirm">อัพโหลด</button>
        </div>
    </div>
</div>

<div id="loading-overlay">
    <div class="spinner"></div>
    <div class="loading-text">กำลังอัพโหลด...</div>
</div>