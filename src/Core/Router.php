<?php

namespace App\Core;

/**
 * Минимальный роутер без зависимостей.
 * Пример регистрации в public/index.php:
 *   $router->get('/', [HomeController::class, 'index']);
 */
class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);
            echo '404 — страница не найдена: ' . htmlspecialchars($path);
            return;
        }

        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass();
        $controller->$action();
    }
}
