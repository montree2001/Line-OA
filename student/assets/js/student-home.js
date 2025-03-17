// สำหรับการจัดการการคลิกปุ่มต่างๆ และฟังก์ชันเสริม
document.addEventListener('DOMContentLoaded', () => {
    const checkInButton = document.querySelector('.check-in-button');
    if (checkInButton) {
        checkInButton.addEventListener('click', () => {
            window.location.href = 'check-in.php';
        });
    }
});