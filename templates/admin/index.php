<?php $pageTitle = 'Админка'; require __DIR__ . '/../partials/header.php'; ?>

<h1>Психологи</h1>
<p class="subtitle">
    <a href="/admin/psychologists/new">+ Добавить психолога</a>
    · <a href="/admin/appointments">Все записи</a>
</p>

<?php if (empty($psychologists)): ?>
    <p class="empty-state">Психологов пока нет.</p>
<?php else: ?>
    <?php foreach ($psychologists as $p): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
            <div style="display:flex;align-items:center;gap:12px;min-width:0;">
                <div class="tw-profile__photo" style="width:54px;height:54px;border-radius:12px;">
                    <?php if (!empty($p['photo_path'])): ?>
                        <img src="<?= htmlspecialchars($p['photo_path']) ?>" alt="">
                    <?php else: ?>
                        <?php
                            $fn = (string)($p['first_name'] ?? '');
                            $ln = (string)($p['last_name'] ?? '');
                            if (function_exists('mb_substr')) {
                                $initials = mb_substr($fn, 0, 1) . mb_substr($ln, 0, 1);
                            } else {
                                $initials = substr($fn, 0, 1) . substr($ln, 0, 1);
                            }
                        ?>
                        <?= htmlspecialchars($initials) ?>
                    <?php endif; ?>
                </div>
                <div style="min-width:0;">
                    <p class="psychologist-name" style="margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?>
                    </p>
                    <p style="font-size:13px;color:var(--text-secondary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= htmlspecialchars($p['email']) ?>
                    </p>
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <a class="tw-btn tw-btn--ghost" href="/admin/schedule?psychologist_id=<?= (int)$p['id'] ?>" style="height:34px;padding:0 12px;">Расписание</a>
                <span style="font-size:13px;color:var(--text-secondary);"><?= (int)$p['booked_count'] ?> записей</span>
                <form method="post" action="/admin/psychologists/delete" onsubmit="return confirm('Удалить психолога?');" style="margin:0;">
                    <input type="hidden" name="user_id" value="<?= (int)$p['user_id'] ?>">
                    <button type="submit" class="tw-btn tw-btn--ghost" style="height:34px;padding:0 12px;border-color:#fdecea;color:#c0392b;">Удалить</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
