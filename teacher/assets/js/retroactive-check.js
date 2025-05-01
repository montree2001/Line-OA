/**
 * retroactive-check.js - จัดการการเช็คชื่อย้อนหลัง
 * 
 * ฟังก์ชันหลัก:
 * - แสดงหน้าต่างเช็คชื่อย้อนหลัง
 * - แสดงป้ายบอกว่ากำลังเช็คชื่อย้อนหลัง
 * - จัดการการบันทึกเช็คชื่อย้อนหลัง
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log("โหลด retroactive-check.js สำเร็จ");

    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    const dateSelector = document.getElementById('dateSelect');
    if (!dateSelector) return;

    const today = new Date().toISOString().split('T')[0];
    const isRetroactive = dateSelector.value !== today;

    // เก็บค่าว่าเป็นการเช็คชื่อย้อนหลังไว้ในตัวแปรระดับโค้ด
    window.isRetroactiveMode = isRetroactive;

    // แสดงป้ายบอกการเช็คชื่อย้อนหลัง
    if (isRetroactive) {
        showRetroactiveBadge();
    }

    // เพิ่ม Event Listener สำหรับการเปลี่ยนวันที่
    dateSelector.addEventListener('change', function() {
        const newDate = this.value;
        const isNowRetroactive = newDate !== today;

        // อัพเดทสถานะการเช็คชื่อย้อนหลัง
        window.isRetroactiveMode = isNowRetroactive;

        // แสดงหรือซ่อนป้ายเช็คชื่อย้อนหลัง
        if (isNowRetroactive) {
            showRetroactiveBadge();
        } else {
            hideRetroactiveBadge();
        }
    });

    // แทนที่ปุ่มการเช็คชื่อเพื่อให้รองรับการเช็คชื่อย้อนหลัง
    if (isRetroactive) {
        setupRetroactiveButtons();
    }

    // ตรวจสอบและแก้ไขฟังก์ชันที่เกี่ยวข้องเพื่อให้รองรับการเช็คชื่อย้อนหลัง
    extendAttendanceFunctions();
});

/**
 * แสดงป้ายบอกว่ากำลังเช็คชื่อย้อนหลัง
 */
