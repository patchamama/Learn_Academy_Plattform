<?php
/**
 * Static template: Course detail page (sections + lessons accordion).
 * Variables injected by StaticGenerator:
 *   $course
 *   $totalLessons
 *   $totalSections
 */
$activePage = 'courses';
?>
<!DOCTYPE html>
<html lang="en" data-course-slug="<?= e($course['slug']) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($course['title']) ?> — Course</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/partials/header.php'; ?>

        <main>
            <div class="course-layout">
                <!-- Left: sections accordion -->
                <div class="course-sections">
                    <h2 class="section-title">Course content</h2>
                    <div class="accordion">
                        <?php foreach (($course['sections'] ?? []) as $si => $section):
                            $lessonCount = count($section['lessons'] ?? []);
                        ?>
                        <div class="accordion-item active">
                            <div class="accordion-header">
                                <?= e($section['title']) ?>
                                <span style="font-size:0.75rem;font-weight:400;color:var(--color-text-muted)">
                                    <?= $lessonCount ?> lesson<?= $lessonCount !== 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <div class="accordion-content">
                                <ul class="lesson-list">
                                    <?php foreach (($section['lessons'] ?? []) as $li => $lesson):
                                        $lessonId   = $course['slug'] . '-' . $si . '-' . $lesson['prefix'];
                                        $lessonFile = 'lesson-' . $si . '-' . $lesson['prefix'] . '.html';
                                        $type       = $lesson['main_content']['type'] ?? 'text';
                                        $icon       = match($type) {
                                            'video' => 'fa-video',
                                            'audio' => 'fa-music',
                                            default => 'fa-file-lines',
                                        };
                                    ?>
                                    <li data-lesson-id="<?= e($lessonId) ?>">
                                        <a href="<?= e($lessonFile) ?>">
                                            <i class="fa-solid <?= $icon ?>"></i>
                                            <?= e($lesson['title']) ?>
                                        </a>
                                        <?php if (!empty($lesson['attachments'])): ?>
                                            <span style="font-size:0.72rem;color:var(--color-text-muted)">
                                                <i class="fa-solid fa-paperclip"></i>
                                            </span>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right: course info card -->
                <div class="course-info-card">
                    <div class="card-banner">
                        <?php if (!empty($course['thumbnail'])): ?>
                            <img src="<?= e($course['thumbnail']) ?>" alt="<?= e($course['title']) ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:var(--color-primary);display:flex;align-items:center;justify-content:center">
                                <i class="fa-solid fa-graduation-cap" style="font-size:3rem;color:var(--color-accent)"></i>
                            </div>
                        <?php endif; ?>
                        <span class="tag"><?= e($course['slug']) ?></span>
                    </div>
                    <div class="card-body">
                        <h3>About this course</h3>
                        <?php if (!empty($course['description'])): ?>
                            <p style="font-size:0.85rem;color:var(--color-text-muted);margin-bottom:1rem">
                                <?= e($course['description']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="course-stats">
                            <span><i class="fa-solid fa-file"></i> <?= (int)$totalLessons ?> lessons</span>
                            <span><i class="fa-solid fa-layer-group"></i> <?= (int)$totalSections ?> sections</span>
                        </div>

                        <!-- Overall progress bar -->
                        <?php
                        $allIds = [];
                        foreach (($course['sections'] ?? []) as $si => $section) {
                            foreach (($section['lessons'] ?? []) as $lesson) {
                                $allIds[] = $course['slug'] . '-' . $si . '-' . $lesson['prefix'];
                            }
                        }
                        $allIdsJson = htmlspecialchars(json_encode($allIds), ENT_QUOTES);
                        ?>
                        <p class="progress-text" style="margin-top:0.75rem">
                            <span id="course-progress-label">0%</span> completed
                        </p>
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill"
                                 id="course-progress-bar"
                                 data-all-lesson-ids="<?= $allIdsJson ?>"
                                 style="width:0%">
                            </div>
                        </div>

                        <?php
                        $firstLesson = null;
                        foreach ($course['sections'] as $si => $section) {
                            if (!empty($section['lessons'])) {
                                $l = $section['lessons'][0];
                                $firstLesson = 'lesson-' . $si . '-' . $l['prefix'] . '.html';
                                break;
                            }
                        }
                        ?>
                        <a href="<?= e($firstLesson ?? '#') ?>" class="btn btn-primary btn-course">
                            Continue learning
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="assets/runtime.js"></script>
</body>
</html>
