<?php

namespace App\Core\Auth;

interface AuthProviderInterface
{
    /**
     * Возвращает данные пользователя (id, role, last_name, first_name, ...)
     * при успехе, либо null при неудаче.
     *
     * @param array<string,mixed> $credentials Для локального входа: ['email' => ..., 'password' => ...].
     *                                          Для SSO в будущем: ['token' => ...] или ['sso_user' => ...].
     */
    public function attempt(array $credentials): ?array;
}
