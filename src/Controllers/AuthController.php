<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\LocalAuthProvider;

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            $this->redirectToDashboard();
            return;
        }
        $error = null;
        require __DIR__ . '/../../templates/login.php';
    }

    public function login(): void
    {
        // Единственное место, которое поменяется на SSO:
        // new LocalAuthProvider() -> new SsoAuthProvider().
        // Всё остальное (Auth::login, редиректы по ролям) не меняется.
        $provider = new LocalAuthProvider();

        $user = $provider->attempt([
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
        ]);

        if (!$user) {
            $error = 'Неверный email или пароль.';
            require __DIR__ . '/../../templates/login.php';
            return;
        }

        Auth::login($user);
        $this->redirectToDashboard();
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
    }

    private function redirectToDashboard(): void
    {
        $target = match (Auth::role()) {
            'admin' => '/admin',
            default => '/cabinet',
        };
        header('Location: ' . $target);
    }
}
