<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งข้อความแจ้งเตือน - ระบบน้องชูใจ AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #28a745;
            --primary-hover: #218838;
            --secondary-color: #6c757d;
            --secondary-hover: #5a6268;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --line-color: #06C755;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --border-color: #dee2e6;
            --text-color: #212529;
            --text-light: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: #f0f2f5;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header-title h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .header-title p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .header-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            background-color: var(--line-color);
            color: white;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .header-actions button:hover {
            opacity: 0.9;
        }
        
        .tabs {
            margin-bottom: 20px;
        }
        
        .tabs-nav {
            display: flex;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .tab-item {
            padding: 15px;
            flex: 1;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .tab-item.active {
            border-bottom-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .tab-item:hover:not(.active) {
            background-color: var(--light-gray);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--light-gray);
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-label {
            font-size: 14px;
            margin-bottom: 5px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-family: inherit;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: var(--secondary-hover);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-line {
            background-color: var(--line-color);
            color: white;
        }
        
        .btn-filter {
            background-color: var(--primary-color);
            color: white;
            margin-top: 26px; /* อไลน์กับฟอร์ม */
        }
        
        .filter-results {
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        table th {
            background-color: var(--light-gray);
            font-weight: 600;
        }
        
        table tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .badge-warning {
            background-color: rgba(255, 193, 7, 0.15);
            color: #d39e00;
        }
        
        .badge-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .student-avatar {
            width: 36px;
            height: 36px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .student-details {
            display: flex;
            flex-direction: column;
        }
        
        .student-name {
            font-weight: 500;
        }
        
        .student-id {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: rgba(0,0,0,0.05);
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background-color: rgba(0,0,0,0.1);
        }
        
        .action-btn-primary {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--primary-color);
        }
        
        .action-btn-primary:hover {
            background-color: rgba(40, 167, 69, 0.25);
        }
        
        .date-range {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            background-color: var(--light-gray);
            padding: 15px;
            border-radius: 8px;
        }
        
        .date-group {
            flex: 1;
        }
        
        .template-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .template-btn {
            padding: 10px 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .template-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .template-btn:hover:not(.active) {
            background-color: var(--light-gray);
        }
        
        .message-textarea {
            width: 100%;
            min-height: 200px;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            margin-bottom: 20px;
            resize: vertical;
        }
        
        .message-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .message-options {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .preview-box {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .preview-title {
            font-weight: 600;
        }
        
        .preview-content {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
        }
        
        .line-preview {
            max-width: 400px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .line-header {
            background-color: var(--line-color);
            height: 10px;
        }
        
        .line-body {
            padding: 20px;
        }
        
        .chart-preview {
            margin-top: 15px;
            border-top: 1px dashed var(--border-color);
            padding-top: 15px;
        }
        
        .chart-canvas {
            width: 100%;
            height: 200px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .link-preview {
            margin-top: 15px;
            border-top: 1px dashed var(--border-color);
            padding-top: 15px;
        }
        
        .message-cost {
            background-color: var(--light-gray);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .cost-title {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cost-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .cost-item {
            display: flex;
            justify-content: space-between;
        }
        
        .cost-total {
            grid-column: span 2;
            border-top: 1px dashed var(--border-color);
            padding-top: 10px;
            margin-top: 10px;
            font-weight: 600;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .recipients-container {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .recipient-item {
            display: flex;
            padding: 10px 15px;
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }
        
        .recipient-item:last-child {
            border-bottom: none;
        }
        
        .recipient-checkbox {
            margin-right: 10px;
        }
        
        .recipient-info {
            flex: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 20px;
            position: relative;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background-color: var(--light-gray);
            cursor: pointer;
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .result-summary {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .result-item {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
        }
        
        .result-icon {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .result-value {
            font-size: 24px;
            font-weight: 600;
        }
        
        .result-label {
            font-size: 14px;
            color: var(--text-light);
        }
        
        .result-success {
            background-color: rgba(40, 167, 69, 0.15);
        }
        
        .result-success .result-icon,
        .result-success .result-value {
            color: var(--success-color);
        }
        
        .result-error {
            background-color: rgba(220, 53, 69, 0.15);
        }
        
        .result-error .result-icon,
        .result-error .result-value {
            color: var(--danger-color);
        }
        
        .result-cost {
            background-color: rgba(23, 162, 184, 0.15);
        }
        
        .result-cost .result-icon,
        .result-cost .result-value {
            color: var(--info-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .header-actions button {
                width: 100%;
                justify-content: center;
            }
            
            .tabs-nav {
                flex-direction: column;
            }
            
            .filter-container,
            .date-range {
                flex-direction: column;
            }
            
            .btn-filter {
                margin-top: 10px;
                width: 100%;
            }
            
            .template-buttons {
                flex-direction: column;
            }
            
            .template-btn {
                width: 100%;
            }
            
            .cost-grid {
                grid-template-columns: 1fr;
            }
            
            .cost-total {
                grid-column: 1;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions button {
                width: 100%;
            }
            
            .modal-content {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">
                <h1>ระบบส่งข้อความแจ้งเตือน</h1>
                <p>ส่งข้อความแจ้งเตือนไปยังผู้ปกครองและครูที่ปรึกษาผ่าน LINE</p>
            </div>
            <div class="header-actions">
                <button onclick="showHistoryModal()">
                    <i class="fas fa-history"></i>
                    ดูประวัติการส่งข้อความ
                </button>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tabs-nav">
                <div class="tab-item active" data-tab="individual">
                    <i class="fas fa-user"></i> ส่งรายบุคคล
                </div>
                <div class="tab-item" data-tab="group">
                    <i class="fas fa-users"></i> ส่งกลุ่ม
                </div>
                <div class="tab-item" data-tab="templates">
                    <i class="fas fa-file-alt"></i> จัดการเทมเพลต
                </div>
            </div>
        </div>
        
        <!-- แท็บส่งรายบุคคล -->
        <div id="individual-tab" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-search"></i> ค้นหานักเรียน
                </div>
                <div class="card-body">
                    <div class="filter-container">
                        <div class="filter-group">
                            <div class="filter-label">ชื่อ-นามสกุลนักเรียน</div>
                            <input type="text" class="form-control" id="student-name" placeholder="ป้อนชื่อหรือนามสกุล">
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">ระดับชั้น</div>
                            <select class="form-control" id="class-level">
                                <option value="">-- ทุกระดับชั้น --</option>
                                <option value="ปวช.1">ปวช.1</option>
                                <option value="ปวช.2">ปวช.2</option>
                                <option value="ปวช.3">ปวช.3</option>
                                <option value="ปวส.1">ปวส.1</option>
                                <option value="ปวส.2">ปวส.2</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">กลุ่ม</div>
                            <select class="form-control" id="class-group">
                                <option value="">-- ทุกกลุ่ม --</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">สถานะการเข้าแถว</div>
                            <select class="form-control" id="attendance-status">
                                <option value="">-- ทุกสถานะ --</option>
                                <option value="เสี่ยงตกกิจกรรม">เสี่ยงตกกิจกรรม</option>
                                <option value="ต้องระวัง">ต้องระวัง</option>
                                <option value="ปกติ">ปกติ</option>
                            </select>
                        </div>
                        <button class="btn btn-filter" onclick="searchStudents()">
                            <i class="fas fa-search"></i> ค้นหา
                        </button>
                    </div>
                    
                    <div class="filter-results">พบนักเรียนทั้งหมด <span id="student-count">6</span> คน</div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th width="30%">นักเรียน</th>
                                    <th width="10%">ชั้น</th>
                                    <th width="15%">เข้าแถว</th>
                                    <th width="15%">สถานะ</th>
                                    <th width="15%">ผู้ปกครอง</th>
                                    <th width="10%">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="student-list">
                                <tr>
                                    <td>
                                        <input type="radio" name="student-select" value="1" checked>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">ธ</div>
                                            <div class="student-details">
                                                <div class="student-name">นายธนกฤต สุขใจ</div>
                                                <div class="student-id">รหัส 67319010001</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>ปวช.1/1</td>
                                    <td>26/40 (65%)</td>
                                    <td><span class="badge badge-danger">เสี่ยงตกกิจกรรม</span></td>
                                    <td>นางสมหญิง สุขใจ (แม่)</td>
                                    <td>
                                        <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(1)">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="radio" name="student-select" value="2">
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">ว</div>
                                            <div class="student-details">
                                                <div class="student-name">นางสาววรรณา มั่นคง</div>
                                                <div class="student-id">รหัส 67319010002</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>ปวช.1/1</td>
                                    <td>30/40 (75%)</td>
                                    <td><span class="badge badge-warning">ต้องระวัง</span></td>
                                    <td>นายวิทยา มั่นคง (พ่อ)</td>
                                    <td>
                                        <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(2)">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <input type="radio" name="student-select" value="3">
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">ส</div>
                                            <div class="student-details">
                                                <div class="student-name">นายสมชาย ใจดี</div>
                                                <div class="student-id">รหัส 67319010003</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>ปวช.1/1</td>
                                    <td>35/40 (87.5%)</td>
                                    <td><span class="badge badge-success">ปกติ</span></td>
                                    <td>นางเพ็ญศรี ใจดี (แม่)</td>
                                    <td>
                                        <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(3)">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card" id="message-card">
                <div class="card-header">
                    <i class="fas fa-paper-plane"></i> ส่งข้อความถึงผู้ปกครอง - <span id="selected-student-name">นายธนกฤต สุขใจ</span> (<span id="selected-student-class">ปวช.1/1</span>)
                </div>
                <div class="card-body">
                    <div class="date-range">
                        <div class="date-group">
                            <div class="filter-label">วันที่เริ่มต้น</div>
                            <input type="date" class="form-control" id="start-date" value="2025-04-01">
                        </div>
                        <div class="date-group">
                            <div class="filter-label">วันที่สิ้นสุด</div>
                            <input type="date" class="form-control" id="end-date" value="2025-04-30">
                        </div>
                    </div>
                    
                    <div class="template-buttons">
                        <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
                        <button class="template-btn" data-template="warning">แจ้งเตือนความเสี่ยง</button>
                        <button class="template-btn" data-template="critical">แจ้งเตือนฉุกเฉิน</button>
                        <button class="template-btn" data-template="summary">รายงานสรุป</button>
                        <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
                    </div>
                    
                    <textarea class="message-textarea" id="message-text">เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท</textarea>
                    
                    <div class="message-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="include-chart" checked>
                            <span>แนบกราฟสรุปการเข้าแถว</span>
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" id="include-link" checked>
                            <span>แนบลิงก์ดูข้อมูลโดยละเอียด</span>
                        </label>
                    </div>
                    
                    <div class="preview-box">
                        <div class="preview-header">
                            <div class="preview-title">ตัวอย่างข้อความที่จะส่ง</div>
                            <button class="btn btn-secondary" onclick="showPreviewModal()">
                                <i class="fas fa-eye"></i> แสดงตัวอย่าง
                            </button>
                        </div>
                        <div class="line-preview">
                            <div class="line-header"></div>
                            <div class="line-body" id="preview-content">
                                <strong>LINE Official Account: SADD-Prasat</strong>
                                <p style="margin-top: 10px;">เรียน ผู้ปกครองของ นายธนกฤต สุขใจ<br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 ปัจจุบันเข้าร่วม 26 วัน จากทั้งหมด 40 วัน (65%)<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                                
                                <div class="chart-preview">
                                    <div class="chart-canvas">
                                        [กราฟแสดงการเข้าแถว]
                                    </div>
                                </div>
                                
                                <div class="link-preview">
                                    <button class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-external-link-alt"></i> ดูข้อมูลโดยละเอียด
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="message-cost">
                        <div class="cost-title">
                            <i class="fas fa-coins"></i> ค่าใช้จ่ายในการส่ง
                        </div>
                        <div class="cost-grid">
                            <div class="cost-item">
                                <div>ข้อความ:</div>
                                <div>0.075 บาท</div>
                            </div>
                            <div class="cost-item">
                                <div>รูปภาพกราฟ:</div>
                                <div>0.150 บาท</div>
                            </div>
                            <div class="cost-item">
                                <div>ลิงก์:</div>
                                <div>0.075 บาท</div>
                            </div>
                            <div class="cost-item cost-total">
                                <div>รวม:</div>
                                <div>0.300 บาท</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> ยกเลิก
                        </button>
                        <button class="btn btn-line" onclick="sendMessage()">
                            <i class="fas fa-paper-plane"></i> ส่งข้อความ
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- แท็บส่งกลุ่ม -->
        <div id="group-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-filter"></i> ตัวกรองนักเรียนสำหรับส่งข้อความกลุ่ม
                </div>
                <div class="card-body">
                    <div class="filter-container">
                        <div class="filter-group">
                            <div class="filter-label">ระดับชั้น</div>
                            <select class="form-control" id="group-class-level">
                                <option value="">-- ทุกระดับชั้น --</option>
                                <option value="ปวช.1">ปวช.1</option>
                                <option value="ปวช.2">ปวช.2</option>
                                <option value="ปวช.3">ปวช.3</option>
                                <option value="ปวส.1">ปวส.1</option>
                                <option value="ปวส.2">ปวส.2</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">กลุ่ม</div>
                            <select class="form-control" id="group-class-group">
                                <option value="">-- ทุกกลุ่ม --</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">สถานะการเข้าแถว</div>
                            <select class="form-control" id="group-attendance-status">
                                <option value="">-- ทุกสถานะ --</option>
                                <option value="เสี่ยงตกกิจกรรม" selected>เสี่ยงตกกิจกรรม</option>
                                <option value="ต้องระวัง">ต้องระวัง</option>
                                <option value="ปกติ">ปกติ</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <div class="filter-label">อัตราการเข้าแถว</div>
                            <select class="form-control" id="group-attendance-rate">
                                <option value="">-- ทั้งหมด --</option>
                                <option value="น้อยกว่า 70%" selected>น้อยกว่า 70%</option>
                                <option value="70% - 80%">70% - 80%</option>
                                <option value="80% - 90%">80% - 90%</option>
                                <option value="มากกว่า 90%">มากกว่า 90%</option>
                            </select>
                        </div>
                        <button class="btn btn-filter" onclick="filterGroupRecipients()">
                            <i class="fas fa-filter"></i> กรองข้อมูล
                        </button>
                    </div>
                    
                    <div class="filter-results">พบนักเรียนที่ตรงตามเงื่อนไข <span id="recipient-count">2</span> คน</div>
                    
                    <div class="recipients-container" id="recipients-list">
                        <div class="recipient-item">
                            <input type="checkbox" class="recipient-checkbox" value="1" checked>
                            <div class="recipient-info">
                                <div class="student-info">
                                    <div class="student-avatar">ธ</div>
                                    <div class="student-details">
                                        <div class="student-name">นายธนกฤต สุขใจ</div>
                                        <div>ปวช.1/1 | 26/40 (65%) | <span class="badge badge-danger">เสี่ยงตกกิจกรรม</span></div>
                                        <div class="student-id">ผู้ปกครอง: นางสมหญิง สุขใจ (แม่)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="recipient-item">
                            <input type="checkbox" class="recipient-checkbox" value="2" checked>
                            <div class="recipient-info">
                                <div class="student-info">
                                    <div class="student-avatar">ว</div>
                                    <div class="student-details">
                                        <div class="student-name">นางสาววรรณา มั่นคง</div>
                                        <div>ปวช.1/1 | 30/40 (75%) | <span class="badge badge-warning">ต้องระวัง</span></div>
                                        <div class="student-id">ผู้ปกครอง: นายวิทยา มั่นคง (พ่อ)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions" style="justify-content: flex-start;">
                        <button class="btn btn-secondary" onclick="selectAllRecipients()">
                            <i class="fas fa-check-square"></i> เลือกทั้งหมด
                        </button>
                        <button class="btn btn-secondary" onclick="clearAllRecipients()">
                            <i class="fas fa-square"></i> ยกเลิกเลือกทั้งหมด
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-paper-plane"></i> ส่งข้อความถึงผู้ปกครองกลุ่ม (<span id="selected-count">2</span> คน)
                </div>
                <div class="card-body">
                    <div class="date-range">
                        <div class="date-group">
                            <div class="filter-label">วันที่เริ่มต้น</div>
                            <input type="date" class="form-control" id="group-start-date" value="2025-04-01">
                        </div>
                        <div class="date-group">
                            <div class="filter-label">วันที่สิ้นสุด</div>
                            <input type="date" class="form-control" id="group-end-date" value="2025-04-30">
                        </div>
                    </div>
                    
                    <div class="template-buttons">
                        <button class="template-btn active" data-template="regular">ข้อความปกติ</button>
                        <button class="template-btn" data-template="risk-warning">แจ้งเตือนกลุ่มเสี่ยง</button>
                        <button class="template-btn" data-template="meeting">นัดประชุมผู้ปกครอง</button>
                        <button class="template-btn" data-template="custom">ข้อความทั่วไป</button>
                    </div>
                    
                    <textarea class="message-textarea" id="group-message-text">เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท</textarea>
                    
                    <div class="message-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="group-include-chart" checked>
                            <span>แนบกราฟสรุปการเข้าแถวแยกรายบุคคล</span>
                        </label>
                        <label class="checkbox-container">
                            <input type="checkbox" id="group-include-link" checked>
                            <span>แนบลิงก์ดูข้อมูลโดยละเอียด</span>
                        </label>
                    </div>
                    
                    <div class="preview-box">
                        <div class="preview-header">
                            <div class="preview-title">ตัวอย่างข้อความที่จะส่ง</div>
                            <button class="btn btn-secondary" onclick="showGroupPreviewModal()">
                                <i class="fas fa-eye"></i> แสดงตัวอย่าง
                            </button>
                        </div>
                        <div class="line-preview">
                            <div class="line-header"></div>
                            <div class="line-body" id="group-preview-content">
                                <strong>LINE Official Account: SADD-Prasat</strong>
                                <p style="margin-top: 10px;">เรียน ท่านผู้ปกครองนักเรียนชั้น ปวช.1/1<br><br>ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด<br><br>โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา<br><br>กรุณาติดต่อครูที่ปรึกษาประจำชั้น ปวช.1/1 อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                                
                                <div class="chart-preview">
                                    <div style="font-size: 12px; color: #666; margin-bottom: 10px;">* แต่ละนักเรียนจะได้รับกราฟข้อมูลการเข้าแถวเฉพาะของตนเอง</div>
                                    <div class="chart-canvas">
                                        [กราฟแสดงการเข้าแถว]
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="message-cost">
                        <div class="cost-title">
                            <i class="fas fa-coins"></i> ประมาณการค่าใช้จ่ายในการส่ง
                        </div>
                        <div class="cost-grid">
                            <div class="cost-item">
                                <div>ข้อความ (2 คน):</div>
                                <div>0.150 บาท</div>
                            </div>
                            <div class="cost-item">
                                <div>รูปภาพกราฟ (2 รูป):</div>
                                <div>0.300 บาท</div>
                            </div>
                            <div class="cost-item">
                                <div>ลิงก์ (2 ลิงก์):</div>
                                <div>0.150 บาท</div>
                            </div>
                            <div class="cost-item cost-total">
                                <div>รวม:</div>
                                <div>0.600 บาท</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button class="btn btn-secondary" onclick="resetGroupForm()">
                            <i class="fas fa-undo"></i> ยกเลิก
                        </button>
                        <button class="btn btn-line" onclick="sendGroupMessage()">
                            <i class="fas fa-paper-plane"></i> ส่งข้อความ (2 ราย)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- แท็บจัดการเทมเพลต -->
        <div id="templates-tab" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-file-alt"></i> จัดการเทมเพลตข้อความแจ้งเตือน
                </div>
                <div class="card-body">
                    <div class="form-actions" style="justify-content: flex-start; margin-bottom: 20px;">
                        <button class="btn btn-primary" onclick="showCreateTemplateModal()">
                            <i class="fas fa-plus"></i> สร้างเทมเพลตใหม่
                        </button>
                    </div>
                    
                    <div class="template-buttons" style="margin-bottom: 20px;">
                        <button class="template-btn active" data-category="all">ทั้งหมด</button>
                        <button class="template-btn" data-category="attendance">การเข้าแถว</button>
                        <button class="template-btn" data-category="meeting">การประชุม</button>
                        <button class="template-btn" data-category="activity">กิจกรรม</button>
                        <button class="template-btn" data-category="other">อื่นๆ</button>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th width="25%">ชื่อเทมเพลต</th>
                                    <th width="15%">ประเภท</th>
                                    <th width="15%">หมวดหมู่</th>
                                    <th width="15%">สร้างเมื่อ</th>
                                    <th width="15%">ใช้งานล่าสุด</th>
                                    <th width="15%">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>แจ้งเตือนความเสี่ยงรายบุคคล</td>
                                    <td>รายบุคคล</td>
                                    <td>การเข้าแถว</td>
                                    <td>01/03/2568</td>
                                    <td>20/04/2568</td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="action-btn" title="แก้ไข" onclick="editTemplate(1)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="ดูตัวอย่าง" onclick="previewTemplate(1)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="ลบ" onclick="deleteTemplate(1)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>นัดประชุมผู้ปกครองกลุ่มเสี่ยง</td>
                                    <td>กลุ่ม</td>
                                    <td>การประชุม</td>
                                    <td>01/03/2568</td>
                                    <td>15/04/2568</td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="action-btn" title="แก้ไข" onclick="editTemplate(2)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="ดูตัวอย่าง" onclick="previewTemplate(2)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="ลบ" onclick="deleteTemplate(2)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>แจ้งเตือนฉุกเฉิน</td>
                                    <td>รายบุคคล</td>
                                    <td>การเข้าแถว</td>
                                    <td>01/03/2568</td>
                                    <td>10/04/2568</td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="action-btn" title="แก้ไข" onclick="editTemplate(3)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="ดูตัวอย่าง" onclick="previewTemplate(3)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="ลบ" onclick="deleteTemplate(3)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>รายงานสรุปประจำเดือน</td>
                                    <td>รายบุคคล</td>
                                    <td>การเข้าแถว</td>
                                    <td>01/03/2568</td>
                                    <td>05/04/2568</td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="action-btn" title="แก้ไข" onclick="editTemplate(4)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="ดูตัวอย่าง" onclick="previewTemplate(4)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="ลบ" onclick="deleteTemplate(4)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>แจ้งข่าวกิจกรรมวิทยาลัย</td>
                                    <td>กลุ่ม</td>
                                    <td>กิจกรรม</td>
                                    <td>01/03/2568</td>
                                    <td>01/04/2568</td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button class="action-btn" title="แก้ไข" onclick="editTemplate(5)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn" title="ดูตัวอย่าง" onclick="previewTemplate(5)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="action-btn" title="ลบ" onclick="deleteTemplate(5)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- โมดัลแสดงตัวอย่างข้อความ -->
    <div id="preview-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('preview-modal')">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h3 class="modal-title">ตัวอย่างข้อความที่จะส่ง</h3>
            </div>
            <div class="line-preview" style="max-width: 100%;">
                <div class="line-header"></div>
                <div class="line-body" id="modal-preview-content">
                    <strong>LINE Official Account: SADD-Prasat</strong>
                    <p style="margin-top: 10px;">เรียน ผู้ปกครองของ นายธนกฤต สุขใจ<br><br>ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 ปัจจุบันเข้าร่วม 26 วัน จากทั้งหมด 40 วัน (65%)<br><br>จึงเรียนมาเพื่อทราบ<br><br>ด้วยความเคารพ<br>ฝ่ายกิจการนักเรียน<br>วิทยาลัยการอาชีพปราสาท</p>
                    
                    <div class="chart-preview">
                        <div class="chart-canvas">
                            [กราฟแสดงการเข้าแถว]
                        </div>
                    </div>
                    
                    <div class="link-preview">
                        <button class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-external-link-alt"></i> ดูข้อมูลโดยละเอียด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- โมดัลสร้างเทมเพลตใหม่ -->
    <div id="template-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('template-modal')">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h3 class="modal-title">สร้างเทมเพลตข้อความใหม่</h3>
            </div>
            <div>
                <div class="filter-label">ชื่อเทมเพลต</div>
                <input type="text" class="form-control" id="template-name" placeholder="กรุณากรอกชื่อเทมเพลต" style="margin-bottom: 15px;">
                
                <div class="filter-label">ประเภท</div>
                <select class="form-control" id="template-type" style="margin-bottom: 15px;">
                    <option value="individual">รายบุคคล</option>
                    <option value="group">กลุ่ม</option>
                </select>
                
                <div class="filter-label">หมวดหมู่</div>
                <select class="form-control" id="template-category" style="margin-bottom: 15px;">
                    <option value="attendance">การเข้าแถว</option>
                    <option value="meeting">การประชุม</option>
                    <option value="activity">กิจกรรม</option>
                    <option value="other">อื่นๆ</option>
                </select>
                
                <div class="filter-label">เนื้อหาข้อความ</div>
                <textarea class="message-textarea" id="template-content" style="min-height: 150px;"></textarea>
                
                <div style="font-size: 12px; color: #666; margin-bottom: 20px;">
                    * คุณสามารถใช้ตัวแปรในข้อความได้ เช่น {{ชื่อนักเรียน}}, {{ชั้นเรียน}}, {{ร้อยละการเข้าแถว}}
                </div>
                
                <div class="form-actions">
                    <button class="btn btn-secondary" onclick="closeModal('template-modal')">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button class="btn btn-primary" onclick="saveTemplate()">
                        <i class="fas fa-save"></i> บันทึกเทมเพลต
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- โมดัลประวัติการส่งข้อความ -->
    <div id="history-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('history-modal')">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h3 class="modal-title">ประวัติการส่งข้อความ</h3>
            </div>
            <div class="filter-container" style="margin-bottom: 20px;">
                <div class="filter-group">
                    <div class="filter-label">ค้นหานักเรียน</div>
                    <input type="text" class="form-control" id="history-student-name" placeholder="ป้อนชื่อนักเรียน">
                </div>
                <div class="filter-group">
                    <div class="filter-label">ประเภทข้อความ</div>
                    <select class="form-control" id="history-message-type">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="attendance">การเข้าแถว</option>
                        <option value="risk_alert">แจ้งเตือนความเสี่ยง</option>
                        <option value="system">ข้อความระบบ</option>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-label">สถานะการส่ง</div>
                    <select class="form-control" id="history-status">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="sent">ส่งสำเร็จ</option>
                        <option value="failed">ส่งไม่สำเร็จ</option>
                    </select>
                </div>
                <button class="btn btn-filter" onclick="searchHistory()">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="20%">วันที่ส่ง</th>
                            <th width="25%">นักเรียน/ผู้ปกครอง</th>
                            <th width="15%">ประเภท</th>
                            <th width="15%">สถานะ</th>
                            <th width="25%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>30/04/2568 14:25</td>
                            <td>นายธนกฤต สุขใจ / นางสมหญิง สุขใจ</td>
                            <td>การเข้าแถว</td>
                            <td><span class="badge badge-success">ส่งสำเร็จ</span></td>
                            <td>
                                <button class="btn btn-secondary" onclick="viewMessage(1)">
                                    <i class="fas fa-eye"></i> ดูข้อความ
                                </button>
                                <button class="btn btn-primary" onclick="resendMessage(1)">
                                    <i class="fas fa-redo"></i> ส่งอีกครั้ง
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>30/04/2568 14:20</td>
                            <td>นางสาววรรณา มั่นคง / นายวิทยา มั่นคง</td>
                            <td>แจ้งเตือนความเสี่ยง</td>
                            <td><span class="badge badge-danger">ส่งไม่สำเร็จ</span></td>
                            <td>
                                <button class="btn btn-secondary" onclick="viewMessage(2)">
                                    <i class="fas fa-eye"></i> ดูข้อความ
                                </button>
                                <button class="btn btn-primary" onclick="resendMessage(2)">
                                    <i class="fas fa-redo"></i> ส่งอีกครั้ง
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>29/04/2568 09:30</td>
                            <td>นายสมชาย ใจดี / นางเพ็ญศรี ใจดี</td>
                            <td>การเข้าแถว</td>
                            <td><span class="badge badge-success">ส่งสำเร็จ</span></td>
                            <td>
                                <button class="btn btn-secondary" onclick="viewMessage(3)">
                                    <i class="fas fa-eye"></i> ดูข้อความ
                                </button>
                                <button class="btn btn-primary" onclick="resendMessage(3)">
                                    <i class="fas fa-redo"></i> ส่งอีกครั้ง
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- โมดัลผลลัพธ์การส่งข้อความ -->
    <div id="result-modal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal('result-modal')">
                <i class="fas fa-times"></i>
            </button>
            <div class="modal-header">
                <h3 class="modal-title">ผลลัพธ์การส่งข้อความ</h3>
            </div>
            <div class="result-summary">
                <div class="result-item result-success">
                    <div class="result-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="result-value">1</div>
                    <div class="result-label">สำเร็จ</div>
                </div>
                <div class="result-item result-error">
                    <div class="result-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="result-value">0</div>
                    <div class="result-label">ล้มเหลว</div>
                </div>
                <div class="result-item result-cost">
                    <div class="result-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="result-value">0.30</div>
                    <div class="result-label">บาท</div>
                </div>
            </div>
            <div class="table-responsive" id="result-details">
                <table>
                    <thead>
                        <tr>
                            <th>นักเรียน</th>
                            <th>สถานะ</th>
                            <th>จำนวนข้อความ</th>
                            <th>ค่าใช้จ่าย</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>นายธนกฤต สุขใจ</td>
                            <td><span class="badge badge-success">ส่งสำเร็จ</span></td>
                            <td>3</td>
                            <td>0.30 บาท</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="form-actions" style="justify-content: center; margin-top: 20px;">
                <button class="btn btn-primary" onclick="closeModal('result-modal')">
                    <i class="fas fa-check"></i> ตกลง
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // เลือก tab
        const tabItems = document.querySelectorAll('.tab-item');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabItems.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.getAttribute('data-tab');
                
                // ลบคลาส active จากทุก tab
                tabItems.forEach(item => item.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // เพิ่มคลาส active ให้กับ tab ที่เลือก
                tab.classList.add('active');
                document.getElementById(tabId + '-tab').classList.add('active');
            });
        });
        
        // เลือกเทมเพลต
        const templateButtons = document.querySelectorAll('.template-btn');
        
        templateButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                // หาปุ่มที่อยู่ในกลุ่มเดียวกัน
                const parent = btn.parentElement;
                const buttons = parent.querySelectorAll('.template-btn');
                
                // ลบคลาส active จากทุกปุ่ม
                buttons.forEach(button => button.classList.remove('active'));
                
                // เพิ่มคลาส active ให้กับปุ่มที่เลือก
                btn.classList.add('active');
                
                // เปลี่ยนข้อความตามเทมเพลตที่เลือก
                const template = btn.getAttribute('data-template');
                if (parent.closest('#individual-tab')) {
                    changeIndividualTemplate(template);
                } else if (parent.closest('#group-tab')) {
                    changeGroupTemplate(template);
                } else if (parent.closest('#templates-tab')) {
                    filterTemplates(template);
                }
            });
        });
        
        // เปลี่ยนข้อความตามเทมเพลตที่เลือกสำหรับส่งรายบุคคล
        function changeIndividualTemplate(template) {
            const messageText = document.getElementById('message-text');
            const studentName = document.getElementById('selected-student-name').textContent;
            const studentClass = document.getElementById('selected-student-class').textContent;
            
            switch(template) {
                case 'regular':
                    messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'warning':
                    messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'critical':
                    messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

ขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'summary':
                    const currentDate = new Date();
                    const month = currentDate.toLocaleString('th-TH', { month: 'long' });
                    const year = currentDate.getFullYear() + 543; // พ.ศ.
                    
                    messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

สรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน${month} ${year}

จำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)
จำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน
สถานะ: {{สถานะการเข้าแถว}}

หมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'custom':
                    messageText.value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

[ข้อความของท่าน] กรุณาพิมพ์ข้อความที่ต้องการส่งที่นี่

ท่านสามารถใช้ตัวแปรต่างๆ เช่น:
- {{ชื่อนักเรียน}} - ชื่อของนักเรียน
- {{ชั้นเรียน}} - ชั้นเรียนของนักเรียน
- {{จำนวนวันเข้าแถว}} - จำนวนวันที่นักเรียนเข้าแถว
- {{จำนวนวันทั้งหมด}} - จำนวนวันทั้งหมดในช่วงเวลาที่เลือก
- {{ร้อยละการเข้าแถว}} - อัตราการเข้าแถวเป็นเปอร์เซ็นต์
- {{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา
- {{เบอร์โทรครู}} - เบอร์โทรของครูที่ปรึกษา

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
            }
            
            updatePreviewContent();
            updateMessageCost();
        }
        
        // เปลี่ยนข้อความตามเทมเพลตที่เลือกสำหรับส่งกลุ่ม
        function changeGroupTemplate(template) {
            const messageText = document.getElementById('group-message-text');
            
            switch(template) {
                case 'regular':
                    messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียนชั้น {{ชั้นเรียน}} ประจำเดือนนี้

กรุณาติดตามการเข้าแถวของบุตรหลานท่านได้ผ่านระบบแจ้งเตือนของวิทยาลัย หากมีข้อสงสัยกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}}

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'risk-warning':
                    messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

โดยอัตราการเข้าแถวของนักเรียนอยู่ที่ต่ำกว่า 70% ซึ่งหากต่ำกว่า 80% เมื่อสิ้นภาคเรียน นักเรียนจะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'meeting':
                    messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มิถุนายน 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้

1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา
2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว
3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 'custom':
                    messageText.value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

[ข้อความของท่าน] กรุณาพิมพ์ข้อความที่ต้องการส่งที่นี่

ท่านสามารถใช้ตัวแปรต่างๆ เช่น:
- {{ชั้นเรียน}} - ชั้นเรียนของนักเรียน
- {{ชื่อครูที่ปรึกษา}} - ชื่อครูที่ปรึกษา
- {{เบอร์โทรครู}} - เบอร์โทรของครูที่ปรึกษา

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
            }
            
            updateGroupPreviewContent();
            updateGroupMessageCost();
        }
        
        // กรองเทมเพลตตามหมวดหมู่
        function filterTemplates(category) {
            // ใช้สำหรับกรองเทมเพลตในแท็บจัดการเทมเพลต
            const rows = document.querySelectorAll('#templates-tab table tbody tr');
            
            rows.forEach(row => {
                if (category === 'all') {
                    row.style.display = '';
                } else {
                    const categoryCell = row.querySelectorAll('td')[2].textContent;
                    row.style.display = categoryCell === category ? '' : 'none';
                }
            });
        }
        
        // ฟังก์ชันเปิด-ปิดโมดัล
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // แสดงโมดัลตัวอย่างข้อความ
        function showPreviewModal() {
            const previewContent = document.getElementById('preview-content').innerHTML;
            document.getElementById('modal-preview-content').innerHTML = previewContent;
            showModal('preview-modal');
        }
        
        function showGroupPreviewModal() {
            const previewContent = document.getElementById('group-preview-content').innerHTML;
            document.getElementById('modal-preview-content').innerHTML = previewContent;
            showModal('preview-modal');
        }
        
        // แสดงโมดัลสร้างเทมเพลตใหม่
        function showCreateTemplateModal() {
            document.getElementById('template-name').value = '';
            document.getElementById('template-content').value = '';
            showModal('template-modal');
        }
        
        // แสดงโมดัลประวัติการส่งข้อความ
        function showHistoryModal() {
            showModal('history-modal');
        }
        
        // ค้นหานักเรียน
        function searchStudents() {
            const studentName = document.getElementById('student-name').value;
            const classLevel = document.getElementById('class-level').value;
            const classGroup = document.getElementById('class-group').value;
            const attendanceStatus = document.getElementById('attendance-status').value;
            
            // ในสถานการณ์จริง นี่จะเป็นการส่ง AJAX request ไปยังเซิร์ฟเวอร์
            // เพื่อดึงข้อมูลนักเรียนตามเงื่อนไขที่กำหนด
            
            // แสดงข้อความกำลังค้นหา
            showToast('กำลังค้นหานักเรียน...', 'info');
            
            // จำลองการค้นหา (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // จำลองผลลัพธ์การค้นหา
                const studentList = document.getElementById('student-list');
                
                // ตัวอย่าง: สร้างข้อมูลจำลอง
                let html = '';
                
                if (attendanceStatus === 'เสี่ยงตกกิจกรรม' || attendanceStatus === '') {
                    html += `
                        <tr>
                            <td>
                                <input type="radio" name="student-select" value="1" checked>
                            </td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">ธ</div>
                                    <div class="student-details">
                                        <div class="student-name">นายธนกฤต สุขใจ</div>
                                        <div class="student-id">รหัส 67319010001</div>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/1</td>
                            <td>26/40 (65%)</td>
                            <td><span class="badge badge-danger">เสี่ยงตกกิจกรรม</span></td>
                            <td>นางสมหญิง สุขใจ (แม่)</td>
                            <td>
                                <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(1)">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }
                
                if (attendanceStatus === 'ต้องระวัง' || attendanceStatus === '') {
                    html += `
                        <tr>
                            <td>
                                <input type="radio" name="student-select" value="2">
                            </td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">ว</div>
                                    <div class="student-details">
                                        <div class="student-name">นางสาววรรณา มั่นคง</div>
                                        <div class="student-id">รหัส 67319010002</div>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/1</td>
                            <td>30/40 (75%)</td>
                            <td><span class="badge badge-warning">ต้องระวัง</span></td>
                            <td>นายวิทยา มั่นคง (พ่อ)</td>
                            <td>
                                <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(2)">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }
                
                if (attendanceStatus === 'ปกติ' || attendanceStatus === '') {
                    html += `
                        <tr>
                            <td>
                                <input type="radio" name="student-select" value="3">
                            </td>
                            <td>
                                <div class="student-info">
                                    <div class="student-avatar">ส</div>
                                    <div class="student-details">
                                        <div class="student-name">นายสมชาย ใจดี</div>
                                        <div class="student-id">รหัส 67319010003</div>
                                    </div>
                                </div>
                            </td>
                            <td>ปวช.1/1</td>
                            <td>35/40 (87.5%)</td>
                            <td><span class="badge badge-success">ปกติ</span></td>
                            <td>นางเพ็ญศรี ใจดี (แม่)</td>
                            <td>
                                <button class="action-btn action-btn-primary" title="ส่งข้อความ" onclick="selectStudent(3)">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }
                
                studentList.innerHTML = html;
                
                // อัปเดตจำนวนนักเรียน
                const count = studentList.querySelectorAll('tr').length;
                document.getElementById('student-count').textContent = count;
                
                showToast(`พบนักเรียนทั้งหมด ${count} คน`, 'success');
            }, 500);
        }
        
        // เลือกนักเรียนที่จะส่งข้อความ
        function selectStudent(studentId) {
            // เลือก radio button
            const radioButton = document.querySelector(`input[name="student-select"][value="${studentId}"]`);
            if (radioButton) {
                radioButton.checked = true;
            }
            
            // อัปเดตข้อมูลนักเรียนที่เลือก
            const row = radioButton.closest('tr');
            const studentName = row.querySelector('.student-name').textContent;
            const studentClass = row.querySelector('td:nth-child(3)').textContent;
            
            document.getElementById('selected-student-name').textContent = studentName;
            document.getElementById('selected-student-class').textContent = studentClass;
            
            // อัปเดตตัวอย่างข้อความ
            updatePreviewContent();
            
            // เลื่อนไปยังส่วนส่งข้อความ
            document.getElementById('message-card').scrollIntoView({ behavior: 'smooth' });
        }
        
        // อัปเดตตัวอย่างข้อความสำหรับส่งรายบุคคล
        function updatePreviewContent() {
            const messageText = document.getElementById('message-text').value;
            const studentName = document.getElementById('selected-student-name').textContent;
            const studentClass = document.getElementById('selected-student-class').textContent;
            
            // แทนที่ตัวแปรในข้อความ
            let previewText = messageText
                .replace(/{{ชื่อนักเรียน}}/g, studentName)
                .replace(/{{ชั้นเรียน}}/g, studentClass)
                .replace(/{{จำนวนวันเข้าแถว}}/g, '26')
                .replace(/{{จำนวนวันทั้งหมด}}/g, '40')
                .replace(/{{ร้อยละการเข้าแถว}}/g, '65')
                .replace(/{{จำนวนวันขาด}}/g, '14')
                .replace(/{{สถานะการเข้าแถว}}/g, 'เสี่ยงตกกิจกรรม')
                .replace(/{{ชื่อครูที่ปรึกษา}}/g, 'อ.ประสิทธิ์ ดีเลิศ')
                .replace(/{{เบอร์โทรครู}}/g, '081-234-5678');
            
            // แทนที่ขึ้นบรรทัดใหม่ด้วย <br>
            previewText = previewText.replace(/\n/g, '<br>');
            
            // อัปเดตตัวอย่าง
            const previewContent = document.getElementById('preview-content');
            previewContent.querySelector('p').innerHTML = previewText;
            
            // แสดง/ซ่อนกราฟและลิงก์ตามตัวเลือก
            const includeChart = document.getElementById('include-chart').checked;
            const includeLink = document.getElementById('include-link').checked;
            
            const chartPreview = previewContent.querySelector('.chart-preview');
            const linkPreview = previewContent.querySelector('.link-preview');
            
            if (chartPreview) {
                chartPreview.style.display = includeChart ? 'block' : 'none';
            }
            
            if (linkPreview) {
                linkPreview.style.display = includeLink ? 'block' : 'none';
            }
        }
        
        // อัปเดตตัวอย่างข้อความสำหรับส่งกลุ่ม
        function updateGroupPreviewContent() {
            const messageText = document.getElementById('group-message-text').value;
            
            // แทนที่ตัวแปรในข้อความ
            let previewText = messageText
                .replace(/{{ชั้นเรียน}}/g, 'ปวช.1/1')
                .replace(/{{ชื่อครูที่ปรึกษา}}/g, 'อ.ประสิทธิ์ ดีเลิศ')
                .replace(/{{เบอร์โทรครู}}/g, '081-234-5678');
            
            // แทนที่ขึ้นบรรทัดใหม่ด้วย <br>
            previewText = previewText.replace(/\n/g, '<br>');
            
            // อัปเดตตัวอย่าง
            const previewContent = document.getElementById('group-preview-content');
            previewContent.querySelector('p').innerHTML = previewText;
            
            // แสดง/ซ่อนกราฟและลิงก์ตามตัวเลือก
            const includeChart = document.getElementById('group-include-chart').checked;
            const includeLink = document.getElementById('group-include-link').checked;
            
            const chartPreview = previewContent.querySelector('.chart-preview');
            if (chartPreview) {
                chartPreview.style.display = includeChart ? 'block' : 'none';
            }
        }
        
        // อัปเดตค่าใช้จ่ายในการส่งข้อความรายบุคคล
        function updateMessageCost() {
            const includeChart = document.getElementById('include-chart').checked;
            const includeLink = document.getElementById('include-link').checked;
            
            const messageCost = 0.075; // บาทต่อข้อความ
            const chartCost = 0.150; // บาทต่อรูปภาพ
            const linkCost = 0.075; // บาทต่อลิงก์
            
            let totalCost = messageCost;
            
            if (includeChart) {
                totalCost += chartCost;
            }
            
            if (includeLink) {
                totalCost += linkCost;
            }
            
            // อัปเดตตารางค่าใช้จ่าย
            const costGrid = document.querySelector('#individual-tab .cost-grid');
            
            costGrid.querySelector('.cost-item:nth-child(2) .cost-value').textContent = includeChart ? `${chartCost.toFixed(3)} บาท` : '0.000 บาท';
            costGrid.querySelector('.cost-item:nth-child(3) .cost-value').textContent = includeLink ? `${linkCost.toFixed(3)} บาท` : '0.000 บาท';
            costGrid.querySelector('.cost-total .cost-value').textContent = `${totalCost.toFixed(3)} บาท`;
        }
        
        // อัปเดตค่าใช้จ่ายในการส่งข้อความกลุ่ม
        function updateGroupMessageCost() {
            const includeChart = document.getElementById('group-include-chart').checked;
            const includeLink = document.getElementById('group-include-link').checked;
            
            // จำนวนผู้รับที่เลือก
            const recipientCount = document.querySelectorAll('.recipient-checkbox:checked').length;
            
            const messageCost = 0.075; // บาทต่อข้อความ
            const chartCost = 0.150; // บาทต่อรูปภาพ
            const linkCost = 0.075; // บาทต่อลิงก์
            
            // คำนวณค่าใช้จ่ายรวม
            const messageTotal = recipientCount * messageCost;
            const chartTotal = includeChart ? recipientCount * chartCost : 0;
            const linkTotal = includeLink ? recipientCount * linkCost : 0;
            const totalCost = messageTotal + chartTotal + linkTotal;
            
            // อัปเดตตารางค่าใช้จ่าย
            const costGrid = document.querySelector('#group-tab .cost-grid');
            
            costGrid.querySelector('.cost-item:nth-child(1)').innerHTML = `
                <div>ข้อความ (${recipientCount} คน):</div>
                <div>${messageTotal.toFixed(3)} บาท</div>
            `;
            
            costGrid.querySelector('.cost-item:nth-child(2)').innerHTML = `
                <div>รูปภาพกราฟ (${includeChart ? recipientCount : 0} รูป):</div>
                <div>${chartTotal.toFixed(3)} บาท</div>
            `;
            
            costGrid.querySelector('.cost-item:nth-child(3)').innerHTML = `
                <div>ลิงก์ (${includeLink ? recipientCount : 0} ลิงก์):</div>
                <div>${linkTotal.toFixed(3)} บาท</div>
            `;
            
            costGrid.querySelector('.cost-total .cost-value').textContent = `${totalCost.toFixed(3)} บาท`;
        }
        
        // ส่งข้อความรายบุคคล
        function sendMessage() {
            // ตรวจสอบว่ามีการเลือกนักเรียนหรือไม่
            const selectedStudent = document.querySelector('input[name="student-select"]:checked');
            
            if (!selectedStudent) {
                showToast('กรุณาเลือกนักเรียนก่อนส่งข้อความ', 'warning');
                return;
            }
            
            // ตรวจสอบว่ามีข้อความหรือไม่
            const messageText = document.getElementById('message-text').value.trim();
            if (!messageText) {
                showToast('กรุณากรอกข้อความก่อนส่ง', 'warning');
                return;
            }
            
            // แสดงข้อความกำลังส่ง
            showToast('กำลังส่งข้อความ...', 'info');
            
            // จำลองการส่งข้อความ (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // แสดงโมดัลผลลัพธ์การส่ง
                const resultModal = document.getElementById('result-modal');
                
                // สร้างตารางผลลัพธ์
                const studentRow = selectedStudent.closest('tr');
                const studentName = studentRow.querySelector('.student-name').textContent;
                
                const includeChart = document.getElementById('include-chart').checked;
                const includeLink = document.getElementById('include-link').checked;
                
                const messageCost = 0.075;
                const chartCost = includeChart ? 0.150 : 0;
                const linkCost = includeLink ? 0.075 : 0;
                const totalCost = messageCost + chartCost + linkCost;
                const messageCount = 1 + (includeChart ? 1 : 0) + (includeLink ? 1 : 0);
                
                // อัปเดตข้อมูลในโมดัลผลลัพธ์
                resultModal.querySelector('.result-success .result-value').textContent = '1';
                resultModal.querySelector('.result-error .result-value').textContent = '0';
                resultModal.querySelector('.result-cost .result-value').textContent = totalCost.toFixed(3);
                
                // สร้าง HTML สำหรับตารางผลลัพธ์
                const resultDetails = resultModal.querySelector('#result-details');
                resultDetails.innerHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>นักเรียน</th>
                                <th>สถานะ</th>
                                <th>จำนวนข้อความ</th>
                                <th>ค่าใช้จ่าย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>${studentName}</td>
                                <td><span class="badge badge-success">ส่งสำเร็จ</span></td>
                                <td>${messageCount}</td>
                                <td>${totalCost.toFixed(3)} บาท</td>
                            </tr>
                        </tbody>
                    </table>
                `;
                
                // แสดงโมดัลผลลัพธ์
                showModal('result-modal');
                
                // แสดงข้อความส่งสำเร็จ
                showToast('ส่งข้อความเรียบร้อยแล้ว', 'success');
            }, 1000);
        }
        
        // รีเซ็ตฟอร์มส่งข้อความรายบุคคล
        function resetForm() {
            // รีเซ็ตเทมเพลต
            document.querySelector('#individual-tab .template-btn.active').classList.remove('active');
            document.querySelector('#individual-tab .template-btn:first-child').classList.add('active');
            
            // รีเซ็ตข้อความ
            document.getElementById('message-text').value = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ปัจจุบันเข้าร่วม {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            
            // รีเซ็ตตัวเลือกแนบกราฟและลิงก์
            document.getElementById('include-chart').checked = true;
            document.getElementById('include-link').checked = true;
            
            // อัปเดตตัวอย่างและค่าใช้จ่าย
            updatePreviewContent();
            updateMessageCost();
            
            showToast('รีเซ็ตฟอร์มเรียบร้อยแล้ว', 'info');
        }
        
        // กรองผู้รับข้อความกลุ่ม
        function filterGroupRecipients() {
            const classLevel = document.getElementById('group-class-level').value;
            const classGroup = document.getElementById('group-class-group').value;
            const attendanceStatus = document.getElementById('group-attendance-status').value;
            const attendanceRate = document.getElementById('group-attendance-rate').value;
            
            // แสดงข้อความกำลังกรอง
            showToast('กำลังกรองข้อมูลผู้รับข้อความ...', 'info');
            
            // จำลองการกรองข้อมูล (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // สร้างตัวอย่างข้อมูลผู้รับ
                const recipientsList = document.getElementById('recipients-list');
                
                // สร้าง HTML สำหรับรายการผู้รับ
                let html = '';
                
                if (attendanceStatus === 'เสี่ยงตกกิจกรรม' || attendanceStatus === '') {
                    html += `
                        <div class="recipient-item">
                            <input type="checkbox" class="recipient-checkbox" value="1" checked>
                            <div class="recipient-info">
                                <div class="student-info">
                                    <div class="student-avatar">ธ</div>
                                    <div class="student-details">
                                        <div class="student-name">นายธนกฤต สุขใจ</div>
                                        <div>ปวช.1/1 | 26/40 (65%) | <span class="badge badge-danger">เสี่ยงตกกิจกรรม</span></div>
                                        <div class="student-id">ผู้ปกครอง: นางสมหญิง สุขใจ (แม่)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                if (attendanceRate === 'น้อยกว่า 70%' || attendanceRate === '') {
                    html += `
                        <div class="recipient-item">
                            <input type="checkbox" class="recipient-checkbox" value="2" checked>
                            <div class="recipient-info">
                                <div class="student-info">
                                    <div class="student-avatar">ว</div>
                                    <div class="student-details">
                                        <div class="student-name">นางสาววรรณา มั่นคง</div>
                                        <div>ปวช.1/1 | 30/40 (75%) | <span class="badge badge-warning">ต้องระวัง</span></div>
                                        <div class="student-id">ผู้ปกครอง: นายวิทยา มั่นคง (พ่อ)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                recipientsList.innerHTML = html || '<div class="text-center" style="padding: 20px;">ไม่พบข้อมูลนักเรียนตามเงื่อนไขที่กำหนด</div>';
                
                // อัปเดตจำนวนผู้รับ
                const count = document.querySelectorAll('.recipient-checkbox').length;
                document.getElementById('recipient-count').textContent = count;
                document.getElementById('selected-count').textContent = document.querySelectorAll('.recipient-checkbox:checked').length;
                
                // เพิ่ม event listener ให้กับ checkbox ใหม่
                document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        updateSelectedCount();
                        updateGroupMessageCost();
                    });
                });
                
                // อัปเดตค่าใช้จ่าย
                updateSelectedCount();
                updateGroupMessageCost();
                
                showToast(`พบนักเรียนตามเงื่อนไขทั้งหมด ${count} คน`, 'success');
            }, 500);
        }
        
        // เลือกผู้รับทั้งหมด
        function selectAllRecipients() {
            document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
            updateGroupMessageCost();
            showToast('เลือกผู้รับทั้งหมดเรียบร้อยแล้ว', 'info');
        }
        
        // ยกเลิกการเลือกผู้รับทั้งหมด
        function clearAllRecipients() {
            document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
            updateGroupMessageCost();
            showToast('ยกเลิกการเลือกผู้รับทั้งหมดเรียบร้อยแล้ว', 'info');
        }
        
        // อัปเดตจำนวนผู้รับที่เลือก
        function updateSelectedCount() {
            const count = document.querySelectorAll('.recipient-checkbox:checked').length;
            document.getElementById('selected-count').textContent = count;
            
            // อัปเดตข้อความปุ่มส่ง
            const sendButton = document.querySelector('#group-tab .btn-line');
            sendButton.innerHTML = `<i class="fas fa-paper-plane"></i> ส่งข้อความ (${count} ราย)`;
        }
        
        // ส่งข้อความกลุ่ม
        function sendGroupMessage() {
            // ตรวจสอบว่ามีการเลือกผู้รับหรือไม่
            const selectedRecipients = document.querySelectorAll('.recipient-checkbox:checked');
            
            if (selectedRecipients.length === 0) {
                showToast('กรุณาเลือกผู้รับข้อความอย่างน้อย 1 คน', 'warning');
                return;
            }
            
            // ตรวจสอบว่ามีข้อความหรือไม่
            const messageText = document.getElementById('group-message-text').value.trim();
            if (!messageText) {
                showToast('กรุณากรอกข้อความก่อนส่ง', 'warning');
                return;
            }
            
            // แสดงข้อความกำลังส่ง
            showToast('กำลังส่งข้อความ...', 'info');
            
            // จำลองการส่งข้อความ (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // แสดงโมดัลผลลัพธ์การส่ง
                const resultModal = document.getElementById('result-modal');
                
                const includeChart = document.getElementById('group-include-chart').checked;
                const includeLink = document.getElementById('group-include-link').checked;
                
                const messageCost = 0.075;
                const chartCost = includeChart ? 0.150 : 0;
                const linkCost = includeLink ? 0.075 : 0;
                const messageCount = 1 + (includeChart ? 1 : 0) + (includeLink ? 1 : 0);
                
                const totalCost = selectedRecipients.length * (messageCost + chartCost + linkCost);
                
                // อัปเดตข้อมูลในโมดัลผลลัพธ์
                resultModal.querySelector('.result-success .result-value').textContent = selectedRecipients.length;
                resultModal.querySelector('.result-error .result-value').textContent = '0';
                resultModal.querySelector('.result-cost .result-value').textContent = totalCost.toFixed(3);
                
                // สร้าง HTML สำหรับตารางผลลัพธ์
                const resultDetails = resultModal.querySelector('#result-details');
                let tableHTML = `
                    <table>
                        <thead>
                            <tr>
                                <th>นักเรียน</th>
                                <th>สถานะ</th>
                                <th>จำนวนข้อความ</th>
                                <th>ค่าใช้จ่าย</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                selectedRecipients.forEach(recipient => {
                    const studentName = recipient.closest('.recipient-item').querySelector('.student-name').textContent;
                    const recipientCost = messageCost + chartCost + linkCost;
                    
                    tableHTML += `
                        <tr>
                            <td>${studentName}</td>
                            <td><span class="badge badge-success">ส่งสำเร็จ</span></td>
                            <td>${messageCount}</td>
                            <td>${recipientCost.toFixed(3)} บาท</td>
                        </tr>
                    `;
                });
                
                tableHTML += `
                        </tbody>
                    </table>
                `;
                
                resultDetails.innerHTML = tableHTML;
                
                // แสดงโมดัลผลลัพธ์
                showModal('result-modal');
                
                // แสดงข้อความส่งสำเร็จ
                showToast(`ส่งข้อความเรียบร้อยแล้ว ${selectedRecipients.length} รายการ`, 'success');
            }, 1000);
        }
        
        // รีเซ็ตฟอร์มส่งข้อความกลุ่ม
        function resetGroupForm() {
            // รีเซ็ตเทมเพลต
            document.querySelector('#group-tab .template-btn.active').classList.remove('active');
            document.querySelector('#group-tab .template-btn:first-child').classList.add('active');
            
            // รีเซ็ตข้อความ
            document.getElementById('group-message-text').value = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียนชั้น {{ชั้นเรียน}} ประจำเดือนนี้

กรุณาติดตามการเข้าแถวของบุตรหลานท่านได้ผ่านระบบแจ้งเตือนของวิทยาลัย หากมีข้อสงสัยกรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}}

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            
            // รีเซ็ตตัวเลือกแนบกราฟและลิงก์
            document.getElementById('group-include-chart').checked = true;
            document.getElementById('group-include-link').checked = true;
            
            // อัปเดตตัวอย่างและค่าใช้จ่าย
            updateGroupPreviewContent();
            updateGroupMessageCost();
            
            showToast('รีเซ็ตฟอร์มเรียบร้อยแล้ว', 'info');
        }
        
        // ค้นหาประวัติการส่งข้อความ
        function searchHistory() {
            const studentName = document.getElementById('history-student-name').value;
            const messageType = document.getElementById('history-message-type').value;
            const status = document.getElementById('history-status').value;
            
            // แสดงข้อความกำลังค้นหา
            showToast('กำลังค้นหาประวัติการส่งข้อความ...', 'info');
            
            // จำลองการค้นหา (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // นี่เป็นตัวอย่างข้อมูลจำลอง
                showToast('พบประวัติการส่งข้อความ 3 รายการ', 'success');
            }, 500);
        }
        
        // แสดงข้อความในโมดัล
        function viewMessage(messageId) {
            // จำลองข้อความตามรหัส
            let messageText = '';
            let messageTitle = '';
            
            if (messageId === 1) {
                messageTitle = 'ข้อความแจ้งเตือนการเข้าแถว';
                messageText = `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 ปัจจุบันเข้าร่วม 26 วัน จากทั้งหมด 40 วัน (65%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            } else if (messageId === 2) {
                messageTitle = 'ข้อความแจ้งเตือนความเสี่ยง';
                messageText = `เรียน ผู้ปกครองของ นางสาววรรณา มั่นคง

ทางวิทยาลัยขอแจ้งว่า นางสาววรรณา มั่นคง นักเรียนชั้น ปวช.1/1 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 30 วัน จากทั้งหมด 40 วัน (75%)

กรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            } else {
                messageTitle = 'ข้อความแจ้งเตือนการเข้าแถว';
                messageText = `เรียน ผู้ปกครองของ นายสมชาย ใจดี

ทางวิทยาลัยขอแจ้งความคืบหน้าเกี่ยวกับการเข้าแถวของนักเรียน นายสมชาย ใจดี นักเรียนชั้น ปวช.1/1 ปัจจุบันเข้าร่วม 35 วัน จากทั้งหมด 40 วัน (87.5%)

จึงเรียนมาเพื่อทราบ

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            }
            
            // แทนที่ขึ้นบรรทัดใหม่ด้วย <br>
            messageText = messageText.replace(/\n/g, '<br>');
            
            // สร้างโมดัลแสดงข้อความ
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = 'view-message-modal';
            
            modal.innerHTML = `
                <div class="modal-content">
                    <button class="modal-close" onclick="closeModal('view-message-modal')">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-header">
                        <h3 class="modal-title">${messageTitle}</h3>
                    </div>
                    <div class="line-preview" style="max-width: 100%;">
                        <div class="line-header"></div>
                        <div class="line-body">
                            <strong>LINE Official Account: SADD-Prasat</strong>
                            <p style="margin-top: 10px;">${messageText}</p>
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top: 20px;">
                        <button class="btn btn-secondary" onclick="closeModal('view-message-modal')">
                            <i class="fas fa-times"></i> ปิด
                        </button>
                    </div>
                </div>
            `;
            
            // เพิ่มโมดัลไปยัง body
            document.body.appendChild(modal);
            
            // แสดงโมดัล
            showModal('view-message-modal');
        }
        
        // ส่งข้อความซ้ำ
        function resendMessage(messageId) {
            // แสดงข้อความกำลังส่ง
            showToast('กำลังส่งข้อความซ้ำ...', 'info');
            
            // จำลองการส่งข้อความ (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                showToast('ส่งข้อความซ้ำเรียบร้อยแล้ว', 'success');
            }, 1000);
        }
        
        // ฟังก์ชันที่เกี่ยวกับการจัดการเทมเพลต
        function editTemplate(templateId) {
            // จำลองข้อมูลเทมเพลต
            let templateName = '';
            let templateType = '';
            let templateCategory = '';
            let templateContent = '';
            
            switch (templateId) {
                case 1:
                    templateName = 'แจ้งเตือนความเสี่ยงรายบุคคล';
                    templateType = 'individual';
                    templateCategory = 'attendance';
                    templateContent = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 2:
                    templateName = 'นัดประชุมผู้ปกครองกลุ่มเสี่ยง';
                    templateType = 'group';
                    templateCategory = 'meeting';
                    templateContent = `เรียน ท่านผู้ปกครองนักเรียนชั้น {{ชั้นเรียน}}

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มิถุนายน 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้

1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา
2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว
3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น {{ชั้นเรียน}} {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 3:
                    templateName = 'แจ้งเตือนฉุกเฉิน';
                    templateType = 'individual';
                    templateCategory = 'attendance';
                    templateContent = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)

ขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                default:
                    templateName = 'รายงานสรุปประจำเดือน';
                    templateType = 'individual';
                    templateCategory = 'attendance';
                    templateContent = `เรียน ผู้ปกครองของ {{ชื่อนักเรียน}}

สรุปข้อมูลการเข้าแถวของ {{ชื่อนักเรียน}} นักเรียนชั้น {{ชั้นเรียน}} ประจำเดือน{{เดือน}} {{ปี}}

จำนวนวันเข้าแถว: {{จำนวนวันเข้าแถว}} วัน จากทั้งหมด {{จำนวนวันทั้งหมด}} วัน ({{ร้อยละการเข้าแถว}}%)
จำนวนวันขาดแถว: {{จำนวนวันขาด}} วัน
สถานะ: {{สถานะการเข้าแถว}}

หมายเหตุ: นักเรียนต้องมีอัตราการเข้าแถวไม่ต่ำกว่า 80% จึงจะผ่านกิจกรรม

กรุณาติดต่อครูที่ปรึกษา {{ชื่อครูที่ปรึกษา}} โทร. {{เบอร์โทรครู}} เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            }
            
            // กำหนดค่าให้กับฟอร์ม
            document.getElementById('template-name').value = templateName;
            document.getElementById('template-type').value = templateType;
            document.getElementById('template-category').value = templateCategory;
            document.getElementById('template-content').value = templateContent;
            
            // แสดงโมดัล
            showModal('template-modal');
        }
        
        function previewTemplate(templateId) {
            // จำลองข้อมูลเทมเพลต
            let templateContent = '';
            
            switch (templateId) {
                case 1:
                    templateContent = `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ

ทางวิทยาลัยขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 มีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากปัจจุบันเข้าร่วมเพียง 26 วัน จากทั้งหมด 40 วัน (65%)

กรุณาติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 เพื่อหาแนวทางแก้ไขต่อไป

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                case 2:
                    templateContent = `เรียน ท่านผู้ปกครองนักเรียนชั้น ปวช.1/1

ทางวิทยาลัยขอแจ้งว่า บุตรหลานของท่านมีความเสี่ยงที่จะไม่ผ่านกิจกรรมเข้าแถว เนื่องจากมีจำนวนวันเข้าแถวต่ำกว่าเกณฑ์ที่กำหนด

ทางวิทยาลัยจะจัดประชุมผู้ปกครองกลุ่มเสี่ยงในวันศุกร์ที่ 21 มิถุนายน 2568 เวลา 15:00 น. ณ ห้องประชุม 2 อาคารอำนวยการ โดยมีวาระการประชุมดังนี้

1. ชี้แจงกฎระเบียบการเข้าแถวและผลกระทบต่อการจบการศึกษา
2. ร่วมหาแนวทางแก้ไขปัญหานักเรียนขาดแถว
3. ปรึกษาหารือเพื่อสนับสนุนนักเรียนในด้านอื่นๆ

กรุณาติดต่อครูที่ปรึกษาประจำชั้น ปวช.1/1 อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 หากมีข้อสงสัยหรือไม่สามารถเข้าร่วมประชุมตามวันเวลาดังกล่าวได้

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
                    break;
                default:
                    templateContent = `เรียน ผู้ปกครองของ นายธนกฤต สุขใจ

[ข้อความด่วน] ทางวิทยาลัยขอแจ้งว่า นายธนกฤต สุขใจ นักเรียนชั้น ปวช.1/1 มีความเสี่ยงสูงที่จะไม่ผ่านกิจกรรมเข้าแถว ซึ่งมีผลต่อการจบการศึกษา เนื่องจากปัจจุบันเข้าร่วมเพียง 26 วัน จากทั้งหมด 40 วัน (65%)

ขอความกรุณาท่านผู้ปกครองติดต่อครูที่ปรึกษา อ.ประสิทธิ์ ดีเลิศ โทร. 081-234-5678 ภายในวันนี้หรืออย่างช้าในวันพรุ่งนี้ เพื่อหาแนวทางแก้ไขอย่างเร่งด่วน

ด้วยความเคารพ
ฝ่ายกิจการนักเรียน
วิทยาลัยการอาชีพปราสาท`;
            }
            
            // แทนที่ขึ้นบรรทัดใหม่ด้วย <br>
            templateContent = templateContent.replace(/\n/g, '<br>');
            
            // แสดงตัวอย่างในโมดัล
            document.getElementById('modal-preview-content').innerHTML = `
                <strong>LINE Official Account: SADD-Prasat</strong>
                <p style="margin-top: 10px;">${templateContent}</p>
            `;
            
            // แสดงโมดัล
            showModal('preview-modal');
        }
        
        function deleteTemplate(templateId) {
            if (confirm('คุณต้องการลบเทมเพลตนี้ใช่หรือไม่?')) {
                // จำลองการลบเทมเพลต (ในสถานการณ์จริง ควรส่ง AJAX request)
                const row = document.querySelector(`#templates-tab table tbody tr:nth-child(${templateId})`);
                if (row) {
                    row.remove();
                }
                
                showToast('ลบเทมเพลตเรียบร้อยแล้ว', 'success');
            }
        }
        
        function saveTemplate() {
            const name = document.getElementById('template-name').value.trim();
            const type = document.getElementById('template-type').value;
            const category = document.getElementById('template-category').value;
            const content = document.getElementById('template-content').value.trim();
            
            if (!name) {
                showToast('กรุณากรอกชื่อเทมเพลต', 'warning');
                return;
            }
            
            if (!content) {
                showToast('กรุณากรอกเนื้อหาข้อความ', 'warning');
                return;
            }
            
            // แสดงข้อความกำลังบันทึก
            showToast('กำลังบันทึกเทมเพลต...', 'info');
            
            // จำลองการบันทึกเทมเพลต (ในสถานการณ์จริง ควรส่ง AJAX request)
            setTimeout(() => {
                // ปิดโมดัล
                closeModal('template-modal');
                
                // แสดงข้อความบันทึกสำเร็จ
                showToast('บันทึกเทมเพลตเรียบร้อยแล้ว', 'success');
                
                // ในสถานการณ์จริง ควรเพิ่มแถวใหม่ในตารางหรืออัปเดตแถวที่มีอยู่
            }, 500);
        }
        
        // แสดงข้อความแจ้งเตือน
        function showToast(message, type = 'info') {
            // ตรวจสอบว่ามี toast container หรือไม่
            let toastContainer = document.querySelector('.toast-container');
            
            if (!toastContainer) {
                // สร้าง toast container
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                toastContainer.style.position = 'fixed';
                toastContainer.style.bottom = '20px';
                toastContainer.style.right = '20px';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            
            // กำหนดสีตาม type
            let bgColor = '#28a745';
            let textColor = 'white';
            let icon = 'check-circle';
            
            switch (type) {
                case 'warning':
                    bgColor = '#ffc107';
                    textColor = '#212529';
                    icon = 'exclamation-triangle';
                    break;
                case 'danger':
                case 'error':
                    bgColor = '#dc3545';
                    textColor = 'white';
                    icon = 'exclamation-circle';
                    break;
                case 'info':
                    bgColor = '#17a2b8';
                    textColor = 'white';
                    icon = 'info-circle';
                    break;
            }
            
            // สร้าง toast element
            const toast = document.createElement('div');
            toast.style.backgroundColor = bgColor;
            toast.style.color = textColor;
            toast.style.padding = '12px 20px';
            toast.style.borderRadius = '8px';
            toast.style.marginBottom = '10px';
            toast.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.justifyContent = 'space-between';
            toast.style.minWidth = '300px';
            toast.style.maxWidth = '500px';
            toast.style.animation = 'fadeIn 0.3s, fadeOut 0.3s 3.7s';
            toast.style.fontWeight = '500';
            
            // สร้าง animation
            if (!document.getElementById('toast-animation')) {
                const style = document.createElement('style');
                style.id = 'toast-animation';
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; transform: translateY(20px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                    @keyframes fadeOut {
                        from { opacity: 1; transform: translateY(0); }
                        to { opacity: 0; transform: translateY(-20px); }
                    }
                `;
                document.head.appendChild(style);
            }
            
            // เพิ่มเนื้อหา
            toast.innerHTML = `
                <div style="display: flex; align-items: center;">
                    <i class="fas fa-${icon}" style="margin-right: 10px;"></i>
                    <span>${message}</span>
                </div>
                <button style="background: none; border: none; color: inherit; cursor: pointer; padding: 0; margin-left: 10px;">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            // เพิ่ม toast ไปยัง container
            toastContainer.appendChild(toast);
            
            // ตั้งค่าปุ่มปิด toast
            const closeButton = toast.querySelector('button');
            closeButton.addEventListener('click', () => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    toastContainer.removeChild(toast);
                }, 300);
            });
            
            // ตั้งค่าลบ toast โดยอัตโนมัติหลังจาก 4 วินาที
            setTimeout(() => {
                if (toastContainer.contains(toast)) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        if (toastContainer.contains(toast)) {
                            toastContainer.removeChild(toast);
                        }
                    }, 300);
                }
            }, 4000);
        }
        
        // เมื่อโหลดหน้าเว็บเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            // ตั้งค่าการแสดงตัวอย่างข้อความเมื่อพิมพ์
            document.getElementById('message-text').addEventListener('input', updatePreviewContent);
            document.getElementById('group-message-text').addEventListener('input', updateGroupPreviewContent);
            
            // ตั้งค่าการคำนวณค่าใช้จ่ายเมื่อเปลี่ยนตัวเลือก
            document.getElementById('include-chart').addEventListener('change', updateMessageCost);
            document.getElementById('include-link').addEventListener('change', updateMessageCost);
            document.getElementById('group-include-chart').addEventListener('change', updateGroupMessageCost);
            document.getElementById('group-include-link').addEventListener('change', updateGroupMessageCost);
            
            // ตั้งค่าการอัปเดตตัวอย่างเมื่อเปลี่ยนตัวเลือก
            document.getElementById('include-chart').addEventListener('change', updatePreviewContent);
            document.getElementById('include-link').addEventListener('change', updatePreviewContent);
            document.getElementById('group-include-chart').addEventListener('change', updateGroupPreviewContent);
            document.getElementById('group-include-link').addEventListener('change', updateGroupPreviewContent);
            
            // ตั้งค่าการตรวจสอบ checkbox ในรายการผู้รับ
            document.querySelectorAll('.recipient-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    updateSelectedCount();
                    updateGroupMessageCost();
                });
            });
            
            // ตั้งค่า event listener ให้กับ radio button นักเรียน
            document.querySelectorAll('input[name="student-select"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        selectStudent(this.value);
                    }
                });
            });
            
            // อัปเดตตัวอย่างและค่าใช้จ่ายครั้งแรก
            updatePreviewContent();
            updateMessageCost();
            updateGroupPreviewContent();
            updateGroupMessageCost();
            
            // อัปเดตจำนวนผู้รับที่เลือกครั้งแรก
            updateSelectedCount();
            
            // เตรียมข้อมูลสำหรับการทดสอบ
            if (document.querySelector('input[name="student-select"]:checked')) {
                const selectedRadio = document.querySelector('input[name="student-select"]:checked');
                selectStudent(selectedRadio.value);
            }
        });
    </script>
</body>
</html>