<?php

use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;

// Browser fixtures must use the built assets, independent of a developer's Vite server.
if (getenv('APP_ENV') !== 'testing') {
    http_response_code(403);
    exit;
}
$public = realpath(__DIR__.'/../../public');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath($public.'/'.ltrim(rawurldecode($path), '/'));
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
define('LARAVEL_START', microtime(true));
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->booted(fn () => app(Vite::class)->useHotFile(storage_path('framework/testing/no-dev-server.hot')));
$app->handleRequest(Request::capture());
