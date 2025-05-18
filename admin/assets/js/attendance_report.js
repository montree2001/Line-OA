/**
 * attendance_report.js - สคริปต์สำหรับหน้ารายงานการเข้าแถว
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

document.addEventListener('DOMContentLoaded', function() {
    // ตั้งค่า Select2 สำหรับตัวเลือกทั้งหมด
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }
    
    // กำหนด DataTable สำหรับตารางการเข้าแถว
    if (typeof $.fn.DataTable !== 'undefined' && $('#attendance-table').length > 0) {
        $('#attendance-table').DataTable({
            responsive: true,
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
                "zeroRecords": "ไม่พบข้อมูล",
                "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจาก _MAX_ รายการทั้งหมด)",
                "search": "ค้นหา:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "pageLength": 25,
            "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        });
    }
    
    // ตรวจสอบค่าสัปดาห์เริ่มต้นและสิ้นสุด
    $('#start_week').on('change', function() {
        var startWeek = parseInt($(this).val());
        var endWeek = parseInt($('#end_week').val());
        
        if (endWeek < startWeek) {
            $('#end_week').val(startWeek).trigger('change');
        }
    });
    
    // เมื่อเลือกแผนกวิชา ให้โหลดข้อมูลห้องเรียนใหม่
    $('#department_id').on('change', function() {
        var departmentId = $(this).val();
        if (departmentId) {
            // แสดง loading
            $('#class_id').html('<option value="">กำลังโหลดข้อมูล...</option>');
            
            // โหลดข้อมูลห้องเรียนตามแผนกวิชา
            $.ajax({
                url: 'ajax/get_classes_by_department.php',
                type: 'GET',
                data: {
                    department_id: departmentId
                },
                dataType: 'json',
                success: function(data) {
                    var options = '<option value="">-- เลือกห้องเรียน --</option>';
                    data.forEach(function(cls) {
                        options += '<option value="' + cls.class_id + '">' + cls.class_name + '</option>';
                    });
                    $('#class_id').html(options).trigger('change');
                },
                error: function() {
                    $('#class_id').html('<option value="">-- เกิดข้อผิดพลาด --</option>');
                }
            });
        } else {
            // ถ้าไม่ได้เลือกแผนกวิชา ให้แสดงห้องเรียนทั้งหมด
            location.reload();
        }
    });
    
    // สร้างกราฟในกรณีที่มีข้อมูล
    if (typeof Chart !== 'undefined' && $('#attendanceChart').length > 0 && typeof chartData !== 'undefined') {
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'เปอร์เซ็นต์การเข้าแถว (%)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'วันที่'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw + '%';
                            }
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // ฟังก์ชันสำหรับการพิมพ์รายงาน
    window.printAttendanceReport = function() {
        document.getElementById('print-form').submit();
    };
    
    // ฟังก์ชันสำหรับการพิมพ์กราฟ
    window.printAttendanceChart = function() {
        document.getElementById('chart-form').submit();
    };
    
    // ฟังก์ชันสำหรับการดาวน์โหลด Excel
    window.downloadExcel = function() {
        document.getElementById('excel-form').submit();
    };
});