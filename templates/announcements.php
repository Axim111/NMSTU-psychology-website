<?php $pageTitle = 'Объявления'; require __DIR__ . '/partials/header.php'; ?>

<h1>Мероприятия и информация</h1>

<p class="section-label">Ближайшие групповые мероприятия</p>
<?php if (empty($events)): ?>
    <p class="empty-state">Пока ничего не запланировано.</p>
<?php else: ?>
    <?php foreach ($events as $e): ?>
        <div class="card">
            <p class="psychologist-name" style="margin-bottom:4px;"><?= htmlspecialchars($e['title']) ?></p>
            <?php if ($e['event_date']): ?>
                <p style="font-size:13px;color:var(--text-secondary);margin:0 0 6px;">
                    <?= htmlspecialchars(date('d.m.Y H:i', strtotime($e['event_date']))) ?>
                </p>
            <?php endif; ?>
            <p style="font-size:14px;margin:0;"><?= nl2br(htmlspecialchars($e['content'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p class="section-label" style="margin-top:24px;">Памятки</p>
<?php if (empty($infoPages)): ?>
    <p class="empty-state">Информация пока не добавлена.</p>
<?php else: ?>
    <?php foreach ($infoPages as $i): ?>
        <div class="card">
            <p class="psychologist-name" style="margin-bottom:6px;"><?= htmlspecialchars($i['title']) ?></p>
            <p style="font-size:14px;margin:0;"><?= nl2br(htmlspecialchars($i['content'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
