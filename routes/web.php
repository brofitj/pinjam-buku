<?php

use App\Controllers\AuthController;

return [
    '/' => [AuthController::class, 'login'],
    '/login' => [AuthController::class, 'login'],
    '/logout' => [AuthController::class, 'logout'],

    '/dashboard' => dirname(__DIR__) . '/app/Views/admin/dashboard.php',
    '/member/dashboard' => dirname(__DIR__) . '/app/Views/member/dashboard.php',
];