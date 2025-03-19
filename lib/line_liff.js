// กำหนด LIFF ID
const liffId = "2007088707-5EJ0XDlr"; // ต้องแทนที่ด้วย LIFF ID จริงของคุณ

// เริ่มต้น LIFF
function initializeLiff() {
    liff.init({
        liffId: liffId
    }).then(() => {
        // ตรวจสอบว่าเปิดใน LINE หรือไม่
        if (liff.isLoggedIn()) {
            // ผู้ใช้ล็อกอินแล้ว
            startApp();
        } else {
            // ผู้ใช้ยังไม่ได้ล็อกอิน
            liff.login();
        }
    }).catch((err) => {
        console.error('LIFF Initialization failed', err);
    });
}

// เริ่มต้นแอปหลังจากล็อกอินแล้ว
function startApp() {
    // ดึงข้อมูลโปรไฟล์
    liff.getProfile().then(profile => {
        const userId = profile.userId;
        const displayName = profile.displayName;
        const pictureUrl = profile.pictureUrl;
        
        // ส่งข้อมูลไปยังเซิร์ฟเวอร์เพื่อล็อกอินหรือลงทะเบียน
        sendProfileToServer(userId, displayName, pictureUrl);
        
    }).catch((err) => {
        console.error('getProfile failed', err);
    });
}

// ส่งข้อมูลโปรไฟล์ไปยังเซิร์ฟเวอร์
function sendProfileToServer(userId, displayName, pictureUrl) {
    // รับบทบาทที่เลือกจากหน้า
    const role = getSelectedRole();
    
    // สร้างข้อมูลสำหรับส่ง
    const data = {
        line_id: userId,
        display_name: displayName,
        picture_url: pictureUrl,
        role: role
    };
    
    // ส่งข้อมูลไปยัง API
    fetch('api/line_auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // เปลี่ยนเส้นทางไปยังหน้าที่เหมาะสม
            window.location.href = data.redirect_url;
        } else {
            // แสดงข้อความข้อผิดพลาด
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
    });
}

// รับบทบาทที่เลือก
function getSelectedRole() {
    const selectedRole = document.querySelector('.role-card.selected');
    
    if (selectedRole.querySelector('.role-name').textContent === 'ครูที่ปรึกษา') {
        return 'teacher';
    } else if (selectedRole.querySelector('.role-name').textContent === 'ผู้ปกครอง') {
        return 'parent';
    } else {
        return 'student';
    }
}

// เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้น LIFF
    initializeLiff();
    
    // เพิ่มการฟังก์ชันเหตุการณ์ให้กับปุ่มล็อกอิน
    document.querySelector('.login-button').addEventListener('click', function() {
        if (liff.isLoggedIn()) {
            startApp();
        } else {
            liff.login();
        }
    });
});

// ฟังก์ชันล็อกเอาต์
function logout() {
    if (liff.isLoggedIn()) {
        liff.logout();
        window.location.reload();
    }
}