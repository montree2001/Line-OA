<div class="header">
    <button class="back-button" onclick="history.back()">
        <span class="material-icons">arrow_back</span>
    </button>
    <div class="header-title">รายละเอียดกิจกรรม</div>
    <div class="header-spacer"></div>
</div>

<div class="container">
    <div class="activity-detail-card">
        <div class="activity-header">
            <h1 class="activity-title"><?php echo $activity_data['name']; ?></h1>
            <?php echo $activity_data['required_badge']; ?>
        </div>
        
        <div class="activity-status-container">
            <div class="activity-status <?php echo $activity_data['status_class']; ?>">
                <span class="material-icons"><?php echo getStatusIcon($activity_data['status_class']); ?></span>
                <?php echo $activity_data['attendance_status']; ?>
            </div>
        </div>
        
        <div class="activity-info">
            <div class="info-item">
                <div class="info-icon">
                    <span class="material-icons">event</span>
                </div>
                <div class="info-content">
                    <div class="info-label">วันที่จัดกิจกรรม</div>
                    <div class="info-value"><?php echo $activity_data['date']; ?></div>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">
                    <span class="material-icons">location_on</span>
                </div>
                <div class="info-content">
                    <div class="info-label">สถานที่</div>
                    <div class="info-value"><?php echo $activity_data['location']; ?></div>
                </div>
            </div>
            
            <?php if (!empty($activity_data['target_departments'])): ?>
            <div class="info-item">
                <div class="info-icon">
                    <span class="material-icons">domain</span>
                </div>
                <div class="info-content">
                    <div class="info-label">แผนกที่เข้าร่วม</div>
                    <div class="info-value"><?php echo implode(', ', $activity_data['target_departments']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($activity_data['target_levels'])): ?>
            <div class="info-item">
                <div class="info-icon">
                    <span class="material-icons">school</span>
                </div>
                <div class="info-content">
                    <div class="info-label">ระดับชั้นที่เข้าร่วม</div>
                    <div class="info-value"><?php echo implode(', ', $activity_data['target_levels']); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <div class="info-icon">
                    <span class="material-icons">person</span>
                </div>
                <div class="info-content">
                    <div class="info-label">ผู้ดูแลกิจกรรม</div>
                    <div class="info-value"><?php echo $activity_data['creator']; ?></div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($activity_data['description'])): ?>
        <div class="activity-description">
            <h2>รายละเอียด</h2>
            <div class="description-content">
                <?php echo nl2br($activity_data['description']); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="activity-actions">
            <button class="btn secondary" onclick="history.back()">
                <span class="material-icons">arrow_back</span> กลับ
            </button>
            
            <?php if (!$activity_data['is_past'] && $activity_data['status_class'] !== 'status-present'): ?>
            <button class="btn primary" id="register-btn">
                <span class="material-icons">how_to_reg</span> ลงทะเบียนเข้าร่วม
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function getStatusIcon($status_class) {
    switch ($status_class) {
        case 'status-present':
            return 'check_circle';
        case 'status-absent':
            return 'cancel';
        case 'status-pending':
        default:
            return 'schedule';
    }
}
?>

<!-- Modal ยืนยันการลงทะเบียน -->
<div class="modal" id="register-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>ยืนยันการลงทะเบียน</h2>
            <button class="close-modal" id="close-modal">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <p>คุณต้องการลงทะเบียนเข้าร่วมกิจกรรม "<strong><?php echo $activity_data['name']; ?></strong>" ใช่หรือไม่?</p>
            <p>กิจกรรมจะจัดขึ้นในวันที่ <?php echo $activity_data['date']; ?> ณ <?php echo $activity_data['location']; ?></p>
        </div>
        <div class="modal-footer">
            <button class="btn secondary" id="cancel-btn">ยกเลิก</button>
            <button class="btn primary" id="confirm-register-btn">ยืนยันการลงทะเบียน</button>
        </div>
    </div>
</div>

<script>
    // เพิ่มการทำงานของ Modal
    document.addEventListener('DOMContentLoaded', function() {
        const registerBtn = document.getElementById('register-btn');
        const registerModal = document.getElementById('register-modal');
        const closeModal = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-btn');
        const confirmRegisterBtn = document.getElementById('confirm-register-btn');
        
        if (registerBtn) {
            registerBtn.addEventListener('click', function() {
                registerModal.style.display = 'flex';
            });
        }
        
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                registerModal.style.display = 'none';
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                registerModal.style.display = 'none';
            });
        }
        
        if (confirmRegisterBtn) {
            confirmRegisterBtn.addEventListener('click', function() {
                // ส่งข้อมูลลงทะเบียนไปยัง API
                fetch('api/register_activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        activity_id: <?php echo $activity_data['id']; ?>
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // ลงทะเบียนสำเร็จ
                        alert('ลงทะเบียนเข้าร่วมกิจกรรมสำเร็จ');
                        window.location.reload();
                    } else {
                        // ลงทะเบียนไม่สำเร็จ
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                    registerModal.style.display = 'none';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการลงทะเบียน');
                    registerModal.style.display = 'none';
                });
            });
        }
        
        // ปิด modal เมื่อคลิกภายนอก
        window.addEventListener('click', function(event) {
            if (event.target === registerModal) {
                registerModal.style.display = 'none';
            }
        });
    });
</script>