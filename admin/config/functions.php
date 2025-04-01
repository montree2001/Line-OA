function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseDir = dirname($_SERVER['PHP_SELF']);
    // ตรวจสอบว่าอยู่ใน subdirectory หรือไม่
    if (basename($baseDir) === 'admin') {
        return $protocol . $host . $baseDir . '/';
    }
    return $protocol . $host . '/line-OA/admin/';
} 