<div class="container">
        <!-- การ์ดแจ้งเตือน -->
        <div class="alert-card">
            <span class="material-icons alert-icon">warning</span>
            <div class="alert-content">
                <div class="alert-title">แจ้งเตือนเวลาเช็คชื่อ</div>
                <div class="alert-message">เช็คชื่อเข้าแถวได้ถึงเวลา 08:30 น. เท่านั้น</div>
            </div>
        </div>

        <!-- โปรไฟล์และสถิติ -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-image">อ</div>
                <div class="profile-info">
                    <div class="profile-name">นายเอกชัย รักเรียน</div>
                    <div class="profile-details">ม.6/1 เลขที่ 15</div>
                    <div class="profile-status status-present">
                        <span class="material-icons">check_circle</span> เข้าแถวแล้ววันนี้
                    </div>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value">97</div>
                    <div class="stat-label">วันเรียนทั้งหมด</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">97</div>
                    <div class="stat-label">วันเข้าแถว</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value good">100%</div>
                    <div class="stat-label">อัตราการเข้าแถว</div>
                </div>
            </div>
        </div>

        <!-- ปุ่มเช็คชื่อ -->
        <button class="check-in-button" onclick="location.href='check-in.html'">
            <span class="material-icons">how_to_reg</span> เช็คชื่อเข้าแถววันนี้
        </button>

        <!-- ประวัติการเช็คชื่อล่าสุด -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="material-icons">history</span> ประวัติการเช็คชื่อล่าสุด
                </div>
                <a href="#" class="view-all">ดูทั้งหมด</a>
            </div>
            
            <ul class="history-list">
                <li class="history-item">
                    <div class="history-date">
                        <div class="history-day">16</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-content">
                        <div class="history-status">
                            <div class="status-dot present"></div>
                            <div class="history-status-text">เข้าแถว</div>
                        </div>
                        <div class="history-time">เช็คชื่อเวลา 07:45 น.</div>
                        <div class="history-method">
                            <span class="material-icons">gps_fixed</span> เช็คชื่อผ่าน GPS
                        </div>
                    </div>
                </li>
                
                <li class="history-item">
                    <div class="history-date">
                        <div class="history-day">15</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-content">
                        <div class="history-status">
                            <div class="status-dot present"></div>
                            <div class="history-status-text">เข้าแถว</div>
                        </div>
                        <div class="history-time">เช็คชื่อเวลา 07:40 น.</div>
                        <div class="history-method">
                            <span class="material-icons">pin</span> เช็คชื่อด้วยรหัส PIN
                        </div>
                    </div>
                </li>
                
                <li class="history-item">
                    <div class="history-date">
                        <div class="history-day">14</div>
                        <div class="history-month">มี.ค.</div>
                    </div>
                    <div class="history-content">
                        <div class="history-status">
                            <div class="status-dot present"></div>
                            <div class="history-status-text">เข้าแถว</div>
                        </div>
                        <div class="history-time">เช็คชื่อเวลา 07:38 น.</div>
                        <div class="history-method">
                            <span class="material-icons">qr_code_scanner</span> เช็คชื่อด้วย QR Code
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <!-- ประกาศจากโรงเรียน -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="material-icons">campaign</span> ประกาศจากโรงเรียน
                </div>
                <a href="#" class="view-all">ดูทั้งหมด</a>
            </div>
            
            <ul class="announcement-list">
                <li class="announcement-item">
                    <div class="announcement-title">
                        <span class="announcement-badge badge-urgent">ด่วน</span>
                        แจ้งกำหนดการสอบปลายภาค
                    </div>
                    <div class="announcement-content">
                        แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2568 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น.
                    </div>
                    <div class="announcement-date">
                        <span class="material-icons">event</span> 14 มี.ค. 2025
                    </div>
                </li>
                
                <li class="announcement-item">
                    <div class="announcement-title">
                        <span class="announcement-badge badge-event">กิจกรรม</span>
                        ประชุมผู้ปกครองภาคเรียนที่ 2
                    </div>
                    <div class="announcement-content">
                        ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน
                    </div>
                    <div class="announcement-date">
                        <span class="material-icons">event</span> 10 มี.ค. 2025
                    </div>
                </li>
                
                <li class="announcement-item">
                    <div class="announcement-title">
                        <span class="announcement-badge badge-info">ข่าวสาร</span>
                        แนะแนวการศึกษาต่อ
                    </div>
                    <div class="announcement-content">
                        จะมีการแนะแนวการศึกษาต่อระดับอุดมศึกษา ในวันพุธที่ 26 มีนาคม 2568 เวลา 13.00-16.00 น. ณ หอประชุมโรงเรียน
                    </div>
                    <div class="announcement-date">
                        <span class="material-icons">event</span> 8 มี.ค. 2025
                    </div>
                </li>
            </ul>
        </div>
    </div>
