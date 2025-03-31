/**
 * students.js - ไฟล์ JavaScript สำหรับจัดการฟังก์ชันต่างๆ ในหน้าจัดการข้อมูลนักเรียน
 * ระบบ STUDENT-Prasat
 */

// เรียกใช้เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    // เริ่มต้น DataTable
    if (document.getElementById('studentDataTable')) {
        $('#studentDataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Thai.json"
            },
            "responsive": true,
            "order": [[0, "asc"]] // เรียงตามรหัสนักเรียน
        });
    }

    // เพิ่ม event listener สำหรับปุ่มต่างๆ
    setupButtonListeners();

    // เริ่มต้นดาต้าลิสต์สำหรับช่องค้นหาห้องเรียน
    initClassDatalist();
});



/**
 * ปรับปรุงส่วนเริ่มต้นส่วนเลือกชั้นเรียน
 * function นี้ควรแทนที่ฟังก์ชัน initClassDatalist() เดิม
 */
function initClassSelection() {
    console.log("Initializing class selection...");
    
    // ตรวจสอบว่ามีแบบฟอร์มเพิ่มนักเรียนหรือไม่
    const addForm = document.getElementById('addStudentForm');
    if (!addForm) {
        console.log("Add student form not found!");
        return;
    }
    
    // ตรวจสอบส่วนเลือกชั้นเรียนแบบ datalist
    const classSearchInput = document.getElementById('class_search');
    const classIdInput = document.getElementById('class_id');
    
    // ตรวจสอบหรือสร้าง datalist
    let classList = document.getElementById('classList');
    if (!classList) {
        classList = document.createElement('datalist');
        classList.id = 'classList';
        document.body.appendChild(classList);
        console.log("Created classList datalist");
    }
    
    // หรือตรวจสอบและใช้ dropdown แบบ select
    const classSelectDropdown = document.getElementById('class_select');
    
    // ดึงข้อมูลห้องเรียน
    console.log("Fetching class data...");
    fetch('api/classes_api.php?action=get_classes_with_advisors')
        .then(response => {
            console.log("API response received:", response.status);
            return response.json();
        })
        .then(data => {
            console.log("Class data received:", data);
            if (data.success && data.classes) {
                // แสดงผลจำนวนชั้นเรียนที่ได้รับ
                console.log(`Received ${data.classes.length} classes`);
                
                // ถ้ามี datalist input 
                if (classSearchInput && classIdInput && classList) {
                    // เติมข้อมูลใน datalist
                    data.classes.forEach(cls => {
                        const option = document.createElement('option');
                        const levelName = cls.levelName || cls.level;
                        const displayText = `${levelName}/${cls.groupNumber} ${cls.departmentName}`;
                        option.value = displayText;
                        option.setAttribute('data-id', cls.classId);
                        classList.appendChild(option);
                    });
                    
                    // เพิ่ม event listener
                    classSearchInput.addEventListener('input', function() {
                        // ค้นหาห้องเรียนจากข้อความที่ป้อน
                        const options = Array.from(classList.options);
                        const found = options.find(option => option.value === this.value);
                        
                        if (found) {
                            classIdInput.value = found.getAttribute('data-id');
                            console.log("Selected class ID:", classIdInput.value);
                        } else {
                            classIdInput.value = '';
                        }
                    });
                    
                    console.log("Datalist initialized with class data");
                } 
                // ถ้ามี dropdown select
                else if (classSelectDropdown) {
                    // เคลียร์ตัวเลือกเดิม (ยกเว้นตัวเลือกแรก)
                    while (classSelectDropdown.options.length > 1) {
                        classSelectDropdown.remove(1);
                    }
                    
                    // เติมข้อมูลใน dropdown
                    data.classes.forEach(cls => {
                        const option = document.createElement('option');
                        const levelName = cls.levelName || cls.level;
                        option.value = cls.classId;
                        option.text = `${levelName}/${cls.groupNumber} ${cls.departmentName}`;
                        classSelectDropdown.appendChild(option);
                    });
                    
                    console.log("Dropdown select initialized with class data");
                }
                // หากทั้งสองแบบไม่มี ลองหา select ปกติที่มี ID เป็น 'class_id'
                else if (document.getElementById('class_id') && document.getElementById('class_id').tagName === 'SELECT') {
                    const classSelect = document.getElementById('class_id');
                    
                    // เคลียร์ตัวเลือกเดิม (ยกเว้นตัวเลือกแรก)
                    while (classSelect.options.length > 1) {
                        classSelect.remove(1);
                    }
                    
                    // เติมข้อมูลใน select
                    data.classes.forEach(cls => {
                        const option = document.createElement('option');
                        const levelName = cls.levelName || cls.level;
                        option.value = cls.classId;
                        option.text = `${levelName}/${cls.groupNumber} ${cls.departmentName}`;
                        classSelect.appendChild(option);
                    });
                    
                    console.log("Regular select initialized with class data");
                } else {
                    console.warn("No suitable class selection element found!");
                    // ตรวจสอบว่ามีอะไรใน DOM บ้าง
                    console.log("Available elements:");
                    console.log("- classList datalist:", document.getElementById('classList'));
                    console.log("- class_search input:", document.getElementById('class_search'));
                    console.log("- class_id input:", document.getElementById('class_id'));
                    console.log("- class_select dropdown:", document.getElementById('class_select'));
                }
            } else {
                console.error("Failed to fetch class data:", data.message || "Unknown error");
            }
        })
        .catch(error => {
            console.error("Error fetching class data:", error);
        });
}

