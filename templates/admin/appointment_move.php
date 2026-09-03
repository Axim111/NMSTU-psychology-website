<?php $pageTitle = 'Перенос записи'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin/appointments" class="back-link">← Все записи</a>
<h1>Перенос записи</h1>
<p class="subtitle">
    Клиент: <?= htmlspecialchars($appointment['client_last_name'] . ' ' . $appointment['client_first_name']) ?><br>
    Психолог: <?= htmlspecialchars($appointment['psych_last_name'] . ' ' . $appointment['psych_first_name']) ?><br>
    Сейчас: <?= htmlspecialchars(date('d.m.Y', strtotime($appointment['slot_date']))) ?> · <?= htmlspecialchars(substr($appointment['start_time'], 0, 5)) ?>
</p>

<div class="card" style="max-width:560px;">
    <?php if (empty($freeSlots)): ?>
        <p class="empty-state" style="margin:0;">Нет свободных слотов для переноса.</p>
    <?php else: ?>
        <form method="post" action="/admin/appointments/move">
            <input type="hidden" name="appointment_id" value="<?= (int)$appointment['id'] ?>">

            <label for="new_slot_id">Новый слот</label>
            <select id="new_slot_id" name="new_slot_id" required>
                <?php foreach ($freeSlots as $s): ?>
                    <option value="<?= (int)$s['id'] ?>">
                        <?= htmlspecialchars(date('d.m.Y', strtotime($s['slot_date']))) ?>
                        <?= htmlspecialchars(substr($s['start_time'], 0, 5)) ?>–<?= htmlspecialchars(substr($s['end_time'], 0, 5)) ?>
                        (<?= match($s['format']) { 'group' => 'группа', 'family' => 'семья', default => 'лично' } ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn" style="margin-top:12px;" onclick="return confirm('Перенести запись на выбранный слот?');">
                Перенести
            </button>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

