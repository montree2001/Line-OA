<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($page_title) ? $page_title : 'SADD-Prasat - ระบบผู้ปกครอง'; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/parent-style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <?php if(isset($extra_css)): ?>
        <?php foreach($extra_css as $css): ?>
            <link href="<?php echo $css; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <div class="header">
        <div class="app-name">SADD-Prasat</div>
        <div class="header-icons">
            <span class="material-icons">notifications</span>
            <span class="material-icons">account_circle</span>
        </div>
    </div>