<?php
$pageTitle = $psychologist['last_name'] . ' ' . $psychologist['first_name'];
require __DIR__ . '/partials/header.php';
?>

<div data-psychologist-page data-psychologist-id="<?= (int)$psychologist['id'] ?>">
    <a href="/" class="back-link">← Все психологи</a>

    <div class="card">
        <div class="tw-profile">
            <div class="tw-profile__photo">
                <?php if (!empty($psychologist['photo_path'])): ?>
                    <img src="<?= htmlspecialchars($psychologist['photo_path']) ?>" alt="">
                <?php else: ?>
                    <?php
                        $fn = (string)($psychologist['first_name'] ?? '');
                        $ln = (string)($psychologist['last_name'] ?? '');
                        if (function_exists('mb_substr')) {
                            $initials = mb_substr($fn, 0, 1) . mb_substr($ln, 0, 1);
                        } else {
                            $initials = substr($fn, 0, 1) . substr($ln, 0, 1);
                        }
                    ?>
                    <?= htmlspecialchars($initials) ?>
                <?php endif; ?>
            </div>
            <div>
                <h1 style="margin-bottom:6px;">
                    <?= htmlspecialchars($psychologist['last_name'] . ' ' . $psychologist['first_name'] . ' ' . ($psychologist['patronymic'] ?? '')) ?>
                </h1>
                <?php if (!empty($psychologist['directions'])): ?>
                    <p class="subtitle" style="margin-bottom:10px;"><?= htmlspecialchars($psychologist['directions']) ?></p>
                <?php else: ?>
                    <p class="subtitle" style="margin-bottom:10px;">Психологическая консультация</p>
                <?php endif; ?>

                <div class="tw-actions-row">
                    <button type="button" class="tw-btn" data-open-slots>Записаться</button>
                    <a class="tw-btn tw-btn--ghost" href="/announcements">Правила и мероприятия</a>
                </div>

                <?php if (!empty($psychologist['bio'])): ?>
                    <div style="margin-top:12px;color:var(--text-secondary);font-size:14px;">
                        <?= nl2br(htmlspecialchars(trim(strip_tags($psychologist['bio'])))) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fallback без JS: старый выбор через страницу -->
    <details class="card" style="margin-top:14px;">
        <summary style="cursor:pointer;font-weight:600;color:var(--muted);">Если модальное окно не открылось — выбрать дату/время здесь</summary>

        <div style="margin-top:12px;">
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
    </details>

    <!-- Модалка выбора даты/времени -->
    <div class="tw-modal" id="twSlotsModal" data-modal>
        <div class="tw-modal__dialog">
            <div class="tw-modal__header">
                <p class="tw-modal__title">Выберите дату и время записи</p>
                <button class="tw-modal__close" type="button" data-modal-close aria-label="Закрыть">×</button>
            </div>
            <div class="tw-modal__body">
                <div class="tw-slots">
                    <div class="tw-slots__col">
                        <div class="tw-slots__head">Даты</div>
                        <div class="tw-slots__list" data-slots-dates></div>
                    </div>
                    <div class="tw-slots__col">
                        <div class="tw-slots__head">Время</div>
                        <div class="tw-slots__grid" data-slots-times></div>
                    </div>
                </div>
                <div class="tw-legend" data-slots-legend></div>
                <p style="margin:12px 0 0;font-size:12px;color:var(--text-secondary);">
                    Цвет слота показывает формат занятия (лично / группа / семья).
                </p>
            </div>
        </div>
    </div>

    <!-- Модалка формы записи (подгружается по /book?slot_id=...&partial=1) -->
    <div class="tw-modal" id="twBookingFormModal" data-modal>
        <div class="tw-modal__dialog" style="max-width:720px;">
            <div class="tw-modal__header">
                <p class="tw-modal__title">Данные для записи</p>
                <button class="tw-modal__close" type="button" data-modal-close aria-label="Закрыть">×</button>
            </div>
            <div class="tw-modal__body">
                <div data-booking-form-container></div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
