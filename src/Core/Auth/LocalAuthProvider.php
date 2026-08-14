<?php

namespace App\Core\Auth;

use App\Core\Database;

/**
 * Проверка по email + паролю в своей БД.
 * Используется для всех трёх ролей (client, psychologist, admin) —
 * это временная замена SSO портала вуза.
 */
class LocalAuthProvider implements AuthProviderInterface
{
    public function attempt(array $credentials): ?array
    {
        $email = trim($credentials['email'] ?? '');
        $password = $credentials['password'] ?? '';

        if ($email === '' || $password === '') {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, role, last_name, first_name, password_hash
             FROM users
             WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['password_hash'] || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        unset($user['password_hash']); // дальше по коду хэш нигде не нужен
        return $user;
    }
}
