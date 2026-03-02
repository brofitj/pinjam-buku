<?php

namespace App\Core;

class Router
{
    public static function resolve()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $routes = require dirname(__DIR__, 2) . '/routes/web.php';

        if (array_key_exists($uri, $routes)) {
            $target = $routes[$uri];

            if (is_array($target) && count($target) === 2) {
                [$controllerClass, $method] = $target;
                if (class_exists($controllerClass) && method_exists($controllerClass, $method)) {
                    $controller = new $controllerClass();
                    $controller->$method();
                    return;
                }
            }

            if (is_string($target) && file_exists($target)) {
                require $target;
                return;
            }

            http_response_code(500);
            echo "Route misconfigured.";
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }
}