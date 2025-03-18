<!-- แจ้งเตือน -->
<div class="notification-banner">
    <span class="material-icons icon">campaign</span>
    <div class="content">
        <div class="title">ข่าวสารและประกาศ</div>
        <div class="message">ข้อมูลล่าสุดจากโรงเรียนและคุณครู</div>
    </div>
</div>

<!-- แท็บเมนู -->
<div class="tab-menu">
    <button class="tab-button" onclick="switchTab('overview')">ภาพรวม</button>
    <button class="tab-button" onclick="switchTab('attendance')">การเข้าแถว</button>
    <button class="tab-button active" onclick="switchTab('news')">ข่าวสาร</button>
</div>

<!-- ตัวกรองข่าว -->
<div class="news-filter">
    <div class="filter-tabs">
        <button class="filter-tab active" data-filter="all">ทั้งหมด</button>
        <button class="filter-tab" data-filter="announcement">ประกาศ</button>
        <button class="filter-tab" data-filter="event">กิจกรรม</button>
        <button class="filter-tab" data-filter="exam">การสอบ</button>
    </div>
    
    <div class="search-box">
        <input type="text" placeholder="ค้นหาข่าวสาร..." id="news-search">
        <button class="search-button">
            <span class="material-icons">search</span>
        </button>
    </div>
</div>

<!-- รายการข่าวสาร -->
<div class="news-list">
    <?php if(isset($news) && !empty($news)): ?>
        <?php foreach($news as $item): ?>
            <div class="news-item" data-category="<?php echo $item['category_class']; ?>">
                <div class="news-header">
                    <div class="news-category <?php echo $item['category_class']; ?>"><?php echo $item['category']; ?></div>
                    <div class="news-date"><?php echo $item['date']; ?></div>
                </div>
                <div class="news-title"><?php echo $item['title']; ?></div>
                <div class="news-content"><?php echo $item['short_content']; ?></div>
                <div class="news-footer">
                    <button class="read-more-button" onclick="readMore(<?php echo $item['id']; ?>)">อ่านต่อ</button>
                    <?php if($item['attachment']): ?>
                        <div class="attachment-info">
                            <span class="material-icons">attach_file</span>
                            <span>เอกสารแนบ</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- ข้อมูลตัวอย่าง -->
        <div class="news-item" data-category="exam">
            <div class="news-header">
                <div class="news-category exam">สอบ</div>
                <div class="news-date">14 มี.ค. 2568</div>
            </div>
            <div class="news-title">แจ้งกำหนดการสอบปลายภาค</div>
            <div class="news-content">แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2567 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น. และแต่งกายให้เรียบร้อยด้วยชุดนักเรียน...</div>
            <div class="news-footer">
                <button class="read-more-button" onclick="readMore(1)">อ่านต่อ</button>
                <div class="attachment-info">
                    <span class="material-icons">attach_file</span>
                    <span>เอกสารแนบ</span>
                </div>
            </div>
        </div>
        
        <div class="news-item" data-category="event">
            <div class="news-header">
                <div class="news-category event">กิจกรรม</div>
                <div class="news-date">10 มี.ค. 2568</div>
            </div>
            <div class="news-title">ประชุมผู้ปกครองภาคเรียนที่ 2</div>
            <div class="news-content">ขอเชิญผู้ปกครองทุกท่านเข้าร่วมประชุมผู้ปกครองภาคเรียนที่ 2 ในวันเสาร์ที่ 22 มีนาคม 2568 เวลา 9.00-12.00 น. ณ หอประชุมโรงเรียน เพื่อพบปะคุณครู รับทราบผลการเรียน...</div>
            <div class="news-footer">
                <button class="read-more-button" onclick="readMore(2)">อ่านต่อ</button>
            </div>
        </div>
        
        <div class="news-item" data-category="announcement">
            <div class="news-header">
                <div class="news-category announcement">ประกาศ</div>
                <div class="news-date">5 มี.ค. 2568</div>
            </div>
            <div class="news-title">การจัดกิจกรรมวันไหว้ครู</div>
            <div class="news-content">โรงเรียนจะจัดกิจกรรมวันไหว้ครูประจำปีการศึกษา 2567 ในวันพฤหัสบดีที่ 3 เมษายน 2568 โดยจะมีพิธีไหว้ครูตั้งแต่เวลา 8.30 น. ณ หอประชุมโรงเรียน นักเรียนต้องแต่งกาย...</div>
            <div class="news-footer">
                <button class="read-more-button" onclick="readMore(3)">อ่านต่อ</button>
                <div class="attachment-info">
                    <span class="material-icons">attach_file</span>
                    <span>เอกสารแนบ</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal แสดงรายละเอียดข่าว -->
