<?php $pageTitle = 'Профиль психолога'; require __DIR__ . '/../partials/header.php'; ?>

<a href="/dashboard" class="back-link">← Мои записи</a>
<h1>Профиль</h1>
<p class="subtitle">Эти данные видны студенту на карточке психолога.</p>

<div class="card" style="max-width:720px;">
    <form method="post" action="/dashboard/profile">
        <label for="photo_path">Фото (URL или путь)</label>
        <input type="text" id="photo_path" name="photo_path" value="<?= htmlspecialchars($profile['photo_path'] ?? '') ?>" placeholder="/assets/img/psychologist.jpg">

        <label for="bio">Краткая информация (стаж, с какими проблемами работает и т.д.)</label>
        <textarea id="bio" name="bio" rows="5" placeholder="Например: «Стаж 5 лет. Работаю с тревожностью, выгоранием, адаптацией первокурсников…»"><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>

        <?php if (!empty($directions)): ?>
            <p class="section-label" style="margin-top:16px;">Направления</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($directions as $d): ?>
                    <?php $checked = in_array((int)$d['id'], $selected ?? [], true); ?>
                    <label class="tw-chip" style="cursor:pointer;">
                        <input type="checkbox" name="directions[]" value="<?= (int)$d['id'] ?>" <?= $checked ? 'checked' : '' ?> style="width:auto;margin-right:8px;">
                        <?= htmlspecialchars($d['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn" style="margin-top:12px;">Сохранить</button>
    </form>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

