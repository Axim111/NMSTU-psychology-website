<?php $pageTitle = 'Карточка клиента'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/dashboard/clients" class="back-link">← Все клиенты</a>
<h1><?= htmlspecialchars($client['last_name'] . ' ' . $client['first_name'] . ' ' . ($client['patronymic'] ?? '')) ?></h1>
<p class="subtitle">
    <?= htmlspecialchars($client['group_or_dept'] ?? '') ?>
    <?php if ($client['phone']): ?> · <?= htmlspecialchars($client['phone']) ?><?php endif; ?>
</p>

<div class="card" style="margin-bottom:16px;">
    <p class="section-label">Сообщение клиенту</p>
    <p style="margin:0 0 10px;color:var(--text-secondary);font-size:13px;">
        Это сообщение увидит клиент (в уведомлениях/почте — сейчас заглушка через `notification_log`).
        Внутренние заметки сохраняйте ниже в истории посещений.
    </p>
    <form method="post" action="/dashboard/message" style="display:flex;gap:8px;">
        <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
        <input type="hidden" name="appointment_id" value="0">
        <input type="text" name="message" placeholder="Например: «Возьмите с собой карандаши»" style="flex:1;margin:0;">
        <button type="submit" class="btn" style="margin:0;padding:8px 14px;">Отправить</button>
    </form>
</div>

<p class="section-label">История посещений (прошлые и будущие)</p>

<?php if (empty($visits)): ?>
    <p class="empty-state">Записей нет.</p>
<?php else: ?>
    <?php foreach ($visits as $v): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <span style="font-size:14px;font-weight:500;">
                    <?= htmlspecialchars(date('d.m.Y', strtotime($v['slot_date']))) ?>
                    · <?= htmlspecialchars(substr($v['start_time'], 0, 5)) ?>
                </span>
                <span style="font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;
                    <?= match($v['status']) {
                        'active' => 'background:var(--blue-light);color:var(--blue-dark);',
                        'cancelled' => 'background:#fdecea;color:#c0392b;',
                        default => 'background:#eee;color:#555;',
                    } ?>">
                    <?= match($v['status']) { 'active' => 'Активна', 'cancelled' => 'Отменена', default => 'Завершена' } ?>
                </span>
            </div>

            <?php if ($v['request_comment']): ?>
                <p style="font-size:13px;color:var(--text-secondary);margin:8px 0 0;">Запрос клиента: «<?= htmlspecialchars($v['request_comment']) ?>»</p>
            <?php endif; ?>

            <?php if (!empty($v['notes'])): ?>
                <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px;">
                    <?php foreach ($v['notes'] as $n): ?>
                        <p style="font-size:13px;background:#fffbe6;border-radius:6px;padding:8px 10px;margin:0;">
                            <?= htmlspecialchars($n['note_text']) ?>
                            <span style="color:var(--text-secondary);font-size:11px;"> — <?= htmlspecialchars(date('d.m.Y H:i', strtotime($n['created_at']))) ?></span>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/dashboard/notes" style="margin-top:10px;display:flex;gap:8px;">
                <input type="hidden" name="appointment_id" value="<?= (int)$v['id'] ?>">
                <input type="hidden" name="client_id" value="<?= (int)$client['id'] ?>">
                <input type="text" name="note_text" placeholder="Добавить заметку (видна только психологам)" style="flex:1;margin:0;">
                <button type="submit" class="btn" style="margin:0;padding:8px 14px;">Сохранить</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
