<?php

$env = require __DIR__ . '/env.php';

return [
    'app_name' => (string)$env('APP_NAME', 'Library Management System'),
    'env'      => (string)$env('APP_ENV', 'local'),
    'debug'    => filter_var($env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'timezone' => (string)$env('APP_TIMEZONE', 'Asia/Jakarta'),
    'log_path' => __DIR__ . '/../storage/logs/app.log',
];
