/**
 * parent-dashboard.js - ไฟล์ JavaScript ที่ปรับปรุงสำหรับหน้าหลักผู้ปกครอง SADD-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่าการทำงานของแท็บ
    setupTabs();

    // ตั้งค่าการทำงานของ Student Card
    setupStudentCards();

    // ตั้งค่าการแจ้งเตือน
    setupNotifications();

    // ตั้งค่าการแตะสองครั้งบนอุปกรณ์สัมผัส
    setupTouchEvents();

    // ตั้งค่าปุ่มติดต่อครู
    setupTeacherContacts();

    // ตั้งค่าการแสดงรายละเอียดเพิ่มเติม
    setupExpandableItems();

    // อัพเดตเวลาทุกนาที
    setInterval(updateRelativeTimes, 60000);
    updateRelativeTimes(); // เรียกครั้งแรกทันที
});

/**
 * ตั้งค่าการทำงานของระบบการแจ้งเตือน
 */
function setupNotifications() {
    const notificationBanners = document.querySelectorAll('.notification-banner');

    notificationBanners.forEach(banner => {
        // เพิ่มปุ่มปิดการแจ้งเตือน
        const closeButton = document.createElement('button');
        closeButton.classList.add('close-notification');
        closeButton.innerHTML = '<span class="material-icons">close</span>';

        closeButton.addEventListener('click', function() {
            // ใส่เอฟเฟกต์การเคลื่อนไหวก่อนที่จะลบ
            banner.style.opacity = '0';
            banner.style.transform = 'translateX(10px)';
            setTimeout(() => {
                banner.style.display = 'none';
            }, 300);

            // บันทึกสถานะการปิดใน localStorage เพื่อไม่ให้แสดงอีก
            const notificationId = banner.getAttribute('data-id');
            if (notificationId) {
                localStorage.setItem(`notification_${notificationId}_dismissed`, 'true');
            }
        });

        banner.appendChild(closeButton);
    });

    // ตรวจสอบการแจ้งเตือนที่เคยปิดไปแล้ว
    notificationBanners.forEach(banner => {
        const notificationId = banner.getAttribute('data-id');
        if (notificationId && localStorage.getItem(`notification_${notificationId}_dismissed`) === 'true') {
            banner.style.display = 'none';
        }
    });
}

/**
 * สลับแท็บ
 * @param {string} tabName - ชื่อแท็บที่ต้องการเปิด
 */
function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // ซ่อนทุก tab content ก่อน
    tabContents.forEach(content => content.style.display = 'none');

    // ลบคลาส active จากทุกแท็บ
    tabs.forEach(tab => tab.classList.remove('active'));

    // ตั้งค่าแท็บที่เลือกและแสดงเนื้อหาที่เกี่ยวข้อง
    if (tabName === 'overview') {
        tabs[0].classList.add('active');
        document.getElementById('overview-content').style.display = 'block';
    } else if (tabName === 'attendance') {
        tabs[1].classList.add('active');
        document.getElementById('attendance-content').style.display = 'block';
        loadAttendanceData();
    } else if (tabName === 'news') {
        tabs[2].classList.add('active');
        document.getElementById('news-content').style.display = 'block';
        loadNewsData();
    }

    // บันทึกแท็บที่เลือกลงใน localStorage เพื่อให้เมื่อโหลดหน้าใหม่ยังคงอยู่ที่แท็บเดิม
    localStorage.setItem('selectedTab', tabName);
}

/**
 * ตั้งค่าการทำงานของแท็บ
 */
function setupTabs() {
    const tabs = document.querySelectorAll('.tab-button');

    if (tabs.length === 0) return; // ไม่มีแท็บในหน้านี้

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // หา tab-name จากข้อความในแท็บ
            const tabText = this.textContent.trim().toLowerCase();
            let tabName = 'overview';

            if (tabText === 'การเข้าแถว') {
                tabName = 'attendance';
            } else if (tabText === 'ข่าวสาร') {
                tabName = 'news';
            }

            switchTab(tabName);
        });
    });

    // เรียกใช้แท็บที่เคยเลือกไว้ (หรือแท็บแรกถ้าไม่มี)
    const savedTab = localStorage.getItem('selectedTab') || 'overview';
    switchTab(savedTab);
}

