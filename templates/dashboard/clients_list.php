<?php $pageTitle = 'Клиенты'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/dashboard" class="back-link">← Мои записи</a>
<h1>Все клиенты</h1>
<p class="subtitle">Все, кто когда-либо обращался к вам за помощью.</p>

<?php if (empty($clients)): ?>
    <p class="empty-state">Пока никто не записывался.</p>
<?php else: ?>
    <?php foreach ($clients as $c): ?>
        <a href="/dashboard/clients/show?id=<?= (int)$c['id'] ?>" class="card"
           style="display:flex;justify-content:space-between;align-items:center;text-decoration:none;color:inherit;">
            <div>
                <p class="psychologist-name" style="margin-bottom:2px;">
                    <?= htmlspecialchars($c['last_name'] . ' ' . $c['first_name'] . ' ' . ($c['patronymic'] ?? '')) ?>
                </p>
                <p style="font-size:13px;color:var(--text-secondary);margin:0;">
                    <?= htmlspecialchars($c['group_or_dept'] ?? '') ?>
                </p>
            </div>
            <div style="text-align:right;font-size:13px;color:var(--text-secondary);">
                <div><?= (int)$c['visits_count'] ?> посещени<?= (int)$c['visits_count'] === 1 ? 'е' : 'й' ?></div>
                <div>последнее: <?= htmlspecialchars(date('d.m.Y', strtotime($c['last_visit']))) ?></div>
            </div>
        </a>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
