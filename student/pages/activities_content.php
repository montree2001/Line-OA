<div class="header">
    <button class="back-button" onclick="history.back()">
        <span class="material-icons">arrow_back</span>
    </button>
    <div class="header-title">กิจกรรมนักเรียน</div>
    <div class="header-spacer"></div>
</div>

<div class="container">
    <!-- สรุปโปรไฟล์ -->
    <div class="profile-summary">
        <div class="profile-info">
            <?php if (!empty($student_info['profile_picture'])): ?>
                <div class="profile-image profile-image-pic" style="background-image: url('<?php echo $student_info['profile_picture']; ?>');"></div>
            <?php else: ?>
                <div class="profile-image"><?php echo $first_char; ?></div>
            <?php endif; ?>
            <div class="profile-details">
                <div class="profile-name"><?php echo $student_info['name']; ?></div>
                <div class="profile-class"><?php echo $student_info['class']; ?></div>
            </div>
        </div>
    </div>

    <!-- การ์ดสรุปกิจกรรม -->
    <div class="activities-summary-card">
        <h2>สรุปการเข้าร่วมกิจกรรม</h2>
        
        <div class="activities-stats">
            <div class="stat-item">
                <div class="stat-value"><?php echo $activities_summary['total']; ?></div>
                <div class="stat-label">กิจกรรมทั้งหมด</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $activities_summary['participated']; ?></div>
                <div class="stat-label">เข้าร่วมแล้ว</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $activities_summary['not_participated']; ?></div>
                <div class="stat-label">ยังไม่ได้เข้าร่วม</div>
            </div>
        </div>
        
        <div class="participation-progress">
            <div class="progress-label">อัตราการเข้าร่วมกิจกรรม</div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $activities_summary['percentage']; ?>%;">
                    <span class="progress-text"><?php echo $activities_summary['percentage']; ?>%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- แท็บกิจกรรม -->
    <div class="tabs-container">
        <div class="tab-header">
            <div class="tab-item active" data-tab="all">ทั้งหมด</div>
            <div class="tab-item" data-tab="participated">เข้าร่วมแล้ว</div>
            <div class="tab-item" data-tab="not-participated">ยังไม่ได้เข้าร่วม</div>
        </div>
        
        <!-- แท็บเนื้อหาทั้งหมด -->
        <div class="tab-content active" id="all-tab">
            <?php if (empty($all_activities)): ?>
                <div class="empty-message">
                    <div class="empty-icon"><span class="material-icons">event_busy</span></div>
                    <div>ยังไม่มีกิจกรรมในขณะนี้</div>
                </div>
            <?php else: ?>
                <div class="activities-list">
                    <?php foreach ($all_activities as $activity): ?>
                        <div class="activity-card <?php echo $activity['attended'] ? 'attended' : 'not-attended'; ?>">
                            <div class="activity-status">
                                <?php if ($activity['attended']): ?>
                                    <span class="status-badge attended">
                                        <span class="material-icons">check_circle</span> เข้าร่วมแล้ว
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge not-attended">
                                        <span class="material-icons">pending</span> ยังไม่ได้เข้าร่วม
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="activity-header">
                                <h3 class="activity-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                <?php if (isset($activity['required_attendance']) && $activity['required_attendance']): ?>
                                    <span class="required-badge">กิจกรรมบังคับ</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="activity-details">
                                <div class="detail-item">
                                    <span class="material-icons">event</span>
                                    <span><?php echo htmlspecialchars($activity['thai_date']); ?></span>
                                </div>
                                
                                <?php if (!empty($activity['activity_location'])): ?>
                                    <div class="detail-item">
                                        <span class="material-icons">location_on</span>
                                        <span><?php echo htmlspecialchars($activity['activity_location']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($activity['description'])): ?>
                                <div class="activity-description">
                                    <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($activity['attended'] && isset($activity['record_time'])): ?>
                                <div class="attendance-details">
                                    <div class="detail-item">
                                        <span class="material-icons">access_time</span>
                                        <span>บันทึกเมื่อ: <?php echo date('d/m/Y H:i น.', strtotime($activity['record_time'])); ?></span>
                                    </div>
                                    <?php if (!empty($activity['remarks'])): ?>
                                        <div class="detail-item">
                                            <span class="material-icons">comment</span>
                                            <span>หมายเหตุ: <?php echo htmlspecialchars($activity['remarks']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- แท็บกิจกรรมที่เข้าร่วมแล้ว -->
        <div class="tab-content" id="participated-tab">
            <?php if (empty($participated)): ?>
                <div class="empty-message">
                    <div class="empty-icon"><span class="material-icons">sentiment_dissatisfied</span></div>
                    <div>คุณยังไม่ได้เข้าร่วมกิจกรรมใดๆ</div>
                </div>
            <?php else: ?>
                <div class="activities-list">
                    <?php foreach ($participated as $activity): ?>
                        <div class="activity-card attended">
                            <div class="activity-status">
                                <span class="status-badge attended">
                                    <span class="material-icons">check_circle</span> เข้าร่วมแล้ว
                                </span>
                            </div>
                            
                            <div class="activity-header">
                                <h3 class="activity-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                <?php if (isset($activity['required_attendance']) && $activity['required_attendance']): ?>
                                    <span class="required-badge">จำเป็น</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="activity-details">
                                <div class="detail-item">
                                    <span class="material-icons">event</span>
                                    <span><?php echo htmlspecialchars($activity['thai_date']); ?></span>
                                </div>
                                
                                <?php if (!empty($activity['activity_location'])): ?>
                                    <div class="detail-item">
                                        <span class="material-icons">location_on</span>
                                        <span><?php echo htmlspecialchars($activity['activity_location']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($activity['description'])): ?>
                                <div class="activity-description">
                                    <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (isset($activity['record_time'])): ?>
                                <div class="attendance-details">
                                    <div class="detail-item">
                                        <span class="material-icons">access_time</span>
                                        <span>บันทึกเมื่อ: <?php echo date('d/m/Y H:i น.', strtotime($activity['record_time'])); ?></span>
                                    </div>
                                    <?php if (!empty($activity['remarks'])): ?>
                                        <div class="detail-item">
                                            <span class="material-icons">comment</span>
                                            <span>หมายเหตุ: <?php echo htmlspecialchars($activity['remarks']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- แท็บกิจกรรมที่ยังไม่ได้เข้าร่วม -->
        <div class="tab-content" id="not-participated-tab">
            <?php if (empty($not_participated)): ?>
                <div class="empty-message">
                    <div class="empty-icon"><span class="material-icons">celebration</span></div>
                    <div>คุณได้เข้าร่วมกิจกรรมทั้งหมดแล้ว!</div>
                </div>
            <?php else: ?>
                <div class="activities-list">
                    <?php foreach ($not_participated as $activity): ?>
                        <div class="activity-card not-attended">
                            <div class="activity-status">
                                <span class="status-badge not-attended">
                                    <span class="material-icons">pending</span> ยังไม่ได้เข้าร่วม
                                </span>
                            </div>
                            
                            <div class="activity-header">
                                <h3 class="activity-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                <?php if (isset($activity['required_attendance']) && $activity['required_attendance']): ?>
                                    <span class="required-badge">จำเป็น</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="activity-details">
                                <div class="detail-item">
                                    <span class="material-icons">event</span>
                                    <span><?php echo htmlspecialchars($activity['thai_date']); ?></span>
                                </div>
                                
                                <?php if (!empty($activity['activity_location'])): ?>
                                    <div class="detail-item">
                                        <span class="material-icons">location_on</span>
                                        <span><?php echo htmlspecialchars($activity['activity_location']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($activity['description'])): ?>
                                <div class="activity-description">
                                    <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- ถ้ากิจกรรมยังไม่ผ่านวันที่จัด แสดงปุ่มให้ดูรายละเอียด -->
                            <?php if (isset($activity['activity_date']) && strtotime($activity['activity_date']) >= strtotime(date('Y-m-d'))): ?>
                                <div class="activity-actions">
                                    <button class="view-details-btn" onclick="viewActivityDetails(<?php echo $activity['activity_id']; ?>)">
                                        <span class="material-icons">info</span> ดูรายละเอียด
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชั่นเปลี่ยนแท็บ
    document.addEventListener('DOMContentLoaded', function() {
        const tabItems = document.querySelectorAll('.tab-item');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabItems.forEach(function(tab) {
            tab.addEventListener('click', function() {
                // ลบคลาส active จากทุกแท็บ
                tabItems.forEach(item => item.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // เพิ่มคลาส active ให้แท็บที่คลิก
                this.classList.add('active');
                
                // เปิดเนื้อหาแท็บที่ตรงกัน
                const tabId = this.getAttribute('data-tab');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // ฟังก์ชั่นดูรายละเอียดกิจกรรม
        window.viewActivityDetails = function(activityId) {
            // สามารถใส่โค้ดเพื่อแสดงหน้าต่างรายละเอียดเพิ่มเติมได้ที่นี่
            alert('ดูรายละเอียดกิจกรรม ID: ' + activityId);
            // หรือเปิดหน้าใหม่: window.location.href = 'activity_detail.php?id=' + activityId;
        };
    });
</script>