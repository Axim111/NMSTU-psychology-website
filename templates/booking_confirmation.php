<?php $pageTitle = 'Запись подтверждена'; require __DIR__ . '/partials/header.php'; ?>

<h1>Вы записаны</h1>

<div class="confirmation-badge">Запись успешно создана</div>

<div class="card">
    <p class="section-label">Информация о записи</p>
    <div class="info-row">
        <span class="label">Психолог</span>
        <span><?= htmlspecialchars($slot['last_name'] . ' ' . $slot['first_name']) ?></span>
    </div>
    <div class="info-row">
        <span class="label">Дата и время</span>
        <span><?= htmlspecialchars(date('d.m.Y', strtotime($slot['slot_date']))) ?>, <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?></span>
    </div>
</div>

<a href="/" class="btn btn-secondary">На главную</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