function showRetroactiveBadge() {
    // ตรวจสอบว่ามีป้ายอยู่แล้วหรือไม่
    let retroBadge = document.querySelector('.retroactive-badge');
    if (retroBadge) {
        retroBadge.style.display = 'inline-flex';
        return;
    }

    // สร้างป้ายบอกการเช็คชื่อย้อนหลัง
    retroBadge = document.createElement('div');
    retroBadge.className = 'retroactive-badge';
    retroBadge.innerHTML = '<i class="fas fa-history"></i> เช็คชื่อย้อนหลัง';
    retroBadge.style.display = 'inline-flex';
    retroBadge.style.alignItems = 'center';
    retroBadge.style.backgroundColor = '#fff3e0';
    retroBadge.style.color = '#e65100';
    retroBadge.style.padding = '4px 8px';
    retroBadge.style.borderRadius = '4px';
    retroBadge.style.fontSize = '14px';
    retroBadge.style.fontWeight = '500';
    retroBadge.style.marginLeft = '8px';

    // เพิ่ม Animation pulse
    retroBadge.style.animation = 'pulse 2s infinite';
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(255, 152, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
        }
    `;
    document.head.appendChild(style);

    // เพิ่มป้ายเข้าไปในหน้า
    const dateContainer = document.querySelector('.date-selector');
    if (dateContainer) {
        dateContainer.appendChild(retroBadge);
    }

    // เพิ่มคำเตือนเช็คชื่อย้อนหลัง
    addRetroactiveWarning();
}

/**
 * ซ่อนป้ายเช็คชื่อย้อนหลัง
 */
function hideRetroactiveBadge() {
    const retroBadge = document.querySelector('.retroactive-badge');
    if (retroBadge) {
        retroBadge.style.display = 'none';
    }

    // ลบคำเตือนเช็คชื่อย้อนหลัง
    removeRetroactiveWarning();
}

/**
 * เพิ่มคำเตือนเช็คชื่อย้อนหลัง
 */
function addRetroactiveWarning() {
    // ตรวจสอบว่ามีคำเตือนอยู่แล้วหรือไม่
    if (document.querySelector('.retroactive-warning-box')) {
        return;
    }

    // สร้างกล่องคำเตือน
    const warningBox = document.createElement('div');
    warningBox.className = 'retroactive-warning-box';
    warningBox.style.backgroundColor = '#fff3e0';
    warningBox.style.borderLeft = '4px solid #ff9800';
    warningBox.style.padding = '12px 16px';
    warningBox.style.marginBottom = '16px';
    warningBox.style.borderRadius = '8px';
    warningBox.style.display = 'flex';
    warningBox.style.alignItems = 'center';
    warningBox.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';

    const icon = document.createElement('div');
    icon.style.color = '#e65100';
    icon.style.fontSize = '24px';
    icon.style.marginRight = '16px';
    icon.innerHTML = '<i class="fas fa-exclamation-circle"></i>';

    const content = document.createElement('div');
    content.style.flex = '1';
    content.style.color = '#795548';

    const heading = document.createElement('strong');
    heading.style.color = '#e65100';
    heading.style.display = 'block';
    heading.style.marginBottom = '5px';
    heading.textContent = 'คุณกำลังเช็คชื่อย้อนหลัง';

    const text = document.createTextNode('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลังทุกครั้ง เช่น ใบรับรองแพทย์, หนังสือลา, การแก้ไขข้อมูลผิดพลาด ฯลฯ');

    content.appendChild(heading);
    content.appendChild(text);
    warningBox.appendChild(icon);
    warningBox.appendChild(content);

    // เพิ่มคำเตือนเข้าไปในหน้า
    const container = document.querySelector('.attendance-container');
    if (container) {
        container.insertBefore(warningBox, container.firstChild);
    }
}

/**
 * ลบคำเตือนเช็คชื่อย้อนหลัง
 */
function removeRetroactiveWarning() {
    const warningBox = document.querySelector('.retroactive-warning-box');
    if (warningBox) {
        warningBox.remove();
    }
}

/**
 * แทนที่ปุ่มเช็คชื่อเพื่อให้รองรับการเช็คชื่อย้อนหลัง
 */
function setupRetroactiveButtons() {
    // แทนที่ปุ่มเช็คชื่อใน waitingTab
    const actionButtons = document.querySelectorAll('#waitingTab .action-button');

    actionButtons.forEach(button => {
        // เก็บค่าเดิมของ onclick
        const originalOnClick = button.getAttribute('onclick');

        if (originalOnClick && originalOnClick.includes('markAttendance')) {
            // ดึงพารามิเตอร์จาก onClick เดิม
            const matches = originalOnClick.match(/markAttendance\(this,\s*['"]([^'"]+)['"]\s*,\s*(\d+)\s*\)/);

            if (matches && matches.length >= 3) {
                const status = matches[1];
                const studentId = matches[2];

                // แทนที่ onclick ด้วยฟังก์ชันที่เปิด Modal เช็คชื่อย้อนหลัง
                button.setAttribute('onclick', `showRetroactiveModal(this, '${status}', ${studentId})`);
            }
        }
    });

    // แทนที่ฟังก์ชัน showDetailAttendanceModal ถ้ามี
    if (typeof window.showDetailAttendanceModal === 'function') {
        const originalShowDetail = window.showDetailAttendanceModal;

        window.showDetailAttendanceModal = function(studentId, studentName) {
            // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
            if (window.isRetroactiveMode) {
                // เปิด Modal เช็คชื่อย้อนหลังแทน
                openRetroactiveDetailModal(studentId, studentName);
            } else {
                // เรียกฟังก์ชันเดิม
                originalShowDetail(studentId, studentName);
            }
        };
    }
}

/**
 * แก้ไขฟังก์ชันที่เกี่ยวข้องกับการเช็คชื่อ
 */
function extendAttendanceFunctions() {
    // แทนที่ฟังก์ชัน markAttendance ถ้ามี
    if (typeof window.markAttendance === 'function') {
        const originalMarkAttendance = window.markAttendance;

        window.markAttendance = function(button, status, studentId) {
            // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
            if (window.isRetroactiveMode) {
                // เปิด Modal เช็คชื่อย้อนหลังแทน
                showRetroactiveModal(button, status, studentId);
            } else {
                // เรียกฟังก์ชันเดิม
                originalMarkAttendance(button, status, studentId);
            }
        };
    }

    // แทนที่ฟังก์ชัน confirmDetailAttendance ถ้ามี
    if (typeof window.confirmDetailAttendance === 'function') {
        const originalConfirmDetail = window.confirmDetailAttendance;

        window.confirmDetailAttendance = function() {
            // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
            if (window.isRetroactiveMode) {
                // ตรวจสอบหมายเหตุย้อนหลัง
                const retroactiveNoteInput = document.getElementById('retroactiveNote');
                if (retroactiveNoteInput && retroactiveNoteInput.value.trim() === '') {
                    alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
                    retroactiveNoteInput.focus();
                    return;
                }

                // ใช้ API เช็คชื่อย้อนหลัง
                confirmRetroactiveDetailAttendance();
            } else {
                // เรียกฟังก์ชันเดิม
                originalConfirmDetail();
            }
        };
    }
}

/**
 * แสดง Modal เช็คชื่อย้อนหลัง
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเช็คชื่อ (present/absent/late/leave)
 * @param {number} studentId - รหัสนักเรียน
 */
function showRetroactiveModal(button, status, studentId) {
    // ตรวจสอบว่ามี Modal ที่สร้างไว้แล้วหรือไม่
    let retroModal = document.getElementById('retroactiveModal');

    if (!retroModal) {
        // สร้าง Modal เช็คชื่อย้อนหลัง
        retroModal = createRetroactiveModal();

        // ให้เวลา DOM อัพเดทก่อนดำเนินการต่อ
        setTimeout(() => {
            // ตั้งค่าข้อมูลในโมดัลหลังจากที่มั่นใจว่าสร้างเสร็จแล้ว
            setRetroactiveModalData(button, status, studentId);
        }, 100);
    } else {
        // ถ้ามีโมดัลอยู่แล้ว ตั้งค่าข้อมูลได้ทันที
        setRetroactiveModalData(button, status, studentId);
    }

    // แสดง Modal
    showModal('retroactiveModal');
}

/**
 * ตั้งค่าข้อมูลในโมดัลเช็คชื่อย้อนหลัง
 * @param {HTMLElement} button - ปุ่มที่กดเพื่อเปิด Modal
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {number} studentId - รหัสนักเรียน
 */
function setRetroactiveModalData(button, status, studentId) {
    // ดึงชื่อนักเรียน
    const studentCard = button.closest('.student-card');
    let studentName = 'นักเรียน';

    if (studentCard) {
        studentName = studentCard.getAttribute('data-name') || 'นักเรียน';
    }

    // ตรวจสอบว่าโมดัลถูกสร้างแล้วหรือยัง
    const titleElement = document.getElementById('retroactiveModalTitle');
    const studentIdElement = document.getElementById('retroactiveStudentId');
    const studentNameElement = document.getElementById('retroactiveStudentName');
    const reasonElement = document.getElementById('retroactiveReason');
    const remarksElement = document.getElementById('retroactiveRemarks');

    // ตั้งค่าเฉพาะส่วนที่มีอยู่
    if (titleElement) {
        titleElement.textContent = 'เช็คชื่อย้อนหลัง: ' + studentName;
    }

    if (studentIdElement) {
        studentIdElement.value = studentId;
    }

    if (studentNameElement) {
        studentNameElement.value = studentName;
    }

    // กำหนดสถานะเริ่มต้น
    const statusRadio = document.querySelector(`input[name="retroactiveStatus"][value="${status}"]`);
    if (statusRadio) {
        statusRadio.checked = true;
    }

    // ล้างค่าเดิม
    if (reasonElement) {
        reasonElement.value = '';
    }

    if (remarksElement) {
        remarksElement.value = '';
    }
}

/**
 * เปิด Modal แสดงรายละเอียดการเช็คชื่อย้อนหลัง
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function openRetroactiveDetailModal(studentId, studentName) {
    // ตรวจสอบว่าได้รับพารามิเตอร์ที่จำเป็นหรือไม่
    if (!studentId || !studentName) {
        console.error('openRetroactiveDetailModal: ไม่พบข้อมูล studentId หรือ studentName');
        showNotification('เกิดข้อผิดพลาด: ไม่พบข้อมูลนักเรียน', 'error');
        return;
    }

    // ตรวจสอบว่ามี Modal หรือยัง
    let retroModal = document.getElementById('retroactiveModal');

    if (!retroModal) {
        // สร้าง Modal
        retroModal = createRetroactiveModal();

        // ให้เวลา DOM อัพเดทก่อนดำเนินการต่อ
        setTimeout(() => {
            // ตั้งค่าข้อมูลในโมดัลหลังจากที่มั่นใจว่าสร้างเสร็จแล้ว
            setRetroactiveDetailModalData(studentId, studentName);
        }, 100);
    } else {
        // ถ้ามีโมดัลอยู่แล้ว ตั้งค่าข้อมูลได้ทันที
        setRetroactiveDetailModalData(studentId, studentName);
    }

    // แสดง Modal
    showModal('retroactiveModal');
}

/**
 * ตั้งค่าข้อมูลในโมดัลเช็คชื่อย้อนหลัง (กรณีเปิดจากปุ่มละเอียด)
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} studentName - ชื่อนักเรียน
 */
function setRetroactiveDetailModalData(studentId, studentName) {
    try {
        // ตรวจสอบว่าได้รับพารามิเตอร์ที่จำเป็นหรือไม่
        if (!studentId || !studentName) {
            console.error('setRetroactiveDetailModalData: ไม่พบข้อมูล studentId หรือ studentName');
            return;
        }

        // ตรวจสอบว่าโมดัลถูกสร้างแล้วหรือยัง
        const titleElement = document.getElementById('retroactiveModalTitle');
        const studentIdElement = document.getElementById('retroactiveStudentId');
        const studentNameElement = document.getElementById('retroactiveStudentName');
        const reasonElement = document.getElementById('retroactiveReason');
        const remarksElement = document.getElementById('retroactiveRemarks');

        // ตั้งค่าเฉพาะส่วนที่มีอยู่
        if (titleElement) {
            titleElement.textContent = 'เช็คชื่อย้อนหลัง: ' + studentName;
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveModalTitle');
        }

        if (studentIdElement) {
            studentIdElement.value = studentId;
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveStudentId');
        }

        if (studentNameElement) {
            studentNameElement.value = studentName;
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveStudentName');
        }

        // กำหนดสถานะเริ่มต้น
        const statusRadio = document.querySelector('input[name="retroactiveStatus"][value="present"]');
        if (statusRadio) {
            statusRadio.checked = true;
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveStatus radio button');
        }

        // ล้างค่าเดิม
        if (reasonElement) {
            reasonElement.value = '';
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveReason');
        }

        if (remarksElement) {
            remarksElement.value = '';
        } else {
            console.warn('ไม่พบอิลิเมนต์ retroactiveRemarks');
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดใน setRetroactiveDetailModalData:', error);
    }
}

/**
 * ยืนยันการเช็คชื่อย้อนหลัง
 */
function confirmRetroactiveAttendance() {
    try {
        // ดึงข้อมูลจาก Modal และเพิ่มการตรวจสอบตัวแปรเป็น null
        const studentIdElement = document.getElementById('retroactiveStudentId');
        const studentNameElement = document.getElementById('retroactiveStudentName');
        const reasonElement = document.getElementById('retroactiveReason');
        const remarksElement = document.getElementById('retroactiveRemarks');
        const statusElement = document.querySelector('input[name="retroactiveStatus"]:checked');

        // ตรวจสอบว่า elements มีอยู่จริง
        if (!studentIdElement || !reasonElement || !statusElement) {
            console.error('ไม่พบอิลิเมนต์ที่จำเป็นในฟอร์ม retroactive');
            alert('เกิดข้อผิดพลาด: ไม่พบข้อมูลที่จำเป็น กรุณารีเฟรชหน้าและลองใหม่');
            return;
        }

        const studentId = studentIdElement.value;
        const studentName = studentNameElement ? studentNameElement.value : 'นักเรียน';
        const reason = reasonElement.value.trim();
        const remarks = remarksElement ? remarksElement.value.trim() : '';
        const status = statusElement.value;

        // ตรวจสอบว่ามีเหตุผลหรือไม่
        if (!reason) {
            alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
            reasonElement.focus();
            return;
        }

        // ปิด Modal
        closeModal('retroactiveModal');

        // แสดงข้อความกำลังดำเนินการ
        showNotification('กำลังบันทึกการเช็คชื่อย้อนหลัง...', 'info');

        // เตรียมข้อมูลสำหรับส่ง API
        const classSelect = document.getElementById('classSelect');
        const dateSelect = document.getElementById('dateSelect');
        const classId = window.currentClassId || (classSelect ? classSelect.value : null);
        const date = window.checkDate || (dateSelect ? dateSelect.value : null);

        if (!classId || !date) {
            console.error('ไม่พบค่า classId หรือ date');
            showNotification('เกิดข้อผิดพลาด: ไม่พบข้อมูลชั้นเรียนหรือวันที่', 'error');
            return;
        }

        // ส่งข้อมูลไปยัง API สำหรับเช็คชื่อย้อนหลัง
        fetch('api/retroactive_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    status: status,
                    class_id: classId,
                    date: date,
                    retroactive_reason: reason,
                    remarks: remarks
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('เซิร์ฟเวอร์ตอบกลับผิดพลาด: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // แสดงข้อความสำเร็จ
                    showNotification(`บันทึกการเช็คชื่อย้อนหลัง "${getStatusText(status)}" สำเร็จ`, 'success');

                    // รีเฟรชหน้าเว็บหลังจาก 1.5 วินาที
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในฟังก์ชัน confirmRetroactiveAttendance:', error);
        showNotification('เกิดข้อผิดพลาดในระบบ กรุณารีเฟรชหน้าและลองใหม่', 'error');
    }
}

/**
 * ยืนยันการเช็คชื่อย้อนหลังในรายละเอียด
 */
function confirmRetroactiveDetailAttendance() {
    try {
        // ตรวจสอบหมายเหตุย้อนหลัง
        const retroactiveNoteInput = document.getElementById('retroactiveNote');
        if (!retroactiveNoteInput || retroactiveNoteInput.value.trim() === '') {
            alert('กรุณาระบุเหตุผลการเช็คชื่อย้อนหลัง');
            if (retroactiveNoteInput) retroactiveNoteInput.focus();
            return;
        }

        // ดึงข้อมูลจาก Modal และตรวจสอบ null
        const studentIdElement = document.getElementById('studentIdDetail');
        const statusElement = document.querySelector('input[name="attendanceStatus"]:checked');
        const remarksElement = document.getElementById('attendanceRemarks');

        // ตรวจสอบว่า elements มีอยู่จริง
        if (!studentIdElement || !statusElement) {
            console.error('ไม่พบอิลิเมนต์ที่จำเป็นในฟอร์ม retroactive detail');
            alert('เกิดข้อผิดพลาด: ไม่พบข้อมูลที่จำเป็น กรุณารีเฟรชหน้าและลองใหม่');
            return;
        }

        const studentId = studentIdElement.value;
        const status = statusElement.value;
        const remarks = remarksElement ? remarksElement.value.trim() : '';
        const reason = retroactiveNoteInput.value.trim();

        // ปิด Modal
        closeModal('attendanceDetailModal');

        // แสดงข้อความกำลังดำเนินการ
        showNotification('กำลังบันทึกการเช็คชื่อย้อนหลัง...', 'info');

        // เตรียมข้อมูลสำหรับส่ง API
        const classSelect = document.getElementById('classSelect');
        const dateSelect = document.getElementById('dateSelect');
        const classId = window.currentClassId || (classSelect ? classSelect.value : null);
        const date = window.checkDate || (dateSelect ? dateSelect.value : null);

        if (!classId || !date) {
            console.error('ไม่พบค่า classId หรือ date');
            showNotification('เกิดข้อผิดพลาด: ไม่พบข้อมูลชั้นเรียนหรือวันที่', 'error');
            return;
        }

        // ส่งข้อมูลไปยัง API สำหรับเช็คชื่อย้อนหลัง
        fetch('api/retroactive_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    status: status,
                    class_id: classId,
                    date: date,
                    retroactive_reason: reason,
                    remarks: remarks
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('เซิร์ฟเวอร์ตอบกลับผิดพลาด: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // แสดงข้อความสำเร็จ
                    showNotification(`บันทึกการเช็คชื่อย้อนหลัง "${getStatusText(status)}" สำเร็จ`, 'success');

                    // รีเฟรชหน้าเว็บหลังจาก 1.5 วินาที
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในฟังก์ชัน confirmRetroactiveDetailAttendance:', error);
        showNotification('เกิดข้อผิดพลาดในระบบ กรุณารีเฟรชหน้าและลองใหม่', 'error');
    }
}

/**
 * จัดการย้ายการ์ดนักเรียน (กรณีไม่มีฟังก์ชัน moveToCheckedTab)
 * @param {HTMLElement} studentCard - การ์ดนักเรียน
 * @param {number} studentId - รหัสนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {object} studentData - ข้อมูลนักเรียน
 */
function handleMoveStudentCard(studentCard, studentId, status, studentData) {
    // ลบการ์ดจากแท็บเดิม
    studentCard.remove();

    // ตรวจสอบว่ามีรายการในแท็บเดิมเหลืออยู่หรือไม่
    const waitingTab = document.getElementById('waitingTab');
    if (!waitingTab) return;

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

    // ตรวจสอบแท็บที่เช็คแล้ว
    const checkedTab = document.getElementById('checkedTab');
    if (!checkedTab) return;

    // ทำความสะอาดแท็บเป้าหมายหากมีข้อความว่าง
    const emptyState = checkedTab.querySelector('.empty-state');
    if (emptyState) {
        emptyState.remove();
    }

    // ตรวจสอบหรือสร้าง student-list
    let studentList = checkedTab.querySelector('.student-list');
    if (!studentList) {
        studentList = document.createElement('div');
        studentList.className = 'student-list';
        checkedTab.appendChild(studentList);
    }

    // สร้างการ์ดใหม่
    const newCard = document.createElement('div');
    newCard.className = `student-card ${status}-card`;
    newCard.setAttribute('data-id', studentId);
    newCard.setAttribute('data-name', studentData.name);
    newCard.setAttribute('data-status', status);

    if (studentData.attendance_id) {
        newCard.setAttribute('data-attendance-id', studentData.attendance_id);
    }

    // ปรับแต่ง HTML ของการ์ดตามข้อมูลที่มี
    newCard.innerHTML = `
        <div class="student-number">?</div>
        
        <div class="student-info">
            <div class="student-avatar">${studentData.name.charAt(0)}</div>
            
            <div class="student-details">
                <div class="student-name">${studentData.name}</div>
                <div class="student-code">รหัส: ${studentData.code || '-'}</div>
            </div>
        </div>
        
        <div class="student-status-info">
            <div class="status-badge ${status}">
                <i class="fas ${getStatusIcon(status)}"></i> ${getStatusText(status)}
            </div>
            
            <div class="check-details">
                <div class="check-time">${studentData.time_checked || ''}</div>
                <div class="check-method">ครู</div>
            </div>
        </div>
    `;

    // เพิ่มแท็กบอกว่าเป็นการเช็คชื่อย้อนหลัง
    const retroTag = document.createElement('div');
    retroTag.className = 'retroactive-tag';
    retroTag.textContent = 'ย้อนหลัง';
    retroTag.style.position = 'absolute';
    retroTag.style.top = '5px';
    retroTag.style.right = '5px';
    retroTag.style.backgroundColor = '#fff3e0';
    retroTag.style.color = '#e65100';
    retroTag.style.fontSize = '10px';
    retroTag.style.padding = '2px 6px';
    retroTag.style.borderRadius = '10px';

    newCard.style.position = 'relative';
    newCard.appendChild(retroTag);

    // เพิ่มการ์ดใหม่ลงในรายการ
    studentList.appendChild(newCard);
}

/**
 * อัพเดทการ์ดที่มีอยู่แล้ว (กรณีไม่มีฟังก์ชัน updateExistingCard)
 * @param {HTMLElement} card - การ์ดนักเรียน
 * @param {string} status - สถานะการเช็คชื่อ
 * @param {object} studentData - ข้อมูลนักเรียน
 */
function handleUpdateExistingCard(card, status, studentData) {
    // ปรับปรุงคลาสการ์ด
    card.className = `student-card ${status}-card`;
    card.setAttribute('data-status', status);

    if (studentData.attendance_id) {
        card.setAttribute('data-attendance-id', studentData.attendance_id);
    }

    // อัพเดทสถานะ
    const statusBadge = card.querySelector('.status-badge');
    if (statusBadge) {
        statusBadge.className = `status-badge ${status}`;
        statusBadge.innerHTML = `<i class="fas ${getStatusIcon(status)}"></i> ${getStatusText(status)}`;
    }

    // อัพเดทเวลา
    const checkTime = card.querySelector('.check-time');
    if (checkTime && studentData.time_checked) {
        checkTime.textContent = studentData.time_checked;
    }

    // เพิ่มแท็กบอกว่าเป็นการเช็คชื่อย้อนหลัง
    let retroTag = card.querySelector('.retroactive-tag');

    if (!retroTag) {
        retroTag = document.createElement('div');
        retroTag.className = 'retroactive-tag';
        retroTag.textContent = 'ย้อนหลัง';
        retroTag.style.position = 'absolute';
        retroTag.style.top = '5px';
        retroTag.style.right = '5px';
        retroTag.style.backgroundColor = '#fff3e0';
        retroTag.style.color = '#e65100';
        retroTag.style.fontSize = '10px';
        retroTag.style.padding = '2px 6px';
        retroTag.style.borderRadius = '10px';

        card.style.position = 'relative';
        card.appendChild(retroTag);
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showNotification(message, type = 'info') {
    // ตรวจสอบว่ามีฟังก์ชัน showNotification อยู่แล้วหรือไม่
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }

    // สร้างแถบแจ้งเตือน
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;

    // กำหนดไอคอนตามประเภท
    let icon = '';
    switch (type) {
        case 'success':
            icon = 'check-circle';
            break;
        case 'warning':
            icon = 'exclamation-triangle';
            break;
        case 'error':
            icon = 'exclamation-circle';
            break;
        case 'info':
        default:
            icon = 'info-circle';
            break;
    }

    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${icon}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
    `;

    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.backgroundColor = 'white';
    notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    notification.style.borderRadius = '8px';
    notification.style.padding = '12px 16px';
    notification.style.display = 'flex';
    notification.style.justifyContent = 'space-between';
    notification.style.alignItems = 'center';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '250px';
    notification.style.maxWidth = '350px';

    // สไตล์ตามประเภท
    switch (type) {
        case 'success':
            notification.style.borderLeft = '4px solid #4caf50';
            notification.querySelector('i').style.color = '#4caf50';
            break;
        case 'warning':
            notification.style.borderLeft = '4px solid #ff9800';
            notification.querySelector('i').style.color = '#ff9800';
            break;
        case 'error':
            notification.style.borderLeft = '4px solid #f44336';
            notification.querySelector('i').style.color = '#f44336';
            break;
        case 'info':
            notification.style.borderLeft = '4px solid #2196f3';
            notification.querySelector('i').style.color = '#2196f3';
            break;
    }

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
}

