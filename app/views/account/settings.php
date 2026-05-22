<?php
/** @var array $settings */
/** @var array $user */
/** @var string $csrf */

$theme     = $settings['theme'] ?? 'light';
$fontSize  = $settings['font_size'] ?? 16;
$speed     = $settings['speed'] ?? '1';
$subtitles = $settings['subtitles'] ?? false;
$saved     = isset($_GET['saved']);
?>
<h2 class="section-title"><?= e(t('settings.title')) ?></h2>

<?php if ($saved): ?>
<div style="background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:0.75rem 1rem;border-radius:6px;margin-bottom:1.25rem;max-width:600px;font-size:0.875rem">
    <?= e(t('settings.saved')) ?>
</div>
<?php endif; ?>

<form method="POST" action="/account/settings" style="max-width:600px">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>" />

    <!-- Appearance -->
    <div class="settings-section">
        <h3>Appearance</h3>

        <div class="settings-row">
            <label for="theme"><?= e(t('settings.theme')) ?></label>
            <select id="theme" name="theme">
                <option value="light"<?= $theme === 'light' ? ' selected' : '' ?>><?= e(t('settings.theme_light')) ?></option>
                <option value="dark"<?= $theme === 'dark' ? ' selected' : '' ?>><?= e(t('settings.theme_dark')) ?></option>
            </select>
        </div>

        <div class="settings-row">
            <span><?= e(t('settings.font_size')) ?></span>
            <div class="font-size-options">
                <?php foreach ([14, 16, 18, 20] as $size): ?>
                <button type="button" class="font-size-btn<?= $fontSize === $size ? ' active' : '' ?>"
                        data-size="<?= $size ?>"
                        onclick="document.getElementById('font-size-input').value=<?= $size ?>;document.querySelectorAll('.font-size-btn').forEach(b=>b.classList.remove('active'));this.classList.add('active')">
                    <?= $size === 14 ? 'A-' : ($size === 16 ? 'A' : ($size === 18 ? 'A+' : 'A++')) ?>
                </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="font-size-input" name="font_size" value="<?= (int)$fontSize ?>" />
        </div>

        <div class="settings-row">
            <label for="locale"><?= e(t('settings.language')) ?></label>
            <select id="locale" name="locale">
                <option value="en"<?= ($user['locale'] ?? 'en') === 'en' ? ' selected' : '' ?>>English</option>
                <option value="es"<?= ($user['locale'] ?? 'en') === 'es' ? ' selected' : '' ?>>Español</option>
            </select>
        </div>
    </div>

    <!-- Video -->
    <div class="settings-section">
        <h3>Video</h3>

        <div class="settings-row">
            <label for="speed"><?= e(t('settings.speed')) ?></label>
            <select id="speed" name="speed">
                <?php foreach (['0.5', '0.75', '1', '1.25', '1.5', '1.75', '2'] as $s): ?>
                <option value="<?= e($s) ?>"<?= $speed === $s ? ' selected' : '' ?>><?= e($s) ?>&times;</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="settings-row">
            <label for="subtitles"><?= e(t('settings.subtitles')) ?></label>
            <input type="checkbox" id="subtitles" name="subtitles"<?= $subtitles ? ' checked' : '' ?> />
        </div>
    </div>

    <button type="submit" class="submit-btn">
        <?= e(t('settings.save')) ?>
    </button>
</form>
