<?php
/**
 * Router for PHP built-in server
 * Usage: php -S localhost:8000 router.php
 *
 * This handles static files AND PHP routing correctly.
 */

$uri = $_SERVER['REQUEST_URI'];

// Strip query string
$path = parse_url($uri, PHP_URL_PATH);

// Build full file path
$file = __DIR__ . $path;

// Serve static files directly (CSS, JS, images, model files)
if (is_file($file)) {
    // Set correct MIME types
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = array(
        'css'  => 'text/css',
        'js'   => 'text/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
        'map'  => 'application/json',
    );

    if (isset($mime[$ext])) {
        header('Content-Type: ' . $mime[$ext]);
    } elseif ($ext === '') {
        // Extensionless binary files (model shards)
        if (strpos($file, 'shard') !== false || strpos($file, '.bin') !== false) {
            header('Content-Type: application/octet-stream');
        }
    }

    return false; // Let PHP serve the file
}

// Default to index.php if directory
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (is_file($index)) {
        require $index;
        return true;
    }
}

// All other requests — serve the PHP file
// Set working directory to the file's directory for relative includes
if (is_file(__DIR__ . $path)) {
    chdir(dirname(__DIR__ . $path));
    require __DIR__ . $path;
    return true;
}
return false;

// v2 - chdir fix applied
