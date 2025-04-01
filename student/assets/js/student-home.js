/**
 * student-home.js - JavaScript สำหรับหน้าหลักของนักเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // ปุ่มเช็คชื่อ
    const checkInButton = document.querySelector('.check-in-button');
    if (checkInButton) {
        checkInButton.addEventListener('click', function(e) {
            if (this.disabled) {
                e.preventDefault();
                showMessage('คุณได้เช็คชื่อไปแล้วในวันนี้', 'info');
            } else {
                // ไปยังหน้าเช็คชื่อ
                window.location.href = 'check-in.php';
            }
        });
    }

    // ตรวจสอบสถานะการเช็คชื่อ
    checkAttendanceStatus();

    // ปุ่มเมนูผู้ใช้
    const userMenuToggle = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenuToggle && userDropdown) {
        userMenuToggle.addEventListener('click', function() {
            userDropdown.classList.toggle('active');
        });

        // ปิดเมนูเมื่อคลิกที่อื่น
        document.addEventListener('click', function(e) {
            if (!userMenuToggle.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
    }
});

/**
 * ตรวจสอบสถานะการเช็คชื่อ
 * ตรวจสอบว่านักเรียนได้เช็คชื่อแล้วหรือยัง
 */
function checkAttendanceStatus() {
    // ในกรณีจริง จะใช้ AJAX เพื่อดึงข้อมูลจาก server
    // แต่ในส่วนนี้ข้อมูลถูกส่งมาจาก PHP แล้ว
    const profileStatus = document.querySelector('.profile-status');
    const checkInButton = document.querySelector('.check-in-button');

    // สถานะถูกกำหนดจาก PHP แล้ว จึงไม่ต้องทำอะไรเพิ่มเติม
    // แต่เราอาจมีการตรวจสอบซ้ำทุกๆ ช่วงเวลาหนึ่ง

    // ตัวอย่างการอัพเดทสถานะทุก 5 นาที
    setInterval(function() {
        refreshAttendanceStatus();
    }, 5 * 60 * 1000);
}

/**
 * แสดงข้อความแจ้งเตือน
 */
function showMessage(message, type = 'success') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;

    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type}`;
    alertBox.innerHTML = `
        <span class="material-icons">${type === 'success' ? 'check_circle' : 'info'}</span>
        <span class="alert-message">${message}</span>
        <button type="button" class="close-alert">
            <span class="material-icons">close</span>
        </button>
    `;

    alertContainer.appendChild(alertBox);

    // เพิ่ม event listener สำหรับปุ่มปิด
    const closeButton = alertBox.querySelector('.close-alert');
    if (closeButton) {
        closeButton.addEventListener('click', function() {
            alertBox.remove();
        });
    }

    // ซ่อนข้อความหลังจาก 5 วินาที
    setTimeout(function() {
        if (alertBox.parentNode) {
            alertBox.classList.add('fade-out');
            setTimeout(() => alertBox.remove(), 500);
        }
    }, 5000);
}

/**
 * อัพเดทสถานะการเช็คชื่อผ่าน AJAX
 */
function refreshAttendanceStatus() {
    fetch('api/check_attendance_status.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const profileStatus = document.querySelector('.profile-status');
                const checkInButton = document.querySelector('.check-in-button');

                if (data.is_checked_in) {
                    // กรณีเช็คชื่อแล้ว
                    if (profileStatus) {
                        profileStatus.className = 'profile-status status-present';
                        profileStatus.innerHTML = '<span class="material-icons">check_circle</span> เข้าแถวแล้ววันนี้';
                    }

                    if (checkInButton) {
                        checkInButton.disabled = true;
                        checkInButton.style.backgroundColor = '#cccccc';
                        checkInButton.innerHTML = '<span class="material-icons">check_circle</span> เช็คชื่อแล้ววันนี้';
                    }
                } else {
                    // กรณียังไม่ได้เช็คชื่อ
                    if (profileStatus) {
                        profileStatus.className = 'profile-status status-absent';
                        profileStatus.innerHTML = '<span class="material-icons">cancel</span> ยังไม่ได้เข้าแถววันนี้';
                    }

                    if (checkInButton) {
                        checkInButton.disabled = false;
                        checkInButton.style.backgroundColor = '#06c755';
                        checkInButton.innerHTML = '<span class="material-icons">how_to_reg</span> เช็คชื่อเข้าแถววันนี้';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing attendance status:', error);
        });
}