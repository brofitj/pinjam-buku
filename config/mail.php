<?php

$env = require __DIR__ . '/env.php';

$mailUsername = (string)$env('MAIL_USERNAME', '');

return [
    'driver'       => (string)$env('MAIL_DRIVER', 'smtp'), // smtp | mail
    'host'         => (string)$env('MAIL_HOST', 'smtp.gmail.com'),
    'port'         => (int)$env('MAIL_PORT', 587),
    'encryption'   => (string)$env('MAIL_ENCRYPTION', 'tls'), // tls | ssl | none
    'username'     => $mailUsername,
    'password'     => (string)$env('MAIL_PASSWORD', ''),
    'timeout'      => (int)$env('MAIL_TIMEOUT', 30),
    'from_address' => (string)$env('MAIL_FROM_ADDRESS', $mailUsername !== '' ? $mailUsername : 'no-reply@example.com'),
    'from_name'    => (string)$env('MAIL_FROM_NAME', 'Library Management System'),
];