// เมื่อโหลด DOM เสร็จสมบูรณ์
document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM fully loaded");
    
    // เรียกใช้ฟังก์ชันสำหรับการเลือกชั้นเรียน
    initClassSelection();
    
    // เพิ่ม event listener สำหรับปุ่มเพิ่มนักเรียนใหม่เพื่อให้แน่ใจว่า datalist ทำงาน
    const addButtons = document.querySelectorAll('.btn-primary[onclick="showAddStudentModal()"]');
    addButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            console.log("Add student button clicked");
            e.preventDefault();
            showAddStudentModal();
            
            // รอสักครู่ให้ Modal แสดงและตรวจสอบว่าส่วนเลือกชั้นเรียนทำงานหรือไม่
            setTimeout(() => {
                if (document.getElementById('classList') && 
                    document.getElementById('classList').options.length === 0) {
                    console.log("Re-initializing class selection...");
                    initClassSelection();
                }
            }, 500);
        });
    });
});

/**
 * แสดง Modal เพิ่มนักเรียนใหม่
 */
function showAddStudentModal() {
    console.log("Showing add student modal");
    
    // รีเซ็ตฟอร์ม
    const form = document.getElementById('addStudentForm');
    if (form) {
        form.reset();
    } else {
        console.error("Add student form not found!");
    }
    
    // แสดง Modal
    const modal = document.getElementById('addStudentModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        
        // ตรวจสอบว่า datalist หรือ select ทำงานหรือไม่
        setTimeout(() => {
            console.log("Checking class selection elements...");
            const classList = document.getElementById('classList');
            const classSearchInput = document.getElementById('class_search');
            const classIdInput = document.getElementById('class_id');
            
            console.log("- classList:", classList);
            console.log("- class_search:", classSearchInput);
            console.log("- class_id:", classIdInput);
            
            if (classList && (!classList.options || classList.options.length === 0)) {
                console.log("Class list is empty, re-initializing...");
                initClassSelection();
            }
        }, 300);
    } else {
        console.error("Add student modal not found!");
    }
}



/**
 * ตั้งค่า event listeners สำหรับปุ่มต่างๆ
 */
function setupButtonListeners() {
    // ปุ่มเพิ่มนักเรียนใหม่
    const addButtons = document.querySelectorAll('.btn-primary[onclick="showAddStudentModal()"]');
    addButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            showAddStudentModal();
        };
    });

    // ปุ่มนำเข้าข้อมูล
    const importButtons = document.querySelectorAll('.btn-success[onclick="showImportModal()"]');
    importButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            showImportModal();
        };
    });

    // ปุ่มสร้าง QR LINE
    const qrButtons = document.querySelectorAll('.btn-line[onclick^="generateLineQR"]');
    qrButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            generateLineQR(studentId);
        };
    });

    // ปุ่มดูข้อมูล
    const viewButtons = document.querySelectorAll('.btn-info[onclick^="viewStudent"]');
    viewButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            viewStudent(studentId);
        };
    });

    // ปุ่มแก้ไข
    const editButtons = document.querySelectorAll('.btn-warning[onclick^="editStudent"]');
    editButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const studentId = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            editStudent(studentId);
        };
    });

    // ปุ่มลบ
    const deleteButtons = document.querySelectorAll('.btn-danger[onclick^="deleteStudent"]');
    deleteButtons.forEach(button => {
        button.onclick = function(e) {
            e.preventDefault();
            const match = this.getAttribute('onclick').match(/deleteStudent\('([^']+)',\s*'([^']+)'/);
            if (match) {
                const studentId = match[1];
                const studentName = match[2];
                deleteStudent(studentId, studentName);
            }
        };
    });

    // เช็คปุ่มพิมพ์และดาวน์โหลด Excel
    const printButton = document.querySelector('button[onclick="printStudentList()"]');
    if (printButton) {
        printButton.onclick = function(e) {
            e.preventDefault();
            printStudentList();
        };
    }

    const downloadButton = document.querySelector('button[onclick="downloadExcel()"]');
    if (downloadButton) {
        downloadButton.onclick = function(e) {
            e.preventDefault();
            downloadExcel();
        };
    }
}

