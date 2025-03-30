<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title : 'STD-Prasat - ระบบเช็คชื่อเข้าแถวออนไลน์'; ?></title>
    
    <!-- Material Icons & Google Fonts -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="assets/css/register.css" rel="stylesheet">
    
    <!-- Extra CSS -->
    <?php if(isset($extra_css)): ?>
        <?php foreach($extra_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <button class="header-icon" onclick="goBack()">
            <span class="material-icons">arrow_back</span>
        </button>
        <h1><?php echo isset($page_title) ? $page_title : 'ลงทะเบียนนักเรียน'; ?></h1>
        <div class="header-spacer"></div>
    </div>
    
    <!-- Loading Indicator -->
    <div class="loading" id="loading-indicator">
        <div class="spinner"></div>
        <div class="loading-text">กำลังดำเนินการ...</div>
    </div>