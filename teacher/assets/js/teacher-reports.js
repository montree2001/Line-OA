/**
 * teacher-reports.js - สคริปต์เฉพาะสำหรับหน้ารายงานของระบบ Teacher-Prasat
 */

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้ารายงาน
    initReportsPage();
});

/**
 * เริ่มต้นการทำงานของหน้ารายงาน
 */
function initReportsPage() {
    // เริ่มต้นแท็บเมนู
    initTabMenu();
    
    // เพิ่ม Event Listener สำหรับค้นหานักเรียน
    const searchInput = document.getElementById('student-search');
    if (searchInput) {
        searchInput.addEventListener('input', searchStudents);
    }
}

/**
 * เริ่มต้นแท็บเมนู
 */
function initTabMenu() {
    // ตั้งค่าเริ่มต้นให้แสดงแท็บ table
    switchTab('table');
}

/**
 * สลับแท็บที่แสดง
 * @param {string} tabName - ชื่อแท็บ (table/graph/calendar)
 */
function switchTab(tabName) {
    // ซ่อนทุกแท็บ
    const tableView = document.getElementById('table-view');
    const graphView = document.getElementById('graph-view');
    const calendarView = document.getElementById('calendar-view');
    
    if (tableView) tableView.style.display = 'none';
    if (graphView) graphView.style.display = 'none';
    if (calendarView) calendarView.style.display = 'none';
    
    // แสดงแท็บที่เลือก
    if (tabName === 'table' && tableView) {
        tableView.style.display = 'block';
    } else if (tabName === 'graph' && graphView) {
        graphView.style.display = 'block';
    } else if (tabName === 'calendar' && calendarView) {
        calendarView.style.display = 'block';
    }
    
    // อัพเดทปุ่มแท็บ
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('active');
    });
    
    // เพิ่มคลาส active ให้กับปุ่มที่เลือก
    const selectedButton = document.querySelector(`.tab-button:nth-child(${
        tabName === 'table' ? 1 : tabName === 'graph' ? 2 : 3
    })`);
    
    if (selectedButton) {
        selectedButton.classList.add('active');
    }
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ดึงเดือนปัจจุบัน
    const monthSelect = document.getElementById('month-select');
    const currentMonth = monthSelect ? monthSelect.value : '';
    
    // สร้าง URL สำหรับการนำทาง
    let url = 'reports.php?class_id=' + classId;
    
    // เพิ่มพารามิเตอร์เดือนถ้ามี
    if (currentMonth) {
        url += '&month=' + currentMonth;
    }
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * เปลี่ยนเดือนที่แสดง
 */