/**
 * แสดง Modal เพิ่มนักเรียนใหม่
 */
function showAddStudentModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('addStudentForm').reset();
    
    // แสดง Modal
    showModal('addStudentModal');
}

/**
 * แสดง Modal นำเข้าข้อมูล
 */
function showImportModal() {
    // รีเซ็ตฟอร์ม
    document.getElementById('importForm').reset();
    
    // แสดง Modal
    showModal('importModal');
}

/**
 * ดูข้อมูลนักเรียน
 * 
 * @param {string} studentId รหัสนักเรียน
 */
function viewStudent(studentId) {
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                
                // แสดงข้อมูลใน Modal
                document.getElementById('view_avatar').innerText = student.first_name.charAt(0).toUpperCase();
                document.getElementById('view_full_name').innerText = `${student.title}${student.first_name} ${student.last_name}`;
                document.getElementById('view_student_code').innerText = student.student_code;
                document.getElementById('view_class').innerText = student.class || '-';
                
                document.querySelector('#view_phone span').innerText = student.phone_number || '-';
                document.querySelector('#view_email span').innerText = student.email || '-';
                document.querySelector('#view_line span').innerText = student.line_connected ? 'เชื่อมต่อแล้ว' : 'ยังไม่ได้เชื่อมต่อ';
                
                document.querySelector('#view_advisor span').innerText = student.advisor_name || '-';
                document.querySelector('#view_department span').innerText = student.department_name || '-';
                document.querySelector('#view_status span').innerText = student.status;
                
                document.querySelector('#view_attendance_status span').innerText = student.attendance_status;
                document.getElementById('view_attendance_days').innerText = student.attendance_days;
                document.getElementById('view_absence_days').innerText = student.absence_days;
                document.getElementById('view_attendance_rate').innerText = student.attendance_rate.toFixed(1) + '%';
                
                // ตั้งค่าปุ่มแก้ไขและสร้าง QR
                document.getElementById('edit_btn').onclick = () => {
                    closeModal('viewStudentModal');
                    editStudent(studentId);
                };
                
                document.getElementById('generate_qr_btn').onclick = () => {
                    closeModal('viewStudentModal');
                    generateLineQR(studentId);
                };
                
                // แสดง Modal
                showModal('viewStudentModal');
            } else {
                showAlert(data.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน', 'error');
        });
}

/**
 * แก้ไขข้อมูลนักเรียน
 * 
 * @param {string} studentId รหัสนักเรียน
 */
