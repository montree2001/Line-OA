/**
 * teacher-check-attendance.js - สคริปต์สำหรับหน้าเช็คชื่อนักเรียน
 * 
 * ฟังก์ชันหลัก:
 * - จัดการการเช็คชื่อนักเรียน (มา/ขาด/สาย/ลา)
 * - อัพเดทสถิติการเช็คชื่อ
 * - บันทึกข้อมูลการเช็คชื่อ
 * - สร้างรหัส PIN สำหรับการเช็คชื่อ
 */

// ตัวแปร Global สำหรับเก็บสถานะการเช็คชื่อ
let attendanceData = {
    class_id: currentClassId,
    date: checkDate,
    students: []
/**
 * บันทึกข้อมูลในตัวแปร attendanceData
 * @param {string} studentId - ID ของนักเรียน
 * @param {string} status - สถานะการเข้าแถว (present/late/leave/absent)
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function saveAttendanceData(studentId, status, remarks) {
    // ตรวจสอบข้อมูล
    if (!studentId) {
        console.error('ไม่พบรหัสนักเรียน');
        return;
    }
    
    const studentIndex = attendanceData.students.findIndex(student => student.student_id === studentId);
    
    if (studentIndex >= 0) {
        // อัพเดทข้อมูลเดิม
        attendanceData.students[studentIndex].status = status;
        if (remarks) {
            attendanceData.students[studentIndex].remarks = remarks;
        }
    } else {
        // เพิ่มข้อมูลใหม่
        attendanceData.students.push({
            student_id: studentId,
            status: status,
            remarks: remarks || ''
        });
    }
    
    console.log('บันทึกข้อมูล:', { studentId, status, remarks });
}

/**
 * อัพเดทจำนวนการเข้าแถว
 */
function updateAttendanceCounters() {
    try {
        // นับจำนวนนักเรียนทั้งหมด
        const totalStudentsCount = parseInt(document.querySelector('.stat-card.total .value').textContent);
        
        // นับจำนวนนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
        
        // คำนวณจำนวนนักเรียนที่ได้รับการเช็คชื่อแล้ว
        let presentCount = 0;
        let absentCount = 0;
        
        // ดึงข้อมูลจาก attendanceData
        attendanceData.students.forEach(student => {
            if (student.status === 'present' || student.status === 'late' || student.status === 'leave') {
                presentCount++;
            } else if (student.status === 'absent') {
                absentCount++;
            }
        });
        
        // หาจำนวนจากแท็บเช็คชื่อแล้ว สำหรับข้อมูลที่อาจจะยังไม่ได้อยู่ใน attendanceData
        document.querySelectorAll('#checked-tab .student-item').forEach(item => {
            const status = item.getAttribute('data-status');
            const studentId = item.getAttribute('data-id');
            
            // ตรวจสอบว่ามีข้อมูลในตัวแปร attendanceData หรือไม่
            const exists = attendanceData.students.some(student => student.student_id === studentId);
            
            if (!exists) {
                if (status === 'present' || status === 'late' || status === 'leave') {
                    presentCount++;
                } else if (status === 'absent') {
                    absentCount++;
                }
            }
        });
        
        // อัพเดทสถิติในหน้าเว็บ
        document.getElementById('present-count').textContent = presentCount;
        document.getElementById('absent-count').textContent = absentCount;
        document.getElementById('not-checked-count').textContent = uncheckedCount;
        
        // อัพเดทจำนวนในแท็บ
        updateTabCounts();
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการอัพเดทจำนวนการเข้าแถว:', error);
    }
}

/**
 * อัพเดทจำนวนในแท็บ
 */
function updateTabCounts() {
    try {
        // นับจำนวนนักเรียนในแต่ละแท็บ
        const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
        const checkedCount = document.querySelectorAll('#checked-tab .student-item').length;
        
        // อัพเดทจำนวนในปุ่มแท็บ
        const uncheckedButton = document.querySelector('.tab-btn[onclick="showTab(\'unchecked\')"] .count');
        const checkedButton = document.querySelector('.tab-btn[onclick="showTab(\'checked\')"] .count');
        
        if (uncheckedButton) {
            uncheckedButton.textContent = uncheckedCount;
        }
        
        if (checkedButton) {
            checkedButton.textContent = checkedCount;
        }
        
        // อัพเดท Remaining Students ใน Save Modal
        const remainingStudents = document.getElementById('remaining-students');
        if (remainingStudents) {
            remainingStudents.textContent = uncheckedCount;
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการอัพเดทจำนวนในแท็บ:', error);
    }
}

/**
 * แสดงตัวบ่งชี้การบันทึก
 */
function showSaveIndicator() {
    try {
        let indicator = document.getElementById('save-indicator');
        
        if (!indicator) {
            // สร้างตัวบ่งชี้ใหม่
            indicator = document.createElement('div');
            indicator.id = 'save-indicator';
            indicator.className = 'save-indicator';
            indicator.textContent = 'มีข้อมูลที่ยังไม่ได้บันทึก! กรุณากดปุ่ม "บันทึก"';
            
            document.body.appendChild(indicator);
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงตัวบ่งชี้การบันทึก:', error);
    }
}

/**
 * แสดง Modal การเช็คชื่อแบบมีตัวเลือกเพิ่มเติม (สาย/ลา)
 * @param {number} studentId - ID ของนักเรียน
 * @param {string} studentName - ชื่อของนักเรียน
 */
function showAttendanceModal(studentId, studentName) {
    try {
        // ตั้งค่าข้อมูลใน Modal
        document.getElementById('student-name-display').textContent = studentName;
        document.getElementById('student-id-input').value = studentId;
        document.getElementById('is-edit-mode').value = '0';
        
        // เลือกสถานะเริ่มต้นเป็น "มาเรียน"
        document.getElementById('status-present').checked = true;
        
        // รีเซ็ตหมายเหตุ
        document.getElementById('status-reason').value = '';
        
        // รีเซ็ตหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
        if (document.getElementById('individual-note')) {
            document.getElementById('individual-note').value = '';
        }
        
        // ซ่อนช่องหมายเหตุเริ่มต้น (จะแสดงเมื่อเลือกสถานะ "มาสาย" หรือ "ลา" เท่านั้น)
        document.getElementById('reason-container').style.display = 'none';
        
        // แสดง Modal
        const modal = document.getElementById('mark-attendance-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดง Modal เช็คชื่อ:', error);
    }
}

    
    // เพิ่มฟังก์ชัน confirmMarkAttendance
    function confirmMarkAttendance() {
        try {
            // ดึงข้อมูลจาก Modal
            const studentId = document.getElementById('student-id-input').value;
            const status = document.querySelector('input[name="attendance-status"]:checked').value;
            const isEditMode = document.getElementById('is-edit-mode').value === '1';
            
            // ดึงข้อมูลหมายเหตุ
            let remarks = '';
            
            // ถ้าเป็นสถานะสายหรือลา ให้ดึงหมายเหตุจากช่องเหตุผล
            if (status === 'late' || status === 'leave') {
                remarks = document.getElementById('status-reason').value.trim();
                
                // ตรวจสอบว่ามีการระบุเหตุผลหรือไม่
                if (remarks === '') {
                    alert('กรุณาระบุเหตุผลสำหรับการมาสาย/ลา');
                    return;
                }
            }
            
            // ถ้าเป็นการเช็คชื่อย้อนหลัง
            if (isRetroactive) {
                const retroactiveInput = document.getElementById('individual-note');
                if (retroactiveInput) {
                    const retroactiveNote = retroactiveInput.value.trim();
                    
                    if (retroactiveNote === '') {
                        alert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง');
                        return;
                    }
                    
                    if (remarks) {
                        remarks += ` (${retroactiveNote})`;
                    } else {
                        remarks = retroactiveNote;
                    }
                }
            }
            
            // ตรวจสอบและเรียกใช้ฟังก์ชันอื่นๆ ที่จำเป็น
            if (typeof saveAttendanceData === 'function') {
                saveAttendanceData(studentId, status, remarks);
            }
            
            // อัพเดทสถานะตามโหมดการใช้งาน (แก้ไขหรือเช็คใหม่)
            if (isEditMode) {
                // โค้ดสำหรับการแก้ไขข้อมูล
                // ... (โค้ดในส่วนนี้ดูจากตัวอย่างในไฟล์ที่สร้างไว้)
            } else {
                // โค้ดสำหรับการเช็คชื่อใหม่
                // ... (โค้ดในส่วนนี้ดูจากตัวอย่างในไฟล์ที่สร้างไว้)
            }
            
            // ปิด Modal
            const modal = document.getElementById('mark-attendance-modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            // ฟังก์ชันเพิ่มเติม
            if (typeof updateAttendanceCounters === 'function') {
                updateAttendanceCounters();
            }
            
            if (typeof showSaveIndicator === 'function') {
                showSaveIndicator();
            }
            
            if (typeof hasChanges !== 'undefined') {
                hasChanges = true;
            }
        } catch (error) {
            console.error('เกิดข้อผิดพลาดในการยืนยันการเช็คชื่อ:', error);
            alert('เกิดข้อผิดพลาดในการเช็คชื่อ');
        }
    }

/**
 * แก้ไขการเช็คชื่อที่บันทึกไปแล้ว
 * @param {number} studentId - ID ของนักเรียน
 * @param {string} studentName - ชื่อของนักเรียน
 * @param {string} status - สถานะปัจจุบัน
 * @param {string} remarks - หมายเหตุ (ถ้ามี)
 */
function editAttendance(studentId, studentName, status, remarks) {
    try {
        // ตั้งค่าข้อมูลใน Modal
        document.getElementById('student-name-display').textContent = studentName;
        document.getElementById('student-id-input').value = studentId;
        document.getElementById('is-edit-mode').value = '1';
        
        // เลือกสถานะปัจจุบัน
        document.getElementById(`status-${status}`).checked = true;
        
        // ตั้งค่าหมายเหตุ
        document.getElementById('status-reason').value = remarks || '';
        
        // รีเซ็ตหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
        if (document.getElementById('individual-note')) {
            document.getElementById('individual-note').value = '';
        }
        
        // แสดง/ซ่อนช่องหมายเหตุตามสถานะ
        const reasonContainer = document.getElementById('reason-container');
        if (status === 'late' || status === 'leave') {
            reasonContainer.style.display = 'block';
        } else {
            reasonContainer.style.display = 'none';
        }
        
        // แสดง Modal
        const modal = document.getElementById('mark-attendance-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแก้ไขการเช็คชื่อ:', error);
        showAlert('เกิดข้อผิดพลาดในการแก้ไขการเช็คชื่อ', 'error');
    }
}

/**
 * แสดง Modal ยืนยันการบันทึก
 */
function saveAttendance() {
    try {
        // ตรวจสอบจำนวนนักเรียนที่ยังไม่ได้เช็คชื่อ
        const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
        
        // อัพเดทจำนวนนักเรียนที่ยังไม่ได้เช็คชื่อในโมดัล
        const remainingStudents = document.getElementById('remaining-students');
        if (remainingStudents) {
            remainingStudents.textContent = uncheckedCount;
        }
        
        // แสดง Modal ยืนยัน
        const modal = document.getElementById('save-modal');
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดง Modal บันทึก:', error);
    }
}

/**
 * ยืนยันการบันทึกการเช็คชื่อ - รวบรวมข้อมูลและส่งไปยัง API
 */
function confirmSaveAttendance() {
    try {
        // รวบรวมข้อมูลการเช็คชื่อทั้งหมด
        let allStudents = [];
        
        // ดึงรายการนักเรียนที่เช็คแล้ว (อยู่ในแท็บ "เช็คชื่อแล้ว")
        const checkedStudents = document.querySelectorAll('#checked-tab .student-item');
        checkedStudents.forEach(item => {
            const studentId = item.getAttribute('data-id');
            
            // ตรวจสอบว่ามีข้อมูลใน attendanceData หรือไม่
            const studentDataIndex = attendanceData.students.findIndex(s => s.student_id === studentId);
            
            if (studentDataIndex >= 0) {
                // ถ้ามี ใช้ข้อมูลจาก attendanceData
                allStudents.push({
                    student_id: studentId,
                    status: attendanceData.students[studentDataIndex].status,
                    remarks: attendanceData.students[studentDataIndex].remarks || ''
                });
            } else {
                // ถ้าไม่มี ใช้ข้อมูลจาก DOM
                const remarksElem = item.querySelector('.student-remarks');
                const remarks = remarksElem ? remarksElem.textContent : '';
                
                allStudents.push({
                    student_id: studentId,
                    status: item.getAttribute('data-status'),
                    remarks: remarks
                });
            }
        });
        
        // ดึงรายการนักเรียนที่ยังไม่ได้เช็ค แต่อาจถูกเลือกสถานะไว้แล้ว
        const uncheckedStudents = document.querySelectorAll('#unchecked-tab .student-item');
        uncheckedStudents.forEach(item => {
            const studentId = item.getAttribute('data-id');
            const status = item.getAttribute('data-status');
            
            // ตรวจสอบว่ามีข้อมูลใน attendanceData หรือไม่
            const studentDataIndex = attendanceData.students.findIndex(s => s.student_id === studentId);
            
            if (studentDataIndex >= 0) {
                // ถ้ามี ใช้ข้อมูลจาก attendanceData
                allStudents.push({
                    student_id: studentId,
                    status: attendanceData.students[studentDataIndex].status,
                    remarks: attendanceData.students[studentDataIndex].remarks || ''
                });
            } else if (status) {
                // ถ้าไม่มี แต่มีสถานะที่เลือกไว้ในหน้าเว็บ
                allStudents.push({
                    student_id: studentId,
                    status: status,
                    remarks: ''
                });
            } else {
                // ถ้าไม่มีทั้งในตัวแปรและไม่ได้เลือกสถานะ ให้ใช้ค่าเริ่มต้นเป็น absent
                allStudents.push({
                    student_id: studentId,
                    status: 'absent',
                    remarks: ''
                });
            }
        });
        
        // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
        if (isRetroactive) {
            const remarksInput = document.getElementById('retroactive-save-note');
            if (remarksInput) {
                const remarks = remarksInput.value.trim();
                
                // ตรวจสอบว่ามีการระบุหมายเหตุหรือไม่
                if (remarks === '') {
                    showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
                
                // เพิ่มหมายเหตุให้กับรายการที่ยังไม่มีหมายเหตุ
                allStudents.forEach(student => {
                    if (!student.remarks) {
                        student.remarks = remarks;
                    }
                });
            }
        }
        
        // ถ้าไม่มีข้อมูลการเช็คชื่อ
        if (allStudents.length === 0) {
            showAlert('ไม่พบข้อมูลนักเรียนที่ต้องการบันทึก', 'warning');
            closeModal('save-modal');
            return;
        }
        
        // ปิด Modal
        closeModal('save-modal');
        
        // แสดงการโหลด
        const loadingButton = document.querySelector('.floating-button');
        if (loadingButton) {
            // เปลี่ยนไอคอนเป็นหมุน
            const icon = loadingButton.querySelector('.material-icons');
            const originalIcon = icon.textContent;
            icon.textContent = 'hourglass_top';
            
            // ปิดการใช้งานปุ่ม
            loadingButton.disabled = true;
            loadingButton.style.backgroundColor = '#9e9e9e';
            
            // ส่งข้อมูลไปยัง API
            fetch('api/save_attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    class_id: currentClassId,
                    date: checkDate,
                    teacher_id: currentTeacherId,
                    students: allStudents,
                    is_retroactive: isRetroactive,
                    check_method: 'Manual'
                })
            })
            .then(response => response.json())
            .then(data => {
                // คืนค่าเดิม
                icon.textContent = originalIcon;
                loadingButton.disabled = false;
                loadingButton.style.backgroundColor = '';
                
                if (data.success) {
                    // ลบตัวบ่งชี้การบันทึก
                    const indicator = document.getElementById('save-indicator');
                    if (indicator) {
                        indicator.remove();
                    }
                    
                    // รีเซ็ตตัวแปรการเปลี่ยนแปลง
                    hasChanges = false;
                    
                    // แสดงข้อความแจ้งเตือน
                    showAlert('บันทึกการเช็คชื่อเรียบร้อย', 'success');
                    
                    // นำทางไปยังหน้าเดิม (รีโหลดหน้า)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // แสดงข้อความเมื่อมีข้อผิดพลาด
                    showAlert(data.message || 'เกิดข้อผิดพลาดในการบันทึกการเช็คชื่อ', 'error');
                }
            })
            .catch(error => {
                // คืนค่าเดิม
                icon.textContent = originalIcon;
                loadingButton.disabled = false;
                loadingButton.style.backgroundColor = '';
                
                // แสดงข้อความเมื่อมีข้อผิดพลาด
                console.error('Error:', error);
                showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            });
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการยืนยันการบันทึก:', error);
        showAlert('เกิดข้อผิดพลาดในการบันทึก', 'error');
        closeModal('save-modal');
    }
}

/**
 * ค้นหานักเรียน
 */
function searchStudents() {
    try {
        const searchInput = document.getElementById('search-input');
        const searchTerm = searchInput.value.toLowerCase();
        
        // ค้นหาในแท็บที่ยังไม่ได้เช็ค
        const uncheckedStudents = document.querySelectorAll('#unchecked-tab .student-item');
        uncheckedStudents.forEach(item => {
            const name = item.getAttribute('data-name') || item.querySelector('.student-name').textContent.toLowerCase();
            
            if (name.toLowerCase().includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // ค้นหาในแท็บที่เช็คแล้ว
        const checkedStudents = document.querySelectorAll('#checked-tab .student-item');
        checkedStudents.forEach(item => {
            const name = item.getAttribute('data-name') || item.querySelector('.student-name').textContent.toLowerCase();
            
            if (name.toLowerCase().includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการค้นหานักเรียน:', error);
    }
}

/**
 * สลับแท็บที่แสดง
 * @param {string} tabName - ชื่อแท็บ
 */
function showTab(tabName) {
    try {
        // ซ่อนทุกแท็บ
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(tab => {
            tab.classList.remove('active');
        });
        
        // แสดงแท็บที่เลือก
        const selectedTab = document.getElementById(`${tabName}-tab`);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // ปรับปุ่มแท็บ
        const tabButtons = document.querySelectorAll('.tab-btn');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });
        
        // เพิ่มคลาส active ให้กับปุ่มที่เลือก
        const selectedButton = document.querySelector(`.tab-btn[onclick="showTab('${tabName}')"]`);
        if (selectedButton) {
            selectedButton.classList.add('active');
        }
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการสลับแท็บ:', error);
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * @param {string} message - ข้อความ
 * @param {string} type - ประเภท (success, info, warning, error)
 */
function showAlert(message, type = 'info') {
    try {
        // ถ้ามีฟังก์ชันจาก main.js ใช้อันนั้น
        if (typeof window.displayAlert === 'function') {
            window.displayAlert(message, type);
            return;
        }
        
        // สร้าง Alert Container ถ้ายังไม่มี
        let alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alertContainer';
            alertContainer.className = 'alert-container';
            document.body.appendChild(alertContainer);
        }
        
        // สร้าง Alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <div class="alert-content">
                <span class="alert-icon material-icons">${getAlertIcon(type)}</span>
                <span class="alert-message">${message}</span>
            </div>
            <span class="alert-close material-icons">close</span>
        `;
        
        // เพิ่มการจัดการเหตุการณ์ปิดการแจ้งเตือน
        const closeButton = alertDiv.querySelector('.alert-close');
        closeButton.addEventListener('click', () => {
            alertDiv.remove();
        });
        
        // เพิ่มการแจ้งเตือนในคอนเทนเนอร์
        alertContainer.appendChild(alertDiv);
        
        // กำหนดให้ปิดอัตโนมัติหลังจาก 5 วินาที
        setTimeout(() => {
            if (alertDiv && alertDiv.parentNode === alertContainer) {
                alertDiv.remove();
            }
        }, 5000);
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการแสดงการแจ้งเตือน:', error);
    }
}

/**
 * รับไอคอนสำหรับประเภทการแจ้งเตือน
 * @param {string} type - ประเภทการแจ้งเตือน
 * @returns {string} - ชื่อไอคอน Material Icons
 */
function getAlertIcon(type) {
    switch (type) {
        case 'success': return 'check_circle';
        case 'warning': return 'warning';
        case 'error': return 'error';
        case 'info':
        default: return 'info';
    }
}

/**
 * จัดการเหตุการณ์คลิกที่รายการนักเรียน
 */
function setupStudentItemEvents() {
    try {
        // เพิ่มเหตุการณ์คลิกที่รายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const uncheckedItems = document.querySelectorAll('#unchecked-tab .student-item');
        uncheckedItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // ถ้าคลิกที่ปุ่มเช็คชื่อ ไม่ต้องทำอะไร
                if (e.target.closest('.action-button') || e.target.closest('.student-name')) {
                    return;
                }
                
                // แสดง Modal เช็คชื่อรายบุคคล
                const studentId = this.getAttribute('data-id');
                const studentName = this.querySelector('.student-name').textContent.trim();
                
                showAttendanceModal(studentId, studentName);
            });
        });
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการตั้งค่าเหตุการณ์คลิก:', error);
    }
}

// เพิ่มการฟังเหตุการณ์ก่อนออกจากหน้าเว็บ
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        // แสดงข้อความยืนยันก่อนออกจากหน้า
        const confirmationMessage = 'คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?';
        e.returnValue = confirmationMessage;
        return confirmationMessage;
    }
});

// เพิ่มการฟังเหตุการณ์เปลี่ยนสถานะของ radio buttons ในโมดัลเช็คชื่อ
document.addEventListener('DOMContentLoaded', function() {
    const statusInputs = document.querySelectorAll('input[name="attendance-status"]');
    const reasonContainer = document.getElementById('reason-container');
    
    statusInputs.forEach(input => {
        input.addEventListener('change', function() {
            const selectedStatus = this.value;
            
            if (selectedStatus === 'late' || selectedStatus === 'leave') {
                reasonContainer.style.display = 'block';
            } else {
                reasonContainer.style.display = 'none';
            }
        });
    });
});

// ตัวแปรเก็บสถานะว่ามีการเปลี่ยนแปลงข้อมูลหรือไม่
let hasChanges = false;

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้นการทำงานของหน้าเช็คชื่อ
    initCheckAttendance();
});

/**
 * เริ่มต้นการทำงานของหน้าเช็คชื่อ
 */
function initCheckAttendance() {
    // อัพเดทจำนวนการเข้าแถว
    updateAttendanceCounters();
    
    // ติดตั้งเหตุการณ์ค้นหา
    initSearchFunction();
    
    // ตั้งค่าแท็บเริ่มต้น
    showTab('unchecked');
    
    // จัดการเหตุการณ์คลิกที่รายการนักเรียน
    setupStudentItemEvents();
}

/**
 * เปลี่ยนห้องเรียน
 * @param {string} classId - ID ของห้องเรียน
 */
function changeClass(classId) {
    // ตรวจสอบว่ามีการเปลี่ยนแปลงข้อมูลหรือไม่
    if (hasChanges) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์ห้องเรียน
            window.location.href = 'check-attendance.php?class_id=' + classId + '&date=' + checkDate;
        }
    } else {
        // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์ห้องเรียน
        window.location.href = 'check-attendance.php?class_id=' + classId + '&date=' + checkDate;
    }
}

/**
 * เปลี่ยนวันที่
 * @param {string} date - วันที่ต้องการเช็คชื่อ
 */
function changeDate(date) {
    // ตรวจสอบว่ามีการเปลี่ยนแปลงข้อมูลหรือไม่
    if (hasChanges) {
        if (confirm('คุณมีข้อมูลที่ยังไม่ได้บันทึก ต้องการออกจากหน้านี้หรือไม่?')) {
            // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์วันที่
            window.location.href = 'check-attendance.php?class_id=' + currentClassId + '&date=' + date;
        }
    } else {
        // นำทางไปยังหน้าเดิมพร้อมกับเปลี่ยนพารามิเตอร์วันที่
        window.location.href = 'check-attendance.php?class_id=' + currentClassId + '&date=' + date;
    }
}

/**
 * สร้างรหัส PIN สำหรับการเช็คชื่อ
 */
function showPinModal() {
    // แสดง Modal สร้างรหัส PIN
    const modal = document.getElementById('pin-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
    }
}

/**
 * ปิด Modal ด้วย ID
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
 * สร้างรหัส PIN ใหม่
 */
function generateNewPin() {
    // แสดงข้อความกำลังดำเนินการ
    showAlert('กำลังสร้างรหัส PIN...', 'info');
    
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // อัพเดท PIN ที่แสดงใน Modal
            const pinDisplay = document.getElementById('pin-display');
            if (pinDisplay) {
                pinDisplay.textContent = data.pin_code;
            }
            
            // อัพเดทเวลาที่เหลือ
            const pinExpireTime = document.getElementById('pin-expire-time');
            if (pinExpireTime) {
                pinExpireTime.textContent = data.expire_minutes;
            }
            
            // แสดงข้อความแจ้งเตือนสำเร็จ
            showAlert('สร้างรหัส PIN ใหม่เรียบร้อย', 'success');
        } else {
            // แสดงข้อความเมื่อมีข้อผิดพลาด
            showAlert(data.message || 'เกิดข้อผิดพลาดในการสร้าง PIN', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
    });
}

/**
 * สแกน QR Code
 */
function scanQRCode() {
    // แสดง Modal สแกน QR Code
    const modal = document.getElementById('qr-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // สำหรับตัวอย่าง (รออัพเดทในเวอร์ชันถัดไป)
        showAlert('ระบบกำลังเรียกใช้กล้อง กรุณารอสักครู่...', 'info');
    }
}

/**
 * แสดง Modal ยืนยันเช็คชื่อทั้งหมด
 */
function showMarkAllModal() {
    // ตรวจสอบว่ามีนักเรียนที่ยังไม่ได้เช็คชื่อหรือไม่
    const uncheckedCount = document.querySelectorAll('#unchecked-tab .student-item').length;
    
    if (uncheckedCount === 0) {
        showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
        return;
    }
    
    // แสดง Modal ยืนยันเช็คชื่อทั้งหมด
    const modal = document.getElementById('mark-all-modal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * เช็คชื่อนักเรียนและเปลี่ยนสถานะให้ถูกต้อง
 * @param {HTMLElement} button - ปุ่มที่ถูกคลิก
 * @param {string} status - สถานะการเข้าแถว (present/absent/late/leave)
 * @param {number} studentId - ID ของนักเรียน
 */
function markAttendance(button, status, studentId) {
    console.log("markAttendance เริ่มทำงาน", { button, status, studentId });
    
    try {
        // ดึงรายการนักเรียน
        const studentItem = button.closest('.student-item');
        if (!studentItem) {
            console.error('ไม่พบรายการนักเรียน');
            return;
        }
        
        // ดึงข้อมูลชื่อนักเรียน
        const studentName = studentItem.querySelector('.student-name');
        if (!studentName) {
            console.error('ไม่พบชื่อนักเรียน');
            return;
        }
        
        // ดึงปุ่มทั้งหมด
        const buttons = studentItem.querySelectorAll('.action-button');
        
        if (buttons.length === 0) {
            console.error('ไม่พบปุ่มเช็คชื่อ');
            return;
        }
        
        // รีเซ็ตสถานะของปุ่มทั้งหมด
        buttons.forEach(btn => {
            btn.classList.remove('active');
            btn.style.backgroundColor = '';
            btn.style.color = '';
        });
        
        // ตั้งค่าปุ่มที่เลือก
        button.classList.add('active');
        
        // กำหนดสีพื้นหลังตามสถานะ
        let statusText, statusColor;
        
        switch (status) {
            case 'present':
                button.style.backgroundColor = '#4caf50';
                button.style.color = 'white';
                statusText = 'มาเรียน';
                statusColor = '#4caf50';
                studentItem.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                studentItem.style.borderLeft = '3px solid #4caf50';
                break;
                
            case 'late':
                button.style.backgroundColor = '#ff9800';
                button.style.color = 'white';
                statusText = 'มาสาย';
                statusColor = '#ff9800';
                studentItem.style.backgroundColor = 'rgba(255, 152, 0, 0.1)';
                studentItem.style.borderLeft = '3px solid #ff9800';
                break;
                
            case 'leave':
                button.style.backgroundColor = '#9c27b0';
                button.style.color = 'white';
                statusText = 'ลา';
                statusColor = '#9c27b0';
                studentItem.style.backgroundColor = 'rgba(156, 39, 176, 0.1)';
                studentItem.style.borderLeft = '3px solid #9c27b0';
                break;
                
            case 'absent':
                button.style.backgroundColor = '#f44336';
                button.style.color = 'white';
                statusText = 'ขาด';
                statusColor = '#f44336';
                studentItem.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
                studentItem.style.borderLeft = '3px solid #f44336';
                break;
        }
        
        // เก็บสถานะในข้อมูล
        studentItem.setAttribute('data-status', status);
        
        // ลบข้อความสถานะเดิม
        const existingStatusElem = studentItem.querySelector('.status-indicator');
        if (existingStatusElem) {
            existingStatusElem.remove();
        }
        
        // เพิ่มข้อความสถานะใหม่
        const statusIndicator = document.createElement('div');
        statusIndicator.className = 'status-indicator';
        statusIndicator.textContent = `สถานะ: ${statusText}`;
        statusIndicator.style.fontSize = '12px';
        statusIndicator.style.marginTop = '4px';
        statusIndicator.style.color = statusColor;
        statusIndicator.style.fontWeight = 'normal';
        
        // เพิ่มเข้าไปในชื่อนักเรียน
        studentName.appendChild(statusIndicator);
        
        // บันทึกข้อมูลในตัวแปร global
        saveAttendanceData(studentId, status, '');
        
        // อัพเดทสถิติการเข้าแถว
        updateAttendanceCounters();
        
        // แสดงข้อความแจ้งเตือนการเปลี่ยนสถานะ
        console.log(`เปลี่ยนสถานะ: นักเรียน ID ${studentId} เป็น ${statusText}`);
        
        // แสดงตัวบ่งชี้การบันทึก
        showSaveIndicator();
        
        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อ:', error);
    }
}

/**
 * เช็คชื่อนักเรียนที่ยังไม่ได้เช็คทั้งหมดตามสถานะที่เลือก
 */
function markAllWithStatus() {
    console.log("markAllWithStatus เริ่มทำงาน");
    
    try {
        // ดึงทุกรายการนักเรียนในแท็บที่ยังไม่ได้เช็ค
        const uncheckedTab = document.getElementById('unchecked-tab');
        const studentItems = uncheckedTab.querySelectorAll('.student-item');
        
        // ถ้าไม่มีนักเรียนที่ยังไม่ได้เช็คชื่อ
        if (studentItems.length === 0) {
            closeModal('mark-all-modal');
            showAlert('ไม่มีนักเรียนที่ต้องเช็คชื่อแล้ว', 'info');
            return;
        }
        
        // ดึงสถานะที่เลือก
        const selectedStatus = document.querySelector('input[name="mark-all-status"]:checked').value;
        console.log("สถานะที่เลือก:", selectedStatus);
        
        // ดึงหมายเหตุสำหรับการเช็คชื่อย้อนหลัง (ถ้ามี)
        let remarks = '';
        if (isRetroactive) {
            const remarksInput = document.getElementById('retroactive-note');
            if (remarksInput) {
                remarks = remarksInput.value.trim();
                
                if (remarks === '') {
                    showAlert('กรุณาระบุหมายเหตุสำหรับการเช็คชื่อย้อนหลัง', 'warning');
                    return;
                }
            }
        }
        
        // รีเซ็ตทุกรายการก่อน
        studentItems.forEach(item => {
            // รีเซ็ตสไตล์
            item.style.backgroundColor = '';
            item.style.borderLeft = '';
            
            // รีเซ็ตปุ่ม
            const buttons = item.querySelectorAll('.action-button');
            buttons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.backgroundColor = '';
                btn.style.color = '';
            });
            
            // ลบข้อความสถานะเดิม
            const statusIndicator = item.querySelector('.status-indicator');
            if (statusIndicator) {
                statusIndicator.remove();
            }
        });
        
        // กำหนดค่าตามสถานะที่เลือก
        let bgColor, borderColor, statusText, statusColor;
        
        switch (selectedStatus) {
            case 'present':
                bgColor = 'rgba(76, 175, 80, 0.1)';
                borderColor = '#4caf50';
                statusText = 'มาเรียน';
                statusColor = '#4caf50';
                break;
            case 'late':
                bgColor = 'rgba(255, 152, 0, 0.1)';
                borderColor = '#ff9800';
                statusText = 'มาสาย';
                statusColor = '#ff9800';
                break;
            case 'leave':
                bgColor = 'rgba(156, 39, 176, 0.1)';
                borderColor = '#9c27b0';
                statusText = 'ลา';
                statusColor = '#9c27b0';
                break;
            case 'absent':
                bgColor = 'rgba(244, 67, 54, 0.1)';
                borderColor = '#f44336';
                statusText = 'ขาด';
                statusColor = '#f44336';
                break;
        }
        
        // อัพเดททุกรายการ
        studentItems.forEach(item => {
            const studentId = item.getAttribute('data-id');
            
            // ตั้งค่าปุ่มที่เกี่ยวข้อง
            const buttonToUse = item.querySelector(`.action-button.${selectedStatus === 'present' || selectedStatus === 'late' || selectedStatus === 'leave' ? 'present' : 'absent'}`);
            
            if (buttonToUse) {
                // เพิ่มคลาส active ให้กับปุ่ม
                buttonToUse.classList.add('active');
                buttonToUse.style.backgroundColor = borderColor;
                buttonToUse.style.color = 'white';
            }
            
            // ตั้งค่าสไตล์ให้รายการ
            item.style.backgroundColor = bgColor;
            item.style.borderLeft = `3px solid ${borderColor}`;
            item.setAttribute('data-status', selectedStatus);
            
            // เพิ่มข้อความสถานะ
            const nameElement = item.querySelector('.student-name');
            const statusIndicator = document.createElement('div');
            statusIndicator.className = 'status-indicator';
            statusIndicator.textContent = `สถานะ: ${statusText}`;
            statusIndicator.style.fontSize = '12px';
            statusIndicator.style.marginTop = '4px';
            statusIndicator.style.color = statusColor;
            statusIndicator.style.fontWeight = 'normal';
            
            // เพิ่มเข้าไปในชื่อนักเรียน
            nameElement.appendChild(statusIndicator);
            
            // บันทึกข้อมูลในตัวแปร global
            saveAttendanceData(studentId, selectedStatus, remarks);
        });
        
        // อัพเดทสถิติการเข้าแถว
        updateAttendanceCounters();
        
        // แสดงข้อความแจ้งเตือน
        showAlert(`เช็คชื่อนักเรียนทั้งหมดเป็น "${statusText}" แล้ว (ยังไม่ได้บันทึก)`, 'info');
        
        // ปิด Modal
        closeModal('mark-all-modal');
        
        // แสดงตัวบ่งชี้การบันทึก
        showSaveIndicator();
        
        // กำหนดว่ามีการเปลี่ยนแปลงข้อมูล
        hasChanges = true;
    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด:', error);
        showAlert('เกิดข้อผิดพลาดในการเช็คชื่อทั้งหมด', 'error');
    }
}

// Make function available globally if it's in a module or closure
if (typeof window.confirmMarkAttendance === 'undefined') {
    window.confirmMarkAttendance = confirmMarkAttendance;
}