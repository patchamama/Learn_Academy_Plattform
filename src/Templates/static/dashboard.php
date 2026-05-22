<?php
/**
 * Static template: Course dashboard / overview page.
 * Variables injected by StaticGenerator:
 *   $course     — full course model
 *   $totalLessons
 *   $allLessonIds — JSON-encoded array of all lesson IDs
 */
$activePage = 'dashboard';
$allIds = $allLessonIds ?? '[]';
?>
<!DOCTYPE html>
<html lang="en" data-course-slug="<?= e($course['slug']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($course['title']) ?></title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main>
            <h2>Hello!</h2>
            <p class="progress-text">Your progress</p>

            <div class="progress-bar-wrap" style="max-width:400px">
                <div class="progress-bar-fill"
                     id="course-progress-bar"
                     data-all-lesson-ids="<?= e($allIds) ?>"
                     style="width:0%">
                </div>
            </div>
            <p style="font-size:0.85rem;color:var(--color-text-muted);margin-top:0.4rem">
                <span id="course-progress-label">0%</span> completed &mdash;
                <?= (int)($totalLessons ?? 0) ?> lessons total
            </p>

            <div class="cards" style="margin-top:2rem">
                <a class="card" href="course.html">
                    <i class="fa-solid fa-chalkboard-user" style="font-size:1.4rem;margin-bottom:0.5rem;display:block;color:var(--color-accent)"></i>
                    <h3><?= e($course['title']) ?></h3>
                    <p><?= e($course['description'] ?? '') ?></p>
                </a>

                <a class="card" href="settings.html">
                    <i class="fa-solid fa-gear" style="font-size:1.4rem;margin-bottom:0.5rem;display:block;color:var(--color-accent)"></i>
                    <h3>Settings</h3>
                    <p>Theme, font size, subtitles, playback speed</p>
                </a>
            </div>

            <!-- Sections overview -->
            <h3 class="section-title" style="margin-top:2.5rem">Course sections</h3>
            <div style="max-width:600px">
                <?php foreach (($course['sections'] ?? []) as $si => $section):
                    $sectionLessonIds = array_map(fn($l) => $course['slug'] . '-' . $si . '-' . $l['prefix'], $section['lessons'] ?? []);
                    $sectionIdsJson = htmlspecialchars(json_encode($sectionLessonIds), ENT_QUOTES);
                ?>
                <div style="margin-bottom:1rem;background:var(--color-bg-card);border-radius:8px;padding:1rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.4rem">
                        <span style="font-weight:600;font-size:0.9rem"><?= e($section['title']) ?></span>
                        <span class="progress-text" data-section-id="section-<?= $si ?>">
                            0 / <?= count($section['lessons'] ?? []) ?>
                        </span>
                    </div>
                    <div class="progress-bar-wrap">
                        <div class="progress-bar-fill"
                             data-section-id="section-<?= $si ?>"
                             data-lesson-ids="<?= $sectionIdsJson ?>"
                             style="width:0%">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>
<script src="assets/runtime.js"></script>
</body>
</html>
