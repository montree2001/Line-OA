/**
 * parent-students.js - ไฟล์ JavaScript ที่ปรับปรุงสำหรับหน้านักเรียนในความดูแลของผู้ปกครอง SADD-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงาน
    initStudentsPage();

    // ตรวจสอบหน้า URL และแสดงเนื้อหาที่เหมาะสม
    checkCurrentView();
});

/**
 * เริ่มต้นการทำงานในหน้านักเรียน
 */
function initStudentsPage() {
    // ตั้งค่าปุ่มกรอง
    setupFilterDropdown();

    // ตั้งค่าการค้นหา
    setupSearch();

    // ตั้งค่าการคลิกที่การ์ดนักเรียน
    setupStudentCards();

    // ตั้งค่าการลบนักเรียน
    setupRemoveStudent();

    // ตั้งค่าการเพิ่มนักเรียน
    setupAddStudent();

    // ตั้งค่าประสบการณ์ผู้ใช้บนอุปกรณ์แท็บเล็ตและมือถือ
    setupMobileExperience();
}

/**
 * ตรวจสอบ URL ปัจจุบันและแสดงเนื้อหาที่เหมาะสม
 */
function checkCurrentView() {
    // ตรวจสอบว่ามีการระบุ ID นักเรียนหรือไม่
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('id');

    if (studentId) {
        console.log(`แสดงข้อมูลนักเรียน ID: ${studentId}`);
        // ตั้งค่าฟังก์ชันสำหรับหน้ารายละเอียดนักเรียน
        setupStudentDetailPage();
    } else {
        console.log('แสดงรายการนักเรียนทั้งหมด');
    }
}

/**
 * ตั้งค่าปุ่มกรอง
 */
function setupFilterDropdown() {
    const filterToggle = document.getElementById('filter-toggle');
    if (!filterToggle) return;

    const filterMenu = document.getElementById('filter-menu');

    // เปิด/ปิดเมนูกรอง
    filterToggle.addEventListener('click', function(e) {
        e.stopPropagation(); // หยุดการลุกลามของเหตุการณ์
        filterMenu.classList.toggle('active');
    });

    // ปิดเมนูกรองเมื่อคลิกที่อื่น
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#filter-toggle') && !event.target.closest('#filter-menu')) {
            filterMenu.classList.remove('active');
        }
    });

    // ตั้งค่าตัวกรอง
    const filterItems = document.querySelectorAll('.filter-item input');
    filterItems.forEach(item => {
        item.addEventListener('change', function() {
            applyFilters();
        });
    });
}

/**
 * ตั้งค่าการค้นหา
 */
function setupSearch() {
    const searchInput = document.getElementById('search-student');
    if (!searchInput) return;

    // ใช้ debounce เพื่อลดการค้นหาที่บ่อยเกินไป
    let debounceTimer;
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const searchText = this.value.toLowerCase().trim();

        debounceTimer = setTimeout(() => {
            filterStudentsBySearch(searchText);
        }, 300); // รอ 300 มิลลิวินาที
    });

    // ล้างการค้นหาเมื่อคลิกที่ไอคอน clear
    const clearSearchBtn = document.createElement('span');
    clearSearchBtn.className = 'material-icons clear-search';
    clearSearchBtn.textContent = 'close';
    clearSearchBtn.style.display = 'none';
    clearSearchBtn.style.cursor = 'pointer';
    clearSearchBtn.style.color = '#999';

    searchInput.parentNode.appendChild(clearSearchBtn);

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        this.style.display = 'none';
        showAllStudents();
    });

    searchInput.addEventListener('input', function() {
        clearSearchBtn.style.display = this.value ? 'inline-block' : 'none';
    });
}

/**
 * ตั้งค่าการคลิกที่การ์ดนักเรียน
 */
function setupStudentCards() {
    const studentCards = document.querySelectorAll('.student-card');

    studentCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // ถ้าคลิกที่ปุ่มดูรายละเอียดหรือปุ่มลบ ให้ดำเนินการตามปกติ
            if (e.target.closest('.view-details-button') ||
                e.target.closest('.remove-student-button') ||
                e.target.closest('.remove-student-form')) {
                return;
            }

            // ถ้าคลิกที่ส่วนอื่นของการ์ด ให้ไปที่หน้ารายละเอียด
            const studentId = this.getAttribute('data-id');
            window.location.href = `students.php?id=${studentId}`;
        });
    });
}

