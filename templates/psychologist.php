<?php
$pageTitle = $psychologist['last_name'] . ' ' . $psychologist['first_name'];
require __DIR__ . '/partials/header.php';
?>

<a href="/" class="back-link">← Все психологи</a>

<h1><?= htmlspecialchars($psychologist['last_name'] . ' ' . $psychologist['first_name'] . ' ' . ($psychologist['patronymic'] ?? '')) ?></h1>
<p class="subtitle">Выберите дату и время записи</p>

<div class="card">
    <p class="section-label">Дата</p>
    <?php if (empty($dates)): ?>
        <p class="empty-state">Свободных дат пока нет</p>
    <?php else: ?>
        <div class="date-grid">
            <?php foreach ($dates as $date): ?>
                <?php
                    $isActive = $selectedDate === $date;
                    $formatted = date('d.m, D', strtotime($date));
                ?>
                <a href="/psychologist?id=<?= (int)$psychologist['id'] ?>&date=<?= htmlspecialchars($date) ?>"
                   class="pill <?= $isActive ? 'active' : '' ?>">
                    <?= htmlspecialchars($formatted) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($selectedDate): ?>
        <p class="section-label" style="margin-top:20px;">Время</p>
        <?php if (empty($times)): ?>
            <p class="empty-state">На эту дату свободного времени не осталось</p>
        <?php else: ?>
            <div class="time-grid">
                <?php foreach ($times as $t): ?>
                    <a href="/book?slot_id=<?= (int)$t['id'] ?>" class="pill">
                        <?= htmlspecialchars(substr($t['start_time'], 0, 5)) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
