<?php $pageTitle = 'Расписание'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/dashboard" class="back-link">← Мои записи</a>
<h1>Расписание</h1>
<p class="subtitle">Лимит по ТЗ — не более 9 часов в неделю на одного психолога (пока не считается автоматически).</p>

<div class="card">
    <p class="section-label">Добавить слот</p>
    <form method="post" action="/dashboard/schedule">
        <label for="slot_date">Дата</label>
        <input type="date" id="slot_date" name="slot_date" required>

        <label for="start_time">Начало</label>
        <input type="time" id="start_time" name="start_time" required>

        <label for="end_time">Конец</label>
        <input type="time" id="end_time" name="end_time" required>

        <button type="submit" class="btn">Добавить</button>
    </form>
</div>

<p class="section-label" style="margin-top:24px;">Ближайшие слоты</p>
<?php if (empty($slots)): ?>
    <p class="empty-state">Слотов пока нет.</p>
<?php else: ?>
    <?php foreach ($slots as $s): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
            <span style="font-size:14px;">
                <?= htmlspecialchars(date('d.m.Y', strtotime($s['slot_date']))) ?>,
                <?= htmlspecialchars(substr($s['start_time'], 0, 5)) ?>–<?= htmlspecialchars(substr($s['end_time'], 0, 5)) ?>
            </span>
            <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;
                <?= match($s['status']) {
                    'free' => 'background:var(--blue-light);color:var(--blue-dark);',
                    'booked' => 'background:#fff4e0;color:#a06400;',
                    default => 'background:#eee;color:#555;',
                } ?>">
                <?= match($s['status']) { 'free' => 'Свободно', 'booked' => 'Занято', default => 'Заблокировано' } ?>
            </span>
            <?php if ($s['status'] === 'free'): ?>
                <form method="post" action="/dashboard/schedule/block" onsubmit="return confirm('Заблокировать слот?');">
                    <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="margin:0;padding:6px 12px;font-size:12px;">Заблокировать</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
