<?php

/**
 * Router for PHP's built-in web server, used for local preview.
 *
 * Run with the public directory as the document root:
 *
 *     php -S 0.0.0.0:8000 -t public public/router.php
 *
 * Requests that match a real file inside /public (Vite bundles, fonts, icons)
 * are returned by the built-in server untouched; everything else is handled by
 * the Laravel front controller.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if ($uri !== '/' && is_file(__DIR__.$uri)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/index.php';

require_once __DIR__.'/index.php';
