<!DOCTYPE html>
<html lang="<?= e(defined('APP_LOCALE') ? APP_LOCALE : 'en') ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($lesson['title'] ?? '') ?> — <?= e($course['title'] ?? '') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="/assets/lessons.css" />
</head>
<body data-lesson-id="<?= e((string)($lesson['id'] ?? '')) ?>"
      data-course-slug="<?= e($course['slug'] ?? '') ?>">

<!-- Dark topbar -->
<header class="topbar">
    <div class="acciones">
        <a href="/" class="back-link topbar-back">
            <i class="fa-solid fa-arrow-left"></i>
            <?= e(t('lesson.back')) ?>
        </a>
    </div>
    <div class="logo">
        <a href="/" style="text-decoration:none;color:inherit">
            <span style="font-weight:700;font-size:1rem;color:var(--color-accent)"><?= e($config['app_name'] ?? 'Learn Academy') ?></span>
        </a>
    </div>
</header>

<!-- Main layout -->
<div class="layout">

    <!-- Lesson sidebar (300px) -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-course-title"><?= e($course['title'] ?? '') ?></div>
            <div class="sidebar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="lesson-search" placeholder="<?= e(t('course.search')) ?>" />
            </div>
        </div>

        <div class="sidebar-lessons">
            <?php foreach (($sections ?? []) as $si => $section): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-header">
                    <?= e($section['title']) ?>
                    <span class="section-progress">
                        <?= count($section['lessons'] ?? []) ?> <?= e(t('course.lessons', ['count' => ''])) ?>
                    </span>
                </div>
                <div class="sidebar-section-lessons">
                    <?php foreach (($section['lessons'] ?? []) as $l):
                        $isActive = isset($lesson) && (int)$l['id'] === (int)$lesson['id'];
                    ?>
                    <a href="/courses/<?= e($course['slug']) ?>/lesson/<?= (int)$l['id'] ?>"
                       class="sidebar-lesson-item<?= $isActive ? ' active' : '' ?>"
                       data-lesson-id="<?= (int)$l['id'] ?>">
                        <i class="fa-solid fa-file-lines"></i>
                        <?= e($l['title']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </aside>

    <!-- Main content -->
    <main class="main-content">
        <?= $content ?>
    </main>
</div>

<script src="/assets/runtime.js"></script>
</body>
</html>