<div class="news-modal" id="news-detail-modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="news-category" id="modal-category">ประกาศ</div>
            <button class="close-button" onclick="closeNewsModal()">
                <span class="material-icons">close</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="news-title" id="modal-title">หัวข้อข่าว</div>
            <div class="news-metadata">
                <div class="news-date" id="modal-date">
                    <span class="material-icons">event</span>
                    <span>14 มี.ค. 2568</span>
                </div>
                <div class="news-author" id="modal-author">
                    <span class="material-icons">person</span>
                    <span>ผู้ประกาศ: ฝ่ายวิชาการ</span>
                </div>
            </div>
            <div class="news-full-content" id="modal-content">
                เนื้อหาข่าวเต็ม...
            </div>
            <div class="news-attachments" id="modal-attachments">
                <div class="attachment-title">เอกสารแนบ</div>
                <div class="attachment-list">
                    <div class="attachment-item">
                        <span class="material-icons">description</span>
                        <span>กำหนดการสอบปลายภาค.pdf</span>
                        <button class="download-button">
                            <span class="material-icons">file_download</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="share-button" onclick="shareNews()">
                <span class="material-icons">share</span> แชร์
            </button>
            <button class="close-modal-button" onclick="closeNewsModal()">ปิด</button>
        </div>
    </div>
</div>

<!-- JavaScript สำหรับหน้าข่าวสาร -->
<script>
// กรองข่าวตามหมวดหมู่
document.addEventListener('DOMContentLoaded', function() {
    const filterTabs = document.querySelectorAll('.filter-tab');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // ลบคลาส active จากทุก tab
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // เพิ่มคลาส active ให้ tab ที่คลิก
            this.classList.add('active');
            
            // ดึงค่า data-filter
            const filter = this.getAttribute('data-filter');
            
            // กรองรายการข่าว
            filterNews(filter);
        });
    });
    
    // ตั้งค่าค้นหาข่าว
    const searchInput = document.getElementById('news-search');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            searchNews(keyword);
        });
    }
});