/**
 * ตั้งค่าการลบนักเรียน
 */
function setupRemoveStudent() {
    const removeButtons = document.querySelectorAll('.remove-student-button');

    removeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // ยืนยันการลบนักเรียน
            if (!confirm('คุณต้องการลบนักเรียนออกจากความดูแลหรือไม่?')) {
                e.preventDefault();
            }
        });
    });
}

/**
 * ตั้งค่าการเพิ่มนักเรียน
 */
function setupAddStudent() {
    const addButtons = document.querySelectorAll('.add-student-button');

    addButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // ในกรณีที่กดปุ่มเพิ่มที่ไม่ใช่ฟอร์ม ให้เลื่อนไปที่ส่วนค้นหานักเรียน
            if (!this.closest('form')) {
                e.preventDefault();
                const addStudentSection = document.getElementById('add-student-section');
                if (addStudentSection) {
                    // เลื่อนไปที่ส่วนค้นหานักเรียนอย่างนุ่มนวล
                    addStudentSection.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });

                    // โฟกัสที่ช่องค้นหา
                    setTimeout(() => {
                        const searchInput = document.querySelector('.search-input-large input');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }, 500);
                }
            }
        });
    });
}

/**
 * ตั้งค่าประสบการณ์ผู้ใช้บนอุปกรณ์แท็บเล็ตและมือถือ
 */
function setupMobileExperience() {
    // เพิ่มการสนับสนุนการแตะสองครั้งสำหรับอุปกรณ์สัมผัส
    const studentCards = document.querySelectorAll('.student-card');
    let lastTap = 0;

    studentCards.forEach(card => {
        card.addEventListener('touchend', function(e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;

            if (tapLength < 500 && tapLength > 0) {
                // Double tap - ไปที่หน้ารายละเอียดนักเรียน
                const studentId = this.getAttribute('data-id');
                window.location.href = `students.php?id=${studentId}`;
                e.preventDefault();
            }

            lastTap = currentTime;
        });
    });

    // เพิ่มการสนับสนุนการสัมผัสนานสำหรับสถานะการเข้าแถว
    const studentStatuses = document.querySelectorAll('.student-status');

    studentStatuses.forEach(status => {
        let pressTimer;

        status.addEventListener('touchstart', function(e) {
            pressTimer = setTimeout(() => {
                // แสดงรายละเอียดเพิ่มเติมเมื่อกดค้าง
                this.classList.add('expanded');
                // สร้างการสั่น (ถ้ารองรับ)
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
            }, 500);
            e.preventDefault();
        });

        status.addEventListener('touchend', function() {
            clearTimeout(pressTimer);
            // ดีเลย์การซ่อนเพื่อให้ผู้ใช้ได้อ่าน
            setTimeout(() => {
                this.classList.remove('expanded');
            }, 2000);
        });

        status.addEventListener('touchmove', function() {
            clearTimeout(pressTimer);
        });
    });

    // เพิ่มให้ฟอร์มค้นหานักเรียนเพื่อเพิ่มรองรับการกดปุ่ม Enter
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchForm.submit();
            }
        });
    }
}

/**
 * ตั้งค่าฟังก์ชันสำหรับหน้ารายละเอียดนักเรียน
 */
function setupStudentDetailPage() {
    // ตั้งค่าแท็บและการกรองประวัติการเข้าแถว
    const filterButtons = document.querySelectorAll('.filter-button');

    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // ลบคลาส active จากทุกปุ่ม
                filterButtons.forEach(btn => btn.classList.remove('active'));

                // เพิ่มคลาส active ให้ปุ่มที่ถูกคลิก
                this.classList.add('active');

                // กรองข้อมูลตามประเภทที่เลือก
                const filterType = this.getAttribute('data-filter');
                if (filterType) {
                    filterAttendance(filterType);
                }
            });
        });

        // เลือกตัวกรอง "ทั้งหมด" เป็นค่าเริ่มต้น
        const allFilterButton = document.querySelector('.filter-button[data-filter="all"]');
        if (allFilterButton) {
            allFilterButton.classList.add('active');
        }
    }

    // ตั้งค่าปุ่มกลับ
    const backButton = document.querySelector('.back-button');
    if (backButton) {
        backButton.addEventListener('click', function(e) {
            // ตรวจสอบว่ามี history หรือไม่
            if (window.history.length > 1) {
                e.preventDefault();
                window.history.back();
            }
        });
    }

    // ตั้งค่าปุ่มติดต่อครู
    const callTeacherBtn = document.querySelector('.call-teacher-btn');
    if (callTeacherBtn) {
        callTeacherBtn.addEventListener('click', function() {
            const phone = this.getAttribute('data-phone');
            if (phone) {
                window.location.href = `tel:${phone}`;
            }
        });
    }

    const messageTeacherBtn = document.querySelector('.message-teacher-btn');
    if (messageTeacherBtn) {
        messageTeacherBtn.addEventListener('click', function() {
            const teacherId = this.getAttribute('data-teacher-id');
            if (teacherId) {
                window.location.href = `messages.php?teacher=${teacherId}`;
            }
        });
    }
}

