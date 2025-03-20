<?php
/**
 * register_process.php - ประมวลผลข้อมูลลงทะเบียนนักเรียน
 */

// ห้ามเข้าถึงโดยตรง
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

// กรณีกดปุ่มกรอกข้อมูลด้วยตนเอง
if (isset($_POST['manual_entry'])) {
    $_SESSION['student_code'] = $_POST['student_code'] ?? '';
    $_SESSION['search_attempted'] = true;
    header('Location: register.php?step=33');
    exit;
}

// จัดการข้อมูลที่ส่งมา
switch ($step) {
    case 2: // ขั้นตอนค้นหารหัสนักศึกษา
        $student_code = $_POST['student_code'] ?? '';
        $_SESSION['search_attempted'] = false; // รีเซ็ตค่าเริ่มต้น

        if (empty($student_code) || strlen($student_code) !== 11) {
            $error_message = "กรุณากรอกรหัสนักศึกษา 11 หลักให้ถูกต้อง";
        } else {
            // ตรวจสอบว่ามีข้อมูลนักศึกษาในฐานข้อมูลหรือไม่ (ในตาราง student_pending)
            try {
                $query = "SELECT * FROM student_pending WHERE student_code = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$student_code]);
                
                if ($stmt->rowCount() > 0) {
                    // พบข้อมูลนักศึกษาในตาราง student_pending
                    $student_data = $stmt->fetch(PDO::FETCH_ASSOC);

                    // บันทึกข้อมูลทั้งหมดไว้ใน session
                    $_SESSION['student_code'] = $student_code;
                    $_SESSION['student_title'] = $student_data['title'];
                    $_SESSION['student_first_name'] = $student_data['first_name'];
                    $_SESSION['student_last_name'] = $student_data['last_name'];
                    $_SESSION['student_level_system'] = $student_data['level_system'];
                    $_SESSION['student_class_level'] = $student_data['class_level'];
                    $_SESSION['student_department'] = $student_data['department'];
                    $_SESSION['student_group_number'] = $student_data['group_number'];
                    $_SESSION['search_attempted'] = false; // รีเซ็ตค่า

                    // ไปยังขั้นตอนถัดไป
                    header('Location: register.php?step=3');
                    exit;
                } else {
                    // ไม่พบข้อมูลนักศึกษา แสดงข้อความแจ้งเตือนและแสดงปุ่มกรอกข้อมูลเอง
                    $_SESSION['student_code'] = $student_code;
                    $_SESSION['search_attempted'] = true; // ตั้งค่า flag ว่าได้พยายามค้นหาแล้ว
                    
                    // กลับไปยังหน้าเดิมและแสดงปุ่มกรอกข้อมูลเอง
                    header('Location: register.php?step=2');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการค้นหาข้อมูล: " . $e->getMessage();
            }
        }
        break;

    case 33: // ขั้นตอนกรอกข้อมูลนักศึกษาเอง
        $title = $_POST['title'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $level_system = $_POST['level_system'] ?? '';
        $class_level = $_POST['class_level'] ?? '';
        $department = $_POST['department'] ?? '';
        $group_number = $_POST['group_number'] ?? '';

        if (empty($title) || empty($first_name) || empty($last_name) || empty($level_system) || 
            empty($class_level) || empty($department) || empty($group_number)) {
            $error_message = "กรุณากรอกข้อมูลให้ครบถ้วน";
        } else {
            // บันทึกข้อมูลใน session
            $_SESSION['student_title'] = $title;
            $_SESSION['student_first_name'] = $first_name;
            $_SESSION['student_last_name'] = $last_name;
            $_SESSION['student_level_system'] = $level_system;
            $_SESSION['student_class_level'] = $class_level;
            $_SESSION['student_department'] = $department;
            $_SESSION['student_group_number'] = $group_number;
            $_SESSION['search_attempted'] = false; // รีเซ็ตค่า

            // ในกรณีนี้ เราเก็บข้อมูลครบถ้วนแล้ว ให้ข้ามขั้นตอนค้นหาครูที่ปรึกษาและกรอกข้อมูลห้องเรียน
            // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติมทันที
            header('Location: register.php?step=6');
            exit;
        }
        break;

    // ส่วนที่เหลือเหมือนเดิม...
    
    case 4: // ขั้นตอนค้นหาครูที่ปรึกษา
        $teacher_name = $_POST['teacher_name'] ?? '';

        if (empty($teacher_name)) {
            $error_message = "กรุณากรอกชื่อครูที่ปรึกษา";
        } else {
            // ค้นหาครูที่ปรึกษาจากชื่อ
            try {
                $query = "SELECT t.teacher_id, u.first_name, u.last_name, t.department 
                         FROM teachers t 
                         JOIN users u ON t.user_id = u.user_id 
                         WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term";
                $stmt = $conn->prepare($query);
                $search_term = "%" . $teacher_name . "%";
                $stmt->bindParam(':search_term', $search_term, PDO::PARAM_STR);
                $stmt->execute();
                
                $_SESSION['search_teacher_name'] = $teacher_name;
                $_SESSION['search_teacher_results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($_SESSION['search_teacher_results']) > 0) {
                    // พบครูที่ปรึกษา แสดงชั้นเรียนที่ครูดูแล
                    header('Location: register.php?step=5');
                    exit;
                } else {
                    // ไม่พบครูที่ปรึกษา ให้ไปยังขั้นตอนกรอกข้อมูลห้องเรียนเอง
                    header('Location: register.php?step=55');
                    exit;
                }
            } catch (PDOException $e) {
                $error_message = "เกิดข้อผิดพลาดในการค้นหาครูที่ปรึกษา: " . $e->getMessage();
            }
        }
        break;
        
    case 5: // ขั้นตอนเลือกครูที่ปรึกษาและชั้นเรียน
        $teacher_id = $_POST['teacher_id'] ?? '';
        $class_id = $_POST['class_id'] ?? '';

        if (empty($teacher_id) || empty($class_id)) {
            $error_message = "กรุณาเลือกครูที่ปรึกษาและชั้นเรียน";
        } else {
            // บันทึกข้อมูลใน session
            $_SESSION['selected_teacher_id'] = $teacher_id;
            $_SESSION['selected_class_id'] = $class_id;

            // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติม
            header('Location: register.php?step=6');
            exit;
        }
        break;

    case 55: // ขั้นตอนกรอกข้อมูลห้องเรียนเอง
        $department = $_POST['department'] ?? '';
        $group_number = $_POST['group_number'] ?? '';

        if (empty($department) || empty($group_number)) {
            $error_message = "กรุณากรอกข้อมูลสาขาวิชาและกลุ่มเรียนให้ครบถ้วน";
        } else {
            // บันทึกข้อมูลใน session
            $_SESSION['student_department'] = $department;
            $_SESSION['student_group_number'] = $group_number;

            // ไปยังขั้นตอนกรอกข้อมูลเพิ่มเติม
            header('Location: register.php?step=6');
            exit;
        }
        break;

    case 6: // ขั้นตอนกรอกข้อมูลเพิ่มเติม
        $phone_number = $_POST['phone_number'] ?? '';
        $email = $_POST['email'] ?? '';
        $gdpr_consent = isset($_POST['gdpr_consent']) ? 1 : 0;

        // ตรวจสอบการยินยอมเก็บข้อมูลส่วนบุคคล
        if (!$gdpr_consent) {
            $error_message = "กรุณายินยอมให้เก็บข้อมูลส่วนบุคคลเพื่อดำเนินการต่อ";
        } else {
            // เริ่มต้น transaction
            try {
                $conn->beginTransaction();
                
                // 1. อัปเดตข้อมูลในตาราง users
                $update_user_sql = "UPDATE users SET 
                                   title = :title, 
                                   first_name = :first_name, 
                                   last_name = :last_name, 
                                   phone_number = :phone_number, 
                                   email = :email, 
                                   gdpr_consent = :gdpr_consent, 
                                   gdpr_consent_date = NOW() 
                                   WHERE user_id = :user_id";

                $user_stmt = $conn->prepare($update_user_sql);
                $user_stmt->bindParam(':title', $_SESSION['student_title'], PDO::PARAM_STR);
                $user_stmt->bindParam(':first_name', $_SESSION['student_first_name'], PDO::PARAM_STR);
                $user_stmt->bindParam(':last_name', $_SESSION['student_last_name'], PDO::PARAM_STR);
                $user_stmt->bindParam(':phone_number', $phone_number, PDO::PARAM_STR);
                $user_stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $user_stmt->bindParam(':gdpr_consent', $gdpr_consent, PDO::PARAM_INT);
                $user_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $user_stmt->execute();

                // 2. ตรวจสอบว่ามี class_id ที่เลือกไว้หรือไม่
                $class_id = null;
                if (isset($_SESSION['selected_class_id'])) {
                    // ใช้ class_id ที่เลือกไว้
                    $class_id = $_SESSION['selected_class_id'];
                } else {
                    // สร้างชั้นเรียนใหม่
                    $level = $_SESSION['student_level_system'] . $_SESSION['student_class_level'];
                    $department = $_SESSION['student_department'];
                    $group_number = $_SESSION['student_group_number'];
                    
                    // ตรวจสอบว่ามีชั้นเรียนนี้อยู่แล้วหรือไม่
                    $check_class_sql = "SELECT class_id FROM classes 
                                       WHERE academic_year_id = :academic_year_id 
                                       AND level = :level 
                                       AND department = :department 
                                       AND group_number = :group_number";
                    $check_class_stmt = $conn->prepare($check_class_sql);
                    $check_class_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                    $check_class_stmt->bindParam(':level', $level, PDO::PARAM_STR);
                    $check_class_stmt->bindParam(':department', $department, PDO::PARAM_STR);
                    $check_class_stmt->bindParam(':group_number', $group_number, PDO::PARAM_INT);
                    $check_class_stmt->execute();
                    
                    if ($check_class_stmt->rowCount() > 0) {
                        // ใช้ class_id ที่มีอยู่แล้ว
                        $class_row = $check_class_stmt->fetch(PDO::FETCH_ASSOC);
                        $class_id = $class_row['class_id'];
                    } else {
                        // สร้างชั้นเรียนใหม่
                        $new_class_sql = "INSERT INTO classes (academic_year_id, level, department, group_number, created_at) 
                                         VALUES (:academic_year_id, :level, :department, :group_number, NOW())";
                        $class_stmt = $conn->prepare($new_class_sql);
                        $class_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                        $class_stmt->bindParam(':level', $level, PDO::PARAM_STR);
                        $class_stmt->bindParam(':department', $department, PDO::PARAM_STR);
                        $class_stmt->bindParam(':group_number', $group_number, PDO::PARAM_INT);
                        $class_stmt->execute();
                        $class_id = $conn->lastInsertId();
                    }
                }

                // 3. เพิ่มข้อมูลในตาราง students
                $insert_student_sql = "INSERT INTO students (
                                      user_id, 
                                      student_code, 
                                      title, 
                                      level_system, 
                                      current_class_id, 
                                      status, 
                                      created_at
                                    ) VALUES (:user_id, :student_code, :title, :level_system, :current_class_id, 'กำลังศึกษา', NOW())";

                $student_stmt = $conn->prepare($insert_student_sql);
                $student_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                $student_stmt->bindParam(':student_code', $_SESSION['student_code'], PDO::PARAM_STR);
                $student_stmt->bindParam(':title', $_SESSION['student_title'], PDO::PARAM_STR);
                $student_stmt->bindParam(':level_system', $_SESSION['student_level_system'], PDO::PARAM_STR);
                $student_stmt->bindParam(':current_class_id', $class_id, PDO::PARAM_INT);
                $student_stmt->execute();
                $student_id = $conn->lastInsertId();

                // 4. สร้างบันทึกประวัติวิชาการ
                $record_sql = "INSERT INTO student_academic_records (
                              student_id, 
                              academic_year_id, 
                              class_id, 
                              total_attendance_days, 
                              total_absence_days, 
                              created_at
                            ) VALUES (:student_id, :academic_year_id, :class_id, 0, 0, NOW())";

                $record_stmt = $conn->prepare($record_sql);
                $record_stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                $record_stmt->bindParam(':academic_year_id', $current_academic_year_id, PDO::PARAM_INT);
                $record_stmt->bindParam(':class_id', $class_id, PDO::PARAM_INT);
                $record_stmt->execute();

                // 5. ถ้ามีการอัปโหลดรูปภาพ ให้อัปเดตรูปโปรไฟล์
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'student_' . $student_id . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                        $profile_url = 'uploads/profiles/' . $new_filename;
                        $profile_update_sql = "UPDATE users SET profile_picture = :profile_url WHERE user_id = :user_id";
                        $profile_stmt = $conn->prepare($profile_update_sql);
                        $profile_stmt->bindParam(':profile_url', $profile_url, PDO::PARAM_STR);
                        $profile_stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
                        $profile_stmt->execute();
                    }
                }


                //ลบข้อมูลจากตาราง student_pending
                $delete_pending_sql = "DELETE FROM student_pending WHERE student_code = ?";
                $delete_stmt = $conn->prepare($delete_pending_sql);
                $delete_stmt->execute([$_SESSION['student_code']]);


                // Commit transaction
                $conn->commit();

                // ล้าง session ที่ไม่จำเป็น
                unset($_SESSION['student_code']);
                unset($_SESSION['student_title']);
                unset($_SESSION['student_first_name']);
                unset($_SESSION['student_last_name']);
                unset($_SESSION['student_level_system']);
                unset($_SESSION['student_class_level']);
                unset($_SESSION['student_department']);
                unset($_SESSION['student_group_number']);
                unset($_SESSION['search_teacher_name']);
                unset($_SESSION['search_teacher_results']);
                unset($_SESSION['selected_teacher_id']);
                unset($_SESSION['selected_class_id']);
                unset($_SESSION['search_attempted']);
                unset($_SESSION['form_error']);
                unset($_SESSION['input_data']);

                // ไปยังขั้นตอนเสร็จสิ้น
                header('Location: register.php?step=7');
                exit;
            } catch (PDOException $e) {
                // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
                $conn->rollBack();
                $error_message = "เกิดข้อผิดพลาดในการลงทะเบียน: " . $e->getMessage();
            }
        }
        break;
}