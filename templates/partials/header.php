<?php
// Для подсветки активной вкладки нав-бара — как активный пункт меню на портале.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
function navActive(string $prefix, string $currentPath): string
{
    if ($prefix === '/') {
        return $currentPath === '/' ? 'active' : '';
    }
    return str_starts_with($currentPath, $prefix) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Психологическая поддержка') ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script defer src="/assets/js/app.js"></script>
</head>
<body>
<header class="site-header" data-site-header>
    <div class="header-inner">
        <a href="/" class="logo">
            <span class="logo-mark">Ц</span>
            <span>Центр психологической поддержки</span>
        </a>

        <button type="button" class="burger-btn" data-burger-toggle aria-label="Открыть меню" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <div class="nav-mobile-wrap" data-burger-menu>
            <nav class="nav-tabs">
                <a href="/" class="<?= navActive('/', $currentPath) ?>">Психологи</a>
                <a href="/announcements" class="<?= navActive('/announcements', $currentPath) ?>">Мероприятия</a>
                <a href="/contacts" class="<?= navActive('/contacts', $currentPath) ?>">Ссылки</a>
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
                    <a href="<?= $roleLink ?>" class="<?= navActive($roleLink, $currentPath) ?>"><?= $roleLabel ?></a>
                    <?php if (\App\Core\Auth::role() === 'psychologist'): ?>
                        <a href="/dashboard/profile" class="<?= navActive('/dashboard/profile', $currentPath) ?>">Профиль</a>
                        <a href="/dashboard/announcements" class="<?= navActive('/dashboard/announcements', $currentPath) ?>">Управление объявлениями</a>
                    <?php elseif (\App\Core\Auth::role() === 'admin'): ?>
                        <a href="/dashboard/announcements" class="<?= navActive('/dashboard/announcements', $currentPath) ?>">Объявления</a>
                        <a href="/admin/contacts" class="<?= navActive('/admin/contacts', $currentPath) ?>">Ссылки (упр.)</a>
                        <a href="/admin/appointments" class="<?= navActive('/admin/appointments', $currentPath) ?>">Записи</a>
                        <a href="/admin/notifications" class="<?= navActive('/admin/notifications', $currentPath) ?>">Уведомления</a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <?php if (\App\Core\Auth::check()): ?>
                    <span class="user-name"><?= htmlspecialchars(\App\Core\Auth::name()) ?></span>
                    <a href="/logout">Выйти</a>
                <?php else: ?>
                    <a href="/login">Вход для сотрудников</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<div class="container">
