<?php
/**
 * Application configuration — override via environment variables or .env file.
 */

if (file_exists(__DIR__ . '/../.env')) {
    foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

define('DB_PATH', getenv('DB_PATH') ?: __DIR__ . '/../database/jagiree.sqlite');

define('APP_NAME', 'Jagiree');
define('SESSION_NAME', 'jagiree_session');

define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYER', 'employer');
define('ROLE_SEEKER', 'seeker');

define('DASHBOARD_PATHS', [
    ROLE_ADMIN => '/admin/',
    ROLE_EMPLOYER => '/employer/',
    ROLE_SEEKER => '/seeker/',
]);
