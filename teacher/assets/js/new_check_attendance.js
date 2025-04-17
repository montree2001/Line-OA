/**
 * new_check_attendance.js - สคริปต์สำหรับหน้าเช็คชื่อนักเรียนรูปแบบใหม่
 * 
 * ฟังก์ชันหลัก:
 * - ระบบเช็คชื่อ 4 สถานะ: มา, ขาด, สาย, ลา
 * - สร้าง PIN สำหรับนักเรียนเช็คชื่อ
 * - สแกน QR Code
 * - เช็คชื่อทั้งห้อง
 * - บันทึกข้อมูลลงฐานข้อมูล
 * - UI/UX ที่ใช้งานง่าย
 * - แสดงปีเป็น พ.ศ.
 */

// ตัวแปรหลัก
let attendanceData = {
    class_id: currentClassId,
    date: checkDate,
    teacher_id: teacherId,
    students: [],
    is_retroactive: isRetroactive,
    check_method: 'Manual'
};

// ตัวแปรเก็บสถานะว่ามีการเปลี่ยนแปลงข้อมูลหรือไม่
let hasChanges = false;

// เมื่อโหลดหน้าเสร็จ ให้เริ่มการทำงาน
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นระบบเช็คชื่อ
    initAttendanceSystem();



    // แสดงตัวอย่างการ Debug
    console.log("ระบบเช็คชื่อพร้อมใช้งาน", {
        class_id: currentClassId,
        date: checkDate,
        teacher_id: teacherId,
        isRetroactive: isRetroactive
    });
});


/**
 * เริ่มต้นระบบเช็คชื่อ
 */
function initAttendanceSystem() {
    // จัดการคลิกที่แท็บ
    setupTabSystem();

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    setupRemarkField();

    // ป้องกันการออกจากหน้าโดยไม่บันทึก
    setupBeforeUnload();
}

/**
 * จัดการระบบแท็บ
 */
function setupTabSystem() {
    const tabButtons = document.querySelectorAll('.tab-button');

    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // ลบคลาส active จากทุกปุ่มและแท็บ
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });

            // เพิ่มคลาส active ให้ปุ่มที่คลิกและแท็บที่เกี่ยวข้อง
            this.classList.add('active');

            const tabId = this.getAttribute('data-tab');
            const tabPane = document.getElementById(tabId + 'Tab');
            if (tabPane) {
                tabPane.classList.add('active');
            }
        });
    });
}

/**
 * แสดง/ซ่อนช่องหมายเหตุตามสถานะ
 */
function setupRemarkField() {
    const statusInputs = document.querySelectorAll('input[name="attendanceStatus"]');
    const remarksContainer = document.getElementById('remarksContainer');

    if (statusInputs.length > 0 && remarksContainer) {
        statusInputs.forEach(input => {
            input.addEventListener('change', function() {
                const status = this.value;

                // แสดงช่องหมายเหตุเฉพาะเมื่อเลือกสถานะมาสายหรือลา
                if (status === 'late' || status === 'leave') {
                    remarksContainer.style.display = 'block';
                } else {
                    remarksContainer.style.display = 'none';
                }
            });
        });

        // ตรวจสอบค่าเริ่มต้น
        const checkedStatus = document.querySelector('input[name="attendanceStatus"]:checked');
        if (checkedStatus) {
            if (checkedStatus.value === 'late' || checkedStatus.value === 'leave') {
                remarksContainer.style.display = 'block';
            } else {
                remarksContainer.style.display = 'none';
            }
        }
    }
}

/**
 * ป้องกันการออกจากหน้าเว็บโดยไม่บันทึก
 */
function setupBeforeUnload() {
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            // แสดงข้อความยืนยันก่อนออกจากหน้า
            const confirmationMessage = 'คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    });
}

/**
 * เปิด/ปิดเมนูเพิ่มเติม
 */
function toggleOptions() {
    const optionsMenu = document.getElementById('optionsMenu');
    if (optionsMenu) {
        optionsMenu.classList.toggle('active');
    }

    // ปิดเมนูเมื่อคลิกที่อื่น
    document.addEventListener('click', function(e) {
        if (optionsMenu && !optionsMenu.contains(e.target) && !e.target.closest('.header-icon')) {
            optionsMenu.classList.remove('active');
        }
    });
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - รหัสห้องเรียน
 */
function changeClass(classId) {
    if (hasChanges) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            window.location.href = `new_check_attendance.php?class_id=${classId}&date=${checkDate}`;
        }
    } else {
        window.location.href = `new_check_attendance.php?class_id=${classId}&date=${checkDate}`;
    }
}

/**
 * เปลี่ยนวันที่เช็คชื่อ
 * @param {string} date - วันที่ต้องการเช็คชื่อ
 */
function changeDate(date) {
    if (hasChanges) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${date}`;
        }
    } else {
        window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${date}`;
    }
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    const searchInput = document.getElementById('searchInput');
    const searchTerm = searchInput.value.toLowerCase();

    // ค้นหาในทั้งสองแท็บ
    searchInTab('waitingTab', searchTerm);
    searchInTab('checkedTab', searchTerm);
}

/**
 * ค้นหานักเรียนในแท็บที่กำหนด
 * @param {string} tabId - ID ของแท็บ
 * @param {string} searchTerm - คำค้นหา
 */
