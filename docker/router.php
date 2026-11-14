<?php

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (str_contains($path, '..')) {
    http_response_code(400);

    return;
}

$public = dirname(__DIR__).'/public';
$file = $public.$path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require $public.'/index.php';
