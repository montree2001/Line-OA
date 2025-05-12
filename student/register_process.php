<?php

/**
 * register_process.php - ไฟล์สำหรับประมวลผลฟอร์มลงทะเบียนนักเรียน
 * รองรับการประมวลผลทุกขั้นตอนของการลงทะเบียน
 */

// ตรวจสอบว่าเรียกใช้งานโดยตรงหรือไม่
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Location: register.php');
    exit;
}

// ฟังก์ชันสำหรับทำความสะอาดข้อมูล input
function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// ฟังก์ชันสำหรับตรวจสอบรหัสนักศึกษา
function validateStudentCode($code)
{
    // ตรวจสอบรูปแบบรหัสนักศึกษา 11 หลัก
    if (empty($code) || !preg_match('/^\d{11}$/', $code)) {
        return false;
    }
    return true;
}

// ฟังก์ชันสำหรับตรวจสอบเบอร์โทรศัพท์
function validatePhone($phone)
{
    // ถ้าไม่ได้กรอกเบอร์ ถือว่าผ่าน
    if (empty($phone)) {
        return true;
    }
    // ตรวจสอบรูปแบบเบอร์โทรศัพท์
    if (!preg_match('/^0\d{9}$/', $phone)) {
        return false;
    }
    return true;
}

// ฟังก์ชันสำหรับตรวจสอบอีเมล
function validateEmail($email)
{
    // ถ้าไม่ได้กรอกอีเมล ถือว่าผ่าน
    if (empty($email)) {
        return true;
    }
    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return true;
}

// ฟังก์ชันบันทึกประวัติการลงทะเบียน
function logRegistrationActivity($conn, $user_id, $action, $details = null)
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO user_activity_logs (user_id, action_type, action_details, ip_address, created_at)
            VALUES (?, 'registration', ?, ?, NOW())
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (PDOException $e) {
        // เพียงบันทึกข้อผิดพลาด แต่ไม่หยุดการทำงาน
        error_log("Error logging registration activity: " . $e->getMessage());
    }
}

