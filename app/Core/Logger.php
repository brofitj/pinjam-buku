<?php

namespace App\Core;

class Logger
{
    private static string $logFile;

    public static function init(): void
    {
        $config = require __DIR__ . '/../../config/app.php';
        self::$logFile = $config['log_path'];

        if (!file_exists(dirname(self::$logFile))) {
            mkdir(dirname(self::$logFile), 0777, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::writeLog('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::writeLog('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::writeLog('ERROR', $message, $context);
    }

    private static function writeLog(string $level, string $message, array $context = []): void
    {
        if (!isset(self::$logFile)) {
            self::init();
        }

        $date = date('Y-m-d H:i:s');
        $contextString = !empty($context) ? json_encode($context) : '';

        $log = "[$date][$level] $message $contextString" . PHP_EOL;

        $written = @file_put_contents(self::$logFile, $log, FILE_APPEND);
        if ($written === false) {
            error_log('[LOGGER_WRITE_FAILED] ' . $log);
        }
    }
}
