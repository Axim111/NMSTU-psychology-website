<?php $pageTitle = 'Психологи — запись на приём'; require __DIR__ . '/partials/header.php'; ?>

<h1>Выберите психолога</h1>
<p class="subtitle">Нажмите на карточку, чтобы посмотреть свободные даты и время</p>

<?php if (empty($psychologists)): ?>
    <p class="empty-state">Психологи пока не добавлены в базу.</p>
<?php else: ?>
    <?php foreach ($psychologists as $p): ?>
        <a href="/psychologist?id=<?= (int)$p['id'] ?>" class="card psychologist-card">
            <div class="avatar"><?= htmlspecialchars(mb_substr($p['first_name'], 0, 1) . mb_substr($p['last_name'], 0, 1)) ?></div>
            <div>
                <p class="psychologist-name"><?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?></p>
                <p class="psychologist-tags"><?= htmlspecialchars($p['directions'] ?? '') ?></p>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