// ฟังก์ชันกรองข่าวตามหมวดหมู่
function filterNews(category) {
    const newsItems = document.querySelectorAll('.news-item');
    
    newsItems.forEach(item => {
        if (category === 'all' || item.getAttribute('data-category') === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// ฟังก์ชันค้นหาข่าว
function searchNews(keyword) {
    const newsItems = document.querySelectorAll('.news-item');
    
    newsItems.forEach(item => {
        const title = item.querySelector('.news-title').textContent.toLowerCase();
        const content = item.querySelector('.news-content').textContent.toLowerCase();
        
        if (title.includes(keyword) || content.includes(keyword)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// เปิด Modal แสดงรายละเอียดข่าว
function readMore(newsId) {
    // ในการใช้งานจริงควรมีการดึงข้อมูลข่าวจาก API ตาม ID
    console.log(`อ่านข่าวเพิ่มเติม ID: ${newsId}`);
    
    // จำลองข้อมูลข่าว
    const newsData = {
        id: newsId,
        title: "แจ้งกำหนดการสอบปลายภาค",
        category: "สอบ",
        category_class: "exam",
        date: "14 มี.ค. 2568",
        author: "ฝ่ายวิชาการ",
        content: `แจ้งกำหนดการสอบปลายภาคเรียนที่ 2/2567 ระหว่างวันที่ 1-5 เมษายน 2568 โดยนักเรียนต้องมาถึงโรงเรียนก่อนเวลา 8.00 น. และแต่งกายให้เรียบร้อยด้วยชุดนักเรียน
        
        ตารางสอบ:
        - วันที่ 1 เมษายน 2568: วิชาภาษาไทย, วิชาสังคมศึกษา
        - วันที่ 2 เมษายน 2568: วิชาคณิตศาสตร์, วิชาวิทยาศาสตร์
        - วันที่ 3 เมษายน 2568: วิชาภาษาอังกฤษ, วิชาสุขศึกษา
        - วันที่ 4 เมษายน 2568: วิชาการงานอาชีพ, วิชาศิลปะ
        - วันที่ 5 เมษายน 2568: วิชาเพิ่มเติม
        
        สิ่งที่นักเรียนต้องเตรียม:
        1. บัตรประจำตัวนักเรียน
        2. อุปกรณ์การเรียน (ปากกา ดินสอ ยางลบ ไม้บรรทัด)
        3. อุปกรณ์เฉพาะวิชา (ตามที่คุณครูแต่ละวิชาแจ้ง)
        
        กรณีนักเรียนป่วยไม่สามารถเข้าสอบได้ ผู้ปกครองต้องแจ้งฝ่ายวิชาการล่วงหน้าหรือภายในวันสอบ และนำใบรับรองแพทย์มายื่นเพื่อขอสอบชดเชยภายใน 7 วันหลังจากวันสอบวิชานั้น
        
        งดกิจกรรมเสริมทั้งหมดในช่วงสัปดาห์สอบ
        
        ประกาศผลสอบวันที่ 20 เมษายน 2568
        `,
        has_attachment: true,
        attachments: [
            {
                name: "กำหนดการสอบปลายภาค.pdf",
                type: "pdf",
                size: "1.2 MB"
            }
        ]
    };
    
    // อัพเดต Modal
    document.getElementById('modal-category').textContent = newsData.category;
    document.getElementById('modal-category').className = `news-category ${newsData.category_class}`;
    document.getElementById('modal-title').textContent = newsData.title;
    document.getElementById('modal-date').innerHTML = `<span class="material-icons">event</span><span>${newsData.date}</span>`;
    document.getElementById('modal-author').innerHTML = `<span class="material-icons">person</span><span>ผู้ประกาศ: ${newsData.author}</span>`;
    document.getElementById('modal-content').innerHTML = newsData.content.replace(/\n/g, '<br>');
    
    // ตรวจสอบว่ามีเอกสารแนบหรือไม่
    const attachmentsSection = document.getElementById('modal-attachments');
    if (newsData.has_attachment && newsData.attachments && newsData.attachments.length > 0) {
        let attachmentHTML = '<div class="attachment-title">เอกสารแนบ</div><div class="attachment-list">';
        
        newsData.attachments.forEach(attachment => {
            let icon = 'description';
            
            if (attachment.type === 'image' || attachment.type === 'jpg' || attachment.type === 'png') {
                icon = 'image';
            } else if (attachment.type === 'pdf') {
                icon = 'picture_as_pdf';
            } else if (attachment.type === 'zip' || attachment.type === 'rar') {
                icon = 'folder_zip';
            }
            
            attachmentHTML += `
                <div class="attachment-item">
                    <span class="material-icons">${icon}</span>
                    <span>${attachment.name}</span>
                    <button class="download-button">
                        <span class="material-icons">file_download</span>
                    </button>
                </div>
            `;
        });
        
        attachmentHTML += '</div>';
        attachmentsSection.innerHTML = attachmentHTML;
        attachmentsSection.style.display = 'block';
    } else {
        attachmentsSection.style.display = 'none';
    }
    
    // แสดง Modal
    document.getElementById('news-detail-modal').classList.add('active');
    document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
}

// ปิด Modal
function closeNewsModal() {
    document.getElementById('news-detail-modal').classList.remove('active');
    document.body.style.overflow = ''; // อนุญาตให้เลื่อนหน้าเว็บได้อีกครั้ง
}

// แชร์ข่าว
function shareNews() {
    // ในการใช้งานจริงควรมีการแชร์ผ่าน LINE หรือช่องทางอื่น
    alert('กำลังแชร์ข่าวนี้');
}
</script>

<!-- เพิ่ม CSS เฉพาะสำหรับหน้าข่าวสาร -->
<style>
/* ตัวกรองข่าว */
.news-filter {
    background-color: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: var(--card-shadow);
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.filter-tabs {
    display: flex;
    overflow-x: auto;
    gap: 10px;
    padding-bottom: 5px;
}

.filter-tab {
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    background-color: #f5f5f5;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.3s;
}

.filter-tab.active {
    background-color: var(--primary-color);
    color: white;
}

.search-box {
    display: flex;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
}

.search-box input {
    flex: 1;
    padding: 10px 15px;
    border: none;
    outline: none;
    font-size: 14px;
}

.search-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* รายการข่าวสาร */
.news-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.news-item {
    background-color: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--card-shadow);
    transition: transform 0.3s;
}

.news-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.news-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.news-category {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    color: white;
}

.news-category.announcement {
    background-color: #009688;
}

.news-category.event {
    background-color: #1976d2;
}

.news-category.exam {
    background-color: #f44336;
}

.news-date {
    font-size: 14px;
    color: var(--text-light);
}

.news-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--text-dark);
}

.news-content {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 15px;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.news-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.read-more-button {
    background-color: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
    border-radius: 20px;
    padding: 5px 15px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.3s;
}

.read-more-button:hover {
    background-color: var(--primary-color-light);
}

.attachment-info {
    display: flex;
    align-items: center;
    color: var(--text-light);
    font-size: 12px;
}

.attachment-info .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

/* Modal แสดงรายละเอียดข่าว */
.news-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.news-modal.active {
    display: flex;
    opacity: 1;
}

.modal-content {
    background-color: white;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
}

.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    background-color: white;
    z-index: 1;
}

.close-button {
    background: transparent;
    border: none;
    font-size: 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-light);
}

.modal-body {
    padding: 20px;
    flex: 1;
}

.news-metadata {
    display: flex;
    margin: 15px 0 20px;
    color: var(--text-light);
    font-size: 14px;
}

.news-date, .news-author {
    display: flex;
    align-items: center;
    margin-right: 20px;
}

.news-date .material-icons, .news-author .material-icons {
    font-size: 16px;
    margin-right: 5px;
}

.news-full-content {
    font-size: 15px;
    line-height: 1.6;
    color: var(--text-dark);
    margin-bottom: 30px;
}

.news-attachments {
    background-color: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.attachment-title {
    font-weight: 600;
    margin-bottom: 10px;
    font-size: 15px;
    color: var(--text-dark);
}

.attachment-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.attachment-item {
    display: flex;
    align-items: center;
    background-color: white;
    border-radius: 5px;
    padding: 10px 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.attachment-item .material-icons {
    margin-right: 10px;
    color: var(--text-light);
}

.download-button {
    margin-left: auto;
    background: transparent;
    border: none;
    color: var(--primary-color);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    position: sticky;
    bottom: 0;
    background-color: white;
}

.share-button {
    display: flex;
    align-items: center;
    background-color: var(--primary-color-light);
    color: var(--primary-color);
    border: none;
    border-radius: 20px;
    padding: 8px 15px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.share-button .material-icons {
    font-size: 18px;
    margin-right: 5px;
}

.close-modal-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .news-filter {
        padding: 12px;
    }
    
    .filter-tabs {
        padding-bottom: 10px;
    }
    
    .filter-tab {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .news-item {
        padding: 15px;
    }
    
    .news-title {
        font-size: 16px;
    }
    
    .news-metadata {
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-content {
        width: 95%;
    }
}

@media (max-width: 480px) {
    .filter-tabs {
        padding-bottom: 5px;
    }
    
    .filter-tab {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    .news-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .news-title {
        font-size: 15px;
    }
    
    .news-content {
        font-size: 13px;
        -webkit-line-clamp: 2;
    }
    
    .news-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .attachment-item {
        font-size: 12px;
    }
}