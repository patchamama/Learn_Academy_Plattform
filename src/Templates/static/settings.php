<?php
/**
 * Static template: Settings page.
 * Variables injected by StaticGenerator:
 *   $course
 */
$activePage = 'settings';
?>
<!DOCTYPE html>
<html lang="en" data-course-slug="<?= e($course['slug']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — <?= e($course['title']) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main>
            <h2 class="section-title">Settings</h2>

            <form id="settings-form" style="max-width:600px">

                <div class="settings-section">
                    <h3>Appearance</h3>

                    <div class="settings-row">
                        <label for="setting-theme">Theme</label>
                        <select id="setting-theme">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <span>Font size</span>
                        <div class="font-size-options">
                            <button type="button" class="font-size-btn" data-size="14">A-</button>
                            <button type="button" class="font-size-btn" data-size="16">A</button>
                            <button type="button" class="font-size-btn" data-size="18">A+</button>
                            <button type="button" class="font-size-btn" data-size="20">A++</button>
                        </div>
                    </div>

                    <div class="settings-row">
                        <label for="setting-locale">Language</label>
                        <select id="setting-locale">
                            <option value="en">English</option>
                            <option value="es">Español</option>
                        </select>
                    </div>
                </div>

                <div class="settings-section">
                    <h3>Video</h3>

                    <div class="settings-row">
                        <label for="setting-speed">Default playback speed</label>
                        <select id="setting-speed">
                            <option value="0.5">0.5×</option>
                            <option value="0.75">0.75×</option>
                            <option value="1">1× (normal)</option>
                            <option value="1.25">1.25×</option>
                            <option value="1.5">1.5×</option>
                            <option value="1.75">1.75×</option>
                            <option value="2">2×</option>
                        </select>
                    </div>

                    <div class="settings-row">
                        <label for="setting-subtitles">Show subtitles by default</label>
                        <input type="checkbox" id="setting-subtitles">
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    Save settings
                    <span id="settings-saved-msg"
                          style="display:none;margin-left:8px;color:#4caf50;font-size:0.85rem">
                        ✓ Saved
                    </span>
                </button>
            </form>
        </main>
    </div>
</div>
<script src="assets/runtime.js"></script>
</body>
</html>
