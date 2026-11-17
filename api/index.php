<?php

// Prevent Vercel serverless proxy (/api/index.php) from causing Symfony/Laravel
// to strip "/api" as a base path prefix on API endpoints like /api/v1/auth/login.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

// Create writable /tmp directories for Vercel serverless read-only filesystem
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    $dirs = [
        '/tmp/storage/framework/views',
        '/tmp/storage/framework/cache/data',
        '/tmp/storage/framework/sessions',
        '/tmp/storage/logs',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

require __DIR__ . '/../public/index.php';

