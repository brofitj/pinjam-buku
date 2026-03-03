<?php

use App\Controllers\AuthController;
use App\Controllers\MemberController;
use App\Controllers\UserController;

return [
    '/' => [AuthController::class, 'login'],
    '/login' => [AuthController::class, 'login'],
    '/logout' => [AuthController::class, 'logout'],

    '/dashboard' => dirname(__DIR__) . '/app/Views/admin/dashboard/index.php',
    '/member' => dirname(__DIR__) . '/app/Views/admin/member/index.php',
    '/member/add' => dirname(__DIR__) . '/app/Views/admin/member/add.php',
    '/member/edit' => dirname(__DIR__) . '/app/Views/admin/member/edit.php',
    '/user' => dirname(__DIR__) . '/app/Views/admin/user/index.php',
    '/user/add' => dirname(__DIR__) . '/app/Views/admin/user/add.php',
    '/user/edit' => dirname(__DIR__) . '/app/Views/admin/user/edit.php',

    '/api/members' => [MemberController::class, 'index'],
    '/api/members/show' => [MemberController::class, 'show'],
    '/api/members/create' => [MemberController::class, 'store'],
    '/api/members/update' => [MemberController::class, 'update'],
    '/api/members/delete' => [MemberController::class, 'delete'],
    '/api/users' => [UserController::class, 'index'],
    '/api/users/show' => [UserController::class, 'show'],
    '/api/users/create' => [UserController::class, 'store'],
    '/api/users/update' => [UserController::class, 'update'],
    '/api/users/delete' => [UserController::class, 'delete'],
    '/user/avatar' => [UserController::class, 'avatar'],
    '/member/avatar' => [MemberController::class, 'avatar'],
    
    '/member/dashboard' => dirname(__DIR__) . '/app/Views/member/dashboard/index.php',
];
