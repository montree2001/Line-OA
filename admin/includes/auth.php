<?php
/**
 * auth.php - ฟังก์ชันสำหรับการยืนยันตัวตนและสิทธิ์การเข้าถึง
 * 
 * ส่วนหนึ่งของระบบ STUDENT-Prasat
 */

// รวมไฟล์ฐานข้อมูล
require_once __DIR__ . '/../config/database.php';

/**
 * ตรวจสอบการล็อกอิน
 * 
 * @return bool สถานะการล็อกอิน (true = ล็อกอินแล้ว, false = ยังไม่ได้ล็อกอิน)
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ตรวจสอบว่าผู้ใช้เป็นเจ้าหน้าที่หรือไม่
 * 
 * @return bool ผลการตรวจสอบ (true = เป็นเจ้าหน้าที่, false = ไม่ใช่เจ้าหน้าที่)
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * ตรวจสอบว่าผู้ใช้เป็นครูหรือไม่
 * 
 * @return bool ผลการตรวจสอบ (true = เป็นครู, false = ไม่ใช่ครู)
 */
function isTeacher() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
}

/**
 * ตรวจสอบว่าผู้ใช้เป็นนักเรียนหรือไม่
 * 
 * @return bool ผลการตรวจสอบ (true = เป็นนักเรียน, false = ไม่ใช่นักเรียน)
 */
function isStudent() {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student';
}

/** 
 * ตรวจสอบว่าผู้ใช้เป็นผู้ปกครองหรือไม่
 * 
 * @return bool ผลการตรวจสอบ (true = เป็นผู้ปกครอง, false = ไม่ใช่ผู้ปกครอง)
 */