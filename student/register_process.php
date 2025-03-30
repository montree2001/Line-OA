<?php
/**
 * register_process.php - จัดการการประมวลผลฟอร์มการลงทะเบียน
 */

// ตรวจสอบว่ามีการส่งข้อมูลฟอร์มมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ตรวจสอบแอคชั่นที่ส่งมา
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    switch ($action) {
        // ขั้นตอนที่ 2: ค้นหารหัสนักศึกษา
        case 'search_student':
            $student_code = isset($_POST['student_code']) ? trim($_POST['student_code']) : '';
            
            // ตรวจสอบว่ากรอกรหัสนักศึกษาหรือไม่
            if (empty($student_code)) {
                $error_message = "กรุณากรอกรหัสนักศึกษา";
                break;
            }
            
            try {
                // ค้นหาข้อมูลนักศึกษาจากฐานข้อมูล
                $sql = "SELECT s.student_id, s.student_code, s.title, s.current_class_id, u.first_name, u.last_name 
                        FROM students s 
                        JOIN users u ON s.user_id = u.user_id 
                        WHERE s.student_code = :student_code";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':student_code', $student_code, PDO::PARAM_STR);
                $stmt->execute();
                $student = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($student) {
                    // พบข้อมูลนักศึกษา เก็บในเซสชัน
                    $_SESSION['student_id'] = $student['student_id'];
                    $_SESSION['student_code'] = $student['student_code'];
                    $_SESSION['student_title'] = $student['title'];
                    $_SESSION['student_first_name'] = $student['first_name'];
                    $_SESSION['student_last_name'] = $student['last_name'];
                    $_SESSION['current_class_id'] = $student['current_class_id'];
                    
                    // อัพเดต line_id ในตาราง users
                    $update_sql = "UPDATE users SET line_id = :line_id WHERE user_id = (
                        SELECT user_id FROM students WHERE student_id = :student_id
                    )";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bindParam(':line_id', $line_id, PDO::PARAM_STR);
                    $update_stmt->bindParam(':student_id', $student['student_id'], PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    // เปลี่ยนไปยังหน้ายืนยันข้อมูล
                    header('Location: register.php?step=3');
                    exit;
                } else {
                    // ไม่พบข้อมูลนักศึกษา
                    // เก็บรหัสนักศึกษาไว้ในเซสชันเพื่อใช้ในการลงทะเบียน
                    $_SESSION['student_code'] = $student_code;
                    
                    // เปลี่ยนไปยังหน้ากรอกข้อมูลเอง
                    header('Location: register.php?step=33');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $e->getMessage();
            }
            break;

        // ขั้นตอนที่ 3: กรอกข้อมูลเอง (กรณีไม่พบข้อมูล)
        case 'manual_info':
            $title = isset($_POST['title']) ? $_POST['title'] : '';
            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            
            // ตรวจสอบข้อมูล
            if (empty($title) || empty($first_name) || empty($last_name)) {
                $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
                break;
            }
            
            // เก็บข้อมูลในเซสชัน
            $_SESSION['student_title'] = $title;
            $_SESSION['student_first_name'] = $first_name;
            $_SESSION['student_last_name'] = $last_name;
            
            // ไปยังขั้นตอนถัดไป
            header('Location: register.php?step=4');
            exit;
            break;

        // ขั้นตอนที่ 4: ค้นหาครูที่ปรึกษา
        case 'search_teacher':
            $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';
            
            if (empty($search_term)) {
                $error_message = "กรุณากรอกชื่อหรือนามสกุลของครูที่ปรึกษา";
                break;
            }
            
            try {
                // ค้นหาครูที่ปรึกษาจากฐานข้อมูล
                $sql = "SELECT t.teacher_id, t.title, t.first_name, t.last_name, d.department_name, t.position
                        FROM teachers t
                        LEFT JOIN departments d ON t.department_id = d.department_id
                        WHERE t.first_name LIKE :search_term OR t.last_name LIKE :search_term
                        LIMIT 10";
                $stmt = $conn->prepare($sql);
                $search_param = "%" . $search_term . "%";
                $stmt->bindParam(':search_term', $search_param, PDO::PARAM_STR);
                $stmt->execute();
                $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // เก็บผลลัพธ์ในเซสชัน
                $_SESSION['search_teacher_results'] = $teachers;
                $_SESSION['search_teacher_term'] = $search_term;
                
                // กลับไปหน้าเดิมเพื่อแสดงผลการค้นหา
                header('Location: register.php?step=4');
                exit;
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $e->getMessage();
            }
            break;

        // ขั้นตอนที่ 4: เลือกครูที่ปรึกษา
        case 'select_teacher':
            $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
            
            if ($teacher_id <= 0) {
                $error_message = "กรุณาเลือกครูที่ปรึกษา";
                break;
            }
            
            try {
                // ค้นหาชั้นเรียนที่ครูดูแล
                $sql = "SELECT ca.class_id, c.level, d.department_name, c.group_number 
                        FROM class_advisors ca
                        JOIN classes c ON ca.class_id = c.class_id
                        JOIN departments d ON c.department_id = d.department_id
                        WHERE ca.teacher_id = :teacher_id AND c.academic_year_id = :academic_year_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':teacher_id', $teacher_id, PDO::PARAM_INT);
                $stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                $stmt->execute();
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // เก็บข้อมูลในเซสชัน
                $_SESSION['teacher_id'] = $teacher_id;
                $_SESSION['teacher_classes'] = $classes;
                
                // ไปยังขั้นตอนถัดไป
                header('Location: register.php?step=5');
                exit;
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการเลือกครูที่ปรึกษา: " . $e->getMessage();
            }
            break;

        // ขั้นตอนที่ 5: เลือกชั้นเรียน
        case 'select_class':
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            
            if ($class_id <= 0) {
                $error_message = "กรุณาเลือกชั้นเรียน";
                break;
            }
            
            // เก็บข้อมูลในเซสชัน
            $_SESSION['class_id'] = $class_id;
            
            // ไปยังขั้นตอนถัดไป
            header('Location: register.php?step=6');
            exit;
            break;

        // ขั้นตอนที่ 5 (กรณีเลือกชั้นเรียนเอง): กรอกข้อมูลชั้นเรียน
        case 'manual_class':
            $level = isset($_POST['level']) ? $_POST['level'] : '';
            $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
            $group_number = isset($_POST['group_number']) ? intval($_POST['group_number']) : 0;
            
            if (empty($level) || $department_id <= 0 || $group_number <= 0) {
                $error_message = "กรุณากรอกข้อมูลชั้นเรียนให้ครบถ้วน";
                break;
            }
            
            try {
                // ค้นหาชั้นเรียนที่ตรงกับข้อมูลที่กรอก
                $sql = "SELECT class_id FROM classes 
                        WHERE level = :level AND department_id = :department_id AND group_number = :group_number
                        AND academic_year_id = :academic_year_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':level', $level, PDO::PARAM_STR);
                $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
                $stmt->bindParam(':group_number', $group_number, PDO::PARAM_INT);
                $stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                $stmt->execute();
                $class = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($class) {
                    // พบชั้นเรียนที่ตรงกับข้อมูล
                    $_SESSION['class_id'] = $class['class_id'];
                } else {
                    // ไม่พบชั้นเรียน ต้องสร้างชั้นเรียนใหม่
                    $insert_sql = "INSERT INTO classes (academic_year_id, level, department_id, group_number, created_at)
                                  VALUES (:academic_year_id, :level, :department_id, :group_number, NOW())";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $insert_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':level', $level, PDO::PARAM_STR);
                    $insert_stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
                    $insert_stmt->bindParam(':group_number', $group_number, PDO::PARAM_INT);
                    $insert_stmt->execute();
                    
                    $_SESSION['class_id'] = $conn->lastInsertId();
                }
                
                // ไปยังขั้นตอนถัดไป
                header('Location: register.php?step=6');
                exit;
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูลชั้นเรียน: " . $e->getMessage();
            }
            break;

        // ขั้นตอนที่ 6: กรอกข้อมูลเพิ่มเติม
        case 'additional_info':
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $gdpr_consent = isset($_POST['gdpr_consent']) ? 1 : 0;
            
            if (!$gdpr_consent) {
                $error_message = "กรุณายินยอมให้เก็บข้อมูลส่วนบุคคล";
                break;
            }
            
            // เก็บข้อมูลในเซสชัน
            $_SESSION['phone'] = $phone;
            $_SESSION['email'] = $email;
            $_SESSION['gdpr_consent'] = $gdpr_consent;
            
            try {
                // เริ่มทำ Transaction
                $conn->beginTransaction();
                
                // ตรวจสอบว่ามี user_id อยู่แล้วหรือไม่
                if (!isset($_SESSION['student_id'])) {
                    // สร้างผู้ใช้ใหม่
                    $user_sql = "INSERT INTO users (line_id, role, title, first_name, last_name, profile_picture, phone_number, email, gdpr_consent, gdpr_consent_date, created_at, last_login)
                                VALUES (:line_id, 'student', :title, :first_name, :last_name, :profile_picture, :phone, :email, :gdpr_consent, NOW(), NOW(), NOW())";
                    $user_stmt = $conn->prepare($user_sql);
                    $user_stmt->bindParam(':line_id', $line_id, PDO::PARAM_STR);
                    $user_stmt->bindParam(':title', $_SESSION['student_title'], PDO::PARAM_STR);
                    $user_stmt->bindParam(':first_name', $_SESSION['student_first_name'], PDO::PARAM_STR);
                    $user_stmt->bindParam(':last_name', $_SESSION['student_last_name'], PDO::PARAM_STR);
                    $user_stmt->bindParam(':profile_picture', $profile_picture, PDO::PARAM_STR);
                    $user_stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $user_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $user_stmt->bindParam(':gdpr_consent', $gdpr_consent, PDO::PARAM_INT);
                    $user_stmt->execute();
                    
                    $user_id = $conn->lastInsertId();
                    
                    // สร้างนักเรียนใหม่
                    $student_sql = "INSERT INTO students (user_id, student_code, title, current_class_id, status, created_at)
                                  VALUES (:user_id, :student_code, :title, :class_id, 'กำลังศึกษา', NOW())";
                    $student_stmt = $conn->prepare($student_sql);
                    $student_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                    $student_stmt->bindParam(':student_code', $_SESSION['student_code'], PDO::PARAM_STR);
                    $student_stmt->bindParam(':title', $_SESSION['student_title'], PDO::PARAM_STR);
                    $student_stmt->bindParam(':class_id', $_SESSION['class_id'], PDO::PARAM_INT);
                    $student_stmt->execute();
                    
                    $student_id = $conn->lastInsertId();
                    
                    // สร้างประวัติการศึกษา
                    $record_sql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, created_at)
                                 VALUES (:student_id, :academic_year_id, :class_id, 0, 0, NOW())";
                    $record_stmt = $conn->prepare($record_sql);
                    $record_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                    $record_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                    $record_stmt->bindParam(':class_id', $_SESSION['class_id'], PDO::PARAM_INT);
                    $record_stmt->execute();
                } else {
                    // อัพเดตข้อมูลที่มีอยู่แล้ว
                    $user_update_sql = "UPDATE users SET 
                                      first_name = :first_name,
                                      last_name = :last_name,
                                      phone_number = :phone,
                                      email = :email,
                                      gdpr_consent = :gdpr_consent,
                                      gdpr_consent_date = NOW(),
                                      updated_at = NOW()
                                      WHERE user_id = (SELECT user_id FROM students WHERE student_id = :student_id)";
                    $user_update_stmt = $conn->prepare($user_update_sql);
                    $user_update_stmt->bindParam(':first_name', $_SESSION['student_first_name'], PDO::PARAM_STR);
                    $user_update_stmt->bindParam(':last_name', $_SESSION['student_last_name'], PDO::PARAM_STR);
                    $user_update_stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
                    $user_update_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $user_update_stmt->bindParam(':gdpr_consent', $gdpr_consent, PDO::PARAM_INT);
                    $user_update_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                    $user_update_stmt->execute();
                    
                    // อัพเดตข้อมูลนักเรียน
                    $student_update_sql = "UPDATE students SET 
                                        title = :title,
                                        current_class_id = :class_id,
                                        updated_at = NOW()
                                        WHERE student_id = :student_id";
                    $student_update_stmt = $conn->prepare($student_update_sql);
                    $student_update_stmt->bindParam(':title', $_SESSION['student_title'], PDO::PARAM_STR);
                    $student_update_stmt->bindParam(':class_id', $_SESSION['class_id'], PDO::PARAM_INT);
                    $student_update_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                    $student_update_stmt->execute();
                    
                    // ตรวจสอบว่ามีประวัติการศึกษาในปีการศึกษานี้หรือไม่
                    $check_record_sql = "SELECT record_id FROM student_academic_records 
                                      WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
                    $check_record_stmt = $conn->prepare($check_record_sql);
                    $check_record_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                    $check_record_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                    $check_record_stmt->execute();
                    $record = $check_record_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$record) {
                        // สร้างประวัติการศึกษาใหม่
                        $record_sql = "INSERT INTO student_academic_records (student_id, academic_year_id, class_id, total_attendance_days, total_absence_days, created_at)
                                     VALUES (:student_id, :academic_year_id, :class_id, 0, 0, NOW())";
                        $record_stmt = $conn->prepare($record_sql);
                        $record_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                        $record_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                        $record_stmt->bindParam(':class_id', $_SESSION['class_id'], PDO::PARAM_INT);
                        $record_stmt->execute();
                    } else {
                        // อัพเดตประวัติการศึกษาที่มีอยู่แล้ว
                        $update_record_sql = "UPDATE student_academic_records SET 
                                           class_id = :class_id,
                                           updated_at = NOW()
                                           WHERE student_id = :student_id AND academic_year_id = :academic_year_id";
                        $update_record_stmt = $conn->prepare($update_record_sql);
                        $update_record_stmt->bindParam(':class_id', $_SESSION['class_id'], PDO::PARAM_INT);
                        $update_record_stmt->bindParam(':student_id', $_SESSION['student_id'], PDO::PARAM_INT);
                        $update_record_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                        $update_record_stmt->execute();
                    }
                }
                
                // ทำ Commit เมื่อทำทุกอย่างสำเร็จ
                $conn->commit();
                
                // ล้างข้อมูลในเซสชัน
                unset($_SESSION['student_code']);
                unset($_SESSION['student_title']);
                unset($_SESSION['student_first_name']);
                unset($_SESSION['student_last_name']);
                unset($_SESSION['search_teacher_results']);
                unset($_SESSION['search_teacher_term']);
                unset($_SESSION['teacher_id']);
                unset($_SESSION['teacher_classes']);
                unset($_SESSION['class_id']);
                unset($_SESSION['phone']);
                unset($_SESSION['email']);
                unset($_SESSION['gdpr_consent']);
                
                // ไปยังหน้าเสร็จสิ้น
                header('Location: register.php?step=7');
                exit;
            } catch (PDOException $e) {
                // ถ้ามีข้อผิดพลาด ทำ Rollback
                $conn->rollBack();
                $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            }
            break;

        default:
            $error_message = "การดำเนินการไม่ถูกต้อง";
            break;
    }
}
?>