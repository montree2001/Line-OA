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

    // เพิ่ม Event Listener ให้กับปุ่มแท็บ
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
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
        if (button.getAttribute('data-tab') === tabName) {
            button.classList.add('active');
        }
    });
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ดึงเดือนปัจจุบัน
    const monthSelect = document.getElementById('month-select');
    const currentMonth = monthSelect ? monthSelect.value : '';
    const yearSelect = document.getElementById('year-select');
    const currentYear = yearSelect ? yearSelect.value : new Date().getFullYear();

    // สร้าง URL สำหรับการนำทาง
    let url = 'reports.php?class_id=' + classId;

    // เพิ่มพารามิเตอร์เดือนและปีถ้ามี
    if (currentMonth) {
        url += '&month=' + currentMonth;
    }

    if (currentYear) {
        url += '&year=' + currentYear;
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
    const classId = urlParams.get('class_id') || document.getElementById('class-select').value;
    const year = urlParams.get('year') || new Date().getFullYear();

    // สร้าง URL ใหม่พร้อมพารามิเตอร์
    const url = 'reports.php?class_id=' + classId + '&month=' + monthSelect.value + '&year=' + year;

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
    const classId = urlParams.get('class_id') || document.getElementById('class-select').value;

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
    const classId = urlParams.get('class_id') || document.getElementById('class-select').value;

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
        const number = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';

        if (name.includes(searchText) || number.includes(searchText)) {
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

    // ซ่อนส่วนข้อมูลผู้ปกครองและปุ่มติดต่อ
    const parentSection = document.getElementById('parent-detail-section');
    if (parentSection) {
        parentSection.style.display = 'none';
    }

    const contactBtn = document.getElementById('contactParentBtn');
    if (contactBtn) {
        contactBtn.style.display = 'none';
    }

    // ดึงพารามิเตอร์จาก URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentMonth = urlParams.get('month');
    const currentYear = urlParams.get('year');

    // ดึงข้อมูลนักเรียนจาก API
    let apiUrl = `api/student_attendance_history.php?student_id=${studentId}`;

    // เพิ่มพารามิเตอร์เดือนและปี (ถ้ามี)
    if (currentMonth) {
        apiUrl += `&month=${currentMonth}`;
    }

    if (currentYear) {
        apiUrl += `&year=${currentYear}`;
    }

    // ดึงข้อมูลนักเรียนและประวัติการเข้าแถว
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // แสดงข้อมูลนักเรียน
                updateStudentDetailContent(data);

                // ดึงข้อมูลผู้ปกครอง
                return fetch(`api/student_parent_data.php?student_id=${studentId}`);
            } else {
                // แสดงข้อความแจ้งเตือนในกรณีที่มีข้อผิดพลาด
                detailContent.innerHTML = `<div class="error">${data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'}</div>`;
                throw new Error('ไม่พบข้อมูลนักเรียน');
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูลผู้ปกครอง');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.parents && data.parents.length > 0) {
                // แสดงข้อมูลผู้ปกครอง
                updateParentDetailContent(data.parents);
                parentSection.style.display = 'block';
                contactBtn.style.display = 'inline-flex';
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);

            // หากเกิดข้อผิดพลาดในการดึงข้อมูลผู้ปกครอง ยังคงแสดงข้อมูลนักเรียนถ้ามี
            if (!detailContent.querySelector('.student-profile')) {
                detailContent.innerHTML = `<div class="error">เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}</div>`;
            }
        });
}

/**
 * อัพเดตข้อมูลนักเรียนใน Modal
 * @param {Object} data - ข้อมูลนักเรียน
 */