/**
 * ตั้งค่าการทำงานของ Student Card
 */
function setupStudentCards() {
    const studentCards = document.querySelectorAll('.student-card');
    const studentCardsContainer = document.querySelector('.student-cards');

    if (studentCards.length === 0) return; // ไม่มีการ์ดนักเรียนในหน้านี้

    // เพิ่มปุ่มเลื่อนซ้าย-ขวาสำหรับหน้าจอที่ไม่ใช่อุปกรณ์สัมผัส
    if (studentCardsContainer && !isTouchDevice() && studentCards.length > 2) {
        addScrollButtons(studentCardsContainer);
    }

    studentCards.forEach(card => {
        card.addEventListener('click', function() {
            // ไปที่หน้ารายละเอียดนักเรียน
            const studentId = this.getAttribute('data-id') || '0';
            window.location.href = `student_detail.php?id=${studentId}`;
        });
    });

    // ตั้งค่า horizontal scroll ให้เลื่อนลื่นและเรียบ
    if (studentCardsContainer) {
        setupSmoothScroll(studentCardsContainer);
    }
}

/**
 * เพิ่มปุ่มเลื่อนซ้าย-ขวาสำหรับ student-cards
 * @param {HTMLElement} container - คอนเทนเนอร์ที่ต้องการเพิ่มปุ่ม
 */
function addScrollButtons(container) {
    const scrollLeftBtn = document.createElement('button');
    const scrollRightBtn = document.createElement('button');

    scrollLeftBtn.className = 'scroll-btn scroll-left';
    scrollRightBtn.className = 'scroll-btn scroll-right';

    scrollLeftBtn.innerHTML = '<span class="material-icons">chevron_left</span>';
    scrollRightBtn.innerHTML = '<span class="material-icons">chevron_right</span>';

    // สไตล์ CSS สำหรับปุ่มเลื่อน
    const scrollBtnStyle = `
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .scroll-left {
            left: -10px;
        }
        .scroll-right {
            right: -10px;
        }
    `;

    // เพิ่ม style ลงในหน้า
    const styleElement = document.createElement('style');
    styleElement.textContent = scrollBtnStyle;
    document.head.appendChild(styleElement);

    // เพิ่ม position: relative ให้ container
    container.style.position = 'relative';

    // เพิ่มปุ่มลงใน DOM
    container.parentNode.appendChild(scrollLeftBtn);
    container.parentNode.appendChild(scrollRightBtn);

    // ตั้งค่าการทำงานของปุ่ม
    scrollLeftBtn.addEventListener('click', () => {
        container.scrollBy({ left: -250, behavior: 'smooth' });
    });

    scrollRightBtn.addEventListener('click', () => {
        container.scrollBy({ left: 250, behavior: 'smooth' });
    });

    // ซ่อน/แสดงปุ่มตามตำแหน่งการเลื่อน
    const updateScrollButtons = () => {
        if (container.scrollLeft <= 0) {
            scrollLeftBtn.style.opacity = '0.5';
            scrollLeftBtn.style.pointerEvents = 'none';
        } else {
            scrollLeftBtn.style.opacity = '1';
            scrollLeftBtn.style.pointerEvents = 'auto';
        }

        if (container.scrollLeft + container.offsetWidth >= container.scrollWidth - 5) {
            scrollRightBtn.style.opacity = '0.5';
            scrollRightBtn.style.pointerEvents = 'none';
        } else {
            scrollRightBtn.style.opacity = '1';
            scrollRightBtn.style.pointerEvents = 'auto';
        }
    };

    container.addEventListener('scroll', updateScrollButtons);
    updateScrollButtons(); // เรียกใช้ครั้งแรก
}

/**
 * ตั้งค่า smooth scroll สำหรับ container ที่มีการเลื่อนในแนวนอน
 * @param {HTMLElement} container - คอนเทนเนอร์ที่ต้องการตั้งค่า
 */
