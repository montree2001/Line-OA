/**
 * parent-activities.js - ไฟล์ JavaScript สำหรับหน้ากิจกรรมของผู้ปกครอง SADD-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงาน
    initActivitiesPage();
});

/**
 * เริ่มต้นการทำงานในหน้ากิจกรรม
 */
function initActivitiesPage() {
    // ตั้งค่าตัวกรองอัตโนมัติ
    setupAutoFilter();
    
    // ตั้งค่าการแสดงรายละเอียดเพิ่มเติม
    setupExpandableDetails();
    
    // ตั้งค่าการอัปเดตเวลาแบบ Relative
    setupRelativeTime();
    
    // ตั้งค่าการตอบสนองบนอุปกรณ์สัมผัส
    setupTouchEvents();
}

/**
 * ตั้งค่าตัวกรองอัตโนมัติ
 */
function setupAutoFilter() {
    const filterSelects = document.querySelectorAll('.filter-select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            // ส่งฟอร์มเมื่อมีการเปลี่ยนแปลงตัวกรอง
            document.querySelector('.filter-form').submit();
        });
    });
    
    // เพิ่มปุ่มรีเซ็ตตัวกรอง
    const resetButton = document.querySelector('.reset-button');
    if (resetButton) {
        resetButton.addEventListener('click', function(e) {
            // เคลียร์ค่าตัวกรองทั้งหมด
            filterSelects.forEach(select => {
                if (select.id === 'student') {
                    select.value = '0';
                } else if (select.id === 'type') {
                    select.value = 'all';
                } else if (select.id === 'period') {
                    select.value = 'month';
                }
            });
            
            // ส่งฟอร์มหลังจากรีเซ็ตค่า
            document.querySelector('.filter-form').submit();
            e.preventDefault();
        });
    }
}

/**
 * ตั้งค่าการแสดงรายละเอียดเพิ่มเติม
 */
function setupExpandableDetails() {
    const activityItems = document.querySelectorAll('.activity-item');
    
    activityItems.forEach(item => {
        const details = item.querySelector('.activity-details');
        
        // ตรวจสอบว่ามีการสร้างปุ่มไปแล้วหรือไม่
        if (details && !item.querySelector('.toggle-details')) {
            // ปิดการแสดงรายละเอียดเริ่มต้น
            details.style.display = 'none';
            
            // สร้างปุ่มขยาย/ย่อรายละเอียด
            const toggleButton = document.createElement('button');
            toggleButton.className = 'toggle-details';
            toggleButton.innerHTML = '<span class="material-icons show-details-icon">expand_more</span>';
            toggleButton.setAttribute('aria-label', 'แสดงรายละเอียดเพิ่มเติม');
            toggleButton.setAttribute('data-expanded', 'false');
            
            // เพิ่มปุ่มหลังจากรายละเอียด
            details.parentNode.insertBefore(toggleButton, details.nextSibling);
            
            // เพิ่มเหตุการณ์เมื่อคลิกปุ่ม
            toggleButton.addEventListener('click', function() {
                const isExpanded = this.getAttribute('data-expanded') === 'true';
                
                if (isExpanded) {
                    // ปิดการแสดงรายละเอียด
                    details.style.display = 'none';
                    this.innerHTML = '<span class="material-icons show-details-icon">expand_more</span>';
                    this.setAttribute('data-expanded', 'false');
                    this.setAttribute('aria-label', 'แสดงรายละเอียดเพิ่มเติม');
                } else {
                    // เปิดการแสดงรายละเอียด
                    details.style.display = 'grid';
                    this.innerHTML = '<span class="material-icons hide-details-icon">expand_less</span>';
                    this.setAttribute('data-expanded', 'true');
                    this.setAttribute('aria-label', 'ซ่อนรายละเอียด');
                }
            });
        }
    });
}

/**
 * ตั้งค่าการอัปเดตเวลาแบบ Relative
 */
function setupRelativeTime() {
    // อัปเดตเวลาทุก 60 วินาที
    setInterval(updateRelativeTimes, 60000);
    
    // อัปเดตเวลาตั้งแต่เริ่มต้น
    updateRelativeTimes();
}

/**
 * อัปเดตเวลาแบบ Relative
 */
