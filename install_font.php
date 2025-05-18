<?php
/**
 * install_fonts.php - สคริปต์สำหรับติดตั้งฟอนต์ THSarabun
 * 
 * ส่วนหนึ่งของระบบน้องชูใจ AI - ดูแลผู้เรียน
 * วิทยาลัยการอาชีพปราสาท
 */

// เริ่ม session
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    die('คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// นำเข้าไฟล์ MPDF
require_once '../vendor/autoload.php';

// กำหนดเส้นทางสำหรับเก็บฟอนต์
$fontDir = dirname(__DIR__) . '/vendor/mpdf/mpdf/ttfonts/';

// ตรวจสอบว่ามีโฟลเดอร์หรือไม่
if (!file_exists($fontDir)) {
    if (!mkdir($fontDir, 0755, true)) {
        die('ไม่สามารถสร้างโฟลเดอร์สำหรับเก็บฟอนต์ได้');
    }
}

// ตรวจสอบการติดตั้งฟอนต์
$fontFiles = [
    'THSarabun.ttf' => false,
    'THSarabun Bold.ttf' => false,
    'THSarabun Italic.ttf' => false,
    'THSarabun BoldItalic.ttf' => false
];

foreach ($fontFiles as $fontFile => $installed) {
    if (file_exists($fontDir . $fontFile)) {
        $fontFiles[$fontFile] = true;
    }
}

// ตรวจสอบการส่งข้อมูลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_fonts'])) {
    // ตรวจสอบว่ามีไฟล์ที่อัปโหลดหรือไม่
    if (isset($_FILES['font_files']) && !empty($_FILES['font_files']['name'][0])) {
        $uploadedFiles = [];
        $error = false;
        
        // วนลูปตรวจสอบไฟล์ที่อัปโหลด
        foreach ($_FILES['font_files']['name'] as $key => $filename) {
            if (empty($filename)) continue;
            
            // ตรวจสอบประเภทไฟล์
            $fileInfo = pathinfo($filename);
            $extension = strtolower($fileInfo['extension'] ?? '');
            
            if ($extension !== 'ttf') {
                $error = true;
                $_SESSION['error_message'] = "ไฟล์ {$filename} ไม่ใช่ไฟล์ TTF";
                break;
            }
            
            // ตรวจสอบขนาดไฟล์
            if ($_FILES['font_files']['size'][$key] > 5 * 1024 * 1024) { // 5MB
                $error = true;
                $_SESSION['error_message'] = "ไฟล์ {$filename} มีขนาดใหญ่เกินไป";
                break;
            }
            
            // ลงทะเบียนไฟล์ที่จะอัปโหลด
            $uploadedFiles[] = [
                'tmp_name' => $_FILES['font_files']['tmp_name'][$key],
                'name' => $_FILES['font_files']['name'][$key]
            ];
        }
        
        // อัปโหลดไฟล์ TTF
        if (!$error) {
            foreach ($uploadedFiles as $file) {
                $fontPath = $fontDir . $file['name'];
                if (move_uploaded_file($file['tmp_name'], $fontPath)) {
                    $_SESSION['success_message'] = "อัปโหลดไฟล์ {$file['name']} สำเร็จ";
                    
                    // อัปเดตสถานะการติดตั้ง
                    foreach ($fontFiles as $fontFile => $installed) {
                        if ($fontFile === $file['name']) {
                            $fontFiles[$fontFile] = true;
                        }
                    }
                } else {
                    $_SESSION['error_message'] = "ไม่สามารถอัปโหลดไฟล์ {$file['name']}";
                }
            }
        }
    } elseif (isset($_POST['use_default_fonts']) && $_POST['use_default_fonts'] === 'yes') {
        // ใช้ฟอนต์จากเว็บไซต์
        $defaultFonts = [
            'https://github.com/lazywasabi/thai-web-fonts/raw/master/fonts/THSarabunNew/THSarabunNew.ttf' => 'THSarabun.ttf',
            'https://github.com/lazywasabi/thai-web-fonts/raw/master/fonts/THSarabunNew/THSarabunNew-Bold.ttf' => 'THSarabun Bold.ttf',
            'https://github.com/lazywasabi/thai-web-fonts/raw/master/fonts/THSarabunNew/THSarabunNew-Italic.ttf' => 'THSarabun Italic.ttf',
            'https://github.com/lazywasabi/thai-web-fonts/raw/master/fonts/THSarabunNew/THSarabunNew-BoldItalic.ttf' => 'THSarabun BoldItalic.ttf'
        ];
        
        $success = true;
        foreach ($defaultFonts as $url => $fontFile) {
            $fontContent = @file_get_contents($url);
            if ($fontContent === false) {
                $success = false;
                $_SESSION['error_message'] = "ไม่สามารถดาวน์โหลดไฟล์ฟอนต์จาก {$url}";
                break;
            }
            
            $result = @file_put_contents($fontDir . $fontFile, $fontContent);
            if ($result === false) {
                $success = false;
                $_SESSION['error_message'] = "ไม่สามารถบันทึกไฟล์ฟอนต์ {$fontFile}";
                break;
            }
            
            // อัปเดตสถานะการติดตั้ง
            $fontFiles[$fontFile] = true;
        }
        
        if ($success) {
            $_SESSION['success_message'] = "ติดตั้งฟอนต์ THSarabun เรียบร้อยแล้ว";
        }
    } else {
        $_SESSION['error_message'] = "กรุณาเลือกไฟล์ฟอนต์ที่ต้องการอัปโหลด";
    }
    
    // ตรวจสอบสถานะการติดตั้งอีกครั้ง
    foreach ($fontFiles as $fontFile => $installed) {
        if (file_exists($fontDir . $fontFile)) {
            $fontFiles[$fontFile] = true;
        }
    }
}

