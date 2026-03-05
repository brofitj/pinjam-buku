<?php

use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Controllers\DashboardController;
use App\Controllers\Member\TransactionController as MemberTransactionController;
use App\Controllers\MemberController;
use App\Controllers\TransactionController;
use App\Controllers\UserController;

return [
    '/' => [AuthController::class, 'login'],
    '/login' => [AuthController::class, 'login'],
    '/register' => [AuthController::class, 'register'],
    '/logout' => [AuthController::class, 'logout'],

    '/dashboard' => dirname(__DIR__) . '/app/Views/admin/dashboard/index.php',
    '/transaction' => dirname(__DIR__) . '/app/Views/admin/transaction/index.php',
    '/transaction/detail' => dirname(__DIR__) . '/app/Views/admin/transaction/detail.php',
    '/book' => dirname(__DIR__) . '/app/Views/admin/book/index.php',
    '/book/add' => dirname(__DIR__) . '/app/Views/admin/book/add.php',
    '/book/edit' => dirname(__DIR__) . '/app/Views/admin/book/edit.php',
    '/member' => dirname(__DIR__) . '/app/Views/admin/member/index.php',
    '/member/add' => dirname(__DIR__) . '/app/Views/admin/member/add.php',
    '/member/edit' => dirname(__DIR__) . '/app/Views/admin/member/edit.php',
    '/member/verify-email' => [MemberController::class, 'verifyEmail'],
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
    '/api/books' => [BookController::class, 'index'],
    '/api/books/show' => [BookController::class, 'show'],
    '/api/books/create' => [BookController::class, 'store'],
    '/api/books/update' => [BookController::class, 'update'],
    '/api/books/delete' => [BookController::class, 'delete'],
    '/api/transactions' => [TransactionController::class, 'index'],
    '/api/transactions/show' => [TransactionController::class, 'show'],
    '/api/transactions/update-status' => [TransactionController::class, 'updateStatus'],
    '/api/transactions/approve-return' => [TransactionController::class, 'approveReturn'],
    '/api/member/transactions' => [MemberTransactionController::class, 'index'],
    '/api/member/transactions/show' => [MemberTransactionController::class, 'show'],
    '/api/member/books' => [MemberTransactionController::class, 'books'],
    '/api/member/transactions/create' => [MemberTransactionController::class, 'store'],
    '/api/member/transactions/request-return' => [MemberTransactionController::class, 'requestReturn'],
    '/api/dashboard/stats' => [DashboardController::class, 'stats'],
    '/book/cover' => [BookController::class, 'cover'],
    '/user/avatar' => [UserController::class, 'avatar'],
    '/member/avatar' => [MemberController::class, 'avatar'],
    
    '/member/dashboard' => dirname(__DIR__) . '/app/Views/member/dashboard/index.php',
    '/member/dashboard/create' => dirname(__DIR__) . '/app/Views/member/transaction/create.php',
    '/member/dashboard/detail' => dirname(__DIR__) . '/app/Views/member/transaction/detail.php',
];
