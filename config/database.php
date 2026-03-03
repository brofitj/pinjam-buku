<?php

$env = require __DIR__ . '/env.php';

return [
    'driver'   => (string)$env('DB_DRIVER', 'mysql'),
    'host'     => (string)$env('DB_HOST', '127.0.0.1'),
    'port'     => (int)$env('DB_PORT', 3306),
    'database' => (string)$env('DB_DATABASE', 'pinjam_buku'),
    'username' => (string)$env('DB_USERNAME', 'root'),
    'password' => (string)$env('DB_PASSWORD', ''),
    'charset'  => (string)$env('DB_CHARSET', 'utf8mb4'),

    'options' => [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
