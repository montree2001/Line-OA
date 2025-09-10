<?php
echo "<h2>📋 Error Log ล่าสุด</h2>";

// หา error log file
$possible_log_files = [
    'C:\xampp\apache\logs\error.log',
    'C:\xampp\php\logs\error.log', 
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    ini_get('error_log')
];

foreach ($possible_log_files as $log_file) {
    if ($log_file && file_exists($log_file)) {
        echo "<h3>📂 $log_file</h3>";
        
        // อ่าน 50 บรรทัดท้าย
        $lines = array_slice(file($log_file), -50);
        
        // กรองเฉพาะ lines ที่เกี่ยวข้องกับโปรเจกต์เรา
        $relevant_lines = array_filter($lines, function($line) {
            return strpos($line, 'DEBUG') !== false || 
                   strpos($line, 'attendance') !== false ||
                   strpos($line, 'student') !== false;
        });
        
        if (count($relevant_lines) > 0) {
            echo "<pre style='background: #f5f5f5; padding: 10px; font-size: 12px; max-height: 400px; overflow: auto;'>";
            foreach ($relevant_lines as $line) {
                echo htmlspecialchars($line);
            }
            echo "</pre>";
        } else {
            echo "<p>ไม่พบ log ที่เกี่ยวข้อง</p>";
        }
        break;
    }
}

if (!isset($log_file) || !file_exists($log_file)) {
    echo "<p>⚠️ ไม่พบไฟล์ error log</p>";
    echo "<p>ลองตรวจสอบใน:</p>";
    echo "<ul>";
    foreach ($possible_log_files as $file) {
        if ($file) {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}
?>

<style>
    body { font-family: 'Sarabun', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    h2, h3 { color: #2196f3; }
</style>