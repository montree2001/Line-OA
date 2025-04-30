/**
 * enhanced-search-functions.js
 * ปรับปรุงฟังก์ชันค้นหาและเช็คชื่อย้อนหลัง
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('เริ่มทำงานฟังก์ชันค้นหาและเช็คชื่อย้อนหลังที่ปรับปรุงแล้ว');

    // ตรวจสอบสถานะการเช็คชื่อย้อนหลัง
    setupRetroactiveChecking();

    // ปรับปรุงช่องค้นหา
    enhanceSearchInput();

    // อัพเดทฟังก์ชันค้นหานักเรียน
    improveSearchFunction();

    // เพิ่มการทำงานให้ Modal
    enhanceModalFunctions();
});

/**
 * ตรวจสอบการเช็คชื่อย้อนหลังและเพิ่มการแสดงผล
 */
function setupRetroactiveChecking() {
    const dateSelector = document.getElementById('dateSelect');
    if (!dateSelector) return;

    const today = new Date().toISOString().split('T')[0];
    const isRetroactive = dateSelector.value !== today;

    if (isRetroactive) {
        // เพิ่มป้ายแจ้งเตือนการเช็คชื่อย้อนหลัง
        let retroactiveBadge = document.querySelector('.retroactive-badge');
        if (!retroactiveBadge) {
            const dateContainer = document.querySelector('.date-selector');
            if (dateContainer) {
                retroactiveBadge = document.createElement('div');
                retroactiveBadge.className = 'retroactive-badge';
                retroactiveBadge.innerHTML = '<i class="fas fa-history"></i> เช็คชื่อย้อนหลัง';
                dateContainer.appendChild(retroactiveBadge);
            }
        }

        // ตรวจสอบว่ามีช่องบันทึกหมายเหตุการเช็คชื่อย้อนหลังหรือไม่
        document.querySelectorAll('.retroactive-note').forEach(note => {
            note.style.display = 'block';
        });

        // ตรวจสอบว่ามีข้อความเตือนการเช็คชื่อย้อนหลังใน Modal หรือไม่
        const attendanceDetailModal = document.getElementById('attendanceDetailModal');
        if (attendanceDetailModal) {
            const modalBody = attendanceDetailModal.querySelector('.modal-body');
            if (modalBody) {
                let retroNote = modalBody.querySelector('.retroactive-note');

                if (!retroNote) {
                    // สร้างส่วนการเช็คชื่อย้อนหลัง
                    retroNote = document.createElement('div');
                    retroNote.className = 'retroactive-note';
                    retroNote.innerHTML = `
                        <label for="retroactiveNote">หมายเหตุการเช็คย้อนหลัง:</label>
                        <textarea id="retroactiveNote" placeholder="ระบุหมายเหตุการเช็คย้อนหลัง เช่น ใบรับรองแพทย์, หนังสือลา ฯลฯ"></textarea>
                        <div class="retroactive-warning">
                            <i class="fas fa-exclamation-triangle"></i> การเช็คชื่อย้อนหลังจำเป็นต้องมีหมายเหตุ
                        </div>
                    `;

                    // แทรกลงใน Modal
                    const remarksContainer = modalBody.querySelector('.remarks-container');
                    if (remarksContainer) {
                        modalBody.insertBefore(retroNote, remarksContainer.nextSibling);
                    } else {
                        modalBody.appendChild(retroNote);
                    }
                }
            }
        }
    } else {
        // ซ่อนการแสดงผลการเช็คชื่อย้อนหลัง
        let retroactiveBadge = document.querySelector('.retroactive-badge');
        if (retroactiveBadge) {
            retroactiveBadge.style.display = 'none';
        }

        document.querySelectorAll('.retroactive-note').forEach(note => {
            note.style.display = 'none';
        });
    }
}

/**
 * ปรับปรุงช่องค้นหา
 */
function enhanceSearchInput() {
    const searchContainer = document.querySelector('.search-container');
    const searchInput = document.getElementById('searchInput');
    if (!searchContainer || !searchInput) return;

    // ตรวจสอบว่ามีปุ่มล้างการค้นหาหรือไม่
    let clearButton = searchContainer.querySelector('.search-clear');
    if (!clearButton) {
        // เพิ่มปุ่มล้างการค้นหา
        clearButton = document.createElement('span');
        clearButton.className = 'search-clear';
        clearButton.innerHTML = '<i class="fas fa-times"></i>';
        clearButton.style.display = 'none';
        searchContainer.appendChild(clearButton);

        // แสดง/ซ่อนปุ่มล้างการค้นหา
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
    }
}

/**
 * ปรับปรุงฟังก์ชันค้นหานักเรียน
 */
function improveSearchFunction() {
    // ตรวจสอบว่ามีฟังก์ชัน searchStudents อยู่แล้วหรือไม่
    if (typeof window.searchStudents !== 'function') {
        // ถ้าไม่มี ให้สร้างฟังก์ชันใหม่
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
 * เพิ่มการทำงานให้ Modal
 */
function enhanceModalFunctions() {
    // ตรวจสอบว่ามีฟังก์ชัน showModal และ closeModal อยู่แล้วหรือไม่
    if (typeof window.showModal !== 'function') {
        window.showModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนพื้นหลัง
            }
        };
    }

    if (typeof window.closeModal !== 'function') {
        window.closeModal = function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = ''; // คืนค่าการเลื่อน
            }
        };
    }

    // ผูกการทำงานของปุ่มปิดใน Modal
    document.querySelectorAll('.close-btn').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    // เพิ่มการทำงานของปุ่มยกเลิกใน Modal
    document.querySelectorAll('.modal-footer .btn.secondary').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
}

/**
 * ตรวจสอบการมีอยู่ของฟังก์ชัน confirmDetailAttendance และแก้ไขถ้าจำเป็น
 */
if (typeof window.confirmDetailAttendance !== 'function') {
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
            const retroactiveNote = retroactiveNoteInput ? retroactiveNoteInput.value : '';
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
        closeModal('attendanceDetailModal');

        // แสดงข้อความกำลังดำเนินการ
        showNotification('กำลังบันทึกข้อมูล...', 'info');

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
                    showNotification(`บันทึกสถานะ "${getStatusText(status)}" สำหรับนักเรียนเรียบร้อย`, 'success');
                } else {
                    showNotification('เกิดข้อผิดพลาด: ' + responseData.message, 'error');
                }
            })
            .catch(error => {
                showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์: ' + error.message, 'error');
                console.error('Error:', error);
            });
    };
}

/**
 * แสดงข้อความแจ้งเตือน
 */
function showNotification(message, type = 'info') {
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

    // เพิ่มไปยัง container
    container.appendChild(notification);

    // กำหนดการปิดเมื่อคลิก
    const closeButton = notification.querySelector('.notification-close');
    if (closeButton) {
        closeButton.addEventListener('click', () => {
            notification.remove();
        });
    }

    // กำหนดการปิดอัตโนมัติ
    setTimeout(() => {
        if (container.contains(notification)) {
            notification.remove();
        }
    }, 5000);
}

/**
 * ดึงข้อความสถานะ
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