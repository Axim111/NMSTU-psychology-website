<?php $pageTitle = 'Расписание психолога'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin" class="back-link">← Психологи</a>
<h1>Расписание</h1>
<p class="subtitle">
    <?= htmlspecialchars($psychologist['last_name'] . ' ' . $psychologist['first_name'] . ' ' . ($psychologist['patronymic'] ?? '')) ?>
    · Недельный лимит: <strong><?= number_format($currentWeekHours, 1) ?> из <?= number_format($maxHours, 1) ?> ч</strong>
</p>

<?php if (!empty($_SESSION['schedule_error'])): ?>
    <p style="color:#c0392b;font-size:14px;"><?= htmlspecialchars($_SESSION['schedule_error']) ?></p>
    <?php unset($_SESSION['schedule_error']); ?>
<?php endif; ?>

<div class="card">
    <p class="section-label">Добавить слот</p>
    <form method="post" action="/admin/schedule">
        <input type="hidden" name="psychologist_id" value="<?= (int)$psychologist['id'] ?>">

        <label for="slot_date">Дата</label>
        <input type="date" id="slot_date" name="slot_date" required>

        <label for="start_time">Начало</label>
        <input type="time" id="start_time" name="start_time" required>

        <label for="end_time">Конец</label>
        <input type="time" id="end_time" name="end_time" required>

        <label for="format">Формат</label>
        <select id="format" name="format">
            <option value="individual">Индивидуальный</option>
            <option value="group">Групповой</option>
            <option value="family">Семейный</option>
        </select>

        <label style="display:flex;align-items:center;gap:6px;font-weight:normal;margin-top:8px;">
            <input type="checkbox" name="emergency_override" value="1" style="width:auto;">
            Экстренный случай — добавить сверх недельного лимита
        </label>

        <button type="submit" class="btn">Добавить</button>
    </form>
</div>

<p class="section-label" style="margin-top:24px;">Ближайшие слоты</p>
<?php if (empty($slots)): ?>
    <p class="empty-state">Слотов пока нет.</p>
<?php else: ?>
    <?php foreach ($slots as $s): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;gap:10px;">
            <span style="font-size:14px;">
                <?= htmlspecialchars(date('d.m.Y', strtotime($s['slot_date']))) ?>,
                <?= htmlspecialchars(substr($s['start_time'], 0, 5)) ?>–<?= htmlspecialchars(substr($s['end_time'], 0, 5)) ?>
                <span style="color:var(--text-secondary);font-size:12px;">
                    · <?= match($s['format']) { 'group' => 'групповой', 'family' => 'семейный', default => 'индивидуальный' } ?>
                    <?php if ($s['is_emergency_override']): ?> · сверх лимита<?php endif; ?>
                </span>
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
                <form method="post" action="/admin/schedule/block" onsubmit="return confirm('Заблокировать слот?');" style="margin:0;">
                    <input type="hidden" name="psychologist_id" value="<?= (int)$psychologist['id'] ?>">
                    <input type="hidden" name="slot_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-secondary" style="margin:0;padding:6px 12px;font-size:12px;">Заблокировать</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>

