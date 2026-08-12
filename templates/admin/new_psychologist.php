<?php $pageTitle = 'Новый психолог'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin" class="back-link">← Психологи</a>
<h1>Новый психолог</h1>

<div class="card" style="max-width:420px;">
    <?php if (!empty($error)): ?>
        <p style="color:#c0392b;font-size:14px;margin-top:0;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/admin/psychologists">
        <label for="last_name">Фамилия *</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="first_name">Имя *</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="patronymic">Отчество</label>
        <input type="text" id="patronymic" name="patronymic">

        <label for="email">Email (логин) *</label>
        <input type="text" id="email" name="email" required>

        <label for="password">Пароль *</label>
        <input type="password" id="password" name="password" required>

        <label for="bio">О себе</label>
        <textarea id="bio" name="bio"></textarea>

        <button type="submit" class="btn">Создать</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
