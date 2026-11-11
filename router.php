<?php
// Tách lấy đường dẫn URL (loại bỏ tham số query string dạng ?TS...)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rawurldecode($uri);

// Chuyển đường dẫn phù hợp với file system trên Windows
$filePath = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// Kiểm tra nếu là file tĩnh (CSS, JS, Image, Font...) thực sự tồn tại (không đọc file .php)
if ($uri !== '/' && $ext !== 'php' && file_exists($filePath) && !is_dir($filePath)) {
    $mimeTypes = [
        'css'   => 'text/css',
        'js'    => 'application/javascript',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'eot'   => 'application/vnd.ms-fontobject',
        'ico'   => 'image/x-icon',
        'map'   => 'application/json',
        'json'  => 'application/json',
        'webp'  => 'image/webp',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    
    readfile($filePath);
    exit;
}

// Nếu không phải file tĩnh, điều hướng về index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . DIRECTORY_SEPARATOR . 'index.php';