<?php
session_start();
require_once '../config/db_config.php';

// ตรวจสอบว่ามีการล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// ตรวจสอบว่าเป็นบทบาทครูหรือไม่
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Teacher-Prasat - ลงทะเบียนเสร็จสิ้น</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <!-- ส่วนหัว -->
    <div class="header">
        <div class="header-spacer"></div>
        <h1>ลงทะเบียนเสร็จสิ้น</h1>
        <div class="header-spacer"></div>
    </div>

    <div class="container">
        <!-- Step Indicator -->
        <div class="steps">
            <div class="step completed">
                <div class="step-number">1</div>
                <div class="step-title">เข้าสู่ระบบ</div>
            </div>
            <div class="step-line completed"></div>
            <div class="step completed">
                <div class="step-number">2</div>
                <div class="step-title">ยืนยันตัวตน</div>
            </div>
            <div class="step-line completed"></div>
            <div class="step completed">
                <div class="step-number">3</div>
                <div class="step-title">ข้อมูลส่วนตัว</div>
            </div>
            <div class="step-line completed"></div>
            <div class="step active">
                <div class="step-number">4</div>
                <div class="step-title">เสร็จสิ้น</div>
            </div>
        </div>

        <!-- ขั้นตอนลงทะเบียนเสร็จสิ้น -->
        <div class="card success-card">
            <!-- Success Icon -->
            <div class="success-icon">
                <span class="material-icons">check</span>
            </div>

            <!-- Success Message -->
            <div class="success-message">
                <h2>ลงทะเบียนเสร็จสิ้น</h2>
                <p>คุณได้ลงทะเบียนเข้าใช้งานระบบ Teacher-Prasat เรียบร้อยแล้ว</p>
                <p>ตอนนี้คุณสามารถเริ่มใช้งานระบบเช็คชื่อเข้าแถวออนไลน์ได้ทันที</p>
            </div>

            <!-- Features Section -->
            <div class="features-section">
                <div class="features-title">คุณสามารถทำอะไรได้บ้าง?</div>
                <div class="feature-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-icons">pin</span>
                        </div>
                        <div class="feature-title">สร้างรหัส PIN</div>
                        <div class="feature-desc">สร้างรหัส PIN 4 หลักให้นักเรียนเช็คชื่อ</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-icons">qr_code_scanner</span>
                        </div>
                        <div class="feature-title">สแกน QR</div>
                        <div class="feature-desc">สแกน QR Code ของนักเรียนเพื่อเช็คชื่อ</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-icons">assessment</span>
                        </div>
                        <div class="feature-title">ดูรายงาน</div>
                        <div class="feature-desc">ตรวจสอบสถิติการเข้าแถวของนักเรียน</div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <span class="material-icons">notifications</span>
                        </div>
                        <div class="feature-title">แจ้งเตือน</div>
                        <div class="feature-desc">ส่งการแจ้งเตือนถึงผู้ปกครอง</div>
                    </div>
                </div>
            </div>

            <!-- Start Button -->
            <button class="btn success" onclick="window.location.href='home.php'">
                เริ่มใช้งานระบบ
                <span class="material-icons">arrow_forward</span>
            </button>
        </div>

        <!-- Confetti Elements -->
        <div id="confetti-container"></div>
    </div>

    <script>
        // สร้างเอฟเฟกต์ confetti เพื่อความสวยงาม
        function createConfetti() {
            const confettiContainer = document.getElementById('confetti-container');
            const colors = ['', 'blue', 'green', 'pink'];
            const totalConfetti = 50;

            for (let i = 0; i < totalConfetti; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti ' + colors[Math.floor(Math.random() * colors.length)];

                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';

                // กำหนดรูปร่างของ confetti
                if (Math.random() > 0.5) {
                    confetti.style.borderRadius = '50%';
                } else if (Math.random() > 0.5) {
                    confetti.style.width = '6px';
                    confetti.style.height = '16px';
                }

                confettiContainer.appendChild(confetti);
            }
        }

        // เรียกใช้ฟังก์ชันสร้าง confetti เมื่อหน้าเว็บโหลดเสร็จ
        window.addEventListener('load', function() {
            createConfetti();
        });
    </script>
</body>
</html>