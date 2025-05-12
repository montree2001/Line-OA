/**
 * users.js - ไฟล์ JavaScript สำหรับจัดการฟังก์ชันต่างๆ ในหน้าจัดการข้อมูลผู้ใช้งาน
 * ระบบ STUDENT-Prasat (น้องชูใจ AI)
 */

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    console.log("เริ่มต้นระบบจัดการผู้ใช้งาน...");
    
    // เริ่มต้น DataTable
    if (document.getElementById('userDataTable')) {
        $('#userDataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            },
            "responsive": true,
            "order": [
                [0, "desc"]
            ]
        });
    }
    
    // ตั้งค่า event listener สำหรับปุ่มต่างๆ
    setupButtonListeners();
});

/**
 * ตั้งค่า event listeners สำหรับปุ่มต่างๆ
 */
function setupButtonListeners() {
    // ปุ่มดูข้อมูล
    const viewButtons = document.querySelectorAll('.btn-info[onclick^="viewUser"]');
    viewButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const userId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            viewUser(userId);
        };
    });

    // ปุ่มแก้ไข
    const editButtons = document.querySelectorAll('.btn-warning[onclick^="editUser"]');
    editButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const userId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            editUser(userId);
        };
    });

    // ปุ่มลบ
    const deleteButtons = document.querySelectorAll('.btn-danger[onclick^="deleteUser"]');
    deleteButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const match = this.getAttribute('onclick').match(/deleteUser\('([^']+)',\s*'([^']+)'/);
            if (match) {
                const userId = match[1];
                const userName = match[2];
                deleteUser(userId, userName);
            }
        };
    });
}

/**
 * ดูข้อมูลผู้ใช้งาน
 * 
 * @param {string} userId รหัสผู้ใช้
 */
