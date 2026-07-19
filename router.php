<?php
/**
 * Router for PHP built-in development server.
 * Usage: php -S localhost:8000 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$publicDir = __DIR__ . '/public';
$file = $publicDir . $uri;

// Serve static assets from /public (never require() binary files as PHP)
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    // Only PHP under public/ should be executed
    if ($ext === 'php') {
        require $file;
        return true;
    }

    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'json' => 'application/json',
        'txt'  => 'text/plain',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
    ];

    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string) filesize($file));
    if (in_array($ext, ['pdf', 'doc', 'docx'], true)) {
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
    }
    readfile($file);
    return true;
}

// Route PHP pages from /public
if ($uri !== '/' && is_dir($file)) {
    $indexFile = rtrim($file, '/') . '/index.php';
    if (file_exists($indexFile)) {
        require $indexFile;
        return true;
    }
}

$phpFile = $publicDir . ($uri === '/' ? '/index.php' : $uri);
if (file_exists($phpFile) && !is_dir($phpFile) && str_ends_with(strtolower($phpFile), '.php')) {
    require $phpFile;
    return true;
}

// Fallback to landing page
require $publicDir . '/index.php';
