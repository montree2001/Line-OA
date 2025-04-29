/**
 * attendance-fix.js - แก้ไขระบบเช็คชื่อและเช็คชื่อย้อนหลัง
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('โหลดไฟล์แก้ไขระบบเช็คชื่อสำเร็จ');

    // ซ่อมแซมฟังก์ชัน searchStudents
    fixSearchFunction();

    // ซ่อมแซมการเช็คชื่อย้อนหลัง
    setupRetroactiveAttendance();
});

/**
 * ซ่อมแซมฟังก์ชันค้นหา
 */
function fixSearchFunction() {
    // ทำให้แน่ใจว่ามีฟังก์ชัน searchStudents
    window.searchStudents = function() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        const searchTerm = searchInput.value.toLowerCase();

        // ค้นหาในทั้งสองแท็บ
        searchInTab('waitingTab', searchTerm);
        searchInTab('checkedTab', searchTerm);

        // แสดงข้อความเมื่อไม่พบผลการค้นหา
        showSearchResultEmpty('waitingTab', searchTerm);
        showSearchResultEmpty('checkedTab', searchTerm);
    };
}

/**
 * ค้นหานักเรียนในแท็บที่ระบุ
 */
function searchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;

    // ลบข้อความว่างจากการค้นหาก่อนหน้า (ถ้ามี)
    const oldEmptyMessage = tab.querySelector('.empty-search-result');
    if (oldEmptyMessage) {
        oldEmptyMessage.remove();
    }

    // ตรวจสอบว่ามี student-list หรือไม่
    const studentList = tab.querySelector('.student-list');
    if (studentList) {
        studentList.style.display = '';
    }

    // ค้นหาทุกการ์ดนักเรียน
    const studentCards = tab.querySelectorAll('.student-card');
    let found = false;

    studentCards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const studentCode = card.querySelector('.student-code') ?
            card.querySelector('.student-code').textContent : '';

        // ค้นหาทั้งชื่อและรหัสนักเรียน
        if (searchTerm === '' ||
            name.toLowerCase().includes(searchTerm) ||
            studentCode.toLowerCase().includes(searchTerm)) {
            card.style.display = '';
            found = true;
        } else {
            card.style.display = 'none';
        }
    });

    return found;
}

/**
 * แสดงข้อความเมื่อไม่พบผลการค้นหา
 */
function showSearchResultEmpty(tabId, searchTerm) {
    if (!searchTerm) return;

    const tab = document.getElementById(tabId);
    if (!tab) return;

    // นับจำนวนการ์ดที่แสดงอยู่
    const visibleCards = Array.from(tab.querySelectorAll('.student-card')).filter(
        card => card.style.display !== 'none'
    );

    // ถ้าไม่พบการ์ดใดๆ ที่ตรงกับการค้นหา
    if (visibleCards.length === 0) {
        // ซ่อน student-list (ถ้ามี)
        const studentList = tab.querySelector('.student-list');
        if (studentList) {
            studentList.style.display = 'none';
        }

        // สร้างข้อความว่าง
        const emptyMessage = document.createElement('div');
        emptyMessage.className = 'empty-state empty-search-result';
        emptyMessage.innerHTML = `
            <div class="empty-icon"><i class="fas fa-search"></i></div>
            <h3>ไม่พบนักเรียนที่ค้นหา</h3>
            <p>ไม่พบข้อมูลที่ตรงกับ "<span class="search-term">${searchTerm}</span>"</p>
        `;

        // เพิ่มข้อความว่างเข้าไปในแท็บ
        tab.appendChild(emptyMessage);
    }
}

/**
 * ตั้งค่าการเช็คชื่อย้อนหลัง
 */