function updateStudentDetailContent(data) {
    const detailContent = document.getElementById('student-detail-content');
    if (!detailContent) return;

    const student = data.student;
    const summary = data.summary;

    // สร้าง Avatar
    let avatarHtml = '';
    if (student.profile_picture) {
        avatarHtml = `<div class="student-avatar" style="background-image: url('${student.profile_picture}'); background-size: cover;"></div>`;
    } else {
        const firstChar = student.name.charAt(0);
        avatarHtml = `<div class="student-avatar">${firstChar}</div>`;
    }

    // คำนวณจำนวนวันเข้าแถว
    const attendanceDays = summary.present_days + summary.late_days;
    const attendanceRate = summary.attendance_percentage;
    let statusClass = 'danger';

    if (attendanceRate >= 80) {
        statusClass = 'good';
    } else if (attendanceRate >= 70) {
        statusClass = 'warning';
    }

    // สร้าง HTML สำหรับข้อมูลนักเรียน
    let html = `
        <div class="student-profile">
            ${avatarHtml}
            <div class="student-info">
                <h3 class="student-name">${student.name}</h3>
                <p>เลขที่ ${student.number} รหัส ${student.code}</p>
                <p>ห้อง ${student.class}</p>
            </div>
        </div>
        
        <div class="attendance-stats">
            <div class="stat-item">
                <span class="stat-label">วันเข้าแถวทั้งหมด:</span>
                <span class="stat-value">${attendanceDays}/${summary.total_days} วัน</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">อัตราการเข้าแถว:</span>
                <span class="stat-value ${statusClass}">${attendanceRate}%</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">มาเรียน:</span>
                <span class="stat-value good">${summary.present_days} วัน</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">มาสาย:</span>
                <span class="stat-value warning">${summary.late_days} วัน</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">ลา:</span>
                <span class="stat-value">${summary.leave_days} วัน</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">ขาดเรียน:</span>
                <span class="stat-value ${summary.absent_days > 0 ? 'danger' : ''}">${summary.absent_days} วัน</span>
            </div>
        </div>
    `;

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
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                        <th>วิธีการเช็ค</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
        `;

        // แสดงข้อมูลย้อนหลัง 30 วันล่าสุด
        data.history.forEach(record => {
            html += `
                <tr>
                    <td>${record.thai_date}</td>
                    <td><span class="status ${record.status_class}">${record.status_text}</span></td>
                    <td>${record.time}</td>
                    <td>${record.method}</td>
                    <td>${record.remarks || '-'}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
        `;

        // ตัวแปรสำหรับการกำหนดหน้า (pagination)
        if (data.history.length > 30) {
            html += `
            <div class="pagination">
                <button class="pagination-button" onclick="changePage(1)">1</button>
                <button class="pagination-button" onclick="changePage(2)">2</button>
                <button class="pagination-button" onclick="changePage(3)">3</button>
            </div>
            `;
        }
    } else {
        html += `<p class="no-data-message">ไม่พบประวัติการเข้าแถว</p>`;
    }

    html += `</div>`;

    detailContent.innerHTML = html;
    detailContent.dataset.studentId = student.id;
    detailContent.dataset.studentName = student.name;
}

/**
 * อัพเดตข้อมูลผู้ปกครองใน Modal
 * @param {Array} parents - ข้อมูลผู้ปกครอง
 */
function updateParentDetailContent(parents) {
    const parentContent = document.getElementById('parent-detail-content');
    if (!parentContent) return;

    let html = '';

    parents.forEach((parent, index) => {
                // สร้าง Avatar
                let avatarHtml = '';
                if (parent.profile_picture) {
                    avatarHtml = `<div class="parent-avatar" style="background-image: url('${parent.profile_picture}'); background-size: cover;"></div>`;
                } else {
                    const firstChar = parent.name.charAt(0);
                    avatarHtml = `<div class="parent-avatar">${firstChar}</div>`;
                }

                html += `
            <div class="parent-profile" ${index > 0 ? 'style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;"' : ''}>
                ${avatarHtml}
                <div class="parent-info">
                    <h3 class="parent-name">${parent.name}</h3>
                    <p>ความสัมพันธ์: ${parent.relationship}</p>
                    ${parent.phone ? `<p>โทรศัพท์: ${parent.phone}</p>` : ''}
                    ${parent.email ? `<p>อีเมล: ${parent.email}</p>` : ''}
                </div>
            </div>
        `;
    });
    
    parentContent.innerHTML = html;
    
    // เก็บข้อมูลผู้ปกครองใน dataset
    if (parents.length > 0) {
        parentContent.dataset.parent_id = parents[0].id;
        parentContent.dataset.parent_name = parents[0].name;
        parentContent.dataset.parent_phone = parents[0].phone || '';
    }
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

    // แสดงการโหลดข้อมูล
    const parentDetail = document.getElementById('parent-detail');
    if (parentDetail) {
        parentDetail.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }

    // ดึงข้อมูลนักเรียนและผู้ปกครอง
    fetch(`api/student_parent_data.php?student_id=${studentId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // แสดงข้อมูลผู้ปกครอง
                if (data.parents && data.parents.length > 0) {
                    updateParentContactModal(data.student, data.parents);
                } else {
                    // กรณีไม่พบข้อมูลผู้ปกครอง
                    parentDetail.innerHTML = `
                        <div class="parent-profile">
                            <div class="parent-avatar">ผ</div>
                            <div class="parent-info">
                                <h3 class="parent-name">ไม่พบข้อมูลผู้ปกครอง</h3>
                                <p>นักเรียน: ${data.student.name}</p>
                                <p class="no-data-message">ยังไม่มีการบันทึกข้อมูลผู้ปกครองในระบบ</p>
                            </div>
                        </div>
                        
                        <div class="message-form">
                            <label class="option-label">ข้อความถึงผู้ปกครอง:</label>
                            <textarea id="parent-message" rows="5" placeholder="ระบุข้อความที่ต้องการส่งถึงผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครองของ ${data.student.name} ขอแจ้งข้อมูลการเข้าแถวของนักเรียน กรุณาติดตามและดูแลการมาเรียนของนักเรียน</textarea>
                        </div>
                    `;
                }
            } else {
                // แสดงข้อความแจ้งเตือนในกรณีที่มีข้อผิดพลาด
                parentDetail.innerHTML = `<div class="error">${data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'}</div>`;
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
            parentDetail.innerHTML = `<div class="error">เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}</div>`;
        });
}