function setupSmoothScroll(container) {
    let isDown = false;
    let startX;
    let scrollLeft;

    container.addEventListener('mousedown', (e) => {
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
    });

    container.addEventListener('mouseleave', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });

    container.addEventListener('mouseup', () => {
        isDown = false;
        container.style.cursor = 'grab';
    });

    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 2; // * 2 เพื่อให้เลื่อนเร็วขึ้น
        container.scrollLeft = scrollLeft - walk;
    });

    // เพิ่ม cursor: grab เมื่อ hover
    container.style.cursor = 'grab';
}

/**
 * ตั้งค่า touch events สำหรับอุปกรณ์สัมผัส
 */
function setupTouchEvents() {
    // การแตะสองครั้งสำหรับเปิดรายละเอียด
    const touchableElements = document.querySelectorAll('.student-card, .contact-teacher, .announcement-item');
    let lastTap = 0;
    let touchTimeout;

    touchableElements.forEach(element => {
        element.addEventListener('touchend', function(e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;

            clearTimeout(touchTimeout);

            if (tapLength < 500 && tapLength > 0) {
                // Double tap - ไปที่หน้ารายละเอียด
                if (element.classList.contains('student-card')) {
                    const studentId = element.getAttribute('data-id') || '0';
                    window.location.href = `student_detail.php?id=${studentId}`;
                } else if (element.classList.contains('contact-teacher')) {
                    const teacherId = element.getAttribute('data-teacher-id') || '0';
                    window.location.href = `teacher_detail.php?id=${teacherId}`;
                } else if (element.classList.contains('announcement-item')) {
                    const announcementId = element.getAttribute('data-id') || '0';
                    window.location.href = `announcement_detail.php?id=${announcementId}`;
                }
                e.preventDefault();
            } else {
                // Single tap - แสดงการตอบสนอง
                element.classList.add('touch-active');

                touchTimeout = setTimeout(() => {
                    element.classList.remove('touch-active');
                }, 300);
            }

            lastTap = currentTime;
        });
    });

    // การแตะส่วนสถานะการเข้าแถวเป็นเวลานานเพื่อแสดงรายละเอียด
    const statusElements = document.querySelectorAll('.student-status');

    statusElements.forEach(status => {
        let pressTimer;

        status.addEventListener('touchstart', function(e) {
            e.stopPropagation(); // ป้องกันการลุกลามของเหตุการณ์

            pressTimer = setTimeout(() => {
                // แสดงรายละเอียดเพิ่มเติมเมื่อกดค้าง
                this.classList.add('expanded');

                // สร้างการสั่น (ถ้ารองรับ)
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
            }, 500);
        });

        status.addEventListener('touchend touchcancel', function() {
            clearTimeout(pressTimer);
            // ดีเลย์การซ่อนเพื่อให้ผู้ใช้ได้อ่าน
            setTimeout(() => {
                this.classList.remove('expanded');
            }, 3000); // 3 วินาที
        });

        status.addEventListener('touchmove', function() {
            clearTimeout(pressTimer);
        });
    });

    // เพิ่มคลาส CSS สำหรับการตอบสนองเมื่อแตะ
    const touchStyle = `
        .touch-active {
            opacity: 0.8;
            transform: scale(0.98);
        }
        
        .expanded {
            max-width: none !important;
            z-index: 100 !important;
        }
        
        .expanded .status-details {
            display: inline !important;
        }
    `;

    const styleElement = document.createElement('style');
    styleElement.textContent = touchStyle;
    document.head.appendChild(styleElement);
}

/**
 * ตรวจสอบว่าเป็นอุปกรณ์สัมผัสหรือไม่
 * @returns {boolean} - true ถ้าเป็นอุปกรณ์สัมผัส
 */
function isTouchDevice() {
    return (('ontouchstart' in window) ||
        (navigator.maxTouchPoints > 0) ||
        (navigator.msMaxTouchPoints > 0));
}

/**
 * ตั้งค่าปุ่มติดต่อครู
 */
function setupTeacherContacts() {
    const callButtons = document.querySelectorAll('.contact-button.call');
    const messageButtons = document.querySelectorAll('.contact-button.message');

    callButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // ป้องกันการลุกลามของเหตุการณ์
            const phone = this.getAttribute('data-phone');
            if (phone) {
                window.location.href = `tel:${phone}`;
            }
        });
    });

    messageButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation(); // ป้องกันการลุกลามของเหตุการณ์
            const teacherId = this.getAttribute('data-teacher-id');
            if (teacherId) {
                window.location.href = `messages.php?teacher=${teacherId}`;
            }
        });
    });
}

