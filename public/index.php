<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

Router::resolve();