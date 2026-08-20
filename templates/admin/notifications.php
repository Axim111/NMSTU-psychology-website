<?php $pageTitle = 'Уведомления (заглушка)'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/admin" class="back-link">← Назад</a>
<h1>Уведомления — заглушка</h1>
<p class="subtitle">
    Реальная отправка (почта портала, колокольчик в личном кабинете) пока не
    подключена. Здесь видно, что и кому было бы отправлено.
</p>

<?php if (empty($items)): ?>
    <p class="empty-state">Пока ничего не "отправлялось" — запишись на приём или отмени запись, чтобы проверить.</p>
<?php else: ?>
    <?php foreach ($items as $it): ?>
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;">
                <div>
                    <p style="font-size:12px;color:var(--muted);margin:0 0 4px;">
                        <?= $it['channel'] === 'email' ? 'Почта' : 'Портал' ?>
                        · <?= htmlspecialchars($it['last_name'] . ' ' . $it['first_name']) ?>
                        · <?= htmlspecialchars(date('d.m.Y H:i', strtotime($it['created_at']))) ?>
                    </p>
                    <p class="psychologist-name" style="margin-bottom:4px;"><?= htmlspecialchars($it['subject']) ?></p>
                    <p style="font-size:13px;margin:0;"><?= htmlspecialchars($it['message']) ?></p>
                </div>
                <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:999px;background:var(--panel);color:var(--muted);white-space:nowrap;">
                    заглушка
                </span>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
