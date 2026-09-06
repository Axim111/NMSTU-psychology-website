<?php $pageTitle = 'Мои записи'; require __DIR__ . '/../partials/header.php'; ?>

<h1>Мои записи</h1>
<p class="subtitle">Здесь видны только твои записи. Отменить или перенести можно не позже чем за 24 часа до приёма.</p>

<?php if (!empty($_SESSION['cabinet_error'])): ?>
    <p style="color:#c0392b;font-size:14px;"><?= htmlspecialchars($_SESSION['cabinet_error']) ?></p>
    <?php unset($_SESSION['cabinet_error']); ?>
<?php endif; ?>

<a href="/" class="btn" style="margin-bottom:20px;display:inline-block;">Записаться на приём</a>

<?php if (empty($appointments)): ?>
    <p class="empty-state">Записей пока нет.</p>
<?php else: ?>
    <?php foreach ($appointments as $a): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <p class="psychologist-name" style="margin-bottom:4px;">
                        <?= htmlspecialchars($a['last_name'] . ' ' . $a['first_name'] . ' ' . ($a['patronymic'] ?? '')) ?>
                    </p>
                    <p style="font-size:13px;color:var(--text-secondary);margin:0;">
                        <?= htmlspecialchars(date('d.m.Y', strtotime($a['slot_date']))) ?>
                        · <?= htmlspecialchars(substr($a['start_time'], 0, 5)) ?>
                    </p>
                    <?php if ($a['request_comment']): ?>
                        <p style="font-size:13px;color:var(--text-secondary);margin:6px 0 0;">«<?= htmlspecialchars($a['request_comment']) ?>»</p>
                    <?php endif; ?>
                </div>
                <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;
                    <?= match($a['status']) {
                        'active' => 'background:var(--blue-light);color:var(--blue-dark);',
                        'cancelled' => 'background:#fdecea;color:#c0392b;',
                        'rescheduled' => 'background:#fff4e0;color:#a06400;',
                        default => 'background:#eee;color:#555;',
                    } ?>">
                    <?= match($a['status']) {
                        'active' => 'Активна',
                        'cancelled' => 'Отменена',
                        'rescheduled' => 'Перенесена',
                        default => 'Завершена',
                    } ?>
                </span>
            </div>

            <?php if ($a['status'] === 'active'): ?>
                <div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap;align-items:center;">
                    <form method="post" action="/cabinet/cancel" onsubmit="return confirm('Отменить запись?');">
                        <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                        <button type="submit" class="btn btn-secondary" style="margin-top:0;">Отменить запись</button>
                    </form>

                    <?php $freeSlots = $availableSlotsByPsychologist[$a['psychologist_id']] ?? []; ?>
                    <?php if ($freeSlots): ?>
                        <form method="post" action="/cabinet/reschedule" style="display:flex;gap:6px;align-items:center;">
                            <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                            <select name="new_slot_id" style="margin:0;width:auto;">
                                <?php foreach ($freeSlots as $slot): ?>
                                    <option value="<?= (int)$slot['id'] ?>">
                                        <?= htmlspecialchars(date('d.m.Y', strtotime($slot['slot_date']))) ?>,
                                        <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-secondary" style="margin-top:0;">Перенести</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
