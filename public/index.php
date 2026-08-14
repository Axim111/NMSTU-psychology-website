<?php

session_start();

// Простой автозагрузчик классов вместо Composer.
// Смотрит на namespace App\Foo\Bar и подключает src/Foo/Bar.php
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($path)) {
        require $path;
    }
});

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\BookingController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\PsychologistController;
use App\Controllers\AdminController;
use App\Controllers\AnnouncementController;
use App\Controllers\ContactController;

$router = new Router();

// Публичная запись (гостевой сценарий, пока нет SSO)
$router->get('/', [HomeController::class, 'index']);
$router->get('/psychologist', [BookingController::class, 'show']);
$router->get('/book', [BookingController::class, 'bookForm']);
$router->post('/book', [BookingController::class, 'store']);

// Вход/выход — общий для всех трёх ролей
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Кабинет студента/ППС
$router->get('/cabinet', [ClientController::class, 'cabinet']);
$router->post('/cabinet/cancel', [ClientController::class, 'cancel']);

// Кабинет психолога
$router->get('/dashboard', [PsychologistController::class, 'dashboard']);
$router->get('/dashboard/schedule', [PsychologistController::class, 'schedule']);
$router->post('/dashboard/schedule', [PsychologistController::class, 'addSlot']);
$router->post('/dashboard/schedule/block', [PsychologistController::class, 'blockSlot']);
$router->get('/dashboard/clients', [PsychologistController::class, 'clients']);
$router->get('/dashboard/clients/show', [PsychologistController::class, 'clientDetail']);
$router->post('/dashboard/notes', [PsychologistController::class, 'addNote']);

// Объявления/памятки — публичная страница + управление (психолог, админ)
$router->get('/announcements', [AnnouncementController::class, 'index']);
$router->get('/dashboard/announcements', [AnnouncementController::class, 'manage']);
$router->post('/dashboard/announcements', [AnnouncementController::class, 'create']);
$router->post('/dashboard/announcements/delete', [AnnouncementController::class, 'delete']);

// Ссылки/контакты — публичная страница + управление (админ)
$router->get('/contacts', [ContactController::class, 'index']);
$router->get('/admin/contacts', [ContactController::class, 'manage']);
$router->post('/admin/contacts', [ContactController::class, 'create']);
$router->post('/admin/contacts/delete', [ContactController::class, 'delete']);

// Админка
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/psychologists/new', [AdminController::class, 'newPsychologistForm']);
$router->post('/admin/psychologists', [AdminController::class, 'createPsychologist']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