function changeMonth() {
    const monthSelect = document.getElementById('month-select');
    if (!monthSelect) return;
    
    // ดึง class_id จาก URL หรือใช้ค่าเริ่มต้น
    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่พร้อมพารามิเตอร์
    const url = 'reports.php?class_id=' + classId + '&month=' + monthSelect.value;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ไปยังเดือนก่อนหน้า
 */
function prevMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear();
    
    // คำนวณเดือนก่อนหน้า
    month--;
    if (month < 1) {
        month = 12;
        year--;
    }
    
    // ดึง class_id
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่
    const url = 'reports.php?class_id=' + classId + '&month=' + month + '&year=' + year;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ไปยังเดือนถัดไป
 */
function nextMonth() {
    const urlParams = new URLSearchParams(window.location.search);
    let month = parseInt(urlParams.get('month')) || new Date().getMonth() + 1;
    let year = parseInt(urlParams.get('year')) || new Date().getFullYear();
    
    // คำนวณเดือนถัดไป
    month++;
    if (month > 12) {
        month = 1;
        year++;
    }
    
    // ดึง class_id
    const classId = urlParams.get('class_id') || '1';
    
    // สร้าง URL ใหม่
    const url = 'reports.php?class_id=' + classId + '&month=' + month + '&year=' + year;
    
    // นำทางไปยัง URL ใหม่
    window.location.href = url;
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('student-search');
    if (!searchInput) return;
    
    const searchText = searchInput.value.toLowerCase();
    const studentTable = document.querySelector('.student-table');
    if (!studentTable) return;
    
    const rows = studentTable.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const name = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
        if (name.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

/**
 * ดูรายละเอียดนักเรียน
 * @param {number} studentId - ID ของนักเรียน
 */
function viewStudentDetail(studentId) {
    if (!studentId) {
        console.error('ไม่ได้ระบุรหัสนักเรียน');
        return;
    }
    
    // แสดง Modal
    const modal = document.getElementById('student-detail-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // แสดงการโหลดข้อมูล
    const detailContent = document.getElementById('student-detail-content');
    if (detailContent) {
        detailContent.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }
    
    // ดึงข้อมูลประวัติการเข้าแถวจาก API
    fetchStudentAttendanceHistory(studentId);
}

/**
 * ดึงข้อมูลประวัติการเข้าแถวของนักเรียน
 * @param {number} studentId - ID ของนักเรียน
 */
function fetchStudentAttendanceHistory(studentId) {
    // สร้าง URL สำหรับดึงข้อมูล
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get('month') || '';
    const year = urlParams.get('year') || '';
    
    let apiUrl = `api/student_attendance_history.php?student_id=${studentId}`;
    if (month) apiUrl += `&month=${month}`;
    if (year) apiUrl += `&year=${year}`;
    
    // ทำการ fetch ข้อมูล
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // อัพเดท Modal ด้วยข้อมูลที่ได้
                updateStudentDetailModal(data);
            } else {
                // แสดงข้อความแจ้งเตือนในกรณีที่มีข้อผิดพลาด
                const detailContent = document.getElementById('student-detail-content');
                if (detailContent) {
                    detailContent.innerHTML = `<div class="error">${data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'}</div>`;
                }
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
            const detailContent = document.getElementById('student-detail-content');
            if (detailContent) {
                detailContent.innerHTML = `<div class="error">เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}</div>`;
            }
        });
}

/**
 * อัพเดท Modal รายละเอียดนักเรียนด้วยข้อมูลที่ได้จาก API
 * @param {Object} data - ข้อมูลที่ได้จาก API
 */
function updateStudentDetailModal(data) {
    const detailContent = document.getElementById('student-detail-content');
    if (!detailContent) return;
    
    // สร้าง Avatar จากชื่อนักเรียนหรือใช้รูปโปรไฟล์ถ้ามี
    let avatarHtml = '';
    if (data.student.profile_picture) {
        avatarHtml = `<div class="student-avatar" style="background-image: url('${data.student.profile_picture}'); background-size: cover;"></div>`;
    } else {
        const firstChar = data.student.name.charAt(0);
        avatarHtml = `<div class="student-avatar">${firstChar}</div>`;
    }
    
    // สร้าง HTML สำหรับ Modal
    let html = `
        <div class="student-profile">
            ${avatarHtml}
            <div class="student-info">
                <h3 class="student-name">${data.student.name}</h3>
                <p>เลขที่ ${data.student.number} รหัส ${data.student.code}</p>
                <p>ห้อง ${data.student.class}</p>
            </div>
        </div>
        
        <div class="attendance-stats">
            <div class="stat-item">
                <span class="stat-label">วันเข้าแถวทั้งหมด:</span>
                <span class="stat-value">${data.summary.present_days + data.summary.late_days}/${data.summary.total_days} วัน</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">อัตราการเข้าแถว:</span>
                <span class="stat-value ${data.summary.status}">${data.summary.attendance_percentage}%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">ขาดเรียน:</span>
                <span class="stat-value">${data.summary.absent_days} วัน</span>
            </div>
    `;
    
    if (data.summary.absent_days > 0) {
        html += `
            <div class="stat-item">
                <span class="stat-label">ขาดล่าสุด:</span>
                <span class="stat-value">${data.summary.last_absent_date}</span>
            </div>
        `;
    }
    
    html += `</div>`;
    
    // เพิ่มประวัติการเข้าแถว
    html += `
        <div class="attendance-history">
            <h4>ประวัติการเข้าแถว</h4>
    `;
    
    if (data.history && data.history.length > 0) {
        html += `
            <table class="history-table">
                <thead>
                    <tr>
                        <th style="width: 30%;">วันที่</th>
                        <th style="width: 20%;">สถานะ</th>
                        <th style="width: 15%;">เวลา</th>
                        <th style="width: 35%;">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.history.forEach(record => {
            html += `
                <tr>
                    <td>${record.thai_date}</td>
                    <td class="status ${record.status_class}">${record.status_text}</td>
                    <td>${record.time}</td>
                    <td>${record.remarks || '-'}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
    } else {
        html += `<p class="no-data-message">ไม่พบประวัติการเข้าแถว</p>`;
    }
    
    html += `</div>`;
    
    detailContent.innerHTML = html;
    
    // เก็บข้อมูลนักเรียนไว้สำหรับใช้ในฟังก์ชันติดต่อผู้ปกครอง
    detailContent.dataset.studentName = data.student.name;
    detailContent.dataset.studentId = data.student.id;
}

/**
 * ติดต่อผู้ปกครอง
 * @param {number} studentId - ID ของนักเรียน
 */
function contactParent(studentId) {
    if (!studentId) {
        console.error('ไม่ได้ระบุรหัสนักเรียน');
        return;
    }
    
    // แสดงหน้าต่างติดต่อผู้ปกครอง
    const modal = document.getElementById('contact-parent-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // ดึงข้อมูลนักเรียน
    const studentInfo = findStudentById(studentId);
    
    // อัพเดทข้อมูลใน Modal
    if (studentInfo) {
        updateParentContactModal(studentInfo);
    } else {
        // กรณีไม่พบข้อมูลในตาราง ให้ดึงข้อมูลจาก API
        fetchStudentForParentContact(studentId);
    }
}

/**
 * ค้นหาข้อมูลนักเรียนจาก ID
 * @param {number} studentId - ID ของนักเรียน
 * @returns {Object|null} ข้อมูลนักเรียนหรือ null ถ้าไม่พบ
 */
function findStudentById(studentId) {
    // ค้นหาข้อมูลนักเรียนจากตาราง
    const rows = document.querySelectorAll('.student-table tbody tr');
    
    for (let row of rows) {
        const actionButton = row.querySelector('.action-button[onclick*="viewStudentDetail"]');
        if (actionButton) {
            const onclickValue = actionButton.getAttribute('onclick');
            const match = onclickValue.match(/viewStudentDetail\((\d+)/);
            
            if (match && parseInt(match[1]) === studentId) {
                // ดึงข้อมูลจากแถวตาราง
                const cellCount = row.cells.length;
                if (cellCount >= 4) {
                    return {
                        id: studentId,
                        number: row.cells[0].textContent.trim(),
                        name: row.cells[1].textContent.trim(),
                        attendance: row.cells[2].textContent.trim(),
                        percentage: row.cells[3].querySelector('.attendance-percent').textContent.trim(),
                        status: row.cells[3].querySelector('.attendance-percent').className.replace('attendance-percent ', '')
                    };
                }
            }
        }
    }
    
    return null;
}

/**
 * ดึงข้อมูลนักเรียนจาก API สำหรับติดต่อผู้ปกครอง
 * @param {number} studentId - ID ของนักเรียน
 */
function fetchStudentForParentContact(studentId) {
    // ทำการ fetch ข้อมูล
    fetch(`api/student_attendance_history.php?student_id=${studentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // สร้างข้อมูลนักเรียนสำหรับส่งต่อไปยังฟังก์ชันอัพเดท Modal
                const studentInfo = {
                    id: data.student.id,
                    number: data.student.number,
                    name: data.student.name,
                    attendance: `${data.summary.present_days + data.summary.late_days}/${data.summary.total_days}`,
                    percentage: `${data.summary.attendance_percentage}%`,
                    status: data.summary.status
                };
                
                // อัพเดท Modal
                updateParentContactModal(studentInfo);
            } else {
                // แสดงข้อความแจ้งเตือนในกรณีที่มีข้อผิดพลาด
                const parentDetail = document.getElementById('parent-detail');
                if (parentDetail) {
                    parentDetail.innerHTML = `<div class="error">${data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'}</div>`;
                }
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
            const parentDetail = document.getElementById('parent-detail');
            if (parentDetail) {
                parentDetail.innerHTML = `<div class="error">เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}</div>`;
            }
        });
}

/**
 * อัพเดทข้อมูลใน Modal ติดต่อผู้ปกครอง
 * @param {Object} student - ข้อมูลนักเรียน
 */
function updateParentContactModal(student) {
    const parentDetail = document.getElementById('parent-detail');
    if (!parentDetail) return;
    
    // ในระบบจริงจะดึงข้อมูลผู้ปกครองจากฐานข้อมูล
    // แต่ในตัวอย่างนี้จะใช้ข้อมูลตัวอย่าง
    const parentName = "ผู้ปกครองของ " + student.name;
    
    let html = `
        <div class="parent-profile">
            <div class="parent-avatar">ผ</div>
            <div class="parent-info">
                <h3 class="parent-name">${parentName}</h3>
                <p>ผู้ปกครองของ ${student.name}</p>
                <p>ความสัมพันธ์: ผู้ปกครอง</p>
            </div>
        </div>
        
        <div class="message-form">
            <label class="option-label">ข้อความถึงผู้ปกครอง:</label>
            <textarea id="parent-message" rows="5" placeholder="ระบุข้อความที่ต้องการส่งถึงผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครอง ขอแจ้งข้อมูลการเข้าแถวของ ${student.name} อัตราการเข้าแถวปัจจุบันอยู่ที่ ${student.percentage} กรุณาติดตามและดูแลการมาเรียนของนักเรียน</textarea>
        </div>
    `;
    
    parentDetail.innerHTML = html;
    
    // เก็บ ID นักเรียนสำหรับใช้ในการส่งข้อความ
    parentDetail.dataset.studentId = student.id;
}

/**
 * ติดต่อผู้ปกครองของนักเรียนที่กำลังดูรายละเอียด
 */
function contactStudentParent() {
    // ปิด Modal รายละเอียดนักเรียน
    closeModal('student-detail-modal');
    
    // ดึงข้อมูลนักเรียนจาก Modal รายละเอียด
    const detailContent = document.getElementById('student-detail-content');
    if (!detailContent) return;
    
    const studentName = detailContent.querySelector('.student-name');
    const studentId = detailContent.dataset.studentId;
    
    if (!studentName || !studentId) {
        console.error('ไม่พบข้อมูลนักเรียน');
        return;
    }
    
    // เปิด Modal ติดต่อผู้ปกครอง
    const modal = document.getElementById('contact-parent-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // อัพเดทข้อมูลใน Modal
    const parentDetail = document.getElementById('parent-detail');
    if (parentDetail) {
        const parentName = "ผู้ปกครองของ " + studentName.textContent;
        
        let html = `
            <div class="parent-profile">
                <div class="parent-avatar">ผ</div>
                <div class="parent-info">
                    <h3 class="parent-name">${parentName}</h3>
                    <p>ผู้ปกครองของ ${studentName.textContent}</p>
                    <p>ความสัมพันธ์: ผู้ปกครอง</p>
                </div>
            </div>
            
            <div class="message-form">
                <label class="option-label">ข้อความถึงผู้ปกครอง:</label>
                <textarea id="parent-message" rows="5" placeholder="ระบุข้อความที่ต้องการส่งถึงผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครอง ขอแจ้งข้อมูลการเข้าแถวของ ${studentName.textContent} กรุณาติดตามและดูแลการมาเรียนของนักเรียน</textarea>
            </div>
        `;
        
        parentDetail.innerHTML = html;
        parentDetail.dataset.studentId = studentId;
    }
}

/**
 * ส่งข้อความถึงผู้ปกครอง
 */
function sendParentMessage() {
    const messageTextarea = document.getElementById('parent-message');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value.trim();
    if (!message) {
        alert('กรุณาระบุข้อความที่ต้องการส่งถึงผู้ปกครอง');
        return;
    }
    
    // ดึง ID นักเรียนจาก dataset
    const parentDetail = document.getElementById('parent-detail');
    if (!parentDetail || !parentDetail.dataset.studentId) {
        alert('ไม่พบข้อมูลนักเรียน');
        return;
    }
    
    const studentId = parentDetail.dataset.studentId;
    
    // แสดงการโหลด
    parentDetail.classList.add('loading');
    
    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์
    // เช่น fetch('api/send_message.php', { method: 'POST', body: JSON.stringify({ studentId, message }) })
    
    // จำลองการส่ง
    setTimeout(() => {
        // ปิด Modal
        closeModal('contact-parent-modal');
        parentDetail.classList.remove('loading');
        
        // แสดงการแจ้งเตือน
        showAlert('ส่งข้อความถึงผู้ปกครองเรียบร้อยแล้ว', 'success');
    }, 1000);
}

/**
 * พิมพ์รายงานนักเรียนรายบุคคล
 */
function printStudentReport() {
    // เตรียมข้อมูลสำหรับพิมพ์
    const detailContent = document.getElementById('student-detail-content');
    if (!detailContent) return;
    
    // สร้างหน้าใหม่สำหรับพิมพ์
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('ไม่สามารถเปิดหน้าต่างใหม่ได้ กรุณาตรวจสอบการตั้งค่าเบราว์เซอร์');
        return;
    }
    
    // สร้าง HTML สำหรับหน้าพิมพ์
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>รายงานการเข้าแถวของนักเรียน</title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
                    padding: 20px;
                    line-height: 1.5;
                }
                h1, h2, h3, h4 {
                    margin-top: 0;
                }
                .report-header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .student-info {
                    margin-bottom: 20px;
                }
                .stats-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                .stats-table th, .stats-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                .stats-table th {
                    background-color: #f2f2f2;
                }
                .history-table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .history-table th, .history-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                .history-table th {
                    background-color: #f2f2f2;
                }
                .present { color: #4caf50; }
                .late { color: #ff9800; }
                .leave { color: #2196f3; }
                .absent { color: #f44336; }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 0.9em;
                }
                @media print {
                    body {
                        font-size: 12pt;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>รายงานการเข้าแถวของนักเรียน</h1>
                <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}</p>
            </div>
            ${detailContent.innerHTML}
            <div class="footer">
                <p>รายงานนี้ออกโดยระบบน้องชูใจ AI ดูแลผู้เรียน</p>
                <p>© ${new Date().getFullYear()} ระบบเช็คชื่อเข้าแถวออนไลน์</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()">พิมพ์รายงาน</button>
                <button onclick="window.close()">ปิดหน้านี้</button>
            </div>
            <script>
                // พิมพ์อัตโนมัติหลังจากโหลดหน้าเสร็จ
                window.onload = function() {
                    // เปิดหน้าต่างพิมพ์อัตโนมัติหลังจากโหลดเสร็จ 1 วินาที
                    setTimeout(function() {
                        window.print();
                    }, 1000);
                };
            </script>
        </body>
        </html>
    `);
    
    // ปิดการเขียน document
    printWindow.document.close();
}

/**
 * แสดง Modal แจ้งเตือนผู้ปกครอง
 */
function notifyParents() {
    const modal = document.getElementById('notify-parents-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ส่งการแจ้งเตือนไปยังผู้ปกครอง
 */
function sendNotification() {
    // รับประเภทการแจ้งเตือนที่เลือก
    const notificationType = document.querySelector('input[name="notification-type"]:checked');
    if (!notificationType) return;
    
    // รับข้อความแจ้งเตือน
    const messageTextarea = document.getElementById('message-text');
    if (!messageTextarea) return;
    
    const message = messageTextarea.value.trim();
    if (!message) {
        alert('กรุณาระบุข้อความแจ้งเตือน');
        return;
    }
    
    // แสดงการโหลด
    const modalContent = document.querySelector('#notify-parents-modal .modal-content');
    if (modalContent) {
        modalContent.classList.add('loading');
    }
    
    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์
    // เช่น fetch('api/notify_parents.php', { method: 'POST', body: JSON.stringify({ type: notificationType.value, message }) })
    
    // จำลองการส่ง
    setTimeout(() => {
        // ปิด Modal
        closeModal('notify-parents-modal');
        if (modalContent) {
            modalContent.classList.remove('loading');
        }
        
        // แสดงการแจ้งเตือน
        const isAll = notificationType.value === 'all';
        showAlert(
            isAll ? 'ส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมดเรียบร้อยแล้ว' : 'ส่งการแจ้งเตือนไปยังผู้ปกครองนักเรียนที่มีปัญหาเรียบร้อยแล้ว',
            'success'
        );
    }, 1500);
}

/**
 * ดาวน์โหลดรายงานการเข้าแถว
 */
function downloadReport() {
    // แสดงการแจ้งเตือน
    showAlert('กำลังเตรียมรายงานสำหรับดาวน์โหลด...', 'info');
    
    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์เพื่อสร้างไฟล์รายงาน
    // เช่น window.location.href = 'api/generate_report.php?class_id=' + currentClassId + '&month=' + currentMonth + '&year=' + currentYear;
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดรายงานเรียบร้อยแล้ว', 'success');
    }, 2000);
}

/**
 * ดาวน์โหลดกราฟรายวัน
 */
function downloadDailyChart() {
    showAlert('กำลังเตรียมกราฟสำหรับดาวน์โหลด...', 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดกราฟเรียบร้อยแล้ว', 'success');
    }, 1500);
}

/**
 * พิมพ์กราฟรายวัน
 */
function printDailyChart() {
    showAlert('กำลังเตรียมกราฟสำหรับพิมพ์...', 'info');
    
    // เตรียมข้อมูลสำหรับพิมพ์
    const chartCard = document.querySelector('.chart-card');
    if (!chartCard) return;
    
    // สร้างหน้าใหม่สำหรับพิมพ์
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('ไม่สามารถเปิดหน้าต่างใหม่ได้ กรุณาตรวจสอบการตั้งค่าเบราว์เซอร์');
        return;
    }
    
    // สร้าง HTML สำหรับหน้าพิมพ์
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>กราฟอัตราการเข้าแถวรายวัน</title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
                    padding: 20px;
                    line-height: 1.5;
                }
                h1, h2, h3, h4 {
                    margin-top: 0;
                }
                .report-header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .chart-content {
                    margin: 20px auto;
                    max-width: 800px;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 0.9em;
                }
                @media print {
                    body {
                        font-size: 12pt;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>กราฟอัตราการเข้าแถวรายวัน</h1>
                <p>ห้อง: ${document.querySelector('.class-details h2').textContent}</p>
                <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}</p>
            </div>
            <div class="chart-content">
                ${chartCard.innerHTML}
            </div>
            <div class="footer">
                <p>รายงานนี้ออกโดยระบบน้องชูใจ AI ดูแลผู้เรียน</p>
                <p>© ${new Date().getFullYear()} ระบบเช็คชื่อเข้าแถวออนไลน์</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()">พิมพ์รายงาน</button>
                <button onclick="window.close()">ปิดหน้านี้</button>
            </div>
            <script>
                // พิมพ์อัตโนมัติหลังจากโหลดหน้าเสร็จ
                window.onload = function() {
                    // เปิดหน้าต่างพิมพ์อัตโนมัติหลังจากโหลดเสร็จ 1 วินาที
                    setTimeout(function() {
                        window.print();
                    }, 1000);
                };
            </script>
        </body>
        </html>
    `);
    
    // ปิดการเขียน document
    printWindow.document.close();
}

/**
 * ดาวน์โหลดกราฟนักเรียนรายคน
 */
function downloadStudentChart() {
    showAlert('กำลังเตรียมกราฟสำหรับดาวน์โหลด...', 'info');
    
    // จำลองการดาวน์โหลด
    setTimeout(() => {
        showAlert('ดาวน์โหลดกราฟเรียบร้อยแล้ว', 'success');
    }, 1500);
}

/**
 * พิมพ์กราฟนักเรียนรายคน
 */
function printStudentChart() {
    showAlert('กำลังเตรียมกราฟสำหรับพิมพ์...', 'info');
    
    // เตรียมข้อมูลสำหรับพิมพ์
    const graphCard = document.querySelector('.graph-card');
    if (!graphCard) return;
    
    // สร้างหน้าใหม่สำหรับพิมพ์
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('ไม่สามารถเปิดหน้าต่างใหม่ได้ กรุณาตรวจสอบการตั้งค่าเบราว์เซอร์');
        return;
    }
    
    // สร้าง HTML สำหรับหน้าพิมพ์
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>กราฟอัตราการเข้าแถวของนักเรียนรายคน</title>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'Sarabun', 'TH Sarabun New', sans-serif;
                    padding: 20px;
                    line-height: 1.5;
                }
                h1, h2, h3, h4 {
                    margin-top: 0;
                }
                .report-header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .graph-content {
                    margin: 20px auto;
                    max-width: 800px;
                }
                .footer {
                    margin-top: 30px;
                    text-align: center;
                    font-size: 0.9em;
                }
                .student-bar-container {
                    margin-bottom: 10px;
                }
                .student-bar-label {
                    display: inline-block;
                    width: 150px;
                    font-weight: bold;
                }
                .student-bar-chart {
                    display: inline-block;
                    width: 400px;
                    height: 20px;
                    background-color: #f0f0f0;
                    vertical-align: middle;
                }
                .student-bar {
                    height: 20px;
                    position: relative;
                }
                .student-bar.good {
                    background-color: #c8e6c9;
                }
                .student-bar.warning {
                    background-color: #ffe0b2;
                }
                .student-bar.danger {
                    background-color: #ffcdd2;
                }
                .student-bar-value {
                    position: absolute;
                    right: 5px;
                    top: 0;
                    font-weight: bold;
                }
                .legend {
                    margin-top: 20px;
                }
                .legend-item {
                    display: inline-block;
                    margin-right: 20px;
                }
                .legend-color {
                    display: inline-block;
                    width: 15px;
                    height: 15px;
                    margin-right: 5px;
                    vertical-align: middle;
                }
                .legend-color.good {
                    background-color: #c8e6c9;
                }
                .legend-color.warning {
                    background-color: #ffe0b2;
                }
                .legend-color.danger {
                    background-color: #ffcdd2;
                }
                @media print {
                    body {
                        font-size: 12pt;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>กราฟอัตราการเข้าแถวของนักเรียนรายคน</h1>
                <p>ห้อง: ${document.querySelector('.class-details h2').textContent}</p>
                <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}</p>
            </div>
            <div class="graph-content">
                ${graphCard.innerHTML}
            </div>
            <div class="footer">
                <p>รายงานนี้ออกโดยระบบน้องชูใจ AI ดูแลผู้เรียน</p>
                <p>© ${new Date().getFullYear()} ระบบเช็คชื่อเข้าแถวออนไลน์</p>
            </div>
            <div class="no-print">
                <button onclick="window.print()">พิมพ์รายงาน</button>
                <button onclick="window.close()">ปิดหน้านี้</button>
            </div>
            <script>
                // พิมพ์อัตโนมัติหลังจากโหลดหน้าเสร็จ
                window.onload = function() {
                    // เปิดหน้าต่างพิมพ์อัตโนมัติหลังจากโหลดเสร็จ 1 วินาที
                    setTimeout(function() {
                        window.print();
                    }, 1000);
                };
            </script>
        </body>
        </html>
    `);
    
    // ปิดการเขียน document
    printWindow.document.close();
}

/**
 * ปิด Modal
 * @param {string} modalId - ID ของ Modal ที่ต้องการปิด
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * ย้อนกลับหน้าที่แล้ว
 */
function goBack() {
    window.history.back();
}

/**
 * แสดงเมนูเพิ่มเติม (ในระบบจริงจะแสดงเมนูดรอปดาวน์)
 */
function toggleOptions() {
    // แสดงการแจ้งเตือน
    showAlert('เมนูเพิ่มเติม (กำลังพัฒนา)', 'info');
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    // ตรวจสอบว่ามี alert container หรือไม่
    let alertContainer = document.querySelector('.alert-container');
    
    if (!alertContainer) {
        // สร้าง alert container
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.position = 'fixed';
        alertContainer.style.top = '20px';
        alertContainer.style.right = '20px';
        alertContainer.style.zIndex = '9999';
        document.body.appendChild(alertContainer);
    }
    
    // สร้าง alert element
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    
    // กำหนดไอคอนตามประเภท
    let icon = 'info';
    if (type === 'success') icon = 'check_circle';
    else if (type === 'warning') icon = 'warning';
    else if (type === 'error') icon = 'error';
    
    // สร้าง HTML สำหรับ alert
    alert.innerHTML = `
        <div class="alert-icon">
            <span class="material-icons">${icon}</span>
        </div>
        <div class="alert-content">
            <div class="alert-message">${message}</div>
        </div>
        <button class="alert-close">&times;</button>
    `;
    
    // เพิ่ม alert ไปยัง container
    alertContainer.appendChild(alert);
    
    // กำหนดการปิด alert เมื่อคลิกปุ่มปิด
    const closeButton = alert.querySelector('.alert-close');
    closeButton.addEventListener('click', function() {
        alertContainer.removeChild(alert);
    });
    
    // ปิด alert โดยอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alertContainer.contains(alert)) {
            alertContainer.removeChild(alert);
        }
    }, 5000);
}