// ฟังก์ชันสร้าง QR Code สำหรับนักเรียน
function createStudentQRCode($conn, $student_id, $student_code)
{
    try {
        // สร้างข้อมูล QR Code
        $qr_data = [
            'type' => 'student_link',
            'student_id' => $student_id,
            'student_code' => $student_code,
            'token' => md5($student_code . time()),
            'expire_time' => date('Y-m-d H:i:s', strtotime('+7 day'))
        ];

        // บันทึกข้อมูล QR Code ลงฐานข้อมูล
        $stmt = $conn->prepare("
            INSERT INTO qr_codes (student_id, qr_code_data, valid_from, valid_until, is_active, created_at)
            VALUES (?, ?, NOW(), ?, 1, NOW())
        ");
        $stmt->execute([
            $student_id,
            json_encode($qr_data),
            date('Y-m-d H:i:s', strtotime('+7 day'))
        ]);

        return true;
    } catch (PDOException $e) {
        error_log("Error creating QR code: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับเช็คว่า student code มีอยู่แล้วหรือไม่
function isStudentCodeExists($conn, $student_code)
{
    try {
        $stmt = $conn->prepare("SELECT student_id FROM students WHERE student_code = ?");
        $stmt->execute([$student_code]);
        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) {
        error_log("Error checking student code: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลแผนกวิชา
function getDepartments($conn)
{
    try {
        $stmt = $conn->prepare("SELECT department_id, department_name FROM departments WHERE is_active = 1 ORDER BY department_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching departments: " . $e->getMessage());
        return [];
    }
}

//////////////////////////////////////////
// เริ่มประมวลผลฟอร์มตามการส่งข้อมูล
//////////////////////////////////////////

// 1. ประมวลผลการค้นหารหัสนักศึกษา
if (isset($_POST['search_student'])) {
    $student_code = isset($_POST['student_code']) ? cleanInput($_POST['student_code']) : '';

    // ตรวจสอบรหัสนักศึกษา
    if (!validateStudentCode($student_code)) {
        $error_message = "กรุณากรอกรหัสนักศึกษาให้ถูกต้อง (ตัวเลข 11 หลัก)";
    } else {
        try {
            // ค้นหานักศึกษาในฐานข้อมูล
            $stmt = $conn->prepare("
                SELECT s.student_id, s.student_code, s.title, s.current_class_id, s.status,
                       u.user_id as existing_user_id, u.first_name, u.last_name, u.phone_number, u.email, u.profile_picture, u.line_id,
                       c.class_id, c.level, c.group_number, c.department_id,
                       d.department_name
                FROM students s
                LEFT JOIN users u ON s.user_id = u.user_id
                LEFT JOIN classes c ON s.current_class_id = c.class_id
                LEFT JOIN departments d ON c.department_id = d.department_id
                WHERE s.student_code = ?
            ");
            $stmt->execute([$student_code]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student) {
                // ตรวจสอบว่า LINE ID เป็นแบบชั่วคราวหรือไม่ (ขึ้นต้นด้วย TEMP_)
                $is_temp_line_id = isset($student['line_id']) && strpos($student['line_id'], 'TEMP_') === 0;

                // ตรวจสอบว่านักเรียนมีข้อมูลผู้ใช้เชื่อมโยงอยู่แล้วหรือไม่
                if ($student['existing_user_id'] && !$is_temp_line_id && $student['existing_user_id'] != $user_id) {
                    // กรณีมีผู้ใช้อื่นเชื่อมโยงกับรหัสนักศึกษานี้แล้ว (และไม่ใช่ TEMP)
                    $error_message = "รหัสนักศึกษานี้ได้ลงทะเบียนกับผู้ใช้อื่นแล้ว กรุณาติดต่อครูที่ปรึกษาหรือเจ้าหน้าที่ทะเบียน";
                } else {
                    // กรณีไม่มีผู้ใช้เชื่อมโยง หรือมี line_id ขึ้นต้นด้วย TEMP_ (ยังไม่มีใครเชื่อมโยงจริง) สามารถใช้ได้
                    // พบข้อมูลนักศึกษา - เก็บข้อมูลใน session
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['student_code'] = $student['student_code'];
                    $_SESSION['student_title'] = $student['title'];
                    $_SESSION['student_first_name'] = $student['first_name'];
                    $_SESSION['student_last_name'] = $student['last_name'];
                    $_SESSION['student_phone'] = $student['phone_number'];
                    $_SESSION['student_email'] = $student['email'];
                    $_SESSION['student_profile_picture'] = $student['profile_picture'];
                    $_SESSION['student_class_id'] = $student['current_class_id'];
                    $_SESSION['student_level'] = $student['level'];
                    $_SESSION['student_group'] = $student['group_number'];
                    $_SESSION['student_department_id'] = $student['department_id'];
                    $_SESSION['student_department_name'] = $student['department_name'];
                    $_SESSION['student_status'] = $student['status'];
                    $_SESSION['line_id'] = $student['line_id'];

                    // ถ้าเป็นบัญชีชั่วคราว ให้อัปเดตข้อมูลผู้ใช้ทันที
                    if ($is_temp_line_id) {
                        // บันทึกลงบันทึกว่าพบการเชื่อมโยงบัญชีชั่วคราว
                        logRegistrationActivity($conn, $user_id, "found_temp_line_account", "Student ID: " . $student_code . ", Temp LINE: " . $student['line_id']);
                    }

                    // ตรวจสอบว่านักศึกษาอยู่ในสถานะที่ลงทะเบียนได้หรือไม่
                    if ($student['status'] == 'พ้นสภาพ' || $student['status'] == 'สำเร็จการศึกษา') {
                        $error_message = "ไม่สามารถลงทะเบียนได้ เนื่องจากสถานะนักศึกษา: " . $student['status'];
                    } else {
                        // ดึงข้อมูลครูที่ปรึกษา
                        $stmt = $conn->prepare("
                            SELECT t.teacher_id, u.title, u.first_name, u.last_name, d.department_name,
                                   t.position, ca.is_primary
                            FROM class_advisors ca
                            JOIN teachers t ON ca.teacher_id = t.teacher_id
                            JOIN users u ON t.user_id = u.user_id
                            LEFT JOIN departments d ON t.department_id = d.department_id
                            WHERE ca.class_id = ?
                            ORDER BY ca.is_primary DESC
                        ");
                        $stmt->execute([$student['current_class_id']]);
                        $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $_SESSION['student_advisors'] = $advisors;

                        // บันทึกกิจกรรมการค้นหาสำเร็จ
                        logRegistrationActivity($conn, $user_id, "search_student_success", "Student ID: " . $student_code);

                        // ไปยังขั้นตอนยืนยันข้อมูล
                        header('Location: register.php?step=3');
                        exit;
                    }
                }
            } else {
                // ไม่พบข้อมูลนักศึกษา
                $error_message = "ไม่พบข้อมูลนักศึกษารหัส: " . $student_code . " ในระบบ";


                $stmt = $conn->prepare("SELECT * FROM students WHERE student_code = ?");
                $stmt->execute([$student_code]);
                $pending_student = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($pending_student) {
                    // พบข้อมูลในตาราง pending - เก็บข้อมูลใน session
                    $_SESSION['student_code'] = $pending_student['student_code'];
                    $_SESSION['student_title'] = $pending_student['title'];
                    $_SESSION['student_first_name'] = $pending_student['first_name'];
                    $_SESSION['student_last_name'] = $pending_student['last_name'];

                    // บันทึกกิจกรรมการค้นหา
                    logRegistrationActivity($conn, $user_id, "search_student_pending", "Student ID: " . $student_code);

                    // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติม
                    header('Location: register.php?step=3manual');
                    exit;
                } else {
                    // แนะนำให้ลงทะเบียนใหม่
                    $success_message = "หากคุณเป็นนักเรียนใหม่ สามารถลงทะเบียนโดยกรอกข้อมูลเอง";

                    // บันทึกกิจกรรมการค้นหาไม่พบข้อมูล
                    logRegistrationActivity($conn, $user_id, "search_student_not_found", "Student ID: " . $student_code);
                }
            }
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $e->getMessage();
            error_log("Database error in searching student: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "search_student_error", "Error: " . $e->getMessage());
        }
    }
}

// 2. ประมวลผลการยืนยันข้อมูลนักศึกษา
if (isset($_POST['confirm_student_info'])) {
    // ตรวจสอบว่ายอมรับนโยบายความเป็นส่วนตัวหรือไม่
    if (!isset($_POST['privacy_policy'])) {
        $error_message = "กรุณายอมรับนโยบายความเป็นส่วนตัวเพื่อดำเนินการต่อ";
    }
    // ตรวจสอบว่ามีข้อมูลใน session ครบถ้วนหรือไม่
    elseif (!isset($_SESSION['student_id']) || !isset($_SESSION['student_code'])) {
        $error_message = "ข้อมูลนักศึกษาไม่ครบถ้วน กรุณาเริ่มต้นใหม่";
    } else {
        try {



            // อัปเดตข้อมูลผู้ใช้จาก LINE ให้ตรงกับข้อมูลนักศึกษา
            $stmt = $conn->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, title = ?, phone_number = ?, email = ?, 
                    gdpr_consent = 1, gdpr_consent_date = NOW(), updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $_SESSION['student_first_name'],
                $_SESSION['student_last_name'],
                $_SESSION['student_title'],
                $_SESSION['student_phone'] ?? null,
                $_SESSION['student_email'] ?? null,
                $user_id
            ]);

            // เชื่อมโยงข้อมูล user กับ student ที่มีอยู่แล้ว
            $stmt = $conn->prepare("
                UPDATE students 
                SET user_id = ?, updated_at = NOW()
                WHERE student_id = ?
            ");
            $stmt->execute([$user_id, $_SESSION['student_id']]);

            // ลบข้อมูลในตาราง users อันเก่า
            $delete_sql = "DELETE FROM users WHERE line_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->execute([$_SESSION['line_id']]);

            // สร้าง QR Code สำหรับนักเรียน
            createStudentQRCode($conn, $_SESSION['student_id'], $_SESSION['student_code']);

            // บันทึกกิจกรรมการยืนยันข้อมูล
            logRegistrationActivity($conn, $user_id, "confirm_existing_student", "Student ID: " . $_SESSION['student_code']);

            // บันทึกข้อความสำเร็จสำหรับขั้นตอนถัดไป
            $_SESSION['registration_complete'] = true;
            $_SESSION['student_registered_name'] = $_SESSION['student_first_name'] . ' ' . $_SESSION['student_last_name'];

            // ไปยังขั้นตอนเสร็จสิ้น
            header('Location: register.php?step=7');
            exit;
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            error_log("Database error in confirming student info: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "confirm_student_error", "Error: " . $e->getMessage());
        }
    }
}

// 3. ประมวลผลการกรอกข้อมูลนักศึกษาเอง
if (isset($_POST['submit_manual_info'])) {
    $student_code = isset($_POST['student_code']) ? cleanInput($_POST['student_code']) : '';
    $title = isset($_POST['title']) ? cleanInput($_POST['title']) : '';
    $first_name = isset($_POST['first_name']) ? cleanInput($_POST['first_name']) : '';
    $last_name = isset($_POST['last_name']) ? cleanInput($_POST['last_name']) : '';
    $phone = isset($_POST['phone']) ? cleanInput($_POST['phone']) : '';
    $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';

    // ตรวจสอบข้อมูล
    $validation_errors = [];

    if (!validateStudentCode($student_code)) {
        $validation_errors[] = "รหัสนักศึกษาไม่ถูกต้อง (ต้องเป็นตัวเลข 11 หลัก)";
    }

    if (empty($title)) {
        $validation_errors[] = "กรุณาเลือกคำนำหน้า";
    }

    if (empty($first_name)) {
        $validation_errors[] = "กรุณากรอกชื่อ";
    }

    if (empty($last_name)) {
        $validation_errors[] = "กรุณากรอกนามสกุล";
    }

    if (!validatePhone($phone)) {
        $validation_errors[] = "รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง";
    }

    if (!validateEmail($email)) {
        $validation_errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    }

    if (!isset($_POST['privacy_policy'])) {
        $validation_errors[] = "กรุณายอมรับนโยบายความเป็นส่วนตัว";
    }

    // ถ้ามีข้อผิดพลาด
    if (!empty($validation_errors)) {
        $error_message = implode("<br>", $validation_errors);
    } else {
        try {
            // บันทึกข้อมูลใน session
            $_SESSION['student_code'] = $student_code;
            $_SESSION['student_title'] = $title;
            $_SESSION['student_first_name'] = $first_name;
            $_SESSION['student_last_name'] = $last_name;
            $_SESSION['student_phone'] = $phone;
            $_SESSION['student_email'] = $email;

            // ตรวจสอบว่ารหัสนักศึกษาซ้ำหรือไม่
            if (isStudentCodeExists($conn, $student_code)) {
                $error_message = "รหัสนักศึกษานี้มีในระบบแล้ว กรุณาใช้ฟังก์ชันค้นหา";

                // บันทึกกิจกรรมการลงทะเบียนซ้ำ
                logRegistrationActivity($conn, $user_id, "manual_registration_duplicate", "Student ID: " . $student_code);
            } else {

                /*    $stmt = $conn->prepare("
                    INSERT INTO students(student_code, title, created_at,user_id)
                    VALUES (?, ?, NOW(), ?)
                    ON DUPLICATE KEY UPDATE
                    title = VALUES(title),created_at = NOW(),student_code = VALUES(student_code),user_id = VALUES(user_id)
                ");
                $stmt->execute([$student_code, $title, $user_id]);
                */

                // อัปเดตข้อมูลผู้ใช้
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET title = ?, first_name = ?, last_name = ?, phone_number = ?, email = ?, 
                        gdpr_consent = 1, gdpr_consent_date = NOW(), updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$title, $first_name, $last_name, $phone, $email, $user_id]);

                // บันทึกกิจกรรมการกรอกข้อมูลเอง
                logRegistrationActivity($conn, $user_id, "manual_info_submitted", "Student ID: " . $student_code);

                // ไปยังขั้นตอนถัดไป
                header('Location: register.php?step=4');
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            error_log("Database error in submitting manual info: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "manual_info_error", "Error: " . $e->getMessage());
        }
    }
}

// 4. ประมวลผลการค้นหาครูที่ปรึกษา
if (isset($_POST['search_teacher'])) {
    $search_term = isset($_POST['teacher_name']) ? cleanInput($_POST['teacher_name']) : '';

    if (empty($search_term)) {
        $error_message = "กรุณากรอกชื่อหรือนามสกุลครูที่ปรึกษา";
    } elseif (strlen($search_term) < 2) {
        $error_message = "กรุณากรอกชื่อหรือนามสกุลอย่างน้อย 2 ตัวอักษร";
    } else {
        try {
            // ค้นหาครูที่ปรึกษา
            $stmt = $conn->prepare("
                SELECT t.teacher_id, u.title, u.first_name, u.last_name, d.department_name, t.position
                FROM teachers t
                JOIN users u ON t.user_id = u.user_id
                LEFT JOIN departments d ON t.department_id = d.department_id
                WHERE u.first_name LIKE ? OR u.last_name LIKE ?
                ORDER BY u.first_name, u.last_name
                LIMIT 20
            ");
            $search_param = "%" . $search_term . "%";
            $stmt->execute([$search_param, $search_param]);
            $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($teachers) > 0) {
                $_SESSION['search_teacher_results'] = $teachers;
                $success_message = "พบครูที่ปรึกษา " . count($teachers) . " คน";

                // บันทึกกิจกรรมการค้นหาครู
                logRegistrationActivity($conn, $user_id, "search_teacher_success", "Found " . count($teachers) . " teachers");
            } else {
                $error_message = "ไม่พบครูที่ปรึกษาตามคำค้นหา";

                // บันทึกกิจกรรมการค้นหาครูไม่พบ
                logRegistrationActivity($conn, $user_id, "search_teacher_not_found", "Search term: " . $search_term);
            }
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $e->getMessage();
            error_log("Database error in searching teacher: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "search_teacher_error", "Error: " . $e->getMessage());
        }
    }
}

// 5. ประมวลผลการเลือกครูที่ปรึกษา
if (isset($_POST['select_teacher'])) {
    if (!isset($_POST['teacher_id']) || empty($_POST['teacher_id'])) {
        $error_message = "กรุณาเลือกครูที่ปรึกษา";
    } else {
        $teacher_id = $_POST['teacher_id'];

        try {
            // ดึงข้อมูลครู
            $stmt = $conn->prepare("
                SELECT t.teacher_id, u.title, u.first_name, u.last_name, d.department_name, t.position
                FROM teachers t
                JOIN users u ON t.user_id = u.user_id
                LEFT JOIN departments d ON t.department_id = d.department_id
                WHERE t.teacher_id = ?
            ");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($teacher) {
                $_SESSION['selected_teacher'] = $teacher;

                // ดึงข้อมูลชั้นเรียนที่ครูเป็นที่ปรึกษา
                $stmt = $conn->prepare("
                    SELECT c.class_id, c.level, c.group_number, d.department_name, ca.is_primary,
                           (SELECT COUNT(*) FROM students s WHERE s.current_class_id = c.class_id AND s.status = 'กำลังศึกษา') AS student_count
                    FROM class_advisors ca
                    JOIN classes c ON ca.class_id = c.class_id
                    JOIN departments d ON c.department_id = d.department_id
                    WHERE ca.teacher_id = ? AND c.academic_year_id = ? AND c.is_active = 1
                    ORDER BY c.level, d.department_name, c.group_number
                ");
                $stmt->execute([$teacher_id, $current_academic_year_id]);
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($classes) > 0) {
                    $_SESSION['teacher_classes'] = $classes;
                    $success_message = "พบชั้นเรียน " . count($classes) . " ห้องที่ครูเป็นที่ปรึกษา";

                    // บันทึกกิจกรรมการเลือกครู
                    logRegistrationActivity($conn, $user_id, "select_teacher_success", "Teacher ID: " . $teacher_id);
                } else {
                    // ถึงแม้จะไม่พบชั้นเรียน ยังคงไปยังขั้นตอนถัดไป
                    $warning_message = "ครูที่เลือกไม่มีชั้นเรียนที่เป็นที่ปรึกษาในปีการศึกษาปัจจุบัน";

                    // บันทึกกิจกรรมการเลือกครูที่ไม่มีชั้นเรียน
                    logRegistrationActivity($conn, $user_id, "select_teacher_no_classes", "Teacher ID: " . $teacher_id);
                }

                // ไปยังขั้นตอนถัดไป
                header('Location: register.php?step=5');
                exit;
            } else {
                $error_message = "ไม่พบข้อมูลครูที่ปรึกษา";

                // บันทึกข้อผิดพลาด
                logRegistrationActivity($conn, $user_id, "select_teacher_not_found", "Teacher ID: " . $teacher_id);
            }
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
            error_log("Database error in selecting teacher: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "select_teacher_error", "Error: " . $e->getMessage());
        }
    }
}

// 6. ประมวลผลการเลือกชั้นเรียน
if (isset($_POST['select_class'])) {
    if (!isset($_POST['class_id']) || empty($_POST['class_id'])) {
        $error_message = "กรุณาเลือกชั้นเรียน";
    } else {
        $class_id = $_POST['class_id'];

        try {
            // ดึงข้อมูลชั้นเรียน
            $stmt = $conn->prepare("
                SELECT c.class_id, c.level, c.group_number, d.department_id, d.department_name
                FROM classes c
                JOIN departments d ON c.department_id = d.department_id
                WHERE c.class_id = ?
            ");
            $stmt->execute([$class_id]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($class) {
                $_SESSION['selected_class'] = $class;
                $_SESSION['class_id'] = $class['class_id'];

                // ดึงข้อมูลครูที่ปรึกษาของชั้นเรียนนี้
                $stmt = $conn->prepare("
                    SELECT t.teacher_id, u.title, u.first_name, u.last_name, d.department_name, t.position, ca.is_primary
                    FROM class_advisors ca
                    JOIN teachers t ON ca.teacher_id = t.teacher_id
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN departments d ON t.department_id = d.department_id
                    WHERE ca.class_id = ?
                    ORDER BY ca.is_primary DESC
                ");
                $stmt->execute([$class_id]);
                $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($advisors) > 0) {
                    $_SESSION['class_advisors'] = $advisors;
                }

                // บันทึกกิจกรรมการเลือกชั้นเรียน
                logRegistrationActivity($conn, $user_id, "select_class_success", "Class ID: " . $class_id);

                // ไปยังขั้นตอนถัดไป
                header('Location: register.php?step=6');
                exit;
            } else {
                $error_message = "ไม่พบข้อมูลชั้นเรียน";

                // บันทึกข้อผิดพลาด
                logRegistrationActivity($conn, $user_id, "select_class_not_found", "Class ID: " . $class_id);
            }
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage();
            error_log("Database error in selecting class: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "select_class_error", "Error: " . $e->getMessage());
        }
    }
}

// 7. ประมวลผลการกรอกข้อมูลชั้นเรียนเอง
if (isset($_POST['submit_manual_class'])) {
    $level = isset($_POST['level']) ? cleanInput($_POST['level']) : '';
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
    $group_number = isset($_POST['group_number']) ? (int)$_POST['group_number'] : 0;

    // ตรวจสอบข้อมูล
    $validation_errors = [];

    if (empty($level)) {
        $validation_errors[] = "กรุณาเลือกระดับชั้น";
    }

    if ($department_id <= 0) {
        $validation_errors[] = "กรุณาเลือกแผนกวิชา";
    }

    if ($group_number <= 0) {
        $validation_errors[] = "กรุณาเลือกกลุ่มเรียน";
    }

    // ถ้ามีข้อผิดพลาด
    if (!empty($validation_errors)) {
        $error_message = implode("<br>", $validation_errors);
    } else {
        try {
            // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่
            $stmt = $conn->prepare("
                SELECT c.class_id, c.level, c.group_number, d.department_name
                FROM classes c
                JOIN departments d ON c.department_id = d.department_id
                WHERE c.academic_year_id = ? AND c.level = ? AND c.department_id = ? AND c.group_number = ?
            ");
            $stmt->execute([$current_academic_year_id, $level, $department_id, $group_number]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($class) {
                // พบชั้นเรียนที่ตรงกัน
                $_SESSION['selected_class'] = $class;
                $_SESSION['class_id'] = $class['class_id'];

                // ดึงข้อมูลครูที่ปรึกษาของชั้นเรียนนี้
                $stmt = $conn->prepare("
                    SELECT t.teacher_id, u.title, u.first_name, u.last_name, d.department_name, t.position, ca.is_primary
                    FROM class_advisors ca
                    JOIN teachers t ON ca.teacher_id = t.teacher_id
                    JOIN users u ON t.user_id = u.user_id
                    LEFT JOIN departments d ON t.department_id = d.department_id
                    WHERE ca.class_id = ?
                    ORDER BY ca.is_primary DESC
                ");
                $stmt->execute([$class['class_id']]);
                $advisors = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($advisors) > 0) {
                    $_SESSION['class_advisors'] = $advisors;
                }

                // บันทึกกิจกรรมการระบุชั้นเรียนที่มีอยู่แล้ว
                logRegistrationActivity($conn, $user_id, "manual_class_existing", "Class ID: " . $class['class_id']);
            } else {
                // ไม่พบชั้นเรียน - สร้างใหม่
                $stmt = $conn->prepare("
                    INSERT INTO classes (academic_year_id, level, department_id, group_number, is_active, created_at)
                    VALUES (?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$current_academic_year_id, $level, $department_id, $group_number]);
                $class_id = $conn->lastInsertId();

                // ดึงข้อมูลแผนกวิชา
                $stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
                $stmt->execute([$department_id]);
                $department = $stmt->fetch(PDO::FETCH_ASSOC);

                $_SESSION['selected_class'] = [
                    'class_id' => $class_id,
                    'level' => $level,
                    'group_number' => $group_number,
                    'department_name' => $department['department_name']
                ];
                $_SESSION['class_id'] = $class_id;

                // บันทึกกิจกรรมการสร้างชั้นเรียนใหม่
                logRegistrationActivity($conn, $user_id, "manual_class_created", "New Class ID: " . $class_id);
            }

            // ไปยังขั้นตอนถัดไป
            header('Location: register.php?step=6');
            exit;
        } catch (PDOException $e) {
            $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            error_log("Database error in submitting manual class: " . $e->getMessage());

            // บันทึกข้อผิดพลาด
            logRegistrationActivity($conn, $user_id, "manual_class_error", "Error: " . $e->getMessage());
        }
    }
}

// 8. ประมวลผลการบันทึกข้อมูลเพิ่มเติมและสร้างนักเรียนใหม่
if (isset($_POST['submit_additional_info'])) {
    // ตรวจสอบการยอมรับนโยบายและข้อตกลง
    if (!isset($_POST['gdpr_consent']) || !isset($_POST['terms_agreement'])) {
        $error_message = "กรุณายอมรับนโยบายความเป็นส่วนตัวและข้อตกลงการใช้งาน";
    }
    // ตรวจสอบว่ามีข้อมูลพื้นฐานครบถ้วนหรือไม่
    elseif (!isset($_SESSION['student_code']) || !isset($_SESSION['student_first_name']) || !isset($_SESSION['class_id'])) {
        $error_message = "ข้อมูลไม่ครบถ้วน กรุณาเริ่มต้นใหม่";
    } else {
        $emergency_contact = isset($_POST['emergency_contact']) ? cleanInput($_POST['emergency_contact']) : '';

        // ตรวจสอบรูปแบบเบอร์โทรฉุกเฉิน
        if (!empty($emergency_contact) && !validatePhone($emergency_contact)) {
            $error_message = "รูปแบบเบอร์โทรฉุกเฉินไม่ถูกต้อง";
        } else {
            try {
                // อัปเดตข้อมูลผู้ใช้
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET gdpr_consent = 1, gdpr_consent_date = NOW(), updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$user_id]);

                // สร้างนักเรียนใหม่
                $stmt = $conn->prepare("
                    INSERT INTO students (user_id, student_code, title, current_class_id, status, created_at)
                    VALUES (?, ?, ?, ?, 'กำลังศึกษา', NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $_SESSION['student_code'],
                    $_SESSION['student_title'],
                    $_SESSION['class_id']
                ]);
                $student_id = $conn->lastInsertId();

                // บันทึกข้อมูลใน student_academic_records
                $stmt = $conn->prepare("
                    INSERT INTO student_academic_records (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, created_at)
                    VALUES (?, ?, ?, 0, 0, NOW())
                ");
                $stmt->execute([
                    $student_id,
                    $current_academic_year_id,
                    $_SESSION['class_id']
                ]);

                // บันทึกข้อมูลฉุกเฉิน (ถ้ามี)
                if (!empty($emergency_contact)) {
                    $stmt = $conn->prepare("
                        INSERT INTO student_emergency_contacts (student_id, contact_number, created_at)
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$student_id, $emergency_contact]);
                }

                // สร้าง QR Code สำหรับนักเรียน
                createStudentQRCode($conn, $student_id, $_SESSION['student_code']);

                // บันทึกกิจกรรมการลงทะเบียนสำเร็จ
                logRegistrationActivity($conn, $user_id, "registration_complete", "New Student ID: " . $student_id);

                // บันทึกข้อความสำเร็จสำหรับขั้นตอนถัดไป
                $_SESSION['registration_complete'] = true;
                $_SESSION['student_registered_name'] = $_SESSION['student_first_name'] . ' ' . $_SESSION['student_last_name'];

                // ลบข้อมูลชั่วคราวออกจาก session
                unset($_SESSION['student_code']);
                unset($_SESSION['student_title']);
                unset($_SESSION['student_first_name']);
                unset($_SESSION['student_last_name']);
                unset($_SESSION['student_phone']);
                unset($_SESSION['student_email']);
                unset($_SESSION['selected_teacher']);
                unset($_SESSION['selected_class']);
                unset($_SESSION['class_id']);
                unset($_SESSION['search_teacher_results']);
                unset($_SESSION['teacher_classes']);

                // ไปยังขั้นตอนเสร็จสิ้น
                header('Location: register.php?step=7');
                exit;
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
                error_log("Database error in completing registration: " . $e->getMessage());

                // บันทึกข้อผิดพลาด
                logRegistrationActivity($conn, $user_id, "registration_error", "Error: " . $e->getMessage());
            }
        }
    }
}
