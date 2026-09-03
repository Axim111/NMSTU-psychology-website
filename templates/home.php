<?php $pageTitle = 'Психологи — запись на приём'; require __DIR__ . '/partials/header.php'; ?>

<h1>Выберите психолога</h1>
<p class="subtitle">Нажмите на карточку, чтобы посмотреть свободные даты и время</p>

<div class="tw-filters">
    <div class="tw-search">
        <input type="text" placeholder="Поиск по ФИО…" data-psych-search>
    </div>
    <?php if (!empty($directions)): ?>
        <?php foreach ($directions as $d): ?>
            <span class="tw-chip" role="button" tabindex="0" data-direction-chip="<?= (int)$d['id'] ?>">
                <?= htmlspecialchars($d['name']) ?>
            </span>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if (empty($psychologists)): ?>
    <p class="empty-state">Психологи пока не добавлены в базу.</p>
<?php else: ?>
    <div class="psych-grid" data-psychologists>
        <?php foreach ($psychologists as $i => $p): ?>
            <a
                href="/psychologist?id=<?= (int)$p['id'] ?>"
                class="psychologist-card"
                data-psych-card
                data-psych-name="<?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?>"
                data-psych-directions="<?= htmlspecialchars($p['direction_ids'] ?? '') ?>"
            >
                <div class="psychologist-card__cover pattern-<?= $i % 4 ?>">
                    <?php if (!empty($p['photo_path'])): ?>
                        <img src="<?= htmlspecialchars($p['photo_path']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                    <?php endif; ?>
                </div>
                <div class="psychologist-card__body">
                    <p class="psychologist-name"><?= htmlspecialchars($p['last_name'] . ' ' . $p['first_name'] . ' ' . ($p['patronymic'] ?? '')) ?></p>
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

<?php require __DIR__ . '/partials/footer.php'; ?>