function editStudent(studentId) {
    // ดึงข้อมูลนักเรียน
    fetch(`api/students_api.php?action=get_student&student_id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                
                // เติมข้อมูลในฟอร์ม
                document.getElementById('edit_student_id').value = student.student_id;
                document.getElementById('edit_title').value = student.title;
                document.getElementById('edit_firstname').value = student.first_name;
                document.getElementById('edit_lastname').value = student.last_name;
                document.getElementById('edit_student_code').value = student.student_code;
                document.getElementById('edit_phone_number').value = student.phone_number || '';
                document.getElementById('edit_email').value = student.email || '';
                document.getElementById('edit_class_id').value = student.class_id || '';
                document.getElementById('edit_status').value = student.status;
                
                // แสดง Modal
                showModal('editStudentModal');
            } else {
                showAlert(data.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching student data:', error);
            showAlert('เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน', 'error');
        });
}

/**
 * ลบข้อมูลนักเรียน
 * 
 * @param {string} studentId รหัสนักเรียน
 * @param {string} studentName ชื่อนักเรียน
 */
function deleteStudent(studentId, studentName) {
    // แสดงชื่อนักเรียนที่จะลบใน Modal
    document.getElementById('delete_student_name').innerText = studentName;
    document.getElementById('delete_student_id').value = studentId;
    
    // แสดง Modal
    showModal('deleteStudentModal');
}

/**
 * สร้าง QR Code สำหรับเชื่อมต่อกับ LINE
 * 
 * @param {string} studentId รหัสนักเรียน
 */
function generateLineQR(studentId) {
    // แสดง Modal และ loading
    showModal('lineQRModal');
    document.getElementById('qrcode-image').src = 'assets/images/loading.gif';
    document.getElementById('qr-expire-time').innerText = 'กำลังสร้าง QR Code...';
    document.getElementById('line-status-text').innerHTML = '<span class="status-badge warning">กำลังตรวจสอบ...</span>';
    
    // สร้าง QR Code
    const formData = new FormData();
    formData.append('action', 'generate_qr_code');
    formData.append('student_id', studentId);
    
    fetch('api/line_connect_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // แสดง QR Code
            document.getElementById('qrcode-image').src = data.qr_code_url;
            document.getElementById('qr-expire-time').innerText = new Date(data.expire_time).toLocaleString();
            document.getElementById('line-connect-url').href = data.line_connect_url;
            document.getElementById('line-status-text').innerHTML = '<span class="status-badge warning">รอการเชื่อมต่อ</span>';
        } else {
            document.getElementById('qrcode-image').src = 'assets/images/error.png';
            document.getElementById('qr-expire-time').innerText = '-';
            document.getElementById('line-status-text').innerHTML = '<span class="status-badge danger">เกิดข้อผิดพลาด</span>';
            showAlert(data.message || 'ไม่สามารถสร้าง QR Code ได้', 'error');
        }
    })
    .catch(error => {
        console.error('Error generating QR code:', error);
        document.getElementById('qrcode-image').src = 'assets/images/error.png';
        document.getElementById('qr-expire-time').innerText = '-';
        document.getElementById('line-status-text').innerHTML = '<span class="status-badge danger">เกิดข้อผิดพลาด</span>';
        showAlert('เกิดข้อผิดพลาดในการสร้าง QR Code', 'error');
    });
}

/**
 * ตรวจสอบสถานะการเชื่อมต่อ LINE
 */
function checkLineStatus() {
    const studentId = document.getElementById('line-status-check').getAttribute('data-student-id');
    if (!studentId) {
        showAlert('ไม่พบรหัสนักเรียน', 'error');
        return;
    }
    
    // แสดงสถานะกำลังตรวจสอบ
    document.getElementById('line-status-text').innerHTML = '<span class="status-badge warning">กำลังตรวจสอบ...</span>';
    
    // ตรวจสอบสถานะ
    const formData = new FormData();
    formData.append('action', 'check_line_status');
    formData.append('student_id', studentId);
    
    fetch('api/line_connect_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.is_connected) {
                document.getElementById('line-status-text').innerHTML = '<span class="status-badge success">เชื่อมต่อแล้ว</span>';
                // รีโหลดหน้าหลังจาก 2 วินาที
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                document.getElementById('line-status-text').innerHTML = '<span class="status-badge warning">ยังไม่ได้เชื่อมต่อ</span>';
            }
        } else {
            document.getElementById('line-status-text').innerHTML = '<span class="status-badge danger">เกิดข้อผิดพลาด</span>';
            showAlert(data.message || 'ไม่สามารถตรวจสอบสถานะได้', 'error');
        }
    })
    .catch(error => {
        console.error('Error checking LINE status:', error);
        document.getElementById('line-status-text').innerHTML = '<span class="status-badge danger">เกิดข้อผิดพลาด</span>';
        showAlert('เกิดข้อผิดพลาดในการตรวจสอบสถานะ', 'error');
    });
}

/**
 * พิมพ์รายชื่อนักเรียน
 */
function printStudentList() {
    // สร้างหน้าเพื่อพิมพ์
    const printWindow = window.open('', '_blank');
    const table = document.getElementById('studentDataTable');
    
    if (!table) {
        showAlert('ไม่พบตารางข้อมูลนักเรียน', 'error');
        return;
    }
    
    // สร้าง HTML สำหรับพิมพ์
    let printContent = `
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>รายชื่อนักเรียน</title>
            <style>
                body {
                    font-family: 'Prompt', sans-serif;
                    padding: 20px;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .print-footer {
                    text-align: center;
                    margin-top: 30px;
                    font-size: 12px;
                    color: #666;
                }
                @media print {
                    @page {
                        size: A4 landscape;
                        margin: 0.5cm;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>รายชื่อนักเรียน</h1>
                <p>วิทยาลัยการอาชีพปราสาท</p>
                <p>วันที่พิมพ์: ${new Date().toLocaleDateString('th-TH')}</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>รหัสนักศึกษา</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>ชั้น/ห้อง</th>
                        <th>แผนกวิชา</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    // คัดลอกข้อมูลจากตารางเดิม
    const rows = table.querySelectorAll('tbody tr');
    let index = 1;
    
    rows.forEach(row => {
        const studentCode = row.querySelector('td:nth-child(1)').innerText;
        const nameCell = row.querySelector('td:nth-child(2)');
        const studentName = nameCell.querySelector('.student-name').innerText;
        const classInfo = row.querySelector('td:nth-child(3)').innerText;
        const department = row.querySelector('td:nth-child(4)').innerText;
        const status = row.querySelector('td:nth-child(7) .status-badge').innerText;
        
        printContent += `
            <tr>
                <td>${index}</td>
                <td>${studentCode}</td>
                <td>${studentName}</td>
                <td>${classInfo}</td>
                <td>${department}</td>
                <td>${status}</td>
            </tr>
        `;
        
        index++;
    });
    
    printContent += `
                </tbody>
            </table>
            
            <div class="print-footer">
                <p>ระบบเช็คชื่อเข้าแถว STUDENT-Prasat</p>
            </div>
            
            <div class="no-print" style="text-align: center; margin-top: 20px;">
                <button onclick="window.print()">พิมพ์</button>
                <button onclick="window.close()">ปิด</button>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}

/**
 * ดาวน์โหลดข้อมูลเป็นไฟล์ Excel
 */
function downloadExcel() {
    // สร้าง URL สำหรับดาวน์โหลด
    let url = 'api/export_students.php';
    
    // เพิ่มพารามิเตอร์การค้นหา
    const urlParams = new URLSearchParams(window.location.search);
    const searchParams = ['name', 'student_code', 'level', 'group_number', 'department_id', 'status', 'attendance_status', 'line_status'];
    
    let hasParams = false;
    
    searchParams.forEach(param => {
        if (urlParams.has(param)) {
            if (!hasParams) {
                url += '?';
                hasParams = true;
            } else {
                url += '&';
            }
            url += `${param}=${encodeURIComponent(urlParams.get(param))}`;
        }
    });
    
    // เปิด URL ในแท็บใหม่
    window.open(url, '_blank');
}

/**
 * เริ่มต้นดาต้าลิสต์สำหรับช่องค้นหาห้องเรียน
 */
function initClassDatalist() {
    const classSearchInput = document.getElementById('class_search');
    const classIdInput = document.getElementById('class_id');
    const classList = document.getElementById('classList');
    
    if (!classSearchInput || !classIdInput || !classList) {
        return;
    }
    
    // ดึงข้อมูลห้องเรียน
    fetch('api/classes_api.php?action=get_classes_with_advisors')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.classes) {
                // เติมข้อมูลในดาต้าลิสต์
                data.classes.forEach(cls => {
                    const option = document.createElement('option');
                    const levelName = cls.levelName || cls.level;
                    const displayText = `${levelName}/${cls.groupNumber} ${cls.departmentName}`;
                    option.value = displayText;
                    option.setAttribute('data-id', cls.classId);
                    classList.appendChild(option);
                });
                
                // เพิ่ม event listener
                classSearchInput.addEventListener('input', function() {
                    // ค้นหาห้องเรียนจากข้อความที่ป้อน
                    const options = Array.from(classList.options);
                    const found = options.find(option => option.value === this.value);
                    
                    if (found) {
                        classIdInput.value = found.getAttribute('data-id');
                    } else {
                        classIdInput.value = '';
                    }
                });
            }
        })
        .catch(error => {
            console.error('Error fetching class data:', error);
        });
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
        
        // เพิ่มพารามิเตอร์สำหรับ QR Code
        if (modalId === 'lineQRModal') {
            const studentId = document.querySelector('button[onclick^="generateLineQR"]')
                .getAttribute('onclick')
                .match(/'([^']+)'/)[1];
                
            document.getElementById('line-status-check').setAttribute('data-student-id', studentId);
        }
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