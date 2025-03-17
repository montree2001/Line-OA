/**
 * student-home.js - JavaScript สำหรับหน้าหลักของนักเรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // ปุ่มเช็คชื่อ
    const checkInButton = document.querySelector('.check-in-button');
    if (checkInButton) {
        checkInButton.addEventListener('click', function() {
            window.location.href = 'check-in.php';
        });
    }

    // ตรวจสอบสถานะการเช็คชื่อ
    checkAttendanceStatus();
});

/**
 * ตรวจสอบสถานะการเช็คชื่อ
 * ตรวจสอบว่านักเรียนได้เช็คชื่อแล้วหรือยัง
 */
function checkAttendanceStatus() {
    // ในกรณีจริง จะใช้ AJAX เพื่อดึงข้อมูลจาก server
    // แต่ในตัวอย่างนี้ใช้ข้อมูลจำลอง

    // สมมติว่าเช็คชื่อแล้ว
    const hasAttended = true;
    const profileStatus = document.querySelector('.profile-status');
    const checkInButton = document.querySelector('.check-in-button');

    if (hasAttended) {
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
            checkInButton.innerHTML = '<span class="material-icons">how_to_reg</span> เช็คชื่อเข้าแถววันนี้';
        }
    }
}