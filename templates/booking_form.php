<?php $pageTitle = 'Данные для записи'; require __DIR__ . '/partials/header.php'; ?>

<a href="/psychologist?id=<?= (int)$slot['psychologist_id'] ?>" class="back-link">← Назад к выбору времени</a>

<h1>Заполните данные</h1>
<p class="subtitle">
    <?= htmlspecialchars($slot['last_name'] . ' ' . $slot['first_name']) ?>
    · <?= htmlspecialchars(date('d.m.Y', strtotime($slot['slot_date']))) ?>
    · <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?>
</p>

<form method="post" action="/book" class="card">
    <input type="hidden" name="slot_id" value="<?= (int)$slot['id'] ?>">

    <?php if ($loggedInClient): ?>
        <p style="font-size:14px;background:var(--blue-light);border-radius:var(--radius);padding:10px 14px;margin:0 0 4px;">
            Запись на <strong><?= htmlspecialchars($loggedInClient['last_name'] . ' ' . $loggedInClient['first_name']) ?></strong>
            <?php if ($loggedInClient['group_or_dept']): ?> · <?= htmlspecialchars($loggedInClient['group_or_dept']) ?><?php endif; ?>
        </p>
    <?php else: ?>
        <label for="last_name">Фамилия *</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="first_name">Имя *</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="group_or_dept">Группа / кафедра</label>
        <input type="text" id="group_or_dept" name="group_or_dept">

        <label for="phone">Номер телефона *</label>
        <input type="tel" id="phone" name="phone" required>
    <?php endif; ?>

    <label for="comment">Краткий запрос (по желанию)</label>
    <textarea id="comment" name="comment"></textarea>

    <button type="submit" class="btn">Записаться</button>
</form>

<?php require __DIR__ . '/partials/footer.php'; ?>
