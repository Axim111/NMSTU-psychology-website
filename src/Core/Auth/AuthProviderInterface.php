<?php

namespace App\Core\Auth;

/**
 * Точка замены на SSO вуза в будущем.
 *
 * Сейчас единственная реализация — LocalAuthProvider (логин/пароль
 * из своей таблицы users). Когда будет готова интеграция с порталом
 * вуза, появится SsoAuthProvider, реализующий этот же интерфейс
 * (принимает токен/данные от портала вместо пароля, возвращает тот
 * же формат массива пользователя). Меняется одна строка в
 * AuthController — new LocalAuthProvider() на new SsoAuthProvider().
 * Всё остальное приложение (Auth::login, сессии, requireRole,
 * шаблоны кабинетов) работает с результатом провайдера одинаково
 * и знать не будет, откуда пришла личность пользователя.
 */
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
