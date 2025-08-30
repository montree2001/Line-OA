<?php
/**
 * student_activity_check.php - หน้าสำหรับนักเรียนตรวจสอบผลการตัดกิจกรรม
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// ตั้งค่าเวลา
date_default_timezone_set('Asia/Bangkok');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบผลการตัดกิจกรรม - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Sarabun -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-container {
            padding: 20px 0;
        }
        
        .search-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .result-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }
        
        .header-section {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header-section h1 {
            font-weight: 600;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.3);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.3);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .attendance-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-top: 20px;
        }
        
        .day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px 5px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        
        .day-cell {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            position: relative;
            border: 2px solid transparent;
        }
        
        .day-cell.present {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
        }
        
        .day-cell.late {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }
        
        .day-cell.absent {
            background: linear-gradient(135deg, #fc466b, #3f5efb);
            color: white;
        }
        
        .day-cell.holiday {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .day-cell.weekend {
            background: #f8f9fa;
            color: #adb5bd;
        }
        
        .day-number {
            font-weight: 600;
        }
        
        .status-badge {
            font-size: 0.6rem;
            padding: 2px 4px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.3);
            margin-top: 2px;
        }
        
        .activity-result {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .activity-result.pass {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 10px 30px rgba(56, 239, 125, 0.3);
        }
        
        .activity-result.fail {
            background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            box-shadow: 0 10px 30px rgba(252, 70, 107, 0.3);
        }
        
        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 500;
            padding: 12px 30px;
            border-radius: 10px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .week-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .stat-number {
                font-size: 2rem;
            }
            
            .attendance-calendar {
                gap: 3px;
            }
            
            .day-cell {
                font-size: 0.7rem;
                padding: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="container main-container">
        <!-- Header Section -->
        <div class="header-section">
            <h1><i class="fas fa-chart-line"></i> ตรวจสอบผลการตัดกิจกรรม</h1>
            <p class="mb-0">วิทยาลัยการอาชีพปราสาท</p>
        </div>

        <!-- Search Section -->
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="card search-card">
                    <div class="card-body p-4">
                        <h5 class="card-title text-center mb-4">
                            <i class="fas fa-search text-primary"></i> ค้นหาข้อมูลของคุณ
                        </h5>
                        
                        <form id="searchForm">
                            <div class="mb-3">
                                <label class="form-label">ชื่อ-นามสกุล หรือ รหัสนักศึกษา</label>
                                <input type="text" class="form-control" id="searchInput" 
                                       placeholder="พิมพ์ชื่อ-นามสกุล หรือ รหัสนักศึกษา" required>
                            </div>
                            
                            <div class="mb-3" id="studentSelectContainer" style="display: none;">
                                <label class="form-label">เลือกชื่อของคุณ</label>
                                <select class="form-select" id="studentSelect" required></select>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-search">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <p class="mt-3 text-white">กำลังค้นหาข้อมูล...</p>
        </div>

        <!-- Results Section -->
        <div id="resultsSection" style="display: none;">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card result-card">
                        <div class="card-body p-4">
                            <!-- Student Info -->
                            <div class="text-center mb-4" id="studentInfo"></div>
                            
                            <!-- Statistics -->
                            <div class="stats-grid" id="statsGrid"></div>
                            
                            <!-- Activity Result -->
                            <div id="activityResult"></div>
                            
                            <!-- Weekly Summary -->
                            <div id="weeklySummary" class="mt-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            let searchTimeout;
            
            // Auto search on input
            $('#searchInput').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val().trim();
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        searchStudents(query);
                    }, 500);
                } else {
                    $('#studentSelectContainer').hide();
                    $('#studentSelect').empty();
                }
            });
            
            // Form submit
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                const studentId = $('#studentSelect').val();
                
                if (studentId) {
                    loadStudentData(studentId);
                } else {
                    alert('กรุณาเลือกชื่อของคุณ');
                }
            });
            
            // Search students function
            function searchStudents(query) {
                $.ajax({
                    url: 'ajax/search_students.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { search: query },
                    success: function(data) {
                        if (data.length > 0) {
                            const select = $('#studentSelect');
                            select.empty();
                            select.append('<option value="">-- เลือกชื่อของคุณ --</option>');
                            
                            data.forEach(student => {
                                select.append(`<option value="${student.student_id}">
                                    ${student.title}${student.first_name} ${student.last_name} 
                                    (${student.student_code}) - ${student.level}/${student.group_number}
                                </option>`);
                            });
                            
                            $('#studentSelectContainer').show();
                        } else {
                            $('#studentSelectContainer').hide();
                        }
                    },
                    error: function() {
                        console.error('Error searching students');
                    }
                });
            }
            
            // Load student attendance data
            function loadStudentData(studentId) {
                $('#loadingSpinner').show();
                $('#resultsSection').hide();
                
                $.ajax({
                    url: 'ajax/get_student_activity_result.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { student_id: studentId },
                    success: function(data) {
                        $('#loadingSpinner').hide();
                        
                        if (data.success) {
                            displayResults(data);
                            $('#resultsSection').show();
                        } else {
                            alert('ไม่พบข้อมูล: ' + (data.message || 'เกิดข้อผิดพลาด'));
                        }
                    },
                    error: function() {
                        $('#loadingSpinner').hide();
                        alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                    }
                });
            }
            
            // Display results
            function displayResults(data) {
                const student = data.student;
                const stats = data.stats;
                const calendar = data.calendar;
                const weekly = data.weekly;
                
                // Student info
                $('#studentInfo').html(`
                    <h4><i class="fas fa-user text-primary"></i> ${student.title}${student.first_name} ${student.last_name}</h4>
                    <p class="mb-0 text-muted">รหัสนักศึกษา: ${student.student_code} | ชั้น: ${student.level}/${student.group_number}</p>
                    <p class="mb-0 text-muted">แผนก: ${student.department_name}</p>
                `);
                
                // Statistics
                const attendanceRate = stats.total_study_days > 0 ? 
                    ((stats.total_present + stats.total_late) / stats.total_study_days * 100).toFixed(1) : 0;
                
                $('#statsGrid').html(`
                    <div class="stat-card">
                        <div class="stat-number">${stats.total_present + stats.total_late}</div>
                        <div class="stat-label">วันที่เข้าแถว</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number">${stats.total_present}</div>
                        <div class="stat-label">มาแถว</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number">${stats.total_late}</div>
                        <div class="stat-label">สาย</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-number">${stats.total_absent}</div>
                        <div class="stat-label">ขาด</div>
                    </div>
                `);
                
                // Activity result
                const isPass = attendanceRate >= 60;
                const resultClass = isPass ? 'pass' : 'fail';
                const resultIcon = isPass ? 'fa-check-circle' : 'fa-times-circle';
                const resultText = isPass ? 'ผ่าน' : 'ไม่ผ่าน';
                
                $('#activityResult').html(`
                    <div class="activity-result ${resultClass}">
                        <h3><i class="fas ${resultIcon} me-2"></i>ผลการประเมิน: ${resultText}</h3>
                        <p class="mb-0">อัตราการเข้าแถว: ${attendanceRate}% (เกณฑ์ผ่าน: 60%)</p>
                        <small>จากทั้งหมด ${stats.total_study_days} วันเรียน</small>
                    </div>
                `);
                
                // Weekly summary
                let weeklyHtml = '<h6><i class="fas fa-list text-primary"></i> สรุปรายสัปดาห์</h6>';
                weekly.forEach((week, index) => {
                    const weekRate = week.study_days > 0 ? 
                        ((week.present + week.late) / week.study_days * 100) : 0;
                    
                    weeklyHtml += `
                        <div class="week-summary">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>สัปดาห์ที่ ${index + 1}</strong>
                                <span class="badge ${weekRate >= 60 ? 'bg-success' : 'bg-danger'}">
                                    ${weekRate.toFixed(1)}%
                                </span>
                            </div>
                            <small class="text-muted">
                                เข้าแถว: ${week.present + week.late}/${week.study_days} วัน 
                                (มาแถว: ${week.present}, สาย: ${week.late}, ขาด: ${week.absent})
                            </small>
                        </div>
                    `;
                });
                $('#weeklySummary').html(weeklyHtml);
            }
            
            // Helper functions
            function formatThaiDate(dateStr) {
                const date = new Date(dateStr);
                return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear() + 543}`;
            }
            
            function getStatusText(dayData) {
                if (dayData.is_holiday) return 'วันหยุด';
                if (!dayData.status) return 'ไม่มีข้อมูล';
                
                const statusMap = {
                    'present': 'มาแถว',
                    'late': 'สาย', 
                    'absent': 'ขาด',
                    'leave': 'ลา'
                };
                
                return statusMap[dayData.status] || 'ไม่มีข้อมูล';
            }
        });
    </script>
</body>
</html>