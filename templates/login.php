<?php $pageTitle = 'Вход'; require __DIR__ . '/partials/header.php'; ?>

<h1>Вход для сотрудников</h1>
<p class="subtitle">Для психологов и администратора. Студенты и ППС записываются без входа.</p>

<div class="card" style="max-width:360px;">
    <?php if (!empty($error)): ?>
        <p style="color:#c0392b;font-size:14px;margin-top:0;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/login">
        <label for="email">Email</label>
        <input type="text" id="email" name="email" required>

        <label for="password">Пароль</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="btn">Войти</button>
    </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
