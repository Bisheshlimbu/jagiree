<?php
/**
 * Resolve asset URLs relative to the current script path.
 * Works with php -S router.php, php -S -t public, and nested routes like /seeker/chat.php
 */
function appRootPath(): string
{
    return dirname(__DIR__);
}

function appPublicPath(string $suffix = ''): string
{
    $base = appRootPath() . '/public';

    return $suffix !== '' ? $base . '/' . ltrim($suffix, '/') : $base;
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $dir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');

    if ($dir === '' || $dir === '.') {
        return '/' . $path;
    }

    $depth = count(array_filter(explode('/', $dir)));
    return str_repeat('../', $depth) . $path;
}