/**
 * กรองนักเรียนตามเงื่อนไขที่เลือก
 */
function applyFilters() {
    // ตรวจสอบว่ามีการเลือกตัวกรองใดบ้าง
    const filterAll = document.getElementById('filter-all');
    const filterHighSchool = document.getElementById('filter-high-school');
    const filterPrimarySchool = document.getElementById('filter-primary-school');

    // ตรวจสอบว่าตัวกรองทั้งหมดถูกเลือกหรือไม่มีการเลือกเลย
    if ((filterAll && filterAll.checked) ||
        (!filterHighSchool || !filterHighSchool.checked) &&
        (!filterPrimarySchool || !filterPrimarySchool.checked)) {
        // แสดงนักเรียนทั้งหมด
        showAllStudents();
        return;
    }

    // ซ่อนนักเรียนทั้งหมดก่อน
    hideAllStudents();

    // แสดงนักเรียนตามเงื่อนไขที่เลือก
    const studentCards = document.querySelectorAll('.student-card');

    studentCards.forEach(card => {
        const studentClass = card.querySelector('.student-class').textContent.toLowerCase();

        // กรองนักเรียนระดับ ปวส.
        if (filterHighSchool && filterHighSchool.checked && studentClass.includes('ปวส.')) {
            card.style.display = 'block';
        }

        // กรองนักเรียนระดับ ปวช.
        if (filterPrimarySchool && filterPrimarySchool.checked && studentClass.includes('ปวช.')) {
            card.style.display = 'block';
        }
    });

    // แสดงข้อความเมื่อไม่พบนักเรียนตามเงื่อนไข
    const visibleCards = document.querySelectorAll('.student-card[style="display: block;"]');
    const noFilterResults = document.getElementById('no-filter-results');

    if (visibleCards.length === 0 && noFilterResults) {
        noFilterResults.style.display = 'block';
    } else if (noFilterResults) {
        noFilterResults.style.display = 'none';
    }
}

/**
 * กรองนักเรียนตามข้อความค้นหา
 * @param {string} searchText - ข้อความที่ใช้ในการค้นหา
 */
function filterStudentsBySearch(searchText) {
    const studentCards = document.querySelectorAll('.student-card');
    let resultsFound = false;

    if (!searchText) {
        // ถ้าไม่มีข้อความค้นหา ให้แสดงนักเรียนทั้งหมด
        showAllStudents();
        return;
    }

    studentCards.forEach(card => {
        const studentName = card.querySelector('.student-name').textContent.toLowerCase();
        const studentClass = card.querySelector('.student-class').textContent.toLowerCase();
        const studentId = card.querySelector('.student-id').textContent.toLowerCase();

        if (studentName.includes(searchText) ||
            studentClass.includes(searchText) ||
            studentId.includes(searchText)) {
            card.style.display = 'block';
            resultsFound = true;
        } else {
            card.style.display = 'none';
        }
    });

    // แสดงข้อความเมื่อไม่พบนักเรียนตามการค้นหา
    const noSearchResults = document.getElementById('no-search-results');

    if (!resultsFound && noSearchResults) {
        noSearchResults.style.display = 'block';
    } else if (noSearchResults) {
        noSearchResults.style.display = 'none';
    }
}

/**
 * แสดงนักเรียนทั้งหมด
 */
function showAllStudents() {
    const studentCards = document.querySelectorAll('.student-card');
    studentCards.forEach(card => {
        card.style.display = 'block';
    });

    // ซ่อนข้อความไม่พบผลลัพธ์
    const noResults = document.querySelectorAll('.no-results-message');
    noResults.forEach(el => {
        if (el) el.style.display = 'none';
    });
}

/**
 * ซ่อนนักเรียนทั้งหมด
 */