function updateRelativeTimes() {
    const timeElements = document.querySelectorAll('.activity-time[data-timestamp]');
    
    timeElements.forEach(el => {
        const timestamp = parseInt(el.getAttribute('data-timestamp'));
        if (timestamp) {
            el.textContent = getRelativeTimeString(timestamp);
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
        const day = date.getDate();
        const month = date.toLocaleString('th-TH', { month: 'short' });
        const year = date.getFullYear() + 543; // แปลงเป็นปี พ.ศ.
        
        return `${day} ${month} ${year}`;
    }
}

/**
 * ตั้งค่าการตอบสนองบนอุปกรณ์สัมผัส
 */
function setupTouchEvents() {
    // การแตะสองครั้งเพื่อดูรายละเอียดนักเรียน
    const activityItems = document.querySelectorAll('.activity-item');
    let lastTap = 0;
    
    activityItems.forEach(item => {
        item.addEventListener('touchend', function(e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            
            if (tapLength < 500 && tapLength > 0) {
                // Double tap - ไปที่หน้ารายละเอียดนักเรียน
                const studentId = this.querySelector('.activity-action a').getAttribute('href').split('=')[1];
                window.location.href = `students.php?id=${studentId}`;
                e.preventDefault();
            }
            
            lastTap = currentTime;
        });
    });
    
    // การแตะนานเพื่อแสดงรายละเอียดเพิ่มเติม
    activityItems.forEach(item => {
        let pressTimer;
        
        item.addEventListener('touchstart', function(e) {
            if (e.target.closest('.toggle-details') || e.target.closest('.view-student-button')) {
                return; // ไม่ทำงานถ้าแตะที่ปุ่ม
            }
            
            pressTimer = setTimeout(() => {
                const detailsToggle = this.querySelector('.toggle-details');
                if (detailsToggle) {
                    const isExpanded = detailsToggle.getAttribute('data-expanded') === 'true';
                    
                    if (!isExpanded) {
                        // เปิดการแสดงรายละเอียด
                        const details = this.querySelector('.activity-details');
                        details.style.display = 'grid';
                        detailsToggle.innerHTML = '<span class="material-icons hide-details-icon">expand_less</span>';
                        detailsToggle.setAttribute('data-expanded', 'true');
                        
                        // สร้างการสั่น (ถ้ารองรับ)
                        if ('vibrate' in navigator) {
                            navigator.vibrate(50);
                        }
                    }
                }
            }, 500);
        });
        
        item.addEventListener('touchend touchcancel', function() {
            clearTimeout(pressTimer);
        });
        
        item.addEventListener('touchmove', function() {
            clearTimeout(pressTimer);
        });
    });
}

/**
 * เปลี่ยนหน้าการแบ่งหน้า
 * @param {number} page - หน้าที่ต้องการเปลี่ยน
 */
function changePage(page) {
    // ดึงค่าตัวกรองปัจจุบัน
    const student = document.getElementById('student').value;
    const type = document.getElementById('type').value;
    const period = document.getElementById('period').value;
    
    // สร้าง URL ใหม่
    const url = `activities.php?student=${student}&type=${type}&period=${period}&page=${page}`;
    
    // ไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ฟังก์ชันตรวจสอบว่าเป็นอุปกรณ์สัมผัสหรือไม่
 * @returns {boolean} - true ถ้าเป็นอุปกรณ์สัมผัส
 */
function isTouchDevice() {
    return (('ontouchstart' in window) ||
        (navigator.maxTouchPoints > 0) ||
        (navigator.msMaxTouchPoints > 0));
}

/**
 * แสดงข้อมูลการสรุปสำหรับนักเรียนที่เลือก
 * @param {number} studentId - รหัสนักเรียน
 */
function showStudentSummary(studentId) {
    // AJAX เพื่อโหลดข้อมูลสรุปสำหรับนักเรียนที่เลือก
    // แสดงข้อมูลในหน้าเว็บ
    
    // ในตัวอย่างนี้จะเป็นเพียงการรีโหลดหน้าด้วยพารามิเตอร์ที่เปลี่ยนไป
    window.location.href = `activities.php?student=${studentId}&type=all&period=month`;
}

/**
 * ฟังก์ชันสำหรับส่งออกข้อมูลเป็น CSV
 */
function exportToCSV() {
    // ดึงค่าตัวกรองปัจจุบัน
    const student = document.getElementById('student').value;
    const type = document.getElementById('type').value;
    const period = document.getElementById('period').value;
    
    // สร้าง URL สำหรับส่งออก
    const exportUrl = `export_activities.php?student=${student}&type=${type}&period=${period}&format=csv`;
    
    // เปิด URL ใหม่
    window.open(exportUrl, '_blank');
}

/**
 * ฟังก์ชันสำหรับส่งออกข้อมูลเป็น PDF
 */
function exportToPDF() {
    // ดึงค่าตัวกรองปัจจุบัน
    const student = document.getElementById('student').value;
    const type = document.getElementById('type').value;
    const period = document.getElementById('period').value;
    
    // สร้าง URL สำหรับส่งออก
    const exportUrl = `export_activities.php?student=${student}&type=${type}&period=${period}&format=pdf`;
    
    // เปิด URL ใหม่
    window.open(exportUrl, '_blank');
}