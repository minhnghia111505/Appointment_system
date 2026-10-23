<?php
/**
 * PHP Built-in Web Server Router for Easy!Appointments
 */
$filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (is_file($filePath)) {
    return false; // Serve static file directly
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require_once __DIR__ . '/index.php';