function searchInTab(tabId, searchTerm) {
    const tab = document.getElementById(tabId);
    if (!tab) return;

    const studentCards = tab.querySelectorAll('.student-card');

    studentCards.forEach(card => {
        const name = card.getAttribute('data-name') ? .toLowerCase() || '';

        if (name.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * เช็คชื่อนักเรียนจากปุ่มทันที (มา/ขาด)
 * @param {HTMLElement} button - ปุ่มที่กด
 * @param {string} status - สถานะ (present/absent)
 * @param {number} studentId - รหัสนักเรียน
 */
function markAttendance(button, status, studentId) {
    try {
        // ดึงการ์ดนักเรียน
        const studentCard = button.closest('.student-card');

        if (!studentCard) {
            showNotification('เกิดข้อผิดพลาด: ไม่พบข้อมูลนักเรียน', 'error');
            console.error('ไม่พบ .student-card ที่เป็น parent ของปุ่มที่คลิก');
            return;
        }

        // บันทึกข้อมูลการเช็คชื่อในตัวแปร
        updateAttendanceData(studentId, status, '');

        // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
        moveStudentToCheckedTab(studentCard, studentId, status);

        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
        updateStudentCounts();

        // อัพเดทสถิติการเช็คชื่อ
        updateAttendanceStats(status);



        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;

        // แสดงข้อความแจ้งเตือน
        showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ: ' + error.message, 'error');
    }
}

/**
 * แสดงรายละเอียดการเช็คชื่อ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function showDetailAttendance(studentId, studentName) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }

    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }

    // ระบุว่าเป็นการเพิ่มใหม่ ไม่ใช่การแก้ไข
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '0';
    }

    // รีเซ็ตค่า attendance_id
    const attendanceIdInput = document.getElementById('attendanceIdDetail');
    if (attendanceIdInput) {
        attendanceIdInput.value = '';
    }

    // รีเซ็ตค่าตัวเลือกเป็น "มาเรียน"
    const presentOption = document.querySelector('input[name="attendanceStatus"][value="present"]');
    if (presentOption) {
        presentOption.checked = true;
    }

    // รีเซ็ตค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = '';
    }

    // รีเซ็ตค่าหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
    const retroactiveNoteInput = document.getElementById('retroactiveNote');
    if (retroactiveNoteInput) {
        retroactiveNoteInput.value = '';
    }

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        remarksContainer.style.display = 'none';
    }

    // แสดง Modal
    showModal('attendanceDetailModal');
}

/**
 * แก้ไขการเช็คชื่อ
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 * @param {string} status - สถานะปัจจุบัน
 * @param {string} remarks - หมายเหตุ
 */
function editAttendance(studentId, studentName, status, remarks) {
    // แสดงชื่อนักเรียนใน Modal
    const studentNameElement = document.getElementById('studentNameDetail');
    if (studentNameElement) {
        studentNameElement.textContent = studentName;
    }

    // กำหนดค่า ID นักเรียน
    const studentIdInput = document.getElementById('studentIdDetail');
    if (studentIdInput) {
        studentIdInput.value = studentId;
    }

    // ระบุว่าเป็นการแก้ไข ไม่ใช่การเพิ่มใหม่
    const isEditMode = document.getElementById('isEditMode');
    if (isEditMode) {
        isEditMode.value = '1';
    }

    // ดึงและกำหนดค่า attendance_id
    const studentCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
    if (studentCard) {
        const attendanceId = studentCard.getAttribute('data-attendance-id');
        const attendanceIdInput = document.getElementById('attendanceIdDetail');
        if (attendanceIdInput && attendanceId) {
            attendanceIdInput.value = attendanceId;
        }
    }

    // เลือกสถานะปัจจุบัน
    const statusOption = document.querySelector(`input[name="attendanceStatus"][value="${status}"]`);
    if (statusOption) {
        statusOption.checked = true;
    }

    // ใส่ค่าหมายเหตุ
    const remarksInput = document.getElementById('attendanceRemarks');
    if (remarksInput) {
        remarksInput.value = remarks || '';
    }

    // รีเซ็ตค่าหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
    const retroactiveNoteInput = document.getElementById('retroactiveNote');
    if (retroactiveNoteInput) {
        retroactiveNoteInput.value = '';
    }

    // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
    const remarksContainer = document.getElementById('remarksContainer');
    if (remarksContainer) {
        if (status === 'late' || status === 'leave') {
            remarksContainer.style.display = 'block';
        } else {
            remarksContainer.style.display = 'none';
        }
    }

    // แสดง Modal
    showModal('attendanceDetailModal');
}


/**
 * ยืนยันการเช็คชื่อจาก Modal รายละเอียด
 */
function confirmDetailAttendance() {
    try {
        // ดึงข้อมูลจาก Modal
        const studentId = document.getElementById('studentIdDetail').value;
        const isEditMode = document.getElementById('isEditMode').value === '1';
        const attendanceId = document.getElementById('attendanceIdDetail') ? .value || null;

        // ดึงสถานะที่เลือก
        const statusElement = document.querySelector('input[name="attendanceStatus"]:checked');
        if (!statusElement) {
            showNotification('กรุณาเลือกสถานะการเช็คชื่อ', 'warning');
            return;
        }
        const status = statusElement.value;

        // ดึงหมายเหตุ
        let remarks = '';

        if (status === 'late' || status === 'leave') {
            const remarksInput = document.getElementById('attendanceRemarks');
            if (remarksInput) {
                remarks = remarksInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (remarks === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการมาสาย/ลา', 'warning');
                    return;
                }
            }
        }

        // ถ้าเป็นการเช็คชื่อย้อนหลัง ให้ดึงหมายเหตุเพิ่มเติม
        if (isRetroactive) {
            const retroactiveNoteInput = document.getElementById('retroactiveNote');
            if (retroactiveNoteInput) {
                const retroactiveNote = retroactiveNoteInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (retroactiveNote === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }

                // เพิ่มหมายเหตุย้อนหลัง
                remarks = remarks ? `${remarks} (${retroactiveNote})` : retroactiveNote;
            }
        }

        // สร้างข้อมูลที่จะส่ง
        const data = {
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            is_retroactive: isRetroactive,
            remarks: remarks
        };

        // แสดงสถานะกำลังโหลด
        const saveButton = document.querySelector('#attendanceDetailModal .btn.primary');
        if (saveButton) {
            const originalText = saveButton.textContent;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
            saveButton.disabled = true;

            // ส่งข้อมูลไปบันทึก AJAX
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
                    // คืนค่าปุ่มเดิม
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;

                    if (responseData.success) {
                        // ปิด Modal
                        closeModal('attendanceDetailModal');

                        if (isEditMode) {
                            // กรณีแก้ไข: อัพเดทการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
                            updateStudentCard(studentId, status, remarks, responseData.attendance_id);
                        } else {
                            // กรณีเพิ่มใหม่: ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
                            const studentCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);

                            if (studentCard) {
                                moveStudentToCheckedTab(studentCard, studentId, status, remarks);
                            }
                        }

                        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
                        updateStudentCounts();

                        // อัพเดทสถิติการเช็คชื่อ
                        updateAttendanceStats();

                        // แสดงข้อความแจ้งเตือน
                        showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                    } else {
                        // แสดงข้อความเมื่อมีข้อผิดพลาด
                        showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                    }
                })
                .catch(error => {
                    // คืนค่าปุ่มเดิม
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;

                    console.error('Error:', error);
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                });
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการยืนยันการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อ: ' + error.message, 'error');
    }
}

/**
 * แสดง Modal เช็คชื่อทั้งหมด
 */
function markAllAttendance() {
    const uncheckedCount = document.querySelectorAll('#waitingTab .student-card').length;

    if (uncheckedCount === 0) {
        showNotification('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }

    showModal('markAllModal');
}

/**
 * ยืนยันการเช็คชื่อทั้งหมด
 */
function confirmMarkAll() {
    try {
        // ดึงสถานะที่เลือก
        const statusElement = document.querySelector('input[name="markAllStatus"]:checked');
        if (!statusElement) {
            showNotification('กรุณาเลือกสถานะการเช็คชื่อ', 'warning');
            return;
        }
        const status = statusElement.value;

        // ดึงหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
        let remarks = '';

        if (isRetroactive) {
            const remarksInput = document.getElementById('markAllRetroactiveNote');
            if (remarksInput) {
                remarks = remarksInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (remarks === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
            }
        }

        // คำแนะนำเพิ่มเติมสำหรับสถานะมาสายและลา
        if ((status === 'late' || status === 'leave') && !remarks && !isRetroactive) {
            if (!confirm(`คุณกำลังจะเช็คชื่อนักเรียนทั้งหมดเป็น "${getStatusText(status)}" โดยไม่มีการระบุหมายเหตุ ต้องการดำเนินการต่อหรือไม่?`)) {
                return;
            }
        }

        // ดึงทุกการ์ดนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const studentCards = document.querySelectorAll('#waitingTab .student-card');

        if (studentCards.length === 0) {
            closeModal('markAllModal');
            showNotification('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
            return;
        }

        // เช็คชื่อทุกคน
        studentCards.forEach(card => {
            const studentId = card.getAttribute('data-id');

            // บันทึกข้อมูลการเช็คชื่อในตัวแปร
            updateAttendanceData(studentId, status, remarks);

            // ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
            moveStudentToCheckedTab(card, studentId, status, remarks);
        });

        // อัพเดทจำนวนนักเรียนในแต่ละแท็บ
        updateStudentCounts();

        // อัพเดทสถิติการเช็คชื่อ
        updateAttendanceStatsAll(status, studentCards.length);

        // ปิด Modal
        closeModal('markAllModal');



        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;

        // แสดงข้อความแจ้งเตือน
        showNotification(`เช็คชื่อนักเรียนทั้งหมด ${studentCards.length} คน เป็น "${getStatusText(status)}" เรียบร้อย`, 'success');
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด:', error);
        showNotification('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด: ' + error.message, 'error');
    }
}

/**
 * สร้าง PIN สำหรับการเช็คชื่อ
 */
function createPIN() {
    // แสดงข้อความกำลังดำเนินการ
    showNotification('กำลังสร้างรหัส PIN...', 'info');

    // แสดง Modal สร้าง PIN ก่อน
    showModal('pinModal');

    // ส่งคำขอสร้าง PIN ใหม่ไปยัง API
    fetch('api/create_pin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                class_id: currentClassId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Server response was not OK: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // แสดง PIN ใน Modal
                displayPINCode(data.pin_code);

                // อัพเดทเวลาหมดอายุ
                const expireTimeElement = document.getElementById('expireTime');
                if (expireTimeElement) {
                    expireTimeElement.textContent = data.expire_minutes;
                }

                // แสดงข้อความแจ้งเตือนสำเร็จ
                showNotification('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
            } else {
                // แสดงข้อความเมื่อมีข้อผิดพลาด
                showNotification(data.message || 'เกิดข้อผิดพลาดในการสร้าง PIN', 'error');
                // แสดง PIN ข้อผิดพลาด
                displayPINCode('ERROR');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
            // แสดง PIN ข้อผิดพลาด
            displayPINCode('ERROR');
        });
}

/**
 * แสดงรหัส PIN ใน Modal
 * @param {string} pinCode - รหัส PIN 4 หลัก
 */
function displayPINCode(pinCode) {
    const digitElements = document.querySelectorAll('.pin-digit');

    if (pinCode === 'ERROR') {
        // แสดงข้อผิดพลาด
        digitElements.forEach(element => {
            element.textContent = 'E';
            element.style.color = 'red';
        });
        return;
    }

    // แสดง PIN ปกติ
    const digits = pinCode.split('');
    digitElements.forEach((element, index) => {
        if (index < digits.length) {
            element.textContent = digits[index];
            element.style.color = '';
        } else {
            element.textContent = '-';
            element.style.color = '';
        }
    });
}

/**
 * สร้างรหัส PIN ใหม่
 */
function generateNewPIN() {
    // ลบค่า PIN เดิม
    const digitElements = document.querySelectorAll('.pin-digit');
    digitElements.forEach(element => {
        element.textContent = '-';
    });

    // เรียกฟังก์ชันสร้าง PIN ใหม่
    createPIN();
}

/**
 * เปิดกล้องเพื่อสแกน QR Code
 */
function scanQR() {
    showModal('qrModal');

    // ในระบบจริงจะมีการเปิดกล้องและสแกน QR Code
    showNotification('ระบบกำลังเรียกใช้กล้อง กรุณารอสักครู่...', 'info');

    // ตัวอย่างการจำลองการใช้กล้อง
    // จะถูกแทนที่ด้วยการใช้ library ที่สามารถสแกน QR Code จริงๆ
    setTimeout(() => {
        const qrPlaceholder = document.querySelector('.qr-placeholder');
        if (qrPlaceholder) {
            qrPlaceholder.innerHTML = `
                <i class="fas fa-camera"></i>
                <p>กำลังสแกน QR Code...</p>
            `;
        }
    }, 1000);
}

/**
 * บันทึกการเช็คชื่อ
 */
function saveAttendance() {
    // อัพเดทจำนวนในโมดัล
    const checkedCount = document.querySelectorAll('#checkedTab .student-card').length;
    const remainingCount = document.querySelectorAll('#waitingTab .student-card').length;

    const saveCheckedCount = document.getElementById('saveCheckedCount');
    const saveRemainingCount = document.getElementById('saveRemainingCount');

    if (saveCheckedCount) {
        saveCheckedCount.textContent = checkedCount;
    }

    if (saveRemainingCount) {
        saveRemainingCount.textContent = remainingCount;
    }

    // แสดง Modal ยืนยันการบันทึก
    showModal('saveAttendanceModal');
}

/**
 * ยืนยันการบันทึกการเช็คชื่อ
 */
function confirmSaveAttendance() {
    try {
        // ดึงหมายเหตุการเช็คย้อนหลัง (ถ้ามี)
        let retroactiveNote = '';
        if (isRetroactive) {
            const remarksInput = document.getElementById('saveRetroactiveNote');
            if (remarksInput) {
                retroactiveNote = remarksInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (retroactiveNote === '') {
                    showNotification('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
            }
        }

        // เพิ่มนักเรียนที่ยังไม่ได้เช็คชื่อเป็นขาดเรียน
        const uncheckedStudents = document.querySelectorAll('#waitingTab .student-card');
        uncheckedStudents.forEach(card => {
            const studentId = card.getAttribute('data-id');

            // บันทึกข้อมูลการเช็คชื่อในตัวแปร (ขาดเรียน)
            updateAttendanceData(studentId, 'absent', retroactiveNote || '');
        });

        // ปิด Modal
        closeModal('saveAttendanceModal');

        // แสดงการโหลด
        const saveButton = document.getElementById('saveAttendanceBtn');
        if (saveButton) {
            const originalHTML = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-sync fa-spin"></i>';
            saveButton.disabled = true;

            // ส่งข้อมูลไปยัง API
            fetch('api/save_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(attendanceData)
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server response was not OK: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    // คืนค่าปุ่ม
                    saveButton.innerHTML = originalHTML;
                    saveButton.disabled = false;

                    if (data.success) {
                        // แสดงข้อความแจ้งเตือน
                        showNotification('บันทึกการเช็คชื่อเรียบร้อย', 'success');



                        // ตั้งค่าว่าไม่มีการเปลี่ยนแปลงข้อมูลแล้ว
                        hasChanges = false;

                        // รีโหลดหน้า
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        // แสดงข้อความเมื่อมีข้อผิดพลาด
                        showNotification(data.message || 'เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ', 'error');
                    }
                })
                .catch(error => {
                    // คืนค่าปุ่ม
                    saveButton.innerHTML = originalHTML;
                    saveButton.disabled = false;

                    console.error('Error:', error);
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                });
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ: ' + error.message, 'error');
    }
}

/**
 * แสดงคำแนะนำการใช้งาน
 */
function showHelp() {
    showModal('helpModal');
}

/**
 * ดาวน์โหลดรายงาน
 */
function downloadReport() {
    showNotification('กำลังเตรียมข้อมูลรายงาน...', 'info');

    // ส่งคำขอไปยัง API เพื่อดาวน์โหลดรายงาน
    window.location.href = `api/download_report.php?class_id=${currentClassId}&date=${checkDate}`;
}

/**
 * บันทึกข้อมูลการเช็คชื่อในตัวแปร
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ
 * @param {number|null} attendanceId - รหัสการเช็คชื่อ (กรณีแก้ไข)
 */
function updateAttendanceData(studentId, status, remarks, attendanceId = null) {
    // แปลงเป็นตัวเลข
    studentId = parseInt(studentId);

    // ตรวจสอบว่ามีข้อมูลนักเรียนในตัวแปรแล้วหรือไม่
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);

    if (studentIndex !== -1) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        attendanceData.students[studentIndex].remarks = remarks;

        // อัพเดท attendance_id ถ้ามี
        if (attendanceId) {
            attendanceData.students[studentIndex].attendance_id = parseInt(attendanceId);
        }
    } else {
        // เพิ่มข้อมูลใหม่
        const studentData = {
            student_id: studentId,
            status: status,
            remarks: remarks
        };

        // เพิ่ม attendance_id ถ้ามี
        if (attendanceId) {
            studentData.attendance_id = parseInt(attendanceId);
        }

        attendanceData.students.push(studentData);
    }
}

/**
 * ย้ายการ์ดนักเรียนไปยังแท็บ "เช็คชื่อแล้ว"
 * @param {HTMLElement} studentCard - การ์ดนักเรียน
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function moveStudentToCheckedTab(studentCard, studentId, status, remarks = '') {
    try {
        // ลบการ์ดจากแท็บเดิม
        studentCard.remove();

        // ตรวจสอบว่ามีรายการในแท็บเดิมเหลืออยู่หรือไม่
        const waitingTab = document.getElementById('waitingTab');
        if (!waitingTab) {
            console.error('ไม่พบ element waitingTab');
            return;
        }

        const waitingStudents = waitingTab.querySelectorAll('.student-card');

        if (waitingStudents.length === 0) {
            // ถ้าไม่มีรายการเหลือ ให้แสดงข้อความว่าง
            const emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <div class="empty-icon"><i class="fas fa-check-circle"></i></div>
                <h3>เช็คชื่อครบทุกคนแล้ว!</h3>
                <p>ทุกคนได้รับการเช็คชื่อเรียบร้อยแล้ว</p>
            `;
            waitingTab.innerHTML = '';
            waitingTab.appendChild(emptyState);
        }

        // สร้างการ์ดใหม่ในแท็บ "เช็คชื่อแล้ว"
        createCheckedStudentCard(studentId, status, remarks);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการย้ายการ์ดนักเรียน:', error);
        showNotification('เกิดข้อผิดพลาดในการย้ายการ์ดนักเรียน: ' + error.message, 'error');
    }
}

/**
 * สร้างการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function createCheckedStudentCard(studentId, status, remarks = '') {
    try {
        // ดึงข้อมูลนักเรียนจากแท็บที่ยังไม่ได้เช็ค
        const originalCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);

        // ตรวจสอบตาราง checked-tab
        const checkedTab = document.getElementById('checkedTab');
        if (!checkedTab) {
            console.error('ไม่พบ element checkedTab');
            return;
        }

        let studentList = checkedTab.querySelector('.student-list');

        // ตรวจสอบว่ามีข้อความว่างหรือไม่
        const emptyState = checkedTab.querySelector('.empty-state');
        if (emptyState) {
            // ถ้ามีข้อความว่าง ให้ลบออกและสร้างรายการใหม่
            emptyState.remove();

            if (!studentList) {
                studentList = document.createElement('div');
                studentList.className = 'student-list';
                checkedTab.appendChild(studentList);
            }
        }

        // สร้าง studentList ถ้ายังไม่มี
        if (!studentList) {
            studentList = document.createElement('div');
            studentList.className = 'student-list';
            checkedTab.appendChild(studentList);
        }

        // หาข้อมูลจากแท็บที่เช็คแล้ว (กรณีแก้ไข)
        const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
        if (existingCard) {
            // อัพเดทการ์ดที่มีอยู่
            updateStudentCard(studentId, status, remarks);
            return;
        }

        // ถ้าไม่มี originalCard ให้ดึงข้อมูลจาก DOM หรือ API
        if (!originalCard) {
            console.warn('ไม่พบการ์ดนักเรียนต้นฉบับ จะพยายามดึงข้อมูลจากทางอื่น');

            // ในกรณีนี้ต้องดึงข้อมูลจาก API หรือข้อมูลที่บันทึกไว้แล้ว
            // แต่ในขั้นตอนนี้จะใช้วิธีการสร้างการ์ดแบบง่ายๆ ก่อน
            const studentName = 'นักเรียน #' + studentId; // ควรดึงจากที่อื่น
            const studentCode = ''; // ควรดึงจากที่อื่น

            // สร้างการ์ดใหม่
            createGenericStudentCard(studentId, studentName, studentCode, status, remarks);
            return;
        }

        // ดึงข้อมูลจาก originalCard
        const studentNumber = originalCard.querySelector('.student-number') ? .textContent || '';
        const studentName = originalCard.getAttribute('data-name') || 'ไม่ระบุชื่อ';
        const avatarElement = originalCard.querySelector('.student-avatar');
        const studentAvatar = avatarElement ? avatarElement.outerHTML : '<div class="student-avatar">?</div>';
        const studentCodeElement = originalCard.querySelector('.student-code');
        const studentCode = studentCodeElement ? studentCodeElement.textContent : 'รหัส: -';

        // กำหนดสถานะและไอคอน
        let statusClass = '';
        let statusIcon = '';
        let statusText = '';

        switch (status) {
            case 'present':
                statusClass = 'present';
                statusIcon = 'fa-check-circle';
                statusText = 'มาเรียน';
                break;
            case 'late':
                statusClass = 'late';
                statusIcon = 'fa-clock';
                statusText = 'มาสาย';
                break;
            case 'leave':
                statusClass = 'leave';
                statusIcon = 'fa-clipboard';
                statusText = 'ลา';
                break;
            case 'absent':
                statusClass = 'absent';
                statusIcon = 'fa-times-circle';
                statusText = 'ขาดเรียน';
                break;
        }

        // สร้างเวลาปัจจุบัน
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const timeString = `${hours}:${minutes}`;

        // สร้างการ์ดใหม่
        const newCard = document.createElement('div');
        newCard.className = `student-card ${statusClass}-card`;
        newCard.setAttribute('data-id', studentId);
        newCard.setAttribute('data-name', studentName);
        newCard.setAttribute('data-status', status);

        // สร้าง HTML ของการ์ด
        newCard.innerHTML = `
            <div class="student-number">${studentNumber}</div>
            
            <div class="student-info" onclick="if (typeof editAttendance === 'function') { editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', '${remarks.replace(/'/g, "\\'")}'); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">
                ${studentAvatar}
                
                <div class="student-details">
                    <div class="student-name">${studentName}</div>
                    ${remarks ? `<div class="student-remarks">${remarks}</div>` : `<div class="student-code">${studentCode}</div>`}
                </div>
            </div>
            
            <div class="student-status-info">
                <div class="status-badge ${statusClass}">
                    <i class="fas ${statusIcon}"></i> ${statusText}
                </div>
                
                <div class="check-details">
                    <div class="check-time">${timeString}</div>
                    <div class="check-method">ครู</div>
                </div>
            </div>
        `;
        
        // เพิ่มการ์ดใหม่ลงในแท็บ "เช็คชื่อแล้ว"
        studentList.appendChild(newCard);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการสร้างการ์ดนักเรียน:', error);
        showNotification('เกิดข้อผิดพลาดในการสร้างการ์ดนักเรียน: ' + error.message, 'error');
    }
}

/**
 * สร้างการ์ดนักเรียนทั่วไป (กรณีไม่มีข้อมูลต้นฉบับ)
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 * @param {string} studentCode - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function createGenericStudentCard(studentId, studentName, studentCode, status, remarks = '') {
    try {
        // ตรวจสอบตาราง checked-tab
        const checkedTab = document.getElementById('checkedTab');
        if (!checkedTab) {
            console.error('ไม่พบ element checkedTab');
            return;
        }
        
        let studentList = checkedTab.querySelector('.student-list');
        
        // ตรวจสอบว่ามีข้อความว่างหรือไม่
        const emptyState = checkedTab.querySelector('.empty-state');
        if (emptyState) {
            // ถ้ามีข้อความว่าง ให้ลบออกและสร้างรายการใหม่
            emptyState.remove();
            
            if (!studentList) {
                studentList = document.createElement('div');
                studentList.className = 'student-list';
                checkedTab.appendChild(studentList);
            }
        }
        
        // สร้าง studentList ถ้ายังไม่มี
        if (!studentList) {
            studentList = document.createElement('div');
            studentList.className = 'student-list';
            checkedTab.appendChild(studentList);
        }
        
        // กำหนดสถานะและไอคอน
        let statusClass = '';
        let statusIcon = '';
        let statusText = '';
        
        switch (status) {
            case 'present':
                statusClass = 'present';
                statusIcon = 'fa-check-circle';
                statusText = 'มาเรียน';
                break;
            case 'late':
                statusClass = 'late';
                statusIcon = 'fa-clock';
                statusText = 'มาสาย';
                break;
            case 'leave':
                statusClass = 'leave';
                statusIcon = 'fa-clipboard';
                statusText = 'ลา';
                break;
            case 'absent':
                statusClass = 'absent';
                statusIcon = 'fa-times-circle';
                statusText = 'ขาดเรียน';
                break;
        }
        
        // สร้างเวลาปัจจุบัน
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const timeString = `${hours}:${minutes}`;
        
        // สร้างการ์ดใหม่
        const newCard = document.createElement('div');
        newCard.className = `student-card ${statusClass}-card`;
        newCard.setAttribute('data-id', studentId);
        newCard.setAttribute('data-name', studentName);
        newCard.setAttribute('data-status', status);
        
        // สร้างตัวอักษรแรกของชื่อสำหรับ avatar
        const firstChar = studentName.charAt(0) || '?';
        
        // สร้าง HTML ของการ์ด
        newCard.innerHTML = `
            <div class="student-number">?</div>
            
            <div class="student-info" onclick="if (typeof editAttendance === 'function') { editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', '${remarks.replace(/'/g, "\\'")}'); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }">
                <div class="student-avatar">${firstChar}</div>
                
                <div class="student-details">
                    <div class="student-name">${studentName}</div>
                    ${remarks ? `<div class="student-remarks">${remarks}</div>` : `<div class="student-code">${studentCode}</div>`}
                </div>
            </div>
            
            <div class="student-status-info">
                <div class="status-badge ${statusClass}">
                    <i class="fas ${statusIcon}"></i> ${statusText}
                </div>
                
                <div class="check-details">
                    <div class="check-time">${timeString}</div>
                    <div class="check-method">ครู</div>
                </div>
            </div>
        `;
        
        // เพิ่มการ์ดใหม่ลงในแท็บ "เช็คชื่อแล้ว"
        studentList.appendChild(newCard);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการสร้างการ์ดนักเรียนทั่วไป:', error);
        showNotification('เกิดข้อผิดพลาดในการสร้างการ์ดนักเรียน: ' + error.message, 'error');
    }
}

/**
 * อัพเดทการ์ดนักเรียนในแท็บ "เช็คชื่อแล้ว"
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 * @param {number|null} attendanceId - รหัสการเช็คชื่อ (กรณีแก้ไข)
 */
function updateStudentCard(studentId, status, remarks = '', attendanceId = null) {
    try {
        // ดึงการ์ดที่ต้องการอัพเดท
        const card = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
        
        if (!card) {
            console.error('ไม่พบการ์ดนักเรียนที่ต้องการอัพเดท');
            return;
        }
        
        // บันทึก attendance_id ถ้ามี
        if (attendanceId) {
            card.setAttribute('data-attendance-id', attendanceId);
        }
        
        // อัพเดทคลาสของการ์ด
        card.className = `student-card ${status}-card`;
        card.setAttribute('data-status', status);
        
        // กำหนดสถานะและไอคอน
        let statusClass = '';
        let statusIcon = '';
        let statusText = '';
        
        switch (status) {
            case 'present':
                statusClass = 'present';
                statusIcon = 'fa-check-circle';
                statusText = 'มาเรียน';
                break;
            case 'late':
                statusClass = 'late';
                statusIcon = 'fa-clock';
                statusText = 'มาสาย';
                break;
            case 'leave':
                statusClass = 'leave';
                statusIcon = 'fa-clipboard';
                statusText = 'ลา';
                break;
            case 'absent':
                statusClass = 'absent';
                statusIcon = 'fa-times-circle';
                statusText = 'ขาดเรียน';
                break;
        }
        
        // อัพเดทการแสดงผลสถานะ
        const statusBadge = card.querySelector('.status-badge');
        if (statusBadge) {
            statusBadge.className = `status-badge ${statusClass}`;
            statusBadge.innerHTML = `<i class="fas ${statusIcon}"></i> ${statusText}`;
        }
        
        // อัพเดทหมายเหตุ (ถ้ามี)
        const studentName = card.getAttribute('data-name');
        const studentInfo = card.querySelector('.student-info');
        
        // อัพเดท onclick handler
        if (studentInfo) {
            studentInfo.setAttribute('onclick', `if (typeof editAttendance === 'function') { editAttendance(${studentId}, '${studentName.replace(/'/g, "\\'")}', '${status}', '${remarks.replace(/'/g, "\\'")}'); } else { alert('ฟังก์ชันยังไม่พร้อมใช้งาน กรุณารีเฟรชหน้า'); }`);
        }
        
        // อัพเดทหมายเหตุ
        const studentDetails = card.querySelector('.student-details');
        if (studentDetails) {
            const studentCode = studentDetails.querySelector('.student-code');
            const studentRemarks = studentDetails.querySelector('.student-remarks');
            
            if (remarks) {
                // แสดงหมายเหตุ
                if (studentRemarks) {
                    studentRemarks.textContent = remarks;
                } else {
                    // สร้างหมายเหตุใหม่
                    if (studentCode) {
                        studentCode.remove();
                    }
                    
                    const newRemarks = document.createElement('div');
                    newRemarks.className = 'student-remarks';
                    newRemarks.textContent = remarks;
                    studentDetails.appendChild(newRemarks);
                }
            } else {
                // ลบหมายเหตุ (ถ้ามี)
                if (studentRemarks) {
                    studentRemarks.remove();
                    
                    // ถ้าไม่มีรหัสนักเรียนให้เพิ่มกลับมา
                    if (!studentCode) {
                        const originalCard = document.querySelector(`#waitingTab .student-card[data-id="${studentId}"]`);
                        let codeText = '';
                        
                        if (originalCard) {
                            const originalCode = originalCard.querySelector('.student-code');
                            if (originalCode) {
                                codeText = originalCode.textContent;
                            }
                        }
                        
                        if (!codeText) {
                            // ถ้าไม่พบใน waiting tab ให้ใช้ค่าเริ่มต้น
                            codeText = 'รหัส: -';
                        }
                        
                        const newCode = document.createElement('div');
                        newCode.className = 'student-code';
                        newCode.textContent = codeText;
                        studentDetails.appendChild(newCode);
                    }
                }
            }
        }
        
        // อัพเดทเวลาเป็นเวลาปัจจุบัน
        const checkTime = card.querySelector('.check-time');
        if (checkTime) {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            checkTime.textContent = `${hours}:${minutes}`;
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการอัพเดทการ์ดนักเรียน:', error);
        showNotification('เกิดข้อผิดพลาดในการอัพเดทการ์ดนักเรียน: ' + error.message, 'error');
    }
}

/**
 * อัพเดทจำนวนนักเรียนในแต่ละแท็บ
 */
function updateStudentCounts() {
    const waitingCount = document.querySelectorAll('#waitingTab .student-card').length;
    const checkedCount = document.querySelectorAll('#checkedTab .student-card').length;
    
    // อัพเดทจำนวนในปุ่มแท็บ
    const waitingCountElement = document.querySelector('button[data-tab="waiting"] .count');
    const checkedCountElement = document.querySelector('button[data-tab="checked"] .count');
    
    if (waitingCountElement) {
        waitingCountElement.textContent = waitingCount;
    }
    
    if (checkedCountElement) {
        checkedCountElement.textContent = checkedCount;
    }
}

/**
 * อัพเดทสถิติการเช็คชื่อ
 * @param {string} status - สถานะที่เปลี่ยน
 * @param {boolean} isEdit - เป็นการแก้ไขหรือไม่
 */
function updateAttendanceStats(status, isEdit = false) {
    try {
        // อัพเดทสถิติตามสถานะ
        const presentCountElement = document.querySelector('.summary-item.present .summary-value');
        const lateCountElement = document.querySelector('.summary-item.late .summary-value');
        const leaveCountElement = document.querySelector('.summary-item.leave .summary-value');
        const absentCountElement = document.querySelector('.summary-item.absent .summary-value');
        const notCheckedCountElement = document.querySelector('.summary-item.not-checked .summary-value');
        
        if (presentCountElement && lateCountElement && leaveCountElement && absentCountElement && notCheckedCountElement) {
            // ดึงค่าปัจจุบัน
            let presentCount = parseInt(presentCountElement.textContent);
            let lateCount = parseInt(lateCountElement.textContent);
            let leaveCount = parseInt(leaveCountElement.textContent);
            let absentCount = parseInt(absentCountElement.textContent);
            let notCheckedCount = parseInt(notCheckedCountElement.textContent);
            
            // ถ้าเป็นการเช็คใหม่ (ไม่ใช่การแก้ไข)
            if (!isEdit) {
                // ลดจำนวนที่ยังไม่ได้เช็ค
                notCheckedCount--;
                
                // เพิ่มจำนวนตามสถานะ
                switch (status) {
                    case 'present':
                        presentCount++;
                        break;
                    case 'late':
                        lateCount++;
                        break;
                    case 'leave':
                        leaveCount++;
                        break;
                    case 'absent':
                        absentCount++;
                        break;
                }
            }
            
            // อัพเดทการแสดงผล
            presentCountElement.textContent = presentCount;
            lateCountElement.textContent = lateCount;
            leaveCountElement.textContent = leaveCount;
            absentCountElement.textContent = absentCount;
            notCheckedCountElement.textContent = notCheckedCount;
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการอัพเดทสถิติการเช็คชื่อ:', error);
        showNotification('เกิดข้อผิดพลาดในการอัพเดทสถิติ: ' + error.message, 'error');
    }
}

/**
 * อัพเดทสถิติการเช็คชื่อทั้งหมด
 * @param {string} status - สถานะที่เปลี่ยน
 * @param {number} count - จำนวนที่เปลี่ยน
 */
function updateAttendanceStatsAll(status, count) {
    try {
        // อัพเดทสถิติตามสถานะ
        const presentCountElement = document.querySelector('.summary-item.present .summary-value');
        const lateCountElement = document.querySelector('.summary-item.late .summary-value');
        const leaveCountElement = document.querySelector('.summary-item.leave .summary-value');
        const absentCountElement = document.querySelector('.summary-item.absent .summary-value');
        const notCheckedCountElement = document.querySelector('.summary-item.not-checked .summary-value');
        
        if (presentCountElement && lateCountElement && leaveCountElement && absentCountElement && notCheckedCountElement) {
            // ดึงค่าปัจจุบัน
            let presentCount = parseInt(presentCountElement.textContent);
            let lateCount = parseInt(lateCountElement.textContent);
            let leaveCount = parseInt(leaveCountElement.textContent);
            let absentCount = parseInt(absentCountElement.textContent);
            let notCheckedCount = parseInt(notCheckedCountElement.textContent);
            
            // ลดจำนวนที่ยังไม่ได้เช็ค
            notCheckedCount -= count;
            
            // เพิ่มจำนวนตามสถานะ
            switch (status) {
                case 'present':
                    presentCount += count;
                    break;
                case 'late':
                    lateCount += count;
                    break;
                case 'leave':
                    leaveCount += count;
                    break;
                case 'absent':
                    absentCount += count;
                    break;
            }
            
            // อัพเดทการแสดงผล
            presentCountElement.textContent = presentCount;
            lateCountElement.textContent = lateCount;
            leaveCountElement.textContent = leaveCount;
            absentCountElement.textContent = absentCount;
            notCheckedCountElement.textContent = notCheckedCount;
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการอัพเดทสถิติการเช็คชื่อทั้งหมด:', error);
        showNotification('เกิดข้อผิดพลาดในการอัพเดทสถิติ: ' + error.message, 'error');
    }
}



/**
 * แสดง Modal
 * @param {string} modalId - ID ของ Modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนพื้นหลัง
    }
}

/**
 * ปิด Modal
 * @param {string} modalId - ID ของ Modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = ''; // คืนค่าการเลื่อน
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showNotification(message, type = 'info') {
    try {
        // สร้างแถบแจ้งเตือน
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        // กำหนดไอคอนตามประเภท
        let icon = '';
        switch (type) {
            case 'success': icon = 'check-circle'; break;
            case 'warning': icon = 'exclamation-triangle'; break;
            case 'error': icon = 'exclamation-circle'; break;
            case 'info': default: icon = 'info-circle'; break;
        }
        
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        // เพิ่มไปยัง body
        document.body.appendChild(notification);
        
        // กำหนดการปิดเมื่อคลิก
        const closeButton = notification.querySelector('.notification-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                notification.remove();
            });
        }
        
        // กำหนดการปิดอัตโนมัติ
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.remove();
            }
        }, 5000);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงข้อความแจ้งเตือน:', error);
    }
}

/**
 * ดึงข้อความสถานะ
 * @param {string} status - สถานะการเช็คชื่อ
 * @returns {string} - ข้อความสถานะ
 */
function getStatusText(status) {
    switch (status) {
        case 'present': return 'มาเรียน';
        case 'late': return 'มาสาย';
        case 'leave': return 'ลา';
        case 'absent': return 'ขาดเรียน';
        default: return 'ไม่ระบุ';
    }
}