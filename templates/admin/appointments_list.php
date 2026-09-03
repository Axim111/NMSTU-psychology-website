<?php $pageTitle = 'Записи'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin" class="back-link">← Админка</a>
<h1>Все записи</h1>
<p class="subtitle">Отмена и перенос записи доступны администратору без ограничения 24 часа.</p>

<?php if (empty($appointments)): ?>
    <p class="empty-state">Записей пока нет.</p>
<?php else: ?>
    <?php foreach ($appointments as $a): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                <div style="min-width:0;">
                    <p style="margin:0 0 4px;font-weight:600;">
                        <?= htmlspecialchars($a['client_last_name'] . ' ' . $a['client_first_name']) ?>
                        <span style="color:var(--text-secondary);font-weight:normal;">
                            <?php if (!empty($a['group_or_dept'])): ?> · <?= htmlspecialchars($a['group_or_dept']) ?><?php endif; ?>
                            <?php if (!empty($a['phone'])): ?> · <?= htmlspecialchars($a['phone']) ?><?php endif; ?>
                            <?php if (!empty($a['email'])): ?> · <?= htmlspecialchars($a['email']) ?><?php endif; ?>
                        </span>
                    </p>
                    <p style="margin:0;color:var(--text-secondary);font-size:13px;">
                        Психолог: <?= htmlspecialchars($a['psych_last_name'] . ' ' . $a['psych_first_name']) ?>
                        · <?= htmlspecialchars(date('d.m.Y', strtotime($a['slot_date']))) ?>
                        · <?= htmlspecialchars(substr($a['start_time'], 0, 5)) ?>
                    </p>
                    <?php if (!empty($a['request_comment'])): ?>
                        <p style="margin:8px 0 0;color:var(--text-secondary);font-size:13px;">
                            Запрос: «<?= htmlspecialchars($a['request_comment']) ?>»
                        </p>
                    <?php endif; ?>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;flex-shrink:0;">
                    <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;
                        <?= match($a['status']) {
                            'active' => 'background:var(--blue-light);color:var(--blue-dark);',
                            'cancelled' => 'background:#fdecea;color:#c0392b;',
                            default => 'background:#eee;color:#555;',
                        } ?>">
                        <?= match($a['status']) { 'active' => 'Активна', 'cancelled' => 'Отменена', default => 'Завершена' } ?>
                    </span>

                    <?php if ($a['status'] === 'active'): ?>
                        <div style="display:flex;gap:8px;">
                            <a class="tw-btn tw-btn--ghost" href="/admin/appointments/move?id=<?= (int)$a['id'] ?>" style="height:34px;padding:0 12px;">Перенести</a>
                            <form method="post" action="/admin/appointments/cancel" onsubmit="return confirm('Отменить запись?');" style="margin:0;">
                                <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                                <button type="submit" class="tw-btn tw-btn--ghost" style="height:34px;padding:0 12px;border-color:#fdecea;color:#c0392b;">Отменить</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>

