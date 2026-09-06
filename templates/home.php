<?php
$isPsychologist = $isPsychologist ?? false;
$currentPsychologist = $currentPsychologist ?? null;
$myProfileId = $myProfileId ?? null;

if ($isPsychologist) {
    $pageTitle = 'Личный кабинет психолога';
} else {
    $pageTitle = 'Психологи — запись на приём';
}
require __DIR__ . '/partials/header.php';
?>

<?php if ($isPsychologist): ?>

    <div class="card">
        <h1 style="margin:0 0 6px;">
            Здравствуйте,
            <?= htmlspecialchars(($currentPsychologist['first_name'] ?? '') . ' ' . ($currentPsychologist['patronymic'] ?? '')) ?>
            <?= htmlspecialchars($currentPsychologist['last_name'] ?? '') ?>
        </h1>
        <p class="subtitle" style="margin:0 0 20px;">Центр психологической поддержки</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:10px;">
            <a href="/psychologist<?= $myProfileId ? '?id=' . (int)$myProfileId : '' ?>"
               class="tw-btn"
               style="text-align:center;padding:16px 20px;font-size:16px;">
                ➕ Записать клиента на приём
            </a>
            <a href="/dashboard"
               class="tw-btn tw-btn--ghost"
               style="text-align:center;padding:16px 20px;font-size:16px;">
                📋 Мои записи и расписание
            </a>
            <a href="/dashboard/schedule"
               class="tw-btn tw-btn--ghost"
               style="text-align:center;padding:14px 18px;">
                ⏰ Настроить расписание
            </a>
            <a href="/dashboard/clients"
               class="tw-btn tw-btn--ghost"
               style="text-align:center;padding:14px 18px;">
                👥 Список клиентов
            </a>
            <a href="/dashboard/profile"
               class="tw-btn tw-btn--ghost"
               style="text-align:center;padding:14px 18px;">
                🖼️ Мой профиль
            </a>
            <a href="/dashboard/announcements"
               class="tw-btn tw-btn--ghost"
               style="text-align:center;padding:14px 18px;">
                📢 Мероприятия и памятки
            </a>
        </div>
    </div>

<?php else: ?>

    <h1>Выберите психолога</h1>
    <p class="subtitle">Нажмите на карточку, чтобы посмотреть свободные даты и время</p>

    <form method="get" action="/" style="display:flex;gap:10px;align-items:center;flex-wrap:nowrap;margin:16px 0 18px;">
        <input type="text" name="q" placeholder="Поиск по ФИО" value="<?= htmlspecialchars($search ?? '') ?>"
            style="flex:5 1 0;min-width:200px;height:40px;border:1px solid var(--border);border-radius:var(--radius);padding:0 12px;font-size:14px;margin:0;">

        <select name="direction"
            style="flex:1 0 160px;min-width:140px;height:40px;border:1px solid var(--border);border-radius:var(--radius);padding:0 10px;font-size:14px;margin:0;">
            <option value="">Все направления</option>
            <?php foreach ($directions as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= ($directionId ?? null) == $d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn" style="margin:0;flex:0 0 auto;white-space:nowrap;">Найти</button>
        <?php if (($search ?? '') !== '' || !empty($directionId)): ?>
            <a href="/" class="back-link" style="margin:0;flex:0 0 auto;white-space:nowrap;">Сбросить</a>
        <?php endif; ?>
    </form>

    <?php if (empty($psychologists)): ?>
        <p class="empty-state">
            <?= ($search ?? '') !== '' || !empty($directionId) ? 'По этому запросу никого не нашлось.' : 'Психологи пока не добавлены в базу.' ?>
        </p>
    <?php else: ?>
        <div class="psych-grid">
            <?php foreach ($psychologists as $i => $p): ?>
                <a href="/psychologist?id=<?= (int) $p['id'] ?>" class="psychologist-card">
                    <div class="psychologist-card__cover pattern-<?= $i % 4 ?>">
                        <?php if (!empty($p['photo_path'])): ?>
                            <img src="<?= htmlspecialchars($p['photo_path']) ?>" alt=""
                                style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php endif; ?>
                    </div>
                    <div class="psychologist-card__body">
                        <p class="psychologist-name">
                            <?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?></p>
                        <p class="psychologist-tags"><?= htmlspecialchars($p['directions'] ?? '') ?></p>
                        <?php if (!empty($p['bio'])): ?>
                            <p style="margin:10px 0 0;color:var(--text-secondary);font-size:13px;">
                                <?php
                                $bioText = trim(strip_tags($p['bio']));
                                if (function_exists('mb_strimwidth')) {
                                    $bioText = mb_strimwidth($bioText, 0, 120, '…');
                                } else {
                                    $bioText = substr($bioText, 0, 120) . (strlen($bioText) > 120 ? '…' : '');
                                }
                                ?>
                                <?= htmlspecialchars($bioText) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