/**
 * อัพเดตข้อมูลใน Modal ติดต่อผู้ปกครอง
 * @param {Object} student - ข้อมูลนักเรียน
 * @param {Array} parents - ข้อมูลผู้ปกครอง
 */
function updateParentContactModal(student, parents) {
    const parentDetail = document.getElementById('parent-detail');
    if (!parentDetail) return;

    // เลือกผู้ปกครองคนแรก
    const parent = parents[0];
    
    // สร้าง Avatar
    let avatarHtml = '';
    if (parent.profile_picture) {
        avatarHtml = `<div class="parent-avatar" style="background-image: url('${parent.profile_picture}'); background-size: cover;"></div>`;
    } else {
        const firstChar = parent.name.charAt(0);
        avatarHtml = `<div class="parent-avatar">${firstChar}</div>`;
    }
    
    let html = `
        <div class="parent-profile">
            ${avatarHtml}
            <div class="parent-info">
                <h3 class="parent-name">${parent.name}</h3>
                <p>ผู้ปกครองของ ${student.name}</p>
                <p>ความสัมพันธ์: ${parent.relationship}</p>
                ${parent.phone ? `<p>โทรศัพท์: ${parent.phone}</p>` : ''}
                ${parent.email ? `<p>อีเมล: ${parent.email}</p>` : ''}
            </div>
        </div>
        
        <div class="message-form">
            <label class="option-label">ข้อความถึงผู้ปกครอง:</label>
            <textarea id="parent-message" rows="5" placeholder="ระบุข้อความที่ต้องการส่งถึงผู้ปกครอง..." class="message-textarea">เรียนท่านผู้ปกครอง ${parent.name} ขอแจ้งข้อมูลการเข้าแถวของ ${student.name} กรุณาติดตามและดูแลการมาเรียนของนักเรียน</textarea>
        </div>
    `;

    parentDetail.innerHTML = html;

    // เก็บ ID นักเรียนและผู้ปกครองสำหรับใช้ในการส่งข้อความ
    parentDetail.dataset.studentId = student.id;
    parentDetail.dataset.parentId = parent.id;
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

    const studentId = detailContent.dataset.studentId;
    const studentName = detailContent.dataset.studentName;

    if (!studentId) {
        console.error('ไม่พบข้อมูลนักเรียน');
        return;
    }

    // เรียกฟังก์ชันติดต่อผู้ปกครอง
    contactParent(studentId);
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

    // ดึงข้อมูลจาก dataset
    const parentDetail = document.getElementById('parent-detail');
    if (!parentDetail) return;

    const studentId = parentDetail.dataset.studentId;
    const parentId = parentDetail.dataset.parentId;

    if (!studentId || !parentId) {
        showAlert('ไม่พบข้อมูลนักเรียนหรือผู้ปกครอง', 'error');
        return;
    }

    // แสดงการโหลด
    parentDetail.innerHTML += '<div class="loading-overlay">กำลังส่งข้อความ...</div>';

    // ข้อมูลที่จะส่งไปยังเซิร์ฟเวอร์
    const data = {
        student_id: studentId,
        parent_id: parentId,
        message: message
    };

    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์
    // fetch('api/send_parent_message.php', {
    //     method: 'POST', 
    //     headers: {
    //         'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify(data)
    // })
    //     .then(response => response.json())
    //     .then(data => {
    //         closeModal('contact-parent-modal');
    //         if (data.success) {
    //             showAlert('ส่งข้อความถึงผู้ปกครองเรียบร้อยแล้ว', 'success');
    //         } else {
    //             showAlert('เกิดข้อผิดพลาดในการส่งข้อความ: ' + data.message, 'error');
    //         }
    //     })
    //     .catch(error => {
    //         showAlert('เกิดข้อผิดพลาดในการส่งข้อความ', 'error');
    //         console.error('Error:', error);
    //     });

    // จำลองการส่ง
    setTimeout(() => {
        // ปิด Modal
        closeModal('contact-parent-modal');

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
                .status {
                    padding: 2px 6px;
                    border-radius: 3px;
                }
                .status.present {
                    background-color: #e8f5e9;
                    color: #4caf50;
                }
                .status.absent {
                    background-color: #ffebee;
                    color: #f44336;
                }
                .status.late {
                    background-color: #fff8e1;
                    color: #ff9800;
                }
                .status.leave {
                    background-color: #e3f2fd;
                    color: #2196f3;
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

    // ดึงพารามิเตอร์จาก URL
    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('class_id') || document.getElementById('class-select').value;

    // สร้างข้อมูลที่จะส่งไปยังเซิร์ฟเวอร์
    const data = {
        class_id: classId,
        notification_type: notificationType.value,
        message: message
    };

    // แสดงการโหลด
    const modalContent = document.querySelector('#notify-parents-modal .modal-content');
    if (modalContent) {
        modalContent.innerHTML += '<div class="loading-overlay">กำลังส่งการแจ้งเตือน...</div>';
    }

    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์
    // fetch('api/send_notification.php', {
    //     method: 'POST',
    //     headers: {
    //         'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify(data)
    // })
    //     .then(response => response.json())
    //     .then(data => {
    //         closeModal('notify-parents-modal');
    //         if (data.success) {
    //             showAlert('ส่งการแจ้งเตือนไปยังผู้ปกครองเรียบร้อยแล้ว', 'success');
    //         } else {
    //             showAlert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน: ' + data.message, 'error');
    //         }
    //     })
    //     .catch(error => {
    //         showAlert('เกิดข้อผิดพลาดในการส่งการแจ้งเตือน', 'error');
    //         console.error('Error:', error);
    //     });

    // จำลองการส่ง
    setTimeout(() => {
        // ปิด Modal
        closeModal('notify-parents-modal');

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
    // ดึงพารามิเตอร์จาก URL
    const urlParams = new URLSearchParams(window.location.search);
    const classId = urlParams.get('class_id') || document.getElementById('class-select').value;
    const month = urlParams.get('month') || new Date().getMonth() + 1;
    const year = urlParams.get('year') || new Date().getFullYear();

    // แสดงการแจ้งเตือน
    showAlert('กำลังเตรียมรายงานสำหรับดาวน์โหลด...', 'info');

    // ในระบบจริงจะส่ง AJAX request ไปยังเซิร์ฟเวอร์เพื่อสร้างไฟล์รายงาน
    // window.location.href = `api/download_report.php?class_id=${classId}&month=${month}&year=${year}`;

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
 * แสดงเมนูเพิ่มเติม
 */
function toggleOptions() {
    // แสดงการแจ้งเตือน
    showAlert('เมนูเพิ่มเติม', 'info');
    
    // ในระบบจริงจะแสดงเมนูดรอปดาวน์
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
        userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
    }
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

/**
 * เปลี่ยนหน้าในตารางประวัติการเข้าแถว (Pagination)
 * @param {number} page - หน้าที่ต้องการแสดง
 */
function changePage(page) {
    // ในระบบจริงจะดึงข้อมูลหน้าที่ต้องการจาก API
    const studentId = document.getElementById('student-detail-content').dataset.studentId;
    
    if (!studentId) return;
    
    // แสดงการโหลดข้อมูล
    const historySection = document.querySelector('.attendance-history');
    if (historySection) {
        historySection.innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
    }
    
    // ดึงพารามิเตอร์จาก URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentMonth = urlParams.get('month');
    const currentYear = urlParams.get('year');
    
    // สร้าง URL สำหรับดึงข้อมูล
    let apiUrl = `api/student_attendance_history.php?student_id=${studentId}&page=${page}`;
    
    // เพิ่มพารามิเตอร์เดือนและปี (ถ้ามี)
    if (currentMonth) {
        apiUrl += `&month=${currentMonth}`;
    }
    
    if (currentYear) {
        apiUrl += `&year=${currentYear}`;
    }
    
    // ดึงข้อมูลจาก API
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // อัพเดตเฉพาะส่วนของประวัติการเข้าแถว
                updateAttendanceHistory(data.history, page);
            } else {
                // แสดงข้อความแจ้งเตือนในกรณีที่มีข้อผิดพลาด
                historySection.innerHTML = `<div class="error">${data.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล'}</div>`;
            }
        })
        .catch(error => {
            console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
            historySection.innerHTML = `<div class="error">เกิดข้อผิดพลาดในการดึงข้อมูล: ${error.message}</div>`;
        });
}

/**
 * อัพเดตส่วนของประวัติการเข้าแถว
 * @param {Array} history - ข้อมูลประวัติการเข้าแถว
 * @param {number} currentPage - หน้าปัจจุบัน
 */
function updateAttendanceHistory(history, currentPage) {
    const historySection = document.querySelector('.attendance-history');
    if (!historySection) return;
    
    let html = `<h4>ประวัติการเข้าแถว</h4>`;
    
    if (history && history.length > 0) {
        html += `
            <table class="history-table">
                <thead>
                    <tr>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                        <th>วิธีการเช็ค</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        history.forEach(record => {
            html += `
                <tr>
                    <td>${record.thai_date}</td>
                    <td><span class="status ${record.status_class}">${record.status_text}</span></td>
                    <td>${record.time}</td>
                    <td>${record.method}</td>
                    <td>${record.remarks || '-'}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        // สร้าง pagination
        html += `
        <div class="pagination">
            <button class="pagination-button ${currentPage === 1 ? 'active' : ''}" onclick="changePage(1)">1</button>
            <button class="pagination-button ${currentPage === 2 ? 'active' : ''}" onclick="changePage(2)">2</button>
            <button class="pagination-button ${currentPage === 3 ? 'active' : ''}" onclick="changePage(3)">3</button>
        </div>
        `;
    } else {
        html += `<p class="no-data-message">ไม่พบประวัติการเข้าแถว</p>`;
    }
    
    historySection.innerHTML = html;
}