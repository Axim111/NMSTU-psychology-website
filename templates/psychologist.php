<?php
$pageTitle = $psychologist['last_name'] . ' ' . $psychologist['first_name'];
require __DIR__ . '/partials/header.php';

use App\Core\Auth;

$isStaff = $isStaff ?? false;
$currentRole = $isStaff ? Auth::role() : null;
$staffPsychologistProfileId = $staffPsychologistProfileId ?? null;
$allPsychologists = $allPsychologists ?? [];
$freeSlotsForForm = $freeSlotsForForm ?? [];

$viewingOtherPsychologist = false;
if ($currentRole === 'psychologist' && $staffPsychologistProfileId && (int)$psychologist['id'] !== (int)$staffPsychologistProfileId) {
    $viewingOtherPsychologist = true;
}
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

                <?php if (!$isStaff): ?>
                    <div class="tw-actions-row">
                        <button type="button" class="tw-btn" data-open-slots>Записаться</button>
                        <a class="tw-btn tw-btn--ghost" href="/announcements">Правила и мероприятия</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($psychologist['bio'])): ?>
                    <div style="margin-top:12px;color:var(--text-secondary);font-size:14px;">
                        <?= nl2br(htmlspecialchars(trim(strip_tags($psychologist['bio'])))) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($isStaff): ?>

        <div class="card" style="margin-top:16px;">
            <h2 style="margin:0 0 4px;font-size:20px;">Создать запись для клиента</h2>
            <p class="subtitle" style="margin:0 0 16px;">
                <?php if ($currentRole === 'psychologist'): ?>
                    <?php if ($viewingOtherPsychologist): ?>
                        <span style="color:var(--danger);">
                            ⚠️ Вы смотрите страницу коллеги, но форма работает только для ваших записей (к вам).
                        </span>
                    <?php else: ?>
                        Форма для записи клиентов к вам на приём.
                    <?php endif; ?>
                <?php else: ?>
                    Админ-режим: запись клиентов к <strong>текущему психологу</strong> (<?= htmlspecialchars($psychologist['last_name'] . ' ' . $psychologist['first_name']) ?>).
                    Чтобы записать к другому — перейдите на его страницу через <a href="/" style="color:var(--primary);">список психологов</a> или <a href="/admin" style="color:var(--primary);">админку</a>.
                <?php endif; ?>
            </p>

            <form method="post" action="/book-for-client" class="">
                <?php
                    $formPsychId = $currentRole === 'psychologist'
                        ? (int)$staffPsychologistProfileId
                        : (int)$psychologist['id'];
                ?>
                <input type="hidden" name="psychologist_id" value="<?= $formPsychId ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 16px;">
                    <div>
                        <label for="s_last_name">Фамилия клиента *</label>
                        <input type="text" id="s_last_name" name="last_name" required>
                    </div>
                    <div>
                        <label for="s_first_name">Имя клиента *</label>
                        <input type="text" id="s_first_name" name="first_name" required>
                    </div>
                    <div>
                        <label for="s_patronymic">Отчество</label>
                        <input type="text" id="s_patronymic" name="patronymic">
                    </div>
                    <div>
                        <label for="s_group">Группа / кафедра</label>
                        <input type="text" id="s_group" name="group_or_dept">
                    </div>
                    <div>
                        <label for="s_phone">Телефон *</label>
                        <input type="tel" id="s_phone" name="phone" required>
                    </div>
                    <div>
                        <label for="s_email">Email</label>
                        <input type="email" id="s_email" name="email" placeholder="you@example.com">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label for="s_contact_link">Ссылка для связи (ВК, Телеграм и т.п.)</label>
                        <input type="text" id="s_contact_link" name="contact_link" placeholder="https://vk.com/... или @username">
                    </div>
                </div>

                <label for="s_slot" style="margin-top:14px;display:block;">Дата и время приёма *</label>
                <?php if (empty($freeSlotsForForm)): ?>
                    <p class="empty-state" style="margin:6px 0 0;">
                        Нет свободных слотов для записи.
                        <?php if ($currentRole === 'psychologist'): ?>
                            Создайте слоты в <a href="/dashboard/schedule" style="color:var(--primary);">вашем расписании</a>.
                        <?php else: ?>
                            Создайте слоты в <a href="/admin/schedule?psychologist_id=<?= $formPsychId ?>" style="color:var(--primary);">расписании психолога</a>.
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <select id="s_slot" name="slot_id" required style="width:100%;">
                        <option value="">— выберите свободные дату и время —</option>
                        <?php
                            $lastDate = '';
                            $formatLabels = [
                                'individual' => 'индивидуально',
                                'group' => 'группа',
                                'family' => 'семья',
                            ];
                        ?>
                        <?php foreach ($freeSlotsForForm as $s): ?>
                            <?php
                                $d = $s['slot_date'];
                                if ($d !== $lastDate) {
                                    if ($lastDate !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars(date('d.m.Y, D', strtotime($d))) . '">';
                                    $lastDate = $d;
                                }
                                $fmt = $formatLabels[$s['format']] ?? $s['format'];
                                $label = substr($s['start_time'], 0, 5) . ' – ' . substr($s['end_time'], 0, 5) . ' (' . $fmt . ')';
                            ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                        <?php if ($lastDate !== '') echo '</optgroup>'; ?>
                    </select>
                <?php endif; ?>

                <label for="s_comment" style="margin-top:14px;display:block;">Краткий запрос / основная причина обращения</label>
                <textarea id="s_comment" name="comment" rows="3"></textarea>

                <button type="submit" class="btn" style="margin-top:16px;" <?= empty($freeSlotsForForm) ? 'disabled' : '' ?>>
                    Создать запись
                </button>
            </form>
        </div>

    <?php else: ?>

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

    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
