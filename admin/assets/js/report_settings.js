/**
 * report_settings.js - JavaScript สำหรับหน้าตั้งค่ารายงาน
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบการปรับขนาดหน้าจอ
    function checkScreenSize() {
        const tables = document.querySelectorAll('.table-responsive table');
        if (window.innerWidth < 768) {
            tables.forEach(table => {
                if (table.classList.contains('dataTable') && !table.classList.contains('responsive')) {
                    // สำหรับเรียกใช้งาน responsive เมื่อใช้ DataTables
                    $(table).DataTable().destroy();
                    $(table).DataTable({
                        responsive: true,
                        language: {
                            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
                        }
                    });
                }
            });
        }
    }
    
    // เรียกใช้งานในครั้งแรก
    checkScreenSize();
    
    // เรียกใช้งานเมื่อมีการปรับขนาดหน้าจอ
    window.addEventListener('resize', checkScreenSize);
    
    // แสดงตัวอย่างรูปภาพก่อนอัพโหลด
    const logoFileInput = document.getElementById('logo_file');
    if (logoFileInput) {
        logoFileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewContainer = document.querySelector('.border.rounded p-3.text-center');
                    if (previewContainer) {
                        previewContainer.innerHTML = `<img src="${e.target.result}" alt="Preview" class="img-fluid" style="max-height: 150px;">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // ตรวจสอบค่าวันที่ในปัจจุบัน
    const holidayDateInput = document.getElementById('holiday_date');
    if (holidayDateInput) {
        // ตั้งค่าวันที่ปัจจุบัน
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        holidayDateInput.value = `${year}-${month}-${day}`;
        
        // ตรวจสอบ "เป็นวันหยุดประจำปี" checkbox
        const isRepeatingCheckbox = document.getElementById('is_repeating');
        const academicYearSelect = document.getElementById('academic_year_id');
        
        isRepeatingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                academicYearSelect.disabled = true;
                academicYearSelect.value = '';
            } else {
                academicYearSelect.disabled = false;
            }
        });
    }
    
    // ส่งรูปภาพเป็น AJAX เพื่อป้องกันการรีโหลดหน้า
    const logoForm = document.querySelector('form[enctype="multipart/form-data"]');
    if (logoForm) {
        logoForm.addEventListener('submit', function(e) {
            if (logoFileInput && !logoFileInput.files[0]) {
                alert('กรุณาเลือกไฟล์รูปภาพ');
                e.preventDefault();
                return false;
            }
        });
    }
});