function setupRetroactiveAttendance() {
    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    const today = new Date().toISOString().split('T')[0];
    const isRetroactive = checkDate !== today;

    if (isRetroactive) {
        // เพิ่มฟังก์ชันยืนยันการเช็คชื่อย้อนหลัง
        window.confirmRetroactiveAttendance = function() {
            const studentId = document.getElementById('retroactiveStudentId').value;
            const reason = document.getElementById('retroactiveReason').value.trim();
            const status = document.querySelector('input[name="retroactiveStatus"]:checked').value;

            // ตรวจสอบว่ามีการระบุเหตุผลหรือไม่
            if (!reason) {
                alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
                document.getElementById('retroactiveReason').focus();
                return;
            }

            // สร้างข้อมูลที่จะส่ง
            const data = {
                student_id: studentId,
                status: status,
                class_id: currentClassId,
                date: checkDate,
                is_retroactive: true,
                remarks: reason,
                retroactive_reason: reason
            };

            // ปิด Modal
            closeModal('retroactiveModal');

            // แสดงข้อความกำลังดำเนินการ
            showNotification('กำลังบันทึกข้อมูลการเช็คชื่อย้อนหลัง...', 'info');

            // ส่งข้อมูลไปยัง API
            fetch('api/ajax_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server response was not OK: ' + response.status);
                    }
                    return response.json();
                })
                .then(responseData => {
                    if (responseData.success) {
                        // ดึงการ์ดนักเรียน
                        const studentCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);
                        if (studentCard) {
                            // ย้ายการ์ดไปยังแท็บเช็คชื่อแล้ว
                            moveToCheckedTab(studentCard, studentId, status, responseData.student.time_checked, responseData.attendance_id);

                            // อัพเดทจำนวนนักเรียน
                            updateStudentCounts();

                            // อัพเดทสถิติ
                            updateAttendanceStats(status);
                        } else {
                            // กรณีแก้ไขข้อมูลในแท็บเช็คชื่อแล้ว
                            const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
                            if (existingCard) {
                                updateExistingCard(existingCard, status, responseData.student.time_checked, responseData.attendance_id);

                                // อัพเดทหมายเหตุ
                                const detailsDiv = existingCard.querySelector('.student-details');
                                if (detailsDiv) {
                                    // ลบข้อมูลรหัสนักเรียน (ถ้ามี)
                                    const codeElement = detailsDiv.querySelector('.student-code');
                                    if (codeElement) {
                                        codeElement.remove();
                                    }

                                    // เพิ่มหรืออัพเดทหมายเหตุ
                                    let remarksElement = detailsDiv.querySelector('.student-remarks');
                                    if (!remarksElement) {
                                        remarksElement = document.createElement('div');
                                        remarksElement.className = 'student-remarks';
                                        detailsDiv.appendChild(remarksElement);
                                    }
                                    remarksElement.textContent = reason;
                                }

                                updateAttendanceStats(status);
                            }
                        }

                        showNotification(`บันทึกการเช็คชื่อย้อนหลัง "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                    } else {
                        showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                    }
                })
                .catch(error => {
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                    console.error('Error:', error);
                });
        };

        // แสดงวันที่กำลังเช็คชื่อย้อนหลัง
        const dateElement = document.getElementById('retroactiveDate');
        if (dateElement) {
            // แปลงรูปแบบวันที่ (YYYY-MM-DD) เป็น (DD/MM/YYYY)
            const parts = checkDate.split('-');
            const formattedDate = `${parts[2]}/${parts[1]}/${parseInt(parts[0]) + 543}`; // แปลงเป็น พ.ศ.
            dateElement.textContent = formattedDate;
        }

        // แก้ไขฟังก์ชัน showDetailAttendanceModal
        const originalShowDetailAttendanceModal = window.showDetailAttendanceModal;
        window.showDetailAttendanceModal = function(studentId, studentName) {
            // ใช้ Modal เช็คชื่อย้อนหลัง
            showRetroactiveModal(studentId, studentName);
        };

        // เพิ่มฟังก์ชัน showRetroactiveModal
        window.showRetroactiveModal = function(studentId, studentName) {
            // กำหนดค่า ID นักเรียน
            document.getElementById('retroactiveStudentId').value = studentId;

            // แสดงวันที่กำลังเช็คชื่อย้อนหลัง
            const dateElement = document.getElementById('retroactiveDate');
            if (dateElement) {
                // แปลงรูปแบบวันที่ (YYYY-MM-DD) เป็น (DD/MM/YYYY)
                const parts = checkDate.split('-');
                const formattedDate = `${parts[2]}/${parts[1]}/${parseInt(parts[0]) + 543}`; // แปลงเป็น พ.ศ.
                dateElement.textContent = formattedDate;
            }

            // รีเซ็ตค่าฟอร์ม
            document.getElementById('retroactiveReason').value = '';
            const presentOption = document.querySelector('input[name="retroactiveStatus"][value="present"]');
            if (presentOption) {
                presentOption.checked = true;
            }

            // แสดง Modal
            showModal('retroactiveModal');
        };
    }
}