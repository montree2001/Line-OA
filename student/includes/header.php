<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title : 'STD-Prasat - ลงทะเบียนนักเรียน'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/register.css" rel="stylesheet">
</head>
<body>
    <!-- ส่วนหัว -->
    <div class="header">
        <?php if ($step < 7): ?>
            <button class="header-icon" onclick="history.back()">
                <span class="material-icons">arrow_back</span>
            </button>
        <?php else: ?>
            <div class="header-spacer"></div>
        <?php endif; ?>

        <h1>
            <?php
            if ($step === 1) echo "เข้าสู่ระบบ";
            elseif ($step === 2) echo "ค้นหารหัสนักศึกษา";
            elseif ($step === 3 || $step === '3manual') echo "ข้อมูลนักศึกษา";
            elseif ($step === 4 || $step === 5 || $step === '5manual') echo "ข้อมูลชั้นเรียน";
            elseif ($step === 6) echo "ข้อมูลเพิ่มเติม";
            else echo "ลงทะเบียนเสร็จสิ้น";
            ?>
        </h1>

        <div class="header-spacer"></div>
    </div>

    <!-- Loading Indicator -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <div class="loading-text">กำลังดำเนินการ...</div>
    </div>