<?php

use App\Controllers\AuthController;
use App\Controllers\MemberController;

return [
    '/' => [AuthController::class, 'login'],
    '/login' => [AuthController::class, 'login'],
    '/logout' => [AuthController::class, 'logout'],

    '/dashboard' => dirname(__DIR__) . '/app/Views/admin/dashboard/index.php',
    '/member' => dirname(__DIR__) . '/app/Views/admin/member/index.php',
    '/member/add' => dirname(__DIR__) . '/app/Views/admin/member/add.php',
    '/member/edit' => dirname(__DIR__) . '/app/Views/admin/member/edit.php',

    '/api/members' => [MemberController::class, 'index'],
    '/api/members/show' => [MemberController::class, 'show'],
    '/api/members/create' => [MemberController::class, 'store'],
    '/api/members/update' => [MemberController::class, 'update'],
    '/api/members/delete' => [MemberController::class, 'delete'],
    '/member/avatar' => [MemberController::class, 'avatar'],
    
    '/member/dashboard' => dirname(__DIR__) . '/app/Views/member/dashboard/index.php',
];
