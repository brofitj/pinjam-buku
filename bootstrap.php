<?php

use Core\Logger;

Logger::init();

/*
|--------------------------------------------------------------------------
| Handle PHP Errors
|--------------------------------------------------------------------------
*/
set_error_handler(function ($severity, $message, $file, $line) {
    Logger::error($message, [
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ]);
});

/*
|--------------------------------------------------------------------------
| Handle Exceptions
|--------------------------------------------------------------------------
*/
set_exception_handler(function ($exception) {
    Logger::error($exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
});

/*
|--------------------------------------------------------------------------
| Handle Fatal Errors
|--------------------------------------------------------------------------
*/
register_shutdown_function(function () {
    $error = error_get_last();

    if ($error !== null) {
        Logger::error($error['message'], $error);
    }
});