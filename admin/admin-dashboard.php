<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>น้องสัตบรรณ - แดชบอร์ดผู้บริหาร</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        :root {
            --primary-color: #28a745;
            --secondary-color: #6c757d;
            --background-color: #f5f8fa;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --success-color: #4caf50;
            --info-color: #1976d2;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: var(--background-color);
            color: #333;
            font-size: 16px;
            line-height: 1.5;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar styling */
        .sidebar {
            width: 280px;
            background-color: #263238;
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all var(--transition-speed);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header img {
            width: 40px;
            height: 40px;
            margin-right: 15px;
        }
        
        .sidebar-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-category {
            color: rgba(255, 255, 255, 0.5);
            text-transform: uppercase;
            font-size: 12px;
            padding: 0 20px;
            margin: 15px 0 5px;
            letter-spacing: 0.5px;
        }
        
        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all var(--transition-speed);
            border-left: 3px solid transparent;
        }
        
        .menu-item.active {
            background-color: rgba(255, 255, 255, 0.05);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .menu-item .material-icons {
            margin-right: 15px;
            opacity: 0.8;
        }
        
        .sidebar-footer {
            padding: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 12px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 280px;
            transition: all var(--transition-speed);
        }
        
        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            border-radius: 10px;
            padding: 15px 20px;
            box-shadow: var(--card-shadow);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 700;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-group {
            display: flex;
            gap: 10px;
        }
        
        .date-filter {
            display: flex;
            align-items: center;
            background-color: var(--background-color);
            border-radius: 5px;
            padding: 8px 12px;
        }
        
        .date-filter .material-icons {
            margin-right: 8px;
            color: #666;
        }
        
        .date-filter select {
            border: none;
            background: none;
            font-size: 14px;
            padding: 5px;
            color: #333;
            outline: none;
        }
        
        .department-filter {
            display: flex;
            align-items: center;
            background-color: var(--background-color);
            border-radius: 5px;
            padding: 8px 12px;
        }
        
        .department-filter .material-icons {
            margin-right: 8px;
            color: #666;
        }
        
        .department-filter select {
            border: none;
            background: none;
            font-size: 14px;
            padding: 5px;
            color: #333;
            outline: none;
        }
        
        .header-button {
            padding: 8px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .header-button:hover {
            background-color: #218838;
        }
        
        .header-button .material-icons {
            margin-right: 5px;
            font-size: 18px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            transition: transform var(--transition-speed);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.blue::before {
            background-color: var(--info-color);
        }
        
        .stat-card.green::before {
            background-color: var(--success-color);
        }
        
        .stat-card.red::before {
            background-color: var(--danger-color);
        }
        
        .stat-card.yellow::before {
            background-color: var(--warning-color);
        }
        
        .stat-title {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }
        
        .stat-title .material-icons {
            margin-right: 5px;
            font-size: 20px;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.blue .stat-value {
            color: var(--info-color);
        }
        
        .stat-card.green .stat-value {
            color: var(--success-color);
        }
        
        .stat-card.red .stat-value {
            color: var(--danger-color);
        }
        
        .stat-card.yellow .stat-value {
            color: var(--warning-color);
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .stat-change.positive {
            color: var(--success-color);
        }
        
        .stat-change.negative {
            color: var(--danger-color);
        }
        
        .stat-change .material-icons {
            font-size: 16px;
            margin-right: 3px;
        }
        
        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            min-height: 400px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .chart-title .material-icons {
            margin-right: 8px;
            font-size: 20px;
            color: var(--info-color);
        }
        
        .chart-actions {
            display: flex;
            gap: 10px;
        }
        
        .chart-tab {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            background-color: var(--background-color);
            color: #666;
            transition: all 0.2s;
        }
        
        .chart-tab.active {
            background-color: var(--info-color);
            color: white;
        }
        
        .chart-tab:hover:not(.active) {
            background-color: #e0e0e0;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        /* Table styling */
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-title .material-icons {
            margin-right: 8px;
            color: var(--warning-color);
        }
        
        .card-actions {
            display: flex;
            gap: 10px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--background-color);
            border-radius: 5px;
            padding: 8px 12px;
            width: 300px;
        }
        
        .search-box .material-icons {
            margin-right: 8px;
            color: #666;
        }
        
        .search-box input {
            border: none;
            background: none;
            flex: 1;
            font-size: 14px;
            color: #333;
            outline: none;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead th {
            background-color: var(--background-color);
            text-align: left;
            padding: 12px 15px;
            font-size: 14px;
            color: #666;
            border-bottom: 1px solid #eee;
        }
        
        tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.01);
        }
        
        .student-name {
            display: flex;
            align-items: center;
        }
        
        .student-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #e3f2fd;
            color: var(--info-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .student-detail a {
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }
        
        .student-detail a:hover {
            color: var(--info-color);
        }
        
        .student-detail p {
            font-size: 13px;
            color: #666;
            margin-top: 3px;
        }
        
        .attendance-rate {
            font-weight: 600;
        }
        
        .attendance-rate.good {
            color: var(--success-color);
        }
        
        .attendance-rate.warning {
            color: var(--warning-color);
        }
        
        .attendance-rate.danger {
            color: var(--danger-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.warning {
            background-color: #fff8e1;
            color: var(--warning-color);
        }
        
        .status-badge.danger {
            background-color: #ffebee;
            color: var(--danger-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-button {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            background-color: var(--background-color);
            transition: background-color 0.2s;
        }
        
        .action-button:hover {
            background-color: #e0e0e0;
        }
        
        .action-button.view {
            color: var(--info-color);
        }
        
        .action-button.message {
            color: var(--success-color);
        }
        
        /* Class Rankings Table */
        .class-rank-table td, .class-rank-table th {
            text-align: center;
        }
        
        .class-rank-table td:first-child, .class-rank-table th:first-child {
            text-align: left;
        }
        
        .progress-bar {
            height: 8px;
            background-color: #f1f1f1;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s;
        }
        
        .progress-fill.green {
            background-color: var(--success-color);
        }
        
        .progress-fill.yellow {
            background-color: var(--warning-color);
        }
        
        .progress-fill.red {
            background-color: var(--danger-color);
        }
        
        /* Department stats card */
        .department-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .department-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: var(--card-shadow);
        }
        
        .department-name {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .department-stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .department-stat {
            text-align: center;
            flex: 1;
        }
        
        .department-stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .department-stat-value {
            font-weight: 600;
            font-size: 18px;
        }
        
        .department-progress {
            margin-top: 10px;
        }
        
        /* Calendar view */
        .calendar-view {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .calendar-day {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 8px;
            text-align: center;
        }
        
        .calendar-date {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .calendar-stats {
            font-size: 12px;
        }
        
        .calendar-day.today {
            background-color: #e3f2fd;
            border: 1px solid #bbdefb;
        }
        
        .calendar-day.weekend {
            background-color: #f5f5f5;
            color: #999;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 25px;
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            position: relative;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .close:hover {
            color: #333;
        }
        
        .notification-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .form-group label {
            font-weight: 500;
        }
        
        .form-group select, .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 150px;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-cancel, .btn-send {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #f5f5f5;
            color: #333;
        }
        
        .btn-send {
            background-color: var(--success-color);
            color: white;
        }
        
        /* Pie chart legend */
        .pie-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .legend-color {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            border-radius: 4px;
        }
        
        .legend-color.green {
            background-color: var(--success-color);
        }
        
        .legend-color.yellow {
            background-color: var(--warning-color);
        }
        
        .legend-color.red {
            background-color: var(--danger-color);
        }
        
        /* Loading indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1100;
            display: none;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive design */
        @media (max-width: 1200px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            
            .department-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 992px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .search-box {
                width: 200px;
            }
            
            .department-stats {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .date-filter, .department-filter {
                width: 100%;
            }
            
            .header-button {
                width: 100%;
                justify-content: center;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .card-actions {
                width: 100%;
            }
            
            .search-box {
                width: 100%;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 10px;
                right: 10px;
                z-index: 1001;
                background-color: var(--primary-color);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            }
            
            .calendar-view {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .calendar-view {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="/api/placeholder/40/40" alt="Logo">
            <h2>น้องสัตบรรณ</h2>
        </div>
        
        <div class="sidebar-menu">
            <div class="menu-category">หน้าหลัก</div>
            <a href="#" class="menu-item active">
                <span class="material-icons">dashboard</span> แดชบอร์ด
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">trending_down</span> นักเรียนตกกิจกรรม
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">assessment</span> รายงาน
            </a>
            
            <div class="menu-category">การจัดการ</div>
            <a href="#" class="menu-item">
                <span class="material-icons">groups</span> นักเรียน
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">person</span> ครู
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">family_restroom</span> ผู้ปกครอง
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">menu_book</span> ชั้นเรียน
            </a>
            
            <div class="menu-category">ระบบ</div>
            <a href="#" class="menu-item">
                <span class="material-icons">notifications</span> การแจ้งเตือน
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">settings</span> ตั้งค่า
            </a>
            <a href="#" class="menu-item">
                <span class="material-icons">help</span> ช่วยเหลือ
            </a>
        </div>
        
        <div class="sidebar-footer">
            <p>ระบบน้องสัตบรรณ v1.0.0</p>
            <p>© 2025 วิทยาลัยการอาชีพปราสาท</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Mobile menu toggle button (visible on small screens) -->
        <div class="mobile-menu-toggle" id="mobileMenuToggle">
            <span class="material-icons">menu</span>
        </div>
        
        <!-- Main Header -->
        <div class="main-header">
            <h1 class="page-title">แดชบอร์ดภาพรวม</h1>
            <div class="header-actions">
                <div class="filter-group">
                    <div class="date-filter">
                        <span class="material-icons">date_range</span>
                        <select id="period-selector">
                            <option value="day">วันนี้</option>
                            <option value="week">สัปดาห์นี้</option>
                            <option value="month" selected>เดือนนี้</option>
                            <option value="semester">ภาคเรียนที่ 1/2568</option>
                            <option value="custom">กำหนดเอง</option>
                        </select>
                    </div>
                    <div class="department-filter">
                        <span class="material-icons">category</span>
                        <select id="department-selector">
                            <option value="all">ทุกแผนก</option>
                            <option value="AUTO">ช่างยนต์</option>
                            <option value="ELEC">ช่างไฟฟ้ากำลัง</option>
                            <option value="ELECT">ช่างอิเล็กทรอนิกส์</option>
                            <option value="IT">เทคโนโลยีสารสนเทศ</option>
                            <option value="WELD">ช่างเชื่อมโลหะ</option>
                        </select>
                    </div>
                </div>
                <button class="header-button" id="downloadReportBtn">
                    <span class="material-icons">file_download</span> ดาวน์โหลดรายงาน
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-title">
                    <span class="material-icons">people</span>
                    จำนวนนักเรียนทั้งหมด
                </div>
                <div class="stat-value">1,250</div>
                <div class="stat-change positive">
                    <span class="material-icons">arrow_upward</span> เพิ่มขึ้น 2.5%
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-title">
                    <span class="material-icons">check_circle</span>
                    เข้าแถวเฉลี่ย
                </div>
                <div class="stat-value">94.8%</div>
                <div class="stat-change positive">
                    <span class="material-icons">arrow_upward</span> เพิ่มขึ้น 0.6%
                </div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-title">
                    <span class="material-icons">cancel</span>
                    นักเรียนตกกิจกรรม
                </div>
                <div class="stat-value">35</div>
                <div class="stat-change negative">
                    <span class="material-icons">arrow_downward</span> ลดลง 12%
                </div>
            </div>
            
            <div class="stat-card yellow">
                <div class="stat-title">
                    <span class="material-icons">warning</span>
                    นักเรียนเสี่ยงตกกิจกรรม
                </div>
                <div class="stat-value">62</div>
                <div class="stat-change negative">
                    <span class="material-icons">arrow_downward</span> ลดลง 8%
                </div>
            </div>
        </div>
        
        <!-- Department stats -->
        <div class="department-stats" id="departmentStats">
            <div class="department-card">
                <div class="department-name">
                    <span>ช่างยนต์</span>
                    <span class="attendance-rate good">95.2%</span>
                </div>
                <div class="department-stats-row">
                    <div class="department-stat">
                        <div class="department-stat-label">นักเรียน</div>
                        <div class="department-stat-value">350</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เข้าแถว</div>
                        <div class="department-stat-value">333</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เสี่ยง</div>
                        <div class="department-stat-value">12</div>
                    </div>
                </div>
                <div class="department-progress">
                    <div class="progress-bar">
                        <div class="progress-fill green" style="width: 95.2%;"></div>
                    </div>
                </div>
            </div>
            
            <div class="department-card">
                <div class="department-name">
                    <span>ช่างไฟฟ้ากำลัง</span>
                    <span class="attendance-rate good">93.8%</span>
                </div>
                <div class="department-stats-row">
                    <div class="department-stat">
                        <div class="department-stat-label">นักเรียน</div>
                        <div class="department-stat-value">290</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เข้าแถว</div>
                        <div class="department-stat-value">272</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เสี่ยง</div>
                        <div class="department-stat-value">15</div>
                    </div>
                </div>
                <div class="department-progress">
                    <div class="progress-bar">
                        <div class="progress-fill green" style="width: 93.8%;"></div>
                    </div>
                </div>
            </div>
            
            <div class="department-card">
                <div class="department-name">
                    <span>เทคโนโลยีสารสนเทศ</span>
                    <span class="attendance-rate good">96.4%</span>
                </div>
                <div class="department-stats-row">
                    <div class="department-stat">
                        <div class="department-stat-label">นักเรียน</div>
                        <div class="department-stat-value">165</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เข้าแถว</div>
                        <div class="department-stat-value">159</div>
                    </div>
                    <div class="department-stat">
                        <div class="department-stat-label">เสี่ยง</div>
                        <div class="department-stat-value">5</div>
                    </div>
                </div>
                <div class="department-progress">
                    <div class="progress-bar">
                        <div class="progress-fill green" style="width: 96.4%;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="material-icons">trending_up</span>
                        อัตราการเข้าแถวตามเวลา
                    </div>
                    <div class="chart-actions">
                        <div class="chart-tab active" data-period="week">ย้อนหลัง 7 วัน</div>
                        <div class="chart-tab" data-period="month">รายเดือน</div>
                        <div class="chart-tab" data-period="semester">รายภาคเรียน</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="attendanceLineChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <span class="material-icons">pie_chart</span>
                        สถานะการเข้าแถว
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="attendancePieChart"></canvas>
                    <div class="pie-legend">
                        <div class="legend-item">
                            <div class="legend-color green"></div>
                            <span>มาปกติ (75%)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color yellow"></div>
                            <span>มาสาย (15%)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color red"></div>
                            <span>ขาด (10%)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Daily Attendance Calendar (for daily view) -->
        <div class="card" id="dailyAttendanceCard" style="display:none;">
            <div class="card-header">
                <div class="card-title">
                    <span class="material-icons">calendar_today</span>
                    การเข้าแถวรายวัน - พฤษภาคม 2568
                </div>
            </div>
            
            <div class="calendar-view" id="calendarView">
                <!-- Calendar will be populated by JavaScript -->
                <div class="calendar-day">
                    <div class="calendar-date">1</div>
                    <div class="calendar-stats">94.2%</div>
                </div>
                <div class="calendar-day">
                    <div class="calendar-date">2</div>
                    <div class="calendar-stats">95.1%</div>
                </div>
                <div class="calendar-day weekend">
                    <div class="calendar-date">3</div>
                    <div class="calendar-stats">-</div>
                </div>
                <!-- More days will be added dynamically -->
            </div>
        </div>

        <!-- Students at Risk Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="material-icons">warning</span>
                    นักเรียนที่ตกกิจกรรมหรือมีความเสี่ยง
                </div>
                <div class="card-actions">
                    <div class="search-box">
                        <span class="material-icons">search</span>
                        <input type="text" id="student-search" placeholder="ค้นหาชื่อหรือรหัสนักเรียน...">
                    </div>
                    <button class="header-button" id="notifyAllBtn">
                        <span class="material-icons">notifications_active</span> แจ้งเตือนทั้งหมด
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="risk-students-table">
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>ชั้นเรียน</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>อัตราการเข้าแถว</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-student-id="1">
                            <td>
                                <div class="student-name">
                                    <div class="student-avatar">พ</div>
                                    <div class="student-detail">
                                        <a href="#" class="student-link" data-student-id="1">นางสาวพิมพ์ใจ ร่าเริง</a>
                                        <p>รหัส: 67319010001</p>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/1</td>
                            <td>อาจารย์ใจดี มากเมตตา</td>
                            <td><span class="attendance-rate danger">65.8%</span></td>
                            <td><span class="status-badge danger">ตกกิจกรรม</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view" data-student-id="1">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button message" data-student-id="1">
                                        <span class="material-icons">message</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-student-id="2">
                            <td>
                                <div class="student-name">
                                    <div class="student-avatar">ม</div>
                                    <div class="student-detail">
                                        <a href="#" class="student-link" data-student-id="2">นายมานะ ตั้งใจ</a>
                                        <p>รหัส: 67319010002</p>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/2</td>
                            <td>อาจารย์ราตรี นอนดึก</td>
                            <td><span class="attendance-rate danger">68.2%</span></td>
                            <td><span class="status-badge danger">ตกกิจกรรม</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view" data-student-id="2">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button message" data-student-id="2">
                                        <span class="material-icons">message</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-student-id="3">
                            <td>
                                <div class="student-name">
                                    <div class="student-avatar">ส</div>
                                    <div class="student-detail">
                                        <a href="#" class="student-link" data-student-id="3">นายสมชาย อ่อนนุช</a>
                                        <p>รหัส: 67319010003</p>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/3</td>
                            <td>อาจารย์จริงจัง ทำงาน</td>
                            <td><span class="attendance-rate warning">75.5%</span></td>
                            <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view" data-student-id="3">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button message" data-student-id="3">
                                        <span class="material-icons">message</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-student-id="4">
                            <td>
                                <div class="student-name">
                                    <div class="student-avatar">ก</div>
                                    <div class="student-detail">
                                        <a href="#" class="student-link" data-student-id="4">นางสาวกนกวรรณ รักษาการ</a>
                                        <p>รหัส: 67319010005</p>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/1</td>
                            <td>อาจารย์มานะ พยายาม</td>
                            <td><span class="attendance-rate warning">78.2%</span></td>
                            <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view" data-student-id="4">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button message" data-student-id="4">
                                        <span class="material-icons">message</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-student-id="5">
                            <td>
                                <div class="student-name">
                                    <div class="student-avatar">ว</div>
                                    <div class="student-detail">
                                        <a href="#" class="student-link" data-student-id="5">นายวิชัย ฉลาดล้ำ</a>
                                        <p>รหัส: 67319010006</p>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/2</td>
                            <td>อาจารย์เมตตา ใจดี</td>
                            <td><span class="attendance-rate warning">76.9%</span></td>
                            <td><span class="status-badge warning">เสี่ยงตกกิจกรรม</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view" data-student-id="5">
                                        <span class="material-icons">visibility</span>
                                    </button>
                                    <button class="action-button message" data-student-id="5">
                                        <span class="material-icons">message</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="risk_students.php" class="header-button">
                    <span class="material-icons">visibility</span> ดูทั้งหมด
                </a>
            </div>
        </div>

        <!-- Class Rankings -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="material-icons">leaderboard</span>
                    อันดับอัตราการเข้าแถวตามชั้นเรียน
                </div>
                <div class="card-actions">
                    <div class="chart-tab active" data-level="all">ทั้งหมด</div>
                    <div class="chart-tab" data-level="high">ระดับ ปวส.</div>
                    <div class="chart-tab" data-level="middle">ระดับ ปวช.</div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="class-rank-table">
                    <thead>
                        <tr>
                            <th>ชั้นเรียน</th>
                            <th>ครูที่ปรึกษา</th>
                            <th>นักเรียน</th>
                            <th>เข้าแถว</th>
                            <th>อัตรา</th>
                            <th>กราฟ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr data-class-id="1" data-level="middle">
                            <td>ปวช.1/1</td>
                            <td>อาจารย์มานะ พยายาม</td>
                            <td>35</td>
                            <td>34</td>
                            <td><span class="attendance-rate good">97.1%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill green" style="width: 97.1%;"></div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-class-id="2" data-level="middle">
                            <td>ปวช.2/1</td>
                            <td>อาจารย์สดใส อารี</td>
                            <td>32</td>
                            <td>31</td>
                            <td><span class="attendance-rate good">96.9%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill green" style="width: 96.9%;"></div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-class-id="3" data-level="middle">
                            <td>ปวช.3/3</td>
                            <td>อาจารย์สมใจ นึกแปลก</td>
                            <td>30</td>
                            <td>29</td>
                            <td><span class="attendance-rate good">96.7%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill green" style="width: 96.7%;"></div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-class-id="4" data-level="high">
                            <td>ปวส.1/1</td>
                            <td>อาจารย์ใจดี มากเมตตา</td>
                            <td>35</td>
                            <td>33</td>
                            <td><span class="attendance-rate good">94.3%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill green" style="width: 94.3%;"></div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr data-class-id="5" data-level="high">
                            <td>ปวส.2/2</td>
                            <td>อาจารย์ราตรี นอนดึก</td>
                            <td>32</td>
                            <td>28</td>
                            <td><span class="attendance-rate warning">87.5%</span></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill yellow" style="width: 87.5%;"></div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับแสดงรายละเอียดนักเรียน -->
    <div class="modal" id="studentDetailModal">
        <div class="modal-content">
            <span class="close" id="closeStudentModal">&times;</span>
            <h2 id="modal-student-name">ข้อมูลการเข้าแถว</h2>
            <div id="student-detail-content">
                <!-- ข้อมูลนักเรียนจะถูกแสดงที่นี่ -->
                <div class="loading">กำลังโหลดข้อมูล...</div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับส่งข้อความแจ้งเตือน -->
    <div class="modal" id="notificationModal">
        <div class="modal-content">
            <span class="close" id="closeNotificationModal">&times;</span>
            <h2>ส่งข้อความแจ้งเตือนผู้ปกครอง</h2>
            <div class="notification-form">
                <div class="form-group">
                    <label for="notification-template">เลือกเทมเพลตข้อความ</label>
                    <select id="notification-template">
                        <option value="risk_alert">แจ้งเตือนความเสี่ยงตกกิจกรรม</option>
                        <option value="absence_alert">แจ้งเตือนการขาดเรียน</option>
                        <option value="monthly_report">รายงานประจำเดือน</option>
                        <option value="custom">ข้อความกำหนดเอง</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notification-content">ข้อความ</label>
                    <textarea id="notification-content" rows="6"></textarea>
                </div>
                <div class="form-actions">
                    <button class="btn-cancel" id="cancelNotification">ยกเลิก</button>
                    <button class="btn-send" id="sendNotification">ส่งข้อความ</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal สำหรับเลือกช่วงวันที่ -->
    <div class="modal" id="dateRangeModal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="close" id="closeDateRangeModal">&times;</span>
            <h2>เลือกช่วงวันที่</h2>
            <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
                <div class="form-group">
                    <label for="start-date">วันที่เริ่มต้น</label>
                    <input type="date" id="start-date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="end-date">วันที่สิ้นสุด</label>
                    <input type="date" id="end-date" class="form-control">
                </div>
                <div class="form-actions">
                    <button class="btn-cancel" id="cancelDateRange">ยกเลิก</button>
                    <button class="btn-send" id="applyDateRange">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ข้อมูลสำหรับกราฟ (จะถูกแทนที่ด้วยข้อมูลจริงจาก AJAX)
        const weeklyAttendanceData = [
            { date: '4 พ.ค.', attendance_rate: 93.5 },
            { date: '5 พ.ค.', attendance_rate: 94.2 },
            { date: '6 พ.ค.', attendance_rate: 92.8 },
            { date: '7 พ.ค.', attendance_rate: 95.1 },
            { date: '8 พ.ค.', attendance_rate: 94.5 },
            { date: '9 พ.ค.', attendance_rate: 93.9 },
            { date: '10 พ.ค.', attendance_rate: 94.8 }
        ];
        
        const pieChartData = {
            normal: 75,
            late: 15,
            absent: 10
        };

        // ตัวแปรสำหรับเก็บ reference ของ Chart
        let attendanceLineChart;
        let attendancePieChart;
        let currentStudentId;
        let currentPeriod = 'week'; // Default period

        // เมื่อโหลดหน้าเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            // สร้างกราฟและแผนภูมิ
            initializeCharts();
            
            // ตั้งค่า Event Listeners
            setupEventListeners();
            
            // สร้างปฏิทินรายวัน (สำหรับมุมมองรายวัน)
            createCalendarView();
        });

        // ฟังก์ชันสร้างกราฟเส้นแสดงอัตราการเข้าแถว
        function initializeLineChart() {
            const ctx = document.getElementById('attendanceLineChart').getContext('2d');
            
            // เตรียมข้อมูลสำหรับกราฟ
            const labels = weeklyAttendanceData.map(item => item.date);
            const data = weeklyAttendanceData.map(item => item.attendance_rate);
            
            attendanceLineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'อัตราการเข้าแถว (%)',
                        data: data,
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderColor: 'rgb(40, 167, 69)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: 'rgb(40, 167, 69)',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: Math.max(0, Math.min(...data) - 10),
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `อัตราการเข้าแถว: ${context.parsed.y}%`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // ฟังก์ชันสร้างกราฟวงกลมแสดงสถานะการเข้าแถว
        function initializePieChart() {
            const ctx = document.getElementById('attendancePieChart').getContext('2d');
            
            attendancePieChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['มาปกติ', 'มาสาย', 'ขาด'],
                    datasets: [{
                        data: [pieChartData.normal, pieChartData.late, pieChartData.absent],
                        backgroundColor: ['#4caf50', '#ff9800', '#f44336'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.parsed}%`;
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        // ฟังก์ชันสร้างกราฟและแผนภูมิทั้งหมด
        function initializeCharts() {
            initializeLineChart();
            initializePieChart();
        }

        // ฟังก์ชันตั้งค่า Event Listeners
        function setupEventListeners() {
            // ปุ่มแท็บกราฟเส้น
            document.querySelectorAll('.chart-actions .chart-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // เอาคลาส active ออกจากทุกแท็บ
                    document.querySelectorAll('.chart-actions .chart-tab').forEach(t => t.classList.remove('active'));
                    // เพิ่มคลาส active ให้กับแท็บที่คลิก
                    this.classList.add('active');
                    
                    // เปลี่ยนข้อมูลกราฟตามช่วงเวลาที่เลือก
                    const period = this.getAttribute('data-period');
                    updateAttendanceChart(period);
                });
            });
            
            // ปุ่มแท็บตารางอันดับชั้นเรียน
            document.querySelectorAll('.card-actions .chart-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    // เอาคลาส active ออกจากทุกแท็บ
                    document.querySelectorAll('.card-actions .chart-tab').forEach(t => t.classList.remove('active'));
                    // เพิ่มคลาส active ให้กับแท็บที่คลิก
                    this.classList.add('active');
                    
                    // กรองข้อมูลตารางตามระดับชั้นที่เลือก
                    const level = this.getAttribute('data-level');
                    filterClassTable(level);
                });
            });
            
            // ช่องค้นหานักเรียน
            const studentSearch = document.getElementById('student-search');
            if (studentSearch) {
                studentSearch.addEventListener('input', function() {
                    filterStudentTable(this.value);
                });
            }
            
            // ตัวเลือกช่วงเวลา
            const periodSelector = document.getElementById('period-selector');
            if (periodSelector) {
                periodSelector.addEventListener('change', changePeriod);
            }
            
            // ตัวเลือกแผนก
            const departmentSelector = document.getElementById('department-selector');
            if (departmentSelector) {
                departmentSelector.addEventListener('change', changeDepartment);
            }
            
            // ปุ่มดาวน์โหลดรายงาน
            const downloadButton = document.getElementById('downloadReportBtn');
            if (downloadButton) {
                downloadButton.addEventListener('click', downloadReport);
            }
            
            // ปุ่มแจ้งเตือนทั้งหมด
            const notifyAllButton = document.getElementById('notifyAllBtn');
            if (notifyAllButton) {
                notifyAllButton.addEventListener('click', confirmNotifyAllRiskStudents);
            }
            
            // ปุ่มดูรายละเอียดนักเรียน
            document.querySelectorAll('.action-button.view, .student-link').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const studentId = this.getAttribute('data-student-id');
                    viewStudentDetail(studentId);
                });
            });
            
            // ปุ่มส่งข้อความแจ้งเตือน
            document.querySelectorAll('.action-button.message').forEach(button => {
                button.addEventListener('click', function() {
                    const studentId = this.getAttribute('data-student-id');
                    notifyParent(studentId);
                });
            });
            
            // ปุ่มปิด Modal รายละเอียดนักเรียน
            document.getElementById('closeStudentModal').addEventListener('click', function() {
                document.getElementById('studentDetailModal').style.display = 'none';
            });
            
            // ปุ่มปิด Modal การแจ้งเตือน
            document.getElementById('closeNotificationModal').addEventListener('click', function() {
                document.getElementById('notificationModal').style.display = 'none';
            });
            
            // ปุ่มยกเลิกการแจ้งเตือน
            document.getElementById('cancelNotification').addEventListener('click', function() {
                document.getElementById('notificationModal').style.display = 'none';
            });
            
            // ปุ่มส่งข้อความแจ้งเตือน
            document.getElementById('sendNotification').addEventListener('click', sendNotification);
            
            // เลือกเทมเพลตข้อความ
            document.getElementById('notification-template').addEventListener('change', updateNotificationContent);
            
            // ปุ่มปิด Modal เลือกช่วงวันที่
            document.getElementById('closeDateRangeModal').addEventListener('click', function() {
                document.getElementById('dateRangeModal').style.display = 'none';
            });
            
            // ปุ่มยกเลิกการเลือกช่วงวันที่
            document.getElementById('cancelDateRange').addEventListener('click', function() {
                document.getElementById('dateRangeModal').style.display = 'none';
            });
            
            // ปุ่มตกลงเลือกช่วงวันที่
            document.getElementById('applyDateRange').addEventListener('click', applyDateRange);
            
            // Mobile menu toggle
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', toggleMobileMenu);
            }
            
            // ปิด Modal เมื่อคลิกพื้นหลัง
            window.onclick = function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            };
        }

        // ฟังก์ชันอัปเดตกราฟเส้นตามช่วงเวลาที่เลือก
        function updateAttendanceChart(period) {
            if (period === currentPeriod) return;
            currentPeriod = period;
            
            showLoading();
            
            // จำลองการดึงข้อมูลจาก API
            setTimeout(() => {
                let labels = [];
                let data = [];
                
                switch (period) {
                    case 'week':
                        labels = weeklyAttendanceData.map(item => item.date);
                        data = weeklyAttendanceData.map(item => item.attendance_rate);
                        break;
                    case 'month':
                        // สร้างข้อมูลจำลองสำหรับเดือน
                        labels = Array.from({length: 30}, (_, i) => `${i+1} พ.ค.`);
                        data = Array.from({length: 30}, () => Math.floor(85 + Math.random() * 10));
                        break;
                    case 'semester':
                        // สร้างข้อมูลจำลองสำหรับภาคเรียน
                        labels = ['พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.'];
                        data = [94.5, 93.8, 92.5, 93.2, 94.1, 94.8];
                        break;
                }
                
                // อัปเดตข้อมูลกราฟ
                if (attendanceLineChart) {
                    attendanceLineChart.data.labels = labels;
                    attendanceLineChart.data.datasets[0].data = data;
                    attendanceLineChart.update();
                }
                
                hideLoading();
            }, 800);
        }

        // ฟังก์ชันกรองตารางชั้นเรียนตามระดับ
        function filterClassTable(level) {
            const rows = document.querySelectorAll('.class-rank-table tbody tr');
            
            rows.forEach(row => {
                if (level === 'all' || row.getAttribute('data-level') === level) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันกรองตารางนักเรียนจากการค้นหา
        function filterStudentTable(searchText) {
            const rows = document.querySelectorAll('#risk-students-table tbody tr');
            const searchLower = searchText.toLowerCase();
            
            rows.forEach(row => {
                const studentName = row.querySelector('.student-detail a')?.textContent.toLowerCase() || '';
                const studentCode = row.querySelector('.student-detail p')?.textContent.toLowerCase() || '';
                
                if (studentName.includes(searchLower) || studentCode.includes(searchLower) || searchText === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันเปลี่ยนช่วงเวลาการแสดงผล
        function changePeriod() {
            const periodSelector = document.getElementById('period-selector');
            const period = periodSelector.value;
            
            // แสดงหรือซ่อนปฏิทินรายวัน
            const dailyAttendanceCard = document.getElementById('dailyAttendanceCard');
            if (dailyAttendanceCard) {
                dailyAttendanceCard.style.display = period === 'day' ? 'block' : 'none';
            }
            
            // ถ้าเป็นกำหนดเอง ให้แสดง modal เลือกวันที่
            if (period === 'custom') {
                showDateRangeSelector();
                return;
            }
            
            showLoading();
            
            // จำลองการดึงข้อมูลตามช่วงเวลา
            setTimeout(() => {
                hideLoading();
                
                // โค้ดอัปเดตข้อมูลตามช่วงเวลาที่เลือก
                // ในระบบจริง ควรส่ง AJAX ไปดึงข้อมูลตามช่วงเวลา
                
                // อัปเดตชื่อหัวข้อตามช่วงเวลา
                let periodText = '';
                switch (period) {
                    case 'day': periodText = 'วันนี้'; break;
                    case 'week': periodText = 'สัปดาห์นี้'; break;
                    case 'month': periodText = 'เดือนนี้'; break;
                    case 'semester': periodText = 'ภาคเรียนที่ 1/2568'; break;
                }
                
                // แสดงข้อความว่าเปลี่ยนช่วงเวลาแล้ว
                alert(`เปลี่ยนการแสดงผลเป็นช่วง: ${periodText} เรียบร้อยแล้ว`);
            }, 800);
        }

        // ฟังก์ชันเปลี่ยนแผนกที่แสดง
        function changeDepartment() {
            const departmentSelector = document.getElementById('department-selector');
            const department = departmentSelector.value;
            
            showLoading();
            
            // จำลองการดึงข้อมูลตามแผนก
            setTimeout(() => {
                hideLoading();
                
                // โค้ดอัปเดตข้อมูลตามแผนกที่เลือก
                // ในระบบจริง ควรส่ง AJAX ไปดึงข้อมูลตามแผนก
                
                const departmentText = departmentSelector.options[departmentSelector.selectedIndex].text;
                alert(`เปลี่ยนการแสดงผลเป็นแผนก: ${departmentText} เรียบร้อยแล้ว`);
            }, 800);
        }

        // แสดง modal เลือกช่วงวันที่
        function showDateRangeSelector() {
            // ตั้งค่าวันที่เริ่มต้นเป็นวันแรกของเดือนปัจจุบัน
            const startDate = document.getElementById('start-date');
            const endDate = document.getElementById('end-date');
            
            const today = new Date();
            const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            
            startDate.valueAsDate = firstDayOfMonth;
            endDate.valueAsDate = today;
            
            // แสดง Modal
            document.getElementById('dateRangeModal').style.display = 'block';
        }

        // ประมวลผลช่วงวันที่ที่เลือก
        function applyDateRange() {
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            if (!startDate || !endDate) {
                alert('กรุณาเลือกวันที่เริ่มต้นและวันที่สิ้นสุด');
                return;
            }
            
            if (new Date(startDate) > new Date(endDate)) {
                alert('วันที่เริ่มต้นต้องมาก่อนวันที่สิ้นสุด');
                return;
            }
            
            // ปิด Modal
            document.getElementById('dateRangeModal').style.display = 'none';
            
            showLoading();
            
            // จำลองการดึงข้อมูลตามช่วงวันที่
            setTimeout(() => {
                hideLoading();
                alert(`เปลี่ยนการแสดงผลเป็นช่วงวันที่ ${startDate} ถึง ${endDate} เรียบร้อยแล้ว`);
                
                // เปลี่ยนตัวเลือกใน dropdown เป็น 'custom'
                const periodSelector = document.getElementById('period-selector');
                periodSelector.value = 'custom';
            }, 800);
        }

        // ฟังก์ชันดาวน์โหลดรายงาน
        function downloadReport() {
            const periodSelector = document.getElementById('period-selector');
            const departmentSelector = document.getElementById('department-selector');
            
            const period = periodSelector.value;
            const department = departmentSelector.value;
            
            showLoading();
            
            // จำลองการสร้างรายงาน
            setTimeout(() => {
                hideLoading();
                
                let periodText = 'ทั้งหมด';
                switch (period) {
                    case 'day': periodText = 'วันนี้'; break;
                    case 'week': periodText = 'สัปดาห์นี้'; break;
                    case 'month': periodText = 'เดือนนี้'; break;
                    case 'semester': periodText = 'ภาคเรียนที่ 1/2568'; break;
                    case 'custom': periodText = 'ช่วงวันที่ที่กำหนด'; break;
                }
                
                const departmentText = departmentSelector.options[departmentSelector.selectedIndex].text;
                
                alert(`เริ่มดาวน์โหลดรายงานสำหรับช่วง: ${periodText} แผนก: ${departmentText}`);
                
                // ในระบบจริง ควรเปิดหน้าต่างดาวน์โหลดหรือส่ง response เป็นไฟล์
                // window.location.href = `download_report.php?period=${period}&department=${department}`;
            }, 800);
        }

        // ฟังก์ชันดูรายละเอียดนักเรียน
        function viewStudentDetail(studentId) {
            currentStudentId = studentId;
            
            // แสดง modal
            document.getElementById('studentDetailModal').style.display = 'block';
            
            // ตั้งค่า loading state
            document.getElementById('student-detail-content').innerHTML = '<div class="loading">กำลังโหลดข้อมูล...</div>';
            
            // จำลองการดึงข้อมูลนักเรียน
            setTimeout(() => {
                // สมมติว่าได้รับข้อมูลจาก server
                let studentData;
                
                // ค้นหาข้อมูลนักเรียนจาก DOM (ในระบบจริงควรดึงจาก AJAX)
                const studentRow = document.querySelector(`#risk-students-table tr[data-student-id="${studentId}"]`);
                if (studentRow) {
                    const nameElement = studentRow.querySelector('.student-detail a');
                    const codeElement = studentRow.querySelector('.student-detail p');
                    const classElement = studentRow.querySelector('td:nth-child(2)');
                    const rateElement = studentRow.querySelector('.attendance-rate');
                    
                    studentData = {
                        id: studentId,
                        name: nameElement.textContent,
                        code: codeElement.textContent.replace('รหัส: ', ''),
                        class: classElement.textContent,
                        attendanceRate: parseFloat(rateElement.textContent),
                        attendance: [
                            { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:45' },
                            { date: '7 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:50' },
                            { date: '8 พ.ค. 2568', status: 'ขาด', statusClass: 'text-danger', time: '-' },
                            { date: '9 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:42' },
                            { date: '10 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:40' }
                        ]
                    };
                } else {
                    // ข้อมูลตัวอย่างหากไม่พบในตาราง
                    studentData = {
                        id: studentId,
                        name: 'นักเรียนรหัส ' + studentId,
                        code: '67319010001',
                        class: 'ปวช.1/1',
                        attendanceRate: 65.8,
                        attendance: [
                            { date: '6 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:45' },
                            { date: '7 พ.ค. 2568', status: 'ขาด', statusClass: 'text-danger', time: '-' },
                            { date: '8 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:50' },
                            { date: '9 พ.ค. 2568', status: 'มา', statusClass: 'text-success', time: '07:42' },
                            { date: '10 พ.ค. 2568', status: 'ขาด', statusClass: 'text-danger', time: '-' }
                        ]
                    };
                }
                
                // อัปเดตชื่อนักเรียนใน modal
                document.getElementById('modal-student-name').textContent = studentData.name;
                
                // กำหนดคลาสสำหรับอัตราการเข้าแถว
                let rateClass = 'text-success';
                if (studentData.attendanceRate < 80 && studentData.attendanceRate >= 70) {
                    rateClass = 'text-warning';
                } else if (studentData.attendanceRate < 70) {
                    rateClass = 'text-danger';
                }
                
                // สร้าง HTML สำหรับแสดงข้อมูล
                let html = `
                    <div class="student-info">
                        <div class="student-header">
                            <h3>${studentData.name}</h3>
                            <p>${studentData.code}</p>
                            <p>ชั้น ${studentData.class}</p>
                            <p>อัตราการเข้าแถว: <span class="${rateClass}">${studentData.attendanceRate}%</span></p>
                        </div>
                        
                        <h4>ประวัติการเข้าแถว</h4>
                        <div class="table-responsive">
                            <table class="attendance-history-table">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>สถานะ</th>
                                        <th>เวลา</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;
                
                // เพิ่มข้อมูลประวัติการเข้าแถว
                studentData.attendance.forEach(day => {
                    html += `
                        <tr>
                            <td>${day.date}</td>
                            <td><span class="${day.statusClass}">${day.status}</span></td>
                            <td>${day.time}</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="button-group">
                            <button class="btn-primary" onclick="notifyParent(${studentId})">
                                <span class="material-icons">notifications</span> แจ้งเตือนผู้ปกครอง
                            </button>
                            <button class="btn-secondary" onclick="viewFullHistory(${studentId})">
                                <span class="material-icons">history</span> ดูประวัติทั้งหมด
                            </button>
                        </div>
                    </div>
                `;
                
                // อัปเดตเนื้อหาใน modal
                document.getElementById('student-detail-content').innerHTML = html;
            }, 800);
        }

        // ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครอง
        function notifyParent(studentId) {
            currentStudentId = studentId;
            
            // แสดง modal แจ้งเตือน
            document.getElementById('notificationModal').style.display = 'block';
            
            // ตั้งค่า template เริ่มต้น
            updateNotificationContent();
        }

        // ฟังก์ชันอัปเดตเนื้อหาข้อความแจ้งเตือน
        function updateNotificationContent() {
            const templateSelect = document.getElementById('notification-template');
            const contentField = document.getElementById('notification-content');
            
            const template = templateSelect.value;
            
            // ข้อมูลนักเรียน (ในระบบจริงควรดึงจาก AJAX)
            let studentName = "นักเรียน";
            const studentRow = document.querySelector(`#risk-students-table tr[data-student-id="${currentStudentId}"]`);
            if (studentRow) {
                studentName = studentRow.querySelector('.student-detail a').textContent;
            }
            
            // ตัวอย่างเทมเพลตข้อความ
            switch (template) {
                case 'risk_alert':
                    contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 70% ซึ่งต่ำกว่าเกณฑ์ที่กำหนด (80%)\n\nกรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nวิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'absence_alert':
                    contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านไม่ได้เข้าร่วมกิจกรรมเข้าแถวในวันนี้\n\nกรุณาติดต่อครูที่ปรึกษาหากมีข้อสงสัย\n\nด้วยความเคารพ\nวิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'monthly_report':
                    contentField.value = `เรียน ผู้ปกครองของ ${studentName}\n\nรายงานสรุปการเข้าแถวประจำเดือนพฤษภาคม 2568\n\nจำนวนวันเข้าแถว: 15 วัน\nจำนวนวันขาด: 5 วัน\nอัตราการเข้าแถว: 75%\nสถานะ: เสี่ยงไม่ผ่านกิจกรรม\n\nกรุณาติดต่อครูที่ปรึกษาเพื่อหาแนวทางแก้ไขต่อไป\n\nด้วยความเคารพ\nวิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'custom':
                    contentField.value = '';
                    break;
            }
        }

        // ฟังก์ชันส่งข้อความแจ้งเตือน
        function sendNotification() {
            const templateSelect = document.getElementById('notification-template');
            const contentField = document.getElementById('notification-content');
            
            const template = templateSelect.value;
            const content = contentField.value;
            
            if (!content.trim()) {
                alert('กรุณากรอกข้อความแจ้งเตือน');
                return;
            }
            
            showLoading();
            
            // จำลองการส่ง API request
            setTimeout(() => {
                hideLoading();
                
                // ปิด modal
                document.getElementById('notificationModal').style.display = 'none';
                
                // แสดงข้อความสำเร็จ
                alert(`ส่งข้อความแจ้งเตือนไปยังผู้ปกครองนักเรียนรหัส ${currentStudentId} เรียบร้อยแล้ว`);
            }, 800);
        }

        // ฟังก์ชันยืนยันการส่งแจ้งเตือนไปยังนักเรียนทั้งหมดที่เสี่ยง
        function confirmNotifyAllRiskStudents() {
            if (confirm('คุณต้องการส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดหรือไม่?')) {
                notifyAllRiskStudents();
            }
        }

        // ฟังก์ชันส่งการแจ้งเตือนไปยังผู้ปกครองทั้งหมด
        function notifyAllRiskStudents() {
            showLoading();
            
            // จำลองการส่ง API request
            setTimeout(() => {
                hideLoading();
                
                // แสดงข้อความสำเร็จ
                alert('ส่งข้อความแจ้งเตือนไปยังผู้ปกครองของนักเรียนที่เสี่ยงตกกิจกรรมทั้งหมดเรียบร้อยแล้ว');
            }, 1200);
        }

        // ฟังก์ชันดูประวัติการเข้าแถวทั้งหมด
        function viewFullHistory(studentId) {
            // ในระบบจริง ควรนำทางไปยังหน้าประวัติแบบละเอียด
            window.location.href = `student_history.php?id=${studentId}`;
        }

        // ฟังก์ชันสร้างปฏิทินรายวัน
        function createCalendarView() {
            const calendarView = document.getElementById('calendarView');
            if (!calendarView) return;
            
            // ล้างปฏิทินเดิม
            calendarView.innerHTML = '';
            
            // สร้างวันที่จำลอง
            const currentDate = new Date();
            const daysInMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0).getDate();
            
            for (let i = 1; i <= daysInMonth; i++) {
                const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);
                const isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6;
                const isToday = i === currentDate.getDate();
                
                // สร้างข้อมูลจำลอง
                let attendanceRate = '-';
                if (!isWeekend) {
                    if (i <= currentDate.getDate()) {
                        attendanceRate = Math.floor(90 + Math.random() * 8) + '%';
                    }
                }
                
                // สร้าง DOM element
                const dayElement = document.createElement('div');
                dayElement.className = `calendar-day${isWeekend ? ' weekend' : ''}${isToday ? ' today' : ''}`;
                
                dayElement.innerHTML = `
                    <div class="calendar-date">${i}</div>
                    <div class="calendar-stats">${attendanceRate}</div>
                `;
                
                calendarView.appendChild(dayElement);
            }
        }

        // ฟังก์ชันแสดง/ซ่อนเมนูบนมือถือ
        function toggleMobileMenu() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('active');
        }

        // ฟังก์ชันแสดง loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        // ฟังก์ชันซ่อน loading
        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    </script>
</body>
</html>