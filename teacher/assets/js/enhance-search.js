/**
 * enhance-search.js
 * เพิ่มฟังก์ชันค้นหาและการแสดงผลที่ดีขึ้นสำหรับระบบเช็คชื่อ
 * สามารถนำไปใช้เสริมกับระบบเดิมโดยไม่กระทบการทำงานหลัก
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('เริ่มทำงานระบบค้นหาเพิ่มเติม');

    // เพิ่มปุ่มล้างการค้นหา
    enhanceSearchInput();

    // ผูกการทำงานค้นหาเพิ่มเติม
    setupAdvancedSearch();

    // เพิ่มการแสดงแถบเตือนการเช็คชื่อย้อนหลัง
    setupRetroactiveBadge();
});

/**
 * เพิ่มปุ่มล้างการค้นหาและปรับปรุงช่องค้นหา
 */
function enhanceSearchInput() {
    const searchContainer = document.querySelector('.search-container');
    const searchInput = document.getElementById('searchInput');

    if (!searchContainer || !searchInput) return;

    // เพิ่มปุ่มล้างการค้นหา
    const clearButton = document.createElement('span');
    clearButton.className = 'search-clear';
    clearButton.innerHTML = '<i class="fas fa-times"></i>';
    clearButton.style.display = 'none';
    searchContainer.appendChild(clearButton);

    // แสดง/ซ่อนปุ่มล้างการค้นหาตามสถานะช่องค้นหา
    searchInput.addEventListener('input', function() {
        clearButton.style.display = this.value ? 'block' : 'none';
    });

    // เพิ่มฟังก์ชันล้างการค้นหา
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.style.display = 'none';
        searchStudents();
        searchInput.focus();
    });

    // เพิ่มความสามารถล้างด้วยปุ่ม Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (this.value) {
                this.value = '';
                clearButton.style.display = 'none';
                searchStudents();
                e.preventDefault();
            }
        }
    });

    // เพิ่ม CSS เพิ่มเติม
    const style = document.createElement('style');
    style.textContent = `
        .search-clear {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9e9e9e;
            cursor: pointer;
            z-index: 10;
            font-size: 16px;
            display: none;
        }
        
        .search-clear:hover {
            color: #f44336;
        }
        
        .search-input:focus {
            box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
        }
    `;
    document.head.appendChild(style);
}

/**
 * ค้นหาขั้นสูงที่สามารถค้นหาได้ทั้งชื่อและรหัสนักเรียน ในทั้งสองแท็บ
 */
function setupAdvancedSearch() {
    const searchInput = document.getElementById('searchInput');

    if (!searchInput) return;

    // แทนที่ฟังก์ชัน searchStudents ดั้งเดิม
    window.searchStudents = function() {
        const searchTerm = searchInput.value.toLowerCase();

        // ค้นหาในทั้งสองแท็บ
        searchInTab('waitingTab', searchTerm);
        searchInTab('checkedTab', searchTerm);

        // แสดงข้อความเมื่อไม่พบผลการค้นหา
        showSearchResultEmpty('waitingTab', searchTerm);
        showSearchResultEmpty('checkedTab', searchTerm);
    };

    // ผูกฟังก์ชันค้นหากับช่องค้นหา (เผื่อว่ายังไม่ได้ผูก)
    searchInput.removeEventListener('input', window.searchStudents);
    searchInput.addEventListener('input', window.searchStudents);
}

/**
 * ค้นหานักเรียนในแท็บที่ระบุ
 * @param {string} tabId - ID ของแท็บที่ต้องการค้นหา
 * @param {string} searchTerm - คำค้นหา
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
 * @param {string} tabId - ID ของแท็บ
 * @param {string} searchTerm - คำค้นหา
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
 * เพิ่มการแสดงแถบเตือนการเช็คชื่อย้อนหลัง
 */
