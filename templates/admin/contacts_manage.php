<?php $pageTitle = 'Ссылки — управление'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin" class="back-link">← Назад</a>
<h1>Ссылки и контакты</h1>

<div class="card">
    <p class="section-label">Добавить</p>
    <form method="post" action="/admin/contacts">
        <label for="title">Название</label>
        <input type="text" id="title" name="title" placeholder="Телефон экстренной помощи" required>

        <label for="value">Значение</label>
        <input type="text" id="value" name="value" placeholder="+7 900 000-00-00 или https://vk.com/..." required>

        <label for="type">Тип</label>
        <select id="type" name="type">
            <option value="phone">Телефон</option>
            <option value="link">Ссылка</option>
            <option value="text">Текст</option>
        </select>

        <button type="submit" class="btn">Добавить</button>
    </form>
</div>

<p class="section-label" style="margin-top:24px;">Текущий список</p>
<?php if (empty($contacts)): ?>
    <p class="empty-state">Пока ничего не добавлено.</p>
<?php else: ?>
    <?php foreach ($contacts as $c): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
            <span style="font-size:14px;"><?= htmlspecialchars($c['title']) ?>: <?= htmlspecialchars($c['value']) ?></span>
            <form method="post" action="/admin/contacts/delete" onsubmit="return confirm('Удалить?');">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="btn btn-secondary" style="margin:0;padding:6px 12px;font-size:12px;">Удалить</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