/**
 * ตั้งค่ารายการที่สามารถขยายเพื่อดูรายละเอียดเพิ่มเติม
 */
function setupExpandableItems() {
    const expandableItems = document.querySelectorAll('.announcement-item, .activity-item');

    expandableItems.forEach(item => {
        // เพิ่มไอคอนขยาย
        const expandIcon = document.createElement('span');
        expandIcon.classList.add('material-icons', 'expand-icon');
        expandIcon.textContent = 'expand_more';
        expandIcon.style.fontSize = '16px';
        expandIcon.style.color = '#999';
        expandIcon.style.marginLeft = 'auto';
        expandIcon.style.cursor = 'pointer';

        // หาตำแหน่งที่เหมาะสมสำหรับใส่ไอคอน
        if (item.classList.contains('announcement-item')) {
            const header = item.querySelector('.announcement-header');
            if (header) {
                header.appendChild(expandIcon);
            }
        } else if (item.classList.contains('activity-item')) {
            const content = item.querySelector('.activity-content');
            if (content) {
                const timeElement = content.querySelector('.activity-time');
                if (timeElement) {
                    timeElement.style.display = 'flex';
                    timeElement.style.justifyContent = 'space-between';
                    timeElement.style.alignItems = 'center';
                    timeElement.appendChild(expandIcon);
                }
            }
        }

        // ตั้งค่าการคลิกที่ไอคอน
        expandIcon.addEventListener('click', function(e) {
            e.stopPropagation(); // ป้องกันการลุกลามของเหตุการณ์

            // สลับสถานะการขยาย
            const isExpanded = item.classList.toggle('expanded');

            // เปลี่ยนไอคอน
            this.textContent = isExpanded ? 'expand_less' : 'expand_more';

            // จัดการกับเนื้อหาที่ต้องการขยาย
            if (item.classList.contains('announcement-item')) {
                const text = item.querySelector('.announcement-text');
                if (text) {
                    text.style.display = isExpanded ? 'block' : '-webkit-box';
                    text.style.webkitLineClamp = isExpanded ? 'unset' : '2';
                    text.style.maxHeight = isExpanded ? 'none' : null;
                }
            } else if (item.classList.contains('activity-item')) {
                const title = item.querySelector('.activity-title');
                if (title) {
                    title.style.display = isExpanded ? 'block' : '-webkit-box';
                    title.style.webkitLineClamp = isExpanded ? 'unset' : '2';
                    title.style.maxHeight = isExpanded ? 'none' : null;
                }
            }
        });
    });
}

/**
 * อัปเดตเวลาแบบ Relative (เช่น "2 นาทีที่แล้ว", "เมื่อวาน", ฯลฯ)
 */
function updateRelativeTimes() {
    const timeElements = document.querySelectorAll('.activity-time, .announcement-date');

    timeElements.forEach(el => {
        const timestamp = el.getAttribute('data-timestamp');
        if (timestamp) {
            el.textContent = getRelativeTimeString(parseInt(timestamp));
        }
    });
}

/**
 * แปลงเวลาเป็นข้อความแบบ Relative
 * @param {number} timestamp - timestamp ในรูปแบบ Unix timestamp (วินาที)
 * @returns {string} - ข้อความแสดงเวลาแบบ Relative
 */
