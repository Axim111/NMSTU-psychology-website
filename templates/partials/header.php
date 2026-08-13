<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Психологическая поддержка') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
        <a href="/" class="logo">Центр психологической поддержки</a>
        <div style="font-size:13px;color:var(--text-secondary);display:flex;align-items:center;gap:12px;">
            <a href="/announcements">Мероприятия</a>
            <a href="/contacts">Ссылки</a>
            <?php if (\App\Core\Auth::check()): ?>
                <?php
                    $roleLink = match (\App\Core\Auth::role()) {
                        'admin' => '/admin',
                        'psychologist' => '/dashboard',
                        default => '/cabinet',
                    };
                    $roleLabel = match (\App\Core\Auth::role()) {
                        'admin' => 'Админка',
                        'psychologist' => 'Кабинет психолога',
                        default => 'Мои записи',
                    };
                ?>
                <a href="<?= $roleLink ?>"><?= $roleLabel ?></a>
                <?php if (\App\Core\Auth::role() === 'psychologist'): ?>
                    <a href="/dashboard/announcements">Объявления</a>
                <?php elseif (\App\Core\Auth::role() === 'admin'): ?>
                    <a href="/dashboard/announcements">Объявления</a>
                    <a href="/admin/contacts">Ссылки (упр.)</a>
                <?php endif; ?>
                <span><?= htmlspecialchars(\App\Core\Auth::name()) ?></span>
                <a href="/logout">Выйти</a>
            <?php else: ?>
                <a href="/login">Вход для сотрудников</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<div class="container">