function hideAllStudents() {
    const studentCards = document.querySelectorAll('.student-card');
    studentCards.forEach(card => {
        card.style.display = 'none';
    });
}

/**
 * กรองประวัติการเข้าแถว
 * @param {string} filterType - ประเภทการกรอง
 */
function filterAttendance(filterType) {
    // กรองรายการเข้าแถว
    const attendanceItems = document.querySelectorAll('.attendance-item');

    if (filterType === 'all') {
        // แสดงทั้งหมด
        attendanceItems.forEach(item => {
            item.style.display = 'flex';
        });
    } else if (filterType === 'month') {
        // กรองตามเดือนปัจจุบัน
        const currentMonth = new Date().getMonth() + 1; // 1-12
        attendanceItems.forEach(item => {
            const monthText = item.querySelector('.date-month') ? .textContent;
            // ตรวจสอบว่าเป็นเดือนปัจจุบัน
            if (monthText && getMonthNumberFromThaiAbbr(monthText) === currentMonth) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    } else if (filterType === 'week') {
        // กรองตามสัปดาห์ปัจจุบัน
        const today = new Date();
        const startOfWeek = new Date(today);
        startOfWeek.setDate(today.getDate() - today.getDay()); // วันอาทิตย์ของสัปดาห์นี้
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6); // วันเสาร์ของสัปดาห์นี้

        attendanceItems.forEach(item => {
            const dateElement = item.querySelector('.attendance-date');
            if (dateElement) {
                const day = parseInt(dateElement.querySelector('.date-day').textContent);
                const monthText = dateElement.querySelector('.date-month').textContent;
                const month = getMonthNumberFromThaiAbbr(monthText) - 1; // 0-11 for JavaScript Date
                const year = new Date().getFullYear();

                const itemDate = new Date(year, month, day);

                if (itemDate >= startOfWeek && itemDate <= endOfWeek) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            } else {
                item.style.display = 'none';
            }
        });
    } else if (filterType === 'present') {
        // กรองเฉพาะมาเรียน
        attendanceItems.forEach(item => {
            const statusElement = item.querySelector('.attendance-status-text');
            if (statusElement && (statusElement.classList.contains('present') || statusElement.classList.contains('late'))) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    } else if (filterType === 'absent') {
        // กรองเฉพาะขาดเรียน
        attendanceItems.forEach(item => {
            const statusElement = item.querySelector('.attendance-status-text');
            if (statusElement && statusElement.classList.contains('absent')) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // ตรวจสอบว่ามีข้อมูลแสดงหรือไม่
    const visibleItems = Array.from(attendanceItems).filter(item => item.style.display !== 'none');
    const noFilterMessage = document.getElementById('no-attendance-message');

    if (visibleItems.length === 0 && noFilterMessage) {
        noFilterMessage.style.display = 'block';
    } else if (noFilterMessage) {
        noFilterMessage.style.display = 'none';
    }
}

/**
 * แปลงชื่อย่อเดือนภาษาไทยเป็นเลขเดือน
 * @param {string} thaiMonth - ชื่อย่อเดือนภาษาไทย (เช่น "ม.ค.")
 * @returns {number} - เลขเดือน (1-12)
 */
function getMonthNumberFromThaiAbbr(thaiMonth) {
    const monthMap = {
        'ม.ค.': 1,
        'ก.พ.': 2,
        'มี.ค.': 3,
        'เม.ย.': 4,
        'พ.ค.': 5,
        'มิ.ย.': 6,
        'ก.ค.': 7,
        'ส.ค.': 8,
        'ก.ย.': 9,
        'ต.ค.': 10,
        'พ.ย.': 11,
        'ธ.ค.': 12
    };

    // ดึงเฉพาะชื่อเดือน (เช่น จาก "ม.ค." หรือ "ม.ค. 2567")
    const monthAbbr = thaiMonth.split(' ')[0].trim();
    return monthMap[monthAbbr] || 0;
}

/**
 * โทรหาครูที่ปรึกษา
 * @param {string} phone - เบอร์โทรศัพท์ของครูที่ปรึกษา
 */
function callTeacher(phone) {
    window.location.href = `tel:${phone}`;
}

/**
 * ส่งข้อความหาครูที่ปรึกษา
 * @param {number} teacherId - รหัสครูที่ปรึกษา
 */
function messageTeacher(teacherId) {
    window.location.href = `messages.php?teacher=${teacherId}`;
}