function getRelativeTimeString(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;

    if (diff < 60) {
        return 'เมื่อสักครู่';
    } else if (diff < 3600) {
        const minutes = Math.floor(diff / 60);
        return `${minutes} นาทีที่แล้ว`;
    } else if (diff < 86400) {
        const hours = Math.floor(diff / 3600);
        return `${hours} ชั่วโมงที่แล้ว`;
    } else if (diff < 172800) {
        return 'เมื่อวาน';
    } else {
        // สร้างข้อความวันที่
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString('th-TH', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }
}

/**
 * โหลดข้อมูลการเข้าแถว
 */
function loadAttendanceData() {
    // โค้ดสำหรับโหลดข้อมูลการเข้าแถวจาก API หรือ AJAX
    console.log("กำลังโหลดข้อมูลการเข้าแถว");

    // ตัวอย่างการแสดง loading indicator
    const attendanceContent = document.getElementById('attendance-content');
    if (attendanceContent) {
        const loadingElement = document.createElement('div');
        loadingElement.className = 'loading-indicator';
        loadingElement.innerHTML = '<span class="material-icons rotating">sync</span> กำลังโหลดข้อมูล...';

        // สไตล์สำหรับ loading indicator
        loadingElement.style.textAlign = 'center';
        loadingElement.style.padding = '20px';
        loadingElement.style.color = '#666';

        // สร้างเอฟเฟกต์หมุน
        const rotatingStyle = document.createElement('style');
        rotatingStyle.textContent = `
            @keyframes rotating {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .rotating {
                animation: rotating 2s linear infinite;
                display: inline-block;
            }
        `;
        document.head.appendChild(rotatingStyle);

        // เพิ่ม loading indicator ลงในหน้า
        attendanceContent.innerHTML = '';
        attendanceContent.appendChild(loadingElement);

        // จำลองการโหลดข้อมูล
        setTimeout(() => {
            attendanceContent.removeChild(loadingElement);
            // ใส่ข้อมูลที่โหลดเสร็จแล้ว
            attendanceContent.innerHTML = '<div class="success-message"><span class="material-icons">check_circle</span> โหลดข้อมูลการเข้าแถวเรียบร้อยแล้ว</div>';

            // สไตล์สำหรับ success message
            const successStyle = document.createElement('style');
            successStyle.textContent = `
                .success-message {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                    background-color: var(--success-color-light);
                    border-radius: 10px;
                    color: var(--success-color);
                    font-weight: 500;
                }
                .success-message .material-icons {
                    margin-right: 10px;
                }
            `;
            document.head.appendChild(successStyle);
        }, 1500);
    }
}

/**
 * โหลดข้อมูลข่าวสาร
 */
function loadNewsData() {
    // โค้ดสำหรับโหลดข้อมูลข่าวสารจาก API หรือ AJAX
    console.log("กำลังโหลดข้อมูลข่าวสาร");

    // ตัวอย่างการแสดง loading indicator
    const newsContent = document.getElementById('news-content');
    if (newsContent) {
        const loadingElement = document.createElement('div');
        loadingElement.className = 'loading-indicator';
        loadingElement.innerHTML = '<span class="material-icons rotating">sync</span> กำลังโหลดข้อมูล...';

        // สไตล์สำหรับ loading indicator
        loadingElement.style.textAlign = 'center';
        loadingElement.style.padding = '20px';
        loadingElement.style.color = '#666';

        // เพิ่ม loading indicator ลงในหน้า
        newsContent.innerHTML = '';
        newsContent.appendChild(loadingElement);

        // จำลองการโหลดข้อมูล
        setTimeout(() => {
            newsContent.removeChild(loadingElement);
            // ใส่ข้อมูลที่โหลดเสร็จแล้ว
            newsContent.innerHTML = '<div class="success-message"><span class="material-icons">check_circle</span> โหลดข้อมูลข่าวสารเรียบร้อยแล้ว</div>';
        }, 1500);
    }
}

/**
 * โทรหาครูประจำชั้น
 * @param {string} phone - เบอร์โทรศัพท์ของครูที่ปรึกษา
 */
function callTeacher(phone) {
    if (!phone || phone === '-') {
        alert('ไม่พบข้อมูลเบอร์โทรศัพท์ของครูที่ปรึกษา');
        return;
    }
    window.location.href = `tel:${phone}`;
}

/**
 * ส่งข้อความหาครูประจำชั้น
 * @param {number} teacherId - รหัสครูที่ปรึกษา
 */
function messageTeacher(teacherId) {
    if (!teacherId || teacherId === '0') {
        alert('ไม่พบข้อมูลครูที่ปรึกษา');
        return;
    }
    window.location.href = `messages.php?teacher=${teacherId}`;
}