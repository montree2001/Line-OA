<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พิมพ์ QR Code นักเรียน - วิทยาลัยการอาชีพปราสาท</title>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .print-header {
            background-color: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .print-actions {
            display: flex;
            gap: 10px;
        }
        .print-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .print-title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .btn-primary {
            background-color: #06c755;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .qr-container {
            display: flex;
            flex-wrap: wrap;
            padding: 20px;
            justify-content: flex-start;
        }
        .qr-card {
            width: 300px;
            height: 450px;
            margin: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
        }
        .qr-header {
            background: linear-gradient(135deg, #06c755 0%, #04a745 100%);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .qr-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            padding: 5px;
        }
        .qr-title {
            flex: 1;
        }
        .qr-title h5 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .qr-title p {
            margin: 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .qr-body {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-body img {
            width: 180px;
            height: 180px;
            margin-bottom: 15px;
        }
        .student-details {
            text-align: center;
            margin-top: 15px;
        }
        .student-name {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 5px 0;
        }
        .student-id {
            font-size: 16px;
            color: #555;
            margin: 0 0 5px 0;
        }
        .student-class {
            font-size: 14px;
            color: #777;
            margin: 0;
        }
        .qr-footer {
            background: #f8f9fa;
            padding: 10px 15px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 14px;
            color: #555;
        }
        .qr-footer p {
            margin: 5px 0;
        }
        .system-name {
            font-style: italic;
            color: #777;
            font-size: 12px;
        }
        @media print {
            .print-header {
                display: none;
            }
            body {
                background: white;
            }
            .qr-card {
                break-inside: avoid;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #eee;
            }
            .qr-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <div class="print-title">
            <i class="material-icons">qr_code_2</i>
            <h1>พิมพ์ QR Code นักเรียน</h1>
        </div>
        <div class="print-actions">
            <button class="btn btn-secondary" onclick="window.location.href='print_qr_code.php'">
                <i class="material-icons">arrow_back</i> กลับ
            </button>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="material-icons">print</i> พิมพ์
            </button>
        </div>
    </div>
    
    <div class="qr-container">
        <?php foreach ($students as $student): ?>
            <?php 
                // แปลง QR Code data จาก JSON เป็น array
                $qr_data = json_decode($student['qr_code_data'], true);
                $expire_date = new DateTime($student['valid_until']);
                $expire_date_display = $expire_date->format('d/m/Y H:i');
                
                // สร้าง QR Code
                $qr = new QRCode();
                $qr->addData(json_encode($qr_data));
                $qr->make();
            ?>
            <div class="qr-card">
                <div class="qr-header">
                    <img src="../assets/images/school_logo.png" alt="Logo" class="qr-logo">
                    <div class="qr-title">
                        <h5>วิทยาลัยการอาชีพปราสาท</h5>
                        <p>QR Code สำหรับเช็คชื่อเข้าแถว</p>
                    </div>
                </div>
                <div class="qr-body">
                    <img src="<?php echo $qr->getBase64(); ?>" alt="QR Code">
                    <div class="student-details">
                        <p class="student-name"><?php echo htmlspecialchars($student['title'] . $student['first_name'] . ' ' . $student['last_name']); ?></p>
                        <p class="student-id">รหัสนักเรียน: <?php echo htmlspecialchars($student['student_code']); ?></p>
                        <p class="student-class"><?php echo htmlspecialchars($student['level'] . ' ' . $student['department_name'] . ' กลุ่ม ' . $student['group_number']); ?></p>
                    </div>
                </div>
                <div class="qr-footer">
                    <p>วันหมดอายุ: <?php echo $expire_date_display; ?></p>
       
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // เมื่อโหลดหน้าเสร็จ ให้แสดงหน้าต่างการพิมพ์อัตโนมัติ
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>