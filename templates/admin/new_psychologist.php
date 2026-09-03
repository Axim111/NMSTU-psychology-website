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

        <label for="photo_path">Фото (URL или путь)</label>
        <input type="text" id="photo_path" name="photo_path" placeholder="/assets/img/psychologist.jpg">

        <?php if (!empty($directions)): ?>
            <p class="section-label" style="margin-top:16px;">Направления</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($directions as $d): ?>
                    <label class="tw-chip" style="cursor:pointer;">
                        <input type="checkbox" name="directions[]" value="<?= (int)$d['id'] ?>" style="width:auto;margin-right:8px;">
                        <?= htmlspecialchars($d['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn">Создать</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
