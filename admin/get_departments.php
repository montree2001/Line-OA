<?php
/**
 * get_departments.php - ดึงข้อมูลแผนกวิชาทั้งหมด
 * 
 * สำหรับใช้แสดงในฟอร์มเพิ่ม/แก้ไขข้อมูลครู
 */

// เริ่ม session
session_start();

// โหลดไฟล์การเชื่อมต่อฐานข้อมูล
require_once '../db_connect.php';
require_once '../models/Teacher.php';

// สร้าง instance ของ Teacher
$teacherModel = new Teacher();

// ดึงข้อมูลแผนกวิชา
$departments = $teacherModel->getDepartmentsWithIds();

// ส่งข้อมูลกลับในรูปแบบ JSON
header('Content-Type: application/json');
echo json_encode($departments);
exit;