function viewUser(userId) {
    // ดึงข้อมูลผู้ใช้
    fetch(`api/users_api.php?action=get_user&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                
                // แสดงข้อมูลใน Modal
                // ตรวจสอบว่ามีรูปโปรไฟล์ LINE หรือไม่
                const avatarContainer = document.getElementById('view_avatar');
                
                if (user.profile_picture) {
                    // ถ้ามีรูปโปรไฟล์ ให้แสดงรูป
                    avatarContainer.innerHTML = `<img src="${user.profile_picture}" alt="${user.first_name}" class="profile-image">`;
                    avatarContainer.classList.add('has-profile-image');
                } else {
                    // ถ้าไม่มีรูปโปรไฟล์ ให้แสดงตัวอักษรตัวแรกของชื่อ
                    avatarContainer.textContent = user.first_name.charAt(0).toUpperCase();
                    avatarContainer.classList.remove('has-profile-image');
                }
                
                // ข้อมูลส่วนตัว
                document.getElementById('view_full_name').innerText = `${user.title} ${user.first_name} ${user.last_name}`;
                
                // แปลงบทบาทเป็นภาษาไทย
                let roleText = '';
                switch (user.role) {
                    case 'student':
                        roleText = 'นักเรียน';
                        break;
                    case 'teacher':
                        roleText = 'ครู';
                        break;
                    case 'parent':
                        roleText = 'ผู้ปกครอง';
                        break;
                    case 'admin':
                        roleText = 'ผู้ดูแลระบบ';
                        break;
                    default:
                        roleText = user.role;
                }
                
                document.getElementById('view_role').innerText = roleText;
                
                // แสดงรหัสนักเรียน (ถ้ามี)
                const userCodeElement = document.getElementById('view_user_code');
                if (user.role === 'student' && user.student_code) {
                    userCodeElement.innerText = `รหัสนักเรียน: ${user.student_code}`;
                    userCodeElement.style.display = 'block';
                } else {
                    userCodeElement.style.display = 'none';
                }
                
                // ข้อมูลติดต่อ
                document.querySelector('#view_phone span').innerText = user.phone_number || '-';
                document.querySelector('#view_email span').innerText = user.email || '-';
                
                // แสดงสถานะการเชื่อมต่อ LINE
                const lineStatusElement = document.querySelector('#view_line span');
                if (user.line_id && !user.line_id.startsWith('TEMP_')) {
                    lineStatusElement.innerHTML = '<span class="line-status connected"><span class="material-icons">check_circle</span> เชื่อมต่อแล้ว</span><br>' + user.line_id;
                } else {
                    lineStatusElement.innerHTML = '<span class="line-status not-connected"><span class="material-icons">highlight_off</span> ยังไม่ได้เชื่อมต่อ</span>';
                }
                
                // ข้อมูลบัญชี
                document.querySelector('#view_created span').innerText = new Date(user.created_at).toLocaleString('th-TH');
                document.querySelector('#view_last_login span').innerText = user.last_login ? new Date(user.last_login).toLocaleString('th-TH') : '-';
                document.querySelector('#view_consent span').innerText = user.gdpr_consent ? 'ยินยอมแล้ว' : 'ยังไม่ได้ยินยอม';
                
                // ตั้งค่าปุ่มแก้ไข
                document.getElementById('edit_btn').onclick = () => {
                    closeModal('viewUserModal');
                    editUser(userId);
                };
                
                // ตั้งค่าปุ่มรีเซ็ต LINE
                const resetLineBtn = document.getElementById('reset_line_btn');
                if (user.line_id && !user.line_id.startsWith('TEMP_')) {
                    resetLineBtn.style.display = 'block';
                    resetLineBtn.onclick = () => {
                        closeModal('viewUserModal');
                        resetLineConnection(userId, `${user.title} ${user.first_name} ${user.last_name}`);
                    };
                } else {
                    resetLineBtn.style.display = 'none';
                }
                
                // แสดง Modal
                showModal('viewUserModal');
            } else {
                showAlert(data.message || 'ไม่สามารถดึงข้อมูลผู้ใช้ได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้', 'error');
        });
}

/**
 * แก้ไขข้อมูลผู้ใช้งาน
 * 
 * @param {string} userId รหัสผู้ใช้
 */
function editUser(userId) {
    // ดึงข้อมูลผู้ใช้
    fetch(`api/users_api.php?action=get_user&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                
                // เติมข้อมูลในฟอร์ม
                document.getElementById('edit_user_id').value = user.user_id;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_title').value = user.title || '';
                document.getElementById('edit_firstname').value = user.first_name || '';
                document.getElementById('edit_lastname').value = user.last_name || '';
                document.getElementById('edit_phone_number').value = user.phone_number || '';
                document.getElementById('edit_email').value = user.email || '';
                
                // ถ้าเป็นนักเรียน แสดงฟิลด์รหัสนักเรียน
                const studentCodeField = document.getElementById('edit_student_code');
                const studentOnlyFields = document.querySelectorAll('.student-only-field');
                
                if (user.role === 'student') {
                    studentCodeField.value = user.student_code || '';
                    studentCodeField.required = true;
                    studentOnlyFields.forEach(field => field.style.display = 'block');
                } else {
                    studentCodeField.value = '';
                    studentCodeField.required = false;
                    studentOnlyFields.forEach(field => field.style.display = 'none');
                }
                
                // แสดง Modal
                showModal('editUserModal');
            } else {
                showAlert(data.message || 'ไม่สามารถดึงข้อมูลผู้ใช้ได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลผู้ใช้', 'error');
        });
}

/**
 * ลบข้อมูลผู้ใช้งาน
 * 
 * @param {string} userId รหัสผู้ใช้
 * @param {string} userName ชื่อผู้ใช้
 */
function deleteUser(userId, userName) {
    // แสดงชื่อผู้ใช้ที่จะลบใน Modal
    document.getElementById('delete_user_name').innerText = userName;
    document.getElementById('delete_user_id').value = userId;
    
    // แสดง Modal
    showModal('deleteUserModal');
}

/**
 * รีเซ็ตการเชื่อมต่อ LINE
 * 
 * @param {string} userId รหัสผู้ใช้
 * @param {string} userName ชื่อผู้ใช้
 */
function resetLineConnection(userId, userName) {
    // แสดงชื่อผู้ใช้ที่จะรีเซ็ตใน Modal
    document.getElementById('reset_line_user_name').innerText = userName;
    document.getElementById('reset_line_user_id').value = userId;
    
    // แสดง Modal
    showModal('resetLineModal');
}

/**
 * แสดง Modal
 * 
 * @param {string} modalId รหัส Modal
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }
}

/**
 * ปิด Modal
 * 
 * @param {string} modalId รหัส Modal
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

/**
 * แสดงข้อความแจ้งเตือน
 * 
 * @param {string} message ข้อความ
 * @param {string} type ประเภท (success, error, warning, info)
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span class="alert-message">${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">
            <span class="material-icons">close</span>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    // ลบข้อความแจ้งเตือนอัตโนมัติหลังจาก 5 วินาที
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}