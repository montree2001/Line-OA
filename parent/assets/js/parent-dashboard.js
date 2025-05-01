/**
 * parent-dashboard.js - ไฟล์ JavaScript สำหรับหน้าหลักผู้ปกครอง SADD-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {


    // ตรวจสอบการแจ้งเตือนใหม่ (ลบโค้ดส่วนการแสดง popup-notification)
    // checkNewNotifications(); (ลบการเรียกใช้ฟังก์ชันนี้)

    // ตั้งค่าการทำงานของแท็บ
    setupTabs();

    // ตั้งค่าการทำงานของ Student Card
    setupStudentCards();
});



/**
 * สลับแท็บ
 * @param {string} tabName - ชื่อแท็บที่ต้องการเปิด
 */
function switchTab(tabName) {
    const tabs = document.querySelectorAll('.tab-button');
    tabs.forEach(tab => tab.classList.remove('active'));

    // ตั้งค่าแท็บที่เลือก
    if (tabName === 'overview') {
        tabs[0].classList.add('active');
        // ในการใช้งานจริงควรแสดงเนื้อหาแท็บ overview
        console.log("สลับไปที่แท็บ overview");
    } else if (tabName === 'attendance') {
        tabs[1].classList.add('active');
        // ในการใช้งานจริงควรแสดงเนื้อหาแท็บ attendance
        console.log("สลับไปที่แท็บ attendance");
        loadAttendanceData();
    } else if (tabName === 'news') {
        tabs[2].classList.add('active');
        // ในการใช้งานจริงควรแสดงเนื้อหาแท็บ news
        console.log("สลับไปที่แท็บ news");
        loadNewsData();
    }
}

/**
 * ตั้งค่าการทำงานของแท็บ
 */
function setupTabs() {
    const tabs = document.querySelectorAll('.tab-button');

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
}

/**
 * ตั้งค่าการทำงานของ Student Card
 */
function setupStudentCards() {
    const studentCards = document.querySelectorAll('.student-card');

    studentCards.forEach(card => {
        card.addEventListener('click', function() {
            // ในงานจริงควรมีการนำไปยังหน้ารายละเอียดนักเรียน
            const studentName = this.querySelector('.student-name').textContent;
            console.log(`คลิกที่การ์ดนักเรียน: ${studentName}`);
            window.location.href = `student_detail.php?name=${encodeURIComponent(studentName)}`;
        });
    });
}

/**
 * โหลดข้อมูลการเข้าแถว
 */
function loadAttendanceData() {
    // ในงานจริงควรมีการดึงข้อมูลจาก API
    console.log("กำลังโหลดข้อมูลการเข้าแถว");

    // จำลองการโหลดข้อมูล
    setTimeout(() => {
        console.log("โหลดข้อมูลการเข้าแถวเสร็จสิ้น");
    }, 500);
}

/**
 * โหลดข้อมูลข่าวสาร
 */
function loadNewsData() {
    // ในงานจริงควรมีการดึงข้อมูลจาก API
    console.log("กำลังโหลดข้อมูลข่าวสาร");

    // จำลองการโหลดข้อมูล
    setTimeout(() => {
        console.log("โหลดข้อมูลข่าวสารเสร็จสิ้น");
    }, 500);
}

// ลบฟังก์ชัน checkNewNotifications() และ showNotification()

/**
 * โทรหาครูประจำชั้น
 */
function callTeacher() {
    // ในงานจริงควรมีการเชื่อมต่อกับระบบโทรศัพท์หรือ LINE
    console.log("กำลังโทรหาครูประจำชั้น");

    alert('กำลังโทรหาครูประจำชั้น: อาจารย์ใจดี มากเมตตา');
}

/**
 * ส่งข้อความหาครูประจำชั้น
 */
function messageTeacher() {
    // ในงานจริงควรมีการเชื่อมต่อกับ LINE หรือระบบข้อความ
    console.log("กำลังส่งข้อความหาครูประจำชั้น");

    // นำไปยังหน้าสนทนากับครู
    window.location.href = 'messages.php?teacher=อาจารย์ใจดี%20มากเมตตา';
}

/**
 * แสดงกราฟการเข้าแถว (ตัวอย่าง - ถ้ามีการแสดงกราฟในหน้า dashboard)
 */
function renderAttendanceChart() {
    // ในงานจริงควรมีการใช้ไลบรารี Chart.js หรือ Google Charts
    console.log("กำลังแสดงกราฟการเข้าแถว");

    // ตัวอย่างข้อมูลสำหรับกราฟ
    const data = {
        labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
        datasets: [{
            label: 'อัตราการเข้าแถว (%)',
            data: [98, 95, 97, 96, 99, 100],
            backgroundColor: '#8e24aa'
        }]
    };

    // ในงานจริงควรมีการเรียกใช้ไลบรารี Chart.js หรือ Google Charts
    console.log("ข้อมูลกราฟ:", data);
}