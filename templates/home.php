<?php $pageTitle = 'Психологи — запись на приём'; require __DIR__ . '/partials/header.php'; ?>

<h1>Выберите психолога</h1>
<p class="subtitle">Нажмите на карточку, чтобы посмотреть свободные даты и время</p>

<?php if (empty($psychologists)): ?>
    <p class="empty-state">Психологи пока не добавлены в базу.</p>
<?php else: ?>
    <div class="psych-grid">
        <?php foreach ($psychologists as $i => $p): ?>
            <a href="/psychologist?id=<?= (int)$p['id'] ?>" class="psychologist-card">
                <div class="psychologist-card__cover pattern-<?= $i % 4 ?>"></div>
                <div class="psychologist-card__body">
                    <p class="psychologist-name"><?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?></p>
                    <p class="psychologist-tags"><?= htmlspecialchars($p['directions'] ?? '') ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
