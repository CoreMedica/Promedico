<?php

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

if (
    str_contains($host, 'localhost') ||
    str_contains($host, '127.0.0.1') ||
    str_contains($host, '.test')
) {
    return require __DIR__ . '/database.local.php';
}

return require __DIR__ . '/database.live.php';
