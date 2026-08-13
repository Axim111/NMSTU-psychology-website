<?php $pageTitle = 'Ссылки'; require __DIR__ . '/partials/header.php'; ?>

<h1>Другие способы связи</h1>

<?php if (empty($contacts)): ?>
    <p class="empty-state">Пока ничего не добавлено.</p>
<?php else: ?>
    <?php foreach ($contacts as $c): ?>
        <div class="card" style="padding:14px 18px;">
            <p style="font-size:13px;color:var(--text-secondary);margin:0 0 2px;"><?= htmlspecialchars($c['title']) ?></p>
            <?php if ($c['type'] === 'link'): ?>
                <a href="<?= htmlspecialchars($c['value']) ?>" target="_blank" style="font-size:14px;"><?= htmlspecialchars($c['value']) ?></a>
            <?php else: ?>
                <p style="font-size:14px;margin:0;font-weight:500;"><?= htmlspecialchars($c['value']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
