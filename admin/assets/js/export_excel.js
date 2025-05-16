/**
 * export_excel.js - ไฟล์สำหรับการส่งออกข้อมูลเป็น Excel ในหน้ารายงานเช็คชื่อ
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตรวจสอบว่ามีการกำหนดให้ส่งออกเป็น Excel หรือไม่
    if (typeof autoExportExcel !== 'undefined' && autoExportExcel) {
        // ปิดหน้าต่างหลังจากการส่งออก
        window.closeAfterExport = function() {
            window.close();
        };
        
        // ดำเนินการส่งออกหลังจากที่รายงานถูกสร้าง
        // หมายเหตุ: ฟังก์ชันนี้จะถูกเรียกจาก print_activity_content.php
    }
});

// ฟังก์ชันส่งออกข้อมูลเป็น Excel แบบโดยตรง (สำหรับใช้ร่วมกับ Server-side processing)
function exportToExcelDirect(reportData) {
    // สร้าง workbook
    const wb = XLSX.utils.book_new();
    
    // สร้างข้อมูลสำหรับ Excel
    const data = [];
    
    // เพิ่มข้อมูลหัวรายงาน
    data.push([`งานกิจกรรมนักเรียน นักศึกษา ฝ่ายพัฒนากิจการนักเรียน นักศึกษา วิทยาลัยการอาชีพปราสาท`]);
    data.push([`แบบรายงานเช็คชื่อนักเรียน นักศึกษา ทำกิจกรรมหน้าเสาธง`]);
    data.push([`ภาคเรียนที่ ${reportData.semester} ปีการศึกษา ${reportData.year} สัปดาห์ที่ ${reportData.week_number}`]);
    data.push([`ระดับชั้น ${reportData.class_level} กลุ่ม ${reportData.group_number} แผนกวิชา${reportData.department_name}`]);
    data.push([``]);
    
    // เพิ่มหัวตาราง
    const headers = ['ลำดับที่', 'รหัสนักศึกษา', 'ชื่อ-สกุล'];
    
    // เพิ่มวันในสัปดาห์
    const workDays = reportData.work_days;
    workDays.forEach((day, index) => {
        const dayDate = new Date(day);
        const dayOfWeek = dayDate.getDay();
        const thaiDaysShort = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        headers.push(`${index + 1} ${thaiDaysShort[dayOfWeek]}`);
    });
    
    headers.push('รวม', 'หมายเหตุ');
    data.push(headers);
    
    // เพิ่มข้อมูลนักเรียน
    reportData.students.forEach((student, index) => {
        const rowData = [
            index + 1,
            student.student_code,
            `${student.title}${student.first_name} ${student.last_name}`
        ];
        
        // ข้อมูลการเข้าแถวในแต่ละวัน
        workDays.forEach(day => {
            let status = '-';
            
            // ตรวจสอบวันหยุด
            if (reportData.holidays[day]) {
                status = 'หยุด';
            } else if (student.attendances[day]) {
                // ใช้สัญลักษณ์แทนสถานะ
                const symbols = {
                    'present': '✓',
                    'absent': 'x',
                    'late': 'ส',
                    'leave': 'ล'
                };
                status = symbols[student.attendances[day].status] || '-';
            }
            
            rowData.push(status);
        });
        
        // เพิ่มคอลัมน์รวมและหมายเหตุ
        const presentCount = Object.values(student.attendances).filter(a => a.status === 'present' || a.status === 'late').length;
        const absentCount = Object.values(student.attendances).filter(a => a.status === 'absent').length;
        const lateCount = Object.values(student.attendances).filter(a => a.status === 'late').length;
        const leaveCount = Object.values(student.attendances).filter(a => a.status === 'leave').length;
        
        rowData.push(presentCount);
        
        // สร้างหมายเหตุอัตโนมัติ
        let remarks = [];
        if (absentCount > 0) remarks.push(`ขาด ${absentCount} วัน`);
        if (lateCount > 0) remarks.push(`สาย ${lateCount} วัน`);
        if (leaveCount > 0) remarks.push(`ลา ${leaveCount} วัน`);
        rowData.push(remarks.join(', '));
        
        data.push(rowData);
    });
    
    // เพิ่มข้อมูลสรุป
    const totalStudents = reportData.students.length;
    const totalPresent = reportData.students.reduce((sum, student) => sum + Object.values(student.attendances).filter(a => a.status === 'present' || a.status === 'late').length, 0);
    const totalAbsent = reportData.students.reduce((sum, student) => sum + Object.values(student.attendances).filter(a => a.status === 'absent').length, 0);
    const totalLate = reportData.students.reduce((sum, student) => sum + Object.values(student.attendances).filter(a => a.status === 'late').length, 0);
    const totalLeave = reportData.students.reduce((sum, student) => sum + Object.values(student.attendances).filter(a => a.status === 'leave').length, 0);
    
    data.push([]);
    data.push(['สรุป']);
    data.push([`จำนวนคน ${totalStudents} มา ${totalPresent} ขาด ${totalAbsent} สาย ${totalLate} ลา ${totalLeave}`]);
    
    // คำนวณอัตราการเข้าแถว
    const totalAttendanceDays = workDays.length * totalStudents;
    const attendanceRate = totalAttendanceDays > 0 ? 
        ((totalPresent) / (totalAttendanceDays - totalLeave) * 100).toFixed(2) : '0.00';
    
    data.push([`สรุปจำนวนนักเรียนเข้าแถวร้อยละ ${attendanceRate}`]);
    
    // เพิ่มข้อมูลลายเซ็น
    data.push([]);
    data.push([]);
    data.push(['ลงชื่อ.........................................', '', '', '', '', 'ลงชื่อ.........................................', '', '', '', '', 'ลงชื่อ........................................']);
    data.push(['(', reportData.advisor_name, ')', '', '', '(', reportData.activity_head_name, ')', '', '', '(', reportData.director_deputy_name, ')']);
    data.push(['ครูที่ปรึกษา', '', '', '', '', 'หัวหน้างานกิจกรรมนักเรียน นักศึกษา', '', '', '', '', 'รองผู้อำนวยการฝ่ายพัฒนากิจการนักเรียนนักศึกษา']);
    
    // สร้าง worksheet
    const ws = XLSX.utils.aoa_to_sheet(data);
    
    // ตั้งค่าความกว้างของคอลัมน์
    ws['!cols'] = [
        { width: 10 }, // ลำดับที่
        { width: 15 }, // รหัสนักศึกษา
        { width: 30 }, // ชื่อ-สกุล
    ];
    
    // เพิ่มความกว้างคอลัมน์สำหรับวันในสัปดาห์
    for (let i = 0; i < workDays.length; i++) {
        ws['!cols'].push({ width: 10 });
    }
    
    // เพิ่มความกว้างคอลัมน์รวมและหมายเหตุ
    ws['!cols'].push({ width: 10 }, { width: 20 });
    
    // เพิ่ม worksheet ลงใน workbook
    XLSX.utils.book_append_sheet(wb, ws, 'รายงานเช็คชื่อเข้าแถว');
    
    // บันทึกไฟล์ Excel
    XLSX.writeFile(wb, `รายงานเช็คชื่อเข้าแถว_${reportData.class_level}${reportData.group_number}_สัปดาห์${reportData.week_number}.xlsx`);
    
    // ปิดหน้าต่างหลังจากส่งออก (ถ้าเป็นการส่งออกอัตโนมัติ)
    if (typeof closeAfterExport === 'function') {
        setTimeout(closeAfterExport, 500);
    }
}