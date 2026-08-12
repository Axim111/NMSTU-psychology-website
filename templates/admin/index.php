<?php $pageTitle = 'Админка'; require __DIR__ . '/../partials/header.php'; ?>

<h1>Психологи</h1>
<p class="subtitle"><a href="/admin/psychologists/new">+ Добавить психолога</a></p>

<?php if (empty($psychologists)): ?>
    <p class="empty-state">Психологов пока нет.</p>
<?php else: ?>
    <?php foreach ($psychologists as $p): ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <p class="psychologist-name" style="margin-bottom:2px;">
                    <?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?>
                </p>
                <p style="font-size:13px;color:var(--text-secondary);margin:0;"><?= htmlspecialchars($p['email']) ?></p>
            </div>
            <span style="font-size:13px;color:var(--text-secondary);"><?= (int)$p['booked_count'] ?> записей</span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
