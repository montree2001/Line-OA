/**
 * parent-students.js - ไฟล์ JavaScript สำหรับหน้านักเรียนในความดูแลของผู้ปกครอง SADD-Prasat
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
        // ในงานจริงอาจมีการโหลดข้อมูลเพิ่มเติมจาก API
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
    filterToggle.addEventListener('click', function() {
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

    searchInput.addEventListener('input', function() {
        const searchText = this.value.toLowerCase().trim();
        filterStudentsBySearch(searchText);
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
        button.addEventListener('click', function() {
            // ในกรณีที่กดปุ่มเพิ่มที่ไม่ใช่ฟอร์ม ให้เลื่อนไปที่ส่วนค้นหานักเรียน
            if (!this.closest('form')) {
                const addStudentSection = document.getElementById('add-student-section');
                if (addStudentSection) {
                    addStudentSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
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
}

/**
 * กรองนักเรียนตามข้อความค้นหา
 * @param {string} searchText - ข้อความที่ใช้ในการค้นหา
 */
function filterStudentsBySearch(searchText) {
    const studentCards = document.querySelectorAll('.student-card');

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
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * แสดงนักเรียนทั้งหมด
 */
function showAllStudents() {
    const studentCards = document.querySelectorAll('.student-card');
    studentCards.forEach(card => {
        card.style.display = 'block';
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
    // ตั้งค่าปุ่มกรอง
    const filterButtons = document.querySelectorAll('.filter-button');
    filterButtons.forEach(button => button.classList.remove('active'));

    const clickedButton = Array.from(filterButtons).find(button =>
        button.textContent.trim().toLowerCase().includes(filterType) ||
        (filterType === 'all' && button.textContent.trim() === 'ทั้งหมด') ||
        (filterType === 'month' && button.textContent.trim() === 'เดือนนี้') ||
        (filterType === 'week' && button.textContent.trim() === 'สัปดาห์นี้') ||
        (filterType === 'present' && button.textContent.trim() === 'มาเรียน') ||
        (filterType === 'absent' && button.textContent.trim() === 'ขาดเรียน')
    );

    if (clickedButton) {
        clickedButton.classList.add('active');
    }

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
            // สำหรับตัวอย่าง ตรวจสอบแค่ว่าเป็นเดือนปัจจุบัน (ในการใช้งานจริงควรมีการตรวจสอบที่ดีกว่านี้)
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

        attendanceItems.forEach((item, index) => {
            // สำหรับตัวอย่าง แสดง 3 รายการล่าสุด
            if (index < 3) {
                item.style.display = 'flex';
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