function setupRetroactiveBadge() {
    const dateSelector = document.getElementById('dateSelect');
    if (!dateSelector) return;

    const today = new Date().toISOString().split('T')[0];

    // ตรวจสอบว่าเป็นการเช็คชื่อย้อนหลังหรือไม่
    const isRetroactive = dateSelector.value !== today;

    // หาหรือสร้างป้ายแจ้งเตือนการเช็คชื่อย้อนหลัง
    let retroactiveBadge = document.querySelector('.retroactive-badge');

    if (isRetroactive) {
        // ถ้าเป็นการเช็คชื่อย้อนหลังแต่ยังไม่มีป้ายแจ้งเตือน
        if (!retroactiveBadge) {
            // ค้นหาตำแหน่งที่จะแสดงป้ายแจ้งเตือน
            const dateContainer = document.querySelector('.date-selector');

            if (dateContainer) {
                retroactiveBadge = document.createElement('div');
                retroactiveBadge.className = 'retroactive-badge';
                retroactiveBadge.innerHTML = '<i class="fas fa-history"></i> เช็คชื่อย้อนหลัง';

                // เพิ่มเอฟเฟ็กต์การแสดงผล
                retroactiveBadge.style.animation = 'pulse 2s infinite';

                // เพิ่มไปยังหน้าเว็บ
                dateContainer.appendChild(retroactiveBadge);

                // เพิ่ม CSS Animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes pulse {
                        0% {
                            box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.4);
                        }
                        70% {
                            box-shadow: 0 0 0 6px rgba(255, 152, 0, 0);
                        }
                        100% {
                            box-shadow: 0 0 0 0 rgba(255, 152, 0, 0);
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        } else {
            // ถ้ามีป้ายแจ้งเตือนอยู่แล้ว ให้แสดง
            retroactiveBadge.style.display = 'inline-flex';
        }
    } else if (retroactiveBadge) {
        // ถ้าไม่ใช่การเช็คชื่อย้อนหลัง แต่มีป้ายแจ้งเตือน ให้ซ่อน
        retroactiveBadge.style.display = 'none';
    }

    // เพิ่มการตรวจจับการเปลี่ยนวันที่
    dateSelector.addEventListener('change', function() {
        // ตรวจสอบการเปลี่ยนวันที่และเปลี่ยนหน้า
        const selectedDate = this.value;

        // ตรวจสอบว่าวันที่เปลี่ยนไปเป็นวันในอนาคตหรือไม่
        if (selectedDate > today) {
            // ถ้าเป็นวันในอนาคต ให้เปลี่ยนเป็นวันปัจจุบัน
            alert('ไม่สามารถเช็คชื่อล่วงหน้าได้');
            this.value = today;
            return;
        }

        // รีโหลดหน้าเว็บด้วยวันที่ใหม่
        window.location.href = `new_check_attendance.php?class_id=${currentClassId}&date=${selectedDate}`;
    });
}

/**
 * ตรวจสอบว่ามีฟังก์ชันแก้ไขการเช็คชื่อหรือไม่ ถ้าไม่มีให้สร้างขึ้นใหม่
 */
if (typeof window.editAttendance !== 'function') {
    /**
     * แก้ไขการเช็คชื่อนักเรียน
     * @param {number} studentId - รหัสนักเรียน
     * @param {string} studentName - ชื่อนักเรียน
     * @param {string} status - สถานะการเช็คชื่อ
     * @param {string} remarks - หมายเหตุ
     */
    window.editAttendance = function(studentId, studentName, status, remarks) {
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

        // ตรวจสอบการเช็คชื่อย้อนหลัง
        const today = new Date().toISOString().split('T')[0];
        const currentDate = document.getElementById('dateSelect') ? .value || today;
        const isRetroactive = currentDate !== today;

        // จัดการส่วนการเช็คชื่อย้อนหลัง
        const retroactiveNoteInput = document.getElementById('retroactiveNote');
        const retroactiveSection = document.querySelector('.retroactive-note');

        if (isRetroactive && retroactiveSection) {
            retroactiveSection.style.display = 'block';

            // เพิ่มข้อความเตือนการเช็คชื่อย้อนหลัง (ถ้ายังไม่มี)
            if (!retroactiveSection.querySelector('.retroactive-warning')) {
                const warning = document.createElement('div');
                warning.className = 'retroactive-warning';
                warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> การเช็คชื่อย้อนหลังจำเป็นต้องมีหมายเหตุ';
                warning.style.color = '#ff9800';
                warning.style.fontSize = '13px';
                warning.style.marginTop = '5px';
                retroactiveSection.appendChild(warning);
            }
        } else if (retroactiveSection) {
            retroactiveSection.style.display = 'none';
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
        if (typeof showModal === 'function') {
            showModal('attendanceDetailModal');
        } else {
            // ถ้ายังไม่มีฟังก์ชัน showModal ให้สร้างขึ้นมาใหม่
            const modal = document.getElementById('attendanceDetailModal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
    };
}

/**
 * ตรวจสอบว่ามีฟังก์ชัน confirmDetailAttendance หรือไม่ ถ้าไม่มีให้สร้างขึ้นใหม่
 */
if (typeof window.confirmDetailAttendance !== 'function') {
    /**
     * ยืนยันการเช็คชื่อรายละเอียด - ปรับปรุงให้ตรวจสอบการเช็คชื่อย้อนหลัง
     */
    window.confirmDetailAttendance = function() {
        const studentId = document.getElementById('studentIdDetail').value;
        const status = document.querySelector('input[name="attendanceStatus"]:checked').value;
        const remarks = document.getElementById('attendanceRemarks').value;

        // ตรวจสอบการเช็คชื่อย้อนหลัง
        const today = new Date().toISOString().split('T')[0];
        const currentDate = document.getElementById('dateSelect') ? .value || today;
        const isRetroactive = currentDate !== today;

        let retroactiveNote = '';
        if (isRetroactive) {
            const retroactiveNoteInput = document.getElementById('retroactiveNote');
            if (retroactiveNoteInput) {
                retroactiveNote = retroactiveNoteInput.value.trim();

                // ตรวจสอบว่ามีการระบุหมายเหตุการเช็คชื่อย้อนหลังหรือไม่
                if (!retroactiveNote) {
                    alert('กรุณาระบุหมายเหตุการเช็คชื่อย้อนหลัง');
                    retroactiveNoteInput.focus();
                    return;
                }
            }
        }

        // รวมหมายเหตุ
        let finalRemarks = remarks;
        if (retroactiveNote) {
            finalRemarks = finalRemarks ? `${finalRemarks} (${retroactiveNote})` : retroactiveNote;
        }

        // ส่งข้อมูลไปยัง API
        const data = {
            student_id: studentId,
            status: status,
            class_id: currentClassId,
            date: checkDate,
            is_retroactive: isRetroactive,
            remarks: finalRemarks
        };

        // ปิด Modal
        if (typeof closeModal === 'function') {
            closeModal('attendanceDetailModal');
        } else {
            const modal = document.getElementById('attendanceDetailModal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // แสดงข้อความกำลังดำเนินการ
        if (typeof showNotification === 'function') {
            showNotification('กำลังบันทึกข้อมูล...', 'info');
        } else {
            alert('กำลังบันทึกข้อมูล...');
        }

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
                        if (typeof moveToCheckedTab === 'function') {
                            moveToCheckedTab(studentCard, studentId, status, responseData.student.time_checked, responseData.attendance_id);
                        }

                        // อัพเดทจำนวนนักเรียน
                        if (typeof updateStudentCounts === 'function') {
                            updateStudentCounts();
                        }

                        // อัพเดทสถิติ
                        if (typeof updateAttendanceStats === 'function') {
                            updateAttendanceStats(status);
                        }
                    } else {
                        // กรณีแก้ไขข้อมูลในแท็บเช็คชื่อแล้ว
                        const existingCard = document.querySelector(`#checkedTab .student-card[data-id="${studentId}"]`);
                        if (existingCard) {
                            if (typeof updateExistingCard === 'function') {
                                updateExistingCard(existingCard, status, responseData.student.time_checked, responseData.attendance_id);
                            }

                            // อัพเดทหมายเหตุ (ถ้ามี)
                            if (finalRemarks) {
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
                                    remarksElement.textContent = finalRemarks;
                                }
                            }

                            if (typeof updateAttendanceStats === 'function') {
                                updateAttendanceStats(status);
                            }
                        }
                    }

                    // แสดงการแจ้งเตือน
                    if (typeof showNotification === 'function') {
                        showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                    } else {
                        alert(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`);
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + responseData.message);
                    }
                }
            })
            .catch(error => {
                if (typeof showNotification === 'function') {
                    showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                } else {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message);
                }
                console.error('Error:', error);
            });
    };
}

// ฟังก์ชันช่วยเหลือกรณียังไม่มีฟังก์ชันเหล่านี้
if (typeof getStatusText !== 'function') {
    window.getStatusText = function(status) {
        switch (status) {
            case 'present':
                return 'มาเรียน';
            case 'absent':
                return 'ขาดเรียน';
            case 'late':
                return 'มาสาย';
            case 'leave':
                return 'ลา';
            default:
                return 'ไม่ระบุ';
        }
    };
}

// ปรับปรุงฟังก์ชัน showModal และ closeModal กรณีที่ยังไม่มี
if (typeof showModal !== 'function') {
    window.showModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };
}

if (typeof closeModal !== 'function') {
    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };
}

// ปรับปรุงฟังก์ชัน showNotification กรณีที่ยังไม่มี
if (typeof showNotification !== 'function') {
    window.showNotification = function(message, type = 'info') {
        // ตรวจสอบว่ามี notification container หรือไม่
        let container = document.querySelector('.notification-container');
        if (!container) {
            // สร้าง container ใหม่
            container = document.createElement('div');
            container.className = 'notification-container';
            container.style.position = 'fixed';
            container.style.top = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            container.style.maxWidth = '350px';
            container.style.width = 'calc(100% - 40px)';
            document.body.appendChild(container);
        }

        // สร้างแถบแจ้งเตือน
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.style.backgroundColor = 'white';
        notification.style.borderRadius = '8px';
        notification.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.1)';
        notification.style.marginBottom = '10px';
        notification.style.animation = 'slideIn 0.3s ease-out';
        notification.style.transition = 'all 0.3s ease';

        // ตั้งค่าสีตามประเภท
        let borderColor = '#2196f3';
        let iconColor = '#2196f3';
        switch (type) {
            case 'success':
                borderColor = '#4caf50';
                iconColor = '#4caf50';
                break;
            case 'warning':
                borderColor = '#ff9800';
                iconColor = '#ff9800';
                break;
            case 'error':
                borderColor = '#f44336';
                iconColor = '#f44336';
                break;
        }
        notification.style.borderLeft = `4px solid ${borderColor}`;

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
            <div style="display: flex; padding: 12px 16px; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-${icon}" style="margin-right: 10px; color: ${iconColor};"></i>
                    <span>${message}</span>
                </div>
                <button style="background: none; border: none; color: #999; cursor: pointer; font-size: 16px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // เพิ่ม CSS Animation ถ้ายังไม่มี
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from { opacity: 0; transform: translateX(50px); }
                    to { opacity: 1; transform: translateX(0); }
                }
                @keyframes slideOut {
                    from { opacity: 1; transform: translateX(0); }
                    to { opacity: 0; transform: translateX(50px); }
                }
            `;
            document.head.appendChild(style);
        }

        // เพิ่มไปยัง container
        container.appendChild(notification);

        // กำหนดการปิดเมื่อคลิก
        const closeButton = notification.querySelector('button');
        if (closeButton) {
            closeButton.addEventListener('click', () => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(50px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            });
        }

        // กำหนดการปิดอัตโนมัติ
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(50px)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    };
}