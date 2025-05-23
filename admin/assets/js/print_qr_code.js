/**
 * print_qr_code.js - JavaScript สำหรับหน้าพิมพ์ QR Code
 * ระบบน้องชูใจ AI ดูแลผู้เรียน
 */

document.addEventListener('DOMContentLoaded', function() {
    // จัดการการเลือกทั้งหมด
    const selectAllBtn = document.getElementById('selectAllBtn');
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    const generateQRBtn = document.getElementById('generateQRBtn');
    const studentCheckboxes = document.querySelectorAll('.student-checkbox');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateGenerateButtonState();
        });
    }
    
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            studentCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateGenerateButtonState();
        });
    }
    
    // เชื่อมโยงวันที่หมดอายุกับอายุการใช้งาน
    const qrValidityInputs = document.querySelectorAll('[id^="qr_validity"]');
    const startDateInputs = document.querySelectorAll('[id^="start_date"]');
    const expiryDateInputs = document.querySelectorAll('[id^="expiry_date"]');
    
    // ฟังก์ชั่นสำหรับคำนวณวันหมดอายุ
    function calculateExpiryDate(startDateInput, validityInput, expiryDateInput) {
        if (expiryDateInput.value) return; // ถ้ามีการกำหนดวันหมดอายุแล้ว ไม่ต้องคำนวณ
        
        const startDate = new Date(startDateInput.value);
        const validity = parseInt(validityInput.value);
        
        if (startDate && !isNaN(validity)) {
            const expiryDate = new Date(startDate);
            expiryDate.setDate(expiryDate.getDate() + validity);
            
            // ตั้งค่า min ของ expiryDate
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            expiryDateInput.min = tomorrow.toISOString().split('T')[0];
            
            // แสดงวันหมดอายุที่คำนวณได้ใน placeholder
            expiryDateInput.placeholder = expiryDate.toISOString().split('T')[0];
        }
    }
    
    // เพิ่ม event listener ให้กับแต่ละชุด input
    if (qrValidityInputs.length > 0 && startDateInputs.length > 0 && expiryDateInputs.length > 0) {
        for (let i = 0; i < qrValidityInputs.length; i++) {
            const validityInput = qrValidityInputs[i];
            const startDateInput = startDateInputs[i];
            const expiryDateInput = expiryDateInputs[i];
            
            // คำนวณวันหมดอายุเริ่มต้น
            calculateExpiryDate(startDateInput, validityInput, expiryDateInput);
            
            // อัปเดตเมื่อเปลี่ยนอายุการใช้งาน
            validityInput.addEventListener('change', function() {
                calculateExpiryDate(startDateInput, validityInput, expiryDateInput);
            });
            
            // อัปเดตเมื่อเปลี่ยนวันที่เริ่มต้น
            startDateInput.addEventListener('change', function() {
                calculateExpiryDate(startDateInput, validityInput, expiryDateInput);
            });
            
            // ล้างวันหมดอายุที่คำนวณเมื่อกำหนดวันหมดอายุเอง
            expiryDateInput.addEventListener('change', function() {
                if (!this.value) {
                    calculateExpiryDate(startDateInput, validityInput, expiryDateInput);
                }
            });
        }
    }
    
    // จัดการการสร้าง QR Code รายบุคคล
    const singleQrBtns = document.querySelectorAll('.single-qr-btn');
    const generateSingleQRBtn = document.getElementById('generateSingleQR');
    const printSingleQRBtn = document.getElementById('printSingleQR');
    
    if (singleQrBtns.length > 0) {
        singleQrBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const studentId = this.getAttribute('data-student-id');
                const studentCode = this.getAttribute('data-student-code');
                const studentName = this.getAttribute('data-student-name');
                const studentClass = this.getAttribute('data-class');
                
                // แสดง Modal
                $('#singleQRModal').modal('show');
                
                // แสดงข้อมูลนักเรียน
                document.getElementById('studentInfo').innerHTML = `
                    <h4>${studentName}</h4>
                    <p>รหัสนักเรียน: ${studentCode}</p>
                    <p>ห้อง: ${studentClass}</p>
                `;
                
                // ซ่อนส่วน QR Preview และแสดง Loading
                document.getElementById('qrPreviewContainer').classList.add('d-none');
                document.getElementById('qrErrorContainer').classList.add('d-none');
                document.getElementById('qrLoadingContainer').classList.add('d-none');
                document.getElementById('generateSingleQR').classList.remove('d-none');
                document.getElementById('printSingleQR').classList.add('d-none');
                
                // เก็บข้อมูลนักเรียนใน attribute ของปุ่มสร้าง QR Code
                generateSingleQRBtn.setAttribute('data-student-id', studentId);
                generateSingleQRBtn.setAttribute('data-student-code', studentCode);
                generateSingleQRBtn.setAttribute('data-student-name', studentName);
                generateSingleQRBtn.setAttribute('data-student-class', studentClass);
                
                // เตรียมฟิลด์วันที่
                const singleQrValidity = document.getElementById('singleQrValidity');
                const singleStartDate = document.getElementById('singleStartDate');
                const singleExpiryDate = document.getElementById('singleExpiryDate');
                
                if (singleQrValidity && singleStartDate && singleExpiryDate) {
                    // กำหนดค่าเริ่มต้น
                    singleQrValidity.value = 7;
                    singleStartDate.value = new Date().toISOString().split('T')[0];
                    singleExpiryDate.value = '';
                    
                    // คำนวณวันหมดอายุเริ่มต้น
                    const expiryDate = new Date();
                    expiryDate.setDate(expiryDate.getDate() + 7);
                    singleExpiryDate.placeholder = expiryDate.toISOString().split('T')[0];
                    
                    // ตั้งค่า min ของ expiryDate
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    singleExpiryDate.min = tomorrow.toISOString().split('T')[0];
                    
                    // เพิ่ม event listener
                    singleQrValidity.addEventListener('change', function() {
                        if (!singleExpiryDate.value) {
                            const startDate = new Date(singleStartDate.value);
                            const validity = parseInt(singleQrValidity.value);
                            const expiryDate = new Date(startDate);
                            expiryDate.setDate(expiryDate.getDate() + validity);
                            singleExpiryDate.placeholder = expiryDate.toISOString().split('T')[0];
                        }
                    });
                    
                    singleStartDate.addEventListener('change', function() {
                        if (!singleExpiryDate.value) {
                            const startDate = new Date(singleStartDate.value);
                            const validity = parseInt(singleQrValidity.value);
                            const expiryDate = new Date(startDate);
                            expiryDate.setDate(expiryDate.getDate() + validity);
                            singleExpiryDate.placeholder = expiryDate.toISOString().split('T')[0];
                        }
                    });
                }
            });
        });
    }
    
    if (generateSingleQRBtn) {
        generateSingleQRBtn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentCode = this.getAttribute('data-student-code');
            const studentName = this.getAttribute('data-student-name');
            const studentClass = this.getAttribute('data-student-class');
            const qrValidity = document.getElementById('singleQrValidity').value;
            const startDate = document.getElementById('singleStartDate').value;
            const expiryDate = document.getElementById('singleExpiryDate').value;
            
            // ซ่อนปุ่มสร้าง QR Code และแสดง Loading
            this.classList.add('d-none');
            document.getElementById('qrLoadingContainer').classList.remove('d-none');
            document.getElementById('qrPreviewContainer').classList.add('d-none');
            document.getElementById('qrErrorContainer').classList.add('d-none');
            
            // สร้าง QR Code ผ่าน AJAX
            fetch('ajax/generate_student_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    student_id: studentId,
                    qr_validity: qrValidity,
                    start_date: startDate,
                    expiry_date: expiryDate
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // สร้าง QR Code สำเร็จ
                    const qrData = data.qr_data;
                    
                    // สร้าง QR Code ด้วย qrcode-generator library
                    const qr = qrcode(0, 'M');
                    qr.addData(JSON.stringify(qrData));
                    qr.make();
                    
                    // แสดง QR Code ใน Preview
                    document.getElementById('qrPreview').innerHTML = qr.createImgTag(5);
                    
                    // ปรับขนาดรูปภาพให้เต็มพื้นที่
                    const img = document.getElementById('qrPreview').querySelector('img');
                    if (img) {
                        img.style.width = '180px';
                        img.style.height = '180px';
                    }
                    
                    // แสดงข้อมูลนักเรียน
                    document.getElementById('previewStudentName').textContent = studentName;
                    document.getElementById('previewStudentId').textContent = 'รหัสนักเรียน: ' + studentCode;
                    document.getElementById('previewStudentClass').textContent = studentClass;
                    
                    // วันหมดอายุ
                    const expireDate = new Date(data.expire_time);
                    const formattedDate = expireDate.toLocaleDateString('th-TH', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    document.getElementById('previewExpireDate').textContent = formattedDate;
                    
                    // แสดง QR Preview และซ่อน Loading
                    document.getElementById('qrPreviewContainer').classList.remove('d-none');
                    document.getElementById('qrLoadingContainer').classList.add('d-none');
                    
                    // แสดงปุ่มพิมพ์
                    document.getElementById('printSingleQR').classList.remove('d-none');
                    
                    // เก็บข้อมูล QR ใน attribute ของปุ่มพิมพ์
                    printSingleQRBtn.setAttribute('data-qr-code', JSON.stringify(qrData));
                    printSingleQRBtn.setAttribute('data-expire-time', data.expire_time);
                    
                } else {
                    // สร้าง QR Code ไม่สำเร็จ
                    document.getElementById('qrErrorMessage').textContent = data.message || 'ไม่สามารถสร้าง QR Code ได้';
                    document.getElementById('qrErrorContainer').classList.remove('d-none');
                    document.getElementById('qrLoadingContainer').classList.add('d-none');
                    document.getElementById('generateSingleQR').classList.remove('d-none');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('qrErrorMessage').textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                document.getElementById('qrErrorContainer').classList.remove('d-none');
                document.getElementById('qrLoadingContainer').classList.add('d-none');
                document.getElementById('generateSingleQR').classList.remove('d-none');
            });
        });
    }
    
    if (printSingleQRBtn) {
        printSingleQRBtn.addEventListener('click', function() {
            const studentName = generateSingleQRBtn.getAttribute('data-student-name');
            const studentCode = generateSingleQRBtn.getAttribute('data-student-code');
            const studentClass = generateSingleQRBtn.getAttribute('data-student-class');
            const qrCodeData = this.getAttribute('data-qr-code');
            const expireTime = this.getAttribute('data-expire-time');
            
            // สร้างหน้าต่างใหม่สำหรับพิมพ์
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="th">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>พิมพ์ QR Code - ${studentName}</title>
                    <style>
                        body {
                            font-family: 'Sarabun', sans-serif;
                            background-color: #f8f9fa;
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                        }
                        .qr-card {
                            width: 300px;
                            height: 450px;
                            background: #fff;
                            border-radius: 10px;
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                        }
                        .qr-header {
                            background: linear-gradient(135deg, #06c755 0%, #04a745 100%);
                            color: white;
                            padding: 15px;
                            display: flex;
                            align-items: center;
                            gap: 15px;
                        }
                        .qr-logo {
                            width: 40px;
                            height: 40px;
                            border-radius: 50%;
                            background: white;
                            padding: 5px;
                        }
                        .qr-title {
                            flex: 1;
                        }
                        .qr-title h5 {
                            margin: 0 0 5px 0;
                            font-size: 16px;
                            font-weight: 600;
                        }
                        .qr-title p {
                            margin: 0;
                            font-size: 14px;
                            opacity: 0.9;
                        }
                        .qr-body {
                            flex: 1;
                            padding: 20px;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                        }
                        .qr-body img {
                            width: 180px;
                            height: 180px;
                            margin-bottom: 15px;
                        }
                        .student-details {
                            text-align: center;
                            margin-top: 15px;
                        }
                        .student-name {
                            font-size: 18px;
                            font-weight: 600;
                            margin: 0 0 5px 0;
                        }
                        .student-id {
                            font-size: 16px;
                            color: #555;
                            margin: 0 0 5px 0;
                        }
                        .student-class {
                            font-size: 14px;
                            color: #777;
                            margin: 0;
                        }
                        .qr-footer {
                            background: #f8f9fa;
                            padding: 10px 15px;
                            border-top: 1px solid #eee;
                            text-align: center;
                            font-size: 14px;
                            color: #555;
                        }
                        .qr-footer p {
                            margin: 5px 0;
                        }
                        .system-name {
                            font-style: italic;
                            color: #777;
                            font-size: 12px;
                        }
                        @media print {
                            body {
                                background: white;
                                padding: 0;
                            }
                            .qr-card {
                                box-shadow: none;
                                border: 1px solid #eee;
                            }
                        }
                    </style>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
                </head>
                <body>
                    <div class="qr-card">
                        <div class="qr-header">
                            <img src="assets/images/logo.png" alt="Logo" class="qr-logo">
                            <div class="qr-title">
                                <h5>วิทยาลัยการอาชีพปราสาท</h5>
                                <p>QR Code สำหรับเช็คชื่อเข้าแถว</p>
                            </div>
                        </div>
                        <div class="qr-body">
                            <img id="qrImage" alt="QR Code">
                            <div class="student-details">
                                <p class="student-name">${studentName}</p>
                                <p class="student-id">รหัสนักเรียน: ${studentCode}</p>
                                <p class="student-class">${studentClass}</p>
                            </div>
                        </div>
                        <div class="qr-footer">
                            <p>วันหมดอายุ: ${new Date(expireTime).toLocaleDateString('th-TH', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            })}</p>
                            <p class="system-name">พัฒนาโดย ครูมนตรี  ศรีสุข</p>
                        </div>
                    </div>
                    
                    <script>
                        // สร้าง QR Code
                        window.onload = function() {
                            const qrData = ${qrCodeData};
                            const qr = qrcode(0, 'M');
                            qr.addData(JSON.stringify(qrData));
                            qr.make();
                            
                            document.getElementById('qrImage').src = qr.createDataURL();
                            
                            // พิมพ์อัตโนมัติ
                            setTimeout(function() {
                                window.print();
                                // ปิดหน้าต่างหลังจากพิมพ์เสร็จ (ใช้ได้ในบางเบราว์เซอร์)
                                // window.close();
                            }, 500);
                        };
                    </script>
                </body>
                </html>
            `);
            printWindow.document.close();
        });
    }
    
    // แสดงแท็บที่ถูกเลือกจาก URL hash
    const hash = window.location.hash;
    if (hash) {
        const tabId = hash.substring(1);
        const tab = document.querySelector(`a[href="#${tabId}"]`);
        if (tab) {
            tab.click();
        }
    }
    
    // ปรับปรุง URL hash เมื่อเปลี่ยนแท็บ
    const tabs = document.querySelectorAll('a[data-toggle="tab"]');
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            const id = e.target.getAttribute('href').substring(1);
            window.location.hash = id;
        });
    });
});