/**
 * แสดง Modal
 * @param {string} modalId - ID ของ Modal
 */
function showModal(modalId) {
    // ตรวจสอบว่ามีฟังก์ชัน showModal อยู่แล้วหรือไม่
    if (typeof window.showModal === 'function') {
        window.showModal(modalId);
        return;
    }

    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

/**
 * ปิด Modal
 * @param {string} modalId - ID ของ Modal
 */
function closeModal(modalId) {
    // ตรวจสอบว่ามีฟังก์ชัน closeModal อยู่แล้วหรือไม่
    if (typeof window.closeModal === 'function') {
        window.closeModal(modalId);
        return;
    }

    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

/**
 * ดึงข้อความสถานะ
 * @param {string} status - สถานะการเช็คชื่อ
 * @returns {string} - ข้อความสถานะ
 */
function getStatusText(status) {
    switch (status) {
        case 'present':
            return 'มาเรียน';
        case 'late':
            return 'มาสาย';
        case 'leave':
            return 'ลา';
        case 'absent':
            return 'ขาดเรียน';
        default:
            return 'ไม่ระบุ';
    }
}

/**
 * ดึงไอคอนสถานะ
 * @param {string} status - สถานะการเช็คชื่อ
 * @returns {string} - คลาสไอคอน
 */
function getStatusIcon(status) {
    switch (status) {
        case 'present':
            return 'fa-check-circle';
        case 'late':
            return 'fa-clock';
        case 'leave':
            return 'fa-clipboard';
        case 'absent':
            return 'fa-times-circle';
        default:
            return 'fa-question-circle';
    }
}

/**
 * สร้าง Modal สำหรับเช็คชื่อย้อนหลัง
 * @returns {HTMLElement} - Modal ที่สร้าง
 */
function createRetroactiveModal() {
    // สร้าง Modal
    const retroModal = document.createElement('div');
    retroModal.id = 'retroactiveModal';
    retroModal.className = 'modal';

    // หาวันที่เช็คชื่อย้อนหลัง
    const dateSelect = document.getElementById('dateSelect');
    const retroactiveDate = dateSelect ? dateSelect.value : new Date().toISOString().split('T')[0];

    // แปลงให้เป็นรูปแบบแสดงผล (DD/MM/YYYY)
    const displayDate = formatDateThai(retroactiveDate);

    // HTML สำหรับ Modal
    retroModal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="retroactiveModalTitle">เช็คชื่อย้อนหลัง</h3>
                    <button type="button" class="close-btn" onclick="closeModal('retroactiveModal')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div style="background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 10px; margin-bottom: 15px; display: flex; align-items: center;">
                        <i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-right: 10px; font-size: 20px;"></i>
                        <div>
                            <p style="margin: 0; color: #e65100; font-weight: 500;">คุณกำลังทำการเช็คชื่อย้อนหลังสำหรับวันที่ <span id="retroactiveDateDisplay">${displayDate}</span></p>
                            <p style="margin: 5px 0 0 0; color: #795548; font-size: 14px;">การเช็คชื่อย้อนหลังจำเป็นต้องระบุเหตุผลที่ชัดเจน และจะถูกบันทึกประวัติการเช็ค</p>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="retroactiveReason" style="display: block; margin-bottom: 8px; font-weight: 500;">
                            <strong>เหตุผลการเช็คชื่อย้อนหลัง</strong>
                            <span style="color: #f44336;">*</span>
                        </label>
                        <textarea id="retroactiveReason" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; min-height: 80px;" 
                            placeholder="เช่น ใบรับรองแพทย์, หนังสือลาที่ได้รับล่าช้า, การแก้ไขข้อมูลผิดพลาด ฯลฯ" required></textarea>
                        <div style="color: #757575; font-size: 12px; margin-top: 4px;">เหตุผลจะถูกบันทึกในประวัติระบบ</div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <label for="retroactiveStatus" style="display: block; margin-bottom: 8px; font-weight: 500;">
                            <strong>สถานะการเช็คชื่อ</strong>
                        </label>
                        <div class="status-options">
                            <label class="status-option">
                                <input type="radio" name="retroactiveStatus" value="present" checked>
                                <span class="status-label present">
                                    <i class="fas fa-check-circle"></i> มาเรียน
                                </span>
                            </label>
                            
                            <label class="status-option">
                                <input type="radio" name="retroactiveStatus" value="late">
                                <span class="status-label late">
                                    <i class="fas fa-clock"></i> มาสาย
                                </span>
                            </label>
                            
                            <label class="status-option">
                                <input type="radio" name="retroactiveStatus" value="leave">
                                <span class="status-label leave">
                                    <i class="fas fa-clipboard"></i> ลา
                                </span>
                            </label>
                            
                            <label class="status-option">
                                <input type="radio" name="retroactiveStatus" value="absent">
                                <span class="status-label absent">
                                    <i class="fas fa-times-circle"></i> ขาดเรียน
                                </span>
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;" id="retroactiveRemarksContainer">
                        <label for="retroactiveRemarks" style="display: block; margin-bottom: 8px; font-weight: 500;">
                            <strong>หมายเหตุเพิ่มเติม</strong>
                        </label>
                        <textarea id="retroactiveRemarks" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical; min-height: 60px;" 
                            placeholder="หมายเหตุเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                    
                    <input type="hidden" id="retroactiveStudentId" value="">
                    <input type="hidden" id="retroactiveStudentName" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn secondary" onclick="closeModal('retroactiveModal')">ยกเลิก</button>
                    <button type="button" class="btn primary" onclick="confirmRetroactiveAttendance()">บันทึก</button>
                </div>
            </div>
        </div>
    `;

    // เพิ่ม Modal เข้าไปในหน้า
    document.body.appendChild(retroModal);

    return retroModal;
}

/**
 * แปลงรูปแบบวันที่เป็นรูปแบบไทย (วัน/เดือน/ปี)
 * @param {string} dateString - วันที่ในรูปแบบ YYYY-MM-DD
 * @returns {string} - วันที่ในรูปแบบ DD/MM/YYYY
 */
function formatDateThai(dateString) {
    if (!dateString) return '';

    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;

    // แปลงปีให้เป็น พ.ศ.
    const year = parseInt(parts[0]) + 543;

    // คืนค่าในรูปแบบ DD/MM/YYYY
    return `${parts[2]}/${parts[1]}/${year}`;
}