<?php

namespace App\Core;

/**
 * Простая обёртка над $_SESSION. session_start() вызывается один раз
 * в public/index.php до роутинга.
 */
class Auth
{
    public static function login(array $user): void
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['last_name'] . ' ' . $user['first_name'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Если роль не подходит — редиректит на /login (не залогинен)
     * или отдаёт 403 (залогинен, но не та роль), и останавливает скрипт.
     */
    public static function requireRole(string ...$roles): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            echo 'Недостаточно прав для просмотра этой страницы.';
            exit;
        }
    }
}
