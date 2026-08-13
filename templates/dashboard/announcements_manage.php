<?php $pageTitle = 'Объявления — управление'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/dashboard" class="back-link">← Назад</a>
<h1>Объявления и памятки</h1>

<div class="card">
    <p class="section-label">Добавить</p>
    <form method="post" action="/dashboard/announcements">
        <label for="kind">Тип</label>
        <select id="kind" name="kind">
            <option value="event">Групповое мероприятие</option>
            <option value="info">Памятка (правила, экстренная помощь и т.п.)</option>
        </select>

        <label for="title">Заголовок</label>
        <input type="text" id="title" name="title" required>

        <label for="content">Текст</label>
        <textarea id="content" name="content" required></textarea>

        <label for="event_date">Дата мероприятия (если применимо)</label>
        <input type="text" id="event_date" name="event_date" placeholder="2026-08-25 15:00">

        <button type="submit" class="btn">Опубликовать</button>
    </form>
</div>

<p class="section-label" style="margin-top:24px;">Все записи</p>
<?php if (empty($items)): ?>
    <p class="empty-state">Пока ничего не опубликовано.</p>
<?php else: ?>
    <?php foreach ($items as $it): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
            <div>
                <p style="font-size:12px;color:var(--text-secondary);margin:0 0 4px;">
                    <?= $it['kind'] === 'info' ? 'Памятка' : 'Мероприятие' ?>
                </p>
                <p class="psychologist-name" style="margin-bottom:4px;"><?= htmlspecialchars($it['title']) ?></p>
                <p style="font-size:13px;margin:0;"><?= nl2br(htmlspecialchars($it['content'])) ?></p>
            </div>
            <form method="post" action="/dashboard/announcements/delete" onsubmit="return confirm('Удалить?');">
                <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                <button type="submit" class="btn btn-secondary" style="margin:0;padding:6px 12px;font-size:12px;">Удалить</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