// ตรวจสอบการลบฟอนต์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_font'])) {
    $fontToRemove = $_POST['font_name'] ?? '';
    
    if (!empty($fontToRemove) && file_exists($fontDir . $fontToRemove)) {
        if (unlink($fontDir . $fontToRemove)) {
            $_SESSION['success_message'] = "ลบไฟล์ฟอนต์ {$fontToRemove} เรียบร้อยแล้ว";
            $fontFiles[$fontToRemove] = false;
        } else {
            $_SESSION['error_message'] = "ไม่สามารถลบไฟล์ฟอนต์ {$fontToRemove}";
        }
    }
}

// ตรวจสอบการอัปเดต mpdf_config.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mpdf_config'])) {
    $configFile = dirname(__DIR__) . '/vendor/mpdf/mpdf/config.php';
    
    // สร้างไฟล์ config ถ้ายังไม่มี
    if (!file_exists($configFile)) {
        $configContent = <<<'EOT'
<?php

// Define a default Monospaced font
$this->fontdata['freeserif'] = [
    'R' => 'FreeSerif.ttf',
    'B' => 'FreeSerifBold.ttf',
    'I' => 'FreeSerifItalic.ttf',
    'BI' => 'FreeSerifBoldItalic.ttf',
];

// Add Thai font
$this->fontdata['thsarabun'] = [
    'R' => 'THSarabun.ttf',
    'B' => 'THSarabun Bold.ttf',
    'I' => 'THSarabun Italic.ttf',
    'BI' => 'THSarabun BoldItalic.ttf',
];

$this->fontdata['norasi'] = [
    'R' => 'Norasi.ttf',
    'B' => 'Norasi Bold.ttf',
    'I' => 'Norasi Italic.ttf',
    'BI' => 'Norasi Bold Italic.ttf',
];

// Set default font
$this->SetFont('thsarabun');
EOT;
        
        if (file_put_contents($configFile, $configContent) === false) {
            $_SESSION['error_message'] = "ไม่สามารถสร้างไฟล์ config.php";
        } else {
            $_SESSION['success_message'] = "สร้างไฟล์ config.php เรียบร้อยแล้ว";
        }
    } else {
        // อ่านและอัปเดตไฟล์ config
        $configContent = file_get_contents($configFile);
        
        // ตรวจสอบว่ามีการกำหนดฟอนต์ thsarabun หรือไม่
        if (strpos($configContent, 'thsarabun') === false) {
            $newConfigContent = str_replace('<?php', "<?php\n\n// Add Thai font\n\$this->fontdata['thsarabun'] = [\n    'R' => 'THSarabun.ttf',\n    'B' => 'THSarabun Bold.ttf',\n    'I' => 'THSarabun Italic.ttf',\n    'BI' => 'THSarabun BoldItalic.ttf',\n];\n", $configContent);
            
            if (file_put_contents($configFile, $newConfigContent) === false) {
                $_SESSION['error_message'] = "ไม่สามารถอัปเดตไฟล์ config.php";
            } else {
                $_SESSION['success_message'] = "อัปเดตไฟล์ config.php เรียบร้อยแล้ว";
            }
        } else {
            $_SESSION['info_message'] = "ไฟล์ config.php มีการกำหนดฟอนต์ thsarabun อยู่แล้ว";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตั้งฟอนต์ THSarabun</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/material-design-icons/3.0.1/iconfont/material-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin-bottom: 30px;
            color: #28a745;
        }
        .font-status {
            margin-bottom: 30px;
        }
        .font-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .font-status-icon {
            margin-right: 15px;
            font-size: 24px;
        }
        .font-status-icon.installed {
            color: #28a745;
        }
        .font-status-icon.not-installed {
            color: #dc3545;
        }
        .font-name {
            flex-grow: 1;
        }
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .back-button {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="material-icons">font_download</span>
            ติดตั้งฟอนต์ THSarabun
        </h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info_message'])): ?>
            <div class="alert alert-info">
                <?php echo $_SESSION['info_message']; unset($_SESSION['info_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="font-status">
            <h3>สถานะการติดตั้งฟอนต์</h3>
            <?php foreach ($fontFiles as $fontFile => $installed): ?>
                <div class="font-item">
                    <span class="font-status-icon <?php echo $installed ? 'installed' : 'not-installed'; ?> material-icons">
                        <?php echo $installed ? 'check_circle' : 'cancel'; ?>
                    </span>
                    <div class="font-name"><?php echo $fontFile; ?></div>
                    <?php if ($installed): ?>
                        <form method="post" onsubmit="return confirm('คุณต้องการลบฟอนต์นี้ใช่หรือไม่?');">
                            <input type="hidden" name="font_name" value="<?php echo $fontFile; ?>">
                            <button type="submit" name="remove_font" class="btn btn-sm btn-danger">
                                <span class="material-icons">delete</span> ลบ
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="form-section">
            <h3>อัปโหลดไฟล์ฟอนต์</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="font_files" class="form-label">เลือกไฟล์ TTF (สามารถเลือกได้หลายไฟล์)</label>
                    <input type="file" class="form-control" id="font_files" name="font_files[]" accept=".ttf" multiple>
                    <div class="form-text">
                        อัปโหลดไฟล์ฟอนต์ THSarabun ที่ต้องการใช้งาน
                    </div>
                </div>
                <button type="submit" name="install_fonts" class="btn btn-primary">
                    <span class="material-icons">upload</span> อัปโหลดฟอนต์
                </button>
            </form>
        </div>
        
        <div class="form-section">
            <h3>ติดตั้งฟอนต์ THSarabun อัตโนมัติ</h3>
            <p>ระบบจะดาวน์โหลดฟอนต์ THSarabun จากอินเทอร์เน็ตและติดตั้งโดยอัตโนมัติ</p>
            <form method="post">
                <input type="hidden" name="use_default_fonts" value="yes">
                <button type="submit" name="install_fonts" class="btn btn-success">
                    <span class="material-icons">cloud_download</span> ติดตั้งฟอนต์อัตโนมัติ
                </button>
            </form>
        </div>
        
        <div class="form-section">
            <h3>อัปเดตไฟล์คอนฟิก mPDF</h3>
            <p>อัปเดตไฟล์คอนฟิกของ mPDF เพื่อให้รองรับฟอนต์ THSarabun</p>
            <form method="post">
                <button type="submit" name="update_mpdf_config" class="btn btn-warning">
                    <span class="material-icons">settings</span> อัปเดตไฟล์คอนฟิก
                </button>
            </form>
        </div>
        
        <div class="back-button">
            <a href="report_settings.php" class="btn btn-secondary">
                <span class="material-icons">arrow_back</span> กลับไปยังหน้าตั้งค่ารายงาน
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>