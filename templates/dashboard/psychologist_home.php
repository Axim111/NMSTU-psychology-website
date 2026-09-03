<?php $pageTitle = 'Кабинет психолога'; require __DIR__ . '/../partials/header.php'; ?>

<h1>Мои записи</h1>
<p class="subtitle"><a href="/dashboard/schedule">Управление расписанием →</a></p>

<?php if (empty($appointments)): ?>
    <p class="empty-state">Пока нет активных записей.</p>
<?php else: ?>
    <?php foreach ($appointments as $a): ?>
        <div class="card">
            <p class="psychologist-name" style="margin-bottom:2px;">
                <a href="/dashboard/clients/show?id=<?= (int)$a['client_id'] ?>" style="text-decoration:none;color:inherit;">
                    <?= htmlspecialchars($a['last_name'] . ' ' . $a['first_name'] . ' ' . ($a['patronymic'] ?? '')) ?>
                </a>
            </p>
            <p style="font-size:13px;color:var(--text-secondary);margin:0 0 6px;">
                <?php if ($a['group_or_dept']): ?><?= htmlspecialchars($a['group_or_dept']) ?> · <?php endif; ?>
                <?= htmlspecialchars($a['phone'] ?? '') ?>
                <?php if (!empty($a['email'])): ?> · <?= htmlspecialchars($a['email']) ?><?php endif; ?>
            </p>
            <p style="font-size:13px;margin:0;">
                <?= htmlspecialchars(date('d.m.Y', strtotime($a['slot_date']))) ?>
                · <?= htmlspecialchars(substr($a['start_time'], 0, 5)) ?>
            </p>
            <?php if ($a['request_comment']): ?>
                <p style="font-size:13px;color:var(--text-secondary);margin:8px 0 0;">«<?= htmlspecialchars($a['request_comment']) ?>»</p>
            <?php endif; ?>

            <form method="post" action="/dashboard/appointments/cancel" onsubmit="return confirm('Отменить запись?');"
                  style="margin-top:12px;display:flex;justify-content:flex-end;">
                <input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn btn-secondary" style="margin:0;">Отменить</button>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
