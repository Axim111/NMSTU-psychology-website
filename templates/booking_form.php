<?php
    $isPartial = $isPartial ?? false;
    if (!$isPartial) {
        $pageTitle = 'Данные для записи';
        require __DIR__ . '/partials/header.php';
    }
?>

<?php if (!$isPartial): ?>
    <a href="/psychologist?id=<?= (int)$slot['psychologist_id'] ?>" class="back-link">← Назад к выбору времени</a>
    <h1>Заполните данные</h1>
    <p class="subtitle">
        <?= htmlspecialchars($slot['last_name'] . ' ' . $slot['first_name']) ?>
        · <?= htmlspecialchars(date('d.m.Y', strtotime($slot['slot_date']))) ?>
        · <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?>
    </p>
<?php else: ?>
    <div style="margin-bottom:12px;">
        <div style="font-family:var(--font-display);font-weight:700;font-size:18px;">
            <?= htmlspecialchars($slot['last_name'] . ' ' . $slot['first_name']) ?>
        </div>
        <div style="color:var(--text-secondary);font-size:13px;margin-top:2px;">
            <?= htmlspecialchars(date('d.m.Y', strtotime($slot['slot_date']))) ?>
            · <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="/book" class="<?= $isPartial ? '' : 'card' ?>">
    <input type="hidden" name="slot_id" value="<?= (int)$slot['id'] ?>">

    <?php if ($loggedInClient): ?>
        <p style="font-size:14px;background:var(--blue-light);border-radius:var(--radius);padding:10px 14px;margin:0 0 4px;">
            Запись на <strong><?= htmlspecialchars($loggedInClient['last_name'] . ' ' . $loggedInClient['first_name']) ?></strong>
            <?php if ($loggedInClient['group_or_dept']): ?> · <?= htmlspecialchars($loggedInClient['group_or_dept']) ?><?php endif; ?>
        </p>
        <p style="font-size:13px;color:var(--text-secondary);margin:0 0 12px;">
            <?php if (!empty($loggedInClient['phone'])): ?>Телефон: <?= htmlspecialchars($loggedInClient['phone']) ?><?php endif; ?>
            <?php if (!empty($loggedInClient['email'])): ?> · Email: <?= htmlspecialchars($loggedInClient['email']) ?><?php endif; ?>
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

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@example.com">
    <?php endif; ?>

    <?php $slotFormat = $slot['format'] ?? 'individual'; ?>
    <label for="lesson_type">Тип занятия</label>
    <input type="hidden" name="lesson_type" value="<?= htmlspecialchars($slotFormat) ?>">
    <select id="lesson_type" name="lesson_type_disabled" disabled>
        <option value="individual" <?= $slotFormat === 'individual' ? 'selected' : '' ?>>Лично (индивидуально)</option>
        <option value="group" <?= $slotFormat === 'group' ? 'selected' : '' ?>>Группа</option>
        <option value="family" <?= $slotFormat === 'family' ? 'selected' : '' ?>>Семья</option>
    </select>

    <label for="comment">Краткий запрос (по желанию)</label>
    <textarea id="comment" name="comment"></textarea>

    <label style="display:flex;align-items:flex-start;gap:8px;font-weight:normal;margin-top:10px;">
        <input type="checkbox" name="consent" value="1" required style="width:auto;margin-top:3px;">
        <span style="font-size:13px;color:var(--text-secondary);">
            Согласен(а) на обработку персональных данных
        </span>
    </label>

    <button type="submit" class="<?= $isPartial ? 'tw-btn' : 'btn' ?>" style="<?= $isPartial ? '' : '' ?>">Записаться</button>
</form>

<?php if (!$isPartial): ?>
    <?php require __DIR__ . '/partials/footer.php'; ?>
<?php endif; ?>
