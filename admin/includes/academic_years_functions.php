<?php
/**
 * academic_years_functions.php - ฟังก์ชันจัดการปีการศึกษา
 */

// ดึงข้อมูลปีการศึกษาทั้งหมด
function getAcademicYearsFromDB() {
    $conn = getDB();  // ใช้ getDB() แทน global $conn;
    if ($conn === null) {
        error_log('Database connection is not established.');
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT academic_year_id, year, semester, start_date, end_date, 
                   required_attendance_days, is_active
            FROM academic_years
            ORDER BY year DESC, semester ASC
        ");
        $stmt->execute();
        $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // หาปีการศึกษาปัจจุบันและปีการศึกษาถัดไป
        $currentAcademicYear = null;
        $nextAcademicYear = null;
        $activeYearId = null;
        $hasNewAcademicYear = false;
        
        foreach ($academicYears as $year) {
            if ($year['is_active'] == 1) {
                $currentAcademicYear = $year;
                $activeYearId = $year['academic_year_id'];
            }
        }
        
        // หาปีการศึกษาถัดไป
        if ($currentAcademicYear) {
            if ($currentAcademicYear['semester'] == 1) {
                // ถ้าเป็นภาคเรียนที่ 1 ให้หาภาคเรียนที่ 2 ของปีเดียวกัน
                foreach ($academicYears as $year) {
                    if ($year['year'] == $currentAcademicYear['year'] && $year['semester'] == 2) {
                        $nextAcademicYear = $year;
                        $hasNewAcademicYear = true;
                        break;
                    }
                }
            } else {
                // ถ้าเป็นภาคเรียนที่ 2 ให้หาภาคเรียนที่ 1 ของปีถัดไป
                foreach ($academicYears as $year) {
                    if ($year['year'] == ($currentAcademicYear['year'] + 1) && $year['semester'] == 1) {
                        $nextAcademicYear = $year;
                        $hasNewAcademicYear = true;
                        break;
                    }
                }
            }
        }
        
        return [
            'academic_years' => $academicYears,
            'current_academic_year' => $currentAcademicYear,
            'next_academic_year' => $nextAcademicYear,
            'has_new_academic_year' => $hasNewAcademicYear,
            'active_year_id' => $activeYearId
        ];
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        return false;
    }
}

// ตัวอย่างข้อมูลปีการศึกษา
function getSampleAcademicYears() {
    $academicYears = [
        [
            'academic_year_id' => 1,
            'year' => 2567,
            'semester' => 1,
            'start_date' => '2024-06-01',
            'end_date' => '2024-10-31',
            'required_attendance_days' => 80,
            'is_active' => 1
        ],
        [
            'academic_year_id' => 2,
            'year' => 2567,
            'semester' => 2,
            'start_date' => '2024-11-01',
            'end_date' => '2025-02-28',
            'required_attendance_days' => 80,
            'is_active' => 0
        ],
        [
            'academic_year_id' => 3,
            'year' => 2568,
            'semester' => 1,
            'start_date' => '2025-06-01',
            'end_date' => '2025-10-31',
            'required_attendance_days' => 80,
            'is_active' => 0
        ]
    ];
    
    return [
        'academic_years' => $academicYears,
        'current_academic_year' => $academicYears[0],
        'next_academic_year' => $academicYears[1],
        'has_new_academic_year' => true,
        'active_year_id' => 1
    ];
}

// สำหรับตัวอย่างข้อมูลการเลื่อนชั้น
function getSamplePromotionCounts() {
    return [
        [
            'current_level' => 'ปวช.1',
            'new_level' => 'ปวช.2',
            'student_count' => 85
        ],
        [
            'current_level' => 'ปวช.2',
            'new_level' => 'ปวช.3',
            'student_count' => 78
        ],
        [
            'current_level' => 'ปวช.3',
            'new_level' => 'สำเร็จการศึกษา',
            'student_count' => 72
        ],
        [
            'current_level' => 'ปวส.1',
            'new_level' => 'ปวส.2',
            'student_count' => 65
        ],
        [
            'current_level' => 'ปวส.2',
            'new_level' => 'สำเร็จการศึกษา',
            'student_count' => 58
        ]
    ];
}